<?php
require __DIR__ . '/wp-load.php';

$existing = get_page_by_path( 'shop-test' );

if ( ! $existing ) {
	$id = wp_insert_post(
		[
			'post_title'   => 'Shop test',
			'post_name'    => 'shop-test',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[pfh_archive_test]',
		]
	);
	echo "page created: #{$id}\n";
} else {
	echo "page already there: #{$existing->ID}\n";
}

echo "bricks stub: " . ( class_exists( '\Bricks\Element' ) ? 'loaded' : 'MISSING' ) . "\n";
echo "archive element: " . ( file_exists( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-archive.php' ) ? 'present' : 'MISSING' ) . "\n";
flush_rewrite_rules( false );
echo "permalinks flushed\n";
