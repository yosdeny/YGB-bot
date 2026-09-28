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
				<?php settings_fields( 'ygb_bot_apariencia' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ygb-bot-name"><?php esc_html_e( 'Nombre del bot', 'ygb-bot' ); ?></label></th>
						<td><input id="ygb-bot-name" type="text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bot_name]" class="regular-text" value="<?php echo esc_attr( $s['bot_name'] ); ?>" /></td>
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
					<?php $ygb_has_logo = (bool) preg_match( '#^https?://#i', $s['bubble_logo'] ); ?>
					<tr>
						<th scope="row"><label for="ygb-bubble-icon"><?php esc_html_e( 'Icono de la burbuja', 'ygb-bot' ); ?></label></th>
						<td>
							<fieldset class="ygb-bubble-icon-field">
								<label class="ygb-bubble-icon-choice">
									<input type="radio" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_icon_type]" value="emoji" <?php checked( ! $ygb_has_logo ); ?> />
									<?php esc_html_e( 'Emoji', 'ygb-bot' ); ?>
								</label>
								<label class="ygb-bubble-icon-choice">
									<input type="radio" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_icon_type]" value="image" <?php checked( $ygb_has_logo ); ?> />
									<?php esc_html_e( 'Logo personalizado (imagen)', 'ygb-bot' ); ?>
								</label>
								<!-- Opcion A: emoji dentro del boton. -->
								<div class="ygb-bubble-emoji"<?php echo $ygb_has_logo ? ' hidden' : ''; ?>>
									<input id="ygb-bubble-icon" type="text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_icon]" class="small-text code" maxlength="8" placeholder="💬" value="<?php echo esc_attr( $s['bubble_icon'] ); ?>" />
									<p class="description"><?php esc_html_e( 'Ej.: 💬, 🖥️, 🤖…', 'ygb-bot' ); ?></p>
								</div>
								<!-- Opcion B: logo personalizado (subida real o Biblioteca de medios). -->
								<div class="ygb-bubble-logo"<?php echo $ygb_has_logo ? '' : ' hidden'; ?>>
									<p class="ygb-bubble-logo-actions">
										<button type="button" class="button button-primary ygb-bubble-logo-upload"><?php esc_html_e( 'Subir imagen', 'ygb-bot' ); ?></button>
										<button type="button" class="button ygb-media-picker"><?php esc_html_e( 'Biblioteca de medios', 'ygb-bot' ); ?></button>
										<button type="button" class="button-link ygb-bubble-logo-clear"<?php echo $ygb_has_logo ? '' : ' hidden'; ?>><?php esc_html_e( 'Quitar logo', 'ygb-bot' ); ?></button>
									</p>
									<!-- Input de archivo real: sube la imagen a la Biblioteca de medios via AJAX de WP. -->
									<input type="file" id="ygb-bubble-logo-file" class="ygb-bubble-logo-file" accept="image/png,image/jpeg,image/gif,image/webp" style="display:none;" />
									<!-- Estado de la subida. -->
									<p class="ygb-bubble-logo-status" role="status" aria-live="polite" hidden></p>
									<!-- Vista previa del logo dentro del boton flotante. -->
									<span class="ygb-bubble-logo-preview-btn"<?php echo $ygb_has_logo ? '' : ' hidden'; ?> style="--preview-bubble-size:56px;--preview-logo-scale:<?php echo esc_attr( max( 30, min( 100, absint( $s['bubble_logo_size'] ) ) / 100 ) ); ?>;background:<?php echo esc_attr( $s['bubble_color'] ); ?>;">
										<img id="ygb-bubble-logo-thumb" class="ygb-bubble-logo-thumb" alt="" src="<?php echo esc_url( $ygb_has_logo ? $s['bubble_logo'] : '' ); ?>"<?php echo $ygb_has_logo ? '' : ' hidden'; ?> />
									</span>
									<p class="description"><?php esc_html_e( 'Vista previa del logo dentro del botón.', 'ygb-bot' ); ?></p>
									<label for="ygb-bubble-logo-size"><strong><?php esc_html_e( 'Tamaño del logo (%)', 'ygb-bot' ); ?></strong></label>
									<input id="ygb-bubble-logo-size" type="number" min="30" max="100" step="1" class="small-text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_logo_size]" value="<?php echo esc_attr( max( 30, min( 100, absint( $s['bubble_logo_size'] ) ) ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Tamaño del logo dentro del botón (30-100% del diámetro).', 'ygb-bot' ); ?></p>
									<details class="ygb-bubble-logo-url-details">
										<summary><?php esc_html_e( 'Usar una URL existente (opcional)', 'ygb-bot' ); ?></summary>
										<input id="ygb-bubble-logo-url" type="url" class="large-text code ygb-media-input" maxlength="2048" placeholder="https://…/logo.png" value="<?php echo esc_url( $ygb_has_logo ? $s['bubble_logo'] : '' ); ?>" />
									</details>
								</div>
								<!-- Valor final que se guarda: el JS escribe aqui la URL del logo (vacio = sin logo). -->
								<input id="ygb-bubble-logo" type="hidden" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_logo]" value="<?php echo esc_attr( $s['bubble_logo'] ); ?>" />
							</fieldset>
							<p class="description"><?php esc_html_e( 'Elige un emoji o sube/selecciona un logo personalizado. El logo se guarda en la Biblioteca de medios y sustituye al icono del botón flotante.', 'ygb-bot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-bubble-color"><?php esc_html_e( 'Color del botón', 'ygb-bot' ); ?></label></th>
						<td>
							<input id="ygb-bubble-color" type="color" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_color]" value="<?php echo esc_attr( $s['bubble_color'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Por defecto: #25D366 (verde WhatsApp).', 'ygb-bot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-bubble-color-hover"><?php esc_html_e( 'Color del botón al pasar el cursor', 'ygb-bot' ); ?></label></th>
						<td>
							<input id="ygb-bubble-color-hover" type="color" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_color_hover]" value="<?php echo esc_attr( $s['bubble_color_hover'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Por defecto: #128C7E (verde oscuro).', 'ygb-bot' ); ?></p>
						</td>
					</tr>
					<tr>
						<tr>
							<th colspan="2" scope="rowgroup"><h2 class="title" style="margin-top:0"><?php esc_html_e( 'Ajustes de escritorio', 'ygb-bot' ); ?></h2></th>
						</tr>
						<tr>
							<th scope="row"><label for="ygb-bubble-size"><?php esc_html_e( 'Tamaño del icono (px)', 'ygb-bot' ); ?></label></th>
							<td>
								<input id="ygb-bubble-size" type="number" min="30" max="120" step="1" class="small-text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_size]" value="<?php echo esc_attr( $s['bubble_size'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Diámetro del botón en escritorio (30-120px)', 'ygb-bot' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ygb-bubble-offset-x"><?php esc_html_e( 'Desplazamiento horizontal (px)', 'ygb-bot' ); ?></label></th>
							<td>
								<input id="ygb-bubble-offset-x" type="number" min="0" max="500" step="1" class="small-text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_offset_x]" value="<?php echo esc_attr( $s['bubble_offset_x'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Distancia desde el borde izquierdo/derecho en escritorio', 'ygb-bot' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ygb-bubble-offset-y"><?php esc_html_e( 'Desplazamiento vertical (px)', 'ygb-bot' ); ?></label></th>
							<td>
								<input id="ygb-bubble-offset-y" type="number" min="0" max="500" step="1" class="small-text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_offset_y]" value="<?php echo esc_attr( $s['bubble_offset_y'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Distancia desde el borde inferior en escritorio', 'ygb-bot' ); ?></p>
							</td>
						</tr>
						<tr>
							<th colspan="2" scope="rowgroup"><h2 class="title" style="margin-top:0"><?php esc_html_e( 'Ajustes móviles', 'ygb-bot' ); ?></h2>
								<p class="description"><?php esc_html_e( 'Estos ajustes se aplican cuando el ancho de pantalla es de 768px o menos', 'ygb-bot' ); ?></p>
							</th>
						</tr>
						<tr>
							<th scope="row"><label for="ygb-bubble-size-mobile"><?php esc_html_e( 'Tamaño del icono (px) - Móvil', 'ygb-bot' ); ?></label></th>
							<td>
								<input id="ygb-bubble-size-mobile" type="number" min="30" max="100" step="1" class="small-text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_size_mobile]" value="<?php echo esc_attr( $s['bubble_size_mobile'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Diámetro del botón en móvil (30-100px)', 'ygb-bot' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ygb-bubble-offset-x-mobile"><?php esc_html_e( 'Desplazamiento horizontal (px) - Móvil', 'ygb-bot' ); ?></label></th>
							<td>
								<input id="ygb-bubble-offset-x-mobile" type="number" min="0" max="300" step="1" class="small-text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_offset_x_mobile]" value="<?php echo esc_attr( $s['bubble_offset_x_mobile'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Distancia desde el borde izquierdo/derecho en móvil', 'ygb-bot' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ygb-bubble-offset-y-mobile"><?php esc_html_e( 'Desplazamiento vertical (px) - Móvil', 'ygb-bot' ); ?></label></th>
							<td>
								<input id="ygb-bubble-offset-y-mobile" type="number" min="0" max="500" step="1" class="small-text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[bubble_offset_y_mobile]" value="<?php echo esc_attr( $s['bubble_offset_y_mobile'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Distancia desde el borde inferior en móvil', 'ygb-bot' ); ?></p>
							</td>
						</tr>
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
			<?php $ygb_logo_scale = max( 30, min( 100, absint( $s['bubble_logo_size'] ) ) ) / 100; ?>
			<div class="ygb-preview-bubble-wrap">
				<span class="ygb-preview-bubble" style="--preview-bubble-size:<?php echo esc_attr( max( 30, min( 120, absint( $s['bubble_size'] ) ) ) ); ?>px;--preview-logo-scale:<?php echo esc_attr( $ygb_logo_scale ); ?>;background:<?php echo esc_attr( $s['bubble_color'] ); ?>;">
					<?php if ( preg_match( '#^https?://#i', $s['bubble_logo'] ) ) : ?>
						<img class="ygb-preview-bubble-logo" src="<?php echo esc_url( $s['bubble_logo'] ); ?>" alt="" />
					<?php else : ?>
						<span class="ygb-preview-bubble-icon"><?php echo esc_html( $s['bubble_icon'] ); ?></span>
					<?php endif; ?>
				</span>
				<p class="description"><?php esc_html_e( 'Vista previa del botón flotante (color, logo y tamaño).', 'ygb-bot' ); ?></p>
			</div>
			<div class="ygb-preview-card" style="--ygb-color:<?php echo esc_attr( $s['color'] ); ?>">
				<div class="ygb-preview-header">
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
