<?php
/**
 * A free-shipping bar in FunnelKit's cart drawer.
 *
 * "Nog € 6,18 tot gratis verzending", with a bar that fills as the cart does.
 * The client missed it in the drawer (feedback #1003294). FunnelKit's own
 * reward bar only works with WooCommerce's "Free shipping" method, and this
 * shop ships everything through the DHL plugin, so adding that method would
 * put a second shipping option in the checkout. This bar is a message only:
 * it reads the subtotal FunnelKit already shows and changes nothing about
 * what shipping costs.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Shipbar extends PFH_Settings_Module {

	const OPTION = 'pfh_shipbar';

	public static function init() {
		PFH_Widgets_Settings::register(
			'cart',
			static function () {
				return __( 'Winkelwagen', 'pfh-widgets' );
			},
			__CLASS__
		);

		// After FunnelKit has enqueued its drawer, the same moment the
		// drawer's restyle is added.
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ], 101 );
		add_action( 'wp_footer', [ __CLASS__, 'assets' ], 2 );
	}

	/**
	 * @return array
	 */
	public static function fields() {
		return [
			'shipbar' => [
				'label'  => __( 'Free-shipping bar', 'pfh-widgets' ),
				'desc'   => __( 'A line with a progress bar at the top of the cart drawer. It only shows a message: what shipping costs is still decided by the shipping settings (DHL).', 'pfh-widgets' ),
				'fields' => [
					'enabled'   => [
						'type'     => 'checkbox',
						'label'    => __( 'Bar', 'pfh-widgets' ),
						'cb_label' => __( 'Show the bar in the cart drawer', 'pfh-widgets' ),
						'default'  => true,
					],
					'threshold' => [
						'type'    => 'number',
						'label'   => __( 'Free from', 'pfh-widgets' ),
						'suffix'  => '€',
						'min'     => 1,
						'max'     => 10000,
						'default' => 60,
						'desc'    => __( 'The amount the bar fills up to, as the cart subtotal including VAT.', 'pfh-widgets' ),
					],
					'text_left' => [
						'type'    => 'text',
						'label'   => __( 'Below it', 'pfh-widgets' ),
						'default' => 'Nog %amount% tot gratis verzending',
						'desc'    => __( '%amount% is what is still missing.', 'pfh-widgets' ),
					],
					'text_done' => [
						'type'    => 'text',
						'label'   => __( 'Reached', 'pfh-widgets' ),
						'default' => 'Je bestelling wordt gratis verzonden',
					],
					'note'      => [
						'type'    => 'text',
						'label'   => __( 'Small line under it', 'pfh-widgets' ),
						'default' => 'Nederland vanaf € 60 · België vanaf € 70',
						'desc'    => __( 'Leave empty to leave it out.', 'pfh-widgets' ),
					],
				],
			],
		];
	}

	/**
	 * Only where FunnelKit's drawer is on the page.
	 */
	public static function assets() {
		if ( is_admin() || ! wp_style_is( 'fkcart-style', 'enqueued' ) || wp_script_is( 'pfh-shipbar', 'enqueued' ) ) {
			return;
		}

		if ( ! self::get( 'enabled', true ) ) {
			return;
		}

		wp_enqueue_script( 'pfh-shipbar', PFH_WIDGETS_URL . 'assets/js/pfh-shipbar.js', [], PFH_WIDGETS_VERSION, true );

		wp_add_inline_script(
			'pfh-shipbar',
			'window.pfhShipbar = ' . wp_json_encode( self::config() ) . ';',
			'before'
		);

		// The bar's styles live with the drawer's restyle; without it (the
		// restyle switched off) a few lines keep the bar presentable.
		if ( ! wp_style_is( 'pfh-fkcart', 'enqueued' ) ) {
			wp_register_style( 'pfh-shipbar', false, [], PFH_WIDGETS_VERSION );
			wp_enqueue_style( 'pfh-shipbar' );
			wp_add_inline_style( 'pfh-shipbar', '.pfh-shipbar{padding:12px 20px;font-size:13px}.pfh-shipbar__track{height:6px;margin-top:8px;border-radius:3px;background:#e6ebe4;overflow:hidden}.pfh-shipbar__fill{height:100%;background:#7caeb2}' );
		}
	}

	/**
	 * What the script needs: the threshold, the wording and the shop's
	 * money format, so "€ 53,82" reads as 53.82.
	 *
	 * @return array
	 */
	public static function config() {
		$decimals = function_exists( 'wc_get_price_decimals' ) ? (int) wc_get_price_decimals() : 2;

		return [
			'threshold' => max( 1, (float) self::get( 'threshold', 60 ) ),
			'left'      => (string) self::get( 'text_left', 'Nog %amount% tot gratis verzending' ),
			'done'      => (string) self::get( 'text_done', 'Je bestelling wordt gratis verzonden' ),
			'note'      => (string) self::get( 'note', '' ),
			'decimal'   => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : ',',
			'thousand'  => function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : '.',
			'decimals'  => $decimals,
			'symbol'    => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '€',
			'position'  => (string) get_option( 'woocommerce_currency_pos', 'left_space' ),
		];
	}
}
