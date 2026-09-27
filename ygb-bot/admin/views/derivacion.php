<?php
/**
 * Vista: Derivación a soporte y fallback (Settings API).
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

$s      = Class_Ygb_DB::get_settings();
$prefix = Class_Ygb_DB::OPTION_KEY;
?>
<div class="wrap ygb-wrap">
	<h1><?php esc_html_e( 'Derivación a soporte', 'ygb-bot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Configura qué hace el bot cuando no encuentra una respuesta y los canales para contactar con un humano.', 'ygb-bot' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( 'ygb_bot_derivacion' ); ?>

		<h2><?php esc_html_e( 'Fallback', 'ygb-bot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="ygb-fallback-msg"><?php esc_html_e( 'Mensaje de fallback', 'ygb-bot' ); ?></label></th>
				<td>
					<textarea id="ygb-fallback-msg" rows="3" class="large-text" name="<?php echo esc_attr( $prefix ); ?>[fallback_msg]"><?php echo esc_textarea( $s['fallback_msg'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Se muestra cuando ninguna pregunta supera el umbral de coincidencia.', 'ygb-bot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ygb-threshold-dc"><?php esc_html_e( 'Umbral mínimo de coincidencia (%)', 'ygb-bot' ); ?></label></th>
				<td>
					<input id="ygb-threshold-dc" type="number" min="1" max="100" class="small-text" name="<?php echo esc_attr( $prefix ); ?>[threshold]" value="<?php echo esc_attr( $s['threshold'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Por defecto 60. Cuanto más alto, más estricto el bot (más fallbacks).', 'ygb-bot' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Canales de derivación', 'ygb-bot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Canales activos', 'ygb-bot' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[dc_email]" value="1" <?php checked( $s['dc_email'], 1 ); ?> /> <?php esc_html_e( 'Correo electrónico (mailto: con la conversación prellenada)', 'ygb-bot' ); ?></label><br />
					<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[dc_whatsapp]" value="1" <?php checked( $s['dc_whatsapp'], 1 ); ?> /> <?php esc_html_e( 'WhatsApp (wa.me con mensaje prellenado)', 'ygb-bot' ); ?></label><br />
					<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[dc_contact]" value="1" <?php checked( $s['dc_contact'], 1 ); ?> /> <?php esc_html_e( 'Página de contacto interna (URL)', 'ygb-bot' ); ?></label><br />
					<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[dc_form]" value="1" <?php checked( $s['dc_form'], 1 ); ?> /> <?php esc_html_e( 'Formulario interno (guarda ticket en BD y notifica al admin)', 'ygb-bot' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ygb-email"><?php esc_html_e( 'Correo de soporte', 'ygb-bot' ); ?></label></th>
				<td><input id="ygb-email" type="email" class="regular-text" name="<?php echo esc_attr( $prefix ); ?>[support_email]" value="<?php echo esc_attr( $s['support_email'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="ygb-wa"><?php esc_html_e( 'Número de WhatsApp', 'ygb-bot' ); ?></label></th>
				<td>
					<input id="ygb-wa" type="text" class="regular-text" name="<?php echo esc_attr( $prefix ); ?>[whatsapp]" value="<?php echo esc_attr( $s['whatsapp'] ); ?>" placeholder="+584121234567" />
					<p class="description"><?php esc_html_e( 'Con código de país, solo dígitos y/o signo +. Se normaliza automáticamente para wa.me.', 'ygb-bot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ygb-contact-url"><?php esc_html_e( 'URL de la página de contacto', 'ygb-bot' ); ?></label></th>
				<td><input id="ygb-contact-url" type="url" class="large-text code" name="<?php echo esc_attr( $prefix ); ?>[contact_url]" value="<?php echo esc_attr( $s['contact_url'] ); ?>" placeholder="https://example.com/contacto" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="ygb-subject"><?php esc_html_e( 'Asunto del correo', 'ygb-bot' ); ?></label></th>
				<td><input id="ygb-subject" type="text" class="regular-text" name="<?php echo esc_attr( $prefix ); ?>[email_subject]" value="<?php echo esc_attr( $s['email_subject'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="ygb-privacy"><?php esc_html_e( 'URL de la política de privacidad', 'ygb-bot' ); ?></label></th>
				<td>
					<input id="ygb-privacy" type="url" class="large-text code" name="<?php echo esc_attr( $prefix ); ?>[privacy_url]" value="<?php echo esc_attr( $s['privacy_url'] ); ?>" />
					<p class="description"><?php esc_html_e( 'RGPD: se enlaza desde el aviso de consentimiento del widget.', 'ygb-bot' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Guardar derivación', 'ygb-bot' ) ); ?>
	</form>

	<h2><?php esc_html_e( 'Cómo funciona la derivación', 'ygb-bot' ); ?></h2>
	<ul class="ul-square">
		<li><?php esc_html_e( 'Intención directa: si el usuario escribe “humano”, “persona”, “agente”, “soporte”, “ayuda real” u “operador”, el bot ofrece derivación de inmediato.', 'ygb-bot' ); ?></li>
		<li><?php esc_html_e( 'Insistencia: tras 3 mensajes sin coincidencia, se ofrece derivación automáticamente.', 'ygb-bot' ); ?></li>
		<li><?php esc_html_e( 'Registro: cada derivación se guarda (fecha, mensaje, canal) en la pestaña Registros.', 'ygb-bot' ); ?></li>
		<li><?php esc_html_e( 'El botón “Hablar con soporte” está siempre visible en el widget si está activado en Apariencia.', 'ygb-bot' ); ?></li>
	</ul>
</div>
