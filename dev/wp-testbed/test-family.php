<?php
/**
 * Product families: separate products linked as sizes and flavours, the way
 * bol.com shows them — a row of buttons per choice, each a link to the
 * sibling product.
 *
 * The checks that matter: the right sibling behind every button (the one
 * keeping the other choices, else the closest, in stock first), sizes in
 * the order a shopper reads them, nothing offered that cannot be reached,
 * and a screen whose save keeps every product in one family at most.
 */
require __DIR__ . '/wp-load.php';
require_once WP_PLUGIN_DIR . '/pfh-bricks-widgets/elements/class-pfh-element-product.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

wp_set_current_user( 1 );

$made  = [];
$terms = [];

function simple( $name, $stock = 'instock', $status = 'publish' ) {
	global $made;
	$p = new WC_Product_Simple();
	$p->set_name( $name );
	$p->set_status( $status );
	$p->set_regular_price( '10' );
	$p->set_stock_status( $stock );
	$id     = $p->save();
	$made[] = $id;
	return $id;
}

function family( $name ) {
	global $terms;
	$t       = wp_insert_term( $name . ' ' . wp_generate_password( 4, false ), PFH_Widgets_Family::TAX );
	$terms[] = (int) $t['term_id'];
	return (int) $t['term_id'];
}

function pdp( $product_id, array $settings = [] ) {
	$el           = new PFH_Element_Product( [ 'id' => 'fam' ] );
	$el->name     = 'pfh-product';
	$el->settings = array_merge( [ 'previewId' => (string) $product_id ], $settings );
	ob_start();
	$el->render();
	return (string) ob_get_clean();
}

function row( $view, $label ) {
	foreach ( (array) ( $view['rows'] ?? [] ) as $row ) {
		if ( $row['label'] === $label ) {
			return $row;
		}
	}
	return null;
}

function option( $row, $words ) {
	foreach ( (array) ( $row['options'] ?? [] ) as $o ) {
		if ( $o['words'] === $words ) {
			return $o;
		}
	}
	return null;
}

echo "── registration ──\n";
ok( 'the family taxonomy exists for products', taxonomy_exists( PFH_Widgets_Family::TAX ) && in_array( 'product', get_taxonomy( PFH_Widgets_Family::TAX )->object_type, true ) );
$tax = get_taxonomy( PFH_Widgets_Family::TAX );
ok( 'it has a screen but no public pages', $tax->show_ui && ! $tax->public && ! $tax->publicly_queryable && false === $tax->rewrite );
ok( 'managed by whoever manages product categories', 'manage_product_terms' === $tax->cap->manage_terms && 'assign_product_terms' === $tax->cap->assign_terms );
ok( 'not a meta box of its own on the product', false === $tax->meta_box_cb );

echo "\n── sizes in the order a shopper reads them ──\n";
ok( '450 ml before 1 L', [ '450 ml', '1 L' ] === PFH_Widgets_Family::sorted( [ '1 L', '450 ml' ] ) );
ok( '500 ml, 750 ml, blik 5L', [ '500 ml', '750 ml', 'blik 5L' ] === PFH_Widgets_Family::sorted( [ 'blik 5L', '750 ml', '500 ml' ] ) );
ok( 'honey by weight, a squeeze bottle included', [ '55 gr', '270 gr', '470 gr', '500 gr knijpfles', '740 gr', '940 gr' ] === PFH_Widgets_Family::sorted( [ '940 gr', '500 gr knijpfles', '55 gr', '740 gr', '270 gr', '470 gr' ] ) );
ok( '1 kg after 500 gr', [ '500 gr', '1 kg' ] === PFH_Widgets_Family::sorted( [ '1 kg', '500 gr' ] ) );
ok( 'decimals with a comma', [ '0,5 L', '750 ml' ] === PFH_Widgets_Family::sorted( [ '750 ml', '0,5 L' ] ) );
ok( 'flavours alphabetically', [ 'Aardbei & citroen', 'Citroen', 'Kers', 'Perzik' ] === PFH_Widgets_Family::sorted( [ 'Perzik', 'Citroen', 'Kers', 'Aardbei & citroen' ] ) );
ok( 'volumes and weights together fall back to natural order', [ '50 ml', '100 gr' ] === PFH_Widgets_Family::sorted( [ '100 gr', '50 ml' ] ) );
ok( '"7 stuks" is a count', 'count' === ( PFH_Widgets_Family::quantity( '7 stuks' )['kind'] ?? '' ) );
ok( 'a flavour is no quantity', null === PFH_Widgets_Family::quantity( 'Groene appel & granaatappel' ) );
ok( '"1L" is a litre', 1000.0 === ( PFH_Widgets_Family::quantity( '1L' )['amount'] ?? 0 ) );
ok( '"1.000 gr" is a thousand grams', 1000.0 === ( PFH_Widgets_Family::quantity( '1.000 gr' )['amount'] ?? 0 ) );
ok( '"2 liters" and "1 kilogram" are read', 2000.0 === ( PFH_Widgets_Family::quantity( '2 liters' )['amount'] ?? 0 ) && 1000.0 === ( PFH_Widgets_Family::quantity( '1 kilogram' )['amount'] ?? 0 ) );
ok( 'a value with an emoji in front sorts by its words', [ 'Aardbei', mb_chr( 0x1F34B, 'UTF-8' ) . ' Citroen' ] === PFH_Widgets_Family::sorted( [ mb_chr( 0x1F34B, 'UTF-8' ) . ' Citroen', 'Aardbei' ] ) );

