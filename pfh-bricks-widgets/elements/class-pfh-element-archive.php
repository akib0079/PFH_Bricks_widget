<?php
/**
 * Bricks element: Products For Home shop archive.
 *
 * The whole shop / collection block — category pills, product count, filter
 * button, the grid itself and pagination — with every control filtering over
 * AJAX and degrading to ordinary page loads when scripting is unavailable.
 *
 * Cards come from the shared product-card trait, so the archive, the slider
 * and the grid render one card, not three that drift apart.
 *
 * The filter state lives in the URL. That is deliberate: a filtered view can
 * be linked, bookmarked, shared and indexed, and the no-JS path is the same
 * code as the AJAX path rather than a second, subtly different renderer.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Archive extends \Bricks\Element {

	use PFH_Product_Card_Trait;

	public $category     = 'products-for-home';
	public $name         = 'pfh-archive';
	public $icon         = 'ti-layout-grid2';
	public $css_selector = '.pfh-arch';

	/**
	 * Transient prefix holding a rendered element's configuration.
	 *
	 * The AJAX request says which archive it is; the configuration comes from
	 * here rather than from the browser, so a hand-edited request cannot make
	 * the archive query something the template never offered.
	 */
	const CONFIG = 'pfh_arch_cfg_';

	/**
	 * Per-instance id, never static — two archives on one page must not share.
	 *
	 * @var string|null
	 */
	private $uid = null;

	/**
	 * The shared card controls' defaults, as they are before card_defaults()
	 * retunes them for this element. Used to tell a value the editor chose
	 * from one they merely inherited.
	 *
	 * @var array<string, mixed>
	 */
	private $shared_defaults = [];

	/**
	 * The card settings the design owns. Anything not listed — how many
	 * columns, how many per page, the container width — is the editor's
	 * choice and is never touched.
	 *
	 * @var string[]
	 */
	const CARD_KEYS = [
		'cartBg', 'cartColor', 'cartHoverBg', 'cartRadius', 'cartHeight',
		'cartLabel', 'cartAddedLabel', 'cartIcon', 'cartIconSize', 'cartSize',
		'reviewMode', 'reviewShowStars', 'reviewSize', 'reviewColor',
		'starColor', 'starSize', 'starEmptyColor',
		'titleSize', 'titleWeight', 'titleLineHeight', 'titleColor', 'titleLines',
		'priceSize', 'priceWeight', 'priceColor', 'oldPriceColor', 'priceGap',
		'priceSuffix', 'priceSuffixOld', 'oldPriceSize',
		'badgeSize', 'badgeOffset',
		'cardRadius', 'cardRatio', 'cardImageBg', 'cardImagePad', 'cardGap',
	];

	/**
	 * The page this archive rendered on, without a query string.
	 *
	 * Captured at render and carried through the transient. An AJAX request
	 * runs on admin-ajax.php, so asking the current request for its own URL
	 * there produced links to admin-ajax.php — which is exactly what went
	 * into the address bar.
	 *
	 * @var string
	 */
	private $base = '';

	public function get_label() {
		return esc_html__( 'PFH Shop Archive', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'shop', 'archive', 'collection', 'filter', 'facet', 'products', 'grid', 'pagination', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::archive();
	}

	public function set_control_groups() {
		$this->control_groups['cats']    = [ 'title' => esc_html__( 'Category row', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['toolbar'] = [ 'title' => esc_html__( 'Toolbar', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['filters'] = [ 'title' => esc_html__( 'Filters', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['panel']   = [ 'title' => esc_html__( 'Filter panel', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['grid']    = [ 'title' => esc_html__( 'Grid', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['pager']   = [ 'title' => esc_html__( 'Pagination', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['badge']   = [ 'title' => esc_html__( 'Sale badge', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['cart']    = [ 'title' => esc_html__( 'Add to cart', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['reviews'] = [ 'title' => esc_html__( 'Reviews', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['card']    = [ 'title' => esc_html__( 'Card', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['type']    = [ 'title' => esc_html__( 'Card typography', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['layout']  = [ 'title' => esc_html__( 'Layout', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$this->category_controls();
		$this->toolbar_controls();
		$this->filter_controls();
		$this->panel_controls();
		$this->grid_controls();
		$this->pager_controls();

		// Card appearance, shared with the slider and the grid.
		$this->badge_controls();
		$this->cart_controls();
		$this->review_controls();
		$this->type_controls();

		// Remember what the shared controls said before this element retunes
		// them; migrate_card() needs both to tell inherited from chosen.
		foreach ( self::CARD_KEYS as $key ) {
			if ( isset( $this->controls[ $key ] ) && array_key_exists( 'default', $this->controls[ $key ] ) ) {
				$this->shared_defaults[ $key ] = $this->controls[ $key ]['default'];
			}
		}

		$this->card_defaults();

		// Not offered here: the shop card's button is fixed by the design.
		unset( $this->controls['cartPosition'], $this->controls['cartReveal'] );

		$this->card_controls();
		$this->layout_controls();
	}

	/**
	 * The shared card controls are tuned for the home-page slider, whose
	 * cards are half again as large. The archive grid puts four across
	 * 1240px, so the type comes down and the review row switches to each
	 * product's own rating — that is what the design asks for here. These
	 * are defaults, so every one of them is still a control in the panel.
	 */
	/**
	 * Let a card saved under an older release take the current design.
	 *
	 * Non-destructive, and deliberately so. The first attempt stamped the
	 * settings with a revision and dropped every card key when the stamp was
	 * behind — but a builder does not necessarily store a value that equals
	 * its control default, so the stamp never persisted, the migration ran on
	 * every render, and it deleted the icon and the title size the editor had
	 * just set. That is what "uploading the icon doesn't work" was.
	 *
	 * So nothing is stamped. A setting is only dropped when it still holds
	 * the *shared* control's default — the slider's value, which is what an
	 * archive built before this card was drawn would be carrying. An uploaded
	 * icon, a 17px title, a colour someone picked: none of those equal it, so
	 * none of them are touched.
	 */
	/**
	 * Everything that must be true before a card is drawn.
	 *
	 * Both entry points run this. render() is the obvious one; ajax_render()
	 * is the one that bit — it builds the element straight from the stored
	 * settings and calls render_results(), so on a filtered or paged request
	 * the migration never ran, the controls were never registered, and the
	 * card's button was not pinned. The visible symptom was a second page of
	 * products whose buttons had lost their TOEVOEGEN label, because the
	 * label is only drawn for the full-width button and the stored settings
	 * still said otherwise.
	 */
	private function prepare() {
		// ajax_render() constructs the element directly, so the controls this
		// relies on may not exist yet. Registering twice is harmless.
		if ( ! $this->controls ) {
			$this->set_control_groups();
			$this->set_controls();
		}

		$this->migrate_card();

		// The shop card's button is fixed by the design, not a preference.
		$this->settings['cartPosition'] = 'block';
		$this->settings['cartReveal']   = 'none';
	}

	private function migrate_card() {
		foreach ( self::CARD_KEYS as $key ) {
			if ( ! array_key_exists( $key, $this->shared_defaults ) ) {
				continue;
			}

			if ( ! array_key_exists( $key, $this->settings ) ) {
				continue;
			}

			// Only a setting still holding the shared control's default is
			// one the editor never chose. Anything else is theirs.
			if ( $this->settings[ $key ] === $this->shared_defaults[ $key ] ) {
				unset( $this->settings[ $key ] );
			}
		}
	}

	private function card_defaults() {
		$tune = [
			'titleSize'      => 17,
			'titleLineHeight' => 1.3,
			'titleColor'     => [ 'hex' => '#51604f' ],
			'priceSize'      => 12,
			'priceColor'     => [ 'hex' => '#51604f' ],
			'oldPriceColor'  => [ 'hex' => '#9aa396' ],
			'priceGap'       => 8,
			// The struck price is a size smaller than the current one.
			'oldPriceSize'   => 10,
			'reviewSize'     => 11,
			'reviewColor'    => [ 'hex' => '#6f7d6b' ],
			'starSize'       => 12,
			'reviewMode'     => 'woocommerce',
			'badgeSize'      => 11,
			'badgeOffset'    => 10,

			/*
			 * The card itself. The archive's is not the slider's card: the
			 * slider reveals a round button in the corner of the image on
			 * hover, this one carries a full-width TOEVOEGEN bar under the
			 * price that is always there. Both are drawn that way, so the
			 * difference belongs here rather than in the shared controls.
			 */
			'cartBg'         => [ 'hex' => '#7caeb2' ],
			'cartRadius'     => 5,
			'cartHeight'     => 36,
			'cartLabel'      => 'TOEVOEGEN',
			'reviewShowStars' => true,
			// The grid puts the old price beside the new one, so the suffix
			// rides on the current price only — the slider repeats it on
			// both, which is the width the slider has room for.
			'priceSuffix'    => 'incl. BTW',
			'priceSuffixOld' => false,
		];

		foreach ( $tune as $key => $value ) {
			if ( isset( $this->controls[ $key ] ) ) {
				$this->controls[ $key ]['default'] = $value;
			}
		}
	}

	/* ---------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	private function category_controls() {
		$this->controls['catsEnable'] = [
			'tab'     => 'content',
			'group'   => 'cats',
			'label'   => esc_html__( 'Show the category row', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['catsSource'] = [
			'tab'         => 'content',
			'group'       => 'cats',
			'label'       => esc_html__( 'Which categories', 'pfh-widgets' ),
			'type'        => 'select',
			'options'     => [
				'children' => esc_html__( 'Children of the category being viewed', 'pfh-widgets' ),
				'siblings' => esc_html__( 'Categories alongside this one', 'pfh-widgets' ),
				'top'      => esc_html__( 'All top level categories', 'pfh-widgets' ),
				'manual'   => esc_html__( 'The list below', 'pfh-widgets' ),
			],
			'default'     => 'children',
			'description' => esc_html__( 'Children and siblings fall back to the level above, and then to the top level, so the row never comes out empty.', 'pfh-widgets' ),
			'required'    => [ 'catsEnable', '=', true ],
		];

		$this->controls['catsManual'] = [
			'tab'         => 'content',
			'group'       => 'cats',
			'label'       => esc_html__( 'Categories', 'pfh-widgets' ),
			'type'        => 'select',
			'multiple'    => true,
			'searchable'  => true,
			'options'     => PFH_Widgets_Helpers::product_cat_options(),
			'required'    => [ [ 'catsEnable', '=', true ], [ 'catsSource', '=', 'manual' ] ],
		];

		$this->controls['catsAllLabel'] = [
			'tab'      => 'content',
			'group'    => 'cats',
			'label'    => esc_html__( '“Everything” label', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Alles',
			'required' => [ 'catsEnable', '=', true ],
		];

		$this->controls['catsAll'] = [
			'tab'      => 'content',
			'group'    => 'cats',
			'label'    => esc_html__( 'Show the “Everything” pill', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'catsEnable', '=', true ],
		];

		$this->controls['catsAllLink'] = [
			'tab'         => 'content',
			'group'       => 'cats',
			'label'       => esc_html__( '“Everything” goes to', 'pfh-widgets' ),
			'type'        => 'link',
			'description' => esc_html__( 'Leave empty for the shop page. Set it to a parent category if this template sits inside one.', 'pfh-widgets' ),
			'required'    => [ [ 'catsEnable', '=', true ], [ 'catsAll', '=', true ] ],
		];

		$this->controls['catsGap'] = [
			'tab'      => 'content',
			'group'    => 'cats',
			'label'    => esc_html__( 'Gap between pills (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 80,
			'inline'   => true,
			'default'  => 8,
			'required' => [ 'catsEnable', '=', true ],
		];

		$this->controls['catsRadius'] = [
			'tab'      => 'content',
			'group'    => 'cats',
			'label'    => esc_html__( 'Pill radius (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 40,
			'inline'   => true,
			'default'  => 10,
			'required' => [ 'catsEnable', '=', true ],
		];

		$this->controls['catsActiveBg'] = [
			'tab'      => 'content',
			'group'    => 'cats',
			'label'    => esc_html__( 'Active pill background', 'pfh-widgets' ),
			'type'     => 'color',
			'required' => [ 'catsEnable', '=', true ],
		];

		$this->controls['catsColor'] = [
			'tab'      => 'content',
			'group'    => 'cats',
			'label'    => esc_html__( 'Pill text', 'pfh-widgets' ),
			'type'     => 'color',
			'required' => [ 'catsEnable', '=', true ],
		];
	}

	private function toolbar_controls() {
		$this->controls['toolbarInfo'] = [
			'tab'     => 'content',
			'group'   => 'toolbar',
			'type'    => 'info',
			'content' => esc_html__( 'Drag the rows to reorder the toolbar. Anything you leave out simply is not rendered.', 'pfh-widgets' ),
		];

		$this->controls['toolbar'] = [
			'tab'           => 'content',
			'group'         => 'toolbar',
			'label'         => esc_html__( 'Toolbar parts', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'part',
			'default'       => [
				[ 'part' => 'cats' ],
				[ 'part' => 'spacer' ],
				[ 'part' => 'count' ],
				[ 'part' => 'filter' ],
			],
			'fields'        => [
				'part' => [
					'label'   => esc_html__( 'Part', 'pfh-widgets' ),
					'type'    => 'select',
					'options' => [
						'cats'   => esc_html__( 'Category pills', 'pfh-widgets' ),
						'count'  => esc_html__( 'Product count', 'pfh-widgets' ),
						'filter' => esc_html__( 'Filter button', 'pfh-widgets' ),
						'sort'   => esc_html__( 'Sort dropdown', 'pfh-widgets' ),
						'spacer' => esc_html__( 'Flexible space', 'pfh-widgets' ),
					],
					'default' => 'cats',
				],
			],
		];

		$this->controls['countText'] = [
			'tab'         => 'content',
			'group'       => 'toolbar',
			'label'       => esc_html__( 'Count text', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => '%s producten',
			'description' => esc_html__( '%s is replaced with the number of products found.', 'pfh-widgets' ),
		];

		$this->controls['filterLabel'] = [
			'tab'     => 'content',
			'group'   => 'toolbar',
			'label'   => esc_html__( 'Filter button', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Filter',
		];

		$this->controls['toolbarRules'] = [
			'tab'     => 'content',
			'group'   => 'toolbar',
			'label'   => esc_html__( 'Rules above and below', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['toolbarRuleColor'] = [
			'tab'      => 'content',
			'group'    => 'toolbar',
			'label'    => esc_html__( 'Rule colour', 'pfh-widgets' ),
			'type'     => 'color',
			'required' => [ 'toolbarRules', '=', true ],
		];
	}

	private function filter_controls() {
		$this->controls['filtersInfo'] = [
			'tab'     => 'content',
			'group'   => 'filters',
			'type'    => 'info',
			'content' => esc_html__( 'Add as many groups as you like. A group with nothing left to narrow down is hidden automatically, so the panel only ever shows filters that can do something.', 'pfh-widgets' ),
		];

		$this->controls['filters'] = [
			'tab'           => 'content',
			'group'         => 'filters',
			'label'         => esc_html__( 'Filter groups', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'label',
			'default'       => [
				[ 'source' => 'price', 'label' => '', 'open' => true, 'limit' => 0 ],
				[ 'source' => 'onsale', 'label' => '', 'open' => true, 'limit' => 0 ],
			],
			'fields'        => [
				'source' => [
					'label'   => esc_html__( 'Source', 'pfh-widgets' ),
					'type'    => 'select',
					'options' => PFH_Widgets_Archive::filter_sources(),
					'default' => 'price',
				],
				'label'  => [
					'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'Taken from the taxonomy', 'pfh-widgets' ),
				],
				'open'   => [
					'label'   => esc_html__( 'Open by default', 'pfh-widgets' ),
					'type'    => 'checkbox',
					'default' => false,
				],
				'limit'  => [
					'label'       => esc_html__( 'Show first', 'pfh-widgets' ),
					'type'        => 'number',
					'min'         => 0,
					'max'         => 100,
					'default'     => 6,
					'description' => esc_html__( 'The rest go behind a “more” link. 0 shows everything.', 'pfh-widgets' ),
				],
			],
		];
	}

	private function panel_controls() {
		$this->controls['panelTitle'] = [
			'tab'     => 'content',
			'group'   => 'panel',
			'label'   => esc_html__( 'Panel title', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Filter',
		];

		$this->controls['panelClear'] = [
			'tab'     => 'content',
			'group'   => 'panel',
			'label'   => esc_html__( 'Clear all', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Alles Wissen',
		];

		$this->controls['panelMore'] = [
			'tab'     => 'content',
			'group'   => 'panel',
			'label'   => esc_html__( '“More” link', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'Meer…',
		];

		$this->controls['panelApply'] = [
			'tab'         => 'content',
			'group'       => 'panel',
			'label'       => esc_html__( 'Apply button', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '',
			'description' => esc_html__( 'Leave empty to apply each choice as it is made, which is how the design works.', 'pfh-widgets' ),
		];

		$this->controls['panelSide'] = [
			'tab'     => 'content',
			'group'   => 'panel',
			'label'   => esc_html__( 'Slides in from', 'pfh-widgets' ),
			'type'    => 'select',
			'options' => [
				'left'  => esc_html__( 'Left', 'pfh-widgets' ),
				'right' => esc_html__( 'Right', 'pfh-widgets' ),
			],
			'default' => 'left',
		];

		$this->controls['panelWidth'] = [
			'tab'     => 'content',
			'group'   => 'panel',
			'label'   => esc_html__( 'Panel width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 240,
			'max'     => 640,
			'inline'  => true,
			'default' => 332,
		];
	}

	private function grid_controls() {
		$this->controls['perPage'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Products per page', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 60,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['columns'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Columns', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 6,
			'inline'  => true,
			'default' => 4,
		];

		$this->controls['columnsTablet'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Columns, tablet', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 4,
			'inline'  => true,
			'default' => 3,
		];

		$this->controls['columnsMobile'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Columns, phone', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 3,
			'inline'  => true,
			'default' => 2,
		];

		$this->controls['gridGap'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Gap (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 80,
			'inline'  => true,
			'default' => 29,
		];

		$this->controls['orderby'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Default sort', 'pfh-widgets' ),
			'type'    => 'select',
			'options' => PFH_Widgets_Archive::order_options(),
			'default' => 'menu_order',
		];

		$this->controls['emptyText'] = [
			'tab'     => 'content',
			'group'   => 'grid',
			'label'   => esc_html__( 'Nothing found', 'pfh-widgets' ),
			'type'    => 'text',
			'default' => 'Geen producten gevonden. Probeer een andere filter.',
		];
	}

	private function pager_controls() {
		$this->controls['pagerEnable'] = [
			'tab'     => 'content',
			'group'   => 'pager',
			'label'   => esc_html__( 'Show pagination', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['pagerPrev'] = [
			'tab'      => 'content',
			'group'    => 'pager',
			'label'    => esc_html__( 'Previous', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Back',
			'required' => [ 'pagerEnable', '=', true ],
		];

		$this->controls['pagerNext'] = [
			'tab'      => 'content',
			'group'    => 'pager',
			'label'    => esc_html__( 'Next', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Next',
			'required' => [ 'pagerEnable', '=', true ],
		];

		$this->controls['pagerRange'] = [
			'tab'         => 'content',
			'group'       => 'pager',
			'label'       => esc_html__( 'Numbers shown', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 3,
			'max'         => 11,
			'inline'      => true,
			'default'     => 5,
			'required'    => [ 'pagerEnable', '=', true ],
		];

		$this->controls['pagerActiveBg'] = [
			'tab'      => 'content',
			'group'    => 'pager',
			'label'    => esc_html__( 'Current page background', 'pfh-widgets' ),
			'type'     => 'color',
			'required' => [ 'pagerEnable', '=', true ],
		];
	}

	private function card_controls() {
		$this->controls['cardRadius'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Image radius (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 40,
			'inline'  => true,
			'default' => 10,
		];

		$this->controls['cardRatio'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Image ratio', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => '255 / 285',
		];

		$this->controls['cardImageBg'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Image background', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#f4f6f3' ],
		];

		$this->controls['cardImagePad'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Image inset (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 60,
			'inline'  => true,
			'default' => 10,
		];

		$this->controls['cardGap'] = [
			'tab'     => 'content',
			'group'   => 'card',
			'label'   => esc_html__( 'Space between card rows (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 40,
			'inline'  => true,
			'default' => 13,
		];
	}

	private function layout_controls() {
		$this->controls['maxWidth'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Container width (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 600,
			'max'     => 1600,
			'inline'  => true,
			'default' => 1240,
		];

		$this->controls['padTop'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Space above (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 0,
		];

		$this->controls['padBottom'] = [
			'tab'     => 'content',
			'group'   => 'layout',
			'label'   => esc_html__( 'Space below (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 200,
			'inline'  => true,
			'default' => 40,
		];
	}

	/* ---------------------------------------------------------------------
	 * Configuration
	 * ------------------------------------------------------------------ */

	/**
	 * The subset of settings the query engine needs.
	 *
	 * Stored server-side and looked up by element id on an AJAX request, so
	 * the browser never gets to say what should be queried.
	 *
	 * @return array
	 */
	private function config() {
		$filters = [];

		foreach ( (array) $this->get( 'filters', [] ) as $row ) {
			$source = isset( $row['source'] ) ? sanitize_key( $row['source'] ) : '';

			if ( '' === $source ) {
				continue;
			}

			$filters[] = [
				'source' => $source,
				'label'  => isset( $row['label'] ) ? (string) $row['label'] : '',
				'open'   => ! empty( $row['open'] ),
				'limit'  => isset( $row['limit'] ) ? (int) $row['limit'] : 0,
			];
		}

		return [
			'per_page' => max( 1, (int) $this->get( 'perPage', 12 ) ),
			'orderby'  => (string) $this->get( 'orderby', 'menu_order' ),
			'base_cat' => $this->base_category(),
			'filters'  => $filters,
		];
	}

	/**
	 * The category this archive is pinned to.
	 *
	 * On a category archive that is the term being viewed; on the shop page it
	 * is empty, which means everything.
	 *
	 * @return string
	 */
	private function base_category() {
		if ( is_tax( 'product_cat' ) ) {
			$term = get_queried_object();

			if ( $term && ! is_wp_error( $term ) ) {
				return (string) $term->slug;
			}
		}

		return '';
	}

	/**
	 * Categories for the pill row.
	 *
	 * @return array<int, \WP_Term>
	 */
	private function pill_terms() {
		$source = (string) $this->get( 'catsSource', 'children' );

		if ( 'manual' === $source ) {
			$ids = PFH_Widgets_Helpers::category_ids( $this->get( 'catsManual' ) );

			if ( ! $ids ) {
				return [];
			}

			$terms = get_terms(
				[
					'taxonomy'   => 'product_cat',
					'include'    => $ids,
					'orderby'    => 'include',
					'hide_empty' => false,
				]
			);

			return is_wp_error( $terms ) ? [] : $terms;
		}

		$base = $this->base_category();
		$term = '' === $base ? null : get_term_by( 'slug', $base, 'product_cat' );

		if ( is_wp_error( $term ) ) {
			$term = null;
		}

		/*
		 * "All top level categories" was offered in the panel but never
		 * implemented: it fell through to the children branch, so on a
		 * category with no children the row of pills disappeared entirely
		 * and the visitor had no way back out of that category.
		 */
		$parents = [ 0 ];

		if ( 'top' !== $source && $term ) {
			$parents = 'siblings' === $source
				? [ (int) $term->parent, 0 ]
				: [ (int) $term->term_id, (int) $term->parent, 0 ];
		}

		// A switcher that offers nothing is worse than one showing the level
		// above, so each candidate is tried until one has categories in it.
		foreach ( $parents as $parent ) {
			$terms = get_terms(
				[
					'taxonomy'   => 'product_cat',
					'parent'     => $parent,
					'hide_empty' => true,
				]
			);

			if ( ! is_wp_error( $terms ) && $terms ) {
				return $terms;
			}
		}

		return [];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$this->prepare();

		if ( ! PFH_Widgets_Helpers::has_woocommerce() ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-arch pfh-arch--empty"><p>' . esc_html__( 'The shop archive needs WooCommerce.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$config = $this->config();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public, read-only filter state.
		$state = PFH_Widgets_Archive::state( wp_unslash( $_GET ), $config );

		$this->base = self::current_base();

		set_transient(
			self::CONFIG . $this->uid(),
			[
				'settings' => $this->settings,
				'base'     => $this->base,
			],
			DAY_IN_SECONDS
		);

		/*
		 * The shop card's button is the full-width TOEVOEGEN bar, always
		 * visible. That is not a preference with a default — it is what this
		 * card is, and the slider's corner button is what that one is.
		 *
		 * Forced rather than defaulted because Bricks stores an element's
		 * settings when the page is saved: a card added under an older build
		 * carries that build's value forever, and no change to a PHP default
		 * will ever reach it. This is why a shop page kept showing the
		 * slider's round button long after the default had moved on.
		 */
		$classes = [
			'pfh-arch',
			'pfh-prod',
			'pfh-scope',
			'pfh-arch--panel-' . (string) $this->get( 'panelSide', 'left' ),
			'pfh-cart-block',
			'pfh-reveal-none',
		];

		if ( $this->is_on( 'toolbarRules' ) ) {
			$classes[] = 'has-rules';
		}

		$this->set_attribute( '_root', 'class', $classes );
		$this->set_attribute( '_root', 'style', $this->build_vars() );
		$this->set_attribute( '_root', 'data-pfh-archive', $this->uid() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>';
		echo '<div class="pfh-arch__inner">';

		$this->render_toolbar( $state, $config );

		echo '<div class="pfh-arch__results" data-pfh-arch-results aria-live="polite" aria-busy="false">';
		$this->render_results( $state, $config );
		echo '</div>';

		$this->render_panel( $state, $config );

		echo '</div>';
		echo '</section>';
	}

	/**
	 * Category pills, count and the filter button, in the configured order.
	 *
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 */
	private function render_toolbar( array $state, array $config ) {
		$parts = [];

		foreach ( (array) $this->get( 'toolbar', [] ) as $row ) {
			if ( ! empty( $row['part'] ) ) {
				$parts[] = (string) $row['part'];
			}
		}

		if ( ! $parts ) {
			$parts = [ 'cats', 'spacer', 'count', 'filter' ];
		}

		echo '<div class="pfh-arch__toolbar">';

		foreach ( $parts as $part ) {
			switch ( $part ) {
				case 'cats':
					$this->render_pills( $state );
					break;

				case 'count':
					echo '<span class="pfh-arch__count" data-pfh-arch-count>' . esc_html( $this->count_text( $state, $config ) ) . '</span>';
					break;

				case 'filter':
					$this->render_filter_button( $state, $config );
					break;

				case 'sort':
					$this->render_sort( $state, $config );
					break;

				case 'spacer':
					echo '<span class="pfh-arch__spacer" aria-hidden="true"></span>';
					break;
			}
		}

		echo '</div>';
	}

	/**
	 * @param array $state Filter state.
	 */
	private function render_pills( array $state ) {
		if ( ! $this->is_on( 'catsEnable' ) ) {
			return;
		}

		$terms = $this->pill_terms();

		if ( ! $terms ) {
			return;
		}

		// Real archive links, not query-string filters. Switching category has
		// to change the title, the breadcrumb and the description as well as
		// the grid — and only a genuine archive request does that. The AJAX
		// layer deliberately leaves these alone.
		$here = $this->base_category();

		echo '<div class="pfh-arch__pills" role="group" aria-label="' . esc_attr__( 'Categories', 'pfh-widgets' ) . '">';

		if ( $this->is_on( 'catsAll' ) ) {
			printf(
				'<a class="pfh-arch__pill%1$s" href="%2$s"%3$s>%4$s</a>',
				'' === $here ? ' is-active' : '',
				esc_url( $this->shop_url() ),
				'' === $here ? ' aria-current="page"' : '',
				esc_html( (string) $this->get( 'catsAllLabel', 'Alles' ) )
			);
		}

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			printf(
				'<a class="pfh-arch__pill%1$s" href="%2$s"%3$s>%4$s</a>',
				$here === $term->slug ? ' is-active' : '',
				esc_url( $link ),
				$here === $term->slug ? ' aria-current="page"' : '',
				esc_html( $term->name )
			);
		}

		echo '</div>';
	}

	/**
	 * Where the "everything" pill goes.
	 *
	 * @return string
	 */
	private function shop_url() {
		$typed = PFH_Widgets_Helpers::link( $this->get( 'catsAllLink' ) );

		if ( '' !== $typed['href'] ) {
			return $typed['href'];
		}

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop = wc_get_page_permalink( 'shop' );

			if ( $shop ) {
				return $shop;
			}
		}

		return home_url( '/' );
	}

	/**
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 */
	private function render_filter_button( array $state, array $config ) {
		if ( ! $config['filters'] ) {
			return;
		}

		$active = PFH_Widgets_Archive::active_count( $state );

		printf(
			'<button type="button" class="pfh-arch__filter-btn" data-pfh-arch-open aria-expanded="false" aria-controls="pfh-panel-%1$s">%2$s<span>%3$s</span>%4$s</button>',
			esc_attr( $this->uid() ),
			PFH_Widgets_Icons::get( 'filter', 'pfh-arch__filter-icon' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			esc_html( (string) $this->get( 'filterLabel', 'Filter' ) ),
			$active
				? '<span class="pfh-arch__filter-badge" data-pfh-arch-badge>' . esc_html( number_format_i18n( $active ) ) . '</span>'
				: '<span class="pfh-arch__filter-badge" data-pfh-arch-badge hidden></span>'
		);
	}

	/**
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 */
	private function render_sort( array $state, array $config ) {
		$current = '' !== $state['orderby'] ? $state['orderby'] : $config['orderby'];

		echo '<label class="pfh-arch__sort"><span class="pfh-sr-only">' . esc_html__( 'Sort by', 'pfh-widgets' ) . '</span>';
		echo '<select data-pfh-arch-sort>';

		foreach ( PFH_Widgets_Archive::order_options() as $key => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $key ),
				selected( $current, $key, false ),
				esc_html( $label )
			);
		}

		echo '</select></label>';
	}

	/**
	 * The grid and the pager — everything AJAX replaces.
	 *
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 */
	private function render_results( array $state, array $config ) {
		$query = PFH_Widgets_Archive::query( $state, $config );

		if ( ! $query->have_posts() ) {
			echo '<p class="pfh-arch__empty">' . esc_html( (string) $this->get( 'emptyText', '' ) ) . '</p>';
			wp_reset_postdata();

			return;
		}

		echo '<ul class="pfh-arch__grid">';

		foreach ( $query->posts as $post ) {
			$card = $this->card_from_product( wc_get_product( $post ) );

			if ( $card ) {
				$this->render_card( $card );
			}
		}

		echo '</ul>';

		wp_reset_postdata();

		$this->render_pager( $state, (int) $query->max_num_pages );
	}

	/**
	 * @param array $state Filter state.
	 * @param int   $total Page count.
	 */
	private function render_pager( array $state, $total ) {
		if ( ! $this->is_on( 'pagerEnable' ) || $total < 2 ) {
			return;
		}

		$current = min( max( 1, (int) $state['page'] ), $total );
		$range   = max( 3, (int) $this->get( 'pagerRange', 5 ) );
		$half    = (int) floor( $range / 2 );

		$from = max( 1, $current - $half );
		$to   = min( $total, $from + $range - 1 );
		$from = max( 1, $to - $range + 1 );

		echo '<nav class="pfh-arch__pager" aria-label="' . esc_attr__( 'Pagination', 'pfh-widgets' ) . '">';

		printf(
			'<a class="pfh-arch__page pfh-arch__page--prev%1$s" href="%2$s" data-pfh-arch-page="%3$d"%4$s>%5$s<span>%6$s</span></a>',
			$current <= 1 ? ' is-disabled' : '',
			esc_url( $this->url( [ PFH_Widgets_Archive::WIRE['page'] => max( 1, $current - 1 ) ] ) ),
			max( 1, $current - 1 ),
			$current <= 1 ? ' aria-disabled="true"' : '',
			PFH_Widgets_Icons::get( 'chevron', 'pfh-arch__page-icon' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			esc_html( (string) $this->get( 'pagerPrev', 'Back' ) )
		);

		for ( $i = $from; $i <= $to; $i++ ) {
			printf(
				'<a class="pfh-arch__page%1$s" href="%2$s" data-pfh-arch-page="%3$d"%4$s>%5$s</a>',
				$i === $current ? ' is-current' : '',
				esc_url( $this->url( [ PFH_Widgets_Archive::WIRE['page'] => $i ] ) ),
				$i,
				$i === $current ? ' aria-current="page"' : '',
				esc_html( number_format_i18n( $i ) )
			);
		}

		printf(
			'<a class="pfh-arch__page pfh-arch__page--next%1$s" href="%2$s" data-pfh-arch-page="%3$d"%4$s><span>%5$s</span>%6$s</a>',
			$current >= $total ? ' is-disabled' : '',
			esc_url( $this->url( [ PFH_Widgets_Archive::WIRE['page'] => min( $total, $current + 1 ) ] ) ),
			min( $total, $current + 1 ),
			$current >= $total ? ' aria-disabled="true"' : '',
			esc_html( (string) $this->get( 'pagerNext', 'Next' ) ),
			PFH_Widgets_Icons::get( 'chevron', 'pfh-arch__page-icon' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		);

		echo '</nav>';
	}

	/**
	 * The slide-in filter panel.
	 *
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 */
	private function render_panel( array $state, array $config ) {
		if ( ! $config['filters'] ) {
			return;
		}

		printf(
			'<div class="pfh-arch__scrim" data-pfh-arch-scrim hidden></div><aside class="pfh-arch__panel" id="pfh-panel-%s" data-pfh-arch-panel aria-label="%s" hidden>',
			esc_attr( $this->uid() ),
			esc_attr( (string) $this->get( 'panelTitle', 'Filter' ) )
		);

		echo '<div class="pfh-arch__panel-head">';
		echo '<p class="pfh-arch__panel-title">' . esc_html( (string) $this->get( 'panelTitle', 'Filter' ) ) . '</p>';

		printf(
			'<button type="button" class="pfh-arch__clear" data-pfh-arch-clear%1$s>%2$s</button>',
			PFH_Widgets_Archive::is_filtered( $state ) ? '' : ' hidden',
			esc_html( (string) $this->get( 'panelClear', 'Alles Wissen' ) )
		);

		printf(
			'<button type="button" class="pfh-arch__panel-close" data-pfh-arch-close aria-label="%s">%s</button>',
			esc_attr__( 'Close', 'pfh-widgets' ),
			PFH_Widgets_Icons::get( 'close' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		);

		echo '</div>';

		echo '<div class="pfh-arch__facets" data-pfh-arch-facets>';
		$this->render_facets( $state, $config );
		echo '</div>';

		$apply = trim( (string) $this->get( 'panelApply', '' ) );

		if ( '' !== $apply ) {
			printf(
				'<div class="pfh-arch__panel-foot"><button type="button" class="pfh-arch__apply" data-pfh-arch-apply>%s</button></div>',
				esc_html( $apply )
			);
		}

		echo '</aside>';
	}

	/**
	 * Every filter group that still has something to offer.
	 *
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 */
	private function render_facets( array $state, array $config ) {
		$facets = PFH_Widgets_Archive::facets( $config, $state );

		if ( ! $facets ) {
			echo '<p class="pfh-arch__facets-empty">' . esc_html__( 'Nothing left to filter on.', 'pfh-widgets' ) . '</p>';

			return;
		}

		$more = (string) $this->get( 'panelMore', 'Meer…' );

		foreach ( $facets as $facet ) {
			/*
			 * A yes/no filter is one checkbox, and a collapsible heading over
			 * a single row repeats its own label back at the visitor — "In de
			 * aanbieding" above a box reading "In de aanbieding". It renders
			 * as a plain row instead, with the label on the box.
			 */
			$bare = 'toggle' === $facet['type'];

			printf(
				'<section class="pfh-arch__facet%1$s%2$s" data-pfh-arch-facet="%3$s">',
				$facet['open'] ? ' is-open' : '',
				$bare ? ' pfh-arch__facet--bare' : '',
				esc_attr( $facet['key'] )
			);

			if ( ! $bare ) {
				printf(
					'<button type="button" class="pfh-arch__facet-head" data-pfh-arch-toggle aria-expanded="%1$s"><span>%2$s</span>%3$s</button>',
					$facet['open'] ? 'true' : 'false',
					esc_html( $facet['label'] ),
					PFH_Widgets_Icons::get( 'chevron', 'pfh-arch__facet-chev' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				);
			}

			echo '<div class="pfh-arch__facet-body"' . ( $facet['open'] || $bare ? '' : ' hidden' ) . '>';

			if ( 'terms' === $facet['type'] ) {
				$this->render_term_facet( $facet, $more );
			} elseif ( 'price' === $facet['type'] ) {
				$this->render_price_facet( $facet );
			} else {
				$this->render_toggle_facet( $facet );
			}

			echo '</div></section>';
		}
	}

	/**
	 * @param array  $facet Facet group.
	 * @param string $more  "More" label.
	 */
	private function render_term_facet( array $facet, $more ) {
		$limit = (int) $facet['limit'];
		$i     = 0;

		echo '<ul class="pfh-arch__opts">';

		foreach ( $facet['options'] as $option ) {
			$hide = $limit > 0 && $i >= $limit && ! $option['checked'];

			printf(
				'<li class="pfh-arch__opt"%1$s><label><input type="checkbox" data-pfh-arch-tax="%2$s" value="%3$s"%4$s><span class="pfh-arch__box" aria-hidden="true"></span><span class="pfh-arch__opt-label">%5$s</span><span class="pfh-arch__opt-count">(%6$s)</span></label></li>',
				$hide ? ' data-pfh-arch-extra hidden' : '',
				esc_attr( $facet['source'] ),
				esc_attr( $option['slug'] ),
				$option['checked'] ? ' checked' : '',
				esc_html( $option['label'] ),
				esc_html( str_pad( (string) $option['count'], 2, '0', STR_PAD_LEFT ) )
			);

			$i++;
		}

		echo '</ul>';

		if ( $limit > 0 && count( $facet['options'] ) > $limit ) {
			printf(
				'<button type="button" class="pfh-arch__more" data-pfh-arch-more aria-expanded="false">%s</button>',
				esc_html( $more )
			);
		}
	}

	/**
	 * Price, as a two-handled range.
	 *
	 * Two native range inputs stacked over one track: each handle is a real
	 * slider, so it is keyboard operable and announced correctly, which a
	 * div-and-mousemove slider never is.
	 *
	 * @param array $facet Facet group.
	 */
	private function render_price_facet( array $facet ) {
		$min  = (int) floor( (float) $facet['min'] );
		$max  = (int) ceil( (float) $facet['max'] );
		$from = (int) max( $min, min( $max, round( (float) $facet['from'] ) ) );
		$to    = (int) max( $min, min( $max, round( (float) $facet['to'] ) ) );
		$span = max( 1, $max - $min );

		$symbol = function_exists( 'get_woocommerce_currency_symbol' )
			? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' )
			: '';

		printf(
			'<div class="pfh-arch__range" data-pfh-arch-range data-symbol="%1$s" style="--pfh-lo:%2$s%%;--pfh-hi:%3$s%%">
				<output class="pfh-arch__range-out">
					<span data-pfh-arch-out="min">%4$s</span>
					<span class="pfh-arch__range-dash" aria-hidden="true">&ndash;</span>
					<span data-pfh-arch-out="max">%5$s</span>
				</output>
				<div class="pfh-arch__range-rail" aria-hidden="true"><span class="pfh-arch__range-fill"></span></div>
				<input type="range" class="pfh-arch__range-input" data-pfh-arch-min min="%6$d" max="%7$d" step="1" value="%8$d" aria-label="%9$s">
				<input type="range" class="pfh-arch__range-input" data-pfh-arch-max min="%6$d" max="%7$d" step="1" value="%10$d" aria-label="%11$s">
			</div>',
			esc_attr( $symbol ),
			esc_attr( (string) round( ( ( $from - $min ) / $span ) * 100, 2 ) ),
			esc_attr( (string) round( ( ( $to - $min ) / $span ) * 100, 2 ) ),
			esc_html( $symbol . number_format_i18n( $from ) ),
			esc_html( $symbol . number_format_i18n( $to ) ),
			$min,
			$max,
			$from,
			esc_attr__( 'Lowest price', 'pfh-widgets' ),
			$to,
			esc_attr__( 'Highest price', 'pfh-widgets' )
		);
	}

	/**
	 * @param array $facet Facet group.
	 */
	private function render_toggle_facet( array $facet ) {
		printf(
			'<ul class="pfh-arch__opts"><li class="pfh-arch__opt"><label><input type="checkbox" data-pfh-arch-flag="%1$s"%2$s><span class="pfh-arch__box" aria-hidden="true"></span><span class="pfh-arch__opt-label">%3$s</span></label></li></ul>',
			esc_attr( $facet['source'] ),
			! empty( $facet['checked'] ) ? ' checked' : '',
			esc_html( $facet['label'] )
		);
	}

	/* ---------------------------------------------------------------------
	 * AJAX
	 * ------------------------------------------------------------------ */

	/**
	 * Re-render an archive for a filter request.
	 *
	 * Hooked once for the class; the element is rebuilt from the settings that
	 * were stored when the page rendered, so the AJAX view and the page view
	 * come from one code path.
	 *
	 * @param null|array $payload Null until answered.
	 * @param string     $id      Element id.
	 * @param array      $raw     Raw request.
	 * @return array|null
	 */
	public static function ajax_render( $payload, $id, $raw ) {
		if ( null !== $payload ) {
			return $payload;
		}

		$stored = get_transient( self::CONFIG . $id );

		if ( ! is_array( $stored ) || empty( $stored['settings'] ) || ! is_array( $stored['settings'] ) ) {
			return null;
		}

		$settings = $stored['settings'];

		$element           = new self( [ 'id' => $id ] );
		$element->name     = 'pfh-archive';
		$element->settings = $settings;
		$element->base     = isset( $stored['base'] ) ? (string) $stored['base'] : '';

		$element->element = [
			'id'       => $id,
			'name'     => 'pfh-archive',
			'settings' => $settings,
		];

		$element->prepare();

		$config = $element->config();

		// The archive's own category still comes from the stored config, so a
		// request cannot escape the collection it belongs to.
		$state = PFH_Widgets_Archive::state( $raw, $config );

		ob_start();
		$element->render_results( $state, $config );
		$results = ob_get_clean();

		ob_start();
		$element->render_facets( $state, $config );
		$facets = ob_get_clean();

		return [
			'results'  => $results,
			'facets'   => $facets,
			'count'    => $element->count_text( $state, $config ),
			'active'   => PFH_Widgets_Archive::active_count( $state ),
			'filtered' => PFH_Widgets_Archive::is_filtered( $state ),
			'url'      => self::as_path( $element->url( $state, true ) ),
		];
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	/**
	 * The "12 producten" line.
	 *
	 * @param array $state  Filter state.
	 * @param array $config Element configuration.
	 * @return string
	 */
	private function count_text( array $state, array $config ) {
		$args                   = PFH_Widgets_Archive::query_args( $state, $config );
		$args['posts_per_page'] = 1;
		$args['paged']          = 1;
		$args['fields']         = 'ids';

		$query = new WP_Query( $args );
		$found = (int) $query->found_posts;

		wp_reset_postdata();

		$text = (string) $this->get( 'countText', '%s producten' );

		return str_replace( '%s', number_format_i18n( $found ), $text );
	}

	/**
	 * A URL for this archive with some state changed.
	 *
	 * @param array $changes Query args to set. Empty values are removed.
	 * @param bool  $full    Treat $changes as a complete state.
	 * @return string
	 */
	private function url( array $changes, $full = false ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
		$current = $full ? [] : array_map( 'sanitize_text_field', wp_unslash( $_GET ) );

		if ( $full ) {
			$changes = $this->state_to_args( $changes );
		}

		foreach ( $changes as $key => $value ) {
			$is_page = PFH_Widgets_Archive::WIRE['page'] === $key;

			if ( '' === $value || null === $value || false === $value || ( $is_page && 1 === (int) $value ) ) {
				unset( $current[ $key ] );
				continue;
			}

			$current[ $key ] = $value;
		}

		$base = '' !== $this->base ? $this->base : self::current_base();

		return $current ? add_query_arg( $current, $base ) : $base;
	}

	/**
	 * The URL of the current request, without its query string.
	 *
	 * @return string
	 */
	private static function current_base() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$uri = strtok( $uri, '?' );

		return home_url( $uri );
	}

	/**
	 * The same URL with the host stripped off.
	 *
	 * The address bar is updated with history.replaceState(), which a
	 * browser refuses — and throws over — for a URL on another origin. The
	 * base is built with home_url(), so a shop reachable on www while its
	 * setting says non-www hands the browser a foreign origin, and the
	 * filter dies at the last step of an otherwise successful request. A
	 * path is always the visitor's own origin, and reads the same in the
	 * address bar.
	 *
	 * @param string $url Absolute URL.
	 * @return string Path, query string included.
	 */
	private static function as_path( $url ) {
		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) ) {
			return $url;
		}

		$path = isset( $parts['path'] ) ? $parts['path'] : '/';

		if ( ! empty( $parts['query'] ) ) {
			$path .= '?' . $parts['query'];
		}

		return $path;
	}

	/**
	 * Turn a state back into query args.
	 *
	 * @param array $state Filter state.
	 * @return array
	 */
	private function state_to_args( array $state ) {
		$w = PFH_Widgets_Archive::WIRE;

		$args = [
			$w['cat']     => $state['cat'],
			$w['page']    => (int) $state['page'] > 1 ? (int) $state['page'] : '',
			$w['orderby'] => $state['orderby'],
			$w['min']     => null === $state['min'] ? '' : $state['min'],
			$w['max']     => null === $state['max'] ? '' : $state['max'],
			$w['onsale']  => ! empty( $state['onsale'] ) ? 1 : '',
			$w['instock'] => ! empty( $state['instock'] ) ? 1 : '',
		];

		foreach ( $state['tax'] as $taxonomy => $slugs ) {
			$args[ PFH_Widgets_Archive::TAX_PREFIX . $taxonomy ] = implode( ',', $slugs );
		}

		return array_filter(
			$args,
			static function ( $value ) {
				return '' !== $value && null !== $value;
			}
		);
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			array_merge(
				$this->card_vars(),
				[
				'--pfh-arch-max'      => PFH_Widgets_Helpers::unit( $this->get( 'maxWidth', 1240 ) ),
				'--pfh-arch-pt'       => PFH_Widgets_Helpers::unit( $this->get( 'padTop', 0 ) ),
				'--pfh-arch-pb'       => PFH_Widgets_Helpers::unit( $this->get( 'padBottom', 40 ) ),
				'--pfh-arch-cols'     => (int) $this->get( 'columns', 4 ),
				'--pfh-arch-cols-t'   => (int) $this->get( 'columnsTablet', 3 ),
				'--pfh-arch-cols-m'   => (int) $this->get( 'columnsMobile', 2 ),
				'--pfh-arch-gap'      => PFH_Widgets_Helpers::unit( $this->get( 'gridGap', 29 ) ),
				'--pfh-arch-pill-gap' => PFH_Widgets_Helpers::unit( $this->get( 'catsGap', 8 ) ),
				'--pfh-arch-pill-r'   => PFH_Widgets_Helpers::unit( $this->get( 'catsRadius', 10 ) ),
				'--pfh-arch-pill-bg'  => PFH_Widgets_Helpers::color( $this->get( 'catsActiveBg' ) ),
				'--pfh-arch-pill-ink' => PFH_Widgets_Helpers::color( $this->get( 'catsColor' ) ),
				'--pfh-arch-rule'     => PFH_Widgets_Helpers::color( $this->get( 'toolbarRuleColor' ) ),
				'--pfh-arch-page-bg'  => PFH_Widgets_Helpers::color( $this->get( 'pagerActiveBg' ) ),
				'--pfh-arch-panel-w'  => PFH_Widgets_Helpers::unit( $this->get( 'panelWidth', 332 ) ),
				/*
				 * The card image is the shared .pfh-prod__media box, so these
				 * have to be its variable names. Under their own names they
				 * styled nothing and the slider's roomy defaults — a 26px pad
				 * inside a 310/358 box — showed through on the grid instead.
				 */
				'--pfh-media-radius'  => PFH_Widgets_Helpers::unit( $this->get( 'cardRadius', 10 ) ),
				'--pfh-media-ratio'   => (string) $this->get( 'cardRatio', '255 / 285' ),
				'--pfh-media-bg'      => PFH_Widgets_Helpers::color( $this->get( 'cardImageBg' ), '#f4f6f3' ),
				'--pfh-media-pad-set' => PFH_Widgets_Helpers::unit( $this->get( 'cardImagePad', 10 ) ),
				'--pfh-media-pad-t'   => PFH_Widgets_Helpers::unit( $this->get( 'cardImagePad', 10 ) ),
				'--pfh-t-size-m'      => PFH_Widgets_Helpers::unit( $this->get( 'titleSize', 14 ) ),
				'--pfh-gap-1'         => PFH_Widgets_Helpers::unit( $this->get( 'cardGap', 13 ) ),
				'--pfh-gap-2'         => PFH_Widgets_Helpers::unit( $this->get( 'cardGap', 13 ) ),
				'--pfh-gap-3'         => PFH_Widgets_Helpers::unit( $this->get( 'cardGap', 13 ) ),
				]
			)
		);
	}

	/**
	 * A setting, then this element's own default for it, then the caller's.
	 *
	 * The middle step matters: card_defaults() retunes the shared controls
	 * for this element — a 14px title where the slider wants 20, a teal
	 * button where the slider wants olive — and those live on the control,
	 * not at the call site. Without consulting them, a setting the migration
	 * has just dropped would fall through to the trait's fallback, which is
	 * the slider's value, and the archive would quietly render the slider's
	 * card again.
	 *
	 * @param string $key     Control name.
	 * @param mixed  $default Used only when the control has no default either.
	 * @return mixed
	 */
	private function get( $key, $default = null ) {
		if ( isset( $this->settings[ $key ] ) && '' !== $this->settings[ $key ] ) {
			return $this->settings[ $key ];
		}

		// Cleared on purpose: the control default must not put it back.
		if ( array_key_exists( $key, (array) $this->settings ) ) {
			return $default;
		}

		if ( isset( $this->controls[ $key ] ) && array_key_exists( 'default', $this->controls[ $key ] ) ) {
			$own = $this->controls[ $key ]['default'];

			if ( '' !== $own && null !== $own ) {
				return $own;
			}
		}

		return $default;
	}

	private function is_on( $key, $default = true ) {
		if ( ! array_key_exists( $key, (array) $this->settings ) ) {
			if ( isset( $this->controls[ $key ] ) && array_key_exists( 'default', $this->controls[ $key ] ) ) {
				return ! empty( $this->controls[ $key ]['default'] );
			}

			return $default;
		}

		return ! empty( $this->settings[ $key ] );
	}

	private function uid() {
		if ( null === $this->uid ) {
			$this->uid = ! empty( $this->element['id'] ) ? sanitize_html_class( $this->element['id'] ) : uniqid( 'pfha' );
		}

		return $this->uid;
	}
}
