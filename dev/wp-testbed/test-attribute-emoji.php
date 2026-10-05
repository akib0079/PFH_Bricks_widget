<?php
/**
 * Emoji typed into attribute values: "🍎 Appel & Granaatappel".
 *
 * They must save on any database, keep new slugs readable, leave existing
 * slugs alone, and split cleanly into emoji and words for the product page.
 * Every emoji here is written as a PHP escape so this file stays ASCII.
 */
require __DIR__ . '/wp-load.php';

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

$apple = "\u{1F34E}";
$peach = "\u{1F351}";
$tax   = 'pa_pfhemojitest';

register_taxonomy( $tax, 'product', [ 'hierarchical' => false, 'show_ui' => false ] );
register_taxonomy( 'pfh_not_an_attribute', 'product', [ 'hierarchical' => false ] );

echo "── hooked ──\n";
ok( 'on new values', false !== has_filter( 'pre_insert_term', [ 'PFH_Widgets_Attribute_Emoji', 'encode_name' ] ) && false !== has_filter( 'wp_insert_term_data', [ 'PFH_Widgets_Attribute_Emoji', 'clean_slug' ] ) );
ok( 'on renamed values', false !== has_filter( 'wp_update_term_data', [ 'PFH_Widgets_Attribute_Emoji', 'encode_update' ] ) );

echo "\n── a new value ──\n";

$made = wp_insert_term( "$apple Appel & Granaatappel", $tax );
ok( 'saves without an error', ! is_wp_error( $made ), is_wp_error( $made ) ? $made->get_error_message() : '' );
$term = is_wp_error( $made ) ? null : get_term( $made['term_id'], $tax );
ok( '  its slug leaves the emoji out', $term && 'appel-granaatappel' === $term->slug, $term ? $term->slug : '' );
ok( '  its name keeps it', $term && 0 === strpos( html_entity_decode( $term->name ), $apple ) );

$only = wp_insert_term( "$peach", $tax );
ok( 'a value that is only an emoji still saves', ! is_wp_error( $only ) );

$given = wp_insert_term( "$peach Perzik", $tax, [ 'slug' => 'mijn-eigen-slug' ] );
ok( 'a slug typed in by hand is kept as typed', ! is_wp_error( $given ) && 'mijn-eigen-slug' === get_term( $given['term_id'], $tax )->slug );

$plain = wp_insert_term( 'Citroen', $tax );
ok( 'a value without emoji is untouched', ! is_wp_error( $plain ) && 'citroen' === get_term( $plain['term_id'], $tax )->slug && 'Citroen' === get_term( $plain['term_id'], $tax )->name );

echo "\n── renaming ──\n";

$renamed = wp_update_term( $plain['term_id'], $tax, [ 'name' => "\u{1F34B} Citroen" ] );
ok( 'adding an emoji to an existing value saves', ! is_wp_error( $renamed ) );
ok( '  and its slug does not move', 'citroen' === get_term( $plain['term_id'], $tax )->slug );

echo "\n── only attributes ──\n";

$other = wp_insert_term( "$apple Appel", 'pfh_not_an_attribute' );
ok( 'other taxonomies are left to WordPress', ! is_wp_error( $other ) && 'appel' !== get_term( $other['term_id'], 'pfh_not_an_attribute' )->slug );

echo "\n── on a database that holds three bytes ──\n";

add_filter( 'pfh_widgets_encode_attribute_emoji', '__return_true' );
$stored = PFH_Widgets_Attribute_Emoji::storable( "$apple Appel" );
ok( 'the emoji becomes a character reference', '&#x1f34e; Appel' === $stored, $stored );
ok( '  which the page draws as the emoji again', "$apple Appel" === html_entity_decode( $stored ) );
ok( '  and esc_html() leaves intact', '&#x1f34e; Appel' === esc_html( $stored ) );
ok( 'a name without emoji is not rewritten', 'Kers' === PFH_Widgets_Attribute_Emoji::storable( 'Kers' ) );
$enc = wp_insert_term( "$peach Perzik", $tax );
ok( 'saving it that way still gets a clean slug', ! is_wp_error( $enc ) && 'perzik' === get_term( $enc['term_id'], $tax )->slug, is_wp_error( $enc ) ? '' : get_term( $enc['term_id'], $tax )->slug );
remove_filter( 'pfh_widgets_encode_attribute_emoji', '__return_true' );

echo "\n── emoji and words apart ──\n";

