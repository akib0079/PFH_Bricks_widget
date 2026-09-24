<?php
/**
 * Bricks element: Products For Home product highlight.
 *
 * The wide banner that puts one offer forward — an eyebrow, a two-line title,
 * a short pitch, ticked selling points, the price, an optional button — beside
 * a photograph that runs to the card's edge. Built to sit directly above the
 * footer and hang over it.
 *
 * In the builder it is static on purpose. Every word, price and link is typed
 * into the panel and printed as typed: no product lookup, no dynamic data, no
 * queries. There is nothing here that can fail during a save.
 *
 * On a category page the category can take it over, from its own edit screen
 * (PFH_Widgets_Collection): hide it, reword any part of it, and tie it to one
 * product — which then supplies the price, the saving, the picture and the
 * link, so the offer can never go stale against the shop. Whatever the
 * category leaves empty is what is typed here. That happens at render only,
 * on the live page, and a product that has gone away simply falls back.
 *
 * Two rules keep it that way.
 *
 *   The defaults live in DEFAULTS, which the panel and the render both read,
 *   so what the page shows never depends on Bricks having built the control
 *   list first.
 *
 *   Nothing it stores contains a 4-byte character. The previous version's
 *   eyebrow icon defaulted to the gift emoji, and where the postmeta table is
 *   utf8 rather than utf8mb4 WordPress refuses to write any value holding one —
 *   the whole page, every element on it, not just this one. So the gift is a
 *   switch, and the emoji is printed as an HTML entity that is never saved.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Highlight extends \Bricks\Element {

	/*
	 * Every default, in one place. Text defaults must stay within the Basic
	 * Multilingual Plane — see the note above; dev/audit.php refuses a shipped
	 * file that breaks this.
	 */
	const DEFAULTS = [
		// Copy.
		'eyebrow'     => 'Meest gekozen',
		'showGift'    => true,
		'titleTop'    => 'Proefpakket',
		'titleBottom' => '3 smaken naar keuze',
		'titleTag'    => 'h2',
		'text'        => 'Ontdek de wereld van Gia Giamas. Kies zelf drie smaken uit het volledige assortiment en bespaar direct €5,00 t.o.v. losse aankoop.',

		// Selling points.
		'points'      => [
			[ 'text' => 'Kies zelf 3 smaken uit het assortiment' ],
			[ 'text' => 'Ideaal cadeau — inclusief receptenkaart' ],
			[ 'text' => 'Gratis verzending bij bestelling' ],
		],
		'pointColor'  => '#3f4c3e',
		'tickColor'   => '#7d9569',

		// Price.
		'price'       => '€ 41,97',
		'priceWas'    => '€ 46,97',
		'saving'      => 'Bespaar €5,00 — 11% korting',

		// Image.
		'imageAlt'    => '',
		'imageWidth'  => 50,
		'imageBlend'  => true,
		'imageFade'   => 14,
		'imageSide'   => 'right',
		'imageFit'    => 'auto',
		'imageMultiply' => true,

		// Button and link.
		'btnLabel'    => '',
		'url'         => '',
		'newTab'      => false,
		'clickable'   => true,
		'btnRadius'   => '',
		'btnBg'       => '',
		'btnColor'    => '',

		// Style.
		'bg'          => '#d9e6dc',
		'radius'      => 20,
		'imageRadius' => 0,
		'padX'        => 52,
		'padY'        => 48,
		'eyebrowSize' => 22,
		'titleSize'   => 32,
		'textSize'    => 14,
		'priceSize'   => 24,
		'ink'         => '#22301c',
		'shadow'      => true,

		// The category's own settings.
		'fromCategory' => true,

		// Layout.
		'maxWidth'    => 1240,
		'minHeight'   => 468,
		'padTop'      => 0,
		'overlap'     => 74,
		'padBottom'   => 56,
	];

	/** U+1F381, printed as an entity so the character itself is never stored. */
	const GIFT = '&#x1F381;';

	/**
	 * The category's own settings for this banner, on a category page.
	 *
	 * @var array|null
	 */
	private $own = null;

	/**
	 * What the category's chosen product offers: price, saving, link, picture.
	 *
	 * @var array|null
	 */
	private $offer = null;

	/**
	 * The picture being drawn, worked out once per render.
	 *
	 * @var array{url:string, alt:string, id:int}|null
	 */
	private $picture = null;

	public $category     = 'products-for-home';
	public $name         = 'pfh-highlight';
	public $icon         = 'ti-package';
	public $css_selector = '.pfh-hl';

	public function get_label() {
		return esc_html__( 'PFH Product Highlight', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'highlight', 'product', 'banner', 'offer', 'bundle', 'cta', 'promo', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::highlight();
	}

	public function set_control_groups() {
		$this->control_groups['copy']   = [ 'title' => esc_html__( 'Copy', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['points'] = [ 'title' => esc_html__( 'Selling points', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['price']  = [ 'title' => esc_html__( 'Price', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['media']  = [ 'title' => esc_html__( 'Image', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['button'] = [ 'title' => esc_html__( 'Button and link', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['style']  = [ 'title' => esc_html__( 'Style', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout'] = [ 'title' => esc_html__( 'Layout', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->copy_controls();
		$this->point_controls();
		$this->price_controls();
		$this->media_controls();
		$this->button_controls();
		$this->style_controls();
		$this->layout_controls();
	}

	/* ---------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	/**
	 * A plain text field: typed, stored and printed as-is.
	 *
	 * @param string $group  Control group.
	 * @param string $label  Panel label.
	 * @param string $key    Setting key, for its default.
	 * @param array  $extra  Anything else the control needs.
	 * @return array
	 */
	private function field( $group, $label, $key, array $extra = [] ) {
		return array_merge(
			[
				'tab'            => 'content',
				'group'          => $group,
				'label'          => $label,
				'type'           => 'text',
				'default'        => self::DEFAULTS[ $key ],
				// Static by design: no dynamic-data picker on any field.
				'hasDynamicData' => false,
			],
			$extra
		);
	}

	/**
	 * A number field with a range.
	 *
	 * @param string $group Control group.
	 * @param string $label Panel label.
	 * @param string $key   Setting key, for its default.
	 * @param int    $min   Lowest value.
	 * @param int    $max   Highest value.
	 * @param array  $extra Anything else the control needs.
	 * @return array
	 */
	private function number_field( $group, $label, $key, $min, $max, array $extra = [] ) {
		$control = [
			'tab'    => 'content',
			'group'  => $group,
			'label'  => $label,
			'type'   => 'number',
			'min'    => $min,
			'max'    => $max,
			'inline' => true,
		];

		// An empty default means "leave it to the stylesheet", so declare none.
		if ( '' !== self::DEFAULTS[ $key ] ) {
			$control['default'] = self::DEFAULTS[ $key ];
		}

		return array_merge( $control, $extra );
	}

	/**
	 * A colour field.
	 *
	 * @param string $group Control group.
	 * @param string $label Panel label.
	 * @param string $key   Setting key, for its default.
	 * @return array
	 */
	private function colour_field( $group, $label, $key ) {
		$control = [
			'tab'    => 'content',
			'group'  => $group,
			'label'  => $label,
			'type'   => 'color',
			'inline' => true,
		];

		if ( '' !== self::DEFAULTS[ $key ] ) {
			$control['default'] = [ 'hex' => self::DEFAULTS[ $key ] ];
		}

		return $control;
	}

	/**
	 * A switch.
	 *
	 * @param string $group Control group.
	 * @param string $label Panel label.
	 * @param string $key   Setting key, for its default.
	 * @param array  $extra Anything else the control needs.
	 * @return array
	 */
	private function switch_field( $group, $label, $key, array $extra = [] ) {
		return array_merge(
			[
				'tab'     => 'content',
				'group'   => $group,
				'label'   => $label,
				'type'    => 'checkbox',
				'default' => (bool) self::DEFAULTS[ $key ],
			],
			$extra
		);
	}

	private function copy_controls() {
		$this->controls['eyebrow'] = $this->field(
			'copy',
			esc_html__( 'Eyebrow', 'pfh-widgets' ),
			'eyebrow',
			[ 'description' => esc_html__( 'Leave any field empty to hide that part.', 'pfh-widgets' ) ]
		);

		$this->controls['fromCategory'] = $this->switch_field(
			'copy',
			esc_html__( 'Follow each category\'s own settings', 'pfh-widgets' ),
			'fromCategory',
			[ 'description' => esc_html__( 'On a category page, the category can hide this banner, reword it and choose the product it sells (Products → Categories → edit → Collection page). The product then sets the price, the saving and the link. Anything the category leaves empty keeps what is typed here.', 'pfh-widgets' ) ]
		);

		$this->controls['showGift'] = $this->switch_field( 'copy', esc_html__( 'Gift icon before the eyebrow', 'pfh-widgets' ), 'showGift' );

		$this->controls['titleTop'] = $this->field( 'copy', esc_html__( 'Title, first line', 'pfh-widgets' ), 'titleTop' );

		$this->controls['titleBottom'] = $this->field(
			'copy',
			esc_html__( 'Title, second line', 'pfh-widgets' ),
			'titleBottom',
			[ 'description' => esc_html__( 'Set in a heavier weight than the first, as drawn.', 'pfh-widgets' ) ]
		);

		$this->controls['titleTag'] = [
			'tab'     => 'content',
			'group'   => 'copy',
			'label'   => esc_html__( 'Title tag', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'h2' => 'H2',
				'h3' => 'H3',
				'p'  => esc_html__( 'Not a heading', 'pfh-widgets' ),
			],
			'default' => self::DEFAULTS['titleTag'],
		];

		$this->controls['text'] = $this->field( 'copy', esc_html__( 'Description', 'pfh-widgets' ), 'text', [ 'type' => 'textarea' ] );
	}

	private function point_controls() {
		$this->controls['points'] = [
			'tab'           => 'content',
			'group'         => 'points',
			'label'         => esc_html__( 'Selling points', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'text',
			'default'       => self::DEFAULTS['points'],
			'fields'        => [
				'text' => [
					'label'          => esc_html__( 'Text', 'pfh-widgets' ),
					'type'           => 'text',
					'hasDynamicData' => false,
				],
			],
		];

		$this->controls['pointColor'] = $this->colour_field( 'points', esc_html__( 'Text colour', 'pfh-widgets' ), 'pointColor' );
		$this->controls['tickColor']  = $this->colour_field( 'points', esc_html__( 'Tick colour', 'pfh-widgets' ), 'tickColor' );
	}

	private function price_controls() {
		$this->controls['price'] = $this->field( 'price', esc_html__( 'Price', 'pfh-widgets' ), 'price', [ 'inline' => true ] );

		$this->controls['priceWas'] = $this->field(
			'price',
			esc_html__( 'Price before the discount', 'pfh-widgets' ),
			'priceWas',
			[
				'inline'      => true,
				'description' => esc_html__( 'Shown struck through. Empty for no discount.', 'pfh-widgets' ),
			]
		);

		$this->controls['saving'] = $this->field( 'price', esc_html__( 'Saving line', 'pfh-widgets' ), 'saving' );
	}

	private function media_controls() {
		$this->controls['image'] = [
			'tab'            => 'content',
			'group'          => 'media',
			'label'          => esc_html__( 'Image', 'pfh-widgets' ),
			'type'           => 'image',
			'hasDynamicData' => false,
			'description'    => esc_html__( 'The supplied banner photograph is used until one is chosen here.', 'pfh-widgets' ),
		];

		$this->controls['imageAlt'] = $this->field(
			'media',
			esc_html__( 'Image description', 'pfh-widgets' ),
			'imageAlt',
			[ 'description' => esc_html__( 'Empty uses the description saved with the image in the media library.', 'pfh-widgets' ) ]
		);

		$this->controls['imageWidth'] = $this->number_field( 'media', esc_html__( 'Image share of the banner (%)', 'pfh-widgets' ), 'imageWidth', 20, 70 );

		$this->controls['imageBlend'] = $this->switch_field(
			'media',
			esc_html__( 'Fade the image into the card', 'pfh-widgets' ),
			'imageBlend',
			[ 'description' => esc_html__( 'The supplied photograph has its own background baked in, which leaves a seam against the card. Turn this off for a cut-out on a transparent background.', 'pfh-widgets' ) ]
		);

		$this->controls['imageFade'] = $this->number_field(
			'media',
			esc_html__( 'Fade distance (%)', 'pfh-widgets' ),
			'imageFade',
			2,
			45,
			[ 'required' => [ 'imageBlend', '=', true ] ]
		);

		$this->controls['imageFit'] = [
			'tab'         => 'content',
			'group'       => 'media',
			'label'       => esc_html__( 'Picture fit', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'auto'    => esc_html__( 'Automatic', 'pfh-widgets' ),
				'cover'   => esc_html__( 'Fill the space', 'pfh-widgets' ),
				'contain' => esc_html__( 'Show the whole picture', 'pfh-widgets' ),
			],
			'default'     => self::DEFAULTS['imageFit'],
			'description' => esc_html__( 'Automatic fills the space with a wide photograph and shows a tall or square product shot whole, so bottles and jars are never cut off. Either way the banner\'s height follows its text, not the picture.', 'pfh-widgets' ),
		];

		$this->controls['imageMultiply'] = $this->switch_field(
			'media',
			esc_html__( 'Blend a white background into the card', 'pfh-widgets' ),
			'imageMultiply',
			[ 'description' => esc_html__( 'For a product shot on white: the white takes the card\'s colour, so the product stands on the banner instead of in a white box. Applies when the whole picture is shown.', 'pfh-widgets' ) ]
		);

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
			'default' => self::DEFAULTS['imageSide'],
		];
	}

	private function button_controls() {
		$this->controls['btnLabel'] = $this->field(
			'button',
			esc_html__( 'Button label', 'pfh-widgets' ),
			'btnLabel',
			[
				'inline'      => true,
				'description' => esc_html__( 'Empty for no button.', 'pfh-widgets' ),
			]
		);

		$this->controls['url'] = $this->field(
			'button',
			esc_html__( 'Link', 'pfh-widgets' ),
			'url',
			[
				'placeholder' => 'https://',
				'description' => esc_html__( 'Where the banner and its button go. Empty for no link.', 'pfh-widgets' ),
			]
		);

		$this->controls['newTab'] = $this->switch_field( 'button', esc_html__( 'Open in a new tab', 'pfh-widgets' ), 'newTab' );

		$this->controls['clickable'] = $this->switch_field(
			'button',
			esc_html__( 'The whole banner is clickable', 'pfh-widgets' ),
			'clickable',
			[ 'description' => esc_html__( 'Off leaves only the button as the link.', 'pfh-widgets' ) ]
		);

		$this->controls['btnRadius'] = $this->number_field(
			'button',
			esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'btnRadius',
			0,
			40,
			[ 'description' => esc_html__( 'Empty leaves it at the 5px it is drawn with. Half the button\'s height or more gives a pill.', 'pfh-widgets' ) ]
		);

		$this->controls['btnBg']    = $this->colour_field( 'button', esc_html__( 'Background', 'pfh-widgets' ), 'btnBg' );
		$this->controls['btnColor'] = $this->colour_field( 'button', esc_html__( 'Text colour', 'pfh-widgets' ), 'btnColor' );
	}

	private function style_controls() {
		$this->controls['bg']     = $this->colour_field( 'style', esc_html__( 'Background', 'pfh-widgets' ), 'bg' );
		$this->controls['radius'] = $this->number_field( 'style', esc_html__( 'Corner radius (px)', 'pfh-widgets' ), 'radius', 0, 60 );

		$this->controls['imageRadius'] = $this->number_field(
			'style',
			esc_html__( 'Image corner radius (px)', 'pfh-widgets' ),
			'imageRadius',
			0,
			60,
			[ 'description' => esc_html__( 'The card already clips the image to its own corners; this rounds the image itself as well.', 'pfh-widgets' ) ]
		);

		$this->controls['padX']        = $this->number_field( 'style', esc_html__( 'Inner padding, sides (px)', 'pfh-widgets' ), 'padX', 0, 120 );
		$this->controls['padY']        = $this->number_field( 'style', esc_html__( 'Inner padding, top and bottom (px)', 'pfh-widgets' ), 'padY', 0, 120 );
		$this->controls['eyebrowSize'] = $this->number_field( 'style', esc_html__( 'Eyebrow size (px)', 'pfh-widgets' ), 'eyebrowSize', 12, 40 );
		$this->controls['titleSize']   = $this->number_field( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 'titleSize', 18, 64 );
		$this->controls['textSize']    = $this->number_field( 'style', esc_html__( 'Description size (px)', 'pfh-widgets' ), 'textSize', 10, 24 );
		$this->controls['priceSize']   = $this->number_field( 'style', esc_html__( 'Price size (px)', 'pfh-widgets' ), 'priceSize', 14, 48 );
		$this->controls['ink']         = $this->colour_field( 'style', esc_html__( 'Text colour', 'pfh-widgets' ), 'ink' );
		$this->controls['shadow']      = $this->switch_field( 'style', esc_html__( 'Shadow under the card', 'pfh-widgets' ), 'shadow' );
	}

	private function layout_controls() {
		$this->controls['maxWidth']  = $this->number_field( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 'maxWidth', 400, 1600 );
		$this->controls['minHeight'] = $this->number_field( 'layout', esc_html__( 'Minimum height (px)', 'pfh-widgets' ), 'minHeight', 200, 800 );
		$this->controls['padTop']    = $this->number_field( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 'padTop', 0, 200 );

		$this->controls['overlap'] = $this->number_field(
			'layout',
			esc_html__( 'Overlap the section below (px)', 'pfh-widgets' ),
			'overlap',
			0,
			300,
			[ 'description' => esc_html__( 'The banner sits over the top of whatever follows it — the footer, in the design. Set to 0 for no overlap.', 'pfh-widgets' ) ]
		);

		$this->controls['padBottom'] = $this->number_field( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 'padBottom', 0, 200 );
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	/**
	 * Always a banner. Clearing a field hides that part of it; nothing in the
	 * panel clears the banner itself, so a block that was added is a block
	 * that shows. The one exception is a category that has asked for it to
	 * be hidden on its own page.
	 */
	public function render() {
		if ( class_exists( 'PFH_Widgets_License' ) && PFH_Widgets_License::locked() ) {
			echo PFH_Widgets_License::locked_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped in locked_markup().

			return;
		}

		$this->own   = null;
		$this->offer = null;

		if ( $this->flag( 'fromCategory' ) && class_exists( 'PFH_Widgets_Collection' ) ) {
			$own = PFH_Widgets_Collection::section( 'bundle' );

			if ( $own && ! empty( $own['hide'] ) ) {
				return;
			}

			$this->own   = $own;
			$this->offer = $own ? PFH_Widgets_Collection::offer( (int) $own['product'], (string) $own['saving'] ) : null;
		}

		$this->picture = $this->image();

		$fit = $this->fit( $this->picture );

		$classes = [
			'pfh-hl',
			'pfh-scope',
			'pfh-hl--media-' . $this->choice( 'imageSide', [ 'right', 'left' ] ),
			'pfh-hl--fit-' . $fit,
		];

		if ( 'contain' === $fit && $this->flag( 'imageMultiply' ) ) {
			$classes[] = 'pfh-hl--multiply';
		}

		if ( $this->flag( 'shadow' ) ) {
			$classes[] = 'pfh-hl--shadow';
		}

		if ( $this->number( 'overlap' ) > 0 ) {
			$classes[] = 'pfh-hl--overlaps';
		}

		if ( $this->flag( 'imageBlend' ) ) {
			$classes[] = 'pfh-hl--blend';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		$link  = $this->link();
		$whole = $this->flag( 'clickable' ) && '' !== $link['href'];

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-hl__inner">';
		printf( '<div class="pfh-hl__card%s">', $whole ? ' is-clickable' : '' );
		echo '<div class="pfh-hl__body">';

		$this->render_eyebrow();
		$this->render_title( $link, $whole );

		$text = $this->text( 'text' );

		if ( '' !== $text ) {
			echo '<p class="pfh-hl__text">' . esc_html( $text ) . '</p>';
		}

		$this->render_points();
		$this->render_price();
		$this->render_button( $link, $whole );

		echo '</div>';

		$this->render_media();

		echo '</div>';
		echo '</div>';
		echo '</section>';
	}

	private function render_eyebrow() {
		$eyebrow = $this->text( 'eyebrow' );

		if ( '' === $eyebrow ) {
			return;
		}

		printf(
			'<p class="pfh-hl__eyebrow">%s<span>%s</span></p>',
			$this->flag( 'showGift' ) ? '<span class="pfh-hl__eyebrow-icon" aria-hidden="true">' . self::GIFT . '</span>' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal markup.
			esc_html( $eyebrow )
		);
	}

	/**
	 * @param array $link  Destination.
	 * @param bool  $whole Whether the card itself is the link.
	 */
	private function render_title( array $link, $whole ) {
		$top    = $this->text( 'titleTop' );
		$bottom = $this->text( 'titleBottom' );

		if ( '' === $top && '' === $bottom ) {
			return;
		}

		$tag   = $this->choice( 'titleTag', [ 'h2', 'h3', 'p' ] );
		$inner = '';

		/*
		 * On a clickable banner the title has to look like what it is: a
		 * link. The line that carries the offer — the bold one, or the only
		 * one — gets an underline and an arrow, and the hover draws the line
		 * in. Inline spans, so the underline follows the words across a wrap
		 * instead of running the full width of the column.
		 */
		$mark = static function ( $text ) {
			return '<span class="pfh-hl__title-line">' . esc_html( $text ) . '</span>'
				. '<span class="pfh-hl__title-arrow" aria-hidden="true">' . PFH_Widgets_Icons::get( 'arrow-ne' ) . '</span>';
		};

		$marked = $whole ? ( '' !== $bottom ? 'bottom' : 'top' ) : '';

		if ( '' !== $top ) {
			$inner .= '<span class="pfh-hl__title-top">' . ( 'top' === $marked ? $mark( $top ) : esc_html( $top ) ) . '</span>';
		}

		if ( '' !== $bottom ) {
			$inner .= '<span class="pfh-hl__title-bottom">' . ( 'bottom' === $marked ? $mark( $bottom ) : esc_html( $bottom ) ) . '</span>';
		}

		/*
		 * When the whole card is clickable the link lives on the title and is
		 * stretched over the card with a pseudo-element. That keeps one real
		 * link in the document — a card wrapped in an <a> folds every word
		 * into the link's name, which is no use to a keyboard or screen reader.
		 */
		if ( $whole ) {
			$inner = '<a class="pfh-hl__link"' . PFH_Widgets_Helpers::link_attrs( $link ) . '>' . $inner . '</a>';
		}

		printf( '<%1$s class="pfh-hl__title">%2$s</%1$s>', $tag, $inner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag whitelisted, inner escaped above.
	}

	private function render_points() {
		$rows = [];

		foreach ( $this->rows( 'points' ) as $row ) {
			$text = isset( $row['text'] ) && is_scalar( $row['text'] ) ? trim( (string) $row['text'] ) : '';

			if ( '' !== $text ) {
				$rows[] = $text;
			}
		}

		if ( ! $rows ) {
			return;
		}

		echo '<ul class="pfh-hl__points">';

		foreach ( $rows as $text ) {
			printf(
				'<li class="pfh-hl__point">%s<span>%s</span></li>',
				PFH_Widgets_Icons::get( 'check', 'pfh-hl__tick' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				esc_html( $text )
			);
		}

		echo '</ul>';
	}

	private function render_price() {
		$now    = $this->text( 'price' );
		$was    = $this->text( 'priceWas' );
		$saving = $this->text( 'saving' );

		if ( '' === $now && '' === $was && '' === $saving ) {
			return;
		}

		echo '<div class="pfh-hl__price">';

		if ( '' !== $now || '' !== $was ) {
			echo '<p class="pfh-hl__price-row">';

			if ( '' !== $now ) {
				echo '<span class="pfh-hl__price-now">' . esc_html( $now ) . '</span>';
			}

			if ( '' !== $was ) {
				echo '<span class="pfh-hl__price-was">' . esc_html( $was ) . '</span>';
			}

			echo '</p>';
		}

		if ( '' !== $saving ) {
			echo '<p class="pfh-hl__save">' . esc_html( $saving ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * @param array $link  Destination.
	 * @param bool  $whole Whether the card itself is already the link.
	 */
	private function render_button( array $link, $whole ) {
		$label = $this->text( 'btnLabel' );

		if ( '' === $label ) {
			return;
		}

		// A second link inside a stretched one would be unreachable.
		if ( '' !== $link['href'] && ! $whole ) {
			echo '<a class="pfh-hl__btn"' . PFH_Widgets_Helpers::link_attrs( $link ) . '>' . esc_html( $label ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().

			return;
		}

		echo '<span class="pfh-hl__btn">' . esc_html( $label ) . '</span>';
	}

	private function render_media() {
		$image = $this->picture ? $this->picture : $this->image();

		printf(
			'<div class="pfh-hl__media"><img class="pfh-hl__img" src="%s" alt="%s" loading="lazy" decoding="async" /></div>',
			esc_url( $image['url'] ),
			esc_attr( $image['alt'] )
		);
	}

	/* ---------------------------------------------------------------------
	 * Reading settings
	 *
	 * Absent means never touched, and takes the default. Present but empty
	 * means cleared on purpose, and a text field stays cleared — an eyebrow the
	 * editor deleted must not come back. Numbers are the exception: an empty
	 * number box means "the design value", not "zero".
	 * ------------------------------------------------------------------ */

	/**
	 * @param string $key Setting.
	 * @return string
	 */
	private function text( $key ) {
		/*
		 * With a product chosen, the numbers are the product's — all three,
		 * even when empty. A product not on sale has no old price and no
		 * saving, and the typed ones must not show beside its real price.
		 */
		$from_offer = [ 'price' => 'now', 'priceWas' => 'was', 'saving' => 'saving' ];

		if ( $this->offer && isset( $from_offer[ $key ] ) ) {
			return (string) $this->offer[ $from_offer[ $key ] ];
		}

		// Words the category filled in replace the typed ones.
		$from_category = [ 'eyebrow' => 'eyebrow', 'titleTop' => 'title_top', 'titleBottom' => 'title_bottom', 'text' => 'text' ];

		if ( $this->own && isset( $from_category[ $key ] ) && '' !== trim( (string) $this->own[ $from_category[ $key ] ] ) ) {
			return trim( (string) $this->own[ $from_category[ $key ] ] );
		}

		if ( array_key_exists( $key, (array) $this->settings ) ) {
			$value = $this->settings[ $key ];

			return is_scalar( $value ) ? trim( (string) $value ) : '';
		}

		return (string) self::DEFAULTS[ $key ];
	}

	/**
	 * @param string $key Setting.
	 * @return int|float|string Number, or '' when the default is "none".
	 */
	private function number( $key ) {
		$value = isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : '';

		if ( is_numeric( $value ) ) {
			return $value + 0;
		}

		return self::DEFAULTS[ $key ];
	}

	/**
	 * @param string $key Setting.
	 * @return bool
	 */
	private function flag( $key ) {
		if ( array_key_exists( $key, (array) $this->settings ) ) {
			return ! empty( $this->settings[ $key ] );
		}

		return (bool) self::DEFAULTS[ $key ];
	}

	/**
	 * @param string   $key     Setting.
	 * @param string[] $allowed Accepted values; anything else falls back.
	 * @return string
	 */
	private function choice( $key, array $allowed ) {
		$value = isset( $this->settings[ $key ] ) && is_string( $this->settings[ $key ] ) ? $this->settings[ $key ] : '';

		return in_array( $value, $allowed, true ) ? $value : self::DEFAULTS[ $key ];
	}

	/**
	 * @param string $key Setting.
	 * @return string CSS colour, or '' to leave it to the stylesheet.
	 */
	private function colour( $key ) {
		$value = isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : null;

		return PFH_Widgets_Helpers::color( $value, self::DEFAULTS[ $key ] );
	}

	/**
	 * @param string $key Repeater setting.
	 * @return array
	 */
	private function rows( $key ) {
		if ( 'points' === $key && $this->own && ! empty( $this->own['points'] ) ) {
			return array_map(
				static function ( $point ) {
					return [ 'text' => $point ];
				},
				(array) $this->own['points']
			);
		}

		if ( array_key_exists( $key, (array) $this->settings ) ) {
			return is_array( $this->settings[ $key ] ) ? $this->settings[ $key ] : [];
		}

		return self::DEFAULTS[ $key ];
	}

	/**
	 * The typed link, with its target.
	 *
	 * @return array{href: string, target: string, rel: string}
	 */
	private function link() {
		// The category's own link, else its product, else the typed one.
		if ( $this->own && '' !== (string) $this->own['url'] ) {
			return [ 'href' => esc_url_raw( (string) $this->own['url'] ), 'target' => '', 'rel' => '' ];
		}

		if ( $this->offer && '' !== $this->offer['url'] ) {
			return [ 'href' => $this->offer['url'], 'target' => '', 'rel' => '' ];
		}

		$href = esc_url_raw( $this->text( 'url' ) );
		$new  = '' !== $href && $this->flag( 'newTab' );

		return [
			'href'   => $href,
			'target' => $new ? '_blank' : '',
			'rel'    => $new ? 'noopener' : '',
		];
	}

	/**
	 * The chosen photograph, or the supplied one.
	 *
	 * A media-library pick resolves by its ID, so a regenerated or moved file
	 * still shows. Dynamic-data image values are ignored: this block is static.
	 *
	 * @return array{url: string, alt: string}
	 */
	private function image() {
		// The category's own picture, else its product's cut-out.
		if ( $this->own && ! empty( $this->own['image'] ) ) {
			$url = (string) wp_get_attachment_image_url( (int) $this->own['image'], 'large' );

			if ( '' !== $url ) {
				$alt = trim( (string) get_post_meta( (int) $this->own['image'], '_wp_attachment_image_alt', true ) );

				return [
					'url' => $url,
					'alt' => '' !== $alt ? $alt : ( $this->offer ? $this->offer['name'] : '' ),
					'id'  => (int) $this->own['image'],
				];
			}
		}

		if ( $this->offer && '' !== $this->offer['image'] ) {
			return [
				'url' => $this->offer['image'],
				'alt' => $this->offer['name'],
				'id'  => isset( $this->offer['image_id'] ) ? (int) $this->offer['image_id'] : 0,
			];
		}

		$image = isset( $this->settings['image'] ) && is_array( $this->settings['image'] ) ? $this->settings['image'] : [];
		$id    = ! empty( $image['id'] ) ? absint( $image['id'] ) : 0;
		$url   = '';

		if ( $id ) {
			$size = ! empty( $image['size'] ) && is_string( $image['size'] ) ? $image['size'] : 'large';
			$url  = (string) wp_get_attachment_image_url( $id, $size );
		}

		if ( '' === $url && ! empty( $image['url'] ) && is_string( $image['url'] ) && empty( $image['useDynamicData'] ) ) {
			$url = $image['url'];
		}

		if ( '' === $url ) {
			$id  = 0;
			$url = PFH_Widgets_Assets::img( 'pfh-highlight.jpg' );
		}

		$alt = $this->text( 'imageAlt' );

		if ( '' === $alt && $id ) {
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}

		return [
			'url' => $url,
			'alt' => $alt,
			'id'  => (int) $id,
		];
	}

	/**
	 * How the picture sits in its half of the banner.
	 *
	 * The supplied photograph is a wide scene made to fill that half, and a
	 * scene can lose a strip off its edges without anyone noticing. A product
	 * shot is tall or square — a bottle, a jar — and cropping it cuts the cap
	 * off the bottle. So a wide picture fills and anything else is shown
	 * whole, unless the panel says otherwise. A picture whose size is not
	 * known is treated as the supplied photograph was: it fills.
	 *
	 * @param array $picture From image().
	 * @return string 'cover' or 'contain'.
	 */
	private function fit( array $picture ) {
		$chosen = $this->choice( 'imageFit', [ 'auto', 'cover', 'contain' ] );

		if ( 'auto' !== $chosen ) {
			return $chosen;
		}

		$meta = ! empty( $picture['id'] ) ? wp_get_attachment_metadata( (int) $picture['id'] ) : [];

		if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
			return 'cover';
		}

		return ( (int) $meta['width'] / (int) $meta['height'] ) >= 1.15 ? 'cover' : 'contain';
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-hl-max'         => PFH_Widgets_Helpers::unit( $this->number( 'maxWidth' ) ),
				'--pfh-hl-min-h'       => PFH_Widgets_Helpers::unit( $this->number( 'minHeight' ) ),
				'--pfh-hl-pt'          => PFH_Widgets_Helpers::unit( $this->number( 'padTop' ) ),
				'--pfh-hl-pb'          => PFH_Widgets_Helpers::unit( $this->number( 'padBottom' ) ),
				'--pfh-hl-overlap'     => PFH_Widgets_Helpers::unit( max( 0, (int) $this->number( 'overlap' ) ) ),
				'--pfh-hl-bg'          => $this->colour( 'bg' ),
				'--pfh-hl-radius'      => PFH_Widgets_Helpers::unit( $this->number( 'radius' ) ),
				'--pfh-hl-img-radius'  => PFH_Widgets_Helpers::unit( $this->number( 'imageRadius' ) ),
				'--pfh-hl-btn-radius'  => PFH_Widgets_Helpers::unit( $this->number( 'btnRadius' ) ),
				'--pfh-hl-btn-bg'      => $this->colour( 'btnBg' ),
				'--pfh-hl-btn-ink'     => $this->colour( 'btnColor' ),
				'--pfh-hl-px-set'      => PFH_Widgets_Helpers::unit( $this->number( 'padX' ) ),
				'--pfh-hl-py-set'      => PFH_Widgets_Helpers::unit( $this->number( 'padY' ) ),
				'--pfh-hl-media-w'     => max( 20, min( 70, (int) $this->number( 'imageWidth' ) ) ) . '%',
				'--pfh-hl-fade'        => max( 2, min( 45, (int) $this->number( 'imageFade' ) ) ) . '%',
				'--pfh-hl-ink'         => $this->colour( 'ink' ),
				'--pfh-hl-eyebrow-set' => PFH_Widgets_Helpers::unit( $this->number( 'eyebrowSize' ) ),
				'--pfh-hl-title-set'   => PFH_Widgets_Helpers::unit( $this->number( 'titleSize' ) ),
				'--pfh-hl-text'        => PFH_Widgets_Helpers::unit( $this->number( 'textSize' ) ),
				'--pfh-hl-price'       => PFH_Widgets_Helpers::unit( $this->number( 'priceSize' ) ),
				'--pfh-hl-point'       => $this->colour( 'pointColor' ),
				'--pfh-hl-tick'        => $this->colour( 'tickColor' ),
			]
		);
	}
}
