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

	// The rest of Bricks' own public properties: an element that declares
	// one of these private dies on load (Bricks 2.4.2 added $uid).
	public $block = null;
	public $label;
	public $keywords;
	public $icon;
	public $control_options;
	public $css_selector;
	public $scripts = [];
	public $post_id = 0;
	public $draggable = true;
	public $deprecated = false;
	public $panel_condition = [];
	public $id;
	public $uid;
	public $tag = 'div';
	public $theme_styles = [];
	public $is_frontend = false;
	public $custom_attributes = true;
	public $nestable = false;
	public $nestable_item;
	public $nestable_children;
	public $nestable_hide = false;
	public $nestable_html = '';
	public $vue_component;
	public $original_query = '';

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
