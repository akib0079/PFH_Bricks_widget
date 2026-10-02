<?php
/**
 * The category page after the client's feedback round (2026-09-28/29).
 *
 * Inside a category the first pill is that category as a whole; each category
 * can set its own figures and its own header picture, and falls back to a
 * cut-out of one of its products; the filter speaks Dutch and shows no counts.
 */
require __DIR__ . '/wp-load.php';
foreach ( [ 'archive', 'shophead', 'counter' ] as $f ) {
	require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-' . $f . '.php';
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function el( $class, $id, array $over = [] ) {
	$el       = new $class( [ 'id' => $id ] );
	$el->name = 'pfh-test';
	$el->set_control_groups();
	$el->set_controls();
	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; }
	}
	$el->settings = array_merge( $s, $over );
	return $el;
}

function draw( $el ) {
	ob_start();
	$el->render();
	return ob_get_clean();
}

/** Land on a category archive the way a followed link would. */
function land( WP_Term $term ) {
	$_SERVER['REQUEST_URI'] = wp_parse_url( get_term_link( $term ), PHP_URL_PATH );
	$_GET                   = [];
	query_posts( [ 'post_type' => 'product', 'product_cat' => $term->slug, 'posts_per_page' => 12 ] );
	$GLOBALS['wp_query']->queried_object    = $term;
	$GLOBALS['wp_query']->queried_object_id = $term->term_id;
	$GLOBALS['wp_query']->is_tax            = true;
	$GLOBALS['wp_query']->is_archive        = true;
}

// A parent with children, and a child of it.
$parent = null;
$child  = null;

foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true ] ) as $t ) {
	if ( $t->parent ) {
		$p = get_term( $t->parent, 'product_cat' );
		if ( $p && ! is_wp_error( $p ) ) {
			$parent = $p;
			$child  = $t;
			break;
		}
	}
}

ok( 'the demo shop has a category with subcategories', $parent && $child );

