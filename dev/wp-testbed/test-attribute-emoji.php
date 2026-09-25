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

foreach ( [ $tax, 'pfh_not_an_attribute' ] as $t ) {
	foreach ( (array) get_terms( [ 'taxonomy' => $t, 'hide_empty' => false ] ) as $x ) {
		if ( ! is_wp_error( $x ) ) { wp_delete_term( $x->term_id, $t ); }
	}
}

echo "\n$pass passed, $fail failed\n";
