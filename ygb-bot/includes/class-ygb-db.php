<?php
/**
 * Capa de acceso a datos (CRUD) de YGB Bot.
 *
 * Todas las consultas usan $wpdb->prepare. Ofrece además helpers de
 * configuración (options) con valores por defecto.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_DB
 */
class Class_Ygb_DB {

	/**
	 * Clave de la opción principal de ajustes.
	 */
	const OPTION_KEY = 'ygb_bot_settings';

	/**
	 * Campos que pertenecen a cada formulario del admin.
	 *
	 * Cada pantalla (Ajustes generales, Apariencia, Derivación) comparte la
	 * misma opción (`OPTION_KEY`) pero envía solo sus propios inputs. Al
	 * guardar, `sanitize_settings()` necesita saber qué campos "pertenecen" al
	 * formulario submitido para no tratar como desactivados los checkboxes
	 * ausentes de las otras pantallas (un checkbox no marcado simplemente no
	 * viaja en el POST). Si no se hace así, un formulario pisa los datos del
	 * otro y parece que "sobrescriben" los ajustes.
	 *
	 * @var array<string,array<int,string>>
	 */
	const FORM_FIELDS = array(
		'ygb_bot_group'         => array(
			'enabled',
			'threshold',
			'autoload',
			'cache_enabled',
			'store_chats',
			'anonymize_ips',
			'require_consent',
		),
		'ygb_bot_apariencia'    => array(
			'bot_name',
			'avatar',
			'color',
			'position',
			'bubble_icon',
			'size',
			'welcome',
			'placeholder',
			'show_topics',
			'show_faq',
			'save_history',
			'lazy_load',
			'show_support_btn',
		),
		'ygb_bot_derivacion'    => array(
			'fallback_msg',
			'threshold',
			'dc_email',
			'dc_whatsapp',
			'dc_contact',
			'dc_form',
			'support_email',
			'whatsapp',
			'contact_url',
			'email_subject',
			'privacy_url',
		),
	);

	/**
	 * Slug del grupo de ajustes actualmente activo.
	 *
	 * WordPress ejecuta `sanitize_option()` sobre la opción durante la
	 * validación de `options-post.php`, justo después de correr el argumento
	 * `sanitize_callback` de `register_setting()`. Guardamos aquí el slug del
	 * grupo en `admin_init` para poder leerlo después desde el sanitizador y
	 * saber qué formulario se envió.
	 *
	 * @var string|null
	 */
	private static $current_group = null;

	/**
	 * Define el grupo de ajustes activo (llamado desde register_settings()).
	 *
	 * @param string $group Slug del grupo registrado con register_setting().
	 * @return void
	 */
	public static function set_current_group( $group ) {
		self::$current_group = (string) $group;
	}

	/**
	 * Devuelve el grupo de ajustes activo, si se conoce.
	 *
	 * @return string|null
	 */
	public static function get_current_group() {
		return self::$current_group;
	}

	/**
	 * Prefijo de tablas.
	 *
	 * @return string
	 */
	public static function prefix() {
		global $wpdb;
		return $wpdb->prefix . 'ygb_';
	}

	/**
	 * Nombres completos de las tablas del plugin.
	 *
	 * @return array<string,string>
	 */
	public static function tables() {
		$p = self::prefix();
		return array(
			'temas'          => $p . 'temas',
			'preguntas'      => $p . 'preguntas',
			'conversaciones' => $p . 'conversaciones',
			'mensajes'       => $p . 'mensajes',
			'derivaciones'   => $p . 'derivaciones',
			'fallbacks'      => $p . 'fallbacks',
		);
	}

	/* ---------------------------------------------------------------------
	 * Ajustes (Settings API)
	 * ------------------------------------------------------------------- */

