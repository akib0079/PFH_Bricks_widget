<?php
/**
 * The shop grid and the home slider are two different cards.
 *
 * They share one trait, so a default changed for one silently restyles the
 * other — which is exactly what happened when the archive's full-width
 * TOEVOEGEN bar was set in the shared controls and every product slider on
 * the site grew one. This pins the differences the designs actually call for.
 */
require __DIR__ . '/wp-load.php';

foreach ( [ 'archive', 'products', 'product-grid' ] as $f ) {
	require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-' . $f . '.php';
}

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function build( $class, $name ) {
	$el       = new $class( [ 'id' => 'd' . $name ] );
	$el->name = $name;
	$el->set_control_groups();
	$el->set_controls();

	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; }
	}

	$el->settings = $s;

	ob_start();
	$el->render();

	return [ 'html' => ob_get_clean(), 'settings' => $s ];
}

function build_with( $class, $name, array $over ) {
	$el       = new $class( [ 'id' => 'stale' ] );
	$el->name = $name;
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

$archEl = new PFH_Element_Archive( [ 'id' => 'ctrls' ] );
$archEl->name = 'pfh-archive';
$archEl->set_control_groups();
$archEl->set_controls();
$archControls = $archEl->controls;

$arch   = build( 'PFH_Element_Archive', 'pfh-archive' );
$slider = build( 'PFH_Element_Products', 'pfh-products' );
$grid   = build( 'PFH_Element_Product_Grid', 'pfh-product-grid' );

echo "── the slider keeps its own card ──\n";
ok( 'slider button sits in the image corner', 'br' === $slider['settings']['cartPosition'], $slider['settings']['cartPosition'] );
ok( 'slider button is round', 999 === (int) $slider['settings']['cartRadius'] );
ok( 'slider button is the olive one', '#51604f' === strtolower( $slider['settings']['cartBg']['hex'] ) );
ok( 'slider markup carries the corner class', false !== strpos( $slider['html'], 'pfh-cart-br' ) );
ok( 'slider has no full-width bar', false === strpos( $slider['html'], 'pfh-cart-block' ) );
ok( 'slider shows stars', false !== strpos( $slider['html'], 'pfh-prod__stars' ) );
ok( 'slider repeats the suffix on the old price', true === (bool) $slider['settings']['priceSuffixOld'] );

echo "\n── the grid keeps its own card ──\n";
ok( 'grid button sits in the image corner', 'br' === $grid['settings']['cartPosition'], $grid['settings']['cartPosition'] );
ok( 'grid has no full-width bar', false === strpos( $grid['html'], 'pfh-cart-block' ) );

echo "\n── the shop grid is the other card ──\n";
ok( 'archive button is the full-width bar', false !== strpos( $arch['html'], 'pfh-cart-block' ) );
ok( 'archive does not offer the button position as a setting', ! isset( $archControls['cartPosition'] ), 'it is fixed by the design' );
ok( 'archive shows stars beside the count', false !== strpos( $arch['html'], 'pfh-prod__stars' ) );
ok( 'archive button is teal', '#7caeb2' === strtolower( $arch['settings']['cartBg']['hex'] ) );
ok( 'archive button has the 5px radius', 5 === (int) $arch['settings']['cartRadius'] );
ok( 'archive markup carries the block class', false !== strpos( $arch['html'], 'pfh-cart-block' ) );
ok( 'archive button is always visible', false !== strpos( $arch['html'], 'pfh-reveal-none' ) );
ok( 'archive button reads TOEVOEGEN', false !== strpos( $arch['html'], 'TOEVOEGEN' ) );
ok( 'archive suffix is on the current price only', false === (bool) $arch['settings']['priceSuffixOld'] );
ok( 'archive suffix is Dutch', 'incl. BTW' === $arch['settings']['priceSuffix'] );

echo "\n── the two never become the same card ──\n";
ok( 'cartRadius differs between slider and shop grid', $slider['settings']['cartRadius'] !== $arch['settings']['cartRadius'] );

foreach ( [ 'pfh-cart-block' => 'the full-width bar', 'pfh-reveal-none' => 'the always-visible reveal' ] as $cls => $what ) {
	ok( "the slider never gets $what", false === strpos( $slider['html'], $cls ) );
	ok( "the shop grid always gets $what", false !== strpos( $arch['html'], $cls ) );
}

ok( 'the slider keeps the corner button class', false !== strpos( $slider['html'], 'pfh-cart-br' ) );
ok( 'cartBg differs', $slider['settings']['cartBg'] !== $arch['settings']['cartBg'] );

// The suffix, counted in the markup rather than read off the settings.
preg_match( '#<article class="pfh-prod__card">.*?</article>#s', $arch['html'], $m );
$card = $m[0] ?? '';
ok( 'a shop card prints the suffix once', 1 === substr_count( $card, 'pfh-prod__suffix' ), substr_count( $card, 'pfh-prod__suffix' ) . ' times' );

preg_match( '#<article class="pfh-prod__card">.*?</article>#s', $slider['html'], $m );
$scard = $m[0] ?? '';
$n = substr_count( $scard, 'pfh-prod__suffix' );
ok( 'a slider card prints it on both prices when both are shown', $n >= 1, "$n times" );

echo "\n── a card saved under an older build cannot revive the old layout ──\n";
$stale = build_with( 'PFH_Element_Archive', 'pfh-archive', [
	'cartPosition' => 'br',
	'cartReveal'   => 'up',
] );
ok( 'a stored cartPosition is ignored', false !== strpos( $stale, 'pfh-cart-block' ) );
ok( 'the old corner class never appears', false === strpos( $stale, 'pfh-cart-br' ) );
ok( 'the reveal is pinned to always-visible', false !== strpos( $stale, 'pfh-reveal-none' ) );

echo "\n$pass passed, $fail failed\n";
