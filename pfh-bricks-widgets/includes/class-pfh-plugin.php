<?php
/**
 * Plugin bootstrap: registers the Bricks element category and the elements.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Plugin {

	/**
	 * Bricks element category slug.
	 */
	const CATEGORY = 'products-for-home';

	/**
	 * Singleton instance.
	 *
	 * @var PFH_Widgets_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Element files keyed by element name => class name.
	 *
	 * @var array<string, array{file:string, class:string}>
	 */
	private $elements = [];

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->elements = [
			'pfh-header' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-header.php',
				'class' => 'PFH_Element_Header',
			],
			'pfh-footer' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-footer.php',
				'class' => 'PFH_Element_Footer',
			],
			'pfh-hero'   => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-hero.php',
				'class' => 'PFH_Element_Hero',
			],
			'pfh-categories' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-categories.php',
				'class' => 'PFH_Element_Categories',
			],
			'pfh-products'   => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-products.php',
				'class' => 'PFH_Element_Products',
			],
			'pfh-shophead' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-shophead.php',
				'class' => 'PFH_Element_Shophead',
			],
			'pfh-shopdesc' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-shopdesc.php',
				'class' => 'PFH_Element_Shopdesc',
			],
			'pfh-notice' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-notice.php',
				'class' => 'PFH_Element_Notice',
			],
			'pfh-counter' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-counter.php',
				'class' => 'PFH_Element_Counter',
			],
			'pfh-faq' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-faq.php',
				'class' => 'PFH_Element_Faq',
			],
			'pfh-archive' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-archive.php',
				'class' => 'PFH_Element_Archive',
			],
			'pfh-product-grid' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-product-grid.php',
				'class' => 'PFH_Element_Product_Grid',
			],
			'pfh-info'       => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-info.php',
				'class' => 'PFH_Element_Info',
			],
			'pfh-rating'     => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-rating.php',
				'class' => 'PFH_Element_Rating',
			],
			'pfh-recent'     => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-recent.php',
				'class' => 'PFH_Element_Recent',
			],
			'pfh-highlight'  => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-highlight.php',
				'class' => 'PFH_Element_Highlight',
			],
			'pfh-product'    => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-product.php',
				'class' => 'PFH_Element_Product',
			],
			'pfh-product-tabs' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-product-tabs.php',
				'class' => 'PFH_Element_Product_Tabs',
			],
			'pfh-product-related' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-product-related.php',
				'class' => 'PFH_Element_Product_Related',
			],
			'pfh-product-usp' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-product-usp.php',
				'class' => 'PFH_Element_Product_Usp',
			],
			'pfh-post' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-post.php',
				'class' => 'PFH_Element_Post',
			],
			'pfh-blog' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-blog.php',
				'class' => 'PFH_Element_Blog',
			],
			'pfh-policy' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-policy.php',
				'class' => 'PFH_Element_Policy',
			],
			// The five below are that element with the shop's own words in
			// them; each file requires the parent, so load order is its own
			// problem and not this list's.
			'pfh-terms' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-terms.php',
				'class' => 'PFH_Element_Terms',
			],
			'pfh-shipping' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-shipping.php',
				'class' => 'PFH_Element_Shipping',
			],
			'pfh-returns' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-returns.php',
				'class' => 'PFH_Element_Returns',
			],
			'pfh-complaints' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-complaints.php',
				'class' => 'PFH_Element_Complaints',
			],
			'pfh-privacy' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-privacy.php',
				'class' => 'PFH_Element_Privacy',
			],
			'pfh-about' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-about.php',
				'class' => 'PFH_Element_About',
			],
			'pfh-contact' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-contact.php',
				'class' => 'PFH_Element_Contact',
			],
			'pfh-account' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-account.php',
				'class' => 'PFH_Element_Account',
			],
			'pfh-bottomcart' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-bottomcart.php',
				'class' => 'PFH_Element_Bottomcart',
			],
			'pfh-instagram' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-instagram.php',
				'class' => 'PFH_Element_Instagram',
			],
			'pfh-cta'        => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-cta.php',
				'class' => 'PFH_Element_Cta',
			],
			'pfh-features'   => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-features.php',
				'class' => 'PFH_Element_Features',
			],
			'pfh-reviews'    => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-reviews.php',
				'class' => 'PFH_Element_Reviews',
			],
			'pfh-featured'   => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-featured.php',
				'class' => 'PFH_Element_Featured',
			],
			// Subclass of the above; its file requires the parent, so load order
			// here does not matter.
			'pfh-featured-olive' => [
				'file'  => PFH_WIDGETS_DIR . 'elements/class-pfh-element-featured-olive.php',
				'class' => 'PFH_Element_Featured_Olive',
			],
		];

		add_action( 'init', [ $this, 'register_elements' ], 11 );
		add_filter( 'bricks/builder/i18n', [ $this, 'register_category' ] );
		add_action( 'admin_notices', [ $this, 'maybe_missing_bricks_notice' ] );

		add_action( 'init', [ $this, 'schedule_review_refresh' ] );

		// Keep the cached category list honest.
		foreach ( [ 'created_product_cat', 'edited_product_cat', 'delete_product_cat' ] as $hook ) {
			add_action( $hook, [ 'PFH_Widgets_Helpers', 'flush_product_cats' ] );
		}

		PFH_Widgets_Assets::init();
		PFH_Widgets_Ajax::init();
		PFH_Widgets_Cart::init();
		PFH_Widgets_Reviews::init();
		PFH_Widgets_Quickadd::init();
		PFH_Widgets_Archive::init();

		/*
		 * The recently-viewed slider answers its own AJAX request, and its
		 * element file is not loaded on admin-ajax because Bricks only
		 * registers elements on the front end. So the action is registered
		 * here and the class is loaded when the action actually fires.
		 *
		 * Loading it here instead is what took a site down: Bricks is a
		 * theme, themes load after plugins, and an element extending
		 * \Bricks\Element cannot even be parsed at plugins_loaded. The
		 * result was "Class Bricks\Element not found" on every request,
		 * wp-admin included — a white screen with no way back in.
		 */

		add_action( 'wp_ajax_pfh_recent', [ __CLASS__, 'recent_ajax' ] );
		add_action( 'wp_ajax_nopriv_pfh_recent', [ __CLASS__, 'recent_ajax' ] );

		// Bricks only loads an element file when it registers the element, and
		// an AJAX request registers nothing. Pull it in on demand instead.
		add_filter(
			'pfh_archive_ajax',
			static function ( $payload, $id, $raw ) {
				// The element extends \Bricks\Element, so including it without
				// Bricks would be a fatal rather than a graceful no-op.
				if ( ! class_exists( '\Bricks\Element' ) ) {
					return $payload;
				}

				if ( ! class_exists( 'PFH_Element_Archive' ) ) {
					require_once PFH_WIDGETS_DIR . 'elements/class-pfh-element-archive.php';
				}

				return PFH_Element_Archive::ajax_render( $payload, $id, $raw );
			},
			10,
			3
		);

		// Store services wait for plugins_loaded. WordPress includes plugin
		// files alphabetically, so pfh-bricks-widgets is loaded BEFORE
		// woocommerce — deciding what to hook at this point would always see
		// WooCommerce missing and silently register nothing.
		add_action( 'plugins_loaded', [ __CLASS__, 'boot_services' ], 20 );

		register_activation_hook( PFH_WIDGETS_FILE, [ __CLASS__, 'activate' ] );
		register_deactivation_hook( PFH_WIDGETS_FILE, [ __CLASS__, 'deactivate' ] );
	}

	/**
	 * Start the store services.
	 *
	 * Runs on plugins_loaded so every dependency is present: the modules that
	 * need WooCommerce check for it here, and by now it is loaded.
	 *
	 * Settings first, because each module registers its own tab on it.
	 */
	public static function boot_services() {
		PFH_Widgets_Settings::init();
		PFH_Widgets_Diagnose::boot();
		PFH_Widgets_Save_Guard::init();
		PFH_Widgets_Product_Fields::init();
		PFH_Widgets_Consent::init();
		PFH_Widgets_Permalinks::init();
		PFH_Widgets_Badge::init();
		PFH_Widgets_Instagram::init();
		PFH_Widgets_Account::init();
		PFH_Widgets_Blog::init();
		PFH_Widgets_Collection::init();
		PFH_Widgets_Recent::init();
		PFH_Widgets_Documents::init();
		PFH_Widgets_Diagnostics::init();
	}

	/**
	 * Render one deferred recently-viewed slider.
	 *
	 * The element is required here rather than at boot, because by the time
	 * an AJAX action runs the theme has loaded and \Bricks\Element exists.
	 */
	public static function recent_ajax() {
		if ( ! class_exists( '\Bricks\Element' ) ) {
			wp_send_json_error( [ 'message' => __( 'This needs the Bricks theme.', 'pfh-widgets' ) ], 500 );
		}

		if ( ! class_exists( 'PFH_Element_Recent' ) ) {
			require_once PFH_WIDGETS_DIR . 'elements/class-pfh-element-products.php';
			require_once PFH_WIDGETS_DIR . 'elements/class-pfh-element-recent.php';
		}

		PFH_Element_Recent::ajax();
	}

	/**
	 * On activation: rebuild rewrite rules so the permalink manager's URLs
	 * resolve on the first request rather than after a manual save.
	 */
	public static function activate() {
		flush_rewrite_rules( false );
	}

	/**
	 * On deactivation: rebuild them again, so WooCommerce's own bases come
	 * back cleanly instead of leaving the site 404ing products.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( PFH_Widgets_Reviews::CRON );
		wp_clear_scheduled_hook( PFH_Widgets_Instagram::CRON );
		flush_rewrite_rules( false );
	}

	/**
	 * Keep the WebwinkelKeur cache warm out of band.
	 *
	 * Without this the first visitor after the transient expires pays for the
	 * remote call; the daily event drops it so the refetch happens on a cheap
	 * request instead.
	 */
	public function schedule_review_refresh() {
		if ( ! wp_next_scheduled( PFH_Widgets_Reviews::CRON ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', PFH_Widgets_Reviews::CRON );
		}
	}

	/**
	 * Register the custom elements with Bricks.
	 */
	public function register_elements() {
		if ( ! class_exists( '\Bricks\Elements' ) ) {
			return;
		}

		foreach ( $this->elements as $name => $element ) {
			if ( ! file_exists( $element['file'] ) ) {
				continue;
			}

			\Bricks\Elements::register_element( $element['file'], $name, $element['class'] );
		}
	}

	/**
	 * Add our own panel category so the elements are easy to find.
	 *
	 * @param array $i18n Bricks builder translation strings.
	 * @return array
	 */
	public function register_category( $i18n ) {
		$i18n[ self::CATEGORY ] = esc_html__( 'Products For Home', 'pfh-widgets' );

		return $i18n;
	}

	/**
	 * Tell the admin when Bricks is not available.
	 */
	public function maybe_missing_bricks_notice() {
		if ( class_exists( '\Bricks\Elements' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'Products For Home – Bricks Widgets requires the Bricks theme to be active.', 'pfh-widgets' )
		);
	}
}
