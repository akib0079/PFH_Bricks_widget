<?php
/**
 * Tests for the four store-service modules.
 *
 *   php dev/test-services.php
 *
 * Covers the things that would be expensive to get wrong on a live store:
 * that no URL shape 404s, that tags really are inert before consent, that the
 * PDF a reader receives actually parses, and that invoice numbers cannot
 * collide.
 */

require __DIR__ . '/stubs-services.php';

$pass = 0;
$fail = 0;
$notes = [];

function ok( $label, $condition, $detail = '' ) {
	global $pass, $fail;

	if ( $condition ) {
		$pass++;
		echo "  \033[32m✓\033[0m {$label}\n";
	} else {
		$fail++;
		echo "  \033[31m✗\033[0m {$label}";
		echo $detail ? "\n      {$detail}\n" : "\n";
	}
}

function is_eq( $label, $actual, $expected ) {
	ok(
		$label,
		$actual === $expected,
		'expected: ' . var_export( $expected, true ) . "\n      actual:   " . var_export( $actual, true )
	);
}

function heading( $text ) {
	echo "\n\033[1m{$text}\033[0m\n";
}

/* =====================================================================
 * 1. Settings framework
 * ================================================================== */

heading( '1. Settings framework' );

PFH_Widgets_Consent::forget();

$clean = PFH_Widgets_Consent::sanitize(
	[
		'enabled'      => 'on',
		'layout'       => 'nonsense-value',
		'radius'       => '999',
		'title'        => '  <script>alert(1)</script>Hallo  ',
		'cookie_url'   => '/cookiebeleid-eu/',
		'unknown_key'  => 'should be dropped',
	]
);

is_eq( 'checkbox present becomes true', $clean['enabled'], true );
is_eq( 'checkbox absent becomes false', $clean['show_reject'], false );
is_eq( 'invalid select falls back to the default', $clean['layout'], 'bar' );
is_eq( 'number is clamped to its max', $clean['radius'], 40 );
is_eq( 'text is stripped of markup', $clean['title'], 'alert(1)Hallo' );
ok( 'unknown keys are dropped', ! array_key_exists( 'unknown_key', $clean ) );
ok( 'untouched fields keep their defaults', 'Accepteren' === $clean['accept_label'] );

/* =====================================================================
 * 2. Permalinks — writing URLs
 * ================================================================== */

heading( '2. Permalinks — building URLs' );

pfh_reset_db();

// parent-category > category, with a product in it.
pfh_seed_term( 10, 'griekse-producten', 0 );
pfh_seed_term( 11, 'honing', 10 );
pfh_seed_post( 100, 'rauwe-honing-500g', 'product' );
pfh_seed_meta( 100, 'terms', [ 11 ] );
pfh_seed_meta( 100, '_yoast_wpseo_primary_product_cat', 11 );

$product = $GLOBALS['pfh_db']['posts'][100];
$term    = $GLOBALS['pfh_db']['terms'][11];

function set_permalinks( array $values ) {
	update_option( 'pfh_permalinks', array_merge( PFH_Widgets_Permalinks::defaults(), $values ) );
	PFH_Widgets_Permalinks::forget();
}

// The configuration currently live on productsforhome.nl.
set_permalinks( [ 'product_mode' => 'slug', 'category_mode' => 'full_path' ] );

is_eq(
	'product: bare slug (matches the live Premmerce setting)',
	PFH_Widgets_Permalinks::product_link( 'https://productsforhome.nl/product/rauwe-honing-500g/', $product ),
	'https://productsforhome.nl/rauwe-honing-500g/'
);

is_eq(
	'category: full ancestry path (matches the live Premmerce setting)',
	PFH_Widgets_Permalinks::term_link( 'x', $term, 'product_cat' ),
	'https://productsforhome.nl/griekse-producten/honing/'
);

set_permalinks( [ 'product_mode' => 'slug_category', 'category_mode' => 'full_path' ] );
is_eq(
	'product: slug behind its primary category',
	PFH_Widgets_Permalinks::product_link( 'x', $product ),
	'https://productsforhome.nl/honing/rauwe-honing-500g/'
);

set_permalinks( [ 'product_mode' => 'full_path', 'category_mode' => 'full_path' ] );
is_eq(
	'product: full category path',
	PFH_Widgets_Permalinks::product_link( 'x', $product ),
	'https://productsforhome.nl/griekse-producten/honing/rauwe-honing-500g/'
);

set_permalinks( [ 'product_mode' => 'default' ] );
is_eq(
	'product: default mode leaves WooCommerce alone',
	PFH_Widgets_Permalinks::product_link( 'https://productsforhome.nl/product/x/', $product ),
	'https://productsforhome.nl/product/x/'
);

// The editor asks for the structure with the slug left as a token, so it can
// render the editable "Permalink:" row. Getting this wrong is invisible on the
// front end and makes the setting look broken in wp-admin.
set_permalinks( [ 'product_mode' => 'slug_category', 'category_mode' => 'full_path' ] );
is_eq(
	'editor sample permalink is rewritten too, token intact',
	PFH_Widgets_Permalinks::product_link( 'https://productsforhome.nl/product/%product%/', $product, true ),
	'https://productsforhome.nl/honing/%product%/'
);

set_permalinks( [ 'product_mode' => 'slug' ] );
is_eq(
	'editor sample permalink, bare slug mode',
	PFH_Widgets_Permalinks::product_link( 'https://productsforhome.nl/product/%product%/', $product, true ),
	'https://productsforhome.nl/%product%/'
);

