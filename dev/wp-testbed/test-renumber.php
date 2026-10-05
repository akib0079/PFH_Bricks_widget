<?php
/**
 * Making room for another site's orders (PFH_Widgets_Renumber).
 *
 * The live shop's orders keep their numbers when imported; where this site
 * already has an image, a page, a template or a revision on such a number,
 * the import skips the order. The tool deletes the revisions and moves the
 * rest to new numbers, repointing every place that names them. Here a small
 * site is built with all those kinds of references, the numbers are freed,
 * and undo has to put back every row exactly.
 */
require __DIR__ . '/wp-load.php';

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

global $wpdb;

register_post_type( 'bricks_template', [ 'public' => false ] );
wp_set_current_user( 1 );
PFH_Widgets_Migrate::load();
$R = 'PFH_Widgets_Renumber';

// ── Leftovers of an interrupted run ───────────────────────────────────────
foreach ( (array) $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '%(rn)%'" ) as $stale ) {
	wp_delete_post( (int) $stale, true );
}
delete_option( $R::RUN );
delete_option( $R::SURVEY );
$stale_cat = get_term_by( 'slug', 'rn-cat', 'product_cat' );
if ( $stale_cat ) { wp_delete_term( $stale_cat->term_id, 'product_cat' ); }

$uploads = wp_get_upload_dir();
wp_mkdir_p( $uploads['basedir'] . '/2026/10' );
file_put_contents( $uploads['basedir'] . '/2026/10/rn-photo.png', 'png' );

// ── A small site with every kind of reference ────────────────────────────
$page  = wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Home (rn)' ] );
$img   = wp_insert_attachment( [ 'post_mime_type' => 'image/png', 'post_title' => 'Photo (rn)', 'post_status' => 'inherit' ], $uploads['basedir'] . '/2026/10/rn-photo.png', $page );
$other = wp_insert_attachment( [ 'post_mime_type' => 'image/png', 'post_title' => 'Stays (rn)', 'post_status' => 'inherit' ], $uploads['basedir'] . '/2026/10/rn-photo.png' );
$foot  = wp_insert_post( [ 'post_type' => 'bricks_template', 'post_status' => 'publish', 'post_title' => 'Footer (rn)' ] );
$head  = wp_insert_post( [ 'post_type' => 'bricks_template', 'post_status' => 'publish', 'post_title' => 'Header (rn)' ] );
$rev   = wp_insert_post( [ 'post_type' => 'revision', 'post_status' => 'inherit', 'post_title' => 'Header (rn)', 'post_parent' => $head, 'post_name' => $head . '-revision-v1' ] );
$keep  = wp_insert_post( [ 'post_type' => 'revision', 'post_status' => 'inherit', 'post_title' => 'Header (rn)', 'post_parent' => $head, 'post_name' => $head . '-revision-v2' ] );
// update_post_meta() on a revision writes to its parent; this goes on the revision.
add_metadata( 'post', $rev, '_bricks_page_header_2', wp_slash( [ [ 'id' => 'r1', 'name' => 'text', 'parent' => 0, 'children' => [], 'settings' => [] ] ] ) );

$prod = new WC_Product_Simple();
$prod->set_name( 'Product (rn)' );
$prod->set_status( 'private' );
$prod->set_regular_price( '5' );
$prod->set_image_id( $img );
$prod->set_gallery_image_ids( [ $other, $img ] );
$x = $prod->save();
$cat = (int) wp_insert_term( 'Cat (rn)', 'product_cat', [ 'slug' => 'rn-cat' ] )['term_id'];
wp_set_object_terms( $x, [ $cat ], 'product_cat' );
update_term_meta( $cat, 'thumbnail_id', $img );
update_term_meta( $cat, PFH_Widgets_Collection::META, [ 'header' => [ 'image' => $img ], 'bundle' => [ 'product' => $x ] ] );

wp_update_post( [ 'ID' => $page, 'post_content' => '<!-- wp:image {"id":' . $img . ',"sizeSlug":"large"} --><figure class="wp-block-image"><img src="/x.png" class="wp-image-' . $img . '"/></figure><!-- /wp:image --> [gallery ids="' . $other . ',' . $img . '"] Price 1' . $img . ' stays.' ] );

