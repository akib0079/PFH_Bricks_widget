/**
 * Shop archive: AJAX filtering, faceting and paging.
 *
 * Every control on the page is already a real link or a real input with the
 * filter state in the URL, so the archive works with scripting off. This
 * intercepts those interactions and swaps the results in place instead —
 * the server renders the same markup either way.
 */
( function () {
	'use strict';

	var cfg = window.pfhArchive || {};

	// The wire names come from PHP, so the two halves cannot drift. They are
	// namespaced because WordPress reserves page, cat, order and orderby.
	var KEY = cfg.keys || {
		cat: 'pfh_cat',
		page: 'pfh_page',
		orderby: 'pfh_sort',
		min: 'pfh_min',
		max: 'pfh_max',
		onsale: 'pfh_sale',
		instock: 'pfh_stock'
	};

	var TAX = cfg.tax || 'pfh_tax_';

	function Archive( root ) {
		this.root = root;
		this.id = root.getAttribute( 'data-pfh-archive' );

		this.results = root.querySelector( '[data-pfh-arch-results]' );
		this.panel = root.querySelector( '[data-pfh-arch-panel]' );
		this.scrim = root.querySelector( '[data-pfh-arch-scrim]' );
		this.facets = root.querySelector( '[data-pfh-arch-facets]' );
		this.count = root.querySelector( '[data-pfh-arch-count]' );
		this.badge = root.querySelector( '[data-pfh-arch-badge]' );
		this.clear = root.querySelector( '[data-pfh-arch-clear]' );
		this.opener = root.querySelector( '[data-pfh-arch-open]' );

		this.pending = null;
		this.debounce = null;

		this.state = this.read();
		this.bind();
		this.paintRange( null );
	}

	/* ------------------------------------------------------------------
	 * State
	 * --------------------------------------------------------------- */

	/**
	 * Read the filter state out of the URL, which is the single source of
	 * truth — so a reload, a back button and a shared link all agree.
	 */
	Archive.prototype.read = function () {
		var params = new URLSearchParams( window.location.search );
		var state = { tax: {} };

		var byWire = {};

		Object.keys( KEY ).forEach( function ( internal ) {
			byWire[ KEY[ internal ] ] = internal;
		} );

		params.forEach( function ( value, key ) {
			if ( key.indexOf( TAX ) === 0 ) {
				state.tax[ key.slice( TAX.length ) ] = value.split( ',' ).filter( Boolean );
			} else if ( byWire[ key ] ) {
				state[ byWire[ key ] ] = value;
			}
		} );

		return state;
	};

	/**
	 * The current state as a query string.
	 *
	 * Anything already in the address bar that is not ours is carried through,
	 * so filtering does not quietly drop a campaign tag or a preview token.
	 */
	Archive.prototype.toParams = function () {
		var params = new URLSearchParams( window.location.search );
		var state = this.state;

		// Clear our own keys first, then write back what is set.
		Object.keys( KEY ).forEach( function ( internal ) {
			params.delete( KEY[ internal ] );
		} );

		Array.prototype.forEach.call( Array.from( params.keys() ), function ( key ) {
			if ( key.indexOf( TAX ) === 0 ) {
				params.delete( key );
			}
		} );

		Object.keys( KEY ).forEach( function ( internal ) {
			var value = state[ internal ];

			if ( '' !== value && null !== value && undefined !== value ) {
				params.set( KEY[ internal ], value );
			}
		} );

		Object.keys( state.tax ).forEach( function ( taxonomy ) {
			if ( state.tax[ taxonomy ].length ) {
				params.set( TAX + taxonomy, state.tax[ taxonomy ].join( ',' ) );
			}
		} );

		return params;
	};

	Archive.prototype.set = function ( key, value ) {
		if ( '' === value || null === value || false === value ) {
			delete this.state[ key ];
		} else {
			this.state[ key ] = value;
		}
	};

	Archive.prototype.toggleTerm = function ( taxonomy, slug, on ) {
		var list = this.state.tax[ taxonomy ] || [];
		var at = list.indexOf( slug );

		if ( on && at === -1 ) {
			list.push( slug );
		} else if ( ! on && at > -1 ) {
			list.splice( at, 1 );
		}

		if ( list.length ) {
			this.state.tax[ taxonomy ] = list;
		} else {
			delete this.state.tax[ taxonomy ];
		}
	};

	/* ------------------------------------------------------------------
	 * Fetching
	 * --------------------------------------------------------------- */

	Archive.prototype.busy = function ( on ) {
		this.root.classList.toggle( 'is-loading', on );

		if ( this.results ) {
			this.results.setAttribute( 'aria-busy', on ? 'true' : 'false' );
		}
	};

	/**
	 * Ask the server for this state.
	 *
	 * @param {boolean} keepPanel Leave the filter panel open.
	 * @param {boolean} scroll    Scroll the grid into view when it lands.
	 */
	Archive.prototype.load = function ( keepPanel, scroll ) {
		var self = this;

		if ( ! cfg.ajaxUrl ) {
			window.location.search = this.toParams().toString();
			return;
		}

		// A newer request always wins; an older one must not paint over it.
		if ( this.pending ) {
			this.pending.abort();
		}

		var controller = ( 'AbortController' in window ) ? new AbortController() : null;
		this.pending = controller;

		var body = this.toParams();
		body.set( 'action', 'pfh_archive' );
		body.set( 'nonce', cfg.nonce || '' );
		body.set( 'element', this.id );
		// Which category this page is, signed by the server. Without it the
		// request cannot know: admin-ajax.php is not a category page.
		body.set( 'pfh_ctx', this.root.getAttribute( 'data-pfh-arch-ctx' ) || '' );

		this.busy( true );

		window.fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
			signal: controller ? controller.signal : undefined
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( payload ) {
				if ( ! payload || ! payload.success ) {
					throw new Error( 'archive' );
				}

				return payload.data;
			} )
			.catch( function ( error ) {
				if ( error && 'AbortError' === error.name ) {
					return null;
				}

				// A failed filter must never leave a dead page behind.
				window.location.search = self.toParams().toString();

				return null;
			} )
			.then( function ( data ) {
				/*
				 * Painting happens after the catch, not inside the then
				 * above it: a throw while rendering used to land in that
				 * catch and reload the page, so one bad line in the markup
				 * looked exactly like a filter that does not work — which
				 * is how a cross-origin replaceState presented itself.
				 */
				if ( data ) {
					self.paint( data, keepPanel, scroll );
				}
			} )
			.finally( function () {
				self.pending = null;
				self.busy( false );
			} );
	};

	Archive.prototype.paint = function ( data, keepPanel, scroll ) {
		if ( this.results && 'string' === typeof data.results ) {
			this.results.innerHTML = data.results;
		}

		if ( this.facets && 'string' === typeof data.facets ) {
			this.facets.innerHTML = data.facets;
			this.paintRange( null );
		}

		if ( this.count && 'string' === typeof data.count ) {
			this.count.textContent = data.count;
		}

		if ( this.badge ) {
			this.badge.textContent = data.active ? String( data.active ) : '';
			this.badge.hidden = ! data.active;
		}

		if ( this.clear ) {
			this.clear.hidden = ! data.filtered;
		}

		if ( data.url ) {
			try {
				window.history.replaceState( { pfh: true }, '', data.url );
			} catch ( e ) {
				// A URL the browser will not accept costs the visitor the
				// shareable address, not their filtered results.
			}
		}

		if ( ! keepPanel ) {
			this.close();
		}

		if ( scroll && this.results ) {
			var top = this.results.getBoundingClientRect().top + window.pageYOffset - 90;
			window.scrollTo( { top: Math.max( 0, top ), behavior: 'smooth' } );
		}

		this.root.dispatchEvent(
			new CustomEvent( 'pfh:archive', { bubbles: true, detail: { state: this.state } } )
		);
	};

	/* ------------------------------------------------------------------
	 * Panel
	 * --------------------------------------------------------------- */

	Archive.prototype.open = function () {
		if ( ! this.panel ) {
			return;
		}

		this.panel.hidden = false;

		if ( this.scrim ) {
			this.scrim.hidden = false;
		}

		this.root.classList.add( 'is-panel-open' );
		document.body.classList.add( 'pfh-arch-locked' );

		if ( this.opener ) {
			this.opener.setAttribute( 'aria-expanded', 'true' );
		}

		var focusable = this.panel.querySelector( 'button, input' );

		if ( focusable ) {
			focusable.focus( { preventScroll: true } );
		}
	};

	Archive.prototype.close = function () {
		if ( ! this.panel || this.panel.hidden ) {
			return;
		}

		this.panel.hidden = true;

		if ( this.scrim ) {
			this.scrim.hidden = true;
		}

		this.root.classList.remove( 'is-panel-open' );
		document.body.classList.remove( 'pfh-arch-locked' );

		if ( this.opener ) {
			this.opener.setAttribute( 'aria-expanded', 'false' );
			this.opener.focus( { preventScroll: true } );
		}
	};

	/* ------------------------------------------------------------------
	 * Price range
	 * --------------------------------------------------------------- */

	/**
	 * The two handles, kept in order.
	 */
	Archive.prototype.rangePair = function () {
		var wrap = this.root.querySelector( '[data-pfh-arch-range]' );

		if ( ! wrap ) {
			return null;
		}

		var lo = wrap.querySelector( '[data-pfh-arch-min]' );
		var hi = wrap.querySelector( '[data-pfh-arch-max]' );

		if ( ! lo || ! hi ) {
			return null;
		}

		return {
			wrap: wrap,
			lo: parseInt( lo.value, 10 ),
			hi: parseInt( hi.value, 10 ),
			floor: parseInt( lo.min, 10 ),
			ceiling: parseInt( hi.max, 10 ),
			loEl: lo,
			hiEl: hi
		};
	};

	/**
	 * Keep the handles from crossing, then redraw the filled segment.
	 *
	 * @param {Element} moved The input the visitor is dragging.
	 */
	Archive.prototype.paintRange = function ( moved ) {
		var p = this.rangePair();

		if ( ! p ) {
			return;
		}

		if ( p.lo > p.hi ) {
			if ( moved === p.loEl ) {
				p.loEl.value = p.hi;
				p.lo = p.hi;
			} else {
				p.hiEl.value = p.lo;
				p.hi = p.lo;
			}
		}

		var span = Math.max( 1, p.ceiling - p.floor );
		var symbol = p.wrap.getAttribute( 'data-symbol' ) || '';

		p.wrap.style.setProperty( '--pfh-lo', ( ( p.lo - p.floor ) / span ) * 100 + '%' );
		p.wrap.style.setProperty( '--pfh-hi', ( ( p.hi - p.floor ) / span ) * 100 + '%' );

		var out = p.wrap.querySelectorAll( '[data-pfh-arch-out]' );

		Array.prototype.forEach.call( out, function ( node ) {
			var which = node.getAttribute( 'data-pfh-arch-out' );
			node.textContent = symbol + ( 'min' === which ? p.lo : p.hi );
		} );
	};

	/* ------------------------------------------------------------------
	 * Events
	 * --------------------------------------------------------------- */

	Archive.prototype.bind = function () {
		var self = this;

		this.root.addEventListener( 'click', function ( event ) {
			if ( ! event.target || 'function' !== typeof event.target.closest ) {
				return;
			}

			// Category pills are real archive links and are left to navigate:
			// the title, breadcrumb and description have to change with them,
			// and only a full request does that.
			var page = event.target.closest( '[data-pfh-arch-page]' );

			if ( page && self.root.contains( page ) ) {
				event.preventDefault();

				if ( page.classList.contains( 'is-disabled' ) ) {
					return;
				}

				self.set( 'page', page.getAttribute( 'data-pfh-arch-page' ) );
				self.load( false, true );
				return;
			}

			if ( event.target.closest( '[data-pfh-arch-open]' ) ) {
				event.preventDefault();
				self.open();
				return;
			}

			if ( event.target.closest( '[data-pfh-arch-close]' ) || event.target.closest( '[data-pfh-arch-scrim]' ) ) {
				event.preventDefault();
				self.close();
				return;
			}

			if ( event.target.closest( '[data-pfh-arch-apply]' ) ) {
				event.preventDefault();
				self.close();
				return;
			}

			if ( event.target.closest( '[data-pfh-arch-clear]' ) ) {
				event.preventDefault();
				self.state = { cat: self.state.cat || '', tax: {} };
				self.load( true, false );
				return;
			}

			var head = event.target.closest( '[data-pfh-arch-toggle]' );

			if ( head ) {
				event.preventDefault();

				var facet = head.closest( '[data-pfh-arch-facet]' );
				var body = facet && facet.querySelector( '.pfh-arch__facet-body' );
				var open = 'true' === head.getAttribute( 'aria-expanded' );

				head.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
				facet.classList.toggle( 'is-open', ! open );

				if ( body ) {
					body.hidden = open;
				}

				return;
			}

			var more = event.target.closest( '[data-pfh-arch-more]' );

			if ( more ) {
				event.preventDefault();

				var group = more.closest( '[data-pfh-arch-facet]' );
				var shown = 'true' === more.getAttribute( 'aria-expanded' );

				Array.prototype.forEach.call(
					group.querySelectorAll( '[data-pfh-arch-extra]' ),
					function ( item ) {
						item.hidden = shown;
					}
				);

				more.setAttribute( 'aria-expanded', shown ? 'false' : 'true' );
				more.classList.toggle( 'is-open', ! shown );
			}
		} );

		this.root.addEventListener( 'change', function ( event ) {
			var target = event.target;

			if ( ! target ) {
				return;
			}

			if ( target.hasAttribute( 'data-pfh-arch-tax' ) ) {
				self.toggleTerm( target.getAttribute( 'data-pfh-arch-tax' ), target.value, target.checked );
				self.set( 'page', '' );
				self.load( true, false );
				return;
			}

			if ( target.hasAttribute( 'data-pfh-arch-flag' ) ) {
				self.set( target.getAttribute( 'data-pfh-arch-flag' ), target.checked ? 1 : '' );
				self.set( 'page', '' );
				self.load( true, false );
				return;
			}

			if ( target.hasAttribute( 'data-pfh-arch-sort' ) ) {
				self.set( 'orderby', target.value );
				self.set( 'page', '' );
				self.load( false, false );
			}
		} );

		// The range slider paints on every move but only fetches once the
		// handle settles — otherwise a single drag is fifty requests.
		this.root.addEventListener( 'input', function ( event ) {
			var target = event.target;

			if ( ! target || ( ! target.hasAttribute( 'data-pfh-arch-min' ) && ! target.hasAttribute( 'data-pfh-arch-max' ) ) ) {
				return;
			}

			self.paintRange( target );

			window.clearTimeout( self.debounce );

			self.debounce = window.setTimeout( function () {
				var pair = self.rangePair();

				if ( ! pair ) {
					return;
				}

				// A value at the very end of the scale is not a filter.
				self.set( 'min', pair.lo > pair.floor ? pair.lo : '' );
				self.set( 'max', pair.hi < pair.ceiling ? pair.hi : '' );
				self.set( 'page', '' );
				self.load( true, false );
			}, 420 );
		} );

		this.root.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				self.close();
			}
		} );

		// Back and forward through filtered views.
		window.addEventListener( 'popstate', function () {
			self.state = self.read();
			self.load( false, false );
		} );
	};

	/* ------------------------------------------------------------------
	 * The category track
	 * --------------------------------------------------------------- */

	/**
	 * Say when there are more categories than the row shows.
	 *
	 * Measured rather than assumed: a class goes on for each side that has
	 * something behind it, and the fade and the arrow for that side follow.
	 * A row whose categories all fit gets neither. Independent of the
	 * filter request, so it works with or without the AJAX layer.
	 */
	function cats( wrap ) {
		var track = wrap.querySelector( '[data-pfh-cats-track]' );
		var prev = wrap.querySelector( '[data-pfh-cats-prev]' );
		var next = wrap.querySelector( '[data-pfh-cats-next]' );

		if ( ! track ) {
			return;
		}

		var smooth = ! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

		function measure() {
			// A pixel of slack either way: fractional widths never quite meet.
			var left = track.scrollLeft > 1;
			var right = track.scrollLeft + track.clientWidth < track.scrollWidth - 1;

			wrap.classList.toggle( 'can-prev', left );
			wrap.classList.toggle( 'can-next', right );
		}

		function step( direction ) {
			var by = Math.max( 120, track.clientWidth * 0.7 ) * direction;

			if ( track.scrollBy ) {
				track.scrollBy( { left: by, behavior: smooth ? 'smooth' : 'auto' } );
			} else {
				track.scrollLeft += by;
			}
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				step( -1 );
			} );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				step( 1 );
			} );
		}

		/*
		 * The category being viewed may sit past the edge — the ninth of
		 * twelve on a phone. Bring it into the middle on arrival, without
		 * animating, so the page opens showing where the visitor is.
		 */
		var active = track.querySelector( '.is-active' );

		if ( active && active.offsetLeft + active.offsetWidth > track.clientWidth ) {
			var before = track.style.scrollBehavior;

			track.style.scrollBehavior = 'auto';
			track.scrollLeft = active.offsetLeft - ( track.clientWidth - active.offsetWidth ) / 2;
			track.style.scrollBehavior = before;
		}

		track.addEventListener( 'scroll', measure, { passive: true } );
		window.addEventListener( 'resize', measure, { passive: true } );

		// Web fonts change every pill's width once they land.
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( measure );
		}

		if ( 'ResizeObserver' in window ) {
			new window.ResizeObserver( measure ).observe( track );
		}

		measure();
	}

	function start() {
		Array.prototype.forEach.call(
			document.querySelectorAll( '[data-pfh-archive]' ),
			function ( root ) {
				if ( ! root.pfhArchive ) {
					root.pfhArchive = new Archive( root );
				}
			}
		);

		Array.prototype.forEach.call(
			document.querySelectorAll( '[data-pfh-arch-cats]' ),
			function ( wrap ) {
				if ( ! wrap.pfhCats ) {
					wrap.pfhCats = true;
					cats( wrap );
				}
			}
		);
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
