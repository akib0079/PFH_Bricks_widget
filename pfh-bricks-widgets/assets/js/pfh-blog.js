/**
 * Products For Home – the blog's load more.
 *
 * The button ships as a link to page two, so a browser with no scripting still
 * gets there. This turns it into a button that appends the next cards instead,
 * and puts it back to a link if the request ever fails — there is always a way
 * to the rest of the posts.
 */
( function () {
	'use strict';

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function cfg() {
		var c = window.pfhWidgets || {};

		return { ajaxUrl: c.ajaxUrl || '', nonce: c.nonce || '' };
	}

	function Loader( link ) {
		var c = cfg();

		if ( ! c.ajaxUrl || ! c.nonce ) {
			return;
		}

		this.link = link;
		this.root = link.closest( '.pfh-blog' );
		this.grid = this.root && this.root.querySelector( '[data-pfh-blog-grid]' );

		if ( ! this.grid ) {
			return;
		}

		try {
			this.query = JSON.parse( link.getAttribute( 'data-pfh-blog-query' ) || '{}' );
		} catch ( e ) {
			this.query = {};
		}

		this.page = parseInt( link.getAttribute( 'data-pfh-blog-page' ), 10 ) || 2;
		this.busy = false;

		// It is a button now: it adds to the page rather than leaving it.
		link.setAttribute( 'role', 'button' );

		var self = this;

		link.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			self.fetch();
		} );
	}

	Loader.prototype.fetch = function () {
		if ( this.busy ) {
			return;
		}

		this.busy = true;
		this.link.classList.add( 'is-busy' );

		var self = this;
		var c = cfg();
		var body = new URLSearchParams();

		body.set( 'action', 'pfh_posts' );
		body.set( 'nonce', c.nonce );
		body.set( 'page', String( this.page ) );
		body.set( 'query', JSON.stringify( this.query ) );

		window.fetch( c.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( ! data || ! data.success || ! data.data ) {
					throw new Error( '' );
				}

				self.append( data.data.html || '' );

				if ( ! data.data.more ) {
					self.link.remove();

					return;
				}

				self.page++;
				self.link.setAttribute( 'data-pfh-blog-page', String( self.page ) );
				self.link.href = self.link.href.replace( /\/page\/\d+/, '/page/' + self.page );
			} )
			.catch( function () {
				/*
				 * Put it back to being a link. The next click leaves for page
				 * two the ordinary way, which is where the posts are.
				 */
				self.link.removeAttribute( 'role' );
				self.link.replaceWith( self.link.cloneNode( true ) );
			} )
			.then( function () {
				self.busy = false;
				self.link.classList.remove( 'is-busy' );
			} );
	};

	/**
	 * Put the new cards in, and send the reader to the first of them.
	 *
	 * @param {string} html Markup for the cards.
	 */
	Loader.prototype.append = function ( html ) {
		if ( ! html ) {
			return;
		}

		var holder = document.createElement( 'div' );

		holder.innerHTML = '<ul>' + html + '</ul>';

		var added = Array.prototype.slice.call( holder.firstChild.children );

		added.forEach( function ( card ) {
			this.grid.appendChild( card );
		}, this );

		// Where the reader was is now above a screenful of new posts; the
		// first of them is what they asked for.
		if ( added[0] ) {
			added[0].setAttribute( 'tabindex', '-1' );
			added[0].focus( { preventScroll: true } );
		}
	};

	function boot() {
		qsa( '[data-pfh-blog-load]' ).forEach( function ( link ) {
			if ( ! link.pfhBlog ) {
				link.pfhBlog = new Loader( link );
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
