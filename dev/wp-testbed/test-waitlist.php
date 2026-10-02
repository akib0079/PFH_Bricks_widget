<?php
/**
 * The waitlist (PFH_Widgets_Waitlist): "Informeer wanneer beschikbaar" on a
 * sold-out product, the list in the dashboard, the email once it is back,
 * My Account, and bringing over the old shop's list (2026-10-02).
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$W = 'PFH_Widgets_Waitlist';
global $wpdb;

$W::maybe_install();
$table = $W::table();

// Whatever an interrupted run left behind.
foreach ( (array) $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_title LIKE 'Wachtlijst %'" ) as $stale ) {
	wp_delete_post( (int) $stale, true );
}

// Its own address, so the hourly limit is not shared with other runs.
$_SERVER['REMOTE_ADDR'] = '198.51.100.' . wp_rand( 1, 250 );
delete_transient( 'pfh_wl_rate_' . md5( $_SERVER['REMOTE_ADDR'] ) );
$wpdb->query( "DELETE FROM {$table} WHERE email LIKE '%@example.test'" );
delete_option( $W::OPTION );
$W::forget();

function wl_product( $name, $stock ) {
	$p = new WC_Product_Simple();
	$p->set_name( $name );
	$p->set_status( 'publish' );
	$p->set_regular_price( '12.95' );
	$p->set_stock_status( $stock );
	return $p->save();
}

function wl_pdp( $id, array $settings = [] ) {
	$el           = new PFH_Element_Product( [ 'id' => 'pdp' ] );
	$el->name     = 'pfh-product';
	$el->settings = array_merge( [ 'previewId' => (string) $id ], $settings );
	ob_start();
	$el->render();
	return (string) ob_get_clean();
}

function wl_bar( $id ) {
	$el           = new PFH_Element_Bottomcart( [ 'id' => 'bc' ] );
	$el->name     = 'pfh-bottomcart';
	$el->settings = [ 'productId' => (string) $id ];
	ob_start();
	$el->render();
	return (string) ob_get_clean();
}

function wl_ajax( callable $fn, array $post ) {
	$_POST = $_REQUEST = $post;
	add_filter( 'wp_doing_ajax', '__return_true' );
	ob_start();
	try { $fn(); } catch ( Exception $e ) {}
	$out = (string) ob_get_clean();
	remove_filter( 'wp_doing_ajax', '__return_true' );
	return json_decode( $out, true );
}

add_filter( 'wp_die_ajax_handler', function () { return function () { throw new Exception( 'done' ); }; } );

$sold = wl_product( 'Wachtlijst roerlepel', 'outofstock' );
$have = wl_product( 'Wachtlijst op voorraad', 'instock' );

echo "── on the product page ──\n";
$html = wl_pdp( $sold );
ok( 'a sold-out product offers the waitlist', false !== strpos( $html, 'data-pfh-wl-open' ) && false !== strpos( $html, 'Informeer wanneer beschikbaar' ) );
ok( '  instead of a dead cart button', false === strpos( $html, 'data-pfh-buy' ) && false === strpos( $html, 'Niet beschikbaar' ) );
ok( '  with the old shop\'s wording in the form', false !== strpos( $html, 'Momenteel uitverkocht' ) && false !== strpos( $html, 'Schrijf in op de wachtlijst' ) && false !== strpos( $html, 'update over de voorraad' ) );
ok( '  and a consent box that has to be ticked', (bool) preg_match( '/<input type="checkbox" name="consent" value="1" required/', $html ) );
$form_end = strpos( $html, '</form>' );
ok( 'its form is not inside the cart form', false !== $form_end && strpos( $html, 'data-pfh-wl-form' ) > $form_end, 'a form inside a form is dropped by the browser' );
ok( 'the sticky bar says the same as the button', (bool) preg_match( '/data-pfh-sticky-label>Informeer wanneer beschikbaar</', $html ) );
ok( 'the product goes with the sign-up', false !== strpos( $html, '<input type="hidden" name="product_id" value="' . $sold . '" />' ) );
ok( 'its script and styles come with it', wp_script_is( 'pfh-waitlist', 'enqueued' ) && wp_style_is( 'pfh-waitlist', 'enqueued' ) );

$in = wl_pdp( $have );
ok( 'a product in stock keeps its cart button', false !== strpos( $in, 'data-pfh-buy' ) && false === strpos( $in, 'data-pfh-wl' ) );

$twice = wl_pdp( $sold ) . wl_bar( $sold );
preg_match_all( '/id="(pfh-wl-[0-9-]+)"/', $twice, $m );
ok( 'two on one page get their own ids', count( $m[1] ) >= 2 && count( $m[1] ) === count( array_unique( $m[1] ) ) );
ok( 'the reminder at the bottom offers it too', false !== strpos( wl_bar( $sold ), 'data-pfh-wl-open' ) && false === strpos( wl_bar( $sold ), 'data-pfh-buy' ) );
ok( '  and keeps its button for a product in stock', false !== strpos( wl_bar( $have ), 'data-pfh-buy' ) );

update_option( $W::OPTION, [ 'text_button' => 'Mail mij', 'text_lede' => '' ] + $W::defaults() );
$W::forget();
$own = wl_pdp( $sold );
ok( 'the wording is set in the settings', false !== strpos( $own, 'Mail mij' ) && false === strpos( $own, 'pfh-wl__lede' ) );
update_option( $W::OPTION, [ 'text_button' => '   ' ] + $W::defaults() );
$W::forget();
ok( '  and a cleared button falls back to its own words', false !== strpos( wl_pdp( $sold ), '<span>Informeer wanneer beschikbaar</span>' ) );
update_option( $W::OPTION, [ 'enabled' => false ] + $W::defaults() );
$W::forget();
$off = wl_pdp( $sold );
ok( 'switched off, the page is as it was', false === strpos( $off, 'data-pfh-wl' ) && false !== strpos( $off, 'Niet beschikbaar' ) );
delete_option( $W::OPTION );
$W::forget();

echo "\n── signing up ──\n";
ok( 'a sign-up is saved', 'added' === $W::subscribe( $sold, 'Anna@Example.test' ) );
ok( '  once: the same address again is not a second row', 'exists' === $W::subscribe( $sold, 'anna@example.test' ) );
ok( '  stored in lower case', 1 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE email = %s", 'anna@example.test' ) ) );
ok( 'a wrong address is refused', 'bad-email' === $W::subscribe( $sold, 'anna@' ) );
ok( 'a product that does not exist is refused', 'no-product' === $W::subscribe( 999999, 'anna@example.test' ) );
$draft = wl_product( 'Wachtlijst concept', 'outofstock' );
wp_update_post( [ 'ID' => $draft, 'post_status' => 'draft' ] );
ok( 'and so is one that is not in the shop', 'no-product' === $W::subscribe( $draft, 'anna@example.test' ) );

$r = wl_ajax( [ $W, 'ajax_join' ], [ 'action' => $W::ACTION, 'product_id' => (string) $sold, 'email' => 'bas@example.test', 'consent' => '1', 'website' => '' ] );
ok( 'the form signs up without a page load', ! empty( $r['success'] ) && 'added' === $r['data']['state'] );
$r = wl_ajax( [ $W, 'ajax_join' ], [ 'action' => $W::ACTION, 'product_id' => (string) $sold, 'email' => 'bas@example.test', 'consent' => '1' ] );
ok( '  and says so when they were already on it', ! empty( $r['success'] ) && 'exists' === $r['data']['state'] );
$r = wl_ajax( [ $W, 'ajax_join' ], [ 'action' => $W::ACTION, 'product_id' => (string) $sold, 'email' => 'cor@example.test' ] );
ok( 'without the consent ticked nothing is saved', empty( $r['success'] ) && 'consent' === $r['data']['state'] && 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE email = 'cor@example.test'" ) );
$r = wl_ajax( [ $W, 'ajax_join' ], [ 'action' => $W::ACTION, 'product_id' => (string) $sold, 'email' => 'bot@example.test', 'consent' => '1', 'website' => 'http://spam' ] );
ok( 'a robot filling the hidden field is told yes and saved nowhere', ! empty( $r['success'] ) && 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE email = 'bot@example.test'" ) );
$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
delete_transient( 'pfh_wl_rate_' . md5( '203.0.113.9' ) );
for ( $i = 0; $i < 10; $i++ ) {
	wl_ajax( [ $W, 'ajax_join' ], [ 'action' => $W::ACTION, 'product_id' => (string) $sold, 'email' => "flood{$i}@example.test", 'consent' => '1' ] );
}
$r = wl_ajax( [ $W, 'ajax_join' ], [ 'action' => $W::ACTION, 'product_id' => (string) $sold, 'email' => 'flood10@example.test', 'consent' => '1' ] );
ok( 'one address cannot sign up endlessly', empty( $r['success'] ) && 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE email = 'flood10@example.test'" ) );
delete_transient( 'pfh_wl_rate_' . md5( '203.0.113.9' ) );
$wpdb->query( "DELETE FROM {$table} WHERE email LIKE 'flood%@example.test'" );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

echo "\n── back in stock ──\n";
ok( 'this testbed counts as a test site', $W::is_test_site() );
ok( '  so it mails nobody unless that is switched on', ! $W::may_mail() );
ok( 'Kinsta\'s staging copy is a test site, though it says "production"', $W::is_test_host( 'm01a032ada4d2735fbc629e14eb62edd.kinsta.cloud' ) );
ok( '  and so is a local copy', $W::is_test_host( '127.0.0.1' ) && $W::is_test_host( 'pfh.local' ) );
ok( '  but the real shop is not', ! $W::is_test_host( 'productsforhome.nl' ) && ! $W::is_test_host( 'www.productsforhome.nl' ) );
update_option( $W::OPTION, [ 'mail_off_production' => true ] + $W::defaults() );
$W::forget();
ok( '  and then it may', $W::may_mail() );
$mails = [];
add_filter( 'pre_wp_mail', function ( $null, $atts ) use ( &$mails ) { $mails[] = $atts; return true; }, 10, 2 );
ok( 'nothing is mailed while it is still sold out', 0 === $W::notify_product( $sold ) && ! $mails );

wp_clear_scheduled_hook( $W::CRON, [ $sold ] );
$p = wc_get_product( $sold );
$p->set_stock_status( 'instock' );
$p->save();
ok( 'back in stock, the email is lined up', (bool) wp_next_scheduled( $W::CRON, [ $sold ] ) );
wp_clear_scheduled_hook( $W::CRON, [ $have ] );
do_action( 'woocommerce_product_set_stock_status', $have, 'instock', wc_get_product( $have ) );
ok( '  but not for a product nobody waits for', ! wp_next_scheduled( $W::CRON, [ $have ] ) );

$sent = $W::notify_product( $sold );
ok( 'everyone on the list gets one email', 2 === $sent && 2 === count( $mails ), $sent . ' sent' );
ok( '  about the product, with a way to it', false !== strpos( $mails[0]['subject'], 'Wachtlijst roerlepel is weer op voorraad' ) && false !== strpos( $mails[0]['message'], get_permalink( $sold ) ) );
ok( '  and they are marked as mailed', 0 === $W::waiting( $sold ) );
$mails = [];
ok( 'a second time sends nothing', 0 === $W::notify_product( $sold ) && ! $mails );
ok( 'they may sign up again for the next time', 'added' === $W::subscribe( $sold, 'anna@example.test' ) );
wp_clear_scheduled_hook( $W::CRON, [ $sold ] );

update_option( $W::OPTION, [ 'auto' => false, 'mail_off_production' => true ] + $W::defaults() );
$W::forget();
$p = wc_get_product( $sold );
$p->set_stock_status( 'outofstock' );
$p->save();
$p->set_stock_status( 'instock' );
$p->save();
ok( 'with the automatic email off, nothing is lined up', ! wp_next_scheduled( $W::CRON, [ $sold ] ) );
delete_option( $W::OPTION );
$W::forget();

echo "\n── My Account ──\n";
$user = wp_insert_user( [ 'user_login' => 'wl_klant', 'user_pass' => wp_generate_password(), 'user_email' => 'anna@example.test' ] );
wp_set_current_user( $user );
$items = $W::account_item( [ 'dashboard' => 'Dashboard', 'orders' => 'Bestellingen', 'customer-logout' => 'Uitloggen' ] );
ok( 'a "Wachtlijst" tab, just above signing out', [ 'dashboard', 'orders', $W::ENDPOINT, 'customer-logout' ] === array_keys( $items ) );
ok( 'its own address in My Account', isset( $W::query_vars( [] )[ $W::ENDPOINT ] ) );
ok( 'the products waiting are found by the account\'s email', 1 === count( $W::for_user( $user ) ) );
ob_start();
$W::account_pane();
$pane = (string) ob_get_clean();
ok( '  listed with a link and a way off the list', false !== strpos( $pane, get_permalink( $sold ) ) && false !== strpos( $pane, 'data-pfh-wl-leave=' ) );
$row   = (int) $W::for_user( $user )[0]->id;
$other = (int) $wpdb->get_var( "SELECT id FROM {$table} WHERE email = 'bas@example.test' LIMIT 1" );
wl_ajax( [ $W, 'ajax_leave' ], [ 'action' => $W::LEAVE, 'id' => (string) $other, 'nonce' => wp_create_nonce( $W::LEAVE ) ] );
ok( 'nobody can take someone else off', 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE id = {$other}" ) );
wl_ajax( [ $W, 'ajax_leave' ], [ 'action' => $W::LEAVE, 'id' => (string) $row, 'nonce' => wp_create_nonce( $W::LEAVE ) ] );
ok( 'but they can take themselves off', 0 === count( $W::for_user( $user ) ) );
ob_start();
$W::account_pane();
ok( 'an empty list says so', false !== strpos( (string) ob_get_clean(), 'nergens op de wachtlijst' ) );
ok( 'the account element finds the tab by itself', has_action( 'woocommerce_account_' . $W::ENDPOINT . '_endpoint' ) );
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $user );
wp_set_current_user( 0 );

echo "\n── the old shop's list ──\n";
$rows = [
	[ '101', 'Dirk@Example.test', (string) $sold, '2025-11-03 09:15', '1', '1' ],
	[ '102', 'eva@example.test', (string) $have, '2026-02-14 18:02', '0', '0' ],
	[ '103', 'eva@example.test', (string) $have, '2026-03-01 10:00', '0', '0' ],
	[ '104', 'not-an-address', (string) $have, '2026-03-01 10:00', '0', '0' ],
	[ '105', 'fien@example.test', '0', '2026-03-01 10:00', '0', '0' ],
];
$res = $W::import_rows( $rows );
ok( 'usable rows come over, the rest is counted', 2 === $res['added'] && 3 === $res['skipped'], wp_json_encode( $res ) );
$dirk = $wpdb->get_row( "SELECT * FROM {$table} WHERE legacy_id = 'xstore:101'" );
ok( '  with their own date', $dirk && '2025-11-03 09:15:00' === $dirk->created_at );
ok( '  and those already mailed stay mailed', $dirk && null !== $dirk->notified_at && 'xstore' === $dirk->source );
ok( 'the same address twice on one product is one sign-up', 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE email = 'eva@example.test'" ) );
$again = $W::import_rows( $rows );
ok( 'bringing it over again adds nothing', 0 === $again['added'] );

delete_option( $W::IMPORT_KEY );
$r = wl_ajax( [ $W, 'ajax_remote_import' ], [ 'key' => 'guess', 'rows' => '[]' ] );
ok( 'no key, no import', empty( $r['success'] ) );
$key = $W::start_import();
$r   = wl_ajax( [ $W, 'ajax_remote_import' ], [ 'key' => 'wrong' . $key, 'rows' => '[]' ] );
ok( 'a wrong key is refused', empty( $r['success'] ) && 'key' === $r['data']['state'] );
$r = wl_ajax( [ $W, 'ajax_remote_import' ], [ 'key' => $key, 'rows' => wp_json_encode( [ [ '201', 'gijs@example.test', (string) $have, '2026-04-01 12:00', '0', '0' ] ] ) ] );
ok( 'the right key brings the list over', ! empty( $r['success'] ) && 1 === $r['data']['added'] );
ok( '  once: the key is gone after', false === get_option( $W::IMPORT_KEY ) );
$r = wl_ajax( [ $W, 'ajax_remote_import' ], [ 'key' => $key, 'rows' => '[]' ] );
ok( '  and a second post with it is refused', empty( $r['success'] ) && 'key' === $r['data']['state'] );
$key = $W::start_import();
update_option( $W::IMPORT_KEY, [ 'key' => $key, 'expires' => time() - 5 ] );
$r = wl_ajax( [ $W, 'ajax_remote_import' ], [ 'key' => $key, 'rows' => '[]' ] );
ok( 'a key older than an hour is refused', empty( $r['success'] ) );
delete_option( $W::IMPORT_KEY );

// Two imports that overlapped leave each old row twice; the repair keeps one.
$wpdb->insert( $table, [ 'product_id' => $have, 'email' => 'gijs@example.test', 'created_at' => '2026-04-01 12:00:00', 'source' => 'xstore', 'legacy_id' => 'xstore:201' ] );
$wpdb->insert( $table, [ 'product_id' => $sold, 'email' => 'dirk@example.test', 'created_at' => '2025-11-03 09:15:00', 'notified_at' => '2025-11-03 09:15:00', 'source' => 'xstore', 'legacy_id' => 'xstore:101' ] );
$wpdb->insert( $table, [ 'product_id' => $have, 'email' => 'hans@example.test', 'created_at' => '2026-05-01 12:00:00', 'source' => 'site' ] );
$wpdb->insert( $table, [ 'product_id' => $have, 'email' => 'hans@example.test', 'created_at' => '2026-05-02 12:00:00', 'source' => 'site' ] );
$W::repair();
ok( 'a doubled import is repaired to one row each', 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE legacy_id = 'xstore:201'" ) && 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE legacy_id = 'xstore:101'" ) );
ok( '  and one open sign-up per person per product', 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE email = 'hans@example.test'" ) );
$wpdb->query( "DELETE FROM {$table} WHERE email = 'hans@example.test'" );

echo "\n── the dashboard list ──\n";
$c = $W::counts();
ok( 'the counts add up', 2 === $c['waiting'] && 1 === $c['products'] && 3 === $c['mailed'], wp_json_encode( $c ) );
ok( 'a cell a spreadsheet would run is made text', "'=HYPERLINK(1)" === PFH_Widgets_Waitlist_Admin::csv_cell( '=HYPERLINK(1)' ) && 'eva@example.test' === PFH_Widgets_Waitlist_Admin::csv_cell( 'eva@example.test' ) );
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
PFH_Widgets_Waitlist_Admin::load_table();
if ( class_exists( 'PFH_Widgets_Waitlist_Table' ) ) {
	set_current_screen( 'woocommerce_page_pfh-waitlist' );
	$_GET = $_REQUEST = [ 'page' => 'pfh-waitlist', 's' => 'gijs@' ];
	$t    = new PFH_Widgets_Waitlist_Table();
	$t->prepare_items();
	ok( 'searching by email finds the sign-up', 1 === count( $t->items ) && 'gijs@example.test' === $t->items[0]->email );
	$_GET = $_REQUEST = [ 'page' => 'pfh-waitlist', 's' => 'roerlepel' ];
	$t    = new PFH_Widgets_Waitlist_Table();
	$t->prepare_items();
	ok( '  and by product name', count( $t->items ) >= 1 && (int) $t->items[0]->product_id === $sold );
	$_GET = $_REQUEST = [ 'page' => 'pfh-waitlist', 'status' => 'mailed' ];
	$t    = new PFH_Widgets_Waitlist_Table();
	$t->prepare_items();
	ok( 'the "Mailed" view shows only those', $t->items && ! array_filter( $t->items, function ( $i ) { return null === $i->notified_at; } ) );
	$_GET = $_REQUEST = [];
} else {
	ok( 'the list table loads', false );
}

// Leave the catalogue as it was.
$wpdb->query( "DELETE FROM {$table} WHERE email LIKE '%@example.test'" );
foreach ( [ $sold, $have, $draft ] as $id ) {
	wp_clear_scheduled_hook( $W::CRON, [ $id ] );
	wp_delete_post( $id, true );
}
delete_option( 'pfh_waitlist_last_import' );

echo "\n$pass passed, $fail failed\n";
