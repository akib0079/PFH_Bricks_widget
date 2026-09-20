/**
 * A contents panel that knows which section is being read.
 *
 * Written once and driven by data attributes, because more than one long page
 * needs it: a panel marks `data-pfh-contents` with the block's own BEM prefix,
 * the piece being read marks `data-pfh-contents-body`, and the links inside
 * the panel point at ids in it. Everything the panel does, the page already
 * does without it — the links are real links to real headings — so nothing
 * here is load-bearing.
 */
( function () {
	'use strict';

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function reduced() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function wire( toc ) {
		var prefix = toc.getAttribute( 'data-pfh-contents' );
		var root = toc.closest( '.' + prefix );
		var body = root ? root.querySelector( '[data-pfh-contents-body]' ) : null;

		if ( ! prefix || ! body ) {
			return;
		}

		var pairs = qsa( '.' + prefix + '__toc-link', toc )
			.map( function ( link ) {
				var id = decodeURIComponent( ( link.getAttribute( 'href' ) || '' ).replace( /^#/, '' ) );

				return { link: link, target: id ? document.getElementById( id ) : null };
			} )
			.filter( function ( pair ) {
				return !! pair.target;
			} );

		if ( ! pairs.length ) {
			return;
		}

		// Smooth, unless that was asked not to be.
		if ( ! reduced() ) {
			pairs.forEach( function ( pair ) {
				pair.link.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					pair.target.scrollIntoView( { behavior: 'smooth', block: 'start' } );

					// The address bar should still say where the reader is.
					if ( window.history && history.replaceState ) {
						try {
							history.replaceState( null, '', pair.link.getAttribute( 'href' ) );
						} catch ( e ) {} // eslint-disable-line no-empty
					}

					pair.target.setAttribute( 'tabindex', '-1' );
					pair.target.focus( { preventScroll: true } );
				} );
			} );
		}

		var ticking = false;

		function mark() {
			ticking = false;

			var here = pairs[0];

			pairs.forEach( function ( pair ) {
				// The last section whose top has passed the reading line.
				if ( pair.target.getBoundingClientRect().top <= 120 ) {
					here = pair;
				}
			} );

			/*
			 * The last section is often short and has a good deal of page
			 * under it, so its top never reaches the reading line and the
			 * panel would never mark it. Once the end is on screen, it is
			 * the section being read whatever the arithmetic says.
			 */
			if ( body.getBoundingClientRect().bottom <= window.innerHeight ) {
				here = pairs[ pairs.length - 1 ];
			}

			pairs.forEach( function ( pair ) {
				pair.link.classList.toggle( 'is-here', pair === here );
			} );
		}

		function onScroll() {
			if ( ticking ) {
				return;
			}

			ticking = true;
			window.requestAnimationFrame( mark );
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		mark();

		/*
		 * On a phone the panel is a <details> the reader opens; on a desktop
		 * it is always open. Folding it shut below the breakpoint keeps the
		 * document itself at the top of a small screen.
		 */
		if ( 'DETAILS' === toc.tagName ) {
			var small = window.matchMedia( '(max-width: 900px)' );

			var fit = function () {
				toc.open = ! small.matches;
			};

			if ( small.addEventListener ) {
				small.addEventListener( 'change', fit );
			}

			fit();
		}
	}

	function boot() {
		qsa( '[data-pfh-contents]' ).forEach( function ( toc ) {
			if ( toc.hasAttribute( 'data-pfh-contents-ready' ) ) {
				return;
			}

			toc.setAttribute( 'data-pfh-contents-ready', '' );
			wire( toc );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	document.addEventListener( 'bricks/ajax/end', boot );
} )();