if ( $parent && $child ) {
	echo "\n── the first pill means this category ──\n";
	land( $parent );
	$html = draw( el( 'PFH_Element_Archive', 'a1' ) );
	ok( 'on the parent, "Alles" leads to the parent', (bool) preg_match( '#<a class="pfh-arch__pill is-active" href="' . preg_quote( esc_url( get_term_link( $parent ) ), '#' ) . '"#', $html ) );
	ok( 'its subcategories are the pills', false !== strpos( $html, '>' . esc_html( $child->name ) . '</a>' ) );

	land( $child );
	$html = draw( el( 'PFH_Element_Archive', 'a2' ) );
	ok( 'on a subcategory without children, its siblings are the pills', false !== strpos( $html, 'is-active" href="' . esc_url( get_term_link( $child ) ) ) );
	ok( '  and "Alles" leads back to the parent, not the whole shop', false !== strpos( $html, '<a class="pfh-arch__pill" href="' . esc_url( get_term_link( $parent ) ) . '"' ) );

	echo "\n── toolbar and filter ──\n";
	ok( 'no product count', false === strpos( $html, 'data-pfh-arch-count' ) );
	ok( 'sorting instead, with best selling and highest price', false !== strpos( $html, 'data-pfh-arch-sort' ) && false !== strpos( $html, 'Meest populair' ) && false !== strpos( $html, 'Prijs hoog - laag' ) );
	$saved = draw( el( 'PFH_Element_Archive', 'a3', [ 'toolbar' => [ [ 'part' => 'cats', 'id' => 'x1' ], [ 'part' => 'spacer', 'id' => 'x2' ], [ 'part' => 'count', 'id' => 'x3' ], [ 'part' => 'filter', 'id' => 'x4' ] ] ] ) );
	ok( 'a template saved with the old toolbar gets sorting too', false === strpos( $saved, 'data-pfh-arch-count' ) && false !== strpos( $saved, 'data-pfh-arch-sort' ) );
	$chosen = draw( el( 'PFH_Element_Archive', 'a4', [ 'toolbar' => [ [ 'part' => 'count' ], [ 'part' => 'filter' ] ] ] ) );
	ok( 'a toolbar someone arranged themselves is kept', false !== strpos( $chosen, 'data-pfh-arch-count' ) );
	ok( 'filter options carry no "(01)" counts', false === strpos( $html, 'pfh-arch__opt-count' ) );
	$counted = draw( el( 'PFH_Element_Archive', 'a5', [ 'facetCounts' => true, 'filters' => [ [ 'source' => 'product_tag', 'label' => '', 'open' => true, 'limit' => 0 ] ] ] ) );
	ok( 'unless switched back on', false !== strpos( $counted, 'pfh-arch__opt-count' ) || false === strpos( $counted, 'pfh-arch__opt' ) );
	ok( 'brands are called "Merk"', 'Merk' === PFH_Widgets_Archive::taxonomy_label( 'product_brand' ) );

	echo "\n── the category's own figures ──\n";
	$counter = el( 'PFH_Element_Counter', 'c1' );
	$plain   = draw( $counter );
	ok( 'untouched, the row is as set in Bricks', false !== strpos( $plain, 'Origineel recept' ) );

	$_POST = [];
	update_term_meta( $parent->term_id, PFH_Widgets_Collection::META, PFH_Widgets_Collection::clean( [ 'figures' => [ 'items' => [
		[ 'value' => '100%', 'label' => 'Rauwe honing', 'sub' => 'Koud geslingerd' ],
		[ 'value' => '%score%/10', 'label' => 'Klantbeoordeling', 'sub' => 'Op %count% reviews' ],
		[ 'value' => '', 'label' => 'no figure, no row', 'sub' => '' ],
	] ] ] ) );
	land( $parent );
	$own = draw( el( 'PFH_Element_Counter', 'c2' ) );
	ok( 'a category with figures shows its own', false !== strpos( $own, 'Rauwe honing' ) && false === strpos( $own, 'Origineel recept' ) );
	ok( 'a row without a figure is dropped', false === strpos( $own, 'no figure, no row' ) );
	ok( 'live tokens still work in them', false === strpos( $own, '%score%' ) );
	$plain_numbers = draw( el( 'PFH_Element_Counter', 'c4', [ 'fromCategory' => false, 'items' => [ [ 'value' => '669 mg', 'label' => 'Vitamine C', 'sub' => 'Per 100 ml Pandasia' ], [ 'value' => '%score%/10', 'label' => 'Klantbeoordeling', 'sub' => 'Op %count% reviews' ] ] ] ) );
	ok( 'a number in a small line stays as typed', false !== strpos( $plain_numbers, 'Per 100 ml Pandasia' ) );
	ok( '  while tokens are still filled in', false === strpos( $plain_numbers, '%count%' ) && false === strpos( $plain_numbers, '%score%' ) );
	update_term_meta( $parent->term_id, PFH_Widgets_Collection::META, PFH_Widgets_Collection::clean( [ 'figures' => [ 'hide' => '1' ] ] ) );
	ok( 'and a category can hide the row', '' === trim( draw( el( 'PFH_Element_Counter', 'c3' ) ) ) );

	echo "\n── the header picture ──\n";
	delete_term_meta( $parent->term_id, PFH_Widgets_Collection::META );
	delete_transient( PFH_Widgets_Collection::HEADER_CACHE . $parent->term_id );
	delete_transient( PFH_Widgets_Collection::HEADER_CACHE . $child->term_id );

	// Give one of the child's products a cut-out picture to find.
	$up   = wp_upload_dir();
	$path = trailingslashit( $up['path'] ) . 'pfh-header-cutout.png';
	$im   = imagecreatetruecolor( 400, 400 );
	imagesavealpha( $im, true );
	imagealphablending( $im, false );
	imagefill( $im, 0, 0, imagecolorallocatealpha( $im, 0, 0, 0, 127 ) );
	imagealphablending( $im, true );
	imagefilledellipse( $im, 200, 200, 150, 260, imagecolorallocate( $im, 200, 120, 20 ) );
	imagepng( $im, $path );
	imagedestroy( $im );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$att = wp_insert_attachment( [ 'post_mime_type' => 'image/png', 'post_title' => 'cutout', 'post_status' => 'inherit' ], $path );
	wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $path ) );

	$ids     = get_posts( [ 'post_type' => 'product', 'fields' => 'ids', 'posts_per_page' => -1, 'tax_query' => [ [ 'taxonomy' => 'product_cat', 'terms' => [ $child->term_id ] ] ] ] );
	$was     = [];
	foreach ( $ids as $id ) {
		$was[ $id ] = get_post_thumbnail_id( $id );
		set_post_thumbnail( $id, $att );
	}

	land( $child );
	$head = draw( el( 'PFH_Element_Shophead', 'h1' ) );
	ok( 'with nothing chosen, a cut-out of its own products', false !== strpos( $head, 'pfh-header-cutout' ), substr( strip_tags( $head ), 0, 80 ) );

	$chosen_img = PFH_Widgets_Photo::card_image( $att, '1px' ) ? $att : 0;
	update_term_meta( $parent->term_id, PFH_Widgets_Collection::META, PFH_Widgets_Collection::clean( [ 'header' => [ 'image' => (string) $att ] ] ) );
	$found = PFH_Widgets_Collection::header_image( $child );
	ok( 'a picture chosen on the parent is used by its subcategories', $found && (int) $found['id'] === (int) $att );

	// No cut-out among its products: a packshot on white will do.
	$wpath = trailingslashit( $up['path'] ) . 'pfh-header-white.jpg';
	$im    = imagecreatetruecolor( 400, 400 );
	imagefill( $im, 0, 0, imagecolorallocate( $im, 255, 255, 255 ) );
	imagefilledellipse( $im, 200, 200, 150, 260, imagecolorallocate( $im, 200, 120, 20 ) );
	imagejpeg( $im, $wpath, 90 );
	imagedestroy( $im );
	$white = wp_insert_attachment( [ 'post_mime_type' => 'image/jpeg', 'post_title' => 'white', 'post_status' => 'inherit' ], $wpath );
	wp_update_attachment_metadata( $white, wp_generate_attachment_metadata( $white, $wpath ) );
	foreach ( $ids as $id ) { set_post_thumbnail( $id, $white ); }
	delete_term_meta( $parent->term_id, PFH_Widgets_Collection::META );
	delete_transient( PFH_Widgets_Collection::HEADER_CACHE . $child->term_id );
	$picked = PFH_Widgets_Collection::header_image( $child );
	ok( 'without a cut-out, a product photographed on white', $picked && (int) $picked['id'] === (int) $white );
	wp_delete_attachment( $white, true );

	foreach ( $was as $id => $thumb ) {
		$thumb ? set_post_thumbnail( $id, $thumb ) : delete_post_thumbnail( $id );
	}
	delete_term_meta( $parent->term_id, PFH_Widgets_Collection::META );
	delete_transient( PFH_Widgets_Collection::HEADER_CACHE . $child->term_id );
	wp_delete_attachment( $att, true );
}

