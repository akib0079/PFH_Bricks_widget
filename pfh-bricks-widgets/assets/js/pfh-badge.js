/**
 * Sticky WebwinkelKeur badge.
 *
 * Open state is remembered per visitor, so closing it once does not mean
 * fighting it on every page. Phones always start collapsed.
 */
( function () {
	'use strict';

	var KEY = 'pfh_badge_open';

	function stored() {
		try {
			return window.localStorage.getItem( KEY );
		} catch ( e ) {
			return null;
		}
	}

	function remember( open ) {
		try {
			window.localStorage.setItem( KEY, open ? '1' : '0' );
		} catch ( e ) {}
	}

	function setup( badge ) {
		var panel = badge.querySelector( '.pfh-bdg__panel' );
		var tab = badge.querySelector( '.pfh-bdg__tab' );

		if ( ! panel || ! tab ) {
			return;
		}

		function set( open ) {
			panel.hidden = ! open;
			badge.classList.toggle( 'is-open', open );
			tab.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}

		function toggle() {
			var next = panel.hidden;
			set( next );
			remember( next );
		}

		Array.prototype.forEach.call(
			badge.querySelectorAll( '[data-pfh-badge-toggle]' ),
			function ( node ) {
				node.addEventListener( 'click', toggle );
			}
		);

		badge.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && ! panel.hidden ) {
				set( false );
				remember( false );
				tab.focus( { preventScroll: true } );
			}
		} );

		var saved = stored();
		var narrow = window.matchMedia( '(max-width: 782px)' ).matches;

		if ( null !== saved ) {
			set( '1' === saved && ! narrow );
		} else {
			set( '1' === badge.getAttribute( 'data-open-default' ) && ! narrow );
		}
	}

	function start() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-pfh-badge]' ), setup );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
