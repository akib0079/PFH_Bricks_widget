<?php
/**
 * Standalone WordPress + WooCommerce stubs for the store-service modules.
 *
 * Deliberately separate from stubs.php: that file exists to render Bricks
 * elements and hard-codes empty term/meta lookups, which is exactly what the
 * permalink and document tests need to drive with real data.
 *
 * Only what the modules actually touch is implemented, and it is implemented
 * to behave like WordPress rather than to make a test pass — a stub that lies
 * is worse than no test.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'OBJECT', 'OBJECT' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'STR_PAD_LEFT_SAFE', STR_PAD_LEFT );

defined( 'MINUTE_IN_SECONDS' ) || define( 'MINUTE_IN_SECONDS', 60 );
defined( 'HOUR_IN_SECONDS' )   || define( 'HOUR_IN_SECONDS', 3600 );
defined( 'DAY_IN_SECONDS' )    || define( 'DAY_IN_SECONDS', 86400 );

/* ---- fake content store ------------------------------------------------- */

$GLOBALS['pfh_db'] = [
	'posts'   => [],
	'terms'   => [],
	'meta'    => [],
	'options' => [
		// WordPress always has these; without them every date formats to "".
		'date_format' => 'j F Y',
		'time_format' => 'H:i',
	],
	'filters' => [],
	'actions' => [],
	'redirect' => null,
];

/**
 * Register a post.
 */
function pfh_seed_post( $id, $name, $type = 'product', $status = 'publish', $parent = 0, $title = '' ) {
	$GLOBALS['pfh_db']['posts'][ $id ] = (object) [
		'ID'          => $id,
		'post_name'   => $name,
		'post_type'   => $type,
		'post_status' => $status,
		'post_parent' => $parent,
		'post_title'  => $title ?: ucfirst( str_replace( '-', ' ', $name ) ),
	];
}

/**
 * Register a term.
 */
function pfh_seed_term( $id, $slug, $parent = 0, $taxonomy = 'product_cat' ) {
	$GLOBALS['pfh_db']['terms'][ $id ] = (object) [
		'term_id'  => $id,
		'slug'     => $slug,
		'name'     => ucfirst( str_replace( '-', ' ', $slug ) ),
		'taxonomy' => $taxonomy,
		'parent'   => $parent,
	];
}

function pfh_seed_meta( $post_id, $key, $value ) {
	$GLOBALS['pfh_db']['meta'][ $post_id . ':' . $key ] = $value;
}

function pfh_reset_db() {
	$GLOBALS['pfh_db']['posts']    = [];
	$GLOBALS['pfh_db']['terms']    = [];
	$GLOBALS['pfh_db']['meta']     = [];
	$GLOBALS['pfh_db']['redirect'] = null;
}

/* ---- escaping ----------------------------------------------------------- */

