<?php
/**
 * The notice, counter and FAQ must pick up a corrected design too.
 *
 * Same problem the shop card had: Bricks stored a value for every control
 * when the page was built, so a changed default never arrives. The client saw
 * it as "the notice radius still doesn't match" and "the FAQ width isn't what
 * I asked for" after both had been changed in the code.
 */
require __DIR__ . '/wp-load.php';

foreach ( [ 'notice', 'counter', 'faq' ] as $f ) {
	require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-' . $f . '.php';
}

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function render_el( $class, $name, array $saved ) {
	$el       = new $class( [ 'id' => 'rev' . wp_rand( 1, 99999 ) ] );
	$el->name = $name;
	$el->set_control_groups();
	$el->set_controls();

	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; }
	}

	// A page saved under an older release: every control has a value.
	$s = array_merge( $s, $saved );

	$el->settings = $s;

	ob_start();
	$el->render();

	return ob_get_clean();
}

echo "── the notice picks up the corrected radius ──\n";
$html = render_el( 'PFH_Element_Notice', 'pfh-notice', [ 'maxWidth' => 1140 ] );
ok( 'radius is the 14px the design asks for', false !== strpos( $html, '--pfh-nt-radius:14px' ) );
ok( 'container is 1240 again', false !== strpos( $html, '--pfh-nt-max:1240px' ) );

/*
 * btnBg has no control default, so a stored value can only be one somebody
 * picked — it is theirs and stays. With nothing stored the stylesheet's own
 * colour applies, which is the corrected one.
 */
$chosen = render_el( 'PFH_Element_Notice', 'pfh-notice', [ 'btnBg' => [ 'hex' => '#7fa8a4' ] ] );
ok( 'a chosen button colour is kept', false !== strpos( $chosen, '--pfh-nt-btn-bg:#7fa8a4' ) );
ok( 'no accent bar comes back', false === strpos( $html, 'pfh-notice__rule' ) );

echo "\n── the counter picks up the corrected type ──\n";
$html = render_el( 'PFH_Element_Counter', 'pfh-counter', [ 'valueSize' => 32, 'labelSize' => 14, 'subSize' => 11, 'maxWidth' => 1140 ] );
ok( 'figures are 36px again', false !== strpos( $html, '--pfh-ct-value:36px' ) );
ok( 'labels are 16px again', false !== strpos( $html, '--pfh-ct-label:16px' ) );
ok( 'small line is 12px again', false !== strpos( $html, '--pfh-ct-sub:12px' ) );

echo "\n── the FAQ picks up the width that was asked for ──\n";
$html = render_el( 'PFH_Element_Faq', 'pfh-faq', [ 'listWidth' => 620, 'rowRadius' => 8, 'rowGap' => 10, 'titleSize' => 28 ] );
ok( 'list is 940 wide', false !== strpos( $html, '--pfh-fq-list:940px' ), 'stale width survived' );
ok( 'rows are 12px radius again', false !== strpos( $html, '--pfh-fq-row-r:12px' ) );
ok( 'gap is 12 again', false !== strpos( $html, '--pfh-fq-row-gap:12px' ) );
ok( 'heading is 32 again', false !== strpos( $html, '--pfh-fq-title:32px' ) );

echo "\n── a value the editor actually chose is kept ──\n";
/*
 * Nothing is stamped now. A setting yields only while it still holds the
 * default it used to have — anything else was chosen, and is left alone. This
 * is what makes an uploaded icon or a hand-set size survive, which the
 * stamped version did not.
 */
$html = render_el( 'PFH_Element_Faq', 'pfh-faq', [ 'listWidth' => 700 ] );
ok( 'the editor keeps their own width', false !== strpos( $html, '--pfh-fq-list:700px' ) );

$html = render_el( 'PFH_Element_Notice', 'pfh-notice', [ 'radius' => 20 ] );
ok( 'the editor keeps their own radius', false !== strpos( $html, '--pfh-nt-radius:20px' ) );

$html = render_el( 'PFH_Element_Counter', 'pfh-counter', [ 'valueSize' => 42 ] );
ok( 'the editor keeps their own figure size', false !== strpos( $html, '--pfh-ct-value:42px' ) );

echo "\n── the copy is never touched by a revision ──\n";
$html = render_el( 'PFH_Element_Notice', 'pfh-notice', [ 'title' => 'Eigen titel', 'btnLabel' => 'Eigen knop' ] );
ok( 'a typed title survives', false !== strpos( $html, 'Eigen titel' ) );
ok( 'a typed button label survives', false !== strpos( $html, 'Eigen knop' ) );

echo "\n$pass passed, $fail failed\n";
