<?php
/**
 * Shop archive: filtering, faceting and paging.
 *
 * The element owns the markup; this owns the question "which products, and
 * what can still be narrowed down". Keeping them apart is what lets the same
 * code answer a page load and an AJAX request identically — the front end
 * never renders a second, subtly different version of the grid.
 *
 * Faceting follows the usual contract: choices inside one group are OR'd,
 * groups are AND'ed, and a group's own selection is excluded when counting its
 * own options — otherwise ticking one colour would show every other colour as
 * zero.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Archive {

	/**
	 * AJAX action and nonce.
	 */
	const ACTION = 'pfh_archive';

	/**
	 * Filter sources that are not taxonomies.
	 *
	 * @var string[]
	 */
	const SPECIAL = [ 'price', 'onsale', 'instock' ];

	/**
	 * Internal state key => the name it travels under in a URL.
	 *
	 * Namespaced because WordPress reserves `page`, `cat`, `order` and
	 * `orderby` as public query vars. A bare ?page=3 on a page means "the
	 * third page of the post content", which empties the archive instead of
	 * paging it — found the hard way on a real install.
	 *
	 * @var array<string, string>
	 */
	const WIRE = [
		'cat'     => 'pfh_cat',
		'page'    => 'pfh_page',
		'orderby' => 'pfh_sort',
		'min'     => 'pfh_min',
		'max'     => 'pfh_max',
		'onsale'  => 'pfh_sale',
		'instock' => 'pfh_stock',
	];

	/**
	 * Prefix for a taxonomy's slug list in a URL.
	 */
	const TAX_PREFIX = 'pfh_tax_';

	/**
	 * The wire contract, handed to the browser so both halves agree.
	 *
	 * @return array{keys:array<string,string>, tax:string}
	 */
	public static function wire() {
		return [
			'keys' => self::WIRE,
			'tax'  => self::TAX_PREFIX,
		];
	}

	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION, [ __CLASS__, 'ajax' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ __CLASS__, 'ajax' ] );
	}

	/* ---------------------------------------------------------------------
	 * State
	 * ------------------------------------------------------------------ */

	/**
	 * Normalise a raw request into a filter state.
	 *
	 * Everything is whitelisted against the element's own configuration, so a
	 * hand-written query string cannot make the archive query a taxonomy the
	 * template never offered.
	 *
	 * @param array $raw    Raw input, usually $_POST or $_GET.
	 * @param array $config Element configuration from settings().
	 * @return array
	 */
	public static function state( array $raw, array $config ) {
		$state = [
			'cat'     => '',
			'page'    => 1,
			'orderby' => '',
			'tax'     => [],
			'min'     => null,
			'max'     => null,
			'onsale'  => false,
			'instock' => false,
		];

		$w = self::WIRE;

		if ( ! empty( $raw[ $w['cat'] ] ) ) {
			$state['cat'] = sanitize_title( wp_unslash( $raw[ $w['cat'] ] ) );
		}

		if ( ! empty( $raw[ $w['page'] ] ) ) {
			$state['page'] = max( 1, (int) $raw[ $w['page'] ] );
		}

		$orders = array_keys( self::order_options() );

		if ( ! empty( $raw[ $w['orderby'] ] ) && in_array( (string) $raw[ $w['orderby'] ], $orders, true ) ) {
			$state['orderby'] = (string) $raw[ $w['orderby'] ];
		}

		foreach ( $config['filters'] as $filter ) {
			$source = $filter['source'];

			if ( 'price' === $source ) {
				// max() hands back the operand it picked, so max(0, -50.0) is
				// the int 0, not 0.0. Cast after, or the state carries a type
				// its own contract says it will not.
				if ( isset( $raw[ $w['min'] ] ) && '' !== $raw[ $w['min'] ] ) {
					$state['min'] = (float) max( 0, (float) $raw[ $w['min'] ] );
				}

				if ( isset( $raw[ $w['max'] ] ) && '' !== $raw[ $w['max'] ] ) {
					$state['max'] = (float) max( 0, (float) $raw[ $w['max'] ] );
				}

				continue;
			}

			if ( 'onsale' === $source || 'instock' === $source ) {
				$state[ $source ] = ! empty( $raw[ $w[ $source ] ] );
				continue;
			}

			// A taxonomy group. Values arrive as a comma separated slug list.
			$key = self::TAX_PREFIX . $source;

			if ( empty( $raw[ $key ] ) ) {
				continue;
			}

			$slugs = is_array( $raw[ $key ] ) ? $raw[ $key ] : explode( ',', (string) wp_unslash( $raw[ $key ] ) );
			$slugs = array_filter( array_map( 'sanitize_title', $slugs ) );

			if ( $slugs ) {
				$state['tax'][ $source ] = array_values( array_unique( $slugs ) );
			}
		}

		if ( null !== $state['min'] && null !== $state['max'] && $state['min'] > $state['max'] ) {
			list( $state['min'], $state['max'] ) = [ $state['max'], $state['min'] ];
		}

		return $state;
	}

	/**
	 * Is anything actually filtered?
	 *
	 * @param array $state Filter state.
	 * @return bool
	 */
	public static function is_filtered( array $state ) {
		return ! empty( $state['tax'] )
			|| null !== $state['min']
			|| null !== $state['max']
			|| ! empty( $state['onsale'] )
			|| ! empty( $state['instock'] );
	}

	/**
	 * How many individual choices are active, for the button's badge.
	 *
	 * @param array $state Filter state.
	 * @return int
	 */
	public static function active_count( array $state ) {
		$count = 0;

		foreach ( $state['tax'] as $slugs ) {
			$count += count( $slugs );
		}

		if ( null !== $state['min'] || null !== $state['max'] ) {
			$count++;
		}

		foreach ( [ 'onsale', 'instock' ] as $flag ) {
			if ( ! empty( $state[ $flag ] ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Sort options offered by the element.
	 *
	 * @return array<string, string>
	 */
	public static function order_options() {
		return [
			'menu_order' => esc_html__( 'Standaard', 'pfh-widgets' ),
			'popularity' => esc_html__( 'Populairste', 'pfh-widgets' ),
			'rating'     => esc_html__( 'Best beoordeeld', 'pfh-widgets' ),
			'date'       => esc_html__( 'Nieuwste', 'pfh-widgets' ),
			'price'      => esc_html__( 'Prijs: laag naar hoog', 'pfh-widgets' ),
			'price-desc' => esc_html__( 'Prijs: hoog naar laag', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Querying
	 * ------------------------------------------------------------------ */

	/**
	 * Build WP_Query arguments for a state.
	 *
	 * @param array  $state  Filter state.
	 * @param array  $config Element configuration.
	 * @param string $skip   Taxonomy to leave out, when counting its own facet.
	 * @return array
	 */
	public static function query_args( array $state, array $config, $skip = '' ) {
		$args = [
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $config['per_page'],
			'paged'               => (int) $state['page'],
			'ignore_sticky_posts' => true,
			'tax_query'           => [ 'relation' => 'AND' ],
			'meta_query'          => [ 'relation' => 'AND' ],
		];

		// Hide out-of-stock products if the store is configured that way.
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$args['tax_query'][] = [
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'outofstock',
				'operator' => 'NOT IN',
			];
		}

		$args['tax_query'][] = [
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => [ 'exclude-from-catalog' ],
			'operator' => 'NOT IN',
		];

		// The archive's own category, or the one the visitor picked.
		$cat = '' !== $state['cat'] ? $state['cat'] : $config['base_cat'];

		if ( '' !== $cat ) {
			$args['tax_query'][] = [
				'taxonomy'         => 'product_cat',
				'field'            => 'slug',
				'terms'            => $cat,
				'include_children' => true,
			];
		}

		foreach ( $state['tax'] as $taxonomy => $slugs ) {
			if ( $taxonomy === $skip || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$args['tax_query'][] = [
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $slugs,
				'operator' => 'IN',
			];
		}

		if ( ! empty( $state['onsale'] ) && function_exists( 'wc_get_product_ids_on_sale' ) ) {
			$sale = wc_get_product_ids_on_sale();

			$args['post__in'] = $sale ? $sale : [ 0 ];
		}

		if ( ! empty( $state['instock'] ) ) {
			$args['tax_query'][] = [
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'outofstock',
				'operator' => 'NOT IN',
			];
		}

		if ( null !== $state['min'] ) {
			$args['meta_query'][] = [
				'key'     => '_price',
				'value'   => $state['min'],
				'compare' => '>=',
				'type'    => 'DECIMAL(10,2)',
			];
		}

		if ( null !== $state['max'] ) {
			$args['meta_query'][] = [
				'key'     => '_price',
				'value'   => $state['max'],
				'compare' => '<=',
				'type'    => 'DECIMAL(10,2)',
			];
		}

		self::order( $args, $state['orderby'] ?: $config['orderby'] );

		/**
		 * Filter the archive query arguments.
		 *
		 * @param array $args   WP_Query arguments.
		 * @param array $state  Filter state.
		 * @param array $config Element configuration.
		 */
		return (array) apply_filters( 'pfh_archive_query_args', $args, $state, $config );
	}

	/**
	 * Apply a sort to the arguments.
	 *
	 * @param array  $args    Query arguments, by reference.
	 * @param string $orderby Sort key.
	 */
	private static function order( array &$args, $orderby ) {
		switch ( $orderby ) {
			case 'popularity':
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			case 'rating':
				$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			case 'date':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;

			case 'price':
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'ASC';
				break;

			case 'price-desc':
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			default:
				$args['orderby'] = [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				];
				break;
		}
	}

	/**
	 * Run the archive query.
	 *
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 * @return \WP_Query
	 */
	public static function query( array $state, array $config ) {
		return new WP_Query( self::query_args( $state, $config ) );
	}

	/* ---------------------------------------------------------------------
	 * Faceting
	 * ------------------------------------------------------------------ */

	/**
	 * Options and counts for every configured filter group.
	 *
	 * Groups with nothing left to offer are dropped, which is what keeps the
	 * panel showing only filters that can actually do something.
	 *
	 * @param array $config Element configuration.
	 * @param array $state  Filter state.
	 * @return array<int, array>
	 */
	public static function facets( array $config, array $state ) {
		$out = [];

		foreach ( $config['filters'] as $filter ) {
			$source = $filter['source'];

			if ( in_array( $source, self::SPECIAL, true ) ) {
				$group = self::special_facet( $filter, $state, $config );
			} else {
				$group = self::taxonomy_facet( $filter, $state, $config );
			}

			if ( $group ) {
				$out[] = $group;
			}
		}

		/**
		 * Filter the rendered facets.
		 *
		 * @param array $out    Facet groups.
		 * @param array $state  Filter state.
		 * @param array $config Element configuration.
		 */
		return (array) apply_filters( 'pfh_archive_facets', $out, $state, $config );
	}

	/**
	 * A taxonomy group, with a count per term.
	 *
	 * @param array $filter One configured filter.
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 * @return array|null
	 */
	private static function taxonomy_facet( array $filter, array $state, array $config ) {
		$taxonomy = $filter['source'];

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return null;
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			]
		);

		if ( is_wp_error( $terms ) || ! $terms ) {
			return null;
		}

		$counts   = self::counts( $taxonomy, $state, $config );
		$selected = isset( $state['tax'][ $taxonomy ] ) ? $state['tax'][ $taxonomy ] : [];
		$options  = [];

		foreach ( $terms as $term ) {
			$count = isset( $counts[ $term->term_id ] ) ? (int) $counts[ $term->term_id ] : 0;
			$on    = in_array( $term->slug, $selected, true );

			// A zero that is not already ticked cannot narrow anything.
			if ( 0 === $count && ! $on ) {
				continue;
			}

			$options[] = [
				'slug'    => $term->slug,
				'label'   => $term->name,
				'count'   => $count,
				'checked' => $on,
			];
		}

		if ( ! $options ) {
			return null;
		}

		return [
			'key'      => $taxonomy,
			'source'   => $taxonomy,
			'type'     => 'terms',
			'label'    => '' !== $filter['label'] ? $filter['label'] : self::taxonomy_label( $taxonomy ),
			'open'     => ! empty( $filter['open'] ) || ! empty( $selected ),
			'limit'    => (int) $filter['limit'],
			'options'  => $options,
			'selected' => $selected,
		];
	}

	/**
	 * Product counts per term, honouring every other active filter.
	 *
	 * One grouped count over the relationship table beats one query per term,
	 * which is the difference between a filter panel that opens instantly and
	 * one that takes a second on a catalogue of any size.
	 *
	 * @param string $taxonomy Taxonomy to count.
	 * @param array  $state    Filter state.
	 * @param array  $config   Element configuration.
	 * @return array<int, int> term_id => count
	 */
	private static function counts( $taxonomy, array $state, array $config ) {
		global $wpdb;

		$args                   = self::query_args( $state, $config, $taxonomy );
		$args['posts_per_page'] = -1;
		$args['paged']          = 1;
		$args['fields']         = 'ids';
		$args['no_found_rows']  = true;

		$ids = get_posts( $args );

		if ( ! $ids ) {
			return [];
		}

		$ids   = array_map( 'absint', $ids );
		$in    = implode( ',', $ids );
		$cache = 'pfh_facet_' . md5( $taxonomy . '|' . $in );
		$found = wp_cache_get( $cache, 'pfh_archive' );

		if ( is_array( $found ) ) {
			return $found;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tt.term_id AS term_id, COUNT(tr.object_id) AS total
				 FROM {$wpdb->term_relationships} tr
				 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				 WHERE tt.taxonomy = %s AND tr.object_id IN ({$in})
				 GROUP BY tt.term_id",
				$taxonomy
			),
			ARRAY_A
		);
		// phpcs:enable

		$out = [];

		foreach ( (array) $rows as $row ) {
			$out[ (int) $row['term_id'] ] = (int) $row['total'];
		}

		wp_cache_set( $cache, $out, 'pfh_archive', MINUTE_IN_SECONDS * 10 );

		return $out;
	}

	/**
	 * Price, on sale and in stock.
	 *
	 * @param array $filter One configured filter.
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 * @return array|null
	 */
	private static function special_facet( array $filter, array $state, array $config ) {
		$source = $filter['source'];

		if ( 'price' === $source ) {
			$range = self::price_range( $state, $config );

			if ( $range['max'] <= $range['min'] ) {
				return null;
			}

			return [
				'key'    => 'price',
				'source' => 'price',
				'type'   => 'price',
				// Dutch, like every other default the archive ships with —
				// "Filter", "Alles Wissen", "%s producten". Each filter's
				// own Label control overrides it.
				'label'  => '' !== $filter['label'] ? $filter['label'] : esc_html__( 'Prijs', 'pfh-widgets' ),
				'open'   => ! empty( $filter['open'] ) || null !== $state['min'] || null !== $state['max'],
				'min'    => $range['min'],
				'max'    => $range['max'],
				'from'   => null === $state['min'] ? $range['min'] : $state['min'],
				'to'     => null === $state['max'] ? $range['max'] : $state['max'],
			];
		}

		return [
			'key'     => $source,
			'source'  => $source,
			'type'    => 'toggle',
			'label'   => '' !== $filter['label'] ? $filter['label'] : (
				'onsale' === $source
					? esc_html__( 'In de aanbieding', 'pfh-widgets' )
					: esc_html__( 'Op voorraad', 'pfh-widgets' )
			),
			'open'    => true,
			'checked' => ! empty( $state[ $source ] ),
		];
	}

	/**
	 * Cheapest and dearest product in the current result set.
	 *
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 * @return array{min:float, max:float}
	 */
	private static function price_range( array $state, array $config ) {
		global $wpdb;

		$args                   = self::query_args( $state, $config );
		$args['posts_per_page'] = -1;
		$args['paged']          = 1;
		$args['fields']         = 'ids';
		$args['no_found_rows']  = true;

		// The range must not collapse to whatever the visitor already picked.
		unset( $args['meta_query'] );

		$ids = get_posts( $args );

		if ( ! $ids ) {
			return [
				'min' => 0.0,
				'max' => 0.0,
			];
		}

		$in = implode( ',', array_map( 'absint', $ids ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			"SELECT MIN(CAST(meta_value AS DECIMAL(10,2))) AS lo,
			        MAX(CAST(meta_value AS DECIMAL(10,2))) AS hi
			 FROM {$wpdb->postmeta}
			 WHERE meta_key = '_price' AND meta_value <> '' AND post_id IN ({$in})",
			ARRAY_A
		);
		// phpcs:enable

		return [
			'min' => (float) floor( (float) ( $row['lo'] ?? 0 ) ),
			'max' => (float) ceil( (float) ( $row['hi'] ?? 0 ) ),
		];
	}

	/**
	 * A readable label for a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	public static function taxonomy_label( $taxonomy ) {
		$object = get_taxonomy( $taxonomy );

		if ( $object && ! empty( $object->labels->singular_name ) ) {
			return $object->labels->singular_name;
		}

		if ( 0 === strpos( $taxonomy, 'pa_' ) && function_exists( 'wc_attribute_label' ) ) {
			return wc_attribute_label( $taxonomy );
		}

		return ucfirst( str_replace( [ 'pa_', '_', '-' ], [ '', ' ', ' ' ], $taxonomy ) );
	}

	/**
	 * Every taxonomy that can be offered as a filter.
	 *
	 * @return array<string, string>
	 */
	public static function filter_sources() {
		$out = [
			'price'   => esc_html__( 'Price range', 'pfh-widgets' ),
			'onsale'  => esc_html__( 'On sale only', 'pfh-widgets' ),
			'instock' => esc_html__( 'In stock only', 'pfh-widgets' ),
		];

		if ( ! PFH_Widgets_Helpers::has_woocommerce() ) {
			return $out;
		}

		foreach ( get_object_taxonomies( 'product', 'objects' ) as $taxonomy ) {
			// The category drives the pill row, not the panel.
			if ( in_array( $taxonomy->name, [ 'product_cat', 'product_visibility', 'product_type', 'product_shipping_class' ], true ) ) {
				continue;
			}

			if ( ! $taxonomy->public && ! $taxonomy->show_ui ) {
				continue;
			}

			$out[ $taxonomy->name ] = self::taxonomy_label( $taxonomy->name );
		}

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * AJAX
	 * ------------------------------------------------------------------ */

	/**
	 * Re-render the grid for a new filter state.
	 *
	 * The element re-renders itself rather than this file emitting markup, so
	 * a filtered page and a freshly loaded one are produced by exactly the
	 * same code.
	 */
	public static function ajax() {
		check_ajax_referer( self::ACTION, 'nonce' );

		$id = isset( $_POST['element'] ) ? sanitize_key( wp_unslash( $_POST['element'] ) ) : '';

		if ( ! $id ) {
			wp_send_json_error( [ 'message' => __( 'Dit filterverzoek hoort bij geen enkel productoverzicht.', 'pfh-widgets' ) ], 400 );
		}

		// The filter state arrives as ordinary form fields, exactly as it
		// arrives in the query string on a page load — and goes through the
		// same whitelist. Anything not offered by the element is dropped.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- whitelisted and sanitised in state().
		$raw = wp_unslash( $_POST );

		/**
		 * Render an archive for an AJAX request.
		 *
		 * The archive element hooks this; nothing else knows how to draw it.
		 *
		 * @param null|array $payload Null until the element answers.
		 * @param string     $id      Element id.
		 * @param array      $raw     Raw request.
		 */
		$payload = apply_filters( 'pfh_archive_ajax', null, $id, $raw );

		if ( ! is_array( $payload ) ) {
			wp_send_json_error( [ 'message' => __( 'Dit productoverzicht staat niet meer op de pagina.', 'pfh-widgets' ) ], 404 );
		}

		wp_send_json_success( $payload );
	}
}
