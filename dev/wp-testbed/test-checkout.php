<?php
/**
 * The two elements beside the checkout.
 *
 * FunnelKit's own checkout is not tested here — it is FunnelKit's. What is
 * tested is what these add: real reviews when WebwinkelKeur answers, the
 * typed ones when it does not, and the live review count in the trust list
 * without touching the other numbers written there.
 *
 * WebwinkelKeur itself is stood in for with the review service's own
 * short-circuit filters, so nothing here calls the real API.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function el( $class, array $settings = [] ) {
	$e           = new $class( [ 'id' => 'ck' . wp_rand( 1, 99999 ) ] );
	$e->settings = $settings;

	ob_start();
	$e->render();

	return (string) ob_get_clean();
}

$feed = [
	[ 'id' => '1', 'name' => 'Marieke', 'city' => 'Utrecht', 'text' => 'Heerlijke honing, snel geleverd en netjes verpakt.', 'date' => gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ), 'rating10' => 10.0, 'url' => '' ],
	[ 'id' => '2', 'name' => 'Pieter', 'city' => '', 'text' => str_repeat( 'Echt een aanrader voor iedereen die van Griekse producten houdt. ', 8 ), 'date' => '2025-01-10 10:00:00', 'rating10' => 9.0, 'url' => '' ],
	[ 'id' => '3', 'name' => 'Karel', 'city' => '', 'text' => 'Het pakket kwam beschadigd aan.', 'date' => gmdate( 'Y-m-d H:i:s' ), 'rating10' => 4.0, 'url' => '' ],
	[ 'id' => '4', 'name' => 'Sanne', 'city' => '', 'text' => '', 'date' => gmdate( 'Y-m-d H:i:s' ), 'rating10' => 10.0, 'url' => '' ],
];

$live_feed = static function () use ( $feed ) {
	return $feed;
};
$live_summary = static function () {
	return [ 'rating' => 9.7, 'count' => 396, 'scale' => 10 ];
};

add_filter( 'pfh_webwinkelkeur_pre_reviews', $live_feed );
add_filter( 'pfh_webwinkelkeur_pre_summary', $live_summary );

echo "── real reviews ──\n";

$html = el( 'PFH_Element_Checkout_Reviews' );

ok( 'a real review is shown', false !== strpos( $html, 'Heerlijke honing, snel geleverd' ) );
ok( '  with the reviewer\'s name and town', false !== strpos( $html, '>Marieke<' ) && false !== strpos( $html, 'Utrecht' ) );
ok( '  and how recently they wrote it', false !== strpos( $html, '3 dagen geleden' ) );
ok( 'a low rating is not chosen for the column', false === strpos( $html, 'beschadigd' ) );
ok( 'a rating without words is not a review to show', false === strpos( $html, '>Sanne<' ) );
ok( 'a long review is cut at a word', (bool) preg_match( '/houdt\.?…|[a-z]…/u', $html ) && substr_count( $html, 'Echt een aanrader' ) < 8 );
ok( 'the typed testimonial is not shown beside real ones', false === strpos( $html, 'Jessica' ) );
ok( 'the stars say the rating out loud', false !== strpos( $html, 'aria-label="' . esc_attr( sprintf( __( '%s out of 5 stars', 'pfh-widgets' ), number_format_i18n( 5, 1 ) ) ) . '"' ) );

echo "\n── the shop's score ──\n";

ok( 'the live review count is in the score line', false !== strpos( $html, '396 beoordelingen' ) );
ok( '  and it links to the reviews', false !== strpos( $html, 'class="pfh-ckrev__score" href="' ) && false !== strpos( $html, 'target="_blank"' ) );
ok( 'the line can be switched off', false === strpos( el( 'PFH_Element_Checkout_Reviews', [ 'scoreLine' => '' ] ), 'pfh-ckrev__score' ) );

echo "\n── turning over ──\n";

ok( 'more than one review turns over at the set pace', false !== strpos( $html, 'data-pfh-ckrev="7000"' ) );
ok( '  one shows, the rest wait', 1 === substr_count( $html, 'pfh-ckrev__card is-active' ) && false !== strpos( $html, 'aria-hidden="true"' ) );
ok( '  with a dot for each', 2 === substr_count( $html, 'data-pfh-ckrev-to=' ) );
ok( 'set to 0, it stays put', false !== strpos( el( 'PFH_Element_Checkout_Reviews', [ 'interval' => 0 ] ), 'data-pfh-ckrev="0"' ) );
ok( 'the minimum rating can be lowered', false !== strpos( el( 'PFH_Element_Checkout_Reviews', [ 'minRating' => 1 ] ), 'beschadigd' ) );

echo "\n── without the feed ──\n";

remove_filter( 'pfh_webwinkelkeur_pre_reviews', $live_feed );
add_filter( 'pfh_webwinkelkeur_pre_reviews', '__return_empty_array' );

$quiet = el( 'PFH_Element_Checkout_Reviews' );

ok( 'the typed review is shown instead', false !== strpos( $quiet, 'Jessica' ) && false !== strpos( $quiet, 'Tevreden klant' ) );
ok( '  so the column is never empty', false !== strpos( $quiet, 'pfh-ckrev__card is-active' ) );
ok( 'one review does not pretend to rotate', false !== strpos( $quiet, 'data-pfh-ckrev="0"' ) && false === strpos( $quiet, 'pfh-ckrev__dots' ) );

remove_filter( 'pfh_webwinkelkeur_pre_reviews', '__return_empty_array' );

echo "\n── the reasons ──\n";

$trust = el( 'PFH_Element_Checkout_Trust' );

ok( 'the heading is there', false !== strpos( $trust, 'Waarom meer dan 4000+ klanten voor ons kiezen' ) );
ok( 'four reasons, each with its icon', 4 === substr_count( $trust, 'pfh-cktrust__row' ) - substr_count( $trust, 'pfh-cktrust__row-title' ) && 4 === substr_count( $trust, '<svg' ) );
ok( 'the review count is the live one, rounded down', false !== strpos( $trust, '390+ 5 sterren reviews' ), 'expected 390+ from 396' );
ok( 'a price written into another line is left alone', false !== strpos( $trust, 'vanaf €60 NL &amp; €70 BE' ) );
ok( '  and so is every other number', false !== strpos( $trust, 'Voor 15:00 besteld' ) && false !== strpos( $trust, 'elke euro is 1 punt' ) );

remove_filter( 'pfh_webwinkelkeur_pre_summary', $live_summary );
add_filter( 'pfh_webwinkelkeur_pre_summary', static function () { return [ 'rating' => null, 'count' => null ]; } );

ok( 'without the feed the count falls back rather than disappearing', false !== strpos( el( 'PFH_Element_Checkout_Trust' ), '380+ 5 sterren reviews' ) );

$own = el( 'PFH_Element_Checkout_Trust', [ 'rows' => [ [ 'icon' => 'nope', 'title' => 'Eigen reden', 'text' => '' ] ] ] );

ok( 'an unknown icon falls back to a tick rather than nothing', false !== strpos( $own, 'Eigen reden' ) && 1 === substr_count( $own, '<svg' ) );

echo "\n── address labels on the checkout ──\n";

if ( ! wp_script_is( 'wc-address-i18n', 'registered' ) ) {
	wp_register_script( 'wc-address-i18n', 'https://example.test/address-i18n.js', [ 'jquery' ], '1', true );
}

$inline = static function () {
	$data = wp_scripts()->get_data( 'wc-address-i18n', 'before' );

	return is_array( $data ) ? implode( "\n", array_filter( $data, 'is_string' ) ) : '';
};

PFH_Widgets_Checkout_Labels::attach();
ok( 'nothing is added to a page that is not a checkout', false === strpos( $inline(), 'wc_address_i18n_params' ) );

add_filter( 'woocommerce_is_checkout', '__return_true' );
PFH_Widgets_Checkout_Labels::attach();
remove_filter( 'woocommerce_is_checkout', '__return_true' );

$js = $inline();

ok( 'on the checkout it runs just before WooCommerce\'s address script', false !== strpos( $js, 'wc_address_i18n_params' ) );
ok( '  once', 1 === substr_count( $js, 'var params = window.wc_address_i18n_params' ) );
ok( '  it notes labels as served and puts back only the shop\'s own', false !== strpos( $js, 'country_to_state_changing' ) && false !== strpos( $js, '! theirs[ name ]' ) );
ok( '  listening on the document, which exists even when scripts are printed in the head', false !== strpos( $js, "$( document ).on( 'country_to_state_changing'" ) && false === strpos( $js, 'document.body' ) );
ok( 'the script is the plugin\'s file, so the checks run over what ships', PFH_Widgets_Checkout_Labels::script() === trim( file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/assets/js/pfh-checkout-labels.js' ) ) );

echo "\n$pass passed, $fail failed\n";
