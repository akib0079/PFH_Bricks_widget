<?php
/**
 * Verhuizen: the design moved from one site onto another (1.60.0).
 *
 * One install plays both sites. First it is the staging site: templates,
 * a new page, images, a font, a menu, category and product content, settings.
 * The export is taken. Then it becomes the live shop: the new things are
 * gone, an order has taken the header template's number, the menu and
 * settings are the old ones and a different picture sits under one of the
 * design's file names. The import has to put the design on top of that —
 * every number pointing at what it means here — without touching the
 * order, and undo has to put the shop back exactly as it was.
 */
require __DIR__ . '/wp-load.php';

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

global $wpdb;

if ( ! defined( 'BRICKS_VERSION' ) ) {
	define( 'BRICKS_VERSION', '2.0-test' );
}

register_post_type( 'bricks_template', [ 'public' => false ] );
register_post_type( 'bricks_fonts', [ 'public' => false ] );
register_post_type( 'wfob_bump', [ 'public' => false ] );

wp_set_current_user( 1 );
require_once ABSPATH . 'wp-admin/includes/template.php';
PFH_Widgets_Migrate::load();

$E = 'PFH_Widgets_Migrate_Export';
$I = 'PFH_Widgets_Migrate_Import';

$uploads = wp_get_upload_dir();
$base    = trailingslashit( $uploads['basedir'] );
$home    = untrailingslashit( home_url() );
$source  = sys_get_temp_dir() . '/pfh-migrate-source-' . getmypid();

// ── Clean up whatever an interrupted run left ─────────────────────────────
foreach ( (array) $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '%(mig)%' OR post_title LIKE 'Mig %'" ) as $stale ) {
	wp_delete_post( (int) $stale, true );
}
foreach ( [ 'mig-main', 'mig-main-live' ] as $slug ) {
	$m = wp_get_nav_menu_object( $slug );
	if ( $m ) { wp_delete_nav_menu( $m->term_id ); }
}
foreach ( (array) $wpdb->get_col( "SELECT term_id FROM {$wpdb->terms} WHERE slug LIKE 'mig-main%' OR name LIKE '%before the move%'" ) as $stale ) {
	wp_delete_term( (int) $stale, 'nav_menu' );
}
$old_cat = get_term_by( 'slug', 'mig-cat', 'product_cat' );
if ( $old_cat ) { wp_delete_term( $old_cat->term_id, 'product_cat' ); }
foreach ( [ 'pfh_migrate_run', 'pfh_migrate_history', 'bricks_global_settings', 'bricks_color_palette', 'bricks_license_key', 'woocommerce_dhlpwc_7_settings' ] as $o ) {
	delete_option( $o );
}

foreach ( array_merge( glob( $base . '2026/09/mig-*' ), glob( $base . '2020/01/mig-*' ) ) as $stale ) {
	@unlink( $stale );
}

function mig_file( $rel, $body ) {
	global $base;
	wp_mkdir_p( dirname( $base . $rel ) );
	file_put_contents( $base . $rel, $body );
	return $base . $rel;
}

function mig_attachment( $rel, $body, $mime, $title ) {
	$full = mig_file( $rel, $body );
	$id   = wp_insert_attachment( [ 'post_mime_type' => $mime, 'post_title' => $title, 'post_status' => 'inherit' ], $full );
	return (int) $id;
}

function mig_backdate( $id ) {
	global $wpdb;
	$wpdb->update( $wpdb->posts, [ 'post_date' => '2020-01-01 10:00:00', 'post_date_gmt' => '2020-01-01 10:00:00', 'post_modified' => '2020-01-01 10:00:00' ], [ 'ID' => $id ] );
	clean_post_cache( $id );
}

$png = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' );

// ── The site the copy was taken from: these exist on both sides ───────────
$p_old = wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Over ons (mig)', 'post_name' => 'over-ons-mig', 'post_content' => 'Oude woorden die blijven.' ] );
$prod_a = new WC_Product_Simple(); $prod_a->set_name( 'Mig product A' ); $prod_a->set_sku( 'MIG-A-' . getmypid() ); $prod_a->set_status( 'publish' ); $prod_a->set_regular_price( '9.95' ); $a = $prod_a->save();
$prod_b = new WC_Product_Simple(); $prod_b->set_name( 'Mig product B' ); $prod_b->set_status( 'publish' ); $prod_b->set_regular_price( '5.95' ); $b = $prod_b->save();
$img_old = mig_attachment( '2020/01/mig-old.png', $png . 'old', 'image/png', 'Mig old image' );
foreach ( [ $p_old, $a, $b, $img_old ] as $id ) { mig_backdate( $id ); }
$cat = wp_insert_term( 'Mig categorie', 'product_cat', [ 'slug' => 'mig-cat' ] );
$cat = (int) $cat['term_id'];
wp_set_object_terms( $a, [ $cat ], 'product_cat' );

// A flavour attribute and a product with a variation in it, on both sides.
$stale_attr = wc_attribute_taxonomy_id_by_name( 'migsmaak' );
if ( $stale_attr ) { wc_delete_attribute( $stale_attr ); }
$attr_id = wc_create_attribute( [ 'name' => 'Mig smaak', 'slug' => 'migsmaak', 'type' => 'select', 'order_by' => 'menu_order' ] );
$ptax    = 'pa_migsmaak';
register_taxonomy( $ptax, 'product', [ 'hierarchical' => false, 'show_ui' => false ] );
delete_transient( 'wc_attribute_taxonomies' );
WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
foreach ( [ 'mig-tag' ] as $stale_slug ) { $st = get_term_by( 'slug', $stale_slug, 'product_tag' ); if ( $st ) { wp_delete_term( $st->term_id, 'product_tag' ); } }
$mand = (int) wp_insert_term( 'Mandarijn', $ptax, [ 'slug' => 'mig-mandarijn' ] )['term_id'];
$pa   = new WC_Product_Attribute();
$pa->set_id( $attr_id );
$pa->set_name( $ptax );
$pa->set_options( [ $mand ] );
$pa->set_visible( true );
$pa->set_variation( true );
$vprod = new WC_Product_Variable();
$vprod->set_name( 'Mig variabel' );
$vprod->set_status( 'publish' );
$vprod->set_attributes( [ $pa ] );
$vpid = $vprod->save();
wp_set_object_terms( $vpid, [ $mand ], $ptax );
$vvar = new WC_Product_Variation();
$vvar->set_parent_id( $vpid );
$vvar->set_attributes( [ $ptax => 'mig-mandarijn' ] );
$vvar->set_regular_price( '5' );
$vvar->set_description( 'Variatie oud' );
$vv = $vvar->save();
foreach ( [ $vpid, $vv ] as $id ) { mig_backdate( $id ); }
$blog_old = wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Mig blog oud', 'post_name' => 'mig-blog-oud', 'post_content' => 'Blog oud' ] );
mig_backdate( $blog_old );
$bump = wp_insert_post( [ 'post_type' => 'wfob_bump', 'post_status' => 'publish', 'post_title' => 'Mig bump', 'post_name' => 'mig-bump' ] );
mig_backdate( $bump );

