/**
 * Products For Home – header element.
 *
 * Exposes window.pfhHeaderInit() so Bricks can re-run it after an in-builder
 * re-render, and self-initialises on the front end.
 */
( function () {
	'use strict';

	var OPEN_PANELS = [];

	/* ----------------------------------------------------------------- *
	 * Small helpers
	 * ----------------------------------------------------------------- */

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function ajaxConfig() {
		return window.pfhWidgets || { ajaxUrl: '', nonce: '' };
	}

	function debounce( fn, wait ) {
		var timer = null;

		return function () {
			var args = arguments;
			var self = this;

			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				fn.apply( self, args );
			}, wait );
		};
	}

	function lockScroll( lock ) {
		var root = document.documentElement;

		if ( lock ) {
			var width = window.innerWidth - root.clientWidth;

			root.style.setProperty( '--pfh-sbw', width > 0 ? width + 'px' : '0px' );
			root.classList.add( 'pfh-locked' );
			document.body.classList.add( 'pfh-locked' );

			return;
		}

		root.classList.remove( 'pfh-locked' );
		document.body.classList.remove( 'pfh-locked' );
		root.style.removeProperty( '--pfh-sbw' );
	}

	var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

	function trapFocus( panel, event ) {
		var nodes = qsa( FOCUSABLE, panel ).filter( function ( node ) {
			return node.offsetParent !== null || node === document.activeElement;
		} );

		if ( ! nodes.length ) {
			return;
		}

		var first = nodes[ 0 ];
		var last = nodes[ nodes.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	/* ----------------------------------------------------------------- *
	 * Panel (search / cart / mobile) controller
	 * ----------------------------------------------------------------- */

	function Panel( element, header ) {
		this.el = element;
		this.header = header;
		this.name = element.getAttribute( 'data-pfh-panel' );
		this.trigger = null;
		this.isOpen = false;

		var self = this;

		element.removeAttribute( 'hidden' );

		qsa( '[data-pfh-close]', element ).forEach( function ( node ) {
			node.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				self.close();
			} );
		} );

		element.addEventListener( 'keydown', function ( event ) {
			if ( 'Tab' === event.key && self.isOpen ) {
				trapFocus( element, event );
			}
		} );
	}

	Panel.prototype.open = function ( trigger ) {
		if ( this.isOpen ) {
			return;
		}

		this.trigger = trigger || null;
		this.isOpen = true;
		this.el.classList.add( 'is-open' );
		this.el.setAttribute( 'aria-hidden', 'false' );

		if ( this.trigger ) {
			this.trigger.setAttribute( 'aria-expanded', 'true' );
		}

		OPEN_PANELS.push( this );
		lockScroll( true );

		var focusTarget = this.el.querySelector( '[data-pfh-search-input]' ) || this.el.querySelector( FOCUSABLE );

		if ( focusTarget ) {
			window.setTimeout( function () {
				focusTarget.focus();
			}, 80 );
		}

		this.el.dispatchEvent( new CustomEvent( 'pfh:open', { bubbles: true } ) );
	};

	Panel.prototype.close = function () {
		if ( ! this.isOpen ) {
			return;
		}

		this.isOpen = false;
		this.el.classList.remove( 'is-open' );
		this.el.setAttribute( 'aria-hidden', 'true' );

		if ( this.trigger ) {
			this.trigger.setAttribute( 'aria-expanded', 'false' );

			/*
			 * preventScroll matters when the drawer was opened from an add to
			 * cart half way down the page: the trigger is the header's cart
			 * button, and returning focus to it without this scrolls it into
			 * view — which reads as the page jumping to the top on close.
			 */
			try {
				this.trigger.focus( { preventScroll: true } );
			} catch ( error ) {
				this.trigger.focus();
			}

			this.trigger = null;
		}

		OPEN_PANELS = OPEN_PANELS.filter( function ( panel ) {
			return panel.isOpen;
		} );

		if ( ! OPEN_PANELS.length ) {
			lockScroll( false );
		}

		this.el.dispatchEvent( new CustomEvent( 'pfh:close', { bubbles: true } ) );
	};

	/* ----------------------------------------------------------------- *
	 * Header
	 * ----------------------------------------------------------------- */

	function Header( root ) {
		this.root = root;
		this.uid = root.getAttribute( 'data-pfh-header' ) || '';
		this.config = this.readConfig();
		this.panels = {};
		this.megaTimer = null;
		this.openItem = null;

		this.movePortal();
		this.bindMega();
		this.bindTopbar();
		this.bindSticky();
		this.bindTriggers();
		this.bindSearch();
		this.bindCart();
		this.bindMobile();
		this.bindGlobalKeys();
	}

	Header.prototype.readConfig = function () {
		var defaults = {
			megaTrigger: 'hover',
			megaDelay: 90,
			megaOverlay: true,
			breakpoint: 991,
			topbarInterval: 5000,
			search: { live: true, limit: 6, postType: 'product', emptyText: '' },
			cart: { enabled: false, openOnAdd: true, labels: {} }
		};

		try {
			var parsed = JSON.parse( this.root.getAttribute( 'data-pfh-config' ) || '{}' );

			return Object.assign( {}, defaults, parsed, {
				search: Object.assign( {}, defaults.search, parsed.search || {} ),
				cart: Object.assign( {}, defaults.cart, parsed.cart || {} )
			} );
		} catch ( error ) {
			return defaults;
		}
	};

	/**
	 * Move the overlays to <body> so no transformed ancestor can clip them.
	 * The inline custom properties travel with the node, so styling survives.
	 */
	Header.prototype.movePortal = function () {
		var portal = this.root.querySelector( '[data-pfh-portal]' );

		if ( ! portal ) {
			this.portal = document.querySelector( 'body > .pfh-portal[data-pfh-portal="' + this.uid + '"]' );

			return;
		}

		qsa( 'body > .pfh-portal[data-pfh-portal="' + this.uid + '"]' ).forEach( function ( stale ) {
			if ( stale !== portal ) {
				stale.remove();
			}
		} );

		document.body.appendChild( portal );
		this.portal = portal;

		var self = this;
		var scrim = portal.querySelector( '[data-pfh-scrim]' );

		if ( scrim ) {
			scrim.removeAttribute( 'hidden' );
			scrim.addEventListener( 'click', function () {
				self.closeMega();
			} );
			this.scrim = scrim;
		}

		qsa( '[data-pfh-panel]', portal ).forEach( function ( element ) {
			self.panels[ element.getAttribute( 'data-pfh-panel' ) ] = new Panel( element, self );
		} );
	};

	/* -------------------------------------------------- Mega menu ---- */

	Header.prototype.bindMega = function () {
		var self = this;
		var items = qsa( '[data-pfh-mega-item]', this.root );

		if ( ! items.length ) {
			return;
		}

		items.forEach( function ( item ) {
			var panel = item.querySelector( '[data-pfh-mega]' );
			var link = item.querySelector( '.pfh-nav__link' );

			if ( ! panel || ! link ) {
				return;
			}

			panel.removeAttribute( 'hidden' );

			if ( 'click' === self.config.megaTrigger ) {
				link.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					self.toggleMega( item );
				} );
			} else {
				item.addEventListener( 'mouseenter', function () {
					self.scheduleMega( item );
				} );

				item.addEventListener( 'mouseleave', function () {
					self.scheduleMega( null );
				} );

				// Coarse pointers get a first-tap-opens behaviour.
				link.addEventListener( 'click', function ( event ) {
					if ( window.matchMedia( '(hover: hover)' ).matches ) {
						return;
					}

					if ( ! item.classList.contains( 'is-open' ) ) {
						event.preventDefault();
						self.openMega( item );
					}
				} );
			}

			// Keyboard: open on focus, close when focus leaves the item.
			link.addEventListener( 'focus', function () {
				self.openMega( item );
			} );

			item.addEventListener( 'focusout', function ( event ) {
				if ( ! item.contains( event.relatedTarget ) ) {
					self.closeMega();
				}
			} );
		} );

		this.root.addEventListener( 'mouseleave', function () {
			self.scheduleMega( null );
		} );
	};

	Header.prototype.scheduleMega = function ( item ) {
		var self = this;

		window.clearTimeout( this.megaTimer );

		this.megaTimer = window.setTimeout( function () {
			if ( item ) {
				self.openMega( item );
			} else {
				self.closeMega();
			}
		}, item ? this.config.megaDelay : Math.max( this.config.megaDelay, 120 ) );
	};

	Header.prototype.openMega = function ( item ) {
		if ( this.openItem === item ) {
			return;
		}

		this.closeMega( true );

		this.openItem = item;
		item.classList.add( 'is-open' );

		var link = item.querySelector( '.pfh-nav__link' );

		if ( link ) {
			link.setAttribute( 'aria-expanded', 'true' );
		}

		if ( this.config.megaOverlay && this.scrim ) {
			this.scrim.classList.add( 'is-open' );
		}

		this.root.classList.add( 'pfh-mega-open' );
	};

	Header.prototype.closeMega = function ( keepScrim ) {
		window.clearTimeout( this.megaTimer );

		if ( this.openItem ) {
			this.openItem.classList.remove( 'is-open' );

			var link = this.openItem.querySelector( '.pfh-nav__link' );

			if ( link ) {
				link.setAttribute( 'aria-expanded', 'false' );
			}

			this.openItem = null;
		}

		if ( ! keepScrim ) {
			if ( this.scrim ) {
				this.scrim.classList.remove( 'is-open' );
			}

			this.root.classList.remove( 'pfh-mega-open' );
		}
	};

	Header.prototype.toggleMega = function ( item ) {
		if ( this.openItem === item ) {
			this.closeMega();
		} else {
			this.openMega( item );
		}
	};

	/* --------------------------------------------------- Topbar ------ */

	Header.prototype.bindTopbar = function () {
		var items = qsa( '[data-pfh-topbar-item]', this.root );

		if ( items.length < 2 ) {
			return;
		}

		var index = 0;

		window.setInterval( function () {
			items[ index ].classList.remove( 'is-active' );
			index = ( index + 1 ) % items.length;
			items[ index ].classList.add( 'is-active' );
		}, this.config.topbarInterval );
	};

	/* --------------------------------------------------- Sticky ------ */

	Header.prototype.bindSticky = function () {
		if ( ! this.root.classList.contains( 'has-scroll-shadow' ) ) {
			return;
		}

		var root = this.root;
		var ticking = false;

		function update() {
			root.classList.toggle( 'is-scrolled', window.scrollY > 4 );
			ticking = false;
		}

		window.addEventListener(
			'scroll',
			function () {
				if ( ! ticking ) {
					ticking = true;
					window.requestAnimationFrame( update );
				}
			},
			{ passive: true }
		);

		update();
	};

	/* ------------------------------------------------- Triggers ------ */

	Header.prototype.bindTriggers = function () {
		var self = this;

		qsa( '[data-pfh-open]', this.root ).forEach( function ( button ) {
			button.addEventListener( 'click', function ( event ) {
				var name = button.getAttribute( 'data-pfh-open' );
				var panel = self.panels[ name ];

				if ( ! panel ) {
					return;
				}

				event.preventDefault();
				self.closeMega();
				panel.open( button );
			} );
		} );
	};

	Header.prototype.bindGlobalKeys = function () {
		var self = this;

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' !== event.key ) {
				return;
			}

			if ( OPEN_PANELS.length ) {
				OPEN_PANELS[ OPEN_PANELS.length - 1 ].close();

				return;
			}

			self.closeMega();
		} );
	};

	/* --------------------------------------------------- Search ------ */

	Header.prototype.bindSearch = function () {
		var panel = this.panels.search;

		if ( ! panel || ! this.config.search.live ) {
			return;
		}

		var input = panel.el.querySelector( '[data-pfh-search-input]' );
		var output = panel.el.querySelector( '[data-pfh-search-output]' );
		var config = this.config.search;

		if ( ! input || ! output ) {
			return;
		}

		var controller = null;

		var run = debounce( function () {
			var term = input.value.trim();

			if ( controller ) {
				controller.abort();
			}

			if ( term.length < 2 ) {
				output.innerHTML = '';
				output.classList.remove( 'is-loading' );

				return;
			}

			var settings = ajaxConfig();

			if ( ! settings.ajaxUrl ) {
				return;
			}

			var body = new FormData();

			body.append( 'action', 'pfh_search' );
			body.append( 'nonce', settings.nonce );
			body.append( 'term', term );
			body.append( 'postType', config.postType );
			body.append( 'limit', config.limit );

			controller = window.AbortController ? new AbortController() : null;
			output.classList.add( 'is-loading' );

			window
				.fetch( settings.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: body,
					signal: controller ? controller.signal : undefined
				} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					output.classList.remove( 'is-loading' );

					if ( ! json || ! json.success ) {
						return;
					}

					if ( json.data.html ) {
						output.innerHTML = json.data.html;
					} else {
						output.innerHTML = config.emptyText
							? '<p class="pfh-search__message">' + config.emptyText + '</p>'
							: '';
					}
				} )
				.catch( function ( error ) {
					if ( ! error || 'AbortError' !== error.name ) {
						output.classList.remove( 'is-loading' );
					}
				} );
		}, 260 );

		input.addEventListener( 'input', run );

		panel.el.addEventListener( 'pfh:close', function () {
			output.innerHTML = '';
			input.value = '';
		} );
	};

	/* ----------------------------------------------------- Cart ------ */

	Header.prototype.bindCart = function () {
		var panel = this.panels.cart;

		if ( ! panel || ! this.config.cart.enabled ) {
			return;
		}

		var self = this;

		panel.el.addEventListener( 'click', function ( event ) {
			var remove = event.target.closest( '[data-pfh-cart-remove]' );

			if ( remove ) {
				event.preventDefault();
				self.updateCart( 'remove', remove.getAttribute( 'data-pfh-cart-remove' ) );

				return;
			}

			var step = event.target.closest( '[data-pfh-cart-step]' );

			if ( ! step ) {
				return;
			}

			event.preventDefault();

			var row = step.closest( '[data-pfh-cart-item]' );
			var value = row ? row.querySelector( '[data-pfh-cart-qty]' ) : null;
			var quantity = value ? parseInt( value.textContent, 10 ) : 0;

			if ( isNaN( quantity ) ) {
				return;
			}

			self.updateCart( 'set', step.getAttribute( 'data-key' ), quantity + parseInt( step.getAttribute( 'data-pfh-cart-step' ), 10 ) );
		} );

		/*
		 * Our own quick-add fires this, so the drawer opens without needing
		 * jQuery or WooCommerce's loop script on the page at all.
		 */
		document.addEventListener( 'pfh:added', function () {
			/*
			 * Open first, refresh second. Waiting for the contents would mean
			 * two sequential round trips before anything visible happens; the
			 * drawer slides in on its own busy state and fills a moment later,
			 * which is what makes the add feel immediate.
			 */
			if ( self.config.cart.openOnAdd ) {
				var panel = self.panels.cart;

				if ( panel && ! panel.el.classList.contains( 'is-open' ) ) {
					panel.open( self.root.querySelector( '[data-pfh-open="cart"]' ) );
				}
			}

			self.updateCart( 'refresh' );
		} );

		if ( window.jQuery ) {
			window.jQuery( document.body ).on( 'added_to_cart', function () {
				self.updateCart( 'refresh', '', 0, self.config.cart.openOnAdd );
			} );

			window.jQuery( document.body ).on( 'wc_fragments_refreshed wc_fragments_loaded removed_from_cart updated_wc_div', function () {
				self.updateCart( 'refresh' );
			} );
		}
	};

	Header.prototype.updateCart = function ( command, key, quantity, openAfter ) {
		var panel = this.panels.cart;
		var settings = ajaxConfig();

		if ( ! panel || ! settings.ajaxUrl ) {
			return;
		}

		var body = new FormData();
		var labels = this.config.cart.labels || {};

		body.append( 'action', 'pfh_cart' );
		body.append( 'nonce', settings.nonce );
		body.append( 'command', command || 'refresh' );
		body.append( 'key', key || '' );

		if ( 'undefined' !== typeof quantity ) {
			body.append( 'quantity', quantity );
		}

		Object.keys( labels ).forEach( function ( name ) {
			body.append( 'labels[' + name + ']', labels[ name ] );
		} );

		var self = this;

		panel.el.classList.add( 'is-busy' );

		window
			.fetch( settings.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				panel.el.classList.remove( 'is-busy' );

				if ( ! json || ! json.success ) {
					return;
				}

				self.paintCart( json.data );

				// A change made here, not just a look: tell the page, so a
				// cart page underneath the drawer redraws too.
				if ( 'refresh' !== ( command || 'refresh' ) ) {
					document.dispatchEvent( new CustomEvent( 'pfh:cart-changed', { detail: { count: json.data.count } } ) );
				}

				if ( openAfter ) {
					var trigger = self.root.querySelector( '[data-pfh-open="cart"]' );

					panel.open( trigger );
				}
			} )
			.catch( function () {
				panel.el.classList.remove( 'is-busy' );
			} );
	};

	Header.prototype.paintCart = function ( data ) {
		var panel = this.panels.cart;

		if ( ! panel ) {
			return;
		}

		var bodyEl = panel.el.querySelector( '[data-pfh-cart-body]' );
		var footEl = panel.el.querySelector( '[data-pfh-cart-foot]' );
		var countEl = panel.el.querySelector( '[data-pfh-cart-count-text]' );

		if ( bodyEl && 'undefined' !== typeof data.body ) {
			bodyEl.innerHTML = data.body;
		}

		if ( footEl && 'undefined' !== typeof data.foot ) {
			footEl.innerHTML = data.foot;
		}

		if ( countEl ) {
			countEl.textContent = '(' + data.count + ')';
		}

		qsa( '[data-pfh-cart-count]', this.root ).forEach( function ( badge ) {
			badge.textContent = data.count;
			badge.classList.toggle( 'is-empty', ! data.count );
		} );
	};

	/* --------------------------------------------------- Mobile ------ */

	Header.prototype.bindMobile = function () {
		var panel = this.panels.mobile;

		if ( ! panel ) {
			return;
		}

		qsa( '[data-pfh-accordion]', panel.el ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var target = document.getElementById( button.getAttribute( 'data-pfh-accordion' ) );

				if ( ! target ) {
					return;
				}

				var open = button.getAttribute( 'aria-expanded' ) === 'true';

				button.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
				target.hidden = open;
			} );
		} );

		// Close the drawer once the viewport grows past the breakpoint.
		var config = this.config;

		window.addEventListener( 'resize', debounce( function () {
			if ( window.innerWidth > config.breakpoint ) {
				panel.close();
			}
		}, 200 ) );
	};

	/* ----------------------------------------------------------------- *
	 * Boot
	 * ----------------------------------------------------------------- */

	function init() {
		qsa( '[data-pfh-header]' ).forEach( function ( root ) {
			if ( root.pfhHeader ) {
				return;
			}

			root.pfhHeader = new Header( root );
		} );
	}

	window.pfhHeaderInit = init;

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
