<?php
/**
 * Keep a Bricks page saveable when the database cannot hold an emoji.
 *
 * Bricks stores all of a page's elements as one post-meta value. Where the
 * postmeta table is utf8 — three bytes a character, the default on installs
 * older than 2015 and still common on sites moved between hosts — rather than
 * utf8mb4, WordPress core refuses to write any value containing a 4-byte
 * character. wpdb::process_fields() strips what the column cannot hold, sees
 * the value changed, and writes nothing.
 *
 * Nothing, for the whole page: one emoji in one field of one element and every
 * later save of that page fails, while the builder carries on showing the
 * edits. The page stays on whatever was last saved before the emoji arrived,
 * which looks exactly like "nothing I add gets saved".
 *
 * WordPress already handles this for post titles and content, by writing those
 * characters as HTML entities. It does not for meta, so this does the same for
 * Bricks' element data — only where the column needs it, and only the
 * characters it cannot store. An entity renders as the same character, so the
 * page looks identical; the save simply goes through.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Save_Guard {

	/** Where Bricks keeps element trees. */
	const KEYS = [ '_bricks_page_content_2', '_bricks_page_header_2', '_bricks_page_footer_2' ];

	public static function init() {
		foreach ( self::KEYS as $key ) {
			add_filter( 'sanitize_post_meta_' . $key, [ __CLASS__, 'guard' ] );
		}
	}

	/**
	 * Encode what the column cannot store, and nothing else.
	 *
	 * @param mixed $value Meta value about to be written.
	 * @return mixed
	 */
	public static function guard( $value ) {
		return self::needed() ? self::encode( $value ) : $value;
	}

	/**
	 * Is postmeta a utf8 column that cannot hold 4-byte characters?
	 *
	 * Deliberately not cached: wpdb caches the column lookup itself, and
	 * caching the answer here would outlive a changed database.
	 *
	 * @return bool
	 */
	public static function needed() {
		global $wpdb;

		if ( ! $wpdb || ! method_exists( $wpdb, 'get_col_charset' ) ) {
			return false;
		}

		$charset = $wpdb->get_col_charset( $wpdb->postmeta, 'meta_value' );

		if ( ! is_string( $charset ) ) {
			return false;
		}

		return in_array( strtolower( $charset ), [ 'utf8', 'utf8mb3' ], true );
	}

	/**
	 * Replace every 4-byte character in a value with its HTML entity.
	 *
	 * Walks arrays, since an element tree is nested. A string that is not
	 * valid UTF-8 is returned untouched rather than dropped.
	 *
	 * @param mixed $value Anything.
	 * @return mixed
	 */
	public static function encode( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::encode( $item );
			}

			return $value;
		}

		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}

		// Cheap test first: a 4-byte sequence always starts with F0–F4.
		if ( ! preg_match( '/[\xF0-\xF4]/', $value ) ) {
			return $value;
		}

		$encoded = preg_replace_callback(
			'/[\x{10000}-\x{10FFFF}]/u',
			static function ( $match ) {
				$bytes = array_values( unpack( 'C*', $match[0] ) );
				$point = ( ( $bytes[0] & 0x07 ) << 18 )
					| ( ( $bytes[1] & 0x3F ) << 12 )
					| ( ( $bytes[2] & 0x3F ) << 6 )
					| ( $bytes[3] & 0x3F );

				return '&#x' . strtoupper( dechex( $point ) ) . ';';
			},
			$value
		);

		return null === $encoded ? $value : $encoded;
	}
}
