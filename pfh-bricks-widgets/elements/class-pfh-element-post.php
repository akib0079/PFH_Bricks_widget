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
			'source'  => esc_html__( 'Article', 'pfh-widgets' ),
			'head'    => esc_html__( 'Head', 'pfh-widgets' ),
			'toc'     => esc_html__( 'Contents', 'pfh-widgets' ),
			'below'   => esc_html__( 'Under the article', 'pfh-widgets' ),
			'related' => esc_html__( 'Read next', 'pfh-widgets' ),
			'style'   => esc_html__( 'Style', 'pfh-widgets' ),
			'layout'  => esc_html__( 'Layout', 'pfh-widgets' ),
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

		$this->controls['titleSize'] = $this->number( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 44, 24, 72 );
		$this->controls['textSize']  = $this->number( 'style', esc_html__( 'Article text size (px)', 'pfh-widgets' ), 17, 14, 22 );
		$this->controls['radius']    = $this->number( 'style', esc_html__( 'Picture radius (px)', 'pfh-widgets' ), 16, 0, 40 );

		/* ---- layout ---- */

		$this->controls['maxWidth'] = $this->number( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1140, 600, 1600 );
		$this->controls['measure']  = $this->number( 'layout', esc_html__( 'Article width (px)', 'pfh-widgets' ), 720, 480, 900 );
		$this->controls['asideWidth'] = $this->number( 'layout', esc_html__( 'Contents width (px)', 'pfh-widgets' ), 260, 180, 360 );
		$this->controls['padTop']   = $this->number( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 40, 0, 200 );
		$this->controls['padBottom'] = $this->number( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 88, 0, 200 );
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

		$this->set_attribute( '_root', 'class', [ 'pfh-post', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<article ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.

		if ( $this->switched_on( 'showProgress' ) ) {
			echo '<div class="pfh-post__progress" data-pfh-post-progress aria-hidden="true"><span></span></div>';
		}

		echo '<div class="pfh-post__inner">';

		$this->render_crumbs( $post );
		$this->render_head( $post );
		$this->render_hero( $post );

		printf( '<div class="pfh-post__layout%s">', $article['toc'] ? '' : ' pfh-post__layout--wide' );
		echo '<div class="pfh-post__main">';

		printf( '<div class="pfh-post__body" data-pfh-post-body>%s</div>', $article['html'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content, already filtered.

		$this->render_share( $post );
		$this->render_bio( $post );
		$this->render_nav( $post );

		echo '</div>';

		if ( $article['toc'] ) {
			$this->render_toc( $article['toc'] );
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

		// A <details> so a phone can fold it away; open, so a desktop sees it.
		printf(
			'<details class="pfh-post__toc" open data-pfh-post-toc><summary>%s</summary><p class="pfh-post__toc-title">%s</p><ul class="pfh-post__toc-list">',
			esc_html( $title ),
			esc_html( $title )
		);

		foreach ( $toc as $item ) {
			printf(
				'<li class="pfh-post__toc-item pfh-post__toc-item--%d"><a class="pfh-post__toc-link" href="#%s" title="%s">%s</a></li>',
				(int) $item['level'],
				esc_attr( $item['id'] ),
				esc_attr( $item['text'] ),
				esc_html( self::shorten( $item['text'] ) )
			);
		}

		echo '</ul></details>';
	}

	/**
	 * A heading, cut to something the eye can take in at a glance.
	 *
	 * The panel is a narrow column beside the article, and headings are
	 * sentences: one of them filled four lines of it. The stylesheet clamps
	 * the height as well, but a clamp is only a clamp in a browser that
	 * honours it — one that does not cuts the third line in half rather than
	 * leaving it out. So the text itself is cut, at a word, and the whole
	 * heading stays in the link's title for anyone who wants it.
	 *
	 * @param string $text  Heading.
	 * @param int    $limit How many characters fit on the two lines.
	 * @return string
	 */
	private static function shorten( $text, $limit = 58 ) {
		$text = trim( (string) $text );
		$mb   = function_exists( 'mb_substr' ) && function_exists( 'mb_strlen' );

		if ( ( $mb ? mb_strlen( $text ) : strlen( $text ) ) <= $limit ) {
			return $text;
		}

		$cut   = $mb ? mb_substr( $text, 0, $limit ) : substr( $text, 0, $limit );
		$space = $mb && function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );

		// Back to the last whole word — unless that would leave almost nothing.
		if ( $space && $space > $limit * 0.55 ) {
			$cut = $mb ? mb_substr( $cut, 0, $space ) : substr( $cut, 0, $space );
		}

		return rtrim( $cut, " \t\n\r\0\x0B,.;:-" ) . '…';
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
				'--pfh-po-max'         => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1140 ) ),
				'--pfh-po-measure-set' => PFH_Widgets_Helpers::unit( $this->setting( 'measure', 720 ) ),
				'--pfh-po-aside-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'asideWidth', 260 ) ),
				'--pfh-po-pt-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 40 ) ),
				'--pfh-po-pb-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 88 ) ),
				'--pfh-po-radius'      => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 16 ) ),
				'--pfh-po-title-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 44 ) ),
				'--pfh-po-text-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'textSize', 17 ) ),
				'--pfh-po-ink'         => PFH_Widgets_Helpers::color( $this->setting( 'ink' ), '#22301c' ),
				'--pfh-po-body'        => PFH_Widgets_Helpers::color( $this->setting( 'bodyInk' ), '#43503f' ),
				'--pfh-po-muted'       => PFH_Widgets_Helpers::color( $this->setting( 'mutedInk' ), '#8d9589' ),
				'--pfh-po-accent'      => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#2b5f63' ),
				'--pfh-po-line'        => PFH_Widgets_Helpers::color( $this->setting( 'lineColor' ), '#eaeaea' ),
				'--pfh-po-tag-bg'      => PFH_Widgets_Helpers::color( $this->setting( 'tagBg' ), '#dfe9dc' ),
				'--pfh-po-tag-ink'     => PFH_Widgets_Helpers::color( $this->setting( 'tagInk' ), '#46603f' ),
			]
		);
	}
}
