/**
 * Products For Home – hero slider.
 *
 * Slides are stacked and crossfaded; each slide's parts carry data-pfh-reveal
 * and animate in with a stagger driven entirely by CSS custom properties.
 */
( function () {
	'use strict';

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function prefersReducedMotion() {
		return window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	function Hero( root ) {
		this.root = root;
		this.config = this.readConfig();
		this.slides = qsa( '[data-pfh-slide]', root );
		this.dots = qsa( '[data-pfh-dot]', root );
		this.dotsWrap = root.querySelector( '.pfh-hero__dots' );
		this.bg = root.querySelector( '[data-pfh-bg]' );
		this.live = root.querySelector( '[data-pfh-live]' );
		this.index = 0;
		this.timer = null;
		this.paused = false;
		this.reduced = prefersReducedMotion();

		if ( ! this.slides.length ) {
			return;
		}

		this.defaultBg = this.bg ? this.bg.style.getPropertyValue( '--pfh-hero-bg-img' ) : '';

		this.bindDots();
		this.bindArrows();
		this.bindKeys();
		this.bindHover();
		this.bindSwipe();
		this.bindVisibility();
		this.bindParallax();

		this.syncSlides( 0, true );
		this.intro();
	}

	Hero.prototype.readConfig = function () {
		var defaults = {
			total: 1, autoplay: false, interval: 6000, pauseOnHover: true,
			loop: true, swipe: true, progress: false, parallax: false,
			crossfade: 600, slideLabel: 'Slide %1$d of %2$d'
		};

		try {
			return Object.assign( {}, defaults, JSON.parse( this.root.getAttribute( 'data-pfh-config' ) || '{}' ) );
		} catch ( error ) {
			return defaults;
		}
	};

	/**
	 * Replay the reveal on the first slide once the hero is on screen, so the
	 * animation is seen even when the section sits below the fold.
	 *
	 * Visibility is never gated on IntersectionObserver alone: it does not fire
	 * in every embedding context, and a hero whose content stays hidden is far
	 * worse than one that skips its intro. A geometry check, a scroll listener
	 * and a timeout all race to activate, whichever gets there first.
	 */
	Hero.prototype.intro = function () {
		var self = this;
		var first = this.slides[ 0 ];
		var observer = null;
		var failsafe = null;

		function activate() {
			if ( self.introDone ) {
				return;
			}

			self.introDone = true;

			// Reading offsetWidth commits the hidden state, so adding the class
			// on the very next line still animates. Doing this synchronously
			// avoids depending on requestAnimationFrame ever firing.
			void first.offsetWidth;
			first.classList.add( 'is-active' );
			self.start();
		}

		if ( this.reduced ) {
			activate();

			return;
		}

		first.classList.remove( 'is-active' );
		// Force a style flush so the reset is the transition's start point.
		void first.offsetWidth;

		function inView() {
			var box = self.root.getBoundingClientRect();
			var height = window.innerHeight || document.documentElement.clientHeight;

			return box.top < height * 0.9 && box.bottom > 0;
		}

		if ( inView() ) {
			activate();

			return;
		}

		function cleanup() {
			window.removeEventListener( 'scroll', onMove );
			window.removeEventListener( 'resize', onMove );
			window.clearTimeout( failsafe );

			if ( observer ) {
				observer.disconnect();
			}
		}

		function onMove() {
			if ( inView() ) {
				cleanup();
				activate();
			}
		}

		window.addEventListener( 'scroll', onMove, { passive: true } );
		window.addEventListener( 'resize', onMove, { passive: true } );

		if ( 'IntersectionObserver' in window ) {
			observer = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						cleanup();
						activate();
					}
				} );
			}, { threshold: 0.15 } );

			observer.observe( this.root );
		}

		failsafe = window.setTimeout( function () {
			cleanup();
			activate();
		}, 1500 );
	};

	/* ------------------------------------------------------- navigation -- */

	Hero.prototype.goTo = function ( index, viaUser ) {
		var total = this.slides.length;

		if ( total < 2 ) {
			return;
		}

		if ( index < 0 ) {
			index = this.config.loop ? total - 1 : 0;
		} else if ( index >= total ) {
			index = this.config.loop ? 0 : total - 1;
		}

		if ( index === this.index ) {
			return;
		}

		this.syncSlides( index );

		if ( viaUser ) {
			this.restart();
		}
	};

	Hero.prototype.next = function ( viaUser ) {
		this.goTo( this.index + 1, viaUser );
	};

	Hero.prototype.prev = function ( viaUser ) {
		this.goTo( this.index - 1, viaUser );
	};

	Hero.prototype.syncSlides = function ( index, initial ) {
		var self = this;

		this.index = index;

		this.slides.forEach( function ( slide, i ) {
			var active = i === index;

			slide.classList.toggle( 'is-active', active );
			slide.setAttribute( 'aria-hidden', active ? 'false' : 'true' );

			// Keep hidden slides out of the tab order.
			qsa( 'a[href], button, input, select, textarea', slide ).forEach( function ( node ) {
				if ( active ) {
					if ( node.hasAttribute( 'data-pfh-tab' ) ) {
						node.removeAttribute( 'tabindex' );
						node.removeAttribute( 'data-pfh-tab' );
					}
				} else if ( ! node.hasAttribute( 'data-pfh-tab' ) ) {
					node.setAttribute( 'data-pfh-tab', '' );
					node.setAttribute( 'tabindex', '-1' );
				}
			} );
		} );

		this.dots.forEach( function ( dot, i ) {
			dot.classList.toggle( 'is-active', i === index );
			dot.setAttribute( 'aria-selected', i === index ? 'true' : 'false' );
		} );

		this.paintBackground( initial );
		this.restartProgress();

		if ( this.live && ! initial ) {
			this.live.textContent = this.config.slideLabel
				.replace( '%1$d', index + 1 )
				.replace( '%2$d', this.slides.length );
		}
	};

	Hero.prototype.paintBackground = function ( initial ) {
		if ( ! this.bg ) {
			return;
		}

		var slide = this.slides[ this.index ];
		var url = slide.getAttribute( 'data-pfh-slide-bg' );
		var next = url ? 'url(' + url + ')' : this.defaultBg;
		var current = this.bg.style.getPropertyValue( '--pfh-hero-bg-img' );

		if ( next === current ) {
			return;
		}

		if ( initial || this.reduced ) {
			this.bg.style.setProperty( '--pfh-hero-bg-img', next );

			return;
		}

		var bg = this.bg;

		bg.style.opacity = '0';

		window.setTimeout( function () {
			bg.style.setProperty( '--pfh-hero-bg-img', next );
			bg.style.opacity = '';
		}, Math.min( 220, this.config.crossfade / 2 ) );
	};

	/* --------------------------------------------------------- autoplay -- */

	Hero.prototype.start = function () {
		if ( ! this.config.autoplay || this.reduced || this.slides.length < 2 ) {
			return;
		}

		this.stop();

		var self = this;

		this.timer = window.setInterval( function () {
			if ( ! self.paused ) {
				self.next( false );
			}
		}, this.config.interval );
	};

	Hero.prototype.stop = function () {
		window.clearInterval( this.timer );
		this.timer = null;
	};

	Hero.prototype.restart = function () {
		if ( this.timer ) {
			this.start();
		}
	};

	Hero.prototype.setPaused = function ( paused ) {
		this.paused = paused;

		if ( this.dotsWrap ) {
			this.dotsWrap.classList.toggle( 'is-paused', paused );
		}
	};

	Hero.prototype.restartProgress = function () {
		if ( ! this.config.progress || this.reduced ) {
			return;
		}

		var dot = this.dots[ this.index ];

		if ( ! dot ) {
			return;
		}

		var fill = dot.querySelector( '.pfh-hero__dot-fill' );

		if ( ! fill ) {
			return;
		}

		this.root.style.setProperty( '--pfh-dot-time', this.config.interval + 'ms' );

		// Restart the CSS animation from zero.
		fill.style.animation = 'none';
		void fill.offsetWidth;
		fill.style.animation = '';
	};

	/* --------------------------------------------------------- bindings -- */

	Hero.prototype.bindDots = function () {
		var self = this;

		this.dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				self.goTo( parseInt( dot.getAttribute( 'data-pfh-dot' ), 10 ), true );
			} );
		} );
	};

	Hero.prototype.bindArrows = function () {
		var self = this;
		var prev = this.root.querySelector( '[data-pfh-prev]' );
		var next = this.root.querySelector( '[data-pfh-next]' );

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				self.prev( true );
			} );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				self.next( true );
			} );
		}
	};

	Hero.prototype.bindKeys = function () {
		var self = this;

		this.root.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowLeft' === event.key ) {
				event.preventDefault();
				self.prev( true );
			} else if ( 'ArrowRight' === event.key ) {
				event.preventDefault();
				self.next( true );
			}
		} );
	};

	Hero.prototype.bindHover = function () {
		if ( ! this.config.pauseOnHover ) {
			return;
		}

		var self = this;

		[ 'mouseenter', 'focusin' ].forEach( function ( name ) {
			self.root.addEventListener( name, function () {
				self.setPaused( true );
			} );
		} );

		[ 'mouseleave', 'focusout' ].forEach( function ( name ) {
			self.root.addEventListener( name, function ( event ) {
				if ( 'focusout' === name && self.root.contains( event.relatedTarget ) ) {
					return;
				}

				self.setPaused( false );
			} );
		} );
	};

	Hero.prototype.bindVisibility = function () {
		var self = this;

		document.addEventListener( 'visibilitychange', function () {
			self.setPaused( document.hidden );
		} );
	};

	Hero.prototype.bindSwipe = function () {
		if ( ! this.config.swipe || this.slides.length < 2 ) {
			return;
		}

		var viewport = this.root.querySelector( '[data-pfh-viewport]' );

		if ( ! viewport ) {
			return;
		}

		var self = this;
		var startX = 0;
		var startY = 0;
		var tracking = false;

		viewport.addEventListener( 'pointerdown', function ( event ) {
			if ( 'mouse' === event.pointerType ) {
				return;
			}

			tracking = true;
			startX = event.clientX;
			startY = event.clientY;
		}, { passive: true } );

		viewport.addEventListener( 'pointerup', function ( event ) {
			if ( ! tracking ) {
				return;
			}

			tracking = false;

			var dx = event.clientX - startX;
			var dy = event.clientY - startY;

			// Ignore mostly-vertical gestures so page scrolling still works.
			if ( Math.abs( dx ) < 45 || Math.abs( dx ) < Math.abs( dy ) ) {
				return;
			}

			if ( dx < 0 ) {
				self.next( true );
			} else {
				self.prev( true );
			}
		}, { passive: true } );

		viewport.addEventListener( 'pointercancel', function () {
			tracking = false;
		}, { passive: true } );
	};

	Hero.prototype.bindParallax = function () {
		if ( ! this.config.parallax || this.reduced ) {
			return;
		}

		if ( window.matchMedia && ! window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches ) {
			return;
		}

		var root = this.root;
		var targets = qsa( '[data-pfh-depth]', root );

		if ( ! targets.length ) {
			return;
		}

		var pending = false;
		var relX = 0;
		var relY = 0;

		function paint() {
			pending = false;

			targets.forEach( function ( node ) {
				var depth = parseFloat( node.getAttribute( 'data-pfh-depth' ) ) || 1;
				var scale = 10 * depth;

				node.style.setProperty( '--pfh-px', ( relX * scale ).toFixed( 2 ) + 'px' );
				node.style.setProperty( '--pfh-py', ( relY * scale ).toFixed( 2 ) + 'px' );
			} );
		}

		root.addEventListener( 'pointermove', function ( event ) {
			if ( 'mouse' !== event.pointerType ) {
				return;
			}

			var box = root.getBoundingClientRect();

			relX = ( event.clientX - box.left ) / box.width - 0.5;
			relY = ( event.clientY - box.top ) / box.height - 0.5;

			if ( ! pending ) {
				pending = true;
				window.requestAnimationFrame( paint );
			}
		}, { passive: true } );

		root.addEventListener( 'pointerleave', function () {
			relX = 0;
			relY = 0;

			if ( ! pending ) {
				pending = true;
				window.requestAnimationFrame( paint );
			}
		} );
	};

	/* ------------------------------------------------------------- boot -- */

	function init() {
		qsa( '[data-pfh-hero]' ).forEach( function ( root ) {
			if ( root.pfhHero ) {
				return;
			}

			root.pfhHero = new Hero( root );
		} );
	}

	window.pfhHeroInit = init;

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
