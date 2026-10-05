<?php
/**
 * Site-wide AJAX add to cart, with a variant chooser.
 *
 * WooCommerce only ships AJAX add-to-cart for simple products, and only on
 * loops it renders itself: a variable product falls back to a link to its
 * page, and a theme's own buttons reload. This adds one endpoint that handles
 * both, plus a second that describes a variable product so the chooser can be
 * built without loading the product page.
 *
 * Nothing here prints a notice — the drawer opening is the confirmation, so
 * WooCommerce's own "added to cart" message is cleared before it can queue up
 * for the next page view.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Quickadd {

	public static function init() {
		add_action( 'wp_ajax_pfh_add', [ __CLASS__, 'add' ] );
		add_action( 'wp_ajax_nopriv_pfh_add', [ __CLASS__, 'add' ] );

		add_action( 'wp_ajax_pfh_variations', [ __CLASS__, 'variations' ] );
		add_action( 'wp_ajax_nopriv_pfh_variations', [ __CLASS__, 'variations' ] );
	}

	/**
	 * Add a product — simple, or a resolved variation — to the cart.
	 */
	public static function add() {
		check_ajax_referer( PFH_Widgets_Ajax::NONCE, 'nonce' );

		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( [ 'message' => __( 'De winkel is op dit moment niet beschikbaar.', 'pfh-widgets' ) ] );
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? (float) wp_unslash( $_POST['quantity'] ) : 1;
		$quantity     = $quantity > 0 ? $quantity : 1;

		$attributes = [];

		if ( isset( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ) {
			foreach ( wp_unslash( $_POST['attributes'] ) as $name => $value ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$attributes[ sanitize_text_field( (string) $name ) ] = sanitize_text_field( (string) $value );
			}
		}

		$product = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product ) {
			wp_send_json_error( [ 'message' => __( 'Dit product is niet gevonden.', 'pfh-widgets' ) ] );
		}

		/*
		 * A variable product must arrive with a variation. Resolving it here
		 * as well as in the browser means a stale chooser cannot put the
		 * wrong thing in the cart.
		 */
		if ( $product->is_type( 'variable' ) && ! $variation_id ) {
			$variation_id = self::match_variation( $product, $attributes );

			if ( ! $variation_id ) {
				wp_send_json_error(
					[
						'message' => __( 'Kies een optie voor elk veld.', 'pfh-widgets' ),
						'needs'   => 'variation',
					]
				);
			}
		}

		$attributes = self::variation_attributes( $variation_id, $attributes );

		if ( $variation_id && ! self::complete( $attributes ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Kies een optie voor elk veld.', 'pfh-widgets' ),
					'needs'   => 'variation',
				]
			);
		}

		$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes );

		if ( ! $added ) {
			$notices = function_exists( 'wc_get_notices' ) ? wc_get_notices( 'error' ) : [];
			$message = '';

			foreach ( (array) $notices as $notice ) {
				$text = is_array( $notice ) && isset( $notice['notice'] ) ? $notice['notice'] : $notice;
				$message = wp_strip_all_tags( (string) $text );
				break;
			}

			if ( function_exists( 'wc_clear_notices' ) ) {
				wc_clear_notices();
			}

			wp_send_json_error(
				[ 'message' => $message ? $message : __( 'Dit kon niet aan je winkelwagen worden toegevoegd.', 'pfh-widgets' ) ]
			);
		}

		// The drawer is the confirmation; a queued notice would surface on the
		// next page view with no context.
		if ( function_exists( 'wc_clear_notices' ) ) {
			wc_clear_notices();
		}

		$item = $variation_id ? wc_get_product( $variation_id ) : $product;

		wp_send_json_success(
			[
				'count' => WC()->cart->get_cart_contents_count(),
				'name'  => $item ? $item->get_name() : $product->get_name(),
				'total' => WC()->cart->get_cart_total(),
			]
		);
	}

	/**
	 * The attributes to add a variation to the cart with.
	 *
	 * Taken from the variation rather than from what was posted. WooCommerce
	 * compares the posted value against the chosen variation's own with ===,
	 * so a stale chooser, a difference in case, or a character that came back
	 * slightly altered all fail with "Invalid value posted for X" — which is
	 * what a shopper saw instead of a basket. The variation is the authority
	 * on what it is; only an attribute it leaves as "any" needs the posted
	 * answer, and that one is checked against the allowed list by WooCommerce.
	 *
	 * @param int   $variation_id Chosen variation, or 0.
	 * @param array $posted       What the browser sent.
	 * @return array
	 */
	public static function variation_attributes( $variation_id, array $posted ) {
		if ( ! $variation_id || ! function_exists( 'wc_get_product' ) ) {
			return $posted;
		}

		$variation = wc_get_product( $variation_id );

		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			return $posted;
		}

		$resolved = [];

		foreach ( (array) $variation->get_variation_attributes() as $key => $value ) {
			$resolved[ $key ] = '' !== $value
				? $value
				: ( isset( $posted[ $key ] ) ? $posted[ $key ] : '' );
		}

		return $resolved;
	}

	/**
	 * Does every attribute of a variation have a value?
	 *
	 * An attribute the variation leaves as "any" must be answered by the
	 * shopper. WooCommerce checks that only for attributes it can see: when an
	 * attribute's taxonomy is not registered it sees none, and took a bundle
	 * with every flavour empty (live, 2026-10-06, after the move). A line
	 * like that cannot be packed, so it is refused here whatever the browser
	 * sent.
	 *
	 * @param array $attributes attribute_x => value, as resolved.
	 * @return bool
	 */
	public static function complete( array $attributes ) {
		foreach ( $attributes as $value ) {
			if ( '' === trim( (string) $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Describe a variable product: its attributes and every buyable variation.
	 */
	public static function variations() {
		check_ajax_referer( PFH_Widgets_Ajax::NONCE, 'nonce' );

		if ( ! PFH_Widgets_Helpers::has_woocommerce() ) {
			wp_send_json_error( [ 'message' => __( 'De winkel is op dit moment niet beschikbaar.', 'pfh-widgets' ) ] );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			wp_send_json_error( [ 'message' => __( 'Dit product heeft geen opties.', 'pfh-widgets' ) ] );
		}

		$attributes = [];

		foreach ( $product->get_variation_attributes() as $name => $options ) {
			$choices = [];

			foreach ( (array) $options as $option ) {
				$choices[] = [
					'value' => $option,
					'label' => self::option_label( $name, $option ),
				];
			}

			$attributes[] = [
				'name'    => 'attribute_' . sanitize_title( $name ),
				'label'   => self::plain( wc_attribute_label( $name, $product ) ),
				'options' => $choices,
			];
		}

		$variations = [];

		foreach ( (array) $product->get_available_variations() as $variation ) {
			$variations[] = [
				'id'         => isset( $variation['variation_id'] ) ? (int) $variation['variation_id'] : 0,
				'attributes' => isset( $variation['attributes'] ) ? $variation['attributes'] : [],
				'price'      => isset( $variation['price_html'] ) ? $variation['price_html'] : '',
				'inStock'    => ! empty( $variation['is_in_stock'] ),
				'maxQty'     => isset( $variation['max_qty'] ) && '' !== $variation['max_qty'] ? (int) $variation['max_qty'] : 0,
				'image'      => isset( $variation['image']['src'] ) ? $variation['image']['src'] : '',
				'sku'        => isset( $variation['sku'] ) ? $variation['sku'] : '',
			];
		}

		wp_send_json_success(
			[
				'id'         => $product->get_id(),
				'name'       => self::plain( $product->get_name() ),
				'price'      => $product->get_price_html(),
				'image'      => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ),
				'permalink'  => $product->get_permalink(),
				'attributes' => $attributes,
				'variations' => $variations,
			]
		);
	}

	/**
	 * Find the variation matching a set of chosen attributes.
	 *
	 * @param WC_Product $product Variable product.
	 * @param array      $chosen  attribute_x => value.
	 * @return int Variation id, or 0.
	 */
	private static function match_variation( $product, array $chosen ) {
		if ( ! class_exists( 'WC_Data_Store' ) ) {
			return 0;
		}

		try {
			$store = WC_Data_Store::load( 'product' );
			$id    = $store->find_matching_product_variation( $product, $chosen );

			return $id ? (int) $id : 0;
		} catch ( Exception $e ) {
			return 0;
		}
	}

	/**
	 * A value as the product page shows it: its emoji, then its words — as
	 * plain text, because the popup escapes what it is given.
	 *
	 * Term names are stored HTML-escaped ("Appel &amp; Granaatappel"), and an
	 * emoji as a character reference where the table cannot hold it
	 * ("&#x1f34a; Mandarijn"). Escaped once more by the popup, both showed
	 * as code. A custom attribute borrows the emoji its global namesake has,
	 * as on the product page.
	 *
	 * @param string $attribute Attribute name.
	 * @param string $value     Stored value.
	 * @return string
	 */
	private static function option_label( $attribute, $value ) {
		$label = self::term_label( $attribute, $value );

		if ( ! class_exists( 'PFH_Widgets_Attribute_Emoji' ) ) {
			return self::plain( $label );
		}

		list( $emoji, $words ) = PFH_Widgets_Attribute_Emoji::split( $label );

		if ( '' === $emoji ) {
			$emoji = PFH_Widgets_Attribute_Emoji::borrowed( $attribute, $words );
		}

		return trim( $emoji . ' ' . $words );
	}

	/**
	 * Stored text as the characters it stands for.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function plain( $text ) {
		return html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Human label for an attribute value — taxonomy terms store a slug.
	 *
	 * @param string $attribute Attribute name.
	 * @param string $value     Stored value.
	 * @return string
	 */
	private static function term_label( $attribute, $value ) {
		if ( ! taxonomy_exists( $attribute ) ) {
			return $value;
		}

		$term = get_term_by( 'slug', $value, $attribute );

		return ( $term && ! is_wp_error( $term ) ) ? $term->name : $value;
	}
}
