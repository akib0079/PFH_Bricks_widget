<?php
/**
 * A read-only "what is this plugin doing to my site?" report.
 *
 * Added because a routing problem was reported that could not be reproduced
 * on a clean install. Rather than guess from a screenshot, this prints what
 * the plugin is actually hooked into and what WordPress makes of a given URL,
 * on the site where the problem is. It changes nothing.
 *
 * Visit: /wp-admin/admin.php?page=pfh-widgets&pfh_diagnostics=1
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Diagnostics {

	public static function init() {
		add_action( 'admin_notices', [ __CLASS__, 'maybe_render' ] );
	}

	public static function maybe_render() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, capability checked.
		if ( ! isset( $_GET['pfh_diagnostics'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="notice notice-info"><h2>' . esc_html__( 'PFH Widgets — diagnostics', 'pfh-widgets' ) . '</h2><pre style="white-space:pre-wrap;font-size:12px;line-height:1.6">';
		echo esc_html( self::report() );
		echo '</pre></div>';
	}

	/**
	 * @return string
	 */
	public static function report() {
		$out = [];

		$out[] = 'Plugin version : ' . PFH_WIDGETS_VERSION;
		$out[] = 'WordPress      : ' . get_bloginfo( 'version' );
		$out[] = 'WooCommerce    : ' . ( defined( 'WC_VERSION' ) ? WC_VERSION : 'not active' );
		$out[] = '';

		$out[] = '── Does this plugin touch routing? ──';
		$out[] = 'No. It has no permalink manager; WooCommerce handles every URL.';
		$out[] = '';

		$out[] = '── The shop page ──';

		if ( function_exists( 'wc_get_page_id' ) ) {
			$shop = wc_get_page_id( 'shop' );
			$out[] = 'Shop page id             : ' . $shop;
			$out[] = 'Shop page permalink      : ' . ( $shop > 0 ? get_permalink( $shop ) : 'no shop page set' );
			$out[] = 'Shop page slug           : ' . ( $shop > 0 ? get_post_field( 'post_name', $shop ) : '-' );
		}

		$out[] = 'Front page shows         : ' . get_option( 'show_on_front' );
		$out[] = 'Front page id            : ' . get_option( 'page_on_front' ) . ( (int) get_option( 'page_on_front' ) > 0 ? ' (' . get_the_title( (int) get_option( 'page_on_front' ) ) . ')' : '' );
		$out[] = 'Posts page id            : ' . get_option( 'page_for_posts' );
		$out[] = 'Permalink structure      : ' . get_option( 'permalink_structure' );
		$out[] = '';

		$out[] = '── Rewrite rules WordPress holds ──';
		$rules = get_option( 'rewrite_rules' );
		$rules = is_array( $rules ) ? $rules : [];
		$out[] = 'Total rules              : ' . count( $rules );

		$archive = array_filter( $rules, function ( $v ) { return false !== strpos( $v, 'post_type=product' ); } );
		$out[]   = 'Product archive rules    : ' . count( $archive ) . ( $archive ? '' : '   <-- none: /shop/ cannot resolve, re-save Settings > Permalinks' );

		foreach ( array_slice( $archive, 0, 4, true ) as $pattern => $target ) {
			$out[] = '    ' . $pattern . '  =>  ' . $target;
		}

		$out[] = '';
		$out[] = '── Other things this plugin switches on ──';

		if ( class_exists( 'PFH_Widgets_Badge' ) ) {
			$out[] = 'Sticky review badge      : ' . var_export( PFH_Widgets_Badge::get( 'enabled', false ), true );
		}

		return implode( "\n", $out );
	}
}
