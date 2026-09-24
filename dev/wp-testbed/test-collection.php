<?php
/**
 * The collection page.
 *
 * Everything under a category's product grid, and the grid's own paging:
 * page two of a category has to be page two of that category, and each
 * section a category can switch off or rewrite has to do exactly that and
 * nothing more — a category that was never touched shows what it always did.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

/**
 * Make WordPress believe it is showing one category's archive, the way the
 * real request would.
 *
 * @param string $slug Category slug, or '' for the shop.
 */
function on_category( $slug ) {
	global $wp_query, $wp_the_query;

	$wp_query     = new WP_Query( '' !== $slug ? [ 'product_cat' => $slug ] : [ 'post_type' => 'product' ] );
	$wp_the_query = $wp_query;

	// The address the visitor asked for, which is what the pager builds on.
	$link = '' !== $slug ? get_term_link( $slug, 'product_cat' ) : get_permalink( wc_get_page_id( 'shop' ) );
	$_SERVER['REQUEST_URI'] = is_string( $link ) ? (string) wp_parse_url( $link, PHP_URL_PATH ) : '/';
}

/**
 * The ids of the products a rendered grid shows.
 *
 * @param string $html Markup.
 * @return int[]
 */
function shown( $html ) {
	preg_match_all( '/data-product[_-]id="(\d+)"/', $html, $m );

	return array_values( array_unique( array_map( 'intval', $m[1] ) ) );
}

/**
 * Everything in one category, children included.
 *
 * @param string $slug Category slug.
 * @return int[]
 */
function products_in( $slug ) {
	return get_posts(
		[
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => [ [ 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $slug, 'include_children' => true ] ],
		]
	);
}

function archive( $id, array $over = [] ) {
	$el       = new PFH_Element_Archive( [ 'id' => $id ] );
	$el->name = 'pfh-archive';
	$el->set_control_groups();
	$el->set_controls();

	$s = [];

	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) {
			$s[ $k ] = $c['default'];
		}
	}

	$el->settings = array_merge( $s, [ 'perPage' => 4 ], $over );
	$el->element  = [ 'id' => $id, 'name' => 'pfh-archive', 'settings' => $el->settings ];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

/** The context token the page carries, as the browser would send it back. */
function context_of( $html ) {
	return preg_match( '/data-pfh-arch-ctx="([^"]+)"/', $html, $m ) ? html_entity_decode( $m[1] ) : '';
}

echo "── page two of a category is page two of that category ──\n";

$gia = products_in( 'gia-giamas' );

on_category( 'gia-giamas' );
$first = archive( 'colpage' );

ok( 'the first page is the category', shown( $first ) && ! array_diff( shown( $first ), $gia ), implode( ',', shown( $first ) ) );
ok( 'the page carries its own context', '' !== context_of( $first ) );

/*
 * The request the pager makes. It runs through admin-ajax.php, where
 * WordPress is not showing any archive at all — which is exactly why the
 * category used to be lost there.
 */
on_category( '' );
$ctx  = context_of( $first );
$page = PFH_Element_Archive::ajax_render( null, 'colpage', [ 'pfh_page' => '3', 'pfh_ctx' => $ctx ] );

ok( 'page three renders', is_array( $page ) && ! empty( $page['results'] ) );
ok( '  with only that category\'s products', is_array( $page ) && shown( $page['results'] ) && ! array_diff( shown( $page['results'] ), $gia ), is_array( $page ) ? implode( ',', array_diff( shown( $page['results'] ), $gia ) ) . ' do not belong' : '' );
ok( '  and the count is still the category\'s', is_array( $page ) && false !== strpos( $page['count'], (string) count( $gia ) ), is_array( $page ) ? $page['count'] : '' );
ok( '  and the address still the category\'s', is_array( $page ) && false !== strpos( $page['url'], 'gia-giamas' ) && false !== strpos( $page['url'], 'pfh_page=3' ), is_array( $page ) ? $page['url'] : '' );

/*
 * One Bricks template draws every category, so every category shares the
 * element id. Another shopper opening a different category in between must
 * not change what this shopper's next page shows.
 */
