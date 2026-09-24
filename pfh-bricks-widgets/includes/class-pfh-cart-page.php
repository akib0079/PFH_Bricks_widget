<?php
/**
 * The cart page: what is in the cart, what it costs, and the way to pay.
 *
 * Rendered here rather than in the element, because the same markup has to
 * come back from AJAX after every change — a quantity, a removal, a coupon —
 * and the element is not loaded on an AJAX request.
 *
 * It is WooCommerce's cart, drawn in the shop's own style. The numbers are
 * WooCommerce's own (subtotal, discounts, fees, VAT, total), every per-item
 * filter WooCommerce's cart template applies is applied here too, and the
 * standard cart hooks still fire where plugins expect them — notices and
 * loyalty messages above the cart, rows in the totals table, express-pay
 * buttons next to the checkout button. Without JavaScript it still works:
 * quantities and coupons post to WooCommerce's own form handler, and
 * removing goes through WooCommerce's own remove link.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Cart_Page {

	const ACTION = 'pfh_cart_page';

	/** What a row's quantity cell starts as, to see whether a plugin replaced it. */
	const QTY_MARK = '<!--pfh-cartp-qty-->';

	/**
	 * The words and choices the element hands over. Also the defaults the
	 * element's controls show.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return [
			'eyebrow'        => '',
			'title'          => 'Jouw <em>winkelwagen</em>',
			'titleTag'       => 'h1',
			'countOne'       => '%count% artikel',
			'countMany'      => '%count% artikelen',
			'continue'       => 'Verder winkelen',
			'continueUrl'    => '',

			'shipBar'        => true,
			'shipFrom'       => 60.0,
			'shipCountries'  => "BE: 70",
			'shipTodo'       => 'Nog %amount% tot gratis verzending',
			'shipDone'       => 'Je bestelling wordt gratis verzonden',

			'colProduct'     => 'Product',
			'colQty'         => 'Aantal',
			'colTotal'       => 'Totaal',
			'remove'         => 'Verwijderen',
			'removed'        => '%name% is verwijderd.',
			'undo'           => 'Ongedaan maken',
			'backorder'      => 'Wordt nabesteld',

			'summaryTitle'   => 'Overzicht',
			'subtotal'       => 'Subtotaal',
			'shipping'       => 'Verzending',
			'shippingLater'  => 'Berekend bij afrekenen',
			'free'           => 'Gratis',
			'total'          => 'Totaal',
			'couponToggle'   => 'Kortingscode toevoegen',
			'couponField'    => 'Kortingscode',
			'couponApply'    => 'Toepassen',
			'couponRemove'   => 'Verwijderen',
			'checkout'       => 'Veilig afrekenen',
			'reasons'        => [
				[ 'icon' => 'usp-secure', 'text' => 'Veilig betalen met iDEAL, Bancontact en meer' ],
				[ 'icon' => 'clock', 'text' => 'Voor 15:00 besteld, dezelfde dag verzonden' ],
				[ 'icon' => 'medal', 'text' => 'Spaar punten bij elke bestelling' ],
			],

			'emptyTitle'     => 'Je winkelwagen is nog <em>leeg</em>',
			'emptyText'      => 'Ontdek onze Griekse honing, olijfolie en meer — met liefde geselecteerd.',
			'emptyButton'    => 'Naar de winkel',
			'emptyUrl'       => '',

			'preview'        => false,
		];
	}

	/** Icons a reassurance line can carry. */
	const ICONS = [ 'usp-secure', 'usp-delivery', 'usp-natural', 'clock', 'medal', 'review', 'box-check', 'check', 'lock', 'tag', 'phone', 'mail' ];

	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION, [ __CLASS__, 'ajax' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ __CLASS__, 'ajax' ] );
	}

	/**
	 * Settings from the element, cleaned and filled with defaults.
	 *
	 * @param array $in Raw.
	 * @return array<string, mixed>
	 */
	public static function options( array $in ) {
		$out = self::defaults();

		foreach ( $out as $key => $default ) {
			if ( ! array_key_exists( $key, $in ) || null === $in[ $key ] ) {
				continue;
			}

			$value = $in[ $key ];

			if ( is_bool( $default ) ) {
				$out[ $key ] = (bool) $value;
			} elseif ( is_float( $default ) ) {
				$out[ $key ] = max( 0.0, (float) str_replace( ',', '.', (string) $value ) );
			} elseif ( is_array( $default ) ) {
				$out[ $key ] = self::clean_reasons( $value );
			} else {
				$out[ $key ] = trim( self::strip4( (string) $value ) );
			}
		}

		if ( ! in_array( $out['titleTag'], [ 'h1', 'h2', 'div' ], true ) ) {
			$out['titleTag'] = 'h1';
		}

		return $out;
	}

	/**
	 * Characters outside the Basic Multilingual Plane (emoji) do not survive
	 * a utf8 database column, and are dropped rather than breaking a save.
	 *
	 * @param string $value Text.
	 * @return string
	 */
	private static function strip4( $value ) {
		return (string) preg_replace( '/[\x{10000}-\x{10FFFF}]/u', '', (string) $value );
	}

	/**
	 * @param mixed $rows Repeater rows.
	 * @return array<int, array{icon:string, text:string}>
	 */
	private static function clean_reasons( $rows ) {
		$out = [];

		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$text = isset( $row['text'] ) ? trim( wp_strip_all_tags( (string) $row['text'] ) ) : '';

			if ( '' === $text ) {
				continue;
			}

			$icon  = isset( $row['icon'] ) ? (string) $row['icon'] : 'check';
			$out[] = [
				'icon' => in_array( $icon, self::ICONS, true ) ? $icon : 'check',
				'text' => trim( self::strip4( $text ) ),
			];
		}

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * The request that follows every change
	 * ------------------------------------------------------------------ */

	/**
	 * Sign the element's words into the page, so the AJAX call re-renders
	 * with exactly them and a visitor cannot inject markup of their own.
	 *
	 * @param array $opts Options.
	 * @return string
	 */
	public static function sign( array $opts ) {
		unset( $opts['preview'] );

		$json = (string) wp_json_encode( $opts );

		return base64_encode( $json ) . '.' . hash_hmac( 'sha256', $json, wp_salt( 'nonce' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- transport, not obfuscation.
	}

	/**
	 * @param string $token Signed options.
	 * @return array|null
	 */
	public static function unsign( $token ) {
		$parts = explode( '.', (string) $token, 2 );

		if ( 2 !== count( $parts ) ) {
			return null;
		}

		$json = base64_decode( $parts[0], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- transport, not obfuscation.

		if ( false === $json || ! hash_equals( hash_hmac( 'sha256', $json, wp_salt( 'nonce' ) ), $parts[1] ) ) {
			return null;
		}

		$data = json_decode( $json, true );

		return is_array( $data ) ? $data : null;
	}

	public static function ajax() {
		if ( ! check_ajax_referer( self::ACTION, 'nonce', false ) ) {
			// Usually a session that changed under the page (a login in
			// another tab). A fresh page has a fresh nonce.
			wp_send_json_error( [ 'reload' => true ], 403 );
		}

		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( [ 'reload' => true ], 400 );
		}

		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$signed  = isset( $_POST['ctx'] ) ? self::unsign( sanitize_text_field( wp_unslash( $_POST['ctx'] ) ) ) : null;
		$opts    = self::options( is_array( $signed ) ? $signed : [] );
		$command = isset( $_POST['command'] ) ? sanitize_key( wp_unslash( $_POST['command'] ) ) : 'refresh';
		$key     = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$qty     = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 0;
		$code    = isset( $_POST['code'] ) ? wc_format_coupon_code( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) : '';
		// phpcs:enable

		$removed = self::apply( $command, $key, $qty, $code );

		WC()->cart->calculate_totals();
		WC()->cart->maybe_set_cart_cookies();

		wp_send_json_success(
			[
				'html'  => self::inner( $opts, $removed ),
				'count' => (int) WC()->cart->get_cart_contents_count(),
				'hash'  => (string) WC()->cart->get_cart_hash(),
				'empty' => WC()->cart->is_empty(),
			]
		);
	}

	/**
	 * Carry out one change to the cart.
	 *
	 * @param string $command set | remove | restore | coupon | uncoupon | refresh.
	 * @param string $key     Cart item key.
	 * @param float  $qty     New quantity.
	 * @param string $code    Coupon code.
	 * @return array{key:string, name:string}|null The line just removed, for the undo bar.
	 */
	public static function apply( $command, $key, $qty, $code ) {
		$cart = WC()->cart;
		$item = '' !== $key ? $cart->get_cart_item( $key ) : [];

		switch ( $command ) {
			case 'set':
				if ( ! $item ) {
					return null;
				}

				if ( $qty <= 0 ) {
					return self::remove( $key, $item );
				}

				$product = $item['data'];

				if ( $product->is_sold_individually() ) {
					$qty = 1;
				}

				$max = $product->get_max_purchase_quantity();

				if ( $max > 0 && $qty > $max ) {
					$qty = $max;
				}

				// The same check WooCommerce's own cart form runs, so a plugin
				// that limits quantities is asked here too.
				if ( apply_filters( 'woocommerce_update_cart_validation', true, $key, $item, $qty ) ) {
					$cart->set_quantity( $key, $qty, false );
				}

				return null;

			case 'remove':
				return $item ? self::remove( $key, $item ) : null;

			case 'restore':
				$cart->restore_cart_item( $key );

				return null;

			case 'coupon':
				if ( '' === $code ) {
					wc_add_notice( __( 'Please enter a coupon code.', 'woocommerce' ), 'error' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- WooCommerce's own string.
				} else {
					$cart->apply_coupon( $code );
				}

				return null;

			case 'uncoupon':
				if ( '' !== $code && $cart->remove_coupon( $code ) ) {
					wc_add_notice( __( 'Coupon has been removed.', 'woocommerce' ) ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- WooCommerce's own string.
				}

				return null;
		}

		return null;
	}

	/**
	 * @param string $key  Cart item key.
	 * @param array  $item Cart item.
	 * @return array{key:string, name:string}|null
	 */
	private static function remove( $key, array $item ) {
		$name = isset( $item['data'] ) && $item['data'] ? $item['data']->get_name() : '';

		if ( ! WC()->cart->remove_cart_item( $key ) ) {
			return null;
		}

		return [
			'key'  => $key,
			'name' => $name,
		];
	}

	/* ---------------------------------------------------------------------
	 * Drawing it
	 * ------------------------------------------------------------------ */

	/**
	 * Everything inside the element's root: what the AJAX call replaces.
	 *
	 * @param array      $opts    Options.
	 * @param array|null $removed The line just removed, if any.
	 * @return string
	 */
	public static function inner( array $opts, $removed = null ) {
		ob_start();

		$rows = self::rows( $opts );

		echo '<div class="pfh-cartp__notices" data-pfh-cartp-notices>';

		if ( empty( $opts['preview'] ) ) {
			self::notices( $rows ? 'woocommerce_before_cart' : 'woocommerce_cart_is_empty' );
		}

		if ( $removed && '' !== $removed['name'] ) {
			printf(
				'<div class="pfh-cartp__undo" role="status"><span>%1$s</span><button type="button" class="pfh-cartp__undo-btn" data-pfh-cartp-restore="%2$s">%3$s</button></div>',
				esc_html( str_replace( '%name%', '“' . $removed['name'] . '”', $opts['removed'] ) ),
				esc_attr( $removed['key'] ),
				esc_html( $opts['undo'] )
			);
		}

		echo '</div>';

		if ( ! $rows ) {
			self::render_empty( $opts );

			return (string) ob_get_clean();
		}

		self::render_head( $opts, $rows );
		self::render_ship_bar( $opts, $rows );

		echo '<div class="pfh-cartp__layout">';
		echo '<div class="pfh-cartp__main">';
		self::render_items( $opts, $rows );
		echo '</div>';

		echo '<aside class="pfh-cartp__summary" aria-labelledby="pfh-cartp-summary-title">';
		self::render_summary( $opts, $rows );
		echo '</aside>';
		echo '</div>';

		if ( empty( $opts['preview'] ) ) {
			do_action( 'woocommerce_after_cart' );
		}

		return (string) ob_get_clean();
	}

	/**
	 * WooCommerce prints its notices on this hook; if something unhooked
	 * that, they are printed anyway, or a coupon error would vanish silently.
	 *
	 * @param string $hook woocommerce_before_cart, or the empty-cart one.
	 */
	private static function notices( $hook ) {
		if ( ! function_exists( 'wc_print_notices' ) ) {
			return;
		}

		// The empty state below says it better than WooCommerce's
		// "Your cart is currently empty." line, which rides the same hook.
		$message = has_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message' );

		if ( false !== $message ) {
			remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', $message );
		}

		do_action( $hook );

		if ( false !== $message ) {
			add_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', $message );
		}

		if ( wc_notice_count() > 0 ) {
			wc_print_notices();
		}
	}

	/**
	 * The lines to draw: the real cart, or in the builder with an empty
	 * cart a few products standing in, so the layout can be styled.
	 *
	 * @param array $opts Options.
	 * @return array<int, array<string, mixed>>
	 */
	public static function rows( array $opts ) {
		if ( ! empty( $opts['preview'] ) ) {
			return self::preview_rows();
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return [];
		}

		$rows = [];

		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$product = isset( $item['data'] ) ? $item['data'] : null;

			if ( ! $product || ! $product->exists() || $item['quantity'] <= 0 ) {
				continue;
			}

			if ( ! apply_filters( 'woocommerce_cart_item_visible', true, $item, $key ) ) {
				continue;
			}

			$rows[] = [
				'key'     => (string) $key,
				'item'    => $item,
				'product' => $product,
				'qty'     => (float) $item['quantity'],
				'preview' => false,
			];
		}

		return $rows;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function preview_rows() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return [];
		}

		$rows = [];

		foreach ( wc_get_products( [ 'status' => 'publish', 'limit' => 3, 'type' => [ 'simple', 'variable' ], 'orderby' => 'date', 'order' => 'DESC' ] ) as $i => $product ) {
			$rows[] = [
				'key'     => 'preview-' . $i,
				'item'    => [ 'quantity' => 1, 'data' => $product, 'product_id' => $product->get_id() ],
				'product' => $product,
				'qty'     => 1.0,
				'preview' => true,
			];
		}

		return $rows;
	}

	/**
	 * @param array $opts Options.
	 * @param array $rows Lines.
	 */
	private static function render_head( array $opts, array $rows ) {
		$count = empty( $opts['preview'] ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : count( $rows );
		$tag   = $opts['titleTag'];
		$words = str_replace( '%count%', number_format_i18n( $count ), 1 === $count ? $opts['countOne'] : $opts['countMany'] );
		$shop  = '' !== $opts['continueUrl'] ? $opts['continueUrl'] : self::shop_url();

		echo '<header class="pfh-cartp__head">';
		echo '<div class="pfh-cartp__heading">';

		if ( '' !== $opts['eyebrow'] ) {
			echo '<p class="pfh-cartp__eyebrow">' . esc_html( $opts['eyebrow'] ) . '</p>';
		}

		printf(
			'<%1$s class="pfh-cartp__title">%2$s</%1$s>',
			tag_escape( $tag ),
			wp_kses( $opts['title'], [ 'em' => [], 'i' => [], 'br' => [] ] )
		);

		echo '<p class="pfh-cartp__count" data-pfh-cartp-count>' . esc_html( $words ) . '</p>';
		echo '</div>';

		if ( '' !== $opts['continue'] ) {
			printf(
				'<a class="pfh-cartp__continue" href="%1$s">%2$s<span>%3$s</span></a>',
				esc_url( $shop ),
				PFH_Widgets_Icons::get( 'arrow', 'pfh-cartp__continue-icon' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				esc_html( $opts['continue'] )
			);
		}

		echo '</header>';
	}

	/**
	 * How far the cart is from free shipping, or null when there is no bar
	 * to draw (switched off, nothing to ship, no threshold).
	 *
	 * The amount comes from WooCommerce's own free-shipping method for the
	 * shopper's zone when the store uses it, counted the way that method
	 * counts. Otherwise from the element: a default, and an amount per
	 * country for the shopper's country.
	 *
	 * @param array $opts Options.
	 * @param array $rows Lines.
	 * @return array{threshold:float, amount:float, left:float, done:bool, pct:float}|null
	 */
	public static function free_shipping( array $opts, array $rows ) {
		if ( empty( $opts['shipBar'] ) ) {
			return null;
		}

		$preview = ! empty( $opts['preview'] );
		$cart    = $preview ? null : WC()->cart;

		if ( $cart && ! $cart->needs_shipping() ) {
			return null;
		}

		$ignore    = false;
		$threshold = $cart ? self::wc_threshold( $ignore ) : null;

		if ( null === $threshold ) {
			$threshold = self::country_threshold( $opts );
		}

		if ( $threshold <= 0 ) {
			return null;
		}

		if ( $cart ) {
			$amount = (float) $cart->get_displayed_subtotal();

			if ( $cart->display_prices_including_tax() ) {
				$amount -= (float) $cart->get_discount_tax();
			}

			if ( ! $ignore ) {
				$amount -= (float) $cart->get_discount_total();
			}
		} else {
			$amount = 0.0;

			foreach ( $rows as $row ) {
				$amount += (float) wc_get_price_to_display( $row['product'] ) * $row['qty'];
			}
		}

		$amount = round( $amount, wc_get_price_decimals() );
		$left   = max( 0, round( $threshold - $amount, wc_get_price_decimals() ) );

		return [
			'threshold' => (float) $threshold,
			'amount'    => $amount,
			'left'      => $left,
			'done'      => $left <= 0,
			'pct'       => min( 100, max( 0, round( $amount / $threshold * 100, 1 ) ) ),
		];
	}

	/**
	 * WooCommerce's free-shipping minimum for the zone the cart ships to.
	 *
	 * @param bool $ignore Set to whether that method ignores discounts.
	 * @return float|null
	 */
	private static function wc_threshold( &$ignore ) {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return null;
		}

		$packages = WC()->cart->get_shipping_packages();

		if ( ! $packages ) {
			return null;
		}

		$zone = WC_Shipping_Zones::get_zone_matching_package( reset( $packages ) );

		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			if ( 'free_shipping' !== $method->id || ! in_array( $method->requires, [ 'min_amount', 'either' ], true ) ) {
				continue;
			}

			$min = (float) $method->min_amount;

			if ( $min > 0 ) {
				$ignore = 'yes' === $method->ignore_discounts;

				return $min;
			}
		}

		return null;
	}

	/**
	 * The element's own amount for the shopper's country.
	 *
	 * @param array $opts Options.
	 * @return float
	 */
	public static function country_threshold( array $opts ) {
		$country = '';

		if ( function_exists( 'WC' ) && WC()->customer ) {
			$country = WC()->customer->get_shipping_country();

			if ( '' === $country ) {
				$country = WC()->customer->get_billing_country();
			}
		}

		if ( '' === $country && function_exists( 'WC' ) && WC()->countries ) {
			$country = WC()->countries->get_base_country();
		}

		foreach ( preg_split( '/[\r\n,;]+/', (string) $opts['shipCountries'] ) as $line ) {
			if ( preg_match( '/^\s*([A-Za-z]{2})\s*[:=]\s*([0-9]+(?:[.,][0-9]+)?)\s*$/', $line, $m ) && strtoupper( $m[1] ) === strtoupper( $country ) ) {
				return (float) str_replace( ',', '.', $m[2] );
			}
		}

		return (float) $opts['shipFrom'];
	}

	/**
	 * @param array $opts Options.
	 * @param array $rows Lines.
	 */
	private static function render_ship_bar( array $opts, array $rows ) {
		$state = self::free_shipping( $opts, $rows );

		if ( ! $state ) {
			return;
		}

		if ( $state['done'] ) {
			$text = esc_html( $opts['shipDone'] );
		} else {
			$parts = explode( '%amount%', $opts['shipTodo'], 2 );
			$text  = esc_html( $parts[0] ) . '<strong>' . wp_strip_all_tags( wc_price( $state['left'] ) ) . '</strong>' . ( isset( $parts[1] ) ? esc_html( $parts[1] ) : '' );
		}

		printf(
			'<div class="pfh-cartp__ship%1$s"><span class="pfh-cartp__ship-icon" aria-hidden="true">%2$s</span><div class="pfh-cartp__ship-copy"><p class="pfh-cartp__ship-text">%3$s</p><span class="pfh-cartp__ship-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="%4$s" aria-label="%5$s"><span class="pfh-cartp__ship-fill" style="width:%4$s%%"></span></span></div></div>',
			$state['done'] ? ' is-done' : '',
			PFH_Widgets_Icons::get( $state['done'] ? 'check' : 'usp-delivery' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			$text, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			esc_attr( (string) $state['pct'] ),
			esc_attr( wp_strip_all_tags( $opts['shipDone'] ) )
		);
	}

	/**
	 * @param array $opts Options.
	 * @param array $rows Lines.
	 */
	private static function render_items( array $opts, array $rows ) {
		$preview = ! empty( $opts['preview'] );

		if ( ! $preview ) {
			do_action( 'woocommerce_before_cart_table' );
		}

		// A real form to WooCommerce's own handler, so the cart still updates
		// with JavaScript switched off. With it on, every change is AJAX.
		printf( '<form class="pfh-cartp__form" action="%s" method="post" data-pfh-cartp-form>', esc_url( wc_get_cart_url() ) );

		printf(
			'<div class="pfh-cartp__labels" aria-hidden="true"><span>%1$s</span><span>%2$s</span><span>%3$s</span></div>',
			esc_html( $opts['colProduct'] ),
			esc_html( $opts['colQty'] ),
			esc_html( $opts['colTotal'] )
		);

		echo '<ul class="pfh-cartp__items">';

		foreach ( $rows as $row ) {
			self::render_row( $opts, $row );
		}

		echo '</ul>';

		if ( ! $preview ) {
			echo '<noscript><button type="submit" class="pfh-cartp__update" name="update_cart" value="1">' . esc_html__( 'Update cart', 'woocommerce' ) . '</button></noscript>'; // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- WooCommerce's own string.
			wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' );
		}

		echo '</form>';

		if ( ! $preview ) {
			do_action( 'woocommerce_after_cart_table' );
		}
	}

	/**
	 * One line of the cart, through the same filters WooCommerce's own cart
	 * template uses, so a plugin that renames, reprices or locks a line
	 * (bundles, gifts, subscriptions) is respected here.
	 *
	 * @param array $opts Options.
	 * @param array $row  Line.
	 */
	private static function render_row( array $opts, array $row ) {
		$key     = $row['key'];
		$item    = $row['item'];
		$product = $row['product'];
		$qty     = $row['qty'];
		$preview = $row['preview'];

		$link  = $product->is_visible() ? $product->get_permalink( $item ) : '';
		$link  = $preview ? $link : (string) apply_filters( 'woocommerce_cart_item_permalink', $link, $item, $key );
		$thumb = $product->get_image( 'woocommerce_thumbnail', [ 'class' => 'pfh-cartp__img', 'loading' => 'lazy' ] );
		$thumb = $preview ? $thumb : apply_filters( 'woocommerce_cart_item_thumbnail', $thumb, $item, $key );
		$name  = $link ? sprintf( '<a href="%s">%s</a>', esc_url( $link ), esc_html( $product->get_name() ) ) : esc_html( $product->get_name() );
		$name  = $preview ? $name : apply_filters( 'woocommerce_cart_item_name', $name, $item, $key );
		$class = $preview ? '' : (string) apply_filters( 'woocommerce_cart_item_class', 'cart_item', $item, $key );

		printf( '<li class="pfh-cartp__item %1$s" data-pfh-cartp-item="%2$s">', esc_attr( $class ), esc_attr( $key ) );

		// Picture.
		echo '<div class="pfh-cartp__thumb">';
		echo $link ? '<a href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true">' . wp_kses_post( $thumb ) . '</a>' : wp_kses_post( $thumb );
		echo '</div>';

		// What it is.
		echo '<div class="pfh-cartp__info">';
		echo '<h3 class="pfh-cartp__name">' . wp_kses_post( $name ) . '</h3>';

		if ( ! $preview ) {
			do_action( 'woocommerce_after_cart_item_name', $item, $key );

			$meta = wc_get_formatted_cart_item_data( $item );

			if ( '' !== trim( $meta ) ) {
				echo '<div class="pfh-cartp__meta">' . wp_kses_post( $meta ) . '</div>';
			}
		}

		echo '<div class="pfh-cartp__unit">' . self::kses( self::unit_price( $item, $key, $preview ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd.

		if ( ! $preview && $product->backorders_require_notification() && $product->is_on_backorder( $qty ) ) {
			echo '<p class="pfh-cartp__note">' . esc_html( $opts['backorder'] ) . '</p>';
		}

		self::render_remove( $opts, $key, $preview );
		echo '</div>';

		// How many.
		echo '<div class="pfh-cartp__qty-cell">';
		self::render_qty( $opts, $row );
		echo '</div>';

		// What they cost together.
		$line = WC()->cart ? WC()->cart->get_product_subtotal( $product, $qty ) : wc_price( (float) wc_get_price_to_display( $product ) * $qty );
		$line = $preview ? $line : apply_filters( 'woocommerce_cart_item_subtotal', $line, $item, $key );

		echo '<div class="pfh-cartp__line"><span class="pfh-cartp__line-label">' . esc_html( $opts['colTotal'] ) . '</span>' . self::kses( $line ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd.

		echo '</li>';
	}

	/**
	 * The price of one, struck through beside the regular price when it is
	 * on sale — unless a plugin has priced the line its own way, which is
	 * then shown exactly as it says.
	 *
	 * @param array  $item    Cart item.
	 * @param string $key     Cart item key.
	 * @param bool   $preview Builder stand-in.
	 * @return string
	 */
	private static function unit_price( array $item, $key, $preview ) {
		$product = $item['data'];
		$plain   = WC()->cart ? WC()->cart->get_product_price( $product ) : wc_price( (float) wc_get_price_to_display( $product ) );
		$shown   = $preview ? $plain : (string) apply_filters( 'woocommerce_cart_item_price', $plain, $item, $key );

		if ( $shown !== $plain || ! $product->is_on_sale() ) {
			return $shown;
		}

		$regular = (float) wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] );
		$now     = (float) wc_get_price_to_display( $product );

		if ( $regular <= $now ) {
			return $shown;
		}

		return wc_format_sale_price( $regular, $now );
	}

	/**
	 * A link to WooCommerce's own remove URL — works without JavaScript —
	 * that the script turns into an in-place removal. A plugin can take the
	 * link away (a free gift that cannot be removed) through WooCommerce's
	 * filter, and then there is no button.
	 *
	 * @param array  $opts    Options.
	 * @param string $key     Cart item key.
	 * @param bool   $preview Builder stand-in.
	 */
	private static function render_remove( array $opts, $key, $preview ) {
		$url = $preview ? '#' : wc_get_cart_remove_url( $key );

		if ( ! $preview ) {
			$item    = WC()->cart->get_cart_item( $key );
			$product = $item ? $item['data'] : null;
			$default = sprintf(
				'<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
				esc_url( $url ),
				esc_attr( $opts['remove'] ),
				esc_attr( $product ? $product->get_id() : '' ),
				esc_attr( $product ? $product->get_sku() : '' )
			);

			if ( '' === trim( (string) apply_filters( 'woocommerce_cart_item_remove_link', $default, $key ) ) ) {
				return;
			}
		}

		printf(
			'<a class="pfh-cartp__remove" href="%1$s" data-pfh-cartp-remove="%2$s">%3$s<span>%4$s</span></a>',
			esc_url( $url ),
			esc_attr( $key ),
			PFH_Widgets_Icons::get( 'trash' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			esc_html( $opts['remove'] )
		);
	}

	/**
	 * The stepper — or, when a plugin replaced WooCommerce's quantity field
	 * for this line (a bundled item that follows its parent, a gift fixed at
	 * one), what the plugin put there instead.
	 *
	 * @param array $opts Options.
	 * @param array $row  Line.
	 */
	private static function render_qty( array $opts, array $row ) {
		$key     = $row['key'];
		$product = $row['product'];
		$qty     = $row['qty'];

		$stepper = self::stepper( $key, $product, $qty );

		if ( $row['preview'] ) {
			echo $stepper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped below.

			return;
		}

		$filtered = (string) apply_filters( 'woocommerce_cart_item_quantity', self::QTY_MARK, $key, $row['item'] );

		if ( false !== strpos( $filtered, self::QTY_MARK ) ) {
			// Left alone, or only wrapped: the stepper, inside whatever was added.
			$parts = explode( self::QTY_MARK, $filtered, 2 );

			echo wp_kses_post( $parts[0] ) . $stepper . wp_kses_post( $parts[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the stepper is built escaped.

			return;
		}

		echo '<div class="pfh-cartp__qty is-fixed">' . wp_kses_post( $filtered ) . '</div>';
	}

	/**
	 * @param string     $key     Cart item key.
	 * @param WC_Product $product Product.
	 * @param float      $qty     Quantity.
	 * @return string
	 */
	private static function stepper( $key, $product, $qty ) {
		$name = $product->get_name();

		if ( $product->is_sold_individually() ) {
			return sprintf(
				'<div class="pfh-cartp__qty is-fixed"><span>1</span><input type="hidden" name="cart[%s][qty]" value="1" /></div>',
				esc_attr( $key )
			);
		}

		$max  = (int) $product->get_max_purchase_quantity();
		$more = $max > 0 && $qty >= $max;

		return sprintf(
			'<div class="pfh-cartp__qty" data-pfh-cartp-qty>'
				. '<button type="button" class="pfh-cartp__step" data-pfh-cartp-step="-1" aria-label="%1$s">%2$s</button>'
				. '<input class="pfh-cartp__qty-input" type="number" inputmode="numeric" name="cart[%3$s][qty]" value="%4$s" min="0" %5$s step="1" aria-label="%6$s" data-pfh-cartp-input="%3$s" />'
				. '<button type="button" class="pfh-cartp__step" data-pfh-cartp-step="1" aria-label="%7$s"%8$s>%9$s</button>'
			. '</div>',
			esc_attr( sprintf( /* translators: %s: product name. */ __( 'Eén minder van %s', 'pfh-widgets' ), $name ) ),
			PFH_Widgets_Icons::get( 'minus' ),
			esc_attr( $key ),
			esc_attr( wc_stock_amount( $qty ) ),
			$max > 0 ? 'max="' . esc_attr( $max ) . '"' : '',
			esc_attr( sprintf( /* translators: %s: product name. */ __( 'Aantal van %s', 'pfh-widgets' ), $name ) ),
			esc_attr( sprintf( /* translators: %s: product name. */ __( 'Eén meer van %s', 'pfh-widgets' ), $name ) ),
			$more ? ' disabled' : '',
			PFH_Widgets_Icons::get( 'plus' )
		);
	}

	/**
	 * @param array $opts Options.
	 * @param array $rows Lines.
	 */
	private static function render_summary( array $opts, array $rows ) {
		$preview = ! empty( $opts['preview'] ) || ! WC()->cart;

		echo '<div class="pfh-cartp__card">';

		if ( ! $preview ) {
			do_action( 'woocommerce_before_cart_totals' );
		}

		echo '<h2 class="pfh-cartp__summary-title" id="pfh-cartp-summary-title">' . esc_html( $opts['summaryTitle'] ) . '</h2>';

		// A table, because that is what the totals hooks print into: a plugin
		// adding a line adds a <tr>, which anywhere else would be dropped.
		echo '<table class="pfh-cartp__totals"><tbody>';

		if ( $preview ) {
			$sum = 0.0;

			foreach ( $rows as $row ) {
				$sum += (float) wc_get_price_to_display( $row['product'] ) * $row['qty'];
			}

			self::total_row( 'subtotal', $opts['subtotal'], wc_price( $sum ) );
			self::total_row( 'shipping', $opts['shipping'], '<span class="pfh-cartp__later">' . esc_html( $opts['shippingLater'] ) . '</span>' );
			self::total_row( 'order-total', $opts['total'], '<strong>' . wc_price( $sum ) . '</strong>' );
		} else {
			self::render_totals( $opts );
		}

		echo '</tbody></table>';

		self::render_coupon( $opts, $preview );
		self::render_checkout( $opts, $preview );

		if ( ! $preview ) {
			do_action( 'woocommerce_after_cart_totals' );
		}

		echo '</div>';

		if ( $opts['reasons'] ) {
			echo '<ul class="pfh-cartp__reasons">';

			foreach ( $opts['reasons'] as $reason ) {
				printf(
					'<li><span class="pfh-cartp__reason-icon" aria-hidden="true">%1$s</span><span>%2$s</span></li>',
					PFH_Widgets_Icons::get( $reason['icon'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
					esc_html( $reason['text'] )
				);
			}

			echo '</ul>';
		}
	}

	/**
	 * WooCommerce's totals, in WooCommerce's order and with its own helpers,
	 * so VAT, fees and discounts read exactly as they will at checkout.
	 *
	 * @param array $opts Options.
	 */
	private static function render_totals( array $opts ) {
		$cart = WC()->cart;

		// WooCommerce's totals helpers print rather than return.
		ob_start();
		wc_cart_totals_subtotal_html();
		self::total_row( 'subtotal', $opts['subtotal'], (string) ob_get_clean() );

		foreach ( $cart->get_coupons() as $code => $coupon ) {
			$amount = $cart->get_coupon_discount_amount( $coupon->get_code(), $cart->display_cart_ex_tax );
			$value  = '-' . wc_price( $amount );

			if ( $coupon->get_free_shipping() && empty( $amount ) ) {
				$value = esc_html( $opts['free'] );
			}

			$value = (string) apply_filters( 'woocommerce_coupon_discount_amount_html', $value, $coupon );
			$undo  = sprintf(
				' <button type="button" class="pfh-cartp__uncoupon" data-pfh-cartp-uncoupon="%1$s" aria-label="%2$s">%3$s</button>',
				esc_attr( $code ),
				esc_attr( $opts['couponRemove'] . ': ' . $code ),
				PFH_Widgets_Icons::get( 'close' )
			);

			self::total_row( 'coupon coupon-' . sanitize_html_class( $code ), wc_cart_totals_coupon_label( $coupon, false ), $value, $undo );
		}

		if ( $cart->needs_shipping() ) {
			self::total_row( 'shipping', $opts['shipping'], self::shipping_value( $opts ) );
		}

		foreach ( $cart->get_fees() as $fee ) {
			ob_start();
			wc_cart_totals_fee_html( $fee );
			self::total_row( 'fee', $fee->name, (string) ob_get_clean() );
		}

		if ( wc_tax_enabled() && ! $cart->display_prices_including_tax() ) {
			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( $cart->get_tax_totals() as $code => $tax ) {
					self::total_row( 'tax-rate tax-rate-' . sanitize_html_class( $code ), $tax->label, $tax->formatted_amount );
				}
			} else {
				ob_start();
				wc_cart_totals_taxes_total_html();
				self::total_row( 'tax-total', WC()->countries->tax_or_vat(), (string) ob_get_clean() );
			}
		}

		do_action( 'woocommerce_cart_totals_before_order_total' );

		ob_start();
		wc_cart_totals_order_total_html();
		self::total_row( 'order-total', $opts['total'], (string) ob_get_clean() );

		do_action( 'woocommerce_cart_totals_after_order_total' );
	}

	/**
	 * What shipping costs as things stand: the chosen rate, "Gratis" at
	 * zero, or "calculated at checkout" before there is an address to price.
	 *
	 * @param array $opts Options.
	 * @return string
	 */
	private static function shipping_value( array $opts ) {
		$later = '<span class="pfh-cartp__later">' . esc_html( $opts['shippingLater'] ) . '</span>';
		$cart  = WC()->cart;

		if ( ! $cart->show_shipping() ) {
			return $later;
		}

		$packages = WC()->shipping()->get_packages();
		$chosen   = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods', [] ) : [];
		$cost     = 0.0;
		$found    = false;

		foreach ( $packages as $i => $package ) {
			$rates = isset( $package['rates'] ) ? $package['rates'] : [];

			if ( ! $rates ) {
				continue;
			}

			$id   = isset( $chosen[ $i ], $rates[ $chosen[ $i ] ] ) ? $chosen[ $i ] : key( $rates );
			$rate = $rates[ $id ];

			$cost += (float) $rate->get_cost();

			if ( $cart->display_prices_including_tax() ) {
				$cost += (float) array_sum( (array) $rate->get_taxes() );
			}

			$found = true;
		}

		if ( ! $found ) {
			return $later;
		}

		return $cost > 0 ? wc_price( $cost ) : '<span class="pfh-cartp__free">' . esc_html( $opts['free'] ) . '</span>';
	}

	/**
	 * Price markup, as WooCommerce writes it. The post allowlist would strip
	 * its <bdi> (which keeps "€ 12,50" in order in a right-to-left context)
	 * and its translate="no" (which stops browser translation rewriting the
	 * amount), so those two are added to it.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function kses( $html ) {
		static $allowed = null;

		if ( null === $allowed ) {
			$allowed = wp_kses_allowed_html( 'post' );

			$allowed['bdi'] = [ 'class' => true, 'dir' => true ];

			foreach ( [ 'span', 'small', 'strong', 'bdi', 'del', 'ins' ] as $tag ) {
				$allowed[ $tag ]              = isset( $allowed[ $tag ] ) ? $allowed[ $tag ] : [];
				$allowed[ $tag ]['translate'] = true;
				$allowed[ $tag ]['dir']       = true;
				$allowed[ $tag ]['class']     = true;
				$allowed[ $tag ]['aria-hidden'] = true;
			}
		}

		return wp_kses( (string) $html, $allowed );
	}

	/**
	 * @param string $class CSS class for the row.
	 * @param string $label Label.
	 * @param string $value Value HTML.
	 * @param string $tail  Our own escaped markup after the value (an icon
	 *                      button, which kses would strip the SVG from).
	 */
	private static function total_row( $class, $label, $value, $tail = '' ) {
		printf(
			'<tr class="pfh-cartp__row %1$s"><th scope="row">%2$s</th><td>%3$s%4$s</td></tr>',
			esc_attr( $class ),
			esc_html( wp_strip_all_tags( $label ) ),
			self::kses( $value ),
			$tail // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped by the caller.
		);
	}

	/**
	 * @param array $opts    Options.
	 * @param bool  $preview Builder stand-in.
	 */
	private static function render_coupon( array $opts, $preview ) {
		if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
			return;
		}

		echo '<details class="pfh-cartp__coupon" data-pfh-cartp-coupon>';
		echo '<summary>' . PFH_Widgets_Icons::get( 'tag' ) . '<span>' . esc_html( $opts['couponToggle'] ) . '</span>' . PFH_Widgets_Icons::get( 'chevron', 'pfh-cartp__coupon-chevron' ) . '</summary>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.

		printf(
			'<form class="pfh-cartp__coupon-form" action="%1$s" method="post" data-pfh-cartp-coupon-form><label class="pfh-sr-only" for="pfh-cartp-coupon-code">%2$s</label><input id="pfh-cartp-coupon-code" type="text" name="coupon_code" autocomplete="off" placeholder="%2$s" /><button type="submit" name="apply_coupon" value="1">%3$s</button>',
			esc_url( wc_get_cart_url() ),
			esc_attr( $opts['couponField'] ),
			esc_html( $opts['couponApply'] )
		);

		if ( ! $preview ) {
			wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' );
		}

		echo '</form></details>';
	}

	/**
	 * Our button, then whatever else WooCommerce's checkout-button hook
	 * carries — express payment buttons — without its own plain button.
	 *
	 * @param array $opts    Options.
	 * @param bool  $preview Builder stand-in.
	 */
	private static function render_checkout( array $opts, $preview ) {
		printf(
			'<a class="pfh-cartp__checkout" href="%1$s">%2$s<span>%3$s</span></a>',
			esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' ) ),
			PFH_Widgets_Icons::get( 'lock' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			esc_html( $opts['checkout'] )
		);

		if ( $preview ) {
			return;
		}

		$priority = has_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout' );

		if ( false !== $priority ) {
			remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', $priority );
		}

		ob_start();
		do_action( 'woocommerce_proceed_to_checkout' );
		$extra = trim( (string) ob_get_clean() );

		if ( false !== $priority ) {
			add_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', $priority );
		}

		if ( '' !== $extra ) {
			echo '<div class="pfh-cartp__express wc-proceed-to-checkout">' . $extra . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- third-party payment markup, printed as WooCommerce would.
		}
	}

	/**
	 * @param array $opts Options.
	 */
	private static function render_empty( array $opts ) {
		$url = '' !== $opts['emptyUrl'] ? $opts['emptyUrl'] : self::shop_url();

		echo '<div class="pfh-cartp__empty cart-empty">';
		echo '<span class="pfh-cartp__empty-icon" aria-hidden="true">' . PFH_Widgets_Icons::get( 'cart' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		printf(
			'<%1$s class="pfh-cartp__empty-title">%2$s</%1$s>',
			tag_escape( $opts['titleTag'] ),
			wp_kses( $opts['emptyTitle'], [ 'em' => [], 'i' => [], 'br' => [] ] )
		);

		if ( '' !== $opts['emptyText'] ) {
			echo '<p class="pfh-cartp__empty-text">' . esc_html( $opts['emptyText'] ) . '</p>';
		}

		if ( '' !== $opts['emptyButton'] ) {
			printf( '<a class="pfh-cartp__empty-btn" href="%1$s">%2$s</a>', esc_url( $url ), esc_html( $opts['emptyButton'] ) );
		}

		echo '</div>';
	}

	/**
	 * @return string
	 */
	private static function shop_url() {
		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';

		return $url ? $url : home_url( '/' );
	}

	/**
	 * Before drawing on a page view: the checks WooCommerce's own cart page
	 * runs (stock, coupons still valid), and fresh totals.
	 */
	public static function prepare() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );

		do_action( 'woocommerce_check_cart_items' );
		WC()->cart->calculate_totals();
	}

	/**
	 * The page this element is on is the cart. WooCommerce's classic cart
	 * script is then in the way: it listens for every add-to-cart on the
	 * page, looks for its own cart form to refresh, finds none, and reloads
	 * the page. This element does that job itself, so the script is dropped
	 * from pages that carry it.
	 */
	public static function claim() {
		if ( has_action( 'wp_footer', [ __CLASS__, 'drop_wc_cart_script' ] ) ) {
			return;
		}

		add_action( 'wp_footer', [ __CLASS__, 'drop_wc_cart_script' ], 1 );
	}

	public static function drop_wc_cart_script() {
		wp_dequeue_script( 'wc-cart' );
	}
}