$header = [
	[ 'id' => 'aaaaaa', 'name' => 'image', 'parent' => 0, 'children' => [], 'settings' => [ 'image' => [ 'id' => $img, 'filename' => 'rn-photo.png', 'url' => '/wp-content/uploads/2026/10/rn-photo.png' ] ] ],
	[ 'id' => 'bbbbbb', 'name' => 'button', 'parent' => 0, 'children' => [], 'settings' => [ 'link' => [ 'type' => 'internal', 'postId' => $page ] ] ],
	[ 'id' => 'cccccc', 'name' => 'template', 'parent' => 0, 'children' => [], 'settings' => [ 'template' => $foot ] ],
	[ 'id' => 'dddddd', 'name' => 'pfh-header', 'parent' => 0, 'children' => [], 'settings' => [ 'productId' => $x ] ],
];
update_post_meta( $head, '_bricks_page_header_2', wp_slash( $header ) );
update_post_meta( $foot, '_bricks_template_settings', [ 'templateConditions' => [ [ 'main' => 'ids', 'ids' => [ $page ] ] ] ] );
update_post_meta( $page, '_elementor_data', wp_slash( wp_json_encode( [ [ 'elType' => 'widget', 'settings' => [ 'image' => [ 'url' => '/x.png', 'id' => $img ] ] ] ] ) ) );
update_post_meta( $page, '_rn_unknown_ref', (string) $img );

$menu = wp_create_nav_menu( 'Menu (rn) ' . wp_rand() );
$item = wp_update_nav_menu_item( $menu, 0, [ 'menu-item-title' => 'Home', 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $page, 'menu-item-status' => 'publish' ] );

$opts_before = [ 'page_on_front' => get_option( 'page_on_front' ), 'show_on_front' => get_option( 'show_on_front' ), 'bricks_global_settings' => get_option( 'bricks_global_settings' ) ];
$stylesheet  = get_stylesheet();
$mods_before = get_option( 'theme_mods_' . $stylesheet );
update_option( 'page_on_front', $page );
update_option( 'bricks_global_settings', [ 'siteLogo' => [ 'id' => $img, 'url' => '/wp-content/uploads/2026/10/rn-photo.png' ] ] );
update_option( 'theme_mods_' . $stylesheet, array_merge( (array) $mods_before, [ 'custom_logo' => $img ] ) );
update_user_meta( 1, '_rn_wishlist', [ $x ] );

// An empty placeholder an interrupted import left, a real order, a free number.
$orphan = wp_insert_post( [ 'post_type' => 'shop_order_placehold', 'post_status' => 'draft', 'post_title' => 'Placeholder (rn)' ] );
$order  = wc_create_order();
$order->set_billing_email( 'rn@example.test' );
$order->save();
$oid  = $order->get_id();
$free = (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->posts}" ) + 500;

$numbers = implode( ', ', [ $img, $page, $head, $foot, $rev, $x, $orphan, $oid, $free ] );
$ids     = $R::parse( $numbers . ', 3-5' );
ok( 'numbers and ranges are read', in_array( 4, $ids, true ) && in_array( $img, $ids, true ) && count( $ids ) === 12 );
$ids = $R::parse( $numbers );

// What a page shows, before.
$snap = function () use ( $wpdb, $img, $page, $head, $foot, $x, $cat, $item, $stylesheet ) {
	return [
		'content' => get_post_field( 'post_content', $page ),
		'header'  => get_post_meta( $head, '_bricks_page_header_2', true ),
		'conds'   => get_post_meta( $foot, '_bricks_template_settings', true ),
		'thumb'   => get_post_meta( $x, '_thumbnail_id', true ),
		'gallery' => get_post_meta( $x, '_product_image_gallery', true ),
		'cthumb'  => get_term_meta( $cat, 'thumbnail_id', true ),
		'coll'    => get_term_meta( $cat, PFH_Widgets_Collection::META, true ),
		'front'   => get_option( 'page_on_front' ),
		'bricks'  => get_option( 'bricks_global_settings' ),
		'mods'    => get_option( 'theme_mods_' . $stylesheet ),
		'menu'    => get_post_meta( $item, '_menu_item_object_id', true ),
		'elem'    => get_post_meta( $page, '_elementor_data', true ),
	];
};
$before = $snap();