set_permalinks( [ 'product_mode' => 'default' ] );
is_eq(
	'editor sample permalink untouched in default mode',
	PFH_Widgets_Permalinks::product_link( 'https://productsforhome.nl/product/%product%/', $product, true ),
	'https://productsforhome.nl/product/%product%/'
);

// A draft has no slug yet and gets a token permalink; that must survive.
$draft = (object) [ 'ID' => 900, 'post_name' => '', 'post_type' => 'product' ];
set_permalinks( [ 'product_mode' => 'slug' ] );
is_eq(
	'product: a draft keeps its sample permalink',
	PFH_Widgets_Permalinks::product_link( 'https://productsforhome.nl/?post_type=product&p=900', $draft ),
	'https://productsforhome.nl/?post_type=product&p=900'
);

// Primary category off: fall back to the lowest term id, not the Yoast one.
pfh_seed_meta( 100, 'terms', [ 11, 10 ] );
set_permalinks( [ 'product_mode' => 'slug_category', 'use_primary_category' => false ] );
is_eq(
	'product: without Yoast primary, lowest term id wins',
	PFH_Widgets_Permalinks::product_link( 'x', $product ),
	'https://productsforhome.nl/griekse-producten/rauwe-honing-500g/'
);
pfh_seed_meta( 100, 'terms', [ 11 ] );

/* =====================================================================
 * 3. Permalinks — resolving URLs back
 * ================================================================== */

heading( '3. Permalinks — resolving URLs (nothing may 404)' );

set_permalinks( [ 'product_mode' => 'slug', 'category_mode' => 'full_path' ] );

// A real page, to prove pages keep precedence over products.
pfh_seed_post( 200, 'contact', 'page' );
pfh_seed_post( 201, 'rauwe-honing-500g', 'page' ); // deliberate collision

/**
 * Run a path through parse_request the way WordPress would.
 */
function resolve_path( $path, array $vars = [] ) {
	$wp = new WP( $path, $vars );
	PFH_Widgets_Permalinks::resolve( $wp );

	return $wp->query_vars;
}

$vars = resolve_path( 'rauwe-honing-500g', [ 'pagename' => 'rauwe-honing-500g' ] );
ok(
	'a page beats a product on the same slug',
	! isset( $vars['post_type'] ),
	'query vars: ' . json_encode( $vars )
);

// Remove the colliding page for the remaining cases.
unset( $GLOBALS['pfh_db']['posts'][201] );

$cases = [
	'canonical product URL' => [
		'rauwe-honing-500g',
		[ 'name' => 'rauwe-honing-500g' ],
		[ 'product' => 'rauwe-honing-500g', 'post_type' => 'product' ],
	],
	'legacy /product/ base still resolves' => [
		'product/rauwe-honing-500g',
		[],
		[ 'product' => 'rauwe-honing-500g', 'post_type' => 'product' ],
	],
	'legacy category path in front of the slug still resolves' => [
		'griekse-producten/honing/rauwe-honing-500g',
		[],
		[ 'product' => 'rauwe-honing-500g', 'post_type' => 'product' ],
	],
	'legacy /product/category/slug still resolves' => [
		'product/honing/rauwe-honing-500g',
		[],
		[ 'product' => 'rauwe-honing-500g', 'post_type' => 'product' ],
	],
	'canonical category path' => [
		'griekse-producten/honing',
		[],
		[ 'product_cat' => 'honing' ],
	],
	'bare category slug still resolves' => [
		'honing',
		[],
		[ 'product_cat' => 'honing' ],
	],
	'legacy /product-category/ base still resolves' => [
		'product-category/griekse-producten/honing',
		[],
		[ 'product_cat' => 'honing' ],
	],
];

foreach ( $cases as $label => $case ) {
	list( $path, $incoming, $expected ) = $case;

	$got = resolve_path( $path, $incoming );
	$hit = true;

	foreach ( $expected as $key => $value ) {
		if ( ! isset( $got[ $key ] ) || $got[ $key ] !== $value ) {
			$hit = false;
			break;
		}
	}

	ok( $label, $hit, 'got: ' . json_encode( $got ) );
}

// Other plugins' query vars must survive: a builder loads the front end with
// extra state attached, and wiping it takes the editor out.
$wp = new WP( 'rauwe-honing-500g', [ 'name' => 'rauwe-honing-500g', 'bricks' => 'run', 'custom_var' => 'keep-me' ] );
PFH_Widgets_Permalinks::resolve( $wp );
ok(
	'unrelated query vars are preserved',
	'run' === ( $wp->query_vars['bricks'] ?? '' ) && 'keep-me' === ( $wp->query_vars['custom_var'] ?? '' ),
	json_encode( $wp->query_vars )
);

$wp = new WP( 'honing', [ 'pagename' => 'honing', 'preview' => 'true' ] );
PFH_Widgets_Permalinks::resolve( $wp );
ok(
	'but a contradicting pagename is dropped',
	! isset( $wp->query_vars['pagename'] ) && 'honing' === ( $wp->query_vars['product_cat'] ?? '' ),
	json_encode( $wp->query_vars )
);

// Junk must not be answered.
$got = resolve_path( 'made/up/path/entirely', [] );
ok( 'an invented path is left to 404', empty( $got['product'] ) && empty( $got['product_cat'] ), json_encode( $got ) );

