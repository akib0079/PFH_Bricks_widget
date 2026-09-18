<?php
/**
 * A variable product to test the product page against.
 *
 * Two attributes, fourteen variations, one of them reduced and one out of
 * stock, three images, a tag and three highlight lines — enough for the pills,
 * the saving, the disabled state and the gallery to have something real.
 *
 * Its own name and slug on purpose: the demo catalogue already has a
 * 'Gia Giamas starterspakket', and sharing a slug meant this fixture deleted
 * a real demo product every run — which the archive suite then noticed.
 *
 * Whoever includes this owns it: it changes what is in the catalogue, and the
 * archive suite asserts against a known one. test-product.php deletes it again
 * when it is done, which is why those numbers still add up.
 *
 * @return array{id: int, attributes: int[], taxonomies: string[], categories: int[]} What it made, so it can all be undone.
 */

$made = [ 'id' => 0, 'attributes' => [], 'taxonomies' => [], 'categories' => [] ];

/*
 * Exactly one, always fresh. Returning an existing copy would leave whatever
 * an interrupted run left behind, and creating a second gets a -2 slug that
 * the first one then outlives — which is how three of these piled up.
 */
global $wpdb;

foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_name LIKE %s", 'pfh-fixture-starterspakket%' ) ) as $stale ) {
	$old = wc_get_product( $stale );

	if ( $old && $old->is_type( 'variable' ) ) {
		foreach ( $old->get_children() as $child ) {
			wp_delete_post( $child, true );
		}
	}

	foreach ( get_attached_media( '', $stale ) as $attachment ) {
		wp_delete_post( $attachment->ID, true );
	}

	wp_delete_post( $stale, true );
}

// Attributes as real taxonomies, the way a shop actually has them.
$attributes = [
	'soort' => [ 'Traditioneel 1000ml', 'Premium 2.0 750ml' ],
	'smaak' => [ 'Appel & Granaatappel', 'Perzik', 'Citroen', 'Kers', 'Citrus Twist', 'Mandarijn', 'Aardbei & Citroen' ],
];
$terms = [];

