<?php
/**
 * The plugin must load and boot with no Bricks anywhere.
 *
 * Bricks is a *theme*. Themes load after plugins, so at plugins_loaded the
 * class every element extends does not exist yet. Requiring an element there
 * is not a graceful degradation — PHP cannot parse the file at all, and the
 * result is "Class Bricks\Element not found" on every single request,
 * wp-admin included. A site goes white with no way back in.
 *
 * That shipped once, in v1.25.0, and no existing suite caught it: the
 * wp-testbed defines a Bricks stub in an mu-plugin, which loads *before*
 * plugins and hid the fault completely. This runs in a bare process with no
 * stub at all, which is the only place the ordering is real.
 */

$pass = 0;
$fail = 0;

function ok( $label, $cond, $detail = '' ) {
	global $pass, $fail;

	if ( $cond ) {
		$pass++;
		echo "  ok   $label\n";
	} else {
		$fail++;
		echo "  FAIL $label" . ( $detail ? "\n       $detail" : '' ) . "\n";
	}
}

$dir  = __DIR__ . '/../pfh-bricks-widgets/';
$boot = file_get_contents( $dir . 'pfh-bricks-widgets.php' );

echo "── nothing the plugin includes at boot may need Bricks ──\n";

preg_match_all( "#require_once PFH_WIDGETS_DIR \. '([^']+)';#", $boot, $m );
ok( 'the bootstrap has includes to check', count( $m[1] ) > 5, count( $m[1] ) . ' found' );

$elements = array_filter( $m[1], function ( $f ) { return 0 === strpos( $f, 'elements/' ); } );
ok( 'no element file is included at boot', ! $elements, implode( ', ', $elements ) );

echo "\n── and nothing loads one on plugins_loaded either ──\n";

$plugin = file_get_contents( $dir . 'includes/class-pfh-plugin.php' );

// Isolate boot_services(), which is what plugins_loaded runs.
$start = strpos( $plugin, 'public static function boot_services()' );
$body  = substr( $plugin, $start, strpos( $plugin, "\n\t}", $start ) - $start );

ok(
	'boot_services() requires no element',
	false === strpos( $body, "elements/" ),
	'it requires an element file, which fatals before the theme exists'
);

echo "\n── every element file is only reachable behind a guard ──\n";

foreach ( glob( $dir . 'includes/*.php' ) as $file ) {
	$src  = file_get_contents( $file );
	$name = basename( $file );

	if ( false === strpos( $src, "elements/" ) ) {
		continue;
	}

	// Each require of an element must sit near a Bricks check.
	preg_match_all( "#require_once PFH_WIDGETS_DIR \. 'elements/[^']+';#", $src, $reqs, PREG_OFFSET_CAPTURE );

	foreach ( $reqs[0] as $req ) {
		$before = substr( $src, max( 0, $req[1] - 600 ), 600 );

		ok(
			"$name: " . trim( preg_replace( '#.*elements/#', '', $req[0] ), "';" ) . ' is guarded',
			false !== strpos( $before, "class_exists( '\\Bricks\\Element' )" ),
			'required without checking that Bricks is loaded'
		);
	}
}

echo "\n── the whole bootstrap actually runs in a bare process ──\n";

/*
 * Not a simulation: this includes every boot file for real, with no Bricks
 * and no WordPress, and fails if PHP cannot get through them.
 */
$probe = tempnam( sys_get_temp_dir(), 'pfhboot' ) . '.php';

file_put_contents(
	$probe,
	'<?php
	define( "ABSPATH", "/tmp/" );
	define( "PFH_WIDGETS_FILE", ' . var_export( $dir . 'pfh-bricks-widgets.php', true ) . ' );
	define( "PFH_WIDGETS_DIR", ' . var_export( $dir, true ) . ' );
	define( "PFH_WIDGETS_URL", "https://example.test/wp-content/plugins/pfh-bricks-widgets/" );
	define( "PFH_WIDGETS_VERSION", "test" );

	// The handful of WordPress functions the files touch while being read.
	function add_action() {} function add_filter() {} function esc_html__( $t ) { return $t; }
	function __( $t ) { return $t; } function esc_attr__( $t ) { return $t; }
	function register_activation_hook() {} function register_deactivation_hook() {}
	function trait_exists_stub() {}

	$src = file_get_contents( PFH_WIDGETS_DIR . "pfh-bricks-widgets.php" );
	preg_match_all( "#require_once PFH_WIDGETS_DIR \\. \'([^\']+)\';#", $src, $m );

	foreach ( $m[1] as $f ) {
		require_once PFH_WIDGETS_DIR . $f;
	}

	echo "BOOT_OK " . count( $m[1] );'
);

exec( 'php ' . escapeshellarg( $probe ) . ' 2>&1', $out, $code );
unlink( $probe );

$said = implode( "\n", $out );

ok(
	'every boot file parses and loads with no Bricks present',
	0 === $code && false !== strpos( $said, 'BOOT_OK' ),
	$said
);

ok( 'and nothing mentions a missing Bricks class', false === strpos( $said, 'Bricks' ), $said );

echo "\n$pass passed, $fail failed\n";

exit( $fail ? 1 : 0 );
