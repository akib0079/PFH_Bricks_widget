<?php
/**
 * A wishlist: a heart on every product, a list in My Account.
 *
 * The old shop had one (it came with its theme), and the client missed it on
 * the new one — on every product, on the category pages, and in the account
 * (feedback, 2026-09-28). This is the small version of it:
 *
 *   Anyone can save a product. A visitor's list lives in a cookie, so it works
 *   on cached pages and costs the server nothing. A signed-in customer's list
 *   is kept on their account too, so it follows them to another device; the
 *   cookie they had before signing in is folded into it when they do.
 *
 *   The heart is a button carrying the product id. The script marks the ones
 *   on the list and toggles them, so the same markup works in a slider, in a
 *   grid that was filtered without a reload, and on the product page.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Wishlist {

	const COOKIE = 'pfh_wishlist';
	const META   = '_pfh_wishlist';
	const ACTION = 'pfh_wishlist';

	/** A list is a convenience, not an archive. */
	const MAX = 60;

	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION, [ __CLASS__, 'ajax' ] );
		add_action( 'wp_login', [ __CLASS__, 'merge_on_login' ], 10, 2 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ], 20 );
		add_shortcode( 'pfh_wishlist', [ __CLASS__, 'shortcode' ] );
	}

	/**
	 * @return bool Whether hearts are drawn at all.
	 */
	public static function enabled() {
		/**
		 * Filter whether the wishlist is on.
		 *
		 * @param bool $on Default true.
		 */
		return (bool) apply_filters( 'pfh_widgets_wishlist', true ) && PFH_Widgets_Helpers::has_woocommerce();
	}

	public static function assets() {
		if ( is_admin() || ! self::enabled() ) {
			return;
		}

		wp_enqueue_style( 'pfh-wishlist', PFH_WIDGETS_URL . 'assets/css/pfh-wishlist.css', [], PFH_WIDGETS_VERSION );
		wp_enqueue_script( 'pfh-wishlist', PFH_WIDGETS_URL . 'assets/js/pfh-wishlist.js', [], PFH_WIDGETS_VERSION, true );

		$config = [
			'cookie'   => self::COOKIE,
			'max'      => self::MAX,
			'loggedIn' => is_user_logged_in(),
			'labelOn'  => __( 'Uit je verlanglijst halen', 'pfh-widgets' ),
			'labelOff' => __( 'Bewaar in je verlanglijst', 'pfh-widgets' ),
		];

		// Only a signed-in customer talks to the server, and only their pages
		// are personal; a visitor's page can be cached with no nonce in it.
		if ( is_user_logged_in() ) {
			$config['ajaxUrl'] = admin_url( 'admin-ajax.php' );
			$config['nonce']   = wp_create_nonce( self::ACTION );
			$config['ids']     = self::ids();
		}

		wp_add_inline_script( 'pfh-wishlist', 'window.pfhWishlist = ' . wp_json_encode( $config ) . ';', 'before' );
	}

	/**
	 * The ids on the current visitor's list.
	 *
	 * @return int[]
	 */
	public static function ids() {
		$ids = self::from_cookie();

		if ( is_user_logged_in() ) {
			$ids = array_merge( self::from_user( get_current_user_id() ), $ids );
		}

		return self::clean( $ids );
	}

	/**
	 * @return int[]
	 */
	private static function from_cookie() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cast to ints in clean().
		$raw = isset( $_COOKIE[ self::COOKIE ] ) ? (string) wp_unslash( $_COOKIE[ self::COOKIE ] ) : '';

		return self::clean( explode( '.', $raw ) );
	}

	/**
	 * @param int $user_id User.
	 * @return int[]
	 */
	private static function from_user( $user_id ) {
		$stored = get_user_meta( (int) $user_id, self::META, true );

		return self::clean( is_array( $stored ) ? $stored : [] );
	}

	/**
	 * Unique, positive, at most MAX, newest first as given.
	 *
	 * @param array $ids Ids.
	 * @return int[]
	 */
	private static function clean( array $ids ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		return array_slice( $ids, 0, self::MAX );
	}

	/**
	 * A visitor's cookie list becomes part of their account when they sign in.
	 *
	 * @param string  $login Login name.
	 * @param WP_User $user  User.
	 */
	public static function merge_on_login( $login, $user ) {
		$cookie = self::from_cookie();

		if ( ! $cookie || ! $user instanceof WP_User ) {
			return;
		}

		update_user_meta( $user->ID, self::META, self::clean( array_merge( $cookie, self::from_user( $user->ID ) ) ) );
	}

	/**
	 * Save or remove one product, for a signed-in customer.
	 */
	public static function ajax() {
		check_ajax_referer( self::ACTION, 'nonce' );

		$user = get_current_user_id();
		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$on   = ! empty( $_POST['on'] );

		if ( ! $user || ! $id || 'product' !== get_post_type( $id ) ) {
			wp_send_json_error( null, 400 );
		}

		$ids = array_values( array_diff( self::from_user( $user ), [ $id ] ) );

		if ( $on ) {
			array_unshift( $ids, $id );
		}

		$ids = self::clean( $ids );

		update_user_meta( $user, self::META, $ids );

		wp_send_json_success( [ 'ids' => $ids ] );
	}

	/**
	 * The heart for one product.
	 *
	 * @param int    $product_id Product.
	 * @param string $class      Extra class.
	 * @return string
	 */
	public static function button( $product_id, $class = '' ) {
		if ( ! self::enabled() || ! $product_id ) {
			return '';
		}

		$on = in_array( (int) $product_id, is_user_logged_in() ? self::ids() : [], true );

		return sprintf(
			'<button type="button" class="pfh-wish%s%s" data-pfh-wish="%d" aria-pressed="%s" aria-label="%s"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.3s-7.3-4.4-9.2-9.1C1.5 7.9 3.6 4.5 7 4.3c2-.1 3.6 1 5 2.8 1.4-1.8 3-2.9 5-2.8 3.4.2 5.5 3.6 4.2 6.9-1.9 4.7-9.2 9.1-9.2 9.1Z"/></svg></button>',
			$class ? ' ' . esc_attr( $class ) : '',
			$on ? ' is-on' : '',
			(int) $product_id,
			$on ? 'true' : 'false',
			esc_attr( $on ? __( 'Uit je verlanglijst halen', 'pfh-widgets' ) : __( 'Bewaar in je verlanglijst', 'pfh-widgets' ) )
		);
	}

	/**
	 * The list itself: every saved product that can still be bought.
	 *
	 * @return string
	 */
	public static function render_list() {
		$products = [];

		foreach ( self::ids() as $id ) {
			$product = wc_get_product( $id );

			if ( $product && 'publish' === $product->get_status() && $product->is_visible() ) {
				$products[] = $product;
			}
		}

		$out = '<div class="pfh-wishlist" data-pfh-wishlist>';

		$out .= sprintf(
			'<p class="pfh-wishlist__empty"%s>%s</p>',
			$products ? ' hidden' : '',
			esc_html__( 'Je verlanglijst is nog leeg. Tik op het hartje bij een product om het te bewaren.', 'pfh-widgets' )
		);

		if ( $products ) {
			$out .= '<ul class="pfh-wishlist__list">';

			foreach ( $products as $product ) {
				$image = (int) $product->get_image_id();

				$out .= sprintf(
					'<li class="pfh-wishlist__item" data-pfh-wish-item="%1$d"><a class="pfh-wishlist__media" href="%2$s">%3$s</a><div class="pfh-wishlist__body"><a class="pfh-wishlist__name" href="%2$s">%4$s</a><span class="pfh-wishlist__price">%5$s</span></div>%6$s</li>',
					(int) $product->get_id(),
					esc_url( $product->get_permalink() ),
					$image ? wp_get_attachment_image( $image, 'woocommerce_thumbnail', false, [ 'class' => 'pfh-wishlist__img', 'loading' => 'lazy' ] ) : '',
					esc_html( $product->get_name() ),
					wp_kses_post( $product->get_price_html() ),
					self::button( $product->get_id(), 'pfh-wish--inline' )
				);
			}

			$out .= '</ul>';
		}

		return $out . '</div>';
	}

	/**
	 * [pfh_wishlist] — the list on a page of its own.
	 *
	 * @return string
	 */
	public static function shortcode() {
		return self::enabled() ? self::render_list() : '';
	}
}
