<?php
/**
 * Bricks element: Products For Home related products.
 *
 * The row under a product. It extends the product slider rather than copying
 * it, so the card, the drag behaviour and every style control stay identical
 * by construction — the two cannot drift apart the way a duplicate would.
 *
 * Only the source is its own, and it is pinned: what the editor chose for this
 * product, else the rest of its own category, else nothing. A section filled
 * with whatever the shop happens to sell is worse than no section, so the
 * third case renders nothing at all.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PFH_Widgets_Product_Fields' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'includes/class-pfh-product-fields.php';
}

/*
 * It extends the product slider, so the parent has to be there before this
 * class is declared. Bricks loads an element file on its own, and a parent
 * merely assumed to be loaded is a fatal on a live page rather than a notice.
 */
if ( ! class_exists( 'PFH_Element_Products' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'elements/class-pfh-element-products.php';
}

class PFH_Element_Product_Related extends PFH_Element_Products {

	/** The band this row sits on when nothing else is chosen. */
	const BACKGROUND = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Group-1000001553.webp';

	public $name = 'pfh-product-related';
	public $icon = 'ti-layout-grid3';

	public function get_label() {
		return esc_html__( 'PFH Related Products', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'related', 'products', 'cross-sell', 'slider', 'pdp', 'pfh' ];
	}

	public function set_controls() {
		parent::set_controls();

		// The source is what this element is, so it is not offered.
		unset( $this->controls['source'], $this->controls['manualCards'], $this->controls['productIds'], $this->controls['category'] );

		if ( isset( $this->controls['heading'] ) ) {
			$this->controls['heading']['default'] = 'Gerelateerde <em>Producten</em>';
		}

		if ( isset( $this->controls['limit'] ) ) {
			$this->controls['limit']['default'] = 8;
		}

		/*
		 * Unlike the shop's own sliders this row sits on a band. It goes in as
		 * a URL rather than a picture because an image control cannot carry a
		 * default — choosing one in the panel still wins over it.
		 */
		/*
		 * Every section on the product page pads 72 above and below. This row
		 * inherited the shop slider's 88, which put a step in the page each
		 * time someone scrolled past it. The controls are still there.
		 */
		foreach ( [ 'paddingTop' => 72, 'paddingBottom' => 72 ] as $control => $value ) {
			if ( isset( $this->controls[ $control ] ) ) {
				$this->controls[ $control ]['default'] = $value;
			}
		}

		if ( isset( $this->controls['bgUrl'] ) ) {
			$this->controls['bgUrl']['default']     = self::BACKGROUND;
			$this->controls['bgUrl']['description'] = esc_html__( 'Used only when no image is chosen above. Empty leaves the section plain.', 'pfh-widgets' );
		}

		$this->controls['previewId'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Product to relate to while editing', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'e.g. 1482', 'pfh-widgets' ),
			'description' => esc_html__( 'On a product page this always follows the product being viewed. This ID is only used in the builder. Empty uses the most recent product.', 'pfh-widgets' ),
		];

		$this->controls['relatedNote'] = [
			'tab'     => 'content',
			'group'   => 'source',
			'type'    => 'info',
			'content' => esc_html__( 'Each product picks its own under Products For Home → Related products. With none chosen it shows the rest of that product\'s category, and with neither this section is left off the page.', 'pfh-widgets' ),
		];
	}

	public function render() {
		// Whatever is stored, this element has one source.
		$this->settings['source'] = 'related';

		parent::render();
	}
}
