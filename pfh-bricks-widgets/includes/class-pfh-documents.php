<?php
/**
 * Order documents: invoice and packing slip.
 *
 * Replaces PDF Invoices & Packing Slips. Totals are read back from
 * WooCommerce's own get_order_item_totals() rather than recalculated, so
 * whatever the customer saw at checkout — the staffel discount, loyalty
 * redemption, shipping, VAT — is what the invoice says. Recomputing those is
 * how invoices end up disagreeing with the order.
 *
 * Documents are rendered on request and streamed. Nothing is written to
 * wp-content, so there is no directory of customer invoices sitting under a
 * guessable URL.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Documents extends PFH_Settings_Module {

	const OPTION = 'pfh_docs';

	/**
	 * Option holding the invoice counter.
	 */
	const SEQUENCE = 'pfh_invoice_seq';

	/**
	 * Order meta keys.
	 */
	const META_NUMBER = '_pfh_invoice_number';
	const META_SEQ    = '_pfh_invoice_seq';
	const META_DATE   = '_pfh_invoice_date';

	public static function init() {
		PFH_Widgets_Settings::register( 'documents', __( 'Invoices', 'pfh-widgets' ), __CLASS__ );

		if ( ! PFH_Widgets_Helpers::has_woocommerce() ) {
			return;
		}

		add_action( 'admin_post_pfh_document', [ __CLASS__, 'serve' ] );
		add_action( 'add_meta_boxes', [ __CLASS__, 'metabox' ] );

		add_filter( 'woocommerce_email_attachments', [ __CLASS__, 'attach' ], 10, 3 );
		add_filter( 'woocommerce_my_account_my_orders_actions', [ __CLASS__, 'account_action' ], 10, 2 );

		// Bulk download from the orders list, for both storage backends.
		foreach ( [ 'bulk_actions-edit-shop_order', 'bulk_actions-woocommerce_page_wc-orders' ] as $hook ) {
			add_filter( $hook, [ __CLASS__, 'bulk_action' ] );
		}

		foreach ( [ 'handle_bulk_actions-edit-shop_order', 'handle_bulk_actions-woocommerce_page_wc-orders' ] as $hook ) {
			add_filter( $hook, [ __CLASS__, 'handle_bulk' ], 10, 3 );
		}

		$trigger = self::get( 'number_on', 'manual' );

		if ( 'manual' !== $trigger ) {
			add_action( 'woocommerce_order_status_' . $trigger, [ __CLASS__, 'assign_on_status' ] );
		}
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	/**
	 * @return array
	 */
	public static function fields() {
		return [
			'shop' => [
				'label'  => __( 'Sender', 'pfh-widgets' ),
				'desc'   => __( 'Printed at the top of every document and in the footer.', 'pfh-widgets' ),
				'fields' => [
					'logo' => [
						'type'  => 'media',
						'label' => __( 'Logo', 'pfh-widgets' ),
						'desc'  => __( 'Scaled to fit 150 × 55 pt. A PNG with transparency is flattened onto white.', 'pfh-widgets' ),
					],
					'company_name' => [
						'type'    => 'text',
						'label'   => __( 'Company name', 'pfh-widgets' ),
						'default' => 'Products for Home',
					],
					'address' => [
						'type'    => 'textarea',
						'rows'    => 4,
						'label'   => __( 'Address', 'pfh-widgets' ),
						'desc'    => __( 'One line per line.', 'pfh-widgets' ),
						'default' => '',
					],
					'vat' => [
						'type'    => 'text',
						'label'   => __( 'VAT number', 'pfh-widgets' ),
						'default' => '',
					],
					'coc' => [
						'type'    => 'text',
						'label'   => __( 'KvK number', 'pfh-widgets' ),
						'default' => '',
					],
					'iban' => [
						'type'    => 'text',
						'label'   => __( 'IBAN', 'pfh-widgets' ),
						'default' => '',
					],
					'contact' => [
						'type'    => 'text',
						'label'   => __( 'Email / phone', 'pfh-widgets' ),
						'default' => '',
					],
					'footer_note' => [
						'type'    => 'textarea',
						'rows'    => 3,
						'label'   => __( 'Footer note', 'pfh-widgets' ),
						'default' => '',
					],
				],
			],

			'numbering' => [
				'label'  => __( 'Invoice numbers', 'pfh-widgets' ),
				'desc'   => __( 'A number is allocated once and then never changes, which is what the Belastingdienst expects of a sequential invoice series. Regenerating a PDF reuses the number already on the order.', 'pfh-widgets' ),
				'fields' => [
					'number_on' => [
						'type'    => 'select',
						'label'   => __( 'Allocate the number', 'pfh-widgets' ),
						'default' => 'manual',
						'choices' => [
							'manual'     => __( 'When the invoice is first created', 'pfh-widgets' ),
							'processing' => __( 'When the order goes to processing', 'pfh-widgets' ),
							'completed'  => __( 'When the order is completed', 'pfh-widgets' ),
						],
					],
					'prefix' => [
						'type'    => 'text',
						'label'   => __( 'Prefix', 'pfh-widgets' ),
						'default' => '',
						'desc'    => __( 'Supports <code>[year]</code> and <code>[month]</code>, e.g. <code>PFH-[year]-</code>.', 'pfh-widgets' ),
					],
					'suffix' => [
						'type'    => 'text',
						'label'   => __( 'Suffix', 'pfh-widgets' ),
						'default' => '',
					],
					'padding' => [
						'type'    => 'number',
						'label'   => __( 'Pad to', 'pfh-widgets' ),
						'suffix'  => __( 'digits', 'pfh-widgets' ),
						'min'     => 1,
						'max'     => 12,
						'default' => 5,
					],
					'start_number' => [
						'type'    => 'number',
						'label'   => __( 'Start at', 'pfh-widgets' ),
						'min'     => 1,
						'max'     => 100000000,
						'default' => 1,
						'desc'    => __( 'Only used for the very first invoice. Set this to continue the existing series rather than restarting it — check the last number the old plugin issued before going live.', 'pfh-widgets' ),
					],
				],
			],

			'output' => [
				'label'  => __( 'Documents', 'pfh-widgets' ),
				'fields' => [
					'prices_incl_tax' => [
						'type'     => 'checkbox',
						'label'    => __( 'Prices', 'pfh-widgets' ),
						'cb_label' => __( 'Show line prices including VAT', 'pfh-widgets' ),
						'default'  => true,
					],
					'show_sku' => [
						'type'     => 'checkbox',
						'label'    => __( 'SKU column', 'pfh-widgets' ),
						'cb_label' => __( 'Print the SKU next to each line', 'pfh-widgets' ),
						'default'  => true,
					],
					'customer_download' => [
						'type'     => 'checkbox',
						'label'    => __( 'My account', 'pfh-widgets' ),
						'cb_label' => __( 'Let customers download their own invoice', 'pfh-widgets' ),
						'desc'     => __( 'Adds an Invoice button to the orders list. Only appears once a number has been allocated.', 'pfh-widgets' ),
						'default'  => true,
					],
					'attach_processing' => [
						'type'     => 'checkbox',
						'label'    => __( 'Attach to', 'pfh-widgets' ),
						'cb_label' => __( 'Processing order email', 'pfh-widgets' ),
						'default'  => false,
					],
					'attach_completed' => [
						'type'     => 'checkbox',
						'label'    => '',
						'cb_label' => __( 'Completed order email', 'pfh-widgets' ),
						'default'  => true,
					],
					'attach_invoice' => [
						'type'     => 'checkbox',
						'label'    => '',
						'cb_label' => __( 'Customer invoice / order details email', 'pfh-widgets' ),
						'default'  => true,
					],
					'accent' => [
						'type'    => 'color',
						'label'   => __( 'Accent colour', 'pfh-widgets' ),
						'default' => '#3F7E7C',
					],
				],
			],

			'labels' => [
				'label'  => __( 'Wording', 'pfh-widgets' ),
				'desc'   => __( 'PDF text is generated, not rendered HTML, so TranslatePress cannot reach it — these are the strings the documents print.', 'pfh-widgets' ),
				'fields' => [
					'title_invoice'  => [
						'type'    => 'text',
						'label'   => __( 'Invoice title', 'pfh-widgets' ),
						'default' => 'Factuur',
					],
					'title_packing'  => [
						'type'    => 'text',
						'label'   => __( 'Packing slip title', 'pfh-widgets' ),
						'default' => 'Pakbon',
					],
					'label_invoice_no' => [
						'type'    => 'text',
						'label'   => __( 'Invoice number', 'pfh-widgets' ),
						'default' => 'Factuurnummer',
					],
					'label_invoice_date' => [
						'type'    => 'text',
						'label'   => __( 'Invoice date', 'pfh-widgets' ),
						'default' => 'Factuurdatum',
					],
					'label_order_no' => [
						'type'    => 'text',
						'label'   => __( 'Order number', 'pfh-widgets' ),
						'default' => 'Ordernummer',
					],
					'label_order_date' => [
						'type'    => 'text',
						'label'   => __( 'Order date', 'pfh-widgets' ),
						'default' => 'Orderdatum',
					],
					'label_payment' => [
						'type'    => 'text',
						'label'   => __( 'Payment method', 'pfh-widgets' ),
						'default' => 'Betaalmethode',
					],
					'label_billing' => [
						'type'    => 'text',
						'label'   => __( 'Billing address', 'pfh-widgets' ),
						'default' => 'Factuuradres',
					],
					'label_shipping' => [
						'type'    => 'text',
						'label'   => __( 'Shipping address', 'pfh-widgets' ),
						'default' => 'Verzendadres',
					],
					'label_description' => [
						'type'    => 'text',
						'label'   => __( 'Description column', 'pfh-widgets' ),
						'default' => 'Omschrijving',
					],
					'label_sku' => [
						'type'    => 'text',
						'label'   => __( 'SKU column', 'pfh-widgets' ),
						'default' => 'SKU',
					],
					'label_qty' => [
						'type'    => 'text',
						'label'   => __( 'Quantity column', 'pfh-widgets' ),
						'default' => 'Aantal',
					],
					'label_price' => [
						'type'    => 'text',
						'label'   => __( 'Price column', 'pfh-widgets' ),
						'default' => 'Prijs',
					],
					'label_total' => [
						'type'    => 'text',
						'label'   => __( 'Total column', 'pfh-widgets' ),
						'default' => 'Totaal',
					],
					'label_page' => [
						'type'    => 'text',
						'label'   => __( 'Page x of y', 'pfh-widgets' ),
						'default' => 'Pagina %1$d van %2$d',
						'desc'    => __( 'Keep <code>%1$d</code> and <code>%2$d</code> in place.', 'pfh-widgets' ),
					],
				],
			],
		];
	}

	/* ---------------------------------------------------------------------
	 * Numbering
	 * ------------------------------------------------------------------ */

	/**
	 * The invoice number for an order, allocating one if needed.
	 *
	 * @param \WC_Order $order  Order.
	 * @param bool      $create Allocate when missing.
	 * @return string Empty when none exists and none was requested.
	 */
	public static function number( $order, $create = true ) {
		$existing = (string) $order->get_meta( self::META_NUMBER );

		if ( '' !== $existing ) {
			return $existing;
		}

		if ( ! $create ) {
			return '';
		}

		$sequence = self::next_sequence();

		$number = self::tokens( (string) self::get( 'prefix', '' ) )
			. str_pad( (string) $sequence, max( 1, (int) self::get( 'padding', 5 ) ), '0', STR_PAD_LEFT )
			. self::tokens( (string) self::get( 'suffix', '' ) );

		$order->update_meta_data( self::META_NUMBER, $number );
		$order->update_meta_data( self::META_SEQ, $sequence );
		$order->update_meta_data( self::META_DATE, current_time( 'mysql' ) );
		$order->save();

		/**
		 * Fires once an invoice number is allocated.
		 *
		 * @param string    $number Invoice number.
		 * @param \WC_Order $order  Order.
		 */
		do_action( 'pfh_invoice_number_assigned', $number, $order );

		return $number;
	}

	/**
	 * Expand [year] / [month] in a prefix or suffix.
	 *
	 * @param string $text Raw.
	 * @return string
	 */
	private static function tokens( $text ) {
		return strtr(
			$text,
			[
				'[year]'  => wp_date( 'Y' ),
				'[month]' => wp_date( 'm' ),
			]
		);
	}

	/**
	 * The next number in the series.
	 *
	 * Incremented inside MySQL rather than read-modify-written in PHP: two
	 * orders completing in the same second must not be handed the same
	 * invoice number, and a sequential series with a duplicate in it is a
	 * bookkeeping problem, not a cosmetic one.
	 *
	 * @return int
	 */
	private static function next_sequence() {
		global $wpdb;

		$start = max( 1, (int) self::get( 'start_number', 1 ) );

		if ( false === get_option( self::SEQUENCE, false ) ) {
			add_option( self::SEQUENCE, $start - 1, '', 'no' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- an atomic increment has no options API.
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = LAST_INSERT_ID(option_value + 1) WHERE option_name = %s",
				self::SEQUENCE
			)
		);

		wp_cache_delete( self::SEQUENCE, 'options' );
		wp_cache_delete( 'alloptions', 'options' );

		$next = (int) $wpdb->insert_id;

		if ( $updated && $next > 0 ) {
			return $next;
		}

		// The atomic path is unavailable (an object-cache-only store, say).
		// Fall back rather than fail: a gap is recoverable, a collision is not.
		$next = max( $start, (int) get_option( self::SEQUENCE, $start - 1 ) + 1 );
		update_option( self::SEQUENCE, $next, false );

		return $next;
	}

	/**
	 * Allocate a number when the order reaches the configured status.
	 *
	 * @param int $order_id Order id.
	 */
	public static function assign_on_status( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order ) {
			self::number( $order );
		}
	}

	/* ---------------------------------------------------------------------
	 * Delivery
	 * ------------------------------------------------------------------ */

	/**
	 * A signed link to one document.
	 *
	 * @param \WC_Order $order Order.
	 * @param string    $type  invoice|packing-slip.
	 * @return string
	 */
	public static function url( $order, $type = 'invoice' ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=pfh_document&type=' . rawurlencode( $type ) . '&order=' . $order->get_id() ),
			'pfh_document_' . $order->get_id()
		);
	}

	/**
	 * May the current user see this order's documents?
	 *
	 * @param \WC_Order $order Order.
	 * @return bool
	 */
	private static function allowed( $order ) {
		if ( current_user_can( 'edit_shop_orders' ) || current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		$user = get_current_user_id();

		return $user && (int) $order->get_customer_id() === $user;
	}

	/**
	 * Stream a document.
	 */
	public static function serve() {
		$id = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;

		if ( ! $id || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'pfh_document_' . $id ) ) {
			wp_die( esc_html__( 'That link has expired. Please reload the page and try again.', 'pfh-widgets' ), 403 );
		}

		$order = wc_get_order( $id );

		if ( ! $order ) {
			wp_die( esc_html__( 'Order not found.', 'pfh-widgets' ), 404 );
		}

		if ( ! self::allowed( $order ) ) {
			wp_die( esc_html__( 'You do not have permission to view this document.', 'pfh-widgets' ), 403 );
		}

		$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'invoice';
		$type = 'packing-slip' === $type ? 'packing-slip' : 'invoice';

		// Customers only ever get the invoice, never the warehouse document.
		if ( 'packing-slip' === $type && ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this document.', 'pfh-widgets' ), 403 );
		}

		$pdf  = self::build( $order, $type );
		$name = self::filename( $order, $type );

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . $name . '"' );
		header( 'Content-Length: ' . strlen( $pdf ) );

		// phpcs:ignore WordPress.Security.EscapeOutput -- binary PDF body.
		echo $pdf;
		exit;
	}

	/**
	 * File name for a document.
	 *
	 * @param \WC_Order $order Order.
	 * @param string    $type  Document type.
	 * @return string
	 */
	public static function filename( $order, $type ) {
		$stem = 'invoice' === $type
			? sanitize_file_name( self::get( 'title_invoice', 'Factuur' ) . '-' . ( self::number( $order, false ) ?: $order->get_order_number() ) )
			: sanitize_file_name( self::get( 'title_packing', 'Pakbon' ) . '-' . $order->get_order_number() );

		return $stem . '.pdf';
	}

	/**
	 * Attach the invoice to the configured WooCommerce emails.
	 *
	 * @param array     $attachments Paths.
	 * @param string    $email_id    Email id.
	 * @param mixed     $object      Usually the order.
	 * @return array
	 */
	public static function attach( $attachments, $email_id, $object ) {
		$map = [
			'customer_processing_order' => 'attach_processing',
			'customer_completed_order'  => 'attach_completed',
			'customer_invoice'          => 'attach_invoice',
		];

		if ( ! isset( $map[ $email_id ] ) || ! self::get( $map[ $email_id ], false ) ) {
			return $attachments;
		}

		if ( ! is_a( $object, 'WC_Order' ) ) {
			return $attachments;
		}

		$path = self::temp_file( $object );

		if ( $path ) {
			$attachments[] = $path;
		}

		return $attachments;
	}

	/**
	 * Write the invoice somewhere the mailer can read it.
	 *
	 * Lives in the system temp directory, not under wp-content, so it is not
	 * reachable over HTTP. Mail is sent during the same request, so the file
	 * is unlinked on shutdown.
	 *
	 * @param \WC_Order $order Order.
	 * @return string|null Absolute path.
	 */
	private static function temp_file( $order ) {
		$pdf = self::build( $order, 'invoice' );
		$dir = get_temp_dir();

		if ( ! $dir || ! wp_is_writable( $dir ) ) {
			return null;
		}

		$path = trailingslashit( $dir ) . self::filename( $order, 'invoice' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions -- WP_Filesystem is not initialised during mail.
		if ( false === file_put_contents( $path, $pdf ) ) {
			return null;
		}

		add_action(
			'shutdown',
			static function () use ( $path ) {
				if ( file_exists( $path ) ) {
					wp_delete_file( $path );
				}
			}
		);

		return $path;
	}

	/**
	 * Add an Invoice button to the My Account orders table.
	 *
	 * @param array     $actions Actions.
	 * @param \WC_Order $order   Order.
	 * @return array
	 */
	public static function account_action( $actions, $order ) {
		if ( ! self::get( 'customer_download', true ) ) {
			return $actions;
		}

		// Never allocate a number just because someone opened My Account.
		if ( '' === self::number( $order, false ) ) {
			return $actions;
		}

		$actions['pfh_invoice'] = [
			'url'  => self::url( $order, 'invoice' ),
			'name' => self::get( 'title_invoice', __( 'Invoice', 'pfh-widgets' ) ),
		];

		return $actions;
	}

	/* ---------------------------------------------------------------------
	 * Admin
	 * ------------------------------------------------------------------ */

	/**
	 * The Create PDF box on the order screen.
	 */
	public static function metabox() {
		$screens = [ 'shop_order', 'woocommerce_page_wc-orders' ];

		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		}

		foreach ( array_unique( array_filter( $screens ) ) as $screen ) {
			add_meta_box(
				'pfh-documents',
				__( 'Create PDF', 'pfh-widgets' ),
				[ __CLASS__, 'render_metabox' ],
				$screen,
				'side',
				'default'
			);
		}
	}

	/**
	 * @param mixed $post_or_order Post or order object, depending on storage.
	 */
	public static function render_metabox( $post_or_order ) {
		$order = is_a( $post_or_order, 'WC_Order' )
			? $post_or_order
			: wc_get_order( is_object( $post_or_order ) ? $post_or_order->ID : $post_or_order );

		if ( ! $order ) {
			return;
		}

		$number = self::number( $order, false );
		?>
		<p class="pfh-doc-actions">
			<a class="button button-primary" href="<?php echo esc_url( self::url( $order, 'invoice' ) ); ?>" target="_blank" rel="noopener">
				<?php echo esc_html( self::get( 'title_invoice', 'Factuur' ) ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( self::url( $order, 'packing-slip' ) ); ?>" target="_blank" rel="noopener">
				<?php echo esc_html( self::get( 'title_packing', 'Pakbon' ) ); ?>
			</a>
		</p>

		<p class="description">
			<?php if ( '' !== $number ) : ?>
				<?php
				printf(
					/* translators: 1: invoice number, 2: date. */
					esc_html__( 'Invoice %1$s, issued %2$s.', 'pfh-widgets' ),
					'<strong>' . esc_html( $number ) . '</strong>',
					esc_html( mysql2date( get_option( 'date_format' ), (string) $order->get_meta( self::META_DATE ) ) )
				);
				?>
			<?php else : ?>
				<?php esc_html_e( 'No invoice number yet. One is allocated the first time the invoice is opened, and then never changes.', 'pfh-widgets' ); ?>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * @param array $actions Bulk actions.
	 * @return array
	 */
	public static function bulk_action( $actions ) {
		$actions['pfh_invoices'] = __( 'Create invoices (PDF)', 'pfh-widgets' );
		$actions['pfh_packing']  = __( 'Create packing slips (PDF)', 'pfh-widgets' );

		return $actions;
	}

	/**
	 * Merge the selected orders into one document and stream it.
	 *
	 * @param string $redirect Redirect URL.
	 * @param string $action   Chosen action.
	 * @param array  $ids      Order ids.
	 * @return string
	 */
	public static function handle_bulk( $redirect, $action, $ids ) {
		if ( 'pfh_invoices' !== $action && 'pfh_packing' !== $action ) {
			return $redirect;
		}

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return $redirect;
		}

		$type   = 'pfh_invoices' === $action ? 'invoice' : 'packing-slip';
		$orders = [];

		foreach ( (array) $ids as $id ) {
			$order = wc_get_order( absint( $id ) );

			if ( $order ) {
				$orders[] = $order;
			}
		}

		if ( ! $orders ) {
			return $redirect;
		}

		$pdf = self::build_many( $orders, $type );

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $type . '-' . count( $orders ) . '.pdf' ) . '"' );
		header( 'Content-Length: ' . strlen( $pdf ) );

		// phpcs:ignore WordPress.Security.EscapeOutput -- binary PDF body.
		echo $pdf;
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------------ */

	/**
	 * One order as a PDF.
	 *
	 * @param \WC_Order $order Order.
	 * @param string    $type  invoice|packing-slip.
	 * @return string
	 */
	public static function build( $order, $type = 'invoice' ) {
		return self::build_many( [ $order ], $type );
	}

	/**
	 * Several orders in one PDF.
	 *
	 * @param array  $orders Orders.
	 * @param string $type   invoice|packing-slip.
	 * @return string
	 */
	public static function build_many( array $orders, $type = 'invoice' ) {
		$pdf = new PFH_Widgets_PDF();

		foreach ( $orders as $order ) {
			self::render_order( $pdf, $order, $type );
		}

		self::stamp_pages( $pdf );

		/**
		 * Filter the finished document.
		 *
		 * @param string $bytes  PDF.
		 * @param array  $orders Orders.
		 * @param string $type   Document type.
		 */
		return (string) apply_filters( 'pfh_document_pdf', $pdf->output(), $orders, $type );
	}

	/**
	 * Lay one order out, across as many pages as it needs.
	 *
	 * @param PFH_Widgets_PDF $pdf   Writer.
	 * @param \WC_Order       $order Order.
	 * @param string          $type  Document type.
	 */
	private static function render_order( $pdf, $order, $type ) {
		$invoice = 'invoice' === $type;
		$accent  = PFH_Widgets_Helpers::color( self::get( 'accent', '#3F7E7C' ), '#3F7E7C' );
		$muted   = '#6b7573';
		$rule    = '#dcdfdd';

		$left   = $pdf->margin();
		$right  = $pdf->page_width() - $pdf->margin();
		$width  = $pdf->inner_width();
		$bottom = $pdf->page_height() - 70;

		$pdf->add_page();

		$y = self::header( $pdf, $order, $type, $accent, $muted );

		/* ---- addresses ---- */

		$col = ( $width - 20 ) / 2;

		$pdf->font( 8.5, true )->color( $muted );
		$pdf->text( $left, $y, strtoupper( self::get( 'label_billing', 'Factuuradres' ) ) );

		$shipping = $order->get_formatted_shipping_address();

		if ( $shipping ) {
			$pdf->text( $left + $col + 20, $y, strtoupper( self::get( 'label_shipping', 'Verzendadres' ) ) );
		}

		$y += 13;

		$pdf->font( 9.5 )->color( '#1f2b2e' );

		$bill_end = $pdf->paragraph(
			$left,
			$y,
			$col,
			self::plain( $order->get_formatted_billing_address() ),
			12.5
		);

		$ship_end = $shipping
			? $pdf->paragraph( $left + $col + 20, $y, $col, self::plain( $shipping ), 12.5 )
			: $y;

		$y = max( $bill_end, $ship_end ) + 12;

		/* ---- meta strip ---- */

		$meta = [
			[ self::get( 'label_order_no', 'Ordernummer' ), $order->get_order_number() ],
			[ self::get( 'label_order_date', 'Orderdatum' ), $order->get_date_created() ? wp_date( get_option( 'date_format' ), $order->get_date_created()->getTimestamp() ) : '' ],
		];

		if ( $invoice ) {
			array_unshift(
				$meta,
				[ self::get( 'label_invoice_no', 'Factuurnummer' ), self::number( $order ) ],
				[ self::get( 'label_invoice_date', 'Factuurdatum' ), mysql2date( get_option( 'date_format' ), (string) $order->get_meta( self::META_DATE ) ) ]
			);

			$payment = $order->get_payment_method_title();

			if ( $payment ) {
				$meta[] = [ self::get( 'label_payment', 'Betaalmethode' ), $payment ];
			}
		}

		$pdf->rect( $left, $y, $width, 34, '#f4f6f5' );

		$slot = $width / max( 1, count( $meta ) );
		$mx   = $left + 10;

		foreach ( $meta as $pair ) {
			$pdf->font( 7.5, true )->color( $muted );
			$pdf->text( $mx, $y + 13, strtoupper( $pair[0] ) );

			$pdf->font( 9.5, true )->color( '#1f2b2e' );
			$pdf->text( $mx, $y + 26, $pair[1] );

			$mx += $slot;
		}

		$y += 52;

		/* ---- items ---- */

		$columns = self::columns( $pdf, $invoice );

		$y = self::table_head( $pdf, $columns, $y, $accent, $rule );

		$incl = (bool) self::get( 'prices_incl_tax', true );

		foreach ( $order->get_items() as $item ) {
			$product = is_callable( [ $item, 'get_product' ] ) ? $item->get_product() : null;

			$name = self::plain( $item->get_name() );
			$meta_text = function_exists( 'wc_display_item_meta' )
				? self::plain( wc_display_item_meta( $item, [ 'echo' => false, 'separator' => ', ' ] ) )
				: '';

			$pdf->font( 9.5 );
			$lines = $pdf->wrap( $name, $columns['description']['w'] - 8 );

			$pdf->font( 8.5 );
			$meta_lines = '' !== $meta_text ? $pdf->wrap( $meta_text, $columns['description']['w'] - 8 ) : [];

			$height = ( count( $lines ) * 13 ) + ( count( $meta_lines ) * 11 ) + 10;

			if ( $y + $height > $bottom ) {
				$pdf->add_page();
				$y = $pdf->margin();
				$y = self::table_head( $pdf, $columns, $y, $accent, $rule );
			}

			$row = $y + 11;

			$pdf->font( 9.5 )->color( '#1f2b2e' );

			foreach ( $lines as $line ) {
				$pdf->text( $columns['description']['x'], $row, $line );
				$row += 13;
			}

			if ( $meta_lines ) {
				$pdf->font( 8.5 )->color( $muted );

				foreach ( $meta_lines as $line ) {
					$pdf->text( $columns['description']['x'], $row, $line );
					$row += 11;
				}
			}

			$base = $y + 11;

			if ( isset( $columns['sku'] ) ) {
				$pdf->font( 8.5 )->color( $muted );
				$pdf->text( $columns['sku']['x'], $base, $product ? (string) $product->get_sku() : '' );
			}

			$pdf->font( 9.5 )->color( '#1f2b2e' );
			$pdf->text( $columns['qty']['x'] + $columns['qty']['w'], $base, (string) $item->get_quantity(), 'right' );

			if ( $invoice ) {
				$pdf->text(
					$columns['price']['x'] + $columns['price']['w'],
					$base,
					self::money( $order->get_item_total( $item, $incl, true ), $order ),
					'right'
				);

				$pdf->text(
					$columns['total']['x'] + $columns['total']['w'],
					$base,
					self::money( $order->get_line_total( $item, $incl, true ), $order ),
					'right'
				);
			}

			$y += $height;
			$pdf->line( $left, $y, $right, $y, $rule, 0.5 );
		}

		/* ---- totals ---- */

		if ( $invoice ) {
			$rows = self::totals( $order );

			if ( $y + ( count( $rows ) * 16 ) + 30 > $bottom ) {
				$pdf->add_page();
				$y = $pdf->margin();
			}

			$y += 14;

			$box = 240;
			$bx  = $right - $box;
			$last = count( $rows ) - 1;

			foreach ( $rows as $i => $pair ) {
				$strong = ( $i === $last );

				// Rule sits immediately above the grand total, with clearance
				// on both sides — measuring back from the previous row instead
				// put it through that row's descenders.
				if ( $strong && $i > 0 ) {
					$y += 6;
					$pdf->line( $bx, $y, $right, $y, $rule, 0.8 );
					$y += 7;
				}

				$pdf->font( $strong ? 11 : 9.5, $strong );
				$pdf->color( $strong ? '#1f2b2e' : $muted );
				$pdf->text( $bx, $y + 11, $pair[0] );

				$pdf->color( '#1f2b2e' );
				$pdf->text( $right, $y + 11, $pair[1], 'right' );

				$y += $strong ? 20 : 16;
			}
		}

		/* ---- note ---- */

		$note = trim( (string) self::get( 'footer_note', '' ) );

		if ( '' !== $note ) {
			$y += 10;

			if ( $y + 40 > $bottom ) {
				$pdf->add_page();
				$y = $pdf->margin();
			}

			$pdf->font( 8.5 )->color( $muted );
			$pdf->paragraph( $left, $y + 10, $width, $note, 11 );
		}
	}

	/**
	 * Sender block and document title. Returns the y to carry on from.
	 *
	 * @param PFH_Widgets_PDF $pdf    Writer.
	 * @param \WC_Order       $order  Order.
	 * @param string          $type   Document type.
	 * @param string          $accent Accent colour.
	 * @param string          $muted  Muted colour.
	 * @return float
	 */
	private static function header( $pdf, $order, $type, $accent, $muted ) {
		$left  = $pdf->margin();
		$right = $pdf->page_width() - $pdf->margin();
		$y     = $pdf->margin();

		$logo_id = (int) self::get( 'logo', 0 );
		$drawn   = [ 'h' => 0.0 ];

		if ( $logo_id ) {
			$path = get_attached_file( $logo_id );

			// phpcs:ignore WordPress.WP.AlternativeFunctions -- reading a local file for embedding.
			$bytes = ( $path && file_exists( $path ) ) ? file_get_contents( $path ) : '';

			if ( $bytes ) {
				$drawn = $pdf->image( $bytes, $left, $y, 150, 55 );
			}
		}

		if ( $drawn['h'] <= 0 ) {
			$pdf->font( 15, true )->color( '#1f2b2e' );
			$pdf->text( $left, $y + 14, self::get( 'company_name', get_bloginfo( 'name' ) ) );
			$drawn['h'] = 20;
		}

		// Sender details, right aligned against the logo.
		$pdf->font( 8.5 )->color( $muted );

		$ry    = $y + 8;
		$lines = PFH_Widgets_Helpers::lines( self::get( 'address', '' ) );

		foreach ( $lines as $line ) {
			$pdf->text( $right, $ry, $line, 'right' );
			$ry += 11;
		}

		foreach ( [ 'contact', 'vat', 'coc', 'iban' ] as $key ) {
			$value = trim( (string) self::get( $key, '' ) );

			if ( '' === $value ) {
				continue;
			}

			$prefix = [
				'vat'  => 'BTW ',
				'coc'  => 'KvK ',
				'iban' => 'IBAN ',
			];

			$pdf->text( $right, $ry, ( $prefix[ $key ] ?? '' ) . $value, 'right' );
			$ry += 11;
		}

		$y = max( $y + $drawn['h'], $ry ) + 22;

		$title = 'invoice' === $type
			? self::get( 'title_invoice', 'Factuur' )
			: self::get( 'title_packing', 'Pakbon' );

		$pdf->font( 19, true )->color( $accent );
		$pdf->text( $left, $y + 16, $title );

		$y += 32;

		$pdf->line( $left, $y, $right, $y, $accent, 1.4 );

		return $y + 20;
	}

	/**
	 * Column geometry.
	 *
	 * @param PFH_Widgets_PDF $pdf     Writer.
	 * @param bool            $invoice Invoice rather than packing slip.
	 * @return array<string, array{x:float, w:float, label:string, align:string}>
	 */
	private static function columns( $pdf, $invoice ) {
		$left  = $pdf->margin();
		$width = $pdf->inner_width();
		$sku   = (bool) self::get( 'show_sku', true );

		$sku_w   = $sku ? 78.0 : 0.0;
		$qty_w   = 44.0;
		$price_w = $invoice ? 76.0 : 0.0;
		$total_w = $invoice ? 82.0 : 0.0;

		$desc_w = $width - $sku_w - $qty_w - $price_w - $total_w;

		$columns = [
			'description' => [
				'x'     => $left,
				'w'     => $desc_w,
				'label' => self::get( 'label_description', 'Omschrijving' ),
				'align' => 'left',
			],
		];

		$x = $left + $desc_w;

		if ( $sku ) {
			$columns['sku'] = [
				'x'     => $x,
				'w'     => $sku_w,
				'label' => self::get( 'label_sku', 'SKU' ),
				'align' => 'left',
			];

			$x += $sku_w;
		}

		$columns['qty'] = [
			'x'     => $x,
			'w'     => $qty_w,
			'label' => self::get( 'label_qty', 'Aantal' ),
			'align' => 'right',
		];

		$x += $qty_w;

		if ( $invoice ) {
			$columns['price'] = [
				'x'     => $x,
				'w'     => $price_w,
				'label' => self::get( 'label_price', 'Prijs' ),
				'align' => 'right',
			];

			$x += $price_w;

			$columns['total'] = [
				'x'     => $x,
				'w'     => $total_w,
				'label' => self::get( 'label_total', 'Totaal' ),
				'align' => 'right',
			];
		}

		return $columns;
	}

	/**
	 * Draw the table header. Returns the y beneath it.
	 *
	 * @param PFH_Widgets_PDF $pdf     Writer.
	 * @param array           $columns Columns.
	 * @param float           $y       Top.
	 * @param string          $accent  Accent colour.
	 * @param string          $rule    Rule colour.
	 * @return float
	 */
	private static function table_head( $pdf, array $columns, $y, $accent, $rule ) {
		$left  = $pdf->margin();
		$right = $pdf->page_width() - $pdf->margin();

		$pdf->font( 8, true )->color( $accent );

		foreach ( $columns as $column ) {
			$x = 'right' === $column['align'] ? $column['x'] + $column['w'] : $column['x'];
			$pdf->text( $x, $y + 9, strtoupper( $column['label'] ), $column['align'] );
		}

		$y += 15;
		$pdf->line( $left, $y, $right, $y, $rule, 0.9 );

		return $y;
	}

	/**
	 * Totals, taken from WooCommerce rather than recalculated.
	 *
	 * @param \WC_Order $order Order.
	 * @return array<int, array{0:string, 1:string}>
	 */
	private static function totals( $order ) {
		$rows = [];

		foreach ( (array) $order->get_order_item_totals() as $key => $line ) {
			if ( ! isset( $line['label'] ) ) {
				continue;
			}

			// WooCommerce puts the payment method in with the money. The
			// header strip already carries it, and repeating it here pushes
			// the rule above the grand total onto the wrong row.
			if ( 'payment_method' === $key ) {
				continue;
			}

			$rows[] = [
				rtrim( self::plain( $line['label'] ), ': ' ),
				self::plain( isset( $line['value'] ) ? $line['value'] : '' ),
			];
		}

		if ( ! $rows ) {
			$rows[] = [
				self::get( 'label_total', 'Totaal' ),
				self::plain( $order->get_formatted_order_total() ),
			];
		}

		return $rows;
	}

	/**
	 * Format an amount in the order's currency, without markup.
	 *
	 * @param float     $amount Amount.
	 * @param \WC_Order $order  Order.
	 * @return string
	 */
	private static function money( $amount, $order ) {
		return self::plain(
			wc_price(
				(float) $amount,
				[
					'currency' => $order->get_currency(),
				]
			)
		);
	}

	/**
	 * HTML to single-line text a PDF can set.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	private static function plain( $html ) {
		$text = (string) $html;
		$text = str_ireplace( [ '<br>', '<br/>', '<br />', '</p>', '</div>', '</li>' ], "\n", $text );
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( "/[ \t]+/", ' ', $text );
		$text = preg_replace( "/\n{2,}/", "\n", (string) $text );

		return trim( (string) $text );
	}

	/**
	 * Page numbers, once the total is known.
	 *
	 * @param PFH_Widgets_PDF $pdf Writer.
	 */
	private static function stamp_pages( $pdf ) {
		$total = $pdf->page_count();

		if ( $total < 2 ) {
			return;
		}

		$format = (string) self::get( 'label_page', 'Pagina %1$d van %2$d' );

		for ( $i = 0; $i < $total; $i++ ) {
			$pdf->select_page( $i );
			$pdf->font( 8 )->color( '#8a928f' );
			$pdf->text(
				$pdf->page_width() - $pdf->margin(),
				$pdf->page_height() - 34,
				sprintf( $format, $i + 1, $total ),
				'right'
			);
		}
	}
}
