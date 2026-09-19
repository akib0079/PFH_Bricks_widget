<?php
/**
 * The three sections under the product, and the line above the highlights.
 *
 * All four read the product first and fall back second, and every one of them
 * is left off the page entirely when there is nothing to show — which is the
 * rule that keeps a thin product from looking broken.
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

$bare_id = (int) ( get_posts( [ 'post_type' => 'product', 'numberposts' => 1, 'fields' => 'ids', 'exclude' => [ $fixture_id ] ] )[0] ?? 0 );

function draw( $class, $name, $product_id, array $settings = [] ) {
	$el       = new $class( [ 'id' => 'sec' ] );
	$el->name = $name;
	$el->settings = array_merge( [ 'previewId' => (string) $product_id ], $settings );

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

function related( $product_id, array $settings = [] ) {
	return draw( 'PFH_Element_Product_Related', 'pfh-product-related', $product_id, $settings );
}

function usp( $product_id, array $settings = [] ) {
	return draw( 'PFH_Element_Product_Usp', 'pfh-product-usp', $product_id, $settings );
}

function faq( $product_id, array $settings = [] ) {
	$el       = new PFH_Element_Faq( [ 'id' => 'faq' ] );
	$el->name = 'pfh-faq';
	$el->settings = array_merge( [ 'productId' => (string) $product_id ], $settings );

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

echo "── the line above the highlights is the product's own ──\n";
$pdp = draw( 'PFH_Element_Product', 'pfh-product', $fixture_id );
ok( 'it says what the product says', false !== strpos( $pdp, '>Smaak &mdash; Mandarijn</p>' ) || false !== strpos( $pdp, ">Smaak \u{2014} Mandarijn</p>" ) );
ok( 'and no longer follows the chooser', false === strpos( $pdp, 'data-pfh-box-label' ) );

update_post_meta( $fixture_id, $F::HIGHLIGHT_TITLE, '' );
ok( 'a product without one falls back to the element', false !== strpos( draw( 'PFH_Element_Product', 'pfh-product', $fixture_id, [ 'highlightsLabel' => 'WAT ZIT ERIN' ] ), '>WAT ZIT ERIN</p>' ) );
// Scoped to the box: the variant groups use the same label class, so looking
// at the whole page would find theirs and never fail.
preg_match( '/<div class="pfh-pdp__box">.*?<\/div>/s', draw( 'PFH_Element_Product', 'pfh-product', $fixture_id ), $box );
ok( 'and with neither there is no line at all', isset( $box[0] ) && false === strpos( $box[0], 'pfh-pdp__attr-label' ) );
update_post_meta( $fixture_id, $F::HIGHLIGHT_TITLE, 'Smaak — Mandarijn' );

echo "\n── Gerelateerde Producten ──\n";
$fallback = related( $fixture_id );
ok( 'with nothing chosen it shows the category', false !== strpos( $fallback, 'pfh-prod__card' ) );
ok( 'never the product being looked at', false === strpos( $fallback, 'PFH...Fixture Starterspakket</' ) );
ok( 'as the same slider as everywhere else', false !== strpos( $fallback, 'pfh-prod' ) && false !== strpos( $fallback, 'pfh-prod__track' ) );
ok( 'under the drawn heading', false !== strpos( $fallback, 'Gerelateerde' ) && false !== strpos( $fallback, '<em>Producten</em>' ) );

echo "\n── the band behind it ──\n";
ok( 'it sits on the artwork the design gives it', false !== strpos( $fallback, 'Group-1000001553.webp' ) );
ok( '  as a real background, not a colour that wipes it out', false !== strpos( $fallback, '--pfh-p-image:url(' ) );
ok( 'a picture chosen in Bricks wins', false !== strpos( related( $fixture_id, [ 'bgImage' => [ 'url' => 'https://example.com/chosen.jpg' ] ] ), 'chosen.jpg' ) );
ok( 'a URL typed in wins over the default', false !== strpos( related( $fixture_id, [ 'bgUrl' => 'https://example.com/typed.jpg' ] ), 'typed.jpg' ) );
ok( 'and clearing it leaves the section plain', false !== strpos( related( $fixture_id, [ 'bgUrl' => '' ] ), '--pfh-p-image:none' ) );
ok( 'the fit is the client\'s', false !== strpos( related( $fixture_id, [ 'bgSize' => 'contain' ] ), '--pfh-p-bg-size:contain' ) );
ok( '  and so is the position', false !== strpos( related( $fixture_id, [ 'bgPosition' => 'center bottom' ] ), '--pfh-p-bg-pos:center bottom' ) );

/*
 * The shop's own sliders are unchanged: they gained the controls but not a
 * picture, so no existing page suddenly grows a background.
 */
$plain = ( function () {
	$el = new PFH_Element_Products( [ 'id' => 'pl' ] );
	$el->name = 'pfh-products';
	$el->settings = [];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
} )();
ok( 'the shop\'s own slider still has none', false !== strpos( $plain, '--pfh-p-image:none' ) );

// The colour and the picture are separate longhands; `background:` would drop
// the picture every time the colour was set.
$css = (string) file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/assets/css/pfh-products.css' );
ok( 'the stylesheet paints both, not one over the other', false !== strpos( $css, 'background-image: var(--pfh-p-image)' ) && false === strpos( $css, 'background: var(--pfh-p-bg);' ) );