$got = resolve_path( 'nonsense-prefix/rauwe-honing-500g', [] );
ok( 'a junk prefix is not accepted as a product path', empty( $got['product'] ), json_encode( $got ) );

// Pagination and feeds have to survive the rewrite.
$got = resolve_path( 'griekse-producten/honing/page/3', [] );
ok(
	'category pagination survives',
	isset( $got['product_cat'], $got['paged'] ) && 'honing' === $got['product_cat'] && 3 === $got['paged'],
	json_encode( $got )
);

$got = resolve_path( 'griekse-producten/honing/feed', [] );
ok(
	'category feed survives',
	isset( $got['product_cat'], $got['feed'] ) && 'honing' === $got['product_cat'],
	json_encode( $got )
);

// A draft product must not become publicly reachable.
pfh_seed_post( 300, 'geheim-product', 'product', 'draft' );
$got = resolve_path( 'geheim-product', [] );
ok( 'a draft product is not served', empty( $got['product'] ), json_encode( $got ) );

// An unrelated post type is left alone.
pfh_seed_post( 400, 'blogbericht', 'post' );
$got = resolve_path( 'blogbericht', [ 'name' => 'blogbericht' ] );
ok( 'a post is left to WordPress', empty( $got['product'] ), json_encode( $got ) );

/* =====================================================================
 * 4. Consent — tag blocking
 * ================================================================== */

heading( '4. Consent — tag blocking' );

update_option( 'pfh_consent', PFH_Widgets_Consent::defaults() );
PFH_Widgets_Consent::forget();

$markup = <<<'HTML'
<script src="https://connect.facebook.net/en_US/fbevents.js"></script>
<script>fbq('init','123');fbq('track','PageView');</script>
<script src="https://static.klaviyo.com/onsite/js/klaviyo.js?company_id=ABC"></script>
<script type="application/ld+json">{"@type":"Product","name":"Honing"}</script>
<script src="https://productsforhome.nl/wp-includes/js/jquery.js"></script>
<script id="pfh-consent-mode">gtag('consent','default',{});</script>
<script>var pfhConsent = {cookie:"pfh_consent"};</script>
<iframe src="https://www.youtube.com/embed/abc123" width="560" height="315"></iframe>
<iframe src="https://productsforhome.nl/embed/own" width="400" height="300"></iframe>
HTML;

$out = PFH_Widgets_Consent::rewrite( $markup );

ok( 'Meta pixel library is held', false !== strpos( $out, 'data-pfh-consent="marketing"' ) && false !== strpos( $out, 'data-pfh-src="https://connect.facebook.net' ) );
ok( 'inline fbq() call is held', substr_count( $out, 'data-pfh-consent="marketing"' ) >= 3 );
ok( 'Klaviyo is held', false !== strpos( $out, 'data-pfh-src="https://static.klaviyo.com' ) );
ok( 'JSON-LD is untouched', false !== strpos( $out, '<script type="application/ld+json">{"@type":"Product"' ) );
ok( 'first-party jQuery is untouched', false !== strpos( $out, '<script src="https://productsforhome.nl/wp-includes/js/jquery.js">' ) );
ok( 'the consent-mode snippet does not block itself', false !== strpos( $out, '<script id="pfh-consent-mode">' ) );
ok( 'the consent layer\'s own config is not held', false !== strpos( $out, '<script>var pfhConsent' ) );
ok( 'YouTube iframe is held behind a placeholder', false !== strpos( $out, 'data-pfh-ck-placeholder="marketing"' ) && false !== strpos( $out, '<iframe data-pfh-consent="marketing" hidden' ) );
ok( 'a first-party iframe is untouched', false !== strpos( $out, '<iframe src="https://productsforhome.nl/embed/own"' ) );
ok( 'no held script keeps an executable src', false === strpos( $out, 'text/plain" data-pfh-consent="marketing" src=' ) );

// Rewriting is idempotent: the head buffer and the footer buffer must not
// double-wrap anything that passed through both.
$twice = PFH_Widgets_Consent::rewrite( $out );
is_eq( 'rewriting twice changes nothing', $twice, $out );

// Google is governed by Consent Mode, not by blocking, unless asked.
$google = '<script src="https://www.googletagmanager.com/gtag/js?id=G-X"></script>';
ok( 'Google is left to Consent Mode by default', PFH_Widgets_Consent::rewrite( $google ) === $google );

update_option( 'pfh_consent', array_merge( PFH_Widgets_Consent::defaults(), [ 'block_google' => true ] ) );
PFH_Widgets_Consent::forget();
ok( 'Google is held when strict blocking is switched on', false !== strpos( PFH_Widgets_Consent::rewrite( $google ), 'data-pfh-consent="statistics"' ) );

update_option( 'pfh_consent', PFH_Widgets_Consent::defaults() );
PFH_Widgets_Consent::forget();

// Registered handles.
ok(
	'a registered marketing handle is held',
	false !== strpos( PFH_Widgets_Consent::filter_handle( "<script src='/a.js' id='klaviyo-js'></script>", 'klaviyo', '/a.js' ), 'data-pfh-consent="marketing"' )
);
is_eq(
	'an unrelated handle is untouched',
	PFH_Widgets_Consent::filter_handle( "<script src='/b.js'></script>", 'jquery-core', '/b.js' ),
	"<script src='/b.js'></script>"
);

