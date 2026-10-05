<?php
/**
 * A variation never reaches the cart with a choice left empty.
 *
 * On live (2026-10-06) WooCommerce's list of attributes was a stale copy, so
 * the flavour taxonomies were not registered: the product page showed no
 * flavours, the quick-add chooser showed no fields, and the bundle went into
 * the cart with every flavour empty — WooCommerce only checks attributes it
 * can see, and it saw none. The endpoint now refuses that line itself.
 */
require __DIR__ . '/wp-load.php';

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

wp_set_current_user( 1 );

echo "── complete() ──\n";
ok( 'every value set is complete', PFH_Widgets_Quickadd::complete( [ 'attribute_pa_smaak' => 'citroen', 'attribute_pa_smaak2' => 'kers' ] ) );
ok( 'no attributes at all is complete', PFH_Widgets_Quickadd::complete( [] ) );
ok( 'one empty value is not', ! PFH_Widgets_Quickadd::complete( [ 'attribute_pa_smaak' => 'citroen', 'attribute_pa_smaak2' => '' ] ) );
ok( 'whitespace is empty', ! PFH_Widgets_Quickadd::complete( [ 'attribute_pa_smaak' => '  ' ] ) );
ok( '"0" is a value', PFH_Widgets_Quickadd::complete( [ 'attribute_maat' => '0' ] ) );

echo "\n── a bundle whose flavours the shopper chooses ──\n";
// A global attribute with three terms, and a bundle whose one variation says "any".
$tax = 'pa_guardsmaak';
if ( ! wc_attribute_taxonomy_id_by_name( $tax ) ) {
	wc_create_attribute( [ 'name' => 'Guardsmaak', 'slug' => 'guardsmaak', 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ] );
}
register_taxonomy( $tax, 'product', [ 'hierarchical' => false, 'label' => 'Guardsmaak' ] );
foreach ( [ 'citroen', 'kers', 'perzik' ] as $slug ) {
	if ( ! term_exists( $slug, $tax ) ) {
		wp_insert_term( ucfirst( $slug ), $tax, [ 'slug' => $slug ] );
	}
}

$product = new WC_Product_Variable();
$product->set_name( 'Guard bundel ' . wp_generate_password( 4, false ) );
$product->set_status( 'publish' );
$attribute = new WC_Product_Attribute();
$attribute->set_id( wc_attribute_taxonomy_id_by_name( $tax ) );
$attribute->set_name( $tax );
$attribute->set_options( array_map( static function ( $s ) use ( $tax ) { return get_term_by( 'slug', $s, $tax )->term_id; }, [ 'citroen', 'kers', 'perzik' ] ) );
$attribute->set_visible( true );
$attribute->set_variation( true );
$product->set_attributes( [ $attribute ] );
$product_id = $product->save();

$variation = new WC_Product_Variation();
$variation->set_parent_id( $product_id );
$variation->set_attributes( [ $tax => '' ] ); // "any"
$variation->set_regular_price( '10' );
$variation->set_stock_status( 'instock' );
$variation_id = $variation->save();
WC_Product_Variable::sync( $product_id );

$resolved = PFH_Widgets_Quickadd::variation_attributes( $variation_id, [] );
ok( 'with nothing chosen the flavour resolves empty', isset( $resolved[ 'attribute_' . $tax ] ) && '' === $resolved[ 'attribute_' . $tax ], wp_json_encode( $resolved ) );
ok( '  and the line is refused', ! PFH_Widgets_Quickadd::complete( $resolved ) );

$resolved = PFH_Widgets_Quickadd::variation_attributes( $variation_id, [ 'attribute_' . $tax => 'kers' ] );
ok( 'with a flavour chosen it resolves to it', 'kers' === ( $resolved[ 'attribute_' . $tax ] ?? null ) );
ok( '  and the line is accepted', PFH_Widgets_Quickadd::complete( $resolved ) );

echo "\n── the failure seen on live: the taxonomy is not registered ──\n";
// What WooCommerce itself would do with an empty flavour once it cannot see the attribute.
unregister_taxonomy( $tax );
wc_delete_product_transients( $product_id );
$blind = wc_get_product( $product_id );
ok( 'WooCommerce then sees no attributes on the bundle', [] === $blind->get_variation_attributes(), wp_json_encode( array_keys( $blind->get_variation_attributes() ) ) );

$resolved = PFH_Widgets_Quickadd::variation_attributes( $variation_id, [] );
ok( 'the variation still says the flavour is open', '' === ( $resolved[ 'attribute_' . $tax ] ?? 'missing' ), wp_json_encode( $resolved ) );
ok( '  so the endpoint refuses it', ! PFH_Widgets_Quickadd::complete( $resolved ) );

if ( ! WC()->cart ) { wc_load_cart(); }
WC()->cart->empty_cart();
$taken = WC()->cart->add_to_cart( $product_id, 1, $variation_id, $resolved );
ok( 'which matters: WooCommerce on its own would have taken it', (bool) $taken );
WC()->cart->empty_cart();
wc_clear_notices();

// Leave the testbed as it was found: the other suites assert a known catalogue.
register_taxonomy( $tax, 'product', [ 'hierarchical' => false ] );
wp_delete_post( $variation_id, true );
wp_delete_post( $product_id, true );
wc_delete_attribute( wc_attribute_taxonomy_id_by_name( $tax ) );

echo "\n$pass passed, $fail failed\n";
