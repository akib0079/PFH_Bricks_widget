<?php
/**
 * Bricks element: Products For Home single product.
 *
 * The top of the product page — breadcrumbs, the gallery, and everything down
 * the right-hand column: category, title, short description, the shop's
 * rating, the price, the variant pills, what is in the box, the quantity and
 * the add-to-cart button, and the three promises underneath.
 *
 * Everything on it comes from the product being viewed. The two things
 * WooCommerce has no field for — the corner tag and the "what is in the box"
 * lines — are added by PFH_Widgets_Product_Fields, in their own tab in the
 * Product data box.
 *
 * The variant chooser is pills rather than dropdowns, and it resolves the
 * variation itself from the data WooCommerce prints alongside it: no jQuery,
 * no dependency on WooCommerce's own variation script, and the same code path
 * in the builder as on the page.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

/*
 * The two product fields are this element's own dependency. The plugin loads
 * them at boot, but an element file can be pulled in on its own — Bricks does
 * exactly that, and so does the audit — and a class that is merely assumed to
 * be there is how a fatal reaches a live page.
 */
if ( ! class_exists( 'PFH_Widgets_Product_Fields' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'includes/class-pfh-product-fields.php';
}

if ( ! trait_exists( 'PFH_Element_Product_Price' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'includes/trait-pfh-product-price.php';
}

class PFH_Element_Product extends \Bricks\Element {

	use PFH_Element_Defaults;
	use PFH_Element_Product_Price;

	/** Past this many variations WooCommerce stops printing them inline. */
	const VARIATION_LIMIT = 60;

	public $category     = 'products-for-home';
	public $name         = 'pfh-product';
	public $icon         = 'ti-package';
	public $css_selector = '.pfh-pdp';

	public function get_label() {
		return esc_html__( 'PFH Product', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'product', 'single', 'pdp', 'gallery', 'cart', 'variations', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::product();
	}

	public function set_control_groups() {
		foreach ( [
			'source'     => esc_html__( 'Product', 'pfh-widgets' ),
			'crumbs'     => esc_html__( 'Breadcrumbs', 'pfh-widgets' ),
			'gallery'    => esc_html__( 'Gallery', 'pfh-widgets' ),
			'details'    => esc_html__( 'Title and description', 'pfh-widgets' ),
			'reviews'    => esc_html__( 'Rating', 'pfh-widgets' ),
			'price'      => esc_html__( 'Price', 'pfh-widgets' ),
			'variants'   => esc_html__( 'Variants', 'pfh-widgets' ),
			'highlights' => esc_html__( 'What is in the box', 'pfh-widgets' ),
			'cart'       => esc_html__( 'Add to cart', 'pfh-widgets' ),
			'usp'        => esc_html__( 'Promises', 'pfh-widgets' ),
			'style'      => esc_html__( 'Style', 'pfh-widgets' ),
			'layout'     => esc_html__( 'Layout', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		$this->source_controls();
		$this->crumb_controls();
		$this->gallery_controls();
		$this->detail_controls();
		$this->review_controls();
		$this->price_controls();
		$this->variant_controls();
		$this->highlight_controls();
		$this->cart_controls();
		$this->usp_controls();
		$this->style_controls();
		$this->layout_controls();
	}

	/* ---------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	/**
	 * @param string $group Group.
	 * @param string $label Label.
	 * @param array  $extra Anything else.
	 * @return array
	 */
	private function text_field( $group, $label, array $extra = [] ) {
		return array_merge( [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'text' ], $extra );
	}

	/**
	 * @param string $group Group.
	 * @param string $label Label.
	 * @param bool   $on    Default.
	 * @param array  $extra Anything else.
	 * @return array
	 */
	private function switch_field( $group, $label, $on = true, array $extra = [] ) {
		return array_merge( [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'checkbox', 'default' => $on ], $extra );
	}

	/**
	 * @param string $group   Group.
	 * @param string $label   Label.
	 * @param string $default Hex.
	 * @return array
	 */
	private function colour_field( $group, $label, $default = '' ) {
		$control = [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'color', 'inline' => true ];

		if ( '' !== $default ) {
			$control['default'] = [ 'hex' => $default ];
		}

		return $control;
	}

	/**
	 * @param string $group   Group.
	 * @param string $label   Label.
	 * @param int    $default Default.
	 * @param int    $min     Min.
	 * @param int    $max     Max.
	 * @return array
	 */
	private function number_field( $group, $label, $default, $min, $max ) {
		return [
			'tab'     => 'content',
			'group'   => $group,
			'label'   => $label,
			'type'    => 'number',
			'min'     => $min,
			'max'     => $max,
			'inline'  => true,
			'default' => $default,
		];
	}

	private function source_controls() {
		$this->controls['previewId'] = $this->text_field(
			'source',
			esc_html__( 'Product to show while editing', 'pfh-widgets' ),
			[
				'inline'      => true,
				'placeholder' => esc_html__( 'e.g. 1482', 'pfh-widgets' ),
				'description' => esc_html__( 'On a product page this element always shows the product being viewed. This ID is only used in the builder, where there is no product to read. Empty uses the most recent one.', 'pfh-widgets' ),
			]
		);
	}

	private function crumb_controls() {
		$this->controls['crumbsEnable'] = $this->switch_field( 'crumbs', esc_html__( 'Show breadcrumbs', 'pfh-widgets' ) );
		$this->controls['homeLabel']    = $this->text_field( 'crumbs', esc_html__( 'Home label', 'pfh-widgets' ), [ 'inline' => true, 'default' => 'Home' ] );
		$this->controls['crumbsSep']    = $this->text_field( 'crumbs', esc_html__( 'Separator', 'pfh-widgets' ), [ 'inline' => true, 'default' => '>' ] );
	}

	private function gallery_controls() {
		$this->controls['galThumbs'] = $this->switch_field( 'gallery', esc_html__( 'Show thumbnails', 'pfh-widgets' ) );
		$this->controls['galArrows'] = $this->switch_field( 'gallery', esc_html__( 'Show arrows', 'pfh-widgets' ) );

		$this->controls['galPlaceholder'] = [
			'tab'         => 'content',
			'group'       => 'gallery',
			'label'       => esc_html__( 'Placeholder image', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'Shown when a product has no image of its own. Empty uses WooCommerce\'s own placeholder.', 'pfh-widgets' ),
		];

		$this->controls['badgeMeta'] = $this->text_field(
			'gallery',
			esc_html__( 'Tag field', 'pfh-widgets' ),
			[
				'inline'      => true,
				'default'     => PFH_Widgets_Product_Fields::BADGE,
				'description' => esc_html__( 'The product field the corner tag is read from. Products For Home → Tag fills this one; point it at another field to use one of your own.', 'pfh-widgets' ),
			]
		);
	}

	private function detail_controls() {
		$this->controls['showEyebrow'] = $this->switch_field( 'details', esc_html__( 'Show the category above the title', 'pfh-widgets' ) );

		$this->controls['titleTag'] = [
			'tab'     => 'content',
			'group'   => 'details',
			'label'   => esc_html__( 'Title tag', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [ 'h1' => 'H1', 'h2' => 'H2' ],
			'default' => 'h1',
		];

		$this->controls['showExcerpt'] = $this->switch_field(
			'details',
			esc_html__( 'Show the short description', 'pfh-widgets' ),
			true,
			[ 'description' => esc_html__( 'The product\'s short description, as written in WooCommerce.', 'pfh-widgets' ) ]
		);
	}

	private function review_controls() {
		$this->controls['showReviews'] = $this->switch_field(
			'reviews',
			esc_html__( 'Show the shop rating', 'pfh-widgets' ),
			true,
			[ 'description' => esc_html__( 'The shop\'s own WebwinkelKeur rating, not this product\'s reviews.', 'pfh-widgets' ) ]
		);

		$this->controls['reviewsLabel'] = $this->text_field( 'reviews', esc_html__( 'Link label', 'pfh-widgets' ), [ 'inline' => true, 'default' => 'Lees reviews' ] );

		$this->controls['reviewsUrl'] = $this->text_field(
			'reviews',
			esc_html__( 'Link', 'pfh-widgets' ),
			[
				'placeholder' => '#reviews',
				'description' => esc_html__( 'Empty links to the shop\'s WebwinkelKeur page.', 'pfh-widgets' ),
			]
		);

		$this->controls['reviewsCountText'] = $this->text_field(
			'reviews',
			esc_html__( 'Count wording', 'pfh-widgets' ),
			[
				'default'     => '%s Reviews',
				'description' => esc_html__( '%s becomes the number of reviews.', 'pfh-widgets' ),
			]
		);
	}

	private function price_controls() {
		$this->controls['showSaving'] = $this->switch_field(
			'price',
			esc_html__( 'Show what the discount saves', 'pfh-widgets' ),
			true,
			[ 'description' => esc_html__( 'The amount saved, as drawn — not a percentage.', 'pfh-widgets' ) ]
		);

		$this->controls['savingSuffix'] = $this->text_field( 'price', esc_html__( 'Saving wording', 'pfh-widgets' ), [ 'inline' => true, 'default' => 'VOORDEEL' ] );
		$this->controls['savingWhole']  = $this->switch_field( 'price', esc_html__( 'Round the saving to whole euros', 'pfh-widgets' ) );
	}

	private function variant_controls() {
		$this->controls['showVariants'] = $this->switch_field( 'variants', esc_html__( 'Show the variant chooser', 'pfh-widgets' ) );

		$this->controls['labelSelected'] = $this->switch_field(
			'variants',
			esc_html__( 'Name the choice beside the group', 'pfh-widgets' ),
			true,
			[ 'description' => esc_html__( 'As drawn: SMAAK — PERZIK.', 'pfh-widgets' ) ]
		);

		$this->controls['tintedAttrs'] = $this->text_field(
			'variants',
			esc_html__( 'Groups using the second colour', 'pfh-widgets' ),
			[
				'placeholder' => 'smaak',
				'description' => esc_html__( 'Left empty, the first group uses the first colour and every group under it the second, as drawn. Name attributes here, separated by commas, to decide it yourself instead.', 'pfh-widgets' ),
			]
		);
	}

	private function highlight_controls() {
		$this->controls['highlightsMeta'] = $this->text_field(
			'highlights',
			esc_html__( 'Highlights field', 'pfh-widgets' ),
			[
				'inline'      => true,
				'default'     => PFH_Widgets_Product_Fields::HIGHLIGHTS,
				'description' => esc_html__( 'The product field the lines are read from. Products For Home → Highlights fills this one. Nothing in it means the whole block is left out.', 'pfh-widgets' ),
			]
		);

		$this->controls['highlightsLabel'] = $this->text_field(
			'highlights',
			esc_html__( 'Line above them', 'pfh-widgets' ),
			[
				'inline'      => true,
				'placeholder' => 'Smaak — Mandarijn',
				'description' => esc_html__( 'Used for products with no Highlight title of their own. Each product sets its own under Products For Home.', 'pfh-widgets' ),
			]
		);
	}

	private function cart_controls() {
		$this->controls['cartLabel'] = $this->text_field( 'cart', esc_html__( 'Button label', 'pfh-widgets' ), [ 'default' => 'Voeg toe aan winkelmand' ] );
		$this->controls['showQty']   = $this->switch_field( 'cart', esc_html__( 'Show the quantity stepper', 'pfh-widgets' ) );

		$this->controls['padQty'] = $this->switch_field(
			'cart',
			esc_html__( 'Write the quantity as 01', 'pfh-widgets' ),
			true,
			[ 'description' => esc_html__( 'Single figures get a leading zero, as drawn.', 'pfh-widgets' ) ]
		);

		$this->controls['ajaxCart'] = $this->switch_field(
			'cart',
			esc_html__( 'Add to the cart without reloading', 'pfh-widgets' ),
			true,
			[ 'description' => esc_html__( 'Off submits the form the ordinary way.', 'pfh-widgets' ) ]
		);

		$this->controls['soldOutLabel'] = $this->text_field( 'cart', esc_html__( 'Label when unavailable', 'pfh-widgets' ), [ 'default' => 'Niet beschikbaar' ] );

		$this->controls['addedLabel'] = $this->text_field(
			'cart',
			esc_html__( 'Label once it is in the basket', 'pfh-widgets' ),
			[
				'default'     => 'Toegevoegd',
				'description' => esc_html__( 'Shown for a moment after adding, then the button goes back to its own label.', 'pfh-widgets' ),
			]
		);
	}

	private function usp_controls() {
		$this->controls['showUsp'] = $this->switch_field( 'usp', esc_html__( 'Show the promises', 'pfh-widgets' ) );

		$this->controls['uspItems'] = [
			'tab'           => 'content',
			'group'         => 'usp',
			'label'         => esc_html__( 'Promises', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'default'       => [
				[ 'icon' => 'usp-secure', 'title' => 'Veilig betalen', 'note' => 'iDEAL · Klarna · PayPal' ],
				[ 'icon' => 'usp-natural', 'title' => 'Snel bezorgd', 'note' => 'Voor 15:00 = zelfde dag' ],
				[ 'icon' => 'usp-delivery', 'title' => 'Veilig betalen', 'note' => 'iDEAL · Klarna · PayPal' ],
			],
			'fields'        => [
				'icon'  => [
					'label'   => esc_html__( 'Icon', 'pfh-widgets' ),
					'type'    => 'select',
					'options' => [
						'usp-secure'   => esc_html__( 'Secure payment', 'pfh-widgets' ),
						'usp-natural'  => esc_html__( 'Leaf', 'pfh-widgets' ),
						'usp-delivery' => esc_html__( 'Delivery van', 'pfh-widgets' ),
						'check-list'   => esc_html__( 'Tick', 'pfh-widgets' ),
					],
					'default' => 'usp-secure',
				],
				'title' => [ 'label' => esc_html__( 'Title', 'pfh-widgets' ), 'type' => 'text' ],
				'note'  => [ 'label' => esc_html__( 'Below it', 'pfh-widgets' ), 'type' => 'text' ],
			],
		];
	}

	private function style_controls() {
		$this->controls['lineColor']  = $this->colour_field( 'style', esc_html__( 'Borders', 'pfh-widgets' ), '#EAEAEA' );
		$this->controls['accent']     = $this->colour_field( 'style', esc_html__( 'Button and first chosen variant', 'pfh-widgets' ), '#2b5f63' );
		$this->controls['accentInk']  = $this->colour_field( 'style', esc_html__( 'Text on those', 'pfh-widgets' ), '#ffffff' );
		$this->controls['softBg']     = $this->colour_field( 'style', esc_html__( 'Chosen variant, groups after the first', 'pfh-widgets' ), '#7caeb2' );
		$this->controls['softInk']    = $this->colour_field( 'style', esc_html__( 'Text on those', 'pfh-widgets' ), '#ffffff' );
		$this->controls['stageBg']    = $this->colour_field( 'style', esc_html__( 'Gallery background', 'pfh-widgets' ), '#f2f2f2' );
		$this->controls['badgeBg']    = $this->colour_field( 'style', esc_html__( 'Tag background', 'pfh-widgets' ), '#7f9471' );
		$this->controls['badgeInk']   = $this->colour_field( 'style', esc_html__( 'Tag text', 'pfh-widgets' ), '#ffffff' );
		$this->controls['ink']        = $this->colour_field( 'style', esc_html__( 'Headings', 'pfh-widgets' ), '#14181b' );
		$this->controls['bodyInk']    = $this->colour_field( 'style', esc_html__( 'Body text', 'pfh-widgets' ), '#3e4a3c' );
		$this->controls['mutedInk']   = $this->colour_field( 'style', esc_html__( 'Quiet text', 'pfh-widgets' ), '#a6a6a6' );
		$this->controls['starColor']  = $this->colour_field( 'style', esc_html__( 'Stars', 'pfh-widgets' ), '#f5a623' );
		$this->controls['iconColor']  = $this->colour_field( 'style', esc_html__( 'Promise icons', 'pfh-widgets' ), '#377a7f' );
		$this->controls['uspInk']     = $this->colour_field( 'style', esc_html__( 'Promise titles', 'pfh-widgets' ), '#1f3a3d' );
		$this->controls['tickColor']  = $this->colour_field( 'style', esc_html__( 'Ticks', 'pfh-widgets' ), '#879f82' );
		$this->controls['priceColor'] = $this->colour_field( 'style', esc_html__( 'Price and saving', 'pfh-widgets' ), '#697c66' );
		$this->controls['qtyInk']     = $this->colour_field( 'style', esc_html__( 'Quantity figure', 'pfh-widgets' ), '#51604f' );
		$this->controls['qtyLine']    = $this->colour_field( 'style', esc_html__( 'Quantity border', 'pfh-widgets' ), '#cedacb' );

		$this->controls['radius']     = $this->number_field( 'style', esc_html__( 'Corner radius (px)', 'pfh-widgets' ), 8, 0, 40 );
		$this->controls['titleSize']  = $this->number_field( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 40, 20, 72 );
		$this->controls['priceSize']  = $this->number_field( 'style', esc_html__( 'Price size (px)', 'pfh-widgets' ), 28, 16, 56 );
		$this->controls['textSize']   = $this->number_field( 'style', esc_html__( 'Body size (px)', 'pfh-widgets' ), 14, 10, 20 );
	}

	private function layout_controls() {
		$this->controls['maxWidth'] = $this->number_field( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1240, 600, 1600 );
		$this->controls['gap']      = $this->number_field( 'layout', esc_html__( 'Space between the columns (px)', 'pfh-widgets' ), 40, 12, 120 );
		$this->controls['padTop']   = $this->number_field( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 24, 0, 160 );
		$this->controls['padBottom'] = $this->number_field( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 56, 0, 200 );
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$product = $this->product();

		if ( ! $product ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-pdp pfh-pdp--empty"><p>'
					. esc_html__( 'This shows the product being viewed. There is none here, so give it a product to preview in the panel.', 'pfh-widgets' )
					. '</p></div>';
			}

			return;
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-pdp', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-pdp__inner">';

		$this->render_crumbs( $product );

		echo '<div class="pfh-pdp__main">';
		$this->render_gallery( $product );
		$this->render_details( $product );
		echo '</div>';

		echo '</div>';
		echo '</section>';
	}

	/* ---------------------------------------------------------------------
	 * Breadcrumbs
	 * ------------------------------------------------------------------ */

	/**
	 * @param WC_Product $product Product.
	 */
	private function render_crumbs( $product ) {
		if ( ! $this->switched_on( 'crumbsEnable' ) ) {
			return;
		}

		$crumbs = [ [ 'label' => (string) $this->setting( 'homeLabel', 'Home' ), 'href' => home_url( '/' ) ] ];
		$term   = $this->primary_term( $product );

		if ( $term ) {
			// The whole path down to it, so "Gia Giamas > Traditionele" reads
			// the way the catalogue is actually organised.
			foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat' ) ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );

				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$crumbs[] = [ 'label' => $ancestor->name, 'href' => (string) get_term_link( $ancestor ) ];
				}
			}

			$crumbs[] = [ 'label' => $term->name, 'href' => (string) get_term_link( $term ) ];
		}

		$crumbs[] = [ 'label' => $product->get_name(), 'href' => '' ];

		$sep  = (string) $this->setting( 'crumbsSep', '>' );
		$last = count( $crumbs ) - 1;

		echo '<nav class="pfh-pdp__crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'pfh-widgets' ) . '"><ol>';

		foreach ( $crumbs as $i => $crumb ) {
			echo '<li>';

			if ( $i < $last && '' !== $crumb['href'] ) {
				printf( '<a href="%s">%s</a>', esc_url( $crumb['href'] ), esc_html( $crumb['label'] ) );
			} else {
				printf( '<span aria-current="page">%s</span>', esc_html( $crumb['label'] ) );
			}

			if ( $i < $last ) {
				printf( '<span class="pfh-pdp__sep" aria-hidden="true">%s</span>', esc_html( $sep ) );
			}

			echo '</li>';
		}

		echo '</ol></nav>';
	}

	/* ---------------------------------------------------------------------
	 * Gallery
	 * ------------------------------------------------------------------ */

	/**
	 * @param WC_Product $product Product.
	 */
	private function render_gallery( $product ) {
		$images = $this->images( $product );
		$badge  = PFH_Widgets_Product_Fields::badge( $product->get_id(), (string) $this->setting( 'badgeMeta', PFH_Widgets_Product_Fields::BADGE ) );
		$arrows = $this->switched_on( 'galArrows' ) && count( $images ) > 1;
		$thumbs = $this->switched_on( 'galThumbs' ) && count( $images ) > 1;

		echo '<div class="pfh-pdp__gallery" data-pfh-gallery>';
		echo '<div class="pfh-pdp__stage">';

		if ( '' !== $badge ) {
			echo '<span class="pfh-pdp__badge">' . esc_html( $badge ) . '</span>';
		}

		echo '<div class="pfh-pdp__frame">';

		foreach ( $images as $i => $image ) {
			printf(
				'<img class="pfh-pdp__shot%s" src="%s" alt="%s" data-pfh-shot="%d"%s />',
				0 === $i ? ' is-active' : '',
				esc_url( $image['url'] ),
				esc_attr( $image['alt'] ),
				(int) $i,
				0 === $i ? '' : ' loading="lazy"'
			);
		}

		echo '</div>';

		if ( $arrows ) {
			printf(
				'<button type="button" class="pfh-pdp__nav pfh-pdp__nav--prev" data-pfh-shot-step="-1" aria-label="%s">%s</button>',
				esc_attr__( 'Previous image', 'pfh-widgets' ),
				PFH_Widgets_Icons::get( 'nav-left' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
			printf(
				'<button type="button" class="pfh-pdp__nav pfh-pdp__nav--next" data-pfh-shot-step="1" aria-label="%s">%s</button>',
				esc_attr__( 'Next image', 'pfh-widgets' ),
				PFH_Widgets_Icons::get( 'nav-right' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
		}

		echo '</div>';

		if ( $thumbs ) {
			/*
			 * One row that scrolls, however many images there are. Wrapping
			 * them pushed the buying column down the page on a product with
			 * five photographs, and a second half-empty row of thumbnails
			 * reads as a mistake rather than a gallery.
			 */
			echo '<div class="pfh-pdp__thumbs" data-pfh-thumbs>';

			printf(
				'<button type="button" class="pfh-pdp__thumbs-nav pfh-pdp__thumbs-nav--prev" data-pfh-thumbs-step="-1" aria-label="%s" hidden>%s</button>',
				esc_attr__( 'Scroll thumbnails back', 'pfh-widgets' ),
				PFH_Widgets_Icons::get( 'nav-left' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);

			echo '<ul class="pfh-pdp__thumbs-track" data-pfh-thumbs-track>';

			foreach ( $images as $i => $image ) {
				printf(
					'<li class="pfh-pdp__thumbs-item"><button type="button" class="pfh-pdp__thumb%s" data-pfh-shot-go="%d" aria-label="%s"><img src="%s" alt="%s" loading="lazy" /></button></li>',
					0 === $i ? ' is-active' : '',
					(int) $i,
					esc_attr( sprintf( /* translators: image number */ __( 'Show image %d', 'pfh-widgets' ), (int) $i + 1 ) ),
					esc_url( $image['thumb'] ),
					esc_attr( $image['alt'] )
				);
			}

			echo '</ul>';

			printf(
				'<button type="button" class="pfh-pdp__thumbs-nav pfh-pdp__thumbs-nav--next" data-pfh-thumbs-step="1" aria-label="%s" hidden>%s</button>',
				esc_attr__( 'Scroll thumbnails on', 'pfh-widgets' ),
				PFH_Widgets_Icons::get( 'nav-right' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Every image to show, the featured one first.
	 *
	 * @param WC_Product $product Product.
	 * @return array<int, array{url: string, thumb: string, alt: string}>
	 */
	private function images( $product ) {
		$ids = array_filter( array_merge( [ (int) $product->get_image_id() ], array_map( 'intval', (array) $product->get_gallery_image_ids() ) ) );
		$out = [];

		foreach ( array_unique( $ids ) as $id ) {
			$full = wp_get_attachment_image_url( $id, 'large' );

			if ( ! $full ) {
				continue;
			}

			$out[] = [
				'url'   => $full,
				'thumb' => (string) ( wp_get_attachment_image_url( $id, 'woocommerce_thumbnail' ) ?: $full ),
				'alt'   => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ?: $product->get_name(),
			];
		}

		if ( $out ) {
			return $out;
		}

		// Nothing of its own: whatever was chosen in the panel, else Woo's.
		$chosen = PFH_Widgets_Helpers::image_url( $this->setting( 'galPlaceholder' ), 'large' );
		$url    = $chosen ? $chosen : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'large' ) : '' );

		return $url ? [ [ 'url' => $url, 'thumb' => $url, 'alt' => $product->get_name() ] ] : [];
	}

	/* ---------------------------------------------------------------------
	 * The right-hand column
	 * ------------------------------------------------------------------ */

	/**
	 * @param WC_Product $product Product.
	 */
	private function render_details( $product ) {
		echo '<div class="pfh-pdp__details">';

		if ( $this->switched_on( 'showEyebrow' ) ) {
			$term = $this->primary_term( $product );

			if ( $term ) {
				echo '<p class="pfh-pdp__eyebrow">' . esc_html( $term->name ) . '</p>';
			}
		}

		$tag = in_array( (string) $this->setting( 'titleTag', 'h1' ), [ 'h1', 'h2' ], true ) ? (string) $this->setting( 'titleTag', 'h1' ) : 'h1';
		printf( '<%1$s class="pfh-pdp__title">%2$s</%1$s>', $tag, esc_html( $product->get_name() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag whitelisted.

		if ( $this->switched_on( 'showExcerpt' ) ) {
			$excerpt = trim( (string) $product->get_short_description() );

			if ( '' !== $excerpt ) {
				echo '<div class="pfh-pdp__excerpt">' . wp_kses_post( wpautop( $excerpt ) ) . '</div>';
			}
		}

		$this->render_rating();
		$this->render_price( $product );
		$this->render_form( $product );
		$this->render_usp();

		echo '</div>';
	}

	private function render_rating() {
		if ( ! $this->switched_on( 'showReviews' ) ) {
			return;
		}

		/*
		 * The same figures the rating badge and the footer show: the live
		 * WebwinkelKeur summary when there is one, and the shop's own settings
		 * when there is not. Asking summary() directly meant this row was the
		 * only thing on the site that vanished when the API had nothing —
		 * which is exactly how it turned up on the live page.
		 */
		if ( ! class_exists( 'PFH_Widgets_Badge' ) ) {
			return;
		}

		$data   = PFH_Widgets_Badge::data();
		$score  = (string) $data['rating'];
		$count  = (int) $data['count'];
		$stars  = (float) $data['stars'];

		if ( '' === $score || 0.0 === (float) str_replace( ',', '.', $score ) ) {
			return;
		}

		echo '<div class="pfh-pdp__rating">';
		echo '<span class="pfh-pdp__stars" aria-hidden="true">';

		for ( $i = 1; $i <= 5; $i++ ) {
			printf(
				'<span class="pfh-pdp__star%s">%s</span>',
				$i <= round( $stars ) ? ' is-on' : '',
				PFH_Widgets_Icons::get( 'star' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
		}

		echo '</span>';

		printf( '<span class="pfh-pdp__score">%s</span>', esc_html( $score ) );

		if ( $count > 0 ) {
			echo '<span class="pfh-pdp__rule" aria-hidden="true"></span>';
			printf(
				'<span class="pfh-pdp__count">%s</span>',
				esc_html( sprintf( (string) $this->setting( 'reviewsCountText', '%s Reviews' ), number_format_i18n( $count ) ) )
			);
		}

		printf(
			'<img class="pfh-pdp__wwk" src="%s" alt="%s" loading="lazy" />',
			esc_url( PFH_Widgets_Assets::img( 'pfh-webwinkelkeur.png' ) ),
			esc_attr__( 'WebwinkelKeur', 'pfh-widgets' )
		);

		$label = trim( (string) $this->setting( 'reviewsLabel', 'Lees reviews' ) );
		$url   = trim( (string) $this->setting( 'reviewsUrl', '' ) );
		$url   = '' !== $url ? $url : (string) PFH_Widgets_Reviews::review_url();

		if ( '' !== $label && '' !== $url ) {
			printf( '<a class="pfh-pdp__reviews-link" href="%s">%s</a>', esc_url( $url ), esc_html( $label ) );
		}

		echo '</div>';
	}

	/**
	 * @param WC_Product $product Product.
	 */
	private function render_price( $product ) {
		$price = $this->price_of( $product );

		echo '<div class="pfh-pdp__price" data-pfh-price>';
		printf( '<span class="pfh-pdp__price-now" data-pfh-price-now>%s</span>', wp_kses_post( $price['now'] ) );
		printf( '<span class="pfh-pdp__price-was" data-pfh-price-was%s>%s</span>', '' === $price['was'] ? ' hidden' : '', wp_kses_post( $price['was'] ) );
		printf( '<span class="pfh-pdp__save" data-pfh-price-save%s>%s</span>', '' === $price['save'] ? ' hidden' : '', esc_html( $price['save'] ) );
		echo '</div>';
	}

	/**
	 * Now, before, and what the difference saves.
	 *
	 * @param WC_Product $product Product or variation.
	 * @return array{now: string, was: string, save: string}
	 */
	private function price_of( $product ) {
		$parts = $this->price_parts( $product );

		if ( '' === $parts['now'] ) {
			return [ 'now' => '', 'was' => '', 'save' => '' ];
		}

		$saving = $parts['saving'];
		$show   = $this->switched_on( 'showSaving' ) && $saving > 0;

		return [
			'now'  => wc_price( $parts['now'] ),
			'was'  => $saving > 0 ? wc_price( $parts['was'] ) : '',
			'save' => $show ? $this->saving_text( $saving ) : '',
		];
	}

	/**
	 * "€14 VOORDEEL".
	 *
	 * @param float $saving Amount.
	 * @return string
	 */
	private function saving_text( $saving ) {
		$args = $this->switched_on( 'savingWhole' ) ? [ 'decimals' => 0 ] : [];
		$text = wp_strip_all_tags( wc_price( $this->switched_on( 'savingWhole' ) ? round( $saving ) : $saving, $args ) );
		$word = trim( (string) $this->setting( 'savingSuffix', 'VOORDEEL' ) );

		return trim( $text . ( '' !== $word ? ' ' . $word : '' ) );
	}

	/* ---------------------------------------------------------------------
	 * Variants, highlights and the cart
	 * ------------------------------------------------------------------ */

	/**
	 * @param WC_Product $product Product.
	 */
	private function render_form( $product ) {
		$variable   = $product->is_type( 'variable' ) && $this->switched_on( 'showVariants' );
		$variations = $variable ? $this->variation_data( $product ) : [];

		printf(
			'<form class="pfh-pdp__form" method="post" enctype="multipart/form-data" action="%s" data-pfh-form data-product="%d"%s>',
			esc_url( $product->get_permalink() ),
			(int) $product->get_id(),
			( $this->switched_on( 'ajaxCart' ) ? ' data-pfh-ajax data-pfh-added-label="' . esc_attr( $this->setting( 'addedLabel', 'Toegevoegd' ) ) . '"' : '' )
				. ( $variations ? ' data-variations="' . esc_attr( wp_json_encode( $variations ) ) . '"' : '' )
		);

		if ( $variable ) {
			$this->render_variants( $product );
		}

		$this->render_highlights( $product );
		$this->render_buy( $product, $variable );

		echo '</form>';
	}

	/**
	 * @param WC_Product $product Variable product.
	 */
	private function render_variants( $product ) {
		$attributes = $product->get_variation_attributes();

		if ( ! $attributes ) {
			return;
		}

		$tinted   = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $this->setting( 'tintedAttrs', '' ) ) ) ) );
		$defaults = $product->get_default_attributes();

		echo '<div class="pfh-pdp__attrs">';

		$index = 0;

		foreach ( $attributes as $name => $options ) {
			$key    = 'attribute_' . sanitize_title( $name );
			$label  = $this->attribute_label( $name, $product );
			$chosen = isset( $defaults[ sanitize_title( $name ) ] ) ? (string) $defaults[ sanitize_title( $name ) ] : '';

			/*
			 * Matched on every name the editor might reasonably type: what the
			 * panel shows them ("Smaak"), the taxonomy ("pa_smaak"), and the
			 * bare attribute ("smaak") — which is the one they will pick, and
			 * the only one that was not accepted before.
			 */
			$aliases = array_map( 'strtolower', [ $label, sanitize_title( $name ), preg_replace( '/^pa_/', '', $name ) ] );

			/*
			 * As drawn: the first group is the solid colour and everything
			 * under it the second. Naming groups in the panel takes over.
			 */
			$is_soft = $tinted ? (bool) array_intersect( $aliases, $tinted ) : $index > 0;
			$index++;

			printf(
				'<div class="pfh-pdp__attr%s" data-pfh-attr="%s">',
				$is_soft ? ' pfh-pdp__attr--soft' : '',
				esc_attr( $key )
			);

			printf(
				'<p class="pfh-pdp__attr-label">%s<span class="pfh-pdp__attr-value" data-pfh-attr-value></span></p>',
				esc_html( $label )
			);

			// The real field, for a form post and for anything reading the
			// page — the pills drive it rather than replacing it.
			printf( '<select class="pfh-pdp__select" name="%s" data-pfh-attr-field><option value="">%s</option>', esc_attr( $key ), esc_html__( 'Choose', 'pfh-widgets' ) );

			foreach ( $options as $option ) {
				$value = taxonomy_exists( $name ) ? $option : $option;
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $value ),
					selected( $chosen, $value, false ),
					esc_html( $this->option_label( $name, $option ) )
				);
			}

			echo '</select>';

			echo '<div class="pfh-pdp__pills" role="group">';

			foreach ( $options as $option ) {
				printf(
					'<button type="button" class="pfh-pdp__pill%s" data-pfh-pill="%s" aria-pressed="%s">%s</button>',
					(string) $chosen === (string) $option ? ' is-chosen' : '',
					esc_attr( $option ),
					(string) $chosen === (string) $option ? 'true' : 'false',
					esc_html( $this->option_label( $name, $option ) )
				);
			}

			echo '</div>';
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * The readable name of one attribute group.
	 *
	 * WooCommerce hands back the raw taxonomy when its attribute cache has not
	 * caught up with a newly created attribute, which would put "PA_SOORT" on
	 * the page. Tidying it is cheaper than showing that to a shopper.
	 *
	 * @param string     $name    Attribute name.
	 * @param WC_Product $product Product.
	 * @return string
	 */
	private function attribute_label( $name, $product ) {
		$label = (string) wc_attribute_label( $name, $product );

		if ( '' === $label || strtolower( $label ) === strtolower( $name ) ) {
			$label = ucfirst( trim( str_replace( [ '-', '_' ], ' ', preg_replace( '/^pa_/', '', $name ) ) ) );
		}

		return $label;
	}

	/**
	 * The readable name of one attribute value.
	 *
	 * @param string $name   Attribute name.
	 * @param string $option Value.
	 * @return string
	 */
	private function option_label( $name, $option ) {
		if ( taxonomy_exists( $name ) ) {
			$term = get_term_by( 'slug', $option, $name );

			if ( $term && ! is_wp_error( $term ) ) {
				return $term->name;
			}
		}

		return (string) $option;
	}

	/**
	 * @param WC_Product $product Product.
	 */
	private function render_highlights( $product ) {
		$rows = PFH_Widgets_Product_Fields::highlights(
			$product->get_id(),
			(string) $this->setting( 'highlightsMeta', PFH_Widgets_Product_Fields::HIGHLIGHTS )
		);

		if ( ! $rows ) {
			return;
		}

		echo '<div class="pfh-pdp__box">';

		/*
		 * The product's own line first: it differs per product and is set
		 * where the rest of the product is. The element's wording is only a
		 * fallback for products that have not been given one.
		 */
		$label = trim( PFH_Widgets_Product_Fields::text( $product->get_id(), PFH_Widgets_Product_Fields::HIGHLIGHT_TITLE ) );

		if ( '' === $label ) {
			$label = trim( (string) $this->setting( 'highlightsLabel', '' ) );
		}

		if ( '' !== $label ) {
			echo '<p class="pfh-pdp__attr-label">' . esc_html( $label ) . '</p>';
		}

		echo '<ul class="pfh-pdp__list">';

		foreach ( $rows as $row ) {
			echo '<li class="pfh-pdp__line">';
			echo PFH_Widgets_Icons::get( 'check-list', 'pfh-pdp__tick' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			printf( '<span class="pfh-pdp__line-label">%s</span>', esc_html( $row['label'] ) );

			if ( '' !== $row['note'] ) {
				printf( '<span class="pfh-pdp__line-note">%s</span>', esc_html( $row['note'] ) );
			}

			echo '</li>';
		}

		echo '</ul></div>';
	}

	/**
	 * @param WC_Product $product  Product.
	 * @param bool       $variable Whether the chooser was drawn.
	 */
	private function render_buy( $product, $variable ) {
		$available = $variable ? true : $product->is_purchasable() && $product->is_in_stock();
		$label     = $available
			? (string) $this->setting( 'cartLabel', 'Voeg toe aan winkelmand' )
			: (string) $this->setting( 'soldOutLabel', 'Niet beschikbaar' );

		echo '<div class="pfh-pdp__buy">';

		if ( $this->switched_on( 'showQty' ) ) {
			$max = $product->get_max_purchase_quantity();

			echo '<div class="pfh-pdp__qty">';
			printf(
				'<button type="button" class="pfh-pdp__step" data-pfh-qty="-1" aria-label="%s">%s</button>',
				esc_attr__( 'One fewer', 'pfh-widgets' ),
				PFH_Widgets_Icons::get( 'step-minus' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
			printf(
				'<input class="pfh-pdp__qty-input" type="number" name="quantity" value="1" min="1"%s inputmode="numeric" aria-label="%s" data-pfh-qty-field%s />',
				$max > 0 ? ' max="' . (int) $max . '"' : '',
				esc_attr__( 'Quantity', 'pfh-widgets' ),
				$this->switched_on( 'padQty' ) ? ' data-pfh-qty-pad' : ''
			);
			printf(
				'<button type="button" class="pfh-pdp__step" data-pfh-qty="1" aria-label="%s">%s</button>',
				esc_attr__( 'One more', 'pfh-widgets' ),
				PFH_Widgets_Icons::get( 'step-plus' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
			echo '</div>';
		} else {
			echo '<input type="hidden" name="quantity" value="1" data-pfh-qty-field />';
		}

		printf(
			'<button type="submit" class="pfh-pdp__cart"%s data-pfh-buy><span class="pfh-pdp__cart-label">%s</span><span class="pfh-pdp__cart-spin" aria-hidden="true"></span></button>',
			$available ? '' : ' disabled',
			esc_html( $label )
		);

		printf( '<input type="hidden" name="add-to-cart" value="%d" />', (int) $product->get_id() );
		printf( '<input type="hidden" name="product_id" value="%d" />', (int) $product->get_id() );
		echo '<input type="hidden" name="variation_id" value="0" data-pfh-variation />';

		echo '</div>';
		echo '<p class="pfh-pdp__notice" data-pfh-notice hidden></p>';
	}

	private function render_usp() {
		if ( ! $this->switched_on( 'showUsp' ) ) {
			return;
		}

		$items = $this->setting( 'uspItems', [] );
		$items = is_array( $items ) ? $items : [];
		$rows  = [];

		foreach ( $items as $item ) {
			$title = isset( $item['title'] ) ? trim( (string) $item['title'] ) : '';
			$note  = isset( $item['note'] ) ? trim( (string) $item['note'] ) : '';

			if ( '' !== $title || '' !== $note ) {
				$rows[] = [
					'icon'  => isset( $item['icon'] ) ? (string) $item['icon'] : 'usp-secure',
					'title' => $title,
					'note'  => $note,
				];
			}
		}

		if ( ! $rows ) {
			return;
		}

		echo '<ul class="pfh-pdp__usp">';

		foreach ( $rows as $row ) {
			echo '<li class="pfh-pdp__usp-item">';
			echo PFH_Widgets_Icons::get( $row['icon'], 'pfh-pdp__usp-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.

			if ( '' !== $row['title'] ) {
				printf( '<span class="pfh-pdp__usp-title">%s</span>', esc_html( $row['title'] ) );
			}

			if ( '' !== $row['note'] ) {
				printf( '<span class="pfh-pdp__usp-note">%s</span>', esc_html( $row['note'] ) );
			}

			echo '</li>';
		}

		echo '</ul>';
	}

	/* ---------------------------------------------------------------------
	 * The product, and its variations
	 * ------------------------------------------------------------------ */

	/**
	 * The product being viewed, or the one to preview.
	 *
	 * @return WC_Product|null
	 */
	private function product() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$id = 0;

		if ( is_singular( 'product' ) ) {
			$id = get_queried_object_id();
		}

		if ( ! $id ) {
			$id = (int) $this->setting( 'previewId', 0 );
		}

		if ( ! $id ) {
			global $post;

			if ( $post && 'product' === get_post_type( $post ) ) {
				$id = (int) $post->ID;
			}
		}

		if ( ! $id ) {
			// The builder has no product in hand; show the newest one so the
			// element can be laid out at all.
			$recent = get_posts( [ 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ] );
			$id     = $recent ? (int) $recent[0] : 0;
		}

		if ( ! $id ) {
			return null;
		}

		$product = wc_get_product( $id );

		return ( $product && is_object( $product ) ) ? $product : null;
	}

	/**
	 * The category to name, preferring an SEO plugin's primary one.
	 *
	 * @param WC_Product $product Product.
	 * @return WP_Term|null
	 */
	private function primary_term( $product ) {
		$id = (int) $product->get_id();

		foreach ( [ '_yoast_wpseo_primary_product_cat', 'rank_math_primary_product_cat' ] as $meta ) {
			$primary = (int) get_post_meta( $id, $meta, true );

			if ( $primary > 0 ) {
				$term = get_term( $primary, 'product_cat' );

				if ( $term && ! is_wp_error( $term ) ) {
					return $term;
				}
			}
		}

		$terms = get_the_terms( $id, 'product_cat' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return null;
		}

		/*
		 * The deepest one, so a product filed under "Gia Giamas > Traditionele"
		 * is named by the shelf it is on rather than the aisle.
		 */
		$best = null;
		$deep = -1;

		foreach ( $terms as $term ) {
			$depth = count( get_ancestors( $term->term_id, 'product_cat' ) );

			if ( $depth > $deep ) {
				$deep = $depth;
				$best = $term;
			}
		}

		return $best;
	}

	/**
	 * What the browser needs to resolve a choice into a variation.
	 *
	 * Only what is actually used: the attributes, the id, whether it can be
	 * bought, its image, and the three price strings. WooCommerce's own blob
	 * carries far more and is far larger.
	 *
	 * @param WC_Product $product Variable product.
	 * @return array
	 */
	private function variation_data( $product ) {
		$ids = $product->get_children();

		if ( ! $ids || count( $ids ) > self::VARIATION_LIMIT ) {
			return [];
		}

		$out = [];

		foreach ( $ids as $id ) {
			$variation = wc_get_product( $id );

			if ( ! $variation || ! $variation->exists() ) {
				continue;
			}

			$price = $this->price_of( $variation );
			$image = (int) $variation->get_image_id();

			$out[] = [
				'id'         => (int) $id,
				'attributes' => $variation->get_variation_attributes(),
				'buyable'    => $variation->is_purchasable() && $variation->is_in_stock(),
				'image'      => $image ? (string) wp_get_attachment_image_url( $image, 'large' ) : '',
				'now'        => $price['now'],
				'was'        => $price['was'],
				'save'       => $price['save'],
			];
		}

		return $out;
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-pdp-max'       => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1240 ) ),
				'--pfh-pdp-gap-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'gap', 40 ) ),
				'--pfh-pdp-pt'        => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 24 ) ),
				'--pfh-pdp-pb'        => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 56 ) ),
				'--pfh-pdp-radius'    => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 8 ) ),
				'--pfh-pdp-title-set' => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 40 ) ),
				'--pfh-pdp-price-set' => PFH_Widgets_Helpers::unit( $this->setting( 'priceSize', 28 ) ),
				'--pfh-pdp-text'      => PFH_Widgets_Helpers::unit( $this->setting( 'textSize', 14 ) ),
				'--pfh-pdp-line'      => PFH_Widgets_Helpers::color( $this->setting( 'lineColor' ), '#EAEAEA' ),
				'--pfh-pdp-accent'    => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#4f6d6c' ),
				'--pfh-pdp-accent-ink' => PFH_Widgets_Helpers::color( $this->setting( 'accentInk' ), '#ffffff' ),
				'--pfh-pdp-soft'      => PFH_Widgets_Helpers::color( $this->setting( 'softBg' ), '#c9dac2' ),
				'--pfh-pdp-soft-ink'  => PFH_Widgets_Helpers::color( $this->setting( 'softInk' ), '#2f3e2b' ),
				'--pfh-pdp-stage'     => PFH_Widgets_Helpers::color( $this->setting( 'stageBg' ), '#f2f2f2' ),
				'--pfh-pdp-badge'     => PFH_Widgets_Helpers::color( $this->setting( 'badgeBg' ), '#7f9471' ),
				'--pfh-pdp-badge-ink' => PFH_Widgets_Helpers::color( $this->setting( 'badgeInk' ), '#ffffff' ),
				'--pfh-pdp-ink'       => PFH_Widgets_Helpers::color( $this->setting( 'ink' ), '#14181b' ),
				'--pfh-pdp-body'      => PFH_Widgets_Helpers::color( $this->setting( 'bodyInk' ), '#3e4a3c' ),
				'--pfh-pdp-muted'     => PFH_Widgets_Helpers::color( $this->setting( 'mutedInk' ), '#8a8a8a' ),
				'--pfh-pdp-star'      => PFH_Widgets_Helpers::color( $this->setting( 'starColor' ), '#f5a623' ),
				'--pfh-pdp-icon'      => PFH_Widgets_Helpers::color( $this->setting( 'iconColor' ), '#377a7f' ),
				'--pfh-pdp-usp-ink'   => PFH_Widgets_Helpers::color( $this->setting( 'uspInk' ), '#1f3a3d' ),
				'--pfh-pdp-tick'      => PFH_Widgets_Helpers::color( $this->setting( 'tickColor' ), '#879f82' ),
				'--pfh-pdp-price-ink' => PFH_Widgets_Helpers::color( $this->setting( 'priceColor' ), '#697c66' ),
				'--pfh-pdp-qty-ink'   => PFH_Widgets_Helpers::color( $this->setting( 'qtyInk' ), '#51604f' ),
				'--pfh-pdp-qty-line'  => PFH_Widgets_Helpers::color( $this->setting( 'qtyLine' ), '#cedacb' ),
			]
		);
	}
}
