<?php
/**
 * Plugin Name:       Products For Home – Bricks Widgets
 * Plugin URI:        https://productsforhome.nl/
 * Description:       Custom Bricks Builder elements for the Products For Home redesign, plus the store services that replace four third-party plugins: cookie consent with real tag blocking, a WooCommerce permalink manager, WebwinkelKeur reviews with a sticky trust badge, and PDF invoices and packing slips. Ships a full shop/collection template — AJAX-filtered product archive with a slide-in filter panel and pagination, shop header, collection description, notice band, counter row and FAQ — plus fully dynamic Header (mega menu, search popup, cart drawer), Hero Slider, Category Slider, Product Slider, Product Grid, two Featured Sections, an Info Section, a Rating Badge, a Highlighted Features grid, a WebwinkelKeur Review Slider, an overlapping Call To Action and Footer elements.
 * Version:           1.39.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            AVIX Digital Agency
 * License:           GPL-2.0-or-later
 * Text Domain:       pfh-widgets
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'PFH_WIDGETS_VERSION', '1.39.0' );
define( 'PFH_WIDGETS_FILE', __FILE__ );
define( 'PFH_WIDGETS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PFH_WIDGETS_URL', plugin_dir_url( __FILE__ ) );

require_once PFH_WIDGETS_DIR . 'includes/class-pfh-helpers.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-icons.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-cart.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-ajax.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-quickadd.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-reviews.php';
require_once PFH_WIDGETS_DIR . 'includes/trait-pfh-element-defaults.php';
require_once PFH_WIDGETS_DIR . 'includes/trait-pfh-design-revision.php';
require_once PFH_WIDGETS_DIR . 'includes/trait-pfh-product-card.php';
require_once PFH_WIDGETS_DIR . 'includes/trait-pfh-product-price.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-archive.php';

// Store services. The settings framework has to load before the modules that
// extend it, and the PDF writer before the documents that drive it.
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-settings.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-diagnose.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-save-guard.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-product-fields.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-consent.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-permalinks.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-badge.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-instagram.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-diagnostics.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-pdf.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-documents.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-assets.php';
require_once PFH_WIDGETS_DIR . 'includes/class-pfh-plugin.php';

PFH_Widgets_Plugin::instance();