function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $u ) { return htmlspecialchars( (string) $u, ENT_QUOTES, 'UTF-8' ); }
function esc_url_raw( $u ) { return (string) $u; }
function esc_textarea( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $t, $d = '' ) { return $t; }
function esc_attr__( $t, $d = '' ) { return $t; }
function esc_attr_e( $t, $d = '' ) { echo esc_attr( $t ); }
function esc_html_e( $t, $d = '' ) { echo esc_html( $t ); }
function __( $t, $d = '' ) { return $t; }
function _e( $t, $d = '' ) { echo $t; }
function _x( $t, $c, $d = '' ) { return $t; }
function _n( $s, $p, $n, $d = '' ) { return 1 === (int) $n ? $s : $p; }
function wp_kses_post( $t ) { return $t; }
function wp_strip_all_tags( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_text_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_textarea_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_key( $t ) { return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $t ) ); }
function sanitize_file_name( $t ) { return preg_replace( '/[^A-Za-z0-9._-]/', '-', (string) $t ); }
function sanitize_html_class( $t ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $t ); }
function absint( $v ) { return abs( (int) $v ); }
function wp_unslash( $v ) { return $v; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function number_format_i18n( $n, $dec = 0 ) { return number_format( (float) $n, (int) $dec ); }

/* ---- hooks -------------------------------------------------------------- */

function add_filter( $tag, $cb, $priority = 10, $args = 1 ) {
	$GLOBALS['pfh_db']['filters'][ $tag ][] = $cb;
	return true;
}

function add_action( $tag, $cb = null, $priority = 10, $args = 1 ) {
	$GLOBALS['pfh_db']['actions'][ $tag ][] = $cb;
	return true;
}

function apply_filters( $tag, $value ) {
	$args = array_slice( func_get_args(), 2 );

	foreach ( $GLOBALS['pfh_db']['filters'][ $tag ] ?? [] as $cb ) {
		$value = call_user_func_array( $cb, array_merge( [ $value ], $args ) );
	}

	return $value;
}

function do_action( $tag ) {
	$args = array_slice( func_get_args(), 1 );

	foreach ( $GLOBALS['pfh_db']['actions'][ $tag ] ?? [] as $cb ) {
		call_user_func_array( $cb, $args );
	}
}

function add_shortcode() {}
function shortcode_atts( $pairs, $atts, $shortcode = '' ) { return array_merge( $pairs, (array) $atts ); }
function register_activation_hook() {}
function register_deactivation_hook() {}
function wp_clear_scheduled_hook() {}
function flush_rewrite_rules() {}
function add_meta_box() {}
function add_menu_page() {}
function wp_enqueue_media() {}
function nocache_headers() {}
function wp_die( $m = '', $c = 0 ) { throw new RuntimeException( 'wp_die: ' . $m ); }

/* ---- urls --------------------------------------------------------------- */

function home_url( $p = '' ) { return 'https://productsforhome.nl/' . ltrim( (string) $p, '/' ); }
function admin_url( $p = '' ) { return 'https://productsforhome.nl/wp-admin/' . ltrim( (string) $p, '/' ); }
function get_bloginfo( $k = '' ) { return 'Products For Home'; }
function plugin_dir_path( $f ) { return dirname( $f ) . '/'; }
function plugin_dir_url( $f ) { return './'; }
function trailingslashit( $s ) { return rtrim( (string) $s, '/\\' ) . '/'; }
function untrailingslashit( $s ) { return rtrim( (string) $s, '/' ); }
function user_trailingslashit( $s, $ctx = '' ) { return trailingslashit( $s ); }
function is_ssl() { return true; }
function is_admin() { return false; }
function is_wp_error( $t ) { return $t instanceof WP_Error; }
function wp_nonce_url( $url, $action = '' ) { return $url . '&_wpnonce=test'; }
function wp_create_nonce( $a = '' ) { return 'testnonce'; }
function wp_verify_nonce( $n, $a = '' ) { return 'test' === $n || 'testnonce' === $n; }
function wp_nonce_field() {}
function check_admin_referer() { return true; }
function check_ajax_referer() { return true; }
function wp_safe_redirect( $url, $status = 302 ) {
	$GLOBALS['pfh_db']['redirect'] = [ 'url' => $url, 'status' => $status ];
	throw new PFH_Redirect( $url );
}

class PFH_Redirect extends RuntimeException {}

function add_query_arg( $args, $url = '' ) {
	if ( ! is_array( $args ) || ! $args ) {
		return $url;
	}

	$join = false === strpos( (string) $url, '?' ) ? '?' : '&';

	return $url . $join . http_build_query( $args );
}

/* ---- options ------------------------------------------------------------ */

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['pfh_db']['options'] )
		? $GLOBALS['pfh_db']['options'][ $name ]
		: $default;
}

function update_option( $name, $value, $autoload = null ) {
	$GLOBALS['pfh_db']['options'][ $name ] = $value;
	return true;
}

function add_option( $name, $value, $x = '', $autoload = 'yes' ) {
	if ( array_key_exists( $name, $GLOBALS['pfh_db']['options'] ) ) {
		return false;
	}

	$GLOBALS['pfh_db']['options'][ $name ] = $value;
	return true;
}

