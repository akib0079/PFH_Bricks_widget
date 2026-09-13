<?php
/**
 * Render smoke test for the shop template elements.
 *
 *   php dev/test-shop.php
 *
 * The audit only calls set_controls(); this actually renders each element with
 * its defaults, which is what catches a typo in a render path.
 */

require __DIR__ . '/stubs.php';

$dir = __DIR__ . '/../pfh-bricks-widgets/';

define( 'PFH_WIDGETS_VERSION', 'test' );
define( 'PFH_WIDGETS_FILE', $dir . 'pfh-bricks-widgets.php' );
define( 'PFH_WIDGETS_DIR', $dir );
define( 'PFH_WIDGETS_URL', './' );

require $dir . 'includes/class-pfh-helpers.php';
require $dir . 'includes/class-pfh-icons.php';
require $dir . 'includes/class-pfh-reviews.php';
require $dir . 'includes/class-pfh-assets.php';
require $dir . 'includes/class-pfh-archive.php';
require $dir . 'includes/trait-pfh-design-revision.php';
require $dir . 'includes/trait-pfh-product-card.php';

$pass = 0;
$fail = 0;

function ok( $label, $condition, $detail = '' ) {
	global $pass, $fail;

	if ( $condition ) {
		$pass++;
		echo "  \033[32m✓\033[0m {$label}\n";
	} else {
		$fail++;
		echo "  \033[31m✗\033[0m {$label}" . ( $detail ? "\n      {$detail}\n" : "\n" );
	}
}

$elements = [
	'PFH_Element_Shophead' => 'class-pfh-element-shophead.php',
	'PFH_Element_Shopdesc' => 'class-pfh-element-shopdesc.php',
	'PFH_Element_Notice'   => 'class-pfh-element-notice.php',
	'PFH_Element_Counter'  => 'class-pfh-element-counter.php',
	'PFH_Element_Faq'      => 'class-pfh-element-faq.php',
	'PFH_Element_Archive'  => 'class-pfh-element-archive.php',
];

echo "\n\033[1mShop template elements\033[0m\n";

$html = [];

foreach ( $elements as $class => $file ) {
	require_once $dir . 'elements/' . $file;

	$element       = new $class( [ 'id' => strtolower( $class ) ] );
	$element->name = 'x';
	$element->set_control_groups();
	$element->set_controls();

	$settings = [];

	foreach ( $element->controls as $key => $control ) {
		if ( array_key_exists( 'default', $control ) ) {
			$settings[ $key ] = $control['default'];
		}
	}

	$element->settings            = $settings;
	$element->element['settings'] = $settings;

	$error = '';

	ob_start();

	try {
		$element->render();
	} catch ( Throwable $e ) {
		$error = get_class( $e ) . ': ' . $e->getMessage();
	}

	$out = ob_get_clean();
	$html[ $class ] = $out;

	ok( $class . ' renders without throwing', '' === $error, $error );
}

echo "\n\033[1mMarkup\033[0m\n";

ok( 'header prints a breadcrumb trail', false !== strpos( $html['PFH_Element_Shophead'], 'pfh-shophead__crumbs' ) );
ok( 'header falls back to the bundled transparent banner', false !== strpos( $html['PFH_Element_Shophead'], 'pfh-shop-banner.png' ) );
ok( 'header title uses the chosen tag', false !== strpos( $html['PFH_Element_Shophead'], '<h1 class="pfh-shophead__title"' ) );

ok( 'notice renders its button', false !== strpos( $html['PFH_Element_Notice'], 'pfh-notice__btn' ) );
ok( 'notice band carries the copy', false !== strpos( $html['PFH_Element_Notice'], 'Inspiratie nodig?' ) );

ok( 'counter renders four items', 4 === substr_count( $html['PFH_Element_Counter'], 'pfh-counter__item' ) );
ok( 'counter falls back to the typed score offline', false !== strpos( $html['PFH_Element_Counter'], '9.7/10' ) );
ok( 'counter leaves an unresolved token out of sight', false === strpos( $html['PFH_Element_Counter'], '%count%' ) );

ok( 'faq uses details/summary so it works without JS', false !== strpos( $html['PFH_Element_Faq'], '<details class="pfh-faq__item"' ) );
ok( 'faq opens the first question', false !== strpos( $html['PFH_Element_Faq'], 'pfh-faq__item" open' ) );
ok( 'faq emits FAQ structured data', false !== strpos( $html['PFH_Element_Faq'], '"@type":"FAQPage"' ) );
ok(
	'faq marks up only answered questions',
	1 === substr_count( $html['PFH_Element_Faq'], '"@type":"Question"' ),
	'unanswered questions must stay out of the schema'
);

// Without WooCommerce the archive must bail quietly rather than fatal.
ok( 'counter keeps a ratio label intact', false !== strpos( $html['PFH_Element_Counter'], '/10' ) && false === strpos( $html['PFH_Element_Counter'], '396/10' ) );
ok( 'archive degrades without WooCommerce', '' === trim( $html['PFH_Element_Archive'] ) );

echo "\n" . str_repeat( '-', 56 ) . "\n";
printf( "\033[1m%d passed, %d failed\033[0m\n", $pass, $fail );

exit( $fail ? 1 : 0 );
