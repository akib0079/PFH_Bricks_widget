<?php
/**
 * An article's content, made ready to show.
 *
 * Three jobs: run the content through WordPress's own filters, give its
 * headings ids so the contents panel can point at them, and put a wide table
 * in a box that scrolls instead of widening the page.
 *
 * The headings are read from the rendered HTML rather than the raw post,
 * because a shortcode or a block can put a heading there that the raw content
 * does not contain — and a contents list that disagrees with the article is
 * worse than none.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Post_Body {

	/**
	 * The article, and its headings.
	 *
	 * @param WP_Post $post    Article.
	 * @param bool    $toc     Whether a contents list is wanted.
	 * @param array   $levels  Which headings count, e.g. [ 'h2', 'h3' ].
	 * @return array{html:string, toc:array<int, array{level:int, id:string, text:string}>}
	 */
	public static function prepare( $post, $toc = true, array $levels = [ 'h2', 'h3' ] ) {
		$html = self::content( $post );
		$list = [];

		if ( $toc ) {
			$html = self::mark_headings( $html, $levels, $list );

			// One heading is a section, not a contents list.
			if ( count( $list ) < 2 ) {
				$list = [];
			}
		}

		$html = self::wrap_tables( $html );

		return [ 'html' => $html, 'toc' => $list ];
	}

	/**
	 * The post's content, filtered the way WordPress filters it.
	 *
	 * @param WP_Post $post Article.
	 * @return string
	 */
	public static function content( $post ) {
		/*
		 * setup_postdata first: shortcodes and blocks read the global post,
		 * and a gallery in an article rendered outside the loop otherwise
		 * comes out belonging to whatever was there before.
		 */
		global $wp_query;

		$previous = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;

		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		$html = (string) apply_filters( 'the_content', $post->post_content );

		wp_reset_postdata();

		if ( $previous ) {
			$GLOBALS['post'] = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		return str_replace( ']]>', ']]&gt;', $html );
	}

	/**
	 * Give every heading an id, and collect them.
	 *
	 * @param string $html   Rendered content.
	 * @param array  $levels Which headings count.
	 * @param array  $list   Collected headings, by reference.
	 * @return string
	 */
	private static function mark_headings( $html, array $levels, array &$list ) {
		$tags = implode( '|', array_map( 'preg_quote', $levels ) );
		$seen = [];

		return (string) preg_replace_callback(
			'/<(' . $tags . ')([^>]*)>(.*?)<\/\1>/is',
			static function ( $m ) use ( &$list, &$seen ) {
				$tag   = strtolower( $m[1] );
				$attrs = $m[2];
				$text  = trim( wp_strip_all_tags( $m[3] ) );

				if ( '' === $text ) {
					return $m[0];
				}

				// An id the author already gave it wins: something may link to it.
				if ( preg_match( '/\sid=["\']([^"\']+)["\']/i', $attrs, $has ) ) {
					$id = $has[1];
				} else {
					$id = sanitize_title( $text );
					$id = '' !== $id ? $id : 'kop';

					// Two headings can read the same; their ids cannot.
					if ( isset( $seen[ $id ] ) ) {
						$seen[ $id ]++;
						$id .= '-' . $seen[ $id ];
					} else {
						$seen[ $id ] = 1;
					}

					$attrs .= ' id="' . esc_attr( $id ) . '"';
				}

				$list[] = [
					'level' => (int) substr( $tag, 1 ),
					'id'    => $id,
					'text'  => $text,
				];

				return '<' . $tag . $attrs . '>' . $m[3] . '</' . $tag . '>';
			},
			$html
		);
	}

	/**
	 * Put each table in a box of its own that scrolls.
	 *
	 * A five-column table in a 720px column otherwise makes the whole page
	 * scroll sideways, which is the one thing a phone must never do.
	 *
	 * @param string $html Rendered content.
	 * @return string
	 */
	private static function wrap_tables( $html ) {
		if ( false === stripos( $html, '<table' ) ) {
			return $html;
		}

		return (string) preg_replace(
			'/(<table[\s>].*?<\/table>)/is',
			'<div class="pfh-post__scroller">$1</div>',
			$html
		);
	}

	/* ---------------------------------------------------------------------
	 * Around the article
	 * ------------------------------------------------------------------ */

	/**
	 * The article's category, skipping the default one where possible.
	 *
	 * @param WP_Post $post Article.
	 * @return WP_Term|null
	 */
	public static function first_term( $post ) {
		$terms = get_the_terms( $post, 'category' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return null;
		}

		$default = (int) get_option( 'default_category' );

		foreach ( $terms as $term ) {
			if ( (int) $term->term_id !== $default ) {
				return $term;
			}
		}

		return $terms[0];
	}

	/**
	 * The article before or after this one.
	 *
	 * get_adjacent_post() reads the global post, which is not necessarily the
	 * one being drawn — in the builder it is whatever the page is. So the
	 * query is made here instead, by date, which is what "previous" means.
	 *
	 * @param WP_Post $post     Article.
	 * @param bool    $previous Older rather than newer.
	 * @return WP_Post|null
	 */
	public static function adjacent( $post, $previous = true ) {
		$found = get_posts(
			[
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'exclude'        => [ (int) $post->ID ],
				'orderby'        => 'date',
				'order'          => $previous ? 'DESC' : 'ASC',
				'date_query'     => [
					[
						( $previous ? 'before' : 'after' ) => $post->post_date,
						'inclusive'                        => false,
						'column'                           => 'post_date',
					],
				],
			]
		);

		return $found ? $found[0] : null;
	}

	/**
	 * Articles worth reading next: the same category first, then the newest.
	 *
	 * @param WP_Post $post  Article.
	 * @param int     $count How many.
	 * @return WP_Post[]
	 */
	public static function related( $post, $count = 3 ) {
		$count = max( 1, min( 6, (int) $count ) );
		$term  = self::first_term( $post );

		$args = [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'exclude'        => [ (int) $post->ID ],
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		$found = $term ? get_posts( array_merge( $args, [ 'cat' => (int) $term->term_id ] ) ) : [];

		// Not enough in that category: fill up with the most recent.
		if ( count( $found ) < $count ) {
			$have = wp_list_pluck( $found, 'ID' );

			$more = get_posts(
				array_merge(
					$args,
					[
						'posts_per_page' => $count - count( $found ),
						'exclude'        => array_merge( [ (int) $post->ID ], $have ),
					]
				)
			);

			$found = array_merge( $found, $more );
		}

		/**
		 * Filter the articles shown under an article.
		 *
		 * @param WP_Post[] $found Articles.
		 * @param WP_Post   $post  The one being read.
		 */
		return (array) apply_filters( 'pfh_post_related', $found, $post );
	}
}
