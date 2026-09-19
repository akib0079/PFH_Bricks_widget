<?php
/**
 * The account page.
 *
 * The assertion that matters most in this file is the one about somebody
 * else's order. An order id is a small number in a form field, and the whole
 * point of fetching order contents over AJAX is that the endpoint decides who
 * may see them.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

/* ------------------------------------------------------------------ *
 * Two customers, and an order each
 * ------------------------------------------------------------------ */

function make_customer( $email ) {
	$id = email_exists( $email );

	if ( ! $id ) {
		$id = wp_insert_user( [ 'user_login' => $email, 'user_email' => $email, 'user_pass' => wp_generate_password(), 'role' => 'customer', 'first_name' => 'Test' ] );
	}

	return (int) $id;
}

function make_order( $customer, $status, $when, $tracking = false ) {
	$products = get_posts( [ 'post_type' => 'product', 'numberposts' => 2, 'fields' => 'ids' ] );
	$order    = wc_create_order( [ 'customer_id' => $customer ] );

	foreach ( $products as $pid ) {
		$order->add_product( wc_get_product( $pid ), 2 );
	}

	$order->set_date_created( gmdate( 'Y-m-d H:i:s', strtotime( $when ) ) );
	$order->calculate_totals();
	$order->set_status( $status );

	if ( in_array( $status, [ 'processing', 'completed' ], true ) ) {
		$order->set_date_paid( gmdate( 'Y-m-d H:i:s', strtotime( $when ) ) );
	}

	if ( $tracking ) {
		$order->update_meta_data( '_tracking_number', '3STESTNUMBER1' );
		$order->update_meta_data( '_tracking_provider', 'PostNL' );
		$order->update_meta_data( '_tracking_url', 'https://postnl.nl/tracktrace/' );
	}

	$order->save();

	return $order;
}

$mine   = make_customer( 'pfh-acct-mine@example.test' );
$theirs = make_customer( 'pfh-acct-theirs@example.test' );

$order_open   = make_order( $mine, 'processing', '-2 days', true );
$order_done   = make_order( $mine, 'completed', '-20 days' );
$order_gone   = make_order( $mine, 'cancelled', '-40 days' );
$order_theirs = make_order( $theirs, 'processing', '-1 day' );

