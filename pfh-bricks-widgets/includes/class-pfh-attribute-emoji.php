<?php
/**
 * Emoji in attribute values — an apple emoji in front of "Appel &
 * Granaatappel", typed straight into the value's name, for every attribute.
 *
 * Nothing extra to fill in: the emoji is part of the name, so it shows
 * wherever WooCommerce shows the value — the product page, the cart, the
 * checkout, the order and its e-mails. This class only takes away the two
 * ways that could go wrong:
 *
 *   Saving. An emoji is four bytes, and a table on MySQL's older "utf8" set
 *   holds three; WordPress then refuses the whole term ("Could not insert
 *   term into the database"). Where the terms table is like that, the emoji
 *   is saved as a character reference instead — what core already does for
 *   post titles on such tables — and a browser draws it as the emoji again.
 *
 *   The slug. WordPress builds a new value's slug from its name, and an
 *   emoji turns into "%f0%9f%8d%8e-appel" in it. The emoji is left out of a
 *   slug made that way, so "<emoji> Appel" gets "appel". An existing value
 *   keeps its slug when renamed, which is what its variations match on.
 *
 * On the product page the element also draws a leading emoji apart from the
 * words (see split()), so the heading above can read "Smaak — Perzik".
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Attribute_Emoji {

	/**
	 * What counts as emoji here: the pictograph blocks, the older symbol
	 * blocks many emoji live in, and the joiners and modifiers that bind a
	 * sequence into one picture.
	 */
	const EMOJI = '\x{1F000}-\x{1FAFF}\x{2300}-\x{23FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}\x{20E3}\x{E0020}-\x{E007F}';

	public static function init() {
		add_filter( 'pre_insert_term', [ __CLASS__, 'encode_name' ], 10, 2 );
		add_filter( 'wp_update_term_data', [ __CLASS__, 'encode_update' ], 10, 4 );
		add_filter( 'wp_insert_term_data', [ __CLASS__, 'clean_slug' ], 10, 3 );
	}

	/**
	 * @param string $taxonomy Taxonomy.
	 * @return bool Whether it is a product attribute (pa_*).
	 */
	public static function is_attribute( $taxonomy ) {
		return is_string( $taxonomy ) && 0 === strpos( $taxonomy, 'pa_' );
	}

	/**
	 * A new value's name, made storable.
	 *
	 * @param string|WP_Error $term     Name.
	 * @param string          $taxonomy Taxonomy.
	 * @return string|WP_Error
	 */
	public static function encode_name( $term, $taxonomy ) {
		if ( ! is_string( $term ) || ! self::is_attribute( $taxonomy ) ) {
			return $term;
		}

		return self::storable( $term );
	}

	/**
	 * A renamed value's name, made storable.
	 *
	 * @param array  $data     Term row about to be written.
	 * @param int    $term_id  Term.
	 * @param string $taxonomy Taxonomy.
	 * @param array  $args     Arguments.
	 * @return array
	 */
	public static function encode_update( $data, $term_id, $taxonomy, $args ) {
		if ( self::is_attribute( $taxonomy ) && isset( $data['name'] ) ) {
			$data['name'] = self::storable( (string) $data['name'] );
		}

		return $data;
	}

	/**
	 * Leave the emoji out of a slug WordPress made from the name.
	 *
	 * Only a new value: renaming never touches the slug of one that exists.
	 *
	 * @param array  $data     Term row about to be written.
	 * @param string $taxonomy Taxonomy.
	 * @param array  $args     Arguments.
	 * @return array
	 */
	public static function clean_slug( $data, $taxonomy, $args ) {
		if ( ! self::is_attribute( $taxonomy ) || empty( $data['slug'] ) || ! empty( $args['slug'] ) ) {
			return $data;
		}

		$slug = (string) $data['slug'];

		// sanitize_title() writes each emoji byte as %xx: a four-byte
		// character starts %f0-%f4, a three-byte symbol (U+2000-2FFF) %e2.
		$clean = preg_replace( '/(%f[0-4](%[89ab][0-9a-f]){3}|%e2(%[89ab][0-9a-f]){2}|%ef%b8%8f|%e2%80%8d)+/i', '', $slug );
		$clean = trim( preg_replace( '/-{2,}/', '-', $clean ), '-' );

		if ( '' !== $clean && $clean !== $slug ) {
			$data['slug'] = wp_unique_term_slug( $clean, (object) [ 'taxonomy' => $taxonomy, 'parent' => 0 ] );
		}

		return $data;
	}

	/**
	 * The name as the terms table can hold it: unchanged on utf8mb4, emoji
	 * as character references on the older three-byte set.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	public static function storable( $name ) {
		global $wpdb;

		if ( ! function_exists( 'wp_encode_emoji' ) || ! preg_match( '/[\x{10000}-\x{10FFFF}]/u', $name ) ) {
			return $name;
		}

		$charset = method_exists( $wpdb, 'get_col_charset' ) ? $wpdb->get_col_charset( $wpdb->terms, 'name' ) : 'utf8mb4';

		/**
		 * Filter whether attribute names keep their emoji as references.
		 *
		 * @param bool   $encode  True when the terms table cannot hold four bytes.
		 * @param string $charset The column's character set.
		 */
		$encode = (bool) apply_filters( 'pfh_widgets_encode_attribute_emoji', is_string( $charset ) && 'utf8mb4' !== $charset, $charset );

		return $encode ? wp_encode_emoji( $name ) : $name;
	}

	/**
	 * A value's name split into its leading emoji and its words.
	 *
	 * "<emoji> Appel & Granaatappel" gives [ '<emoji>', 'Appel & Granaatappel' ].
	 * A name without one comes back as [ '', name ]; one that is only an
	 * emoji is left whole, so there is always something to read.
	 *
	 * @param string $name Name, as stored (references are read as emoji).
	 * @return string[] [ emoji, words ]
	 */
	public static function split( $name ) {
		$text = html_entity_decode( (string) $name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( ! preg_match( '/^\s*([' . self::EMOJI . ']+)\s*(.*)$/us', $text, $m ) || '' === trim( $m[2] ) ) {
			return [ '', $text ];
		}

		return [ $m[1], trim( $m[2] ) ];
	}
}
