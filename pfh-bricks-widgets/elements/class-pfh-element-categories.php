<?php
/**
 * Bricks element: Products For Home category slider.
 *
 * Drag-to-scroll slider of highlighted category cards with a custom scrollbar.
 * The track is a native overflow-x container, so trackpad scrolling, touch
 * momentum and keyboard scrolling all work for free; pointer drag and the
 * scrollbar are layered on top of the same scrollLeft.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Categories extends \Bricks\Element {

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
	public $name         = 'pfh-categories';
	public $icon         = 'ti-layout-grid4-alt';
	public $css_selector = '.pfh-cats';
	public $scripts      = [ 'pfhSliderInit' ];

	const CARD_BASE = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/';

	public function get_label() {
		return esc_html__( 'PFH Category Slider', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'category', 'slider', 'drag', 'carousel', 'cards', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::categories();
	}

	public function set_control_groups() {
		$this->control_groups['head']      = [ 'title' => esc_html__( 'Section heading', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['cards']     = [ 'title' => esc_html__( 'Cards', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout']    = [ 'title' => esc_html__( 'Layout & background', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['cardstyle'] = [ 'title' => esc_html__( 'Card style', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']      = [ 'title' => esc_html__( 'Card typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['hover']     = [ 'title' => esc_html__( 'Hover effect', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['drag']      = [ 'title' => esc_html__( 'Drag & scrollbar', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->head_controls();
		$this->card_controls();
		$this->layout_controls();
		$this->cardstyle_controls();
		$this->type_controls();
		$this->hover_controls();
		$this->drag_controls();
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
	 * Section heading
	 * ------------------------------------------------------------------ */

	private function head_controls() {
		$this->controls['heading'] = [
			'tab'         => 'content',
			'group'       => 'head',
			'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'        => 'textarea',
			'default'     => "Fluisteringen van de Egeïsche Zee,\nvervaardigd in <em>pure harmonie</em>",
			'description' => esc_html__( 'Line breaks are kept. Wrap words in <em>…</em> for the underlined italic accent.', 'pfh-widgets' ),
		];

		$this->controls['intro'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro text', 'pfh-widgets' ),
			'type'    => 'textarea',
			'default' => 'Een zorgvuldig samengestelde selectie, geïnspireerd op zonovergoten Griekse landschappen — waar olijven, honing en verse sappen samenkomen met tijdloos vakmanschap en natuurlijke elegantie.',
		];

		$this->controls['headFamily'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading font family', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Playfair Display',
		];

		$this->controls['headSize'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 16,
			'max'     => 90,
			'inline'  => true,
			'default' => 38,
		];

		$this->controls['headSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 14,
			'max'     => 60,
			'inline'  => true,
			'default' => 28,
		];

		$this->controls['headLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0.8,
			'max'     => 2,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.25,
		];

		$this->controls['headColor'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#22504a' ],
		];

		$this->controls['headAccent'] = [
			'tab'         => 'content',
			'group'       => 'head',
			'label'       => esc_html__( 'Accent colour', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#2f7e7c' ],
			'description' => esc_html__( 'Used for the italic underlined part of the heading.', 'pfh-widgets' ),
		];

		$this->controls['headWidth'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading max width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 160,
			'max'     => 900,
			'inline'  => true,
			'default' => 470,
		];

		$this->controls['introSize'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 28,
			'inline'  => true,
			'default' => 15,
		];

		$this->controls['introLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 2.4,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.6,
		];

		$this->controls['introColor'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#3d4a4d' ],
		];

		$this->controls['introWidth'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro max width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 160,
			'max'     => 900,
			'inline'  => true,
			'default' => 350,
		];

		$this->controls['introOffset'] = [
			'tab'         => 'content',
			'group'       => 'head',
			'label'       => esc_html__( 'Intro column start (%)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 90,
			'inline'      => true,
			'default'     => 58,
			'description' => esc_html__( 'Where the intro column begins across the content width.', 'pfh-widgets' ),
		];

		$this->controls['headGap'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Space below the heading (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 160,
			'inline'  => true,
			'default' => 56,
		];
	}

	/* ---------------------------------------------------------------------
	 * Cards
	 * ------------------------------------------------------------------ */

	private function card_controls() {
		$this->controls['slides'] = [
			'tab'           => 'content',
			'group'         => 'cards',
			'label'         => esc_html__( 'Cards', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'eyebrow',
			'default'       => $this->default_slides(),
			'fields'        => [
				'eyebrow'  => [
					'label' => esc_html__( 'Category label', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'title'    => [
					'label'       => esc_html__( 'Headline', 'pfh-widgets' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'Wrap the emphasised part in <strong>…</strong>, as in the design.', 'pfh-widgets' ),
				],
				'image'    => [
					'label'       => esc_html__( 'Card artwork', 'pfh-widgets' ),
					'type'        => 'image',
					'description' => esc_html__( 'Full card image: background and product together.', 'pfh-widgets' ),
				],
				'bgColor'  => [
					'label'       => esc_html__( 'Fallback background', 'pfh-widgets' ),
					'type'        => 'color',
					'description' => esc_html__( 'Shown behind the artwork while it loads.', 'pfh-widgets' ),
				],
				'imagePos' => [
					'label'       => esc_html__( 'Artwork position', 'pfh-widgets' ),
					'type'        => 'text',
					'inline'      => true,
					'placeholder' => 'center center',
				],
				'ctaLabel' => [
					'label'   => esc_html__( 'Link label', 'pfh-widgets' ),
					'type'    => 'text',
					'inline'  => true,
					'default' => 'Bekijk nu',
				],
				'link'     => [
					'label' => esc_html__( 'Link', 'pfh-widgets' ),
					'type'  => 'link',
				],
				'color'    => [
					'label' => esc_html__( 'Text colour override', 'pfh-widgets' ),
					'type'  => 'color',
				],
			],
		];
	}

	private function default_slides() {
		$cards = [
			[ 'Gia Giamas', "Ontdek eenvoudig je\n<strong>favoriete smaak</strong>", 'Card-10.jpg' ],
			[ 'Olijfolie', "Bron van <strong>extra\nantioxidanten</strong>", 'Card-5.jpg' ],
			[ 'Pijnboomhoning', "Zoals onze Griekse\n<strong>oma het maakt</strong>", 'Card-4.jpg' ],
			[ 'Huidverzorging', "Natuurlijke verzorging\n<strong>puur voor je huid</strong>", 'Card-9.jpg' ],
			[ 'Bijenwas', "Met liefde gemaakt\n<strong>pure bijenwas</strong>", 'Card-8.jpg' ],
		];

		$out = [];

		foreach ( $cards as $card ) {
			$out[] = [
				'eyebrow'  => $card[0],
				'title'    => $card[1],
				'image'    => [ 'url' => self::CARD_BASE . $card[2] ],
				'ctaLabel' => 'Bekijk nu',
			];
		}

		return $out;
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
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Padding top (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'inline'  => true,
			'default' => 96,
		];

		$this->controls['paddingBottom'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Padding bottom (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 240,
			'inline'  => true,
			'default' => 96,
		];

		$this->controls['bgColor'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Section background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#fbfaf7' ],
		];

		$this->controls['bgImage'] = [
			'tab'   => 'content',
			'group' => 'layout',
			'label' => esc_html__( 'Section background image', 'pfh-widgets' ),
			'type'  => 'image',
		];

		$this->controls['bleed'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Let the track run to the screen edge', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Cards start at the content edge and the next one peeks past the right of the screen.', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Card style
	 * ------------------------------------------------------------------ */

	private function cardstyle_controls() {
		$this->controls['perView'] = [
			'tab'         => 'content',
			'group'       => 'cardstyle',
			'label'       => esc_html__( 'Cards per view', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 6,
			'step'        => 0.1,
			'inline'      => true,
			'default'     => 3,
			'description' => esc_html__( 'Decimals are allowed, e.g. 3.4 to show part of the next card.', 'pfh-widgets' ),
		];

		$this->controls['perViewTablet'] = [
			'tab'     => 'content',
			'group'   => 'cardstyle',
			'label'   => esc_html__( 'Cards per view — tablet', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 5,
			'step'    => 0.1,
			'inline'  => true,
			'default' => 2.2,
		];

		$this->controls['perViewMobile'] = [
			'tab'     => 'content',
			'group'   => 'cardstyle',
			'label'   => esc_html__( 'Cards per view — mobile', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 3,
			'step'    => 0.1,
			'inline'  => true,
			'default' => 1.15,
		];

		$this->controls['cardGap'] = [
			'tab'     => 'content',
			'group'   => 'cardstyle',
			'label'   => esc_html__( 'Gap between cards (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['cardRatio'] = [
			'tab'         => 'content',
			'group'       => 'cardstyle',
			'label'       => esc_html__( 'Card ratio', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '1082 / 1395',
			'description' => esc_html__( 'Matches the supplied artwork. Any CSS aspect-ratio value works.', 'pfh-widgets' ),
		];

		$this->controls['cardRadius'] = [
			'tab'     => 'content',
			'group'   => 'cardstyle',
			'label'   => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 30,
		];

		$this->controls['cardClip'] = [
			'tab'         => 'content',
			'group'       => 'cardstyle',
			'label'       => esc_html__( 'Artwork corner trim (%)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 20,
			'step'        => 0.1,
			'inline'      => true,
			'default'     => 7,
			'description' => esc_html__( 'The supplied JPGs have rounded corners baked in against black. This trims the card by a percentage of its own width so those corners are always clipped. Set to 0 for flat or transparent artwork.', 'pfh-widgets' ),
		];

		$this->controls['cardPadding'] = [
			'tab'     => 'content',
			'group'   => 'cardstyle',
			'label'   => esc_html__( 'Card padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 90,
			'inline'  => true,
			'default' => 34,
		];

		$this->controls['cardFit'] = [
			'tab'     => 'content',
			'group'   => 'cardstyle',
			'label'   => esc_html__( 'Artwork fit', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'cover'   => 'cover',
				'contain' => 'contain',
			],
			'default' => 'cover',
		];
	}

	/* ---------------------------------------------------------------------
	 * Card typography
	 * ------------------------------------------------------------------ */

	private function type_controls() {
		$this->controls['eyebrowFamily'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Label font family', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Playfair Display',
		];

		$this->controls['eyebrowSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Label size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 40,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['eyebrowItalic'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Label in italic', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['eyebrowColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Label colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#2b2b2b' ],
		];

		$this->controls['titleSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Headline size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 60,
			'inline'  => true,
			'default' => 29,
		];

		$this->controls['titleSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Headline size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 12,
			'max'     => 44,
			'inline'  => true,
			'default' => 24,
		];

		$this->controls['titleWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Headline weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '400',
		];

		$this->controls['titleStrongWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Emphasised weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '600',
		];

		$this->controls['titleLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Headline line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0.9,
			'max'     => 2,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.24,
		];

		$this->controls['titleColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Headline colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#1c1c1c' ],
		];

		$this->controls['titleGap'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Space under the label (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['ctaSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Link size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 30,
			'inline'  => true,
			'default' => 17,
		];

		$this->controls['ctaColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Link colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#1c1c1c' ],
		];

		$this->controls['ctaIconSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Link arrow size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 32,
			'inline'  => true,
			'default' => 15,
		];
	}

	/* ---------------------------------------------------------------------
	 * Hover
	 * ------------------------------------------------------------------ */

	private function hover_controls() {
		$this->controls['hoverEffect'] = [
			'tab'     => 'content',
			'group'   => 'hover',
			'label'   => esc_html__( 'Effect', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'both' => esc_html__( 'Lift + gentle grow', 'pfh-widgets' ),
				'lift' => esc_html__( 'Lift only', 'pfh-widgets' ),
				'grow' => esc_html__( 'Grow only', 'pfh-widgets' ),
				'zoom' => esc_html__( 'Artwork zoom (crops the artwork)', 'pfh-widgets' ),
				'none' => esc_html__( 'None', 'pfh-widgets' ),
			],
			'default' => 'both',
		];

		$this->controls['hoverLift'] = [
			'tab'      => 'content',
			'group'    => 'hover',
			'label'    => esc_html__( 'Lift distance (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 40,
			'inline'   => true,
			'default'  => 8,
			'required' => [ 'hoverEffect', '!=', [ 'grow', 'zoom', 'none' ] ],
		];

		$this->controls['hoverZoom'] = [
			'tab'      => 'content',
			'group'    => 'hover',
			'label'       => esc_html__( 'Scale (%)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 100,
			'max'         => 130,
			'step'        => 0.5,
			'inline'      => true,
			'default'     => 103,
			'required'    => [ 'hoverEffect', '!=', [ 'lift', 'none' ] ],
			'description' => esc_html__( 'Scales the whole card, so the artwork keeps its framing. Only the "artwork zoom" effect scales inside the card and crops.', 'pfh-widgets' ),
		];

		$this->controls['hoverRoom'] = [
			'tab'         => 'content',
			'group'       => 'hover',
			'label'       => esc_html__( 'Headroom for the effect (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 120,
			'inline'      => true,
			'default'     => 36,
			'description' => esc_html__( 'Space reserved around the track so a lifted card and its shadow are not clipped. It is taken back out of the layout, so it does not add whitespace.', 'pfh-widgets' ),
		];

		$this->controls['hoverShadow'] = [
			'tab'     => 'content',
			'group'   => 'hover',
			'label'   => esc_html__( 'Shadow on hover', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['hoverShadowColor'] = [
			'tab'      => 'content',
			'group'    => 'hover',
			'label'    => esc_html__( 'Shadow colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(34, 80, 74, 0.18)' ],
			'required' => [ 'hoverShadow', '=', true ],
		];

		$this->controls['hoverDim'] = [
			'tab'         => 'content',
			'group'       => 'hover',
			'label'       => esc_html__( 'Fade the other cards', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Dims every card except the one under the cursor.', 'pfh-widgets' ),
		];

		$this->controls['hoverSpeed'] = [
			'tab'     => 'content',
			'group'   => 'hover',
			'label'   => esc_html__( 'Transition (ms)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 80,
			'max'     => 1200,
			'step'    => 10,
			'inline'  => true,
			'default' => 420,
		];
	}

	/* ---------------------------------------------------------------------
	 * Drag & scrollbar
	 * ------------------------------------------------------------------ */

	private function drag_controls() {
		$this->controls['dragEnable'] = [
			'tab'     => 'content',
			'group'   => 'drag',
			'label'   => esc_html__( 'Drag with the mouse', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['snap'] = [
			'tab'         => 'content',
			'group'       => 'drag',
			'label'       => esc_html__( 'Snap to cards', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Off gives free scrolling, which suits a peeking card layout.', 'pfh-widgets' ),
		];

		$this->controls['wheel'] = [
			'tab'         => 'content',
			'group'       => 'drag',
			'label'       => esc_html__( 'Vertical wheel scrolls the track', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Leave off so a normal mouse wheel still scrolls the page.', 'pfh-widgets' ),
		];

		$this->controls['barEnable'] = [
			'tab'     => 'content',
			'group'   => 'drag',
			'label'   => esc_html__( 'Show the scrollbar', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['barGap'] = [
			'tab'      => 'content',
			'group'    => 'drag',
			'label'    => esc_html__( 'Space above the scrollbar (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 120,
			'inline'   => true,
			'default'  => 44,
			'required' => [ 'barEnable', '=', true ],
		];

		$this->controls['barHeight'] = [
			'tab'      => 'content',
			'group'    => 'drag',
			'label'    => esc_html__( 'Track thickness (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 1,
			'max'      => 20,
			'inline'   => true,
			'default'  => 2,
			'required' => [ 'barEnable', '=', true ],
		];

		$this->controls['barThumbHeight'] = [
			'tab'      => 'content',
			'group'    => 'drag',
			'label'    => esc_html__( 'Handle thickness (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 1,
			'max'      => 24,
			'inline'   => true,
			'default'  => 2,
			'required' => [ 'barEnable', '=', true ],
		];

		$this->controls['barTrackColor'] = [
			'tab'      => 'content',
			'group'    => 'drag',
			'label'    => esc_html__( 'Track colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(34, 80, 74, 0.07)' ],
			'required' => [ 'barEnable', '=', true ],
		];

		$this->controls['barThumbColor'] = [
			'tab'      => 'content',
			'group'    => 'drag',
			'label'    => esc_html__( 'Handle colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(34, 80, 74, 0.3)' ],
			'required' => [ 'barEnable', '=', true ],
		];

		$this->controls['barThumbHover'] = [
			'tab'         => 'content',
			'group'       => 'drag',
			'label'       => esc_html__( 'Handle colour (hover)', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'rgb' => 'rgba(34, 80, 74, 0.55)' ],
			'required'    => [ 'barEnable', '=', true ],
			'description' => esc_html__( 'The handle stays quiet until the cursor is on it.', 'pfh-widgets' ),
		];

		$this->controls['barWidth'] = [
			'tab'         => 'content',
			'group'       => 'drag',
			'label'       => esc_html__( 'Scrollbar width (%)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 10,
			'max'         => 100,
			'inline'      => true,
			'default'     => 100,
			'required'    => [ 'barEnable', '=', true ],
			'description' => esc_html__( 'Percentage of the content width.', 'pfh-widgets' ),
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
			$this->uid = ! empty( $this->element['id'] ) ? sanitize_html_class( $this->element['id'] ) : uniqid( 'pfhc' );
		}

		return $this->uid;
	}

	/**
	 * Cards that have something to show.
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

			if ( ! empty( $slide['eyebrow'] ) || ! empty( $slide['title'] ) || ! empty( $slide['image'] ) ) {
				$this->slides[] = $slide;
			}
		}

		return $this->slides;
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$slides = $this->slides();

		if ( empty( $slides ) ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-cats pfh-cats--empty"><p>' . esc_html__( 'Add at least one card.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$classes = [ 'pfh-cats', 'pfh-scope', 'pfh-hov-' . (string) $this->get( 'hoverEffect', 'both' ) ];

		if ( $this->is_on( 'bleed' ) ) {
			$classes[] = 'is-bleed';
		}

		if ( $this->is_on( 'snap', false ) ) {
			$classes[] = 'is-snap';
		}

		if ( $this->is_on( 'hoverShadow' ) ) {
			$classes[] = 'has-shadow';
		}

		if ( $this->is_on( 'hoverDim', false ) ) {
			$classes[] = 'has-dim';
		}

		if ( $this->is_on( 'eyebrowItalic' ) ) {
			$classes[] = 'is-italic-label';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );
		$this->set_attribute( '_root', 'data-pfh-cats', $this->uid() );
		$this->set_attribute( '_root', 'data-pfh-drag-slider', '' );
		$this->set_attribute( '_root', 'data-pfh-config', wp_json_encode( $this->js_config() ) );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';

		$this->render_head();

		echo '<div class="pfh-cats__slider">';
		echo '<div class="pfh-cats__viewport" data-pfh-viewport tabindex="0" role="group" aria-label="' . esc_attr__( 'Categorieën', 'pfh-widgets' ) . '">';
		echo '<ul class="pfh-cats__track" data-pfh-track>';

		foreach ( $slides as $index => $slide ) {
			$this->render_card( $slide, $index );
		}

		echo '</ul>';
		echo '</div>';

		$this->render_scrollbar();

		echo '</div>';
		echo '</section>';
	}

	private function render_head() {
		$heading = (string) $this->get( 'heading', '' );
		$intro   = (string) $this->get( 'intro', '' );

		if ( ! $heading && ! $intro ) {
			return;
		}

		echo '<div class="pfh-cats__inner">';
		echo '<div class="pfh-cats__head">';

		if ( $heading ) {
			echo '<h2 class="pfh-cats__title">' . wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $heading ) ) ) . '</h2>';
		}

		if ( $intro ) {
			echo '<p class="pfh-cats__intro">' . wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $intro ) ) ) . '</p>';
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * @param array $slide Card settings.
	 * @param int   $index Zero based index.
	 */
	private function render_card( $slide, $index ) {
		$image = PFH_Widgets_Helpers::image_url( isset( $slide['image'] ) ? $slide['image'] : null, 'large' );
		$link  = PFH_Widgets_Helpers::link( isset( $slide['link'] ) ? $slide['link'] : null );
		$cta   = ! empty( $slide['ctaLabel'] ) ? PFH_Widgets_Helpers::dd( $slide['ctaLabel'] ) : '';

		$style = PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-card-img'   => $image ? 'url(' . $image . ')' : '',
				'--pfh-card-bg'    => PFH_Widgets_Helpers::color( isset( $slide['bgColor'] ) ? $slide['bgColor'] : null ),
				'--pfh-card-pos'   => ! empty( $slide['imagePos'] ) ? $slide['imagePos'] : '',
				'--pfh-card-color' => PFH_Widgets_Helpers::color( isset( $slide['color'] ) ? $slide['color'] : null ),
			]
		);

		// The whole card is one link when a destination is set, a plain box otherwise.
		$is_link = '' !== $link['href'];
		$tag     = $is_link ? 'a' : 'div';
		$attrs   = $is_link ? PFH_Widgets_Helpers::link_attrs( $link ) : '';

		printf(
			'<li class="pfh-cats__item" data-pfh-card="%d"%s>',
			(int) $index,
			$style ? ' style="' . esc_attr( $style ) . '"' : ''
		);

		echo '<' . $tag . ' class="pfh-cats__card"' . $attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().
		echo '<span class="pfh-cats__art" aria-hidden="true"></span>';

		if ( $image ) {
			// A real <img> keeps the artwork in the accessibility and SEO layer
			// and lets the browser lazy-load it; the painted layer is the span.
			printf(
				'<img class="pfh-sr-only" src="%s" alt="%s" loading="lazy" decoding="async" width="1082" height="1395" />',
				esc_url( $image ),
				esc_attr( ! empty( $slide['eyebrow'] ) ? $slide['eyebrow'] : '' )
			);
		}

		echo '<span class="pfh-cats__body">';

		if ( ! empty( $slide['eyebrow'] ) ) {
			echo '<span class="pfh-cats__eyebrow">' . esc_html( PFH_Widgets_Helpers::dd( $slide['eyebrow'] ) ) . '</span>';
		}

		if ( ! empty( $slide['title'] ) ) {
			echo '<span class="pfh-cats__name">' . wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $slide['title'] ) ) ) . '</span>';
		}

		echo '</span>';

		if ( $cta ) {
			echo '<span class="pfh-cats__cta">';
			echo '<span class="pfh-cats__cta-label">' . esc_html( $cta ) . '</span>';
			echo PFH_Widgets_Icons::get( 'arrow-ne', 'pfh-cats__cta-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			echo '</span>';
		}

		echo '</' . $tag . '>';
		echo '</li>';
	}

	private function render_scrollbar() {
		if ( ! $this->is_on( 'barEnable' ) ) {
			return;
		}
		?>
		<div class="pfh-cats__bar" data-pfh-bar hidden>
			<div class="pfh-cats__bar-track" data-pfh-bar-track>
				<div
					class="pfh-cats__bar-thumb"
					data-pfh-bar-thumb
					role="scrollbar"
					tabindex="0"
					aria-controls="pfh-viewport-<?php echo esc_attr( $this->uid() ); ?>"
					aria-orientation="horizontal"
					aria-label="<?php esc_attr_e( 'Schuif door de categorieën', 'pfh-widgets' ); ?>"
					aria-valuemin="0"
					aria-valuemax="100"
					aria-valuenow="0"
				></div>
			</div>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Tokens & config
	 * ------------------------------------------------------------------ */

	private function build_vars() {
		$stack     = ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif';
		$head_font = trim( (string) $this->get( 'headFamily', 'Playfair Display' ) );
		$eb_font   = trim( (string) $this->get( 'eyebrowFamily', 'Playfair Display' ) );
		$bg        = PFH_Widgets_Helpers::image_url( $this->get( 'bgImage' ), 'full' );

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-container'      => PFH_Widgets_Helpers::unit( $this->get( 'containerWidth', 1140 ) ),
				'--pfh-gutter-set'     => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-cats-pad-top-set'   => PFH_Widgets_Helpers::unit( $this->get( 'paddingTop', 96 ) ),
				'--pfh-cats-pad-bot-set'   => PFH_Widgets_Helpers::unit( $this->get( 'paddingBottom', 96 ) ),
				'--pfh-cats-bg'        => PFH_Widgets_Helpers::color( $this->get( 'bgColor' ), '#fbfaf7' ),
				'--pfh-cats-bg-img'    => $bg ? 'url(' . $bg . ')' : 'none',

				'--pfh-head-font'      => $head_font ? $head_font . $stack : 'inherit',
				'--pfh-head-size-set'  => PFH_Widgets_Helpers::unit( $this->get( 'headSize', 38 ) ),
				'--pfh-head-size-m'    => PFH_Widgets_Helpers::unit( $this->get( 'headSizeMobile', 28 ) ),
				'--pfh-head-lh'        => $this->get( 'headLineHeight', 1.25 ),
				'--pfh-head-color'     => PFH_Widgets_Helpers::color( $this->get( 'headColor' ), '#22504a' ),
				'--pfh-head-accent'    => PFH_Widgets_Helpers::color( $this->get( 'headAccent' ), '#2f7e7c' ),
				'--pfh-head-max'       => PFH_Widgets_Helpers::unit( $this->get( 'headWidth', 470 ) ),
				'--pfh-head-gap-set'       => PFH_Widgets_Helpers::unit( $this->get( 'headGap', 56 ) ),
				'--pfh-intro-size'     => PFH_Widgets_Helpers::unit( $this->get( 'introSize', 15 ) ),
				'--pfh-intro-lh'       => $this->get( 'introLineHeight', 1.6 ),
				'--pfh-intro-color'    => PFH_Widgets_Helpers::color( $this->get( 'introColor' ), '#3d4a4d' ),
				'--pfh-intro-max'      => PFH_Widgets_Helpers::unit( $this->get( 'introWidth', 350 ) ),
				'--pfh-intro-start'    => PFH_Widgets_Helpers::unit( $this->get( 'introOffset', 58 ), '%' ),

				'--pfh-per-set'        => $this->get( 'perView', 3 ),
				'--pfh-per-t'          => $this->get( 'perViewTablet', 2.2 ),
				'--pfh-per-m'          => $this->get( 'perViewMobile', 1.15 ),
				'--pfh-card-gap-set'   => PFH_Widgets_Helpers::unit( $this->get( 'cardGap', 14 ) ),
				'--pfh-card-ratio'     => $this->get( 'cardRatio', '1082 / 1395' ),
				'--pfh-card-radius'    => PFH_Widgets_Helpers::unit( $this->get( 'cardRadius', 30 ) ),
				'--pfh-card-clip'      => PFH_Widgets_Helpers::unit( $this->get( 'cardClip', 7 ), 'cqw' ),
				'--pfh-card-pad-set'   => PFH_Widgets_Helpers::unit( $this->get( 'cardPadding', 34 ) ),
				'--pfh-card-fit'       => $this->get( 'cardFit', 'cover' ),

				'--pfh-eb-font'        => $eb_font ? $eb_font . $stack : 'inherit',
				'--pfh-eb-size-set'        => PFH_Widgets_Helpers::unit( $this->get( 'eyebrowSize', 20 ) ),
				'--pfh-eb-color'       => PFH_Widgets_Helpers::color( $this->get( 'eyebrowColor' ), '#2b2b2b' ),
				'--pfh-ct-size-set'    => PFH_Widgets_Helpers::unit( $this->get( 'titleSize', 29 ) ),
				'--pfh-ct-size-m'      => PFH_Widgets_Helpers::unit( $this->get( 'titleSizeMobile', 24 ) ),
				'--pfh-ct-weight'      => $this->get( 'titleWeight', '400' ),
				'--pfh-ct-strong'      => $this->get( 'titleStrongWeight', '600' ),
				'--pfh-ct-lh'          => $this->get( 'titleLineHeight', 1.24 ),
				'--pfh-ct-color'       => PFH_Widgets_Helpers::color( $this->get( 'titleColor' ), '#1c1c1c' ),
				'--pfh-ct-gap'         => PFH_Widgets_Helpers::unit( $this->get( 'titleGap', 14 ) ),
				'--pfh-cta-size'       => PFH_Widgets_Helpers::unit( $this->get( 'ctaSize', 17 ) ),
				'--pfh-cta-color'      => PFH_Widgets_Helpers::color( $this->get( 'ctaColor' ), '#1c1c1c' ),
				'--pfh-cta-icon'       => PFH_Widgets_Helpers::unit( $this->get( 'ctaIconSize', 15 ) ),

				'--pfh-hov-lift'       => PFH_Widgets_Helpers::unit( $this->get( 'hoverLift', 8 ) ),
				'--pfh-hov-zoom'       => ( (float) $this->get( 'hoverZoom', 103 ) ) / 100,
				'--pfh-hov-room'       => PFH_Widgets_Helpers::unit( $this->get( 'hoverRoom', 36 ) ),
				'--pfh-hov-shadow'     => PFH_Widgets_Helpers::color( $this->get( 'hoverShadowColor' ), 'rgba(34,80,74,.18)' ),
				'--pfh-hov-speed'      => PFH_Widgets_Helpers::unit( $this->get( 'hoverSpeed', 420 ), 'ms' ),

				'--pfh-bar-gap-set'        => PFH_Widgets_Helpers::unit( $this->get( 'barGap', 44 ) ),
				'--pfh-bar-h'          => PFH_Widgets_Helpers::unit( $this->get( 'barHeight', 2 ) ),
				'--pfh-bar-thumb-h'    => PFH_Widgets_Helpers::unit( $this->get( 'barThumbHeight', 2 ) ),
				'--pfh-bar-track'      => PFH_Widgets_Helpers::color( $this->get( 'barTrackColor' ), 'rgba(34,80,74,.07)' ),
				'--pfh-bar-thumb'      => PFH_Widgets_Helpers::color( $this->get( 'barThumbColor' ), 'rgba(34,80,74,.3)' ),
				'--pfh-bar-thumb-hover' => PFH_Widgets_Helpers::color( $this->get( 'barThumbHover' ), 'rgba(34,80,74,.55)' ),
				'--pfh-bar-w'          => PFH_Widgets_Helpers::unit( $this->get( 'barWidth', 100 ), '%' ),
			]
		);
	}

	private function js_config() {
		return [
			'uid'   => $this->uid(),
			'drag'  => $this->is_on( 'dragEnable' ),
			'wheel' => $this->is_on( 'wheel', false ),
			'bar'   => $this->is_on( 'barEnable' ),
			'snap'  => $this->is_on( 'snap', false ),
		];
	}
}
