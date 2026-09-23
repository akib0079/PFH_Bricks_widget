<?php
/**
 * The article page.
 *
 * Most of what matters here is what happens to the client's own writing: the
 * headings have to get ids the contents list can point at, a wide table has to
 * stop widening the page, and none of it may change what they typed.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

/* ------------------------------------------------------------------ *
 * One article, its neighbours, and a category
 * ------------------------------------------------------------------ */

$term = term_exists( 'PFH Artikelen', 'category' ) ?: wp_insert_term( 'PFH Artikelen', 'category' );
$term = (int) ( is_array( $term ) ? $term['term_id'] : $term );

$author = email_exists( 'pfh-author@example.test' );

if ( ! $author ) {
	$author = wp_insert_user(
		[
			'user_login'   => 'pfh-author@example.test',
			'user_email'   => 'pfh-author@example.test',
			'user_pass'    => wp_generate_password(),
			'display_name' => 'Maria uit Samos',
			'description'  => 'Schrijft over honing, olijfolie en het eiland waar ze vandaan komen.',
			'role'         => 'author',
		]
	);
}

$body = '<p>De eerste alinea.</p>'
	. '<h2>Eerste kop</h2><p>Tekst onder de eerste kop.</p>'
	. '<h3>Een kop eronder</h3><p>Nog wat tekst.</p>'
	. '<h2 id="eigen-id">Kop met een eigen id</h2><p>Tekst.</p>'
	. '<h2>Eerste kop</h2><p>Dezelfde woorden, een andere kop.</p>'
	. '<table><tr><td>cel</td></tr></table>';

$article = wp_insert_post(
	[
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => 'PFH hoofdartikel',
		'post_content' => $body,
		'post_author'  => $author,
		'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
	]
);

wp_set_post_categories( $article, [ $term ] );

$older = wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'PFH ouder artikel', 'post_content' => 'Ouder.', 'post_date' => gmdate( 'Y-m-d H:i:s', strtotime( '-9 days' ) ) ] );
$newer = wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'PFH nieuwer artikel', 'post_content' => 'Nieuwer.', 'post_date' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ) ] );
wp_set_post_categories( $older, [ $term ] );
wp_set_post_categories( $newer, [ $term ] );

function article( $id, array $settings = [] ) {
	$el       = new PFH_Element_Post( [ 'id' => 'po' ] );
	$el->name = 'pfh-post';
	$el->settings = array_merge( [ 'previewId' => (string) $id ], $settings );

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

/*
 * Not $page: setup_postdata() sets a WordPress global of that name — the page
 * number within a post — so rendering an article would quietly overwrite it,
 * and every assertion after the first render would be reading an empty string.
 */
$html = article( $article );

/* ------------------------------------------------------------------ *
 * The head
 * ------------------------------------------------------------------ */

echo "── the head ──\n";
ok( 'it is drawn', false !== strpos( $html, 'class="pfh-post' ) );
ok( 'the crumbs lead back', false !== strpos( $html, 'pfh-post__crumbs' ) && false !== strpos( $html, 'PFH Artikelen' ) );
ok( '  ending on this article', false !== strpos( $html, 'aria-current="page"' ) );
ok( 'the category is a pill', false !== strpos( $html, 'pfh-post__tag' ) );
ok( 'the title is an h1', false !== strpos( $html, '<h1 class="pfh-post__title">PFH hoofdartikel</h1>' ) );
ok( 'the date is machine readable too', (bool) preg_match( '/<time datetime="\d{4}-\d{2}-\d{2}/', $html ) );
ok( 'the reading time is shown', false !== strpos( $html, 'leestijd' ) );
ok( 'the author is named', false !== strpos( $html, 'Maria uit Samos' ) );

$nobody = wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'PFH zonder auteur', 'post_content' => 'Tekst.', 'post_author' => 0 ] );
/*
 * Looked for inside the meta line: the progress bar is an empty <span> by
 * design, so searching the whole page for one would always find it.
 */
preg_match( '/<div class="pfh-post__meta">(.*?)<\/div>/s', article( $nobody ), $bare );
ok( 'an article with no author leaves the space out', ! isset( $bare[1] ) || false === strpos( $bare[1], '<span></span>' ), $bare[1] ?? 'no meta at all' );
wp_delete_post( $nobody, true );

