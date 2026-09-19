<?php
/**
 * The bottom add-to-cart reminder.
 *
 * Two things are worth more than the markup checks here. One: the card prints
 * the same hooks the single product script reads, and both sides are asserted,
 * so the day one of them is renamed this says so instead of the button quietly
 * doing nothing. Two: what the card posts really does reach the basket.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$fixture    = require __DIR__ . '/fixture-variable-product.php';
$fixture_id = (int) $fixture['id'];
$F          = 'PFH_Widgets_Product_Fields';

if ( ! $fixture_id ) {
	echo "The fixture could not be built.\n";
	exit;
}

function bar( $product_id, array $settings = [] ) {
	$el       = new PFH_Element_Bottomcart( [ 'id' => 'bc' ] );
	$el->name = 'pfh-bottomcart';
	$el->settings = array_merge( [ 'productId' => (string) $product_id ], $settings );

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

$html = bar( $fixture_id );

echo "── the reminder ──\n";
ok( 'it is drawn', false !== strpos( $html, 'class="pfh-bcart' ) );
ok( 'the category is above the name', false !== strpos( $html, 'pfh-bcart__eyebrow' ) && false !== strpos( $html, 'Traditionele' ) );
ok( 'the product name is the product', false !== strpos( $html, 'PFH...Fixture Starterspakket' ) );
ok( '  and links to it', (bool) preg_match( '/<h2 class="pfh-bcart__title"><a href="[^"]+">/', $html ) );
ok( 'the category can be switched off', false === strpos( bar( $fixture_id, [ 'showCategory' => false ] ), 'pfh-bcart__eyebrow' ) );

echo "\n── the price ──\n";
ok( 'what it costs now', false !== strpos( $html, 'pfh-bcart__now' ) );
ok( 'what it cost, struck through', false !== strpos( $html, 'pfh-bcart__was' ) && false === strpos( $html, 'pfh-bcart__was" data-pfh-price-was hidden' ) );
ok( 'and what that saves, as an amount', (bool) preg_match( '/pfh-bcart__save"[^>]*>Bespaar[^<]*\d/', $html ), 'no amount found' );
ok( '  not a percentage', ! preg_match( '/pfh-bcart__save"[^>]*>[^<]*%/', $html ) );
ok( 'the wording is the client\'s', false !== strpos( bar( $fixture_id, [ 'savingLabel' => 'Jij bespaart' ] ), 'Jij bespaart' ) );
ok( 'the saving can be switched off', false !== strpos( bar( $fixture_id, [ 'showSaving' => false ] ), 'pfh-bcart__save" data-pfh-price-save hidden' ) );

echo "\n── choosing ──\n";
ok( 'a dropdown for each attribute', 2 === substr_count( $html, 'pfh-bcart__select' ) );
ok( 'each is a real select that posts', 2 === substr_count( $html, 'data-pfh-attr-field' ) );
ok( 'the first says what it is for', false !== strpos( $html, '>Select Type</option>' ), 'no "Select Type"' );
ok( 'and the second likewise', false !== strpos( $html, '>Select Smaak</option>' ) );
ok( 'the wording is the client\'s', false !== strpos( bar( $fixture_id, [ 'choosePrefix' => 'Kies' ] ), '>Kies Type</option>' ) );
ok( 'left empty it is the attribute alone', false !== strpos( bar( $fixture_id, [ 'choosePrefix' => '' ] ), '>Type</option>' ) );
ok( 'the options read as the shop writes them', false !== strpos( $html, '>Traditioneel 1000ml</option>' ) && false !== strpos( $html, '>Mandarijn</option>' ) );
ok( 'each dropdown is labelled for a screen reader', 2 === substr_count( $html, 'pfh-bcart__choice-label' ) );
ok( 'and carries the chevron from the design', false !== strpos( $html, 'pfh-bcart__chev' ) );

echo "\n── buying ──\n";
ok( 'there is one button', 1 === substr_count( $html, 'pfh-bcart__cart"' ) );
ok( 'it says what it does', false !== strpos( $html, 'Voeg toe aan winkelmand' ) );
ok( 'it has a loader', false !== strpos( $html, 'pfh-bcart__cart-spin' ) );
ok( 'and a label of its own, so the loader can sit beside it', false !== strpos( $html, 'data-pfh-cart-label' ) );
ok( 'it adds without a reload', false !== strpos( $html, 'data-pfh-ajax' ) );
ok( '  and says so afterwards', false !== strpos( $html, 'data-pfh-added-label="Toegevoegd"' ) );
ok( 'which can be switched off', false === strpos( bar( $fixture_id, [ 'ajaxCart' => false ] ), 'data-pfh-ajax' ) );
ok( 'the form still posts the ordinary way', false !== strpos( $html, 'name="add-to-cart"' ) && false !== strpos( $html, 'name="quantity"' ) );
ok( 'every variation is printed for the chooser', false !== strpos( $html, 'data-variations=' ) );
ok( 'a failure has somewhere to be said', false !== strpos( $html, 'data-pfh-notice' ) );

echo "\n── the card and the script agree ──\n";
$js = (string) file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/assets/js/pfh-product.js' );

ok( 'the product script looks for this section', false !== strpos( $js, '.pfh-bcart' ) );
ok( 'and there is no second script to drift from it', ! file_exists( WP_PLUGIN_DIR . '/pfh-bricks-widgets/assets/js/pfh-bottomcart.js' ) );

foreach ( [
	'data-pfh-form',
	'data-pfh-attr=',
	'data-pfh-attr-field',
	'data-pfh-variation',
	'data-pfh-buy',
	'data-pfh-price-now',
	'data-pfh-price-was',
	'data-pfh-price-save',
	'data-pfh-notice',
] as $hook ) {
	$bare = rtrim( $hook, '=' );

	ok( "  $bare is on the card and in the script", false !== strpos( $html, $hook ) && false !== strpos( $js, $bare ) );
}

/*
 * The script reads the three price nodes off the form's parent, so they have
 * to be inside the same box as the form rather than beside it.
 */
