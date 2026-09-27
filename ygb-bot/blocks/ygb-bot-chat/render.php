<?php
/**
 * Render del bloque YGB Bot (server-side render para block.json).
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

$attributes = isset( $attributes ) && is_array( $attributes ) ? $attributes : array(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$theme      = isset( $attributes['theme'] ) ? sanitize_key( $attributes['theme'] ) : 'light';

if ( class_exists( 'Class_Ygb_Shortcode' ) ) {
	echo Class_Ygb_Shortcode::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML ya escapado internamente.
		array(
			'theme'  => $theme,
			'inline' => 1,
		)
	);
}
