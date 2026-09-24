<?php
/**
 * Bricks element: Products For Home header.
 *
 * Announcement bar + sticky header bar + hover mega menu, with a search
 * popup, a WooCommerce cart drawer and a mobile off-canvas drawer.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Header extends \Bricks\Element {

	use PFH_Element_Defaults;

	/**
	 * Cached navigation items.
	 *
	 * Per instance, not a method static: a static is shared by every
	 * instance of the class, so a second copy of this element on the same
	 * page would reuse the first one's value instead of computing its own.
	 *
	 * @var mixed
	 */
	private $items = null;

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
	public $name         = 'pfh-header';
	public $icon         = 'ti-layout-width-full';
	public $css_selector = '.pfh-header';
	public $scripts      = [ 'pfhHeaderInit' ];

	/**
	 * Default asset URLs supplied by the client.
	 */
	const LOGO_URL    = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Group-1-logomain.jpg';
	const SEARCH_URL  = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/search-line.svg';
	const CART_URL    = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/fi_4903482.svg';
	const ACCOUNT_URL = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/fi_12500060.svg';
	const MEGA_BG_URL = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/Frame-470015-scaled.jpg';

	public function get_label() {
		return esc_html__( 'PFH Header', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'header', 'mega', 'menu', 'nav', 'cart', 'search', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::header();
	}

	public function set_control_groups() {
		$this->control_groups['layout']   = [ 'title' => esc_html__( 'Layout', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['topbar']   = [ 'title' => esc_html__( 'Announcement bar', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['logo']     = [ 'title' => esc_html__( 'Logo', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['nav']      = [ 'title' => esc_html__( 'Navigation', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['mega']     = [ 'title' => esc_html__( 'Mega menu', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['actions']  = [ 'title' => esc_html__( 'Icons & actions', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['search']   = [ 'title' => esc_html__( 'Search popup', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['cart']     = [ 'title' => esc_html__( 'Cart drawer', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['mobile']   = [ 'title' => esc_html__( 'Mobile menu', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->layout_controls();
		$this->topbar_controls();
		$this->logo_controls();
		$this->nav_controls();
		$this->mega_controls();
		$this->action_controls();
		$this->search_controls();
		$this->cart_controls();
		$this->mobile_controls();
	}

	/* ---------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	private function layout_controls() {
		$this->controls['mobileOrder'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Mobile icon order', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'search-cart-menu' => esc_html__( 'Search, cart, menu — menu furthest right', 'pfh-widgets' ),
				'search-menu-cart' => esc_html__( 'Search, menu, cart', 'pfh-widgets' ),
				'menu-search-cart' => esc_html__( 'Menu, search, cart', 'pfh-widgets' ),
				'cart-search-menu' => esc_html__( 'Cart, search, menu', 'pfh-widgets' ),
			],
			'default'     => 'search-cart-menu',
			'description' => esc_html__( 'The logo always sits on the left below the menu breakpoint; this orders what follows it.', 'pfh-widgets' ),
		];

		$this->controls['accountMobile'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Show the account icon on mobile', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Off by design — the account link stays in the mobile menu instead of crowding the bar.', 'pfh-widgets' ),
		];

		$this->controls['containerWidth'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Content width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 600,
			'max'     => 1920,
			'step'    => 1,
			'inline'  => true,
			'default' => 1140,
			'description' => esc_html__( 'Keep this the same as the footer so the logo lines up with the first footer column.', 'pfh-widgets' ),
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

		$this->controls['headerHeight'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Header height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 48,
			'max'     => 160,
			'inline'  => true,
			'default' => 72,
		];

		$this->controls['headerBg'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Header background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['accentColor'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Brand green', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#6f8566' ],
			'description' => esc_html__( 'Used for the active menu underline, links and buttons.', 'pfh-widgets' ),
		];

		$this->controls['inkColor'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#14181b' ],
		];

		$this->controls['fontFamily'] = [
			'tab'   => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Font family', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => 'Outfit',
			'description' => esc_html__( 'Falls back to the system stack automatically.', 'pfh-widgets' ),
		];

		$this->controls['sticky'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Sticky header', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['stickyShadow'] = [
			'tab'   => 'content',
			'group'    => 'layout',
			'label'    => esc_html__( 'Shadow once scrolled', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'sticky', '=', true ],
		];

		$this->controls['stickyTopbar'] = [
			'tab'   => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Keep announcement bar visible', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => false,
			'required'    => [ 'sticky', '=', true ],
			'description' => esc_html__( 'Off: the bar scrolls away and only the header sticks.', 'pfh-widgets' ),
		];

		$this->controls['headerBorder'] = [
			'tab'   => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Bottom border colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'rgb' => 'rgba(20, 24, 27, 0.08)' ],
		];
	}

	private function topbar_controls() {
		$this->controls['topbarEnable'] = [
			'tab'   => 'content',
			'group'   => 'topbar',
			'label'   => esc_html__( 'Show announcement bar', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['topbarItems'] = [
			'tab'   => 'content',
			'group'         => 'topbar',
			'label'         => esc_html__( 'Messages', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'text',
			'required'      => [ 'topbarEnable', '=', true ],
			'default'       => [
				[ 'text' => '35% korting op je eerste bestelling én GRATIS bezorging!' ],
			],
			'fields'        => [
				'text' => [
					'label' => esc_html__( 'Text', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'link' => [
					'label' => esc_html__( 'Link', 'pfh-widgets' ),
					'type'  => 'link',
				],
			],
		];

		$this->controls['topbarInterval'] = [
			'tab'   => 'content',
			'group'       => 'topbar',
			'label'       => esc_html__( 'Rotation interval (seconds)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 2,
			'max'         => 30,
			'inline'      => true,
			'default'     => 5,
			'required'    => [ 'topbarEnable', '=', true ],
			'description' => esc_html__( 'Only applies when more than one message is set.', 'pfh-widgets' ),
		];

		$this->controls['topbarBg'] = [
			'tab'   => 'content',
			'group'    => 'topbar',
			'label'    => esc_html__( 'Background', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#6f8566' ],
			'required' => [ 'topbarEnable', '=', true ],
		];

		$this->controls['topbarColor'] = [
			'tab'   => 'content',
			'group'    => 'topbar',
			'label'    => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#fefefe' ],
			'required' => [ 'topbarEnable', '=', true ],
		];

		$this->controls['topbarFontSize'] = [
			'tab'   => 'content',
			'group'    => 'topbar',
			'label'    => esc_html__( 'Font size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 10,
			'max'      => 24,
			'inline'   => true,
			'default'  => 16,
			'required' => [ 'topbarEnable', '=', true ],
		];

		$this->controls['topbarFontWeight'] = [
			'tab'   => 'content',
			'group'    => 'topbar',
			'label'    => esc_html__( 'Font weight', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => $this->weight_options(),
			'default'  => '400',
			'required' => [ 'topbarEnable', '=', true ],
		];

		$this->controls['topbarHeight'] = [
			'tab'   => 'content',
			'group'    => 'topbar',
			'label'    => esc_html__( 'Bar height (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 24,
			'max'      => 90,
			'inline'   => true,
			'default'  => 41,
			'required' => [ 'topbarEnable', '=', true ],
		];
	}

	private function logo_controls() {
		$this->controls['logoImage'] = [
			'tab'   => 'content',
			'group'   => 'logo',
			'label'   => esc_html__( 'Logo', 'pfh-widgets' ),
			'type'    => 'image',
			'default' => [ 'url' => self::LOGO_URL ],
		];

		$this->controls['logoWidth'] = [
			'tab'   => 'content',
			'group'   => 'logo',
			'label'   => esc_html__( 'Logo width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 16,
			'max'     => 400,
			'inline'  => true,
			'default' => 40,
		];

		$this->controls['logoLink'] = [
			'tab'   => 'content',
			'group'       => 'logo',
			'label'       => esc_html__( 'Logo link', 'pfh-widgets' ),
			'type'        => 'link',
			'description' => esc_html__( 'Leave empty to link to the homepage.', 'pfh-widgets' ),
		];

		$this->controls['logoAlt'] = [
			'tab'   => 'content',
			'group'   => 'logo',
			'label'   => esc_html__( 'Alt text', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => get_bloginfo( 'name' ),
		];
	}

	private function nav_controls() {
		$this->controls['navItems'] = [
			'tab'   => 'content',
			'group'         => 'nav',
			'label'         => esc_html__( 'Menu items', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'label',
			'default'       => $this->default_nav_items(),
			'fields'        => [
				'label'        => [
					'label' => esc_html__( 'Label', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'link'         => [
					'label' => esc_html__( 'Link', 'pfh-widgets' ),
					'type'  => 'link',
				],
				'hasMega'      => [
					'label' => esc_html__( 'Enable mega menu', 'pfh-widgets' ),
					'type'  => 'checkbox',
				],
				'megaSource'   => [
					'label'    => esc_html__( 'Cards from', 'pfh-widgets' ),
					'type'     => 'select',
					'inline'   => true,
					'options'  => [
						'product_cat' => esc_html__( 'Product categories', 'pfh-widgets' ),
						'products'    => esc_html__( 'Products', 'pfh-widgets' ),
						'custom'      => esc_html__( 'Custom list', 'pfh-widgets' ),
					],
					'default'  => 'product_cat',
					'required' => [ 'hasMega', '=', true ],
				],
				'megaParent'   => [
					'label'       => esc_html__( 'Parent category', 'pfh-widgets' ),
					'type'        => 'select',
					'searchable'  => true,
					'options'     => PFH_Widgets_Helpers::product_cat_options(),
					'placeholder' => esc_html__( 'Top level', 'pfh-widgets' ),
					'description' => esc_html__( 'Shows the direct children of this category.', 'pfh-widgets' ),
					'required'    => [ [ 'hasMega', '=', true ], [ 'megaSource', '=', 'product_cat' ] ],
				],
				'megaInclude'  => [
					'label'       => esc_html__( 'Only these category IDs / slugs', 'pfh-widgets' ),
					'type'        => 'text',
					'placeholder' => 'honing, olijfolie, 42',
					'description' => esc_html__( 'Comma separated. Overrides the parent setting and keeps this exact order.', 'pfh-widgets' ),
					'required'    => [ [ 'hasMega', '=', true ], [ 'megaSource', '=', 'product_cat' ] ],
				],
				'megaProdCat'  => [
					'label'       => esc_html__( 'Product category', 'pfh-widgets' ),
					'type'        => 'select',
					'searchable'  => true,
					'options'     => PFH_Widgets_Helpers::product_cat_options(),
					'placeholder' => esc_html__( 'All products', 'pfh-widgets' ),
					'required'    => [ [ 'hasMega', '=', true ], [ 'megaSource', '=', 'products' ] ],
				],
				'megaOrderby'  => [
					'label'    => esc_html__( 'Order by', 'pfh-widgets' ),
					'type'     => 'select',
					'inline'   => true,
					'options'  => [
						'menu_order' => esc_html__( 'Menu order', 'pfh-widgets' ),
						'date'       => esc_html__( 'Newest', 'pfh-widgets' ),
						'title'      => esc_html__( 'Title', 'pfh-widgets' ),
						'popularity' => esc_html__( 'Best selling', 'pfh-widgets' ),
						'rand'       => esc_html__( 'Random', 'pfh-widgets' ),
					],
					'default'  => 'menu_order',
					'required' => [ [ 'hasMega', '=', true ], [ 'megaSource', '=', 'products' ] ],
				],
				'megaCustom'   => [
					'label'       => esc_html__( 'Custom cards', 'pfh-widgets' ),
					'type'        => 'textarea',
					'placeholder' => "Gia Giamas bundle 450ml | /product/bundle-450 | https://…/image.jpg",
					'description' => esc_html__( 'One card per line: Title | URL | Image URL', 'pfh-widgets' ),
					'required'    => [ [ 'hasMega', '=', true ], [ 'megaSource', '=', 'custom' ] ],
				],
				'megaLimit'    => [
					'label'    => esc_html__( 'Number of cards', 'pfh-widgets' ),
					'type'     => 'number',
					'min'      => 1,
					'max'      => 12,
					'inline'   => true,
					'default'  => 5,
					'required' => [ 'hasMega', '=', true ],
				],
				'megaLinkText' => [
					'label'    => esc_html__( 'Card link text', 'pfh-widgets' ),
					'type'     => 'text',
					'inline'   => true,
					'default'  => 'Bekijk alles',
					'required' => [ 'hasMega', '=', true ],
				],
			],
		];

		$this->controls['navAlign'] = [
			'tab'   => 'content',
			'group'       => 'nav',
			'label'       => esc_html__( 'Menu alignment', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'page'   => esc_html__( 'Centred on the container', 'pfh-widgets' ),
				'fill'   => esc_html__( 'Centred in the free space', 'pfh-widgets' ),
				'start'  => esc_html__( 'Next to the logo', 'pfh-widgets' ),
			],
			'default'     => 'page',
			'description' => esc_html__( 'Container centring matches the Figma layout; free-space centring keeps the menu clear of a wide logo.', 'pfh-widgets' ),
		];

		$this->controls['navGap'] = [
			'tab'   => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Gap between items (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 80,
			'inline'  => true,
			'default' => 40,
		];

		$this->controls['navFontSize'] = [
			'tab'   => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Font size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 15,
		];

		$this->controls['navFontWeight'] = [
			'tab'   => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Font weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '500',
		];

		$this->controls['navColor'] = [
			'tab'   => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Link colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#14181b' ],
		];

		$this->controls['navHoverColor'] = [
			'tab'   => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Link colour (hover / open)', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#14181b' ],
		];

		$this->controls['navIndicatorColor'] = [
			'tab'   => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Underline colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#6f8566' ],
		];

		$this->controls['navIndicatorHeight'] = [
			'tab'   => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Underline thickness (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 8,
			'inline'  => true,
			'default' => 2,
		];

		$this->controls['navCaret'] = [
			'tab'   => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Show dropdown arrows', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['navHighlightCurrent'] = [
			'tab'   => 'content',
			'group'   => 'nav',
			'label'   => esc_html__( 'Underline the current page', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];
	}

	private function mega_controls() {
		$this->controls['megaBgColor'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Panel background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['megaBgImage'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Panel background image', 'pfh-widgets' ),
			'type'    => 'image',
			'default' => [ 'url' => self::MEGA_BG_URL ],
		];

		$this->controls['megaBgSize'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Background size', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'fit'       => esc_html__( 'Fit the panel, capped at the artwork width', 'pfh-widgets' ),
				'cover'     => 'cover',
				'contain'   => 'contain',
				'auto'      => 'auto',
				'100% 100%' => '100% 100%',
			],
			'default'     => 'fit',
			'description' => esc_html__( 'The artwork is drawn for a 1920px frame. “Fit” spans the panel up to that width and then stops growing, so on a wide monitor the decoration stays beside the content instead of being scaled up and cropped.', 'pfh-widgets' ),
		];

		$this->controls['megaBgMaxWidth'] = [
			'tab'      => 'content',
			'group'    => 'mega',
			'label'    => esc_html__( 'Artwork width (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 320,
			'max'      => 3840,
			'inline'   => true,
			'default'  => 1920,
			'required' => [ 'megaBgSize', '=', 'fit' ],
		];

		$this->controls['megaBgPosition'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Background position', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'center bottom',
		];

		$this->controls['megaRadius'] = [
			'tab'   => 'content',
			'group'       => 'mega',
			'label'       => esc_html__( 'Bottom corner radius (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 80,
			'inline'      => true,
			'default'     => 30,
		];

		$this->controls['megaPaddingY'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Vertical padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 140,
			'inline'  => true,
			'default' => 44,
		];

		$this->controls['megaPaddingX'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Horizontal inset (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 140,
			'inline'  => true,
			'default' => 22,
		];

		$this->controls['megaColumns'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Columns', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 8,
			'inline'  => true,
			'default' => 5,
		];

		$this->controls['megaGap'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Gap between cards (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 22,
		];

		$this->controls['megaCardRatio'] = [
			'tab'   => 'content',
			'group'       => 'mega',
			'label'       => esc_html__( 'Image box ratio', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '196 / 220',
			'description' => esc_html__( 'Any CSS aspect-ratio value, e.g. 1 / 1.', 'pfh-widgets' ),
		];

		$this->controls['megaCardBg'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Image box background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#f4f4f4' ],
		];

		$this->controls['megaCardRadius'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Image box radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 18,
		];

		$this->controls['megaCardPadding'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Image box padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 26,
		];

		$this->controls['megaTitleSize'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Card title size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 32,
			'inline'  => true,
			'default' => 17,
		];

		$this->controls['megaTitleWeight'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Card title weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '500',
		];

		$this->controls['megaTitleColor'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Card title colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#14181b' ],
		];

		$this->controls['megaLinkSize'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Card link size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 15,
		];

		$this->controls['megaLinkColor'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Card link colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#5c7752' ],
		];

		$this->controls['megaShowArrow'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Show arrow after link', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['megaTrigger'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Open on', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'hover' => esc_html__( 'Hover', 'pfh-widgets' ),
				'click' => esc_html__( 'Click', 'pfh-widgets' ),
			],
			'default' => 'hover',
		];

		$this->controls['megaDelay'] = [
			'tab'   => 'content',
			'group'       => 'mega',
			'label'       => esc_html__( 'Hover delay (ms)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 600,
			'inline'      => true,
			'default'     => 90,
			'required'    => [ 'megaTrigger', '=', 'hover' ],
			'description' => esc_html__( 'Small delay stops the panel flickering between items.', 'pfh-widgets' ),
		];

		$this->controls['megaAnimation'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Animation', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'slide' => esc_html__( 'Fade + slide', 'pfh-widgets' ),
				'fade'  => esc_html__( 'Fade', 'pfh-widgets' ),
				'none'  => esc_html__( 'None', 'pfh-widgets' ),
			],
			'default' => 'slide',
		];

		$this->controls['megaOverlay'] = [
			'tab'   => 'content',
			'group'   => 'mega',
			'label'   => esc_html__( 'Dim the page behind the panel', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['megaScrimColor'] = [
			'tab'   => 'content',
			'group'    => 'mega',
			'label'    => esc_html__( 'Dim colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(20, 24, 27, 0.12)' ],
			'required' => [ 'megaOverlay', '=', true ],
		];
	}

	private function action_controls() {
		$this->controls['iconSize'] = [
			'tab'   => 'content',
			'group'   => 'actions',
			'label'   => esc_html__( 'Icon size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 48,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['iconGap'] = [
			'tab'   => 'content',
			'group'   => 'actions',
			'label'   => esc_html__( 'Gap between icons (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 4,
			'max'     => 60,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['iconColor'] = [
			'tab'   => 'content',
			'group'       => 'actions',
			'label'       => esc_html__( 'Icon colour', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#14181b' ],
			'description' => esc_html__( 'Applies to the built-in icons. Uploaded image icons keep their own colours.', 'pfh-widgets' ),
		];

		$this->controls['showSearch'] = [
			'tab'   => 'content',
			'group'   => 'actions',
			'label'   => esc_html__( 'Show search', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['searchIcon'] = [
			'tab'   => 'content',
			'group'    => 'actions',
			'label'    => esc_html__( 'Search icon', 'pfh-widgets' ),
			'type'     => 'image',
			'default'  => [ 'url' => self::SEARCH_URL ],
			'required' => [ 'showSearch', '=', true ],
		];

		$this->controls['showCart'] = [
			'tab'   => 'content',
			'group'   => 'actions',
			'label'   => esc_html__( 'Show cart', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['cartIcon'] = [
			'tab'   => 'content',
			'group'    => 'actions',
			'label'    => esc_html__( 'Cart icon', 'pfh-widgets' ),
			'type'     => 'image',
			'default'  => [ 'url' => self::CART_URL ],
			'required' => [ 'showCart', '=', true ],
		];

		$this->controls['showCartCount'] = [
			'tab'   => 'content',
			'group'    => 'actions',
			'label'    => esc_html__( 'Show item count badge', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'showCart', '=', true ],
		];

		$this->controls['cartBadgeBg'] = [
			'tab'   => 'content',
			'group'    => 'actions',
			'label'    => esc_html__( 'Badge background', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#6f8566' ],
			'required' => [ 'showCartCount', '=', true ],
		];

		$this->controls['cartBadgeColor'] = [
			'tab'   => 'content',
			'group'    => 'actions',
			'label'    => esc_html__( 'Badge text', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#ffffff' ],
			'required' => [ 'showCartCount', '=', true ],
		];

		$this->controls['showAccount'] = [
			'tab'   => 'content',
			'group'   => 'actions',
			'label'   => esc_html__( 'Show account', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['accountIcon'] = [
			'tab'   => 'content',
			'group'    => 'actions',
			'label'    => esc_html__( 'Account icon', 'pfh-widgets' ),
			'type'     => 'image',
			'default'  => [ 'url' => self::ACCOUNT_URL ],
			'required' => [ 'showAccount', '=', true ],
		];

		$this->controls['accountLink'] = [
			'tab'   => 'content',
			'group'       => 'actions',
			'label'       => esc_html__( 'Account link', 'pfh-widgets' ),
			'type'        => 'link',
			'required'    => [ 'showAccount', '=', true ],
			'description' => esc_html__( 'Leave empty to use the WooCommerce My Account page.', 'pfh-widgets' ),
		];

		$this->controls['accountLabel'] = [
			'tab'   => 'content',
			'group'    => 'actions',
			'label'    => esc_html__( 'Account aria-label', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Mijn account',
			'required' => [ 'showAccount', '=', true ],
		];
	}

	private function search_controls() {
		$this->controls['searchHeading'] = [
			'tab'   => 'content',
			'group'    => 'search',
			'label'    => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'     => 'text',
			'default'  => 'Waar ben je naar op zoek?',
			'required' => [ 'showSearch', '=', true ],
		];

		$this->controls['searchPlaceholder'] = [
			'tab'   => 'content',
			'group'    => 'search',
			'label'    => esc_html__( 'Placeholder', 'pfh-widgets' ),
			'type'     => 'text',
			'default'  => 'Zoek naar producten…',
			'required' => [ 'showSearch', '=', true ],
		];

		$this->controls['searchButtonText'] = [
			'tab'   => 'content',
			'group'    => 'search',
			'label'    => esc_html__( 'Submit button text', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Zoeken',
			'required' => [ 'showSearch', '=', true ],
		];

		$this->controls['searchPostType'] = [
			'tab'   => 'content',
			'group'    => 'search',
			'label'    => esc_html__( 'Search in', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => [
				'product' => esc_html__( 'Products only', 'pfh-widgets' ),
				'any'     => esc_html__( 'Everything', 'pfh-widgets' ),
				'post'    => esc_html__( 'Blog posts', 'pfh-widgets' ),
			],
			'default'  => 'product',
			'required' => [ 'showSearch', '=', true ],
		];

		$this->controls['searchLive'] = [
			'tab'   => 'content',
			'group'    => 'search',
			'label'    => esc_html__( 'Live results while typing', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'showSearch', '=', true ],
		];

		$this->controls['searchLimit'] = [
			'tab'   => 'content',
			'group'    => 'search',
			'label'    => esc_html__( 'Number of live results', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 1,
			'max'      => 20,
			'inline'   => true,
			'default'  => 6,
			'required' => [ [ 'showSearch', '=', true ], [ 'searchLive', '=', true ] ],
		];

		$this->controls['searchEmptyText'] = [
			'tab'   => 'content',
			'group'    => 'search',
			'label'    => esc_html__( 'No results text', 'pfh-widgets' ),
			'type'     => 'text',
			'default'  => 'Geen resultaten gevonden.',
			'required' => [ [ 'showSearch', '=', true ], [ 'searchLive', '=', true ] ],
		];

		$this->controls['searchOverlayBg'] = [
			'tab'   => 'content',
			'group'    => 'search',
			'label'    => esc_html__( 'Overlay colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(20, 24, 27, 0.45)' ],
			'required' => [ 'showSearch', '=', true ],
		];

		$this->controls['searchPanelBg'] = [
			'tab'   => 'content',
			'group'    => 'search',
			'label'    => esc_html__( 'Panel colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#ffffff' ],
			'required' => [ 'showSearch', '=', true ],
		];
	}

	private function cart_controls() {
		$this->controls['cartMode'] = [
			'tab'   => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Cart icon behaviour', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => [
				'drawer'    => esc_html__( 'Open drawer', 'pfh-widgets' ),
				'funnelkit' => esc_html__( 'Open FunnelKit Cart', 'pfh-widgets' ),
				'link'      => esc_html__( 'Go to cart page', 'pfh-widgets' ),
			],
			'default'  => 'drawer',
			'required' => [ 'showCart', '=', true ],
			'description' => esc_html__( 'FunnelKit Cart: the slide-in cart of the FunnelKit Cart plugin opens, with its upsells and coupon field. On a page where that plugin does not load its cart, the drawer below opens instead.', 'pfh-widgets' ),
		];

		$this->controls['cartLink'] = [
			'tab'   => 'content',
			'group'       => 'cart',
			'label'       => esc_html__( 'Cart link', 'pfh-widgets' ),
			'type'        => 'link',
			'required'    => [ [ 'showCart', '=', true ], [ 'cartMode', '=', 'link' ] ],
			'description' => esc_html__( 'Leave empty to use the WooCommerce cart page.', 'pfh-widgets' ),
		];

		$this->controls['cartTitle'] = [
			'tab'   => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Drawer title', 'pfh-widgets' ),
			'type'     => 'text',
			'default'  => 'Winkelwagen',
			'required' => [ [ 'showCart', '=', true ], [ 'cartMode', '=', [ 'drawer', 'funnelkit' ] ] ],
		];

		$this->controls['cartOpenOnAdd'] = [
			'tab'   => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Open drawer after add to cart', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ [ 'showCart', '=', true ], [ 'cartMode', '=', [ 'drawer', 'funnelkit' ] ] ],
		];

		$this->controls['cartWidth'] = [
			'tab'   => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Drawer width (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 280,
			'max'      => 720,
			'inline'   => true,
			'default'  => 420,
			'required' => [ [ 'showCart', '=', true ], [ 'cartMode', '=', [ 'drawer', 'funnelkit' ] ] ],
		];

		$labels = [
			'cartEmptyText'    => [ 'Je winkelwagen is nog leeg.', esc_html__( 'Empty cart text', 'pfh-widgets' ) ],
			'cartShopText'     => [ 'Verder winkelen', esc_html__( 'Empty cart button', 'pfh-widgets' ) ],
			'cartSubtotalText' => [ 'Subtotaal', esc_html__( 'Subtotal label', 'pfh-widgets' ) ],
			'cartNoteText'     => [ 'Verzendkosten worden berekend bij het afrekenen.', esc_html__( 'Footer note', 'pfh-widgets' ) ],
			'cartViewText'     => [ 'Bekijk winkelwagen', esc_html__( 'View cart button', 'pfh-widgets' ) ],
			'cartCheckoutText' => [ 'Afrekenen', esc_html__( 'Checkout button', 'pfh-widgets' ) ],
		];

		foreach ( $labels as $key => $data ) {
			$this->controls[ $key ] = [
				'tab'   => 'content',
				'group'    => 'cart',
				'label'    => $data[1],
				'type'     => 'text',
				'default'  => $data[0],
				'required' => [ [ 'showCart', '=', true ], [ 'cartMode', '=', [ 'drawer', 'funnelkit' ] ] ],
			];
		}

		$this->controls['cartShopLink'] = [
			'tab'   => 'content',
			'group'       => 'cart',
			'label'       => esc_html__( 'Empty cart button link', 'pfh-widgets' ),
			'type'        => 'link',
			'required'    => [ [ 'showCart', '=', true ], [ 'cartMode', '=', [ 'drawer', 'funnelkit' ] ] ],
			'description' => esc_html__( 'Leave empty to use the WooCommerce shop page.', 'pfh-widgets' ),
		];

		$this->controls['cartPanelBg'] = [
			'tab'   => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Drawer background', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#ffffff' ],
			'required' => [ [ 'showCart', '=', true ], [ 'cartMode', '=', [ 'drawer', 'funnelkit' ] ] ],
		];

		$this->controls['cartBtnBg'] = [
			'tab'   => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Primary button background', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#6f8566' ],
			'required' => [ [ 'showCart', '=', true ], [ 'cartMode', '=', [ 'drawer', 'funnelkit' ] ] ],
		];

		$this->controls['cartBtnColor'] = [
			'tab'   => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Primary button text', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#ffffff' ],
			'required' => [ [ 'showCart', '=', true ], [ 'cartMode', '=', [ 'drawer', 'funnelkit' ] ] ],
		];
	}

	private function mobile_controls() {
		$this->controls['mobileBreakpoint'] = [
			'tab'   => 'content',
			'group'   => 'mobile',
			'label'   => esc_html__( 'Switch to mobile menu below', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'1200' => '1200px',
				'991'  => '991px',
				'767'  => '767px',
			],
			'default' => '991',
		];

		$this->controls['mobileTitle'] = [
			'tab'   => 'content',
			'group'   => 'mobile',
			'label'   => esc_html__( 'Drawer title', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Menu',
		];

		$this->controls['mobileWidth'] = [
			'tab'   => 'content',
			'group'   => 'mobile',
			'label'   => esc_html__( 'Drawer width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 260,
			'max'     => 560,
			'inline'  => true,
			'default' => 340,
		];

		$this->controls['mobilePanelBg'] = [
			'tab'   => 'content',
			'group'   => 'mobile',
			'label'   => esc_html__( 'Drawer background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['mobileShowAccount'] = [
			'tab'   => 'content',
			'group'   => 'mobile',
			'label'   => esc_html__( 'Show account link in drawer', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['mobileFooterText'] = [
			'tab'   => 'content',
			'group'   => 'mobile',
			'label'   => esc_html__( 'Drawer footer text', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => '',
		];
	}

	/* ---------------------------------------------------------------------
	 * Control helpers
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

	private function default_nav_items() {
		$items = [
			[ 'label' => 'Gia giamas', 'hasMega' => true ],
			[ 'label' => 'Honing', 'hasMega' => true ],
			[ 'label' => 'Olijfolie', 'hasMega' => true ],
			[ 'label' => 'Bijenwas', 'hasMega' => true ],
			[ 'label' => 'Sales en bundels' ],
		];

		foreach ( $items as $index => $item ) {
			if ( ! empty( $item['hasMega'] ) ) {
				$items[ $index ]['megaSource']   = 'product_cat';
				$items[ $index ]['megaLimit']    = 5;
				$items[ $index ]['megaLinkText'] = 'Bekijk alles';
			}
		}

		return $items;
	}

	/* ---------------------------------------------------------------------
	 * Setting accessors
	 * ------------------------------------------------------------------ */

	/**
	 * Read a setting, falling back to $default when unset or empty string.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	private function get( $key, $default = null ) {
		return $this->setting( $key, $default );
	}

	/**
	 * Read a checkbox setting.
	 *
	 * When the key was never written we honour the control default, which is
	 * what Bricks shows in the panel.
	 *
	 * @param string $key     Setting key.
	 * @param bool   $default Control default.
	 * @return bool
	 */
	private function is_on( $key, $default = true ) {
		return $this->switched_on( $key, $default );
	}

	/**
	 * Unique suffix for element scoped DOM ids.
	 *
	 * @return string
	 */
	private function uid() {
		if ( null === $this->uid ) {
			$this->uid = ! empty( $this->element['id'] ) ? sanitize_html_class( $this->element['id'] ) : uniqid( 'pfh' );
		}

		return $this->uid;
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$vars    = $this->build_vars();
		$topbar  = $this->topbar_items();
		$classes = [
			'pfh-header',
			'pfh-scope',
			'pfh-bp-' . (string) $this->get( 'mobileBreakpoint', '991' ),
			'pfh-mo-' . (string) $this->get( 'mobileOrder', 'search-cart-menu' ),
		];

		if ( ! $this->is_on( 'accountMobile', false ) ) {
			$classes[] = 'hide-account-mobile';
		}

		if ( ! empty( $topbar ) ) {
			$classes[] = 'has-topbar';
		}

		if ( $this->is_on( 'sticky' ) ) {
			$classes[] = 'is-sticky';

			if ( $this->is_on( 'stickyTopbar', false ) ) {
				$classes[] = 'is-sticky-all';
			}

			if ( $this->is_on( 'stickyShadow' ) ) {
				$classes[] = 'has-scroll-shadow';
			}
		}

		$classes[] = 'pfh-anim-' . (string) $this->get( 'megaAnimation', 'slide' );

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $vars );
		$this->set_attribute( '_root', 'data-pfh-header', $this->uid() );
		$this->set_attribute( '_root', 'data-pfh-config', wp_json_encode( $this->js_config() ) );

		echo '<header ' . $this->render_attributes( '_root' ) . '>';

		$this->render_topbar( $topbar );

		echo '<div class="pfh-bar">';
		echo '<div class="pfh-bar__inner">';

		$this->render_burger();
		$this->render_logo();
		$this->render_nav();
		$this->render_actions();

		echo '</div>';
		echo '</div>';

		$this->render_portal( $vars );

		echo '</header>';
	}

	/**
	 * Build the inline CSS custom properties consumed by the stylesheet.
	 *
	 * @return string
	 */
	/**
	 * Resolve the mega panel's background-size.
	 *
	 * `cover` on a panel wider than the artwork scales it up and crops the
	 * decoration out of view, which is what goes wrong on a large monitor.
	 * "Fit" caps the painted width at the artwork's own so it simply stops
	 * growing and stays centred on the content.
	 *
	 * @return string
	 */
	private function mega_bg_size() {
		$size = (string) $this->get( 'megaBgSize', 'fit' );

		if ( 'fit' !== $size ) {
			return $size;
		}

		$cap = max( 320, (int) $this->get( 'megaBgMaxWidth', 1920 ) );

		return 'min(100%, ' . $cap . 'px) auto';
	}

	private function build_vars() {
		$font    = trim( (string) $this->get( 'fontFamily', 'Outfit' ) );
		$mega_bg = PFH_Widgets_Helpers::image_url( $this->get( 'megaBgImage' ), 'full' );

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-font'          => $font ? $font . ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif' : '',
				'--pfh-accent'        => PFH_Widgets_Helpers::color( $this->get( 'accentColor' ), '#6f8566' ),
				'--pfh-ink'           => PFH_Widgets_Helpers::color( $this->get( 'inkColor' ), '#14181b' ),
				'--pfh-container'     => PFH_Widgets_Helpers::unit( $this->get( 'containerWidth', 1140 ) ),
				'--pfh-gutter-set'        => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-header-h-set'      => PFH_Widgets_Helpers::unit( $this->get( 'headerHeight', 72 ) ),
				'--pfh-header-bg'     => PFH_Widgets_Helpers::color( $this->get( 'headerBg' ), '#ffffff' ),
				'--pfh-header-border' => PFH_Widgets_Helpers::color( $this->get( 'headerBorder' ), 'rgba(20,24,27,.08)' ),

				'--pfh-topbar-bg'     => PFH_Widgets_Helpers::color( $this->get( 'topbarBg' ), '#6f8566' ),
				'--pfh-topbar-color'  => PFH_Widgets_Helpers::color( $this->get( 'topbarColor' ), '#fefefe' ),
				'--pfh-topbar-size'   => PFH_Widgets_Helpers::unit( $this->get( 'topbarFontSize', 16 ) ),
				'--pfh-topbar-weight' => $this->get( 'topbarFontWeight', '400' ),
				'--pfh-topbar-h'      => PFH_Widgets_Helpers::unit( $this->get( 'topbarHeight', 41 ) ),

				'--pfh-logo-w'        => PFH_Widgets_Helpers::unit( $this->get( 'logoWidth', 44 ) ),

				'--pfh-nav-gap-set'       => PFH_Widgets_Helpers::unit( $this->get( 'navGap', 40 ) ),
				'--pfh-nav-size'      => PFH_Widgets_Helpers::unit( $this->get( 'navFontSize', 15 ) ),
				'--pfh-nav-weight'    => $this->get( 'navFontWeight', '500' ),
				'--pfh-nav-color'     => PFH_Widgets_Helpers::color( $this->get( 'navColor' ), '#14181b' ),
				'--pfh-nav-hover'     => PFH_Widgets_Helpers::color( $this->get( 'navHoverColor' ), '#14181b' ),
				'--pfh-indicator'     => PFH_Widgets_Helpers::color( $this->get( 'navIndicatorColor' ), '#6f8566' ),
				'--pfh-indicator-h'   => PFH_Widgets_Helpers::unit( $this->get( 'navIndicatorHeight', 2 ) ),

				'--pfh-mega-bg'       => PFH_Widgets_Helpers::color( $this->get( 'megaBgColor' ), '#ffffff' ),
				'--pfh-mega-image'    => $mega_bg ? 'url(' . $mega_bg . ')' : 'none',
				'--pfh-mega-size'     => $this->mega_bg_size(),
				'--pfh-mega-pos'      => $this->get( 'megaBgPosition', 'center bottom' ),
				'--pfh-mega-radius'   => PFH_Widgets_Helpers::unit( $this->get( 'megaRadius', 30 ) ),
				'--pfh-mega-pad-y-set'    => PFH_Widgets_Helpers::unit( $this->get( 'megaPaddingY', 44 ) ),
				'--pfh-mega-pad-x-set'    => PFH_Widgets_Helpers::unit( $this->get( 'megaPaddingX', 22 ) ),
				'--pfh-mega-cols-set'     => (int) $this->get( 'megaColumns', 5 ),
				'--pfh-mega-gap'      => PFH_Widgets_Helpers::unit( $this->get( 'megaGap', 22 ) ),

				'--pfh-card-ratio'    => $this->get( 'megaCardRatio', '196 / 220' ),
				'--pfh-card-bg'       => PFH_Widgets_Helpers::color( $this->get( 'megaCardBg' ), '#f4f4f4' ),
				'--pfh-card-radius'   => PFH_Widgets_Helpers::unit( $this->get( 'megaCardRadius', 18 ) ),
				'--pfh-card-pad'      => PFH_Widgets_Helpers::unit( $this->get( 'megaCardPadding', 26 ) ),
				'--pfh-card-title'    => PFH_Widgets_Helpers::unit( $this->get( 'megaTitleSize', 17 ) ),
				'--pfh-card-title-w'  => $this->get( 'megaTitleWeight', '500' ),
				'--pfh-card-title-c'  => PFH_Widgets_Helpers::color( $this->get( 'megaTitleColor' ), '#14181b' ),
				'--pfh-card-link'     => PFH_Widgets_Helpers::unit( $this->get( 'megaLinkSize', 15 ) ),
				'--pfh-card-link-c'   => PFH_Widgets_Helpers::color( $this->get( 'megaLinkColor' ), '#5c7752' ),

				'--pfh-icon-size'     => PFH_Widgets_Helpers::unit( $this->get( 'iconSize', 22 ) ),
				'--pfh-icon-gap-set'      => PFH_Widgets_Helpers::unit( $this->get( 'iconGap', 26 ) ),
				'--pfh-icon-color'    => PFH_Widgets_Helpers::color( $this->get( 'iconColor' ), '#14181b' ),
				'--pfh-badge-bg'      => PFH_Widgets_Helpers::color( $this->get( 'cartBadgeBg' ), '#6f8566' ),
				'--pfh-badge-color'   => PFH_Widgets_Helpers::color( $this->get( 'cartBadgeColor' ), '#ffffff' ),

				'--pfh-scrim'         => PFH_Widgets_Helpers::color( $this->get( 'searchOverlayBg' ), 'rgba(20,24,27,.45)' ),
				'--pfh-mega-scrim'    => PFH_Widgets_Helpers::color( $this->get( 'megaScrimColor' ), 'rgba(20,24,27,.12)' ),
				'--pfh-search-bg'     => PFH_Widgets_Helpers::color( $this->get( 'searchPanelBg' ), '#ffffff' ),

				'--pfh-cart-w'        => PFH_Widgets_Helpers::unit( $this->get( 'cartWidth', 420 ) ),
				'--pfh-cart-bg'       => PFH_Widgets_Helpers::color( $this->get( 'cartPanelBg' ), '#ffffff' ),
				'--pfh-btn-bg'        => PFH_Widgets_Helpers::color( $this->get( 'cartBtnBg' ), '#6f8566' ),
				'--pfh-btn-color'     => PFH_Widgets_Helpers::color( $this->get( 'cartBtnColor' ), '#ffffff' ),

				'--pfh-mobile-w'      => PFH_Widgets_Helpers::unit( $this->get( 'mobileWidth', 340 ) ),
				'--pfh-mobile-bg'     => PFH_Widgets_Helpers::color( $this->get( 'mobilePanelBg' ), '#ffffff' ),
			]
		);
	}

	/**
	 * Config handed to the front-end script.
	 *
	 * @return array
	 */
	private function js_config() {
		return [
			'uid'            => $this->uid(),
			'megaTrigger'    => (string) $this->get( 'megaTrigger', 'hover' ),
			'megaDelay'      => (int) $this->get( 'megaDelay', 90 ),
			'megaOverlay'    => $this->is_on( 'megaOverlay' ),
			'breakpoint'     => (int) $this->get( 'mobileBreakpoint', 991 ),
			'topbarInterval' => max( 2, (int) $this->get( 'topbarInterval', 5 ) ) * 1000,
			'search'         => [
				'live'      => $this->is_on( 'searchLive' ),
				'limit'     => (int) $this->get( 'searchLimit', 6 ),
				'postType'  => (string) $this->get( 'searchPostType', 'product' ),
				'emptyText' => (string) $this->get( 'searchEmptyText', '' ),
			],
			'cart'           => [
				'enabled'   => $this->is_on( 'showCart' ) && in_array( $this->get( 'cartMode', 'drawer' ), [ 'drawer', 'funnelkit' ], true ),
				'mode'      => (string) $this->get( 'cartMode', 'drawer' ),
				'openOnAdd' => $this->is_on( 'cartOpenOnAdd' ),
				'labels'    => $this->cart_labels(),
			],
		];
	}

	/**
	 * Drawer copy pulled from the controls.
	 *
	 * @return array<string, string>
	 */
	private function cart_labels() {
		$shop = PFH_Widgets_Helpers::link( $this->get( 'cartShopLink' ) );

		return [
			'empty'    => (string) $this->get( 'cartEmptyText', 'Je winkelwagen is nog leeg.' ),
			'shop'     => (string) $this->get( 'cartShopText', 'Verder winkelen' ),
			'shopUrl'  => $shop['href'],
			'subtotal' => (string) $this->get( 'cartSubtotalText', 'Subtotaal' ),
			'note'     => (string) $this->get( 'cartNoteText', '' ),
			'cart'     => (string) $this->get( 'cartViewText', 'Bekijk winkelwagen' ),
			'checkout' => (string) $this->get( 'cartCheckoutText', 'Afrekenen' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Partials
	 * ------------------------------------------------------------------ */

	/**
	 * Announcement bar messages that actually have content.
	 *
	 * @return array<int, array>
	 */
	private function topbar_items() {
		if ( ! $this->is_on( 'topbarEnable' ) ) {
			return [];
		}

		return array_values(
			array_filter(
				(array) $this->get( 'topbarItems', [] ),
				static function ( $item ) {
					return ! empty( $item['text'] );
				}
			)
		);
	}

	/**
	 * @param array|null $items Pre-resolved messages.
	 */
	private function render_topbar( $items = null ) {
		$items = null === $items ? $this->topbar_items() : $items;

		if ( empty( $items ) ) {
			return;
		}

		echo '<div class="pfh-topbar" data-pfh-topbar>';
		echo '<div class="pfh-topbar__inner">';

		foreach ( $items as $index => $item ) {
			$text = PFH_Widgets_Helpers::dd( $item['text'] );
			$link = PFH_Widgets_Helpers::link( isset( $item['link'] ) ? $item['link'] : null );
			$open = 0 === $index ? ' is-active' : '';

			echo '<div class="pfh-topbar__item' . esc_attr( $open ) . '" data-pfh-topbar-item>';

			if ( $link['href'] ) {
				echo '<a class="pfh-topbar__link"' . PFH_Widgets_Helpers::link_attrs( $link ) . '>' . esc_html( $text ) . '</a>';
			} else {
				echo '<span>' . esc_html( $text ) . '</span>';
			}

			echo '</div>';
		}

		echo '</div>';
		echo '</div>';
	}

	private function render_burger() {
		echo '<button type="button" class="pfh-burger" data-pfh-open="mobile" aria-expanded="false" aria-controls="pfh-mobile-' . esc_attr( $this->uid() ) . '" aria-label="' . esc_attr__( 'Menu openen', 'pfh-widgets' ) . '">';
		echo PFH_Widgets_Icons::get( 'burger' );
		echo '</button>';
	}

	private function render_logo() {
		$url  = PFH_Widgets_Helpers::image_url( $this->get( 'logoImage' ), 'medium' );
		$link = PFH_Widgets_Helpers::link( $this->get( 'logoLink' ), home_url( '/' ) );
		$alt  = (string) $this->get( 'logoAlt', get_bloginfo( 'name' ) );

		echo '<div class="pfh-logo">';
		echo '<a class="pfh-logo__link"' . PFH_Widgets_Helpers::link_attrs( $link ) . '>';

		if ( $url ) {
			printf(
				'<img class="pfh-logo__img" src="%s" alt="%s" width="88" height="88" decoding="async" />',
				esc_url( $url ),
				esc_attr( $alt )
			);
		} else {
			echo '<span class="pfh-logo__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
		}

		echo '</a>';
		echo '</div>';
	}

	private function render_nav() {
		$items = $this->nav_items();

		if ( empty( $items ) ) {
			return;
		}

		$caret = $this->is_on( 'navCaret' );

		$align   = (string) $this->get( 'navAlign', 'page' );
		$nav_cls = 'pfh-nav';

		if ( 'fill' === $align ) {
			$nav_cls .= ' pfh-nav--fill';
		} elseif ( 'start' === $align ) {
			$nav_cls .= ' pfh-nav--start';
		}

		echo '<nav class="' . esc_attr( $nav_cls ) . '" aria-label="' . esc_attr__( 'Hoofdmenu', 'pfh-widgets' ) . '">';
		echo '<ul class="pfh-nav__list">';

		foreach ( $items as $index => $item ) {
			$has_mega = ! empty( $item['hasMega'] );
			$cards    = $has_mega ? $this->mega_cards( $item ) : [];
			$has_mega = $has_mega && ! empty( $cards );

			$li_class = [ 'pfh-nav__item' ];

			if ( $has_mega ) {
				$li_class[] = 'has-mega';
			}

			if ( ! empty( $item['isCurrent'] ) ) {
				$li_class[] = 'is-current';
			}

			$panel_id = 'pfh-mega-' . $this->uid() . '-' . $index;

			echo '<li class="' . esc_attr( implode( ' ', $li_class ) ) . '"' . ( $has_mega ? ' data-pfh-mega-item' : '' ) . '>';

			echo '<a class="pfh-nav__link"' . PFH_Widgets_Helpers::link_attrs( $item['link'] );

			if ( $has_mega ) {
				echo ' aria-expanded="false" aria-controls="' . esc_attr( $panel_id ) . '"';
			}

			echo '>';
			echo '<span class="pfh-nav__label">' . esc_html( $item['label'] ) . '</span>';

			if ( $has_mega && $caret ) {
				echo '<span class="pfh-nav__caret">' . PFH_Widgets_Icons::get( 'chevron' ) . '</span>';
			}

			echo '</a>';

			if ( $has_mega ) {
				$this->render_mega( $cards, $item, $panel_id );
			}

			echo '</li>';
		}

		echo '</ul>';
		echo '</nav>';
	}

	/**
	 * Render one mega menu panel.
	 *
	 * @param array  $cards    Card rows.
	 * @param array  $item     Nav item settings.
	 * @param string $panel_id DOM id.
	 */
	private function render_mega( $cards, $item, $panel_id ) {
		$link_text = isset( $item['megaLinkText'] ) ? $item['megaLinkText'] : 'Bekijk alles';
		$arrow     = $this->is_on( 'megaShowArrow' );

		echo '<div class="pfh-mega" id="' . esc_attr( $panel_id ) . '" data-pfh-mega hidden>';
		echo '<div class="pfh-mega__inner">';
		echo '<div class="pfh-mega__grid">';

		foreach ( $cards as $card ) {
			echo '<a class="pfh-card" href="' . esc_url( $card['url'] ) . '">';
			echo '<span class="pfh-card__media">';

			if ( ! empty( $card['image'] ) ) {
				printf(
					'<img class="pfh-card__img" src="%s" alt="%s" loading="lazy" decoding="async" />',
					esc_url( $card['image'] ),
					esc_attr( $card['title'] )
				);
			}

			echo '</span>';
			echo '<span class="pfh-card__title">' . esc_html( $card['title'] ) . '</span>';

			if ( $link_text ) {
				echo '<span class="pfh-card__link">' . esc_html( $link_text );

				if ( $arrow ) {
					echo PFH_Widgets_Icons::get( 'arrow', 'pfh-card__arrow' );
				}

				echo '</span>';
			}

			echo '</a>';
		}

		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	private function render_actions() {
		echo '<div class="pfh-actions">';

		if ( $this->is_on( 'showSearch' ) ) {
			echo '<button type="button" class="pfh-actions__btn pfh-actions__btn--search" data-pfh-open="search" aria-expanded="false" aria-controls="pfh-search-' . esc_attr( $this->uid() ) . '" aria-label="' . esc_attr__( 'Zoeken', 'pfh-widgets' ) . '">';
			$this->render_action_icon( 'searchIcon', 'search' );
			echo '</button>';
		}

		if ( $this->is_on( 'showCart' ) ) {
			$mode  = (string) $this->get( 'cartMode', 'drawer' );
			$count = PFH_Widgets_Cart::count();

			$badge = '';

			if ( $this->is_on( 'showCartCount' ) ) {
				$badge = sprintf(
					'<span class="pfh-actions__badge%s" data-pfh-cart-count>%s</span>',
					$count > 0 ? '' : ' is-empty',
					esc_html( $count )
				);
			}

			if ( 'link' === $mode ) {
				$fallback = PFH_Widgets_Helpers::has_woocommerce() ? wc_get_cart_url() : home_url( '/' );
				$link     = PFH_Widgets_Helpers::link( $this->get( 'cartLink' ), $fallback );

				echo '<a class="pfh-actions__btn pfh-actions__btn--cart"' . PFH_Widgets_Helpers::link_attrs( $link ) . ' aria-label="' . esc_attr__( 'Winkelwagen', 'pfh-widgets' ) . '">';
				$this->render_action_icon( 'cartIcon', 'cart' );
				echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above with esc_html().
				echo '</a>';
			} else {
				echo '<button type="button" class="pfh-actions__btn pfh-actions__btn--cart" data-pfh-open="cart" aria-expanded="false" aria-controls="pfh-cart-' . esc_attr( $this->uid() ) . '" aria-label="' . esc_attr__( 'Winkelwagen', 'pfh-widgets' ) . '">';
				$this->render_action_icon( 'cartIcon', 'cart' );
				echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above with esc_html().
				echo '</button>';
			}
		}

		if ( $this->is_on( 'showAccount' ) ) {
			$link = PFH_Widgets_Helpers::link( $this->get( 'accountLink' ), $this->account_url() );

			echo '<a class="pfh-actions__btn pfh-actions__btn--account"' . PFH_Widgets_Helpers::link_attrs( $link ) . ' aria-label="' . esc_attr( $this->get( 'accountLabel', 'Mijn account' ) ) . '">';
			$this->render_action_icon( 'accountIcon', 'account' );
			echo '</a>';
		}

		echo '</div>';
	}

	/**
	 * Output an action icon: uploaded image when set, inline SVG otherwise.
	 *
	 * @param string $setting_key Image control key.
	 * @param string $fallback    Inline icon slug.
	 */
	private function render_action_icon( $setting_key, $fallback ) {
		$url = PFH_Widgets_Helpers::image_url( $this->get( $setting_key ), 'full' );

		if ( $url ) {
			printf(
				'<img class="pfh-actions__icon" src="%s" alt="" aria-hidden="true" decoding="async" />',
				esc_url( $url )
			);

			return;
		}

		echo PFH_Widgets_Icons::get( $fallback, 'pfh-actions__icon' );
	}

	/**
	 * Default My Account URL.
	 *
	 * @return string
	 */
	private function account_url() {
		if ( PFH_Widgets_Helpers::has_woocommerce() && function_exists( 'wc_get_page_permalink' ) ) {
			$url = wc_get_page_permalink( 'myaccount' );

			if ( $url ) {
				return $url;
			}
		}

		return wp_login_url();
	}

	/* ---------------------------------------------------------------------
	 * Portal: scrim, search popup, cart drawer, mobile drawer
	 * ------------------------------------------------------------------ */

	/**
	 * @param string $vars Inline CSS variables (repeated so styles survive the move to <body>).
	 */
	private function render_portal( $vars ) {
		echo '<div class="pfh-portal pfh-scope" data-pfh-portal="' . esc_attr( $this->uid() ) . '" style="' . esc_attr( $vars ) . '">';

		echo '<div class="pfh-scrim" data-pfh-scrim hidden></div>';

		$this->render_search_popup();
		$this->render_cart_drawer();
		$this->render_mobile_drawer();

		echo '</div>';
	}

	private function render_search_popup() {
		if ( ! $this->is_on( 'showSearch' ) ) {
			return;
		}

		$post_type = (string) $this->get( 'searchPostType', 'product' );
		$id        = 'pfh-search-' . $this->uid();
		?>
		<div class="pfh-search" id="<?php echo esc_attr( $id ); ?>" data-pfh-panel="search" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Zoeken', 'pfh-widgets' ); ?>" hidden>
			<div class="pfh-search__backdrop" data-pfh-close></div>
			<div class="pfh-search__panel">
				<button type="button" class="pfh-search__close" data-pfh-close aria-label="<?php esc_attr_e( 'Sluiten', 'pfh-widgets' ); ?>"><?php echo PFH_Widgets_Icons::get( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>

				<?php if ( $this->get( 'searchHeading' ) ) : ?>
					<h2 class="pfh-search__heading"><?php echo esc_html( PFH_Widgets_Helpers::dd( $this->get( 'searchHeading' ) ) ); ?></h2>
				<?php endif; ?>

				<form class="pfh-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="pfh-search__icon"><?php echo PFH_Widgets_Icons::get( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input
						class="pfh-search__input"
						type="search"
						name="s"
						value=""
						autocomplete="off"
						placeholder="<?php echo esc_attr( $this->get( 'searchPlaceholder', '' ) ); ?>"
						data-pfh-search-input
					/>
					<?php if ( 'any' !== $post_type ) : ?>
						<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>" />
					<?php endif; ?>
					<button type="submit" class="pfh-search__submit"><?php echo esc_html( $this->get( 'searchButtonText', 'Zoeken' ) ); ?></button>
				</form>

				<?php if ( $this->is_on( 'searchLive' ) ) : ?>
					<div class="pfh-search__output" data-pfh-search-output aria-live="polite"></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private function render_cart_drawer() {
		// With FunnelKit Cart chosen the drawer is still drawn: it is what
		// opens on a page where FunnelKit does not load its own cart.
		if ( ! $this->is_on( 'showCart' ) || ! in_array( $this->get( 'cartMode', 'drawer' ), [ 'drawer', 'funnelkit' ], true ) ) {
			return;
		}

		$labels = $this->cart_labels();
		$id     = 'pfh-cart-' . $this->uid();
		?>
		<div class="pfh-drawer pfh-drawer--right" id="<?php echo esc_attr( $id ); ?>" data-pfh-panel="cart" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $this->get( 'cartTitle', 'Winkelwagen' ) ); ?>" hidden>
			<div class="pfh-drawer__backdrop" data-pfh-close></div>
			<aside class="pfh-drawer__panel">
				<header class="pfh-drawer__head">
					<h2 class="pfh-drawer__title">
						<?php echo esc_html( $this->get( 'cartTitle', 'Winkelwagen' ) ); ?>
						<span class="pfh-drawer__count" data-pfh-cart-count-text>(<?php echo esc_html( PFH_Widgets_Cart::count() ); ?>)</span>
					</h2>
					<button type="button" class="pfh-drawer__close" data-pfh-close aria-label="<?php esc_attr_e( 'Sluiten', 'pfh-widgets' ); ?>"><?php echo PFH_Widgets_Icons::get( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</header>

				<div class="pfh-drawer__body" data-pfh-cart-body>
					<?php echo PFH_Widgets_Cart::render_body( $labels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_body(). ?>
				</div>

				<footer class="pfh-drawer__foot" data-pfh-cart-foot>
					<?php echo PFH_Widgets_Cart::render_foot( $labels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_foot(). ?>
				</footer>

				<div class="pfh-drawer__loader" data-pfh-cart-loader aria-hidden="true"></div>
			</aside>
		</div>
		<?php
	}

	private function render_mobile_drawer() {
		$items = $this->nav_items();
		$id    = 'pfh-mobile-' . $this->uid();
		?>
		<div class="pfh-drawer pfh-drawer--left pfh-mobile" id="<?php echo esc_attr( $id ); ?>" data-pfh-panel="mobile" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $this->get( 'mobileTitle', 'Menu' ) ); ?>" hidden>
			<div class="pfh-drawer__backdrop" data-pfh-close></div>
			<aside class="pfh-drawer__panel">
				<header class="pfh-drawer__head">
					<h2 class="pfh-drawer__title"><?php echo esc_html( $this->get( 'mobileTitle', 'Menu' ) ); ?></h2>
					<button type="button" class="pfh-drawer__close" data-pfh-close aria-label="<?php esc_attr_e( 'Sluiten', 'pfh-widgets' ); ?>"><?php echo PFH_Widgets_Icons::get( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</header>

				<div class="pfh-drawer__body">
					<ul class="pfh-mobile__list">
						<?php
						foreach ( $items as $index => $item ) {
							$cards   = ! empty( $item['hasMega'] ) ? $this->mega_cards( $item ) : [];
							$sub_id  = $id . '-sub-' . $index;
							$has_sub = ! empty( $cards );
							?>
							<li class="pfh-mobile__item<?php echo $has_sub ? ' has-sub' : ''; ?>">
								<div class="pfh-mobile__row">
									<a class="pfh-mobile__link"<?php echo PFH_Widgets_Helpers::link_attrs( $item['link'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $item['label'] ); ?></a>
									<?php if ( $has_sub ) : ?>
										<button type="button" class="pfh-mobile__toggle" data-pfh-accordion="<?php echo esc_attr( $sub_id ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $sub_id ); ?>" aria-label="<?php esc_attr_e( 'Submenu openen', 'pfh-widgets' ); ?>">
											<?php echo PFH_Widgets_Icons::get( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</button>
									<?php endif; ?>
								</div>

								<?php if ( $has_sub ) : ?>
									<ul class="pfh-mobile__sub" id="<?php echo esc_attr( $sub_id ); ?>" hidden>
										<?php foreach ( $cards as $card ) : ?>
											<li>
												<a class="pfh-mobile__sub-link" href="<?php echo esc_url( $card['url'] ); ?>">
													<?php if ( ! empty( $card['image'] ) ) : ?>
														<img class="pfh-mobile__sub-img" src="<?php echo esc_url( $card['image'] ); ?>" alt="" loading="lazy" decoding="async" />
													<?php endif; ?>
													<span><?php echo esc_html( $card['title'] ); ?></span>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</li>
							<?php
						}
						?>
					</ul>
				</div>

				<?php if ( $this->is_on( 'mobileShowAccount' ) || $this->get( 'mobileFooterText' ) ) : ?>
					<footer class="pfh-drawer__foot pfh-mobile__foot">
						<?php if ( $this->is_on( 'mobileShowAccount' ) ) : ?>
							<?php $account = PFH_Widgets_Helpers::link( $this->get( 'accountLink' ), $this->account_url() ); ?>
							<a class="pfh-mobile__account"<?php echo PFH_Widgets_Helpers::link_attrs( $account ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<?php echo PFH_Widgets_Icons::get( 'account' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php echo esc_html( $this->get( 'accountLabel', 'Mijn account' ) ); ?></span>
							</a>
						<?php endif; ?>

						<?php if ( $this->get( 'mobileFooterText' ) ) : ?>
							<p class="pfh-mobile__note"><?php echo esc_html( PFH_Widgets_Helpers::dd( $this->get( 'mobileFooterText' ) ) ); ?></p>
						<?php endif; ?>
					</footer>
				<?php endif; ?>
			</aside>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Data
	 * ------------------------------------------------------------------ */

	/**
	 * Normalised nav items: label, resolved link, mega settings, current flag.
	 *
	 * @return array<int, array>
	 */
	private function nav_items() {
		if ( null !== $this->items ) {
			return $this->items;
		}

		$this->items   = [];
		$raw     = (array) $this->get( 'navItems', [] );
		$current = $this->is_on( 'navHighlightCurrent' ) ? untrailingslashit( $this->current_url() ) : '';

		foreach ( $raw as $row ) {
			if ( empty( $row['label'] ) ) {
				continue;
			}

			$link = PFH_Widgets_Helpers::link( isset( $row['link'] ) ? $row['link'] : null, '#' );

			$row['label']     = PFH_Widgets_Helpers::dd( $row['label'] );
			$row['link']      = $link;
			$row['isCurrent'] = $current && '#' !== $link['href'] && untrailingslashit( $link['href'] ) === $current;

			$this->items[] = $row;
		}

		return $this->items;
	}

	/**
	 * Current front-end URL, used for the active menu underline.
	 *
	 * @return string
	 */
	private function current_url() {
		global $wp;

		if ( ! isset( $wp->request ) ) {
			return '';
		}

		return home_url( $wp->request );
	}

	/**
	 * Build the mega menu cards for one nav item.
	 *
	 * @param array $item Nav item settings.
	 * @return array<int, array{title:string, url:string, image:string}>
	 */
	private function mega_cards( $item ) {
		$source = isset( $item['megaSource'] ) ? $item['megaSource'] : 'product_cat';
		$limit  = isset( $item['megaLimit'] ) ? max( 1, (int) $item['megaLimit'] ) : 5;

		if ( 'custom' === $source ) {
			$cards = [];

			foreach ( PFH_Widgets_Helpers::parse_lines( isset( $item['megaCustom'] ) ? $item['megaCustom'] : '' ) as $row ) {
				if ( empty( $row[0] ) ) {
					continue;
				}

				$cards[] = [
					'title' => PFH_Widgets_Helpers::dd( $row[0] ),
					'url'   => isset( $row[1] ) ? $row[1] : '#',
					'image' => isset( $row[2] ) ? $row[2] : '',
				];
			}

			return array_slice( $cards, 0, $limit );
		}

		if ( ! PFH_Widgets_Helpers::has_woocommerce() ) {
			return [];
		}

		return 'products' === $source
			? $this->mega_cards_from_products( $item, $limit )
			: $this->mega_cards_from_terms( $item, $limit );
	}

	/**
	 * @param array $item  Nav item settings.
	 * @param int   $limit Max cards.
	 * @return array
	 */
	private function mega_cards_from_products( $item, $limit ) {
		$orderby = isset( $item['megaOrderby'] ) ? $item['megaOrderby'] : 'menu_order';

		$args = [
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
		];

		if ( 'popularity' === $orderby ) {
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
		} elseif ( 'date' === $orderby ) {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		} elseif ( in_array( $orderby, [ 'title', 'rand' ], true ) ) {
			$args['orderby'] = $orderby;
		}

		if ( ! empty( $item['megaProdCat'] ) ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => [ (int) $item['megaProdCat'] ],
				],
			];
		}

		$query = new WP_Query( $args );
		$cards = [];

		foreach ( $query->posts as $post ) {
			$image = get_the_post_thumbnail_url( $post, 'medium_large' );

			$cards[] = [
				'title' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
				'image' => $image ? $image : $this->placeholder_image(),
			];
		}

		wp_reset_postdata();

		return $cards;
	}

	/**
	 * @param array $item  Nav item settings.
	 * @param int   $limit Max cards.
	 * @return array
	 */
	private function mega_cards_from_terms( $item, $limit ) {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return [];
		}

		$include = array_filter( array_map( 'trim', explode( ',', (string) ( isset( $item['megaInclude'] ) ? $item['megaInclude'] : '' ) ) ) );
		$terms   = [];

		if ( ! empty( $include ) ) {
			foreach ( $include as $ref ) {
				$term = is_numeric( $ref )
					? get_term( (int) $ref, 'product_cat' )
					: get_term_by( 'slug', $ref, 'product_cat' );

				if ( $term && ! is_wp_error( $term ) ) {
					$terms[] = $term;
				}
			}
		} else {
			$args = [
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => isset( $item['megaParent'] ) ? (int) $item['megaParent'] : 0,
				'number'     => $limit,
				'orderby'    => 'menu_order',
			];

			$found = get_terms( $args );

			if ( is_wp_error( $found ) ) {
				$args['orderby'] = 'name';
				$found           = get_terms( $args );
			}

			if ( ! is_wp_error( $found ) ) {
				$terms = $found;
			}
		}

		$cards = [];

		foreach ( array_slice( $terms, 0, $limit ) as $term ) {
			$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
			$image    = $thumb_id ? wp_get_attachment_image_url( (int) $thumb_id, 'medium_large' ) : '';

			$cards[] = [
				'title' => $term->name,
				'url'   => get_term_link( $term ),
				'image' => $image ? $image : $this->placeholder_image(),
			];
		}

		return array_filter(
			$cards,
			static function ( $card ) {
				return ! is_wp_error( $card['url'] );
			}
		);
	}

	/**
	 * WooCommerce placeholder image, empty string when unavailable.
	 *
	 * @return string
	 */
	private function placeholder_image() {
		return function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'medium_large' ) : '';
	}
}
