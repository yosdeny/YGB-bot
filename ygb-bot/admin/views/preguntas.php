<?php
/**
 * Vista: CRUD de Preguntas (editor + lista con filtros, paginación y acciones en masa).
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

$temas   = isset( $ygb_view_data['temas'] ) ? $ygb_view_data['temas'] : Class_Ygb_DB::get_temas();
$list    = isset( $ygb_view_data['list'] ) ? $ygb_view_data['list'] : Class_Ygb_DB::get_preguntas();
$args    = isset( $ygb_view_data['args'] ) ? $ygb_view_data['args'] : array();
$editing = isset( $ygb_view_data['editing'] ) ? $ygb_view_data['editing'] : null;

$args       = wp_parse_args( $args, array( 'tema_id' => 0, 'activo' => -1, 'search' => '', 'page' => 1, 'per_page' => 20 ) );
$total      = (int) $list['total'];
$pages      = max( 1, (int) ceil( $total / $args['per_page'] ) );
$page       = min( max( 1, (int) $args['page'] ), $pages );
$tema_names = array();
foreach ( $temas as $t ) {
	$tema_names[ (int) $t->id ] = $t->nombre;
}
$base_url = admin_url( 'admin.php?page=ygb-bot-preguntas' );
?>
<div class="wrap ygb-wrap">
	<h1><?php esc_html_e( 'Preguntas', 'ygb-bot' ); ?></h1>

	<?php if ( $editing || ! empty( $_GET['nueva'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<h2 class="ygb-subtitle"><?php $editing ? esc_html_e( 'Editar pregunta', 'ygb-bot' ) : esc_html_e( 'Añadir pregunta', 'ygb-bot' ); ?></h2>
		<form method="post" action="<?php echo esc_url( $base_url ); ?>">
			<?php wp_nonce_field( 'ygb_manage' ); ?>
			<input type="hidden" name="ygb_action" value="pregunta_save" />
			<input type="hidden" name="id" value="<?php echo $editing ? esc_attr( $editing->id ) : ''; ?>" />
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ygb-tema-id"><?php esc_html_e( 'Tema', 'ygb-bot' ); ?> *</label></th>
					<td>
						<select name="tema_id" id="ygb-tema-id" required>
							<option value=""><?php esc_html_e( '— Selecciona un tema —', 'ygb-bot' ); ?></option>
							<?php foreach ( $temas as $t ) : ?>
								<option value="<?php echo esc_attr( $t->id ); ?>" <?php selected( $editing && (int) $editing->tema_id === (int) $t->id ); ?>>
									<?php echo esc_html( $t->icono . ' ' . $t->nombre ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Obligatorio. Los temas agrupan las preguntas.', 'ygb-bot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ygb-pregunta"><?php esc_html_e( 'Pregunta principal', 'ygb-bot' ); ?> *</label></th>
					<td><input name="pregunta" id="ygb-pregunta" type="text" class="large-text" required maxlength="191"
						value="<?php echo $editing ? esc_attr( $editing->pregunta ) : ''; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ygb-variaciones"><?php esc_html_e( 'Variaciones / sinónimos', 'ygb-bot' ); ?></label></th>
					<td>
						<textarea name="variaciones" id="ygb-variaciones" rows="4" class="large-text"
							placeholder="<?php esc_attr_e( "Una variación por línea…", 'ygb-bot' ); ?>"><?php echo $editing ? esc_textarea( $editing->variaciones ) : ''; ?></textarea>
						<p class="description"><?php esc_html_e( 'Una por línea. Se buscan con coincidencia exacta tras normalizar.', 'ygb-bot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ygb-keywords"><?php esc_html_e( 'Palabras clave', 'ygb-bot' ); ?></label></th>
					<td>
						<input name="keywords" id="ygb-keywords" type="text" class="large-text"
							value="<?php echo $editing ? esc_attr( $editing->keywords ) : ''; ?>" placeholder="<?php esc_attr_e( 'zelle, pago, enviar dinero', 'ygb-bot' ); ?>" />
						<p class="description"><?php esc_html_e( 'Separadas por comas. Tienen alto peso en el score.', 'ygb-bot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ygb-respuesta"><?php esc_html_e( 'Respuesta', 'ygb-bot' ); ?> *</label></th>
					<td>
						<?php
						$respuesta = $editing ? $editing->respuesta : '';
						if ( current_user_can( 'edit_posts' ) && user_can_richedit() ) {
							wp_editor(
								$respuesta,
								'ygb_respuesta',
								array(
									'textarea_name' => 'respuesta',
									'textarea_rows' => 8,
									'media_buttons' => false,
									'teeny'         => true,
									'quicktags'     => true,
								)
							);
						} else {
							?>
							<textarea name="respuesta" id="ygb-respuesta" rows="8" class="large-text" required><?php echo esc_textarea( $respuesta ); ?></textarea>
							<?php
						}
						?>
						<p class="description"><?php esc_html_e( 'Soporta HTML básico. Se sanea con wp_kses_post al guardar.', 'ygb-bot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Adjuntos opcionales', 'ygb-bot' ); ?></th>
					<td>
						<p><label><?php esc_html_e( 'Enlace:', 'ygb-bot' ); ?>
							<input name="enlace" type="url" class="large-text code" value="<?php echo $editing ? esc_attr( $editing->enlace ) : ''; ?>" placeholder="https://…" /></label></p>
						<p>
							<label><?php esc_html_e( 'Imagen (URL):', 'ygb-bot' ); ?></label>
							<input name="imagen" type="url" class="large-text code ygb-media-input" value="<?php echo $editing ? esc_attr( $editing->imagen ) : ''; ?>" placeholder="https://…" />
							<button type="button" class="button ygb-media-picker" data-target="imagen"><?php esc_html_e( 'Biblioteca de medios', 'ygb-bot' ); ?></button>
						</p>
						<p>
							<label><?php esc_html_e( 'Archivo (URL de descarga):', 'ygb-bot' ); ?></label>
							<input name="archivo" type="url" class="large-text code ygb-media-input" value="<?php echo $editing ? esc_attr( $editing->archivo ) : ''; ?>" placeholder="https://…/guia.pdf" />
							<button type="button" class="button ygb-media-picker" data-target="archivo"><?php esc_html_e( 'Biblioteca de medios', 'ygb-bot' ); ?></button>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ygb-prioridad"><?php esc_html_e( 'Prioridad', 'ygb-bot' ); ?></label></th>
					<td>
						<input name="prioridad" id="ygb-prioridad" type="number" min="0" class="small-text"
							value="<?php echo $editing ? esc_attr( $editing->prioridad ) : '0'; ?>" />
						<p class="description"><?php esc_html_e( 'Desempata cuando dos preguntas obtienen el mismo score.', 'ygb-bot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Estado', 'ygb-bot' ); ?></th>
					<td><label><input type="checkbox" name="activo" value="1" <?php checked( ! $editing || $editing->activo, 1 ); ?> /> <?php esc_html_e( 'Activa (visible para el bot)', 'ygb-bot' ); ?></label></td>
				</tr>
			</table>
			<?php submit_button( $editing ? __( 'Actualizar pregunta', 'ygb-bot' ) : __( 'Crear pregunta', 'ygb-bot' ) ); ?>
			<a href="<?php echo esc_url( $base_url ); ?>" class="button"><?php esc_html_e( 'Cancelar', 'ygb-bot' ); ?></a>
		</form>
		<hr />
	<?php endif; ?>

	<h2 class="ygb-subtitle"><?php esc_html_e( 'Listado de preguntas', 'ygb-bot' ); ?></h2>

	<form method="get" action="<?php echo esc_url( $base_url ); ?>" class="ygb-filter-form">
		<input type="hidden" name="page" value="ygb-bot-preguntas" />
		<select name="tema">
			<option value="0"><?php esc_html_e( 'Todos los temas', 'ygb-bot' ); ?></option>
			<?php foreach ( $temas as $t ) : ?>
				<option value="<?php echo esc_attr( $t->id ); ?>" <?php selected( (int) $args['tema_id'], (int) $t->id ); ?>><?php echo esc_html( $t->nombre ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="estado">
			<option value="-1"><?php esc_html_e( 'Todos los estados', 'ygb-bot' ); ?></option>
			<option value="1" <?php selected( (int) $args['activo'], 1 ); ?>><?php esc_html_e( 'Activas', 'ygb-bot' ); ?></option>
			<option value="0" <?php selected( (int) $args['activo'], 0 ); ?>><?php esc_html_e( 'Inactivas', 'ygb-bot' ); ?></option>
		</select>
		<input type="search" name="s" value="<?php echo esc_attr( $args['search'] ); ?>" placeholder="<?php esc_attr_e( 'Buscar pregunta o keyword…', 'ygb-bot' ); ?>" />
		<button type="submit" class="button"><?php esc_html_e( 'Filtrar', 'ygb-bot' ); ?></button>
		<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'nueva', 1, $base_url ) ); ?>"><?php esc_html_e( '+ Añadir pregunta', 'ygb-bot' ); ?></a>
	</form>

	<form method="post" action="">
		<?php wp_nonce_field( 'ygb_manage' ); ?>
		<input type="hidden" name="ygb_action" value="bulk" />
		<div class="ygb-bulk-bar">
			<select name="bulk_op">
				<option value=""><?php esc_html_e( 'Acciones en masa…', 'ygb-bot' ); ?></option>
				<option value="activar"><?php esc_html_e( 'Activar', 'ygb-bot' ); ?></option>
				<option value="desactivar"><?php esc_html_e( 'Desactivar', 'ygb-bot' ); ?></option>
				<option value="mover"><?php esc_html_e( 'Mover a tema…', 'ygb-bot' ); ?></option>
				<option value="eliminar"><?php esc_html_e( 'Eliminar definitivamente', 'ygb-bot' ); ?></option>
			</select>
			<select name="bulk_tema">
				<option value="0"><?php esc_html_e( '(Tema destino)', 'ygb-bot' ); ?></option>
				<?php foreach ( $temas as $t ) : ?>
					<option value="<?php echo esc_attr( $t->id ); ?>"><?php echo esc_html( $t->nombre ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Aplicar', 'ygb-bot' ); ?></button>
		</div>

		<table class="widefat striped">
			<thead>
				<tr>
					<th class="check-column"><input type="checkbox" id="ygb-check-all" title="<?php esc_attr_e( 'Seleccionar todo', 'ygb-bot' ); ?>" /></th>
					<th><?php esc_html_e( 'Pregunta', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Tema', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Keywords', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Prioridad', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Consultas', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Estado', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Modificada', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Acciones', 'ygb-bot' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $list['items'] ) ) : ?>
				<tr><td colspan="9"><?php esc_html_e( 'No hay preguntas que coincidan con los filtros.', 'ygb-bot' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $list['items'] as $p ) : ?>
					<tr>
						<th class="check-column"><input type="checkbox" name="ids[]" class="ygb-check" value="<?php echo esc_attr( $p->id ); ?>" /></th>
						<td><strong><?php echo esc_html( $p->pregunta ); ?></strong>
							<?php if ( trim( (string) $p->variaciones ) !== '' ) : ?>
								<br /><span class="description"><?php echo esc_html( sprintf( /* translators: %d: número de variaciones. */ __( '%d variaciones', 'ygb-bot' ), count( array_filter( preg_split( '/\r\n|\r|\n/', $p->variaciones ) ) ) ) ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( isset( $tema_names[ (int) $p->tema_id ] ) ? $tema_names[ (int) $p->tema_id ] : '—' ); ?></td>
						<td><code><?php echo esc_html( wp_trim_words( $p->keywords, 6, '…' ) ); ?></code></td>
						<td><?php echo esc_html( (int) $p->prioridad ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $p->contador ) ); ?></td>
						<td><?php echo $p->activo ? esc_html__( 'Activa', 'ygb-bot' ) : esc_html__( 'Inactiva', 'ygb-bot' ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $p->modificado ) ) ); ?></td>
						<td>
							<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'editar', (int) $p->id, $base_url ) ); ?>"><?php esc_html_e( 'Editar', 'ygb-bot' ); ?></a>
							<button type="button" class="button button-small button-link-delete ygb-delete"
								form="ygb-del-<?php echo esc_attr( $p->id ); ?>"
								data-ygb-confirm="<?php esc_attr_e( '¿Eliminar esta pregunta? No se puede deshacer.', 'ygb-bot' ); ?>"><?php esc_html_e( 'Eliminar', 'ygb-bot' ); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</form>

	<?php foreach ( $list['items'] as $p ) : ?>
		<form method="post" action="" id="ygb-del-<?php echo esc_attr( $p->id ); ?>" class="ygb-hidden-form">
			<?php wp_nonce_field( 'ygb_manage' ); ?>
			<input type="hidden" name="ygb_action" value="pregunta_delete" />
			<input type="hidden" name="id" value="<?php echo esc_attr( $p->id ); ?>" />
		</form>
	<?php endforeach; ?>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav"><div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%', $base_url ),
						'format'    => '',
						'current'   => $page,
						'total'     => $pages,
						'prev_text' => '«',
						'next_text' => '»',
					)
				)
			);
			?>
		</div></div>
	<?php endif; ?>
	<p class="description"><?php echo esc_html( sprintf( /* translators: %s: total. */ __( 'Total de preguntas: %s', 'ygb-bot' ), number_format_i18n( $total ) ) ); ?></p>
</div>
