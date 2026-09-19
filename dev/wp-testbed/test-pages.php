<?php
/**
 * The about and contact pages.
 *
 * Both are content elements, so most of this is about what happens when a
 * field is left empty — which is the state every one of them is in until the
 * client fills it, and the state a page is most likely to be caught in.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function about( array $settings = [] ) {
	$el       = new PFH_Element_About( [ 'id' => 'ab' ] );
	$el->name = 'pfh-about';
	$el->settings = $settings;

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

function contact( array $settings = [] ) {
	$el       = new PFH_Element_Contact( [ 'id' => 'ct' ] );
	$el->name = 'pfh-contact';
	$el->settings = $settings;

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ *
 * About
 * ------------------------------------------------------------------ */

$page = about();

echo "── the about page ──\n";
ok( 'it is drawn', false !== strpos( $page, 'class="pfh-about' ) );
ok( 'the eyebrow, the title and the opening', false !== strpos( $page, 'pfh-about__eyebrow' ) && false !== strpos( $page, 'pfh-about__title' ) && false !== strpos( $page, 'pfh-about__lede' ) );
ok( 'with one word in the italic serif', false !== strpos( $page, '<em>voor winkel?</em>' ) );
ok( 'and the shop\'s own words', false !== strpos( $page, 'natuurproducten' ) );
ok( 'markup other than emphasis is stripped from the title', false === strpos( about( [ 'title' => 'Hallo<script>x</script>' ] ), '<script>' ) );

echo "\n── the sections ──\n";
ok( 'both ship written', 2 === substr_count( $page, '<li class="pfh-about__block' ), substr_count( $page, '<li class="pfh-about__block' ) . ' sections' );
ok( 'each with a heading', 2 === substr_count( $page, 'pfh-about__block-title' ) );
ok( 'and its paragraphs', false !== strpos( $page, 'Samos' ) && false !== strpos( $page, '2020' ) );

$with_image = about( [
	'blocks' => [
		[ 'title' => 'Eén', 'text' => '<p>Eerste</p>', 'image' => [ 'url' => 'https://example.com/one.jpg' ] ],
		[ 'title' => 'Twee', 'text' => '<p>Tweede</p>' ],
	],
] );

ok( 'a section with a picture draws it', false !== strpos( $with_image, 'one.jpg' ) && false !== strpos( $with_image, 'pfh-about__media' ) );
ok( 'a section without one is a column of text', false !== strpos( $with_image, 'pfh-about__block--text' ) );
ok( '  and has no empty figure', 1 === substr_count( $with_image, 'pfh-about__media' ) );
ok( 'a picture is described by its heading', false !== strpos( $with_image, 'alt="Eén"' ) );
ok( 'an empty row is skipped', 2 === substr_count( about( [ 'blocks' => [ [ 'title' => 'Eén' ], [ 'title' => '', 'text' => '' ], [ 'text' => '<p>Drie</p>' ] ] ] ), '<li class="pfh-about__block' ) );

echo "\n── the awards ──\n";
$awards = about( [
	'awards' => [
		[ 'image' => [ 'url' => 'https://example.com/gold.png' ], 'label' => 'Athena 2022' ],
		[ 'image' => [ 'url' => 'https://example.com/silver.png' ], 'label' => 'Amsterdam 2022', 'link' => 'https://example.com/award' ],
		[ 'label' => 'No badge at all' ],
	],
] );

ok( 'a badge for each award with a picture', 2 === substr_count( $awards, 'class="pfh-about__award"' ), substr_count( $awards, 'class="pfh-about__award"' ) . ' badges' );
ok( '  and one without a picture is skipped', false === strpos( $awards, 'No badge at all' ) );
ok( 'the caption is drawn', false !== strpos( $awards, 'Athena 2022' ) );
ok( 'a linked award opens away from the shop', false !== strpos( $awards, 'href="https://example.com/award"' ) && false !== strpos( $awards, 'noopener' ) );
ok( 'the band carries its heading and text', false !== strpos( $awards, 'pfh-about__awards-title' ) && false !== strpos( $awards, 'pfh-about__awards-lede' ) );
ok( 'the badge size is the client\'s', false !== strpos( about( [ 'awardSize' => 140 ] ), '--pfh-ab-award:140px' ) );

$no_awards = about( [ 'awardsTitle' => '', 'awardsLede' => '', 'awards' => [] ] );
ok( 'with no awards and nothing to say, the band is left off', false === strpos( $no_awards, 'pfh-about__awards' ) );
ok( '  but the rest of the page stays', false !== strpos( $no_awards, 'pfh-about__block' ) );

echo "\n── an empty about page ──\n";
$bare = [ 'title' => '', 'lede' => '', 'eyebrow' => '', 'blocks' => [], 'awards' => [], 'awardsTitle' => '', 'awardsLede' => '' ];
ok( 'nothing is drawn on the front end', '' === trim( about( $bare ) ) );

/* ------------------------------------------------------------------ *
 * Contact
 * ------------------------------------------------------------------ */

$page = contact();

echo "\n── the contact page ──\n";
ok( 'it is drawn', false !== strpos( $page, 'class="pfh-contact' ) );
ok( 'with the question in the italic serif', false !== strpos( $page, '<em>vragen?</em>' ) );
ok( 'and the line under it', false !== strpos( $page, 'werkdagen' ) );
ok( 'three ways to reach the shop', 3 === substr_count( $page, 'class="pfh-contact__way"' ) );

