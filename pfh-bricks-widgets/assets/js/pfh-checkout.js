/**
 * Checkout reviews: one real review at a time, turning over quietly.
 *
 * Every review is already in the page; this only chooses which one shows.
 * It pauses while the pointer or keyboard focus is on it, while the tab is in
 * the background, and never turns over on its own for someone who has asked
 * for less motion — the dots still work for them.
 */
( function () {
	'use strict';

	function reduced() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function wire( root ) {
		var cards = Array.prototype.slice.call( root.querySelectorAll( '.pfh-ckrev__card' ) );
		var dots = Array.prototype.slice.call( root.querySelectorAll( '[data-pfh-ckrev-to]' ) );
		var every = parseInt( root.getAttribute( 'data-pfh-ckrev' ), 10 ) || 0;
		var current = 0;
		var timer = null;
		var held = false;

		if ( cards.length < 2 ) {
			return;
		}

		function show( index ) {
			current = ( index + cards.length ) % cards.length;

			cards.forEach( function ( card, i ) {
				var on = i === current;

				card.classList.toggle( 'is-active', on );

				if ( on ) {
					card.removeAttribute( 'aria-hidden' );
				} else {
					card.setAttribute( 'aria-hidden', 'true' );
				}
			} );

			dots.forEach( function ( dot, i ) {
				var on = i === current;

				dot.classList.toggle( 'is-active', on );

				if ( on ) {
					dot.setAttribute( 'aria-current', 'true' );
				} else {
					dot.removeAttribute( 'aria-current' );
				}
			} );
		}

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function start() {
			stop();

			if ( every > 0 && ! held && ! reduced() && ! document.hidden ) {
				timer = window.setInterval( function () {
					show( current + 1 );
				}, every );
			}
		}

		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				show( parseInt( dot.getAttribute( 'data-pfh-ckrev-to' ), 10 ) || 0 );
				start();
			} );
		} );

		[ 'mouseenter', 'focusin' ].forEach( function ( type ) {
			root.addEventListener( type, function () {
				held = true;
				stop();
			} );
		} );

		[ 'mouseleave', 'focusout' ].forEach( function ( type ) {
			root.addEventListener( type, function () {
				held = false;
				start();
			} );
		} );

		document.addEventListener( 'visibilitychange', start );

		start();
	}

	function boot() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-pfh-ckrev]' ), function ( root ) {
			if ( ! root.pfhCkrev ) {
				root.pfhCkrev = true;
				wire( root );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	document.addEventListener( 'bricks/ajax/end', boot );
}() );
