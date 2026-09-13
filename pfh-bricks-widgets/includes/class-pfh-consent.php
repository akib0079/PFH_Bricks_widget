<?php
/**
 * Cookie consent.
 *
 * Replaces Complianz. A banner on its own is decoration — what makes a consent
 * layer lawful is that the tags genuinely do not run before consent, and that
 * the choice can be shown later. So this does three things:
 *
 *   1. Blocks third-party scripts and iframes server-side, by rewriting them to
 *      type="text/plain" before they reach the browser.
 *   2. Publishes Google Consent Mode v2 defaults before any Google tag loads,
 *      because Google's own tags are governed by that rather than by blocking.
 *   3. Records what was consented to, so the choice can be evidenced.
 *
 * Everything is cache-safe: the markup is identical for every visitor and the
 * state lives in a cookie read by JavaScript, so a full-page cache in front of
 * WordPress changes nothing.
 *
 * What this does NOT replace is Complianz's generated legal documents. The
 * cookie and privacy policy pages stay where they are and still need an owner.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Consent extends PFH_Settings_Module {

	const OPTION = 'pfh_consent';

	/**
	 * Cookie holding the visitor's choice.
	 */
	const COOKIE = 'pfh_consent';

	/**
	 * Bumping this asks every visitor again. Exposed as a setting.
	 */
	const VERSION = 1;

	/**
	 * Option tracking the consent-log table's schema version.
	 */
	const TABLE_VERSION = 'pfh_consent_table';

	/**
	 * How much of an inline script is scanned for tracking patterns.
	 */
	const SCAN_LIMIT = 20000;

	/**
	 * Categories that can be switched off. Functional is always on.
	 *
	 * @var string[]
	 */
	private static $optional = [ 'statistics', 'marketing' ];

	/**
	 * Buffers we opened, so a nested start/stop cannot unbalance ob_*.
	 *
	 * @var int
	 */
	private static $depth = 0;

	public static function init() {
		PFH_Widgets_Settings::register( 'consent', __( 'Cookie consent', 'pfh-widgets' ), __CLASS__ );

		add_action( 'wp_ajax_pfh_consent', [ __CLASS__, 'ajax_log' ] );
		add_action( 'wp_ajax_nopriv_pfh_consent', [ __CLASS__, 'ajax_log' ] );
		add_action( 'admin_init', [ __CLASS__, 'maybe_install' ] );

		add_shortcode( 'pfh_cookie_settings', [ __CLASS__, 'shortcode' ] );

		if ( ! self::get( 'enabled', true ) ) {
			return;
		}

		// Consent Mode has to be defined before any Google tag, so it goes out
		// at the very top of <head>.
		add_action( 'wp_head', [ __CLASS__, 'head_snippet' ], 0 );

		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_action( 'wp_footer', [ __CLASS__, 'render' ], 20 );

		if ( self::get( 'block_scripts', true ) ) {
			add_filter( 'script_loader_tag', [ __CLASS__, 'filter_handle' ], 20, 3 );

			foreach ( [ 'wp_head', 'wp_body_open', 'wp_footer' ] as $hook ) {
				add_action( $hook, [ __CLASS__, 'buffer_start' ], -9999 );
				add_action( $hook, [ __CLASS__, 'buffer_end' ], 9999 );
			}
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
			'general' => [
				'label'  => __( 'Banner', 'pfh-widgets' ),
				'fields' => [
					'enabled' => [
						'type'     => 'checkbox',
						'label'    => __( 'Cookie consent', 'pfh-widgets' ),
						'cb_label' => __( 'Show the consent banner and block tags until a choice is made', 'pfh-widgets' ),
						'default'  => true,
					],
					'layout' => [
						'type'    => 'select',
						'label'   => __( 'Placement', 'pfh-widgets' ),
						'default' => 'bar',
						'choices' => [
							'bar'   => __( 'Bottom bar, full width (no overlay)', 'pfh-widgets' ),
							'card'  => __( 'Bottom left card (no overlay)', 'pfh-widgets' ),
							'cardr' => __( 'Bottom right card (no overlay)', 'pfh-widgets' ),
						],
						'desc'    => __( 'None of these dim the page or block interaction — the visitor can keep browsing while the banner is up.', 'pfh-widgets' ),
					],
					'title' => [
						'type'    => 'text',
						'label'   => __( 'Title', 'pfh-widgets' ),
						'default' => 'Wij gebruiken cookies',
					],
					'body' => [
						'type'    => 'textarea',
						'rows'    => 3,
						'label'   => __( 'Text', 'pfh-widgets' ),
						'default' => 'Wij gebruiken cookies om onze website goed te laten functioneren. Met jouw toestemming helpen cookies ons om de website te verbeteren en content beter op jouw wensen af te stemmen.',
					],
					'accept_label' => [
						'type'    => 'text',
						'label'   => __( 'Accept button', 'pfh-widgets' ),
						'default' => 'Accepteren',
					],
					'reject_label' => [
						'type'    => 'text',
						'label'   => __( 'Reject button', 'pfh-widgets' ),
						'default' => 'Weigeren',
					],
					'prefs_label' => [
						'type'    => 'text',
						'label'   => __( 'Preferences button', 'pfh-widgets' ),
						'default' => 'Bekijk voorkeuren',
					],
					'expand_details' => [
						'type'     => 'checkbox',
						'label'    => __( 'Category descriptions', 'pfh-widgets' ),
						'cb_label' => __( 'Show them expanded instead of behind a disclosure', 'pfh-widgets' ),
						'desc'     => __( 'Collapsed keeps the panel about three lines tall. Expanded shows every explanation at once, which is thorough but makes the panel dominate the page.', 'pfh-widgets' ),
						'default'  => false,
					],
					'show_reject' => [
						'type'     => 'checkbox',
						'label'    => __( 'Reject button', 'pfh-widgets' ),
						'cb_label' => __( 'Show a reject button next to accept', 'pfh-widgets' ),
						'desc'     => __( 'Keep this on. Under the AVG and the ACM guidance, refusing has to be as easy as accepting — an accept button with only a preferences link behind it is the setup regulators have been acting against.', 'pfh-widgets' ),
						'default'  => true,
					],
				],
			],

			'links' => [
				'label'  => __( 'Policy links', 'pfh-widgets' ),
				'desc'   => __( 'Shown under the buttons. Leave a field empty to hide that link.', 'pfh-widgets' ),
				'fields' => [
					'cookie_url' => [
						'type'    => 'url',
						'label'   => __( 'Cookie policy', 'pfh-widgets' ),
						'default' => '/cookiebeleid-eu/',
					],
					'cookie_label' => [
						'type'    => 'text',
						'label'   => __( 'Cookie policy label', 'pfh-widgets' ),
						'default' => 'Cookiebeleid',
					],
					'privacy_url' => [
						'type'    => 'url',
						'label'   => __( 'Privacy policy', 'pfh-widgets' ),
						'default' => '/privacy-policy/',
					],
					'privacy_label' => [
						'type'    => 'text',
						'label'   => __( 'Privacy policy label', 'pfh-widgets' ),
						'default' => 'Privacy Policy',
					],
					'contact_url' => [
						'type'    => 'url',
						'label'   => __( 'Contact', 'pfh-widgets' ),
						'default' => '/contact/',
					],
					'contact_label' => [
						'type'    => 'text',
						'label'   => __( 'Contact label', 'pfh-widgets' ),
						'default' => 'Contact',
					],
				],
			],

			'categories' => [
				'label'  => __( 'Categories', 'pfh-widgets' ),
				'desc'   => __( 'Functional cookies are always on and cannot be refused — they are what makes the cart and checkout work.', 'pfh-widgets' ),
				'fields' => [
					'functional_label' => [
						'type'    => 'text',
						'label'   => __( 'Functional label', 'pfh-widgets' ),
						'default' => 'Functioneel',
					],
					'functional_desc' => [
						'type'    => 'textarea',
						'rows'    => 2,
						'label'   => __( 'Functional description', 'pfh-widgets' ),
						'default' => 'Noodzakelijk om de winkelwagen, het afrekenen en je accountgegevens te laten werken. Deze cookies kunnen niet worden uitgeschakeld.',
					],
					'statistics_label' => [
						'type'    => 'text',
						'label'   => __( 'Statistics label', 'pfh-widgets' ),
						'default' => 'Statistieken',
					],
					'statistics_desc' => [
						'type'    => 'textarea',
						'rows'    => 2,
						'label'   => __( 'Statistics description', 'pfh-widgets' ),
						'default' => 'Helpen ons te begrijpen hoe bezoekers de website gebruiken, zodat we de winkel kunnen verbeteren.',
					],
					'marketing_label' => [
						'type'    => 'text',
						'label'   => __( 'Marketing label', 'pfh-widgets' ),
						'default' => 'Marketing',
					],
					'marketing_desc' => [
						'type'    => 'textarea',
						'rows'    => 2,
						'label'   => __( 'Marketing description', 'pfh-widgets' ),
						'default' => 'Worden gebruikt om advertenties relevanter te maken en om het effect van onze campagnes te meten.',
					],
				],
			],

			'blocking' => [
				'label'  => __( 'Tag blocking', 'pfh-widgets' ),
				'desc'   => __( 'This is the part that makes the banner mean something: matching scripts and iframes are rewritten so the browser will not run them until the visitor agrees.', 'pfh-widgets' ),
				'fields' => [
					'block_scripts' => [
						'type'     => 'checkbox',
						'label'    => __( 'Block tags', 'pfh-widgets' ),
						'cb_label' => __( 'Hold scripts and iframes until consent is given', 'pfh-widgets' ),
						'default'  => true,
					],
					'consent_mode' => [
						'type'     => 'checkbox',
						'label'    => __( 'Google Consent Mode v2', 'pfh-widgets' ),
						'cb_label' => __( 'Publish consent signals for Google tags', 'pfh-widgets' ),
						'desc'     => __( 'Google tags are governed by these signals rather than by blocking, which is why googletagmanager is not in the list below by default. Turning this off and blocking Google outright loses conversion modelling in Google Ads.', 'pfh-widgets' ),
						'default'  => true,
					],
					'block_google' => [
						'type'     => 'checkbox',
						'label'    => __( 'Block Google tags too', 'pfh-widgets' ),
						'cb_label' => __( 'Also hold googletagmanager and google-analytics until consent', 'pfh-widgets' ),
						'desc'     => __( 'Stricter, and it breaks Consent Mode. Only switch this on if legal counsel asks for it.', 'pfh-widgets' ),
						'default'  => false,
					],
					'statistics_hosts' => [
						'type'    => 'textarea',
						'rows'    => 4,
						'label'   => __( 'Statistics — hosts and patterns', 'pfh-widgets' ),
						'desc'    => __( 'One per line. Matched against a script or iframe src, and against inline script contents.', 'pfh-widgets' ),
						'default' => "hotjar.com\nclarity.ms\nmatomo\nstatcounter.com",
					],
					'marketing_hosts' => [
						'type'    => 'textarea',
						'rows'    => 8,
						'label'   => __( 'Marketing — hosts and patterns', 'pfh-widgets' ),
						'desc'    => __( 'Pre-filled with the tags running on this store: Klaviyo, Meta, Triple Whale, PixelYourSite.', 'pfh-widgets' ),
						'default' => "connect.facebook.net\nfacebook.com/tr\nstatic.klaviyo.com\nstatic-tracking.klaviyo.com\nklaviyo.com/onsite\ntriplewhale.com\ntriplepixel\ndoubleclick.net\ngoogleadservices.com\nsnap.licdn.com\nanalytics.tiktok.com\nct.pinterest.com\nfbq(\n_learnq\nPixelYourSite\npysOptions",
					],
					'statistics_handles' => [
						'type'    => 'textarea',
						'rows'    => 2,
						'label'   => __( 'Statistics — script handles', 'pfh-widgets' ),
						'desc'    => __( 'Registered WordPress handles, one per line.', 'pfh-widgets' ),
						'default' => '',
					],
					'marketing_handles' => [
						'type'    => 'textarea',
						'rows'    => 4,
						'label'   => __( 'Marketing — script handles', 'pfh-widgets' ),
						'default' => "klaviyo\nklaviyo-js\npys-js\nfacebook-for-woocommerce-pixel\ntriple-whale",
					],
					'block_iframes' => [
						'type'     => 'checkbox',
						'label'    => __( 'Embeds', 'pfh-widgets' ),
						'cb_label' => __( 'Also hold matching iframes (YouTube, Maps)', 'pfh-widgets' ),
						'default'  => true,
					],
					'iframe_hosts' => [
						'type'    => 'textarea',
						'rows'    => 3,
						'label'   => __( 'Marketing — iframe hosts', 'pfh-widgets' ),
						'default' => "youtube.com\nyoutu.be\nvimeo.com\ngoogle.com/maps",
					],
					'placeholder_text' => [
						'type'    => 'text',
						'label'   => __( 'Blocked embed message', 'pfh-widgets' ),
						'default' => 'Deze inhoud wordt geblokkeerd tot je marketingcookies accepteert.',
					],
					'placeholder_button' => [
						'type'    => 'text',
						'label'   => __( 'Blocked embed button', 'pfh-widgets' ),
						'default' => 'Accepteer en toon',
					],
				],
			],

			'record' => [
				'label'  => __( 'Consent record', 'pfh-widgets' ),
				'fields' => [
					'log_consent' => [
						'type'     => 'checkbox',
						'label'    => __( 'Keep a record', 'pfh-widgets' ),
						'cb_label' => __( 'Log each choice so consent can be evidenced', 'pfh-widgets' ),
						'desc'     => __( 'Stores a timestamp, the categories chosen, the policy version and a truncated IP address. The last octet is dropped before storing, so the record is not itself personal data under the usual reading.', 'pfh-widgets' ),
						'default'  => true,
					],
					'retention' => [
						'type'    => 'number',
						'label'   => __( 'Keep records for', 'pfh-widgets' ),
						'suffix'  => __( 'days', 'pfh-widgets' ),
						'min'     => 30,
						'max'     => 3650,
						'default' => 365,
					],
					'policy_version' => [
						'type'    => 'number',
						'label'   => __( 'Policy version', 'pfh-widgets' ),
						'min'     => 1,
						'max'     => 999,
						'default' => 1,
						'desc'    => __( 'Raise this when the cookie policy changes materially — every visitor is asked again.', 'pfh-widgets' ),
					],
					'lifetime' => [
						'type'    => 'number',
						'label'   => __( 'Ask again after', 'pfh-widgets' ),
						'suffix'  => __( 'days', 'pfh-widgets' ),
						'min'     => 1,
						'max'     => 365,
						'default' => 182,
						'desc'    => __( 'Six months is the common reading of the Dutch guidance.', 'pfh-widgets' ),
					],
				],
			],

			'style' => [
				'label'  => __( 'Style', 'pfh-widgets' ),
				'fields' => [
					'accent' => [
						'type'    => 'color',
						'label'   => __( 'Button colour', 'pfh-widgets' ),
						'default' => '#3F7E7C',
					],
					'accent_text' => [
						'type'    => 'color',
						'label'   => __( 'Button text', 'pfh-widgets' ),
						'default' => '#FFFFFF',
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
					'radius' => [
						'type'    => 'number',
						'label'   => __( 'Corner radius', 'pfh-widgets' ),
						'suffix'  => 'px',
						'min'     => 0,
						'max'     => 40,
						'default' => 12,
					],
				],
			],
		];
	}

	/* ---------------------------------------------------------------------
	 * Front end
	 * ------------------------------------------------------------------ */

	/**
	 * Requests this layer must keep its hands off.
	 *
	 * Page builders render the front end inside an iframe to edit it. Holding
	 * their scripts, or dropping a fixed cookie bar over the canvas, breaks
	 * the editor — and nobody is being tracked while an admin edits a page.
	 *
	 * Checked at hook time rather than at init, because Bricks is not
	 * necessarily loaded when this module boots.
	 *
	 * @return bool
	 */
	public static function skip() {
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
	 * Register and enqueue the banner's own assets.
	 */
	public static function assets() {
		if ( self::skip() ) {
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

		wp_enqueue_style( 'pfh-consent', PFH_WIDGETS_URL . 'assets/css/pfh-consent.css', [ 'pfh-base' ], PFH_WIDGETS_VERSION );
		wp_enqueue_script( 'pfh-consent', PFH_WIDGETS_URL . 'assets/js/pfh-consent.js', [], PFH_WIDGETS_VERSION, true );

		wp_localize_script(
			'pfh-consent',
			'pfhConsent',
			[
				'cookie'      => self::COOKIE,
				'version'     => (int) self::get( 'policy_version', 1 ),
				'lifetime'    => (int) self::get( 'lifetime', 182 ),
				'consentMode' => (bool) self::get( 'consent_mode', true ),
				'log'         => (bool) self::get( 'log_consent', true ),
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'pfh_consent' ),
				'categories'  => self::$optional,
			]
		);
	}

	/**
	 * The inline snippet that has to beat every tag on the page.
	 *
	 * Defaults are always denied — the page may be served from a full-page
	 * cache, so the server cannot know this visitor. The update immediately
	 * after reads the cookie in the browser, which the cache cannot flatten.
	 */
	public static function head_snippet() {
		if ( self::skip() || ! self::get( 'consent_mode', true ) ) {
			return;
		}

		$cookie = wp_json_encode( self::COOKIE );

		?>
<script id="pfh-consent-mode">
window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',personalization_storage:'denied',functionality_storage:'granted',security_storage:'granted',wait_for_update:500});
(function(){try{var m=document.cookie.match(new RegExp('(?:^|; )'+<?php echo $cookie; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_json_encode output. ?>+'=([^;]*)'));if(!m){return;}var c=JSON.parse(decodeURIComponent(m[1]));if(!c||c.v!==<?php echo (int) self::get( 'policy_version', 1 ); ?>){return;}var s=c.c&&c.c.indexOf('statistics')>-1?'granted':'denied';var k=c.c&&c.c.indexOf('marketing')>-1?'granted':'denied';gtag('consent','update',{ad_storage:k,ad_user_data:k,ad_personalization:k,analytics_storage:s,personalization_storage:k});window.pfhConsentState=c;}catch(e){}})();
</script>
		<?php
	}

	/**
	 * The banner and the preferences panel.
	 *
	 * Rendered hidden on every request; the script decides whether to show it.
	 * That keeps one cached copy of the page correct for everybody.
	 */
	public static function render() {
		if ( self::skip() ) {
			return;
		}

		/**
		 * Filter whether the consent banner prints on this request.
		 *
		 * @param bool $render Default true.
		 */
		if ( ! apply_filters( 'pfh_consent_render', true ) ) {
			return;
		}

		$layout = (string) self::get( 'layout', 'bar' );
		$style  = PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-ck-accent'      => PFH_Widgets_Helpers::color( self::get( 'accent', '#3F7E7C' ), '#3F7E7C' ),
				'--pfh-ck-accent-text' => PFH_Widgets_Helpers::color( self::get( 'accent_text', '#FFFFFF' ), '#FFFFFF' ),
				'--pfh-ck-surface'     => PFH_Widgets_Helpers::color( self::get( 'surface', '#FFFFFF' ), '#FFFFFF' ),
				'--pfh-ck-ink'         => PFH_Widgets_Helpers::color( self::get( 'ink', '#1F2B2E' ), '#1F2B2E' ),
				'--pfh-ck-radius'      => PFH_Widgets_Helpers::unit( self::get( 'radius', 12 ) ),
			]
		);

		$links = self::links();
		?>
		<div class="pfh-scope pfh-ck pfh-ck--<?php echo esc_attr( $layout ); ?>"
			id="pfh-consent"
			style="<?php echo esc_attr( $style ); ?>"
			role="region"
			aria-label="<?php echo esc_attr( self::get( 'title', 'Cookies' ) ); ?>"
			data-pfh-consent-root
			hidden>

			<div class="pfh-ck__inner">
				<div class="pfh-ck__banner" data-pfh-ck-view="banner">
					<div class="pfh-ck__copy">
						<p class="pfh-ck__title"><?php echo esc_html( self::get( 'title', '' ) ); ?></p>
						<p class="pfh-ck__body"><?php echo esc_html( self::get( 'body', '' ) ); ?></p>
					</div>

					<div class="pfh-ck__actions">
						<?php if ( self::get( 'show_reject', true ) ) : ?>
							<button type="button" class="pfh-ck__btn pfh-ck__btn--ghost" data-pfh-ck="reject">
								<?php echo esc_html( self::get( 'reject_label', 'Weigeren' ) ); ?>
							</button>
						<?php endif; ?>

						<button type="button" class="pfh-ck__btn pfh-ck__btn--ghost" data-pfh-ck="prefs">
							<?php echo esc_html( self::get( 'prefs_label', 'Bekijk voorkeuren' ) ); ?>
						</button>

						<button type="button" class="pfh-ck__btn pfh-ck__btn--solid" data-pfh-ck="accept">
							<?php echo esc_html( self::get( 'accept_label', 'Accepteren' ) ); ?>
						</button>
					</div>

					<?php if ( $links ) : ?>
						<p class="pfh-ck__links">
							<?php foreach ( $links as $link ) : ?>
								<a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
							<?php endforeach; ?>
						</p>
					<?php endif; ?>
				</div>

				<div class="pfh-ck__prefs" data-pfh-ck-view="prefs" hidden>
					<p class="pfh-ck__title"><?php echo esc_html( self::get( 'prefs_label', 'Voorkeuren' ) ); ?></p>

					<ul class="pfh-ck__list">
						<?php
						self::pref_row( 'functional', self::get( 'functional_label', 'Functioneel' ), self::get( 'functional_desc', '' ), false );

						foreach ( self::$optional as $cat ) {
							self::pref_row( $cat, self::get( $cat . '_label', $cat ), self::get( $cat . '_desc', '' ), true );
						}
						?>
					</ul>

					<div class="pfh-ck__actions">
						<button type="button" class="pfh-ck__btn pfh-ck__btn--ghost" data-pfh-ck="back">
							<?php esc_html_e( 'Terug', 'pfh-widgets' ); ?>
						</button>
						<button type="button" class="pfh-ck__btn pfh-ck__btn--solid" data-pfh-ck="save">
							<?php esc_html_e( 'Voorkeuren opslaan', 'pfh-widgets' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * One category row.
	 *
	 * The description sits behind a disclosure by default. Three labelled
	 * switches is a panel someone reads; three paragraphs is a wall they
	 * dismiss, and the text is still one click away for anyone who wants it.
	 *
	 * @param string $key    Category slug.
	 * @param string $label  Visible label.
	 * @param string $desc   Explanation.
	 * @param bool   $toggle Whether the visitor can switch it off.
	 */
	private static function pref_row( $key, $label, $desc, $toggle ) {
		$id   = 'pfh-ck-desc-' . $key;
		$open = (bool) self::get( 'expand_details', false );
		?>
		<li class="pfh-ck__row">
			<div class="pfh-ck__row-head">
				<button type="button" class="pfh-ck__row-toggle" data-pfh-ck="detail"
					aria-expanded="<?php echo $open ? 'true' : 'false'; ?>"
					aria-controls="<?php echo esc_attr( $id ); ?>">
					<span class="pfh-ck__chev" aria-hidden="true">
						<svg viewBox="0 0 12 12" width="10" height="10" focusable="false">
							<path d="M4.5 2L8 6l-3.5 4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<span class="pfh-ck__row-label"><?php echo esc_html( $label ); ?></span>
				</button>

				<?php if ( $toggle ) : ?>
					<span class="pfh-ck__switch">
						<input type="checkbox"
							id="pfh-ck-<?php echo esc_attr( $key ); ?>"
							data-pfh-ck-cat="<?php echo esc_attr( $key ); ?>"
							aria-label="<?php echo esc_attr( $label ); ?>">
						<span class="pfh-ck__switch-track" aria-hidden="true"></span>
					</span>
				<?php else : ?>
					<span class="pfh-ck__always"><?php esc_html_e( 'Altijd aan', 'pfh-widgets' ); ?></span>
				<?php endif; ?>
			</div>

			<p class="pfh-ck__row-desc" id="<?php echo esc_attr( $id ); ?>"<?php echo $open ? '' : ' hidden'; ?>>
				<?php echo esc_html( $desc ); ?>
			</p>
		</li>
		<?php
	}

	/**
	 * Policy links, skipping the empty ones.
	 *
	 * @return array<int, array{url:string, label:string}>
	 */
	private static function links() {
		$out = [];

		foreach ( [ 'cookie', 'privacy', 'contact' ] as $key ) {
			$url = trim( (string) self::get( $key . '_url', '' ) );

			if ( '' === $url ) {
				continue;
			}

			// Relative paths are stored as typed and resolved against the site.
			if ( 0 === strpos( $url, '/' ) ) {
				$url = home_url( $url );
			}

			$out[] = [
				'url'   => $url,
				'label' => (string) self::get( $key . '_label', $key ),
			];
		}

		return $out;
	}

	/**
	 * A link or button that reopens the preferences panel.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'label' => __( 'Cookie-instellingen', 'pfh-widgets' ),
				'class' => '',
			],
			(array) $atts,
			'pfh_cookie_settings'
		);

		return sprintf(
			'<button type="button" class="pfh-ck__reopen %1$s" data-pfh-ck="reopen">%2$s</button>',
			esc_attr( $atts['class'] ),
			esc_html( $atts['label'] )
		);
	}

	/* ---------------------------------------------------------------------
	 * Blocking
	 * ------------------------------------------------------------------ */

	/**
	 * Hold a registered script until its category is consented to.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Handle.
	 * @param string $src    Source URL.
	 * @return string
	 */
	public static function filter_handle( $tag, $handle, $src ) {
		if ( self::skip() ) {
			return $tag;
		}

		$category = '';

		foreach ( self::$optional as $cat ) {
			$handles = PFH_Widgets_Helpers::lines( self::get( $cat . '_handles', '' ) );

			if ( in_array( $handle, $handles, true ) ) {
				$category = $cat;
				break;
			}
		}

		if ( '' === $category ) {
			// Fall back to the host list, so a handle we were not told about
			// still gets caught by its URL.
			$category = self::category_for( (string) $src );
		}

		if ( '' === $category ) {
			return $tag;
		}

		return self::hold( $tag, $category );
	}

	/**
	 * Start capturing output so inline and third-party tags can be rewritten.
	 */
	public static function buffer_start() {
		if ( self::skip() ) {
			return;
		}

		self::$depth++;
		ob_start();
	}

	/**
	 * Rewrite what was captured and print it.
	 */
	public static function buffer_end() {
		if ( self::skip() || self::$depth < 1 ) {
			return;
		}

		self::$depth--;

		$html = ob_get_clean();

		if ( ! is_string( $html ) || '' === $html ) {
			echo '';

			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput -- rewriting markup that was already escaped by its author.
		echo self::rewrite( $html );
	}

	/**
	 * Rewrite every script and iframe that needs consent.
	 *
	 * Deliberately a linear scan rather than a regex over whole script bodies.
	 * `<script\b[^>]*>(.*?)</script>` looks tidy, but a single inline script
	 * larger than pcre.backtrack_limit makes preg_replace_callback return null
	 * — and the Bricks builder prints its entire element payload as one such
	 * script. Returning that null as a string silently emptied the whole
	 * buffer and took the page with it.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function rewrite( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return is_string( $html ) ? $html : '';
		}

		$out    = '';
		$offset = 0;
		$length = strlen( $html );

		while ( $offset < $length ) {
			$start = stripos( $html, '<script', $offset );

			if ( false === $start ) {
				$out .= substr( $html, $offset );
				break;
			}

			$out .= substr( $html, $offset, $start - $offset );

			// "<scripting" and friends are not script tags.
			$next = (string) substr( $html, $start + 7, 1 );

			if ( '' !== $next && false === strpos( " \t\r\n>/", $next ) ) {
				$out   .= substr( $html, $start, 7 );
				$offset = $start + 7;
				continue;
			}

			$open_end = strpos( $html, '>', $start );

			if ( false === $open_end ) {
				$out .= substr( $html, $start );
				break;
			}

			$close = stripos( $html, '</script', $open_end );

			if ( false === $close ) {
				$out .= substr( $html, $start );
				break;
			}

			$close_end = strpos( $html, '>', $close );
			$close_end = false === $close_end ? $length - 1 : $close_end;

			$open  = substr( $html, $start, $open_end - $start + 1 );
			$attrs = substr( $html, $start + 7, $open_end - $start - 7 );
			$body  = substr( $html, $open_end + 1, $close - $open_end - 1 );
			$tail  = substr( $html, $close, $close_end - $close + 1 );

			$out   .= self::filter_script( $open, $attrs, $body, $tail );
			$offset = $close_end + 1;
		}

		if ( self::get( 'block_iframes', true ) ) {
			// Bounded pattern: the opening tag only, never a body.
			$replaced = preg_replace_callback( '#<iframe\b[^>]*>#i', [ __CLASS__, 'iframe_callback' ], $out );

			// Never hand back null. Losing a block is a bug; losing the page
			// is an outage.
			if ( is_string( $replaced ) ) {
				$out = $replaced;
			}
		}

		return $out;
	}

	/**
	 * Decide what to do with one script tag.
	 *
	 * @param string $open  The opening tag, angle brackets included.
	 * @param string $attrs Its attributes.
	 * @param string $body  Everything between the tags.
	 * @param string $tail  The closing tag.
	 * @return string
	 */
	private static function filter_script( $open, $attrs, $body, $tail ) {
		$whole = $open . $body . $tail;

		// Already handled, deliberately exempt, or not executable JavaScript.
		if ( false !== stripos( $attrs, 'data-pfh-consent' ) || false !== stripos( $attrs, 'data-pfh-keep' ) ) {
			return $whole;
		}

		if ( preg_match( '#type\s*=\s*["\']?(application/ld\+json|application/json|text/template|text/x-template|text/plain)#i', $attrs ) ) {
			return $whole;
		}

		// Our own consent layer must never block itself.
		if ( false !== strpos( $attrs, 'pfh-consent' ) ) {
			return $whole;
		}

		$category = '';

		if ( preg_match( '#\bsrc\s*=\s*(["\'])(.*?)\1#i', $attrs, $s ) ) {
			$category = self::category_for( $s[2] );
		}

		if ( '' === $category && '' !== trim( $body ) ) {
			// Tracking snippets declare themselves in the first few lines, so
			// only the head of a large payload is worth lowercasing.
			$head = strlen( $body ) > self::SCAN_LIMIT ? substr( $body, 0, self::SCAN_LIMIT ) : $body;

			if ( false !== strpos( $head, 'pfhConsent' ) ) {
				return $whole;
			}

			$category = self::category_for( $head );
		}

		if ( '' === $category ) {
			return $whole;
		}

		return self::hold( $open, $category ) . $body . $tail;
	}

	/**
	 * Hold an embed until marketing consent is given.
	 *
	 * Receives the opening tag only; the closing </iframe> is left where it
	 * is, which is all this needs to move the src out of reach.
	 *
	 * @param array $m Regex match.
	 * @return string
	 */
	private static function iframe_callback( $m ) {
		$open = (string) $m[0];

		if ( false !== stripos( $open, 'data-pfh-consent' ) || false !== stripos( $open, 'data-pfh-keep' ) ) {
			return $open;
		}

		if ( ! preg_match( '#\bsrc\s*=\s*(["\'])(.*?)\1#i', $open, $src ) ) {
			return $open;
		}

		$hosts = PFH_Widgets_Helpers::lines( self::get( 'iframe_hosts', '' ) );

		if ( ! self::matches( $src[2], $hosts ) ) {
			return $open;
		}

		$held = preg_replace( '#\bsrc\s*=\s*#i', 'data-pfh-src=', $open, 1 );

		if ( ! is_string( $held ) ) {
			return $open;
		}

		$held = preg_replace( '#<iframe\b#i', '<iframe data-pfh-consent="marketing" hidden', $held, 1 );

		if ( ! is_string( $held ) ) {
			return $open;
		}

		return '<div class="pfh-ck__blocked" data-pfh-ck-placeholder="marketing">'
			. '<p>' . esc_html( self::get( 'placeholder_text', '' ) ) . '</p>'
			. '<button type="button" class="pfh-ck__btn pfh-ck__btn--solid" data-pfh-ck="accept-embed">'
			. esc_html( self::get( 'placeholder_button', '' ) ) . '</button>'
			. '</div>'
			. $held;
	}

	/**
	 * Neutralise a script tag.
	 *
	 * type="text/plain" stops the browser executing it; moving src out of the
	 * way stops it being fetched. Both are reversed in JavaScript on consent.
	 *
	 * Takes the opening tag only — running these patterns over a script body
	 * is how the unbounded-match problem gets back in.
	 *
	 * @param string $tag      Opening tag.
	 * @param string $category Consent category.
	 * @return string
	 */
	private static function hold( $tag, $category ) {
		$original = $tag;

		$tag = preg_replace( '#\stype\s*=\s*(["\']).*?\1#i', '', $tag, 1 );
		$tag = is_string( $tag ) ? preg_replace( '#\bsrc\s*=\s*#i', 'data-pfh-src=', $tag, 1 ) : null;
		$tag = is_string( $tag )
			? preg_replace(
				'#<script\b#i',
				'<script type="text/plain" data-pfh-consent="' . esc_attr( $category ) . '"',
				$tag,
				1
			)
			: null;

		// Failing to block is a bug worth fixing; emitting nothing is an
		// outage. Hand back the untouched tag if any step gave up.
		return is_string( $tag ) ? $tag : $original;
	}

	/**
	 * Which category, if any, a URL or snippet belongs to.
	 *
	 * @param string $subject URL or script body.
	 * @return string Category slug, or an empty string.
	 */
	private static function category_for( $subject ) {
		if ( '' === trim( (string) $subject ) ) {
			return '';
		}

		foreach ( self::$optional as $cat ) {
			$hosts = PFH_Widgets_Helpers::lines( self::get( $cat . '_hosts', '' ) );

			if ( self::matches( $subject, $hosts ) ) {
				return $cat;
			}
		}

		if ( self::get( 'block_google', false ) ) {
			$google = [ 'googletagmanager.com', 'google-analytics.com', 'gtag/js' ];

			if ( self::matches( $subject, $google ) ) {
				return 'statistics';
			}
		}

		return '';
	}

	/**
	 * Case-insensitive substring match against a list.
	 *
	 * @param string $subject Haystack.
	 * @param array  $needles Patterns.
	 * @return bool
	 */
	private static function matches( $subject, array $needles ) {
		$subject = strtolower( (string) $subject );

		foreach ( $needles as $needle ) {
			$needle = strtolower( trim( (string) $needle ) );

			if ( '' !== $needle && false !== strpos( $subject, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/* ---------------------------------------------------------------------
	 * Record
	 * ------------------------------------------------------------------ */

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;

		return $wpdb->prefix . 'pfh_consent_log';
	}

	/**
	 * Create the log table once, and prune it.
	 */
	public static function maybe_install() {
		if ( ! self::get( 'log_consent', true ) ) {
			return;
		}

		if ( (int) get_option( self::TABLE_VERSION, 0 ) !== self::VERSION ) {
			global $wpdb;

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$table   = self::table();
			$collate = $wpdb->get_charset_collate();

			dbDelta(
				"CREATE TABLE {$table} (
					id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					uid CHAR(32) NOT NULL DEFAULT '',
					consented_at DATETIME NOT NULL,
					categories VARCHAR(191) NOT NULL DEFAULT '',
					policy_version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
					ip VARCHAR(45) NOT NULL DEFAULT '',
					source VARCHAR(191) NOT NULL DEFAULT '',
					PRIMARY KEY (id),
					KEY uid (uid),
					KEY consented_at (consented_at)
				) {$collate};"
			);

			update_option( self::TABLE_VERSION, self::VERSION );
		}

		self::prune();
	}

	/**
	 * Drop records past the retention window.
	 */
	public static function prune() {
		global $wpdb;

		$days = max( 30, (int) self::get( 'retention', 365 ) );
		$last = (int) get_option( 'pfh_consent_pruned', 0 );

		if ( $last && ( time() - $last ) < DAY_IN_SECONDS ) {
			return;
		}

		update_option( 'pfh_consent_pruned', time(), false );

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- housekeeping on our own table.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE consented_at < DATE_SUB(NOW(), INTERVAL %d DAY)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$days
			)
		);
	}

	/**
	 * Record one choice.
	 */
	public static function ajax_log() {
		check_ajax_referer( 'pfh_consent', 'nonce' );

		if ( ! self::get( 'log_consent', true ) ) {
			wp_send_json_success( [ 'logged' => false ] );
		}

		$raw = isset( $_POST['categories'] ) ? sanitize_text_field( wp_unslash( $_POST['categories'] ) ) : '';
		$uid = isset( $_POST['uid'] ) ? sanitize_key( wp_unslash( $_POST['uid'] ) ) : '';

		$categories = array_values(
			array_intersect(
				array_map( 'sanitize_key', explode( ',', $raw ) ),
				array_merge( self::$optional, [ 'functional' ] )
			)
		);

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- our own table, nothing to cache.
		$wpdb->insert(
			self::table(),
			[
				'uid'            => substr( $uid, 0, 32 ),
				'consented_at'   => current_time( 'mysql' ),
				'categories'     => implode( ',', $categories ),
				'policy_version' => (int) self::get( 'policy_version', 1 ),
				'ip'             => self::anonymise_ip(),
				'source'         => isset( $_POST['source'] ) ? esc_url_raw( wp_unslash( $_POST['source'] ) ) : '',
			],
			[ '%s', '%s', '%s', '%d', '%s', '%s' ]
		);

		wp_send_json_success( [ 'logged' => true ] );
	}

	/**
	 * The visitor's IP with the host part removed.
	 *
	 * Keeps the record useful for showing consent came from a real session
	 * without storing an address that identifies a person.
	 *
	 * @return string
	 */
	private static function anonymise_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( '' === $ip ) {
			return '';
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts = explode( '.', $ip );

			if ( 4 === count( $parts ) ) {
				$parts[3] = '0';

				return implode( '.', $parts );
			}
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$parts = explode( ':', $ip );
			$parts = array_slice( $parts, 0, 4 );

			return implode( ':', $parts ) . '::';
		}

		return '';
	}
}