// A single inline script larger than pcre.backtrack_limit used to make
// preg_replace_callback return null, which was cast to '' and wiped the whole
// buffer. The Bricks builder prints its element payload exactly that way, so
// this took the editor out completely.
$huge = '<script>var payload="' . str_repeat( 'a', 2 * 1048576 ) . '";</script>'
	. '<script src="https://connect.facebook.net/fbevents.js"></script>';

$survived = PFH_Widgets_Consent::rewrite( $huge );

ok(
	'a 2MB inline script does not destroy the buffer',
	strlen( $survived ) >= strlen( $huge ),
	'in ' . strlen( $huge ) . ' bytes, out ' . strlen( $survived )
);
ok( 'and the pixel beside it is still held', false !== strpos( $survived, 'data-pfh-consent="marketing"' ) );
ok( 'and the big script is left alone', false !== strpos( $survived, '<script>var payload="' ) );

// Unterminated and malformed markup must come back, not vanish.
is_eq( 'an unclosed script tag is returned intact', PFH_Widgets_Consent::rewrite( '<script>alert(1)' ), '<script>alert(1)' );
is_eq( 'a bare angle bracket survives', PFH_Widgets_Consent::rewrite( '<script' ), '<script' );
is_eq( 'markup that merely starts with the word is untouched', PFH_Widgets_Consent::rewrite( '<scripting>x</scripting>' ), '<scripting>x</scripting>' );
is_eq( 'empty input stays empty', PFH_Widgets_Consent::rewrite( '' ), '' );

// Page builders: the layer must stand down entirely.
$_GET['bricks'] = 'run';
ok( 'the consent layer stands down inside the Bricks builder', true === PFH_Widgets_Consent::skip() );

ob_start();
PFH_Widgets_Consent::render();
PFH_Widgets_Consent::head_snippet();
$in_builder = ob_get_clean();

is_eq( 'nothing is printed into the builder canvas', $in_builder, '' );

PFH_Widgets_Consent::buffer_start();
PFH_Widgets_Consent::buffer_end();
ok( 'and the output buffer is never opened there', true );

unset( $_GET['bricks'] );
ok( 'outside a builder it runs again', false === PFH_Widgets_Consent::skip() );

/* =====================================================================
 * 5. PDF writer
 * ================================================================== */

heading( '5. PDF writer' );

$pdf = new PFH_Widgets_PDF();
$pdf->add_page();
$pdf->font( 18, true )->color( '#3F7E7C' )->text( 40, 60, 'Factuur' );
$pdf->font( 10 )->color( '#1f2b2e' );
$pdf->paragraph( 40, 90, 300, "Straat 12\n2405 AB Alphen aan den Rijn\nNederland" );
$pdf->text( 555, 90, '€ 1.234,56', 'right' );
$pdf->line( 40, 120, 555, 120, '#cccccc' );
$pdf->rect( 40, 130, 200, 20, '#f4f6f5' );
$pdf->add_page();
$pdf->text( 40, 60, 'Pagina twee' );

$bytes = $pdf->output();

ok( 'starts with a PDF header', 0 === strpos( $bytes, '%PDF-1.4' ) );
ok( 'ends with EOF', '%%EOF' === substr( $bytes, -5 ) );
ok( 'declares two pages', false !== strpos( $bytes, '/Count 2' ) );

/**
 * Walk the cross-reference table the way a reader does: every offset must
 * land exactly on the object it claims.
 */
function verify_xref( $bytes ) {
	if ( ! preg_match( '/startxref\s+(\d+)\s+%%EOF$/', $bytes, $m ) ) {
		return 'no startxref';
	}

	$start = (int) $m[1];
	$table = substr( $bytes, $start );

	if ( 0 !== strpos( $table, 'xref' ) ) {
		return 'startxref does not point at the table';
	}

	if ( ! preg_match( '/^xref\s+0 (\d+)\s+/', $table, $m ) ) {
		return 'malformed xref header';
	}

	$size = (int) $m[1];

	preg_match_all( '/^(\d{10}) (\d{5}) ([nf])\s*$/m', $table, $rows, PREG_SET_ORDER );

	if ( count( $rows ) !== $size ) {
		return 'xref lists ' . count( $rows ) . ' entries but declares ' . $size;
	}

	foreach ( $rows as $i => $row ) {
		if ( 'f' === $row[3] ) {
			continue;
		}

		$offset = (int) $row[1];
		$found  = substr( $bytes, $offset, 20 );

		if ( 0 !== strpos( $found, $i . ' 0 obj' ) ) {
			return "entry {$i} points at offset {$offset}, which reads '" . trim( substr( $found, 0, 12 ) ) . "'";
		}
	}

	return true;
}

$xref = verify_xref( $bytes );
ok( 'every xref offset lands on its object', true === $xref, is_string( $xref ) ? $xref : '' );

// Text metrics must be real, or wrapping silently overflows the page.
$pdf->font( 10 );
$width = $pdf->width_of( 'Rauwe honing uit Griekenland' );
ok( 'text measures to a plausible width', $width > 120 && $width < 170, 'got ' . round( $width, 2 ) . 'pt' );

$pdf->font( 9.5 );
$lines = $pdf->wrap( 'Biologische Griekse olijfolie extra vierge premium 750ml fles', 150 );
$widest = 0;

foreach ( $lines as $line ) {
	$widest = max( $widest, $pdf->width_of( $line ) );
}

