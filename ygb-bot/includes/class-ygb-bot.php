<?php
/**
 * Bootstrap principal del plugin (singleton).
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_Bot
 */
class Class_Ygb_Bot {

	/**
	 * Instancia única.
	 *
	 * @var Class_Ygb_Bot|null
	 */
	private static $instance = null;

	/**
	 * Versión de BD instalada y migraciones si procede.
	 */
	public function maybe_upgrade() {
		if ( get_option( 'ygb_bot_db_version' ) !== YGB_BOT_DB_VERSION ) {
			require_once YGB_BOT_PATH . 'includes/class-ygb-activator.php';
			Class_Ygb_Activator::create_tables();
			update_option( 'ygb_bot_db_version', YGB_BOT_DB_VERSION );
		}
	}

	/**
	 * Devuelve la instancia única.
	 *
	 * @return Class_Ygb_Bot
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: arranca componentes según contexto.
	 */
	private function __construct() {
		$this->maybe_upgrade();

		// Componentes comunes.
		Class_Ygb_Shortcode::register();
		Class_Ygb_Block::register();
		Class_Ygb_Ajax::register_hooks();

				add_action( 'widgets_init', array( 'Class_Ygb_Public', 'register_classic_widget' ) );

		if ( is_admin() ) {
			require_once YGB_BOT_PATH . 'admin/class-ygb-admin.php';
			$admin = new Class_Ygb_Admin();
			$admin->hooks();
		} else {
			require_once YGB_BOT_PATH . 'public/class-ygb-public.php';
			$public = new Class_Ygb_Public();
			$public->hooks();
		}
	}

	/**
	 * Evita clonado.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Deserialización no permitida.
	 *
	 * @throws Exception Siempre.
	 * @return void
	 */
	public function __wakeup() {
		throw new Exception( 'YGB Bot: no se permite deserializar el singleton.' );
	}
}
