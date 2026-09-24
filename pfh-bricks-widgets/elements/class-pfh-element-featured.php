<?php
/**
 * Bricks element: Products For Home featured section.
 *
 * Full-bleed promo band — a patterned background, a text column, and a
 * masked product photo that runs to the edge of the screen, with an optional
 * scrolling notice bar along the bottom.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Featured extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-featured';
	public $icon         = 'ti-layout-media-right-alt';
	public $css_selector = '.pfh-feat';

	const BASE      = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/';
	const BG_URL    = self::BASE . 'Group-1000001535-1.jpg';
	const BEE_URL   = self::BASE . 'fi_9421578.svg';
	const MEDIA_URL = self::BASE . 'Mask-group-1.png';
	const ARROW_URL = self::BASE . 'arrow-up-right-01.svg';

	public function get_label() {
		return esc_html__( 'PFH Featured Section', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'featured', 'honey', 'promo', 'banner', 'marquee', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::featured();
	}

	public function set_control_groups() {
		$this->control_groups['content'] = [ 'title' => esc_html__( 'Content', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']    = [ 'title' => esc_html__( 'Typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['button']  = [ 'title' => esc_html__( 'Button', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['media']   = [ 'title' => esc_html__( 'Image', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout']  = [ 'title' => esc_html__( 'Layout & background', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['marquee'] = [ 'title' => esc_html__( 'Notice bar', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->content_controls();
		$this->type_controls();
		$this->button_controls();
		$this->media_controls();
		$this->layout_controls();
		$this->marquee_controls();
	}

	private function weight_options() {
		return [
			'300' => esc_html__( 'Light (300)', 'pfh-widgets' ),
			'400' => esc_html__( 'Regular (400)', 'pfh-widgets' ),
			'500' => esc_html__( 'Medium (500)', 'pfh-widgets' ),
			'600' => esc_html__( 'Semibold (600)', 'pfh-widgets' ),
			'700' => esc_html__( 'Bold (700)', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Content
	 * ------------------------------------------------------------------ */

	private function content_controls() {
		$this->controls['heading'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'    => 'textarea',
			'default' => 'Griekse producten',
		];

		$this->controls['text'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Text', 'pfh-widgets' ),
			'type'        => 'textarea',
			'default'     => 'Dankzij onze familie en vrienden in Griekenland kunnen we een exclusief assortiment <a href="#">Griekse producten online</a> aanbieden. Van traditionele honing tot biologische olijfolie: hier vind je de beste Griekse producten, die je eenvoudig bij ons bestelt.',
			'description' => esc_html__( 'Accepts links and <strong> — the highlighted phrase in the design is a link.', 'pfh-widgets' ),
		];

		$this->controls['btnLabel'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Button label', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Shop nu',
		];

		$this->controls['btnLink'] = [
			'tab'      => 'content',
			'group'    => 'content',
			'label'    => esc_html__( 'Button link', 'pfh-widgets' ),
			'type'     => 'link',
			'required' => [ 'btnLabel', '!=', '' ],
		];
	}

	/* ---------------------------------------------------------------------
	 * Typography
	 * ------------------------------------------------------------------ */

	private function type_controls() {
		$this->controls['headFamily'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading font family', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Playfair Display',
		];

		$this->controls['headSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 14,
			'max'     => 90,
			'inline'  => true,
			'default' => 36,
		];

		$this->controls['headSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 60,
			'inline'  => true,
			'default' => 28,
		];

		$this->controls['headWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '500',
		];

		$this->controls['headItalic'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading in italic', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['headLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0.8,
			'max'     => 2,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.05,
		];

		$this->controls['headColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#5c2e15' ],
		];

		$this->controls['textSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 28,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['textWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '400',
		];

		$this->controls['textLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 2.4,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.5,
		];

		$this->controls['textColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#7e4a28' ],
		];

		$this->controls['linkColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Link colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#5c2e15' ],
		];

		$this->controls['linkWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Link weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '600',
		];

		$this->controls['textWidth'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text column width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 160,
			'max'     => 800,
			'inline'  => true,
			'default' => 370,
		];

		$this->controls['gapHeadText'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Space under the heading (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 18,
		];

		$this->controls['gapTextBtn'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Space above the button (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 90,
			'inline'  => true,
			'default' => 28,
		];
	}

	/* ---------------------------------------------------------------------
	 * Button
	 * ------------------------------------------------------------------ */

	private function button_controls() {
		$this->controls['btnIcon'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Icon', 'pfh-widgets' ),
			'type'    => 'image',
			'default' => [ 'url' => self::ARROW_URL ],
		];

		$this->controls['btnIconSize'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Icon size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 32,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['btnHeight'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 24,
			'max'     => 80,
			'inline'  => true,
			'default' => 40,
		];

		$this->controls['btnPadding'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Side padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 4,
			'max'     => 80,
			'inline'  => true,
			'default' => 36,
		];

		$this->controls['btnGap'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Gap: label to icon (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 40,
			'inline'  => true,
			'default' => 10,
		];

		$this->controls['btnRadius'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 70,
		];

		$this->controls['btnSize'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Font size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 9,
			'max'     => 26,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['btnWeight'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Font weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '500',
		];

		$this->controls['btnBg'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['btnOpacity'] = [
			'tab'         => 'content',
			'group'       => 'button',
			'label'       => esc_html__( 'Background opacity (%)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 100,
			'inline'      => true,
			'default'     => 80,
			'description' => esc_html__( 'Figma has the fill at 80% so the pattern shows through.', 'pfh-widgets' ),
		];

		$this->controls['btnColor'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ad6738' ],
		];

		$this->controls['btnBorder'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Border colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#f2e0bf' ],
		];

		$this->controls['btnBorderWidth'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Border width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 6,
			'inline'  => true,
			'default' => 1,
		];

		$this->controls['btnHoverBg'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Background (hover)', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];
	}

	/* ---------------------------------------------------------------------
	 * Image
	 * ------------------------------------------------------------------ */

	private function media_controls() {
		$this->controls['image'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Image', 'pfh-widgets' ),
			'type'        => 'image',
			'default'     => [ 'url' => self::MEDIA_URL ],
			'description' => esc_html__( 'The curved edge is part of the PNG, so keep a transparent file here.', 'pfh-widgets' ),
		];

		$this->controls['imageAlt'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Alt text', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Griekse pijnboomhoning',
		];

		$this->controls['imageSide'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Image side', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'right' => esc_html__( 'Right', 'pfh-widgets' ),
				'left'  => esc_html__( 'Left', 'pfh-widgets' ),
			],
			'default' => 'right',
		];

		$this->controls['imageWidth'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Image width (%)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 20,
			'max'         => 80,
			'inline'      => true,
			'default'     => 57,
			'description' => esc_html__( 'Percentage of the section, measured from its edge.', 'pfh-widgets' ),
		];

		$this->controls['imageFit'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Fit', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'cover'   => 'cover',
				'contain' => 'contain',
			],
			'default' => 'cover',
		];

		$this->controls['imagePosition'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Focal point', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'center center',
		];

		$this->controls['decorImage'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Floating decoration', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'Sits on top of the section, e.g. the olive branch.', 'pfh-widgets' ),
		];

		$this->controls['decorW'] = [
			'tab'      => 'content',
			'group'    => 'media',
			'label'    => esc_html__( 'Decoration width (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 20,
			'max'      => 700,
			'inline'   => true,
			'default'  => 190,
			'required' => [ 'decorImage', '!=', '' ],
		];

		$this->controls['decorPin'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Pin the decoration to', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'top-right'    => esc_html__( 'Top right', 'pfh-widgets' ),
				'top-left'     => esc_html__( 'Top left', 'pfh-widgets' ),
				'bottom-right' => esc_html__( 'Bottom right', 'pfh-widgets' ),
				'bottom-left'  => esc_html__( 'Bottom left', 'pfh-widgets' ),
				'free'         => esc_html__( 'Free position (percentages below)', 'pfh-widgets' ),
			],
			'default'     => 'top-right',
			'description' => esc_html__( 'Pinned, it keeps the same distance from that edge at every width. A free position is a share of the band, so it slides outward and eventually runs off the side as the screen grows.', 'pfh-widgets' ),
			'required'    => [ 'decorImage', '!=', '' ],
		];

		$this->controls['decorDX'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Offset from that side (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => -400,
			'max'         => 600,
			'inline'      => true,
			'default'     => 64,
			'description' => esc_html__( 'Negative runs the artwork off the edge.', 'pfh-widgets' ),
			'required'    => [ 'decorPin', '!=', 'free' ],
		];

		$this->controls['decorDY'] = [
			'tab'      => 'content',
			'group'    => 'media',
			'label'    => esc_html__( 'Offset from top or bottom (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => -400,
			'max'      => 600,
			'inline'   => true,
			'default'  => 44,
			'required' => [ 'decorPin', '!=', 'free' ],
		];

		$this->controls['decorX'] = [
			'tab'      => 'content',
			'group'    => 'media',
			'label'    => esc_html__( 'Decoration X (%)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => -40,
			'max'      => 140,
			'inline'   => true,
			'default'  => 82,
			'required' => [ 'decorPin', '=', 'free' ],
		];

		$this->controls['decorY'] = [
			'tab'      => 'content',
			'group'    => 'media',
			'label'    => esc_html__( 'Decoration Y (%)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => -40,
			'max'      => 140,
			'inline'   => true,
			'default'  => 6,
			'required' => [ 'decorPin', '=', 'free' ],
		];

		$this->controls['decorRotate'] = [
			'tab'      => 'content',
			'group'    => 'media',
			'label'    => esc_html__( 'Decoration rotation (deg)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => -180,
			'max'      => 180,
			'inline'   => true,
			'default'  => 0,
			'required' => [ 'decorImage', '!=', '' ],
		];

		$this->controls['decorAnchor'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Position the decoration against', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'full'      => esc_html__( 'The full section width', 'pfh-widgets' ),
				'container' => esc_html__( 'The content column', 'pfh-widgets' ),
			],
			'default'     => 'full',
			'description' => esc_html__( 'Full width keeps the design\'s proportions across the whole band. Switch to the content column to pin the decoration beside the copy instead.', 'pfh-widgets' ),
			'required'    => [ 'decorImage', '!=', '' ],
		];

		$this->controls['decorScale'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Scale the decoration with the screen', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Its width is drawn against a 1440 frame; scaling keeps that proportion on a wider monitor.', 'pfh-widgets' ),
			'required'    => [ 'decorImage', '!=', '' ],
		];

		$this->controls['decorOpacity'] = [
			'tab'      => 'content',
			'group'    => 'media',
			'label'    => esc_html__( 'Decoration opacity (%)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 5,
			'max'      => 100,
			'inline'   => true,
			'default'  => 100,
			'required' => [ 'decorImage', '!=', '' ],
		];

		$this->controls['decorHideMobile'] = [
			'tab'      => 'content',
			'group'    => 'media',
			'label'    => esc_html__( 'Hide the decoration on mobile', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'decorImage', '!=', '' ],
		];

		$this->controls['mobileOrder'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Mobile order', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'image' => esc_html__( 'Image first, then text', 'pfh-widgets' ),
				'text'  => esc_html__( 'Text first, then image', 'pfh-widgets' ),
			],
			'default'     => 'image',
			'description' => esc_html__( 'Only affects the stacked layout below 992px.', 'pfh-widgets' ),
		];

		$this->controls['imageMobile'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Show the image on mobile', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['imageMobileHeight'] = [
			'tab'      => 'content',
			'group'    => 'media',
			'label'    => esc_html__( 'Image height on mobile (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 120,
			'max'      => 600,
			'inline'   => true,
			'default'  => 280,
			'required' => [ 'imageMobile', '=', true ],
		];
	}

	/* ---------------------------------------------------------------------
	 * Layout
	 * ------------------------------------------------------------------ */

	private function layout_controls() {
		$this->controls['containerWidth'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Content width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 600,
			'max'     => 1920,
			'inline'  => true,
			'default' => 1140,
		];

		$this->controls['containerPadding'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Side padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 24,
		];

		$this->controls['minHeight'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Minimum height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 240,
			'max'     => 1000,
			'inline'  => true,
			'default' => 650,
		];

		$this->controls['minHeightMobile'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Minimum height on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 160,
			'max'     => 800,
			'inline'  => true,
			'default' => 320,
		];

		$this->controls['paddingY'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Vertical padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 64,
		];

		$this->controls['bgColor'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#f7e8cb' ],
		];

		$this->controls['bgImage'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background image', 'pfh-widgets' ),
			'type'    => 'image',
			'default' => [ 'url' => self::BG_URL ],
		];

		$this->controls['bgSize'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background size', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'cover'     => 'cover',
				'contain'   => 'contain',
				'100% 100%' => '100% 100%',
			],
			'default' => 'cover',
		];

		$this->controls['bgPosition'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background position', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'center center',
		];

		$this->controls['radius'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 0,
		];
	}

	/* ---------------------------------------------------------------------
	 * Notice bar
	 * ------------------------------------------------------------------ */

	private function marquee_controls() {
		$this->controls['mqEnable'] = [
			'tab'     => 'content',
			'group'   => 'marquee',
			'label'   => esc_html__( 'Show the notice bar', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['mqItems'] = [
			'tab'         => 'content',
			'group'       => 'marquee',
			'label'       => esc_html__( 'Items', 'pfh-widgets' ),
			'type'        => 'textarea',
			'default'     => 'Gratis verzending bij bestellingen boven €40',
			'description' => esc_html__( 'One item per line. A single line repeats across the bar.', 'pfh-widgets' ),
			'required'    => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqIcon'] = [
			'tab'         => 'content',
			'group'       => 'marquee',
			'label'       => esc_html__( 'Separator icon', 'pfh-widgets' ),
			'type'        => 'image',
			'default'     => [ 'url' => self::BEE_URL ],
			'required'    => [ 'mqEnable', '=', true ],
			'description' => esc_html__( 'Sits between items. Leave empty for a plain gap.', 'pfh-widgets' ),
		];

		$this->controls['mqIconSize'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Icon size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 8,
			'max'      => 60,
			'inline'   => true,
			'default'  => 30,
			'required' => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqRepeat'] = [
			'tab'         => 'content',
			'group'       => 'marquee',
			'label'       => esc_html__( 'Repeats per pass', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 12,
			'inline'      => true,
			'default'     => 4,
			'required'    => [ 'mqEnable', '=', true ],
			'description' => esc_html__( 'How many times the list repeats before it loops. Raise it if you see a gap on wide screens.', 'pfh-widgets' ),
		];

		$this->controls['mqSpeed'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Seconds per loop', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 4,
			'max'      => 200,
			'inline'   => true,
			'default'  => 32,
			'required' => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqDirection'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Direction', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => [
				'left'  => esc_html__( 'Right to left', 'pfh-widgets' ),
				'right' => esc_html__( 'Left to right', 'pfh-widgets' ),
			],
			'default'  => 'left',
			'required' => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqPause'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Pause on hover', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqBg'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Background', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#f5dfa8' ],
			'required' => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqColor'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#9b4713' ],
			'required' => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqHeight'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Bar height (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 20,
			'max'      => 120,
			'inline'   => true,
			'default'  => 46,
			'required' => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqSize'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Font size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 9,
			'max'      => 40,
			'inline'   => true,
			'default'  => 20,
			'required' => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqWeight'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Font weight', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => $this->weight_options(),
			'default'  => '600',
			'required' => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqGap'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Gap between items (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 4,
			'max'      => 140,
			'inline'   => true,
			'default'  => 48,
			'required' => [ 'mqEnable', '=', true ],
		];

		$this->controls['mqOverlap'] = [
			'tab'         => 'content',
			'group'       => 'marquee',
			'label'       => esc_html__( 'Bar sits over the section', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'required'    => [ 'mqEnable', '=', true ],
			'description' => esc_html__( 'On: pinned to the bottom edge, as in the design. Off: stacked underneath.', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Setting accessors
	 * ------------------------------------------------------------------ */

	private function get( $key, $default = null ) {
		return $this->setting( $key, $default );
	}

	private function is_on( $key, $default = true ) {
		return $this->switched_on( $key, $default );
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$classes = [
			'pfh-feat',
			'pfh-scope',
			'pfh-media-' . (string) $this->get( 'imageSide', 'right' ),
			'pfh-morder-' . (string) $this->get( 'mobileOrder', 'image' ),
		];

		if ( 'container' !== (string) $this->get( 'decorAnchor', 'full' ) ) {
			$classes[] = 'decor-full';
		}

		if ( $this->is_on( 'headItalic' ) ) {
			$classes[] = 'is-head-italic';
		}

		if ( $this->is_on( 'mqEnable' ) && $this->is_on( 'mqOverlap' ) ) {
			$classes[] = 'has-overlap-bar';
		}

		if ( ! $this->is_on( 'imageMobile' ) ) {
			$classes[] = 'hide-media-mobile';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';

		echo '<div class="pfh-feat__inner">';
		$this->render_content();
		echo '</div>';

		// After the content in the DOM: absolute positioning ignores source
		// order on desktop, and it gives the right stacking order on mobile
		// where the section becomes a plain block.
		$this->render_media();
		$this->render_decor();

		$this->render_marquee();

		echo '</section>';
	}

	private function render_media() {
		$url = PFH_Widgets_Helpers::image_url( $this->get( 'image' ), 'full' );

		if ( ! $url ) {
			return;
		}

		$setting = $this->get( 'image' );
		$extra   = '';

		// A media-library image can ship its whole size set, so a phone is not
		// made to download the full-bleed desktop crop. `sizes` describes the
		// band's real width: roughly the image column on desktop, the full
		// viewport once it stacks.
		if ( is_array( $setting ) && ! empty( $setting['id'] ) && function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$srcset = wp_get_attachment_image_srcset( (int) $setting['id'], 'large' );

			if ( $srcset ) {
				$extra = sprintf(
					' srcset="%s" sizes="%s"',
					esc_attr( $srcset ),
					esc_attr( '(max-width: 991px) 100vw, ' . (int) $this->get( 'imageWidth', 57 ) . 'vw' )
				);
			}
		}

		printf(
			'<div class="pfh-feat__media"><img class="pfh-feat__img" src="%s" alt="%s"%s loading="lazy" decoding="async" /></div>',
			esc_url( $url ),
			esc_attr( PFH_Widgets_Helpers::dd( (string) $this->get( 'imageAlt', '' ) ) ),
			$extra // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		);
	}

	private function render_decor() {
		$url = PFH_Widgets_Helpers::image_url( $this->get( 'decorImage' ), 'large' );

		if ( ! $url ) {
			return;
		}

		$style = PFH_Widgets_Helpers::css_vars(
			array_merge(
			[
				'--pfh-d-x'   => PFH_Widgets_Helpers::unit( $this->get( 'decorX', 82 ), '%' ),
				'--pfh-d-y'   => PFH_Widgets_Helpers::unit( $this->get( 'decorY', 6 ), '%' ),
				'--pfh-d-w'   => $this->is_on( 'decorScale' ) ? PFH_Widgets_Helpers::fluid( $this->get( 'decorW', 190 ) ) : PFH_Widgets_Helpers::unit( $this->get( 'decorW', 190 ) ),
				'--pfh-d-rot' => PFH_Widgets_Helpers::unit( $this->get( 'decorRotate', 0 ), 'deg' ),
				'--pfh-d-o'   => ( (float) $this->get( 'decorOpacity', 100 ) ) / 100,
			],
			PFH_Widgets_Helpers::edges(
				$this->get( 'decorPin', 'top-right' ),
				$this->get( 'decorDX', 64 ),
				$this->get( 'decorDY', 44 ),
				'--pfh-d-'
			)
			)
		);

		// Wrapped in a box the width of the content column, so the percentage
		// position holds its place in the design at any viewport width.
		printf(
			'<div class="pfh-feat__decor-area"><img class="pfh-feat__decor%s" src="%s" alt="" aria-hidden="true" loading="lazy" decoding="async" style="%s" /></div>',
			$this->is_on( 'decorHideMobile' ) ? ' is-hidden-mobile' : '',
			esc_url( $url ),
			esc_attr( $style )
		);
	}

	private function render_content() {
		$heading = (string) $this->get( 'heading', '' );
		$text    = (string) $this->get( 'text', '' );
		$label   = (string) $this->get( 'btnLabel', '' );

		if ( ! $heading && ! $text && ! $label ) {
			return;
		}

		echo '<div class="pfh-feat__content">';

		if ( $heading ) {
			echo '<h2 class="pfh-feat__title">' . wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $heading ) ) ) . '</h2>';
		}

		if ( $text ) {
			echo '<div class="pfh-feat__text">' . wp_kses_post( wpautop( PFH_Widgets_Helpers::dd( $text ) ) ) . '</div>';
		}

		if ( $label ) {
			$link  = PFH_Widgets_Helpers::link( $this->get( 'btnLink' ), '#' );
			$icon  = PFH_Widgets_Helpers::image_url( $this->get( 'btnIcon' ), 'full' );

			echo '<a class="pfh-feat__btn"' . PFH_Widgets_Helpers::link_attrs( $link ) . '>';
			echo '<span>' . esc_html( PFH_Widgets_Helpers::dd( $label ) ) . '</span>';

			if ( $icon ) {
				printf(
					'<img class="pfh-feat__btn-icon" src="%s" alt="" aria-hidden="true" decoding="async" />',
					esc_url( $icon )
				);
			}

			echo '</a>';
		}

		echo '</div>';
	}

	private function render_marquee() {
		if ( ! $this->is_on( 'mqEnable' ) ) {
			return;
		}

		$items = [];

		foreach ( PFH_Widgets_Helpers::parse_lines( (string) $this->get( 'mqItems', '' ) ) as $row ) {
			if ( ! empty( $row[0] ) ) {
				$items[] = PFH_Widgets_Helpers::dd( $row[0] );
			}
		}

		if ( empty( $items ) ) {
			return;
		}

		$icon    = PFH_Widgets_Helpers::image_url( $this->get( 'mqIcon' ), 'full' );
		$repeat  = max( 1, (int) $this->get( 'mqRepeat', 4 ) );
		$classes = [ 'pfh-feat__mq', 'is-' . (string) $this->get( 'mqDirection', 'left' ) ];

		if ( $this->is_on( 'mqPause' ) ) {
			$classes[] = 'is-pausable';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" aria-hidden="true">';
		echo '<div class="pfh-feat__mq-track">';

		// Two identical groups so the animation can loop seamlessly at -50%.
		for ( $copy = 0; $copy < 2; $copy++ ) {
			echo '<div class="pfh-feat__mq-group">';

			for ( $pass = 0; $pass < $repeat; $pass++ ) {
				foreach ( $items as $item ) {
					echo '<span class="pfh-feat__mq-item">' . esc_html( $item ) . '</span>';

					if ( $icon ) {
						printf(
							'<img class="pfh-feat__mq-icon" src="%s" alt="" decoding="async" />',
							esc_url( $icon )
						);
					}
				}
			}

			echo '</div>';
		}

		echo '</div>';
		echo '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Tokens
	 * ------------------------------------------------------------------ */

	private function build_vars() {
		$stack = ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif';
		$head  = trim( (string) $this->get( 'headFamily', 'Playfair Display' ) );
		$bg    = PFH_Widgets_Helpers::image_url( $this->get( 'bgImage' ), 'full' );

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-container'      => PFH_Widgets_Helpers::unit( $this->get( 'containerWidth', 1140 ) ),
				'--pfh-gutter-set'     => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-f-min-set'      => PFH_Widgets_Helpers::unit( $this->get( 'minHeight', 650 ) ),
				'--pfh-f-min-m'        => PFH_Widgets_Helpers::unit( $this->get( 'minHeightMobile', 320 ) ),
				'--pfh-f-pad-y-set'    => PFH_Widgets_Helpers::unit( $this->get( 'paddingY', 64 ) ),
				'--pfh-f-bg'           => PFH_Widgets_Helpers::color( $this->get( 'bgColor' ), '#f7e8cb' ),
				'--pfh-f-bg-img'       => $bg ? 'url(' . $bg . ')' : 'none',
				'--pfh-f-bg-size'      => $this->get( 'bgSize', 'cover' ),
				'--pfh-f-bg-pos'       => $this->get( 'bgPosition', 'center center' ),
				'--pfh-f-radius'       => PFH_Widgets_Helpers::unit( $this->get( 'radius', 0 ) ),

				'--pfh-head-font'      => $head ? $head . $stack : 'inherit',
				'--pfh-head-size-set'  => PFH_Widgets_Helpers::unit( $this->get( 'headSize', 36 ) ),
				'--pfh-head-size-m'    => PFH_Widgets_Helpers::unit( $this->get( 'headSizeMobile', 28 ) ),
				'--pfh-head-weight'    => $this->get( 'headWeight', '500' ),
				'--pfh-head-lh'        => $this->get( 'headLineHeight', 1.05 ),
				'--pfh-head-color'     => PFH_Widgets_Helpers::color( $this->get( 'headColor' ), '#5c2e15' ),

				'--pfh-txt-size'       => PFH_Widgets_Helpers::unit( $this->get( 'textSize', 14 ) ),
				'--pfh-txt-weight'     => $this->get( 'textWeight', '400' ),
				'--pfh-txt-lh'         => $this->get( 'textLineHeight', 1.5 ),
				'--pfh-txt-color'      => PFH_Widgets_Helpers::color( $this->get( 'textColor' ), '#7e4a28' ),
				'--pfh-txt-width'      => PFH_Widgets_Helpers::unit( $this->get( 'textWidth', 370 ) ),
				'--pfh-link-color'     => PFH_Widgets_Helpers::color( $this->get( 'linkColor' ), '#5c2e15' ),
				'--pfh-link-weight'    => $this->get( 'linkWeight', '600' ),
				'--pfh-gap-1'          => PFH_Widgets_Helpers::unit( $this->get( 'gapHeadText', 18 ) ),
				'--pfh-gap-2'          => PFH_Widgets_Helpers::unit( $this->get( 'gapTextBtn', 28 ) ),

				'--pfh-btn-h'          => PFH_Widgets_Helpers::unit( $this->get( 'btnHeight', 40 ) ),
				'--pfh-btn-pad-set'        => PFH_Widgets_Helpers::unit( $this->get( 'btnPadding', 36 ) ),
				'--pfh-btn-gap'        => PFH_Widgets_Helpers::unit( $this->get( 'btnGap', 10 ) ),
				'--pfh-btn-radius'     => PFH_Widgets_Helpers::unit( $this->get( 'btnRadius', 70 ) ),
				'--pfh-btn-size'       => PFH_Widgets_Helpers::unit( $this->get( 'btnSize', 14 ) ),
				'--pfh-btn-weight'     => $this->get( 'btnWeight', '500' ),
				'--pfh-btn-bg'         => PFH_Widgets_Helpers::color( $this->get( 'btnBg' ), '#ffffff' ),
				'--pfh-btn-op'         => ( (float) $this->get( 'btnOpacity', 80 ) ) / 100,
				'--pfh-btn-color'      => PFH_Widgets_Helpers::color( $this->get( 'btnColor' ), '#ad6738' ),
				'--pfh-btn-border'     => PFH_Widgets_Helpers::color( $this->get( 'btnBorder' ), '#f2e0bf' ),
				'--pfh-btn-bw'         => PFH_Widgets_Helpers::unit( $this->get( 'btnBorderWidth', 1 ) ),
				'--pfh-btn-hover'      => PFH_Widgets_Helpers::color( $this->get( 'btnHoverBg' ), '#ffffff' ),
				'--pfh-btn-icon'       => PFH_Widgets_Helpers::unit( $this->get( 'btnIconSize', 16 ) ),

				'--pfh-media-w-set'        => PFH_Widgets_Helpers::unit( $this->get( 'imageWidth', 57 ), '%' ),
				'--pfh-media-fit'      => $this->get( 'imageFit', 'cover' ),
				'--pfh-media-pos'      => $this->get( 'imagePosition', 'center center' ),
				'--pfh-media-h-m'      => PFH_Widgets_Helpers::unit( $this->get( 'imageMobileHeight', 280 ) ),

				'--pfh-mq-bg'          => PFH_Widgets_Helpers::color( $this->get( 'mqBg' ), '#f5dfa8' ),
				'--pfh-mq-color'       => PFH_Widgets_Helpers::color( $this->get( 'mqColor' ), '#9b4713' ),
				'--pfh-mq-h-set'       => PFH_Widgets_Helpers::unit( $this->get( 'mqHeight', 46 ) ),
				'--pfh-mq-size-set'    => PFH_Widgets_Helpers::unit( $this->get( 'mqSize', 20 ) ),
				'--pfh-mq-weight'      => $this->get( 'mqWeight', '600' ),
				'--pfh-mq-gap-set'     => PFH_Widgets_Helpers::unit( $this->get( 'mqGap', 48 ) ),
				'--pfh-mq-icon-set'        => PFH_Widgets_Helpers::unit( $this->get( 'mqIconSize', 30 ) ),
				'--pfh-mq-time'        => PFH_Widgets_Helpers::unit( $this->get( 'mqSpeed', 32 ), 's' ),
			]
		);
	}
}
