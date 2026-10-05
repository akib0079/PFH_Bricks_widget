<?php
/**
 * The design export: everything the new design consists of, in one file.
 *
 * Made on the site the design was built on, read by the import on the site it
 * moves to. It holds the design and nothing else — no order, customer, point
 * or stock figure ever goes in — and it holds it by name: a page by its
 * address, a category by its slug, a product by its id, SKU and slug, an
 * image by its file and a fingerprint of it. Numbers go in too, but only as
 * the old side of a pair the import fills in on the other site.
 *
 * Left out on purpose: the licence (bound to this domain), API keys unless
 * asked for, and the waitlist rows (the new site keeps its own sign-ups).
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Migrate_Export {

	const FORMAT  = 'pfh-design';
	const VERSION = 1;

	/** When the staging copy was taken: what was made after it is new. */
	const DEFAULT_BASELINE = '2026-08-23 21:30:00';

	/** Bricks options that belong to this install, not to the design. */
	const BRICKS_SKIP = '/licen[cs]e|cache|transient|remote_templates|notice|_version$|db_version|signature|dismiss|onboarding|_last_|_lock|feedback|_hash$|_secret/i';

	/** Bricks post meta that is state, not design. */
	const META_SKIP = '/^_bricks_(locked|editor_lock|.*_cache.*)$/';

	/** Pages the shop is built around, kept as references. */
	const POST_OPTIONS = [
		'page_on_front',
		'page_for_posts',
		'woocommerce_shop_page_id',
		'woocommerce_cart_page_id',
		'woocommerce_checkout_page_id',
		'woocommerce_myaccount_page_id',
		'woocommerce_terms_page_id',
		'wp_page_for_privacy_policy',
	];

	/** Taxonomies whose terms move whole, with the products in them. */
	const MEMBER_TAXONOMIES = [ 'product_brand' ];

	/**
	 * Product meta that is the shop's running state, or another service's
	 * record of the product, not its content: it stays as it is there.
	 */
	const CATALOG_SKIP = '/^(_stock|_stock_status|total_sales|_wc_average_rating|_wc_rating_count|_wc_review_count|_edit_lock|_edit_last|_wp_old_slug|_wp_old_date|_wp_trash_meta_.*|_wp_desired_post_slug|_wc_facebook.*|fb_.*|_fb_.*|_wc_gla_.*|_klaviyo.*|_wcpdf_.*|_pfh_.*|_transient_.*|_oembed_.*)$/';

	/** Product taxonomies set as the other site has them. */
	const CATALOG_TAXONOMIES = [ 'product_cat', 'product_tag', 'product_shipping_class', 'product_visibility' ];

	/** @var array References met while walking, by kind. */
	private static $refs = [];

	/** @var string This site's host. */
	private static $host = '';

	/**
	 * Build the export.
	 *
	 * @param array $args { baseline: 'Y-m-d H:i:s', secrets: bool }.
	 * @return array
	 */
	public static function build( array $args = [] ) {
		global $wpdb;

		$baseline = isset( $args['baseline'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', (string) $args['baseline'] )
			? (string) $args['baseline']
			: self::DEFAULT_BASELINE;

		self::$refs = [ 'attachment' => [], 'post' => [], 'term' => [], 'file' => [] ];
		self::$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		$uploads = wp_get_upload_dir();

		$package = [
			'format'      => self::FORMAT,
			'version'     => self::VERSION,
			'plugin'      => defined( 'PFH_WIDGETS_VERSION' ) ? PFH_WIDGETS_VERSION : '',
			'created'     => gmdate( 'c' ),
			'source'      => [
				'home'    => untrailingslashit( home_url() ),
				'uploads' => untrailingslashit( (string) $uploads['baseurl'] ),
				'name'    => get_bloginfo( 'name' ),
			],
			'baseline'    => self::baseline( $baseline ),
			'posts'       => [],
			'ref_posts'   => [],
			'terms'       => [],
			'ref_terms'   => [],
			'products'    => [],
			'catalog'     => [],
			'cat_terms'   => [],
			'attributes'  => [],
			'menus'       => [],
			'options'     => [],
			'modules'     => [],
			'theme_mods'  => null,
			'post_opts'   => [],
			'patches'     => [],
			'gateways'    => [],
			'attachments' => [],
			'files'       => [],
			'notes'       => [],
		];

		$package['posts']    = self::posts( $package['baseline']['max_post_id'] );
		$package['terms']    = self::terms();
		$package['products'] = self::products();
		$package['menus']    = self::menus();

		if ( ! empty( $args['catalog'] ) ) {
			$package['catalog']    = self::catalog();
			$package['cat_terms']  = self::catalog_terms();
			$package['attributes'] = self::attribute_taxonomies();
		}

		self::options( $package, ! empty( $args['secrets'] ) );
		self::shop_settings( $package );

		$package['ref_posts']   = self::ref_posts( $package );
		$package['ref_terms']   = self::ref_terms( $package );
		$package['attachments'] = self::attachments( $package );
		$package['files']       = self::files( $package );

		return $package;
	}

	/**
	 * Where the copy ends: everything up to this id existed on the site the
	 * copy was taken from; past it, the number means something else there.
	 *
	 * Dates are what tell, but dates can mislead both ways: something made
	 * since can carry an old date (an import keeps its dates), and something
	 * older can carry a new one (a draft published later). So the line goes
	 * where the fewest posts are on the wrong side of it, and the ones that
	 * are go in the file, to be seen. The import does not lean on it alone:
	 * past the line, a post must also have the same address there.
	 *
	 * @param string $date When the copy was taken.
	 * @return array
	 */
	private static function baseline( $date ) {
		global $wpdb;

		// In full, as the posts table has it: "2026-08-23 21:30" → "… 21:30:00".
		$time = strtotime( $date );
		$date = false === $time ? $date : gmdate( 'Y-m-d H:i:s', $time );

		// Drafts and scheduled posts carry dates that move or lie ahead.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT ID, post_type, post_date FROM {$wpdb->posts} WHERE post_status NOT IN ('auto-draft', 'draft', 'pending', 'future') AND post_date > '1000-01-01' ORDER BY ID", ARRAY_A );

		foreach ( $rows as $i => $row ) {
			$rows[ $i ]['after_copy'] = $row['post_date'] > $date;
		}

		// Wrong-side count with the line at 0: every post dated before.
		$wrong = 0;

		foreach ( $rows as $row ) {
			$wrong += $row['after_copy'] ? 0 : 1;
		}

		$best      = $wrong;
		$best_line = 0;

		foreach ( $rows as $row ) {
			$wrong += $row['after_copy'] ? 1 : -1;

			// Strictly fewer only: on a tie the lower line, which is the safe side.
			if ( $wrong < $best ) {
				$best      = $wrong;
				$best_line = (int) $row['ID'];
			}
		}

		$misfits = [];

		foreach ( $rows as $row ) {
			$inside = (int) $row['ID'] <= $best_line;

			if ( $inside === (bool) $row['after_copy'] && count( $misfits ) < 15 ) {
				$misfits[] = sprintf( '%d %s %s', $row['ID'], $row['post_type'], $row['post_date'] );
			}
		}

		return [
			'date'        => $date,
			'max_post_id' => $best_line,
			'misfits'     => $best,
			'examples'    => $misfits,
		];
	}

	/**
	 * A file name for the download.
	 *
	 * @return string
	 */
	public static function filename() {
		return 'pfh-design-' . gmdate( 'Y-m-d-Hi' ) . '.json';
	}

	/**
	 * Collect while walking: every reference is noted, nothing is changed.
	 *
	 * @param string $kind  Kind.
	 * @param mixed  $value Value.
	 * @return mixed
	 */
	public static function collect( $kind, $value ) {
		if ( 'attachment' === $kind || 'post' === $kind ) {
			if ( (int) $value > 0 ) {
				self::$refs[ $kind ][ (int) $value ] = true;
			}
		} elseif ( 0 === strpos( $kind, 'term:' ) ) {
			if ( (int) $value > 0 ) {
				self::$refs['term'][ substr( $kind, 5 ) ][ (int) $value ] = true;
			}
		} elseif ( 'url' === $kind ) {
			foreach ( PFH_Widgets_Migrate_Refs::upload_paths( (string) $value, self::$host ) as $path ) {
				self::$refs['file'][ $path ] = true;
			}
		}

		return $value;
	}

	/**
	 * The meta keys a Bricks layout lives in.
	 *
	 * @return string[]
	 */
	public static function layout_keys() {
		return [
			defined( 'BRICKS_DB_PAGE_CONTENT' ) ? BRICKS_DB_PAGE_CONTENT : '_bricks_page_content_2',
			defined( 'BRICKS_DB_PAGE_HEADER' ) ? BRICKS_DB_PAGE_HEADER : '_bricks_page_header_2',
			defined( 'BRICKS_DB_PAGE_FOOTER' ) ? BRICKS_DB_PAGE_FOOTER : '_bricks_page_footer_2',
		];
	}

	/**
	 * Every template, and every post with a Bricks layout.
	 *
	 * @param int $max_post_id Last id of the copy.
	 * @return array
	 */
	private static function posts( $max_post_id ) {
		global $wpdb;

		$keys = self::layout_keys();
		$in   = implode( ',', array_fill( 0, count( $keys ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built above.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE m.meta_key IN ($in) AND m.meta_value NOT IN ('', 'a:0:{}') AND p.post_status IN ('publish', 'private') AND p.post_type NOT IN ('revision', 'attachment', 'nav_menu_item') ORDER BY p.ID", $keys ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		// Templates and fonts, and the FunnelKit checkout, thank-you pages and
		// order bumps, whose fields and texts live in their own meta.
		$templates = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('bricks_template', 'bricks_fonts', 'wfacp_checkout', 'wffn_ty', 'wfob_bump') AND post_status IN ('publish', 'private') ORDER BY ID" );

		$ids = array_values( array_unique( array_map( 'intval', array_merge( $templates, $ids ) ) ) );
		$out = [];

		foreach ( $ids as $id ) {
			$post = get_post( $id );

			if ( ! $post ) {
				continue;
			}

			$out[] = self::post( $post, $max_post_id );
		}

		return $out;
	}

	/**
	 * One post, by address, with its design meta.
	 *
	 * @param WP_Post $post        Post.
	 * @param int     $max_post_id Last id of the copy.
	 * @return array
	 */
	private static function post( WP_Post $post, $max_post_id ) {
		$new  = (int) $post->ID > (int) $max_post_id;
		$meta = [];

		foreach ( get_post_meta( $post->ID ) as $key => $values ) {
			if ( ! self::design_meta( $post->post_type, $key ) ) {
				continue;
			}

			$value = maybe_unserialize( $values[0] );

			$meta[ $key ] = PFH_Widgets_Migrate_Refs::post_meta( $key, $value, [ __CLASS__, 'collect' ] );
		}

		$terms = [];

		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			if ( in_array( $taxonomy, [ 'post_format', 'post_tag', 'category' ], true ) && 'bricks_template' !== $post->post_type ) {
				continue;
			}

			$slugs = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'slugs' ] );

			if ( ! is_wp_error( $slugs ) && $slugs ) {
				$terms[ $taxonomy ] = $slugs;
			}
		}

		if ( $post->post_parent ) {
			self::$refs['post'][ (int) $post->post_parent ] = true;
		}

		return [
			'source_id' => (int) $post->ID,
			'type'      => $post->post_type,
			'status'    => $post->post_status,
			'title'     => $post->post_title,
			'slug'      => $post->post_name,
			'path'      => self::path( $post ),
			'parent'    => (int) $post->post_parent,
			'order'     => (int) $post->menu_order,
			'modified'  => $post->post_modified,
			'new'       => $new,
			// Used only when the post is not found there: a page that exists
			// on the other site keeps the words it has.
			'content'   => $post->post_content,
			'excerpt'   => $post->post_excerpt,
			'meta'      => $meta,
			'terms'     => $terms,
		];
	}

	/**
	 * Is this meta key part of the design?
	 *
	 * @param string $type Post type.
	 * @param string $key  Meta key.
	 * @return bool
	 */
	private static function design_meta( $type, $key ) {
		if ( 0 === strpos( $key, '_bricks_' ) ) {
			return ! preg_match( self::META_SKIP, $key );
		}

		if ( '_wp_page_template' === $key ) {
			return true;
		}

		// A custom font's files and how it is shown.
		if ( 'bricks_fonts' === $type ) {
			return 0 === strpos( $key, 'bricks_font_' );
		}

		// FunnelKit keeps a checkout's fields and design, and a thank-you
		// page's design, in its own meta.
		if ( 'wfacp_checkout' === $type ) {
			return 0 === strpos( $key, '_wfacp_' ) && ! preg_match( '/stat|view|count|revenue|conversion/i', $key );
		}

		if ( 'wffn_ty' === $type ) {
			return 0 === strpos( $key, '_wfty_' ) && ! preg_match( '/stat|view|count|revenue|conversion/i', $key );
		}

		// An order bump keeps its product, texts and look in its own meta.
		if ( 'wfob_bump' === $type ) {
			return ! preg_match( '/^_(edit_lock|edit_last|wp_old_slug|wp_old_date)$|stat|view|count|revenue|conversion/i', $key );
		}

		return false;
	}

	/**
	 * A post's address: its path for a page, its slug otherwise.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	public static function path( WP_Post $post ) {
		return is_post_type_hierarchical( $post->post_type ) ? (string) get_page_uri( $post ) : (string) $post->post_name;
	}

	/**
	 * Categories with their own content, and the brands.
	 *
	 * @return array
	 */
	private static function terms() {
		$out   = [];
		$terms = [];

		if ( taxonomy_exists( 'product_cat' ) && class_exists( 'PFH_Widgets_Collection' ) ) {
			$found = get_terms(
				[
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'meta_key'   => PFH_Widgets_Collection::META, // phpcs:ignore WordPress.DB.SlowDBQuery
				]
			);

			if ( ! is_wp_error( $found ) ) {
				$terms = array_merge( $terms, $found );
			}
		}

		foreach ( self::MEMBER_TAXONOMIES as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$found = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] );

				if ( ! is_wp_error( $found ) ) {
					$terms = array_merge( $terms, $found );
				}
			}
		}

		foreach ( $terms as $term ) {
			$parent = $term->parent ? get_term( $term->parent, $term->taxonomy ) : null;
			$meta   = [];

			if ( class_exists( 'PFH_Widgets_Collection' ) ) {
				$value = get_term_meta( $term->term_id, PFH_Widgets_Collection::META, true );

				if ( is_array( $value ) && $value ) {
					$meta[ PFH_Widgets_Collection::META ] = PFH_Widgets_Migrate_Refs::collection( $value, [ __CLASS__, 'collect' ] );
				}
			}

			$members = [];

			if ( in_array( $term->taxonomy, self::MEMBER_TAXONOMIES, true ) ) {
				// Products on sale only: a brand made for a test product stays here.
				$members = array_values(
					array_filter(
						array_map( 'intval', (array) get_objects_in_term( $term->term_id, $term->taxonomy ) ),
						static function ( $id ) {
							return 'publish' === get_post_status( $id );
						}
					)
				);

				if ( ! $members ) {
					continue;
				}

				foreach ( $members as $member ) {
					self::$refs['post'][ $member ] = true;
				}
			}

			$out[] = [
				'source_id'   => (int) $term->term_id,
				'taxonomy'    => $term->taxonomy,
				'slug'        => $term->slug,
				'name'        => $term->name,
				'description' => $term->description,
				'parent'      => $parent && ! is_wp_error( $parent ) ? $parent->slug : '',
				'meta'        => $meta,
				'members'     => $members,
			];
		}

		return $out;
	}

	/**
	 * Products with fields of their own (_pfh_*).
	 *
	 * @return array
	 */
	private static function products() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ids = $wpdb->get_col( "SELECT DISTINCT m.post_id FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key LIKE '\\_pfh\\_%' AND p.post_type = 'product' AND p.post_status NOT IN ('trash', 'auto-draft') ORDER BY m.post_id" );
		$out = [];

		foreach ( $ids as $id ) {
			$post = get_post( (int) $id );

			if ( ! $post ) {
				continue;
			}

			$meta = [];

			foreach ( get_post_meta( $post->ID ) as $key => $values ) {
				if ( 0 !== strpos( $key, '_pfh_' ) ) {
					continue;
				}

				$meta[ $key ] = PFH_Widgets_Migrate_Refs::product_meta( $key, maybe_unserialize( $values[0] ), [ __CLASS__, 'collect' ] );
			}

			$out[] = [
				'source_id' => (int) $post->ID,
				'slug'      => $post->post_name,
				'sku'       => (string) get_post_meta( $post->ID, '_sku', true ),
				'title'     => $post->post_title,
				'meta'      => $meta,
			];
		}

		return $out;
	}

	/**
	 * The catalogue as the shop shows it: every product and variation with
	 * its text, its fields and its terms. Stock and the like stay out.
	 *
	 * @return array
	 */
	private static function catalog() {
		global $wpdb;

		// Products before their variations, so a parent is found first.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('product', 'product_variation', 'post') AND post_status NOT IN ('trash', 'auto-draft', 'inherit') ORDER BY post_type = 'product_variation', ID" );
		$out = [];

		foreach ( $ids as $id ) {
			$post = get_post( (int) $id );

			if ( ! $post ) {
				continue;
			}

			$meta = [];

			foreach ( get_post_meta( $post->ID ) as $key => $values ) {
				if ( preg_match( self::CATALOG_SKIP, (string) $key ) ) {
					continue;
				}

				$meta[ $key ] = PFH_Widgets_Migrate_Refs::catalog_meta( (string) $key, maybe_unserialize( $values[0] ), [ __CLASS__, 'collect' ] );
			}

			$terms = [];

			foreach ( 'post' === $post->post_type ? [ 'category', 'post_tag' ] : self::catalog_taxonomy_names() as $taxonomy ) {
				$slugs = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'slugs' ] );

				if ( ! is_wp_error( $slugs ) ) {
					$terms[ $taxonomy ] = $slugs;
				}
			}

			if ( $post->post_parent ) {
				self::$refs['post'][ (int) $post->post_parent ] = true;
			}

			$out[] = [
				'source_id' => (int) $post->ID,
				'type'      => $post->post_type,
				'parent'    => (int) $post->post_parent,
				'slug'      => $post->post_name,
				'sku'       => (string) get_post_meta( $post->ID, '_sku', true ),
				'title'     => $post->post_title,
				'content'   => self::collect( 'url', $post->post_content ),
				'excerpt'   => self::collect( 'url', $post->post_excerpt ),
				'status'    => $post->post_status,
				'order'     => (int) $post->menu_order,
				'date'      => $post->post_date,
				'date_gmt'  => $post->post_date_gmt,
				'author'    => (int) $post->post_author,
				'comments'  => $post->comment_status,
				'meta'      => $meta,
				'terms'     => $terms,
			];
		}

		return $out;
	}

	/**
	 * The product taxonomies the catalogue sets: the fixed ones and every
	 * attribute (pa_*).
	 *
	 * @return string[]
	 */
	public static function catalog_taxonomy_names() {
		$names = self::CATALOG_TAXONOMIES;

		if ( function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
			$names = array_merge( $names, wc_get_attribute_taxonomy_names() );
		}

		return array_values( array_filter( array_unique( $names ), 'taxonomy_exists' ) );
	}

	/**
	 * The terms the catalogue uses — tags, attribute values, categories — by
	 * slug, with the names and descriptions they carry here.
	 *
	 * @return array
	 */
	private static function catalog_terms() {
		$out = [];

		foreach ( array_merge( self::catalog_taxonomy_names(), [ 'category', 'post_tag' ] ) as $taxonomy ) {
			$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] );

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$parent = $term->parent ? get_term( $term->parent, $taxonomy ) : null;

				$out[] = [
					'source_id'   => (int) $term->term_id,
					'taxonomy'    => $taxonomy,
					'slug'        => $term->slug,
					'name'        => $term->name,
					'description' => $term->description,
					'parent'      => $parent && ! is_wp_error( $parent ) ? $parent->slug : '',
					'order'       => (string) get_term_meta( $term->term_id, 'order', true ),
				];
			}
		}

		return $out;
	}

	/**
	 * The product attributes themselves (Smaak, Inhoud …).
	 *
	 * @return array
	 */
	private static function attribute_taxonomies() {
		$out = [];

		foreach ( function_exists( 'wc_get_attribute_taxonomies' ) ? (array) wc_get_attribute_taxonomies() : [] as $row ) {
			$out[] = [
				'name'    => $row->attribute_name,
				'label'   => $row->attribute_label,
				'type'    => $row->attribute_type,
				'orderby' => $row->attribute_orderby,
				'public'  => (int) $row->attribute_public,
			];
		}

		return $out;
	}

	/**
	 * Every menu, item by item.
	 *
	 * @return array
	 */
	private static function menus() {
		$out = [];

		foreach ( (array) wp_get_nav_menus() as $menu ) {
			$items = [];

			$posts = get_posts(
				[
					'post_type'      => 'nav_menu_item',
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
					'tax_query'      => [ [ 'taxonomy' => 'nav_menu', 'field' => 'term_id', 'terms' => $menu->term_id ] ], // phpcs:ignore WordPress.DB.SlowDBQuery
				]
			);

			foreach ( $posts as $item ) {
				$type   = (string) get_post_meta( $item->ID, '_menu_item_type', true );
				$object = (string) get_post_meta( $item->ID, '_menu_item_object', true );
				$target = (int) get_post_meta( $item->ID, '_menu_item_object_id', true );

				if ( 'post_type' === $type && $target ) {
					self::$refs['post'][ $target ] = true;
				} elseif ( 'taxonomy' === $type && $target ) {
					self::$refs['term'][ $object ][ $target ] = true;
				}

				$url = (string) get_post_meta( $item->ID, '_menu_item_url', true );

				$items[] = [
					'source_id'   => (int) $item->ID,
					'parent'      => (int) get_post_meta( $item->ID, '_menu_item_menu_item_parent', true ),
					'order'       => (int) $item->menu_order,
					'title'       => $item->post_title,
					'description' => $item->post_content,
					'attr_title'  => $item->post_excerpt,
					'type'        => $type,
					'object'      => $object,
					'object_id'   => $target,
					'url'         => '' !== $url ? self::collect( 'url', $url ) : '',
					'target'      => (string) get_post_meta( $item->ID, '_menu_item_target', true ),
					'classes'     => array_values( array_filter( (array) get_post_meta( $item->ID, '_menu_item_classes', true ) ) ),
					'xfn'         => (string) get_post_meta( $item->ID, '_menu_item_xfn', true ),
				];
			}

			$out[] = [
				'source_id'   => (int) $menu->term_id,
				'slug'        => $menu->slug,
				'name'        => $menu->name,
				'description' => $menu->description,
				'items'       => $items,
			];
		}

		return $out;
	}

	/**
	 * Bricks' own settings, this plugin's settings, the theme's mods.
	 *
	 * @param array $package Export, filled in.
	 * @param bool  $secrets Include API keys.
	 */
	private static function options( array &$package, $secrets ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'bricks\\_%' OR option_name LIKE 'premmerce%' ORDER BY option_name" );

		foreach ( $names as $name ) {
			if ( 0 === strpos( $name, 'bricks_' ) && preg_match( self::BRICKS_SKIP, $name ) ) {
				continue;
			}

			$package['options'][ $name ] = PFH_Widgets_Migrate_Refs::walk( get_option( $name ), [ __CLASS__, 'collect' ] );
		}

		foreach ( PFH_Widgets_Settings::tabs() as $slug => $meta ) {
			$class = $meta['class'];

			if ( ! class_exists( $class ) || '' === (string) $class::OPTION || in_array( $class::OPTION, [ 'pfh_license' ], true ) ) {
				continue;
			}

			$value = get_option( $class::OPTION, null );

			if ( ! is_array( $value ) ) {
				continue;
			}

			$media   = [];
			$removed = [];

			foreach ( $class::flat_fields() as $key => $field ) {
				$type = $field['type'] ?? 'text';

				if ( 'media' === $type ) {
					$media[] = $key;
				}

				if ( 'password' === $type && ! $secrets && isset( $value[ $key ] ) ) {
					if ( '' !== (string) $value[ $key ] ) {
						$removed[] = (string) ( $field['label'] ?? $key );
					}

					unset( $value[ $key ] );
				}
			}

			$package['modules'][ $class::OPTION ] = [
				'media' => $media,
				'value' => PFH_Widgets_Migrate_Refs::module_option( $value, $media, [ __CLASS__, 'collect' ] ),
			];

			if ( $removed ) {
				/* translators: 1: settings tab, 2: field labels. */
				$package['notes'][] = sprintf( __( 'Not in the file, on purpose: %1$s — %2$s. Enter them again on the new site.', 'pfh-widgets' ), $slug, implode( ', ', $removed ) );
			}
		}

		$stylesheet = (string) get_option( 'stylesheet' );
		$mods       = get_option( 'theme_mods_' . $stylesheet );

		if ( is_array( $mods ) ) {
			$package['theme_mods'] = [
				'stylesheet' => $stylesheet,
				'value'      => PFH_Widgets_Migrate_Refs::theme_mods( $mods, [ __CLASS__, 'collect' ] ),
			];
		}

		$icon = (int) get_option( 'site_icon' );

		if ( $icon ) {
			$package['post_opts']['site_icon'] = self::collect( 'attachment', $icon );
		}

		foreach ( self::POST_OPTIONS as $name ) {
			$id = (int) get_option( $name );

			if ( $id ) {
				$package['post_opts'][ $name ] = self::collect( 'post', $id );
			}
		}

		$package['options']['show_on_front'] = (string) get_option( 'show_on_front' );
	}

	/**
	 * Shop settings changed for the new design: the DHL option names, the
	 * order of the payment methods.
	 *
	 * @param array $package Export, filled in.
	 */
	private static function shop_settings( array &$package ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce\\_dhlpwc\\_%settings'" );

		foreach ( $names as $name ) {
			$value = get_option( $name );

			if ( ! is_array( $value ) ) {
				continue;
			}

			$set = [];

			foreach ( $value as $key => $item ) {
				if ( 0 === strpos( (string) $key, 'alternative_option_text_' ) && is_string( $item ) && '' !== trim( $item ) ) {
					$set[ $key ] = $item;
				}
			}

			if ( $set ) {
				$package['patches'][] = [
					'option' => $name,
					'set'    => $set,
				];
			}
		}

		if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( 'yes' === $gateway->enabled ) {
					$package['gateways'][] = $gateway->id;
				}
			}
		}
	}

	/**
	 * Posts referred to but not moved: products, the blog page, a page a
	 * button links to. By id, type and address, so the import can find them.
	 *
	 * @param array $package Export.
	 * @return array
	 */
	private static function ref_posts( array &$package ) {
		$moving = [];

		foreach ( $package['posts'] as $post ) {
			$moving[ $post['source_id'] ] = true;
		}

		$out = [];

		foreach ( array_keys( self::$refs['post'] ) as $id ) {
			if ( isset( $moving[ $id ] ) ) {
				continue;
			}

			$post = get_post( $id );

			if ( ! $post || 'trash' === $post->post_status ) {
				/* translators: %d: post id. */
				$package['notes'][] = sprintf( __( 'Something refers to post %d, which does not exist here either. It is left as it is.', 'pfh-widgets' ), $id );
				continue;
			}

			$out[ $id ] = [
				'source_id' => (int) $id,
				'type'      => $post->post_type,
				'slug'      => $post->post_name,
				'path'      => self::path( $post ),
				'sku'       => 'product' === $post->post_type || 'product_variation' === $post->post_type ? (string) get_post_meta( $id, '_sku', true ) : '',
				'title'     => $post->post_title,
			];
		}

		return $out;
	}

	/**
	 * Terms referred to but not moved, by taxonomy and slug.
	 *
	 * @param array $package Export.
	 * @return array
	 */
	private static function ref_terms( array &$package ) {
		$moving = [];

		foreach ( $package['terms'] as $term ) {
			$moving[ $term['taxonomy'] ][ $term['source_id'] ] = true;
		}

		foreach ( $package['menus'] as $menu ) {
			$moving['nav_menu'][ $menu['source_id'] ] = true;
		}

		$out = [];

		foreach ( self::$refs['term'] as $taxonomy => $ids ) {
			foreach ( array_keys( $ids ) as $id ) {
				if ( isset( $moving[ $taxonomy ][ $id ] ) ) {
					continue;
				}

				$term = get_term( $id, $taxonomy );

				if ( ! $term || is_wp_error( $term ) ) {
					/* translators: 1: taxonomy, 2: term id. */
					$package['notes'][] = sprintf( __( 'Something refers to %1$s term %2$d, which does not exist here either. It is left as it is.', 'pfh-widgets' ), $taxonomy, $id );
					continue;
				}

				$out[] = [
					'source_id' => (int) $id,
					'taxonomy'  => $taxonomy,
					'slug'      => $term->slug,
					'name'      => $term->name,
				];
			}
		}

		return $out;
	}

	/**
	 * Every image referred to: its file and a fingerprint of it.
	 *
	 * @param array $package Export.
	 * @return array
	 */
	private static function attachments( array &$package ) {
		$out = [];

		foreach ( array_keys( self::$refs['attachment'] ) as $id ) {
			$post = get_post( $id );

			if ( ! $post || 'attachment' !== $post->post_type ) {
				/* translators: %d: attachment id. */
				$package['notes'][] = sprintf( __( 'An image setting points at %d, which is not an image here. It is left as it is.', 'pfh-widgets' ), $id );
				continue;
			}

			$file = (string) get_post_meta( $id, '_wp_attached_file', true );
			$path = get_attached_file( $id );

			$out[ $id ] = [
				'source_id' => (int) $id,
				'file'      => $file,
				'mime'      => $post->post_mime_type,
				'title'     => $post->post_title,
				'caption'   => $post->post_excerpt,
				'alt'       => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
				'size'      => $path && is_readable( $path ) ? (int) filesize( $path ) : 0,
				'md5'       => $path && is_readable( $path ) ? (string) md5_file( $path ) : '',
			];
		}

		return $out;
	}

	/**
	 * Every uploaded file mentioned by address, and the defaults the plugin's
	 * own elements fall back on.
	 *
	 * @param array $package Export.
	 * @return array
	 */
	private static function files( array &$package ) {
		$base     = trailingslashit( (string) wp_get_upload_dir()['basedir'] );
		$optional = [];

		// The code's own defaults are candidates: keep the ones that exist.
		foreach ( self::code_assets() as $path ) {
			if ( ! isset( self::$refs['file'][ $path ] ) ) {
				$optional[ $path ] = true;
			}

			self::$refs['file'][ $path ] = true;
		}

		$out = [];

		foreach ( array_keys( self::$refs['file'] ) as $path ) {
			$full = $base . $path;

			if ( ! is_readable( $full ) ) {
				if ( isset( $optional[ $path ] ) ) {
					continue;
				}

				/* translators: %s: file path. */
				$package['notes'][] = sprintf( __( 'Mentioned but missing here as well: %s', 'pfh-widgets' ), $path );
				continue;
			}

			$out[ $path ] = [
				'size' => (int) filesize( $full ),
				'md5'  => (string) md5_file( $full ),
			];
		}

		ksort( $out );

		return $out;
	}

	/**
	 * The uploads the plugin's elements use as their defaults.
	 *
	 * @return string[]
	 */
	public static function code_assets() {
		$found   = [];
		$sources = [];
		$bases   = [];

		foreach ( array_merge( (array) glob( PFH_WIDGETS_DIR . 'elements/*.php' ), (array) glob( PFH_WIDGETS_DIR . 'includes/*.php' ) ) as $file ) {
			$sources[ $file ] = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file.

			// A folder constant: const BASE = '/wp-content/uploads/2026/09/';
			if ( preg_match_all( "#const\s+(\w+)\s*=\s*'/wp-content/uploads/(\d{4}/\d{2}/)';#", $sources[ $file ], $m, PREG_SET_ORDER ) ) {
				foreach ( $m as $base ) {
					$bases[ $base[1] ] = $base[2];
				}
			}
		}

		foreach ( $sources as $source ) {
			// A full path.
			if ( preg_match_all( "#/wp-content/uploads/(\d{4}/\d{2}/[A-Za-z0-9._-]+\.[a-z0-9]{2,5})#", $source, $m ) ) {
				foreach ( $m[1] as $path ) {
					$found[ $path ] = true;
				}
			}

			// A file name in a file that uses a folder constant — a child class
			// uses its parent's, and a list of names joins one in a loop — is a
			// candidate in that folder. Only the candidates that exist are kept.
			foreach ( $bases as $name => $folder ) {
				if ( ! preg_match( '#(self|static|parent)::' . preg_quote( $name, '#' ) . '\b#', $source ) ) {
					continue;
				}

				if ( preg_match_all( "#'([A-Za-z0-9._-]+\.(?:png|jpe?g|webp|svg|gif|avif))'#i", $source, $names ) ) {
					foreach ( $names[1] as $file_name ) {
						$found[ $folder . $file_name ] = true;
					}
				}
			}
		}

		return array_keys( $found );
	}
}
