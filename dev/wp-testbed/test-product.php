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
ok( 'and how many reviews', false !== strpos( $html, '396 reviews' ) );

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
ok( 'and each says which image it opens', false !== strpos( $html, 'aria-label="Toon afbeelding 1"' ) );

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

echo "\n── the corrections against Figma ──\n";
ok( 'the price and its saving read one olive', false !== strpos( $html, '--pfh-pdp-price-ink:#697c66' ) );
ok( 'the first chosen variant is the dark teal', false !== strpos( $html, '--pfh-pdp-accent:#2b5f63' ) );
ok( 'the ones under it the lighter one', false !== strpos( $html, '--pfh-pdp-soft:#7caeb2' ) );
ok( 'with white on them', false !== strpos( $html, '--pfh-pdp-soft-ink:#ffffff' ) );
ok( 'quiet labels are the drawn grey', false !== strpos( $html, '--pfh-pdp-muted:#a6a6a6' ) );
ok( 'the quantity has its own border and figure colour', false !== strpos( $html, '--pfh-pdp-qty-line:#cedacb' ) && false !== strpos( $html, '--pfh-pdp-qty-ink:#51604f' ) );

/*
 * Positional, as asked: the first group solid and everything under it the
 * second colour, without anyone naming the groups in the panel.
 */
// The data attribute is required, or the wrapper <div class="pfh-pdp__attrs">
// matches first and every index is out by one.
preg_match_all( '/<div class="pfh-pdp__attr([^"]*)" data-pfh-attr=/', $html, $groups );
ok( 'the first group is not the soft one', isset( $groups[1][0] ) && false === strpos( $groups[1][0], 'soft' ) );
ok( 'and the second one is', isset( $groups[1][1] ) && false !== strpos( $groups[1][1], 'soft' ) );
ok( 'naming a group still decides it instead', false !== strpos( pdp( $variable->ID, [ 'tintedAttrs' => 'type' ] ), 'pfh-pdp__attr--soft" data-pfh-attr="attribute_pa_soort"' ) );

echo "\n── it opens on something that can be bought ──\n";
/*
 * A chooser that opens on nothing leaves the add to cart button greyed out
 * until the shopper guesses which combination exists. So: the shop's own
 * default when that can be bought, and otherwise the first one that can.
 */
$variable_product = wc_get_product( $variable->ID );
$declared         = $variable_product->get_default_attributes();

// 1. No defaults at all.
$variable_product->set_default_attributes( [] );
$variable_product->save();

$open = pdp( $variable->ID );
preg_match_all( '/<div class="pfh-pdp__attr[^"]*" data-pfh-attr="[^"]*">.*?<\/select>/s', $open, $groups );

$every_group_chose = ! empty( $groups[0] );

foreach ( $groups[0] as $group ) {
	if ( ! preg_match( '/<option value="[^"]+" selected/', $group ) ) { $every_group_chose = false; }
}

ok( 'with no default set, every group still opens on a value', $every_group_chose, count( $groups[0] ) . ' groups' );
ok( '  and the form carries a real variation', (bool) preg_match( '/name="variation_id" value="([1-9]\d*)"/', $open ) );
ok( '  so the button is not left disabled', false === strpos( $open, 'data-pfh-buy disabled' ) );

preg_match( '/name="variation_id" value="(\d+)"/', $open, $picked );
$opened_on = wc_get_product( (int) $picked[1] );
ok( '  on one that is actually in stock', $opened_on && $opened_on->is_purchasable() && $opened_on->is_in_stock() );

// 2. A default that cannot be bought.
$dead = null;

foreach ( $variable_product->get_children() as $child ) {
	$candidate = wc_get_product( $child );

	if ( $candidate && ! $candidate->is_in_stock() ) { $dead = $candidate; break; }
}

if ( $dead ) {
	$variable_product->set_default_attributes( array_combine(
		array_map( static function ( $k ) { return preg_replace( '/^attribute_/', '', $k ); }, array_keys( $dead->get_variation_attributes() ) ),
		array_values( $dead->get_variation_attributes() )
	) );
	$variable_product->save();

	$open = pdp( $variable->ID );
	preg_match( '/name="variation_id" value="(\d+)"/', $open, $picked );

	ok( 'an out-of-stock default is stepped over', (int) $picked[1] !== (int) $dead->get_id(), 'opened on the sold-out one' );
	ok( '  for one that can be bought', (int) $picked[1] > 0 && wc_get_product( (int) $picked[1] )->is_in_stock() );
} else {
	ok( 'an out-of-stock default is stepped over', true, 'no sold-out variation in the fixture' );
	ok( '  for one that can be bought', true );
}

// 3. Switched off, only what the shop declared is chosen.
$variable_product->set_default_attributes( [] );
$variable_product->save();

$manual = pdp( $variable->ID, [ 'preselect' => false ] );
ok( 'switched off it waits for the shopper', false === strpos( $manual, 'value="' . ( $picked[1] ?? '0' ) . '" data-pfh-variation' ) && (bool) preg_match( '/name="variation_id" value="0"/', $manual ) );

