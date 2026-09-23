<?php
/**
 * Bricks element: Products For Home notice band.
 *
 * The quiet announcement strip — a heading, a line of copy and one action.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Notice extends \Bricks\Element {

	use PFH_Design_Revision;

	/**
	 * What these settings used to default to, before the design pass.
	 * A stored value still equal to one of these was inherited, not chosen.
	 *
	 * @return array<string, mixed>
	 */
	private function previous_defaults() {
		return [
		'bg' => [ 'hex' => '#eef4f0' ],
		'ink' => [ 'hex' => '#35402f' ],
		'radius' => 14,
		'padX' => 24,
		'padY' => 20,
		'titleSize' => 20,
		'textSize' => 12,
		'maxWidth' => 1140,
		];
	}

	public $category     = 'products-for-home';
	public $name         = 'pfh-notice';
	public $icon         = 'ti-announcement';
	public $css_selector = '.pfh-notice';

	public function get_label() {
		return esc_html__( 'PFH Notice Band', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'notice', 'announcement', 'banner', 'callout', 'strip', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::shop();
	}

	public function set_control_groups() {
		$this->control_groups['content'] = [ 'title' => esc_html__( 'Content', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['action']  = [ 'title' => esc_html__( 'Button', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['style']   = [ 'title' => esc_html__( 'Style', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->controls['fromCategory'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Follow each category\'s own settings', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'On a category page, the category can hide this band or replace any of its words (Products → Categories → edit → Collection page). What it leaves empty keeps the text below.', 'pfh-widgets' ),
		];

		$this->controls['title'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Title', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Inspiratie nodig?',
		];

		$this->controls['titleSize'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Title size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 48,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['text'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Text', 'pfh-widgets' ),
			'type'    => 'textarea',
			'default' => 'Ontdek recepten met Gia Giamas — van klassieke limonade tot zomerse cocktails.',
		];

		$this->controls['textSize'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Text size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['icon'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Leading image', 'pfh-widgets' ),
			'type'    => 'image',
		];

		/* ---- action ---- */

		$this->controls['btnLabel'] = [
			'tab'     => 'content',
			'group'   => 'action',
			'label'   => esc_html__( 'Button label', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Bekijk alle recepten',
		];

		$this->controls['btnLink'] = [
			'tab'   => 'content',
			'group' => 'action',
			'label' => esc_html__( 'Button link', 'pfh-widgets' ),
			'type'  => 'link',
		];

		$this->controls['btnArrow'] = [
			'tab'     => 'content',
			'group'   => 'action',
			'label'   => esc_html__( 'Show the arrow', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['btnBg'] = [
			'tab'   => 'content',
			'group' => 'action',
			'label' => esc_html__( 'Button background', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['btnColor'] = [
			'tab'   => 'content',
			'group' => 'action',
			'label' => esc_html__( 'Button text', 'pfh-widgets' ),
			'type'  => 'color',
		];

		/* ---- style ---- */

		$this->controls['bg'] = [
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Background', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['ink'] = [
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'  => 'color',
		];

		$this->controls['radius'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 48,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['btnRadius'] = [
			'tab'         => 'content',
			'group'       => 'action',
			'label'       => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 48,
			'inline'      => true,
			'description' => esc_html__( 'Leave empty and the button follows the band\'s own corner radius, which is what it has always done.', 'pfh-widgets' ),
		];

		$this->controls['padX'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Inner padding, sides (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 26,
		];

		$this->controls['padY'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Inner padding, top and bottom (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 30,
		];

		$this->controls['maxWidth'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Container width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 400,
			'max'     => 1600,
			'inline'  => true,
			'default' => 1240,
		];

		$this->controls['padTop'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Space above (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 0,
		];

		$this->controls['padBottom'] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => esc_html__( 'Space below (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 48,
		];
	}

	public function render() {
		$this->apply_design_revision( $this->previous_defaults() );

		/*
		 * The category's own say comes first: it can hide the band, and any
		 * field it filled in replaces the one set here. Anything it left
		 * empty is the fallback — this element, as it is set up in Bricks.
		 */
		$own = $this->is_on( 'fromCategory' ) && class_exists( 'PFH_Widgets_Collection' )
			? PFH_Widgets_Collection::section( 'inspire' )
			: null;

		if ( $own && ! empty( $own['hide'] ) ) {
			return;
		}

		$title = trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'title', '' ) ) );
		$text  = trim( PFH_Widgets_Helpers::dd( (string) $this->get( 'text', '' ) ) );
		$label = trim( (string) $this->get( 'btnLabel', '' ) );

		if ( $own ) {
			$title = '' !== $own['title'] ? $own['title'] : $title;
			$text  = '' !== $own['text'] ? $own['text'] : $text;
			$label = '' !== $own['label'] ? $own['label'] : $label;
		}

		if ( '' === $title && '' === $text && '' === $label ) {
			return;
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-notice', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-notice__inner">';
		echo '<div class="pfh-notice__band">';

		$icon = PFH_Widgets_Helpers::image_url( $this->get( 'icon' ), 'thumbnail' );

		if ( $icon ) {
			printf(
				'<img class="pfh-notice__icon" src="%s" alt="" loading="lazy" decoding="async" />',
				esc_url( $icon )
			);
		}

		echo '<div class="pfh-notice__lead">';
		echo '<div class="pfh-notice__copy">';

		if ( '' !== $title ) {
			echo '<p class="pfh-notice__title">' . esc_html( $title ) . '</p>';
		}

		if ( '' !== $text ) {
			echo '<p class="pfh-notice__text">' . esc_html( $text ) . '</p>';
		}

		echo '</div>';
		echo '</div>';

		if ( '' !== $label ) {
			$link = PFH_Widgets_Helpers::link( $this->get( 'btnLink' ) );

			if ( $own && '' !== $own['url'] ) {
				$link = [ 'href' => $own['url'], 'target' => '', 'rel' => '', 'aria' => '' ];
			}

			$tag = $link['href'] ? 'a' : 'span';

			printf(
				'<%1$s class="pfh-notice__btn"%2$s>%3$s%4$s</%1$s>',
				$tag, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal.
				'a' === $tag ? PFH_Widgets_Helpers::link_attrs( $link ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().
				esc_html( $label ),
				$this->is_on( 'btnArrow' )
					? PFH_Widgets_Icons::get( 'arrow', 'pfh-notice__arrow' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
					: ''
			);
		}

		echo '</div>';
		echo '</div>';
		echo '</section>';
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-nt-max'       => PFH_Widgets_Helpers::unit( $this->get( 'maxWidth', 1140 ) ),
				'--pfh-nt-pt'        => PFH_Widgets_Helpers::unit( $this->get( 'padTop', 0 ) ),
				'--pfh-nt-pb'        => PFH_Widgets_Helpers::unit( $this->get( 'padBottom', 48 ) ),
				'--pfh-nt-bg'        => PFH_Widgets_Helpers::color( $this->get( 'bg' ) ),
				'--pfh-nt-ink'       => PFH_Widgets_Helpers::color( $this->get( 'ink' ) ),
				'--pfh-nt-radius'    => PFH_Widgets_Helpers::unit( $this->get( 'radius', 14 ) ),
				'--pfh-nt-px'        => PFH_Widgets_Helpers::unit( $this->get( 'padX', 24 ) ),
				'--pfh-nt-py'        => PFH_Widgets_Helpers::unit( $this->get( 'padY', 20 ) ),
				'--pfh-nt-title'     => PFH_Widgets_Helpers::unit( $this->get( 'titleSize', 20 ) ),
				'--pfh-nt-text'      => PFH_Widgets_Helpers::unit( $this->get( 'textSize', 12 ) ),
				'--pfh-nt-btn-radius' => PFH_Widgets_Helpers::unit( $this->get( 'btnRadius', '' ) ),
				'--pfh-nt-btn-bg'    => PFH_Widgets_Helpers::color( $this->get( 'btnBg' ) ),
				'--pfh-nt-btn-ink'   => PFH_Widgets_Helpers::color( $this->get( 'btnColor' ) ),
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
