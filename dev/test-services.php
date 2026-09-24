<?php
/**
 * Tests for the store services: the settings framework, the review badge and
 * the shared review figures.
 *
 *   php dev/test-services.php
 *
 * Cookie consent, the permalink manager and PDF invoices were taken out in
 * 1.48.0; the last section checks they stay out.
 */

require __DIR__ . '/stubs-services.php';

$pass = 0;
$fail = 0;
$notes = [];

function ok( $label, $condition, $detail = '' ) {
	global $pass, $fail;

	if ( $condition ) {
		$pass++;
		echo "  \033[32m✓\033[0m {$label}\n";
	} else {
		$fail++;
		echo "  \033[31m✗\033[0m {$label}";
		echo $detail ? "\n      {$detail}\n" : "\n";
	}
}

function is_eq( $label, $actual, $expected ) {
	ok(
		$label,
		$actual === $expected,
		'expected: ' . var_export( $expected, true ) . "\n      actual:   " . var_export( $actual, true )
	);
}

function heading( $text ) {
	echo "\n\033[1m{$text}\033[0m\n";
}

/* =====================================================================
 * 1. Settings framework
 * ================================================================== */

heading( '1. Settings framework' );

PFH_Widgets_Badge::forget();

$clean = PFH_Widgets_Badge::sanitize(
	[
		'enabled'     => 'on',
		'side'        => 'nonsense-value',
		'offset'      => '9999',
		'title'       => '  <script>alert(1)</script>Hallo  ',
		'unknown_key' => 'should be dropped',
	]
);

is_eq( 'checkbox present becomes true', $clean['enabled'], true );
is_eq( 'checkbox absent becomes false', $clean['mobile'], false );
is_eq( 'invalid select falls back to the default', $clean['side'], 'left' );
is_eq( 'number is clamped to its max', $clean['offset'], 600 );
is_eq( 'text is stripped of markup', $clean['title'], 'alert(1)Hallo' );
ok( 'unknown keys are dropped', ! array_key_exists( 'unknown_key', $clean ) );

/* =====================================================================
 * 8. Badge
 * ================================================================== */

heading( '8. Review badge' );

update_option(
	'pfh_badge',
	array_merge(
		PFH_Widgets_Badge::defaults(),
		[ 'enabled' => true, 'fallback_rating' => '9,7', 'fallback_count' => 396 ]
	)
);
PFH_Widgets_Badge::forget();

$data = PFH_Widgets_Badge::data();

is_eq( 'falls back to the configured score when offline', $data['rating'], '9.7' );
is_eq( 'falls back to the configured count', $data['count'], 396 );
is_eq( 'converts a 9.7/10 score to 5 stars', $data['stars'], 5.0 );
ok( 'knows the figures are not live', false === $data['live'] );

is_eq( '8.0/10 becomes 4 stars', PFH_Widgets_Reviews::stars( 8.0, 10, 5 ), 4.0 );
is_eq( '9.0/10 becomes 4.5 stars', PFH_Widgets_Reviews::stars( 9.0, 10, 5 ), 4.5 );

ob_start();
PFH_Widgets_Badge::render();
$badge = ob_get_clean();

ok( 'badge renders the score', false !== strpos( $badge, '9.7' ) );
ok( 'badge renders every trust point', 4 === substr_count( $badge, 'pfh-bdg__point"' ) );
ok( 'badge panel starts hidden for the script to decide', false !== strpos( $badge, 'id="pfh-badge-panel" hidden' ) );
ok( 'badge links out with rel="noopener nofollow"', false !== strpos( $badge, 'rel="noopener nofollow"' ) );

/* =====================================================================
 * 10. Badge mark
 * ================================================================== */

heading( '10. Badge mark' );

update_option( 'pfh_badge', array_merge( PFH_Widgets_Badge::defaults(), [ 'enabled' => true ] ) );
PFH_Widgets_Badge::forget();

