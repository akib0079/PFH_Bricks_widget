<?php
require __DIR__ . '/wp-load.php';
$existing = get_page_by_path( 'all-widgets' );
$id = wp_insert_post( [
	'ID'           => $existing ? $existing->ID : 0,
	'post_title'   => 'All widgets',
	'post_name'    => 'all-widgets',
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_content' => '[pfh_all_widgets]',
] );
echo 'page: ' . get_permalink( $id ) . "\n";
