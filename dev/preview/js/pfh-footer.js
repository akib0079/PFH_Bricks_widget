/**
 * Products For Home – footer element.
 *
 * Turns the link columns into accordions on small screens and adds a
 * submitting state to the built-in newsletter form.
 */
( function () {
	'use strict';

	var MOBILE = 767;

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function bindAccordions( footer ) {
		qsa( '.pfh-footer__col', footer ).forEach( function ( column ) {
			var heading = column.querySelector( '.pfh-footer__heading' );
			var list = column.querySelector( '.pfh-footer__links' );

			if ( ! heading || ! list ) {
				return;
			}

			column.classList.add( 'is-accordion' );
			heading.setAttribute( 'role', 'button' );
			heading.setAttribute( 'tabindex', '0' );
			heading.setAttribute( 'aria-expanded', 'false' );

			function toggle() {
				if ( window.innerWidth > MOBILE ) {
					return;
				}

				var open = column.classList.toggle( 'is-open' );

				heading.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			}

			heading.addEventListener( 'click', toggle );

			heading.addEventListener( 'keydown', function ( event ) {
				if ( 'Enter' === event.key || ' ' === event.key ) {
					event.preventDefault();
					toggle();
				}
			} );
		} );
	}

	function bindNewsletter( footer ) {
		qsa( '[data-pfh-newsletter]', footer ).forEach( function ( form ) {
			form.addEventListener( 'submit', function () {
				form.classList.add( 'is-busy' );

				window.setTimeout( function () {
					form.classList.remove( 'is-busy' );
				}, 4000 );
			} );
		} );
	}

	function init() {
		qsa( '.pfh-footer' ).forEach( function ( footer ) {
			if ( footer.pfhFooter ) {
				return;
			}

			footer.pfhFooter = true;

			bindAccordions( footer );
			bindNewsletter( footer );
		} );
	}

	window.pfhFooterInit = init;

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