$s = [ PFH_Widgets_Attribute_Emoji::class, 'split' ];
ok( 'a leading emoji', [ $apple, 'Appel & Granaatappel' ] === $s( "$apple Appel & Granaatappel" ) );
ok( 'with no space after it', [ $apple, 'Appel' ] === $s( "{$apple}Appel" ) );
ok( 'as a stored reference', [ $apple, 'Appel' ] === $s( '&#x1f34e; Appel' ) );
ok( 'two of them', [ "\u{1F34B}\u{1F34A}", 'Citrus Twist' ] === $s( "\u{1F34B}\u{1F34A} Citrus Twist" ) );
ok( 'one with a variation selector (red heart)', [ "\u{2764}\u{FE0F}", 'Liefde' ] === $s( "\u{2764}\u{FE0F} Liefde" ) );
ok( 'none', [ '', 'Kers' ] === $s( 'Kers' ) );
ok( 'an emoji only after the words is left in place', [ '', "Kers $apple" ] === $s( "Kers $apple" ) );
ok( 'an emoji alone is kept whole, so there is something to read', [ '', $apple ] === $s( $apple ) );
ok( 'an escaped ampersand is read as one', [ $apple, 'Appel & Granaatappel' ] === $s( "$apple Appel &amp; Granaatappel" ) );

echo "\n── sorted by the words, not the emoji ──\n";

$sorted = 'pa_pfhemojisort';
register_taxonomy( $sorted, 'product', [ 'hierarchical' => false, 'show_ui' => false ] );
// Orange sorts before lemon before apple by code point; the words say otherwise.
foreach ( [ "\u{1F34A} Mandarijn", "\u{1F34B} Citroen", "\u{1F34E} Appel", 'Kers' ] as $n ) {
	wp_insert_term( $n, $sorted );
}
$words = static function ( $terms ) {
	return array_map( static function ( $t ) { return PFH_Widgets_Attribute_Emoji::split( is_object( $t ) ? $t->name : $t )[1]; }, array_values( (array) $terms ) );
};
ok( 'a list by name follows the words', [ 'Appel', 'Citroen', 'Kers', 'Mandarijn' ] === $words( get_terms( [ 'taxonomy' => $sorted, 'hide_empty' => false, 'orderby' => 'name' ] ) ), implode( ',', $words( get_terms( [ 'taxonomy' => $sorted, 'hide_empty' => false, 'orderby' => 'name' ] ) ) ) );
ok( '  and backwards when asked', [ 'Mandarijn', 'Kers', 'Citroen', 'Appel' ] === $words( get_terms( [ 'taxonomy' => $sorted, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'DESC' ] ) ) );
$by_id = wp_list_pluck( get_terms( [ 'taxonomy' => $sorted, 'hide_empty' => false, 'orderby' => 'term_id' ] ), 'term_id' );
ok( 'any other order is left alone', $by_id === array_values( array_map( 'intval', $by_id ) ) && $by_id == array_values( array_filter( $by_id ) ) && $by_id === array_values( ( static function ( $a ) { sort( $a ); return $a; } )( $by_id ) ) );
ok( 'lists of names or slugs are not touched', 4 === count( get_terms( [ 'taxonomy' => $sorted, 'hide_empty' => false, 'fields' => 'names' ] ) ) );
ok( 'WooCommerce\'s product lookup by name follows the words too', [ 'Appel', 'Citroen', 'Mandarijn' ] === $words( PFH_Widgets_Attribute_Emoji::sort_product_terms( [ "\u{1F34A} Mandarijn", "\u{1F34E} Appel", "\u{1F34B} Citroen" ], 0, $sorted, [ 'orderby' => 'name', 'fields' => 'names' ] ) ) );
$slugs = PFH_Widgets_Attribute_Emoji::sort_product_terms( [ 'mandarijn', 'appel', 'citroen' ], 0, $sorted, [ 'orderby' => 'name', 'fields' => 'slugs' ] );
ok( '  also when it asks for slugs', [ 'appel', 'citroen', 'mandarijn' ] === $slugs, implode( ',', $slugs ) );
ok( '  and not when the shop set its own order', [ 'mandarijn', 'appel' ] === PFH_Widgets_Attribute_Emoji::sort_product_terms( [ 'mandarijn', 'appel' ], 0, $sorted, [ 'orderby' => 'menu_order', 'fields' => 'slugs' ] ) );
ok( 'without any emoji the database order stands', [ 'b', 'a' ] === PFH_Widgets_Attribute_Emoji::sort_product_terms( [ 'b', 'a' ], 0, $sorted, [ 'orderby' => 'name', 'fields' => 'names' ] ) );
foreach ( (array) get_terms( [ 'taxonomy' => $sorted, 'hide_empty' => false ] ) as $x ) {
	wp_delete_term( $x->term_id, $sorted );
}

echo "\n── a custom attribute borrows from its global namesake ──\n";