function draw( $settings = [] ) {
	$el       = new PFH_Element_Account( [ 'id' => 'acc' ] );
	$el->name = 'pfh-account';
	$el->settings = $settings;

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ *
 * Signed out
 * ------------------------------------------------------------------ */

wp_set_current_user( 0 );
update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
update_option( 'woocommerce_registration_generate_password', 'no' );

$out = draw();

echo "── a visitor gets the sign-in card ──\n";
ok( 'it is drawn', false !== strpos( $out, 'pfh-acc__auth' ) );
ok( 'with the title and the panel', false !== strpos( $out, 'Welkom' ) && false !== strpos( $out, 'pfh-acc__auth-aside' ) );
ok( 'and the panel\'s points', 4 === substr_count( $out, 'class="pfh-acc__auth-point"' ), substr_count( $out, 'class="pfh-acc__auth-point"' ) . ' points' );
ok( 'no account is shown', false === strpos( $out, 'pfh-acc__rail' ) );

echo "\n── the sign-in form is WooCommerce's own ──\n";
ok( 'it posts a username', false !== strpos( $out, 'name="username"' ) );
ok( '  and a password', false !== strpos( $out, 'name="password"' ) );
ok( '  with remember me', false !== strpos( $out, 'name="rememberme"' ) );
ok( '  under the nonce WooCommerce checks', false !== strpos( $out, 'name="woocommerce-login-nonce"' ) );
ok( '  and the button it looks for', false !== strpos( $out, 'name="login"' ) );
ok( 'lost password goes to WooCommerce too', false !== strpos( $out, 'lost-password' ) || false !== strpos( $out, 'lostpassword' ) );

echo "\n── registering ──\n";
ok( 'the two forms share one switch', false !== strpos( $out, 'data-pfh-acc-seg' ) );
ok( 'the register form is behind it', false !== strpos( $out, 'data-pfh-acc-form="register"' ) );
ok( '  and starts hidden', (bool) preg_match( '/id="pfh-form-register-[^"]*"[^>]*hidden/', $out ) );
ok( 'it posts an email', false !== strpos( $out, 'name="email"' ) );
ok( '  under WooCommerce\'s own nonce', false !== strpos( $out, 'name="woocommerce-register-nonce"' ) );
ok( '  and its button', false !== strpos( $out, 'name="register"' ) );
ok( 'a name is asked for', false !== strpos( $out, 'name="pfh_first_name"' ) && false !== strpos( $out, 'name="pfh_last_name"' ) );
ok( '  which can be switched off', false === strpos( draw( [ 'showNames' => false ] ), 'name="pfh_first_name"' ) );

update_option( 'woocommerce_enable_myaccount_registration', 'no' );
$closed = draw();
ok( 'a shop with registration off shows only the sign-in', false === strpos( $closed, 'data-pfh-acc-form="register"' ) );
ok( '  and no switch either', false === strpos( $closed, 'data-pfh-acc-seg' ) );
update_option( 'woocommerce_enable_myaccount_registration', 'yes' );

ok( 'the element can decline registration on its own', false === strpos( draw( [ 'showRegister' => false ] ), 'data-pfh-acc-form="register"' ) );

$generated = ( function () {
	update_option( 'woocommerce_registration_generate_password', 'yes' );
	$html = draw();
	update_option( 'woocommerce_registration_generate_password', 'no' );

	return $html;
} )();
ok( 'a shop that makes its own passwords asks for none', 1 === substr_count( $generated, 'type="password"' ), substr_count( $generated, 'type="password"' ) . ' password fields' );

/* ------------------------------------------------------------------ *
 * Signed in
 * ------------------------------------------------------------------ */

wp_set_current_user( $mine );
$in = draw();

echo "\n── a customer gets the account ──\n";
ok( 'the rail is drawn', false !== strpos( $in, 'pfh-acc__rail' ) );
ok( 'with a tab for each part', 4 === substr_count( $in, 'data-pfh-acc-tab=' ) );
ok( '  and a way out', false !== strpos( $in, 'pfh-acc__tab--out' ) );
ok( 'the first tab opens', 1 === substr_count( $in, 'aria-selected="true"' ) );
ok( 'one pane for each tab', 4 === substr_count( $in, 'class="pfh-acc__pane"' ), substr_count( $in, 'class="pfh-acc__pane"' ) . ' panes' );
ok( '  and only the first is shown', 3 === substr_count( $in, 'role="tabpanel" tabindex="0" hidden' ) + substr_count( $in, 'tabindex="0" hidden' ) - 0, substr_count( $in, 'hidden' ) . ' hidden in total' );
ok( 'no sign-in card is shown', false === strpos( $in, 'pfh-acc__auth' ) );
ok( 'the greeting names the customer', false !== strpos( $in, 'Test' ) );

echo "\n── the tiles ──\n";
ok( 'three of them', 3 === substr_count( $in, 'pfh-acc__stat"' ) );
ok( 'counting this customer\'s orders', false !== strpos( $in, '>3</span>' ), 'expected 3 orders' );
ok( '  not anybody else\'s', false === strpos( $in, '>4</span>' ) );
ok( 'they can be switched off', false === strpos( draw( [ 'showStats' => false ] ), 'pfh-acc__stat"' ) );

echo "\n── the orders ──\n";
ok( 'each order is listed', 3 === substr_count( $in, 'data-pfh-acc-order=' ) - 1, 'the newest is listed twice, once on the overview' );
ok( 'the newest opens with the page', false !== strpos( $in, 'pfh-acc__order is-open' ) );
ok( '  showing its contents', false !== strpos( $in, 'pfh-acc__lines' ) );
ok( 'the others wait to be asked', false !== strpos( $in, 'pfh-acc__loading' ) );
ok( 'every status gets its own colour', false !== strpos( $in, 'pfh-acc__status--processing' ) && false !== strpos( $in, 'pfh-acc__status--completed' ) && false !== strpos( $in, 'pfh-acc__status--cancelled' ) );

echo "\n── where the parcel is ──\n";
$detail = PFH_Element_Account::order_detail( $order_open );

ok( 'the four steps are drawn', 4 === substr_count( $detail, 'pfh-acc__step ' ) );
ok( 'a paid, processing order is on its way', false !== strpos( $detail, 'is-now' ) );
ok( '  with the steps before it done', 2 === substr_count( $detail, 'is-done' ), substr_count( $detail, 'is-done' ) . ' done' );
ok( '  and the ones after it still ahead', 1 === substr_count( $detail, 'class="pfh-acc__step ">' ), substr_count( $detail, 'class="pfh-acc__step ">' ) . ' plain steps' );
ok( 'the tracking number is shown', false !== strpos( $detail, '3STESTNUMBER1' ) && false !== strpos( $detail, 'PostNL' ) );
ok( '  with a link to follow it', false !== strpos( $detail, 'postnl.nl/tracktrace' ) );
ok( 'the lines are there', 2 === substr_count( $detail, 'pfh-acc__line"' ) );
ok( 'and what it came to', false !== strpos( $detail, 'pfh-acc__total-row--grand' ) );

$finished = PFH_Element_Account::order_detail( $order_done );
ok( 'a finished order shows every step done', 4 === substr_count( $finished, 'is-done' ), substr_count( $finished, 'is-done' ) . ' done' );
ok( '  and nothing still in progress', false === strpos( $finished, 'is-now' ) );

$stopped = PFH_Element_Account::order_detail( $order_gone );
ok( 'a cancelled order says so instead of drawing a journey', false === strpos( $stopped, 'pfh-acc__track' ) && false !== strpos( $stopped, 'pfh-acc__notice' ) );

ok( 'the steps can be switched off', false === strpos( PFH_Element_Account::order_detail( $order_open, false ), 'pfh-acc__track' ) );

echo "\n── tracking comes from wherever the shop keeps it ──\n";
$trace = PFH_Widgets_Account::tracking( $order_open );
ok( 'the plain meta keys are read', '3STESTNUMBER1' === $trace['number'] );

$shipment = make_order( $mine, 'processing', '-3 days' );
$shipment->update_meta_data( '_wc_shipment_tracking_items', [ [ 'tracking_number' => 'SHIP999', 'tracking_provider' => 'DHL' ] ] );
$shipment->save();
ok( 'so is WooCommerce Shipment Tracking', 'SHIP999' === PFH_Widgets_Account::tracking( wc_get_order( $shipment->get_id() ) )['number'] );

add_filter( 'pfh_account_order_tracking', static function () { return [ 'number' => 'FILTERED', 'carrier' => 'X', 'url' => '', 'date' => null ]; } );
ok( 'and a shop can supply its own', 'FILTERED' === PFH_Widgets_Account::tracking( $order_open )['number'] );
remove_all_filters( 'pfh_account_order_tracking' );

/* ------------------------------------------------------------------ *
 * Somebody else's order
 * ------------------------------------------------------------------ */

echo "\n── the endpoint decides who may look ──\n";

function ajax( array $post ) {
	$_POST    = $post;
	$_REQUEST = $post;

	$handler = static function () {
		return static function () { throw new RuntimeException( 'wp_die' ); };
	};

	add_filter( 'wp_doing_ajax', '__return_true' );
	add_filter( 'wp_die_ajax_handler', $handler );

	ob_start();

	try {
		PFH_Widgets_Account::order();
	} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
		// wp_send_json always ends in wp_die; the body is already buffered.
	}

	$body = (string) ob_get_clean();

	remove_filter( 'wp_doing_ajax', '__return_true' );
	remove_filter( 'wp_die_ajax_handler', $handler );

	return json_decode( $body, true );
}

