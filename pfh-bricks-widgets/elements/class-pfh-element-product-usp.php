<?php
/**
 * Bricks element: Products For Home "why this product".
 *
 * The band of pastel cards under a product — an eyebrow, a heading with one
 * word set in the italic serif, and a card for each reason to buy it.
 *
 * All of it is the product's own, set under Products For Home, so it reads
 * differently for a syrup than for a jar of honey. What is set here is only
 * the fallback for products that have not been given any; with neither, the
 * band is left off the page rather than drawn empty.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PFH_Widgets_Product_Fields' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'includes/class-pfh-product-fields.php';
}

class PFH_Element_Product_Usp extends \Bricks\Element {

	use PFH_Element_Defaults;

	/** The four pastels the cards take in turn. */
	const PALETTE = [ '#f9e9cf', '#dfe9dc', '#e6eff4', '#fde1d5' ];

	public $category     = 'products-for-home';
	public $name         = 'pfh-product-usp';
	public $icon         = 'ti-layout-grid4';
	public $css_selector = '.pfh-usp';

	public function get_label() {
		return esc_html__( 'PFH Product Reasons', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'usp', 'reasons', 'why', 'benefits', 'product', 'pdp', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::product_usp();
	}

	public function set_control_groups() {
		foreach ( [
			'source'  => esc_html__( 'Product', 'pfh-widgets' ),
			'content' => esc_html__( 'Fallback content', 'pfh-widgets' ),
			'style'   => esc_html__( 'Style', 'pfh-widgets' ),
			'layout'  => esc_html__( 'Layout', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		$this->controls['previewId'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Product to show while editing', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'e.g. 1482', 'pfh-widgets' ),
			'description' => esc_html__( 'On a product page this always follows the product being viewed. Empty uses the most recent product.', 'pfh-widgets' ),
		];

		$this->controls['sourceNote'] = [
			'tab'     => 'content',
			'group'   => 'source',
			'type'    => 'info',
			'content' => esc_html__( 'Each product sets its own reasons under Products For Home → Why this product. What follows is used only for products that have none.', 'pfh-widgets' ),
		];

		/* ---- fallback ---- */

		$this->controls['eyebrow'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Eyebrow', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'WAAROM GIA...GIAMAS',
		];

		$this->controls['title'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Title', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'Puur natuur, <em>ongeevenaard</em> van smaak',
			'description' => esc_html__( 'A word wrapped in <em> is set in the italic serif, as drawn.', 'pfh-widgets' ),
		];

		$this->controls['cards'] = [
			'tab'           => 'content',
			'group'         => 'content',
			'label'         => esc_html__( 'Cards', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'label',
			'default'       => [],
			'fields'        => [
				'icon'  => [ 'label' => esc_html__( 'Icon', 'pfh-widgets' ), 'type' => 'image' ],
				'label' => [ 'label' => esc_html__( 'Title', 'pfh-widgets' ), 'type' => 'text' ],
				'text'  => [ 'label' => esc_html__( 'Text', 'pfh-widgets' ), 'type' => 'textarea' ],
				'color' => [ 'label' => esc_html__( 'Background', 'pfh-widgets' ), 'type' => 'color' ],
			],
		];

		/* ---- style ---- */

		foreach ( self::PALETTE as $i => $hex ) {
			$this->controls[ 'pastel' . ( $i + 1 ) ] = [
				'tab'     => 'content',
				'group'   => 'style',
				'label'   => sprintf( /* translators: card number */ esc_html__( 'Card colour %d', 'pfh-widgets' ), $i + 1 ),
				'type'    => 'color',
				'inline'  => true,
				'default' => [ 'hex' => $hex ],
			];
		}

		$this->controls['eyebrowInk'] = $this->colour( 'style', esc_html__( 'Eyebrow', 'pfh-widgets' ), '#8a9a8a' );
		$this->controls['titleInk']   = $this->colour( 'style', esc_html__( 'Title', 'pfh-widgets' ), '#14181b' );
		$this->controls['cardInk']    = $this->colour( 'style', esc_html__( 'Card title', 'pfh-widgets' ), '#14181b' );
		$this->controls['textInk']    = $this->colour( 'style', esc_html__( 'Card text', 'pfh-widgets' ), '#6e6b60' );
		$this->controls['tileBg']     = $this->colour( 'style', esc_html__( 'Icon tile', 'pfh-widgets' ), '#ffffff' );

		$this->controls['titleSize'] = $this->number( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 40, 20, 72 );
		$this->controls['cardSize']  = $this->number( 'style', esc_html__( 'Card title size (px)', 'pfh-widgets' ), 17, 12, 28 );
		$this->controls['textSize']  = $this->number( 'style', esc_html__( 'Card text size (px)', 'pfh-widgets' ), 14, 11, 20 );
		$this->controls['radius']    = $this->number( 'style', esc_html__( 'Corner radius (px)', 'pfh-widgets' ), 16, 0, 40 );

		/* ---- layout ---- */

		$this->controls['maxWidth'] = $this->number( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1140, 600, 1600 );
		$this->controls['columns']  = $this->number( 'layout', esc_html__( 'Cards per row', 'pfh-widgets' ), 4, 1, 6 );
		$this->controls['gap']      = $this->number( 'layout', esc_html__( 'Space between cards (px)', 'pfh-widgets' ), 20, 8, 60 );
		$this->controls['padTop']   = $this->number( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 72, 0, 200 );
		$this->controls['padBottom'] = $this->number( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 72, 0, 200 );
	}

	/**
	 * @param string $group   Group.
	 * @param string $label   Label.
	 * @param string $default Hex.
	 * @return array
	 */
	private function colour( $group, $label, $default ) {
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
	private function number( $group, $label, $default, $min, $max ) {
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

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$product = $this->product();
		$cards   = $this->cards( $product );

		if ( ! $cards ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-usp pfh-usp--empty"><p>'
					. esc_html__( 'Nothing to show: this product has no reasons under Products For Home, and no fallback cards are set here.', 'pfh-widgets' )
					. '</p></div>';
			}

			return;
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-usp', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		$eyebrow = $this->line( $product, PFH_Widgets_Product_Fields::USP_EYEBROW, 'eyebrow' );
		$title   = $this->line( $product, PFH_Widgets_Product_Fields::USP_TITLE, 'title' );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-usp__inner">';

		if ( '' !== $eyebrow ) {
			echo '<p class="pfh-usp__eyebrow">' . esc_html( wp_strip_all_tags( $eyebrow ) ) . '</p>';
		}

		if ( '' !== $title ) {
			// <em> is the italic serif in the design; nothing else is allowed.
			echo '<h2 class="pfh-usp__title">' . wp_kses( $title, [ 'em' => [], 'i' => [], 'br' => [], 'strong' => [] ] ) . '</h2>';
		}

		echo '<ul class="pfh-usp__grid">';

		foreach ( $cards as $i => $card ) {
			printf( '<li class="pfh-usp__card" style="--pfh-usp-card:%s">', esc_attr( $this->card_colour( $card, $i ) ) );

			$icon = $this->icon_markup( $card );

			if ( '' !== $icon ) {
				echo '<span class="pfh-usp__icon">' . $icon . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built below.
			}

			if ( '' !== $card['label'] ) {
				echo '<h3 class="pfh-usp__card-title">' . esc_html( $card['label'] ) . '</h3>';
			}

			if ( '' !== $card['text'] ) {
				echo '<div class="pfh-usp__card-text">' . wp_kses_post( wpautop( $card['text'] ) ) . '</div>';
			}

			echo '</li>';
		}

		echo '</ul></div></section>';
	}

	/**
	 * The cards for this product, or the ones set here.
	 *
	 * @param WC_Product|null $product Product.
	 * @return array<int, array{icon: mixed, label: string, text: string, color: mixed}>
	 */
	private function cards( $product ) {
		$out = [];

		if ( $product ) {
			foreach ( PFH_Widgets_Product_Fields::rows( (int) $product->get_id(), PFH_Widgets_Product_Fields::USP ) as $row ) {
				$out[] = [
					'icon'  => isset( $row['icon'] ) ? $row['icon'] : '',
					'label' => isset( $row['label'] ) ? $row['label'] : '',
					'text'  => isset( $row['text'] ) ? $row['text'] : '',
					'color' => isset( $row['color'] ) ? $row['color'] : '',
				];
			}
		}

		if ( $out ) {
			return $out;
		}

		$fallback = $this->setting( 'cards', [] );

		foreach ( is_array( $fallback ) ? $fallback : [] as $row ) {
			$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
			$text  = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';

			if ( '' === $label && '' === $text ) {
				continue;
			}

			$out[] = [
				'icon'  => isset( $row['icon'] ) ? $row['icon'] : '',
				'label' => $label,
				'text'  => $text,
				'color' => isset( $row['color'] ) ? $row['color'] : '',
			];
		}

		return $out;
	}

	/**
	 * A line the product may have its own version of.
	 *
	 * @param WC_Product|null $product  Product.
	 * @param string          $meta_key Product field.
	 * @param string          $control  Fallback control.
	 * @return string
	 */
	private function line( $product, $meta_key, $control ) {
		$own = $product ? trim( PFH_Widgets_Product_Fields::text( (int) $product->get_id(), $meta_key ) ) : '';

		return '' !== $own ? $own : trim( (string) $this->setting( $control, '' ) );
	}

	/**
	 * The card's own colour, or the next pastel in turn.
	 *
	 * @param array $card  Card.
	 * @param int   $index Position.
	 * @return string
	 */
	private function card_colour( array $card, $index ) {
		$own = PFH_Widgets_Helpers::color( $card['color'], '' );

		if ( '' !== $own ) {
			return $own;
		}

		$slot = ( $index % count( self::PALETTE ) ) + 1;

		return PFH_Widgets_Helpers::color( $this->setting( 'pastel' . $slot ), self::PALETTE[ $slot - 1 ] );
	}

	/**
	 * @param array $card Card.
	 * @return string
	 */
	private function icon_markup( array $card ) {
		$url = '';

		if ( is_array( $card['icon'] ) ) {
			$url = PFH_Widgets_Helpers::image_url( $card['icon'], 'thumbnail' );
		} elseif ( is_scalar( $card['icon'] ) && absint( $card['icon'] ) ) {
			$url = (string) wp_get_attachment_image_url( absint( $card['icon'] ), 'thumbnail' );
		}

		if ( '' !== $url ) {
			return '<img src="' . esc_url( $url ) . '" alt="" loading="lazy" />';
		}

		// Something rather than a gap, so a card without an icon still reads
		// as one of a set.
		return PFH_Widgets_Icons::get( 'check-list' );
	}

	/**
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
				'--pfh-usp-max'       => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1140 ) ),
				'--pfh-usp-cols-set'      => max( 1, (int) $this->setting( 'columns', 4 ) ),
				'--pfh-usp-gap-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'gap', 20 ) ),
				'--pfh-usp-pt-set'        => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 72 ) ),
				'--pfh-usp-pb-set'        => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 72 ) ),
				'--pfh-usp-radius'    => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 16 ) ),
				'--pfh-usp-title-set' => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 40 ) ),
				'--pfh-usp-card-size' => PFH_Widgets_Helpers::unit( $this->setting( 'cardSize', 17 ) ),
				'--pfh-usp-text'      => PFH_Widgets_Helpers::unit( $this->setting( 'textSize', 14 ) ),
				'--pfh-usp-eyebrow'   => PFH_Widgets_Helpers::color( $this->setting( 'eyebrowInk' ), '#8a9a8a' ),
				'--pfh-usp-title-ink' => PFH_Widgets_Helpers::color( $this->setting( 'titleInk' ), '#14181b' ),
				'--pfh-usp-card-ink'  => PFH_Widgets_Helpers::color( $this->setting( 'cardInk' ), '#14181b' ),
				'--pfh-usp-text-ink'  => PFH_Widgets_Helpers::color( $this->setting( 'textInk' ), '#6e6b60' ),
				'--pfh-usp-tile'      => PFH_Widgets_Helpers::color( $this->setting( 'tileBg' ), '#ffffff' ),
			]
		);
	}
}