$attr_id = wc_create_attribute( [ 'name' => 'Proefsmaak', 'slug' => 'proefsmaak', 'type' => 'select', 'order_by' => 'name' ] );
$gtax    = 'pa_proefsmaak';
register_taxonomy( $gtax, 'product', [ 'hierarchical' => false, 'show_ui' => false ] );
delete_transient( 'wc_attribute_taxonomies' );
if ( class_exists( 'WC_Cache_Helper' ) ) { WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' ); }
foreach ( [ "\u{1F34E} Appel & Granaatappel", "\u{1F34B} Citroen", "\u{1F353} Aardbei & Citroen", 'Kers' ] as $n ) {
	wp_insert_term( $n, $gtax );
}

$b = [ PFH_Widgets_Attribute_Emoji::class, 'borrowed' ];
ok( 'same words, other punctuation and case', "\u{1F34E}" === $b( 'Proefsmaak', 'Appel / granaatappel' ) );
ok( 'the attribute found by its label in any case', "\u{1F34B}" === $b( 'proefsmaak', 'citroen' ) );
ok( 'a longer name takes the value it starts with', "\u{1F34B}" === $b( 'Proefsmaak', 'Citroen 2.0' ) );
ok( '  the longest one', "\u{1F353}" === $b( 'Proefsmaak', 'Aardbei / citroen 2.0' ) );
ok( 'a global value without emoji lends nothing', '' === $b( 'Proefsmaak', 'Kers' ) );
ok( 'an unknown value gets nothing', '' === $b( 'Proefsmaak', 'Onbekend' ) );
ok( 'an attribute with no global namesake gets nothing', '' === $b( 'Iets anders', 'Appel / granaatappel' ) );
ok( 'a global attribute never borrows (it has its own)', '' === $b( $gtax, 'Appel / granaatappel' ) );
add_filter( 'pfh_widgets_borrow_attribute_emoji', '__return_false' );
ok( 'it can be switched off', '' === $b( 'Proefsmaak', 'Citroen' ) );
remove_filter( 'pfh_widgets_borrow_attribute_emoji', '__return_false' );

// A real product with the attribute typed into it, drawn by the element.
foreach ( glob( WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/*.php' ) as $ef ) { require_once $ef; }
$local = new WC_Product_Attribute();
$local->set_name( 'Proefsmaak' );
$local->set_options( [ 'Appel / granaatappel', 'Citroen 2.0', 'Onbekend' ] );
$local->set_visible( true );
$local->set_variation( true );
$vp = new WC_Product_Variable();
$vp->set_name( 'PFH emoji borrow test' );
$vp->set_slug( 'pfh-fixture-emoji-borrow' );
$vp->set_status( 'publish' );
$vp->set_attributes( [ $local ] );
$vp_id = $vp->save();
foreach ( [ 'Appel / granaatappel', 'Citroen 2.0', 'Onbekend' ] as $v ) {
	$var = new WC_Product_Variation();
	$var->set_parent_id( $vp_id );
	$var->set_attributes( [ 'proefsmaak' => $v ] );
	$var->set_regular_price( '10' );
	$var->save();
}
WC_Product_Variable::sync( $vp_id );
wc_delete_product_transients( $vp_id );

$el           = new PFH_Element_Product( [ 'id' => 'emo' ] );
$el->name     = 'pfh-product';
$el->settings = [ 'previewId' => (string) $vp_id ];
ob_start();
$el->render();
$page = (string) ob_get_clean();

ok( 'the product page draws the borrowed emoji', (bool) preg_match( '#data-pfh-pill="Appel / granaatappel"[^>]*><span class="pfh-pdp__pill-emoji" aria-hidden="true">\x{1F34E}</span><span class="pfh-pdp__pill-text" data-pfh-pill-text>Appel / granaatappel</span>#u', $page ) );
ok( '  and the one for "Citroen 2.0"', (bool) preg_match( '#data-pfh-pill="Citroen 2.0"[^>]*><span class="pfh-pdp__pill-emoji" aria-hidden="true">\x{1F34B}</span>#u', $page ) );
ok( '  and none where there is no match', (bool) preg_match( '#data-pfh-pill="Onbekend" aria-pressed="(true|false)"><span class="pfh-pdp__pill-text"#', $page ) );
ok( 'the value itself is untouched, so the variations still match', false !== strpos( $page, 'value="Citroen 2.0"' ) && 'Citroen 2.0' === wc_get_product( $vp_id )->get_attributes()['proefsmaak']->get_options()[1] );

echo "\n── the quick-add popup ──\n";

/**
 * The popup's list of choices for a product, as the browser gets it.
 *
 * @param int $id Product.
 * @return array
 */
function quickadd_choices( $id ) {
	$_POST    = [ 'product_id' => $id, 'nonce' => wp_create_nonce( PFH_Widgets_Ajax::NONCE ) ];
	$_REQUEST = $_POST;
	$handler  = static function () {
		return static function () { throw new RuntimeException( 'wp_die' ); };
	};

	add_filter( 'wp_doing_ajax', '__return_true' );
	add_filter( 'wp_die_ajax_handler', $handler );
	ob_start();

	try {
		PFH_Widgets_Quickadd::variations();
	} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
		// wp_send_json ends in wp_die; the body is already buffered.
	}

	$body = (string) ob_get_clean();
	remove_filter( 'wp_doing_ajax', '__return_true' );
	remove_filter( 'wp_die_ajax_handler', $handler );

	return (array) ( json_decode( $body, true )['data'] ?? [] );
}

// The shop's own case: values of a global attribute, stored as character
// references on a table that cannot hold the emoji itself.
add_filter( 'pfh_widgets_encode_attribute_emoji', '__return_true' );
$qa_terms = [];
foreach ( [ "\u{1F34A} Mandarijn", "\u{1F34E} Appel & Peer" ] as $n ) {
	$made = wp_insert_term( $n, $gtax );
	if ( ! is_wp_error( $made ) ) { $qa_terms[] = (int) $made['term_id']; }
}
remove_filter( 'pfh_widgets_encode_attribute_emoji', '__return_true' );
$stored_name = get_term( $qa_terms[0], $gtax )->name;
ok( 'the stand-in values are stored as the shop\'s are', 0 === strpos( $stored_name, '&#x1f34a;' ), $stored_name );

$global = new WC_Product_Attribute();
$global->set_id( $attr_id );
$global->set_name( $gtax );
$global->set_options( $qa_terms );
$global->set_visible( true );
$global->set_variation( true );
$qp = new WC_Product_Variable();
$qp->set_name( 'PFH quickadd emoji test & co' );
$qp->set_status( 'publish' );
$qp->set_attributes( [ $global ] );
$qp_id = $qp->save();
foreach ( $qa_terms as $tid ) {
	$var = new WC_Product_Variation();
	$var->set_parent_id( $qp_id );
	$var->set_attributes( [ $gtax => get_term( $tid, $gtax )->slug ] );
	$var->set_regular_price( '10' );
	$var->save();
}
WC_Product_Variable::sync( $qp_id );
wc_delete_product_transients( $qp_id );

$qa     = quickadd_choices( $qp_id );
$labels = wp_list_pluck( (array) ( $qa['attributes'][0]['options'] ?? [] ), 'label' );
ok( 'its choices are the emoji and the words, as plain text', in_array( "\u{1F34A} Mandarijn", $labels, true ) && in_array( "\u{1F34E} Appel & Peer", $labels, true ), wp_json_encode( $labels ) );
ok( '  no character reference and no &amp; left for the popup to escape again', false === strpos( implode( '|', $labels ), '&#' ) && false === strpos( implode( '|', $labels ), '&amp;' ) );
ok( 'the values sent back are still the slugs the variations match on', in_array( get_term( $qa_terms[0], $gtax )->slug, wp_list_pluck( $qa['attributes'][0]['options'], 'value' ), true ) );
ok( 'the product name is plain text too', 'PFH quickadd emoji test & co' === ( $qa['name'] ?? '' ), (string) ( $qa['name'] ?? '' ) );

$qb     = quickadd_choices( $vp_id );
$labels = wp_list_pluck( (array) ( $qb['attributes'][0]['options'] ?? [] ), 'label' );
ok( 'a custom attribute borrows the emoji there as on the product page', [ "\u{1F34E} Appel / granaatappel", "\u{1F34B} Citroen 2.0", 'Onbekend' ] === $labels, wp_json_encode( $labels ) );

foreach ( wc_get_product( $qp_id )->get_children() as $child ) { wp_delete_post( $child, true ); }
wp_delete_post( $qp_id, true );
foreach ( wc_get_product( $vp_id )->get_children() as $child ) { wp_delete_post( $child, true ); }
wp_delete_post( $vp_id, true );
foreach ( (array) get_terms( [ 'taxonomy' => $gtax, 'hide_empty' => false ] ) as $x ) { wp_delete_term( $x->term_id, $gtax ); }
wc_delete_attribute( $attr_id );
delete_transient( 'wc_attribute_taxonomies' );

foreach ( [ $tax, 'pfh_not_an_attribute' ] as $t ) {
	foreach ( (array) get_terms( [ 'taxonomy' => $t, 'hide_empty' => false ] ) as $x ) {
		if ( ! is_wp_error( $x ) ) { wp_delete_term( $x->term_id, $t ); }
	}
}

echo "\n$pass passed, $fail failed\n";
