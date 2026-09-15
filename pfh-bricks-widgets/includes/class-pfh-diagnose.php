<?php
/**
 * A read-only report on why an element is not on the page.
 *
 * Written because two plausible fixes in a row changed nothing: the guessing
 * has to stop somewhere, and the thing worth looking at is what Bricks
 * actually saved, not what the code would do with it.
 *
 * It answers, for every Bricks page and template on the site:
 *
 *   is the element in the saved data at all — which is the difference between
 *   "it will not save" and "it will not render";
 *   what settings it was saved with;
 *   whether anything on it, or on a section above it, carries a condition,
 *   which would keep it off the front end no matter what the element does;
 *   and what it renders right now, outside the builder.
 *
 * Admin-only, changes nothing, writes nothing.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Diagnose {

	const SLUG = 'pfh-diagnose';

	public static function boot() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 20 );
	}

	public static function menu() {
		add_submenu_page(
			PFH_Widgets_Settings::PAGE,
			esc_html__( 'Diagnose', 'pfh-widgets' ),
			esc_html__( 'Diagnose', 'pfh-widgets' ),
			PFH_Widgets_Settings::CAP,
			self::SLUG,
			[ __CLASS__, 'render' ]
		);
	}

	public static function render() {
		if ( ! current_user_can( PFH_Widgets_Settings::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to view this.', 'pfh-widgets' ) );
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Diagnose', 'pfh-widgets' ) . '</h1>';
		echo '<p>' . esc_html__( 'Everything below is read from the database as it stands. Copy the whole box and send it over.', 'pfh-widgets' ) . '</p>';
		echo '<textarea readonly style="width:100%;height:32em;font-family:Menlo,Consolas,monospace;font-size:12px;white-space:pre;overflow:auto">';
		echo esc_textarea( self::report() );
		echo '</textarea></div>';
	}

	/**
	 * The report itself.
	 *
	 * @return string
	 */
	public static function report() {
		$out = [];

		$out[] = 'PFH ' . PFH_WIDGETS_VERSION
			. ' | Bricks ' . ( defined( 'BRICKS_VERSION' ) ? BRICKS_VERSION : 'not loaded' )
			. ' | WP ' . get_bloginfo( 'version' )
			. ' | Woo ' . ( defined( 'WC_VERSION' ) ? WC_VERSION : '-' )
			. ' | PHP ' . PHP_VERSION;
		$out[] = '';

		/* ---- are the elements registered where the front end can see them ---- */

		$out[] = '== elements registered with Bricks ==';

		if ( ! class_exists( '\Bricks\Elements' ) ) {
			$out[] = 'Bricks is not loaded on this request, so there is nothing to ask.';
		} elseif ( ! property_exists( '\Bricks\Elements', 'elements' ) ) {
			// Reading Bricks' own internals: if the name ever changes, say so
			// rather than reporting an empty list as if it meant something.
			$out[] = 'Cannot read Bricks\' element list on this version — treat this section as unknown, not as empty.';
		} else {
			$registered = array_keys( (array) \Bricks\Elements::$elements );

			$ours = array_values( array_filter( $registered, static function ( $n ) {
				return 0 === strpos( (string) $n, 'pfh-' );
			} ) );

			$out[] = count( $ours ) . ' of ' . count( $registered ) . ' registered are ours: '
				. ( $ours ? implode( ', ', $ours ) : 'NONE — Bricks cannot render any of them' );

			$out[] = in_array( 'pfh-highlight', $ours, true )
				? 'pfh-highlight IS registered.'
				: 'pfh-highlight is NOT registered — that alone would keep it off the page.';
		}
		$out[] = '';

		/* ---- what is actually saved ---- */

		$out[] = '== pfh elements found in saved Bricks data ==';

		$posts = get_posts(
			[
				'post_type'        => [ 'bricks_template', 'page', 'post', 'product' ],
				'post_status'      => [ 'publish', 'draft', 'private' ],
				'numberposts'      => 200,
				'fields'           => 'ids',
				'suppress_filters' => true,
			]
		);

		$found = 0;

		foreach ( (array) $posts as $pid ) {
			foreach ( [ '_bricks_page_content_2', '_bricks_page_header_2', '_bricks_page_footer_2' ] as $meta ) {
				$data = get_post_meta( $pid, $meta, true );

				if ( ! is_array( $data ) ) {
					continue;
				}

				$hits = self::scan( $data, $pid, $meta );

				if ( $hits ) {
					$found += count( $hits );
					$out    = array_merge( $out, $hits );
				}
			}
		}

		if ( ! $found ) {
			$out[] = 'NONE. No pfh element is saved anywhere — which means the page was never saved with one in it.';
		}

		$out[] = '';
		$out[] = '== what a highlight renders right now, outside the builder ==';
		$out[] = self::render_probe();

		return implode( "\n", $out );
	}

	/**
	 * Walk one saved element tree looking for ours.
	 *
	 * @param array  $elements Saved Bricks elements.
	 * @param int    $pid      Post they belong to.
	 * @param string $meta     Which area.
	 * @return array Lines.
	 */
	private static function scan( array $elements, $pid, $meta ) {
		$lines = [];

		// Parent lookup, so a condition on the section above can be reported:
		// that alone keeps an element off the front end whatever it does.
		$by_id = [];

		foreach ( $elements as $el ) {
			if ( isset( $el['id'] ) ) {
				$by_id[ $el['id'] ] = $el;
			}
		}

		foreach ( $elements as $el ) {
			$name = isset( $el['name'] ) ? (string) $el['name'] : '';

			if ( 0 !== strpos( $name, 'pfh-' ) ) {
				continue;
			}

			$settings = isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : [];
			$keys     = array_keys( $settings );

			$line = sprintf(
				'#%d %s [%s] %s — %d setting(s)%s',
				$pid,
				get_post_type( $pid ),
				str_replace( '_bricks_page_', '', $meta ),
				$name,
				count( $keys ),
				$keys ? ': ' . implode( ', ', array_slice( $keys, 0, 12 ) ) . ( count( $keys ) > 12 ? ' …' : '' ) : ''
			);

			// Conditions, on it and on every ancestor.
			$conds = [];
			$node  = $el;
			$depth = 0;

			while ( $node && $depth < 12 ) {
				if ( ! empty( $node['settings']['_conditions'] ) ) {
					$conds[] = ( $node === $el ? 'on itself' : 'on ' . ( $node['name'] ?? 'parent' ) );
				}

				$parent = isset( $node['parent'] ) ? $node['parent'] : '';
				$node   = ( $parent && isset( $by_id[ $parent ] ) ) ? $by_id[ $parent ] : null;
				$depth++;
			}

			if ( $conds ) {
				$line .= ' | CONDITIONS ' . implode( ', ', $conds ) . ' — these decide whether it is output at all';
			}

			$lines[] = $line;
		}

		if ( $lines ) {
			array_unshift( $lines, sprintf( '#%d holds %d element(s) in total:', $pid, count( $elements ) ) );
		}

		return $lines;
	}

	/**
	 * Render a highlight the way a visitor would get it.
	 *
	 * @return string
	 */
	private static function render_probe() {
		if ( ! class_exists( '\Bricks\Element' ) ) {
			return 'Bricks is not loaded here, so nothing to render.';
		}

		if ( ! class_exists( 'PFH_Element_Highlight' ) ) {
			$file = PFH_WIDGETS_DIR . 'elements/class-pfh-element-highlight.php';

			if ( ! file_exists( $file ) ) {
				return 'The element file is missing from the installed plugin.';
			}

			require_once $file;
		}

		$el       = new PFH_Element_Highlight( [ 'id' => 'diagnose' ] );
		$el->name = 'pfh-highlight';
		// Deliberately no set_controls(): this is the front end's own shape.
		$el->settings = [];

		ob_start();

		try {
			$el->render();
		} catch ( \Throwable $e ) {
			ob_end_clean();

			return 'THREW: ' . $e->getMessage();
		}

		$html = trim( (string) ob_get_clean() );

		if ( '' === $html ) {
			return 'Renders NOTHING with no settings — the defaults are not reaching it.';
		}

		return 'Renders ' . strlen( $html ) . ' bytes with no settings at all, so its defaults are reaching it. First 200: '
			. substr( preg_replace( '/\s+/', ' ', $html ), 0, 200 );
	}
}
