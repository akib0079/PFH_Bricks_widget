<?php
/**
 * The licence gate.
 *
 * A signing keypair is made here, its public key injected, and tokens minted
 * in-test, so nothing touches a real licence server. What is checked is the
 * behaviour that matters: it is dormant until armed, it verifies a real token
 * and rejects a forged, wrong-domain or expired one, it withholds only the
 * presentational sections (never the store, nav or admin), and it is a pure
 * reversible transform that deletes nothing.
 */
require __DIR__ . '/wp-load.php';

require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/includes/class-pfh-settings.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/includes/class-pfh-license.php';

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

if ( ! function_exists( 'sodium_crypto_sign_keypair' ) ) {
	echo "libsodium not available — skipping\n0 passed, 0 failed\n";
	exit;
}

$kp  = sodium_crypto_sign_keypair();
$sk  = sodium_crypto_sign_secretkey( $kp );
$pub = base64_encode( sodium_crypto_sign_publickey( $kp ) );

add_filter( 'pfh_license_pubkey', static function () use ( $pub ) { return $pub; } );

$mint = static function ( $domain, $expires, $plan = 'full' ) use ( $sk ) {
	$payload = wp_json_encode( [ 'domain' => $domain, 'expires' => $expires, 'plan' => $plan, 'issued' => gmdate( 'Y-m-d' ) ] );
	$b       = static function ( $x ) { return rtrim( strtr( base64_encode( $x ), '+/', '-_' ), '=' ); };

	return $b( $payload ) . '.' . $b( sodium_crypto_sign_detached( $payload, $sk ) );
};

$day     = DAY_IN_SECONDS;
$future  = gmdate( 'Y-m-d', time() + 40 * $day );
$soon    = gmdate( 'Y-m-d', time() - 3 * $day );   // expired, within grace
$past    = gmdate( 'Y-m-d', time() - 40 * $day );  // expired, past grace
$host    = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

$arm = static function ( $key, $enforce = true ) {
	update_option( 'pfh_license', [ 'enforce' => $enforce, 'key' => $key ] );
	PFH_Widgets_License::forget();
};

echo "── dormant by default ──\n";

delete_option( 'pfh_license' );
PFH_Widgets_License::forget();

ok( 'off the shelf, the gate is not armed', false === PFH_Widgets_License::enforced() );
ok( '  so nothing is locked', false === PFH_Widgets_License::locked() );
ok( '  and the status says so', 'unarmed' === PFH_Widgets_License::status()['state'] );

$arm( $mint( '*', $future ), false );
ok( 'a valid token but the switch off is still dormant', false === PFH_Widgets_License::enforced() && false === PFH_Widgets_License::locked() );

echo "\n── a real token ──\n";

$arm( $mint( '*', $future ) );
$s = PFH_Widgets_License::status();
ok( 'a valid token for any domain is active', 'active' === $s['state'], $s['state'] );
ok( '  nothing is locked while active', false === PFH_Widgets_License::locked() );

$arm( $mint( $host, $future ) );
ok( 'a token for this exact domain is active', 'active' === PFH_Widgets_License::status()['state'] );

echo "\n── what is refused ──\n";

$arm( $mint( 'someone-else.example', $future ) );
$s = PFH_Widgets_License::status();
ok( 'a token for another domain is refused', 'domain' === $s['state'], $s['state'] );
ok( '  and that locks the sections', true === PFH_Widgets_License::locked() );

$good = $mint( '*', $future );
$arm( substr( $good, 0, -4 ) . 'AAAA' );
ok( 'a token with the signature altered is invalid', 'invalid' === PFH_Widgets_License::status()['state'] );

$arm( 'not-even-a-token' );
ok( 'gibberish is invalid, not a fatal', 'invalid' === PFH_Widgets_License::status()['state'] );

$arm( '' );
ok( 'no token at all reads as none', 'none' === PFH_Widgets_License::status()['state'] );

// A token minted by a different key must not pass.
$other = sodium_crypto_sign_keypair();
$op    = wp_json_encode( [ 'domain' => '*', 'expires' => $future ] );
$b     = static function ( $x ) { return rtrim( strtr( base64_encode( $x ), '+/', '-_' ), '=' ); };
$arm( $b( $op ) . '.' . $b( sodium_crypto_sign_detached( $op, sodium_crypto_sign_secretkey( $other ) ) ) );
ok( 'a token signed by someone else is invalid', 'invalid' === PFH_Widgets_License::status()['state'] );

echo "\n── expiry and grace ──\n";

$arm( $mint( '*', $soon ) );
$s = PFH_Widgets_License::status();
ok( 'just-expired is in grace', 'grace' === $s['state'], $s['state'] );
ok( '  grace still shows the sections', false === PFH_Widgets_License::locked() );

$arm( $mint( '*', $past ) );
$s = PFH_Widgets_License::status();
ok( 'long-expired is expired', 'expired' === $s['state'], $s['state'] );
ok( '  and locked', true === PFH_Widgets_License::locked() );

echo "\n── the gate only touches the design sections ──\n";

