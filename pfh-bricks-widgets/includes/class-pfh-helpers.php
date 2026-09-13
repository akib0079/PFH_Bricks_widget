<?php
/**
 * Shared helpers for the Products For Home Bricks elements.
 *
 * Everything in here is deliberately defensive: control values coming out of
 * Bricks can be strings, arrays or missing entirely depending on how the user
 * touched the control, so each getter normalises before it returns.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Helpers {

	/**
	 * Transient holding the product category list for front-end requests.
	 */
	const CAT_CACHE = 'pfh_product_cat_options';

	/**
	 * Run a string through the Bricks dynamic data parser when available.
	 *
	 * @param string $value   Raw control value, may contain {dynamic_tags}.
	 * @param int    $post_id Post context.
	 * @return string
	 */
	public static function dd( $value, $post_id = 0 ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return is_string( $value ) ? $value : '';
		}

		if ( false === strpos( $value, '{' ) ) {
			return $value;
		}

		if ( function_exists( 'bricks_render_dynamic_data' ) ) {
			return bricks_render_dynamic_data( $value, $post_id );
		}

		return $value;
	}

	/**
	 * Normalise a Bricks colour control value into a CSS colour string.
	 *
	 * @param mixed  $color    Control value.
	 * @param string $fallback Returned when nothing usable is set.
	 * @return string
	 */
	public static function color( $color, $fallback = '' ) {
		if ( empty( $color ) ) {
			return $fallback;
		}

		if ( is_string( $color ) ) {
			return $color;
		}

		if ( ! is_array( $color ) ) {
			return $fallback;
		}

		foreach ( [ 'raw', 'hex', 'rgb', 'hsl' ] as $key ) {
			if ( ! empty( $color[ $key ] ) && is_string( $color[ $key ] ) ) {
				return $color[ $key ];
			}
		}

		return $fallback;
	}

	/**
	 * Normalise a Bricks image control value into a URL.
	 *
	 * @param mixed  $image Control value.
	 * @param string $size  Preferred registered image size.
	 * @return string
	 */
	/**
	 * The products this visitor has looked at, newest first.
	 *
	 * WooCommerce keeps them in its own cookie — `wc_track_product_view()`
	 * appends each product page as it is viewed and trims the list to 15, so
	 * the newest is last and this reverses it. Reading Woo's cookie rather
	 * than keeping a second list means the history is already there for every
	 * visitor who has browsed the shop, and it stays in step with whatever
	 * WooCommerce itself shows.
	 *
	 * @param int $limit Most to return.
	 * @return int[]
	 */
	public static function recently_viewed( $limit = 12 ) {
		if ( empty( $_COOKIE['woocommerce_recently_viewed'] ) ) {
			return [];
		}

		$raw = (string) wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitised -- cast to int below.
		$ids = array_filter( array_map( 'absint', explode( '|', $raw ) ) );

		// Newest last in the cookie; newest first on the page.
		$ids = array_reverse( array_values( array_unique( $ids ) ) );

		return array_slice( $ids, 0, max( 1, (int) $limit ) );
	}

	public static function image_url( $image, $size = 'medium_large' ) {
		if ( empty( $image ) ) {
			return '';
		}

		if ( is_string( $image ) ) {
			return $image;
		}

		if ( ! is_array( $image ) ) {
			return '';
		}

		if ( ! empty( $image['id'] ) ) {
			$requested = ! empty( $image['size'] ) ? $image['size'] : $size;
			$src       = wp_get_attachment_image_src( (int) $image['id'], $requested );

			if ( ! empty( $src[0] ) ) {
				return $src[0];
			}
		}

		if ( ! empty( $image['url'] ) ) {
			return $image['url'];
		}

		return ! empty( $image['full'] ) ? $image['full'] : '';
	}

	/**
	 * Resolve a Bricks link control into href / target / rel.
	 *
	 * We do the resolving ourselves instead of leaning on Bricks internals so
	 * the elements keep working across Bricks releases.
	 *
	 * @param mixed  $link     Control value.
	 * @param string $fallback Href used when the control is empty.
	 * @return array{href:string, target:string, rel:string, aria:string}
	 */
	public static function link( $link, $fallback = '' ) {
		$out = [
			'href'   => $fallback,
			'target' => '',
			'rel'    => '',
			'aria'   => '',
		];

		if ( empty( $link ) || ! is_array( $link ) ) {
			return $out;
		}

		$type = isset( $link['type'] ) ? $link['type'] : 'external';

		if ( 'internal' === $type && ! empty( $link['postId'] ) ) {
			$permalink = get_permalink( (int) $link['postId'] );

			if ( $permalink ) {
				$out['href'] = $permalink;
			}
		} elseif ( ! empty( $link['url'] ) ) {
			$out['href'] = self::dd( $link['url'] );
		}

		if ( ! empty( $link['newTab'] ) ) {
			$out['target'] = '_blank';
			$out['rel']    = 'noopener noreferrer';
		}

		if ( ! empty( $link['rel'] ) ) {
			$out['rel'] = trim( $out['rel'] . ' ' . $link['rel'] );
		}

		if ( ! empty( $link['ariaLabel'] ) ) {
			$out['aria'] = $link['ariaLabel'];
		}

		return $out;
	}

	/**
	 * Build the attribute string for a resolved link.
	 *
	 * @param array $link Result of self::link().
	 * @return string
	 */
	public static function link_attrs( array $link ) {
		$attrs = ' href="' . esc_url( $link['href'] ) . '"';

		if ( ! empty( $link['target'] ) ) {
			$attrs .= ' target="' . esc_attr( $link['target'] ) . '"';
		}

		if ( ! empty( $link['rel'] ) ) {
			$attrs .= ' rel="' . esc_attr( $link['rel'] ) . '"';
		}

		if ( ! empty( $link['aria'] ) ) {
			$attrs .= ' aria-label="' . esc_attr( $link['aria'] ) . '"';
		}

		return $attrs;
	}

	/**
	 * Parse a "Label | URL | Image" textarea into rows of trimmed parts.
	 *
	 * @param string $text Raw textarea value.
	 * @return array<int, array<int, string>>
	 */
	public static function parse_lines( $text ) {
		$rows = [];

		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			return $rows;
		}

		$lines = preg_split( '/\r\n|\r|\n/', $text );

		foreach ( (array) $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			$rows[] = array_map( 'trim', explode( '|', $line ) );
		}

		return $rows;
	}

	/**
	 * Split a textarea into trimmed, non-empty lines.
	 *
	 * Unlike parse_lines() this does not treat "|" as a column separator, so
	 * it is the right one for plain lists — script handles, hostnames, address
	 * lines — where a pipe is just a character.
	 *
	 * @param string $text Raw textarea value.
	 * @return string[]
	 */
	public static function lines( $text ) {
		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			return [];
		}

		$out = [];

		foreach ( (array) preg_split( '/\r\n|\r|\n/', $text ) as $line ) {
			$line = trim( $line );

			if ( '' !== $line ) {
				$out[] = $line;
			}
		}

		return $out;
	}

	/**
	 * Turn a map of CSS custom properties into an inline style string.
	 *
	 * Empty values are dropped so the stylesheet default wins.
	 *
	 * @param array<string, mixed> $vars Property => value.
	 * @return string
	 */
	public static function css_vars( array $vars ) {
		$style = '';

		foreach ( $vars as $property => $value ) {
			if ( null === $value || '' === $value || false === $value ) {
				continue;
			}

			// Values land in an HTML style attribute, so drop anything that
			// could terminate the declaration or the attribute itself.
			$value = str_replace( [ '"', "'", '<', '>', ';', '{', '}' ], '', (string) $value );

			if ( '' === trim( $value ) ) {
				continue;
			}

			$style .= $property . ':' . trim( $value ) . ';';
		}

		return $style;
	}

	/**
	 * Append a unit to a numeric control value.
	 *
	 * Passes through values that already carry a unit or CSS function.
	 *
	 * @param mixed  $value Control value.
	 * @param string $unit  Unit to append.
	 * @return string
	 */
	/**
	 * Turn a px size authored against the design frame into a fluid one.
	 *
	 * A decoration drawn 160px wide in a 1440 frame should be ~285px on a
	 * 2560 monitor, not still 160 — otherwise it shrinks away visually as the
	 * screen grows. The clamp keeps it sane at both extremes.
	 *
	 * @param mixed $value Authored px size.
	 * @param int   $frame Design frame width.
	 * @param float $min   Lower bound as a share of the authored size.
	 * @param float $max   Upper bound as a share of the authored size.
	 * @return string A CSS clamp(), or a plain px value when input is unusable.
	 */
	/**
	 * Edge offsets for a decoration pinned to a corner.
	 *
	 * Pinning beats a percentage position because the offset from the chosen
	 * edge stays the same at every width, where a percentage slides inward as
	 * the section grows and eventually runs the artwork off the opposite side.
	 *
	 * @param string $anchor bottom-left | bottom-right | top-left | top-right | free.
	 * @param mixed  $dx     Offset from the left or right edge, px.
	 * @param mixed  $dy     Offset from the top or bottom edge, px.
	 * @param string $prefix Custom property prefix, e.g. '--pfh-d-'.
	 * @return array<string, string> Empty when the placement is free.
	 */
	public static function edges( $anchor, $dx, $dy, $prefix = '--pfh-' ) {
		$anchor = (string) $anchor;

		if ( '' === $anchor || 'free' === $anchor ) {
			return [];
		}

		$x      = self::unit( (float) $dx );
		$y      = self::unit( (float) $dy );
		$right  = false !== strpos( $anchor, 'right' );
		$bottom = false !== strpos( $anchor, 'bottom' );

		return [
			$prefix . 'l' => $right ? 'auto' : $x,
			$prefix . 'r' => $right ? $x : 'auto',
			$prefix . 't' => $bottom ? 'auto' : $y,
			$prefix . 'b' => $bottom ? $y : 'auto',
		];
	}

	public static function fluid( $value, $frame = 1440, $min = 0.62, $max = 1.75 ) {
		$px    = (float) $value;
		$frame = (float) $frame;

		if ( $px <= 0 || $frame <= 0 ) {
			return self::unit( $value );
		}

		return sprintf(
			'clamp(%spx, %svw, %spx)',
			round( $px * $min, 2 ),
			round( $px / $frame * 100, 4 ),
			round( $px * $max, 2 )
		);
	}

	public static function unit( $value, $unit = 'px' ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		if ( is_numeric( $value ) ) {
			return $value . $unit;
		}

		return (string) $value;
	}

	/**
	 * Are we currently serving the Bricks builder (panel or preview)?
	 *
	 * Used to avoid running option-populating queries on every front-end hit.
	 *
	 * @return bool
	 */
	public static function is_builder_context() {
		if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
			return true;
		}

		if ( function_exists( 'bricks_is_builder_call' ) && bricks_is_builder_call() ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		/*
		 * admin-ajax.php makes is_admin() true, so without this every
		 * front-end AJAX render counted as a builder call and elements put
		 * out their editor placeholders — a visitor with no viewing history
		 * was shown "No products matched. Check the Products group." The
		 * builder's own calls are caught by bricks_is_builder_call() above,
		 * so a plain AJAX request can safely be treated as the front end.
		 */
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return false;
		}

		return is_admin();
	}

	/**
	 * Registered nav menus as a Bricks select option list.
	 *
	 * @return array<int|string, string>
	 */
	public static function menu_options() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$cache = [];

		if ( ! self::is_builder_context() ) {
			return $cache;
		}

		$menus = wp_get_nav_menus();

		if ( is_wp_error( $menus ) || empty( $menus ) ) {
			return $cache;
		}

		foreach ( $menus as $menu ) {
			$cache[ $menu->term_id ] = $menu->name;
		}

		return $cache;
	}

	/**
	 * WooCommerce product categories as a Bricks select option list.
	 *
	 * @return array<int|string, string>
	 */
	public static function product_cat_options() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$cache = [];

		/*
		 * Built on the front end as well as in the builder.
		 *
		 * Bricks validates a select control's saved value against this map, so
		 * returning an empty list outside the builder quietly dropped the
		 * chosen category on the front end: the element then queried with no
		 * category filter at all and rendered the whole catalogue, while the
		 * builder — where the options did exist — looked correct. The result is
		 * cached in a transient so a page view costs nothing.
		 */
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return $cache;
		}

		if ( ! self::is_builder_context() ) {
			$stored = get_transient( self::CAT_CACHE );

			if ( is_array( $stored ) ) {
				$cache = $stored;

				return $cache;
			}
		}

		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => 300,
				'orderby'    => 'name',
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $cache;
		}

		foreach ( $terms as $term ) {
			$cache[ $term->term_id ] = $term->name;
		}

		set_transient( self::CAT_CACHE, $cache, 12 * HOUR_IN_SECONDS );

		return $cache;
	}

	/**
	 * Drop the cached category list when the terms change.
	 */
	public static function flush_product_cats() {
		delete_transient( self::CAT_CACHE );
	}

	/**
	 * Resolve a category control value to term ids.
	 *
	 * Accepts an id, a slug, an array of either, or the object shape a Bricks
	 * control can store — so the filter still resolves whatever the panel
	 * saved.
	 *
	 * @param mixed $value Control value.
	 * @return array<int, int>
	 */
	public static function category_ids( $value ) {
		if ( null === $value || '' === $value || [] === $value ) {
			return [];
		}

		$ids = [];

		foreach ( (array) $value as $item ) {
			if ( is_array( $item ) ) {
				$item = isset( $item['id'] ) ? $item['id'] : ( isset( $item['value'] ) ? $item['value'] : '' );
			}

			$item = trim( (string) $item );

			if ( '' === $item ) {
				continue;
			}

			if ( ctype_digit( $item ) ) {
				$ids[] = (int) $item;

				continue;
			}

			$term = get_term_by( 'slug', $item, 'product_cat' );

			if ( $term && ! is_wp_error( $term ) ) {
				$ids[] = (int) $term->term_id;
			}
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Render a WordPress nav menu as a flat <ul> of links.
	 *
	 * @param int    $menu_id    Nav menu term ID.
	 * @param string $list_class Class for the <ul>.
	 * @param string $link_class Class for each <a>.
	 * @return string
	 */
	public static function render_menu_links( $menu_id, $list_class, $link_class ) {
		$menu_id = (int) $menu_id;

		if ( ! $menu_id ) {
			return '';
		}

		$items = wp_get_nav_menu_items( $menu_id, [ 'update_post_term_cache' => false ] );

		if ( is_wp_error( $items ) || empty( $items ) ) {
			return '';
		}

		$html = '<ul class="' . esc_attr( $list_class ) . '">';

		foreach ( $items as $item ) {
			// Only top level items — these columns are single level by design.
			if ( ! empty( $item->menu_item_parent ) ) {
				continue;
			}

			$target = ! empty( $item->target ) ? ' target="' . esc_attr( $item->target ) . '" rel="noopener noreferrer"' : '';

			$html .= '<li><a class="' . esc_attr( $link_class ) . '" href="' . esc_url( $item->url ) . '"' . $target . '>' . esc_html( $item->title ) . '</a></li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Is WooCommerce active and booted?
	 *
	 * @return bool
	 */
	public static function has_woocommerce() {
		return class_exists( 'WooCommerce' ) && function_exists( 'WC' );
	}
}
