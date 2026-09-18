<?php
require __DIR__ . '/wp-load.php';
header( 'Content-Type: text/plain' );
global $wpdb;

$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('product','product_variation') AND (post_name LIKE 'gia-giamas-starterspakket%' OR post_title LIKE 'Gia...Giamas Starterspakket%')" );
foreach ( $ids as $id ) {
	foreach ( get_attached_media( '', $id ) as $a ) { wp_delete_post( $a->ID, true ); }
	$p = wc_get_product( $id );
	if ( $p && $p->is_type( 'variable' ) ) { foreach ( $p->get_children() as $c ) { wp_delete_post( $c, true ); } }
	wp_delete_post( $id, true );
}
echo 'products removed: ' . count( $ids ) . "\n";

foreach ( [ 'pa_soort', 'pa_smaak' ] as $tax ) {
	if ( taxonomy_exists( $tax ) ) {
		foreach ( (array) get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false ] ) as $t ) {
			if ( ! is_wp_error( $t ) ) { wp_delete_term( $t->term_id, $tax ); }
		}
	}
}
foreach ( (array) wc_get_attribute_taxonomies() as $a ) {
	if ( in_array( $a->attribute_name, [ 'soort', 'smaak' ], true ) ) { wc_delete_attribute( $a->attribute_id ); echo "attribute removed: {$a->attribute_name}\n"; }
}
$t = get_term_by( 'slug', 'traditionele', 'product_cat' );
if ( $t ) { wp_delete_term( $t->term_id, 'product_cat' ); echo "category removed: Traditionele\n"; }

delete_transient( 'wc_attribute_taxonomies' );
wc_delete_product_transients();
echo 'published products now: ' . (int) wp_count_posts( 'product' )->publish . "\n";
