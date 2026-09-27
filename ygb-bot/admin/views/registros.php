<?php
/**
 * Vista: Registros (conversaciones, derivaciones, fallbacks) con paginación, export CSV y borrado.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Solo lectura de filtros de vista.
$tipo = isset( $_GET['tipo'] ) && in_array( sanitize_key( wp_unslash( $_GET['tipo'] ) ), array( 'conversaciones', 'derivaciones', 'fallbacks' ), true )
	? sanitize_key( wp_unslash( $_GET['tipo'] ) )
	: 'conversaciones';
$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
// phpcs:enable

$per     = 25;
$data    = Class_Ygb_DB::get_registros( $tipo, $paged, $per );
$pages   = max( 1, (int) ceil( $data['total'] / $per ) );
$base    = admin_url( 'admin.php?page=ygb-bot-registros' );
$canales = array(
	'email'      => __( 'Correo electrónico', 'ygb-bot' ),
	'whatsapp'   => 'WhatsApp',
	'contacto'   => __( 'Página de contacto', 'ygb-bot' ),
	'formulario' => __( 'Formulario interno', 'ygb-bot' ),
);
?>
<div class="wrap ygb-wrap">
	<h1><?php esc_html_e( 'Registros', 'ygb-bot' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<?php foreach ( array( 'conversaciones' => __( 'Conversaciones (mensajes)', 'ygb-bot' ), 'derivaciones' => __( 'Derivaciones', 'ygb-bot' ), 'fallbacks' => __( 'Fallbacks (sin respuesta)', 'ygb-bot' ) ) as $key => $label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tipo', $key, $base ) ); ?>" class="nav-tab <?php echo $tipo === $key ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<p>
		<form method="post" action="" class="ygb-inline-form">
			<?php wp_nonce_field( 'ygb_manage' ); ?>
			<input type="hidden" name="ygb_action" value="export_logs" />
			<input type="hidden" name="tipo" value="<?php echo esc_attr( $tipo ); ?>" />
			<button type="submit" class="button"><?php esc_html_e( '⬇ Exportar a CSV', 'ygb-bot' ); ?></button>
		</form>
	</p>

	<table class="widefat striped">
		<thead>
			<tr>
				<?php if ( 'conversaciones' === $tipo ) : ?>
					<th><?php esc_html_e( 'ID conv.', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Emisor', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Mensaje', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Pregunta', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Score', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Fecha', 'ygb-bot' ); ?></th>
				<?php elseif ( 'derivaciones' === $tipo ) : ?>
					<th><?php esc_html_e( 'ID', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Conv.', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Canal', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Mensaje del usuario', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Estado', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Fecha', 'ygb-bot' ); ?></th>
				<?php else : ?>
					<th><?php esc_html_e( 'ID', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Mensaje sin respuesta', 'ygb-bot' ); ?></th>
					<th><?php esc_html_e( 'Fecha', 'ygb-bot' ); ?></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $data['items'] ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'Sin registros todavía.', 'ygb-bot' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $data['items'] as $r ) : ?>
				<tr>
					<?php if ( 'conversaciones' === $tipo ) : ?>
						<td><?php echo esc_html( (int) $r->conversacion_id ); ?></td>
						<td><?php echo 'user' === $r->emisor ? esc_html__( 'Usuario', 'ygb-bot' ) : esc_html__( 'Bot', 'ygb-bot' ); ?></td>
						<td><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $r->mensaje ), 20, '…' ) ); ?></td>
						<td><?php echo $r->pregunta_id ? esc_html( (int) $r->pregunta_id ) : '—'; ?></td>
						<td><?php echo esc_html( round( (float) $r->score ) . '%' ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $r->fecha ) ) ); ?></td>
					<?php elseif ( 'derivaciones' === $tipo ) : ?>
						<td><?php echo esc_html( (int) $r->id ); ?></td>
						<td><?php echo esc_html( (int) $r->conversacion_id ); ?></td>
						<td><?php echo esc_html( isset( $canales[ $r->canal ] ) ? $canales[ $r->canal ] : $r->canal ); ?></td>
						<td><?php echo esc_html( wp_trim_words( $r->mensaje_usuario, 20, '…' ) ); ?></td>
						<td><?php echo esc_html( $r->estado ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $r->fecha ) ) ); ?></td>
					<?php else : ?>
						<td><?php echo esc_html( (int) $r->id ); ?></td>
						<td><?php echo esc_html( $r->mensaje_usuario ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $r->fecha ) ) ); ?></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav"><div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( array( 'tipo' => $tipo, 'paged' => '%#%' ), $base ),
						'format'    => '&paged=%#%',
						'current'   => $paged,
						'total'     => $pages,
						'prev_text' => '«',
						'next_text' => '»',
					)
				)
			);
			?>
		</div></div>
	<?php endif; ?>
	<p class="description"><?php echo esc_html( sprintf( /* translators: %s: total de registros. */ __( 'Total de registros: %s', 'ygb-bot' ), number_format_i18n( $data['total'] ) ) ); ?></p>

	<hr />
	<h2><?php esc_html_e( 'Borrar registros (RGPD)', 'ygb-bot' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Acción irreversible. Exporta antes si necesitas conservar una copia.', 'ygb-bot' ); ?></p>
	<?php
	$grupos = array(
		'mensajes'       => __( 'Mensajes / conversaciones', 'ygb-bot' ),
		'conversaciones' => __( 'Cabeceras de conversaciones', 'ygb-bot' ),
		'derivaciones'   => __( 'Derivaciones', 'ygb-bot' ),
		'fallbacks'      => __( 'Fallbacks', 'ygb-bot' ),
	);
	foreach ( $grupos as $que => $label ) :
		?>
		<form method="post" action="" class="ygb-inline-form" data-ygb-confirm>
			<?php wp_nonce_field( 'ygb_manage' ); ?>
			<input type="hidden" name="ygb_action" value="clear_logs" />
			<input type="hidden" name="que" value="<?php echo esc_attr( $que ); ?>" />
			<button type="submit" class="button button-link-delete"><?php echo esc_html( sprintf( /* translators: %s: tipo de registro. */ __( 'Borrar %s', 'ygb-bot' ), $label ) ); ?></button>
		</form>
	<?php endforeach; ?>
</div>
