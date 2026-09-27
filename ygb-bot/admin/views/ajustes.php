<?php
/**
 * Vista: Ajustes generales (Settings API).
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

$s      = Class_Ygb_DB::get_settings();
$prefix = Class_Ygb_DB::OPTION_KEY;
?>
<div class="wrap ygb-wrap">
	<h1><?php esc_html_e( 'Ajustes generales', 'ygb-bot' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'ygb_bot_group' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Bot activo', 'ygb-bot' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?> /> <?php esc_html_e( 'Activar el chatbot en todo el sitio', 'ygb-bot' ); ?></label>
					<p class="description"><?php esc_html_e( 'Si se desactiva, ni el shortcode ni la carga automática mostrarán el widget.', 'ygb-bot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Carga automática', 'ygb-bot' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[autoload]" value="1" <?php checked( $s['autoload'], 1 ); ?> /> <?php esc_html_e( 'Mostrar la burbuja flotante en todas las páginas', 'ygb-bot' ); ?></label>
					<p class="description"><?php esc_html_e( 'Si lo desactivas, podrás insertar el bot con el shortcode [ygb_bot], el bloque de Gutenberg o ygb_bot_render().', 'ygb-bot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ygb-threshold"><?php esc_html_e( 'Umbral de coincidencia (%)', 'ygb-bot' ); ?></label></th>
				<td>
					<input id="ygb-threshold" type="number" min="1" max="100" class="small-text" name="<?php echo esc_attr( $prefix ); ?>[threshold]" value="<?php echo esc_attr( $s['threshold'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Score mínimo para dar una respuesta por buena. Por defecto: 60.', 'ygb-bot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Caché', 'ygb-bot' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[cache_enabled]" value="1" <?php checked( $s['cache_enabled'], 1 ); ?> /> <?php esc_html_e( 'Cachear la base de conocimiento en transients (se invalida al guardar)', 'ygb-bot' ); ?></label>
					<p class="description"><?php esc_html_e( 'Recomendado. Compatible con WP Rocket, W3TC y LiteSpeed (el widget se hidrata vía JS, no se cachea dinámico).', 'ygb-bot' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Privacidad (RGPD)', 'ygb-bot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Guardar conversaciones', 'ygb-bot' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[store_chats]" value="1" <?php checked( $s['store_chats'], 1 ); ?> /> <?php esc_html_e( 'Registrar mensajes y conversaciones en la base de datos', 'ygb-bot' ); ?></label>
					<p class="description"><?php esc_html_e( 'Desactívalo para modo 100% privado: el bot funciona sin escribir nada en las tablas de registros.', 'ygb-bot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Consentimiento', 'ygb-bot' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[require_consent]" value="1" <?php checked( $s['require_consent'], 1 ); ?> /> <?php esc_html_e( 'Pedir consentimiento al usuario antes de guardar su conversación', 'ygb-bot' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Anonimizar IPs', 'ygb-bot' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[anonymize_ips]" value="1" <?php checked( $s['anonymize_ips'], 1 ); ?> /> <?php esc_html_e( 'Guardar solo un hash de la IP (no reversible)', 'ygb-bot' ); ?></label>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Guardar ajustes generales', 'ygb-bot' ) ); ?>
	</form>

	<hr />
	<h2><?php esc_html_e( 'Datos técnicos', 'ygb-bot' ); ?></h2>
	<table class="widefat striped" style="max-width:640px">
		<tbody>
			<tr><td><?php esc_html_e( 'Versión del plugin', 'ygb-bot' ); ?></td><td><code><?php echo esc_html( YGB_BOT_VERSION ); ?></code></td></tr>
			<tr><td><?php esc_html_e( 'Versión de la BD', 'ygb-bot' ); ?></td><td><code><?php echo esc_html( (string) get_option( 'ygb_bot_db_version', YGB_BOT_DB_VERSION ) ); ?></code></td></tr>
			<tr><td><?php esc_html_e( 'PHP', 'ygb-bot' ); ?></td><td><code><?php echo esc_html( PHP_VERSION ); ?></code></td></tr>
			<tr><td><?php esc_html_e( 'WordPress', 'ygb-bot' ); ?></td><td><code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code></td></tr>
		</tbody>
	</table>
</div>
