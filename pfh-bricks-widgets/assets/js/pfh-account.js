/**
 * Products For Home – the account page.
 *
 * Three small jobs, none of which reload the page: the rail switches panes,
 * the sign-in card switches forms, and an order fetches its own contents the
 * first time it is opened and keeps them afterwards.
 *
 * The chosen tab goes into the URL with replaceState, so a refresh — or the
 * redirect WooCommerce does after saving an address — comes back to where the
 * customer was rather than to the top.
 */
( function () {
	'use strict';

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function cfg() {
		var c = window.pfhWidgets || {};

		return { ajaxUrl: c.ajaxUrl || '', nonce: c.nonce || '' };
	}

	/* ------------------------------------------------------------------
	 * The rail
	 * --------------------------------------------------------------- */

	function tabs( root ) {
		var rail = root.querySelector( '[data-pfh-acc-rail]' );

		if ( ! rail ) {
			return;
		}

		var buttons = qsa( '[data-pfh-acc-tab]', rail );

		function show( key, remember ) {
			var found = false;

			buttons.forEach( function ( button ) {
				var mine = button.getAttribute( 'data-pfh-acc-tab' ) === key;

				button.setAttribute( 'aria-selected', mine ? 'true' : 'false' );
				button.tabIndex = mine ? 0 : -1;

				if ( mine ) {
					found = true;
				}
			} );

			if ( ! found ) {
				return false;
			}

			qsa( '.pfh-acc__pane', root ).forEach( function ( pane ) {
				pane.hidden = pane.getAttribute( 'aria-labelledby' ).indexOf( 'pfh-tab-' + key + '-' ) !== 0;
			} );

			if ( remember && window.history && history.replaceState ) {
				try {
					var url = new URL( window.location.href );

					url.hash = 'acc-' + key;
					history.replaceState( null, '', url.toString() );
				} catch ( e ) {} // eslint-disable-line no-empty
			}

			return true;
		}

		buttons.forEach( function ( button, index ) {
			button.addEventListener( 'click', function () {
				show( button.getAttribute( 'data-pfh-acc-tab' ), true );
			} );

			// Arrow keys along the rail, as a tablist is meant to behave.
			button.addEventListener( 'keydown', function ( event ) {
				var step = 0;

				if ( 'ArrowRight' === event.key || 'ArrowDown' === event.key ) { step = 1; }
				if ( 'ArrowLeft' === event.key || 'ArrowUp' === event.key ) { step = -1; }
				if ( 'Home' === event.key ) { step = -index; }
				if ( 'End' === event.key ) { step = buttons.length - 1 - index; }

				if ( ! step ) {
					return;
				}

				event.preventDefault();

				var next = buttons[ ( index + step + buttons.length ) % buttons.length ];

				next.focus();
				show( next.getAttribute( 'data-pfh-acc-tab' ), true );
			} );
		} );

		// Deep link: #acc-orders opens the orders.
		var wanted = ( window.location.hash || '' ).replace( /^#acc-/, '' );

		if ( wanted && wanted !== window.location.hash ) {
			show( wanted, false );
		}
	}

	/* ------------------------------------------------------------------
	 * Sign in / register
	 * --------------------------------------------------------------- */

	function forms( root ) {
		var seg = root.querySelector( '[data-pfh-acc-seg]' );

		if ( ! seg ) {
			return;
		}

		var buttons = qsa( '[data-pfh-acc-form]', seg );

		buttons.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var want = button.getAttribute( 'data-pfh-acc-form' );

				buttons.forEach( function ( other ) {
					var mine = other === button;

					other.setAttribute( 'aria-selected', mine ? 'true' : 'false' );
					other.tabIndex = mine ? 0 : -1;
				} );

				qsa( '.pfh-acc__form', root ).forEach( function ( form ) {
					if ( form.id.indexOf( 'pfh-form-' ) !== 0 ) {
						return;
					}

					var shown = form.id.indexOf( 'pfh-form-' + want + '-' ) === 0;

					form.hidden = ! shown;

					if ( shown ) {
						var first = form.querySelector( 'input:not([type="hidden"])' );

						if ( first ) {
							first.focus( { preventScroll: true } );
						}
					}
				} );
			} );
		} );

		/*
		 * A failed registration comes back with the sign-in form on top and the
		 * error underneath it, which reads as though logging in failed. If the
		 * register form is the one that was posted, open it.
		 */
		if ( 'register' === ( window.location.hash || '' ).replace( '#', '' ) ) {
			var register = seg.querySelector( '[data-pfh-acc-form="register"]' );

			if ( register ) {
				register.click();
			}
		}
	}

	/* ------------------------------------------------------------------
	 * Orders
	 * --------------------------------------------------------------- */

	function orders( root ) {
		qsa( '.pfh-acc__order', root ).forEach( function ( order ) {
			var head = order.querySelector( '.pfh-acc__order-head' );
			var body = order.querySelector( '.pfh-acc__order-body' );

			if ( ! head || ! body ) {
				return;
			}

			head.addEventListener( 'click', function () {
				var open = ! order.classList.contains( 'is-open' );

				order.classList.toggle( 'is-open', open );
				head.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				body.hidden = ! open;

				if ( open ) {
					fetchOrder( order, body );
				}
			} );
		} );
	}

	/** Ask for one order's contents, once. */
	function fetchOrder( order, body ) {
		if ( order.hasAttribute( 'data-pfh-acc-loaded' ) ) {
			return;
		}

		var c = cfg();
		var id = order.getAttribute( 'data-pfh-acc-order' );

		if ( ! c.ajaxUrl || ! c.nonce || ! id ) {
			return;
		}

		// Set before the request, so a double click does not ask twice.
		order.setAttribute( 'data-pfh-acc-loaded', '' );

		var payload = new URLSearchParams();

		payload.set( 'action', 'pfh_order' );
		payload.set( 'nonce', c.nonce );
		payload.set( 'order', id );

		window.fetch( c.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: payload.toString()
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( data && data.success && data.data && data.data.html ) {
					body.innerHTML = data.data.html;

					return;
				}

				throw new Error( ( data && data.data && data.data.message ) || '' );
			} )
			.catch( function ( error ) {
				/*
				 * Say so, and let them try again — the attribute goes back so
				 * closing and reopening asks once more.
				 */
				order.removeAttribute( 'data-pfh-acc-loaded' );
				body.innerHTML = '<p class="pfh-acc__notice">' + ( error.message || 'Deze bestelling kon niet worden geopend.' ) + '</p>';
			} );
	}

	/* ------------------------------------------------------------------
	 * Passwords
	 * --------------------------------------------------------------- */

	function peek( root ) {
		qsa( '[data-pfh-acc-peek]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var field = button.parentNode.querySelector( 'input' );

				if ( ! field ) {
					return;
				}

				var show = 'password' === field.type;

				field.type = show ? 'text' : 'password';
				button.setAttribute( 'aria-pressed', show ? 'true' : 'false' );
				field.focus( { preventScroll: true } );
			} );
		} );
	}

	function boot() {
		qsa( '.pfh-acc' ).forEach( function ( root ) {
			if ( root.hasAttribute( 'data-pfh-acc-ready' ) ) {
				return;
			}

			root.setAttribute( 'data-pfh-acc-ready', '' );

			tabs( root );
			forms( root );
			orders( root );
			peek( root );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	document.addEventListener( 'bricks/ajax/end', boot );
} )();
