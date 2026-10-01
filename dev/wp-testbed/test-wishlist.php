<?php
/**
 * The wishlist: a heart on every product, kept in a cookie for a visitor and
 * on the account for a customer, and folded together when they sign in.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-products.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$ids = get_posts( [ 'post_type' => 'product', 'numberposts' => 3, 'fields' => 'ids' ] );

echo "── the heart ──\n";
$b = PFH_Widgets_Wishlist::button( $ids[0], 'pfh-wish--card' );
ok( 'is a button carrying the product', false !== strpos( $b, '<button type="button" class="pfh-wish pfh-wish--card"' ) && false !== strpos( $b, 'data-pfh-wish="' . $ids[0] . '"' ) );
ok( 'says what it does, in Dutch', false !== strpos( $b, 'Bewaar in je verlanglijst' ) );
ok( 'and is not pressed for a visitor until the script reads the cookie', false !== strpos( $b, 'aria-pressed="false"' ) );

$el       = new PFH_Element_Products( [ 'id' => 'wl' ] );
$el->name = 'pfh-products';
$el->set_control_groups();
$el->set_controls();
$s = [];
foreach ( $el->controls as $k => $c ) { if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; } }
$el->settings = array_merge( $s, [ 'source' => 'recent', 'limit' => 3 ] );
ob_start(); $el->render(); $html = ob_get_clean();
ok( 'every product card has one', substr_count( $html, 'data-pfh-wish=' ) === substr_count( $html, 'class="pfh-prod__item"' ) && substr_count( $html, 'data-pfh-wish=' ) > 0 );
$el->settings['wishlist'] = false;
ob_start(); $el->render(); $off = ob_get_clean();
ok( 'unless switched off', false === strpos( $off, 'data-pfh-wish=' ) );

echo "\n── a customer ──\n";
$user = wp_insert_user( [ 'user_login' => 'wl' . wp_rand(), 'user_pass' => wp_generate_password(), 'user_email' => 'wl' . wp_rand() . '@example.test' ] );
wp_set_current_user( $user );

$_COOKIE[ PFH_Widgets_Wishlist::COOKIE ] = $ids[1] . '.' . $ids[2] . '.junk.' . $ids[1];
PFH_Widgets_Wishlist::merge_on_login( 'x', get_user_by( 'id', $user ) );
$stored = get_user_meta( $user, PFH_Widgets_Wishlist::META, true );
ok( 'signing in keeps what was saved as a visitor', [ $ids[1], $ids[2] ] === array_map( 'intval', (array) $stored ), wp_json_encode( $stored ) );
unset( $_COOKIE[ PFH_Widgets_Wishlist::COOKIE ] );

ok( 'the heart shows it saved', false !== strpos( PFH_Widgets_Wishlist::button( $ids[1] ), 'aria-pressed="true"' ) );
$list = PFH_Widgets_Wishlist::render_list();
ok( 'the list shows the saved products', 2 === substr_count( $list, 'data-pfh-wish-item=' ) );
ok( '  each with a heart to take it off', 2 === substr_count( $list, 'pfh-wish--inline' ) );

// The AJAX save, as the script sends it.
add_filter( 'wp_die_ajax_handler', function () { return function () { throw new Exception( 'done' ); }; } );
add_filter( 'wp_doing_ajax', '__return_true' );
$_POST = $_REQUEST = [ 'action' => PFH_Widgets_Wishlist::ACTION, 'nonce' => wp_create_nonce( PFH_Widgets_Wishlist::ACTION ), 'id' => (string) $ids[0], 'on' => '1' ];
ob_start();
try { PFH_Widgets_Wishlist::ajax(); } catch ( Exception $e ) {}
ob_end_clean();
ok( 'saving from the heart puts it first on the account', $ids[0] === (int) get_user_meta( $user, PFH_Widgets_Wishlist::META, true )[0] );
$_POST['on'] = '';
$_REQUEST    = $_POST;
ob_start();
try { PFH_Widgets_Wishlist::ajax(); } catch ( Exception $e ) {}
ob_end_clean();
ok( 'and taking it off removes it', ! in_array( $ids[0], array_map( 'intval', (array) get_user_meta( $user, PFH_Widgets_Wishlist::META, true ) ), true ) );

remove_filter( 'wp_doing_ajax', '__return_true' );
delete_user_meta( $user, PFH_Widgets_Wishlist::META );
ok( 'an empty list says how to fill it', false !== strpos( PFH_Widgets_Wishlist::render_list(), 'Tik op het hartje' ) );

require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $user );
wp_set_current_user( 0 );
$_POST = $_REQUEST = [];

echo "\n$pass passed, $fail failed\n";
