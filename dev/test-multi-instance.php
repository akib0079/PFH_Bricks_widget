<?php
/**
 * Regression: two elements of the same type in ONE request must not share state.
 *
 * A method `static` is shared by every instance of a class, so the second
 * product slider on a page used to return the first one's cards and never run
 * its own query — a category filter therefore worked in the builder, where
 * elements render one per request, and did nothing on the front end.
 */
require __DIR__ . '/stubs.php';
$dir = __DIR__ . '/../pfh-bricks-widgets/';
define( 'PFH_WIDGETS_VERSION', 'test' );
define( 'PFH_WIDGETS_FILE', $dir . 'pfh-bricks-widgets.php' );
define( 'PFH_WIDGETS_DIR', $dir );
define( 'PFH_WIDGETS_URL', './' );
require $dir . 'includes/class-pfh-helpers.php';
require $dir . 'includes/class-pfh-icons.php';
require $dir . 'includes/class-pfh-cart.php';
require $dir . 'includes/class-pfh-ajax.php';
require $dir . 'includes/class-pfh-reviews.php';
require $dir . 'includes/class-pfh-assets.php';
require $dir . 'includes/trait-pfh-element-defaults.php';
require $dir . 'includes/trait-pfh-design-revision.php';
require $dir . 'includes/trait-pfh-product-card.php';
require $dir . 'elements/class-pfh-element-products.php';

function defaults( $el ) {
	$el->set_control_groups(); $el->set_controls();
	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( ! array_key_exists( 'default', $c ) ) continue;
		$s[ $k ] = $c['default'];
		if ( 'repeater' === $c['type'] && is_array( $c['default'] ) ) {
			foreach ( $c['default'] as $i => $row )
				foreach ( $c['fields'] as $fk => $f )
					if ( ! isset( $row[ $fk ] ) && array_key_exists( 'default', $f ) )
						$s[ $k ][ $i ][ $fk ] = $f['default'];
		}
	}
	return $s;
}

/** Two sliders in ONE request, each with its own manual list. */
function make( $id, $titles ) {
	$el = new PFH_Element_Products( [ 'id' => $id ] );
	$el->name = 'pfh-products';
	$s = defaults( $el );
	$s['source'] = 'manual';
	$s['manualCards'] = [];
	foreach ( $titles as $t ) {
		$s['manualCards'][] = [ 'title' => $t, 'price' => '1', 'image' => [ 'url' => '/x.png' ] ];
	}
	$el->settings = $s;
	$el->element['settings'] = $s;
	return $el;
}

$a = make( 'sliderA', [ 'ON SALE bundle', 'ON SALE jar' ] );
$b = make( 'sliderB', [ 'HONEY 470gr', 'HONEY 940gr' ] );

ob_start(); $a->render(); $ha = ob_get_clean();
ob_start(); $b->render(); $hb = ob_get_clean();

preg_match_all( '/pfh-prod__name[^>]*>(?:<a[^>]*>)?([^<]+)/', $ha, $ma );
preg_match_all( '/pfh-prod__name[^>]*>(?:<a[^>]*>)?([^<]+)/', $hb, $mb );
preg_match( '/id="pfh-viewport-([^"]*)"/', $ha, $ua );
preg_match( '/id="pfh-viewport-([^"]*)"/', $hb, $ub );

printf( "slider A cards : %s\n", implode( ' | ', $ma[1] ) );
printf( "slider B cards : %s\n", implode( ' | ', $mb[1] ) );
printf( "slider A uid   : %s\n", $ua[1] ?? '?' );
printf( "slider B uid   : %s\n", $ub[1] ?? '?' );
printf( "\ncards independent : %s\n", $ma[1] !== $mb[1] ? 'YES' : 'NO  <-- BUG' );
printf( "uids unique       : %s\n", ( ( $ua[1] ?? 'a' ) !== ( $ub[1] ?? 'b' ) ) ? 'YES' : 'NO  <-- BUG' );

// uid: pull any id/aria-controls that carries it
preg_match_all('/(?:id|aria-controls)="([^"]*)"/', $ha, $ia);
preg_match_all('/(?:id|aria-controls)="([^"]*)"/', $hb, $ib);
$sa = array_values(array_unique($ia[1])); $sb = array_values(array_unique($ib[1]));
printf("\nA ids: %s\nB ids: %s\n", implode(', ', $sa), implode(', ', $sb));
printf("no shared ids between the two: %s\n", array_intersect($sa,$sb) ? 'NO  <-- BUG: '.implode(',',array_intersect($sa,$sb)) : 'YES');

exit( ( $ma[1] !== $mb[1] && ! array_intersect( $sa, $sb ) ) ? 0 : 1 );