echo "\n── the rows of buttons ──\n";
ok( 'typed with commas', [ 'Smaak', 'Inhoud' ] === PFH_Widgets_Family::parse_dims( 'Smaak, Inhoud' ) );
ok( 'or one per line, empty ones dropped', [ 'Smaak', 'Inhoud' ] === PFH_Widgets_Family::parse_dims( "Smaak\n\nInhoud\n" ) );
ok( 'the same row twice is one', [ 'Smaak' ] === PFH_Widgets_Family::parse_dims( 'Smaak, smaak' ) );
ok( 'three at most', 3 === count( PFH_Widgets_Family::parse_dims( 'A, B, C, D' ) ) );

echo "\n── a family of two flavours in two sizes ──\n";
$c450 = simple( 'Test citroen 450ml' );
$c1l  = simple( 'Test citroen 1L' );
$a450 = simple( 'Test aardbei 450ml' );
$a1l  = simple( 'Test aardbei 1L' );
$gg   = family( 'Test Gia Giamas' );

$members = PFH_Widgets_Family::apply(
	$gg,
	[ 'Smaak', 'Inhoud' ],
	[
		[ 'product' => $c450, 'values' => [ 'Citroen', '450 ml' ] ],
		[ 'product' => $c1l, 'values' => [ 'Citroen', '1 L' ] ],
		[ 'product' => $a450, 'values' => [ 'Aardbei & citroen', '450 ml' ] ],
		[ 'product' => $a1l, 'values' => [ 'Aardbei & citroen', '1L' ] ],
	]
);
ok( 'four members', 4 === count( $members ) );
ok( 'the rows are kept on the family', [ 'Smaak', 'Inhoud' ] === PFH_Widgets_Family::dims( $gg ) );
ok( 'each product knows its values', [ 'Smaak' => 'Citroen', 'Inhoud' => '450 ml' ] === PFH_Widgets_Family::values( $c450 ) );
ok( 'and its family', $gg === PFH_Widgets_Family::family_of( $c1l ) );

$v     = PFH_Widgets_Family::view( $c450 );
$smaak = row( $v, 'Smaak' );
$inh   = row( $v, 'Inhoud' );
ok( 'citroen 450 ml shows a Smaak and an Inhoud row', $smaak && $inh, wp_json_encode( $v ) );
ok( 'Smaak names what is chosen', 'Citroen' === ( $smaak['current'] ?? '' ) );
ok( '"1L" and "1 L" are one button', 2 === count( $inh['options'] ?? [] ) );
ok( 'sizes small to large', [ '450 ml', '1 L' ] === array_column( $inh['options'] ?? [], 'words' ), wp_json_encode( array_column( $inh['options'] ?? [], 'words' ) ) );
ok( 'its own size is marked, not linked', ( option( $inh, '450 ml' )['current'] ?? false ) && '' === ( option( $inh, '450 ml' )['url'] ?? 'x' ) );
ok( '1 L leads to citroen 1 L', $c1l === ( option( $inh, '1 L' )['id'] ?? 0 ) );
ok( '  as an exact match', true === ( option( $inh, '1 L' )['exact'] ?? false ) );
ok( '  by its own address', get_permalink( $c1l ) === ( option( $inh, '1 L' )['url'] ?? '' ) );
ok( 'Aardbei keeps the size: aardbei 450 ml', $a450 === ( option( $smaak, 'Aardbei & citroen' )['id'] ?? 0 ) );

