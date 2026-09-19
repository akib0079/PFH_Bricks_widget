<?php
require __DIR__ . '/stubs.php';

$dir = '/Users/macm1pro/Bricks Integration widgets/pfh-bricks-widgets/';

define( 'PFH_WIDGETS_VERSION', '1.0.0' );
define( 'PFH_WIDGETS_FILE', $dir . 'pfh-bricks-widgets.php' );
define( 'PFH_WIDGETS_DIR', $dir );
define( 'PFH_WIDGETS_URL', './' );

require $dir . 'includes/class-pfh-helpers.php';
require $dir . 'includes/class-pfh-icons.php';
require $dir . 'includes/class-pfh-cart.php';
require $dir . 'includes/class-pfh-ajax.php';
require $dir . 'includes/class-pfh-reviews.php';
require $dir . 'includes/class-pfh-assets.php';
require $dir . 'includes/trait-pfh-element-defaults.php';
require $dir . 'includes/trait-pfh-design-revision.php';
require $dir . 'includes/trait-pfh-product-card.php';
require $dir . 'includes/trait-pfh-product-price.php';
require $dir . 'elements/class-pfh-element-header.php';
require $dir . 'elements/class-pfh-element-footer.php';
require $dir . 'elements/class-pfh-element-hero.php';
require $dir . 'elements/class-pfh-element-categories.php';
require $dir . 'elements/class-pfh-element-products.php';
require $dir . 'elements/class-pfh-element-rating.php';
require $dir . 'elements/class-pfh-element-cta.php';
require $dir . 'elements/class-pfh-element-features.php';
require $dir . 'elements/class-pfh-element-reviews.php';
require $dir . 'elements/class-pfh-element-info.php';
require $dir . 'elements/class-pfh-element-featured.php';
require $dir . 'elements/class-pfh-element-featured-olive.php';
require $dir . 'elements/class-pfh-element-product-grid.php';

/**
 * Collect a control's defaults the way the Bricks builder seeds settings.
 */
function pfh_defaults( $element ) {
	$element->set_control_groups();
	$element->set_controls();

	$settings = [];

	foreach ( $element->controls as $key => $control ) {
		if ( ! array_key_exists( 'default', $control ) ) {
			continue;
		}

		$settings[ $key ] = $control['default'];

		// Repeaters: seed each row with its own field defaults, like the builder does.
		if ( 'repeater' === $control['type'] && is_array( $control['default'] ) ) {
			foreach ( $control['default'] as $i => $row ) {
				foreach ( $control['fields'] as $field_key => $field ) {
					if ( ! isset( $row[ $field_key ] ) && array_key_exists( 'default', $field ) ) {
						$settings[ $key ][ $i ][ $field_key ] = $field['default'];
					}
				}
			}
		}
	}

	return $settings;
}

// Mega cards need Woo; feed the header custom cards so the panel has content.
$header = new PFH_Element_Header( [ 'id' => 'hdr001' ] );
$header->name = 'pfh-header';
$settings = pfh_defaults( $header );

$cards = [
	[ 'Gia...Giamas bundle 450ml', '#', '/img/card1.svg' ],
	[ 'Gia...Giamas bundle 1L', '#', '/img/card2.svg' ],
	[ 'Gia...Giamas bundle 450ml', '#', '/img/card3.svg' ],
	[ 'Combo deal best olive oil and raw pine honey', '#', '/img/card4.svg' ],
	[ 'Combo deal best olive oil and raw pine honey', '#', '/img/card5.svg' ],
];
$custom = implode( "\n", array_map( function ( $c ) { return implode( ' | ', $c ); }, $cards ) );

foreach ( $settings['navItems'] as $i => $row ) {
	if ( ! empty( $row['hasMega'] ) ) {
		$settings['navItems'][ $i ]['megaSource'] = 'custom';
		$settings['navItems'][ $i ]['megaCustom'] = $custom;
	}
}

$header->settings = $settings;
$header->element['settings'] = $settings;

