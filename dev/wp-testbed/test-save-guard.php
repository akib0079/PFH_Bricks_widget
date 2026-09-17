<?php
/**
 * One emoji must not be able to stop a Bricks page saving.
 *
 * Where postmeta is utf8 rather than utf8mb4, WordPress core refuses to write
 * any value holding a 4-byte character — and Bricks keeps a whole page in one
 * value, so one emoji in one field stops every later save of that page while
 * the builder carries on showing the edits. The product highlight's gift icon
 * default did exactly that.
 *
 * The testbed is SQLite, whose driver hardcodes utf8mb4 and ignores the charset
 * filter, so the live $wpdb is re-homed into a subclass reporting utf8 for
 * postmeta — same connection, same data. Everything from update_post_meta()
 * down is then WordPress core's own code, which is the point: this proves the
 * failure on the code that actually runs, not on a model of it.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-highlight.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

class PFH_Test_Utf8_DB extends WP_SQLite_DB {
	public $pretend = 'utf8';
	public function get_col_charset( $table, $column ) {
		return ( $table === $this->postmeta && 'meta_value' === $column ) ? $this->pretend : parent::get_col_charset( $table, $column );
	}
}

function charset_db( $real, $charset ) {
	$copy = ( new ReflectionClass( 'PFH_Test_Utf8_DB' ) )->newInstanceWithoutConstructor();
	for ( $c = new ReflectionClass( $real ); $c; $c = $c->getParentClass() ) {
		foreach ( $c->getProperties() as $p ) {
			if ( $p->isStatic() ) { continue; }
			$p->setAccessible( true );
			if ( $p->isInitialized( $real ) ) { $p->setValue( $copy, $p->getValue( $real ) ); }
		}
	}
	foreach ( get_object_vars( $real ) as $k => $v ) { $copy->$k = $v; }
	$copy->pretend = $charset;

	return $copy;
}

function save( $post, array $tree, $key = '_bricks_page_content_2' ) {
	global $wpdb;
	wp_cache_delete( $post, 'post_meta' );
	$wpdb->last_error = '';
	$ok = update_post_meta( $post, $key, $tree );
	// Read it now: the query get_post_meta() runs next clears it.
	$error = $wpdb->last_error;
	wp_cache_delete( $post, 'post_meta' );

	return [ $ok, get_post_meta( $post, $key, true ), $error ];
}

$gift    = "\u{1F381}";
$real    = $GLOBALS['wpdb'];
$page    = wp_insert_post( [ 'post_type' => 'bricks_template', 'post_title' => 'Guard probe', 'post_status' => 'publish' ] );
$heading = [ 'id' => 'h1', 'name' => 'heading', 'settings' => [ 'text' => 'Veelgestelde Vragen' ] ];
$faq     = [ 'id' => 'f1', 'name' => 'pfh-faq', 'settings' => [ 'title' => 'Vragen' ] ];
$old_hl  = [ 'id' => 'hl', 'name' => 'pfh-highlight', 'settings' => [ 'eyebrow' => 'Meest gekozen', 'eyebrowIcon' => $gift, 'text' => 'Bespaar €5,00 — nu.' ] ];

$GLOBALS['wpdb'] = charset_db( $real, 'utf8' );

echo "── without the guard: the failure, on core's own code ──\n";
remove_filter( 'sanitize_post_meta__bricks_page_content_2', [ 'PFH_Widgets_Save_Guard', 'guard' ] );

[ $ok ] = save( $page, [ $heading ] );
ok( 'a page with no emoji saves', $ok );

[ $ok, $stored, $err ] = save( $page, [ $heading, $old_hl ] );
ok( 'adding the old highlight is refused', ! $ok, $err );
ok( 'with the database saying why', false !== strpos( $err, 'meta_value' ) );
ok( 'and the page left on its last save', is_array( $stored ) && 1 === count( $stored ) );

[ $ok, $stored ] = save( $page, [ $heading, $old_hl, $faq ] );
ok( 'after which adding any other element fails too', ! $ok && 1 === count( (array) $stored ) );

echo "\n── with the guard ──\n";
add_filter( 'sanitize_post_meta__bricks_page_content_2', [ 'PFH_Widgets_Save_Guard', 'guard' ] );

ok( 'it knows this column needs it', PFH_Widgets_Save_Guard::needed() );

[ $ok, $stored, $err ] = save( $page, [ $heading, $old_hl, $faq ] );
ok( 'the same page now saves', $ok, $err );
ok( 'every element with it', is_array( $stored ) && [ 'heading', 'pfh-highlight', 'pfh-faq' ] === wp_list_pluck( $stored, 'name' ) );
ok( 'the emoji stored as an entity', '&#x1F381;' === ( $stored[1]['settings']['eyebrowIcon'] ?? '' ) );
ok( 'which is the same character on the page', $gift === html_entity_decode( $stored[1]['settings']['eyebrowIcon'], ENT_QUOTES, 'UTF-8' ) );
ok( 'while € and — are left exactly as typed', 'Bespaar €5,00 — nu.' === ( $stored[1]['settings']['text'] ?? '' ) );

foreach ( [ '_bricks_page_header_2', '_bricks_page_footer_2' ] as $key ) {
	[ $ok, $stored ] = save( $page, [ [ 'id' => 'x', 'name' => 'heading', 'settings' => [ 'text' => "Hallo $gift" ] ] ], $key );
	ok( "headers and footers too ($key)", $ok && '&#x1F381;' === substr( $stored[0]['settings']['text'], -9 ) );
}

[ $ok, , $err ] = save( $page, [ 'x' => "\u{1F44B}" ], 'some_other_meta' );
ok( 'meta that is not Bricks is not its business', ! $ok, 'expected core to refuse it untouched' );

echo "\n── the rebuilt highlight never needs it ──\n";
remove_filter( 'sanitize_post_meta__bricks_page_content_2', [ 'PFH_Widgets_Save_Guard', 'guard' ] );

$el = new PFH_Element_Highlight( [ 'id' => 'n' ] );
$el->name = 'pfh-highlight';
$el->set_control_groups();
$el->set_controls();
$stamped = [];
foreach ( $el->controls as $k => $c ) {
	if ( array_key_exists( 'default', $c ) ) { $stamped[ $k ] = $c['default']; }
}

[ $ok, , $err ] = save( $page, [ $heading, [ 'id' => 'hl', 'name' => 'pfh-highlight', 'settings' => $stamped ], $faq ] );
ok( 'every default copied in, as Bricks does on insert, saves unguarded', $ok, $err );

add_filter( 'sanitize_post_meta__bricks_page_content_2', [ 'PFH_Widgets_Save_Guard', 'guard' ] );

echo "\n── on a modern database it does nothing ──\n";
$GLOBALS['wpdb'] = charset_db( $real, 'utf8mb4' );
ok( 'it knows it is not needed', ! PFH_Widgets_Save_Guard::needed() );
[ $ok, $stored ] = save( $page, [ $old_hl ] );
ok( 'the emoji is stored as the character itself', $ok && $gift === ( $stored[0]['settings']['eyebrowIcon'] ?? '' ) );

$GLOBALS['wpdb'] = $real;

echo "\n── encoding ──\n";
ok( 'nested values are reached', [ 'a' => [ 'b' => [ 'c' => '&#x1F44B;' ] ] ] === PFH_Widgets_Save_Guard::encode( [ 'a' => [ 'b' => [ 'c' => "\u{1F44B}" ] ] ] ) );
ok( 'several in one string', 'x&#x1F381;y&#x1F44B;z' === PFH_Widgets_Save_Guard::encode( "x{$gift}y\u{1F44B}z" ) );
ok( 'non-emoji 4-byte characters too', '&#x1D49C;' === PFH_Widgets_Save_Guard::encode( "\u{1D49C}" ) );
ok( 'numbers, booleans and null pass through', [ 1, true, null, 2.5 ] === PFH_Widgets_Save_Guard::encode( [ 1, true, null, 2.5 ] ) );
ok( 'broken UTF-8 is returned, not lost', "\xF0\x28\x8C\x28" === PFH_Widgets_Save_Guard::encode( "\xF0\x28\x8C\x28" ) );

echo "\n── Diagnose says it in words ──\n";
$GLOBALS['wpdb'] = charset_db( $real, 'utf8' );
$report = PFH_Widgets_Diagnose::report();
$GLOBALS['wpdb'] = $real;
ok( 'the charset is reported', false !== strpos( $report, 'postmeta charset: utf8' ) );
ok( 'with what it means', false !== strpos( $report, 'cannot store emoji' ) );
ok( 'and nothing alarming on a modern database', false === strpos( PFH_Widgets_Diagnose::report(), 'cannot store emoji' ) );

wp_delete_post( $page, true );
echo "\n$pass passed, $fail failed\n";
