<?php
namespace Bricks;

class Element {
	public $element  = [];
	public $settings = [];
	public $controls = [];
	public $control_groups = [];
	public $attributes = [];
	public $category = '';
	public $name = '';

	public function __construct( $element = [] ) {
		$this->element  = $element;
		$this->settings = isset( $element['settings'] ) ? $element['settings'] : [];
	}

	public function set_attribute( $key, $attr, $value = null ) {
		if ( ! isset( $this->attributes[ $key ] ) ) {
			$this->attributes[ $key ] = [];
		}

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
			$out[] = 'id="brxe-' . \esc_attr( $this->element['id'] ) . '"';
			$out[] = 'class="brxe-' . \esc_attr( $this->name ) . ' ' . \esc_attr( implode( ' ', isset( $this->attributes['_root']['class'] ) ? $this->attributes['_root']['class'] : [] ) ) . '"';
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
	public function set_controls() {}
	public function set_control_groups() {}
	public function enqueue_scripts() {}
	public function render() {}
}

class Elements {
	public static function register_element( $file, $name = '', $class = '' ) {}
}
