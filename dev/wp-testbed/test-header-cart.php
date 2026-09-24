<?php
/**
 * What the header's cart icon opens.
 *
 * With "FunnelKit Cart" chosen the icon is still the header's own button,
 * and the drawer is still drawn: the script opens FunnelKit's slide cart
 * where the plugin put it on the page, and the drawer where it did not.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function header_el( array $settings ) {
	$e           = new PFH_Element_Header( [ 'id' => 'hd' . wp_rand( 1, 99999 ) ] );
	$e->settings = $settings;

	ob_start();
	$e->render();

	return (string) ob_get_clean();
}

function config_of( $html ) {
	if ( ! preg_match( '/data-pfh-config="([^"]+)"/', $html, $m ) ) {
		return [];
	}

	return (array) json_decode( html_entity_decode( $m[1], ENT_QUOTES ), true );
}

echo "── FunnelKit Cart chosen ──\n";

$html = header_el( [ 'showCart' => true, 'cartMode' => 'funnelkit' ] );
$cfg  = config_of( $html );

ok( 'the icon is the header\'s own button', false !== strpos( $html, 'pfh-actions__btn--cart" data-pfh-open="cart"' ) );
ok( 'the script is told which cart to open', isset( $cfg['cart']['mode'] ) && 'funnelkit' === $cfg['cart']['mode'], wp_json_encode( $cfg['cart'] ?? null ) );
ok( '  and that the cart is on', ! empty( $cfg['cart']['enabled'] ) );
ok( 'the drawer is still drawn, for pages FunnelKit leaves out', false !== strpos( $html, 'pfh-cart-' ) && false !== strpos( $html, 'data-pfh-cart-body' ) );

echo "\n── the other choices are as they were ──\n";

$drawer = header_el( [ 'showCart' => true, 'cartMode' => 'drawer' ] );
ok( 'the drawer: a button and the drawer', false !== strpos( $drawer, 'data-pfh-open="cart"' ) && false !== strpos( $drawer, 'data-pfh-cart-body' ) && 'drawer' === config_of( $drawer )['cart']['mode'] );

$link = header_el( [ 'showCart' => true, 'cartMode' => 'link' ] );
ok( 'the cart page: a link, no drawer', false !== strpos( $link, 'pfh-actions__btn--cart" href="' ) && false === strpos( $link, 'data-pfh-cart-body' ) && empty( config_of( $link )['cart']['enabled'] ) );

echo "\n── the script ──\n";

$js = file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/assets/js/pfh-header.js' );
ok( 'it opens FunnelKit with FunnelKit\'s own event', false !== strpos( $js, "trigger( 'fkcart_open' )" ) );
ok( '  only when FunnelKit\'s cart is on the page', false !== strpos( $js, "document.getElementById( 'fkcart-modal' )" ) && false !== strpos( $js, 'window.fkcart_app_data' ) );
ok( 'after our own add-to-cart it asks FunnelKit to refresh', false !== strpos( $js, "trigger( 'fkcart_update_side_cart'" ) );
ok( '  and opens by FunnelKit\'s own setting, never on the cart page', false !== strpos( $js, "'yes' === window.fkcart_app_data.should_open_cart && ! onCart" ) );

echo "\n$pass passed, $fail failed\n";
