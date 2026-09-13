<?php
/**
 * Standalone HTML preview for the shop header.
 *
 *   php dev/preview-shophead.php > dev/out/shophead.html
 *
 * Renders the real element at desktop and phone widths so the design can be
 * judged before anything is wired into Bricks.
 */
require __DIR__ . '/stubs.php';

$dir = __DIR__ . '/../pfh-bricks-widgets/';

define( 'PFH_WIDGETS_VERSION', 'preview' );
define( 'PFH_WIDGETS_DIR', $dir );
define( 'PFH_WIDGETS_URL', '../../pfh-bricks-widgets/' );

require $dir . 'includes/class-pfh-helpers.php';
require $dir . 'includes/class-pfh-icons.php';
require $dir . 'includes/class-pfh-reviews.php';
require $dir . 'includes/class-pfh-assets.php';
require $dir . 'elements/class-pfh-element-shophead.php';

function pfh_head( array $over = [] ) {
	static $n = 0;

	$n++;

	$e       = new PFH_Element_Shophead( [ 'id' => 'sh' . $n ] );
	$e->name = 'pfh-shophead';
	$e->set_control_groups();
	$e->set_controls();

	$s = [];

	foreach ( $e->controls as $k => $c ) {
		if ( array_key_exists( 'default', $c ) ) {
			$s[ $k ] = $c['default'];
		}
	}

	$s                      = array_merge( $s, $over );
	$e->settings            = $s;
	$e->element['settings'] = $s;

	ob_start();
	$e->render();

	return ob_get_clean();
}

$category = pfh_head(
	[
		'titleSource'  => 'manual',
		'title'        => 'Gia giamas',
		'crumbsManual' => [
			[ 'label' => 'Home', 'link' => [ 'url' => '#' ] ],
			[ 'label' => 'Gia Giamas', 'link' => [] ],
		],
	]
);

$shop = pfh_head(
	[
		'titleSource'  => 'manual',
		'title'        => 'Shop',
		'crumbsManual' => [
			[ 'label' => 'Home', 'link' => [ 'url' => '#' ] ],
			[ 'label' => 'Shop', 'link' => [] ],
		],
	]
);

$base = file_get_contents( $dir . 'assets/css/pfh-base.css' );
$css  = file_get_contents( $dir . 'assets/css/pfh-shop.css' );
?>
<!doctype html>
<meta charset="utf-8">
<title>PFH Shop Header — preview</title>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap">
<style><?php echo $base; // phpcs:ignore ?></style>
<style><?php echo $css; // phpcs:ignore ?></style>
<style>
	* { box-sizing: border-box; }

	body {
		margin: 0;
		padding: 0 0 60px;
		background: #f2f3f0;
		font-family: Outfit, system-ui, sans-serif;
		color: #35402f;
	}

	.lab {
		max-width: 1240px;
		margin: 40px auto 10px;
		padding: 0 20px;
		font-size: 11px;
		font-weight: 600;
		letter-spacing: .12em;
		text-transform: uppercase;
		color: #8a9386;
	}

	.stage {
		background: #fff;
		padding: 0 20px;
	}

	.stage--wide { max-width: 1280px; margin-inline: auto; }

	/* A real 390px viewport, not a squashed desktop one. */
	.phone {
		width: 390px;
		margin: 0 auto;
		background: #fff;
		border: 1px solid #dfe2dc;
		border-radius: 18px;
		overflow: hidden;
	}

	.phone iframe { width: 390px; height: 260px; border: 0; display: block; }

	.rule { max-width: 1240px; margin: 0 auto; border-top: 1px solid rgba(148,175,143,.26); }
</style>

<p class="lab">1 &middot; Category page &mdash; 1240px container, 551px text column</p>
<div class="stage stage--wide">
	<?php echo $category; // phpcs:ignore ?>
	<div class="rule"></div>
</div>

<p class="lab">2 &middot; Shop page &mdash; breadcrumb reads Home / Shop</p>
<div class="stage stage--wide">
	<?php echo $shop; // phpcs:ignore ?>
	<div class="rule"></div>
</div>

<p class="lab">3 &middot; Phone, 390px &mdash; featured image not rendered</p>
<div class="phone">
	<iframe srcdoc="<?php
		echo esc_attr(
			'<!doctype html><meta charset="utf-8">'
			. '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap">'
			. '<style>*{box-sizing:border-box}body{margin:0;padding:0 20px;font-family:Outfit,system-ui,sans-serif;color:#35402f}'
			. $base . $css . '</style>' . $category
		);
	?>"></iframe>
</div>
