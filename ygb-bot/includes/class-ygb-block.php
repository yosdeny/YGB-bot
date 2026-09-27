<?php
/**
 * Registro del bloque de Gutenberg "YGB Bot" (block.json + render dinámico).
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_Block
 */
class Class_Ygb_Block {

	/**
	 * Registra el bloque.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
	}

	/**
	 * Callback de registro (init).
	 *
	 * @return void
	 */
	public static function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return; // WP < 5.8.
		}

		register_block_type(
			'ygb-bot/bot',
			array(
				'api_version'     => 2,
				'attributes'      => array(
					'theme' => array(
						'type'    => 'string',
						'default' => 'light',
					),
				),
				'render_callback' => array( __CLASS__, 'render' ),
				'supports'        => array(
					'html' => false,
				),
			)
		);
	}

	/**
	 * Render dinámico del bloque.
	 *
	 * @param array $attributes Atributos del bloque.
	 * @return string
	 */
	public static function render( $attributes = array() ) {
		$theme = isset( $attributes['theme'] ) ? $attributes['theme'] : 'light';
		return Class_Ygb_Shortcode::render( array( 'theme' => $theme, 'inline' => 1 ) );
	}
}
