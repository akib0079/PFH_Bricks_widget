<?php
/**
 * The four product tabs, and the steps panel beside them.
 *
 * The rule the whole element follows: a field the client has not filled in
 * leaves nothing on the page. No empty heading, no empty box, and no tab that
 * opens onto nothing — except the description, which says so in Dutch.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-product-tabs.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$fixture    = require __DIR__ . '/fixture-variable-product.php';
$fixture_id = (int) $fixture['id'];

if ( ! $fixture_id ) {
	echo "The fixture could not be built.\n";
	exit;
}

// A product with none of the fields filled in, to prove what stays off.
$bare_id = (int) ( get_posts( [ 'post_type' => 'product', 'numberposts' => 1, 'fields' => 'ids', 'exclude' => [ $fixture_id ] ] )[0] ?? 0 );

function tabs( $product_id, array $settings = [], $build = false ) {
	$el       = new PFH_Element_Product_Tabs( [ 'id' => 'tabs' ] );
	$el->name = 'pfh-product-tabs';

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
	$el       = new PFH_Element_Product_Tabs( [ 'id' => 'c' ] );
	$el->name = 'pfh-product-tabs';
	$el->set_control_groups();
	$el->set_controls();

	return $el->controls;
}

$html = tabs( $fixture_id );
$F    = 'PFH_Widgets_Product_Fields';

echo "── four tabs, and only one open ──\n";
ok( 'every tab is drawn', 4 === substr_count( $html, 'data-pfh-tab="' ) );
foreach ( [ 'Omschrijving', 'Ingrediënten', 'Houdbaarheid', 'Voedingswaarden' ] as $label ) {
	ok( "  $label", false !== strpos( $html, '>' . $label . '</span>' ) );
}
ok( 'a panel for each', 4 === substr_count( $html, 'data-pfh-panel="' ) );
ok( 'exactly one is selected', 1 === substr_count( $html, 'aria-selected="true"' ) );
ok( 'and the other three start hidden', 3 === substr_count( $html, 'hidden>' ) );
ok( 'the labels can be renamed', false !== strpos( tabs( $fixture_id, [ 'descLabel' => 'Over dit product' ] ), '>Over dit product</span>' ) );

echo "\n── it is a real tablist, not four links ──\n";
ok( 'the strip says what it is', false !== strpos( $html, 'role="tablist"' ) );
ok( 'each button is a tab', 4 === substr_count( $html, 'role="tab"' ) );
ok( 'each panel is a tabpanel', 4 === substr_count( $html, 'role="tabpanel"' ) );
ok( 'tabs point at their panel', 4 === substr_count( $html, 'aria-controls="' ) );
ok( 'panels point back', 4 === substr_count( $html, 'aria-labelledby="' ) );
ok( 'only the open tab is in the tab order', 1 === substr_count( $html, 'tabindex="0"' ) && 3 === substr_count( $html, 'tabindex="-1"' ) );

echo "\n── Omschrijving ──\n";
ok( 'the product description is the panel', false !== strpos( $html, 'Een frisse smaak van Griekenland' ) );
ok( 'its own heading keeps its place', false !== strpos( $html, '<h2>Een frisse smaak van Griekenland</h2>' ) );
ok( 'bullet points are drawn as ticks', false !== strpos( $html, 'pfh-tabs__rich--ticks' ) );
/**
 * Just the description panel: the ingredients are ticked by design, so looking
 * at the whole page would find those and never fail.
 */
function desc_panel( $html ) {
	preg_match( '/data-pfh-panel="desc".*?(?=<div class="pfh-tabs__panel|$)/s', $html, $m );

	return isset( $m[0] ) ? $m[0] : '';
}

ok( 'which is a setting', false === strpos( desc_panel( tabs( $fixture_id, [ 'descTicks' => false ] ) ), 'pfh-tabs__rich--ticks' ) );
ok( 'and on by default', false !== strpos( desc_panel( $html ), 'pfh-tabs__rich--ticks' ) );

/*
 * The one tab that always shows. A product with no description still has a
 * Omschrijving tab, saying so — an empty panel would look broken.
 */
