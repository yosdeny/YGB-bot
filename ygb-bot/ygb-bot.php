<?php
/**
 * Plugin Name:       YGB Bot
 * Plugin URI:        https://ygb.example/ygb-bot
 * Description:       Chatbot de preguntas y respuestas basado en reglas (sin IA), con derivación a soporte por email/WhatsApp, RGPD y 100% local.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            YGB
 * Author URI:        https://ygb.example
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ygb-bot
 * Domain Path:       /languages
 * Tags:              chatbot, faq, support, widget, gdpr
 */

/**
 * Plugin principal de YGB Bot.
 *
 * Chatbot de preguntas y respuestas basado en reglas, sin IA y sin
 * dependencias externas. 100% funcional en local.
 *
 * @package    YGB_Bot
 * @author     YGB <soporte@ygb.example>
 * @copyright  2026 YGB
 * @license    GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link       https://ygb.example
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit; // Acceso directo bloqueado.

if ( ! defined( 'YGB_BOT_VERSION' ) ) {
	define( 'YGB_BOT_VERSION', '1.0.0' );
}

if ( ! defined( 'YGB_BOT_DB_VERSION' ) ) {
	define( 'YGB_BOT_DB_VERSION', '1.0.0' );
}

if ( ! defined( 'YGB_BOT_PLUGIN_FILE' ) ) {
	define( 'YGB_BOT_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'YGB_BOT_PATH' ) ) {
	define( 'YGB_BOT_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'YGB_BOT_URL' ) ) {
	define( 'YGB_BOT_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Autocargador PSR-8 (estilo WordPress) para las clases del plugin.
 *
 * Carga Class_Ygb_Foo desde includes/class-ygb-foo.php,
 * admin/class-ygb-foo.php o public/class-ygb-foo.php.
 *
 * @param string $class_name Nombre completo de la clase.
 * @return void
 */
function ygb_autoload( $class_name ) {
	$prefix = 'Class_Ygb_';
	if ( 0 !== strpos( $class_name, $prefix ) ) {
		return;
	}

	$slug = strtolower( str_replace( '_', '-', substr( $class_name, strlen( $prefix ) ) ) );
	$file = 'class-ygb-' . $slug . '.php';

	foreach ( array( 'includes', 'admin', 'public' ) as $dir ) {
		$path = YGB_BOT_PATH . $dir . '/' . $file;
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}
}
spl_autoload_register( 'ygb_autoload' );

/**
 * Devuelve la instancia única del plugin (singleton).
 *
 * @return Class_Ygb_Bot
 */
function ygb_bot() {
	return Class_Ygb_Bot::get_instance();
}

/**
 * Renderiza el widget del bot desde un tema (función de plantilla).
 *
 * Uso en temas: <?php if ( function_exists( 'ygb_bot_render' ) ) { ygb_bot_render(); } ?>
 *
 * @param array $args Atributos opcionales del shortcode.
 * @return string|void HTML del widget cuando $echo es false implícitamente
 *                     (imprime directamente si se llama en una plantilla).
 */
function ygb_bot_render( $args = array(), $echo = true ) {
	$html = Class_Ygb_Shortcode::render( wp_parse_args( $args, array( 'inline' => 0 ) ) );
	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML ya escapado internamente.
	}
	return $html;
}

// Activación / desactivación / desinstalación.
register_activation_hook( __FILE__, array( 'Class_Ygb_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Class_Ygb_Deactivator', 'deactivate' ) );

// Arranque del plugin.
add_action( 'plugins_loaded', 'ygb_bot' );
