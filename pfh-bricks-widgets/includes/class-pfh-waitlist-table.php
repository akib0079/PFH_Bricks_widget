<?php
/**
 * The waitlist's table in the dashboard (WooCommerce → Wachtlijst).
 *
 * Loaded only when the screen is drawn, since WordPress's list table is an
 * admin-only class.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

/**
 * The list itself.
 */
class PFH_Widgets_Waitlist_Table extends WP_List_Table {

	/**
	 * @var array<int, WC_Product|false>
	 */
	private $products = [];

	public function __construct() {
		parent::__construct(
			[
				'singular' => 'pfh-waitlist-row',
				'plural'   => 'pfh-waitlist',
				'ajax'     => false,
			]
		);
	}

	public function get_columns() {
		return [
			'cb'         => '<input type="checkbox" />',
			'email'      => __( 'Customer email', 'pfh-widgets' ),
			'product'    => __( 'Product', 'pfh-widgets' ),
			'stock'      => __( 'Stock', 'pfh-widgets' ),
			'created_at' => __( 'Date added', 'pfh-widgets' ),
			'notified'   => __( 'Mailed', 'pfh-widgets' ),
		];
	}

	protected function get_sortable_columns() {
		return [
			'email'      => [ 'email', false ],
			'created_at' => [ 'created_at', true ],
			'notified'   => [ 'notified_at', false ],
		];
	}

	protected function get_bulk_actions() {
		return [
			'notify' => __( 'Mail now (back in stock)', 'pfh-widgets' ),
			'delete' => __( 'Remove', 'pfh-widgets' ),
		];
	}

	/**
	 * @return string
	 */
	private function status() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a view filter.
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';