$v = PFH_Widgets_Family::view( $a1l );
ok( 'from aardbei 1 L, Citroen keeps 1 L', $c1l === ( option( row( $v, 'Smaak' ), 'Citroen' )['id'] ?? 0 ) );

echo "\n── a combination the shop does not make ──\n";
wp_trash_post( $a1l );
PFH_Widgets_Family::apply( $gg, [ 'Smaak', 'Inhoud' ], [
	[ 'product' => $c450, 'values' => [ 'Citroen', '450 ml' ] ],
	[ 'product' => $c1l, 'values' => [ 'Citroen', '1 L' ] ],
	[ 'product' => $a450, 'values' => [ 'Aardbei & citroen', '450 ml' ] ],
	[ 'product' => $a1l, 'values' => [ 'Aardbei & citroen', '1 L' ] ],
] );
$v = PFH_Widgets_Family::view( $c1l );
$a = option( row( $v, 'Smaak' ), 'Aardbei & citroen' );
ok( 'a binned member is not offered: Aardbei leads to the 450 ml', $a450 === ( $a['id'] ?? 0 ), wp_json_encode( $a ) );
ok( '  and is marked as changing the size too', false === ( $a['exact'] ?? true ) );
$html = pdp( $c1l );
ok( '  drawn dashed', (bool) preg_match( '/<a class="[^"]*pfh-pdp__pill--link[^"]*is-other[^"]*" href="[^"]*"/', $html ) );
wp_untrash_post( $a1l );
wp_publish_post( $a1l );

echo "\n── in stock first ──\n";
$k1 = simple( 'Test kers 450ml A', 'outofstock' );
$k2 = simple( 'Test kers 450ml B' );
$k3 = simple( 'Test kers 1L', 'outofstock' );
$kf = family( 'Test kers' );
PFH_Widgets_Family::apply( $kf, [ 'Inhoud' ], [
	[ 'product' => $k3, 'values' => [ '1 L' ] ],
	[ 'product' => $k1, 'values' => [ '450 ml' ] ],
	[ 'product' => $k2, 'values' => [ '450 ml' ] ],
] );
$o = option( row( PFH_Widgets_Family::view( $k3 ), 'Inhoud' ), '450 ml' );
ok( 'of two equal siblings the one in stock is chosen', $k2 === ( $o['id'] ?? 0 ) );
$o = option( row( PFH_Widgets_Family::view( $k2 ), 'Inhoud' ), '1 L' );
ok( 'a sold-out sibling is still offered', $k3 === ( $o['id'] ?? 0 ) && false === ( $o['in_stock'] ?? true ) );
ok( '  greyed and struck through', false !== strpos( pdp( $k2 ), 'is-soldout' ) );

echo "\n── what is not offered ──\n";
$d1 = simple( 'Test draft 750ml', 'instock', 'draft' );
$h1 = simple( 'Test hidden 500ml' );
wc_get_product( $h1 )->set_catalog_visibility( 'hidden' );
$hp = wc_get_product( $h1 );
$hp->set_catalog_visibility( 'hidden' );
$hp->save();
PFH_Widgets_Family::apply( $kf, [ 'Inhoud' ], [
	[ 'product' => $k3, 'values' => [ '1 L' ] ],
	[ 'product' => $k1, 'values' => [ '450 ml' ] ],
	[ 'product' => $k2, 'values' => [ '450 ml' ] ],
	[ 'product' => $d1, 'values' => [ '750 ml' ] ],
	[ 'product' => $h1, 'values' => [ '500 ml' ] ],
] );
$words = array_column( row( PFH_Widgets_Family::view( $k2 ), 'Inhoud' )['options'] ?? [], 'words' );
ok( 'neither a draft nor a product hidden from the shop', [ '450 ml', '1 L' ] === $words, wp_json_encode( $words ) );
ok( 'but both are on the family\'s screen', 5 === count( PFH_Widgets_Family::members( $kf, true ) ) );
$words = array_column( row( PFH_Widgets_Family::view( $h1 ), 'Inhoud' )['options'] ?? [], 'words' );
ok( 'a hidden product\'s own page still leads to its siblings', [ '450 ml', '500 ml', '1 L' ] === $words, wp_json_encode( $words ) );
$sp = wc_get_product( $h1 );
$sp->set_catalog_visibility( 'search' );
$sp->save();
$words = array_column( row( PFH_Widgets_Family::view( $k2 ), 'Inhoud' )['options'] ?? [], 'words' );
ok( 'a product shown in search only is offered', [ '450 ml', '500 ml', '1 L' ] === $words, wp_json_encode( $words ) );