$tree = [
	[ 'id' => 'a1', 'name' => 'section', 'parent' => 0, 'children' => [ 'a2' ], 'settings' => [ 'x' => 1 ] ],
	[ 'id' => 'a2', 'name' => 'pfh-hero', 'parent' => 'a1', 'children' => [], 'settings' => [ 'title' => 'Hallo' ] ],
	[ 'id' => 'a3', 'name' => 'pfh-header', 'parent' => 0, 'children' => [], 'settings' => [] ],
	[ 'id' => 'a4', 'name' => 'pfh-cart-page', 'parent' => 0, 'children' => [], 'settings' => [] ],
	[ 'id' => 'a5', 'name' => 'pfh-checkout-reviews', 'parent' => 0, 'children' => [], 'settings' => [] ],
	[ 'id' => 'a6', 'name' => 'pfh-cta', 'parent' => 0, 'children' => [], 'settings' => [ 'heading' => 'X' ] ],
	[ 'id' => 'a7', 'name' => 'pfh-footer', 'parent' => 0, 'children' => [], 'settings' => [] ],
];

$out = PFH_Widgets_License::gate( $tree, 'content' );
$by  = array_column( $out, null, 'id' );

ok( 'every element is still there — nothing removed', count( $out ) === count( $tree ) );
ok( 'the ids and parents are kept', 'a1' === $by['a2']['parent'] && 'a2' === $out[1]['id'] );
ok( 'the hero is swapped for a plain notice', 'text-basic' === $by['a2']['name'] && false !== strpos( $by['a2']['settings']['text'], 'niet beschikbaar' ) );
ok( '  wearing the locked class', 'pfh-license-locked' === $by['a2']['settings']['_cssClasses'] );
ok( 'the CTA is locked too', 'text-basic' === $by['a6']['name'] );
ok( 'the header is untouched', 'pfh-header' === $by['a3']['name'] );
ok( 'the cart is untouched — the store keeps working', 'pfh-cart-page' === $by['a4']['name'] );
ok( 'the checkout reviews are untouched', 'pfh-checkout-reviews' === $by['a5']['name'] );
ok( 'the footer is untouched — navigation stays', 'pfh-footer' === $by['a7']['name'] );
ok( 'a core Bricks element is untouched', 'section' === $by['a1']['name'] );
ok( 'no lockable section survives as itself', ! array_filter( $out, static function ( $e ) { return in_array( $e['name'], PFH_Widgets_License::LOCKABLE, true ); } ) );

echo "\n── it is reversible ──\n";

$arm( $mint( '*', $future ) );
ok( 'a fresh valid token unlocks again', false === PFH_Widgets_License::locked() && 'active' === PFH_Widgets_License::status()['state'] );
$same = PFH_Widgets_License::gate( $tree, 'content' );
ok( '  the gate is a pure function — the input tree is not mutated', 'pfh-hero' === $tree[1]['name'] );

echo "\n── an element withholds itself when locked ──\n";

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $ef ) { require_once $ef; }

$render = static function ( $class ) {
	$e           = new $class( [ 'id' => 'lk' . wp_rand( 1, 99999 ) ] );
	$e->settings = [];
	ob_start();
	try { $e->render(); } catch ( \Throwable $t ) { echo 'ERR ' . $t->getMessage(); }
	return (string) ob_get_clean();
};

$arm( $mint( '*', $past ) ); // locked
$hero = $render( 'PFH_Element_Hero' );
ok( 'a locked hero shows the notice in place of its content', false !== strpos( $hero, 'pfh-license-locked' ) && false !== strpos( $hero, 'niet beschikbaar' ) );
ok( '  and none of its own slider markup', false === strpos( $hero, 'pfh-hero__slide' ) );

$prods = $render( 'PFH_Element_Products' );
ok( 'a locked product slider is withheld too', false !== strpos( $prods, 'pfh-license-locked' ) && false === strpos( $prods, 'pfh-prod__card' ) );

$header = $render( 'PFH_Element_Header' );
ok( 'the header renders as usual even when locked', false === strpos( $header, 'pfh-license-locked' ) );

$arm( $mint( '*', $future ) ); // active again
$hero2 = $render( 'PFH_Element_Hero' );
ok( 'a licensed hero renders its content again', false === strpos( $hero2, 'pfh-license-locked' ) );

delete_option( 'pfh_license' );
PFH_Widgets_License::forget();
$hero3 = $render( 'PFH_Element_Hero' );
ok( 'dormant, the hero renders normally', false === strpos( $hero3, 'pfh-license-locked' ) );

echo "\n── the constants win over the screen ──\n";

$arm( 'not-a-token' );
ok( 'without the constant the bad token locks', true === PFH_Widgets_License::locked() );

// Prove the option is read only as a fallback: the enforce constant overrides.
if ( ! defined( 'PFH_WIDGETS_LICENSE_ENFORCE' ) ) {
	define( 'PFH_WIDGETS_LICENSE_ENFORCE', false );
	PFH_Widgets_License::forget();
	ok( 'PFH_WIDGETS_LICENSE_ENFORCE=false disarms it whatever the option says', false === PFH_Widgets_License::enforced() && false === PFH_Widgets_License::locked() );
}

delete_option( 'pfh_license' );
PFH_Widgets_License::forget();

echo "\n$pass passed, $fail failed\n";
