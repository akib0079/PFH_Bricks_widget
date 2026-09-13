/**
 * Products For Home – highlighted features reveal.
 *
 * Adds `is-ready` (which arms the hidden state) and then `is-in` (which plays
 * the staggered rise) once the section is on screen.
 *
 * The hidden state is only ever applied from script, so with JavaScript off —
 * or if anything here throws — the section renders plainly rather than
 * invisibly. IntersectionObserver is the primary trigger, but a geometry check,
 * a scroll listener and a timeout race it, because an observer never fires in
 * a few real contexts (a hidden or zero-height ancestor while the builder is
 * laying out, for one) and an unrevealed section would be a blank hole.
 */
( function () {
	'use strict';

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function Features( root ) {
		this.root = root;
		this.done = false;

		var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		this.index();

		if ( reduced ) {
			root.classList.add( 'is-ready', 'is-in' );
			this.done = true;

			return;
		}

		root.classList.add( 'is-ready' );

		// Force a reflow so the hidden state is the browser's starting point;
		// without it the class swap can be coalesced and nothing animates.
		void root.offsetWidth;

		this.watch();
	}

	/** Number each animated child so CSS can stagger off it. */
	Features.prototype.index = function () {
		var items = qsa( '.pfh-hf__head > *', this.root ).concat( qsa( '.pfh-hf__tile', this.root ) );

		items.forEach( function ( node, i ) {
			node.style.setProperty( '--pfh-hf-i', String( i ) );
		} );
	};

	Features.prototype.reveal = function () {
		if ( this.done ) {
			return;
		}

		this.done = true;
		this.root.classList.add( 'is-in' );

		if ( this.observer ) {
			this.observer.disconnect();
		}

		window.removeEventListener( 'scroll', this.onScroll );
		window.removeEventListener( 'resize', this.onScroll );
		window.clearTimeout( this.timer );
	};

	/** True once any part of the section has entered the viewport. */
	Features.prototype.visible = function () {
		var box = this.root.getBoundingClientRect();
		var height = window.innerHeight || document.documentElement.clientHeight;

		if ( ! box.height ) {
			return false;
		}

		return box.top < height * 0.92 && box.bottom > 0;
	};

	Features.prototype.watch = function () {
		var self = this;

		this.onScroll = function () {
			if ( self.visible() ) {
				self.reveal();
			}
		};

		if ( 'IntersectionObserver' in window ) {
			this.observer = new IntersectionObserver(
				function ( entries ) {
					entries.forEach( function ( entry ) {
						if ( entry.isIntersecting ) {
							self.reveal();
						}
					} );
				},
				{ rootMargin: '0px 0px -8% 0px', threshold: 0.01 }
			);

			this.observer.observe( this.root );
		}

		window.addEventListener( 'scroll', this.onScroll, { passive: true } );
		window.addEventListener( 'resize', this.onScroll, { passive: true } );

		// Already in view on load.
		this.onScroll();

		// Last resort: never leave the section hidden.
		this.timer = window.setTimeout( function () {
			self.reveal();
		}, 1500 );
	};

	function boot() {
		qsa( '[data-pfh-features]' ).forEach( function ( root ) {
			if ( ! root.pfhFeatures ) {
				root.pfhFeatures = new Features( root );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	document.addEventListener( 'bricks/ajax/end', boot );
} )();