echo "\n── the bands under the products ──\n";
foreach ( [ 'notice', 'highlight', 'faq' ] as $f ) {
	require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-' . $f . '.php';
}
if ( $parent && $child ) {
	land( $child );
	$only = draw( el( 'PFH_Element_Notice', 'n1', [ 'onlyCats' => $parent->slug ] ) );
	ok( 'a band limited to a category shows under it', false !== strpos( $only, 'pfh-notice' ) );
	$other = get_terms( [ 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'exclude' => [ $parent->term_id ], 'number' => 1 ] );
	$elsewhere = draw( el( 'PFH_Element_Notice', 'n2', [ 'onlyCats' => (string) $other[0]->term_id ] ) );
	ok( 'and nowhere else', '' === trim( $elsewhere ) );
	ok( 'unlimited, it shows anywhere', false !== strpos( draw( el( 'PFH_Element_Notice', 'n3' ) ), 'pfh-notice' ) );
}
if ( $parent && $child ) {
	land( $child );
	$elsewhere_cat = get_terms( [ 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'exclude' => [ $parent->term_id ], 'number' => 1 ] );
	$typed = [ 'titleTop' => 'Proefpakketten', 'text' => 'Ontdek de wereld van Gia Giamas.', 'points' => [ [ 'text' => 'Kies zelf 3 smaken' ] ] ];
	$mine  = draw( el( 'PFH_Element_Highlight', 'hb1', $typed + [ 'typedCats' => $parent->slug ] ) );
	ok( 'the typed bundle copy shows under the category it is about', false !== strpos( $mine, 'Proefpakketten' ) );
	if ( $elsewhere_cat ) {
		$other_copy = draw( el( 'PFH_Element_Highlight', 'hb2', $typed + [ 'typedCats' => $elsewhere_cat[0]->slug ] ) );
		$best       = PFH_Widgets_Collection::best_seller( $child );
		ok( 'elsewhere, the category\'s own best seller instead', $best && false === strpos( $other_copy, 'Proefpakketten' ) && false !== strpos( $other_copy, esc_html( get_the_title( $best ) ) ), substr( strip_tags( $other_copy ), 0, 120 ) );
		ok( '  without the typed selling points', false === strpos( $other_copy, 'Kies zelf 3 smaken' ) );
		ok( '  linking to that product', false !== strpos( $other_copy, esc_url( get_permalink( $best ) ) ) );
	}
	$plain = draw( el( 'PFH_Element_Highlight', 'hb3', $typed ) );
	ok( 'with no categories named, the typed copy shows everywhere, as before', false !== strpos( $plain, 'Proefpakketten' ) );
}

