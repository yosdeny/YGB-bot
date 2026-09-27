<?php
/**
 * Parte pública del plugin: assets, autoload del widget y WP_Widget clásico.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_Public
 */
class Class_Ygb_Public {

	/**
	 * ¿Se han encolado ya los assets? (evita duplicados).
	 *
	 * @var bool
	 */
	private static $enqueued = false;

	/**
	 * Registra los hooks de frontend.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'autoload_widget' ), 50 );
	}

	/**
	 * Carga global si la opción "autoload" está activa.
	 *
	 * @return void
	 */
	public static function maybe_enqueue_assets() {
		if ( Class_Ygb_DB::get_setting( 'autoload' ) && Class_Ygb_DB::get_setting( 'enabled' ) ) {
			self::enqueue_assets();
		}
	}

	/**
	 * Encola CSS/JS solo cuando es necesario.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		if ( self::$enqueued || is_admin() ) {
			return;
		}
		self::$enqueued = true;

		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$ver     = YGB_BOT_VERSION;
		$file_css = YGB_BOT_PATH . 'public/css/ygb-bot' . $suffix . '.css';
		$file_js  = YGB_BOT_PATH . 'public/js/ygb-bot' . $suffix . '.js';

		wp_enqueue_style(
			'ygb-bot',
			YGB_BOT_URL . 'public/css/ygb-bot' . $suffix . '.css',
			array(),
			file_exists( $file_css ) ? $ver : $ver
		);

		wp_enqueue_script(
			'ygb-bot',
			YGB_BOT_URL . 'public/js/ygb-bot' . $suffix . '.js',
			array(), // Sin dependencia de jQuery: JS puro.
			file_exists( $file_js ) ? $ver : $ver,
			true
		);

		$settings = Class_Ygb_DB::get_settings();

		// Variables para el JS (solo datos públicos).
		wp_localize_script(
			'ygb-bot',
			'ygbBotConfig',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'ygb_public' ),
				'settings'  => array(
					'botName'       => $settings['bot_name'],
					'avatar'        => $settings['avatar'],
					'position'      => $settings['position'],
					'color'         => $settings['color'],
					'bubbleIcon'    => $settings['bubble_icon'],
					'size'          => $settings['size'],
					'welcome'       => $settings['welcome'],
					'placeholder'   => $settings['placeholder'],
					'showTopics'    => (bool) $settings['show_topics'],
					'showFaq'       => (bool) $settings['show_faq'],
					'saveHistory'   => (bool) $settings['save_history'],
					'lazyLoad'      => (bool) $settings['lazy_load'],
					'showSupportBtn' => (bool) $settings['show_support_btn'],
					'requireConsent' => (bool) $settings['require_consent'],
					'storeChats'    => (bool) $settings['store_chats'],
					'privacyUrl'    => $settings['privacy_url'],
					'consentText'   => __( 'Al continuar aceptas que esta conversación pueda guardarse para mejorar el servicio. Consulta nuestra política de privacidad.', 'ygb-bot' ),
					'i18n'          => array(
						'online'        => __( 'En línea', 'ygb-bot' ),
						'typing'        => __( 'escribiendo…', 'ygb-bot' ),
						'support'       => __( '💬 Hablar con soporte', 'ygb-bot' ),
						'send'          => __( 'Enviar', 'ygb-bot' ),
						'openChat'      => __( 'Abrir chat', 'ygb-bot' ),
						'closeChat'     => __( 'Minimizar chat', 'ygb-bot' ),
						'topicsTitle'   => __( 'Elige un tema:', 'ygb-bot' ),
						'faqTitle'      => __( 'Preguntas frecuentes:', 'ygb-bot' ),
						'suggestTitle'  => __( '¿Quisiste decir?', 'ygb-bot' ),
						'error'         => __( 'Ups, algo falló. Inténtalo de nuevo.', 'ygb-bot' ),
						'consentDecline' => __( 'Puedes seguir usando el bot, pero no guardaremos la conversación.', 'ygb-bot' ),
					),
				),
			)
		);

		// Variables CSS de marca en inline (barato y cacheable por página).
		$custom_css = sprintf(
			':root{--ygb-color:%1$s;--ygb-color-dark:%2$s;}',
			Class_Ygb_DB::sanitize_hex( $settings['color'] ),
			self::darken_hex( $settings['color'] )
		);
		wp_add_inline_style( 'ygb-bot', $custom_css );
	}

	/**
	 * Oscurece un hex para hover/bordes.
	 *
	 * @param string $hex Color.
	 * @return string
	 */
	public static function darken_hex( $hex ) {
		$hex  = trim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = max( 0, (int) hexdec( substr( $hex, 0, 2 ) ) - 25 );
		$g = max( 0, (int) hexdec( substr( $hex, 2, 2 ) ) - 25 );
		$b = max( 0, (int) hexdec( substr( $hex, 4, 2 ) ) - 25 );
		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}

	/**
	 * Autocarga el widget flotante en todo el sitio si está activado.
	 *
	 * @return void
	 */
	public static function autoload_widget() {
		if ( ! Class_Ygb_DB::get_setting( 'autoload' ) || ! Class_Ygb_DB::get_setting( 'enabled' ) ) {
			return;
		}
		self::enqueue_assets();
		echo Class_Ygb_Shortcode::render( array( 'inline' => 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML saneado internamente.
	}

	/**
	 * Registra el widget clásico de WordPress.
	 *
	 * @return void
	 */
	public static function register_classic_widget() {
		register_widget( 'Class_Ygb_Public_Widget' );
	}
}

/**
 * Widget clásico (Apariencia → Widgets).
 */
class Class_Ygb_Public_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'ygb_bot_widget',
			__( 'YGB Bot (chat)', 'ygb-bot' ),
			array( 'description' => __( 'Muestra el chatbot YGB Bot incrustado en una zona de widgets.', 'ygb-bot' ) )
		);
	}

	/**
	 * Front-end del widget.
	 *
	 * @param array $args     Args de sidebar.
	 * @param array $instance Valores guardados.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Class_Ygb_Shortcode::render( array( 'inline' => 1, 'theme' => 'light' ) );
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Formulario del admin del widget.
	 *
	 * @param array $instance Valores actuales.
	 * @return void
	 */
	public function form( $instance ) {
		echo '<p>' . esc_html__( 'El widget no requiere ajustes: usa la configuración de YGB Bot.', 'ygb-bot' ) . '</p>';
	}

	/**
	 * Sanitiza valores del formulario.
	 *
	 * @param array $new_instance Nueva entrada.
	 * @param array $old_instance Instancia previa.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		return array();
	}
}