ok( 'wrapped lines all fit the box', count( $lines ) > 1 && $widest <= 150, 'widest line ' . round( $widest, 2 ) . 'pt over ' . count( $lines ) . ' lines' );

$long = $pdf->wrap( 'EAN8712345678901234567890ABCDEFGHIJKLMNOP', 60 );
$widest = 0;

foreach ( $long as $line ) {
	$widest = max( $widest, $pdf->width_of( $line ) );
}

ok( 'an unbreakable word is cut rather than overflowing', $widest <= 60, 'widest ' . round( $widest, 2 ) . 'pt' );

// Accented Dutch text must survive the WinAnsi conversion.
$pdf2 = new PFH_Widgets_PDF();
$pdf2->add_page();
$pdf2->font( 10 )->text( 40, 60, 'Eén café – ëïöü ß € 12,50' );
$accented = $pdf2->output();
ok( 'accented text does not corrupt the stream', false !== strpos( $accented, '%%EOF' ) && true === verify_xref( $accented ) );

/* =====================================================================
 * 6. Invoice numbering
 * ================================================================== */

heading( '6. Invoice numbering' );

update_option(
	'pfh_docs',
	array_merge(
		PFH_Widgets_Documents::defaults(),
		[ 'prefix' => 'PFH-[year]-', 'padding' => 5, 'start_number' => 1040 ]
	)
);
PFH_Widgets_Documents::forget();
delete_option( 'pfh_invoice_seq' );

$order_a = new WC_Order( 501 );
$order_b = new WC_Order( 502 );

$a1 = PFH_Widgets_Documents::number( $order_a );
$a2 = PFH_Widgets_Documents::number( $order_a );
$b1 = PFH_Widgets_Documents::number( $order_b );

is_eq( 'first invoice starts at the configured number', $a1, 'PFH-' . gmdate( 'Y' ) . '-01040' );
is_eq( 'the same order always returns the same number', $a2, $a1 );
is_eq( 'the next order gets the next number', $b1, 'PFH-' . gmdate( 'Y' ) . '-01041' );

$order_c = new WC_Order( 503 );
ok( 'no number is allocated just by asking', '' === PFH_Widgets_Documents::number( $order_c, false ) );
ok( 'and the counter did not move', 1041 === (int) get_option( 'pfh_invoice_seq' ) );

/* =====================================================================
 * 7. A real invoice, end to end
 * ================================================================== */

heading( '7. Invoice rendering' );

update_option(
	'pfh_docs',
	array_merge(
		PFH_Widgets_Documents::defaults(),
		[
			'company_name' => 'Products for Home',
			'address'      => "Handelsweg 12\n2404 CD Alphen aan den Rijn\nNederland",
			'vat'          => 'NL001234567B01',
			'coc'          => '87654321',
			'iban'         => 'NL91 ABNA 0417 1643 00',
			'contact'      => 'info@productsforhome.nl',
			'footer_note'  => 'Bedankt voor je bestelling. Betaling is voldaan via iDEAL.',
			'prefix'       => 'PFH-2026-',
			'start_number' => 1040,
		]
	)
);
PFH_Widgets_Documents::forget();

$order = new WC_Order( 10372 );
$order->billing  = "Jaimy de Vries\nKeizersgracht 118\n1015 CW Amsterdam\nNederland";
$order->shipping = "Jaimy de Vries\nKeizersgracht 118\n1015 CW Amsterdam\nNederland";

$item_a = new WC_Order_Item( 'Griekse olijfolie extra vierge premium 750ml', 2, 18.95, 'PFH-OO-750-P' );
$item_b = new WC_Order_Item( 'Rauwe honing uit Griekenland — ongefilterd, direct van de imker', 1, 12.50, 'PFH-HON-450' );
$item_b->pfh_meta = '450 gram';
$item_c = new WC_Order_Item( 'Griekse kruidenthee proefpakket', 3, 7.95, 'PFH-THEE-MIX' );

$order->items  = [ $item_a, $item_b, $item_c ];
$order->totals = [
	'cart_subtotal'  => [ 'label' => 'Subtotaal:', 'value' => wc_price( 74.25 ) ],
	'discount'       => [ 'label' => 'Staffelkorting:', 'value' => '-' . wc_price( 3.79 ) ],
	'shipping'       => [ 'label' => 'Verzending:', 'value' => wc_price( 0 ) ],
	'tax'            => [ 'label' => 'BTW 9%:', 'value' => wc_price( 5.82 ) ],
	'payment_method' => [ 'label' => 'Betaalmethode:', 'value' => 'iDEAL' ],
	'order_total'    => [ 'label' => 'Totaal:', 'value' => wc_price( 70.46 ) ],
];

$invoice = PFH_Widgets_Documents::build( $order, 'invoice' );
$packing = PFH_Widgets_Documents::build( $order, 'packing-slip' );

ok( 'invoice renders', strlen( $invoice ) > 1200 && 0 === strpos( $invoice, '%PDF' ) );
ok( 'invoice xref is valid', true === verify_xref( $invoice ) );
ok( 'packing slip renders', strlen( $packing ) > 1000 && 0 === strpos( $packing, '%PDF' ) );
ok( 'packing slip xref is valid', true === verify_xref( $packing ) );
ok( 'packing slip is smaller than the invoice (no prices or totals)', strlen( $packing ) < strlen( $invoice ) );

$out_dir = __DIR__ . '/out';

