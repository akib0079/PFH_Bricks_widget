<?php
/**
 * Making room for another site's orders: moving this site's own posts off
 * the numbers those orders carry.
 *
 * WordPress counts orders, pages, images, templates and revisions with one
 * number. Two copies of a shop that were split at some point each go on
 * counting from there, so the live shop's order 13723 and this site's header
 * template can share a number. An order import that keeps the order's own
 * number — as it has to: invoices, e-mails, the payment and the shipping
 * label all name it — then skips the order. Freeing the number means moving
 * what is here to a number of its own and pointing everything that names it
 * at the new one.
 *
 * Two halves, as in the design import. survey() reads what is on each number
 * and every place that names it, says which of those it knows how to repoint,
 * and writes nothing. The run then works in short steps: revisions on those
 * numbers are deleted (their row and meta are kept in the journal), every
 * other post is moved, and the references are rewritten. Each change goes
 * into the journal first; undo() puts it all back as long as the numbers
 * have not been taken since.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Renumber {

	/** The current (or last) run. */
	const RUN = 'pfh_renumber_run';

	/** The last check, until a run starts. */
	const SURVEY = 'pfh_renumber_survey';

	/** Post types that are an order's own: the number is already an order. */
	const ORDER_TYPES = [ 'shop_order', 'shop_order_refund', 'shop_order_placehold' ];

	/** Meta whose whole value is one post id. */
	const ID_KEYS = [ '_thumbnail_id', '_menu_item_object_id', '_menu_item_menu_item_parent', '_product_id', '_variation_id', '_pfh_bottom_image', 'thumbnail_id' ];

	/** Meta holding a list of post ids: "1,2,3" or an array. */
	const LIST_KEYS = [ '_product_image_gallery', '_children', '_upsell_ids', '_crosssell_ids', '_pfh_related' ];

	/** Options whose whole value is one post id. */
	const ID_OPTIONS = [ 'page_on_front', 'page_for_posts', 'site_icon', 'woocommerce_shop_page_id', 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id', 'woocommerce_myaccount_page_id', 'woocommerce_terms_page_id', 'woocommerce_refund_returns_page_id', 'wp_page_for_privacy_policy', 'woocommerce_placeholder_image' ];

	/** Options never looked in: caches and this tool's own state. */
	const SKIP_OPTIONS = '/^(_transient_|_site_transient_|cron$|rewrite_rules$|pfh_renumber_|pfh_migrate_|pfh_save_log$)/';

	/** Steps, in order, and how much of each one request does. */
	const STEPS = [
		'revisions' => 40,
		'move'      => 6,
		'refs'      => 1,
		'finish'    => 1,
	];

	/** @var array|null The run being worked on. */
	private static $run = null;

	// ------------------------------------------------------------------
	// The numbers
	// ------------------------------------------------------------------

	/**
	 * Numbers from what was pasted: "13711, 13712 13715-13720".
	 *
	 * @param string $text Text.
	 * @return int[]
	 */
	public static function parse( $text ) {
		$ids = [];

		if ( preg_match_all( '/(\d+)\s*-\s*(\d+)|(\d+)/', (string) $text, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $match ) {
				if ( ! empty( $match[3] ) ) {
					$ids[] = (int) $match[3];
					continue;
				}

				$from = (int) $match[1];
				$to   = (int) $match[2];

				if ( $to >= $from && $to - $from <= 20000 ) {
					$ids = array_merge( $ids, range( $from, $to ) );
				}
			}
		}

		$ids = array_values( array_unique( array_filter( $ids ) ) );
		sort( $ids );

		return $ids;
	}

	/**
	 * Which of these numbers are already orders here.
	 *
	 * @param int[] $ids Numbers.
	 * @return array<int,true>
	 */
	private static function order_ids( array $ids ) {
		global $wpdb;

		$found = [];
		$table = $wpdb->prefix . 'wc_orders';

		if ( ! $ids || $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return $found;
		}

		foreach ( array_chunk( $ids, 500 ) as $chunk ) {
			foreach ( (array) $wpdb->get_col( "SELECT id FROM {$table} WHERE id IN (" . implode( ',', array_map( 'intval', $chunk ) ) . ')' ) as $id ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- integers.
				$found[ (int) $id ] = true;
			}
		}

		return $found;
	}

	// ------------------------------------------------------------------
	// The check
	// ------------------------------------------------------------------

	/**
	 * What is on each number, and where what moves is named. Writes nothing.
	 *
	 * @param int[] $ids Numbers the other site's orders need.
	 * @return array
	 */
	public static function survey( array $ids ) {
		global $wpdb;

		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
		$out = [
			'ids'       => $ids,
			'free'      => [],
			'orders'    => [],
			'revisions' => [],
			'orphans'   => [],
			'move'      => [],
			'refs'      => [],
			'unhandled' => [],
		];

		if ( ! $ids ) {
			return $out;
		}

		$rows = [];

		foreach ( array_chunk( $ids, 500 ) as $chunk ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- integers.
			foreach ( (array) $wpdb->get_results( "SELECT ID, post_type, post_status, post_title, post_parent FROM {$wpdb->posts} WHERE ID IN (" . implode( ',', $chunk ) . ')', ARRAY_A ) as $row ) {
				$rows[ (int) $row['ID'] ] = $row;
			}
		}

		$orders = self::order_ids( $ids );

		foreach ( $ids as $id ) {
			$row = $rows[ $id ] ?? null;

			if ( isset( $orders[ $id ] ) ) {
				$out['orders'][] = $id;
			} elseif ( ! $row ) {
				$out['free'][] = $id;
			} elseif ( in_array( $row['post_type'], self::ORDER_TYPES, true ) ) {
				// A placeholder with no order behind it: what an import that
				// stopped half way leaves. It holds the number for nothing.
				$out['orphans'][] = $id;
			} elseif ( 'revision' === $row['post_type'] ) {
				$out['revisions'][] = $id;
			} else {
				$out['move'][] = [
					'id'     => $id,
					'type'   => $row['post_type'],
					'status' => $row['post_status'],
					'title'  => $row['post_title'],
					'parent' => (int) $row['post_parent'],
				];
			}
		}

		$moving = wp_list_pluck( $out['move'], 'id' );

		if ( $moving ) {
			$found = self::references( $moving, true );

			$out['refs']      = $found['handled'];
			$out['unhandled'] = $found['unhandled'];
		}

		return $out;
	}

	// ------------------------------------------------------------------
	// References
	// ------------------------------------------------------------------

	/**
	 * Every place a moving number is named, and — when not only looking —
	 * every such place rewritten to the new number.
	 *
	 * @param int[]      $ids  Old numbers.
	 * @param bool       $look Only look.
	 * @param array|null $map  Old → new, when rewriting.
	 * @return array{handled: array, unhandled: array}
	 */
	public static function references( array $ids, $look, $map = null ) {
		global $wpdb;

		$ids    = array_map( 'intval', $ids );
		$map    = is_array( $map ) ? $map : array_fill_keys( $ids, 0 );
		$report = [ 'handled' => [], 'unhandled' => [] ];

		if ( ! $ids ) {
			return $report;
		}

		$note = static function ( $bucket, $where, $detail ) use ( &$report ) {
			$report[ $bucket ][ $where ] = ( $report[ $bucket ][ $where ] ?? 0 ) + max( 1, (int) $detail );
		};

		// Children: revisions of a template, images attached to a page.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- integers.
		$children = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_parent IN (" . implode( ',', $ids ) . ')' );

		if ( $children ) {
			$note( 'handled', 'posts.post_parent', $children );
		}

		// Post meta, term meta, options and post content that name a number.
		foreach ( self::candidates( 'postmeta', $ids ) as $row ) {
			self::fix_meta( 'post', $row, $map, $look, $note );
		}

		foreach ( self::candidates( 'termmeta', $ids ) as $row ) {
			self::fix_meta( 'term', $row, $map, $look, $note );
		}

		foreach ( self::candidates( 'options', $ids ) as $row ) {
			self::fix_option( $row, $map, $look, $note );
		}

		foreach ( self::candidates( 'posts', $ids ) as $row ) {
			self::fix_content( $row, $map, $look, $note );
		}

		foreach ( self::candidates( 'usermeta', $ids ) as $row ) {
			$note( 'unhandled', 'usermeta ' . $row['meta_key'], self::occurrences( (string) $row['meta_value'], $ids ) );
		}

		return $report;
	}

	/**
	 * Rows that mention one of the numbers somewhere in their value.
	 *
	 * @param string $table postmeta | termmeta | options | posts | usermeta.
	 * @param int[]  $ids   Numbers.
	 * @return array
	 */
	private static function candidates( $table, array $ids ) {
		global $wpdb;

		$spec = [
			'postmeta' => [ $wpdb->postmeta, 'meta_id, post_id AS object_id, meta_key, meta_value', 'meta_value' ],
			'termmeta' => [ $wpdb->termmeta, 'meta_id, term_id AS object_id, meta_key, meta_value', 'meta_value' ],
			'usermeta' => [ $wpdb->usermeta, 'umeta_id AS meta_id, user_id AS object_id, meta_key, meta_value', 'meta_value' ],
			'options'  => [ $wpdb->options, 'option_id, option_name, option_value', 'option_value' ],
			'posts'    => [ $wpdb->posts, 'ID, post_type, post_content', 'post_content' ],
		][ $table ];

		$rows = [];

		foreach ( array_chunk( $ids, 40 ) as $chunk ) {
			$like = [];

			foreach ( $chunk as $id ) {
				$like[] = $wpdb->prepare( "{$spec[2]} LIKE %s", '%' . $wpdb->esc_like( (string) $id ) . '%' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- column name.
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- built from prepared parts.
			foreach ( (array) $wpdb->get_results( "SELECT {$spec[1]} FROM {$spec[0]} WHERE " . implode( ' OR ', $like ), ARRAY_A ) as $row ) {
				$key          = (string) reset( $row );
				$rows[ $key ] = $row;
			}
		}

		// Only where one stands as a number of its own, not inside another.
		return array_filter(
			$rows,
			static function ( $row ) use ( $spec, $ids, $table ) {
				$value = (string) $row[ 'options' === $table ? 'option_value' : ( 'posts' === $table ? 'post_content' : 'meta_value' ) ];

				return self::occurrences( $value, $ids ) > 0;
			}
		);
	}

	/**
	 * How often one of the numbers stands on its own in a text.
	 *
	 * @param string $text Text.
	 * @param int[]  $ids  Numbers.
	 * @return int
	 */
	public static function occurrences( $text, array $ids ) {
		if ( '' === $text || ! $ids ) {
			return 0;
		}

		$count = 0;

		foreach ( array_chunk( $ids, 200 ) as $chunk ) {
			$count += (int) preg_match_all( '/(?<![0-9A-Za-z_])(' . implode( '|', array_map( 'intval', $chunk ) ) . ')(?![0-9A-Za-z_])/', $text );
		}

		return $count;
	}

	/**
	 * A visitor for the design walk that swaps moving numbers and counts.
	 *
	 * @param array $map    Old → new.
	 * @param int   $mapped Counter.
	 * @return callable
	 */
	private static function visitor( array $map, &$mapped ) {
		return static function ( $kind, $value ) use ( $map, &$mapped ) {
			if ( ( 'attachment' === $kind || 'post' === $kind ) && is_numeric( $value ) && isset( $map[ (int) $value ] ) ) {
				$mapped++;

				return is_string( $value ) ? (string) $map[ (int) $value ] : (int) $map[ (int) $value ];
			}

			return $value;
		};
	}

	/**
	 * A list of ids, as text or array, renumbered.
	 *
	 * @param mixed $value  Value.
	 * @param array $map    Old → new.
	 * @param int   $mapped Counter.
	 * @return mixed
	 */
	private static function map_list( $value, array $map, &$mapped ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $i => $id ) {
				if ( is_numeric( $id ) && isset( $map[ (int) $id ] ) ) {
					$value[ $i ] = is_string( $id ) ? (string) $map[ (int) $id ] : (int) $map[ (int) $id ];
					$mapped++;
				}
			}

			return $value;
		}

		$parts = explode( ',', (string) $value );

		foreach ( $parts as $i => $id ) {
			if ( ctype_digit( trim( $id ) ) && isset( $map[ (int) trim( $id ) ] ) ) {
				$parts[ $i ] = (string) $map[ (int) trim( $id ) ];
				$mapped++;
			}
		}

		return implode( ',', $parts );
	}

	/**
	 * One meta row: renumbered by what its key holds, or reported.
	 *
	 * @param string   $type post | term.
	 * @param array    $row  Row.
	 * @param array    $map  Old → new.
	 * @param bool     $look Only look.
	 * @param callable $note Reporter.
	 */
	private static function fix_meta( $type, array $row, array $map, $look, callable $note ) {
		$key    = (string) $row['meta_key'];
		$raw    = (string) $row['meta_value'];
		$want   = self::occurrences( $raw, array_keys( $map ) );
		$mapped = 0;
		$value  = maybe_unserialize( $raw );
		$new    = $value;
		$where  = ( 'post' === $type ? 'postmeta ' : 'termmeta ' ) . $key;

		if ( in_array( $key, self::ID_KEYS, true ) && is_numeric( $value ) ) {
			// A menu item that points at a category names a term, not a post.
			if ( '_menu_item_object_id' === $key && 'post_type' !== get_post_meta( (int) $row['object_id'], '_menu_item_type', true ) ) {
				$note( 'unhandled', $where . ' (not a post)', $want );

				return;
			}

			if ( isset( $map[ (int) $value ] ) ) {
				$new = (string) $map[ (int) $value ];
				$mapped++;
			}
		} elseif ( in_array( $key, self::LIST_KEYS, true ) ) {
			$new = self::map_list( $value, $map, $mapped );
		} elseif ( 0 === strpos( $key, '_bricks_' ) || 0 === strpos( $key, 'bricks_' ) ) {
			$new = PFH_Widgets_Migrate_Refs::post_meta( $key, $value, self::visitor( $map, $mapped ) );
		} elseif ( 0 === strpos( $key, '_pfh_' ) ) {
			$new = 'term' === $type && class_exists( 'PFH_Widgets_Collection' ) && PFH_Widgets_Collection::META === $key && is_array( $value )
				? PFH_Widgets_Migrate_Refs::collection( $value, self::visitor( $map, $mapped ) )
				: PFH_Widgets_Migrate_Refs::product_meta( $key, $value, self::visitor( $map, $mapped ) );
		} elseif ( '_elementor_data' === $key && is_string( $value ) ) {
			$data = json_decode( $value, true );

			if ( is_array( $data ) ) {
				$data = PFH_Widgets_Migrate_Refs::walk( $data, self::visitor( $map, $mapped ) );
				$new  = $mapped ? (string) wp_json_encode( $data ) : $value;
			}
		}

		if ( $mapped < $want ) {
			$note( 'unhandled', $where, $want - $mapped );
		}

		if ( ! $mapped ) {
			return;
		}

		$note( 'handled', $where, $mapped );

		if ( $look ) {
			return;
		}

		global $wpdb;

		$table = 'post' === $type ? $wpdb->postmeta : $wpdb->termmeta;
		$store = is_string( $new ) ? $new : maybe_serialize( $new );

		self::journal( [ 'type' => 'meta', 'table' => $table, 'id' => (int) $row['meta_id'], 'old' => $raw ] );
		$wpdb->update( $table, [ 'meta_value' => $store ], [ 'meta_id' => (int) $row['meta_id'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery
	}

	/**
	 * One option: renumbered by what it is, or reported.
	 *
	 * @param array    $row  Row.
	 * @param array    $map  Old → new.
	 * @param bool     $look Only look.
	 * @param callable $note Reporter.
	 */
	private static function fix_option( array $row, array $map, $look, callable $note ) {
		$name = (string) $row['option_name'];

		if ( preg_match( self::SKIP_OPTIONS, $name ) ) {
			return;
		}

		$raw    = (string) $row['option_value'];
		$want   = self::occurrences( $raw, array_keys( $map ) );
		$mapped = 0;
		$value  = maybe_unserialize( $raw );
		$new    = $value;
		$media  = self::module_media( $name );

		if ( in_array( $name, self::ID_OPTIONS, true ) && is_numeric( $value ) ) {
			if ( isset( $map[ (int) $value ] ) ) {
				$new = (string) $map[ (int) $value ];
				$mapped++;
			}
		} elseif ( 0 === strpos( $name, 'bricks_' ) ) {
			$new = PFH_Widgets_Migrate_Refs::walk( $value, self::visitor( $map, $mapped ) );
		} elseif ( 0 === strpos( $name, 'theme_mods_' ) && is_array( $value ) ) {
			$custom = $value['custom_css_post_id'] ?? null;
			$new    = PFH_Widgets_Migrate_Refs::theme_mods( $value, self::visitor( $map, $mapped ) );

			// The mapper leaves this one out for an export; here it stays.
			if ( null !== $custom ) {
				$new['custom_css_post_id'] = isset( $map[ (int) $custom ] ) ? $map[ (int) $custom ] : $custom;
				$mapped                   += isset( $map[ (int) $custom ] ) ? 1 : 0;
			}
		} elseif ( null !== $media && is_array( $value ) ) {
			$new = PFH_Widgets_Migrate_Refs::module_option( $value, $media, self::visitor( $map, $mapped ) );
		}

		if ( $mapped < $want ) {
			$note( 'unhandled', 'option ' . $name, $want - $mapped );
		}

		if ( ! $mapped ) {
			return;
		}

		$note( 'handled', 'option ' . $name, $mapped );

		if ( $look ) {
			return;
		}

		global $wpdb;

		self::journal( [ 'type' => 'option', 'id' => (int) $row['option_id'], 'old' => $raw ] );
		$wpdb->update( $wpdb->options, [ 'option_value' => is_string( $new ) ? $new : maybe_serialize( $new ) ], [ 'option_id' => (int) $row['option_id'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * The media fields of one of this plugin's settings options, or null.
	 *
	 * @param string $name Option.
	 * @return string[]|null
	 */
	private static function module_media( $name ) {
		static $media = null;

		if ( null === $media ) {
			$media = [];

			foreach ( class_exists( 'PFH_Widgets_Settings' ) ? PFH_Widgets_Settings::tabs() : [] as $meta ) {
				$class = $meta['class'];

				if ( ! class_exists( $class ) || '' === (string) $class::OPTION ) {
					continue;
				}

				$media[ $class::OPTION ] = [];

				foreach ( $class::flat_fields() as $key => $field ) {
					if ( 'media' === ( $field['type'] ?? '' ) ) {
						$media[ $class::OPTION ][] = $key;
					}
				}
			}
		}

		return $media[ $name ] ?? null;
	}

	/**
	 * A post's text: images placed in it by number.
	 *
	 * @param array    $row  Row.
	 * @param array    $map  Old → new.
	 * @param bool     $look Only look.
	 * @param callable $note Reporter.
	 */
	private static function fix_content( array $row, array $map, $look, callable $note ) {
		$raw    = (string) $row['post_content'];
		$want   = self::occurrences( $raw, array_keys( $map ) );
		$mapped = 0;
		$swap   = static function ( $id ) use ( $map, &$mapped ) {
			if ( isset( $map[ (int) $id ] ) ) {
				$mapped++;

				return (string) $map[ (int) $id ];
			}

			return $id;
		};

		// <img class="wp-image-12">, data-id="12", "id":12 / "mediaId":12 in
		// a block's settings, "ids":[1,2] in a gallery block, [gallery ids=].
		$new = preg_replace_callback(
			'/(wp-image-|data-id=")(\d+)/',
			static function ( $m ) use ( $swap ) {
				return $m[1] . $swap( $m[2] );
			},
			$raw
		);

		$new = preg_replace_callback(
			'/<!-- wp:[a-z0-9\/-]+ (\{.*?\}) \/?-->/s',
			static function ( $m ) use ( $swap ) {
				$json = preg_replace_callback(
					'/"(id|mediaId|featuredImage)":(\d+)/',
					static function ( $n ) use ( $swap ) {
						return '"' . $n[1] . '":' . $swap( $n[2] );
					},
					$m[1]
				);

				$json = preg_replace_callback(
					'/"ids":\[([\d,]*)\]/',
					static function ( $n ) use ( $swap ) {
						return '"ids":[' . implode( ',', array_map( $swap, array_filter( explode( ',', $n[1] ), 'strlen' ) ) ) . ']';
					},
					$json
				);

				return str_replace( $m[1], $json, $m[0] );
			},
			$new
		);

		$new = preg_replace_callback(
			'/\[gallery([^\]]*?)ids="([\d,\s]+)"/',
			static function ( $m ) use ( $swap ) {
				return '[gallery' . $m[1] . 'ids="' . implode( ',', array_map( $swap, array_map( 'trim', explode( ',', $m[2] ) ) ) ) . '"';
			},
			$new
		);

		$where = 'post_content (' . $row['post_type'] . ')';

		if ( $mapped < $want ) {
			$note( 'unhandled', $where, $want - $mapped );
		}

		if ( ! $mapped ) {
			return;
		}

		$note( 'handled', $where, $mapped );

		if ( $look ) {
			return;
		}

		global $wpdb;

		self::journal( [ 'type' => 'content', 'id' => (int) $row['ID'], 'old' => $raw ] );
		$wpdb->update( $wpdb->posts, [ 'post_content' => $new ], [ 'ID' => (int) $row['ID'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	// ------------------------------------------------------------------
	// The run
	// ------------------------------------------------------------------

	/**
	 * Start freeing the numbers the last check was made for.
	 *
	 * @return array|WP_Error The run.
	 */
	public static function start() {
		$survey = get_option( self::SURVEY );

		if ( ! is_array( $survey ) || empty( $survey['ids'] ) ) {
			return new WP_Error( 'pfh_renumber_nothing', __( 'Check the numbers first.', 'pfh-widgets' ) );
		}

		$current = get_option( self::RUN );

		if ( is_array( $current ) && 'running' === ( $current['state'] ?? '' ) && ( time() - (int) ( $current['touched'] ?? 0 ) ) < 10 * MINUTE_IN_SECONDS ) {
			return new WP_Error( 'pfh_renumber_busy', __( 'This is already running.', 'pfh-widgets' ) );
		}

		// Look again: something may have changed since the check.
		$survey = self::survey( $survey['ids'] );

		$id  = gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 20, false, false );
		$run = [
			'id'        => $id,
			'state'     => 'running',
			'step'      => 0,
			'cursor'    => 0,
			'revisions' => array_merge( $survey['revisions'], $survey['orphans'] ),
			'move'      => wp_list_pluck( $survey['move'], 'id' ),
			'map'       => [],
			'counts'    => [],
			'log'       => [],
			'started'   => current_time( 'mysql' ),
			'touched'   => time(),
			'journal'   => 'renumber-' . $id . '.jsonl',
		];

		file_put_contents( PFH_Widgets_Migrate_Import::dir() . $run['journal'], '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		self::$run = $run;
		self::save();

		return self::$run;
	}

	/**
	 * One short piece of the run.
	 *
	 * @return array|WP_Error The run.
	 */
	public static function run_step() {
		$run = get_option( self::RUN );

		if ( ! is_array( $run ) || 'running' !== ( $run['state'] ?? '' ) ) {
			return new WP_Error( 'pfh_renumber_idle', __( 'Nothing is running.', 'pfh-widgets' ) );
		}

		self::$run = $run;

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

		$items = 'revisions' === $name ? self::$run['revisions'] : ( 'move' === $name ? self::$run['move'] : [ $name ] );
		$batch = array_slice( $items, (int) self::$run['cursor'], self::STEPS[ $name ] );

		foreach ( $batch as $item ) {
			try {
				if ( 'revisions' === $name ) {
					self::delete_post( (int) $item );
				} elseif ( 'move' === $name ) {
					self::move( (int) $item );
				} elseif ( 'refs' === $name ) {
					self::references( array_keys( self::$run['map'] ), false, self::$run['map'] );
				} else {
					self::finish();
				}
			} catch ( Throwable $e ) {
				self::log( 'error', $name . ' ' . ( is_scalar( $item ) ? $item : '' ) . ': ' . $e->getMessage() );
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

			self::save();
		}

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
	 * A revision, or a placeholder with no order: gone, with its meta, kept
	 * whole in the journal.
	 *
	 * @param int $id Post.
	 */
	private static function delete_post( $id ) {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( ! $row || ! in_array( $row['post_type'], array_merge( [ 'revision' ], self::ORDER_TYPES ), true ) || isset( self::order_ids( [ $id ] )[ $id ] ) ) {
			return;
		}

		$meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		self::journal( [ 'type' => 'deleted', 'post' => $row, 'meta' => $meta ] );

		$wpdb->delete( $wpdb->postmeta, [ 'post_id' => $id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->posts, [ 'ID' => $id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		clean_post_cache( $id );

		self::count( 'deleted' );
	}

	/**
	 * A number from the counter, taken so nothing else gets it.
	 *
	 * @return int
	 */
	private static function reserve() {
		global $wpdb;

		$now = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$wpdb->posts,
			[
				'post_type'             => 'pfh_reserved',
				'post_status'           => 'draft',
				'post_title'            => '',
				'post_content'          => '',
				'post_excerpt'          => '',
				'post_content_filtered' => '',
				'to_ping'               => '',
				'pinged'                => '',
				'post_date'             => $now,
				'post_date_gmt'         => $now,
				'post_modified'         => $now,
				'post_modified_gmt'     => $now,
			]
		);

		$id = (int) $wpdb->insert_id;

		$wpdb->delete( $wpdb->posts, [ 'ID' => $id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return $id;
	}

	/**
	 * Tables that hold a post's number: [ table, column, extra where ].
	 *
	 * @param string $type Post type.
	 * @return array
	 */
	private static function columns( $type ) {
		global $wpdb;

		$list = [
			[ $wpdb->posts, 'post_parent', '' ],
			[ $wpdb->postmeta, 'post_id', '' ],
			[ $wpdb->term_relationships, 'object_id', '' ],
			[ $wpdb->comments, 'comment_post_ID', '' ],
		];

		if ( in_array( $type, [ 'product', 'product_variation' ], true ) ) {
			$list[] = [ $wpdb->prefix . 'wc_product_meta_lookup', 'product_id', '' ];
			$list[] = [ $wpdb->prefix . 'wc_product_attributes_lookup', 'product_id', '' ];
			$list[] = [ $wpdb->prefix . 'wc_product_attributes_lookup', 'product_or_parent_id', '' ];
			$list[] = [ $wpdb->prefix . 'wc_order_product_lookup', 'product_id', '' ];
			$list[] = [ $wpdb->prefix . 'wc_order_product_lookup', 'variation_id', '' ];
			$list[] = [ $wpdb->prefix . 'pfh_waitlist', 'product_id', '' ];
		}

		$list[] = [ $wpdb->prefix . 'yoast_indexable', 'object_id', "object_type = 'post'" ];
		$list[] = [ $wpdb->prefix . 'yoast_seo_links', 'post_id', '' ];
		$list[] = [ $wpdb->prefix . 'yoast_seo_links', 'target_post_id', '' ];
		$list[] = [ $wpdb->prefix . 'yoast_primary_term', 'post_id', '' ];

		return $list;
	}

	/**
	 * Does this table exist here?
	 *
	 * @param string $table Table.
	 * @return bool
	 */
	private static function has_table( $table ) {
		global $wpdb;
		static $known = [];

		if ( ! isset( $known[ $table ] ) ) {
			$known[ $table ] = $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		return $known[ $table ];
	}

	/**
	 * One post onto a number of its own, with everything that hangs on it by
	 * number. What names it in a value is done afterwards, all in one go.
	 *
	 * @param int $old Number.
	 */
	private static function move( $old ) {
		global $wpdb;

		$type = (string) $wpdb->get_var( $wpdb->prepare( "SELECT post_type FROM {$wpdb->posts} WHERE ID = %d", $old ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( '' === $type || in_array( $type, self::ORDER_TYPES, true ) || 'revision' === $type ) {
			return;
		}

		$new = self::reserve();

		self::journal( [ 'type' => 'moved', 'old' => $old, 'new' => $new, 'post_type' => $type ] );

		$wpdb->update( $wpdb->posts, [ 'ID' => $new ], [ 'ID' => $old ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		self::shift( $type, $old, $new );

		// A product's line items keep their number in item meta.
		if ( in_array( $type, [ 'product', 'product_variation' ], true ) && self::has_table( $wpdb->prefix . 'woocommerce_order_itemmeta' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}woocommerce_order_itemmeta SET meta_value = %s WHERE meta_key IN ('_product_id', '_variation_id') AND meta_value = %s", (string) $new, (string) $old ) );
		}

		clean_post_cache( $old );
		clean_post_cache( $new );

		self::$run['map'][ $old ] = $new;
		self::count( 'moved' );
	}

	/**
	 * Every column that holds the number by itself: from one number to another.
	 *
	 * @param string $type Post type.
	 * @param int    $from Number.
	 * @param int    $to   Number.
	 */
	private static function shift( $type, $from, $to ) {
		global $wpdb;

		foreach ( self::columns( $type ) as $col ) {
			if ( ! self::has_table( $col[0] ) ) {
				continue;
			}

			$where = $col[2] ? ' AND ' . $col[2] : '';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table and column names from the list above.
			$wpdb->query( $wpdb->prepare( "UPDATE {$col[0]} SET {$col[1]} = %d WHERE {$col[1]} = %d{$where}", $to, $from ) );
		}
	}

	/**
	 * Caches that still hold the old numbers.
	 */
	private static function finish() {
		if ( class_exists( 'PFH_Widgets_Helpers' ) && defined( 'PFH_Widgets_Helpers::CAT_CACHE' ) ) {
			delete_transient( PFH_Widgets_Helpers::CAT_CACHE );
		}

		wp_cache_flush();

		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}

		/* translators: 1: moved, 2: deleted. */
		self::log( 'info', sprintf( __( 'Done: %1$d moved, %2$d revisions and empty placeholders deleted.', 'pfh-widgets' ), (int) ( self::$run['counts']['moved'] ?? 0 ), (int) ( self::$run['counts']['deleted'] ?? 0 ) ) );
	}

	// ------------------------------------------------------------------
	// Journal and undo
	// ------------------------------------------------------------------

	/**
	 * @param array $entry Change.
	 */
	private static function journal( array $entry ) {
		file_put_contents( PFH_Widgets_Migrate_Import::dir() . self::$run['journal'], wp_json_encode( $entry ) . "\n", FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
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

	/**
	 * Put everything back: references, numbers, deleted revisions.
	 *
	 * Refused when an old number has been taken since — an order imported on
	 * it — because putting a post back there would take it from the order.
	 *
	 * @return array{undone:int}|WP_Error
	 */
	public static function undo() {
		global $wpdb;

		$run = get_option( self::RUN );

		if ( ! is_array( $run ) || empty( $run['journal'] ) || 'undone' === ( $run['state'] ?? '' ) ) {
			return new WP_Error( 'pfh_renumber_nothing', __( 'There is nothing to undo.', 'pfh-widgets' ) );
		}

		$body = @file_get_contents( PFH_Widgets_Migrate_Import::dir() . $run['journal'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $body ) {
			return new WP_Error( 'pfh_renumber_journal', __( 'The journal cannot be read. Restore the backup instead.', 'pfh-widgets' ) );
		}

		$entries = array_values( array_filter( array_map( static function ( $line ) {
			return '' !== trim( $line ) ? json_decode( $line, true ) : null;
		}, explode( "\n", $body ) ) ) );

		$taken = [];

		foreach ( $entries as $entry ) {
			if ( in_array( $entry['type'], [ 'moved', 'deleted' ], true ) ) {
				$old = 'moved' === $entry['type'] ? (int) $entry['old'] : (int) $entry['post']['ID'];

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				if ( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE ID = %d", $old ) ) || isset( self::order_ids( [ $old ] )[ $old ] ) ) {
					$taken[] = $old;
				}
			}
		}

		if ( $taken ) {
			/* translators: %s: numbers. */
			return new WP_Error( 'pfh_renumber_taken', sprintf( __( 'These numbers have been taken since, so the posts cannot go back on them: %s. Restore the backup instead.', 'pfh-widgets' ), implode( ', ', array_slice( $taken, 0, 30 ) ) ) );
		}

		$done = 0;

		foreach ( array_reverse( $entries ) as $entry ) {
			switch ( $entry['type'] ) {
				case 'meta':
					$wpdb->update( $entry['table'], [ 'meta_value' => $entry['old'] ], [ 'meta_id' => (int) $entry['id'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery
					break;

				case 'option':
					$wpdb->update( $wpdb->options, [ 'option_value' => $entry['old'] ], [ 'option_id' => (int) $entry['id'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					break;

				case 'content':
					$wpdb->update( $wpdb->posts, [ 'post_content' => $entry['old'] ], [ 'ID' => (int) $entry['id'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					break;

				case 'moved':
					$wpdb->update( $wpdb->posts, [ 'ID' => (int) $entry['old'] ], [ 'ID' => (int) $entry['new'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					self::shift( (string) $entry['post_type'], (int) $entry['new'], (int) $entry['old'] );

					if ( in_array( $entry['post_type'], [ 'product', 'product_variation' ], true ) && self::has_table( $wpdb->prefix . 'woocommerce_order_itemmeta' ) ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}woocommerce_order_itemmeta SET meta_value = %s WHERE meta_key IN ('_product_id', '_variation_id') AND meta_value = %s", (string) $entry['old'], (string) $entry['new'] ) );
					}
					break;

				case 'deleted':
					$wpdb->insert( $wpdb->posts, $entry['post'] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

					foreach ( (array) $entry['meta'] as $meta ) {
						$wpdb->insert( $wpdb->postmeta, $meta ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					}
					break;

				default:
					continue 2;
			}

			$done++;
		}

		$run['state'] = 'undone';
		$run['log'][] = [ 'level' => 'info', 'text' => __( 'Undone.', 'pfh-widgets' ) ];
		update_option( self::RUN, $run, false );
		wp_cache_flush();

		return [ 'undone' => $done ];
	}
}
