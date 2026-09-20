<?php
/**
 * Bricks element: Products For Home contact page.
 *
 * A heading, then a card holding the ways to reach the shop beside a map. Each
 * way is a link rather than a line of text: a phone number dials, an email
 * opens a message, an address opens maps. Any of them left empty is left out.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Contact extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-contact';
	public $icon         = 'ti-email';
	public $css_selector = '.pfh-contact';

	/**
	 * Things can be dropped inside it.
	 *
	 * Which is how the contact form gets here: Bricks' own Form element, with
	 * its own fields, its own actions and its own spam settings. Writing a
	 * second form would mean a second set of everything to keep working.
	 */
	public $nestable = true;

	/**
	 * What a freshly dropped element comes with.
	 *
	 * A Bricks form and nothing else — no settings of ours, so it arrives with
	 * Bricks' own defaults rather than a half-configured copy of them.
	 *
	 * @return array
	 */
	public function get_nestable_children() {
		return [
			[
				'name'  => 'form',
				'label' => esc_html__( 'Contact form', 'pfh-widgets' ),
			],
		];
	}

	public function get_label() {
		return esc_html__( 'PFH Contact', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'contact', 'address', 'phone', 'email', 'map', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::contact();
	}

	public function set_control_groups() {
		foreach ( [
			'head'   => esc_html__( 'Opening', 'pfh-widgets' ),
			'ways'   => esc_html__( 'Contact details', 'pfh-widgets' ),
			'legal'  => esc_html__( 'Registration', 'pfh-widgets' ),
			'map'    => esc_html__( 'Map', 'pfh-widgets' ),
			'style'  => esc_html__( 'Style', 'pfh-widgets' ),
			'layout' => esc_html__( 'Layout', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		/* ---- opening ---- */

		$this->controls['eyebrow'] = $this->text( 'head', esc_html__( 'Eyebrow', 'pfh-widgets' ), 'Contact' );

		$this->controls['title'] = $this->text(
			'head',
			esc_html__( 'Title', 'pfh-widgets' ),
			'Heb je <em>vragen?</em>',
			[ 'description' => esc_html__( 'A word wrapped in <em> is set in the italic serif.', 'pfh-widgets' ) ]
		);

		$this->controls['lede'] = $this->text(
			'head',
			esc_html__( 'Line under it', 'pfh-widgets' ),
			'Je kunt ons op werkdagen bereiken op onderstaande contactgegevens.'
		);

		/* ---- the ways ---- */

		$this->controls['phone'] = $this->text( 'ways', esc_html__( 'Phone', 'pfh-widgets' ), '0617392302' );
		$this->controls['phoneNote'] = $this->text( 'ways', esc_html__( 'Note under the phone', 'pfh-widgets' ), 'Op werkdagen bereikbaar' );

		$this->controls['email'] = $this->text( 'ways', esc_html__( 'Email', 'pfh-widgets' ), 'info@productsforhome.nl' );
		$this->controls['emailNote'] = $this->text( 'ways', esc_html__( 'Note under the email', 'pfh-widgets' ), 'We antwoorden meestal binnen één werkdag' );

		$this->controls['address'] = [
			'tab'         => 'content',
			'group'       => 'ways',
			'label'       => esc_html__( 'Address', 'pfh-widgets' ),
			'type'        => 'textarea',
			'rows'        => 3,
			'default'     => "Nikkelweg 22, 2401 MM\nAlphen aan den Rijn",
			'description' => esc_html__( 'One line per line. It links through to maps.', 'pfh-widgets' ),
		];

		$this->controls['addressNote'] = $this->text( 'ways', esc_html__( 'Note under the address', 'pfh-widgets' ), '' );

		$this->controls['labels'] = [
			'tab'     => 'content',
			'group'   => 'ways',
			'type'    => 'info',
			'content' => esc_html__( 'Anything left empty is left off the page.', 'pfh-widgets' ),
		];

		$this->controls['phoneLabel']   = $this->text( 'ways', esc_html__( 'Phone label', 'pfh-widgets' ), 'Telefoon' );
		$this->controls['emailLabel']   = $this->text( 'ways', esc_html__( 'Email label', 'pfh-widgets' ), 'E-mail' );
		$this->controls['addressLabel'] = $this->text( 'ways', esc_html__( 'Address label', 'pfh-widgets' ), 'Adres' );

		/* ---- registration ---- */

		$this->controls['kvk'] = $this->text( 'legal', esc_html__( 'KVK', 'pfh-widgets' ), '78571936' );
		$this->controls['vat'] = $this->text( 'legal', esc_html__( 'BTW', 'pfh-widgets' ), 'NL003349641B31' );

		/* ---- map ---- */

		$this->controls['mapQuery'] = $this->text(
			'map',
			esc_html__( 'Map location', 'pfh-widgets' ),
			'',
			[
				'placeholder' => 'Nikkelweg 22, Alphen aan den Rijn',
				'description' => esc_html__( 'Empty uses the address above. Clear the address too, or switch the map off, to leave it out.', 'pfh-widgets' ),
			]
		);

		$this->controls['mapPlace'] = [
			'tab'         => 'content',
			'group'       => 'map',
			'label'       => esc_html__( 'Where the map goes', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'default'     => 'side',
			'options'     => [
				'side'  => esc_html__( 'Beside the details', 'pfh-widgets' ),
				'under' => esc_html__( 'Under the card', 'pfh-widgets' ),
			],
			'description' => esc_html__( 'A form dropped into this element takes the space beside the details, so put the map under the card to have both.', 'pfh-widgets' ),
		];

		$this->controls['showMap'] = [
			'tab'     => 'content',
			'group'   => 'map',
			'label'   => esc_html__( 'Show the map', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['mapImage'] = [
			'tab'         => 'content',
			'group'       => 'map',
			'label'       => esc_html__( 'Picture instead of a map', 'pfh-widgets' ),
			'type'        => 'image',
			'description' => esc_html__( 'A photograph of the shop, say. It replaces the map entirely — and loads nothing from Google.', 'pfh-widgets' ),
		];

		$this->controls['mapHeight'] = $this->number( 'map', esc_html__( 'Map height (px)', 'pfh-widgets' ), 380, 200, 700 );

		/* ---- style ---- */

		$this->controls['ink']      = $this->colour( 'style', esc_html__( 'Headings', 'pfh-widgets' ), '#22301c' );
		$this->controls['bodyInk']  = $this->colour( 'style', esc_html__( 'Body text', 'pfh-widgets' ), '#5c6657' );
		$this->controls['mutedInk'] = $this->colour( 'style', esc_html__( 'Labels', 'pfh-widgets' ), '#8d9589' );
		$this->controls['accent']   = $this->colour( 'style', esc_html__( 'Icons and links', 'pfh-widgets' ), '#2b5f63' );
		$this->controls['tile']     = $this->colour( 'style', esc_html__( 'Icon tile', 'pfh-widgets' ), '#f4f7f4' );
		$this->controls['lineColor'] = $this->colour( 'style', esc_html__( 'Borders', 'pfh-widgets' ), '#eaeaea' );

		$this->controls['titleSize'] = $this->number( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 40, 22, 64 );
		$this->controls['radius']    = $this->number( 'style', esc_html__( 'Card radius (px)', 'pfh-widgets' ), 16, 0, 40 );

		/* ---- layout ---- */

		$this->controls['maxWidth'] = $this->number( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1140, 600, 1600 );
		$this->controls['padTop']   = $this->number( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 72, 0, 200 );
		$this->controls['padBottom'] = $this->number( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 72, 0, 200 );
	}

	/* ---- control helpers ---- */

	private function text( $group, $label, $default = '', array $extra = [] ) {
		return array_merge( [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'text', 'default' => $default ], $extra );
	}

	private function colour( $group, $label, $default ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'color', 'inline' => true, 'default' => [ 'hex' => $default ] ];
	}

	private function number( $group, $label, $default, $min, $max ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'number', 'min' => $min, 'max' => $max, 'inline' => true, 'default' => $default ];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$ways = $this->ways();

		if ( ! $ways && '' === trim( (string) $this->setting( 'title', '' ) ) ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-contact pfh-contact--empty"><p>'
					. esc_html__( 'Nothing to show yet: give the page a title, or a way to reach the shop.', 'pfh-widgets' )
					. '</p></div>';
			}

			return;
		}

		$slot  = $this->slot();
		$map   = $this->map();
		$under = 'under' === $this->setting( 'mapPlace', 'side' );

		// The column beside the details: what was dropped in first, then the
		// map — unless the map has been sent under the card.
		$beside = '' !== $slot ? $slot : ( $under ? '' : $map );

		$this->set_attribute( '_root', 'class', [ 'pfh-contact', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-contact__inner">';

		$this->render_head();

		printf( '<div class="pfh-contact__card%s">', '' === $beside ? ' pfh-contact__card--plain' : '' );
		echo '<div class="pfh-contact__body">';

		if ( $ways ) {
			echo '<ul class="pfh-contact__ways">';

			foreach ( $ways as $way ) {
				$this->render_way( $way );
			}

			echo '</ul>';
		}

		$this->render_legal();

		echo '</div>';

		if ( '' !== $beside ) {
			printf(
				'<div class="%s">%s</div>',
				'' !== $slot ? 'pfh-contact__slot' : 'pfh-contact__map',
				$beside // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built below.
			);
		}

		echo '</div>';

		// A form took the column, so the map goes full width under the card.
		if ( $under && '' !== $map ) {
			echo '<div class="pfh-contact__map pfh-contact__map--under">' . $map . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built below.
		}

		echo '</div></section>';
	}

	private function render_head() {
		$eyebrow = trim( (string) $this->setting( 'eyebrow', '' ) );
		$title   = trim( (string) $this->setting( 'title', '' ) );
		$lede    = trim( (string) $this->setting( 'lede', '' ) );

		if ( '' === $eyebrow && '' === $title && '' === $lede ) {
			return;
		}

		echo '<header class="pfh-contact__head">';

		if ( '' !== $eyebrow ) {
			echo '<p class="pfh-contact__eyebrow">' . esc_html( $eyebrow ) . '</p>';
		}

		if ( '' !== $title ) {
			echo '<h1 class="pfh-contact__title">' . wp_kses( $title, [ 'em' => [], 'i' => [], 'br' => [], 'strong' => [] ] ) . '</h1>';
		}

		if ( '' !== $lede ) {
			echo '<p class="pfh-contact__lede">' . esc_html( $lede ) . '</p>';
		}

		echo '</header>';
	}

	/**
	 * @param array $way { icon, label, value, href, note, multiline }
	 */
	private function render_way( array $way ) {
		echo '<li class="pfh-contact__way">';

		printf(
			'<span class="pfh-contact__way-icon">%s</span>',
			PFH_Widgets_Icons::get( $way['icon'] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		);

		echo '<span>';
		printf( '<span class="pfh-contact__way-label">%s</span>', esc_html( $way['label'] ) );

		// nl2br only on the address: a phone number has no lines to keep.
		$value = $way['multiline'] ? nl2br( esc_html( $way['value'] ) ) : esc_html( $way['value'] );

		if ( '' !== $way['href'] ) {
			printf(
				'<a class="pfh-contact__way-value" href="%s"%s>%s</a>',
				esc_url( $way['href'] ),
				0 === strpos( $way['href'], 'http' ) ? ' target="_blank" rel="noopener noreferrer"' : '',
				$value // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);
		} else {
			printf( '<span class="pfh-contact__way-value">%s</span>', $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}

		if ( '' !== $way['note'] ) {
			printf( '<span class="pfh-contact__way-note">%s</span>', esc_html( $way['note'] ) );
		}

		echo '</span></li>';
	}

	private function render_legal() {
		$kvk = trim( (string) $this->setting( 'kvk', '' ) );
		$vat = trim( (string) $this->setting( 'vat', '' ) );

		if ( '' === $kvk && '' === $vat ) {
			return;
		}

		echo '<p class="pfh-contact__legal">';

		if ( '' !== $kvk ) {
			printf( '<span>%s <strong>%s</strong></span>', esc_html__( 'KVK', 'pfh-widgets' ), esc_html( $kvk ) );
		}

		if ( '' !== $vat ) {
			printf( '<span>%s <strong>%s</strong></span>', esc_html__( 'BTW', 'pfh-widgets' ), esc_html( $vat ) );
		}

		echo '</p>';
	}

	/* ---------------------------------------------------------------------
	 * Reading the fields
	 * ------------------------------------------------------------------ */

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function ways() {
		$out     = [];
		$phone   = trim( (string) $this->setting( 'phone', '' ) );
		$email   = trim( (string) $this->setting( 'email', '' ) );
		$address = trim( (string) $this->setting( 'address', '' ) );

		if ( '' !== $phone ) {
			$out[] = [
				'icon'      => 'phone',
				'label'     => (string) $this->setting( 'phoneLabel', '' ),
				'value'     => $phone,
				'href'      => 'tel:' . PFH_Widgets_Helpers::diallable( $phone ),
				'note'      => trim( (string) $this->setting( 'phoneNote', '' ) ),
				'multiline' => false,
			];
		}

		if ( '' !== $email && is_email( $email ) ) {
			$out[] = [
				'icon'      => 'mail',
				'label'     => (string) $this->setting( 'emailLabel', '' ),
				'value'     => $email,
				'href'      => 'mailto:' . $email,
				'note'      => trim( (string) $this->setting( 'emailNote', '' ) ),
				'multiline' => false,
			];
		}

		if ( '' !== $address ) {
			$out[] = [
				'icon'      => 'pin',
				'label'     => (string) $this->setting( 'addressLabel', '' ),
				'value'     => $address,
				'href'      => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( preg_replace( '/\s+/', ' ', $address ) ),
				'note'      => trim( (string) $this->setting( 'addressNote', '' ) ),
				'multiline' => true,
			];
		}

		return $out;
	}

	/**
	 * Whatever was dropped inside this element.
	 *
	 * Bricks renders its own children; all this does is ask it to, and only
	 * when there is something to render and a Bricks to ask. Every call is
	 * guarded because an element file is loaded in places Bricks is not —
	 * the audit, the tests, and admin-ajax among them.
	 *
	 * @return string
	 */
	private function slot() {
		$children = isset( $this->element['children'] ) ? (array) $this->element['children'] : [];

		if ( ! $children || ! class_exists( '\\Bricks\\Frontend' ) || ! method_exists( '\\Bricks\\Frontend', 'render_children' ) ) {
			return '';
		}

		// Some versions return the markup and some echo it; take either.
		ob_start();
		$returned = \Bricks\Frontend::render_children( $this );
		$echoed   = (string) ob_get_clean();

		return ( is_string( $returned ) && '' !== $returned ) ? $returned : $echoed;
	}


	/**
	 * The map, a picture, or nothing at all.
	 *
	 * @return string
	 */
	private function map() {
		$picture = PFH_Widgets_Helpers::image_url( $this->setting( 'mapImage' ), 'large' );

		// A picture wins, and costs the visitor no third-party request.
		if ( '' !== $picture ) {
			return sprintf(
				'<img src="%s" alt="%s" loading="lazy" decoding="async" />',
				esc_url( $picture ),
				esc_attr__( 'Waar je ons vindt', 'pfh-widgets' )
			);
		}

		if ( ! $this->switched_on( 'showMap', true ) ) {
			return '';
		}

		$query = trim( (string) $this->setting( 'mapQuery', '' ) );

		if ( '' === $query ) {
			$query = trim( (string) $this->setting( 'address', '' ) );
		}

		if ( '' === $query ) {
			return '';
		}

		/**
		 * Filter the map embed, for a shop that would rather not load Google
		 * on its contact page.
		 *
		 * @param string $embed Markup, or '' for none.
		 * @param string $query The place being shown.
		 */
		$embed = (string) apply_filters( 'pfh_contact_map', '', $query );

		if ( '' !== $embed ) {
			return $embed;
		}

		return sprintf(
			'<iframe src="https://www.google.com/maps?q=%s&output=embed" title="%s" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>',
			rawurlencode( preg_replace( '/\s+/', ' ', $query ) ),
			esc_attr__( 'Waar je ons vindt', 'pfh-widgets' )
		);
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-ct-max'       => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1140 ) ),
				'--pfh-ct-pt-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 72 ) ),
				'--pfh-ct-pb-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 72 ) ),
				'--pfh-ct-radius'    => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 16 ) ),
				'--pfh-ct-title-set' => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 40 ) ),
				'--pfh-ct-map-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'mapHeight', 380 ) ),
				'--pfh-ct-ink'       => PFH_Widgets_Helpers::color( $this->setting( 'ink' ), '#22301c' ),
				'--pfh-ct-body'      => PFH_Widgets_Helpers::color( $this->setting( 'bodyInk' ), '#5c6657' ),
				'--pfh-ct-muted'     => PFH_Widgets_Helpers::color( $this->setting( 'mutedInk' ), '#8d9589' ),
				'--pfh-ct-accent'    => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#2b5f63' ),
				'--pfh-ct-tile'      => PFH_Widgets_Helpers::color( $this->setting( 'tile' ), '#f4f7f4' ),
				'--pfh-ct-line'      => PFH_Widgets_Helpers::color( $this->setting( 'lineColor' ), '#eaeaea' ),
			]
		);
	}
}
