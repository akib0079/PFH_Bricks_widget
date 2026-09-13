/**
 * Products For Home – marquee filler.
 *
 * The CSS loop translates the track by -50%, which is seamless only while the
 * two halves are identical AND together cover more than the visible band. With
 * a short phrase list on a wide screen one half is narrower than the strip, so
 * the loop shows a gap before it wraps — the wider the monitor, the worse.
 *
 * This clones the group until the track is comfortably wider than the strip,
 * always keeping an even number of copies so -50% still lands exactly one set
 * over, and scales the duration with the extra copies so the words keep moving
 * at the speed the element asked for rather than N times faster.
 */
( function () {
	'use strict';

	var SETS = [
		{ root: '.pfh-hero__marquee', track: '.pfh-hero__marquee-track', group: '.pfh-hero__marquee-group' },
		{ root: '.pfh-feat__mq', track: '.pfh-feat__mq-track', group: '.pfh-feat__mq-group' }
	];

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function fill( root, trackSel, groupSel ) {
		var track = root.querySelector( trackSel );

		if ( ! track ) {
			return;
		}

		var groups = qsa( groupSel, track );

		if ( ! groups.length ) {
			return;
		}

		// Remember the authored state so a resize re-solves from scratch
		// rather than compounding the clones it made last time.
		if ( ! track.pfhBase ) {
			track.pfhBase = {
				html: track.innerHTML,
				count: groups.length,
				time: parseFloat( window.getComputedStyle( track ).animationDuration ) || 30
			};
		} else {
			track.innerHTML = track.pfhBase.html;
			groups = qsa( groupSel, track );
		}

		var one = groups[ 0 ].getBoundingClientRect().width;
		var strip = root.getBoundingClientRect().width;

		if ( ! one || ! strip ) {
			return;
		}

		// One full set has to out-run the strip, then it is doubled so the
		// -50% wrap is invisible.
		var perSet = Math.max( 1, Math.ceil( strip / one ) + 1 );
		var need = perSet * 2;
		var have = groups.length;

		for ( var i = have; i < need; i++ ) {
			track.appendChild( groups[ i % have ].cloneNode( true ) );
		}

		// Distance travelled is half the track, so the duration scales with
		// the number of sets to hold the authored pixels-per-second.
		track.style.animationDuration = ( track.pfhBase.time * perSet ) + 's';
	}

	function boot() {
		SETS.forEach( function ( set ) {
			qsa( set.root ).forEach( function ( root ) {
				fill( root, set.track, set.group );
			} );
		} );
	}

	var frame = null;

	function schedule() {
		if ( frame ) {
			window.cancelAnimationFrame( frame );
		}

		frame = window.requestAnimationFrame( function () {
			frame = null;
			boot();
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	// Fonts land after first paint and change every group's width.
	if ( document.fonts && document.fonts.ready ) {
		document.fonts.ready.then( schedule );
	}

	window.addEventListener( 'resize', schedule );
	window.addEventListener( 'load', schedule );
	document.addEventListener( 'bricks/ajax/end', schedule );
} )();
