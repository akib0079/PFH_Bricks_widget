<?php
/**
 * Bricks element: Products For Home account.
 *
 * One element, two states. A visitor gets the sign-in card — the two forms
 * behind one switch, posting to WooCommerce's own handlers so nothing about
 * logging in or registering is reimplemented here. A customer gets the account
 * itself: a rail of tabs beside a panel, and orders that open where they are
 * rather than navigating away.
 *
 * Everything WooCommerce already knows how to do is left to WooCommerce. What
 * this element owns is the shape of it.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PFH_Widgets_Account' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'includes/class-pfh-account.php';
}

class PFH_Element_Account extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-account';
	public $icon         = 'ti-user';
	public $css_selector = '.pfh-acc';

	public function get_label() {
		return esc_html__( 'PFH My Account', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'account', 'login', 'register', 'orders', 'dashboard', 'my account', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::account();
	}

	public function set_control_groups() {
		foreach ( [
			'account' => esc_html__( 'Account', 'pfh-widgets' ),
			'auth'    => esc_html__( 'Sign in & register', 'pfh-widgets' ),
			'style'   => esc_html__( 'Style', 'pfh-widgets' ),
			'layout'  => esc_html__( 'Layout', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		/* ---- the account ---- */

		$this->controls['eyebrow'] = $this->text_field( 'account', esc_html__( 'Eyebrow', 'pfh-widgets' ), 'Mijn account' );

		$this->controls['greeting'] = $this->text_field(
			'account',
			esc_html__( 'Greeting', 'pfh-widgets' ),
			'Hallo, <em>%s</em>',
			[ 'description' => esc_html__( '%s becomes the customer\'s first name. A word in <em> is set in the italic serif.', 'pfh-widgets' ) ]
		);

		$this->controls['lede'] = $this->text_field(
			'account',
			esc_html__( 'Line under the greeting', 'pfh-widgets' ),
			'Hier vind je je bestellingen, je adressen en je gegevens — alles op één plek.'
		);

		$this->controls['shopLabel'] = $this->text_field( 'account', esc_html__( 'Shop button', 'pfh-widgets' ), 'Verder winkelen' );
		$this->controls['shopLink']  = [
			'tab'         => 'content',
			'group'       => 'account',
			'label'       => esc_html__( 'Shop button link', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => '',
			'description' => esc_html__( 'Empty uses the shop page.', 'pfh-widgets' ),
		];

		$this->controls['orders'] = [
			'tab'     => 'content',
			'group'   => 'account',
			'label'   => esc_html__( 'Orders to list', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 100,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['showStats'] = $this->switch_field( 'account', esc_html__( 'Show the three tiles', 'pfh-widgets' ) );
		$this->controls['showTrack'] = $this->switch_field( 'account', esc_html__( 'Show the delivery steps', 'pfh-widgets' ) );

		/* ---- signed out ---- */

		$this->controls['authTitle'] = $this->text_field( 'auth', esc_html__( 'Title', 'pfh-widgets' ), 'Welkom <em>terug</em>' );

		$this->controls['authLede'] = $this->text_field(
			'auth',
			esc_html__( 'Line under it', 'pfh-widgets' ),
			'Log in om je bestellingen te volgen, of maak een account aan — het duurt een halve minuut.'
		);

		$this->controls['asideTitle'] = $this->text_field( 'auth', esc_html__( 'Panel title', 'pfh-widgets' ), 'Alles op <em>één plek</em>' );

		$this->controls['asidePoints'] = [
			'tab'           => 'content',
			'group'         => 'auth',
			'label'         => esc_html__( 'Panel points', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'text',
			'default'       => [
				[ 'text' => 'Volg elke bestelling tot aan de deur' ],
				[ 'text' => 'Bestel je favorieten in twee tikken opnieuw' ],
				[ 'text' => 'Je adressen staan klaar bij het afrekenen' ],
				[ 'text' => 'Facturen wanneer je ze nodig hebt' ],
			],
			'fields'        => [
				'text' => [ 'label' => esc_html__( 'Point', 'pfh-widgets' ), 'type' => 'text' ],
			],
		];

		$this->controls['showRegister'] = $this->switch_field(
			'auth',
			esc_html__( 'Offer registration', 'pfh-widgets' ),
			true,
			[ 'description' => esc_html__( 'Switched off — or with registration disabled in WooCommerce — only the sign-in form is shown.', 'pfh-widgets' ) ]
		);

		$this->controls['showNames'] = $this->switch_field( 'auth', esc_html__( 'Ask for a name when registering', 'pfh-widgets' ) );

		/* ---- style ---- */

		$this->controls['accent']   = $this->colour_field( 'style', esc_html__( 'Accent', 'pfh-widgets' ), '#2b5f63' );
		$this->controls['ink']      = $this->colour_field( 'style', esc_html__( 'Headings', 'pfh-widgets' ), '#22301c' );
		$this->controls['bodyInk']  = $this->colour_field( 'style', esc_html__( 'Body text', 'pfh-widgets' ), '#5c6657' );
		$this->controls['mutedInk'] = $this->colour_field( 'style', esc_html__( 'Quiet text', 'pfh-widgets' ), '#8d9589' );
		$this->controls['lineColor'] = $this->colour_field( 'style', esc_html__( 'Borders', 'pfh-widgets' ), '#eaeaea' );
		$this->controls['tileOne']   = $this->colour_field( 'style', esc_html__( 'Tile 1', 'pfh-widgets' ), '#dfe9dc' );
		$this->controls['tileTwo']   = $this->colour_field( 'style', esc_html__( 'Tile 2', 'pfh-widgets' ), '#e6eff4' );
		$this->controls['tileThree'] = $this->colour_field( 'style', esc_html__( 'Tile 3', 'pfh-widgets' ), '#f9e9cf' );

		$this->controls['titleSize'] = $this->number_field( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 34, 20, 60 );
		$this->controls['radius']    = $this->number_field( 'style', esc_html__( 'Card radius (px)', 'pfh-widgets' ), 16, 0, 32 );

		/* ---- layout ---- */

		$this->controls['maxWidth'] = $this->number_field( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1140, 600, 1600 );
		$this->controls['railWidth'] = $this->number_field( 'layout', esc_html__( 'Menu width (px)', 'pfh-widgets' ), 264, 180, 360 );
		$this->controls['gap']      = $this->number_field( 'layout', esc_html__( 'Space beside the menu (px)', 'pfh-widgets' ), 32, 12, 80 );
		$this->controls['padTop']   = $this->number_field( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 64, 0, 200 );
		$this->controls['padBottom'] = $this->number_field( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 96, 0, 200 );
	}

	/* ---- control helpers ---- */

	private function text_field( $group, $label, $default = '', array $extra = [] ) {
		return array_merge(
			[ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'text', 'default' => $default ],
			$extra
		);
	}

	private function switch_field( $group, $label, $on = true, array $extra = [] ) {
		return array_merge(
			[ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'checkbox', 'default' => $on ],
			$extra
		);
	}

	private function colour_field( $group, $label, $default ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'color', 'inline' => true, 'default' => [ 'hex' => $default ] ];
	}

	private function number_field( $group, $label, $default, $min, $max ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'number', 'min' => $min, 'max' => $max, 'inline' => true, 'default' => $default ];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-acc pfh-acc--empty"><p>' . esc_html__( 'The account page needs WooCommerce.', 'pfh-widgets' ) . '</p></div>';
			}

			return;
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-acc', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-acc__inner">';

		if ( is_user_logged_in() ) {
			$this->render_account();
		} else {
			$this->render_auth();
		}

		echo '</div></section>';
	}

	/* ---------------------------------------------------------------------
	 * Signed out
	 * ------------------------------------------------------------------ */

	private function render_auth() {
		$register = $this->switched_on( 'showRegister', true ) && 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );

		printf(
			'<div style="max-width:560px"><p class="pfh-acc__eyebrow">%s</p><h1 class="pfh-acc__title">%s</h1><p class="pfh-acc__lede">%s</p></div>',
			esc_html( (string) $this->setting( 'eyebrow', '' ) ),
			wp_kses( (string) $this->setting( 'authTitle', '' ), [ 'em' => [], 'i' => [], 'br' => [] ] ),
			esc_html( (string) $this->setting( 'authLede', '' ) )
		);

		echo '<div class="pfh-acc__card pfh-acc__auth">';

		$this->render_auth_aside();

		echo '<div class="pfh-acc__auth-main">';

		$this->render_notices();

		if ( $register ) {
			printf(
				'<div class="pfh-acc__seg" role="tablist" aria-label="%s" data-pfh-acc-seg>',
				esc_attr__( 'Inloggen of registreren', 'pfh-widgets' )
			);

			printf(
				'<button type="button" class="pfh-acc__seg-btn" role="tab" id="pfh-seg-login-%1$s" aria-controls="pfh-form-login-%1$s" aria-selected="true" data-pfh-acc-form="login">%2$s</button>',
				esc_attr( $this->uid() ),
				esc_html__( 'Inloggen', 'pfh-widgets' )
			);

			printf(
				'<button type="button" class="pfh-acc__seg-btn" role="tab" id="pfh-seg-register-%1$s" aria-controls="pfh-form-register-%1$s" aria-selected="false" tabindex="-1" data-pfh-acc-form="register">%2$s</button>',
				esc_attr( $this->uid() ),
				esc_html__( 'Registreren', 'pfh-widgets' )
			);

			echo '</div>';
		}

		$this->render_login_form( $register );

		if ( $register ) {
			$this->render_register_form();
		}

		echo '</div></div>';
	}

	private function render_auth_aside() {
		$points = $this->setting( 'asidePoints', [] );
		$title  = trim( (string) $this->setting( 'asideTitle', '' ) );

		if ( '' === $title && ! $points ) {
			return;
		}

		echo '<div class="pfh-acc__auth-aside">';

		if ( '' !== $title ) {
			echo '<h2 class="pfh-acc__auth-title">' . wp_kses( $title, [ 'em' => [], 'i' => [], 'br' => [] ] ) . '</h2>';
		}

		if ( is_array( $points ) && $points ) {
			echo '<ul class="pfh-acc__auth-points">';

			foreach ( $points as $point ) {
				$text = isset( $point['text'] ) ? trim( (string) $point['text'] ) : '';

				if ( '' === $text ) {
					continue;
				}

				printf(
					'<li class="pfh-acc__auth-point">%s<span>%s</span></li>',
					PFH_Widgets_Icons::get( 'check' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
					esc_html( $text )
				);
			}

			echo '</ul>';
		}

		echo '</div>';
	}

	/**
	 * WooCommerce's own login, in our clothes.
	 *
	 * The field names and the nonce are the ones WC_Form_Handler expects, so
	 * the shop's login rules, its rate limiting and its error messages all
	 * still apply — none of that is reimplemented here.
	 *
	 * @param bool $tabbed Whether the register form shares the panel.
	 */
	private function render_login_form( $tabbed ) {
		printf(
			'<form class="pfh-acc__form" id="pfh-form-login-%1$s" method="post" %2$s>',
			esc_attr( $this->uid() ),
			$tabbed ? 'role="tabpanel" aria-labelledby="pfh-seg-login-' . esc_attr( $this->uid() ) . '"' : ''
		);

		$this->field( 'username-' . $this->uid(), __( 'E-mailadres', 'pfh-widgets' ), 'text', 'username', [ 'autocomplete' => 'username', 'placeholder' => 'naam@voorbeeld.nl', 'required' => true ] );
		$this->password_field( 'password-' . $this->uid(), __( 'Wachtwoord', 'pfh-widgets' ), 'password', 'current-password' );

		printf(
			'<div class="pfh-acc__form-foot"><label class="pfh-acc__remember"><input type="checkbox" name="rememberme" value="forever" /> %s</label><a class="pfh-acc__link" href="%s">%s</a></div>',
			esc_html__( 'Ingelogd blijven', 'pfh-widgets' ),
			esc_url( function_exists( 'wc_lostpassword_url' ) ? wc_lostpassword_url() : wp_lostpassword_url() ),
			esc_html__( 'Wachtwoord vergeten?', 'pfh-widgets' )
		);

		wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' );

		printf(
			'<div><button type="submit" class="pfh-acc__btn" style="width:100%%" name="login" value="%s">%s</button></div>',
			esc_attr__( 'Inloggen', 'pfh-widgets' ),
			esc_html__( 'Inloggen', 'pfh-widgets' )
		);

		echo '</form>';
	}

	private function render_register_form() {
		printf(
			'<form class="pfh-acc__form" id="pfh-form-register-%1$s" method="post" role="tabpanel" aria-labelledby="pfh-seg-register-%1$s" hidden>',
			esc_attr( $this->uid() )
		);

		if ( $this->switched_on( 'showNames', true ) ) {
			echo '<div class="pfh-acc__row">';
			$this->field( 'first-' . $this->uid(), __( 'Voornaam', 'pfh-widgets' ), 'text', 'pfh_first_name', [ 'autocomplete' => 'given-name' ] );
			$this->field( 'last-' . $this->uid(), __( 'Achternaam', 'pfh-widgets' ), 'text', 'pfh_last_name', [ 'autocomplete' => 'family-name' ] );
			echo '</div>';
		}

		$this->field( 'email-' . $this->uid(), __( 'E-mailadres', 'pfh-widgets' ), 'email', 'email', [ 'autocomplete' => 'email', 'placeholder' => 'naam@voorbeeld.nl', 'required' => true ] );

		// A shop that generates its own passwords asks for none.
		if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) {
			$this->password_field( 'newpass-' . $this->uid(), __( 'Wachtwoord', 'pfh-widgets' ), 'password', 'new-password', __( 'Minimaal 8 tekens.', 'pfh-widgets' ) );
		}

		/**
		 * WooCommerce's own registration hook, so anything a shop has added to
		 * the register form — a consent box, a marketing opt-in — still shows.
		 */
		if ( has_action( 'woocommerce_register_form' ) ) {
			echo '<div class="pfh-acc__woo">';
			do_action( 'woocommerce_register_form' );
			echo '</div>';
		}

		wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' );

		printf(
			'<div><button type="submit" class="pfh-acc__btn" style="width:100%%" name="register" value="%s">%s</button></div>',
			esc_attr__( 'Registreren', 'pfh-widgets' ),
			esc_html__( 'Account aanmaken', 'pfh-widgets' )
		);

		$privacy = get_option( 'woocommerce_registration_privacy_policy_text' );

		if ( $privacy ) {
			echo '<p class="pfh-acc__hint">' . wp_kses_post( wpautop( wptexturize( $privacy ) ) ) . '</p>';
		}

		echo '</form>';
	}

	/* ---------------------------------------------------------------------
	 * Signed in
	 * ------------------------------------------------------------------ */

	private function render_account() {
		$user   = wp_get_current_user();
		$orders = $this->orders();

		printf(
			'<div class="pfh-acc__head"><div><p class="pfh-acc__eyebrow">%s</p><h1 class="pfh-acc__title">%s</h1><p class="pfh-acc__lede">%s</p></div>%s</div>',
			esc_html( (string) $this->setting( 'eyebrow', '' ) ),
			wp_kses(
				sprintf( (string) $this->setting( 'greeting', '%s' ), esc_html( $user->first_name ? $user->first_name : $user->display_name ) ),
				[ 'em' => [], 'i' => [], 'br' => [] ]
			),
			esc_html( (string) $this->setting( 'lede', '' ) ),
			$this->shop_button() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built below.
		);

		echo '<div class="pfh-acc__layout">';
		$this->render_rail( count( $orders ) );

		echo '<div class="pfh-acc__panel">';
		$this->render_notices();
		$this->render_dashboard( $orders );
		$this->render_orders( $orders );
		$this->render_addresses();
		$this->render_details( $user );
		echo '</div></div>';
	}

	private function shop_button() {
		$label = trim( (string) $this->setting( 'shopLabel', '' ) );

		if ( '' === $label ) {
			return '';
		}

		$url = trim( (string) $this->setting( 'shopLink', '' ) );

		if ( '' === $url ) {
			$url = function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : home_url( '/' );
		}

		return sprintf( '<a class="pfh-acc__btn pfh-acc__btn--quiet" href="%s">%s</a>', esc_url( $url ), esc_html( $label ) );
	}

	/**
	 * @param int $count Orders listed.
	 */
	private function render_rail( $count ) {
		$tabs = [
			'dashboard' => [ 'grid', __( 'Overzicht', 'pfh-widgets' ), null ],
			'orders'    => [ 'box-check', __( 'Bestellingen', 'pfh-widgets' ), $count ],
			'addresses' => [ 'usp-delivery', __( 'Adressen', 'pfh-widgets' ), null ],
			'details'   => [ 'account', __( 'Gegevens', 'pfh-widgets' ), null ],
		];

		printf(
			'<nav class="pfh-acc__rail pfh-acc__card" role="tablist" aria-label="%s" data-pfh-acc-rail>',
			esc_attr__( 'Mijn account', 'pfh-widgets' )
		);

		$first = true;

		foreach ( $tabs as $key => $tab ) {
			printf(
				'<button type="button" class="pfh-acc__tab" role="tab" id="pfh-tab-%1$s-%2$s" aria-controls="pfh-pane-%1$s-%2$s" aria-selected="%3$s" tabindex="%4$d" data-pfh-acc-tab="%1$s">%5$s<span>%6$s</span>%7$s</button>',
				esc_attr( $key ),
				esc_attr( $this->uid() ),
				$first ? 'true' : 'false',
				$first ? 0 : -1,
				PFH_Widgets_Icons::get( $tab[0] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				esc_html( $tab[1] ),
				null === $tab[2] ? '' : '<span class="pfh-acc__tab-count">' . esc_html( number_format_i18n( $tab[2] ) ) . '</span>'
			);

			$first = false;
		}

		printf(
			'<a class="pfh-acc__tab pfh-acc__tab--out" href="%s">%s<span>%s</span></a>',
			esc_url( wp_logout_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' ) ) ),
			PFH_Widgets_Icons::get( 'close' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			esc_html__( 'Uitloggen', 'pfh-widgets' )
		);

		echo '</nav>';
	}

	/**
	 * @param string $key      Pane key.
	 * @param bool   $selected Whether it opens.
	 */
	private function open_pane( $key, $selected = false ) {
		printf(
			'<section class="pfh-acc__pane" id="pfh-pane-%1$s-%2$s" role="tabpanel" aria-labelledby="pfh-tab-%1$s-%2$s" tabindex="0"%3$s>',
			esc_attr( $key ),
			esc_attr( $this->uid() ),
			$selected ? '' : ' hidden'
		);
	}

	/**
	 * @param WC_Order[] $orders Orders.
	 */
	private function render_dashboard( array $orders ) {
		$this->open_pane( 'dashboard', true );

		if ( $this->switched_on( 'showStats', true ) ) {
			$spent   = 0.0;
			$moving  = 0;

			foreach ( $orders as $order ) {
				$spent += (float) $order->get_total();

				if ( in_array( $order->get_status(), [ 'processing', 'on-hold' ], true ) ) {
					$moving++;
				}
			}

			echo '<div class="pfh-acc__card pfh-acc__block">';
			printf( '<h2 class="pfh-acc__block-title">%s</h2>', esc_html__( 'Overzicht', 'pfh-widgets' ) );
			printf( '<p class="pfh-acc__block-lede">%s</p>', esc_html__( 'Een samenvatting van je account.', 'pfh-widgets' ) );
			echo '<ul class="pfh-acc__stats">';

			foreach ( [
				[ number_format_i18n( count( $orders ) ), __( 'Bestellingen', 'pfh-widgets' ) ],
				[ number_format_i18n( $moving ), __( 'Onderweg', 'pfh-widgets' ) ],
				[ wp_strip_all_tags( wc_price( $spent ) ), __( 'Totaal besteed', 'pfh-widgets' ) ],
			] as $stat ) {
				printf(
					'<li class="pfh-acc__stat"><span class="pfh-acc__stat-value">%s</span><span class="pfh-acc__stat-label">%s</span></li>',
					esc_html( $stat[0] ),
					esc_html( $stat[1] )
				);
			}

			echo '</ul></div>';
		}

		echo '<div class="pfh-acc__card pfh-acc__block">';
		printf( '<h2 class="pfh-acc__block-title">%s</h2>', esc_html__( 'Laatste bestelling', 'pfh-widgets' ) );

		if ( $orders ) {
			printf( '<p class="pfh-acc__block-lede">%s</p>', esc_html__( 'Je meest recente bestelling, met de stand van zaken.', 'pfh-widgets' ) );
			echo '<ul class="pfh-acc__orders">';
			$this->render_order( $orders[0], true );
			echo '</ul>';
		} else {
			$this->render_empty();
		}

		echo '</div></section>';
	}

	/**
	 * @param WC_Order[] $orders Orders.
	 */
	private function render_orders( array $orders ) {
		$this->open_pane( 'orders' );

		echo '<div class="pfh-acc__card pfh-acc__block">';
		printf( '<h2 class="pfh-acc__block-title">%s</h2>', esc_html__( 'Bestellingen', 'pfh-widgets' ) );

		if ( ! $orders ) {
			$this->render_empty();
			echo '</div></section>';

			return;
		}

		printf( '<p class="pfh-acc__block-lede">%s</p>', esc_html__( 'Klap een bestelling open om de producten en de bezorging te zien.', 'pfh-widgets' ) );
		echo '<ul class="pfh-acc__orders">';

		foreach ( $orders as $order ) {
			$this->render_order( $order );
		}

		echo '</ul></div></section>';
	}

	/**
	 * One row in the list. Its body is fetched when it is first opened, so a
	 * customer with forty orders does not download forty of them to look at
	 * one.
	 *
	 * @param WC_Order $order Order.
	 * @param bool     $open  Whether it starts open.
	 */
	private function render_order( $order, $open = false ) {
		$id     = (int) $order->get_id();
		$status = $order->get_status();

		printf(
			'<li class="pfh-acc__order%s" data-pfh-acc-order="%d">',
			$open ? ' is-open' : '',
			$id
		);

		printf(
			'<button type="button" class="pfh-acc__order-head" aria-expanded="%s" aria-controls="pfh-order-%d-%s">',
			$open ? 'true' : 'false',
			$id,
			esc_attr( $this->uid() )
		);

		printf(
			'<span><span class="pfh-acc__order-id">%s</span><span class="pfh-acc__order-date">%s</span></span>',
			esc_html( sprintf( /* translators: order number */ __( 'Bestelling #%s', 'pfh-widgets' ), $order->get_order_number() ) ),
			esc_html( $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'j F Y' ) : '' )
		);

		printf(
			'<span class="pfh-acc__status pfh-acc__status--%s">%s</span>',
			esc_attr( $status ),
			esc_html( wc_get_order_status_name( $status ) )
		);

		printf(
			'<span class="pfh-acc__order-total">%s</span>',
			wp_kses_post( $order->get_formatted_order_total() )
		);

		printf(
			'<span class="pfh-acc__order-toggle">%s%s</span>',
			esc_html__( 'Details', 'pfh-widgets' ),
			PFH_Widgets_Icons::get( 'chevron' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		);

		echo '</button>';

		printf(
			'<div class="pfh-acc__order-body" id="pfh-order-%d-%s"%s>',
			$id,
			esc_attr( $this->uid() ),
			$open ? '' : ' hidden'
		);

		if ( $open ) {
			echo self::order_detail( $order, $this->switched_on( 'showTrack', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built below.
		} else {
			printf(
				'<p class="pfh-acc__loading"><span class="pfh-acc__spin"></span> %s</p>',
				esc_html__( 'Bestelling wordt opgehaald…', 'pfh-widgets' )
			);
		}

		echo '</div></li>';
	}

	/**
	 * The inside of one order: where it is, what is in it, what it cost.
	 *
	 * Static, because the AJAX endpoint renders it too — one order looks the
	 * same whether it arrived with the page or a moment later.
	 *
	 * @param WC_Order $order Order.
	 * @param bool     $track Whether to draw the delivery steps.
	 * @return string
	 */
	public static function order_detail( $order, $track = true ) {
		ob_start();

		if ( $track ) {
			$progress = PFH_Widgets_Account::progress( $order );

			if ( $progress['stalled'] ) {
				printf(
					'<p class="pfh-acc__notice">%s</p>',
					esc_html( sprintf( /* translators: order status */ __( 'Deze bestelling is %s.', 'pfh-widgets' ), wc_get_order_status_name( $order->get_status() ) ) )
				);
			} else {
				echo '<ol class="pfh-acc__track">';

				foreach ( $progress['steps'] as $step ) {
					printf(
						'<li class="pfh-acc__step %s">%s<span class="pfh-acc__step-when">%s</span></li>',
						esc_attr( $step['state'] ),
						esc_html( $step['label'] ),
						esc_html( $step['when'] )
					);
				}

				echo '</ol>';
			}

			$trace = PFH_Widgets_Account::tracking( $order );

			if ( '' !== $trace['number'] ) {
				printf(
					'<p class="pfh-acc__trace"><strong>%s</strong> <span>%s</span>%s</p>',
					esc_html__( 'Track & trace', 'pfh-widgets' ),
					esc_html( trim( $trace['carrier'] . ' ' . $trace['number'] ) ),
					'' !== $trace['url']
						? sprintf( ' <a class="pfh-acc__link" href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $trace['url'] ), esc_html__( 'Volg je pakket', 'pfh-widgets' ) )
						: ''
				);
			}
		}

		echo '<ul class="pfh-acc__lines">';

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$image   = $product ? $product->get_image_id() : 0;
			$url     = $image ? wp_get_attachment_image_url( $image, 'thumbnail' ) : '';

			echo '<li class="pfh-acc__line"><span class="pfh-acc__line-media">';

			if ( $url ) {
				printf( '<img src="%s" alt="" loading="lazy" decoding="async" />', esc_url( $url ) );
			}

			echo '</span><span>';

			printf( '<span class="pfh-acc__line-name">%s</span>', esc_html( $item->get_name() ) );

			$meta = wc_display_item_meta( $item, [ 'echo' => false, 'before' => '', 'after' => '', 'separator' => ' · ', 'label_before' => '', 'label_after' => ': ' ] );

			printf(
				'<span class="pfh-acc__line-meta">%s%s</span>',
				$meta ? wp_kses_post( wp_strip_all_tags( $meta ) ) . ' · ' : '',
				esc_html( sprintf( /* translators: quantity */ __( '%d×', 'pfh-widgets' ), (int) $item->get_quantity() ) )
			);

			echo '</span>';

			printf( '<span class="pfh-acc__line-total">%s</span>', wp_kses_post( $order->get_formatted_line_subtotal( $item ) ) );

			echo '</li>';
		}

		echo '</ul><div class="pfh-acc__totals">';

		foreach ( $order->get_order_item_totals() as $key => $total ) {
			printf(
				'<div class="pfh-acc__total-row%s"><span>%s</span><span>%s</span></div>',
				'order_total' === $key ? ' pfh-acc__total-row--grand' : '',
				esc_html( $total['label'] ),
				wp_kses_post( $total['value'] )
			);
		}

		echo '</div><div class="pfh-acc__order-actions">';

		printf(
			'<a class="pfh-acc__btn pfh-acc__btn--small" href="%s">%s</a>',
			esc_url( $order->get_view_order_url() ),
			esc_html__( 'Bekijk bestelling', 'pfh-widgets' )
		);

		if ( $order->needs_payment() ) {
			printf(
				'<a class="pfh-acc__btn pfh-acc__btn--quiet pfh-acc__btn--small" href="%s">%s</a>',
				esc_url( $order->get_checkout_payment_url() ),
				esc_html__( 'Betalen', 'pfh-widgets' )
			);
		}

		/**
		 * Room for another button on an order — an invoice, a return.
		 *
		 * @param WC_Order $order Order.
		 */
		do_action( 'pfh_account_order_actions', $order );

		echo '</div>';

		return (string) ob_get_clean();
	}

	private function render_addresses() {
		$this->open_pane( 'addresses' );

		echo '<div class="pfh-acc__card pfh-acc__block">';
		printf( '<h2 class="pfh-acc__block-title">%s</h2>', esc_html__( 'Adressen', 'pfh-widgets' ) );
		printf( '<p class="pfh-acc__block-lede">%s</p>', esc_html__( 'Deze adressen gebruiken we bij het afrekenen.', 'pfh-widgets' ) );
		echo '<div class="pfh-acc__addresses">';

		foreach ( [
			'billing'  => __( 'Factuuradres', 'pfh-widgets' ),
			'shipping' => __( 'Verzendadres', 'pfh-widgets' ),
		] as $type => $label ) {
			$address = function_exists( 'wc_get_account_formatted_address' ) ? wc_get_account_formatted_address( $type ) : '';

			echo '<div class="pfh-acc__address">';
			printf( '<h3>%s</h3>', esc_html( $label ) );

			printf(
				'<address>%s</address>',
				$address ? wp_kses_post( $address ) : esc_html__( 'Nog niet ingevuld.', 'pfh-widgets' )
			);

			printf(
				'<a class="pfh-acc__btn pfh-acc__btn--quiet pfh-acc__btn--small" href="%s">%s</a>',
				esc_url( wc_get_endpoint_url( 'edit-address', $type, wc_get_page_permalink( 'myaccount' ) ) ),
				esc_html( $address ? __( 'Bewerken', 'pfh-widgets' ) : __( 'Toevoegen', 'pfh-widgets' ) )
			);

			echo '</div>';
		}

		echo '</div></div></section>';
	}

	/**
	 * @param WP_User $user Current user.
	 */
	private function render_details( $user ) {
		$this->open_pane( 'details' );

		echo '<div class="pfh-acc__card pfh-acc__block">';
		printf( '<h2 class="pfh-acc__block-title">%s</h2>', esc_html__( 'Gegevens', 'pfh-widgets' ) );
		printf( '<p class="pfh-acc__block-lede">%s</p>', esc_html__( 'Je naam, je e-mailadres en je wachtwoord.', 'pfh-widgets' ) );

		printf(
			'<form class="pfh-acc__form" method="post" action="%s">',
			esc_url( wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) ) )
		);

		echo '<div class="pfh-acc__row">';
		$this->field( 'fn-' . $this->uid(), __( 'Voornaam', 'pfh-widgets' ), 'text', 'account_first_name', [ 'value' => $user->first_name, 'autocomplete' => 'given-name' ] );
		$this->field( 'ln-' . $this->uid(), __( 'Achternaam', 'pfh-widgets' ), 'text', 'account_last_name', [ 'value' => $user->last_name, 'autocomplete' => 'family-name' ] );
		echo '</div>';

		$this->field( 'em-' . $this->uid(), __( 'E-mailadres', 'pfh-widgets' ), 'email', 'account_email', [ 'value' => $user->user_email, 'autocomplete' => 'email', 'required' => true ] );

		$this->password_field(
			'cp-' . $this->uid(),
			__( 'Huidig wachtwoord', 'pfh-widgets' ),
			'password_current',
			'current-password',
			__( 'Alleen nodig als je je wachtwoord wijzigt.', 'pfh-widgets' )
		);

		$this->password_field( 'np-' . $this->uid(), __( 'Nieuw wachtwoord', 'pfh-widgets' ), 'password_1', 'new-password' );
		$this->password_field( 'np2-' . $this->uid(), __( 'Herhaal het nieuwe wachtwoord', 'pfh-widgets' ), 'password_2', 'new-password' );

		wp_nonce_field( 'save_account_details', 'save-account-details-nonce' );

		printf(
			'<div><button type="submit" class="pfh-acc__btn" name="save_account_details" value="%s">%s</button></div>',
			esc_attr__( 'Opslaan', 'pfh-widgets' ),
			esc_html__( 'Opslaan', 'pfh-widgets' )
		);

		echo '<input type="hidden" name="action" value="save_account_details" />';
		echo '</form></div></section>';
	}

	/* ---------------------------------------------------------------------
	 * Pieces
	 * ------------------------------------------------------------------ */

	/**
	 * @param string $id    Field id.
	 * @param string $label Label.
	 * @param string $type  Input type.
	 * @param string $name  Field name.
	 * @param array  $args  value, autocomplete, placeholder, required.
	 */
	private function field( $id, $label, $type, $name, array $args = [] ) {
		printf(
			'<p class="pfh-acc__field"><label class="pfh-acc__label" for="%s">%s</label><input class="pfh-acc__input" id="%s" type="%s" name="%s" value="%s"%s%s%s /></p>',
			esc_attr( $id ),
			esc_html( $label ),
			esc_attr( $id ),
			esc_attr( $type ),
			esc_attr( $name ),
			esc_attr( isset( $args['value'] ) ? $args['value'] : '' ),
			isset( $args['autocomplete'] ) ? ' autocomplete="' . esc_attr( $args['autocomplete'] ) . '"' : '',
			isset( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '',
			! empty( $args['required'] ) ? ' required' : ''
		);
	}

	/**
	 * @param string $id       Field id.
	 * @param string $label    Label.
	 * @param string $name     Field name.
	 * @param string $complete autocomplete token.
	 * @param string $hint     Optional hint under it.
	 */
	private function password_field( $id, $label, $name, $complete, $hint = '' ) {
		printf(
			'<p class="pfh-acc__field"><label class="pfh-acc__label" for="%s">%s</label><span class="pfh-acc__pass"><input class="pfh-acc__input" id="%s" type="password" name="%s" autocomplete="%s" /><button type="button" class="pfh-acc__peek" data-pfh-acc-peek aria-label="%s">%s</button></span>%s</p>',
			esc_attr( $id ),
			esc_html( $label ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $complete ),
			esc_attr__( 'Toon wachtwoord', 'pfh-widgets' ),
			PFH_Widgets_Icons::get( 'eye' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			'' !== $hint ? '<span class="pfh-acc__hint">' . esc_html( $hint ) . '</span>' : ''
		);
	}

	/**
	 * WooCommerce's own notices — a wrong password, a saved address.
	 */
	private function render_notices() {
		if ( ! function_exists( 'wc_get_notices' ) || ! did_action( 'woocommerce_init' ) ) {
			return;
		}

		foreach ( [ 'error' => '', 'success' => ' pfh-acc__notice--good', 'notice' => ' pfh-acc__notice--good' ] as $type => $class ) {
			foreach ( (array) wc_get_notices( $type ) as $notice ) {
				printf(
					'<p class="pfh-acc__notice%s">%s</p>',
					esc_attr( $class ),
					wp_kses_post( is_array( $notice ) ? $notice['notice'] : $notice )
				);
			}
		}

		wc_clear_notices();
	}

	private function render_empty() {
		printf(
			'<div class="pfh-acc__empty">%s<p>%s</p><a class="pfh-acc__btn" href="%s">%s</a></div>',
			PFH_Widgets_Icons::get( 'basket-add' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			esc_html__( 'Je hebt nog niets besteld. Zodra je dat doet, vind je hier de stand van zaken.', 'pfh-widgets' ),
			esc_url( function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : home_url( '/' ) ),
			esc_html__( 'Naar de winkel', 'pfh-widgets' )
		);
	}

	/**
	 * This customer's orders, newest first.
	 *
	 * @return WC_Order[]
	 */
	private function orders() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return [];
		}

		$orders = wc_get_orders(
			[
				'customer' => get_current_user_id(),
				'limit'    => max( 1, (int) $this->setting( 'orders', 20 ) ),
				'orderby'  => 'date',
				'order'    => 'DESC',
				'status'   => array_keys( wc_get_order_statuses() ),
			]
		);

		return is_array( $orders ) ? $orders : [];
	}

	/**
	 * A stable id for this instance, so two on one page do not share ids.
	 *
	 * @return string
	 */
	private function uid() {
		return ! empty( $this->element['id'] ) ? sanitize_html_class( $this->element['id'] ) : 'acc';
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-acc-max'         => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1140 ) ),
				'--pfh-acc-rail-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'railWidth', 264 ) ),
				'--pfh-acc-gap-set'     => PFH_Widgets_Helpers::unit( $this->setting( 'gap', 32 ) ),
				'--pfh-acc-pt-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 64 ) ),
				'--pfh-acc-pb-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 96 ) ),
				'--pfh-acc-radius'      => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 16 ) ),
				'--pfh-acc-title-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 34 ) ),
				'--pfh-acc-accent'      => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#2b5f63' ),
				'--pfh-acc-ink'         => PFH_Widgets_Helpers::color( $this->setting( 'ink' ), '#22301c' ),
				'--pfh-acc-body'        => PFH_Widgets_Helpers::color( $this->setting( 'bodyInk' ), '#5c6657' ),
				'--pfh-acc-muted'       => PFH_Widgets_Helpers::color( $this->setting( 'mutedInk' ), '#8d9589' ),
				'--pfh-acc-line'        => PFH_Widgets_Helpers::color( $this->setting( 'lineColor' ), '#eaeaea' ),
				'--pfh-acc-tile-1'      => PFH_Widgets_Helpers::color( $this->setting( 'tileOne' ), '#dfe9dc' ),
				'--pfh-acc-tile-2'      => PFH_Widgets_Helpers::color( $this->setting( 'tileTwo' ), '#e6eff4' ),
				'--pfh-acc-tile-3'      => PFH_Widgets_Helpers::color( $this->setting( 'tileThree' ), '#f9e9cf' ),
			]
		);
	}
}
