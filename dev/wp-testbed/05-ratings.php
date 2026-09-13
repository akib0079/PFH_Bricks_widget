<?php
require __DIR__ . '/wp-load.php';

$ids = get_posts( [ 'post_type' => 'product', 'numberposts' => -1, 'fields' => 'ids' ] );
$n = 0;

foreach ( $ids as $i => $id ) {
	// A spread of honest-looking ratings, plus a couple with none at all so
	// the no-reviews fallback gets exercised too.
	$plan = [
		[ 4.5, 12 ], [ 5.0, 3 ], [ 4.0, 27 ], [ 3.5, 8 ],
		[ 4.8, 41 ], [ 0, 0 ], [ 4.2, 15 ], [ 2.5, 2 ],
	];
	list( $score, $count ) = $plan[ $i % count( $plan ) ];

	update_post_meta( $id, '_wc_average_rating', $score );
	update_post_meta( $id, '_wc_review_count', $count );
	update_post_meta( $id, '_wc_rating_count', $count ? [ (string) max( 1, (int) round( $score ) ) => $count ] : [] );
	wp_update_post( [ 'ID' => $id, 'comment_count' => $count ] );
	$n++;
}

echo "rated $n products\n";
