<?php
/**
 * Bricks element: Products For Home info section.
 *
 * Full-bleed band over a soft gradient with any number of alternating rows —
 * a square-ish photo on one side, a Playfair heading with an italic underlined
 * accent, body copy and a pill button on the other.
 *
 * Figma geometry (1440 frame):
 *   background artwork 1440 x 1380   photo 522 x 500   content column 1110
 *   173 + 500 + 34 + 500 + 173 = 1380
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Info extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-info';
	public $icon         = 'ti-layout-media-left-alt';
	public $css_selector = '.pfh-info';

	const BASE      = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/';
	const BG_URL    = self::BASE . 'Frame-469975-1-1.jpg';
	const ONE_URL   = self::BASE . 'Frame-469976.jpg';
	const TWO_URL   = self::BASE . 'Frame-469976-1.jpg';

	/** Body copy shared by both designed rows. */
	const COPY = "At Products for Home, you will find the very best Greek delicacies. From our Greek raw honey to organic extra virgin olive oil, every product is carefully selected for the highest quality and authenticity.\n\nAt Products for Home, you will find the very best Greek delicacies. From our Greek raw honey to organic extra virgin olive oil, every product.";

	public function get_label() {
		return esc_html__( 'PFH Info Section', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'info', 'about', 'alternating', 'zigzag', 'image', 'text', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::info();
	}

	public function set_control_groups() {
		$this->control_groups['rows']   = [ 'title' => esc_html__( 'Rows', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']   = [ 'title' => esc_html__( 'Typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['button'] = [ 'title' => esc_html__( 'Button', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['media']  = [ 'title' => esc_html__( 'Image', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout'] = [ 'title' => esc_html__( 'Layout & background', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->row_controls();
		$this->type_controls();
		$this->button_controls();
		$this->media_controls();
		$this->layout_controls();
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
	 * Rows
	 * ------------------------------------------------------------------ */

	private function row_controls() {
		$this->controls['startSide'] = [
			'tab'         => 'content',
			'group'       => 'rows',
			'label'       => esc_html__( 'First row image side', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'left'  => esc_html__( 'Left', 'pfh-widgets' ),
				'right' => esc_html__( 'Right', 'pfh-widgets' ),
			],
			'default'     => 'left',
			'description' => esc_html__( 'Rows alternate from here. Any row can override it below.', 'pfh-widgets' ),
		];

		$this->controls['rows'] = [
			'tab'           => 'content',
			'group'         => 'rows',
			'label'         => esc_html__( 'Rows', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'default'       => $this->default_rows(),
			'fields'        => [
				'image'    => [
					'label' => esc_html__( 'Image', 'pfh-widgets' ),
					'type'  => 'image',
				],
				'alt'      => [
					'label'       => esc_html__( 'Image alt text', 'pfh-widgets' ),
					'type'        => 'text',
					'description' => esc_html__( 'Leave empty to use the media library alt text.', 'pfh-widgets' ),
				],
				'imagePos' => [
					'label'       => esc_html__( 'Image position', 'pfh-widgets' ),
					'type'        => 'text',
					'inline'      => true,
					'placeholder' => 'center center',
				],
				'side'     => [
					'label'       => esc_html__( 'Image side', 'pfh-widgets' ),
					'type'        => 'select',
					'inline'      => true,
					'options'     => [
						'left'  => esc_html__( 'Left', 'pfh-widgets' ),
						'right' => esc_html__( 'Right', 'pfh-widgets' ),
					],
					'placeholder' => esc_html__( 'Alternate automatically', 'pfh-widgets' ),
				],
				'title'    => [
					'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'Line breaks are kept. Wrap the italic underlined part in <em>…</em>, as in the design.', 'pfh-widgets' ),
				],
				'text'     => [
					'label'       => esc_html__( 'Text', 'pfh-widgets' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'Separate paragraphs with a blank line. Links and <strong> are allowed.', 'pfh-widgets' ),
				],
				'btnLabel' => [
					'label'   => esc_html__( 'Button label', 'pfh-widgets' ),
					'type'    => 'text',
					'inline'  => true,
					'default' => 'Shop Now',
				],
				'link'     => [
					'label' => esc_html__( 'Button link', 'pfh-widgets' ),
					'type'  => 'link',
				],
			],
		];
	}

	private function default_rows() {
		$title = "The tastiest Greek\n<em>delicatessen shop</em>";

		return [
			[
				'image'    => [ 'url' => self::ONE_URL ],
				'alt'      => 'Greek pine honey',
				'title'    => $title,
				'text'     => self::COPY,
				'btnLabel' => 'Shop Now',
			],
			[
				'image'    => [ 'url' => self::TWO_URL ],
				'alt'      => 'Greek lemonade',
				'title'    => $title,
				'text'     => self::COPY,
				'btnLabel' => 'Shop Now',
			],
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
			'max'     => 80,
			'inline'  => true,
			'default' => 28,
		];

		$this->controls['headSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 60,
			'inline'  => true,
			'default' => 24,
		];

		$this->controls['headWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '400',
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
			'default' => 1.15,
		];

		$this->controls['headColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#22504a' ],
		];

		$this->controls['headAccent'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Accent colour', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#2f7e7c' ],
			'description' => esc_html__( 'Used for the italic underlined part of the heading.', 'pfh-widgets' ),
		];

		$this->controls['headAccentWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Accent weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '400',
		];

		$this->controls['headUnderlineOffset'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Accent underline offset (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 20,
			'inline'  => true,
			'default' => 6,
		];

		$this->controls['headUnderlineWidth'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Accent underline thickness (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 6,
			'inline'  => true,
			'default' => 1,
		];

		$this->controls['textSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 28,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['textSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 15,
		];

		$this->controls['textWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '300',
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
			'default' => [ 'hex' => '#3d4a4d' ],
		];

		$this->controls['linkColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Inline link colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#2f7e7c' ],
		];

		$this->controls['textWidth'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Text column max width (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 200,
			'max'         => 900,
			'inline'      => true,
			'default'     => 464,
			'description' => esc_html__( 'Figma measures the paragraph frame at 464px.', 'pfh-widgets' ),
		];

		$this->controls['paraGap'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Gap between paragraphs (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['gapHeadText'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Gap heading → text (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 22,
		];

		$this->controls['gapTextBtn'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Gap text → button (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 24,
		];
	}

	/* ---------------------------------------------------------------------
	 * Button
	 * ------------------------------------------------------------------ */

	private function button_controls() {
		$this->controls['btnIcon'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Button icon', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'arrow-ne' => esc_html__( 'Arrow up-right', 'pfh-widgets' ),
				'none'     => esc_html__( 'No icon', 'pfh-widgets' ),
			],
			'default' => 'arrow-ne',
		];

		$this->controls['btnHeight'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 28,
			'max'     => 80,
			'inline'  => true,
			'default' => 40,
		];

		$this->controls['btnPadding'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Side padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 80,
			'inline'  => true,
			'default' => 40,
		];

		$this->controls['btnGap'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Label → icon gap (px)', 'pfh-widgets' ),
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
			'max'     => 100,
			'inline'  => true,
			'default' => 70,
		];

		$this->controls['btnSize'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Label size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['btnWeight'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Label weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '500',
		];

		$this->controls['btnIconSize'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Icon size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 32,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['btnBg'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#4a6d6d' ],
		];

		$this->controls['btnColor'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Label colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['btnHoverBg'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Hover background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#22504a' ],
		];

		$this->controls['btnHoverColor'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Hover label colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['btnShift'] = [
			'tab'         => 'content',
			'group'       => 'button',
			'label'       => esc_html__( 'Hover lift (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 10,
			'inline'      => true,
			'default'     => 2,
			'description' => esc_html__( 'How far the button rises on hover. 0 disables it.', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Image
	 * ------------------------------------------------------------------ */

	private function media_controls() {
		$this->controls['imageWidth'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Image width (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 120,
			'max'         => 1200,
			'inline'      => true,
			'default'     => 522,
			'description' => esc_html__( 'Measured against the content width, then held as a proportion so it scales down cleanly.', 'pfh-widgets' ),
		];

		$this->controls['imageHeight'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Image height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 120,
			'max'     => 1200,
			'inline'  => true,
			'default' => 500,
		];

		$this->controls['imageRadius'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 120,
			'inline'      => true,
			'default'     => 30,
			'description' => esc_html__( 'Also clips the black corners baked into flattened JPG artwork.', 'pfh-widgets' ),
		];

		$this->controls['imageFit'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Image fit', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'cover'   => esc_html__( 'Cover', 'pfh-widgets' ),
				'contain' => esc_html__( 'Contain', 'pfh-widgets' ),
			],
			'default' => 'cover',
		];

		$this->controls['imagePosition'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Image position', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => 'center center',
			'description' => esc_html__( 'Any CSS object-position value. Rows can override it.', 'pfh-widgets' ),
		];

		$this->controls['imageZoom'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Image zoom (%)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 100,
			'max'     => 160,
			'inline'  => true,
			'default' => 100,
		];

		$this->controls['imageBg'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Placeholder colour', 'pfh-widgets' ),
			'type'        => 'color',
			'description' => esc_html__( 'Shown behind the photo while it loads.', 'pfh-widgets' ),
		];

		$this->controls['hoverEffect'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Hover effect', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'none' => esc_html__( 'None (as designed)', 'pfh-widgets' ),
				'zoom' => esc_html__( 'Gentle zoom', 'pfh-widgets' ),
				'lift' => esc_html__( 'Lift and shadow', 'pfh-widgets' ),
			],
			'default'     => 'none',
			'description' => esc_html__( 'The photo stays clipped to its corner radius, so nothing crops on hover.', 'pfh-widgets' ),
		];

		$this->controls['mobileOrder'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Mobile order', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'image'  => esc_html__( 'Image first, then text', 'pfh-widgets' ),
				'text'   => esc_html__( 'Text first, then image', 'pfh-widgets' ),
				'follow' => esc_html__( 'Follow each row’s desktop side', 'pfh-widgets' ),
			],
			'default'     => 'image',
			'description' => esc_html__( 'Only affects the stacked layout below 992px.', 'pfh-widgets' ),
		];

		$this->controls['stackGap'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'       => esc_html__( 'Stacked image ↔ text gap (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 100,
			'inline'      => true,
			'default'     => 28,
			'description' => esc_html__( 'Below 768px the row stacks into a single column capped at the image width.', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Layout & background
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

		$this->controls['paddingY'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Top / bottom padding (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 400,
			'inline'      => true,
			'default'     => 173,
			'description' => esc_html__( '173 + 500 + 34 + 500 + 173 = the 1380px section in Figma.', 'pfh-widgets' ),
		];

		$this->controls['rowGap'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Gap between rows (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 34,
		];

		$this->controls['colGap'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Gap image ↔ text (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 72,
		];

		$this->controls['bgImage'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background image', 'pfh-widgets' ),
			'type'    => 'image',
			'default' => [ 'url' => self::BG_URL ],
		];

		$this->controls['bgColor'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Background colour', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#f2f6f2' ],
			'description' => esc_html__( 'Sits under the artwork and fills anything it does not cover.', 'pfh-widgets' ),
		];

		$this->controls['bgSize'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background size', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'cover'      => esc_html__( 'Cover', 'pfh-widgets' ),
				'contain'    => esc_html__( 'Contain', 'pfh-widgets' ),
				'100% 100%'  => esc_html__( 'Stretch', 'pfh-widgets' ),
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
			'label'   => esc_html__( 'Section corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 0,
		];
	}

	/* ---------------------------------------------------------------------
	 * Setting accessors
	 * ------------------------------------------------------------------ */

	private function get( $key, $default = null ) {
		return $this->setting( $key, $default );
	}

	/**
	 * Split a textarea into paragraphs on blank lines.
	 *
	 * Single newlines are left alone so the copy reflows with the column
	 * instead of carrying the author's line breaks onto small screens.
	 *
	 * @param mixed $text Raw control value.
	 * @return array<int, string>
	 */
	private function paragraphs( $text ) {
		$text = (string) $text;

		if ( '' === trim( $text ) ) {
			return [];
		}

		$out = [];

		foreach ( (array) preg_split( '/(?:\r\n|\r|\n){2,}/', $text ) as $block ) {
			$block = trim( (string) $block );

			if ( '' !== $block ) {
				$out[] = $block;
			}
		}

		return $out;
	}

	/**
	 * Which side the photo sits on for a given row.
	 *
	 * @param array $row   Row settings.
	 * @param int   $index Zero based row index.
	 * @return string 'left'|'right'
	 */
	private function side_for( $row, $index ) {
		$side = isset( $row['side'] ) ? (string) $row['side'] : '';

		if ( 'left' === $side || 'right' === $side ) {
			return $side;
		}

		$start = 'right' === (string) $this->get( 'startSide', 'left' ) ? 'right' : 'left';
		$flip  = 'left' === $start ? 'right' : 'left';

		return 0 === $index % 2 ? $start : $flip;
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$rows = $this->get( 'rows', [] );
		$rows = is_array( $rows ) ? array_values( $rows ) : [];

		$classes = [
			'pfh-info',
			'pfh-scope',
			'pfh-morder-' . (string) $this->get( 'mobileOrder', 'image' ),
			'pfh-hov-' . (string) $this->get( 'hoverEffect', 'none' ),
		];

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-info__inner">';

		if ( $rows ) {
			echo '<div class="pfh-info__rows">';

			foreach ( $rows as $index => $row ) {
				$this->render_row( (array) $row, (int) $index );
			}

			echo '</div>';
		} elseif ( PFH_Widgets_Helpers::is_builder_context() ) {
			printf(
				'<p class="pfh-info__empty">%s</p>',
				esc_html__( 'Add a row to the Info Section.', 'pfh-widgets' )
			);
		}

		echo '</div>';
		echo '</section>';
	}

	/**
	 * @param array $row   Row settings.
	 * @param int   $index Zero based row index.
	 */
	private function render_row( $row, $index ) {
		$image = PFH_Widgets_Helpers::image_url( isset( $row['image'] ) ? $row['image'] : null, 'large' );
		$title = isset( $row['title'] ) ? (string) $row['title'] : '';
		$paras = $this->paragraphs( isset( $row['text'] ) ? $row['text'] : '' );
		$label = isset( $row['btnLabel'] ) ? trim( (string) $row['btnLabel'] ) : '';
		$link  = PFH_Widgets_Helpers::link( isset( $row['link'] ) ? $row['link'] : null );

		$style = PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-i-media-pos' => ! empty( $row['imagePos'] ) ? $row['imagePos'] : '',
			]
		);

		printf(
			'<article class="pfh-info__row pfh-media-%s"%s>',
			esc_attr( $this->side_for( $row, $index ) ),
			$style ? ' style="' . esc_attr( $style ) . '"' : ''
		);

		echo '<div class="pfh-info__media">';

		if ( $image ) {
			$alt = isset( $row['alt'] ) ? (string) $row['alt'] : '';

			if ( '' === $alt && ! empty( $row['image']['id'] ) ) {
				$alt = (string) get_post_meta( (int) $row['image']['id'], '_wp_attachment_image_alt', true );
			}

			printf(
				'<img class="pfh-info__img" src="%s" alt="%s" width="%d" height="%d" loading="lazy" decoding="async" />',
				esc_url( $image ),
				esc_attr( $alt ),
				(int) $this->get( 'imageWidth', 522 ),
				(int) $this->get( 'imageHeight', 500 )
			);
		}

		echo '</div>';

		echo '<div class="pfh-info__body">';

		if ( '' !== trim( $title ) ) {
			echo '<h2 class="pfh-info__title">' . wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $title ) ) ) . '</h2>';
		}

		if ( $paras ) {
			echo '<div class="pfh-info__text">';

			foreach ( $paras as $para ) {
				echo '<p>' . wp_kses_post( PFH_Widgets_Helpers::dd( $para ) ) . '</p>';
			}

			echo '</div>';
		}

		if ( '' !== $label ) {
			$icon = (string) $this->get( 'btnIcon', 'arrow-ne' );

			// A button with nowhere to go would be a lie to keyboard users, so
			// it renders as a span until a link is set.
			$tag   = '' !== $link['href'] ? 'a' : 'span';
			$attrs = '' !== $link['href'] ? PFH_Widgets_Helpers::link_attrs( $link ) : '';

			echo '<' . $tag . ' class="pfh-info__btn"' . $attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().
			echo '<span>' . esc_html( PFH_Widgets_Helpers::dd( $label ) ) . '</span>';

			if ( 'none' !== $icon ) {
				echo PFH_Widgets_Icons::get( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG markup.
			}

			echo '</' . $tag . '>';
		}

		echo '</div>';
		echo '</article>';
	}

	/* ---------------------------------------------------------------------
	 * Tokens
	 * ------------------------------------------------------------------ */

	private function build_vars() {
		$stack = ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif';
		$head  = trim( (string) $this->get( 'headFamily', 'Playfair Display' ) );
		$bg    = PFH_Widgets_Helpers::image_url( $this->get( 'bgImage' ), 'full' );

		// The column split is authored in px against the content width, then
		// emitted as a proportion: exact at 1110, and in proportion below it.
		$container = max( 1.0, (float) $this->get( 'containerWidth', 1140 ) );
		$media_w   = max( 0.0, (float) $this->get( 'imageWidth', 522 ) );
		$media_h   = max( 1.0, (float) $this->get( 'imageHeight', 500 ) );
		$col_gap   = max( 0.0, (float) $this->get( 'colGap', 72 ) );

		$media_pct = round( min( 90.0, $media_w / $container * 100 ), 4 );
		$gap_pct   = round( min( 40.0, $col_gap / $container * 100 ), 4 );
		$shift     = abs( (float) $this->get( 'btnShift', 2 ) );

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-container'          => PFH_Widgets_Helpers::unit( $container ),
				'--pfh-gutter-set'         => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-i-pad-y-set'        => PFH_Widgets_Helpers::unit( $this->get( 'paddingY', 173 ) ),
				'--pfh-i-row-gap-set'      => PFH_Widgets_Helpers::unit( $this->get( 'rowGap', 34 ) ),
				'--pfh-i-col-gap-set'      => $gap_pct . '%',
				'--pfh-i-media-w-set'      => $media_pct . '%',
				'--pfh-i-stack-gap'        => PFH_Widgets_Helpers::unit( $this->get( 'stackGap', 28 ) ),
				'--pfh-i-stack-max'        => PFH_Widgets_Helpers::unit( $media_w ),

				'--pfh-i-bg'               => PFH_Widgets_Helpers::color( $this->get( 'bgColor' ), '#f2f6f2' ),
				'--pfh-i-bg-img'           => $bg ? 'url(' . $bg . ')' : 'none',
				'--pfh-i-bg-size'          => $this->get( 'bgSize', 'cover' ),
				'--pfh-i-bg-pos'           => $this->get( 'bgPosition', 'center center' ),
				'--pfh-i-radius'           => PFH_Widgets_Helpers::unit( $this->get( 'radius', 0 ) ),

				'--pfh-i-media-ratio'      => $media_w . ' / ' . $media_h,
				'--pfh-i-media-radius'     => PFH_Widgets_Helpers::unit( $this->get( 'imageRadius', 30 ) ),
				'--pfh-i-media-fit'        => $this->get( 'imageFit', 'cover' ),
				'--pfh-i-media-pos'        => $this->get( 'imagePosition', 'center center' ),
				'--pfh-i-media-bg'         => PFH_Widgets_Helpers::color( $this->get( 'imageBg' ) ),
				'--pfh-i-media-zoom'       => round( max( 100.0, (float) $this->get( 'imageZoom', 100 ) ) / 100, 4 ),

				'--pfh-i-text-w-set'       => PFH_Widgets_Helpers::unit( $this->get( 'textWidth', 464 ) ),

				'--pfh-head-font'          => $head ? $head . $stack : 'inherit',
				'--pfh-head-size-set'      => PFH_Widgets_Helpers::unit( $this->get( 'headSize', 28 ) ),
				'--pfh-head-size-m'        => PFH_Widgets_Helpers::unit( $this->get( 'headSizeMobile', 24 ) ),
				'--pfh-head-weight'        => $this->get( 'headWeight', '400' ),
				'--pfh-head-lh'            => $this->get( 'headLineHeight', 1.15 ),
				'--pfh-head-color'         => PFH_Widgets_Helpers::color( $this->get( 'headColor' ), '#22504a' ),
				'--pfh-head-accent'        => PFH_Widgets_Helpers::color( $this->get( 'headAccent' ), '#2f7e7c' ),
				'--pfh-head-accent-weight' => $this->get( 'headAccentWeight', '400' ),
				'--pfh-head-ul-offset'     => PFH_Widgets_Helpers::unit( $this->get( 'headUnderlineOffset', 6 ) ),
				'--pfh-head-ul-width'      => PFH_Widgets_Helpers::unit( $this->get( 'headUnderlineWidth', 1 ) ),

				'--pfh-txt-size-set'       => PFH_Widgets_Helpers::unit( $this->get( 'textSize', 16 ) ),
				'--pfh-txt-size-m'         => PFH_Widgets_Helpers::unit( $this->get( 'textSizeMobile', 15 ) ),
				'--pfh-txt-weight'         => $this->get( 'textWeight', '300' ),
				'--pfh-txt-lh'             => $this->get( 'textLineHeight', 1.5 ),
				'--pfh-txt-color'          => PFH_Widgets_Helpers::color( $this->get( 'textColor' ), '#3d4a4d' ),
				'--pfh-txt-gap'            => PFH_Widgets_Helpers::unit( $this->get( 'paraGap', 12 ) ),
				'--pfh-link-color'         => PFH_Widgets_Helpers::color( $this->get( 'linkColor' ), '#2f7e7c' ),

				'--pfh-gap-1'              => PFH_Widgets_Helpers::unit( $this->get( 'gapHeadText', 22 ) ),
				'--pfh-gap-2'              => PFH_Widgets_Helpers::unit( $this->get( 'gapTextBtn', 24 ) ),

				'--pfh-btn-h'              => PFH_Widgets_Helpers::unit( $this->get( 'btnHeight', 40 ) ),
				'--pfh-btn-pad-set'        => PFH_Widgets_Helpers::unit( $this->get( 'btnPadding', 40 ) ),
				'--pfh-btn-gap'            => PFH_Widgets_Helpers::unit( $this->get( 'btnGap', 10 ) ),
				'--pfh-btn-radius'         => PFH_Widgets_Helpers::unit( $this->get( 'btnRadius', 70 ) ),
				'--pfh-btn-size'           => PFH_Widgets_Helpers::unit( $this->get( 'btnSize', 14 ) ),
				'--pfh-btn-weight'         => $this->get( 'btnWeight', '500' ),
				'--pfh-btn-bg'             => PFH_Widgets_Helpers::color( $this->get( 'btnBg' ), '#4a6d6d' ),
				'--pfh-btn-color'          => PFH_Widgets_Helpers::color( $this->get( 'btnColor' ), '#ffffff' ),
				'--pfh-btn-hover'          => PFH_Widgets_Helpers::color( $this->get( 'btnHoverBg' ), '#22504a' ),
				'--pfh-btn-hover-color'    => PFH_Widgets_Helpers::color( $this->get( 'btnHoverColor' ), '#ffffff' ),
				'--pfh-btn-icon'           => PFH_Widgets_Helpers::unit( $this->get( 'btnIconSize', 14 ) ),
				'--pfh-btn-shift'          => $shift > 0 ? '-' . PFH_Widgets_Helpers::unit( $shift ) : '0px',
			]
		);
	}
}