echo "\n── the contents ──\n";
ok( 'it is built from the article\'s own headings', false !== strpos( $html, 'pfh-post__toc' ) );
ok( 'it begins folded on every viewport', false !== strpos( $html, '<details class="pfh-post__toc" data-pfh-post-toc>' ) && false === strpos( $html, 'data-pfh-post-toc open' ) );
ok( 'one line per heading', 4 === substr_count( $html, 'pfh-post__toc-link' ), substr_count( $html, 'pfh-post__toc-link' ) . ' lines' );
ok( 'a sub-heading is marked as one', false !== strpos( $html, 'pfh-post__toc-item--3' ) );
ok( 'every link points at a heading that exists', ( function () use ( $html ) {
	preg_match_all( '/href="#([^"]+)"/', $html, $links );

	foreach ( $links[1] as $id ) {
		if ( false === strpos( $html, 'id="' . $id . '"' ) ) {
			return false;
		}
	}

	return ! empty( $links[1] );
} )() );

ok( 'an id the author gave is kept', false !== strpos( $html, 'id="eigen-id"' ) );
ok( 'two headings that read the same get different ids', false !== strpos( $html, 'id="eerste-kop"' ) && false !== strpos( $html, 'id="eerste-kop-2"' ) );

/*
 * A heading is a sentence and the panel is a narrow column beside the
 * article: one long heading filled four lines of it. The label is cut at a
 * word, the whole heading stays in the link's title, and the article's own
 * heading is of course untouched.
 */
$long_text = 'Waarom extra vierge olijfolie de beste keuze is: tips voor het online kopen van olijfolie';
$long      = wp_insert_post(
	[
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => 'PFH lange koppen',
		'post_content' => '<h2>' . $long_text . '</h2><p>Tekst.</p><h2>Een korte kop</h2><p>Tekst.</p>',
	]
);
$long_page = article( $long );

preg_match_all( '/<a class="pfh-post__toc-link"[^>]*>(.*?)<\/a>/s', $long_page, $labels );
$first = isset( $labels[1][0] ) ? html_entity_decode( $labels[1][0], ENT_QUOTES, 'UTF-8' ) : '';

ok( 'a heading too long for the panel is cut down', '' !== $first && mb_strlen( $first ) <= 60, $first );
ok( '  at a word, ending in an ellipsis', '…' === mb_substr( $first, -1 ) && false === strpos( $first, ' …' ), $first );
ok( '  with the whole heading kept in the link\'s title', false !== strpos( $long_page, 'title="' . esc_attr( $long_text ) . '"' ) );
ok( '  and the article\'s own heading left as written', false !== strpos( $long_page, '>' . $long_text . '</h2>' ) );
ok( 'a heading that already fits is left alone', isset( $labels[1][1] ) && 'Een korte kop' === $labels[1][1], $labels[1][1] ?? 'no second line' );
wp_delete_post( $long, true );

echo "\n── the organised sidebar ──\n";
ok( 'the article is the left column', false !== strpos( $html, 'class="pfh-post__main"' ) );
ok( 'the tools are grouped in their own right rail', false !== strpos( $html, 'class="pfh-post__sidebar"' ) );
ok( 'the hero belongs to the article flow', false === strpos( $html, '</header><figure class="pfh-post__hero"' ) );
ok( 'search opens the shared header popup when enhanced', false !== strpos( $html, 'data-pfh-post-search' ) );
ok( '  and keeps an ordinary search URL without scripts', false !== strpos( $html, '/?s=' ) );
ok( '  with its keyboard shortcut exposed', false !== strpos( $html, 'aria-keyshortcuts="Meta+K Control+K"' ) && false !== strpos( $html, 'Cmd K' ) );
ok( 'the promises use editable repeater rows', 8 === substr_count( $html, 'pfh-post__promise-icon' ), substr_count( $html, 'pfh-post__promise-icon' ) . ' rows' );
ok( 'the shop call to action uses its supplied image', false !== strpos( $html, 'generated-image-6-1024x1024.webp' ) );
ok( '  and leads to the shop', false !== strpos( $html, 'pfh-post__cta-button' ) );
ok( 'live products fill the popular products shelf', false !== strpos( $html, 'data-pfh-post-products' ) && false !== strpos( $html, 'pfh-post__product-card' ) );

