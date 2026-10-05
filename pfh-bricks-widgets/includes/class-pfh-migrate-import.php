<?php
/**
 * The design import: a design export applied to this site.
 *
 * In two halves. plan() reads the export against this site and says what it
 * would do, item by item, and writes nothing. run_step() then does it, in
 * short steps a page in the browser keeps asking for, so a host that stops a
 * request after thirty or sixty seconds never cuts it off half way. Every
 * change is written to a journal first, which is what undo() reads.
 *
 * What it never touches: orders, customers, loyalty points, stock, reviews —
 * none of it is in the file. A page or product that exists here keeps its
 * words, prices and images; it gets the design's layout and fields on top.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Migrate_Import {

	/** The current (or last) run. */
	const RUN = 'pfh_migrate_run';

	/** Earlier runs, newest last, so undo can go back one at a time. */
	const HISTORY = 'pfh_migrate_history';

	/** Folder under uploads for exports and journals. */
	const DIR = 'pfh-migrate';

	/** Steps, in order, and how much of each one request does. */
	const STEPS = [
		'files'       => 12,
		'attachments' => 4,
		'terms'       => 40,
		'cat_terms'   => 1,
		'posts'       => 25,
		'menus'       => 3,
		'layouts'     => 8,
		'products'    => 40,
		'catalog'     => 20,
		'settings'    => 1,
		'finish'      => 1,
	];

	/** @var array|null The run being worked on. */
	private static $run = null;

	/** @var array|null Its export. */
	private static $package = null;

	/** @var array Problems met while renumbering in this request. */
	private static $unresolved = [];

	// ------------------------------------------------------------------
	// Storage
	// ------------------------------------------------------------------

	/**
	 * The folder exports and journals are kept in, made and closed off.
	 *
	 * @return string Absolute path, with a trailing slash.
	 */
	public static function dir() {
		$dir = trailingslashit( (string) wp_get_upload_dir()['basedir'] ) . self::DIR . '/';

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		foreach ( [ 'index.php' => "<?php // Silence.\n", '.htaccess' => "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ] as $name => $body ) {
			if ( ! file_exists( $dir . $name ) ) {
				file_put_contents( $dir . $name, $body ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
		}

		return $dir;
	}

	/**
	 * Keep an uploaded export, after checking it is one.
	 *
	 * @param string $json File contents.
	 * @return string|WP_Error The stored export's id.
	 */
	public static function store( $json ) {
		$package = json_decode( (string) $json, true );

		if ( ! is_array( $package ) || ( $package['format'] ?? '' ) !== PFH_Widgets_Migrate_Export::FORMAT ) {
			return new WP_Error( 'pfh_migrate_format', __( 'This is not a design export from this plugin.', 'pfh-widgets' ) );
		}

		if ( (int) ( $package['version'] ?? 0 ) > PFH_Widgets_Migrate_Export::VERSION ) {
			return new WP_Error( 'pfh_migrate_version', __( 'This export is from a newer version of the plugin. Update the plugin here first.', 'pfh-widgets' ) );
		}

		$id = gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 20, false, false );

		file_put_contents( self::dir() . 'export-' . $id . '.json', $json ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		return $id;
	}

	/**
	 * A stored export.
	 *
	 * @param string $id Export id.
	 * @return array|null
	 */
	public static function load( $id ) {
		$id   = preg_replace( '/[^A-Za-z0-9-]/', '', (string) $id );
		$file = self::dir() . 'export-' . $id . '.json';

		if ( '' === $id || ! is_readable( $file ) ) {
			return null;
		}

		$package = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		return is_array( $package ) ? $package : null;
	}

	// ------------------------------------------------------------------
	// Finding things on this site
	// ------------------------------------------------------------------

	/**
	 * This site's copy of a post the export names.
	 *
	 * The same id counts if it was part of the copy the design was made on
	 * and is still the same kind of thing here; past the copy the number may
	 * be an order, or a page made here since, so there the address has to
	 * agree as well. Then a product's SKU, then the address.
	 *
	 * @param array $item     Post or ref_post from the export.
	 * @param array $baseline The export's baseline: date, max_post_id.
	 * @return int 0 when there is none.
	 */
	public static function find_post( array $item, array $baseline ) {
		$id   = (int) ( $item['source_id'] ?? 0 );
		$type = (string) ( $item['type'] ?? '' );
		$slug = (string) ( $item['slug'] ?? '' );

		if ( $id ) {
			$post = get_post( $id );

			if ( $post && $post->post_type === $type && 'trash' !== $post->post_status ) {
				// The same address settles it. Without it, the number has to
				// be inside the copy, and this site has to have had the post
				// before the copy too: a page made here since is another page.
				$same_address = '' !== $slug && $post->post_name === $slug;
				$from_copy    = $id <= (int) ( $baseline['max_post_id'] ?? 0 ) && $post->post_date <= (string) ( $baseline['date'] ?? '' );

				if ( $same_address || $from_copy ) {
					return (int) $post->ID;
				}
			}
		}

		if ( ! empty( $item['sku'] ) && function_exists( 'wc_get_product_id_by_sku' ) ) {
			$found = (int) wc_get_product_id_by_sku( (string) $item['sku'] );

			if ( $found && get_post_type( $found ) === $type ) {
				return $found;
			}
		}

		$path = (string) ( $item['path'] ?? ( $item['slug'] ?? '' ) );

		if ( '' !== $path ) {
			$post = get_page_by_path( $path, OBJECT, $type );

			if ( $post && 'trash' !== $post->post_status ) {
				return (int) $post->ID;
			}
		}

		return 0;
	}

	/**
	 * This site's term with a taxonomy and slug.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $slug     Slug.
	 * @return int
	 */
	public static function find_term( $taxonomy, $slug ) {
		if ( ! taxonomy_exists( $taxonomy ) || '' === (string) $slug ) {
			return 0;
		}

		$term = get_term_by( 'slug', (string) $slug, $taxonomy );

		return $term && ! is_wp_error( $term ) ? (int) $term->term_id : 0;
	}

	/**
	 * An attachment here with this file.
	 *
	 * @param string $file Path relative to uploads.
	 * @return int
	 */
	public static function find_attachment( $file ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT m.post_id FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = '_wp_attached_file' AND m.meta_value = %s AND p.post_type = 'attachment' ORDER BY m.post_id LIMIT 1", (string) $file ) );
	}

	/**
	 * What state an uploaded file is in here.
	 *
	 * @param string $path Path relative to uploads.
	 * @param string $md5  Its fingerprint on the other site.
	 * @return string present | missing | clash
	 */
	public static function file_state( $path, $md5 ) {
		$full = trailingslashit( (string) wp_get_upload_dir()['basedir'] ) . $path;

		if ( ! file_exists( $full ) ) {
			return 'missing';
		}

		return ( '' === (string) $md5 || md5_file( $full ) === $md5 ) ? 'present' : 'clash';
	}

	// ------------------------------------------------------------------
	// The plan
	// ------------------------------------------------------------------

	/**
	 * What importing this export would do here. Writes nothing.
	 *
	 * @param array $package Export.
	 * @return array
	 */
	public static function plan( array $package ) {
		$max      = (int) ( $package['baseline']['max_post_id'] ?? 0 );
		$base     = (array) ( $package['baseline'] ?? [] );
		$plan     = [
			'source'    => $package['source'] ?? [],
			'created'   => $package['created'] ?? '',
			'plugin'    => $package['plugin'] ?? '',
			'baseline'  => $package['baseline'] ?? [],
			'items'     => [],
			'counts'    => [],
			'images'    => [ 'reuse' => 0, 'add' => 0, 'download' => 0, 'clash' => 0 ],
			'files'     => [ 'present' => 0, 'download' => 0, 'clash' => 0 ],
			'problems'  => [],
			'warnings'  => [],
			'reminders' => [],
		];

		if ( ! defined( 'BRICKS_VERSION' ) ) {
			$plan['problems'][] = __( 'Bricks is not the active theme here. Install and activate Bricks first, then check again: the templates are Bricks posts, and only Bricks knows them.', 'pfh-widgets' );
		}

		$here = untrailingslashit( home_url() );

		if ( untrailingslashit( (string) ( $package['source']['home'] ?? '' ) ) === $here ) {
			$plan['warnings'][] = __( 'This export was made on this very site. Importing it changes nothing worth having.', 'pfh-widgets' );
		}

		foreach ( (array) ( $package['posts'] ?? [] ) as $item ) {
			$target = self::find_post( $item, $base );
			$group  = in_array( $item['type'], [ 'bricks_template', 'bricks_fonts' ], true ) ? 'templates' : 'pages';
			$note   = '';

			if ( $target ) {
				$here_post = get_post( $target );
				$notes     = [];

				if ( $here_post && $here_post->post_modified > (string) ( $package['baseline']['date'] ?? '' ) && 'templates' !== $group ) {
					/* translators: %s: date. */
					$notes[] = sprintf( __( 'edited here after the copy (%s); its words stay, its layout is replaced', 'pfh-widgets' ), $here_post->post_modified );
				}

				if ( $here_post && $here_post->post_name !== (string) ( $item['slug'] ?? '' ) ) {
					/* translators: %s: address here. */
					$notes[] = sprintf( __( 'found by its number; here its address is /%s/', 'pfh-widgets' ), get_page_uri( $here_post ) );
				}

				$note = implode( '; ', $notes );
			}

			$plan['items'][] = [
				'key'    => 'post:' . (int) $item['source_id'],
				'group'  => $group,
				'label'  => '' !== (string) $item['title'] ? $item['title'] : $item['path'],
				'detail' => $item['type'] . ' · /' . $item['path'],
				'action' => $target ? 'update' : 'create',
				'target' => $target,
				'note'   => $note,
			];
		}

		foreach ( (array) ( $package['ref_posts'] ?? [] ) as $item ) {
			if ( ! self::find_post( $item, $base ) ) {
				/* translators: 1: post type, 2: title, 3: id. */
				$plan['warnings'][] = sprintf( __( 'Not found here: %1$s "%2$s" (%3$d on the other site). What points at it will point at nothing.', 'pfh-widgets' ), $item['type'], $item['title'], $item['source_id'] );
			}
		}

		foreach ( (array) ( $package['terms'] ?? [] ) as $item ) {
			$target = self::find_term( $item['taxonomy'], $item['slug'] );

			if ( ! taxonomy_exists( $item['taxonomy'] ) ) {
				/* translators: %s: taxonomy. */
				$plan['warnings'][] = sprintf( __( 'The taxonomy %s does not exist here; its terms are skipped.', 'pfh-widgets' ), $item['taxonomy'] );
				continue;
			}

			$plan['items'][] = [
				'key'    => 'term:' . $item['taxonomy'] . ':' . (int) $item['source_id'],
				'group'  => 'terms',
				'label'  => $item['name'],
				'detail' => $item['taxonomy'] . ' · ' . $item['slug'] . ( $item['members'] ? ' · ' . count( $item['members'] ) . ' ' . __( 'products', 'pfh-widgets' ) : '' ),
				'action' => $target ? 'update' : 'create',
				'target' => $target,
				'note'   => '',
			];
		}

		foreach ( (array) ( $package['ref_terms'] ?? [] ) as $item ) {
			if ( 'nav_menu' !== $item['taxonomy'] && ! self::find_term( $item['taxonomy'], $item['slug'] ) ) {
				/* translators: 1: taxonomy, 2: term name. */
				$plan['warnings'][] = sprintf( __( 'Not found here: %1$s "%2$s". What points at it will point at nothing.', 'pfh-widgets' ), $item['taxonomy'], $item['name'] );
			}
		}

		$products = 0;

		foreach ( (array) ( $package['products'] ?? [] ) as $item ) {
			if ( self::find_post( $item + [ 'type' => 'product', 'path' => $item['slug'] ], $base ) ) {
				$products++;
			} else {
				/* translators: %s: product title. */
				$plan['warnings'][] = sprintf( __( 'Product not found here, its extra fields are skipped: %s', 'pfh-widgets' ), $item['title'] );
			}
		}

		if ( ! empty( $package['catalog'] ) ) {
			$found   = 0;
			$missing = [];

			foreach ( (array) $package['catalog'] as $item ) {
				if ( self::find_post( $item + [ 'path' => $item['slug'] ], $base ) || 'post' === $item['type'] ) {
					$found++;
				} elseif ( 'product' === $item['type'] ) {
					$missing[] = $item['title'];
				}
			}

			$plan['items'][] = [
				'key'    => 'group:catalog',
				'group'  => 'products',
				/* translators: %d: count. */
				'label'  => sprintf( _n( 'Catalogue: %d product, variation or blog post', 'Catalogue: %d products, variations and blog posts', $found, 'pfh-widgets' ), $found ),
				'detail' => __( 'texts, photos, tags, attributes and their values, categories, and blog posts new there — as on the other site; stock and sales stay as they are here', 'pfh-widgets' ),
				'action' => 'update',
				'target' => 0,
				'note'   => '',
			];

			if ( $missing ) {
				/* translators: %s: product titles. */
				$plan['warnings'][] = sprintf( __( 'Not in this shop, so not in its catalogue: %s', 'pfh-widgets' ), implode( '; ', array_slice( $missing, 0, 10 ) ) );
			}
		}

		if ( $products ) {
			$plan['items'][] = [
				'key'    => 'group:products',
				'group'  => 'products',
				/* translators: %d: count. */
				'label'  => sprintf( _n( 'Extra fields of %d product', 'Extra fields of %d products', $products, 'pfh-widgets' ), $products ),
				'detail' => __( 'tabs, questions, highlights, ingredients, nutrition', 'pfh-widgets' ),
				'action' => 'update',
				'target' => 0,
				'note'   => '',
			];
		}

		foreach ( (array) ( $package['menus'] ?? [] ) as $menu ) {
			$here_menu = wp_get_nav_menu_object( $menu['slug'] );

			$plan['items'][] = [
				'key'    => 'menu:' . (int) $menu['source_id'],
				'group'  => 'menus',
				'label'  => $menu['name'],
				/* translators: %d: count. */
				'detail' => sprintf( _n( '%d item', '%d items', count( $menu['items'] ), 'pfh-widgets' ), count( $menu['items'] ) ),
				'action' => $here_menu ? 'replace' : 'create',
				'target' => $here_menu ? (int) $here_menu->term_id : 0,
				'note'   => $here_menu ? __( 'the menu here is kept, renamed, unless it is already the same', 'pfh-widgets' ) : '',
			];
		}

		$settings = [
			'group:bricks'   => [ __( 'Bricks settings', 'pfh-widgets' ), __( 'global settings, colours, classes, fonts, breakpoints', 'pfh-widgets' ) ],
			'group:modules'  => [ __( 'Products For Home settings', 'pfh-widgets' ), __( 'reviews, cart, badge, shipping bar, waitlist and the other tabs', 'pfh-widgets' ) ],
			'group:shop'     => [ __( 'Shop settings', 'pfh-widgets' ), __( 'DHL option names, permalinks (Premmerce)', 'pfh-widgets' ) ],
			/* translators: %s: payment methods in order. */
			'group:payments' => [ __( 'Order of the payment methods', 'pfh-widgets' ), sprintf( __( 'as on the other site: %s', 'pfh-widgets' ), implode( ', ', (array) ( $package['gateways'] ?? [] ) ) ) ],
			'group:front'    => [ __( 'Front page and shop pages', 'pfh-widgets' ), __( 'which page is the home page, the blog, cart, checkout, account, terms, privacy', 'pfh-widgets' ) ],
			'group:theme'    => [ __( 'Theme settings', 'pfh-widgets' ), __( 'logo, menu locations', 'pfh-widgets' ) ],
		];

		foreach ( $settings as $key => $label ) {
			$plan['items'][] = [
				'key'    => $key,
				'group'  => 'settings',
				'label'  => $label[0],
				'detail' => $label[1],
				'action' => 'update',
				'target' => 0,
				'note'   => '',
			];
		}

		foreach ( (array) ( $package['patches'] ?? [] ) as $patch ) {
			if ( false === get_option( $patch['option'], false ) ) {
				/* translators: %s: option name. */
				$plan['warnings'][] = sprintf( __( 'Shop setting %s does not exist here; skipped. Is the DHL shipping zone set up here?', 'pfh-widgets' ), $patch['option'] );
			}
		}

		foreach ( (array) ( $package['attachments'] ?? [] ) as $item ) {
			if ( (int) $item['source_id'] <= $max && (string) get_post_meta( (int) $item['source_id'], '_wp_attached_file', true ) === $item['file'] ) {
				$plan['images']['reuse']++;
			} elseif ( self::find_attachment( $item['file'] ) && 'clash' !== self::file_state( $item['file'], $item['md5'] ) ) {
				$plan['images']['reuse']++;
			} else {
				$state = self::file_state( $item['file'], $item['md5'] );

				if ( 'missing' === $state ) {
					$plan['images']['download']++;
				} elseif ( 'clash' === $state ) {
					$plan['images']['clash']++;
				} else {
					$plan['images']['add']++;
				}
			}
		}

		foreach ( (array) ( $package['files'] ?? [] ) as $path => $file ) {
			$state = self::file_state( $path, $file['md5'] );

			if ( 'present' === $state ) {
				$plan['files']['present']++;
			} elseif ( 'missing' === $state ) {
				$plan['files']['download']++;
			} else {
				$plan['files']['clash']++;
			}
		}

		if ( $plan['files']['clash'] || $plan['images']['clash'] ) {
			$plan['warnings'][] = __( 'Some file names exist here with different content. Those come in under a new name, and the design is pointed at the new name.', 'pfh-widgets' );
		}

		foreach ( (array) ( $package['notes'] ?? [] ) as $note ) {
			$plan['warnings'][] = $note;
		}

		if ( ! empty( $package['baseline']['examples'] ) ) {
			/* translators: 1: count, 2: examples. */
			$plan['warnings'][] = sprintf( __( 'On the other site, %1$d posts are dated on the wrong side of the copy, so their numbers are only trusted where the address agrees too. For example: %2$s', 'pfh-widgets' ), (int) $package['baseline']['misfits'], implode( '; ', array_slice( (array) $package['baseline']['examples'], 0, 6 ) ) );
		}

		$theme = (string) ( $package['theme_mods']['stylesheet'] ?? '' );

		if ( '' !== $theme && get_stylesheet() !== $theme ) {
			/* translators: 1: theme there, 2: theme here. */
			$plan['warnings'][] = sprintf( __( 'The design was made with the theme "%1$s"; here "%2$s" is active. Install and activate "%1$s" first, or the logo and menu places go to a theme that is not in use.', 'pfh-widgets' ), $theme, get_stylesheet() );
		}

		$plan['reminders'] = [
			__( 'Make a full backup of this site before you start.', 'pfh-widgets' ),
			__( 'Products For Home licence: enter a token made for this domain under Products For Home → Licentie.', 'pfh-widgets' ),
			__( 'API keys are not in the file (unless the export said so): enter them under Products For Home.', 'pfh-widgets' ),
			__( 'Afterwards: Bricks → Settings → Performance → "Regenerate CSS files" (when Bricks writes its CSS to files), then clear the server cache (LiteSpeed), then check the home page, a category, a product, the checkout and My Account.', 'pfh-widgets' ),
			__( 'Waitlist: once the import is done, take over the old theme\'s sign-ups at the bottom of this screen.', 'pfh-widgets' ),
		];

		// Bricks signs code elements with this site's own keys: signed on the
		// other site, they do not run here until they are signed again.
		if ( false !== strpos( (string) wp_json_encode( $package['posts'] ?? [] ), '"signature"' ) ) {
			$plan['reminders'][] = __( 'The design has code elements: Bricks → Settings → Custom code → "Regenerate code signatures", or they do not run here.', 'pfh-widgets' );
		}

		foreach ( $plan['items'] as $item ) {
			$plan['counts'][ $item['group'] ] = ( $plan['counts'][ $item['group'] ] ?? 0 ) + 1;
		}

		return $plan;
	}

	// ------------------------------------------------------------------
	// The run
	// ------------------------------------------------------------------

	/**
	 * Start importing an export.
	 *
	 * @param string   $package_id Stored export id.
	 * @param string[] $selection  Plan item keys to import.
	 * @return array|WP_Error The run.
	 */
	public static function start( $package_id, array $selection ) {
		$package = self::load( $package_id );

		if ( ! $package ) {
			return new WP_Error( 'pfh_migrate_missing', __( 'The export is gone. Upload it again.', 'pfh-widgets' ) );
		}

		$plan = self::plan( $package );

		if ( $plan['problems'] ) {
			return new WP_Error( 'pfh_migrate_blocked', implode( ' ', $plan['problems'] ) );
		}

		$current = get_option( self::RUN );

		if ( is_array( $current ) && 'running' === ( $current['state'] ?? '' ) && ( time() - (int) ( $current['touched'] ?? 0 ) ) < 10 * MINUTE_IN_SECONDS ) {
			return new WP_Error( 'pfh_migrate_busy', __( 'An import is already running.', 'pfh-widgets' ) );
		}

		// The run before this one stays undoable, after this one is undone.
		if ( is_array( $current ) && in_array( $current['state'] ?? '', [ 'done', 'running' ], true ) ) {
			$history   = get_option( self::HISTORY, [] );
			$history   = is_array( $history ) ? $history : [];
			$history[] = [ 'state' => 'done' === $current['state'] ? 'done' : 'stopped' ] + array_intersect_key( $current, array_flip( [ 'id', 'package', 'started', 'finished', 'journal', 'counts', 'log' ] ) );

			update_option( self::HISTORY, array_slice( $history, -5 ), false );
		}

		// Two runs in one second must not share a journal, and its name must
		// not be guessable: it holds the values the import replaced.
		$run_id = gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 20, false, false );
		$run    = [
			'id'        => $run_id,
			'package'   => preg_replace( '/[^A-Za-z0-9-]/', '', (string) $package_id ),
			'selection' => array_values( array_unique( array_map( 'strval', $selection ) ) ),
			'state'     => 'running',
			'step'      => 0,
			'cursor'    => 0,
			'map'       => [ 'post' => [], 'attachment' => [], 'term' => [], 'file' => [] ],
			'counts'    => [],
			'log'       => [],
			'unresolved'=> [],
			'started'   => current_time( 'mysql' ),
			'touched'   => time(),
			'finished'  => '',
			'journal'   => 'journal-' . $run_id . '.jsonl',
		];

		file_put_contents( self::dir() . $run['journal'], '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		self::$run     = $run;
		self::$package = $package;

		self::map_references();
		self::save();

		return self::$run;
	}

	/**
	 * Do one short piece of the run.
	 *
	 * @return array|WP_Error The run.
	 */
	public static function run_step() {
		$run = get_option( self::RUN );

		if ( ! is_array( $run ) || 'running' !== ( $run['state'] ?? '' ) ) {
			return new WP_Error( 'pfh_migrate_idle', __( 'No import is running.', 'pfh-widgets' ) );
		}

		$package = self::load( $run['package'] );

		if ( ! $package ) {
			return new WP_Error( 'pfh_migrate_missing', __( 'The export is gone.', 'pfh-widgets' ) );
		}

		self::$run        = $run;
		self::$package    = $package;
		self::$unresolved = [];

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$steps = array_keys( self::STEPS );
		$name  = $steps[ self::$run['step'] ] ?? '';

		if ( '' === $name ) {
			self::$run['state'] = 'done';
			self::save();

			return self::$run;
		}

		$items = self::step_items( $name );
		$batch = array_slice( $items, (int) self::$run['cursor'], self::STEPS[ $name ] );

		$method = 'do_' . $name;

		// The run is saved after every item, not after the batch: should the
		// host cut a request off half way, the next one carries on after the
		// last item done instead of doing it — and making a page — twice.
		foreach ( $batch as $item ) {
			try {
				self::$method( $item );
			} catch ( Throwable $e ) {
				self::log( 'error', $name . ': ' . $e->getMessage() );
			}

			self::$run['cursor']++;
			self::$run['touched'] = time();
			self::save();
		}

		if ( self::$run['cursor'] >= count( $items ) ) {
			self::$run['step']++;
			self::$run['cursor'] = 0;

			if ( self::$run['step'] >= count( $steps ) ) {
				self::$run['state']    = 'done';
				self::$run['finished'] = current_time( 'mysql' );
			}
		}

		foreach ( self::$unresolved as $what ) {
			self::$run['unresolved'][ $what ] = true;
		}

		self::$run['touched'] = time();
		self::save();

		return self::$run;
	}

	/**
	 * Where the run is, for a progress bar.
	 *
	 * @param array $run Run.
	 * @return array{step:string, done:int, total:int}
	 */
	public static function progress( array $run ) {
		$steps = array_keys( self::STEPS );

		return [
			'step'  => $steps[ $run['step'] ] ?? 'done',
			'done'  => (int) $run['step'],
			'total' => count( $steps ),
		];
	}

	/**
	 * The units a step works through.
	 *
	 * @param string $name Step.
	 * @return array
	 */
	private static function step_items( $name ) {
		$p = self::$package;

		switch ( $name ) {
			case 'files':
				return array_keys( (array) ( $p['files'] ?? [] ) );

			case 'attachments':
				return array_values( (array) ( $p['attachments'] ?? [] ) );

			case 'terms':
				$terms = array_values(
					array_filter(
						(array) ( $p['terms'] ?? [] ),
						static function ( $t ) {
							return self::chosen( 'term:' . $t['taxonomy'] . ':' . (int) $t['source_id'] ) && taxonomy_exists( $t['taxonomy'] );
						}
					)
				);

				// Parents before their children.
				usort(
					$terms,
					static function ( $a, $b ) {
						return ( '' === $a['parent'] ? 0 : 1 ) - ( '' === $b['parent'] ? 0 : 1 );
					}
				);

				return $terms;

			case 'posts':
			case 'layouts':
				$posts = array_values(
					array_filter(
						(array) ( $p['posts'] ?? [] ),
						static function ( $item ) {
							return self::chosen( 'post:' . (int) $item['source_id'] );
						}
					)
				);

				usort(
					$posts,
					static function ( $a, $b ) {
						return substr_count( (string) $a['path'], '/' ) - substr_count( (string) $b['path'], '/' );
					}
				);

				return $posts;

			case 'menus':
				return array_values(
					array_filter(
						(array) ( $p['menus'] ?? [] ),
						static function ( $menu ) {
							return self::chosen( 'menu:' . (int) $menu['source_id'] );
						}
					)
				);

			case 'products':
				return self::chosen( 'group:products' ) ? array_values( (array) ( $p['products'] ?? [] ) ) : [];

			case 'cat_terms':
				return self::chosen( 'group:catalog' ) && ! empty( $p['catalog'] ) ? [ 'cat_terms' ] : [];

			case 'catalog':
				return self::chosen( 'group:catalog' ) ? array_values( (array) ( $p['catalog'] ?? [] ) ) : [];

			default:
				return [ $name ];
		}
	}

	/**
	 * Was this plan item ticked?
	 *
	 * @param string $key Item key.
	 * @return bool
	 */
	private static function chosen( $key ) {
		return in_array( $key, (array) self::$run['selection'], true );
	}

	/**
	 * Map what the export refers to but does not move: existing products,
	 * pages, categories. Done once, at the start.
	 */
	private static function map_references() {
		$base = (array) ( self::$package['baseline'] ?? [] );

		foreach ( (array) ( self::$package['ref_posts'] ?? [] ) as $item ) {
			$found = self::find_post( $item, $base );

			if ( $found ) {
				self::$run['map']['post'][ (int) $item['source_id'] ] = $found;
			}
		}

		foreach ( (array) ( self::$package['ref_terms'] ?? [] ) as $item ) {
			$found = 'nav_menu' === $item['taxonomy'] ? 0 : self::find_term( $item['taxonomy'], $item['slug'] );

			if ( $found ) {
				self::$run['map']['term'][ $item['taxonomy'] ][ (int) $item['source_id'] ] = $found;
			}
		}

		// Moved posts and terms that already exist here are known now too, so
		// a layout written before its neighbour is created still finds it.
		foreach ( (array) ( self::$package['posts'] ?? [] ) as $item ) {
			$found = self::find_post( $item, $base );

			if ( $found ) {
				self::$run['map']['post'][ (int) $item['source_id'] ] = $found;
			}
		}

		foreach ( (array) ( self::$package['terms'] ?? [] ) as $item ) {
			$found = self::find_term( $item['taxonomy'], $item['slug'] );

			if ( $found ) {
				self::$run['map']['term'][ $item['taxonomy'] ][ (int) $item['source_id'] ] = $found;
			}
		}

		foreach ( (array) ( self::$package['catalog'] ?? [] ) as $item ) {
			$found = self::find_post( $item + [ 'path' => $item['slug'] ], $base );

			if ( $found ) {
				self::$run['map']['post'][ (int) $item['source_id'] ] = $found;
			}
		}
	}

	// ------------------------------------------------------------------
	// Steps
	// ------------------------------------------------------------------

	/**
	 * Make sure an uploaded file the design mentions is here.
	 *
	 * @param string $path Path relative to uploads.
	 */
	private static function do_files( $path ) {
		$file = self::$package['files'][ $path ] ?? [];
		$got  = self::ensure_file( (string) $path, (string) ( $file['md5'] ?? '' ) );

		if ( $got && $got !== $path ) {
			self::$run['map']['file'][ $path ] = $got;
		}
	}

	/**
	 * An image: the one already here, or a new attachment for its file.
	 *
	 * @param array $item Attachment from the export.
	 */
	private static function do_attachments( array $item ) {
		$max = (int) ( self::$package['baseline']['max_post_id'] ?? 0 );
		$id  = (int) $item['source_id'];

		if ( $id <= $max && (string) get_post_meta( $id, '_wp_attached_file', true ) === $item['file'] && 'attachment' === get_post_type( $id ) ) {
			self::$run['map']['attachment'][ $id ] = $id;
			self::count( 'images_reused' );

			return;
		}

		// The files step may already have brought this file in under a new name.
		if ( isset( self::$run['map']['file'][ $item['file'] ] ) ) {
			$item['file_here'] = self::$run['map']['file'][ $item['file'] ];
		}

		$state = isset( $item['file_here'] ) ? 'present' : self::file_state( $item['file'], $item['md5'] );
		$found = isset( $item['file_here'] ) ? 0 : self::find_attachment( $item['file'] );

		if ( $found && 'clash' !== $state ) {
			self::$run['map']['attachment'][ $id ] = $found;
			self::count( 'images_reused' );

			return;
		}

		$path = isset( $item['file_here'] ) ? $item['file_here'] : self::ensure_file( $item['file'], $item['md5'] );

		if ( ! $path ) {
			return;
		}

		if ( $path !== $item['file'] ) {
			self::$run['map']['file'][ $item['file'] ] = $path;

			// Brought in under that name by an earlier import, record and all.
			$earlier = self::find_attachment( $path );

			if ( $earlier ) {
				self::$run['map']['attachment'][ $id ] = $earlier;
				self::count( 'images_reused' );

				return;
			}
		}

		$full = trailingslashit( (string) wp_get_upload_dir()['basedir'] ) . $path;

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$new = wp_insert_attachment(
			[
				'post_mime_type' => (string) $item['mime'],
				'post_title'     => (string) $item['title'],
				'post_excerpt'   => (string) $item['caption'],
				'post_status'    => 'inherit',
			],
			$full,
			0,
			true
		);

		if ( is_wp_error( $new ) ) {
			self::log( 'error', $item['file'] . ': ' . $new->get_error_message() );

			return;
		}

		self::journal( [ 'type' => 'attachment', 'id' => (int) $new, 'downloaded' => 'missing' === $state || 'clash' === $state ] );

		if ( '' !== (string) $item['alt'] ) {
			update_post_meta( $new, '_wp_attachment_image_alt', wp_slash( (string) $item['alt'] ) );
		}

		$meta = wp_generate_attachment_metadata( $new, $full );

		if ( $meta ) {
			wp_update_attachment_metadata( $new, $meta );
		}

		self::$run['map']['attachment'][ $id ] = (int) $new;
		self::count( 'images_added' );
	}

	/**
	 * A category with its own content, or a brand with its products.
	 *
	 * @param array $item Term from the export.
	 */
	private static function do_terms( array $item ) {
		$taxonomy = $item['taxonomy'];
		$target   = self::find_term( $taxonomy, $item['slug'] );

		if ( ! $target ) {
			$parent = '' !== $item['parent'] ? self::find_term( $taxonomy, $item['parent'] ) : 0;
			$made   = wp_insert_term(
				$item['name'],
				$taxonomy,
				[
					'slug'        => $item['slug'],
					'description' => (string) $item['description'],
					'parent'      => $parent,
				]
			);

			if ( is_wp_error( $made ) ) {
				self::log( 'error', $taxonomy . ' ' . $item['slug'] . ': ' . $made->get_error_message() );

				return;
			}

			$target = (int) $made['term_id'];

			self::journal( [ 'type' => 'term', 'id' => $target, 'taxonomy' => $taxonomy ] );
			self::count( 'terms_added' );
		}

		self::$run['map']['term'][ $taxonomy ][ (int) $item['source_id'] ] = $target;

		foreach ( (array) $item['meta'] as $key => $value ) {
			if ( class_exists( 'PFH_Widgets_Collection' ) && PFH_Widgets_Collection::META === $key && is_array( $value ) ) {
				$value = PFH_Widgets_Migrate_Refs::collection( $value, [ __CLASS__, 'resolve' ] );
			} else {
				$value = PFH_Widgets_Migrate_Refs::walk( $value, [ __CLASS__, 'resolve' ] );
			}

			self::set_meta( 'term', $target, $key, $value );
		}

		foreach ( (array) $item['members'] as $member ) {
			$product = (int) ( self::$run['map']['post'][ (int) $member ] ?? 0 );

			if ( ! $product || has_term( $target, $taxonomy, $product ) ) {
				continue;
			}

			$added = wp_set_object_terms( $product, [ $target ], $taxonomy, true );

			if ( ! is_wp_error( $added ) ) {
				self::journal( [ 'type' => 'relation', 'object' => $product, 'term' => $target, 'taxonomy' => $taxonomy ] );
			}
		}

		if ( class_exists( 'PFH_Widgets_Collection' ) ) {
			clean_term_cache( $target, $taxonomy );
		}
	}

	/**
	 * A post the design needs: found here, or made, without its layout yet.
	 *
	 * @param array $item Post from the export.
	 */
	private static function do_posts( array $item ) {
		$source = (int) $item['source_id'];

		if ( ! empty( self::$run['map']['post'][ $source ] ) ) {
			return;
		}

		$parent = $item['parent'] ? (int) ( self::$run['map']['post'][ (int) $item['parent'] ] ?? 0 ) : 0;

		$new = wp_insert_post(
			wp_slash(
				[
					'post_type'    => $item['type'],
					'post_status'  => in_array( $item['status'], [ 'publish', 'private' ], true ) ? $item['status'] : 'draft',
					'post_title'   => (string) $item['title'],
					'post_name'    => (string) $item['slug'],
					'post_parent'  => $parent,
					'menu_order'   => (int) $item['order'],
					'post_content' => is_string( $item['content'] ) ? (string) self::resolve( 'url', $item['content'] ) : '',
					'post_excerpt' => is_string( $item['excerpt'] ) ? (string) $item['excerpt'] : '',
				]
			),
			true
		);

		if ( is_wp_error( $new ) ) {
			self::log( 'error', $item['path'] . ': ' . $new->get_error_message() );

			return;
		}

		self::journal( [ 'type' => 'post', 'id' => (int) $new ] );
		self::$run['map']['post'][ $source ] = (int) $new;
		self::count( 'posts_added' );

		foreach ( (array) $item['terms'] as $taxonomy => $slugs ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				wp_set_object_terms( (int) $new, array_map( 'strval', (array) $slugs ), $taxonomy, false );
			}
		}
	}

	/**
	 * A menu: kept when it is already the same, else made new beside the old.
	 *
	 * @param array $menu Menu from the export.
	 */
	private static function do_menus( array $menu ) {
		$items = [];

		foreach ( $menu['items'] as $item ) {
			$items[] = self::menu_item_target( $item );
		}

		$here = wp_get_nav_menu_object( $menu['slug'] );

		if ( $here && self::same_menu( (int) $here->term_id, $menu, $items ) ) {
			self::$run['map']['term']['nav_menu'][ (int) $menu['source_id'] ] = (int) $here->term_id;

			return;
		}

		if ( $here ) {
			$suffix = ' (' . __( 'before the move', 'pfh-widgets' ) . ' ' . gmdate( 'Y-m-d' ) . ')';

			wp_update_term(
				(int) $here->term_id,
				'nav_menu',
				[
					'name' => $here->name . $suffix,
					'slug' => $here->slug . '-before-move-' . gmdate( 'Ymd-His' ),
				]
			);

			self::journal( [ 'type' => 'menu_renamed', 'id' => (int) $here->term_id, 'name' => $here->name, 'slug' => $here->slug ] );
		}

		$made = wp_create_nav_menu( $menu['name'] );

		if ( is_wp_error( $made ) ) {
			self::log( 'error', $menu['name'] . ': ' . $made->get_error_message() );

			return;
		}

		wp_update_term( (int) $made, 'nav_menu', [ 'slug' => $menu['slug'], 'description' => (string) $menu['description'] ] );
		self::journal( [ 'type' => 'menu', 'id' => (int) $made ] );

		$ids = [];

		foreach ( $menu['items'] as $i => $item ) {
			$target = $items[ $i ];
			$args   = [
				'menu-item-title'       => $item['title'],
				'menu-item-description' => $item['description'],
				'menu-item-attr-title'  => $item['attr_title'],
				'menu-item-type'        => $item['type'],
				'menu-item-object'      => $item['object'],
				'menu-item-object-id'   => $target['object_id'],
				'menu-item-url'         => $target['url'],
				'menu-item-target'      => $item['target'],
				'menu-item-classes'     => implode( ' ', (array) $item['classes'] ),
				'menu-item-xfn'         => $item['xfn'],
				'menu-item-position'    => (int) $item['order'],
				'menu-item-parent-id'   => (int) ( $ids[ (int) $item['parent'] ] ?? 0 ),
				'menu-item-status'      => 'publish',
			];

			$new_item = wp_update_nav_menu_item( (int) $made, 0, wp_slash( $args ) );

			if ( ! is_wp_error( $new_item ) ) {
				$ids[ (int) $item['source_id'] ] = (int) $new_item;
			}
		}

		self::$run['map']['term']['nav_menu'][ (int) $menu['source_id'] ] = (int) $made;
		self::count( 'menus_added' );
	}

	/**
	 * Where a menu item points on this site.
	 *
	 * @param array $item Item from the export.
	 * @return array{object_id:int, url:string}
	 */
	private static function menu_item_target( array $item ) {
		// A custom link's "object" is the item itself: it gets its own number.
		$object_id = 'custom' === $item['type'] ? 0 : (int) $item['object_id'];

		if ( 'post_type' === $item['type'] && $object_id ) {
			$object_id = (int) self::resolve( 'post', $object_id );
		} elseif ( 'taxonomy' === $item['type'] && $object_id ) {
			$object_id = (int) self::resolve( 'term:' . $item['object'], $object_id );
		}

		return [
			'object_id' => $object_id,
			'url'       => '' !== (string) $item['url'] ? (string) self::resolve( 'url', $item['url'] ) : '',
		];
	}

	/**
	 * Is the menu here already the same, item for item?
	 *
	 * @param int   $menu_id Menu here.
	 * @param array $menu    The export's menu.
	 * @param array $targets Where its items point here.
	 * @return bool
	 */
	private static function same_menu( $menu_id, array $menu, array $targets ) {
		$here = wp_get_nav_menu_items( $menu_id, [ 'post_status' => 'any' ] );

		if ( ! is_array( $here ) || count( $here ) !== count( $menu['items'] ) ) {
			return false;
		}

		foreach ( array_values( $here ) as $i => $item ) {
			$want = $menu['items'][ $i ];
			$post = get_post( $item->ID );

			if ( ! $post || $post->post_title !== $want['title'] || $item->type !== $want['type'] ) {
				return false;
			}

			if ( 'custom' !== $want['type'] && (int) $item->object_id !== (int) $targets[ $i ]['object_id'] ) {
				return false;
			}

			if ( 'custom' === $want['type'] && untrailingslashit( (string) $item->url ) !== untrailingslashit( $targets[ $i ]['url'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * A post's layout and design fields, renumbered for this site.
	 *
	 * @param array $item Post from the export.
	 */
	private static function do_layouts( array $item ) {
		$target = (int) ( self::$run['map']['post'][ (int) $item['source_id'] ] ?? 0 );

		if ( ! $target ) {
			return;
		}

		foreach ( (array) $item['meta'] as $key => $value ) {
			self::set_meta( 'post', $target, $key, PFH_Widgets_Migrate_Refs::post_meta( $key, $value, [ __CLASS__, 'resolve' ] ) );
		}

		clean_post_cache( $target );
		self::count( 'layouts' );
	}

	/**
	 * A product's extra fields.
	 *
	 * @param array $item Product from the export.
	 */
	private static function do_products( array $item ) {
		$base   = (array) ( self::$package['baseline'] ?? [] );
		$target = self::find_post( $item + [ 'type' => 'product', 'path' => $item['slug'] ], $base );

		if ( ! $target ) {
			return;
		}

		self::$run['map']['post'][ (int) $item['source_id'] ] = $target;

		foreach ( (array) $item['meta'] as $key => $value ) {
			self::set_meta( 'post', $target, $key, PFH_Widgets_Migrate_Refs::product_meta( $key, $value, [ __CLASS__, 'resolve' ] ) );
		}

		self::count( 'products' );
	}

	/**
	 * The catalogue's attributes and terms: made where missing, renamed where
	 * the other site names them otherwise. Parents before their children.
	 */
	private static function do_cat_terms() {
		foreach ( (array) ( self::$package['attributes'] ?? [] ) as $attr ) {
			$id = (int) wc_attribute_taxonomy_id_by_name( $attr['name'] );

			if ( ! $id ) {
				$made = wc_create_attribute(
					[
						'name'         => $attr['label'],
						'slug'         => $attr['name'],
						'type'         => $attr['type'],
						'order_by'     => $attr['orderby'],
						'has_archives' => (bool) $attr['public'],
					]
				);

				if ( is_wp_error( $made ) ) {
					self::log( 'error', $attr['name'] . ': ' . $made->get_error_message() );
					continue;
				}

				self::journal( [ 'type' => 'attribute_created', 'id' => (int) $made ] );
				register_taxonomy( wc_attribute_taxonomy_name( $attr['name'] ), [ 'product' ], [ 'hierarchical' => false, 'show_ui' => false, 'query_var' => false, 'rewrite' => false ] );
				continue;
			}

			$here = wc_get_attribute( $id );
			$want = [ 'name' => $attr['label'], 'slug' => $attr['name'], 'type' => $attr['type'], 'order_by' => $attr['orderby'], 'has_archives' => (bool) $attr['public'] ];

			if ( $here && ( $here->name !== $want['name'] || $here->type !== $want['type'] || $here->order_by !== $want['order_by'] || (bool) $here->has_archives !== $want['has_archives'] ) ) {
				self::journal( [ 'type' => 'attribute_updated', 'id' => $id, 'old' => [ 'name' => $here->name, 'slug' => $here->slug === wc_attribute_taxonomy_name( $attr['name'] ) ? $attr['name'] : $here->slug, 'type' => $here->type, 'order_by' => $here->order_by, 'has_archives' => (bool) $here->has_archives ] ] );
				wc_update_attribute( $id, $want );
			}
		}

		delete_transient( 'wc_attribute_taxonomies' );

		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
		}

		$terms = (array) ( self::$package['cat_terms'] ?? [] );

		usort(
			$terms,
			static function ( $a, $b ) {
				return ( '' === $a['parent'] ? 0 : 1 ) - ( '' === $b['parent'] ? 0 : 1 );
			}
		);

		foreach ( $terms as $item ) {
			$taxonomy = $item['taxonomy'];

			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$parent = '' !== $item['parent'] ? (int) self::find_term( $taxonomy, $item['parent'] ) : 0;
			$here   = get_term_by( 'slug', $item['slug'], $taxonomy );

			if ( ! $here ) {
				$made = wp_insert_term( $item['name'], $taxonomy, [ 'slug' => $item['slug'], 'description' => (string) $item['description'], 'parent' => $parent ] );

				if ( is_wp_error( $made ) ) {
					self::log( 'error', $taxonomy . ' ' . $item['slug'] . ': ' . $made->get_error_message() );
					continue;
				}

				$id = (int) $made['term_id'];
				self::journal( [ 'type' => 'term', 'id' => $id, 'taxonomy' => $taxonomy ] );
				self::count( 'terms_added' );
			} else {
				$id = (int) $here->term_id;

				if ( $here->name !== $item['name'] || $here->description !== (string) $item['description'] || (int) $here->parent !== $parent ) {
					self::journal( [ 'type' => 'term_update', 'id' => $id, 'taxonomy' => $taxonomy, 'name' => $here->name, 'description' => $here->description, 'parent' => (int) $here->parent ] );
					wp_update_term( $id, $taxonomy, [ 'name' => $item['name'], 'description' => (string) $item['description'], 'parent' => $parent ] );
					self::count( 'terms_renamed' );
				}
			}

			if ( '' !== (string) $item['order'] ) {
				self::set_meta( 'term', $id, 'order', (string) $item['order'] );
			}

			self::$run['map']['term'][ $taxonomy ][ (int) $item['source_id'] ] = $id;
		}
	}

	/**
	 * One product or variation as the other site has it: its text, its own
	 * fields and its terms. Stock, sales and what other services keep on it
	 * were never in the file; whether it is out of stock stays this site's.
	 *
	 * @param array $item Product from the catalogue.
	 */
	private static function do_catalog( array $item ) {
		global $wpdb;

		$base   = (array) ( self::$package['baseline'] ?? [] );
		$target = (int) ( self::$run['map']['post'][ (int) $item['source_id'] ] ?? 0 );
		$target = $target ? $target : self::find_post( $item + [ 'path' => $item['slug'] ], $base );
		$post   = $target ? get_post( $target ) : null;

		// A blog post written there and not here yet: made, as it is there.
		if ( ! $post && 'post' === $item['type'] ) {
			$made = wp_insert_post(
				wp_slash(
					[
						'post_type'      => 'post',
						'post_status'    => (string) $item['status'],
						'post_title'     => (string) $item['title'],
						'post_name'      => (string) $item['slug'],
						'post_content'   => (string) self::resolve( 'url', (string) $item['content'] ),
						'post_excerpt'   => (string) self::resolve( 'url', (string) $item['excerpt'] ),
						'post_date'      => (string) $item['date'],
						'post_date_gmt'  => (string) $item['date_gmt'],
						'post_author'    => get_userdata( (int) $item['author'] ) ? (int) $item['author'] : get_current_user_id(),
						'comment_status' => (string) $item['comments'],
					]
				),
				true
			);

			if ( is_wp_error( $made ) ) {
				self::log( 'error', $item['slug'] . ': ' . $made->get_error_message() );

				return;
			}

			self::journal( [ 'type' => 'post', 'id' => (int) $made ] );
			self::count( 'posts_added' );

			$target = (int) $made;
			$post   = get_post( $target );
		}

		if ( ! $post || $post->post_type !== $item['type'] ) {
			self::count( 'catalog_skipped' );

			return;
		}

		self::$run['map']['post'][ (int) $item['source_id'] ] = $target;

		$want = [
			'post_title'   => (string) $item['title'],
			'post_content' => (string) self::resolve( 'url', (string) $item['content'] ),
			'post_excerpt' => (string) self::resolve( 'url', (string) $item['excerpt'] ),
			'menu_order'   => (int) $item['order'],
		];
		$change = [];

		foreach ( $want as $field => $value ) {
			if ( (string) $post->$field !== (string) $value ) {
				$change[ $field ] = $value;
			}
		}

		if ( $change ) {
			self::journal( [ 'type' => 'post_fields', 'id' => $target, 'old' => array_intersect_key( $post->to_array(), $change ) ] );
			$wpdb->update( $wpdb->posts, $change, [ 'ID' => $target ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			clean_post_cache( $target );
		}

		foreach ( (array) $item['meta'] as $key => $value ) {
			self::set_meta( 'post', $target, (string) $key, PFH_Widgets_Migrate_Refs::catalog_meta( (string) $key, $value, [ __CLASS__, 'resolve' ] ) );
		}

		$oos = taxonomy_exists( 'product_visibility' ) ? get_term_by( 'slug', 'outofstock', 'product_visibility' ) : null;

		foreach ( (array) $item['terms'] as $taxonomy => $slugs ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$ids = [];

			foreach ( (array) $slugs as $slug ) {
				$term = get_term_by( 'slug', $slug, $taxonomy );

				if ( $term ) {
					$ids[] = (int) $term->term_id;
				}
			}

			$have = wp_get_object_terms( $target, $taxonomy, [ 'fields' => 'ids' ] );
			$have = is_wp_error( $have ) ? [] : array_map( 'intval', $have );

			// Out of stock follows this shop's stock, not the other site's.
			if ( 'product_visibility' === $taxonomy && $oos ) {
				$ids = array_values( array_diff( $ids, [ (int) $oos->term_id ] ) );

				if ( in_array( (int) $oos->term_id, $have, true ) ) {
					$ids[] = (int) $oos->term_id;
				}
			}

			sort( $ids );
			sort( $have );

			if ( $ids === $have ) {
				continue;
			}

			self::journal( [ 'type' => 'terms_set', 'object' => $target, 'taxonomy' => $taxonomy, 'old' => $have ] );
			wp_set_object_terms( $target, $ids, $taxonomy, false );
		}

		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( 'product_variation' === $item['type'] ? (int) $post->post_parent : $target );
		}

		self::count( 'catalog' );
	}

	/**
	 * The settings, all in one go.
	 */
	private static function do_settings() {
		$p = self::$package;

		if ( self::chosen( 'group:bricks' ) ) {
			foreach ( (array) ( $p['options'] ?? [] ) as $name => $value ) {
				if ( 0 === strpos( $name, 'bricks_' ) ) {
					self::set_option( $name, PFH_Widgets_Migrate_Refs::walk( $value, [ __CLASS__, 'resolve' ] ) );
				}
			}
		}

		if ( self::chosen( 'group:shop' ) ) {
			foreach ( (array) ( $p['options'] ?? [] ) as $name => $value ) {
				if ( 0 === strpos( $name, 'premmerce' ) ) {
					self::set_option( $name, PFH_Widgets_Migrate_Refs::walk( $value, [ __CLASS__, 'resolve' ] ) );
				}
			}

			foreach ( (array) ( $p['patches'] ?? [] ) as $patch ) {
				$current = get_option( $patch['option'], null );

				if ( ! is_array( $current ) ) {
					continue;
				}

				self::set_option( $patch['option'], array_merge( $current, (array) $patch['set'] ) );
			}

		}

		if ( self::chosen( 'group:payments' ) ) {
			self::gateway_order( (array) ( $p['gateways'] ?? [] ) );
		}

		if ( self::chosen( 'group:modules' ) ) {
			foreach ( (array) ( $p['modules'] ?? [] ) as $name => $module ) {
				$value   = PFH_Widgets_Migrate_Refs::module_option( (array) $module['value'], (array) $module['media'], [ __CLASS__, 'resolve' ] );
				$current = get_option( $name, [] );

				// What the file leaves out — an API key — keeps what is here.
				self::set_option( $name, array_merge( is_array( $current ) ? $current : [], $value ) );
			}
		}

		if ( self::chosen( 'group:theme' ) && ! empty( $p['theme_mods']['value'] ) ) {
			$name    = 'theme_mods_' . (string) $p['theme_mods']['stylesheet'];
			$current = get_option( $name, [] );
			$value   = PFH_Widgets_Migrate_Refs::theme_mods( (array) $p['theme_mods']['value'], [ __CLASS__, 'resolve' ] );

			self::set_option( $name, array_merge( is_array( $current ) ? $current : [], $value ) );
		}

		if ( self::chosen( 'group:front' ) ) {
			foreach ( (array) ( $p['post_opts'] ?? [] ) as $name => $source ) {
				$kind = 'site_icon' === $name ? 'attachment' : 'post';

				// Only a page this site has: never point the home page at a
				// number that means something else here.
				if ( isset( self::$run['map'][ $kind ][ (int) $source ] ) ) {
					self::set_option( $name, (int) self::$run['map'][ $kind ][ (int) $source ] );
				}
			}

			if ( ! empty( $p['options']['show_on_front'] ) && ( 'page' !== $p['options']['show_on_front'] || (int) get_option( 'page_on_front' ) ) ) {
				self::set_option( 'show_on_front', (string) $p['options']['show_on_front'] );
			}
		}
	}

	/**
	 * Put the payment methods in the design's order — each on a number of
	 * its own. WooCommerce files a gateway under its number, so two on the
	 * same number means one of them disappears from the checkout.
	 *
	 * @param string[] $wanted Gateway ids, in order.
	 */
	private static function gateway_order( array $wanted ) {
		if ( ! $wanted || ! function_exists( 'WC' ) ) {
			return;
		}

		$order = get_option( 'woocommerce_gateway_order', [] );
		$order = is_array( $order ) ? $order : [];
		$known = function_exists( 'WC' ) && WC()->payment_gateways() ? array_keys( WC()->payment_gateways()->payment_gateways() ) : [];
		$known = array_merge( $known, array_keys( $order ) );

		$wanted = array_values( array_filter( $wanted, static function ( $id ) use ( $known ) {
			return in_array( $id, $known, true );
		} ) );

		if ( ! $wanted ) {
			return;
		}

		$others = array_diff_key( $order, array_flip( $wanted ) );
		$next   = $others ? max( array_map( 'intval', $others ) ) + 1 : 0;

		foreach ( $wanted as $id ) {
			$order[ $id ] = $next++;
		}

		self::set_option( 'woocommerce_gateway_order', $order );
	}

	/**
	 * Tidy up: caches that still hold the old site.
	 */
	private static function do_finish() {
		if ( class_exists( 'PFH_Widgets_Helpers' ) && defined( 'PFH_Widgets_Helpers::CAT_CACHE' ) ) {
			delete_transient( PFH_Widgets_Helpers::CAT_CACHE );
		}

		wp_cache_flush();
		flush_rewrite_rules( false );

		$unresolved = array_keys( (array) self::$run['unresolved'] );

		if ( $unresolved ) {
			/* translators: %s: references. */
			self::log( 'warning', sprintf( __( 'Left pointing at their old number, because nothing here matches: %s', 'pfh-widgets' ), implode( ', ', array_slice( $unresolved, 0, 40 ) ) ) );
		}

		self::log( 'info', __( 'Done.', 'pfh-widgets' ) );
	}

	// ------------------------------------------------------------------
	// Renumbering
	// ------------------------------------------------------------------

	/**
	 * The visitor for the import walk: each reference becomes this site's.
	 *
	 * @param string $kind  Kind.
	 * @param mixed  $value Value.
	 * @return mixed
	 */
	public static function resolve( $kind, $value ) {
		$map = self::$run['map'] ?? [];

		if ( 'attachment' === $kind || 'post' === $kind ) {
			$id = (int) $value;

			if ( $id <= 0 ) {
				return $value;
			}

			if ( isset( $map[ $kind ][ $id ] ) ) {
				return (int) $map[ $kind ][ $id ];
			}

			self::$unresolved[ $kind . ' ' . $id ] = true;

			return $value;
		}

		if ( 0 === strpos( $kind, 'term:' ) ) {
			$taxonomy = substr( $kind, 5 );
			$id       = (int) $value;

			if ( $id > 0 && isset( $map['term'][ $taxonomy ][ $id ] ) ) {
				return (int) $map['term'][ $taxonomy ][ $id ];
			}

			if ( $id > 0 ) {
				self::$unresolved[ $taxonomy . ' ' . $id ] = true;
			}

			return $value;
		}

		if ( 'url' === $kind ) {
			return self::rewrite_urls( (string) $value );
		}

		return $value;
	}

	/**
	 * The other site's address becomes this one's; a file that came in under
	 * a new name is pointed at by that name, sizes included.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function rewrite_urls( $text ) {
		$from = untrailingslashit( (string) ( self::$package['source']['home'] ?? '' ) );
		$to   = untrailingslashit( home_url() );

		if ( '' !== $from && $from !== $to ) {
			$plain   = [ $from, str_replace( 'https://', 'http://', $from ) ];
			$escaped = array_map(
				static function ( $url ) {
					return str_replace( '/', '\/', $url );
				},
				$plain
			);

			$text = str_replace( array_merge( $plain, $escaped ), [ $to, $to, str_replace( '/', '\/', $to ), str_replace( '/', '\/', $to ) ], $text );
		}

		foreach ( (array) ( self::$run['map']['file'] ?? [] ) as $old => $new ) {
			$old_base = preg_replace( '/\.[a-z0-9]+$/i', '', $old );
			$new_base = preg_replace( '/\.[a-z0-9]+$/i', '', $new );

			$text = str_replace( '/' . $old, '/' . $new, $text );
			$text = preg_replace( '#/' . preg_quote( $old_base, '#' ) . '-(\d+x\d+)\.#', '/' . $new_base . '-$1.', $text );
		}

		return $text;
	}

	// ------------------------------------------------------------------
	// Files
	// ------------------------------------------------------------------

	/**
	 * Make sure a file is here: it already is, or it is fetched from the
	 * other site — under a new name if this site has a different file under
	 * the same one.
	 *
	 * @param string $path Path relative to uploads.
	 * @param string $md5  Fingerprint.
	 * @return string The path it is at here, or '' when it could not be had.
	 */
	public static function ensure_file( $path, $md5 ) {
		$base  = trailingslashit( (string) wp_get_upload_dir()['basedir'] );
		$state = self::file_state( $path, $md5 );

		if ( 'present' === $state ) {
			return $path;
		}

		$dest = $path;

		if ( 'clash' === $state ) {
			$dir    = dirname( $path );
			$prefix = '.' === $dir ? '' : $dir . '/';

			// An earlier import may have brought it in under a new name already.
			$earlier = self::earlier_copy( $path, $md5 );

			if ( '' !== $earlier ) {
				return $earlier;
			}

			$dest = $prefix . wp_unique_filename( $base . $dir, basename( $path ) );
		}

		$tmp = self::fetch( $path );

		if ( is_wp_error( $tmp ) ) {
			self::log( 'error', $path . ': ' . $tmp->get_error_message() );

			return '';
		}

		if ( '' !== $md5 && md5_file( $tmp ) !== $md5 ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			self::log( 'error', $path . ': ' . __( 'the file that arrived is not the one in the export.', 'pfh-widgets' ) );

			return '';
		}

		wp_mkdir_p( dirname( $base . $dest ) );

		if ( ! @rename( $tmp, $base . $dest ) && ! @copy( $tmp, $base . $dest ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
			self::log( 'error', $path . ': ' . __( 'could not be written to the uploads folder.', 'pfh-widgets' ) );

			return '';
		}

		@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink

		self::journal( [ 'type' => 'file', 'path' => $dest, 'md5' => $md5 ] );
		self::count( 'files_fetched' );

		return $dest;
	}

	/**
	 * The same file under the name an earlier import gave it beside one of
	 * this site's: name-1.png, name-2.png …
	 *
	 * @param string $path Path relative to uploads.
	 * @param string $md5  Fingerprint.
	 * @return string Its path, or ''.
	 */
	private static function earlier_copy( $path, $md5 ) {
		if ( '' === $md5 ) {
			return '';
		}

		$base = trailingslashit( (string) wp_get_upload_dir()['basedir'] );
		$info = pathinfo( $path );
		$dir  = '.' === $info['dirname'] ? '' : $info['dirname'] . '/';
		$ext  = isset( $info['extension'] ) ? '.' . $info['extension'] : '';

		for ( $n = 1; $n <= 20; $n++ ) {
			$candidate = $dir . $info['filename'] . '-' . $n . $ext;

			if ( is_file( $base . $candidate ) && md5_file( $base . $candidate ) === $md5 ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Fetch a file from the other site's uploads, or from a folder holding a
	 * copy of them (set with the pfh_migrate_local_source filter).
	 *
	 * @param string $path Path relative to uploads.
	 * @return string|WP_Error Temporary file.
	 */
	private static function fetch( $path ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$local = (string) apply_filters( 'pfh_migrate_local_source', '' );

		if ( '' !== $local && is_readable( trailingslashit( $local ) . $path ) ) {
			$tmp = wp_tempnam( basename( $path ) );

			return copy( trailingslashit( $local ) . $path, $tmp ) ? $tmp : new WP_Error( 'pfh_migrate_copy', __( 'could not be copied from the local folder.', 'pfh-widgets' ) );
		}

		$url = untrailingslashit( (string) ( self::$package['source']['uploads'] ?? '' ) ) . '/' . implode( '/', array_map( 'rawurlencode', explode( '/', $path ) ) );

		return download_url( $url, 60 );
	}

	// ------------------------------------------------------------------
	// Writing, with the journal
	// ------------------------------------------------------------------

	/**
	 * Set meta, noting what was there.
	 *
	 * @param string $type  post | term.
	 * @param int    $id    Object.
	 * @param string $key   Key.
	 * @param mixed  $value Value.
	 */
	private static function set_meta( $type, $id, $key, $value ) {
		$had = metadata_exists( $type, $id, $key );
		$old = $had ? get_metadata( $type, $id, $key, true ) : null;

		// The database gives numbers back as strings: "12" here is 12 there.
		if ( $had && self::same( $old, $value ) ) {
			return;
		}

		self::journal( [ 'type' => 'meta', 'object' => $type, 'id' => (int) $id, 'key' => $key, 'had' => $had, 'old' => $old ] );

		// update_metadata() unslashes what it is given; slash it first, or a
		// backslash in a layout — CSS, an escaped quote — is lost on the way.
		update_metadata( $type, $id, $key, wp_slash( $value ) );
	}

	/**
	 * The same value, but for numbers kept as strings.
	 *
	 * @param mixed $a Value.
	 * @param mixed $b Value.
	 * @return bool
	 */
	private static function same( $a, $b ) {
		if ( is_array( $a ) && is_array( $b ) ) {
			if ( array_keys( $a ) !== array_keys( $b ) ) {
				return false;
			}

			foreach ( $a as $key => $value ) {
				if ( ! self::same( $value, $b[ $key ] ) ) {
					return false;
				}
			}

			return true;
		}

		if ( is_scalar( $a ) && is_scalar( $b ) && is_numeric( $a ) && is_numeric( $b ) ) {
			return (string) $a === (string) $b;
		}

		return $a === $b;
	}

	/**
	 * Set an option, noting what was there.
	 *
	 * @param string $name  Option.
	 * @param mixed  $value Value.
	 */
	private static function set_option( $name, $value ) {
		$missing = new stdClass();
		$old     = get_option( $name, $missing );
		$had     = $old !== $missing;

		if ( $had && self::same( $old, $value ) ) {
			return;
		}

		self::journal( [ 'type' => 'option', 'name' => $name, 'had' => $had, 'old' => $had ? $old : null ] );
		update_option( $name, $value );
		self::count( 'settings' );
	}

	/**
	 * Note a change before (or as) it is made.
	 *
	 * @param array $entry Change.
	 */
	private static function journal( array $entry ) {
		// One line per change, added to the end: nothing already written is
		// ever rewritten, so a request cut off leaves every earlier line whole.
		file_put_contents( self::dir() . self::$run['journal'], wp_json_encode( $entry ) . "\n", FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * A run's journal, oldest change first.
	 *
	 * @param array $run Run.
	 * @return array|null Null when it cannot be read.
	 */
	public static function journal_entries( array $run ) {
		$body = @file_get_contents( self::dir() . $run['journal'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $body ) {
			return null;
		}

		$entries = [];

		foreach ( explode( "\n", (string) $body ) as $line ) {
			$entry = '' !== trim( $line ) ? json_decode( $line, true ) : null;

			if ( is_array( $entry ) ) {
				$entries[] = $entry;
			}
		}

		return $entries;
	}

	/**
	 * @param string $level info | warning | error.
	 * @param string $text  Message.
	 */
	private static function log( $level, $text ) {
		self::$run['log'][] = [ 'level' => $level, 'text' => $text ];
	}

	/**
	 * @param string $what Counter.
	 */
	private static function count( $what ) {
		self::$run['counts'][ $what ] = ( self::$run['counts'][ $what ] ?? 0 ) + 1;
	}

	private static function save() {
		update_option( self::RUN, self::$run, false );
	}

	// ------------------------------------------------------------------
	// Undo
	// ------------------------------------------------------------------

	/**
	 * Put back what the last run changed, newest change first.
	 *
	 * @return array{undone:int}|WP_Error
	 */
	public static function undo() {
		global $wpdb;

		$run = get_option( self::RUN );

		if ( ! is_array( $run ) || empty( $run['journal'] ) || 'undone' === ( $run['state'] ?? '' ) ) {
			return new WP_Error( 'pfh_migrate_nothing', __( 'There is no import to undo.', 'pfh-widgets' ) );
		}

		$entries = self::journal_entries( $run );

		if ( ! is_array( $entries ) ) {
			return new WP_Error( 'pfh_migrate_journal', __( 'The journal of the last import cannot be read. Restore the backup instead.', 'pfh-widgets' ) );
		}

		$done = 0;
		$base = trailingslashit( (string) wp_get_upload_dir()['basedir'] );

		foreach ( array_reverse( $entries ) as $entry ) {
			switch ( $entry['type'] ?? '' ) {
				case 'meta':
					if ( $entry['had'] ) {
						update_metadata( $entry['object'], (int) $entry['id'], $entry['key'], wp_slash( $entry['old'] ) );
					} else {
						delete_metadata( $entry['object'], (int) $entry['id'], $entry['key'] );
					}
					break;

				case 'option':
					if ( $entry['had'] ) {
						update_option( $entry['name'], $entry['old'] );
					} else {
						delete_option( $entry['name'] );
					}
					break;

				case 'post':
					wp_delete_post( (int) $entry['id'], true );
					break;

				case 'attachment':
					if ( ! empty( $entry['downloaded'] ) ) {
						wp_delete_attachment( (int) $entry['id'], true );
					} else {
						// The file was here before: only the record goes.
						$wpdb->delete( $wpdb->postmeta, [ 'post_id' => (int) $entry['id'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$wpdb->delete( $wpdb->posts, [ 'ID' => (int) $entry['id'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						clean_post_cache( (int) $entry['id'] );
					}
					break;

				case 'file':
					$full = $base . $entry['path'];

					if ( is_file( $full ) && ( '' === (string) $entry['md5'] || md5_file( $full ) === $entry['md5'] ) ) {
						@unlink( $full ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
					}
					break;

				case 'term':
					wp_delete_term( (int) $entry['id'], $entry['taxonomy'] );
					break;

				case 'relation':
					wp_remove_object_terms( (int) $entry['object'], [ (int) $entry['term'] ], $entry['taxonomy'] );
					break;

				case 'menu':
					wp_delete_nav_menu( (int) $entry['id'] );
					break;

				case 'menu_renamed':
					wp_update_term( (int) $entry['id'], 'nav_menu', [ 'name' => $entry['name'], 'slug' => $entry['slug'] ] );
					break;

				case 'post_fields':
					$wpdb->update( $wpdb->posts, (array) $entry['old'], [ 'ID' => (int) $entry['id'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					clean_post_cache( (int) $entry['id'] );
					break;

				case 'terms_set':
					wp_set_object_terms( (int) $entry['object'], array_map( 'intval', (array) $entry['old'] ), $entry['taxonomy'], false );
					break;

				case 'term_update':
					wp_update_term( (int) $entry['id'], $entry['taxonomy'], [ 'name' => $entry['name'], 'description' => $entry['description'], 'parent' => (int) $entry['parent'] ] );
					break;

				case 'attribute_created':
					if ( function_exists( 'wc_delete_attribute' ) ) {
						wc_delete_attribute( (int) $entry['id'] );
					}
					break;

				case 'attribute_updated':
					if ( function_exists( 'wc_update_attribute' ) ) {
						wc_update_attribute( (int) $entry['id'], (array) $entry['old'] );
					}
					break;

				default:
					continue 2;
			}

			$done++;
		}

		// The run before it, if any, is now the one an undo would take back.
		$history = get_option( self::HISTORY, [] );
		$history = is_array( $history ) ? $history : [];
		$before  = array_pop( $history );

		if ( is_array( $before ) ) {
			update_option( self::HISTORY, $history, false );
			update_option( self::RUN, $before, false );
		} else {
			$run['state'] = 'undone';
			$run['log'][] = [ 'level' => 'info', 'text' => __( 'Undone.', 'pfh-widgets' ) ];
			update_option( self::RUN, $run, false );
		}

		wp_cache_flush();

		return [ 'undone' => $done ];
	}
}