preg_match( '/<div class="pfh-bcart__body">(.*)<\/form>/s', $html, $body );
ok( 'the prices sit where the script looks for them', isset( $body[1] ) && false !== strpos( $body[1], 'data-pfh-price-now' ) );

echo "\n── the picture ──\n";
ok( 'a product with no picture of its own uses its product image', false !== strpos( $html, 'pfh-bcart__shot' ) );

$own = wp_insert_attachment(
	[ 'post_title' => 'PFH bottom probe', 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit' ],
	get_temp_dir() . 'pfh-bottom-probe.jpg',
	$fixture_id
);
update_post_meta( $own, '_wp_attached_file', 'pfh-bottom-probe.jpg' );
update_post_meta( $fixture_id, $F::BOTTOM_IMAGE, $own );

ok( 'the product\'s own choice wins', false !== strpos( bar( $fixture_id ), 'pfh-bottom-probe' ) );

update_post_meta( $fixture_id, $F::BOTTOM_IMAGE, 0 );
ok( 'and clearing it goes back to the product image', false === strpos( bar( $fixture_id ), 'pfh-bottom-probe' ) );
ok( '  which is still drawn', false !== strpos( bar( $fixture_id ), 'pfh-bcart__shot' ) );
wp_delete_attachment( $own, true );

ok(
	'a product with no picture at all leaves the picture out',
	false === strpos( bar( $fixture_id, [ 'previewOnly' => 1 ] ), 'pfh-bcart__shot src=""' )
);

echo "\n── the artwork and the overlap ──\n";
ok( 'the design\'s background is used unless one is chosen', false !== strpos( $html, 'Frame-470150-1-scaled.jpg' ) );
ok( '  and a chosen one wins', false !== strpos( bar( $fixture_id, [ 'background' => [ 'url' => 'https://example.com/own.jpg' ] ] ), 'own.jpg' ) );
ok( 'the next section is pulled up', false !== strpos( $html, '--pfh-bc-overlap-set:90px' ) );
ok( '  by however much is asked for', false !== strpos( bar( $fixture_id, [ 'overlap' => 140 ] ), '--pfh-bc-overlap-set:140px' ) );
ok( 'the card keeps the drawn corner', false !== strpos( $html, '--pfh-bc-radius:17px' ) );
ok( 'and the drawn colours', false !== strpos( $html, '--pfh-bc-button:#377a7f' ) && false !== strpos( $html, '--pfh-bc-price-ink:#3c868c' ) );

echo "\n── what it posts reaches the basket ──\n";
/*
 * The same check the product page gets: WooCommerce compares the posted
 * attribute with the variation's own using ===, so the endpoint resolves them
 * from the variation rather than trusting what the form sent.
 */
$children = wc_get_product( $fixture_id )->get_children();
$first    = (int) $children[0];
$mine     = wc_get_product( $first )->get_variation_attributes();

if ( ! WC()->cart ) { wc_load_cart(); }

WC()->cart->empty_cart();
wc_clear_notices();

$attributes = PFH_Widgets_Quickadd::variation_attributes( $first, $mine );
$added      = WC()->cart->add_to_cart( $fixture_id, 1, $first, $attributes );
$why        = '';

foreach ( (array) wc_get_notices( 'error' ) as $notice ) {
	$why = wp_strip_all_tags( is_array( $notice ) ? $notice['notice'] : $notice );
	break;
}

ok( 'the chosen variation goes in', (bool) $added, $why );

wc_clear_notices();
WC()->cart->empty_cart();

// The keys the card's selects post under have to be the ones the variation
// answers to, or the above passes while the real form fails.
preg_match_all( '/<select class="pfh-bcart__select"[^>]*name="([^"]+)"/', $html, $names );
ok( 'the dropdowns post the keys the variation knows', isset( $names[1] ) && ! array_diff( $names[1], array_keys( $mine ) ), implode( ',', $names[1] ?? [] ) . ' vs ' . implode( ',', array_keys( $mine ) ) );

echo "\n── which product it shows ──\n";
$other = (int) ( get_posts( [ 'post_type' => 'product', 'numberposts' => 1, 'fields' => 'ids', 'exclude' => [ $fixture_id ] ] )[0] ?? 0 );
ok( 'a named product is shown', false !== strpos( bar( $other ), esc_html( get_the_title( $other ) ) ) );
ok( 'with none named it falls back to the most recent', '' !== trim( bar( 0 ) ) );

echo "\n── it behaves like the rest of the plugin ──\n";
$el = new PFH_Element_Bottomcart( [ 'id' => 'c' ] );
$el->name = 'pfh-bottomcart';
$el->set_control_groups();
$el->set_controls();

ok( 'it encodes its controls for the builder', false !== wp_json_encode( $el->controls ) );
ok( 'and holds no 4-byte character', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', wp_json_encode( $el->controls, JSON_UNESCAPED_UNICODE ) ) );

$missing = [];

foreach ( $el->controls as $key => $control ) {
	if ( ! isset( $control['tab'] ) ) { $missing[] = $key; }
}

ok( 'every control declares its tab', ! $missing, implode( ', ', $missing ) );

$panel = ( function () use ( $fixture_id ) {
	$el = new PFH_Element_Bottomcart( [ 'id' => 'bc' ] );
	$el->name = 'pfh-bottomcart';
	$el->set_control_groups();
	$el->set_controls();
	$el->settings = [ 'productId' => (string) $fixture_id ];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
} )();

ok( 'it renders the same without the controls built', $panel === bar( $fixture_id ) );

/* ---- put the catalogue back ---- */
$product = wc_get_product( $fixture_id );

foreach ( $product->get_children() as $child ) {
	wp_delete_post( $child, true );
}

foreach ( get_attached_media( '', $fixture_id ) as $attachment ) {
	wp_delete_post( $attachment->ID, true );
}

wp_delete_post( $fixture_id, true );

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
wc_delete_product_transients();

$left = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type IN ('product','product_variation') AND post_name LIKE 'pfh-fixture%'" );
ok( 'the catalogue is left as it was found', 0 === $left, "$left left behind" );

echo "\n$pass passed, $fail failed\n";
