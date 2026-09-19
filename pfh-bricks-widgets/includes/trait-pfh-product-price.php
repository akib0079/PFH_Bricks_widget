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
			$default = $this->default_variation( $product );
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
	 * The variation a variable product opens on, if it names one.
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
