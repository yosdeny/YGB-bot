<?php
/**
 * Panel de administración: assets, settings, acciones CRUD.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

require_once YGB_BOT_PATH . 'admin/class-ygb-admin-menu.php';

/**
 * Class Class_Ygb_Admin
 */
class Class_Ygb_Admin {

	/**
	 * Sufijo .min si no es SCRIPT_DEBUG.
	 *
	 * @return string
	 */
	private static function suffix() {
		return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	}

	/**
	 * Registra hooks de administración.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( 'Class_Ygb_Admin_Menu', 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'wp_ajax_ygb_test_chat', array( __CLASS__, 'ajax_test_chat' ) );
	}

	/**
	 * Encola CSS/JS solo en pantallas del plugin.
	 *
	 * @param string $hook Página actual.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'ygb-bot' ) ) {
			return;
		}
		$suffix = self::suffix();

		$css = file_exists( YGB_BOT_PATH . 'admin/css/admin' . $suffix . '.css' ) ? 'admin/css/admin' . $suffix . '.css' : 'admin/css/admin.css';
		wp_enqueue_style(
			'ygb-admin',
			YGB_BOT_URL . $css,
			array(),
			YGB_BOT_VERSION
		);
		$js  = file_exists( YGB_BOT_PATH . 'admin/js/admin' . $suffix . '.js' ) ? 'admin/js/admin' . $suffix . '.js' : 'admin/js/admin.js';
		$dep = false !== strpos( $hook, 'apariencia' ) || false !== strpos( $hook, 'dashboard' ) ? array( 'wp-media' ) : array();
		wp_enqueue_script(
			'ygb-admin',
			YGB_BOT_URL . $js,
			$dep,
			YGB_BOT_VERSION,
			true
		);
		wp_localize_script(
			'ygb-admin',
			'ygbAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ygb_admin' ),
				'i18n'    => array(
					'saved'     => __( '✅ Guardado correctamente.', 'ygb-bot' ),
					'deleted'   => __( '🗑️ Elemento eliminado.', 'ygb-bot' ),
					'confirm'   => __( '¿Seguro? Esta acción no se puede deshacer.', 'ygb-bot' ),
					'testing'   => __( 'escribiendo…', 'ygb-bot' ),
					'error'     => __( 'Error de conexión.', 'ygb-bot' ),
				),
			)
		);

		// Media library solo donde se usa (selector de imagen/adjuntos en Preguntas).
		if ( false !== strpos( $hook, 'preguntas' ) ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Registra los ajustes con la Settings API.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'ygb_bot_group',
			Class_Ygb_DB::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'Class_Ygb_DB', 'sanitize_settings' ),
				'default'           => Class_Ygb_DB::default_settings(),
			)
		);
	}

	/**
	 * Muestra avisos (guardado, activación).
	 *
	 * @return void
	 */
	public static function notices() {
		if ( get_option( 'ygb_bot_activated_notice' ) && current_user_can( 'manage_options' ) ) {
			delete_option( 'ygb_bot_activated_notice' );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( 'YGB Bot se ha activado. Se han creado un tema y preguntas de ejemplo.', 'ygb-bot' ),
				esc_url( admin_url( 'admin.php?page=ygb-bot' ) ),
				esc_html__( 'Ir al panel →', 'ygb-bot' )
			);
		}