$faq = draw( el( 'PFH_Element_Faq', 'f1', [ 'title' => 'Veelgestelde Vragen' ] ) );
ok( 'a FAQ saved with "Vragen" reads "vragen"', false !== strpos( $faq, 'Veelgestelde vragen' ) && false === strpos( $faq, 'Veelgestelde Vragen' ) );
$hl = draw( el( 'PFH_Element_Highlight', 'h1', [ 'saving' => 'Bespaar € 5,49 — 15% korting', 'fromCategory' => false ] ) );
ok( 'a saving typed with a percentage loses it', false !== strpos( $hl, '>Bespaar € 5,49<' ), substr( strip_tags( $hl ), 0, 120 ) );

echo "\n── the menu: subcategories, and only ones with products ──\n";
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-header.php';
$empty_parent = wp_insert_term( 'PFH lege verzorging', 'product_cat' );
$empty_parent = is_wp_error( $empty_parent ) ? (int) $empty_parent->get_error_data() : (int) $empty_parent['term_id'];
$empty_child  = wp_insert_term( 'PFH lege sub', 'product_cat', [ 'parent' => $empty_parent ] );
$empty_child  = is_wp_error( $empty_child ) ? (int) $empty_child->get_error_data() : (int) $empty_child['term_id'];
$nav = static function ( $parent_id ) {
	$e           = new PFH_Element_Header( [ 'id' => 'hdm' . wp_rand( 1, 99999 ) ] );
	$e->settings = [ 'navItems' => [ [ 'label' => 'Menu', 'hasMega' => true, 'megaSource' => 'product_cat', 'megaParent' => (string) $parent_id, 'link' => [ 'type' => 'external', 'url' => '/x/' ] ] ] ];
	ob_start();
	$e->render();
	return (string) ob_get_clean();
};
ok( 'a menu item whose subcategories are all empty is a plain link', false === strpos( $nav( $empty_parent ), 'has-mega' ) && false === strpos( $nav( $empty_parent ), 'PFH lege sub' ) );
if ( $parent ) {
	$full = $nav( $parent->term_id );
	ok( 'one with products in its subcategories opens them', false !== strpos( $full, 'has-mega' ) && false !== strpos( $full, esc_html( $child->name ) ) );
}
wp_delete_term( $empty_child, 'product_cat' );
wp_delete_term( $empty_parent, 'product_cat' );

wp_reset_query();
echo "\n$pass passed, $fail failed\n";