	/**
	 * Ajustes por defecto del plugin.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			// General.
			'enabled'        => 1,
			'threshold'      => 60,
			'autoload'       => 1,
			'cache_enabled'  => 1,
			'store_chats'    => 1,
			'anonymize_ips'  => 1,
			'require_consent' => 1,
			'privacy_url'    => '',
			// Apariencia.
			'bot_name'       => __( 'YGB Bot', 'ygb-bot' ),
			'avatar'         => '🤖',
			'position'       => 'right',
			'color'          => '#2271b1',
			'bubble_icon'    => '💬',
			'size'           => 'medium',
			'welcome'        => __( '¡Hola! 👋 Soy el asistente virtual. Escribe tu pregunta o elige un tema.', 'ygb-bot' ),
			'placeholder'    => __( 'Escribe tu pregunta aquí…', 'ygb-bot' ),
			'show_topics'    => 1,
			'show_faq'       => 1,
			'save_history'   => 1,
			'lazy_load'      => 1,
			// Derivación / fallback.
			'fallback_msg'   => __( 'No tengo esa información. ¿Quieres que te comunique con un agente de soporte?', 'ygb-bot' ),
			'dc_email'       => 1,
			'dc_whatsapp'    => 1,
			'dc_contact'     => 0,
			'dc_form'        => 1,
			'support_email'  => get_option( 'admin_email' ),
			'whatsapp'       => '',
			'contact_url'    => '',
			'email_subject'  => __( 'Consulta desde el chat', 'ygb-bot' ),
			'show_support_btn' => 1,
		);
	}

	/**
	 * Lee los ajustes fusionados con los valores por defecto.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::default_settings(), $saved );
	}

	/**
	 * Devuelve un ajuste concreto.
	 *
	 * @param string $key     Clave del ajuste.
	 * @param mixed  $default Valor por defecto opcional.
	 * @return mixed
	 */
	public static function get_setting( $key, $default = null ) {
		$settings = self::get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Sanitiza los ajustes según su tipo.
	 *
	 * Se usa como callback de register_setting(). IMPORTANTE: las pantallas
	 * de Ajustes generales, Apariencia y Derivación comparten esta misma
	 * opción (`OPTION_KEY`) pero cada `<form>` solo envía sus propios campos.
	 * Como los checkboxes no marcados no viajan en el POST, antes se forzaban
	 * a 0 todos los booleanos "ausentes", lo que hacía que guardar un
	 * formulario pisara / reseteara los datos del otro. Ahora solo se
	 * procesan los campos pertenecientes al grupo submitido (ver
	 * FORM_FIELDS); los demás se conservan con su valor actual.
	 *
	 * @param mixed $input Valores crudos del formulario.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$out   = self::get_settings();
		$input = is_array( $input ) ? $input : array();

		// Campos del formulario que se acaba de enviar. Si no podemos
		// determinarlo, asumimos todos para no perder compatibilidad.
		$fields = self::fields_for_current_submission();

		$is_present = static function ( $key ) use ( $input, $fields ) {
			return in_array( $key, $fields, true ) && array_key_exists( $key, $input );
		};
		$in_form = static function ( $key ) use ( $fields ) {
			return in_array( $key, $fields, true );
		};

		$bools     = array( 'enabled', 'autoload', 'cache_enabled', 'store_chats', 'anonymize_ips', 'require_consent', 'show_topics', 'show_faq', 'save_history', 'lazy_load', 'dc_email', 'dc_whatsapp', 'dc_contact', 'dc_form', 'show_support_btn' );
		$texts     = array( 'bot_name', 'bubble_icon', 'size', 'position', 'placeholder', 'support_email', 'whatsapp', 'contact_url', 'privacy_url', 'email_subject' );
		$textareas = array( 'welcome', 'fallback_msg' );

		foreach ( $bools as $key ) {
			// Solo tocar los booleanos cuyo checkbox pertenece al form
			// enviado; ausente = desmarcado => 0. Los de otras pantallas
			// se dejan intactos.
			if ( $in_form( $key ) ) {
				$out[ $key ] = $is_present( $key ) ? 1 : 0;
			}
		}
		foreach ( $texts as $key ) {
			if ( $is_present( $key ) ) {
				$out[ $key ] = sanitize_text_field( wp_unslash( $input[ $key ] ) );
			}
		}
		foreach ( $textareas as $key ) {
			if ( $is_present( $key ) ) {
				$out[ $key ] = sanitize_textarea_field( wp_unslash( $input[ $key ] ) );
			}
		}

		$out['threshold'] = $is_present( 'threshold' ) ? max( 1, min( 100, absint( $input['threshold'] ) ) ) : $out['threshold'];
		$out['color']     = $is_present( 'color' ) ? self::sanitize_hex( $input['color'], $out['color'] ) : $out['color'];
		$out['avatar']    = $is_present( 'avatar' ) ? mb_substr( sanitize_text_field( wp_unslash( $input['avatar'] ) ), 0, 32 ) : $out['avatar'];

		if ( ! in_array( $out['position'], array( 'right', 'left' ), true ) ) {
			$out['position'] = 'right';
		}
		if ( ! in_array( $out['size'], array( 'small', 'medium', 'large' ), true ) ) {
			$out['size'] = 'medium';
		}
		$out['support_email'] = sanitize_email( $out['support_email'] );
		$out['contact_url']   = esc_url_raw( $out['contact_url'] );
		$out['privacy_url']   = esc_url_raw( $out['privacy_url'] );

		/**
		 * Permite modificar los ajustes sanitizados.
		 *
		 * @param array $out Ajustes finales.
		 */
		return apply_filters( 'ygb_sanitize_settings', $out );
	}

