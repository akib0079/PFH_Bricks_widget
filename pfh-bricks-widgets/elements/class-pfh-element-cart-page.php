<?php
/**
 * Bricks element: the cart page.
 *
 * Everything in the cart with its picture, options, price and a stepper;
 * how far the order is from free shipping; and beside it the order summary —
 * WooCommerce's own totals, a coupon field, the way to checkout and the
 * reasons to trust the shop. Every change happens in place.
 *
 * The drawing lives in PFH_Widgets_Cart_Page, which the AJAX call after each
 * change uses too; this class is the Bricks panel and the frame around it.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Cart_Page extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-cart-page';
	public $icon         = 'ti-shopping-cart';
	public $css_selector = '.pfh-cartp';

	public function get_label() {
		return esc_html__( 'PFH Cart', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'cart', 'winkelwagen', 'basket', 'woocommerce', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::cart_page();
	}

	public function set_control_groups() {
		$this->control_groups['head']     = [ 'title' => esc_html__( 'Heading', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['shipping'] = [ 'title' => esc_html__( 'Free shipping bar', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['lines']    = [ 'title' => esc_html__( 'Products', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['summary']  = [ 'title' => esc_html__( 'Order summary', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['reasons']  = [ 'title' => esc_html__( 'Reassurance', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['empty']    = [ 'title' => esc_html__( 'Empty cart', 'pfh-widgets' ), 'tab' => 'content' ];
		$this->control_groups['style']    = [ 'title' => esc_html__( 'Style', 'pfh-widgets' ), 'tab' => 'content' ];
	}

	public function set_controls() {
		$d = PFH_Widgets_Cart_Page::defaults();

		/* ---- heading ---- */

		$this->text( 'eyebrow', 'head', esc_html__( 'Small line above', 'pfh-widgets' ), $d['eyebrow'] );
		$this->text( 'title', 'head', esc_html__( 'Title', 'pfh-widgets' ), $d['title'], esc_html__( 'Wrap a word in <em> for the serif accent.', 'pfh-widgets' ) );

		$this->controls['titleTag'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Title tag', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [ 'h1' => 'H1', 'h2' => 'H2', 'div' => 'div' ],
			'default' => $d['titleTag'],
		];

		$this->text( 'countOne', 'head', esc_html__( 'Count, one item', 'pfh-widgets' ), $d['countOne'] );
		$this->text( 'countMany', 'head', esc_html__( 'Count, more items', 'pfh-widgets' ), $d['countMany'], esc_html__( '%count% is the number of items.', 'pfh-widgets' ) );
		$this->text( 'continue', 'head', esc_html__( 'Continue shopping link', 'pfh-widgets' ), $d['continue'], esc_html__( 'Leave empty to hide it.', 'pfh-widgets' ) );
		$this->text( 'continueUrl', 'head', esc_html__( 'Continue shopping URL', 'pfh-widgets' ), '', esc_html__( 'Empty goes to the shop page.', 'pfh-widgets' ) );

		/* ---- free shipping ---- */

		$this->controls['shipBar'] = [
			'tab'     => 'content',
			'group'   => 'shipping',
			'label'   => esc_html__( 'Show the free shipping bar', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['shipInfo'] = [
			'tab'      => 'content',
			'group'    => 'shipping',
			'type'     => 'info',
			'content'  => esc_html__( 'If WooCommerce\'s own "Free shipping" method has a minimum amount for the shopper\'s zone, that amount is used. Otherwise the amounts below.', 'pfh-widgets' ),
			'required' => [ 'shipBar', '=', true ],
		];

		$this->controls['shipFrom'] = [
			'tab'      => 'content',
			'group'    => 'shipping',
			'label'    => esc_html__( 'Free from (amount)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'inline'   => true,
			'default'  => $d['shipFrom'],
			'required' => [ 'shipBar', '=', true ],
		];

		$this->controls['shipCountries'] = [
			'tab'         => 'content',
			'group'       => 'shipping',
			'label'       => esc_html__( 'Other amount per country', 'pfh-widgets' ),
			'type'        => 'textarea',
			'default'     => $d['shipCountries'],
			'description' => esc_html__( 'One per line: a two-letter country code, a colon, the amount. "BE: 70".', 'pfh-widgets' ),
			'required'    => [ 'shipBar', '=', true ],
		];

		$this->text( 'shipTodo', 'shipping', esc_html__( 'Not yet free', 'pfh-widgets' ), $d['shipTodo'], esc_html__( '%amount% is what is still missing.', 'pfh-widgets' ), [ 'shipBar', '=', true ] );
		$this->text( 'shipDone', 'shipping', esc_html__( 'Free', 'pfh-widgets' ), $d['shipDone'], '', [ 'shipBar', '=', true ] );

		/* ---- lines ---- */

		$this->text( 'colProduct', 'lines', esc_html__( 'Column: product', 'pfh-widgets' ), $d['colProduct'] );
		$this->text( 'colQty', 'lines', esc_html__( 'Column: quantity', 'pfh-widgets' ), $d['colQty'] );
		$this->text( 'colTotal', 'lines', esc_html__( 'Column: total', 'pfh-widgets' ), $d['colTotal'] );
		$this->text( 'remove', 'lines', esc_html__( 'Remove', 'pfh-widgets' ), $d['remove'] );
		$this->text( 'removed', 'lines', esc_html__( 'After removing', 'pfh-widgets' ), $d['removed'], esc_html__( '%name% is the product.', 'pfh-widgets' ) );
		$this->text( 'undo', 'lines', esc_html__( 'Undo', 'pfh-widgets' ), $d['undo'] );
		$this->text( 'backorder', 'lines', esc_html__( 'On backorder', 'pfh-widgets' ), $d['backorder'] );

		/* ---- summary ---- */

		$this->text( 'summaryTitle', 'summary', esc_html__( 'Title', 'pfh-widgets' ), $d['summaryTitle'] );
		$this->text( 'subtotal', 'summary', esc_html__( 'Subtotal', 'pfh-widgets' ), $d['subtotal'] );
		$this->text( 'shipping', 'summary', esc_html__( 'Shipping', 'pfh-widgets' ), $d['shipping'] );
		$this->text( 'shippingLater', 'summary', esc_html__( 'Shipping not known yet', 'pfh-widgets' ), $d['shippingLater'] );
		$this->text( 'free', 'summary', esc_html__( 'Free', 'pfh-widgets' ), $d['free'] );
		$this->text( 'total', 'summary', esc_html__( 'Total', 'pfh-widgets' ), $d['total'] );
		$this->text( 'couponToggle', 'summary', esc_html__( 'Coupon: open', 'pfh-widgets' ), $d['couponToggle'] );
		$this->text( 'couponField', 'summary', esc_html__( 'Coupon: field', 'pfh-widgets' ), $d['couponField'] );
		$this->text( 'couponApply', 'summary', esc_html__( 'Coupon: apply', 'pfh-widgets' ), $d['couponApply'] );
		$this->text( 'couponRemove', 'summary', esc_html__( 'Coupon: remove', 'pfh-widgets' ), $d['couponRemove'] );
		$this->text( 'checkout', 'summary', esc_html__( 'Checkout button', 'pfh-widgets' ), $d['checkout'] );

		/* ---- reassurance ---- */

		$icons = [];

		foreach ( PFH_Widgets_Cart_Page::ICONS as $icon ) {
			$icons[ $icon ] = ucwords( str_replace( [ 'usp-', '-' ], [ '', ' ' ], $icon ) );
		}

		$this->controls['reasons'] = [
			'tab'           => 'content',
			'group'         => 'reasons',
			'label'         => esc_html__( 'Lines under the summary', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'text',
			'fields'        => [
				'icon' => [ 'label' => esc_html__( 'Icon', 'pfh-widgets' ), 'type' => 'select', 'options' => $icons ],
				'text' => [ 'label' => esc_html__( 'Text', 'pfh-widgets' ), 'type' => 'text' ],
			],
			'default'       => $d['reasons'],
		];

		/* ---- empty ---- */

		$this->text( 'emptyTitle', 'empty', esc_html__( 'Title', 'pfh-widgets' ), $d['emptyTitle'] );
		$this->text( 'emptyText', 'empty', esc_html__( 'Text', 'pfh-widgets' ), $d['emptyText'] );
		$this->text( 'emptyButton', 'empty', esc_html__( 'Button', 'pfh-widgets' ), $d['emptyButton'] );
		$this->text( 'emptyUrl', 'empty', esc_html__( 'Button URL', 'pfh-widgets' ), '', esc_html__( 'Empty goes to the shop page.', 'pfh-widgets' ) );

		/* ---- style ---- */

		$this->colour( 'accent', esc_html__( 'Button & accent', 'pfh-widgets' ) );
		$this->colour( 'accentHover', esc_html__( 'Button hover', 'pfh-widgets' ) );
		$this->colour( 'ink', esc_html__( 'Headings & prices', 'pfh-widgets' ) );
		$this->colour( 'body', esc_html__( 'Text', 'pfh-widgets' ) );
		$this->colour( 'band', esc_html__( 'Summary background', 'pfh-widgets' ) );

		$this->number( 'maxWidth', esc_html__( 'Content width (px)', 'pfh-widgets' ), 1180, 720, 1600 );
		$this->number( 'titleSize', esc_html__( 'Title size (px)', 'pfh-widgets' ), 40, 20, 72 );
		$this->number( 'radius', esc_html__( 'Corner radius (px)', 'pfh-widgets' ), 16, 0, 32 );
		$this->number( 'paddingTop', esc_html__( 'Space above (px)', 'pfh-widgets' ), 48, 0, 200 );
		$this->number( 'paddingBottom', esc_html__( 'Space below (px)', 'pfh-widgets' ), 72, 0, 200 );
		$this->number( 'stickyTop', esc_html__( 'Summary stays this far from the top (px)', 'pfh-widgets' ), 24, 0, 240 );
	}

	/**
	 * @param string $key         Setting.
	 * @param string $group       Group.
	 * @param string $label       Label.
	 * @param string $default     Default.
	 * @param string $description Help.
	 * @param array  $required    Condition.
	 */
	private function text( $key, $group, $label, $default, $description = '', $required = [] ) {
		$this->controls[ $key ] = array_filter(
			[
				'tab'         => 'content',
				'group'       => $group,
				'label'       => $label,
				'type'        => 'text',
				'default'     => $default,
				'description' => $description,
				'required'    => $required,
			],
			static function ( $v ) {
				return '' !== $v && [] !== $v;
			}
		);

		// An empty default still has to be a declared default.
		if ( ! isset( $this->controls[ $key ]['default'] ) ) {
			$this->controls[ $key ]['default'] = '';
		}
	}

	private function colour( $key, $label ) {
		$this->controls[ $key ] = [ 'tab' => 'content', 'group' => 'style', 'label' => $label, 'type' => 'color', 'inline' => true ];
	}

	private function number( $key, $label, $default, $min, $max ) {
		$this->controls[ $key ] = [
			'tab'     => 'content',
			'group'   => 'style',
			'label'   => $label,
			'type'    => 'number',
			'min'     => $min,
			'max'     => $max,
			'inline'  => true,
			'default' => $default,
		];
	}

	public function render() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! function_exists( 'WC' ) ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-cartp pfh-cartp--empty-note"><p>' . esc_html__( 'PFH Cart needs WooCommerce.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$builder = PFH_Widgets_Helpers::is_builder_context();

		// The builder renders over AJAX, where WooCommerce has not loaded a
		// cart; load it so the designer sees their own.
		if ( null === WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		$opts = PFH_Widgets_Cart_Page::options( $this->collect() );

		if ( $builder ) {
			// With nothing in the cart there is nothing to style, so a few
			// products stand in. Only in the builder.
			$opts['preview'] = ! WC()->cart || WC()->cart->is_empty();
		} else {
			PFH_Widgets_Cart_Page::prepare();
			PFH_Widgets_Cart_Page::claim();
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-cartp', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );
		$this->set_attribute( '_root', 'data-pfh-cartp', PFH_Widgets_Cart_Page::sign( $opts ) );
		$this->set_attribute( '_root', 'data-pfh-cartp-hash', WC()->cart ? (string) WC()->cart->get_cart_hash() : '' );

		echo '<div ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-cartp__inner">';
		echo '<p class="pfh-sr-only" role="status" aria-live="polite" data-pfh-cartp-live></p>';
		echo '<div class="pfh-cartp__body" data-pfh-cartp-body>';
		echo PFH_Widgets_Cart_Page::inner( $opts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped where it is built.
		echo '</div></div></div>';
	}

	/**
	 * The panel's values, by the service's keys.
	 *
	 * @return array
	 */
	private function collect() {
		$out = [];

		foreach ( array_keys( PFH_Widgets_Cart_Page::defaults() ) as $key ) {
			if ( 'preview' === $key ) {
				continue;
			}

			if ( 'shipBar' === $key ) {
				$out[ $key ] = $this->switched_on( 'shipBar', true );
				continue;
			}

			// Untouched gives the control's default; cleared on purpose gives
			// nothing, so a line the editor emptied stays hidden.
			$out[ $key ] = $this->setting( $key, is_array( PFH_Widgets_Cart_Page::defaults()[ $key ] ) ? [] : '' );
		}

		return $out;
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-cartp-accent-set'  => PFH_Widgets_Helpers::color( $this->setting( 'accent' ) ),
				'--pfh-cartp-hover-set'   => PFH_Widgets_Helpers::color( $this->setting( 'accentHover' ) ),
				'--pfh-cartp-ink-set'     => PFH_Widgets_Helpers::color( $this->setting( 'ink' ) ),
				'--pfh-cartp-body-set'    => PFH_Widgets_Helpers::color( $this->setting( 'body' ) ),
				'--pfh-cartp-band-set'    => PFH_Widgets_Helpers::color( $this->setting( 'band' ) ),
				'--pfh-cartp-max-set'     => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1180 ) ),
				'--pfh-cartp-title-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 40 ) ),
				'--pfh-cartp-radius-set'  => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 16 ) ),
				'--pfh-cartp-pt-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'paddingTop', 48 ) ),
				'--pfh-cartp-pb-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'paddingBottom', 72 ) ),
				'--pfh-cartp-sticky-set'  => PFH_Widgets_Helpers::unit( $this->setting( 'stickyTop', 24 ) ),
			]
		);
	}
}