foreach ( $attributes as $slug => $values ) {
	$taxonomy = 'pa_' . $slug;
	if ( ! taxonomy_exists( $taxonomy ) ) {
		$attribute_id = wc_create_attribute( [ 'name' => ( 'soort' === $slug ? 'Type' : ucfirst( $slug ) ), 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order' ] );

		if ( ! is_wp_error( $attribute_id ) ) {
			$made['attributes'][] = (int) $attribute_id;
			$made['taxonomies'][] = $taxonomy;
		}

		register_taxonomy( $taxonomy, 'product', [ 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ] );
	}
	foreach ( $values as $value ) {
		$term = term_exists( $value, $taxonomy ) ?: wp_insert_term( $value, $taxonomy );
		$terms[ $slug ][ $value ] = get_term( is_array( $term ) ? $term['term_id'] : $term, $taxonomy )->slug;
	}
}

$product = new WC_Product_Variable();
$product->set_name( 'PFH...Fixture Starterspakket' );
$product->set_slug( 'pfh-fixture-starterspakket' );
$product->set_status( 'publish' );
$product->set_short_description( 'Het complete starterspakket voor thuis gemaakt van 100% Grieks fruit, doseerpomp en twee speciale glazen.' );
$product->set_description(
	"<h2>Een frisse smaak van Griekenland</h2>\n"
	. "<p>Het pakket neemt je mee naar de zonnige heuvels van Griekenland. Elk flesje vruchtensap is gemaakt van zorgvuldig geselecteerde Griekse vruchten.</p>\n"
	. "<p>Dankzij de unieke formule krijg je tot 33 glazen heerlijke huisgemaakte limonade uit een fles van 1000ml.</p>\n"
	. "<ul><li>Gemaakt van 100% Grieks fruit, zonder kunstmatige toevoegingen</li>"
	. "<li>Glutenvrij en vrij van conserveringsmiddelen</li>"
	. "<li>SKAL bio gecertificeerd</li>"
	. "<li>Geschikt voor stil en bruisend water</li></ul>"
);

$objects = [];
foreach ( $attributes as $slug => $values ) {
	$a = new WC_Product_Attribute();
	$a->set_id( wc_attribute_taxonomy_id_by_name( 'pa_' . $slug ) );
	$a->set_name( 'pa_' . $slug );
	$a->set_options( array_values( $terms[ $slug ] ) );
	$a->set_visible( true );
	$a->set_variation( true );
	$objects[] = $a;
}
$product->set_attributes( $objects );
$product->set_default_attributes( [ 'pa_soort' => $terms['soort']['Traditioneel 1000ml'], 'pa_smaak' => $terms['smaak']['Appel & Granaatappel'] ] );

// Category path: Gia Giamas > Traditionele
$parent = term_exists( 'Gia Giamas', 'product_cat' ) ?: wp_insert_term( 'Gia Giamas', 'product_cat' );
$parent_id = is_array( $parent ) ? $parent['term_id'] : $parent;
$child = term_exists( 'Traditionele', 'product_cat' );

if ( ! $child ) {
	$child = wp_insert_term( 'Traditionele', 'product_cat', [ 'parent' => $parent_id ] );
	$made['categories'][] = (int) ( is_array( $child ) ? $child['term_id'] : $child );
}

$child_id = is_array( $child ) ? $child['term_id'] : $child;
$product->set_category_ids( [ (int) $parent_id, (int) $child_id ] );

$id = $product->save();

/*
 * Images. Drawn here rather than copied from somewhere: deleting an attachment
 * deletes its file, so a fixture that relied on files left by a previous run
 * came back with three broken images the first time it was torn down.
 */
$dir = WP_CONTENT_DIR . '/uploads/pfh-demo';
wp_mkdir_p( $dir );

foreach ( [ 'shot-1.jpg' => [ 214, 230, 205 ], 'shot-2.jpg' => [ 236, 224, 190 ], 'shot-3.jpg' => [ 205, 219, 230 ] ] as $file => $rgb ) {
	if ( file_exists( "$dir/$file" ) || ! function_exists( 'imagecreatetruecolor' ) ) {
		continue;
	}

	$canvas = imagecreatetruecolor( 600, 600 );
	imagefill( $canvas, 0, 0, imagecolorallocate( $canvas, $rgb[0], $rgb[1], $rgb[2] ) );
	imagefilledellipse( $canvas, 300, 320, 260, 320, imagecolorallocate( $canvas, 120, 140, 110 ) );
	imagejpeg( $canvas, "$dir/$file", 70 );
	imagedestroy( $canvas );
}

$shots = [];
foreach ( [ 'shot-1.jpg', 'shot-2.jpg', 'shot-3.jpg' ] as $file ) {
	$mime = 'image/jpeg';
	$path = WP_CONTENT_DIR . '/uploads/pfh-demo/' . $file;
	$att  = wp_insert_attachment( [ 'post_title' => $file, 'post_mime_type' => $mime, 'post_status' => 'inherit' ], $path, $id );
	update_post_meta( $att, '_wp_attached_file', 'pfh-demo/' . $file );
	update_post_meta( $att, '_wp_attachment_image_alt', 'Starterspakket' );
	$shots[] = $att;
}
set_post_thumbnail( $id, $shots[0] );
update_post_meta( $id, '_product_image_gallery', implode( ',', array_slice( $shots, 1 ) ) );

// The two PFH fields.
update_post_meta( $id, PFH_Widgets_Product_Fields::BADGE, 'SALES' );
update_post_meta( $id, PFH_Widgets_Product_Fields::HIGHLIGHTS, [
	[ 'label' => '1x 1000ml Griekse vruchtensap', 'note' => '33 glazen van 467ml' ],
	[ 'label' => '1x Doseerpomp', 'note' => 'Precies 30ml' ],
	[ 'label' => '2x Speciale glazen (467ml)', 'note' => 'Inclusief markering' ],
] );

// The tab fields.
update_post_meta( $id, PFH_Widgets_Product_Fields::INGREDIENTS, '<p><strong>Perzik (voorbeeld smaak):</strong> Vruchtensap uit concentraat van perzik (min. 50%), water, suiker, citroenzuur (E330), natuurlijk aroma.</p><p>De exacte ingredientenlijst verschilt per smaak.</p>' );
update_post_meta( $id, PFH_Widgets_Product_Fields::ALLERGENS, 'Dit product bevat geen van de 14 grote allergenen. Geproduceerd in een faciliteit die ook andere vruchtenproducten verwerkt.' );
update_post_meta( $id, PFH_Widgets_Product_Fields::FREE_FROM, [
	[ 'label' => 'Glutenvrij' ],
	[ 'label' => 'Conserveringsmiddelenvrij' ],
	[ 'label' => 'Kunstmatige kleurstoffenvrij' ],
	[ 'label' => 'Lactosevrij' ],
	[ 'label' => 'Kunstmatige zoetstoffenvrij' ],
	[ 'label' => 'Notenvrij' ],
] );

update_post_meta( $id, PFH_Widgets_Product_Fields::STORAGE_TITLE, 'Bewaring & houdbaarheid' );
update_post_meta( $id, PFH_Widgets_Product_Fields::STORAGE, [
	[ 'icon' => '', 'heading' => 'Bewaring & houdbaarheid', 'text' => 'Bewaar op kamertemperatuur, droog en uit direct zonlicht. Gemiddeld 12-18 maanden houdbaar.' ],
	[ 'icon' => '', 'heading' => 'Na openen', 'text' => 'Na opening 30 dagen houdbaar. Bewaar gesloten op kamertemperatuur, niet in de koelkast.' ],
	[ 'icon' => '', 'heading' => 'Licht & temperatuur', 'text' => 'Vermijd direct zonlicht en temperaturen boven 25°C.' ],
	[ 'icon' => '', 'heading' => 'Invriezen', 'text' => 'Het concentraat is niet geschikt om in te vriezen.' ],
] );

update_post_meta( $id, PFH_Widgets_Product_Fields::NUTRITION_TITLE, 'Voedingswaarden' );
update_post_meta( $id, PFH_Widgets_Product_Fields::NUTRITION_INTRO, 'Per 100ml bereide drank (1 glas, Classic smaak Perzik — voorbeeld)' );
update_post_meta( $id, PFH_Widgets_Product_Fields::NUTRITION, [
	[ 'c1' => 'Energie', 'c2' => '38 kcal / 158 kJ', 'c3' => '76 kcal / 316 kJ' ],
	[ 'c1' => 'Vetten', 'c2' => '0,1 g', 'c3' => '18,4 g' ],
	[ 'c1' => '— waarvan verzadigd', 'c2' => '0,1 g', 'c3' => '18,4 g' ],
	[ 'c1' => 'Koolhydraten', 'c2' => '0,1 g', 'c3' => '18,4 g' ],
	[ 'c1' => '— waarvan suikers', 'c2' => '0,1 g', 'c3' => '18,4 g' ],
] );

// Variations: every type x a few flavours, one of them reduced.
$built = 0;
foreach ( $terms['soort'] as $type_name => $type_slug ) {
	foreach ( $terms['smaak'] as $smaak_name => $smaak_slug ) {
		$v = new WC_Product_Variation();
		$v->set_parent_id( $id );
		$v->set_attributes( [ 'pa_soort' => $type_slug, 'pa_smaak' => $smaak_slug ] );
		$v->set_regular_price( 'Traditioneel 1000ml' === $type_name ? '99.96' : '89.96' );
		if ( 'Appel & Granaatappel' === $smaak_name ) { $v->set_sale_price( 'Traditioneel 1000ml' === $type_name ? '85.96' : '79.96' ); }
		if ( 'Kers' === $smaak_name ) { $v->set_stock_status( 'outofstock' ); }
		$v->set_status( 'publish' );
		$v->save();
		$built++;
	}
}

WC_Product_Variable::sync( $id );
wc_delete_product_transients( $id );


$made['id'] = (int) $id;

return $made;
