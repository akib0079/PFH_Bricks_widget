<?php
/**
 * Where a design export points at other things, and how to point it again.
 *
 * A Bricks layout, a category's content or a product's extra fields carry
 * numbers: an image's attachment id, a page a button links to, the category
 * a mega menu column lists, the menu a footer column shows. On the site the
 * design was made on, those numbers are right. On the site it moves to they
 * are not, or not all of them: that site has had orders of its own since the
 * copy was taken, and an order takes a number from the same counter as a page
 * or an image. The live shop's order 13723 is the staging header template.
 *
 * This class is the one place that knows where those numbers sit. The export
 * walks a value with it to learn what the value needs; the import walks the
 * same value with it to swap every number for the one it has on the new site.
 * The walk is the same both times, so what is collected is what is replaced.
 *
 * A visitor is called for each reference with its kind and its value, and
 * returns the value to put back:
 *
 *   attachment      an image id
 *   post            a post, page, template or product id
 *   term:<tax>      a term id in that taxonomy (nav_menu for menus)
 *   url             a string holding a URL or an uploads path
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Migrate_Refs {

	/**
	 * Element settings that hold a term id, by element name. '*' applies to
	 * every pfh- element; a named element's own entry wins over it.
	 */
	const TERM_KEYS = [
		'pfh-header'  => [
			'megaParent'  => 'product_cat',
			'megaProdCat' => 'product_cat',
			'megaInclude' => 'product_cat',
		],
		'pfh-archive' => [ 'catsManual' => 'product_cat' ],
		'pfh-post'    => [ 'productsCategory' => 'product_cat' ],
		'pfh-blog'    => [ 'category' => 'category' ],
		'pfh-footer'  => [ 'menu' => 'nav_menu' ],
		'nav-menu'    => [ 'menu' => 'nav_menu' ],
		'*'           => [ 'category' => 'product_cat' ],
	];

	/** Settings of pfh- elements that hold product ids: one, a list, or "1, 2, 3". */
	const POST_KEYS = [ 'productId', 'productIds', 'badgeProducts' ];

	/** A Bricks query loop's post filters, in any element. */
	const QUERY_POST_KEYS = [ 'post__in', 'post__not_in', 'post_parent', 'post_parent__in', 'post_parent__not_in' ];

	/** The files of a Bricks custom font face, by format. */
	const FONT_FORMATS = [ 'woff2', 'woff', 'ttf', 'otf', 'eot', 'svg' ];

	/**
	 * Walk any value: a Bricks element list, a template's settings, an option.
	 *
	 * @param mixed    $data  Value.
	 * @param callable $visit function( string $kind, mixed $value ): mixed.
	 * @return mixed The value with every reference passed through the visitor.
	 */
	public static function walk( $data, callable $visit ) {
		return self::node( $data, $visit, '' );
	}

	/**
	 * One node, inside the element named $element ('' outside any element).
	 *
	 * @param mixed    $data    Value.
	 * @param callable $visit   Visitor.
	 * @param string   $element Element name.
	 * @return mixed
	 */
	private static function node( $data, callable $visit, $element ) {
		if ( is_string( $data ) ) {
			return self::text( $data, $visit );
		}

		if ( ! is_array( $data ) ) {
			return $data;
		}

		// A Bricks element: its name says how to read its settings.
		if ( isset( $data['id'], $data['name'] ) && array_key_exists( 'settings', $data ) && is_string( $data['name'] ) ) {
			if ( is_array( $data['settings'] ) ) {
				$data['settings'] = self::settings( $data['settings'], $visit, $data['name'] );
			}

			return $data;
		}

		return self::settings( $data, $visit, $element );
	}

	/**
	 * Settings, or anything nested in them, read with the element's rules.
	 *
	 * @param array    $data    Value.
	 * @param callable $visit   Visitor.
	 * @param string   $element Element name.
	 * @return array
	 */
	private static function settings( array $data, callable $visit, $element ) {
		// An image as Bricks stores it: { id, url, size, filename, full }.
		if ( self::is_image( $data ) ) {
			$data['id'] = $visit( 'attachment', (int) $data['id'] );
		}

		// A link to a page on the site: { type: internal, postId }.
		if ( isset( $data['type'], $data['postId'] ) && 'internal' === $data['type'] && is_numeric( $data['postId'] ) ) {
			$data['postId'] = $visit( 'post', (int) $data['postId'] );
		}

		// A template condition naming pages: { main: ids, ids: [ ... ] }.
		if ( isset( $data['main'], $data['ids'] ) && is_array( $data['ids'] ) ) {
			$data['ids'] = self::ids( $data['ids'], $visit, 'post' );
		}

		foreach ( $data as $key => $value ) {
			if ( 'id' === $key && self::is_image( $data ) ) {
				continue;
			}

			if ( is_string( $key ) && '' !== $element ) {
				$kind = self::rule( $element, $key );

				if ( $kind ) {
					$data[ $key ] = self::ids( $value, $visit, $kind );
					continue;
				}
			}

			// The template element: { template: <bricks_template id> }.
			if ( 'template' === $element && 'template' === $key && is_numeric( $value ) ) {
				$data[ $key ] = $visit( 'post', (int) $value );
				continue;
			}

			if ( is_array( $value ) ) {
				$data[ $key ] = self::node( $value, $visit, $element );
			} elseif ( is_string( $value ) ) {
				$data[ $key ] = self::text( $value, $visit );
			}
		}

		return $data;
	}

	/**
	 * What kind of reference a setting holds, if any.
	 *
	 * @param string $element Element name.
	 * @param string $key     Setting key.
	 * @return string '' when it is not a reference.
	 */
	private static function rule( $element, $key ) {
		if ( isset( self::TERM_KEYS[ $element ][ $key ] ) ) {
			return 'term:' . self::TERM_KEYS[ $element ][ $key ];
		}

		if ( in_array( $key, self::QUERY_POST_KEYS, true ) ) {
			return 'post';
		}

		if ( 0 !== strpos( $element, 'pfh-' ) ) {
			return '';
		}

		if ( isset( self::TERM_KEYS['*'][ $key ] ) ) {
			return 'term:' . self::TERM_KEYS['*'][ $key ];
		}

		return in_array( $key, self::POST_KEYS, true ) ? 'post' : '';
	}

	/**
	 * Ids in whatever shape a setting keeps them: a number, a numeric string,
	 * "12, 34" or a list. Non-numeric parts — a slug in megaInclude — stay.
	 *
	 * @param mixed    $value Value.
	 * @param callable $visit Visitor.
	 * @param string   $kind  Reference kind.
	 * @return mixed
	 */
	public static function ids( $value, callable $visit, $kind ) {
		if ( is_int( $value ) ) {
			return $value ? (int) $visit( $kind, $value ) : $value;
		}

		if ( is_string( $value ) ) {
			if ( '' === trim( $value ) ) {
				return $value;
			}

			if ( ctype_digit( trim( $value ) ) ) {
				return (string) $visit( $kind, (int) trim( $value ) );
			}

			if ( false === strpos( $value, ',' ) ) {
				return $value;
			}

			$parts = array_map( 'trim', explode( ',', $value ) );

			foreach ( $parts as $i => $part ) {
				if ( '' !== $part && ctype_digit( $part ) ) {
					$parts[ $i ] = (string) $visit( $kind, (int) $part );
				}
			}

			return implode( ', ', $parts );
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $i => $item ) {
				$value[ $i ] = self::ids( $item, $visit, $kind );
			}
		}

		return $value;
	}

	/**
	 * A string: a "taxonomy::id" term reference, or text that may hold URLs.
	 *
	 * @param string   $text  Text.
	 * @param callable $visit Visitor.
	 * @return string
	 */
	private static function text( $text, callable $visit ) {
		// Bricks query loops and template conditions keep terms as "product_cat::127".
		if ( preg_match( '/^([a-z0-9_-]+)::(\d+)$/', $text, $m ) ) {
			return $m[1] . '::' . (int) $visit( 'term:' . $m[1], (int) $m[2] );
		}

		if ( false !== strpos( $text, '://' ) || false !== strpos( $text, '/wp-content/uploads/' ) || false !== strpos( $text, '\/wp-content\/uploads\/' ) ) {
			return (string) $visit( 'url', $text );
		}

		return $text;
	}

	/**
	 * @param array $data Value.
	 * @return bool
	 */
	private static function is_image( array $data ) {
		return isset( $data['id'] )
			&& is_numeric( $data['id'] )
			&& (int) $data['id'] > 0
			&& ( isset( $data['url'] ) || isset( $data['full'] ) || isset( $data['filename'] ) );
	}

	/**
	 * A moved post's meta value: a layout, a template's settings, or a custom
	 * font's files.
	 *
	 * @param string   $key   Meta key.
	 * @param mixed    $value Meta value.
	 * @param callable $visit Visitor.
	 * @return mixed
	 */
	public static function post_meta( $key, $value, callable $visit ) {
		return 'bricks_font_faces' === $key && is_array( $value ) ? self::font_faces( $value, $visit ) : self::walk( $value, $visit );
	}

	/**
	 * A Bricks custom font: per weight and style, an attachment id per format.
	 *
	 * @param array    $faces bricks_font_faces.
	 * @param callable $visit Visitor.
	 * @return array
	 */
	public static function font_faces( array $faces, callable $visit ) {
		foreach ( $faces as $variant => $files ) {
			if ( ! is_array( $files ) ) {
				continue;
			}

			foreach ( $files as $format => $id ) {
				if ( in_array( $format, self::FONT_FORMATS, true ) && is_numeric( $id ) && (int) $id > 0 ) {
					$faces[ $variant ][ $format ] = is_int( $id ) ? (int) $visit( 'attachment', $id ) : (string) $visit( 'attachment', (int) $id );
				}
			}
		}

		return $faces;
	}

	/**
	 * A category's own content (_pfh_collection): two images, a product and
	 * two links.
	 *
	 * @param array    $meta  Collection meta.
	 * @param callable $visit Visitor.
	 * @return array
	 */
	public static function collection( array $meta, callable $visit ) {
		foreach ( [ [ 'header', 'image' ], [ 'bundle', 'image' ] ] as $path ) {
			if ( ! empty( $meta[ $path[0] ][ $path[1] ] ) ) {
				$meta[ $path[0] ][ $path[1] ] = (int) $visit( 'attachment', (int) $meta[ $path[0] ][ $path[1] ] );
			}
		}

		if ( ! empty( $meta['bundle']['product'] ) ) {
			$meta['bundle']['product'] = (int) $visit( 'post', (int) $meta['bundle']['product'] );
		}

		foreach ( [ 'inspire', 'bundle', 'faq' ] as $section ) {
			if ( isset( $meta[ $section ] ) && is_array( $meta[ $section ] ) ) {
				foreach ( $meta[ $section ] as $key => $value ) {
					if ( is_string( $value ) ) {
						$meta[ $section ][ $key ] = self::text( $value, $visit );
					} elseif ( 'items' === $key && is_array( $value ) ) {
						$meta[ $section ][ $key ] = self::walk( $value, $visit );
					}
				}
			}
		}

		return $meta;
	}

	/**
	 * One of a product's extra fields (_pfh_*).
	 *
	 * @param string   $key   Meta key.
	 * @param mixed    $value Meta value.
	 * @param callable $visit Visitor.
	 * @return mixed
	 */
	public static function product_meta( $key, $value, callable $visit ) {
		if ( '_pfh_bottom_image' === $key ) {
			return $value ? (int) $visit( 'attachment', (int) $value ) : $value;
		}

		if ( '_pfh_related' === $key ) {
			return is_array( $value ) ? self::ids( array_map( 'intval', $value ), $visit, 'post' ) : $value;
		}

		$media = class_exists( 'PFH_Widgets_Product_Fields' ) ? PFH_Widgets_Product_Fields::media_columns() : [];

		if ( isset( $media[ $key ] ) && is_array( $value ) ) {
			foreach ( $value as $i => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				foreach ( $row as $column => $cell ) {
					if ( in_array( $column, $media[ $key ], true ) && is_numeric( $cell ) && (int) $cell > 0 ) {
						$row[ $column ] = (string) $visit( 'attachment', (int) $cell );
					} elseif ( is_string( $cell ) ) {
						$row[ $column ] = self::text( $cell, $visit );
					}
				}

				$value[ $i ] = $row;
			}

			return $value;
		}

		return self::walk( $value, $visit );
	}

	/**
	 * A settings module's option: its media fields are bare attachment ids.
	 *
	 * @param array    $value  Option value.
	 * @param string[] $media  Keys of its media fields.
	 * @param callable $visit  Visitor.
	 * @return array
	 */
	public static function module_option( array $value, array $media, callable $visit ) {
		foreach ( $value as $key => $item ) {
			if ( in_array( $key, $media, true ) && is_numeric( $item ) && (int) $item > 0 ) {
				$value[ $key ] = (int) $visit( 'attachment', (int) $item );
			} elseif ( is_string( $item ) ) {
				$value[ $key ] = self::text( $item, $visit );
			} elseif ( is_array( $item ) ) {
				$value[ $key ] = self::walk( $item, $visit );
			}
		}

		return $value;
	}

	/**
	 * The theme's mods: a logo, the menu locations.
	 *
	 * @param array    $mods  theme_mods_<theme>.
	 * @param callable $visit Visitor.
	 * @return array
	 */
	public static function theme_mods( array $mods, callable $visit ) {
		if ( ! empty( $mods['custom_logo'] ) ) {
			$mods['custom_logo'] = (int) $visit( 'attachment', (int) $mods['custom_logo'] );
		}

		if ( ! empty( $mods['nav_menu_locations'] ) && is_array( $mods['nav_menu_locations'] ) ) {
			foreach ( $mods['nav_menu_locations'] as $location => $menu ) {
				$mods['nav_menu_locations'][ $location ] = $menu ? (int) $visit( 'term:nav_menu', (int) $menu ) : 0;
			}
		}

		// A Customizer CSS post is the old theme's; the new site has its own.
		unset( $mods['custom_css_post_id'] );

		return $mods;
	}

	/**
	 * The uploads paths (relative to the uploads folder) a string mentions,
	 * on the given site or relative to its root.
	 *
	 * @param string $text Text.
	 * @param string $host Host of the site the text came from.
	 * @return string[]
	 */
	public static function upload_paths( $text, $host ) {
		$text  = str_replace( '\/', '/', (string) $text );
		$found = [];

		if ( ! preg_match_all( '#(?:https?:)?(?://([^/\s"\'<>]+))?/wp-content/uploads/([^\s"\'()<>?\#\\\\,]+)#i', $text, $m, PREG_SET_ORDER ) ) {
			return $found;
		}

		foreach ( $m as $match ) {
			// The host as given, without a port: localhost:8080 is localhost.
			$on = isset( $match[1] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $match[1] ) ) : '';

			if ( '' !== $on && strtolower( (string) $host ) !== $on ) {
				continue;
			}

			$path = rawurldecode( rtrim( $match[2], '.;:' ) );

			if ( preg_match( '#^\d{4}/\d{2}/[^/]+\.[a-z0-9]{2,5}$#i', $path ) ) {
				$found[ $path ] = true;
			}
		}

		return array_keys( $found );
	}
}
