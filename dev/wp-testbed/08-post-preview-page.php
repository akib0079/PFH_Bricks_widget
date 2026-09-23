<?php
/** Create the browser preview article and page. */
require __DIR__ . '/wp-load.php';

$body = '<p>De Griekse honing uit Samos is een heerlijk zachte honing met een donkere amberkleur. De smaak vertelt het verhaal van het eiland: warm, puur en vol karakter.</p>'
	. '<p>Griekse honing bestaat al heel lang, ook op Samos. De bijen verzamelen nectar van wilde kruiden, bloemen en bomen die in het mediterrane klimaat volop groeien.</p>'
	. '<h2>Honing uit Griekenland</h2><p>Het klimaat, de natuur en de kleinschalige productie geven deze honing zijn volle smaak.</p>'
	. '<h2>Waarom honing van Samos bijzonder is</h2><p>Elke oogst weerspiegelt de bloemen en kruiden van het seizoen.</p>'
	. '<h2>Griekse rauwe honing online kopen</h2><p>Kies een product met een heldere herkomst en zo min mogelijk verwerking.</p>';

$article = get_page_by_path( 'griekse-honing-uit-samos', OBJECT, 'post' );
$article_id = wp_insert_post(
	[
		'ID'           => $article ? $article->ID : 0,
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => 'Griekse honing uit Samos',
		'post_name'    => 'griekse-honing-uit-samos',
		'post_content' => $body,
	]
);

$category = term_exists( 'Honing', 'category' );
$category = $category ?: wp_insert_term( 'Honing', 'category' );

if ( ! is_wp_error( $category ) ) {
	wp_set_post_categories( $article_id, [ (int) ( is_array( $category ) ? $category['term_id'] : $category ) ] );
}

$image = get_posts( [ 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids' ] );

if ( $image ) {
	set_post_thumbnail( $article_id, (int) $image[0] );
}

$page = get_page_by_path( 'post-test' );
$page_id = wp_insert_post(
	[
		'ID'           => $page ? $page->ID : 0,
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Article preview',
		'post_name'    => 'post-test',
		'post_content' => '[pfh_post_preview article="' . (int) $article_id . '"]',
	]
);

echo 'page: ' . get_permalink( $page_id ) . "\n";
