<?php
/**
 * The policy and information pages.
 *
 * One element with five sets of words in front of it, so most of what is
 * worth checking is that the shared half behaves: what the client types comes
 * out as they typed it, a clause number moves into the margin and nothing
 * else does, the contents point at sections that exist, and a page that is
 * one answer does not grow a contents panel for it.
 */
require __DIR__ . '/wp-load.php';

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $file ) {
	require_once $file;
}

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

/**
 * @param string $class    Element class.
 * @param array  $settings Overrides.
 * @return string
 */
function page( $class, array $settings = [] ) {
	$el           = new $class( [ 'id' => 'pl' ] );
	$el->settings = $settings;

	ob_start();
	$el->render();

	return (string) ob_get_clean();
}

$pages = [
	'PFH_Element_Terms'      => 'Algemene Voorwaarden',
	'PFH_Element_Shipping'   => 'Betalen en',
	'PFH_Element_Returns'    => 'Retourbeleid',
	'PFH_Element_Complaints' => 'klacht',
	'PFH_Element_Privacy'    => 'Privacybeleid',
];

echo "── the five pages ──\n";

foreach ( $pages as $class => $needle ) {
	ok( "$class exists", class_exists( $class ) );
}

$html = [];

foreach ( $pages as $class => $needle ) {
	$html[ $class ] = page( $class );

	ok( "  $class draws its own title", false !== strpos( $html[ $class ], $needle ), substr( $html[ $class ], 0, 120 ) );
	// Bricks puts its own brxe- class in front of ours, so the root is found
	// by the pair of classes this element adds rather than by the attribute.
	ok( "  $class is one root", 1 === substr_count( $html[ $class ], 'pfh-policy pfh-scope' ) );
}

$terms = $html['PFH_Element_Terms'];

/*
 * Every one of them is the same element, so the settings the client changes
 * have to reach all five. The subclass supplies words and nothing else.
 */
ok( 'they are all the same element underneath', ( function () use ( $pages ) {
	foreach ( array_keys( $pages ) as $class ) {
		if ( ! is_subclass_of( $class, 'PFH_Element_Policy' ) ) {
			return false;
		}
	}

	return true;
} )() );

ok( 'each has its own name for the builder', 5 === count( array_unique( array_map(
	static function ( $class ) {
		$el = new $class( [ 'id' => 'x' ] );

		return $el->name;
	},
	array_keys( $pages )
) ) ) );

echo "\n── the clause numbers ──\n";

ok( 'a numbered clause is set in the margin', false !== strpos( $terms, '<span class="pfh-policy__clause-no">1.1</span>' ) );
ok( '  and the sentence beside it keeps its words', false !== strpos( $terms, 'Deze algemene voorwaarden zijn van toepassing op alle aanbiedingen, bestellingen en overeenkomsten via onze webshop.' ) );
ok( '  the number itself is not left in the sentence', false === strpos( $terms, '>1.1 Deze algemene' ) );
ok( 'every clause of the document is found', 35 === substr_count( $terms, 'pfh-policy__clause-no' ), substr_count( $terms, 'pfh-policy__clause-no' ) . ' found' );
ok( 'a two-digit clause is one too', false !== strpos( $terms, '>10.1</span>' ) );

$loose = page(
	'PFH_Element_Policy',
	[
		'sections' => [
			[ 'title' => 'Prijzen', 'text' => '<p>1,50 euro per pot, en niet meer.</p><p>2026 was een goed jaar.</p><p>3.2 Dit is wel een clausule.</p>' ],
			[ 'title' => 'Nog iets', 'text' => '<p>Gewone tekst.</p>' ],
		],
	]
);

ok( 'a sentence that merely opens with a number is left alone', 1 === substr_count( $loose, 'pfh-policy__clause-no' ), substr_count( $loose, 'pfh-policy__clause-no' ) . ' lifted' );
ok( '  the price stays where it was typed', false !== strpos( $loose, '<p>1,50 euro per pot, en niet meer.</p>' ) );
ok( '  and so does the year', false !== strpos( $loose, '<p>2026 was een goed jaar.</p>' ) );
ok( '  while the real clause moves', false !== strpos( $loose, '<span class="pfh-policy__clause-no">3.2</span>' ) );

ok( 'a page with no clauses gets none invented', false === strpos( $html['PFH_Element_Shipping'], 'pfh-policy__clause-no' ) );

echo "\n── the contents ──\n";

ok( 'a long page gets a contents panel', false !== strpos( $terms, 'pfh-policy__toc' ) );
ok( '  one line per section', 11 === substr_count( $terms, 'pfh-policy__toc-link' ), substr_count( $terms, 'pfh-policy__toc-link' ) . ' lines' );
ok( '  and the document is not the full width', false === strpos( $terms, 'pfh-policy__layout--wide' ) );