$chosen = array_slice( get_posts( [ 'post_type' => 'product', 'numberposts' => 2, 'fields' => 'ids', 'exclude' => [ $fixture_id ] ] ), 0, 2 );
update_post_meta( $fixture_id, $F::RELATED, $chosen );
$picked = related( $fixture_id );
ok( 'what the editor picked wins', 2 === substr_count( $picked, 'pfh-prod__card' ), substr_count( $picked, 'pfh-prod__card' ) . ' cards' );

foreach ( $chosen as $id ) {
	ok( '  ' . get_the_title( $id ), false !== strpos( $picked, esc_html( get_the_title( $id ) ) ) );
}

delete_post_meta( $fixture_id, $F::RELATED );

/*
 * Neither chosen nor a category to draw on: the section is left off rather
 * than filled with whatever the shop happens to sell.
 */
$orphan_id = wp_insert_post( [ 'post_type' => 'product', 'post_title' => 'PFH orphan probe', 'post_status' => 'publish' ] );
ok( 'a product with no category and no picks shows nothing', '' === trim( related( $orphan_id ) ) );
wp_delete_post( $orphan_id, true );

echo "\n── why this product ──\n";
$band = usp( $fixture_id );
ok( 'a card for every reason', 4 === substr_count( $band, 'pfh-usp__card"' ) );
ok( 'the eyebrow comes from the product', false !== strpos( $band, 'WAAROM GIA...GIAMAS' ) );
ok( 'and so does the title', false !== strpos( $band, 'Puur natuur' ) );
ok( 'with one word in the italic serif', false !== strpos( $band, '<em>ongeevenaard</em>' ) );
ok( 'each card has its title and text', false !== strpos( $band, 'Authentiek Grieks' ) && false !== strpos( $band, 'eeuwenoude tradities' ) );

foreach ( [ '#f9e9cf', '#dfe9dc', '#e6eff4', '#fde1d5' ] as $i => $hex ) {
	ok( '  card ' . ( $i + 1 ) . ' takes ' . $hex, false !== strpos( $band, '--pfh-usp-card:' . $hex ) );
}

$rows = $F::rows( $fixture_id, $F::USP );
$rows[0]['color'] = '#123456';
update_post_meta( $fixture_id, $F::USP, $rows );
ok( 'a colour set on a card wins over the run', false !== strpos( usp( $fixture_id ), '--pfh-usp-card:#123456' ) );
$rows[0]['color'] = '';
update_post_meta( $fixture_id, $F::USP, $rows );

ok( 'markup other than emphasis is stripped from the title', false === strpos( usp( $fixture_id, [ 'title' => '<script>x</script>' ] ), '<script>' ) );

/*
 * The fallback is for products nobody has filled in yet, and with neither the
 * band is not drawn.
 */
$spare = usp( $bare_id, [ 'cards' => [ [ 'label' => 'Van het element', 'text' => 'Valt hierop terug.' ] ] ] );
ok( 'a product with none falls back to the element', false !== strpos( $spare, 'Van het element' ) );
ok( 'and with neither there is no band at all', '' === trim( usp( $bare_id ) ) );

echo "\n── the questions ──\n";
$own = faq( $fixture_id, [ 'fromProduct' => true ] );
ok( 'the product\'s own are used', false !== strpos( $own, 'Hoeveel glazen haal ik uit dit pakket?' ) );
ok( 'and the element\'s own are not', false === strpos( $own, 'Is Gia giamas gemaakt van echt fruit?' ) );
ok( 'all of them', 3 === substr_count( $own, 'pfh-faq__item' ) );
ok( 'and it is the same accordion as the shop page', false !== strpos( $own, 'pfh-faq__q' ) );

$element = faq( $bare_id, [ 'fromProduct' => true ] );
ok( 'a product with none falls back to the element\'s own', false !== strpos( $element, 'Is Gia giamas gemaakt van echt fruit?' ) );
ok( 'which is what the shop page keeps doing', false !== strpos( faq( $fixture_id ), 'Is Gia giamas gemaakt van echt fruit?' ) );

echo "\n── they behave like the rest of the plugin ──\n";
foreach ( [ 'PFH_Element_Product_Related' => 'pfh-product-related', 'PFH_Element_Product_Usp' => 'pfh-product-usp' ] as $class => $name ) {
	$el = new $class( [ 'id' => 'c' ] );
	$el->name = $name;
	$el->set_control_groups();
	$el->set_controls();

	$short = str_replace( 'PFH_Element_', '', $class );
	ok( "$short encodes its controls for the builder", false !== wp_json_encode( $el->controls ) );
	ok( "  and holds no 4-byte character", ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', wp_json_encode( $el->controls, JSON_UNESCAPED_UNICODE ) ) );
}

ok( 'the band renders the same without the controls built', usp( $fixture_id ) === ( function () use ( $fixture_id ) {
	$el = new PFH_Element_Product_Usp( [ 'id' => 'sec' ] );
	$el->name = 'pfh-product-usp';
	$el->set_control_groups();
	$el->set_controls();
	$el->settings = [ 'previewId' => (string) $fixture_id ];
	ob_start();
	$el->render();

	return (string) ob_get_clean();
} )() );

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

$left = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type IN ('product','product_variation') AND (post_name LIKE 'pfh-fixture%' OR post_name LIKE 'pfh-orphan%')" );
ok( 'the catalogue is left as it was found', 0 === $left, "$left left behind" );

echo "\n$pass passed, $fail failed\n";
