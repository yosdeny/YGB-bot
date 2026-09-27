<?php
/**
 * Desactivador del plugin.
 *
 * No elimina datos: la limpieza total ocurre en uninstall.php.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_Deactivator
 */
class Class_Ygb_Deactivator {

	/**
	 * Hook de desactivación.
	 *
	 * @param bool $network_wide Desactivación de red.
	 * @return void
	 */
	public static function deactivate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
			foreach ( $ids as $id ) {
				switch_to_blog( $id );
				self::single_deactivate();
				restore_current_blog();
			}
			return;
		}
		self::single_deactivate();
	}

	/**
	 * Limpieza ligera por sitio.
	 *
	 * @return void
	 */
	public static function single_deactivate() {
		Class_Ygb_Search_Engine::invalidate_cache();
		delete_transient( 'ygb_bot_stats' );
	}
}
