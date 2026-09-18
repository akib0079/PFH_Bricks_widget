<?php
/**
 * The Instagram strip, and the feed behind it.
 *
 * The feed is exercised through `pre_http_request`, which is where WordPress
 * itself hands a request off to the transport. Everything this plugin is
 * responsible for is therefore real: the URL that gets built, the response
 * handling, the JSON, the normalising, the caching and the two failure paths.
 * Only the socket is stubbed, and the socket is WordPress's job.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$IG = 'PFH_Widgets_Instagram';

/* ------------------------------------------------------------------ *
 * The stub transport
 * ------------------------------------------------------------------ */

$GLOBALS['ig_calls'] = [];
$GLOBALS['ig_reply'] = null;

add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) {
		if ( false === strpos( $url, 'instagram.com' ) ) {
			return $pre;
		}

		$GLOBALS['ig_calls'][] = $url;
		$reply = $GLOBALS['ig_reply'];

		if ( is_callable( $reply ) ) {
			return $reply( $url );
		}

		return $reply;
	},
	10,
	3
);

/** A 200 carrying this body. */
function ig_json( array $body ) {
	return [
		'headers'  => [],
		'body'     => wp_json_encode( $body ),
		'response' => [ 'code' => 200, 'message' => 'OK' ],
		'cookies'  => [],
		'filename' => null,
	];
}

/** Whatever the feed would answer with, plus a couple of awkward rows. */
function ig_payload() {
	return [
		'data' => [
			[
				'id'         => '1',
				'media_type' => 'IMAGE',
				'media_url'  => 'https://scontent.example.com/one.jpg',
				'permalink'  => 'https://www.instagram.com/p/AAA/',
				'caption'    => "Verse honing uit Samos \u{1F36F} #honing #griekenland",
			],
			[
				'id'            => '2',
				'media_type'    => 'VIDEO',
				'media_url'     => 'https://scontent.example.com/two.mp4',
				'thumbnail_url' => 'https://scontent.example.com/two-poster.jpg',
				'permalink'     => 'https://www.instagram.com/p/BBB/',
				'caption'       => 'Hoe je de doseerpomp gebruikt',
			],
			[
				'id'         => '3',
				'media_type' => 'CAROUSEL_ALBUM',
				'media_url'  => 'https://scontent.example.com/three.jpg',
				'permalink'  => 'https://www.instagram.com/p/CCC/',
				'caption'    => str_repeat( 'Een heel lang bijschrift dat niemand ooit helemaal voorleest. ', 4 ),
			],
			[
				// Nothing to draw: no media_url, no thumbnail.
				'id'         => '4',
				'media_type' => 'IMAGE',
				'permalink'  => 'https://www.instagram.com/p/DDD/',
			],
		],
	];
}

