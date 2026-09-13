<?php
/**
 * WooCommerce permalink manager.
 *
 * Replaces Premmerce Permalink Manager. The defaults below reproduce the
 * configuration running on productsforhome.nl today — full category path,
 * bare product slug, Yoast primary category — so switching over changes no
 * URL. That is deliberate: the brief's §4 says any URL that resolves today
 * must resolve after launch.
 *
 * Two halves:
 *
 *   Writing  post_type_link / term_link produce the new URLs.
 *   Reading  parse_request resolves them back, because a bare product slug
 *            matches no core rewrite rule.
 *
 * Nothing here 404s a URL that used to work. Every legacy shape — the
 * /product/ base, the /product-category/ base, a bare category slug — still
 * resolves and then 301s to the canonical form.
 *
 * Both modes ship as "Use WooCommerce settings", so activating the plugin
 * changes no routing whatsoever. They used to default to the live site's
 * shape, which meant simply switching the plugin on took over URL handling
 * for the whole store before anyone had opened the settings screen — on a
 * trading shop that is not a default to make for someone.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Permalinks extends PFH_Settings_Module {

	const OPTION = 'pfh_permalinks';

	/**
	 * Resolved-path memo for this request, so parse_request and the canonical
	 * redirect do not repeat the same lookups.
	 *
	 * @var array<string, mixed>
	 */
	private static $lookup = [];

	/**
	 * Set while we rewrite a product permalink, to stop the primary-category
	 * lookup recursing through get_permalink().
	 *
	 * @var bool
	 */
	private static $building = false;

	public static function init() {
		PFH_Widgets_Settings::register( 'permalinks', __( 'Permalinks', 'pfh-widgets' ), __CLASS__ );

		add_action( 'pfh_widgets_settings_saved', [ __CLASS__, 'maybe_flush' ], 10, 2 );

		if ( ! PFH_Widgets_Helpers::has_woocommerce() ) {
			return;
		}

		// Nothing at all until the manager is switched on.
		if ( ! self::get( 'enabled', false ) ) {
			return;
		}

		if ( 'default' !== self::get( 'product_mode', 'default' ) ) {
			add_filter( 'post_type_link', [ __CLASS__, 'product_link' ], 20, 3 );
		}

		if ( 'default' !== self::get( 'category_mode', 'default' ) ) {
			add_filter( 'term_link', [ __CLASS__, 'term_link' ], 20, 3 );
		}

		if ( self::get( 'remove_tag_base', false ) ) {
			add_filter( 'term_link', [ __CLASS__, 'tag_link' ], 20, 3 );
		}

		if ( self::active() ) {
			add_action( 'parse_request', [ __CLASS__, 'resolve' ] );
			add_action( 'template_redirect', [ __CLASS__, 'canonical' ], 5 );
		}
	}

	/**
	 * Whether any rewriting is switched on.
	 *
	 * @return bool
	 */
	public static function active() {
		/*
		 * One switch above everything else, off unless it was turned on.
		 *
		 * Changing the per-mode defaults was not enough on its own: a site
		 * that had ever opened this screen and pressed Save carries the old
		 * modes in the option, so the manager stayed live there however the
		 * defaults moved. An install that never asked for URL rewriting
		 * should not get it, and this is the only way to say so for sites
		 * that already have settings stored.
		 */
		if ( ! self::get( 'enabled', false ) ) {
			return false;
		}

		return 'default' !== self::get( 'product_mode', 'default' )
			|| 'default' !== self::get( 'category_mode', 'default' )
			|| (bool) self::get( 'remove_tag_base', false );
	}

	/**
	 * Settings schema. Defaults mirror the live Premmerce configuration.
	 *
	 * @return array
	 */
	public static function fields() {
		$home = untrailingslashit( home_url() );

		return [
			'products' => [
				'label'  => __( 'Products', 'pfh-widgets' ),
				'desc'   => __( 'How a single product URL is built. <strong>Product slug</strong> matches the structure currently live on this store.', 'pfh-widgets' ),
				'fields' => [
					'enabled' => [
						'type'    => 'checkbox',
						'label'   => __( 'Manage WooCommerce permalinks', 'pfh-widgets' ),
						'default' => false,
						'desc'    => __( 'Off means WooCommerce handles every URL exactly as it does now. Turn this on only on staging first, check a product, a category and the shop page, then move it to production.', 'pfh-widgets' ),
					],

					'permanent_redirect' => [
						'type'    => 'checkbox',
						'label'   => __( 'Make legacy redirects permanent (301)', 'pfh-widgets' ),
						'default' => false,
						'desc'    => __( 'Leave off while you are still changing the URL shape: a 301 is cached by every browser that follows it. Turn it on once the structure is final.', 'pfh-widgets' ),
					],

					'product_mode' => [
						'type'    => 'select',
						'label'   => __( 'Product permalinks', 'pfh-widgets' ),
						'default' => 'default',
						'choices' => [
							'default'       => __( 'Use WooCommerce settings', 'pfh-widgets' ),
							'slug'          => __( 'Product slug', 'pfh-widgets' ),
							'slug_category' => __( 'Product slug with primary category', 'pfh-widgets' ),
							'full_path'     => __( 'Full product path', 'pfh-widgets' ),
						],
						'preview' => [
							'default'       => $home . '/product/sample-product',
							'slug'          => $home . '/sample-product',
							'slug_category' => $home . '/category/sample-product',
							'full_path'     => $home . '/parent-category/category/sample-product',
						],
					],
					'use_primary_category' => [
						'type'     => 'checkbox',
						'label'    => __( 'Use primary category', 'pfh-widgets' ),
						'cb_label' => __( 'Build the path from the Yoast SEO primary category', 'pfh-widgets' ),
						'desc'     => __( 'When a product sits in several categories, the Yoast primary category decides the path. Without it the lowest-numbered category is used. Only affects the two path-based modes above.', 'pfh-widgets' ),
						'default'  => true,
					],
				],
			],

			'categories' => [
				'label'  => __( 'Categories', 'pfh-widgets' ),
				'desc'   => __( 'How a product category archive URL is built. <strong>Full category path</strong> matches the structure currently live on this store.', 'pfh-widgets' ),
				'fields' => [
					'category_mode' => [
						'type'    => 'select',
						'label'   => __( 'Category permalinks', 'pfh-widgets' ),
						'default' => 'default',
						'choices' => [
							'default'   => __( 'Use WooCommerce settings', 'pfh-widgets' ),
							'slug'      => __( 'Category slug', 'pfh-widgets' ),
							'full_path' => __( 'Full category path', 'pfh-widgets' ),
						],
						'preview' => [
							'default'   => $home . '/product-category/parent-category/category',
							'slug'      => $home . '/category',
							'full_path' => $home . '/parent-category/category',
						],
					],
					'remove_tag_base' => [
						'type'     => 'checkbox',
						'label'    => __( 'Product tags', 'pfh-widgets' ),
						'cb_label' => __( 'Remove the product tag base from tag URLs', 'pfh-widgets' ),
						'default'  => false,
					],
				],
			],

			'safety' => [
				'label'  => __( 'Safety', 'pfh-widgets' ),
				'desc'   => __( 'Old URLs keep working either way — this only decides whether they redirect.', 'pfh-widgets' ),
				'fields' => [
					'redirect_legacy' => [
						'type'     => 'checkbox',
						'label'    => __( 'Redirect old URLs', 'pfh-widgets' ),
						'cb_label' => __( 'Send the old URL shape to the new one with a 301', 'pfh-widgets' ),
						'desc'     => __( 'Keeps the WooCommerce bases, bare category slugs and any previous structure resolving, then points search engines at the canonical URL. Leave this on.', 'pfh-widgets' ),
						'default'  => true,
					],
					'examples' => [
						'type'     => 'info',
						'callback' => [ __CLASS__, 'render_examples' ],
					],
					'conflicts' => [
						'type'     => 'info',
						'callback' => [ __CLASS__, 'render_conflicts' ],
					],
				],
			],
		];
	}

	/**
	 * Rewrite rules describe the bases we are bypassing, so a change of mode
	 * has to regenerate them.
	 *
	 * @param string $tab   Saved tab.
	 * @param string $class Saved module.
	 */
	public static function maybe_flush( $tab, $class ) {
		if ( __CLASS__ !== $class ) {
			return;
		}

		self::forget();

		if ( function_exists( 'flush_rewrite_rules' ) ) {
			flush_rewrite_rules( false );
		}
	}

	/* ---------------------------------------------------------------------
	 * Writing URLs
	 * ------------------------------------------------------------------ */

	/**
	 * Build a product permalink.
	 *
	 * $leavename is how the editor asks for the structure with the slug left
	 * as a %product% token, so it can render the editable "Permalink:" row.
	 * Rewriting that case too is what makes the admin screen agree with the
	 * front end — without it the editor keeps showing the WooCommerce base
	 * and the setting looks like it did nothing.
	 *
	 * @param string   $permalink Permalink as WordPress built it.
	 * @param \WP_Post $post      Post object.
	 * @param bool     $leavename Leave the slug as a token.
	 * @return string
	 */
	public static function product_link( $permalink, $post, $leavename = false ) {
		if ( ! is_object( $post ) || 'product' !== $post->post_type || self::$building ) {
			return $permalink;
		}

		$slug = $leavename ? '%product%' : (string) $post->post_name;

		if ( '' === $slug ) {
			return $permalink;
		}

		// Any other structure token means WordPress is mid-build on something
		// we should not touch — a scheduled post's date-based preview, say.
		if ( ! $leavename && false !== strpos( $permalink, '%' ) ) {
			return $permalink;
		}

		$path = self::product_path( $post, $slug );

		if ( '' === $path ) {
			return $permalink;
		}

		return user_trailingslashit( home_url( '/' . $path ), 'single' );
	}

	/**
	 * The path part of a product URL, without leading or trailing slashes.
	 *
	 * @param \WP_Post    $post Product.
	 * @param string|null $slug Override the slug, e.g. with a %product% token.
	 * @return string
	 */
	public static function product_path( $post, $slug = null ) {
		$slug = null === $slug ? (string) $post->post_name : (string) $slug;
		$mode = self::get( 'product_mode', 'default' );

		// Empty means "we are not rewriting this", which product_link() reads
		// as leave the WooCommerce permalink alone. init() normally skips the
		// filter entirely in default mode, but the mode can change after the
		// filter is registered — saving this settings tab does exactly that.
		if ( 'default' === $mode ) {
			return '';
		}

		if ( 'slug' === $mode ) {
			return $slug;
		}

		$term = self::primary_term( $post );

		if ( ! $term ) {
			return $slug;
		}

		$prefix = 'full_path' === $mode ? self::term_path( $term ) : $term->slug;

		return $prefix . '/' . $slug;
	}

	/**
	 * Build a product category permalink.
	 *
	 * @param string   $link     Term link.
	 * @param \WP_Term $term     Term.
	 * @param string   $taxonomy Taxonomy.
	 * @return string
	 */
	public static function term_link( $link, $term, $taxonomy ) {
		if ( 'product_cat' !== $taxonomy || ! is_object( $term ) ) {
			return $link;
		}

		$path = 'full_path' === self::get( 'category_mode', 'default' )
			? self::term_path( $term )
			: (string) $term->slug;

		if ( '' === $path ) {
			return $link;
		}

		return user_trailingslashit( home_url( '/' . $path ), 'category' );
	}

	/**
	 * Drop the base from product tag URLs.
	 *
	 * @param string   $link     Term link.
	 * @param \WP_Term $term     Term.
	 * @param string   $taxonomy Taxonomy.
	 * @return string
	 */
	public static function tag_link( $link, $term, $taxonomy ) {
		if ( 'product_tag' !== $taxonomy || ! is_object( $term ) || '' === (string) $term->slug ) {
			return $link;
		}

		return user_trailingslashit( home_url( '/' . $term->slug ), 'category' );
	}

	/**
	 * Ancestor path for a term: parent/child.
	 *
	 * @param \WP_Term $term Term.
	 * @return string
	 */
	public static function term_path( $term ) {
		if ( ! is_object( $term ) || empty( $term->slug ) ) {
			return '';
		}

		$slugs  = [ $term->slug ];
		$parent = (int) ( $term->parent ?? 0 );
		$guard  = 0;

		while ( $parent && $guard < 20 ) {
			$ancestor = get_term( $parent, $term->taxonomy );

			if ( ! $ancestor || is_wp_error( $ancestor ) ) {
				break;
			}

			array_unshift( $slugs, $ancestor->slug );
			$parent = (int) $ancestor->parent;
			$guard++;
		}

		return implode( '/', $slugs );
	}

	/**
	 * The category a product's path is built from.
	 *
	 * @param \WP_Post $post Product.
	 * @return \WP_Term|null
	 */
	public static function primary_term( $post ) {
		self::$building = true;

		$term = null;

		if ( self::get( 'use_primary_category', true ) ) {
			$primary = (int) get_post_meta( $post->ID, '_yoast_wpseo_primary_product_cat', true );

			if ( $primary > 0 ) {
				$found = get_term( $primary, 'product_cat' );

				if ( $found && ! is_wp_error( $found ) ) {
					$term = $found;
				}
			}
		}

		if ( ! $term ) {
			$terms = get_the_terms( $post->ID, 'product_cat' );

			if ( is_array( $terms ) && $terms ) {
				// Deepest-first would change existing URLs; lowest id is what
				// WooCommerce and Premmerce both settle on.
				usort(
					$terms,
					static function ( $a, $b ) {
						return (int) $a->term_id <=> (int) $b->term_id;
					}
				);

				$term = $terms[0];
			}
		}

		self::$building = false;

		return $term;
	}

	/* ---------------------------------------------------------------------
	 * Reading URLs
	 * ------------------------------------------------------------------ */

	/**
	 * Resolve a rewritten URL back to a query.
	 *
	 * Runs at the end of WP::parse_request(), so the query vars WordPress
	 * worked out are already in place and we only step in when they point at
	 * nothing.
	 *
	 * @param \WP $wp Request object.
	 */
	public static function resolve( $wp ) {
		if ( is_admin() || ! is_object( $wp ) ) {
			return;
		}

		$path = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';

		if ( '' === $path ) {
			return;
		}

		// Anything already pointing at a real object is none of our business.
		if ( self::already_resolved( $wp->query_vars, $path ) ) {
			return;
		}

		$tail  = [];
		$clean = self::strip_tail( $path, $tail );

		if ( '' === $clean ) {
			return;
		}

		$segments = explode( '/', $clean );

		$product = self::match_product( $segments );

		if ( $product ) {
			$wp->query_vars = self::rebuild(
				$wp->query_vars,
				$tail,
				[
					'product'   => $product->post_name,
					'post_type' => 'product',
					'name'      => $product->post_name,
				]
			);

			self::$lookup['product'] = $product;

			return;
		}

		$term = self::match_term( $segments );

		if ( $term ) {
			$wp->query_vars = self::rebuild( $wp->query_vars, $tail, [ 'product_cat' => $term->slug ] );

			self::$lookup['term'] = $term;
		}
	}

	/**
	 * Swap in our own query vars without discarding everyone else's.
	 *
	 * Only the vars that would now contradict us are dropped. Replacing the
	 * whole array — which is what this used to do — silently threw away any
	 * public query var another plugin had registered, and page builders load
	 * the front end with exactly that kind of extra state attached.
	 *
	 * @param array $current Vars WordPress parsed.
	 * @param array $tail    Pagination / feed vars from the path suffix.
	 * @param array $ours    What this request actually resolves to.
	 * @return array
	 */
	private static function rebuild( array $current, array $tail, array $ours ) {
		foreach ( [ 'pagename', 'name', 'post_type', 'attachment', 'category_name', 'p', 'page_id', 'error' ] as $var ) {
			unset( $current[ $var ] );
		}

		return array_merge( $current, $tail, $ours );
	}

	/**
	 * Does the request already point at something real?
	 *
	 * @param array $vars Query vars as WordPress parsed them.
	 * @return bool
	 */
	private static function already_resolved( array $vars, $path = '' ) {
		// A page WordPress matched verbosely, or any archive/search/feed query.
		foreach ( [ 'p', 'page_id', 'cat', 'tag', 'author', 's', 'category_name', 'product_cat', 'product_tag', 'taxonomy' ] as $var ) {
			if ( ! empty( $vars[ $var ] ) ) {
				return true;
			}
		}

		/*
		 * A post type archive with nothing else on it is already an answer —
		 * /shop/ arrives as exactly ['post_type' => 'product']. This used to
		 * fall through, so a product or a category sharing that slug could
		 * take the shop page over and leave the visitor on the blog.
		 */
		if ( ! empty( $vars['post_type'] ) && empty( $vars['name'] ) && empty( $vars['pagename'] ) ) {
			return true;
		}

		// The WooCommerce shop page and the front page are never ours.
		if ( '' !== $path && self::is_reserved_path( $path ) ) {
			return true;
		}

		if ( ! empty( $vars['pagename'] ) && self::by_path( (string) $vars['pagename'], 'page' ) ) {
			return true;
		}

		if ( ! empty( $vars['name'] ) ) {
			$type = ! empty( $vars['post_type'] ) ? $vars['post_type'] : 'post';

			if ( 'product' === $type ) {
				return true;
			}

			if ( is_string( $type ) && self::by_path( (string) $vars['name'], $type ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Split pagination, feed and embed suffixes off the path.
	 *
	 * @param string $path Request path.
	 * @param array  $tail Receives the query vars the suffix implies.
	 * @return string The path without its suffix.
	 */
	private static function strip_tail( $path, array &$tail ) {
		$tail = [];

		if ( preg_match( '#^(.*?)/embed$#', $path, $m ) ) {
			$tail['embed'] = true;
			$path          = $m[1];
		}

		if ( preg_match( '#^(.*?)/page/([0-9]{1,})$#', $path, $m ) ) {
			$tail['paged'] = (int) $m[2];
			$path          = $m[1];
		}

		if ( preg_match( '#^(.*?)/feed(?:/([^/]+))?$#', $path, $m ) ) {
			$tail['feed'] = ! empty( $m[2] ) ? $m[2] : 'feed';
			$path         = $m[1];
		}

		if ( preg_match( '#^(.*?)/comment-page-([0-9]{1,})$#', $path, $m ) ) {
			$tail['cpage'] = (int) $m[2];
			$path          = $m[1];
		}

		return trim( $path, '/' );
	}

	/**
	 * Find the product a path points at.
	 *
	 * Accepts the canonical shape and every legacy one — the /product/ base,
	 * a category path in front of the slug — so an old link never 404s. The
	 * canonical redirect afterwards tidies the URL up.
	 *
	 * @param array $segments Path segments.
	 * @return \WP_Post|null
	 */
	private static function match_product( array $segments ) {
		if ( 'default' === self::get( 'product_mode', 'default' ) || ! $segments ) {
			return null;
		}

		$slug    = (string) end( $segments );
		$prefix  = array_slice( $segments, 0, -1 );
		$product = self::by_path( $slug, 'product' );

		if ( ! $product ) {
			return null;
		}

		if ( ! $prefix ) {
			return $product;
		}

		// The WooCommerce base, with or without a category path behind it.
		$base = self::product_base();

		if ( $base && $prefix[0] === $base ) {
			array_shift( $prefix );
		}

		if ( ! $prefix ) {
			return $product;
		}

		// Otherwise every remaining segment has to be a real product category,
		// so we resolve historic category paths without answering to junk.
		foreach ( $prefix as $segment ) {
			if ( ! self::term_by_slug( $segment ) ) {
				return null;
			}
		}

		return $product;
	}

	/**
	 * Find the product category a path points at.
	 *
	 * @param array $segments Path segments.
	 * @return \WP_Term|null
	 */
	/**
	 * Is the request one of the pages the store cannot do without?
	 *
	 * @return bool
	 */
	private static function is_reserved_path( $path ) {
		/*
		 * The path comes from $wp->request, not from REQUEST_URI: WordPress
		 * has already stripped the install's subdirectory from it, which is
		 * what get_page_uri() returns too. Comparing against REQUEST_URI put
		 * /store/shop/ next to "shop" on a subdirectory install and the shop
		 * page lost its protection.
		 */
		$path = trim( (string) $path, '/' );

		if ( '' === $path ) {
			return true;
		}

		$reserved = [];

		foreach ( [ 'shop', 'cart', 'checkout', 'myaccount', 'terms' ] as $page ) {
			$id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( $page ) : 0;

			if ( $id > 0 ) {
				$post = get_post( $id );

				if ( $post ) {
					$reserved[] = trim( (string) get_page_uri( $post ), '/' );
				}
			}
		}

		$front = (int) get_option( 'page_on_front' );
		$posts = (int) get_option( 'page_for_posts' );

		foreach ( [ $front, $posts ] as $id ) {
			if ( $id > 0 ) {
				$post = get_post( $id );

				if ( $post ) {
					$reserved[] = trim( (string) get_page_uri( $post ), '/' );
				}
			}
		}

		/**
		 * Filter the paths the permalink resolver will not touch.
		 *
		 * @param string[] $reserved Paths, without leading or trailing slashes.
		 */
		$reserved = (array) apply_filters( 'pfh_widgets_reserved_paths', array_filter( $reserved ) );

		foreach ( $reserved as $one ) {
			if ( $path === $one || 0 === strpos( $path . '/', $one . '/' ) ) {
				return true;
			}
		}

		return false;
	}

	private static function match_term( array $segments ) {
		if ( 'default' === self::get( 'category_mode', 'default' ) || ! $segments ) {
			return null;
		}

		$base = self::category_base();

		// Tolerate the WooCommerce base in front of the path.
		if ( $base ) {
			$parts = explode( '/', $base );

			if ( array_slice( $segments, 0, count( $parts ) ) === $parts ) {
				$segments = array_slice( $segments, count( $parts ) );
			}
		}

		if ( ! $segments ) {
			return null;
		}

		$term = self::term_by_slug( (string) end( $segments ) );

		if ( ! $term ) {
			return null;
		}

		// A bare slug is accepted in every mode; the redirect canonicalises it.
		if ( 1 === count( $segments ) ) {
			return $term;
		}

		// A longer path must be this term's real ancestry.
		return self::term_path( $term ) === implode( '/', $segments ) ? $term : null;
	}

	/**
	 * The WooCommerce product base, e.g. "product".
	 *
	 * @return string
	 */
	private static function product_base() {
		$permalinks = function_exists( 'wc_get_permalink_structure' ) ? wc_get_permalink_structure() : [];
		$base       = isset( $permalinks['product_rewrite_slug'] ) ? (string) $permalinks['product_rewrite_slug'] : 'product';
		$base       = trim( $base, '/' );

		// The structure can itself carry a category placeholder.
		$base = str_replace( '%product_cat%', '', $base );

		return trim( $base, '/' );
	}

	/**
	 * The WooCommerce category base, e.g. "product-category".
	 *
	 * @return string
	 */
	private static function category_base() {
		$permalinks = function_exists( 'wc_get_permalink_structure' ) ? wc_get_permalink_structure() : [];
		$base       = isset( $permalinks['category_rewrite_slug'] ) ? (string) $permalinks['category_rewrite_slug'] : 'product-category';

		return trim( (string) $base, '/' );
	}

	/**
	 * Cached get_page_by_path().
	 *
	 * @param string $path Slug or path.
	 * @param string $type Post type.
	 * @return \WP_Post|null
	 */
	private static function by_path( $path, $type ) {
		$key = $type . ':' . $path;

		if ( array_key_exists( $key, self::$lookup ) ) {
			return self::$lookup[ $key ];
		}

		$found = get_page_by_path( $path, OBJECT, $type );
		$found = ( $found && ! is_wp_error( $found ) ) ? $found : null;

		if ( $found && ! in_array( $found->post_status, [ 'publish', 'private' ], true ) ) {
			$found = null;
		}

		self::$lookup[ $key ] = $found;

		return $found;
	}

	/**
	 * Cached product category lookup by slug.
	 *
	 * @param string $slug Term slug.
	 * @return \WP_Term|null
	 */
	private static function term_by_slug( $slug ) {
		$key = 'term:' . $slug;

		if ( array_key_exists( $key, self::$lookup ) ) {
			return self::$lookup[ $key ];
		}

		$term = get_term_by( 'slug', $slug, 'product_cat' );
		$term = ( $term && ! is_wp_error( $term ) ) ? $term : null;

		self::$lookup[ $key ] = $term;

		return $term;
	}

	/* ---------------------------------------------------------------------
	 * Canonical redirect
	 * ------------------------------------------------------------------ */

	/**
	 * Send a legacy URL to the canonical one.
	 *
	 * Runs before redirect_canonical so the two never argue: by the time core
	 * looks, the URL already matches get_permalink().
	 */
	public static function canonical() {
		if ( ! self::get( 'redirect_legacy', true ) || is_admin() ) {
			return;
		}

		if ( is_feed() || is_embed() || ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) ) {
			return;
		}

		// Never redirect a POST, or a request carrying a preview token.
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		// A builder renders the front end in an iframe to edit it; redirecting
		// that navigates the canvas away and the editor dies. Note that
		// resolve() must still run for these — without it the canvas cannot
		// find the product at its rewritten URL in the first place.
		foreach ( [ 'preview', 'p', 'page_id', 'bricks', 'brickspreview', 'elementor-preview', 'vc_editable', 'et_fb', 'fl_builder', 'customize_changeset_uuid' ] as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check.
			if ( isset( $_GET[ $key ] ) ) {
				return;
			}
		}

		$target = '';

		if ( is_singular( 'product' ) ) {
			$target = get_permalink( get_queried_object_id() );
		} elseif ( is_tax( 'product_cat' ) ) {
			$term = get_queried_object();

			if ( $term && ! is_wp_error( $term ) ) {
				$target = get_term_link( $term );
			}
		}

		if ( ! $target || is_wp_error( $target ) ) {
			return;
		}

		$current = self::current_url();
		$target  = self::carry_query( $target );

		if ( self::comparable( $current ) === self::comparable( $target ) ) {
			return;
		}

		// Paged archives and comment pages keep their suffix.
		$paged = (int) get_query_var( 'paged' );

		if ( $paged > 1 ) {
			$target = user_trailingslashit( trailingslashit( $target ) . 'page/' . $paged, 'paged' );
			$target = self::carry_query( $target );

			if ( self::comparable( $current ) === self::comparable( $target ) ) {
				return;
			}
		}

		/*
		 * 302 unless a permanent one is asked for. A browser caches a 301 and
		 * keeps following it long after the plugin that sent it was fixed or
		 * removed, so a wrong one during setup is very hard to take back —
		 * the visitor has to clear their cache before they can even see the
		 * fix. Switch this to permanent once the URL shape is settled.
		 */
		wp_safe_redirect( $target, self::get( 'permanent_redirect', false ) ? 301 : 302 );
		exit;
	}

	/**
	 * Re-attach the current query string to a target URL.
	 *
	 * @param string $target Target URL.
	 * @return string
	 */
	private static function carry_query( $target ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
		if ( empty( $_GET ) ) {
			return $target;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- add_query_arg escapes on output.
		return add_query_arg( array_map( 'rawurlencode', wp_unslash( $_GET ) ), $target );
	}

	/**
	 * The URL actually requested.
	 *
	 * @return string
	 */
	private static function current_url() {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( '' === $host ) {
			return home_url( $uri );
		}

		return ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri;
	}

	/**
	 * Normalise a URL so a trailing slash or a scheme is not seen as a change.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private static function comparable( $url ) {
		$url = (string) $url;
		$url = preg_replace( '#^https?://#', '', $url );

		return rtrim( (string) $url, '/' );
	}

	/* ---------------------------------------------------------------------
	 * Diagnostics
	 * ------------------------------------------------------------------ */

	/**
	 * Product slugs that collide with a page or post slug.
	 *
	 * With the base removed, a product and a page can claim the same URL. The
	 * page wins, so the product would become unreachable. This is the check
	 * that surfaces that before launch rather than after.
	 *
	 * @param int $limit Stop after this many.
	 * @return array<int, array{slug:string, product:string, conflict:string, type:string}>
	 */
	public static function conflicts( $limit = 25 ) {
		global $wpdb;

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return [];
		}

		$limit = max( 1, (int) $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off diagnostic, no cache to keep.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_name AS slug, p.post_title AS product, o.post_title AS conflict, o.post_type AS type
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->posts} o
				    ON o.post_name = p.post_name
				   AND o.post_type IN ('page','post')
				   AND o.post_status = 'publish'
				 WHERE p.post_type = 'product'
				   AND p.post_status = 'publish'
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Real URLs, as they render right now.
	 *
	 * The sample line under each dropdown is a mock-up; this is the actual
	 * output of get_permalink() for products on this store, which is the only
	 * thing that answers "did the setting take effect".
	 */
	public static function render_examples() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() ) {
			return;
		}

		$products = get_posts(
			[
				'post_type'        => 'product',
				'post_status'      => 'publish',
				'numberposts'      => 3,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => false,
			]
		);

		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'number'     => 2,
				'hide_empty' => true,
			]
		);

		if ( ! $products && ( ! $terms || is_wp_error( $terms ) ) ) {
			return;
		}

		echo '<div class="pfh-settings__note">';
		echo '<p><strong>' . esc_html__( 'How these settings resolve right now', 'pfh-widgets' ) . '</strong> ';
		esc_html_e( 'Live output for real content on this store. Save the page to refresh it.', 'pfh-widgets' );
		echo '</p><table><tbody>';

		foreach ( $products as $product ) {
			printf(
				'<tr><th>%1$s</th><td><code>%2$s</code></td></tr>',
				esc_html( get_the_title( $product ) ),
				esc_html( str_replace( untrailingslashit( home_url() ), '', get_permalink( $product ) ) )
			);
		}

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$link = get_term_link( $term );

				if ( is_wp_error( $link ) ) {
					continue;
				}

				printf(
					'<tr><th>%1$s</th><td><code>%2$s</code></td></tr>',
					esc_html( $term->name ),
					esc_html( str_replace( untrailingslashit( home_url() ), '', $link ) )
				);
			}
		}

		echo '</tbody></table></div>';
	}

	/**
	 * The collision panel on the settings screen.
	 *
	 * Shown whether or not anything is wrong: "we checked and it is clean" is
	 * the answer the audit needs, and an empty panel would read as untested.
	 */
	public static function render_conflicts() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() ) {
			return;
		}

		$rows = self::conflicts();

		if ( ! $rows ) {
			echo '<div class="pfh-settings__note pfh-settings__note--good"><p>'
				. esc_html__( 'No slug collisions. Every published product has a URL of its own that no page or post is claiming.', 'pfh-widgets' )
				. '</p></div>';

			return;
		}

		echo '<div class="pfh-settings__note pfh-settings__note--bad">';
		echo '<p><strong>' . esc_html(
			sprintf(
				/* translators: %d: number of conflicts. */
				_n(
					'%d product shares its slug with a page or post.',
					'%d products share their slug with a page or post.',
					count( $rows ),
					'pfh-widgets'
				),
				count( $rows )
			)
		) . '</strong> ';

		esc_html_e( 'With the product base removed both want the same URL, and the page wins — so these products would become unreachable. Rename one side of each pair before going live.', 'pfh-widgets' );
		echo '</p>';

		echo '<table><thead><tr>'
			. '<th>' . esc_html__( 'Slug', 'pfh-widgets' ) . '</th>'
			. '<th>' . esc_html__( 'Product', 'pfh-widgets' ) . '</th>'
			. '<th>' . esc_html__( 'Conflicts with', 'pfh-widgets' ) . '</th>'
			. '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			printf(
				'<tr><td><code>/%1$s/</code></td><td>%2$s</td><td>%3$s <em>(%4$s)</em></td></tr>',
				esc_html( $row['slug'] ),
				esc_html( $row['product'] ),
				esc_html( $row['conflict'] ),
				esc_html( $row['type'] )
			);
		}

		echo '</tbody></table></div>';
	}
}
