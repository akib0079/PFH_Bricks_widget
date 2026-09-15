<?php
/**
 * Bricks element: Products For Home closing call to action.
 *
 * A rounded card on a mint gradient — copy on the left, a cut-out photo
 * bleeding off the bottom right — that deliberately overlaps whatever follows
 * it: the section carries a negative bottom margin and its own stacking
 * context, so the footer slides up underneath the card.
 *
 * Figma geometry (1440 frame): card 1110 x 419, radius 20, text column 503
 * inset 80, button 206 x 50.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Cta extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-cta';
	public $icon         = 'ti-announcement';
	public $css_selector = '.pfh-cta';

	const BASE = 'https://m01a032ada4d2735fbc629e14eb62edd.kinsta.cloud/wp-content/uploads/2026/09/';

	/** The card's mint gradient, exported at 1920 x 725 — the card's own ratio. */
	const BG_URL = self::BASE . 'Frame-469978-1.jpg';

	/**
	 * The cut-out photo.
	 *
	 * The .jpg of this asset is flattened onto black — JPEG cannot carry an
	 * alpha channel — so the default points at the .png beside it, which has
	 * the real cut-out.
	 */
	const IMG_URL = self::BASE . 'Gemini_Generated_Image_kvzwuikvzwuikvzw-1.png';

	/** The supplied arrow, inlined so it can take currentColor. */
	const ARROW = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false"><path d="M5.22 3.46s5.887-.46 6.716.369c.83.829.369 6.716.369 6.716M11.584 4.18 3.766 11.999" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

	public function get_label() {
		return esc_html__( 'PFH Call To Action', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'cta', 'call to action', 'banner', 'overlap', 'footer', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::cta();
	}

	public function set_control_groups() {
		$this->control_groups['content'] = [ 'title' => esc_html__( 'Content', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']    = [ 'title' => esc_html__( 'Typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['button']  = [ 'title' => esc_html__( 'Button', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['media']   = [ 'title' => esc_html__( 'Photo', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['card']    = [ 'title' => esc_html__( 'Card & background', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['overlap'] = [ 'title' => esc_html__( 'Overlap & layout', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->content_controls();
		$this->type_controls();
		$this->button_controls();
		$this->media_controls();
		$this->card_controls();
		$this->overlap_controls();
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
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
			'type'        => 'textarea',
			'default'     => "Bring the essence of the\n<em>mediterranean home</em>",
			'description' => esc_html__( 'Line breaks are kept. Wrap the italic underlined part in <em>…</em>.', 'pfh-widgets' ),
		];

		$this->controls['text'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Text', 'pfh-widgets' ),
			'type'    => 'textarea',
			'default' => 'Discover thoughtfully crafted flavors inspired by timeless coastal traditions — from golden honey and handpicked olives to refreshing natural juices made for everyday moments.',
		];

		$this->controls['headingTag'] = [
			'tab'     => 'content',
			'group'   => 'content',
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

		$this->controls['btnLabel'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Button label', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Explore the Collection',
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
			'min'     => 16,
			'max'     => 80,
			'inline'  => true,
			'default' => 34,
		];

		$this->controls['headSizeTablet'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading size on tablet (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 14,
			'max'     => 60,
			'inline'  => true,
			'default' => 28,
		];

		$this->controls['headSizeMobile'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Heading size on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 14,
			'max'     => 48,
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
			'default' => 1.05,
		];

		$this->controls['headColor'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Heading colour', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#22301c' ],
			'description' => esc_html__( 'Olive Green / green-900 in the Figma library.', 'pfh-widgets' ),
		];

		$this->controls['headAccent'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Accent colour', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#6f8566' ],
			'description' => esc_html__( 'Olive Green / green-600 — the italic underlined line.', 'pfh-widgets' ),
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

		$this->controls['textSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Text size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 24,
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
			'default' => [ 'hex' => '#22301c' ],
		];

		$this->controls['gapHeadText'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Gap heading → text (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 32,
		];

		$this->controls['gapTextBtn'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Gap text → button (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 20,
		];
	}

	/* ---------------------------------------------------------------------
	 * Button
	 * ------------------------------------------------------------------ */

	private function button_controls() {
		$this->controls['btnIcon'] = [
			'tab'         => 'content',
			'group'       => 'button',
			'label'       => esc_html__( 'Button icon', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'Leave empty to use the supplied arrow inline, which lets it follow the label colour.', 'pfh-widgets' ),
		];

		$this->controls['btnHeight'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Height (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 30,
			'max'     => 90,
			'inline'  => true,
			'default' => 50,
		];

		$this->controls['btnPadding'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Side padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 8,
			'max'     => 80,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['btnGap'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Label → icon gap (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 40,
			'inline'  => true,
			'default' => 4,
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
			'default' => 15,
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
			'default' => 16,
		];

		$this->controls['btnBg'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#2b5f63' ],
		];

		$this->controls['btnOpacity'] = [
			'tab'         => 'content',
			'group'       => 'button',
			'label'       => esc_html__( 'Background opacity (%)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 10,
			'max'         => 100,
			'inline'      => true,
			'default'     => 80,
			'description' => esc_html__( 'Figma has the fill at 80% with the label at full strength, so only the fill is translucent.', 'pfh-widgets' ),
		];

		$this->controls['btnHoverOpacity'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Hover opacity (%)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 100,
			'inline'  => true,
			'default' => 100,
		];

		$this->controls['btnColor'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Label colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#ffffff' ],
		];

		$this->controls['btnShift'] = [
			'tab'     => 'content',
			'group'   => 'button',
			'label'   => esc_html__( 'Hover lift (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 10,
			'inline'  => true,
			'default' => 2,
		];
	}

	/* ---------------------------------------------------------------------
	 * Photo
	 * ------------------------------------------------------------------ */

	private function media_controls() {
		$this->controls['image'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Photo', 'pfh-widgets' ),
			'type'        => 'image',
			'default'     => [ 'url' => self::IMG_URL ],
			'description' => esc_html__( 'Needs a real cut-out. The default points at the PNG — the JPG of this artwork is flattened onto black, because JPEG cannot carry transparency.', 'pfh-widgets' ),
		];

		$this->controls['imageAlt'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Photo alt text', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Three fresh Greek juices raised together',
		];

		$this->controls['imageHeight'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Photo height (% of card)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 40,
			'max'         => 160,
			'inline'      => true,
			'default'     => 102,
			'description' => esc_html__( 'Sized off the card height so it tracks the card as the container narrows.', 'pfh-widgets' ),
		];

		$this->controls['imageRight'] = [
			'tab'     => 'content',
			'group'   => 'media',
			'label'   => esc_html__( 'Inset from the right (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => -200,
			'max'     => 400,
			'inline'  => true,
			'default' => 40,
		];

		$this->controls['imageBottom'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Inset from the bottom (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => -200,
			'max'         => 400,
			'inline'      => true,
			'default'     => 0,
			'description' => esc_html__( 'The card clips its contents, so a negative value runs the photo off the edge.', 'pfh-widgets' ),
		];

		$this->controls['imageMobile'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Show the photo on mobile', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Below 768px the card stacks and the photo runs full width along its bottom edge.', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Card
	 * ------------------------------------------------------------------ */

	private function card_controls() {
		$this->controls['bgImage'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Card background', 'pfh-widgets' ),
			'type'    => 'image',
			'default' => [ 'url' => self::BG_URL ],
		];

		$this->controls['bgGradient'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Gradient fallback', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'linear-gradient(180deg, #f5f7f4 0%, #e5f1f1 52%, #d7eced 100%)',
			'description' => esc_html__( 'Any CSS gradient. Sits under the artwork and shows while it loads.', 'pfh-widgets' ),
		];

		$this->controls['bgSize'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Background size', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'cover'     => esc_html__( 'Cover', 'pfh-widgets' ),
				'contain'   => esc_html__( 'Contain', 'pfh-widgets' ),
				'100% 100%' => esc_html__( 'Stretch', 'pfh-widgets' ),
			],
			'default' => 'cover',
		];

		$this->controls['bgPosition'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Background position', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'center center',
		];

		$this->controls['cardWidth'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Card width (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 400,
			'max'         => 1920,
			'inline'      => true,
			'default'     => 1140,
			'description' => esc_html__( 'Also the content width. The card fills it.', 'pfh-widgets' ),
		];

		$this->controls['cardHeight'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Card height (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 160,
			'max'         => 900,
			'inline'      => true,
			'default'     => 419,
			'description' => esc_html__( 'Held as a ratio against the width, so the card keeps its shape as it narrows. Content can still push it taller.', 'pfh-widgets' ),
		];

		$this->controls['cardRadius'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['cardPadding'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Side padding (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 200,
			'inline'      => true,
			'default'     => 80,
			'description' => esc_html__( 'Measured against the card width, then held as a proportion.', 'pfh-widgets' ),
		];

		$this->controls['textWidth'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Text column width (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 200,
			'max'         => 1200,
			'inline'      => true,
			'default'     => 503,
			'description' => esc_html__( 'Figma measures the heading and copy frames at 503.', 'pfh-widgets' ),
		];

		$this->controls['cardShadow'] = [
			'tab'         => 'content',
			'group'       => 'card',
			'label'       => esc_html__( 'Card shadow', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 'none',
			'description' => esc_html__( 'Any CSS box-shadow, e.g. 0 30px 60px rgba(34, 48, 28, 0.12).', 'pfh-widgets' ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Overlap
	 * ------------------------------------------------------------------ */

	private function overlap_controls() {
		$this->controls['overlapInfo'] = [
			'tab'     => 'content',
			'group'   => 'overlap',
			'type'    => 'info',
			'content' => esc_html__( 'The section pulls whatever follows it upward with a negative bottom margin, and sits in its own stacking context so the card paints over it. Place this element directly above the footer.', 'pfh-widgets' ),
		];

		$this->controls['overlap'] = [
			'tab'     => 'content',
			'group'   => 'overlap',
			'label'   => esc_html__( 'Overlap the next section by (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 400,
			'inline'  => true,
			'default' => 80,
		];

		$this->controls['overlapMobile'] = [
			'tab'     => 'content',
			'group'   => 'overlap',
			'label'   => esc_html__( 'Overlap on mobile (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 300,
			'inline'  => true,
			'default' => 48,
		];

		$this->controls['zIndex'] = [
			'tab'         => 'content',
			'group'       => 'overlap',
			'label'       => esc_html__( 'Stacking order', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 99,
			'inline'      => true,
			'default'     => 2,
			'description' => esc_html__( 'Raise this if something in the footer still paints over the card.', 'pfh-widgets' ),
		];

		$this->controls['paddingTop'] = [
			'tab'     => 'content',
			'group'   => 'overlap',
			'label'   => esc_html__( 'Top padding (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 400,
			'inline'  => true,
			'default' => 0,
		];

		$this->controls['paddingBottom'] = [
			'tab'         => 'content',
			'group'       => 'overlap',
			'label'       => esc_html__( 'Bottom padding (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 400,
			'inline'      => true,
			'default'     => 0,
			'description' => esc_html__( 'Added before the overlap is subtracted.', 'pfh-widgets' ),
		];

		$this->controls['containerPadding'] = [
			'tab'     => 'content',
			'group'   => 'overlap',
			'label'   => esc_html__( 'Side gutter (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 120,
			'inline'  => true,
			'default' => 24,
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
		$classes = [ 'pfh-cta', 'pfh-scope' ];

		if ( ! $this->is_on( 'imageMobile' ) ) {
			$classes[] = 'hide-media-mobile';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-cta__inner">';
		echo '<div class="pfh-cta__card">';

		$this->render_content();
		$this->render_media();

		echo '</div>';
		echo '</div>';
		echo '</section>';
	}

	private function render_content() {
		$heading = (string) $this->get( 'heading', '' );
		$text    = (string) $this->get( 'text', '' );
		$label   = trim( (string) $this->get( 'btnLabel', '' ) );

		$tag = (string) $this->get( 'headingTag', 'h2' );
		$tag = in_array( $tag, [ 'h2', 'h3', 'div' ], true ) ? $tag : 'h2';

		echo '<div class="pfh-cta__content">';

		if ( '' !== trim( $heading ) ) {
			printf(
				'<%1$s class="pfh-cta__title">%2$s</%1$s>',
				$tag, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted above.
				wp_kses_post( nl2br( PFH_Widgets_Helpers::dd( $heading ) ) )
			);
		}

		if ( '' !== trim( $text ) ) {
			echo '<p class="pfh-cta__text">' . wp_kses_post( PFH_Widgets_Helpers::dd( $text ) ) . '</p>';
		}

		if ( '' !== $label ) {
			$link  = PFH_Widgets_Helpers::link( $this->get( 'btnLink' ) );
			$icon  = PFH_Widgets_Helpers::image_url( $this->get( 'btnIcon' ), 'full' );
			$glyph = $icon
				? sprintf( '<img src="%s" alt="" aria-hidden="true" />', esc_url( $icon ) )
				: self::ARROW;

			// Nowhere to go means it must not pretend to be a link.
			$is_link = '' !== $link['href'];
			$el      = $is_link ? 'a' : 'span';
			$attrs   = $is_link ? PFH_Widgets_Helpers::link_attrs( $link ) : '';

			echo '<' . $el . ' class="pfh-cta__btn"' . $attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().
			echo '<span>' . esc_html( PFH_Widgets_Helpers::dd( $label ) ) . '</span>';
			echo $glyph; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG or escaped <img>.
			echo '</' . $el . '>';
		}

		echo '</div>';
	}

	private function render_media() {
		$setting = $this->get( 'image' );
		$image   = PFH_Widgets_Helpers::image_url( $setting, 'large' );

		if ( ! $image ) {
			return;
		}

		// The photo's box is sized from its height, so its width is
		// shrink-to-fit from the intrinsic ratio — which a lazy image does not
		// have until it loads. Publishing the dimensions keeps the card stable.
		$dims = '';

		if ( is_array( $setting ) && ! empty( $setting['id'] ) && function_exists( 'wp_get_attachment_image_src' ) ) {
			$src = wp_get_attachment_image_src( (int) $setting['id'], 'large' );

			if ( ! empty( $src[1] ) && ! empty( $src[2] ) ) {
				$dims = sprintf( ' width="%d" height="%d"', (int) $src[1], (int) $src[2] );
			}
		}

		printf(
			'<div class="pfh-cta__media"><img src="%s" alt="%s"%s loading="lazy" decoding="async" /></div>',
			esc_url( $image ),
			esc_attr( PFH_Widgets_Helpers::dd( (string) $this->get( 'imageAlt', '' ) ) ),
			$dims // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from integers above.
		);
	}

	/* ---------------------------------------------------------------------
	 * Tokens
	 * ------------------------------------------------------------------ */

	private function build_vars() {
		$stack = ', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif';
		$head  = trim( (string) $this->get( 'headFamily', 'Playfair Display' ) );
		$bg    = PFH_Widgets_Helpers::image_url( $this->get( 'bgImage' ), 'full' );

		// Card metrics are authored in px and emitted as proportions, so the
		// card keeps its shape — and the photo, which is sized off the card
		// height, stays in step with the text column.
		$width  = max( 1.0, (float) $this->get( 'cardWidth', 1140 ) );
		$height = max( 1.0, (float) $this->get( 'cardHeight', 419 ) );
		$pad    = max( 0.0, (float) $this->get( 'cardPadding', 80 ) );
		$text_w = max( 0.0, (float) $this->get( 'textWidth', 503 ) );
		// A percentage max-width on the copy resolves against the card's
		// content box, which the side padding has already narrowed — measuring
		// it against the full card width would come out short by 2 x padding.
		$inner  = max( 1.0, $width - ( 2 * $pad ) );
		$right  = (float) $this->get( 'imageRight', 40 );
		$shadow = trim( (string) $this->get( 'cardShadow', '' ) );

		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-container'          => PFH_Widgets_Helpers::unit( $width ),
				'--pfh-gutter-set'         => PFH_Widgets_Helpers::unit( $this->get( 'containerPadding', 24 ) ),
				'--pfh-cta-pad-t-set'      => PFH_Widgets_Helpers::unit( $this->get( 'paddingTop', 0 ) ),
				'--pfh-cta-pad-b-set'      => PFH_Widgets_Helpers::unit( $this->get( 'paddingBottom', 0 ) ),
				'--pfh-cta-overlap-set'    => PFH_Widgets_Helpers::unit( $this->get( 'overlap', 80 ) ),
				'--pfh-cta-overlap-m'      => PFH_Widgets_Helpers::unit( $this->get( 'overlapMobile', 48 ) ),
				'--pfh-cta-z'              => (int) $this->get( 'zIndex', 2 ),

				'--pfh-cta-ratio-set'      => $width . ' / ' . $height,
				'--pfh-cta-min-set'        => PFH_Widgets_Helpers::unit( $height ),
				'--pfh-cta-radius'         => PFH_Widgets_Helpers::unit( $this->get( 'cardRadius', 20 ) ),
				'--pfh-cta-bg'             => (string) $this->get( 'bgGradient', '' ),
				'--pfh-cta-bg-img'         => $bg ? 'url(' . $bg . ')' : 'none',
				'--pfh-cta-bg-size'        => $this->get( 'bgSize', 'cover' ),
				'--pfh-cta-bg-pos'         => $this->get( 'bgPosition', 'center center' ),
				'--pfh-cta-shadow'         => '' !== $shadow ? $shadow : 'none',

				'--pfh-cta-pad-x-set'      => round( min( 45.0, $pad / $width * 100 ), 4 ) . '%',
				'--pfh-cta-text-w-set'     => round( min( 100.0, $text_w / $inner * 100 ), 4 ) . '%',

				'--pfh-head-font'          => $head ? $head . $stack : 'inherit',
				'--pfh-cta-head-size-set'  => PFH_Widgets_Helpers::unit( $this->get( 'headSize', 34 ) ),
				'--pfh-cta-head-size-m'    => PFH_Widgets_Helpers::unit( $this->get( 'headSizeTablet', 28 ) ),
				'--pfh-cta-head-size-s'    => PFH_Widgets_Helpers::unit( $this->get( 'headSizeMobile', 24 ) ),
				'--pfh-cta-head-weight'    => $this->get( 'headWeight', '400' ),
				'--pfh-cta-head-lh'        => $this->get( 'headLineHeight', 1.05 ),
				'--pfh-cta-head-color'     => PFH_Widgets_Helpers::color( $this->get( 'headColor' ), '#22301c' ),
				'--pfh-cta-accent'         => PFH_Widgets_Helpers::color( $this->get( 'headAccent' ), '#6f8566' ),
				'--pfh-cta-ul-offset'      => PFH_Widgets_Helpers::unit( $this->get( 'headUnderlineOffset', 6 ) ),

				'--pfh-cta-text-size-set'  => PFH_Widgets_Helpers::unit( $this->get( 'textSize', 14 ) ),
				'--pfh-cta-text-weight'    => $this->get( 'textWeight', '300' ),
				'--pfh-cta-text-lh'        => $this->get( 'textLineHeight', 1.5 ),
				'--pfh-cta-text-color'     => PFH_Widgets_Helpers::color( $this->get( 'textColor' ), '#22301c' ),
				'--pfh-cta-gap-1'          => PFH_Widgets_Helpers::unit( $this->get( 'gapHeadText', 32 ) ),
				'--pfh-cta-gap-2'          => PFH_Widgets_Helpers::unit( $this->get( 'gapTextBtn', 20 ) ),

				'--pfh-cta-btn-h'          => PFH_Widgets_Helpers::unit( $this->get( 'btnHeight', 50 ) ),
				'--pfh-cta-btn-pad-set'    => PFH_Widgets_Helpers::unit( $this->get( 'btnPadding', 20 ) ),
				'--pfh-cta-btn-gap'        => PFH_Widgets_Helpers::unit( $this->get( 'btnGap', 4 ) ),
				'--pfh-cta-btn-radius'     => PFH_Widgets_Helpers::unit( $this->get( 'btnRadius', 70 ) ),
				'--pfh-cta-btn-size'       => PFH_Widgets_Helpers::unit( $this->get( 'btnSize', 15 ) ),
				'--pfh-cta-btn-weight'     => $this->get( 'btnWeight', '500' ),
				'--pfh-cta-btn-bg'         => PFH_Widgets_Helpers::color( $this->get( 'btnBg' ), '#2b5f63' ),
				'--pfh-cta-btn-op'         => round( (float) $this->get( 'btnOpacity', 80 ) / 100, 3 ),
				'--pfh-cta-btn-hover-op'   => round( (float) $this->get( 'btnHoverOpacity', 100 ) / 100, 3 ),
				'--pfh-cta-btn-color'      => PFH_Widgets_Helpers::color( $this->get( 'btnColor' ), '#ffffff' ),
				'--pfh-cta-btn-icon'       => PFH_Widgets_Helpers::unit( $this->get( 'btnIconSize', 16 ) ),
				'--pfh-cta-btn-shift'      => '-' . PFH_Widgets_Helpers::unit( abs( (float) $this->get( 'btnShift', 2 ) ) ),

				'--pfh-cta-img-h-set'      => PFH_Widgets_Helpers::unit( $this->get( 'imageHeight', 102 ), '%' ),
				'--pfh-cta-img-right-set'  => round( $right / $width * 100, 4 ) . '%',
				'--pfh-cta-img-bottom'     => PFH_Widgets_Helpers::unit( $this->get( 'imageBottom', 0 ) ),
			]
		);
	}
}
