<?php
require __DIR__ . '/wp-load.php';

if ( ! function_exists( 'wc_get_product' ) ) {
	exit( "WooCommerce not loaded\n" );
}

/** Create or fetch a product_cat. */
function pfh_cat( $name, $slug, $parent = 0 ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );

	if ( $term ) {
		return (int) $term->term_id;
	}

	$made = wp_insert_term( $name, 'product_cat', [ 'slug' => $slug, 'parent' => $parent ] );

	return is_wp_error( $made ) ? 0 : (int) $made['term_id'];
}

/** Create or fetch an attribute term. */
function pfh_attr_term( $taxonomy, $name ) {
	$slug = sanitize_title( $name );
	$term = get_term_by( 'slug', $slug, $taxonomy );

	if ( $term ) {
		return $slug;
	}

	wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );

	return $slug;
}

/* ---- categories, mirroring the live structure ---- */

$gia   = pfh_cat( 'Gia giamas', 'gia-giamas' );
$trad  = pfh_cat( 'Traditioneel', 'traditioneel', $gia );
$prem  = pfh_cat( 'Premium 2.0', 'premium-2-0', $gia );
$acc   = pfh_cat( 'Accessoires', 'accessoires', $gia );
$bund  = pfh_cat( 'Bundels', 'bundels', $gia );
$honing = pfh_cat( 'Honing', 'honing' );
$olie   = pfh_cat( 'Olijfolie', 'olijfolie' );

printf(
	"categories: gia=%d trad=%d prem=%d acc=%d bund=%d honing=%d olijfolie=%d\n",
	$gia, $trad, $prem, $acc, $bund, $honing, $olie
);

/* ---- attribute terms ---- */

foreach ( [ 'Gia Giamas', 'Yiayia Marika', 'Durable' ] as $v ) {
	pfh_attr_term( 'pa_merk', $v );
}

foreach ( [ 'Geel', 'Oranje', 'Rood', 'Blauw' ] as $v ) {
	pfh_attr_term( 'pa_kleur', $v );
}

foreach ( [ '450 gram', '580 ml', '750 ml', '1 liter' ] as $v ) {
	pfh_attr_term( 'pa_gewicht', $v );
}

echo "attribute terms seeded\n";

/* ---- products ---- */