$solo = simple( 'Test solo' );
$sf   = family( 'Test solo' );
PFH_Widgets_Family::apply( $sf, [ 'Inhoud' ], [ [ 'product' => $solo, 'values' => [ '1 L' ] ] ] );
ok( 'a family of one shows nothing', null === PFH_Widgets_Family::view( $solo ) );
ok( 'a product in no family shows nothing', null === PFH_Widgets_Family::view( simple( 'Test loose' ) ) );

$same = family( 'Test one size' );
$s1   = simple( 'Test one size citroen' );
$s2   = simple( 'Test one size kers' );
PFH_Widgets_Family::apply( $same, [ 'Smaak', 'Inhoud' ], [
	[ 'product' => $s1, 'values' => [ 'Citroen', '450 ml' ] ],
	[ 'product' => $s2, 'values' => [ 'Kers', '450 ml' ] ],
] );
$v = PFH_Widgets_Family::view( $s1 );
ok( 'a row with only one value is left out', null !== row( $v, 'Smaak' ) && null === row( $v, 'Inhoud' ) );

echo "\n── the screen's save ──\n";
$moved = family( 'Test moved to' );
PFH_Widgets_Family::apply( $moved, [ 'Smaak' ], [
	[ 'product' => $s2, 'values' => [ 'Kers' ] ],
	[ 'product' => $c450, 'values' => [ 'Citroen' ] ],
] );
ok( 'a product moves to the family it is added to', $moved === PFH_Widgets_Family::family_of( $c450 ) && 1 === count( wp_get_object_terms( $c450, PFH_Widgets_Family::TAX ) ) );
ok( '  with its new values', [ 'Smaak' => 'Citroen' ] === PFH_Widgets_Family::values( $c450 ) );
ok( '  and is gone from the old one', ! isset( PFH_Widgets_Family::members( $gg, true )[ $c450 ] ) );

PFH_Widgets_Family::apply( $moved, [ 'Smaak' ], [ [ 'product' => $s2, 'values' => [ 'Kers' ] ] ] );
ok( 'one left out of the table leaves the family', 0 === PFH_Widgets_Family::family_of( $c450 ) );
ok( '  and keeps no values', [] === PFH_Widgets_Family::values( $c450 ) );

PFH_Widgets_Family::apply( $moved, [ 'Smaak' ], [ [ 'product' => $s2, 'values' => [ 'Kers' ] ], [ 'product' => $s2, 'values' => [ 'Dubbel' ] ], [ 'product' => 999999, 'values' => [ 'x' ] ], [ 'product' => get_option( 'page_on_front' ) ?: 1, 'values' => [ 'y' ] ] ] );
ok( 'a product twice, a missing one or a non-product is ignored', [ $s2 ] === array_keys( PFH_Widgets_Family::members( $moved, true ) ) && [ 'Smaak' => 'Kers' ] === PFH_Widgets_Family::values( $s2 ) );

// Through the form, as the screen posts it.
$form = family( 'Test form' );
$f1   = simple( 'Test form 500 ml' );
$f2   = simple( 'Test form 750 ml' );
$_POST = [
	'pfh_family_nonce'   => wp_create_nonce( PFH_Widgets_Family::NONCE ),
	'pfh_family_table'   => '1',
	'pfh_family_dims'    => 'Inhoud',
	'pfh_family_members' => [
		'0'    => [ 'product' => (string) $f1, 'values' => [ '0' => ' 500 ml ' ] ],
		'1003' => [ 'product' => (string) $f2, 'values' => [ '0' => '750 <b>ml</b>' ] ],
		'1004' => [ 'product' => '', 'values' => [ '0' => '' ] ],
	],
];
PFH_Widgets_Family::save( $form );
ok( 'the form saves the rows and members', [ 'Inhoud' ] === PFH_Widgets_Family::dims( $form ) && 2 === count( PFH_Widgets_Family::members( $form, true ) ) );
ok( '  values trimmed and stripped of markup', [ 'Inhoud' => '750 ml' ] === PFH_Widgets_Family::values( $f2 ) && [ 'Inhoud' => '500 ml' ] === PFH_Widgets_Family::values( $f1 ) );

