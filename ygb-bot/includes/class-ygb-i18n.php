<?php
/**
 * Carga de textos, dominio de i18n y assets de traducción.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_I18n
 */
class Class_Ygb_I18n {

	/**
	 * Carga el dominio de textos del plugin.
	 *
	 * @return void
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain(
			'ygb-bot',
			false,
			dirname( plugin_basename( YGB_BOT_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}