// 4. The price agrees with what the chooser opened on.
$open = pdp( $variable->ID );
preg_match( '/name="variation_id" value="(\d+)"/', $open, $picked );
$opened_on = wc_get_product( (int) $picked[1] );
/*
 * wc_price() nests spans, so the price row is read as a whole rather than by
 * matching up to the first closing tag — which is the currency symbol.
 */
preg_match( '/<div class="pfh-pdp__price" data-pfh-price>(.*?)<span class="pfh-pdp__price-was/s', $open, $shown );
$want = wp_strip_all_tags( wc_price( wc_get_price_to_display( $opened_on ) ) );
$got  = wp_strip_all_tags( $shown[1] ?? '' );

ok( 'the price shown is the price of what it opened on', false !== strpos( $got, $want ), "shown '$got', wanted '$want'" );

$variable_product->set_default_attributes( $declared );
$variable_product->save();

echo "\n── the line above what is in the box ──\n";
/*
 * The product's own Highlight title, set where the rest of the product is, so
 * it can differ per product. It used to repeat the variant chooser and follow
 * it; that was replaced on request with something the client controls.
 */
update_post_meta( $variable->ID, PFH_Widgets_Product_Fields::HIGHLIGHT_TITLE, 'Smaak - Mandarijn' );
ok( 'it says what the product says', false !== strpos( pdp( $variable->ID ), '>Smaak - Mandarijn</p>' ) );
ok( 'and it is fixed, not following the chooser', false === strpos( pdp( $variable->ID ), 'data-pfh-box-label' ) );

delete_post_meta( $variable->ID, PFH_Widgets_Product_Fields::HIGHLIGHT_TITLE );
ok( 'a product without one falls back to the element', false !== strpos( pdp( $variable->ID, [ 'highlightsLabel' => 'WAT ZIT ERIN' ] ), '>WAT ZIT ERIN</p>' ) );

echo "\n── the quantity reads minus, figure, plus ──\n";
$minus = strpos( $html, 'data-pfh-qty="-1"' );
$field = strpos( $html, 'data-pfh-qty-field' );
$plus  = strpos( $html, 'data-pfh-qty="1"' );
ok( 'in that order', $minus < $field && $field < $plus, "$minus, $field, $plus" );
ok( 'all three inside one box', false !== strpos( $html, '<div class="pfh-pdp__qty">' ) && $plus < strpos( $html, '</div><button type="submit"' ) + 1 );

echo "\n── it behaves like the rest of the plugin ──\n";
ok( 'rendering does not depend on Bricks building the controls', pdp( $variable->ID, [], true ) === pdp( $variable->ID ) );
ok( 'the controls encode for the builder', false !== wp_json_encode( controls() ) );
ok( 'no control default holds a 4-byte character', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', wp_json_encode( controls(), JSON_UNESCAPED_UNICODE ) ) );
ok( 'nor does the rendered page', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', $html ) );

$before = get_num_queries();
pdp( $variable->ID );
$cost = get_num_queries() - $before;
ok( 'a render stays reasonable on queries', $cost <= 40, "$cost queries" );

echo "\n── an emoji in a value's name ──\n";

// "🍑 Perzik", typed into the value's name the way the shop does it.
$peach   = get_term_by( 'name', 'Perzik', 'pa_smaak' );
$slug    = $peach ? $peach->slug : '';
$renamed = $peach ? wp_update_term( $peach->term_id, 'pa_smaak', [ 'name' => "\u{1F351} Perzik" ] ) : new WP_Error( 'none' );
ok( 'renaming a value to lead with an emoji saves', ! is_wp_error( $renamed ) );
ok( '  and keeps its slug, which the variations match on', $peach && get_term( $peach->term_id, 'pa_smaak' )->slug === $slug );
wc_delete_product_transients( $variable->ID );

$html = pdp( $variable->ID );
ok( 'the button draws the emoji apart from the words', (bool) preg_match( '#data-pfh-pill="' . preg_quote( $slug, '#' ) . '"[^>]*><span class="pfh-pdp__pill-emoji" aria-hidden="true">\x{1F351}</span><span class="pfh-pdp__pill-text" data-pfh-pill-text>Perzik</span>#u', $html ) );
ok( '  and is marked as having one', (bool) preg_match( '#class="pfh-pdp__pill[^"]*has-emoji[^"]*" data-pfh-pill="' . preg_quote( $slug, '#' ) . '"#', $html ) );
ok( 'a value without one is drawn as before', false !== strpos( $html, '<span class="pfh-pdp__pill-text" data-pfh-pill-text>Kers</span></button>' ) && false === strpos( $html, 'has-emoji" data-pfh-pill="kers"' ) );
ok( 'the dropdown behind the buttons keeps the whole name', false !== strpos( $html, ">\u{1F351} Perzik</option>" ) );

$js = file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/assets/js/pfh-product.js' );
ok( 'the heading repeats the words only ("Smaak — Perzik")', false !== strpos( $js, "pill.querySelector( '[data-pfh-pill-text]' )" ) );

if ( $peach ) {
	wp_update_term( $peach->term_id, 'pa_smaak', [ 'name' => 'Perzik' ] );
}

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