$_POST['pfh_family_nonce'] = 'wrong';
$_POST['pfh_family_dims']  = 'Iets anders';
PFH_Widgets_Family::save( $form );
ok( 'without a valid nonce nothing changes', [ 'Inhoud' ] === PFH_Widgets_Family::dims( $form ) );

wp_set_current_user( 0 );
$_POST['pfh_family_nonce'] = wp_create_nonce( PFH_Widgets_Family::NONCE );
PFH_Widgets_Family::save( $form );
ok( 'nor for someone who may not edit it', [ 'Inhoud' ] === PFH_Widgets_Family::dims( $form ) );
wp_set_current_user( 1 );

$_POST = [ 'pfh_family_nonce' => wp_create_nonce( PFH_Widgets_Family::NONCE ), 'pfh_family_dims' => 'Smaak, Inhoud' ];
PFH_Widgets_Family::save( $form );
ok( 'the "new family" form sets the rows and leaves the members alone', [ 'Smaak', 'Inhoud' ] === PFH_Widgets_Family::dims( $form ) && 2 === count( PFH_Widgets_Family::members( $form, true ) ) );

/**
 * Post the family's own screen, as drawn with $drawn and saved with $dims.
 */
function post_screen( $term, $drawn, $dims, array $rows ) {
	$_POST = [
		'pfh_family_nonce'       => wp_create_nonce( PFH_Widgets_Family::NONCE ),
		'pfh_family_table'       => '1',
		'pfh_family_dims_before' => wp_json_encode( $drawn ),
		'pfh_family_dims'        => $dims,
		'pfh_family_members'     => $rows,
	];
	PFH_Widgets_Family::save( $term );
	$_POST = [];
}

$ord = family( 'Test order' );
$o1  = simple( 'Test order citroen 450' );
$o2  = simple( 'Test order kers 1L' );
$both = [
	[ 'product' => (string) $o1, 'values' => [ 'Citroen', '450 ml' ] ],
	[ 'product' => (string) $o2, 'values' => [ 'Kers', '1 L' ] ],
];
post_screen( $ord, [], 'Smaak, Inhoud', $both );
ok( 'a first save maps the columns by position', [ 'Smaak' => 'Citroen', 'Inhoud' => '450 ml' ] === PFH_Widgets_Family::values( $o1 ) );

post_screen( $ord, [ 'Smaak', 'Inhoud' ], 'Inhoud, Smaak', $both );
ok( 'swapping the keuzes in the same save keeps every value with its row', [ 'Inhoud' => '450 ml', 'Smaak' => 'Citroen' ] === PFH_Widgets_Family::values( $o1 ), wp_json_encode( PFH_Widgets_Family::values( $o1 ) ) );
ok( '  and the rows in their new order', [ 'Inhoud', 'Smaak' ] === PFH_Widgets_Family::dims( $ord ) );

$drawn_now = [ 'Inhoud', 'Smaak' ];
$cols      = [
	[ 'product' => (string) $o1, 'values' => [ '450 ml', 'Citroen' ] ],
	[ 'product' => (string) $o2, 'values' => [ '1 L', 'Kers' ] ],
];
post_screen( $ord, $drawn_now, 'Maat, Smaak', $cols );
ok( 'a keuze renamed in place keeps its values', [ 'Maat' => '450 ml', 'Smaak' => 'Citroen' ] === PFH_Widgets_Family::values( $o1 ), wp_json_encode( PFH_Widgets_Family::values( $o1 ) ) );

post_screen( $ord, [ 'Maat', 'Smaak' ], 'Smaak', [
	[ 'product' => (string) $o1, 'values' => [ '450 ml', 'Citroen' ] ],
	[ 'product' => (string) $o2, 'values' => [ '1 L', 'Kers' ] ],
] );
ok( 'a keuze removed takes only its own values along', [ 'Smaak' => 'Citroen' ] === PFH_Widgets_Family::values( $o1 ) && [ 'Smaak' => 'Kers' ] === PFH_Widgets_Family::values( $o2 ) );