$blank_id = wp_insert_post( [ 'post_type' => 'product', 'post_title' => 'PFH blank probe', 'post_status' => 'publish', 'post_content' => '' ] );
$blank    = tabs( $blank_id );

ok( 'a product with no description still has the tab', false !== strpos( $blank, 'data-pfh-tab="desc"' ) );
ok( 'and says so in Dutch', false !== strpos( $blank, 'Geen informatie beschikbaar.' ) );
ok( 'in wording that can be changed', false !== strpos( tabs( $blank_id, [ 'descEmpty' => 'Nog niets ingevuld' ] ), 'Nog niets ingevuld' ) );

wp_delete_post( $blank_id, true );

$bare = tabs( $bare_id );

echo "\n── Ingredienten ──\n";
ok( 'the heading', false !== strpos( $html, '>Ingrediënten</h2>' ) );
ok( 'the ingredients themselves', false !== strpos( $html, 'Vruchtensap uit concentraat' ) );
ok( 'the allergens get their own box', false !== strpos( $html, 'pfh-tabs__note' ) && false !== strpos( $html, '14 grote allergenen' ) );
ok( 'under their own heading', false !== strpos( $html, '>Allergenen</h3>' ) );
ok( 'every claim is a ticked pill', 6 === substr_count( $html, 'pfh-tabs__claim"' ) );
ok( 'with a tick each', 6 === substr_count( $html, 'pfh-tabs__tick' ) );
ok( 'under the Zonder heading', false !== strpos( $html, '>Zonder</h3>' ) );
ok( 'the sub-headings are settings', false !== strpos( tabs( $fixture_id, [ 'allergenTitle' => 'Allergieinfo' ] ), '>Allergieinfo</h3>' ) );

echo "\n── Houdbaarheid ──\n";
ok( 'the heading comes from the product', false !== strpos( $html, '>Bewaring &amp; houdbaarheid</h2>' ) );
ok( 'a card for every row', 4 === substr_count( $html, 'pfh-tabs__card"' ) );
ok( 'each with its heading and text', false !== strpos( $html, '>Na openen</h4>' ) && false !== strpos( $html, '30 dagen houdbaar' ) );
ok( 'and the supplied icon when none was chosen', 4 === substr_count( $html, 'pfh-tabs__card-icon' ) );

$icon = wp_insert_attachment( [ 'post_title' => 'Own icon', 'post_mime_type' => 'image/png', 'post_status' => 'inherit' ], 'probe/own-icon.png' );
update_post_meta( $icon, '_wp_attached_file', 'probe/own-icon.png' );
$rows = $F::rows( $fixture_id, $F::STORAGE );
$rows[0]['icon'] = (string) $icon;
update_post_meta( $fixture_id, $F::STORAGE, $rows );
ok( 'a chosen icon is used instead', false !== strpos( tabs( $fixture_id ), 'probe/own-icon.png' ) );
$rows[0]['icon'] = '';
update_post_meta( $fixture_id, $F::STORAGE, $rows );
wp_delete_attachment( $icon, true );

echo "\n── Voedingswaarden ──\n";
ok( 'the heading', false !== strpos( $html, '>Voedingswaarden</h2>' ) );
ok( 'the line under it', false !== strpos( $html, 'Per 100ml bereide drank' ) );
ok( 'three column headings', 3 === substr_count( $html, '</th>' ) );
ok( 'named as the element says', false !== strpos( $html, '>Voedingsstof</th>' ) && false !== strpos( $html, '>Per portie (200ml)</th>' ) );
ok( 'a row for every line', 5 === substr_count( $html, '</tr>' ) - 1 );
ok( 'figures sit to the right', false !== strpos( $html, 'class="is-figure"' ) );
ok( 'alternate rows are shaded', false !== strpos( $html, 'is-striped' ) );
ok( 'which is a setting', false === strpos( tabs( $fixture_id, [ 'stripe' => false ] ), 'is-striped' ) );
ok( 'and the table scrolls rather than the page', false !== strpos( $html, 'pfh-tabs__scroll' ) );

