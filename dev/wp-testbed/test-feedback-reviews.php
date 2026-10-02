<?php
/**
 * Product reviews where they belong, and the cart drawer's shipping bar
 * (client feedback #1003055, #1003294; 2026-10-02).
 *
 * A product's own reviews: counted on its cards, linked to a Reviews tab on
 * its page, listed there. The shipping bar: a message-only module that loads
 * its script where FunnelKit's drawer is, with the threshold and wording
 * from its settings.
 */
require __DIR__ . '/wp-load.php';
foreach ( [ 'products', 'product-tabs' ] as $f ) {
	require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-' . $f . '.php';
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function render_el( $class, $name, array $over = [] ) {
	$el       = new $class( [ 'id' => 'rv' . wp_rand( 1, 99999 ) ] );
	$el->name = $name;
	$el->set_control_groups();
	$el->set_controls();
	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; }
	}
	$el->settings = array_merge( $s, $over );
	ob_start();
	$el->render();
	return (string) ob_get_clean();
}

foreach ( get_posts( [ 'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'title' => 'PFH review test' ] ) as $old ) {
	wp_delete_post( $old, true );
}

$p = new WC_Product_Simple();
$p->set_name( 'PFH review test' );
$p->set_status( 'publish' );
$p->set_regular_price( '12' );
$p->set_reviews_allowed( true );
$pid = $p->save();

$bare = new WC_Product_Simple();
$bare->set_name( 'PFH review test' );
$bare->set_status( 'publish' );
$bare->set_regular_price( '9' );
$bare_id = $bare->save();

foreach ( [ [ 'Anna', 5, 'Heerlijk, snel geleverd.' ], [ 'Bram', 4, 'Lekker fris.' ] ] as $i => $r ) {
	$cid = wp_insert_comment(
		[
			'comment_post_ID'  => $pid,
			'comment_author'   => $r[0],
			'comment_content'  => $r[2],
			'comment_type'     => 'review',
			'comment_approved' => 1,
			'comment_date'     => gmdate( 'Y-m-d H:i:s', time() - ( $i + 1 ) * DAY_IN_SECONDS ),
		]
	);
	update_comment_meta( $cid, 'rating', $r[1] );
}
// One waiting for moderation: never shown.
$held = wp_insert_comment( [ 'comment_post_ID' => $pid, 'comment_author' => 'Spam', 'comment_content' => 'Niet zichtbaar', 'comment_type' => 'review', 'comment_approved' => 0 ] );
update_comment_meta( $held, 'rating', 1 );
WC_Comments::clear_transients( $pid );
$p = wc_get_product( $pid );

echo "── the card ──\n";
ok( 'the product counts its approved reviews', 2 === (int) $p->get_review_count(), (string) $p->get_review_count() );
$html = render_el( 'PFH_Element_Products', 'pfh-products', [ 'source' => 'ids', 'productIds' => (string) $pid ] );
$card = '';
if ( preg_match( '#<li class="pfh-prod__item">(?:(?!</li>).)*?' . preg_quote( '?p=' . $pid, '#' ) . '|<li class="pfh-prod__item">(?:(?!</li>).)*PFH review test(?:(?!</li>).)*</li>#s', $html, $m ) ) {
	$card = $m[0];
}
ok( 'the card shows "2 reviews"', false !== strpos( $html, '2 reviews' ) );
ok( 'and links them to the product\'s Reviews tab', (bool) preg_match( '#class="pfh-prod__reviews" href="[^"]*' . preg_quote( (string) wp_parse_url( get_permalink( $pid ), PHP_URL_PATH ) ?: '', '#' ) . '[^"]*\#reviews"#', $html ) || false !== strpos( $html, esc_url( get_permalink( $pid ) . '#reviews' ) ), substr( $html, (int) strpos( $html, 'pfh-prod__reviews' ) - 10, 200 ) );
ok( 'not to the WebwinkelKeur page in a new tab', false === strpos( $html, 'webwinkelkeur.nl' ) );

$none = render_el( 'PFH_Element_Products', 'pfh-products', [ 'source' => 'ids', 'productIds' => (string) $bare_id ] );
ok( 'a product without reviews shows no review row', false === strpos( $none, 'pfh-prod__reviews' ) );

echo "\n── the Reviews tab ──\n";
$tabs = render_el( 'PFH_Element_Product_Tabs', 'pfh-product-tabs', [ 'previewId' => (string) $pid ] );
ok( 'a Reviews tab with the count', false !== strpos( $tabs, '>Reviews (2)<' ) );
ok( 'the anchor a card links to', false !== strpos( $tabs, 'id="reviews"' ) );
ok( 'each review: name, text, stars', false !== strpos( $tabs, 'Anna' ) && false !== strpos( $tabs, 'Lekker fris.' ) && false !== strpos( $tabs, 'pfh-tabs__stars' ) );
ok( 'newest first', strpos( $tabs, 'Anna' ) < strpos( $tabs, 'Bram' ) );
ok( 'a review awaiting moderation is not shown', false === strpos( $tabs, 'Niet zichtbaar' ) );
ok( 'the average', false !== strpos( $tabs, number_format_i18n( 4.5, 1 ) . ' van 5' ) );
$bare_tabs = render_el( 'PFH_Element_Product_Tabs', 'pfh-product-tabs', [ 'previewId' => (string) $bare_id ] );
ok( 'no reviews, no tab', false === strpos( $bare_tabs, 'Reviews (' ) && false === strpos( $bare_tabs, 'id="reviews"' ) );
$off = render_el( 'PFH_Element_Product_Tabs', 'pfh-product-tabs', [ 'previewId' => (string) $pid, 'reviewsOn' => false ] );
ok( 'and the tab can be switched off', false === strpos( $off, 'Reviews (' ) );

echo "\n── the shipping bar ──\n";
delete_option( PFH_Widgets_Shipbar::OPTION );
PFH_Widgets_Shipbar::forget();
$cfg = PFH_Widgets_Shipbar::config();
ok( 'free from €60 unless set', 60.0 === (float) $cfg['threshold'] );
ok( 'with the shop\'s separators', ',' === $cfg['decimal'] || '.' === $cfg['decimal'] );
ok( 'and Dutch wording with the amount in it', false !== strpos( $cfg['left'], '%amount%' ) );
ok( 'a "Winkelwagen" tab on the settings screen', isset( PFH_Widgets_Settings::tabs()['cart'] ) );

global $wp_scripts, $wp_styles;
wp_dequeue_script( 'pfh-shipbar' );
PFH_Widgets_Shipbar::assets();
ok( 'no FunnelKit drawer on the page, no script', ! wp_script_is( 'pfh-shipbar', 'enqueued' ) );
wp_register_style( 'fkcart-style', false, [], '1' );
wp_enqueue_style( 'fkcart-style' );
PFH_Widgets_Shipbar::assets();
ok( 'with the drawer, the script', wp_script_is( 'pfh-shipbar', 'enqueued' ) );
$inline = implode( '', (array) $wp_scripts->get_data( 'pfh-shipbar', 'before' ) );
ok( 'and its settings', false !== strpos( $inline, 'window.pfhShipbar' ) && false !== strpos( $inline, '"threshold":60' ) );

wp_dequeue_script( 'pfh-shipbar' );
PFH_Widgets_Shipbar::update( [ 'enabled' => '', 'threshold' => '70' ] );
PFH_Widgets_Shipbar::assets();
ok( 'switched off, nothing loads', ! wp_script_is( 'pfh-shipbar', 'enqueued' ) );
ok( 'the threshold is stored as a number', 70 === (int) PFH_Widgets_Shipbar::get( 'threshold' ) );
delete_option( PFH_Widgets_Shipbar::OPTION );
PFH_Widgets_Shipbar::forget();

/* ---- clean up ---- */
wp_delete_post( $pid, true );
wp_delete_post( $bare_id, true );

echo "\n$pass passed, $fail failed\n";
