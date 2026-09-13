<?php
/**
 * Cart drawer rendering + WooCommerce fragment wiring.
 *
 * The drawer body is rendered by static methods so that both the element
 * (first paint) and the AJAX handler (after a quantity change) produce
 * identical markup.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Cart {

	/**
	 * Default drawer copy. Overridden by element settings.
	 *
	 * @return array<string, string>
	 */
	public static function default_labels() {
		return [
			'empty'    => esc_html__( 'Je winkelwagen is nog leeg.', 'pfh-widgets' ),
			'shop'     => esc_html__( 'Verder winkelen', 'pfh-widgets' ),
			'shopUrl'  => '',
			'subtotal' => esc_html__( 'Subtotaal', 'pfh-widgets' ),
			'cart'     => esc_html__( 'Bekijk winkelwagen', 'pfh-widgets' ),
			'checkout' => esc_html__( 'Afrekenen', 'pfh-widgets' ),
			'remove'   => esc_html__( 'Verwijderen', 'pfh-widgets' ),
			'note'     => esc_html__( 'Verzendkosten worden berekend bij het afrekenen.', 'pfh-widgets' ),
		];
	}

	/**
	 * Merge caller supplied labels over the defaults.
	 *
	 * @param array $labels Partial label map.
	 * @return array<string, string>
	 */
	public static function labels( $labels = [] ) {
		$labels = is_array( $labels ) ? $labels : [];

		return array_merge( self::default_labels(), array_filter( $labels, 'strlen' ) );
	}

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_add_to_cart_fragments', [ __CLASS__, 'count_fragment' ] );
	}

	/**
	 * Keep the header badge in sync with WooCommerce's own AJAX add-to-cart.
	 *
	 * @param array $fragments Existing fragments.
	 * @return array
	 */
	public static function count_fragment( $fragments ) {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! WC()->cart ) {
			return $fragments;
		}

		$count = WC()->cart->get_cart_contents_count();

		$fragments['.pfh-actions__badge'] = sprintf(
			'<span class="pfh-actions__badge%s" data-pfh-cart-count>%s</span>',
			$count > 0 ? '' : ' is-empty',
			esc_html( $count )
		);

		return $fragments;
	}

	/**
	 * Current cart item count, safe when WooCommerce is absent.
	 *
	 * @return int
	 */
	public static function count() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! WC()->cart ) {
			return 0;
		}

		return (int) WC()->cart->get_cart_contents_count();
	}

	/**
	 * Render the scrollable list of cart lines.
	 *
	 * @param array $labels Drawer copy.
	 * @return string
	 */
	public static function render_body( $labels = [] ) {
		$labels = self::labels( $labels );

		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! WC()->cart ) {
			return '<div class="pfh-cart__empty"><p>' . esc_html__( 'WooCommerce is niet actief.', 'pfh-widgets' ) . '</p></div>';
		}

		$items = WC()->cart->get_cart();

		if ( empty( $items ) ) {
			$shop_url = $labels['shopUrl'] ? $labels['shopUrl'] : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );

			return '<div class="pfh-cart__empty">'
				. PFH_Widgets_Icons::get( 'cart', 'pfh-cart__empty-icon' )
				. '<p>' . esc_html( $labels['empty'] ) . '</p>'
				. '<a class="pfh-btn pfh-btn--ghost" href="' . esc_url( $shop_url ) . '">' . esc_html( $labels['shop'] ) . '</a>'
				. '</div>';
		}

		ob_start();

		echo '<ul class="pfh-cart__list">';

		foreach ( $items as $key => $item ) {
			$product = isset( $item['data'] ) ? $item['data'] : null;

			if ( ! $product || ! $product->exists() || $item['quantity'] <= 0 ) {
				continue;
			}

			/** This filter is documented in woocommerce/templates/cart/mini-cart.php */
			if ( ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $item, $key ) ) {
				continue;
			}

			$permalink  = $product->is_visible() ? $product->get_permalink( $item ) : '';
			$thumb      = $product->get_image( 'woocommerce_thumbnail', [ 'class' => 'pfh-cart__thumb-img' ] );
			$name       = $product->get_name();
			$price      = WC()->cart->get_product_price( $product );
			$meta       = function_exists( 'wc_get_formatted_cart_item_data' ) ? wc_get_formatted_cart_item_data( $item, true ) : '';
			$max_qty    = $product->get_max_purchase_quantity();
			$sold_alone = $product->is_sold_individually();

			echo '<li class="pfh-cart__item" data-pfh-cart-item="' . esc_attr( $key ) . '">';

			echo '<div class="pfh-cart__thumb">';
			if ( $permalink ) {
				echo '<a href="' . esc_url( $permalink ) . '">' . wp_kses_post( $thumb ) . '</a>';
			} else {
				echo wp_kses_post( $thumb );
			}
			echo '</div>';

			echo '<div class="pfh-cart__info">';
			echo '<div class="pfh-cart__row">';
			echo '<h3 class="pfh-cart__name">';
			if ( $permalink ) {
				echo '<a href="' . esc_url( $permalink ) . '">' . esc_html( $name ) . '</a>';
			} else {
				echo esc_html( $name );
			}
			echo '</h3>';
			echo '<button type="button" class="pfh-cart__remove" data-pfh-cart-remove="' . esc_attr( $key ) . '" aria-label="' . esc_attr( $labels['remove'] ) . '">' . PFH_Widgets_Icons::get( 'trash' ) . '</button>';
			echo '</div>';

			if ( $meta ) {
				echo '<div class="pfh-cart__meta">' . wp_kses_post( $meta ) . '</div>';
			}

			echo '<div class="pfh-cart__row pfh-cart__row--bottom">';

			if ( $sold_alone ) {
				echo '<span class="pfh-cart__qty pfh-cart__qty--static">1</span>';
			} else {
				$disable_plus = ( $max_qty > 0 && $item['quantity'] >= $max_qty ) ? ' disabled' : '';

				echo '<div class="pfh-cart__qty">';
				echo '<button type="button" class="pfh-cart__qty-btn" data-pfh-cart-step="-1" data-key="' . esc_attr( $key ) . '" aria-label="' . esc_attr__( 'Aantal verlagen', 'pfh-widgets' ) . '">' . PFH_Widgets_Icons::get( 'minus' ) . '</button>';
				echo '<span class="pfh-cart__qty-value" data-pfh-cart-qty>' . esc_html( $item['quantity'] ) . '</span>';
				echo '<button type="button" class="pfh-cart__qty-btn" data-pfh-cart-step="1" data-key="' . esc_attr( $key ) . '" aria-label="' . esc_attr__( 'Aantal verhogen', 'pfh-widgets' ) . '"' . $disable_plus . '>' . PFH_Widgets_Icons::get( 'plus' ) . '</button>';
				echo '</div>';
			}

			echo '<span class="pfh-cart__price">' . wp_kses_post( $price ) . '</span>';
			echo '</div>';

			echo '</div>';
			echo '</li>';
		}

		echo '</ul>';

		return (string) ob_get_clean();
	}

	/**
	 * Render the sticky drawer footer (subtotal + buttons).
	 *
	 * @param array $labels Drawer copy.
	 * @return string
	 */
	public static function render_foot( $labels = [] ) {
		$labels = self::labels( $labels );

		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! WC()->cart || WC()->cart->is_empty() ) {
			return '';
		}

		$subtotal     = WC()->cart->get_cart_subtotal();
		$cart_url     = wc_get_cart_url();
		$checkout_url = wc_get_checkout_url();

		ob_start();
		?>
		<div class="pfh-cart__totals">
			<span class="pfh-cart__totals-label"><?php echo esc_html( $labels['subtotal'] ); ?></span>
			<span class="pfh-cart__totals-value"><?php echo wp_kses_post( $subtotal ); ?></span>
		</div>
		<?php if ( $labels['note'] ) : ?>
			<p class="pfh-cart__note"><?php echo esc_html( $labels['note'] ); ?></p>
		<?php endif; ?>
		<div class="pfh-cart__actions">
			<a class="pfh-btn pfh-btn--ghost" href="<?php echo esc_url( $cart_url ); ?>"><?php echo esc_html( $labels['cart'] ); ?></a>
			<a class="pfh-btn pfh-btn--solid" href="<?php echo esc_url( $checkout_url ); ?>"><?php echo esc_html( $labels['checkout'] ); ?></a>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