// ---- Category slider ------------------------------------------------------
$cats = new PFH_Element_Categories( [ 'id' => 'cat001' ] );
$cats->name = 'pfh-categories';
$cs = pfh_defaults( $cats );
foreach ( $cs['slides'] as $i => $row ) {
	$cs['slides'][ $i ]['image'] = [ 'url' => '/img/catj' . ( $i + 1 ) . '.jpg' ];
	$cs['slides'][ $i ]['link']  = [ 'type' => 'external', 'url' => '#' ];
}
$cats->settings = $cs;
$cats->element['settings'] = $cs;

ob_start();
$cats->render();
$cats_html = ob_get_clean();

// ---- Product slider -------------------------------------------------------
$prod = new PFH_Element_Products( [ 'id' => 'prd001' ] );
$prod->name = 'pfh-products';
$ps = pfh_defaults( $prod );
// No WooCommerce in the harness, so exercise the manual-card path.
$ps['source'] = 'manual';
$demo = [
	[ 'Gia…Giamas bundle 450ml', '/img/card1.svg' ],
	[ 'Gia…Giamas bundle 1L', '/img/card2.svg' ],
	[ 'Gia…Giamas bundle 450ml', '/img/card3.svg' ],
	[ 'Combo deal best olive oil and raw pine honey', '/img/card4.svg' ],
	[ 'Combo deal best olive oil and raw pine honey', '/img/card5.svg' ],
];
$ps['manualCards'] = [];
foreach ( $demo as $i => $row ) {
	$ps['manualCards'][] = [
		'title'    => $row[0],
		'image'    => [ 'url' => $row[1] ],
		'price'    => '€ 41.97 incl. VAT',
		'oldPrice' => '€ 46.97 incl. VAT',
		'link'     => [ 'type' => 'external', 'url' => '#' ],
		'onSale'   => $i !== 3,
	];
}
$ps['badgeMode'] = 'onsale';
$prod->settings = $ps;
$prod->element['settings'] = $ps;

ob_start();
$prod->render();
$prod_html = ob_get_clean();

// ---- Featured section -----------------------------------------------------
$feat = new PFH_Element_Featured( [ 'id' => 'fea001' ] );
$feat->name = 'pfh-featured';
$fe = pfh_defaults( $feat );
$fe['bgImage'] = [ 'url' => '/img/honeybg.jpg' ];
$fe['image']   = [ 'url' => '/img/honeyjar.png' ];
$fe['mqIcon']  = [ 'url' => '/img/bee.svg' ];
$fe['btnIcon'] = [ 'url' => '/img/arrow-ne.svg' ];
$feat->settings = $fe;
$feat->element['settings'] = $fe;

ob_start();
$feat->render();
$feat_html = ob_get_clean();

// ---- Product grid ---------------------------------------------------------
$grid = new PFH_Element_Product_Grid( [ 'id' => 'grd001' ] );
$grid->name = 'pfh-product-grid';
$gs = pfh_defaults( $grid );
$gs['source'] = 'manual';
$gs['manualCards'] = [];
foreach ( [ 'card1', 'card2', 'card3', 'card4' ] as $i => $img ) {
	$gs['manualCards'][] = [
		'title'    => 'Combo deal best olive oil and raw pine honey',
		'image'    => [ 'url' => '/img/' . $img . '.svg' ],
		'price'    => '€ 41.97 incl. VAT',
		'oldPrice' => '€ 46.97 incl. VAT',
		'link'     => [ 'type' => 'external', 'url' => '#' ],
		'onSale'   => false,
	];
}
$grid->settings = $gs;
$grid->element['settings'] = $gs;
ob_start(); $grid->render(); $grid_html = ob_get_clean();

// ---- Featured section, olive ----------------------------------------------
$olive = new PFH_Element_Featured_Olive( [ 'id' => 'olv001' ] );
$olive->name = 'pfh-featured-olive';
$os = pfh_defaults( $olive );
$os['image']      = [ 'url' => '/img/olivejar.png' ];
$os['decorImage'] = [ 'url' => '/img/oliveleaf.png' ];
$os['mqIcon']     = [ 'url' => '/img/leaf.svg' ];
$os['btnIcon']    = [ 'url' => '/img/arrow-ne-green.svg' ];
$olive->settings = $os;
$olive->element['settings'] = $os;
ob_start(); $olive->render(); $olive_html = ob_get_clean();

