<?php
/**
 * Mint a licence token for a site — run when a client is paid up.
 *
 *   php dev/license/sign.php <domain> <expires: YYYY-MM-DD> [plan]
 *   php dev/license/sign.php productsforhome.nl 2026-12-31
 *   php dev/license/sign.php '*' 2027-01-01 dev     # any domain (staging)
 *
 * Paste the printed token into WP Admin → Products For Home → Licentie.
 * The token is not secret; it only works on the domain and until the date
 * baked into it. To extend a licence, mint a new token with a later date.
 */
if ( ! function_exists( 'sodium_crypto_sign_detached' ) ) { fwrite( STDERR, "libsodium not available\n" ); exit( 1 ); }
$path = __DIR__ . '/private.key';
if ( ! file_exists( $path ) ) { fwrite( STDERR, "no private.key — run keygen.php first\n" ); exit( 1 ); }
$domain  = $argv[1] ?? '';
$expires = $argv[2] ?? '';
$plan    = $argv[3] ?? 'full';
if ( '' === $domain || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $expires ) ) { fwrite( STDERR, "usage: php sign.php <domain> <YYYY-MM-DD> [plan]\n" ); exit( 1 ); }
$sk      = base64_decode( trim( file_get_contents( $path ) ) );
$payload = json_encode( [ 'domain' => strtolower( $domain ), 'expires' => $expires, 'plan' => $plan, 'issued' => gmdate( 'Y-m-d' ) ], JSON_UNESCAPED_SLASHES );
$b64u    = static function ( $s ) { return rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' ); };
echo $b64u( $payload ) . '.' . $b64u( sodium_crypto_sign_detached( $payload, $sk ) ) . "\n";
