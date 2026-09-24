<?php
/**
 * Bricks element: real reviews, beside the checkout.
 *
 * The checkout used to show one testimonial typed into the page. This shows
 * what customers actually wrote on WebwinkelKeur — the shop's live score and
 * review count on top, and one real review at a time underneath, turning
 * over quietly. At the moment someone is deciding whether to pay, a real name
 * and a recent date carry more weight than the best line anyone could write.
 *
 * Only reviews with a comment and a high rating are shown; the score above
 * them is the whole shop's, so nothing is hidden by the choice. When the feed
 * cannot be reached — no API code yet, WebwinkelKeur down — the reviews typed
 * into the panel are shown instead, so the column is never empty.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Checkout_Reviews extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-checkout-reviews';
	public $icon         = 'ti-comments';
	public $css_selector = '.pfh-ckrev';

	public function get_label() {
		return esc_html__( 'PFH Checkout Reviews', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'checkout', 'reviews', 'webwinkelkeur', 'testimonial', 'funnelkit', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::checkout();
	}

	public function set_control_groups() {
		$this->control_groups['content']  = [ 'title' => esc_html__( 'Content', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['feed']     = [ 'title' => esc_html__( 'Which reviews', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['fallback'] = [ 'title' => esc_html__( 'Fallback reviews', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['style']    = [ 'title' => esc_html__( 'Style', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->controls['title'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Dit vinden klanten van ons',
		];

		$this->controls['scoreLine'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Score line', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => '%score%/10 · %total% beoordelingen',
			'description' => esc_html__( '%score% is the shop\'s live WebwinkelKeur rating, %total% the number of reviews. Leave empty to hide the line.', 'pfh-widgets' ),
		];

		$this->controls['interval'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Next review after (seconds)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 30,
			'inline'      => true,
			'default'     => 7,
			'description' => esc_html__( '0 shows one review and stays there. It pauses while the pointer is over it, and never moves for visitors who have asked for less motion.', 'pfh-widgets' ),
		];

		/* ---- which reviews ---- */

		$this->controls['limit'] = [
			'tab'     => 'content',
			'group'   => 'feed',
			'label'   => esc_html__( 'How many', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 12,
			'inline'  => true,
			'default' => 6,
		];

		$this->controls['minRating'] = [
			'tab'         => 'content',
			'group'       => 'feed',
			'label'       => esc_html__( 'Lowest rating shown (out of 10)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 10,
			'inline'      => true,
			'default'     => 8,
			'description' => esc_html__( 'The score above is always the whole shop\'s. This only chooses which written reviews appear.', 'pfh-widgets' ),
		];

		$this->controls['minLength'] = [
			'tab'         => 'content',
			'group'       => 'feed',
			'label'       => esc_html__( 'Shortest review (characters)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 200,
			'inline'      => true,
			'default'     => 25,
			'description' => esc_html__( 'Skips one-word reviews ("Top!") so the column shows ones that say something. If too few are left, the shorter ones are used after all.', 'pfh-widgets' ),
		];

		$this->controls['maxLength'] = [
			'tab'         => 'content',
			'group'       => 'feed',
			'label'       => esc_html__( 'Longest review (characters)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 80,
			'max'         => 600,
			'inline'      => true,
			'default'     => 240,
			'description' => esc_html__( 'Longer reviews are cut at a word. The column beside a checkout is narrow.', 'pfh-widgets' ),
		];

		$this->controls['showDate'] = [
			'tab'     => 'content',
			'group'   => 'feed',
			'label'   => esc_html__( 'Show when it was written', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		/* ---- fallback ---- */

		$this->controls['fallback'] = [
			'tab'           => 'content',
			'group'         => 'fallback',
			'label'         => esc_html__( 'Reviews to show without the feed', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'name',
			'fields'        => [
				'text' => [ 'label' => esc_html__( 'Review', 'pfh-widgets' ), 'type' => 'textarea' ],
				'name' => [ 'label' => esc_html__( 'Name', 'pfh-widgets' ), 'type' => 'text' ],
				'role' => [ 'label' => esc_html__( 'Under the name', 'pfh-widgets' ), 'type' => 'text' ],
			],
			'default'       => [
				[
					'text' => 'Voor het eerst besteld en twijfelde wel een poosje omdat het toch best wel prijzig is. Maar wat is dit lekker!!!!! Dit had ik veel eerder moeten kopen!',
					'name' => 'Jessica',
					'role' => 'Tevreden klant',
				],
			],
			'description'   => esc_html__( 'Shown in the builder, and on the site whenever WebwinkelKeur cannot be reached. The API code goes in wp-config.php as PFH_WEBWINKELKEUR_CODE.', 'pfh-widgets' ),
		];

		/* ---- style ---- */

		foreach ( [
			'ink'      => esc_html__( 'Headings', 'pfh-widgets' ),
			'textInk'  => esc_html__( 'Review text', 'pfh-widgets' ),
			'nameInk'  => esc_html__( 'Name', 'pfh-widgets' ),
			'starInk'  => esc_html__( 'Stars', 'pfh-widgets' ),
			'cardBg'   => esc_html__( 'Card background', 'pfh-widgets' ),
		] as $key => $label ) {
			$this->controls[ $key ] = [ 'tab' => 'content', 'group' => 'style', 'label' => $label, 'type' => 'color', 'inline' => true ];
		}

		$this->controls['titleSize'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Heading size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 14,
			'max'     => 32,
			'inline'  => true,
			'default' => 18,
		];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$reviews = $this->reviews();
		$title   = trim( (string) $this->setting( 'title', '' ) );

		if ( ! $reviews && '' === $title ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-ckrev pfh-ckrev--empty"><p>' . esc_html__( 'Add a fallback review, or connect WebwinkelKeur.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$interval = max( 0, (int) $this->setting( 'interval', 7 ) );

		$this->set_attribute( '_root', 'class', [ 'pfh-ckrev', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );
		$this->set_attribute( '_root', 'data-pfh-ckrev', (string) ( count( $reviews ) > 1 ? $interval * 1000 : 0 ) );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.

		$this->render_head( $title );

		if ( $reviews ) {
			echo '<div class="pfh-ckrev__stage" aria-live="polite">';

			foreach ( $reviews as $i => $review ) {
				$this->render_card( $review, 0 === $i );
			}

			echo '</div>';

			if ( count( $reviews ) > 1 ) {
				echo '<div class="pfh-ckrev__dots">';

				foreach ( $reviews as $i => $review ) {
					printf(
						'<button type="button" class="pfh-ckrev__dot%s" data-pfh-ckrev-to="%d" aria-label="%s"%s></button>',
						0 === $i ? ' is-active' : '',
						(int) $i,
						/* translators: %d: review number. */
						esc_attr( sprintf( __( 'Review %d', 'pfh-widgets' ), $i + 1 ) ),
						0 === $i ? ' aria-current="true"' : ''
					);
				}

				echo '</div>';
			}
		}

		echo '</section>';
	}

	/**
	 * The heading and the shop's live score.
	 *
	 * @param string $title Heading.
	 */
	private function render_head( $title ) {
		$line = trim( (string) $this->setting( 'scoreLine', '' ) );

		echo '<div class="pfh-ckrev__head">';

		if ( '' !== $title ) {
			echo '<h3 class="pfh-ckrev__title">' . esc_html( $title ) . '</h3>';
		}

		if ( '' !== $line && class_exists( 'PFH_Widgets_Reviews' ) ) {
			$figures = PFH_Widgets_Reviews::figures( [ 'scale' => 10, 'score' => '9,7', 'count' => 396 ] );

			// Written the way the shop writes its prices ("€ 6,50" — "9,7"), not
			// the admin language's way, which on an English admin is "9.7".
			if ( function_exists( 'wc_get_price_decimal_separator' ) ) {
				$figures['score'] = strtr( (string) $figures['score'], [ '.' => wc_get_price_decimal_separator(), ',' => wc_get_price_decimal_separator() ] );
			}

			printf(
				'<a class="pfh-ckrev__score" href="%s" target="_blank" rel="noopener noreferrer">%s<span class="pfh-ckrev__score-text">%s</span><span class="pfh-ckrev__source">WebwinkelKeur</span></a>',
				esc_url( PFH_Widgets_Reviews::review_url() ),
				$this->stars( (float) $figures['stars'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from static SVG.
				esc_html( PFH_Widgets_Reviews::tokens( $line, $figures ) )
			);
		}

		echo '</div>';
	}

	/**
	 * @param array $review One review.
	 * @param bool  $active Whether it is the one on show.
	 */
	private function render_card( array $review, $active ) {
		printf( '<figure class="pfh-ckrev__card%s"%s>', $active ? ' is-active' : '', $active ? '' : ' aria-hidden="true"' );

		echo $this->stars( $review['stars'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from static SVG.
		echo '<blockquote class="pfh-ckrev__text"><p>' . esc_html( $review['text'] ) . '</p></blockquote>';
		echo '<figcaption class="pfh-ckrev__by">';
		echo '<span class="pfh-ckrev__name">' . esc_html( $review['name'] ) . '</span>';

		if ( '' !== $review['meta'] ) {
			echo '<span class="pfh-ckrev__meta">' . esc_html( $review['meta'] ) . '</span>';
		}

		echo '</figcaption></figure>';
	}

	/**
	 * Five stars, filled to the rating — a half star is a half star.
	 *
	 * @param float $value Stars out of five.
	 * @return string
	 */
	private function stars( $value ) {
		$value = max( 0, min( 5, (float) $value ) );
		$star  = PFH_Widgets_Icons::get( 'star' );
		$row   = str_repeat( $star, 5 );

		return sprintf(
			'<span class="pfh-ckrev__stars" role="img" aria-label="%1$s"><span class="pfh-ckrev__stars-base" aria-hidden="true">%2$s</span><span class="pfh-ckrev__stars-fill" aria-hidden="true" style="width:%3$s%%">%2$s</span></span>',
			/* translators: %s: rating out of five. */
			esc_attr( sprintf( __( '%s van 5 sterren', 'pfh-widgets' ), number_format_i18n( $value, 1 ) ) ),
			$row,
			esc_attr( (string) round( $value / 5 * 100, 2 ) )
		);
	}

	/* ---------------------------------------------------------------------
	 * The reviews
	 * ------------------------------------------------------------------ */

	/**
	 * Real ones when the feed answers, typed ones when it does not.
	 *
	 * @return array<int, array{text:string, name:string, meta:string, stars:float}>
	 */
	private function reviews() {
		$max  = max( 80, (int) $this->setting( 'maxLength', 240 ) );
		$feed = [];

		// The builder never waits on a remote call; it shows the fallback.
		if ( class_exists( 'PFH_Widgets_Reviews' ) && ! PFH_Widgets_Helpers::is_builder_context() ) {
			$feed = PFH_Widgets_Reviews::get(
				[
					'limit' => max( 1, min( 12, (int) $this->setting( 'limit', 6 ) ) ) * 3,
					'min'   => (float) $this->setting( 'minRating', 8 ),
					'text'  => true,
				]
			);
		}

		$limit = max( 1, min( 12, (int) $this->setting( 'limit', 6 ) ) );
		$least = max( 0, (int) $this->setting( 'minLength', 25 ) );
		$full  = [];
		$short = [];

		foreach ( $feed as $row ) {
			$text = $this->cut( (string) $row['text'], $max );

			if ( '' === $text ) {
				continue;
			}

			$name = trim( (string) $row['name'] );
			$meta = [];
			$from = PFH_Widgets_Reviews::place( $row );

			if ( '' !== $from ) {
				$meta[] = $from;
			}

			if ( $this->switched_on( 'showDate' ) && '' !== (string) $row['date'] ) {
				$when = $this->when( (string) $row['date'] );

				if ( '' !== $when ) {
					$meta[] = $when;
				}
			}

			$review = [
				'text'  => $text,
				'name'  => '' !== $name ? $name : __( 'Klant via WebwinkelKeur', 'pfh-widgets' ),
				'meta'  => implode( ' · ', $meta ),
				'stars' => PFH_Widgets_Reviews::stars( $row['rating10'], 10, 5 ),
			];

			if ( function_exists( 'mb_strlen' ) ? mb_strlen( $text ) >= $least : strlen( $text ) >= $least ) {
				$full[] = $review;
			} else {
				$short[] = $review;
			}

			if ( count( $full ) >= $limit ) {
				break;
			}
		}

		// Newest first throughout; the short ones only fill a gap.
		$out = array_slice( array_merge( $full, $short ), 0, $limit );

		if ( $out ) {
			return $out;
		}

		foreach ( (array) $this->setting( 'fallback', [] ) as $row ) {
			$text = isset( $row['text'] ) ? $this->cut( (string) $row['text'], $max ) : '';

			if ( '' === $text ) {
				continue;
			}

			$out[] = [
				'text'  => $text,
				'name'  => isset( $row['name'] ) ? trim( (string) $row['name'] ) : '',
				'meta'  => isset( $row['role'] ) ? trim( (string) $row['role'] ) : '',
				'stars' => 5.0,
			];
		}

		return $out;
	}

	/**
	 * A review cut to length, at a word.
	 *
	 * @param string $text Review.
	 * @param int    $max  Characters.
	 * @return string
	 */
	private function cut( $text, $max ) {
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );

		if ( '' === $text || ! function_exists( 'mb_strlen' ) || mb_strlen( $text ) <= $max ) {
			return $text;
		}

		$cut   = mb_substr( $text, 0, $max );
		$space = mb_strrpos( $cut, ' ' );

		if ( $space && $space > $max * 0.6 ) {
			$cut = mb_substr( $cut, 0, $space );
		}

		return rtrim( $cut, " ,.;:-" ) . "\u{2026}";
	}

	/**
	 * "gisteren", "3 dagen geleden", or a date once it is more than a month
	 * old — in Dutch, like the rest of the checkout, whatever the site's
	 * admin language.
	 *
	 * @param string $date As WebwinkelKeur sends it.
	 * @return string
	 */
	private function when( $date ) {
		$zone = wp_timezone();

		// WebwinkelKeur writes its dates in Dutch time, without saying so.
		try {
			$then = is_numeric( $date )
				? ( new DateTimeImmutable( '@' . (int) $date ) )->setTimezone( $zone )
				: new DateTimeImmutable( $date, $zone );
		} catch ( Exception $e ) {
			return '';
		}

		// Calendar days, not 24-hour blocks, so a review from last night is
		// "gisteren" this morning.
		$then  = $then->setTime( 0, 0 );
		$today = ( new DateTimeImmutable( 'now', $zone ) )->setTime( 0, 0 );
		$days  = (int) $then->diff( $today )->format( '%r%a' );

		if ( $days <= 0 ) {
			return 'vandaag';
		}

		if ( 1 === $days ) {
			return 'gisteren';
		}

		if ( $days < 14 ) {
			return $days . ' dagen geleden';
		}

		if ( $days < 35 ) {
			return (int) floor( $days / 7 ) . ' weken geleden';
		}

		$months = [ 'januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december' ];

		return (int) $then->format( 'j' ) . ' ' . $months[ (int) $then->format( 'n' ) - 1 ] . ' ' . $then->format( 'Y' );
	}


	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-ck-ink'     => PFH_Widgets_Helpers::color( $this->setting( 'ink' ) ),
				'--pfh-ck-body'    => PFH_Widgets_Helpers::color( $this->setting( 'textInk' ) ),
				'--pfh-ck-name'    => PFH_Widgets_Helpers::color( $this->setting( 'nameInk' ) ),
				'--pfh-ck-star'    => PFH_Widgets_Helpers::color( $this->setting( 'starInk' ) ),
				'--pfh-ck-card'    => PFH_Widgets_Helpers::color( $this->setting( 'cardBg' ) ),
				'--pfh-ck-title'   => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 18 ) ),
			]
		);
	}
}
