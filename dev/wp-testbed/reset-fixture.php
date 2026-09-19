<?php
/**
 * Clear away anything the variable-product fixture left behind.
 *
 * It matches on the fixture's own names only. The first version of this script
 * matched 'gia-giamas-starterspakket%', which was the fixture's slug back when
 * it collided with a real demo product of the same name — so every run quietly
 * deleted that demo product, and the engine suite then failed on a catalogue
 * one product short. The fixture was renamed; this had not been.
 *
 * The rule now: this file may only ever match 'pfh-'. If a future fixture needs
 * clearing, give it a pfh- name rather than widening the match here.
 */
require __DIR__ . '/wp-load.php';
header( 'Content-Type: text/plain' );
global $wpdb;

$ids = $wpdb->get_col(
	"SELECT ID FROM {$wpdb->posts}
	 WHERE post_type IN ('product','product_variation')
	   AND (post_name LIKE 'pfh-fixture%' OR post_name LIKE 'pfh-orphan%' OR post_title LIKE 'PFH...Fixture%')"
);

foreach ( $ids as $id ) {
	foreach ( get_attached_media( '', $id ) as $a ) { wp_delete_post( $a->ID, true ); }

	$p = wc_get_product( $id );

	if ( $p && $p->is_type( 'variable' ) ) {
		foreach ( $p->get_children() as $c ) { wp_delete_post( $c, true ); }
	}

	wp_delete_post( $id, true );
}

echo 'products removed: ' . count( $ids ) . "\n";

/*
 * The attribute terms and the two attributes themselves: the demo catalogue
 * uses merk / kleur / gewicht, so soort and smaak belong to the fixture alone.
 */
foreach ( [ 'pa_soort', 'pa_smaak' ] as $tax ) {
	if ( taxonomy_exists( $tax ) ) {
		foreach ( (array) get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false ] ) as $t ) {
			if ( ! is_wp_error( $t ) ) { wp_delete_term( $t->term_id, $tax ); }
		}
	}
}

foreach ( (array) wc_get_attribute_taxonomies() as $a ) {
	if ( in_array( $a->attribute_name, [ 'soort', 'smaak' ], true ) ) {
		wc_delete_attribute( $a->attribute_id );
		echo "attribute removed: {$a->attribute_name}\n";
	}
}

/*
 * The fixture's own category, not the demo one. The demo catalogue's is
 * 'traditioneel'; the fixture makes 'traditionele', one letter apart, which is
 * exactly why this checks the slug rather than the name.
 */
$t = get_term_by( 'slug', 'traditionele', 'product_cat' );

if ( $t && 'traditioneel' !== $t->slug ) {
	wp_delete_term( $t->term_id, 'product_cat' );
	echo "category removed: {$t->slug}\n";
}

delete_transient( 'wc_attribute_taxonomies' );
wc_delete_product_transients();

$count = (int) wp_count_posts( 'product' )->publish;

echo 'published products now: ' . $count . "\n";
echo 32 === $count ? "the demo catalogue is intact\n" : "WARNING: the demo catalogue should hold 32 products\n";
