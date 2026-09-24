<?php
/**
 * Bricks element: Products For Home product tabs.
 *
 * The four panels under the product — Omschrijving, Ingrediënten,
 * Houdbaarheid, Voedingswaarden — and the steps panel beside them.
 *
 * Everything in them comes from the product. The description is WooCommerce's
 * own; the rest are fields this plugin adds to the Product data box, so the
 * client fills them in where they are already working. A field left empty
 * leaves nothing on the page, and a tab with nothing in it is not drawn at all
 * rather than opening onto a blank panel.
 *
 * The steps panel is the same copy on every product — it is set here, once —
 * but each product can turn it off.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

/*
 * The product fields are this element's own dependency: Bricks loads an
 * element file on its own, and a class merely assumed to be there is how a
 * fatal reaches a live page.
 */
if ( ! class_exists( 'PFH_Widgets_Product_Fields' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'includes/class-pfh-product-fields.php';
}

class PFH_Element_Product_Tabs extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-product-tabs';
	public $icon         = 'ti-layout-tab';
	public $css_selector = '.pfh-tabs';

	public function get_label() {
		return esc_html__( 'PFH Product Tabs', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'product', 'tabs', 'description', 'ingredients', 'nutrition', 'pdp', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::product_tabs();
	}

	public function set_control_groups() {
		foreach ( [
			'source'    => esc_html__( 'Product', 'pfh-widgets' ),
			'tabs'      => esc_html__( 'Tabs', 'pfh-widgets' ),
			'desc'      => esc_html__( 'Omschrijving', 'pfh-widgets' ),
			'ingr'      => esc_html__( 'Ingredienten', 'pfh-widgets' ),
			'storage'   => esc_html__( 'Houdbaarheid', 'pfh-widgets' ),
			'nutrition' => esc_html__( 'Voedingswaarden', 'pfh-widgets' ),
			'steps'     => esc_html__( 'Steps panel', 'pfh-widgets' ),
			'style'     => esc_html__( 'Style', 'pfh-widgets' ),
			'layout'    => esc_html__( 'Layout', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		$this->controls['previewId'] = $this->text_field(
			'source',
			esc_html__( 'Product to show while editing', 'pfh-widgets' ),
			'',
			[
				'inline'      => true,
				'placeholder' => esc_html__( 'e.g. 1482', 'pfh-widgets' ),
				'description' => esc_html__( 'On a product page this always shows the product being viewed. This ID is only used in the builder. Empty uses the most recent product.', 'pfh-widgets' ),
			]
		);

		/* ---- which tabs, and what they are called ---- */

		foreach ( $this->tab_spec() as $key => $tab ) {
			$this->controls[ $key . 'On' ]    = $this->switch_field( 'tabs', sprintf( /* translators: tab name */ esc_html__( 'Show %s', 'pfh-widgets' ), $tab['label'] ), true );
			$this->controls[ $key . 'Label' ] = $this->text_field( 'tabs', sprintf( /* translators: tab name */ esc_html__( '%s label', 'pfh-widgets' ), $tab['label'] ), $tab['label'], [ 'inline' => true ] );
		}

		$this->controls['hideEmpty'] = $this->switch_field(
			'tabs',
			esc_html__( 'Hide a tab with nothing in it', 'pfh-widgets' ),
			true,
			[ 'description' => esc_html__( 'A product with no ingredients filled in simply has no Ingredienten tab, rather than one that opens onto nothing.', 'pfh-widgets' ) ]
		);

		/* ---- Omschrijving ---- */

		$this->controls['descEmpty'] = $this->text_field(
			'desc',
			esc_html__( 'When there is no description', 'pfh-widgets' ),
			'Geen informatie beschikbaar.',
			[ 'description' => esc_html__( 'This tab always shows, so it says this rather than nothing.', 'pfh-widgets' ) ]
		);

		$this->controls['descTicks'] = $this->switch_field(
			'desc',
			esc_html__( 'Bullet points become ticks', 'pfh-widgets' ),
			true,
			[ 'description' => esc_html__( 'Every list in the description is drawn with the tick from the design instead of a bullet.', 'pfh-widgets' ) ]
		);

		/* ---- Ingredienten ---- */

		$this->controls['ingrHeading']    = $this->text_field( 'ingr', esc_html__( 'Heading', 'pfh-widgets' ), 'Ingrediënten', [ 'inline' => true ] );
		$this->controls['allergenTitle']  = $this->text_field( 'ingr', esc_html__( 'Allergens heading', 'pfh-widgets' ), 'Allergenen', [ 'inline' => true ] );
		$this->controls['freeFromTitle']  = $this->text_field( 'ingr', esc_html__( 'Claims heading', 'pfh-widgets' ), 'Zonder', [ 'inline' => true ] );

		/* ---- Houdbaarheid ---- */

		$this->controls['storageHeading'] = $this->text_field(
			'storage',
			esc_html__( 'Heading', 'pfh-widgets' ),
			'Bewaring & houdbaarheid',
			[ 'description' => esc_html__( 'Used when the product has no heading of its own.', 'pfh-widgets' ) ]
		);

		/* ---- Voedingswaarden ---- */

		$this->controls['nutritionHeading'] = $this->text_field( 'nutrition', esc_html__( 'Heading', 'pfh-widgets' ), 'Voedingswaarden' );

		foreach ( [ 'col1' => 'Voedingsstof', 'col2' => 'Per 100ml', 'col3' => 'Per portie (200ml)' ] as $key => $default ) {
			$this->controls[ $key ] = $this->text_field(
				'nutrition',
				sprintf( /* translators: column number */ esc_html__( 'Column %d', 'pfh-widgets' ), (int) substr( $key, -1 ) ),
				$default,
				[ 'inline' => true ]
			);
		}

		$this->controls['stripe'] = $this->switch_field( 'nutrition', esc_html__( 'Shade alternate rows', 'pfh-widgets' ) );

		/* ---- steps ---- */

		$this->controls['stepsOn']      = $this->switch_field( 'steps', esc_html__( 'Show the steps panel', 'pfh-widgets' ), true, [ 'description' => esc_html__( 'Each product can still turn it off on its own.', 'pfh-widgets' ) ] );
		$this->controls['stepsEyebrow'] = $this->text_field( 'steps', esc_html__( 'Eyebrow', 'pfh-widgets' ), 'ZO SIMPEL', [ 'inline' => true ] );
		$this->controls['stepsTitle']   = $this->text_field( 'steps', esc_html__( 'Title', 'pfh-widgets' ), 'In 3 stappen', [ 'inline' => true ] );
		$this->controls['stepsTitleTail'] = $this->text_field( 'steps', esc_html__( 'Title, lighter half', 'pfh-widgets' ), 'klaar', [ 'inline' => true ] );

		$this->controls['steps'] = [
			'tab'           => 'content',
			'group'         => 'steps',
			'label'         => esc_html__( 'Steps', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'heading',
			'default'       => [
				[ 'heading' => 'Vul het siroop', 'text' => 'Voeg de vruchtensap toe tot de markering op het glas of gebruik de handige doseerpomp.' ],
				[ 'heading' => 'Voeg water toe', 'text' => 'Vul aan met stil of bruisend water tot de N/S markering op het glas.' ],
				[ 'heading' => 'Roer en geniet', 'text' => 'Roer even door, voeg desgewenst ijs toe, en geniet van je perfecte huisgemaakte limonade.' ],
			],
			'fields'        => [
				'heading' => [ 'label' => esc_html__( 'Heading', 'pfh-widgets' ), 'type' => 'text' ],
				'text'    => [ 'label' => esc_html__( 'Text', 'pfh-widgets' ), 'type' => 'textarea' ],
			],
		];

		/* ---- style ---- */

		$this->controls['accent']    = $this->colour_field( 'style', esc_html__( 'Headings and active tab', 'pfh-widgets' ), '#2b5f63' );
		$this->controls['quiet']     = $this->colour_field( 'style', esc_html__( 'Inactive tabs', 'pfh-widgets' ), '#8fb3b5' );
		$this->controls['underline'] = $this->colour_field( 'style', esc_html__( 'Active tab underline', 'pfh-widgets' ), '#51604f' );
		$this->controls['bodyInk']   = $this->colour_field( 'style', esc_html__( 'Body text', 'pfh-widgets' ), '#1f4a4e' );
		$this->controls['tickColor'] = $this->colour_field( 'style', esc_html__( 'Ticks', 'pfh-widgets' ), '#879f82' );
		$this->controls['cardBg']    = $this->colour_field( 'style', esc_html__( 'Card background', 'pfh-widgets' ), '#f5fcfd' );
		$this->controls['cardLine']  = $this->colour_field( 'style', esc_html__( 'Card border', 'pfh-widgets' ), '#c3d9db' );
		$this->controls['panelBg']   = $this->colour_field( 'style', esc_html__( 'Steps panel background', 'pfh-widgets' ), '#ecf3f4' );
		$this->controls['eyebrowInk'] = $this->colour_field( 'style', esc_html__( 'Eyebrow', 'pfh-widgets' ), '#3c868c' );
		$this->controls['stepInk']   = $this->colour_field( 'style', esc_html__( 'Step text', 'pfh-widgets' ), '#6e6b60' );
		$this->controls['line']      = $this->colour_field( 'style', esc_html__( 'Rules', 'pfh-widgets' ), '#eaeaea' );

		$this->controls['headingSize'] = $this->number_field( 'style', esc_html__( 'Heading size (px)', 'pfh-widgets' ), 30, 16, 56 );
		$this->controls['tabSize']     = $this->number_field( 'style', esc_html__( 'Tab size (px)', 'pfh-widgets' ), 16, 12, 24 );
		$this->controls['textSize']    = $this->number_field( 'style', esc_html__( 'Body size (px)', 'pfh-widgets' ), 14, 11, 20 );
		$this->controls['radius']      = $this->number_field( 'style', esc_html__( 'Corner radius (px)', 'pfh-widgets' ), 8, 0, 32 );

		/* ---- layout ---- */

		$this->controls['maxWidth']  = $this->number_field( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1140, 600, 1600 );
		$this->controls['gap']       = $this->number_field( 'layout', esc_html__( 'Space beside the steps (px)', 'pfh-widgets' ), 40, 12, 120 );
		$this->controls['stepsWidth'] = $this->number_field( 'layout', esc_html__( 'Steps panel width (px)', 'pfh-widgets' ), 305, 220, 480 );
		$this->controls['padTop']    = $this->number_field( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 40, 0, 200 );
		$this->controls['padBottom'] = $this->number_field( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 72, 0, 200 );
	}

	/* ---------------------------------------------------------------------
	 * Control helpers
	 * ------------------------------------------------------------------ */

	/**
	 * @param string $group   Group.
	 * @param string $label   Label.
	 * @param string $default Default.
	 * @param array  $extra   Anything else.
	 * @return array
	 */
	private function text_field( $group, $label, $default = '', array $extra = [] ) {
		return array_merge(
			[ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'text', 'default' => $default ],
			$extra
		);
	}

	/**
	 * @param string $group Group.
	 * @param string $label Label.
	 * @param bool   $on    Default.
	 * @param array  $extra Anything else.
	 * @return array
	 */
	private function switch_field( $group, $label, $on = true, array $extra = [] ) {
		return array_merge( [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'checkbox', 'default' => $on ], $extra );
	}

	/**
	 * @param string $group   Group.
	 * @param string $label   Label.
	 * @param string $default Hex.
	 * @return array
	 */
	private function colour_field( $group, $label, $default ) {
		return [
			'tab'     => 'content',
			'group'   => $group,
			'label'   => $label,
			'type'    => 'color',
			'inline'  => true,
			'default' => [ 'hex' => $default ],
		];
	}

	/**
	 * @param string $group   Group.
	 * @param string $label   Label.
	 * @param int    $default Default.
	 * @param int    $min     Min.
	 * @param int    $max     Max.
	 * @return array
	 */
	private function number_field( $group, $label, $default, $min, $max ) {
		return [
			'tab'     => 'content',
			'group'   => $group,
			'label'   => $label,
			'type'    => 'number',
			'min'     => $min,
			'max'     => $max,
			'inline'  => true,
			'default' => $default,
		];
	}

	/**
	 * The four tabs, in order.
	 *
	 * @return array<string, array{label: string}>
	 */
	private function tab_spec() {
		return [
			'desc'      => [ 'label' => 'Omschrijving' ],
			'ingr'      => [ 'label' => 'Ingrediënten' ],
			'storage'   => [ 'label' => 'Houdbaarheid' ],
			'nutrition' => [ 'label' => 'Voedingswaarden' ],
		];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$product = $this->product();

		if ( ! $product ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-tabs pfh-tabs--empty"><p>'
					. esc_html__( 'This shows the tabs for the product being viewed. There is none here, so give it a product to preview in the panel.', 'pfh-widgets' )
					. '</p></div>';
			}

			return;
		}

		$panels = $this->panels( $product );

		if ( ! $panels ) {
			return;
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-tabs', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		$uid   = 'pfh-tabs-' . ( isset( $this->id ) ? sanitize_key( (string) $this->id ) : 'x' );
		$steps = $this->steps_markup( $product );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-tabs__inner">';

		/* ---- the tab strip ---- */

		printf( '<div class="pfh-tabs__nav" role="tablist" aria-label="%s">', esc_attr__( 'Productinformatie', 'pfh-widgets' ) );

		$first = true;

		foreach ( $panels as $key => $panel ) {
			printf(
				'<button type="button" class="pfh-tabs__tab%s" role="tab" id="%s-tab-%s" aria-controls="%s-panel-%s" aria-selected="%s" tabindex="%s" data-pfh-tab="%s"><span>%s</span></button>',
				$first ? ' is-active' : '',
				esc_attr( $uid ),
				esc_attr( $key ),
				esc_attr( $uid ),
				esc_attr( $key ),
				$first ? 'true' : 'false',
				$first ? '0' : '-1',
				esc_attr( $key ),
				esc_html( $panel['label'] )
			);

			$first = false;
		}

		echo '</div>';

		/* ---- the panels, and the steps beside them ---- */

		printf( '<div class="pfh-tabs__body%s">', $steps ? '' : ' pfh-tabs__body--wide' );
		echo '<div class="pfh-tabs__panels">';

		$first = true;

		foreach ( $panels as $key => $panel ) {
			printf(
				'<div class="pfh-tabs__panel%s" role="tabpanel" id="%s-panel-%s" aria-labelledby="%s-tab-%s" data-pfh-panel="%s"%s>',
				$first ? ' is-active' : '',
				esc_attr( $uid ),
				esc_attr( $key ),
				esc_attr( $uid ),
				esc_attr( $key ),
				esc_attr( $key ),
				$first ? '' : ' hidden'
			);

			echo $panel['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped below.
			echo '</div>';

			$first = false;
		}

		echo '</div>';

		if ( $steps ) {
			echo $steps; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped below.
		}

		echo '</div>';
		echo '</div>';
		echo '</section>';
	}

	/**
	 * Every tab that has something to show.
	 *
	 * @param WC_Product $product Product.
	 * @return array<string, array{label: string, html: string}>
	 */
	private function panels( $product ) {
		$hide  = $this->switched_on( 'hideEmpty' );
		$out   = [];
		$built = [
			'desc'      => $this->description_panel( $product ),
			'ingr'      => $this->ingredients_panel( $product ),
			'storage'   => $this->storage_panel( $product ),
			'nutrition' => $this->nutrition_panel( $product ),
		];

		foreach ( $this->tab_spec() as $key => $tab ) {
			if ( ! $this->switched_on( $key . 'On' ) ) {
				continue;
			}

			// The description tab always shows: it says so when it is empty.
			if ( '' === $built[ $key ] && ( $hide || 'desc' !== $key ) ) {
				continue;
			}

			$label = trim( (string) $this->setting( $key . 'Label', $tab['label'] ) );

			$out[ $key ] = [
				'label' => '' !== $label ? $label : $tab['label'],
				'html'  => $built[ $key ],
			];
		}

		return $out;
	}

	/**
	 * @param WC_Product $product Product.
	 * @return string
	 */
	private function description_panel( $product ) {
		$text = trim( (string) $product->get_description() );

		if ( '' === $text ) {
			$empty = trim( (string) $this->setting( 'descEmpty', '' ) );

			return '' !== $empty ? '<p class="pfh-tabs__none">' . esc_html( $empty ) . '</p>' : '';
		}

		$classes = 'pfh-tabs__rich' . ( $this->switched_on( 'descTicks' ) ? ' pfh-tabs__rich--ticks' : '' );

		return '<div class="' . esc_attr( $classes ) . '">' . wp_kses_post( wpautop( $text ) ) . '</div>';
	}

	/**
	 * @param WC_Product $product Product.
	 * @return string
	 */
	private function ingredients_panel( $product ) {
		$id        = (int) $product->get_id();
		$body      = trim( PFH_Widgets_Product_Fields::text( $id, PFH_Widgets_Product_Fields::INGREDIENTS ) );
		$allergens = trim( PFH_Widgets_Product_Fields::text( $id, PFH_Widgets_Product_Fields::ALLERGENS ) );
		$claims    = PFH_Widgets_Product_Fields::rows( $id, PFH_Widgets_Product_Fields::FREE_FROM );

		if ( '' === $body && '' === $allergens && ! $claims ) {
			return '';
		}

		$html = $this->heading( (string) $this->setting( 'ingrHeading', 'Ingrediënten' ) );

		if ( '' !== $body ) {
			$html .= '<div class="pfh-tabs__rich pfh-tabs__rich--ticks">' . wp_kses_post( wpautop( $body ) ) . '</div>';
		}

		if ( '' !== $allergens ) {
			$html .= $this->subheading( (string) $this->setting( 'allergenTitle', 'Allergenen' ) );
			$html .= '<div class="pfh-tabs__note">' . wp_kses_post( wpautop( $allergens ) ) . '</div>';
		}

		if ( $claims ) {
			$html .= $this->subheading( (string) $this->setting( 'freeFromTitle', 'Zonder' ) );
			$html .= '<ul class="pfh-tabs__claims">';

			foreach ( $claims as $claim ) {
				$html .= '<li class="pfh-tabs__claim">'
					. PFH_Widgets_Icons::get( 'check-list', 'pfh-tabs__tick' )
					. '<span>' . esc_html( $claim['label'] ) . '</span></li>';
			}

			$html .= '</ul>';
		}

		return $html;
	}

	/**
	 * @param WC_Product $product Product.
	 * @return string
	 */
	private function storage_panel( $product ) {
		$id   = (int) $product->get_id();
		$rows = PFH_Widgets_Product_Fields::rows( $id, PFH_Widgets_Product_Fields::STORAGE );

		if ( ! $rows ) {
			return '';
		}

		$title = trim( PFH_Widgets_Product_Fields::text( $id, PFH_Widgets_Product_Fields::STORAGE_TITLE ) );
		$html  = $this->heading( '' !== $title ? $title : (string) $this->setting( 'storageHeading', '' ) );

		$html .= '<div class="pfh-tabs__cards">';

		foreach ( $rows as $row ) {
			$icon = '';

			if ( ! empty( $row['icon'] ) ) {
				$src = wp_get_attachment_image_url( (int) $row['icon'], 'thumbnail' );

				if ( $src ) {
					$icon = '<img class="pfh-tabs__card-icon" src="' . esc_url( $src ) . '" alt="" loading="lazy" />';
				}
			}

			if ( '' === $icon ) {
				$icon = PFH_Widgets_Icons::get( 'box-check', 'pfh-tabs__card-icon' );
			}

			$html .= '<div class="pfh-tabs__card">' . $icon . '<div class="pfh-tabs__card-body">';

			if ( '' !== $row['heading'] ) {
				$html .= '<h4 class="pfh-tabs__card-title">' . esc_html( $row['heading'] ) . '</h4>';
			}

			if ( '' !== $row['text'] ) {
				$html .= '<div class="pfh-tabs__card-text">' . wp_kses_post( wpautop( $row['text'] ) ) . '</div>';
			}

			$html .= '</div></div>';
		}

		return $html . '</div>';
	}

	/**
	 * @param WC_Product $product Product.
	 * @return string
	 */
	private function nutrition_panel( $product ) {
		$id   = (int) $product->get_id();
		$rows = PFH_Widgets_Product_Fields::rows( $id, PFH_Widgets_Product_Fields::NUTRITION );

		if ( ! $rows ) {
			return '';
		}

		$title = trim( PFH_Widgets_Product_Fields::text( $id, PFH_Widgets_Product_Fields::NUTRITION_TITLE ) );
		$intro = trim( PFH_Widgets_Product_Fields::text( $id, PFH_Widgets_Product_Fields::NUTRITION_INTRO ) );
		$html  = $this->heading( '' !== $title ? $title : (string) $this->setting( 'nutritionHeading', '' ) );

		if ( '' !== $intro ) {
			$html .= '<div class="pfh-tabs__lede">' . wp_kses_post( wpautop( $intro ) ) . '</div>';
		}

		// The product may rename the columns; otherwise the element's wording.
		$own     = PFH_Widgets_Product_Fields::columns( $id );
		$headers = [];

		foreach ( [ 'col1', 'col2', 'col3' ] as $i => $key ) {
			$headers[] = '' !== $own[ $i ] ? $own[ $i ] : (string) $this->setting( $key, '' );
		}

		$html .= '<div class="pfh-tabs__scroll"><table class="pfh-tabs__table' . ( $this->switched_on( 'stripe' ) ? ' is-striped' : '' ) . '"><thead><tr>';

		foreach ( $headers as $i => $header ) {
			$html .= '<th' . ( $i ? ' class="is-figure"' : '' ) . '>' . esc_html( $header ) . '</th>';
		}

		$html .= '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			$html .= '<tr>'
				. '<td>' . esc_html( $row['c1'] ) . '</td>'
				. '<td class="is-figure">' . esc_html( $row['c2'] ) . '</td>'
				. '<td class="is-figure">' . esc_html( $row['c3'] ) . '</td>'
				. '</tr>';
		}

		return $html . '</tbody></table></div>';
	}

	/**
	 * The steps panel, when this product wants it.
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	private function steps_markup( $product ) {
		if ( ! $this->switched_on( 'stepsOn' ) || ! PFH_Widgets_Product_Fields::steps_shown( (int) $product->get_id() ) ) {
			return '';
		}

		$steps = $this->setting( 'steps', [] );
		$steps = is_array( $steps ) ? $steps : [];
		$rows  = [];

		foreach ( $steps as $step ) {
			$heading = isset( $step['heading'] ) ? trim( (string) $step['heading'] ) : '';
			$text    = isset( $step['text'] ) ? trim( (string) $step['text'] ) : '';

			if ( '' !== $heading || '' !== $text ) {
				$rows[] = [ 'heading' => $heading, 'text' => $text ];
			}
		}

		if ( ! $rows ) {
			return '';
		}

		$eyebrow = trim( (string) $this->setting( 'stepsEyebrow', '' ) );
		$title   = trim( (string) $this->setting( 'stepsTitle', '' ) );
		$tail    = trim( (string) $this->setting( 'stepsTitleTail', '' ) );

		$html = '<aside class="pfh-tabs__steps">';

		if ( '' !== $eyebrow ) {
			$html .= '<p class="pfh-tabs__steps-eyebrow">' . esc_html( $eyebrow ) . '</p>';
		}

		if ( '' !== $title || '' !== $tail ) {
			$html .= '<h3 class="pfh-tabs__steps-title">'
				. ( '' !== $title ? '<strong>' . esc_html( $title ) . '</strong>' : '' )
				. ( '' !== $tail ? ' <span>' . esc_html( $tail ) . '</span>' : '' )
				. '</h3><span class="pfh-tabs__steps-rule" aria-hidden="true"></span>';
		}

		$html .= '<ol class="pfh-tabs__steps-list">';

		foreach ( $rows as $i => $row ) {
			$html .= '<li class="pfh-tabs__step">'
				. '<span class="pfh-tabs__step-no" aria-hidden="true">' . esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ) . '</span>'
				. '<div class="pfh-tabs__step-body">';

			if ( '' !== $row['heading'] ) {
				$html .= '<h4 class="pfh-tabs__step-title">' . esc_html( $row['heading'] ) . '</h4>';
			}

			if ( '' !== $row['text'] ) {
				$html .= '<p class="pfh-tabs__step-text">' . esc_html( $row['text'] ) . '</p>';
			}

			$html .= '</div></li>';
		}

		return $html . '</ol></aside>';
	}

	/**
	 * @param string $text Heading.
	 * @return string
	 */
	private function heading( $text ) {
		$text = trim( $text );

		return '' !== $text ? '<h2 class="pfh-tabs__heading">' . esc_html( $text ) . '</h2>' : '';
	}

	/**
	 * @param string $text Sub-heading.
	 * @return string
	 */
	private function subheading( $text ) {
		$text = trim( $text );

		return '' !== $text ? '<h3 class="pfh-tabs__subheading">' . esc_html( $text ) . '</h3>' : '';
	}

	/**
	 * The product being viewed, or the one to preview.
	 *
	 * @return WC_Product|null
	 */
	private function product() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$id = is_singular( 'product' ) ? get_queried_object_id() : 0;

		if ( ! $id ) {
			$id = (int) $this->setting( 'previewId', 0 );
		}

		if ( ! $id ) {
			global $post;

			if ( $post && 'product' === get_post_type( $post ) ) {
				$id = (int) $post->ID;
			}
		}

		if ( ! $id ) {
			$recent = get_posts( [ 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ] );
			$id     = $recent ? (int) $recent[0] : 0;
		}

		if ( ! $id ) {
			return null;
		}

		$product = wc_get_product( $id );

		return ( $product && is_object( $product ) ) ? $product : null;
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-tabs-max'         => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1140 ) ),
				'--pfh-tabs-gap-set'     => PFH_Widgets_Helpers::unit( $this->setting( 'gap', 40 ) ),
				'--pfh-tabs-aside'       => PFH_Widgets_Helpers::unit( $this->setting( 'stepsWidth', 305 ) ),
				'--pfh-tabs-pt-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 40 ) ),
				'--pfh-tabs-pb-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 72 ) ),
				'--pfh-tabs-radius'      => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 8 ) ),
				'--pfh-tabs-heading-set' => PFH_Widgets_Helpers::unit( $this->setting( 'headingSize', 30 ) ),
				'--pfh-tabs-tab-set'     => PFH_Widgets_Helpers::unit( $this->setting( 'tabSize', 16 ) ),
				'--pfh-tabs-text'        => PFH_Widgets_Helpers::unit( $this->setting( 'textSize', 14 ) ),
				'--pfh-tabs-accent'      => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#2b5f63' ),
				'--pfh-tabs-quiet'       => PFH_Widgets_Helpers::color( $this->setting( 'quiet' ), '#8fb3b5' ),
				'--pfh-tabs-underline'   => PFH_Widgets_Helpers::color( $this->setting( 'underline' ), '#51604f' ),
				'--pfh-tabs-body'        => PFH_Widgets_Helpers::color( $this->setting( 'bodyInk' ), '#1f4a4e' ),
				'--pfh-tabs-tick'        => PFH_Widgets_Helpers::color( $this->setting( 'tickColor' ), '#879f82' ),
				'--pfh-tabs-card'        => PFH_Widgets_Helpers::color( $this->setting( 'cardBg' ), '#f5fcfd' ),
				'--pfh-tabs-card-line'   => PFH_Widgets_Helpers::color( $this->setting( 'cardLine' ), '#c3d9db' ),
				'--pfh-tabs-panel'       => PFH_Widgets_Helpers::color( $this->setting( 'panelBg' ), '#ecf3f4' ),
				'--pfh-tabs-eyebrow'     => PFH_Widgets_Helpers::color( $this->setting( 'eyebrowInk' ), '#3c868c' ),
				'--pfh-tabs-step'        => PFH_Widgets_Helpers::color( $this->setting( 'stepInk' ), '#6e6b60' ),
				'--pfh-tabs-line'        => PFH_Widgets_Helpers::color( $this->setting( 'line' ), '#eaeaea' ),
			]
		);
	}
}
