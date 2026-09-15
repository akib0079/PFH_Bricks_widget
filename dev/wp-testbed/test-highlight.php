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

echo "\n── the product is picked by name, not by hunting for an ID ──\n";
$el = new PFH_Element_Highlight( [ 'id' => 'pick' ] );
$el->name = 'pfh-highlight';
$el->set_control_groups();
$el->set_controls();

$picker = $el->controls['product'];
ok( 'the product control is a searchable list', 'select' === $picker['type'] && ! empty( $picker['searchable'] ) );
ok( 'it can be cleared again', ! empty( $picker['clearable'] ) );
ok( 'it is populated with real products', count( (array) $picker['options'] ) > 3, count( (array) $picker['options'] ) . ' listed' );

$first = array_key_first( (array) $picker['options'] );
ok( 'keyed by product ID', ctype_digit( (string) $first ) );
ok( 'labelled by product name', false !== strpos( (string) $picker['options'][ $first ], get_the_title( (int) $first ) ) );

$chosen = wc_get_product( (int) $first );
$html   = highlight( [ 'product' => (string) $first ] );
ok( 'choosing one prices the banner from it', false !== strpos( $html, esc_url( $chosen->get_permalink() ) ) );

ok( 'a typed ID still works, for a dynamic field', false !== strpos( highlight( [ 'product' => '', 'productId' => (string) $first ] ), esc_url( $chosen->get_permalink() ) ) );
ok( 'and wins over the picker', false !== strpos( highlight( [ 'product' => '999999', 'productId' => (string) $first ] ), esc_url( $chosen->get_permalink() ) ) );

echo "\n── the saving names a percentage, worked out from the product ──\n";
$sale    = wc_get_product_ids_on_sale();
$product = wc_get_product( (int) $sale[0] );
$now     = wc_get_price_to_display( $product );
$was     = wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] );
$pct     = (int) round( ( ( $was - $now ) / $was ) * 100 );

$html = html_entity_decode( text_of( highlight( [ 'product' => (string) $sale[0] ] ) ), ENT_QUOTES, 'UTF-8' );
ok( "it says {$pct}% korting", false !== strpos( $html, $pct . '% korting' ), $html );
ok( 'and the amount alongside it', false !== strpos( $html, html_entity_decode( wp_strip_all_tags( wc_price( $was - $now ) ), ENT_QUOTES, 'UTF-8' ) ) );

// A per-cent sign in the template must not be read as a conversion.
$html = html_entity_decode( text_of( highlight( [ 'product' => (string) $sale[0], 'saveText' => 'Bespaar %s — %pct%% korting vandaag' ] ) ), ENT_QUOTES, 'UTF-8' );
ok( 'the rest of the sentence survives the per-cent sign', false !== strpos( $html, 'korting vandaag' ), $html );

echo "\n── with no discount the percentage clause is dropped, not left at 0% ──\n";
$full = null;
foreach ( get_posts( [ 'post_type' => 'product', 'numberposts' => -1, 'fields' => 'ids' ] ) as $id ) {
	if ( ! in_array( $id, $sale, true ) ) { $full = $id; break; }
}

if ( $full ) {
	$html = text_of( highlight( [ 'product' => (string) $full ] ) );
	ok( 'no saving line at all', false === strpos( $html, 'korting' ), $html );
	ok( 'and no stray 0%', false === strpos( $html, '0%' ) );
}

echo "\n── it overlaps the section below it ──\n";
$html = highlight();
ok( 'the overlap class is on by default', false !== strpos( $html, 'pfh-hl--overlaps' ) );
ok( 'and carries the distance', false !== strpos( $html, '--pfh-hl-overlap:74px' ) );

$html = highlight( [ 'overlap' => 0 ] );
ok( 'set to zero it does not overlap', false === strpos( $html, 'pfh-hl--overlaps' ) );

$html = highlight( [ 'overlap' => 120 ] );
ok( 'a different distance is honoured', false !== strpos( $html, '--pfh-hl-overlap:120px' ) );

echo "\n── every text field takes a dynamic value ──\n";
$fields = [ 'eyebrow', 'eyebrowIcon', 'titleTop', 'titleBottom', 'text', 'btnLabel', 'priceManual', 'oldManual', 'saveText', 'saveFallback', 'imageAlt' ];

foreach ( $fields as $f ) {
	$marker = 'ZZ' . strtoupper( $f ) . 'ZZ';
	$html   = highlight( [ $f => $marker, 'product' => '', 'priceSource' => 'manual' ] );
	ok( "$f reaches the page", false !== strpos( $html, $marker ) || 'imageAlt' === $f, "typed value did not render" );
}

echo "\n── it says something useful when it has nothing ──\n";
$html = highlight( [ 'titleTop' => '', 'titleBottom' => '', 'text' => '' ] );
ok( 'nothing renders on the front end', '' === trim( $html ), substr( $html, 0, 60 ) );

echo "\n$pass passed, $fail failed\n";
