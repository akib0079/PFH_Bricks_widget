/**
 * Product tabs.
 *
 * Built on the real tab pattern rather than hidden radio inputs: the buttons
 * carry role="tab", only the open panel is in the document's flow, and the
 * arrow keys move between tabs the way they do in every other tablist. That is
 * what makes it usable by keyboard, and it costs nothing extra.
 */
( function () {
	'use strict';

	function tabs( root ) {
		var strip = root.querySelector( '.pfh-tabs__nav' );

		if ( ! strip ) {
			return;
		}

		var buttons = Array.prototype.slice.call( strip.querySelectorAll( '[data-pfh-tab]' ) );
		var panels = Array.prototype.slice.call( root.querySelectorAll( '[data-pfh-panel]' ) );

		if ( buttons.length < 2 ) {
			return;
		}

		function show( index, focus ) {
			var at = ( index + buttons.length ) % buttons.length;

			buttons.forEach( function ( button, i ) {
				var on = i === at;

				button.classList.toggle( 'is-active', on );
				button.setAttribute( 'aria-selected', on ? 'true' : 'false' );

				// Only the open tab is in the tab order; the arrows reach the
				// others, which is how a tablist is meant to behave.
				button.tabIndex = on ? 0 : -1;
			} );

			panels.forEach( function ( panel, i ) {
				var on = i === at;

				panel.classList.toggle( 'is-active', on );

				if ( on ) {
					panel.removeAttribute( 'hidden' );
				} else {
					panel.setAttribute( 'hidden', '' );
				}
			} );

			if ( focus ) {
				buttons[ at ].focus();
			}

			/*
			 * Keep the chosen tab in view: on a narrow screen the strip
			 * scrolls, and the tab just tapped can be the one off the edge.
			 * Only ever called from a click or a key, so this cannot move the
			 * page on load.
			 */
			if ( buttons[ at ].scrollIntoView ) {
				buttons[ at ].scrollIntoView( { block: 'nearest', inline: 'nearest' } );
			}
		}

		buttons.forEach( function ( button, i ) {
			button.addEventListener( 'click', function () {
				show( i, false );
			} );

			button.addEventListener( 'keydown', function ( event ) {
				var keys = {
					ArrowRight: i + 1,
					ArrowLeft: i - 1,
					Home: 0,
					End: buttons.length - 1
				};

				if ( ! ( event.key in keys ) ) {
					return;
				}

				event.preventDefault();
				show( keys[ event.key ], true );
			} );
		} );
	}

	function start() {
		Array.prototype.forEach.call( document.querySelectorAll( '.pfh-tabs' ), function ( root ) {
			if ( root.hasAttribute( 'data-pfh-ready' ) ) {
				return;
			}

			root.setAttribute( 'data-pfh-ready', '' );
			tabs( root );
		} );
	}

	window.pfhProductTabsInit = start;

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