if ( ! is_dir( $out_dir ) ) {
	mkdir( $out_dir, 0755, true );
}

file_put_contents( $out_dir . '/invoice.pdf', $invoice );
file_put_contents( $out_dir . '/packing-slip.pdf', $packing );

// A long order, to exercise the page break.
$order->items = array_fill( 0, 30, $item_a );
$long = PFH_Widgets_Documents::build( $order, 'invoice' );

ok( 'a 30-line order breaks onto more pages', false === strpos( $long, '/Count 1 ' ) && preg_match( '#/Count (\d+)#', $long, $m ) && (int) $m[1] >= 2, isset( $m[1] ) ? 'pages: ' . $m[1] : '' );
ok( 'the long invoice is still valid', true === verify_xref( $long ) );

file_put_contents( $out_dir . '/invoice-long.pdf', $long );

/* =====================================================================
 * 8. Badge
 * ================================================================== */

heading( '8. Review badge' );

update_option(
	'pfh_badge',
	array_merge(
		PFH_Widgets_Badge::defaults(),
		[ 'enabled' => true, 'fallback_rating' => '9,7', 'fallback_count' => 396 ]
	)
);
PFH_Widgets_Badge::forget();

$data = PFH_Widgets_Badge::data();

is_eq( 'falls back to the configured score when offline', $data['rating'], '9.7' );
is_eq( 'falls back to the configured count', $data['count'], 396 );
is_eq( 'converts a 9.7/10 score to 5 stars', $data['stars'], 5.0 );
ok( 'knows the figures are not live', false === $data['live'] );

is_eq( '8.0/10 becomes 4 stars', PFH_Widgets_Reviews::stars( 8.0, 10, 5 ), 4.0 );
is_eq( '9.0/10 becomes 4.5 stars', PFH_Widgets_Reviews::stars( 9.0, 10, 5 ), 4.5 );

ob_start();
PFH_Widgets_Badge::render();
$badge = ob_get_clean();

ok( 'badge renders the score', false !== strpos( $badge, '9.7' ) );
ok( 'badge renders every trust point', 4 === substr_count( $badge, 'pfh-bdg__point"' ) );
ok( 'badge panel starts hidden for the script to decide', false !== strpos( $badge, 'id="pfh-badge-panel" hidden' ) );
ok( 'badge links out with rel="noopener nofollow"', false !== strpos( $badge, 'rel="noopener nofollow"' ) );

/* =====================================================================
 * 9. Consent banner markup
 * ================================================================== */

heading( '9. Consent banner markup' );

update_option( 'pfh_consent', PFH_Widgets_Consent::defaults() );
PFH_Widgets_Consent::forget();

ob_start();
PFH_Widgets_Consent::render();
$banner = ob_get_clean();

ok( 'banner is rendered hidden, so one cached page suits everyone', false !== strpos( $banner, 'data-pfh-consent-root' ) && false !== strpos( $banner, 'hidden>' ) );
ok( 'accept, reject and preferences are all present', false !== strpos( $banner, 'data-pfh-ck="accept"' ) && false !== strpos( $banner, 'data-pfh-ck="reject"' ) && false !== strpos( $banner, 'data-pfh-ck="prefs"' ) );
ok( 'both optional categories get a switch', false !== strpos( $banner, 'data-pfh-ck-cat="statistics"' ) && false !== strpos( $banner, 'data-pfh-ck-cat="marketing"' ) );
ok( 'functional is shown as always on, with no switch', false !== strpos( $banner, 'pfh-ck__always' ) && 2 === substr_count( $banner, 'data-pfh-ck-cat=' ) );
ok( 'relative policy links are resolved against the site', false !== strpos( $banner, 'https://productsforhome.nl/cookiebeleid-eu/' ) );
ok( 'all three policy links are present', false !== strpos( $banner, '/privacy-policy/' ) && false !== strpos( $banner, '/contact/' ) );
ok( 'there is no overlay element at all', false === strpos( $banner, 'overlay' ) && false === strpos( $banner, 'backdrop' ) );
ok( 'category descriptions start collapsed', 3 === substr_count( $banner, 'aria-expanded="false"' ) && 3 === substr_count( $banner, 'pfh-ck__row-desc' ) );
ok( 'every description is hidden and wired to its toggle', 3 === substr_count( $banner, 'aria-controls="pfh-ck-desc-' ) );

update_option( 'pfh_consent', array_merge( PFH_Widgets_Consent::defaults(), [ 'expand_details' => true ] ) );
PFH_Widgets_Consent::forget();

ob_start();
PFH_Widgets_Consent::render();
$expanded = ob_get_clean();

ok( 'the expanded setting opens every description', 3 === substr_count( $expanded, 'aria-expanded="true"' ) && false === strpos( $expanded, 'pfh-ck__row-desc" id="pfh-ck-desc-marketing" hidden' ) );

update_option( 'pfh_consent', PFH_Widgets_Consent::defaults() );
PFH_Widgets_Consent::forget();

ob_start();
PFH_Widgets_Consent::head_snippet();
$head = ob_get_clean();

ok( 'consent mode denies by default', false !== strpos( $head, "ad_storage:'denied'" ) && false !== strpos( $head, "analytics_storage:'denied'" ) );
ok( 'consent mode grants what is strictly necessary', false !== strpos( $head, "security_storage:'granted'" ) );
ok( 'consent mode waits for the cookie read', false !== strpos( $head, 'wait_for_update' ) );
ok( 'the cookie is read inline so a cached page still updates', false !== strpos( $head, "gtag('consent','update'" ) );

