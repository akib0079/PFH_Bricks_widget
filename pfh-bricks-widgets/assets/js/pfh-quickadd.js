/**
 * Products For Home – site-wide AJAX add to cart, with a variant chooser.
 *
 * Every add-to-cart on the site is intercepted: product cards, WooCommerce
 * loop buttons and the single-product form. A simple product goes straight
 * into the cart; a variable one opens a chooser first. Nothing reloads and
 * nothing prints a notice — the cart drawer opening is the confirmation, which
 * the header listens for via the `pfh:added` event.
 */
( function () {
	'use strict';

	function config() {
		return window.pfhWidgets || {};
	}

	function ready() {
		var c = config();

		return !! ( c.ajaxUrl && c.nonce );
	}

	function post( action, data ) {
		var body = new FormData();
		var c = config();

		body.append( 'action', action );
		body.append( 'nonce', c.nonce );

		Object.keys( data || {} ).forEach( function ( key ) {
			var value = data[ key ];

			if ( value && typeof value === 'object' ) {
				Object.keys( value ).forEach( function ( inner ) {
					body.append( key + '[' + inner + ']', value[ inner ] );
				} );

				return;
			}

			body.append( key, value );
		} );

		return window
			.fetch( c.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( r ) {
				return r.json();
			} );
	}

	function announce( payload ) {
		document.dispatchEvent( new CustomEvent( 'pfh:added', { detail: payload || {} } ) );

		// Keep WooCommerce's own listeners (mini-cart widgets, counters) in
		// step without letting its notice machinery run.
		if ( window.jQuery ) {
			window.jQuery( document.body ).trigger( 'wc_fragment_refresh' );
		}
	}

	function busy( el, state ) {
		if ( ! el ) {
			return;
		}

		el.classList.toggle( 'is-loading', !! state );
		el.setAttribute( 'aria-busy', state ? 'true' : 'false' );

		if ( 'BUTTON' === el.tagName ) {
			el.disabled = !! state;
		} else {
			// Anchors cannot be disabled; stop a second click landing while
			// the first is still in flight.
			el.style.pointerEvents = state ? 'none' : '';
		}
	}

	/* ------------------------------------------------------------- adding */

	function addToCart( payload, trigger ) {
		busy( trigger, true );

		return post( 'pfh_add', payload )
			.then( function ( res ) {
				busy( trigger, false );

				if ( res && res.success ) {
					if ( trigger ) {
						trigger.classList.add( 'is-added' );
						window.setTimeout( function () {
							trigger.classList.remove( 'is-added' );
						}, 1600 );
					}

					announce( res.data );

					return true;
				}

				var message = res && res.data && res.data.message ? res.data.message : null;

				if ( message ) {
					Modal.error( message );
				}

				return false;
			} )
			.catch( function () {
				busy( trigger, false );
				Modal.error();

				return false;
			} );
	}

	/* -------------------------------------------------------------- modal */

	var Modal = {
		el: null,
		lastFocus: null,

		build: function () {
			if ( this.el ) {
				return this.el;
			}

			var root = document.createElement( 'div' );

			root.className = 'pfh-qa pfh-scope';
			root.setAttribute( 'role', 'dialog' );
			root.setAttribute( 'aria-modal', 'true' );
			root.hidden = true;
			root.innerHTML =
				'<div class="pfh-qa__scrim" data-pfh-qa-close></div>' +
				'<div class="pfh-qa__panel" role="document">' +
				'<button type="button" class="pfh-qa__close" data-pfh-qa-close aria-label="Close">' +
				'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>' +
				'</button>' +
				'<div class="pfh-qa__body"></div>' +
				'</div>';

			document.body.appendChild( root );

			root.addEventListener( 'click', function ( event ) {
				if ( event.target.closest( '[data-pfh-qa-close]' ) ) {
					Modal.close();

					return;
				}

				var body = root.querySelector( '.pfh-qa__body' );

				if ( ! body || ! body.pfhData ) {
					return;
				}

				var step = event.target.closest( '[data-pfh-qa-step]' );

				if ( step ) {
					var input = body.querySelector( '[data-pfh-qa-qty]' );
					var by = parseInt( step.getAttribute( 'data-pfh-qa-step' ), 10 ) || 0;

					input.value = Math.max( 1, ( parseInt( input.value, 10 ) || 1 ) + by );

					return;
				}

				if ( event.target.closest( '[data-pfh-qa-submit]' ) ) {
					submit( body );
				}
			} );

			root.addEventListener( 'change', function ( event ) {
				var body = root.querySelector( '.pfh-qa__body' );

				if ( body && body.pfhData && event.target.matches( '[data-pfh-qa-attr]' ) ) {
					resolve( body );
				}
			} );

			document.addEventListener( 'keydown', function ( event ) {
				if ( 'Escape' === event.key && ! root.hidden ) {
					Modal.close();
				}
			} );

			this.el = root;

			return root;
		},

		open: function ( html ) {
			var root = this.build();

			// A pending close would otherwise hide the dialog we just opened.
			if ( this.hideTimer ) {
				window.clearTimeout( this.hideTimer );
				this.hideTimer = null;
			}

			this.lastFocus = document.activeElement;
			root.querySelector( '.pfh-qa__body' ).innerHTML = html;
			root.hidden = false;
			// Reflow so the opening transition has a starting frame.
			void root.offsetWidth;
			root.classList.add( 'is-open' );
			document.documentElement.classList.add( 'pfh-qa-open' );

			var focusable = root.querySelector( 'select, button:not([data-pfh-qa-close]), input' );

			if ( focusable ) {
				focusable.focus();
			}
		},

		close: function () {
			if ( ! this.el || this.el.hidden ) {
				return;
			}

			var root = this.el;

			root.classList.remove( 'is-open' );
			document.documentElement.classList.remove( 'pfh-qa-open' );

			// `hidden` lands after the fade; `pointer-events: none` in the
			// stylesheet covers the gap so nothing is clickable meanwhile.
			if ( this.hideTimer ) {
				window.clearTimeout( this.hideTimer );
			}

			this.hideTimer = window.setTimeout( function () {
				root.hidden = true;
				Modal.hideTimer = null;
			}, 220 );

			// Same reason as the drawer: returning focus must not scroll.
			if ( this.lastFocus && this.lastFocus.focus ) {
				try {
					this.lastFocus.focus( { preventScroll: true } );
				} catch ( error ) {
					this.lastFocus.focus();
				}
			}
		},

		error: function ( message ) {
			this.open(
				'<p class="pfh-qa__error">' +
					escapeHtml( message || 'Something went wrong. Please try again.' ) +
					'</p>'
			);
		}
	};

	function escapeHtml( value ) {
		var div = document.createElement( 'div' );

		div.textContent = value == null ? '' : String( value );

		return div.innerHTML;
	}

	/* ------------------------------------------------------ variant picker */

	function openPicker( productId, trigger ) {
		busy( trigger, true );

		post( 'pfh_variations', { product_id: productId } )
			.then( function ( res ) {
				busy( trigger, false );

				if ( ! res || ! res.success ) {
					Modal.error( res && res.data ? res.data.message : null );

					return;
				}

				renderPicker( res.data );
			} )
			.catch( function () {
				busy( trigger, false );
				Modal.error();
			} );
	}

	function renderPicker( data ) {
		var html = '<div class="pfh-qa__product">';

		if ( data.image ) {
			html += '<img class="pfh-qa__thumb" src="' + escapeHtml( data.image ) + '" alt="" />';
		}

		html +=
			'<div class="pfh-qa__meta">' +
			'<h2 class="pfh-qa__title">' + escapeHtml( data.name ) + '</h2>' +
			'<div class="pfh-qa__price" data-pfh-qa-price>' + ( data.price || '' ) + '</div>' +
			'</div></div>';

		html += '<div class="pfh-qa__fields">';

		data.attributes.forEach( function ( attr, i ) {
			html +=
				'<label class="pfh-qa__field">' +
				'<span class="pfh-qa__label">' + escapeHtml( attr.label ) + '</span>' +
				'<select class="pfh-qa__select" data-pfh-qa-attr="' + escapeHtml( attr.name ) + '">' +
				'<option value="">' + escapeHtml( 'Choose ' + attr.label.toLowerCase() ) + '</option>';

			attr.options.forEach( function ( option ) {
				html +=
					'<option value="' + escapeHtml( option.value ) + '">' +
					escapeHtml( option.label ) +
					'</option>';
			} );

			html += '</select></label>';
			i = i; // eslint no-unused
		} );

		html += '</div>';

		html +=
			'<div class="pfh-qa__actions">' +
			'<div class="pfh-qa__qty">' +
			'<button type="button" class="pfh-qa__step" data-pfh-qa-step="-1" aria-label="Fewer">&minus;</button>' +
			'<input class="pfh-qa__qty-input" type="number" min="1" value="1" data-pfh-qa-qty aria-label="Quantity" />' +
			'<button type="button" class="pfh-qa__step" data-pfh-qa-step="1" aria-label="More">+</button>' +
			'</div>' +
			'<button type="button" class="pfh-qa__submit" data-pfh-qa-submit disabled>Add to cart</button>' +
			'</div>' +
			'<p class="pfh-qa__note" data-pfh-qa-note></p>';

		Modal.open( html );

		var root = Modal.el;
		var body = root.querySelector( '.pfh-qa__body' );

		body.pfhData = data;
		body.pfhChoice = null;
		// Keep the product's own price/image so an invalid pick can fall back
		// to them instead of leaving the last valid variation on screen.
		body.pfhBasePrice = data.price || '';
		body.pfhBaseImage = data.image || '';

		/*
		 * The listeners live on the dialog root and are bound once in build().
		 * Binding them here instead would stack another pair on the same
		 * persistent body element every time the chooser opened, so a single
		 * click on "+" would step the quantity once per previous open.
		 */
		resolve( body );
	}

	/** Narrow the chosen attributes down to a single variation. */
	function resolve( body ) {
		var data = body.pfhData;
		var selects = Array.prototype.slice.call( body.querySelectorAll( '[data-pfh-qa-attr]' ) );
		var chosen = {};
		var complete = true;

		selects.forEach( function ( select ) {
			var name = select.getAttribute( 'data-pfh-qa-attr' );

			chosen[ name ] = select.value;

			if ( ! select.value ) {
				complete = false;
			}
		} );

		var match = null;

		if ( complete ) {
			match = data.variations.filter( function ( variation ) {
				return Object.keys( chosen ).every( function ( name ) {
					var want = variation.attributes[ name ];

					// An empty value on the variation means "any".
					return ! want || want === chosen[ name ];
				} );
			} )[ 0 ] || null;
		}

		body.pfhChoice = match;
		body.pfhChosen = chosen;

		var submitBtn = body.querySelector( '[data-pfh-qa-submit]' );
		var note = body.querySelector( '[data-pfh-qa-note]' );
		var price = body.querySelector( '[data-pfh-qa-price]' );
		var thumb = body.querySelector( '.pfh-qa__thumb' );

		note.textContent = '';

		function reset() {
			if ( price ) {
				price.innerHTML = body.pfhBasePrice;
			}

			if ( thumb && body.pfhBaseImage ) {
				thumb.src = body.pfhBaseImage;
			}
		}

		if ( ! complete ) {
			submitBtn.disabled = true;
			reset();

			return;
		}

		if ( ! match ) {
			submitBtn.disabled = true;
			note.textContent = 'That combination is not available.';
			reset();

			return;
		}

		if ( ! match.inStock ) {
			submitBtn.disabled = true;
			note.textContent = 'Out of stock.';

			if ( match.price && price ) {
				price.innerHTML = match.price;
			}

			return;
		}

		submitBtn.disabled = false;

		if ( match.price && price ) {
			price.innerHTML = match.price;
		}

		if ( match.image && thumb ) {
			thumb.src = match.image;
		}
	}

	function submit( body ) {
		var match = body.pfhChoice;

		if ( ! match ) {
			return;
		}

		var qty = parseInt( body.querySelector( '[data-pfh-qa-qty]' ).value, 10 ) || 1;
		var button = body.querySelector( '[data-pfh-qa-submit]' );

		busy( button, true );

		addToCart(
			{
				product_id: body.pfhData.id,
				variation_id: match.id,
				quantity: qty,
				attributes: body.pfhChosen
			},
			button
		).then( function ( ok ) {
			busy( button, false );

			if ( ok ) {
				Modal.close();
			}
		} );
	}

	/* ---------------------------------------------------------- delegation */

	function onClick( event ) {
		if ( ! ready() ) {
			return;
		}

		var trigger = event.target.closest( '[data-pfh-add]' );

		if ( trigger ) {
			event.preventDefault();

			var type = trigger.getAttribute( 'data-pfh-type' );
			var id = parseInt( trigger.getAttribute( 'data-pfh-add' ), 10 );

			if ( ! id ) {
				return;
			}

			if ( 'variable' === type ) {
				openPicker( id, trigger );
			} else {
				addToCart( { product_id: id, quantity: trigger.getAttribute( 'data-pfh-qty' ) || 1 }, trigger );
			}

			return;
		}

		// WooCommerce's own loop buttons, wherever the theme renders them.
		var woo = event.target.closest( '.add_to_cart_button, .product_type_variable' );

		if ( ! woo || woo.classList.contains( 'pfh-prod__cart' ) ) {
			return;
		}

		var wooId = parseInt( woo.getAttribute( 'data-product_id' ), 10 );

		if ( ! wooId ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		if ( woo.classList.contains( 'product_type_variable' ) ) {
			openPicker( wooId, woo );
		} else {
			addToCart( { product_id: wooId, quantity: woo.getAttribute( 'data-quantity' ) || 1 }, woo );
		}
	}

	/** The single product page's own form. */
	function onSubmit( event ) {
		if ( ! ready() ) {
			return;
		}

		var form = event.target;

		if ( ! form.matches || ! form.matches( 'form.cart' ) ) {
			return;
		}

		var addField = form.querySelector( '[name="add-to-cart"]' );
		var productId = parseInt( addField ? addField.value : 0, 10 );
		var variationField = form.querySelector( '[name="variation_id"]' );
		var variationId = parseInt( variationField ? variationField.value : 0, 10 );

		// Grouped products post an array; leave those to WooCommerce.
		if ( ! productId || form.querySelector( '[name^="quantity["]' ) ) {
			return;
		}

		event.preventDefault();

		var attributes = {};

		Array.prototype.slice.call( form.querySelectorAll( '[name^="attribute_"]' ) ).forEach( function ( field ) {
			attributes[ field.name ] = field.value;
		} );

		var qtyField = form.querySelector( '[name="quantity"]' );

		addToCart(
			{
				product_id: productId,
				variation_id: variationId || 0,
				quantity: qtyField ? qtyField.value : 1,
				attributes: attributes
			},
			form.querySelector( '[type="submit"]' )
		);
	}

	document.addEventListener( 'click', onClick, true );
	document.addEventListener( 'submit', onSubmit, true );
} )();
