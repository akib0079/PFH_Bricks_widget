<?php
/**
 * Bricks element: Products For Home hero slider.
 *
 * Crossfading hero slider with staggered reveal animations, per-slide
 * featured / floating / avatar imagery, and section-level decorations.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Hero extends \Bricks\Element {

	use PFH_Element_Defaults;

	/**
	 * Cached slide list.
	 *
	 * Per instance, not a method static: a static is shared by every
	 * instance of the class, so a second copy of this element on the same
	 * page would reuse the first one's value instead of computing its own.
	 *
	 * @var mixed
	 */
	private $slides = null;

	/**
	 * Cached element uid.
	 *
	 * Per instance, not a method static: a static is shared by every
	 * instance of the class, so a second copy of this element on the same
	 * page would reuse the first one's value instead of computing its own.
	 *
	 * @var mixed
	 */
	private $uid = null;

	public $category     = 'products-for-home';
	public $name         = 'pfh-hero';
	public $icon         = 'ti-layout-slider';
	public $css_selector = '.pfh-hero';
	public $scripts      = [ 'pfhHeroInit' ];

	/**
	 * Default asset URLs supplied by the client.
	 */
	const PRODUCT_URL = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Group-1-1.png';
	const LEMON_URL   = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/pngwing.com-32-1.png';
	/*
	 * The supplied cut-out is matted onto white, which fringes against the
	 * hero's warm ground. This is the same artwork with the matte recovered
	 * and the halo softened; see assets/img/.
	 */
	/* Resolved at call time, because it is served from the project repo. */
	const BG_URL      = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Frame-469999-1.jpg';
	const AVATAR_URL  = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Frame-16.png';
	const STARS_URL   = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Frame-17.svg';
	const ARROW_URL   = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Vector-1.svg';

	public function get_label() {
		return esc_html__( 'PFH Hero Slider', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'hero', 'slider', 'banner', 'carousel', 'slideshow', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::hero();
	}

	public function set_control_groups() {
		$this->control_groups['slides']  = [ 'title' => esc_html__( 'Slides', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout']  = [ 'title' => esc_html__( 'Layout & background', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']    = [ 'title' => esc_html__( 'Typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['rating']  = [ 'title' => esc_html__( 'Rating row', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['buttons'] = [ 'title' => esc_html__( 'Buttons', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['media']   = [ 'title' => esc_html__( 'Media & images', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['decor']   = [ 'title' => esc_html__( 'Section decorations', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['motion']  = [ 'title' => esc_html__( 'Slider & motion', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['nav']     = [ 'title' => esc_html__( 'Dots & arrows', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['marquee'] = [ 'title' => esc_html__( 'Marquee bar', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->slide_controls();
		$this->layout_controls();
		$this->type_controls();
		$this->rating_controls();
		$this->button_controls();
		$this->media_controls();
		$this->decor_controls();
		$this->motion_controls();
		$this->nav_controls();
		$this->marquee_controls();
	}

	/* ---------------------------------------------------------------------
	 * Shared option lists
	 * ------------------------------------------------------------------ */

	private function weight_options() {
		return [
			'300' => esc_html__( 'Light (300)', 'pfh-widgets' ),
			'400' => esc_html__( 'Regular (400)', 'pfh-widgets' ),
			'500' => esc_html__( 'Medium (500)', 'pfh-widgets' ),
			'600' => esc_html__( 'Semibold (600)', 'pfh-widgets' ),
			'700' => esc_html__( 'Bold (700)', 'pfh-widgets' ),
		];
	}

	/**
	 * Background-removal modes for flattened (JPG) cutout images.
	 *
	 * @return array<string, string>
	 */
	public function cutout_options() {
		return [
			''       => esc_html__( 'Keep as uploaded (PNG/SVG)', 'pfh-widgets' ),
			'black'  => esc_html__( 'Cut out a black background', 'pfh-widgets' ),
			'white'  => esc_html__( 'Cut out a white background', 'pfh-widgets' ),
			'screen' => esc_html__( 'Blend out black (dark backgrounds only)', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Slides
	 * ------------------------------------------------------------------ */

	private function slide_controls() {
		$this->controls['slides'] = [
			'tab'           => 'content',
			'group'         => 'slides',
			'label'         => esc_html__( 'Slides', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'default'       => $this->default_slides(),
			'fields'        => [
				// -- Copy ------------------------------------------------
				'eyebrow'      => [
					'label' => esc_html__( 'Eyebrow', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'title'        => [
					'label'       => esc_html__( 'Title', 'pfh-widgets' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'Line breaks are kept. Wrap words in <em>…</em> for the italic accent, as in the design.', 'pfh-widgets' ),
				],
				'text'         => [
					'label' => esc_html__( 'Description', 'pfh-widgets' ),
					'type'  => 'textarea',
				],

				// -- Buttons ---------------------------------------------
				'btn1Label'    => [
					'label'  => esc_html__( 'Button label', 'pfh-widgets' ),
					'type'   => 'text',
					'inline' => true,
				],
				'btn1Link'     => [
					'label'    => esc_html__( 'Button link', 'pfh-widgets' ),
					'type'     => 'link',
					'required' => [ 'btn1Label', '!=', '' ],
				],
				'btn2Label'    => [
					'label'  => esc_html__( 'Secondary label', 'pfh-widgets' ),
					'type'   => 'text',
					'inline' => true,
				],
				'btn2Link'     => [
					'label'    => esc_html__( 'Secondary link', 'pfh-widgets' ),
					'type'     => 'link',
					'required' => [ 'btn2Label', '!=', '' ],
				],

				// -- Rating ----------------------------------------------
				'ratingText'   => [
					'label'       => esc_html__( 'Rating text', 'pfh-widgets' ),
					'type'        => 'text',
					'placeholder' => '(%count%+ reviews) %score%',
				],
				'avatarImage'  => [
					'label'       => esc_html__( 'Avatar image (bulk)', 'pfh-widgets' ),
					'type'        => 'image',
					'description' => esc_html__( 'One image containing the whole avatar cluster.', 'pfh-widgets' ),
				],
				'avatarList'   => [
					'label'       => esc_html__( 'Or separate avatars', 'pfh-widgets' ),
					'type'        => 'textarea',
					'placeholder' => "https://…/avatar-1.jpg\nhttps://…/avatar-2.jpg",
					'description' => esc_html__( 'One image URL per line. Rendered as overlapping circles and takes priority over the bulk image.', 'pfh-widgets' ),
				],

				// -- Featured image --------------------------------------
				'image'        => [
					'label' => esc_html__( 'Featured image', 'pfh-widgets' ),
					'type'  => 'image',
				],
				'imageAlt'     => [
					'label'    => esc_html__( 'Featured image alt text', 'pfh-widgets' ),
					'type'     => 'text',
					'inline'   => true,
					'required' => [ 'image', '!=', '' ],
				],
				'imageCutout'  => [
					'label'   => esc_html__( 'Featured image background', 'pfh-widgets' ),
					'type'    => 'select',
					'inline'  => true,
					'options' => $this->cutout_options(),
					'default' => '',
				],
				'imageWidth'   => [
					'label'       => esc_html__( 'Featured image width (%)', 'pfh-widgets' ),
					'type'        => 'number',
					'min'         => 20,
					'max'         => 140,
					'inline'      => true,
					'default'     => 100,
					'description' => esc_html__( 'Relative to the media column.', 'pfh-widgets' ),
				],

				// -- Floating image 1 ------------------------------------
				'float1Image'  => [
					'label' => esc_html__( 'Floating image 1', 'pfh-widgets' ),
					'type'  => 'image',
				],
				'float1Cutout' => [
					'label'    => esc_html__( 'Background', 'pfh-widgets' ),
					'type'     => 'select',
					'inline'   => true,
					'options'  => $this->cutout_options(),
					'default'  => '',
					'required' => [ 'float1Image', '!=', '' ],
				],
				'float1W'      => [
					'label'    => esc_html__( 'Width (px)', 'pfh-widgets' ),
					'type'     => 'number',
					'min'      => 20,
					'max'      => 700,
					'inline'   => true,
					'default'  => 190,
					'required' => [ 'float1Image', '!=', '' ],
				],
				'float1X'      => [
					'label'    => esc_html__( 'Position X (%)', 'pfh-widgets' ),
					'type'     => 'number',
					'min'      => -60,
					'max'      => 160,
					'inline'   => true,
					'default'  => -8,
					'required' => [ 'float1Image', '!=', '' ],
				],
				'float1Y'      => [
					'label'    => esc_html__( 'Position Y (%)', 'pfh-widgets' ),
					'type'     => 'number',
					'min'      => -60,
					'max'      => 160,
					'inline'   => true,
					'default'  => 4,
					'required' => [ 'float1Image', '!=', '' ],
				],
				'float1Front'  => [
					'label'    => esc_html__( 'In front of the product', 'pfh-widgets' ),
					'type'     => 'checkbox',
					'required' => [ 'float1Image', '!=', '' ],
				],

				// -- Floating image 2 ------------------------------------
				'float2Image'  => [
					'label' => esc_html__( 'Floating image 2', 'pfh-widgets' ),
					'type'  => 'image',
				],
				'float2Cutout' => [
					'label'    => esc_html__( 'Background', 'pfh-widgets' ),
					'type'     => 'select',
					'inline'   => true,
					'options'  => $this->cutout_options(),
					'default'  => '',
					'required' => [ 'float2Image', '!=', '' ],
				],
				'float2W'      => [
					'label'    => esc_html__( 'Width (px)', 'pfh-widgets' ),
					'type'     => 'number',
					'min'      => 20,
					'max'      => 700,
					'inline'   => true,
					'default'  => 150,
					'required' => [ 'float2Image', '!=', '' ],
				],
				'float2X'      => [
					'label'    => esc_html__( 'Position X (%)', 'pfh-widgets' ),
					'type'     => 'number',
					'min'      => -60,
					'max'      => 160,
					'inline'   => true,
					'default'  => 86,
					'required' => [ 'float2Image', '!=', '' ],
				],
				'float2Y'      => [
					'label'    => esc_html__( 'Position Y (%)', 'pfh-widgets' ),
					'type'     => 'number',
					'min'      => -60,
					'max'      => 160,
					'inline'   => true,
					'default'  => 52,
					'required' => [ 'float2Image', '!=', '' ],
				],
				'float2Front'  => [
					'label'    => esc_html__( 'In front of the product', 'pfh-widgets' ),
					'type'     => 'checkbox',
					'required' => [ 'float2Image', '!=', '' ],
				],

				// -- Per slide overrides ---------------------------------
				'bgImage'      => [
					'label'       => esc_html__( 'Background image override', 'pfh-widgets' ),
					'type'        => 'image',
					'description' => esc_html__( 'Falls back to the section background.', 'pfh-widgets' ),
				],
				'textColor'    => [
					'label' => esc_html__( 'Text colour override', 'pfh-widgets' ),
					'type'  => 'color',
				],
			],
		];
	}

	private function default_slides() {
		$base = [
			'ratingText'  => '(%count%+ reviews) %score%',
			'avatarImage' => [ 'url' => self::AVATAR_URL ],
			'image'       => [ 'url' => self::PRODUCT_URL ],
			'imageCutout' => '',
			'imageWidth'  => 100,
			'btn1Label'   => 'Shop Now',
			'btn2Label'   => 'Learn More',
		];

		return [
			array_merge(
				$base,
				[
					'title'    => "Een frisse smaak\nvan <em>Griekenland</em>",
					'text'     => 'Ontdek Gia Giamas. De authentieke, natuurlijke dorstlesser vol zongerijpt fruit.',
					'imageAlt' => 'Gia Giamas limonade',
				]
			),
			array_merge(
				$base,
				[
					'title' => "Rauwe honing uit\n<em>Griekenland</em>",
					'text'  => 'Direct van de imker, ongefilterd en vol natuurlijke aroma’s.',
				]
			),
		];
	}

	/* ---------------------------------------------------------------------
	 * Layout & background
	 * ------------------------------------------------------------------ */

	private function layout_controls() {
		$this->controls['decorAnchor'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Position floating images against', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'full'      => esc_html__( 'The full section width', 'pfh-widgets' ),
				'container' => esc_html__( 'The content column', 'pfh-widgets' ),
			],
			'default'     => 'full',
			'description' => esc_html__( 'Full width keeps the design\'s proportions across the whole band, which is what the layout is drawn for. Switch to the content column to pin a decoration beside the copy instead.', 'pfh-widgets' ),
		];

		$this->controls['decorScale'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Scale floating images with the screen', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Their width is drawn against a 1440 frame; scaling keeps that proportion on a wider monitor instead of leaving them stranded at a fixed size.', 'pfh-widgets' ),
		];

		$this->controls['designFrame'] = [
			'tab'      => 'content',
			'group'    => 'layout',
			'label'    => esc_html__( 'Design frame width (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 320,
			'max'      => 2560,
			'inline'   => true,
			'default'  => 1440,
			'required' => [ 'decorScale', '=', true ],
		];

		$this->controls['containerWidth'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Content width (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 600,
			'max'         => 1920,
			'inline'      => true,
			'default'     => 1140,
			'description' => esc_html__( 'Keep this the same as the header and footer.', 'pfh-widgets' ),
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
			'min'     => 320,
			'max'     => 1200,
			'inline'  => true,
			'default' => 736,
		];

		$this->controls['minHeightMobile'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Minimum height on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 240,
			'max'     => 1000,
			'inline'  => true,
			'default' => 520,
		];

		$this->controls['paddingY'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Vertical padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 56,
		];

		$this->controls['textRatio'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Text column width (%)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 25,
			'max'         => 75,
			'inline'      => true,
			'default'     => 46,
			'description' => esc_html__( 'The media column takes the rest.', 'pfh-widgets' ),
		];

		$this->controls['columnGap'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Gap between columns (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 160,
			'inline'  => true,
			'default' => 40,
		];

		$this->controls['mediaFirst'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Media on the left', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => false,
		];

		$this->controls['bgColor'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
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
				'cover'   => 'cover',
				'contain' => 'contain',
				'auto'    => 'auto',
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

		$this->controls['bgOverlay'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Background overlay', 'pfh-widgets' ),
			'type'        => 'color',
			'description' => esc_html__( 'Sits between the image and the content. Leave empty for none.', 'pfh-widgets' ),
		];

		$this->controls['bottomRadius'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Bottom corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 0,
		];
	}

	/* ---------------------------------------------------------------------
	 * Typography
	 * ------------------------------------------------------------------ */

	private function type_controls() {
		$this->controls['fontFamily'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Font family', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Outfit',
		];

		$this->controls['titleFamily'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Title font family', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => 'Playfair Display',
			'description' => esc_html__( 'Leave empty to use the font above.', 'pfh-widgets' ),
		];

		$this->controls['titleSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Title size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 20,
			'max'     => 140,
			'inline'  => true,
			'default' => 60,
		];

		$this->controls['titleSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Title size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 18,
			'max'     => 90,
			'inline'  => true,
			'default' => 36,
		];

		$this->controls['titleWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Title weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '400',
		];

		$this->controls['titleLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Title line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0.8,
			'max'     => 2,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.15,
		];

		$this->controls['titleColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Title colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#484c4d' ],
		];

		$this->controls['titleMaxWidth'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Title max width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 120,
			'max'     => 900,
			'inline'  => true,
			'default' => 520,
		];

		$this->controls['textSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Description size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 32,
			'inline'  => true,
			'default' => 18,
		];

		$this->controls['textColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Description colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#66758e' ],
		];

		$this->controls['textWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Description weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '300',
		];

		$this->controls['textLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Description line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0.9,
			'max'     => 2.4,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.4,
		];

		$this->controls['gapRatingTitle'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Space under the rating row (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 90,
			'inline'  => true,
			'default' => 26,
		];

		$this->controls['gapTitleText'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Space under the title (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 90,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['gapTextButtons'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Space above the buttons (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 90,
			'inline'  => true,
			'default' => 30,
		];

		$this->controls['textMaxWidth'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Description max width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 120,
			'max'     => 900,
			'inline'  => true,
			'default' => 412,
		];

		$this->controls['eyebrowSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Eyebrow size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 9,
			'max'     => 26,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['eyebrowColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Eyebrow colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#6f8566' ],
		];
	}

	/* ---------------------------------------------------------------------
	 * Rating row
	 * ------------------------------------------------------------------ */

	private function rating_controls() {
		$this->controls['ratingLive'] = [
			'tab'         => 'content',
			'group'       => 'rating',
			'label'       => esc_html__( 'Use the live WebwinkelKeur rating', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Put %score%, %count% or %total% in a slide\'s rating text and they are replaced with the real figures. Without a token the numbers already in the text are replaced instead — the first is the count, a trailing decimal is the score. Whatever is typed shows if the feed is unreachable, and in the builder.', 'pfh-widgets' ),
			'required'    => [ 'ratingEnable', '=', true ],
		];

		$this->controls['ratingScale'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Score shown out of', 'pfh-widgets' ),
			'type'     => 'select',
			'options'  => [
				'5'  => esc_html__( '5 — e.g. 4.9', 'pfh-widgets' ),
				'10' => esc_html__( '10 — e.g. 9.7', 'pfh-widgets' ),
			],
			'default'  => '5',
			'inline'   => true,
			'required' => [ 'ratingLive', '=', true ],
		];

		$this->controls['ratingRound'] = [
			'tab'         => 'content',
			'group'       => 'rating',
			'label'       => esc_html__( 'Round %count% down to', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 1000,
			'step'        => 10,
			'default'     => 0,
			'inline'      => true,
			'description' => esc_html__( 'For a "270+ reviews" style label. 0 shows the exact number. %total% is always exact.', 'pfh-widgets' ),
			'required'    => [ 'ratingLive', '=', true ],
		];

		$this->controls['starsLive'] = [
			'tab'         => 'content',
			'group'       => 'rating',
			'label'       => esc_html__( 'Draw the stars from the score', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Replaces the star image below with a row that fills to the real score, rounded to the nearest half star. At 9.7/10 that is five full stars, so the design is unchanged until the score actually moves.', 'pfh-widgets' ),
			'required'    => [ 'ratingEnable', '=', true ],
		];

		$this->controls['starsColor'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Star colour', 'pfh-widgets' ),
			'type'     => 'color',
			'inline'   => true,
			'required' => [ 'starsLive', '=', true ],
		];

		$this->controls['ratingEnable'] = [
			'tab'     => 'content',
			'group'   => 'rating',
			'label'   => esc_html__( 'Show rating row', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['starsImage'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Stars image', 'pfh-widgets' ),
			'type'     => 'image',
			'default'  => [ 'url' => self::STARS_URL ],
			'required' => [ 'ratingEnable', '=', true ],
		];

		$this->controls['starsWidth'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Stars width (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 40,
			'max'      => 240,
			'inline'   => true,
			'default'  => 91,
			'required' => [ 'ratingEnable', '=', true ],
		];

		$this->controls['ratingFamily'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Rating font family', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Playfair Display',
			'required' => [ 'ratingEnable', '=', true ],
		];

		$this->controls['starsTextGap'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Gap between stars and text (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 40,
			'inline'   => true,
			'default'  => 10,
			'required' => [ 'ratingEnable', '=', true ],
		];

		$this->controls['avatarHeight'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Avatar height (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 16,
			'max'      => 80,
			'inline'   => true,
			'default'  => 36,
			'required' => [ 'ratingEnable', '=', true ],
		];

		$this->controls['avatarCutout'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Avatar image background', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => $this->cutout_options(),
			'default'  => '',
			'required' => [ 'ratingEnable', '=', true ],
		];

		$this->controls['avatarOverlap'] = [
			'tab'         => 'content',
			'group'       => 'rating',
			'label'       => esc_html__( 'Separate avatar overlap (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 40,
			'inline'      => true,
			'default'     => 10,
			'required'    => [ 'ratingEnable', '=', true ],
			'description' => esc_html__( 'Only used when a slide lists separate avatar URLs.', 'pfh-widgets' ),
		];

		$this->controls['ratingGap'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Gap after the avatars (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 48,
			'inline'   => true,
			'default'  => 16,
			'required' => [ 'ratingEnable', '=', true ],
		];

		$this->controls['ratingSize'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Rating text size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 9,
			'max'      => 26,
			'inline'   => true,
			'default'  => 14,
			'required' => [ 'ratingEnable', '=', true ],
		];

		$this->controls['ratingColor'] = [
			'tab'      => 'content',
			'group'    => 'rating',
			'label'    => esc_html__( 'Rating text colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#000000' ],
			'required' => [ 'ratingEnable', '=', true ],
		];
	}

	/* ---------------------------------------------------------------------
	 * Buttons
	 * ------------------------------------------------------------------ */

	private function button_controls() {
		$this->controls['btnArrow'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Button arrow icon', 'pfh-widgets' ),
			'type'    => 'image',
			'default' => [ 'url' => self::ARROW_URL ],
		];

		$this->controls['btnArrowSize'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Arrow size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 6,
			'max'     => 32,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['btnBg'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#7caeb2' ],
		];

		$this->controls['btnColor'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['btnHoverBg'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Background (hover)', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#6b9da1' ],
		];

		$this->controls['btnHeight'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 32,
			'max'     => 90,
			'inline'  => true,
			'default' => 48,
		];

		$this->controls['btnPadding'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Side padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 80,
			'inline'  => true,
			'default' => 26,
		];

		$this->controls['btnRadius'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 70,
		];

		$this->controls['btnGap'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Gap: label to arrow (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 40,
			'inline'  => true,
			'default' => 10,
		];

		$this->controls['btnsGap'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Gap between the two buttons (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['btn2Bg'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Secondary background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['btn2Color'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Secondary text colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#5f6d46' ],
		];

		$this->controls['btn2Border'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Secondary border colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#5f6d46' ],
		];

		$this->controls['btn2Arrow'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Show arrow on the secondary button', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['btnSize'] = [
			'tab'     => 'content',
			'group'   => 'buttons',
			'label'   => esc_html__( 'Font size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 26,
			'inline'  => true,
			'default' => 15,
		];
	}

	/* ---------------------------------------------------------------------
	 * Media
	 * ------------------------------------------------------------------ */

	private function media_controls() {
		$this->controls['cutoutStrength'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Cutout edge strength', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 2,
			'max'         => 60,
			'inline'      => true,
			'default'     => 20,
			'description' => esc_html__( 'Higher removes more of the flattened background but hardens the edge. Only affects images set to "cut out".', 'pfh-widgets' ),
		];

		$this->controls['mediaAlign'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Media alignment', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'center'     => esc_html__( 'Centre', 'pfh-widgets' ),
				'flex-end'   => esc_html__( 'Bottom', 'pfh-widgets' ),
				'flex-start' => esc_html__( 'Top', 'pfh-widgets' ),
			],
			'default' => 'center',
		];

		$this->controls['mediaOffsetX'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Horizontal offset (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => -300,
			'max'         => 300,
			'inline'      => true,
			'default'     => 0,
			'description' => esc_html__( 'Nudges the product sideways. Positive moves it right, past the content column if needed.', 'pfh-widgets' ),
		];

		$this->controls['mediaOffsetY'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Vertical offset (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => -300,
			'max'     => 300,
			'inline'  => true,
			'default' => 0,
		];

		$this->controls['mediaShadow'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Soft glow behind the product', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['mediaGlowColor'] = [
			'tab'      => 'content',
			'group'    => 'media',
			'label'    => esc_html__( 'Glow colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(238, 230, 209, 0.85)' ],
			'required' => [ 'mediaShadow', '=', true ],
		];

		$this->controls['floatAnimate'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Gently float the decorative images', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['floatParallax'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Follow the mouse (parallax)', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Disabled automatically on touch devices and when reduced motion is requested.', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Section decorations
	 * ------------------------------------------------------------------ */

	private function decor_controls() {
		foreach ( [ 1, 2 ] as $i ) {
			$this->controls[ "decor{$i}Image" ] = [
				'tab'         => 'content',
				'group'       => 'decor',
				'label'       => sprintf( /* translators: %d: decoration number. */ esc_html__( 'Decoration %d', 'pfh-widgets' ), $i ),
				'type'        => 'image',
				'default'     => [ 'url' => 1 === $i ? self::LEMON_URL : PFH_Widgets_Assets::img( 'pfh-hero-branch.png' ) ],
				'description' => 1 === $i ? esc_html__( 'Stays put while the slides change.', 'pfh-widgets' ) : '',
			];

			$this->controls[ "decor{$i}Cutout" ] = [
				'tab'      => 'content',
				'group'    => 'decor',
				'label'    => esc_html__( 'Background', 'pfh-widgets' ),
				'type'     => 'select',
				'inline'   => true,
				'options'  => $this->cutout_options(),
				'default'  => '',
				'required' => [ "decor{$i}Image", '!=', '' ],
			];

			$this->controls[ "decor{$i}W" ] = [
				'tab'      => 'content',
				'group'    => 'decor',
				'label'    => esc_html__( 'Width (px)', 'pfh-widgets' ),
				'type'     => 'number',
				'min'      => 20,
				'max'      => 900,
				'inline'   => true,
				'default'  => 1 === $i ? 230 : 150,
				'required' => [ "decor{$i}Image", '!=', '' ],
			];

			$this->controls[ "decor{$i}Anchor" ] = [
				'tab'         => 'content',
				'group'       => 'decor',
				'label'       => esc_html__( 'Pin to', 'pfh-widgets' ),
				'type'        => 'select',
				'inline'      => true,
				'options'     => [
					'bottom-left'  => esc_html__( 'Bottom left', 'pfh-widgets' ),
					'bottom-right' => esc_html__( 'Bottom right', 'pfh-widgets' ),
					'top-left'     => esc_html__( 'Top left', 'pfh-widgets' ),
					'top-right'    => esc_html__( 'Top right', 'pfh-widgets' ),
					'free'         => esc_html__( 'Free position (percentages below)', 'pfh-widgets' ),
				],
				'default'     => 1 === $i ? 'bottom-left' : 'bottom-right',
				'description' => esc_html__( 'Pinned to a corner it holds the same relationship to that edge at every screen size. A free position is a share of the section width, so it slides as the viewport grows.', 'pfh-widgets' ),
				'required'    => [ "decor{$i}Image", '!=', '' ],
			];

			$this->controls[ "decor{$i}DX" ] = [
				'tab'         => 'content',
				'group'       => 'decor',
				'label'       => esc_html__( 'Offset from that side (px)', 'pfh-widgets' ),
				'type'        => 'number',
				'min'         => -400,
				'max'         => 400,
				'inline'      => true,
				'default'     => 1 === $i ? -40 : -30,
				'description' => esc_html__( 'Negative runs the artwork off the edge.', 'pfh-widgets' ),
				'required'    => [ "decor{$i}Anchor", '!=', 'free' ],
			];

			$this->controls[ "decor{$i}DY" ] = [
				'tab'      => 'content',
				'group'    => 'decor',
				'label'    => esc_html__( 'Offset from top or bottom (px)', 'pfh-widgets' ),
				'type'     => 'number',
				'min'      => -400,
				'max'      => 400,
				'inline'   => true,
				'default'  => 0,
				'required' => [ "decor{$i}Anchor", '!=', 'free' ],
			];

			$this->controls[ "decor{$i}X" ] = [
				'tab'      => 'content',
				'group'    => 'decor',
				'label'    => esc_html__( 'Position X (%)', 'pfh-widgets' ),
				'type'     => 'number',
				'min'      => -40,
				'max'      => 140,
				'inline'   => true,
				'default'  => 1 === $i ? -4 : 94,
				'required' => [ "decor{$i}Anchor", '=', 'free' ],
			];

			$this->controls[ "decor{$i}Y" ] = [
				'tab'      => 'content',
				'group'    => 'decor',
				'label'    => esc_html__( 'Position Y (%)', 'pfh-widgets' ),
				'type'     => 'number',
				'min'      => -40,
				'max'      => 140,
				'inline'   => true,
				'default'  => 1 === $i ? 72 : 66,
				'required' => [ "decor{$i}Anchor", '=', 'free' ],
			];

			$this->controls[ "decor{$i}Opacity" ] = [
				'tab'      => 'content',
				'group'    => 'decor',
				'label'    => esc_html__( 'Opacity (%)', 'pfh-widgets' ),
				'type'     => 'number',
				'min'      => 5,
				'max'      => 100,
				'inline'   => true,
				'default'  => 100,
				'required' => [ "decor{$i}Image", '!=', '' ],
			];

			$this->controls[ "decor{$i}Hide" ] = [
				'tab'      => 'content',
				'group'    => 'decor',
				'label'    => esc_html__( 'Hide on mobile', 'pfh-widgets' ),
				'type'     => 'checkbox',
				'default'  => true,
				'required' => [ "decor{$i}Image", '!=', '' ],
			];
		}
	}

	/* ---------------------------------------------------------------------
	 * Slider & motion
	 * ------------------------------------------------------------------ */

	private function motion_controls() {
		$this->controls['autoplay'] = [
			'tab'     => 'content',
			'group'   => 'motion',
			'label'   => esc_html__( 'Autoplay', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['interval'] = [
			'tab'      => 'content',
			'group'    => 'motion',
			'label'    => esc_html__( 'Time per slide (seconds)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 2,
			'max'      => 30,
			'step'     => 0.5,
			'inline'   => true,
			'default'  => 6,
			'required' => [ 'autoplay', '=', true ],
		];

		$this->controls['pauseOnHover'] = [
			'tab'      => 'content',
			'group'    => 'motion',
			'label'    => esc_html__( 'Pause on hover', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'autoplay', '=', true ],
		];

		$this->controls['revealEffect'] = [
			'tab'     => 'content',
			'group'   => 'motion',
			'label'   => esc_html__( 'Reveal effect', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'up'    => esc_html__( 'Rise + fade', 'pfh-widgets' ),
				'mask'  => esc_html__( 'Mask wipe', 'pfh-widgets' ),
				'left'  => esc_html__( 'Slide in from the left', 'pfh-widgets' ),
				'zoom'  => esc_html__( 'Zoom + fade', 'pfh-widgets' ),
				'blur'  => esc_html__( 'Blur + fade', 'pfh-widgets' ),
				'fade'  => esc_html__( 'Fade only', 'pfh-widgets' ),
				'none'  => esc_html__( 'None', 'pfh-widgets' ),
			],
			'default' => 'up',
		];

		$this->controls['revealDistance'] = [
			'tab'      => 'content',
			'group'    => 'motion',
			'label'    => esc_html__( 'Reveal distance (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 160,
			'inline'   => true,
			'default'  => 26,
			'required' => [ 'revealEffect', '!=', [ 'fade', 'none' ] ],
		];

		$this->controls['revealDuration'] = [
			'tab'     => 'content',
			'group'   => 'motion',
			'label'   => esc_html__( 'Reveal duration (ms)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 100,
			'max'     => 2000,
			'step'    => 50,
			'inline'  => true,
			'default' => 750,
		];

		$this->controls['revealStagger'] = [
			'tab'         => 'content',
			'group'       => 'motion',
			'label'       => esc_html__( 'Stagger between elements (ms)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 400,
			'step'        => 10,
			'inline'      => true,
			'default'     => 90,
			'description' => esc_html__( 'Each part of the slide starts this much later than the one above it.', 'pfh-widgets' ),
		];

		$this->controls['crossfade'] = [
			'tab'     => 'content',
			'group'   => 'motion',
			'label'   => esc_html__( 'Crossfade duration (ms)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 100,
			'max'     => 2000,
			'step'    => 50,
			'inline'  => true,
			'default' => 600,
		];

		$this->controls['mediaEffect'] = [
			'tab'     => 'content',
			'group'   => 'motion',
			'label'   => esc_html__( 'Featured image effect', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'zoom'  => esc_html__( 'Zoom + fade', 'pfh-widgets' ),
				'up'    => esc_html__( 'Rise + fade', 'pfh-widgets' ),
				'right' => esc_html__( 'Slide in from the right', 'pfh-widgets' ),
				'fade'  => esc_html__( 'Fade only', 'pfh-widgets' ),
				'none'  => esc_html__( 'None', 'pfh-widgets' ),
			],
			'default' => 'zoom',
		];

		$this->controls['loop'] = [
			'tab'     => 'content',
			'group'   => 'motion',
			'label'   => esc_html__( 'Loop back to the first slide', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['swipe'] = [
			'tab'     => 'content',
			'group'   => 'motion',
			'label'   => esc_html__( 'Swipe on touch devices', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];
	}

	/* ---------------------------------------------------------------------
	 * Marquee bar
	 * ------------------------------------------------------------------ */

	private function marquee_controls() {
		$this->controls['marqueeEnable'] = [
			'tab'     => 'content',
			'group'   => 'marquee',
			'label'   => esc_html__( 'Show the scrolling bar', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['marqueeItems'] = [
			'tab'      => 'content',
			'group'       => 'marquee',
			'label'       => esc_html__( 'Items', 'pfh-widgets' ),
			'type'        => 'textarea',
			'default'     => "NATURALLY REFRESHING\nINSPIRED BY GREECE\nLIGHT & VIBRANT FLAVORS\nMADE FOR SHARING\nMEDITERRANEAN FRESHNESS\nGIA GIAMAS",
			'description' => esc_html__( 'One item per line.', 'pfh-widgets' ),
			'required'    => [ 'marqueeEnable', '=', true ],
		];

		$this->controls['marqueeSeparator'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Separator', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => '•',
			'required' => [ 'marqueeEnable', '=', true ],
		];

		$this->controls['marqueeSpeed'] = [
			'tab'      => 'content',
			'group'       => 'marquee',
			'label'       => esc_html__( 'Seconds per loop', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 5,
			'max'         => 200,
			'inline'      => true,
			'default'     => 40,
			'required'    => [ 'marqueeEnable', '=', true ],
			'description' => esc_html__( 'Higher is slower.', 'pfh-widgets' ),
		];

		$this->controls['marqueeDirection'] = [
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
			'required' => [ 'marqueeEnable', '=', true ],
		];

		$this->controls['marqueePause'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Pause on hover', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'marqueeEnable', '=', true ],
		];

		$this->controls['marqueeBg'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Background', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#7caeb2' ],
			'required' => [ 'marqueeEnable', '=', true ],
		];

		$this->controls['marqueeColor'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#ffffff' ],
			'required' => [ 'marqueeEnable', '=', true ],
		];

		$this->controls['marqueeHeight'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Bar height (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 24,
			'max'      => 120,
			'inline'   => true,
			'default'  => 40,
			'required' => [ 'marqueeEnable', '=', true ],
		];

		$this->controls['marqueeSize'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Font size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 9,
			'max'      => 32,
			'inline'   => true,
			'default'  => 16,
			'required' => [ 'marqueeEnable', '=', true ],
		];

		$this->controls['marqueeWeight'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Font weight', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => $this->weight_options(),
			'default'  => '400',
			'required' => [ 'marqueeEnable', '=', true ],
		];

		$this->controls['marqueeGap'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Gap between items (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 8,
			'max'      => 120,
			'inline'   => true,
			'default'  => 34,
			'required' => [ 'marqueeEnable', '=', true ],
		];

		$this->controls['marqueeLetterSpacing'] = [
			'tab'      => 'content',
			'group'    => 'marquee',
			'label'    => esc_html__( 'Letter spacing (em)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => -0.05,
			'max'      => 0.4,
			'step'     => 0.005,
			'inline'   => true,
			'default'  => 0.02,
			'required' => [ 'marqueeEnable', '=', true ],
		];
	}

	/* ---------------------------------------------------------------------
	 * Dots & arrows
	 * ------------------------------------------------------------------ */

	private function nav_controls() {
		$this->controls['dotsEnable'] = [
			'tab'     => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Show dots', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['dotsPosition'] = [
			'tab'      => 'content',
			'group'    => 'nav',
			'label'    => esc_html__( 'Dots position', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => [
				'right'  => esc_html__( 'Vertical, right edge', 'pfh-widgets' ),
				'left'   => esc_html__( 'Vertical, left edge', 'pfh-widgets' ),
				'bottom' => esc_html__( 'Horizontal, bottom', 'pfh-widgets' ),
			],
			'default'  => 'right',
			'required' => [ 'dotsEnable', '=', true ],
		];

		$this->controls['dotsOffset'] = [
			'tab'      => 'content',
			'group'       => 'nav',
			'label'       => esc_html__( 'Distance from the edge (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 200,
			'inline'      => true,
			'default'     => 54,
			'required'    => [ 'dotsEnable', '=', true ],
			'description' => esc_html__( 'Measured from the edge of the section, not the content column.', 'pfh-widgets' ),
		];

		$this->controls['dotsProgress'] = [
			'tab'         => 'content',
			'group'       => 'nav',
			'label'       => esc_html__( 'Show autoplay progress on the active dot', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'required'    => [ [ 'dotsEnable', '=', true ], [ 'autoplay', '=', true ] ],
		];

		$this->controls['dotSize'] = [
			'tab'      => 'content',
			'group'    => 'nav',
			'label'    => esc_html__( 'Dot size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 4,
			'max'      => 30,
			'inline'   => true,
			'default'  => 8,
			'required' => [ 'dotsEnable', '=', true ],
		];

		$this->controls['dotGap'] = [
			'tab'      => 'content',
			'group'    => 'nav',
			'label'    => esc_html__( 'Gap between dots (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 4,
			'max'      => 60,
			'inline'   => true,
			'default'  => 20,
			'required' => [ 'dotsEnable', '=', true ],
		];

		$this->controls['dotColor'] = [
			'tab'      => 'content',
			'group'    => 'nav',
			'label'    => esc_html__( 'Dot colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(124, 174, 178, 0.45)' ],
			'required' => [ 'dotsEnable', '=', true ],
		];

		$this->controls['dotActiveColor'] = [
			'tab'      => 'content',
			'group'    => 'nav',
			'label'    => esc_html__( 'Active dot colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#7caeb2' ],
			'required' => [ 'dotsEnable', '=', true ],
		];

		$this->controls['arrowsEnable'] = [
			'tab'     => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Show previous / next arrows', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => false,
		];

		$this->controls['arrowColor'] = [
			'tab'      => 'content',
			'group'    => 'nav',
			'label'    => esc_html__( 'Arrow colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#51604f' ],
			'required' => [ 'arrowsEnable', '=', true ],
		];

		$this->controls['arrowBg'] = [
			'tab'      => 'content',
			'group'    => 'nav',
			'label'    => esc_html__( 'Arrow background', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(255, 255, 255, 0.86)' ],
			'required' => [ 'arrowsEnable', '=', true ],
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

	private function uid() {
		if ( null === $this->uid ) {
			$this->uid = ! empty( $this->element['id'] ) ? sanitize_html_class( $this->element['id'] ) : uniqid( 'pfhh' );
		}

		return $this->uid;
	}

	/**
	 * CSS filter value for a cutout mode.
	 *
	 * @param string $mode One of '', 'black', 'white', 'screen'.
	 * @return array{filter:string, blend:string}
	 */
	private function cutout( $mode ) {
		$out = [
			'filter' => '',
			'blend'  => '',
		];

		if ( 'black' === $mode ) {
			$out['filter'] = 'url(#pfh-cut-black-' . $this->uid() . ')';
		} elseif ( 'white' === $mode ) {
			$out['filter'] = 'url(#pfh-cut-white-' . $this->uid() . ')';
		} elseif ( 'screen' === $mode ) {
			$out['blend'] = 'screen';
		}

		return $out;
	}

	/**
	 * Build the style attribute for an image that may need a cutout filter.
	 *
	 * @param string $mode  Cutout mode.
	 * @param array  $extra Extra CSS custom properties.
	 * @return string
	 */
	private function image_style( $mode, $extra = [] ) {
		$cut  = $this->cutout( $mode );
		$vars = array_merge(
			[
				'--pfh-cut'   => $cut['filter'] ? $cut['filter'] : 'none',
				'--pfh-blend' => $cut['blend'] ? $cut['blend'] : 'normal',
			],
			$extra
		);

		return PFH_Widgets_Helpers::css_vars( $vars );
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$slides = $this->slides();

		if ( empty( $slides ) ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-hero pfh-hero--empty"><p>' . esc_html__( 'Add at least one slide.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$classes = [
			'pfh-hero',
			'pfh-scope',
			'pfh-reveal-' . (string) $this->get( 'revealEffect', 'up' ),
			'pfh-media-' . (string) $this->get( 'mediaEffect', 'zoom' ),
		];

		if ( 'container' !== (string) $this->get( 'decorAnchor', 'full' ) ) {
			$classes[] = 'decor-full';
		}

		if ( $this->is_on( 'mediaFirst', false ) ) {
			$classes[] = 'is-media-first';
		}

		if ( $this->is_on( 'floatAnimate' ) ) {
			$classes[] = 'has-float-anim';
		}

		if ( $this->is_on( 'mediaShadow' ) ) {
			$classes[] = 'has-glow';
		}

		if ( count( $slides ) < 2 ) {
			$classes[] = 'is-single';
		}

		if ( $this->is_on( 'marqueeEnable' ) ) {
			$classes[] = 'has-marquee';
		}

		$classes[] = 'pfh-dots-' . (string) $this->get( 'dotsPosition', 'right' );

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );
		$this->set_attribute( '_root', 'data-pfh-hero', $this->uid() );
		$this->set_attribute( '_root', 'data-pfh-config', wp_json_encode( $this->js_config( count( $slides ) ) ) );
		$this->set_attribute( '_root', 'role', 'region' );
		$this->set_attribute( '_root', 'aria-roledescription', esc_attr__( 'carousel', 'pfh-widgets' ) );
		$this->set_attribute( '_root', 'aria-label', esc_attr__( 'Hero slider', 'pfh-widgets' ) );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';

		$this->render_filters();
		$this->render_background();
		$this->render_decor();

		echo '<div class="pfh-hero__inner">';
		echo '<div class="pfh-hero__viewport" data-pfh-viewport>';

		foreach ( $slides as $index => $slide ) {
			$this->render_slide( $slide, $index, count( $slides ) );
		}

		echo '</div>';
		echo '</div>';

		// Dots sit against the section edge, not the content column.
		$this->render_dots( count( $slides ) );
		$this->render_arrows();
		$this->render_marquee();

		echo '<p class="pfh-sr-only" aria-live="polite" data-pfh-live></p>';
		echo '</section>';
	}

	/**
	 * The SVG filters that knock a flattened background out of a JPG cutout.
	 *
	 * Compositing a transparent PNG onto black leaves premultiplied colour, so
	 * boosting luminance into the alpha channel recovers a usable cutout.
	 */
	private function render_filters() {
		$strength = max( 2, (float) $this->get( 'cutoutStrength', 20 ) );
		$uid      = $this->uid();
		$s        = (string) round( $strength, 2 );
		?>
		<svg class="pfh-hero__filters" width="0" height="0" aria-hidden="true" focusable="false">
			<filter id="pfh-cut-black-<?php echo esc_attr( $uid ); ?>" color-interpolation-filters="sRGB">
				<feColorMatrix type="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  <?php echo esc_attr( "$s $s $s" ); ?> 0 0" />
			</filter>
			<filter id="pfh-cut-white-<?php echo esc_attr( $uid ); ?>" color-interpolation-filters="sRGB">
				<feColorMatrix type="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  <?php echo esc_attr( "-$s -$s -$s" ); ?> 0 <?php echo esc_attr( $s ); ?>" />
			</filter>
		</svg>
		<?php
	}

	private function render_background() {
		$image = PFH_Widgets_Helpers::image_url( $this->get( 'bgImage' ), 'full' );

		echo '<div class="pfh-hero__bg" data-pfh-bg aria-hidden="true"';

		if ( $image ) {
			echo ' style="' . esc_attr( PFH_Widgets_Helpers::css_vars( [ '--pfh-hero-bg-img' => 'url(' . $image . ')' ] ) ) . '"';
		}

		echo '></div>';

		if ( PFH_Widgets_Helpers::color( $this->get( 'bgOverlay' ) ) ) {
			echo '<div class="pfh-hero__overlay" aria-hidden="true"></div>';
		}
	}

	private function render_decor() {
		$items = [];

		foreach ( [ 1, 2 ] as $i ) {
			$url = PFH_Widgets_Helpers::image_url( $this->get( "decor{$i}Image" ), 'large' );

			if ( ! $url ) {
				continue;
			}

			$items[] = [
				'url'    => $url,
				'style'  => $this->image_style(
					(string) $this->get( "decor{$i}Cutout", '' ),
					array_merge(
						[
							'--pfh-x' => PFH_Widgets_Helpers::unit( $this->get( "decor{$i}X", 0 ), '%' ),
							'--pfh-y' => PFH_Widgets_Helpers::unit( $this->get( "decor{$i}Y", 0 ), '%' ),
							'--pfh-w' => $this->decor_width( $this->get( "decor{$i}W", 160 ) ),
							'--pfh-o' => (float) $this->get( "decor{$i}Opacity", 100 ) / 100,
							'--pfh-d' => ( $i * 0.9 ) . 's',
						],
						$this->anchor_vars(
							$this->get( "decor{$i}Anchor", 1 === $i ? 'bottom-left' : 'bottom-right' ),
							$this->get( "decor{$i}DX", 1 === $i ? -40 : -30 ),
							$this->get( "decor{$i}DY", 0 )
						)
					)
				),
				'hide'   => $this->is_on( "decor{$i}Hide" ),
				'depth'  => $i,
			];
		}

		if ( empty( $items ) ) {
			return;
		}

		echo '<div class="pfh-hero__decor" aria-hidden="true">';

		foreach ( $items as $item ) {
			printf(
				'<img class="pfh-hero__decor-img%s" src="%s" alt="" loading="lazy" decoding="async" data-pfh-depth="%d" style="%s" />',
				$item['hide'] ? ' is-hidden-mobile' : '',
				esc_url( $item['url'] ),
				(int) $item['depth'],
				esc_attr( $item['style'] )
			);
		}

		echo '</div>';
	}

	/**
	 * @param array $slide Normalised slide.
	 * @param int   $index Zero based index.
	 * @param int   $total Slide count.
	 */
	private function render_slide( $slide, $index, $total ) {
		$active = 0 === $index;
		$bg     = PFH_Widgets_Helpers::image_url( isset( $slide['bgImage'] ) ? $slide['bgImage'] : null, 'full' );
		$color  = PFH_Widgets_Helpers::color( isset( $slide['textColor'] ) ? $slide['textColor'] : null );

		$style = PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-slide-bg' => $bg ? 'url(' . $bg . ')' : '',
				'--pfh-slide-fg' => $color,
			]
		);

		printf(
			'<article class="pfh-hero__slide%s" data-pfh-slide="%d"%s role="group" aria-roledescription="%s" aria-label="%s"%s%s>',
			$active ? ' is-active' : '',
			(int) $index,
			$bg ? ' data-pfh-slide-bg="' . esc_url( $bg ) . '"' : '',
			esc_attr__( 'slide', 'pfh-widgets' ),
			esc_attr( sprintf( /* translators: 1: current slide, 2: total slides. */ __( '%1$d of %2$d', 'pfh-widgets' ), $index + 1, $total ) ),
			$active ? '' : ' aria-hidden="true"',
			$style ? ' style="' . esc_attr( $style ) . '"' : ''
		);

		echo '<div class="pfh-hero__col pfh-hero__col--text">';
		$this->render_slide_text( $slide );
		echo '</div>';

		echo '<div class="pfh-hero__col pfh-hero__col--media">';
		$this->render_slide_media( $slide );
		echo '</div>';

		echo '</article>';
	}

	/**
	 * @param array $slide Normalised slide.
	 */
	private function render_slide_text( $slide ) {
		$order = 0;

		echo '<div class="pfh-hero__content">';

		if ( $this->is_on( 'ratingEnable' ) ) {
			$rating = $this->rating_markup( $slide );

			if ( $rating ) {
				printf( '<div class="pfh-hero__rating" data-pfh-reveal style="--pfh-i:%d">%s</div>', $order++, $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped.
			}
		}

		if ( ! empty( $slide['eyebrow'] ) ) {
			printf(
				'<p class="pfh-hero__eyebrow" data-pfh-reveal style="--pfh-i:%d">%s</p>',
				$order++,
				esc_html( PFH_Widgets_Helpers::dd( $slide['eyebrow'] ) )
			);
		}

		if ( ! empty( $slide['title'] ) ) {
			printf(
				'<h2 class="pfh-hero__title" data-pfh-reveal style="--pfh-i:%d">%s</h2>',
				$order++,
				wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $slide['title'] ) ) )
			);
		}

		if ( ! empty( $slide['text'] ) ) {
			printf(
				'<p class="pfh-hero__text" data-pfh-reveal style="--pfh-i:%d">%s</p>',
				$order++,
				wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $slide['text'] ) ) )
			);
		}

		$buttons = $this->buttons_markup( $slide );

		if ( $buttons ) {
			printf( '<div class="pfh-hero__actions" data-pfh-reveal style="--pfh-i:%d">%s</div>', $order++, $buttons ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped.
		}

		echo '</div>';
	}

	/**
	 * The figures behind this slide's rating row.
	 *
	 * The slide's own text is the fallback, so an unreachable feed leaves the
	 * banner exactly as it was typed rather than blank.
	 *
	 * @param array $slide Normalised slide.
	 * @return array
	 */
	private function rating_figures( $slide ) {
		$typed = isset( $slide['ratingText'] ) ? (string) $slide['ratingText'] : '';

		// Pull a number out of the typed label to seed the fallback score, so
		// the static stars still match the static text.
		$score = '';

		if ( preg_match( '/([0-9]+[.,][0-9]+)\s*$/', trim( $typed ), $m ) ) {
			$score = $m[1];
		}

		return PFH_Widgets_Reviews::figures(
			[
				'live'     => $this->is_on( 'ratingLive' ),
				'scale'    => (int) $this->get( 'ratingScale', 5 ),
				'score'    => $score,
				'count'    => 0,
				'round'    => (int) $this->get( 'ratingRound', 0 ),
			]
		);
	}

	/**
	 * Five stars filled to a score.
	 *
	 * A clipped overlay rather than per-star glyphs, so a partial star lands
	 * on the exact fraction instead of the nearest whole one.
	 *
	 * @param float $stars Score out of five.
	 * @return string
	 */
	private function stars_markup( $stars ) {
		$glyph = '';

		for ( $i = 0; $i < 5; $i++ ) {
			$glyph .= '<svg viewBox="0 0 20 20" focusable="false" aria-hidden="true">'
				. '<path d="M10 1.6l2.47 5.16 5.53.74-4.05 3.9 1.02 5.6L10 14.3l-4.97 2.7 1.02-5.6-4.05-3.9 5.53-.74z" fill="currentColor"/>'
				. '</svg>';
		}

		$pct = max( 0, min( 100, ( (float) $stars / 5 ) * 100 ) );

		return sprintf(
			'<span class="pfh-hero__stars is-live" role="img" aria-label="%1$s">'
				. '<span class="pfh-hero__stars-row">%2$s</span>'
				. '<span class="pfh-hero__stars-fill" style="width:%3$s%%"><span class="pfh-hero__stars-row">%2$s</span></span>'
				. '</span>',
			esc_attr(
				sprintf(
					/* translators: %s: star rating out of five. */
					__( '%s out of 5 stars', 'pfh-widgets' ),
					number_format_i18n( (float) $stars, 1 )
				)
			),
			$glyph,
			esc_attr( (string) round( $pct, 2 ) )
		);
	}

	/**
	 * @param array $slide Normalised slide.
	 * @return string
	 */
	private function rating_markup( $slide ) {
		$avatars = '';
		$list    = [];

		foreach ( PFH_Widgets_Helpers::parse_lines( isset( $slide['avatarList'] ) ? $slide['avatarList'] : '' ) as $row ) {
			if ( ! empty( $row[0] ) ) {
				$list[] = $row[0];
			}
		}

		if ( ! empty( $list ) ) {
			$avatars = '<span class="pfh-hero__avatars is-separate">';

			foreach ( $list as $url ) {
				$avatars .= '<img class="pfh-hero__avatar" src="' . esc_url( $url ) . '" alt="" loading="lazy" decoding="async" />';
			}

			$avatars .= '</span>';
		} else {
			$bulk = PFH_Widgets_Helpers::image_url( isset( $slide['avatarImage'] ) ? $slide['avatarImage'] : null, 'medium' );

			if ( $bulk ) {
				$avatars = sprintf(
					'<img class="pfh-hero__avatars" src="%s" alt="" loading="lazy" decoding="async" style="%s" />',
					esc_url( $bulk ),
					esc_attr( $this->image_style( (string) $this->get( 'avatarCutout', '' ) ) )
				);
			}
		}

		$figures = $this->rating_figures( $slide );

		if ( $this->is_on( 'starsLive' ) ) {
			$stars_tag = $this->stars_markup( $figures['stars'] );
		} else {
			$stars     = PFH_Widgets_Helpers::image_url( $this->get( 'starsImage' ), 'full' );
			$stars_tag = $stars
				? '<img class="pfh-hero__stars" src="' . esc_url( $stars ) . '" alt="" loading="lazy" decoding="async" />'
				: '';
		}

		$text = ! empty( $slide['ratingText'] ) ? PFH_Widgets_Helpers::dd( $slide['ratingText'] ) : '';
		$text = PFH_Widgets_Reviews::tokens( $text, $figures );

		$text_tag = $text ? '<span class="pfh-hero__rating-text">' . esc_html( $text ) . '</span>' : '';

		if ( ! $avatars && ! $stars_tag && ! $text_tag ) {
			return '';
		}

		$column = ( $stars_tag || $text_tag )
			? '<span class="pfh-hero__rating-col">' . $stars_tag . $text_tag . '</span>'
			: '';

		return $avatars . $column;
	}

	/**
	 * @param array $slide Normalised slide.
	 * @return string
	 */
	private function buttons_markup( $slide ) {
		$html  = '';
		$arrow = PFH_Widgets_Helpers::image_url( $this->get( 'btnArrow' ), 'full' );

		foreach ( [ 1, 2 ] as $i ) {
			$label = ! empty( $slide[ "btn{$i}Label" ] ) ? PFH_Widgets_Helpers::dd( $slide[ "btn{$i}Label" ] ) : '';

			if ( ! $label ) {
				continue;
			}

			$link  = PFH_Widgets_Helpers::link( isset( $slide[ "btn{$i}Link" ] ) ? $slide[ "btn{$i}Link" ] : null, '#' );
			$class = 1 === $i ? 'pfh-hero__btn pfh-hero__btn--primary' : 'pfh-hero__btn pfh-hero__btn--ghost';

			$html .= '<a class="' . esc_attr( $class ) . '"' . PFH_Widgets_Helpers::link_attrs( $link ) . '>';
			$html .= '<span>' . esc_html( $label ) . '</span>';

			if ( $arrow && ( 1 === $i || $this->is_on( 'btn2Arrow' ) ) ) {
				// Masked span rather than <img>, so the arrow always inherits the
				// button's own text colour instead of baking in the SVG's fill.
				$html .= '<span class="pfh-hero__btn-arrow" aria-hidden="true"></span>';
			}

			$html .= '</a>';
		}

		return $html;
	}

	/**
	 * @param array $slide Normalised slide.
	 */
	private function render_slide_media( $slide ) {
		$image = PFH_Widgets_Helpers::image_url( isset( $slide['image'] ) ? $slide['image'] : null, 'large' );
		$has   = $image || ! empty( $slide['float1Image'] ) || ! empty( $slide['float2Image'] );

		if ( ! $has ) {
			return;
		}

		echo '<div class="pfh-hero__media">';

		// Floats behind the product.
		$this->render_floats( $slide, false );

		if ( $image ) {
			$alt = ! empty( $slide['imageAlt'] ) ? $slide['imageAlt'] : '';

			printf(
				'<img class="pfh-hero__product" data-pfh-media src="%s" alt="%s" decoding="async" style="%s" />',
				esc_url( $image ),
				esc_attr( PFH_Widgets_Helpers::dd( $alt ) ),
				esc_attr(
					$this->image_style(
						(string) ( isset( $slide['imageCutout'] ) ? $slide['imageCutout'] : '' ),
						[ '--pfh-w' => PFH_Widgets_Helpers::unit( isset( $slide['imageWidth'] ) ? $slide['imageWidth'] : 100, '%' ) ]
					)
				)
			);
		}

		// Floats in front of the product.
		$this->render_floats( $slide, true );

		echo '</div>';
	}

	/**
	 * @param array $slide Normalised slide.
	 * @param bool  $front Render the in-front layer.
	 */
	private function render_floats( $slide, $front ) {
		foreach ( [ 1, 2 ] as $i ) {
			$url = PFH_Widgets_Helpers::image_url( isset( $slide[ "float{$i}Image" ] ) ? $slide[ "float{$i}Image" ] : null, 'large' );

			if ( ! $url ) {
				continue;
			}

			$is_front = ! empty( $slide[ "float{$i}Front" ] );

			if ( $is_front !== $front ) {
				continue;
			}

			printf(
				'<img class="pfh-hero__float pfh-hero__float--%d%s" data-pfh-reveal-float data-pfh-depth="%d" src="%s" alt="" loading="lazy" decoding="async" style="%s" />',
				$i,
				$front ? ' is-front' : '',
				$i + 1,
				esc_url( $url ),
				esc_attr(
					$this->image_style(
						(string) ( isset( $slide[ "float{$i}Cutout" ] ) ? $slide[ "float{$i}Cutout" ] : '' ),
						[
							'--pfh-x' => PFH_Widgets_Helpers::unit( isset( $slide[ "float{$i}X" ] ) ? $slide[ "float{$i}X" ] : 0, '%' ),
							'--pfh-y' => PFH_Widgets_Helpers::unit( isset( $slide[ "float{$i}Y" ] ) ? $slide[ "float{$i}Y" ] : 0, '%' ),
							'--pfh-w' => $this->decor_width( isset( $slide[ "float{$i}W" ] ) ? $slide[ "float{$i}W" ] : 180 ),
							'--pfh-d' => ( $i * 1.2 ) . 's',
						]
					)
				)
			);
		}
	}

	/**
	 * @param int $total Slide count.
	 */
	private function render_dots( $total ) {
		if ( ! $this->is_on( 'dotsEnable' ) || $total < 2 ) {
			return;
		}

		$progress = $this->is_on( 'dotsProgress' ) && $this->is_on( 'autoplay' );

		echo '<div class="pfh-hero__dots' . ( $progress ? ' has-progress' : '' ) . '" role="tablist" aria-label="' . esc_attr__( 'Slides', 'pfh-widgets' ) . '">';

		for ( $i = 0; $i < $total; $i++ ) {
			printf(
				'<button type="button" class="pfh-hero__dot%s" data-pfh-dot="%d" role="tab" aria-selected="%s" aria-label="%s"><span class="pfh-hero__dot-fill"></span></button>',
				0 === $i ? ' is-active' : '',
				$i,
				0 === $i ? 'true' : 'false',
				esc_attr( sprintf( /* translators: %d: slide number. */ __( 'Go to slide %d', 'pfh-widgets' ), $i + 1 ) )
			);
		}

		echo '</div>';
	}

	private function render_arrows() {
		if ( ! $this->is_on( 'arrowsEnable', false ) || count( $this->slides() ) < 2 ) {
			return;
		}

		echo '<div class="pfh-hero__arrows">';

		printf(
			'<button type="button" class="pfh-hero__arrow pfh-hero__arrow--prev" data-pfh-prev aria-label="%s">%s</button>',
			esc_attr__( 'Vorige slide', 'pfh-widgets' ),
			PFH_Widgets_Icons::get( 'arrow' )
		);

		printf(
			'<button type="button" class="pfh-hero__arrow pfh-hero__arrow--next" data-pfh-next aria-label="%s">%s</button>',
			esc_attr__( 'Volgende slide', 'pfh-widgets' ),
			PFH_Widgets_Icons::get( 'arrow' )
		);

		echo '</div>';
	}

	private function render_marquee() {
		if ( ! $this->is_on( 'marqueeEnable' ) ) {
			return;
		}

		$items = [];

		foreach ( PFH_Widgets_Helpers::parse_lines( (string) $this->get( 'marqueeItems', '' ) ) as $row ) {
			if ( ! empty( $row[0] ) ) {
				$items[] = PFH_Widgets_Helpers::dd( $row[0] );
			}
		}

		if ( empty( $items ) ) {
			return;
		}

		$separator = (string) $this->get( 'marqueeSeparator', '' );
		$classes   = [ 'pfh-hero__marquee', 'is-' . (string) $this->get( 'marqueeDirection', 'left' ) ];

		if ( $this->is_on( 'marqueePause' ) ) {
			$classes[] = 'is-pausable';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" aria-hidden="true">';
		echo '<div class="pfh-hero__marquee-track">';

		// Two identical groups let the animation loop seamlessly at -50%.
		for ( $copy = 0; $copy < 2; $copy++ ) {
			echo '<div class="pfh-hero__marquee-group">';

			foreach ( $items as $item ) {
				echo '<span class="pfh-hero__marquee-item">' . esc_html( $item ) . '</span>';

				if ( '' !== $separator ) {
					echo '<span class="pfh-hero__marquee-sep">' . esc_html( $separator ) . '</span>';
				}
			}

			echo '</div>';
		}

		echo '</div>';
		echo '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Data
	 * ------------------------------------------------------------------ */

	/**
	 * Slides that have something to show.
	 *
	 * @return array<int, array>
	 */
	private function slides() {
		if ( null !== $this->slides ) {
			return $this->slides;
		}

		$this->slides = [];

		foreach ( (array) $this->get( 'slides', [] ) as $slide ) {
			if ( ! is_array( $slide ) ) {
				continue;
			}

			$has_copy  = ! empty( $slide['title'] ) || ! empty( $slide['text'] ) || ! empty( $slide['eyebrow'] );
			$has_media = ! empty( $slide['image'] ) || ! empty( $slide['float1Image'] ) || ! empty( $slide['float2Image'] );

			if ( $has_copy || $has_media ) {
				$this->slides[] = $slide;
			}
		}

		return $this->slides;
	}

	/**
	 * Inline CSS custom properties consumed by the stylesheet.
	 *
	 * @return string
	 */
	/**
	 * Width for a floating decoration.
	 *
	 * Authored against the 1440 design frame. Left as a fixed px it shrinks
	 * away visually as the monitor grows, so by default it is emitted as a
	 * clamp that tracks the viewport and stops at a sane maximum.
	 *
	 * @param mixed $value Authored px width.
	 * @return string
	 */
	/**
	 * Edge offsets for a pinned decoration.
	 *
	 * Returns the inset properties as custom properties, leaving the opposite
	 * side `auto` so the artwork holds its corner at any width. An empty array
	 * means free positioning, where the percentage variables take over.
	 *
	 * @param string $anchor bottom-left | bottom-right | top-left | top-right | free.
	 * @param mixed  $dx     Offset from the left or right edge, px.
	 * @param mixed  $dy     Offset from the top or bottom edge, px.
	 * @return array<string, string>
	 */
	private function anchor_vars( $anchor, $dx, $dy ) {
		return PFH_Widgets_Helpers::edges( $anchor, $dx, $dy, '--pfh-' );
	}

	private function decor_width( $value ) {
		if ( ! $this->is_on( 'decorScale' ) ) {
			return PFH_Widgets_Helpers::unit( $value );
		}

		return PFH_Widgets_Helpers::fluid( $value, (float) $this->get( 'designFrame', 1440 ) );
	}

	private function build_vars() {
		$font       = trim( (string) $this->get( 'fontFamily', 'Outfit' ) );
		$title_font = trim( (string) $this->get( 'titleFamily', '' ) );
		$stack      = ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif';

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-font'          => $font ? $font . $stack : '',
				'--pfh-title-font'    => $title_font ? $title_font . $stack : 'var(--pfh-font)',
				'--pfh-container'     => PFH_Widgets_Helpers::unit( $this->get( 'containerWidth', 1140 ) ),
				'--pfh-gutter-set'        => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-hero-min-set'      => PFH_Widgets_Helpers::unit( $this->get( 'minHeight', 736 ) ),
				'--pfh-hero-min-m'    => PFH_Widgets_Helpers::unit( $this->get( 'minHeightMobile', 520 ) ),
				'--pfh-hero-pad-y-set'    => PFH_Widgets_Helpers::unit( $this->get( 'paddingY', 56 ) ),
				'--pfh-hero-bg'       => PFH_Widgets_Helpers::color( $this->get( 'bgColor' ), '#ffffff' ),
				'--pfh-hero-bg-size'  => $this->get( 'bgSize', 'cover' ),
				'--pfh-hero-bg-pos'   => $this->get( 'bgPosition', 'center center' ),
				'--pfh-hero-overlay'  => PFH_Widgets_Helpers::color( $this->get( 'bgOverlay' ), 'transparent' ),
				'--pfh-hero-radius'   => PFH_Widgets_Helpers::unit( $this->get( 'bottomRadius', 0 ) ),
				'--pfh-text-col'      => PFH_Widgets_Helpers::unit( $this->get( 'textRatio', 46 ), '%' ),
				'--pfh-col-gap'       => PFH_Widgets_Helpers::unit( $this->get( 'columnGap', 40 ) ),

				'--pfh-title-size-set'    => PFH_Widgets_Helpers::unit( $this->get( 'titleSize', 62 ) ),
				'--pfh-title-size-m'  => PFH_Widgets_Helpers::unit( $this->get( 'titleSizeMobile', 36 ) ),
				'--pfh-title-weight'  => $this->get( 'titleWeight', '400' ),
				'--pfh-title-lh'      => $this->get( 'titleLineHeight', 1.1 ),
				'--pfh-title-color'   => PFH_Widgets_Helpers::color( $this->get( 'titleColor' ), '#14181b' ),
				'--pfh-title-max'     => PFH_Widgets_Helpers::unit( $this->get( 'titleMaxWidth', 520 ) ),
				'--pfh-text-size'     => PFH_Widgets_Helpers::unit( $this->get( 'textSize', 18 ) ),
				'--pfh-text-weight'   => $this->get( 'textWeight', '300' ),
				'--pfh-text-lh'       => $this->get( 'textLineHeight', 1.4 ),
				'--pfh-text-color'    => PFH_Widgets_Helpers::color( $this->get( 'textColor' ), '#66758e' ),
				'--pfh-text-max'      => PFH_Widgets_Helpers::unit( $this->get( 'textMaxWidth', 412 ) ),
				'--pfh-gap-rt'        => PFH_Widgets_Helpers::unit( $this->get( 'gapRatingTitle', 26 ) ),
				'--pfh-gap-tt'        => PFH_Widgets_Helpers::unit( $this->get( 'gapTitleText', 20 ) ),
				'--pfh-gap-tb'        => PFH_Widgets_Helpers::unit( $this->get( 'gapTextButtons', 30 ) ),
				'--pfh-eyebrow-size'  => PFH_Widgets_Helpers::unit( $this->get( 'eyebrowSize', 14 ) ),
				'--pfh-eyebrow-color' => PFH_Widgets_Helpers::color( $this->get( 'eyebrowColor' ), '#6f8566' ),

				'--pfh-stars-w'       => PFH_Widgets_Helpers::unit( $this->get( 'starsWidth', 91 ) ),
				'--pfh-star-color'    => PFH_Widgets_Helpers::color( $this->get( 'starsColor' ) ),
				'--pfh-avatar-h'      => PFH_Widgets_Helpers::unit( $this->get( 'avatarHeight', 36 ) ),
				'--pfh-avatar-lap'    => PFH_Widgets_Helpers::unit( $this->get( 'avatarOverlap', 10 ) ),
				'--pfh-rating-gap'    => PFH_Widgets_Helpers::unit( $this->get( 'ratingGap', 16 ) ),
				'--pfh-rating-size'   => PFH_Widgets_Helpers::unit( $this->get( 'ratingSize', 14 ) ),
				'--pfh-rating-color'  => PFH_Widgets_Helpers::color( $this->get( 'ratingColor' ), '#000000' ),
				'--pfh-rating-font'   => ( $rating_font = trim( (string) $this->get( 'ratingFamily', 'Playfair Display' ) ) ) ? $rating_font . $stack : 'var(--pfh-font)',
				'--pfh-stars-gap'     => PFH_Widgets_Helpers::unit( $this->get( 'starsTextGap', 10 ) ),

				'--pfh-btn-bg'        => PFH_Widgets_Helpers::color( $this->get( 'btnBg' ), '#7caeb2' ),
				'--pfh-btn-color'     => PFH_Widgets_Helpers::color( $this->get( 'btnColor' ), '#ffffff' ),
				'--pfh-btn-hover'     => PFH_Widgets_Helpers::color( $this->get( 'btnHoverBg' ), '#6b9da1' ),
				'--pfh-btn-h-set'         => PFH_Widgets_Helpers::unit( $this->get( 'btnHeight', 48 ) ),
				'--pfh-btn-pad-set'       => PFH_Widgets_Helpers::unit( $this->get( 'btnPadding', 26 ) ),
				'--pfh-btn-radius'    => PFH_Widgets_Helpers::unit( $this->get( 'btnRadius', 70 ) ),
				'--pfh-btn-size'      => PFH_Widgets_Helpers::unit( $this->get( 'btnSize', 15 ) ),
				'--pfh-btn-arrow'     => PFH_Widgets_Helpers::unit( $this->get( 'btnArrowSize', 12 ) ),
				'--pfh-arrow-src'     => ( $arrow_src = PFH_Widgets_Helpers::image_url( $this->get( 'btnArrow' ), 'full' ) ) ? 'url(' . $arrow_src . ')' : 'none',
				'--pfh-btn-gap'       => PFH_Widgets_Helpers::unit( $this->get( 'btnGap', 10 ) ),
				'--pfh-btns-gap'      => PFH_Widgets_Helpers::unit( $this->get( 'btnsGap', 12 ) ),
				'--pfh-btn2-bg'       => PFH_Widgets_Helpers::color( $this->get( 'btn2Bg' ), '#ffffff' ),
				'--pfh-btn2-color'    => PFH_Widgets_Helpers::color( $this->get( 'btn2Color' ), '#5f6d46' ),
				'--pfh-btn2-border'   => PFH_Widgets_Helpers::color( $this->get( 'btn2Border' ), '#5f6d46' ),

				'--pfh-media-align'   => $this->get( 'mediaAlign', 'center' ),
				'--pfh-media-x'       => PFH_Widgets_Helpers::unit( $this->get( 'mediaOffsetX', 0 ) ),
				'--pfh-media-y'       => PFH_Widgets_Helpers::unit( $this->get( 'mediaOffsetY', 0 ) ),
				'--pfh-glow'          => PFH_Widgets_Helpers::color( $this->get( 'mediaGlowColor' ), 'rgba(238,230,209,.85)' ),

				'--pfh-reveal-d'      => PFH_Widgets_Helpers::unit( $this->get( 'revealDistance', 26 ) ),
				'--pfh-reveal-t'      => PFH_Widgets_Helpers::unit( $this->get( 'revealDuration', 750 ), 'ms' ),
				'--pfh-stagger'       => PFH_Widgets_Helpers::unit( $this->get( 'revealStagger', 90 ), 'ms' ),
				'--pfh-crossfade'     => PFH_Widgets_Helpers::unit( $this->get( 'crossfade', 600 ), 'ms' ),

				'--pfh-dot-size'      => PFH_Widgets_Helpers::unit( $this->get( 'dotSize', 8 ) ),
				'--pfh-dot-gap'       => PFH_Widgets_Helpers::unit( $this->get( 'dotGap', 20 ) ),
				'--pfh-dot-color'     => PFH_Widgets_Helpers::color( $this->get( 'dotColor' ), 'rgba(124,174,178,.45)' ),
				'--pfh-dot-active'    => PFH_Widgets_Helpers::color( $this->get( 'dotActiveColor' ), '#7caeb2' ),
				'--pfh-dot-offset'    => PFH_Widgets_Helpers::unit( $this->get( 'dotsOffset', 54 ) ),

				'--pfh-mq-bg'         => PFH_Widgets_Helpers::color( $this->get( 'marqueeBg' ), '#7caeb2' ),
				'--pfh-mq-color'      => PFH_Widgets_Helpers::color( $this->get( 'marqueeColor' ), '#ffffff' ),
				'--pfh-mq-h'          => PFH_Widgets_Helpers::unit( $this->get( 'marqueeHeight', 40 ) ),
				'--pfh-mq-size'       => PFH_Widgets_Helpers::unit( $this->get( 'marqueeSize', 16 ) ),
				'--pfh-mq-weight'     => $this->get( 'marqueeWeight', '400' ),
				'--pfh-mq-gap'        => PFH_Widgets_Helpers::unit( $this->get( 'marqueeGap', 34 ) ),
				'--pfh-mq-ls'         => PFH_Widgets_Helpers::unit( $this->get( 'marqueeLetterSpacing', 0.02 ), 'em' ),
				'--pfh-mq-time'       => PFH_Widgets_Helpers::unit( $this->get( 'marqueeSpeed', 40 ), 's' ),
				'--pfh-arrow-color'   => PFH_Widgets_Helpers::color( $this->get( 'arrowColor' ), '#51604f' ),
				'--pfh-arrow-bg'      => PFH_Widgets_Helpers::color( $this->get( 'arrowBg' ), 'rgba(255,255,255,.86)' ),
			]
		);
	}

	/**
	 * @param int $total Slide count.
	 * @return array
	 */
	private function js_config( $total ) {
		return [
			'uid'          => $this->uid(),
			'total'        => $total,
			'autoplay'     => $this->is_on( 'autoplay' ) && $total > 1,
			'interval'     => max( 2, (float) $this->get( 'interval', 6 ) ) * 1000,
			'pauseOnHover' => $this->is_on( 'pauseOnHover' ),
			'loop'         => $this->is_on( 'loop' ),
			'swipe'        => $this->is_on( 'swipe' ),
			'progress'     => $this->is_on( 'dotsProgress' ) && $this->is_on( 'autoplay' ),
			'parallax'     => $this->is_on( 'floatParallax', false ),
			'crossfade'    => (int) $this->get( 'crossfade', 600 ),
			'slideLabel'   => __( 'Slide %1$d of %2$d', 'pfh-widgets' ),
		];
	}
}