// ---- Info section ---------------------------------------------------------
$info = new PFH_Element_Info( [ 'id' => 'inf001' ] );
$info->name = 'pfh-info';
$is = pfh_defaults( $info );
$is['bgImage'] = [ 'url' => '/img/infobg.jpg' ];
$is['rows'][0]['image'] = [ 'url' => '/img/info1.jpg' ];
$is['rows'][1]['image'] = [ 'url' => '/img/info2.jpg' ];
foreach ( $is['rows'] as $i => $row ) {
	$is['rows'][ $i ]['link'] = [ 'type' => 'external', 'url' => '#' ];
}
$info->settings = $is;
$info->element['settings'] = $is;
ob_start(); $info->render(); $info_html = ob_get_clean();

// ---- Highlighted features -------------------------------------------------
$feats = new PFH_Element_Features( [ 'id' => 'hlf001' ] );
$feats->name = 'pfh-features';
$hs = pfh_defaults( $feats );
$hf_img = [ '/img/hf-card-honey.jpg', '/img/hf-card-lemon.jpg', '/img/hf-oil.jpg', '', '/img/hf-jars.jpg', '/img/hf-card-olive.jpg', '/img/hf-juice.jpg' ];
foreach ( $hf_img as $i => $url ) {
	if ( $url ) {
		$hs['tiles'][ $i ]['image'] = [ 'url' => $url ];
	}
}
$feats->settings = $hs;
$feats->element['settings'] = $hs;
ob_start(); $feats->render(); $feats_html = ob_get_clean();

// ---- Review slider --------------------------------------------------------
// Feed the widget through the documented short-circuit filter, which is what a
// live site's WebwinkelKeur response turns into after normalisation. 16 rows so
// the 2x2 grid pages exactly four times, like the design.
add_filter( 'pfh_webwinkelkeur_pre_reviews', function () {
	$names = [ 'Annette Black', 'Cameron Williamson', 'Darlene Robertson', 'Marvin McKinney',
		'Esther Howard', 'Jenny Wilson', 'Robert Fox', 'Kristin Watson',
		'Devon Lane', 'Jacob Jones', 'Courtney Henry', 'Arlene McCoy',
		'Dianne Russell', 'Theresa Webb', 'Guy Hawkins', 'Floyd Miles' ];
	$cities = [ 'Utrecht', 'Amsterdam', '', 'Rotterdam', 'Delft', 'Breda', 'Eindhoven', 'Haarlem' ];
	$scores = [ 9, 9.5, 8.4, 10, 7, 9, 8, 9.5 ];
	$out = [];

	foreach ( $names as $i => $name ) {
		$out[] = [
			'id'       => (string) ( 100 + $i ),
			'name'     => $name,
			'city'     => $cities[ $i % count( $cities ) ],
			'text'     => 'The quality feels incredibly authentic, from the rich honey to the perfectly balanced juices. Every order feels thoughtfully curated.',
			'date'     => '2026-0' . ( ( $i % 8 ) + 1 ) . '-12 10:00:00',
			'rating10' => $scores[ $i % count( $scores ) ],
			'url'      => '',
		];
	}

	return $out;
} );

$revs = new PFH_Element_Reviews( [ 'id' => 'rev001' ] );
$revs->name = 'pfh-reviews';
$rs = pfh_defaults( $revs );
$rs['shopId']    = '1222432';
$rs['apiCode']   = 'harness';
$rs['logoImage'] = [ 'url' => '/img/wwk-logo.svg' ];
$rs['subLabel']  = 'city';
$revs->settings = $rs;
$revs->element['settings'] = $rs;
ob_start(); $revs->render(); $revs_html = ob_get_clean();

// ---- Rating badge ---------------------------------------------------------
$rate = new PFH_Element_Rating( [ 'id' => 'rat001' ] );
$rate->name = 'pfh-rating';
$rs2 = pfh_defaults( $rate );
$rs2['logo'] = [ 'url' => '/img/wwk-logo.svg' ];
$rate->settings = $rs2;
$rate->element['settings'] = $rs2;
ob_start(); $rate->render(); $rate_html = ob_get_clean();

// ---- Closing CTA (sits directly above the footer and overlaps it) ---------
$cta = new PFH_Element_Cta( [ 'id' => 'cta001' ] );
$cta->name = 'pfh-cta';
$cs2 = pfh_defaults( $cta );
$cs2['bgImage'] = [ 'url' => '/img/cta-bg.jpg' ];
$cs2['image']   = [ 'url' => '/img/cta-juices.png' ];
$cs2['btnLink'] = [ 'type' => 'external', 'url' => '#' ];
$cta->settings = $cs2;
$cta->element['settings'] = $cs2;
ob_start(); $cta->render(); $cta_html = ob_get_clean();

$footer = new PFH_Element_Footer( [ 'id' => 'ftr001' ] );
$footer->name = 'pfh-footer';
$fs = pfh_defaults( $footer );
// Use the real client assets (local copies) so blend modes and sizes are exercised.
$fs['brandLogo']   = [ 'url' => '/img/footer-logo-real.jpg' ];
$fs['reviewsLogo'] = [ 'url' => '/img/webwinkelkeur-real.svg' ];
$fs['socialItems'][0]['image'] = [ 'url' => '/img/fb.svg' ];
$fs['socialItems'][1]['image'] = [ 'url' => '/img/ig.svg' ];
$footer->settings = $fs;
$footer->element['settings'] = $fs;

// ---- Hero -----------------------------------------------------------------
$hero = new PFH_Element_Hero( [ 'id' => 'hro001' ] );
$hero->name = 'pfh-hero';
$hs = pfh_defaults( $hero );

// Point every asset at the local copies so the preview works offline.
$local = [
	'bgImage'     => '/img/herobg.jpg',
	'starsImage'  => '/img/stars.svg',
	'btnArrow'    => '/img/arrow.svg',
	'decor1Image' => '/img/lemon.png',
	'decor2Image' => '/img/branch.png',
];
foreach ( $local as $key => $url ) {
	$hs[ $key ] = [ 'url' => $url ];
}
foreach ( $hs['slides'] as $i => $row ) {
	$hs['slides'][ $i ]['image']       = [ 'url' => '/img/product.png' ];
	$hs['slides'][ $i ]['avatarImage'] = [ 'url' => '/img/avatars.png' ];
}
$hero->settings = $hs;
$hero->element['settings'] = $hs;

ob_start();
$hero->render();
$hero_html = ob_get_clean();

ob_start();
$header->render();
$header_html = ob_get_clean();

ob_start();
$footer->render();
$footer_html = ob_get_clean();

$css_dir = 'css/';
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PFH widgets preview</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-base.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-header.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-hero.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-categories.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-products.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-pgrid.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-info.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-rating.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-cta.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-quickadd.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-features.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-reviews.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-featured.css">
<link rel="stylesheet" href="<?php echo $css_dir; ?>pfh-footer.css">
<style>
	body { margin: 0; background: #fff; font-family: Outfit, sans-serif; }
	.demo-page { min-height: 200px; padding: 60px 24px; text-align: center; color: #6b7280; }
	.demo-page h1 { font-size: 54px; font-weight: 400; color: #14181b; }
</style>
</head>
<body>
<?php echo $header_html; ?>
<?php echo $hero_html; ?>
<?php echo $cats_html; ?>
<?php echo $prod_html; ?>
<?php echo $grid_html; ?>
<?php echo $feat_html; ?>
<?php echo $olive_html; ?>
<?php echo $info_html; ?>
<?php echo $feats_html; ?>
<?php echo $revs_html; ?>
<div style="padding:40px 24px"><?php echo $rate_html; ?></div>
<?php echo $cta_html; ?>
<?php echo $footer_html; ?>
<script>window.pfhWidgets = { ajaxUrl: '/ajax-test.php', nonce: 'harness' };</script>
<script src="js/pfh-header.js"></script>
<script src="js/pfh-hero.js"></script>
<script src="js/pfh-slider.js"></script>
<script src="js/pfh-features.js"></script>
<script src="js/pfh-marquee.js"></script>
<script src="js/pfh-quickadd.js"></script>
<script src="js/pfh-reviews.js"></script>
<script src="js/pfh-footer.js"></script>
</body>
</html>
