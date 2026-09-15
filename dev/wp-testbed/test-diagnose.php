<?php
/**
 * The Diagnose report has to be right, because it is what decisions get made
 * from — and the decision it informs is "why does my page show an old version".
 *
 * Two arrangements look identical from the builder and are the usual cause:
 * a second template also claiming the page, and a page carrying Bricks content
 * of its own, which wins over every template.
 */
require __DIR__ . '/wp-load.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$made = [];

function bricks_post( $type, $title, array $elements, $status = 'publish' ) {
	global $made;
	$id = wp_insert_post( [ 'post_type' => $type, 'post_title' => $title, 'post_status' => $status ] );
	update_post_meta( $id, '_bricks_page_content_2', $elements );
	$made[] = $id;

	return $id;
}

echo "── a template with our elements in it is reported ──\n";

$tpl = bricks_post( 'bricks_template', 'Shop', [
	[ 'id' => 'a1', 'name' => 'pfh-shophead', 'settings' => [ 'title' => 'Shop' ] ],
	[ 'id' => 'a2', 'name' => 'pfh-highlight', 'settings' => [] ],
] );
update_post_meta( $tpl, '_bricks_template_type', 'archive' );

$r = PFH_Widgets_Diagnose::report();

ok( 'the template is listed', false !== strpos( $r, '#' . $tpl . ' "Shop"' ) );
ok( 'with its element count', false !== strpos( $r, '2 element(s)' ) );
ok( 'and our elements named', false !== strpos( $r, 'pfh-shophead, pfh-highlight' ) );
ok( 'a highlight saved with no settings is shown as such', false !== strpos( $r, 'pfh-highlight — 0 setting(s)' ) );

echo "\n── a template with no condition cannot apply anywhere ──\n";
ok( 'and it says so rather than staying quiet', false !== strpos( $r, 'condition: none set' ) );

echo "\n── a second template claiming the same thing is visible ──\n";
$tpl2 = bricks_post( 'bricks_template', 'Shop', [
	[ 'id' => 'b1', 'name' => 'pfh-shophead', 'settings' => [] ],
] );
update_post_meta( $tpl2, '_bricks_template_type', 'archive' );

$r = PFH_Widgets_Diagnose::report();
ok( 'both templates of the same name are listed', 2 === substr_count( $r, '"Shop" [publish]' ), substr_count( $r, '"Shop" [publish]' ) . ' listed' );
ok( 'each with its own last-edited time', 2 === substr_count( $r, '| edited ' ) );

echo "\n── a page with Bricks content of its own is called out ──\n";
/*
 * This is the arrangement that makes template edits look like they vanish:
 * the page renders itself, and the template is never consulted.
 */
$shop = (int) wc_get_page_id( 'shop' );
update_post_meta( $shop, '_bricks_page_content_2', [
	[ 'id' => 'c1', 'name' => 'pfh-products', 'settings' => [] ],
] );

$r = PFH_Widgets_Diagnose::report();
ok( 'the shop page is identified', false !== strpos( $r, '== the WooCommerce shop page ==' ) );
ok( 'its own content is flagged', false !== strpos( $r, 'HAS ITS OWN BRICKS CONTENT' ) );
ok( 'and what that means is spelled out', false !== strpos( $r, 'wins over any template' ) );

delete_post_meta( $shop, '_bricks_page_content_2' );
$r = PFH_Widgets_Diagnose::report();
ok( 'without it, the template is said to decide', false !== strpos( $r, 'No Bricks content of its own' ) );

echo "\n── the report never takes the site down ──\n";
update_post_meta( $tpl, '_bricks_page_content_2', 'not an array at all' );
$threw = '';
try { PFH_Widgets_Diagnose::report(); } catch ( \Throwable $e ) { $threw = $e->getMessage(); }
ok( 'corrupt Bricks data does not throw', '' === $threw, $threw );

foreach ( $made as $id ) { wp_delete_post( $id, true ); }

echo "\n$pass passed, $fail failed\n";
