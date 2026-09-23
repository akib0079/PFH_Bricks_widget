<?php
/**
 * Plugin Name: PFH article preview page
 *
 * Renders the real header and article elements together for browser checks.
 */

function pfh_post_preview_element( $class, $name, array $settings = [] ) {
	$element       = new $class( [ 'id' => 'preview-' . $name ] );
	$element->name = $name;
	$element->set_control_groups();
	$element->set_controls();

	$defaults = [];

	foreach ( $element->controls as $key => $control ) {
		if ( array_key_exists( 'default', $control ) ) {
			$defaults[ $key ] = $control['default'];
		}
	}

	$element->settings = array_merge( $defaults, $settings );

	ob_start();
	$element->render();

	return (string) ob_get_clean();
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_page( 'post-test' ) ) {
			return;
		}

		require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-header.php';
		require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-post.php';
		PFH_Widgets_Assets::header();
		PFH_Widgets_Assets::post();
	}
);

add_action(
	'wp_head',
	function () {
		if ( ! is_page( 'post-test' ) ) {
			return;
		}

		echo '<style>'
			. 'html body .wp-site-blocks{padding:0}'
			. 'html body .wp-site-blocks>main,html body main.wp-block-group,html body .entry-content,html body .wp-block-post-content{width:100%!important;max-width:none!important;margin:0!important;padding:0!important}'
			. 'body .wp-block-post-title{display:none}'
			. 'body #pfh-consent,body .pfh-consent{display:none!important}'
			. '</style>';
	}
);

add_shortcode(
	'pfh_post_preview',
	function ( $attributes ) {
		$attributes = shortcode_atts( [ 'article' => 0 ], $attributes, 'pfh_post_preview' );
		$article_id = absint( $attributes['article'] );
		require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-header.php';
		require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-post.php';

		if ( ! class_exists( 'PFH_Element_Header' ) || ! class_exists( 'PFH_Element_Post' ) ) {
			return '<p>Preview elements are unavailable.</p>';
		}

		return pfh_post_preview_element( 'PFH_Element_Header', 'pfh-header' )
			. pfh_post_preview_element( 'PFH_Element_Post', 'pfh-post', [ 'previewId' => (string) $article_id ] );
	}
);
