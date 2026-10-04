<?php
/**
 * My Account's own addresses (go-live check, 2026-10-04).
 *
 * WooCommerce sends people to the account page with an endpoint on the end:
 * edit-address/billing from the "edit" button, view-order/123 from every
 * order email, lost-password and its reset link, orders/2 for older orders.
 * The element drew the same overview for all of them, and its details form
 * could not be saved. These checks open each address and run WooCommerce's
 * own save handlers, as a customer's browser would.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function customer( $email ) {
	$id = email_exists( $email );

	if ( $id ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $id );
	}

	return (int) wp_insert_user( [ 'user_login' => $email, 'user_email' => $email, 'user_pass' => wp_generate_password(), 'role' => 'customer', 'first_name' => 'Anna', 'last_name' => 'Jansen', 'display_name' => 'Anna Jansen' ] );
}

function order_for( $customer, $status, $when ) {
	$pid   = get_posts( [ 'post_type' => 'product', 'numberposts' => 1, 'fields' => 'ids' ] )[0];
	$order = wc_create_order( [ 'customer_id' => $customer ] );
	$order->add_product( wc_get_product( $pid ), 1 );
	$order->set_date_created( gmdate( 'Y-m-d H:i:s', strtotime( $when ) ) );
	$order->calculate_totals();
	$order->set_status( $status );
	$order->save();

	return $order;
}

/** Open the account page at an endpoint, as WordPress would have parsed it. */
function at( $endpoint = '', $value = '', array $settings = [] ) {
	global $wp;

	$wp->query_vars = [ 'pagename' => 'my-account' ];

	if ( '' !== $endpoint ) {
		$vars = WC()->query->get_query_vars();
		$wp->query_vars[ isset( $vars[ $endpoint ] ) ? $vars[ $endpoint ] : $endpoint ] = $value;
	}

	$el           = new PFH_Element_Account( [ 'id' => 'acc' ] );
	$el->name     = 'pfh-account';
	$el->settings = $settings;

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

function selected_pane( $html ) {
	return preg_match( '/aria-selected="true" tabindex="0" data-pfh-acc-tab="([^"]+)"/', $html, $m ) ? $m[1] : '';
}

/** Run one of WooCommerce's own form handlers; its redirect ends the run. */
function handle( callable $handler, array $post ) {
	$_POST = $_REQUEST = $post;
	$to    = '';
	$stop  = static function ( $location ) use ( &$to ) {
		$to = $location;
		throw new Exception( 'redirect' );
	};

	add_filter( 'wp_redirect', $stop, 1 );

	try {
		$handler();
	} catch ( Exception $e ) {} // phpcs:ignore Generic.CodeAnalysis.EmptyStatement

	remove_filter( 'wp_redirect', $stop, 1 );
	$_POST = $_REQUEST = [];

	return $to;
}

function notices_text() {
	$out = [];

	foreach ( wc_get_notices() as $type => $list ) {
		foreach ( $list as $notice ) {
			$out[] = $type . ': ' . wp_strip_all_tags( is_array( $notice ) ? $notice['notice'] : $notice );
		}
	}

	return implode( ' | ', $out );
}

$anna  = customer( 'pages-anna@example.test' );
$other = customer( 'pages-bas@example.test' );
$o1    = order_for( $anna, 'completed', '-20 days' );
$o2    = order_for( $anna, 'processing', '-10 days' );
$o3    = order_for( $anna, 'pending', '-2 days' );
$their = order_for( $other, 'processing', '-5 days' );

wp_set_current_user( $anna );
wc_clear_notices();

echo "── the account opens where the address says ──\n";
ok( 'the plain account page opens the overview', 'dashboard' === selected_pane( at() ) );
ok( '/edit-account/ opens the details', 'details' === selected_pane( at( 'edit-account' ) ) );
ok( '/edit-address/ opens the addresses', 'addresses' === selected_pane( at( 'edit-address' ) ) );
ok( '/orders/ opens the orders', 'orders' === selected_pane( at( 'orders' ) ) );

echo "\n── editing an address ──\n";
$form = at( 'edit-address', 'billing' );
ok( '/edit-address/billing/ shows WooCommerce\'s address form', false !== strpos( $form, 'name="billing_first_name"' ) && false !== strpos( $form, 'name="billing_postcode"' ) );
ok( '  in the addresses pane, opened', 'addresses' === selected_pane( $form ) );
ok( '  with its nonce and its action', false !== strpos( $form, 'woocommerce-edit-address-nonce' ) && false !== strpos( $form, 'value="edit_address"' ) );
ok( '  and a way back to the addresses', false !== strpos( $form, 'Terug naar je adressen' ) );
ok( '/edit-address/shipping/ shows the shipping form', false !== strpos( at( 'edit-address', 'shipping' ), 'name="shipping_first_name"' ) );

global $wp;
$wp->query_vars = [ 'pagename' => 'my-account', 'edit-address' => 'billing' ];
$to = handle(
	[ 'WC_Form_Handler', 'save_address' ],
	[
		'action'                         => 'edit_address',
		'woocommerce-edit-address-nonce' => wp_create_nonce( 'woocommerce-edit_address' ),
		'billing_first_name'             => 'Anna',
		'billing_last_name'              => 'Jansen',
		'billing_country'                => 'NL',
		'billing_address_1'              => 'Teststraat 12',
		'billing_postcode'               => '1234 AB',
		'billing_city'                   => 'Utrecht',
		'billing_phone'                  => '0612345678',
		'billing_email'                  => 'pages-anna@example.test',
		'save_address'                   => 'Adres opslaan',
	]
);
$saved = new WC_Customer( $anna );
ok( 'saving it stores the address', 'Teststraat 12' === $saved->get_billing_address_1() && 'Utrecht' === $saved->get_billing_city(), notices_text() );
ok( '  and comes back to the addresses', false !== strpos( $to, 'edit-address' ) );
$back = at( 'edit-address' );
ok( '  which show it, with the message', false !== strpos( $back, 'Teststraat 12' ) && false !== strpos( $back, 'pfh-acc__notice--good' ) );
wc_clear_notices();

echo "\n── the account details ──\n";
$details = at( 'edit-account' );
ok( 'the details form sends the display name WooCommerce requires', (bool) preg_match( '/name="account_display_name" value="Anna Jansen"/', $details ) );
$to = handle(
	[ 'WC_Form_Handler', 'save_account_details' ],
	[
		'action'                     => 'save_account_details',
		'save-account-details-nonce' => wp_create_nonce( 'save_account_details' ),
		'account_first_name'         => 'Annemiek',
		'account_last_name'          => 'Jansen',
		'account_display_name'       => 'Anna Jansen',
		'account_email'              => 'pages-anna@example.test',
		'password_current'           => '',
		'password_1'                 => '',
		'password_2'                 => '',
	]
);
ok( 'saving a new first name works', 'Annemiek' === get_userdata( $anna )->first_name, notices_text() );
ok( '  without "Display name is a required field"', false === stripos( notices_text(), 'display name' ) );
wc_clear_notices();

echo "\n── orders ──\n";
$view = at( 'view-order', (string) $o1->get_id() );
ok( '/view-order/ID/ (the link in order emails) opens the orders', 'orders' === selected_pane( $view ) );
ok( '  with that order unfolded', (bool) preg_match( '/pfh-acc__order is-open" data-pfh-acc-order="' . $o1->get_id() . '"/', $view ) );
$theirs = at( 'view-order', (string) $their->get_id() );
ok( 'somebody else\'s order is not shown', false === strpos( $theirs, 'data-pfh-acc-order="' . $their->get_id() . '"' ) && false !== strpos( $theirs, 'kon niet worden geopend' ) );

$page1 = at( 'orders', '', [ 'orders' => 2 ] );
ok( 'more orders than fit: two on the first page', 2 === substr_count( $page1, '<li class="pfh-acc__order' ) - 1, substr_count( $page1, '<li class="pfh-acc__order' ) . ' order rows incl. the overview' );
ok( '  and a link to the older ones', false !== strpos( $page1, 'Oudere bestellingen' ) && false !== strpos( $page1, '/orders/2/' ) );
$page2 = at( 'orders', '2', [ 'orders' => 2 ] );
ok( '/orders/2/ shows the oldest order', false !== strpos( $page2, 'data-pfh-acc-order="' . $o1->get_id() . '"' ) && false !== strpos( $page2, 'Nieuwere bestellingen' ) );
ok( 'the order count counts every order', (bool) preg_match( '/data-pfh-acc-tab="orders">.*?pfh-acc__tab-count">3</s', $page1 ) );

$pending = PFH_Element_Account::order_detail( $o3 );
ok( 'an unpaid order can be paid', false !== strpos( $pending, esc_url( $o3->get_checkout_payment_url() ) ) );
ok( '  and cancelled, as WooCommerce allows', false !== strpos( $pending, 'cancel_order' ) );
$done = PFH_Element_Account::order_detail( $o1 );
ok( 'a completed order can be ordered again', false !== strpos( $done, 'order_again=' . $o1->get_id() ) );
ok( 'every order links to its own page', false !== strpos( $done, esc_url( $o1->get_view_order_url() ) ) );
add_filter(
	'woocommerce_my_account_my_orders_actions',
	static function ( $actions ) {
		$actions['invoice'] = [ 'url' => 'https://example.test/factuur.pdf', 'name' => 'Factuur' ];
		return $actions;
	}
);
ok( 'buttons plugins add (the invoice) show too', false !== strpos( PFH_Element_Account::order_detail( $o1 ), 'Factuur' ) );

echo "\n── saved payment methods ──\n";
$with_methods = static function ( $items ) {
	$items['payment-methods'] = 'Betaalmethodes';
	return $items;
};
add_filter( 'woocommerce_account_menu_items', $with_methods, 99 );
$pm = at( 'payment-methods' );
ok( 'when WooCommerce offers them, they get a tab', false !== strpos( $pm, 'data-pfh-acc-tab="payments"' ) );
ok( '  /payment-methods/ opens it', 'payments' === selected_pane( $pm ) );
ok( '  with WooCommerce\'s own list', false !== strpos( $pm, 'woocommerce' ) && (bool) preg_match( '/pfh-pane-payments-acc.*?pfh-acc__woo-form/s', $pm ) );
remove_filter( 'woocommerce_account_menu_items', $with_methods, 99 );
ok( 'without them, no tab', false === strpos( at(), 'data-pfh-acc-tab="payments"' ) );
ok( 'no Downloads tab for a customer with nothing to download, as on the live shop', false === strpos( at(), 'data-pfh-acc-tab="downloads"' ) );

echo "\n── signed out: the password reset ──\n";
wp_set_current_user( 0 );
$lost = at( 'lost-password' );
ok( '/lost-password/ shows the reset request form', false !== strpos( $lost, 'name="user_login"' ) && false !== strpos( $lost, 'wc_reset_password' ) );
ok( '  not the sign-in form', false === strpos( $lost, 'name="username"' ) );
ok( '  with a way back to signing in', false !== strpos( $lost, 'Terug naar inloggen' ) );
$_GET['reset-link-sent'] = 'true';
ok( 'after asking: "check your email"', false !== stripos( at( 'lost-password' ), 'lost-password-confirmation' ) || false !== stripos( at( 'lost-password' ), 'e-mail' ) || false !== stripos( at( 'lost-password' ), 'email' ) );
unset( $_GET['reset-link-sent'] );

$user = get_userdata( $anna );
$key  = get_password_reset_key( $user );
$_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] = $anna . ':' . $key;
$_GET['show-reset-form']                 = 'true';
$reset = at( 'lost-password' );
ok( 'the reset link shows the new-password form', false !== strpos( $reset, 'name="password_1"' ) && false !== strpos( $reset, 'name="reset_key"' ), substr( wp_strip_all_tags( $reset ), 0, 160 ) );
unset( $_GET['show-reset-form'], $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] );

ok( 'the plain page still shows the sign-in form', false !== strpos( at(), 'name="username"' ) );

// Leave nothing behind.
foreach ( [ $o1, $o2, $o3, $their ] as $order ) {
	$order->delete( true );
}
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $anna );
wp_delete_user( $other );
$wp->query_vars = [];

echo "\n$pass passed, $fail failed\n";
