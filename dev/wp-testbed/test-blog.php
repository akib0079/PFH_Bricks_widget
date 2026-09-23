<?php
/**
 * The blog.
 *
 * Two things are worth more than the markup checks: that the load-more
 * endpoint cannot be talked into showing anything the first page would not
 * have, and that the card it returns is the same card the page drew.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

/* ------------------------------------------------------------------ *
 * Eight posts, in two categories
 * ------------------------------------------------------------------ */

$made = [ 'posts' => [], 'terms' => [] ];

foreach ( [ 'PFH Honing', 'PFH Olijven' ] as $name ) {
	$term = term_exists( $name, 'category' ) ?: wp_insert_term( $name, 'category' );
	$made['terms'][ $name ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
}

for ( $i = 0; $i < 8; $i++ ) {
	$id = wp_insert_post(
		[
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => 'PFH bericht ' . ( $i + 1 ),
			'post_content' => str_repeat( 'Woord ', 420 ),
			'post_excerpt' => 0 === $i ? 'Een eigen samenvatting van het eerste bericht.' : '',
			'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( $i + 1 ) . ' days' ) ),
		]
	);

	wp_set_post_categories( $id, [ $made['terms'][ $i < 5 ? 'PFH Honing' : 'PFH Olijven' ] ] );
	$made['posts'][] = $id;
}

$honing = $made['terms']['PFH Honing'];

function blog( array $settings = [] ) {
	$el       = new PFH_Element_Blog( [ 'id' => 'bl' ] );
	$el->name = 'pfh-blog';
	$el->settings = $settings;

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ *
 * The page
 * ------------------------------------------------------------------ */

$page = blog( [ 'perPage' => 6 ] );

echo "── the blog ──\n";
ok( 'it is drawn', false !== strpos( $page, 'class="pfh-blog' ) );
ok( 'with the opening', false !== strpos( $page, 'pfh-blog__eyebrow' ) && false !== strpos( $page, 'pfh-blog__title' ) );
ok( '  and a word in the italic serif', false !== strpos( $page, '<em>Griekenland</em>' ) );
ok( 'six cards, as asked for', 6 === substr_count( $page, '<li class="pfh-blog__card"' ), substr_count( $page, '<li class="pfh-blog__card"' ) . ' cards' );
ok( 'newest first', strpos( $page, 'PFH bericht 1' ) < strpos( $page, 'PFH bericht 2' ) );
ok( 'each card links to its post', 6 <= substr_count( $page, 'pfh-blog__card-title' ) );

echo "\n── what a card carries ──\n";
ok( 'the date', false !== strpos( $page, 'pfh-blog__meta' ) );
ok( 'the reading time', false !== strpos( $page, 'leestijd' ) );
ok( '  worked out from the post', 3 === PFH_Widgets_Blog::reading_time( get_post( $made['posts'][0] ) ), PFH_Widgets_Blog::reading_time( get_post( $made['posts'][0] ) ) . ' minutes for 420 words' );
ok( 'the category as a pill', false !== strpos( $page, 'pfh-blog__tag' ) && false !== strpos( $page, 'PFH Honing' ) );
ok( 'a summary', false !== strpos( $page, 'pfh-blog__excerpt' ) );
ok( '  and the post\'s own when it has one', false !== strpos( $page, 'Een eigen samenvatting' ) );
ok( 'a link under it', false !== strpos( $page, 'Lees verder' ) );

echo "\n── every part can go ──\n";
ok( 'the picture', false === strpos( blog( [ 'showImage' => false ] ), 'pfh-blog__media' ) );
ok( 'the pill', false === strpos( blog( [ 'showTag' => false ] ), 'pfh-blog__tag' ) );
ok( 'the date and the reading time', false === strpos( blog( [ 'showDate' => false, 'showRead' => false ] ), 'pfh-blog__meta' ) );
ok( 'the summary', false === strpos( blog( [ 'showExcerpt' => false ] ), 'pfh-blog__excerpt' ) );
ok( 'and the link', false === strpos( blog( [ 'moreLabel' => '' ] ), 'pfh-blog__more' ) );

$short = blog( [ 'excerptWords' => 8, 'perPage' => 2 ] );
preg_match( '/<p class="pfh-blog__excerpt">(.*?)<\/p>/s', $short, $cut );
ok( 'the summary is cut where asked', isset( $cut[1] ) && str_word_count( wp_strip_all_tags( $cut[1] ) ) <= 9, wp_strip_all_tags( $cut[1] ?? '' ) );

echo "\n── the lead article ──\n";
$lead = blog( [ 'lead' => true, 'perPage' => 4 ] );
ok( 'the first post is given the width', false !== strpos( $lead, 'pfh-blog__lead' ) );
/*
 * Asked of the install rather than assumed: a fresh WordPress has its own
 * "Hello world!" dated the moment it was installed, which is newer than any
 * fixture dated in the past.
 */
$newest = get_posts( [ 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC' ] );
ok( '  and is the newest one', $newest && false !== strpos( substr( $lead, 0, (int) strpos( $lead, 'pfh-blog__grid' ) ), esc_html( get_the_title( $newest[0] ) ) ), $newest ? get_the_title( $newest[0] ) : 'no posts' );
ok( 'the rest fill the grid', 3 === substr_count( substr( $lead, (int) strpos( $lead, 'pfh-blog__grid' ) ), '<li class="pfh-blog__card"' ) );
ok( 'and the text is boxed so it can sit beside the picture', false !== strpos( $lead, 'pfh-blog__card-body' ) );

echo "\n── choosing a category ──\n";
$one = blog( [ 'category' => (string) $honing, 'perPage' => 10 ] );
ok( 'only that category is shown', 5 === substr_count( $one, '<li class="pfh-blog__card"' ), substr_count( $one, '<li class="pfh-blog__card"' ) . ' cards' );
ok( '  and nothing from the other', false === strpos( $one, 'PFH bericht 6' ) );

ok( 'the categories can be listed', false !== strpos( blog( [ 'showTerms' => true ] ), 'pfh-blog__terms' ) );
ok( '  with a link for each', false !== strpos( blog( [ 'showTerms' => true ] ), 'PFH Olijven' ) );

echo "\n── more posts ──\n";
ok( 'the button is there when there are more', false !== strpos( $page, 'data-pfh-blog-load' ) );
ok( '  and is a real link, for a browser with no scripting', (bool) preg_match( '/<a class="pfh-blog__load" href="http/', $page ) );
ok( '  carrying the page it would fetch', false !== strpos( $page, 'data-pfh-blog-page="2"' ) );
ok( 'page numbers instead, if asked', false !== strpos( blog( [ 'pager' => 'numbers' ] ), 'pfh-blog__pager' ) );
ok( 'or nothing at all', false === strpos( blog( [ 'pager' => 'none' ] ), 'pfh-blog__foot' ) );
ok( 'and nothing when every post already fits', false === strpos( blog( [ 'perPage' => 24 ] ), 'pfh-blog__foot' ) );

echo "\n── with no posts to show ──\n";
$none = blog( [ 'category' => '999999' ] );
ok( 'it says so rather than drawing an empty grid', false !== strpos( $none, 'pfh-blog__none' ) && false === strpos( $none, '<li class="pfh-blog__card"' ) );
ok( '  in the client\'s own words', false !== strpos( $none, 'Kom snel weer terug' ) );

/* ------------------------------------------------------------------ *
 * Load more
 * ------------------------------------------------------------------ */

echo "\n── the load-more endpoint ──\n";

function ajax( array $post ) {
	$_POST    = $post;
	$_REQUEST = $post;

	$handler = static function () {
		return static function () { throw new RuntimeException( 'wp_die' ); };
	};

	add_filter( 'wp_doing_ajax', '__return_true' );
	add_filter( 'wp_die_ajax_handler', $handler );

	ob_start();

	try {
		PFH_Widgets_Blog::ajax();
	} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
		// wp_send_json always ends in wp_die; the body is already buffered.
	}

	$body = (string) ob_get_clean();

	remove_filter( 'wp_doing_ajax', '__return_true' );
	remove_filter( 'wp_die_ajax_handler', $handler );

	return json_decode( $body, true );
}

$nonce = wp_create_nonce( PFH_Widgets_Ajax::NONCE );
$travel = wp_json_encode( PFH_Widgets_Blog::travelling( PFH_Widgets_Blog::options( [ 'per_page' => 6 ] ) ) );

$second = ajax( [ 'nonce' => $nonce, 'page' => 2, 'query' => $travel ] );
ok( 'page two comes back', ! empty( $second['success'] ) );
ok( '  as cards, the same ones the page draws', ! empty( $second['data']['html'] ) && false !== strpos( $second['data']['html'], 'pfh-blog__card' ) );
ok( '  holding the posts the first page did not', false !== strpos( $second['data']['html'] ?? '', 'PFH bericht 7' ) );
/*
 * Derived, not assumed: a real blog has posts these tests did not make, and
 * whether page three exists depends on how many.
 */
$pages = (int) PFH_Widgets_Blog::query( PFH_Widgets_Blog::options( [ 'per_page' => 6, 'page' => 2 ] ) )->max_num_pages;
ok( '  and says whether there is any more', isset( $second['data']['more'] ) && ( 2 < $pages ) === $second['data']['more'], 'said ' . var_export( $second['data']['more'] ?? null, true ) . ' with ' . $pages . ' pages' );

ok( 'page one is not something to fetch', empty( ajax( [ 'nonce' => $nonce, 'page' => 1, 'query' => $travel ] )['success'] ) );
ok( 'and a request with no nonce is not answered', empty( ajax( [ 'page' => 2, 'query' => $travel ] )['success'] ) );

echo "\n── and it cannot be talked into more than that ──\n";
$greedy = ajax(
	[
		'nonce' => $nonce,
		'page'  => 2,
		'query' => wp_json_encode( [ 'per_page' => 999, 'words' => 5000, 'source' => 'current' ] ),
	]
);

ok( 'a huge page size is clamped', ! empty( $greedy['success'] ) && substr_count( $greedy['data']['html'], '<li class="pfh-blog__card"' ) <= 24, substr_count( $greedy['data']['html'] ?? '', '<li class="pfh-blog__card"' ) . ' cards' );

$clamped = PFH_Widgets_Blog::options( [ 'per_page' => 999, 'words' => 5000, 'source' => 'nonsense' ] );
ok( '  to twenty-four at most', 24 === $clamped['per_page'] );
ok( 'the summary length is clamped too', 80 === $clamped['words'] );
ok( 'and an unknown source falls back to the latest posts', 'latest' === $clamped['source'] );

/* ------------------------------------------------------------------ *
 * House rules
 * ------------------------------------------------------------------ */

echo "\n── it behaves like the rest of the plugin ──\n";
$el = new PFH_Element_Blog( [ 'id' => 'c' ] );
$el->name = 'pfh-blog';
$el->set_control_groups();
$el->set_controls();

ok( 'it encodes its controls for the builder', false !== wp_json_encode( $el->controls ) );
ok( 'and holds no 4-byte character', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', wp_json_encode( $el->controls, JSON_UNESCAPED_UNICODE ) ) );

$missing = [];

foreach ( $el->controls as $key => $control ) {
	if ( ! isset( $control['tab'] ) ) { $missing[] = $key; }
}

ok( 'every control declares its tab', ! $missing, implode( ', ', $missing ) );

/*
 * Bricks checks a select's saved value against its options, so the category
 * list has to be built on the front end as well as in the builder.
 */
ok( 'the category list is built outside the builder too', count( PFH_Widgets_Blog::category_options() ) > 1 );

$panel = ( function () {
	$el = new PFH_Element_Blog( [ 'id' => 'bl' ] );
	$el->name = 'pfh-blog';
	$el->set_control_groups();
	$el->set_controls();
	$el->settings = [ 'perPage' => 6 ];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
} )();

ok( 'it renders the same without the controls built', $panel === blog( [ 'perPage' => 6 ] ) );

/* ---- put the blog back ---- */
foreach ( $made['posts'] as $id ) {
	wp_delete_post( $id, true );
}

foreach ( $made['terms'] as $term_id ) {
	wp_delete_term( $term_id, 'category' );
}

$left = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type = 'post' AND post_title LIKE 'PFH bericht%'" );
ok( 'the blog is left as it was found', 0 === $left, "$left posts left behind" );

echo "\n$pass passed, $fail failed\n";