$deny = static function ( $caps, $cap, $user, $args ) use ( $o2 ) {
	return ( 'edit_post' === $cap && isset( $args[0] ) && (int) $args[0] === $o2 ) ? [ 'do_not_allow' ] : $caps;
};
add_filter( 'map_meta_cap', $deny, 10, 4 );
$o3 = simple( 'Test order perzik' );
post_screen( $ord, [ 'Smaak' ], 'Smaak', [
	[ 'product' => (string) $o2, 'values' => [ 'Kers bewerkt' ] ],
	[ 'product' => (string) $o3, 'values' => [ 'Perzik' ] ],
] );
remove_filter( 'map_meta_cap', $deny, 10 );
ok( 'a product the user may not edit keeps its values', [ 'Smaak' => 'Kers' ] === PFH_Widgets_Family::values( $o2 ), wp_json_encode( PFH_Widgets_Family::values( $o2 ) ) );
ok( '  and one they may edit is written', [ 'Smaak' => 'Perzik' ] === PFH_Widgets_Family::values( $o3 ) && isset( PFH_Widgets_Family::members( $ord, true )[ $o3 ] ) );

add_filter( 'map_meta_cap', $deny, 10, 4 );
post_screen( $ord, [ 'Smaak' ], 'Smaak', [ [ 'product' => (string) $o3, 'values' => [ 'Perzik' ] ] ] );
remove_filter( 'map_meta_cap', $deny, 10 );
ok( '  nor is it dropped when left out of the table', isset( PFH_Widgets_Family::members( $ord, true )[ $o2 ] ) && [ 'Smaak' => 'Kers' ] === PFH_Widgets_Family::values( $o2 ) );

post_screen( $ord, [ 'Smaak' ], 'Smaak', [] );
ok( 'an emptied table empties the family', [] === PFH_Widgets_Family::members( $ord, true ) && [] === PFH_Widgets_Family::values( $o3 ) );

ok( 'remap: kept rows follow their name', [ 0 => 'Inhoud', 1 => 'Smaak' ] === PFH_Widgets_Family::remap( [ 'Inhoud', 'Smaak' ], [ 'Smaak', 'Inhoud' ] ) );
ok( 'remap: a new row added leaves the old columns where they were', [ 0 => 'Smaak' ] === PFH_Widgets_Family::remap( [ 'Smaak' ], [ 'Smaak', 'Inhoud' ] ) );
ok( 'remap: two renamed at once go by position', [ 0 => 'A', 1 => 'B' ] === PFH_Widgets_Family::remap( [ 'X', 'Y' ], [ 'A', 'B' ] ) );
$_POST = [];

echo "\n── deleting a family ──\n";
wp_delete_term( $form, PFH_Widgets_Family::TAX );
ok( 'its members keep no values', [] === PFH_Widgets_Family::values( $f1 ) && 0 === PFH_Widgets_Family::family_of( $f1 ) );

echo "\n── on the product page ──\n";
PFH_Widgets_Family::apply( $gg, [ 'Smaak', 'Inhoud' ], [
	[ 'product' => $c450, 'values' => [ 'Citroen', '450 ml' ] ],
	[ 'product' => $c1l, 'values' => [ 'Citroen', '1 L' ] ],
	[ 'product' => $a450, 'values' => [ 'Aardbei & citroen', '450 ml' ] ],
	[ 'product' => $a1l, 'values' => [ 'Aardbei & citroen', '1 L' ] ],
] );
$html = pdp( $c450 );
ok( 'the rows are drawn', false !== strpos( $html, 'pfh-pdp__family' ) && 2 === substr_count( $html, 'pfh-pdp__family-row' ) );
ok( 'under the price, above the cart', strpos( $html, 'data-pfh-price' ) < strpos( $html, 'pfh-pdp__family' ) && strpos( $html, 'pfh-pdp__family' ) < strpos( $html, 'data-pfh-form' ) );
ok( 'the label names the choice: Smaak — Citroen', false !== strpos( $html, 'Smaak<span class="pfh-pdp__attr-value"> — Citroen</span>' ) );
ok( 'each sibling is a link to its page', false !== strpos( $html, 'href="' . esc_url( get_permalink( $c1l ) ) . '"' ) && false !== strpos( $html, 'href="' . esc_url( get_permalink( $a450 ) ) . '"' ) );
ok( 'the product itself is marked, not linked', 2 === substr_count( $html, 'aria-current="true"' ) && false === strpos( $html, 'href="' . esc_url( get_permalink( $c450 ) ) . '"' ) );
ok( 'each row is a labelled group', false !== strpos( $html, 'role="group" aria-label="Inhoud"' ) );
ok( 'the second row uses the second colour, as the variant pills do', (bool) preg_match( '/pfh-pdp__attr pfh-pdp__family-row pfh-pdp__attr--soft/', $html ) );
ok( 'no variant script hooks on them', ! preg_match( '/pfh-pdp__family[\s\S]*data-pfh-pill=/U', substr( $html, strpos( $html, 'pfh-pdp__family' ), 3000 ) ) );
ok( 'switched off in the panel, nothing', false === strpos( pdp( $c450, [ 'showFamily' => false ] ), 'pfh-pdp__family' ) );
ok( 'photos only when asked for', false === strpos( $html, 'pfh-pdp__pill-photo' ) );
ok( 'not on a product in no family', false === strpos( pdp( $solo ), 'pfh-pdp__family' ) );

