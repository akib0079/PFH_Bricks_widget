<?php
/**
 * Asset registration.
 *
 * Handles are registered on wp_enqueue_scripts (and the Bricks builder hooks)
 * but only enqueued from each element's enqueue_scripts(), so pages without
 * the header/footer stay clean.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Assets {

	/**
	 * Whether register() has already run this request.
	 *
	 * @var bool
	 */
	/**
	 * The repository ref the artwork is pulled from.
	 *
	 * A tag rather than a branch on purpose: jsDelivr caches a tag for ever,
	 * and pinning means pushing to main can never change what a live site is
	 * already showing. Raise it when the artwork itself changes.
	 */
	const IMAGE_REF = 'v1.26.0';

	private static $registered = false;

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register' ], 5 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register' ], 5 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'maybe_quickadd' ], 20 );
	}

	/**
	 * Register every handle this plugin can enqueue.
	 */
	public static function register() {
		// An element's enqueue_scripts() can run before wp_enqueue_scripts has
		// fired — a block theme renders its template, and so any shortcode in
		// the content, before wp_head. Registering twice is a no-op, so the
		// enqueue helpers below call this first rather than assume ordering.
		if ( self::$registered ) {
			return;
		}

		self::$registered = true;

		/**
		 * Filter whether the Outfit webfont is pulled from Google Fonts.
		 *
		 * Return false when the font is self-hosted or already loaded by the theme.
		 *
		 * @param bool $load Default true.
		 */
		if ( apply_filters( 'pfh_widgets_load_google_font', true ) ) {
			wp_register_style(
				'pfh-font-outfit',
				'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap',
				[],
				null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts URL is already versioned.
			);
		}

		/*
		 * The font is a dependency of the base layer, not a sibling of it.
		 * Every element style depends on pfh-base, so declaring it here
		 * means the webfont arrives with any of them however they were
		 * enqueued — rather than only when something remembered to call
		 * base() first, which is the kind of ordering that holds until one
		 * new element does it differently.
		 */
		wp_register_style(
			'pfh-base',
			PFH_WIDGETS_URL . 'assets/css/pfh-base.css',
			wp_style_is( 'pfh-font-outfit', 'registered' ) ? [ 'pfh-font-outfit' ] : [],
			PFH_WIDGETS_VERSION
		);

		wp_register_style(
			'pfh-header',
			PFH_WIDGETS_URL . 'assets/css/pfh-header.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_style(
			'pfh-footer',
			PFH_WIDGETS_URL . 'assets/css/pfh-footer.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_script(
			'pfh-header',
			PFH_WIDGETS_URL . 'assets/js/pfh-header.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_localize_script( 'pfh-header', 'pfhWidgets', self::endpoint() );

		wp_register_style(
			'pfh-hero',
			PFH_WIDGETS_URL . 'assets/css/pfh-hero.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_script(
			'pfh-hero',
			PFH_WIDGETS_URL . 'assets/js/pfh-hero.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_register_style(
			'pfh-categories',
			PFH_WIDGETS_URL . 'assets/css/pfh-categories.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_style(
			'pfh-products',
			PFH_WIDGETS_URL . 'assets/css/pfh-products.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_style(
			'pfh-pgrid',
			PFH_WIDGETS_URL . 'assets/css/pfh-pgrid.css',
			[ 'pfh-products' ],
			PFH_WIDGETS_VERSION
		);

		// The archive layers on top of the shared card styles.
		wp_register_style(
			'pfh-archive',
			PFH_WIDGETS_URL . 'assets/css/pfh-archive.css',
			[ 'pfh-products' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_style(
			'pfh-product',
			PFH_WIDGETS_URL . 'assets/css/pfh-product.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_script(
			'pfh-product',
			PFH_WIDGETS_URL . 'assets/js/pfh-product.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_localize_script( 'pfh-product', 'pfhWidgets', self::endpoint() );

		wp_register_style(
			'pfh-product-tabs',
			PFH_WIDGETS_URL . 'assets/css/pfh-product-tabs.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_script(
			'pfh-product-tabs',
			PFH_WIDGETS_URL . 'assets/js/pfh-product-tabs.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_register_script(
			'pfh-archive',
			PFH_WIDGETS_URL . 'assets/js/pfh-archive.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_localize_script(
			'pfh-archive',
			'pfhArchive',
			array_merge(
				[
					'ajaxUrl' => admin_url( 'admin-ajax.php', 'relative' ),
					'nonce'   => wp_create_nonce( PFH_Widgets_Archive::ACTION ),
				],
				PFH_Widgets_Archive::wire()
			)
		);

		// The five shop-template parts share one sheet and one script.
		wp_register_style(
			'pfh-shop',
			PFH_WIDGETS_URL . 'assets/css/pfh-shop.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_script(
			'pfh-shop',
			PFH_WIDGETS_URL . 'assets/js/pfh-shop.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_register_script(
			'pfh-recent',
			PFH_WIDGETS_URL . 'assets/js/pfh-recent.js',
			[ 'pfh-slider' ],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_localize_script( 'pfh-recent', 'pfhRecent', self::endpoint() );

		wp_register_style(
			'pfh-highlight',
			PFH_WIDGETS_URL . 'assets/css/pfh-highlight.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_style(
			'pfh-rating',
			PFH_WIDGETS_URL . 'assets/css/pfh-rating.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_style(
			'pfh-cta',
			PFH_WIDGETS_URL . 'assets/css/pfh-cta.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_style(
			'pfh-features',
			PFH_WIDGETS_URL . 'assets/css/pfh-features.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		// Shared by the hero and both featured bands.
		wp_register_style(
			'pfh-quickadd',
			PFH_WIDGETS_URL . 'assets/css/pfh-quickadd.css',
			[],
			PFH_WIDGETS_VERSION
		);

		wp_register_script(
			'pfh-quickadd',
			PFH_WIDGETS_URL . 'assets/js/pfh-quickadd.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_localize_script( 'pfh-quickadd', 'pfhWidgets', self::endpoint() );

		wp_register_script(
			'pfh-marquee',
			PFH_WIDGETS_URL . 'assets/js/pfh-marquee.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_register_script(
			'pfh-features',
			PFH_WIDGETS_URL . 'assets/js/pfh-features.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_register_style(
			'pfh-reviews',
			PFH_WIDGETS_URL . 'assets/css/pfh-reviews.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_script(
			'pfh-reviews',
			PFH_WIDGETS_URL . 'assets/js/pfh-reviews.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_register_style(
			'pfh-info',
			PFH_WIDGETS_URL . 'assets/css/pfh-info.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		wp_register_style(
			'pfh-featured',
			PFH_WIDGETS_URL . 'assets/css/pfh-featured.css',
			[ 'pfh-base' ],
			PFH_WIDGETS_VERSION
		);

		// One drag-slider engine shared by the category and product sliders.
		wp_register_script(
			'pfh-slider',
			PFH_WIDGETS_URL . 'assets/js/pfh-slider.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);

		wp_register_script(
			'pfh-footer',
			PFH_WIDGETS_URL . 'assets/js/pfh-footer.js',
			[],
			PFH_WIDGETS_VERSION,
			true
		);
	}

	/**
	 * Load the quick-add layer on the whole shop, not only where one of our
	 * elements happens to be.
	 *
	 * The brief is that nothing on the site reloads to add to the cart, which
	 * includes the theme's own loops and the single product page — so this
	 * rides on wp_enqueue_scripts rather than on an element.
	 *
	 * Filter `pfh_widgets_quickadd` to switch it off.
	 */
	public static function maybe_quickadd() {
		if ( is_admin() || ! PFH_Widgets_Helpers::has_woocommerce() ) {
			return;
		}

		/**
		 * Filter whether site-wide AJAX add to cart loads.
		 *
		 * @param bool $load Default true.
		 */
		if ( ! apply_filters( 'pfh_widgets_quickadd', true ) ) {
			return;
		}

		self::quickadd();
	}

	/**
	 * Enqueue the quick-add chooser and its endpoint config.
	 *
	 * The config is attached here rather than to the header, because the
	 * chooser has to work on pages that have no PFH header on them.
	 */
	public static function quickadd() {
		self::register();
		wp_enqueue_style( 'pfh-quickadd' );
		wp_enqueue_script( 'pfh-quickadd' );
	}

	/**
	 * Where the bundled artwork lives.
	 *
	 * The images are fallbacks — a decorative branch, a banner, the shop
	 * placeholder — shown until the real ACF fields are connected. Bundling
	 * them put 426K into a plugin that has to fit through a host's upload
	 * limit, and a release once came back as "Incompatible Archive" for
	 * exactly that reason. So they are served from the project's own
	 * repository over a CDN, and the plugin itself carries none of them.
	 *
	 * Two ways out of that if it ever becomes inconvenient:
	 *
	 *   Drop the file into assets/img/ and it wins — a local copy is always
	 *   preferred, so a site can self-host any or all of them without a
	 *   setting.
	 *
	 *   Or filter `pfh_widgets_image_base` to point somewhere else entirely,
	 *   such as your own media library or CDN.
	 *
	 * @param string $file File name, e.g. pfh-hero-branch.png.
	 * @return string Absolute URL.
	 */
	public static function img( $file ) {
		$file = ltrim( (string) $file, '/' );

		// A local copy always wins, so self-hosting needs no configuration.
		if ( file_exists( PFH_WIDGETS_DIR . 'assets/img/' . $file ) ) {
			return PFH_WIDGETS_URL . 'assets/img/' . $file;
		}

		/**
		 * Filter the base URL the bundled artwork is served from.
		 *
		 * Must end with a slash.
		 *
		 * @param string $base Default: the project repository over jsDelivr.
		 */
		$base = apply_filters(
			'pfh_widgets_image_base',
			'https://cdn.jsdelivr.net/gh/akib0079/PFH_Bricks_widget@' . self::IMAGE_REF . '/pfh-bricks-widgets/assets/img/'
		);

		return trailingslashit( (string) $base ) . $file;
	}

	/**
	 * The AJAX endpoint every script needs.
	 *
	 * Root-relative on purpose. admin_url() builds an absolute URL from the
	 * stored site address, so a shop reachable on any other host — www
	 * against a non-www setting, a staging alias, an IP — posts its filter
	 * requests cross-origin, the browser blocks them, and every filter
	 * quietly degrades to a full page reload. A path is same-origin
	 * whatever host the visitor arrived on.
	 *
	 * @return array{ajaxUrl:string, nonce:string}
	 */
	private static function endpoint() {
		return [
			'ajaxUrl' => admin_url( 'admin-ajax.php', 'relative' ),
			'nonce'   => wp_create_nonce( PFH_Widgets_Ajax::NONCE ),
		];
	}

	/**
	 * Enqueue the shared base layer (font + tokens).
	 */
	public static function base() {
		self::register();

		if ( wp_style_is( 'pfh-font-outfit', 'registered' ) ) {
			wp_enqueue_style( 'pfh-font-outfit' );
		}

		wp_enqueue_style( 'pfh-base' );
	}

	/**
	 * Enqueue everything the header element needs.
	 */
	public static function header() {
		self::base();
		wp_enqueue_style( 'pfh-header' );
		wp_enqueue_script( 'pfh-header' );
	}

	/**
	 * Enqueue everything the hero element needs.
	 */
	public static function hero() {
		self::base();
		wp_enqueue_style( 'pfh-hero' );
		wp_enqueue_script( 'pfh-hero' );
		wp_enqueue_script( 'pfh-marquee' );
	}

	/**
	 * Enqueue everything the category slider needs.
	 */
	public static function categories() {
		self::base();
		wp_enqueue_style( 'pfh-categories' );
		wp_enqueue_script( 'pfh-slider' );
	}

	/**
	 * Enqueue everything the product slider needs.
	 */
	public static function products() {
		self::quickadd();
		self::base();
		wp_enqueue_style( 'pfh-products' );
		wp_enqueue_script( 'pfh-slider' );
	}

	/**
	 * Enqueue everything the product grid needs. It reuses the slider's card
	 * styles, so pfh-products comes along as a dependency.
	 */
	public static function product_grid() {
		self::quickadd();
		self::base();
		wp_enqueue_style( 'pfh-pgrid' );
	}

	/**
	 * Enqueue everything the shop archive needs.
	 */
	public static function archive() {
		self::quickadd();
		self::base();
		wp_enqueue_style( 'pfh-archive' );
		wp_enqueue_script( 'pfh-archive' );
	}

	/**
	 * Enqueue everything the shop template parts need.
	 */
	public static function shop() {
		self::base();
		wp_enqueue_style( 'pfh-shop' );
		wp_enqueue_script( 'pfh-shop' );
	}

	/**
	 * Enqueue what the recently-viewed slider needs on top of the slider.
	 */
	public static function recent() {
		self::register();
		wp_enqueue_script( 'pfh-recent' );
	}

	/**
	 * Enqueue everything the product highlight needs.
	 */
	public static function highlight() {
		self::base();
		wp_enqueue_style( 'pfh-highlight' );
	}

	/**
	 * Enqueue everything the single product needs.
	 */
	public static function product() {
		self::base();
		wp_enqueue_style( 'pfh-product' );
		wp_enqueue_script( 'pfh-product' );
	}

	/**
	 * Enqueue everything the product tabs need.
	 */
	public static function product_tabs() {
		self::base();
		wp_enqueue_style( 'pfh-product-tabs' );
		wp_enqueue_script( 'pfh-product-tabs' );
	}

	/**
	 * Enqueue everything the rating badge needs.
	 */
	public static function rating() {
		self::base();
		wp_enqueue_style( 'pfh-rating' );
	}

	/**
	 * Enqueue everything the closing CTA needs. It is pure CSS.
	 */
	public static function cta() {
		self::base();
		wp_enqueue_style( 'pfh-cta' );
	}

	/**
	 * Enqueue everything the highlighted features grid needs.
	 */
	public static function features() {
		self::base();
		wp_enqueue_style( 'pfh-features' );
		wp_enqueue_script( 'pfh-features' );
	}

	/**
	 * Enqueue everything the review slider needs.
	 */
	public static function reviews() {
		self::base();
		wp_enqueue_style( 'pfh-reviews' );
		wp_enqueue_script( 'pfh-reviews' );
	}

	/**
	 * Enqueue everything the info section needs. The layout is pure CSS.
	 */
	public static function info() {
		self::base();
		wp_enqueue_style( 'pfh-info' );
	}

	/**
	 * Enqueue everything the featured section needs. The marquee is pure CSS,
	 * so there is no script for this one.
	 */
	public static function featured() {
		self::base();
		wp_enqueue_style( 'pfh-featured' );
		wp_enqueue_script( 'pfh-marquee' );
	}

	/**
	 * Enqueue everything the footer element needs.
	 */
	public static function footer() {
		self::base();
		wp_enqueue_style( 'pfh-footer' );
		wp_enqueue_script( 'pfh-footer' );
	}
}