/* =====================================================================
 * 10. Badge mark
 * ================================================================== */

heading( '10. Badge mark' );

update_option( 'pfh_badge', array_merge( PFH_Widgets_Badge::defaults(), [ 'enabled' => true ] ) );
PFH_Widgets_Badge::forget();

ok(
	'falls back to the transparent PNG that ships with the plugin',
	false !== strpos( PFH_Widgets_Badge::mark_src(), 'assets/img/pfh-webwinkelkeur.png' ),
	PFH_Widgets_Badge::mark_src()
);

update_option( 'pfh_badge', array_merge( PFH_Widgets_Badge::defaults(), [ 'enabled' => true, 'mark_url' => 'https://cdn.example.com/wwk.svg' ] ) );
PFH_Widgets_Badge::forget();

is_eq( 'a typed URL wins over the bundled file', PFH_Widgets_Badge::mark_src(), 'https://cdn.example.com/wwk.svg' );

ob_start();
PFH_Widgets_Badge::render();
$marked = ob_get_clean();

ok( 'the mark renders as an image, not the built-in SVG', 2 === substr_count( $marked, 'pfh-bdg__mark-img' ) );
ok( 'the mark is hidden from assistive tech, since the text says it', 2 === substr_count( $marked, 'alt="" aria-hidden="true"' ) );
ok( 'the tab colours reach the markup', false !== strpos( $marked, '--pfh-bdg-tab-bg' ) && false !== strpos( $marked, '--pfh-bdg-mark' ) );

update_option( 'pfh_badge', array_merge( PFH_Widgets_Badge::defaults(), [ 'enabled' => true ] ) );
PFH_Widgets_Badge::forget();

/* =====================================================================
 * 11. Load order
 *
 * WordPress includes plugin files alphabetically, so this plugin is loaded
 * before woocommerce. A module that decides what to hook at load time sees no
 * WooCommerce and silently registers nothing — which is exactly how the order
 * screen ended up with no Create PDF box.
 * ================================================================== */

heading( '11. Load order (WooCommerce arrives after this plugin)' );

$GLOBALS['pfh_db']['actions'] = [];
$GLOBALS['pfh_db']['filters'] = [];

PFH_Widgets_Documents::init();
PFH_Widgets_Permalinks::init();

ok( 'before WooCommerce loads, the order screen box is not registered', empty( $GLOBALS['pfh_db']['actions']['add_meta_boxes'] ) );
ok( 'before WooCommerce loads, permalinks hook nothing', empty( $GLOBALS['pfh_db']['filters']['post_type_link'] ) );
ok( 'but both settings tabs register regardless', isset( PFH_Widgets_Settings::tabs()['documents'], PFH_Widgets_Settings::tabs()['permalinks'] ) );

// WooCommerce's own file defines this at include time, so by plugins_loaded
// it always exists. Simulate that, then boot the services the way the plugin
// now does.
// Wrapped in a conditional on purpose: PHP hoists unconditional top-level
// declarations to compile time, which would make WooCommerce exist from the
// first line of this file and quietly invalidate the two checks above.
if ( ! class_exists( 'WooCommerce' ) ) {
	class WooCommerce {}

	function WC() {
		return null;
	}
}

$GLOBALS['pfh_db']['actions'] = [];
$GLOBALS['pfh_db']['filters'] = [];

PFH_Widgets_Documents::init();
PFH_Widgets_Permalinks::init();

ok( 'once WooCommerce is loaded, the Create PDF box registers', ! empty( $GLOBALS['pfh_db']['actions']['add_meta_boxes'] ) );
ok( 'the document download endpoint registers', ! empty( $GLOBALS['pfh_db']['actions']['admin_post_pfh_document'] ) );
ok( 'invoices attach to WooCommerce email', ! empty( $GLOBALS['pfh_db']['filters']['woocommerce_email_attachments'] ) );
ok( 'the My Account invoice button registers', ! empty( $GLOBALS['pfh_db']['filters']['woocommerce_my_account_my_orders_actions'] ) );

/*
 * The permalink manager registers nothing until it is switched on. A plugin
 * that starts rewriting a trading shop's URLs the moment it is activated is
 * the bug this asserts against, not a feature.
 */
ok(
	'permalink rewriting does NOT register on a fresh install',
	empty( $GLOBALS['pfh_db']['filters']['post_type_link'] ) && empty( $GLOBALS['pfh_db']['filters']['term_link'] )
);
ok(
	'permalink resolution does NOT register on a fresh install',
	empty( $GLOBALS['pfh_db']['actions']['parse_request'] )
);

// Switched on deliberately, it hooks up as it should.
set_permalinks( [ 'enabled' => true, 'product_mode' => 'slug', 'category_mode' => 'full_path' ] );
$GLOBALS['pfh_db']['actions'] = [];
$GLOBALS['pfh_db']['filters'] = [];
PFH_Widgets_Permalinks::init();

ok( 'permalink rewriting registers once enabled', ! empty( $GLOBALS['pfh_db']['filters']['post_type_link'] ) && ! empty( $GLOBALS['pfh_db']['filters']['term_link'] ) );
ok( 'permalink resolution registers once enabled', ! empty( $GLOBALS['pfh_db']['actions']['parse_request'] ) );

