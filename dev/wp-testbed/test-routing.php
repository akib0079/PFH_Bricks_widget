<?php
/**
 * Activating the plugin must not change how a single URL resolves.
 *
 * The permalink manager used to ship switched on — product_mode 'slug',
 * category_mode 'full_path' — so the moment the plugin was activated it took
 * over routing for the whole store, before anyone had opened its settings.
 * That is what this guards.
 */
require __DIR__ . '/wp-load.php';

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

echo "── a fresh activation rewrites nothing ──\n";
$saved = get_option( 'pfh_permalinks' );
delete_option( 'pfh_permalinks' );
wp_cache_flush();
PFH_Widgets_Permalinks::forget();

ok( 'product mode is "use WooCommerce settings"', 'default' === PFH_Widgets_Permalinks::get( 'product_mode', 'default' ) );
ok( 'category mode is "use WooCommerce settings"', 'default' === PFH_Widgets_Permalinks::get( 'category_mode', 'default' ) );
ok( 'the resolver is not active at all', false === PFH_Widgets_Permalinks::active() );
ok( 'parse_request carries no resolver', false === has_action( 'parse_request', [ 'PFH_Widgets_Permalinks', 'resolve' ] ) );

echo "\n── a site that already saved the old settings is neutralised too ──\n";
/*
 * Moving the per-mode defaults was not enough on its own: an install that had
 * ever opened this screen and pressed Save carries the old modes in the
 * option, so the manager stayed live there whatever the defaults said. This
 * is the case the client was actually in.
 */
update_option( 'pfh_permalinks', [ 'product_mode' => 'slug', 'category_mode' => 'full_path' ] );
wp_cache_flush();
PFH_Widgets_Permalinks::forget();

ok( 'stored modes are still readable', 'slug' === PFH_Widgets_Permalinks::get( 'product_mode', 'default' ) );
ok( 'but the manager is OFF without the master switch', false === PFH_Widgets_Permalinks::active() );

update_option( 'pfh_permalinks', [ 'enabled' => true, 'product_mode' => 'slug', 'category_mode' => 'full_path' ] );
wp_cache_flush();
PFH_Widgets_Permalinks::forget();
ok( 'and ON once it is switched on deliberately', true === PFH_Widgets_Permalinks::active() );

echo "\n── a legacy redirect is never cached permanently ──\n";
ok( 'permanent redirects are off by default', false === PFH_Widgets_Permalinks::get( 'permanent_redirect', false ) );

echo "\n── the shop page stays the shop page ──\n";
$shop = wc_get_page_id( 'shop' );
ok( 'a shop page exists', $shop > 0 );

// Resolve /shop/ the way a request does.
$url = wc_get_page_permalink( 'shop' );
$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
$_SERVER['REQUEST_URI'] = '/' . $path . '/';

$q = new WP_Query( [ 'post_type' => 'product', 'posts_per_page' => 5 ] );
ok( 'the shop query finds products', $q->found_posts > 0, $q->found_posts . ' found' );
wp_reset_postdata();

echo "\n── with the resolver on, reserved pages are still untouchable ──\n";
update_option( 'pfh_permalinks', [ 'enabled' => true, 'product_mode' => 'slug', 'category_mode' => 'full_path' ] );
wp_cache_flush();
PFH_Widgets_Permalinks::forget();
ok( 'the resolver is now active', true === PFH_Widgets_Permalinks::active() );

$ref = new ReflectionMethod( 'PFH_Widgets_Permalinks', 'already_resolved' );
$ref->setAccessible( true );

// The shop archive arrives as nothing but a post type.
ok( 'a bare post type archive counts as resolved', true === $ref->invoke( null, [ 'post_type' => 'product' ] ) );
ok( 'a real product request is still ours to canonicalise', true === $ref->invoke( null, [ 'name' => 'x', 'post_type' => 'product' ] ) );

$reserved = new ReflectionMethod( 'PFH_Widgets_Permalinks', 'is_reserved_path' );
$reserved->setAccessible( true );

foreach ( [ 'shop', 'cart', 'checkout' ] as $page ) {
	$id = wc_get_page_id( $page );
	if ( $id <= 0 ) { continue; }
	$uri = trim( get_page_uri( $id ), '/' );
	ok( "$uri is reserved", true === $reserved->invoke( null, $uri ) );
	ok( "$uri/page/2 is reserved too", true === $reserved->invoke( null, $uri . '/page/2' ) );
}

ok( 'the front page is reserved', true === $reserved->invoke( null, '' ) );
ok( 'an ordinary category path is NOT reserved', false === $reserved->invoke( null, 'honing' ) );
ok( 'a path that merely starts with the same letters is NOT reserved', false === $reserved->invoke( null, 'shopping-tas' ) );

/*
 * The guard takes the path WordPress parsed, not REQUEST_URI. On an install
 * in a subdirectory those differ — REQUEST_URI carries /store/ in front —
 * and comparing the wrong one left the shop page unprotected there.
 */
$_SERVER['REQUEST_URI'] = '/store/shop/';
ok( 'a subdirectory install still reserves its shop page', true === $reserved->invoke( null, 'shop' ) );
ok( 'and REQUEST_URI does not decide it', false === $reserved->invoke( null, 'honing' ) );

// Put the site back exactly as it was.
if ( false === $saved ) { delete_option( 'pfh_permalinks' ); } else { update_option( 'pfh_permalinks', $saved ); }
wp_cache_flush();
PFH_Widgets_Permalinks::forget();

echo "\n$pass passed, $fail failed\n";
