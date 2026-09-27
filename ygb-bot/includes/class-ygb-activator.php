<?php
/**
 * Activador del plugin: crea tablas (dbDelta) y datos de ejemplo.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_Activator
 */
class Class_Ygb_Activator {

	/**
	 * Rutas relativas de archivos a copiar al subir plugins en multisite.
	 *
	 * @var array
	 */
	public static $sitemu = array( 'ygb-bot.php', 'includes/', 'admin/', 'public/', 'languages/', 'assets/' );

	/**
	 * Hook de activación.
	 *
	 * @param bool $network_wide Activación de red.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
			foreach ( $ids as $id ) {
				switch_to_blog( $id );
				self::single_activate();
				restore_current_blog();
			}
			return;
		}
		self::single_activate();
	}

	/**
	 * Activación para un sitio concreto.
	 *
	 * @return void
	 */
	public static function single_activate() {
		self::create_tables();

		// Onboarding: crear tema y pregunta de ejemplo si no hay datos.
		global $wpdb;
		$temas = self::table( 'temas' );
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$temas}" ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( 0 === $count ) {
			self::seed_example();
		}

		// Ajustes por defecto (sin sobrescribir).
		if ( false === get_option( Class_Ygb_DB::OPTION_KEY ) ) {
			add_option( Class_Ygb_DB::OPTION_KEY, Class_Ygb_DB::default_settings() );
		}

		update_option( 'ygb_bot_db_version', YGB_BOT_DB_VERSION );
		Class_Ygb_Search_Engine::invalidate_cache();

		if ( ! get_option( 'ygb_bot_activated_notice' ) ) {
			update_option( 'ygb_bot_activated_notice', 1 );
		}
	}

	/**
	 * Nombre completo de una tabla.
	 *
	 * @param string $key Clave lógica.
	 * @return string
	 */
	private static function table( $key ) {
		$t = Class_Ygb_DB::tables();
		return isset( $t[ $key ] ) ? $t[ $key ] : '';
	}

	/**
	 * Crea/actualiza las tablas con dbDelta.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$prefix  = Class_Ygb_DB::prefix();

		$sql = array();

		$sql[] = "CREATE TABLE {$prefix}temas (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			nombre VARCHAR(191) NOT NULL,
			descripcion TEXT NOT NULL,
			icono VARCHAR(40) NOT NULL DEFAULT '',
			orden INT(11) NOT NULL DEFAULT 0,
			activo TINYINT(1) NOT NULL DEFAULT 1,
			creado DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			modificado DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY activo (activo),
			KEY orden (orden)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}preguntas (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			tema_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			pregunta TEXT NOT NULL,
			variaciones TEXT NOT NULL,
			keywords TEXT NOT NULL,
			respuesta LONGTEXT NOT NULL,
			enlace VARCHAR(500) NOT NULL DEFAULT '',
			imagen VARCHAR(500) NOT NULL DEFAULT '',
			archivo VARCHAR(500) NOT NULL DEFAULT '',
			prioridad INT(11) NOT NULL DEFAULT 0,
			activo TINYINT(1) NOT NULL DEFAULT 1,
			contador BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			creado DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			modificado DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY tema_id (tema_id),
			KEY activo (activo),
			KEY prioridad (prioridad),
			FULLTEXT KEY pregunta (pregunta, variaciones, keywords)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}conversaciones (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(64) NOT NULL DEFAULT '',
			user_ip VARCHAR(64) NOT NULL DEFAULT '',
			user_agent VARCHAR(255) NOT NULL DEFAULT '',
			iniciado DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			finalizado DATETIME DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY session_id (session_id),
			KEY iniciado (iniciado)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}mensajes (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			conversacion_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			emisor VARCHAR(10) NOT NULL DEFAULT 'user',
			mensaje TEXT NOT NULL,
			pregunta_id BIGINT(20) UNSIGNED DEFAULT NULL,
			score FLOAT NOT NULL DEFAULT 0,
			fecha DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY conversacion_id (conversacion_id),
			KEY pregunta_id (pregunta_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}derivaciones (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			conversacion_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			canal VARCHAR(20) NOT NULL DEFAULT 'email',
			mensaje_usuario TEXT NOT NULL,
			estado VARCHAR(20) NOT NULL DEFAULT 'nuevo',
			fecha DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY canal (canal),
			KEY estado (estado)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefix}fallbacks (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			mensaje_usuario TEXT NOT NULL,
			fecha DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY fecha (fecha)
		) {$charset};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	/**
	 * Crea contenido de ejemplo (onboarding).
	 *
	 * @return void
	 */
	private static function seed_example() {
		global $wpdb;
		$now   = current_time( 'mysql' );
		$temas = self::table( 'temas' );
		$pregs = self::table( 'preguntas' );

		$wpdb->insert(
			$temas,
			array(
				'nombre'       => __( 'Soporte', 'ygb-bot' ),
				'descripcion'  => __( 'Preguntas frecuentes de ejemplo. Edítalas o elimínalas.', 'ygb-bot' ),
				'icono'        => '🛟',
				'orden'        => 0,
				'activo'       => 1,
				'creado'       => $now,
				'modificado'   => $now,
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);
		$tema_id = (int) $wpdb->insert_id;

		$ejemplos = array(
			array(
				'¿Cuál es vuestro horario de atención?',
				"horario\nhorario de atencion\nque horas abren\ncuando atienden",
				'horario,atencion,abrir,cita',
				'<p>Nuestro horario de atención es <strong>lunes a viernes de 9:00 a 18:00</strong>. Los fines de semana el bot sigue disponible.</p>',
			),
			array(
				'¿Cómo contacto con soporte?',
				"soporte\ncontacto\nhablar con alguien\nagente",
				'soporte,contacto,ayuda,humano',
				'<p>Puedes escribirnos pulsando el botón <em>“Hablar con soporte”</em> y elegirás entre correo electrónico o WhatsApp.</p>',
			),
		);

		foreach ( $ejemplos as $e ) {
			$wpdb->insert(
				$pregs,
				array(
					'tema_id'     => $tema_id,
					'pregunta'    => $e[0],
					'variaciones' => $e[1],
					'keywords'    => $e[2],
					'respuesta'   => $e[3],
					'enlace'      => '',
					'imagen'      => '',
					'archivo'     => '',
					'prioridad'   => 5,
					'activo'      => 1,
					'contador'    => 0,
					'creado'      => $now,
					'modificado'  => $now,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
			);
		}
	}
}
