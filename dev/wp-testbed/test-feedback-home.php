<?php
/**
 * The home page on a phone, after the client's feedback (2026-09-28).
 *
 * The hero leads with its picture and loses its rating, headline and second
 * button; the categories sit two by two; the features show their first tile
 * and no heading. All of it is phone-only CSS keyed off classes checked here.
 */
require __DIR__ . '/wp-load.php';
foreach ( [ 'hero', 'categories', 'features' ] as $f ) {
	require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-' . $f . '.php';
}
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function draw( $class, array $over = [] ) {
	$el       = new $class( [ 'id' => 'h' . substr( md5( $class . serialize( $over ) ), 0, 6 ) ] );
	$el->name = 'pfh-test';
	$el->set_control_groups();
	$el->set_controls();
	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; }
	}
	$el->settings = array_merge( $s, $over );
	ob_start();
	$el->render();
	return ob_get_clean();
}

$hero = draw( 'PFH_Element_Hero' );
ok( 'the hero is compact on phones by default', false !== strpos( $hero, 'is-phone-compact' ) );
ok( '  its first sentence can stand in for the headline', (bool) preg_match( '#<span class="pfh-hero__lead">[^<]+[.!?]</span>#', $hero ) );
ok( '  and it can be switched off', false === strpos( draw( 'PFH_Element_Hero', [ 'phoneCompact' => false ] ), 'is-phone-compact' ) );

$css = file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/assets/css/pfh-hero.css' );
ok( '  on a phone the picture comes first and the rest is left out', false !== strpos( $css, '.pfh-hero.is-phone-compact .pfh-hero__col--media { order: 0; }' ) && false !== strpos( $css, '.pfh-hero.is-phone-compact .pfh-hero__btn--ghost' ) );

ok( 'categories sit two by two on phones', false !== strpos( draw( 'PFH_Element_Categories' ), 'is-phone-grid' ) );
$feat = draw( 'PFH_Element_Features' );
ok( 'features drop their heading on phones', false !== strpos( $feat, 'is-phone-no-head' ) );
ok( '  and show their first tile only', false !== strpos( $feat, 'is-phone-first-only' ) );

echo "\n$pass passed, $fail failed\n";