update_post_meta( $fixture_id, $F::NUTRITION_COLS, [ 'Stof', 'Per glas', '' ] );
$renamed = tabs( $fixture_id );
ok( 'a product can rename a column', false !== strpos( $renamed, '>Stof</th>' ) && false !== strpos( $renamed, '>Per glas</th>' ) );
ok( 'and the ones it leaves alone keep the element wording', false !== strpos( $renamed, '>Per portie (200ml)</th>' ) );
delete_post_meta( $fixture_id, $F::NUTRITION_COLS );

echo "\n── a tab with nothing in it is not drawn ──\n";
ok( 'no ingredients, no Ingredienten tab', false === strpos( $bare, 'data-pfh-tab="ingr"' ) );
ok( 'no storage rows, no Houdbaarheid tab', false === strpos( $bare, 'data-pfh-tab="storage"' ) );
ok( 'no nutrition rows, no Voedingswaarden tab', false === strpos( $bare, 'data-pfh-tab="nutrition"' ) );
ok( 'so a bare product still has one working tab', 1 === substr_count( $bare, 'data-pfh-tab="' ) );

echo "\n── every tab can be switched off ──\n";
foreach ( [ 'desc', 'ingr', 'storage', 'nutrition' ] as $key ) {
	ok( "  $key", false === strpos( tabs( $fixture_id, [ $key . 'On' => false ] ), 'data-pfh-tab="' . $key . '"' ) );
}
ok( 'with all four off, nothing is drawn at all', '' === trim( tabs( $fixture_id, [ 'descOn' => false, 'ingrOn' => false, 'storageOn' => false, 'nutritionOn' => false ] ) ) );

echo "\n── the steps panel ──\n";
ok( 'it is beside the tabs', false !== strpos( $html, 'pfh-tabs__steps' ) );
ok( 'with its eyebrow and title', false !== strpos( $html, 'ZO SIMPEL' ) && false !== strpos( $html, 'In 3 stappen' ) );
ok( 'a step for each row', 3 === substr_count( $html, 'pfh-tabs__step"' ) );
ok( 'numbered 01, 02, 03', false !== strpos( $html, '>01</span>' ) && false !== strpos( $html, '>03</span>' ) );
ok( 'the element can turn it off', false === strpos( tabs( $fixture_id, [ 'stepsOn' => false ] ), 'pfh-tabs__steps' ) );

update_post_meta( $fixture_id, $F::STEPS_SHOW, 'no' );
$off = tabs( $fixture_id );
ok( 'and so can a single product', false === strpos( $off, 'pfh-tabs__steps' ) );
ok( 'which widens the panels instead of leaving a gap', false !== strpos( $off, 'pfh-tabs__body--wide' ) );
update_post_meta( $fixture_id, $F::STEPS_SHOW, 'yes' );
ok( 'turning it back on brings it back', false !== strpos( tabs( $fixture_id ), 'pfh-tabs__steps' ) );

echo "\n── the fields read other shapes too ──\n";
update_post_meta( $bare_id, $F::FREE_FROM, [ 'Glutenvrij', 'Notenvrij' ] );
ok( 'a plain list of strings works', 2 === substr_count( tabs( $bare_id ), 'pfh-tabs__claim"' ) );
update_post_meta( $bare_id, $F::FREE_FROM, [ [ 'text' => 'Van ACF' ] ] );
ok( 'and ACF\'s own key names', false !== strpos( tabs( $bare_id ), 'Van ACF' ) );
delete_post_meta( $bare_id, $F::FREE_FROM );

echo "\n── it behaves like the rest of the plugin ──\n";
ok( 'rendering does not depend on Bricks building the controls', tabs( $fixture_id, [], true ) === tabs( $fixture_id ) );
ok( 'the controls encode for the builder', false !== wp_json_encode( controls() ) );
ok( 'no control default holds a 4-byte character', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', wp_json_encode( controls(), JSON_UNESCAPED_UNICODE ) ) );
ok( 'nor does the rendered page', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', $html ) );
ok( 'typed markup in a field is escaped', false === strpos( tabs( $fixture_id, [ 'ingrHeading' => '<script>x</script>' ] ), '<script>' ) );

$before = get_num_queries();
tabs( $fixture_id );
$cost = get_num_queries() - $before;
ok( 'a render stays reasonable on queries', $cost <= 30, "$cost queries" );

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
