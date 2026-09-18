/**
 * Single product: the gallery, the quantity, and the variant pills.
 *
 * The pills resolve the variation here, from the data printed with the form,
 * rather than handing off to WooCommerce's own variation script. That keeps
 * one code path: the same thing happens in the builder as on the page, there
 * is no jQuery to depend on, and nothing has to match the DOM shape a
 * third-party script expects.
 *
 * The <select> behind each group is still the real field. The pills set it and
 * fire a change, so a form post and anything else listening see the choice.
 */
( function () {
	'use strict';

	function cfg() {
		return window.pfhWidgets || {};
	}

	/* ------------------------------------------------------------------
	 * Gallery
	 * --------------------------------------------------------------- */

	function gallery( root ) {
		var shots = Array.prototype.slice.call( root.querySelectorAll( '[data-pfh-shot]' ) );
		var thumbs = Array.prototype.slice.call( root.querySelectorAll( '[data-pfh-shot-go]' ) );

		if ( ! shots.length ) {
			return null;
		}

		var at = 0;

		function show( index ) {
			at = ( index + shots.length ) % shots.length;

			shots.forEach( function ( shot, i ) {
				shot.classList.toggle( 'is-active', i === at );
			} );

			thumbs.forEach( function ( thumb, i ) {
				thumb.classList.toggle( 'is-active', i === at );
			} );
		}

		thumbs.forEach( function ( thumb ) {
			thumb.addEventListener( 'click', function () {
				show( parseInt( thumb.getAttribute( 'data-pfh-shot-go' ), 10 ) || 0 );
			} );
		} );

		Array.prototype.forEach.call( root.querySelectorAll( '[data-pfh-shot-step]' ), function ( button ) {
			button.addEventListener( 'click', function () {
				show( at + ( parseInt( button.getAttribute( 'data-pfh-shot-step' ), 10 ) || 1 ) );
			} );
		} );

		/**
		 * Put a variation's own photograph in front, if it has one.
		 *
		 * It is added to the strip rather than replacing anything, so going
		 * back to another variant still has every original image.
		 */
		function showUrl( url ) {
			if ( ! url ) {
				return;
			}

			var found = -1;

			shots.forEach( function ( shot, i ) {
				if ( shot.getAttribute( 'src' ) === url ) {
					found = i;
				}
			} );

			if ( found > -1 ) {
				show( found );

				return;
			}

			var image = shots[ 0 ].cloneNode( true );

			image.setAttribute( 'src', url );
			image.setAttribute( 'data-pfh-shot', String( shots.length ) );
			image.classList.remove( 'is-active' );
			shots[ 0 ].parentNode.appendChild( image );
			shots.push( image );
			show( shots.length - 1 );
		}

		return { show: show, showUrl: showUrl };
	}

	/* ------------------------------------------------------------------
	 * Quantity
	 * --------------------------------------------------------------- */

	function quantity( form ) {
		var field = form.querySelector( '[data-pfh-qty-field]' );

		if ( ! field ) {
			return;
		}

		var pad = field.hasAttribute( 'data-pfh-qty-pad' );

		function clamp( value ) {
			var min = parseInt( field.getAttribute( 'min' ), 10 ) || 1;
			var max = parseInt( field.getAttribute( 'max' ), 10 ) || 0;
			var n = parseInt( value, 10 );

			if ( isNaN( n ) || n < min ) {
				n = min;
			}

			if ( max > 0 && n > max ) {
				n = max;
			}

			return n;
		}

		function paint() {
			var n = clamp( field.value );

			// Shown as 01, posted as 1: a padded value in a number field is
			// not a number, so the display and the value are kept apart.
			field.value = pad && n < 10 ? '0' + n : String( n );

			Array.prototype.forEach.call( form.querySelectorAll( '[data-pfh-qty]' ), function ( button ) {
				var step = parseInt( button.getAttribute( 'data-pfh-qty' ), 10 ) || 0;
				var max = parseInt( field.getAttribute( 'max' ), 10 ) || 0;

				button.disabled = ( step < 0 && n <= ( parseInt( field.getAttribute( 'min' ), 10 ) || 1 ) )
					|| ( step > 0 && max > 0 && n >= max );
			} );
		}

		Array.prototype.forEach.call( form.querySelectorAll( '[data-pfh-qty]' ), function ( button ) {
			button.addEventListener( 'click', function () {
				field.value = clamp( ( parseInt( field.value, 10 ) || 1 ) + ( parseInt( button.getAttribute( 'data-pfh-qty' ), 10 ) || 0 ) );
				paint();
			} );
		} );

		field.addEventListener( 'change', paint );
		field.addEventListener( 'blur', paint );
		paint();
	}

	/* ------------------------------------------------------------------
	 * Variants
	 * --------------------------------------------------------------- */

	function variants( form, shots ) {
		var groups = Array.prototype.slice.call( form.querySelectorAll( '[data-pfh-attr]' ) );
		var raw = form.getAttribute( 'data-variations' );
		var variations = [];

		if ( raw ) {
			try {
				variations = JSON.parse( raw );
			} catch ( e ) {
				variations = [];
			}
		}

		if ( ! groups.length ) {
			return;
		}

		var now = form.parentNode.querySelector( '[data-pfh-price-now]' );
		var was = form.parentNode.querySelector( '[data-pfh-price-was]' );
		var save = form.parentNode.querySelector( '[data-pfh-price-save]' );
		var idField = form.querySelector( '[data-pfh-variation]' );
		var buy = form.querySelector( '[data-pfh-buy]' );
		var base = {
			now: now ? now.innerHTML : '',
			was: was ? was.innerHTML : '',
			save: save ? save.textContent : '',
			wasOff: was ? was.hasAttribute( 'hidden' ) : true,
			saveOff: save ? save.hasAttribute( 'hidden' ) : true
		};

		function chosen() {
			var picked = {};

			groups.forEach( function ( group ) {
				var field = group.querySelector( '[data-pfh-attr-field]' );

				picked[ group.getAttribute( 'data-pfh-attr' ) ] = field ? field.value : '';
			} );

			return picked;
		}

		/**
		 * Does a variation accept this set of choices? An empty value on the
		 * variation means "any", which is how WooCommerce stores a variation
		 * that ignores an attribute.
		 */
		function accepts( variation, picked, ignore ) {
			var ok = true;

			Object.keys( picked ).forEach( function ( key ) {
				if ( key === ignore || ! picked[ key ] ) {
					return;
				}

				var want = variation.attributes[ key ];

				if ( want && want !== picked[ key ] ) {
					ok = false;
				}
			} );

			return ok;
		}

		function match() {
			var picked = chosen();
			var complete = Object.keys( picked ).every( function ( key ) {
				return !! picked[ key ];
			} );

			if ( ! complete ) {
				return null;
			}

			for ( var i = 0; i < variations.length; i++ ) {
				if ( accepts( variations[ i ], picked, null ) ) {
					return variations[ i ];
				}
			}

			return null;
		}

		function setText( node, html, off ) {
			if ( ! node ) {
				return;
			}

			node.innerHTML = html;

			if ( off ) {
				node.setAttribute( 'hidden', '' );
			} else {
				node.removeAttribute( 'hidden' );
			}
		}

		function paint() {
			var picked = chosen();

			groups.forEach( function ( group ) {
				var key = group.getAttribute( 'data-pfh-attr' );
				var value = picked[ key ];
				var label = group.querySelector( '[data-pfh-attr-value]' );

				Array.prototype.forEach.call( group.querySelectorAll( '[data-pfh-pill]' ), function ( pill ) {
					var mine = pill.getAttribute( 'data-pfh-pill' );
					var on = mine === value;

					pill.classList.toggle( 'is-chosen', on );
					pill.setAttribute( 'aria-pressed', on ? 'true' : 'false' );

					// Would anything still be buyable if this were chosen?
					if ( variations.length ) {
						var reachable = variations.some( function ( variation ) {
							var want = variation.attributes[ key ];

							return variation.buyable
								&& ( ! want || want === mine )
								&& accepts( variation, picked, key );
						} );

						pill.disabled = ! reachable;
					}

					if ( on && label ) {
						label.textContent = ' — ' + pill.textContent.trim();
					}
				} );

				if ( ! value && label ) {
					label.textContent = '';
				}
			} );

			var found = match();

			if ( idField ) {
				idField.value = found ? found.id : 0;
			}

			if ( found ) {
				setText( now, found.now, false );
				setText( was, found.was, ! found.was );
				setText( save, found.save, ! found.save );
			} else {
				setText( now, base.now, false );
				setText( was, base.was, base.wasOff );
				setText( save, base.save, base.saveOff );
			}

			if ( buy ) {
				buy.disabled = variations.length ? ! ( found && found.buyable ) : false;
			}

			if ( found && found.image && shots ) {
				shots.showUrl( found.image );
			}
		}

		groups.forEach( function ( group ) {
			var field = group.querySelector( '[data-pfh-attr-field]' );

			Array.prototype.forEach.call( group.querySelectorAll( '[data-pfh-pill]' ), function ( pill ) {
				pill.addEventListener( 'click', function () {
					if ( pill.disabled || ! field ) {
						return;
					}

					var value = pill.getAttribute( 'data-pfh-pill' );

					// Clicking the chosen one again clears it, which is the
					// only way back to "no choice" without a reset link.
					field.value = field.value === value ? '' : value;
					field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				} );
			} );

			if ( field ) {
				field.addEventListener( 'change', paint );
			}
		} );

		paint();
	}

	/* ------------------------------------------------------------------
	 * Adding to the cart
	 * --------------------------------------------------------------- */

	function cart( form ) {
		if ( ! form.hasAttribute( 'data-pfh-ajax' ) ) {
			return;
		}

		var c = cfg();

		if ( ! c.ajaxUrl || ! c.nonce ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			var buy = form.querySelector( '[data-pfh-buy]' );
			var notice = form.parentNode.querySelector( '[data-pfh-notice]' );

			event.preventDefault();

			if ( ! buy || buy.disabled ) {
				return;
			}

			var body = new URLSearchParams();

			body.set( 'action', 'pfh_add' );
			body.set( 'nonce', c.nonce );
			body.set( 'product_id', form.getAttribute( 'data-product' ) || '0' );
			body.set( 'variation_id', ( form.querySelector( '[data-pfh-variation]' ) || {} ).value || '0' );
			body.set( 'quantity', String( parseInt( ( form.querySelector( '[data-pfh-qty-field]' ) || {} ).value, 10 ) || 1 ) );

			Array.prototype.forEach.call( form.querySelectorAll( '[data-pfh-attr-field]' ), function ( field ) {
				if ( field.value ) {
					body.set( 'attributes[' + field.name + ']', field.value );
				}
			} );

			buy.classList.add( 'is-busy' );

			if ( notice ) {
				notice.setAttribute( 'hidden', '' );
			}

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
					if ( payload && payload.success ) {
						// The drawer and the header count both listen for this.
						document.dispatchEvent( new CustomEvent( 'pfh:added', { detail: payload.data || {} } ) );

						return;
					}

					throw new Error( ( payload && payload.data && payload.data.message ) || '' );
				} )
				.catch( function ( error ) {
					/*
					 * Say what went wrong and leave the form alone: submitting
					 * it the ordinary way still works, and is the way out if
					 * the request keeps failing.
					 */
					if ( notice ) {
						notice.textContent = error.message || notice.getAttribute( 'data-fallback' ) || '';
						notice.removeAttribute( 'hidden' );
					}
				} )
				.then( function () {
					buy.classList.remove( 'is-busy' );
				} );
		} );
	}

	function start() {
		Array.prototype.forEach.call( document.querySelectorAll( '.pfh-pdp' ), function ( root ) {
			if ( root.hasAttribute( 'data-pfh-ready' ) ) {
				return;
			}

			root.setAttribute( 'data-pfh-ready', '' );

			var shots = gallery( root );
			var form = root.querySelector( '[data-pfh-form]' );

			if ( ! form ) {
				return;
			}

			quantity( form );
			variants( form, shots );
			cart( form );
		} );
	}

	window.pfhProductInit = start;

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
