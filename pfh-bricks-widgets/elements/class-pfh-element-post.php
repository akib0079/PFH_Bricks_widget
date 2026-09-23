<?php
/**
 * Bricks element: Products For Home article page.
 *
 * The whole of a blog post: the crumbs, the head, the picture, the article
 * itself, a contents panel built from its own headings, the sharing row, the
 * author, what to read next, and a row of related articles.
 *
 * The cards in that last row come from the blog's own card, so an article's
 * related row and the blog index cannot drift apart.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PFH_Widgets_Blog' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'includes/class-pfh-blog.php';
}

class PFH_Element_Post extends \Bricks\Element {

	use PFH_Element_Defaults;

	/** @var string|null Stable id for controls and aria relationships. */
	private $uid = null;

	public $category     = 'products-for-home';
	public $name         = 'pfh-post';
	public $icon         = 'ti-align-left';
	public $css_selector = '.pfh-post';

	public function get_label() {
		return esc_html__( 'PFH Article', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'post', 'article', 'blog', 'single', 'content', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::post();
	}

	public function set_control_groups() {
		foreach ( [
			'source'   => esc_html__( 'Article', 'pfh-widgets' ),
			'head'     => esc_html__( 'Head', 'pfh-widgets' ),
			'toc'      => esc_html__( 'Contents', 'pfh-widgets' ),
			'search'   => esc_html__( 'Search', 'pfh-widgets' ),
			'products' => esc_html__( 'Popular products', 'pfh-widgets' ),
			'promises' => esc_html__( 'Promises', 'pfh-widgets' ),
			'cta'      => esc_html__( 'Shop call to action', 'pfh-widgets' ),
			'below'    => esc_html__( 'Under the article', 'pfh-widgets' ),
			'related'  => esc_html__( 'Read next', 'pfh-widgets' ),
			'style'    => esc_html__( 'Global style', 'pfh-widgets' ),
			'layout'   => esc_html__( 'Layout', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		/* ---- which article ---- */

		$this->controls['previewId'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Article to show while editing', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'e.g. 214', 'pfh-widgets' ),
			'description' => esc_html__( 'On an article this always follows the one being read. Empty uses the most recent post.', 'pfh-widgets' ),
		];

		$this->controls['showProgress'] = $this->switch_field( 'source', esc_html__( 'Show how far down the reader is', 'pfh-widgets' ) );

		/* ---- head ---- */

		$this->controls['showCrumbs'] = $this->switch_field( 'head', esc_html__( 'Breadcrumbs', 'pfh-widgets' ) );
		$this->controls['homeLabel']  = $this->text( 'head', esc_html__( 'Home label', 'pfh-widgets' ), 'Home' );
		$this->controls['blogLabel']  = $this->text( 'head', esc_html__( 'Blog label', 'pfh-widgets' ), 'Blog' );

		$this->controls['showTag']    = $this->switch_field( 'head', esc_html__( 'Category', 'pfh-widgets' ) );
		$this->controls['showDate']   = $this->switch_field( 'head', esc_html__( 'Date', 'pfh-widgets' ) );
		$this->controls['showRead']   = $this->switch_field( 'head', esc_html__( 'Reading time', 'pfh-widgets' ) );
		$this->controls['showAuthor'] = $this->switch_field( 'head', esc_html__( 'Author', 'pfh-widgets' ) );
		$this->controls['showHero']   = $this->switch_field( 'head', esc_html__( 'Picture', 'pfh-widgets' ) );

		$this->controls['readLabel'] = $this->text(
			'head',
			esc_html__( 'Reading-time wording', 'pfh-widgets' ),
			'%s min leestijd',
			[ 'description' => esc_html__( '%s becomes the number of minutes.', 'pfh-widgets' ) ]
		);

		/* ---- contents ---- */

		$this->controls['showToc'] = $this->switch_field( 'toc', esc_html__( 'Show the contents', 'pfh-widgets' ) );
		$this->controls['tocTitle'] = $this->text( 'toc', esc_html__( 'Heading', 'pfh-widgets' ), 'In dit artikel' );

		$this->controls['tocDepth'] = [
			'tab'         => 'content',
			'group'       => 'toc',
			'label'       => esc_html__( 'How deep', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'default'     => 'h2h3',
			'options'     => [
				'h2'   => esc_html__( 'Main headings only', 'pfh-widgets' ),
				'h2h3' => esc_html__( 'Main headings and the ones under them', 'pfh-widgets' ),
			],
			'description' => esc_html__( 'Built from the article\'s own headings. With fewer than two it is left out.', 'pfh-widgets' ),
		];

		/* ---- search ---- */

		$this->controls['showSearch'] = $this->switch_field( 'search', esc_html__( 'Show search shortcut', 'pfh-widgets' ) );
		$this->controls['searchLabel'] = $this->text( 'search', esc_html__( 'Label', 'pfh-widgets' ), 'Zoek producten en verhalen' );
		$this->controls['searchShortcut'] = $this->text( 'search', esc_html__( 'Keyboard shortcut label', 'pfh-widgets' ), 'Cmd K' );
		$this->controls['searchBg'] = $this->colour( 'search', esc_html__( 'Background', 'pfh-widgets' ), '#ffffff' );
		$this->controls['searchInk'] = $this->colour( 'search', esc_html__( 'Text', 'pfh-widgets' ), '#7f897b' );
		$this->controls['searchBorder'] = $this->colour( 'search', esc_html__( 'Border', 'pfh-widgets' ), '#e4e8e1' );
		$this->controls['searchRadius'] = $this->number( 'search', esc_html__( 'Corner radius (px)', 'pfh-widgets' ), 14, 0, 40 );

		/* ---- products ---- */

		$this->controls['showProducts'] = $this->switch_field( 'products', esc_html__( 'Show products', 'pfh-widgets' ) );
		$this->controls['productsTitle'] = $this->text( 'products', esc_html__( 'Heading', 'pfh-widgets' ), 'Populaire producten' );
		$this->controls['productsSource'] = [
			'tab'     => 'content',
			'group'   => 'products',
			'label'   => esc_html__( 'Products to show', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'default' => 'best',
			'options' => [
				'best'     => esc_html__( 'Best selling', 'pfh-widgets' ),
				'recent'   => esc_html__( 'Newest', 'pfh-widgets' ),
				'onsale'   => esc_html__( 'On sale', 'pfh-widgets' ),
				'featured' => esc_html__( 'Featured', 'pfh-widgets' ),
				'category' => esc_html__( 'Product category', 'pfh-widgets' ),
				'ids'      => esc_html__( 'Specific products', 'pfh-widgets' ),
			],
		];
		$this->controls['productsCategory'] = [
			'tab'         => 'content',
			'group'       => 'products',
			'label'       => esc_html__( 'Category', 'pfh-widgets' ),
			'type'        => 'select',
			'searchable'  => true,
			'options'     => PFH_Widgets_Helpers::product_cat_options(),
			'placeholder' => esc_html__( 'Select a category', 'pfh-widgets' ),
			'required'    => [ 'productsSource', '=', 'category' ],
		];
		$this->controls['productIds'] = $this->text(
			'products',
			esc_html__( 'Product IDs', 'pfh-widgets' ),
			'',
			[
				'placeholder' => '128, 94, 71',
				'description' => esc_html__( 'Comma separated. The slider keeps this order.', 'pfh-widgets' ),
				'required'    => [ 'productsSource', '=', 'ids' ],
			]
		);
		$this->controls['productsCount'] = $this->number( 'products', esc_html__( 'Number of products', 'pfh-widgets' ), 6, 2, 12 );
		$this->controls['productsArrows'] = $this->switch_field( 'products', esc_html__( 'Show slider arrows', 'pfh-widgets' ) );
		$this->controls['bestsellerLabel'] = $this->text( 'products', esc_html__( 'Bestseller badge', 'pfh-widgets' ), 'Bestseller' );
		$this->controls['newLabel'] = $this->text( 'products', esc_html__( 'New badge', 'pfh-widgets' ), 'Nieuw' );
		$this->controls['saleLabel'] = $this->text( 'products', esc_html__( 'Sale badge', 'pfh-widgets' ), 'Sale' );
		$this->controls['productBg'] = $this->colour( 'products', esc_html__( 'Card background', 'pfh-widgets' ), '#ffffff' );
		$this->controls['productMediaBg'] = $this->colour( 'products', esc_html__( 'Picture background', 'pfh-widgets' ), '#f6f1e7' );
		$this->controls['productBorder'] = $this->colour( 'products', esc_html__( 'Card border', 'pfh-widgets' ), '#e3cbbd' );
		$this->controls['productRadius'] = $this->number( 'products', esc_html__( 'Card radius (px)', 'pfh-widgets' ), 16, 0, 40 );

		/* ---- promises ---- */

		$this->controls['showPromises'] = $this->switch_field( 'promises', esc_html__( 'Show promises', 'pfh-widgets' ) );
		$this->controls['promisesTitle'] = $this->text( 'promises', esc_html__( 'Heading', 'pfh-widgets' ), 'Onze beloftes' );
		$this->controls['promises'] = [
			'tab'           => 'content',
			'group'         => 'promises',
			'label'         => esc_html__( 'Items', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'text',
			'default'       => $this->default_promises(),
			'fields'        => [
				'text' => [ 'label' => esc_html__( 'Text', 'pfh-widgets' ), 'type' => 'text' ],
				'icon' => [
					'label'   => esc_html__( 'Icon', 'pfh-widgets' ),
					'type'    => 'select',
					'default' => 'check',
					'options' => [
						'star'     => esc_html__( 'Star', 'pfh-widgets' ),
						'quality'  => esc_html__( 'Quality', 'pfh-widgets' ),
						'delivery' => esc_html__( 'Delivery', 'pfh-widgets' ),
						'returns'  => esc_html__( 'Returns', 'pfh-widgets' ),
						'check'    => esc_html__( 'Check', 'pfh-widgets' ),
					],
				],
				'compact' => [
					'label'       => esc_html__( 'Compact payment row', 'pfh-widgets' ),
					'type'        => 'checkbox',
					'default'     => false,
					'description' => esc_html__( 'Removes the separator and uses the compact spacing shown for payment methods.', 'pfh-widgets' ),
				],
			],
		];
		$this->controls['promisesBg'] = $this->colour( 'promises', esc_html__( 'Background', 'pfh-widgets' ), '#f6f6f6' );
		$this->controls['promisesInk'] = $this->colour( 'promises', esc_html__( 'Text', 'pfh-widgets' ), '#596b54' );
		$this->controls['promisesIcon'] = $this->colour( 'promises', esc_html__( 'Icons', 'pfh-widgets' ), '#83a07c' );
		$this->controls['promisesRadius'] = $this->number( 'promises', esc_html__( 'Corner radius (px)', 'pfh-widgets' ), 24, 0, 48 );

		/* ---- call to action ---- */

		$this->controls['showCta'] = $this->switch_field( 'cta', esc_html__( 'Show shop call to action', 'pfh-widgets' ) );
		$this->controls['ctaEyebrow'] = $this->text( 'cta', esc_html__( 'Eyebrow', 'pfh-widgets' ), 'Ontdek de shop' );
		$this->controls['ctaTitle'] = $this->text( 'cta', esc_html__( 'Heading', 'pfh-widgets' ), 'Meer moois voor thuis' );
		$this->controls['ctaText'] = [
			'tab'     => 'content',
			'group'   => 'cta',
			'label'   => esc_html__( 'Text', 'pfh-widgets' ),
			'type'    => 'textarea',
			'default' => 'Van Griekse honing tot olijfolie. Ontdek zorgvuldig geselecteerde producten met een bijzonder verhaal.',
		];
		$this->controls['ctaButton'] = $this->text( 'cta', esc_html__( 'Button label', 'pfh-widgets' ), 'Shop meer' );
		$this->controls['ctaLink'] = [
			'tab'   => 'content',
			'group' => 'cta',
			'label' => esc_html__( 'Button link', 'pfh-widgets' ),
			'type'  => 'link',
		];
		$this->controls['ctaImage'] = [
			'tab'     => 'content',
			'group'   => 'cta',
			'label'   => esc_html__( 'Background image', 'pfh-widgets' ),
			'type'    => 'image',
			'default' => [
				'url' => 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/generated-image-6-1024x1024.webp',
			],
		];
		$this->controls['ctaOverlayTop'] = $this->colour( 'cta', esc_html__( 'Overlay at top', 'pfh-widgets' ), 'rgba(34,48,28,.94)' );
		$this->controls['ctaOverlayBottom'] = $this->colour( 'cta', esc_html__( 'Overlay at bottom', 'pfh-widgets' ), 'rgba(34,48,28,.12)' );
		$this->controls['ctaInk'] = $this->colour( 'cta', esc_html__( 'Text', 'pfh-widgets' ), '#ffffff' );
		$this->controls['ctaButtonBg'] = $this->colour( 'cta', esc_html__( 'Button background', 'pfh-widgets' ), '#ffffff' );
		$this->controls['ctaButtonInk'] = $this->colour( 'cta', esc_html__( 'Button text', 'pfh-widgets' ), '#22301c' );
		$this->controls['ctaHeight'] = $this->number( 'cta', esc_html__( 'Minimum height (px)', 'pfh-widgets' ), 390, 240, 620 );
		$this->controls['ctaRadius'] = $this->number( 'cta', esc_html__( 'Corner radius (px)', 'pfh-widgets' ), 18, 0, 48 );

		/* ---- under the article ---- */

		$this->controls['showShare']  = $this->switch_field( 'below', esc_html__( 'Sharing', 'pfh-widgets' ) );
		$this->controls['shareLabel'] = $this->text( 'below', esc_html__( 'Sharing label', 'pfh-widgets' ), 'Deel dit artikel' );
		$this->controls['copyLabel']  = $this->text( 'below', esc_html__( 'Copied wording', 'pfh-widgets' ), 'Link gekopieerd' );

		$this->controls['showBio'] = $this->switch_field( 'below', esc_html__( 'About the author', 'pfh-widgets' ), false );
		$this->controls['showNav'] = $this->switch_field( 'below', esc_html__( 'Previous and next', 'pfh-widgets' ) );
		$this->controls['prevLabel'] = $this->text( 'below', esc_html__( 'Previous label', 'pfh-widgets' ), 'Vorige' );
		$this->controls['nextLabel'] = $this->text( 'below', esc_html__( 'Next label', 'pfh-widgets' ), 'Volgende' );

		/* ---- read next ---- */

		$this->controls['showRelated']  = $this->switch_field( 'related', esc_html__( 'Show related articles', 'pfh-widgets' ) );
		$this->controls['relatedTitle'] = $this->text( 'related', esc_html__( 'Heading', 'pfh-widgets' ), 'Lees ook' );
		$this->controls['relatedCount'] = $this->number( 'related', esc_html__( 'How many', 'pfh-widgets' ), 3, 2, 4 );

		$this->controls['relatedMore'] = $this->text(
			'related',
			esc_html__( 'Link under each card', 'pfh-widgets' ),
			'Lees verder',
			[ 'description' => esc_html__( 'The cards are the blog\'s own, so this is the same link the blog page shows.', 'pfh-widgets' ) ]
		);

		/* ---- style ---- */

		$this->controls['ink']      = $this->colour( 'style', esc_html__( 'Headings', 'pfh-widgets' ), '#22301c' );
		$this->controls['bodyInk']  = $this->colour( 'style', esc_html__( 'Body text', 'pfh-widgets' ), '#43503f' );
		$this->controls['mutedInk'] = $this->colour( 'style', esc_html__( 'Meta text', 'pfh-widgets' ), '#8d9589' );
		$this->controls['accent']   = $this->colour( 'style', esc_html__( 'Links and marks', 'pfh-widgets' ), '#2b5f63' );
		$this->controls['lineColor'] = $this->colour( 'style', esc_html__( 'Borders', 'pfh-widgets' ), '#eaeaea' );
		$this->controls['tagBg']    = $this->colour( 'style', esc_html__( 'Category pill', 'pfh-widgets' ), '#dfe9dc' );
		$this->controls['tagInk']   = $this->colour( 'style', esc_html__( 'Category pill text', 'pfh-widgets' ), '#46603f' );

		$this->controls['titleSize'] = $this->number( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 42, 24, 64 );
		$this->controls['titleSizeMobile'] = $this->number( 'style', esc_html__( 'Title size on mobile (px)', 'pfh-widgets' ), 32, 22, 46 );
		$this->controls['textSize']  = $this->number( 'style', esc_html__( 'Article text size (px)', 'pfh-widgets' ), 17, 14, 22 );
		$this->controls['sidebarTitleSize'] = $this->number( 'style', esc_html__( 'Sidebar heading size (px)', 'pfh-widgets' ), 18, 14, 28 );
		$this->controls['radius']    = $this->number( 'style', esc_html__( 'Picture radius (px)', 'pfh-widgets' ), 16, 0, 40 );

		/* ---- layout ---- */

		$this->controls['maxWidth'] = $this->number( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1240, 600, 1600 );
		$this->controls['measure']  = $this->number( 'layout', esc_html__( 'Article width (px)', 'pfh-widgets' ), 880, 480, 980 );
		$this->controls['asideWidth'] = $this->number( 'layout', esc_html__( 'Sidebar width (px)', 'pfh-widgets' ), 320, 220, 420 );
		$this->controls['columnGap'] = $this->number( 'layout', esc_html__( 'Space between columns (px)', 'pfh-widgets' ), 64, 16, 120 );
		$this->controls['moduleGap'] = $this->number( 'layout', esc_html__( 'Space between sidebar sections (px)', 'pfh-widgets' ), 24, 8, 64 );
		$this->controls['padTop']   = $this->number( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 30, 0, 200 );
		$this->controls['padBottom'] = $this->number( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 88, 0, 200 );
	}

	/**
	 * Default promise rows matching the approved storefront card.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function default_promises() {
		return [
			[ 'text' => '5 sterren op WebwinkelKeur', 'icon' => 'star', 'compact' => false ],
			[ 'text' => 'De beste kwaliteit die er is', 'icon' => 'quality', 'compact' => false ],
			[ 'text' => 'Gratis verzending vanaf € 59', 'icon' => 'delivery', 'compact' => false ],
			[ 'text' => 'Retourneren binnen 30 dagen', 'icon' => 'returns', 'compact' => false ],
			[ 'text' => 'iDEAL/WERO', 'icon' => 'check', 'compact' => true ],
			[ 'text' => 'Bancontact', 'icon' => 'check', 'compact' => true ],
			[ 'text' => 'PayPal', 'icon' => 'check', 'compact' => true ],
			[ 'text' => 'Creditcard', 'icon' => 'check', 'compact' => true ],
		];
	}

	/* ---- control helpers ---- */

	private function text( $group, $label, $default = '', array $extra = [] ) {
		return array_merge( [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'text', 'default' => $default ], $extra );
	}

	private function switch_field( $group, $label, $on = true ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'checkbox', 'default' => $on ];
	}

	private function colour( $group, $label, $default ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'color', 'inline' => true, 'default' => [ 'hex' => $default ] ];
	}

	private function number( $group, $label, $default, $min, $max ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'number', 'min' => $min, 'max' => $max, 'inline' => true, 'default' => $default ];
	}

	/**
	 * Stable id for this element instance.
	 *
	 * @return string
	 */
	private function uid() {
		if ( null === $this->uid ) {
			$this->uid = ! empty( $this->element['id'] ) ? sanitize_html_class( $this->element['id'] ) : uniqid( 'pfh-post-' );
		}

		return $this->uid;
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$post = $this->post();

		if ( ! $post ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-post pfh-post--empty"><p>'
					. esc_html__( 'Nothing to show: this page has no article, and none is named above.', 'pfh-widgets' )
					. '</p></div>';
			}

			return;
		}

		$article = PFH_Widgets_Post_Body::prepare(
			$post,
			$this->switched_on( 'showToc' ),
			'h2' === $this->setting( 'tocDepth', 'h2h3' ) ? [ 'h2' ] : [ 'h2', 'h3' ]
		);
		$products = $this->switched_on( 'showProducts' ) ? $this->product_cards() : [];
		$promises = $this->switched_on( 'showPromises' ) ? $this->promise_items() : [];
		$show_cta = $this->switched_on( 'showCta' );
		$sidebar  = $this->switched_on( 'showSearch' ) || ! empty( $article['toc'] ) || ! empty( $products ) || ! empty( $promises ) || $show_cta;

		$this->set_attribute( '_root', 'class', [ 'pfh-post', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<article ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.

		if ( $this->switched_on( 'showProgress' ) ) {
			echo '<div class="pfh-post__progress" data-pfh-post-progress aria-hidden="true"><span></span></div>';
		}

		echo '<div class="pfh-post__inner">';

		$this->render_crumbs( $post );
		$this->render_head( $post );

		printf( '<div class="pfh-post__layout%s">', $sidebar ? '' : ' pfh-post__layout--wide' );
		echo '<div class="pfh-post__main">';

		$this->render_hero( $post );

		printf( '<div class="pfh-post__body" data-pfh-post-body>%s</div>', $article['html'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content, already filtered.

		$this->render_share( $post );
		$this->render_bio( $post );
		$this->render_nav( $post );

		echo '</div>';

		if ( $sidebar ) {
			echo '<aside class="pfh-post__sidebar" aria-label="' . esc_attr__( 'Artikelhulpmiddelen en aanbevolen producten', 'pfh-widgets' ) . '">';
			$this->render_search();

			if ( $article['toc'] ) {
				$this->render_toc( $article['toc'] );
			}

			$this->render_products( $products );
			$this->render_promises( $promises );
			$this->render_cta( $show_cta );
			echo '</aside>';
		}

		echo '</div>';

		$this->render_related( $post );

		echo '</div></article>';
	}

	/**
	 * @param WP_Post $post Article.
	 */
	private function render_crumbs( $post ) {
		if ( ! $this->switched_on( 'showCrumbs' ) ) {
			return;
		}

		$crumbs = [
			[ home_url( '/' ), (string) $this->setting( 'homeLabel', '' ) ],
		];

		$blog = PFH_Widgets_Blog::blog_url();
		$label = trim( (string) $this->setting( 'blogLabel', '' ) );

		if ( '' !== $blog && '' !== $label ) {
			$crumbs[] = [ $blog, $label ];
		}

		$term = PFH_Widgets_Post_Body::first_term( $post );

		if ( $term ) {
			$crumbs[] = [ (string) get_category_link( $term ), $term->name ];
		}

		echo '<ol class="pfh-post__crumbs">';

		foreach ( $crumbs as $crumb ) {
			if ( '' === trim( (string) $crumb[1] ) ) {
				continue;
			}

			printf( '<li><a href="%s">%s</a></li>', esc_url( $crumb[0] ), esc_html( $crumb[1] ) );
		}

		printf( '<li><span aria-current="page">%s</span></li>', esc_html( get_the_title( $post ) ) );

		echo '</ol>';
	}

	/**
	 * @param WP_Post $post Article.
	 */
	private function render_head( $post ) {
		echo '<header class="pfh-post__head">';

		if ( $this->switched_on( 'showTag' ) ) {
			$term = PFH_Widgets_Post_Body::first_term( $post );

			if ( $term ) {
				printf(
					'<a class="pfh-post__tag" href="%s">%s</a>',
					esc_url( (string) get_category_link( $term ) ),
					esc_html( $term->name )
				);
			}
		}

		printf( '<h1 class="pfh-post__title">%s</h1>', esc_html( get_the_title( $post ) ) );

		$meta = '';

		if ( $this->switched_on( 'showAuthor' ) ) {
			$author = (int) $post->post_author;
			$name   = trim( (string) get_the_author_meta( 'display_name', $author ) );

			// An imported post can have no author at all; an empty space where
			// a name should be reads as something broken.
			if ( '' !== $name ) {
				$meta .= sprintf(
					'<span>%s%s</span>',
					get_avatar( $author, 52, '', '', [ 'class' => 'pfh-post__avatar', 'loading' => 'lazy' ] ),
					esc_html( $name )
				);
			}
		}

		if ( $this->switched_on( 'showDate' ) ) {
			$meta .= sprintf(
				'<span><time datetime="%s">%s</time></span>',
				esc_attr( (string) get_the_date( 'c', $post ) ),
				esc_html( (string) get_the_date( '', $post ) )
			);
		}

		if ( $this->switched_on( 'showRead' ) ) {
			$wording = trim( (string) $this->setting( 'readLabel', '' ) );
			$wording = '' !== $wording ? $wording : '%s min';

			$meta .= sprintf(
				'<span>%s%s</span>',
				PFH_Widgets_Icons::get( 'clock' ),
				esc_html( sprintf( $wording, number_format_i18n( PFH_Widgets_Blog::reading_time( $post ) ) ) )
			);
		}

		if ( '' !== $meta ) {
			echo '<div class="pfh-post__meta">' . $meta . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above.
		}

		echo '</header>';
	}

	/**
	 * @param WP_Post $post Article.
	 */
	private function render_hero( $post ) {
		if ( ! $this->switched_on( 'showHero' ) || ! has_post_thumbnail( $post ) ) {
			return;
		}

		$caption = wp_get_attachment_caption( (int) get_post_thumbnail_id( $post ) );

		echo '<figure class="pfh-post__hero">';
		echo get_the_post_thumbnail( $post, 'full', [ 'loading' => 'eager', 'decoding' => 'async' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress escapes it.

		if ( $caption ) {
			printf( '<figcaption>%s</figcaption>', esc_html( $caption ) );
		}

		echo '</figure>';
	}

	/**
	 * @param array $toc Headings.
	 */
	private function render_toc( array $toc ) {
		$title = trim( (string) $this->setting( 'tocTitle', '' ) );

		// Kept folded on every viewport so it never competes with the article.
		printf(
			'<details class="pfh-post__toc" data-pfh-post-toc><summary>%s</summary><ul class="pfh-post__toc-list">',
			esc_html( $title )
		);

		foreach ( $toc as $item ) {
			printf(
				'<li class="pfh-post__toc-item pfh-post__toc-item--%d"><a class="pfh-post__toc-link" href="#%s" title="%s">%s</a></li>',
				(int) $item['level'],
				esc_attr( $item['id'] ),
				esc_attr( $item['text'] ),
				esc_html( PFH_Widgets_Helpers::shorten( $item['text'] ) )
			);
		}

		echo '</ul></details>';
	}

	/**
	 * Search shortcut. The article script opens the search panel supplied by
	 * the PFH header and lets the ordinary search URL work when JavaScript or
	 * that header is not present.
	 */
	private function render_search() {
		if ( ! $this->switched_on( 'showSearch' ) ) {
			return;
		}

		$label    = PFH_Widgets_Helpers::dd( (string) $this->setting( 'searchLabel', '' ) );
		$shortcut = trim( (string) $this->setting( 'searchShortcut', '' ) );

		echo '<a class="pfh-post__search" href="' . esc_url( home_url( '/?s=' ) ) . '" data-pfh-post-search role="button" aria-haspopup="dialog" aria-keyshortcuts="Meta+K Control+K">';
		echo PFH_Widgets_Icons::get( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		echo '<span class="pfh-post__search-label">' . esc_html( $label ) . '</span>';

		if ( '' !== $shortcut ) {
			echo '<kbd class="pfh-post__search-shortcut" aria-hidden="true">' . esc_html( $shortcut ) . '</kbd>';
		}

		echo '</a>';
	}

	/**
	 * @param array<int, WC_Product> $products Products.
	 */
	private function render_products( array $products ) {
		if ( ! $products ) {
			return;
		}

		$title      = PFH_Widgets_Helpers::dd( (string) $this->setting( 'productsTitle', '' ) );
		$track_id   = 'pfh-post-products-' . $this->uid();
		$heading_id = $track_id . '-title';

		echo '<section class="pfh-post__products" aria-labelledby="' . esc_attr( $heading_id ) . '">';
		echo '<div class="pfh-post__module-head">';
		echo '<h2 id="' . esc_attr( $heading_id ) . '">' . esc_html( $title ) . '</h2>';

		if ( $this->switched_on( 'productsArrows' ) && count( $products ) > 1 ) {
			echo '<div class="pfh-post__slider-controls">';
			printf(
				'<button type="button" class="pfh-post__slider-button" data-pfh-post-products-direction="prev" aria-controls="%s" aria-label="%s">%s</button>',
				esc_attr( $track_id ),
				esc_attr__( 'Vorige producten', 'pfh-widgets' ),
				PFH_Widgets_Icons::get( 'nav-left' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
			printf(
				'<button type="button" class="pfh-post__slider-button" data-pfh-post-products-direction="next" aria-controls="%s" aria-label="%s">%s</button>',
				esc_attr( $track_id ),
				esc_attr__( 'Volgende producten', 'pfh-widgets' ),
				PFH_Widgets_Icons::get( 'nav-right' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
			echo '</div>';
		}

		echo '</div>';
		printf( '<div class="pfh-post__product-track" id="%s" data-pfh-post-products tabindex="0" aria-label="%s">', esc_attr( $track_id ), esc_attr( $title ) );

		foreach ( $products as $product ) {
			$this->render_product_card( $product );
		}

		echo '</div></section>';
	}

	/**
	 * @param array<int, array<string, mixed>> $promises Promise rows.
	 */
	private function render_promises( array $promises ) {
		if ( ! $promises ) {
			return;
		}

		$title      = PFH_Widgets_Helpers::dd( (string) $this->setting( 'promisesTitle', '' ) );
		$heading_id = 'pfh-post-promises-' . $this->uid();

		echo '<section class="pfh-post__promises" aria-labelledby="' . esc_attr( $heading_id ) . '">';
		echo '<h2 id="' . esc_attr( $heading_id ) . '">' . esc_html( $title ) . '</h2><ul>';

		foreach ( $promises as $row ) {
			$classes = ! empty( $row['compact'] ) ? ' class="is-compact"' : '';
			echo '<li' . $classes . '><span class="pfh-post__promise-icon">' . $this->promise_icon( (string) $row['icon'] ) . '</span><span>' . esc_html( $row['text'] ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- icon is from the registry.
		}

		echo '</ul></section>';
	}

	/**
	 * @param bool $show Whether the module is enabled.
	 */
	private function render_cta( $show ) {
		if ( ! $show ) {
			return;
		}

		$default_image = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/generated-image-6-1024x1024.webp';
		$image         = PFH_Widgets_Helpers::image_url( $this->setting( 'ctaImage' ), 'large' );
		$image         = $image ? $image : $default_image;
		$shop          = function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : '';
		$link          = PFH_Widgets_Helpers::link( $this->setting( 'ctaLink' ), $shop ? $shop : home_url( '/shop/' ) );
		$heading_id    = 'pfh-post-cta-' . $this->uid();
		$style         = '--pfh-po-cta-image:url("' . esc_url_raw( $image ) . '")';

		echo '<section class="pfh-post__cta" aria-labelledby="' . esc_attr( $heading_id ) . '" style="' . esc_attr( $style ) . '">';
		echo '<p class="pfh-post__cta-eyebrow">' . esc_html( PFH_Widgets_Helpers::dd( (string) $this->setting( 'ctaEyebrow', '' ) ) ) . '</p>';
		echo '<h2 id="' . esc_attr( $heading_id ) . '">' . esc_html( PFH_Widgets_Helpers::dd( (string) $this->setting( 'ctaTitle', '' ) ) ) . '</h2>';
		echo '<p class="pfh-post__cta-text">' . esc_html( PFH_Widgets_Helpers::dd( (string) $this->setting( 'ctaText', '' ) ) ) . '</p>';

		if ( '' !== trim( (string) $this->setting( 'ctaButton', '' ) ) && '' !== $link['href'] ) {
			echo '<a class="pfh-post__cta-button"' . PFH_Widgets_Helpers::link_attrs( $link ) . '><span>' . esc_html( PFH_Widgets_Helpers::dd( (string) $this->setting( 'ctaButton', '' ) ) ) . '</span>' . PFH_Widgets_Icons::get( 'arrow' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper escapes attributes and icon is static.
		}

		echo '</section>';
	}

	/**
	 * @param WP_Post $post Article.
	 */
	private function render_share( $post ) {
		if ( ! $this->switched_on( 'showShare' ) ) {
			return;
		}

		$url   = (string) get_permalink( $post );
		$title = get_the_title( $post );

		echo '<div class="pfh-post__share">';
		printf( '<span class="pfh-post__share-label">%s</span>', esc_html( (string) $this->setting( 'shareLabel', '' ) ) );

		$places = [
			'whatsapp' => [ 'https://wa.me/?text=' . rawurlencode( $title . ' ' . $url ), 'WhatsApp' ],
			'facebook' => [ 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ), 'Facebook' ],
			'linkedin' => [ 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ), 'LinkedIn' ],
			'x'        => [ 'https://x.com/intent/tweet?text=' . rawurlencode( $title ) . '&url=' . rawurlencode( $url ), 'X' ],
		];

		foreach ( $places as $icon => $place ) {
			printf(
				'<a class="pfh-post__share-link" href="%s" target="_blank" rel="noopener noreferrer nofollow" aria-label="%s">%s</a>',
				esc_url( $place[0] ),
				esc_attr( sprintf( /* translators: network name */ __( 'Deel op %s', 'pfh-widgets' ), $place[1] ) ),
				PFH_Widgets_Icons::get( $icon ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			);
		}

		printf(
			'<button type="button" class="pfh-post__share-link" data-pfh-post-copy data-pfh-post-url="%s" data-pfh-post-done="%s" aria-label="%s">%s</button>',
			esc_url( $url ),
			esc_attr( (string) $this->setting( 'copyLabel', '' ) ),
			esc_attr__( 'Kopieer de link', 'pfh-widgets' ),
			PFH_Widgets_Icons::get( 'link' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		);

		echo '</div>';
	}

	/**
	 * @param WP_Post $post Article.
	 */
	private function render_bio( $post ) {
		if ( ! $this->switched_on( 'showBio', false ) ) {
			return;
		}

		$author = (int) $post->post_author;
		$bio    = (string) get_the_author_meta( 'description', $author );

		if ( '' === trim( $bio ) ) {
			return;
		}

		printf(
			'<div class="pfh-post__author">%s<div><h3>%s</h3><p>%s</p></div></div>',
			get_avatar( $author, 128, '', '', [ 'loading' => 'lazy' ] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress escapes it.
			esc_html( (string) get_the_author_meta( 'display_name', $author ) ),
			esc_html( $bio )
		);
	}

	/**
	 * @param WP_Post $post Article.
	 */
	private function render_nav( $post ) {
		if ( ! $this->switched_on( 'showNav' ) ) {
			return;
		}

		// get_adjacent_post() reads the loop, so it is told which post to use.
		$previous = PFH_Widgets_Post_Body::adjacent( $post, true );
		$next     = PFH_Widgets_Post_Body::adjacent( $post, false );

		if ( ! $previous && ! $next ) {
			return;
		}

		echo '<nav class="pfh-post__nav">';

		if ( $previous ) {
			printf(
				'<a class="pfh-post__nav-link pfh-post__nav-link--prev" href="%s">%s<span><span class="pfh-post__nav-dir">%s</span><span class="pfh-post__nav-title">%s</span></span></a>',
				esc_url( (string) get_permalink( $previous ) ),
				PFH_Widgets_Icons::get( 'nav-left' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				esc_html( (string) $this->setting( 'prevLabel', '' ) ),
				esc_html( get_the_title( $previous ) )
			);
		}

		if ( $next ) {
			printf(
				'<a class="pfh-post__nav-link pfh-post__nav-link--next" href="%s">%s<span><span class="pfh-post__nav-dir">%s</span><span class="pfh-post__nav-title">%s</span></span></a>',
				esc_url( (string) get_permalink( $next ) ),
				PFH_Widgets_Icons::get( 'nav-right' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				esc_html( (string) $this->setting( 'nextLabel', '' ) ),
				esc_html( get_the_title( $next ) )
			);
		}

		echo '</nav>';
	}

	/**
	 * @param WP_Post $post Article.
	 */
	private function render_related( $post ) {
		if ( ! $this->switched_on( 'showRelated' ) ) {
			return;
		}

		$posts = PFH_Widgets_Post_Body::related( $post, (int) $this->setting( 'relatedCount', 3 ) );

		if ( ! $posts ) {
			return;
		}

		/*
		 * The blog's own card with the blog's own defaults — not a trimmed
		 * version of it. Asking for fewer words and no link gave a card that
		 * was recognisably the same shape but plainly not the same card, which
		 * is the worst of both.
		 */
		$options = PFH_Widgets_Blog::options(
			[
				'more_label' => (string) $this->setting( 'relatedMore', '' ),
				'read_label' => (string) $this->setting( 'readLabel', '' ),
			]
		);

		echo '<div class="pfh-post__related">';
		printf( '<h2 class="pfh-post__related-title">%s</h2>', esc_html( (string) $this->setting( 'relatedTitle', '' ) ) );
		/*
		 * -cols-set, not -cols: the inline value is the starting number, and
		 * the blog's own breakpoints still get to cut it down to two and then
		 * to one. An inline --pfh-bl-cols would have beaten them both.
		 */
		printf(
			'<ul class="pfh-blog__grid pfh-blog-cards" style="--pfh-bl-cols-set:%d">%s</ul>',
			count( $posts ),
			PFH_Widgets_Blog::cards( $posts, $options ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built there.
		);
		echo '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Sidebar data
	 * ------------------------------------------------------------------ */

	/**
	 * Clean the editable promises before rendering.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function promise_items() {
		$rows = (array) $this->setting( 'promises', $this->default_promises() );
		$out  = [];

		foreach ( $rows as $row ) {
			$text = isset( $row['text'] ) ? trim( PFH_Widgets_Helpers::dd( (string) $row['text'] ) ) : '';

			if ( '' === $text ) {
				continue;
			}

			$out[] = [
				'text'    => $text,
				'icon'    => isset( $row['icon'] ) ? sanitize_key( (string) $row['icon'] ) : 'check',
				'compact' => ! empty( $row['compact'] ),
			];
		}

		return $out;
	}

	/**
	 * @param string $name Editor icon key.
	 * @return string
	 */
	private function promise_icon( $name ) {
		$icons = [
			'star'     => 'star',
			'quality'  => 'usp-natural',
			'delivery' => 'usp-delivery',
			'returns'  => 'box-check',
			'check'    => 'check',
		];

		return PFH_Widgets_Icons::get( isset( $icons[ $name ] ) ? $icons[ $name ] : 'check' );
	}

	/**
	 * Products selected by the article sidebar controls.
	 *
	 * @return array<int, WC_Product>
	 */
	private function product_cards() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! function_exists( 'wc_get_product' ) ) {
			return [];
		}

		$query    = new WP_Query( $this->product_query_args() );
		$products = [];

		foreach ( $query->posts as $product_post ) {
			$product = wc_get_product( $product_post );

			if ( ! $product || ! is_callable( [ $product, 'is_visible' ] ) || ! $product->is_visible() ) {
				continue;
			}

			$products[] = $product;
		}

		wp_reset_postdata();

		return $products;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function product_query_args() {
		$source = (string) $this->setting( 'productsSource', 'best' );
		$limit  = max( 2, min( 12, (int) $this->setting( 'productsCount', 6 ) ) );
		$args   = [
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'meta_key'            => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		];

		if ( taxonomy_exists( 'product_visibility' ) ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => [ 'exclude-from-catalog', 'outofstock' ],
					'operator' => 'NOT IN',
				],
			];
		}

		switch ( $source ) {
			case 'recent':
				unset( $args['meta_key'] );
				$args['orderby'] = 'date';
				break;

			case 'onsale':
				$ids              = function_exists( 'wc_get_product_ids_on_sale' ) ? array_map( 'absint', wc_get_product_ids_on_sale() ) : [];
				$args['post__in'] = $ids ? $ids : [ 0 ];
				$args['orderby']  = 'post__in';
				unset( $args['meta_key'], $args['order'] );
				break;

			case 'featured':
				unset( $args['meta_key'] );
				$args['orderby'] = 'menu_order title';
				$args['order']   = 'ASC';
				$args['tax_query'][] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
				];
				break;

			case 'category':
				$categories = PFH_Widgets_Helpers::category_ids( $this->setting( 'productsCategory' ) );

				if ( $categories ) {
					$args['tax_query'][] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						'taxonomy'         => 'product_cat',
						'field'            => 'term_id',
						'terms'            => $categories,
						'include_children' => true,
					];
				} else {
					$args['post__in'] = [ 0 ];
				}
				break;

			case 'ids':
				$ids              = $this->product_ids( $this->setting( 'productIds', '' ) );
				$args['post__in'] = $ids ? $ids : [ 0 ];
				$args['orderby']  = 'post__in';
				unset( $args['meta_key'], $args['order'] );
				break;
		}

		/**
		 * Filter products shown beside an article.
		 *
		 * @param array  $args   WP_Query arguments.
		 * @param string $source Selected source.
		 */
		return apply_filters( 'pfh_widgets_post_products_args', $args, $source );
	}

	/**
	 * @param mixed $value Comma separated ids.
	 * @return int[]
	 */
	private function product_ids( $value ) {
		$ids = array_map( 'absint', array_map( 'trim', explode( ',', (string) $value ) ) );

		return array_values( array_filter( $ids ) );
	}

	/**
	 * @param WC_Product $product Product.
	 */
	private function render_product_card( $product ) {
		$title = (string) $product->get_name();
		$link  = (string) $product->get_permalink();
		$brand = $this->product_brand( (int) $product->get_id() );
		$badge = $this->product_badge( $product );
		$image = $product->get_image(
			'woocommerce_thumbnail',
			[
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => $title,
			]
		);

		echo '<article class="pfh-post__product-card">';
		echo '<a class="pfh-post__product-link" href="' . esc_url( $link ) . '" aria-label="' . esc_attr( $title ) . '">';
		echo '<span class="pfh-post__product-media">';

		if ( '' !== $badge ) {
			echo '<span class="pfh-post__product-badge">' . esc_html( $badge ) . '</span>';
		}

		echo wp_kses_post( $image ) . '</span><span class="pfh-post__product-body">';

		if ( '' !== $brand ) {
			echo '<span class="pfh-post__product-brand">' . esc_html( $brand ) . '</span>';
		}

		echo '<span class="pfh-post__product-title">' . esc_html( $title ) . '</span>';
		echo '<span class="pfh-post__product-price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
		echo '</span></a></article>';
	}

	/**
	 * @param WC_Product $product Product.
	 * @return string
	 */
	private function product_badge( $product ) {
		if ( $product->is_on_sale() ) {
			return trim( (string) $this->setting( 'saleLabel', '' ) );
		}

		$source = (string) $this->setting( 'productsSource', 'best' );

		if ( 'recent' === $source ) {
			return trim( (string) $this->setting( 'newLabel', '' ) );
		}

		return 'best' === $source ? trim( (string) $this->setting( 'bestsellerLabel', '' ) ) : '';
	}

	/**
	 * Prefer a store brand taxonomy and fall back to the product category.
	 *
	 * @param int $product_id Product id.
	 * @return string
	 */
	private function product_brand( $product_id ) {
		foreach ( [ 'product_brand', 'pwb-brand', 'pa_merk', 'product_cat' ] as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$terms = get_the_terms( $product_id, $taxonomy );

			if ( $terms && ! is_wp_error( $terms ) ) {
				$term = reset( $terms );

				return $term instanceof WP_Term ? (string) $term->name : '';
			}
		}

		return '';
	}

	/* ---------------------------------------------------------------------
	 * Which article
	 * ------------------------------------------------------------------ */

	/**
	 * @return WP_Post|null
	 */
	private function post() {
		$id = is_singular( 'post' ) ? get_queried_object_id() : 0;

		if ( ! $id ) {
			$id = (int) $this->setting( 'previewId', 0 );
		}

		if ( ! $id ) {
			global $post;

			if ( $post && 'post' === get_post_type( $post ) ) {
				$id = (int) $post->ID;
			}
		}

		if ( ! $id ) {
			$recent = get_posts( [ 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ] );
			$id     = $recent ? (int) $recent[0] : 0;
		}

		$found = $id ? get_post( $id ) : null;

		return ( $found && 'post' === $found->post_type ) ? $found : null;
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-po-max'         => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1240 ) ),
				'--pfh-po-measure-set' => PFH_Widgets_Helpers::unit( $this->setting( 'measure', 880 ) ),
				'--pfh-po-aside-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'asideWidth', 320 ) ),
				'--pfh-po-gap-set'     => PFH_Widgets_Helpers::unit( $this->setting( 'columnGap', 64 ) ),
				'--pfh-po-module-gap-set' => PFH_Widgets_Helpers::unit( $this->setting( 'moduleGap', 24 ) ),
				'--pfh-po-pt-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 30 ) ),
				'--pfh-po-pb-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 88 ) ),
				'--pfh-po-radius'      => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 16 ) ),
				'--pfh-po-title-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 42 ) ),
				'--pfh-po-title-mobile-set' => PFH_Widgets_Helpers::unit( $this->setting( 'titleSizeMobile', 32 ) ),
				'--pfh-po-text-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'textSize', 17 ) ),
				'--pfh-po-sidebar-title-set' => PFH_Widgets_Helpers::unit( $this->setting( 'sidebarTitleSize', 18 ) ),
				'--pfh-po-ink'         => PFH_Widgets_Helpers::color( $this->setting( 'ink' ), '#22301c' ),
				'--pfh-po-body'        => PFH_Widgets_Helpers::color( $this->setting( 'bodyInk' ), '#43503f' ),
				'--pfh-po-muted'       => PFH_Widgets_Helpers::color( $this->setting( 'mutedInk' ), '#8d9589' ),
				'--pfh-po-accent'      => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#2b5f63' ),
				'--pfh-po-line'        => PFH_Widgets_Helpers::color( $this->setting( 'lineColor' ), '#eaeaea' ),
				'--pfh-po-tag-bg'      => PFH_Widgets_Helpers::color( $this->setting( 'tagBg' ), '#dfe9dc' ),
				'--pfh-po-tag-ink'     => PFH_Widgets_Helpers::color( $this->setting( 'tagInk' ), '#46603f' ),
				'--pfh-po-search-bg'   => PFH_Widgets_Helpers::color( $this->setting( 'searchBg' ), '#ffffff' ),
				'--pfh-po-search-ink'  => PFH_Widgets_Helpers::color( $this->setting( 'searchInk' ), '#7f897b' ),
				'--pfh-po-search-border' => PFH_Widgets_Helpers::color( $this->setting( 'searchBorder' ), '#e4e8e1' ),
				'--pfh-po-search-radius' => PFH_Widgets_Helpers::unit( $this->setting( 'searchRadius', 14 ) ),
				'--pfh-po-product-bg'  => PFH_Widgets_Helpers::color( $this->setting( 'productBg' ), '#ffffff' ),
				'--pfh-po-product-media' => PFH_Widgets_Helpers::color( $this->setting( 'productMediaBg' ), '#f6f1e7' ),
				'--pfh-po-product-border' => PFH_Widgets_Helpers::color( $this->setting( 'productBorder' ), '#e3cbbd' ),
				'--pfh-po-product-radius' => PFH_Widgets_Helpers::unit( $this->setting( 'productRadius', 16 ) ),
				'--pfh-po-promises-bg' => PFH_Widgets_Helpers::color( $this->setting( 'promisesBg' ), '#f6f6f6' ),
				'--pfh-po-promises-ink' => PFH_Widgets_Helpers::color( $this->setting( 'promisesInk' ), '#596b54' ),
				'--pfh-po-promises-icon' => PFH_Widgets_Helpers::color( $this->setting( 'promisesIcon' ), '#83a07c' ),
				'--pfh-po-promises-radius' => PFH_Widgets_Helpers::unit( $this->setting( 'promisesRadius', 24 ) ),
				'--pfh-po-cta-top'     => PFH_Widgets_Helpers::color( $this->setting( 'ctaOverlayTop' ), 'rgba(34,48,28,.94)' ),
				'--pfh-po-cta-bottom'  => PFH_Widgets_Helpers::color( $this->setting( 'ctaOverlayBottom' ), 'rgba(34,48,28,.12)' ),
				'--pfh-po-cta-ink'     => PFH_Widgets_Helpers::color( $this->setting( 'ctaInk' ), '#ffffff' ),
				'--pfh-po-cta-button-bg' => PFH_Widgets_Helpers::color( $this->setting( 'ctaButtonBg' ), '#ffffff' ),
				'--pfh-po-cta-button-ink' => PFH_Widgets_Helpers::color( $this->setting( 'ctaButtonInk' ), '#22301c' ),
				'--pfh-po-cta-height'  => PFH_Widgets_Helpers::unit( $this->setting( 'ctaHeight', 390 ) ),
				'--pfh-po-cta-radius'  => PFH_Widgets_Helpers::unit( $this->setting( 'ctaRadius', 18 ) ),
			]
		);
	}
}
