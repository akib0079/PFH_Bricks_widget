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

		// The contents always begins folded so it never competes with the
		// article. Native <details> keeps it usable without JavaScript.
		toc.open = false;
	}

	/* ------------------------------------------------------------------
	 * Popular product shelf
	 * --------------------------------------------------------------- */

	function products( root ) {
		qsa( '[data-pfh-post-products]', root ).forEach( function ( track ) {
			var section = track.closest( '.pfh-post__products' );
			var buttons = qsa( '[data-pfh-post-products-direction]', section || root );

			if ( ! buttons.length ) {
				return;
			}

			function update() {
				var max = Math.max( 0, track.scrollWidth - track.clientWidth );
				var left = Math.max( 0, track.scrollLeft );

				buttons.forEach( function ( button ) {
					var previous = 'prev' === button.getAttribute( 'data-pfh-post-products-direction' );

					button.disabled = previous ? left <= 2 : left >= max - 2;
				} );
			}

			buttons.forEach( function ( button ) {
				button.addEventListener( 'click', function () {
					var direction = 'prev' === button.getAttribute( 'data-pfh-post-products-direction' ) ? -1 : 1;
					var amount = Math.max( 220, track.clientWidth * .75 );

					track.scrollBy( {
						left: amount * direction,
						behavior: reduced() ? 'auto' : 'smooth'
					} );
				} );
			} );

			track.addEventListener( 'scroll', update, { passive: true } );
			window.addEventListener( 'resize', update, { passive: true } );
			update();
		} );
	}

	/* ------------------------------------------------------------------
	 * Search shortcut
	 * --------------------------------------------------------------- */

	function visibleHeaderSearch() {
		var buttons = qsa( '.pfh-header [data-pfh-open="search"]' );

		for ( var i = 0; i < buttons.length; i++ ) {
			if ( buttons[ i ].offsetParent !== null ) {
				return buttons[ i ];
			}
		}

		return buttons.length ? buttons[ 0 ] : null;
	}

	function openSearch() {
		var button = visibleHeaderSearch();

		if ( ! button ) {
			return false;
		}

		button.click();

		return true;
	}

	function search( root ) {
		qsa( '[data-pfh-post-search]', root ).forEach( function ( trigger ) {
			trigger.addEventListener( 'click', function ( event ) {
				if ( openSearch() ) {
					event.preventDefault();
				}
			} );
		} );

		if ( document.documentElement.hasAttribute( 'data-pfh-post-search-key' ) ) {
			return;
		}

		document.documentElement.setAttribute( 'data-pfh-post-search-key', '' );
		document.addEventListener( 'keydown', function ( event ) {
			var target = event.target;
			var editing = target && ( /^(INPUT|TEXTAREA|SELECT)$/.test( target.tagName ) || target.isContentEditable );

			if ( editing || 'k' !== String( event.key ).toLowerCase() || ( ! event.metaKey && ! event.ctrlKey ) ) {
				return;
			}

			if ( openSearch() ) {
				event.preventDefault();
			}
		} );
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
			products( root );
			search( root );
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
