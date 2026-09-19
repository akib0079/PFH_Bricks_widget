<?php
/**
 * Test stand-in for the Bricks base class, plus a shortcode that renders the
 * archive element. Bricks itself is a paid theme and cannot be downloaded, but
 * the element only needs the base class to exist — the filter engine underneath
 * is plain WordPress and WooCommerce.
 */

namespace Bricks {

	/**
	 * Bricks renders an element's children itself. The stand-in says so
	 * unmistakably, so a test can tell "the element asked Bricks" apart from
	 * "the element drew something of its own".
	 */
	class Frontend {

		public static function render_children( $element ) {
			$children = isset( $element->element['children'] ) ? (array) $element->element['children'] : [];

			return '<div data-bricks-children="' . count( $children ) . '">BRICKS CHILDREN</div>';
		}
	}

	class Element {
		public $element        = [];
		public $settings       = [];
		public $controls       = [];
		public $control_groups = [];
		public $attributes     = [];
		public $category       = '';
		public $name           = '';
		public $icon           = '';
		public $css_selector   = '';

		public function __construct( $element = [] ) {
			$this->element  = $element;
			$this->settings = isset( $element['settings'] ) ? $element['settings'] : [];
		}

		public function set_attribute( $key, $attr, $value = null ) {
			if ( ! isset( $this->attributes[ $key ][ $attr ] ) ) {
				$this->attributes[ $key ][ $attr ] = [];
			}

			foreach ( (array) $value as $single ) {
				$this->attributes[ $key ][ $attr ][] = $single;
			}
		}

		public function render_attributes( $key ) {
			$out = [];

			if ( '_root' === $key ) {
				$out[] = 'id="brxe-' . \esc_attr( isset( $this->element['id'] ) ? $this->element['id'] : 'x' ) . '"';
				$out[] = 'class="brxe-' . \esc_attr( $this->name ) . ' '
					. \esc_attr( implode( ' ', isset( $this->attributes['_root']['class'] ) ? $this->attributes['_root']['class'] : [] ) ) . '"';
			}

			foreach ( ( isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : [] ) as $attr => $values ) {
				if ( 'class' === $attr ) {
					continue;
				}

				$out[] = $attr . '="' . \esc_attr( implode( ' ', $values ) ) . '"';
			}

			return implode( ' ', $out );
		}

		public function get_label() { return ''; }
		public function get_keywords() { return []; }
		public function set_controls() {}
		public function set_control_groups() {}
		public function enqueue_scripts() {}
		public function render() {}
	}

	class Elements {
		public static function register_element( $file, $name = '', $class = '' ) {}
	}
}

namespace {

	function pfh_test_archive_element() {
		if ( ! class_exists( 'PFH_Element_Archive' ) ) {
			require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-archive.php';
		}

		$element       = new \PFH_Element_Archive( [ 'id' => 'archtest' ] );
		$element->name = 'pfh-archive';
		$element->set_control_groups();
		$element->set_controls();

		$settings = [];

		foreach ( $element->controls as $key => $control ) {
			if ( array_key_exists( 'default', $control ) ) {
				$settings[ $key ] = $control['default'];
			}
		}

		$settings['catsSource'] = 'top';
		$settings['filters']    = [
			[ 'source' => 'pa_merk', 'label' => '', 'open' => true, 'limit' => 6 ],
			[ 'source' => 'pa_kleur', 'label' => '', 'open' => false, 'limit' => 3 ],
			[ 'source' => 'pa_gewicht', 'label' => '', 'open' => false, 'limit' => 6 ],
			[ 'source' => 'price', 'label' => '', 'open' => true, 'limit' => 0 ],
			[ 'source' => 'onsale', 'label' => '', 'open' => true, 'limit' => 0 ],
		];

		$element->settings            = $settings;
		$element->element['settings'] = $settings;

		return $element;
	}

	add_shortcode(
		'pfh_archive_test',
		function () {
			$element = pfh_test_archive_element();
			$element->enqueue_scripts();

			ob_start();
			$element->render();

			return ob_get_clean();
		}
	);

	// A nonce the test can fetch without holding a browser session.
	add_action(
		'init',
		function () {
			if ( isset( $_GET['pfh_nonce'] ) && class_exists( 'PFH_Widgets_Archive' ) ) {
				echo wp_create_nonce( \PFH_Widgets_Archive::ACTION );
				exit;
			}
		}
	);
}