ok(
	'falls back to the transparent PNG that ships with the plugin',
	false !== strpos( PFH_Widgets_Badge::mark_src(), 'assets/img/pfh-webwinkelkeur.png' ),
	PFH_Widgets_Badge::mark_src()
);

update_option( 'pfh_badge', array_merge( PFH_Widgets_Badge::defaults(), [ 'enabled' => true, 'mark_url' => 'https://cdn.example.com/wwk.svg' ] ) );
PFH_Widgets_Badge::forget();

is_eq( 'a typed URL wins over the bundled file', PFH_Widgets_Badge::mark_src(), 'https://cdn.example.com/wwk.svg' );

ob_start();
PFH_Widgets_Badge::render();
$marked = ob_get_clean();

ok( 'the mark renders as an image, not the built-in SVG', 2 === substr_count( $marked, 'pfh-bdg__mark-img' ) );
ok( 'the mark is hidden from assistive tech, since the text says it', 2 === substr_count( $marked, 'alt="" aria-hidden="true"' ) );
ok( 'the tab colours reach the markup', false !== strpos( $marked, '--pfh-bdg-tab-bg' ) && false !== strpos( $marked, '--pfh-bdg-mark' ) );

update_option( 'pfh_badge', array_merge( PFH_Widgets_Badge::defaults(), [ 'enabled' => true ] ) );
PFH_Widgets_Badge::forget();

/* =====================================================================
 * 11. What was taken out stays out
 * ================================================================== */

heading( '11. Cookie consent, permalinks and invoices are gone' );

$root      = dirname( __DIR__ ) . '/pfh-bricks-widgets/';
$bootstrap = file_get_contents( $root . 'pfh-bricks-widgets.php' ) . file_get_contents( $root . 'includes/class-pfh-plugin.php' );

foreach ( [ 'includes/class-pfh-consent.php', 'includes/class-pfh-permalinks.php', 'includes/class-pfh-documents.php', 'includes/class-pfh-pdf.php', 'assets/js/pfh-consent.js', 'assets/css/pfh-consent.css' ] as $file ) {
	ok( "{$file} is not shipped", ! file_exists( $root . $file ) );
}

foreach ( [ 'PFH_Widgets_Consent', 'PFH_Widgets_Permalinks', 'PFH_Widgets_Documents', 'PFH_Widgets_PDF' ] as $class ) {
	ok( "{$class} is neither loaded nor booted", ! class_exists( $class, false ) && false === strpos( $bootstrap, $class ) );
}

$left = '';
foreach ( glob( $root . '{includes,elements,assets/js}/*.{php,js}', GLOB_BRACE ) as $file ) {
	$left .= file_get_contents( $file );
}

ok( 'nothing left in the plugin calls them', ! preg_match( '/PFH_Widgets_(Consent|Permalinks|Documents|PDF)\b|pfhConsentApi/', $left ) );

ok(
	'the plugin boots its services on plugins_loaded, not at include time',
	false !== strpos( $bootstrap, "add_action( 'plugins_loaded', [ __CLASS__, 'boot_services' ]" )
);

/* =====================================================================
 * 12. Shared review figures
 *
 * The hero, the footer, the inline rating and the sticky badge all read this,
 * so one feed cannot produce three different numbers on one page.
 * ================================================================== */

heading( '12. Shared review figures' );

// Offline: the typed values survive untouched.
$off = PFH_Widgets_Reviews::figures( [ 'live' => true, 'scale' => 10, 'score' => '9.7', 'count' => 270 ] );

is_eq( 'an unreachable feed keeps the typed score', $off['score'], '9.7' );
is_eq( 'an unreachable feed keeps the typed count', $off['count'], 270 );
ok( 'and says so', false === $off['live'] );
is_eq( 'stars still match the typed score', $off['stars'], 5.0 );

// A live summary, injected the way the API would supply it.
add_filter(
	'pfh_webwinkelkeur_pre_summary',
	static function () {
		return [ 'rating' => 9.7, 'count' => 396, 'scale' => 10 ];
	}
);

