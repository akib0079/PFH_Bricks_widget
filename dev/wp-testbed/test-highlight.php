<?php
/**
 * The product highlight banner.
 *
 * The brief: every piece of copy static but connectable to an ACF field
 * later, the image likewise with a supplied fallback, and the prices live
 * from a real product picked in the widget.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-highlight.php';

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function highlight( array $over = [] ) {
	$el       = new PFH_Element_Highlight( [ 'id' => 'h' . wp_rand( 1, 99999 ) ] );
	$el->name = 'pfh-highlight';
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

function text_of( $html ) {
	return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $html ) ) );
}

echo "── it renders a finished banner with nothing configured ──\n";
$html = highlight();
ok( 'the eyebrow is there', false !== strpos( $html, 'Meest gekozen' ) );
ok( 'both title lines are there', false !== strpos( $html, 'Proefpakket' ) && false !== strpos( $html, '3 smaken naar keuze' ) );
ok( 'the description is there', false !== strpos( $html, 'Ontdek de wereld van Gia Giamas' ) );
ok( 'all three selling points are there', 3 === substr_count( $html, 'pfh-hl__point"' ) );
ok( 'the supplied image is used', false !== strpos( $html, 'assets/img/pfh-highlight.jpg' ) );
ok( 'the title is a heading', (bool) preg_match( '#<h2 class="pfh-hl__title">#', $html ) );

echo "\n── the prices come from a real product ──\n";
$sale = wc_get_product_ids_on_sale();
ok( 'the shop has something on sale to test with', ! empty( $sale ) );

$pid     = (int) $sale[0];
$product = wc_get_product( $pid );
$now     = wc_get_price_to_display( $product );
$was     = wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] );

$html = highlight( [ 'product' => (string) $pid ] );
$shown = html_entity_decode( text_of( $html ), ENT_QUOTES, 'UTF-8' );
ok( 'the live price is shown', false !== strpos( $shown, html_entity_decode( wp_strip_all_tags( wc_price( $now ) ), ENT_QUOTES, 'UTF-8' ) ), $shown );
ok( 'the price before the discount is shown', false !== strpos( $shown, html_entity_decode( wp_strip_all_tags( wc_price( $was ) ), ENT_QUOTES, 'UTF-8' ) ) );
ok( 'the saving is worked out, not typed', false !== strpos( $shown, html_entity_decode( wp_strip_all_tags( wc_price( $was - $now ) ), ENT_QUOTES, 'UTF-8' ) ) );
ok( 'the banner links to that product', false !== strpos( $html, esc_url( $product->get_permalink() ) ) );

echo "\n── a product that is not reduced shows one price ──\n";
$full = null;
foreach ( get_posts( [ 'post_type' => 'product', 'numberposts' => -1, 'fields' => 'ids' ] ) as $id ) {
	if ( ! in_array( $id, $sale, true ) ) { $full = wc_get_product( $id ); break; }
}

if ( $full ) {
	$html = highlight( [ 'product' => (string) $full->get_id() ] );
	ok( 'no struck-through price', false === strpos( $html, 'pfh-hl__price-was' ) );
	ok( 'no saving line', false === strpos( $html, 'pfh-hl__save' ) );
	ok( 'but the price is still shown', false !== strpos( $html, 'pfh-hl__price-now' ) );
} else {
	ok( 'a full-price product exists to test with', false );
}

echo "\n── without a product it falls back to what was typed ──\n";
$html = highlight( [ 'product' => '' ] );
ok( 'the typed price is used', false !== strpos( text_of( $html ), '41,97' ) );
ok( 'the typed old price is used', false !== strpos( text_of( $html ), '46,97' ) );
ok( 'the typed saving is used', false !== strpos( html_entity_decode( text_of( $html ), ENT_QUOTES, 'UTF-8' ), 'Bespaar €5,00' ) );

$html = highlight( [ 'product' => (string) $pid, 'priceSource' => 'manual' ] );
ok( 'manual mode ignores the product', false !== strpos( text_of( $html ), '41,97' ) );

echo "\n── every field can be replaced, which is what an ACF field will do ──\n";
$html = highlight( [
	'eyebrow'     => 'Nieuw binnen',
	'titleTop'    => 'Zomerbox',
	'titleBottom' => '5 smaken',
	'text'        => 'Een andere tekst.',
	'points'      => [ [ 'text' => 'Eén punt' ] ],
	'btnLabel'    => 'Bekijk',
] );
ok( 'the eyebrow can be replaced', false !== strpos( $html, 'Nieuw binnen' ) && false === strpos( $html, 'Meest gekozen' ) );
ok( 'both title lines can be replaced', false !== strpos( $html, 'Zomerbox' ) && false !== strpos( $html, '5 smaken' ) );
ok( 'the description can be replaced', false !== strpos( $html, 'Een andere tekst.' ) );
ok( 'the points can be replaced', 1 === substr_count( $html, 'pfh-hl__point"' ) );
ok( 'a button appears when given a label', false !== strpos( $html, 'pfh-hl__btn' ) );

echo "\n── a chosen image replaces the supplied one ──\n";
$att = get_posts( [ 'post_type' => 'attachment', 'numberposts' => 1, 'fields' => 'ids' ] );

if ( $att ) {
	$html = highlight( [ 'image' => [ 'id' => (int) $att[0], 'url' => wp_get_attachment_url( (int) $att[0] ) ] ] );
	ok( 'the chosen image is used', false === strpos( $html, 'assets/img/pfh-highlight.jpg' ) );
	ok( 'and only one image is drawn', 1 === substr_count( $html, 'pfh-hl__img' ) );
} else {
	ok( 'an attachment exists to test with', false );
}

echo "\n── the whole banner is one link, not a link round everything ──\n";
$html = highlight( [ 'product' => (string) $pid ] );
ok( 'exactly one anchor when the card is clickable', 1 === substr_count( $html, '<a ' ), substr_count( $html, '<a ' ) . ' anchors' );
ok( 'the card is marked clickable', false !== strpos( $html, 'is-clickable' ) );

$html = highlight( [ 'product' => (string) $pid, 'clickable' => false, 'btnLabel' => 'Bekijk' ] );
ok( 'with the card not clickable the button is the link', false === strpos( $html, 'is-clickable' ) && false !== strpos( $html, '<a class="pfh-hl__btn"' ) );

echo "\n── it says something useful when it has nothing ──\n";
$html = highlight( [ 'titleTop' => '', 'titleBottom' => '', 'text' => '' ] );
ok( 'nothing renders on the front end', '' === trim( $html ), substr( $html, 0, 60 ) );

echo "\n$pass passed, $fail failed\n";
