<?php
/**
 * The four points sent on 2026-10-02: the saving from €2 with cents
 * (#1011812), the bundles and recipes in the menu (#1011827), and a
 * category header with a photograph of its own products (#1012055).
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

global $wpdb;
foreach ( (array) $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_title LIKE 'Oktober %'" ) as $stale ) {
	wp_delete_post( (int) $stale, true );
}

function oct_product( $name, $regular, $sale ) {
	$p = new WC_Product_Simple();
	$p->set_name( $name );
	$p->set_status( 'publish' );
	$p->set_regular_price( (string) $regular );
	$p->set_sale_price( (string) $sale );
	return $p->save();
}

function oct_render( $class, $name, array $settings ) {
	$el       = new $class( [ 'id' => 'o' . wp_rand( 1, 99999 ) ] );
	$el->name = $name;
	$el->set_control_groups();
	$el->set_controls();
	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; }
	}
	$el->settings = array_merge( $s, $settings );
	ob_start();
	$el->render();
	return (string) ob_get_clean();
}

echo "── the saving: from €2, with cents (#1011812) ──\n";
$small = oct_product( 'Oktober kleine korting', '10.00', '8.95' );
$big   = oct_product( 'Oktober grote korting', '20.00', '17.55' );
$two   = oct_product( 'Oktober precies twee', '12.00', '10.00' );

$pdp = static function ( $id, array $s = [] ) {
	return oct_render( 'PFH_Element_Product', 'pfh-product', [ 'previewId' => (string) $id ] + $s );
};
ok( 'a saving of €1,05 gets no badge', false === strpos( $pdp( $small ), 'VOORDEEL' ) );
ok( '  but the old price still shows', false !== strpos( $pdp( $small ), 'pfh-pdp__price-was' ) );
ok( 'from €2 it does', false !== strpos( $pdp( $two ), '2,00 VOORDEEL' ) );
ok( 'with two figures after the comma', false !== strpos( $pdp( $big ), '2,45 VOORDEEL' ) );
ok( 'rounding is still a choice', false !== strpos( $pdp( $big, [ 'savingWhole' => true ] ), '2 VOORDEEL' ) && false === strpos( $pdp( $big, [ 'savingWhole' => true ] ), '2,45' ) );
ok( 'and so is the floor', false !== strpos( $pdp( $small, [ 'savingMin' => 0 ] ), '1,05 VOORDEEL' ) );
ok( 'a floor typed with a comma is understood', false === strpos( $pdp( $two, [ 'savingMin' => '2,5' ] ), 'VOORDEEL' ) );
ok( 'with no saving set at all, the floor is €2', false === strpos( oct_render( 'PFH_Element_Product', 'pfh-product', [ 'previewId' => (string) $small, 'savingMin' => null ] ), 'VOORDEEL' ) );

$bar = static function ( $id ) {
	return oct_render( 'PFH_Element_Bottomcart', 'pfh-bottomcart', [ 'productId' => (string) $id ] );
};
ok( 'the reminder at the bottom agrees: no "Bespaar" under €2', (bool) preg_match( '/data-pfh-price-save hidden><\/span>/', $bar( $small ) ) );
ok( '  and "Bespaar € 2,45" above it', false !== strpos( $bar( $big ), 'Bespaar' ) && false !== strpos( $bar( $big ), '2,45' ) );

foreach ( [ $small, $big, $two ] as $id ) {
	wp_delete_post( $id, true );
}

echo "\n── the menu: bundles and recipes (#1011827) ──\n";
$parent = wp_insert_term( 'Oktober Gia', 'product_cat' );
$parent = is_wp_error( $parent ) ? (int) $parent->get_error_data() : (int) $parent['term_id'];
$subs   = [];
foreach ( [ 'Oktober Traditioneel', 'Oktober Premium', 'Oktober Bundels' ] as $name ) {
	$t      = wp_insert_term( $name, 'product_cat', [ 'parent' => $parent ] );
	$subs[] = is_wp_error( $t ) ? (int) $t->get_error_data() : (int) $t['term_id'];
}
// Only the first two have products: a bundle category is empty for now.
$filled = [];
foreach ( array_slice( $subs, 0, 2 ) as $i => $term ) {
	$filled[] = $pid = oct_product( 'Oktober product ' . $i, '5', '' );
	wp_set_object_terms( $pid, [ $term ], 'product_cat' );
}

$menu = static function ( array $item ) {
	return oct_render(
		'PFH_Element_Header',
		'pfh-header',
		[ 'navItems' => [ $item + [ 'label' => 'Gia Giamas', 'hasMega' => true, 'megaSource' => 'product_cat', 'link' => [ 'type' => 'external', 'url' => '/gia/' ] ] ] ]
	);
};
$named = $menu( [ 'megaInclude' => implode( ',', [ $subs[1], $subs[0], $subs[2] ] ), 'megaLimit' => 5, 'megaExtra' => "Recepten | /recepten-van-gia-giamas/ | https://example.test/recept.jpg" ] );
$p1    = strpos( $named, 'Oktober Premium' );
$p2    = strpos( $named, 'Oktober Traditioneel' );
$p3    = strpos( $named, 'Oktober Bundels' );
$p4    = strpos( $named, '>Recepten<' );
ok( 'categories named in the menu show in that order', false !== $p1 && false !== $p2 && $p1 < $p2 );
ok( '  even one with no products yet, like the bundles', false !== $p3 && $p2 < $p3 );
ok( 'and a page can follow them: Recepten', false !== $p4 && $p3 < $p4 );
ok( '  linking to the recipes', false !== strpos( $named, 'href="/recepten-van-gia-giamas/"' ) );
ok( '  with its own photo', false !== strpos( $named, 'https://example.test/recept.jpg' ) );
$worded = $menu( [ 'megaInclude' => (string) $subs[0], 'megaExtra' => "Recepten | /recepten/ | | Bekijk recepten" ] );
ok( '  and its own link text, while the categories keep theirs', 1 === substr_count( $worded, 'Bekijk recepten' ) && false !== strpos( $worded, 'pfh-card__link">Bekijk' ) && 2 === substr_count( $worded, 'class="pfh-card__link"' ) );
$capped = $menu( [ 'megaInclude' => implode( ',', $subs ), 'megaLimit' => 2, 'megaExtra' => "Recepten | /recepten/" ] );
ok( 'the card limit still holds', false === strpos( $capped, '>Recepten<' ) && false === strpos( $capped, 'Oktober Bundels' ) );
$bare = $menu( [ 'megaParent' => (string) $parent, 'megaExtra' => "Recepten | /recepten/" ] );
ok( 'without a photo, an extra card gets the placeholder, not a broken image', false !== strpos( $bare, '>Recepten<' ) && false === strpos( $bare, 'src=""' ) );
ok( 'a menu by parent still leaves out empty subcategories', false === strpos( $bare, 'Oktober Bundels' ) );

echo "\n── a category's header photo (#1012055) ──\n";
// A photo with its own background: no cut-out, not on white.
$up   = wp_upload_dir();
$path = trailingslashit( $up['path'] ) . 'pfh-oktober-photo.jpg';
$im   = imagecreatetruecolor( 600, 450 );
imagefill( $im, 0, 0, imagecolorallocate( $im, 120, 160, 140 ) );
imagefilledellipse( $im, 300, 225, 200, 300, imagecolorallocate( $im, 230, 200, 120 ) );
imagejpeg( $im, $path, 85 );
imagedestroy( $im );
require_once ABSPATH . 'wp-admin/includes/image.php';
$att = wp_insert_attachment( [ 'post_mime_type' => 'image/jpeg', 'post_title' => 'lippenbalsem', 'post_status' => 'inherit' ], $path );
wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $path ) );
foreach ( $filled as $pid ) {
	set_post_thumbnail( $pid, $att );
}
$term = get_term( $subs[0], 'product_cat' );
delete_transient( PFH_Widgets_Collection::HEADER_CACHE . $term->term_id );
$found = PFH_Widgets_Collection::header_image( $term );
ok( 'with only photographs, the best seller\'s photo is used', $found && (int) $found['id'] === (int) $att, wp_json_encode( $found ) );
ok( '  and it is known to have a background', PFH_Widgets_Photo::has_backdrop( $att ) );
$empty = get_term( $subs[2], 'product_cat' );
delete_transient( PFH_Widgets_Collection::HEADER_CACHE . $empty->term_id );
ok( 'a category with no products keeps the generic banner', null === PFH_Widgets_Collection::header_image( $empty ) );

// The shop header draws it as a photo tile.
$GLOBALS['wp_query']                 = new WP_Query( [ 'product_cat' => $term->slug ] );
$GLOBALS['wp_query']->queried_object = $term;
$GLOBALS['wp_query']->queried_object_id = $term->term_id;
$GLOBALS['wp_query']->is_tax         = true;
$GLOBALS['wp_query']->is_archive     = true;
$head = oct_render( 'PFH_Element_Shophead', 'pfh-shophead', [] );
ok( 'the header shows it as a tile with rounded corners', false !== strpos( $head, 'pfh-shophead__banner--photo' ), substr( strip_tags( $head ), 0, 80 ) );
$shop_css = file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/assets/css/pfh-shop.css' );
ok( '  styled as one', false !== strpos( $shop_css, '.pfh-shophead__banner--photo' ) );

foreach ( $filled as $pid ) {
	wp_delete_post( $pid, true );
}
foreach ( array_merge( $subs, [ $parent ] ) as $t ) {
	delete_transient( PFH_Widgets_Collection::HEADER_CACHE . $t );
	wp_delete_term( $t, 'product_cat' );
}
wp_delete_attachment( $att, true );

echo "\n$pass passed, $fail failed\n";
