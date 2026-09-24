<?php
/**
 * Generate the licence signing keypair — run once.
 *
 *   php dev/license/keygen.php
 *
 * Writes the PRIVATE key to dev/license/private.key (git-ignored, never in the
 * plugin zip) and prints the PUBLIC key. The public key is safe to ship: it can
 * only verify a token, never mint one. Keep private.key secret — whoever has it
 * can issue licences. If it ever leaks, regenerate and re-bake the public key.
 */
if ( ! function_exists( 'sodium_crypto_sign_keypair' ) ) { fwrite( STDERR, "libsodium not available\n" ); exit( 1 ); }
$path = __DIR__ . '/private.key';
if ( file_exists( $path ) && empty( $argv[1] ) ) { fwrite( STDERR, "private.key already exists; pass 'force' to overwrite\n" ); exit( 1 ); }
$kp = sodium_crypto_sign_keypair();
file_put_contents( $path, base64_encode( sodium_crypto_sign_secretkey( $kp ) ) . "\n" );
chmod( $path, 0600 );
echo "Private key written to " . $path . " (keep it safe, out of git and the zip).\n";
echo "Public key (bake this into the plugin as PFH_WIDGETS_LICENSE_PUBKEY):\n\n";
echo base64_encode( sodium_crypto_sign_publickey( $kp ) ) . "\n";
