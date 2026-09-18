<?php
/**
 * The Instagram feed, and the screen that connects it.
 *
 * One remote call every few hours, cached, normalised into the shape the strip
 * renders. A shop with no token, an expired token or an Instagram outage is not
 * a broken page: the feed comes back empty and the strip falls back to the
 * pictures set in Bricks.
 *
 * Credentials, in order of precedence:
 *   1. the PFH_INSTAGRAM_TOKEN / PFH_INSTAGRAM_USER constants (wp-config)
 *   2. the `pfh_instagram_credentials` filter
 *   3. the values typed into Products For Home → Instagram
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Instagram extends PFH_Settings_Module {

	const OPTION = 'pfh_instagram';

	/**
	 * Media endpoint.
	 *
	 * graph.instagram.com, not the old Basic Display host: that API was shut
	 * off at the end of 2024 and its tokens stopped answering with it. A
	 * business account going through the Facebook login uses
	 * graph.facebook.com instead, which answers the same fields — hence the
	 * filter rather than a hard-coded host.
	 */
	const ENDPOINT = 'https://graph.instagram.com/v21.0/';

	/**
	 * Long-lived token refresh endpoint.
	 */
	const REFRESH = 'https://graph.instagram.com/refresh_access_token';

	/**
	 * Transient prefix for the feed itself.
	 */
	const CACHE = 'pfh_ig_feed_';

	/**
	 * Cron hook that keeps the token alive.
	 */
	const CRON = 'pfh_widgets_refresh_instagram';

	/**
	 * What a long-lived token is worth when Instagram does not say.
	 */
	const LIFETIME = 60 * DAY_IN_SECONDS;

	public static function init() {
		PFH_Widgets_Settings::register( 'instagram', __( 'Instagram', 'pfh-widgets' ), __CLASS__ );

		add_action( self::CRON, [ __CLASS__, 'keep_alive' ] );
		add_action( 'pfh_widgets_settings_saved', [ __CLASS__, 'maybe_flush' ], 10, 2 );

		// Scheduled on init, where the review refresh is scheduled too, rather
		// than here on plugins_loaded: cron is a stored option, and reading it
		// before WordPress is fully up is the kind of ordering that holds
		// until one host loads something in a different order.
		add_action( 'init', [ __CLASS__, 'schedule' ] );
	}

	/**
	 * Keep one daily event booked, so the token is refreshed out of band.
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON );
		}
	}

	/**
	 * Drop the cached feed when the token changes.
	 *
	 * @param string $tab   Saved tab.
	 * @param string $class Saved module.
	 */
	public static function maybe_flush( $tab, $class ) {
		if ( __CLASS__ !== $class ) {
			return;
		}

		self::forget();
		self::flush();
	}

	/* ---------------------------------------------------------------------
	 * Credentials
	 * ------------------------------------------------------------------ */

	/**
	 * Resolve the token and the account it belongs to.
	 *
	 * @return array{token:string, user:string, locked:bool}
	 */
	public static function credentials() {
		$token  = (string) self::get( 'token', '' );
		$user   = (string) self::get( 'user_id', '' );
		$locked = false;

		if ( defined( 'PFH_INSTAGRAM_TOKEN' ) && PFH_INSTAGRAM_TOKEN ) {
			$token  = (string) PFH_INSTAGRAM_TOKEN;
			$locked = true;
		}

		if ( defined( 'PFH_INSTAGRAM_USER' ) && PFH_INSTAGRAM_USER ) {
			$user   = (string) PFH_INSTAGRAM_USER;
			$locked = true;
		}

		/**
		 * Filter the Instagram credentials.
		 *
		 * @param array $credentials ['token' => string, 'user' => string]
		 */
		$out = apply_filters( 'pfh_instagram_credentials', [ 'token' => trim( $token ), 'user' => trim( $user ) ] );

		return [
			'token'  => isset( $out['token'] ) ? (string) $out['token'] : '',
			'user'   => isset( $out['user'] ) ? (string) $out['user'] : '',
			'locked' => $locked,
		];
	}

	/**
	 * Whether a token has been supplied at all.
	 *
	 * @return bool
	 */
	public static function connected() {
		$creds = self::credentials();

		return '' !== $creds['token'];
	}

	/* ---------------------------------------------------------------------
	 * The feed
	 * ------------------------------------------------------------------ */

	/**
	 * Posts, ready to render.
	 *
	 * Never throws and never blocks twice: a failed call caches an empty
	 * result briefly, so a revoked token does not put an HTTP request in front
	 * of every page view.
	 *
	 * @param array $args {
	 *     @type int $limit How many to ask for. Default 12.
	 *     @type int $ttl   Cache lifetime in seconds. Default 6 hours.
	 * }
	 * @return array<int, array{url:string, thumb:string, permalink:string, alt:string, type:string}>
	 */
	public static function posts( array $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'limit' => 12,
				'ttl'   => max( 1, (int) self::get( 'ttl', 6 ) ) * HOUR_IN_SECONDS,
			]
		);

		$limit = max( 1, min( 50, (int) $args['limit'] ) );

		/**
		 * Short-circuit the feed — return an array of posts to bypass the API
		 * entirely (staging, tests, or a different source).
		 *
		 * @param null|array $posts Null to continue.
		 * @param array      $args  Resolved arguments.
		 */
		$pre = apply_filters( 'pfh_instagram_pre_feed', null, $args );

		if ( is_array( $pre ) ) {
			return array_slice( self::normalise( $pre ), 0, $limit );
		}

		$creds = self::credentials();

		if ( '' === $creds['token'] ) {
			return [];
		}

		$key    = self::CACHE . md5( $creds['token'] . '|' . $creds['user'] . '|' . $limit );
		$cached = get_transient( $key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$rows = self::request( $creds, $limit );

		/**
		 * Filter the cache lifetime. A failed call is held for 15 minutes so
		 * the shop degrades quietly instead of retrying on every request.
		 *
		 * @param int  $ttl    Seconds.
		 * @param bool $failed Whether the request failed.
		 */
		$ttl = (int) apply_filters(
			'pfh_instagram_cache_ttl',
			null === $rows ? 15 * MINUTE_IN_SECONDS : (int) $args['ttl'],
			null === $rows
		);

		$rows = is_array( $rows ) ? $rows : [];

		set_transient( $key, $rows, max( 60, $ttl ) );

		return $rows;
	}

	/**
	 * One HTTP call to Instagram.
	 *
	 * @param array $creds Credentials.
	 * @param int   $limit Row count.
	 * @return array|null Normalised rows, or null when the call failed.
	 */
	private static function request( array $creds, $limit ) {
		$user = '' !== $creds['user'] ? $creds['user'] : 'me';

		/**
		 * Filter the media endpoint, for a business account on
		 * graph.facebook.com or a proxy. Must end with a slash.
		 *
		 * @param string $endpoint Base URL.
		 * @param array  $creds    Credentials.
		 */
		$base = (string) apply_filters( 'pfh_instagram_endpoint', self::ENDPOINT, $creds );

		$url = add_query_arg(
			[
				'fields'       => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp',
				'limit'        => (int) $limit,
				'access_token' => rawurlencode( $creds['token'] ),
			],
			trailingslashit( $base ) . rawurlencode( $user ) . '/media'
		);

		$response = wp_remote_get(
			$url,
			(array) apply_filters(
				'pfh_instagram_request_args',
				[
					'timeout'     => 8,
					'redirection' => 2,
					'headers'     => [ 'Accept' => 'application/json' ],
				]
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return null;
		}

		return self::normalise( $body['data'] );
	}

	/**
	 * Turn whatever the API returned into the one shape the strip draws.
	 *
	 * @param array $rows Raw rows.
	 * @return array<int, array<string, string>>
	 */
	private static function normalise( array $rows ) {
		$out = [];

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$type  = isset( $row['media_type'] ) ? strtoupper( (string) $row['media_type'] ) : 'IMAGE';
			$media = isset( $row['media_url'] ) ? (string) $row['media_url'] : '';
			$thumb = isset( $row['thumbnail_url'] ) ? (string) $row['thumbnail_url'] : '';

			// A video's media_url is the file itself; its poster is the
			// thumbnail, which is the only one of the two an <img> can draw.
			$url = ( 'VIDEO' === $type && '' !== $thumb ) ? $thumb : $media;

			if ( '' === $url ) {
				$url = $thumb;
			}

			$url = esc_url_raw( $url );

			/*
			 * Not wp_http_validate_url(): that is the guard for a request this
			 * server is about to make, and it resolves the host to prove it is
			 * not a private address — a DNS lookup for every picture, during a
			 * page render. These URLs are going into an img src, where the
			 * only thing that matters is that they survived escaping and are
			 * really http(s).
			 */
			if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
				continue;
			}

			$out[] = [
				'url'       => $url,
				'thumb'     => '' !== $thumb ? esc_url_raw( $thumb ) : $url,
				'permalink' => isset( $row['permalink'] ) ? esc_url_raw( (string) $row['permalink'] ) : '',
				'alt'       => self::caption( isset( $row['caption'] ) ? (string) $row['caption'] : '' ),
				'type'      => $type,
			];
		}

		/**
		 * Filter the posts just before they are cached.
		 *
		 * @param array $out Normalised rows.
		 */
		return (array) apply_filters( 'pfh_instagram_posts', $out );
	}

	/**
	 * A caption, cut down to something usable as alt text.
	 *
	 * Emoji are dropped rather than kept. The feed is cached in the options
	 * table, and on a shop whose tables are still utf8 rather than utf8mb4 a
	 * 4-byte character makes the write fail outright — the transient is never
	 * stored, and every page view then goes back to Instagram. Alt text loses
	 * nothing by leaving them out.
	 *
	 * @param string $caption Raw caption.
	 * @return string
	 */
	private static function caption( $caption ) {
		$caption = preg_replace( '/[\x{10000}-\x{10FFFF}]/u', '', $caption );
		$caption = trim( preg_replace( '/\s+/u', ' ', (string) $caption ) );

		if ( '' === $caption ) {
			return '';
		}

		// Hashtag tails read as noise in a screen reader.
		$caption = trim( preg_replace( '/(?:\s#[^\s#]+)+\s*$/u', '', $caption ) );

		if ( function_exists( 'mb_substr' ) && function_exists( 'mb_strlen' ) && mb_strlen( $caption ) > 120 ) {
			return rtrim( mb_substr( $caption, 0, 117 ) ) . '...';
		}

		return $caption;
	}

	/**
	 * Forget every cached feed.
	 */
	public static function flush() {
		global $wpdb;

		if ( ! isset( $wpdb ) ) {
			return;
		}

		$names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::CACHE ) . '%'
			)
		);

		foreach ( (array) $names as $name ) {
			delete_transient( substr( (string) $name, strlen( '_transient_' ) ) );
		}
	}

	/* ---------------------------------------------------------------------
	 * Keeping the token alive
	 * ------------------------------------------------------------------ */

	/**
	 * Refresh the stored long-lived token before it lapses.
	 *
	 * Instagram's long-lived tokens last 60 days and are refreshable once they
	 * are a day old. Without this the strip works for two months and then
	 * quietly falls back to the pictures in Bricks, which is the kind of
	 * failure nobody notices until a client does.
	 *
	 * Only a token in the database is refreshed. One defined in wp-config is
	 * the site owner's to manage, and cannot be written back anyway.
	 */
	public static function keep_alive() {
		$creds = self::credentials();

		if ( $creds['locked'] || '' === $creds['token'] ) {
			return;
		}

		$stored = (int) self::get( 'token_set', 0 );

		// Instagram refuses a token less than 24 hours old, and there is no
		// point asking more than once a week.
		if ( $stored && ( time() - $stored ) < WEEK_IN_SECONDS ) {
			return;
		}

		$response = wp_remote_get(
			add_query_arg(
				[
					'grant_type'   => 'ig_refresh_token',
					'access_token' => rawurlencode( $creds['token'] ),
				],
				(string) apply_filters( 'pfh_instagram_refresh_endpoint', self::REFRESH )
			),
			[ 'timeout' => 8 ]
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['access_token'] ) ) {
			return;
		}

		$all              = self::all();
		$all['token']     = (string) $body['access_token'];
		$all['token_set'] = time();
		$all['expires']   = time() + ( isset( $body['expires_in'] ) ? (int) $body['expires_in'] : self::LIFETIME );

		update_option( self::OPTION, $all );

		// all() memoises for the request, and the refreshed token has to be
		// the one anything asking later gets.
		self::forget();
		self::flush();
	}

	/**
	 * Remember when a token was typed in, so the refresh has a clock to work
	 * from and the screen can say when it lapses.
	 *
	 * @param array $raw Posted values.
	 * @return array
	 */
	public static function sanitize( array $raw ) {
		$clean = parent::sanitize( $raw );
		$was   = (string) self::get( 'token', '' );

		if ( isset( $clean['token'] ) && $clean['token'] !== $was ) {
			$clean['token_set'] = time();
			$clean['expires']   = '' === $clean['token'] ? 0 : time() + self::LIFETIME;
		} else {
			$clean['token_set'] = (int) self::get( 'token_set', 0 );
			$clean['expires']   = (int) self::get( 'expires', 0 );
		}

		return $clean;
	}

	/* ---------------------------------------------------------------------
	 * The settings tab
	 * ------------------------------------------------------------------ */

	/**
	 * @return array
	 */
	public static function fields() {
		$locked = defined( 'PFH_INSTAGRAM_TOKEN' ) || defined( 'PFH_INSTAGRAM_USER' );

		return [
			'account' => [
				'label'  => __( 'Account', 'pfh-widgets' ),
				'desc'   => $locked
					? __( 'The <code>PFH_INSTAGRAM_TOKEN</code> / <code>PFH_INSTAGRAM_USER</code> constants are defined in wp-config.php and take precedence over anything typed here.', 'pfh-widgets' )
					: __( 'Fills the Instagram strip. Without a token the strip shows the pictures set on the element in Bricks, so the section is never empty. For production, prefer defining <code>PFH_INSTAGRAM_TOKEN</code> in wp-config.php — a token in the database ends up in every backup and export.', 'pfh-widgets' ),
				'fields' => [
					'token' => [
						'type'    => 'password',
						'label'   => __( 'Access token', 'pfh-widgets' ),
						'default' => '',
						'desc'    => __( 'A long-lived Instagram access token. It is refreshed automatically each week, so it does not lapse after 60 days.', 'pfh-widgets' ),
					],
					'user_id' => [
						'type'        => 'text',
						'label'       => __( 'Account ID', 'pfh-widgets' ),
						'placeholder' => 'me',
						'default'     => '',
						'desc'        => __( 'Only needed for a business account reached through the Facebook login. Leave empty for the account the token itself belongs to.', 'pfh-widgets' ),
					],
					'ttl' => [
						'type'    => 'number',
						'label'   => __( 'Refresh every', 'pfh-widgets' ),
						'suffix'  => __( 'hours', 'pfh-widgets' ),
						'min'     => 1,
						'max'     => 168,
						'default' => 6,
						'desc'    => __( 'How long a fetched feed is kept before Instagram is asked again.', 'pfh-widgets' ),
					],
					'status' => [
						'type'     => 'info',
						'callback' => [ __CLASS__, 'render_status' ],
					],
				],
			],
		];
	}

	/**
	 * Say plainly whether the strip is running on the feed or on Bricks.
	 */
	public static function render_status() {
		if ( ! self::connected() ) {
			echo '<div class="pfh-settings__note pfh-settings__note--warn"><p>'
				. esc_html__( 'No token yet. The Instagram strip is showing the pictures set on the element in Bricks.', 'pfh-widgets' )
				. '</p></div>';

			return;
		}

		$posts = self::posts( [ 'limit' => 12 ] );

		if ( ! $posts ) {
			echo '<div class="pfh-settings__note pfh-settings__note--bad"><p><strong>'
				. esc_html__( 'Instagram did not answer.', 'pfh-widgets' ) . '</strong> '
				. esc_html__( 'The strip is showing the pictures set in Bricks meanwhile. Check the token — a failed call is held for 15 minutes, so give it that long after correcting it, or save this page again, which clears it.', 'pfh-widgets' )
				. '</p></div>';

			return;
		}

		$expires = (int) self::get( 'expires', 0 );
		$note    = sprintf(
			/* translators: post count. */
			_n( 'Connected. Showing %s post from the feed.', 'Connected. Showing %s posts from the feed.', count( $posts ), 'pfh-widgets' ),
			number_format_i18n( count( $posts ) )
		);

		if ( $expires > time() ) {
			$note .= ' ' . sprintf(
				/* translators: human-readable duration, e.g. "2 months". */
				__( 'The token lapses in %s and is refreshed automatically before then.', 'pfh-widgets' ),
				human_time_diff( time(), $expires )
			);
		}

		echo '<div class="pfh-settings__note pfh-settings__note--good"><p>' . esc_html( $note ) . '</p></div>';
	}
}