$shallow = article( $article, [ 'tocDepth' => 'h2' ] );
ok( 'it can list the main headings only', 3 === substr_count( $shallow, 'pfh-post__toc-link' ), substr_count( $shallow, 'pfh-post__toc-link' ) . ' lines' );

$thin = wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'PFH kort', 'post_content' => '<p>Geen koppen.</p>' ] );
$thin_page = article( $thin );
ok( 'an article with no headings gets no contents', false === strpos( $thin_page, 'pfh-post__toc' ) );
ok( '  while its other sidebar tools remain', false !== strpos( $thin_page, 'pfh-post__sidebar' ) && false === strpos( $thin_page, 'pfh-post__layout--wide' ) );
$wide_page = article( $thin, [ 'showSearch' => false, 'showProducts' => false, 'showPromises' => false, 'showCta' => false ] );
ok( 'the article takes the full width when every sidebar module is disabled', false !== strpos( $wide_page, 'pfh-post__layout--wide' ) && false === strpos( $wide_page, 'pfh-post__sidebar' ) );
ok( 'the contents can be switched off entirely', false === strpos( article( $article, [ 'showToc' => false ] ), 'pfh-post__toc' ) );
wp_delete_post( $thin, true );

echo "\n── the article itself ──\n";
ok( 'the writing comes through', false !== strpos( $html, 'Tekst onder de eerste kop' ) );
ok( 'a wide table is put in something that scrolls', false !== strpos( $html, 'pfh-post__scroller' ) );
ok( '  with the table still inside it', (bool) preg_match( '/pfh-post__scroller"><table/', $html ) );

echo "\n── under the article ──\n";
ok( 'there is a way to share it', false !== strpos( $html, 'pfh-post__share' ) );
ok( '  on each of the four', 4 === substr_count( $html, 'pfh-post__share-link" href' ), substr_count( $html, 'pfh-post__share-link" href' ) . ' links' );
ok( '  with the article\'s own address', false !== strpos( $html, rawurlencode( (string) get_permalink( $article ) ) ) );
ok( '  none of which follow it', 4 === substr_count( $html, 'nofollow' ) );
ok( 'and a button to copy the link', false !== strpos( $html, 'data-pfh-post-copy' ) );

ok( 'the author can introduce themselves', false !== strpos( article( $article, [ 'showBio' => true ] ), 'Schrijft over honing' ) );
ok( '  and is left out by default', false === strpos( $html, 'pfh-post__author' ) );

echo "\n── what to read next ──\n";
/*
 * Asserted through the function rather than the markup: a real blog has other
 * posts, and the nearest neighbours by date are whichever those turn out to
 * be — which is exactly the contract.
 */
$subject  = get_post( $article );
$previous = PFH_Widgets_Post_Body::adjacent( $subject, true );
$next     = PFH_Widgets_Post_Body::adjacent( $subject, false );

ok( 'the article before it is older', $previous && $previous->post_date < $subject->post_date );
ok( '  and nothing published sits between them', $previous && 0 === count( get_posts( [
	'post_type'   => 'post',
	'post_status' => 'publish',
	'exclude'     => [ (int) $subject->ID, (int) $previous->ID ],
	'date_query'  => [ [ 'after' => $previous->post_date, 'before' => $subject->post_date, 'inclusive' => false, 'column' => 'post_date' ] ],
] ) ) );

ok( 'the one after it is newer', $next && $next->post_date > $subject->post_date );
ok( '  and nothing sits between those either', $next && 0 === count( get_posts( [
	'post_type'   => 'post',
	'post_status' => 'publish',
	'exclude'     => [ (int) $subject->ID, (int) $next->ID ],
	'date_query'  => [ [ 'after' => $subject->post_date, 'before' => $next->post_date, 'inclusive' => false, 'column' => 'post_date' ] ],
] ) ) );

ok( 'both are drawn under the article', 2 === substr_count( $html, '<a class="pfh-post__nav-link' ), substr_count( $html, '<a class="pfh-post__nav-link' ) . ' links' );

