<?php
/**
 * The account page's server side.
 *
 * Two jobs: hand one order back over AJAX so the list can open in place, and
 * keep the extra registration fields that WooCommerce's own handler does not
 * know about.
 *
 * The ownership check in order() is the important line in this file. An order
 * id is a small number, and anyone can type a different one.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Account {

	/**
	 * Statuses, in the order a parcel passes through them.
	 */
	const FLOW = [ 'placed', 'paid', 'shipped', 'delivered' ];

	public static function init() {
		add_action( 'wp_ajax_pfh_order', [ __CLASS__, 'order' ] );

		// A name typed into the register form is worth keeping.
		add_action( 'woocommerce_created_customer', [ __CLASS__, 'save_name' ], 10, 1 );
	}

	/**
	 * One order, rendered, for the panel to drop in.
	 */
	public static function order() {
		check_ajax_referer( PFH_Widgets_Ajax::NONCE, 'nonce' );

		$id = isset( $_POST['order'] ) ? absint( $_POST['order'] ) : 0;

		if ( ! $id || ! is_user_logged_in() || ! function_exists( 'wc_get_order' ) ) {
			wp_send_json_error( [ 'message' => __( 'That order could not be opened.', 'pfh-widgets' ) ], 400 );
		}

		$order = wc_get_order( $id );

		/*
		 * The whole point of this endpoint: an order belongs to one customer,
		 * and the id is guessable. current_user_can( 'view_order' ) is
		 * WooCommerce's own check and covers shop managers too.
		 */
		if ( ! $order || ! current_user_can( 'view_order', $id ) ) {
			wp_send_json_error( [ 'message' => __( 'That order could not be opened.', 'pfh-widgets' ) ], 403 );
		}

		if ( ! class_exists( 'PFH_Element_Account' ) && ! class_exists( '\Bricks\Element' ) ) {
			wp_send_json_error( [ 'message' => __( 'Account details are temporarily unavailable.', 'pfh-widgets' ) ], 503 );
		}

		if ( ! class_exists( 'PFH_Element_Account' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
			require_once PFH_WIDGETS_DIR . 'elements/class-pfh-element-account.php';
		}

		wp_send_json_success( [ 'html' => PFH_Element_Account::order_detail( $order ) ] );
	}

	/**
	 * Keep the first and last name the register form asked for.
	 *
	 * @param int $customer_id New customer.
	 */
	public static function save_name( $customer_id ) {
		// WooCommerce has verified its own nonce before firing this.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$first = isset( $_POST['pfh_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['pfh_first_name'] ) ) : '';
		$last  = isset( $_POST['pfh_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['pfh_last_name'] ) ) : '';
		// phpcs:enable

		if ( '' === $first && '' === $last ) {
			return;
		}

		wp_update_user(
			[
				'ID'         => $customer_id,
				'first_name' => $first,
				'last_name'  => $last,
			]
		);

		update_user_meta( $customer_id, 'billing_first_name', $first );
		update_user_meta( $customer_id, 'billing_last_name', $last );
	}

	/**
	 * Where an order has got to, and when each step happened.
	 *
	 * WooCommerce has no "shipped" of its own, so completed is read as gone —
	 * which is how every Dutch shop uses it — and a tracking number, when one
	 * is there, is taken as proof it is on its way.
	 *
	 * @param WC_Order $order Order.
	 * @return array{steps: array<int, array{key:string, label:string, when:string, state:string}>, done: bool}
	 */
	public static function progress( $order ) {
		$status  = $order->get_status();
		$created = $order->get_date_created();
		$paid    = $order->get_date_paid();
		$done    = $order->get_date_completed();
		$trace   = self::tracking( $order );

		$labels = [
			'placed'    => __( 'Besteld', 'pfh-widgets' ),
			'paid'      => __( 'Betaald', 'pfh-widgets' ),
			'shipped'   => __( 'Verzonden', 'pfh-widgets' ),
			'delivered' => __( 'Bezorgd', 'pfh-widgets' ),
		];

		$at = [
			'placed'    => $created,
			'paid'      => $paid,
			'shipped'   => $trace['date'] ? $trace['date'] : ( 'completed' === $status ? $done : null ),
			'delivered' => 'completed' === $status ? $done : null,
		];

		/*
		 * How many steps have actually happened. Completed is the end of the
		 * road — every step behind it, and nothing still in progress — which
		 * is the one case where counting "the step it is on" would leave the
		 * parcel forever arriving.
		 */
		$done = 1;

		if ( $paid || in_array( $status, [ 'processing', 'completed' ], true ) ) {
			$done = 2;
		}

		if ( 'completed' === $status ) {
			$done = count( self::FLOW );
		}

		$steps = [];
		$i     = 0;

		foreach ( self::FLOW as $key ) {
			$i++;

			$steps[] = [
				'key'   => $key,
				'label' => $labels[ $key ],
				'when'  => $at[ $key ] ? wc_format_datetime( $at[ $key ], 'j M' ) : '',
				'state' => $i <= $done ? 'is-done' : ( $i === $done + 1 ? 'is-now' : '' ),
			];
		}

		/**
		 * Filter the steps shown on an order.
		 *
		 * @param array    $steps Steps.
		 * @param WC_Order $order Order.
		 */
		return [
			'steps'   => (array) apply_filters( 'pfh_account_order_steps', $steps, $order ),
			'stalled' => in_array( $status, [ 'cancelled', 'failed', 'refunded' ], true ),
		];
	}

	/**
	 * A tracking number, from wherever the shop keeps one.
	 *
	 * @param WC_Order $order Order.
	 * @return array{number:string, carrier:string, url:string, date:mixed}
	 */
	public static function tracking( $order ) {
		$out = [ 'number' => '', 'carrier' => '', 'url' => '', 'date' => null ];

		// WooCommerce Shipment Tracking, which is what most shops end up on.
		$items = $order->get_meta( '_wc_shipment_tracking_items' );

		if ( is_array( $items ) && $items ) {
			$first = reset( $items );

			$out['number']  = isset( $first['tracking_number'] ) ? (string) $first['tracking_number'] : '';
			$out['carrier'] = isset( $first['tracking_provider'] ) ? (string) $first['tracking_provider'] : '';
			$out['url']     = isset( $first['custom_tracking_link'] ) ? (string) $first['custom_tracking_link'] : '';
		}

		// The plain keys a hand-rolled setup tends to use.
		if ( '' === $out['number'] ) {
			$out['number']  = (string) $order->get_meta( '_tracking_number' );
			$out['carrier'] = (string) $order->get_meta( '_tracking_provider' );
			$out['url']     = (string) $order->get_meta( '_tracking_url' );
		}

		/**
		 * Filter an order's tracking details, for a shop that keeps them
		 * somewhere else entirely.
		 *
		 * @param array    $tracking ['number','carrier','url','date']
		 * @param WC_Order $order    Order.
		 */
		return (array) apply_filters( 'pfh_account_order_tracking', $out, $order );
	}
}
