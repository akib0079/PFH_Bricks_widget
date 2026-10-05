/**
 * Verhuizen: start the import and keep asking for the next short step
 * until it is done. A step the server does not answer is asked again a few
 * times — the run on the server knows where it was, so asking again never
 * does anything twice. An import left half way can be carried on.
 */
( function () {
	'use strict';

	var cfg = window.pfhMigrate;

	if ( ! cfg ) {
		return;
	}

	function post( action, pairs ) {
		var body = new URLSearchParams();

		body.append( 'action', action );
		body.append( 'nonce', cfg.nonce );

		( pairs || [] ).forEach( function ( pair ) {
			body.append( pair[ 0 ], pair[ 1 ] );
		} );

		return fetch( cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( response ) {
			return response.json();
		} );
	}

	/**
	 * The progress box inside a container, and the button that started it.
	 */
	function runner( container, button, stepAction ) {
		var box = container.querySelector( '.pfh-migrate__progress' );
		var status = container.querySelector( '.pfh-migrate__status' );
		var bar = container.querySelector( 'progress' );
		var list = container.querySelector( '.pfh-migrate__log' );
		var retries = 0;

		function show( data ) {
			var counts = Object.keys( data.counts || {} ).map( function ( key ) {
				return key + ': ' + data.counts[ key ];
			} ).join( ' · ' );

			bar.value = data.total ? Math.round( ( data.done / data.total ) * 100 ) : 0;
			status.textContent = cfg.text.running + ' — ' + data.step + ( counts ? ' (' + counts + ')' : '' );

			list.innerHTML = '';
			( data.log || [] ).forEach( function ( line ) {
				var li = document.createElement( 'li' );

				li.className = 'pfh-migrate__log--' + line.level;
				li.textContent = line.text;
				list.appendChild( li );
			} );
		}

		function fail( message ) {
			status.textContent = cfg.text.failed + ' ' + ( message || '' );
			button.disabled = false;
		}

		function step() {
			post( stepAction ).then( function ( result ) {
				retries = 0;

				if ( ! result || ! result.success ) {
					fail( result && result.data ? result.data.message : '' );
					return;
				}

				show( result.data );

				if ( 'running' === result.data.state ) {
					step();
					return;
				}

				bar.value = 100;
				status.textContent = cfg.text.done;
				window.setTimeout( function () {
					window.location.href = window.location.href.replace( /[&?]plan=[^&]*/, '' );
				}, 1500 );
			} ).catch( function () {
				if ( retries++ < 5 ) {
					status.textContent = cfg.text.retry;
					window.setTimeout( step, 3000 * retries );
					return;
				}

				fail( '' );
			} );
		}

		return {
			begin: function () {
				button.disabled = true;
				box.hidden = false;
				status.textContent = cfg.text.running + '…';
			},
			show: show,
			fail: fail,
			step: step
		};
	}

	// Every runner on the screen: the design import, making room for orders.
	document.querySelectorAll( 'form[data-pfh-run]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			if ( ! window.confirm( form.getAttribute( 'data-confirm' ) || cfg.text.confirm ) ) {
				return;
			}

			var run = runner( form, form.querySelector( 'button[type="submit"]' ), form.getAttribute( 'data-step' ) );
			var pairs = [];

			if ( form.hasAttribute( 'data-package' ) ) {
				pairs.push( [ 'package', form.getAttribute( 'data-package' ) ] );
			}

			form.querySelectorAll( 'input[name="selection[]"]:checked' ).forEach( function ( input ) {
				pairs.push( [ 'selection[]', input.value ] );
			} );

			run.begin();

			post( form.getAttribute( 'data-start' ), pairs ).then( function ( result ) {
				if ( ! result || ! result.success ) {
					run.fail( result && result.data ? result.data.message : '' );
					return;
				}

				run.show( result.data );
				run.step();
			} ).catch( function () {
				run.fail( '' );
			} );
		} );
	} );

	// A run left half way, carried on.
	document.querySelectorAll( '#pfh-migrate-continue, #pfh-renumber-continue' ).forEach( function ( carry ) {
		carry.querySelector( 'button' ).addEventListener( 'click', function () {
			var run = runner( carry, this, carry.getAttribute( 'data-step' ) );

			run.begin();
			run.step();
		} );
	} );
}() );
