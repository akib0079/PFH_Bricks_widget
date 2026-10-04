<?php
/**
 * The pages moved over from Elementor to Bricks (2026-10-04).
 *
 * Each keeps its Elementor data, and Elementor kept swapping every Bricks
 * text element on such a page for the whole old layout: the first converted
 * page showed the old design five times and none of its new words. These
 * checks run the plugin's guard against a stand-in for Elementor's front end.
 */
require __DIR__ . '/wp-load.php';
require_once __DIR__ . '/fixture-elementor-stub.php';

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

if ( ! defined( 'BRICKS_DB_PAGE_CONTENT' ) ) {
	define( 'BRICKS_DB_PAGE_CONTENT', '_bricks_page_content_2' );
}

$legacy_layout = [
	[ 'id' => 'aaaaaa', 'name' => 'section', 'parent' => 0, 'children' => [ 'bbbbbb' ], 'settings' => [ '_cssClasses' => 'pfh-legacy' ] ],
	[ 'id' => 'bbbbbb', 'name' => 'container', 'parent' => 'aaaaaa', 'children' => [ 'cccccc' ], 'settings' => [ '_cssClasses' => 'pfh-legacy__inner' ] ],
	[ 'id' => 'cccccc', 'name' => 'text', 'parent' => 'bbbbbb', 'children' => [], 'settings' => [ 'text' => '<p>Nieuwe woorden</p>', '_cssClasses' => 'pfh-legacy__text' ] ],
];
$plain_layout = [ [ 'id' => 'dddddd', 'name' => 'text', 'parent' => 0, 'children' => [], 'settings' => [ 'text' => '<p>Gewoon Bricks</p>' ] ] ];

function legacy_page( $title, $layout, $mode = 'bricks' ) {
	$id = wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title ] );
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_data', '[]' );
	if ( $layout ) {
		update_post_meta( $id, BRICKS_DB_PAGE_CONTENT, wp_slash( $layout ) );
	}
	if ( $mode ) {
		update_post_meta( $id, '_bricks_editor_mode', $mode );
	}
	return $id;
}

function visit( $id ) {
	$GLOBALS['wp_query']     = new WP_Query( [ 'page_id' => $id ] );
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
	$GLOBALS['post']         = get_post( $id );
	setup_postdata( $GLOBALS['post'] );
	wp_dequeue_style( 'pfh-legacy' );
	$front          = \Elementor\Plugin::instance()->frontend;
	$front->removed = 0;
	$front->remove_content_filter();
	$front->add_content_filter();
	$front->removed = 0;
	PFH_Widgets_Assets::quiet_elementor();
	PFH_Widgets_Assets::maybe_legacy();
	return $front;
}

$converted = legacy_page( 'Gia giamas (test)', $legacy_layout );
$bricks    = legacy_page( 'Al Bricks (test)', $plain_layout );
$elementor = legacy_page( 'Nog Elementor (test)', [] , '' );
$switched  = legacy_page( 'Terug naar WordPress (test)', $legacy_layout, 'wordpress' );

PFH_Widgets_Assets::register();

echo "── a page moved over to Bricks ──\n";
ok( 'the guard runs once Elementor has set up the page', 99 === has_action( 'template_redirect', [ 'PFH_Widgets_Assets', 'quiet_elementor' ] ) );
$front = visit( $converted );
ok( 'Elementor is told to leave its content alone', 1 === $front->removed );
$out = apply_filters( 'the_content', '<p>Nieuwe woorden</p>' );
ok( 'a text element prints its own words', false !== strpos( $out, 'Nieuwe woorden' ) );
ok( '  and not the old Elementor layout', false === strpos( $out, 'elementor-old' ) );
ok( 'the converted look is loaded', wp_style_is( 'pfh-legacy', 'enqueued' ) );

echo "\n── any other page Bricks draws ──\n";
$front = visit( $bricks );
ok( 'Elementor stays out there too', 1 === $front->removed && false === strpos( apply_filters( 'the_content', '<p>x</p>' ), 'elementor-old' ) );
ok( '  without the converted look', ! wp_style_is( 'pfh-legacy', 'enqueued' ) );

echo "\n── pages that are still Elementor's ──\n";
$front = visit( $elementor );
ok( 'a page without a Bricks layout keeps Elementor', 0 === $front->removed && false !== strpos( apply_filters( 'the_content', '<p>x</p>' ), 'elementor-old' ) );
ok( '  and its look', ! wp_style_is( 'pfh-legacy', 'enqueued' ) );
$front = visit( $switched );
ok( 'a page switched back to the WordPress editor keeps Elementor', 0 === $front->removed );

echo "\n── the look of the moved pages ──\n";
$legacy_css = file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/assets/css/pfh-legacy.css' );
ok( 'customer quotes keep their speech bubbles', false !== strpos( $legacy_css, '.pfh-legacy__quote-text::after' ) );
ok( 'the recipes keep their previous / all / next row', false !== strpos( $legacy_css, '.pfh-legacy__postnav' ) && false !== strpos( $legacy_css, '.pfh-legacy__nav-all::before' ) );
ok( '  showing only the arrows on a phone', (bool) preg_match( '/@media \(max-width: 575px\).*pfh-legacy__nav-prev/s', $legacy_css ) );
ok( 'a dark band keeps white type and a white button', false !== strpos( $legacy_css, '.pfh-legacy.pfh-legacy--dark' ) && false !== strpos( $legacy_css, '.pfh-legacy--dark .pfh-legacy__btn' ) );
ok( 'partner logos are shown whole', false !== strpos( $legacy_css, 'object-fit: contain' ) && false !== strpos( $legacy_css, 'background-size: contain' ) );
ok( 'tables and dividers in the long texts are styled', false !== strpos( $legacy_css, '.pfh-legacy__text table' ) && false !== strpos( $legacy_css, '.pfh-legacy__text hr' ) );
ok( 'paragraphs set as h5 read as body text', (bool) preg_match( '/\.pfh-legacy__text h5,[^{]*\{[^}]*font-weight: 400/', $legacy_css ) );
ok( 'the thank-you page dresses FunnelKit\'s order and customer details', false !== strpos( $legacy_css, '.pfh-ty .wfty_title' ) && false !== strpos( $legacy_css, '.pfh-ty__check' ) );
ok( 'braces balance', substr_count( $legacy_css, '{' ) === substr_count( $legacy_css, '}' ) );

echo "\n── where it must not run ──\n";
$front = visit( $converted );
$front->add_content_filter();
$front->removed = 0;
$GLOBALS['wp_query'] = new WP_Query( [ 'post_type' => 'page', 'posts_per_page' => 2 ] );
$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
PFH_Widgets_Assets::quiet_elementor();
ok( 'not on a list of pages', 0 === $front->removed );

foreach ( [ $converted, $bricks, $elementor, $switched ] as $id ) {
	wp_delete_post( $id, true );
}
wp_reset_postdata();

echo "\n$pass passed, $fail failed\n";
