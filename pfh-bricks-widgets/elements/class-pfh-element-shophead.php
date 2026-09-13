<?php
/**
 * Bricks element: Products For Home shop header.
 *
 * Breadcrumbs, the collection title, a short intro and the banner image.
 *
 * Every text field runs through the dynamic-data helper, so "Gia giamas" can
 * be typed today and swapped for an ACF field tomorrow without touching the
 * template — which is exactly how this was asked for.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Shophead extends \Bricks\Element {

	public $category     = 'products-for-home';
	public $name         = 'pfh-shophead';
	public $icon         = 'ti-layout-cta-left';
	public $css_selector = '.pfh-shophead';

	/**
	 * Banner shown until a real one is set or a field is connected.
	 */
	public function get_label() {
		return esc_html__( 'PFH Shop Header', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'shop', 'collection', 'archive', 'header', 'breadcrumbs', 'category', 'banner', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::shop();
	}

	public function set_control_groups() {
		$this->control_groups['crumbs']  = [ 'title' => esc_html__( 'Breadcrumbs', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['title']   = [ 'title' => esc_html__( 'Title & intro', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['banner']  = [ 'title' => esc_html__( 'Banner image', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout']  = [ 'title' => esc_html__( 'Layout', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		/* ---- breadcrumbs ---- */

		$this->controls['crumbsEnable'] = [
			'tab'     => 'content',
			'group'   => 'crumbs',
			'label'   => esc_html__( 'Show breadcrumbs', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['crumbsHome'] = [
			'tab'      => 'content',
			'group'    => 'crumbs',
			'label'    => esc_html__( 'Home label', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Home',
			'required' => [ 'crumbsEnable', '=', true ],
		];

		$this->controls['crumbsSep'] = [
			'tab'      => 'content',
			'group'    => 'crumbs',
			'label'    => esc_html__( 'Separator', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => '/',
			'required' => [ 'crumbsEnable', '=', true ],
		];

		$this->controls['crumbsManual'] = [
			'tab'           => 'content',
			'group'         => 'crumbs',
			'label'         => esc_html__( 'Override the trail', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'label',
			'placeholder'   => esc_html__( 'Crumb', 'pfh-widgets' ),
			'description'   => esc_html__( 'Leave empty to build the trail from the category being viewed.', 'pfh-widgets' ),
			'required'      => [ 'crumbsEnable', '=', true ],
			'fields'        => [
				'label' => [
					'label' => esc_html__( 'Label', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'link'  => [
					'label' => esc_html__( 'Link', 'pfh-widgets' ),
					'type'  => 'link',
				],
			],
		];

		$this->controls['crumbsColor'] = [
			'tab'      => 'content',
			'group'    => 'crumbs',
			'label'    => esc_html__( 'Colour', 'pfh-widgets' ),
			'type'     => 'color',
			'required' => [ 'crumbsEnable', '=', true ],
		];

		/* ---- title and intro ---- */

		$this->controls['titleSource'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Title', 'pfh-widgets' ),
			'type'    => 'select',
			'options' => [
				'auto'   => esc_html__( 'The category being viewed', 'pfh-widgets' ),
				'manual' => esc_html__( 'The text below', 'pfh-widgets' ),
			],
			'default' => 'auto',
		];

		$this->controls['title'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'Title text', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'Gia giamas',
			'description' => esc_html__( 'Dynamic data is supported, so an ACF field can be dropped in here later.', 'pfh-widgets' ),
			'required'    => [ 'titleSource', '=', 'manual' ],
		];

		$this->controls['titleTag'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Title tag', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'h1' => 'H1',
				'h2' => 'H2',
				'h3' => 'H3',
			],
			'default' => 'h1',
		];

		$this->controls['titleSize'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Title size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 18,
			'max'     => 96,
			'inline'  => true,
			'default' => 46,
		];

		$this->controls['titleColor'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Title colour', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['introSource'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Short description', 'pfh-widgets' ),
			'type'    => 'select',
			'options' => [
				'manual' => esc_html__( 'The text below', 'pfh-widgets' ),
				'auto'   => esc_html__( 'The category description', 'pfh-widgets' ),
				'none'   => esc_html__( 'Do not show one', 'pfh-widgets' ),
			],
			'default' => 'manual',
		];

		$this->controls['intro'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'Intro text', 'pfh-widgets' ),
			'type'        => 'textarea',
			'default'     => 'Griekse vruchtenconcentraten naar het originele recept van Yiayia Marika uit 1957. Puur natuur, direct uit Griekenland — 100% ambachtelijk.',
			'description' => esc_html__( 'Dynamic data is supported here too.', 'pfh-widgets' ),
			'required'    => [ 'introSource', '=', 'manual' ],
		];

		$this->controls['textWidth'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'Text column width (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 200,
			'max'         => 1200,
			'inline'      => true,
			'default'     => 551,
			'description' => esc_html__( 'Holds the title and the intro. 551 matches the design.', 'pfh-widgets' ),
		];

		$this->controls['introColor'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Intro colour', 'pfh-widgets' ),
			'type'  => 'color',
		];

		/* ---- banner ---- */

		$this->controls['bannerEnable'] = [
			'tab'     => 'content',
			'group'   => 'banner',
			'label'   => esc_html__( 'Show the banner image', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['banner'] = [
			'tab'         => 'content',
			'group'       => 'banner',
			'label'       => esc_html__( 'Image', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'Leave empty for the placeholder. Connect a field here once the category image is in place.', 'pfh-widgets' ),
			'required'    => [ 'bannerEnable', '=', true ],
		];

		$this->controls['bannerHeight'] = [
			'tab'      => 'content',
			'group'    => 'banner',
			'label'    => esc_html__( 'Image height (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 60,
			'max'      => 460,
			'inline'   => true,
			'default'  => 170,
			'required' => [ 'bannerEnable', '=', true ],
		];

		$this->controls['bannerMobile'] = [
			'tab'         => 'content',
			'group'       => 'banner',
			'label'       => esc_html__( 'Show it on phones', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Off by default: on a narrow screen the image pushes the title and intro below the fold for no benefit.', 'pfh-widgets' ),
			'required'    => [ 'bannerEnable', '=', true ],
		];

		$this->controls['bannerFit'] = [
			'tab'      => 'content',
			'group'    => 'banner',
			'label'    => esc_html__( 'Fit', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => [
				'contain' => esc_html__( 'Contain — the whole image', 'pfh-widgets' ),
				'cover'   => esc_html__( 'Cover — fill and crop', 'pfh-widgets' ),
			],
			'default'  => 'contain',
			'required' => [ 'bannerEnable', '=', true ],
		];

		/* ---- layout ---- */

		$this->controls['maxWidth'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Container width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 600,
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
			'default' => 28,
		];

		$this->controls['padBottom'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Space below (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 0,
		];

		$this->controls['gap'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Gap between text and image (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 160,
			'inline'  => true,
			'default' => 40,
		];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$classes = [ 'pfh-shophead', 'pfh-scope' ];

		if ( ! $this->is_on( 'bannerMobile', false ) ) {
			$classes[] = 'pfh-shophead--hide-banner-sm';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-shophead__inner">';

		echo '<div class="pfh-shophead__text">';

		$this->render_crumbs();

		$title = $this->title_text();

		if ( '' !== $title ) {
			$tag = in_array( (string) $this->get( 'titleTag', 'h1' ), [ 'h1', 'h2', 'h3' ], true )
				? (string) $this->get( 'titleTag', 'h1' )
				: 'h1';

			printf( '<%1$s class="pfh-shophead__title">%2$s</%1$s>', $tag, esc_html( $title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag whitelisted above.
		}

		$intro = $this->intro_text();

		if ( '' !== $intro ) {
			echo '<div class="pfh-shophead__intro">' . wp_kses_post( wpautop( $intro ) ) . '</div>';
		}

		echo '</div>';

		$this->render_banner();

		echo '</div>';
		echo '</section>';
	}

	private function render_crumbs() {
		if ( ! $this->is_on( 'crumbsEnable' ) ) {
			return;
		}

		$crumbs = $this->crumbs();

		if ( ! $crumbs ) {
			return;
		}

		$sep = (string) $this->get( 'crumbsSep', '/' );

		echo '<nav class="pfh-shophead__crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'pfh-widgets' ) . '"><ol>';

		$last = count( $crumbs ) - 1;

		foreach ( $crumbs as $i => $crumb ) {
			echo '<li>';

			if ( $i < $last && '' !== $crumb['href'] ) {
				printf( '<a href="%s">%s</a>', esc_url( $crumb['href'] ), esc_html( $crumb['label'] ) );
			} else {
				printf( '<span aria-current="page">%s</span>', esc_html( $crumb['label'] ) );
			}

			if ( $i < $last ) {
				printf( '<span class="pfh-shophead__sep" aria-hidden="true">%s</span>', esc_html( $sep ) );
			}

			echo '</li>';
		}

		echo '</ol></nav>';
	}

	/**
	 * The trail, typed or worked out from the category being viewed.
	 *
	 * @return array<int, array{label:string, href:string}>
	 */
	private function crumbs() {
		$manual = (array) $this->get( 'crumbsManual', [] );
		$out    = [];

		if ( $manual ) {
			foreach ( $manual as $row ) {
				$label = isset( $row['label'] ) ? PFH_Widgets_Helpers::dd( $row['label'] ) : '';

				if ( '' === trim( $label ) ) {
					continue;
				}

				$link = PFH_Widgets_Helpers::link( isset( $row['link'] ) ? $row['link'] : null );

				$out[] = [
					'label' => $label,
					'href'  => $link['href'],
				];
			}

			return $out;
		}

		$out[] = [
			'label' => (string) $this->get( 'crumbsHome', 'Home' ),
			'href'  => home_url( '/' ),
		];

		$term = $this->queried_term();

		if ( $term ) {
			$trail  = [];
			$parent = (int) $term->parent;
			$guard  = 0;

			while ( $parent && $guard < 10 ) {
				$ancestor = get_term( $parent, 'product_cat' );

				if ( ! $ancestor || is_wp_error( $ancestor ) ) {
					break;
				}

				$link = get_term_link( $ancestor );

				array_unshift(
					$trail,
					[
						'label' => $ancestor->name,
						'href'  => is_wp_error( $link ) ? '' : $link,
					]
				);

				$parent = (int) $ancestor->parent;
				$guard++;
			}

			$out = array_merge( $out, $trail );

			$out[] = [
				'label' => $term->name,
				'href'  => '',
			];

			return $out;
		}

		// Not a category archive. The shop page and any ordinary page still
		// need their own crumb — leaving it off is why the shop page showed a
		// bare "Home" while category pages read correctly.
		$leaf = $this->leaf_label();

		if ( '' !== $leaf ) {
			$out[] = [
				'label' => $leaf,
				'href'  => '',
			];
		}

		return $out;
	}

	/**
	 * The name of the thing being viewed, for the last crumb.
	 *
	 * @return string
	 */
	private function leaf_label() {
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			$page = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;

			if ( $page > 0 ) {
				$name = get_the_title( $page );

				if ( '' !== trim( (string) $name ) ) {
					return (string) $name;
				}
			}

			return esc_html__( 'Shop', 'pfh-widgets' );
		}

		if ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
			$tag = get_queried_object();

			if ( $tag && ! is_wp_error( $tag ) && ! empty( $tag->name ) ) {
				return (string) $tag->name;
			}
		}

		if ( is_search() ) {
			return (string) get_search_query();
		}

		if ( is_singular() || is_page() ) {
			$name = get_the_title();

			if ( '' !== trim( (string) $name ) ) {
				return (string) $name;
			}
		}

		// The builder has no archive to read, so fall back to the title field.
		return trim( (string) $this->get( 'title', '' ) );
	}

	/**
	 * @return \WP_Term|null
	 */
	private function queried_term() {
		if ( ! is_tax( 'product_cat' ) ) {
			return null;
		}

		$term = get_queried_object();

		return ( $term && ! is_wp_error( $term ) && isset( $term->term_id ) ) ? $term : null;
	}

	private function title_text() {
		if ( 'manual' === (string) $this->get( 'titleSource', 'auto' ) ) {
			return trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'title', '' ) ) );
		}

		$term = $this->queried_term();

		if ( $term ) {
			return $term->name;
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			$page = wc_get_page_id( 'shop' );

			if ( $page > 0 ) {
				return get_the_title( $page );
			}
		}

		// In the builder there is no archive to read, so show the typed value.
		return trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'title', '' ) ) );
	}

	private function intro_text() {
		$source = (string) $this->get( 'introSource', 'manual' );

		if ( 'none' === $source ) {
			return '';
		}

		if ( 'auto' === $source ) {
			$term = $this->queried_term();

			if ( $term && '' !== trim( (string) $term->description ) ) {
				return (string) $term->description;
			}
		}

		return trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'intro', '' ) ) );
	}

	private function render_banner() {
		if ( ! $this->is_on( 'bannerEnable' ) ) {
			return;
		}

		$url = PFH_Widgets_Helpers::image_url( $this->get( 'banner' ), 'large' );

		if ( ! $url ) {
			// The supplied banner was a JPEG on black, so a de-matted copy
			// ships with the plugin — the original shows a black box against
			// the page.
			$url = PFH_Widgets_Assets::img( 'pfh-shop-banner.png' );
		}

		printf(
			'<div class="pfh-shophead__banner"><img src="%s" alt="" loading="lazy" decoding="async" /></div>',
			esc_url( $url )
		);
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-sh-max'         => PFH_Widgets_Helpers::unit( $this->get( 'maxWidth', 1240 ) ),
				'--pfh-sh-pt'          => PFH_Widgets_Helpers::unit( $this->get( 'padTop', 28 ) ),
				'--pfh-sh-pb'          => PFH_Widgets_Helpers::unit( $this->get( 'padBottom', 0 ) ),
				'--pfh-sh-gap'         => PFH_Widgets_Helpers::unit( $this->get( 'gap', 40 ) ),
				'--pfh-sh-title'       => PFH_Widgets_Helpers::unit( $this->get( 'titleSize', 46 ) ),
				'--pfh-sh-title-color' => PFH_Widgets_Helpers::color( $this->get( 'titleColor' ) ),
				'--pfh-sh-intro-color' => PFH_Widgets_Helpers::color( $this->get( 'introColor' ) ),
				'--pfh-sh-text-max'    => PFH_Widgets_Helpers::unit( $this->get( 'textWidth', 551 ) ),
				'--pfh-sh-crumb-color' => PFH_Widgets_Helpers::color( $this->get( 'crumbsColor' ) ),
				'--pfh-sh-banner-h'    => PFH_Widgets_Helpers::unit( $this->get( 'bannerHeight', 170 ) ),
				'--pfh-sh-banner-fit'  => (string) $this->get( 'bannerFit', 'contain' ),
			]
		);
	}

	private function get( $key, $default = null ) {
		if ( ! isset( $this->settings[ $key ] ) || '' === $this->settings[ $key ] ) {
			return $default;
		}

		return $this->settings[ $key ];
	}

	private function is_on( $key, $default = true ) {
		if ( ! array_key_exists( $key, (array) $this->settings ) ) {
			return $default;
		}

		return ! empty( $this->settings[ $key ] );
	}
}
