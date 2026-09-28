<?php
/**
 * Fallback y derivación a soporte humano.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_Fallback
 */
class Class_Ygb_Fallback {

	/**
	 * Palabras clave que indican intención de hablar con un humano.
	 *
	 * Filtrables con 'ygb_human_intents'.
	 *
	 * @return array<string>
	 */
	public static function human_intents() {
		$intents = array(
			'humano',
			'persona',
			'real persona',
			'agente',
			'soporte',
			'ayuda real',
			'operador',
		);
		/** Permite añadir/alterar las intenciones de derivación directa. */
		return apply_filters( 'ygb_human_intents', $intents );
	}

	/**
	 * ¿El mensaje pide explícitamente un agente humano?
	 *
	 * @param string $message Mensaje normalizado o crudo.
	 * @return bool
	 */
	public static function is_human_intent( $message ) {
		$norm = Class_Ygb_Search_Engine::normalize( $message );
		if ( '' === $norm ) {
			return false;
		}
		foreach ( self::human_intents() as $intent ) {
			$i = Class_Ygb_Search_Engine::normalize( $intent );
			if ( '' !== $i && false !== strpos( $norm, $i ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Canales de soporte humano configurados (correo, WhatsApp, contacto, ticket).
	 *
	 * Se reutiliza tanto en el fallback automático como en el botón persistente
	 * "Hablar con soporte" del widget.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function support_channels() {
		$settings = Class_Ygb_DB::get_settings();
		$channels = array();

		if ( ! empty( $settings['dc_email'] ) && ! empty( $settings['support_email'] ) ) {
			$channels[] = array(
				'id'   => 'email',
				'text' => __( '✉️ Enviar correo', 'ygb-bot' ),
				'url'  => 'mailto:' . rawurlencode( $settings['support_email'] ) . '?subject=' . rawurlencode( $settings['email_subject'] ),
			);
		}
		if ( ! empty( $settings['dc_whatsapp'] ) && ! empty( $settings['whatsapp'] ) ) {
			$number     = preg_replace( '/\D+/', '', $settings['whatsapp'] );
			$channels[] = array(
				'id'   => 'whatsapp',
				'text' => __( '📱 WhatsApp', 'ygb-bot' ),
				'url'  => 'https://wa.me/' . rawurlencode( $number ),
			);
		}
		if ( ! empty( $settings['dc_contact'] ) && ! empty( $settings['contact_url'] ) ) {
			$channels[] = array(
				'id'   => 'contacto',
				'text' => __( '📄 Página de contacto', 'ygb-bot' ),
				'url'  => $settings['contact_url'],
			);
		}
		if ( ! empty( $settings['dc_form'] ) ) {
			$channels[] = array(
				'id'     => 'formulario',
				'text'   => __( '📝 Abrir ticket interno', 'ygb-bot' ),
				'action' => 'ticket',
			);
		}

		return $channels;
	}

	/**
	 * Mensaje que se muestra al ofrecer los canales de soporte humano.
	 *
	 * @return string
	 */
	public static function support_message() {
		return Class_Ygb_DB::get_setting( 'fallback_msg' );
	}

	/**
	 * Construye la respuesta de fallback (sin consultar).
	 *
	 * @param array $suggest Sugerencias del motor de búsqueda.
	 * @return array Respuesta para el front-end.
	 */
	public static function build_response( $suggest = array() ) {
		$settings  = Class_Ygb_DB::get_settings();
		$channels = self::support_channels();

		$suggestions = array();
		foreach ( (array) $suggest as $s ) {
			$suggestions[] = array(
				'id'       => (int) $s->id,
				'pregunta' => wp_strip_all_tags( $s->pregunta ),
			);
		}

		return array(
			'matched'    => 0,
			'fallback'   => 1,
			'message'    => $settings['fallback_msg'],
			'channels'   => $channels,
			'suggestions' => $suggestions,
		);
	}

	/**
	 * Registra el fallback en BD.
	 *
	 * @param string $message Mensaje del usuario.
	 * @return void
	 */
	public static function log( $message ) {
		Class_Ygb_DB::add_fallback( $message );
	}

	/**
	 * Crea un ticket (derivación por formulario) y notifica al admin.
	 *
	 * @param int    $conv_id  ID de conversación.
	 * @param string $message  Mensaje del usuario.
	 * @param string $transcript Conversación formateada en texto.
	 * @return true|WP_Error
	 */
	public static function create_ticket( $conv_id, $message, $transcript = '' ) {
		$settings = Class_Ygb_DB::get_settings();
		$to       = sanitize_email( $settings['support_email'] ? $settings['support_email'] : get_option( 'admin_email' ) );

		if ( ! $to ) {
			return new WP_Error( 'ygb_no_email', __( 'No hay correo de soporte configurado.', 'ygb-bot' ) );
		}

		$id = Class_Ygb_DB::add_derivacion( $conv_id, 'formulario', $message, 'nuevo' );
		if ( ! $id ) {
			return new WP_Error( 'ygb_db', __( 'No se pudo guardar el ticket.', 'ygb-bot' ) );
		}

		$subject = sprintf(
			/* translators: %s: asunto del sitio. */
			__( '[%s] Nuevo ticket del chatbot #%d', 'ygb-bot' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			(int) $id
		);

		$body = sprintf(
			/* translators: placeholders de texto. */
			__( "Se ha creado un nuevo ticket desde el chatbot YGB Bot.\r\n\r\nTicket: #%d\r\nMensaje del usuario:\r\n%s\r\n\r\nConversación:\r\n%s\r\n\r\nGestionar: %s", 'ygb-bot' ),
			(int) $id,
			$message,
			$transcript ? $transcript : '—',
			admin_url( 'admin.php?page=ygb-bot-registros' )
		);

		$sent = wp_mail( $to, $subject, $body );

		/**
		 * Acción tras crear un ticket.
		 *
		 * @param int    $id      ID de derivación.
		 * @param string $message Mensaje.
		 * @param bool   $sent    ¿Se envió el correo?
		 */
		do_action( 'ygb_ticket_created', $id, $message, $sent );

		return true;
	}

	/**
	 * Formatea una conversación como texto plano (para mail/wa.me).
	 *
	 * @param array $messages Lista de mensajes {emisor,mensaje}.
	 * @return string
	 */
	public static function format_transcript( $messages ) {
		$lines = array();
		foreach ( (array) $messages as $m ) {
			$who   = 'bot' === $m['emisor'] ? Class_Ygb_DB::get_setting( 'bot_name' ) : __( 'Usuario', 'ygb-bot' );
			$text  = wp_strip_all_tags( (string) $m['mensaje'] );
			$lines[] = $who . ': ' . $text;
		}
		return implode( "\n", $lines );
	}
}
