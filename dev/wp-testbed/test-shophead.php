<?php
/**
 * Shop header against real WordPress query objects.
 *
 * The block theme renders the shop and category pages through blocks, so
 * neither a shortcode nor WooCommerce's template hooks reach them. Priming
 * $wp_query directly is what actually exercises the conditional tags the
 * breadcrumb depends on.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-shophead.php';

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

/** Build the element with its defaults. */
function head_element( array $over = [] ) {
	$e       = new PFH_Element_Shophead( [ 'id' => 'sh' ] );
	$e->name = 'pfh-shophead';
	$e->set_control_groups();
	$e->set_controls();

	$s = [];

	foreach ( $e->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) {
			$s[ $k ] = $c['default'];
		}
	}

	$s                      = array_merge( $s, $over );
	$e->settings            = $s;
	$e->element['settings'] = $s;

	return $e;
}

/** The breadcrumb trail as a readable string. */
function trail( $element ) {
	$m = new ReflectionMethod( $element, 'crumbs' );
	$m->setAccessible( true );

	$out = [];

	foreach ( $m->invoke( $element ) as $c ) {
		$out[] = $c['label'] . ( '' === $c['href'] ? '*' : '' );
	}

	return implode( ' / ', $out );
}

/** Swap in a real query and restore afterwards. */
function with_query( array $args, callable $fn ) {
	global $wp_query, $wp_the_query, $post;

	$before     = $wp_query;
	$before_the = $wp_the_query;
	$before_post = $post;

	$q            = new WP_Query( $args );
	$wp_query     = $q;
	$wp_the_query = $q;

	if ( ! empty( $q->posts ) ) {
		$post = $q->posts[0];
	}

	try {
		return $fn();
	} finally {
		$wp_query     = $before;
		$wp_the_query = $before_the;
		$post         = $before_post;
	}
}

echo "\n\033[1mBreadcrumbs\033[0m\n";

// The shop page: a product post-type archive.
$shop = with_query(
	[ 'post_type' => 'product', 'posts_per_page' => 1 ],
	function () {
		return [ 'trail' => trail( head_element() ), 'is_shop' => is_shop() ];
	}
);

ok( 'the shop page is recognised', $shop['is_shop'], 'is_shop() returned false' );
ok( 'shop page reads "Home / Shop"', 'Home / Shop*' === $shop['trail'], $shop['trail'] );

// A top-level category.
$honing = with_query(
	[ 'product_cat' => 'honing', 'posts_per_page' => 1 ],
	function () {
		return trail( head_element() );
	}
);

ok( 'a top-level category still reads correctly', 'Home / Honing*' === $honing, $honing );

// A child category brings its parent along.
$child = with_query(
	[ 'product_cat' => 'premium-2-0', 'posts_per_page' => 1 ],
	function () {
		return trail( head_element() );
	}
);

ok( 'a child category includes its parent', 'Home / Gia giamas / Premium 2.0*' === $child, $child );

// An ordinary page.
$page = get_page_by_path( 'shop-test' );
$ord  = with_query(
	[ 'page_id' => $page->ID ],
	function () {
		return trail( head_element() );
	}
);

ok( 'an ordinary page names itself', 'Home / Shop test*' === $ord, $ord );

// A manual override wins everywhere.
$manual = with_query(
	[ 'post_type' => 'product', 'posts_per_page' => 1 ],
	function () {
		return trail(
			head_element(
				[
					'crumbsManual' => [
						[ 'label' => 'Winkel', 'link' => [ 'url' => 'https://example.com/winkel' ] ],
						[ 'label' => 'Honing', 'link' => [] ],
					],
				]
			)
		);
	}
);

ok( 'a typed trail overrides everything', 'Winkel / Honing*' === $manual, $manual );

echo "\n\033[1mTitle\033[0m\n";

$shop_title = with_query(
	[ 'post_type' => 'product', 'posts_per_page' => 1 ],
	function () {
		$e = head_element();
		$m = new ReflectionMethod( $e, 'title_text' );
		$m->setAccessible( true );

		return $m->invoke( $e );
	}
);

ok( 'shop page title comes from the shop page', 'Shop' === $shop_title, $shop_title );

$cat_title = with_query(
	[ 'product_cat' => 'honing', 'posts_per_page' => 1 ],
	function () {
		$e = head_element();
		$m = new ReflectionMethod( $e, 'title_text' );
		$m->setAccessible( true );

		return $m->invoke( $e );
	}
);

ok( 'category title comes from the term', 'Honing' === $cat_title, $cat_title );

echo "\n\033[1mMarkup\033[0m\n";

$html = with_query(
	[ 'post_type' => 'product', 'posts_per_page' => 1 ],
	function () {
		ob_start();
		head_element()->render();

		return ob_get_clean();
	}
);

ok( 'container is 1240px', false !== strpos( $html, '--pfh-sh-max:1240px' ) );
ok( 'text column is 551px', false !== strpos( $html, '--pfh-sh-text-max:551px' ) );
ok( 'banner is hidden on phones by default', false !== strpos( $html, 'pfh-shophead--hide-banner-sm' ) );
ok( 'banner falls back to the bundled transparent file', false !== strpos( $html, 'pfh-shop-banner.png' ) );
ok( 'the current crumb is not a link', false !== strpos( $html, 'aria-current="page"' ) );

$shown = with_query(
	[ 'post_type' => 'product', 'posts_per_page' => 1 ],
	function () {
		ob_start();
		head_element( [ 'bannerMobile' => true ] )->render();

		return ob_get_clean();
	}
);

ok( 'turning the phone banner on removes the class', false === strpos( $shown, 'hide-banner-sm' ) );

echo "\n" . str_repeat( '=', 56 ) . "\n";
printf( "\033[1m%d passed, %d failed\033[0m\n", $pass, $fail );
exit( $fail ? 1 : 0 );
