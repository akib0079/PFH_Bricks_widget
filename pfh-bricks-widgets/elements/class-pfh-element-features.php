<?php
/**
 * Bricks element: Products For Home highlighted features.
 *
 * A header row (heading left, intro right) over a bento grid. Every tile is a
 * repeater row that either shows a photo or lays a title and copy over a
 * pre-composed card background, with its own column and row span.
 *
 * Figma geometry (1440 frame): content 1110, 3 columns, 20 gutter — column
 * 356.67, row 234, grid 3 x 234 + 2 x 20 = 742.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Features extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-features';
	public $icon         = 'ti-layout-grid2';
	public $css_selector = '.pfh-hf';

	const BASE = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/';

	/** Pre-composed card grounds — the decoration is baked into the artwork. */
	const CARD_HONEY = self::BASE . 'Frame-29.jpg';
	const CARD_LEMON = self::BASE . 'Frame-35.jpg';
	const CARD_OLIVE = self::BASE . 'Frame-36.jpg';

	/** Photography. */
	const PHOTO_OIL   = self::BASE . 'Frame-34.jpg';
	const PHOTO_JARS  = self::BASE . 'Frame-33.jpg';
	const PHOTO_JUICE = self::BASE . 'Frame-470049.jpg';

	public function get_label() {
		return esc_html__( 'PFH Highlighted Features', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'features', 'bento', 'grid', 'highlight', 'mosaic', 'usp', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::features();
	}

	public function set_control_groups() {
		$this->control_groups['head']   = [ 'title' => esc_html__( 'Heading', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['tiles']  = [ 'title' => esc_html__( 'Tiles', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']   = [ 'title' => esc_html__( 'Typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['grid']   = [ 'title' => esc_html__( 'Grid', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['motion'] = [ 'title' => esc_html__( 'Hover & reveal', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout'] = [ 'title' => esc_html__( 'Layout & background', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->head_controls();
		$this->tile_controls();
		$this->type_controls();
		$this->grid_controls();
		$this->motion_controls();
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

	private function span_options() {
		return [
			'1' => esc_html__( '1', 'pfh-widgets' ),
			'2' => esc_html__( '2', 'pfh-widgets' ),
			'3' => esc_html__( '3', 'pfh-widgets' ),
			'4' => esc_html__( '4', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Heading
	 * ------------------------------------------------------------------ */

	private function head_controls() {
		$this->controls['heading'] = [
			'tab'         => 'content',
			'group'       => 'head',
			'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'        => 'textarea',
			'default'     => "Whispers of the Aegean,\nCrafted in <em>pure harmony</em>",
			'description' => esc_html__( 'Line breaks are kept. Wrap the italic underlined part in <em>…</em>.', 'pfh-widgets' ),
		];

		$this->controls['lede'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro text', 'pfh-widgets' ),
			'type'    => 'textarea',
			'default' => 'A curated selection inspired by sun-drenched Greek landscapes — where olives, honey, and fresh juices meet timeless craftsmanship and natural elegance.',
		];

		$this->controls['headingTag'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading tag', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'h2'  => 'H2',
				'h3'  => 'H3',
				'div' => 'div',
			],
			'default' => 'h2',
		];

		$this->controls['headWidth'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading max width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 200,
			'max'     => 900,
			'inline'  => true,
			'default' => 417,
		];

		$this->controls['ledeWidth'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro max width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 160,
			'max'     => 900,
			'inline'  => true,
			'default' => 352,
		];

		$this->controls['headGap'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Gap header → grid (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 160,
			'inline'  => true,
			'default' => 56,
		];
	}

	/* ---------------------------------------------------------------------
	 * Tiles
	 * ------------------------------------------------------------------ */

	private function tile_controls() {
		$this->controls['tiles'] = [
			'tab'           => 'content',
			'group'         => 'tiles',
			'label'         => esc_html__( 'Tiles', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'default'       => $this->default_tiles(),
			'description'   => esc_html__( 'Tiles fill the grid in order. Give a tile a column or row span to make it a feature block.', 'pfh-widgets' ),
			'fields'        => [
				'image'      => [
					'label'       => esc_html__( 'Artwork', 'pfh-widgets' ),
					'type'        => 'image',
					'description' => esc_html__( 'The photo, or the card ground a title sits on.', 'pfh-widgets' ),
				],
				'alt'        => [
					'label'       => esc_html__( 'Describe the artwork', 'pfh-widgets' ),
					'type'        => 'text',
					'description' => esc_html__( 'Read out by screen readers on tiles with no visible text.', 'pfh-widgets' ),
				],
				'imagePos'   => [
					'label'       => esc_html__( 'Artwork position', 'pfh-widgets' ),
					'type'        => 'text',
					'inline'      => true,
					'placeholder' => 'center center',
				],
				'title'      => [
					'label'       => esc_html__( 'Title', 'pfh-widgets' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'Leave empty for a photo-only tile. Wrap the italic line in <em>…</em>.', 'pfh-widgets' ),
				],
				'text'       => [
					'label' => esc_html__( 'Text', 'pfh-widgets' ),
					'type'  => 'textarea',
				],
				'titleColor' => [
					'label' => esc_html__( 'Title colour', 'pfh-widgets' ),
					'type'  => 'color',
				],
				'textColor'  => [
					'label' => esc_html__( 'Text colour', 'pfh-widgets' ),
					'type'  => 'color',
				],
				'bgColor'    => [
					'label'       => esc_html__( 'Fallback background', 'pfh-widgets' ),
					'type'        => 'color',
					'description' => esc_html__( 'Shown behind the artwork while it loads.', 'pfh-widgets' ),
				],
				'colSpan'    => [
					'label'   => esc_html__( 'Columns wide', 'pfh-widgets' ),
					'type'    => 'select',
					'inline'  => true,
					'options' => $this->span_options(),
					'default' => '1',
				],
				'rowSpan'    => [
					'label'   => esc_html__( 'Rows tall', 'pfh-widgets' ),
					'type'    => 'select',
					'inline'  => true,
					'options' => $this->span_options(),
					'default' => '1',
				],
				'align'      => [
					'label'   => esc_html__( 'Text position', 'pfh-widgets' ),
					'type'    => 'select',
					'inline'  => true,
					'options' => [
						'flex-start' => esc_html__( 'Top', 'pfh-widgets' ),
						'center'     => esc_html__( 'Middle', 'pfh-widgets' ),
						'flex-end'   => esc_html__( 'Bottom', 'pfh-widgets' ),
					],
					'default' => 'center',
				],
				'textWidth'  => [
					'label'       => esc_html__( 'Text max width (px)', 'pfh-widgets' ),
					'type'        => 'number',
					'inline'      => true,
					'placeholder' => esc_html__( 'Full width', 'pfh-widgets' ),
				],
				'link'       => [
					'label' => esc_html__( 'Link', 'pfh-widgets' ),
					'type'  => 'link',
				],
			],
		];
	}

	/**
	 * The seven tiles in the design, in reading order.
	 *
	 * Spans reproduce the bento exactly: the oil bottle and the juice hands run
	 * two rows, everything else is a single cell.
	 */
	private function default_tiles() {
		$light = '#ffffff';
		$body  = 'rgba(255, 255, 255, 0.88)';

		return [
			[
				'image'      => [ 'url' => self::CARD_HONEY ],
				'imagePos'   => 'right center',
				'title'      => "Pure Goodness\n<em>from nature</em>",
				'text'       => 'Fresh juices, raw honey, and handpicked olives delivered with authentic taste and natural quality for every home.',
				'titleColor' => [ 'hex' => '#49492b' ],
				'textColor'  => [ 'hex' => '#49492b' ],
				'colSpan'    => '1',
				'rowSpan'    => '1',
				'align'      => 'center',
			],
			[
				'image'      => [ 'url' => self::CARD_LEMON ],
				'imagePos'   => 'right center',
				'title'      => "Crafted For\n<em>healthy living</em>",
				'text'       => 'Discover everyday essentials made with care — rich flavors, clean ingredients, and products inspired by nature.',
				'titleColor' => [ 'hex' => $light ],
				'textColor'  => [ 'rgb' => $body ],
				'bgColor'    => [ 'hex' => '#3d868c' ],
				'colSpan'    => '1',
				'rowSpan'    => '1',
				'align'      => 'center',
			],
			[
				'image'   => [ 'url' => self::PHOTO_OIL ],
				'alt'     => 'Greek extra virgin olive oil',
				'colSpan' => '1',
				'rowSpan' => '2',
			],
			[
				'alt'     => 'Fresh juices raised to the sky',
				'bgColor' => [ 'hex' => '#cfe2ef' ],
				'colSpan' => '1',
				'rowSpan' => '2',
			],
			[
				'image'   => [ 'url' => self::PHOTO_JARS ],
				'alt'     => 'Jars of raw Greek honey',
				'colSpan' => '1',
				'rowSpan' => '1',
			],
			[
				'image'      => [ 'url' => self::CARD_OLIVE ],
				'imagePos'   => 'right center',
				'title'      => "Pure goodness\n<em>from nature</em>",
				'text'       => 'Fresh juices, raw honey, and handpicked olives delivered with authentic taste and natural quality for every home.',
				'titleColor' => [ 'hex' => $light ],
				'textColor'  => [ 'rgb' => $body ],
				'bgColor'    => [ 'hex' => '#697c66' ],
				'colSpan'    => '1',
				'rowSpan'    => '1',
				'align'      => 'center',
			],
			[
				'image'   => [ 'url' => self::PHOTO_JUICE ],
				'alt'     => 'Three fresh fruit juices',
				'colSpan' => '1',
				'rowSpan' => '1',
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
			'label'   => esc_html__( 'Serif font family', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Playfair Display',
		];

		$this->controls['headSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 16,
			'max'     => 96,
			'inline'  => true,
			'default' => 36,
		];

		$this->controls['headSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 14,
			'max'     => 64,
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
			'default'     => [ 'hex' => '#6b9691' ],
			'description' => esc_html__( 'The italic underlined part of the heading.', 'pfh-widgets' ),
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

		$this->controls['ledeSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Intro size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['ledeWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Intro weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '300',
		];

		$this->controls['ledeLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Intro line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 2.4,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.4,
		];

		$this->controls['ledeColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Intro colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#3d4a4d' ],
		];

		$this->controls['titleSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Tile title size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 56,
			'inline'  => true,
			'default' => 24,
		];

		$this->controls['titleSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Tile title on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 40,
			'inline'  => true,
			'default' => 22,
		];

		$this->controls['titleLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Tile title line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0.8,
			'max'     => 2,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.2,
		];

		$this->controls['titleColor'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Tile title colour', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#49492b' ],
			'description' => esc_html__( 'Each tile can override this.', 'pfh-widgets' ),
		];

		$this->controls['textSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Tile text size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['textWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Tile text weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '300',
		];

		$this->controls['textLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Tile text line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 2.4,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.4,
		];

		$this->controls['textColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Tile text colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#49492b' ],
		];

		$this->controls['textWidth'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Tile text max width (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 120,
			'max'         => 900,
			'inline'      => true,
			'default'     => 292,
			'description' => esc_html__( 'Caps the measure so the copy breaks as it does in Figma. Each tile can override it.', 'pfh-widgets' ),
		];

		$this->controls['titleTextGap'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Gap title → text (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 20,
		];
	}

	/* ---------------------------------------------------------------------
	 * Grid
	 * ------------------------------------------------------------------ */

	private function grid_controls() {
		$this->controls['columns'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Columns', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 6,
			'inline'  => true,
			'default' => 3,
		];

		$this->controls['gridGap'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Gap (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['rowHeight'] = [
			'tab'         => 'content',
			'group'       => 'grid',
			'label'       => esc_html__( 'Row height (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 80,
			'max'         => 600,
			'inline'      => true,
			'default'     => 234,
			'description' => esc_html__( 'Measured against the column width at the content width, then held as a ratio — so tiles keep their shape when the column count changes.', 'pfh-widgets' ),
		];

		$this->controls['tilePadding'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Tile padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 24,
		];

		$this->controls['tileRadius'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Tile corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['tileBg'] = [
			'tab'         => 'content',
			'group'       => 'grid',
			'label'       => esc_html__( 'Default tile background', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#eef0e9' ],
			'description' => esc_html__( 'Sits behind the artwork. Each tile can override it.', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Motion
	 * ------------------------------------------------------------------ */

	private function motion_controls() {
		$this->controls['hoverEffect'] = [
			'tab'         => 'content',
			'group'       => 'motion',
			'label'       => esc_html__( 'Hover effect', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'both' => esc_html__( 'Lift and zoom', 'pfh-widgets' ),
				'zoom' => esc_html__( 'Zoom only', 'pfh-widgets' ),
				'lift' => esc_html__( 'Lift only', 'pfh-widgets' ),
				'none' => esc_html__( 'None', 'pfh-widgets' ),
			],
			'default'     => 'both',
			'description' => esc_html__( 'The artwork is its own layer, so it scales without moving the copy — and the tile clips it, so nothing spills.', 'pfh-widgets' ),
		];

		$this->controls['hoverZoom'] = [
			'tab'      => 'content',
			'group'    => 'motion',
			'label'    => esc_html__( 'Zoom (%)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 100,
			'max'      => 130,
			'inline'   => true,
			'default'  => 105,
			'required' => [ 'hoverEffect', '!=', [ 'none', 'lift' ] ],
		];

		$this->controls['hoverLift'] = [
			'tab'      => 'content',
			'group'    => 'motion',
			'label'    => esc_html__( 'Lift (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 20,
			'inline'   => true,
			'default'  => 4,
			'required' => [ 'hoverEffect', '!=', [ 'none', 'zoom' ] ],
		];

		$this->controls['hoverSpeed'] = [
			'tab'     => 'content',
			'group'   => 'motion',
			'label'   => esc_html__( 'Hover speed (ms)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 100,
			'max'     => 1200,
			'step'    => 10,
			'inline'  => true,
			'default' => 550,
		];

		$this->controls['reveal'] = [
			'tab'         => 'content',
			'group'       => 'motion',
			'label'       => esc_html__( 'Reveal on scroll', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Tiles rise and fade in, one after another. The hidden state is only ever set by script, so the section still renders with JavaScript off.', 'pfh-widgets' ),
		];

		$this->controls['revealRise'] = [
			'tab'      => 'content',
			'group'    => 'motion',
			'label'    => esc_html__( 'Rise distance (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 80,
			'inline'   => true,
			'default'  => 18,
			'required' => [ 'reveal', '=', true ],
		];

		$this->controls['revealSpeed'] = [
			'tab'      => 'content',
			'group'    => 'motion',
			'label'    => esc_html__( 'Reveal speed (ms)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 100,
			'max'      => 2000,
			'step'     => 10,
			'inline'   => true,
			'default'  => 620,
			'required' => [ 'reveal', '=', true ],
		];

		$this->controls['revealStagger'] = [
			'tab'      => 'content',
			'group'    => 'motion',
			'label'    => esc_html__( 'Stagger (ms)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 400,
			'step'     => 5,
			'inline'   => true,
			'default'  => 70,
			'required' => [ 'reveal', '=', true ],
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

		$this->controls['paddingTop'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Top padding (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 400,
			'inline'      => true,
			'default'     => 140,
			'description' => esc_html__( '140 + 82 + 56 + 742 + 128 = the 1148px section in Figma.', 'pfh-widgets' ),
		];

		$this->controls['paddingBottom'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Bottom padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 400,
			'inline'  => true,
			'default' => 128,
		];

		$this->controls['bgColor'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Background colour', 'pfh-widgets' ),
			'type'    => 'color',
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

	private function is_on( $key, $default = true ) {
		return $this->switched_on( $key, $default );
	}

	/**
	 * Clamp a span to something the grid can actually hold.
	 *
	 * @param mixed $value Control value.
	 * @param int   $max   Columns available.
	 * @return int
	 */
	private function span( $value, $max ) {
		$span = (int) $value;

		return max( 1, min( $max, $span ? $span : 1 ) );
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$tiles = $this->get( 'tiles', [] );
		$tiles = is_array( $tiles ) ? array_values( $tiles ) : [];

		$classes = [
			'pfh-hf',
			'pfh-scope',
			'pfh-hover-' . (string) $this->get( 'hoverEffect', 'both' ),
		];

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		if ( $this->is_on( 'reveal' ) ) {
			$this->set_attribute( '_root', 'data-pfh-features', '' );
		}

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-hf__inner">';

		$this->render_head();

		if ( $tiles ) {
			// The wrapper is the query container; the grid inside reads cqw
			// from it to size its rows against the column width.
			echo '<div class="pfh-hf__gridwrap">';
			echo '<div class="pfh-hf__grid">';

			foreach ( $tiles as $tile ) {
				$this->render_tile( (array) $tile );
			}

			echo '</div>';
			echo '</div>';
		} elseif ( PFH_Widgets_Helpers::is_builder_context() ) {
			printf(
				'<p class="pfh-hf__empty">%s</p>',
				esc_html__( 'Add a tile to the Highlighted Features grid.', 'pfh-widgets' )
			);
		}

		echo '</div>';
		echo '</section>';
	}

	private function render_head() {
		$heading = (string) $this->get( 'heading', '' );
		$lede    = (string) $this->get( 'lede', '' );

		if ( '' === trim( $heading ) && '' === trim( $lede ) ) {
			return;
		}

		$tag = (string) $this->get( 'headingTag', 'h2' );
		$tag = in_array( $tag, [ 'h2', 'h3', 'div' ], true ) ? $tag : 'h2';

		echo '<div class="pfh-hf__head">';

		if ( '' !== trim( $heading ) ) {
			printf(
				'<%1$s class="pfh-hf__title">%2$s</%1$s>',
				$tag, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted above.
				wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $heading ) ) )
			);
		}

		if ( '' !== trim( $lede ) ) {
			echo '<p class="pfh-hf__lede">' . wp_kses_post( PFH_Widgets_Helpers::dd( $lede ) ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * @param array $tile Repeater row.
	 */
	private function render_tile( $tile ) {
		$cols  = max( 1, (int) $this->get( 'columns', 3 ) );
		$image = PFH_Widgets_Helpers::image_url( isset( $tile['image'] ) ? $tile['image'] : null, 'large' );
		$title = isset( $tile['title'] ) ? (string) $tile['title'] : '';
		$text  = isset( $tile['text'] ) ? (string) $tile['text'] : '';
		$alt   = isset( $tile['alt'] ) ? trim( (string) $tile['alt'] ) : '';
		$link  = PFH_Widgets_Helpers::link( isset( $tile['link'] ) ? $tile['link'] : null );

		$col = $this->span( isset( $tile['colSpan'] ) ? $tile['colSpan'] : 1, $cols );
		$row = $this->span( isset( $tile['rowSpan'] ) ? $tile['rowSpan'] : 1, 6 );

		$has_copy = '' !== trim( $title ) || '' !== trim( $text );

		$style = PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-hf-img'        => $image ? 'url(' . $image . ')' : 'none',
				'--pfh-hf-img-pos'    => ! empty( $tile['imagePos'] ) ? $tile['imagePos'] : '',
				'--pfh-hf-tile-bg'    => PFH_Widgets_Helpers::color( isset( $tile['bgColor'] ) ? $tile['bgColor'] : null ),
				'--pfh-hf-tile-title' => PFH_Widgets_Helpers::color( isset( $tile['titleColor'] ) ? $tile['titleColor'] : null ),
				'--pfh-hf-tile-text'  => PFH_Widgets_Helpers::color( isset( $tile['textColor'] ) ? $tile['textColor'] : null ),
				'--pfh-hf-align'      => isset( $tile['align'] ) ? $tile['align'] : '',
				'--pfh-hf-text-w'     => ! empty( $tile['textWidth'] ) ? PFH_Widgets_Helpers::unit( $tile['textWidth'] ) : '',
				'--pfh-hf-c'          => $col,
				// Two columns is the widest the tablet grid gets.
				'--pfh-hf-c-md'       => min( 2, $col ),
				'--pfh-hf-r'          => $row,
			]
		);

		$classes = 'pfh-hf__tile' . ( $has_copy ? '' : ' is-photo' );

		// A linked tile is a real anchor; an unlinked one must not be.
		$is_link = '' !== $link['href'];
		$tag     = $is_link ? 'a' : 'div';
		$attrs   = $is_link ? PFH_Widgets_Helpers::link_attrs( $link ) : '';

		printf(
			'<%s class="%s"%s%s>',
			$tag, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal above.
			esc_attr( $classes ),
			$style ? ' style="' . esc_attr( $style ) . '"' : '',
			$attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().
		);

		echo '<span class="pfh-hf__art" aria-hidden="true"></span>';

		if ( '' !== trim( $title ) ) {
			echo '<h3 class="pfh-hf__tile-title">' . wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $title ) ) ) . '</h3>';
		}

		if ( '' !== trim( $text ) ) {
			echo '<p class="pfh-hf__tile-text">' . wp_kses_post( PFH_Widgets_Helpers::dd( $text ) ) . '</p>';
		}

		// The artwork is a background, so a photo-only tile would otherwise be
		// invisible to assistive tech.
		if ( ! $has_copy && '' !== $alt ) {
			echo '<span class="pfh-hf__sr">' . esc_html( PFH_Widgets_Helpers::dd( $alt ) ) . '</span>';
		}

		echo '</' . $tag . '>';
	}

	/* ---------------------------------------------------------------------
	 * Tokens
	 * ------------------------------------------------------------------ */

	private function build_vars() {
		$stack = ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif';
		$head  = trim( (string) $this->get( 'headFamily', 'Playfair Display' ) );

		// The row height is authored in px against the column width at the
		// content width, then emitted as a ratio of the column so the tiles
		// keep their proportions at any column count.
		$container = max( 1.0, (float) $this->get( 'containerWidth', 1140 ) );
		$cols      = max( 1, (int) $this->get( 'columns', 3 ) );
		$gap       = max( 0.0, (float) $this->get( 'gridGap', 20 ) );
		$row       = max( 1.0, (float) $this->get( 'rowHeight', 234 ) );
		$col_w     = max( 1.0, ( $container - ( ( $cols - 1 ) * $gap ) ) / $cols );

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-container'            => PFH_Widgets_Helpers::unit( $container ),
				'--pfh-gutter-set'           => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-hf-pad-t-set'         => PFH_Widgets_Helpers::unit( $this->get( 'paddingTop', 140 ) ),
				'--pfh-hf-pad-b-set'         => PFH_Widgets_Helpers::unit( $this->get( 'paddingBottom', 128 ) ),
				'--pfh-hf-bg'                => PFH_Widgets_Helpers::color( $this->get( 'bgColor' ) ),
				'--pfh-hf-radius'            => PFH_Widgets_Helpers::unit( $this->get( 'radius', 0 ) ),

				'--pfh-hf-head-w-set'        => PFH_Widgets_Helpers::unit( $this->get( 'headWidth', 417 ) ),
				'--pfh-hf-lede-w-set'        => PFH_Widgets_Helpers::unit( $this->get( 'ledeWidth', 352 ) ),
				'--pfh-hf-head-gap-set'      => PFH_Widgets_Helpers::unit( $this->get( 'headGap', 56 ) ),

				'--pfh-head-font'            => $head ? $head . $stack : 'inherit',
				'--pfh-head-size-set'        => PFH_Widgets_Helpers::unit( $this->get( 'headSize', 36 ) ),
				'--pfh-head-size-m'          => PFH_Widgets_Helpers::unit( $this->get( 'headSizeMobile', 28 ) ),
				'--pfh-head-weight'          => $this->get( 'headWeight', '400' ),
				'--pfh-head-lh'              => $this->get( 'headLineHeight', 1.15 ),
				'--pfh-head-color'           => PFH_Widgets_Helpers::color( $this->get( 'headColor' ), '#22504a' ),
				'--pfh-head-accent'          => PFH_Widgets_Helpers::color( $this->get( 'headAccent' ), '#6b9691' ),
				'--pfh-head-ul-offset'       => PFH_Widgets_Helpers::unit( $this->get( 'headUnderlineOffset', 6 ) ),

				'--pfh-hf-lede-size'         => PFH_Widgets_Helpers::unit( $this->get( 'ledeSize', 14 ) ),
				'--pfh-hf-lede-weight'       => $this->get( 'ledeWeight', '300' ),
				'--pfh-hf-lede-lh'           => $this->get( 'ledeLineHeight', 1.4 ),
				'--pfh-hf-lede-color'        => PFH_Widgets_Helpers::color( $this->get( 'ledeColor' ), '#3d4a4d' ),

				'--pfh-hf-cols-set'          => $cols,
				'--pfh-hf-gap-set'           => PFH_Widgets_Helpers::unit( $gap ),
				'--pfh-hf-ratio-set'         => round( $row / $col_w, 5 ),
				'--pfh-hf-row-px'            => PFH_Widgets_Helpers::unit( $row ),

				'--pfh-hf-tile-radius'       => PFH_Widgets_Helpers::unit( $this->get( 'tileRadius', 16 ) ),
				'--pfh-hf-tile-pad-set'      => PFH_Widgets_Helpers::unit( $this->get( 'tilePadding', 24 ) ),
				'--pfh-hf-tile-bg'           => PFH_Widgets_Helpers::color( $this->get( 'tileBg' ), '#eef0e9' ),

				'--pfh-hf-title-size-set'    => PFH_Widgets_Helpers::unit( $this->get( 'titleSize', 24 ) ),
				'--pfh-hf-title-size-m'      => PFH_Widgets_Helpers::unit( $this->get( 'titleSizeMobile', 22 ) ),
				'--pfh-hf-title-lh'          => $this->get( 'titleLineHeight', 1.2 ),
				'--pfh-hf-title-color'       => PFH_Widgets_Helpers::color( $this->get( 'titleColor' ), '#49492b' ),

				'--pfh-hf-text-size-set'     => PFH_Widgets_Helpers::unit( $this->get( 'textSize', 14 ) ),
				'--pfh-hf-text-weight'       => $this->get( 'textWeight', '300' ),
				'--pfh-hf-text-lh'           => $this->get( 'textLineHeight', 1.4 ),
				'--pfh-hf-text-color'        => PFH_Widgets_Helpers::color( $this->get( 'textColor' ), '#49492b' ),
				'--pfh-hf-text-gap'          => PFH_Widgets_Helpers::unit( $this->get( 'titleTextGap', 20 ) ),
				'--pfh-hf-text-w'            => PFH_Widgets_Helpers::unit( $this->get( 'textWidth', 292 ) ),

				'--pfh-hf-zoom'              => round( max( 100.0, (float) $this->get( 'hoverZoom', 105 ) ) / 100, 4 ),
				'--pfh-hf-lift'              => PFH_Widgets_Helpers::unit( $this->get( 'hoverLift', 4 ) ),
				'--pfh-hf-hover-speed'       => PFH_Widgets_Helpers::unit( $this->get( 'hoverSpeed', 550 ), 'ms' ),
				'--pfh-hf-rise'              => PFH_Widgets_Helpers::unit( $this->get( 'revealRise', 18 ) ),
				'--pfh-hf-reveal-speed'      => PFH_Widgets_Helpers::unit( $this->get( 'revealSpeed', 620 ), 'ms' ),
				'--pfh-hf-stagger'           => PFH_Widgets_Helpers::unit( $this->get( 'revealStagger', 70 ), 'ms' ),
			]
		);
	}
}
