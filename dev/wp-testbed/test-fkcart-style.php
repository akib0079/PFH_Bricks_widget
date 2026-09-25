<?php
/**
 * FunnelKit Cart, restyled.
 *
 * The sheet loads only where FunnelKit's own cart sheet is on the page, loads
 * after it, can be switched off, and outranks FunnelKit's selectors without
 * reaching for !important.
 */
require __DIR__ . '/wp-load.php';

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$reset = static function () {
	wp_dequeue_style( 'pfh-fkcart' );
	wp_dequeue_style( 'fkcart-style' );
	wp_deregister_style( 'fkcart-style' );
};

echo "── without FunnelKit ──\n";

$reset();
PFH_Widgets_Assets::maybe_fkcart();
ok( 'nothing is added when FunnelKit has no cart on the page', ! wp_style_is( 'pfh-fkcart', 'enqueued' ) );

wp_register_style( 'fkcart-style', 'https://example.test/fkcart.css', [], '1' );
PFH_Widgets_Assets::maybe_fkcart();
ok( '  nor when FunnelKit only registered its sheet', ! wp_style_is( 'pfh-fkcart', 'enqueued' ) );

echo "\n── with FunnelKit's cart on the page ──\n";

wp_enqueue_style( 'fkcart-style' );
PFH_Widgets_Assets::maybe_fkcart();
ok( 'the brand sheet is enqueued', wp_style_is( 'pfh-fkcart', 'enqueued' ) );

$dep = wp_styles()->registered['pfh-fkcart'] ?? null;
ok( '  after FunnelKit\'s own sheet', $dep && in_array( 'fkcart-style', $dep->deps, true ) );
ok( '  from this plugin, versioned', $dep && false !== strpos( $dep->src, 'assets/css/pfh-fkcart.css' ) && PFH_WIDGETS_VERSION === $dep->ver );
ok( '  with the brand font', ! wp_style_is( 'pfh-font-outfit', 'registered' ) || in_array( 'pfh-font-outfit', $dep->deps, true ) );

PFH_Widgets_Assets::maybe_fkcart();
ok( 'a second call (the footer pass) is harmless', 1 === count( array_keys( wp_styles()->queue, 'pfh-fkcart', true ) ) );

echo "\n── switched off ──\n";

wp_dequeue_style( 'pfh-fkcart' );
add_filter( 'pfh_widgets_fkcart_style', '__return_false' );
PFH_Widgets_Assets::maybe_fkcart();
ok( 'pfh_widgets_fkcart_style=false keeps FunnelKit\'s own look', ! wp_style_is( 'pfh-fkcart', 'enqueued' ) );
remove_filter( 'pfh_widgets_fkcart_style', '__return_false' );

ok( 'it is hooked on the front end', false !== has_action( 'wp_enqueue_scripts', [ 'PFH_Widgets_Assets', 'maybe_fkcart' ] ) && false !== has_action( 'wp_footer', [ 'PFH_Widgets_Assets', 'maybe_fkcart' ] ) );

$reset();

echo "\n── the sheet ──\n";

$css  = file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/assets/css/pfh-fkcart.css' );
$bare = preg_replace( '#/\*.*?\*/#s', '', $css );

ok( 'braces balance', substr_count( $bare, '{' ) === substr_count( $bare, '}' ) );

// Every rule that reaches inside the cart names the id twice, so it outranks
// FunnelKit's "#fkcart-modal .a .b .c" and "#fkcart-modal #fkcart-coupon__input".
preg_match_all( '/([^{}]+)\{/', $bare, $m );
$weak = [];
foreach ( $m[1] as $sel ) {
	foreach ( array_map( 'trim', explode( ',', $sel ) ) as $one ) {
		if ( '' === $one || '@' === $one[0] || preg_match( '/^(from|to|\d+%)$/', $one ) || '#fkcart-modal' === $one ) {
			continue;
		}
		if ( 0 !== strpos( $one, '#fkcart-modal#fkcart-modal' ) ) {
			$weak[] = $one;
		}
	}
}
ok( 'every selector is scoped to the doubled cart id', ! $weak, implode( ' | ', array_slice( $weak, 0, 3 ) ) );

$imp = preg_match_all( '/!important/', $bare );
ok( '!important only in the reduced-motion switch', 2 === $imp, (string) $imp );
ok( 'FunnelKit\'s palette is pointed at the brand', false !== strpos( $bare, '--fkcart-primary-bg-color: var(--pfh-fk-accent)' ) );
ok( 'the brand fonts are used', false !== strpos( $bare, 'Outfit' ) && false !== strpos( $bare, '"Playfair Display"' ) );
ok( 'motion is switched off for those who ask', false !== strpos( $bare, 'prefers-reduced-motion: reduce' ) );
ok( 'the coupon field keeps 16px on phones (no iOS zoom)', (bool) preg_match( '/max-width: 767px\)[^@]*#fkcart-coupon__input \{ font-size: 16px; \}/s', $bare ) );

ok( 'the primary is the sage #6d8465', false !== strpos( $bare, '--pfh-fk-accent: #6d8465' ) );
ok( '  with a deeper sage for small text', false !== strpos( $bare, '--pfh-fk-accent-ink: #56704f' ) );

// The phone carousel: FunnelKit's own breakpoint, and its slide geometry left alone.
$phone = strpos( $bare, '@media screen and (max-width: 1000px)' );
ok( 'the phone slider rules sit under FunnelKit\'s 1000px breakpoint', false !== $phone );
$carousel = false === $phone ? '' : substr( $bare, $phone, strpos( $bare, "\n}\n", $phone ) - $phone );
ok( '  the card is drawn inside the 15px slide gutter', false !== strpos( $carousel, 'inset: 0 0 0 15px' ) && false !== strpos( $carousel, 'padding: 12px 12px 12px 27px' ) );
ok( '  the track is not padded (that made the next card peek in)', false !== strpos( $carousel, '.fkcart-item-wrap { padding: 0; }' ) );
ok( '  no slide width or flex-basis is overridden', ! preg_match( '/\.fkcart--item(:hover)?\s*(,[^{]*)?\{[^}]*(?<![-\w])(flex|width|flex-basis):/', $carousel ) );
ok( '  the price keeps its width on a phone', (bool) preg_match( '/fkcart-item-misc \{[^}]*flex: none/', $carousel ) );
ok( 'no carousel rule leaks outside that breakpoint', 1 === substr_count( $bare, '.fkcart-slider-body .fkcart-drawer-upsells .fkcart-drawer-container' ) && false !== strpos( $carousel, '.fkcart-slider-body .fkcart-drawer-upsells .fkcart-drawer-container' ) );

// The empty cart's "Nu winkelen" button also carries .fkcart-modal-close.
ok( 'the close-button disc is only the header\'s ×', ! preg_match( '/#fkcart-modal#fkcart-modal \.fkcart-modal-close\s*\{/', $bare ) );
ok( 'the empty cart button is a pill with white text', (bool) preg_match( '/\.fkcart-zero-state \.fkcart-shop-button \{[^}]*min-width: 200px[^}]*color: #ffffff/s', $bare ) );

echo "\n$pass passed, $fail failed\n";