on_category( 'honing' );
archive( 'colpage' );

on_category( '' );
$after = PFH_Element_Archive::ajax_render( null, 'colpage', [ 'pfh_page' => '2', 'pfh_ctx' => $ctx ] );

ok( 'another category opened in between changes nothing', is_array( $after ) && shown( $after['results'] ) && ! array_diff( shown( $after['results'] ), $gia ), is_array( $after ) ? implode( ',', shown( $after['results'] ) ) : '' );
ok( '  not even the address', is_array( $after ) && false !== strpos( $after['url'], 'gia-giamas' ), is_array( $after ) ? $after['url'] : '' );

echo "\n── the context cannot be forged ──\n";

$parts   = explode( '.', $ctx );
$forged  = base64_encode( wp_json_encode( [ 'cat' => 'honing', 'base' => home_url( '/product-category/honing/' ) ] ) ) . '.' . ( isset( $parts[1] ) ? $parts[1] : '' );
$tamper  = PFH_Element_Archive::ajax_render( null, 'colpage', [ 'pfh_page' => '2', 'pfh_ctx' => $forged ] );
$honing  = products_in( 'honing' );

ok( 'a context with the wrong signature is not believed', ! is_array( $tamper ) || ! shown( $tamper['results'] ) || array_diff( shown( $tamper['results'] ), $honing ), 'a forged token was obeyed' );

echo "\n── the shop is still the whole shop ──\n";

on_category( '' );
$shop = archive( 'shoppage' );
$all  = PFH_Element_Archive::ajax_render( null, 'shoppage', [ 'pfh_page' => '2', 'pfh_ctx' => context_of( $shop ) ] );

