<?php
/**
 * Partial del widget de chat (frontend).
 *
 * Variables disponibles: $ygb_inline (bool), $ygb_theme (light|dark).
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

$ygb_settings = array_merge(
	Class_Ygb_DB::get_settings(),
	array( 'consentText' => __( 'Al continuar aceptas que esta conversación pueda guardarse para mejorar el servicio.', 'ygb-bot' ) )
); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$ygb_inline   = isset( $ygb_inline ) ? (bool) $ygb_inline : false; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$ygb_theme    = isset( $ygb_theme ) && 'dark' === $ygb_theme ? 'dark' : 'light'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// El desplegable "Tamaño" (small|medium|large) solo afecta al ancho de la ventana,
// nunca al botón flotante (ese se controla con "Tamaño del icono (px)").
$ygb_size  = in_array( $ygb_settings['size'], array( 'small', 'medium', 'large' ), true ) ? $ygb_settings['size'] : 'medium'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="ygb-widget ygb-<?php echo esc_attr( $ygb_theme ); ?> ygb-size-<?php echo esc_attr( $ygb_size ); ?> <?php echo $ygb_inline ? 'ygb-inline' : 'ygb-floating ygb-pos-' . esc_attr( $ygb_settings['position'] ); ?>"
	data-ygb-instance="<?php echo $ygb_inline ? 'inline' : 'floating'; ?>"
	role="region"
	aria-label="<?php esc_attr_e( 'Chat de ayuda', 'ygb-bot' ); ?>">

	<?php if ( ! $ygb_inline ) : ?>
		<?php $ygb_bubble_logo = preg_match( '#^https?://#i', (string) $ygb_settings['bubble_logo'] ) ? $ygb_settings['bubble_logo'] : ''; ?>
		<button type="button" class="ygb-bubble" aria-label="<?php esc_attr_e( 'Abrir chat', 'ygb-bot' ); ?>" aria-expanded="false">
			<?php if ( $ygb_bubble_logo ) : ?>
				<img class="ygb-bubble-logo" src="<?php echo esc_url( $ygb_bubble_logo ); ?>" alt="" aria-hidden="true" />
			<?php else : ?>
				<span class="ygb-bubble-icon" aria-hidden="true"><?php echo esc_html( $ygb_settings['bubble_icon'] ); ?></span>
			<?php endif; ?>
		</button>
	<?php endif; ?>

	<div class="ygb-window" role="dialog" aria-modal="false" aria-label="<?php esc_attr_e( 'Ventana de chat', 'ygb-bot' ); ?>" hidden>

		<header class="ygb-header">
			<span class="ygb-avatar" aria-hidden="true"><?php echo esc_html( $ygb_settings['avatar'] ); ?></span>
			<span class="ygb-header-texts">
				<strong class="ygb-title"><?php echo esc_html( $ygb_settings['bot_name'] ); ?></strong>
				<small class="ygb-status"><span class="ygb-dot" aria-hidden="true"></span><?php esc_html_e( 'En línea', 'ygb-bot' ); ?></small>
			</span>
			<button type="button" class="ygb-minimize" aria-label="<?php esc_attr_e( 'Minimizar chat', 'ygb-bot' ); ?>">−</button>
		</header>

		<div class="ygb-consent" hidden>
			<p>
				<?php echo esc_html( $ygb_settings['consentText'] ); ?>
				<?php if ( ! empty( $ygb_settings['privacy_url'] ) ) : ?>
					<a href="<?php echo esc_url( $ygb_settings['privacy_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Política de privacidad', 'ygb-bot' ); ?></a>
				<?php endif; ?>
			</p>
			<div class="ygb-consent-actions">
				<button type="button" class="ygb-btn ygb-btn-primary ygb-consent-ok"><?php esc_html_e( 'Aceptar', 'ygb-bot' ); ?></button>
				<button type="button" class="ygb-btn ygb-consent-no"><?php esc_html_e( 'No, gracias', 'ygb-bot' ); ?></button>
			</div>
		</div>

		<div class="ygb-messages" id="ygb-messages" role="log" aria-live="polite" tabindex="0"></div>

		<div class="ygb-quick" hidden>
			<div class="ygb-quick-head">
				<span class="ygb-quick-title"><?php esc_html_e( 'Menú inicial de temas y FAQ sugeridas', 'ygb-bot' ); ?></span>
				<button type="button" class="ygb-quick-toggle" aria-expanded="true" aria-controls="ygb-quick-body"
						title="<?php esc_attr_e( 'Plegar/expandir menú inicial', 'ygb-bot' ); ?>">
						<span class="ygb-quick-indicator" aria-hidden="true">&#9662;</span>
						<span class="screen-reader-text ygb-sr-only"><?php esc_html_e( 'Plegar/expandir menú inicial', 'ygb-bot' ); ?></span>
				</button>
			</div>
			<div class="ygb-quick-body" id="ygb-quick-body"></div>
		</div>

		<footer class="ygb-footer">
			<?php if ( ! empty( $ygb_settings['show_support_btn'] ) ) : ?>
			<div class="ygb-support-persist-wrap">
				<span class="ygb-support-persist-label">💬 <?php esc_html_e( 'Hablar con soporte', 'ygb-bot' ); ?></span>
				<div class="ygb-support-persist-row">
					<button type="button" class="ygb-support-persist ygb-support-opt" data-channel="email" title="<?php esc_attr_e( 'Enviar un correo al equipo de soporte', 'ygb-bot' ); ?>">
						✉️ <?php esc_html_e( 'Correo', 'ygb-bot' ); ?>
					</button>
					<button type="button" class="ygb-support-persist ygb-support-opt" data-channel="whatsapp" title="<?php esc_attr_e( 'Hablar por WhatsApp', 'ygb-bot' ); ?>">
						📱 <?php esc_html_e( 'WhatsApp', 'ygb-bot' ); ?>
					</button>
				</div>
			</div>
			<?php endif; ?>
			<form class="ygb-input-row">
				<label class="screen-reader-text ygb-sr-only" for="ygb-input-msg"><?php esc_html_e( 'Escribe tu mensaje', 'ygb-bot' ); ?></label>
				<input type="text" id="ygb-input-msg" class="ygb-input" autocomplete="off"
					placeholder="<?php echo esc_attr( $ygb_settings['placeholder'] ); ?>" maxlength="500" />
				<button type="submit" class="ygb-send" aria-label="<?php esc_attr_e( 'Enviar mensaje', 'ygb-bot' ); ?>">➤</button>
			</form>
		</footer>
	</div>
</div>
