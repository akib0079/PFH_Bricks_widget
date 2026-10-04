<?php
/**
 * Bricks element: Products For Home recipe cards.
 *
 * A heading with the shop's italic accent over a grid of recipe cards — a
 * photograph, a name, one line on what it is, and a link to the recipe. Made
 * for "Recepten van Gia Giamas", the page that leads to the six recipes, when
 * it moved from Elementor to Bricks (2026-10-02). Three cards across on a
 * desktop, two on a tablet; on a phone each card is a row, photo beside the
 * words, so six recipes do not make six screens of scrolling.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Recipes extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-recipes';
	public $icon         = 'ti-layout-grid3';
	public $css_selector = '.pfh-rc';

	public function get_label() {
		return esc_html__( 'PFH Recipe Cards', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'recipe', 'recipes', 'recepten', 'cards', 'grid', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::recipes();
	}

	/**
	 * The six recipes the page links to, as they are named on the site.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function default_items() {
		return [
			[
				'title' => 'Vruchtensap & gezonde frisdrank',
				'text'  => 'Zo maak je thuis een glas Gia Giamas, aangelengd met water of bruisend met soda.',
				'link'  => [ 'type' => 'external', 'url' => '/recepten-van-gia-giamas/home-made-juice/' ],
			],
			[
				'title' => 'Cocktails',
				'text'  => 'Fruitige cocktails en mocktails met Gia Giamas als basis.',
				'link'  => [ 'type' => 'external', 'url' => '/recepten-van-gia-giamas/gia-giamas-cocktails/' ],
			],
			[
				'title' => 'Granita slush',
				'text'  => 'IJskoud en vol fruit: zo maak je een granita van Gia Giamas.',
				'link'  => [ 'type' => 'external', 'url' => '/recepten-van-gia-giamas/home-made-slush/' ],
			],
			[
				'title' => 'Water of soda to go',
				'text'  => 'Een fles water of soda met een scheut Gia Giamas, voor onderweg.',
				'link'  => [ 'type' => 'external', 'url' => '/recepten-van-gia-giamas/gia-giamas-to-go/' ],
			],
			[
				'title' => 'Griekse yoghurt',
				'text'  => 'Een scheut Gia Giamas door je yoghurt, als ontbijt of als toetje.',
				'link'  => [ 'type' => 'external', 'url' => '/recepten-van-gia-giamas/greek-yoghurt/' ],
			],
			[
				'title' => 'Ice tea',
				'text'  => 'Huisgemaakte ice tea met de frisse smaak van Grieks fruit.',
				'link'  => [ 'type' => 'external', 'url' => '/recepten-van-gia-giamas/home-made-ice-tea/' ],
			],
		];
	}

	public function set_control_groups() {
		$this->control_groups['head']  = [ 'title' => esc_html__( 'Heading', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['cards'] = [ 'title' => esc_html__( 'Recipes', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['style'] = [ 'title' => esc_html__( 'Style', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->controls['eyebrow'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Small line above', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Recepten',
		];

		$this->controls['heading'] = [
			'tab'         => 'content',
			'group'       => 'head',
			'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'Wat maak je met <em>Gia Giamas</em>?',
			'description' => esc_html__( 'Put a word between <em> and </em> for the italic accent, as in the other headings.', 'pfh-widgets' ),
		];

		$this->controls['lede'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Text under the heading', 'pfh-widgets' ),
			'type'    => 'textarea',
			'default' => 'Kies een recept en lees stap voor stap hoe je het maakt.',
		];

		$this->controls['items'] = [
			'tab'           => 'content',
			'group'         => 'cards',
			'label'         => esc_html__( 'Recipes', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'default'       => self::default_items(),
			'fields'        => [
				'image' => [
					'label' => esc_html__( 'Photo', 'pfh-widgets' ),
					'type'  => 'image',
				],
				'title' => [
					'label' => esc_html__( 'Name', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'text'  => [
					'label' => esc_html__( 'One line about it', 'pfh-widgets' ),
					'type'  => 'textarea',
				],
				'link'  => [
					'label' => esc_html__( 'Recipe page', 'pfh-widgets' ),
					'type'  => 'link',
				],
			],
		];

		$this->controls['moreLabel'] = [
			'tab'     => 'content',
			'group'   => 'cards',
			'label'   => esc_html__( 'Link wording', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Bekijk recept',
		];

		$this->controls['columns'] = [
			'tab'     => 'content',
			'group'   => 'cards',
			'label'   => esc_html__( 'Cards across on a desktop', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 2,
			'max'     => 4,
			'default' => 3,
		];

		foreach ( [
			'headColor'  => [ esc_html__( 'Heading', 'pfh-widgets' ), '#22301c' ],
			'accent'     => [ esc_html__( 'Heading accent and eyebrow', 'pfh-widgets' ), '#557f82' ],
			'nameColor'  => [ esc_html__( 'Recipe name', 'pfh-widgets' ), '#22301c' ],
			'textColor'  => [ esc_html__( 'Text', 'pfh-widgets' ), '#5c6657' ],
			'linkColor'  => [ esc_html__( 'Link', 'pfh-widgets' ), '#3c868c' ],
			'mediaBg'    => [ esc_html__( 'Photo background', 'pfh-widgets' ), '#f4f4f4' ],
			'background' => [ esc_html__( 'Section background', 'pfh-widgets' ), '#ffffff' ],
		] as $key => $spec ) {
			$this->controls[ $key ] = [
				'tab'     => 'content',
				'group'   => 'style',
				'label'   => $spec[0],
				'type'    => 'color',
				'default' => [ 'hex' => $spec[1] ],
			];
		}

		$this->controls['padTop'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Space above (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'default' => 72,
		];

		$this->controls['padBottom'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Space below (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'default' => 72,
		];
	}

	public function render() {
		if ( class_exists( 'PFH_Widgets_License' ) && PFH_Widgets_License::locked() ) {
			echo PFH_Widgets_License::locked_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped in locked_markup().

			return;
		}

		$items = $this->setting( 'items', [] );
		$items = is_array( $items ) ? array_values( array_filter( $items, 'is_array' ) ) : [];

		if ( ! $items ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-rc pfh-rc--empty"><p>' . esc_html__( 'Add a recipe in the Recipes group.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-rc', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		$eyebrow = trim( (string) PFH_Widgets_Helpers::dd( (string) $this->setting( 'eyebrow', '' ) ) );
		$heading = trim( (string) PFH_Widgets_Helpers::dd( (string) $this->setting( 'heading', '' ) ) );
		$lede    = trim( (string) PFH_Widgets_Helpers::dd( (string) $this->setting( 'lede', '' ) ) );
		$more    = trim( (string) $this->setting( 'moreLabel', '' ) );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-rc__inner">';

		if ( '' !== $eyebrow || '' !== $heading || '' !== $lede ) {
			echo '<header class="pfh-rc__head">';

			if ( '' !== $eyebrow ) {
				echo '<p class="pfh-rc__eyebrow">' . esc_html( $eyebrow ) . '</p>';
			}

			if ( '' !== $heading ) {
				echo '<h2 class="pfh-rc__title">' . wp_kses( $heading, [ 'em' => [], 'i' => [], 'br' => [], 'strong' => [] ] ) . '</h2>';
			}

			if ( '' !== $lede ) {
				echo '<p class="pfh-rc__lede">' . esc_html( $lede ) . '</p>';
			}

			echo '</header>';
		}

		echo '<ul class="pfh-rc__grid">';

		foreach ( $items as $item ) {
			$this->render_card( $item, $more );
		}

		echo '</ul>';
		echo '</div>';
		echo '</section>';
	}

	/**
	 * One recipe: the whole card is the link.
	 *
	 * @param array  $item Repeater row.
	 * @param string $more Link wording.
	 */
	private function render_card( array $item, $more ) {
		$title = trim( (string) PFH_Widgets_Helpers::dd( isset( $item['title'] ) ? (string) $item['title'] : '' ) );
		$text  = trim( (string) PFH_Widgets_Helpers::dd( isset( $item['text'] ) ? (string) $item['text'] : '' ) );
		$link  = PFH_Widgets_Helpers::link( isset( $item['link'] ) ? $item['link'] : null );
		$tag   = '' !== $link['href'] ? 'a' : 'div';

		if ( '' === $title && '' === $text ) {
			return;
		}

		echo '<li class="pfh-rc__item">';
		echo '<' . $tag . ' class="pfh-rc__card"' . ( 'a' === $tag ? PFH_Widgets_Helpers::link_attrs( $link ) : '' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().
		echo '<span class="pfh-rc__media">' . $this->picture( isset( $item['image'] ) ? $item['image'] : null, $title ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped in picture().
		echo '<span class="pfh-rc__body">';

		if ( '' !== $title ) {
			echo '<h3 class="pfh-rc__name">' . esc_html( $title ) . '</h3>';
		}

		if ( '' !== $text ) {
			echo '<span class="pfh-rc__text">' . esc_html( $text ) . '</span>';
		}

		if ( 'a' === $tag && '' !== $more ) {
			echo '<span class="pfh-rc__more">' . esc_html( $more ) . PFH_Widgets_Icons::get( 'arrow', 'pfh-rc__arrow' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		}

		echo '</span>';
		echo '</' . $tag . '>';
		echo '</li>';
	}

	/**
	 * The card's photograph, sharp at every width, or an empty tile.
	 *
	 * @param mixed  $image Image control value.
	 * @param string $alt   Fallback alternative text.
	 * @return string
	 */
	private function picture( $image, $alt ) {
		$id = is_array( $image ) && ! empty( $image['id'] ) ? (int) $image['id'] : 0;

		if ( $id && wp_attachment_is_image( $id ) ) {
			$own = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );

			return wp_get_attachment_image(
				$id,
				'large',
				false,
				[
					'class'    => 'pfh-rc__img',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 575px) 112px, (max-width: 991px) 50vw, 380px',
					'alt'      => '' !== $own ? $own : $alt,
				]
			);
		}

		$url = PFH_Widgets_Helpers::image_url( $image, 'large' );

		return '' !== $url
			? '<img class="pfh-rc__img" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async" />'
			: '';
	}

	/**
	 * @return string
	 */
	private function build_vars() {
		$vars = [
			'--pfh-rc-cols'   => (string) max( 2, min( 4, (int) $this->setting( 'columns', 3 ) ) ),
			'--pfh-rc-head'   => PFH_Widgets_Helpers::color( $this->setting( 'headColor' ), '#22301c' ),
			'--pfh-rc-accent' => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#557f82' ),
			'--pfh-rc-name'   => PFH_Widgets_Helpers::color( $this->setting( 'nameColor' ), '#22301c' ),
			'--pfh-rc-text'   => PFH_Widgets_Helpers::color( $this->setting( 'textColor' ), '#5c6657' ),
			'--pfh-rc-link'   => PFH_Widgets_Helpers::color( $this->setting( 'linkColor' ), '#3c868c' ),
			'--pfh-rc-media'  => PFH_Widgets_Helpers::color( $this->setting( 'mediaBg' ), '#f4f4f4' ),
			'--pfh-rc-bg'     => PFH_Widgets_Helpers::color( $this->setting( 'background' ), '#ffffff' ),
			'--pfh-rc-pad-t'  => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 72 ) ),
			'--pfh-rc-pad-b'  => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 72 ) ),
		];

		return PFH_Widgets_Helpers::css_vars( $vars );
	}
}
