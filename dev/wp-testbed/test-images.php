<?php
/**
 * Where the bundled artwork is served from.
 *
 * It moved out of the plugin to keep the package inside a host's upload
 * limit. What must stay true: a local copy still wins, the filter still
 * works, and no element ever emits a broken URL.
 */
require __DIR__ . '/wp-load.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$images = [ 'pfh-hero-branch.png', 'pfh-olive-branch.png', 'pfh-shop-banner.png', 'pfh-highlight.jpg', 'pfh-faq-bg.jpg', 'pfh-webwinkelkeur.png' ];

echo "── every image resolves to a real URL ──\n";
foreach ( $images as $img ) {
	$url = PFH_Widgets_Assets::img( $img );
	ok( $img . ' -> ' . $url, (bool) filter_var( $url, FILTER_VALIDATE_URL ) && false !== strpos( $url, $img ) );
}

echo "\n── a local copy always wins, so a site can self-host ──\n";
$local = PFH_WIDGETS_DIR . 'assets/img/pfh-hero-branch.png';
$had   = file_exists( $local );

if ( ! $had ) {
	if ( ! is_dir( dirname( $local ) ) ) { wp_mkdir_p( dirname( $local ) ); }
	file_put_contents( $local, 'x' );
}

ok( 'with the file present it is served from the plugin', false !== strpos( PFH_Widgets_Assets::img( 'pfh-hero-branch.png' ), PFH_WIDGETS_URL ) );

if ( ! $had ) { unlink( $local ); }

ok( 'with it absent it comes from the CDN', false !== strpos( PFH_Widgets_Assets::img( 'pfh-hero-branch.png' ), 'cdn.jsdelivr.net' ) );

echo "\n── the base is filterable ──\n";
add_filter( 'pfh_widgets_image_base', function () { return 'https://example.test/art/'; } );
ok( 'the filter is honoured', 'https://example.test/art/pfh-faq-bg.jpg' === PFH_Widgets_Assets::img( 'pfh-faq-bg.jpg' ) );

add_filter( 'pfh_widgets_image_base', function () { return 'https://example.test/art'; }, 20 );
ok( 'a base without a trailing slash still works', 'https://example.test/art/pfh-faq-bg.jpg' === PFH_Widgets_Assets::img( 'pfh-faq-bg.jpg' ) );
remove_all_filters( 'pfh_widgets_image_base' );

echo "\n── the ref is pinned, not a moving branch ──\n";
$url = PFH_Widgets_Assets::img( 'pfh-highlight.jpg' );
ok( 'it points at a tag', (bool) preg_match( '#@v\d+\.\d+\.\d+/#', $url ), $url );
ok( 'not at main', false === strpos( $url, '@main' ) );

echo "\n── the elements emit it, not a plugin path ──\n";
foreach ( [ 'highlight', 'shophead', 'faq' ] as $slug ) {
	require_once PFH_WIDGETS_DIR . 'elements/class-pfh-element-' . $slug . '.php';
	$class = 'PFH_Element_' . ucfirst( $slug );

	$el       = new $class( [ 'id' => 'img' . $slug ] );
	$el->name = 'pfh-' . $slug;
	$el->set_control_groups();
	$el->set_controls();

	$s = [];
	foreach ( $el->controls as $k => $c ) { if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; } }
	$el->settings = $s;

	ob_start(); $el->render(); $html = ob_get_clean();

	ok( $slug . ' points at the hosted copy', false === strpos( $html, '/plugins/pfh-bricks-widgets/assets/img/' ), 'still bundling' );
}

echo "\n$pass passed, $fail failed\n";
