<?php
/**
 * WebwinkelKeur review feed.
 *
 * Pulls the shop's ratings from the WebwinkelKeur dashboard API, normalises
 * them into one predictable shape and caches the result in a transient, so a
 * page view never waits on a remote call. Everything is filterable.
 *
 * Credentials, in order of precedence:
 *   1. the PFH_WEBWINKELKEUR_ID / PFH_WEBWINKELKEUR_CODE constants (wp-config)
 *   2. the `pfh_webwinkelkeur_credentials` filter
 *   3. the values typed into the element
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Reviews {

	/**
	 * Documented ratings endpoint.
	 */
	const ENDPOINT = 'https://dashboard.webwinkelkeur.nl/api/1.0/ratings.json';

	/**
	 * Transient prefix.
	 */
	const CACHE = 'pfh_wwk_reviews_';

	/**
	 * Cron hook used to warm the cache out of band.
	 */
	const CRON = 'pfh_widgets_refresh_reviews';

	/**
	 * The webshop id in productsforhome.nl's WebwinkelKeur URL.
	 */
	const DEFAULT_ID = '1222432';

	/**
	 * Public shop page, used as the review link when the API gives none.
	 */
	const SHOP_URL = 'https://www.webwinkelkeur.nl/webshop/Products-for-Home_1222432';

	public static function init() {
		add_action( self::CRON, [ __CLASS__, 'refresh' ] );
	}

	/**
	 * Documented shop summary endpoint.
	 */
	const SUMMARY_ENDPOINT = 'https://dashboard.webwinkelkeur.nl/api/1.0/ratings_summary.json';

	/**
	 * Transient holding the shop's score and review count.
	 */
	const SUMMARY_CACHE = 'pfh_wwk_summary';

	/**
	 * Resolve the API credentials.
	 *
	 * @param array $args Element settings (id, code).
	 * @return array{id:string, code:string}
	 */
	public static function credentials( array $args = [] ) {
		$id   = isset( $args['id'] ) ? trim( (string) $args['id'] ) : '';
		$code = isset( $args['code'] ) ? trim( (string) $args['code'] ) : '';

		if ( defined( 'PFH_WEBWINKELKEUR_ID' ) && PFH_WEBWINKELKEUR_ID ) {
			$id = (string) PFH_WEBWINKELKEUR_ID;
		}

		if ( defined( 'PFH_WEBWINKELKEUR_CODE' ) && PFH_WEBWINKELKEUR_CODE ) {
			$code = (string) PFH_WEBWINKELKEUR_CODE;
		}

		/**
		 * Filter the WebwinkelKeur credentials.
		 *
		 * @param array $credentials ['id' => string, 'code' => string]
		 */
		$out = apply_filters( 'pfh_webwinkelkeur_credentials', [ 'id' => $id, 'code' => $code ] );

		return [
			'id'   => isset( $out['id'] ) ? (string) $out['id'] : '',
			'code' => isset( $out['code'] ) ? (string) $out['code'] : '',
		];
	}

	/**
	 * Reviews, ready to render.
	 *
	 * Never throws and never blocks twice: a failed call caches a short-lived
	 * empty result so a broken key does not hammer the API on every page view.
	 *
	 * @param array $args {
	 *     @type string $id       Webshop id.
	 *     @type string $code     API code.
	 *     @type int    $limit    How many to ask for. Default 20.
	 *     @type int    $ttl      Cache lifetime in seconds. Default 12 hours.
	 *     @type int    $min      Drop reviews below this rating (1-10 scale). Default 0.
	 *     @type bool   $text     Require a written comment. Default true.
	 * }
	 * @return array<int, array<string, mixed>>
	 */
	public static function get( array $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'id'    => '',
				'code'  => '',
				'limit' => 20,
				'ttl'   => 12 * HOUR_IN_SECONDS,
				'min'   => 0,
				'text'  => true,
			]
		);

		$creds = self::credentials( $args );

		/**
		 * Short-circuit the whole feed — return an array of reviews to bypass
		 * the API entirely (useful for staging, tests, or a different source).
		 *
		 * @param null|array $reviews Null to continue.
		 * @param array      $args    Resolved arguments.
		 */
		$pre = apply_filters( 'pfh_webwinkelkeur_pre_reviews', null, $args );

		if ( is_array( $pre ) ) {
			return self::finish( $pre, $args );
		}

		if ( '' === $creds['id'] || '' === $creds['code'] ) {
			return [];
		}

		$limit = max( 1, min( 100, (int) $args['limit'] ) );
		$key   = self::CACHE . md5( $creds['id'] . '|' . $limit );
		$cached = get_transient( $key );

		if ( is_array( $cached ) ) {
			return self::finish( $cached, $args );
		}

		$rows = self::request( $creds['id'], $creds['code'], $limit );

		/**
		 * Filter the cache lifetime. A failed call is cached for 15 minutes so
		 * the site degrades quietly instead of retrying on every request.
		 *
		 * @param int  $ttl    Seconds.
		 * @param bool $failed Whether the request failed.
		 */
		$ttl = (int) apply_filters(
			'pfh_webwinkelkeur_cache_ttl',
			null === $rows ? 15 * MINUTE_IN_SECONDS : (int) $args['ttl'],
			null === $rows
		);

		$rows = is_array( $rows ) ? $rows : [];

		set_transient( $key, $rows, max( 60, $ttl ) );

		return self::finish( $rows, $args );
	}

	/**
	 * Apply the display-time filters that must not be baked into the cache.
	 *
	 * @param array $rows Normalised reviews.
	 * @param array $args Resolved arguments.
	 * @return array
	 */
	private static function finish( array $rows, array $args ) {
		$min  = (float) $args['min'];
		$need = ! empty( $args['text'] );
		$out  = [];

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			if ( $min > 0 && isset( $row['rating10'] ) && (float) $row['rating10'] < $min ) {
				continue;
			}

			if ( $need && '' === trim( (string) ( isset( $row['text'] ) ? $row['text'] : '' ) ) ) {
				continue;
			}

			$out[] = $row;
		}

		/**
		 * Filter the reviews just before they are rendered.
		 *
		 * @param array $out  Normalised reviews.
		 * @param array $args Resolved arguments.
		 */
		return (array) apply_filters( 'pfh_webwinkelkeur_reviews', $out, $args );
	}

	/**
	 * One HTTP call to WebwinkelKeur.
	 *
	 * @param string $id    Webshop id.
	 * @param string $code  API code.
	 * @param int    $limit Row count.
	 * @return array|null Normalised rows, or null when the call failed.
	 */
	private static function request( $id, $code, $limit ) {
		/**
		 * Filter the endpoint, for the sandbox or a proxy.
		 *
		 * @param string $endpoint Absolute URL, no query string.
		 */
		$endpoint = (string) apply_filters( 'pfh_webwinkelkeur_endpoint', self::ENDPOINT );

		$url = add_query_arg(
			[
				'id'    => rawurlencode( $id ),
				'code'  => rawurlencode( $code ),
				'limit' => (int) $limit,
			],
			$endpoint
		);

		/**
		 * Filter the wp_remote_get arguments.
		 *
		 * @param array $request Request arguments.
		 */
		$request = (array) apply_filters(
			'pfh_webwinkelkeur_request_args',
			[
				'timeout'    => 8,
				'redirection' => 2,
				'headers'    => [ 'Accept' => 'application/json' ],
				'user-agent' => 'PFH-Widgets/' . PFH_WIDGETS_VERSION . '; ' . home_url( '/' ),
			]
		);

		$response = wp_remote_get( $url, $request );

		if ( is_wp_error( $response ) ) {
			self::log( 'request failed: ' . $response->get_error_message() );

			return null;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );

		if ( $status < 200 || $status >= 300 ) {
			self::log( 'unexpected status ' . $status );

			return null;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) ) {
			self::log( 'response was not JSON' );

			return null;
		}

		return self::normalize( $body );
	}

	/**
	 * Turn whatever the API returned into one predictable shape.
	 *
	 * The payload has moved around between API versions — a bare list, a list
	 * under `data`, or one under `ratings` — and field names vary with it, so
	 * every lookup below tries the known aliases rather than assuming one.
	 *
	 * @param array $body Decoded JSON.
	 * @return array<int, array<string, mixed>>
	 */
	public static function normalize( array $body ) {
		$rows = $body;

		foreach ( [ 'data', 'ratings', 'reviews', 'items' ] as $wrapper ) {
			if ( isset( $body[ $wrapper ] ) && is_array( $body[ $wrapper ] ) ) {
				$rows = $body[ $wrapper ];
				break;
			}
		}

		$out = [];

		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$text = self::pick( $row, [ 'comment', 'review', 'text', 'content', 'message' ] );
			$name = self::pick( $row, [ 'name', 'customer_name', 'consumer_name', 'author', 'firstname' ] );
			$city = self::pick( $row, [ 'city', 'place', 'location', 'town' ] );
			$date = self::pick( $row, [ 'created', 'created_at', 'date', 'datetime', 'timestamp' ] );

			$rating = self::pick( $row, [ 'rating', 'score', 'total', 'average', 'stars' ] );
			$rating = '' === $rating ? null : (float) $rating;

			$out[] = [
				'id'       => (string) self::pick( $row, [ 'id', 'rating_id', 'uuid' ] ),
				'name'     => (string) $name,
				'city'     => (string) $city,
				'text'     => (string) $text,
				'date'     => (string) $date,
				'rating10' => $rating,
				'url'      => (string) self::pick( $row, [ 'url', 'link', 'permalink' ] ),
			];
		}

		return $out;
	}

	/**
	 * First non-empty value among a list of possible keys.
	 *
	 * @param array $row  Source row.
	 * @param array $keys Candidate keys, best first.
	 * @return string|int|float
	 */
	public static function pick( array $row, array $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $row[ $key ] ) && ! is_array( $row[ $key ] ) && '' !== $row[ $key ] ) {
				return $row[ $key ];
			}
		}

		return '';
	}

	/**
	 * Convert a WebwinkelKeur score to stars.
	 *
	 * @param float|null $rating Raw rating.
	 * @param int        $scale  Scale the API uses: 10 or 5.
	 * @param int        $stars  Number of stars shown. Default 5.
	 * @return float Rounded to the nearest half star.
	 */
	public static function stars( $rating, $scale = 10, $stars = 5 ) {
		if ( null === $rating || '' === $rating ) {
			return (float) $stars;
		}

		$scale = (int) $scale > 0 ? (int) $scale : 10;
		$value = ( (float) $rating / $scale ) * (int) $stars;

		return max( 0, min( (float) $stars, round( $value * 2 ) / 2 ) );
	}

	/**
	 * The shop's headline score and total review count.
	 *
	 * Comes from the summary endpoint, which reports the whole shop — the
	 * ratings list is capped by `limit`, so counting that would understate the
	 * total. When the summary is unavailable this returns nulls rather than a
	 * guess, and the caller falls back to whatever was typed in the panel.
	 *
	 * @param array $args Optional id / code overrides.
	 * @return array{rating: float|null, count: int|null, scale: int}
	 */
	public static function summary( array $args = [] ) {
		$empty = [ 'rating' => null, 'count' => null, 'scale' => 10 ];

		/**
		 * Short-circuit the summary, e.g. on staging.
		 *
		 * @param null|array $summary Null to continue.
		 */
		$pre = apply_filters( 'pfh_webwinkelkeur_pre_summary', null );

		if ( is_array( $pre ) ) {
			return wp_parse_args( $pre, $empty );
		}

		$creds = self::credentials( $args );

		if ( '' === $creds['id'] || '' === $creds['code'] ) {
			return $empty;
		}

		$key    = self::SUMMARY_CACHE . '_' . md5( $creds['id'] );
		$cached = get_transient( $key );

		if ( is_array( $cached ) ) {
			return wp_parse_args( $cached, $empty );
		}

		$endpoint = (string) apply_filters( 'pfh_webwinkelkeur_summary_endpoint', self::SUMMARY_ENDPOINT );

		$url = add_query_arg(
			[
				'id'   => rawurlencode( $creds['id'] ),
				'code' => rawurlencode( $creds['code'] ),
			],
			$endpoint
		);

		$response = wp_remote_get(
			$url,
			(array) apply_filters(
				'pfh_webwinkelkeur_request_args',
				[
					'timeout'     => 8,
					'redirection' => 2,
					'headers'     => [ 'Accept' => 'application/json' ],
					'user-agent'  => 'PFH-Widgets/' . PFH_WIDGETS_VERSION . '; ' . home_url( '/' ),
				]
			)
		);

		$out = $empty;

		if ( ! is_wp_error( $response ) ) {
			$status = (int) wp_remote_retrieve_response_code( $response );
			$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

			if ( $status >= 200 && $status < 300 && is_array( $body ) ) {
				$data = $body;

				foreach ( [ 'data', 'summary', 'ratings_summary' ] as $wrapper ) {
					if ( isset( $body[ $wrapper ] ) && is_array( $body[ $wrapper ] ) ) {
						$data = $body[ $wrapper ];
						break;
					}
				}

				$rating = self::pick( $data, [ 'rating', 'average', 'score', 'average_rating' ] );
				$count  = self::pick( $data, [ 'amount', 'count', 'total', 'number_of_ratings', 'ratings' ] );

				if ( '' !== $rating ) {
					$out['rating'] = round( (float) $rating, 1 );
				}

				if ( '' !== $count ) {
					$out['count'] = (int) $count;
				}
			} else {
				self::log( 'summary: unexpected response' );
			}
		} else {
			self::log( 'summary: ' . $response->get_error_message() );
		}

		$ttl = ( null === $out['rating'] && null === $out['count'] )
			? 15 * MINUTE_IN_SECONDS
			: 12 * HOUR_IN_SECONDS;

		set_transient( $key, $out, $ttl );

		return $out;
	}

	/**
	 * Where a review badge should link to.
	 *
	 * The sticky badge's configured link if there is one, otherwise the shop
	 * page for the webshop id in use.
	 *
	 * @return string
	 */
	public static function review_url() {
		if ( class_exists( 'PFH_Widgets_Badge' ) ) {
			$url = trim( (string) PFH_Widgets_Badge::get( 'link_url', '' ) );

			if ( '' !== $url ) {
				return $url;
			}
		}

		$creds = self::credentials();

		if ( '' !== $creds['id'] && self::DEFAULT_ID !== $creds['id'] ) {
			return 'https://www.webwinkelkeur.nl/webshop/_' . rawurlencode( $creds['id'] );
		}

		return self::SHOP_URL;
	}

	/**
	 * Display-ready figures for any review badge.
	 *
	 * One place that decides what a score and a count look like, so the hero,
	 * the footer, the inline rating and the sticky badge cannot drift apart.
	 *
	 * Falls back to the values typed into the element whenever the feed is
	 * unreachable, and always inside the builder — editing should never wait
	 * on a remote call, and the panel should show exactly what a broken feed
	 * would put on the page.
	 *
	 * @param array $args {
	 *     @type bool   $live     Consult the API at all. Default true.
	 *     @type int    $scale    Show the score out of 5 or out of 10. Default 5.
	 *     @type int    $decimals Decimal places on the score. Default 1.
	 *     @type string $score    Fallback score.
	 *     @type int    $count    Fallback count.
	 *     @type int    $round    Round the displayed count down to a multiple
	 *                            of this, for a "270+" style label. 0 = exact.
	 * }
	 * @return array{score:string, count:int, shown:int, stars:float, live:bool}
	 */
	public static function figures( array $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'live'     => true,
				'scale'    => 5,
				'decimals' => 1,
				'score'    => '',
				'count'    => 0,
				'round'    => 0,
			]
		);

		$scale  = 10 === (int) $args['scale'] ? 10 : 5;
		$score  = (string) $args['score'];
		$count  = (int) $args['count'];
		$rating = null;
		$live   = false;

		if ( ! empty( $args['live'] ) && ! PFH_Widgets_Helpers::is_builder_context() ) {
			$summary = self::summary();

			if ( null !== $summary['rating'] ) {
				$rating = (float) $summary['rating'];
				$score  = number_format_i18n( 10 === $scale ? $rating : ( $rating / 10 ) * 5, (int) $args['decimals'] );
				$live   = true;
			}

			if ( null !== $summary['count'] ) {
				$count = (int) $summary['count'];
				$live  = true;
			}
		}

		// No live rating: derive the stars from whatever score is on display,
		// so the row still agrees with itself.
		if ( null === $rating ) {
			$typed  = (float) str_replace( ',', '.', $score );
			$rating = 5 === $scale ? $typed * 2 : $typed;
		}

		$shown = $count;
		$round = (int) $args['round'];

		if ( $round > 1 && $shown >= $round ) {
			$shown = (int) floor( $shown / $round ) * $round;
		}

		return [
			'score' => $score,
			'count' => $count,
			'shown' => $shown,
			'scale' => $scale,
			'stars' => self::stars( $rating, 10, 5 ),
			'live'  => $live,
		];
	}

	/**
	 * Put the live figures into a label.
	 *
	 * Explicit tokens win: %score%, %count%, %total%, and %s for the count,
	 * which is what the footer badge has always used.
	 *
	 * When a label carries no token at all the numbers already written into it
	 * are replaced instead. That matters because "use the live rating" is a
	 * switch people turn on expecting it to work — a label reading
	 * "270 reviews on" silently keeping its 270 forever is the switch lying.
	 *
	 * @param string $text    Label.
	 * @param array  $figures Output of figures().
	 * @return string
	 */
	public static function tokens( $text, array $figures ) {
		$text = (string) $text;

		if ( '' === $text ) {
			return $text;
		}

		$map = [
			'%score%' => (string) $figures['score'],
			'%count%' => number_format_i18n( (int) $figures['shown'] ),
			'%total%' => number_format_i18n( (int) $figures['count'] ),
			'%s'      => number_format_i18n( (int) $figures['shown'] ),
		];

		foreach ( array_keys( $map ) as $token ) {
			if ( false !== strpos( $text, $token ) ) {
				return strtr( $text, $map );
			}
		}

		// Nothing typed is worth overwriting when the feed is down.
		if ( empty( $figures['live'] ) ) {
			return $text;
		}

		return self::renumber( $text, $figures );
	}

	/**
	 * Replace the numbers already written into a label.
	 *
	 * The first number is the count, a trailing decimal is the score — which
	 * is how every label this plugin ships is shaped: "270 reviews on",
	 * "(270+ reviews) 5.0". A label carrying three or more numbers is too
	 * ambiguous to guess at, so it is left exactly as typed.
	 *
	 * @param string $text    Label.
	 * @param array  $figures Output of figures().
	 * @return string
	 */
	private static function renumber( $text, array $figures ) {
		if ( ! preg_match_all( '/\d+(?:[.,]\d+)*/', $text, $found, PREG_OFFSET_CAPTURE ) ) {
			return $text;
		}

		$runs = $found[0];

		if ( count( $runs ) > 2 ) {
			return $text;
		}

		$out   = $text;
		$first = $runs[0];
		$score = (string) $figures['score'];

		// "9.7/10" and "4.9/5" are ratios: the leading number is the score,
		// not a count. Renumbering it to the review total gives "396/10".
		$after = substr( $text, $first[1] + strlen( $first[0] ), 1 );

		if ( '/' === $after ) {
			// Only rewrite when the denominator agrees with the scale the
			// score is on — putting a 9.7 into "x/5" is worse than leaving it.
			$denominator = isset( $runs[1] ) ? (int) $runs[1][0] : 0;

			if ( '' === $score || $denominator !== (int) $figures['scale'] ) {
				return $text;
			}

			return substr_replace( $text, $score, $first[1], strlen( $first[0] ) );
		}

		$last = $runs[ count( $runs ) - 1 ];

		// Back to front, so the first run's offset stays valid.
		if ( count( $runs ) > 1 && preg_match( '/^\d+[.,]\d+$/', $last[0] ) && '' !== $score ) {
			$out = substr_replace( $out, $score, $last[1], strlen( $last[0] ) );
		}

		return substr_replace( $out, number_format_i18n( (int) $figures['shown'] ), $first[1], strlen( $first[0] ) );
	}

	/**
	 * Drop every cached feed.
	 */
	public static function flush() {
		delete_transient( self::SUMMARY_CACHE );
		global $wpdb;

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return;
		}

		$like = $wpdb->esc_like( '_transient_' . self::CACHE ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- transients have no bulk API.
		$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );

		foreach ( (array) $names as $name ) {
			delete_transient( str_replace( '_transient_', '', $name ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- transients have no bulk API.
		$summaries = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::SUMMARY_CACHE ) . '%'
			)
		);

		foreach ( (array) $summaries as $name ) {
			delete_transient( str_replace( '_transient_', '', $name ) );
		}
	}

	/**
	 * Cron callback: drop the cache and, when the credentials live outside the
	 * element (constants or a filter), refill it straight away.
	 *
	 * Warming here means the refetch happens on a cron request rather than on
	 * a visitor's. With the credentials only in the element we cannot know them
	 * from cron, so the cache is simply dropped and the next view refills it.
	 */
	public static function refresh() {
		self::flush();

		$creds = self::credentials();

		if ( '' !== $creds['id'] && '' !== $creds['code'] ) {
			self::get();
		}
	}

	/**
	 * @param string $message Diagnostic.
	 */
	private static function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[PFH WebwinkelKeur] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
