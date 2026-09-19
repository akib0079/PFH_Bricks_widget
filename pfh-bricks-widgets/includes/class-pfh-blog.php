<?php
/**
 * The blog's query and its cards.
 *
 * Kept out of the element because the load-more endpoint draws the same cards,
 * and a card that looked different on page two than on page one would be worse
 * than no load-more at all.
 *
 * Nothing here trusts the request. The endpoint takes only the handful of
 * display values the element travels with, clamps every one of them, and asks
 * for published posts — which is all the first page showed anyway.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Blog {

	/**
	 * AJAX action.
	 */
	const ACTION = 'pfh_posts';

	/**
	 * Words a minute, for the reading time. The usual figure for prose.
	 */
	const WPM = 200;

	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION, [ __CLASS__, 'ajax' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ __CLASS__, 'ajax' ] );
	}

	/* ---------------------------------------------------------------------
	 * Options
	 * ------------------------------------------------------------------ */

	/**
	 * Fill in and clamp everything the cards and the query need.
	 *
	 * @param array $raw Loose values, from the element or from a request.
	 * @return array<string, mixed>
	 */
	public static function options( array $raw ) {
		$out = wp_parse_args(
			$raw,
			[
				'source'     => 'latest',
				'category'   => '',
				'per_page'   => 6,
				'page'       => 0,
				'image'      => true,
				'tag'        => true,
				'date'       => true,
				'read'       => true,
				'excerpt'    => true,
				'words'      => 22,
				'more_label' => '',
				'read_label' => '',
			]
		);

		$out['source']   = 'current' === $out['source'] ? 'current' : 'latest';
		$out['category'] = sanitize_text_field( (string) $out['category'] );
		$out['per_page'] = max( 1, min( 24, (int) $out['per_page'] ) );
		$out['words']    = max( 6, min( 80, (int) $out['words'] ) );

		foreach ( [ 'image', 'tag', 'date', 'read', 'excerpt' ] as $flag ) {
			$out[ $flag ] = ! empty( $out[ $flag ] );
		}

		$out['more_label'] = sanitize_text_field( (string) $out['more_label'] );
		$out['read_label'] = sanitize_text_field( (string) $out['read_label'] );

		// Which page is being looked at, unless one was handed in.
		if ( ! $out['page'] ) {
			$out['page'] = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
		}

		$out['page'] = max( 1, (int) $out['page'] );

		return $out;
	}

	/**
	 * The subset that travels to the load-more endpoint.
	 *
	 * Only what changes how a card is drawn. Nothing about the page, nothing
	 * about the element, nothing that could name a post the visitor could not
	 * already see.
	 *
	 * @param array $options Resolved options.
	 * @return array<string, mixed>
	 */
	public static function travelling( array $options ) {
		return [
			'source'     => $options['source'],
			'category'   => $options['category'],
			'per_page'   => $options['per_page'],
			'image'      => $options['image'] ? 1 : 0,
			'tag'        => $options['tag'] ? 1 : 0,
			'date'       => $options['date'] ? 1 : 0,
			'read'       => $options['read'] ? 1 : 0,
			'excerpt'    => $options['excerpt'] ? 1 : 0,
			'words'      => $options['words'],
			'more_label' => $options['more_label'],
			'read_label' => $options['read_label'],
		];
	}

	/* ---------------------------------------------------------------------
	 * The posts
	 * ------------------------------------------------------------------ */

	/**
	 * @param array $options Resolved options.
	 * @return WP_Query
	 */
	public static function query( array $options ) {
		/*
		 * On an archive the page has already been queried, and running a
		 * second query would ignore the category, the search and the paging
		 * the visitor actually asked for.
		 */
		if ( 'current' === $options['source'] && ! is_admin() && ( is_home() || is_archive() || is_search() ) ) {
			global $wp_query;

			if ( $wp_query instanceof WP_Query ) {
				return $wp_query;
			}
		}

		$args = [
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $options['per_page'],
			'paged'               => $options['page'],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		];

		if ( '' !== $options['category'] ) {
			$args['cat'] = (int) $options['category'];

			if ( ! $args['cat'] ) {
				unset( $args['cat'] );
				$args['category_name'] = $options['category'];
			}
		}

		/**
		 * Filter the blog query.
		 *
		 * @param array $args    Query arguments.
		 * @param array $options Resolved options.
		 */
		return new WP_Query( (array) apply_filters( 'pfh_blog_query_args', $args, $options ) );
	}

	/**
	 * The categories that actually have posts in them.
	 *
	 * @return WP_Term[]
	 */
	public static function terms() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		// An element file gets loaded where WordPress is not — the audit and
		// the packaging checks among them.
		if ( ! function_exists( 'get_categories' ) ) {
			return [];
		}

		$terms = get_categories( [ 'hide_empty' => true, 'number' => 12, 'orderby' => 'count', 'order' => 'DESC' ] );
		$cache = ( is_array( $terms ) && ! is_wp_error( $terms ) ) ? $terms : [];

		return $cache;
	}

	/**
	 * Categories as a Bricks select list.
	 *
	 * @return array<string, string>
	 */
	public static function category_options() {
		/*
		 * Built on the front end as well as in the builder: Bricks validates a
		 * select's saved value against this map, and an empty list on the
		 * front end would quietly drop the chosen category — the element would
		 * then show every post while the builder looked correct.
		 */
		$out = [ '' => function_exists( 'esc_html__' ) ? esc_html__( 'All of them', 'pfh-widgets' ) : 'All of them' ];

		foreach ( self::terms() as $term ) {
			$out[ (string) $term->term_id ] = $term->name . ' (' . (int) $term->count . ')';
		}

		return $out;
	}

	/**
	 * Where "all posts" lives.
	 *
	 * @return string
	 */
	public static function blog_url() {
		if ( ! function_exists( 'get_option' ) ) {
			return '';
		}

		$page = (int) get_option( 'page_for_posts' );

		return $page ? (string) get_permalink( $page ) : home_url( '/' );
	}

	/* ---------------------------------------------------------------------
	 * The cards
	 * ------------------------------------------------------------------ */

	/**
	 * @param array|WP_Post[] $posts   Posts.
	 * @param array           $options Resolved options.
	 * @return string
	 */
	public static function cards( $posts, array $options ) {
		$out = '';

		foreach ( (array) $posts as $post ) {
			$out .= self::card( $post, $options );
		}

		return $out;
	}

	/**
	 * One post, as a card.
	 *
	 * @param WP_Post $post    Post.
	 * @param array   $options Resolved options.
	 * @return string
	 */
	public static function card( $post, array $options ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$link  = (string) get_permalink( $post );
		$title = get_the_title( $post );

		ob_start();

		echo '<li class="pfh-blog__card">';

		if ( $options['image'] ) {
			printf( '<a class="pfh-blog__media" href="%s" tabindex="-1" aria-hidden="true">', esc_url( $link ) );

			$image = (int) get_post_thumbnail_id( $post );

			if ( $image ) {
				echo wp_get_attachment_image( $image, 'large', false, [ 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ] );
			}

			if ( $options['tag'] ) {
				$term = self::first_term( $post );

				if ( $term ) {
					printf( '<span class="pfh-blog__tag">%s</span>', esc_html( $term->name ) );
				}
			}

			echo '</a>';
		}

		// Everything that is not the picture, in one box — which is what lets
		// the lead article sit beside its picture instead of spreading down
		// the column next to it.
		echo '<div class="pfh-blog__card-body">';

		$meta = [];

		if ( $options['date'] ) {
			$meta[] = esc_html( (string) get_the_date( '', $post ) );
		}

		if ( $options['read'] ) {
			$minutes = self::reading_time( $post );
			$wording = '' !== $options['read_label'] ? $options['read_label'] : '%s min';

			$meta[] = esc_html( sprintf( $wording, number_format_i18n( $minutes ) ) );
		}

		if ( $meta ) {
			echo '<p class="pfh-blog__meta">';

			foreach ( $meta as $line ) {
				echo '<span>' . $line . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			}

			echo '</p>';
		}

		printf(
			'<h2 class="pfh-blog__card-title"><a href="%s">%s</a></h2>',
			esc_url( $link ),
			esc_html( $title )
		);

		if ( $options['excerpt'] ) {
			$excerpt = self::excerpt( $post, $options['words'] );

			if ( '' !== $excerpt ) {
				echo '<p class="pfh-blog__excerpt">' . esc_html( $excerpt ) . '</p>';
			}
		}

		if ( '' !== $options['more_label'] ) {
			printf(
				'<a class="pfh-blog__more" href="%s"><span>%s</span>%s<span class="pfh-sr-only"> — %s</span></a>',
				esc_url( $link ),
				esc_html( $options['more_label'] ),
				PFH_Widgets_Icons::get( 'arrow-ne' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				esc_html( $title )
			);
		}

		echo '</div></li>';

		return (string) ob_get_clean();
	}

	/**
	 * @param WP_Post $post Post.
	 * @return WP_Term|null
	 */
	private static function first_term( $post ) {
		$terms = get_the_terms( $post, 'category' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return null;
		}

		// The default "Uncategorised" says nothing; anything else is better.
		$default = (int) get_option( 'default_category' );

		foreach ( $terms as $term ) {
			if ( (int) $term->term_id !== $default ) {
				return $term;
			}
		}

		return $terms[0];
	}

	/**
	 * @param WP_Post $post  Post.
	 * @param int     $words How many words to keep.
	 * @return string
	 */
	private static function excerpt( $post, $words ) {
		$text = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
		$text = strip_shortcodes( (string) $text );
		$text = wp_strip_all_tags( $text );

		return trim( wp_trim_words( $text, $words, '…' ) );
	}

	/**
	 * Roughly how long the post takes to read, never less than a minute.
	 *
	 * @param WP_Post $post Post.
	 * @return int
	 */
	public static function reading_time( $post ) {
		$words = str_word_count( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ) );

		/**
		 * Filter a post's reading time in minutes.
		 *
		 * @param int     $minutes Minutes.
		 * @param WP_Post $post    Post.
		 */
		return (int) apply_filters( 'pfh_blog_reading_time', max( 1, (int) ceil( $words / self::WPM ) ), $post );
	}

	/* ---------------------------------------------------------------------
	 * Load more
	 * ------------------------------------------------------------------ */

	/**
	 * The next page of cards.
	 */
	public static function ajax() {
		check_ajax_referer( PFH_Widgets_Ajax::NONCE, 'nonce' );

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput -- every value goes through options(), which clamps it.
		$raw  = isset( $_POST['query'] ) ? json_decode( wp_unslash( $_POST['query'] ), true ) : [];
		$page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 0;
		// phpcs:enable

		if ( ! is_array( $raw ) || $page < 2 ) {
			wp_send_json_error( [ 'message' => __( 'There was nothing more to fetch.', 'pfh-widgets' ) ], 400 );
		}

		/*
		 * "Whatever this page is about" cannot be answered here — there is no
		 * page to be about — so the endpoint always runs its own query. That
		 * is also why nothing from the request can widen what is shown: it is
		 * published posts either way.
		 */
		$raw['source'] = 'latest';
		$raw['page']   = $page;

		$options = self::options( $raw );
		$query   = self::query( $options );

		if ( ! $query->have_posts() ) {
			wp_send_json_success( [ 'html' => '', 'more' => false ] );
		}

		$html = self::cards( $query->posts, $options );

		wp_reset_postdata();

		wp_send_json_success(
			[
				'html' => $html,
				'more' => $page < (int) $query->max_num_pages,
			]
		);
	}
}