$related = PFH_Widgets_Post_Body::related( get_post( $article ), 3 );
ok( 'related articles are found', count( $related ) > 0 );
ok( '  never the one being read', ! in_array( (int) $article, wp_list_pluck( $related, 'ID' ), true ) );
ok( 'they are drawn as the blog\'s own cards', false !== strpos( $html, 'pfh-blog__card' ) );
ok( '  carrying the blog\'s own settings, so the two cannot drift apart', false !== strpos( $html, 'class="pfh-blog__grid pfh-blog-cards"' ) );
/*
 * The inline value is the starting column count. Written as --pfh-bl-cols it
 * would have beaten the blog's own breakpoints, and a phone would have got
 * three cards side by side at 109px each.
 */
ok( '  with a column count the breakpoints can still cut down', false !== strpos( $html, '--pfh-bl-cols-set:' ) && false === strpos( $html, '--pfh-bl-cols:' ) );

echo "\n── every part can go ──\n";
foreach ( [
	'showCrumbs'  => 'pfh-post__crumbs',
	'showTag'     => 'pfh-post__tag',
	'showHero'    => 'pfh-post__hero',
	'showSearch'  => 'pfh-post__search',
	'showProducts' => 'pfh-post__products',
	'showPromises' => 'pfh-post__promises',
	'showCta'     => 'pfh-post__cta',
	'showShare'   => 'pfh-post__share',
	'showNav'     => 'pfh-post__nav',
	'showRelated' => 'pfh-post__related',
	'showProgress' => 'pfh-post__progress',
] as $control => $needle ) {
	ok( "  $control", false === strpos( article( $article, [ $control => false ] ), $needle ) );
}

echo "\n── with no article at all ──\n";
/*
 * Every post is deleted for this one check, because "no article" is a state a
 * page can genuinely be in and it must not be a fatal.
 */
$blank = ( function () {
	add_filter( 'pfh_post_none', '__return_true' );
	$el = new PFH_Element_Post( [ 'id' => 'po' ] );
	$el->name = 'pfh-post';
	$el->settings = [ 'previewId' => '99999999' ];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
} )();

ok( 'nothing is drawn rather than something broken', '' === trim( $blank ) || false !== strpos( $blank, 'pfh-post--empty' ) );

/* ------------------------------------------------------------------ *
 * House rules
 * ------------------------------------------------------------------ */

echo "\n── it behaves like the rest of the plugin ──\n";
$el = new PFH_Element_Post( [ 'id' => 'c' ] );
$el->name = 'pfh-post';
$el->set_control_groups();
$el->set_controls();

ok( 'it encodes its controls for the builder', false !== wp_json_encode( $el->controls ) );
ok( 'and holds no 4-byte character', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', wp_json_encode( $el->controls, JSON_UNESCAPED_UNICODE ) ) );

$missing = [];

foreach ( $el->controls as $key => $control ) {
	if ( ! isset( $control['tab'] ) ) { $missing[] = $key; }
}

ok( 'every control declares its tab', ! $missing, implode( ', ', $missing ) );

$panel = ( function () use ( $article ) {
	$el = new PFH_Element_Post( [ 'id' => 'po' ] );
	$el->name = 'pfh-post';
	$el->set_control_groups();
	$el->set_controls();
	$el->settings = [ 'previewId' => (string) $article ];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
} )();

ok( 'it renders the same without the controls built', $panel === article( $article ) );

/*
 * Rendering an article must not leave the page pointing at it: anything drawn
 * after this element would otherwise inherit the wrong post.
 */
$before = isset( $GLOBALS['post'] ) ? (int) $GLOBALS['post']->ID : 0;
article( $article );
$after = isset( $GLOBALS['post'] ) ? (int) $GLOBALS['post']->ID : 0;

ok( 'and leaves the loop where it found it', $before === $after, "was $before, now $after" );

/* ---- put the blog back ---- */
foreach ( [ $article, $older, $newer ] as $id ) {
	wp_delete_post( $id, true );
}

wp_delete_term( $term, 'category' );

require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $author );

$left = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_title LIKE 'PFH %artikel%' OR post_title = 'PFH kort'" );
ok( 'the blog is left as it was found', 0 === $left, "$left left behind" );

echo "\n$pass passed, $fail failed\n";
