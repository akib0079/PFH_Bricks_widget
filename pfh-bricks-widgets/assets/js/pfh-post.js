/**
 * Products For Home – the article page.
 *
 * Three small things, none of which the page needs to work: the bar showing
 * how far down the article the reader is, the contents panel marking the
 * section they are in, and the copy-link button. Everything they enhance is
 * already there and already works — the contents are real links to real ids,
 * and the other share buttons are ordinary links.
 */
( function () {
	'use strict';

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function reduced() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/* ------------------------------------------------------------------
	 * How far down
	 * --------------------------------------------------------------- */

	function progress( root ) {
		var bar = root.querySelector( '[data-pfh-post-progress] span' );
		var body = root.querySelector( '[data-pfh-post-body]' );

		if ( ! bar || ! body ) {
			return;
		}

		var ticking = false;

		function paint() {
			ticking = false;

			var box = body.getBoundingClientRect();
			var seen = -box.top;
			// How much of the article can actually scroll past.
			var run = box.height - window.innerHeight;

			if ( run <= 0 ) {
				bar.style.width = box.top <= 0 ? '100%' : '0';

				return;
			}

			var done = Math.min( 1, Math.max( 0, seen / run ) );

			bar.style.width = ( done * 100 ).toFixed( 2 ) + '%';
		}

		function onScroll() {
			if ( ticking ) {
				return;
			}

			ticking = true;
			window.requestAnimationFrame( paint );
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll, { passive: true } );
		paint();
	}

	/* ------------------------------------------------------------------
	 * Which section the reader is in
	 * --------------------------------------------------------------- */

	function contents( root ) {
		var toc = root.querySelector( '[data-pfh-post-toc]' );
		var body = root.querySelector( '[data-pfh-post-body]' );

		if ( ! toc || ! body ) {
			return;
		}

		var links = qsa( '.pfh-post__toc-link', toc );
		var headings = links
			.map( function ( link ) {
				var id = decodeURIComponent( ( link.getAttribute( 'href' ) || '' ).replace( /^#/, '' ) );

				return { link: link, heading: id ? document.getElementById( id ) : null };
			} )
			.filter( function ( pair ) {
				return !! pair.heading;
			} );

		if ( ! headings.length ) {
			return;
		}

		// Smooth, unless that was asked not to be.
		if ( ! reduced() ) {
			headings.forEach( function ( pair ) {
				pair.link.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					pair.heading.scrollIntoView( { behavior: 'smooth', block: 'start' } );

					// The address bar should still say where the reader is.
					if ( window.history && history.replaceState ) {
						try {
							history.replaceState( null, '', pair.link.getAttribute( 'href' ) );
						} catch ( e ) {} // eslint-disable-line no-empty
					}

					pair.heading.setAttribute( 'tabindex', '-1' );
					pair.heading.focus( { preventScroll: true } );
				} );
			} );
		}

		var ticking = false;

		function mark() {
			ticking = false;

			var here = headings[0];

			headings.forEach( function ( pair ) {
				// The last heading whose top has passed the reading line.
				if ( pair.heading.getBoundingClientRect().top <= 120 ) {
					here = pair;
				}
			} );

			/*
			 * The last section is often short, and there is a share row, a
			 * next-article row and a row of related articles under it — so its
			 * heading never reaches the reading line and the contents would
			 * never mark it. Once the end of the article is on screen, it is
			 * the section being read whatever the arithmetic says.
			 */
			if ( body.getBoundingClientRect().bottom <= window.innerHeight ) {
				here = headings[ headings.length - 1 ];
			}

			headings.forEach( function ( pair ) {
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
		 * article at the top of a small screen.
		 */
		var small = window.matchMedia( '(max-width: 900px)' );

		function fit() {
			toc.open = ! small.matches;
		}

		if ( small.addEventListener ) {
			small.addEventListener( 'change', fit );
		}

		fit();
	}

	/* ------------------------------------------------------------------
	 * Copying the link
	 * --------------------------------------------------------------- */

	function copy( root ) {
		qsa( '[data-pfh-post-copy]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var url = button.getAttribute( 'data-pfh-post-url' ) || window.location.href;
				var said = button.getAttribute( 'aria-label' );
				var done = button.getAttribute( 'data-pfh-post-done' ) || said;

				function tell() {
					button.classList.add( 'is-done' );
					button.setAttribute( 'aria-label', done );

					window.setTimeout( function () {
						button.classList.remove( 'is-done' );
						button.setAttribute( 'aria-label', said );
					}, 2000 );
				}

				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( url ).then( tell, function () {} );

					return;
				}

				// Older browsers, and anything served without https.
				var field = document.createElement( 'input' );

				field.value = url;
				field.setAttribute( 'readonly', '' );
				field.style.position = 'fixed';
				field.style.opacity = '0';
				document.body.appendChild( field );
				field.select();

				try {
					document.execCommand( 'copy' );
					tell();
				} catch ( e ) {} // eslint-disable-line no-empty

				document.body.removeChild( field );
			} );
		} );
	}

	function boot() {
		qsa( '.pfh-post' ).forEach( function ( root ) {
			if ( root.hasAttribute( 'data-pfh-post-ready' ) ) {
				return;
			}

			root.setAttribute( 'data-pfh-post-ready', '' );

			progress( root );
			contents( root );
			copy( root );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	document.addEventListener( 'bricks/ajax/end', boot );
} )();
