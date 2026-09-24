<?php
/**
 * Bricks element: Products For Home product grid.
 *
 * A plain multi-column grid of product cards under a centred heading — the
 * same card as the product slider (shared through PFH_Product_Card_Trait), but
 * laid out rather than scrolled.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Product_Grid extends \Bricks\Element {

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
	public $name         = 'pfh-product-grid';
	public $icon         = 'ti-layout-grid3';
	public $css_selector = '.pfh-pgrid';

	public function get_label() {
		return esc_html__( 'PFH Product Grid', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'product', 'grid', 'columns', 'woocommerce', 'shop', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::product_grid();
	}

	public function set_control_groups() {
		$this->control_groups['head']    = [ 'title' => esc_html__( 'Section heading', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['source']  = [ 'title' => esc_html__( 'Products', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['grid']    = [ 'title' => esc_html__( 'Grid', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['badge']   = [ 'title' => esc_html__( 'Sale badge', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['cart']    = [ 'title' => esc_html__( 'Add to cart', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['reviews'] = [ 'title' => esc_html__( 'Reviews', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['card']    = [ 'title' => esc_html__( 'Card', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']    = [ 'title' => esc_html__( 'Card typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout']  = [ 'title' => esc_html__( 'Layout & background', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->head_controls();
		$this->source_controls();
		$this->grid_controls();
		$this->badge_controls();
		$this->cart_controls();
		$this->review_controls();
		$this->card_controls();
		$this->type_controls();
		$this->layout_controls();
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
			'default'     => "ZILVEREN ONDERSCHEIDING Amsterdam\ninternationale <em>olijfoliecompetitie</em>",
			'description' => esc_html__( 'Line breaks are kept. Wrap words in <em>…</em> for the underlined italic accent.', 'pfh-widgets' ),
		];

		$this->controls['intro'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro text', 'pfh-widgets' ),
			'type'    => 'textarea',
			'default' => 'Uiteraard gebruiken we alleen de beste natuurlijke producten. Onze olijfolie heeft daarom medailles gewonnen!',
		];

		$this->controls['headAlign'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Alignment', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'center' => esc_html__( 'Centred', 'pfh-widgets' ),
				'left'   => esc_html__( 'Left', 'pfh-widgets' ),
			],
			'default' => 'center',
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
			'min'     => 14,
			'max'     => 72,
			'inline'  => true,
			'default' => 28,
		];

		$this->controls['headSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 48,
			'inline'  => true,
			'default' => 22,
		];

		$this->controls['headWeight'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '600',
		];

		$this->controls['headLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0.8,
			'max'     => 2,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.15,
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

		$this->controls['headWidth'] = [
			'tab'         => 'content',
			'group'       => 'head',
			'label'       => esc_html__( 'Heading max width (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 160,
			'max'         => 1200,
			'inline'      => true,
			'default'     => 481,
			'description' => esc_html__( 'Controls where the heading wraps. Figma: 481.', 'pfh-widgets' ),
		];

		$this->controls['introSize'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 9,
			'max'     => 26,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['introLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 2.4,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.5,
		];

		$this->controls['introColor'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#3d4a3a' ],
		];

		$this->controls['introWidth'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro max width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 160,
			'max'     => 1200,
			'inline'  => true,
			'default' => 520,
		];

		$this->controls['gapHeadIntro'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Space under the heading (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['headGap'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Space above the grid (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 160,
			'inline'  => true,
			'default' => 44,
		];
	}

	/* ---------------------------------------------------------------------
	 * Grid
	 * ------------------------------------------------------------------ */

	private function grid_controls() {
		$this->controls['columns'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Columns', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 6,
			'inline'  => true,
			'default' => 4,
		];

		$this->controls['columnsTablet'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Columns — tablet', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 4,
			'inline'  => true,
			'default' => 2,
		];

		$this->controls['columnsMobile'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Columns — mobile', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 3,
			'inline'  => true,
			'default' => 2,
		];

		$this->controls['gridGap'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Gap between columns (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 18,
		];

		$this->controls['gridRowGap'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Gap between rows (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 44,
		];
	}

	/* ---------------------------------------------------------------------
	 * Card visuals (the typography half lives in the shared trait)
	 * ------------------------------------------------------------------ */

	private function card_controls() {
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
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Image zoom on hover (%)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 100,
			'max'     => 125,
			'inline'  => true,
			'default' => 106,
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
			'default' => 100,
		];

		$this->controls['paddingBottom'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Padding bottom (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'inline'  => true,
			'default' => 100,
		];

		$this->controls['bgColor'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Section background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#fbfaf7' ],
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
			$this->uid = ! empty( $this->element['id'] ) ? sanitize_html_class( $this->element['id'] ) : uniqid( 'pfhg' );
		}

		return $this->uid;
	}

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
				echo '<div class="pfh-pgrid pfh-prod--empty"><p>' . esc_html__( 'No products matched. Check the Products group, or switch to manual cards.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		// pfh-prod carries the shared card tokens and styles; pfh-pgrid adds the
		// grid layout and the centred heading on top.
		$classes = [
			'pfh-pgrid',
			'pfh-prod',
			'pfh-scope',
			'pfh-align-' . (string) $this->get( 'headAlign', 'center' ),
			'pfh-cart-' . (string) $this->get( 'cartPosition', 'br' ),
			'pfh-reveal-' . (string) $this->get( 'cartReveal', 'up' ),
		];

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-pgrid__inner">';

		$this->render_head();

		echo '<ul class="pfh-pgrid__grid">';

		foreach ( $cards as $card ) {
			$this->render_card( $card );
		}

		echo '</ul>';
		echo '</div>';
		echo '</section>';
	}

	private function render_head() {
		$heading = (string) $this->get( 'heading', '' );
		$intro   = (string) $this->get( 'intro', '' );

		if ( ! $heading && ! $intro ) {
			return;
		}

		echo '<div class="pfh-pgrid__head">';

		if ( $heading ) {
			echo '<h2 class="pfh-pgrid__title">' . wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $heading ) ) ) . '</h2>';
		}

		if ( $intro ) {
			echo '<p class="pfh-pgrid__intro">' . wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $intro ) ) ) . '</p>';
		}

		echo '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Tokens
	 * ------------------------------------------------------------------ */

	private function build_vars() {
		$stack = ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif';
		$head  = trim( (string) $this->get( 'headFamily', 'Playfair Display' ) );

		return PFH_Widgets_Helpers::css_vars(
			array_merge(
				$this->card_vars(),
				[
				'--pfh-container'      => PFH_Widgets_Helpers::unit( $this->get( 'containerWidth', 1140 ) ),
				'--pfh-gutter-set'     => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-p-pad-top-set'  => PFH_Widgets_Helpers::unit( $this->get( 'paddingTop', 100 ) ),
				'--pfh-p-pad-bot-set'  => PFH_Widgets_Helpers::unit( $this->get( 'paddingBottom', 100 ) ),
				'--pfh-p-bg'           => PFH_Widgets_Helpers::color( $this->get( 'bgColor' ), '#fbfaf7' ),

				'--pfh-head-font'      => $head ? $head . $stack : 'inherit',
				'--pfh-head-size-set'  => PFH_Widgets_Helpers::unit( $this->get( 'headSize', 28 ) ),
				'--pfh-head-size-m'    => PFH_Widgets_Helpers::unit( $this->get( 'headSizeMobile', 22 ) ),
				'--pfh-head-weight'    => $this->get( 'headWeight', '600' ),
				'--pfh-head-lh'        => $this->get( 'headLineHeight', 1.15 ),
				'--pfh-head-color'     => PFH_Widgets_Helpers::color( $this->get( 'headColor' ), '#22301c' ),
				'--pfh-head-accent'    => PFH_Widgets_Helpers::color( $this->get( 'headAccent' ), '#5f6d46' ),
				'--pfh-head-max'       => PFH_Widgets_Helpers::unit( $this->get( 'headWidth', 481 ) ),
				'--pfh-head-gap-set'   => PFH_Widgets_Helpers::unit( $this->get( 'headGap', 44 ) ),
				'--pfh-intro-size'     => PFH_Widgets_Helpers::unit( $this->get( 'introSize', 14 ) ),
				'--pfh-intro-lh'       => $this->get( 'introLineHeight', 1.5 ),
				'--pfh-intro-color'    => PFH_Widgets_Helpers::color( $this->get( 'introColor' ), '#3d4a3a' ),
				'--pfh-intro-max'      => PFH_Widgets_Helpers::unit( $this->get( 'introWidth', 520 ) ),
				'--pfh-gap-hi'         => PFH_Widgets_Helpers::unit( $this->get( 'gapHeadIntro', 16 ) ),

				'--pfh-cols-set'       => (int) $this->get( 'columns', 4 ),
				'--pfh-cols-t'         => (int) $this->get( 'columnsTablet', 2 ),
				'--pfh-cols-m'         => (int) $this->get( 'columnsMobile', 2 ),
				'--pfh-grid-gap-set'       => PFH_Widgets_Helpers::unit( $this->get( 'gridGap', 18 ) ),
				'--pfh-grid-row-gap-set'   => PFH_Widgets_Helpers::unit( $this->get( 'gridRowGap', 44 ) ),

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

				'--pfh-t-size-m'       => PFH_Widgets_Helpers::unit( $this->get( 'titleSizeMobile', 24 ) ),
				]
			)
		);
	}
}
