<?php
/**
 * Plugin Name: PFH all-widgets test page
 *
 * Renders every registered element on one page with its own control
 * defaults, so styles and fonts can be checked in a browser rather than
 * inferred from the markup.
 */

add_shortcode( 'pfh_all_widgets', function () {
	$plugin = PFH_Widgets_Plugin::instance();

	$ref = new ReflectionClass( $plugin );
	$prop = $ref->getProperty( 'elements' );
	$prop->setAccessible( true );
	$elements = $prop->getValue( $plugin );

	$out = '';

	foreach ( $elements as $name => $spec ) {
		if ( ! file_exists( $spec['file'] ) ) {
			continue;
		}

		require_once $spec['file'];

		if ( ! class_exists( $spec['class'] ) ) {
			continue;
		}

		$el       = new $spec['class']( [ 'id' => 'all-' . str_replace( 'pfh-', '', $name ) ] );
		$el->name = $name;
		$el->set_control_groups();
		$el->set_controls();

		$settings = [];
		foreach ( $el->controls as $key => $control ) {
			if ( array_key_exists( 'default', $control ) ) {
				$settings[ $key ] = $control['default'];
			}
		}

		// The description widget renders nothing without a description, which
		// is correct but leaves it unverifiable — so give it one here.
		if ( 'pfh-shopdesc' === $name ) {
			$settings['body'] = 'Griekse vruchtenconcentraten naar het originele recept van Yiayia Marika uit 1957. Puur natuur, direct uit Griekenland en 100% ambachtelijk bereid in kleine oplages. Elke pot wordt met de hand gevuld en gecontroleerd voordat hij de deur uit gaat.';
			$settings['bodySource'] = 'manual';
			$settings['text'] = $settings['body'];
		}

		$el->settings = $settings;

		if ( method_exists( $el, 'enqueue_scripts' ) ) {
			$el->enqueue_scripts();
		}

		ob_start();
		try {
			$el->render();
		} catch ( Throwable $e ) {
			echo '<p style="color:#b00">render threw: ' . esc_html( $e->getMessage() ) . '</p>';
		}
		$html = ob_get_clean();

		$out .= '<div class="pfh-probe" data-widget="' . esc_attr( $name ) . '">'
			. '<p class="pfh-probe__label">' . esc_html( $name ) . '</p>'
			. $html
			. '</div>';
	}

	return $out;
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( is_page( 'all-widgets' ) ) {
		PFH_Widgets_Assets::base();
	}
} );
