<?php
/**
 * The shop card's button states, and the settings the editor owns.
 *
 * Three of these are regressions the client reported: a label that vanished
 * on page two, an uploaded icon that never stuck, and a title size that would
 * not take.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-archive.php';

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function build( array $over = [] ) {
	$el       = new PFH_Element_Archive( [ 'id' => 'st' . wp_rand( 1, 99999 ) ] );
	$el->name = 'pfh-archive';
	$el->set_control_groups();
	$el->set_controls();

	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; }
	}

	$el->settings = array_merge( $s, [ 'perPage' => 30, 'catsEnable' => false, 'filterEnable' => false, 'pagerEnable' => false ], $over );

	ob_start();
	$el->render();

	return ob_get_clean();
}

echo "── out of stock ──\n";
$html = build();
ok( 'an out-of-stock card says so', false !== strpos( $html, 'Niet beschikbaar' ) );
ok( 'it is not a link', (bool) preg_match( '#<span class="pfh-prod__cart is-unavailable"#', $html ) );
ok( 'it carries aria-disabled', false !== strpos( $html, 'aria-disabled="true"' ) );

preg_match( '#<span class="pfh-prod__cart is-unavailable".*?</span>\s*</article>#s', $html, $m );
$oos = $m[0] ?? '';
ok( 'it has no basket icon', false === strpos( $oos, '<svg' ), 'an icon is still drawn' );
ok( 'it has no add-to-cart hook', false === strpos( $oos, 'data-pfh-add' ) );

$in = substr_count( $html, 'data-pfh-add' );
ok( 'in-stock products still get a working button', $in > 0, "$in found" );

echo "\n── settings the editor owns are never eaten ──\n";
$html = build( [ 'titleSize' => 17 ] );
ok( 'a 17px title sticks', false !== strpos( $html, '--pfh-t-size-set:17px' ), 'the migration ate it' );

$html = build( [ 'cartLabel' => 'IN MANDJE' ] );
ok( 'a chosen button label sticks', false !== strpos( $html, 'IN MANDJE' ) );

$html = build( [ 'cartBg' => [ 'hex' => '#123456' ] ] );
ok( 'a chosen button colour sticks', false !== strpos( $html, '--pfh-cart-bg:#123456' ) );

echo "\n── an uploaded icon replaces the built-in one and stays ──\n";
$att = get_posts( [ 'post_type' => 'attachment', 'numberposts' => 1, 'fields' => 'ids' ] );

if ( $att ) {
	$id  = (int) $att[0];
	$url = wp_get_attachment_url( $id );

	$html = build( [ 'cartIcon' => [ 'id' => $id, 'url' => $url ] ] );
	ok( 'the uploaded file is used', false !== strpos( $html, 'pfh-prod__cart-icon" src=' ), 'the upload was dropped' );
	ok( 'the built-in basket is not also drawn', false === strpos( $html, 'viewBox="0 0 10 10"' ) );

	// The shape Bricks stores when only a url is known.
	$html = build( [ 'cartIcon' => [ 'url' => $url ] ] );
	ok( 'a url-only value works too', false !== strpos( $html, 'pfh-prod__cart-icon" src=' ) );
} else {
	ok( 'an attachment exists to test with', false, 'no media in the library' );
}

echo "\n── and the design still reaches an untouched card ──\n";
$html = build();
ok( 'the button is teal', false !== strpos( $html, '--pfh-cart-bg:#7caeb2' ) );
ok( 'the title is 17px', false !== strpos( $html, '--pfh-t-size-set:17px' ) );
ok( 'stars are back', false !== strpos( $html, 'pfh-prod__stars' ) );
ok( 'the label is TOEVOEGEN', false !== strpos( $html, 'TOEVOEGEN' ) );

echo "\n── page two keeps the label ──\n";
$el       = new PFH_Element_Archive( [ 'id' => 'ajaxpage' ] );
$el->name = 'pfh-archive';
$el->set_control_groups();
$el->set_controls();
$s = [];
foreach ( $el->controls as $k => $c ) { if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; } }
$el->settings = array_merge( $s, [ 'perPage' => 8 ] );
ob_start(); $el->render(); ob_end_clean();

// Exactly what the filter endpoint does for the second page.
$data = PFH_Element_Archive::ajax_render( null, 'ajaxpage', [ 'pfh_page' => '2' ] );

ok( 'the second page renders', is_array( $data ) && ! empty( $data['results'] ) );
ok( 'its buttons still say TOEVOEGEN', false !== strpos( $data['results'], 'TOEVOEGEN' ), 'the label was lost again' );
ok( 'its buttons are still the full-width bar', false !== strpos( $data['results'], 'pfh-prod__cart-label' ) );
ok( 'it is a different page of products', false === strpos( $data['results'], 'Bijenwas kaars groot' ) );

echo "\n$pass passed, $fail failed\n";
