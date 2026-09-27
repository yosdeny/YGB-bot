<?php
/**
 * Vista: CRUD de Temas.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

$editing = isset( $ygb_view_data['editing'] ) ? $ygb_view_data['editing'] : null;
$search  = isset( $ygb_view_data['search'] ) ? $ygb_view_data['search'] : '';
$temas   = Class_Ygb_DB::get_temas( null, $search );
?>
<div class="wrap ygb-wrap">
	<h1><?php esc_html_e( 'Temas', 'ygb-bot' ); ?></h1>

	<div class="ygb-columns">
		<div class="ygb-col">
			<h2><?php echo $editing ? esc_html__( 'Editar tema', 'ygb-bot' ) : esc_html__( 'Añadir tema', 'ygb-bot' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ygb-bot-temas' ) ); ?>">
				<?php wp_nonce_field( 'ygb_manage' ); ?>
				<input type="hidden" name="ygb_action" value="tema_save" />
				<input type="hidden" name="id" value="<?php echo $editing ? esc_attr( $editing->id ) : ''; ?>" />
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ygb-nombre"><?php esc_html_e( 'Nombre', 'ygb-bot' ); ?> *</label></th>
						<td><input name="nombre" id="ygb-nombre" type="text" class="regular-text" required maxlength="191"
							value="<?php echo $editing ? esc_attr( $editing->nombre ) : ''; ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-desc"><?php esc_html_e( 'Descripción', 'ygb-bot' ); ?></label></th>
						<td><textarea name="descripcion" id="ygb-desc" rows="3" class="large-text"><?php echo $editing ? esc_textarea( $editing->descripcion ) : ''; ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-icono"><?php esc_html_e( 'Icono (emoji o dashicon)', 'ygb-bot' ); ?></label></th>
						<td>
							<input name="icono" id="ygb-icono" type="text" class="small-text" maxlength="20"
								value="<?php echo $editing ? esc_attr( $editing->icono ) : ''; ?>" placeholder="🛟" />
							<p class="description"><?php esc_html_e( 'Ej.: 💬, 📄 o "dashicons-info".', 'ygb-bot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ygb-orden"><?php esc_html_e( 'Orden', 'ygb-bot' ); ?></label></th>
						<td><input name="orden" id="ygb-orden" type="number" min="0" class="small-text"
							value="<?php echo $editing ? esc_attr( $editing->orden ) : '0'; ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Estado', 'ygb-bot' ); ?></th>
						<td>
							<label><input type="checkbox" name="activo" value="1" <?php checked( ! $editing || $editing->activo, 1 ); ?> /> <?php esc_html_e( 'Activo', 'ygb-bot' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button( $editing ? __( 'Actualizar tema', 'ygb-bot' ) : __( 'Crear tema', 'ygb-bot' ) ); ?>
			</form>
		</div>

		<div class="ygb-col">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php?page=ygb-bot-temas' ) ); ?>" class="ygb-filter-form">
				<input type="hidden" name="page" value="ygb-bot-temas" />
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Buscar temas…', 'ygb-bot' ); ?>" />
				<button type="submit" class="button"><?php esc_html_e( 'Buscar', 'ygb-bot' ); ?></button>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Icono', 'ygb-bot' ); ?></th>
						<th><?php esc_html_e( 'Nombre', 'ygb-bot' ); ?></th>
						<th><?php esc_html_e( 'Preguntas', 'ygb-bot' ); ?></th>
						<th><?php esc_html_e( 'Orden', 'ygb-bot' ); ?></th>
						<th><?php esc_html_e( 'Estado', 'ygb-bot' ); ?></th>
						<th><?php esc_html_e( 'Acciones', 'ygb-bot' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $temas ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No hay temas.', 'ygb-bot' ); ?></td></tr>
				<?php else : ?>
					<?php
					foreach ( $temas as $t ) :
						$np = Class_Ygb_DB::get_preguntas( array( 'tema_id' => (int) $t->id, 'per_page' => 1 ) );
						?>
						<tr>
							<td><?php echo esc_html( $t->icono ); ?></td>
							<td><strong><?php echo esc_html( $t->nombre ); ?></strong><br /><span class="description"><?php echo esc_html( wp_trim_words( $t->descripcion, 10 ) ); ?></span></td>
							<td><?php echo esc_html( number_format_i18n( (int) $np['total'] ) ); ?></td>
							<td><?php echo esc_html( (int) $t->orden ); ?></td>
							<td><?php echo $t->activo ? esc_html__( 'Activo', 'ygb-bot' ) : esc_html__( 'Inactivo', 'ygb-bot' ); ?></td>
							<td>
								<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=ygb-bot-temas&editar=' . (int) $t->id ) ); ?>"><?php esc_html_e( 'Editar', 'ygb-bot' ); ?></a>
								<form method="post" action="" class="ygb-inline-form" data-ygb-confirm>
									<?php wp_nonce_field( 'ygb_manage' ); ?>
									<input type="hidden" name="ygb_action" value="tema_delete" />
									<input type="hidden" name="id" value="<?php echo esc_attr( $t->id ); ?>" />
									<button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Eliminar', 'ygb-bot' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