$nonce = wp_create_nonce( PFH_Widgets_Ajax::NONCE );

wp_set_current_user( $mine );
$reply = ajax( [ 'nonce' => $nonce, 'order' => $order_open->get_id() ] );
ok( 'a customer can open their own order', ! empty( $reply['success'] ) && ! empty( $reply['data']['html'] ) );
ok( '  and gets the contents, not a stub', ! empty( $reply['data']['html'] ) && false !== strpos( $reply['data']['html'], 'pfh-acc__line' ) );

$refused = ajax( [ 'nonce' => $nonce, 'order' => $order_theirs->get_id() ] );
ok( 'somebody else\'s order is refused', empty( $refused['success'] ), 'IT WAS RETURNED' );
ok( '  and nothing of it leaks', empty( $refused['data']['html'] ) );

wp_set_current_user( 0 );
$logged_out = ajax( [ 'nonce' => $nonce, 'order' => $order_open->get_id() ] );
ok( 'a signed-out visitor gets nothing', empty( $logged_out['success'] ) );

wp_set_current_user( $mine );
$no_nonce = ajax( [ 'order' => $order_open->get_id() ] );
ok( 'and a request with no nonce is not answered', empty( $no_nonce['success'] ) );

/* ------------------------------------------------------------------ *
 * House rules
 * ------------------------------------------------------------------ */

echo "\n── it behaves like the rest of the plugin ──\n";
wp_set_current_user( $mine );

$el = new PFH_Element_Account( [ 'id' => 'c' ] );
$el->name = 'pfh-account';
$el->set_control_groups();
$el->set_controls();

ok( 'it encodes its controls for the builder', false !== wp_json_encode( $el->controls ) );
ok( 'and holds no 4-byte character', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', wp_json_encode( $el->controls, JSON_UNESCAPED_UNICODE ) ) );

$missing = [];

foreach ( $el->controls as $key => $control ) {
	if ( ! isset( $control['tab'] ) ) { $missing[] = $key; }
}

ok( 'every control declares its tab', ! $missing, implode( ', ', $missing ) );

$panel = ( function () {
	$el = new PFH_Element_Account( [ 'id' => 'acc' ] );
	$el->name = 'pfh-account';
	$el->set_control_groups();
	$el->set_controls();
	$el->settings = [];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
} )();

ok( 'it renders the same without the controls built', $panel === draw() );

/* ---- put the shop back ---- */
foreach ( [ $order_open, $order_done, $order_gone, $order_theirs, $shipment ] as $order ) {
	wp_delete_post( $order->get_id(), true );
}

require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $mine );
wp_delete_user( $theirs );
wp_set_current_user( 0 );

$left = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->users} WHERE user_email LIKE 'pfh-acct-%@example.test'" );
ok( 'the shop is left as it was found', 0 === $left, "$left customers left behind" );

echo "\n$pass passed, $fail failed\n";
