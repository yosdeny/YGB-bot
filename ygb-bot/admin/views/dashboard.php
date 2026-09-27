<?php
/**
 * Vista: Dashboard con estadísticas y probador de bot.
 *
 * Variable disponible: $ygb_view_data (stats).
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

$stats = isset( $ygb_view_data['stats'] ) ? $ygb_view_data['stats'] : array();
?>
<div class="wrap ygb-wrap">
	<h1><?php esc_html_e( 'YGB Bot — Panel', 'ygb-bot' ); ?> <span class="ygb-version-badge">v<?php echo esc_html( YGB_BOT_VERSION ); ?></span></h1>

	<div class="ygb-cards">
		<div class="ygb-card"><span class="ygb-card-num"><?php echo esc_html( number_format_i18n( $stats['conversaciones'] ?? 0 ) ); ?></span><span class="ygb-card-label"><?php esc_html_e( 'Conversaciones', 'ygb-bot' ); ?></span></div>
		<div class="ygb-card"><span class="ygb-card-num"><?php echo esc_html( number_format_i18n( $stats['conversaciones_hoy'] ?? 0 ) ); ?></span><span class="ygb-card-label"><?php esc_html_e( 'Hoy', 'ygb-bot' ); ?></span></div>
		<div class="ygb-card"><span class="ygb-card-num"><?php echo esc_html( number_format_i18n( $stats['mensajes'] ?? 0 ) ); ?></span><span class="ygb-card-label"><?php esc_html_e( 'Mensajes', 'ygb-bot' ); ?></span></div>
		<div class="ygb-card"><span class="ygb-card-num"><?php echo esc_html( number_format_i18n( $stats['derivaciones'] ?? 0 ) ); ?></span><span class="ygb-card-label"><?php esc_html_e( 'Derivaciones', 'ygb-bot' ); ?></span></div>
		<div class="ygb-card"><span class="ygb-card-num"><?php echo esc_html( number_format_i18n( $stats['fallbacks'] ?? 0 ) ); ?></span><span class="ygb-card-label"><?php esc_html_e( 'Fallbacks', 'ygb-bot' ); ?></span></div>
		<div class="ygb-card"><span class="ygb-card-num"><?php echo esc_html( number_format_i18n( $stats['preguntas'] ?? 0 ) ); ?></span><span class="ygb-card-label"><?php esc_html_e( 'Preguntas', 'ygb-bot' ); ?></span></div>
	</div>

	<div class="ygb-columns">
		<div class="ygb-col">
			<h2><?php esc_html_e( 'Preguntas más consultadas', 'ygb-bot' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Pregunta', 'ygb-bot' ); ?></th><th><?php esc_html_e( 'Consultas', 'ygb-bot' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $stats['top_preguntas'] ) ) : ?>
					<tr><td colspan="2"><?php esc_html_e( 'Sin datos todavía.', 'ygb-bot' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $stats['top_preguntas'] as $row ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=ygb-bot-preguntas&editar=' . (int) $row->id ) ); ?>"><?php echo esc_html( $row->pregunta ); ?></a></td>
							<td><?php echo esc_html( number_format_i18n( (int) $row->contador ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Temas top', 'ygb-bot' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Tema', 'ygb-bot' ); ?></th><th><?php esc_html_e( 'Consultas', 'ygb-bot' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $stats['top_temas'] ) ) : ?>
					<tr><td colspan="2"><?php esc_html_e( 'Sin datos todavía.', 'ygb-bot' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $stats['top_temas'] as $row ) : ?>
						<tr><td><?php echo esc_html( $row->nombre ); ?></td><td><?php echo esc_html( number_format_i18n( (int) $row->consultas ) ); ?></td></tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="ygb-col">
			<h2><?php esc_html_e( 'Lo que la gente pregunta y el bot no sabe', 'ygb-bot' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Mensaje', 'ygb-bot' ); ?></th><th><?php esc_html_e( 'Veces', 'ygb-bot' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $stats['top_fallbacks'] ) ) : ?>
					<tr><td colspan="2"><?php esc_html_e( 'Genial: sin fallbacks registrados.', 'ygb-bot' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $stats['top_fallbacks'] as $row ) : ?>
						<tr>
							<td><?php echo esc_html( wp_trim_words( $row->mensaje_usuario, 12 ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (int) $row->veces ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Probar el bot', 'ygb-bot' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Usa el mismo motor de búsqueda que los visitantes. No se registra en los logs.', 'ygb-bot' ); ?></p>
			<div class="ygb-tester" id="ygb-tester" aria-live="polite"></div>
			<form id="ygb-tester-form" class="ygb-tester-form">
				<input type="text" id="ygb-tester-input" placeholder="<?php esc_attr_e( 'Escribe una prueba…', 'ygb-bot' ); ?>" maxlength="300" />
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Probar', 'ygb-bot' ); ?></button>
			</form>
		</div>
	</div>
</div>