echo "\n── every detail is a link ──\n";
ok( 'the phone dials', false !== strpos( $page, 'href="tel:0617392302"' ) );
ok( 'the email opens a message', false !== strpos( $page, 'href="mailto:info@productsforhome.nl"' ) );
ok( 'the address opens maps', false !== strpos( $page, 'google.com/maps/search' ) );
ok( '  and away from the shop', false !== strpos( $page, 'rel="noopener noreferrer"' ) );
ok( 'the address keeps its lines', false !== strpos( $page, '<br />' ) || false !== strpos( $page, '<br>' ) );

$messy = contact( [ 'phone' => '+31 (0)6 17 39 23 02' ] );
/*
 * The bracketed nought in "+31 (0)6" is an instruction, not a digit: it is the
 * one you leave out when you dial the country code. Keeping it gives a number
 * that reads correctly and rings nowhere.
 */
ok( 'a written-out number still dials', false !== strpos( $messy, 'href="tel:+31617392302"' ), 'got ' . ( preg_match( '/href="(tel:[^"]*)"/', $messy, $m ) ? $m[1] : 'nothing' ) );
ok( '  and a plain Dutch number keeps its nought', false !== strpos( contact( [ 'phone' => '06 17 39 23 02' ] ), 'href="tel:0617392302"' ) );

ok( 'something that is not an email is left out', false === strpos( contact( [ 'email' => 'not an address' ] ), 'mailto:' ) );

echo "\n── anything empty is left out ──\n";
ok( 'no phone, no phone row', 2 === substr_count( contact( [ 'phone' => '' ] ), 'class="pfh-contact__way"' ) );
ok( 'no email, no email row', false === strpos( contact( [ 'email' => '' ] ), 'mailto:' ) );
ok( 'no address, no address row', 2 === substr_count( contact( [ 'address' => '' ] ), 'class="pfh-contact__way"' ) );
ok( 'the notes under them can go too', false === strpos( contact( [ 'phoneNote' => '', 'emailNote' => '' ] ), 'pfh-contact__way-note' ) );

echo "\n── the registration numbers ──\n";
ok( 'both are shown', false !== strpos( $page, '78571936' ) && false !== strpos( $page, 'NL003349641B31' ) );
ok( 'with neither, the line goes', false === strpos( contact( [ 'kvk' => '', 'vat' => '' ] ), 'pfh-contact__legal' ) );

echo "\n── the map ──\n";
ok( 'it follows the address', false !== strpos( $page, 'google.com/maps?q=' ) && false !== strpos( $page, 'Nikkelweg' ) );
ok( '  in an iframe that says what it is', false !== strpos( $page, '<iframe' ) && false !== strpos( $page, 'title="Waar je ons vindt"' ) );
ok( '  and loads late', false !== strpos( $page, 'loading="lazy"' ) );
ok( 'a place of its own wins', false !== strpos( contact( [ 'mapQuery' => 'Samos, Griekenland' ] ), 'Samos' ) );
ok( 'a picture wins over the map', false !== strpos( contact( [ 'mapImage' => [ 'url' => 'https://example.com/shop.jpg' ] ] ), 'shop.jpg' ) );
ok( '  and loads nothing from Google', false === strpos( contact( [ 'mapImage' => [ 'url' => 'https://example.com/shop.jpg' ] ] ), 'google.com/maps?q=' ) );

$mapless = contact( [ 'showMap' => false ] );
ok( 'switched off there is no map', false === strpos( $mapless, '<iframe' ) );
ok( '  and the details take the width', false !== strpos( $mapless, 'pfh-contact__card--plain' ) );

add_filter( 'pfh_contact_map', static function () { return '<p id="own-map">Mijn eigen kaart</p>'; } );
ok( 'a shop can supply its own map', false !== strpos( contact(), 'own-map' ) );
remove_all_filters( 'pfh_contact_map' );

echo "\n── an empty contact page ──\n";
ok( 'nothing is drawn on the front end', '' === trim( contact( [ 'title' => '', 'lede' => '', 'eyebrow' => '', 'phone' => '', 'email' => '', 'address' => '' ] ) ) );

/* ------------------------------------------------------------------ *
 * House rules
 * ------------------------------------------------------------------ */

echo "\n── they behave like the rest of the plugin ──\n";

foreach ( [ 'PFH_Element_About' => 'pfh-about', 'PFH_Element_Contact' => 'pfh-contact' ] as $class => $name ) {
	$el = new $class( [ 'id' => 'c' ] );
	$el->name = $name;
	$el->set_control_groups();
	$el->set_controls();

	$short   = str_replace( 'PFH_Element_', '', $class );
	$missing = [];

	foreach ( $el->controls as $key => $control ) {
		if ( ! isset( $control['tab'] ) ) { $missing[] = $key; }
	}

	ok( "$short encodes its controls for the builder", false !== wp_json_encode( $el->controls ) );
	ok( "  and holds no 4-byte character", ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', wp_json_encode( $el->controls, JSON_UNESCAPED_UNICODE ) ) );
	ok( "  and every control declares its tab", ! $missing, implode( ', ', $missing ) );
}

$panel = ( function () {
	$el = new PFH_Element_About( [ 'id' => 'ab' ] );
	$el->name = 'pfh-about';
	$el->set_control_groups();
	$el->set_controls();
	$el->settings = [];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
} )();

ok( 'About renders the same without the controls built', $panel === about() );

$panel = ( function () {
	$el = new PFH_Element_Contact( [ 'id' => 'ct' ] );
	$el->name = 'pfh-contact';
	$el->set_control_groups();
	$el->set_controls();
	$el->settings = [];

	ob_start();
	$el->render();

	return (string) ob_get_clean();
} )();

ok( 'Contact renders the same without the controls built', $panel === contact() );

echo "\n$pass passed, $fail failed\n";
