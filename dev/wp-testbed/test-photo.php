<?php
/**
 * Product photographs on the cards: which kind each one is, served sharp,
 * the next photo on hover, and the card defaults that were retired.
 *
 * Also the repair of photographs an upload optimiser turned from PNG into a
 * JPEG with a black background.
 */
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

foreach ( [ 'products', 'archive' ] as $f ) {
	require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-' . $f . '.php';
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$made = [];

/** An attachment drawn with GD: a cut-out PNG or a photo with a background. */
function picture( $name, $transparent, $as = 'png' ) {
	global $made;
	$up   = wp_upload_dir();
	$path = trailingslashit( $up['path'] ) . $name . '.' . $as;
	$im   = imagecreatetruecolor( 800, 800 );

	if ( $transparent ) {
		imagesavealpha( $im, true );
		imagealphablending( $im, false );
		imagefill( $im, 0, 0, imagecolorallocatealpha( $im, 0, 0, 0, 127 ) );
	} else {
		imagefill( $im, 0, 0, imagecolorallocate( $im, 250, 250, 250 ) );
	}

	imagealphablending( $im, true );
	imagefilledellipse( $im, 400, 400, 300, 500, imagecolorallocate( $im, 120, 80, 30 ) );

	if ( 'png' === $as ) {
		imagepng( $im, $path );
	} else {
		imagejpeg( $im, $path, 80 );
	}

	imagedestroy( $im );

	$id = wp_insert_attachment( [ 'post_mime_type' => 'png' === $as ? 'image/png' : 'image/jpeg', 'post_title' => $name, 'post_status' => 'inherit' ], $path );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $path ) );
	$made[] = $id;

	return $id;
}

// Leftovers of a run that stopped half way.
foreach ( get_posts( [ 'post_type' => 'attachment', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 's' => 'pfh-test-' ] ) as $old ) {
	wp_delete_attachment( $old, true );
}
foreach ( get_posts( [ 'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'title' => 'PFH photo test' ] ) as $old ) {
	wp_delete_post( $old, true );
}

echo "── which kind of photograph ──\n";
$cut   = picture( 'pfh-test-cutout', true );
$photo = picture( 'pfh-test-photo', false, 'jpg' );
$solid = picture( 'pfh-test-solid-png', false );
ok( 'a transparent PNG is a cut-out', false === PFH_Widgets_Photo::has_backdrop( $cut ) );
ok( 'a JPEG brings its own background', true === PFH_Widgets_Photo::has_backdrop( $photo ) );
ok( 'an opaque PNG brings its own background too', true === PFH_Widgets_Photo::has_backdrop( $solid ) );
ok( 'the verdict is kept on the attachment', '' !== get_post_meta( $cut, PFH_Widgets_Photo::META, true ) );
wp_update_attachment_metadata( $cut, wp_get_attachment_metadata( $cut ) );
ok( 'and forgotten when the file is regenerated', '' === get_post_meta( $cut, PFH_Widgets_Photo::META, true ) );

$img = PFH_Widgets_Photo::card_image( $photo, '360px' );
ok( 'a card gets a srcset', ! empty( $img['srcset'] ) && false !== strpos( $img['srcset'], 'w,' ) );
ok( 'and a sharp default source, not the 300px thumbnail', false === strpos( $img['src'], '-300x300' ), $img['src'] );

echo "\n── on a card ──\n";
$p = new WC_Product_Simple();
$p->set_name( 'PFH photo test' );
$p->set_status( 'publish' );
$p->set_regular_price( '20' );
$p->set_sale_price( '15' );
$p->set_image_id( $photo );
$p->set_gallery_image_ids( [ $cut ] );
$pid = $p->save();

function render_el( $class, $name, array $over = [] ) {
	$el       = new $class( [ 'id' => 'ph' . $name ] );
	$el->name = $name;
	$el->set_control_groups();
	$el->set_controls();
	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; }
	}
	$el->settings = array_merge( $s, $over );
	ob_start();
	$el->render();
	return ob_get_clean();
}

$html = render_el( 'PFH_Element_Products', 'pfh-products', [ 'source' => 'manual_ids', 'ids' => (string) $pid, 'productIds' => (string) $pid ] );

if ( false === strpos( $html, 'PFH photo test' ) ) {
	// The slider's own source names differ between releases; ask it for on-sale.
	$html = render_el( 'PFH_Element_Products', 'pfh-products', [ 'source' => 'onsale', 'limit' => 50 ] );
}

$card = '';
if ( preg_match( '#<li class="pfh-prod__item">(?:(?!</li>).)*PFH photo test(?:(?!</li>).)*</li>#s', $html, $m ) ) {
	$card = $m[0];
}

ok( 'the card was drawn', '' !== $card );
ok( 'a photo with a background fills the tile', false !== strpos( $card, 'pfh-prod__media--photo' ) );
ok( 'the photo carries a srcset', false !== strpos( $card, 'srcset=' ) );
ok( 'the next photo is there for hover', false !== strpos( $card, 'pfh-prod__img--next' ) );
ok( 'and is decorative', (bool) preg_match( '#pfh-prod__img--next[^>]*aria-hidden="true"#', $card ) );
ok( 'a cut-out next photo is not drawn full-bleed', false === strpos( $card, 'pfh-prod__img--next is-photo' ) );
ok( 'incl. btw appears once', 1 === substr_count( $card, 'pfh-prod__suffix' ), (string) substr_count( $card, 'pfh-prod__suffix' ) );
ok( 'no shop-wide stars on a product without reviews', false === strpos( $card, 'pfh-prod__stars' ) );

