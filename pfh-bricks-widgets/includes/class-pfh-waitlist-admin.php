<?php
/**
 * WooCommerce → Wachtlijst: everyone waiting for a sold-out product.
 *
 * The same list XStore kept (email, product, stock, date, mailed), with the
 * same actions — mail them now, or remove them — plus a search, a filter per
 * product and a CSV download.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Waitlist_Admin {

	const PAGE = 'pfh-waitlist';
	const CAP  = 'manage_woocommerce';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 60 );
		add_action( 'admin_post_pfh_waitlist_export', [ __CLASS__, 'export' ] );
	}

	public static function menu() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() ) {
			return;
		}

		$hook = add_submenu_page(
			'woocommerce',
			__( 'Wachtlijst', 'pfh-widgets' ),
			__( 'Wachtlijst', 'pfh-widgets' ),
			self::CAP,
			self::PAGE,
			[ __CLASS__, 'render' ]
		);

		add_action( 'load-' . $hook, [ __CLASS__, 'handle' ] );
	}

	/**
	 * @return string
	 */
	private static function url( array $args = [] ) {
		return add_query_arg( $args, admin_url( 'admin.php?page=' . self::PAGE ) );
	}

	/**
	 * The actions, before anything is drawn, so they can redirect.
	 */
	public static function handle() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		PFH_Widgets_Waitlist::maybe_install();

		// phpcs:disable WordPress.Security.NonceVerification -- checked below, per action.
		$action = '';

		foreach ( [ 'action', 'action2' ] as $field ) {
			if ( ! empty( $_REQUEST[ $field ] ) && '-1' !== $_REQUEST[ $field ] ) {
				$action = sanitize_key( wp_unslash( $_REQUEST[ $field ] ) );
				break;
			}
		}

		if ( isset( $_POST['pfh_wl_import_start'] ) ) {
			$action = 'import-start';
		}

		if ( ! $action ) {
			return;
		}

		$ids = isset( $_REQUEST['row'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_REQUEST['row'] ) ) ) : [];
		// phpcs:enable

		if ( 'import-start' === $action ) {
			check_admin_referer( 'pfh_wl_import' );
			set_transient( 'pfh_wl_import_shown_' . get_current_user_id(), PFH_Widgets_Waitlist::start_import(), HOUR_IN_SECONDS );
			wp_safe_redirect( self::url( [ 'pfh-wl-done' => 'import-key' ] ) . '#pfh-wl-import' );
			exit;
		}

		if ( ! in_array( $action, [ 'notify', 'delete' ], true ) || ! $ids ) {
			return;
		}

		check_admin_referer( 'bulk-pfh-waitlist' );

		global $wpdb;

		$table = PFH_Widgets_Waitlist::table();
		$list  = implode( ',', $ids );

		if ( 'delete' === $action ) {
			$gone = (int) $wpdb->query( "DELETE FROM {$table} WHERE id IN ({$list})" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- integers only.

			wp_safe_redirect( self::url( [ 'pfh-wl-done' => 'deleted', 'n' => $gone ] ) );
			exit;
		}

		if ( ! PFH_Widgets_Waitlist::may_mail() ) {
			wp_safe_redirect( self::url( [ 'pfh-wl-done' => 'no-mail' ] ) );
			exit;
		}

		$rows    = $wpdb->get_results( "SELECT id, product_id FROM {$table} WHERE notified_at IS NULL AND id IN ({$list})" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- integers only.
		$by      = [];
		$sent    = 0;
		$skipped = 0;

		foreach ( (array) $rows as $row ) {
			$by[ (int) $row->product_id ][] = (int) $row->id;
		}

		foreach ( $by as $product_id => $row_ids ) {
			$product = wc_get_product( $product_id );

			if ( ! $product || ! $product->is_in_stock() ) {
				$skipped += count( $row_ids );
				continue;
			}

			$sent += PFH_Widgets_Waitlist::notify_product( $product_id, $row_ids );
		}

		wp_safe_redirect( self::url( [ 'pfh-wl-done' => 'notified', 'n' => $sent, 'skipped' => $skipped ] ) );
		exit;
	}

	/**
	 * WordPress's list table, and the waitlist's, on demand.
	 */
	public static function load_table() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		require_once PFH_WIDGETS_DIR . 'includes/class-pfh-waitlist-table.php';
	}

	public static function render() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to see the waitlist.', 'pfh-widgets' ) );
		}

		self::load_table();

		$table = new PFH_Widgets_Waitlist_Table();
		$table->prepare_items();

		$counts = PFH_Widgets_Waitlist::counts();
		?>
		<div class="wrap pfh-wl-admin">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Wachtlijst', 'pfh-widgets' ); ?></h1>
			<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pfh_waitlist_export' ), 'pfh_wl_export' ) ); ?>"><?php esc_html_e( 'Download as CSV', 'pfh-widgets' ); ?></a>
			<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=' . PFH_Widgets_Settings::PAGE . '&tab=waitlist' ) ); ?>"><?php esc_html_e( 'Settings', 'pfh-widgets' ); ?></a>
			<hr class="wp-header-end">

			<?php self::notice(); ?>

			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: people waiting, 2: products, 3: already mailed. */
						__( '%1$d sign-ups waiting on %2$d products; %3$d already mailed.', 'pfh-widgets' ),
						$counts['waiting'],
						$counts['products'],
						$counts['mailed']
					)
				);

				if ( ! PFH_Widgets_Waitlist::may_mail() ) {
					echo ' <strong>' . esc_html__( 'This is a test site: no emails are sent from here.', 'pfh-widgets' ) . '</strong>';
				}
				?>
			</p>

			<?php $table->views(); ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>">
				<?php
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- keeps the chosen view.
				$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';

				if ( $status ) {
					echo '<input type="hidden" name="status" value="' . esc_attr( $status ) . '">';
				}

				$table->search_box( __( 'Search', 'pfh-widgets' ), 'pfh-wl' );
				$table->display();
				?>
			</form>

			<?php self::import_panel(); ?>
		</div>
		<?php
	}

	private static function notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- messages after a redirect.
		$done    = isset( $_GET['pfh-wl-done'] ) ? sanitize_key( wp_unslash( $_GET['pfh-wl-done'] ) ) : '';
		$n       = isset( $_GET['n'] ) ? absint( $_GET['n'] ) : 0;
		$skipped = isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0;
		// phpcs:enable

		$messages = [
			/* translators: %d: rows. */
			'deleted'  => sprintf( _n( '%d sign-up removed.', '%d sign-ups removed.', $n, 'pfh-widgets' ), $n ),
			/* translators: %d: emails. */
			'notified' => sprintf( _n( '%d email sent.', '%d emails sent.', $n, 'pfh-widgets' ), $n )
				/* translators: %d: rows. */
				. ( $skipped ? ' ' . sprintf( _n( '%d skipped: that product is still sold out.', '%d skipped: those products are still sold out.', $skipped, 'pfh-widgets' ), $skipped ) : '' ),
			'no-mail'  => __( 'Nothing was sent: this is a test site. Sending from here can be switched on in the settings.', 'pfh-widgets' ),
		];

		if ( isset( $messages[ $done ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', 'no-mail' === $done ? 'warning' : 'success', esc_html( $messages[ $done ] ) );
		}
	}

	/**
	 * Bringing over the old shop's list, once.
	 */
	private static function import_panel() {
		$last = get_option( 'pfh_waitlist_last_import' );
		$key  = get_transient( 'pfh_wl_import_shown_' . get_current_user_id() );
		$live = get_option( PFH_Widgets_Waitlist::IMPORT_KEY );

		if ( $key && ( ! is_array( $live ) || ! hash_equals( (string) ( $live['key'] ?? '' ), (string) $key ) ) ) {
			$key = '';
		}
		?>
		<details class="pfh-wl-admin__import" id="pfh-wl-import"<?php echo $key ? ' open' : ''; ?> style="margin-top:32px;max-width:760px">
			<summary style="cursor:pointer;font-weight:600"><?php esc_html_e( 'Bring over the list from the old shop', 'pfh-widgets' ); ?></summary>
			<div style="padding:12px 0">
				<?php if ( is_array( $last ) ) : ?>
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: date, 2: added, 3: skipped. */
								__( 'Last brought over on %1$s: %2$d added, %3$d already here or unusable.', 'pfh-widgets' ),
								mysql2date( 'j F Y H:i', $last['at'] ),
								(int) $last['added'],
								(int) $last['skipped']
							)
						);
						?>
					</p>
				<?php endif; ?>

				<p><?php esc_html_e( 'The old shop (XStore) keeps its own list. A one-time key lets that list be posted here from the old shop\'s dashboard; it works once and for one hour. Rows already brought over are skipped, so doing it twice adds nothing twice.', 'pfh-widgets' ); ?></p>

				<?php if ( $key ) : ?>
					<p><label><?php esc_html_e( 'Key', 'pfh-widgets' ); ?><br><input type="text" readonly class="large-text code" value="<?php echo esc_attr( $key ); ?>" onclick="this.select()"></label></p>
					<p><label><?php esc_html_e( 'Address', 'pfh-widgets' ); ?><br><input type="text" readonly class="large-text code" value="<?php echo esc_attr( admin_url( 'admin-ajax.php?action=pfh_waitlist_remote_import' ) ); ?>" onclick="this.select()"></label></p>
				<?php else : ?>
					<form method="post">
						<?php wp_nonce_field( 'pfh_wl_import' ); ?>
						<button type="submit" class="button" name="pfh_wl_import_start" value="1"><?php esc_html_e( 'Make a one-time key', 'pfh-widgets' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
		</details>
		<?php
	}

	/**
	 * Everything, as a spreadsheet.
	 */
	public static function export() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to see the waitlist.', 'pfh-widgets' ) );
		}

		check_admin_referer( 'pfh_wl_export' );

		global $wpdb;

		$table = PFH_Widgets_Waitlist::table();
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name.

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wachtlijst-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- Excel reads UTF-8 with a BOM.
		fputcsv( $out, [ 'E-mail', 'Product', 'Product ID', 'Voorraad', 'Aangemeld', 'Gemaild' ], ';' );

		$names = [];

		foreach ( (array) $rows as $row ) {
			$pid = (int) $row->product_id;

			if ( ! isset( $names[ $pid ] ) ) {
				$product       = wc_get_product( $pid );
				$names[ $pid ] = $product ? [ $product->get_name(), $product->is_in_stock() ? 'Op voorraad' : 'Uitverkocht' ] : [ '#' . $pid, '' ];
			}

			fputcsv( $out, [ self::csv_cell( $row->email ), self::csv_cell( $names[ $pid ][0] ), $pid, $names[ $pid ][1], $row->created_at, (string) $row->notified_at ], ';' );
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/**
	 * A cell a spreadsheet will not run as a formula.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function csv_cell( $value ) {
		$value = (string) $value;

		return preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;
	}
}