ok( 'every link points at a section that exists', ( function () use ( $terms ) {
	preg_match_all( '/href="#([^"]+)"/', $terms, $links );

	foreach ( $links[1] as $id ) {
		if ( false === strpos( $terms, 'id="' . $id . '"' ) ) {
			return false;
		}
	}

	return ! empty( $links[1] );
} )() );

$twins = page(
	'PFH_Element_Policy',
	[
		'sections' => [
			[ 'title' => 'Levering', 'text' => '<p>Een.</p>' ],
			[ 'title' => 'Levering', 'text' => '<p>Twee.</p>' ],
		],
	]
);

ok( 'two sections that read the same get different ids', false !== strpos( $twins, 'id="levering"' ) && false !== strpos( $twins, 'id="levering-2"' ) );

$long_title = 'Waarom wij bepaalde producten niet kunnen terugnemen en wat dat voor jou betekent';
$long       = page(
	'PFH_Element_Policy',
	[
		'sections' => [
			[ 'title' => $long_title, 'text' => '<p>Tekst.</p>' ],
			[ 'title' => 'Kort', 'text' => '<p>Tekst.</p>' ],
		],
	]
);

preg_match( '/<a class="pfh-policy__toc-link"[^>]*>(.*?)<\/a>/s', $long, $label );
$cut = isset( $label[1] ) ? html_entity_decode( wp_strip_all_tags( $label[1] ), ENT_QUOTES, 'UTF-8' ) : '';

ok( 'a heading too long for the panel is cut down', '' !== $cut && mb_strlen( $cut ) <= 60, $cut );
ok( '  with the whole heading kept in the link\'s title', false !== strpos( $long, 'title="' . esc_attr( $long_title ) . '"' ) );
ok( '  and the heading on the page left as written', false !== strpos( $long, '>' . $long_title . '</h2>' ) );

$one = page( 'PFH_Element_Policy', [ 'sections' => [ [ 'title' => 'Alleen dit', 'text' => '<p>Een antwoord.</p>' ] ] ] );

ok( 'one section is a page, not a contents list', false === strpos( $one, 'pfh-policy__toc' ) );
ok( '  and it takes the full width', false !== strpos( $one, 'pfh-policy__layout--wide' ) );
ok( 'the contents can be switched off entirely', false === strpos( page( 'PFH_Element_Terms', [ 'showToc' => false ] ), 'pfh-policy__toc' ) );

echo "\n── numbering ──\n";

ok( 'the terms are numbered', false !== strpos( $terms, '<span class="pfh-policy__no">1</span>' ) );
ok( '  and the contents say the same numbers', false !== strpos( $terms, '<span class="pfh-policy__toc-no">11.</span>' ) );
ok( 'a page that is not a contract is not numbered', false === strpos( $html['PFH_Element_Shipping'], 'pfh-policy__no"' ) );
ok( 'numbering can be turned on for any page', false !== strpos( page( 'PFH_Element_Shipping', [ 'numbered' => true ] ), 'pfh-policy__no' ) );
ok( '  and off again for the terms', false === strpos( page( 'PFH_Element_Terms', [ 'numbered' => false ] ), 'pfh-policy__no"' ) );

echo "\n── the chips ──\n";

ok( 'an e-mail address becomes a link you can mail', false !== strpos( $terms, 'href="mailto:klantenservice@productsforhome.nl"' ) );
ok( 'a phone number becomes a link you can dial', false !== strpos( $terms, 'href="tel:0617392302"' ) );
ok( 'anything else is left as words', false !== strpos( $terms, '>Alphen aan den Rijn</li>' ) );
ok( 'the payment methods are chips', 4 === substr_count( $html['PFH_Element_Shipping'], 'pfh-policy__chip">iDEAL' )
	+ substr_count( $html['PFH_Element_Shipping'], 'pfh-policy__chip">PayPal' )
	+ substr_count( $html['PFH_Element_Shipping'], 'pfh-policy__chip">Klarna' )
	+ substr_count( $html['PFH_Element_Shipping'], 'pfh-policy__chip">Bancontact' ) );

$dial = page(
	'PFH_Element_Policy',
	[
		'sections' => [
			[ 'title' => 'Bellen', 'chips' => "+31 (0)6 17 39 23 02\n" ],
			[ 'title' => 'Iets anders', 'text' => '<p>Tekst.</p>' ],
		],
	]
);

/*
 * The trunk zero in brackets is there for someone calling from inside the
 * country, and makes the international form undiallable.
 */
ok( 'an international number drops the trunk zero', false !== strpos( $dial, 'href="tel:+31617392302"' ), $dial );
ok( '  and is still shown the way it is written', false !== strpos( $dial, '>+31 (0)6 17 39 23 02</a>' ) );

echo "\n── the button ──\n";

$returns = $html['PFH_Element_Returns'];

