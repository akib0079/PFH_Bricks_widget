<?php
/**
 * The shop-description fallback: does each page get copy of its own?
 *
 * One paragraph repeated across every category is duplicate content, so the
 * point of the fallback is that it reads differently per page — that is what
 * is checked here, not merely that something rendered.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-shopdesc.php';

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function render_on( $slug, array $over = [] ) {
	if ( $slug ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		$_SERVER['REQUEST_URI'] = wp_parse_url( get_term_link( $t ), PHP_URL_PATH );
		query_posts( [ 'post_type' => 'product', 'product_cat' => $slug, 'posts_per_page' => 3 ] );
	} else {
		$_SERVER['REQUEST_URI'] = '/shop/';
		query_posts( [ 'post_type' => 'product', 'posts_per_page' => 3 ] );
	}

	$el = new PFH_Element_Shopdesc( [ 'id' => 'fb' . ( $slug ? $slug : 'shop' ) ] );
	$el->name = 'pfh-shopdesc';
	$el->set_control_groups();
	$el->set_controls();

	$s = [];
	foreach ( $el->controls as $k => $c ) { if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; } }
	$el->settings = array_merge( $s, $over );

	ob_start(); $el->render(); $html = ob_get_clean();
	wp_reset_query();

	return $html;
}

function heading( $h ) { preg_match( '#pfh-shopdesc__title">([^<]*)#', $h, $m ); return $m[1] ?? ''; }
function body( $h )    { preg_match( '#pfh-shopdesc__body[^>]*>(.*?)</div>#s', $h, $m ); return trim( wp_strip_all_tags( $m[1] ?? '' ) ); }

echo "── every category gets its own copy ──\n";
$terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 3 ] );
$heads = []; $bodies = [];

foreach ( $terms as $t ) {
	$h = render_on( $t->slug );
	$head = heading( $h ); $bod = body( $h );
	$heads[] = $head; $bodies[] = $bod;

	echo "  {$t->name}:\n";
	ok( "    heading names the category", false !== strpos( $head, $t->name ), $head );
	ok( "    heading names the shop", false !== strpos( $head, get_bloginfo( 'name' ) ) );
	ok( "    body names the category", false !== strpos( $bod, $t->name ) );
	ok( "    no template token survives", false === strpos( $head . $bod, '%' ), 'a %token% leaked through' );
	ok( "    body is long enough to index", str_word_count( $bod ) >= 40, str_word_count( $bod ) . ' words' );
}

ok( 'the headings differ from one another', count( array_unique( $heads ) ) === count( $heads ) );
ok( 'the bodies differ from one another', count( array_unique( $bodies ) ) === count( $bodies ) );

echo "\n── the shop page gets its own line ──\n";
$h = render_on( '' );
ok( 'shop heading is the shop variant', false !== strpos( heading( $h ), 'Griekse delicatessen' ), heading( $h ) );
ok( 'shop heading is not a category heading', false === strpos( heading( $h ), 'kopen bij' ) );
ok( 'shop body differs from a category body', body( $h ) !== $bodies[0] );

echo "\n── a real description always wins ──\n";
$t = $terms[0];
wp_update_term( $t->term_id, 'product_cat', [ 'description' => 'Dit is de echte categorieomschrijving uit WordPress.' ] );
$h = render_on( $t->slug );
ok( 'the term description is used instead of the fallback', false !== strpos( body( $h ), 'echte categorieomschrijving' ) );
wp_update_term( $t->term_id, 'product_cat', [ 'description' => '' ] );

echo "\n── typed text wins over the fallback ──\n";
$h = render_on( $terms[1]->slug, [ 'body' => '<p>Handmatig getypte tekst.</p>' ] );
ok( 'typed body is used', false !== strpos( body( $h ), 'Handmatig getypte tekst' ) );
$h = render_on( $terms[1]->slug, [ 'title' => 'Eigen kop' ] );
ok( 'typed heading is used', 'Eigen kop' === heading( $h ) );

echo "\n── the fallback can be switched off ──\n";
$h = render_on( $terms[2]->slug, [ 'fallbackEnable' => false ] );
ok( 'nothing renders with no source and no fallback', '' === trim( $h ), substr( $h, 0, 80 ) );

echo "\n$pass passed, $fail failed\n";
