<?php
/**
 * Minimal WordPress + Bricks stubs so the elements can be rendered outside WP.
 * Used only to smoke-test the render path and produce a visual preview.
 */

define( 'ABSPATH', __DIR__ . '/' );

// ---- Escaping ----------------------------------------------------------
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $u ) { return htmlspecialchars( (string) $u, ENT_QUOTES, 'UTF-8' ); }
function esc_url_raw( $u ) { return (string) $u; }
function esc_html__( $t, $d = '' ) { return $t; }
function esc_attr__( $t, $d = '' ) { return $t; }
function esc_attr_e( $t, $d = '' ) { echo esc_attr( $t ); }
function esc_html_e( $t, $d = '' ) { echo esc_html( $t ); }
function __( $t, $d = '' ) { return $t; }
function _n( $single, $plural, $n, $d = '' ) { return 1 === (int) $n ? $single : $plural; }
function number_format_i18n( $n, $dec = 0 ) { return number_format( (float) $n, (int) $dec ); }
function _x( $t, $ctx, $d = '' ) { return $t; }
function wp_kses_post( $t ) { return $t; }

// ---- Core --------------------------------------------------------------
function add_action() {}

/* A real-enough filter registry, so the preview can exercise the hooks the
   plugin actually ships (the WebwinkelKeur feed in particular). */
