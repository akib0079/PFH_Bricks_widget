<?php
/**
 * Just enough of Elementor for test-legacy-pages.php: its front end hooks
 * the_content and swaps whatever a page prints for the old Elementor layout,
 * which is what it does to a page moved over to Bricks.
 */

namespace Elementor;

class Frontend {
	const THE_CONTENT_FILTER_PRIORITY = 9;

	public $removed = 0;

	public function apply_builder_in_content( $content ) {
		return '<div class="elementor elementor-old">old layout</div>';
	}

	public function add_content_filter() {
		add_filter( 'the_content', [ $this, 'apply_builder_in_content' ], self::THE_CONTENT_FILTER_PRIORITY );
	}

	public function remove_content_filter() {
		$this->removed++;
		remove_filter( 'the_content', [ $this, 'apply_builder_in_content' ], self::THE_CONTENT_FILTER_PRIORITY );
	}
}

class Plugin {
	public static $instance;

	public $frontend;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance           = new self();
			self::$instance->frontend = new Frontend();
		}

		return self::$instance;
	}
}