$rows = [
	// name, slug, cats, price, sale, stock, merk, kleur, gewicht
	[ 'Gia Giamas bundle 450ml',        'gia-giamas-bundle-450ml',     [ $bund ],  46.97, 41.97, true,  'Gia Giamas',    'Geel',   '450 gram' ],
	[ 'Gia giamas special jar 580ml',   'gia-giamas-special-jar-580',  [ $trad ],   2.99, null,  true,  'Gia Giamas',    'Blauw',  '580 ml' ],
	[ 'Gia Giamas starterspakket',      'gia-giamas-starterspakket',   [ $bund ],  32.45, 31.50, true,  'Gia Giamas',    'Oranje', '1 liter' ],
	[ 'Gia Giamas Pomp',                'gia-giamas-pomp',             [ $acc ],    6.50, null,  true,  'Durable',       'Blauw',  '450 gram' ],
	[ 'Gia Giamas citroen 1L',          'gia-giamas-citroen-1l',       [ $trad ],   8.95, null,  true,  'Gia Giamas',    'Geel',   '1 liter' ],
	[ 'Gia Giamas sinaasappel 1L',      'gia-giamas-sinaasappel-1l',   [ $trad ],   8.95, 7.45,  true,  'Gia Giamas',    'Oranje', '1 liter' ],
	[ 'Gia Giamas granaatappel 1L',     'gia-giamas-granaatappel-1l',  [ $trad ],   9.95, null,  true,  'Gia Giamas',    'Rood',   '1 liter' ],
	[ 'Gia Giamas premium 2.0 750ml',   'gia-giamas-premium-750',      [ $prem ],  18.95, null,  true,  'Gia Giamas',    'Geel',   '750 ml' ],
	[ 'Gia Giamas premium perzik',      'gia-giamas-premium-perzik',   [ $prem ],  18.95, 16.50, true,  'Gia Giamas',    'Oranje', '750 ml' ],
	[ 'Gia Giamas premium kers',        'gia-giamas-premium-kers',     [ $prem ],  19.95, null,  false, 'Gia Giamas',    'Rood',   '750 ml' ],
	[ 'Gia Giamas glazen pot',          'gia-giamas-glazen-pot',       [ $acc ],    4.25, null,  true,  'Durable',       'Blauw',  '580 ml' ],
	[ 'Gia Giamas schenkdop',           'gia-giamas-schenkdop',        [ $acc ],    2.50, null,  false, 'Durable',       'Blauw',  '450 gram' ],
	[ 'Rauwe honing 450g',              'rauwe-honing-450g',           [ $honing ],12.50, null,  true,  'Yiayia Marika', 'Geel',   '450 gram' ],
	[ 'Rauwe honing 1kg',               'rauwe-honing-1kg',            [ $honing ],22.50, 19.95, true,  'Yiayia Marika', 'Geel',   '1 liter' ],
	[ 'Thijmhoning 450g',               'thijmhoning-450g',            [ $honing ],14.95, null,  true,  'Yiayia Marika', 'Oranje', '450 gram' ],
	[ 'Pijnboomhoning 450g',            'pijnboomhoning-450g',         [ $honing ],15.95, null,  true,  'Yiayia Marika', 'Rood',   '450 gram' ],
	[ 'Olijfolie extra vierge 500ml',   'olijfolie-extra-vierge-500',  [ $olie ],  11.95, null,  true,  'Yiayia Marika', 'Geel',   '580 ml' ],
	[ 'Olijfolie premium 750ml',        'olijfolie-premium-750',       [ $olie ],  18.95, 16.95, true,  'Yiayia Marika', 'Geel',   '750 ml' ],
	[ 'Olijfolie blik 3L',              'olijfolie-blik-3l',           [ $olie ],  46.50, null,  true,  'Yiayia Marika', 'Oranje', '1 liter' ],
	[ 'Olijfolie proefset',             'olijfolie-proefset',          [ $olie, $bund ], 29.95, 26.50, true, 'Yiayia Marika', 'Geel', '580 ml' ],
	[ 'Combideal honing en olie',       'combideal-honing-olie',       [ $bund ],  41.97, 36.97, true,  'Yiayia Marika', 'Geel',   '1 liter' ],
	[ 'Proefpakket 3 smaken',           'proefpakket-3-smaken',        [ $bund, $trad ], 41.97, 39.50, true, 'Gia Giamas', 'Oranje', '580 ml' ],
	[ 'Gia Giamas limonade mix',        'gia-giamas-limonade-mix',     [ $trad ],   7.95, null,  true,  'Gia Giamas',    'Rood',   '580 ml' ],
	[ 'Gia Giamas cadeaubox',           'gia-giamas-cadeaubox',        [ $bund ],  34.95, null,  true,  'Gia Giamas',    'Blauw',  '750 ml' ],
	[ 'Bijenwas kaars klein',           'bijenwas-kaars-klein',        [ $acc ],    5.95, null,  true,  'Yiayia Marika', 'Geel',   '450 gram' ],
	[ 'Bijenwas kaars groot',           'bijenwas-kaars-groot',        [ $acc ],    9.95, 8.50,  true,  'Yiayia Marika', 'Geel',   '750 ml' ],
	[ 'Gia Giamas premium bundel 2.0',  'gia-giamas-premium-bundel',   [ $prem, $bund ], 52.95, 46.97, true, 'Gia Giamas', 'Geel', '750 ml' ],
	[ 'Gia Giamas premium mango',       'gia-giamas-premium-mango',    [ $prem ],  18.95, null,  true,  'Gia Giamas',    'Oranje', '750 ml' ],
	[ 'Gia Giamas traditioneel duo',    'gia-giamas-traditioneel-duo', [ $trad, $bund ], 16.50, 14.95, true, 'Gia Giamas', 'Geel', '1 liter' ],
	[ 'Honing cadeauset',               'honing-cadeauset',            [ $honing, $bund ], 27.50, null, true, 'Yiayia Marika', 'Rood', '450 gram' ],
	[ 'Olijfolie dispenser',            'olijfolie-dispenser',         [ $acc ],    7.95, null,  true,  'Durable',       'Blauw',  '580 ml' ],
	[ 'Gia Giamas maatbeker',           'gia-giamas-maatbeker',        [ $acc ],    3.95, null,  true,  'Durable',       'Blauw',  '450 gram' ],
];

$made = 0;
$skipped = 0;

foreach ( $rows as $row ) {
	list( $name, $slug, $cats, $price, $sale, $in_stock, $merk, $kleur, $gewicht ) = $row;

	$existing = get_page_by_path( $slug, OBJECT, 'product' );

	if ( $existing ) {
		$skipped++;
		continue;
	}

	$product = new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_slug( $slug );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( (string) $price );

	if ( null !== $sale ) {
		$product->set_sale_price( (string) $sale );
	}

	$product->set_sku( strtoupper( substr( md5( $slug ), 0, 8 ) ) );
	$product->set_stock_status( $in_stock ? 'instock' : 'outofstock' );
	$product->set_category_ids( $cats );
	$product->set_short_description( 'Demo product for filter testing.' );
	$product->set_description( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.' );

	$id = $product->save();

	if ( ! $id ) {
		continue;
	}

	wp_set_object_terms( $id, sanitize_title( $merk ), 'pa_merk' );
	wp_set_object_terms( $id, sanitize_title( $kleur ), 'pa_kleur' );
	wp_set_object_terms( $id, sanitize_title( $gewicht ), 'pa_gewicht' );

	$made++;
}

printf( "products: %d created, %d already there\n", $made, $skipped );

if ( class_exists( 'WC_Install' ) ) {
	wc_delete_product_transients();
	delete_transient( 'wc_products_onsale' );
}

// Stock status lives in product_visibility, and the lookup table drives sorting.
if ( function_exists( 'wc_update_product_lookup_tables' ) ) {
	wc_update_product_lookup_tables();
}

$count = wp_count_posts( 'product' );
echo "published products: " . (int) $count->publish . "\n";
