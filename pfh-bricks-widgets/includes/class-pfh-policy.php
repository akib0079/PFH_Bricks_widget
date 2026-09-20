<?php
/**
 * Turning what the client typed into a document.
 *
 * Two small transforms, both of which look at the rendered HTML rather than
 * the raw field, and both of which do nothing at all when there is nothing to
 * do: a clause that opens with its own number gets that number lifted into
 * the margin, and a table wider than a phone gets a box of its own to scroll
 * in. Neither invents anything — a page with no numbered clauses and no
 * tables comes out exactly as it was typed.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Policy {

	/**
	 * A section's text, ready to show.
	 *
	 * @param string $text    What the client typed.
	 * @param bool   $clauses Whether to pull leading clause numbers out.
	 * @return string
	 */
	public static function body( $text, $clauses = true ) {
		$html = wp_kses_post( wpautop( (string) $text ) );

		if ( $clauses ) {
			$html = self::clauses( $html );
		}

		return self::wrap_tables( $html );
	}

	/**
	 * Lift a leading clause number out of each paragraph.
	 *
	 * "1.1 Deze algemene voorwaarden…" becomes a number in the margin and a
	 * sentence beside it, which is how a set of terms reads on paper. Only a
	 * paragraph opening with digits, a dot and digits is touched: a sentence
	 * that happens to start with a price or a date is not a clause.
	 *
	 * Both halves are wrapped, because a grid container makes one item of a
	 * whole run of inline content — the number and the sentence have to be
	 * two elements or they land in the same column.
	 *
	 * @param string $html Rendered text.
	 * @return string
	 */
	private static function clauses( $html ) {
		if ( false === strpos( $html, '<p>' ) ) {
			return $html;
		}

		return (string) preg_replace_callback(
			'#<p>\s*(\d{1,2}(?:\.\d{1,3})+)\.?[ \t\x{00A0}]+(.*?)</p>#us',
			static function ( $m ) {
				$text = trim( $m[2] );

				if ( '' === $text ) {
					return $m[0];
				}

				return '<p class="pfh-policy__clause">'
					. '<span class="pfh-policy__clause-no">' . esc_html( $m[1] ) . '</span>'
					. '<span class="pfh-policy__clause-text">' . $text . '</span>'
					. '</p>';
			},
			$html
		);
	}

	/**
	 * Put each table in a box of its own that scrolls.
	 *
	 * @param string $html Rendered text.
	 * @return string
	 */
	private static function wrap_tables( $html ) {
		if ( false === stripos( $html, '<table' ) ) {
			return $html;
		}

		return (string) preg_replace(
			'/(<table[\s>].*?<\/table>)/is',
			'<div class="pfh-policy__scroller">$1</div>',
			$html
		);
	}

	/**
	 * An id for a section, unique within the page being drawn.
	 *
	 * @param string $title Section heading.
	 * @param int    $index Its position, used when the heading makes no slug.
	 * @param array  $seen  Slugs already handed out, by reference.
	 * @return string
	 */
	public static function anchor( $title, $index, array &$seen ) {
		$slug = sanitize_title( wp_strip_all_tags( (string) $title ) );
		$slug = '' !== $slug ? $slug : 'deel-' . ( (int) $index + 1 );

		// Two headings can read the same; their ids cannot.
		if ( isset( $seen[ $slug ] ) ) {
			$seen[ $slug ]++;
			$slug .= '-' . $seen[ $slug ];
		} else {
			$seen[ $slug ] = 1;
		}

		return $slug;
	}

	/**
	 * The lines of a section's extra list, one per line.
	 *
	 * @param string $raw Textarea contents.
	 * @return string[]
	 */
	public static function lines( $raw ) {
		$out = [];

		foreach ( preg_split( '/\R/', (string) $raw ) as $line ) {
			$line = trim( $line );

			if ( '' !== $line ) {
				$out[] = $line;
			}
		}

		return $out;
	}
}
