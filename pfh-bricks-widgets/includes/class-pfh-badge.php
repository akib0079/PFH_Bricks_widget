<?php
/**
 * WebwinkelKeur settings and the sticky trust badge.
 *
 * Replaces the WebwinkelKeur plugin. The credentials move into this plugin's
 * settings and become the shared source for every review surface — the slider,
 * the inline rating and this badge all read the same cached feed, so the store
 * makes one remote call a day rather than one per widget.
 *
 * Constants still win over the stored values, so a key can be kept out of the
 * database on staging.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Badge extends PFH_Settings_Module {

	const OPTION = 'pfh_badge';

	public static function init() {
		PFH_Widgets_Settings::register(
			'reviews',
			static function () {
				return __( 'WebwinkelKeur', 'pfh-widgets' );
			},
			__CLASS__
		);

		// Make the stored credentials the fallback for every review surface.
		add_filter( 'pfh_webwinkelkeur_credentials', [ __CLASS__, 'credentials' ] );

		// A changed key must not keep serving the old shop's cache.
		add_action( 'pfh_widgets_settings_saved', [ __CLASS__, 'maybe_flush' ], 10, 2 );

		// Whether the badge is on is asked when the page is drawn, not here:
		// reading a setting builds the translated settings schema, and on
		// plugins_loaded that logs "translation loading was triggered too
		// early" on every request.
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_action( 'wp_footer', [ __CLASS__, 'render' ], 30 );
	}

	/**
	 * Supply the credentials typed into the settings screen.
	 *
	 * Runs after the constants have had their say, so it only fills gaps.
	 *
	 * @param array $creds Credentials so far.
	 * @return array
	 */
	public static function credentials( $creds ) {
		$creds = is_array( $creds ) ? $creds : [];

		if ( empty( $creds['id'] ) ) {
			$creds['id'] = (string) self::get( 'shop_id', '' );
		}

		if ( empty( $creds['code'] ) ) {
			$creds['code'] = (string) self::get( 'api_key', '' );
		}

		return $creds;
	}

	/**
	 * Drop the cached feed when the credentials change.
	 *
	 * @param string $tab   Saved tab.
	 * @param string $class Saved module.
	 */
	public static function maybe_flush( $tab, $class ) {
		if ( __CLASS__ !== $class ) {
			return;
		}

		self::forget();
		PFH_Widgets_Reviews::flush();
	}

	/**
	 * @return array
	 */
	public static function fields() {
		$locked = defined( 'PFH_WEBWINKELKEUR_ID' ) || defined( 'PFH_WEBWINKELKEUR_CODE' );

		return [
			'account' => [
				'label'  => __( 'Account', 'pfh-widgets' ),
				'desc'   => $locked
					? __( 'The <code>PFH_WEBWINKELKEUR_ID</code> / <code>PFH_WEBWINKELKEUR_CODE</code> constants are defined in wp-config.php and take precedence over anything typed here.', 'pfh-widgets' )
					: __( 'These feed the review slider, the inline rating and the badge below. For production, prefer defining <code>PFH_WEBWINKELKEUR_ID</code> and <code>PFH_WEBWINKELKEUR_CODE</code> in wp-config.php — a key in the database ends up in every backup and export.', 'pfh-widgets' ),
				'fields' => [
					'shop_id' => [
						'type'        => 'text',
						'label'       => __( 'Webshop ID', 'pfh-widgets' ),
						'placeholder' => PFH_Widgets_Reviews::DEFAULT_ID,
						'default'     => '',
					],
					'api_key' => [
						'type'    => 'password',
						'label'   => __( 'API key', 'pfh-widgets' ),
						'default' => '',
						'desc'    => __( 'From the WebwinkelKeur dashboard, under Settings → API.', 'pfh-widgets' ),
					],
					'status' => [
						'type'     => 'info',
						'callback' => [ __CLASS__, 'render_status' ],
					],
				],
			],

			'badge' => [
				'label'  => __( 'Sticky badge', 'pfh-widgets' ),
				'desc'   => __( 'A fixed trust panel anchored to the side of every page, filled from the live feed.', 'pfh-widgets' ),
				'fields' => [
					'enabled' => [
						'type'     => 'checkbox',
						'label'    => __( 'Sticky badge', 'pfh-widgets' ),
						'cb_label' => __( 'Show the badge on the front end', 'pfh-widgets' ),
						'default'  => false,
					],
					'side' => [
						'type'    => 'select',
						'label'   => __( 'Side', 'pfh-widgets' ),
						'default' => 'left',
						'choices' => [
							'left'  => __( 'Left', 'pfh-widgets' ),
							'right' => __( 'Right', 'pfh-widgets' ),
						],
					],
					'offset' => [
						'type'    => 'number',
						'label'   => __( 'Distance from the bottom', 'pfh-widgets' ),
						'suffix'  => 'px',
						'min'     => 0,
						'max'     => 600,
						'default' => 180,
					],
					'open_default' => [
						'type'     => 'checkbox',
						'label'    => __( 'Opens', 'pfh-widgets' ),
						'cb_label' => __( 'Start expanded on desktop', 'pfh-widgets' ),
						'desc'     => __( 'On phones the badge always starts as the small tab, so it never sits over the content.', 'pfh-widgets' ),
						'default'  => true,
					],
					'mobile' => [
						'type'     => 'checkbox',
						'label'    => __( 'Phones', 'pfh-widgets' ),
						'cb_label' => __( 'Show the badge on small screens', 'pfh-widgets' ),
						'default'  => true,
					],
					'title' => [
						'type'    => 'text',
						'label'   => __( 'Panel title', 'pfh-widgets' ),
						'default' => 'Reviews & Zekerheden',
					],
					'points' => [
						'type'    => 'textarea',
						'rows'    => 5,
						'label'   => __( 'Trust points', 'pfh-widgets' ),
						'desc'    => __( 'One per line. Each gets a check mark.', 'pfh-widgets' ),
						'default' => "Echte ondernemers\nVeilig online\nGoede voorwaarden\nBetrouwbare info",
					],
					'mark_image' => [
						'type'  => 'media',
						'label' => __( 'WebwinkelKeur icon', 'pfh-widgets' ),
						'desc'  => __( 'Shown on the edge tab and next to the link at the bottom of the panel. A transparent PNG or SVG is what you want — a JPEG carries its background with it, which shows as a solid square against the panel. The plugin ships a transparent mark, used when this is empty.', 'pfh-widgets' ),
					],
					'mark_url' => [
						'type'    => 'url',
						'label'   => __( 'or icon URL', 'pfh-widgets' ),
						'default' => '',
						'desc'    => __( 'Used only when no image is chosen above.', 'pfh-widgets' ),
					],
					'mark_size' => [
						'type'    => 'number',
						'label'   => __( 'Icon size', 'pfh-widgets' ),
						'suffix'  => 'px',
						'min'     => 12,
						'max'     => 48,
						'default' => 20,
					],
					'link_label' => [
						'type'    => 'text',
						'label'   => __( 'Footer link label', 'pfh-widgets' ),
						'default' => 'WebwinkelKeur',
					],
					'link_url' => [
						'type'    => 'url',
						'label'   => __( 'Footer link', 'pfh-widgets' ),
						'default' => PFH_Widgets_Reviews::SHOP_URL,
						'desc'    => __( 'Leave empty to use the shop page for the webshop ID above.', 'pfh-widgets' ),
					],
					'fallback_rating' => [
						'type'    => 'text',
						'label'   => __( 'Fallback score', 'pfh-widgets' ),
						'default' => '9,7',
						'desc'    => __( 'Shown only if the API is unreachable, so the badge never renders empty.', 'pfh-widgets' ),
					],
					'fallback_count' => [
						'type'    => 'number',
						'label'   => __( 'Fallback review count', 'pfh-widgets' ),
						'min'     => 0,
						'max'     => 1000000,
						'default' => 396,
					],
				],
			],

			'style' => [
				'label'  => __( 'Style', 'pfh-widgets' ),
				'fields' => [
					'tab_bg' => [
						'type'    => 'color',
						'label'   => __( 'Tab background', 'pfh-widgets' ),
						'default' => '#22282A',
						'desc'    => __( 'Set this light if your icon is dark, or dark if it is light.', 'pfh-widgets' ),
					],
					'tab_ink' => [
						'type'    => 'color',
						'label'   => __( 'Tab score colour', 'pfh-widgets' ),
						'default' => '#FFFFFF',
					],
					'star_color' => [
						'type'    => 'color',
						'label'   => __( 'Stars', 'pfh-widgets' ),
						'default' => '#F5A623',
					],
					'check_color' => [
						'type'    => 'color',
						'label'   => __( 'Check marks', 'pfh-widgets' ),
						'default' => '#2FA84F',
					],
					'brand_color' => [
						'type'    => 'color',
						'label'   => __( 'WebwinkelKeur mark', 'pfh-widgets' ),
						'default' => '#E5007D',
					],
					'surface' => [
						'type'    => 'color',
						'label'   => __( 'Background', 'pfh-widgets' ),
						'default' => '#FFFFFF',
					],
					'ink' => [
						'type'    => 'color',
						'label'   => __( 'Text', 'pfh-widgets' ),
						'default' => '#1F2B2E',
					],
				],
			],
		];
	}

	/**
	 * Whether this request is an editor, not a page a visitor sees: the admin,
	 * the Bricks canvas, or another page builder's preview. A fixed badge
	 * there only gets in the way.
	 *
	 * @return bool
	 */
	private static function skip() {
		if ( is_admin() || PFH_Widgets_Helpers::is_builder_context() ) {
			return true;
		}

		foreach ( [ 'bricks', 'brickspreview', 'elementor-preview', 'vc_editable', 'et_fb', 'fl_builder' ] as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only context check.
			if ( isset( $_GET[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Front-end assets.
	 */
	public static function assets() {
		if ( ! self::get( 'enabled', false ) || self::skip() ) {
			return;
		}

		/*
		 * The base layer first: the markup is wrapped in .pfh-scope, whose
		 * tokens and resets live in pfh-base. This runs on every page,
		 * including pages carrying no PFH element at all, where nothing
		 * else would have enqueued it — so the banner came out with every
		 * --pfh-* variable undefined and the theme's font.
		 */
		PFH_Widgets_Assets::base();

		wp_enqueue_style( 'pfh-badge', PFH_WIDGETS_URL . 'assets/css/pfh-badge.css', [ 'pfh-base' ], PFH_WIDGETS_VERSION );
		wp_enqueue_script( 'pfh-badge', PFH_WIDGETS_URL . 'assets/js/pfh-badge.js', [], PFH_WIDGETS_VERSION, true );
	}

	/**
	 * The score and count, live where possible.
	 *
	 * @return array{rating:string, count:int, stars:float, live:bool}
	 */
	public static function data() {
		$summary = PFH_Widgets_Reviews::summary();

		$rating = $summary['rating'];
		$count  = $summary['count'];
		$live   = null !== $rating || null !== $count;

		if ( null === $rating ) {
			$raw    = (string) self::get( 'fallback_rating', '9,7' );
			$rating = (float) str_replace( ',', '.', $raw );
		}

		if ( null === $count ) {
			$count = (int) self::get( 'fallback_count', 0 );
		}

		return [
			'rating' => number_format_i18n( (float) $rating, 1 ),
			'count'  => (int) $count,
			'stars'  => PFH_Widgets_Reviews::stars( (float) $rating, 10, 5 ),
			'live'   => $live,
		];
	}

	/**
	 * Render the badge.
	 */
	public static function render() {
		// A fixed badge over the Bricks canvas is in the editor's way.
		if ( ! self::get( 'enabled', false ) || self::skip() ) {
			return;
		}

		/**
		 * Filter whether the sticky badge prints on this request.
		 *
		 * @param bool $render Default true.
		 */
		if ( ! apply_filters( 'pfh_badge_render', true ) ) {
			return;
		}

		$data   = self::data();
		$points = PFH_Widgets_Helpers::lines( self::get( 'points', '' ) );
		$side   = 'right' === self::get( 'side', 'left' ) ? 'right' : 'left';

		$url = trim( (string) self::get( 'link_url', '' ) );

		if ( '' === $url ) {
			$url = PFH_Widgets_Reviews::SHOP_URL;
		}

		$style = PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-bdg-star'    => PFH_Widgets_Helpers::color( self::get( 'star_color', '#F5A623' ), '#F5A623' ),
				'--pfh-bdg-check'   => PFH_Widgets_Helpers::color( self::get( 'check_color', '#2FA84F' ), '#2FA84F' ),
				'--pfh-bdg-brand'   => PFH_Widgets_Helpers::color( self::get( 'brand_color', '#E5007D' ), '#E5007D' ),
				'--pfh-bdg-surface' => PFH_Widgets_Helpers::color( self::get( 'surface', '#FFFFFF' ), '#FFFFFF' ),
				'--pfh-bdg-ink'     => PFH_Widgets_Helpers::color( self::get( 'ink', '#1F2B2E' ), '#1F2B2E' ),
				'--pfh-bdg-bottom'  => PFH_Widgets_Helpers::unit( self::get( 'offset', 180 ) ),
				'--pfh-bdg-tab-bg'  => PFH_Widgets_Helpers::color( self::get( 'tab_bg', '#22282A' ), '#22282A' ),
				'--pfh-bdg-tab-ink' => PFH_Widgets_Helpers::color( self::get( 'tab_ink', '#FFFFFF' ), '#FFFFFF' ),
				'--pfh-bdg-mark'    => PFH_Widgets_Helpers::unit( self::get( 'mark_size', 20 ) ),
			]
		);

		$classes = [ 'pfh-scope', 'pfh-bdg', 'pfh-bdg--' . $side ];

		if ( ! self::get( 'mobile', true ) ) {
			$classes[] = 'pfh-bdg--desktop';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			style="<?php echo esc_attr( $style ); ?>"
			data-pfh-badge
			data-open-default="<?php echo self::get( 'open_default', true ) ? '1' : '0'; ?>">

			<button type="button" class="pfh-bdg__tab" data-pfh-badge-toggle
				aria-expanded="false"
				aria-controls="pfh-badge-panel">
				<span class="pfh-bdg__tab-mark" aria-hidden="true"><?php self::mark(); ?></span>
				<span class="pfh-bdg__tab-score"><?php echo esc_html( $data['rating'] ); ?></span>
				<span class="pfh-bdg__sr">
					<?php
					printf(
						/* translators: %s: shop rating. */
						esc_html__( 'Reviews, score %s. Paneel openen.', 'pfh-widgets' ),
						esc_html( $data['rating'] )
					);
					?>
				</span>
			</button>

			<div class="pfh-bdg__panel" id="pfh-badge-panel" hidden>
				<div class="pfh-bdg__head">
					<p class="pfh-bdg__title"><?php echo esc_html( self::get( 'title', '' ) ); ?></p>
					<button type="button" class="pfh-bdg__close" data-pfh-badge-toggle aria-label="<?php esc_attr_e( 'Close', 'pfh-widgets' ); ?>">
						<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false">
							<path d="M3 3l10 10M13 3L3 13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
						</svg>
					</button>
				</div>

				<div class="pfh-bdg__score">
					<span class="pfh-bdg__stars" role="img"
						aria-label="<?php
						printf(
							/* translators: 1: star rating, 2: maximum. */
							esc_attr__( '%1$s van %2$s sterren', 'pfh-widgets' ),
							esc_attr( number_format_i18n( $data['stars'], 1 ) ),
							'5'
						);
						?>">
						<?php self::stars( $data['stars'] ); ?>
					</span>
					<span class="pfh-bdg__value"><?php echo esc_html( $data['rating'] ); ?></span>
					<span class="pfh-bdg__count">(<?php echo esc_html( number_format_i18n( $data['count'] ) ); ?>)</span>
				</div>

				<?php if ( $points ) : ?>
					<ul class="pfh-bdg__points">
						<?php foreach ( $points as $point ) : ?>
							<li class="pfh-bdg__point">
								<span class="pfh-bdg__check" aria-hidden="true">
									<svg viewBox="0 0 18 18" width="16" height="16" focusable="false">
										<circle cx="9" cy="9" r="9" fill="currentColor"/>
										<path d="M5 9.2l2.6 2.6L13 6.4" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</span>
								<span><?php echo esc_html( $point ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<a class="pfh-bdg__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow">
					<span class="pfh-bdg__link-mark" aria-hidden="true"><?php self::mark(); ?></span>
					<span class="pfh-bdg__link-label"><?php echo esc_html( self::get( 'link_label', 'WebwinkelKeur' ) ); ?></span>
					<span class="pfh-bdg__link-go" aria-hidden="true">
						<svg viewBox="0 0 16 16" width="12" height="12" focusable="false">
							<path d="M5.5 3l5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Where the mark comes from.
	 *
	 * An uploaded image wins, then a typed URL, then the transparent PNG that
	 * ships with the plugin. The inline SVG below is only reached if that file
	 * is missing.
	 *
	 * @return string Image URL, or an empty string to draw the SVG.
	 */
	public static function mark_src() {
		$id = (int) self::get( 'mark_image', 0 );

		if ( $id > 0 ) {
			$src = wp_get_attachment_image_url( $id, 'medium' );

			if ( $src ) {
				return $src;
			}
		}

		$url = trim( (string) self::get( 'mark_url', '' ) );

		if ( '' !== $url ) {
			return $url;
		}

		return PFH_Widgets_Assets::img( 'pfh-webwinkelkeur.png' );
	}

	/**
	 * The WebwinkelKeur mark.
	 */
	private static function mark() {
		$src = self::mark_src();

		if ( '' !== $src ) {
			printf(
				'<img class="pfh-bdg__mark-img" src="%s" alt="" aria-hidden="true" loading="lazy" decoding="async" />',
				esc_url( $src )
			);

			return;
		}
		?>
		<svg viewBox="0 0 24 24" width="20" height="20" focusable="false" aria-hidden="true">
			<circle cx="12" cy="12" r="10.4" fill="none" stroke="currentColor" stroke-width="1.8"/>
			<path d="M7.2 12.4l3.1 3.1 6.4-6.9" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
		<?php
	}

	/**
	 * Five stars, filled to the score.
	 *
	 * The fill is a clipped overlay rather than per-star icons, so a half star
	 * lands on the exact fraction instead of the nearest whole one.
	 *
	 * @param float $stars Score out of five.
	 */
	private static function stars( $stars ) {
		$pct = max( 0, min( 100, ( (float) $stars / 5 ) * 100 ) );
		?>
		<span class="pfh-bdg__stars-track" aria-hidden="true">
			<span class="pfh-bdg__stars-row"><?php self::star_row(); ?></span>
			<span class="pfh-bdg__stars-fill" style="width:<?php echo esc_attr( round( $pct, 2 ) ); ?>%">
				<span class="pfh-bdg__stars-row"><?php self::star_row(); ?></span>
			</span>
		</span>
		<?php
	}

	/**
	 * Live connection check on the settings screen.
	 *
	 * Calls the summary endpoint through the normal cached path, so this says
	 * what the front end will actually show rather than what a fresh request
	 * might.
	 */
	public static function render_status() {
		$creds = PFH_Widgets_Reviews::credentials();

		if ( '' === $creds['id'] || '' === $creds['code'] ) {
			echo '<div class="pfh-settings__note pfh-settings__note--warn"><p>'
				. esc_html__( 'No credentials yet. The review slider, the inline rating and the badge all fall back to whatever is typed into them until a webshop ID and API key are set.', 'pfh-widgets' )
				. '</p></div>';

			return;
		}

		$summary = PFH_Widgets_Reviews::summary();

		if ( null === $summary['rating'] && null === $summary['count'] ) {
			echo '<div class="pfh-settings__note pfh-settings__note--bad"><p><strong>'
				. esc_html__( 'WebwinkelKeur did not answer.', 'pfh-widgets' ) . '</strong> '
				. esc_html__( 'Check the webshop ID and API key. A failed call is cached for 15 minutes, so give it that long after correcting them — or save this page again, which clears the cache.', 'pfh-widgets' )
				. '</p></div>';

			return;
		}

		echo '<div class="pfh-settings__note pfh-settings__note--good"><p>'
			. esc_html(
				sprintf(
					/* translators: 1: rating, 2: review count. */
					__( 'Connected. Reporting %1$s from %2$s reviews, cached for 12 hours and refreshed daily by cron.', 'pfh-widgets' ),
					number_format_i18n( (float) $summary['rating'], 1 ),
					number_format_i18n( (int) $summary['count'] )
				)
			)
			. '</p></div>';
	}

	/**
	 * Five star glyphs in a row.
	 */
	private static function star_row() {
		for ( $i = 0; $i < 5; $i++ ) {
			?>
			<svg viewBox="0 0 20 20" width="14" height="14" focusable="false" aria-hidden="true">
				<path d="M10 1.6l2.47 5.16 5.53.74-4.05 3.9 1.02 5.6L10 14.3l-4.97 2.7 1.02-5.6-4.05-3.9 5.53-.74z" fill="currentColor"/>
			</svg>
			<?php
		}
	}
}
