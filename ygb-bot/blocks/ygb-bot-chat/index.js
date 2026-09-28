/**
 * Editor del bloque YGB Bot (React mínimo vía wp.blocks / wp.element).
 * Sin build: usa las globals de WordPress.
 */
( function ( blocks, element, components, blockEditor ) {
	'use strict';

	var el = element.createElement;
	var __ = window.wp.i18n.__;

	blocks.registerBlockType( 'ygb-bot/bot', {
		edit: function ( props ) {
			return el(
				'div',
				{ className: 'ygb-block-placeholder' },
				el(
					'p',
					{ style: { fontSize: '34px', margin: '0' } },
					'🖥️'
				),
				el(
					'strong',
					null,
					__( 'YGB Bot — chat en línea', 'ygb-bot' )
				),
				el(
					'p',
					{ style: { color: '#787c82', maxWidth: '360px', margin: '4px auto 12px' } },
					__(
						'En la vista pública se mostrará el widget del bot con los ajustes actuales (color, avatar, textos y derivación).',
						'ygb-bot'
					)
				),
				el(
					components.Disabled,
					null,
					el(
						'div',
						{
							style: {
								background: '#f6f7f7',
								border: '1px solid #dcdcde',
								borderRadius: '10px',
								maxWidth: '320px',
								margin: '0 auto'
							}
						},
						el(
							'div',
							{
								style: {
									background: '#2271b1',
									color: '#fff',
									padding: '10px 14px',
									borderRadius: '10px 10px 0 0'
								}
							},
							'🖥️ ' + __( 'YGB Bot', 'ygb-bot' )
						),
						el(
							'div',
							{ style: { padding: '14px', minHeight: '70px' } },
							el(
								'div',
								{
									style: {
										background: '#fff',
										border: '1px solid #dcdcde',
										borderRadius: '8px',
										padding: '8px 10px'
									}
								},
								__( '¡Hola! 👋 Escribe tu pregunta.', 'ygb-bot' )
							)
						)
					)
				)
			);
		},
		save: function () {
			return null; // Render dinámico en PHP.
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor );
