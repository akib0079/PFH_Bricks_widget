<?php
/**
 * The four widgets on a bare page, at their designed width, with the
 * plugin's own CSS inlined — no theme in the way.
 */
require __DIR__ . '/wp-load.php';

$specs = [
	'pfh-shopdesc' => 'PFH_Element_Shopdesc',
	'pfh-notice'   => 'PFH_Element_Notice',
	'pfh-counter'  => 'PFH_Element_Counter',
	'pfh-faq'      => 'PFH_Element_Faq',
];

$body = '';

foreach ( $specs as $name => $class ) {
	$slug = str_replace( 'pfh-', '', $name );
	require_once PFH_WIDGETS_DIR . 'elements/class-pfh-element-' . $slug . '.php';

	$el       = new $class( [ 'id' => 'p-' . $slug ] );
	$el->name = $name;
	$el->set_control_groups();
	$el->set_controls();

	$s = [];
	foreach ( $el->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) {
			$s[ $k ] = $c['default'];
		}
	}

	$el->settings = $s;

	ob_start();
	$el->render();
	$body .= ob_get_clean();
}

$css = '';
foreach ( [ 'pfh-base.css', 'pfh-shop.css' ] as $f ) {
	$css .= file_get_contents( PFH_WIDGETS_DIR . 'assets/css/' . $f ) . "\n";
}

// The plugin's asset URLs point at the live install; keep them absolute.
header( 'Content-Type: text/html; charset=utf-8' );
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PFH — shop description, notice, counter, FAQ</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap">
<style>
body { margin: 0; background: #fff; }
<?php echo $css; // phpcs:ignore ?>
</style>
</head>
<body>
<?php echo $body; // phpcs:ignore ?>
<script><?php echo file_get_contents( PFH_WIDGETS_DIR . 'assets/js/pfh-shop.js' ); // phpcs:ignore ?></script>
</body>
</html>