function delete_option( $name ) { unset( $GLOBALS['pfh_db']['options'][ $name ] ); return true; }
function wp_cache_delete() { return true; }
function get_transient( $k ) { return $GLOBALS['pfh_db']['options'][ '_t_' . $k ] ?? false; }
function set_transient( $k, $v, $ttl = 0 ) { $GLOBALS['pfh_db']['options'][ '_t_' . $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['pfh_db']['options'][ '_t_' . $k ] ); return true; }

/* ---- posts and terms ---------------------------------------------------- */

function get_post_meta( $id, $key = '', $single = false ) {
	$value = $GLOBALS['pfh_db']['meta'][ $id . ':' . $key ] ?? '';

	return $single ? $value : ( '' === $value ? [] : [ $value ] );
}

/**
 * Path lookup, matching WordPress's own precedence.
 *
 * Hierarchical types resolve the whole path through post_parent; flat types
 * match the last segment on post_name, which is what WordPress does.
 */
function get_page_by_path( $path, $output = OBJECT, $type = 'page' ) {
	$path     = trim( (string) $path, '/' );
	$segments = explode( '/', $path );
	$leaf     = end( $segments );

	foreach ( $GLOBALS['pfh_db']['posts'] as $post ) {
		if ( $post->post_type !== $type || $post->post_name !== $leaf ) {
			continue;
		}

		if ( 'page' !== $type ) {
			return $post;
		}

		// Walk the ancestry back up and compare it with the requested path.
		$trail  = [ $post->post_name ];
		$parent = (int) $post->post_parent;

		while ( $parent && isset( $GLOBALS['pfh_db']['posts'][ $parent ] ) ) {
			$ancestor = $GLOBALS['pfh_db']['posts'][ $parent ];
			array_unshift( $trail, $ancestor->post_name );
			$parent = (int) $ancestor->post_parent;
		}

		if ( implode( '/', $trail ) === $path ) {
			return $post;
		}
	}

	return null;
}

function get_term( $id, $taxonomy = '' ) {
	$term = $GLOBALS['pfh_db']['terms'][ (int) $id ] ?? null;

	if ( $term && $taxonomy && $term->taxonomy !== $taxonomy ) {
		return null;
	}

	return $term;
}

function get_term_by( $field, $value, $taxonomy = '' ) {
	foreach ( $GLOBALS['pfh_db']['terms'] as $term ) {
		if ( 'slug' === $field && $term->slug === $value && ( ! $taxonomy || $term->taxonomy === $taxonomy ) ) {
			return $term;
		}
	}

	return false;
}

function get_the_terms( $post_id, $taxonomy ) {
	$ids = $GLOBALS['pfh_db']['meta'][ $post_id . ':terms' ] ?? [];
	$out = [];

	foreach ( (array) $ids as $id ) {
		$term = get_term( $id, $taxonomy );

		if ( $term ) {
			$out[] = $term;
		}
	}

	return $out ?: false;
}

function get_term_link( $term, $taxonomy = '' ) {
	$slug = is_object( $term ) ? $term->slug : $term;

	return home_url( 'product-category/' . $slug . '/' );
}

function get_permalink( $id = 0 ) {
	$post = $GLOBALS['pfh_db']['posts'][ (int) $id ] ?? null;

	if ( ! $post ) {
		return home_url( '?p=' . (int) $id );
	}

	return apply_filters( 'post_type_link', home_url( 'product/' . $post->post_name . '/' ), $post );
}

function get_attached_file( $id ) { return ''; }
function wp_get_attachment_image_url( $id, $size = '' ) { return ''; }

/* ---- query conditionals (documents / canonical) ------------------------- */

$GLOBALS['pfh_query'] = [];

function is_singular( $type = '' ) { return ( $GLOBALS['pfh_query']['singular'] ?? '' ) === $type; }
function is_tax( $tax = '' ) { return ( $GLOBALS['pfh_query']['tax'] ?? '' ) === $tax; }
function is_feed() { return ! empty( $GLOBALS['pfh_query']['feed'] ); }
function is_embed() { return ! empty( $GLOBALS['pfh_query']['embed'] ); }
function get_queried_object() { return $GLOBALS['pfh_query']['object'] ?? null; }
function get_queried_object_id() { return (int) ( $GLOBALS['pfh_query']['object_id'] ?? 0 ); }
function get_query_var( $var, $default = '' ) { return $GLOBALS['pfh_query'][ $var ] ?? $default; }

