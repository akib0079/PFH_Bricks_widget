<?php
/**
 * Bricks element: Products For Home FAQ.
 *
 * An accordion of questions, built on <details>/<summary> so it opens and
 * closes with no JavaScript at all, is keyboard operable for free, and is
 * findable by the browser's own in-page search.
 *
 * Emits FAQPage structured data, which is the whole reason a shop puts these
 * on a collection page.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Faq extends \Bricks\Element {

	use PFH_Design_Revision;

	/**
	 * What these settings used to default to, before the design pass.
	 * A stored value still equal to one of these was inherited, not chosen.
	 *
	 * @return array<string, mixed>
	 */
	private function previous_defaults() {
		return [
		'titleSize' => 28,
		'qSize' => 14,
		'rowRadius' => 8,
		'rowGap' => 10,
		'listWidth' => 620,
		'maxWidth' => 1140,
		];
	}

	public $category     = 'products-for-home';
	public $name         = 'pfh-faq';
	public $icon         = 'ti-help-alt';
	public $css_selector = '.pfh-faq';

	public function get_label() {
		return esc_html__( 'PFH FAQ', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'faq', 'questions', 'accordion', 'answers', 'veelgestelde', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::shop();
	}

	public function set_control_groups() {
		$this->control_groups['head']   = [ 'title' => esc_html__( 'Heading', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['items']  = [ 'title' => esc_html__( 'Questions', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['style']  = [ 'title' => esc_html__( 'Style', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['decor']  = [ 'title' => esc_html__( 'Decoration', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout'] = [ 'title' => esc_html__( 'Layout', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->controls['fromCategory'] = [
			'tab'         => 'content',
			'group'       => 'items',
			'label'       => esc_html__( 'Follow each category\'s own settings', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'On a category page, the category can hide these questions or give its own (Products → Categories → edit → Collection page). A category with none of its own shows the questions set here.', 'pfh-widgets' ),
		];

		$this->controls['title'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Veelgestelde Vragen',
		];

		$this->controls['titleTag'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading tag', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'h2' => 'H2',
				'h3' => 'H3',
				'h4' => 'H4',
			],
			'default' => 'h2',
		];

		$this->controls['titleSize'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 16,
			'max'     => 64,
			'inline'  => true,
			'default' => 32,
		];

		$this->controls['titleAlign'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading alignment', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'center' => esc_html__( 'Centre', 'pfh-widgets' ),
				'left'   => esc_html__( 'Left', 'pfh-widgets' ),
			],
			'default' => 'center',
		];

		/* ---- items ---- */

		$this->controls['fromProduct'] = [
			'tab'         => 'content',
			'group'       => 'items',
			'label'       => esc_html__( 'Use the product\'s own questions', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'For a product page. Each product sets its questions under Products For Home, and the ones below are used for any product that has none.', 'pfh-widgets' ),
		];

		$this->controls['productId'] = [
			'tab'         => 'content',
			'group'       => 'items',
			'label'       => esc_html__( 'Product to read while editing', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'e.g. 1482', 'pfh-widgets' ),
			'required'    => [ 'fromProduct', '=', true ],
		];

		$this->controls['items'] = [
			'tab'           => 'content',
			'group'         => 'items',
			'label'         => esc_html__( 'Questions', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'q',
			'default'       => [
				[
					'q' => 'Is Gia giamas gemaakt van echt fruit?',
					'a' => 'Ja! Gia giamas wordt gemaakt van echt vers geperst fruit uit Griekenland.',
				],
				[ 'q' => 'Heb ik een pomp nodig?', 'a' => '' ],
				[ 'q' => 'Zijn het natuurlijke ingrediënten?', 'a' => '' ],
				[ 'q' => 'Zit er suiker in?', 'a' => '' ],
				[ 'q' => 'Moet je gia giamas in de koelkast bewaren?', 'a' => '' ],
				[ 'q' => 'Wat is de houdbaarheid?', 'a' => '' ],
				[ 'q' => 'Zitten er conserveringsmiddelen in?', 'a' => '' ],
			],
			'fields'        => [
				'q' => [
					'label' => esc_html__( 'Question', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'a' => [
					'label' => esc_html__( 'Answer', 'pfh-widgets' ),
					'type'  => 'editor',
				],
			],
		];

		$this->controls['openFirst'] = [
			'tab'     => 'content',
			'group'   => 'items',
			'label'   => esc_html__( 'Open the first one', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['single'] = [
			'tab'         => 'content',
			'group'       => 'items',
			'label'       => esc_html__( 'One open at a time', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Closes the others when one is opened.', 'pfh-widgets' ),
		];

		$this->controls['schema'] = [
			'tab'         => 'content',
			'group'       => 'items',
			'label'       => esc_html__( 'FAQ structured data', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Only questions that actually have an answer are included, which is what Google asks for.', 'pfh-widgets' ),
		];

		/* ---- style ---- */

		$this->controls['rowBg'] = [
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Row background', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['rowBorder'] = [
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Row border', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['rowBorderOpen'] = [
			'tab'         => 'content',
			'group'       => 'style',
			'label'       => esc_html__( 'Row border, open', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#3c868c' ],
			'description' => esc_html__( 'The open question is outlined a shade darker than the rest.', 'pfh-widgets' ),
		];

		$this->controls['rowRadius'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Row radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 32,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['rowGap'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Gap between rows (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 40,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['qSize'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Question size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 28,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['aSize'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Answer size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['aLineHeight'] = [
			'tab'         => 'content',
			'group'       => 'style',
			'label'       => esc_html__( 'Answer line height', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 2.4,
			'step'        => 0.1,
			'inline'      => true,
			'default'     => 1,
			'description' => esc_html__( 'The design draws a one-line answer at 1. Raise it for answers that run to several lines.', 'pfh-widgets' ),
		];

		$this->controls['ink'] = [
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'  => 'color',
		];

		/* ---- decoration ---- */

		$this->controls['decorImage'] = [
			'tab'     => 'content',
			'group'   => 'decor',
			'label'   => esc_html__( 'Decoration image', 'pfh-widgets' ),
			'type'    => 'image',
		];

		$this->controls['decorWidth'] = [
			'tab'     => 'content',
			'group'   => 'decor',
			'label'   => esc_html__( 'Width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 40,
			'max'     => 600,
			'inline'  => true,
			'default' => 200,
		];

		$this->controls['decorSide'] = [
			'tab'     => 'content',
			'group'   => 'decor',
			'label'   => esc_html__( 'Side', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'right' => esc_html__( 'Right', 'pfh-widgets' ),
				'left'  => esc_html__( 'Left', 'pfh-widgets' ),
			],
			'default' => 'right',
		];

		$this->controls['decorTop'] = [
			'tab'     => 'content',
			'group'   => 'decor',
			'label'   => esc_html__( 'From the top (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => -200,
			'max'     => 600,
			'inline'  => true,
			'default' => 0,
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
			'default' => 1140,
		];

		$this->controls['listWidth'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Accordion width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 300,
			'max'     => 1200,
			'inline'  => true,
			'default' => 940,
		];

		$this->controls['bg'] = [
			'tab'   => 'content',
			'group' => 'layout',
			'label' => esc_html__( 'Section background', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['bgImage'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Section background image', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'The supplied backdrop is used until one is chosen here. Clear the checkbox below to drop it entirely.', 'pfh-widgets' ),
		];

		$this->controls['bgFallback'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Use the supplied backdrop when none is set', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['padTop'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Space above (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'inline'  => true,
			'default' => 72,
		];

		$this->controls['padBottom'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Space below (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'inline'  => true,
			'default' => 72,
		];
	}

	public function render() {
		$this->apply_design_revision( $this->previous_defaults() );

		$own = $this->category();

		if ( $own && ! empty( $own['hide'] ) ) {
			return;
		}

		$items = $this->items();

		if ( ! $items ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-faq pfh-faq--empty"><p>' . esc_html__( 'Add a question or two in the Questions group.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$classes = [ 'pfh-faq', 'pfh-scope', 'pfh-faq--' . (string) $this->get( 'titleAlign', 'center' ) ];

		if ( $this->background_url() ) {
			$classes[] = 'has-bg-image';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		if ( $this->is_on( 'single' ) ) {
			$this->set_attribute( '_root', 'data-pfh-faq-single', 'true' );
		}

		echo '<section ' . $this->render_attributes( '_root' ) . ' data-pfh-faq>';

		$this->render_decor();

		echo '<div class="pfh-faq__inner">';

		$title = trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'title', '' ) ) );

		if ( $own && '' !== $own['title'] ) {
			$title = $own['title'];
		}

		if ( '' !== $title ) {
			$tag = in_array( (string) $this->get( 'titleTag', 'h2' ), [ 'h2', 'h3', 'h4' ], true )
				? (string) $this->get( 'titleTag', 'h2' )
				: 'h2';

			printf( '<%1$s class="pfh-faq__title">%2$s</%1$s>', $tag, esc_html( $title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag whitelisted.
		}

		echo '<div class="pfh-faq__list">';

		$open_first = $this->is_on( 'openFirst' );

		foreach ( $items as $i => $item ) {
			/*
			 * Both supplied arrows are rendered and CSS shows one. They are
			 * mirror images, so a single rotated chevron would look the same
			 * — but the design names an asset per state, and swapping them
			 * keeps the drawn stroke caps exactly as supplied.
			 */
			printf(
				'<details class="pfh-faq__item"%s><summary class="pfh-faq__q"><span>%s</span><span class="pfh-faq__chev" aria-hidden="true">%s%s</span></summary>',
				( 0 === $i && $open_first ) ? ' open' : '',
				esc_html( $item['q'] ),
				PFH_Widgets_Icons::get( 'faq-open', 'pfh-faq__chev-down' ),  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				PFH_Widgets_Icons::get( 'faq-close', 'pfh-faq__chev-up' )    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);

			if ( '' !== trim( wp_strip_all_tags( $item['a'] ) ) ) {
				echo '<div class="pfh-faq__a">' . wp_kses_post( $item['a'] ) . '</div>';
			}

			echo '</details>';
		}

		echo '</div>';
		echo '</div>';

		$this->render_schema( $items );

		echo '</section>';
	}

	/**
	 * Questions with something in them.
	 *
	 * @return array<int, array{q:string, a:string}>
	 */
	private function items() {
		$out = [];

		foreach ( $this->rows() as $row ) {
			$q = isset( $row['q'] ) ? trim( PFH_Widgets_Helpers::dd( (string) $row['q'] ) ) : '';

			if ( '' === $q ) {
				continue;
			}

			$a = isset( $row['a'] ) ? (string) $row['a'] : '';

			$out[] = [
				'q' => $q,
				'a' => '' === trim( $a ) ? '' : PFH_Widgets_Helpers::dd( $a ),
			];
		}

		return $out;
	}

	private function render_decor() {
		$url = PFH_Widgets_Helpers::image_url( $this->get( 'decorImage' ), 'medium_large' );

		if ( ! $url ) {
			return;
		}

		printf(
			'<img class="pfh-faq__decor pfh-faq__decor--%s" src="%s" alt="" aria-hidden="true" loading="lazy" decoding="async" />',
			esc_attr( (string) $this->get( 'decorSide', 'right' ) ),
			esc_url( $url )
		);
	}

	/**
	 * FAQPage JSON-LD.
	 *
	 * Only answered questions go in: marking up an empty answer is the kind of
	 * thing that gets rich results revoked rather than granted.
	 *
	 * @param array $items Questions.
	 */
	private function render_schema( array $items ) {
		if ( ! $this->is_on( 'schema' ) || PFH_Widgets_Helpers::is_builder_context() ) {
			return;
		}

		$entities = [];

		foreach ( $items as $item ) {
			$answer = trim( wp_strip_all_tags( $item['a'] ) );

			if ( '' === $answer ) {
				continue;
			}

			$entities[] = [
				'@type'          => 'Question',
				'name'           => $item['q'],
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => $answer,
				],
			];
		}

		if ( ! $entities ) {
			return;
		}

		$schema = [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		];

		printf(
			'<script type="application/ld+json">%s</script>',
			wp_json_encode( $schema ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, inside a ld+json block.
		);
	}

	/**
	 * The section backdrop: the chosen image, else the supplied one.
	 *
	 * @return string
	 */
	private function background_url() {
		$chosen = PFH_Widgets_Helpers::image_url( $this->get( 'bgImage' ), 'full' );

		if ( $chosen ) {
			return $chosen;
		}

		if ( ! $this->is_on( 'bgFallback' ) ) {
			return '';
		}

		return PFH_Widgets_Assets::img( 'pfh-faq-bg.jpg' );
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-fq-max'    => PFH_Widgets_Helpers::unit( $this->get( 'maxWidth', 1140 ) ),
				'--pfh-fq-list'   => PFH_Widgets_Helpers::unit( $this->get( 'listWidth', 620 ) ),
				'--pfh-fq-pt-set' => PFH_Widgets_Helpers::unit( $this->get( 'padTop', 72 ) ),
				'--pfh-fq-pb-set' => PFH_Widgets_Helpers::unit( $this->get( 'padBottom', 72 ) ),
				'--pfh-fq-bg'     => PFH_Widgets_Helpers::color( $this->get( 'bg' ) ),
				'--pfh-fq-title'  => PFH_Widgets_Helpers::unit( $this->get( 'titleSize', 28 ) ),
				'--pfh-fq-q'      => PFH_Widgets_Helpers::unit( $this->get( 'qSize', 14 ) ),
				'--pfh-fq-ink'    => PFH_Widgets_Helpers::color( $this->get( 'ink' ) ),
				'--pfh-fq-row-bg' => PFH_Widgets_Helpers::color( $this->get( 'rowBg' ) ),
				'--pfh-fq-row-bd' => PFH_Widgets_Helpers::color( $this->get( 'rowBorder' ) ),
				'--pfh-fq-row-r'  => PFH_Widgets_Helpers::unit( $this->get( 'rowRadius', 8 ) ),
				'--pfh-fq-row-gap' => PFH_Widgets_Helpers::unit( $this->get( 'rowGap', 10 ) ),
				'--pfh-fq-decor-w' => PFH_Widgets_Helpers::unit( $this->get( 'decorWidth', 200 ) ),
				'--pfh-fq-decor-t' => PFH_Widgets_Helpers::unit( $this->get( 'decorTop', 0 ) ),
				'--pfh-fq-row-bd-open' => PFH_Widgets_Helpers::color( $this->get( 'rowBorderOpen' ), '#3c868c' ),
				'--pfh-fq-a'      => PFH_Widgets_Helpers::unit( $this->get( 'aSize', 14 ) ),
				'--pfh-fq-a-lh'   => (string) $this->get( 'aLineHeight', 1 ),
				'--pfh-fq-bg-img'  => $this->background_url() ? 'url(' . esc_url( $this->background_url() ) . ')' : 'none',
			]
		);
	}

	/**
	 * The questions to draw: the product's own, else the ones set here.
	 *
	 * A product with none falls back rather than showing an empty accordion,
	 * which is the same rule the rest of the product page follows.
	 *
	 * @return array
	 */
	private function rows() {
		$own = [];

		if ( $this->is_on( 'fromProduct', false ) && class_exists( 'PFH_Widgets_Product_Fields' ) ) {
			$id = is_singular( 'product' ) ? (int) get_queried_object_id() : (int) $this->get( 'productId', 0 );

			if ( ! $id ) {
				global $post;

				$id = ( $post && 'product' === get_post_type( $post ) ) ? (int) $post->ID : 0;
			}

			if ( $id ) {
				foreach ( PFH_Widgets_Product_Fields::rows( $id, PFH_Widgets_Product_Fields::FAQ ) as $row ) {
					$own[] = [ 'q' => $row['label'], 'a' => $row['text'] ];
				}
			}
		}

		if ( $own ) {
			return $own;
		}

		// Then the category's, on a category page.
		$category = $this->category();

		if ( $category && ! empty( $category['items'] ) ) {
			$rows = [];

			foreach ( $category['items'] as $item ) {
				$rows[] = [ 'q' => $item['q'], 'a' => wpautop( $item['a'] ) ];
			}

			return $rows;
		}

		return (array) $this->get( 'items', [] );
	}

	/**
	 * The category's own questions and switch, when this follows them.
	 *
	 * @return array|null
	 */
	private function category() {
		if ( ! $this->is_on( 'fromCategory' ) || ! class_exists( 'PFH_Widgets_Collection' ) ) {
			return null;
		}

		return PFH_Widgets_Collection::section( 'faq' );
	}

	private function get( $key, $default = null ) {
		return $this->setting( $key, $default );
	}

	private function is_on( $key, $default = true ) {
		return $this->switched_on( $key, $default );
	}
}
