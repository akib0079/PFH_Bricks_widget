<?php
/**
 * Bricks element: Products For Home rating badge.
 *
 * The inline shop-rating line — "Excellent 9,7 | 270 reviews on
 * WebwinkelKeur" — reading its score and count from the live WebwinkelKeur
 * summary, with typed values as the fallback.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Rating extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-rating';
	public $icon         = 'ti-star';
	public $css_selector = '.pfh-rating';

	const LOGO_URL = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Frame-470024.svg';

	public function get_label() {
		return esc_html__( 'PFH Rating Badge', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'rating', 'reviews', 'webwinkelkeur', 'score', 'badge', 'trust', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::rating();
	}

	public function set_control_groups() {
		$this->control_groups['content'] = [ 'title' => esc_html__( 'Content', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']    = [ 'title' => esc_html__( 'Typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['logo']    = [ 'title' => esc_html__( 'Provider logo', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->controls['sourceInfo'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'type'    => 'info',
			'content' => esc_html__( 'The score and the count come from the live WebwinkelKeur summary. Set PFH_WEBWINKELKEUR_ID and PFH_WEBWINKELKEUR_CODE in wp-config.php; the values below are what shows if the feed is unreachable.', 'pfh-widgets' ),
		];

		$this->controls['live'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Use the live rating', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['label'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Label', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Uitstekend',
		];

		$this->controls['score'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Score', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '9.7',
			'description' => esc_html__( 'Used when the live rating is off or unavailable.', 'pfh-widgets' ),
		];

		$this->controls['count'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Review count', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'inline'  => true,
			'default' => 270,
		];

		$this->controls['countText'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Count text', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => '%s reviews op',
			'description' => esc_html__( '%s is replaced with the number.', 'pfh-widgets' ),
		];

		$this->controls['separator'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Separator', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => '|',
		];

		$this->controls['link'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Link', 'pfh-widgets' ),
			'type'    => 'link',
			'default' => [ 'type' => 'external', 'url' => PFH_Widgets_Reviews::SHOP_URL ],
		];

		$this->controls['align'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Alignment', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'flex-start' => esc_html__( 'Left', 'pfh-widgets' ),
				'center'     => esc_html__( 'Centre', 'pfh-widgets' ),
				'flex-end'   => esc_html__( 'Right', 'pfh-widgets' ),
			],
			'default' => 'flex-start',
		];

		/* ---------------- typography ---------------- */

		$this->controls['size'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 9,
			'max'     => 32,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['weight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'300' => esc_html__( 'Light (300)', 'pfh-widgets' ),
				'400' => esc_html__( 'Regular (400)', 'pfh-widgets' ),
				'500' => esc_html__( 'Medium (500)', 'pfh-widgets' ),
				'600' => esc_html__( 'Semibold (600)', 'pfh-widgets' ),
				'700' => esc_html__( 'Bold (700)', 'pfh-widgets' ),
			],
			'default' => '400',
		];

		$this->controls['strongWeight'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Label and score weight', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'500' => esc_html__( 'Medium (500)', 'pfh-widgets' ),
				'600' => esc_html__( 'Semibold (600)', 'pfh-widgets' ),
				'700' => esc_html__( 'Bold (700)', 'pfh-widgets' ),
			],
			'default'     => '600',
			'description' => esc_html__( '“Excellent” and the score are set apart from the rest of the line.', 'pfh-widgets' ),
		];

		$this->controls['color'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#22301c' ],
		];

		$this->controls['mutedColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Count colour', 'pfh-widgets' ),
			'type'    => 'color',
		];

		$this->controls['gap'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Gap (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 40,
			'inline'  => true,
			'default' => 8,
		];

		/* ---------------- logo ---------------- */

		$this->controls['logo'] = [
			'tab'     => 'content',
			'group'   => 'logo',
			'label'   => esc_html__( 'Logo', 'pfh-widgets' ),
			'type'    => 'image',
			'default' => [ 'url' => self::LOGO_URL ],
		];

		$this->controls['logoHeight'] = [
			'tab'     => 'content',
			'group'   => 'logo',
			'label'   => esc_html__( 'Logo height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 60,
			'inline'  => true,
			'default' => 16,
		];
	}

	private function get( $key, $default = null ) {
		return $this->setting( $key, $default );
	}

	private function is_on( $key, $default = true ) {
		return $this->switched_on( $key, $default );
	}

	/**
	 * Score and count, live where possible.
	 *
	 * @return array{score: string, count: int}
	 */
	private function figures() {
		$score = (string) $this->get( 'score', '' );
		$count = (int) $this->get( 'count', 0 );

		// The builder stays on the typed values so editing never waits on a
		// remote call, and the panel shows what a misconfigured feed would.
		if ( ! $this->is_on( 'live' ) || PFH_Widgets_Helpers::is_builder_context() ) {
			return [ 'score' => $score, 'count' => $count ];
		}

		$summary = PFH_Widgets_Reviews::summary();

		if ( null !== $summary['rating'] ) {
			$score = number_format_i18n( (float) $summary['rating'], 1 );
		}

		if ( null !== $summary['count'] ) {
			$count = (int) $summary['count'];
		}

		return [ 'score' => $score, 'count' => $count ];
	}

	public function render() {
		$figures = $this->figures();
		$link    = PFH_Widgets_Helpers::link( $this->get( 'link' ) );
		$logo    = PFH_Widgets_Helpers::image_url( $this->get( 'logo' ), 'medium' );
		$label   = trim( (string) $this->get( 'label', '' ) );
		$sep     = trim( (string) $this->get( 'separator', '|' ) );

		$this->set_attribute( '_root', 'class', [ 'pfh-rating', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		$is_link = '' !== $link['href'];
		$tag     = $is_link ? 'a' : 'div';
		$attrs   = $is_link ? PFH_Widgets_Helpers::link_attrs( $link ) : '';

		echo '<' . $tag . ' ' . $this->render_attributes( '_root' ) . $attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().

		if ( '' !== $label ) {
			echo '<strong class="pfh-rating__label">' . esc_html( PFH_Widgets_Helpers::dd( $label ) ) . '</strong>';
		}

		if ( '' !== $figures['score'] ) {
			echo '<strong class="pfh-rating__score">' . esc_html( $figures['score'] ) . '</strong>';
		}

		if ( '' !== $sep ) {
			echo '<span class="pfh-rating__sep" aria-hidden="true">' . esc_html( $sep ) . '</span>';
		}

		$text = (string) $this->get( 'countText', '%s reviews op' );

		if ( '' !== trim( $text ) ) {
			printf(
				'<span class="pfh-rating__count">%s</span>',
				esc_html( str_replace( '%s', number_format_i18n( $figures['count'] ), $text ) )
			);
		}

		if ( $logo ) {
			printf(
				'<img class="pfh-rating__logo" src="%s" alt="%s" loading="lazy" decoding="async" />',
				esc_url( $logo ),
				esc_attr__( 'WebwinkelKeur', 'pfh-widgets' )
			);
		}

		echo '</' . $tag . '>';
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-rt-size'   => PFH_Widgets_Helpers::unit( $this->get( 'size', 14 ) ),
				'--pfh-rt-weight' => $this->get( 'weight', '400' ),
				'--pfh-rt-strong' => $this->get( 'strongWeight', '600' ),
				'--pfh-rt-color'  => PFH_Widgets_Helpers::color( $this->get( 'color' ), '#22301c' ),
				'--pfh-rt-muted'  => PFH_Widgets_Helpers::color( $this->get( 'mutedColor' ) ),
				'--pfh-rt-gap-set' => PFH_Widgets_Helpers::unit( $this->get( 'gap', 8 ) ),
				'--pfh-rt-logo'   => PFH_Widgets_Helpers::unit( $this->get( 'logoHeight', 16 ) ),
				'--pfh-rt-align'  => $this->get( 'align', 'flex-start' ),
			]
		);
	}
}
