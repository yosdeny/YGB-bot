<?php
/**
 * Handler de peticiones AJAX del widget (frontend) y del admin.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_Ajax
 */
class Class_Ygb_Ajax {

	/**
	 * Registra los hooks AJAX.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		// Frontend (usuarios conectados y anónimos).
		add_action( 'wp_ajax_ygb_chat', array( __CLASS__, 'ajax_chat' ) );
		add_action( 'wp_ajax_nopriv_ygb_chat', array( __CLASS__, 'ajax_chat' ) );
		add_action( 'wp_ajax_ygb_start', array( __CLASS__, 'ajax_start' ) );
		add_action( 'wp_ajax_nopriv_ygb_start', array( __CLASS__, 'ajax_start' ) );
		add_action( 'wp_ajax_ygb_derive', array( __CLASS__, 'ajax_derive' ) );
		add_action( 'wp_ajax_nopriv_ygb_derive', array( __CLASS__, 'ajax_derive' ) );
	}

	/**
	 * Verifica el nonce público y termina con error si falla.
	 *
	 * @return void
	 */
	private static function check_public_nonce() {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ygb_public' ) ) {
			wp_send_json_error( array( 'message' => __( 'Petición no válida.', 'ygb-bot' ) ), 403 );
		}
	}

	/**
	 * Obtiene la IP del visitante (respeta cabeceras habituales de proxy).
	 *
	 * @return string
	 */
	private static function get_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return substr( $ip, 0, 45 );
	}

	/**
	 * Inicio de widget: devuelve temas, FAQ sugeridas y configuración pública.
	 *
	 * @return void
	 */
	public static function ajax_start() {
		self::check_public_nonce();

		if ( ! Class_Ygb_DB::get_setting( 'enabled' ) ) {
			wp_send_json_error( array( 'message' => __( 'El bot está desactivado.', 'ygb-bot' ) ), 403 );
		}

		$session_id = isset( $_REQUEST['session'] ) ? preg_replace( '/[^a-f0-9]/', '', substr( sanitize_text_field( wp_unslash( $_REQUEST['session'] ) ), 0, 64 ) ) : '';
		if ( ! $session_id ) {
			$session_id = bin2hex( random_bytes( 16 ) ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.random_bytesFound -- PHP 7.4+.
		}

		$conv_id = Class_Ygb_DB::create_conversacion(
			$session_id,
			self::get_ip(),
			isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : ''
		);

		$temas      = array();
		foreach ( Class_Ygb_DB::get_temas( 1 ) as $t ) {
			$temas[] = array(
				'id'    => (int) $t->id,
				'nombre' => $t->nombre,
				'icono'  => $t->icono,
			);
		}

		$faq = array();
		global $wpdb;
		$t        = Class_Ygb_DB::tables();
		$faq_rows = $wpdb->get_results( "SELECT id, pregunta FROM {$t['preguntas']} WHERE activo = 1 ORDER BY prioridad DESC, contador DESC LIMIT 5" ); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ( (array) $faq_rows as $f ) {
			$faq[] = array(
				'id'       => (int) $f->id,
				'pregunta' => $f->pregunta,
			);
		}

		wp_send_json_success(
			array(
				'session' => $session_id,
				'conv_id' => (int) $conv_id,
				'temas'   => $temas,
				'faq'     => $faq,
			)
		);
	}

	/**
	 * Consulta directa por ID de pregunta (botones de tema/FAQ).
	 *
	 * @param int $id ID de pregunta.
	 * @return array Respuesta lista para JSON.
	 */
	private static function answer_by_question( $id ) {
		$q = Class_Ygb_DB::get_pregunta( absint( $id ) );
		if ( ! $q || ! $q->activo ) {
			return Class_Ygb_Fallback::build_response();
		}
		Class_Ygb_DB::bump_contador( $q->id );
		return array(
			'matched'  => 1,
			'score'    => 100,
			'question' => array(
				'id'       => (int) $q->id,
				'pregunta' => $q->pregunta,
			),
			'answer'   => Class_Ygb_Search_Engine::format_answer( $q ),
		);
	}

	/**
	 * Procesa un mensaje de chat.
	 *
	 * @return void
	 */
	public static function ajax_chat() {
		self::check_public_nonce();

		if ( ! Class_Ygb_DB::get_setting( 'enabled' ) ) {
			wp_send_json_error( array( 'message' => __( 'El bot está desactivado.', 'ygb-bot' ) ), 403 );
		}

		$message   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$conv_id   = isset( $_POST['conv_id'] ) ? absint( $_POST['conv_id'] ) : 0;
		$qid       = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
		$fails     = isset( $_POST['fails'] ) ? min( 10, absint( $_POST['fails'] ) ) : 0;
		$transcript = isset( $_POST['transcript'] ) ? sanitize_textarea_field( wp_unslash( $_POST['transcript'] ) ) : '';

		$message = trim( mb_substr( $message, 0, 500 ) );

		if ( '' === $message && ! $qid ) {
			wp_send_json_error( array( 'message' => __( 'Mensaje vacío.', 'ygb-bot' ) ), 400 );
		}

		// Registrar mensaje del usuario.
		if ( $message ) {
			Class_Ygb_DB::add_mensaje( $conv_id, 'user', $message );
		}

		/* 1) Petición directa por botón (question_id). */
		if ( $qid ) {
			$payload = self::answer_by_question( $qid );
			if ( ! empty( $payload['matched'] ) ) {
				Class_Ygb_DB::add_mensaje( $conv_id, 'bot', wp_strip_all_tags( $payload['answer'] ), $qid, 100 );
			}
			$payload['fails'] = $fails;
			wp_send_json_success( $payload );
		}

		/* 2) Intención humana → derivación directa. */
		if ( Class_Ygb_Fallback::is_human_intent( $message ) ) {
			$payload              = Class_Ygb_Fallback::build_response();
			$payload['human']     = 1;
			$payload['fails']     = $fails;
			Class_Ygb_DB::add_mensaje( $conv_id, 'bot', $payload['message'] );
			wp_send_json_success( $payload );
		}

		/* 3) Búsqueda en la base de conocimiento. */
		$res = Class_Ygb_Search_Engine::search( $message );

		if ( $res['matched'] && $res['question'] ) {
			$q = $res['question'];
			Class_Ygb_DB::bump_contador( $q->id );
			$answer = Class_Ygb_Search_Engine::format_answer( $q );
			Class_Ygb_DB::add_mensaje( $conv_id, 'bot', wp_strip_all_tags( $answer ), $q->id, $res['score'] );

			wp_send_json_success(
				array(
					'matched'  => 1,
					'score'    => $res['score'],
					'question' => array(
						'id'       => (int) $q->id,
						'pregunta' => $q->pregunta,
					),
					'answer'   => $answer,
					'fails'    => 0,
				)
			);
		}

		/* 4) Fallback (con sugerencias e insistencia). */
		Class_Ygb_Fallback::log( $message );
		$payload          = Class_Ygb_Fallback::build_response( $res['suggest'] );
		$payload['fails'] = $fails + 1;

		// Al tercer fallo consecutivo se ofrece derivación explícita.
		if ( $payload['fails'] >= 3 ) {
			$payload['message'] .= ' ' . __( 'Llevamos varios intentos sin éxito: puedo ponerte en contacto con una persona del equipo ahora mismo.', 'ygb-bot' );
			$payload['insist']  = 1;
		}

		Class_Ygb_DB::add_mensaje( $conv_id, 'bot', $payload['message'] );

		/**
		 * Acción tras un fallback.
		 *
		 * @param string $message Mensaje sin resolver.
		 * @param array  $payload Respuesta enviada.
		 */
		do_action( 'ygb_fallback', $message, $payload );

		wp_send_json_success( $payload );
	}

	/**
	 * Registra una derivación elegida por el usuario.
	 *
	 * @return void
	 */
	public static function ajax_derive() {
		self::check_public_nonce();

		$canal      = isset( $_POST['canal'] ) ? sanitize_key( wp_unslash( $_POST['canal'] ) ) : '';
		$conv_id    = isset( $_POST['conv_id'] ) ? absint( $_POST['conv_id'] ) : 0;
		$message    = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$transcript = isset( $_POST['transcript'] ) ? sanitize_textarea_field( wp_unslash( $_POST['transcript'] ) ) : '';
		$message    = mb_substr( $message, 0, 1000 );

		$allowed = array( 'email', 'whatsapp', 'contacto', 'formulario' );
		if ( ! in_array( $canal, $allowed, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Canal no válido.', 'ygb-bot' ) ), 400 );
		}

		if ( 'formulario' === $canal ) {
			$ticket = Class_Ygb_Fallback::create_ticket( $conv_id, $message, $transcript );
			if ( is_wp_error( $ticket ) ) {
				wp_send_json_error( array( 'message' => $ticket->get_error_message() ), 500 );
			}
			wp_send_json_success(
				array(
					'message' => __( '✅ Ticket creado. Un agente te responderá muy pronto por correo.', 'ygb-bot' ),
					'ticket'  => true,
				)
			);
		}

		// Email / WhatsApp / contacto: se registra y el front abre el enlace.
		Class_Ygb_DB::add_derivacion( $conv_id, $canal, $message, 'nuevo' );

		$settings = Class_Ygb_DB::get_settings();
		$url      = '';
		$body     = trim( $message . ( $transcript ? "\n\n---\n" . $transcript : '' ) );

		if ( 'email' === $canal && $settings['support_email'] ) {
			$url = 'mailto:' . rawurlencode( $settings['support_email'] )
				. '?subject=' . rawurlencode( $settings['email_subject'] )
				. '&body=' . rawurlencode( $body );
		} elseif ( 'whatsapp' === $canal && $settings['whatsapp'] ) {
			$number = preg_replace( '/\D+/', '', $settings['whatsapp'] );
			$url    = 'https://wa.me/' . rawurlencode( $number ) . '?text=' . rawurlencode( $body );
		} elseif ( 'contacto' === $canal && $settings['contact_url'] ) {
			$url = $settings['contact_url'];
		}

		wp_send_json_success( array( 'url' => $url ) );
	}
}
