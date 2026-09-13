<?php
/**
 * The columns and per-page settings, exercised through the element itself
 * rather than trusted from the control list.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-archive.php';

$pass = 0;
$fail = 0;

function check( $label, $got, $want ) {
	global $pass, $fail;
	if ( (string) $got === (string) $want ) {
		$pass++;
		echo "  ok   $label = $got\n";
	} else {
		$fail++;
		echo "  FAIL $label = $got (wanted $want)\n";
	}
}

function render_with( array $over, $id ) {
	$el       = new PFH_Element_Archive( [ 'id' => $id ] );
	$el->name = 'pfh-archive';
	$el->set_control_groups();
	$el->set_controls();

	$settings = [];
	foreach ( $el->controls as $key => $control ) {
		if ( array_key_exists( 'default', $control ) ) {
			$settings[ $key ] = $control['default'];
		}
	}

	$el->settings = array_merge( $settings, [ 'catsSource' => 'top' ], $over );

	ob_start();
	$el->render();

	return ob_get_clean();
}

$cases = [
	[ 'columns' => 3, 'perPage' => 6 ],
	[ 'columns' => 5, 'perPage' => 20 ],
	[ 'columns' => 2, 'perPage' => 1 ],
	[ 'columns' => 6, 'columnsTablet' => 4, 'columnsMobile' => 3, 'perPage' => 9 ],
];

foreach ( $cases as $i => $over ) {
	echo 'case ' . ( $i + 1 ) . ': ' . wp_json_encode( $over ) . "\n";
	$html = render_with( $over, 'cols' . $i );

	preg_match( '/--pfh-arch-cols:\s*(\d+)/', $html, $m );
	check( 'desktop columns', $m[1] ?? 'missing', $over['columns'] );

	if ( isset( $over['columnsTablet'] ) ) {
		preg_match( '/--pfh-arch-cols-t:\s*(\d+)/', $html, $m );
		check( 'tablet columns', $m[1] ?? 'missing', $over['columnsTablet'] );
	}

	if ( isset( $over['columnsMobile'] ) ) {
		preg_match( '/--pfh-arch-cols-m:\s*(\d+)/', $html, $m );
		check( 'phone columns', $m[1] ?? 'missing', $over['columnsMobile'] );
	}

	check( 'cards rendered', preg_match_all( '/class="pfh-prod__card"/', $html ), min( $over['perPage'], 32 ) );
}

echo "pager follows per page:\n";
$html = render_with( [ 'perPage' => 10, 'pagerEnable' => true ], 'colspager' );
preg_match_all( '/data-pfh-arch-page="(\d+)"/', $html, $m );
check( 'highest page offered for 32 products at 10 a page', max( array_map( 'intval', $m[1] ) ), 4 );

echo "\ncard format, in order:\n";
$html = render_with( [ 'perPage' => 1 ], 'colsfmt' );
preg_match( '/<article class="pfh-prod__card">(.*?)<\/article>/s', $html, $m );
$card = $m[1] ?? '';
$order = [];
foreach ( [ 'pfh-prod__media' => 'image', 'pfh-prod__reviews' => 'rating', 'pfh-prod__name' => 'title', 'pfh-prod__price' => 'price', 'pfh-prod__cart' => 'button' ] as $cls => $name ) {
	$at = strpos( $card, $cls );
	if ( false !== $at ) {
		$order[ $at ] = $name;
	}
}
ksort( $order );
check( 'image | rating | title | price | button', implode( ' | ', $order ), 'image | rating | title | price | button' );
check( 'button label', false !== strpos( $card, 'TOEVOEGEN' ) ? 'TOEVOEGEN' : 'missing', 'TOEVOEGEN' );
check( 'basket icon on the button', preg_match( '/pfh-prod__cart[^>]*>.*?<svg/s', $card ), 1 );

echo "\n$pass passed, $fail failed\n";