		return in_array( $status, [ 'waiting', 'mailed' ], true ) ? $status : '';
	}

	protected function get_views() {
		$counts  = PFH_Widgets_Waitlist::counts();
		$current = $this->status();
		$views   = [
			''        => [ __( 'All', 'pfh-widgets' ), $counts['waiting'] + $counts['mailed'] ],
			'waiting' => [ __( 'Waiting', 'pfh-widgets' ), $counts['waiting'] ],
			'mailed'  => [ __( 'Mailed', 'pfh-widgets' ), $counts['mailed'] ],
		];
		$out     = [];

		foreach ( $views as $key => $view ) {
			$out[ $key ? $key : 'all' ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( [ 'page' => PFH_Widgets_Waitlist_Admin::PAGE, 'status' => $key ? $key : false ], admin_url( 'admin.php' ) ) ),
				$key === $current ? ' class="current" aria-current="page"' : '',
				esc_html( $view[0] ),
				(int) $view[1]
			);
		}

		return $out;
	}

	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		global $wpdb;

		$table = PFH_Widgets_Waitlist::table();
		$ids   = $wpdb->get_col( "SELECT DISTINCT product_id FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a filter.
		$chosen = isset( $_GET['product'] ) ? absint( $_GET['product'] ) : 0;
		$names  = [];

		foreach ( (array) $ids as $id ) {
			$product = $this->product( (int) $id );
			$names[ (int) $id ] = $product ? $product->get_name() : '#' . (int) $id;
		}

		natcasesort( $names );

		echo '<div class="alignleft actions"><select name="product"><option value="">' . esc_html__( 'All products', 'pfh-widgets' ) . '</option>';

		foreach ( $names as $id => $name ) {
			printf( '<option value="%d"%s>%s</option>', (int) $id, selected( $chosen, $id, false ), esc_html( $name ) );
		}

		echo '</select>';
		submit_button( __( 'Filter', 'pfh-widgets' ), '', 'filter_action', false );
		echo '</div>';
	}

	/**
	 * @param int $id Product.
	 * @return WC_Product|false
	 */
	private function product( $id ) {
		if ( ! array_key_exists( $id, $this->products ) ) {
			$this->products[ $id ] = wc_get_product( $id );
		}

		return $this->products[ $id ];
	}

	public function prepare_items() {
		global $wpdb;

		$table    = PFH_Widgets_Waitlist::table();
		$per_page = 25;
		$where    = [ '1=1' ];
		$args     = [];

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- list filters.
		$search  = isset( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '';
		$product = isset( $_GET['product'] ) ? absint( $_GET['product'] ) : 0;
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order   = isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'ASC' : 'DESC';
		// phpcs:enable

		if ( 'waiting' === $this->status() ) {
			$where[] = 'notified_at IS NULL';
		} elseif ( 'mailed' === $this->status() ) {
			$where[] = 'notified_at IS NOT NULL';
		}

		if ( $product ) {
			$where[] = 'product_id = %d';
			$args[]  = $product;
		}

		if ( '' !== $search ) {
			$like  = '%' . $wpdb->esc_like( $search ) . '%';
			$found = get_posts(
				[
					'post_type'      => [ 'product', 'product_variation' ],
					'post_status'    => 'any',
					's'              => $search,
					'fields'         => 'ids',
					'posts_per_page' => 200,
				]
			);

			$where[] = $found ? '( email LIKE %s OR product_id IN (' . implode( ',', array_map( 'absint', $found ) ) . ') )' : 'email LIKE %s';
			$args[]  = $like;
		}

		$orderby = in_array( $orderby, [ 'email', 'created_at', 'notified_at' ], true ) ? $orderby : 'created_at';
		$sql     = implode( ' AND ', $where );
		$page    = $this->get_pagenum();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders filled by prepare(); table name, order and integers are not user text.
		$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$sql}", $args ) : "SELECT COUNT(*) FROM {$table} WHERE {$sql}" );
		$query = "SELECT * FROM {$table} WHERE {$sql} ORDER BY {$orderby} {$order}, id DESC LIMIT %d OFFSET %d";

		$this->items = $wpdb->get_results( $wpdb->prepare( $query, array_merge( $args, [ $per_page, ( $page - 1 ) * $per_page ] ) ) );
		// phpcs:enable

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns(), 'email' ];

		$this->set_pagination_args(
			[
				'total_items' => $total,
				'per_page'    => $per_page,
			]
		);
	}

	public function no_items() {
		esc_html_e( 'Nobody is on the waitlist.', 'pfh-widgets' );
	}

	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="row[]" value="%d" />', (int) $item->id );
	}

	protected function column_email( $item ) {
		$base    = [ 'page' => PFH_Widgets_Waitlist_Admin::PAGE, 'row[]' => (int) $item->id, '_wpnonce' => wp_create_nonce( 'bulk-pfh-waitlist' ) ];
		$actions = [];

		if ( ! $item->notified_at ) {
			$actions['notify'] = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( $base + [ 'action' => 'notify' ], admin_url( 'admin.php' ) ) ), esc_html__( 'Mail now', 'pfh-widgets' ) );
		}

		$actions['delete'] = sprintf(
			'<a href="%s" class="submitdelete" onclick="return confirm(%s)">%s</a>',
			esc_url( add_query_arg( $base + [ 'action' => 'delete' ], admin_url( 'admin.php' ) ) ),
			esc_attr( wp_json_encode( __( 'Remove this sign-up?', 'pfh-widgets' ) ) ),
			esc_html__( 'Remove', 'pfh-widgets' )
		);

		$user = $item->user_id ? get_userdata( (int) $item->user_id ) : false;
		$who  = $user ? sprintf( ' <span class="description">(<a href="%s">%s</a>)</span>', esc_url( get_edit_user_link( $user->ID ) ), esc_html( $user->display_name ) ) : '';

		return '<strong>' . esc_html( $item->email ) . '</strong>' . $who . $this->row_actions( $actions );
	}

	protected function column_product( $item ) {
		$product = $this->product( (int) $item->product_id );

		if ( ! $product ) {
			/* translators: %d: product ID. */
			return esc_html( sprintf( __( 'Product #%d (no longer in the shop)', 'pfh-widgets' ), (int) $item->product_id ) );
		}

		$edit = get_edit_post_link( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() );

		return sprintf(
			'<a href="%s">%s</a> <a href="%s" target="_blank" rel="noopener" class="description">%s</a>',
			esc_url( (string) $edit ),
			esc_html( $product->get_name() ),
			esc_url( $product->get_permalink() ),
			esc_html__( 'view', 'pfh-widgets' )
		);
	}

	protected function column_stock( $item ) {
		$product = $this->product( (int) $item->product_id );

		if ( ! $product ) {
			return '–';
		}

		return $product->is_in_stock()
			? '<mark class="instock" style="background:none;color:#7ad03a;font-weight:600">' . esc_html__( 'In stock', 'pfh-widgets' ) . '</mark>'
			: '<mark class="outofstock" style="background:none;color:#a44;font-weight:600">' . esc_html__( 'Sold out', 'pfh-widgets' ) . '</mark>';
	}

	protected function column_created_at( $item ) {
		return esc_html( mysql2date( 'j F Y H:i', $item->created_at ) );
	}

	protected function column_notified( $item ) {
		if ( ! $item->notified_at ) {
			return '<span aria-hidden="true">✗</span> <span class="description">' . esc_html__( 'Not yet', 'pfh-widgets' ) . '</span>';
		}

		return '<span aria-hidden="true" style="color:#7ad03a">✓</span> ' . esc_html( mysql2date( 'j F Y', $item->notified_at ) );
	}
}
