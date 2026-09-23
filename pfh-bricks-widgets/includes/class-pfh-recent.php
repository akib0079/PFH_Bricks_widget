<?php
/**
 * Remembering which products a visitor has looked at.
 *
 * WooCommerce keeps that list in its `woocommerce_recently_viewed` cookie —
 * but only writes the cookie while its own "Recently Viewed Products" sidebar
 * widget is active. A Bricks site has no sidebar widgets, so the cookie was
 * never written, the list was always empty, and the recently viewed section
 * never had anything to show.
 *
 * So the plugin keeps the list itself, in the same cookie and the same format,
 * which means anything else reading WooCommerce's list sees it too.
 *
 * It is written in the browser, not by PHP, for two reasons. A product page
 * served from the page cache never reaches PHP, so a server-side write would
 * miss most views. And a Set-Cookie header on the response is exactly what
 * makes a host's page cache refuse to store that page — tracking views that
 * way would have made every product page uncacheable.
 *
 * It is a first-party, session-only cookie that the shop needs to show the
 * visitor their own history; no personal data leaves the browser.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Recent {

	const COOKIE = 'woocommerce_recently_viewed';

	/** WooCommerce keeps fifteen; so does this. */
	const KEEP = 15;

	public static function init() {
		add_action( 'wp_footer', [ __CLASS__, 'script' ], 50 );
	}

	/**
	 * On a product page, put this product at the end of the visitor's list.
	 *
	 * Printed inline with the product's id in it. The id belongs to the page,
	 * so a cached copy of the page carries the right one.
	 */
	public static function script() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		$id = (int) get_queried_object_id();

		if ( ! $id ) {
			return;
		}

		$path = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		?>
<script id="pfh-recent-track">(function(){try{var n=<?php echo wp_json_encode( self::COOKIE ); ?>,id=<?php echo (int) $id; ?>,m=document.cookie.match(new RegExp('(?:^|; )'+n+'=([^;]*)')),l=m?decodeURIComponent(m[1]).split('|'):[];l=l.map(function(v){return parseInt(v,10)}).filter(function(v){return v>0&&v!==id});l.push(id);if(l.length><?php echo (int) self::KEEP; ?>){l=l.slice(-<?php echo (int) self::KEEP; ?>)}document.cookie=n+'='+encodeURIComponent(l.join('|'))+'; path='+<?php echo wp_json_encode( $path ); ?>+'; SameSite=Lax'+('https:'===location.protocol?'; Secure':'')}catch(e){}})();</script>
		<?php
	}
}