$html = render_el( 'PFH_Element_Products', 'pfh-products', [ 'source' => 'onsale', 'limit' => 50, 'imageSwap' => false ] );
ok( 'the hover photo can be switched off', false === strpos( $html, 'pfh-prod__img--next' ) );

echo "\n── retired defaults ──\n";
$html = render_el( 'PFH_Element_Products', 'pfh-products', [ 'source' => 'onsale', 'limit' => 4, 'priceSize' => 14, 'cartHoverBg' => [ 'hex' => '#3f4c3e' ] ] );
ok( 'a price saved at the old 14px default grows to 16px', false !== strpos( $html, '--pfh-pr-size:16px' ) );
ok( 'the old olive hover becomes the button one tint darker', false !== strpos( $html, 'color-mix(in srgb, var(--pfh-cart-bg) 84%, #000)' ) );
$html = render_el( 'PFH_Element_Products', 'pfh-products', [ 'source' => 'onsale', 'limit' => 4, 'priceSize' => 18, 'cartHoverBg' => [ 'hex' => '#112233' ] ] );
ok( 'a size someone chose is kept', false !== strpos( $html, '--pfh-pr-size:18px' ) );
ok( 'and so is a hover someone chose', false !== stripos( $html, '#112233' ) );

$html = render_el( 'PFH_Element_Archive', 'pfh-archive', [ 'titleColor' => [ 'hex' => '#51604f' ], 'priceColor' => [ 'hex' => '#51604f' ], 'priceSize' => 12, 'oldPriceSize' => 10 ] );
ok( 'the archive title moves from green-grey to the home page\'s near-black', false !== stripos( $html, '--pfh-t-color:#22301c' ) );
ok( 'its price too', false !== stripos( $html, '--pfh-pr-color:#212121' ) );
ok( 'and a size up', false !== strpos( $html, '--pfh-pr-size:15px' ) );

echo "\n── the flattened-PNG repair ──\n";
$fl = picture( 'pfh-test-flattened', true );
$png = get_attached_file( $fl );
$jpg = preg_replace( '/\.png$/', '.jpg', $png );
$im  = imagecreatefrompng( $png );
imagejpeg( $im, $jpg, 80 );
imagedestroy( $im );
update_attached_file( $fl, $jpg );   // what the optimiser left: a PNG in name, a JPEG on disk

$found = array_values( array_filter( PFH_Widgets_Photo::flattened(), static function ( $r ) use ( $fl ) { return $r['id'] === $fl; } ) );
ok( 'it is found', 1 === count( $found ) );
ok( 'with its original PNG beside it', $found && $found[0]['png'] === $png );
ok( 'and it repairs', $found && PFH_Widgets_Photo::repair( $fl, $found[0]['png'] ) );
ok( 'the attachment is a PNG again', get_attached_file( $fl ) === $png );
ok( 'and a cut-out again', false === PFH_Widgets_Photo::has_backdrop( $fl ) );
ok( 'the JPEG is left on disk', file_exists( $jpg ) );
ok( 'nothing else is listed', ! array_filter( PFH_Widgets_Photo::flattened(), static function ( $r ) use ( $fl ) { return $r['id'] === $fl; } ) );

// A palette PNG whose see-through part lives in a tRNS chunk: the kind the
// optimiser misjudged.
$pal = picture( 'pfh-test-palette', true );
$ppng = get_attached_file( $pal );
$im   = imagecreatefrompng( $ppng );
imagetruecolortopalette( $im, false, 64 );
imagecolortransparent( $im, imagecolorat( $im, 0, 0 ) );
imagepng( $im, $ppng );
imagedestroy( $im );
$pjpg = preg_replace( '/\.png$/', '.jpg', $ppng );
imagejpeg( imagecreatefrompng( $ppng ), $pjpg, 80 );
update_attached_file( $pal, $pjpg );

// And a photograph saved as PNG, opaque throughout: the JPEG is better.
$opq  = picture( 'pfh-test-opaque', false );
$opng = get_attached_file( $opq );
$ojpg = preg_replace( '/\.png$/', '.jpg', $opng );
imagejpeg( imagecreatefrompng( $opng ), $ojpg, 80 );
update_attached_file( $opq, $ojpg );

$rows = [];
foreach ( PFH_Widgets_Photo::flattened() as $r ) { $rows[ $r['id'] ] = $r; }
ok( 'a palette PNG with a transparent entry counts as see-through', ! empty( $rows[ $pal ]['transparent'] ) );
ok( 'an opaque PNG does not', isset( $rows[ $opq ] ) && empty( $rows[ $opq ]['transparent'] ) );
require_once ABSPATH . 'wp-admin/includes/template.php';
ob_start();
PFH_Widgets_Photo::render_repair_box();
$box = ob_get_clean();
ok( 'the repair box lists the see-through one', false !== strpos( $box, 'pfh-test-palette' ) );
ok( 'and leaves the opaque one out', false === strpos( $box, 'pfh-test-opaque' ) );

/* ---- clean up ---- */
wp_delete_post( $pid, true );
foreach ( $made as $id ) { wp_delete_attachment( $id, true ); }
@unlink( $jpg );
@unlink( $pjpg );
@unlink( $ojpg );

echo "\n$pass passed, $fail failed\n";
