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

echo "\n── the highlight's product picker specifically ──\n";
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-highlight.php';

$el = new PFH_Element_Highlight( [ 'id' => 'hl' ] );
$el->name = 'pfh-highlight';
$el->set_control_groups();
$el->set_controls();

ok( 'it asks for no options while saving', empty( $el->controls['product']['options'] ) );

$before = get_num_queries();
$el2 = new PFH_Element_Highlight( [ 'id' => 'hl2' ] );
$el2->name = 'pfh-highlight';
$el2->set_control_groups();
$el2->set_controls();
ok( 'and runs no query for them', get_num_queries() === $before );

// A saved choice must still work when the list is not built.
$sale = wc_get_product_ids_on_sale();
$el2->settings = [ 'product' => (string) $sale[0], 'priceSource' => 'product' ];
ob_start(); $el2->render(); $html = ob_get_clean();
ok( 'a saved product still prices the banner with no list present', false !== strpos( $html, esc_url( wc_get_product( (int) $sale[0] )->get_permalink() ) ) );

echo "\n── and the list is built when the panel is actually open ──\n";
unset( $_POST['action'] );
$_POST = [];
delete_transient( PFH_Element_Highlight::OPTIONS_KEY );

// picking() asks is_admin(); stand that up the way an admin request does.
if ( ! defined( 'WP_ADMIN' ) ) { define( 'WP_ADMIN', true ); }

$el3 = new PFH_Element_Highlight( [ 'id' => 'hl3' ] );
$el3->name = 'pfh-highlight';
$el3->set_control_groups();
$el3->set_controls();

$opts = (array) ( $el3->controls['product']['options'] ?? [] );
ok( 'the picker is populated', count( $opts ) > 3, count( $opts ) . ' products' );
ok( 'and it JSON encodes', false !== wp_json_encode( $opts ) );
ok( 'every label is valid UTF-8', count( array_filter( $opts, function ( $l ) { return mb_check_encoding( (string) $l, 'UTF-8' ); } ) ) === count( $opts ) );

echo "\n── the corner radius is adjustable ──\n";
foreach ( [ 'radius' => 20, 'imageRadius' => 0 ] as $control => $default ) {
	ok( "$control is a control", isset( $el3->controls[ $control ] ) && 'number' === $el3->controls[ $control ]['type'] );
	ok( "  and defaults to $default", $default === $el3->controls[ $control ]['default'] );
}

echo "\n$pass passed, $fail failed\n";