// The copy was taken here: everything in the testbed so far is older.
$baseline = '2029-01-01 00:00:00';

// ── Made on staging after the copy ─────────────────────────────────────────
$img_new  = mig_attachment( '2026/09/mig-new.png', $png . 'new', 'image/png', 'Mig new image' );
$font_att = mig_attachment( '2026/09/mig-font.woff2', 'wOF2fontbytes', 'font/woff2', 'Mig font' );
mig_file( '2026/09/mig-clash.png', $png . 'staging-clash' );
$clash_md5_staging = md5_file( $base . '2026/09/mig-clash.png' );

$p_new  = wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Nieuw (mig)', 'post_name' => 'nieuw-mig', 'post_parent' => $p_old, 'post_content' => 'Nieuwe pagina-tekst.' ] );
$t_foot = wp_insert_post( [ 'post_type' => 'bricks_template', 'post_status' => 'publish', 'post_title' => 'Mig footer', 'post_name' => 'mig-footer' ] );
$t_head = wp_insert_post( [ 'post_type' => 'bricks_template', 'post_status' => 'publish', 'post_title' => 'Mig header', 'post_name' => 'mig-header' ] );
$fonts  = wp_insert_post( [ 'post_type' => 'bricks_fonts', 'post_status' => 'publish', 'post_title' => 'Mig Playfair', 'post_name' => 'mig-playfair' ] );
foreach ( [ $img_new, $font_att, $p_new, $t_foot, $t_head, $fonts ] as $x ) {
	$wpdb->update( $wpdb->posts, [ 'post_date' => '2030-01-01 10:00:00', 'post_date_gmt' => '2030-01-01 10:00:00' ], [ 'ID' => $x ] );
	clean_post_cache( $x );
}
// Made after the copy, but given an old date: it must not move where the copy ends.
$outlier = wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Oud gedateerd (mig)', 'post_date' => '2020-06-01 10:00:00' ] );

$menu = wp_create_nav_menu( 'Mig hoofdmenu' );
wp_update_term( $menu, 'nav_menu', [ 'slug' => 'mig-main' ] );
$mi_page = wp_update_nav_menu_item( $menu, 0, [ 'menu-item-title' => 'Nieuw', 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $p_new, 'menu-item-status' => 'publish', 'menu-item-position' => 1 ] );
wp_update_nav_menu_item( $menu, 0, [ 'menu-item-title' => 'Categorie', 'menu-item-type' => 'taxonomy', 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $cat, 'menu-item-parent-id' => $mi_page, 'menu-item-status' => 'publish', 'menu-item-position' => 2 ] );
wp_update_nav_menu_item( $menu, 0, [ 'menu-item-title' => 'Winkel', 'menu-item-type' => 'custom', 'menu-item-url' => $home . '/winkel/', 'menu-item-status' => 'publish', 'menu-item-position' => 3 ] );

$img_obj = [ 'id' => $img_new, 'filename' => 'mig-new.png', 'size' => 'full', 'full' => $home . '/wp-content/uploads/2026/09/mig-new.png', 'url' => $home . '/wp-content/uploads/2026/09/mig-new.png' ];
$header  = [
	[ 'id' => 'h00001', 'name' => 'pfh-header', 'parent' => 0, 'children' => [], 'settings' => [
		'logo'        => $img_obj,
		'megaProdCat' => (string) $cat,
		'productId'   => $a,
		'cta'         => [ 'link' => [ 'type' => 'internal', 'postId' => $p_new ] ],
	] ],
	[ 'id' => 'h00002', 'name' => 'nav-menu', 'parent' => 0, 'children' => [], 'settings' => [ 'menu' => $menu ] ],
	[ 'id' => 'h00003', 'name' => 'text-basic', 'parent' => 0, 'children' => [], 'settings' => [ 'text' => '<img src="' . $home . '/wp-content/uploads/2026/09/mig-clash.png" alt="">' ] ],
	[ 'id' => 'h00004', 'name' => 'template', 'parent' => 0, 'children' => [], 'settings' => [ 'template' => $t_foot ] ],
];
update_post_meta( $t_head, '_bricks_template_type', 'header' );
update_post_meta( $t_head, '_bricks_page_header_2', wp_slash( $header ) );
update_post_meta( $t_foot, '_bricks_template_type', 'footer' );
update_post_meta( $t_foot, '_bricks_template_settings', [ 'templateConditions' => [ [ 'main' => 'ids', 'ids' => [ $p_old ] ] ] ] );
update_post_meta( $t_foot, '_bricks_page_footer_2', wp_slash( [
	[ 'id' => 'f00001', 'name' => 'container', 'parent' => 0, 'children' => [], 'settings' => [ 'hasLoop' => true, 'query' => [ 'post_type' => [ 'product' ], 'post__in' => [ $a ], 'tax_query' => [ 'product_cat::' . $cat ] ] ] ],
	[ 'id' => 'f00002', 'name' => 'text', 'parent' => 0, 'children' => [], 'settings' => [ 'text' => 'Escaped \\" quote and a backslash \\ stay.' ] ],
] ) );
update_post_meta( $p_old, '_bricks_page_content_2', wp_slash( [
	[ 'id' => 'p00001', 'name' => 'image', 'parent' => 0, 'children' => [], 'settings' => [ 'image' => [ 'id' => $img_old, 'filename' => 'mig-old.png', 'url' => $home . '/wp-content/uploads/2020/01/mig-old.png' ] ] ],
] ) );
update_post_meta( $p_old, '_bricks_editor_mode', 'bricks' );
update_post_meta( $p_new, '_bricks_page_content_2', wp_slash( [ [ 'id' => 'n00001', 'name' => 'heading', 'parent' => 0, 'children' => [], 'settings' => [ 'text' => 'Nieuw' ] ] ] ) );
update_post_meta( $fonts, 'bricks_font_faces', [ '400' => [ 'woff2' => (string) $font_att ] ] );

