<?php
/**
 * Bricks element: Products For Home about page.
 *
 * An opening, a run of sections that alternate a picture with their text, and
 * a band of the awards the shop has won. Every part of it is a field, so the
 * story can be rewritten without anyone opening a template.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_About extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-about';
	public $icon         = 'ti-layout-media-left-alt';
	public $css_selector = '.pfh-about';

	public function get_label() {
		return esc_html__( 'PFH About', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'about', 'story', 'over ons', 'awards', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::about();
	}

	public function set_control_groups() {
		foreach ( [
			'head'   => esc_html__( 'Opening', 'pfh-widgets' ),
			'blocks' => esc_html__( 'Sections', 'pfh-widgets' ),
			'awards' => esc_html__( 'Awards', 'pfh-widgets' ),
			'style'  => esc_html__( 'Style', 'pfh-widgets' ),
			'layout' => esc_html__( 'Layout', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		/* ---- opening ---- */

		$this->controls['eyebrow'] = $this->text( 'head', esc_html__( 'Eyebrow', 'pfh-widgets' ), 'Over ons' );

		$this->controls['title'] = $this->text(
			'head',
			esc_html__( 'Title', 'pfh-widgets' ),
			'Wat is Products for Home <em>voor winkel?</em>',
			[ 'description' => esc_html__( 'A word wrapped in <em> is set in the italic serif.', 'pfh-widgets' ) ]
		);

		$this->controls['lede'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Opening paragraph', 'pfh-widgets' ),
			'type'    => 'editor',
			'default' => 'De naam zegt het natuurlijk al — producten voor in huis. Maar over wat soort producten hebben wij het dan? Dit zijn enkel en alleen natuurproducten zoals pure onbewerkte honing, extra vierge olijfolie, pure bijenwas kaarsen en nog veel meer!',
		];

		/* ---- the sections ---- */

		$this->controls['blocks'] = [
			'tab'           => 'content',
			'group'         => 'blocks',
			'label'         => esc_html__( 'Sections', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'default'       => [
				[
					'title' => 'Uit passie voor Griekenland',
					'text'  => '<p>Dit bedrijf is opgezet uit een passie die we hebben met natuurproducten en Griekenland. Deze passie willen we graag met zoveel mogelijk mensen delen. Zelf kom ik uit Samos en een deel van mijn familie woont daar nog. Veel van onze producten komen dan ook van het mooie Griekse eiland Samos.</p><p>In de oudheid stond Samos bekend als het land dat bloeit, vanwege de vruchtbare grond die zich op het eiland bevindt. Op dit prachtige groene eiland maken ze niet alleen de heerlijke Samos wijn maar ook de honing en olijfolie, en er bloeien talloze kruiden.</p>',
				],
				[
					'title' => 'Ons <em>verhaal</em>',
					'text'  => '<p>Ons verhaal begon in 2020 toen we besloten een testfase te doen om te kijken of het zou aanslaan bij de mensen. Na een kleine testzending van onze pijnboomhoning was dat wel duidelijk: het meeste was al verkocht voordat het überhaupt binnen was!</p><p>Deze prachtige honing en zijn unieke smaak zijn een echte aanrader — iedereen die het heeft geproefd vindt het heerlijk. Sinds we dit zijn gaan verkopen hebben wij de honing aan mensen laten proeven, met groot succes. Hieruit hebben we een vaste klantenkring kunnen opbouwen, en door de goede verhalen over onze honing komen er steeds meer nieuwe klanten bij. Daar zijn wij onwijs trots op!</p><p>Vanuit hier zijn wij ons assortiment gaan uitbreiden met de heerlijke extra vierge olijfolie, gemaakt van jonge olijven. Nu verkopen we ook streekproducten, andere soorten honing en andere natuurproducten.</p>',
				],
			],
			'fields'        => [
				'title' => [ 'label' => esc_html__( 'Heading', 'pfh-widgets' ), 'type' => 'text' ],
				'text'  => [ 'label' => esc_html__( 'Text', 'pfh-widgets' ), 'type' => 'editor' ],
				'image' => [ 'label' => esc_html__( 'Picture', 'pfh-widgets' ), 'type' => 'image' ],
			],
			'description'   => esc_html__( 'Sections with a picture alternate sides. A section without one is a column of text.', 'pfh-widgets' ),
		];

		/* ---- awards ---- */

		$this->controls['awardsTitle'] = $this->text(
			'awards',
			esc_html__( 'Heading', 'pfh-widgets' ),
			'Door de uitstekende kwaliteit van onze producten beschikken we over <em>mooie prijzen</em>'
		);

		$this->controls['awardsLede'] = [
			'tab'     => 'content',
			'group'   => 'awards',
			'label'   => esc_html__( 'Text', 'pfh-widgets' ),
			'type'    => 'editor',
			'default' => 'Bij Products for Home streven we naar producten van de beste kwaliteit en zorgen we ervoor dat onze producten zo natuurlijk mogelijk zijn. Dat houdt in dat de producten weinig tot niet bewerkt mogen zijn en alles puur natuur moet zijn. Hierdoor zijn wij ook erg selectief met wat wij in ons assortiment opnemen.',
		];

		$this->controls['awards'] = [
			'tab'           => 'content',
			'group'         => 'awards',
			'label'         => esc_html__( 'Awards', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'label',
			'default'       => [],
			'fields'        => [
				'image' => [ 'label' => esc_html__( 'Badge', 'pfh-widgets' ), 'type' => 'image' ],
				'label' => [ 'label' => esc_html__( 'Caption', 'pfh-widgets' ), 'type' => 'text' ],
				'link'  => [ 'label' => esc_html__( 'Link', 'pfh-widgets' ), 'type' => 'text' ],
			],
			'description'   => esc_html__( 'Upload the badges here. With none, the band shows only its heading and text; with neither, it is left off the page.', 'pfh-widgets' ),
		];

		$this->controls['awardSize'] = $this->number( 'awards', esc_html__( 'Badge size (px)', 'pfh-widgets' ), 96, 48, 200 );

		/* ---- style ---- */

		$this->controls['ink']      = $this->colour( 'style', esc_html__( 'Headings', 'pfh-widgets' ), '#22301c' );
		$this->controls['bodyInk']  = $this->colour( 'style', esc_html__( 'Body text', 'pfh-widgets' ), '#5c6657' );
		$this->controls['mutedInk'] = $this->colour( 'style', esc_html__( 'Quiet text', 'pfh-widgets' ), '#8d9589' );
		$this->controls['accent']   = $this->colour( 'style', esc_html__( 'Links', 'pfh-widgets' ), '#2b5f63' );
		$this->controls['band']     = $this->colour( 'style', esc_html__( 'Awards band', 'pfh-widgets' ), '#f6f8f5' );

		$this->controls['titleSize'] = $this->number( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 40, 22, 64 );
		$this->controls['headSize']  = $this->number( 'style', esc_html__( 'Heading size (px)', 'pfh-widgets' ), 28, 18, 44 );
		$this->controls['radius']    = $this->number( 'style', esc_html__( 'Picture radius (px)', 'pfh-widgets' ), 16, 0, 40 );

		$this->controls['ratio'] = [
			'tab'         => 'content',
			'group'       => 'style',
			'label'       => esc_html__( 'Picture shape', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'default'     => '4 / 3',
			'options'     => [
				'4 / 3'  => esc_html__( 'Landscape', 'pfh-widgets' ),
				'1 / 1'  => esc_html__( 'Square', 'pfh-widgets' ),
				'3 / 4'  => esc_html__( 'Portrait', 'pfh-widgets' ),
				'16 / 9' => esc_html__( 'Wide', 'pfh-widgets' ),
			],
		];

		/* ---- layout ---- */

		$this->controls['maxWidth'] = $this->number( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1140, 600, 1600 );
		$this->controls['gap']      = $this->number( 'layout', esc_html__( 'Space between sections (px)', 'pfh-widgets' ), 64, 24, 160 );
		$this->controls['padTop']   = $this->number( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 72, 0, 200 );
		$this->controls['padBottom'] = $this->number( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 72, 0, 200 );
	}

	/* ---- control helpers ---- */

	private function text( $group, $label, $default = '', array $extra = [] ) {
		return array_merge( [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'text', 'default' => $default ], $extra );
	}

	private function colour( $group, $label, $default ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'color', 'inline' => true, 'default' => [ 'hex' => $default ] ];
	}

	private function number( $group, $label, $default, $min, $max ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'number', 'min' => $min, 'max' => $max, 'inline' => true, 'default' => $default ];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$blocks = $this->blocks();
		$awards = $this->awards();
		$title  = trim( (string) $this->setting( 'title', '' ) );
		$lede   = trim( (string) $this->setting( 'lede', '' ) );

		if ( '' === $title && '' === $lede && ! $blocks && ! $awards['any'] ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-about pfh-about--empty"><p>'
					. esc_html__( 'Nothing to show yet: give the page a title, a section or an award.', 'pfh-widgets' )
					. '</p></div>';
			}

			return;
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-about', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-about__inner">';

		$this->render_head( $title, $lede );
		$this->render_blocks( $blocks );
		$this->render_awards( $awards );

		echo '</div></section>';
	}

	/**
	 * @param string $title Title.
	 * @param string $lede  Opening paragraph.
	 */
	private function render_head( $title, $lede ) {
		$eyebrow = trim( (string) $this->setting( 'eyebrow', '' ) );

		if ( '' === $title && '' === $lede && '' === $eyebrow ) {
			return;
		}

		echo '<header class="pfh-about__head">';

		if ( '' !== $eyebrow ) {
			echo '<p class="pfh-about__eyebrow">' . esc_html( $eyebrow ) . '</p>';
		}

		if ( '' !== $title ) {
			echo '<h1 class="pfh-about__title">' . wp_kses( $title, [ 'em' => [], 'i' => [], 'br' => [], 'strong' => [] ] ) . '</h1>';
		}

		if ( '' !== $lede ) {
			echo '<div class="pfh-about__lede">' . wp_kses_post( wpautop( $lede ) ) . '</div>';
		}

		echo '</header>';
	}

	/**
	 * @param array $blocks Sections.
	 */
	private function render_blocks( array $blocks ) {
		if ( ! $blocks ) {
			return;
		}

		echo '<ul class="pfh-about__blocks">';

		foreach ( $blocks as $block ) {
			printf( '<li class="pfh-about__block%s">', '' === $block['image'] ? ' pfh-about__block--text' : '' );

			if ( '' !== $block['image'] ) {
				printf(
					'<figure class="pfh-about__media"><img src="%s" alt="%s" loading="lazy" decoding="async" /></figure>',
					esc_url( $block['image'] ),
					esc_attr( $block['alt'] )
				);
			}

			echo '<div class="pfh-about__block-text">';

			if ( '' !== $block['title'] ) {
				echo '<h2 class="pfh-about__block-title">' . wp_kses( $block['title'], [ 'em' => [], 'i' => [], 'br' => [], 'strong' => [] ] ) . '</h2>';
			}

			if ( '' !== $block['text'] ) {
				echo '<div class="pfh-about__block-body">' . wp_kses_post( wpautop( $block['text'] ) ) . '</div>';
			}

			echo '</div></li>';
		}

		echo '</ul>';
	}

	/**
	 * @param array $awards { title, lede, items, any }
	 */
	private function render_awards( array $awards ) {
		if ( ! $awards['any'] ) {
			return;
		}

		echo '<div class="pfh-about__awards">';

		if ( '' !== $awards['title'] ) {
			echo '<h2 class="pfh-about__awards-title">' . wp_kses( $awards['title'], [ 'em' => [], 'i' => [], 'br' => [], 'strong' => [] ] ) . '</h2>';
		}

		if ( '' !== $awards['lede'] ) {
			echo '<div class="pfh-about__awards-lede">' . wp_kses_post( wpautop( $awards['lede'] ) ) . '</div>';
		}

		if ( $awards['items'] ) {
			echo '<ul class="pfh-about__awards-list">';

			foreach ( $awards['items'] as $award ) {
				echo '<li class="pfh-about__award">';

				$badge = sprintf(
					'<img src="%s" alt="%s" loading="lazy" decoding="async" />',
					esc_url( $award['image'] ),
					esc_attr( $award['label'] )
				);

				if ( '' !== $award['link'] ) {
					printf(
						'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
						esc_url( $award['link'] ),
						$badge // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
					);
				} else {
					echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				}

				if ( '' !== $award['label'] ) {
					echo '<span class="pfh-about__award-label">' . esc_html( $award['label'] ) . '</span>';
				}

				echo '</li>';
			}

			echo '</ul>';
		}

		echo '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Reading the fields
	 * ------------------------------------------------------------------ */

	/**
	 * @return array<int, array{title:string, text:string, image:string, alt:string}>
	 */
	private function blocks() {
		$rows = $this->setting( 'blocks', [] );
		$out  = [];

		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$title = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';
			$text  = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';
			$image = isset( $row['image'] ) ? PFH_Widgets_Helpers::image_url( $row['image'], 'large' ) : '';

			if ( '' === $title && '' === $text && '' === $image ) {
				continue;
			}

			$out[] = [
				'title' => $title,
				'text'  => $text,
				'image' => $image,
				'alt'   => wp_strip_all_tags( $title ),
			];
		}

		return $out;
	}

	/**
	 * @return array{title:string, lede:string, items:array, any:bool}
	 */
	private function awards() {
		$rows  = $this->setting( 'awards', [] );
		$items = [];

		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$image = isset( $row['image'] ) ? PFH_Widgets_Helpers::image_url( $row['image'], 'medium' ) : '';

			if ( '' === $image ) {
				continue;
			}

			$items[] = [
				'image' => $image,
				'label' => isset( $row['label'] ) ? trim( (string) $row['label'] ) : '',
				'link'  => isset( $row['link'] ) ? trim( (string) $row['link'] ) : '',
			];
		}

		$title = trim( (string) $this->setting( 'awardsTitle', '' ) );
		$lede  = trim( (string) $this->setting( 'awardsLede', '' ) );

		return [
			'title' => $title,
			'lede'  => $lede,
			'items' => $items,
			'any'   => ( '' !== $title || '' !== $lede || (bool) $items ),
		];
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-ab-max'       => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1140 ) ),
				'--pfh-ab-gap-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'gap', 64 ) ),
				'--pfh-ab-pt-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 72 ) ),
				'--pfh-ab-pb-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 72 ) ),
				'--pfh-ab-radius'    => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 16 ) ),
				'--pfh-ab-title-set' => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 40 ) ),
				'--pfh-ab-head-set'  => PFH_Widgets_Helpers::unit( $this->setting( 'headSize', 28 ) ),
				'--pfh-ab-award'     => PFH_Widgets_Helpers::unit( $this->setting( 'awardSize', 96 ) ),
				'--pfh-ab-ratio'     => (string) $this->setting( 'ratio', '4 / 3' ),
				'--pfh-ab-ink'       => PFH_Widgets_Helpers::color( $this->setting( 'ink' ), '#22301c' ),
				'--pfh-ab-body'      => PFH_Widgets_Helpers::color( $this->setting( 'bodyInk' ), '#5c6657' ),
				'--pfh-ab-muted'     => PFH_Widgets_Helpers::color( $this->setting( 'mutedInk' ), '#8d9589' ),
				'--pfh-ab-accent'    => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#2b5f63' ),
				'--pfh-ab-band'      => PFH_Widgets_Helpers::color( $this->setting( 'band' ), '#f6f8f5' ),
			]
		);
	}
}
