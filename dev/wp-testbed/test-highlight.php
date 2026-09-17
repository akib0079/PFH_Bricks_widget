<?php
/**
 * The product highlight, rebuilt static.
 *
 * What it has to guarantee, in order of how badly it went wrong before:
 *
 *   it can never stop a page saving — nothing it stores holds a 4-byte
 *   character, which a utf8 postmeta table refuses along with the whole page;
 *   a block that was added is a block that shows, whatever Bricks did or did
 *   not build first;
 *   it is static: typed in, printed out, no lookups;
 *   and it still looks exactly as designed.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-highlight.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

/**
 * Render the way a live page does: settings as stored, controls never built.
 */
function highlight( array $settings = [], $build = false ) {
	$el       = new PFH_Element_Highlight( [ 'id' => 'hl' ] );
	$el->name = 'pfh-highlight';

	if ( $build ) {
		$el->set_control_groups();
		$el->set_controls();
	}

	$el->settings = $settings;

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

function controls() {
	$el       = new PFH_Element_Highlight( [ 'id' => 'c' ] );
	$el->name = 'pfh-highlight';
	$el->set_control_groups();
	$el->set_controls();

	return $el;
}

$four_byte = '/[\x{10000}-\x{10FFFF}]/u';
$src       = file_get_contents( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-highlight.php' );

echo "── nothing it stores can stop a page saving ──\n";
ok( 'no default holds a 4-byte character', ! preg_match( $four_byte, wp_json_encode( PFH_Element_Highlight::DEFAULTS, JSON_UNESCAPED_UNICODE ) ) );
ok( 'no control default holds one either', ! preg_match( $four_byte, wp_json_encode( controls()->controls, JSON_UNESCAPED_UNICODE ) ) );
ok( 'nor does the source file anywhere', ! preg_match( $four_byte, $src ) );
ok( 'the controls encode to JSON for the builder', false !== wp_json_encode( controls()->controls ) );

$html = highlight();
ok( 'the gift is printed as an entity', false !== strpos( $html, '<span class="pfh-hl__eyebrow-icon" aria-hidden="true">&#x1F381;</span>' ) );
ok( 'so the rendered page carries no raw emoji', ! preg_match( $four_byte, $html ) );
ok( 'and it can be switched off', false === strpos( highlight( [ 'showGift' => false ] ), 'pfh-hl__eyebrow-icon' ) );
ok( 'the old emoji setting is ignored, not printed', ! preg_match( $four_byte, highlight( [ 'eyebrowIcon' => "\u{1F381}" ] ) ) );

echo "\n── a block that was added is a block that shows ──\n";
ok( 'no settings at all renders the full banner', false !== strpos( $html, 'pfh-hl__card' ) && false !== strpos( $html, 'Proefpakket' ) );
ok( 'identical whether or not Bricks built the controls', highlight( [], true ) === highlight() );

$cleared = highlight( [ 'eyebrow' => '', 'titleTop' => '', 'titleBottom' => '', 'text' => '', 'points' => [], 'price' => '', 'priceWas' => '', 'saving' => '' ] );
ok( 'every word cleared still leaves the card and photo', false !== strpos( $cleared, 'pfh-hl__card' ) && false !== strpos( $cleared, 'pfh-hl__img' ) );

$promised = [];
foreach ( controls()->controls as $key => $control ) {
	if ( array_key_exists( 'default', $control ) ) { $promised[ $key ] = $control['default']; }
}
ok( 'what the panel shows is what the page renders', highlight( $promised ) === highlight() );

$drift = [];
foreach ( controls()->controls as $key => $control ) {
	if ( ! array_key_exists( 'default', $control ) ) { continue; }
	$want = PFH_Element_Highlight::DEFAULTS[ $key ];
	$have = 'color' === $control['type'] ? $control['default']['hex'] : $control['default'];
	if ( $want !== $have ) { $drift[] = $key; }
}
ok( 'every control default comes from DEFAULTS', ! $drift, implode( ', ', $drift ) );

echo "\n── static: typed in, printed out ──\n";
$q = get_num_queries();
highlight( [ 'url' => 'https://example.com/', 'btnLabel' => 'Bekijk' ] );
ok( 'rendering costs no queries', 0 === get_num_queries() - $q, ( get_num_queries() - $q ) . ' queries' );

foreach ( [ 'wc_get_product', 'wc_price', 'bricks_render_dynamic_data', '::dd(', 'get_transient', '$wpdb' ] as $needle ) {
	ok( "does not call $needle", false === strpos( $src, $needle ) );
}

ok( 'a dynamic tag typed in is printed as typed', false !== strpos( highlight( [ 'titleTop' => '{post_title}' ] ), '{post_title}' ) );
ok( 'no field offers the dynamic-data picker', ! array_filter( controls()->controls, static function ( $c ) {
	return in_array( $c['type'], [ 'text', 'textarea', 'image' ], true ) && ( $c['hasDynamicData'] ?? true );
} ) );

echo "\n── cleared stays cleared, typed wins ──\n";
ok( 'a cleared eyebrow does not come back', false === strpos( highlight( [ 'eyebrow' => '' ] ), 'pfh-hl__eyebrow' ) );
ok( 'a typed title replaces the default', false !== strpos( highlight( [ 'titleTop' => 'Zomerpakket' ] ), 'Zomerpakket' ) );
ok( 'untouched fields keep theirs meanwhile', false !== strpos( highlight( [ 'titleTop' => 'Zomerpakket' ] ), '3 smaken naar keuze' ) );
ok( 'typed markup is escaped', false === strpos( highlight( [ 'text' => '<script>x</script>' ] ), '<script>' ) );

$points = highlight( [ 'points' => [ [ 'text' => 'Een' ], [ 'text' => '' ], [ 'text' => 'Twee' ] ] ] );
ok( 'selling points print their own rows', 2 === substr_count( $points, 'pfh-hl__point"' ) );
ok( 'with the tick icon on each', 2 === substr_count( $points, 'pfh-hl__tick' ) );

echo "\n── the price is three typed lines ──\n";
ok( 'price and old price print', false !== strpos( $html, '<span class="pfh-hl__price-now">€ 41,97</span>' ) && false !== strpos( $html, '<span class="pfh-hl__price-was">€ 46,97</span>' ) );
ok( 'with the saving line under them', false !== strpos( $html, '<p class="pfh-hl__save">Bespaar €5,00 — 11% korting</p>' ) );
ok( 'no old price means no strikethrough', false === strpos( highlight( [ 'priceWas' => '' ] ), 'pfh-hl__price-was' ) );
ok( 'no price at all drops the block', false === strpos( highlight( [ 'price' => '', 'priceWas' => '', 'saving' => '' ] ), 'pfh-hl__price' ) );

echo "\n── the link ──\n";
ok( 'no link means nothing is clickable', false === strpos( $html, 'is-clickable' ) && false === strpos( $html, '<a ' ) );

$linked = highlight( [ 'url' => 'https://productsforhome.nl/proefpakket/', 'btnLabel' => 'Bekijk' ] );
ok( 'a link makes the whole card clickable', false !== strpos( $linked, 'pfh-hl__card is-clickable' ) );
ok( 'through a single link on the title', 1 === substr_count( $linked, '<a ' ) && false !== strpos( $linked, '<a class="pfh-hl__link" href="https://productsforhome.nl/proefpakket/"' ) );
ok( 'so the button is not a second link inside it', false !== strpos( $linked, '<span class="pfh-hl__btn">Bekijk</span>' ) );

$button = highlight( [ 'url' => 'https://productsforhome.nl/', 'btnLabel' => 'Bekijk', 'clickable' => false ] );
ok( 'with the card not clickable, the button is the link', false !== strpos( $button, '<a class="pfh-hl__btn" href="https://productsforhome.nl/"' ) );

$tab = highlight( [ 'url' => 'https://productsforhome.nl/', 'newTab' => true ] );
ok( 'a new tab opens safely', false !== strpos( $tab, 'target="_blank"' ) && false !== strpos( $tab, 'rel="noopener"' ) );
ok( 'a javascript: link is refused', false === strpos( highlight( [ 'url' => 'javascript:alert(1)' ] ), 'javascript' ) );

echo "\n── the photograph ──\n";
ok( 'the supplied photo until one is chosen', false !== strpos( $html, 'pfh-highlight.jpg' ) );

$att = wp_insert_attachment( [ 'post_title' => 'Banner', 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit' ], 'probe/banner.jpg' );
update_post_meta( $att, '_wp_attached_file', 'probe/banner.jpg' );
update_post_meta( $att, '_wp_attachment_image_alt', 'Proefpakket met drie flessen' );

$picked = highlight( [ 'image' => [ 'id' => $att, 'size' => 'full' ] ] );
ok( 'a media-library pick resolves by its ID', false !== strpos( $picked, 'probe/banner.jpg' ) );
ok( 'and brings its description along', false !== strpos( $picked, 'alt="Proefpakket met drie flessen"' ) );
ok( 'a typed description wins over it', false !== strpos( highlight( [ 'image' => [ 'id' => $att ], 'imageAlt' => 'Eigen tekst' ] ), 'alt="Eigen tekst"' ) );
ok( 'a dynamic image value is ignored', false !== strpos( highlight( [ 'image' => [ 'useDynamicData' => '{featured_image}', 'url' => 'https://x.test/dyn.jpg' ] ] ), 'pfh-highlight.jpg' ) );
wp_delete_attachment( $att, true );

echo "\n── numbers: 0 is a value, empty is the design ──\n";
$zero = highlight( [ 'radius' => '0', 'imageRadius' => 0, 'btnRadius' => '0' ] );
ok( 'a square card', false !== strpos( $zero, '--pfh-hl-radius:0px' ) );
ok( 'a square image', false !== strpos( $zero, '--pfh-hl-img-radius:0px' ) );
ok( 'a square button', false !== strpos( $zero, '--pfh-hl-btn-radius:0px' ) );
ok( 'an empty box falls back to the drawn 20px', false !== strpos( highlight( [ 'radius' => '' ] ), '--pfh-hl-radius:20px' ) );
ok( 'the button forces no radius until given one', false === strpos( $html, '--pfh-hl-btn-radius' ) );
ok( 'a pill button', false !== strpos( highlight( [ 'btnRadius' => 24 ] ), '--pfh-hl-btn-radius:24px' ) );
ok( 'overlap 0 turns the overlap off', false === strpos( highlight( [ 'overlap' => 0 ] ), 'pfh-hl--overlaps' ) );
ok( 'the image share is held to 20–70%', false !== strpos( highlight( [ 'imageWidth' => 95 ] ), '--pfh-hl-media-w:70%' ) );

echo "\n── colours ──\n";
$paint = highlight( [ 'bg' => [ 'hex' => '#112233' ], 'btnBg' => [ 'hex' => '#445566' ], 'btnColor' => [ 'rgb' => 'rgb(1, 2, 3)' ] ] );
ok( 'the card background', false !== strpos( $paint, '--pfh-hl-bg:#112233' ) );
ok( 'the button background', false !== strpos( $paint, '--pfh-hl-btn-bg:#445566' ) );
ok( 'the button text, in any format Bricks stores', false !== strpos( $paint, '--pfh-hl-btn-ink:rgb(1, 2, 3)' ) );
ok( 'untouched button colours stay with the stylesheet', false === strpos( $html, '--pfh-hl-btn-bg' ) );

echo "\n── it still looks exactly as designed ──\n";
foreach ( [ 'pfh-hl ', 'pfh-hl--media-right', 'pfh-hl--overlaps', 'pfh-hl--blend', 'pfh-hl__inner', 'pfh-hl__body', 'pfh-hl__eyebrow', 'pfh-hl__title-top', 'pfh-hl__title-bottom', 'pfh-hl__text', 'pfh-hl__points', 'pfh-hl__price-row', 'pfh-hl__media', 'pfh-hl__img' ] as $class ) {
	ok( "class $class", false !== strpos( $html, $class ) );
}
foreach ( [ '--pfh-hl-max:1240px', '--pfh-hl-min-h:468px', '--pfh-hl-overlap:74px', '--pfh-hl-pb:56px', '--pfh-hl-radius:20px', '--pfh-hl-px-set:52px', '--pfh-hl-py-set:48px', '--pfh-hl-eyebrow-set:22px', '--pfh-hl-title-set:32px', '--pfh-hl-text:14px', '--pfh-hl-price:24px', '--pfh-hl-bg:#d9e6dc', '--pfh-hl-ink:#22301c' ] as $var ) {
	ok( "Figma value $var", false !== strpos( $html, $var ) );
}
ok( 'the photo can sit on the left', false !== strpos( highlight( [ 'imageSide' => 'left' ] ), 'pfh-hl--media-left' ) );
ok( 'an unknown side falls back to the right', false !== strpos( highlight( [ 'imageSide' => 'top' ] ), 'pfh-hl--media-right' ) );
ok( 'the title tag can step down', 0 === strpos( trim( substr( highlight( [ 'titleTag' => 'h3' ] ), strpos( highlight( [ 'titleTag' => 'h3' ] ), '<h3' ) ) ), '<h3 class="pfh-hl__title"' ) );

echo "\n$pass passed, $fail failed\n";
