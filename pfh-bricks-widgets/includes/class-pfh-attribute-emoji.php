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
 *   The order. Values sorted by name would sort by their emoji — every
 *   orange before every lemon. Wherever attribute values are sorted by
 *   name, the leading emoji is ignored, so "Aardbei" still comes first.
 *
 * On the product page the element also draws a leading emoji apart from the
 * words (see split()), so the heading above can read "Smaak — Perzik".
 *
 * A product can also have its own attribute typed into it ("Custom product
 * attribute": "Appel / granaatappel | Perzik | ..."). Those values are plain
 * text on the product, and each variation is tied to that exact text, so an
 * emoji cannot be typed into them without unhooking the variations. Instead
 * they borrow one (see borrowed()): a custom "Smaak" looks up the global
 * Smaak, and "Appel / granaatappel" finds "<emoji> Appel & Granaatappel".
 * Only the product page shows it; nothing is written anywhere.
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
		add_filter( 'get_terms', [ __CLASS__, 'sort_terms' ], 20, 3 );
		add_filter( 'woocommerce_get_product_terms', [ __CLASS__, 'sort_product_terms' ], 20, 4 );
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
	 * Attribute values asked for by name, re-sorted on their words.
	 *
	 * Only a list of whole terms from attribute taxonomies, ordered by name:
	 * anything ordered otherwise (the shop's own custom order, by id, by
	 * count) is left exactly as the database returned it.
	 *
	 * @param array $terms      Terms found.
	 * @param array $taxonomies Taxonomies asked for.
	 * @param array $args       Query arguments.
	 * @return array
	 */
	public static function sort_terms( $terms, $taxonomies, $args ) {
		if ( ! is_array( $terms ) || count( $terms ) < 2 || ! self::by_name( $args ) || ( isset( $args['fields'] ) && 'all' !== $args['fields'] ) ) {
			return $terms;
		}

		foreach ( (array) $taxonomies as $taxonomy ) {
			if ( ! self::is_attribute( $taxonomy ) ) {
				return $terms;
			}
		}

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				return $terms;
			}
		}

		return self::sorted( $terms, $args, static function ( $term ) {
			return $term->name;
		} );
	}

	/**
	 * The same for WooCommerce's own lookup of a product's values, which
	 * can hand back names, slugs or ids rather than whole terms.
	 *
	 * @param array  $terms      Values found.
	 * @param int    $product_id Product.
	 * @param string $taxonomy   Attribute taxonomy.
	 * @param array  $args       Query arguments.
	 * @return array
	 */
	public static function sort_product_terms( $terms, $product_id, $taxonomy, $args ) {
		if ( ! is_array( $terms ) || count( $terms ) < 2 || ! self::is_attribute( $taxonomy ) || ! self::by_name( $args ) ) {
			return $terms;
		}

		$fields = isset( $args['fields'] ) ? (string) $args['fields'] : 'all';

		return self::sorted( $terms, $args, static function ( $value ) use ( $fields, $taxonomy ) {
			if ( $value instanceof WP_Term ) {
				return $value->name;
			}

			if ( 'names' === $fields ) {
				return (string) $value;
			}

			$term = 'slugs' === $fields ? get_term_by( 'slug', (string) $value, $taxonomy ) : get_term( (int) $value, $taxonomy );

			return ( $term && ! is_wp_error( $term ) ) ? $term->name : (string) $value;
		} );
	}

	/**
	 * @param array $args Query arguments.
	 * @return bool Whether they ask for an order by name.
	 */
	private static function by_name( $args ) {
		$orderby = isset( $args['orderby'] ) ? strtolower( (string) $args['orderby'] ) : 'name';

		return in_array( $orderby, [ 'name', 'name_num' ], true );
	}

	/**
	 * Sort on the words of each name, keeping the order asked for.
	 *
	 * Only when a name actually starts with an emoji: without one this
	 * returns the list untouched, so nothing about a shop without emoji
	 * changes at all.
	 *
	 * @param array    $items Items.
	 * @param array    $args  Query arguments (orderby, order).
	 * @param callable $name  Item → its name.
	 * @return array
	 */
	private static function sorted( array $items, $args, callable $name ) {
		$keys  = [];
		$emoji = false;

		foreach ( $items as $i => $item ) {
			list( $mark, $words ) = self::split( (string) call_user_func( $name, $item ) );
			$emoji                = $emoji || '' !== $mark;
			$keys[ $i ]           = strtolower( remove_accents( $words ) );
		}

		if ( ! $emoji ) {
			return $items;
		}

		// WooCommerce's "name (numeric)" arrives as name_num, or as name with
		// force_numeric_name once it has rewritten the query.
		$natural = ( isset( $args['orderby'] ) && 'name_num' === strtolower( (string) $args['orderby'] ) ) || ! empty( $args['force_numeric_name'] );
		$desc    = isset( $args['order'] ) && 'DESC' === strtoupper( (string) $args['order'] );
		$order   = array_keys( $items );

		usort( $order, static function ( $a, $b ) use ( $keys, $natural, $desc ) {
			$c = $natural ? strnatcmp( $keys[ $a ], $keys[ $b ] ) : strcmp( $keys[ $a ], $keys[ $b ] );

			return $desc ? -$c : $c;
		} );

		$out = [];

		foreach ( $order as $i ) {
			$out[] = $items[ $i ];
		}

		// A list keyed by term id stays keyed by term id.
		return array_values( $items ) === $items ? $out : array_combine( $order, $out );
	}

	/**
	 * The emoji a custom attribute's value borrows from the global attribute
	 * of the same name, or ''.
	 *
	 * Matched on letters and digits only, so "Appel / granaatappel" and
	 * "Appel & Granaatappel" are the same value. Failing an exact match, the
	 * longest global value the custom one starts with: "Citroen 2.0" takes
	 * Citroen's, "Aardbei / citroen 2.0" takes Aardbei & Citroen's.
	 *
	 * @param string $attribute Attribute name as the product has it ("Smaak").
	 * @param string $value     Value as the product has it.
	 * @return string Emoji, as plain characters.
	 */
	public static function borrowed( $attribute, $value ) {
		/**
		 * Filter whether custom attributes borrow emoji from global ones.
		 *
		 * @param bool $borrow Default true.
		 */
		if ( taxonomy_exists( (string) $attribute ) || ! apply_filters( 'pfh_widgets_borrow_attribute_emoji', true ) ) {
			return '';
		}

		$map  = self::emoji_map( (string) $attribute );
		$want = self::key( self::split( (string) $value )[1] );

		if ( ! $map || '' === $want ) {
			return '';
		}

		if ( isset( $map[ $want ] ) ) {
			return $map[ $want ];
		}

		$best = '';

		foreach ( $map as $key => $emoji ) {
			if ( strlen( $key ) > strlen( $best ) && strlen( $key ) >= 3 && 0 === strpos( $want, (string) $key ) ) {
				$best = (string) $key;
			}
		}

		return '' === $best ? '' : $map[ $best ];
	}

	/**
	 * Words → emoji for every value of the global attribute that carries
	 * the given name (by its label or its slug). Once per request.
	 *
	 * @param string $attribute Attribute name.
	 * @return array<string,string>
	 */
	private static function emoji_map( $attribute ) {
		static $maps = [];

		$name = self::key( $attribute );

		if ( isset( $maps[ $name ] ) ) {
			return $maps[ $name ];
		}

		$maps[ $name ] = [];

		if ( '' === $name || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return [];
		}

		$taxonomy = '';

		foreach ( (array) wc_get_attribute_taxonomies() as $row ) {
			if ( self::key( $row->attribute_label ) === $name || self::key( $row->attribute_name ) === $name ) {
				$taxonomy = wc_attribute_taxonomy_name( $row->attribute_name );
				break;
			}
		}

		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}

		$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] );

		foreach ( is_array( $terms ) ? $terms : [] as $term ) {
			list( $emoji, $words ) = self::split( $term->name );
			$key                   = self::key( $words );

			if ( '' !== $emoji && '' !== $key && ! isset( $maps[ $name ][ $key ] ) ) {
				$maps[ $name ][ $key ] = $emoji;
			}
		}

		return $maps[ $name ];
	}

	/**
	 * @param string $text Words.
	 * @return string Lower-case letters and digits only.
	 */
	private static function key( $text ) {
		$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return preg_replace( '/[^a-z0-9]+/', '', strtolower( remove_accents( $text ) ) );
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
