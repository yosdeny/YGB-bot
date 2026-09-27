<?php
/**
 * Registro del menú de administración y render de vistas.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_Admin_Menu
 */
class Class_Ygb_Admin_Menu {

	/**
	 * Slug base del menú.
	 */
	const SLUG = 'ygb-bot';

	/**
	 * Registra el menú y submenús.
	 *
	 * @return void
	 */
	public static function register() {
		add_menu_page(
			__( 'YGB Bot', 'ygb-bot' ),
			__( 'YGB Bot', 'ygb-bot' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-format-chat',
			30
		);

		add_submenu_page( self::SLUG, __( 'Dashboard', 'ygb-bot' ), __( 'Dashboard', 'ygb-bot' ), 'manage_options', self::SLUG, array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( self::SLUG, __( 'Temas', 'ygb-bot' ), __( 'Temas', 'ygb-bot' ), 'manage_options', self::SLUG . '-temas', array( __CLASS__, 'render_temas' ) );
		add_submenu_page( self::SLUG, __( 'Preguntas', 'ygb-bot' ), __( 'Preguntas', 'ygb-bot' ), 'manage_options', self::SLUG . '-preguntas', array( __CLASS__, 'render_preguntas' ) );
		add_submenu_page( self::SLUG, __( 'Apariencia', 'ygb-bot' ), __( 'Apariencia', 'ygb-bot' ), 'manage_options', self::SLUG . '-apariencia', array( __CLASS__, 'render_apariencia' ) );
		add_submenu_page( self::SLUG, __( 'Derivación', 'ygb-bot' ), __( 'Derivación', 'ygb-bot' ), 'manage_options', self::SLUG . '-derivacion', array( __CLASS__, 'render_derivacion' ) );
		add_submenu_page( self::SLUG, __( 'Registros', 'ygb-bot' ), __( 'Registros', 'ygb-bot' ), 'manage_options', self::SLUG . '-registros', array( __CLASS__, 'render_registros' ) );
		add_submenu_page( self::SLUG, __( 'Importar / Exportar', 'ygb-bot' ), __( 'Importar / Exportar', 'ygb-bot' ), 'manage_options', self::SLUG . '-importacion', array( __CLASS__, 'render_import_export' ) );
		add_submenu_page( self::SLUG, __( 'Ajustes generales', 'ygb-bot' ), __( 'Ajustes generales', 'ygb-bot' ), 'manage_options', self::SLUG . '-ajustes', array( __CLASS__, 'render_ajustes' ) );
		add_submenu_page( self::SLUG, __( 'Ayuda', 'ygb-bot' ), __( 'Ayuda', 'ygb-bot' ), 'manage_options', self::SLUG . '-ayuda', array( __CLASS__, 'render_ayuda' ) );
	}

	/**
	 * Carga un partial con datos.
	 *
	 * @param string $view Nombre (sin extensión).
	 * @param array  $data Variables extra accesibles como $ygb_view_data.
	 * @return void
	 */
	private static function view( $view, $data = array() ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permisos insuficientes.', 'ygb-bot' ) );
		}
		$ygb_view_data = $data; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$path          = YGB_BOT_PATH . 'admin/views/' . basename( $view ) . '.php';
		if ( ! file_exists( $path ) ) {
			wp_die( esc_html__( 'Vista no encontrada.', 'ygb-bot' ) );
		}
		include $path;
	}

	/**
	 * Dashboard.
	 *
	 * @return void
	 */
	public static function render_dashboard() {
		self::view( 'dashboard', array( 'stats' => Class_Ygb_DB::get_stats() ) );
	}

	/**
	 * Temas (lista + editor).
	 *
	 * @return void
	 */
	public static function render_temas() {
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$editing = isset( $_GET['editar'] ) ? absint( $_GET['editar'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		self::view(
			'temas',
			array(
				'search'  => $search,
				'editing' => $editing ? Class_Ygb_DB::get_tema( $editing ) : null,
			)
		);
	}

	/**
	 * Preguntas (lista + editor).
	 *
	 * @return void
	 */
	public static function render_preguntas() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Solo lectura de filtros.
		$args = array(
			'tema_id'  => isset( $_GET['tema'] ) ? absint( $_GET['tema'] ) : 0,
			'activo'   => isset( $_GET['estado'] ) && in_array( (int) $_GET['estado'], array( 0, 1 ), true ) ? (int) $_GET['estado'] : -1,
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'page'     => isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1,
			'per_page' => 20,
		);
		$editing = isset( $_GET['editar'] ) ? absint( $_GET['editar'] ) : 0;
		// phpcs:enable

		self::view(
			'preguntas',
			array(
				'temas'   => Class_Ygb_DB::get_temas(),
				'list'    => Class_Ygb_DB::get_preguntas( $args ),
				'args'    => $args,
				'editing' => $editing ? Class_Ygb_DB::get_pregunta( $editing ) : null,
			)
		);
	}

	/**
	 * Apariencia.
	 *
	 * @return void
	 */
	public static function render_apariencia() {
		self::view( 'apariencia' );
	}

	/**
	 * Derivación.
	 *
	 * @return void
	 */
	public static function render_derivacion() {
		self::view( 'derivacion' );
	}

	/**
	 * Registros.
	 *
	 * @return void
	 */
	public static function render_registros() {
		self::view( 'registros' );
	}

	/**
	 * Import/Export.
	 *
	 * @return void
	 */
	public static function render_import_export() {
		self::view( 'import-export' );
	}

	/**
	 * Ajustes generales.
	 *
	 * @return void
	 */
	public static function render_ajustes() {
		self::view( 'ajustes' );
	}

	/**
	 * Ayuda.
	 *
	 * @return void
	 */
	public static function render_ayuda() {
		self::view( 'ayuda' );
	}
}
