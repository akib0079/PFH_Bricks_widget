<?php
/**
 * The WebwinkelKeur feed, read the way WebwinkelKeur actually sends it.
 *
 * The replies below have the live API's shape — checked against the real
 * endpoints — with made-up people in them: the ratings list gives each review
 * in stars (1 to 5, as integers) under `ratings`, and the summary scores the
 * shop out of ten under `data.rating_average`. Read as tenths, a five-star
 * review was a 5/10, so every "good reviews only" filter dropped them all and
 * the checkout fell back to its typed review.
 *
 * No request leaves the machine: pre_http_request answers for WebwinkelKeur.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function el( $class, array $settings = [] ) {
	$e           = new $class( [ 'id' => 'rf' . wp_rand( 1, 99999 ) ] );
	$e->settings = $settings;

	ob_start();
	$e->render();

	return (string) ob_get_clean();
}

$day = static function ( $ago ) {
	return wp_date( 'Y-m-d H:i:s', time() - $ago * DAY_IN_SECONDS );
};

$ratings = [
	'status'  => 'success',
	'message' => 'Ratings successfully retrieved',
	'total'   => '348',
	'ratings' => [
		[ 'id' => 1, 'name' => 'Anna de Vries', 'rating' => 5, 'comment' => 'Top!', 'date' => '', 'created' => $day( 1 ), 'country' => 'NL', 'quarantine' => false, 'ratings' => [ 'shippingtime' => 0 ] ],
		[ 'id' => 2, 'name' => 'Bram Peeters', 'rating' => 5, 'comment' => 'De olijfolie is heerlijk en alles kwam goed verpakt en snel aan.', 'created' => $day( 3 ), 'country' => 'BE', 'quarantine' => false ],
		[ 'id' => 3, 'name' => 'Carla Jansen', 'rating' => 4, 'comment' => 'Mooie honing, levering duurde alleen een dag langer dan gedacht.', 'created' => $day( 20 ), 'country' => 'NL', 'quarantine' => false ],
		[ 'id' => 4, 'name' => 'Dirk Smit', 'rating' => 3, 'comment' => 'Prima, maar de pot was een beetje plakkerig bij aankomst.', 'created' => $day( 40 ), 'country' => 'NL', 'quarantine' => false ],
		[ 'id' => 5, 'name' => 'Eva Bakker', 'rating' => 1, 'comment' => 'Dit bericht staat in quarantaine en is nog niet openbaar.', 'created' => $day( 2 ), 'country' => 'NL', 'quarantine' => true ],
		[ 'id' => 6, 'name' => 'Frank Visser', 'rating' => 5, 'comment' => '', 'created' => $day( 2 ), 'country' => 'NL', 'quarantine' => false ],
	],
];

$summary = [
	'status'  => 'success',
	'message' => 'Rating summary successfully retrieved',
	'data'    => [
		'amount'          => '396',
		'rating_average'  => '9.737438383838384',
		'ratings_average' => [ 'shippingtime' => '0' ],
	],
];

$calls = [];

add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) use ( $ratings, $summary, &$calls ) {
		if ( false === strpos( $url, 'dashboard.webwinkelkeur.nl' ) ) {
			return $pre;
		}

		$calls[] = $url;
		$body    = false !== strpos( $url, 'ratings_summary' ) ? $summary : $ratings;

		return [
			'headers'  => [],
			'body'     => wp_json_encode( $body ),
			'response' => [ 'code' => 200, 'message' => 'OK' ],
			'cookies'  => [],
		];
	},
	10,
	3
);

add_filter( 'pfh_webwinkelkeur_credentials', static function () { return [ 'id' => '1222432', 'code' => 'test-code' ]; }, 99 );

PFH_Widgets_Reviews::flush();

echo "── reading the list ──\n";

$rows = PFH_Widgets_Reviews::normalize( $ratings );
$by   = array_column( $rows, null, 'id' );

ok( 'a five-star review is a 10 out of 10', isset( $by['2'] ) && 10.0 === (float) $by['2']['rating10'], isset( $by['2'] ) ? var_export( $by['2']['rating10'], true ) : 'missing' );
ok( '  four stars is an 8, three a 6', 8.0 === (float) $by['3']['rating10'] && 6.0 === (float) $by['4']['rating10'] );
ok( '  and it shows as five full stars', 5.0 === PFH_Widgets_Reviews::stars( $by['2']['rating10'], 10, 5 ) );
ok( 'a review in quarantine is left out', ! isset( $by['5'] ) );
ok( 'the country comes through for the reviewer\'s line', 'BE' === $by['2']['country'] && 'België' === PFH_Widgets_Reviews::place( $by['2'] ) && 'Nederland' === PFH_Widgets_Reviews::place( $by['3'] ) );
ok( '  a town wins when there is one', 'Utrecht' === PFH_Widgets_Reviews::place( [ 'city' => 'Utrecht', 'country' => 'NL' ] ) );
ok( 'a list already out of ten is not doubled', 9.0 === (float) PFH_Widgets_Reviews::normalize( [ 'ratings' => [ [ 'rating' => 9, 'comment' => 'x' ], [ 'rating' => 4, 'comment' => 'y' ] ] ] )[0]['rating10'] );

add_filter( 'pfh_webwinkelkeur_rating_scale', static function () { return 10; } );
ok( 'the scale can be pinned by filter', 5.0 === (float) PFH_Widgets_Reviews::normalize( $ratings )[0]['rating10'] );
remove_all_filters( 'pfh_webwinkelkeur_rating_scale' );

echo "\n── the good ones, through the cache ──\n";

$good = PFH_Widgets_Reviews::get( [ 'min' => 8, 'text' => true ] );
$ids  = array_column( $good, 'id' );

ok( 'good reviews survive an 8-out-of-10 threshold', in_array( '2', $ids, true ) && in_array( '3', $ids, true ), implode( ',', $ids ) );
ok( '  a three-star one does not', ! in_array( '4', $ids, true ) );
ok( '  nor a rating without words', ! in_array( '6', $ids, true ) );

$before = count( $calls );
PFH_Widgets_Reviews::get( [ 'min' => 8 ] );
ok( 'a second look is served from the cache', count( $calls ) === $before );

echo "\n── the shop's score ──\n";

$sum = PFH_Widgets_Reviews::summary();

ok( 'the score is read from rating_average', 9.7 === $sum['rating'], var_export( $sum['rating'], true ) );
ok( '  and the count from amount', 396 === $sum['count'] );

$fig = PFH_Widgets_Reviews::figures( [ 'scale' => 10, 'score' => '1,0', 'count' => 1 ] );
ok( 'the badges show the live 9,7, not the typed score', $fig['live'] && '9.7' === str_replace( ',', '.', $fig['score'] ), $fig['score'] );

echo "\n── the checkout column ──\n";

$html = el( 'PFH_Element_Checkout_Reviews' );

ok( 'a real review is shown', false !== strpos( $html, 'De olijfolie is heerlijk' ) );
ok( '  not the typed one', false === strpos( $html, 'Jessica' ) );
ok( '  with a real name and country', false !== strpos( $html, '>Bram Peeters<' ) && false !== strpos( $html, 'België' ) );
ok( '  and when, in Dutch', false !== strpos( $html, '3 dagen geleden' ) );
ok( '  older ones read as weeks', false !== strpos( $html, '2 weken geleden' ) );
ok( '  its stars are all five', false !== strpos( $html, 'style="width:100%"' ) );
ok( 'a one-word review waits while there are better ones', false === strpos( el( 'PFH_Element_Checkout_Reviews', [ 'limit' => 2 ] ), 'Top!' ) );
ok( '  but fills in when there are not', false !== strpos( el( 'PFH_Element_Checkout_Reviews', [ 'limit' => 3 ] ), 'Top!' ) );
ok( '  and the length is the client\'s to change', false !== strpos( el( 'PFH_Element_Checkout_Reviews', [ 'limit' => 1, 'minLength' => 0 ] ), 'Top!' ) );
ok( 'the score line is the live one', false !== strpos( $html, '9,7/10' ) || false !== strpos( $html, '9.7/10' ) );

echo "\n── the homepage slider ──\n";

$slider = el( 'PFH_Element_Reviews', [ 'minRating' => 8 ] );

ok( 'it shows the real reviews', false !== strpos( $slider, 'De olijfolie is heerlijk' ) );
ok( '  at five stars, not two and a half', false !== strpos( $slider, 'var(--pfh-rv-star) * 5 +' ) && false === strpos( $slider, 'var(--pfh-rv-star) * 2.5' ) );
ok( '  with the country under the name', false !== strpos( $slider, 'België' ) );

echo "\n── after an update ──\n";

ok( 'the cache key carries the schema, so rows read the old way are not served', false !== strpos( file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/includes/class-pfh-reviews.php' ), "'|' . self::SCHEMA" ) );

PFH_Widgets_Reviews::flush();

echo "\n$pass passed, $fail failed\n";
