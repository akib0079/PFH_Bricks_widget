<?php
/**
 * Bricks element: Products For Home product slider.
 *
 * Drag-to-scroll slider of WooCommerce product cards with a sale badge, a
 * hover-revealed add-to-cart button and a static review row. Falls back to a
 * manual card list when WooCommerce is not available, so the element is still
 * usable (and previewable) without a shop.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Products extends \Bricks\Element {

	use PFH_Element_Defaults;

	/**
	 * Cached element uid.
	 *
	 * Per instance, not a method static: a static is shared by every
	 * instance of the class, so a second copy of this element on the same
	 * page would reuse the first one's value instead of computing its own.
	 *
	 * @var mixed
	 */
	private $uid = null;

	use PFH_Product_Card_Trait;

	public $category     = 'products-for-home';
	public $name         = 'pfh-products';
	public $icon         = 'ti-shopping-cart';
	public $css_selector = '.pfh-prod';
	public $scripts      = [ 'pfhSliderInit' ];


	public function get_label() {
		return esc_html__( 'PFH Product Slider', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'product', 'slider', 'sale', 'woocommerce', 'shop', 'cards', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::products();
	}

	public function set_control_groups() {
		$this->control_groups['head']    = [ 'title' => esc_html__( 'Section heading', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['source']  = [ 'title' => esc_html__( 'Products', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['badge']   = [ 'title' => esc_html__( 'Sale badge', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['cart']    = [ 'title' => esc_html__( 'Add to cart', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['reviews'] = [ 'title' => esc_html__( 'Reviews', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['card']    = [ 'title' => esc_html__( 'Card', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']    = [ 'title' => esc_html__( 'Card typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout']  = [ 'title' => esc_html__( 'Layout & background', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['drag']    = [ 'title' => esc_html__( 'Drag & scrollbar', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->head_controls();
		$this->source_controls();
		$this->badge_controls();
		$this->cart_controls();
		$this->review_controls();
		$this->card_controls();
		$this->type_controls();
		$this->layout_controls();
		$this->drag_controls();
	}

	/* ---------------------------------------------------------------------
	 * Section heading
	 * ------------------------------------------------------------------ */

	private function head_controls() {
		$this->controls['heading'] = [
			'tab'         => 'content',
			'group'       => 'head',
			'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'        => 'textarea',
			'default'     => 'Onze <em>bestsellers</em>',
			'description' => esc_html__( 'Wrap words in <em>…</em> for the underlined italic accent.', 'pfh-widgets' ),
		];

		$this->controls['headFamily'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading font family', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Playfair Display',
		];

		$this->controls['headSize'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 16,
			'max'     => 80,
			'inline'  => true,
			'default' => 38,
		];

		$this->controls['headSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 14,
			'max'     => 56,
			'inline'  => true,
			'default' => 28,
		];

		$this->controls['headColor'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#22301c' ],
		];

		$this->controls['headAccent'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Accent colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#5f6d46' ],
		];

		$this->controls['headGap'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Space below the heading (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 140,
			'inline'  => true,
			'default' => 40,
		];

		$this->controls['btnLabel'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Button label', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Bekijk meer',
		];

		$this->controls['btnLink'] = [
			'tab'      => 'content',
			'group'    => 'head',
			'label'    => esc_html__( 'Button link', 'pfh-widgets' ),
			'type'     => 'link',
			'required' => [ 'btnLabel', '!=', '' ],
		];

		$this->controls['btnColor'] = [
			'tab'      => 'content',
			'group'    => 'head',
			'label'    => esc_html__( 'Button text & border', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#5f6d46' ],
			'required' => [ 'btnLabel', '!=', '' ],
		];

		$this->controls['btnHeight'] = [
			'tab'      => 'content',
			'group'    => 'head',
			'label'    => esc_html__( 'Button height (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 28,
			'max'      => 80,
			'inline'   => true,
			'default'  => 44,
			'required' => [ 'btnLabel', '!=', '' ],
		];
	}

	/* ---------------------------------------------------------------------
	 * Product source
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * Sale badge
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * Add to cart
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * Reviews
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * Card
	 * ------------------------------------------------------------------ */

	private function card_controls() {
		$this->controls['perView'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Cards per view', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 7,
			'step'        => 0.1,
			'inline'      => true,
			'default'     => 3.5,
			'description' => esc_html__( 'Decimals show part of the next card, as in the design.', 'pfh-widgets' ),
		];

		$this->controls['perViewTablet'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Cards per view — tablet', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 5,
			'step'    => 0.1,
			'inline'  => true,
			'default' => 2.4,
		];

		$this->controls['perViewMobile'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Cards per view — mobile', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 3,
			'step'    => 0.1,
			'inline'  => true,
			'default' => 1.35,
		];

		$this->controls['cardGap'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Gap between cards (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['mediaBg'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Image box background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#f4f4f4' ],
		];

		$this->controls['mediaRadius'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Image box radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 18,
		];

		$this->controls['shotRadius'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Picture corner radius (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 40,
			'inline'      => true,
			'description' => esc_html__( 'The corners of the product photograph itself, inside its box. Leave empty and it follows the box\'s own radius, a little tighter, which is how nested corners stay concentric.', 'pfh-widgets' ),
		];

		$this->controls['mediaRatio'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Image box ratio', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => '310 / 358',
		];

		$this->controls['mediaPadding'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Image box padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 90,
			'inline'  => true,
			'default' => 26,
		];

		$this->controls['gapMediaMeta'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Space under the image box (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['gapMetaTitle'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Space under the reviews (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['gapTitlePrice'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Space under the title (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['imageZoom'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Image zoom on hover (%)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 100,
			'max'         => 125,
			'inline'      => true,
			'default'     => 106,
			'description' => esc_html__( 'The product sits inside a padded box, so this has room and never crops.', 'pfh-widgets' ),
		];

		$this->controls['hoverSpeed'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Transition (ms)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 80,
			'max'     => 1200,
			'step'    => 10,
			'inline'  => true,
			'default' => 360,
		];
	}

	/* ---------------------------------------------------------------------
	 * Card typography
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * Layout
	 * ------------------------------------------------------------------ */

	private function layout_controls() {
		$this->controls['containerWidth'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Content width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 600,
			'max'     => 1920,
			'inline'  => true,
			'default' => 1140,
		];

		$this->controls['containerPadding'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Side padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 24,
		];

		$this->controls['paddingTop'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Padding top (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'inline'  => true,
			'default' => 88,
		];

		$this->controls['paddingBottom'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Padding bottom (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'inline'  => true,
			'default' => 88,
		];

		$this->controls['bgColor'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Section background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['bgImage'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Background image', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'Sits behind the whole section, over the colour above.', 'pfh-widgets' ),
		];

		$this->controls['bgUrl'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'or background image URL', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '',
			'description' => esc_html__( 'Used only when no image is chosen above.', 'pfh-widgets' ),
		];

		$this->controls['bgSize'] = [
			'tab'      => 'content',
			'group'    => 'layout',
			'label'    => esc_html__( 'Background fit', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'default'  => 'cover',
			'options'  => [
				'cover'   => esc_html__( 'Fill the section', 'pfh-widgets' ),
				'contain' => esc_html__( 'Fit inside it', 'pfh-widgets' ),
				'auto'    => esc_html__( 'Its own size', 'pfh-widgets' ),
			],
		];

		$this->controls['bgPosition'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background position', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'default' => 'center center',
			'options' => [
				'center center' => esc_html__( 'Centre', 'pfh-widgets' ),
				'center top'    => esc_html__( 'Top', 'pfh-widgets' ),
				'center bottom' => esc_html__( 'Bottom', 'pfh-widgets' ),
				'left center'   => esc_html__( 'Left', 'pfh-widgets' ),
				'right center'  => esc_html__( 'Right', 'pfh-widgets' ),
			],
		];

		$this->controls['bleed'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Let the track run to the screen edge', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];
	}

	/* ---------------------------------------------------------------------
	 * Drag & scrollbar
	 * ------------------------------------------------------------------ */

	private function drag_controls() {
		$this->controls['dragEnable'] = [
			'tab'     => 'content',
			'group'   => 'drag',
			'label'   => esc_html__( 'Drag with the mouse', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['snap'] = [
			'tab'     => 'content',
			'group'   => 'drag',
			'label'   => esc_html__( 'Snap to cards', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => false,
		];

		$this->controls['barEnable'] = [
			'tab'     => 'content',
			'group'   => 'drag',
			'label'   => esc_html__( 'Show the scrollbar', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['barGap'] = [
			'tab'      => 'content',
			'group'    => 'drag',
			'label'    => esc_html__( 'Space above the scrollbar (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 120,
			'inline'   => true,
			'default'  => 40,
			'required' => [ 'barEnable', '=', true ],
		];

		$this->controls['barHeight'] = [
			'tab'      => 'content',
			'group'    => 'drag',
			'label'    => esc_html__( 'Track thickness (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 1,
			'max'      => 20,
			'inline'   => true,
			'default'  => 2,
			'required' => [ 'barEnable', '=', true ],
		];

		$this->controls['barTrackColor'] = [
			'tab'      => 'content',
			'group'    => 'drag',
			'label'    => esc_html__( 'Track colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(34, 80, 74, 0.07)' ],
			'required' => [ 'barEnable', '=', true ],
		];

		$this->controls['barThumbColor'] = [
			'tab'      => 'content',
			'group'    => 'drag',
			'label'    => esc_html__( 'Handle colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(34, 80, 74, 0.3)' ],
			'required' => [ 'barEnable', '=', true ],
		];

		$this->controls['barThumbHover'] = [
			'tab'      => 'content',
			'group'    => 'drag',
			'label'    => esc_html__( 'Handle colour (hover)', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(34, 80, 74, 0.55)' ],
			'required' => [ 'barEnable', '=', true ],
		];
	}

	/* ---------------------------------------------------------------------
	 * Setting accessors
	 * ------------------------------------------------------------------ */

	private function get( $key, $default = null ) {
		return $this->setting( $key, $default );
	}

	private function is_on( $key, $default = true ) {
		return $this->switched_on( $key, $default );
	}

	private function uid() {
		if ( null === $this->uid ) {
			$this->uid = ! empty( $this->element['id'] ) ? sanitize_html_class( $this->element['id'] ) : uniqid( 'pfhp' );
		}

		return $this->uid;
	}

	/* ---------------------------------------------------------------------
	 * Cards
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		if ( class_exists( 'PFH_Widgets_License' ) && PFH_Widgets_License::locked() ) {
			echo PFH_Widgets_License::locked_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped in locked_markup().

			return;
		}

		$cards = $this->cards();

		if ( empty( $cards ) ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-prod pfh-prod--empty"><p>' . esc_html__( 'No products matched. Check the Products group, or switch to manual cards.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$classes = [
			'pfh-prod',
			'pfh-scope',
			'pfh-cart-' . (string) $this->get( 'cartPosition', 'br' ),
			'pfh-reveal-' . (string) $this->get( 'cartReveal', 'up' ),
		];

		if ( $this->is_on( 'bleed' ) ) {
			$classes[] = 'is-bleed';
		}

		if ( $this->is_on( 'snap', false ) ) {
			$classes[] = 'is-snap';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );
		$this->set_attribute( '_root', 'data-pfh-drag-slider', '' );
		$this->set_attribute( '_root', 'data-pfh-config', wp_json_encode( $this->js_config() ) );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';

		$this->render_head();

		echo '<div class="pfh-prod__slider">';
		echo '<div class="pfh-prod__viewport" data-pfh-viewport tabindex="0" role="group" aria-label="' . esc_attr__( 'Producten', 'pfh-widgets' ) . '">';
		echo '<ul class="pfh-prod__track" data-pfh-track>';

		foreach ( $cards as $card ) {
			$this->render_card( $card );
		}

		echo '</ul>';
		echo '</div>';

		$this->render_scrollbar();

		echo '</div>';
		echo '</section>';
	}

	private function render_head() {
		$heading = (string) $this->get( 'heading', '' );
		$label   = (string) $this->get( 'btnLabel', '' );

		if ( ! $heading && ! $label ) {
			return;
		}

		echo '<div class="pfh-prod__inner">';
		echo '<div class="pfh-prod__head">';

		if ( $heading ) {
			echo '<h2 class="pfh-prod__title">' . wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $heading ) ) ) . '</h2>';
		}

		if ( $label ) {
			$link = PFH_Widgets_Helpers::link( $this->get( 'btnLink' ), '#' );

			echo '<a class="pfh-prod__more"' . PFH_Widgets_Helpers::link_attrs( $link ) . '>';
			echo '<span>' . esc_html( PFH_Widgets_Helpers::dd( $label ) ) . '</span>';
			echo PFH_Widgets_Icons::get( 'arrow-ne', 'pfh-prod__more-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			echo '</a>';
		}

		echo '</div>';
		echo '</div>';
	}

	private function render_scrollbar() {
		if ( ! $this->is_on( 'barEnable' ) ) {
			return;
		}
		?>
		<div class="pfh-prod__bar" data-pfh-bar hidden>
			<div class="pfh-prod__bar-track" data-pfh-bar-track>
				<div
					class="pfh-prod__bar-thumb"
					data-pfh-bar-thumb
					role="scrollbar"
					tabindex="0"
					aria-controls="pfh-viewport-<?php echo esc_attr( $this->uid() ); ?>"
					aria-orientation="horizontal"
					aria-label="<?php esc_attr_e( 'Schuif door de producten', 'pfh-widgets' ); ?>"
					aria-valuemin="0"
					aria-valuemax="100"
					aria-valuenow="0"
				></div>
			</div>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Tokens & config
	 * ------------------------------------------------------------------ */

	/**
	 * The section's background image, as a CSS value.
	 *
	 * A chosen picture first, then a URL — which is what lets an element that
	 * extends this one ship artwork of its own, since an image control cannot
	 * carry a URL as its default.
	 *
	 * @return string url(...) or 'none'.
	 */
	protected function background_image() {
		$url = PFH_Widgets_Helpers::image_url( $this->get( 'bgImage' ), 'full' );

		if ( '' === $url ) {
			$url = trim( (string) $this->get( 'bgUrl', '' ) );
		}

		if ( '' === $url ) {
			return 'none';
		}

		return 'url(' . esc_url_raw( $url ) . ')';
	}

	private function build_vars() {
		$stack = ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif';
		$head  = trim( (string) $this->get( 'headFamily', 'Playfair Display' ) );

		return PFH_Widgets_Helpers::css_vars(
			array_merge(
				$this->card_vars(),
				[
				'--pfh-container'      => PFH_Widgets_Helpers::unit( $this->get( 'containerWidth', 1140 ) ),
				'--pfh-gutter-set'     => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-p-pad-top-set'  => PFH_Widgets_Helpers::unit( $this->get( 'paddingTop', 88 ) ),
				'--pfh-p-pad-bot-set'  => PFH_Widgets_Helpers::unit( $this->get( 'paddingBottom', 88 ) ),
				'--pfh-p-bg'           => PFH_Widgets_Helpers::color( $this->get( 'bgColor' ), '#ffffff' ),
				'--pfh-p-image'        => $this->background_image(),
				'--pfh-p-bg-size'      => $this->get( 'bgSize', 'cover' ),
				'--pfh-p-bg-pos'       => $this->get( 'bgPosition', 'center center' ),

				'--pfh-head-font'      => $head ? $head . $stack : 'inherit',
				'--pfh-head-size-set'  => PFH_Widgets_Helpers::unit( $this->get( 'headSize', 38 ) ),
				'--pfh-head-size-m'    => PFH_Widgets_Helpers::unit( $this->get( 'headSizeMobile', 28 ) ),
				'--pfh-head-color'     => PFH_Widgets_Helpers::color( $this->get( 'headColor' ), '#22301c' ),
				'--pfh-head-accent'    => PFH_Widgets_Helpers::color( $this->get( 'headAccent' ), '#5f6d46' ),
				'--pfh-head-gap-set'   => PFH_Widgets_Helpers::unit( $this->get( 'headGap', 40 ) ),
				'--pfh-more-color'     => PFH_Widgets_Helpers::color( $this->get( 'btnColor' ), '#5f6d46' ),
				'--pfh-more-h'         => PFH_Widgets_Helpers::unit( $this->get( 'btnHeight', 44 ) ),

				'--pfh-per-set'        => $this->get( 'perView', 3.5 ),
				'--pfh-per-t'          => $this->get( 'perViewTablet', 2.4 ),
				'--pfh-per-m'          => $this->get( 'perViewMobile', 1.35 ),
				'--pfh-card-gap'       => PFH_Widgets_Helpers::unit( $this->get( 'cardGap', 20 ) ),

				'--pfh-media-bg'       => PFH_Widgets_Helpers::color( $this->get( 'mediaBg' ), '#f4f4f4' ),
				'--pfh-media-radius'   => PFH_Widgets_Helpers::unit( $this->get( 'mediaRadius', 18 ) ),
				'--pfh-shot-radius-set' => PFH_Widgets_Helpers::unit( $this->get( 'shotRadius', '' ) ),
				'--pfh-media-ratio'    => $this->get( 'mediaRatio', '310 / 358' ),
				'--pfh-media-pad-set'  => PFH_Widgets_Helpers::unit( $this->get( 'mediaPadding', 26 ) ),
				'--pfh-gap-1'          => PFH_Widgets_Helpers::unit( $this->get( 'gapMediaMeta', 14 ) ),
				'--pfh-gap-2'          => PFH_Widgets_Helpers::unit( $this->get( 'gapMetaTitle', 14 ) ),
				'--pfh-gap-3'          => PFH_Widgets_Helpers::unit( $this->get( 'gapTitlePrice', 12 ) ),
				'--pfh-img-zoom'       => ( (float) $this->get( 'imageZoom', 106 ) ) / 100,
				'--pfh-speed-set'      => PFH_Widgets_Helpers::unit( $this->get( 'hoverSpeed', 360 ), 'ms' ),

				'--pfh-bar-gap-set'    => PFH_Widgets_Helpers::unit( $this->get( 'barGap', 40 ) ),
				'--pfh-bar-h'          => PFH_Widgets_Helpers::unit( $this->get( 'barHeight', 2 ) ),
				'--pfh-bar-track'      => PFH_Widgets_Helpers::color( $this->get( 'barTrackColor' ), 'rgba(34,80,74,.07)' ),
				'--pfh-bar-thumb'      => PFH_Widgets_Helpers::color( $this->get( 'barThumbColor' ), 'rgba(34,80,74,.3)' ),
				'--pfh-bar-thumb-hover' => PFH_Widgets_Helpers::color( $this->get( 'barThumbHover' ), 'rgba(34,80,74,.55)' ),
				]
			)
		);
	}

	private function js_config() {
		return [
			'uid'   => $this->uid(),
			'drag'  => $this->is_on( 'dragEnable' ),
			'wheel' => false,
			'bar'   => $this->is_on( 'barEnable' ),
			'snap'  => $this->is_on( 'snap', false ),
		];
	}
}
