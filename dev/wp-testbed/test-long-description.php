<?php
/**
 * A category's long description: its own field, filled once from the old
 * XStore text, shown by the Collection Description element and by the
 * {pfh_long_description} tag — and nothing else about the category moves.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-shopdesc.php';

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

function render_desc( $slug, array $over = [] ) {
	$_SERVER['REQUEST_URI'] = wp_parse_url( get_term_link( $slug, 'product_cat' ), PHP_URL_PATH );
	query_posts( [ 'post_type' => 'product', 'product_cat' => $slug, 'posts_per_page' => 3 ] );

	$el = new PFH_Element_Shopdesc( [ 'id' => 'ld' . substr( md5( $slug . wp_json_encode( $over ) ), 0, 6 ) ] );
	$el->name = 'pfh-shopdesc';
	$el->set_control_groups();
	$el->set_controls();

	$s = [];
	foreach ( $el->controls as $k => $c ) { if ( array_key_exists( 'default', $c ) ) { $s[ $k ] = $c['default']; } }
	$el->settings = array_merge( $s, $over );

	ob_start(); $el->render(); $html = ob_get_clean();
	wp_reset_query();

	return $html;
}

function body_of( $h ) { preg_match( '#pfh-shopdesc__body[^>]*>(.*)</div>#s', $h, $m ); return $m[1] ?? ''; }

wp_set_current_user( 1 );

$terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 3 ] );
ok( 'three categories to work with', count( $terms ) >= 3 );
list( $a, $b, $c ) = $terms;

$meta = PFH_Widgets_Collection::LONG;
$old  = PFH_Widgets_Collection::LEGACY_LONG;

// A clean start, and the descriptions as they are, to prove they stay.
foreach ( $terms as $t ) {
	delete_term_meta( $t->term_id, $meta );
	delete_term_meta( $t->term_id, $old );
}
delete_option( PFH_Widgets_Collection::ADOPTED );
$desc_before = [];
foreach ( $terms as $t ) { $desc_before[ $t->term_id ] = get_term( $t->term_id, 'product_cat' )->description; }

echo "── the field is known to WordPress ──\n";
$registered = get_registered_meta_keys( 'term', 'product_cat' );
ok( 'registered for categories', isset( $registered[ $meta ] ) );
ok( 'offered to the REST API', ! empty( $registered[ $meta ]['show_in_rest'] ) );

echo "\n── the old XStore text is copied in once ──\n";
$legacy_a = "<h2>Kop van A</h2>\n<p>De lange tekst van A met <strong>vet</strong>, een <a href=\"https://example.com/x\">link</a> en een lijst:</p>\n<ul>\n<li>een</li>\n<li>twee</li>\n</ul>";
$legacy_b = '<p>Oude tekst van B.</p>';
update_term_meta( $a->term_id, $old, $legacy_a );
update_term_meta( $b->term_id, $old, $legacy_b );
update_term_meta( $b->term_id, $meta, '<p>Al ingevuld voor B.</p>' );

PFH_Widgets_Collection::adopt_legacy_long();

ok( 'A gets the old text, exactly', get_term_meta( $a->term_id, $meta, true ) === $legacy_a, get_term_meta( $a->term_id, $meta, true ) );
ok( 'B keeps what it already had', '<p>Al ingevuld voor B.</p>' === get_term_meta( $b->term_id, $meta, true ) );
ok( 'C, with no old text, stays empty', '' === get_term_meta( $c->term_id, $meta, true ) );
ok( 'the old XStore field is untouched', get_term_meta( $a->term_id, $old, true ) === $legacy_a );
$done = get_option( PFH_Widgets_Collection::ADOPTED );
ok( 'the run is recorded', is_array( $done ) && in_array( $a->term_id, $done['copied'], true ) && in_array( $b->term_id, $done['kept'], true ) );

update_term_meta( $a->term_id, $old, '<p>Later gewijzigd in XStore.</p>' );
PFH_Widgets_Collection::adopt_legacy_long();
ok( 'a second run changes nothing', get_term_meta( $a->term_id, $meta, true ) === $legacy_a );
update_term_meta( $a->term_id, $old, $legacy_a );

foreach ( $terms as $t ) {
	ok( "description of {$t->slug} untouched", get_term( $t->term_id, 'product_cat' )->description === $desc_before[ $t->term_id ] );
}

echo "\n── the element shows it ──\n";
$h = render_desc( $a->slug, [ 'source' => 'long' ] );
$body = body_of( $h );
ok( 'the long text is in the body', false !== strpos( $body, 'De lange tekst van A' ), substr( $body, 0, 120 ) );
ok( 'its heading is kept', false !== strpos( $body, '<h2>Kop van A</h2>' ) );
ok( 'its list is kept', false !== strpos( $body, '<li>twee</li>' ) );
ok( 'its link is kept', false !== strpos( $body, 'href="https://example.com/x"' ) );
ok( 'not the fallback copy', false === strpos( $body, 'zorgvuldig samengestelde' ) );

$h = render_desc( $c->slug, [ 'source' => 'long' ] );
ok( 'a category without one falls back to the section\'s copy', '' !== trim( wp_strip_all_tags( body_of( $h ) ) ) && false === strpos( body_of( $h ), 'De lange tekst' ) );

$h = render_desc( $c->slug, [ 'source' => 'long', 'body' => '<p>Eigen tekst in het blok.</p>' ] );
ok( 'or to the text typed in the section', false !== strpos( body_of( $h ), 'Eigen tekst in het blok' ) );

$h = render_desc( $a->slug, [ 'source' => 'auto' ] );
ok( 'the old "category description" choice still ignores it', false === strpos( body_of( $h ), 'De lange tekst van A' ) );

echo "\n── the dynamic data tag ──\n";
$_SERVER['REQUEST_URI'] = wp_parse_url( get_term_link( $a ), PHP_URL_PATH );
query_posts( [ 'post_type' => 'product', 'product_cat' => $a->slug, 'posts_per_page' => 1 ] );
$out = apply_filters( 'bricks/dynamic_data/render_content', 'Voor {pfh_long_description} na', null, 'text' );
ok( 'render_content fills the tag', false !== strpos( $out, 'De lange tekst van A' ) && 0 === strpos( $out, 'Voor ' ) );
$out = apply_filters( 'bricks/dynamic_data/render_tag', '{pfh_long_description}', null, 'text' );
ok( 'render_tag fills the tag', false !== strpos( $out, 'De lange tekst van A' ) );
$out = apply_filters( 'bricks/dynamic_data/render_tag', '{other_tag}', null, 'text' );
ok( 'other tags are left alone', '{other_tag}' === $out );
$list = apply_filters( 'bricks/dynamic_tags_list', [] );
ok( 'listed in the builder', in_array( '{pfh_long_description}', wp_list_pluck( $list, 'name' ), true ) );
wp_reset_query();
ok( 'outside a category it prints nothing', '' === apply_filters( 'bricks/dynamic_data/render_tag', '{pfh_long_description}', null, 'text' ) );

echo "\n── saving from the category screen ──\n";
$_POST = [
	'pfh_collection_nonce' => wp_create_nonce( PFH_Widgets_Collection::NONCE ),
	'pfh_long_description' => '<p>Nieuw geschreven.</p><script>alert(1)</script>',
];
PFH_Widgets_Collection::save( $c->term_id );
ok( 'saved, without the script', '<p>Nieuw geschreven.</p>alert(1)' === get_term_meta( $c->term_id, $meta, true ) || '<p>Nieuw geschreven.</p>' === get_term_meta( $c->term_id, $meta, true ), get_term_meta( $c->term_id, $meta, true ) );
ok( 'no script tag stored', false === strpos( get_term_meta( $c->term_id, $meta, true ), '<script' ) );

$_POST = [ 'pfh_collection_nonce' => wp_create_nonce( PFH_Widgets_Collection::NONCE ), 'pfh_long_description' => '' ];
PFH_Widgets_Collection::save( $c->term_id );
ok( 'emptied, the field is removed', ! metadata_exists( 'term', $c->term_id, $meta ) );

$_POST = [ 'pfh_collection_nonce' => wp_create_nonce( PFH_Widgets_Collection::NONCE ) ];
PFH_Widgets_Collection::save( $a->term_id );
ok( 'a form without the field leaves it alone', get_term_meta( $a->term_id, $meta, true ) === $legacy_a );

$_POST = [ 'pfh_collection_nonce' => 'wrong', 'pfh_long_description' => '<p>Mag niet.</p>' ];
PFH_Widgets_Collection::save( $a->term_id );
ok( 'a bad nonce changes nothing', get_term_meta( $a->term_id, $meta, true ) === $legacy_a );
$_POST = [];

echo "\n── the REST API ──\n";
$r = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/product_cat/' . $a->term_id ) );
$d = $r->get_data();
ok( 'readable', isset( $d['meta'][ $meta ] ) && $d['meta'][ $meta ] === $legacy_a );

$req = new WP_REST_Request( 'POST', '/wp/v2/product_cat/' . $c->term_id );
$req->set_body_params( [ 'meta' => [ $meta => '<p>Via REST.</p>' ] ] );
$r = rest_do_request( $req );
ok( 'writable by someone who manages categories', 200 === $r->get_status() && '<p>Via REST.</p>' === get_term_meta( $c->term_id, $meta, true ), $r->get_status() );

wp_set_current_user( 0 );
$req = new WP_REST_Request( 'POST', '/wp/v2/product_cat/' . $c->term_id );
$req->set_body_params( [ 'meta' => [ $meta => '<p>Anoniem.</p>' ] ] );
$r = rest_do_request( $req );
ok( 'not writable by a visitor', '<p>Via REST.</p>' === get_term_meta( $c->term_id, $meta, true ), $r->get_status() );
wp_set_current_user( 1 );

// Leave the testbed as it was found.
foreach ( $terms as $t ) {
	delete_term_meta( $t->term_id, $meta );
	delete_term_meta( $t->term_id, $old );
}
delete_option( PFH_Widgets_Collection::ADOPTED );

echo "\n$pass passed, $fail failed\n";
