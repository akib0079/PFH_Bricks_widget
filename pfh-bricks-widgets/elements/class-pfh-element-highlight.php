<?php
/**
 * Bricks element: Products For Home product highlight.
 *
 * The wide banner that puts one product forward — an eyebrow, a two-line
 * title, a short pitch, a few ticked selling points, and the price.
 *
 * Every piece of copy is its own control and every one accepts dynamic data,
 * so an ACF field can be dropped into any of them later. Until that happens
 * the defaults carry the launch copy, which is why this reads as a finished
 * banner out of the box rather than an empty frame.
 *
 * The prices are the exception: they come from a real WooCommerce product the
 * editor picks, so the banner cannot drift out of step with the shop. There
 * is a manual mode for the case where the offer is not a single product.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Highlight extends \Bricks\Element {

	use PFH_Design_Revision;

	public $category     = 'products-for-home';
	public $name         = 'pfh-highlight';
	public $icon         = 'ti-package';
	public $css_selector = '.pfh-hl';

	public function get_label() {
		return esc_html__( 'PFH Product Highlight', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'highlight', 'product', 'banner', 'offer', 'bundle', 'cta', 'promo', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::highlight();
	}

	public function set_control_groups() {
		$this->control_groups['product'] = [ 'title' => esc_html__( 'Product', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['copy']    = [ 'title' => esc_html__( 'Copy', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['points']  = [ 'title' => esc_html__( 'Selling points', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['price']   = [ 'title' => esc_html__( 'Price', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['media']   = [ 'title' => esc_html__( 'Image', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['style']   = [ 'title' => esc_html__( 'Style', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout']  = [ 'title' => esc_html__( 'Layout', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->product_controls();
		$this->copy_controls();
		$this->point_controls();
		$this->price_controls();
		$this->media_controls();
		$this->style_controls();
		$this->layout_controls();
	}

	/* ---------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	private function product_controls() {
		/*
		 * A searchable list of real products, not an ID to go and look up.
		 * Typing an ID means leaving the builder, finding the product, and
		 * copying a number out of the address bar — for something the editor
		 * already knows by name.
		 */
		$this->controls['product'] = [
			'tab'         => 'content',
			'group'       => 'product',
			'label'       => esc_html__( 'Product', 'pfh-widgets' ),
			'type'        => 'select',
			'searchable'  => true,
			'clearable'   => true,
			'options'     => self::product_options(),
			'placeholder' => esc_html__( 'Search for a product…', 'pfh-widgets' ),
			'description' => esc_html__( 'The product this banner prices. Leave empty to type the prices in yourself below.', 'pfh-widgets' ),
		];

		$this->controls['productId'] = [
			'tab'         => 'content',
			'group'       => 'product',
			'label'       => esc_html__( 'Or a product ID', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'e.g. 1482', 'pfh-widgets' ),
			'description' => esc_html__( 'Only needed to connect a dynamic field, or for a product the list above does not reach. It wins over the choice above.', 'pfh-widgets' ),
		];

		$this->controls['link'] = [
			'tab'         => 'content',
			'group'       => 'product',
			'label'       => esc_html__( 'Where the banner goes', 'pfh-widgets' ),
			'type'        => 'link',
			'description' => esc_html__( 'Leave empty to link to the product above.', 'pfh-widgets' ),
		];

		$this->controls['clickable'] = [
			'tab'         => 'content',
			'group'       => 'product',
			'label'       => esc_html__( 'The whole banner is clickable', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Off leaves only the button, if one is shown.', 'pfh-widgets' ),
		];
	}

	private function copy_controls() {
		$this->controls['eyebrow'] = [
			'tab'         => 'content',
			'group'       => 'copy',
			'label'       => esc_html__( 'Eyebrow', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'Meest gekozen',
			'description' => esc_html__( 'Dynamic data is supported here and in every field below. Leave empty to hide it.', 'pfh-widgets' ),
		];

		$this->controls['eyebrowIcon'] = [
			'tab'         => 'content',
			'group'       => 'copy',
			'label'       => esc_html__( 'Eyebrow icon', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '🎁',
			'description' => esc_html__( 'An emoji, or empty for none.', 'pfh-widgets' ),
		];

		$this->controls['titleTop'] = [
			'tab'     => 'content',
			'group'   => 'copy',
			'label'   => esc_html__( 'Title, first line', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Proefpakket',
		];

		$this->controls['titleBottom'] = [
			'tab'         => 'content',
			'group'       => 'copy',
			'label'       => esc_html__( 'Title, second line', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => '3 smaken naar keuze',
			'description' => esc_html__( 'Set in a heavier weight than the first, as drawn.', 'pfh-widgets' ),
		];

		$this->controls['titleTag'] = [
			'tab'     => 'content',
			'group'   => 'copy',
			'label'   => esc_html__( 'Title tag', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'h2' => 'H2',
				'h3' => 'H3',
				'p'  => esc_html__( 'Not a heading', 'pfh-widgets' ),
			],
			'default' => 'h2',
		];

		$this->controls['text'] = [
			'tab'     => 'content',
			'group'   => 'copy',
			'label'   => esc_html__( 'Description', 'pfh-widgets' ),
			'type'    => 'textarea',
			'default' => 'Ontdek de wereld van Gia Giamas. Kies zelf drie smaken uit het volledige assortiment en bespaar direct €5,00 t.o.v. losse aankoop.',
		];

		$this->controls['btnLabel'] = [
			'tab'         => 'content',
			'group'       => 'copy',
			'label'       => esc_html__( 'Button', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '',
			'description' => esc_html__( 'Empty for none — the banner itself is the link.', 'pfh-widgets' ),
		];
	}

	private function point_controls() {
		$this->controls['points'] = [
			'tab'           => 'content',
			'group'         => 'points',
			'label'         => esc_html__( 'Selling points', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'text',
			'default'       => [
				[ 'text' => 'Kies zelf 3 smaken uit het assortiment' ],
				[ 'text' => 'Ideaal cadeau — inclusief receptenkaart' ],
				[ 'text' => 'Gratis verzending bij bestelling' ],
			],
			'fields'        => [
				'text' => [
					'label' => esc_html__( 'Text', 'pfh-widgets' ),
					'type'  => 'text',
				],
			],
		];

		$this->controls['pointColor'] = [
			'tab'     => 'content',
			'group'   => 'points',
			'label'   => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#3f4c3e' ],
		];

		$this->controls['tickColor'] = [
			'tab'     => 'content',
			'group'   => 'points',
			'label'   => esc_html__( 'Tick colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#7d9569' ],
		];
	}

	private function price_controls() {
		$this->controls['priceSource'] = [
			'tab'     => 'content',
			'group'   => 'price',
			'label'   => esc_html__( 'Prices come from', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'product' => esc_html__( 'The product above', 'pfh-widgets' ),
				'manual'  => esc_html__( 'The fields below', 'pfh-widgets' ),
			],
			'default' => 'product',
		];

		$this->controls['priceManual'] = [
			'tab'         => 'content',
			'group'       => 'price',
			'label'       => esc_html__( 'Price', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '€ 41,97',
			'description' => esc_html__( 'Also used when no product is picked, and in the builder.', 'pfh-widgets' ),
		];

		$this->controls['oldManual'] = [
			'tab'     => 'content',
			'group'   => 'price',
			'label'   => esc_html__( 'Price before the discount', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => '€ 46,97',
		];

		$this->controls['saveText'] = [
			'tab'         => 'content',
			'group'       => 'price',
			'label'       => esc_html__( 'Saving line', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'Bespaar %s — %pct%% korting',
			'description' => esc_html__( '%s becomes the amount saved and %pct% the percentage, both worked out from the product. When there is no discount the clause naming it is dropped rather than left reading "0%".', 'pfh-widgets' ),
		];

		$this->controls['savePercentFallback'] = [
			'tab'      => 'content',
			'group'    => 'price',
			'label'    => esc_html__( 'Saving percentage, when there is no product', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 99,
			'inline'   => true,
			'default'  => 11,
		];

		$this->controls['saveFallback'] = [
			'tab'         => 'content',
			'group'       => 'price',
			'label'       => esc_html__( 'Saving, when there is no product', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '€5,00',
		];
	}

	private function media_controls() {
		$this->controls['image'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Image', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'The supplied banner image is used until one is chosen. Connect an ACF image field here later.', 'pfh-widgets' ),
		];

		$this->controls['imageAlt'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Image description', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => '',
		];

		$this->controls['imageWidth'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Image share of the banner (%)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 20,
			'max'     => 70,
			'inline'  => true,
			'default' => 50,
		];

		$this->controls['imageBlend'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Fade the image into the card', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'The supplied photograph has its own background baked in, which leaves a seam against the card. Turn this off for a cut-out on a transparent background.', 'pfh-widgets' ),
		];

		$this->controls['imageFade'] = [
			'tab'      => 'content',
			'group'    => 'media',
			'label'    => esc_html__( 'Fade distance (%)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 2,
			'max'      => 45,
			'inline'   => true,
			'default'  => 14,
			'required' => [ 'imageBlend', '=', true ],
		];

		$this->controls['imageSide'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Image side', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'right' => esc_html__( 'Right', 'pfh-widgets' ),
				'left'  => esc_html__( 'Left', 'pfh-widgets' ),
			],
			'default' => 'right',
		];
	}

	private function style_controls() {
		$this->controls['bg'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#d9e6dc' ],
		];

		$this->controls['radius'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['padX'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Inner padding, sides (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 52,
		];

		$this->controls['padY'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Inner padding, top and bottom (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 48,
		];

		$this->controls['eyebrowSize'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Eyebrow size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 40,
			'inline'  => true,
			'default' => 22,
		];

		$this->controls['titleSize'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Title size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 18,
			'max'     => 64,
			'inline'  => true,
			'default' => 32,
		];

		$this->controls['textSize'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Description size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['priceSize'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Price size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 14,
			'max'     => 48,
			'inline'  => true,
			'default' => 24,
		];

		$this->controls['ink'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#22301c' ],
		];
	}

	private function layout_controls() {
		$this->controls['maxWidth'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Container width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 400,
			'max'     => 1600,
			'inline'  => true,
			'default' => 1240,
		];

		$this->controls['minHeight'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Minimum height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 200,
			'max'     => 800,
			'inline'  => true,
			'default' => 468,
		];

		$this->controls['padTop'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Space above (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 0,
		];

		$this->controls['overlap'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Overlap the section below (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 300,
			'inline'      => true,
			'default'     => 74,
			'description' => esc_html__( 'The banner sits over the top of whatever follows it — the footer, in the design. Set to 0 for no overlap.', 'pfh-widgets' ),
		];

		$this->controls['padBottom'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Space below (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 56,
		];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$title_top    = $this->copy( 'titleTop' );
		$title_bottom = $this->copy( 'titleBottom' );

		// Nothing to say and nothing to show is not a banner.
		if ( '' === $title_top && '' === $title_bottom && '' === $this->copy( 'text' ) ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-hl pfh-hl--empty"><p>' . esc_html__( 'Give the highlight a title, or pick a product.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$classes = [
			'pfh-hl',
			'pfh-scope',
			'pfh-hl--media-' . (string) $this->get( 'imageSide', 'right' ),
		];

		if ( (int) $this->get( 'overlap', 74 ) > 0 ) {
			$classes[] = 'pfh-hl--overlaps';
		}

		if ( $this->is_on( 'imageBlend' ) ) {
			$classes[] = 'pfh-hl--blend';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-hl__inner">';

		$link  = $this->destination();
		$whole = $this->is_on( 'clickable' ) && '' !== $link['href'];

		printf( '<div class="pfh-hl__card%s">', $whole ? ' is-clickable' : '' );

		echo '<div class="pfh-hl__body">';

		$this->render_eyebrow();
		$this->render_title( $title_top, $title_bottom, $link, $whole );

		$text = $this->copy( 'text' );

		if ( '' !== $text ) {
			echo '<p class="pfh-hl__text">' . esc_html( $text ) . '</p>';
		}

		$this->render_points();
		$this->render_price();
		$this->render_button( $link, $whole );

		echo '</div>';

		$this->render_media();

		echo '</div>';
		echo '</div>';
		echo '</section>';
	}

	private function render_eyebrow() {
		$eyebrow = $this->copy( 'eyebrow' );

		if ( '' === $eyebrow ) {
			return;
		}

		$icon = trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'eyebrowIcon', '' ) ) );

		printf(
			'<p class="pfh-hl__eyebrow">%s<span>%s</span></p>',
			'' !== $icon ? '<span class="pfh-hl__eyebrow-icon" aria-hidden="true">' . esc_html( $icon ) . '</span>' : '',
			esc_html( $eyebrow )
		);
	}

	/**
	 * @param string $top    First line.
	 * @param string $bottom Second line.
	 * @param array  $link   Destination.
	 * @param bool   $whole  Whether the card itself is the link.
	 */
	private function render_title( $top, $bottom, array $link, $whole ) {
		if ( '' === $top && '' === $bottom ) {
			return;
		}

		$tag = (string) $this->get( 'titleTag', 'h2' );
		$tag = in_array( $tag, [ 'h2', 'h3', 'p' ], true ) ? $tag : 'h2';

		$inner = '';

		if ( '' !== $top ) {
			$inner .= '<span class="pfh-hl__title-top">' . esc_html( $top ) . '</span>';
		}

		if ( '' !== $bottom ) {
			$inner .= '<span class="pfh-hl__title-bottom">' . esc_html( $bottom ) . '</span>';
		}

		/*
		 * When the whole card is clickable the link lives on the title and is
		 * stretched over the card with a pseudo-element. That keeps one real
		 * link in the document — a card wrapped in an <a> swallows the text
		 * into the link's own name, which is no use to anyone reading by
		 * keyboard or screen reader.
		 */
		if ( $whole ) {
			$inner = sprintf(
				'<a class="pfh-hl__link" %s>%s</a>',
				PFH_Widgets_Helpers::link_attrs( $link ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().
				$inner
			);
		}

		printf( '<%1$s class="pfh-hl__title">%2$s</%1$s>', $tag, $inner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag whitelisted, inner escaped above.
	}

	private function render_points() {
		$points = $this->get( 'points', [] );
		$points = is_array( $points ) ? $points : [];

		$rows = [];

		foreach ( $points as $row ) {
			$text = isset( $row['text'] ) ? trim( PFH_Widgets_Helpers::dd( (string) $row['text'] ) ) : '';

			if ( '' !== $text ) {
				$rows[] = $text;
			}
		}

		if ( ! $rows ) {
			return;
		}

		echo '<ul class="pfh-hl__points">';

		foreach ( $rows as $text ) {
			printf(
				'<li class="pfh-hl__point">%s<span>%s</span></li>',
				PFH_Widgets_Icons::get( 'check', 'pfh-hl__tick' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				esc_html( $text )
			);
		}

		echo '</ul>';
	}

	private function render_price() {
		$price = $this->prices();

		if ( '' === $price['now'] ) {
			return;
		}

		echo '<div class="pfh-hl__price">';
		echo '<p class="pfh-hl__price-row">';
		echo '<span class="pfh-hl__price-now">' . wp_kses_post( $price['now'] ) . '</span>';

		if ( '' !== $price['was'] ) {
			echo '<span class="pfh-hl__price-was">' . wp_kses_post( $price['was'] ) . '</span>';
		}

		echo '</p>';

		if ( '' !== $price['save'] ) {
			echo '<p class="pfh-hl__save">' . esc_html( $price['save'] ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * @param array $link  Destination.
	 * @param bool  $whole Whether the card itself is already the link.
	 */
	private function render_button( array $link, $whole ) {
		$label = trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'btnLabel', '' ) ) );

		if ( '' === $label ) {
			return;
		}

		// A second link inside a stretched one would be unreachable.
		$tag = ( '' !== $link['href'] && ! $whole ) ? 'a' : 'span';

		printf(
			'<%1$s class="pfh-hl__btn"%2$s>%3$s</%1$s>',
			$tag, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal.
			'a' === $tag ? PFH_Widgets_Helpers::link_attrs( $link ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().
			esc_html( $label )
		);
	}

	private function render_media() {
		$url = $this->image_url();

		if ( '' === $url ) {
			return;
		}

		printf(
			'<div class="pfh-hl__media"><img class="pfh-hl__img" src="%s" alt="%s" loading="lazy" decoding="async" /></div>',
			esc_url( $url ),
			esc_attr( PFH_Widgets_Helpers::dd( (string) $this->get( 'imageAlt', '' ) ) )
		);
	}

	/* ---------------------------------------------------------------------
	 * Data
	 * ------------------------------------------------------------------ */

	/**
	 * The chosen product, if there is one and WooCommerce is here.
	 *
	 * @return \WC_Product|null
	 */
	/**
	 * Every published product, for the picker.
	 *
	 * Built once per request and cached for the hour, because Bricks asks
	 * each element for its controls on every builder load and a shop with a
	 * few hundred products should not be queried each time.
	 *
	 * @return array<string, string>
	 */
	private static function product_options() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! function_exists( 'wc_get_products' ) ) {
			return [];
		}

		$cached = get_transient( 'pfh_highlight_products' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$products = wc_get_products(
			[
				'status'  => 'publish',
				'limit'   => 300,
				'orderby' => 'title',
				'order'   => 'ASC',
				'return'  => 'objects',
			]
		);

		$options = [];

		foreach ( $products as $product ) {
			if ( ! is_object( $product ) ) {
				continue;
			}

			$sku = $product->get_sku();

			$options[ (string) $product->get_id() ] = $sku
				? sprintf( '%s — %s', $product->get_name(), $sku )
				: $product->get_name();
		}

		set_transient( 'pfh_highlight_products', $options, HOUR_IN_SECONDS );

		return $options;
	}

	private function product() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		// A typed ID wins, so a dynamic field can drive this.
		$typed = trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'productId', '' ) ) );
		$id    = (int) ( '' !== $typed ? $typed : PFH_Widgets_Helpers::dd( (string) $this->get( 'product', '' ) ) );

		if ( $id <= 0 ) {
			return null;
		}

		$product = wc_get_product( $id );

		return ( $product && is_object( $product ) ) ? $product : null;
	}

	/**
	 * What the banner shows for money.
	 *
	 * @return array{now:string, was:string, save:string}
	 */
	private function prices() {
		$manual = [
			'now'  => trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'priceManual', '' ) ) ),
			'was'  => trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'oldManual', '' ) ) ),
			'save' => $this->save_line(
				trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'saveFallback', '' ) ) ),
				(float) $this->get( 'savePercentFallback', 0 )
			),
		];

		if ( 'product' !== (string) $this->get( 'priceSource', 'product' ) ) {
			return $manual;
		}

		$product = $this->product();

		if ( ! $product || ! function_exists( 'wc_price' ) ) {
			return $manual;
		}

		$now = wc_get_price_to_display( $product );
		$was = wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] );

		if ( '' === $now || null === $now ) {
			return $manual;
		}

		$saving  = (float) $was - (float) $now;
		$percent = (float) $was > 0 ? ( $saving / (float) $was ) * 100 : 0.0;

		return [
			'now'  => wc_price( $now ),
			// Only worth showing when the product is actually reduced.
			'was'  => $saving > 0 ? wc_price( $was ) : '',
			'save' => $saving > 0
				? $this->save_line( wp_strip_all_tags( wc_price( $saving ) ), $percent )
				: '',
		];
	}

	/**
	 * @param string $amount Already formatted.
	 * @return string
	 */
	/**
	 * @param string $amount  Already formatted, e.g. "€5,00".
	 * @param float  $percent Discount as a percentage, 0 when unknown.
	 * @return string
	 */
	private function save_line( $amount, $percent = 0.0 ) {
		$template = trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'saveText', '' ) ) );

		if ( '' === $template || '' === $amount ) {
			return '';
		}

		/*
		 * str_replace rather than sprintf: the line carries a per-cent sign
		 * of its own — "Bespaar €5,00 — 11% korting" — and sprintf reads that
		 * as a conversion and mangles the rest of the sentence.
		 */
		$line = str_replace( '%s', $amount, $template );

		if ( false !== strpos( $line, '%pct%' ) ) {
			$line = $percent > 0
				? str_replace( '%pct%', (string) (int) round( $percent ), $line )
				// No discount to name, so the clause naming it goes too.
				: trim( preg_replace( '/\s*[—–-]?\s*[^—–-]*%pct%[^—–-]*/u', '', $line ) );
		}

		return trim( $line );
	}

	/**
	 * Where the banner points: the typed link, else the chosen product.
	 *
	 * @return array{href:string, target:string, rel:string, aria:string}
	 */
	private function destination() {
		$link = PFH_Widgets_Helpers::link( $this->get( 'link' ) );

		if ( '' !== $link['href'] ) {
			return $link;
		}

		$product = $this->product();

		if ( $product ) {
			$link['href'] = (string) $product->get_permalink();
		}

		return $link;
	}

	/**
	 * The chosen image, else the supplied one.
	 *
	 * @return string
	 */
	private function image_url() {
		$chosen = PFH_Widgets_Helpers::image_url( $this->get( 'image' ), 'large' );

		if ( $chosen ) {
			return $chosen;
		}

		return PFH_Widgets_Assets::img( 'pfh-highlight.jpg' );
	}

	/**
	 * A copy field, run through dynamic data and trimmed.
	 *
	 * @param string $key Control name.
	 * @return string
	 */
	private function copy( $key ) {
		return trim( PFH_Widgets_Helpers::dd( (string) $this->get( $key, '' ) ) );
	}

	private function build_vars() {
		$share = max( 20, min( 70, (int) $this->get( 'imageWidth', 50 ) ) );

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-hl-max'     => PFH_Widgets_Helpers::unit( $this->get( 'maxWidth', 1240 ) ),
				'--pfh-hl-min-h'   => PFH_Widgets_Helpers::unit( $this->get( 'minHeight', 468 ) ),
				'--pfh-hl-pt'      => PFH_Widgets_Helpers::unit( $this->get( 'padTop', 0 ) ),
				'--pfh-hl-pb'      => PFH_Widgets_Helpers::unit( $this->get( 'padBottom', 56 ) ),
				'--pfh-hl-overlap' => PFH_Widgets_Helpers::unit( max( 0, (int) $this->get( 'overlap', 74 ) ) ),
				'--pfh-hl-bg'      => PFH_Widgets_Helpers::color( $this->get( 'bg' ), '#d9e6dc' ),
				'--pfh-hl-radius'  => PFH_Widgets_Helpers::unit( $this->get( 'radius', 20 ) ),
				'--pfh-hl-px-set'      => PFH_Widgets_Helpers::unit( $this->get( 'padX', 52 ) ),
				'--pfh-hl-py-set'      => PFH_Widgets_Helpers::unit( $this->get( 'padY', 48 ) ),
				'--pfh-hl-media-w' => $share . '%',
				'--pfh-hl-fade'    => max( 2, min( 45, (int) $this->get( 'imageFade', 14 ) ) ) . '%',
				'--pfh-hl-ink'     => PFH_Widgets_Helpers::color( $this->get( 'ink' ), '#22301c' ),
				'--pfh-hl-eyebrow-set' => PFH_Widgets_Helpers::unit( $this->get( 'eyebrowSize', 22 ) ),
				'--pfh-hl-title-set'   => PFH_Widgets_Helpers::unit( $this->get( 'titleSize', 32 ) ),
				'--pfh-hl-text'    => PFH_Widgets_Helpers::unit( $this->get( 'textSize', 14 ) ),
				'--pfh-hl-price'   => PFH_Widgets_Helpers::unit( $this->get( 'priceSize', 24 ) ),
				'--pfh-hl-point'   => PFH_Widgets_Helpers::color( $this->get( 'pointColor' ), '#3f4c3e' ),
				'--pfh-hl-tick'    => PFH_Widgets_Helpers::color( $this->get( 'tickColor' ), '#7d9569' ),
			]
		);
	}

	private function get( $key, $default = null ) {
		return $this->setting( $key, $default );
	}

	private function is_on( $key, $default = true ) {
		if ( ! array_key_exists( $key, (array) $this->settings ) ) {
			if ( isset( $this->controls[ $key ] ) && array_key_exists( 'default', $this->controls[ $key ] ) ) {
				return ! empty( $this->controls[ $key ]['default'] );
			}

			return $default;
		}

		return ! empty( $this->settings[ $key ] );
	}
}
