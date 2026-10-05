<?php
/**
 * The waitlist: "Informeer wanneer beschikbaar".
 *
 * The current shop runs on XStore, whose waitlist lets a visitor leave an
 * email address on a product that is sold out, keeps the list in the
 * dashboard (Waitlists, 526 sign-ups) and mails everyone once the product is
 * back. The new shop has no XStore, so the plugin does it (2026-10-02):
 *
 *   A sold-out product shows "Informeer wanneer beschikbaar" instead of the
 *   cart button. It opens a small form — email, consent, "Schrijf in op de
 *   wachtlijst" — that saves the sign-up without leaving the page.
 *
 *   Sign-ups live in their own table, one row per person per product, and
 *   are listed under WooCommerce → Wachtlijst.
 *
 *   When the product's stock comes back, everyone who signed up and has not
 *   been mailed yet gets one email, and is marked as mailed. The list can
 *   also be mailed by hand. Off production (a staging copy holding real
 *   customers' addresses) nothing is mailed unless that is switched on.
 *
 *   A signed-in customer finds their sign-ups in My Account → Wachtlijst.
 *
 *   The live shop's list is brought over once, with a one-time key: an admin
 *   starts the import here, and the list is posted from the live dashboard.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Waitlist extends PFH_Settings_Module {

	const DB_VERSION = '2';
	const DB_OPTION  = 'pfh_waitlist_db';
	const OPTION     = 'pfh_waitlist';
	const IMPORT_KEY = 'pfh_waitlist_import_key';
	const ENDPOINT   = 'pfh-wachtlijst';
	const ACTION     = 'pfh_waitlist_join';
	const LEAVE      = 'pfh_waitlist_leave';
	const CRON       = 'pfh_waitlist_notify';

	public static function init() {
		PFH_Widgets_Settings::register(
			'waitlist',
			static function () {
				return __( 'Wachtlijst', 'pfh-widgets' );
			},
			__CLASS__
		);

		add_action( 'init', [ __CLASS__, 'maybe_install' ], 5 );
		add_filter( 'woocommerce_get_query_vars', [ __CLASS__, 'query_vars' ] );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', [ __CLASS__, 'endpoint_title' ] );

		add_action( 'wp_ajax_' . self::ACTION, [ __CLASS__, 'ajax_join' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ __CLASS__, 'ajax_join' ] );
		add_action( 'wp_ajax_' . self::LEAVE, [ __CLASS__, 'ajax_leave' ] );

		add_action( 'wp_ajax_pfh_waitlist_remote_import', [ __CLASS__, 'ajax_remote_import' ] );
		add_action( 'wp_ajax_nopriv_pfh_waitlist_remote_import', [ __CLASS__, 'ajax_remote_import' ] );

		// Back in stock: mail the list, a moment later, outside the save.
		add_action( 'woocommerce_product_set_stock_status', [ __CLASS__, 'stock_changed' ], 10, 2 );
		add_action( 'woocommerce_variation_set_stock_status', [ __CLASS__, 'stock_changed' ], 10, 2 );
		add_action( self::CRON, [ __CLASS__, 'notify_product' ] );

		add_filter( 'woocommerce_account_menu_items', [ __CLASS__, 'account_item' ], 30 );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', [ __CLASS__, 'account_pane' ] );

		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ], 5 );
	}

	/* ------------------------------------------------------------------ */
	/* Settings                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * @return array
	 */
	public static function fields() {
		return [
			'waitlist' => [
				'label'  => __( 'Waitlist', 'pfh-widgets' ),
				'desc'   => __( 'A sold-out product shows "Informeer wanneer beschikbaar" instead of the cart button. Visitors leave their email address, and get one email when the product is back in stock. The sign-ups are under WooCommerce → Wachtlijst.', 'pfh-widgets' ),
				'fields' => [
					'overview'            => [
						'type'     => 'info',
						'callback' => [ __CLASS__, 'settings_overview' ],
					],
					'enabled'             => [
						'type'     => 'checkbox',
						'label'    => __( 'Waitlist', 'pfh-widgets' ),
						'cb_label' => __( 'Let visitors sign up on sold-out products', 'pfh-widgets' ),
						'default'  => true,
					],
					'auto'                => [
						'type'     => 'checkbox',
						'label'    => __( 'Back in stock', 'pfh-widgets' ),
						'cb_label' => __( 'Email the list as soon as a product is back in stock', 'pfh-widgets' ),
						'default'  => true,
						'desc'     => __( 'Everyone gets one email, and is marked as mailed. Without this, mail them from the list by hand.', 'pfh-widgets' ),
					],
					'mail_off_production' => [
						'type'     => 'checkbox',
						'label'    => __( 'Test site', 'pfh-widgets' ),
						'cb_label' => __( 'Also send the emails from a staging or development site', 'pfh-widgets' ),
						'default'  => false,
						'desc'     => __( 'Off by default: a copy of the shop holds the real customers\' addresses, and should not mail them.', 'pfh-widgets' ),
					],
					'text_button'         => [
						'type'    => 'text',
						'label'   => __( 'Button', 'pfh-widgets' ),
						'default' => 'Informeer wanneer beschikbaar',
					],
					'text_title'          => [
						'type'    => 'text',
						'label'   => __( 'Form title', 'pfh-widgets' ),
						'default' => 'Momenteel uitverkocht',
					],
					'text_lede'           => [
						'type'    => 'textarea',
						'label'   => __( 'Form text', 'pfh-widgets' ),
						'default' => 'Laat je e-mailadres achter. We sturen je één bericht zodra dit product weer op voorraad is.',
					],
					'text_consent'        => [
						'type'    => 'text',
						'label'   => __( 'Consent', 'pfh-widgets' ),
						'default' => 'Ik ga ermee akkoord dat ik een update over de voorraad ontvang',
					],
					'text_submit'         => [
						'type'    => 'text',
						'label'   => __( 'Form button', 'pfh-widgets' ),
						'default' => 'Schrijf in op de wachtlijst',
					],
				],
			],
		];
	}

	/**
	 * The numbers at the top of the tab, with the way to the list.
	 */
	public static function settings_overview() {
		$counts = self::counts();

		printf(
			'<div class="pfh-settings__note">%s <a href="%s">%s</a></div>',
			esc_html(
				sprintf(
					/* translators: 1: people waiting, 2: products, 3: already mailed. */
					__( '%1$d sign-ups waiting on %2$d products; %3$d already mailed.', 'pfh-widgets' ),
					$counts['waiting'],
					$counts['products'],
					$counts['mailed']
				)
			),
			esc_url( admin_url( 'admin.php?page=' . PFH_Widgets_Waitlist_Admin::PAGE ) ),
			esc_html__( 'Open the list', 'pfh-widgets' )
		);
	}

	/**
	 * @return bool Whether sold-out products show the waitlist.
	 */
	public static function enabled() {
		return (bool) self::get( 'enabled', true ) && PFH_Widgets_Helpers::has_woocommerce();
	}

	/**
	 * May this site send waitlist mail? Production yes; a staging copy only
	 * when switched on, so a test does not mail the live shop's customers.
	 *
	 * @return bool
	 */
	public static function may_mail() {
		return ! self::is_test_site() || (bool) self::get( 'mail_off_production', false );
	}

	/**
	 * @param string $host Site address, without the scheme.
	 * @return bool Whether it is a staging or local address.
	 */
	public static function is_test_host( $host ) {
		return (bool) preg_match( '/(\.kinsta\.cloud|\.local|\.test|\.localhost)$|^(localhost|127\.0\.0\.1|\[::1\])$|staging/', strtolower( (string) $host ) );
	}

	/**
	 * Is this a copy of the shop rather than the shop? Kinsta's staging site
	 * calls itself "production", so its address is checked as well.
	 *
	 * @return bool
	 */
	public static function is_test_site() {
		$env  = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$test = 'production' !== $env
			|| ( defined( 'KINSTA_DEV_ENV' ) && KINSTA_DEV_ENV )
			|| self::is_test_host( $host );

		/**
		 * Filter whether this site is a test copy, which sends no waitlist mail.
		 *
		 * @param bool   $test Test copy.
		 * @param string $host Site address.
		 */
		return (bool) apply_filters( 'pfh_widgets_waitlist_test_site', $test, $host );
	}

	/* ------------------------------------------------------------------ */
	/* Storage                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * @return string
	 */
	public static function table() {
		global $wpdb;

		return $wpdb->prefix . 'pfh_waitlist';
	}

	public static function maybe_install() {
		if ( self::DB_VERSION === get_option( self::DB_OPTION ) ) {
			return;
		}

		self::install();

		// The My Account address (/mijn-account/pfh-wachtlijst/) is new.
		add_action( 'init', 'flush_rewrite_rules', 99 );
	}

	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$collate = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				product_id bigint(20) unsigned NOT NULL DEFAULT 0,
				email varchar(190) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				notified_at datetime NULL DEFAULT NULL,
				source varchar(20) NOT NULL DEFAULT 'site',
				legacy_id varchar(40) NOT NULL DEFAULT '',
				PRIMARY KEY  (id),
				KEY product_id (product_id),
				KEY email (email),
				KEY legacy_id (legacy_id)
			) {$collate};"
		);

		self::repair();

		update_option( self::DB_OPTION, self::DB_VERSION, true );
	}

	/**
	 * One row per sign-up brought over, and one open sign-up per person per
	 * product. Two imports that overlapped on the staging site each added the
	 * old shop's list before either had finished (2026-10-02).
	 */
	public static function repair() {
		global $wpdb;

		$table = self::table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name only.
		$wpdb->query( "DELETE FROM {$table} WHERE legacy_id <> '' AND id NOT IN ( SELECT keep FROM ( SELECT MIN(id) AS keep FROM {$table} WHERE legacy_id <> '' GROUP BY legacy_id ) AS k )" );
		$wpdb->query( "DELETE FROM {$table} WHERE notified_at IS NULL AND id NOT IN ( SELECT keep FROM ( SELECT MIN(id) AS keep FROM {$table} WHERE notified_at IS NULL GROUP BY product_id, email ) AS k )" );
		// phpcs:enable
	}

	/**
	 * @return array{waiting: int, products: int, mailed: int}
	 */
	public static function counts() {
		global $wpdb;

		$table = self::table();

		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return [ 'waiting' => 0, 'products' => 0, 'mailed' => 0 ];
		}

		$row = $wpdb->get_row( "SELECT SUM( notified_at IS NULL ) AS waiting, COUNT( DISTINCT CASE WHEN notified_at IS NULL THEN product_id END ) AS products, SUM( notified_at IS NOT NULL ) AS mailed FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name.

		return [
			'waiting'  => $row ? (int) $row->waiting : 0,
			'products' => $row ? (int) $row->products : 0,
			'mailed'   => $row ? (int) $row->mailed : 0,
		];
	}

	/**
	 * @param int $product_id Product or variation.
	 * @return int People on its list who have not been mailed.
	 */
	public static function waiting( $product_id ) {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE product_id = %d AND notified_at IS NULL', (int) $product_id ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name.
		);
	}

	/**
	 * Put someone on a product's list.
	 *
	 * @param int    $product_id Product or variation.
	 * @param string $email      Address.
	 * @param int    $user_id    Signed-in customer, or 0.
	 * @return string 'added', 'exists' or an error code.
	 */
	public static function subscribe( $product_id, $email, $user_id = 0 ) {
		global $wpdb;

		$product_id = (int) $product_id;
		$email      = strtolower( trim( (string) $email ) );
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product || 'publish' !== get_post_status( $product->get_parent_id() ? $product->get_parent_id() : $product_id ) ) {
			return 'no-product';
		}

		if ( ! is_email( $email ) ) {
			return 'bad-email';
		}

		$open = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE product_id = %d AND email = %s AND notified_at IS NULL', $product_id, $email ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name.
		);

		if ( $open ) {
			return 'exists';
		}

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			[
				'product_id' => $product_id,
				'email'      => $email,
				'user_id'    => (int) $user_id,
				'created_at' => current_time( 'mysql' ),
				'source'     => 'site',
			],
			[ '%d', '%s', '%d', '%s', '%s' ]
		);

		/**
		 * Fires when someone joins a product's waitlist.
		 *
		 * @param int    $product_id Product.
		 * @param string $email      Address.
		 */
		do_action( 'pfh_widgets_waitlist_joined', $product_id, $email );

		return 'added';
	}

	/* ------------------------------------------------------------------ */
	/* The form                                                            */
	/* ------------------------------------------------------------------ */

	public static function register_assets() {
		wp_register_style( 'pfh-waitlist', PFH_WIDGETS_URL . 'assets/css/pfh-waitlist.css', [ 'pfh-base' ], PFH_WIDGETS_VERSION );
		wp_register_script( 'pfh-waitlist', PFH_WIDGETS_URL . 'assets/js/pfh-waitlist.js', [], PFH_WIDGETS_VERSION, true );
	}

	/**
	 * Does this product get the waitlist instead of a cart button? A simple
	 * product that is sold out, or a variable one with nothing left in any
	 * of its variations.
	 *
	 * @param WC_Product|mixed $product Product.
	 * @return bool
	 */
	public static function applies( $product ) {
		if ( ! $product instanceof WC_Product || ! self::enabled() ) {
			return false;
		}

		if ( ! $product->is_type( [ 'simple', 'variable' ] ) ) {
			return false;
		}

		$applies = ! $product->is_in_stock();

		/**
		 * Filter whether a product shows the waitlist.
		 *
		 * @param bool       $applies Sold out.
		 * @param WC_Product $product Product.
		 */
		return (bool) apply_filters( 'pfh_widgets_waitlist_applies', $applies, $product );
	}

	/**
	 * @return string The button's wording.
	 */
	public static function label() {
		$label = trim( (string) self::get( 'text_button', '' ) );

		return '' !== $label ? $label : (string) self::defaults()['text_button'];
	}

	/**
	 * The button and its form, for a product that is sold out.
	 *
	 * @param WC_Product $product Product.
	 * @param array      $text    Wording: button, title, consent, submit.
	 * @return string
	 */
	public static function form( $product, array $text = [] ) {
		if ( ! self::enabled() || ! $product instanceof WC_Product ) {
			return '';
		}

		$text = array_merge(
			[
				'button'  => (string) self::get( 'text_button', '' ),
				'title'   => (string) self::get( 'text_title', '' ),
				'lede'    => (string) self::get( 'text_lede', '' ),
				'consent' => (string) self::get( 'text_consent', '' ),
				'submit'  => (string) self::get( 'text_submit', '' ),
			],
			array_filter( array_map( 'strval', $text ), 'strlen' )
		);

		// A cleared field falls back to the wording it came with, except the
		// text under the title, which may be left out.
		foreach ( [ 'button', 'title', 'consent', 'submit' ] as $key ) {
			if ( '' === trim( $text[ $key ] ) ) {
				$text[ $key ] = (string) self::defaults()[ 'text_' . $key ];
			}
		}

		self::register_assets();
		wp_enqueue_style( 'pfh-waitlist' );
		wp_enqueue_script( 'pfh-waitlist' );

		static $configured = false;

		if ( ! $configured ) {
			$configured = true;

			wp_add_inline_script(
				'pfh-waitlist',
				'window.pfhWaitlist = ' . wp_json_encode(
					[
						'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
						'action'   => self::ACTION,
						'messages' => [
							'added'     => __( 'Je staat op de wachtlijst. We mailen je zodra het product weer op voorraad is.', 'pfh-widgets' ),
							'exists'    => __( 'Je stond al op de wachtlijst voor dit product. We mailen je zodra het er weer is.', 'pfh-widgets' ),
							'bad-email' => __( 'Vul een geldig e-mailadres in.', 'pfh-widgets' ),
							'consent'   => __( 'Vink het vakje aan, dan mogen we je mailen.', 'pfh-widgets' ),
							'error'     => __( 'Dat lukte niet. Probeer het later nog eens.', 'pfh-widgets' ),
						],
					]
				) . ';',
				'before'
			);
		}

		static $count = 0;

		$count++;

		$id    = 'pfh-wl-' . (int) $product->get_id() . '-' . $count;
		$email = '';

		if ( is_user_logged_in() ) {
			$user  = wp_get_current_user();
			$email = (string) $user->user_email;
		}

		ob_start();
		?>
		<div class="pfh-wl" data-pfh-wl>
			<button type="button" class="pfh-wl__open" data-pfh-wl-open aria-haspopup="dialog" aria-controls="<?php echo esc_attr( $id ); ?>">
				<?php echo PFH_Widgets_Icons::get( 'bell', 'pfh-wl__bell' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<span><?php echo esc_html( $text['button'] ); ?></span>
			</button>
			<div class="pfh-wl__modal" id="<?php echo esc_attr( $id ); ?>" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $id ); ?>-title" hidden data-pfh-wl-modal>
				<div class="pfh-wl__scrim" data-pfh-wl-close></div>
				<form class="pfh-wl__panel" data-pfh-wl-form novalidate>
					<button type="button" class="pfh-wl__close" data-pfh-wl-close aria-label="<?php esc_attr_e( 'Sluiten', 'pfh-widgets' ); ?>">
						<?php echo PFH_Widgets_Icons::get( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					</button>
					<p class="pfh-wl__eyebrow"><?php echo esc_html( $product->get_name() ); ?></p>
					<h2 class="pfh-wl__title" id="<?php echo esc_attr( $id ); ?>-title"><?php echo esc_html( $text['title'] ); ?></h2>
					<?php if ( '' !== trim( $text['lede'] ) ) : ?>
						<p class="pfh-wl__lede"><?php echo esc_html( $text['lede'] ); ?></p>
					<?php endif; ?>
					<input type="hidden" name="product_id" value="<?php echo (int) $product->get_id(); ?>" />
					<label class="pfh-wl__field">
						<span class="pfh-wl__label"><?php esc_html_e( 'E-mailadres', 'pfh-widgets' ); ?></span>
						<input type="email" name="email" required autocomplete="email" value="<?php echo esc_attr( $email ); ?>" placeholder="naam@voorbeeld.nl" />
					</label>
					<label class="pfh-wl__hp" aria-hidden="true">
						<span>Website</span>
						<input type="text" name="website" tabindex="-1" autocomplete="off" />
					</label>
					<label class="pfh-wl__consent">
						<input type="checkbox" name="consent" value="1" required />
						<span><?php echo esc_html( $text['consent'] ); ?></span>
					</label>
					<button type="submit" class="pfh-wl__submit">
						<span class="pfh-wl__submit-label"><?php echo esc_html( $text['submit'] ); ?></span>
						<span class="pfh-wl__spin" aria-hidden="true"></span>
					</button>
					<p class="pfh-wl__message" data-pfh-wl-message role="status" hidden></p>
				</form>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function ajax_join() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- a public form on cached pages; a honeypot and a rate limit stand in for a nonce.
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$consent    = ! empty( $_POST['consent'] );
		$trap       = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';
		// phpcs:enable

		if ( '' !== $trap ) {
			wp_send_json_success( [ 'state' => 'added' ] );
		}

		if ( ! $consent ) {
			wp_send_json_error( [ 'state' => 'consent' ], 400 );
		}

		// Ten sign-ups an hour from one address is plenty for a person.
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key   = 'pfh_wl_rate_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= 10 ) {
			wp_send_json_error( [ 'state' => 'error' ], 429 );
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );

		$state = self::subscribe( $product_id, $email, get_current_user_id() );

		if ( in_array( $state, [ 'added', 'exists' ], true ) ) {
			wp_send_json_success( [ 'state' => $state ] );
		}

		wp_send_json_error( [ 'state' => 'bad-email' === $state ? 'bad-email' : 'error' ], 400 );
	}

	/* ------------------------------------------------------------------ */
	/* Back in stock                                                       */
	/* ------------------------------------------------------------------ */

	/**
	 * @param int    $product_id Product or variation.
	 * @param string $status     New stock status.
	 */
	public static function stock_changed( $product_id, $status ) {
		if ( 'instock' !== $status || ! self::get( 'auto', true ) ) {
			return;
		}

		if ( ! self::waiting( $product_id ) ) {
			return;
		}

		if ( ! wp_next_scheduled( self::CRON, [ (int) $product_id ] ) ) {
			wp_schedule_single_event( time() + 60, self::CRON, [ (int) $product_id ] );
		}
	}

	/**
	 * Mail everyone on a product's list who has not been mailed.
	 *
	 * @param int        $product_id Product or variation.
	 * @param int[]|null $ids        Only these rows (for the dashboard).
	 * @return int How many were mailed.
	 */
	public static function notify_product( $product_id, $ids = null ) {
		global $wpdb;

		$product = wc_get_product( (int) $product_id );

		// Sold out again by the time this runs: the list waits for next time.
		if ( ! $product || ! $product->is_in_stock() || ! self::may_mail() ) {
			return 0;
		}

		$sql = $wpdb->prepare( 'SELECT id, email FROM ' . self::table() . ' WHERE product_id = %d AND notified_at IS NULL', (int) $product_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name.

		if ( is_array( $ids ) ) {
			$ids = array_filter( array_map( 'absint', $ids ) );

			if ( ! $ids ) {
				return 0;
			}

			$sql .= ' AND id IN (' . implode( ',', $ids ) . ')';
		}

		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- prepared above; ids are integers.
		$sent = 0;

		foreach ( (array) $rows as $row ) {
			if ( self::send( $row->email, $product ) ) {
				$wpdb->update( self::table(), [ 'notified_at' => current_time( 'mysql' ) ], [ 'id' => (int) $row->id ], [ '%s' ], [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$sent++;
			}
		}

		return $sent;
	}

	/**
	 * The "weer op voorraad" email, in WooCommerce's own email layout.
	 *
	 * @param string     $email   Address.
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	public static function send( $email, $product ) {
		if ( ! function_exists( 'WC' ) || ! is_email( $email ) ) {
			return false;
		}

		$name    = $product->get_name();
		$url     = $product->get_permalink();
		$image   = wp_get_attachment_image_url( (int) $product->get_image_id(), 'woocommerce_thumbnail' );
		/* translators: %s: product name. */
		$subject = sprintf( __( '%s is weer op voorraad', 'pfh-widgets' ), $name );
		$heading = __( 'Goed nieuws: het is er weer!', 'pfh-widgets' );

		$body  = '<p>' . esc_html__( 'Hallo,', 'pfh-widgets' ) . '</p>';
		/* translators: %s: product name. */
		$body .= '<p>' . sprintf( esc_html__( 'Je vroeg ons je te laten weten wanneer %s weer op voorraad is. Dat is nu zo.', 'pfh-widgets' ), '<strong>' . esc_html( $name ) . '</strong>' ) . '</p>';

		if ( $image ) {
			$body .= '<p><a href="' . esc_url( $url ) . '"><img src="' . esc_url( $image ) . '" alt="' . esc_attr( $name ) . '" width="220" style="max-width:220px;height:auto;border-radius:12px" /></a></p>';
		}

		$body .= '<p><a href="' . esc_url( $url ) . '" style="display:inline-block;padding:12px 24px;border-radius:8px;background:#557f82;color:#ffffff;text-decoration:none;font-weight:600">' . esc_html__( 'Bekijk het product', 'pfh-widgets' ) . '</a></p>';
		$body .= '<p>' . esc_html__( 'Op is op, dus wacht niet te lang.', 'pfh-widgets' ) . '</p>';
		$body .= '<p style="color:#8d9589;font-size:12px">' . esc_html__( 'Je krijgt dit bericht één keer, omdat je je voor dit product op de wachtlijst hebt gezet.', 'pfh-widgets' ) . '</p>';

		$mailer  = WC()->mailer();
		$message = $mailer->wrap_message( $heading, $body );

		return (bool) $mailer->send( $email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	/* ------------------------------------------------------------------ */
	/* My Account                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * WooCommerce's account addresses, plus the waitlist's.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;

		return $vars;
	}

	/**
	 * @return string
	 */
	public static function endpoint_title() {
		return __( 'Wachtlijst', 'pfh-widgets' );
	}

	/**
	 * @param array $items Menu items.
	 * @return array
	 */
	public static function account_item( $items ) {
		if ( ! self::enabled() ) {
			return $items;
		}

		$out = [];

		foreach ( (array) $items as $key => $label ) {
			if ( 'customer-logout' === $key ) {
				$out[ self::ENDPOINT ] = __( 'Wachtlijst', 'pfh-widgets' );
			}

			$out[ $key ] = $label;
		}

		if ( ! isset( $out[ self::ENDPOINT ] ) ) {
			$out[ self::ENDPOINT ] = __( 'Wachtlijst', 'pfh-widgets' );
		}

		return $out;
	}

	/**
	 * @param int $user_id Customer.
	 * @return array Open sign-ups.
	 */
	public static function for_user( $user_id ) {
		global $wpdb;

		$user = get_userdata( (int) $user_id );

		if ( ! $user ) {
			return [];
		}

		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( 'SELECT id, product_id, created_at FROM ' . self::table() . ' WHERE notified_at IS NULL AND ( user_id = %d OR email = %s ) ORDER BY created_at DESC', (int) $user_id, strtolower( $user->user_email ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name.
		);
	}

	public static function account_pane() {
		$rows = self::for_user( get_current_user_id() );

		echo '<div class="pfh-wl-list" data-pfh-wl-list>';
		echo '<p class="pfh-wl-list__lede">' . esc_html__( 'De producten waarvoor je een bericht krijgt zodra ze weer op voorraad zijn.', 'pfh-widgets' ) . '</p>';

		if ( ! $rows ) {
			echo '<p class="pfh-wl-list__empty">' . esc_html__( 'Je staat nergens op de wachtlijst.', 'pfh-widgets' ) . '</p></div>';
			return;
		}

		wp_enqueue_script( 'pfh-waitlist' );
		wp_enqueue_style( 'pfh-waitlist' );

		echo '<ul class="pfh-wl-list__items">';

		foreach ( $rows as $row ) {
			$product = wc_get_product( (int) $row->product_id );

			if ( ! $product ) {
				continue;
			}

			printf(
				'<li class="pfh-wl-list__item"><a href="%s">%s</a><span class="pfh-wl-list__date">%s</span><button type="button" class="pfh-wl-list__leave" data-pfh-wl-leave="%d" data-nonce="%s">%s</button></li>',
				esc_url( $product->get_permalink() ),
				esc_html( $product->get_name() ),
				/* translators: %s: date. */
				esc_html( sprintf( __( 'Sinds %s', 'pfh-widgets' ), mysql2date( 'j F Y', $row->created_at ) ) ),
				(int) $row->id,
				esc_attr( wp_create_nonce( self::LEAVE ) ),
				esc_html__( 'Verwijderen', 'pfh-widgets' )
			);
		}

		echo '</ul></div>';
	}

	public static function ajax_leave() {
		check_ajax_referer( self::LEAVE, 'nonce' );

		global $wpdb;

		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$user = wp_get_current_user();

		if ( ! $id || ! $user->exists() ) {
			wp_send_json_error( null, 400 );
		}

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE id = %d AND ( user_id = %d OR email = %s )', $id, (int) $user->ID, strtolower( $user->user_email ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name.
		);

		wp_send_json_success();
	}

	/* ------------------------------------------------------------------ */
	/* Bringing over the live shop's list                                  */
	/* ------------------------------------------------------------------ */

	/**
	 * A one-time key, valid for an hour, that lets the live shop's list be
	 * posted here from its own dashboard.
	 *
	 * @return string
	 */
	public static function start_import() {
		$key = wp_generate_password( 40, false, false );

		update_option( self::IMPORT_KEY, [ 'key' => $key, 'expires' => time() + HOUR_IN_SECONDS ], false );

		return $key;
	}

	/**
	 * Rows as the live dashboard lists them: legacy id, email, product,
	 * date added, mailed (1/0).
	 *
	 * @param array $rows Rows.
	 * @return array{added: int, skipped: int}
	 */
	public static function import_rows( array $rows ) {
		global $wpdb;

		$added   = 0;
		$skipped = 0;

		foreach ( $rows as $row ) {
			$row    = array_values( (array) $row );
			$legacy = isset( $row[0] ) ? 'xstore:' . preg_replace( '/[^0-9a-z_-]/i', '', (string) $row[0] ) : '';
			$email  = isset( $row[1] ) ? strtolower( sanitize_email( (string) $row[1] ) ) : '';
			$pid    = isset( $row[2] ) ? absint( $row[2] ) : 0;
			$date   = isset( $row[3] ) && preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', (string) $row[3] ) ? substr( (string) $row[3], 0, 16 ) . ':00' : current_time( 'mysql' );
			$mailed = ! empty( $row[4] );

			if ( ! is_email( $email ) || ! $pid || 'xstore:' === $legacy ) {
				$skipped++;
				continue;
			}

			$seen = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE legacy_id = %s', $legacy ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- table name.

			if ( $seen ) {
				$skipped++;
				continue;
			}

			// XStore let the same address sign up twice for one product; one
			// open sign-up is enough, or they would get the email twice.
			if ( ! $mailed ) {
				$open = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE product_id = %d AND email = %s AND notified_at IS NULL', $pid, $email ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- table name.

				if ( $open ) {
					$skipped++;
					continue;
				}
			}

			$user = get_user_by( 'email', $email );

			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				self::table(),
				[
					'product_id'  => $pid,
					'email'       => $email,
					'user_id'     => $user ? (int) $user->ID : 0,
					'created_at'  => $date,
					'notified_at' => $mailed ? $date : null,
					'source'      => 'xstore',
					'legacy_id'   => $legacy,
				],
				[ '%d', '%s', '%d', '%s', '%s', '%s', '%s' ]
			);

			$added++;
		}

		return [ 'added' => $added, 'skipped' => $skipped ];
	}

	/**
	 * The old theme's own waitlist table, when this database still has it —
	 * as the live shop will, once the design has moved onto it.
	 *
	 * XStore's columns are read rather than assumed: which one holds the
	 * address, the product, the date and whether it was mailed.
	 *
	 * @return array|null {table, rows, columns, problem}
	 */
	public static function legacy_table_info() {
		global $wpdb;

		$table = $wpdb->prefix . 'xstore_waitlist';

		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return null;
		}

		$names = array_map( 'strtolower', (array) $wpdb->get_col( 'SHOW COLUMNS FROM ' . esc_sql( $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- table name.
		$pick  = static function ( array $wanted ) use ( $names ) {
			foreach ( $wanted as $name ) {
				if ( in_array( $name, $names, true ) ) {
					return $name;
				}
			}

			return '';
		};

		$columns = [
			'id'      => $pick( [ 'id', 'waitlist_id' ] ),
			'email'   => $pick( [ 'email', 'user_email', 'customer_email', 'mail' ] ),
			'product' => $pick( [ 'product_id', 'product', 'post_id', 'variation_id' ] ),
			'date'    => $pick( [ 'created_at', 'date_added', 'date_created', 'date', 'created', 'time', 'added' ] ),
			'mailed'  => $pick( [ 'notified', 'is_notified', 'mailed', 'sent', 'email_sent', 'notified_at', 'status' ] ),
		];

		$info = [
			'table'   => $table,
			'rows'    => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '`' ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- table name.
			'columns' => $columns,
			'problem' => '',
		];

		if ( ! $columns['id'] || ! $columns['email'] || ! $columns['product'] ) {
			/* translators: %s: the columns found. */
			$info['problem'] = sprintf( __( 'The table\'s columns are not the ones expected (%s). Bring the list over from Products For Home → Wachtlijst instead.', 'pfh-widgets' ), implode( ', ', $names ) );
		}

		return $info;
	}

	/**
	 * Take over the old theme's waitlist from this database's own table, in
	 * the same rows the remote import takes — so a sign-up brought over either
	 * way is never brought over twice.
	 *
	 * @return array{added:int, skipped:int}|WP_Error
	 */
	public static function import_from_table() {
		global $wpdb;

		$info = self::legacy_table_info();

		if ( ! $info || '' !== $info['problem'] ) {
			return new WP_Error( 'pfh_waitlist_legacy', $info['problem'] ?? __( 'There is no old waitlist here.', 'pfh-widgets' ) );
		}

		self::maybe_install();

		$c      = $info['columns'];
		$select = [];

		foreach ( [ 'id', 'email', 'product', 'date', 'mailed' ] as $key ) {
			$select[] = $c[ $key ] ? '`' . esc_sql( $c[ $key ] ) . '`' : "''";
		}

		$rows = $wpdb->get_results( 'SELECT ' . implode( ', ', $select ) . ' FROM `' . esc_sql( $info['table'] ) . '` ORDER BY 1', ARRAY_N ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- names read from the table itself.

		foreach ( $rows as &$row ) {
			$mailed = strtolower( trim( (string) $row[4] ) );

			// A date in a "notified_at" column, or a flag, or a status word.
			$row[4] = '' !== $mailed && ! in_array( $mailed, [ '0', '0000-00-00 00:00:00', 'pending', 'waiting', 'new', 'subscribed', 'active', 'no' ], true );

			if ( is_numeric( $row[3] ) && (int) $row[3] > 100000000 ) {
				$row[3] = wp_date( 'Y-m-d H:i:s', (int) $row[3] );
			}
		}
		unset( $row );

		$result = self::import_rows( $rows );
		update_option( 'pfh_waitlist_last_import', [ 'at' => current_time( 'mysql' ) ] + $result, false );

		return $result;
	}

	/**
	 * The live dashboard posts its list here, once, with the key.
	 */
	public static function ajax_remote_import() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- authorised by the one-time key instead.
		$given = isset( $_POST['key'] ) ? (string) wp_unslash( $_POST['key'] ) : '';
		$rows  = isset( $_POST['rows'] ) ? json_decode( (string) wp_unslash( $_POST['rows'] ), true ) : null;
		// phpcs:enable

		$saved = get_option( self::IMPORT_KEY );

		if ( ! is_array( $saved ) || empty( $saved['key'] ) || (int) $saved['expires'] < time() || ! hash_equals( (string) $saved['key'], $given ) ) {
			wp_send_json_error( [ 'state' => 'key' ], 403 );
		}

		if ( ! is_array( $rows ) ) {
			wp_send_json_error( [ 'state' => 'rows' ], 400 );
		}

		// The key is spent before the work starts: a second post arriving
		// while the first is still importing finds it gone.
		if ( ! delete_option( self::IMPORT_KEY ) ) {
			wp_send_json_error( [ 'state' => 'key' ], 403 );
		}

		self::maybe_install();

		$result = self::import_rows( $rows );
		update_option( 'pfh_waitlist_last_import', [ 'at' => current_time( 'mysql' ) ] + $result, false );

		wp_send_json_success( $result );
	}
}