	/**
	 * Devuelve la lista de campos que pertenecen al envío actual.
	 *
	 * Estrategia (en orden de prioridad):
	 *  1. Si `option_page` llega en el POST y coincide con una clave de
	 *     FORM_FIELDS, devolvemos sus campos.
	 *  2. Si no, usamos el grupo registrado durante `admin_init`
	 *     (`self::$current_group`).
	 *  3. Como último recurso, asumimos todos los campos conocidos
	 *     (comportamiento antiguo) para no romper integraciones externas.
	 *
	 * @return array<int,string>
	 */
	public static function fields_for_current_submission() {
		$page = isset( $_POST['option_page'] ) ? sanitize_key( wp_unslash( $_POST['option_page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' !== $page && isset( self::FORM_FIELDS[ $page ] ) ) {
			return self::FORM_FIELDS[ $page ];
		}
		if ( null !== self::$current_group && isset( self::FORM_FIELDS[ self::$current_group ] ) ) {
			return self::FORM_FIELDS[ self::$current_group ];
		}
		$all = array();
		foreach ( self::FORM_FIELDS as $group_fields ) {
			$all = array_merge( $all, $group_fields );
		}
		return array_unique( $all );
	}

	/**
	 * Sanitiza un color hexadecimal.
	 *
	 * @param mixed  $value   Valor crudo.
	 * @param string $fallback Valor por defecto.
	 * @return string
	 */
	public static function sanitize_hex( $value, $fallback = '#2271b1' ) {
		$value = sanitize_text_field( wp_unslash( (string) $value ) );
		return preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ? strtolower( $value ) : $fallback;
	}

	/* ---------------------------------------------------------------------
	 * TEMAS
	 * ------------------------------------------------------------------- */

	/**
	 * Lista temas.
	 *
	 * @param bool|null $activo  Filtrar por estado (null = todos).
	 * @param string    $search  Búsqueda por nombre.
	 * @return array<object>
	 */
	public static function get_temas( $activo = null, $search = '' ) {
		global $wpdb;
		$table = self::tables()['temas'];
		$where = array( '1=1' );
		$prep  = array();

		if ( null !== $activo ) {
			$where[] = 'activo = %d';
			$prep[]  = absint( $activo );
		}
		if ( '' !== $search ) {
			$where[] = 'nombre LIKE %s';
			$prep[]  = '%' . $wpdb->esc_like( $search ) . '%';
		}
		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY orden ASC, id ASC';
		if ( $prep ) {
			$sql = $wpdb->prepare( $sql, $prep ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Devuelve un tema por ID.
	 *
	 * @param int $id ID del tema.
	 * @return object|null
	 */
	public static function get_tema( $id ) {
		global $wpdb;
		$table = self::tables()['temas'];
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? $row : null;
	}

	/**
	 * Inserta o actualiza un tema (espera datos ya sanitizados).
	 *
	 * @param array $data Datos del tema.
	 * @return int|false  ID insertado o false.
	 */
	public static function save_tema( $data ) {
		global $wpdb;
		$table  = self::tables()['temas'];
		$now    = current_time( 'mysql' );
		$fields = array(
			'nombre'      => $data['nombre'],
			'descripcion' => $data['descripcion'],
			'icono'       => $data['icono'],
			'orden'       => absint( $data['orden'] ),
			'activo'      => empty( $data['activo'] ) ? 0 : 1,
		);

		Class_Ygb_Search_Engine::invalidate_cache();

		if ( ! empty( $data['id'] ) ) {
			$id = absint( $data['id'] );
			unset( $fields['creado'] );
			$ok = $wpdb->update( $table, $fields, array( 'id' => $id ), array( '%s', '%s', '%s', '%d', '%d' ), array( '%d' ) );
			return false === $ok ? false : $id;
		}

		$fields['creado']    = $now;
		$fields['modificado'] = $now;
		$ok = $wpdb->insert( $table, $fields, array( '%s', '%s', '%s', '%d', '%d', '%s', '%s' ) );
		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Elimina un tema y desasocia sus preguntas.
	 *
	 * @param int $id ID del tema.
	 * @return bool
	 */
	public static function delete_tema( $id ) {
		global $wpdb;
		$id    = absint( $id );
		$tabla = self::tables()['temas'];
		$preg  = self::tables()['preguntas'];
		$wpdb->query( $wpdb->prepare( "UPDATE {$preg} SET tema_id = 0 WHERE tema_id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		Class_Ygb_Search_Engine::invalidate_cache();
		return false !== $wpdb->delete( $tabla, array( 'id' => $id ), array( '%d' ) );
	}

	/* ---------------------------------------------------------------------
	 * PREGUNTAS
	 * ------------------------------------------------------------------- */

	/**
	 * Lista preguntas con filtros, búsqueda y paginación.
	 *
	 * @param array $args {
	 *     Opciones.
	 *     @type int    $tema_id  Filtrar por tema.
	 *     @type int    $activo   0/1 o -1 para todos.
	 *     @type string $search   Búsqueda en pregunta/keywords.
	 *     @type int    $page     Página (1-based).
	 *     @type int    $per_page Por página.
	 * }
	 * @return array{items:array,total:int}
	 */
	public static function get_preguntas( $args = array() ) {
		global $wpdb;
		$args  = wp_parse_args(
			$args,
			array(
				'tema_id'  => 0,
				'activo'   => -1,
				'search'   => '',
				'page'     => 1,
				'per_page' => 20,
			)
		);
		$table = self::tables()['preguntas'];
		$where = array( '1=1' );
		$prep  = array();

		if ( $args['tema_id'] > 0 ) {
			$where[] = 'tema_id = %d';
			$prep[]  = absint( $args['tema_id'] );
		}
		if ( in_array( (int) $args['activo'], array( 0, 1 ), true ) ) {
			$where[] = 'activo = %d';
			$prep[]  = (int) $args['activo'];
		}
		if ( '' !== $args['search'] ) {
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = '(pregunta LIKE %s OR keywords LIKE %s)';
			$prep[]  = $like;
			$prep[]  = $like;
		}

		$per_page = max( 1, absint( $args['per_page'] ) );
		$offset   = ( max( 1, absint( $args['page'] ) ) - 1 ) * $per_page;

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$list_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY prioridad DESC, id DESC LIMIT %d OFFSET %d";

		$total = (int) $wpdb->get_var( $prep ? $wpdb->prepare( $count_sql, $prep ) : $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $list_sql, array_merge( $prep, array( $per_page, $offset ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Devuelve una pregunta por ID.
	 *
	 * @param int $id ID.
	 * @return object|null
	 */
	public static function get_pregunta( $id ) {
		global $wpdb;
		$table = self::tables()['preguntas'];
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ? $row : null;
	}

	/**
	 * Inserta o actualiza una pregunta (espera datos ya sanitizados).
	 *
	 * @param array $data Datos.
	 * @return int|false
	 */
	public static function save_pregunta( $data ) {
		global $wpdb;
		$table = self::tables()['preguntas'];
		$now   = current_time( 'mysql' );

		$fields = array(
			'tema_id'   => absint( $data['tema_id'] ),
			'pregunta'  => $data['pregunta'],
			'variaciones' => $data['variaciones'],
			'keywords'  => $data['keywords'],
			'respuesta' => $data['respuesta'],
			'enlace'    => $data['enlace'],
			'imagen'    => $data['imagen'],
			'archivo'   => $data['archivo'],
			'prioridad' => absint( $data['prioridad'] ),
			'activo'    => empty( $data['activo'] ) ? 0 : 1,
		);

		Class_Ygb_Search_Engine::invalidate_cache();

		if ( ! empty( $data['id'] ) ) {
			$id = absint( $data['id'] );
			$ok = $wpdb->update(
				$table,
				array_merge( $fields, array( 'modificado' => $now ) ),
				array( 'id' => $id ),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' ),
				array( '%d' )
			);
			return false === $ok ? false : $id;
		}

		$fields['creado']     = $now;
		$fields['modificado'] = $now;
		$fields['contador']   = 0;
		$ok = $wpdb->insert( $table, $fields, array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d' ) );
		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Elimina una pregunta.
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	public static function delete_pregunta( $id ) {
		Class_Ygb_Search_Engine::invalidate_cache();
		global $wpdb;
		return false !== $wpdb->delete( self::tables()['preguntas'], array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/**
	 * Acciones en masa sobre preguntas.
	 *
	 * @param array  $ids   IDs.
	 * @param string $action activar|desactivar|eliminar|mover.
	 * @param int    $tema_id Destino para "mover".
	 * @return int Número de filas afectadas.
	 */
	public static function bulk_preguntas( $ids, $action, $tema_id = 0 ) {
		global $wpdb;
		$ids = array_filter( array_map( 'absint', (array) $ids ) );
		if ( ! $ids ) {
			return 0;
		}
		$table = self::tables()['preguntas'];
		$in    = implode( ',', $ids );
		Class_Ygb_Search_Engine::invalidate_cache();

		switch ( $action ) {
			case 'activar':
				$wpdb->query( "UPDATE {$table} SET activo = 1 WHERE id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL
				break;
			case 'desactivar':
				$wpdb->query( "UPDATE {$table} SET activo = 0 WHERE id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL
				break;
			case 'eliminar':
				$wpdb->query( "DELETE FROM {$table} WHERE id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL
				break;
			case 'mover':
				$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET tema_id = %d WHERE id IN ({$in})", absint( $tema_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				break;
		}
		return count( $ids );
	}

	/**
	 * Incrementa el contador de consultas de una pregunta.
	 *
	 * @param int $id ID.
	 * @return void
	 */
	public static function bump_contador( $id ) {
		global $wpdb;
		$table = self::tables()['preguntas'];
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET contador = contador + 1 WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/* ---------------------------------------------------------------------
	 * CONVERSACIONES / MENSAJES / DERIVACIONES / FALLBACKS
	 * ------------------------------------------------------------------- */

	/**
	 * Crea una conversación y devuelve su ID.
	 *
	 * @param string $session_id Identificador anónimo de sesión.
	 * @param string $ip         IP cruda (se hashea si anonymize_ips).
	 * @param string $user_agent User agent.
	 * @return int|false
	 */
	public static function create_conversacion( $session_id, $ip, $user_agent ) {
		global $wpdb;
		if ( ! self::get_setting( 'store_chats' ) ) {
			return false;
		}
		$table = self::tables()['conversaciones'];
		$ip_stored = self::get_setting( 'anonymize_ips' ) ? self::hash_ip( $ip ) : substr( (string) $ip, 0, 45 );
		$ok = $wpdb->insert(
			$table,
			array(
				'session_id' => substr( sanitize_text_field( $session_id ), 0, 64 ),
				'user_ip'    => $ip_stored,
				'user_agent' => substr( sanitize_text_field( $user_agent ), 0, 255 ),
				'iniciado'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);
		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Hashea una IP conforme a RGPD (salt rotatorio diario).
	 *
	 * @param string $ip IP.
	 * @return string
	 */
	public static function hash_ip( $ip ) {
		// Se usa el alias 'sha256': el nombre con guion ('sha-256') no está
		// disponible en todos los builds de PHP y provoca un ValueError fatal
		// que rompía el endpoint AJAX `ygb_start` (primer mensaje del bot).
		return 'anon:' . substr( hash( 'sha256', $ip . wp_salt( 'auth' ) . gmdate( 'Ymd' ) ), 0, 40 );
	}

	/**
	 * Registra un mensaje de una conversación.
	 *
	 * @param int    $conv_id  ID de conversación (0 permite conversaciones inexistentes).
	 * @param string $emisor   user|bot.
	 * @param string $mensaje  Texto.
	 * @param int    $pregunta_id ID de pregunta asociada.
	 * @param float  $score    Score de coincidencia.
	 * @return int|false
	 */
	public static function add_mensaje( $conv_id, $emisor, $mensaje, $pregunta_id = null, $score = 0 ) {
		global $wpdb;
		if ( ! self::get_setting( 'store_chats' ) ) {
			return false;
		}
		$table = self::tables()['mensajes'];
		$ok    = $wpdb->insert(
			$table,
			array(
				'conversacion_id' => absint( $conv_id ),
				'emisor'          => 'bot' === $emisor ? 'bot' : 'user',
				'mensaje'         => sanitize_textarea_field( $mensaje ),
				'pregunta_id'     => $pregunta_id ? absint( $pregunta_id ) : null,
				'score'           => round( (float) $score, 2 ),
				'fecha'           => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%f', '%s' )
		);
		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Cierra una conversación.
	 *
	 * @param int $conv_id ID.
	 * @return void
	 */
	public static function close_conversacion( $conv_id ) {
		global $wpdb;
		if ( ! $conv_id ) {
			return;
		}
		$table = self::tables()['conversaciones'];
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET finalizado = %s WHERE id = %d", current_time( 'mysql' ), absint( $conv_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Registra una derivación a soporte.
	 *
	 * @param int    $conv_id ID conversación.
	 * @param string $canal   email|whatsapp|contacto|formulario.
	 * @param string $mensaje Último mensaje del usuario.
	 * @param string $estado  nuevo|atendido|cerrado.
	 * @return int|false
	 */
	public static function add_derivacion( $conv_id, $canal, $mensaje, $estado = 'nuevo' ) {
		global $wpdb;
		$allowed = array( 'email', 'whatsapp', 'contacto', 'formulario' );
		$canal   = in_array( $canal, $allowed, true ) ? $canal : 'email';
		$table   = self::tables()['derivaciones'];
		$ok      = $wpdb->insert(
			$table,
			array(
				'conversacion_id' => absint( $conv_id ),
				'canal'           => $canal,
				'mensaje_usuario' => sanitize_textarea_field( $mensaje ),
				'estado'          => sanitize_key( $estado ),
				'fecha'           => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);
		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Registra un fallback (pregunta sin respuesta).
	 *
	 * @param string $mensaje Mensaje del usuario.
	 * @return int|false
	 */
	public static function add_fallback( $mensaje ) {
		global $wpdb;
		$table = self::tables()['fallbacks'];
		$ok    = $wpdb->insert(
			$table,
			array(
				'mensaje_usuario' => sanitize_textarea_field( $mensaje ),
				'fecha'           => current_time( 'mysql' ),
			),
			array( '%s', '%s' )
		);
		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Paginado genérico de registros para la vista de logs.
	 *
	 * @param string $tipo   conversaciones|derivaciones|fallbacks.
	 * @param int    $page   Página.
	 * @param int    $per    Por página.
	 * @return array{items:array,total:int}
	 */
	public static function get_registros( $tipo, $page = 1, $per = 25 ) {
		global $wpdb;
		$per    = max( 1, absint( $per ) );
		$offset = ( max( 1, absint( $page ) ) - 1 ) * $per;
		$t      = self::tables();

		if ( 'derivaciones' === $tipo ) {
			$table = $t['derivaciones'];
		} elseif ( 'fallbacks' === $tipo ) {
			$table = $t['fallbacks'];
		} else {
			$table = $t['conversaciones'];
		}

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$items = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT {$per} OFFSET {$offset}" ); // phpcs:ignore WordPress.DB.PreparedSQL
		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Mensajes de una conversación.
	 *
	 * @param int $conv_id ID.
	 * @return array<object>
	 */
	public static function get_mensajes( $conv_id ) {
		global $wpdb;
		$table = self::tables()['mensajes'];
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE conversacion_id = %d ORDER BY id ASC", absint( $conv_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Estadísticas para el dashboard.
	 *
	 * @return array
	 */
	public static function get_stats() {
		global $wpdb;
		$t = self::tables();

		$stats = array(
			'conversaciones'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['conversaciones']}" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'conversaciones_hoy' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['conversaciones']} WHERE iniciado >= %s", gmdate( 'Y-m-d 00:00:00', strtotime( current_time( 'timestamp' ) ) ) ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'mensajes'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['mensajes']}" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'derivaciones'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['derivaciones']}" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'fallbacks'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['fallbacks']}" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'temas'            => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['temas']}" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'preguntas'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['preguntas']}" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'top_preguntas'    => $wpdb->get_results( "SELECT p.id, p.pregunta, p.contador FROM {$t['preguntas']} p ORDER BY p.contador DESC LIMIT 8" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'top_temas'        => $wpdb->get_results( "SELECT t.id, t.nombre, SUM(p.contador) AS consultas FROM {$t['temas']} t LEFT JOIN {$t['preguntas']} p ON p.tema_id = t.id GROUP BY t.id, t.nombre ORDER BY consultas DESC LIMIT 8" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'top_fallbacks'    => $wpdb->get_results( "SELECT mensaje_usuario, COUNT(*) AS veces FROM {$t['fallbacks']} GROUP BY mensaje_usuario ORDER BY veces DESC LIMIT 8" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'por_canal'        => $wpdb->get_results( "SELECT canal, COUNT(*) AS total FROM {$t['derivaciones']} GROUP BY canal" ), // phpcs:ignore WordPress.DB.PreparedSQL
		);
		return $stats;
	}

	/**
	 * Exporta temas + preguntas como array (para JSON/CSV).
	 *
	 * @return array
	 */
	public static function export_data() {
		return array(
			'version' => YGB_BOT_VERSION,
			'fecha'   => current_time( 'mysql' ),
			'temas'   => self::get_temas(),
			'preguntas' => self::get_preguntas( array( 'per_page' => 100000, 'activo' => -1 ) )['items'],
		);
	}
}
