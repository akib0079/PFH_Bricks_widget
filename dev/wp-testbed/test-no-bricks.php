<?php
/**
 * The plugin on a real WordPress with no Bricks at all.
 *
 * This is the condition that took a site down: plugins load before themes, so
 * at plugins_loaded the class every element extends does not exist. The
 * failure was not a missing feature — it was "Class Bricks\Element not found"
 * on every request, wp-admin included, with no way in to deactivate.
 *
 * Run it with the Bricks stub moved out of mu-plugins:
 *
 *   mv wp-content/mu-plugins/bricks-stub.php /tmp/
 *   curl .../pfh-test-no-bricks.php
 *   mv /tmp/bricks-stub.php wp-content/mu-plugins/
 *
 * The stub is an mu-plugin, and mu-plugins load *before* plugins — which is
 * exactly why every other suite here passed on the build that was broken.
 */
define( 'WP_ADMIN', true );
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';
header( 'Content-Type: text/plain' );

$pass = 0; $fail = 0;
function ok( $l, $c, $d = '' ) { global $pass, $fail; if ( $c ) { $pass++; echo "  ok   $l\n"; } else { $fail++; echo "  FAIL $l" . ( $d ? " — $d" : '' ) . "\n"; } }

if ( class_exists( '\Bricks\Element' ) ) {
	echo "SKIPPED: Bricks is loaded, so the ordering this guards is not in play.\n";
	echo "Move wp-content/mu-plugins/bricks-stub.php aside and run it again.\n";
	exit;
}

echo "── it boots ──\n";
ok( 'the plugin is active', in_array( 'pfh-bricks-widgets/pfh-bricks-widgets.php', (array) get_option( 'active_plugins' ), true ) );
ok( 'it reached its bootstrap', defined( 'PFH_WIDGETS_VERSION' ) );
ok( 'its services are up', class_exists( 'PFH_Widgets_Assets' ) && class_exists( 'PFH_Widgets_Consent' ) && class_exists( 'PFH_Widgets_Permalinks' ) );

echo "\n── and it loaded no element while doing so ──\n";
foreach ( [ 'PFH_Element_Recent', 'PFH_Element_Products', 'PFH_Element_Archive', 'PFH_Element_Highlight' ] as $class ) {
	ok( "$class was not loaded at boot", ! class_exists( $class, false ), 'loading it here is the fatal' );
}

echo "\n── the admin is reachable, which is what a white screen takes away ──\n";
$user = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
ok( 'there is an administrator to test as', ! empty( $user ) );

if ( $user ) {
	wp_set_current_user( $user[0]->ID );

	foreach ( [ 'admin_init', 'admin_menu', 'admin_enqueue_scripts', 'admin_notices' ] as $hook ) {
		ob_start();
		do_action( $hook );
		ob_end_clean();
		ok( "$hook ran without dying", true );
	}

	global $menu, $submenu;
	$found = false;

	foreach ( array_merge( [ (array) $menu ], array_values( (array) $submenu ) ) as $items ) {
		foreach ( (array) $items as $item ) {
			if ( is_array( $item ) && isset( $item[2] ) && false !== strpos( (string) $item[2], 'pfh' ) ) {
				$found = true;
				break 2;
			}
		}
	}

	ok( 'the settings screen is registered', $found );

	ob_start();
	PFH_Widgets_Settings::render();
	$html = ob_get_clean();
	ok( 'and renders', strlen( $html ) > 1000, strlen( $html ) . ' bytes' );
}

echo "\n── it says so, rather than dying ──\n";
ob_start();
do_action( 'admin_notices' );
$notices = wp_strip_all_tags( ob_get_clean() );
ok( 'an admin notice explains that Bricks is needed', false !== stripos( $notices, 'Bricks' ), $notices );

echo "\n── activation is clean too ──\n";
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$slug = 'pfh-bricks-widgets/pfh-bricks-widgets.php';
$rules_before = count( (array) get_option( 'rewrite_rules' ) );

deactivate_plugins( $slug );
$err = activate_plugin( $slug );

ok( 'it reactivates without error', ! is_wp_error( $err ), is_wp_error( $err ) ? $err->get_error_message() : '' );
ok( 'and the rewrite rules survive', count( (array) get_option( 'rewrite_rules' ) ) >= $rules_before - 1 );

echo "\n$pass passed, $fail failed\n";
