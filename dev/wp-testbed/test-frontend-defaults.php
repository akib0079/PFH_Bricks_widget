<?php
/**
 * An element dropped in and left alone must render on the front end.
 *
 * Two Bricks behaviours meet here, and together they were deleting whole
 * sections from live pages:
 *
 *   Bricks does not store a value that still equals its default. A section the
 *   editor drops in and does not touch is saved with *no settings at all*.
 *
 *   Bricks builds an element's control list when it needs the panel. On the
 *   front end it does not, so `$this->controls` is empty there.
 *
 * An element that reads its defaults off its controls therefore had nothing to
 * render from — and four of them rendered nothing at all, while looking
 * perfectly correct in the builder. That is precisely how it was reported:
 * "when I add that widget it doesn't show up".
 *
 * The old highlight suite could not have caught it: its harness built the
 * settings *out of* the control defaults, which is the one shape a real page
 * never produces.
 */
require __DIR__ . '/wp-load.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $f ) { require_once $f; }

$classes = array_values( array_filter( get_declared_classes(), static function ( $c ) {
	return 0 === strpos( $c, 'PFH_Element_' );
} ) );

/**
 * Render one element the way the front end does, or the way the panel does.
 */
function paint( $class, $with_controls, array $settings = [], $id = 'fdprobe' ) {
	// A fixed id on purpose: it is printed into the markup, so a random one
	// would make every comparison below differ for the wrong reason.
	$el       = new $class( [ 'id' => $id ] );
	$el->name = 'probe';

	if ( $with_controls ) {
		$el->set_control_groups();
		$el->set_controls();
	}

	$el->settings = $settings;

	ob_start();
	try {
		$el->render();
	} catch ( \Throwable $e ) {
		while ( ob_get_level() > 0 ) { ob_end_clean(); }

		return '__THREW__ ' . $e->getMessage();
	}

	return normalise( trim( (string) ob_get_clean() ) );
}

/**
 * Blank out the one id that is derived from the settings themselves.
 *
 * The deferred slider names its stored settings after the element id, and
 * falls back to a hash of the settings when there is no id — which is the case
 * under the test harness, so the two renders below differ for a reason that
 * has nothing to do with defaults.
 */
function normalise( $html ) {
	$html = preg_replace( '/data-pfh-recent="[^"]*"/', 'data-pfh-recent=""', $html );

	/*
	 * WordPress marks the first large image of a REQUEST with
	 * fetchpriority="high", once. Painting the same element twice to compare
	 * them therefore gives the first one an attribute the second cannot have.
	 * It is an artefact of measuring, not of defaults — and the attribute is
	 * wanted, since the article's picture is the thing the page is waiting on.
	 */
	return str_replace( ' fetchpriority="high"', '', $html );
}

echo "── the front end renders what the builder renders ──\n";
echo "   (no settings, because Bricks saved none — every value was a default)\n\n";

foreach ( $classes as $class ) {
	$short = str_replace( 'PFH_Element_', '', $class );
	$panel = paint( $class, true );
	$front = paint( $class, false );

	ok(
		sprintf( '%-16s', $short ) . ' renders the same either way',
		$panel === $front,
		'panel ' . strlen( $panel ) . ' bytes, front end ' . strlen( $front )
	);
}

/*
 * The four that were broken, named, so a regression says which one went. All
 * four take their content from the trait that reads control defaults.
 */
echo "\n── the panel's promise and the live page must agree ──\n";
/*
 * Every control declares a default, and that is what the editor is shown in
 * the panel. A live page sends none of them. So rendering with the defaults
 * stamped in and rendering with nothing at all have to come out the same — if
 * they do not, some call site is falling back to a different value than the
 * one the panel is advertising, and the page quietly disagrees with the
 * editor. The shop header's title did exactly that: the control said "Gia
 * giamas" and the render said "".
 */
foreach ( $classes as $class ) {
	$short = str_replace( 'PFH_Element_', '', $class );

	$probe = new $class( [ 'id' => 'x' ] );
	$probe->name = 'probe';
	$probe->set_control_groups();
	$probe->set_controls();

	$stamped = [];
	foreach ( $probe->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) && '' !== $c['default'] && null !== $c['default'] ) {
			$stamped[ $k ] = $c['default'];
		}
	}

	$promised = paint( $class, false, $stamped );
	$live     = paint( $class, false );

	ok(
		sprintf( '%-16s', $short ) . ' renders its declared defaults',
		$promised === $live,
		'panel default ' . strlen( $promised ) . ' bytes, live page ' . strlen( $live )
	);
}

echo "\n── the four that were empty on live pages ──\n";

$expect = [
	'PFH_Element_Highlight' => [ 'Meest gekozen', 'Proefpakket', 'pfh-hl__card' ],
	'PFH_Element_Notice'    => [ 'pfh-notice' ],
	'PFH_Element_Faq'       => [ 'pfh-faq' ],
	'PFH_Element_Counter'   => [ 'pfh-counter' ],
];

foreach ( $expect as $class => $needles ) {
	$html  = paint( $class, false );
	$short = str_replace( 'PFH_Element_', '', $class );

	ok( "$short renders without the panel having run", strlen( $html ) > 200, strlen( $html ) . ' bytes' );

	foreach ( $needles as $needle ) {
		ok( "  $short still says \"$needle\"", false !== strpos( $html, $needle ) );
	}
}

echo "\n── reaching for a default must not cost a query, or recurse ──\n";

$before = get_num_queries();
foreach ( $classes as $class ) { paint( $class, false ); }
$cost = get_num_queries() - $before;

ok( 'rendering all ' . count( $classes ) . ' front-end elements stays query-light', $cost <= 40, "$cost queries" );

$html = paint( 'PFH_Element_Highlight', false );
ok( 'building controls on demand did not recurse', false === strpos( $html, '__THREW__' ) && '' !== $html );

echo "\n── a field the editor cleared stays cleared ──\n";
/*
 * The fix must not turn "I deleted this eyebrow" back into the default. An
 * absent key means untouched; a key that is present and empty was cleared.
 */
$html = paint( 'PFH_Element_Highlight', false, [ 'eyebrow' => '' ] );
ok( 'a cleared eyebrow does not come back', false === strpos( $html, 'Meest gekozen' ) );
ok( 'but the rest of the banner still renders', false !== strpos( $html, 'pfh-hl__card' ) );

$html = paint( 'PFH_Element_Highlight', false, [ 'titleTop' => 'Eigen titel' ] );
ok( 'a typed value wins over the default', false !== strpos( $html, 'Eigen titel' ) );
ok( 'and untouched fields still fall back', false !== strpos( $html, 'Meest gekozen' ) );

echo "\n$pass passed, $fail failed\n";
