<?php
/**
 * Bricks element: Products For Home review slider.
 *
 * A rail (heading, lede, prev/next) beside a scrolling grid of WebwinkelKeur
 * review cards. Reviews come from the WebwinkelKeur API through
 * PFH_Widgets_Reviews; the manual list is the fallback when the feed is not
 * configured or is temporarily unreachable, so the section never renders empty.
 *
 * Figma geometry (1440 frame): content 1110 = rail 362 + gap 68 + cards
 * 330 + 20 + 330.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Reviews extends \Bricks\Element {

	public $category     = 'products-for-home';
	public $name         = 'pfh-reviews';
	public $icon         = 'ti-comments';
	public $css_selector = '.pfh-rev';

	const BASE     = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/';
	const LOGO_URL = self::BASE . 'Frame-470024.svg';
	const ARROW_URL = self::BASE . 'Vector-2.svg';

	/** The chevron the client supplied, inlined so it can take currentColor. */
	const CHEVRON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 13" fill="none" aria-hidden="true" focusable="false"><path d="M1.111 1.112S6.342 4.964 6.342 6.343c0 1.378-5.231 5.23-5.231 5.23" stroke="currentColor" stroke-width="2.223" stroke-linecap="round" stroke-linejoin="round"/></svg>';

	/** Neutral avatar used when a review has no photo — WebwinkelKeur has none. */
	const AVATAR = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><circle cx="16" cy="12" r="5.2" fill="currentColor" opacity=".75"/><path d="M4.8 30a11.2 11.2 0 0 1 22.4 0Z" fill="currentColor" opacity=".75"/></svg>';

	public function get_label() {
		return esc_html__( 'PFH Review Slider', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'reviews', 'testimonials', 'webwinkelkeur', 'rating', 'stars', 'slider', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::reviews();
	}

	public function set_control_groups() {
		$this->control_groups['source'] = [ 'title' => esc_html__( 'Reviews (WebwinkelKeur)', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['head']   = [ 'title' => esc_html__( 'Heading', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']   = [ 'title' => esc_html__( 'Typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['card']   = [ 'title' => esc_html__( 'Card', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['slider'] = [ 'title' => esc_html__( 'Slider', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout'] = [ 'title' => esc_html__( 'Layout & background', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->source_controls();
		$this->head_controls();
		$this->type_controls();
		$this->card_controls();
		$this->slider_controls();
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
	 * Source
	 * ------------------------------------------------------------------ */

	private function source_controls() {
		$this->controls['sourceInfo'] = [
			'tab'     => 'content',
			'group'   => 'source',
			'type'    => 'info',
			'content' => esc_html__( 'Reviews are pulled from the WebwinkelKeur API and cached. For a live site, put the credentials in wp-config.php as PFH_WEBWINKELKEUR_ID and PFH_WEBWINKELKEUR_CODE — those win over the fields below and keep the API code out of the page content.', 'pfh-widgets' ),
		];

		$this->controls['shopId'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Webshop ID', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => PFH_Widgets_Reviews::DEFAULT_ID,
			'description' => esc_html__( 'The number at the end of the WebwinkelKeur shop URL.', 'pfh-widgets' ),
		];

		$this->controls['apiCode'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'API code', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'description' => esc_html__( 'WebwinkelKeur dashboard → Settings → API. Leave empty if you set the constant.', 'pfh-widgets' ),
		];

		$this->controls['limit'] = [
			'tab'     => 'content',
			'group'   => 'source',
			'label'   => esc_html__( 'How many reviews', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 100,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['cacheHours'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Cache for (hours)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 168,
			'inline'      => true,
			'default'     => 12,
			'description' => esc_html__( 'A failed call is cached for 15 minutes instead, so a bad key cannot slow every page view.', 'pfh-widgets' ),
		];

		$this->controls['ratingScale'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'API rating scale', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'10' => esc_html__( '1 – 10 (WebwinkelKeur default)', 'pfh-widgets' ),
				'5'  => esc_html__( '1 – 5', 'pfh-widgets' ),
			],
			'default'     => '10',
			'description' => esc_html__( 'Converted to the star count below.', 'pfh-widgets' ),
		];

		$this->controls['minRating'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Hide reviews below', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.5,
			'inline'      => true,
			'default'     => 0,
			'description' => esc_html__( 'On the API scale. 0 shows everything.', 'pfh-widgets' ),
		];

		$this->controls['requireText'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Only reviews with a comment', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Ratings without written feedback would render as an empty card.', 'pfh-widgets' ),
		];

		$this->controls['subLabel'] = [
			'tab'     => 'content',
			'group'   => 'source',
			'label'   => esc_html__( 'Line under the name', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'city'   => esc_html__( 'City (from WebwinkelKeur)', 'pfh-widgets' ),
				'date'   => esc_html__( 'Review date', 'pfh-widgets' ),
				'custom' => esc_html__( 'Same text for everyone', 'pfh-widgets' ),
				'none'   => esc_html__( 'Nothing', 'pfh-widgets' ),
			],
			'default' => 'city',
		];

		$this->controls['subLabelText'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Fallback / custom text', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => 'Verified customer',
			'description' => esc_html__( 'Used when the chosen field is empty, and for every card in “Same text” mode.', 'pfh-widgets' ),
			'required'    => [ 'subLabel', '!=', 'none' ],
		];

		$this->controls['dateFormat'] = [
			'tab'      => 'content',
			'group'    => 'source',
			'label'    => esc_html__( 'Date format', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'F Y',
			'required' => [ 'subLabel', '=', 'date' ],
		];

		$this->controls['fallback'] = [
			'tab'           => 'content',
			'group'         => 'source',
			'label'         => esc_html__( 'Fallback reviews', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'name',
			'default'       => $this->default_reviews(),
			'description'   => esc_html__( 'Shown when the API is not configured or is unreachable — and in the builder, so the section is never blank.', 'pfh-widgets' ),
			'fields'        => [
				'name'   => [
					'label' => esc_html__( 'Name', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'role'   => [
					'label'  => esc_html__( 'Line under the name', 'pfh-widgets' ),
					'type'   => 'text',
					'inline' => true,
				],
				'rating' => [
					'label'       => esc_html__( 'Stars', 'pfh-widgets' ),
					'type'        => 'number',
					'min'         => 0,
					'max'         => 5,
					'step'        => 0.5,
					'inline'      => true,
					'default'     => 4.5,
					'description' => esc_html__( 'Half stars allowed.', 'pfh-widgets' ),
				],
				'text'   => [
					'label' => esc_html__( 'Review', 'pfh-widgets' ),
					'type'  => 'textarea',
				],
				'avatar' => [
					'label' => esc_html__( 'Photo', 'pfh-widgets' ),
					'type'  => 'image',
				],
			],
		];
	}

	private function default_reviews() {
		$text = 'The quality feels incredibly authentic, from the rich honey to the perfectly balanced juices. Every order feels thoughtfully curated.';
		$out  = [];

		foreach ( [ 'Annette Black', 'Cameron Williamson', 'Darlene Robertson', 'Marvin McKinney' ] as $name ) {
			$out[] = [
				'name'   => $name,
				'role'   => 'Mother',
				'rating' => 4.5,
				'text'   => $text,
			];
		}

		return $out;
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
			'default'     => "Voices gathered from sunlit\n<em>mediterranean tables</em>",
			'description' => esc_html__( 'Line breaks are kept. Wrap the italic underlined part in <em>…</em>.', 'pfh-widgets' ),
		];

		$this->controls['lede'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Intro text', 'pfh-widgets' ),
			'type'    => 'textarea',
			'default' => 'Thoughts and experiences shared by those who found comfort, flavor, and authenticity in every carefully crafted product',
		];

		$this->controls['railSide'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Heading side', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'left'  => esc_html__( 'Left', 'pfh-widgets' ),
				'right' => esc_html__( 'Right', 'pfh-widgets' ),
			],
			'default' => 'left',
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
			'min'     => 16,
			'max'     => 96,
			'inline'  => true,
			'default' => 42,
		];

		$this->controls['headSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 14,
			'max'     => 64,
			'inline'  => true,
			'default' => 30,
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
			'default' => 1.5,
		];

		$this->controls['ledeColor'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Intro colour', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'rgb' => 'rgba(0, 0, 0, 0.62)' ],
			'description' => esc_html__( 'Figma has this as black at 62%.', 'pfh-widgets' ),
		];

		$this->controls['gapHeadLede'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Gap heading → intro (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['gapLedeNav'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Gap intro → arrows (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 24,
		];
	}

	/* ---------------------------------------------------------------------
	 * Card
	 * ------------------------------------------------------------------ */

	private function card_controls() {
		$this->controls['cardBg'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#eef3f1' ],
		];

		$this->controls['cardRadius'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['cardPadding'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['avatarMode'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'When a review has no photo', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'placeholder' => esc_html__( 'Placeholder image', 'pfh-widgets' ),
				'initials'    => esc_html__( 'Initials', 'pfh-widgets' ),
			],
			'default'     => 'placeholder',
			'description' => esc_html__( 'WebwinkelKeur does not return photos, so this is what most cards will use.', 'pfh-widgets' ),
		];

		$this->controls['avatarImage'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Placeholder photo', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'Leave empty for the built-in neutral avatar.', 'pfh-widgets' ),
			'required'    => [ 'avatarMode', '=', 'placeholder' ],
		];

		$this->controls['avatarSize'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Photo size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 20,
			'max'     => 80,
			'inline'  => true,
			'default' => 32,
		];

		$this->controls['avatarGap'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Photo → name gap (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 40,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['avatarBg'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Photo fallback background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#d9e4e0' ],
		];

		$this->controls['avatarColor'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Photo fallback ink', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#2b5f63' ],
		];

		$this->controls['nameSize'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Name size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['nameWeight'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Name weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '500',
		];

		$this->controls['nameColor'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Name colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#3d0023' ],
		];

		$this->controls['roleSize'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Sub-line size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['roleWeight'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Sub-line weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '400',
		];

		$this->controls['roleColor'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Sub-line colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'rgb' => 'rgba(0, 0, 0, 0.62)' ],
		];

		$this->controls['starCount'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Number of stars', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 3,
			'max'     => 10,
			'inline'  => true,
			'default' => 5,
		];

		$this->controls['starSize'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Star size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 40,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['starGap'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Star gap (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 20,
			'inline'  => true,
			'default' => 4,
		];

		$this->controls['starOn'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Star colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#2f7e7c' ],
		];

		$this->controls['starOff'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Empty star colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#cfdcd8' ],
		];

		$this->controls['textSize'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Review size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 28,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['textWeight'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Review weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '300',
		];

		$this->controls['textLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Review line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 2.4,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1.5,
		];

		$this->controls['textColor'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Review colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#3d4a4d' ],
		];

		$this->controls['textLines'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Review lines', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 2,
			'max'         => 12,
			'inline'      => true,
			'default'     => 4,
			'description' => esc_html__( 'Reserved and capped, so real reviews of any length keep every card the same height.', 'pfh-widgets' ),
		];

		$this->controls['logoImage'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Badge', 'pfh-widgets' ),
			'type'        => 'image',
			'default'     => [ 'url' => self::LOGO_URL ],
			'description' => esc_html__( 'The WebwinkelKeur lockup at the bottom of each card.', 'pfh-widgets' ),
		];

		$this->controls['logoHeight'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Badge height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 48,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['linkBadge'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Link the badge to the review page', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['reviewUrl'] = [
			'tab'      => 'content',
			'group'    => 'card',
			'label'    => esc_html__( 'Review page', 'pfh-widgets' ),
			'type'     => 'text',
			'default'  => PFH_Widgets_Reviews::SHOP_URL,
			'required' => [ 'linkBadge', '=', true ],
		];

		$this->controls['gapWhoStars'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Gap name → stars (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 18,
		];

		$this->controls['gapStarsText'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Gap stars → review (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 16,
		];

		$this->controls['gapTextLogo'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Gap review → badge (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 16,
		];
	}

	/* ---------------------------------------------------------------------
	 * Slider
	 * ------------------------------------------------------------------ */

	private function slider_controls() {
		$this->controls['cols'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Columns in view', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 4,
			'inline'  => true,
			'default' => 2,
		];

		$this->controls['rows'] = [
			'tab'         => 'content',
			'group'       => 'slider',
			'label'       => esc_html__( 'Rows', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 4,
			'inline'      => true,
			'default'     => 2,
			'description' => esc_html__( 'Rows × columns is one page, which is what the arrows and dots step through.', 'pfh-widgets' ),
		];

		$this->controls['cardGap'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Gap between cards (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['showArrows'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Show arrows', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['arrowIcon'] = [
			'tab'         => 'content',
			'group'       => 'slider',
			'label'       => esc_html__( 'Arrow icon', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'Leave empty to use the supplied chevron inline, which lets it recolour on hover.', 'pfh-widgets' ),
			'required'    => [ 'showArrows', '=', true ],
		];

		$this->controls['arrowSize'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Arrow button size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 24,
			'max'     => 80,
			'inline'  => true,
			'default' => 38,
		];

		$this->controls['arrowGap'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Gap between arrows (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 40,
			'inline'  => true,
			'default' => 8,
		];

		$this->controls['arrowIconSize'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Arrow icon height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 6,
			'max'     => 32,
			'inline'  => true,
			'default' => 13,
		];

		$this->controls['arrowColor'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Arrow colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#2b5f63' ],
		];

		$this->controls['arrowBorder'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Arrow border', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#d3dedb' ],
		];

		$this->controls['arrowHoverBg'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Arrow hover background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#2b5f63' ],
		];

		$this->controls['arrowHoverColor'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Arrow hover ink', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['showDots'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Show dots', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['dotSize'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Dot size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 4,
			'max'     => 20,
			'inline'  => true,
			'default' => 8,
		];

		$this->controls['dotGap'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Dot gap (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 32,
			'inline'  => true,
			'default' => 10,
		];

		$this->controls['dotOn'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Active dot', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#2b5f63' ],
		];

		$this->controls['dotOff'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Inactive dot', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#cfdcd8' ],
		];

		$this->controls['maxDots'] = [
			'tab'         => 'content',
			'group'       => 'slider',
			'label'       => esc_html__( 'Most dots to show', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 30,
			'inline'      => true,
			'default'     => 8,
			'description' => esc_html__( 'Past this the dots become a “3 / 20” counter — a phone showing one card at a time would otherwise get one dot per review.', 'pfh-widgets' ),
			'required'    => [ 'showDots', '=', true ],
		];

		$this->controls['dotsGap'] = [
			'tab'     => 'content',
			'group'   => 'slider',
			'label'   => esc_html__( 'Gap cards → dots (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 28,
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

		$this->controls['paddingY'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Top / bottom padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 400,
			'inline'  => true,
			'default' => 120,
		];

		$this->controls['railWidth'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Heading column width (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 180,
			'max'         => 900,
			'inline'      => true,
			'default'     => 362,
			'description' => esc_html__( 'Measured against the content width, then held as a proportion.', 'pfh-widgets' ),
		];

		$this->controls['splitGap'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Gap heading ↔ cards (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 68,
		];

		$this->controls['stackGap'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Stacked gap (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 120,
			'inline'      => true,
			'default'     => 48,
			'description' => esc_html__( 'Below 992px the heading moves above the cards.', 'pfh-widgets' ),
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
		if ( ! isset( $this->settings[ $key ] ) || '' === $this->settings[ $key ] ) {
			return $default;
		}

		return $this->settings[ $key ];
	}

	private function is_on( $key, $default = true ) {
		if ( ! array_key_exists( $key, (array) $this->settings ) ) {
			return $default;
		}

		return ! empty( $this->settings[ $key ] );
	}

	/* ---------------------------------------------------------------------
	 * Data
	 * ------------------------------------------------------------------ */

	/**
	 * Cards to render: the live WebwinkelKeur feed, or the manual list.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function cards() {
		$stars = max( 3, (int) $this->get( 'starCount', 5 ) );
		$feed  = [];

		// The builder should stay predictable and fast, so it never waits on
		// the remote call — it always shows the fallback list.
		if ( ! PFH_Widgets_Helpers::is_builder_context() ) {
			$feed = PFH_Widgets_Reviews::get(
				[
					'id'    => (string) $this->get( 'shopId', '' ),
					'code'  => (string) $this->get( 'apiCode', '' ),
					'limit' => (int) $this->get( 'limit', 20 ),
					'ttl'   => max( 1, (int) $this->get( 'cacheHours', 12 ) ) * HOUR_IN_SECONDS,
					'min'   => (float) $this->get( 'minRating', 0 ),
					'text'  => $this->is_on( 'requireText' ),
				]
			);
		}

		if ( $feed ) {
			return $this->from_feed( $feed, $stars );
		}

		return $this->from_manual( $stars );
	}

	/**
	 * @param array $feed  Normalised WebwinkelKeur rows.
	 * @param int   $stars Star count.
	 * @return array
	 */
	private function from_feed( array $feed, $stars ) {
		$scale = (int) $this->get( 'ratingScale', 10 );
		$out   = [];

		foreach ( $feed as $row ) {
			$name = trim( (string) $row['name'] );

			$out[] = [
				'name'   => '' !== $name ? $name : esc_html__( 'Anonymous', 'pfh-widgets' ),
				'role'   => $this->sub_label( $row ),
				'stars'  => PFH_Widgets_Reviews::stars( $row['rating10'], $scale, $stars ),
				'text'   => (string) $row['text'],
				'avatar' => '',
			];
		}

		return $out;
	}

	/**
	 * @param int $stars Star count.
	 * @return array
	 */
	private function from_manual( $stars ) {
		$rows = $this->get( 'fallback', [] );
		$rows = is_array( $rows ) ? $rows : [];
		$out  = [];

		foreach ( $rows as $row ) {
			$row = (array) $row;

			$out[] = [
				'name'   => isset( $row['name'] ) ? (string) $row['name'] : '',
				'role'   => isset( $row['role'] ) ? (string) $row['role'] : '',
				'stars'  => isset( $row['rating'] ) ? min( (float) $stars, (float) $row['rating'] ) : (float) $stars,
				'text'   => isset( $row['text'] ) ? (string) $row['text'] : '',
				'avatar' => PFH_Widgets_Helpers::image_url( isset( $row['avatar'] ) ? $row['avatar'] : null, 'thumbnail' ),
			];
		}

		return $out;
	}

	/**
	 * The line under the name, per the chosen source.
	 *
	 * @param array $row Normalised review.
	 * @return string
	 */
	private function sub_label( array $row ) {
		$mode = (string) $this->get( 'subLabel', 'city' );
		$text = trim( (string) $this->get( 'subLabelText', '' ) );

		if ( 'none' === $mode ) {
			return '';
		}

		if ( 'custom' === $mode ) {
			return $text;
		}

		if ( 'date' === $mode ) {
			$stamp = strtotime( (string) $row['date'] );

			return $stamp ? date_i18n( (string) $this->get( 'dateFormat', 'F Y' ), $stamp ) : $text;
		}

		$city = trim( (string) $row['city'] );

		return '' !== $city ? $city : $text;
	}

	/**
	 * First letters of a name, for the initials avatar.
	 *
	 * @param string $name Reviewer name.
	 * @return string
	 */
	private function initials( $name ) {
		$parts = preg_split( '/\s+/', trim( (string) $name ) );
		$out   = '';

		foreach ( (array) $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}

			$out .= function_exists( 'mb_substr' ) ? mb_substr( $part, 0, 1 ) : substr( $part, 0, 1 );

			if ( 2 <= strlen( $out ) ) {
				break;
			}
		}

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$cards = $this->cards();

		$classes = [ 'pfh-rev', 'pfh-scope' ];

		if ( 'right' === (string) $this->get( 'railSide', 'left' ) ) {
			$classes[] = 'is-rail-right';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );
		$this->set_attribute( '_root', 'data-pfh-reviews', '' );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-rev__inner">';
		echo '<div class="pfh-rev__layout">';

		$this->render_rail();

		echo '<div class="pfh-rev__slider">';

		if ( $cards ) {
			echo '<div class="pfh-rev__viewport" data-pfh-viewport tabindex="0" role="group" aria-label="' . esc_attr__( 'Customer reviews', 'pfh-widgets' ) . '">';
			echo '<div class="pfh-rev__track" data-pfh-track>';

			foreach ( $cards as $index => $card ) {
				$this->render_card( $card, (int) $index );
			}

			echo '</div>';
			echo '</div>';

			if ( $this->is_on( 'showDots' ) ) {
				printf(
					'<div class="pfh-rev__dots" data-pfh-dots data-pfh-max-dots="%d" data-pfh-dot-label="%s"></div>',
					max( 1, (int) $this->get( 'maxDots', 8 ) ),
					esc_attr__( 'Go to slide %d', 'pfh-widgets' )
				);
			}
		} else {
			printf(
				'<p class="pfh-rev__empty">%s</p>',
				esc_html__( 'No reviews yet. Add the WebwinkelKeur credentials, or fill in the fallback list.', 'pfh-widgets' )
			);
		}

		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</section>';
	}

	private function render_rail() {
		$heading = (string) $this->get( 'heading', '' );
		$lede    = (string) $this->get( 'lede', '' );
		$tag     = (string) $this->get( 'headingTag', 'h2' );
		$tag     = in_array( $tag, [ 'h2', 'h3', 'div' ], true ) ? $tag : 'h2';

		echo '<div class="pfh-rev__aside">';

		if ( '' !== trim( $heading ) ) {
			printf(
				'<%1$s class="pfh-rev__title">%2$s</%1$s>',
				$tag, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted above.
				wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $heading ) ) )
			);
		}

		if ( '' !== trim( $lede ) ) {
			echo '<p class="pfh-rev__lede">' . wp_kses_post( PFH_Widgets_Helpers::dd( $lede ) ) . '</p>';
		}

		if ( $this->is_on( 'showArrows' ) ) {
			$icon = PFH_Widgets_Helpers::image_url( $this->get( 'arrowIcon' ), 'full' );

			$glyph = $icon
				? sprintf( '<img src="%s" alt="" aria-hidden="true" />', esc_url( $icon ) )
				: self::CHEVRON;

			echo '<div class="pfh-rev__nav">';

			printf(
				'<button type="button" class="pfh-rev__arrow pfh-rev__arrow--prev" data-pfh-prev aria-label="%s">%s</button>',
				esc_attr__( 'Previous reviews', 'pfh-widgets' ),
				$glyph // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG or escaped <img>.
			);

			printf(
				'<button type="button" class="pfh-rev__arrow pfh-rev__arrow--next" data-pfh-next aria-label="%s">%s</button>',
				esc_attr__( 'More reviews', 'pfh-widgets' ),
				$glyph // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG or escaped <img>.
			);

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * @param array $card  Prepared review.
	 * @param int   $index Zero based.
	 */
	private function render_card( $card, $index ) {
		printf( '<article class="pfh-rev__card" data-pfh-card="%d">', (int) $index );

		echo '<div class="pfh-rev__who">';
		$this->render_avatar( $card );

		echo '<div class="pfh-rev__id">';
		echo '<span class="pfh-rev__name">' . esc_html( $card['name'] ) . '</span>';

		if ( '' !== trim( (string) $card['role'] ) ) {
			echo '<span class="pfh-rev__role">' . esc_html( $card['role'] ) . '</span>';
		}

		echo '</div>';
		echo '</div>';

		$this->render_stars( (float) $card['stars'] );

		if ( '' !== trim( (string) $card['text'] ) ) {
			echo '<p class="pfh-rev__text">' . esc_html( $card['text'] ) . '</p>';
		}

		$this->render_badge();

		echo '</article>';
	}

	/**
	 * @param array $card Prepared review.
	 */
	private function render_avatar( $card ) {
		$photo = (string) $card['avatar'];
		$alt   = $card['name'] ? sprintf( /* translators: %s: reviewer name. */ esc_attr__( 'Photo of %s', 'pfh-widgets' ), $card['name'] ) : '';

		if ( $photo ) {
			printf(
				'<span class="pfh-rev__avatar"><img src="%s" alt="%s" loading="lazy" decoding="async" width="%d" height="%d" /></span>',
				esc_url( $photo ),
				esc_attr( $alt ),
				(int) $this->get( 'avatarSize', 32 ),
				(int) $this->get( 'avatarSize', 32 )
			);

			return;
		}

		if ( 'initials' === (string) $this->get( 'avatarMode', 'placeholder' ) ) {
			printf(
				'<span class="pfh-rev__avatar" aria-hidden="true">%s</span>',
				esc_html( $this->initials( $card['name'] ) )
			);

			return;
		}

		$fallback = PFH_Widgets_Helpers::image_url( $this->get( 'avatarImage' ), 'thumbnail' );

		if ( $fallback ) {
			printf(
				'<span class="pfh-rev__avatar"><img src="%s" alt="" loading="lazy" decoding="async" /></span>',
				esc_url( $fallback )
			);

			return;
		}

		echo '<span class="pfh-rev__avatar" aria-hidden="true">' . self::AVATAR . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG markup.
	}

	/**
	 * A grey row of stars with a clipped coloured copy over it.
	 *
	 * The clip is `n × star + floor(n) × gap`, so a half star lands exactly on
	 * the middle of its glyph rather than at a percentage of the whole row.
	 *
	 * @param float $stars Filled stars.
	 */
	private function render_stars( $stars ) {
		$count = max( 3, (int) $this->get( 'starCount', 5 ) );
		$stars = max( 0, min( (float) $count, $stars ) );
		$gaps  = min( (int) floor( $stars ), $count - 1 );

		$row = '<span class="pfh-rev__stars-row">' . str_repeat( PFH_Widgets_Icons::get( 'star' ), $count ) . '</span>';

		$width = sprintf(
			'calc(var(--pfh-rv-star) * %s + var(--pfh-rv-star-gap) * %d)',
			rtrim( rtrim( number_format( $stars, 2, '.', '' ), '0' ), '.' ),
			$gaps
		);

		printf(
			'<div class="pfh-rev__stars" role="img" aria-label="%s">%s<span class="pfh-rev__stars-fill" style="width:%s">%s</span></div>',
			esc_attr(
				sprintf(
					/* translators: 1: rating, 2: maximum. */
					__( '%1$s out of %2$s stars', 'pfh-widgets' ),
					number_format_i18n( $stars, 1 ),
					$count
				)
			),
			$row, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG markup.
			esc_attr( $width ),
			$row // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG markup.
		);
	}

	private function render_badge() {
		$logo = PFH_Widgets_Helpers::image_url( $this->get( 'logoImage' ), 'full' );

		if ( ! $logo ) {
			return;
		}

		$img = sprintf(
			'<img class="pfh-rev__logo" src="%s" alt="%s" loading="lazy" decoding="async" />',
			esc_url( $logo ),
			esc_attr__( 'WebwinkelKeur', 'pfh-widgets' )
		);

		echo '<div class="pfh-rev__foot">';

		$url = $this->is_on( 'linkBadge' ) ? trim( (string) $this->get( 'reviewUrl', '' ) ) : '';

		if ( '' !== $url ) {
			printf(
				'<a href="%s" target="_blank" rel="noopener nofollow">%s</a>',
				esc_url( $url ),
				$img // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);
		} else {
			echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}

		echo '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Tokens
	 * ------------------------------------------------------------------ */

	private function build_vars() {
		$stack = ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif';
		$head  = trim( (string) $this->get( 'headFamily', 'Playfair Display' ) );

		// The split is authored in px against the content width, then emitted
		// as a proportion: exact at 1110, in proportion below it.
		$container = max( 1.0, (float) $this->get( 'containerWidth', 1140 ) );
		$rail      = max( 0.0, (float) $this->get( 'railWidth', 362 ) );
		$split     = max( 0.0, (float) $this->get( 'splitGap', 68 ) );

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-container'          => PFH_Widgets_Helpers::unit( $container ),
				'--pfh-gutter-set'         => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-rv-pad-y-set'       => PFH_Widgets_Helpers::unit( $this->get( 'paddingY', 120 ) ),
				'--pfh-rv-aside-w-set'     => round( min( 90.0, $rail / $container * 100 ), 4 ) . '%',
				'--pfh-rv-split-gap-set'   => round( min( 40.0, $split / $container * 100 ), 4 ) . '%',
				'--pfh-rv-stack-gap-set'   => PFH_Widgets_Helpers::unit( $this->get( 'stackGap', 48 ) ),
				'--pfh-rv-bg'              => PFH_Widgets_Helpers::color( $this->get( 'bgColor' ) ),
				'--pfh-rv-radius'          => PFH_Widgets_Helpers::unit( $this->get( 'radius', 0 ) ),

				'--pfh-rv-cols-set'        => (int) $this->get( 'cols', 2 ),
				'--pfh-rv-rows-set'        => (int) $this->get( 'rows', 2 ),
				'--pfh-rv-gap-set'         => PFH_Widgets_Helpers::unit( $this->get( 'cardGap', 20 ) ),

				'--pfh-rv-card-bg'         => PFH_Widgets_Helpers::color( $this->get( 'cardBg' ), '#eef3f1' ),
				'--pfh-rv-card-radius'     => PFH_Widgets_Helpers::unit( $this->get( 'cardRadius', 16 ) ),
				'--pfh-rv-card-pad-set'    => PFH_Widgets_Helpers::unit( $this->get( 'cardPadding', 20 ) ),

				'--pfh-rv-avatar'          => PFH_Widgets_Helpers::unit( $this->get( 'avatarSize', 32 ) ),
				'--pfh-rv-avatar-gap'      => PFH_Widgets_Helpers::unit( $this->get( 'avatarGap', 12 ) ),
				'--pfh-rv-avatar-bg'       => PFH_Widgets_Helpers::color( $this->get( 'avatarBg' ), '#d9e4e0' ),
				'--pfh-rv-avatar-color'    => PFH_Widgets_Helpers::color( $this->get( 'avatarColor' ), '#2b5f63' ),

				'--pfh-rv-name-size'       => PFH_Widgets_Helpers::unit( $this->get( 'nameSize', 14 ) ),
				'--pfh-rv-name-weight'     => $this->get( 'nameWeight', '500' ),
				'--pfh-rv-name-color'      => PFH_Widgets_Helpers::color( $this->get( 'nameColor' ), '#3d0023' ),
				'--pfh-rv-role-size'       => PFH_Widgets_Helpers::unit( $this->get( 'roleSize', 14 ) ),
				'--pfh-rv-role-weight'     => $this->get( 'roleWeight', '400' ),
				'--pfh-rv-role-color'      => PFH_Widgets_Helpers::color( $this->get( 'roleColor' ), 'rgba(0, 0, 0, 0.62)' ),

				'--pfh-rv-star'            => PFH_Widgets_Helpers::unit( $this->get( 'starSize', 16 ) ),
				'--pfh-rv-star-gap'        => PFH_Widgets_Helpers::unit( $this->get( 'starGap', 4 ) ),
				'--pfh-rv-star-on'         => PFH_Widgets_Helpers::color( $this->get( 'starOn' ), '#2f7e7c' ),
				'--pfh-rv-star-off'        => PFH_Widgets_Helpers::color( $this->get( 'starOff' ), '#cfdcd8' ),

				'--pfh-rv-text-size-set'   => PFH_Widgets_Helpers::unit( $this->get( 'textSize', 16 ) ),
				'--pfh-rv-text-weight'     => $this->get( 'textWeight', '300' ),
				'--pfh-rv-text-lh'         => $this->get( 'textLineHeight', 1.5 ),
				'--pfh-rv-text-color'      => PFH_Widgets_Helpers::color( $this->get( 'textColor' ), '#3d4a4d' ),
				'--pfh-rv-lines'           => (int) $this->get( 'textLines', 4 ),

				'--pfh-rv-logo-h'          => PFH_Widgets_Helpers::unit( $this->get( 'logoHeight', 16 ) ),
				'--pfh-rv-gap-1'           => PFH_Widgets_Helpers::unit( $this->get( 'gapWhoStars', 18 ) ),
				'--pfh-rv-gap-2'           => PFH_Widgets_Helpers::unit( $this->get( 'gapStarsText', 16 ) ),
				'--pfh-rv-gap-3'           => PFH_Widgets_Helpers::unit( $this->get( 'gapTextLogo', 16 ) ),

				'--pfh-head-font'          => $head ? $head . $stack : 'inherit',
				'--pfh-head-size-set'      => PFH_Widgets_Helpers::unit( $this->get( 'headSize', 42 ) ),
				'--pfh-head-size-m'        => PFH_Widgets_Helpers::unit( $this->get( 'headSizeMobile', 30 ) ),
				'--pfh-head-weight'        => $this->get( 'headWeight', '400' ),
				'--pfh-head-lh'            => $this->get( 'headLineHeight', 1.15 ),
				'--pfh-head-color'         => PFH_Widgets_Helpers::color( $this->get( 'headColor' ), '#22504a' ),
				'--pfh-head-accent'        => PFH_Widgets_Helpers::color( $this->get( 'headAccent' ), '#6b9691' ),
				'--pfh-head-accent-weight' => $this->get( 'headAccentWeight', '400' ),
				'--pfh-head-ul-offset'     => PFH_Widgets_Helpers::unit( $this->get( 'headUnderlineOffset', 6 ) ),
				'--pfh-head-ul-width'      => PFH_Widgets_Helpers::unit( $this->get( 'headUnderlineWidth', 1 ) ),

				'--pfh-rv-lede-size'       => PFH_Widgets_Helpers::unit( $this->get( 'ledeSize', 14 ) ),
				'--pfh-rv-lede-weight'     => $this->get( 'ledeWeight', '300' ),
				'--pfh-rv-lede-lh'         => $this->get( 'ledeLineHeight', 1.5 ),
				'--pfh-rv-lede-color'      => PFH_Widgets_Helpers::color( $this->get( 'ledeColor' ), 'rgba(0, 0, 0, 0.62)' ),
				'--pfh-rv-gap-head'        => PFH_Widgets_Helpers::unit( $this->get( 'gapHeadLede', 20 ) ),
				'--pfh-rv-gap-lede'        => PFH_Widgets_Helpers::unit( $this->get( 'gapLedeNav', 24 ) ),

				'--pfh-rv-btn'             => PFH_Widgets_Helpers::unit( $this->get( 'arrowSize', 38 ) ),
				'--pfh-rv-btn-gap'         => PFH_Widgets_Helpers::unit( $this->get( 'arrowGap', 8 ) ),
				'--pfh-rv-btn-icon'        => PFH_Widgets_Helpers::unit( $this->get( 'arrowIconSize', 13 ) ),
				'--pfh-rv-btn-color'       => PFH_Widgets_Helpers::color( $this->get( 'arrowColor' ), '#2b5f63' ),
				'--pfh-rv-btn-border'      => PFH_Widgets_Helpers::color( $this->get( 'arrowBorder' ), '#d3dedb' ),
				'--pfh-rv-btn-hover-bg'    => PFH_Widgets_Helpers::color( $this->get( 'arrowHoverBg' ), '#2b5f63' ),
				'--pfh-rv-btn-hover-color' => PFH_Widgets_Helpers::color( $this->get( 'arrowHoverColor' ), '#ffffff' ),

				'--pfh-rv-dot'             => PFH_Widgets_Helpers::unit( $this->get( 'dotSize', 8 ) ),
				'--pfh-rv-dot-gap'         => PFH_Widgets_Helpers::unit( $this->get( 'dotGap', 10 ) ),
				'--pfh-rv-dot-on'          => PFH_Widgets_Helpers::color( $this->get( 'dotOn' ), '#2b5f63' ),
				'--pfh-rv-dot-off'         => PFH_Widgets_Helpers::color( $this->get( 'dotOff' ), '#cfdcd8' ),
				'--pfh-rv-dots-gap'        => PFH_Widgets_Helpers::unit( $this->get( 'dotsGap', 28 ) ),
			]
		);
	}
}
