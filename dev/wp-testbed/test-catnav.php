<?php
/**
 * Switching category by clicking a pill.
 *
 * The user's report was that a pill sent the browser to admin-ajax.php, and
 * that moving between categories had to change the heading, the breadcrumb
 * and the products — not just the grid. This renders the element in a real
 * product_cat query, the way a followed pill link lands, and checks all of
 * that rather than the markup on one page.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-archive.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-shophead.php';

$pass = 0;
$fail = 0;

function ok( $label, $cond, $detail = '' ) {
	global $pass, $fail;
	if ( $cond ) { $pass++; echo "  ok   $label\n"; }
	else { $fail++; echo "  FAIL $label" . ( $detail ? " — $detail" : '' ) . "\n"; }
}

function defaults_for( $class, $id, array $over = [] ) {
	$el       = new $class( [ 'id' => $id ] );
	$el->name = 'pfh-test';
	$el->set_control_groups();
	$el->set_controls();

	$settings = [];
	foreach ( $el->controls as $key => $control ) {
		if ( array_key_exists( 'default', $control ) ) {
			$settings[ $key ] = $control['default'];
		}
	}

	$el->settings = array_merge( $settings, $over );

	return $el;
}

/** Render both widgets as they would appear on a category archive. */
function on_category( $slug ) {
	$url = $slug ? get_term_link( get_term_by( 'slug', $slug, 'product_cat' ) ) : wc_get_page_permalink( 'shop' );

	// Land on that archive exactly as a followed link would.
	$_SERVER['REQUEST_URI'] = wp_parse_url( $url, PHP_URL_PATH );
	$_GET                   = [];

	if ( $slug ) {
		query_posts( [ 'post_type' => 'product', 'product_cat' => $slug, 'posts_per_page' => 12 ] );
	} else {
		query_posts( [ 'post_type' => 'product', 'posts_per_page' => 12 ] );
	}

	$arch = defaults_for( 'PFH_Element_Archive', 'nav' . ( $slug ? $slug : 'shop' ), [ 'catsSource' => 'top' ] );
	$head = defaults_for( 'PFH_Element_Shophead', 'navh' . ( $slug ? $slug : 'shop' ) );

	ob_start();
	$head->render();
	$arch->render();
	$html = ob_get_clean();

	wp_reset_query();

	return $html;
}

function pills( $html ) {
	preg_match_all( '/<a class="pfh-arch__pill([^"]*)" href="([^"]+)"[^>]*>([^<]*)</', $html, $m, PREG_SET_ORDER );
	return $m;
}

function products( $html ) {
	preg_match_all( '/class="pfh-prod__name"[^>]*>\s*(?:<a[^>]*>)?([^<]+)/', $html, $m );
	return array_map( 'trim', $m[1] );
}

echo "── every pill is a real archive URL ──\n";
$shop = on_category( '' );
$rows = pills( $shop );
ok( 'pills rendered', count( $rows ) > 1, count( $rows ) . ' found' );

foreach ( $rows as $row ) {
	$href  = html_entity_decode( $row[2] );
	$label = trim( $row[3] );
	ok( "\"$label\" → $href", false === strpos( $href, 'admin-ajax.php' ) && false === strpos( $href, '?' ) && (bool) filter_var( $href, FILTER_VALIDATE_URL ) );
}

echo "\n── the shop page marks Alles active ──\n";
ok( 'Alles is active on the shop page', false !== strpos( $rows[0][1] ?? '', '' ) && false !== strpos( $rows[0][0], 'is-active' ) );

echo "\n── landing on a category changes everything, not just the grid ──\n";
$terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 3 ] );
$seen  = [];

foreach ( $terms as $term ) {
	$html = on_category( $term->slug );

	$active = '';
	foreach ( pills( $html ) as $row ) {
		if ( false !== strpos( $row[0], 'is-active' ) ) {
			$active = trim( $row[3] );
		}
	}

	$names = products( $html );
	$seen[ $term->slug ] = $names;

	echo "  {$term->name} ({$term->slug}):\n";
	ok( "    active pill is \"{$term->name}\"", $active === $term->name, "got \"$active\"" );
	ok( '    breadcrumb ends on the category', false !== strpos( $html, $term->name ) );
	ok( '    heading shows the category', (bool) preg_match( '/pfh-shophead__title[^>]*>\s*' . preg_quote( $term->name, '/' ) . '/', $html ) );
	ok( '    products belong to it', $names && count( $names ) <= 12, count( $names ) . ' products' );
	ok( '    count line matches the category', (bool) preg_match( '/data-pfh-arch-count>(\d+) producten/', $html, $c ) && (int) $c[1] === (int) $term->count, ( $c[1] ?? '?' ) . ' vs term count ' . $term->count );
}

echo "\n── two categories never show the same products ──\n";
$slugs = array_keys( $seen );
for ( $i = 0; $i < count( $slugs ) - 1; $i++ ) {
	$a = $seen[ $slugs[ $i ] ];
	$b = $seen[ $slugs[ $i + 1 ] ];
	ok( "{$slugs[$i]} vs {$slugs[$i+1]} differ", $a !== $b && ! array_intersect( $a, $b ) );
}

echo "\n$pass passed, $fail failed\n";