$ten  = PFH_Widgets_Reviews::figures( [ 'live' => true, 'scale' => 10, 'score' => '0', 'count' => 0 ] );
$five = PFH_Widgets_Reviews::figures( [ 'live' => true, 'scale' => 5, 'score' => '0', 'count' => 0 ] );

is_eq( 'footer scale: 9.7 out of 10', $ten['score'], '9.7' );
is_eq( 'hero scale: the same rating out of 5', $five['score'], '4.9' );
is_eq( 'the live count replaces the typed one', $five['count'], 396 );
ok( 'both report as live', true === $ten['live'] && true === $five['live'] );

// 9.7/10 is 4.85/5, which rounds to a full five stars — so the banner looks
// exactly as designed until the real score actually drops.
is_eq( 'stars round to the nearest half, so the design holds at 9.7', $five['stars'], 5.0 );

$lower = PFH_Widgets_Reviews::figures( [ 'live' => false, 'scale' => 5, 'score' => '4.2' ] );
is_eq( 'a genuinely lower score moves the stars', $lower['stars'], 4.0 );

// Rounding, for a "270+ reviews" style label.
$round = PFH_Widgets_Reviews::figures( [ 'live' => true, 'scale' => 5, 'round' => 10 ] );

is_eq( 'the displayed count rounds down', $round['shown'], 390 );
is_eq( 'while the exact count stays available', $round['count'], 396 );

// Tokens.
is_eq(
	'hero label tokens resolve',
	PFH_Widgets_Reviews::tokens( '(%count%+ reviews) %score%', $round ),
	'(390+ reviews) 4.9'
);
is_eq(
	'%total% is always the exact figure',
	PFH_Widgets_Reviews::tokens( '%total% reviews', $round ),
	'396 reviews'
);
is_eq(
	'a label with no tokens is left alone',
	PFH_Widgets_Reviews::tokens( 'Excellent', $round ),
	'Excellent'
);

// Labels with no token at all. This is what was actually on the live site:
// the switch was on, the label said 270, and nothing ever changed it.
$live = PFH_Widgets_Reviews::figures( [ 'live' => true, 'scale' => 5, 'round' => 10 ] );

is_eq(
	'a token-free footer label is renumbered',
	PFH_Widgets_Reviews::tokens( '270 reviews on', $live ),
	'390 reviews on'
);
is_eq(
	'%s still works, and wins',
	PFH_Widgets_Reviews::tokens( '%s reviews on', $live ),
	'390 reviews on'
);
is_eq(
	'a token-free hero label gets both figures',
	PFH_Widgets_Reviews::tokens( '(270+ reviews) 5.0', $live ),
	'(390+ reviews) 4.9'
);
is_eq(
	'a label with no numbers is left alone',
	PFH_Widgets_Reviews::tokens( 'Excellent', $live ),
	'Excellent'
);
is_eq(
	'three or more numbers is too ambiguous to guess at',
	PFH_Widgets_Reviews::tokens( 'Open 9.00 - 17.00, 270 reviews', $live ),
	'Open 9.00 - 17.00, 270 reviews'
);

$dead = PFH_Widgets_Reviews::figures( [ 'live' => false, 'scale' => 5, 'score' => '9.7', 'count' => 270 ] );
is_eq(
	'an unreachable feed never overwrites what was typed',
	PFH_Widgets_Reviews::tokens( '270 reviews on', $dead ),
	'270 reviews on'
);

ok( 'the badge has somewhere to link to', false !== strpos( PFH_Widgets_Reviews::review_url(), 'webwinkelkeur.nl' ), PFH_Widgets_Reviews::review_url() );

$GLOBALS['pfh_db']['filters']['pfh_webwinkelkeur_pre_summary'] = [];

/* =====================================================================
 * Summary
 * ================================================================== */

echo "\n" . str_repeat( '─', 62 ) . "\n";
printf( "\033[1m%d passed, %d failed\033[0m\n", $pass, $fail );

exit( $fail ? 1 : 0 );
