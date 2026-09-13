<?php
require __DIR__ . '/stubs.php';

class WP_Query {
	public $posts = [];
	public $post_count = 0;
	private $i = 0;
	public function __construct( $args = [] ) {
		$GLOBALS['pfh_last_query_args'] = $args;
		if ( ! empty( $GLOBALS['pfh_fake_posts'] ) ) {
			$this->posts = $GLOBALS['pfh_fake_posts'];
			$this->post_count = count( $this->posts );
		}
	}
	public function have_posts() { return $this->i < $this->post_count; }
	public function the_post() { $GLOBALS['pfh_current'] = $this->posts[ $this->i++ ]; }
}
function get_the_ID() { return $GLOBALS['pfh_current']->ID; }
function wc_get_product( $id ) { return null; }

$dir = '/Users/macm1pro/Bricks Integration widgets/pfh-bricks-widgets/';
define( 'PFH_WIDGETS_VERSION', '1.0.0' ); define( 'PFH_WIDGETS_FILE', $dir . 'x.php' );
define( 'PFH_WIDGETS_DIR', $dir ); define( 'PFH_WIDGETS_URL', './' );
foreach ( [ 'includes/class-pfh-helpers.php', 'includes/class-pfh-icons.php', 'includes/class-pfh-cart.php', 'includes/class-pfh-ajax.php' ] as $f ) require $dir . $f;

function run( $label, callable $fn ) {
	ob_start();
	try { $fn(); $out = ob_get_clean(); printf( "%-42s OK  %s\n", $label, substr( str_replace( "\n", ' ', $out ), 0, 90 ) ); }
	catch ( PFH_Test_Die $e ) { $out = ob_get_clean(); printf( "%-42s OK  [%s] %s\n", $label, $e->getMessage(), substr( str_replace( "\n", ' ', $out ), 0, 80 ) ); }
	catch ( \Throwable $e ) { ob_end_clean(); printf( "%-42s FAIL %s\n", $label, $e->getMessage() ); }
}

run( 'search: term too short', function () {
	$_POST = [ 'term' => 'a', 'nonce' => 'x' ];
	PFH_Widgets_Ajax::search();
} );

run( 'search: no results', function () {
	$GLOBALS['pfh_fake_posts'] = [];
	$_POST = [ 'term' => 'honing', 'postType' => 'product', 'limit' => 6, 'nonce' => 'x' ];
	PFH_Widgets_Ajax::search();
} );

run( 'search: with results', function () {
	$GLOBALS['pfh_fake_posts'] = [ (object) [ 'ID' => 11 ], (object) [ 'ID' => 12 ] ];
	$_POST = [ 'term' => 'honing', 'postType' => 'product', 'limit' => 6, 'nonce' => 'x' ];
	PFH_Widgets_Ajax::search();
} );

run( 'search: product tax_query present', function () {
	$GLOBALS['pfh_fake_posts'] = [];
	$_POST = [ 'term' => 'olie', 'postType' => 'product', 'nonce' => 'x' ];
	try { PFH_Widgets_Ajax::search(); } catch ( PFH_Test_Die $e ) {}
	if ( empty( $GLOBALS['pfh_last_query_args']['tax_query'] ) ) throw new Exception( 'missing product_visibility tax_query' );
	if ( 'product' !== $GLOBALS['pfh_last_query_args']['post_type'] ) throw new Exception( 'wrong post type' );
	echo 'post_type=product, product_visibility excluded';
} );

run( 'search: limit is clamped', function () {
	$GLOBALS['pfh_fake_posts'] = [];
	$_POST = [ 'term' => 'olie', 'limit' => 9999, 'nonce' => 'x' ];
	try { PFH_Widgets_Ajax::search(); } catch ( PFH_Test_Die $e ) {}
	if ( 20 !== $GLOBALS['pfh_last_query_args']['posts_per_page'] ) throw new Exception( 'limit not clamped: ' . $GLOBALS['pfh_last_query_args']['posts_per_page'] );
	echo 'posts_per_page=' . $GLOBALS['pfh_last_query_args']['posts_per_page'];
} );

run( 'cart: WooCommerce absent -> error', function () {
	$_POST = [ 'command' => 'refresh', 'nonce' => 'x' ];
	PFH_Widgets_Ajax::cart();
} );

run( 'cart: render_body without Woo', function () {
	echo strip_tags( PFH_Widgets_Cart::render_body( [] ) );
} );

run( 'cart: render_foot without Woo', function () {
	echo '[' . PFH_Widgets_Cart::render_foot( [] ) . ']';
} );

run( 'cart: labels merge keeps overrides', function () {
	$l = PFH_Widgets_Cart::labels( [ 'checkout' => 'Bestellen', 'empty' => '' ] );
	if ( 'Bestellen' !== $l['checkout'] ) throw new Exception( 'override lost' );
	if ( '' === $l['empty'] ) throw new Exception( 'empty override should fall back to default' );
	echo 'checkout=' . $l['checkout'];
} );

run( 'helpers: css_vars strips attribute breakout', function () {
	$css = PFH_Widgets_Helpers::css_vars( [ '--a' => '#fff', '--b' => '" onload="alert(1)', '--c' => '', '--d' => null ] );
	if ( false !== strpos( $css, '"' ) ) throw new Exception( 'quote survived: ' . $css );
	echo $css;
} );

run( 'helpers: parse_lines', function () {
	$rows = PFH_Widgets_Helpers::parse_lines( "A | /a | img.png\n\n  B|/b  \n" );
	if ( 2 !== count( $rows ) || 'img.png' !== $rows[0][2] || 'B' !== $rows[1][0] ) throw new Exception( 'bad parse: ' . json_encode( $rows ) );
	echo json_encode( $rows );
} );

run( 'helpers: link resolves newTab + rel', function () {
	$l = PFH_Widgets_Helpers::link( [ 'type' => 'external', 'url' => 'https://x.test', 'newTab' => true ] );
	if ( '_blank' !== $l['target'] || false === strpos( $l['rel'], 'noopener' ) ) throw new Exception( 'bad link' );
	echo PFH_Widgets_Helpers::link_attrs( $l );
} );

run( 'helpers: link falls back', function () {
	$l = PFH_Widgets_Helpers::link( null, 'https://fallback.test' );
	if ( 'https://fallback.test' !== $l['href'] ) throw new Exception( 'no fallback' );
	echo $l['href'];
} );