/* ---- dates -------------------------------------------------------------- */

function current_time( $type = 'mysql' ) { return gmdate( 'Y-m-d H:i:s' ); }
function wp_date( $format, $ts = null ) { return gmdate( $format, $ts ?: time() ); }
function mysql2date( $format, $date ) { return $date ? gmdate( $format, strtotime( $date ) ) : ''; }
function date_i18n( $format, $ts = null ) { return gmdate( $format, $ts ?: time() ); }

/* ---- misc --------------------------------------------------------------- */

function current_user_can() { return true; }
function get_current_user_id() { return 1; }
function get_temp_dir() { return sys_get_temp_dir() . '/'; }
function wp_is_writable( $p ) { return is_writable( $p ); }
function wp_delete_file( $p ) { @unlink( $p ); }
function checked( $a, $b = true, $echo = true ) { return $a == $b ? ' checked' : ''; }
function selected( $a, $b = true, $echo = true ) { return $a == $b ? ' selected' : ''; }
function submit_button() {}
function wp_register_style() {}
function wp_register_script() {}
function wp_enqueue_style() {}
function wp_enqueue_script() {}
function wp_localize_script() {}
function wp_style_is() { return true; }
function wp_remote_get() { return new WP_Error( 'offline', 'no network in tests' ); }
function wp_remote_retrieve_response_code( $r ) { return 0; }
function wp_remote_retrieve_body( $r ) { return ''; }
function wp_parse_args( $args, $defaults = [] ) {
	return array_merge( (array) $defaults, array_filter( (array) $args, static function ( $v ) { return null !== $v; } ) );
}

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

/* ---- WooCommerce -------------------------------------------------------- */

function wc_get_permalink_structure() {
	return [
		'product_rewrite_slug'  => 'product',
		'category_rewrite_slug' => 'product-category',
	];
}

function wc_price( $amount, $args = [] ) {
	return '<span class="amount">&euro;' . number_format( (float) $amount, 2, ',', '.' ) . '</span>';
}

function wc_display_item_meta( $item, $args = [] ) {
	$meta = $item->pfh_meta ?? '';

	return $meta ? '<ul class="wc-item-meta"><li><strong>Maat:</strong> ' . $meta . '</li></ul>' : '';
}

function wc_get_order( $id ) { return $GLOBALS['pfh_orders'][ (int) $id ] ?? false; }
function wc_get_page_screen_id( $x ) { return 'woocommerce_page_wc-orders'; }

/**
 * Just enough WC_Order for the document renderer.
 */
class WC_Order {
	public $id;
	public $meta = [];
	public $items = [];
	public $number;
	public $currency = 'EUR';
	public $payment = 'iDEAL';
	public $billing = '';
	public $shipping = '';
	public $totals = [];
	public $customer = 0;

	public function __construct( $id ) {
		$this->id     = $id;
		$this->number = (string) $id;
	}

	public function get_id() { return $this->id; }
	public function get_order_number() { return $this->number; }
	public function get_currency() { return $this->currency; }
	public function get_customer_id() { return $this->customer; }
	public function get_payment_method_title() { return $this->payment; }
	public function get_formatted_billing_address() { return $this->billing; }
	public function get_formatted_shipping_address() { return $this->shipping; }
	public function get_formatted_order_total() { return wc_price( 0 ); }
	public function get_items() { return $this->items; }
	public function get_order_item_totals() { return $this->totals; }
	public function get_date_created() { return new PFH_Date(); }

	public function get_meta( $key, $single = true ) { return $this->meta[ $key ] ?? ''; }
	public function update_meta_data( $key, $value ) { $this->meta[ $key ] = $value; }
	public function save() { return true; }

	public function get_item_total( $item, $incl = false, $round = true ) {
		return $item->pfh_price;
	}

