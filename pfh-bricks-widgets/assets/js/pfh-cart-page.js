/**
 * The cart page, changing in place.
 *
 * Every change — a quantity, a removal, an undo, a coupon — goes to the
 * server, which answers with the cart drawn afresh; nothing is priced in the
 * browser, so what the page shows is always what WooCommerce will charge.
 *
 * Quantities wait a moment before they are sent, so tapping + three times is
 * one request, not three. Requests never overlap: a change made while one is
 * out waits for it.
 *
 * The rest of the page is told when the cart changed (the header count, a
 * slide-in cart), and the cart is redrawn when something else changed it —
 * decided by comparing WooCommerce's cart hash, so the two never trigger
 * each other in a loop.
 */
( function () {
	'use strict';

	var cfg = window.pfhCartPage || {};
	var WAIT = 450;

	function closest( el, selector ) {
		return el && el.closest ? el.closest( selector ) : null;
	}

	function cookieHash() {
		var match = document.cookie.match( /(?:^|;\s*)woocommerce_cart_hash=([^;]*)/ );

		return match ? decodeURIComponent( match[ 1 ] ) : '';
	}

	function Cart( root ) {
		this.root = root;
		this.body = root.querySelector( '[data-pfh-cartp-body]' );
		this.live = root.querySelector( '[data-pfh-cartp-live]' );
		this.busy = false;
		this.queue = [];
		this.timers = {};

		this.bind();
	}

	Cart.prototype.bind = function () {
		var self = this;
		var root = this.root;

		root.addEventListener( 'click', function ( event ) {
			var step = closest( event.target, '[data-pfh-cartp-step]' );
			var remove = closest( event.target, '[data-pfh-cartp-remove]' );
			var restore = closest( event.target, '[data-pfh-cartp-restore]' );
			var uncoupon = closest( event.target, '[data-pfh-cartp-uncoupon]' );

			if ( step ) {
				event.preventDefault();
				self.step( step );
			} else if ( remove ) {
				event.preventDefault();
				self.remove( remove );
			} else if ( restore ) {
				event.preventDefault();
				self.send( 'restore', { key: restore.getAttribute( 'data-pfh-cartp-restore' ) } );
			} else if ( uncoupon ) {
				event.preventDefault();
				self.send( 'uncoupon', { code: uncoupon.getAttribute( 'data-pfh-cartp-uncoupon' ) } );
			}
		} );

		root.addEventListener( 'input', function ( event ) {
			var input = closest( event.target, '[data-pfh-cartp-input]' );

			if ( input ) {
				self.later( input, WAIT * 2 );
			}
		} );

		root.addEventListener( 'change', function ( event ) {
			var input = closest( event.target, '[data-pfh-cartp-input]' );

			if ( input ) {
				self.later( input, 0 );
			}
		} );

		root.addEventListener( 'keydown', function ( event ) {
			var input = closest( event.target, '[data-pfh-cartp-input]' );

			if ( input && 'Enter' === event.key ) {
				event.preventDefault();
				self.later( input, 0 );
			}
		} );

		root.addEventListener( 'submit', function ( event ) {
			var coupon = closest( event.target, '[data-pfh-cartp-coupon-form]' );

			if ( coupon ) {
				event.preventDefault();

				var field = coupon.querySelector( '[name="coupon_code"]' );

				self.send( 'coupon', { code: field ? field.value.trim() : '' }, { coupon: true } );
			} else if ( closest( event.target, '[data-pfh-cartp-form]' ) ) {
				// Enter in a quantity: already handled above.
				event.preventDefault();
			}
		} );

		// Changed elsewhere on the page: an add-to-cart below, the header's
		// drawer, a slide-in cart.
		document.addEventListener( 'pfh:added', function () {
			self.refresh();
		} );

		document.addEventListener( 'pfh:cart-changed', function () {
			self.refresh();
		} );

		if ( window.jQuery ) {
			window.jQuery( document.body ).on( 'added_to_cart removed_from_cart', function () {
				self.refresh();
			} );

			window.jQuery( document.body ).on( 'wc_fragments_refreshed wc_fragments_loaded', function () {
				if ( ! self.busy && cookieHash() !== ( root.getAttribute( 'data-pfh-cartp-hash' ) || '' ) ) {
					self.refresh();
				}
			} );
		}
	};

	/**
	 * A quantity input's value, as a whole number of at least zero and at
	 * most the input's max.
	 */
	Cart.prototype.value = function ( input ) {
		var value = parseInt( input.value, 10 );
		var max = parseInt( input.getAttribute( 'max' ), 10 );

		if ( isNaN( value ) || value < 0 ) {
			value = 0;
		}

		if ( max > 0 && value > max ) {
			value = max;
		}

		return value;
	};

	Cart.prototype.step = function ( button ) {
		var box = closest( button, '[data-pfh-cartp-qty]' );
		var input = box ? box.querySelector( '[data-pfh-cartp-input]' ) : null;

		if ( ! input ) {
			return;
		}

		var value = this.value( input ) + parseInt( button.getAttribute( 'data-pfh-cartp-step' ), 10 );
		var max = parseInt( input.getAttribute( 'max' ), 10 );

		input.value = Math.max( 0, max > 0 ? Math.min( value, max ) : value );

		var plus = box.querySelector( '[data-pfh-cartp-step="1"]' );

		if ( plus ) {
			plus.disabled = max > 0 && this.value( input ) >= max;
		}

		this.later( input, WAIT );
	};

	/**
	 * Send a quantity after a pause, so a run of taps is one request.
	 */
	Cart.prototype.later = function ( input, wait ) {
		var self = this;
		var key = input.getAttribute( 'data-pfh-cartp-input' );

		window.clearTimeout( this.timers[ key ] );

		this.timers[ key ] = window.setTimeout( function () {
			delete self.timers[ key ];

			var value = self.value( input );

			input.value = value;

			if ( 0 === value ) {
				var row = closest( input, '[data-pfh-cartp-item]' );

				if ( row ) {
					row.classList.add( 'is-leaving' );
				}
			}

			self.send( 'set', { key: key, quantity: value } );
		}, wait );
	};

	Cart.prototype.remove = function ( link ) {
		var row = closest( link, '[data-pfh-cartp-item]' );

		if ( row ) {
			row.classList.add( 'is-leaving' );
		}

		this.send( 'remove', { key: link.getAttribute( 'data-pfh-cartp-remove' ) } );
	};

	Cart.prototype.refresh = function () {
		this.send( 'refresh', {} );
	};

	/**
	 * One request at a time. Changes made while one is out wait their turn,
	 * in order; a newer quantity for the same line replaces the one waiting,
	 * and a refresh is dropped when something is already waiting (whatever
	 * waits answers with the whole cart anyway).
	 */
	Cart.prototype.send = function ( command, data, flags ) {
		var self = this;

		if ( ! cfg.ajaxUrl ) {
			return;
		}

		if ( this.busy ) {
			if ( 'refresh' === command ) {
				if ( ! this.queue.length ) {
					this.queue.push( [ command, data, flags ] );
				}

				return;
			}

			this.queue = this.queue.filter( function ( job ) {
				return 'refresh' !== job[ 0 ] && ! ( 'set' === command && 'set' === job[ 0 ] && job[ 1 ].key === data.key );
			} );
			this.queue.push( [ command, data, flags ] );

			return;
		}

		this.busy = true;
		this.root.classList.add( 'is-busy' );
		this.root.setAttribute( 'aria-busy', 'true' );

		var body = new FormData();

		body.append( 'action', 'pfh_cart_page' );
		body.append( 'nonce', cfg.nonce || '' );
		body.append( 'ctx', this.root.getAttribute( 'data-pfh-cartp' ) || '' );
		body.append( 'command', command );

		Object.keys( data || {} ).forEach( function ( name ) {
			body.append( name, data[ name ] );
		} );

		window
			.fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json().catch( function () {
					return null;
				} );
			} )
			.then( function ( json ) {
				if ( json && json.success && json.data ) {
					self.paint( json.data, flags || {} );
				} else if ( json && json.data && json.data.reload ) {
					window.location.reload();
				} else {
					self.fail();
				}
			} )
			.catch( function () {
				self.fail();
			} )
			.then( function () {
				self.busy = false;
				self.root.classList.remove( 'is-busy' );
				self.root.removeAttribute( 'aria-busy' );

				if ( self.queue.length ) {
					var queued = self.queue.shift();

					self.send( queued[ 0 ], queued[ 1 ], queued[ 2 ] );
				}
			} );
	};

	Cart.prototype.fail = function () {
		qsaIn( this.root, '.is-leaving' ).forEach( function ( row ) {
			row.classList.remove( 'is-leaving' );
		} );

		this.say( cfg.failed || '' );
	};

	function qsaIn( root, selector ) {
		return Array.prototype.slice.call( root.querySelectorAll( selector ) );
	}

	/**
	 * Swap in the cart as the server drew it, keeping the shopper's place:
	 * the field they were typing in keeps focus, an open coupon field stays
	 * open.
	 */
	Cart.prototype.paint = function ( data, flags ) {
		var active = document.activeElement;
		var focusKey = null;

		if ( active && this.root.contains( active ) ) {
			if ( active.hasAttribute( 'data-pfh-cartp-input' ) ) {
				focusKey = '[data-pfh-cartp-input="' + active.getAttribute( 'data-pfh-cartp-input' ) + '"]';
			} else if ( active.hasAttribute( 'data-pfh-cartp-step' ) ) {
				var row = closest( active, '[data-pfh-cartp-item]' );

				if ( row ) {
					focusKey = '[data-pfh-cartp-item="' + row.getAttribute( 'data-pfh-cartp-item' ) + '"] [data-pfh-cartp-step="' + active.getAttribute( 'data-pfh-cartp-step' ) + '"]';
				}
			}
		}

		var couponOpen = !! this.root.querySelector( '[data-pfh-cartp-coupon][open]' );
		var typed = {};
		var root = this.root;

		// A quantity changed but not yet sent keeps showing what was typed;
		// its own request follows and settles it.
		Object.keys( this.timers ).forEach( function ( key ) {
			var input = root.querySelector( '[data-pfh-cartp-input="' + key + '"]' );

			if ( input ) {
				typed[ key ] = input.value;
			}
		} );

		this.body.innerHTML = data.html;

		Object.keys( typed ).forEach( function ( key ) {
			var input = root.querySelector( '[data-pfh-cartp-input="' + key + '"]' );

			if ( input ) {
				input.value = typed[ key ];
			}
		} );
		this.root.setAttribute( 'data-pfh-cartp-hash', data.hash || '' );

		var notices = this.root.querySelector( '[data-pfh-cartp-notices]' );
		var failed = notices && notices.querySelector( '.woocommerce-error, .is-error' );
		var details = this.root.querySelector( '[data-pfh-cartp-coupon]' );

		if ( details && ( couponOpen || ( flags.coupon && failed ) ) ) {
			details.open = true;
		}

		if ( focusKey ) {
			var again = this.root.querySelector( focusKey );

			if ( again && ! again.disabled ) {
				again.focus( { preventScroll: true } );
			}
		}

		if ( notices && notices.children.length && ( failed || flags.coupon ) ) {
			var box = notices.getBoundingClientRect();

			if ( box.top < 0 || box.top > window.innerHeight ) {
				notices.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			}
		}

		var count = this.root.querySelector( '[data-pfh-cartp-count]' );

		this.say( ( cfg.updated || '' ) + ( count ? '. ' + count.textContent : '' ) );

		// Tell the rest of the page: WooCommerce's fragments (the header
		// count, a slide-in cart) and anything listening for ours.
		if ( window.jQuery ) {
			window.jQuery( document.body ).trigger( 'wc_fragment_refresh' );
		}

		document.dispatchEvent( new CustomEvent( 'pfh:cart-page-updated', { detail: { count: data.count, empty: !! data.empty } } ) );
	};

	Cart.prototype.say = function ( text ) {
		if ( ! this.live ) {
			return;
		}

		// Emptied first, so the same words twice are still announced.
		this.live.textContent = '';

		var live = this.live;

		window.setTimeout( function () {
			live.textContent = text;
		}, 60 );
	};

	function boot() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-pfh-cartp]' ), function ( root ) {
			if ( ! root.pfhCartp ) {
				root.pfhCartp = new Cart( root );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
