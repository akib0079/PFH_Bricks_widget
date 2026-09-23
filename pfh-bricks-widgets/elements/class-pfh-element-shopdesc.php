<?php
/**
 * Bricks element: Products For Home collection description.
 *
 * The long copy under the grid, clamped to a few lines with a "read more"
 * button when it runs on. The clamp is CSS line-clamp with a JavaScript
 * measurement on top, so the button only appears when the text genuinely
 * overflows rather than on every page whether it is needed or not.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Shopdesc extends \Bricks\Element {

	use PFH_Element_Defaults;

	/*
	 * Fallback copy, used until the ACF fields are connected.
	 *
	 * Written per category rather than as one paragraph repeated across the
	 * shop: identical text on every category page is duplicate content, so a
	 * single generic block would cost more in search than showing nothing.
	 * %category% carries the term name through, which keeps each page's copy
	 * distinct and its keyword the one that page is actually about.
	 */
	const FALLBACK_BODY = '<p>Op zoek naar %category%? Bij %shop% vind je een zorgvuldig samengestelde selectie, rechtstreeks uit Griekenland. We werken samen met kleine familiebedrijven die hun producten nog op traditionele wijze maken — zonder onnodige toevoegingen, met respect voor het originele recept.</p><p>Elke bestelling wordt met zorg ingepakt en verstuurd vanuit ons eigen magazijn. Vragen over een product of advies nodig bij het kiezen? Neem gerust contact met ons op — we denken graag met je mee.</p>';

	const FALLBACK_SHOP_BODY = '<p>%shop% brengt de smaak van Griekenland naar je keuken. In ons assortiment vind je pure Griekse honing, extra vierge olijfolie en de vruchtenconcentraten van Gia Giamas — stuk voor stuk gemaakt door kleine producenten die hun vak al generaties lang verstaan.</p><p>Alles wat we verkopen proeven we eerst zelf. Geen massaproductie, geen onnodige toevoegingen: alleen producten waar we achter staan, zorgvuldig ingepakt en snel bezorgd.</p>';

	public $category     = 'products-for-home';
	public $name         = 'pfh-shopdesc';
	public $icon         = 'ti-align-left';
	public $css_selector = '.pfh-shopdesc';

	public function get_label() {
		return esc_html__( 'PFH Collection Description', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'shop', 'collection', 'category', 'description', 'read more', 'seo', 'text', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::shop();
	}

	public function set_control_groups() {
		$this->control_groups['content'] = [ 'title' => esc_html__( 'Content', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['fallback'] = [ 'title' => esc_html__( 'Fallback copy', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['clamp']   = [ 'title' => esc_html__( 'Read more', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout']  = [ 'title' => esc_html__( 'Layout', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->controls['eyebrow'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Small heading', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'GIA GIAMAS',
			'description' => esc_html__( 'Dynamic data is supported. Leave empty to hide it.', 'pfh-widgets' ),
		];

		$this->controls['eyebrowSize'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Small heading size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 32,
			'inline'  => true,
			'default' => 11,
		];

		$this->controls['eyebrowColor'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Small heading colour', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['title'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => '',
			'description' => esc_html__( 'Leave empty to use the fallback heading below. Dynamic data is supported.', 'pfh-widgets' ),
		];

		$this->controls['titleTag'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Heading tag', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'h2' => 'H2',
				'h3' => 'H3',
				'p'  => esc_html__( 'Not a heading', 'pfh-widgets' ),
			],
			'default' => 'h2',
		];

		$this->controls['titleSize'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Heading size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 14,
			'max'     => 48,
			'inline'  => true,
			'default' => 24,
		];

		$this->controls['source'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Body text', 'pfh-widgets' ),
			'type'    => 'select',
			'options' => [
				'auto'   => esc_html__( 'The category description', 'pfh-widgets' ),
				'manual' => esc_html__( 'The text below', 'pfh-widgets' ),
			],
			'default' => 'auto',
		];

		$this->controls['body'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Text', 'pfh-widgets' ),
			'type'        => 'editor',
			'default'     => '',
			'description' => esc_html__( 'Used when there is no category description, and in the builder.', 'pfh-widgets' ),
		];

		$this->controls['textSize'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Text size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['textColor'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['titleColor'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Heading colour', 'pfh-widgets' ),
			'type'        => 'color',
			'description' => esc_html__( 'The title, and any headings inside the text.', 'pfh-widgets' ),
		];

		$this->controls['linkColor'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Link colour', 'pfh-widgets' ),
			'type'  => 'color',
		];

		/* ---- fallback ---- */

		$this->controls['fallbackEnable'] = [
			'tab'         => 'content',
			'group'       => 'fallback',
			'label'       => esc_html__( 'Write the text when nothing is connected', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Until the ACF fields are connected, every category still needs copy that reads properly and gives search engines something to index.', 'pfh-widgets' ),
		];

		$this->controls['fallbackTitle'] = [
			'tab'         => 'content',
			'group'       => 'fallback',
			'label'       => esc_html__( 'Fallback heading', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => '%category% kopen bij %shop%',
			'required'    => [ 'fallbackEnable', '=', true ],
			'description' => esc_html__( '%category% is the category being viewed, %shop% the shop name.', 'pfh-widgets' ),
		];

		$this->controls['fallbackBody'] = [
			'tab'      => 'content',
			'group'    => 'fallback',
			'label'    => esc_html__( 'Fallback text', 'pfh-widgets' ),
			'type'     => 'editor',
			'default'  => self::FALLBACK_BODY,
			'required' => [ 'fallbackEnable', '=', true ],
		];

		$this->controls['fallbackShopTitle'] = [
			'tab'         => 'content',
			'group'       => 'fallback',
			'label'       => esc_html__( 'Fallback heading, shop page', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'Griekse delicatessen van %shop%',
			'required'    => [ 'fallbackEnable', '=', true ],
			'description' => esc_html__( 'The shop page has no category, so it gets its own line.', 'pfh-widgets' ),
		];

		$this->controls['fallbackShopBody'] = [
			'tab'      => 'content',
			'group'    => 'fallback',
			'label'    => esc_html__( 'Fallback text, shop page', 'pfh-widgets' ),
			'type'     => 'editor',
			'default'  => self::FALLBACK_SHOP_BODY,
			'required' => [ 'fallbackEnable', '=', true ],
		];

		/* ---- clamp ---- */

		$this->controls['clampEnable'] = [
			'tab'     => 'content',
			'group'   => 'clamp',
			'label'   => esc_html__( 'Collapse long text', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['lines'] = [
			'tab'         => 'content',
			'group'       => 'clamp',
			'label'       => esc_html__( 'Lines before collapsing', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 2,
			'max'         => 40,
			'inline'      => true,
			'default'     => 8,
			'required'    => [ 'clampEnable', '=', true ],
			'description' => esc_html__( 'The button only appears when the text is actually longer than this.', 'pfh-widgets' ),
		];

		$this->controls['moreLabel'] = [
			'tab'      => 'content',
			'group'    => 'clamp',
			'label'    => esc_html__( 'Expand label', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'LEES MEER',
			'required' => [ 'clampEnable', '=', true ],
		];

		$this->controls['lessLabel'] = [
			'tab'      => 'content',
			'group'    => 'clamp',
			'label'    => esc_html__( 'Collapse label', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'LEES MINDER',
			'required' => [ 'clampEnable', '=', true ],
		];

		$this->controls['moreColor'] = [
			'tab'      => 'content',
			'group'    => 'clamp',
			'label'    => esc_html__( 'Button colour', 'pfh-widgets' ),
			'type'     => 'color',
			'required' => [ 'clampEnable', '=', true ],
		];

		/* ---- layout ---- */

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

		$this->controls['padTop'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Space above (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 40,
		];

		$this->controls['padBottom'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Space below (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 32,
		];
	}

	public function render() {
		$body  = $this->body_html();
		$title = $this->title_text();

		if ( '' === trim( wp_strip_all_tags( $body ) ) && '' === $title ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-shopdesc pfh-shopdesc--empty"><p>' . esc_html__( 'No description yet. Add one to the category, type it in the panel, or switch the fallback copy on.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$clamp = $this->is_on( 'clampEnable' );

		$classes = [ 'pfh-shopdesc', 'pfh-scope' ];

		if ( $clamp ) {
			$classes[] = 'is-clamped';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-shopdesc__inner"' . ( $clamp ? ' data-pfh-clamp' : '' ) . '>';

		$eyebrow = trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'eyebrow', '' ) ) );

		if ( '' !== $eyebrow ) {
			echo '<p class="pfh-shopdesc__eyebrow">' . esc_html( $eyebrow ) . '</p>';
		}

		if ( '' !== $title ) {
			$tag = (string) $this->get( 'titleTag', 'h2' );
			$tag = in_array( $tag, [ 'h2', 'h3', 'p' ], true ) ? $tag : 'h2';

			printf(
				'<%1$s class="pfh-shopdesc__title">%2$s</%1$s>',
				$tag, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted above.
				esc_html( $title )
			);
		}

		if ( '' !== trim( wp_strip_all_tags( $body ) ) ) {
			echo '<div class="pfh-shopdesc__body" data-pfh-clamp-body>' . wp_kses_post( $body ) . '</div>';
		}

		if ( $clamp ) {
			printf(
				'<button type="button" class="pfh-shopdesc__more" data-pfh-clamp-toggle aria-expanded="false" data-more="%1$s" data-less="%2$s" hidden>%1$s</button>',
				esc_attr( (string) $this->get( 'moreLabel', 'LEES MEER' ) ),
				esc_attr( (string) $this->get( 'lessLabel', 'LEES MINDER' ) )
			);
		}

		echo '</div>';
		echo '</section>';
	}

	/**
	 * The copy, from the term or the panel.
	 *
	 * @return string
	 */
	private function body_html() {
		if ( 'auto' === (string) $this->get( 'source', 'auto' ) && is_tax( 'product_cat' ) ) {
			$term = get_queried_object();

			if ( $term && ! is_wp_error( $term ) && '' !== trim( (string) $term->description ) ) {
				return wpautop( (string) $term->description );
			}
		}

		$typed = (string) $this->get( 'body', '' );

		if ( '' !== trim( $typed ) ) {
			return PFH_Widgets_Helpers::dd( $typed );
		}

		if ( ! $this->is_on( 'fallbackEnable' ) ) {
			return '';
		}

		$key = $this->on_category() ? 'fallbackBody' : 'fallbackShopBody';
		$raw = (string) $this->get( $key, 'fallbackBody' === $key ? self::FALLBACK_BODY : self::FALLBACK_SHOP_BODY );

		return '' === trim( $raw ) ? '' : $this->fill( $raw );
	}

	/**
	 * The heading, typed or written for this page.
	 *
	 * @return string
	 */
	private function title_text() {
		$typed = trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'title', '' ) ) );

		if ( '' !== $typed ) {
			return $typed;
		}

		if ( ! $this->is_on( 'fallbackEnable' ) ) {
			return '';
		}

		$key = $this->on_category() ? 'fallbackTitle' : 'fallbackShopTitle';
		$raw = (string) $this->get( $key, 'fallbackTitle' === $key ? '%category% kopen bij %shop%' : 'Griekse delicatessen van %shop%' );

		return trim( wp_strip_all_tags( $this->fill( $raw ) ) );
	}

	/**
	 * Is there a category to write about?
	 *
	 * @return bool
	 */
	private function on_category() {
		return is_tax( 'product_cat' ) || is_tax( 'product_tag' );
	}

	/**
	 * Put this page's own words into a fallback template.
	 *
	 * @param string $text Template.
	 * @return string
	 */
	private function fill( $text ) {
		$category = '';

		if ( $this->on_category() ) {
			$term = get_queried_object();

			if ( $term && ! is_wp_error( $term ) && ! empty( $term->name ) ) {
				$category = (string) $term->name;
			}
		}

		// In the builder there is no archive to read, so the preview would
		// otherwise show a sentence with a hole in it.
		if ( '' === $category ) {
			$category = esc_html__( 'Griekse delicatessen', 'pfh-widgets' );
		}

		$shop = trim( (string) get_bloginfo( 'name' ) );

		if ( '' === $shop ) {
			$shop = 'Products for Home';
		}

		return str_replace(
			[ '%category%', '%shop%' ],
			[ $category, $shop ],
			$text
		);
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-sd-max'      => PFH_Widgets_Helpers::unit( $this->get( 'maxWidth', 1140 ) ),
				'--pfh-sd-pt'       => PFH_Widgets_Helpers::unit( $this->get( 'padTop', 40 ) ),
				'--pfh-sd-pb'       => PFH_Widgets_Helpers::unit( $this->get( 'padBottom', 32 ) ),
				'--pfh-sd-lines'    => (int) $this->get( 'lines', 8 ),
				'--pfh-sd-size'     => PFH_Widgets_Helpers::unit( $this->get( 'textSize', 14 ) ),
				'--pfh-sd-title'    => PFH_Widgets_Helpers::unit( $this->get( 'titleSize', 24 ) ),
				'--pfh-sd-color'    => PFH_Widgets_Helpers::color( $this->get( 'textColor' ) ),
				'--pfh-sd-title-color' => PFH_Widgets_Helpers::color( $this->get( 'titleColor' ) ),
				'--pfh-sd-link'     => PFH_Widgets_Helpers::color( $this->get( 'linkColor' ) ),
				'--pfh-sd-eyebrow'  => PFH_Widgets_Helpers::unit( $this->get( 'eyebrowSize', 11 ) ),
				'--pfh-sd-eye-color' => PFH_Widgets_Helpers::color( $this->get( 'eyebrowColor' ) ),
				'--pfh-sd-more'     => PFH_Widgets_Helpers::color( $this->get( 'moreColor' ) ),
			]
		);
	}

	private function get( $key, $default = null ) {
		return $this->setting( $key, $default );
	}

	private function is_on( $key, $default = true ) {
		return $this->switched_on( $key, $default );
	}
}