echo "── the check ──\n";
$s = $R::survey( $ids );
ok( 'the free number is free', [ $free ] === $s['free'] );
ok( 'the order is seen to be an order already', [ $oid ] === $s['orders'] );
ok( 'the revision is to be deleted', [ $rev ] === $s['revisions'] );
ok( 'the empty placeholder too', [ $orphan ] === $s['orphans'] );
$moving = wp_list_pluck( $s['move'], 'id' );
sort( $moving );
$expect = [ $img, $page, $head, $foot, $x ];
sort( $expect );
ok( 'the image, page, templates and product are to move', $expect === $moving, wp_json_encode( $moving ) );
ok( 'it knows the places that name them', isset( $s['refs']['postmeta _thumbnail_id'], $s['refs']['postmeta _product_image_gallery'], $s['refs']['postmeta _bricks_page_header_2'], $s['refs']['termmeta thumbnail_id'], $s['refs']['option page_on_front'], $s['refs']['option bricks_global_settings'], $s['refs']['postmeta _elementor_data'], $s['refs']['postmeta _menu_item_object_id'] ), wp_json_encode( $s['refs'] ) );
ok( 'and says which it cannot repoint', isset( $s['unhandled']['postmeta _rn_unknown_ref'], $s['unhandled']['usermeta _rn_wishlist'] ), wp_json_encode( $s['unhandled'] ) );
ok( 'a number inside another number is not one of them', ! isset( $s['unhandled']['post_content (page)'] ) );
ok( 'nothing has changed', $before === $snap() && get_post( $rev ) && get_post( $orphan ) );

echo "\n── making room ──\n";
update_option( $R::SURVEY, $s, false );
$run = $R::start();
ok( 'it starts', is_array( $run ) && 'running' === $run['state'], is_wp_error( $run ) ? $run->get_error_message() : '' );
$n = 0;
while ( 'running' === $run['state'] && $n++ < 100 ) { $run = $R::run_step(); }
ok( 'it finishes without errors', 'done' === $run['state'] && ! array_filter( $run['log'], function ( $l ) { return 'error' === $l['level']; } ), wp_json_encode( $run['log'] ) );
$map = $run['map'];
ok( 'five posts moved', 5 === count( $map ) );

foreach ( [ $img, $page, $head, $foot, $x ] as $old ) {
	$new = (int) ( $map[ $old ] ?? 0 );
	ok( "#$old is free and its post is at #$new", $new > $old && ! get_post( $old ) && get_post( $new ) );
}

$I = function ( $old ) use ( $map ) { return (int) $map[ $old ]; };
ok( 'the revision and the placeholder are gone', ! get_post( $rev ) && ! get_post( $orphan ) );
ok( 'the order is untouched', 'rn@example.test' === wc_get_order( $oid )->get_billing_email() );
ok( 'the other revision follows its template', $I( $head ) === (int) get_post( $keep )->post_parent );
ok( 'the image is still attached to its page', $I( $page ) === (int) get_post( $I( $img ) )->post_parent );
ok( '  with its file and title', 'Photo (rn)' === get_the_title( $I( $img ) ) && false !== strpos( (string) get_attached_file( $I( $img ) ), 'rn-photo.png' ) );
$h = get_post_meta( $I( $head ), '_bricks_page_header_2', true );
ok( 'the header layout points at the moved image, page, footer and product', $I( $img ) === (int) $h[0]['settings']['image']['id'] && $I( $page ) === (int) $h[1]['settings']['link']['postId'] && $I( $foot ) === (int) $h[2]['settings']['template'] && $I( $x ) === (int) $h[3]['settings']['productId'] );
ok( 'the footer\'s conditions name the moved page', [ $I( $page ) ] === array_map( 'intval', get_post_meta( $I( $foot ), '_bricks_template_settings', true )['templateConditions'][0]['ids'] ) );
$p = wc_get_product( $I( $x ) );
ok( 'the product keeps its photo and gallery', $p && $I( $img ) === (int) $p->get_image_id() && [ $other, $I( $img ) ] === array_map( 'intval', $p->get_gallery_image_ids() ) );
ok( '  and its category', has_term( $cat, 'product_cat', $I( $x ) ) );
ok( '  and its row in the product lookup', (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $I( $x ) ) ) === 1 );
$c = get_post_field( 'post_content', $I( $page ) );
ok( 'the page text points at the moved image', false !== strpos( $c, 'wp-image-' . $I( $img ) ) && false !== strpos( $c, '"id":' . $I( $img ) ) && false !== strpos( $c, 'ids="' . $other . ',' . $I( $img ) . '"' ) );
ok( '  and leaves a number that only contains it', false !== strpos( $c, 'Price 1' . $img . ' stays.' ) );
ok( 'the category image and content follow', $I( $img ) === (int) get_term_meta( $cat, 'thumbnail_id', true ) && $I( $img ) === (int) get_term_meta( $cat, PFH_Widgets_Collection::META, true )['header']['image'] && $I( $x ) === (int) get_term_meta( $cat, PFH_Widgets_Collection::META, true )['bundle']['product'] );
ok( 'the home page setting follows', $I( $page ) === (int) get_option( 'page_on_front' ) );
ok( 'the Bricks settings and the theme logo follow', $I( $img ) === (int) get_option( 'bricks_global_settings' )['siteLogo']['id'] && $I( $img ) === (int) get_option( 'theme_mods_' . $stylesheet )['custom_logo'] );
ok( 'the menu item points at the moved page', $I( $page ) === (int) get_post_meta( $item, '_menu_item_object_id', true ) );
$e = json_decode( get_post_meta( $I( $page ), '_elementor_data', true ), true );
ok( 'the old Elementor data points at the moved image', $I( $img ) === (int) $e[0]['settings']['image']['id'] );
ok( 'what it could not repoint is left alone', (string) $img === get_post_meta( $I( $page ), '_rn_unknown_ref', true ) );
ok( 'the freed numbers take an order', $img === wp_insert_post( [ 'import_id' => $img, 'post_type' => 'shop_order_placehold', 'post_status' => 'draft', 'post_title' => 'Order (rn)' ] ) );
wp_delete_post( $img, true );