update_post_meta( $a, '_pfh_bottom_image', $img_new );
update_post_meta( $a, '_pfh_related', [ $b ] );
update_post_meta( $a, '_pfh_usp', [ [ 'icon' => (string) $img_new, 'heading' => 'Vers', 'text' => 'Zie ' . $home . '/winkel/' ] ] );
update_term_meta( $cat, PFH_Widgets_Collection::META, [ 'header' => [ 'image' => $img_new, 'title' => 'Kop' ], 'bundle' => [ 'product' => $b, 'image' => $img_old ] ] );

update_option( 'bricks_global_settings', [ 'siteLogo' => $img_obj, 'customCss' => 'body{}' ] );
update_option( 'bricks_color_palette', [ [ 'id' => 'pal', 'colors' => [ [ 'hex' => '#3a8e8c' ] ] ] ] );
update_option( 'bricks_license_key', 'STAGING-LICENCE' );
update_option( 'pfh_badge', array_merge( (array) get_option( 'pfh_badge', [] ), [ 'migTest' => 'staging' ] ) );
$stylesheet = get_stylesheet();
$mods_before_staging = get_option( 'theme_mods_' . $stylesheet );
update_option( 'theme_mods_' . $stylesheet, [ 'custom_logo' => $img_new, 'nav_menu_locations' => [ 'primary' => $menu ], 'custom_css_post_id' => 99 ] );
$front_before = [ get_option( 'show_on_front' ), get_option( 'page_on_front' ) ];
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $p_new );
update_option( 'woocommerce_dhlpwc_7_settings', [ 'enabled' => 'yes', 'alternative_option_text_home' => 'Thuisbezorgd', 'other' => 'staging' ] );
$gateway_order_before = get_option( 'woocommerce_gateway_order' );

// Which badge field is a password, if any — it must stay out of the file.
$badge_secret = '';
foreach ( PFH_Widgets_Badge::flat_fields() as $key => $field ) {
	if ( 'password' === ( $field['type'] ?? '' ) ) { $badge_secret = $key; break; }
}
if ( $badge_secret ) {
	update_option( 'pfh_badge', array_merge( (array) get_option( 'pfh_badge', [] ), [ $badge_secret => 'STAGING-SECRET' ] ) );
}

// A brand for real products, and one made for a test product only.
$brands = [];
if ( taxonomy_exists( 'product_brand' ) ) {
	foreach ( [ 'mig-merk' => $b, 'mig-testmerk' => 0 ] as $slug => $member ) {
		$old_brand = get_term_by( 'slug', $slug, 'product_brand' );
		if ( $old_brand ) { wp_delete_term( $old_brand->term_id, 'product_brand' ); }
		$brands[ $slug ] = (int) wp_insert_term( ucfirst( $slug ), 'product_brand', [ 'slug' => $slug ] )['term_id'];
	}
	$private = wp_insert_post( [ 'post_type' => 'product', 'post_status' => 'private', 'post_title' => 'Mig testproduct (mig)' ] );
	wp_set_object_terms( $b, [ $brands['mig-merk'] ], 'product_brand' );
	wp_set_object_terms( $private, [ $brands['mig-testmerk'] ], 'product_brand' );
}

echo "── the export ──\n";
// The catalogue as staging has it now.
wp_update_post( [ 'ID' => $a, 'post_content' => 'Nieuwe tekst <img src="' . $home . '/wp-content/uploads/2026/09/mig-new.png">', 'post_excerpt' => 'Kort nieuw' ] );
update_post_meta( $a, '_product_image_gallery', (string) $img_new );
wp_insert_term( 'Mig tag', 'product_tag', [ 'slug' => 'mig-tag' ] );
wp_set_object_terms( $a, [ 'mig-tag' ], 'product_tag' );
update_post_meta( $a, '_manage_stock', 'yes' );
update_post_meta( $a, '_stock', '99' );
update_post_meta( $a, 'fb_product_item_id', 'STAGING-FB' );
wp_update_term( $mand, $ptax, [ 'name' => "\u{1F34A} Mandarijn" ] );
update_post_meta( $vv, '_variation_description', 'Variatie nieuw' );
update_post_meta( $vv, '_thumbnail_id', $img_new );
wp_update_post( [ 'ID' => $blog_old, 'post_content' => 'Blog nieuw' ] );
update_post_meta( $bump, '_wfob_selected_products', [ 'olie' => '750 ml' ] );
update_post_meta( $bump, '_edit_lock', '123:1' );
$blog_new = wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Mig blog nieuw', 'post_name' => 'mig-blog-nieuw', 'post_content' => 'Alleen op staging', 'post_date' => '2030-01-02 10:00:00' ] );
update_post_meta( $blog_new, '_thumbnail_id', $img_new );

