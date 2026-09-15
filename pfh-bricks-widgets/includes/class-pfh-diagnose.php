<?php
/**
 * A read-only report on why an element is not on the page.
 *
 * Written because two plausible fixes in a row changed nothing: the guessing
 * has to stop somewhere, and the thing worth looking at is what Bricks
 * actually saved, not what the code would do with it.
 *
 * It answers, for every Bricks page and template on the site:
 *
 *   is the element in the saved data at all — which is the difference between
 *   "it will not save" and "it will not render";
 *   what settings it was saved with;
 *   whether anything on it, or on a section above it, carries a condition,
 *   which would keep it off the front end no matter what the element does;
 *   and what it renders right now, outside the builder.
 *
 * Admin-only, changes nothing, writes nothing.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Diagnose {

	const SLUG = 'pfh-diagnose';

	/** Where the last few save attempts are recorded. */
	const LOG = 'pfh_save_log';

	public static function boot() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 20 );

		// Early, so a save that dies later has still been noticed.
		add_action( 'init', [ __CLASS__, 'watch_save' ], 1 );
	}

	/**
	 * Notice a Bricks save going past, and record what the server received.
	 *
	 * A save that fails leaves nothing behind to look at: the builder says
	 * only that it could not save. This records the request's own shape — how
	 * big it was, how many variables actually arrived, what PHP's limits are —
	 * and then, at shutdown, how it ended and whether it died on a fatal.
	 *
	 * It reads nothing it could consume. php://input is deliberately not
	 * touched: REST reads that body itself, and the one thing this must never
	 * do is break the save it is trying to explain.
	 */
	public static function watch_save() {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore

		$is_save = ( false !== strpos( $action, 'bricks' ) && false !== strpos( $action, 'save' ) )
			|| ( false !== stripos( $uri, '/bricks/' ) && false !== stripos( $uri, 'save' ) );

		if ( ! $is_save ) {
			return;
		}

		$limit = (int) ini_get( 'max_input_vars' );
		$vars  = is_array( $_POST ) ? count( $_POST, COUNT_RECURSIVE ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$bytes = isset( $_SERVER['CONTENT_LENGTH'] ) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;

		$entry = [
			'when'    => current_time( 'mysql' ),
			'action'  => $action ? $action : 'REST ' . preg_replace( '/\?.*$/', '', $uri ),
			'type'    => isset( $_SERVER['CONTENT_TYPE'] ) ? (string) wp_unslash( $_SERVER['CONTENT_TYPE'] ) : '', // phpcs:ignore
			'bytes'   => $bytes,
			'vars'    => $vars,
			'limit'   => $limit,
			'post_max'=> (string) ini_get( 'post_max_size' ),
			'memory'  => (string) ini_get( 'memory_limit' ),
			'time'    => (string) ini_get( 'max_execution_time' ),
			'user'    => get_current_user_id(),
			'status'  => 'did not finish',
			'fatal'   => '',
		];

		/*
		 * PHP drops everything past max_input_vars silently — no error, no
		 * warning to the browser, just a truncated tree. It is the classic
		 * cause of "it saves but nothing is there", and it gets worse every
		 * time another element is added to the page.
		 */
		if ( $limit > 0 && $vars >= $limit - 10 ) {
			$entry['status'] = 'TRUNCATED BY max_input_vars — ' . $vars . ' of ' . $limit . ' used';
		}

		self::record( $entry );

		add_action( 'shutdown', static function () use ( $entry ) {
			$code  = function_exists( 'http_response_code' ) ? (int) http_response_code() : 0;
			$fatal = error_get_last();

			if ( 0 === strpos( (string) $entry['status'], 'TRUNCATED' ) ) {
				$entry['status'] .= ' | HTTP ' . $code;
			} else {
				$entry['status'] = 'HTTP ' . $code;
			}

			if ( is_array( $fatal ) && in_array( (int) $fatal['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ], true ) ) {
				$entry['fatal'] = $fatal['message'] . ' @ ' . $fatal['file'] . ':' . $fatal['line'];
			}

			self::record( $entry, true );
		}, 1 );
	}

	/**
	 * Keep the last few attempts, newest first.
	 *
	 * @param array $entry   The attempt.
	 * @param bool  $replace Replace the newest rather than adding one.
	 */
	private static function record( array $entry, $replace = false ) {
		$log = get_option( self::LOG, [] );
		$log = is_array( $log ) ? $log : [];

		if ( $replace && $log ) {
			array_shift( $log );
		}

		array_unshift( $log, $entry );

		update_option( self::LOG, array_slice( $log, 0, 6 ), false );
	}

	public static function menu() {
		add_submenu_page(
			PFH_Widgets_Settings::PAGE,
			esc_html__( 'Diagnose', 'pfh-widgets' ),
			esc_html__( 'Diagnose', 'pfh-widgets' ),
			PFH_Widgets_Settings::CAP,
			self::SLUG,
			[ __CLASS__, 'render' ]
		);
	}

	public static function render() {
		if ( ! current_user_can( PFH_Widgets_Settings::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to view this.', 'pfh-widgets' ) );
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Diagnose', 'pfh-widgets' ) . '</h1>';
		echo '<p>' . esc_html__( 'Everything below is read from the database as it stands. Copy the whole box and send it over.', 'pfh-widgets' ) . '</p>';
		$report = self::report();

		// The routing report already existed behind a query string on the
		// settings screen. One page, not two.
		if ( class_exists( 'PFH_Widgets_Diagnostics' ) && method_exists( 'PFH_Widgets_Diagnostics', 'report' ) ) {
			$report .= "\n\n" . PFH_Widgets_Diagnostics::report();
		}

		echo '<textarea readonly style="width:100%;height:32em;font-family:Menlo,Consolas,monospace;font-size:12px;white-space:pre;overflow:auto">';
		echo esc_textarea( $report );
		echo '</textarea></div>';
	}

	/**
	 * The report itself.
	 *
	 * @return string
	 */
	public static function report() {
		$out = [];

		$out[] = 'PFH ' . PFH_WIDGETS_VERSION
			. ' | Bricks ' . ( defined( 'BRICKS_VERSION' ) ? BRICKS_VERSION : 'not loaded' )
			. ' | WP ' . get_bloginfo( 'version' )
			. ' | Woo ' . ( defined( 'WC_VERSION' ) ? WC_VERSION : '-' )
			. ' | PHP ' . PHP_VERSION;
		$out[] = 'max_input_vars ' . ini_get( 'max_input_vars' )
			. ' | post_max_size ' . ini_get( 'post_max_size' )
			. ' | memory_limit ' . ini_get( 'memory_limit' )
			. ' | max_execution_time ' . ini_get( 'max_execution_time' );
		$out[] = '';

		$out[] = '== the last save attempts Bricks made ==';

		$log = get_option( self::LOG, [] );

		if ( ! is_array( $log ) || ! $log ) {
			$out[] = 'None recorded yet. Open the builder, press save once, then come back here.';
		} else {
			$truncated = false;
			$fataled   = false;

			foreach ( $log as $e ) {
				$out[] = sprintf(
					'%s  %s  %s bytes, %d vars of %s  -> %s%s',
					$e['when'] ?? '?',
					$e['action'] ?? '?',
					number_format( (int) ( $e['bytes'] ?? 0 ) ),
					(int) ( $e['vars'] ?? 0 ),
					$e['limit'] ?? '?',
					$e['status'] ?? '?',
					! empty( $e['fatal'] ) ? "\n      FATAL: " . $e['fatal'] : ''
				);

				$truncated = $truncated || 0 === strpos( (string) ( $e['status'] ?? '' ), 'TRUNCATED' );
				$fataled   = $fataled || ! empty( $e['fatal'] );
			}

			/*
			 * Saying what to do about it, here, rather than leaving a number
			 * to be interpreted — this is read by whoever is stuck, not by
			 * whoever wrote it.
			 */
			if ( $truncated ) {
				$out[] = '';
				$out[] = 'WHAT THIS MEANS: PHP threw away everything past max_input_vars before';
				$out[] = 'WordPress ever saw it, without an error. Bricks then saved the part that';
				$out[] = 'arrived, which is why the page loses what was added. It gets worse with';
				$out[] = 'every element added to the page.';
				$out[] = '';
				$out[] = 'THE FIX: raise max_input_vars to 10000. It cannot be set from PHP at';
				$out[] = 'runtime, so a plugin cannot do it — it needs one of:';
				$out[] = '  - a .user.ini file in the site root containing:  max_input_vars = 10000';
				$out[] = '  - or the host raising it (on Kinsta, ask support — it is a normal request)';
				$out[] = 'Then reload the builder and save again, and this line should not come back.';
			}

			if ( $fataled ) {
				$out[] = '';
				$out[] = 'A FATAL ended a save. The file and line are above — if it is in';
				$out[] = 'pfh-bricks-widgets, send this report over and it will be fixed.';
			}
		}

		$out[] = '';

		/* ---- are the elements registered where the front end can see them ---- */

		$out[] = '== elements registered with Bricks ==';

		if ( ! class_exists( '\Bricks\Elements' ) ) {
			$out[] = 'Bricks is not loaded on this request, so there is nothing to ask.';
		} elseif ( ! property_exists( '\Bricks\Elements', 'elements' ) ) {
			// Reading Bricks' own internals: if the name ever changes, say so
			// rather than reporting an empty list as if it meant something.
			$out[] = 'Cannot read Bricks\' element list on this version — treat this section as unknown, not as empty.';
		} else {
			$registered = array_keys( (array) \Bricks\Elements::$elements );

			$ours = array_values( array_filter( $registered, static function ( $n ) {
				return 0 === strpos( (string) $n, 'pfh-' );
			} ) );

			$out[] = count( $ours ) . ' of ' . count( $registered ) . ' registered are ours: '
				. ( $ours ? implode( ', ', $ours ) : 'NONE — Bricks cannot render any of them' );

			$out[] = in_array( 'pfh-highlight', $ours, true )
				? 'pfh-highlight IS registered.'
				: 'pfh-highlight is NOT registered — that alone would keep it off the page.';
		}
		$out[] = '';

		/* ---- what is actually saved ---- */

		$out[] = '== pfh elements found in saved Bricks data ==';

		$posts = get_posts(
			[
				'post_type'        => [ 'bricks_template', 'page', 'post', 'product' ],
				'post_status'      => [ 'publish', 'draft', 'private' ],
				'numberposts'      => 200,
				'fields'           => 'ids',
				'suppress_filters' => true,
			]
		);

		$found = 0;

		foreach ( (array) $posts as $pid ) {
			foreach ( [ '_bricks_page_content_2', '_bricks_page_header_2', '_bricks_page_footer_2' ] as $meta ) {
				$data = get_post_meta( $pid, $meta, true );

				if ( ! is_array( $data ) ) {
					continue;
				}

				$hits = self::scan( $data, $pid, $meta );

				if ( $hits ) {
					$found += count( $hits );
					$out    = array_merge( $out, $hits );
				}
			}
		}

		if ( ! $found ) {
			$out[] = 'NONE. No pfh element is saved anywhere — which means the page was never saved with one in it.';
		}

		$out[] = '';
		$out = array_merge( $out, self::templates() );

		$out[] = '';
		$out[] = '== what a highlight renders right now, outside the builder ==';
		$out[] = self::render_probe();

		return implode( "\n", $out );
	}

	/**
	 * Walk one saved element tree looking for ours.
	 *
	 * @param array  $elements Saved Bricks elements.
	 * @param int    $pid      Post they belong to.
	 * @param string $meta     Which area.
	 * @return array Lines.
	 */
	private static function scan( array $elements, $pid, $meta ) {
		$lines = [];

		// Parent lookup, so a condition on the section above can be reported:
		// that alone keeps an element off the front end whatever it does.
		$by_id = [];

		foreach ( $elements as $el ) {
			if ( isset( $el['id'] ) ) {
				$by_id[ $el['id'] ] = $el;
			}
		}

		foreach ( $elements as $el ) {
			$name = isset( $el['name'] ) ? (string) $el['name'] : '';

			if ( 0 !== strpos( $name, 'pfh-' ) ) {
				continue;
			}

			$settings = isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : [];
			$keys     = array_keys( $settings );

			$line = sprintf(
				'#%d %s [%s] %s — %d setting(s)%s',
				$pid,
				get_post_type( $pid ),
				str_replace( '_bricks_page_', '', $meta ),
				$name,
				count( $keys ),
				$keys ? ': ' . implode( ', ', array_slice( $keys, 0, 12 ) ) . ( count( $keys ) > 12 ? ' …' : '' ) : ''
			);

			// Conditions, on it and on every ancestor.
			$conds = [];
			$node  = $el;
			$depth = 0;

			while ( $node && $depth < 12 ) {
				if ( ! empty( $node['settings']['_conditions'] ) ) {
					$conds[] = ( $node === $el ? 'on itself' : 'on ' . ( $node['name'] ?? 'parent' ) );
				}

				$parent = isset( $node['parent'] ) ? $node['parent'] : '';
				$node   = ( $parent && isset( $by_id[ $parent ] ) ) ? $by_id[ $parent ] : null;
				$depth++;
			}

			if ( $conds ) {
				$line .= ' | CONDITIONS ' . implode( ', ', $conds ) . ' — these decide whether it is output at all';
			}

			$lines[] = $line;
		}

		if ( $lines ) {
			array_unshift( $lines, sprintf( '#%d holds %d element(s) in total:', $pid, count( $elements ) ) );
		}

		return $lines;
	}

	/**
	 * Every Bricks template, what it is for, and how big it is.
	 *
	 * The question this exists to answer: the shop page was showing an older
	 * version of the template being edited. Either two templates both claim
	 * that page and Bricks renders the other one, or the shop page carries
	 * Bricks content of its own — which wins over any template. Both look
	 * exactly like "my edits do not save".
	 *
	 * @return array Lines.
	 */
	private static function templates() {
		$out = [ '== Bricks templates, and what claims the shop page ==' ];

		$templates = get_posts(
			[
				'post_type'        => 'bricks_template',
				'post_status'      => [ 'publish', 'draft', 'private' ],
				'numberposts'      => 100,
				'suppress_filters' => true,
			]
		);

		if ( ! $templates ) {
			$out[] = 'No Bricks templates on this site.';
		}

		foreach ( $templates as $t ) {
			$content = get_post_meta( $t->ID, '_bricks_page_content_2', true );
			$type    = get_post_meta( $t->ID, '_bricks_template_type', true );
			$conds   = get_post_meta( $t->ID, '_bricks_template_settings', true );
			$names   = [];

			if ( is_array( $content ) ) {
				foreach ( $content as $el ) {
					if ( isset( $el['name'] ) && 0 === strpos( (string) $el['name'], 'pfh-' ) ) {
						$names[] = $el['name'];
					}
				}
			}

			$out[] = sprintf(
				'#%d "%s" [%s] %s — %d element(s), pfh: %s | edited %s',
				$t->ID,
				$t->post_title,
				$t->post_status,
				$type ? $type : 'no type',
				is_array( $content ) ? count( $content ) : 0,
				$names ? implode( ', ', $names ) : 'none',
				$t->post_modified
			);

			if ( is_array( $conds ) && ! empty( $conds['templateConditions'] ) ) {
				foreach ( (array) $conds['templateConditions'] as $c ) {
					$out[] = '      condition: ' . wp_json_encode( $c );
				}
			} else {
				$out[] = '      condition: none set — Bricks will not apply it anywhere on its own';
			}
		}

		/* ---- and the shop page itself ---- */

		$out[] = '';
		$out[] = '== the WooCommerce shop page ==';

		if ( ! function_exists( 'wc_get_page_id' ) ) {
			$out[] = 'WooCommerce is not loaded here.';

			return $out;
		}

		$shop = (int) wc_get_page_id( 'shop' );

		if ( $shop < 1 ) {
			$out[] = 'No shop page is set in WooCommerce.';

			return $out;
		}

		$own = get_post_meta( $shop, '_bricks_page_content_2', true );

		$out[] = sprintf( '#%d "%s" — %s', $shop, get_the_title( $shop ), get_permalink( $shop ) );

		if ( is_array( $own ) && $own ) {
			$names = [];

			foreach ( $own as $el ) {
				if ( isset( $el['name'] ) && 0 === strpos( (string) $el['name'], 'pfh-' ) ) {
					$names[] = $el['name'];
				}
			}

			$out[] = sprintf(
				'      HAS ITS OWN BRICKS CONTENT: %d element(s), pfh: %s, edited %s',
				count( $own ),
				$names ? implode( ', ', $names ) : 'none',
				get_post_field( 'post_modified', $shop )
			);
			$out[] = '      This wins over any template. Editing the template will not change this page —';
			$out[] = '      edit the page itself, or clear its Bricks content so the template applies.';
		} else {
			$out[] = '      No Bricks content of its own, so a template decides what it looks like.';
		}

		return $out;
	}

	/**
	 * Render a highlight the way a visitor would get it.
	 *
	 * @return string
	 */
	private static function render_probe() {
		if ( ! class_exists( '\Bricks\Element' ) ) {
			return 'Bricks is not loaded here, so nothing to render.';
		}

		if ( ! class_exists( 'PFH_Element_Highlight' ) ) {
			$file = PFH_WIDGETS_DIR . 'elements/class-pfh-element-highlight.php';

			if ( ! file_exists( $file ) ) {
				return 'The element file is missing from the installed plugin.';
			}

			require_once $file;
		}

		$el       = new PFH_Element_Highlight( [ 'id' => 'diagnose' ] );
		$el->name = 'pfh-highlight';
		// Deliberately no set_controls(): this is the front end's own shape.
		$el->settings = [];

		ob_start();

		try {
			$el->render();
		} catch ( \Throwable $e ) {
			ob_end_clean();

			return 'THREW: ' . $e->getMessage();
		}

		$html = trim( (string) ob_get_clean() );

		if ( '' === $html ) {
			return 'Renders NOTHING with no settings — the defaults are not reaching it.';
		}

		return 'Renders ' . strlen( $html ) . ' bytes with no settings at all, so its defaults are reaching it. First 200: '
			. substr( preg_replace( '/\s+/', ' ', $html ), 0, 200 );
	}
}
