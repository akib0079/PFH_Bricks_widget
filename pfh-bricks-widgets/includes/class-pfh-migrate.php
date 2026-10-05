<?php
/**
 * Products For Home → Verhuizen: moving the design to another site.
 *
 * The screen and its buttons. The work is done by three classes loaded only
 * when this screen, its requests or the command line need them:
 * PFH_Widgets_Migrate_Refs (where a design points at things),
 * PFH_Widgets_Migrate_Export and PFH_Widgets_Migrate_Import.
 *
 * The same steps run from WP-CLI, where no request time limit applies:
 *
 *   wp pfh migrate export [--baseline=<date>] [--secrets] [--file=<path>]
 *   wp pfh migrate plan <file>
 *   wp pfh migrate import <file> [--yes] [--skip=<key,key>]
 *   wp pfh migrate undo [--yes]
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Migrate {

	const SLUG  = 'pfh-migrate';
	const NONCE = 'pfh_migrate';

	public static function boot() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 30 );
		add_action( 'admin_post_pfh_migrate_export', [ __CLASS__, 'handle_export' ] );
		add_action( 'admin_post_pfh_migrate_upload', [ __CLASS__, 'handle_upload' ] );
		add_action( 'admin_post_pfh_migrate_undo', [ __CLASS__, 'handle_undo' ] );
		add_action( 'admin_post_pfh_migrate_waitlist', [ __CLASS__, 'handle_waitlist' ] );
		add_action( 'wp_ajax_pfh_migrate_start', [ __CLASS__, 'ajax_start' ] );
		add_action( 'wp_ajax_pfh_migrate_step', [ __CLASS__, 'ajax_step' ] );
		add_action( 'admin_post_pfh_renumber_check', [ __CLASS__, 'handle_renumber_check' ] );
		add_action( 'admin_post_pfh_renumber_undo', [ __CLASS__, 'handle_renumber_undo' ] );
		add_action( 'wp_ajax_pfh_renumber_start', [ __CLASS__, 'ajax_renumber_start' ] );
		add_action( 'wp_ajax_pfh_renumber_step', [ __CLASS__, 'ajax_renumber_step' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			self::load();
			WP_CLI::add_command( 'pfh migrate', 'PFH_Widgets_Migrate_CLI' );
		}
	}

	/**
	 * The working classes.
	 */
	public static function load() {
		require_once PFH_WIDGETS_DIR . 'includes/class-pfh-migrate-refs.php';
		require_once PFH_WIDGETS_DIR . 'includes/class-pfh-migrate-export.php';
		require_once PFH_WIDGETS_DIR . 'includes/class-pfh-migrate-import.php';
		require_once PFH_WIDGETS_DIR . 'includes/class-pfh-renumber.php';
	}

	public static function menu() {
		add_submenu_page(
			PFH_Widgets_Settings::PAGE,
			esc_html__( 'Verhuizen', 'pfh-widgets' ),
			esc_html__( 'Verhuizen', 'pfh-widgets' ),
			PFH_Widgets_Settings::CAP,
			self::SLUG,
			[ __CLASS__, 'render' ]
		);
	}

	/**
	 * @param string $hook Admin page hook.
	 */
	public static function assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which screen this is.
		if ( ! isset( $_GET['page'] ) || self::SLUG !== $_GET['page'] ) {
			return;
		}

		wp_enqueue_style( 'pfh-admin', PFH_WIDGETS_URL . 'assets/css/pfh-admin.css', [], PFH_WIDGETS_VERSION );
		wp_enqueue_script( 'pfh-migrate', PFH_WIDGETS_URL . 'assets/js/pfh-migrate.js', [], PFH_WIDGETS_VERSION, true );
		wp_localize_script(
			'pfh-migrate',
			'pfhMigrate',
			[
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( self::NONCE ),
				'text'  => [
					'confirm' => __( 'Start the import now? Make sure there is a full backup of this site.', 'pfh-widgets' ),
					'running' => __( 'Importing', 'pfh-widgets' ),
					'done'    => __( 'Import finished.', 'pfh-widgets' ),
					'failed'  => __( 'The import stopped:', 'pfh-widgets' ),
					'retry'   => __( 'The server did not answer; trying again…', 'pfh-widgets' ),
				],
			]
		);
	}

	private static function guard() {
		if ( ! current_user_can( PFH_Widgets_Settings::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'pfh-widgets' ), 403 );
		}
	}

	/**
	 * Back to the screen, with a message and maybe a plan to show.
	 *
	 * @param array $args Query args.
	 */
	private static function back( array $args = [] ) {
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=' . self::SLUG ) ) );
		exit;
	}

	// ------------------------------------------------------------------
	// Handlers
	// ------------------------------------------------------------------

	public static function handle_export() {
		self::guard();
		check_admin_referer( self::NONCE );
		self::load();

		$package = PFH_Widgets_Migrate_Export::build(
			[
				'baseline' => isset( $_POST['baseline'] ) ? str_replace( 'T', ' ', sanitize_text_field( wp_unslash( $_POST['baseline'] ) ) ) : '',
				'secrets'  => ! empty( $_POST['secrets'] ),
				'catalog'  => ! empty( $_POST['catalog'] ),
			]
		);

		$json = (string) wp_json_encode( $package );
		$name = PFH_Widgets_Migrate_Export::filename();

		if ( function_exists( 'gzencode' ) ) {
			$json  = (string) gzencode( $json, 9 );
			$name .= '.gz';
		}

		nocache_headers();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $name . '"' );
		header( 'Content-Length: ' . strlen( $json ) );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a file download.
		exit;
	}

	public static function handle_upload() {
		self::guard();
		check_admin_referer( self::NONCE );
		self::load();

		$body = '';

		// A file put in the folder by FTP, chosen from the list.
		$picked = isset( $_POST['picked'] ) ? basename( sanitize_text_field( wp_unslash( $_POST['picked'] ) ) ) : '';

		if ( '' !== $picked ) {
			$file = PFH_Widgets_Migrate_Import::dir() . $picked;

			if ( preg_match( '/^pfh-design-[\w.-]+\.json(\.gz)?$/', $picked ) && is_readable( $file ) ) {
				$body = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}
		} elseif ( ! empty( $_FILES['export']['tmp_name'] ) && is_uploaded_file( $_FILES['export']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$body = (string) file_get_contents( $_FILES['export']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.ValidatedSanitizedInput
		} elseif ( isset( $_FILES['export']['error'] ) && UPLOAD_ERR_INI_SIZE === (int) $_FILES['export']['error'] ) {
			self::back( [ 'pfh_msg' => 'toobig' ] );
		}

		if ( "\x1f\x8b" === substr( $body, 0, 2 ) && function_exists( 'gzdecode' ) ) {
			$body = (string) gzdecode( $body );
		}

		$id = '' !== $body ? PFH_Widgets_Migrate_Import::store( $body ) : new WP_Error( 'pfh_migrate_empty', '' );

		if ( is_wp_error( $id ) ) {
			self::back( [ 'pfh_msg' => 'bad' ] );
		}

		self::back( [ 'plan' => $id ] );
	}

	public static function handle_undo() {
		self::guard();
		check_admin_referer( self::NONCE );
		self::load();

		$result = PFH_Widgets_Migrate_Import::undo();

		self::back( [ 'pfh_msg' => is_wp_error( $result ) ? 'undo_failed' : 'undone' ] );
	}

	public static function handle_waitlist() {
		self::guard();
		check_admin_referer( self::NONCE );

		$result = class_exists( 'PFH_Widgets_Waitlist' ) ? PFH_Widgets_Waitlist::import_from_table() : new WP_Error( 'pfh_waitlist', '' );

		self::back(
			is_wp_error( $result )
				? [ 'pfh_msg' => 'waitlist_failed' ]
				: [ 'pfh_msg' => 'waitlist', 'added' => (int) $result['added'], 'skipped' => (int) $result['skipped'], 'updated' => (int) ( $result['updated'] ?? 0 ) ]
		);
	}

	public static function ajax_start() {
		if ( ! current_user_can( PFH_Widgets_Settings::CAP ) || ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Not allowed.', 'pfh-widgets' ) ], 403 );
		}

		self::load();

		$package   = isset( $_POST['package'] ) ? sanitize_text_field( wp_unslash( $_POST['package'] ) ) : '';
		$selection = isset( $_POST['selection'] ) && is_array( $_POST['selection'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['selection'] ) ) : [];
		$run       = PFH_Widgets_Migrate_Import::start( $package, $selection );

		if ( is_wp_error( $run ) ) {
			wp_send_json_error( [ 'message' => $run->get_error_message() ] );
		}

		wp_send_json_success( self::run_summary( $run ) );
	}

	public static function ajax_step() {
		if ( ! current_user_can( PFH_Widgets_Settings::CAP ) || ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Not allowed.', 'pfh-widgets' ) ], 403 );
		}

		self::load();

		$run = PFH_Widgets_Migrate_Import::run_step();

		if ( is_wp_error( $run ) ) {
			wp_send_json_error( [ 'message' => $run->get_error_message() ] );
		}

		wp_send_json_success( self::run_summary( $run ) );
	}

	/**
	 * What the browser needs to know about a run.
	 *
	 * @param array $run Run.
	 * @return array
	 */
	private static function run_summary( array $run ) {
		$progress = PFH_Widgets_Migrate_Import::progress( $run );

		return [
			'state'  => $run['state'],
			'step'   => $progress['step'],
			'done'   => $progress['done'],
			'total'  => $progress['total'],
			'counts' => $run['counts'],
			'log'    => array_slice( (array) $run['log'], -12 ),
		];
	}

	public static function handle_renumber_check() {
		self::guard();
		check_admin_referer( self::NONCE );
		self::load();

		$ids = PFH_Widgets_Renumber::parse( isset( $_POST['numbers'] ) ? sanitize_textarea_field( wp_unslash( $_POST['numbers'] ) ) : '' );

		if ( $ids ) {
			update_option( PFH_Widgets_Renumber::SURVEY, PFH_Widgets_Renumber::survey( $ids ), false );
		} else {
			delete_option( PFH_Widgets_Renumber::SURVEY );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) . '#pfh-renumber' );
		exit;
	}

	public static function handle_renumber_undo() {
		self::guard();
		check_admin_referer( self::NONCE );
		self::load();

		$result = PFH_Widgets_Renumber::undo();

		if ( is_wp_error( $result ) ) {
			set_transient( 'pfh_renumber_error_' . get_current_user_id(), $result->get_error_message(), 10 * MINUTE_IN_SECONDS );
		}

		self::back( [ 'pfh_msg' => is_wp_error( $result ) ? '' : 'renumbered_undo' ] );
	}

	public static function ajax_renumber_start() {
		if ( ! current_user_can( PFH_Widgets_Settings::CAP ) || ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Not allowed.', 'pfh-widgets' ) ], 403 );
		}

		self::load();

		$run = PFH_Widgets_Renumber::start();

		if ( is_wp_error( $run ) ) {
			wp_send_json_error( [ 'message' => $run->get_error_message() ] );
		}

		delete_option( PFH_Widgets_Renumber::SURVEY );
		wp_send_json_success( self::renumber_summary( $run ) );
	}

	public static function ajax_renumber_step() {
		if ( ! current_user_can( PFH_Widgets_Settings::CAP ) || ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Not allowed.', 'pfh-widgets' ) ], 403 );
		}

		self::load();

		$run = PFH_Widgets_Renumber::run_step();

		if ( is_wp_error( $run ) ) {
			wp_send_json_error( [ 'message' => $run->get_error_message() ] );
		}

		wp_send_json_success( self::renumber_summary( $run ) );
	}

	/**
	 * @param array $run Run.
	 * @return array
	 */
	private static function renumber_summary( array $run ) {
		$progress = PFH_Widgets_Renumber::progress( $run );

		return [
			'state'  => $run['state'],
			'step'   => $progress['step'],
			'done'   => $progress['done'],
			'total'  => $progress['total'],
			'counts' => $run['counts'],
			'log'    => array_slice( (array) $run['log'], -12 ),
		];
	}

	/**
	 * Numbers as short ranges: 1,2,3,7 → "1-3, 7".
	 *
	 * @param int[] $ids Numbers.
	 * @return string
	 */
	private static function ranges( array $ids ) {
		$ids = array_map( 'intval', $ids );
		sort( $ids );

		$out   = [];
		$start = null;
		$prev  = null;

		foreach ( array_merge( $ids, [ null ] ) as $id ) {
			if ( null !== $prev && $id === $prev + 1 ) {
				$prev = $id;
				continue;
			}

			if ( null !== $start ) {
				$out[] = $start === $prev ? (string) $start : $start . '-' . $prev;
			}

			$start = $id;
			$prev  = $id;
		}

		return implode( ', ', $out );
	}

	// ------------------------------------------------------------------
	// Screen
	// ------------------------------------------------------------------

	public static function render() {
		self::guard();
		self::load();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only.
		$plan_id = isset( $_GET['plan'] ) ? preg_replace( '/[^A-Za-z0-9-]/', '', (string) wp_unslash( $_GET['plan'] ) ) : '';
		$msg     = isset( $_GET['pfh_msg'] ) ? sanitize_key( wp_unslash( $_GET['pfh_msg'] ) ) : '';
		// phpcs:enable

		echo '<div class="wrap pfh-settings pfh-migrate">';
		echo '<h1>' . esc_html__( 'Verhuizen', 'pfh-widgets' ) . '</h1>';
		echo '<p class="pfh-settings__intro">' . esc_html__( 'Moves the design — templates, page layouts, category and product content, menus and settings — from the site it was made on to another. Orders, customers, loyalty points and stock are never in the export and are never touched.', 'pfh-widgets' ) . '</p>';

		self::message( $msg );

		if ( $plan_id ) {
			$package = PFH_Widgets_Migrate_Import::load( $plan_id );

			if ( $package ) {
				self::render_plan( $plan_id, PFH_Widgets_Migrate_Import::plan( $package ) );
			} else {
				echo '<div class="pfh-migrate__box pfh-migrate__box--error"><p>' . esc_html__( 'That export is no longer here. Upload it again.', 'pfh-widgets' ) . '</p></div>';
			}
		}

		self::render_last_run();
		self::render_export();
		self::render_import();
		self::render_waitlist();
		self::render_renumber();

		echo '</div>';
	}

	/**
	 * @param string $msg Message key.
	 */
	private static function message( $msg ) {
		$messages = [
			'bad'             => [ 'error', __( 'That file is not a design export from this plugin.', 'pfh-widgets' ) ],
			'toobig'          => [ 'error', __( 'The file is bigger than this server accepts as an upload. Put it in wp-content/uploads/pfh-migrate/ by FTP and choose it from the list below.', 'pfh-widgets' ) ],
			'undone'          => [ 'success', __( 'The last import has been undone.', 'pfh-widgets' ) ],
			'undo_failed'     => [ 'error', __( 'The last import could not be undone. Restore the backup instead.', 'pfh-widgets' ) ],
			'waitlist_failed' => [ 'error', __( 'The old theme\'s waitlist could not be read.', 'pfh-widgets' ) ],
			'renumbered_undo' => [ 'success', __( 'The numbers have been put back as they were.', 'pfh-widgets' ) ],
		];

		if ( 'waitlist' === $msg ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$added   = isset( $_GET['added'] ) ? absint( $_GET['added'] ) : 0;
			$skipped = isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0;
			$updated = isset( $_GET['updated'] ) ? absint( $_GET['updated'] ) : 0;
			// phpcs:enable

			/* translators: 1: added, 2: marked as mailed, 3: skipped. */
			$messages['waitlist'] = [ 'success', sprintf( __( 'Waitlist taken over: %1$d added, %2$d marked as already mailed, %3$d already here or not usable.', 'pfh-widgets' ), $added, $updated, $skipped ) ];
		}

		if ( isset( $messages[ $msg ] ) ) {
			// Not a WordPress .notice: notice-hiding plugins sweep those away.
			printf( '<div class="pfh-migrate__box pfh-migrate__box--%1$s"><p>%2$s</p></div>', esc_attr( $messages[ $msg ][0] ), esc_html( $messages[ $msg ][1] ) );
		}
	}

	private static function render_export() {
		?>
		<div class="pfh-settings__section">
			<h2><?php esc_html_e( '1. Export — on the site the design was made on', 'pfh-widgets' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="pfh_migrate_export">
				<table class="form-table" role="presentation"><tbody>
					<tr>
						<th scope="row"><label for="pfh-migrate-baseline"><?php esc_html_e( 'Copy taken on', 'pfh-widgets' ); ?></label></th>
						<td>
							<input type="datetime-local" id="pfh-migrate-baseline" name="baseline" value="<?php echo esc_attr( str_replace( ' ', 'T', substr( PFH_Widgets_Migrate_Export::DEFAULT_BASELINE, 0, 16 ) ) ); ?>">
							<p class="description"><?php esc_html_e( 'When this site was copied from the one the design moves to. What was made here after it is new; what is older exists there too.', 'pfh-widgets' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Product catalogue', 'pfh-widgets' ); ?></th>
						<td>
							<label class="pfh-settings__toggle"><input type="checkbox" name="catalog" value="1"> <span><?php esc_html_e( 'Include every product as it is here: texts, photos, tags, attributes, categories', 'pfh-widgets' ); ?></span></label>
							<p class="description"><?php esc_html_e( 'For when the products were edited here too. Stock, sales and reviews stay as they are on the other site.', 'pfh-widgets' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'API keys', 'pfh-widgets' ); ?></th>
						<td><label class="pfh-settings__toggle"><input type="checkbox" name="secrets" value="1"> <span><?php esc_html_e( 'Include them (the file then has to be kept safe)', 'pfh-widgets' ); ?></span></label></td>
					</tr>
				</tbody></table>
				<?php submit_button( __( 'Download export', 'pfh-widgets' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	private static function render_import() {
		$files = array_map( 'basename', (array) glob( PFH_Widgets_Migrate_Import::dir() . 'pfh-design-*.json*' ) );
		?>
		<div class="pfh-settings__section">
			<h2><?php esc_html_e( '2. Import — on the site the design moves to', 'pfh-widgets' ); ?></h2>
			<p class="pfh-settings__intro"><?php esc_html_e( 'Choose the export. You get a check first: what would be added and changed, and anything that does not match. Nothing is written until you start it.', 'pfh-widgets' ); ?></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="pfh_migrate_upload">
				<p><input type="file" name="export" accept=".json,.gz"></p>
				<?php if ( $files ) : ?>
					<p>
						<label for="pfh-migrate-picked"><?php esc_html_e( 'Or a file put in wp-content/uploads/pfh-migrate/:', 'pfh-widgets' ); ?></label>
						<select id="pfh-migrate-picked" name="picked">
							<option value=""><?php esc_html_e( '— none —', 'pfh-widgets' ); ?></option>
							<?php foreach ( $files as $file ) : ?>
								<option value="<?php echo esc_attr( $file ); ?>"><?php echo esc_html( $file ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				<?php endif; ?>
				<?php submit_button( __( 'Check', 'pfh-widgets' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param string $id   Stored export id.
	 * @param array  $plan Plan.
	 */
	private static function render_plan( $id, array $plan ) {
		$groups = [
			'templates' => __( 'Templates', 'pfh-widgets' ),
			'pages'     => __( 'Pages and their layouts', 'pfh-widgets' ),
			'terms'     => __( 'Categories and brands', 'pfh-widgets' ),
			'products'  => __( 'Products', 'pfh-widgets' ),
			'menus'     => __( 'Menus', 'pfh-widgets' ),
			'settings'  => __( 'Settings', 'pfh-widgets' ),
		];
		$actions = [
			'create'  => __( 'add', 'pfh-widgets' ),
			'update'  => __( 'update', 'pfh-widgets' ),
			'replace' => __( 'replace', 'pfh-widgets' ),
		];
		?>
		<div class="pfh-settings__section pfh-migrate__plan">
			<h2><?php esc_html_e( 'Check', 'pfh-widgets' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: 1: site, 2: date, 3: plugin version. */
					esc_html__( 'Export from %1$s, made %2$s with plugin %3$s.', 'pfh-widgets' ),
					'<strong>' . esc_html( (string) ( $plan['source']['home'] ?? '' ) ) . '</strong>',
					esc_html( (string) $plan['created'] ),
					esc_html( (string) $plan['plugin'] )
				);
				echo ' ';
				printf(
					/* translators: 1: date, 2: post id. */
					esc_html__( 'The copy was taken %1$s; numbers up to %2$d are taken to mean the same thing on both sites.', 'pfh-widgets' ),
					esc_html( (string) ( $plan['baseline']['date'] ?? '' ) ),
					(int) ( $plan['baseline']['max_post_id'] ?? 0 )
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1-4: image counts, 5-7: file counts. */
					esc_html__( 'Images: %1$d already here, %2$d to register, %3$d to fetch, %4$d under a new name. Other files: %5$d here, %6$d to fetch, %7$d under a new name.', 'pfh-widgets' ),
					(int) $plan['images']['reuse'],
					(int) $plan['images']['add'],
					(int) $plan['images']['download'],
					(int) $plan['images']['clash'],
					(int) $plan['files']['present'],
					(int) $plan['files']['download'],
					(int) $plan['files']['clash']
				);
				?>
			</p>

			<?php foreach ( [ 'problems' => 'error', 'warnings' => 'warning' ] as $key => $level ) : ?>
				<?php if ( ! empty( $plan[ $key ] ) ) : ?>
					<div class="pfh-migrate__box pfh-migrate__box--<?php echo esc_attr( $level ); ?>"><ul>
						<?php foreach ( $plan[ $key ] as $line ) : ?>
							<li><?php echo esc_html( $line ); ?></li>
						<?php endforeach; ?>
					</ul></div>
				<?php endif; ?>
			<?php endforeach; ?>

			<form id="pfh-migrate-run" data-pfh-run data-start="pfh_migrate_start" data-step="pfh_migrate_step" data-package="<?php echo esc_attr( $id ); ?>">
				<?php foreach ( $groups as $group => $label ) : ?>
					<?php
					$items = array_filter(
						$plan['items'],
						static function ( $item ) use ( $group ) {
							return $item['group'] === $group;
						}
					);

					if ( ! $items ) {
						continue;
					}
					?>
					<h3><?php echo esc_html( $label ); ?> <span class="count">(<?php echo (int) count( $items ); ?>)</span></h3>
					<table class="widefat striped"><tbody>
						<?php foreach ( $items as $item ) : ?>
							<tr>
								<td style="width:2em"><input type="checkbox" name="selection[]" value="<?php echo esc_attr( $item['key'] ); ?>" checked></td>
								<td><strong><?php echo esc_html( $item['label'] ); ?></strong><br><span class="description"><?php echo esc_html( $item['detail'] ); ?></span></td>
								<td><?php echo esc_html( $actions[ $item['action'] ] ?? $item['action'] ); ?><?php echo $item['target'] ? ' #' . (int) $item['target'] : ''; ?></td>
								<td class="description"><?php echo esc_html( $item['note'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody></table>
				<?php endforeach; ?>

				<h3><?php esc_html_e( 'Before and after', 'pfh-widgets' ); ?></h3>
				<ul class="ul-disc">
					<?php foreach ( $plan['reminders'] as $line ) : ?>
						<li><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>

				<p>
					<button type="submit" class="button button-primary" <?php disabled( ! empty( $plan['problems'] ) ); ?>><?php esc_html_e( 'Start the import', 'pfh-widgets' ); ?></button>
				</p>
				<div class="pfh-migrate__progress" hidden>
					<p class="pfh-migrate__status"></p>
					<progress max="100" value="0" style="width:100%"></progress>
					<ul class="pfh-migrate__log"></ul>
				</div>
			</form>
		</div>
		<?php
	}

	private static function render_last_run() {
		$run = get_option( PFH_Widgets_Migrate_Import::RUN );

		if ( ! is_array( $run ) || empty( $run['id'] ) ) {
			return;
		}

		$states = [
			'running' => __( 'not finished — the page was closed or the server stopped answering', 'pfh-widgets' ),
			'done'    => __( 'finished', 'pfh-widgets' ),
			'stopped' => __( 'stopped half way, and replaced by a later import', 'pfh-widgets' ),
			'undone'  => __( 'undone', 'pfh-widgets' ),
		];
		?>
		<div class="pfh-settings__section">
			<h2><?php esc_html_e( 'Last import', 'pfh-widgets' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: 1: date, 2: state. */
					esc_html__( 'Started %1$s — %2$s.', 'pfh-widgets' ),
					esc_html( (string) $run['started'] ),
					esc_html( $states[ $run['state'] ] ?? (string) $run['state'] )
				);
				?>
			</p>
			<?php if ( ! empty( $run['counts'] ) ) : ?>
				<p class="description">
					<?php
					$parts = [];

					foreach ( $run['counts'] as $what => $count ) {
						$parts[] = $what . ': ' . (int) $count;
					}

					echo esc_html( implode( ' · ', $parts ) );
					?>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $run['log'] ) ) : ?>
				<ul class="ul-disc">
					<?php foreach ( array_slice( (array) $run['log'], -20 ) as $line ) : ?>
						<li class="pfh-migrate__log--<?php echo esc_attr( $line['level'] ); ?>"><?php echo esc_html( $line['text'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( 'running' === $run['state'] ) : ?>
				<div id="pfh-migrate-continue" data-step="pfh_migrate_step">
					<p><button type="button" class="button button-primary"><?php esc_html_e( 'Carry on with this import', 'pfh-widgets' ); ?></button></p>
					<div class="pfh-migrate__progress" hidden>
						<p class="pfh-migrate__status"></p>
						<progress max="100" value="0" style="width:100%"></progress>
						<ul class="pfh-migrate__log"></ul>
					</div>
				</div>
			<?php endif; ?>
			<?php if ( in_array( $run['state'], [ 'done', 'running', 'stopped' ], true ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Undo this import? Everything it added or changed goes back as it was.', 'pfh-widgets' ) ); ?>');">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="action" value="pfh_migrate_undo">
					<?php
					/* translators: %s: when the import started. */
					submit_button( sprintf( __( 'Undo the import of %s', 'pfh-widgets' ), (string) $run['started'] ), 'delete', 'submit', false );
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_renumber() {
		$survey = get_option( PFH_Widgets_Renumber::SURVEY );
		$run    = get_option( PFH_Widgets_Renumber::RUN );
		$error  = get_transient( 'pfh_renumber_error_' . get_current_user_id() );

		if ( $error ) {
			delete_transient( 'pfh_renumber_error_' . get_current_user_id() );
		}
		?>
		<div class="pfh-settings__section" id="pfh-renumber">
			<h2><?php esc_html_e( '4. Make room for another site\'s orders', 'pfh-widgets' ); ?></h2>
			<p class="pfh-settings__intro"><?php esc_html_e( 'An order import that keeps the order numbers skips every order whose number this site already uses for something of its own: a revision, an image, a template. Paste the order numbers the import needs. The check shows what is on them and where it is used, and changes nothing. Starting it deletes the revisions on those numbers and moves everything else to numbers of its own, with every place that names it. After that the order import can be run again.', 'pfh-widgets' ); ?></p>

			<?php if ( $error ) : ?>
				<div class="pfh-migrate__box pfh-migrate__box--error"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="pfh_renumber_check">
				<p><textarea name="numbers" rows="3" class="large-text code" placeholder="13711-13720, 13723, 13748"><?php echo esc_textarea( is_array( $survey ) ? self::ranges( (array) $survey['ids'] ) : '' ); ?></textarea></p>
				<?php submit_button( __( 'Check these numbers', 'pfh-widgets' ), 'secondary', 'submit', false ); ?>
			</form>

			<?php if ( is_array( $survey ) && ! empty( $survey['ids'] ) ) : ?>
				<p>
					<?php
					printf(
						/* translators: 1-6: counts. */
						esc_html__( '%1$d numbers: %2$d free, %3$d already orders, %4$d revisions and %5$d empty order placeholders to delete, %6$d posts to move.', 'pfh-widgets' ),
						count( $survey['ids'] ),
						count( $survey['free'] ),
						count( $survey['orders'] ),
						count( $survey['revisions'] ),
						count( $survey['orphans'] ),
						count( $survey['move'] )
					);
					?>
				</p>

				<?php if ( $survey['move'] ) : ?>
					<table class="widefat striped"><tbody>
						<?php foreach ( $survey['move'] as $item ) : ?>
							<tr>
								<td>#<?php echo (int) $item['id']; ?></td>
								<td><?php echo esc_html( $item['type'] ); ?></td>
								<td><?php echo esc_html( '' !== (string) $item['title'] ? $item['title'] : '—' ); ?></td>
								<td><?php echo esc_html( $item['status'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody></table>
				<?php endif; ?>

				<?php foreach ( [ 'refs' => [ 'success', __( 'Places that name them, and are repointed:', 'pfh-widgets' ) ], 'unhandled' => [ 'warning', __( 'Places that mention the number but are not repointed (look at these before starting):', 'pfh-widgets' ) ] ] as $key => $box ) : ?>
					<?php if ( ! empty( $survey[ $key ] ) ) : ?>
						<div class="pfh-migrate__box pfh-migrate__box--<?php echo esc_attr( $box[0] ); ?>">
							<p><?php echo esc_html( $box[1] ); ?></p>
							<ul>
								<?php foreach ( $survey[ $key ] as $where => $count ) : ?>
									<li><?php echo esc_html( $where . ' × ' . (int) $count ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>

				<?php if ( $survey['revisions'] || $survey['orphans'] || $survey['move'] ) : ?>
					<form id="pfh-renumber-run" data-pfh-run data-start="pfh_renumber_start" data-step="pfh_renumber_step" data-confirm="<?php echo esc_attr( __( 'Delete these revisions and move these posts to new numbers now? Make sure there is a full backup of this site.', 'pfh-widgets' ) ); ?>">
						<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Make room', 'pfh-widgets' ); ?></button></p>
						<div class="pfh-migrate__progress" hidden>
							<p class="pfh-migrate__status"></p>
							<progress max="100" value="0" style="width:100%"></progress>
							<ul class="pfh-migrate__log"></ul>
						</div>
					</form>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( is_array( $run ) && ! empty( $run['id'] ) ) : ?>
				<h3><?php esc_html_e( 'Last time', 'pfh-widgets' ); ?></h3>
				<p class="description">
					<?php
					$parts = [ (string) $run['started'], (string) $run['state'] ];

					foreach ( (array) $run['counts'] as $what => $count ) {
						$parts[] = $what . ': ' . (int) $count;
					}

					echo esc_html( implode( ' · ', $parts ) );
					?>
				</p>
				<?php if ( ! empty( $run['map'] ) ) : ?>
					<p class="description">
						<?php
						$pairs = [];

						foreach ( (array) $run['map'] as $old => $new ) {
							$pairs[] = $old . '→' . $new;
						}

						echo esc_html( implode( ', ', $pairs ) );
						?>
					</p>
				<?php endif; ?>
				<?php if ( 'running' === $run['state'] ) : ?>
					<div id="pfh-renumber-continue" data-step="pfh_renumber_step">
						<p><button type="button" class="button button-primary"><?php esc_html_e( 'Carry on', 'pfh-widgets' ); ?></button></p>
						<div class="pfh-migrate__progress" hidden>
							<p class="pfh-migrate__status"></p>
							<progress max="100" value="0" style="width:100%"></progress>
							<ul class="pfh-migrate__log"></ul>
						</div>
					</div>
				<?php endif; ?>
				<?php if ( in_array( $run['state'], [ 'done', 'running' ], true ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Put every post back on its old number?', 'pfh-widgets' ) ); ?>');">
						<?php wp_nonce_field( self::NONCE ); ?>
						<input type="hidden" name="action" value="pfh_renumber_undo">
						<?php submit_button( __( 'Undo', 'pfh-widgets' ), 'delete', 'submit', false ); ?>
					</form>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_waitlist() {
		if ( ! class_exists( 'PFH_Widgets_Waitlist' ) ) {
			return;
		}

		$found = PFH_Widgets_Waitlist::legacy_table_info();

		if ( ! $found ) {
			return;
		}
		?>
		<div class="pfh-settings__section">
			<h2><?php esc_html_e( '3. Waitlist from the old theme', 'pfh-widgets' ); ?></h2>
			<p class="pfh-settings__intro">
				<?php
				printf(
					/* translators: 1: row count, 2: table. */
					esc_html__( 'This site still has the old theme\'s waitlist: %1$d sign-ups in %2$s. Take them over once, after the switch; sign-ups already here are not added twice.', 'pfh-widgets' ),
					(int) $found['rows'],
					'<code>' . esc_html( $found['table'] ) . '</code>'
				);
				?>
			</p>
			<p class="description">
				<?php
				$c = $found['columns'];
				/* translators: 1-5: column names, 6: all columns. */
				printf( esc_html__( 'Read as: number %1$s, address %2$s, product %3$s, date %4$s, mailed %5$s. All columns: %6$s.', 'pfh-widgets' ), esc_html( $c['id'] ? $c['id'] : '—' ), esc_html( $c['email'] ? $c['email'] : '—' ), esc_html( $c['product'] ? $c['product'] : '—' ), esc_html( $c['date'] ? $c['date'] : '—' ), esc_html( $c['mailed'] ? $c['mailed'] : '—' ), esc_html( implode( ', ', (array) ( $found['names'] ?? [] ) ) ) );
				?>
			</p>
			<?php if ( ! empty( $found['problem'] ) ) : ?>
				<div class="pfh-migrate__box pfh-migrate__box--warning"><p><?php echo esc_html( $found['problem'] ); ?></p></div>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( self::NONCE ); ?>
					<input type="hidden" name="action" value="pfh_migrate_waitlist">
					<?php submit_button( __( 'Take over the waitlist', 'pfh-widgets' ), 'secondary', 'submit', false ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Move the design between sites from the command line.
	 */
	class PFH_Widgets_Migrate_CLI {

		/**
		 * Write a design export.
		 *
		 * ## OPTIONS
		 *
		 * [--baseline=<date>]
		 * : When the copy was taken. Default 2026-08-23 21:30:00.
		 *
		 * [--secrets]
		 * : Include API keys.
		 *
		 * [--catalog]
		 * : Include the product catalogue (texts, photos, tags, attributes).
		 *
		 * [--file=<path>]
		 * : Where to write it. Default: the current folder.
		 *
		 * @param array $args  Positional.
		 * @param array $assoc Named.
		 */
		public function export( $args, $assoc ) {
			$package = PFH_Widgets_Migrate_Export::build(
				[
					'baseline' => (string) ( $assoc['baseline'] ?? '' ),
					'secrets'  => ! empty( $assoc['secrets'] ),
					'catalog'  => ! empty( $assoc['catalog'] ),
				]
			);

			$file = (string) ( $assoc['file'] ?? PFH_Widgets_Migrate_Export::filename() );

			file_put_contents( $file, wp_json_encode( $package ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

			WP_CLI::success(
				sprintf(
					'%s: %d templates and pages, %d categories and brands, %d products, %d menus, %d images, %d files.',
					$file,
					count( $package['posts'] ),
					count( $package['terms'] ),
					count( $package['products'] ),
					count( $package['menus'] ),
					count( $package['attachments'] ),
					count( $package['files'] )
				)
			);

			foreach ( $package['notes'] as $note ) {
				WP_CLI::warning( $note );
			}
		}

		/**
		 * Show what importing an export would do. Writes nothing.
		 *
		 * ## OPTIONS
		 *
		 * <file>
		 * : The export.
		 *
		 * @param array $args Positional.
		 */
		public function plan( $args ) {
			$plan = PFH_Widgets_Migrate_Import::plan( self::read( $args[0] ) );

			WP_CLI\Utils\format_items( 'table', $plan['items'], [ 'key', 'label', 'action', 'target', 'note' ] );
			WP_CLI::log( 'Images: ' . wp_json_encode( $plan['images'] ) . '  Files: ' . wp_json_encode( $plan['files'] ) );

			foreach ( $plan['problems'] as $line ) {
				WP_CLI::error( $line, false );
			}

			foreach ( $plan['warnings'] as $line ) {
				WP_CLI::warning( $line );
			}
		}

		/**
		 * Import an export.
		 *
		 * ## OPTIONS
		 *
		 * <file>
		 * : The export.
		 *
		 * [--skip=<keys>]
		 * : Plan item keys to leave out, comma separated.
		 *
		 * [--yes]
		 * : Do not ask.
		 *
		 * @param array $args  Positional.
		 * @param array $assoc Named.
		 */
		public function import( $args, $assoc ) {
			$body = (string) file_get_contents( $args[0] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$id   = PFH_Widgets_Migrate_Import::store( "\x1f\x8b" === substr( $body, 0, 2 ) ? (string) gzdecode( $body ) : $body );

			if ( is_wp_error( $id ) ) {
				WP_CLI::error( $id->get_error_message() );
			}

			$plan = PFH_Widgets_Migrate_Import::plan( PFH_Widgets_Migrate_Import::load( $id ) );
			$skip = array_filter( array_map( 'trim', explode( ',', (string) ( $assoc['skip'] ?? '' ) ) ) );
			$keys = array_values( array_diff( wp_list_pluck( $plan['items'], 'key' ), $skip ) );

			WP_CLI::confirm( sprintf( 'Import %d items into %s?', count( $keys ), home_url() ), $assoc );

			$run = PFH_Widgets_Migrate_Import::start( $id, $keys );

			if ( is_wp_error( $run ) ) {
				WP_CLI::error( $run->get_error_message() );
			}

			while ( is_array( $run ) && 'running' === $run['state'] ) {
				$run = PFH_Widgets_Migrate_Import::run_step();

				if ( is_wp_error( $run ) ) {
					WP_CLI::error( $run->get_error_message() );
				}
			}

			foreach ( (array) $run['log'] as $line ) {
				WP_CLI::log( strtoupper( $line['level'] ) . ' ' . $line['text'] );
			}

			WP_CLI::success( wp_json_encode( $run['counts'] ) );
		}

		/**
		 * Undo the last import.
		 *
		 * ## OPTIONS
		 *
		 * [--yes]
		 * : Do not ask.
		 *
		 * @param array $args  Positional.
		 * @param array $assoc Named.
		 */
		public function undo( $args, $assoc ) {
			WP_CLI::confirm( 'Undo the last import?', $assoc );

			$result = PFH_Widgets_Migrate_Import::undo();

			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
			}

			WP_CLI::success( sprintf( '%d changes undone.', $result['undone'] ) );
		}

		/**
		 * @param string $file Path.
		 * @return array
		 */
		private static function read( $file ) {
			$body    = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$package = json_decode( "\x1f\x8b" === substr( $body, 0, 2 ) ? (string) gzdecode( $body ) : $body, true );

			if ( ! is_array( $package ) ) {
				WP_CLI::error( 'Not a design export.' );
			}

			return $package;
		}
	}
}
