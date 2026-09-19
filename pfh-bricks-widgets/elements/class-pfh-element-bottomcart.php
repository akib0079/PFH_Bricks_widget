<?php
/**
 * Bricks element: Products For Home bottom add to cart.
 *
 * The reminder at the foot of the page — the category, the product, what it
 * costs and what it saves, a dropdown for each choice, and one button that
 * adds it without a reload. It sits over the footer the way the closing banner
 * does, so the page ends on the thing the shopper came for.
 *
 * It follows the product being viewed. Off a product page it shows whichever
 * product is named here, so it can also close a landing page.
 *
 * The form is the same shape the single product page prints, down to the data
 * attributes, so pfh-product.js drives both — the choosing, the price
 * following the choice, the loader and the "Toegevoegd" are one piece of code,
 * not two that will drift.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PFH_Widgets_Product_Fields' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'includes/class-pfh-product-fields.php';
}

if ( ! trait_exists( 'PFH_Element_Product_Price' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'includes/trait-pfh-product-price.php';
}

class PFH_Element_Bottomcart extends \Bricks\Element {

	use PFH_Element_Defaults;
	use PFH_Element_Product_Price;

	/** Past this many variations the chooser posts the form instead. */
	const VARIATION_LIMIT = 60;

	/** The artwork behind the card. */
	const BACKGROUND = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Frame-470150-1-scaled.jpg';

	public $category     = 'products-for-home';
	public $name         = 'pfh-bottomcart';
	public $icon         = 'ti-shopping-cart-full';
	public $css_selector = '.pfh-bcart';

	public function get_label() {
		return esc_html__( 'PFH Bottom Add To Cart', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'cart', 'add to cart', 'bottom', 'reminder', 'inline', 'sticky', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::bottomcart();
	}

	public function set_control_groups() {
		foreach ( [
			'source'  => esc_html__( 'Product', 'pfh-widgets' ),
			'content' => esc_html__( 'Content', 'pfh-widgets' ),
			'style'   => esc_html__( 'Style', 'pfh-widgets' ),
			'layout'  => esc_html__( 'Layout & overlap', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		/* ---- product ---- */

		$this->controls['productId'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Product', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'e.g. 1482', 'pfh-widgets' ),
			'description' => esc_html__( 'On a product page this always follows the product being viewed. Elsewhere it shows this one. Empty uses the most recent product.', 'pfh-widgets' ),
		];

		$this->controls['sourceNote'] = [
			'tab'     => 'content',
			'group'   => 'source',
			'type'    => 'info',
			'content' => esc_html__( 'The picture comes from the product, under Products For Home → Bottom add to cart. A product without one uses its own product image.', 'pfh-widgets' ),
		];

		/* ---- content ---- */

		$this->controls['showCategory'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Show the category', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['showSaving'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Show what it saves', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['savingLabel'] = [
			'tab'      => 'content',
			'group'    => 'content',
			'label'    => esc_html__( 'Saving wording', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Bespaar',
			'required' => [ 'showSaving', '=', true ],
		];

		$this->controls['buttonLabel'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Button', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Voeg toe aan winkelmand',
		];

		$this->controls['addedLabel'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Button once added', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => 'Toegevoegd',
			'description' => esc_html__( 'Shown for a moment after the product goes in.', 'pfh-widgets' ),
		];

		$this->controls['choosePrefix'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Dropdown wording', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => 'Select',
			'description' => esc_html__( 'Put before the attribute, as "Select Type". Empty uses the attribute on its own.', 'pfh-widgets' ),
		];

		$this->controls['ajaxCart'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Add without a reload', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Switched off, the form posts the ordinary WooCommerce way.', 'pfh-widgets' ),
		];

		/* ---- style ---- */

		$this->controls['background'] = [
			'tab'         => 'content',
			'group'       => 'style',
			'label'       => esc_html__( 'Card background', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'Empty uses the artwork from the design.', 'pfh-widgets' ),
		];

		$this->controls['cardColor']  = $this->colour( 'style', esc_html__( 'Behind the artwork', 'pfh-widgets' ), '#cfe0d2' );
		$this->controls['eyebrowInk'] = $this->colour( 'style', esc_html__( 'Category', 'pfh-widgets' ), '#8a9a8a' );
		$this->controls['titleInk']   = $this->colour( 'style', esc_html__( 'Product name', 'pfh-widgets' ), '#2f3e2b' );
		$this->controls['priceInk']   = $this->colour( 'style', esc_html__( 'Price', 'pfh-widgets' ), '#3c868c' );
		$this->controls['saveInk']    = $this->colour( 'style', esc_html__( 'Saving', 'pfh-widgets' ), '#2f3e2b' );
		$this->controls['fieldLine']  = $this->colour( 'style', esc_html__( 'Dropdown border', 'pfh-widgets' ), '#828282' );
		$this->controls['buttonBg']   = $this->colour( 'style', esc_html__( 'Button', 'pfh-widgets' ), '#377a7f' );
		$this->controls['buttonInk']  = $this->colour( 'style', esc_html__( 'Button text', 'pfh-widgets' ), '#ffffff' );

		$this->controls['titleSize'] = $this->number( 'style', esc_html__( 'Product name size (px)', 'pfh-widgets' ), 36, 18, 60 );
		$this->controls['priceSize'] = $this->number( 'style', esc_html__( 'Price size (px)', 'pfh-widgets' ), 24, 14, 44 );
		$this->controls['radius']    = $this->number( 'style', esc_html__( 'Card radius (px)', 'pfh-widgets' ), 17, 0, 40 );

		/* ---- layout ---- */

		$this->controls['maxWidth'] = $this->number( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1140, 600, 1600 );
		$this->controls['bodyWidth'] = $this->number( 'layout', esc_html__( 'Text column width (px)', 'pfh-widgets' ), 505, 300, 900 );
		$this->controls['formWidth'] = $this->number( 'layout', esc_html__( 'Dropdowns and button width (px)', 'pfh-widgets' ), 426, 200, 900 );
		$this->controls['pad']      = $this->number( 'layout', esc_html__( 'Card padding (px)', 'pfh-widgets' ), 44, 12, 96 );
		$this->controls['padTop']   = $this->number( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 64, 0, 200 );

		$this->controls['overlap'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Pull the next section up (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 320,
			'inline'      => true,
			'default'     => 90,
			'description' => esc_html__( 'How far the footer rises behind the card. Put this element directly above the footer.', 'pfh-widgets' ),
		];

		$this->controls['imageWidth'] = $this->number( 'layout', esc_html__( 'Picture width (px)', 'pfh-widgets' ), 420, 160, 800 );
	}

	/**
	 * @param string $group   Group.
	 * @param string $label   Label.
	 * @param string $default Hex.
	 * @return array
	 */
	private function colour( $group, $label, $default ) {
		return [
			'tab'     => 'content',
			'group'   => $group,
			'label'   => $label,
			'type'    => 'color',
			'inline'  => true,
			'default' => [ 'hex' => $default ],
		];
	}

	/**
	 * @param string $group   Group.
	 * @param string $label   Label.
	 * @param int    $default Default.
	 * @param int    $min     Min.
	 * @param int    $max     Max.
	 * @return array
	 */
	private function number( $group, $label, $default, $min, $max ) {
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

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$product = $this->product();

		if ( ! $product ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-bcart pfh-bcart--empty"><p>'
					. esc_html__( 'Nothing to show: this page has no product, and none is named under Product.', 'pfh-widgets' )
					. '</p></div>';
			}

			return;
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-bcart', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-bcart__inner"><div class="pfh-bcart__card">';

		echo '<div class="pfh-bcart__body">';
		$this->render_head( $product );
		$this->render_price( $product );
		$this->render_form( $product );
		echo '</div>';

		$this->render_picture( $product );

		echo '</div></div></section>';
	}

	/**
	 * @param WC_Product $product Product.
	 */
	private function render_head( $product ) {
		if ( $this->switched_on( 'showCategory', true ) ) {
			$category = $this->category_name( $product );

			if ( '' !== $category ) {
				echo '<p class="pfh-bcart__eyebrow">' . esc_html( $category ) . '</p>';
			}
		}

		printf(
			'<h2 class="pfh-bcart__title"><a href="%s">%s</a></h2>',
			esc_url( (string) $product->get_permalink() ),
			esc_html( $product->get_name() )
		);
	}

	/**
	 * The price, the old price, and what the two differ by.
	 *
	 * The data attributes are the ones pfh-product.js looks for, so choosing a
	 * variant rewrites these three in place.
	 *
	 * @param WC_Product $product Product.
	 */
	private function render_price( $product ) {
		$price = $this->price_of( $product );

		echo '<div class="pfh-bcart__prices">';

		printf( '<span class="pfh-bcart__now" data-pfh-price-now>%s</span>', wp_kses_post( $price['now'] ) );

		printf(
			'<span class="pfh-bcart__was" data-pfh-price-was%s>%s</span>',
			'' === $price['was'] ? ' hidden' : '',
			wp_kses_post( $price['was'] )
		);

		printf(
			'<span class="pfh-bcart__save" data-pfh-price-save%s>%s</span>',
			'' === $price['save'] ? ' hidden' : '',
			esc_html( $price['save'] )
		);

		echo '</div>';
	}

	/**
	 * @param WC_Product $product Product.
	 */
	private function render_form( $product ) {
		$variable   = $product->is_type( 'variable' );
		$variations = $variable ? $this->variation_data( $product ) : [];

		printf(
			'<form class="pfh-bcart__form" method="post" enctype="multipart/form-data" action="%s" data-pfh-form data-product="%d"%s>',
			esc_url( (string) $product->get_permalink() ),
			(int) $product->get_id(),
			( $this->switched_on( 'ajaxCart', true ) ? ' data-pfh-ajax data-pfh-added-label="' . esc_attr( (string) $this->setting( 'addedLabel', 'Toegevoegd' ) ) . '"' : '' )
				. ( $variations ? ' data-variations="' . esc_attr( (string) wp_json_encode( $variations ) ) . '"' : '' )
		);

		if ( $variable ) {
			$this->render_choices( $product );
		}

		printf( '<input type="hidden" name="add-to-cart" value="%d" />', (int) $product->get_id() );
		printf( '<input type="hidden" name="product_id" value="%d" />', (int) $product->get_id() );
		echo '<input type="hidden" name="variation_id" value="0" data-pfh-variation />';
		echo '<input type="hidden" name="quantity" value="1" />';

		printf(
			'<button type="submit" class="pfh-bcart__cart" data-pfh-buy><span class="pfh-bcart__cart-label" data-pfh-cart-label>%s</span><span class="pfh-bcart__cart-spin" aria-hidden="true"></span></button>',
			esc_html( (string) $this->setting( 'buttonLabel', 'Voeg toe aan winkelmand' ) )
		);

		echo '</form>';

		printf(
			'<p class="pfh-bcart__notice" data-pfh-notice data-fallback="%s" hidden></p>',
			esc_attr__( 'That did not work. Please try again.', 'pfh-widgets' )
		);
	}

	/**
	 * One dropdown per attribute.
	 *
	 * A real select rather than the pills the product page uses: down here the
	 * row has to stay one line high whatever the product has, and a select is
	 * also what a phone gives its own picker for.
	 *
	 * @param WC_Product $product Variable product.
	 */
	private function render_choices( $product ) {
		$attributes = $product->get_variation_attributes();

		if ( ! $attributes ) {
			return;
		}

		$defaults = $product->get_default_attributes();
		$prefix   = trim( (string) $this->setting( 'choosePrefix', '' ) );

		echo '<div class="pfh-bcart__choices">';

		foreach ( $attributes as $name => $options ) {
			$key    = 'attribute_' . sanitize_title( $name );
			$label  = $this->attribute_label( $name, $product );
			$chosen = isset( $defaults[ sanitize_title( $name ) ] ) ? (string) $defaults[ sanitize_title( $name ) ] : '';

			printf( '<div class="pfh-bcart__choice" data-pfh-attr="%s">', esc_attr( $key ) );

			printf(
				'<label class="pfh-bcart__choice-label pfh-sr-only" for="%s">%s</label>',
				esc_attr( $key . '-' . (int) $product->get_id() ),
				esc_html( $label )
			);

			printf(
				'<select class="pfh-bcart__select" id="%s" name="%s" data-pfh-attr-field><option value="">%s</option>',
				esc_attr( $key . '-' . (int) $product->get_id() ),
				esc_attr( $key ),
				esc_html( '' !== $prefix ? $prefix . ' ' . $label : $label )
			);

			foreach ( $options as $option ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $option ),
					selected( $chosen, $option, false ),
					esc_html( $this->option_label( $name, $option ) )
				);
			}

			echo '</select>';
			echo PFH_Widgets_Icons::get( 'chevron', 'pfh-bcart__chev' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * @param WC_Product $product Product.
	 */
	private function render_picture( $product ) {
		$url = PFH_Widgets_Product_Fields::bottom_image( (int) $product->get_id(), 'large' );

		if ( '' === $url ) {
			return;
		}

		printf(
			'<div class="pfh-bcart__shots"><img class="pfh-bcart__shot is-active" src="%s" alt="%s" data-pfh-shot="0" loading="lazy" decoding="async" /></div>',
			esc_url( $url ),
			esc_attr( $product->get_name() )
		);
	}

	/* ---------------------------------------------------------------------
	 * The product, and what it costs
	 * ------------------------------------------------------------------ */

	/**
	 * @return WC_Product|null
	 */
	private function product() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$id = is_singular( 'product' ) ? get_queried_object_id() : 0;

		if ( ! $id ) {
			$id = (int) $this->setting( 'productId', 0 );
		}

		if ( ! $id ) {
			global $post;

			if ( $post && 'product' === get_post_type( $post ) ) {
				$id = (int) $post->ID;
			}
		}

		if ( ! $id ) {
			$recent = get_posts( [ 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ] );
			$id     = $recent ? (int) $recent[0] : 0;
		}

		if ( ! $id ) {
			return null;
		}

		$product = wc_get_product( $id );

		return ( $product && is_object( $product ) && $product->is_visible() ) ? $product : null;
	}

	/**
	 * The product's primary category.
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	private function category_name( $product ) {
		$terms = get_the_terms( $product->get_id(), 'product_cat' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}

		// The deepest one reads as the product's own shelf; the top of the
		// tree is usually just "Shop".
		$deepest = null;

		foreach ( $terms as $term ) {
			if ( ! $deepest || $term->parent > $deepest->parent ) {
				$deepest = $term;
			}
		}

		return $deepest ? (string) $deepest->name : '';
	}

	/**
	 * What it costs now, what it cost, and the difference — as an amount, not
	 * a percentage, which is what the design asks for.
	 *
	 * @param WC_Product $product Product or variation.
	 * @return array{now:string, was:string, save:string}
	 */
	private function price_of( $product ) {
		$parts = $this->price_parts( $product );

		if ( '' === $parts['now'] ) {
			return [ 'now' => '', 'was' => '', 'save' => '' ];
		}

		$saving = $parts['saving'];
		$show   = $this->switched_on( 'showSaving', true ) && $saving > 0;
		$label  = trim( (string) $this->setting( 'savingLabel', '' ) );

		return [
			'now'  => (string) wc_price( $parts['now'] ),
			'was'  => $saving > 0 ? (string) wc_price( $parts['was'] ) : '',
			'save' => $show ? trim( $label . ' ' . wp_strip_all_tags( wc_price( $saving ) ) ) : '',
		];
	}

	/**
	 * Every variation, in the shape the script matches against.
	 *
	 * @param WC_Product $product Variable product.
	 * @return array<int, array<string, mixed>>
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

	/**
	 * The readable name of one attribute.
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
	 * @param string $name   Attribute name.
	 * @param string $option Value.
	 * @return string
	 */
	private function option_label( $name, $option ) {
		if ( taxonomy_exists( $name ) ) {
			$term = get_term_by( 'slug', $option, $name );

			if ( $term && ! is_wp_error( $term ) ) {
				return (string) $term->name;
			}
		}

		return (string) $option;
	}

	private function build_vars() {
		$image = PFH_Widgets_Helpers::image_url( $this->setting( 'background' ), 'full' );

		if ( '' === $image ) {
			$image = self::BACKGROUND;
		}

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-bc-max'        => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1140 ) ),
				'--pfh-bc-body-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'bodyWidth', 505 ) ),
				'--pfh-bc-form-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'formWidth', 426 ) ),
				'--pfh-bc-pad-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'pad', 44 ) ),
				'--pfh-bc-pt-set'     => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 64 ) ),
				'--pfh-bc-overlap-set' => PFH_Widgets_Helpers::unit( $this->setting( 'overlap', 90 ) ),
				'--pfh-bc-shot-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'imageWidth', 420 ) ),
				'--pfh-bc-radius'     => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 17 ) ),
				'--pfh-bc-title-set'  => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 36 ) ),
				'--pfh-bc-price-set'  => PFH_Widgets_Helpers::unit( $this->setting( 'priceSize', 24 ) ),
				'--pfh-bc-image'      => 'url(' . esc_url_raw( $image ) . ')',
				'--pfh-bc-card'       => PFH_Widgets_Helpers::color( $this->setting( 'cardColor' ), '#cfe0d2' ),
				'--pfh-bc-eyebrow'    => PFH_Widgets_Helpers::color( $this->setting( 'eyebrowInk' ), '#8a9a8a' ),
				'--pfh-bc-title-ink'  => PFH_Widgets_Helpers::color( $this->setting( 'titleInk' ), '#2f3e2b' ),
				'--pfh-bc-price-ink'  => PFH_Widgets_Helpers::color( $this->setting( 'priceInk' ), '#3c868c' ),
				'--pfh-bc-save-ink'   => PFH_Widgets_Helpers::color( $this->setting( 'saveInk' ), '#2f3e2b' ),
				'--pfh-bc-field-line' => PFH_Widgets_Helpers::color( $this->setting( 'fieldLine' ), '#828282' ),
				'--pfh-bc-button'     => PFH_Widgets_Helpers::color( $this->setting( 'buttonBg' ), '#377a7f' ),
				'--pfh-bc-button-ink' => PFH_Widgets_Helpers::color( $this->setting( 'buttonInk' ), '#ffffff' ),
			]
		);
	}
}
