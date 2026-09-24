<?php
/**
 * The recently viewed slider.
 *
 * Two things matter beyond "it renders": with nothing viewed it must never
 * show an empty rail or pass the best sellers off as history — it shows them
 * under their own heading, or, switched to "nothing", nothing at all — and a
 * visitor's history must never be able to end up in a cached page that
 * another visitor is then served.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-products.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-recent.php';

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function recent( array $over = [] ) {
	$el       = new PFH_Element_Recent( [ 'id' => 'r' . wp_rand( 1, 99999 ) ] );
	$el->name = 'pfh-recent';
	$el->set_control_groups();
	$el->set_controls();

	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; }
	}

	$el->settings = array_merge( $s, $over );

	ob_start();
	$el->render();

	return ob_get_clean();
}

$ids = get_posts( [ 'post_type' => 'product', 'numberposts' => 4, 'fields' => 'ids' ] );

echo "── nothing viewed: the best sellers, under their own heading ──\n";
unset( $_COOKIE['woocommerce_recently_viewed'] );
$html = recent( [ 'deferred' => false ] );
ok( 'a first visit gets products, not a gap', false !== strpos( $html, 'pfh-prod__card' ), substr( trim( $html ), 0, 80 ) );
ok( 'they are not passed off as history', false === strpos( $html, 'Recent' ) && false !== strpos( $html, 'populair' ) );

echo "── nothing viewed, switched to nothing ──\n";
$none = [ 'deferred' => false, 'fallback' => 'none' ];
$html = recent( $none );
ok( 'renders absolutely nothing', '' === trim( $html ), substr( trim( $html ), 0, 80 ) );
ok( 'no heading leaks out', false === strpos( $html, 'Recently' ) );
ok( 'no empty slider rail', false === strpos( $html, 'pfh-prod__track' ) );

echo "── an empty cookie is the same as no cookie ──\n";
$_COOKIE['woocommerce_recently_viewed'] = '';
ok( 'still nothing', '' === trim( recent( $none ) ) );

$_COOKIE['woocommerce_recently_viewed'] = '|||';
ok( 'a cookie of separators is still nothing', '' === trim( recent( $none ) ) );

$_COOKIE['woocommerce_recently_viewed'] = '999999';
ok( 'an id that is not a product is nothing', '' === trim( recent( $none ) ) );

echo "\n── with a history it shows those products, newest first ──\n";
$_COOKIE['woocommerce_recently_viewed'] = implode( '|', $ids );   // newest last, as Woo writes it
$html = recent( [ 'deferred' => false ] );

ok( 'it renders', false !== strpos( $html, 'pfh-prod__track' ) );
ok( 'one card per viewed product', count( $ids ) === substr_count( $html, 'pfh-prod__card' ), substr_count( $html, 'pfh-prod__card' ) . ' cards' );

foreach ( $ids as $id ) {
	ok( '  ' . get_the_title( $id ) . ' is there', false !== strpos( $html, esc_html( get_the_title( $id ) ) ) );
}

// Newest viewed is last in the cookie, so it must come first on the page.
preg_match_all( '/class="pfh-prod__name"[^>]*>\s*(?:<a[^>]*>)?([^<]+)/', $html, $m );
$order = array_map( 'trim', $m[1] );
ok( 'the newest viewed comes first', $order[0] === get_the_title( end( $ids ) ), 'first is ' . ( $order[0] ?? '?' ) );

ok( 'products never viewed are absent', false === strpos( $html, esc_html( get_the_title( get_posts( [ 'post_type' => 'product', 'numberposts' => 1, 'fields' => 'ids', 'exclude' => $ids ] )[0] ) ) ) );

echo "\n── it is the slider, not a copy of it ──\n";
ok( 'it extends the product slider', is_subclass_of( 'PFH_Element_Recent', 'PFH_Element_Products' ) );
ok( 'so it carries the slider card', false !== strpos( $html, 'pfh-prod__card' ) );
ok( 'and the drag behaviour', false !== strpos( $html, 'data-pfh-drag-slider' ) );

$el = new PFH_Element_Recent( [ 'id' => 'ctrl' ] );
$el->name = 'pfh-recent';
$el->set_control_groups();
$el->set_controls();
ok( 'the source is not offered — it is what this element is', ! isset( $el->controls['source'] ) );
ok( 'nor are the other sources\' settings', ! isset( $el->controls['productIds'] ) && ! isset( $el->controls['category'] ) );
ok( 'the heading defaults to the design', 'Recent <em>bekeken</em>' === $el->controls['heading']['default'] );
ok( 'but every style control is still there', isset( $el->controls['cardGap'] ) && isset( $el->controls['perView'] ) );

echo "\n── a cached page can never carry one visitor's history ──\n";
$_COOKIE['woocommerce_recently_viewed'] = implode( '|', $ids );
$html = recent();   // deferred is the default

ok( 'the markup is an empty placeholder', false !== strpos( $html, 'data-pfh-recent' ) );
ok( 'it is hidden until it has something', false !== strpos( $html, 'hidden' ) );
ok( 'no product name is in the page source', false === strpos( $html, esc_html( get_the_title( $ids[0] ) ) ), 'a history leaked into the markup' );
ok( 'no card markup either', false === strpos( $html, 'pfh-prod__card' ) );

echo "\n── the deferred render returns that visitor's own slider ──\n";
preg_match( '/data-pfh-recent="([^"]+)"/', $html, $m );
$uid = $m[1] ?? '';
ok( 'the placeholder names its stored settings', '' !== $uid );
ok( 'and those settings are stored', is_array( get_transient( 'pfh_recent_cfg_' . $uid ) ) );

echo "\n$pass passed, $fail failed\n";
