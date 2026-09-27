<?php
/**
 * Vista: Apariencia del widget (Settings API).
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

$s = Class_Ygb_DB::get_settings();
?>
<div class="wrap ygb-wrap">
	<h1><?php esc_html_e( 'Apariencia', 'ygb-bot' ); ?></h1>

	<div class="ygb-columns">
		<div class="ygb-col">
			<form method="post" action="options.php">
				<?php settings_fields( 'ygb_bot_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ygb-bot-name"><?php esc_html_e( 'Nombre del bot', 'ygb-bot' ); ?></label></th>
						<td><input id="ygb-bot-name" type="text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bot_name]" class="regular-text" value="<?php echo esc_attr( $s['bot_name'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-avatar"><?php esc_html_e( 'Avatar (emoji o URL de imagen)', 'ygb-bot' ); ?></label></th>
						<td>
							<input id="ygb-avatar" type="text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[avatar]" class="regular-text code" value="<?php echo esc_attr( $s['avatar'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Ej.: 🤖 o https://…/avatar.png', 'ygb-bot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-color"><?php esc_html_e( 'Color principal', 'ygb-bot' ); ?></label></th>
						<td><input id="ygb-color" type="color" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[color]" value="<?php echo esc_attr( $s['color'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-position"><?php esc_html_e( 'Posición de la burbuja', 'ygb-bot' ); ?></label></th>
						<td>
							<select id="ygb-position" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[position]">
								<option value="right" <?php selected( $s['position'], 'right' ); ?>><?php esc_html_e( 'Abajo a la derecha', 'ygb-bot' ); ?></option>
								<option value="left" <?php selected( $s['position'], 'left' ); ?>><?php esc_html_e( 'Abajo a la izquierda', 'ygb-bot' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-size"><?php esc_html_e( 'Tamaño', 'ygb-bot' ); ?></label></th>
						<td>
							<select id="ygb-size" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[size]">
								<option value="small" <?php selected( $s['size'], 'small' ); ?>><?php esc_html_e( 'Pequeño', 'ygb-bot' ); ?></option>
								<option value="medium" <?php selected( $s['size'], 'medium' ); ?>><?php esc_html_e( 'Mediano', 'ygb-bot' ); ?></option>
								<option value="large" <?php selected( $s['size'], 'large' ); ?>><?php esc_html_e( 'Grande', 'ygb-bot' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-bubble-icon"><?php esc_html_e( 'Icono de la burbuja', 'ygb-bot' ); ?></label></th>
						<td><input id="ygb-bubble-icon" type="text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_icon]" class="small-text code" maxlength="8" value="<?php echo esc_attr( $s['bubble_icon'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-welcome"><?php esc_html_e( 'Mensaje de bienvenida', 'ygb-bot' ); ?></label></th>
						<td><textarea id="ygb-welcome" rows="3" class="large-text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[welcome]"><?php echo esc_textarea( $s['welcome'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-placeholder"><?php esc_html_e( 'Placeholder del campo de texto', 'ygb-bot' ); ?></label></th>
						<td><input id="ygb-placeholder" type="text" class="large-text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[placeholder]" value="<?php echo esc_attr( $s['placeholder'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Elementos visibles', 'ygb-bot' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[show_topics]" value="1" <?php checked( $s['show_topics'], 1 ); ?> /> <?php esc_html_e( 'Menú inicial de temas', 'ygb-bot' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[show_faq]" value="1" <?php checked( $s['show_faq'], 1 ); ?> /> <?php esc_html_e( 'FAQ sugeridas (más consultadas)', 'ygb-bot' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[show_support_btn]" value="1" <?php checked( $s['show_support_btn'], 1 ); ?> /> <?php esc_html_e( 'Botón persistente “Hablar con soporte”', 'ygb-bot' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[save_history]" value="1" <?php checked( $s['save_history'], 1 ); ?> /> <?php esc_html_e( 'Guardar historial en localStorage del navegador', 'ygb-bot' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[lazy_load]" value="1" <?php checked( $s['lazy_load'], 1 ); ?> /> <?php esc_html_e( 'Carga diferida del widget (recomendado)', 'ygb-bot' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Guardar apariencia', 'ygb-bot' ) ); ?>
			</form>
		</div>

		<div class="ygb-col ygb-col-preview">
			<h2><?php esc_html_e( 'Vista previa', 'ygb-bot' ); ?></h2>
			<div class="ygb-preview-card" style="--ygb-color:<?php echo esc_attr( $s['color'] ); ?>">
				<div class="ygb-preview-header">
					<span class="ygb-preview-avatar"><?php echo esc_html( mb_substr( $s['avatar'], 0, 2 ) ); ?></span>
					<span>
						<strong><?php echo esc_html( $s['bot_name'] ); ?></strong><br />
						<small><?php esc_html_e( '● En línea', 'ygb-bot' ); ?></small>
					</span>
				</div>
				<div class="ygb-preview-body">
					<div class="ygb-preview-msg"><?php echo esc_html( $s['welcome'] ); ?></div>
					<div class="ygb-preview-msg ygb-preview-user"><?php esc_html_e( '¿Cómo envío dinero con Zelle?', 'ygb-bot' ); ?></div>
				</div>
				<div class="ygb-preview-input"><span><?php echo esc_html( $s['placeholder'] ); ?></span><button type="button"><?php esc_html_e( '➤', 'ygb-bot' ); ?></button></div>
			</div>
			<p class="description"><?php esc_html_e( 'Vista orientativa. El widget real se ve en la parte inferior de tu sitio.', 'ygb-bot' ); ?></p>

			<h2><?php esc_html_e( 'Probar el bot', 'ygb-bot' ); ?></h2>
			<div id="ygb-test-chat" class="ygb-test-chat" aria-live="polite"></div>
			<form id="ygb-test-form" class="ygb-test-form">
				<input type="text" id="ygb-test-input" class="regular-text" placeholder="<?php esc_attr_e( 'Escribe una pregunta para probar el motor…', 'ygb-bot' ); ?>" />
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Probar bot', 'ygb-bot' ); ?></button>
			</form>
		</div>
	</div>
</div>
