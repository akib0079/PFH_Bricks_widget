<?php
/**
 * The recipe cards (PFH_Element_Recipes): the "Recepten van Gia Giamas"
 * page, moved from Elementor to Bricks (2026-10-02).
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-recipes.php';

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function recipes( array $over = [], $defaults = true ) {
	$el       = new PFH_Element_Recipes( [ 'id' => 'rc' . wp_rand( 1, 99999 ) ] );
	$el->name = 'pfh-recipes';
	$el->set_control_groups();
	$el->set_controls();
	$s = [];
	if ( $defaults ) {
		foreach ( $el->controls as $k => $c ) {
			if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; }
		}
	}
	$el->settings = array_merge( $s, $over );
	ob_start();
	$el->render();
	return (string) ob_get_clean();
}

echo "── as it comes ──\n";
$html = recipes();
ok( 'six recipe cards', 6 === substr_count( $html, 'class="pfh-rc__item"' ) );
ok( 'each a link to its recipe page', false !== strpos( $html, 'href="/home-made-juice/"' ) && false !== strpos( $html, 'href="/home-made-ice-tea/"' ) && 6 === substr_count( $html, '<a class="pfh-rc__card"' ) );
ok( 'the heading keeps its italic accent', false !== strpos( $html, '<h2 class="pfh-rc__title">Wat maak je met <em>Gia Giamas</em>?</h2>' ) );
ok( 'a small line above it', false !== strpos( $html, '<p class="pfh-rc__eyebrow">Recepten</p>' ) );
ok( 'every card says where it leads', 6 === substr_count( $html, 'Bekijk recept' ) );
ok( 'three across unless set', false !== strpos( $html, '--pfh-rc-cols:3;' ) );
ok( 'no two recipes share a description', count( array_unique( wp_list_pluck( PFH_Element_Recipes::default_items(), 'text' ) ) ) === 6 );

echo "\n── with a photo ──\n";
$up   = wp_upload_dir();
$path = trailingslashit( $up['path'] ) . 'pfh-recipe-test.jpg';
$im   = imagecreatetruecolor( 1200, 900 );
imagefill( $im, 0, 0, imagecolorallocate( $im, 230, 200, 120 ) );
imagejpeg( $im, $path, 80 );
imagedestroy( $im );
require_once ABSPATH . 'wp-admin/includes/image.php';
$att = wp_insert_attachment( [ 'post_mime_type' => 'image/jpeg', 'post_title' => 'Granita', 'post_status' => 'inherit' ], $path );
wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $path ) );

$one = recipes( [ 'items' => [ [ 'title' => 'Granita slush', 'text' => 'IJskoud.', 'image' => [ 'id' => $att, 'url' => wp_get_attachment_url( $att ) ], 'link' => [ 'type' => 'external', 'url' => '/home-made-slush/' ] ] ] ] );
ok( 'the photo comes with a srcset', false !== strpos( $one, 'class="pfh-rc__img' ) && false !== strpos( $one, 'srcset=' ) );
ok( '  and the recipe name as its alt text', false !== strpos( $one, 'alt="Granita slush"' ) );

echo "\n── edge cases ──\n";
$nolink = recipes( [ 'items' => [ [ 'title' => 'Binnenkort', 'text' => 'Nog geen pagina.' ] ] ] );
ok( 'a recipe without a page is a card, not a link', false === strpos( $nolink, '<a class="pfh-rc__card"' ) && false !== strpos( $nolink, '<div class="pfh-rc__card"' ) && false === strpos( $nolink, 'Bekijk recept' ) );
$bare = recipes( [ 'eyebrow' => '', 'heading' => '', 'lede' => '' ] );
ok( 'a heading cleared in Bricks stays away', false === strpos( $bare, 'pfh-rc__head' ) );
ok( 'columns are kept between 2 and 4', false !== strpos( recipes( [ 'columns' => 9 ] ), '--pfh-rc-cols:4;' ) );
ok( 'no recipes, nothing on the page', '' === trim( recipes( [ 'items' => [] ] ) ) );
ok( 'a script in a name is printed as text', false === strpos( recipes( [ 'items' => [ [ 'title' => '<script>x</script>', 'text' => 'a' ] ] ] ), '<script>' ) );

wp_delete_attachment( $att, true );

echo "\n$pass passed, $fail failed\n";