	public function get_line_total( $item, $incl = false, $round = true ) {
		return $item->pfh_price * $item->pfh_qty;
	}
}

class PFH_Date {
	public function getTimestamp() { return time(); }
}

class WC_Order_Item {
	public $pfh_name;
	public $pfh_qty;
	public $pfh_price;
	public $pfh_sku;
	public $pfh_meta = '';

	public function __construct( $name, $qty, $price, $sku = '' ) {
		$this->pfh_name  = $name;
		$this->pfh_qty   = $qty;
		$this->pfh_price = $price;
		$this->pfh_sku   = $sku;
	}

	public function get_name() { return $this->pfh_name; }
	public function get_quantity() { return $this->pfh_qty; }
	public function get_product() { return new PFH_Stub_Product( $this->pfh_sku ); }
}

class PFH_Stub_Product {
	private $sku;

	public function __construct( $sku ) { $this->sku = $sku; }
	public function get_sku() { return $this->sku; }
}

$GLOBALS['pfh_orders'] = [];

/* ---- $wpdb -------------------------------------------------------------- */

class PFH_Stub_WPDB {
	public $prefix = 'wp_';
	public $options = 'wp_options';
	public $posts = 'wp_posts';
	public $insert_id = 0;

	public function prepare( $sql, ...$args ) {
		foreach ( $args as $arg ) {
			$sql = preg_replace( '/%[ds]/', is_numeric( $arg ) ? (string) $arg : "'" . $arg . "'", $sql, 1 );
		}

		return $sql;
	}

	public function query( $sql ) {
		// Emulate the atomic invoice counter.
		if ( preg_match( "/option_value \+ 1.*option_name = '([^']+)'/s", $sql, $m ) ) {
			$name = $m[1];

			if ( ! array_key_exists( $name, $GLOBALS['pfh_db']['options'] ) ) {
				return 0;
			}

			$GLOBALS['pfh_db']['options'][ $name ] = (int) $GLOBALS['pfh_db']['options'][ $name ] + 1;
			$this->insert_id                       = $GLOBALS['pfh_db']['options'][ $name ];

			return 1;
		}

		return 0;
	}

	public function get_results( $sql, $mode = OBJECT ) { return []; }
	public function get_col( $sql ) { return []; }
	public function get_charset_collate() { return ''; }
	public function esc_like( $s ) { return $s; }
	public function insert( $table, $data, $format = [] ) { return 1; }
}

$GLOBALS['wpdb'] = new PFH_Stub_WPDB();

/* ---- load the plugin ---------------------------------------------------- */

define( 'PFH_WIDGETS_VERSION', 'test' );
define( 'PFH_WIDGETS_FILE', dirname( __DIR__ ) . '/pfh-bricks-widgets/pfh-bricks-widgets.php' );
define( 'PFH_WIDGETS_DIR', dirname( __DIR__ ) . '/pfh-bricks-widgets/' );
define( 'PFH_WIDGETS_URL', './' );

require PFH_WIDGETS_DIR . 'includes/class-pfh-helpers.php';
require PFH_WIDGETS_DIR . 'includes/class-pfh-reviews.php';
require PFH_WIDGETS_DIR . 'includes/class-pfh-settings.php';
require PFH_WIDGETS_DIR . 'includes/class-pfh-consent.php';
require PFH_WIDGETS_DIR . 'includes/class-pfh-permalinks.php';
// The badge resolves its mark through the asset layer, which is where the
// artwork's hosted location is decided.
require PFH_WIDGETS_DIR . 'includes/class-pfh-assets.php';
require PFH_WIDGETS_DIR . 'includes/class-pfh-badge.php';
require PFH_WIDGETS_DIR . 'includes/class-pfh-pdf.php';
require PFH_WIDGETS_DIR . 'includes/class-pfh-documents.php';

/**
 * Minimal WP request object, as parse_request hands it over.
 */
class WP {
	public $request = '';
	public $query_vars = [];

	public function __construct( $request = '', array $vars = [] ) {
		$this->request    = $request;
		$this->query_vars = $vars;
	}
}
