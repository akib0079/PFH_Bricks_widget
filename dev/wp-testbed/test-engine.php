<?php
/**
 * Real-WordPress test for the archive filter engine.
 * Run from the WP root: php pfh-test-engine.php
 */
require __DIR__ . '/wp-load.php';

$pass = 0;
$fail = 0;

function ok( $label, $cond, $detail = '' ) {
	global $pass, $fail;

	if ( $cond ) {
		$pass++;
		echo "  \033[32m✓\033[0m {$label}\n";
	} else {
		$fail++;
		echo "  \033[31m✗\033[0m {$label}" . ( $detail ? "\n      {$detail}\n" : "\n" );
	}
}

function eq( $label, $actual, $expected ) {
	ok( $label, $actual === $expected, 'expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
}

function heading( $t ) {
	echo "\n\033[1m{$t}\033[0m\n";
}

if ( ! class_exists( 'PFH_Widgets_Archive' ) ) {
	exit( "PFH_Widgets_Archive missing — plugin not loaded\n" );
}

$config = [
	'per_page' => 12,
	'orderby'  => 'menu_order',
	'base_cat' => '',
	'filters'  => [
		[ 'source' => 'pa_merk',    'label' => '', 'open' => true,  'limit' => 6 ],
		[ 'source' => 'pa_kleur',   'label' => '', 'open' => false, 'limit' => 6 ],
		[ 'source' => 'pa_gewicht', 'label' => '', 'open' => false, 'limit' => 6 ],
		[ 'source' => 'price',      'label' => '', 'open' => true,  'limit' => 0 ],
		[ 'source' => 'onsale',     'label' => '', 'open' => true,  'limit' => 0 ],
		[ 'source' => 'instock',    'label' => '', 'open' => true,  'limit' => 0 ],
	],
];

function found( array $state, array $config ) {
	$args                   = PFH_Widgets_Archive::query_args( $state, $config );
	$args['posts_per_page'] = 1;
	$args['fields']         = 'ids';
	$q                      = new WP_Query( $args );

	return (int) $q->found_posts;
}

function state( array $raw, array $config ) {
	return PFH_Widgets_Archive::state( $raw, $config );
}

/* ================================================================= */
heading( '1. Filter sources come from the real store' );

$sources = PFH_Widgets_Archive::filter_sources();

ok( 'price / on sale / in stock offered', isset( $sources['price'], $sources['onsale'], $sources['instock'] ) );
ok( 'global attributes discovered', isset( $sources['pa_merk'], $sources['pa_kleur'], $sources['pa_gewicht'] ), implode( ', ', array_keys( $sources ) ) );
ok( 'product_cat excluded, it drives the pills', ! isset( $sources['product_cat'] ) );
ok( 'internal taxonomies excluded', ! isset( $sources['product_visibility'] ) && ! isset( $sources['product_type'] ) );
eq( 'attribute label is readable', $sources['pa_kleur'], 'Kleur' );

/* ================================================================= */
heading( '2. Unfiltered query' );

$all = state( [], $config );
eq( 'every published product is found', found( $all, $config ), 32 );

$q = PFH_Widgets_Archive::query( $all, $config );
eq( 'page one holds per_page items', count( $q->posts ), 12 );
eq( 'page count is right', (int) $q->max_num_pages, 3 );

$p3 = PFH_Widgets_Archive::query( state( [ 'pfh_page' => 3 ], $config ), $config );
eq( 'last page holds the remainder', count( $p3->posts ), 8 );

/* ================================================================= */
heading( '3. Category filtering' );

eq( 'a leaf category narrows correctly', found( state( [ 'pfh_cat' => 'honing' ], $config ), $config ), 5 );
eq( 'a parent includes its children', found( state( [ 'pfh_cat' => 'gia-giamas' ], $config ), $config ), 25 );
eq( 'another leaf', found( state( [ 'pfh_cat' => 'premium-2-0' ], $config ), $config ), 5 );
eq( 'an unknown category yields nothing', found( state( [ 'pfh_cat' => 'does-not-exist' ], $config ), $config ), 0 );

$pinned = array_merge( $config, [ 'base_cat' => 'olijfolie' ] );
eq( 'a pinned archive stays inside its collection', found( state( [], $pinned ), $pinned ), 4 );

/* ================================================================= */
heading( '4. Attribute facets' );

$facets = PFH_Widgets_Archive::facets( $config, $all );
$by_key = [];

foreach ( $facets as $f ) {
	$by_key[ $f['key'] ] = $f;
}

ok( 'every configured group is present', 6 === count( $facets ), implode( ', ', array_keys( $by_key ) ) );

$merk = $by_key['pa_merk'];
$total = 0;

foreach ( $merk['options'] as $o ) {
	$total += $o['count'];
}

eq( 'merk counts add up to the catalogue', $total, 32 );
eq( 'merk offers three brands', count( $merk['options'] ), 3 );

$kleur = $by_key['pa_kleur'];
$counts = [];

foreach ( $kleur['options'] as $o ) {
	$counts[ $o['slug'] ] = $o['count'];
}

ok( 'kleur counts look right', 13 === ( $counts['geel'] ?? 0 ), json_encode( $counts ) );

/* ================================================================= */
heading( '5. Faceting contract: OR inside a group, AND across groups' );

$geel = state( [ 'pfh_tax_pa_kleur' => 'geel' ], $config );
eq( 'one colour', found( $geel, $config ), 13 );

$two = state( [ 'pfh_tax_pa_kleur' => 'geel,rood' ], $config );
eq( 'two colours OR together', found( $two, $config ), 13 + 5 );

$cross = state( [ 'pfh_tax_pa_kleur' => 'geel', 'pfh_tax_pa_merk' => 'gia-giamas' ], $config );
$expected_cross = (int) count(
	get_posts(
		[
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => [
				'relation' => 'AND',
				[ 'taxonomy' => 'pa_kleur', 'field' => 'slug', 'terms' => 'geel' ],
				[ 'taxonomy' => 'pa_merk', 'field' => 'slug', 'terms' => 'gia-giamas' ],
			],
		]
	)
);
eq( 'groups AND together', found( $cross, $config ), $expected_cross );

// A group must not count itself, or picking one option zeroes the rest.
$self = PFH_Widgets_Archive::facets( $config, $geel );
$self_by = [];

foreach ( $self as $f ) {
	$self_by[ $f['key'] ] = $f;
}

$still = [];

foreach ( $self_by['pa_kleur']['options'] as $o ) {
	$still[ $o['slug'] ] = $o['count'];
}

ok(
	'with geel ticked, other colours still show their own totals',
	( $still['rood'] ?? 0 ) === 5 && ( $still['geel'] ?? 0 ) === 13,
	json_encode( $still )
);

$narrowed = [];

foreach ( $self_by['pa_merk']['options'] as $o ) {
	$narrowed[ $o['slug'] ] = $o['count'];
}

ok(
	'but another group is narrowed by it',
	array_sum( $narrowed ) === 13,
	json_encode( $narrowed ) . ' (should total 13)'
);

/* ================================================================= */
heading( '6. Price, sale and stock' );

$price = $by_key['price'];
eq( 'price floor comes from the cheapest product', (float) $price['min'], 2.0 );
eq( 'price ceiling from the dearest', (float) $price['max'], 47.0 );

eq( 'a price window narrows', found( state( [ 'pfh_min' => 10, 'pfh_max' => 20 ], $config ), $config ), 11 );
eq( 'a narrow window', found( state( [ 'pfh_min' => 0, 'pfh_max' => 5 ], $config ), $config ), 4 );

$on_sale = wc_get_product_ids_on_sale();
eq( 'on sale matches WooCommerce', found( state( [ 'pfh_sale' => 1 ], $config ), $config ), count( $on_sale ) );
ok( 'and there really are some', count( $on_sale ) > 5, count( $on_sale ) . ' on sale' );

// Counted from the catalogue rather than hard-coded: the out-of-stock cards
// have their own suite now, which changes how many there are.
$out_of_stock = count( get_posts( [
	'post_type'   => 'product',
	'numberposts' => -1,
	'fields'      => 'ids',
	'meta_query'  => [ [ 'key' => '_stock_status', 'value' => 'outofstock' ] ],
] ) );

eq(
	'in stock excludes every out-of-stock item',
	found( state( [ 'pfh_stock' => 1 ], $config ), $config ),
	32 - $out_of_stock
);

$combo = state( [ 'pfh_cat' => 'gia-giamas', 'pfh_sale' => 1, 'pfh_tax_pa_kleur' => 'geel' ], $config );
$combo_n = found( $combo, $config );
ok( 'category + sale + colour combine', $combo_n > 0 && $combo_n < 13, $combo_n . ' products' );

/* ================================================================= */
heading( '7. Sorting' );

$cheap = PFH_Widgets_Archive::query( state( [ 'pfh_sort' => 'price' ], $config ), $config );
$first = wc_get_product( $cheap->posts[0] );
$dear  = PFH_Widgets_Archive::query( state( [ 'pfh_sort' => 'price-desc' ], $config ), $config );
$top   = wc_get_product( $dear->posts[0] );

ok( 'price ascending starts cheapest', (float) $first->get_price() <= 2.5, $first->get_name() . ' @ ' . $first->get_price() );
ok( 'price descending starts dearest', (float) $top->get_price() >= 46, $top->get_name() . ' @ ' . $top->get_price() );

/* ================================================================= */
heading( '8. State hardening' );

$evil = state(
	[
		'pfh_cat'          => '../../etc/passwd',
		'pfh_tax_pa_kleur' => 'geel,<script>',
		'pfh_tax_post_tag' => 'not-offered',
		'pfh_min'          => '-50',
		'pfh_page'         => '-3',
		'pfh_sort'         => 'DROP TABLE',
	],
	$config
);

eq( 'path traversal is sanitised away', $evil['cat'], 'etc-passwd' );
ok( 'taxonomy not in the config is ignored', ! isset( $evil['tax']['post_tag'] ), json_encode( $evil['tax'] ) );
eq( 'an unusable slug is dropped, not passed through', $evil['tax']['pa_kleur'], [ 'geel' ] );
eq( 'negative price clamps to zero', $evil['min'], 0.0 );
eq( 'page never goes below one', $evil['page'], 1 );
eq( 'an unknown sort falls back', $evil['orderby'], '' );

// Bare names must no longer reach the engine: WordPress owns page/cat/orderby.
$bare = state( [ 'cat' => 'honing', 'page' => 3, 'orderby' => 'price', 'min' => 5 ], $config );
ok( 'un-namespaced query vars are ignored entirely', '' === $bare['cat'] && 1 === $bare['page'] && '' === $bare['orderby'] && null === $bare['min'], json_encode( $bare ) );

$swapped = state( [ 'pfh_min' => 40, 'pfh_max' => 10 ], $config );
ok( 'a reversed price range is put back in order', 10.0 === $swapped['min'] && 40.0 === $swapped['max'] );

eq( 'active count is right', PFH_Widgets_Archive::active_count( $combo ), 2 );
ok( 'is_filtered agrees', PFH_Widgets_Archive::is_filtered( $combo ) && ! PFH_Widgets_Archive::is_filtered( $all ) );

/* ================================================================= */
heading( '9. Zero-result facets are dropped' );

$empty_cfg = array_merge( $config, [ 'base_cat' => 'accessoires' ] );
$acc_facets = PFH_Widgets_Archive::facets( $empty_cfg, state( [], $empty_cfg ) );
$acc_keys = [];

foreach ( $acc_facets as $f ) {
	$acc_keys[] = $f['key'];
}

ok( 'accessoires still offers filters', in_array( 'pa_merk', $acc_keys, true ), implode( ', ', $acc_keys ) );

foreach ( $acc_facets as $f ) {
	if ( 'terms' !== $f['type'] ) {
		continue;
	}

	foreach ( $f['options'] as $o ) {
		if ( 0 === $o['count'] && ! $o['checked'] ) {
			ok( 'no zero-count option is offered', false, $f['key'] . ' / ' . $o['slug'] );
			break 2;
		}
	}
}

ok( 'no zero-count option is offered', true );

echo "\n" . str_repeat( '=', 60 ) . "\n";
printf( "\033[1m%d passed, %d failed\033[0m  (WordPress %s, WooCommerce %s)\n", $pass, $fail, get_bloginfo( 'version' ), WC()->version );
exit( $fail ? 1 : 0 );
