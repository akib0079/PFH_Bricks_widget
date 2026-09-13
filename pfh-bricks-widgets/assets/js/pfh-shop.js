/**
 * Shop template parts: the read-more clamp and the FAQ accordion.
 *
 * Both degrade completely: the description is clamped by CSS and the FAQ is
 * <details>/<summary>, so with scripting off the page is still readable and
 * operable. This only adds the parts CSS cannot do — measuring whether the
 * text actually overflows, and closing sibling answers.
 */
( function () {
	'use strict';

	/* ------------------------------------------------------------------
	 * Read more
	 * --------------------------------------------------------------- */

	function clamp( wrap ) {
		var body = wrap.querySelector( '[data-pfh-clamp-body]' );
		var button = wrap.querySelector( '[data-pfh-clamp-toggle]' );
		var root = wrap.closest( '.pfh-shopdesc' );

		if ( ! body || ! button || ! root ) {
			return;
		}

		/**
		 * Show the button only when the copy is genuinely taller than its
		 * clamp. scrollHeight beats counting lines: it accounts for the real
		 * font, the real width and any markup inside the text.
		 */
		function measure() {
			if ( root.classList.contains( 'is-open' ) ) {
				return;
			}

			var overflows = body.scrollHeight - body.clientHeight > 2;

			button.hidden = ! overflows;
			root.classList.toggle( 'is-clamped', true );
		}

		button.addEventListener( 'click', function () {
			var open = root.classList.toggle( 'is-open' );

			button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			button.textContent = open
				? ( button.getAttribute( 'data-less' ) || button.textContent )
				: ( button.getAttribute( 'data-more' ) || button.textContent );

			if ( ! open ) {
				var top = root.getBoundingClientRect().top + window.pageYOffset - 100;
				window.scrollTo( { top: Math.max( 0, top ), behavior: 'smooth' } );
			}
		} );

		measure();

		// Webfonts land after first paint and change the line count.
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( measure ).catch( function () {} );
		}

		if ( 'ResizeObserver' in window ) {
			new ResizeObserver( measure ).observe( body );
		} else {
			window.addEventListener( 'resize', measure );
		}
	}

	/* ------------------------------------------------------------------
	 * FAQ
	 * --------------------------------------------------------------- */

	/*
	 * <details> has no in-between: it snaps open and snaps shut, and with one
	 * answer open at a time the row above jumps as the other slams closed.
	 * So the summary's own toggle is taken over and the row's height is
	 * animated between the two states, which is the only part of this that
	 * needs scripting — with JavaScript off the element still opens and
	 * closes on its own.
	 */
	var DURATION = 260;
	var EASING   = 'cubic-bezier(.22,.61,.36,1)';

	function canAnimate( item ) {
		return typeof item.animate === 'function' &&
			! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	function heights( item ) {
		var summary = item.querySelector( '.pfh-faq__q' );
		var body    = item.querySelector( '.pfh-faq__a' );

		return {
			shut: summary ? summary.offsetHeight : 0,
			open: ( summary ? summary.offsetHeight : 0 ) + ( body ? body.offsetHeight : 0 )
		};
	}

	function slide( item, opening ) {
		// A second click mid-flight must not fight the first.
		if ( item.pfhAnim ) {
			item.pfhAnim.cancel();
			item.pfhAnim = null;
		}

		if ( ! canAnimate( item ) ) {
			item.open = opening;

			return;
		}

		var from = item.offsetHeight;

		// The summary's padding differs between the two states, so the target
		// can only be measured with the element in the state it is going to.
		item.open = true;
		var target = heights( item );
		var to     = opening ? target.open : target.shut;

		item.style.overflow = 'hidden';

		var anim = item.animate(
			{ height: [ from + 'px', to + 'px' ] },
			{ duration: DURATION, easing: EASING }
		);

		item.pfhAnim = anim;

		anim.onfinish = function () {
			item.pfhAnim = null;
			item.style.overflow = '';
			item.style.height = '';
			item.open = opening;
		};

		anim.oncancel = function () {
			item.style.overflow = '';
			item.style.height = '';
		};
	}

	function faq( root ) {
		var single = 'true' === root.getAttribute( 'data-pfh-faq-single' );
		var items  = root.querySelectorAll( '.pfh-faq__item' );

		Array.prototype.forEach.call( items, function ( item ) {
			var summary = item.querySelector( '.pfh-faq__q' );

			if ( ! summary ) {
				return;
			}

			summary.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				var opening = ! item.open;

				if ( opening && single ) {
					Array.prototype.forEach.call( items, function ( other ) {
						if ( other !== item && other.open ) {
							slide( other, false );
						}
					} );
				}

				slide( item, opening );
			} );
		} );
	}

	function start() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-pfh-clamp]' ), clamp );
		Array.prototype.forEach.call( document.querySelectorAll( '[data-pfh-faq]' ), faq );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