ok( 'page two of the shop renders', is_array( $all ) && ! empty( $all['results'] ) );
ok( '  and is not narrowed to any category', is_array( $all ) && false !== strpos( $all['count'], (string) count( get_posts( [ 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' ] ) ) ), is_array( $all ) ? $all['count'] : '' );

/* ===================================================================== *
 * The sections a category can take over
 * ================================================================== */

function draw( $class, array $settings = [] ) {
	$el           = new $class( [ 'id' => 'sec' . wp_rand( 1, 99999 ) ] );
	$el->settings = $settings;
	$el->element  = [ 'id' => 'sec', 'name' => $el->name, 'settings' => $settings ];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

function money( $amount ) {
	return trim( html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' ) );
}

$term = get_term_by( 'slug', 'gia-giamas', 'product_cat' );
delete_term_meta( $term->term_id, PFH_Widgets_Collection::META );

echo "\n── a category nobody has touched looks as it always did ──\n";

on_category( '' );
$plain = [
	'notice'    => draw( 'PFH_Element_Notice' ),
	'faq'       => draw( 'PFH_Element_Faq' ),
	'highlight' => draw( 'PFH_Element_Highlight' ),
];

on_category( 'gia-giamas' );

ok( 'the inspiration band', false !== strpos( draw( 'PFH_Element_Notice' ), 'Inspiratie nodig?' ) );
ok( 'the questions', false !== strpos( draw( 'PFH_Element_Faq' ), 'Veelgestelde Vragen' ) );
ok( 'the bundle, with the price typed in Bricks', false !== strpos( draw( 'PFH_Element_Highlight' ), '€ 41,97' ) && false !== strpos( draw( 'PFH_Element_Highlight' ), 'Proefpakket' ) );

echo "\n── what a save keeps ──\n";

$clean = PFH_Widgets_Collection::clean(
	[
		'inspire' => [ 'title' => "  Recepten \xF0\x9F\x8D\x8B  ", 'url' => 'javascript:alert(1)' ],
		'faq'     => [ 'items' => [ [ 'q' => 'Echt fruit?', 'a' => 'Ja.<script>x</script>' ], [ 'q' => '', 'a' => 'Een los antwoord' ] ] ],
		'bundle'  => [ 'product' => '999999', 'points' => "Een\n\n  Twee  \n" ],
	]
);

ok( 'an emoji is dropped, so the database can store the rest', 'Recepten' === $clean['inspire']['title'], $clean['inspire']['title'] );
ok( 'a script link is not a link', '' === $clean['inspire']['url'], $clean['inspire']['url'] );
ok( 'a question needs its question', 1 === count( $clean['faq']['items'] ) );
ok( '  and its answer loses the script', false === strpos( $clean['faq']['items'][0]['a'], '<script' ) );
ok( 'a product that does not exist is not kept', 0 === $clean['bundle']['product'] );
ok( 'selling points are one per line, blank lines dropped', [ 'Een', 'Twee' ] === $clean['bundle']['points'], wp_json_encode( $clean['bundle']['points'] ) );

echo "\n── hiding ──\n";

foreach ( [ 'inspire' => 'PFH_Element_Notice', 'faq' => 'PFH_Element_Faq', 'bundle' => 'PFH_Element_Highlight' ] as $section => $class ) {
	update_term_meta( $term->term_id, PFH_Widgets_Collection::META, PFH_Widgets_Collection::clean( [ $section => [ 'hide' => '1' ] ] ) );
	ok( "a category can hide the $section section", '' === trim( draw( $class ) ) );

	on_category( 'honing' );
	ok( '  and only on that category', '' !== trim( draw( $class ) ) );

	on_category( '' );
	ok( '  not on the shop page either', '' !== trim( draw( $class ) ) );

	on_category( 'gia-giamas' );
	ok( '  and not when the section is told to ignore categories', '' !== trim( draw( $class, [ 'fromCategory' => false ] ) ) );
}

echo "\n── the inspiration band, reworded ──\n";

update_term_meta(
	$term->term_id,
	PFH_Widgets_Collection::META,
	PFH_Widgets_Collection::clean( [ 'inspire' => [ 'title' => 'Limonade maken?', 'url' => 'https://example.test/recepten/' ] ] )
);

$band = draw( 'PFH_Element_Notice' );

ok( 'its title is the category\'s', false !== strpos( $band, 'Limonade maken?' ) && false === strpos( $band, 'Inspiratie nodig?' ) );
ok( 'its button goes where the category says', false !== strpos( $band, 'href="https://example.test/recepten/"' ) );
ok( 'what the category left empty is still there', false !== strpos( $band, 'Ontdek recepten met Gia Giamas' ) && false !== strpos( $band, 'Bekijk alle recepten' ) );

echo "\n── the questions, the category's own ──\n";

update_term_meta(
	$term->term_id,
	PFH_Widgets_Collection::META,
	PFH_Widgets_Collection::clean(
		[
			'faq' => [
				'title' => 'Vragen over limonade',
				'items' => [
					[ 'q' => 'Hoeveel glazen haal ik uit een fles?', 'a' => 'Ongeveer 33 glazen uit een liter.' ],
					[ 'q' => 'Kan het in de vriezer?', 'a' => 'Liever niet.' ],
				],
			],
		]
	)
);

$faq = draw( 'PFH_Element_Faq' );

ok( 'they replace the section\'s own', false !== strpos( $faq, 'Hoeveel glazen haal ik uit een fles?' ) && false === strpos( $faq, 'Heb ik een pomp nodig?' ) );
ok( 'in the order given', strpos( $faq, 'Hoeveel glazen' ) < strpos( $faq, 'vriezer' ) );
ok( 'under the category\'s heading', false !== strpos( $faq, 'Vragen over limonade' ) );
ok( 'and the search-engine markup says the same', false !== strpos( $faq, '"name":"Kan het in de vriezer?"' ) );

update_term_meta( $term->term_id, PFH_Widgets_Collection::META, PFH_Widgets_Collection::clean( [ 'faq' => [ 'title' => 'Alleen een kop' ] ] ) );
$faq = draw( 'PFH_Element_Faq' );

ok( 'a category with a heading but no questions keeps the section\'s questions', false !== strpos( $faq, 'Alleen een kop' ) && false !== strpos( $faq, 'Heb ik een pomp nodig?' ) );

echo "\n── the bundle, tied to a product ──\n";

$sale = get_posts( [ 'post_type' => 'product', 'title' => 'Proefpakket 3 smaken', 'fields' => 'ids', 'posts_per_page' => 1 ] );
$sale = $sale ? (int) $sale[0] : 0;
$item = wc_get_product( $sale );

update_term_meta(
	$term->term_id,
	PFH_Widgets_Collection::META,
	PFH_Widgets_Collection::clean(
		[
			'bundle' => [
				'product'      => (string) $sale,
				'title_bottom' => 'Kies je drie favorieten',
				'points'       => "Drie flessen naar keuze\nGratis verzonden",
			],
		]
	)
);

$bundle = draw( 'PFH_Element_Highlight' );
$now    = money( (float) $item->get_sale_price() );
$was    = money( (float) $item->get_regular_price() );
$diff   = money( (float) $item->get_regular_price() - (float) $item->get_sale_price() );
$pct    = (int) round( ( $item->get_regular_price() - $item->get_sale_price() ) / $item->get_regular_price() * 100 );

ok( 'the price is the product\'s', false !== strpos( $bundle, '>' . esc_html( $now ) . '<' ), $now );
ok( '  with its old price beside it', false !== strpos( $bundle, 'pfh-hl__price-was">' . esc_html( $was ) . '<' ), $was );
ok( '  and the saving worked out from them', false !== strpos( $bundle, esc_html( 'Bespaar ' . $diff . ' — ' . $pct . '% korting' ) ), $diff . ' / ' . $pct );
ok( 'the typed price is gone', false === strpos( $bundle, '€ 41,97' ) && false === strpos( $bundle, '46,97' ) );
ok( 'the title looks like the link it is: underlined, with an arrow', false !== strpos( $bundle, '<span class="pfh-hl__title-line">' ) && false !== strpos( $bundle, 'pfh-hl__title-arrow' ) );
ok( '  on the bold line, the one that carries the offer', (bool) preg_match( '/pfh-hl__title-bottom"><span class="pfh-hl__title-line">/', $bundle ) );
ok( '  and a banner that links nowhere has neither', false === strpos( draw( 'PFH_Element_Highlight', [ 'fromCategory' => false ] ), 'pfh-hl__title-line' ) );
ok( 'the whole banner links to the product', false !== strpos( $bundle, 'pfh-hl__link" href="' . esc_url( get_permalink( $sale ) ) . '"' ) && false !== strpos( $bundle, 'is-clickable' ) );
ok( 'the category\'s words replace the typed ones', false !== strpos( $bundle, 'Kies je drie favorieten' ) && false === strpos( $bundle, '3 smaken naar keuze' ) );
ok( '  its selling points too', false !== strpos( $bundle, 'Drie flessen naar keuze' ) && false === strpos( $bundle, 'Ideaal cadeau' ) );
ok( '  and what it left empty keeps the typed words', false !== strpos( $bundle, 'Meest gekozen' ) && false !== strpos( $bundle, 'Proefpakket' ) );

$full = get_posts(
	[
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => [ [ 'key' => '_sale_price', 'compare' => 'NOT EXISTS' ] ],
	]
);

if ( ! $full ) {
	$full = get_posts( [ 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_query' => [ [ 'key' => '_sale_price', 'value' => '' ] ] ] );
}

update_term_meta( $term->term_id, PFH_Widgets_Collection::META, PFH_Widgets_Collection::clean( [ 'bundle' => [ 'product' => (string) $full[0] ] ] ) );
$bundle = draw( 'PFH_Element_Highlight' );

ok( 'a product not on sale shows no old price', false === strpos( $bundle, 'pfh-hl__price-was' ), 'product ' . $full[0] );
ok( '  and no saving — not even the typed one', false === strpos( $bundle, 'Bespaar' ) );

echo "\n── how the picture sits ──\n";

require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * A picture of the given size, attached to nothing.
 */
function picture( $w, $h ) {
	$dir  = WP_CONTENT_DIR . '/uploads/pfh-fit';
	wp_mkdir_p( $dir );
	$file = $dir . "/shot-{$w}x{$h}.jpg";
	$im   = imagecreatetruecolor( $w, $h );
	imagefill( $im, 0, 0, imagecolorallocate( $im, 255, 255, 255 ) );
	imagejpeg( $im, $file, 80 );
	$id = wp_insert_attachment( [ 'post_title' => 'fit', 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit' ], $file );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

	return $id;
}

$tall = picture( 600, 800 );
$wide = picture( 1200, 700 );

update_term_meta( $term->term_id, PFH_Widgets_Collection::META, PFH_Widgets_Collection::clean( [ 'bundle' => [ 'image' => (string) $tall ] ] ) );
$shot = draw( 'PFH_Element_Highlight' );

ok( 'a tall product shot is shown whole, not cropped', false !== strpos( $shot, 'pfh-hl--fit-contain' ) );
ok( '  with its white background taken into the card', false !== strpos( $shot, 'pfh-hl--multiply' ) );
ok( '  unless that is switched off', false === strpos( draw( 'PFH_Element_Highlight', [ 'imageMultiply' => false ] ), 'pfh-hl--multiply' ) );
ok( '  and the panel can still say fill', false !== strpos( draw( 'PFH_Element_Highlight', [ 'imageFit' => 'cover' ] ), 'pfh-hl--fit-cover' ) );

update_term_meta( $term->term_id, PFH_Widgets_Collection::META, PFH_Widgets_Collection::clean( [ 'bundle' => [ 'image' => (string) $wide ] ] ) );
ok( 'a wide scene fills its half', false !== strpos( draw( 'PFH_Element_Highlight' ), 'pfh-hl--fit-cover' ) );

delete_term_meta( $term->term_id, PFH_Widgets_Collection::META );
$default = draw( 'PFH_Element_Highlight' );

ok( 'the supplied photograph fills, as designed', false !== strpos( $default, 'pfh-hl--fit-cover' ) && false === strpos( $default, 'pfh-hl--multiply' ) );
ok( 'the card has its shadow', false !== strpos( $default, 'pfh-hl--shadow' ) );
ok( '  which can be turned off', false === strpos( draw( 'PFH_Element_Highlight', [ 'shadow' => false ] ), 'pfh-hl--shadow' ) );

wp_delete_attachment( $tall, true );
wp_delete_attachment( $wide, true );

wp_trash_post( $sale );
update_term_meta( $term->term_id, PFH_Widgets_Collection::META, PFH_Widgets_Collection::clean( [ 'bundle' => [ 'product' => (string) $sale ] ] ) );
wp_untrash_post( $sale );
wp_trash_post( $sale );

$bundle = draw( 'PFH_Element_Highlight' );

ok( 'a product that has gone falls back to what is typed in Bricks', false !== strpos( $bundle, '€ 41,97' ), 'the banner advertised a trashed product' );

wp_untrash_post( $sale );
wp_publish_post( $sale );

delete_term_meta( $term->term_id, PFH_Widgets_Collection::META );

echo "\n── saving from the category screen ──\n";

wp_set_current_user( 1 );

$_POST = [
	'pfh_collection_nonce' => wp_create_nonce( PFH_Widgets_Collection::NONCE ),
	'pfh_collection'       => [ 'inspire' => [ 'title' => 'Opgeslagen' ] ],
];
PFH_Widgets_Collection::save( $term->term_id );

ok( 'a filled-in panel is stored', 'Opgeslagen' === PFH_Widgets_Collection::get( $term->term_id )['inspire']['title'] );

$_POST['pfh_collection'] = [ 'inspire' => [ 'title' => '' ] ];
PFH_Widgets_Collection::save( $term->term_id );

ok( 'an emptied panel stores nothing at all', '' === get_term_meta( $term->term_id, PFH_Widgets_Collection::META, true ) );

$_POST = [ 'pfh_collection' => [ 'inspire' => [ 'title' => 'Zonder nonce' ] ] ];
PFH_Widgets_Collection::save( $term->term_id );

ok( 'a save without the form\'s nonce is ignored', '' === get_term_meta( $term->term_id, PFH_Widgets_Collection::META, true ) );

$_POST = [];
wp_set_current_user( 0 );

echo "\n── the panel is on the category screen ──\n";

ob_start();
PFH_Widgets_Collection::fields( $term );
$panel = (string) ob_get_clean();

ok( 'all three sections are there', false !== strpos( $panel, 'pfh_collection[inspire][title]' ) && false !== strpos( $panel, 'pfh_collection[faq][items][0][q]' ) && false !== strpos( $panel, 'pfh_collection[bundle][product]' ) );
ok( 'the product is picked with WooCommerce\'s own search', false !== strpos( $panel, 'wc-product-search' ) );
ok( 'it carries its nonce', false !== strpos( $panel, 'pfh_collection_nonce' ) );
ok( 'it is hooked to the category screen', false !== has_action( 'product_cat_edit_form_fields', [ 'PFH_Widgets_Collection', 'fields' ] ) );

/* ===================================================================== *
 * Recently viewed
 * ================================================================== */

echo "\n── recently viewed ──\n";

function recent( array $over = [], $exclude = 0 ) {
	$el       = new PFH_Element_Recent( [ 'id' => 'rv' ] );
	$el->name = 'pfh-recent';
	$el->set_control_groups();
	$el->set_controls();

	$s = [];

	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) {
			$s[ $k ] = $c['default'];
		}
	}

	set_transient( PFH_Element_Recent::CONFIG . 'rv', array_merge( $s, $over ), HOUR_IN_SECONDS );

	$_POST = [ 'nonce' => wp_create_nonce( PFH_Widgets_Ajax::NONCE ), 'element' => 'rv', 'exclude' => (string) $exclude ];
	$_REQUEST = $_POST;

	add_filter( 'wp_doing_ajax', '__return_true' );
	add_filter( 'wp_die_ajax_handler', static function () { return static function () {}; } );

	ob_start();
	try {
		PFH_Element_Recent::ajax();
	} catch ( Throwable $e ) {} // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
	$out = (string) ob_get_clean();

	$_POST = [];

	$json = json_decode( $out, true );

	return isset( $json['data']['html'] ) ? (string) $json['data']['html'] : '';
}

on_category( 'gia-giamas' );
unset( $_COOKIE['woocommerce_recently_viewed'] );

$first = recent();

ok( 'a first visit is not an empty gap', false !== strpos( $first, 'pfh-prod__card' ) );
ok( '  it shows the best sellers', false !== strpos( $first, 'populair' ) );
ok( '  under their own heading, not "recently viewed"', false === strpos( $first, 'viewed' ), 'the best sellers were labelled as history' );
ok( 'or nothing, when that is what is asked for', '' === recent( [ 'fallback' => 'none' ] ) );

$some = array_slice( products_in( 'honing' ), 0, 2 );
$_COOKIE['woocommerce_recently_viewed'] = implode( '|', $some );

$history = recent();

ok( 'with a history, it shows that history', ! array_diff( shown( $history ), $some ) && count( shown( $history ) ) === count( $some ), implode( ',', shown( $history ) ) );
ok( '  newest first', shown( $history ) && end( $some ) === shown( $history )[0] );
ok( '  under the recently viewed heading', false !== strpos( $history, 'bekeken' ) );
ok( 'the product being looked at is left out of its own list', ! in_array( $some[1], shown( recent( [], $some[1] ) ), true ) );

unset( $_COOKIE['woocommerce_recently_viewed'] );

echo "\n── views are recorded without WooCommerce's widget ──\n";

$product = $some[0];

$GLOBALS['wp_query']     = new WP_Query( [ 'p' => $product, 'post_type' => 'product' ] );
$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];

ob_start();
PFH_Widgets_Recent::script();
$track = (string) ob_get_clean();

ok( 'a product page records itself', false !== strpos( $track, 'id=' . $product . ',' ) );
ok( '  in WooCommerce\'s own cookie', false !== strpos( $track, '"woocommerce_recently_viewed"' ) );
ok( '  from the browser, so a cached page still counts and stays cacheable', false !== strpos( $track, 'document.cookie' ) );

on_category( 'gia-giamas' );
ob_start();
PFH_Widgets_Recent::script();
ok( 'any other page records nothing', '' === trim( (string) ob_get_clean() ) );

echo "\n$pass passed, $fail failed\n";
