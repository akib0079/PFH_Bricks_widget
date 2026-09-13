/**
 * Products For Home – review slider.
 *
 * The track is a native overflow-x scroller holding a grid that is
 * `--pfh-rv-rows` deep and flows into columns. Arrows, dots and pointer drag
 * all write to the same scrollLeft, so momentum, trackpad and keyboard
 * scrolling keep working for free.
 *
 * Rows and columns come from the computed grid rather than from markup, so a
 * breakpoint that changes either is picked up on resize and the dots recount
 * themselves.
 */
( function () {
	'use strict';

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function clamp( value, min, max ) {
		return Math.max( min, Math.min( max, value ) );
	}

	function Reviews( root ) {
		this.root = root;
		this.viewport = root.querySelector( '[data-pfh-viewport]' );
		this.track = root.querySelector( '[data-pfh-track]' );
		this.dots = root.querySelector( '[data-pfh-dots]' );
		this.prev = root.querySelector( '[data-pfh-prev]' );
		this.next = root.querySelector( '[data-pfh-next]' );

		if ( ! this.viewport || ! this.track ) {
			return;
		}

		this.cards = qsa( '[data-pfh-card]', this.track );
		this.page = 0;
		this.pages = 1;

		this.bindArrows();
		this.bindScroll();
		this.bindDrag();
		this.bindResize();
		this.sync();
	}

	/** Rows in view, read from the live grid rather than from markup. */
	Reviews.prototype.rows = function () {
		var rows = ( window.getComputedStyle( this.track ).gridTemplateRows || '' )
			.split( ' ' )
			.filter( Boolean ).length;

		return Math.max( 1, rows );
	};

	/** Columns in view, derived from the column pitch. */
	Reviews.prototype.columns = function () {
		var step = this.columnStep();

		if ( step <= 0 ) {
			return 1;
		}

		return Math.max( 1, Math.round( ( this.viewport.clientWidth + this.gap() ) / step ) );
	};

	/**
	 * Cards visible at once.
	 *
	 * @return {number} Rows x columns, at least 1.
	 */
	Reviews.prototype.perView = function () {
		return Math.max( 1, this.rows() * this.columns() );
	};

	Reviews.prototype.gap = function () {
		var gap = parseFloat( window.getComputedStyle( this.track ).columnGap );

		return isNaN( gap ) ? 0 : gap;
	};

	/**
	 * Distance from one column's start to the next.
	 *
	 * @return {number} Pixels.
	 */
	Reviews.prototype.columnStep = function () {
		if ( ! this.cards.length ) {
			return 0;
		}

		var first = this.cards[ 0 ].getBoundingClientRect();

		return first.width + this.gap();
	};

	Reviews.prototype.maxScroll = function () {
		return Math.max( 0, this.viewport.scrollWidth - this.viewport.clientWidth );
	};

	/** A full page is everything on screen plus the gutter that follows it. */
	Reviews.prototype.pageStep = function () {
		return this.viewport.clientWidth + this.gap();
	};

	/* --------------------------------------------------------------- state */

	/**
	 * Make a column-flowing grid read left to right.
	 *
	 * `grid-auto-flow: column` fills top-to-bottom then across, so cards in
	 * document order would land in the wrong visual slots. Auto-placement uses
	 * order-modified document order, so re-ordering the items fixes the
	 * reading order without touching the markup — and because rows and columns
	 * are read from the live grid, it re-solves itself at every breakpoint.
	 *
	 * @param {number} rows    Rows in view.
	 * @param {number} cols    Columns in view.
	 * @param {number} perView rows x cols.
	 */
	Reviews.prototype.applyOrder = function ( rows, cols, perView ) {
		if ( this.orderedFor === rows + 'x' + cols ) {
			return;
		}

		this.orderedFor = rows + 'x' + cols;

		this.cards.forEach( function ( card, i ) {
			var page = Math.floor( i / perView );
			var k = i % perView;
			var row = Math.floor( k / cols );
			var col = k % cols;

			card.style.order = String( page * perView + ( col * rows ) + row );
		} );
	};

	Reviews.prototype.sync = function () {
		var cols = this.columns();
		var rows = this.rows();
		var perView = Math.max( 1, rows * cols );
		var pages = Math.max( 1, Math.ceil( this.cards.length / perView ) );
		var scrollable = this.maxScroll() > 1;

		this.applyOrder( rows, cols, perView );

		this.root.classList.toggle( 'is-static', ! scrollable );
		this.root.classList.toggle( 'is-draggable', scrollable );

		if ( pages !== this.pages ) {
			this.pages = pages;
			this.buildDots();
		}

		this.update();
	};

	Reviews.prototype.update = function () {
		var step = this.pageStep();
		var max = this.maxScroll();
		var left = this.viewport.scrollLeft;
		var index = step > 0 ? Math.round( left / step ) : 0;

		// The last page is usually short of a full step, so snap the reading
		// to it once the scroller has bottomed out.
		if ( max > 0 && left >= max - 2 ) {
			index = this.pages - 1;
		}

		this.page = clamp( index, 0, this.pages - 1 );

		if ( this.counter ) {
			this.counter.textContent = ( this.page + 1 ) + ' / ' + this.pages;
		} else if ( this.dots ) {
			qsa( 'button', this.dots ).forEach( function ( dot, i ) {
				var on = i === this.page;
				dot.classList.toggle( 'is-active', on );
				dot.setAttribute( 'aria-current', on ? 'true' : 'false' );
			}, this );
		}

		if ( this.prev ) {
			this.prev.disabled = left <= 1;
		}

		if ( this.next ) {
			this.next.disabled = max <= 1 || left >= max - 2;
		}
	};

	/**
	 * One dot per page — until there are too many to read.
	 *
	 * A phone showing one card at a time turns twenty reviews into twenty
	 * dots, which is noise rather than navigation, so past the cap the dots
	 * become a plain "3 / 20" counter and the arrows do the work.
	 */
	Reviews.prototype.buildDots = function () {
		if ( ! this.dots ) {
			return;
		}

		var max = parseInt( this.dots.getAttribute( 'data-pfh-max-dots' ), 10 );
		max = isNaN( max ) || max < 1 ? 8 : max;

		var label = this.dots.getAttribute( 'data-pfh-dot-label' ) || 'Go to slide %d';

		this.dots.innerHTML = '';
		this.counter = null;
		this.dots.classList.toggle( 'is-counter', this.pages > max );

		if ( this.pages > max ) {
			this.counter = document.createElement( 'span' );
			this.counter.className = 'pfh-rev__count';
			this.dots.appendChild( this.counter );

			return;
		}

		for ( var i = 0; i < this.pages; i++ ) {
			var dot = document.createElement( 'button' );
			dot.type = 'button';
			dot.className = 'pfh-rev__dot';
			dot.setAttribute( 'aria-label', label.replace( '%d', i + 1 ) );
			dot.setAttribute( 'data-pfh-dot', String( i ) );
			this.dots.appendChild( dot );
		}

		if ( this.dotsBound ) {
			return;
		}

		this.dotsBound = true;

		var self = this;

		this.dots.addEventListener( 'click', function ( event ) {
			var dot = event.target.closest( '[data-pfh-dot]' );

			if ( dot ) {
				self.goTo( parseInt( dot.getAttribute( 'data-pfh-dot' ), 10 ) || 0 );
			}
		} );
	};

	/* ------------------------------------------------------------ movement */

	Reviews.prototype.goTo = function ( index ) {
		var target = clamp( index, 0, this.pages - 1 ) * this.pageStep();

		this.viewport.scrollTo( { left: clamp( target, 0, this.maxScroll() ), behavior: 'smooth' } );
	};

	Reviews.prototype.bindArrows = function () {
		var self = this;

		if ( this.prev ) {
			this.prev.addEventListener( 'click', function () {
				self.goTo( self.page - 1 );
			} );
		}

		if ( this.next ) {
			this.next.addEventListener( 'click', function () {
				self.goTo( self.page + 1 );
			} );
		}
	};

	Reviews.prototype.bindScroll = function () {
		var self = this;
		var frame = null;

		this.viewport.addEventListener(
			'scroll',
			function () {
				if ( frame ) {
					return;
				}

				frame = window.requestAnimationFrame( function () {
					frame = null;
					self.update();
				} );
			},
			{ passive: true }
		);
	};

	Reviews.prototype.bindResize = function () {
		var self = this;
		var rerun = function () {
			self.sync();
		};

		if ( 'ResizeObserver' in window ) {
			new ResizeObserver( rerun ).observe( this.viewport );
		}

		window.addEventListener( 'resize', rerun );

		// Late-loading avatars change the card height, not the width, but the
		// scroll extent can still shift on the first paint.
		qsa( 'img', this.root ).forEach( function ( img ) {
			if ( ! img.complete ) {
				img.addEventListener( 'load', rerun, { once: true } );
			}
		} );
	};

	Reviews.prototype.bindDrag = function () {
		var root = this.root;
		var viewport = this.viewport;
		var self = this;
		var down = false;
		var moved = false;
		var startX = 0;
		var startLeft = 0;

		viewport.addEventListener( 'pointerdown', function ( event ) {
			if ( event.button !== 0 || self.maxScroll() <= 1 ) {
				return;
			}

			down = true;
			moved = false;
			startX = event.clientX;
			startLeft = viewport.scrollLeft;
			root.classList.add( 'is-dragging' );
		} );

		viewport.addEventListener( 'pointermove', function ( event ) {
			if ( ! down ) {
				return;
			}

			var delta = event.clientX - startX;

			if ( ! moved && Math.abs( delta ) < 4 ) {
				return;
			}

			if ( ! moved ) {
				moved = true;

				try {
					viewport.setPointerCapture( event.pointerId );
				} catch ( error ) {
					// Capture is a nicety; dragging still works without it.
				}
			}

			event.preventDefault();
			viewport.scrollLeft = startLeft - delta;
		} );

		function end() {
			if ( ! down ) {
				return;
			}

			down = false;
			root.classList.remove( 'is-dragging' );

			if ( moved ) {
				// Swallow the click that follows a drag so a card link does
				// not fire when the pointer was only used to scroll.
				var swallow = function ( event ) {
					event.preventDefault();
					event.stopPropagation();
				};

				viewport.addEventListener( 'click', swallow, { capture: true, once: true } );
				window.setTimeout( function () {
					viewport.removeEventListener( 'click', swallow, { capture: true } );
				}, 0 );

				// Settle on the nearest page now that snapping is back on.
				self.update();
				self.goTo( self.page );
			}

			moved = false;
		}

		viewport.addEventListener( 'pointerup', end );
		viewport.addEventListener( 'pointercancel', end );
		viewport.addEventListener( 'lostpointercapture', end );

		qsa( 'img, a', viewport ).forEach( function ( node ) {
			node.addEventListener( 'dragstart', function ( event ) {
				event.preventDefault();
			} );
		} );
	};

	function boot() {
		qsa( '[data-pfh-reviews]' ).forEach( function ( root ) {
			if ( ! root.pfhReviews ) {
				root.pfhReviews = new Reviews( root );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	// Bricks swaps element markup in the builder without a page load.
	document.addEventListener( 'bricks/ajax/end', boot );
} )();
