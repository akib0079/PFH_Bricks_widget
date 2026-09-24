<?php
/**
 * Bricks element: Products For Home blog.
 *
 * An opening, an optional lead article, and a grid of cards — on the blog
 * index, on a category archive, or anywhere a few recent posts belong.
 *
 * On an archive it draws the query WordPress already ran rather than running
 * one of its own, so the category page, the search results and page two all
 * say what they are supposed to say without anyone configuring it.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PFH_Widgets_Blog' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'includes/class-pfh-blog.php';
}

class PFH_Element_Blog extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-blog';
	public $icon         = 'ti-layout-grid2';
	public $css_selector = '.pfh-blog';

	public function get_label() {
		return esc_html__( 'PFH Blog', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'blog', 'posts', 'articles', 'news', 'archive', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::blog();
	}

	public function set_control_groups() {
		foreach ( [
			'head'   => esc_html__( 'Opening', 'pfh-widgets' ),
			'source' => esc_html__( 'Posts', 'pfh-widgets' ),
			'card'   => esc_html__( 'Card', 'pfh-widgets' ),
			'pager'  => esc_html__( 'More posts', 'pfh-widgets' ),
			'style'  => esc_html__( 'Style', 'pfh-widgets' ),
			'layout' => esc_html__( 'Layout', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		/* ---- opening ---- */

		$this->controls['eyebrow'] = $this->text( 'head', esc_html__( 'Eyebrow', 'pfh-widgets' ), 'Blog' );

		$this->controls['title'] = $this->text(
			'head',
			esc_html__( 'Title', 'pfh-widgets' ),
			'Verhalen uit <em>Griekenland</em>',
			[ 'description' => esc_html__( 'A word wrapped in <em> is set in the italic serif. Empty leaves the opening out.', 'pfh-widgets' ) ]
		);

		$this->controls['lede'] = $this->text( 'head', esc_html__( 'Line under it', 'pfh-widgets' ), 'Over honing, olijfolie en het eiland waar ze vandaan komen.' );

		$this->controls['showTerms'] = $this->switch_field( 'head', esc_html__( 'Show the categories', 'pfh-widgets' ), false );
		$this->controls['allLabel']  = $this->text( 'head', esc_html__( 'Label for all of them', 'pfh-widgets' ), 'Alles' );

		/* ---- posts ---- */

		$this->controls['source'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Which posts', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'default'     => 'latest',
			'options'     => [
				'latest'  => esc_html__( 'The most recent', 'pfh-widgets' ),
				'current' => esc_html__( 'Whatever this page is about', 'pfh-widgets' ),
			],
			'description' => esc_html__( 'The second is for a blog or category template: it draws the query WordPress already ran, so the page, its paging and its search all follow.', 'pfh-widgets' ),
		];

		$this->controls['category'] = [
			'tab'      => 'content',
			'group'    => 'source',
			'label'    => esc_html__( 'Category', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'default'  => '',
			'options'  => PFH_Widgets_Blog::category_options(),
			'required' => [ 'source', '=', 'latest' ],
		];

		$this->controls['perPage'] = $this->number( 'source', esc_html__( 'Posts to show', 'pfh-widgets' ), 6, 1, 24 );
		$this->controls['lead']    = $this->switch_field( 'source', esc_html__( 'Give the first post the full width', 'pfh-widgets' ), false );
		$this->controls['emptyText'] = $this->text( 'source', esc_html__( 'When there is nothing to show', 'pfh-widgets' ), 'Er staat hier nog niets. Kom snel weer terug.' );

		/* ---- card ---- */

		$this->controls['showImage']   = $this->switch_field( 'card', esc_html__( 'Picture', 'pfh-widgets' ) );
		$this->controls['showTag']     = $this->switch_field( 'card', esc_html__( 'Category on the picture', 'pfh-widgets' ) );
		$this->controls['showDate']    = $this->switch_field( 'card', esc_html__( 'Date', 'pfh-widgets' ) );
		$this->controls['showRead']    = $this->switch_field( 'card', esc_html__( 'Reading time', 'pfh-widgets' ) );
		$this->controls['showExcerpt'] = $this->switch_field( 'card', esc_html__( 'Summary', 'pfh-widgets' ) );

		$this->controls['excerptWords'] = $this->number( 'card', esc_html__( 'Words in the summary', 'pfh-widgets' ), 22, 6, 80 );

		$this->controls['moreLabel'] = $this->text( 'card', esc_html__( 'Link under the card', 'pfh-widgets' ), 'Lees verder' );

		$this->controls['readLabel'] = $this->text(
			'card',
			esc_html__( 'Reading-time wording', 'pfh-widgets' ),
			'%s min leestijd',
			[ 'description' => esc_html__( '%s becomes the number of minutes.', 'pfh-widgets' ) ]
		);

		/* ---- more posts ---- */

		$this->controls['pager'] = [
			'tab'         => 'content',
			'group'       => 'pager',
			'label'       => esc_html__( 'More posts', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'default'     => 'load',
			'options'     => [
				'load'    => esc_html__( 'A load more button', 'pfh-widgets' ),
				'numbers' => esc_html__( 'Page numbers', 'pfh-widgets' ),
				'none'    => esc_html__( 'Nothing', 'pfh-widgets' ),
			],
			'description' => esc_html__( 'The button adds the next posts without a reload, and falls back to a link if scripting is off.', 'pfh-widgets' ),
		];

		$this->controls['loadLabel'] = $this->text( 'pager', esc_html__( 'Button', 'pfh-widgets' ), 'Meer verhalen' );

		/* ---- style ---- */

		$this->controls['ink']      = $this->colour( 'style', esc_html__( 'Headings', 'pfh-widgets' ), '#22301c' );
		$this->controls['bodyInk']  = $this->colour( 'style', esc_html__( 'Body text', 'pfh-widgets' ), '#5c6657' );
		$this->controls['mutedInk'] = $this->colour( 'style', esc_html__( 'Meta text', 'pfh-widgets' ), '#8d9589' );
		$this->controls['accent']   = $this->colour( 'style', esc_html__( 'Links and buttons', 'pfh-widgets' ), '#2b5f63' );
		$this->controls['lineColor'] = $this->colour( 'style', esc_html__( 'Borders', 'pfh-widgets' ), '#eaeaea' );
		$this->controls['tagBg']    = $this->colour( 'style', esc_html__( 'Category pill', 'pfh-widgets' ), '#dfe9dc' );
		$this->controls['tagInk']   = $this->colour( 'style', esc_html__( 'Category pill text', 'pfh-widgets' ), '#46603f' );

		$this->controls['titleSize'] = $this->number( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 40, 22, 64 );
		$this->controls['cardSize']  = $this->number( 'style', esc_html__( 'Card heading size (px)', 'pfh-widgets' ), 20, 14, 34 );
		$this->controls['radius']    = $this->number( 'style', esc_html__( 'Picture radius (px)', 'pfh-widgets' ), 16, 0, 40 );

		$this->controls['ratio'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Picture shape', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'default' => '3 / 2',
			'options' => [
				'3 / 2'  => esc_html__( 'Landscape', 'pfh-widgets' ),
				'4 / 3'  => esc_html__( 'Softer landscape', 'pfh-widgets' ),
				'1 / 1'  => esc_html__( 'Square', 'pfh-widgets' ),
				'16 / 9' => esc_html__( 'Wide', 'pfh-widgets' ),
			],
		];

		/* ---- layout ---- */

		$this->controls['maxWidth'] = $this->number( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1140, 600, 1600 );
		$this->controls['columns']  = $this->number( 'layout', esc_html__( 'Cards per row', 'pfh-widgets' ), 3, 1, 4 );
		$this->controls['gap']      = $this->number( 'layout', esc_html__( 'Space between cards (px)', 'pfh-widgets' ), 32, 12, 72 );
		$this->controls['padTop']   = $this->number( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 72, 0, 200 );
		$this->controls['padBottom'] = $this->number( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 72, 0, 200 );
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

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		if ( class_exists( 'PFH_Widgets_License' ) && PFH_Widgets_License::locked() ) {
			echo PFH_Widgets_License::locked_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped in locked_markup().

			return;
		}

		$options = $this->options();
		$query   = PFH_Widgets_Blog::query( $options );

		$this->set_attribute( '_root', 'class', [ 'pfh-blog', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-blog__inner">';

		$this->render_head();

		if ( ! $query->have_posts() ) {
			printf(
				'<div class="pfh-blog__none"><p>%s</p></div>',
				esc_html( (string) $this->setting( 'emptyText', '' ) )
			);

			echo '</div></section>';
			wp_reset_postdata();

			return;
		}

		$posts = $query->posts;
		$lead  = $this->switched_on( 'lead', false ) ? array_shift( $posts ) : null;

		if ( $lead ) {
			echo '<div class="pfh-blog__lead">';
			echo PFH_Widgets_Blog::card( $lead, $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built there.
			echo '</div>';
		}

		printf( '<ul class="pfh-blog__grid" data-pfh-blog-grid>%s</ul>', PFH_Widgets_Blog::cards( $posts, $options ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built there.

		$this->render_foot( $query, $options );

		echo '</div></section>';
		wp_reset_postdata();
	}

	private function render_head() {
		$eyebrow = trim( (string) $this->setting( 'eyebrow', '' ) );
		$title   = trim( (string) $this->setting( 'title', '' ) );
		$lede    = trim( (string) $this->setting( 'lede', '' ) );
		$terms   = $this->switched_on( 'showTerms', false ) ? PFH_Widgets_Blog::terms() : [];

		if ( '' === $eyebrow && '' === $title && '' === $lede && ! $terms ) {
			return;
		}

		echo '<header class="pfh-blog__head">';

		if ( '' !== $eyebrow ) {
			echo '<p class="pfh-blog__eyebrow">' . esc_html( $eyebrow ) . '</p>';
		}

		if ( '' !== $title ) {
			echo '<h1 class="pfh-blog__title">' . wp_kses( $title, [ 'em' => [], 'i' => [], 'br' => [], 'strong' => [] ] ) . '</h1>';
		}

		if ( '' !== $lede ) {
			echo '<p class="pfh-blog__lede">' . esc_html( $lede ) . '</p>';
		}

		if ( $terms ) {
			echo '<ul class="pfh-blog__terms">';

			$all = trim( (string) $this->setting( 'allLabel', '' ) );

			if ( '' !== $all ) {
				printf(
					'<li><a class="pfh-blog__term%s" href="%s">%s</a></li>',
					is_category() ? '' : ' is-on',
					esc_url( PFH_Widgets_Blog::blog_url() ),
					esc_html( $all )
				);
			}

			foreach ( $terms as $term ) {
				printf(
					'<li><a class="pfh-blog__term%s" href="%s">%s</a></li>',
					is_category( $term->term_id ) ? ' is-on' : '',
					esc_url( (string) get_category_link( $term ) ),
					esc_html( $term->name )
				);
			}

			echo '</ul>';
		}

		echo '</header>';
	}

	/**
	 * @param WP_Query $query   The posts.
	 * @param array    $options Card options.
	 */
	private function render_foot( $query, array $options ) {
		$mode  = (string) $this->setting( 'pager', 'load' );
		$pages = (int) $query->max_num_pages;
		$page  = max( 1, (int) $options['page'] );

		if ( 'none' === $mode || $pages <= $page ) {
			return;
		}

		echo '<div class="pfh-blog__foot">';

		if ( 'numbers' === $mode ) {
			printf(
				'<nav class="pfh-blog__pager" aria-label="%s">%s</nav>',
				esc_attr__( 'Meer pagina\'s', 'pfh-widgets' ),
				wp_kses_post(
					(string) paginate_links(
						[
							'total'   => $pages,
							'current' => $page,
							'type'    => 'plain',
							'prev_text' => esc_html__( 'Vorige', 'pfh-widgets' ),
							'next_text' => esc_html__( 'Volgende', 'pfh-widgets' ),
						]
					)
				)
			);

			echo '</div>';

			return;
		}

		/*
		 * A link first, so a browser with no scripting still gets to page two;
		 * the script turns it into a button that appends instead.
		 */
		printf(
			'<a class="pfh-blog__load" href="%s" data-pfh-blog-load data-pfh-blog-page="%d" data-pfh-blog-pages="%d" data-pfh-blog-query="%s"><span>%s</span><span class="pfh-blog__load-spin" aria-hidden="true"></span></a>',
			esc_url( (string) get_pagenum_link( $page + 1 ) ),
			$page + 1,
			$pages,
			esc_attr( (string) wp_json_encode( PFH_Widgets_Blog::travelling( $options ) ) ),
			esc_html( (string) $this->setting( 'loadLabel', '' ) )
		);

		echo '</div>';
	}

	/* ---------------------------------------------------------------------
	 * What the cards need to know
	 * ------------------------------------------------------------------ */

	/**
	 * @return array<string, mixed>
	 */
	private function options() {
		return PFH_Widgets_Blog::options(
			[
				'source'       => (string) $this->setting( 'source', 'latest' ),
				'category'     => (string) $this->setting( 'category', '' ),
				'per_page'     => (int) $this->setting( 'perPage', 6 ),
				'image'        => $this->switched_on( 'showImage' ),
				'tag'          => $this->switched_on( 'showTag' ),
				'date'         => $this->switched_on( 'showDate' ),
				'read'         => $this->switched_on( 'showRead' ),
				'excerpt'      => $this->switched_on( 'showExcerpt' ),
				'words'        => (int) $this->setting( 'excerptWords', 22 ),
				'more_label'   => (string) $this->setting( 'moreLabel', '' ),
				'read_label'   => (string) $this->setting( 'readLabel', '' ),
			]
		);
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-bl-max'       => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1140 ) ),
				'--pfh-bl-cols-set'  => max( 1, (int) $this->setting( 'columns', 3 ) ),
				'--pfh-bl-gap-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'gap', 32 ) ),
				'--pfh-bl-pt-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 72 ) ),
				'--pfh-bl-pb-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 72 ) ),
				'--pfh-bl-radius'    => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 16 ) ),
				'--pfh-bl-title-set' => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 40 ) ),
				'--pfh-bl-card-set'  => PFH_Widgets_Helpers::unit( $this->setting( 'cardSize', 20 ) ),
				'--pfh-bl-ratio'     => (string) $this->setting( 'ratio', '3 / 2' ),
				'--pfh-bl-ink'       => PFH_Widgets_Helpers::color( $this->setting( 'ink' ), '#22301c' ),
				'--pfh-bl-body'      => PFH_Widgets_Helpers::color( $this->setting( 'bodyInk' ), '#5c6657' ),
				'--pfh-bl-muted'     => PFH_Widgets_Helpers::color( $this->setting( 'mutedInk' ), '#8d9589' ),
				'--pfh-bl-accent'    => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#2b5f63' ),
				'--pfh-bl-line'      => PFH_Widgets_Helpers::color( $this->setting( 'lineColor' ), '#eaeaea' ),
				'--pfh-bl-tag-bg'    => PFH_Widgets_Helpers::color( $this->setting( 'tagBg' ), '#dfe9dc' ),
				'--pfh-bl-tag-ink'   => PFH_Widgets_Helpers::color( $this->setting( 'tagInk' ), '#46603f' ),
			]
		);
	}
}
