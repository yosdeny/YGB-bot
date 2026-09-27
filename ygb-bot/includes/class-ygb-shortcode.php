<?php
/**
 * Shortcode [ygb_bot] para incrustar el widget.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_Shortcode
 */
class Class_Ygb_Shortcode {

	/**
	 * Registra el shortcode.
	 *
	 * @return void
	 */
	public static function register() {
		add_shortcode( 'ygb_bot', array( __CLASS__, 'handle' ) );
	}

	/**
	 * Callback del shortcode.
	 *
	 * @param array $atts Atributos.
	 * @return string HTML.
	 */
	public static function handle( $atts = array() ) {
		return self::render( (array) $atts );
	}

	/**
	 * Renderiza el widget.
	 *
	 * @param array $atts {
	 *     @type string $theme Tema del widget: light|dark.
	 *     @type int    $inline 1 para render inline (dentro del contenido).
	 * }
	 * @return string
	 */
	public static function render( $atts = array() ) {
		if ( ! Class_Ygb_DB::get_setting( 'enabled' ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'theme'  => 'light',
				'inline' => 0,
			),
			(array) $atts,
			'ygb_bot'
		);

		// Asegurar que los assets vayan a salir en el footer.
		Class_Ygb_Public::enqueue_assets();

		ob_start();
		$ygb_inline = ! empty( $atts['inline'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$ygb_theme  = in_array( $atts['theme'], array( 'light', 'dark' ), true ) ? $atts['theme'] : 'light'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		include YGB_BOT_PATH . 'public/partials/widget.php';
		return (string) ob_get_clean();
	}
}
