<?php
/**
 * Product families: separate products that are one product in different
 * sizes or flavours, linked the way bol.com links them.
 *
 * Gia Giamas citroen comes as a 450 ml and a 1 L bottle, and as six other
 * flavours in both sizes. Those are fourteen products of their own — their
 * own price, stock, photos, reviews and address — and the shop keeps them
 * that way. What a shopper is missing is the way between them: on bol.com
 * the product page shows "Smaak" and "Inhoud" as rows of buttons, and each
 * button is simply a link to the sibling product.
 *
 * A family is a term of its own taxonomy, managed on one screen under
 * Products → Productfamilies: its "keuzes" (the rows of buttons, in order,
 * such as "Smaak, Inhoud") and its members, each with its value for every
 * row. A product belongs to one family at most. The values are kept on the
 * product, keyed by the row's name.
 *
 * A button leads to the member with that value whose other values match the
 * product being viewed; where no member matches all of them, to the one that
 * matches most, so every value of a row is always reachable.
 *
 * No four-byte characters are written as they are: where the database is
 * utf8 rather than utf8mb4, WordPress refuses such a value whole.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Family {

	const TAX    = 'pfh_family';
	const DIMS   = 'pfh_family_dims';
	const VALUES = '_pfh_family_values';
	const NONCE  = 'pfh_family_save';

	/** Rows of buttons a family can have. Three already makes a long page. */
	const MAX_DIMS = 3;

	/** Members per family, as a sanity bound for the query and the screen. */
	const MAX_MEMBERS = 60;

	/** @var array<int, array<int, array>> Members per family, per request. */
	private static $members = [];

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register' ], 11 );
		add_action( self::TAX . '_add_form_fields', [ __CLASS__, 'add_fields' ] );
		add_action( self::TAX . '_edit_form_fields', [ __CLASS__, 'edit_fields' ] );
		add_action( 'created_' . self::TAX, [ __CLASS__, 'save' ] );
		add_action( 'edited_' . self::TAX, [ __CLASS__, 'save' ] );
		add_action( 'pre_delete_term', [ __CLASS__, 'forget_term' ], 10, 2 );
		add_action( 'pfh_widgets_product_panel', [ __CLASS__, 'product_panel' ] );
		add_filter( 'term_updated_messages', [ __CLASS__, 'messages' ] );

		// A product saved in the same request is read afresh.
		add_action( 'clean_post_cache', [ __CLASS__, 'flush' ] );
		add_action( 'clean_object_term_cache', [ __CLASS__, 'flush' ] );

		// A sibling sold out or back in stock changes the buttons on the
		// family's other pages too.
		add_action( 'woocommerce_product_set_stock_status', [ __CLASS__, 'stock_changed' ] );
	}

	/**
	 * @param int $product_id Product whose stock status changed.
	 */
	public static function stock_changed( $product_id ) {
		$term_id = self::family_of( (int) $product_id );

		if ( $term_id ) {
			self::purge( array_keys( self::members( $term_id, true ) ) );
		}
	}

	/**
	 * Ask the page cache to drop these products' pages, so a change to the
	 * family shows on all of them at once rather than when each copy
	 * expires. LiteSpeed Cache listens for this; without it nothing happens.
	 *
	 * @param int[] $ids Products.
	 */
	public static function purge( array $ids ) {
		foreach ( array_unique( array_map( 'intval', $ids ) ) as $id ) {
			if ( $id ) {
				do_action( 'litespeed_purge_post', $id );
			}
		}
	}

	/**
	 * The screen's notices in the shop's own words, instead of WordPress's
	 * generic "Item updated."
	 *
	 * @param array $messages Per taxonomy.
	 * @return array
	 */
	public static function messages( $messages ) {
		$messages[ self::TAX ] = [
			0 => '',
			1 => __( 'Familie toegevoegd. Voeg nu de producten toe.', 'pfh-widgets' ),
			2 => __( 'Familie verwijderd.', 'pfh-widgets' ),
			3 => __( 'Familie bijgewerkt.', 'pfh-widgets' ),
			4 => __( 'Familie niet toegevoegd.', 'pfh-widgets' ),
			5 => __( 'Familie niet bijgewerkt.', 'pfh-widgets' ),
			6 => __( 'Families verwijderd.', 'pfh-widgets' ),
		];

		return $messages;
	}

	/**
	 * Forget the members read so far in this request.
	 */
	public static function flush() {
		self::$members = [];
	}

	public static function register() {
		if ( taxonomy_exists( self::TAX ) || ! post_type_exists( 'product' ) ) {
			return;
		}

		register_taxonomy(
			self::TAX,
			'product',
			[
				'labels'             => [
					'name'          => __( 'Productfamilies', 'pfh-widgets' ),
					'singular_name' => __( 'Productfamilie', 'pfh-widgets' ),
					'menu_name'     => __( 'Productfamilies', 'pfh-widgets' ),
					'all_items'     => __( 'Alle families', 'pfh-widgets' ),
					'edit_item'     => __( 'Familie bewerken', 'pfh-widgets' ),
					'update_item'   => __( 'Familie bijwerken', 'pfh-widgets' ),
					'add_new_item'  => __( 'Nieuwe familie', 'pfh-widgets' ),
					'new_item_name' => __( 'Naam van de familie', 'pfh-widgets' ),
					'search_items'  => __( 'Families zoeken', 'pfh-widgets' ),
					'not_found'     => __( 'Nog geen families.', 'pfh-widgets' ),
					'no_terms'      => __( 'Geen familie', 'pfh-widgets' ),
					'back_to_items' => __( '← Terug naar de families', 'pfh-widgets' ),
				],
				'description'        => __( 'Losse producten die één product zijn in andere maten of smaken. Op de productpagina staan ze als knoppen, zoals op bol.com.', 'pfh-widgets' ),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => false,
				'show_tagcloud'      => false,
				'show_in_quick_edit' => false,
				'show_admin_column'  => true,
				'show_in_rest'       => false,
				'meta_box_cb'        => false,
				'hierarchical'       => false,
				'rewrite'            => false,
				'query_var'          => false,
				'capabilities'       => [
					'manage_terms' => 'manage_product_terms',
					'edit_terms'   => 'edit_product_terms',
					'delete_terms' => 'delete_product_terms',
					'assign_terms' => 'assign_product_terms',
				],
			]
		);
	}

	/* ---------------------------------------------------------------------
	 * Reading
	 * ------------------------------------------------------------------ */

	/**
	 * A family's rows of buttons, in order.
	 *
	 * @param int $term_id Family.
	 * @return string[]
	 */
	public static function dims( $term_id ) {
		$dims = get_term_meta( (int) $term_id, self::DIMS, true );

		return is_array( $dims ) ? array_values( array_filter( array_map( [ __CLASS__, 'plain' ], $dims ), 'strlen' ) ) : [];
	}

	/**
	 * A product's values, row name → value.
	 *
	 * @param int $product_id Product.
	 * @return array<string, string>
	 */
	public static function values( $product_id ) {
		$values = get_post_meta( (int) $product_id, self::VALUES, true );
		$out    = [];

		foreach ( is_array( $values ) ? $values : [] as $dim => $value ) {
			$dim   = self::plain( $dim );
			$value = self::plain( $value );

			if ( '' !== $dim && '' !== $value ) {
				$out[ $dim ] = $value;
			}
		}

		return $out;
	}

	/**
	 * The family a product belongs to, or 0.
	 *
	 * @param int $product_id Product.
	 * @return int
	 */
	public static function family_of( $product_id ) {
		$terms = get_the_terms( (int) $product_id, self::TAX );

		return ( is_array( $terms ) && $terms ) ? (int) $terms[0]->term_id : 0;
	}

	/**
	 * The family's members a shopper can reach, in the shop's own order.
	 *
	 * Published and listed in the catalogue only: a product hidden from the
	 * shop is not offered as a button either.
	 *
	 * @param int  $term_id Family.
	 * @param bool $all     Every member, whatever its status (for the screen).
	 * @return array<int, array<string, string>> Product id → its values.
	 */
	public static function members( $term_id, $all = false ) {
		$term_id = (int) $term_id;
		$key     = $term_id . ( $all ? ':all' : '' );

		if ( isset( self::$members[ $key ] ) ) {
			return self::$members[ $key ];
		}

		$args = [
			'post_type'              => 'product',
			'post_status'            => $all ? [ 'publish', 'private', 'draft', 'pending', 'future' ] : 'publish',
			'posts_per_page'         => self::MAX_MEMBERS,
			'orderby'                => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
			'no_found_rows'          => true,
			'tax_query'              => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => self::TAX,
					'field'    => 'term_id',
					'terms'    => [ $term_id ],
				],
			],
		];

		if ( ! $all ) {
			$args['has_password'] = false;
		}

		$out = [];

		/*
		 * Whole posts rather than ids: WP_Query then primes their meta and
		 * terms in two queries, so the values, the stock, the visibility and
		 * the links below cost nothing more per sibling.
		 */
		foreach ( wp_list_pluck( ( new WP_Query( $args ) )->posts, 'ID' ) as $id ) {
			// Hidden from the shop altogether ("Verborgen"): not offered. A
			// product listed only in the shop, or only in search, still is.
			if ( ! $all && self::hidden( (int) $id ) ) {
				continue;
			}

			$out[ (int) $id ] = self::values( (int) $id );
		}

		self::$members[ $key ] = $out;

		return $out;
	}

	/**
	 * Is a product hidden from the shop and from search both?
	 *
	 * @param int $id Product.
	 * @return bool
	 */
	private static function hidden( $id ) {
		$terms = get_the_terms( (int) $id, 'product_visibility' );
		$names = is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : [];

		return in_array( 'exclude-from-catalog', $names, true ) && in_array( 'exclude-from-search', $names, true );
	}

	/**
	 * What the product page draws: one row per "keuze" with more than one
	 * value, each value a link to the sibling that has it.
	 *
	 * @param int  $product_id Product being viewed.
	 * @param bool $photos     Add each sibling's photo.
	 * @return array|null { term: int, rows: array[] }, or null for nothing to show.
	 */
	public static function view( $product_id, $photos = false ) {
		$product_id = (int) $product_id;
		$term_id    = self::family_of( $product_id );

		if ( ! $term_id ) {
			return null;
		}

		$dims    = self::dims( $term_id );
		$members = self::members( $term_id );

		// A product left out of the buttons (hidden from the shop) still
		// leads to its siblings from its own page.
		if ( ! isset( $members[ $product_id ] ) && 'publish' === get_post_status( $product_id ) ) {
			$members[ $product_id ] = self::values( $product_id );
		}

		if ( ! $dims || count( $members ) < 2 ) {
			return null;
		}

		$current = $members[ $product_id ];
		$stock   = [];
		$rows    = [];

		foreach ( $dims as $index => $dim ) {
			$values = [];

			foreach ( $members as $values_of ) {
				if ( isset( $values_of[ $dim ] ) ) {
					$values[ self::key( $values_of[ $dim ] ) ] = $values_of[ $dim ];
				}
			}

			// A row with one value offers no choice.
			if ( count( $values ) < 2 ) {
				continue;
			}

			$options = [];

			foreach ( self::sorted( array_values( $values ) ) as $value ) {
				$id = self::best( $members, $dims, $dim, $value, $current, $product_id, $stock );

				if ( ! $id ) {
					continue;
				}

				$is_current = isset( $current[ $dim ] ) && self::key( $current[ $dim ] ) === self::key( $value );

				list( $emoji, $words ) = self::emoji( $dim, $value );

				$options[] = [
					'value'    => $value,
					'words'    => $words,
					'emoji'    => $emoji,
					'id'       => $id,
					'url'      => $is_current ? '' : (string) get_permalink( $id ),
					'current'  => $is_current,
					'exact'    => self::matches( $members[ $id ], $current, $dims, $dim ) === count( $dims ) - 1,
					'in_stock' => self::in_stock( $id, $stock ),
					'photo'    => $photos ? (string) get_the_post_thumbnail_url( $id, 'woocommerce_gallery_thumbnail' ) : '',
					'title'    => self::plain( get_the_title( $id ) ),
				];
			}

			if ( count( $options ) < 2 ) {
				continue;
			}

			$rows[] = [
				'label'   => $dim,
				'index'   => $index,
				'current' => isset( $current[ $dim ] ) ? self::emoji( $dim, $current[ $dim ] )[1] : '',
				'options' => $options,
			];
		}

		return $rows ? [ 'term' => $term_id, 'rows' => $rows ] : null;
	}

	/**
	 * The sibling a value's button leads to.
	 *
	 * Of the members with that value, the one sharing most of the viewed
	 * product's other values; then one in stock; then the shop's own order.
	 * For the viewed product's own value that is the product itself.
	 *
	 * @param array  $members    Product id → values.
	 * @param array  $dims       Rows.
	 * @param string $dim        This row.
	 * @param string $value      This value.
	 * @param array  $current    The viewed product's values.
	 * @param int    $product_id The viewed product.
	 * @param array  $stock      Stock cache, by reference.
	 * @return int Product id, or 0.
	 */
	private static function best( array $members, array $dims, $dim, $value, array $current, $product_id, array &$stock ) {
		$want = self::key( $value );

		if ( isset( $current[ $dim ] ) && self::key( $current[ $dim ] ) === $want ) {
			return (int) $product_id;
		}

		$best  = 0;
		$score = -1;

		foreach ( $members as $id => $values ) {
			if ( ! isset( $values[ $dim ] ) || self::key( $values[ $dim ] ) !== $want ) {
				continue;
			}

			// Matching values first, in stock second; the order breaks ties.
			$points = self::matches( $values, $current, $dims, $dim ) * 2 + ( self::in_stock( (int) $id, $stock ) ? 1 : 0 );

			if ( $points > $score ) {
				$score = $points;
				$best  = (int) $id;
			}
		}

		return $best;
	}

	/**
	 * How many of the other rows two products agree on.
	 *
	 * @param array  $a    Values.
	 * @param array  $b    Values.
	 * @param array  $dims Rows.
	 * @param string $skip The row being chosen.
	 * @return int
	 */
	private static function matches( array $a, array $b, array $dims, $skip ) {
		$n = 0;

		foreach ( $dims as $dim ) {
			if ( $dim !== $skip && isset( $a[ $dim ], $b[ $dim ] ) && self::key( $a[ $dim ] ) === self::key( $b[ $dim ] ) ) {
				$n++;
			}
		}

		return $n;
	}

	/**
	 * @param int   $id    Product.
	 * @param array $stock Cache, by reference.
	 * @return bool
	 */
	private static function in_stock( $id, array &$stock ) {
		// WooCommerce's own stock status, read from the meta the members
		// query already loaded; on backorder counts as available, as there.
		if ( ! isset( $stock[ $id ] ) ) {
			$stock[ $id ] = 'outofstock' !== get_post_meta( (int) $id, '_stock_status', true );
		}

		return $stock[ $id ];
	}

	/**
	 * A value's emoji and words. One typed in front of the value is used; a
	 * flavour borrows the emoji its namesake in the global "Smaak"
	 * attribute has, as everywhere else in the shop.
	 *
	 * @param string $dim   Row.
	 * @param string $value Value.
	 * @return string[] [ emoji, words ]
	 */
	private static function emoji( $dim, $value ) {
		if ( ! class_exists( 'PFH_Widgets_Attribute_Emoji' ) ) {
			return [ '', $value ];
		}

		list( $emoji, $words ) = PFH_Widgets_Attribute_Emoji::split( $value );

		if ( '' === $emoji ) {
			$emoji = PFH_Widgets_Attribute_Emoji::borrowed( $dim, $words );
		}

		return [ $emoji, $words ];
	}

	/**
	 * Values in the order a shopper expects: sizes from small to large
	 * (450 ml before 1 L, 55 gr before 940 gr), anything else alphabetically.
	 *
	 * @param string[] $values Values.
	 * @return string[]
	 */
	public static function sorted( array $values ) {
		$amounts = [];
		$kinds   = [];

		foreach ( $values as $value ) {
			$q = self::quantity( $value );

			if ( null === $q ) {
				$kinds = null;
				break;
			}

			$kinds[ $q['kind'] ]        = true;
			$amounts[ (string) $value ] = $q['amount'];
		}

		$by_amount = is_array( $kinds ) && 1 === count( $kinds );

		usort(
			$values,
			static function ( $a, $b ) use ( $by_amount, $amounts ) {
				if ( $by_amount && $amounts[ (string) $a ] !== $amounts[ (string) $b ] ) {
					return $amounts[ (string) $a ] < $amounts[ (string) $b ] ? -1 : 1;
				}

				return strnatcasecmp( self::words( (string) $a ), self::words( (string) $b ) );
			}
		);

		return $values;
	}

	/**
	 * A value's words, without an emoji typed in front of it.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function words( $value ) {
		return class_exists( 'PFH_Widgets_Attribute_Emoji' ) ? PFH_Widgets_Attribute_Emoji::split( $value )[1] : self::plain( $value );
	}

	/**
	 * The amount a value names, in millilitres, grams or pieces.
	 *
	 * "450 ml" is 450 ml, "1L" and "blik 5L" are 1000 and 5000 ml, "500 gr
	 * knijpfles" is 500 g, "7 stuks" is 7 pieces.
	 *
	 * @param string $value Value.
	 * @return array|null { kind: string, amount: float }
	 */
	public static function quantity( $value ) {
		$text = strtolower( remove_accents( self::plain( $value ) ) );

		if ( ! preg_match( '/(\d{1,3}(?:\.\d{3})+(?:,\d+)?|\d+(?:[.,]\d+)?)\s*(ml|cl|dl|ltr|liters|liter|litres|litre|l|kilogram|kilos|kilo|kg|grams|gram|gr|g|stuks|stuk|st)(?![a-z])/', $text, $m ) ) {
			return null;
		}

		// "1.000 gr" is a thousand grams: a dot before three digits is the
		// Dutch thousands separator, a comma the decimal one.
		$number = preg_match( '/^\d{1,3}(?:\.\d{3})+(?:,\d+)?$/', $m[1] ) ? str_replace( '.', '', $m[1] ) : $m[1];
		$n      = (float) str_replace( ',', '.', $number );
		$unit = $m[2];

		$table = [
			'ml'    => [ 'volume', 1 ],
			'cl'    => [ 'volume', 10 ],
			'dl'    => [ 'volume', 100 ],
			'l'     => [ 'volume', 1000 ],
			'ltr'   => [ 'volume', 1000 ],
			'liter' => [ 'volume', 1000 ],
			'litre' => [ 'volume', 1000 ],
			'liters' => [ 'volume', 1000 ],
			'litres' => [ 'volume', 1000 ],
			'g'     => [ 'mass', 1 ],
			'gr'    => [ 'mass', 1 ],
			'gram'  => [ 'mass', 1 ],
			'kg'    => [ 'mass', 1000 ],
			'kilo'  => [ 'mass', 1000 ],
			'kilos' => [ 'mass', 1000 ],
			'kilogram' => [ 'mass', 1000 ],
			'grams' => [ 'mass', 1 ],
			'st'    => [ 'count', 1 ],
			'stuk'  => [ 'count', 1 ],
			'stuks' => [ 'count', 1 ],
		];

		return [ 'kind' => $table[ $unit ][0], 'amount' => $n * $table[ $unit ][1] ];
	}

	/* ---------------------------------------------------------------------
	 * Writing
	 * ------------------------------------------------------------------ */

	/**
	 * Put a family's rows and members in place. What the screen's save does,
	 * public so the tests and a setup script go through exactly the same.
	 *
	 * Members left out lose their place and their values; a member that was
	 * in another family moves to this one.
	 *
	 * @param int   $term_id Family.
	 * @param array $dims    Row names, in order.
	 * @param array $rows    [ [ 'product' => id, 'values' => [ index => value ] ], ... ]
	 * @return int[] The members now in the family.
	 */
	public static function apply( $term_id, array $dims, array $rows ) {
		$term_id = (int) $term_id;
		$dims    = self::clean_dims( $dims );

		self::put_term_meta( $term_id, $dims );

		$before = array_keys( self::members( $term_id, true ) );
		$after  = [];

		foreach ( $rows as $row ) {
			$id = isset( $row['product'] ) ? absint( $row['product'] ) : 0;

			if ( ! $id || 'product' !== get_post_type( $id ) || in_array( $id, $after, true ) || count( $after ) >= self::MAX_MEMBERS ) {
				continue;
			}

			$values = [];
			$given  = isset( $row['values'] ) && is_array( $row['values'] ) ? $row['values'] : [];

			foreach ( $dims as $index => $dim ) {
				$value = self::given( $given, $dim, $index );
				$value = self::plain( sanitize_text_field( $value ) );

				if ( '' !== $value ) {
					$values[ $dim ] = $value;
				}
			}

			wp_set_object_terms( $id, [ $term_id ], self::TAX, false );
			self::put_values( $id, $values );
			$after[] = $id;
		}

		foreach ( array_diff( $before, $after ) as $gone ) {
			wp_remove_object_terms( (int) $gone, [ $term_id ], self::TAX );
			delete_post_meta( (int) $gone, self::VALUES );
		}

		self::$members = [];
		self::purge( array_merge( $before, $after ) );

		return $after;
	}

	/**
	 * A row's value for one "keuze": given under its name, or under its
	 * position in the list.
	 *
	 * @param array  $given Values as given.
	 * @param string $dim   Row name.
	 * @param int    $index Its position.
	 * @return string
	 */
	private static function given( array $given, $dim, $index ) {
		foreach ( $given as $key => $value ) {
			if ( is_string( $key ) && self::key( $key ) === self::key( $dim ) ) {
				return (string) $value;
			}
		}

		return isset( $given[ $index ] ) && ! is_array( $given[ $index ] ) ? (string) $given[ $index ] : '';
	}

	/**
	 * What the screen's columns become when the "keuzes" change in the same
	 * save: a column keeps its values when its row is still there (in any
	 * place), and passes them to the row that took its place when one was
	 * renamed. A column whose row was removed is dropped.
	 *
	 * @param string[] $before Rows the table was drawn with.
	 * @param string[] $after  Rows as saved.
	 * @return array<int, string> Column position → row name.
	 */
	public static function remap( array $before, array $after ) {
		$keys_after  = array_map( [ __CLASS__, 'key' ], $after );
		$keys_before = array_map( [ __CLASS__, 'key' ], $before );
		$map         = [];

		foreach ( $before as $i => $dim ) {
			$found = array_search( self::key( $dim ), $keys_after, true );

			if ( false !== $found ) {
				$map[ $i ] = $after[ $found ];
			} elseif ( count( $before ) === count( $after ) && isset( $after[ $i ] ) && ! in_array( $keys_after[ $i ], $keys_before, true ) ) {
				$map[ $i ] = $after[ $i ];
			}
		}

		return $map;
	}

	/**
	 * @param array $dims Raw names.
	 * @return string[]
	 */
	public static function clean_dims( array $dims ) {
		$out = [];

		foreach ( $dims as $dim ) {
			$dim = self::plain( sanitize_text_field( is_scalar( $dim ) ? (string) $dim : '' ) );

			// Row names are keys of the product's values too, where the save
			// guard does not reach: a four-byte character is encoded here.
			$dim = (string) self::guard( $dim );

			if ( '' !== $dim && ! in_array( self::key( $dim ), array_map( [ __CLASS__, 'key' ], $out ), true ) ) {
				$out[] = $dim;
			}
		}

		return array_slice( $out, 0, self::MAX_DIMS );
	}

	/**
	 * "Smaak, Inhoud" or one per line, as typed into the screen.
	 *
	 * @param string $text Typed.
	 * @return string[]
	 */
	public static function parse_dims( $text ) {
		return self::clean_dims( preg_split( '/[,\r\n]+/', (string) $text ) );
	}

	/**
	 * @param int   $term_id Family.
	 * @param array $dims    Rows.
	 */
	private static function put_term_meta( $term_id, array $dims ) {
		if ( ! $dims ) {
			delete_term_meta( $term_id, self::DIMS );

			return;
		}

		update_term_meta( $term_id, self::DIMS, self::guard( $dims ) );
	}

	/**
	 * @param int   $product_id Product.
	 * @param array $values     Row → value.
	 */
	private static function put_values( $product_id, array $values ) {
		if ( ! $values ) {
			delete_post_meta( $product_id, self::VALUES );

			return;
		}

		update_post_meta( $product_id, self::VALUES, self::guard( $values ) );
	}

	/**
	 * Encode four-byte characters where the database cannot hold them.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private static function guard( $value ) {
		if ( class_exists( 'PFH_Widgets_Save_Guard' ) && PFH_Widgets_Save_Guard::needed() ) {
			return PFH_Widgets_Save_Guard::encode( $value );
		}

		return $value;
	}

	/**
	 * A deleted family leaves no values behind on its members.
	 *
	 * @param int    $term_id  Term.
	 * @param string $taxonomy Taxonomy.
	 */
	public static function forget_term( $term_id, $taxonomy ) {
		if ( self::TAX !== $taxonomy ) {
			return;
		}

		$ids = array_keys( self::members( (int) $term_id, true ) );

		foreach ( $ids as $id ) {
			delete_post_meta( (int) $id, self::VALUES );
		}

		self::$members = [];
		self::purge( $ids );
	}

	/* ---------------------------------------------------------------------
	 * The screen: Products → Productfamilies
	 * ------------------------------------------------------------------ */

	/**
	 * On the "new family" form: just the rows. Members are added once the
	 * family exists, on its own screen.
	 */
	public static function add_fields() {
		wp_nonce_field( self::NONCE, 'pfh_family_nonce' );
		?>
		<div class="form-field">
			<label for="pfh-family-dims"><?php esc_html_e( 'Keuzes', 'pfh-widgets' ); ?></label>
			<input type="text" id="pfh-family-dims" name="pfh_family_dims" value="" placeholder="Smaak, Inhoud" />
			<p><?php esc_html_e( 'De rijen knoppen op de productpagina, in volgorde, met komma\'s ertussen. Bijvoorbeeld "Smaak, Inhoud" of alleen "Inhoud". Producten voeg je toe zodra de familie is aangemaakt.', 'pfh-widgets' ); ?></p>
		</div>
		<?php
	}

	/**
	 * On a family's own screen: the rows, and every member with its values.
	 *
	 * @param WP_Term $term Family.
	 */
	public static function edit_fields( $term ) {
		$dims    = self::dims( $term->term_id );
		$members = self::members( $term->term_id, true );
		$all     = get_posts(
			[
				'post_type'      => 'product',
				'post_status'    => [ 'publish', 'private', 'draft', 'pending', 'future' ],
				'posts_per_page' => 500,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			]
		);

		// A member is always among the choices, however long the shop's list.
		$all = array_values( array_unique( array_merge( array_map( 'intval', $all ), array_keys( $members ) ) ) );

		// Where each product already is, so moving one is a visible choice.
		$elsewhere = [];

		update_object_term_cache( $all, 'product' );

		foreach ( $all as $id ) {
			$family = self::family_of( (int) $id );

			if ( $family && $family !== (int) $term->term_id ) {
				$other              = get_term( $family, self::TAX );
				$elsewhere[ $id ] = $other && ! is_wp_error( $other ) ? $other->name : '';
			}
		}

		$columns = $dims ? $dims : [ __( 'Keuze', 'pfh-widgets' ) ];
		?>
		<tr class="form-field">
			<th scope="row"><label for="pfh-family-dims"><?php esc_html_e( 'Keuzes', 'pfh-widgets' ); ?></label></th>
			<td>
				<?php wp_nonce_field( self::NONCE, 'pfh_family_nonce' ); ?>
				<input type="hidden" name="pfh_family_table" value="1" />
				<input type="hidden" name="pfh_family_dims_before" value="<?php echo esc_attr( (string) wp_json_encode( $dims ) ); ?>" />
				<input type="text" id="pfh-family-dims" name="pfh_family_dims" value="<?php echo esc_attr( implode( ', ', $dims ) ); ?>" placeholder="Smaak, Inhoud" />
				<p class="description"><?php esc_html_e( 'De rijen knoppen op de productpagina, in volgorde, met komma\'s ertussen (hoogstens drie). Een nieuwe keuze krijgt na het opslaan een eigen kolom hieronder.', 'pfh-widgets' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Producten', 'pfh-widgets' ); ?></th>
			<td>
				<table class="widefat striped pfh-family-table" data-pfh-family-table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Product', 'pfh-widgets' ); ?></th>
							<?php foreach ( $columns as $dim ) : ?>
								<th><?php echo esc_html( $dim ); ?></th>
							<?php endforeach; ?>
							<th class="pfh-family-table__x"><span class="screen-reader-text"><?php esc_html_e( 'Verwijderen', 'pfh-widgets' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$i = 0;

						foreach ( $members as $id => $values ) {
							self::member_row( $i++, $all, (int) $id, $values, $columns, $elsewhere );
						}

						if ( ! $members ) {
							self::member_row( $i++, $all, 0, [], $columns, $elsewhere );
						}
						?>
					</tbody>
				</table>
				<template data-pfh-family-blank>
					<?php self::member_row( '__i__', $all, 0, [], $columns, $elsewhere ); ?>
				</template>
				<p><button type="button" class="button" data-pfh-family-add><?php esc_html_e( '+ Product toevoegen', 'pfh-widgets' ); ?></button></p>
				<p class="description">
					<?php esc_html_e( 'Elk product in de familie krijgt een waarde per keuze, precies zoals de knop moet heten: "Citroen", "450 ml", "1 L". Gelijke waarden worden als dezelfde knop gezien. Maten staan vanzelf van klein naar groot. Een product dat al in een andere familie zit, verhuist naar deze.', 'pfh-widgets' ); ?>
				</p>
				<?php self::script(); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * @param int|string $i         Row number, or a placeholder.
	 * @param int[]      $all       Every product.
	 * @param int        $chosen    This row's product.
	 * @param array      $values    Its values.
	 * @param string[]   $columns   Rows of the family.
	 * @param array      $elsewhere Product → the family it is in now.
	 */
	private static function member_row( $i, array $all, $chosen, array $values, array $columns, array $elsewhere ) {
		$name = 'pfh_family_members[' . $i . ']';
		?>
		<tr>
			<td>
				<select name="<?php echo esc_attr( $name . '[product]' ); ?>">
					<option value=""><?php esc_html_e( '— Kies een product —', 'pfh-widgets' ); ?></option>
					<?php foreach ( $all as $id ) : ?>
						<option value="<?php echo (int) $id; ?>"<?php selected( (int) $chosen, (int) $id ); ?>>
							<?php
							echo esc_html( self::plain( get_the_title( $id ) ) . ( 'publish' !== get_post_status( $id ) ? ' (' . get_post_status( $id ) . ')' : '' ) );

							if ( isset( $elsewhere[ $id ] ) ) {
								/* translators: %s: the family the product is in now. */
								echo esc_html( ' — ' . sprintf( __( 'nu in %s', 'pfh-widgets' ), $elsewhere[ $id ] ) );
							}
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
			<?php foreach ( $columns as $index => $dim ) : ?>
				<td><input type="text" name="<?php echo esc_attr( $name . '[values][' . $index . ']' ); ?>" value="<?php echo esc_attr( $values[ $dim ] ?? '' ); ?>" placeholder="<?php echo esc_attr( $dim ); ?>" /></td>
			<?php endforeach; ?>
			<td class="pfh-family-table__x"><button type="button" class="button-link button-link-delete" data-pfh-family-remove aria-label="<?php esc_attr_e( 'Verwijderen', 'pfh-widgets' ); ?>">&times;</button></td>
		</tr>
		<?php
	}

	private static function script() {
		?>
		<style>
			/*
			 * Wide enough to read a whole product name and a whole value
			 * ("Appel & granaatappel"): WordPress keeps this form at 800px,
			 * which left the value columns a few letters wide.
			 */
			body.taxonomy-pfh_family #edittag { max-width: 1200px; }
			.form-field table.pfh-family-table { width: 100%; max-width: 1000px; }
			.pfh-family-table td { vertical-align: middle; }
			.pfh-family-table td:first-child { width: 46%; }
			.pfh-family-table td input[type=text] { min-width: 11em; }
			.form-field .pfh-family-table select { width: 100%; max-width: none; }
			.pfh-family-table input[type=text] { width: 100%; }
			.pfh-family-table__x { width: 32px; text-align: center; }
			.pfh-family-table__x .button-link { font-size: 20px; line-height: 1; text-decoration: none; }
		</style>
		<script>
		( function () {
			var table = document.querySelector( '[data-pfh-family-table]' );
			var blank = document.querySelector( '[data-pfh-family-blank]' );
			var add   = document.querySelector( '[data-pfh-family-add]' );
			var next  = table ? table.tBodies[ 0 ].rows.length + 1000 : 0;

			if ( ! table || ! blank || ! add ) {
				return;
			}

			add.addEventListener( 'click', function () {
				var html = blank.innerHTML.split( '__i__' ).join( String( next++ ) );
				var body = document.createElement( 'tbody' );

				body.innerHTML = html;
				table.tBodies[ 0 ].appendChild( body.firstElementChild );
			} );

			table.addEventListener( 'click', function ( event ) {
				var button = event.target.closest( '[data-pfh-family-remove]' );

				if ( button ) {
					button.closest( 'tr' ).remove();
				}
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * Save the screen.
	 *
	 * @param int $term_id Family.
	 */
	public static function save( $term_id ) {
		if ( ! isset( $_POST['pfh_family_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pfh_family_nonce'] ) ), self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_term', $term_id ) || ! current_user_can( 'edit_products' ) ) {
			return;
		}

		$dims = self::parse_dims( isset( $_POST['pfh_family_dims'] ) ? sanitize_text_field( wp_unslash( $_POST['pfh_family_dims'] ) ) : '' );

		// The "new family" form has no member table: it sets the rows only.
		// The family's own screen says so, so an emptied table empties it.
		if ( ! isset( $_POST['pfh_family_table'] ) ) {
			self::put_term_meta( (int) $term_id, $dims );

			return;
		}

		$drawn  = isset( $_POST['pfh_family_dims_before'] ) ? json_decode( (string) wp_unslash( $_POST['pfh_family_dims_before'] ), true ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded, then cleaned by clean_dims().
		$before = self::clean_dims( is_array( $drawn ) ? $drawn : [] );
		$map    = $before ? self::remap( $before, $dims ) : array_combine( array_keys( $dims ), $dims );
		$posted = isset( $_POST['pfh_family_members'] ) && is_array( $_POST['pfh_family_members'] ) ? wp_unslash( $_POST['pfh_family_members'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every cell is sanitised in apply().
		$rows   = [];

		foreach ( $posted as $row ) {
			$id = is_array( $row ) && isset( $row['product'] ) ? absint( $row['product'] ) : 0;

			// Only products this user may edit are put in or kept.
			if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}

			$values = [];

			foreach ( isset( $row['values'] ) && is_array( $row['values'] ) ? $row['values'] : [] as $column => $value ) {
				if ( isset( $map[ (int) $column ] ) && ! is_array( $value ) ) {
					$values[ $map[ (int) $column ] ] = (string) $value;
				}
			}

			$rows[] = [ 'product' => $id, 'values' => $values ];
		}

		// A member this user may not edit is not theirs to drop either: it
		// stays as it is.
		$listed = array_column( $rows, 'product' );

		foreach ( self::members( (int) $term_id, true ) as $id => $values ) {
			if ( ! in_array( (int) $id, $listed, true ) && ! current_user_can( 'edit_post', (int) $id ) ) {
				$rows[] = [ 'product' => (int) $id, 'values' => $values ];
			}
		}

		self::apply( (int) $term_id, $dims, $rows );
	}

	/**
	 * In the product's own Products For Home tab: which family it is in and
	 * with what values — edited on the family's screen, so there is one
	 * place that keeps the whole family consistent.
	 *
	 * @param int $product_id Product.
	 */
	public static function product_panel( $product_id ) {
		$term_id = self::family_of( (int) $product_id );
		$list    = admin_url( 'edit-tags.php?taxonomy=' . self::TAX . '&post_type=product' );
		?>
		<div class="options_group">
			<p class="form-field">
				<label><?php esc_html_e( 'Productfamilie', 'pfh-widgets' ); ?></label>
				<?php
				if ( $term_id ) {
					$term   = get_term( $term_id, self::TAX );
					$values = self::values( (int) $product_id );
					$parts  = [];

					foreach ( $values as $dim => $value ) {
						$parts[] = $dim . ': ' . $value;
					}

					printf(
						'<strong>%s</strong>%s &nbsp;<a href="%s">%s</a>',
						esc_html( $term && ! is_wp_error( $term ) ? $term->name : '' ),
						$parts ? ' — ' . esc_html( implode( ', ', $parts ) ) : '',
						esc_url( get_edit_term_link( $term_id, self::TAX, 'product' ) ),
						esc_html__( 'Familie beheren', 'pfh-widgets' )
					);
				} else {
					printf(
						'%s <a href="%s">%s</a>',
						esc_html__( 'Niet gekoppeld.', 'pfh-widgets' ),
						esc_url( $list ),
						esc_html__( 'Koppel andere maten of smaken onder Producten → Productfamilies', 'pfh-widgets' )
					);
				}
				?>
			</p>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	/**
	 * Stored text as the characters it stands for.
	 *
	 * @param mixed $text Text.
	 * @return string
	 */
	private static function plain( $text ) {
		return trim( html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * Two values are the same button when they read the same, spaces and
	 * capitals aside: "1L" and "1 l" are one button.
	 *
	 * @param string $text Value.
	 * @return string
	 */
	public static function key( $text ) {
		return preg_replace( '/\s+/u', '', mb_strtolower( self::plain( $text ), 'UTF-8' ) );
	}
}