/** Render the element. */
function ig_draw( array $settings = [] ) {
	$el       = new PFH_Element_Instagram( [ 'id' => 'ig' ] );
	$el->name = 'pfh-instagram';
	$el->settings = $settings;

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

/** Two pictures set on the element, the way Bricks stores them. */
function ig_fallback() {
	return [
		[ 'image' => [ 'url' => 'https://example.com/bricks-one.jpg' ], 'title' => 'Eerste' ],
		[ 'image' => [ 'url' => 'https://example.com/bricks-two.jpg' ], 'title' => 'Tweede' ],
	];
}

/** Forget everything between cases, so no test leans on another. */
function ig_reset() {
	$GLOBALS['ig_calls'] = [];
	delete_option( 'pfh_instagram' );
	PFH_Widgets_Instagram::forget();
	PFH_Widgets_Instagram::flush();
}

ig_reset();

/* ------------------------------------------------------------------ *
 * What it draws
 * ------------------------------------------------------------------ */

echo "── the strip ──\n";
$html = ig_draw( [ 'images' => ig_fallback() ] );

ok( 'it is drawn', false !== strpos( $html, 'class="pfh-ig' ) );
ok( 'one tile for each picture', 2 === substr_count( $html, 'pfh-ig__tile' ) );
ok( 'the pictures are the ones set here', false !== strpos( $html, 'bricks-one.jpg' ) && false !== strpos( $html, 'bricks-two.jpg' ) );
ok( 'a description becomes the alt text', false !== strpos( $html, 'alt="Eerste"' ) );
ok( 'the title is drawn', false !== strpos( $html, 'Ontdekken' ) );
ok( 'with one word in the italic serif', false !== strpos( $html, '<em>Instagram</em>' ) );
ok( 'the account is drawn', false !== strpos( $html, 'products.for.home' ) );
ok( 'as a label, because no link was given', false !== strpos( $html, '<span class="pfh-ig__pill">' ) );
ok( 'both arrows are there', 1 === substr_count( $html, 'data-pfh-ig-step="-1"' ) && 1 === substr_count( $html, 'data-pfh-ig-step="1"' ) );
ok( 'and the pictures are not links', false === strpos( $html, 'pfh-ig__link' ) );

$linked = ig_draw( [ 'images' => ig_fallback(), 'profileUrl' => 'https://www.instagram.com/products.for.home/' ] );
ok( 'given a profile it becomes a link', false !== strpos( $linked, '<a class="pfh-ig__pill" href="https://www.instagram.com/products.for.home/"' ) );
ok( '  which opens away from the shop', false !== strpos( $linked, 'rel="noopener noreferrer"' ) );

ok( 'the arrows can be switched off', false === strpos( ig_draw( [ 'images' => ig_fallback(), 'showNav' => false ] ), 'pfh-ig__arrow' ) );

echo "\n── what the marquee is told ──\n";
$motion = ig_draw( [ 'images' => ig_fallback(), 'speed' => 25, 'direction' => 'right', 'pauseHover' => false ] );
ok( 'the speed is carried', false !== strpos( $motion, 'data-pfh-ig-speed="25"' ) );
ok( 'the direction too', false !== strpos( $motion, 'data-pfh-ig-direction="right"' ) );
ok( 'and whether it pauses', false !== strpos( $motion, 'data-pfh-ig-pause="0"' ) );
ok( 'a sane direction is the only thing that gets through', false !== strpos( ig_draw( [ 'images' => ig_fallback(), 'direction' => 'sideways<script>' ] ), 'data-pfh-ig-direction="left"' ) );
ok( 'one set is printed, not a copy of it', 1 === substr_count( $motion, 'data-pfh-ig-set' ) );

/* ------------------------------------------------------------------ *
 * The feed
 * ------------------------------------------------------------------ */

echo "\n── when Instagram answers ──\n";
ig_reset();
update_option( 'pfh_instagram', [ 'token' => 'test-token-123', 'ttl' => 6 ] );
PFH_Widgets_Instagram::forget();
$GLOBALS['ig_reply'] = ig_json( ig_payload() );

$posts = PFH_Widgets_Instagram::posts( [ 'limit' => 12 ] );
$url   = $GLOBALS['ig_calls'][0] ?? '';

ok( 'it asks graph.instagram.com for the account media', false !== strpos( $url, 'graph.instagram.com' ) && false !== strpos( $url, '/me/media' ) );
ok( '  with the token', false !== strpos( $url, 'access_token=test-token-123' ) );
ok( '  the fields it draws', false !== strpos( $url, 'media_url' ) && false !== strpos( $url, 'thumbnail_url' ) && false !== strpos( $url, 'permalink' ) );
ok( '  and the count asked for', false !== strpos( $url, 'limit=12' ) );

ok( 'the drawable posts come back', 3 === count( $posts ), count( $posts ) . ' posts' );
ok( 'a post with no picture at all is dropped', false === strpos( wp_json_encode( $posts ), 'DDD' ) );
ok( "a video uses its poster, not the file", ! empty( $posts[1]['url'] ) && false !== strpos( $posts[1]['url'], 'two-poster.jpg' ) );
ok( '  and never the mp4', false === strpos( wp_json_encode( $posts ), 'two.mp4' ) );
ok( 'the permalink is kept', ! empty( $posts[0]['permalink'] ) && false !== strpos( $posts[0]['permalink'], '/p/AAA/' ) );

echo "\n── captions become alt text ──\n";
ok( 'the caption is used', false !== strpos( $posts[0]['alt'], 'Verse honing uit Samos' ) );
ok( 'the hashtag tail is dropped', false === strpos( $posts[0]['alt'], '#honing' ) );
/*
 * Emoji are stripped on purpose. The feed is cached in the options table, and
 * on a shop still running utf8 rather than utf8mb4 a 4-byte character makes
 * the write fail outright — the transient is never stored and every page view
 * goes back to Instagram. This is the same failure that stopped Bricks pages
 * saving, one table over.
 */
ok( 'and so are emoji', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', $posts[0]['alt'] ), $posts[0]['alt'] );
ok( 'a long caption is cut', function_exists( 'mb_strlen' ) ? mb_strlen( $posts[2]['alt'] ) <= 120 : true );
ok( '  and says so', false !== strpos( $posts[2]['alt'], '...' ) );

echo "\n── the feed wins, and is asked once ──\n";
$GLOBALS['ig_calls'] = [];
$html = ig_draw( [ 'images' => ig_fallback() ] );

ok( 'the feed replaces the pictures set here', false !== strpos( $html, 'one.jpg' ) && false === strpos( $html, 'bricks-one.jpg' ) );
ok( 'one tile for each post', 3 === substr_count( $html, 'pfh-ig__tile' ) );
ok( 'the cached answer is reused, not refetched', 0 === count( $GLOBALS['ig_calls'] ), count( $GLOBALS['ig_calls'] ) . ' calls' );

$linked = ig_draw( [ 'images' => ig_fallback(), 'linkTiles' => true ] );
ok( 'switched on, each picture links to its post', false !== strpos( $linked, 'href="https://www.instagram.com/p/AAA/"' ) );
ok( '  and opens away from the shop', false !== strpos( $linked, 'class="pfh-ig__link" href' ) && false !== strpos( $linked, 'noopener' ) );

$off = ig_draw( [ 'images' => ig_fallback(), 'useFeed' => false ] );
ok( 'the feed can be switched off on the element', false !== strpos( $off, 'bricks-one.jpg' ) && false === strpos( $off, 'scontent.example.com' ) );

/* ------------------------------------------------------------------ *
 * Failure
 * ------------------------------------------------------------------ */

echo "\n── when Instagram does not answer ──\n";
ig_reset();
update_option( 'pfh_instagram', [ 'token' => 'revoked-token' ] );
PFH_Widgets_Instagram::forget();

$GLOBALS['ig_reply'] = [
	'headers'  => [],
	'body'     => wp_json_encode( [ 'error' => [ 'message' => 'Invalid OAuth access token' ] ] ),
	'response' => [ 'code' => 400, 'message' => 'Bad Request' ],
	'cookies'  => [],
	'filename' => null,
];

$html = ig_draw( [ 'images' => ig_fallback() ] );
ok( 'the shop falls back to the pictures in Bricks', false !== strpos( $html, 'bricks-one.jpg' ) );
ok( 'and says nothing about it on the page', false === stripos( $html, 'error' ) && false === stripos( $html, 'token' ) );

$GLOBALS['ig_calls'] = [];
ig_draw( [ 'images' => ig_fallback() ] );
ok( 'a failure is cached too, so the next view does not call again', 0 === count( $GLOBALS['ig_calls'] ), count( $GLOBALS['ig_calls'] ) . ' calls' );

ig_reset();
$GLOBALS['ig_reply'] = new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
update_option( 'pfh_instagram', [ 'token' => 'timeout-token' ] );
PFH_Widgets_Instagram::forget();
ok( 'a timeout is no different', false !== strpos( ig_draw( [ 'images' => ig_fallback() ] ), 'bricks-one.jpg' ) );

ig_reset();
$GLOBALS['ig_reply'] = ig_json( [ 'nonsense' => true ] );
update_option( 'pfh_instagram', [ 'token' => 'odd-token' ] );
PFH_Widgets_Instagram::forget();
ok( 'nor is an answer in a shape nobody expected', false !== strpos( ig_draw( [ 'images' => ig_fallback() ] ), 'bricks-one.jpg' ) );

echo "\n── with nothing at all to show ──\n";
ig_reset();
ok( 'the front end leaves the section off the page', '' === trim( ig_draw() ) );

/*
 * REST_REQUEST is one of the things is_builder_context() looks at, and the
 * only one this harness can turn on. It stays on for the rest of the file,
 * which changes nothing below: every case after this one has pictures.
 */
define( 'REST_REQUEST', true );
$builder = ig_draw();

ok( 'the builder says why instead of drawing nothing', false !== strpos( $builder, 'pfh-ig--empty' ) );
ok( '  and names where the token goes', false !== strpos( $builder, 'Products For Home' ) );

/* ------------------------------------------------------------------ *
 * The token
 * ------------------------------------------------------------------ */

echo "\n── the token ──\n";
ig_reset();
update_option( 'pfh_instagram', [ 'token' => 'stored-token' ] );
PFH_Widgets_Instagram::forget();

$creds = PFH_Widgets_Instagram::credentials();
ok( 'the stored one is used', 'stored-token' === $creds['token'] );
ok( 'and it is not locked', empty( $creds['locked'] ) );
ok( 'a shop with a token counts as connected', PFH_Widgets_Instagram::connected() );

// A constant cannot be defined twice, so this is the last word on the subject.
define( 'PFH_INSTAGRAM_TOKEN', 'wp-config-token' );
PFH_Widgets_Instagram::forget();

$creds = PFH_Widgets_Instagram::credentials();
ok( 'wp-config beats the database', 'wp-config-token' === $creds['token'] );
ok( 'and says so, so the screen can', ! empty( $creds['locked'] ) );

$GLOBALS['ig_calls'] = [];
$GLOBALS['ig_reply'] = ig_json( [ 'access_token' => 'refreshed-token', 'expires_in' => 5184000 ] );
PFH_Widgets_Instagram::keep_alive();
ok( 'a token in wp-config is never rewritten', 0 === count( $GLOBALS['ig_calls'] ), count( $GLOBALS['ig_calls'] ) . ' calls' );

/* ------------------------------------------------------------------ *
 * House rules
 * ------------------------------------------------------------------ */

echo "\n── it behaves like the rest of the plugin ──\n";
$el = new PFH_Element_Instagram( [ 'id' => 'c' ] );
$el->name = 'pfh-instagram';
$el->set_control_groups();
$el->set_controls();

ok( 'it encodes its controls for the builder', false !== wp_json_encode( $el->controls ) );
ok( 'and holds no 4-byte character', ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', wp_json_encode( $el->controls, JSON_UNESCAPED_UNICODE ) ) );

foreach ( $el->controls as $key => $control ) {
	if ( ! isset( $control['tab'] ) ) {
		ok( "  $key declares its tab", false );
	}
}

ok( 'every control declares its tab', true );

$panel = ( function () {
	$el = new PFH_Element_Instagram( [ 'id' => 'ig' ] );
	$el->name = 'pfh-instagram';
	$el->set_control_groups();
	$el->set_controls();
	$el->settings = [ 'images' => ig_fallback() ];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
} )();

ok( 'it renders the same without the controls built', $panel === ig_draw( [ 'images' => ig_fallback() ] ) );

/* ---- leave the shop as it was found ---- */
ig_reset();

echo "\n$pass passed, $fail failed\n";
