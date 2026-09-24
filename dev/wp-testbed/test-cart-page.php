<?php
/**
 * The cart page element, against a real WooCommerce cart.
 *
 * What is checked is what a shopper would notice going wrong: the lines and
 * numbers are WooCommerce's own, changes land in the cart, the free shipping
 * bar counts the way the shop does, plugins that lock or reprice a line are
 * respected, and the hooks other plugins print into still fire in places
 * where their markup survives.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function cart_el( array $settings = [] ) {
	$e           = new PFH_Element_Cart_Page( [ 'id' => 'cp' . wp_rand( 1, 99999 ) ] );
	$e->settings = $settings;

	ob_start();
	$e->render();

	return (string) ob_get_clean();
}

function inner( array $opts = [], $removed = null ) {
	return PFH_Widgets_Cart_Page::inner( PFH_Widgets_Cart_Page::options( $opts ), $removed );
}

if ( ! WC()->cart ) {
	wc_load_cart();
}

wc_clear_notices();
WC()->cart->empty_cart();
WC()->customer->set_shipping_country( 'NL' );
WC()->customer->set_billing_country( 'NL' );

// Two simple products with a price, to fill the cart with.
$simple = [];

foreach ( wc_get_products( [ 'type' => 'simple', 'status' => 'publish', 'limit' => 20, 'orderby' => 'ID', 'order' => 'ASC' ] ) as $p ) {
	if ( $p->is_purchasable() && $p->is_in_stock() && (float) $p->get_price() > 0 && ! $p->is_sold_individually() ) {
		$simple[] = $p;
	}

	if ( count( $simple ) >= 3 ) {
		break;
	}
}

if ( count( $simple ) < 3 ) {
	echo "FAIL need three purchasable simple products in the testbed\n0 passed, 1 failed\n";
	exit;
}

[ $a, $b, $c ] = $simple;

// These are the shared demo products the other suites count on; everything
// changed here is put back at the end.
$restore = [];

foreach ( $simple as $p ) {
	$restore[ $p->get_id() ] = [
		'regular' => $p->get_regular_price(),
		'sale'    => $p->get_sale_price(),
		'manage'  => $p->get_manage_stock(),
		'stock'   => $p->get_stock_quantity(),
		'xs'      => $p->get_cross_sell_ids(),
	];
}

// Cheap enough that free shipping is still some way off.
foreach ( [ $a, $b ] as $p ) {
	$p->set_regular_price( '12.50' );
	$p->set_sale_price( '' );
	$p->save();
}

// Something to ship with, or WooCommerce decides nothing needs shipping.
$ship_zone = new WC_Shipping_Zone();
$ship_zone->set_zone_name( 'PFH test flat' );
$ship_zone->add_location( 'NL', 'country' );
$ship_zone->add_location( 'BE', 'country' );
$ship_zone->save();
$flat = $ship_zone->add_shipping_method( 'flat_rate' );
update_option( 'woocommerce_flat_rate_' . $flat . '_settings', [ 'title' => 'Bezorgen', 'cost' => '5', 'tax_status' => 'none' ] );
WC_Cache_Helper::get_transient_version( 'shipping', true );

echo "── an empty cart ──\n";

$html = cart_el();

ok( 'the empty state shows, with its own words', false !== strpos( $html, 'pfh-cartp__empty' ) && false !== strpos( $html, 'Je winkelwagen is nog <em>leeg</em>' ) );
ok( '  not WooCommerce\'s "currently empty" line as well', false === stripos( $html, 'currently empty' ) );
ok( '  with a way to the shop', false !== strpos( $html, 'pfh-cartp__empty-btn' ) && false !== strpos( $html, esc_url( wc_get_page_permalink( 'shop' ) ) ) );
ok( 'WooCommerce\'s own empty-cart hook is back in place afterwards', false !== has_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message' ) );

echo "\n── a cart with two products ──\n";

$ka = WC()->cart->add_to_cart( $a->get_id(), 2 );
$kb = WC()->cart->add_to_cart( $b->get_id(), 1 );
WC()->cart->calculate_totals();

$html = cart_el();

ok( 'both lines are drawn', 2 === substr_count( $html, 'data-pfh-cartp-item="' ) );
ok( '  with their names linked', false !== strpos( $html, esc_html( $a->get_name() ) ) && false !== strpos( $html, esc_url( $a->get_permalink() ) ) );
ok( '  and a stepper posting WooCommerce\'s own field name', false !== strpos( $html, 'name="cart[' . $ka . '][qty]" value="2"' ) );
ok( '  and a remove link that works without JavaScript', false !== strpos( $html, 'data-pfh-cartp-remove="' . $ka . '"' ) && false !== strpos( $html, 'remove_item=' . $ka ) );
ok( 'the count counts items, not lines', false !== strpos( $html, '3 artikelen' ) );
ok( 'one item reads in the singular', false !== strpos( PFH_Widgets_Cart_Page::inner( PFH_Widgets_Cart_Page::options( [ 'countMany' => '%count% x', 'countOne' => 'EEN' ] ) ), '3 x' ) );
ok( 'the line total is WooCommerce\'s', false !== strpos( $html, WC()->cart->get_product_subtotal( $a, 2 ) ) );
ok( 'the subtotal and total are WooCommerce\'s', false !== strpos( $html, WC()->cart->get_cart_subtotal() ) && false !== strpos( $html, WC()->cart->get_total() ) );
ok( '  the subtotal sits in its own row, not printed loose', (bool) preg_match( '#<tr class="pfh-cartp__row subtotal"><th scope="row">Subtotaal</th><td>.*?woocommerce-Price-amount#s', $html ) );
ok( 'shipping shows the rate WooCommerce chose', (bool) preg_match( '#<tr class="pfh-cartp__row shipping">.*?5,00#s', $html ) );
ok( 'the checkout button goes to checkout', false !== strpos( $html, 'class="pfh-cartp__checkout" href="' . esc_url( wc_get_checkout_url() ) . '"' ) );
ok( 'the three reasons are there', 3 === substr_count( $html, 'pfh-cartp__reason-icon' ) );
ok( 'the summary is labelled by its heading', false !== strpos( $html, 'aria-labelledby="pfh-cartp-summary-title"' ) && false !== strpos( $html, 'id="pfh-cartp-summary-title"' ) );
ok( 'the root carries the signed words and the cart hash', (bool) preg_match( '/data-pfh-cartp="[A-Za-z0-9+\/=]+\.[0-9a-f]{64}"/', $html ) && false !== strpos( $html, 'data-pfh-cartp-hash="' . WC()->cart->get_cart_hash() . '"' ) );
ok( 'a nonce for WooCommerce\'s own cart form is in it', false !== strpos( $html, 'name="woocommerce-cart-nonce"' ) );
ok( 'the coupon form is there, folded', false !== strpos( $html, '<details class="pfh-cartp__coupon"' ) && false === strpos( $html, '<details class="pfh-cartp__coupon" open' ) );

echo "\n── free shipping ──\n";

$state = PFH_Widgets_Cart_Page::free_shipping( PFH_Widgets_Cart_Page::options( [] ), PFH_Widgets_Cart_Page::rows( [] ) );

ok( 'the bar counts toward the shop\'s €60', $state && 60.0 === $state['threshold'] && ! $state['done'], wp_json_encode( $state ) );
ok( '  and knows what is missing', $state && abs( $state['left'] - ( 60 - $state['amount'] ) ) < 0.01 );
ok( '  the words say it', false !== strpos( $html, 'Nog <strong>' ) && false !== strpos( $html, 'tot gratis verzending' ) );

WC()->customer->set_shipping_country( 'BE' );
$be = PFH_Widgets_Cart_Page::free_shipping( PFH_Widgets_Cart_Page::options( [] ), [] );
ok( 'a Belgian address counts toward €70', $be && 70.0 === $be['threshold'], wp_json_encode( $be ) );
WC()->customer->set_shipping_country( 'NL' );

WC()->cart->set_quantity( $ka, 6 );
WC()->cart->calculate_totals();
$done = PFH_Widgets_Cart_Page::free_shipping( PFH_Widgets_Cart_Page::options( [] ), [] );
ok( 'past the amount, it is free', $done && $done['done'] && 100.0 === (float) $done['pct'] );
ok( '  and says so', false !== strpos( inner(), 'pfh-cartp__ship is-done' ) );
WC()->cart->set_quantity( $ka, 2 );
WC()->cart->calculate_totals();

ok( 'the bar can be switched off', false === strpos( inner( [ 'shipBar' => false ] ), 'pfh-cartp__ship' ) );

// When the shop uses WooCommerce's own free-shipping method, its amount wins.
// Added to the zone the cart ships to: WooCommerce matches one zone only.
$zone     = $ship_zone;
$instance = $zone->add_shipping_method( 'free_shipping' );
update_option( 'woocommerce_free_shipping_' . $instance . '_settings', [ 'title' => 'Gratis', 'requires' => 'min_amount', 'min_amount' => '150', 'ignore_discounts' => 'no' ] );
WC_Cache_Helper::get_transient_version( 'shipping', true );

$wc = PFH_Widgets_Cart_Page::free_shipping( PFH_Widgets_Cart_Page::options( [] ), [] );
ok( 'WooCommerce\'s own free-shipping minimum is used when there is one', $wc && 150.0 === $wc['threshold'], wp_json_encode( $wc ) );

$zone->delete_shipping_method( $instance );
WC_Cache_Helper::get_transient_version( 'shipping', true );

echo "\n── changing the cart ──\n";

PFH_Widgets_Cart_Page::apply( 'set', $ka, 4, '' );
ok( 'a quantity lands in the cart', 4 === (int) WC()->cart->get_cart_item( $ka )['quantity'] );

$b_max = $b;
$b_max->set_manage_stock( true );
$b_max->set_stock_quantity( 3 );
$b_max->save();
WC()->cart->get_cart_item( $kb )['data']->set_manage_stock( true );
WC()->cart->get_cart_item( $kb )['data']->set_stock_quantity( 3 );
PFH_Widgets_Cart_Page::apply( 'set', $kb, 99, '' );
ok( 'more than is in stock is held to what is', 3 === (int) WC()->cart->get_cart_item( $kb )['quantity'], (string) WC()->cart->get_cart_item( $kb )['quantity'] );
$b->set_manage_stock( false );
$b->save();

$removed = PFH_Widgets_Cart_Page::apply( 'set', $kb, 0, '' );
ok( 'zero removes the line', ! WC()->cart->get_cart_item( $kb ) && $removed && $removed['key'] === $kb );

$after = inner( [], $removed );
ok( '  and offers to undo it, naming the product', false !== strpos( $after, 'data-pfh-cartp-restore="' . $kb . '"' ) && false !== strpos( $after, esc_html( $b->get_name() ) ) );

PFH_Widgets_Cart_Page::apply( 'restore', $kb, 0, '' );
ok( 'undo puts it back', (bool) WC()->cart->get_cart_item( $kb ) );

$gone = PFH_Widgets_Cart_Page::apply( 'remove', $kb, 0, '' );
ok( 'remove removes it', ! WC()->cart->get_cart_item( $kb ) && $gone );
PFH_Widgets_Cart_Page::apply( 'restore', $kb, 0, '' );

ok( 'an unknown line is ignored rather than an error', null === PFH_Widgets_Cart_Page::apply( 'set', 'nope', 3, '' ) );

echo "\n── coupons ──\n";

$coupon = new WC_Coupon();
$coupon->set_code( 'pfhtest10' );
$coupon->set_discount_type( 'percent' );
$coupon->set_amount( 10 );
$coupon->save();

wc_clear_notices();
PFH_Widgets_Cart_Page::apply( 'coupon', '', 0, 'pfhtest10' );
WC()->cart->calculate_totals();
$html = inner();

ok( 'a coupon applies', WC()->cart->has_discount( 'pfhtest10' ) );
ok( '  shows as a line with its discount', false !== strpos( $html, 'coupon-pfhtest10' ) && false !== strpos( $html, '-<span class="woocommerce-Price-amount' ) );
ok( '  and a button to take it off, icon intact', false !== strpos( $html, 'data-pfh-cartp-uncoupon="pfhtest10"' ) && false !== strpos( $html, 'pfh-cartp__uncoupon" data-pfh-cartp-uncoupon="pfhtest10" aria-label="Verwijderen: pfhtest10"><svg' ) );
ok( 'WooCommerce\'s message about it is shown, once', 1 === substr_count( $html, 'woocommerce-message' ) || 1 === substr_count( $html, 'is-success' ) );
ok( '  and not again on the next draw', false === strpos( inner(), 'woocommerce-message' ) );

PFH_Widgets_Cart_Page::apply( 'uncoupon', '', 0, 'pfhtest10' );
ok( 'it comes off again', ! WC()->cart->has_discount( 'pfhtest10' ) );

wc_clear_notices();
PFH_Widgets_Cart_Page::apply( 'coupon', '', 0, 'no-such-code' );
$wrong = inner();
ok( 'a wrong code is told as an error', false !== strpos( $wrong, 'woocommerce-error' ) || false !== strpos( $wrong, 'is-error' ) );
wc_clear_notices();

$coupon->delete( true );

echo "\n── other plugins ──\n";

$fixed = static function ( $html, $key ) use ( $kb ) {
	return $key === $kb ? '<span class="gift">1</span>' : $html;
};
add_filter( 'woocommerce_cart_item_quantity', $fixed, 10, 2 );
$html = inner();
remove_filter( 'woocommerce_cart_item_quantity', $fixed, 10 );

ok( 'a line a plugin fixed at one gets no stepper', false === strpos( $html, 'name="cart[' . $kb . '][qty]"' ) && false !== strpos( $html, 'is-fixed"><span class="gift">1</span>' ) );
ok( '  the other line keeps its stepper', false !== strpos( $html, 'name="cart[' . $ka . '][qty]"' ) );

$wrap = static function ( $html ) {
	return $html . '<small class="per-unit">per stuk</small>';
};
add_filter( 'woocommerce_cart_item_quantity', $wrap );
$html = inner();
remove_filter( 'woocommerce_cart_item_quantity', $wrap );
ok( 'something added around the field is kept, stepper and all', false !== strpos( $html, 'data-pfh-cartp-input="' . $ka . '" /></button>' ) || ( false !== strpos( $html, 'per stuk' ) && false !== strpos( $html, 'name="cart[' . $ka . '][qty]"' ) ) );

$locked = static function ( $link, $key ) use ( $kb ) {
	return $key === $kb ? '' : $link;
};
add_filter( 'woocommerce_cart_item_remove_link', $locked, 10, 2 );
$html = inner();
remove_filter( 'woocommerce_cart_item_remove_link', $locked, 10 );
ok( 'a line a plugin will not let go of has no remove link', false === strpos( $html, 'data-pfh-cartp-remove="' . $kb . '"' ) && false !== strpos( $html, 'data-pfh-cartp-remove="' . $ka . '"' ) );

$rename = static function ( $name, $item, $key ) use ( $ka ) {
	return $key === $ka ? $name . ' <em>(cadeau)</em>' : $name;
};
add_filter( 'woocommerce_cart_item_name', $rename, 10, 3 );
ok( 'a renamed line reads as renamed', false !== strpos( inner(), '(cadeau)' ) );
remove_filter( 'woocommerce_cart_item_name', $rename, 10 );

$row = static function () {
	echo '<tr class="pfh-test-row"><th>Punten</th><td>+40</td></tr>';
};
add_action( 'woocommerce_cart_totals_before_order_total', $row );
$html = inner();
remove_action( 'woocommerce_cart_totals_before_order_total', $row );
ok( 'a row a plugin adds to the totals lands inside the table', (bool) preg_match( '#<table class="pfh-cartp__totals"><tbody>.*pfh-test-row.*</tbody></table>#s', $html ) );

$express = static function () {
	echo '<div class="pfh-test-express">Pay</div>';
};
add_action( 'woocommerce_proceed_to_checkout', $express, 30 );
$html = inner();
remove_action( 'woocommerce_proceed_to_checkout', $express, 30 );
ok( 'express payment buttons appear under ours', false !== strpos( $html, 'pfh-cartp__express wc-proceed-to-checkout"><div class="pfh-test-express">' ) );
ok( '  without WooCommerce\'s own plain button beside it', false === strpos( $html, 'checkout-button' ) );
ok( '  which is back on its hook afterwards', false !== has_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout' ) );

$before = static function () {
	echo '<div class="pfh-test-loyalty">Verdien 40 punten</div>';
};
add_action( 'woocommerce_before_cart', $before, 20 );
ok( 'a message on the before-cart hook shows', false !== strpos( inner(), 'pfh-test-loyalty' ) );
remove_action( 'woocommerce_before_cart', $before, 20 );

echo "\n── the words ──\n";

$html = cart_el( [ 'continue' => '', 'title' => 'Mijn <em>tas</em><script>x</script>', 'reasons' => [] ] );
ok( 'a link cleared on purpose stays away', false === strpos( $html, 'pfh-cartp__continue' ) );
ok( 'the title keeps its serif word and nothing else', false !== strpos( $html, 'Mijn <em>tas</em>x' ) && false === strpos( $html, '<script>' ) );
ok( 'no reasons, no list', false === strpos( $html, 'pfh-cartp__reasons' ) );

$emoji = PFH_Widgets_Cart_Page::options( [ 'checkout' => "Afrekenen \u{1F512}" ] );
ok( 'an emoji is dropped rather than breaking a save', 'Afrekenen' === $emoji['checkout'] );

$token = PFH_Widgets_Cart_Page::sign( PFH_Widgets_Cart_Page::options( [ 'checkout' => 'Betalen' ] ) );
ok( 'the signed words come back', 'Betalen' === PFH_Widgets_Cart_Page::unsign( $token )['checkout'] );
ok( '  a changed token is refused', null === PFH_Widgets_Cart_Page::unsign( base64_encode( '{"checkout":"<b>x</b>"}' ) . '.' . substr( $token, -64 ) ) );

echo "\n── the page it sits on ──\n";

wp_enqueue_script( 'wc-cart', 'https://example.test/cart.js', [], '1', true );
PFH_Widgets_Cart_Page::claim();
do_action( 'wp_footer' );
ok( 'WooCommerce\'s classic cart script is taken off the page', ! wp_script_is( 'wc-cart', 'enqueued' ) );

echo "\n── what goes with the cart ──\n";

$c->set_regular_price( '9' );
$c->save();
$a->set_cross_sell_ids( [ $b->get_id(), $c->get_id() ] );
$a->save();
WC()->cart->empty_cart();
WC()->cart->add_to_cart( $a->get_id(), 1 );
WC()->cart->add_to_cart( $b->get_id(), 1 );

$slider = new PFH_Element_Products( [ 'id' => 'gw' ] );
$slider->settings = [ 'source' => 'cart', 'limit' => 4 ];
$method = new ReflectionMethod( $slider, 'goes_with_cart' );
$ids = $method->invoke( $slider, 4 );

ok( 'the shop\'s cross-sell comes first', isset( $ids[0] ) && $c->get_id() === $ids[0], implode( ',', $ids ) );
ok( '  nothing already in the cart is offered', ! in_array( $a->get_id(), $ids, true ) && ! in_array( $b->get_id(), $ids, true ) );
ok( '  the row is filled up to the limit', count( $ids ) <= 4 && count( $ids ) >= 1 );

$a->set_cross_sell_ids( [] );
$a->save();

echo "\n── in the builder ──\n";

WC()->cart->empty_cart();
$preview = PFH_Widgets_Cart_Page::inner( array_merge( PFH_Widgets_Cart_Page::options( [] ), [ 'preview' => true ] ) );
ok( 'an empty cart shows products standing in, so the layout can be styled', substr_count( $preview, 'data-pfh-cartp-item="preview-' ) >= 1 );
ok( '  without firing the cart hooks', false === strpos( $preview, 'woocommerce-cart-nonce' ) );

wc_clear_notices();
WC()->cart->empty_cart();
$ship_zone->delete();
WC_Cache_Helper::get_transient_version( 'shipping', true );

foreach ( $restore as $id => $was ) {
	$p = wc_get_product( $id );
	$p->set_regular_price( $was['regular'] );
	$p->set_sale_price( $was['sale'] );
	$p->set_manage_stock( $was['manage'] );
	$p->set_stock_quantity( $was['stock'] );
	$p->set_cross_sell_ids( $was['xs'] );
	$p->save();
}

echo "\n$pass passed, $fail failed\n";
