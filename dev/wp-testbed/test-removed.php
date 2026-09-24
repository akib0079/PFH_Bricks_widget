<?php
/**
 * Cookie consent, the permalink manager and PDF invoices were taken out in
 * 1.48.0. On a real WordPress boot: nothing of them is hooked, the settings
 * screen shows the two tabs that are left and still opens from an old link
 * to a removed one, and the report page no longer asks after them.
 */
require __DIR__ . '/wp-load.php';

require_once ABSPATH . 'wp-admin/includes/admin.php';

header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

echo "── nothing of them is left running ──\n";

foreach ( [ 'PFH_Widgets_Consent', 'PFH_Widgets_Permalinks', 'PFH_Widgets_Documents', 'PFH_Widgets_PDF' ] as $class ) {
	ok( "$class does not exist", ! class_exists( $class ) );
}

ok( 'no consent banner in the footer', false === has_action( 'wp_ajax_nopriv_pfh_consent' ) && false === has_action( 'wp_ajax_pfh_consent' ) );
ok( 'no invoice download endpoint', false === has_action( 'admin_post_pfh_document' ) );

$attaching = false;

foreach ( (array) ( $GLOBALS['wp_filter']['woocommerce_email_attachments'] ?? [] ) as $callbacks ) {
	foreach ( (array) $callbacks as $cb ) {
		if ( is_array( $cb['function'] ) && is_string( $cb['function'][0] ) && 0 === strpos( $cb['function'][0], 'PFH_' ) ) {
			$attaching = true;
		}
	}
}

ok( 'no PDF attached to WooCommerce email by this plugin', ! $attaching );
ok( 'no URL rewriting', ! has_filter( 'post_type_link', [ 'PFH_Widgets_Permalinks', 'product_link' ] ) );

echo "\n── the settings screen ──\n";

$tabs = array_keys( PFH_Widgets_Settings::tabs() );
ok( 'two tabs are left: WebwinkelKeur and Instagram', [ 'reviews', 'instagram' ] === $tabs, implode( ',', $tabs ) );

wp_set_current_user( 1 );

foreach ( [ 'reviews', 'instagram', 'permalinks', 'consent', 'documents', '' ] as $asked ) {
	$_GET['tab'] = $asked;

	ob_start();
	PFH_Widgets_Settings::render();
	$page = ob_get_clean();

	ok(
		sprintf( 'it opens%s', '' === $asked ? ' with no tab asked for' : " from a link to ?tab=$asked" ),
		false !== strpos( $page, 'WebwinkelKeur' ) && false !== strpos( $page, 'Instagram' ) && false === strpos( $page, 'Cookie consent' ) && false === strpos( $page, 'Invoices' )
	);
}

unset( $_GET['tab'] );

echo "\n── the review badge still switches on and off ──\n";

$saved = get_option( 'pfh_badge' );

update_option( 'pfh_badge', array_merge( PFH_Widgets_Badge::defaults(), [ 'enabled' => false ] ) );
PFH_Widgets_Badge::forget();
ob_start();
PFH_Widgets_Badge::render();
ok( 'off, it prints nothing', '' === trim( ob_get_clean() ) );

update_option( 'pfh_badge', array_merge( PFH_Widgets_Badge::defaults(), [ 'enabled' => true ] ) );
PFH_Widgets_Badge::forget();
ob_start();
PFH_Widgets_Badge::render();
ok( 'on, it prints the badge', false !== strpos( ob_get_clean(), 'pfh-bdg' ) );

if ( false === $saved ) { delete_option( 'pfh_badge' ); } else { update_option( 'pfh_badge', $saved ); }
PFH_Widgets_Badge::forget();

ok( 'its hooks are in place whatever the setting, and ask it when the page is drawn', false !== has_action( 'wp_footer', [ 'PFH_Widgets_Badge', 'render' ] ) );

echo "\n── the report page ──\n";

$report = PFH_Widgets_Diagnostics::report();

ok( 'it says the plugin does not touch routing', false !== strpos( $report, 'It has no permalink manager' ) );
ok( '  and no longer asks after the cookie banner', false === strpos( $report, 'Cookie banner' ) );

echo "\n$pass passed, $fail failed\n";
