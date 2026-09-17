<?php
/**
 * Every element must survive the conditions Bricks saves a page under.
 *
 * Bricks calls set_controls() while saving, not only while drawing a panel.
 * Anything in there that throws, stalls, or produces something that will not
 * JSON encode takes the save with it — and the builder reports that as
 * nothing more than "could not save", with no clue which element did it.
 *
 * The product picker is why this exists: it queried WooCommerce from inside
 * set_controls(), which is a query the save neither needs nor should risk.
 */
require __DIR__ . '/wp-load.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$plugin = PFH_Widgets_Plugin::instance();
$ref    = new ReflectionClass( $plugin );
$prop   = $ref->getProperty( 'elements' );
$prop->setAccessible( true );
$elements = $prop->getValue( $plugin );

// A save is a POST. Stand in one up so the elements see what they would see.
$_POST['action'] = 'bricks_save_post';

echo "── every element builds its controls during a save ──\n";

$queries_before = get_num_queries();

foreach ( $elements as $name => $spec ) {
	if ( ! file_exists( $spec['file'] ) ) { continue; }
	require_once $spec['file'];
	if ( ! class_exists( $spec['class'] ) ) { continue; }

	$before = get_num_queries();

	try {
		$el       = new $spec['class']( [ 'id' => 'save' . substr( md5( $name ), 0, 6 ) ] );
		$el->name = $name;
		$el->set_control_groups();
		$el->set_controls();
	} catch ( Throwable $e ) {
		ok( "$name builds controls", false, get_class( $e ) . ': ' . $e->getMessage() );
		continue;
	}

	$spent = get_num_queries() - $before;

	// JSON is how Bricks moves and stores all of this.
	$json = wp_json_encode( $el->controls );

	ok(
		sprintf( '%-18s %2d controls, %d queries', $name, count( $el->controls ), $spent ),
		false !== $json && $spent <= 2,
		false === $json ? 'controls will not JSON encode: ' . json_last_error_msg() : "$spent queries during a save"
	);
}

echo "\ntotal queries to build every element's controls during a save: " . ( get_num_queries() - $queries_before ) . "\n";

echo "\n── the highlight, rebuilt static ──\n";
/*
 * The product picker that used to live here queried the catalogue from
 * set_controls(), which Bricks also runs while saving. The rebuilt block has
 * no picker and no lookups, so the line to hold is simply: none, ever.
 */
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-highlight.php';

$before = get_num_queries();
$el = new PFH_Element_Highlight( [ 'id' => 'hl' ] );
$el->name = 'pfh-highlight';
$el->set_control_groups();
$el->set_controls();
ok( 'building its controls during a save runs no query', get_num_queries() === $before, ( get_num_queries() - $before ) . ' queries' );
ok( 'it has no product picker left to build', ! isset( $el->controls['product'] ) && ! isset( $el->controls['productId'] ) );
ok( 'its controls encode for the builder', false !== wp_json_encode( $el->controls ) );

echo "\n── the corner radius is adjustable ──\n";
foreach ( [ 'radius' => 20, 'imageRadius' => 0 ] as $control => $default ) {
	ok( "$control is a control", isset( $el->controls[ $control ] ) && 'number' === $el->controls[ $control ]['type'] );
	ok( "  and defaults to $default", $default === $el->controls[ $control ]['default'] );
}
ok( 'and the button has one of its own', isset( $el->controls['btnRadius'] ) && 'number' === $el->controls['btnRadius']['type'] );

echo "\n$pass passed, $fail failed\n";
