/**
 * YGB Bot — JS del panel de administración.
 * JS puro, sin dependencia de jQuery. Toasts, confirmaciones,
 * selector de medios, check-all y chat de prueba.
 */
( function () {
	'use strict';

	var cfg = window.ygbAdmin || {};

	/* ---------- Toasts ---------- */
	function toast( msg, isError ) {
		var wrap = document.getElementById( 'ygb-toasts' );
		if ( ! wrap ) {
			wrap = document.createElement( 'div' );
			wrap.id = 'ygb-toasts';
			wrap.setAttribute( 'aria-live', 'polite' );
			document.body.appendChild( wrap );
		}
		var el = document.createElement( 'div' );
		el.className = 'ygb-toast' + ( isError ? ' ygb-error' : '' );
		el.textContent = msg;
		wrap.appendChild( el );
		window.setTimeout( function () { el.classList.add( 'ygb-show' ); }, 10 );
		window.setTimeout( function () {
			el.classList.remove( 'ygb-show' );
			window.setTimeout( function () { el.remove(); }, 300 );
		}, 3500 );
	}

	/* ---------- Confirmaciones (data-ygb-confirm) ---------- */
	document.addEventListener( 'submit', function ( ev ) {
		var form = ev.target.closest( '[data-ygb-confirm]' );
		if ( form && ! window.confirm( cfg.i18n ? cfg.i18n.confirm : '¿Seguro?' ) ) {
			ev.preventDefault();
		}
	} );
	document.addEventListener( 'click', function ( ev ) {
		var btn = ev.target.closest( '[data-ygb-confirm]' );
		if ( btn && ! window.confirm( btn.getAttribute( 'data-ygb-confirm' ) ) ) {
			ev.preventDefault();
			ev.stopPropagation();
		}
	} );

	/* Aviso "guardado" tras submit de Settings API (page reload). */
	document.addEventListener( 'submit', function ( ev ) {
		if ( ev.target && ev.target.action && ev.target.action.indexOf( 'options.php' ) !== -1 ) {
			try { window.sessionStorage.setItem( 'ygb_saved', '1' ); } catch ( e ) {}
		}
	} );
	if ( window.sessionStorage ) {
		try {
			if ( window.sessionStorage.getItem( 'ygb_saved' ) ) {
				window.sessionStorage.removeItem( 'ygb_saved' );
				toast( cfg.i18n ? cfg.i18n.saved : 'Guardado.' );
			}
		} catch ( e ) {}
	}

	/* ---------- Check all (preguntas) ---------- */
	var checkAll = document.getElementById( 'ygb-check-all' );
	if ( checkAll ) {
		checkAll.addEventListener( 'change', function () {
			Array.prototype.forEach.call( document.querySelectorAll( '.ygb-check' ), function ( cb ) {
				cb.checked = checkAll.checked;
			} );
		} );
	}

	/* ---------- Selector de medios (wp.media) ---------- */
	document.addEventListener( 'click', function ( ev ) {
		var btn = ev.target.closest( '.ygb-media-picker' );
		if ( ! btn || typeof window.wp === 'undefined' || ! window.wp.media ) {
			return;
		}
		ev.preventDefault();
		var target = btn.closest( 'td' ).querySelector( '.ygb-media-input' );
		var frame = window.wp.media( { frame: 'select', multiple: false, library: { type: '' } } );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			var url = att.url || ( att.sizes && att.sizes.full ? att.sizes.full.url : '' );
			if ( target && url ) {
				target.value = url;
				toast( cfg.i18n ? cfg.i18n.saved : 'OK' );
			}
		} );
		frame.open();
	} );

	/* ---------- Avatar: sincronizar emoji / URL con el campo oculto ---------- */
		var avatarHidden = document.getElementById( 'ygb-avatar' );
		var avatarEmoji  = document.getElementById( 'ygb-avatar-emoji' );
		var avatarUrl    = document.getElementById( 'ygb-avatar-url' );

		function isImageUrl( v ) {
			return /^https?:\/\//i.test( String( v || '' ).trim() );
		}

		function syncAvatar() {
			if ( ! avatarHidden ) { return; }
			var type = document.querySelector( 'input[name$="[avatar_type]"]:checked' );
			var mode = type ? type.value : ( isImageUrl( avatarUrl && avatarUrl.value ) ? 'image' : 'emoji' );
			var val  = 'image' === mode ? ( avatarUrl ? avatarUrl.value.trim() : '' ) : ( avatarEmoji ? avatarEmoji.value.trim() : '' );
			avatarHidden.value = val;
			updateAvatarPreview( val );
		}

		function updateAvatarPreview( val ) {
			// Miniatura bajo el campo de URL (solo la del selector, id único).
			var thumb = document.getElementById( 'ygb-avatar-thumb' );
			if ( thumb ) {
				if ( isImageUrl( val ) ) {
					thumb.src = val;
					thumb.removeAttribute( 'hidden' );
				} else {
					thumb.removeAttribute( 'src' );
					thumb.setAttribute( 'hidden', 'hidden' );
				}
			}
			// Vista previa de la tarjeta.
			var prev = document.querySelector( '.ygb-preview-avatar' );
			if ( prev ) {
				if ( isImageUrl( val ) ) {
					if ( 'IMG' !== prev.tagName ) {
						var img = document.createElement( 'img' );
						img.className = 'ygb-preview-avatar ygb-preview-avatar-img';
						img.alt = '';
						prev.parentNode.replaceChild( img, prev );
						prev = img;
					}
					prev.src = val;
				} else {
					if ( 'IMG' === prev.tagName ) {
						var span = document.createElement( 'span' );
						span.className = 'ygb-preview-avatar';
						prev.parentNode.replaceChild( span, prev );
						prev = span;
					}
					prev.textContent = String( val || '' ).slice( 0, 2 );
				}
			}
		}

		if ( avatarHidden ) {
			Array.prototype.forEach.call( document.querySelectorAll( 'input[name$="[avatar_type]"]' ), function ( radio ) {
				radio.addEventListener( 'change', function () {
					var emojiBox = document.querySelector( '.ygb-avatar-emoji' );
					var imageBox = document.querySelector( '.ygb-avatar-image' );
					var isImage  = 'image' === this.value;
					if ( emojiBox ) { emojiBox.hidden = isImage; }
					if ( imageBox ) { imageBox.hidden = ! isImage; }
					syncAvatar();
				} );
			} );
			[ avatarEmoji, avatarUrl ].forEach( function ( el ) {
				if ( el ) {
					el.addEventListener( 'input', syncAvatar );
				}
			} );
			// Asegurar sincronía también al enviar (por si el evento input no ocurrió).
			var avatarForm = avatarHidden.closest( 'form' );
			if ( avatarForm ) {
				avatarForm.addEventListener( 'submit', syncAvatar );
			}
			var clearBtn = document.querySelector( '.ygb-avatar-clear' );
			if ( clearBtn ) {
				clearBtn.addEventListener( 'click', function () {
					if ( avatarUrl ) { avatarUrl.value = ''; }
					syncAvatar();
				} );
			}
		}

		/* ---------- Chat de prueba (Apariencia / Dashboard) ---------- */
	var testForm = document.getElementById( 'ygb-test-form' );
	var testBox  = document.getElementById( 'ygb-test-chat' );
	if ( testForm && testBox ) {
		function row( cls, html ) {
			var d = document.createElement( 'div' );
			d.className = 'ygb-t-row ' + cls;
			d.innerHTML = html; // html ya saneado/escapado abajo.
			testBox.appendChild( d );
			testBox.scrollTop = testBox.scrollHeight;
			return d;
		}
		function esc( s ) {
			var div = document.createElement( 'div' );
			div.appendChild( document.createTextNode( String( s || '' ) ) );
			return div.innerHTML;
		}
		testForm.addEventListener( 'submit', function ( ev ) {
			ev.preventDefault();
			var input = document.getElementById( 'ygb-test-input' );
			var msg   = input.value.trim();
			if ( ! msg ) { return; }
			input.value = '';
			row( 'ygb-t-user', esc( msg ) );
			var typing = row( 'ygb-t-bot', '<em>' + esc( cfg.i18n ? cfg.i18n.testing : '…' ) + '</em>' );

			var body = new URLSearchParams();
			body.append( 'action', 'ygb_test_chat' );
			body.append( 'nonce', cfg.nonce );
			body.append( 'message', msg );

			window.fetch( cfg.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					typing.remove();
					var d = res && res.data ? res.data : {};
					if ( d.matched ) {
						row( 'ygb-t-bot',
							'<span class="ygb-t-score">' + esc( Math.round( d.score ) ) + '%</span>' +
							'<strong>' + esc( d.pregunta ) + '</strong>' +
							'<div>' + ( d.answer || '' ) + '</div>' );
					} else {
						var sug = ( d.suggest || [] ).map( function ( s ) {
							return '<li>' + esc( s.pregunta ) + '</li>';
						} ).join( '' );
						row( 'ygb-t-bot ygb-t-fallback',
							esc( d.message || 'Sin coincidencia.' ) +
							( sug ? '<ul>' + sug + '</ul>' : '' ) );
					}
				} )
				.catch( function () {
					typing.remove();
					row( 'ygb-t-bot ygb-t-fallback', esc( cfg.i18n ? cfg.i18n.error : 'Error' ) );
				} );
		} );
	}
} )();
