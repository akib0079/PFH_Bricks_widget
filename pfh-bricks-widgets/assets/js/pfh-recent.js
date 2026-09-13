/**
 * Recently viewed slider, fetched after the page.
 *
 * The visitor's history is in their own WooCommerce cookie, so the markup
 * cannot be part of a cached page — it would show one shopper what the last
 * one had been looking at. The placeholder ships empty and hidden and this
 * asks the server for that visitor's own slider.
 *
 * Nothing viewed means nothing returned, and the placeholder simply stays
 * hidden: no heading, no empty rail, no layout shift.
 */
( function () {
	'use strict';

	function cfg() {
		return window.pfhRecent || {};
	}

	/**
	 * Hand the freshly inserted slider to the drag-slider script.
	 *
	 * It binds on DOMContentLoaded, which is long gone by the time this
	 * arrives, so the markup would otherwise be a static row that cannot be
	 * dragged or paged.
	 */
	function startSlider() {
		// It skips anything it has already bound, so this is safe to call.
		if ( 'function' === typeof window.pfhSliderInit ) {
			window.pfhSliderInit();
		}
	}

	function load( host ) {
		var id = host.getAttribute( 'data-pfh-recent' );
		var c  = cfg();

		if ( ! id || ! c.ajaxUrl ) {
			return;
		}

		var body = new URLSearchParams();
		body.set( 'action', 'pfh_recent' );
		body.set( 'nonce', c.nonce || '' );
		body.set( 'element', id );

		window.fetch( c.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( payload ) {
				if ( ! payload || ! payload.success || ! payload.data || ! payload.data.html ) {
					return;
				}

				host.innerHTML = payload.data.html;
				host.hidden = false;

				startSlider();

				host.dispatchEvent(
					new CustomEvent( 'pfh:recent', { bubbles: true, detail: { element: id } } )
				);
			} )
			.catch( function () {
				/*
				 * Left hidden on purpose. This section is a convenience, not
				 * part of the page's job — a failed request should cost the
				 * shopper nothing and say nothing.
				 */
			} );
	}

	function start() {
		Array.prototype.forEach.call(
			document.querySelectorAll( '[data-pfh-recent]' ),
			load
		);
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
