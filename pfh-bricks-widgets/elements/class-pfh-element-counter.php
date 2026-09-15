<?php
/**
 * Bricks element: Products For Home counter row.
 *
 * The four-up figure strip — 9.7/10, 9 jaar, 100%, 1957 — separated by rules.
 *
 * Any item can take its figure from the live WebwinkelKeur feed instead of a
 * typed one, which is what stops the shop rating going stale in a place
 * nobody thinks to look.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Counter extends \Bricks\Element {

	use PFH_Design_Revision;

	/**
	 * What these settings used to default to, before the design pass.
	 * A stored value still equal to one of these was inherited, not chosen.
	 *
	 * @return array<string, mixed>
	 */
	private function previous_defaults() {
		return [
		'valueSize' => 32,
		'labelSize' => 14,
		'subSize' => 11,
		'maxWidth' => 1140,
		];
	}

	public $category     = 'products-for-home';
	public $name         = 'pfh-counter';
	public $icon         = 'ti-stats-up';
	public $css_selector = '.pfh-counter';

	public function get_label() {
		return esc_html__( 'PFH Counter Row', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'counter', 'stats', 'figures', 'usp', 'numbers', 'trust', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::shop();
	}

	public function set_control_groups() {
		$this->control_groups['items']  = [ 'title' => esc_html__( 'Items', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['style']  = [ 'title' => esc_html__( 'Style', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout'] = [ 'title' => esc_html__( 'Layout', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->controls['items'] = [
			'tab'           => 'content',
			'group'         => 'items',
			'label'         => esc_html__( 'Figures', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'value',
			'default'       => [
				[
					'value' => '%score%/10',
					'label' => 'Klantbeoordeling',
					'sub'   => 'Gebaseerd op %count% reviews',
					'live'  => true,
				],
				[
					'value' => '9 jaar',
					'label' => 'Klantbeoordeling',
					'sub'   => 'Gebaseerd op 396 reviews',
				],
				[
					'value' => '100%',
					'label' => 'Natuurlijke ingrediënten',
					'sub'   => 'Gebaseerd op 396 reviews',
				],
				[
					'value' => '1957',
					'label' => 'Origineel recept',
					'sub'   => 'Gebaseerd op 396 reviews',
				],
			],
			'fields'        => [
				'value' => [
					'label' => esc_html__( 'Figure', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'label' => [
					'label' => esc_html__( 'Label', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'sub'   => [
					'label'       => esc_html__( 'Small line', 'pfh-widgets' ),
					'type'        => 'text',
					'description' => esc_html__( '%score% and %count% are replaced with the live figures.', 'pfh-widgets' ),
				],
				'live'  => [
					'label'       => esc_html__( 'Live WebwinkelKeur figure', 'pfh-widgets' ),
					'type'        => 'checkbox',
					'default'     => false,
					'description' => esc_html__( 'Replaces the numbers above with the real score and count.', 'pfh-widgets' ),
				],
			],
		];

		$this->controls['fallbackScore'] = [
			'tab'         => 'content',
			'group'       => 'items',
			'label'       => esc_html__( 'Fallback score', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '9.7',
			'description' => esc_html__( 'What %score% shows when the feed is unreachable, and in the builder — so the row never renders a gap where a number should be.', 'pfh-widgets' ),
		];

		$this->controls['fallbackCount'] = [
			'tab'     => 'content',
			'group'   => 'items',
			'label'   => esc_html__( 'Fallback review count', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 1000000,
			'inline'  => true,
			'default' => 396,
		];

		$this->controls['scale'] = [
			'tab'     => 'content',
			'group'   => 'items',
			'label'   => esc_html__( 'Live score shown out of', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'10' => esc_html__( '10 — e.g. 9.7', 'pfh-widgets' ),
				'5'  => esc_html__( '5 — e.g. 4.9', 'pfh-widgets' ),
			],
			'default' => '10',
		];

		/* ---- style ---- */

		$this->controls['valueSize'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Figure size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 16,
			'max'     => 80,
			'inline'  => true,
			'default' => 36,
		];

		$this->controls['valueColor'] = [
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Figure colour', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['labelSize'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Label size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 28,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['subSize'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Small line size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 20,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['subColor'] = [
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Small line colour', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['dividers'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Show dividers', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['dividerColor'] = [
			'tab'      => 'content',
			'group'    => 'style',
			'label'    => esc_html__( 'Divider colour', 'pfh-widgets' ),
			'type'     => 'color',
			'required' => [ 'dividers', '=', true ],
		];

		$this->controls['align'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Alignment', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'center' => esc_html__( 'Centre', 'pfh-widgets' ),
				'left'   => esc_html__( 'Left', 'pfh-widgets' ),
			],
			'default' => 'center',
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
			'default' => 24,
		];

		$this->controls['padBottom'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Space below (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 56,
		];
	}

	public function render() {
		$this->apply_design_revision( $this->previous_defaults() );

		$items = (array) $this->get( 'items', [] );

		if ( ! $items ) {
			return;
		}

		$figures = PFH_Widgets_Reviews::figures(
			[
				'live'  => true,
				'scale' => (int) $this->get( 'scale', 10 ),
				'score' => (string) $this->get( 'fallbackScore', '9.7' ),
				'count' => (int) $this->get( 'fallbackCount', 396 ),
			]
		);

		$classes = [ 'pfh-counter', 'pfh-scope', 'pfh-counter--' . (string) $this->get( 'align', 'center' ) ];

		if ( $this->is_on( 'dividers' ) ) {
			$classes[] = 'has-dividers';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars( count( $items ) ) );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-counter__inner"><ul class="pfh-counter__list">';

		foreach ( $items as $item ) {
			$live  = ! empty( $item['live'] );
			$value = isset( $item['value'] ) ? (string) $item['value'] : '';
			$label = isset( $item['label'] ) ? (string) $item['label'] : '';
			$sub   = isset( $item['sub'] ) ? (string) $item['sub'] : '';

			if ( $live ) {
				$value = PFH_Widgets_Reviews::tokens( $value, $figures );
			}

			$sub = PFH_Widgets_Reviews::tokens( $sub, $figures );

			echo '<li class="pfh-counter__item">';

			if ( '' !== trim( $value ) ) {
				echo '<p class="pfh-counter__value">' . esc_html( PFH_Widgets_Helpers::dd( $value ) ) . '</p>';
			}

			if ( '' !== trim( $label ) ) {
				echo '<p class="pfh-counter__label">' . esc_html( PFH_Widgets_Helpers::dd( $label ) ) . '</p>';
			}

			if ( '' !== trim( $sub ) ) {
				echo '<p class="pfh-counter__sub">' . esc_html( PFH_Widgets_Helpers::dd( $sub ) ) . '</p>';
			}

			echo '</li>';
		}

		echo '</ul></div>';
		echo '</section>';
	}

	private function build_vars( $count ) {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-ct-max'     => PFH_Widgets_Helpers::unit( $this->get( 'maxWidth', 1140 ) ),
				'--pfh-ct-pt'      => PFH_Widgets_Helpers::unit( $this->get( 'padTop', 24 ) ),
				'--pfh-ct-pb'      => PFH_Widgets_Helpers::unit( $this->get( 'padBottom', 56 ) ),
				'--pfh-ct-cols'    => max( 1, (int) $count ),
				'--pfh-ct-value'   => PFH_Widgets_Helpers::unit( $this->get( 'valueSize', 32 ) ),
				'--pfh-ct-v-color' => PFH_Widgets_Helpers::color( $this->get( 'valueColor' ) ),
				'--pfh-ct-label'   => PFH_Widgets_Helpers::unit( $this->get( 'labelSize', 14 ) ),
				'--pfh-ct-sub'     => PFH_Widgets_Helpers::unit( $this->get( 'subSize', 11 ) ),
				'--pfh-ct-s-color' => PFH_Widgets_Helpers::color( $this->get( 'subColor' ) ),
				'--pfh-ct-rule'    => PFH_Widgets_Helpers::color( $this->get( 'dividerColor' ) ),
			]
		);
	}

	private function get( $key, $default = null ) {
		return $this->setting( $key, $default );
	}

	private function is_on( $key, $default = true ) {
		return $this->switched_on( $key, $default );
	}
}
