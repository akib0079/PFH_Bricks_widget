<?php
/**
 * What a product costs, before anyone has chosen anything.
 *
 * Shared by the single product page and the bottom reminder, because the
 * answer has to be the same on both: a variable product opens on its default
 * variation, and only falls back to the cheapest one when it has no default.
 * Getting that wrong shows a price the shopper cannot actually buy.
 *
 * Only the numbers are here. How a saving is worded belongs to each element —
 * the product page says "€14 VOORDEEL" and the reminder says "Bespaar €5,00".
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

trait PFH_Element_Product_Price {

	/**
	 * @param WC_Product $product Product or variation.
	 * @return array{now: string|float, was: string|float, saving: float}
	 */
	private function price_parts( $product ) {
		$empty = [ 'now' => '', 'was' => '', 'saving' => 0.0 ];

		if ( ! function_exists( 'wc_get_price_to_display' ) || ! is_object( $product ) ) {
			return $empty;
		}

		$target = $product;

		// A variable product shows whichever variation it opens on.
		if ( $product->is_type( 'variable' ) ) {
			$default = $this->opening_variation( $product );
			$target  = $default ? $default : $product;
		}

		if ( $target->is_type( 'variable' ) ) {
			// No default set: the range's floor, which is the price the shop
			// advertises for it anyway.
			$now = $target->get_variation_price( 'min', true );
			$was = $target->get_variation_regular_price( 'min', true );
		} else {
			$now = wc_get_price_to_display( $target );
			$was = wc_get_price_to_display( $target, [ 'price' => $target->get_regular_price() ] );
		}

		if ( '' === $now || null === $now ) {
			return $empty;
		}

		return [
			'now'    => $now,
			'was'    => $was,
			'saving' => (float) $was - (float) $now,
		];
	}

	/**
	 * The variation a variable product opens on.
	 *
	 * Its own default if that can be bought, and otherwise the first variation
	 * that can be — so the chooser opens on something real and the add to cart
	 * button is live rather than greyed out until the shopper guesses which
	 * combination exists.
	 *
	 * The answer is memoised per product: the price row asks for it, and so
	 * does the chooser, and resolving it walks every variation.
	 *
	 * @param WC_Product $product Variable product.
	 * @return WC_Product|null
	 */
	private function opening_variation( $product ) {
		static $memo = [];

		$id = (int) $product->get_id();

		if ( array_key_exists( $id, $memo ) ) {
			return $memo[ $id ];
		}

		$found = $this->default_variation( $product );

		if ( $found && $found->is_purchasable() && $found->is_in_stock() ) {
			$memo[ $id ] = $found;

			return $found;
		}

		$first = $this->first_available( $product );

		// Nothing is buyable: keep the declared default, so the page at least
		// shows the combination the shop meant to lead with.
		$memo[ $id ] = $first ? $first : $found;

		return $memo[ $id ];
	}

	/**
	 * The first variation a shopper could actually buy, in the shop's own
	 * order.
	 *
	 * @param WC_Product $product Variable product.
	 * @return WC_Product|null
	 */
	private function first_available( $product ) {
		foreach ( (array) $product->get_children() as $child ) {
			$variation = wc_get_product( $child );

			if ( $variation && is_object( $variation ) && $variation->is_purchasable() && $variation->is_in_stock() ) {
				return $variation;
			}
		}

		return null;
	}

	/**
	 * What the chooser should open with: attribute key => value.
	 *
	 * Taken from the opening variation, so the pills, the dropdowns and the
	 * price are all describing the same thing. A variation that answers "any"
	 * for an attribute leaves that one unchosen, which is correct — there is
	 * nothing to choose.
	 *
	 * @param WC_Product $product Variable product.
	 * @return array<string, string> Keyed by the bare attribute name.
	 */
	private function opening_choice( $product ) {
		$out = [];

		foreach ( (array) $product->get_default_attributes() as $name => $value ) {
			$out[ sanitize_title( $name ) ] = (string) $value;
		}

		$variation = $this->opening_variation( $product );

		if ( ! $variation ) {
			return $out;
		}

		foreach ( (array) $variation->get_variation_attributes() as $key => $value ) {
			if ( '' === $value ) {
				continue;
			}

			$out[ sanitize_title( preg_replace( '/^attribute_/', '', $key ) ) ] = (string) $value;
		}

		return $out;
	}

	/**
	 * The variation a variable product names as its default, if any.
	 *
	 * @param WC_Product $product Variable product.
	 * @return WC_Product|null
	 */
	private function default_variation( $product ) {
		$defaults = $product->get_default_attributes();

		if ( ! $defaults || ! class_exists( 'WC_Data_Store' ) ) {
			return null;
		}

		$wanted = [];

		foreach ( $defaults as $name => $value ) {
			$wanted[ 'attribute_' . $name ] = $value;
		}

		try {
			$store = WC_Data_Store::load( 'product' );
			$id    = $store->find_matching_product_variation( $product, $wanted );
		} catch ( \Throwable $e ) {
			return null;
		}

		if ( ! $id ) {
			return null;
		}

		$variation = wc_get_product( $id );

		return ( $variation && is_object( $variation ) ) ? $variation : null;
	}
}