$package = $E::build( [ 'baseline' => $baseline, 'catalog' => true ] );
$by_cat  = [];
foreach ( $package['catalog'] as $c ) { $by_cat[ $c['source_id'] ] = $c; }
ok( 'the catalogue holds the product and its variation', isset( $by_cat[ $a ], $by_cat[ $vv ] ) && 'product_variation' === $by_cat[ $vv ]['type'] && $vpid === $by_cat[ $vv ]['parent'] );
ok( '  with its text, photos and tags, without stock or Facebook\'s id', 'Kort nieuw' === $by_cat[ $a ]['excerpt'] && (string) $img_new === $by_cat[ $a ]['meta']['_product_image_gallery'] && [ 'mig-tag' ] === $by_cat[ $a ]['terms']['product_tag'] && ! isset( $by_cat[ $a ]['meta']['_stock'] ) && ! isset( $by_cat[ $a ]['meta']['fb_product_item_id'] ) );
ok( '  and the flavour names', (bool) array_filter( $package['cat_terms'], function ( $t ) { return 'mig-mandarijn' === $t['slug'] && false !== strpos( html_entity_decode( $t['name'] ), "\u{1F34A}" ); } ) );
$titles  = wp_list_pluck( $package['posts'], 'title', 'source_id' );
ok( 'the templates, the new page and the converted page are in it', isset( $titles[ $t_head ], $titles[ $t_foot ], $titles[ $p_new ], $titles[ $p_old ], $titles[ $fonts ] ) );
ok( 'the copy ends before the first thing made after it', (int) $package['baseline']['max_post_id'] >= $img_old && (int) $package['baseline']['max_post_id'] < $img_new, wp_json_encode( $package['baseline'] ) );
ok( '  however old a later item says it is — and that item is shown', in_array( $outlier . ' post 2020-06-01 10:00:00', $package['baseline']['examples'], true ) );
ok( '  the date is kept in full', '2029-01-01 00:00:00' === $package['baseline']['date'] && '2026-08-23 21:30:00' === $E::build( [ 'baseline' => '2026-08-23 21:30' ] )['baseline']['date'] );
$by_id = [];
foreach ( $package['posts'] as $p ) { $by_id[ $p['source_id'] ] = $p; }
ok( 'a page from before the copy is known as such', false === $by_id[ $p_old ]['new'] && true === $by_id[ $t_head ]['new'] && true === $by_id[ $p_new ]['new'] );
ok( 'a new page brings its own', 'Nieuwe pagina-tekst.' === $by_id[ $p_new ]['content'] && 'over-ons-mig/nieuw-mig' === $by_id[ $p_new ]['path'] );
ok( 'the images it uses are in it, by file and fingerprint', isset( $package['attachments'][ $img_new ], $package['attachments'][ $img_old ], $package['attachments'][ $font_att ] ) && md5( $png . 'new' ) === $package['attachments'][ $img_new ]['md5'] );
ok( 'a file only named in a text is in it too', isset( $package['files']['2026/09/mig-clash.png'] ) );
ok( 'the products it points at are known by SKU and slug', isset( $package['ref_posts'][ $a ] ) && $prod_a->get_sku() === $package['ref_posts'][ $a ]['sku'] );
ok( 'the category is known by slug', in_array( 'mig-cat', wp_list_pluck( $package['terms'], 'slug' ), true ) );
ok( 'Bricks settings are in it, the licence is not', isset( $package['options']['bricks_global_settings'] ) && ! isset( $package['options']['bricks_license_key'] ) );
ok( 'the plugin licence is not in it', ! isset( $package['modules']['pfh_license'] ) );
if ( $badge_secret ) {
	ok( 'an API key is left out unless asked for', ! isset( $package['modules']['pfh_badge']['value'][ $badge_secret ] ) && $package['notes'] );
	$with = $E::build( [ 'baseline' => $baseline, 'secrets' => true ] );
	ok( '  and put in when asked for', 'STAGING-SECRET' === ( $with['modules']['pfh_badge']['value'][ $badge_secret ] ?? '' ) );
}
ok( 'the menu is in it, item by item', 3 === count( wp_list_filter( $package['menus'], [ 'slug' => 'mig-main' ] ) ? array_values( wp_list_filter( $package['menus'], [ 'slug' => 'mig-main' ] ) )[0]['items'] : [] ) );
ok( 'the DHL option names are in it', 'Thuisbezorgd' === ( wp_list_filter( $package['patches'], [ 'option' => 'woocommerce_dhlpwc_7_settings' ] ) ? array_values( wp_list_filter( $package['patches'], [ 'option' => 'woocommerce_dhlpwc_7_settings' ] ) )[0]['set']['alternative_option_text_home'] : '' ) );
ok( 'no order is in it', false === strpos( wp_json_encode( $package ), 'shop_order' ) );
if ( $brands ) {
	$by_slug = [];
	foreach ( $package['terms'] as $t ) { $by_slug[ $t['slug'] ] = $t; }
	ok( 'a brand moves with the products on sale in it', isset( $by_slug['mig-merk'] ) && [ $b ] === $by_slug['mig-merk']['members'] );
	ok( '  a brand made for a test product does not', ! isset( $by_slug['mig-testmerk'] ) );
	wp_delete_post( $private, true );
	foreach ( $brands as $x ) { wp_delete_term( $x, 'product_brand' ); }
}

// It came from another address: pretend it did.
$json = str_replace( [ $home, str_replace( '/', '\/', $home ) ], [ 'https://staging.example', 'https:\/\/staging.example' ], wp_json_encode( $package ) );

// ── Now this is the live shop ───────────────────────────────────────────
wp_mkdir_p( $source . '/2026/09' );
copy( $base . '2026/09/mig-new.png', $source . '/2026/09/mig-new.png' );
copy( $base . '2026/09/mig-font.woff2', $source . '/2026/09/mig-font.woff2' );
copy( $base . '2026/09/mig-clash.png', $source . '/2026/09/mig-clash.png' );
add_filter( 'pfh_migrate_local_source', function () use ( $source ) { return $source; } );

// The catalogue as live has it: older texts, its own stock and Facebook id.
wp_update_post( [ 'ID' => $a, 'post_content' => 'Oude tekst', 'post_excerpt' => 'Kort oud' ] );
delete_post_meta( $a, '_product_image_gallery' );
wp_set_object_terms( $a, [], 'product_tag' );
wp_delete_term( get_term_by( 'slug', 'mig-tag', 'product_tag' )->term_id, 'product_tag' );
update_post_meta( $a, '_stock', '7' );
update_post_meta( $a, 'fb_product_item_id', 'LIVE-FB' );
wp_update_term( $mand, $ptax, [ 'name' => 'Mandarijn' ] );
update_post_meta( $vv, '_variation_description', 'Variatie oud' );
delete_post_meta( $vv, '_thumbnail_id' );
wp_update_post( [ 'ID' => $blog_old, 'post_content' => 'Blog oud' ] );
wp_delete_post( $blog_new, true );
update_post_meta( $bump, '_wfob_selected_products', [ 'olie' => '500 ml' ] );

wp_delete_attachment( $img_new, true );
wp_delete_attachment( $font_att, true );
foreach ( [ $t_head, $t_foot, $p_new, $fonts ] as $id ) { wp_delete_post( $id, true ); }
wp_delete_nav_menu( $menu );
mig_file( '2026/09/mig-clash.png', $png . 'live-has-another-picture' );
$clash_md5_live = md5_file( $base . '2026/09/mig-clash.png' );