$emoji_tax = 'pa_famsmaak';
if ( ! wc_attribute_taxonomy_id_by_name( $emoji_tax ) ) {
	wc_create_attribute( [ 'name' => 'Famsmaak', 'slug' => 'famsmaak', 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ] );
}
register_taxonomy( $emoji_tax, 'product', [ 'hierarchical' => false ] );
$lemon = mb_chr( 0x1F34B, 'UTF-8' );
wp_insert_term( $lemon . ' Citroen', $emoji_tax, [ 'slug' => 'citroen' ] );
$ef = family( 'Test emoji' );
$e1 = simple( 'Test emoji citroen' );
$e2 = simple( 'Test emoji kers' );
PFH_Widgets_Family::apply( $ef, [ 'Famsmaak' ], [
	[ 'product' => $e1, 'values' => [ 'Citroen' ] ],
	[ 'product' => $e2, 'values' => [ 'Kers' ] ],
] );
$o = option( row( PFH_Widgets_Family::view( $e2 ), 'Famsmaak' ), 'Citroen' );
ok( 'a flavour borrows the emoji of the global attribute with the same name', $lemon === ( $o['emoji'] ?? '' ), wp_json_encode( $o ) );
ok( '  drawn apart from its words', false !== strpos( pdp( $e2 ), '<span class="pfh-pdp__pill-emoji" aria-hidden="true">' . $lemon . '</span><span class="pfh-pdp__pill-text">Citroen</span>' ) );

$fake_src = static function () { return [ 'https://example.test/thumb.jpg', 100, 100, false ]; };
add_filter( 'wp_get_attachment_image_src', $fake_src );
add_filter( 'has_post_thumbnail', '__return_true' );
add_filter( 'post_thumbnail_id', static function () { return 1; } );
$html = pdp( $c450, [ 'familyPhotos' => true ] );
remove_all_filters( 'post_thumbnail_id' );
remove_filter( 'has_post_thumbnail', '__return_true' );
remove_filter( 'wp_get_attachment_image_src', $fake_src );
ok( 'with photos asked for, each sibling shows its picture', false !== strpos( $html, '<img class="pfh-pdp__pill-photo" src="https://example.test/thumb.jpg"' ) && false !== strpos( $html, 'has-photo' ) );

$curly = simple( "\u{2019}Quote kers 1L", 'outofstock' );
PFH_Widgets_Family::apply( $kf, [ 'Inhoud' ], [
	[ 'product' => $k2, 'values' => [ '450 ml' ] ],
	[ 'product' => $curly, 'values' => [ '1 L' ] ],
] );
$html = pdp( $k2 );
ok( 'a sold-out sibling whose name starts with a curly quote keeps its whole tooltip', false !== strpos( $html, 'title="' . esc_attr( "\u{2019}Quote kers 1L" ) . ' — uitverkocht"' ), substr( $html, (int) strpos( $html, 'is-soldout' ), 300 ) );

ok( '  and says so to a screen reader', false !== strpos( $html, '<span class="pfh-sr-only"> (' ) && false !== strpos( $html, 'uitverkocht)</span>' ) );

$html = pdp( $c450, [ 'tintedAttrs' => 'smaak' ] );
ok( 'naming rows for the second colour works as for the variant pills', (bool) preg_match( '/pfh-pdp__family-row pfh-pdp__attr--soft"><p class="pfh-pdp__attr-label">Smaak/', $html ) && ! preg_match( '/pfh-pdp__family-row pfh-pdp__attr--soft"><p class="pfh-pdp__attr-label">Inhoud/', $html ) );

