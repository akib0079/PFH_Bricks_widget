<?php
/**
 * Licence gate for the custom sections.
 *
 * A handover safeguard for the agency: when a site is not licensed, the
 * plugin's *presentational* sections (hero, category slider, features, CTA,
 * reviews slider, and so on) show a small "temporarily unavailable" notice
 * instead of their content, and an admin notice explains why. Entering a
 * valid licence token restores everything on the next page load.
 *
 * What this is NOT, by design:
 *
 *   - It never deletes the plugin, files, the database, or any content.
 *   - It never blocks the site or wp-admin: the admin stays fully usable,
 *     and the store keeps working. The header, footer, cart, checkout,
 *     product and account elements are never locked, so a shopper can still
 *     navigate and buy. Only marketing/design sections degrade.
 *   - It is fully reversible: it changes what is *rendered*, nothing else.
 *   - It is dormant until deliberately armed. On a plain install it does
 *     nothing; enforcement only begins once a public key is present and the
 *     "Licentie afdwingen" switch is on.
 *   - It fails open: if verification cannot run (no libsodium, no/……bad
 *     public key), nothing is locked. A gate that could brick a site on a
 *     server quirk is not one worth having.
 *
 * Tokens are Ed25519-signed and offline: the agency holds the private key
 * (never shipped) and mints a token bound to a domain and an expiry date;
 * the plugin carries only the public key and verifies. A token is not a
 * secret — it only works on its domain, until its date. Renewing a licence
 * means issuing a token with a later date. See dev/license/.
 *
 * Precedence, most trusted first:
 *   Public key : PFH_WIDGETS_LICENSE_PUBKEY constant → bundled default.
 *   Token      : PFH_WIDGETS_LICENSE constant → the key typed on the screen.
 *   Enforce    : PFH_WIDGETS_LICENSE_ENFORCE constant → the switch.
 * Putting the public key and the enforce flag in wp-config.php keeps them out
 * of the database, where a site owner cannot simply switch them off.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_License extends PFH_Settings_Module {

	const OPTION = 'pfh_license';

	/**
	 * The bundled verification key (base64 Ed25519 public key). Safe to ship:
	 * it can check a token, never create one. Replace it with your own from
	 * dev/license/keygen.php, or override with the PFH_WIDGETS_LICENSE_PUBKEY
	 * constant.
	 */
	const DEFAULT_PUBKEY = 'XgonYO+Ymuv8rt2NxE1OMwnVMlPXzVUBicze/Bs76BY=';

	/**
	 * Days a just-expired licence keeps working, with a warning, before the
	 * sections lock — so a lapse never breaks a live page without notice.
	 */
	const GRACE_DAYS = 7;

	/**
	 * The only elements that ever lock: purely presentational sections.
	 * Everything else — the header, footer, cart, checkout, product, account,
	 * archive — always renders, so navigation and the store are never touched.
	 */
	const LOCKABLE = [
		'pfh-hero',
		'pfh-categories',
		'pfh-features',
		'pfh-featured',
		'pfh-featured-olive',
		'pfh-info',
		'pfh-cta',
		'pfh-reviews',
		'pfh-products',
		'pfh-product-grid',
		'pfh-highlight',
		'pfh-blog',
		'pfh-about',
	];

	public static function init() {
		PFH_Widgets_Settings::register(
			'license',
			static function () {
				return __( 'Licentie', 'pfh-widgets' );
			},
			__CLASS__
		);

		add_action( 'admin_notices', [ __CLASS__, 'admin_notice' ] );

		// Registered on the front end only. Whether it actually withholds
		// anything is decided inside gate(), at render time — reading the
		// setting here, at plugins_loaded, would ask for a translation before
		// WordPress is ready to load one.
		if ( ! is_admin() ) {
			add_filter( 'bricks/frontend/render_data', [ __CLASS__, 'gate' ], 20, 2 );
		}
	}

	/* ---------------------------------------------------------------------
	 * State
	 * ------------------------------------------------------------------ */

	/**
	 * The verification key in force, base64.
	 *
	 * @return string
	 */
	public static function pubkey() {
		if ( defined( 'PFH_WIDGETS_LICENSE_PUBKEY' ) && PFH_WIDGETS_LICENSE_PUBKEY ) {
			$key = (string) PFH_WIDGETS_LICENSE_PUBKEY;
		} else {
			$key = (string) self::get( 'pubkey', '' );

			if ( '' === trim( $key ) ) {
				$key = self::DEFAULT_PUBKEY;
			}
		}

		return (string) apply_filters( 'pfh_license_pubkey', trim( $key ) );
	}

	/**
	 * The licence token entered for this site.
	 *
	 * @return string
	 */
	public static function token() {
		if ( defined( 'PFH_WIDGETS_LICENSE' ) && PFH_WIDGETS_LICENSE ) {
			return trim( (string) PFH_WIDGETS_LICENSE );
		}

		return trim( (string) self::get( 'key', '' ) );
	}

	/**
	 * Is the gate armed at all? Off unless someone deliberately turned it on
	 * and the machinery to verify a token is actually present.
	 *
	 * @return bool
	 */
	public static function enforced() {
		if ( defined( 'PFH_WIDGETS_LICENSE_ENFORCE' ) ) {
			$on = (bool) PFH_WIDGETS_LICENSE_ENFORCE;
		} else {
			$on = (bool) self::get( 'enforce', false );
		}

		$on = (bool) apply_filters( 'pfh_license_enforced', $on );

		return $on
			&& '' !== self::pubkey()
			&& function_exists( 'sodium_crypto_sign_verify_detached' );
	}

	/**
	 * Resolve the licence to a state.
	 *
	 * @return array{state:string, expires:string, days:int, plan:string, domain:string}
	 *               state: active | grace | expired | domain | invalid | none | unarmed
	 */
	public static function status() {
		$empty = [ 'state' => 'unarmed', 'expires' => '', 'days' => 0, 'plan' => '', 'domain' => '' ];

		if ( ! self::enforced() ) {
			return $empty;
		}

		$token = self::token();

		if ( '' === $token ) {
			return array_merge( $empty, [ 'state' => 'none' ] );
		}

		$claims = self::verify( $token );

		if ( null === $claims ) {
			return array_merge( $empty, [ 'state' => 'invalid' ] );
		}

		$out = array_merge(
			$empty,
			[
				'expires' => (string) ( $claims['expires'] ?? '' ),
				'plan'    => (string) ( $claims['plan'] ?? '' ),
				'domain'  => (string) ( $claims['domain'] ?? '' ),
			]
		);

		if ( ! self::domain_ok( (string) ( $claims['domain'] ?? '' ) ) ) {
			return array_merge( $out, [ 'state' => 'domain' ] );
		}

		$today   = self::today();
		$expires = strtotime( (string) ( $claims['expires'] ?? '' ) . ' 23:59:59 UTC' );

		if ( false === $expires ) {
			return array_merge( $out, [ 'state' => 'invalid' ] );
		}

		$days = (int) floor( ( $expires - $today ) / DAY_IN_SECONDS );

		if ( $days >= 0 ) {
			return array_merge( $out, [ 'state' => 'active', 'days' => $days ] );
		}

		if ( $days >= -self::GRACE_DAYS ) {
			return array_merge( $out, [ 'state' => 'grace', 'days' => $days ] );
		}

		return array_merge( $out, [ 'state' => 'expired', 'days' => $days ] );
	}

	/**
	 * Whether the presentational sections should be withheld on the front end.
	 *
	 * @return bool
	 */
	public static function locked() {
		if ( ! self::enforced() ) {
			return false;
		}

		// Editing must never be gated, or the licence could not be entered
		// from inside the builder, and the design could not be worked on.
		if ( class_exists( 'PFH_Widgets_Helpers' ) && PFH_Widgets_Helpers::is_builder_context() ) {
			return false;
		}

		$state = self::status()['state'];

		return ! in_array( $state, [ 'active', 'grace', 'unarmed' ], true );
	}

	/**
	 * Verify a token's signature and return its claims, or null.
	 *
	 * @param string $token Signed token.
	 * @return array|null
	 */
	public static function verify( $token ) {
		if ( ! function_exists( 'sodium_crypto_sign_verify_detached' ) ) {
			return null;
		}

		$parts = explode( '.', (string) $token );

		if ( 2 !== count( $parts ) ) {
			return null;
		}

		$payload = self::b64u_decode( $parts[0] );
		$sig     = self::b64u_decode( $parts[1] );
		$pub     = base64_decode( self::pubkey(), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- binary key, not obfuscation.

		if ( '' === $payload || '' === $sig || false === $pub || SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== strlen( $pub ) || SODIUM_CRYPTO_SIGN_BYTES !== strlen( $sig ) ) {
			return null;
		}

		try {
			$ok = sodium_crypto_sign_verify_detached( $sig, $payload, $pub );
		} catch ( \Exception $e ) {
			return null;
		}

		if ( ! $ok ) {
			return null;
		}

		$claims = json_decode( $payload, true );

		return is_array( $claims ) ? $claims : null;
	}

	/**
	 * Does a token's domain cover this site? "*" covers any (for staging).
	 *
	 * @param string $domain Claimed domain.
	 * @return bool
	 */
	public static function domain_ok( $domain ) {
		$domain = strtolower( trim( $domain ) );

		if ( '*' === $domain ) {
			return true;
		}

		$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$strip = static function ( $h ) {
			return preg_replace( '/^www\./', '', (string) $h );
		};

		return '' !== $domain && $strip( $host ) === $strip( $domain );
	}

	/* ---------------------------------------------------------------------
	 * The gate
	 * ------------------------------------------------------------------ */

	/**
	 * Replace each lockable PFH element in the render tree with a small notice.
	 * A pure transform: given Bricks' element list, hand back a list with the
	 * presentational sections swapped for a placeholder, everything else as-is.
	 *
	 * @param array  $elements Bricks element definitions.
	 * @param string $area     header | footer | content (unused; header/footer
	 *                         hold no lockable elements anyway).
	 * @return array
	 */
	public static function gate( $elements, $area = '' ) {
		if ( ! is_array( $elements ) || ! self::locked() ) {
			return $elements;
		}

		$lockable = (array) apply_filters( 'pfh_license_lockable', self::LOCKABLE );

		foreach ( $elements as $i => $element ) {
			$name = isset( $element['name'] ) ? (string) $element['name'] : '';

			if ( '' === $name || ! in_array( $name, $lockable, true ) ) {
				continue;
			}

			$elements[ $i ] = [
				'id'       => isset( $element['id'] ) ? $element['id'] : ( 'pfhlk' . $i ),
				'name'     => 'text-basic',
				'parent'   => isset( $element['parent'] ) ? $element['parent'] : 0,
				'children' => [],
				'settings' => [
					'text'        => self::notice_html(),
					'tag'         => 'div',
					'_cssClasses' => 'pfh-license-locked',
				],
			];
		}

		return $elements;
	}

	/**
	 * @return string
	 */
	public static function notice_html() {
		$text = (string) apply_filters(
			'pfh_license_notice',
			__( 'Deze sectie is tijdelijk niet beschikbaar.', 'pfh-widgets' )
		);

		return '<div class="pfh-license-locked__box" style="padding:28px 24px;border:1px dashed rgba(34,48,28,.25);border-radius:12px;background:#f6f8f5;color:#5c6657;text-align:center;font-family:sans-serif;font-size:15px;line-height:1.5">'
			. esc_html( $text )
			. '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Admin
	 * ------------------------------------------------------------------ */

	public static function admin_notice() {
		if ( ! self::enforced() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = self::status();
		$link   = admin_url( 'admin.php?page=' . PFH_Widgets_Settings::PAGE . '&tab=license' );

		if ( 'active' === $status['state'] ) {
			return;
		}

		if ( 'grace' === $status['state'] ) {
			printf(
				'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
				esc_html__( 'PFH Bricks Widgets — licentie verloopt binnenkort.', 'pfh-widgets' ),
				wp_kses_post(
					sprintf(
						/* translators: 1: days, 2: settings URL. */
						__( 'De licentie is verlopen; de secties werken nog %1$d dag(en) en worden daarna verborgen. <a href="%2$s">Vernieuw de licentie</a>.', 'pfh-widgets' ),
						abs( (int) $status['days'] ),
						esc_url( $link )
					)
				)
			);

			return;
		}

		$reason = [
			'none'    => __( 'Er is nog geen licentietoken ingevoerd.', 'pfh-widgets' ),
			'invalid' => __( 'Het licentietoken is ongeldig.', 'pfh-widgets' ),
			'domain'  => __( 'Het licentietoken hoort bij een ander domein.', 'pfh-widgets' ),
			'expired' => __( 'De licentie is verlopen.', 'pfh-widgets' ),
		];

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s %s</p></div>',
			esc_html__( 'PFH Bricks Widgets — de custom secties zijn verborgen.', 'pfh-widgets' ),
			esc_html( $reason[ $status['state'] ] ?? __( 'De licentie is niet actief.', 'pfh-widgets' ) ),
			wp_kses_post(
				sprintf(
					/* translators: %s: settings URL. */
					__( '<a href="%s">Voer een licentie in</a>.', 'pfh-widgets' ),
					esc_url( $link )
				)
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	public static function fields() {
		return [
			'gate' => [
				'label'  => __( 'Licentie', 'pfh-widgets' ),
				'desc'   => __( 'Beheer de licentie voor de custom secties van deze site. Zonder geldige licentie tonen de presentatie-secties een korte melding; de winkel, navigatie en beheeromgeving blijven volledig werken.', 'pfh-widgets' ),
				'fields' => [
					'enforce' => [
						'type'     => 'checkbox',
						'label'    => __( 'Licentie afdwingen', 'pfh-widgets' ),
						'cb_label' => __( 'Verberg de secties zonder geldige licentie', 'pfh-widgets' ),
						'default'  => false,
						'desc'     => __( 'Staat dit uit, dan is de licentiecontrole slapend en verandert er niets aan de site.', 'pfh-widgets' ),
					],
					'key'     => [
						'type'        => 'textarea',
						'rows'        => 3,
						'label'       => __( 'Licentietoken', 'pfh-widgets' ),
						'default'     => '',
						'desc'        => __( 'Plak hier het token dat je van AVIX Digital Agency ontvangt. Het is gebonden aan dit domein en aan een einddatum.', 'pfh-widgets' ),
					],
					'status'  => [
						'type'     => 'info',
						'callback' => [ __CLASS__, 'render_status' ],
					],
				],
			],
		];
	}

	/**
	 * Live status on the settings screen.
	 */
	public static function render_status() {
		if ( ! function_exists( 'sodium_crypto_sign_verify_detached' ) ) {
			echo '<div class="pfh-settings__note pfh-settings__note--warn"><p>'
				. esc_html__( 'De PHP-extensie libsodium ontbreekt op deze server, dus de licentie kan niet worden gecontroleerd. De secties blijven gewoon zichtbaar.', 'pfh-widgets' )
				. '</p></div>';

			return;
		}

		if ( ! self::enforced() ) {
			echo '<div class="pfh-settings__note"><p>'
				. esc_html__( 'De licentiecontrole staat uit. Zet "Licentie afdwingen" aan om hem te activeren.', 'pfh-widgets' )
				. '</p></div>';

			return;
		}

		$status = self::status();
		$host   = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		if ( 'active' === $status['state'] ) {
			echo '<div class="pfh-settings__note pfh-settings__note--good"><p>'
				. esc_html(
					sprintf(
						/* translators: 1: domain, 2: date, 3: days. */
						__( 'Actief voor %1$s, geldig tot %2$s (nog %3$d dagen).', 'pfh-widgets' ),
						'*' === $status['domain'] ? __( 'elk domein', 'pfh-widgets' ) : $status['domain'],
						$status['expires'],
						(int) $status['days']
					)
				)
				. '</p></div>';

			return;
		}

		$msg = [
			'grace'   => sprintf( __( 'Verlopen op %1$s — nog %2$d dag(en) zichtbaar, daarna verborgen.', 'pfh-widgets' ), $status['expires'], abs( (int) $status['days'] ) ),
			'expired' => sprintf( __( 'Verlopen op %s. De secties zijn verborgen.', 'pfh-widgets' ), $status['expires'] ),
			'domain'  => sprintf( __( 'Dit token hoort bij "%1$s", niet bij "%2$s". De secties zijn verborgen.', 'pfh-widgets' ), $status['domain'], $host ),
			'invalid' => __( 'Het token is ongeldig of onleesbaar. De secties zijn verborgen.', 'pfh-widgets' ),
			'none'    => __( 'Er is nog geen token ingevoerd. De secties zijn verborgen.', 'pfh-widgets' ),
		];

		$class = 'grace' === $status['state'] ? 'pfh-settings__note--warn' : 'pfh-settings__note--bad';

		echo '<div class="pfh-settings__note ' . esc_attr( $class ) . '"><p>'
			. esc_html( $msg[ $status['state'] ] ?? __( 'De licentie is niet actief.', 'pfh-widgets' ) )
			. '</p></div>';
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	/**
	 * @return int Midnight UTC today, so a date compare does not swing on the
	 *             hour a page happens to load.
	 */
	private static function today() {
		return (int) strtotime( gmdate( 'Y-m-d' ) . ' 00:00:00 UTC' );
	}

	/**
	 * @param string $s URL-safe base64.
	 * @return string Raw bytes, or '' on failure.
	 */
	private static function b64u_decode( $s ) {
		$s   = strtr( (string) $s, '-_', '+/' );
		$pad = strlen( $s ) % 4;

		if ( $pad ) {
			$s .= str_repeat( '=', 4 - $pad );
		}

		$out = base64_decode( $s, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- transport, not obfuscation.

		return false === $out ? '' : $out;
	}
}