		$msg = isset( $_GET['ygb_msg'] ) ? sanitize_key( wp_unslash( $_GET['ygb_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $msg ) {
			$texts = array(
				'saved'   => __( 'Ajustes guardados.', 'ygb-bot' ),
				'tema_ok' => __( 'Tema guardado.', 'ygb-bot' ),
				'tema_no' => __( 'No se pudo guardar el tema.', 'ygb-bot' ),
				'tema_del'=> __( 'Tema eliminado.', 'ygb-bot' ),
				'preg_ok' => __( 'Pregunta guardada.', 'ygb-bot' ),
				'preg_no' => __( 'No se pudo guardar la pregunta.', 'ygb-bot' ),
				'preg_del'=> __( 'Pregunta eliminada.', 'ygb-bot' ),
				'bulk_ok' => __( 'Acción en masa aplicada.', 'ygb-bot' ),
				'import_ok' => __( 'Importación completada.', 'ygb-bot' ),
				'import_no' => __( 'Importación fallida: revisa el formato del archivo.', 'ygb-bot' ),
			);
			if ( isset( $texts[ $msg ] ) ) {
				$type = in_array( $msg, array( 'tema_no', 'preg_no', 'import_no' ), true ) ? 'error' : 'success';
				printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $type ), esc_html( $texts[ $msg ] ) );
			}
		}
	}

	/**
	 * Maneja acciones POST del admin (CRUD temas/preguntas, import/export, bulk).
	 *
	 * @return void
	 */
	public static function handle_actions() {
		if ( empty( $_REQUEST['ygb_action'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'ygb-bot' ) );
		}

		$action = sanitize_key( wp_unslash( $_REQUEST['ygb_action'] ) );
		check_admin_referer( 'ygb_manage' );

		switch ( $action ) {
			case 'tema_save':
				$data = array(
					'id'          => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
					'nombre'      => isset( $_POST['nombre'] ) ? sanitize_text_field( wp_unslash( $_POST['nombre'] ) ) : '',
					'descripcion' => isset( $_POST['descripcion'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descripcion'] ) ) : '',
					'icono'       => isset( $_POST['icono'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['icono'] ) ), 0, 20 ) : '',
					'orden'       => isset( $_POST['orden'] ) ? absint( $_POST['orden'] ) : 0,
					'activo'      => ! empty( $_POST['activo'] ) ? 1 : 0,
				);
				$res  = $data['nombre'] ? Class_Ygb_DB::save_tema( $data ) : false;
				self::redirect( admin_url( 'admin.php?page=ygb-bot-temas&ygb_msg=' . ( $res ? 'tema_ok' : 'tema_no' ) ) );
				break;

			case 'tema_delete':
				$id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
				$ok  = $id ? Class_Ygb_DB::delete_tema( $id ) : false;
				self::redirect( admin_url( 'admin.php?page=ygb-bot-temas&ygb_msg=' . ( $ok ? 'tema_del' : 'tema_no' ) ) );
				break;

			case 'pregunta_save':
				$data = array(
					'id'          => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
					'tema_id'     => isset( $_POST['tema_id'] ) ? absint( $_POST['tema_id'] ) : 0,
					'pregunta'    => isset( $_POST['pregunta'] ) ? sanitize_text_field( wp_unslash( $_POST['pregunta'] ) ) : '',
					'variaciones' => isset( $_POST['variaciones'] ) ? sanitize_textarea_field( wp_unslash( $_POST['variaciones'] ) ) : '',
					'keywords'    => isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) ) : '',
					'respuesta'   => isset( $_POST['respuesta'] ) ? wp_kses_post( wp_unslash( $_POST['respuesta'] ) ) : '',
					'enlace'      => isset( $_POST['enlace'] ) ? esc_url_raw( wp_unslash( $_POST['enlace'] ) ) : '',
					'imagen'      => isset( $_POST['imagen'] ) ? esc_url_raw( wp_unslash( $_POST['imagen'] ) ) : '',
					'archivo'     => isset( $_POST['archivo'] ) ? esc_url_raw( wp_unslash( $_POST['archivo'] ) ) : '',
					'prioridad'   => isset( $_POST['prioridad'] ) ? absint( $_POST['prioridad'] ) : 0,
					'activo'      => ! empty( $_POST['activo'] ) ? 1 : 0,
				);
				$res  = ( $data['pregunta'] && $data['tema_id'] ) ? Class_Ygb_DB::save_pregunta( $data ) : false;
				self::redirect( admin_url( 'admin.php?page=ygb-bot-preguntas&ygb_msg=' . ( $res ? 'preg_ok' : 'preg_no' ) ) );
				break;

			case 'pregunta_delete':
				$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
				$ok = $id ? Class_Ygb_DB::delete_pregunta( $id ) : false;
				self::redirect( admin_url( 'admin.php?page=ygb-bot-preguntas&ygb_msg=' . ( $ok ? 'preg_del' : 'preg_no' ) ) );
				break;

			case 'bulk':
				$ids    = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
				$op     = isset( $_POST['bulk_op'] ) ? sanitize_key( wp_unslash( $_POST['bulk_op'] ) ) : '';
				$tema   = isset( $_POST['bulk_tema'] ) ? absint( $_POST['bulk_tema'] ) : 0;
				$allowed = array( 'activar', 'desactivar', 'eliminar', 'mover' );
				if ( in_array( $op, $allowed, true ) && $ids ) {
					Class_Ygb_DB::bulk_preguntas( $ids, $op, $tema );
					self::redirect( admin_url( 'admin.php?page=ygb-bot-preguntas&ygb_msg=bulk_ok' ) );
				}
				self::redirect( admin_url( 'admin.php?page=ygb-bot-preguntas' ) );
				break;

			case 'export':
				self::export( isset( $_POST['formato'] ) ? sanitize_key( wp_unslash( $_POST['formato'] ) ) : 'json' );
				break;

			case 'import':
				self::import();
				break;

			case 'clear_logs':
				self::clear_logs();
				break;

			case 'export_logs':
				self::export_logs( isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : 'conversaciones' );
				break;
		}
	}

	/**
	 * Redirige y muere.
	 *
	 * @param string $url URL destino.
	 * @return void
	 */
	private static function redirect( $url ) {
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Exporta a JSON o CSV.
	 *
	 * @param string $formato json|csv.
	 * @return void
	 */
	private static function export( $formato ) {
		$data = Class_Ygb_DB::export_data();
		$name = 'ygb-bot-export-' . gmdate( 'Ymd-His' );

		if ( 'csv' === $formato ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=' . $name . '.csv' );
			$out = fopen( 'php://output', 'w' );
			fputcsv( $out, array( 'tipo', 'id', 'tema_id', 'nombre_o_pregunta', 'descripcion_o_respuesta', 'variaciones', 'keywords', 'icono_o_enlace', 'prioridad_o_orden', 'activo' ) );
			foreach ( $data['temas'] as $t ) {
				fputcsv( $out, array( 'tema', $t->id, '', $t->nombre, $t->descripcion, '', '', $t->icono, $t->orden, $t->activo ) );
			}
			foreach ( $data['preguntas'] as $p ) {
				fputcsv( $out, array( 'pregunta', $p->id, $p->tema_id, $p->pregunta, $p->respuesta, $p->variaciones, $p->keywords, $p->enlace, $p->prioridad, $p->activo ) );
			}
			fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			exit;
		}

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $name . '.json' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Importa desde JSON subido.
	 *
	 * @return void
	 */
	private static function import() {
		if ( empty( $_FILES['ygb_import']['tmp_name'] ) ) {
			self::redirect( admin_url( 'admin.php?page=ygb-bot-importacion&ygb_msg=import_no' ) );
		}

		$file = $_FILES['ygb_import']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Se valida a continuación.
		$type = wp_check_filetype( $file['name'], array( 'json' => 'application/json' ) );
		if ( 'json' !== $type['ext'] ) {
			self::redirect( admin_url( 'admin.php?page=ygb-bot-importacion&ygb_msg=import_no' ) );
		}

		$raw  = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.NoSilencedErrors.Discouraged
		$data = json_decode( (string) $raw, true );
		if ( ! is_array( $data ) || ( empty( $data['temas'] ) && empty( $data['preguntas'] ) ) ) {
			self::redirect( admin_url( 'admin.php?page=ygb-bot-importacion&ygb_msg=import_no' ) );
		}

		$id_map = array();

		foreach ( (array) ( isset( $data['temas'] ) ? $data['temas'] : array() ) as $t ) {
			$old = isset( $t['id'] ) ? (int) $t['id'] : 0;
			$new = Class_Ygb_DB::save_tema(
				array(
					'id'          => 0,
					'nombre'      => sanitize_text_field( isset( $t['nombre'] ) ? $t['nombre'] : '' ),
					'descripcion' => sanitize_textarea_field( isset( $t['descripcion'] ) ? $t['descripcion'] : '' ),
					'icono'       => sanitize_text_field( isset( $t['icono'] ) ? $t['icono'] : '' ),
					'orden'       => absint( isset( $t['orden'] ) ? $t['orden'] : 0 ),
					'activo'      => absint( isset( $t['activo'] ) ? $t['activo'] : 1 ),
				)
			);
			if ( $new && $old ) {
				$id_map[ $old ] = (int) $new;
			}
		}

		foreach ( (array) ( isset( $data['preguntas'] ) ? $data['preguntas'] : array() ) as $p ) {
			$tema = isset( $p['tema_id'] ) && isset( $id_map[ (int) $p['tema_id'] ] ) ? $id_map[ (int) $p['tema_id'] ] : absint( isset( $p['tema_id'] ) ? $p['tema_id'] : 0 );
			$pregunta = sanitize_text_field( isset( $p['pregunta'] ) ? $p['pregunta'] : '' );
			if ( ! $pregunta || ! $tema ) {
				continue;
			}
			Class_Ygb_DB::save_pregunta(
				array(
					'id'          => 0,
					'tema_id'     => $tema,
					'pregunta'    => $pregunta,
					'variaciones' => sanitize_textarea_field( isset( $p['variaciones'] ) ? $p['variaciones'] : '' ),
					'keywords'    => sanitize_text_field( isset( $p['keywords'] ) ? $p['keywords'] : '' ),
					'respuesta'   => wp_kses_post( isset( $p['respuesta'] ) ? $p['respuesta'] : '' ),
					'enlace'      => esc_url_raw( isset( $p['enlace'] ) ? $p['enlace'] : '' ),
					'imagen'      => esc_url_raw( isset( $p['imagen'] ) ? $p['imagen'] : '' ),
					'archivo'     => esc_url_raw( isset( $p['archivo'] ) ? $p['archivo'] : '' ),
					'prioridad'   => absint( isset( $p['prioridad'] ) ? $p['prioridad'] : 0 ),
					'activo'      => absint( isset( $p['activo'] ) ? $p['activo'] : 1 ),
				)
			);
		}

		Class_Ygb_Search_Engine::invalidate_cache();
		self::redirect( admin_url( 'admin.php?page=ygb-bot-importacion&ygb_msg=import_ok' ) );
	}

	/**
	 * Borra registros (con confirmación previa en la vista).
	 *
	 * @return void
	 */
	private static function clear_logs() {
		global $wpdb;
		$t = Class_Ygb_DB::tables();
		$what = isset( $_POST['que'] ) ? sanitize_key( wp_unslash( $_POST['que'] ) ) : '';
		$map  = array(
			'mensajes'        => $t['mensajes'],
			'conversaciones'  => $t['conversaciones'],
			'derivaciones'    => $t['derivaciones'],
			'fallbacks'       => $t['fallbacks'],
		);
		if ( isset( $map[ $what ] ) ) {
			$wpdb->query( "TRUNCATE TABLE {$map[$what]}" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		self::redirect( admin_url( 'admin.php?page=ygb-bot-registros&ygb_msg=saved' ) );
	}

	/**
	 * Exporta los registros (logs) a CSV.
	 *
	 * @param string $tipo conversaciones|derivaciones|fallbacks.
	 * @return void
	 */
	private static function export_logs( $tipo ) {
		global $wpdb;

		$allowed = array( 'conversaciones', 'derivaciones', 'fallbacks' );
		if ( ! in_array( $tipo, $allowed, true ) ) {
			$tipo = 'conversaciones';
		}

		$tables = Class_Ygb_DB::tables();
		$name   = 'ygb-' . $tipo . '-' . gmdate( 'Ymd-His' );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $name . '.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( 'fallbacks' === $tipo ) {
			fputcsv( $out, array( 'id', 'mensaje_usuario', 'fecha' ) );
			$rows = $wpdb->get_results( "SELECT * FROM {$tables['fallbacks']} ORDER BY id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL
			foreach ( (array) $rows as $r ) {
				fputcsv( $out, array( $r->id, $r->mensaje_usuario, $r->fecha ) );
			}
		} elseif ( 'derivaciones' === $tipo ) {
			fputcsv( $out, array( 'id', 'conversacion_id', 'canal', 'mensaje_usuario', 'estado', 'fecha' ) );
			$rows = $wpdb->get_results( "SELECT * FROM {$tables['derivaciones']} ORDER BY id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL
			foreach ( (array) $rows as $r ) {
				fputcsv( $out, array( $r->id, $r->conversacion_id, $r->canal, $r->mensaje_usuario, $r->estado, $r->fecha ) );
			}
		} else {
			fputcsv( $out, array( 'conversacion_id', 'emisor', 'mensaje', 'pregunta_id', 'score', 'fecha' ) );
			$rows = $wpdb->get_results( "SELECT * FROM {$tables['mensajes']} ORDER BY conversacion_id ASC, id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL
			foreach ( (array) $rows as $r ) {
				fputcsv( $out, array( $r->conversacion_id, $r->emisor, $r->mensaje, $r->pregunta_id, $r->score, $r->fecha ) );
			}
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * AJAX: chat de prueba desde el dashboard (mismo motor que el frontend).
	 *
	 * @return void
	 */
	public static function ajax_test_chat() {
		check_ajax_referer( 'ygb_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sin permisos.', 'ygb-bot' ) ), 403 );
		}

		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$res     = Class_Ygb_Search_Engine::search( $message );

		if ( $res['matched'] && $res['question'] ) {
			$q = $res['question'];
			wp_send_json_success(
				array(
					'matched' => 1,
					'score'   => $res['score'],
					'answer'  => Class_Ygb_Search_Engine::format_answer( $q ),
					'pregunta' => $q->pregunta,
				)
			);
		}

		wp_send_json_success(
			array(
				'matched'  => 0,
				'message'  => Class_Ygb_DB::get_setting( 'fallback_msg' ),
				'suggest'  => array_map(
					function ( $s ) {
						return array(
							'id'       => (int) $s->id,
							'pregunta' => $s->pregunta,
						);
					},
					$res['suggest']
				),
			)
		);
	}
}