echo "\n── undo ──\n";
$u = $R::undo();
ok( 'it undoes', is_array( $u ), is_wp_error( $u ) ? $u->get_error_message() : '' );
ok( 'every post is back on its number', get_post( $img ) && get_post( $page ) && get_post( $head ) && get_post( $foot ) && get_post( $x ) && 'Header (rn)' === get_the_title( $head ) );
ok( 'the revision and placeholder are back', get_post( $rev ) && $head === (int) get_post( $rev )->post_parent && get_post( $orphan ) );
ok( '  with the revision\'s meta', is_array( get_metadata( 'post', $rev, '_bricks_page_header_2', true ) ) );
wp_cache_flush();
$after = $snap();
foreach ( $before as $k => $v ) {
	ok( "  $k is as it was", $v == $after[ $k ], wp_json_encode( [ $v, $after[ $k ] ] ) ); // phpcs:ignore Universal.Operators.StrictComparisons -- numbers come back as strings from the database.
}
ok( 'the product lookup row is back on its number', (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $x ) ) === 1 );

echo "\n── undo is refused once an order has taken a number ──\n";
update_option( $R::SURVEY, $R::survey( $ids ), false );
$run = $R::start();
$n = 0;
while ( 'running' === $run['state'] && $n++ < 100 ) { $run = $R::run_step(); }
$taken = wp_insert_post( [ 'import_id' => $page, 'post_type' => 'shop_order_placehold', 'post_status' => 'draft', 'post_title' => 'Order (rn)' ] );
$u = $R::undo();
ok( 'refused, naming the number', is_wp_error( $u ) && false !== strpos( $u->get_error_message(), (string) $page ) );
wp_delete_post( $taken, true );
ok( '  and fine again once it is free', is_array( $R::undo() ) );

// ── Tidy up ───────────────────────────────────────────────────────────────
$order->delete( true );
foreach ( [ $page, $img, $other, $head, $foot, $rev, $keep, $orphan ] as $id ) { wp_delete_post( $id, true ); }
$p = wc_get_product( $x ); if ( $p ) { $p->delete( true ); }
wp_delete_term( $cat, 'product_cat' );
wp_delete_nav_menu( $menu );
delete_user_meta( 1, '_rn_wishlist' );
foreach ( $opts_before as $k => $v ) { false === $v ? delete_option( $k ) : update_option( $k, $v ); }
update_option( 'theme_mods_' . $stylesheet, $mods_before );
delete_option( $R::RUN );
delete_option( $R::SURVEY );
foreach ( glob( PFH_Widgets_Migrate_Import::dir() . 'renumber-*' ) as $f ) { @unlink( $f ); }

echo "\n$pass passed, $fail failed\n";