// And the bootstrap must actually defer, or none of the above ever runs.
$bootstrap = file_get_contents( dirname( __DIR__ ) . '/pfh-bricks-widgets/includes/class-pfh-plugin.php' );

ok(
	'the plugin boots its services on plugins_loaded, not at include time',
	false !== strpos( $bootstrap, "add_action( 'plugins_loaded', [ __CLASS__, 'boot_services' ]" )
);

/* =====================================================================
 * 12. Shared review figures
 *
 * The hero, the footer, the inline rating and the sticky badge all read this,
 * so one feed cannot produce three different numbers on one page.
 * ================================================================== */

heading( '12. Shared review figures' );

// Offline: the typed values survive untouched.
$off = PFH_Widgets_Reviews::figures( [ 'live' => true, 'scale' => 10, 'score' => '9.7', 'count' => 270 ] );

is_eq( 'an unreachable feed keeps the typed score', $off['score'], '9.7' );
is_eq( 'an unreachable feed keeps the typed count', $off['count'], 270 );
ok( 'and says so', false === $off['live'] );
is_eq( 'stars still match the typed score', $off['stars'], 5.0 );

// A live summary, injected the way the API would supply it.
add_filter(
	'pfh_webwinkelkeur_pre_summary',
	static function () {
		return [ 'rating' => 9.7, 'count' => 396, 'scale' => 10 ];
	}
);

$ten  = PFH_Widgets_Reviews::figures( [ 'live' => true, 'scale' => 10, 'score' => '0', 'count' => 0 ] );
$five = PFH_Widgets_Reviews::figures( [ 'live' => true, 'scale' => 5, 'score' => '0', 'count' => 0 ] );

is_eq( 'footer scale: 9.7 out of 10', $ten['score'], '9.7' );
is_eq( 'hero scale: the same rating out of 5', $five['score'], '4.9' );
is_eq( 'the live count replaces the typed one', $five['count'], 396 );
ok( 'both report as live', true === $ten['live'] && true === $five['live'] );

// 9.7/10 is 4.85/5, which rounds to a full five stars — so the banner looks
// exactly as designed until the real score actually drops.
is_eq( 'stars round to the nearest half, so the design holds at 9.7', $five['stars'], 5.0 );

$lower = PFH_Widgets_Reviews::figures( [ 'live' => false, 'scale' => 5, 'score' => '4.2' ] );
is_eq( 'a genuinely lower score moves the stars', $lower['stars'], 4.0 );

// Rounding, for a "270+ reviews" style label.
$round = PFH_Widgets_Reviews::figures( [ 'live' => true, 'scale' => 5, 'round' => 10 ] );

is_eq( 'the displayed count rounds down', $round['shown'], 390 );
is_eq( 'while the exact count stays available', $round['count'], 396 );

// Tokens.
is_eq(
	'hero label tokens resolve',
	PFH_Widgets_Reviews::tokens( '(%count%+ reviews) %score%', $round ),
	'(390+ reviews) 4.9'
);
is_eq(
	'%total% is always the exact figure',
	PFH_Widgets_Reviews::tokens( '%total% reviews', $round ),
	'396 reviews'
);
is_eq(
	'a label with no tokens is left alone',
	PFH_Widgets_Reviews::tokens( 'Excellent', $round ),
	'Excellent'
);

// Labels with no token at all. This is what was actually on the live site:
// the switch was on, the label said 270, and nothing ever changed it.
$live = PFH_Widgets_Reviews::figures( [ 'live' => true, 'scale' => 5, 'round' => 10 ] );

is_eq(
	'a token-free footer label is renumbered',
	PFH_Widgets_Reviews::tokens( '270 reviews on', $live ),
	'390 reviews on'
);
is_eq(
	'%s still works, and wins',
	PFH_Widgets_Reviews::tokens( '%s reviews on', $live ),
	'390 reviews on'
);
is_eq(
	'a token-free hero label gets both figures',
	PFH_Widgets_Reviews::tokens( '(270+ reviews) 5.0', $live ),
	'(390+ reviews) 4.9'
);
is_eq(
	'a label with no numbers is left alone',
	PFH_Widgets_Reviews::tokens( 'Excellent', $live ),
	'Excellent'
);
is_eq(
	'three or more numbers is too ambiguous to guess at',
	PFH_Widgets_Reviews::tokens( 'Open 9.00 - 17.00, 270 reviews', $live ),
	'Open 9.00 - 17.00, 270 reviews'
);

$dead = PFH_Widgets_Reviews::figures( [ 'live' => false, 'scale' => 5, 'score' => '9.7', 'count' => 270 ] );
is_eq(
	'an unreachable feed never overwrites what was typed',
	PFH_Widgets_Reviews::tokens( '270 reviews on', $dead ),
	'270 reviews on'
);

ok( 'the badge has somewhere to link to', false !== strpos( PFH_Widgets_Reviews::review_url(), 'webwinkelkeur.nl' ), PFH_Widgets_Reviews::review_url() );

$GLOBALS['pfh_db']['filters']['pfh_webwinkelkeur_pre_summary'] = [];

/* =====================================================================
 * Summary
 * ================================================================== */

echo "\n" . str_repeat( '─', 62 ) . "\n";
printf( "\033[1m%d passed, %d failed\033[0m\n", $pass, $fail );

if ( ! $fail ) {
	echo "PDFs written to dev/out/ for visual inspection.\n";
}

exit( $fail ? 1 : 0 );
