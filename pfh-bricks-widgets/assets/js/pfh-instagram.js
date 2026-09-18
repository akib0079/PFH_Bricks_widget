/**
 * Products For Home – the Instagram strip.
 *
 * A marquee that never reaches an end: one set of pictures is printed, and this
 * clones it until the row is wider than the screen, then moves the whole track
 * with a transform and wraps at the width of a single set. Because the wrap
 * happens on a seam where set N ends and set N+1 begins, there is nothing to
 * see when it does.
 *
 * It is a transform rather than scrollLeft on purpose: a transform is
 * composited, so the drift stays smooth while the rest of the page is busy,
 * and it does not fight the browser's own scroll restoration.
 *
 * The strip holds still whenever moving it would be rude or pointless — under
 * the cursor, during a drag, while the tab is hidden, while it is off screen,
 * and for anyone who asked for reduced motion. The arrows and dragging keep
 * working in every one of those cases.
 */
( function () {
	'use strict';

	var MAX_STEP = 50; // ms; a longer gap means the tab was away.

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function easeOut( t ) {
		return 1 - Math.pow( 1 - t, 3 );
	}

	function Marquee( root ) {
		this.root = root;
		this.set  = root.querySelector( '[data-pfh-ig-set]' );

		if ( ! this.set ) {
			return;
		}

		this.speed  = Math.max( 0, parseFloat( root.getAttribute( 'data-pfh-ig-speed' ) ) || 0 );
		this.dir    = 'right' === root.getAttribute( 'data-pfh-ig-direction' ) ? -1 : 1;
		this.hoverPauses = '1' === root.getAttribute( 'data-pfh-ig-pause' );
		this.reduced = !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

		this.pos     = 0;
		this.unit    = 0;
		this.clones  = [];
		this.hover   = false;
		this.visible = true;
		this.drag    = null;
		this.glide   = null;
		this.last    = 0;

		this.build();
		this.measure();
		this.listen();

		root.classList.add( 'is-live' );

		this.tick = this.tick.bind( this );
		this.frame = window.requestAnimationFrame( this.tick );
	}

	/** Wrap the printed set in a track the transform can be applied to. */
	Marquee.prototype.build = function () {
		this.track = document.createElement( 'div' );
		this.track.className = 'pfh-ig__track';

		this.root.insertBefore( this.track, this.set );
		this.track.appendChild( this.set );
	};

	/**
	 * Work out how wide one set is, and clone it until the track covers the
	 * strip twice over — anything less and the wrap would be visible.
	 */
	Marquee.prototype.measure = function () {
		var gap = parseFloat( window.getComputedStyle( this.track ).columnGap );

		if ( isNaN( gap ) ) {
			gap = 0;
		}

		var width = this.set.getBoundingClientRect().width;

		// Before the pictures have arrived there is nothing to measure; the
		// observers below call back when there is.
		if ( width < 1 ) {
			this.unit = 0;

			return;
		}

		this.unit = width + gap;

		var need = Math.ceil( this.root.getBoundingClientRect().width / this.unit ) + 1;

		while ( this.clones.length > need ) {
			this.track.removeChild( this.clones.pop() );
		}

		while ( this.clones.length < need ) {
			var copy = this.set.cloneNode( true );

			// A copy is scenery: it is not read out, and nothing in it can be
			// tabbed to, or the keyboard would walk the same pictures again.
			copy.setAttribute( 'aria-hidden', 'true' );
			copy.removeAttribute( 'data-pfh-ig-set' );

			qsa( 'a', copy ).forEach( function ( link ) {
				link.setAttribute( 'tabindex', '-1' );
			} );

			this.track.appendChild( copy );
			this.clones.push( copy );
		}

		this.draw();
	};

	/** Put the track where pos says it should be. */
	Marquee.prototype.draw = function () {
		if ( ! this.unit ) {
			return;
		}

		var offset = this.pos % this.unit;

		if ( offset < 0 ) {
			offset += this.unit;
		}

		this.track.style.transform = 'translate3d(' + ( -offset ).toFixed( 2 ) + 'px, 0, 0)';
	};

	/** How far one arrow press moves the row. */
	Marquee.prototype.step = function () {
		var tile = this.set.querySelector( '.pfh-ig__tile' );
		var gap  = parseFloat( window.getComputedStyle( this.set ).columnGap );

		if ( isNaN( gap ) ) {
			gap = 0;
		}

		return tile ? tile.getBoundingClientRect().width + gap : 200;
	};

	/**
	 * Move by a fixed distance, eased.
	 *
	 * @param {number} delta Pixels; positive moves the row leftwards.
	 */
	Marquee.prototype.nudge = function ( delta ) {
		if ( this.reduced ) {
			this.pos += delta;
			this.draw();

			return;
		}

		this.glide = {
			from:  this.pos,
			delta: delta,
			start: window.performance && performance.now ? performance.now() : Date.now(),
			time:  450,
		};
	};

	Marquee.prototype.running = function () {
		return ! this.reduced
			&& this.speed > 0
			&& this.unit > 0
			&& ! this.drag
			&& ! this.glide
			&& this.visible
			&& ! document.hidden
			&& ! ( this.hover && this.hoverPauses );
	};

	Marquee.prototype.tick = function ( now ) {
		var delta = this.last ? Math.min( now - this.last, MAX_STEP ) : 0;
		this.last = now;

		if ( this.glide ) {
			var t = Math.min( 1, ( now - this.glide.start ) / this.glide.time );

			this.pos = this.glide.from + this.glide.delta * easeOut( t );
			this.draw();

			if ( t >= 1 ) {
				this.glide = null;
			}
		} else if ( this.running() ) {
			this.pos += ( this.speed * this.dir * delta ) / 1000;
			this.draw();
		}

		this.frame = window.requestAnimationFrame( this.tick );
	};

	Marquee.prototype.listen = function () {
		var self = this;

		this.root.addEventListener( 'mouseenter', function () { self.hover = true; } );
		this.root.addEventListener( 'mouseleave', function () { self.hover = false; } );

		// A picture the keyboard has landed on should not slide away from it.
		this.root.addEventListener( 'focusin', function () { self.hover = true; } );
		this.root.addEventListener( 'focusout', function () { self.hover = false; } );

		qsa( '[data-pfh-ig-step]', this.root.closest( '.pfh-ig' ) || document ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				self.nudge( parseFloat( button.getAttribute( 'data-pfh-ig-step' ) ) * self.step() );
			} );
		} );

		this.dragging();

		if ( window.ResizeObserver ) {
			// The pictures arrive after the markup does, and a phone rotating
			// changes every width at once. Both land here.
			this.observer = new ResizeObserver( function () {
				self.measure();
			} );

			this.observer.observe( this.root );
			this.observer.observe( this.set );
		} else {
			window.addEventListener( 'resize', function () { self.measure(); } );
			window.addEventListener( 'load', function () { self.measure(); } );
		}

		if ( window.IntersectionObserver ) {
			this.seen = new IntersectionObserver( function ( entries ) {
				self.visible = entries[0].isIntersecting;
			}, { rootMargin: '120px' } );

			this.seen.observe( this.root );
		}
	};

	/** Pointer dragging, with a short throw when it is let go. */
	Marquee.prototype.dragging = function () {
		var self = this;

		this.root.addEventListener( 'pointerdown', function ( event ) {
			if ( event.button && 0 !== event.button ) {
				return;
			}

			self.glide = null;
			self.drag  = {
				x:    event.clientX,
				from: self.pos,
				last: event.clientX,
				time: event.timeStamp,
				vel:  0,
				moved: 0,
			};

			self.root.classList.add( 'is-dragging' );

			if ( self.root.setPointerCapture ) {
				try {
					self.root.setPointerCapture( event.pointerId );
				} catch ( e ) {} // eslint-disable-line no-empty
			}
		} );

		this.root.addEventListener( 'pointermove', function ( event ) {
			if ( ! self.drag ) {
				return;
			}

			var dt = event.timeStamp - self.drag.time;

			if ( dt > 0 ) {
				self.drag.vel = ( self.drag.last - event.clientX ) / dt;
			}

			self.drag.last  = event.clientX;
			self.drag.time  = event.timeStamp;
			self.drag.moved = Math.max( self.drag.moved, Math.abs( event.clientX - self.drag.x ) );

			self.pos = self.drag.from + ( self.drag.x - event.clientX );
			self.draw();
		} );

		[ 'pointerup', 'pointercancel', 'pointerleave' ].forEach( function ( name ) {
			self.root.addEventListener( name, function () {
				if ( ! self.drag ) {
					return;
				}

				var thrown = self.drag.vel * 260; // ms of coasting
				var moved  = self.drag.moved;

				self.drag = null;
				self.root.classList.remove( 'is-dragging' );

				if ( Math.abs( thrown ) > 12 ) {
					self.nudge( thrown );
				}

				// A drag that ends on a picture must not also open it.
				if ( moved > 5 ) {
					self.swallow = true;
					window.setTimeout( function () { self.swallow = false; }, 0 );
				}
			} );
		} );

		this.root.addEventListener( 'click', function ( event ) {
			if ( self.swallow ) {
				event.preventDefault();
				event.stopPropagation();
			}
		}, true );
	};

	function boot() {
		qsa( '[data-pfh-ig]' ).forEach( function ( root ) {
			if ( ! root.pfhInstagram ) {
				root.pfhInstagram = new Marquee( root );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	document.addEventListener( 'bricks/ajax/end', boot );
} )();
