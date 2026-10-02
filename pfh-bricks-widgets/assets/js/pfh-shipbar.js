/**
 * The free-shipping bar in FunnelKit's cart drawer.
 *
 * FunnelKit redraws the drawer's contents whenever the cart changes, so the
 * bar is put back (and its figure worked out again) each time the drawer's
 * markup changes, from the subtotal FunnelKit itself prints. A message only:
 * it changes nothing about what shipping costs.
 */
( function () {
	'use strict';

	var cfg = window.pfhShipbar || {};
	var threshold = parseFloat( cfg.threshold ) || 0;

	if ( ! threshold || ! ( 'MutationObserver' in window ) ) {
		return;
	}

	/** "€ 1.053,82" → 1053.82, in the shop's own separators. */
	function parse( text ) {
		var raw = String( text || '' ).replace( /[^0-9.,-]/g, '' );

		if ( cfg.thousand ) {
			raw = raw.split( cfg.thousand ).join( '' );
		}

		if ( cfg.decimal && '.' !== cfg.decimal ) {
			raw = raw.split( cfg.decimal ).join( '.' );
		}

		var value = parseFloat( raw );

		return isNaN( value ) ? null : value;
	}

	function money( value ) {
		var decimals = typeof cfg.decimals === 'number' ? cfg.decimals : 2;
		var fixed = value.toFixed( decimals ).split( '.' );
		var whole = fixed[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, cfg.thousand || '.' );
		var number = fixed[ 1 ] ? whole + ( cfg.decimal || ',' ) + fixed[ 1 ] : whole;
		var symbol = cfg.symbol || '€';

		switch ( cfg.position ) {
			case 'left':
				return symbol + number;
			case 'right':
				return number + symbol;
			case 'right_space':
				return number + ' ' + symbol;
			default:
				return symbol + ' ' + number;
		}
	}

	function build() {
		var bar = document.createElement( 'div' );

		bar.className = 'pfh-shipbar';
		bar.setAttribute( 'data-pfh-shipbar', '' );
		bar.setAttribute( 'role', 'status' );
		bar.innerHTML =
			'<p class="pfh-shipbar__text"></p>' +
			'<div class="pfh-shipbar__track" aria-hidden="true"><div class="pfh-shipbar__fill"></div></div>' +
			( cfg.note ? '<p class="pfh-shipbar__note"></p>' : '' );

		if ( cfg.note ) {
			bar.querySelector( '.pfh-shipbar__note' ).textContent = cfg.note;
		}

		return bar;
	}

	function update( modal ) {
		var body = modal.querySelector( '.fkcart-slider-body' );
		var amount = modal.querySelector( '.fkcart-subtotal-wrap .fkcart-summary-amount' );
		var bar = modal.querySelector( '[data-pfh-shipbar]' );
		var subtotal = amount ? parse( amount.textContent ) : null;

		// An empty cart, or the drawer still loading: no bar.
		if ( ! body || null === subtotal || subtotal <= 0 || modal.querySelector( '.fkcart-preview-loading' ) ) {
			if ( bar ) {
				bar.parentNode.removeChild( bar );
			}

			return;
		}

		if ( ! bar ) {
			bar = build();
			body.insertBefore( bar, body.firstChild );
		}

		var left = Math.max( 0, threshold - subtotal );
		var done = left <= 0;
		var share = Math.min( 100, Math.round( ( subtotal / threshold ) * 100 ) );
		var text = done
			? cfg.done || ''
			: String( cfg.left || '' ).replace( '%amount%', '\u0000' );
		var line = bar.querySelector( '.pfh-shipbar__text' );
		var key = ( done ? 'done' : 'left' ) + ':' + share + ':' + left.toFixed( 2 );

		// Only touch the bar when the figure changed, so redrawing it does
		// not set the observer off again.
		if ( bar.getAttribute( 'data-pfh-shipbar-key' ) === key ) {
			return;
		}

		bar.setAttribute( 'data-pfh-shipbar-key', key );
		bar.classList.toggle( 'is-done', done );

		line.textContent = '';

		var parts = text.split( '\u0000' );

		line.appendChild( document.createTextNode( parts[ 0 ] ) );

		if ( parts.length > 1 ) {
			var strong = document.createElement( 'strong' );

			strong.textContent = money( left );
			line.appendChild( strong );
			line.appendChild( document.createTextNode( parts[ 1 ] ) );
		}

		bar.querySelector( '.pfh-shipbar__fill' ).style.width = share + '%';
	}

	function watch( modal ) {
		var queued = false;

		new window.MutationObserver( function () {
			if ( queued ) {
				return;
			}

			queued = true;

			// A timer rather than a frame: frames stop in a background tab,
			// and a drawer filled there should still have its bar.
			window.setTimeout( function () {
				queued = false;
				update( modal );
			}, 40 );
		} ).observe( modal, { childList: true, subtree: true, characterData: true } );

		update( modal );
	}

	function start() {
		var modal = document.getElementById( 'fkcart-modal' );

		if ( modal ) {
			watch( modal );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
