/**
 * Settings screen behaviour: media picker, colour swatch, option preview
 * and conditional rows.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		/* ---- media picker ---- */

		$( document ).on( 'click', '.pfh-media__pick', function ( event ) {
			event.preventDefault();

			var wrap = $( this ).closest( '[data-pfh-media]' );
			var frame = wp.media( {
				title: wp.media.view.l10n.addMedia,
				library: { type: 'image' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var image = frame.state().get( 'selection' ).first().toJSON();
				var src = ( image.sizes && image.sizes.medium ) ? image.sizes.medium.url : image.url;

				wrap.find( 'input[type="hidden"]' ).val( image.id );
				wrap.find( '.pfh-media__frame' ).html( $( '<img>' ).attr( { src: src, alt: '' } ) );
				wrap.find( '.pfh-media__clear' ).prop( 'hidden', false );
			} );

			frame.open();
		} );

		$( document ).on( 'click', '.pfh-media__clear', function ( event ) {
			event.preventDefault();

			var wrap = $( this ).closest( '[data-pfh-media]' );

			wrap.find( 'input[type="hidden"]' ).val( '' );
			wrap.find( '.pfh-media__frame' ).empty();
			$( this ).prop( 'hidden', true );
		} );

		/* ---- colour swatch mirrors the text field ---- */

		$( '.pfh-color__swatch' ).on( 'input change', function () {
			$( this ).prev( '.pfh-color' ).val( $( this ).val() ).trigger( 'change' );
		} );

		$( '.pfh-color' ).on( 'input', function () {
			var value = $( this ).val();

			if ( /^#[0-9a-f]{6}$/i.test( value ) ) {
				$( this ).next( '.pfh-color__swatch' ).val( value );
			}
		} );

		/* ---- a field's preview follows its select ---- */

		$( 'select[data-pfh-field]' ).on( 'change', function () {
			var key = $( this ).data( 'pfh-field' );
			var value = $( this ).val();

			$( '[data-pfh-preview="' + key + '"] code' ).each( function () {
				$( this ).prop( 'hidden', $( this ).data( 'for' ) !== value );
			} );
		} );

		/* ---- conditional rows ---- */

		function sync() {
			$( 'tr[data-show-if]' ).each( function () {
				var rule = String( $( this ).data( 'show-if' ) );
				var parts = rule.split( ':' );
				var field = parts[ 0 ];
				var want = parts.length > 1 ? parts[ 1 ] : '1';
				var input = $( '[data-pfh-field="' + field + '"]' );
				var actual;

				if ( ! input.length ) {
					return;
				}

				actual = input.is( ':checkbox' ) ? ( input.prop( 'checked' ) ? '1' : '0' ) : String( input.val() );

				$( this ).toggle( actual === want );
			} );
		}

		$( document ).on( 'change', '[data-pfh-field]', sync );
		sync();
	} );
}( jQuery ) );
