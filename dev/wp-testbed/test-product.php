<?php
/**
 * The single product section.
 *
 * Everything on it comes from the product, so the checks that matter are the
 * ones about reading it correctly: the right category in the breadcrumbs, the
 * featured image first, the amount saved rather than a percentage, a variation
 * resolved from the defaults, and the two custom fields left out entirely when
 * they are empty.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-product.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

/*
 * Its own fixture, removed again at the end. The archive suite asserts against
 * a known catalogue, so a product left behind here would break the numbers
 * over there — which is exactly what it did the first time.
 */
$fixture    = require __DIR__ . '/fixture-variable-product.php';
$fixture_id = (int) $fixture['id'];
$variable   = get_post( $fixture_id );

if ( ! $variable ) {
	echo "The fixture could not be built.\n";
	exit;
}

$simple_id = (int) ( get_posts( [ 'post_type' => 'product', 'numberposts' => 1, 'fields' => 'ids', 'exclude' => [ $variable->ID ] ] )[0] ?? 0 );

/**
 * Render for a product, the way a live page does.
 */
function pdp( $product_id, array $settings = [], $build = false ) {
	$el       = new PFH_Element_Product( [ 'id' => 'pdp' ] );
	$el->name = 'pfh-product';

	if ( $build ) {
		$el->set_control_groups();
		$el->set_controls();
	}

	$el->settings = array_merge( [ 'previewId' => (string) $product_id ], $settings );

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

function controls() {
	$el       = new PFH_Element_Product( [ 'id' => 'c' ] );
	$el->name = 'pfh-product';
	$el->set_control_groups();
	$el->set_controls();

	return $el->controls;
}

add_filter( 'pfh_webwinkelkeur_pre_summary', function () {
	return [ 'rating' => 9.7, 'count' => 396, 'scale' => 10 ];
} );

$html = pdp( $variable->ID );

echo "── breadcrumbs ──\n";
ok( 'the trail is there', false !== strpos( $html, 'pfh-pdp__crumbs' ) );
ok( 'starting at home', false !== strpos( $html, '>Home</a>' ) );
ok( 'through the category it is filed under', false !== strpos( $html, '>Traditionele</a>' ) );
// Named from the data rather than typed here: the demo shop already had a
// category of its own spelling, and the test should follow the shop.
$deep   = get_term_by( 'slug', 'traditionele', 'product_cat' );
$parent = $deep ? get_term( $deep->parent, 'product_cat' ) : null;
ok( 'and the aisle above it', $parent && false !== strpos( $html, '>' . $parent->name . '</a>' ), $parent ? $parent->name : 'no parent' );
ok( 'ending on the product, unlinked', false !== strpos( $html, '<span aria-current="page">PFH...Fixture Starterspakket</span>' ) );
ok( 'the separator sits between them', 3 === substr_count( $html, 'pfh-pdp__sep' ) );
ok( 'and it can be turned off', false === strpos( pdp( $variable->ID, [ 'crumbsEnable' => false ] ), 'pfh-pdp__crumbs' ) );

echo "\n── gallery ──\n";
ok( 'the featured image comes first', false !== strpos( $html, 'pfh-pdp__shot is-active' ) );
ok( 'every image is in the strip', 3 === substr_count( $html, 'data-pfh-shot="' ) );
ok( 'with a thumbnail each', 3 === substr_count( $html, 'data-pfh-shot-go="' ) );
ok( 'and arrows either side', 2 === substr_count( $html, 'data-pfh-shot-step' ) );
ok( 'thumbnails can be turned off', false === strpos( pdp( $variable->ID, [ 'galThumbs' => false ] ), 'pfh-pdp__thumbs' ) );

/*
 * One image is not a gallery: arrows and thumbnails that go nowhere are worse
 * than none, so they are left out rather than drawn inert.
 */
$one = pdp( $simple_id );
ok( 'a product with one image gets no arrows', false === strpos( $one, 'data-pfh-shot-step' ) );
ok( 'and no thumbnails', false === strpos( $one, 'pfh-pdp__thumbs' ) );
ok( 'but still shows the image', false !== strpos( $one, 'pfh-pdp__shot' ) );

echo "\n── the tag ──\n";
ok( 'it shows when the product has one', false !== strpos( $html, '<span class="pfh-pdp__badge">SALES</span>' ) );
ok( 'and nothing at all when it does not', false === strpos( pdp( $simple_id ), 'pfh-pdp__badge' ) );

update_post_meta( $variable->ID, '_my_own_tag', 'NIEUW' );
ok( 'another field can be pointed at', false !== strpos( pdp( $variable->ID, [ 'badgeMeta' => '_my_own_tag' ] ), '>NIEUW<' ) );
delete_post_meta( $variable->ID, '_my_own_tag' );

echo "\n── title, category and description ──\n";
ok( 'the category sits above the title', false !== strpos( $html, '<p class="pfh-pdp__eyebrow">Traditionele</p>' ) );
ok( 'the title is the product name', false !== strpos( $html, '<h1 class="pfh-pdp__title">PFH...Fixture Starterspakket</h1>' ) );
ok( 'the short description follows it', false !== strpos( $html, 'Het complete starterspakket' ) );
ok( 'the tag can step down to h2', false !== strpos( pdp( $variable->ID, [ 'titleTag' => 'h2' ] ), '<h2 class="pfh-pdp__title"' ) );
ok( 'the description can be left off', false === strpos( pdp( $variable->ID, [ 'showExcerpt' => false ] ), 'pfh-pdp__excerpt' ) );

echo "\n── the shop's rating, not this product's ──\n";
ok( 'the row is drawn', false !== strpos( $html, 'pfh-pdp__rating' ) );
ok( 'with the score', false !== strpos( $html, '<span class="pfh-pdp__score">9,7</span>' ) || false !== strpos( $html, '<span class="pfh-pdp__score">9.7</span>' ) );
ok( 'and how many reviews', false !== strpos( $html, '396 Reviews' ) );

/*
 * The row reads the shop's figures, not the API alone: on the live site the
 * footer showed 9,7 from the shop's own settings while this row — which asked
 * the API directly — showed nothing at all.
 */
remove_all_filters( 'pfh_webwinkelkeur_pre_summary' );
delete_transient( 'pfh_wwk_summary' );
$offline = pdp( $variable->ID );
ok( 'with no live rating it still shows the shop\'s own', false !== strpos( $offline, 'pfh-pdp__rating' ) );
add_filter( 'pfh_webwinkelkeur_pre_summary', function () {
	return [ 'rating' => 9.7, 'count' => 396, 'scale' => 10 ];
} );
ok( 'the WebwinkelKeur mark beside it', false !== strpos( $html, 'pfh-webwinkelkeur.png' ) );
ok( 'and a link through to them', false !== strpos( $html, 'Lees reviews' ) );
ok( 'the wording is a setting', false !== strpos( pdp( $variable->ID, [ 'reviewsCountText' => '%s beoordelingen' ] ), '396 beoordelingen' ) );

echo "\n── price: the amount saved, not a percentage ──\n";
ok( 'the sale price leads', false !== strpos( $html, 'pfh-pdp__price-now' ) && false !== strpos( $html, '85,96' ) );
ok( 'the old price is struck through', false !== strpos( $html, 'pfh-pdp__price-was' ) && false !== strpos( $html, '99,96' ) );
ok( 'and what it saves is named in euros', false !== strpos( $html, '14 VOORDEEL' ) );
ok( 'not as a percentage', false === strpos( $html, '% VOORDEEL' ) && false === strpos( $html, '14%' ) );
ok( 'the wording is a setting', false !== strpos( pdp( $variable->ID, [ 'savingSuffix' => 'KORTING' ] ), '14 KORTING' ) );
ok( 'and it can be turned off', false === strpos( pdp( $variable->ID, [ 'showSaving' => false ] ), 'pfh-pdp__save">' ) );

echo "\n── variants are pills, and a real field behind them ──\n";
ok( 'a group for each attribute', 2 === substr_count( $html, 'data-pfh-attr="' ) );
ok( 'a pill for every option', 9 === substr_count( $html, 'data-pfh-pill="' ) );
ok( 'the default choice is already made', 2 === substr_count( $html, 'pfh-pdp__pill is-chosen' ) );
ok( 'each group keeps its select, named as WooCommerce expects', false !== strpos( $html, 'name="attribute_pa_soort"' ) && false !== strpos( $html, 'name="attribute_pa_smaak"' ) );
ok( 'with room to name the choice beside the label', 2 === substr_count( $html, 'data-pfh-attr-value' ) );
ok( 'and it is all optional', false === strpos( pdp( $variable->ID, [ 'showVariants' => false ] ), 'pfh-pdp__pills' ) );

preg_match( '/data-variations="([^"]*)"/', $html, $m );
$variations = json_decode( html_entity_decode( $m[1] ?? '[]', ENT_QUOTES ), true );

ok( 'the variations travel with the form', is_array( $variations ) && 14 === count( $variations ) );
ok( 'each carrying its own prices', isset( $variations[0]['now'] ) && isset( $variations[0]['was'] ) && isset( $variations[0]['save'] ) );
$sold_out = count( array_filter( $variations, static function ( $v ) { return ! $v['buyable']; } ) );
$expected = 0;
foreach ( wc_get_product( $variable->ID )->get_children() as $child ) {
	$v = wc_get_product( $child );
	if ( $v && ! ( $v->is_purchasable() && $v->is_in_stock() ) ) { $expected++; }
}
ok( 'and whether it can be bought at all', $expected > 0 && $sold_out === $expected, "$sold_out flagged, $expected actually unavailable" );

echo "\n── what is in the box ──\n";
ok( 'each line is listed', 3 === substr_count( $html, 'pfh-pdp__line-label' ) );
ok( 'with its tick', 3 === substr_count( $html, 'pfh-pdp__tick' ) );
ok( 'and the note on the right', false !== strpos( $html, '33 glazen van 467ml' ) );
ok( 'a product without any gets no block at all', false === strpos( pdp( $simple_id ), 'pfh-pdp__box' ) );

update_post_meta( $variable->ID, '_acf_style', [ [ 'text' => 'Van een ander veld' ] ] );
ok( 'another field shape is understood', false !== strpos( pdp( $variable->ID, [ 'highlightsMeta' => '_acf_style' ] ), 'Van een ander veld' ) );
delete_post_meta( $variable->ID, '_acf_style' );

echo "\n── quantity and the cart ──\n";
ok( 'there is a stepper', 2 === substr_count( $html, 'data-pfh-qty="' ) );
ok( 'writing single figures as 01', false !== strpos( $html, 'data-pfh-qty-pad' ) );
ok( 'the button carries the label', false !== strpos( $html, 'Voeg toe aan winkelmand' ) );
ok( 'the form knows which product it is', false !== strpos( $html, 'data-product="' . $variable->ID . '"' ) );
ok( 'and adds without reloading', false !== strpos( $html, 'data-pfh-ajax' ) );
ok( 'which can be turned off', false === strpos( pdp( $variable->ID, [ 'ajaxCart' => false ] ), 'data-pfh-ajax' ) );
ok( 'the stepper can be left out', false === strpos( pdp( $variable->ID, [ 'showQty' => false ] ), 'pfh-pdp__qty"' ) );

$gone = wc_get_product( $simple_id );
$gone->set_stock_status( 'outofstock' );
$gone->save();
$sold = pdp( $simple_id );
ok( 'a product out of stock cannot be bought', false !== strpos( $sold, 'data-pfh-buy disabled' ) || false !== strpos( $sold, 'disabled data-pfh-buy' ) );
ok( 'and says so on the button', false !== strpos( $sold, 'Niet beschikbaar' ) );
$gone->set_stock_status( 'instock' );
$gone->save();

echo "\n── adding to the cart, whatever the chooser sent ──\n";
/*
 * WooCommerce compares the posted attribute against the chosen variation's own
 * with ===, and throws "Invalid value posted for X" on any difference. That is
 * what a shopper hit on the live site instead of getting a basket, so the
 * endpoint takes the attributes from the variation itself.
 */
$children = wc_get_product( $variable->ID )->get_children();
$first    = (int) $children[0];
$other    = (int) $children[1];
$mine     = wc_get_product( $first )->get_variation_attributes();
$theirs   = wc_get_product( $other )->get_variation_attributes();

function basket( $product_id, $variation_id, array $posted ) {
	if ( ! WC()->cart ) { wc_load_cart(); }

	WC()->cart->empty_cart();
	wc_clear_notices();

	$attributes = PFH_Widgets_Quickadd::variation_attributes( $variation_id, $posted );
	$added      = WC()->cart->add_to_cart( $product_id, 1, $variation_id, $attributes );
	$why        = '';

	foreach ( (array) wc_get_notices( 'error' ) as $notice ) {
		$why = wp_strip_all_tags( is_array( $notice ) ? $notice['notice'] : $notice );
		break;
	}

	wc_clear_notices();
	WC()->cart->empty_cart();

	return [ (bool) $added, $why ];
}

[ $ok ] = basket( $variable->ID, $first, $mine );
ok( 'the ordinary case still works', $ok );

[ $ok, $why ] = basket( $variable->ID, $first, $theirs );
ok( 'a chooser left on the previous variant still adds', $ok, $why );

[ $ok, $why ] = basket( $variable->ID, $first, array_map( 'strtoupper', $mine ) );
ok( 'a value in the wrong case still adds', $ok, $why );

[ $ok, $why ] = basket( $variable->ID, $first, [] );
ok( 'nothing posted at all still adds', $ok, $why );

$resolved = PFH_Widgets_Quickadd::variation_attributes( $first, $theirs );
ok( 'because the variation is what is asked', $resolved == $mine, wp_json_encode( $resolved ) );
ok( 'and with no variation the posted values are left alone', [ 'x' => 'y' ] === PFH_Widgets_Quickadd::variation_attributes( 0, [ 'x' => 'y' ] ) );

echo "\n── the button says what it is doing ──\n";
ok( 'it carries a label of its own', false !== strpos( $html, 'pfh-pdp__cart-label' ) );
ok( 'and something to spin while it waits', false !== strpos( $html, 'pfh-pdp__cart-spin' ) );
ok( 'with the wording for afterwards', false !== strpos( $html, 'data-pfh-added-label="Toegevoegd"' ) );
ok( 'which is a setting', false !== strpos( pdp( $variable->ID, [ 'addedLabel' => 'In je mandje' ] ), 'data-pfh-added-label="In je mandje"' ) );

echo "\n── the thumbnails are one row that scrolls ──\n";
ok( 'they sit on a track', false !== strpos( $html, 'data-pfh-thumbs-track' ) );
ok( 'with an arrow either side', 2 === substr_count( $html, 'data-pfh-thumbs-step' ) );
// Both arrows ship hidden; the script shows them only once the row overflows.
preg_match_all( '/<button[^>]*pfh-pdp__thumbs-nav[^>]*>/', $html, $navs );
$shipped_hidden = array_filter( $navs[0], static function ( $tag ) {
	return false !== strpos( $tag, ' hidden' );
} );
ok( 'both start hidden, until the row overflows', 2 === count( $navs[0] ) && 2 === count( $shipped_hidden ) );
ok( 'every thumbnail is an item on it', 3 === substr_count( $html, 'pfh-pdp__thumbs-item' ) );
ok( 'and each says which image it opens', false !== strpos( $html, 'aria-label="Show image 1"' ) );

echo "\n── the promises ──\n";
ok( 'all three are drawn', 3 === substr_count( $html, 'pfh-pdp__usp-item' ) );
ok( 'each with its icon', 3 === substr_count( $html, 'pfh-pdp__usp-icon' ) );
ok( 'and its two lines', false !== strpos( $html, 'Veilig betalen' ) && false !== strpos( $html, 'iDEAL' ) );
ok( 'they can be turned off', false === strpos( pdp( $variable->ID, [ 'showUsp' => false ] ), 'pfh-pdp__usp' ) );

echo "\n── style ──\n";
ok( 'every border reads one colour', false !== strpos( $html, '--pfh-pdp-line:#EAEAEA' ) );
$paint = pdp( $variable->ID, [ 'lineColor' => [ 'hex' => '#DDDDDD' ], 'accent' => [ 'hex' => '#123456' ] ] );
ok( 'which is a setting', false !== strpos( $paint, '--pfh-pdp-line:#DDDDDD' ) );
ok( 'as is the button and chosen variant', false !== strpos( $paint, '--pfh-pdp-accent:#123456' ) );
ok( 'the soft selection is its own colour', false !== strpos( $html, '--pfh-pdp-soft:' ) );
ok( 'and the flavour group is marked for it out of the box', false !== strpos( $html, 'pfh-pdp__attr--soft' ) );

echo "\n── it behaves like the rest of the plugin ──\n";
ok( 'rendering does not depend on Bricks building the controls', pdp( $variable->ID, [], true ) === pdp( $variable->ID ) );
ok( 'the controls encode for the builder', false !== wp_json_encode( controls() ) );
ok( 'no control default holds a 4-byte character', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', wp_json_encode( controls(), JSON_UNESCAPED_UNICODE ) ) );
ok( 'nor does the rendered page', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', $html ) );

$before = get_num_queries();
pdp( $variable->ID );
$cost = get_num_queries() - $before;
ok( 'a render stays reasonable on queries', $cost <= 40, "$cost queries" );

/* ---- put the catalogue back as it was ---- */
$product = wc_get_product( $fixture_id );

foreach ( $product->get_children() as $child ) {
	wp_delete_post( $child, true );
}

foreach ( get_attached_media( '', $fixture_id ) as $attachment ) {
	wp_delete_post( $attachment->ID, true );
}

wp_delete_post( $fixture_id, true );
wc_delete_product_transients( $fixture_id );

/*
 * The attributes, their terms and the category it filed itself under, too.
 * Leaving those behind changes the facets the archive suite counts — which is
 * how this was found.
 */
foreach ( $fixture['taxonomies'] as $taxonomy ) {
	foreach ( (array) get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] ) as $term ) {
		if ( ! is_wp_error( $term ) ) { wp_delete_term( $term->term_id, $taxonomy ); }
	}
}

foreach ( $fixture['attributes'] as $attribute_id ) {
	wc_delete_attribute( $attribute_id );
}

foreach ( $fixture['categories'] as $category_id ) {
	wp_delete_term( $category_id, 'product_cat' );
}

delete_transient( 'wc_attribute_taxonomies' );
if ( function_exists( 'wc_delete_product_transients' ) ) { wc_delete_product_transients(); }

/*
 * Asked of the database, not get_page_by_path(): that caches its lookup for
 * the rest of the request and still reports a post deleted moments ago.
 */
$left = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type IN ('product','product_variation') AND post_name LIKE 'pfh-fixture%'" );
ok( 'the catalogue is left as it was found', 0 === $left, "$left left behind" );

echo "\n$pass passed, $fail failed\n";
