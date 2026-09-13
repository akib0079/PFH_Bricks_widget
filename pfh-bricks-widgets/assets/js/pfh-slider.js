/**
 * Products For Home – shared drag slider engine.
 *
 * Drives any element carrying data-pfh-drag-slider. The viewport is a native
 * overflow-x scroller; pointer drag and the custom scrollbar both write to the
 * same scrollLeft, so nothing here reimplements momentum, trackpad or keyboard
 * scrolling. Used by the category slider and the product slider.
 */
( function () {
	'use strict';

	/**
	 * Publish the scrollbar width so a full-bleed slider can inset itself to
	 * the content column using viewport units.
	 *
	 * The inset has to be measured against the viewport rather than the
	 * widget's parent, or a page builder's own container silently becomes the
	 * reference and the first card stops lining up with the logo. 100vw counts
	 * the scrollbar, though, and the content column does not — hence this.
	 */
	function publishScrollbarWidth() {
		var width = window.innerWidth - document.documentElement.clientWidth;

		document.documentElement.style.setProperty(
			'--pfh-sbw',
			( width > 0 ? width : 0 ) + 'px'
		);
	}

	publishScrollbarWidth();
	window.addEventListener( 'resize', publishScrollbarWidth, { passive: true } );
	document.addEventListener( 'DOMContentLoaded', publishScrollbarWidth );

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function clamp( value, min, max ) {
		return Math.max( min, Math.min( max, value ) );
	}

	function Slider( root ) {
		this.root = root;
		this.config = this.readConfig();
		this.viewport = root.querySelector( '[data-pfh-viewport]' );
		this.track = root.querySelector( '[data-pfh-track]' );
		this.bar = root.querySelector( '[data-pfh-bar]' );
		this.barTrack = root.querySelector( '[data-pfh-bar-track]' );
		this.thumb = root.querySelector( '[data-pfh-bar-thumb]' );

		if ( ! this.viewport || ! this.track ) {
			return;
		}

		this.viewport.id = 'pfh-viewport-' + ( this.config.uid || '' );

		this.bindScroll();
		this.bindDrag();
		this.bindWheel();
		this.bindBar();
		this.bindResize();
		this.sync();
	}

	Slider.prototype.readConfig = function () {
		var defaults = { uid: '', drag: true, wheel: false, bar: true, snap: false };

		try {
			return Object.assign( {}, defaults, JSON.parse( this.root.getAttribute( 'data-pfh-config' ) || '{}' ) );
		} catch ( error ) {
			return defaults;
		}
	};

	/**
	 * How far the track can scroll. Zero when everything already fits.
	 *
	 * @return {number} Maximum scrollLeft.
	 */
	Slider.prototype.maxScroll = function () {
		return Math.max( 0, this.viewport.scrollWidth - this.viewport.clientWidth );
	};

	/* ----------------------------------------------------------- state -- */

	Slider.prototype.sync = function () {
		var max = this.maxScroll();
		var scrollable = max > 1;

		this.root.classList.toggle( 'is-draggable', scrollable && this.config.drag );

		if ( ! this.bar ) {
			return;
		}

		// A scrollbar for content that cannot scroll is just noise.
		this.bar.hidden = ! ( this.config.bar && scrollable );

		if ( this.bar.hidden || ! this.thumb || ! this.barTrack ) {
			return;
		}

		var trackWidth = this.barTrack.clientWidth;
		var ratio = this.viewport.clientWidth / this.viewport.scrollWidth;
		var thumbWidth = Math.max( 28, Math.round( trackWidth * ratio ) );
		var progress = max > 0 ? this.viewport.scrollLeft / max : 0;

		this.thumb.style.width = thumbWidth + 'px';
		this.thumb.style.transform =
			'translate3d(' + Math.round( ( trackWidth - thumbWidth ) * clamp( progress, 0, 1 ) ) + 'px, -50%, 0)';
		this.thumb.setAttribute( 'aria-valuenow', Math.round( clamp( progress, 0, 1 ) * 100 ) );
	};

	Slider.prototype.bindScroll = function () {
		var self = this;
		var pending = false;

		this.viewport.addEventListener(
			'scroll',
			function () {
				if ( pending ) {
					return;
				}

				pending = true;

				// rAF when available, timeout as the fallback: some embedding
				// contexts never fire animation frames.
				var run = function () {
					pending = false;
					self.sync();
				};

				if ( window.requestAnimationFrame ) {
					window.requestAnimationFrame( run );
					window.setTimeout( function () {
						if ( pending ) {
							run();
						}
					}, 120 );
				} else {
					window.setTimeout( run, 16 );
				}
			},
			{ passive: true }
		);
	};

	Slider.prototype.bindResize = function () {
		var self = this;

		if ( 'ResizeObserver' in window ) {
			var observer = new ResizeObserver( function () {
				self.sync();
			} );

			observer.observe( this.viewport );
			observer.observe( this.track );
		}

		window.addEventListener( 'resize', function () {
			self.sync();
		}, { passive: true } );

		// Card artwork changes the scroll width as it loads in.
		qsa( 'img', this.root ).forEach( function ( img ) {
			if ( ! img.complete ) {
				img.addEventListener( 'load', function () {
					self.sync();
				}, { once: true } );
			}
		} );
	};

	/* ------------------------------------------------------------ drag -- */

	Slider.prototype.bindDrag = function () {
		if ( ! this.config.drag ) {
			return;
		}

		var self = this;
		var viewport = this.viewport;
		var startX = 0;
		var startScroll = 0;
		var pointerId = null;
		var moved = 0;

		viewport.addEventListener( 'pointerdown', function ( event ) {
			// Touch keeps native scrolling; only take over for mouse and pen.
			if ( 'touch' === event.pointerType || 0 !== event.button ) {
				return;
			}

			if ( self.maxScroll() <= 1 ) {
				return;
			}

			pointerId = event.pointerId;
			startX = event.clientX;
			startScroll = viewport.scrollLeft;
			moved = 0;
		} );

		viewport.addEventListener( 'pointermove', function ( event ) {
			if ( null === pointerId || event.pointerId !== pointerId ) {
				return;
			}

			var delta = event.clientX - startX;

			if ( ! moved && Math.abs( delta ) < 4 ) {
				return;
			}

			if ( ! moved ) {
				self.root.classList.add( 'is-dragging' );

				try {
					viewport.setPointerCapture( pointerId );
				} catch ( error ) {
					// Capture is an optimisation; dragging still works without it.
				}
			}

			moved = Math.max( moved, Math.abs( delta ) );
			viewport.scrollLeft = startScroll - delta;
			event.preventDefault();
		} );

		function end() {
			if ( null === pointerId ) {
				return;
			}

			try {
				viewport.releasePointerCapture( pointerId );
			} catch ( error ) {
				// Nothing to release.
			}

			pointerId = null;

			if ( moved > 4 ) {
				// Swallow the click that ends the drag so the card doesn't open.
				var swallow = function ( event ) {
					event.preventDefault();
					event.stopPropagation();
				};

				viewport.addEventListener( 'click', swallow, { capture: true, once: true } );
				window.setTimeout( function () {
					viewport.removeEventListener( 'click', swallow, { capture: true } );
				}, 0 );
			}

			self.root.classList.remove( 'is-dragging' );
			moved = 0;
			self.sync();
		}

		viewport.addEventListener( 'pointerup', end );
		viewport.addEventListener( 'pointercancel', end );
		viewport.addEventListener( 'lostpointercapture', end );

		// Native image drag would fight the pointer drag.
		qsa( 'img, a', viewport ).forEach( function ( node ) {
			node.addEventListener( 'dragstart', function ( event ) {
				event.preventDefault();
			} );
		} );
	};

	Slider.prototype.bindWheel = function () {
		if ( ! this.config.wheel ) {
			return;
		}

		var viewport = this.viewport;
		var self = this;

		viewport.addEventListener( 'wheel', function ( event ) {
			// Leave horizontal/trackpad gestures to the browser.
			if ( Math.abs( event.deltaX ) > Math.abs( event.deltaY ) ) {
				return;
			}

			var max = self.maxScroll();

			if ( max <= 1 ) {
				return;
			}

			var next = clamp( viewport.scrollLeft + event.deltaY, 0, max );

			// Only hijack the page scroll while the track can still move.
			if ( next !== viewport.scrollLeft ) {
				viewport.scrollLeft = next;
				event.preventDefault();
			}
		}, { passive: false } );
	};

	/* ------------------------------------------------------- scrollbar -- */

	Slider.prototype.bindBar = function () {
		if ( ! this.thumb || ! this.barTrack ) {
			return;
		}

		var self = this;
		var viewport = this.viewport;
		var dragging = false;
		var pointerId = null;
		var startX = 0;
		var startLeft = 0;

		function trackToScroll( px ) {
			var trackWidth = self.barTrack.clientWidth;
			var thumbWidth = self.thumb.offsetWidth;
			var span = trackWidth - thumbWidth;

			if ( span <= 0 ) {
				return 0;
			}

			return clamp( px / span, 0, 1 ) * self.maxScroll();
		}

		this.thumb.addEventListener( 'pointerdown', function ( event ) {
			dragging = true;
			pointerId = event.pointerId;
			startX = event.clientX;
			// The thumb is positioned with a transform, so measure it rather
			// than reading offsetLeft (which stays 0).
			startLeft = self.thumb.getBoundingClientRect().left - self.barTrack.getBoundingClientRect().left;

			self.thumb.classList.add( 'is-dragging' );
			self.root.classList.add( 'is-dragging' );

			try {
				self.thumb.setPointerCapture( pointerId );
			} catch ( error ) {
				// Optional.
			}

			event.preventDefault();
		} );

		this.thumb.addEventListener( 'pointermove', function ( event ) {
			if ( ! dragging || event.pointerId !== pointerId ) {
				return;
			}

			viewport.scrollLeft = trackToScroll( startLeft + ( event.clientX - startX ) );
			self.sync();
		} );

		function endThumb() {
			if ( ! dragging ) {
				return;
			}

			dragging = false;

			try {
				self.thumb.releasePointerCapture( pointerId );
			} catch ( error ) {
				// Optional.
			}

			pointerId = null;
			self.thumb.classList.remove( 'is-dragging' );
			self.root.classList.remove( 'is-dragging' );
		}

		this.thumb.addEventListener( 'pointerup', endThumb );
		this.thumb.addEventListener( 'pointercancel', endThumb );
		this.thumb.addEventListener( 'lostpointercapture', endThumb );

		// Click anywhere on the track to jump there.
		this.barTrack.addEventListener( 'pointerdown', function ( event ) {
			if ( event.target === self.thumb ) {
				return;
			}

			var box = self.barTrack.getBoundingClientRect();
			var target = event.clientX - box.left - self.thumb.offsetWidth / 2;

			viewport.scrollTo( { left: trackToScroll( target ), behavior: 'smooth' } );
		} );

		// Keyboard control on the thumb.
		this.thumb.addEventListener( 'keydown', function ( event ) {
			var step = viewport.clientWidth * 0.6;
			var max = self.maxScroll();
			var next = null;

			if ( 'ArrowLeft' === event.key ) {
				next = viewport.scrollLeft - step;
			} else if ( 'ArrowRight' === event.key ) {
				next = viewport.scrollLeft + step;
			} else if ( 'Home' === event.key ) {
				next = 0;
			} else if ( 'End' === event.key ) {
				next = max;
			}

			if ( null === next ) {
				return;
			}

			event.preventDefault();
			viewport.scrollTo( { left: clamp( next, 0, max ), behavior: 'smooth' } );
		} );
	};

	/* ------------------------------------------------------------ boot -- */

	function init() {
		qsa( '[data-pfh-drag-slider]' ).forEach( function ( root ) {
			if ( root.pfhSlider ) {
				return;
			}

			root.pfhSlider = new Slider( root );
		} );
	}

	window.pfhSliderInit = init;
	// Kept so existing Bricks elements referencing the old name still boot.
	window.pfhCategoriesInit = init;

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
