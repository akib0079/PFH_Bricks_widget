/**
 * The wishlist hearts.
 *
 * Every heart is a button with the product's id. The list lives in a cookie
 * for everyone — so a cached page still shows a visitor their own hearts —
 * and, for a signed-in customer, on their account as well. Clicks are caught
 * on the document, so hearts drawn later (a filtered grid, a slider that
 * loads its cards) work without being bound.
 */
( function () {
	'use strict';

	var cfg = window.pfhWishlist || {};
	var name = cfg.cookie || 'pfh_wishlist';
	var max = cfg.max || 60;

	function read() {
		var match = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
		var ids = match ? decodeURIComponent( match[ 1 ] ).split( '.' ) : [];

		if ( Array.isArray( cfg.ids ) ) {
			ids = cfg.ids.map( String ).concat( ids );
		}

		return ids.filter( function ( id, i, all ) {
			return /^\d+$/.test( id ) && all.indexOf( id ) === i;
		} ).slice( 0, max );
	}

	function write( ids ) {
		var expires = new Date( Date.now() + 365 * 864e5 ).toUTCString();

		document.cookie = name + '=' + encodeURIComponent( ids.join( '.' ) ) + '; expires=' + expires + '; path=/; SameSite=Lax';
	}

	var list = read();

	function paint( scope ) {
		Array.prototype.forEach.call( ( scope || document ).querySelectorAll( '[data-pfh-wish]' ), function ( button ) {
			var on = list.indexOf( button.getAttribute( 'data-pfh-wish' ) ) > -1;

			button.classList.toggle( 'is-on', on );
			button.setAttribute( 'aria-pressed', on ? 'true' : 'false' );

			if ( cfg.labelOn && cfg.labelOff ) {
				button.setAttribute( 'aria-label', on ? cfg.labelOn : cfg.labelOff );
			}
		} );
	}

	function save( id, on ) {
		if ( ! cfg.loggedIn || ! cfg.ajaxUrl || ! window.fetch ) {
			return;
		}

		var body = new window.FormData();

		body.append( 'action', 'pfh_wishlist' );
		body.append( 'nonce', cfg.nonce );
		body.append( 'id', id );
		body.append( 'on', on ? '1' : '' );

		window.fetch( cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } ).catch( function () {} );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest ? event.target.closest( '[data-pfh-wish]' ) : null;

		if ( ! button ) {
			return;
		}

		// A heart can sit inside a card that is itself a link.
		event.preventDefault();
		event.stopPropagation();

		var id = button.getAttribute( 'data-pfh-wish' );
		var on = list.indexOf( id ) === -1;

		list = list.filter( function ( one ) {
			return one !== id;
		} );

		if ( on ) {
			list.unshift( id );
		}

		list = list.slice( 0, max );
		write( list );
		save( id, on );
		paint();

		button.classList.remove( 'is-popping' );
		void button.offsetWidth;
		button.classList.add( 'is-popping' );

		// On the list itself, a product taken off goes from the list.
		if ( ! on ) {
			var item = button.closest( '[data-pfh-wish-item]' );
			var box = button.closest( '[data-pfh-wishlist]' );

			if ( item ) {
				item.parentNode.removeChild( item );
			}

			if ( box && ! box.querySelector( '[data-pfh-wish-item]' ) ) {
				var empty = box.querySelector( '.pfh-wishlist__empty' );

				if ( empty ) {
					empty.removeAttribute( 'hidden' );
				}
			}
		}
	}, true );

	// Cards drawn after load — a filtered archive, a deferred slider.
	if ( 'MutationObserver' in window ) {
		new window.MutationObserver( function ( changes ) {
			changes.forEach( function ( change ) {
				Array.prototype.forEach.call( change.addedNodes, function ( node ) {
					if ( 1 === node.nodeType ) {
						paint( node.parentNode || node );
					}
				} );
			} );
		} ).observe( document.documentElement, { childList: true, subtree: true } );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			paint();
		} );
	} else {
		paint();
	}
}() );
