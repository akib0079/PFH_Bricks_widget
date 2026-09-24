/**
 * Checkout: keep the shop's own address labels.
 *
 * The page is served with the names the shop gave its address fields
 * ("Plaats", "Postcode"). WooCommerce's address script then rewrites every
 * label from its own per-country table each time the country is set — which
 * it does once as the page loads — so those names were replaced by the store
 * language's defaults ("Town / City") before anyone saw them.
 *
 * This runs just before that script. It notes each label the page arrived
 * with and, after every country change, puts back the ones the shop chose.
 * A label that is one of WooCommerce's own is not noted, so the per-country
 * wording ("Province", "ZIP") still follows the country as intended.
 */
( function ( $ ) {
	'use strict';

	var params = window.wc_address_i18n_params;
	var own = {};

	if ( ! $ || ! params ) {
		return;
	}

	function parse( json ) {
		try {
			return 'string' === typeof json ? JSON.parse( json ) : json || {};
		} catch ( e ) {
			return {};
		}
	}

	/** The first text in a label — the name, before any required marker. */
	function words( label ) {
		for ( var node = label.firstChild; node; node = node.nextSibling ) {
			if ( 3 === node.nodeType && node.nodeValue.trim() ) {
				return node;
			}
		}

		return null;
	}

	/** Every label WooCommerce's table has for a field, in any country. */
	function known( locale, key ) {
		var seen = {};

		Object.keys( locale ).forEach( function ( country ) {
			var field = locale[ country ] && locale[ country ][ key ];

			if ( field && field.label ) {
				seen[ String( field.label ).trim() ] = true;
			}
		} );

		return seen;
	}

	function remember() {
		var locale = parse( params.locale );
		var fields = parse( params.locale_fields );

		Object.keys( fields ).forEach( function ( key ) {
			var theirs = known( locale, key );

			$( fields[ key ] ).each( function () {
				var label = this.querySelector( 'label' );
				var node = label && words( label );
				var name = node ? node.nodeValue.trim() : '';

				if ( this.id && name && ! theirs[ name ] ) {
					own[ this.id ] = name;
				}
			} );
		} );
	}

	function restore() {
		Object.keys( own ).forEach( function ( id ) {
			var row = document.getElementById( id );
			var label = row && row.querySelector( 'label' );
			var node = label && words( label );

			if ( node && node.nodeValue.trim() !== own[ id ] ) {
				node.nodeValue = own[ id ];
			}
		} );
	}

	// Queued before WooCommerce's own ready handler, so the labels are read
	// as served, before its first rewrite.
	$( remember );

	// Its rewrite happens inside this event; put ours back once it is done.
	// Bound on the document, not the body: some checkouts (FunnelKit's) print
	// their scripts in the head, where there is no body yet to bind to. The
	// event is triggered on the body and bubbles up here either way.
	$( document ).on( 'country_to_state_changing', function () {
		window.setTimeout( restore, 0 );
	} );
}( window.jQuery ) );