echo "\n── the page cache ──\n";
$purged = [];
$catch  = static function ( $id ) use ( &$purged ) { $purged[] = (int) $id; };
add_action( 'litespeed_purge_post', $catch );
PFH_Widgets_Family::apply( $gg, [ 'Smaak', 'Inhoud' ], [
	[ 'product' => $c450, 'values' => [ 'Citroen', '450 ml' ] ],
	[ 'product' => $c1l, 'values' => [ 'Citroen', '1 L' ] ],
	[ 'product' => $a450, 'values' => [ 'Aardbei & citroen', '450 ml' ] ],
] );
ok( 'saving a family clears every member\'s page, the one left out included', ! array_diff( [ $c450, $c1l, $a450, $a1l ], $purged ), wp_json_encode( $purged ) );
$purged = [];
$sold   = wc_get_product( $c1l );
$sold->set_stock_status( 'outofstock' );
$sold->save();
ok( 'a sibling selling out clears the family\'s other pages', in_array( $c450, $purged, true ) && in_array( $a450, $purged, true ), wp_json_encode( $purged ) );
$sold->set_stock_status( 'instock' );
$sold->save();
remove_action( 'litespeed_purge_post', $catch );

echo "\n── what a product page costs ──\n";
/**
 * Queries the buttons take for a family of $n sizes, from a cold cache.
 */
function cost( $n ) {
	$t    = family( 'Test cost ' . $n );
	$rows = [];
	for ( $i = 1; $i <= $n; $i++ ) {
		$rows[] = [ 'product' => simple( "Test cost $n-$i" ), 'values' => [ ( 100 * $i ) . ' ml' ] ];
	}
	PFH_Widgets_Family::apply( $t, [ 'Inhoud' ], $rows );
	PFH_Widgets_Family::flush();
	wp_cache_flush();
	$before = get_num_queries();
	PFH_Widgets_Family::view( $rows[0]['product'] );
	return get_num_queries() - $before;
}
$two   = cost( 2 );
$eight = cost( 8 );
ok( 'six more siblings cost no more queries', $eight - $two <= 1, "$two for two, $eight for eight" );

// Back to four for the screen below.
PFH_Widgets_Family::apply( $gg, [ 'Smaak', 'Inhoud' ], [
	[ 'product' => $c450, 'values' => [ 'Citroen', '450 ml' ] ],
	[ 'product' => $c1l, 'values' => [ 'Citroen', '1 L' ] ],
	[ 'product' => $a450, 'values' => [ 'Aardbei & citroen', '450 ml' ] ],
	[ 'product' => $a1l, 'values' => [ 'Aardbei & citroen', '1 L' ] ],
] );

echo "\n── the product's own tab ──\n";
ob_start();
PFH_Widgets_Family::product_panel( $c1l );
$panel = (string) ob_get_clean();
ok( 'names its family and values', false !== strpos( $panel, 'Smaak: Citroen' ) && false !== strpos( $panel, 'Inhoud: 1 L' ) );
ok( '  with a link to manage it', false !== strpos( $panel, 'taxonomy=' . PFH_Widgets_Family::TAX ) );
ob_start();
PFH_Widgets_Family::product_panel( simple( 'Test unlinked' ) );
ok( 'an unlinked product says so', false !== strpos( (string) ob_get_clean(), 'Niet gekoppeld' ) );

echo "\n── the screen renders ──\n";
ob_start();
PFH_Widgets_Family::edit_fields( get_term( $gg, PFH_Widgets_Family::TAX ) );
$screen = (string) ob_get_clean();
ok( 'a column per row of buttons', false !== strpos( $screen, '<th>Smaak</th>' ) && false !== strpos( $screen, '<th>Inhoud</th>' ) );
ok( 'a line per member, its product chosen', 4 === substr_count( $screen, "selected='selected'" ), (string) substr_count( $screen, "selected='selected'" ) );
ok( '  and its values filled in', false !== strpos( $screen, 'value="Aardbei &amp; citroen"' ) && false !== strpos( $screen, 'value="1 L"' ) );
ok( 'a blank line to copy for "+ Product toevoegen"', false !== strpos( $screen, 'pfh_family_members[__i__][product]' ) );
ok( 'a product in another family says where it is now', false !== strpos( $screen, 'nu in Test kers' ) );

// Leave the testbed as it was found.
foreach ( $made as $id ) {
	wp_delete_post( $id, true );
}
foreach ( $terms as $t ) {
	wp_delete_term( $t, PFH_Widgets_Family::TAX );
}
wc_delete_attribute( wc_attribute_taxonomy_id_by_name( $emoji_tax ) );

echo "\n$pass passed, $fail failed\n";
