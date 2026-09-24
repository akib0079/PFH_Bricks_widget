<?php
/**
 * Bricks element: Products For Home footer.
 *
 * Link columns (WP menus or manual lists) + newsletter block, brand row with
 * social icons, and a bottom bar with the review badge and copyright.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Footer extends \Bricks\Element {

	use PFH_Element_Defaults;

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
	public $name         = 'pfh-footer';
	public $icon         = 'ti-layout-footer';
	public $css_selector = '.pfh-footer';
	public $scripts      = [ 'pfhFooterInit' ];

	/**
	 * Default asset URLs supplied by the client.
	 */
	const LOGO_URL      = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Group-20640.jpg';
	const REVIEWS_URL   = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/image-144.svg';
	const FACEBOOK_URL  = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/facebook-circle-fill.svg';
	const INSTAGRAM_URL = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/instagram-line.svg';

	public function get_label() {
		return esc_html__( 'PFH Footer', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'footer', 'newsletter', 'social', 'columns', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::footer();
	}

	public function set_control_groups() {
		$this->control_groups['layout']     = [ 'title' => esc_html__( 'Layout', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['columns']    = [ 'title' => esc_html__( 'Link columns', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['newsletter'] = [ 'title' => esc_html__( 'Nieuwsbrief', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['brand']      = [ 'title' => esc_html__( 'Brand & social', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['bottom']     = [ 'title' => esc_html__( 'Reviews & copyright', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->layout_controls();
		$this->column_controls();
		$this->newsletter_controls();
		$this->brand_controls();
		$this->bottom_controls();
	}

	/* ---------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	private function layout_controls() {
		$this->controls['containerWidth'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Content width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 600,
			'max'     => 1920,
			'inline'  => true,
			'default' => 1140,
			'description' => esc_html__( 'Width of the content column. The Figma frame is 1440px wide with ~165px margins, which is 1110px of content.', 'pfh-widgets' ),
		];

		$this->controls['containerPadding'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Side padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 24,
		];

		$this->controls['bgColor'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#51604f' ],
		];

		$this->controls['textColor'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['bgImage'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background image', 'pfh-widgets' ),
			'type'    => 'image',
		];

		$this->controls['paddingTop'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Padding top (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'inline'  => true,
			'default' => 120,
		];

		$this->controls['paddingBottom'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Padding bottom (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'inline'  => true,
			'default' => 60,
		];

		$this->controls['topRadius'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Top corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 0,
		];

		$this->controls['fontFamily'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Font family', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Outfit',
		];
	}

	private function column_controls() {
		$this->controls['columns'] = [
			'tab'   => 'content',
			'group'         => 'columns',
			'label'         => esc_html__( 'Columns', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'heading',
			'default'       => $this->default_columns(),
			'fields'        => [
				'heading' => [
					'label' => esc_html__( 'Heading', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'source'  => [
					'label'   => esc_html__( 'Links from', 'pfh-widgets' ),
					'type'    => 'select',
					'inline'  => true,
					'options' => [
						'menu'   => esc_html__( 'WordPress menu', 'pfh-widgets' ),
						'manual' => esc_html__( 'Manual list', 'pfh-widgets' ),
					],
					'default' => 'manual',
				],
				'menu'    => [
					'label'       => esc_html__( 'Menu', 'pfh-widgets' ),
					'type'        => 'select',
					'searchable'  => true,
					'options'     => PFH_Widgets_Helpers::menu_options(),
					'placeholder' => esc_html__( 'Select a menu', 'pfh-widgets' ),
					'description' => esc_html__( 'Manage these links under Appearance → Menus.', 'pfh-widgets' ),
					'required'    => [ 'source', '=', 'menu' ],
				],
				'width'   => [
					'label'       => esc_html__( 'Column width (px)', 'pfh-widgets' ),
					'type'        => 'number',
					'min'         => 60,
					'max'         => 600,
					'inline'      => true,
					'placeholder' => esc_html__( 'Auto', 'pfh-widgets' ),
					'description' => esc_html__( 'Leave empty to size to the content, which is what the Figma layout does.', 'pfh-widgets' ),
				],
				'links'   => [
					'label'       => esc_html__( 'Links', 'pfh-widgets' ),
					'type'        => 'textarea',
					'placeholder' => "Honing | /product-categorie/honing",
					'description' => esc_html__( 'One link per line: Label | URL', 'pfh-widgets' ),
					'required'    => [ 'source', '=', 'manual' ],
				],
			],
		];

		$this->controls['columnsLayout'] = [
			'tab'   => 'content',
			'group'       => 'columns',
			'label'       => esc_html__( 'Column spacing', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'natural' => esc_html__( 'Fixed gap, newsletter right (Figma)', 'pfh-widgets' ),
				'spread'  => esc_html__( 'Spread evenly across the row', 'pfh-widgets' ),
			],
			'default'     => 'natural',
			'description' => esc_html__( 'The Figma layout sizes each column to its content and separates them by a fixed gap.', 'pfh-widgets' ),
		];

		$this->controls['columnsGap'] = [
			'tab'   => 'content',
			'group'   => 'columns',
			'label'   => esc_html__( 'Gap between columns (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 160,
			'inline'  => true,
			'default' => 50,
		];

		$this->controls['headingSize'] = [
			'tab'   => 'content',
			'group'   => 'columns',
			'label'   => esc_html__( 'Heading size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 34,
			'inline'  => true,
			'default' => 18,
		];

		$this->controls['headingWeight'] = [
			'tab'   => 'content',
			'group'   => 'columns',
			'label'   => esc_html__( 'Heading weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '500',
		];

		$this->controls['headingLineHeight'] = [
			'tab'   => 'content',
			'group'   => 'columns',
			'label'   => esc_html__( 'Heading line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0.8,
			'max'     => 3,
			'step'    => 0.05,
			'inline'  => true,
			'default' => 1.05,
		];

		$this->controls['headingGap'] = [
			'tab'   => 'content',
			'group'   => 'columns',
			'label'   => esc_html__( 'Space under heading (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['linkSize'] = [
			'tab'   => 'content',
			'group'   => 'columns',
			'label'   => esc_html__( 'Link size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['linkLineHeight'] = [
			'tab'   => 'content',
			'group'       => 'columns',
			'label'       => esc_html__( 'Link line height', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0.8,
			'max'         => 3.5,
			'step'        => 0.05,
			'inline'      => true,
			'default'     => 2.1,
			'description' => esc_html__( 'Figma: 210%. This is what spaces the links apart.', 'pfh-widgets' ),
		];

		$this->controls['linkGap'] = [
			'tab'   => 'content',
			'group'   => 'columns',
			'label'   => esc_html__( 'Extra space between links (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 48,
			'inline'  => true,
			'default' => 0,
			'description' => esc_html__( 'On top of the line height. The Figma spacing comes from the 210% line height alone.', 'pfh-widgets' ),
		];

		$this->controls['linkColor'] = [
			'tab'   => 'content',
			'group'   => 'columns',
			'label'   => esc_html__( 'Link colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'rgb' => 'rgba(255,255,255,.88)' ],
		];

		$this->controls['linkHoverColor'] = [
			'tab'   => 'content',
			'group'   => 'columns',
			'label'   => esc_html__( 'Link colour (hover)', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];
	}

	private function newsletter_controls() {
		$this->controls['newsletterEnable'] = [
			'tab'   => 'content',
			'group'   => 'newsletter',
			'label'   => esc_html__( 'Show newsletter block', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['newsletterHeading'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'     => 'text',
			'default'  => 'Nieuwsbrief',
			'required' => [ 'newsletterEnable', '=', true ],
		];

		$this->controls['newsletterMode'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Form type', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => [
				'builtin'   => esc_html__( 'Built-in styled form', 'pfh-widgets' ),
				'shortcode' => esc_html__( 'Shortcode (Mailchimp, Fluent Forms…)', 'pfh-widgets' ),
			],
			'default'  => 'builtin',
			'required' => [ 'newsletterEnable', '=', true ],
		];

		$this->controls['newsletterShortcode'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Shortcode', 'pfh-widgets' ),
			'type'     => 'textarea',
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'shortcode' ] ],
		];

		$this->controls['newsletterAction'] = [
			'tab'   => 'content',
			'group'       => 'newsletter',
			'label'       => esc_html__( 'Form action URL', 'pfh-widgets' ),
			'type'        => 'text',
			'placeholder' => 'https://…list-manage.com/subscribe/post',
			'description' => esc_html__( 'Where the email is posted. Leave empty to post to the current page.', 'pfh-widgets' ),
			'required'    => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];

		$this->controls['newsletterField'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Email field name', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'E-MAIL',
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];

		$this->controls['newsletterPlaceholder'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Placeholder', 'pfh-widgets' ),
			'type'     => 'text',
			'default'  => 'Je e-mailadres',
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];

		$this->controls['newsletterButton'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Button text', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Aanmelden',
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];

		$this->controls['newsletterWidth'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Block width (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 200,
			'max'      => 640,
			'inline'   => true,
			'default'  => 348,
			'required' => [ 'newsletterEnable', '=', true ],
		];

		$this->controls['newsletterFieldHeight'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Field height (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 32,
			'max'      => 80,
			'inline'   => true,
			'default'  => 42,
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];

		$this->controls['newsletterRadius'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 40,
			'inline'   => true,
			'default'  => 12,
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];

		$this->controls['newsletterBorder'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Field border colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(255,255,255,.85)' ],
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];

		$this->controls['newsletterBtnBg'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Button background', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#a9bfa5' ],
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];

		$this->controls['newsletterBtnSize'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Button font size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 10,
			'max'      => 24,
			'inline'   => true,
			'default'  => 14,
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];

		$this->controls['newsletterBtnPadding'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Button side padding (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 80,
			'inline'   => true,
			'default'  => 24,
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];

		$this->controls['newsletterBtnColor'] = [
			'tab'   => 'content',
			'group'    => 'newsletter',
			'label'    => esc_html__( 'Button text colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#51604f' ],
			'required' => [ [ 'newsletterEnable', '=', true ], [ 'newsletterMode', '=', 'builtin' ] ],
		];
	}

	private function brand_controls() {
		$this->controls['brandLogo'] = [
			'tab'   => 'content',
			'group'       => 'brand',
			'label'       => esc_html__( 'Footer logo', 'pfh-widgets' ),
			'type'        => 'image',
			'default'     => [ 'url' => self::LOGO_URL ],
			'description' => esc_html__( 'Use the white version of the logo.', 'pfh-widgets' ),
		];

		$this->controls['brandLogoBlend'] = [
			'tab'   => 'content',
			'group'       => 'brand',
			'label'       => esc_html__( 'Logo background', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'screen'   => esc_html__( 'Drop a black background', 'pfh-widgets' ),
				'multiply' => esc_html__( 'Drop a white background', 'pfh-widgets' ),
				'normal'   => esc_html__( 'Keep as uploaded', 'pfh-widgets' ),
			],
			'default'     => 'screen',
			'description' => esc_html__( 'The supplied JPG is white artwork on solid black, so it is blended to hide the black box. Switch to "Keep as uploaded" once a transparent PNG/SVG is used.', 'pfh-widgets' ),
		];

		$this->controls['brandLogoWidth'] = [
			'tab'   => 'content',
			'group'   => 'brand',
			'label'   => esc_html__( 'Logo width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 60,
			'max'     => 480,
			'inline'  => true,
			'default' => 203,
		];

		$this->controls['brandLogoLink'] = [
			'tab'   => 'content',
			'group' => 'brand',
			'label' => esc_html__( 'Logo link', 'pfh-widgets' ),
			'type'  => 'link',
		];

		$this->controls['brandGap'] = [
			'tab'   => 'content',
			'group'   => 'brand',
			'label'   => esc_html__( 'Space above brand row (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 50,
		];

		$this->controls['brandAlign'] = [
			'tab'   => 'content',
			'group'   => 'brand',
			'label'   => esc_html__( 'Row alignment', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'flex-end'   => esc_html__( 'Social icons at the logo baseline', 'pfh-widgets' ),
				'center'     => esc_html__( 'Vertically centred', 'pfh-widgets' ),
				'flex-start' => esc_html__( 'Aligned to the top', 'pfh-widgets' ),
			],
			'default' => 'flex-end',
		];

		$this->controls['socialItems'] = [
			'tab'   => 'content',
			'group'         => 'brand',
			'label'         => esc_html__( 'Social links', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'icon',
			'default'       => [
				[
					'icon'  => 'facebook',
					'image' => [ 'url' => self::FACEBOOK_URL ],
				],
				[
					'icon'  => 'instagram',
					'image' => [ 'url' => self::INSTAGRAM_URL ],
				],
			],
			'fields'        => [
				'icon'  => [
					'label'       => esc_html__( 'Icon', 'pfh-widgets' ),
					'type'        => 'select',
					'inline'      => true,
					'options'     => PFH_Widgets_Icons::social_options(),
					'default'     => 'facebook',
					'description' => esc_html__( 'Used when no image is uploaded below.', 'pfh-widgets' ),
				],
				'image' => [
					'label' => esc_html__( 'Icon image', 'pfh-widgets' ),
					'type'  => 'image',
				],
				'link'  => [
					'label' => esc_html__( 'Link', 'pfh-widgets' ),
					'type'  => 'link',
				],
				'label' => [
					'label'       => esc_html__( 'Accessible label', 'pfh-widgets' ),
					'type'        => 'text',
					'inline'      => true,
					'placeholder' => esc_html__( 'Defaults to the icon name', 'pfh-widgets' ),
				],
			],
		];

		$this->controls['socialRecolor'] = [
			'tab'   => 'content',
			'group'       => 'brand',
			'label'       => esc_html__( 'Recolour uploaded icons', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Turn on to force uploaded SVGs to the colour below. The supplied icons are already white, so this is off by default.', 'pfh-widgets' ),
		];

		$this->controls['socialColor'] = [
			'tab'   => 'content',
			'group'   => 'brand',
			'label'   => esc_html__( 'Icon colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['socialSize'] = [
			'tab'   => 'content',
			'group'   => 'brand',
			'label'   => esc_html__( 'Social icon size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 48,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['socialGap'] = [
			'tab'   => 'content',
			'group'   => 'brand',
			'label'   => esc_html__( 'Social icon gap (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 4,
			'max'     => 48,
			'inline'  => true,
			'default' => 16,
		];
	}

	private function bottom_controls() {
		$this->controls['reviewsEnable'] = [
			'tab'   => 'content',
			'group'   => 'bottom',
			'label'   => esc_html__( 'Show review badge', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['reviewsLive'] = [
			'tab'         => 'content',
			'group'       => 'bottom',
			'label'       => esc_html__( 'Use the live WebwinkelKeur rating', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Fills the score and the count from the shop summary. The values below are what shows if the feed is unreachable, and in the builder.', 'pfh-widgets' ),
			'required'    => [ 'reviewsEnable', '=', true ],
		];

		$this->controls['reviewsLabel'] = [
			'tab'   => 'content',
			'group'    => 'bottom',
			'label'    => esc_html__( 'Label', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Uitstekend',
			'required' => [ 'reviewsEnable', '=', true ],
		];

		$this->controls['reviewsScore'] = [
			'tab'   => 'content',
			'group'    => 'bottom',
			'label'    => esc_html__( 'Score', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => '9.7',
			'required' => [ 'reviewsEnable', '=', true ],
		];

		$this->controls['reviewsText'] = [
			'tab'   => 'content',
			'group'    => 'bottom',
			'label'       => esc_html__( 'Count text', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => '%s reviews op',
			'description' => esc_html__( '%s is replaced with the live review count. If you leave a plain number in here instead, that number is replaced — so "270 reviews on" becomes the real count.', 'pfh-widgets' ),
			'required'    => [ 'reviewsEnable', '=', true ],
		];

		$this->controls['reviewsCount'] = [
			'tab'         => 'content',
			'group'       => 'bottom',
			'label'       => esc_html__( 'Review count', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'inline'      => true,
			'default'     => 270,
			'description' => esc_html__( 'Used when the live rating is off or unavailable.', 'pfh-widgets' ),
			'required'    => [ 'reviewsEnable', '=', true ],
		];

		$this->controls['reviewsLogo'] = [
			'tab'   => 'content',
			'group'    => 'bottom',
			'label'    => esc_html__( 'Provider logo', 'pfh-widgets' ),
			'type'     => 'image',
			'default'  => [ 'url' => self::REVIEWS_URL ],
			'required' => [ 'reviewsEnable', '=', true ],
		];

		$this->controls['reviewsLogoWidth'] = [
			'tab'   => 'content',
			'group'    => 'bottom',
			'label'    => esc_html__( 'Provider logo width (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 40,
			'max'      => 320,
			'inline'   => true,
			'default'  => 126,
			'required' => [ 'reviewsEnable', '=', true ],
		];

		$this->controls['reviewsLink'] = [
			'tab'   => 'content',
			'group'    => 'bottom',
			'label'    => esc_html__( 'Review page link', 'pfh-widgets' ),
			'type'     => 'link',
			'required' => [ 'reviewsEnable', '=', true ],
		];

		$this->controls['copyright'] = [
			'tab'   => 'content',
			'group'       => 'bottom',
			'label'       => esc_html__( 'Copyright', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'COPYRIGHT © {year} ALLE RECHTEN VOORBEHOUDEN',
			'description' => esc_html__( 'Use {year} for the current year.', 'pfh-widgets' ),
		];

		$this->controls['bottomSize'] = [
			'tab'   => 'content',
			'group'   => 'bottom',
			'label'   => esc_html__( 'Bottom row font size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 9,
			'max'     => 22,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['bottomLetterSpacing'] = [
			'tab'   => 'content',
			'group'   => 'bottom',
			'label'   => esc_html__( 'Copyright letter spacing (em)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => -0.05,
			'max'     => 0.3,
			'step'    => 0.005,
			'inline'  => true,
			'default' => 0,
		];

		$this->controls['bottomGap'] = [
			'tab'   => 'content',
			'group'   => 'bottom',
			'label'   => esc_html__( 'Space above bottom row (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 160,
			'inline'  => true,
			'default' => 32,
		];

		$this->controls['dividerEnable'] = [
			'tab'   => 'content',
			'group'   => 'bottom',
			'label'   => esc_html__( 'Show divider above bottom row', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => false,
		];

		$this->controls['dividerColor'] = [
			'tab'   => 'content',
			'group'    => 'bottom',
			'label'    => esc_html__( 'Divider colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(255,255,255,.18)' ],
			'required' => [ 'dividerEnable', '=', true ],
		];
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

	private function default_columns() {
		return [
			[
				'heading' => 'Categorieën',
				'source'  => 'manual',
				'links'   => "Gia Giamas limonade | #\nHoning | #\nOlijfolie | #\nBijenwas | #\nAanbiedingen en bundels | #",
			],
			[
				'heading' => 'Snelle links',
				'source'  => 'manual',
				'links'   => "Home | #\nShop | #\nOver ons | #\nBlog | #\nContact | #",
			],
			[
				'heading' => 'Handige links',
				'source'  => 'manual',
				'links'   => "Winkelwagen | #\nAfrekenen | #\nMijn account | #",
			],
			[
				'heading' => 'Klantenservice',
				'source'  => 'manual',
				'links'   => "Algemene voorwaarden | #\nBetalen en bezorgen | #\nPrivacybeleid | #\nRetourneren of defecten | #\nKlachten | #",
			],
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
			$this->uid = ! empty( $this->element['id'] ) ? sanitize_html_class( $this->element['id'] ) : uniqid( 'pfhf' );
		}

		return $this->uid;
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$this->set_attribute( '_root', 'class', [ 'pfh-footer', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<footer ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-footer__inner">';

		$this->render_top();
		$this->render_brand();
		$this->render_bottom();

		echo '</div>';
		echo '</footer>';
	}

	private function build_vars() {
		$font  = trim( (string) $this->get( 'fontFamily', 'Outfit' ) );
		$image = PFH_Widgets_Helpers::image_url( $this->get( 'bgImage' ), 'full' );

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-font'         => $font ? $font . ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif' : '',
				'--pfh-container'    => PFH_Widgets_Helpers::unit( $this->get( 'containerWidth', 1140 ) ),
				'--pfh-gutter-set'       => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-f-bg'         => PFH_Widgets_Helpers::color( $this->get( 'bgColor' ), '#51604f' ),
				'--pfh-f-image'      => $image ? 'url(' . $image . ')' : 'none',
				'--pfh-f-color'      => PFH_Widgets_Helpers::color( $this->get( 'textColor' ), '#ffffff' ),
				'--pfh-f-pad-top-set'    => PFH_Widgets_Helpers::unit( $this->get( 'paddingTop', 96 ) ),
				'--pfh-f-pad-bottom-set' => PFH_Widgets_Helpers::unit( $this->get( 'paddingBottom', 44 ) ),
				'--pfh-f-radius'     => PFH_Widgets_Helpers::unit( $this->get( 'topRadius', 0 ) ),

				'--pfh-col-gap'      => PFH_Widgets_Helpers::unit( $this->get( 'columnsGap', 50 ) ),
				'--pfh-head-size'    => PFH_Widgets_Helpers::unit( $this->get( 'headingSize', 18 ) ),
				'--pfh-head-weight'  => $this->get( 'headingWeight', '500' ),
				'--pfh-head-lh'      => $this->get( 'headingLineHeight', 1.05 ),
				'--pfh-head-gap'     => PFH_Widgets_Helpers::unit( $this->get( 'headingGap', 14 ) ),
				'--pfh-link-size'    => PFH_Widgets_Helpers::unit( $this->get( 'linkSize', 14 ) ),
				'--pfh-link-lh-set'      => $this->get( 'linkLineHeight', 2.1 ),
				'--pfh-link-gap'     => PFH_Widgets_Helpers::unit( $this->get( 'linkGap', 0 ) ),
				'--pfh-link-color'   => PFH_Widgets_Helpers::color( $this->get( 'linkColor' ), 'rgba(255,255,255,.88)' ),
				'--pfh-link-hover'   => PFH_Widgets_Helpers::color( $this->get( 'linkHoverColor' ), '#ffffff' ),

				'--pfh-news-w'       => PFH_Widgets_Helpers::unit( $this->get( 'newsletterWidth', 348 ) ),
				'--pfh-news-h'       => PFH_Widgets_Helpers::unit( $this->get( 'newsletterFieldHeight', 42 ) ),
				'--pfh-news-radius'  => PFH_Widgets_Helpers::unit( $this->get( 'newsletterRadius', 12 ) ),
				'--pfh-news-border'  => PFH_Widgets_Helpers::color( $this->get( 'newsletterBorder' ), 'rgba(255,255,255,.85)' ),
				'--pfh-news-btn-bg'  => PFH_Widgets_Helpers::color( $this->get( 'newsletterBtnBg' ), '#a9bfa5' ),
				'--pfh-news-btn-c'   => PFH_Widgets_Helpers::color( $this->get( 'newsletterBtnColor' ), '#51604f' ),
				'--pfh-news-btn-size' => PFH_Widgets_Helpers::unit( $this->get( 'newsletterBtnSize', 14 ) ),
				'--pfh-news-btn-pad' => PFH_Widgets_Helpers::unit( $this->get( 'newsletterBtnPadding', 24 ) ),

				'--pfh-brand-w'      => PFH_Widgets_Helpers::unit( $this->get( 'brandLogoWidth', 203 ) ),
				'--pfh-brand-gap-set'    => PFH_Widgets_Helpers::unit( $this->get( 'brandGap', 50 ) ),
				'--pfh-brand-blend'  => $this->get( 'brandLogoBlend', 'screen' ),
				'--pfh-brand-align'  => $this->get( 'brandAlign', 'flex-end' ),
				'--pfh-social-size'  => PFH_Widgets_Helpers::unit( $this->get( 'socialSize', 20 ) ),
				'--pfh-social-gap'   => PFH_Widgets_Helpers::unit( $this->get( 'socialGap', 16 ) ),
				'--pfh-social-color' => PFH_Widgets_Helpers::color( $this->get( 'socialColor' ), '#ffffff' ),

				'--pfh-bottom-size'  => PFH_Widgets_Helpers::unit( $this->get( 'bottomSize', 14 ) ),
				'--pfh-bottom-gap-set'   => PFH_Widgets_Helpers::unit( $this->get( 'bottomGap', 32 ) ),
				'--pfh-bottom-ls'    => PFH_Widgets_Helpers::unit( $this->get( 'bottomLetterSpacing', 0 ), 'em' ),
				'--pfh-divider'      => PFH_Widgets_Helpers::color( $this->get( 'dividerColor' ), 'rgba(255,255,255,.18)' ),
				'--pfh-review-w'     => PFH_Widgets_Helpers::unit( $this->get( 'reviewsLogoWidth', 126 ) ),
			]
		);
	}

	/* ---------------------------------------------------------------------
	 * Partials
	 * ------------------------------------------------------------------ */

	private function render_top() {
		$columns    = (array) $this->get( 'columns', [] );
		$newsletter = $this->is_on( 'newsletterEnable' );

		if ( empty( $columns ) && ! $newsletter ) {
			return;
		}

		$layout = 'spread' === $this->get( 'columnsLayout', 'natural' ) ? 'is-spread' : 'is-natural';

		echo '<div class="pfh-footer__top ' . esc_attr( $layout ) . '">';

		if ( ! empty( $columns ) ) {
			echo '<div class="pfh-footer__cols">';

			foreach ( $columns as $column ) {
				$this->render_column( $column );
			}

			echo '</div>';
		}

		if ( $newsletter ) {
			$this->render_newsletter();
		}

		echo '</div>';
	}

	/**
	 * @param array $column Repeater row.
	 */
	private function render_column( $column ) {
		$heading = ! empty( $column['heading'] ) ? PFH_Widgets_Helpers::dd( $column['heading'] ) : '';
		$source  = isset( $column['source'] ) ? $column['source'] : 'manual';
		$links   = '';

		if ( 'menu' === $source ) {
			$links = PFH_Widgets_Helpers::render_menu_links(
				isset( $column['menu'] ) ? $column['menu'] : 0,
				'pfh-footer__links',
				'pfh-footer__link'
			);
		} else {
			$rows = PFH_Widgets_Helpers::parse_lines( isset( $column['links'] ) ? $column['links'] : '' );

			if ( ! empty( $rows ) ) {
				$links = '<ul class="pfh-footer__links">';

				foreach ( $rows as $row ) {
					if ( empty( $row[0] ) ) {
						continue;
					}

					$url = ! empty( $row[1] ) ? $row[1] : '#';

					$links .= '<li><a class="pfh-footer__link" href="' . esc_url( $url ) . '">' . esc_html( PFH_Widgets_Helpers::dd( $row[0] ) ) . '</a></li>';
				}

				$links .= '</ul>';
			}
		}

		if ( ! $heading && ! $links ) {
			return;
		}

		$width = ! empty( $column['width'] ) ? PFH_Widgets_Helpers::unit( $column['width'] ) : '';
		$style = $width ? ' style="' . esc_attr( PFH_Widgets_Helpers::css_vars( [ '--pfh-col-w' => $width ] ) ) . '"' : '';

		echo '<div class="pfh-footer__col"' . $style . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.

		if ( $heading ) {
			echo '<h3 class="pfh-footer__heading">' . esc_html( $heading ) . '</h3>';
		}

		echo $links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_url()/esc_html() above.
		echo '</div>';
	}

	private function render_newsletter() {
		$heading = $this->get( 'newsletterHeading', '' );
		$mode    = (string) $this->get( 'newsletterMode', 'builtin' );

		echo '<div class="pfh-footer__news">';

		if ( $heading ) {
			echo '<h3 class="pfh-footer__heading">' . esc_html( PFH_Widgets_Helpers::dd( $heading ) ) . '</h3>';
		}

		if ( 'shortcode' === $mode ) {
			$shortcode = (string) $this->get( 'newsletterShortcode', '' );

			if ( $shortcode ) {
				echo '<div class="pfh-footer__news-shortcode">' . do_shortcode( $shortcode ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
			}

			echo '</div>';

			return;
		}

		$action = (string) $this->get( 'newsletterAction', '' );
		$field  = (string) $this->get( 'newsletterField', 'E-MAIL' );
		$field  = preg_replace( '/[^A-Za-z0-9_\-\[\]]/', '', $field );
		$field  = $field ? $field : 'E-MAIL';
		$id     = 'pfh-news-' . $this->uid();

		// Only open a new tab when the form actually posts somewhere else.
		$target = $action ? ' target="_blank"' : '';
		?>
		<form class="pfh-footer__form" method="post"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static string. ?> action="<?php echo esc_url( $action ); ?>" data-pfh-newsletter>
			<label class="pfh-sr-only" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $this->get( 'newsletterPlaceholder', 'E-mail' ) ); ?></label>
			<input
				class="pfh-footer__input"
				id="<?php echo esc_attr( $id ); ?>"
				type="email"
				name="<?php echo esc_attr( $field ); ?>"
				required
				autocomplete="email"
				placeholder="<?php echo esc_attr( $this->get( 'newsletterPlaceholder', '' ) ); ?>"
			/>
			<button type="submit" class="pfh-footer__submit"><?php echo esc_html( $this->get( 'newsletterButton', 'Aanmelden' ) ); ?></button>
		</form>
		<?php
		echo '</div>';
	}

	private function render_brand() {
		$logo   = PFH_Widgets_Helpers::image_url( $this->get( 'brandLogo' ), 'medium_large' );
		$social = (array) $this->get( 'socialItems', [] );

		if ( ! $logo && empty( $social ) ) {
			return;
		}

		echo '<div class="pfh-footer__brand">';

		if ( $logo ) {
			$link = PFH_Widgets_Helpers::link( $this->get( 'brandLogoLink' ), home_url( '/' ) );

			printf(
				'<a class="pfh-footer__brand-link"%s><img class="pfh-footer__brand-img" src="%s" alt="%s" loading="lazy" decoding="async" /></a>',
				PFH_Widgets_Helpers::link_attrs( $link ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().
				esc_url( $logo ),
				esc_attr( get_bloginfo( 'name' ) )
			);
		}

		if ( ! empty( $social ) ) {
			$recolor = $this->is_on( 'socialRecolor', false );

			echo '<ul class="pfh-footer__social">';

			foreach ( $social as $item ) {
				$icon  = ! empty( $item['icon'] ) ? $item['icon'] : '';
				$image = PFH_Widgets_Helpers::image_url( isset( $item['image'] ) ? $item['image'] : null, 'full' );
				$svg   = $icon ? PFH_Widgets_Icons::get( $icon ) : '';

				if ( ! $image && ! $svg ) {
					continue;
				}

				$link  = PFH_Widgets_Helpers::link( isset( $item['link'] ) ? $item['link'] : null, '#' );
				$label = ! empty( $item['label'] ) ? $item['label'] : ucfirst( $icon );

				echo '<li><a class="pfh-footer__social-link"' . PFH_Widgets_Helpers::link_attrs( $link ) . ' aria-label="' . esc_attr( $label ) . '">';

				if ( $image && $recolor ) {
					// CSS mask keeps the icon colour controllable for any single-colour SVG.
					printf(
						'<span class="pfh-footer__social-img is-masked" style="%s" aria-hidden="true"></span>',
						esc_attr( PFH_Widgets_Helpers::css_vars( [ '--pfh-social-icon' => 'url(' . $image . ')' ] ) )
					);
				} elseif ( $image ) {
					printf(
						'<img class="pfh-footer__social-img" src="%s" alt="" aria-hidden="true" decoding="async" />',
						esc_url( $image )
					);
				} else {
					echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup.
				}

				echo '</a></li>';
			}

			echo '</ul>';
		}

		echo '</div>';
	}

	/**
	 * The badge's score and count, live where possible.
	 *
	 * The builder keeps the typed values so editing never waits on a remote
	 * call, and so the panel shows exactly what an unreachable feed would.
	 *
	 * @return array{score: string, count: int}
	 */
	private function review_figures() {
		// Shared with the hero and the sticky badge, so one feed cannot show
		// three different numbers in three places on the same page. The footer
		// shows the raw WebwinkelKeur score, hence the 10 scale.
		$figures = PFH_Widgets_Reviews::figures(
			[
				'live'  => $this->is_on( 'reviewsLive' ),
				'scale' => 10,
				'score' => (string) $this->get( 'reviewsScore', '' ),
				'count' => (int) $this->get( 'reviewsCount', 0 ),
			]
		);

		return $figures;
	}

	private function render_bottom() {
		$reviews   = $this->is_on( 'reviewsEnable' );
		$copyright = (string) $this->get( 'copyright', '' );

		if ( ! $reviews && ! $copyright ) {
			return;
		}

		$classes = [ 'pfh-footer__bottom' ];

		if ( $this->is_on( 'dividerEnable', false ) ) {
			$classes[] = 'has-divider';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		if ( $reviews ) {
			$link = PFH_Widgets_Helpers::link( $this->get( 'reviewsLink' ) );
			$logo = PFH_Widgets_Helpers::image_url( $this->get( 'reviewsLogo' ), 'medium' );

			// A trust badge nobody can click is decoration. With no link set,
			// point at the shop's own WebwinkelKeur page.
			if ( '' === $link['href'] ) {
				$link = [
					'href'   => PFH_Widgets_Reviews::review_url(),
					'target' => '_blank',
					'rel'    => 'noopener nofollow',
					'aria'   => '',
				];
			}

			$tag = $link['href'] ? 'a' : 'div';

			echo '<' . $tag . ' class="pfh-footer__reviews"' . ( 'a' === $tag ? PFH_Widgets_Helpers::link_attrs( $link ) : '' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().

			if ( $this->get( 'reviewsLabel' ) ) {
				echo '<strong class="pfh-footer__reviews-label">' . esc_html( $this->get( 'reviewsLabel' ) ) . '</strong>';
			}

			$figures = $this->review_figures();

			if ( '' !== $figures['score'] ) {
				echo '<strong class="pfh-footer__reviews-score">' . esc_html( $figures['score'] ) . '</strong>';
			}

			$text = (string) $this->get( 'reviewsText', '' );

			if ( '' !== trim( $text ) ) {
				printf(
					'<span class="pfh-footer__reviews-text">| %s</span>',
					esc_html(
						PFH_Widgets_Reviews::tokens(
							PFH_Widgets_Helpers::dd( $text ),
							$figures
						)
					)
				);
			}

			if ( $logo ) {
				printf(
					'<img class="pfh-footer__reviews-logo" src="%s" alt="" loading="lazy" decoding="async" />',
					esc_url( $logo )
				);
			}

			echo '</' . $tag . '>';
		}

		if ( $copyright ) {
			$copyright = str_replace( '{year}', gmdate( 'Y' ), $copyright );

			echo '<p class="pfh-footer__copyright">' . esc_html( PFH_Widgets_Helpers::dd( $copyright ) ) . '</p>';
		}

		echo '</div>';
	}
}