ok( 'the returns page opens with the way to start one', false !== strpos( $returns, 'https://productsforhome.tracewise.nl/' ) );
ok( '  and that button says what it does', false !== strpos( $returns, '>Retour starten' ) );
ok( '  it opens beside the shop, not instead of it', (bool) preg_match( '#tracewise\.nl/"\s+target="_blank"#', $returns ) );

$inside = page(
	'PFH_Element_Policy',
	[
		'sections' => [
			[ 'title' => 'Hier', 'text' => '<p>Tekst.</p>', 'link' => 'Naar de winkel', 'url' => home_url( '/winkel/' ) ],
			[ 'title' => 'Daar', 'text' => '<p>Tekst.</p>' ],
		],
	]
);

ok( 'a link to this shop stays in the same tab', false === strpos( $inside, 'target="_blank"' ), $inside );

echo "\n── what the client types ──\n";

$typed = page(
	'PFH_Element_Policy',
	[
		'sections' => [
			[
				'title' => 'Van alles',
				'text'  => '<p>Een alinea.</p><ul><li>Een punt</li></ul><ol><li>Een stap</li></ol>'
					. '<blockquote><p>Let op.</p></blockquote>'
					. '<table><tr><td>cel</td></tr></table>'
					. '<p><a href="https://www.webwinkelkeur.nl">een link</a></p>'
					. '<script>alert(1)</script><p onclick="alert(2)">Klik.</p>',
			],
			[ 'title' => 'Nog iets', 'text' => '<p>Tekst.</p>' ],
		],
	]
);

ok( 'a list comes through', false !== strpos( $typed, '<li>Een punt</li>' ) );
ok( 'a numbered list comes through', false !== strpos( $typed, '<li>Een stap</li>' ) );
ok( 'a quote becomes a note', false !== strpos( $typed, '<blockquote>' ) );
ok( 'a link comes through', false !== strpos( $typed, 'href="https://www.webwinkelkeur.nl"' ) );
ok( 'a wide table is put in something that scrolls', false !== strpos( $typed, 'pfh-policy__scroller' ) );
ok( '  with the table still inside it', (bool) preg_match( '/pfh-policy__scroller"><table/', $typed ) );
ok( 'a script in the text is dropped', false === strpos( $typed, '<script>alert' ) );
ok( 'an inline handler is dropped', false === strpos( $typed, 'onclick' ) );

echo "\n── a section on its own ground ──\n";

ok( 'the customer service block is set apart', false !== strpos( $terms, 'pfh-policy__section--highlight' ) );
/*
 * By the opening tags only: "class=\"pfh-policy__section" is also the start
 * of __section-head and __section-title, and the last of those is never the
 * one being asked about.
 */
ok( '  and it is the last one', ( function () use ( $terms ) {
	preg_match_all( '/<section class="(pfh-policy__section[^"]*)"/', $terms, $found );

	return ! empty( $found[1] ) && false !== strpos( end( $found[1] ), '--highlight' );
} )() );

echo "\n── an empty one ──\n";

$blank = page( 'PFH_Element_Policy' );

ok( 'an empty info page draws nothing on the front end', '' === trim( $blank ), $blank );

echo "\n── every part can go ──\n";

foreach ( [
	'title'    => 'pfh-policy__title',
	'eyebrow'  => 'pfh-policy__eyebrow',
	'sections' => 'pfh-policy__section',
] as $control => $needle ) {
	ok( "  $control", false === strpos( page( 'PFH_Element_Terms', [ $control => '' ] ), $needle ) );
}

ok( 'the date line is off until it is filled in', false === strpos( $terms, 'pfh-policy__updated' ) );
ok( '  and appears once it is', false !== strpos( page( 'PFH_Element_Terms', [ 'updated' => 'Laatst bijgewerkt: 1 september 2026' ] ), 'Laatst bijgewerkt: 1 september 2026' ) );

echo "\n── the settings reach the page ──\n";

$styled = page( 'PFH_Element_Terms', [ 'accent' => [ 'hex' => '#804000' ], 'measure' => 640, 'textSize' => 18 ] );

ok( 'a colour reaches the page', false !== strpos( $styled, '--pfh-pol-accent:#804000' ) );
ok( 'the document width reaches the page', false !== strpos( $styled, '--pfh-pol-measure-set:640px' ) );
ok( 'the text size reaches the page', false !== strpos( $styled, '--pfh-pol-text-set:18px' ) );

/*
 * Written as --pfh-pol-measure rather than -measure-set, an inline value
 * would beat the stylesheet's own breakpoints and a phone would keep the
 * desktop measure.
 */
ok( 'and none of them beats a breakpoint', false === strpos( $styled, '--pfh-pol-measure:' ) && false === strpos( $styled, '--pfh-pol-text:' ) );

echo "\n$pass passed, $fail failed\n";
