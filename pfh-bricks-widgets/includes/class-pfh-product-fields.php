<?php
/**
 * The two per-product fields the product page needs.
 *
 * Both live in their own tab in WooCommerce's Product data box, next to the
 * price and stock, because that is where whoever adds a product is already
 * looking — not in a separate screen somewhere else.
 *
 *   Tag        a short badge for the corner of the gallery ("SALES", "NIEUW").
 *              Empty means no badge at all, which is the usual case.
 *   Highlights what is in the box: a line of text and, optionally, a note that
 *              sits to its right ("1x 1000ml Griekse vruchtensap" · "33 glazen
 *              van 467ml"). Different for every product. None means the whole
 *              block is left out.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Product_Fields {

	const BADGE      = '_pfh_badge';
	const HIGHLIGHTS = '_pfh_highlights';

	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', [ __CLASS__, 'tab' ] );
		add_action( 'woocommerce_product_data_panels', [ __CLASS__, 'panel' ] );
		add_action( 'woocommerce_process_product_meta', [ __CLASS__, 'save' ] );
	}

	/**
	 * @param array $tabs Product data tabs.
	 * @return array
	 */
	public static function tab( $tabs ) {
		$tabs['pfh'] = [
			'label'    => esc_html__( 'Products For Home', 'pfh-widgets' ),
			'target'   => 'pfh_product_data',
			'class'    => [],
			'priority' => 65,
		];

		return $tabs;
	}

	/**
	 * The badge field and the highlights table.
	 */
	public static function panel() {
		global $post;

		$badge = (string) get_post_meta( $post->ID, self::BADGE, true );
		$rows  = self::highlights( $post->ID );

		// One blank row so the table is never an empty box with a button.
		if ( ! $rows ) {
			$rows = [ [ 'label' => '', 'note' => '' ] ];
		}
		?>
		<div id="pfh_product_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					[
						'id'          => self::BADGE,
						'value'       => $badge,
						'label'       => esc_html__( 'Tag', 'pfh-widgets' ),
						'placeholder' => esc_html__( 'SALES', 'pfh-widgets' ),
						'desc_tip'    => true,
						'description' => esc_html__( 'Shown in the corner of the product gallery. Leave empty for no tag.', 'pfh-widgets' ),
					]
				);
				?>
			</div>

			<div class="options_group">
				<p class="form-field">
					<label><?php esc_html_e( 'Highlights', 'pfh-widgets' ); ?></label>
					<span class="description">
						<?php esc_html_e( 'What is in the box. The note is optional and sits to the right of the line. Leave empty for no highlights.', 'pfh-widgets' ); ?>
					</span>
				</p>

				<table class="widefat pfh-highlights" style="margin:0 12px 12px;width:auto;min-width:92%">
					<thead>
						<tr>
							<th style="text-align:left"><?php esc_html_e( 'Line', 'pfh-widgets' ); ?></th>
							<th style="text-align:left"><?php esc_html_e( 'Note on the right', 'pfh-widgets' ); ?></th>
							<th style="width:1%"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $i => $row ) : ?>
							<tr>
								<td>
									<input type="text" style="width:100%"
										name="<?php echo esc_attr( self::HIGHLIGHTS ); ?>[<?php echo (int) $i; ?>][label]"
										value="<?php echo esc_attr( $row['label'] ); ?>"
										placeholder="<?php esc_attr_e( '1x 1000ml Griekse vruchtensap', 'pfh-widgets' ); ?>" />
								</td>
								<td>
									<input type="text" style="width:100%"
										name="<?php echo esc_attr( self::HIGHLIGHTS ); ?>[<?php echo (int) $i; ?>][note]"
										value="<?php echo esc_attr( $row['note'] ); ?>"
										placeholder="<?php esc_attr_e( '33 glazen van 467ml', 'pfh-widgets' ); ?>" />
								</td>
								<td>
									<button type="button" class="button pfh-highlights__remove" aria-label="<?php esc_attr_e( 'Remove this line', 'pfh-widgets' ); ?>">&times;</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p style="margin:0 12px 12px">
					<button type="button" class="button pfh-highlights__add"><?php esc_html_e( 'Add a line', 'pfh-widgets' ); ?></button>
				</p>
			</div>
		</div>

		<script>
		( function () {
			var table = document.querySelector( '.pfh-highlights' );

			if ( ! table ) {
				return;
			}

			var body = table.querySelector( 'tbody' );

			/*
			 * The row names are re-indexed after every change. PHP takes the
			 * array as it arrives, so a gap left by a removed row would be
			 * harmless — but a duplicate index would quietly drop a line.
			 */
			function renumber() {
				Array.prototype.forEach.call( body.rows, function ( row, i ) {
					Array.prototype.forEach.call( row.querySelectorAll( 'input' ), function ( input ) {
						input.name = input.name.replace( /\[\d+\]/, '[' + i + ']' );
					} );
				} );
			}

			document.querySelector( '.pfh-highlights__add' ).addEventListener( 'click', function () {
				var row = body.rows[ body.rows.length - 1 ].cloneNode( true );

				Array.prototype.forEach.call( row.querySelectorAll( 'input' ), function ( input ) {
					input.value = '';
				} );

				body.appendChild( row );
				renumber();
				row.querySelector( 'input' ).focus();
			} );

			body.addEventListener( 'click', function ( event ) {
				if ( ! event.target.closest( '.pfh-highlights__remove' ) ) {
					return;
				}

				// Always leave one row, so there is something to add to.
				if ( body.rows.length > 1 ) {
					event.target.closest( 'tr' ).remove();
				} else {
					Array.prototype.forEach.call( body.rows[ 0 ].querySelectorAll( 'input' ), function ( input ) {
						input.value = '';
					} );
				}

				renumber();
			} );
		}() );
		</script>
		<?php
	}

	/**
	 * @param int $product_id Product being saved.
	 */
	public static function save( $product_id ) {
		// WooCommerce has already checked the nonce and capability by here.
		$badge = isset( $_POST[ self::BADGE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::BADGE ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$rows = [];

		if ( isset( $_POST[ self::HIGHLIGHTS ] ) && is_array( $_POST[ self::HIGHLIGHTS ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			foreach ( wp_unslash( $_POST[ self::HIGHLIGHTS ] ) as $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
				$label = isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '';
				$note  = isset( $row['note'] ) ? sanitize_text_field( (string) $row['note'] ) : '';

				// A note with nothing to note against is not a highlight.
				if ( '' !== $label ) {
					$rows[] = [ 'label' => $label, 'note' => $note ];
				}
			}
		}

		/*
		 * Same treatment Bricks' own data gets: on a database whose postmeta
		 * column is utf8, a 4-byte character in either field would have the
		 * whole write refused, and the product would appear not to save.
		 */
		if ( class_exists( 'PFH_Widgets_Save_Guard' ) && PFH_Widgets_Save_Guard::needed() ) {
			$badge = PFH_Widgets_Save_Guard::encode( $badge );
			$rows  = PFH_Widgets_Save_Guard::encode( $rows );
		}

		if ( '' === $badge ) {
			delete_post_meta( $product_id, self::BADGE );
		} else {
			update_post_meta( $product_id, self::BADGE, $badge );
		}

		if ( ! $rows ) {
			delete_post_meta( $product_id, self::HIGHLIGHTS );
		} else {
			update_post_meta( $product_id, self::HIGHLIGHTS, $rows );
		}
	}

	/* ---------------------------------------------------------------------
	 * Reading them back
	 * ------------------------------------------------------------------ */

	/**
	 * The badge for a product, if it has one.
	 *
	 * @param int    $product_id Product.
	 * @param string $meta_key   Where to read it from.
	 * @return string
	 */
	public static function badge( $product_id, $meta_key = self::BADGE ) {
		$value = get_post_meta( (int) $product_id, $meta_key ? $meta_key : self::BADGE, true );

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * The highlights for a product, as label/note rows.
	 *
	 * Also understands the shapes another field plugin would store: a plain
	 * list of strings, or an ACF repeater's own key names.
	 *
	 * @param int    $product_id Product.
	 * @param string $meta_key   Where to read them from.
	 * @return array<int, array{label: string, note: string}>
	 */
	public static function highlights( $product_id, $meta_key = self::HIGHLIGHTS ) {
		$value = get_post_meta( (int) $product_id, $meta_key ? $meta_key : self::HIGHLIGHTS, true );
		$rows  = [];

		if ( ! is_array( $value ) ) {
			return $rows;
		}

		foreach ( $value as $row ) {
			if ( is_scalar( $row ) ) {
				$label = trim( (string) $row );
				$note  = '';
			} elseif ( is_array( $row ) ) {
				$label = self::first( $row, [ 'label', 'text', 'title', 'highlight' ] );
				$note  = self::first( $row, [ 'note', 'value', 'meta', 'suffix' ] );
			} else {
				continue;
			}

			if ( '' !== $label ) {
				$rows[] = [ 'label' => $label, 'note' => $note ];
			}
		}

		return $rows;
	}

	/**
	 * The first of several possible keys that holds something.
	 *
	 * @param array    $row  Row.
	 * @param string[] $keys Keys to try, in order.
	 * @return string
	 */
	private static function first( array $row, array $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) && '' !== trim( (string) $row[ $key ] ) ) {
				return trim( (string) $row[ $key ] );
			}
		}

		return '';
	}
}
