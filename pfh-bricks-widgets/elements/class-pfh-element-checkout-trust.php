<?php
/**
 * Bricks element: the reasons to trust the shop, beside the checkout.
 *
 * FunnelKit draws the checkout itself — the form, the order summary, the
 * bumps — and those stay FunnelKit's. What sits next to them is the shop's
 * own: a short list that answers "can I trust this?" at the one moment a
 * shopper is deciding. Icon, a bold line, a quiet line; nothing else.
 *
 * The review count can follow WebwinkelKeur: a line with %count% in it takes
 * the live number, rounded down to a clean "390+", so it never goes stale.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Checkout_Trust extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-checkout-trust';
	public $icon         = 'ti-shield';
	public $css_selector = '.pfh-cktrust';

	/** The icons a row can use: the shop's own line set. */
	const ICONS = [ 'review', 'usp-delivery', 'clock', 'medal', 'usp-secure', 'usp-natural', 'box-check', 'check', 'star', 'phone', 'mail' ];

	public function get_label() {
		return esc_html__( 'PFH Checkout Trust', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'checkout', 'trust', 'usp', 'reasons', 'funnelkit', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::checkout();
	}

	public function set_control_groups() {
		$this->control_groups['content'] = [ 'title' => esc_html__( 'Content', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['style']   = [ 'title' => esc_html__( 'Style', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->controls['title'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Waarom meer dan 4000+ klanten voor ons kiezen',
		];

		$icons = [];

		foreach ( self::ICONS as $icon ) {
			$icons[ $icon ] = ucwords( str_replace( [ 'usp-', '-' ], [ '', ' ' ], $icon ) );
		}

		$this->controls['rows'] = [
			'tab'           => 'content',
			'group'         => 'content',
			'label'         => esc_html__( 'Reasons', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'fields'        => [
				'icon'  => [ 'label' => esc_html__( 'Icon', 'pfh-widgets' ), 'type' => 'select', 'options' => $icons ],
				'image' => [ 'label' => esc_html__( 'Own icon (instead)', 'pfh-widgets' ), 'type' => 'image' ],
				'title' => [ 'label' => esc_html__( 'Title', 'pfh-widgets' ), 'type' => 'text' ],
				'text'  => [ 'label' => esc_html__( 'Text', 'pfh-widgets' ), 'type' => 'text' ],
			],
			'default'       => [
				[ 'icon' => 'review', 'title' => 'Beste reviews', 'text' => '%count%+ 5 sterren reviews op WebwinkelKeur' ],
				[ 'icon' => 'usp-delivery', 'title' => 'Gratis verzending', 'text' => 'Gratis verzending vanaf €60 NL & €70 BE' ],
				[ 'icon' => 'clock', 'title' => 'Snelle verzending', 'text' => 'Voor 15:00 besteld is dezelfde dag verzonden' ],
				[ 'icon' => 'medal', 'title' => 'Spaar punten', 'text' => 'Standaard punten sparen, elke euro is 1 punt!' ],
			],
			'description'   => esc_html__( '%count% in a line is the live number of WebwinkelKeur reviews, rounded down to a clean figure. %score% is the rating.', 'pfh-widgets' ),
		];

		$this->controls['ink']      = $this->colour( esc_html__( 'Headings', 'pfh-widgets' ) );
		$this->controls['textInk']  = $this->colour( esc_html__( 'Text', 'pfh-widgets' ) );
		$this->controls['iconInk']  = $this->colour( esc_html__( 'Icon', 'pfh-widgets' ) );
		$this->controls['iconBg']   = $this->colour( esc_html__( 'Icon background', 'pfh-widgets' ) );

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

	private function colour( $label ) {
		return [ 'tab' => 'content', 'group' => 'style', 'label' => $label, 'type' => 'color', 'inline' => true ];
	}

	public function render() {
		$rows  = $this->rows();
		$title = trim( (string) $this->setting( 'title', '' ) );

		if ( ! $rows && '' === $title ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-cktrust pfh-cktrust--empty"><p>' . esc_html__( 'Add a reason or two.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-cktrust', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.

		if ( '' !== $title ) {
			echo '<h3 class="pfh-cktrust__title">' . esc_html( $title ) . '</h3>';
		}

		if ( $rows ) {
			echo '<ul class="pfh-cktrust__list">';

			foreach ( $rows as $row ) {
				echo '<li class="pfh-cktrust__row">';

				if ( '' !== $row['image'] ) {
					printf( '<span class="pfh-cktrust__icon"><img src="%s" alt="" loading="lazy" decoding="async" /></span>', esc_url( $row['image'] ) );
				} else {
					echo '<span class="pfh-cktrust__icon" aria-hidden="true">' . PFH_Widgets_Icons::get( $row['icon'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				}

				echo '<span class="pfh-cktrust__copy">';

				if ( '' !== $row['title'] ) {
					echo '<strong class="pfh-cktrust__row-title">' . esc_html( $row['title'] ) . '</strong>';
				}

				if ( '' !== $row['text'] ) {
					echo '<span class="pfh-cktrust__text">' . esc_html( $row['text'] ) . '</span>';
				}

				echo '</span></li>';
			}

			echo '</ul>';
		}

		echo '</section>';
	}

	/**
	 * @return array<int, array{icon:string, image:string, title:string, text:string}>
	 */
	private function rows() {
		$rows    = $this->setting( 'rows', [] );
		$figures = null;
		$out     = [];

		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$title = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';
			$text  = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';

			if ( '' === $title && '' === $text ) {
				continue;
			}

			/*
			 * Only a line that asks for a figure gets one. The review service
			 * would otherwise renumber whatever digits a line already has —
			 * "vanaf €60" is a price, not a review count.
			 */
			if ( false !== strpos( $title . $text, '%' ) && class_exists( 'PFH_Widgets_Reviews' ) ) {
				if ( null === $figures ) {
					$figures = PFH_Widgets_Reviews::figures( [ 'scale' => 10, 'round' => 10, 'score' => '9,7', 'count' => 380 ] );
				}

				$title = PFH_Widgets_Reviews::tokens( $title, $figures );
				$text  = PFH_Widgets_Reviews::tokens( $text, $figures );
			}

			$icon = isset( $row['icon'] ) && in_array( $row['icon'], self::ICONS, true ) ? $row['icon'] : 'check';

			$out[] = [
				'icon'  => $icon,
				'image' => isset( $row['image'] ) ? PFH_Widgets_Helpers::image_url( $row['image'], 'thumbnail' ) : '',
				'title' => $title,
				'text'  => $text,
			];
		}

		return $out;
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-ck-ink'     => PFH_Widgets_Helpers::color( $this->setting( 'ink' ) ),
				'--pfh-ck-body'    => PFH_Widgets_Helpers::color( $this->setting( 'textInk' ) ),
				'--pfh-ck-icon'    => PFH_Widgets_Helpers::color( $this->setting( 'iconInk' ) ),
				'--pfh-ck-icon-bg' => PFH_Widgets_Helpers::color( $this->setting( 'iconBg' ) ),
				'--pfh-ck-title'   => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 18 ) ),
			]
		);
	}
}