$GLOBALS['pfh_filters'] = [];
function add_filter( $tag, $callback, $priority = 10 ) {
	$GLOBALS['pfh_filters'][ $tag ][] = [ 'cb' => $callback, 'p' => $priority ];
	usort( $GLOBALS['pfh_filters'][ $tag ], function ( $a, $b ) { return $a['p'] <=> $b['p']; } );
}
function apply_filters( $tag, $value ) {
	$args = array_slice( func_get_args(), 2 );

	foreach ( (array) ( $GLOBALS['pfh_filters'][ $tag ] ?? [] ) as $hook ) {
		$value = call_user_func_array( $hook['cb'], array_merge( [ $value ], $args ) );
	}

	return $value;
}
function home_url( $p = '' ) { return 'https://productsforhome.nl/' . ltrim( (string) $p, '/' ); }
function admin_url( $p = '' ) { return 'https://productsforhome.nl/wp-admin/' . ltrim( (string) $p, '/' ); }
function wp_login_url() { return home_url( 'wp-login.php' ); }
function get_bloginfo( $k ) { return 'Products For Home'; }
function plugin_dir_path( $f ) { return dirname( $f ) . '/'; }
function plugin_dir_url( $f ) { return './'; }
function is_admin() { return false; }
function is_wp_error( $t ) { return $t instanceof WP_Error; }
function untrailingslashit( $s ) { return rtrim( (string) $s, '/' ); }
function sanitize_html_class( $c ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $c ); }
function sanitize_key( $c ) { return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $c ) ); }
function sanitize_text_field( $c ) { return trim( strip_tags( (string) $c ) ); }
function absint( $v ) { return abs( (int) $v ); }
function wp_unslash( $v ) { return $v; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function current_user_can() { return true; }
function do_shortcode( $s ) { return $s; }
function wpautop( $t, $br = true ) { $t = trim( (string) $t ); if ( '' === $t ) return ''; return '<p>' . str_replace( "\n\n", '</p><p>', $t ) . '</p>'; }
function wp_create_nonce( $a ) { return 'testnonce'; }
function check_ajax_referer() { return true; }
class PFH_Test_Die extends \Exception {}
// Real wp_send_json_* call wp_die(); mirror that so guard clauses actually stop.
function wp_send_json_success( $d ) { echo json_encode( $d ); throw new PFH_Test_Die( 'success' ); }
function wp_send_json_error( $d, $c = 200 ) { echo json_encode( $d ); throw new PFH_Test_Die( 'error' ); }
function wp_reset_postdata() {}
function post_type_exists( $t ) { return 'product' === $t; }

// ---- Assets ------------------------------------------------------------
function wp_register_style() {}
function wp_register_script() {}
function wp_enqueue_style() {}
function wp_enqueue_script() {}
function wp_localize_script() {}
function wp_style_is() { return true; }

// ---- Content -----------------------------------------------------------
function get_permalink( $id ) { return home_url( 'p/' . (int) $id ); }
function get_the_title( $p = 0 ) { return 'Product ' . ( is_object( $p ) ? $p->ID : $p ); }
function get_the_post_thumbnail_url() { return ''; }
function get_the_post_thumbnail() { return ''; }
function get_post_type() { return 'product'; }
function wp_get_attachment_image_src( $id, $size ) { return false; }
function wp_get_attachment_image_url( $id, $size ) { return ''; }
function wp_get_nav_menus() { return []; }
function wp_get_nav_menu_items() { return []; }
function taxonomy_exists( $t ) { return false; }
function get_terms( $a = [] ) { return []; }
function get_term( $id, $tax ) { return null; }
function get_term_by() { return null; }
function get_term_link( $t ) { return home_url( 'c/' ); }
function get_term_meta() { return ''; }
function is_tax( $t = '' ) { return false; }
function is_search() { return false; }
function is_singular( $t = '' ) { return false; }
function is_page( $p = '' ) { return false; }
function is_product_tag() { return false; }
function get_search_query() { return ''; }
function is_shop() { return false; }
function get_queried_object() { return null; }
function wc_get_page_id( $p ) { return 0; }
function wp_strip_all_tags( $t ) { return trim( strip_tags( (string) $t ) ); }
function get_taxonomy( $t ) { return null; }
function get_object_taxonomies() { return []; }
function get_posts( $a = [] ) { return []; }
function set_transient_stub() {}


require __DIR__ . '/stubs-bricks.php';

/* ---- Time constants ---------------------------------------------------- */
defined( 'MINUTE_IN_SECONDS' ) || define( 'MINUTE_IN_SECONDS', 60 );
defined( 'HOUR_IN_SECONDS' )   || define( 'HOUR_IN_SECONDS', 3600 );
defined( 'DAY_IN_SECONDS' )    || define( 'DAY_IN_SECONDS', 86400 );

/* ---- Options / transients / cron --------------------------------------- */
$GLOBALS['pfh_transients'] = [];
function get_transient( $key ) { return $GLOBALS['pfh_transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl = 0 ) { $GLOBALS['pfh_transients'][ $key ] = $value; return true; }
function delete_transient( $key ) { unset( $GLOBALS['pfh_transients'][ $key ] ); return true; }
function wp_next_scheduled( $hook ) { return time() + 3600; }
function wp_schedule_event() { return true; }
function get_post_meta( $id, $key = '', $single = false ) { return $single ? '' : []; }

/* ---- HTTP -------------------------------------------------------------- */
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;

		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		public function get_error_message() { return $this->message; }
		public function get_error_code() { return $this->code; }
	}
}

function wp_parse_args( $args, $defaults = [] ) {
	return array_merge( (array) $defaults, array_filter( (array) $args, function ( $v ) { return null !== $v; } ) );
}
function add_query_arg( $args, $url = '' ) {
	$sep = false === strpos( (string) $url, '?' ) ? '?' : '&';

	return $url . $sep . http_build_query( (array) $args );
}
function wp_remote_get( $url, $args = [] ) {
	// The harness never talks to the network; the pre_reviews filter is the
	// documented way to feed the widget instead.
	return new WP_Error( 'pfh_offline', 'The dev harness does not make HTTP requests.' );
}
function wp_remote_retrieve_response_code( $r ) { return is_array( $r ) ? ( $r['response']['code'] ?? 0 ) : 0; }
function wp_remote_retrieve_body( $r ) { return is_array( $r ) ? ( $r['body'] ?? '' ) : ''; }
function date_i18n( $format, $stamp = null ) { return gmdate( $format, $stamp ?: time() ); }