// An order took the header template's number, and the shop made a page of
// its own under the new page's.
$taken = wp_insert_post( [ 'import_id' => $t_head, 'post_type' => 'shop_order_placehold', 'post_status' => 'draft', 'post_title' => 'Bestelling (mig)' ] );
ok( 'the stand-in order has the header template\'s old number', $taken === $t_head, (string) $taken );
$live_page = wp_insert_post( [ 'import_id' => $p_new, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Eigen pagina (mig)', 'post_name' => 'eigen-pagina-mig', 'post_content' => 'Van de winkel zelf.' ] );
ok( 'the stand-in live page has the new page\'s number', $live_page === $p_new, (string) $live_page );
wp_delete_post( $outlier, true );

delete_post_meta( $p_old, '_bricks_page_content_2' );
delete_post_meta( $p_old, '_bricks_editor_mode' );
update_post_meta( $p_old, '_elementor_data', '[{"elType":"section"}]' );
foreach ( [ '_pfh_bottom_image', '_pfh_related', '_pfh_usp' ] as $k ) { delete_post_meta( $a, $k ); }
delete_term_meta( $cat, PFH_Widgets_Collection::META );

$live_menu = wp_create_nav_menu( 'Hoofdmenu live' );
wp_update_term( $live_menu, 'nav_menu', [ 'slug' => 'mig-main' ] );
wp_update_nav_menu_item( $live_menu, 0, [ 'menu-item-title' => 'Oud', 'menu-item-type' => 'custom', 'menu-item-url' => 'https://example.org/', 'menu-item-status' => 'publish' ] );

delete_option( 'bricks_global_settings' );
delete_option( 'bricks_color_palette' );
update_option( 'bricks_license_key', 'LIVE-LICENCE' );
$badge_live = array_merge( (array) get_option( 'pfh_badge', [] ), [ 'migTest' => 'live' ] );
if ( $badge_secret ) { $badge_live[ $badge_secret ] = 'LIVE-KEY'; }
update_option( 'pfh_badge', $badge_live );
update_option( 'theme_mods_' . $stylesheet, [ 'custom_logo' => 0, 'nav_menu_locations' => [ 'primary' => $live_menu ], 'custom_css_post_id' => 7 ] );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $p_old );
update_option( 'woocommerce_dhlpwc_7_settings', [ 'enabled' => 'yes', 'alternative_option_text_home' => 'Home', 'other' => 'live' ] );
update_option( 'woocommerce_gateway_order', [ 'bacs' => 0, 'cheque' => 1, 'cod' => 2, 'paypal' => 3 ] );

$order = wc_create_order();
$order->add_product( wc_get_product( $a ), 2 );
$order->set_billing_email( 'klant@example.test' );
$order->calculate_totals();
$order->update_status( 'processing' );
$order_id = $order->get_id();
function mig_order_print( $id ) {
	$o = wc_get_order( $id );
	return $o ? md5( wp_json_encode( [ $o->get_status(), $o->get_total(), $o->get_billing_email(), count( $o->get_items() ), $o->get_meta_data() ? count( $o->get_meta_data() ) : 0 ] ) ) : 'gone';
}
$order_print = mig_order_print( $order_id );

$snapshot = function () use ( $p_old, $a, $cat, $stylesheet ) {
	return [
		'content'  => get_post_field( 'post_content', $p_old ),
		'layout'   => get_post_meta( $p_old, '_bricks_page_content_2', true ),
		'elem'     => get_post_meta( $p_old, '_elementor_data', true ),
		'pfh'      => [ get_post_meta( $a, '_pfh_bottom_image', true ), get_post_meta( $a, '_pfh_related', true ) ],
		'coll'     => get_term_meta( $cat, PFH_Widgets_Collection::META, true ),
		'opts'     => [ get_option( 'bricks_global_settings' ), get_option( 'bricks_color_palette' ), get_option( 'bricks_license_key' ), get_option( 'pfh_badge' ), get_option( 'theme_mods_' . $stylesheet ), (string) get_option( 'page_on_front' ), get_option( 'show_on_front' ), get_option( 'woocommerce_dhlpwc_7_settings' ), get_option( 'woocommerce_gateway_order' ) ],
		'menu'     => wp_get_nav_menu_object( 'mig-main' ) ? wp_get_nav_menu_object( 'mig-main' )->name : '',
		'bt_count' => (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type = 'bricks_template' AND post_title LIKE 'Mig %'" ),
	];
};
$before = $snapshot();

$live_stock = get_post_meta( $a, '_stock', true );

echo "\n── the check before importing ──\n";
$id = $I::store( $json );
ok( 'the file is accepted', is_string( $id ) && '' !== $id, is_wp_error( $id ) ? $id->get_error_message() : '' );
ok( 'anything else is not', is_wp_error( $I::store( '{"format":"other"}' ) ) );
$plan  = $I::plan( $I::load( $id ) );
$items = [];
foreach ( $plan['items'] as $it ) { $items[ $it['key'] ] = $it; }
ok( 'nothing blocks it', ! $plan['problems'], implode( ' / ', $plan['problems'] ) );
ok( 'the header template is to be made — not found under the order\'s number', 'create' === $items[ 'post:' . $t_head ]['action'] );
ok( 'the new page is to be made — the live page under its number is another', 'create' === $items[ 'post:' . $p_new ]['action'] );
ok( 'the converted page is updated where it is', 'update' === $items[ 'post:' . $p_old ]['action'] && $p_old === (int) $items[ 'post:' . $p_old ]['target'] );
ok( 'the menu is replaced, the old one kept', 'replace' === $items[ 'menu:' . $menu ]['action'] );
ok( 'the images: one here, two to fetch', 1 === $plan['images']['reuse'] && 2 === $plan['images']['download'], wp_json_encode( $plan['images'] ) );
ok( 'the file with another picture under its name is seen', 1 === $plan['files']['clash'], wp_json_encode( $plan['files'] ) );
ok( 'nothing has been written yet', $before === $snapshot() );

$render = function () {
	ob_start();
	PFH_Widgets_Migrate::render();
	return ob_get_clean();
};
$_GET['plan'] = $id;
$html = $render();
unset( $_GET['plan'] );
ok( 'the screen lists the plan with a box per item', false !== strpos( $html, 'value="post:' . $t_head . '"' ) && false !== strpos( $html, 'id="pfh-migrate-run"' ) );

function mig_run( $id, $keys ) {
	$run = PFH_Widgets_Migrate_Import::start( $id, $keys );
	if ( is_wp_error( $run ) ) { return $run; }
	$n = 0;
	while ( is_array( $run ) && 'running' === $run['state'] && $n++ < 400 ) {
		$run = PFH_Widgets_Migrate_Import::run_step();
	}
	return $run;
}

echo "\n── the import ──\n";
$run = mig_run( $id, array_keys( $items ) );
ok( 'it runs to the end', is_array( $run ) && 'done' === $run['state'], is_wp_error( $run ) ? $run->get_error_message() : wp_json_encode( $run['log'] ?? '' ) );
$errors = array_filter( (array) $run['log'], function ( $l ) { return 'error' === $l['level']; } );
ok( '  without errors', ! $errors, wp_json_encode( array_values( $errors ) ) );
$map = $run['map'];

$new_head = (int) ( $map['post'][ $t_head ] ?? 0 );
$new_foot = (int) ( $map['post'][ $t_foot ] ?? 0 );
$new_page = (int) ( $map['post'][ $p_new ] ?? 0 );
$new_img  = (int) ( $map['attachment'][ $img_new ] ?? 0 );
$new_font = (int) ( $map['attachment'][ $font_att ] ?? 0 );
$new_menu = (int) ( $map['term']['nav_menu'][ $menu ] ?? 0 );

ok( 'the header template is made under a number of its own', $new_head && $new_head !== $t_head && 'bricks_template' === get_post_type( $new_head ) );
ok( 'the order keeps its number, type and title', 'shop_order_placehold' === get_post_type( $t_head ) && 'Bestelling (mig)' === get_the_title( $t_head ) );
ok( 'the real order is untouched', $order_print === mig_order_print( $order_id ) );
ok( 'the live page under the same number is untouched', 'Van de winkel zelf.' === get_post_field( 'post_content', $live_page ) && ! get_post_meta( $live_page, '_bricks_page_content_2', true ) && $new_page !== $live_page );
$h = get_post_meta( $new_head, '_bricks_page_header_2', true );
ok( 'its logo points at the image made here', $new_img && $new_img === (int) $h[0]['settings']['logo']['id'] );
ok( '  at this site\'s address', 0 === strpos( $h[0]['settings']['logo']['url'], $home . '/wp-content/uploads/2026/09/mig-new' ) );
ok( 'its product stays the one it was', $a === (int) $h[0]['settings']['productId'] );
ok( 'its category stays the one it was', (string) $cat === (string) $h[0]['settings']['megaProdCat'] );
ok( 'its button links to the new page\'s new number', $new_page === (int) $h[0]['settings']['cta']['link']['postId'] );
ok( 'its menu is the menu made here', $new_menu && $new_menu === (int) $h[1]['settings']['menu'] );
ok( 'the other picture under the same name is not overwritten', $clash_md5_live === md5_file( $base . '2026/09/mig-clash.png' ) );
preg_match( '#/wp-content/uploads/(2026/09/mig-clash[^"]*)"#', $h[2]['settings']['text'], $m );
ok( '  the design\'s picture comes in beside it, and the text points there', ! empty( $m[1] ) && '2026/09/mig-clash.png' !== $m[1] && $clash_md5_staging === md5_file( $base . $m[1] ), $m[1] ?? '' );
ok( 'the footer is placed by its new number', $new_foot === (int) $h[3]['settings']['template'] );
$f  = get_post_meta( $new_foot, '_bricks_page_footer_2', true );
$fs = get_post_meta( $new_foot, '_bricks_template_settings', true );
ok( 'the footer\'s query keeps the product and category', [ $a ] === array_map( 'intval', $f[0]['settings']['query']['post__in'] ) && [ 'product_cat::' . $cat ] === $f[0]['settings']['query']['tax_query'] );
ok( 'backslashes in a layout survive', 'Escaped \\" quote and a backslash \\ stay.' === $f[1]['settings']['text'] );
ok( 'its conditions name the converted page', [ $p_old ] === array_map( 'intval', $fs['templateConditions'][0]['ids'] ) );
ok( 'the new page is made under the converted one', $new_page && $p_old === (int) wp_get_post_parent_id( $new_page ) && 'Nieuwe pagina-tekst.' === get_post_field( 'post_content', $new_page ) );
ok( 'the converted page gets its layout', ! empty( get_post_meta( $p_old, '_bricks_page_content_2', true ) ) && 'bricks' === get_post_meta( $p_old, '_bricks_editor_mode', true ) );
ok( '  and keeps its words and its Elementor data', 'Oude woorden die blijven.' === get_post_field( 'post_content', $p_old ) && '[{"elType":"section"}]' === get_post_meta( $p_old, '_elementor_data', true ) );
$pl = get_post_meta( $p_old, '_bricks_page_content_2', true );
ok( '  its image is the one already here', $img_old === (int) $pl[0]['settings']['image']['id'] );
$new_fonts = (int) ( $map['post'][ $fonts ] ?? 0 );
$faces     = get_post_meta( $new_fonts, 'bricks_font_faces', true );
ok( 'the custom font points at its file here', $new_font && (string) $new_font === (string) ( $faces['400']['woff2'] ?? '' ) && is_file( get_attached_file( $new_font ) ) );
ok( 'the fetched image is the same file', md5( $png . 'new' ) === md5_file( get_attached_file( $new_img ) ) );
ok( 'the product gets its fields, renumbered', $new_img === (int) get_post_meta( $a, '_pfh_bottom_image', true ) && [ $b ] === array_map( 'intval', (array) get_post_meta( $a, '_pfh_related', true ) ) );
$usp = get_post_meta( $a, '_pfh_usp', true );
ok( '  a repeater\'s icon too, and its link moves address', (string) $new_img === (string) $usp[0]['icon'] && false !== strpos( $usp[0]['text'], $home . '/winkel/' ) );
$coll = get_term_meta( $cat, PFH_Widgets_Collection::META, true );
ok( 'the category gets its content', $new_img === (int) $coll['header']['image'] && $b === (int) $coll['bundle']['product'] && $img_old === (int) $coll['bundle']['image'] );
$items_here = wp_get_nav_menu_items( $new_menu );
ok( 'the menu is made with its three items', 3 === count( $items_here ) && 'mig-main' === wp_get_nav_menu_object( $new_menu )->slug );
ok( '  pointing at the new page, the category and this site', $new_page === (int) $items_here[0]->object_id && $cat === (int) $items_here[1]->object_id && $home . '/winkel/' === $items_here[2]->url );
ok( '  with the sub-item under its parent', (int) $items_here[1]->menu_item_parent === (int) $items_here[0]->ID );
$renamed = get_term( $live_menu, 'nav_menu' );
ok( 'the menu that was here is kept, renamed', $renamed && false !== strpos( $renamed->name, 'before the move' ) && 1 === count( wp_get_nav_menu_items( $live_menu ) ) );
$mods = get_option( 'theme_mods_' . $stylesheet );
ok( 'the theme\'s menu place and logo follow', $new_menu === (int) $mods['nav_menu_locations']['primary'] && $new_img === (int) $mods['custom_logo'] );
ok( '  the old theme\'s CSS post stays this site\'s', 7 === (int) $mods['custom_css_post_id'] );
$gs = get_option( 'bricks_global_settings' );
ok( 'Bricks settings come over, renumbered', $new_img === (int) $gs['siteLogo']['id'] && is_array( get_option( 'bricks_color_palette' ) ) );
ok( 'this site\'s Bricks licence stays', 'LIVE-LICENCE' === get_option( 'bricks_license_key' ) );
$badge = get_option( 'pfh_badge' );
ok( 'module settings come over', 'staging' === $badge['migTest'] );
if ( $badge_secret ) {
	ok( '  and this site\'s API key stays', 'LIVE-KEY' === $badge[ $badge_secret ] );
}
ok( 'the home page is the new page', $new_page === (int) get_option( 'page_on_front' ) && 'page' === get_option( 'show_on_front' ) );
$dhl = get_option( 'woocommerce_dhlpwc_7_settings' );
ok( 'DHL gets the option names, and keeps its other settings', 'Thuisbezorgd' === $dhl['alternative_option_text_home'] && 'live' === $dhl['other'] );
$go = get_option( 'woocommerce_gateway_order' );
clean_post_cache( $a );
$pa_after = get_post( $a );
ok( 'the catalogue brings the product\'s text, pointing at this site', 0 === strpos( $pa_after->post_content, 'Nieuwe tekst' ) && false !== strpos( $pa_after->post_content, $home . '/wp-content/uploads/2026/09/mig-new' ) && 'Kort nieuw' === $pa_after->post_excerpt );
ok( '  its gallery, with the image made here', (string) $new_img === get_post_meta( $a, '_product_image_gallery', true ) );
ok( '  its tag, made here again', has_term( 'mig-tag', 'product_tag', $a ) );
ok( '  the flavour name with its emoji', false !== strpos( html_entity_decode( get_term( $mand, $ptax )->name ), "\u{1F34A}" ) );
ok( '  the variation\'s text and photo', 'Variatie nieuw' === get_post_meta( $vv, '_variation_description', true ) && $new_img === (int) get_post_meta( $vv, '_thumbnail_id', true ) );
ok( 'a blog post edited there gets its text', 'Blog nieuw' === get_post_field( 'post_content', $blog_old ) );
ok( 'an order bump gets its product and texts', [ 'olie' => '750 ml' ] === get_post_meta( $bump, '_wfob_selected_products', true ) );
$made_blog = get_page_by_path( 'mig-blog-nieuw', OBJECT, 'post' );
ok( 'a blog post written there is made here, with its image', $made_blog && 'Alleen op staging' === $made_blog->post_content && $new_img === (int) get_post_meta( $made_blog->ID, '_thumbnail_id', true ) );
ok( '  and leaves this shop\'s stock and Facebook id alone', $live_stock === get_post_meta( $a, '_stock', true ) && '99' !== $live_stock && 'LIVE-FB' === get_post_meta( $a, 'fb_product_item_id', true ) );
ok( 'every payment method has a number of its own', count( $go ) === count( array_unique( array_map( 'intval', $go ) ) ), wp_json_encode( $go ) );

echo "\n── importing again changes nothing twice ──\n";
$run2 = mig_run( $id, array_keys( $items ) );
ok( 'it runs', is_array( $run2 ) && 'done' === $run2['state'] );
ok( 'no second header template', 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bricks_template' AND post_title = 'Mig header'" ) );
ok( 'no second new page', 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'page' AND post_title = 'Nieuw (mig)'" ) );
ok( 'no second image', 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_title = 'Mig new image'" ) );
ok( 'the menu is seen to be the same, and kept', $new_menu === (int) ( $run2['map']['term']['nav_menu'][ $menu ] ?? 0 ) && 1 === count( get_terms( [ 'taxonomy' => 'nav_menu', 'hide_empty' => false, 'name__like' => 'before the move' ] ) ) );
ok( 'the second run changed nothing', ! array_filter( $I::journal_entries( $run2 ), function ( $e ) { return 'option' !== $e['type'] || 'woocommerce_gateway_order' !== $e['name']; } ), wp_json_encode( array_slice( $I::journal_entries( $run2 ), 0, 5 ) ) );

echo "\n── undo, twice: back to the shop as it was ──\n";
$u = $I::undo();
ok( 'the second import is undone', is_array( $u ) );
ok( '  the first is now the one to undo', 'done' === get_option( 'pfh_migrate_run' )['state'] && $run['id'] === get_option( 'pfh_migrate_run' )['id'] );
$u = $I::undo();
ok( 'the first import is undone', is_array( $u ) && $u['undone'] > 10, wp_json_encode( $u ) );
ok( 'nothing left to undo', is_wp_error( $I::undo() ) );
$after = $snapshot();
foreach ( $before as $k => $v ) {
	ok( "  $k is as it was", $v === $after[ $k ], wp_json_encode( [ $v, $after[ $k ] ] ) );
}
ok( 'the templates and the new page are gone', ! get_post( $new_head ) && ! get_post( $new_foot ) && ! get_post( $new_page ) );
ok( 'the fetched image and its file are gone', ! get_post( $new_img ) && ! file_exists( $base . '2026/09/mig-new.png' ) );
ok( 'the picture under the clashing name is still this site\'s', $clash_md5_live === md5_file( $base . '2026/09/mig-clash.png' ) && ! file_exists( $base . $m[1] ) );
ok( 'the order is still untouched', $order_print === mig_order_print( $order_id ) && 'shop_order_placehold' === get_post_type( $t_head ) );
clean_post_cache( $a );
ok( 'the product\'s text, gallery and tags are as they were', 'Oude tekst' === get_post( $a )->post_content && 'Kort oud' === get_post( $a )->post_excerpt && '' === get_post_meta( $a, '_product_image_gallery', true ) && ! has_term( '', 'product_tag', $a ) );
ok( '  the flavour name and the variation too', 'Mandarijn' === get_term( $mand, $ptax )->name && 'Variatie oud' === get_post_meta( $vv, '_variation_description', true ) && '' === get_post_meta( $vv, '_thumbnail_id', true ) );
ok( '  the tag made for it is gone again', ! get_term_by( 'slug', 'mig-tag', 'product_tag' ) );
ok( 'the order bump is as it was', [ 'olie' => '500 ml' ] === get_post_meta( $bump, '_wfob_selected_products', true ) );
ok( 'the blog post has its old text, and the new one is gone', 'Blog oud' === get_post_field( 'post_content', $blog_old ) && ! get_page_by_path( 'mig-blog-nieuw', OBJECT, 'post' ) );
ok( 'the old menu has its name and address back', 'Hoofdmenu live' === get_term( $live_menu, 'nav_menu' )->name && 'mig-main' === get_term( $live_menu, 'nav_menu' )->slug );

echo "\n── a selection, and a half-finished run carried on ──\n";
$run3 = $I::start( $id, [ 'post:' . $t_head, 'post:' . $t_foot ] );
$I::run_step();
$mid = get_option( 'pfh_migrate_run' );
ok( 'a run can be picked up where it is', 'running' === $mid['state'] );
$n = 0;
while ( 'running' === ( $r = $I::run_step() )['state'] && $n++ < 200 ) {}
ok( '  and finishes', 'done' === $r['state'] );
ok( 'only what was ticked is done', ! get_option( 'bricks_global_settings' ) && ! get_post_meta( $p_old, '_bricks_page_content_2', true ) && 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bricks_template' AND post_title = 'Mig header'" ) );
$I::undo();
ok( '  and undone', 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bricks_template' AND post_title LIKE 'Mig %'" ) );

echo "\n── the old theme's waitlist in the same database ──\n";
$W      = 'PFH_Widgets_Waitlist';
$legacy = $wpdb->prefix . 'xstore_waitlist';
$W::maybe_install();
$wpdb->query( "DROP TABLE IF EXISTS {$legacy}" );
ok( 'no table, no offer', null === $W::legacy_table_info() );
$wpdb->query( "CREATE TABLE {$legacy} ( id bigint(20) unsigned NOT NULL AUTO_INCREMENT, product_id bigint(20) NOT NULL, email varchar(190) NOT NULL, created_at datetime NULL, notified tinyint(1) NOT NULL DEFAULT 0, PRIMARY KEY (id) )" );
$wpdb->query( $wpdb->prepare( "INSERT INTO {$legacy} (product_id, email, created_at, notified) VALUES (%d, %s, %s, 0), (%d, %s, %s, 1), (%d, %s, %s, 0)", $a, 'mig1@example.test', '2026-09-01 10:00:00', $a, 'mig2@example.test', '2026-09-02 10:00:00', $a, 'not-an-address', '2026-09-03 10:00:00' ) );
$info = $W::legacy_table_info();
ok( 'the table and its columns are found', $info && 3 === $info['rows'] && 'email' === $info['columns']['email'] && 'product_id' === $info['columns']['product'] && '' === $info['problem'], wp_json_encode( $info ) );
$wpdb->query( "DELETE FROM {$W::table()} WHERE email LIKE 'mig%@example.test'" );
$r1 = $W::import_from_table();
ok( 'its sign-ups are taken over', is_array( $r1 ) && 2 === $r1['added'] && 1 === $r1['skipped'], wp_json_encode( $r1 ) );
ok( '  the one already mailed is marked so', 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$W::table()} WHERE email = 'mig2@example.test' AND notified_at IS NOT NULL" ) );
$r2 = $W::import_from_table();
ok( 'taking over again adds nothing', is_array( $r2 ) && 0 === $r2['added'], wp_json_encode( $r2 ) );
// An earlier takeover that did not know which were mailed: put right.
$wpdb->query( "UPDATE {$W::table()} SET notified_at = NULL WHERE email = 'mig2@example.test'" );
$r3 = $W::import_from_table();
ok( '  but marks one it took over unknowing as already mailed', is_array( $r3 ) && 0 === $r3['added'] && 1 === $r3['updated'] && 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$W::table()} WHERE email = 'mig2@example.test' AND notified_at IS NOT NULL" ), wp_json_encode( $r3 ) );
$wpdb->query( "DROP TABLE IF EXISTS {$legacy}" );
$wpdb->query( "CREATE TABLE {$legacy} ( id bigint(20) unsigned NOT NULL AUTO_INCREMENT, product_id bigint(20) NOT NULL, email varchar(190) NOT NULL, created_at datetime NULL, notification_sent varchar(10) NOT NULL DEFAULT 'no', PRIMARY KEY (id) )" );
$info2 = $W::legacy_table_info();
ok( 'a "mailed" column under another name is found too', 'notification_sent' === $info2['columns']['mailed'] && in_array( 'notification_sent', $info2['names'], true ), wp_json_encode( $info2['columns'] ) );
$html = $render();
ok( 'the screen offers it', false !== strpos( $html, 'pfh_migrate_waitlist' ) );
$wpdb->query( "DELETE FROM {$W::table()} WHERE email LIKE 'mig%@example.test'" );
$wpdb->query( "DROP TABLE IF EXISTS {$legacy}" );

echo "\n── the elements no longer name the staging site ──\n";
$named = [];
foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/{elements,includes}/*.php', GLOB_BRACE ) as $file ) {
	if ( preg_match( '/kinsta\.cloud|m01a032ada4d2735fbc629e14eb62edd/', file_get_contents( $file ) ) ) { $named[] = basename( $file ); }
}
ok( 'no staging address in the code', ! $named, implode( ', ', $named ) );
ok( 'the code\'s own default pictures are found for the export', count( $E::code_assets() ) > 5, (string) count( $E::code_assets() ) );

// ── Tidy up ───────────────────────────────────────────────────────────────
wp_delete_post( $blog_old, true );
wp_delete_post( $bump, true );
foreach ( [ $vv, $vpid ] as $x ) { $p = wc_get_product( $x ); if ( $p ) { $p->delete( true ); } }
foreach ( (array) get_terms( [ 'taxonomy' => $ptax, 'hide_empty' => false ] ) as $x ) { if ( ! is_wp_error( $x ) ) { wp_delete_term( $x->term_id, $ptax ); } }
wc_delete_attribute( $attr_id );
$st = get_term_by( 'slug', 'mig-tag', 'product_tag' ); if ( $st ) { wp_delete_term( $st->term_id, 'product_tag' ); }
$order->delete( true );
wp_delete_post( $taken, true );
wp_delete_post( $live_page, true );
foreach ( [ $p_old, $img_old ] as $x ) { wp_delete_post( $x, true ); }
wp_delete_attachment( $img_old, true );
foreach ( [ $a, $b ] as $x ) { $p = wc_get_product( $x ); if ( $p ) { $p->delete( true ); } }
wp_delete_term( $cat, 'product_cat' );
wp_delete_nav_menu( $live_menu );
@unlink( $base . '2026/09/mig-clash.png' );
foreach ( glob( $source . '/2026/09/*' ) as $x ) { @unlink( $x ); }
update_option( 'theme_mods_' . $stylesheet, $mods_before_staging );
update_option( 'show_on_front', $front_before[0] );
update_option( 'page_on_front', $front_before[1] );
false === $gateway_order_before ? delete_option( 'woocommerce_gateway_order' ) : update_option( 'woocommerce_gateway_order', $gateway_order_before );
foreach ( [ 'pfh_migrate_run', 'pfh_migrate_history', 'bricks_license_key', 'woocommerce_dhlpwc_7_settings' ] as $o ) { delete_option( $o ); }
$badge = (array) get_option( 'pfh_badge', [] ); unset( $badge['migTest'] ); if ( $badge_secret ) { unset( $badge[ $badge_secret ] ); } update_option( 'pfh_badge', $badge );
foreach ( glob( $I::dir() . '{export,journal}-*', GLOB_BRACE ) as $x ) { @unlink( $x ); }

echo "\n$pass passed, $fail failed\n";
