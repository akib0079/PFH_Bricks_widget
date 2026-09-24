<?php
/**
 * Keeping the shop's own address labels on the checkout.
 *
 * A checkout can name its address fields itself — FunnelKit's field editor
 * does, and so do WooCommerce's own filters. The page is served with those
 * names, and then WooCommerce's address script renames every field from its
 * per-country table the moment it sets the country, so "Plaats" became
 * "Town / City" before the shopper saw it.
 *
 * The fix is a few lines of browser script that run just before WooCommerce's
 * and put the shop's names back after each change. It is attached to that
 * script rather than enqueued on its own, so it is only ever on a page that
 * has the problem, and it always runs first.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Checkout_Labels {

	const HANDLE = 'wc-address-i18n';

	public static function init() {
		// After WooCommerce registers its scripts on the default priority.
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'attach' ], 100 );
	}

	public static function attach() {
		if ( ! wp_script_is( self::HANDLE, 'registered' ) || ! self::is_checkout() ) {
			return;
		}

		$script = self::script();

		if ( '' !== $script ) {
			wp_add_inline_script( self::HANDLE, $script, 'before' );
		}
	}

	/**
	 * WooCommerce's checkout, or a FunnelKit one (which does not always tell
	 * WooCommerce it is a checkout). Not the thank-you page: no form there.
	 */
	private static function is_checkout() {
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
			return false;
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}

		return is_singular( 'wfacp_checkout' );
	}

	/**
	 * The script itself, read from the plugin so it is the same file the
	 * checks run over. Kept inline so it cannot load after WooCommerce's.
	 */
	public static function script() {
		$file = PFH_WIDGETS_DIR . 'assets/js/pfh-checkout-labels.js';

		if ( ! is_readable( $file ) ) {
			return '';
		}

		return trim( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local plugin file.
	}
}
