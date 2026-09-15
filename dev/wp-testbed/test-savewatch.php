<?php
/**
 * The save watcher has to notice a failing save without becoming one.
 *
 * A save that fails leaves nothing to look at, so this records the request's
 * shape. Which means it runs on every save, including the ones that work — so
 * the first thing it must never do is interfere.
 */
require __DIR__ . '/wp-load.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function attempt( $request, $post, $server = [] ) {
	delete_option( PFH_Widgets_Diagnose::LOG );
	$_REQUEST = $request; $_POST = $post;
	$_SERVER = array_merge( $_SERVER, $server );
	PFH_Widgets_Diagnose::watch_save();
	$log = get_option( PFH_Widgets_Diagnose::LOG, [] );

	return is_array( $log ) && $log ? $log[0] : null;
}

echo "── it notices a Bricks save ──\n";
$e = attempt( [ 'action' => 'bricks_save_post' ], [ 'a' => 1, 'b' => [ 'c' => 2 ] ], [ 'CONTENT_LENGTH' => '4096' ] );
ok( 'a save is recorded', null !== $e );
ok( 'with the action named', 'bricks_save_post' === ( $e['action'] ?? '' ) );
ok( 'and the payload size', 4096 === (int) ( $e['bytes'] ?? 0 ) );
ok( 'and how many variables arrived', (int) ( $e['vars'] ?? 0 ) >= 3, ( $e['vars'] ?? 0 ) . ' counted' );
ok( 'and the limit it is measured against', (int) ( $e['limit'] ?? 0 ) > 0 );

echo "\n── a REST save counts too ──\n";
$e = attempt( [], [], [ 'REQUEST_URI' => '/wp-json/bricks/v1/save-post', 'CONTENT_LENGTH' => '9000' ] );
ok( 'the REST route is recognised', null !== $e && false !== strpos( (string) ( $e['action'] ?? '' ), 'bricks' ) );

echo "\n── ordinary requests are left alone ──\n";
foreach ( [
	[ [ 'action' => 'heartbeat' ], [], [ 'REQUEST_URI' => '/wp-admin/admin-ajax.php' ] ],
	[ [], [], [ 'REQUEST_URI' => '/shop/' ] ],
	[ [ 'action' => 'pfh_recent' ], [], [ 'REQUEST_URI' => '/wp-admin/admin-ajax.php' ] ],
] as $i => $case ) {
	ok( 'request ' . ( $i + 1 ) . ' records nothing', null === attempt( $case[0], $case[1], $case[2] ) );
}

echo "\n── the silent failure it exists to catch ──\n";
/*
 * PHP drops everything past max_input_vars without a word — no error, nothing
 * in the response. The tree arrives truncated and Bricks saves what it got.
 */
$limit = (int) ini_get( 'max_input_vars' );
$big   = [];
for ( $i = 0; $i < $limit + 50; $i++ ) { $big[ 'k' . $i ] = $i; }

$e = attempt( [ 'action' => 'bricks_save_post' ], $big, [ 'CONTENT_LENGTH' => '900000' ] );
ok( 'a truncated save is called out', false !== strpos( (string) ( $e['status'] ?? '' ), 'TRUNCATED' ), $e['status'] ?? '' );
ok( 'and it says how close to the limit it got', false !== strpos( (string) ( $e['status'] ?? '' ), (string) $limit ) );

$e = attempt( [ 'action' => 'bricks_save_post' ], [ 'a' => 1 ], [ 'CONTENT_LENGTH' => '2000' ] );
ok( 'a small save is not accused of it', false === strpos( (string) ( $e['status'] ?? '' ), 'TRUNCATED' ) );

echo "\n── it cannot become the failure ──\n";
/*
 * Reading the body is the one thing that could break a REST save, so look for
 * an actual read rather than the string — it is named in a comment saying
 * exactly this, and matching that would pass for the wrong reason.
 */
$src = file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/includes/class-pfh-diagnose.php' );
ok(
	'the request body is never actually read',
	! preg_match( '/(file_get_contents|fopen|readfile|stream_get_contents)\s*\(\s*[\'"]php:\/\/input/', $src )
);

$before = get_num_queries();
attempt( [ 'action' => 'bricks_save_post' ], [ 'a' => 1 ], [ 'CONTENT_LENGTH' => '10' ] );
ok( 'watching costs a couple of queries at most', get_num_queries() - $before <= 6, ( get_num_queries() - $before ) . ' queries' );

$threw = '';
try { attempt( [ 'action' => 'bricks_save_post' ], [], [] ); } catch ( \Throwable $x ) { $threw = $x->getMessage(); }
ok( 'a save with no body at all does not throw', '' === $threw, $threw );

echo "\n── only the last few are kept ──\n";
delete_option( PFH_Widgets_Diagnose::LOG );
for ( $i = 0; $i < 12; $i++ ) {
	$_REQUEST = [ 'action' => 'bricks_save_post' ]; $_POST = [ 'n' => $i ];
	PFH_Widgets_Diagnose::watch_save();
}
$log = get_option( PFH_Widgets_Diagnose::LOG, [] );
ok( 'the log does not grow without bound', count( (array) $log ) <= 6, count( (array) $log ) . ' kept' );
// WordPress has spelled this 'no' and, more recently, 'off'.
$autoload = (string) $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT autoload FROM {$GLOBALS['wpdb']->options} WHERE option_name = %s", PFH_Widgets_Diagnose::LOG ) );
ok( 'and it is not autoloaded on every page', in_array( $autoload, [ 'no', 'off' ], true ), "autoload=$autoload" );

delete_option( PFH_Widgets_Diagnose::LOG );
echo "\n$pass passed, $fail failed\n";
