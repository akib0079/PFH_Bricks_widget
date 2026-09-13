<?php
/**
 * AJAX endpoints: live search + cart drawer updates.
 *
 * Both endpoints are available to logged-out visitors and are nonce guarded.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Ajax {

	const NONCE = 'pfh_widgets_nonce';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_pfh_search', [ __CLASS__, 'search' ] );
		add_action( 'wp_ajax_nopriv_pfh_search', [ __CLASS__, 'search' ] );

		add_action( 'wp_ajax_pfh_cart', [ __CLASS__, 'cart' ] );
		add_action( 'wp_ajax_nopriv_pfh_cart', [ __CLASS__, 'cart' ] );
	}

	/**
	 * Pull the label map out of the request and sanitise every value.
	 *
	 * @return array<string, string>
	 */
	private static function request_labels() {
		if ( empty( $_POST['labels'] ) || ! is_array( $_POST['labels'] ) ) {
			return [];
		}

		$raw    = wp_unslash( $_POST['labels'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$labels = [];

		foreach ( (array) $raw as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$key = sanitize_key( $key );

			$labels[ $key ] = 'shopUrl' === $key || 'shopurl' === $key
				? esc_url_raw( (string) $value )
				: sanitize_text_field( (string) $value );
		}

		// sanitize_key() lowercases, so restore the camelCase key we expect.
		if ( isset( $labels['shopurl'] ) ) {
			$labels['shopUrl'] = $labels['shopurl'];
			unset( $labels['shopurl'] );
		}

		return $labels;
	}

	/**
	 * Live search results for the header search popup.
	 */
	public static function search() {
		check_ajax_referer( self::NONCE, 'nonce' );

		$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

		if ( mb_strlen( $term ) < 2 ) {
			wp_send_json_success(
				[
					'html'  => '',
					'count' => 0,
				]
			);
		}

		$requested = isset( $_POST['postType'] ) ? sanitize_key( wp_unslash( $_POST['postType'] ) ) : 'any';
		$limit     = isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : 6;
		$limit     = min( max( $limit, 1 ), 20 );

		$post_type = 'any';

		if ( 'product' === $requested && post_type_exists( 'product' ) ) {
			$post_type = 'product';
		} elseif ( 'post' === $requested ) {
			$post_type = 'post';
		}

		$args = [
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			's'                   => $term,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		];

		if ( 'product' === $post_type ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'exclude-from-search',
					'operator' => 'NOT IN',
				],
			];
		}

		/**
		 * Filter the header live-search query arguments.
		 *
		 * @param array  $args WP_Query args.
		 * @param string $term Search term.
		 */
		$args = apply_filters( 'pfh_widgets_search_args', $args, $term );

		$query = new WP_Query( $args );

		ob_start();

		if ( $query->have_posts() ) {
			echo '<ul class="pfh-search__results">';

			while ( $query->have_posts() ) {
				$query->the_post();

				$id    = get_the_ID();
				$thumb = get_the_post_thumbnail( $id, 'thumbnail', [ 'class' => 'pfh-search__thumb-img', 'loading' => 'lazy' ] );
				$price = '';

				if ( 'product' === get_post_type( $id ) && function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( $id );

					if ( $product ) {
						$price = $product->get_price_html();
					}
				}

				echo '<li class="pfh-search__result">';
				echo '<a class="pfh-search__result-link" href="' . esc_url( get_permalink( $id ) ) . '">';
				echo '<span class="pfh-search__thumb">' . ( $thumb ? wp_kses_post( $thumb ) : '' ) . '</span>';
				echo '<span class="pfh-search__result-body">';
				echo '<span class="pfh-search__result-title">' . esc_html( get_the_title( $id ) ) . '</span>';

				if ( $price ) {
					echo '<span class="pfh-search__result-price">' . wp_kses_post( $price ) . '</span>';
				}

				echo '</span>';
				echo '</a>';
				echo '</li>';
			}

			echo '</ul>';
		}

		wp_reset_postdata();

		wp_send_json_success(
			[
				'html'  => (string) ob_get_clean(),
				'count' => (int) $query->post_count,
			]
		);
	}

	/**
	 * Update / remove a cart line and return fresh drawer markup.
	 */
	public static function cart() {
		check_ajax_referer( self::NONCE, 'nonce' );

		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! WC()->cart ) {
			wp_send_json_error( [ 'message' => esc_html__( 'WooCommerce is niet actief.', 'pfh-widgets' ) ], 400 );
		}

		$command = isset( $_POST['command'] ) ? sanitize_key( wp_unslash( $_POST['command'] ) ) : 'refresh';
		$key     = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';

		if ( 'remove' === $command && $key ) {
			WC()->cart->remove_cart_item( $key );
		} elseif ( 'set' === $command && $key ) {
			$quantity = isset( $_POST['quantity'] ) ? (float) wp_unslash( $_POST['quantity'] ) : 0;

			if ( $quantity <= 0 ) {
				WC()->cart->remove_cart_item( $key );
			} else {
				WC()->cart->set_quantity( $key, $quantity, false );
			}
		}

		WC()->cart->calculate_totals();
		WC()->cart->maybe_set_cart_cookies();

		$labels = self::request_labels();

		wp_send_json_success(
			[
				'body'  => PFH_Widgets_Cart::render_body( $labels ),
				'foot'  => PFH_Widgets_Cart::render_foot( $labels ),
				'count' => PFH_Widgets_Cart::count(),
			]
		);
	}
}
