/**
 * Products For Home — the waitlist (PFH_Widgets_Waitlist).
 *
 * Opens the form from a sold-out product's button, sends the sign-up
 * without leaving the page, and lets a customer take one off their list in
 * My Account.
 */
( function () {
	'use strict';

	var config = window.pfhWaitlist || {};
	var messages = config.messages || {};
	var lastFocus = null;

	function say( form, text, ok ) {
		var box = form.querySelector( '[data-pfh-wl-message]' );

		if ( ! box ) {
			return;
		}

		box.textContent = text || '';
		box.hidden = ! text;
		box.classList.toggle( 'is-ok', !! ok );
	}

	function open( root ) {
		var modal = root.pfhWlModal || root.querySelector( '[data-pfh-wl-modal]' );

		if ( ! modal ) {
			return;
		}

		/*
		 * Out to the end of the page: a fixed layer inside the product's
		 * section would be held in by any parent with a transform, and sit
		 * under the sticky header.
		 */
		if ( modal.parentNode !== document.body ) {
			root.pfhWlModal = modal;
			modal.pfhWlRoot = root;
			document.body.appendChild( modal );
		}

		lastFocus = document.activeElement;
		modal.hidden = false;
		document.documentElement.classList.add( 'pfh-wl-open' );
		document.body.style.overflow = 'hidden';

		var field = modal.querySelector( 'input[name="email"]' );

		window.setTimeout( function () {
			( field && ! field.value ? field : modal.querySelector( '.pfh-wl__submit' ) || modal ).focus();
		}, 30 );
	}

	function close( modal ) {
		if ( ! modal || modal.hidden ) {
			return;
		}

		modal.hidden = true;
		document.documentElement.classList.remove( 'pfh-wl-open' );
		document.body.style.overflow = '';

		if ( lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}
	}

	function submit( form ) {
		var modal = form.closest( '[data-pfh-wl-modal]' );
		var root = modal && modal.pfhWlRoot ? modal.pfhWlRoot : form.closest( '[data-pfh-wl]' );
		var email = form.querySelector( 'input[name="email"]' );
		var consent = form.querySelector( 'input[name="consent"]' );

		if ( form.classList.contains( 'is-busy' ) ) {
			return;
		}

		if ( ! email || ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email.value.trim() ) ) {
			say( form, messages[ 'bad-email' ] );
			if ( email ) {
				email.focus();
			}

			return;
		}

		if ( consent && ! consent.checked ) {
			say( form, messages.consent );
			consent.focus();

			return;
		}

		var data = new window.FormData( form );

		data.append( 'action', config.action || 'pfh_waitlist_join' );
		form.classList.add( 'is-busy' );
		say( form, '' );

		window.fetch( config.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' } )
			.then( function ( response ) {
				return response.json().catch( function () {
					return { success: false, data: {} };
				} );
			} )
			.then( function ( payload ) {
				var state = payload && payload.data && payload.data.state ? payload.data.state : 'error';

				if ( payload && payload.success ) {
					form.classList.add( 'is-done' );
					say( form, messages[ state ] || messages.added, true );

					if ( root ) {
						root.classList.add( 'is-joined' );
					}

					var shut = form.querySelector( '.pfh-wl__close' );

					if ( shut ) {
						shut.focus();
					}

					return;
				}

				say( form, messages[ state ] || messages.error );
			} )
			.catch( function () {
				say( form, messages.error );
			} )
			.then( function () {
				form.classList.remove( 'is-busy' );
			} );
	}

	document.addEventListener( 'click', function ( event ) {
		var opener = event.target.closest( '[data-pfh-wl-open]' );

		if ( opener ) {
			event.preventDefault();
			open( opener.closest( '[data-pfh-wl]' ) );

			return;
		}

		var closer = event.target.closest( '[data-pfh-wl-close]' );

		if ( closer ) {
			event.preventDefault();
			close( closer.closest( '[data-pfh-wl-modal]' ) );

			return;
		}

		var leave = event.target.closest( '[data-pfh-wl-leave]' );

		if ( leave && ! leave.disabled ) {
			var data = new window.FormData();

			data.append( 'action', 'pfh_waitlist_leave' );
			data.append( 'id', leave.getAttribute( 'data-pfh-wl-leave' ) );
			data.append( 'nonce', leave.getAttribute( 'data-nonce' ) || '' );
			leave.disabled = true;

			window.fetch( config.ajaxUrl || '/wp-admin/admin-ajax.php', { method: 'POST', body: data, credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( payload ) {
					var item = leave.closest( 'li' );

					if ( payload && payload.success && item ) {
						item.remove();
					} else {
						leave.disabled = false;
					}
				} )
				.catch( function () {
					leave.disabled = false;
				} );
		}
	} );

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target.closest( '[data-pfh-wl-form]' );

		if ( form ) {
			event.preventDefault();
			submit( form );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' !== event.key ) {
			return;
		}

		var modal = document.querySelector( '[data-pfh-wl-modal]:not([hidden])' );

		if ( modal ) {
			close( modal );
		}
	} );
}() );
