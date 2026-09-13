/**
 * Cookie consent.
 *
 * The server ships the banner hidden and every third-party tag inert, so one
 * cached copy of the page is correct for every visitor. This decides what to
 * show and releases the tags the visitor agreed to.
 */
( function () {
	'use strict';

	var cfg = window.pfhConsent || {};
	var COOKIE = cfg.cookie || 'pfh_consent';
	var VERSION = parseInt( cfg.version, 10 ) || 1;
	var LIFETIME = parseInt( cfg.lifetime, 10 ) || 182;
	var CATEGORIES = cfg.categories || [ 'statistics', 'marketing' ];

	var root = null;
	var views = {};

	/* ------------------------------------------------------------------
	 * Cookie
	 * --------------------------------------------------------------- */

	function read() {
		var match = document.cookie.match(
			new RegExp( '(?:^|; )' + COOKIE.replace( /([.*+?^${}()|[\]\\])/g, '\\$1' ) + '=([^;]*)' )
		);

		if ( ! match ) {
			return null;
		}

		try {
			var parsed = JSON.parse( decodeURIComponent( match[ 1 ] ) );

			if ( ! parsed || parsed.v !== VERSION || ! Array.isArray( parsed.c ) ) {
				return null;
			}

			return parsed;
		} catch ( e ) {
			return null;
		}
	}

	function write( categories ) {
		var payload = {
			v: VERSION,
			c: categories,
			t: Math.floor( Date.now() / 1000 )
		};

		var parts = [
			COOKIE + '=' + encodeURIComponent( JSON.stringify( payload ) ),
			'path=/',
			'max-age=' + ( LIFETIME * 86400 ),
			'SameSite=Lax'
		];

		if ( 'https:' === location.protocol ) {
			parts.push( 'Secure' );
		}

		document.cookie = parts.join( '; ' );
		window.pfhConsentState = payload;

		return payload;
	}

	/* ------------------------------------------------------------------
	 * Releasing the tags
	 * --------------------------------------------------------------- */

	/**
	 * Turn held <script type="text/plain"> back into real scripts.
	 *
	 * async = false is what keeps execution in document order — dynamically
	 * inserted external scripts are async by default, which would run a tag
	 * before the library it depends on.
	 */
	function releaseScripts( granted ) {
		var held = document.querySelectorAll( 'script[type="text/plain"][data-pfh-consent]' );

		Array.prototype.forEach.call( held, function ( old ) {
			if ( granted.indexOf( old.getAttribute( 'data-pfh-consent' ) ) === -1 ) {
				return;
			}

			var fresh = document.createElement( 'script' );

			Array.prototype.forEach.call( old.attributes, function ( attr ) {
				if ( 'type' === attr.name || 'data-pfh-consent' === attr.name || 'data-pfh-src' === attr.name ) {
					return;
				}

				try {
					fresh.setAttribute( attr.name, attr.value );
				} catch ( e ) {}
			} );

			var src = old.getAttribute( 'data-pfh-src' );

			if ( src ) {
				fresh.async = false;
				fresh.src = src;
			} else {
				fresh.text = old.textContent || '';
			}

			if ( old.parentNode ) {
				old.parentNode.replaceChild( fresh, old );
			}
		} );
	}

	/**
	 * Restore iframes and drop the placeholder that stood in for them.
	 */
	function releaseFrames( granted ) {
		var held = document.querySelectorAll( 'iframe[data-pfh-consent][data-pfh-src]' );

		Array.prototype.forEach.call( held, function ( frame ) {
			if ( granted.indexOf( frame.getAttribute( 'data-pfh-consent' ) ) === -1 ) {
				return;
			}

			var placeholder = frame.previousElementSibling;

			if ( placeholder && placeholder.hasAttribute( 'data-pfh-ck-placeholder' ) ) {
				placeholder.parentNode.removeChild( placeholder );
			}

			frame.src = frame.getAttribute( 'data-pfh-src' );
			frame.removeAttribute( 'data-pfh-src' );
			frame.removeAttribute( 'data-pfh-consent' );
			frame.hidden = false;
		} );
	}

	/**
	 * Tell Google what changed. The defaults were published inline in <head>
	 * before any tag loaded; this is the update half of the same contract.
	 */
	function signal( granted ) {
		if ( ! cfg.consentMode || 'function' !== typeof window.gtag ) {
			return;
		}

		var stats = granted.indexOf( 'statistics' ) > -1 ? 'granted' : 'denied';
		var ads = granted.indexOf( 'marketing' ) > -1 ? 'granted' : 'denied';

		window.gtag( 'consent', 'update', {
			ad_storage: ads,
			ad_user_data: ads,
			ad_personalization: ads,
			analytics_storage: stats,
			personalization_storage: ads
		} );
	}

	function uid() {
		var key = 'pfh_ck_uid';
		var existing = '';

		try {
			existing = window.localStorage.getItem( key ) || '';
		} catch ( e ) {}

		if ( ! existing ) {
			existing = ( Date.now().toString( 16 ) + Math.random().toString( 16 ).slice( 2 ) ).slice( 0, 32 );

			try {
				window.localStorage.setItem( key, existing );
			} catch ( e ) {}
		}

		return existing;
	}

	/**
	 * Record the choice. Best effort — a failed log never blocks the visitor.
	 */
	function log( granted ) {
		if ( ! cfg.log || ! cfg.ajaxUrl || ! window.fetch ) {
			return;
		}

		var body = new URLSearchParams();
		body.set( 'action', 'pfh_consent' );
		body.set( 'nonce', cfg.nonce || '' );
		body.set( 'categories', granted.join( ',' ) );
		body.set( 'uid', uid() );
		body.set( 'source', location.pathname );

		window.fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).catch( function () {} );
	}

	/**
	 * One place where a decision takes effect.
	 */
	function apply( granted, persist ) {
		granted = granted.filter( function ( cat ) {
			return CATEGORIES.indexOf( cat ) > -1;
		} );

		if ( persist ) {
			write( granted );
			log( granted );
		}

		signal( granted );
		releaseScripts( granted );
		releaseFrames( granted );

		document.dispatchEvent(
			new CustomEvent( 'pfh:consent', { detail: { categories: granted } } )
		);
	}

	/* ------------------------------------------------------------------
	 * Panel
	 * --------------------------------------------------------------- */

	function show( view ) {
		if ( ! root ) {
			return;
		}

		root.hidden = false;

		Object.keys( views ).forEach( function ( name ) {
			views[ name ].hidden = name !== view;
		} );

		var focusable = views[ view ] && views[ view ].querySelector( 'button, input' );

		if ( focusable ) {
			focusable.focus( { preventScroll: true } );
		}
	}

	function hide() {
		if ( root ) {
			root.hidden = true;
		}
	}

	function syncSwitches( granted ) {
		if ( ! root ) {
			return;
		}

		var boxes = root.querySelectorAll( '[data-pfh-ck-cat]' );

		Array.prototype.forEach.call( boxes, function ( box ) {
			box.checked = granted.indexOf( box.getAttribute( 'data-pfh-ck-cat' ) ) > -1;
		} );
	}

	function chosen() {
		if ( ! root ) {
			return [];
		}

		return Array.prototype.filter
			.call( root.querySelectorAll( '[data-pfh-ck-cat]' ), function ( box ) {
				return box.checked;
			} )
			.map( function ( box ) {
				return box.getAttribute( 'data-pfh-ck-cat' );
			} );
	}

	function onClick( event ) {
		// event.target is not always an Element — document and text nodes turn
		// up in synthetic events — and closest() would throw on those.
		if ( ! event.target || 'function' !== typeof event.target.closest ) {
			return;
		}

		var trigger = event.target.closest( '[data-pfh-ck]' );

		if ( ! trigger ) {
			return;
		}

		var action = trigger.getAttribute( 'data-pfh-ck' );

		if ( 'reopen' === action ) {
			event.preventDefault();
			syncSwitches( ( read() || { c: [] } ).c );
			show( 'prefs' );
			return;
		}

		if ( 'accept-embed' === action ) {
			event.preventDefault();

			var current = ( read() || { c: [] } ).c.slice();

			if ( current.indexOf( 'marketing' ) === -1 ) {
				current.push( 'marketing' );
			}

			apply( current, true );
			hide();
			return;
		}

		if ( ! root || ! root.contains( trigger ) ) {
			return;
		}

		event.preventDefault();

		switch ( action ) {
			case 'detail': {
				var wasOpen = 'true' === trigger.getAttribute( 'aria-expanded' );
				var detail = document.getElementById( trigger.getAttribute( 'aria-controls' ) );

				trigger.setAttribute( 'aria-expanded', wasOpen ? 'false' : 'true' );

				if ( detail ) {
					detail.hidden = wasOpen;
				}

				break;
			}

			case 'accept':
				apply( CATEGORIES.slice(), true );
				hide();
				break;

			case 'reject':
				apply( [], true );
				hide();
				break;

			case 'save':
				apply( chosen(), true );
				hide();
				break;

			case 'prefs':
				syncSwitches( ( read() || { c: [] } ).c );
				show( 'prefs' );
				break;

			case 'back':
				show( 'banner' );
				break;
		}
	}

	function start() {
		root = document.getElementById( 'pfh-consent' );

		if ( root ) {
			Array.prototype.forEach.call(
				root.querySelectorAll( '[data-pfh-ck-view]' ),
				function ( node ) {
					views[ node.getAttribute( 'data-pfh-ck-view' ) ] = node;
				}
			);
		}

		var stored = read();

		if ( stored ) {
			// A returning visitor: release what they already agreed to and
			// leave the banner down.
			apply( stored.c, false );
		} else if ( root ) {
			show( 'banner' );
		}

		document.addEventListener( 'click', onClick );

		// Let a footer link open the panel without needing the shortcode.
		document.addEventListener( 'click', function ( event ) {
			if ( ! event.target || 'function' !== typeof event.target.closest ) {
				return;
			}

			var link = event.target.closest( 'a[href$="#pfh-cookie-settings"]' );

			if ( ! link ) {
				return;
			}

			event.preventDefault();
			syncSwitches( ( read() || { c: [] } ).c );
			show( 'prefs' );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}

	/**
	 * Small public surface, so other code can ask rather than parse the cookie.
	 */
	window.pfhConsentApi = {
		get: function () {
			return ( read() || { c: [] } ).c;
		},
		has: function ( category ) {
			return ( read() || { c: [] } ).c.indexOf( category ) > -1;
		},
		open: function () {
			syncSwitches( ( read() || { c: [] } ).c );
			show( 'prefs' );
		}
	};
}() );
