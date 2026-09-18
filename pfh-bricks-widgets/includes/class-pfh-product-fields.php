<?php
/**
 * Everything the product page needs that WooCommerce has nowhere to put.
 *
 * All of it lives in one **Products For Home** tab in the Product data box,
 * next to the price and stock, because that is where whoever adds a product is
 * already looking — not on a separate screen somewhere else.
 *
 * Every field is optional. An empty one leaves nothing on the page: no empty
 * heading, no empty box, no tab with nothing in it.
 *
 * The repeating fields are all described in one place, in repeaters(), and the
 * panel, the save and the reader are generated from that. Adding another one
 * is a few lines there rather than another copy of the same table.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Product_Fields {

	/* The product section. */
	const BADGE      = '_pfh_badge';
	const HIGHLIGHTS = '_pfh_highlights';

	/* The tabs. */
	const STEPS_SHOW      = '_pfh_steps_show';
	const INGREDIENTS     = '_pfh_ingredients';
	const ALLERGENS       = '_pfh_allergens';
	const FREE_FROM       = '_pfh_free_from';
	const STORAGE_TITLE   = '_pfh_storage_title';
	const STORAGE         = '_pfh_storage';
	const NUTRITION_TITLE = '_pfh_nutrition_title';
	const NUTRITION_INTRO = '_pfh_nutrition_intro';
	const NUTRITION_COLS  = '_pfh_nutrition_cols';
	const NUTRITION       = '_pfh_nutrition';

	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', [ __CLASS__, 'tab' ] );
		add_action( 'woocommerce_product_data_panels', [ __CLASS__, 'panel' ] );
		add_action( 'woocommerce_process_product_meta', [ __CLASS__, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'media' ] );
	}

	/**
	 * The media library, for the icon pickers.
	 *
	 * @param string $hook Current screen.
	 */
	public static function media( $hook ) {
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) && 'product' === get_post_type() ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Every repeating field, and the columns it holds.
	 *
	 * @return array
	 */
	private static function repeaters() {
		return [
			self::HIGHLIGHTS => [
				'label'   => __( 'Highlights', 'pfh-widgets' ),
				'help'    => __( 'What is in the box, shown beside the price. The note is optional and sits to the right of the line.', 'pfh-widgets' ),
				'columns' => [
					'label' => [ 'label' => __( 'Line', 'pfh-widgets' ), 'type' => 'text', 'placeholder' => '1x 1000ml Griekse vruchtensap' ],
					'note'  => [ 'label' => __( 'Note on the right', 'pfh-widgets' ), 'type' => 'text', 'placeholder' => '33 glazen van 467ml' ],
				],
			],
			self::FREE_FROM  => [
				'label'   => __( 'Zonder', 'pfh-widgets' ),
				'help'    => __( 'One per claim. Each becomes a ticked pill under the ingredients.', 'pfh-widgets' ),
				'columns' => [
					'label' => [ 'label' => __( 'Claim', 'pfh-widgets' ), 'type' => 'text', 'placeholder' => 'Glutenvrij' ],
				],
			],
			self::STORAGE    => [
				'label'   => __( 'Houdbaarheid rows', 'pfh-widgets' ),
				'help'    => __( 'One card each. Leave the icon empty for the default.', 'pfh-widgets' ),
				'columns' => [
					'icon'    => [ 'label' => __( 'Icon', 'pfh-widgets' ), 'type' => 'media' ],
					'heading' => [ 'label' => __( 'Heading', 'pfh-widgets' ), 'type' => 'text', 'placeholder' => 'Na openen' ],
					'text'    => [ 'label' => __( 'Text', 'pfh-widgets' ), 'type' => 'textarea', 'placeholder' => 'Na opening 30 dagen houdbaar.' ],
				],
			],
			self::NUTRITION  => [
				'label'   => __( 'Voedingswaarden rows', 'pfh-widgets' ),
				'help'    => __( 'One row of the table each, in the order they should appear.', 'pfh-widgets' ),
				'columns' => [
					'c1' => [ 'label' => __( 'Nutrient', 'pfh-widgets' ), 'type' => 'text', 'placeholder' => 'Energie' ],
					'c2' => [ 'label' => __( 'Second column', 'pfh-widgets' ), 'type' => 'text', 'placeholder' => '38 kcal / 158 kJ' ],
					'c3' => [ 'label' => __( 'Third column', 'pfh-widgets' ), 'type' => 'text', 'placeholder' => '76 kcal / 316 kJ' ],
				],
			],
		];
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

	public static function panel() {
		global $post;

		$id = (int) $post->ID;
		?>
		<div id="pfh_product_data" class="panel woocommerce_options_panel hidden">

			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					[
						'id'          => self::BADGE,
						'value'       => self::text( $id, self::BADGE ),
						'label'       => esc_html__( 'Tag', 'pfh-widgets' ),
						'placeholder' => 'SALES',
						'desc_tip'    => true,
						'description' => esc_html__( 'Shown in the corner of the product gallery. Leave empty for no tag.', 'pfh-widgets' ),
					]
				);

				woocommerce_wp_checkbox(
					[
						'id'          => self::STEPS_SHOW,
						'value'       => '' === self::text( $id, self::STEPS_SHOW ) ? 'yes' : self::text( $id, self::STEPS_SHOW ),
						'label'       => esc_html__( 'Show "In 3 stappen klaar"', 'pfh-widgets' ),
						'desc_tip'    => true,
						'description' => esc_html__( 'The steps panel beside the tabs. Turn it off for a product it does not apply to.', 'pfh-widgets' ),
					]
				);
				?>
			</div>

			<?php self::section( esc_html__( 'Product section', 'pfh-widgets' ) ); ?>
			<?php self::repeater( $id, self::HIGHLIGHTS ); ?>

			<?php self::section( esc_html__( 'Tab: Ingredienten', 'pfh-widgets' ) ); ?>
			<div class="options_group">
				<?php
				woocommerce_wp_textarea_input(
					[
						'id'          => self::INGREDIENTS,
						'value'       => self::text( $id, self::INGREDIENTS ),
						'label'       => esc_html__( 'Ingredients', 'pfh-widgets' ),
						'placeholder' => esc_html__( 'Vruchtensap uit concentraat…', 'pfh-widgets' ),
						'desc_tip'    => true,
						'description' => esc_html__( 'Basic formatting is kept. Empty leaves the tab out.', 'pfh-widgets' ),
						'style'       => 'height:120px',
					]
				);

				woocommerce_wp_textarea_input(
					[
						'id'          => self::ALLERGENS,
						'value'       => self::text( $id, self::ALLERGENS ),
						'label'       => esc_html__( 'Allergenen', 'pfh-widgets' ),
						'placeholder' => esc_html__( 'Dit product bevat geen van de 14 grote allergenen.', 'pfh-widgets' ),
						'desc_tip'    => true,
						'description' => esc_html__( 'Shown in its own box. Empty leaves the box out.', 'pfh-widgets' ),
						'style'       => 'height:80px',
					]
				);
				?>
			</div>
			<?php self::repeater( $id, self::FREE_FROM ); ?>

			<?php self::section( esc_html__( 'Tab: Houdbaarheid', 'pfh-widgets' ) ); ?>
			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					[
						'id'          => self::STORAGE_TITLE,
						'value'       => self::text( $id, self::STORAGE_TITLE ),
						'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
						'placeholder' => 'Bewaring & houdbaarheid',
					]
				);
				?>
			</div>
			<?php self::repeater( $id, self::STORAGE ); ?>

			<?php self::section( esc_html__( 'Tab: Voedingswaarden', 'pfh-widgets' ) ); ?>
			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					[
						'id'          => self::NUTRITION_TITLE,
						'value'       => self::text( $id, self::NUTRITION_TITLE ),
						'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
						'placeholder' => 'Voedingswaarden',
					]
				);

				woocommerce_wp_textarea_input(
					[
						'id'          => self::NUTRITION_INTRO,
						'value'       => self::text( $id, self::NUTRITION_INTRO ),
						'label'       => esc_html__( 'Line under it', 'pfh-widgets' ),
						'placeholder' => 'Per 100ml bereide drank',
						'style'       => 'height:60px',
					]
				);

				$columns = self::columns( $id );
				?>
				<p class="form-field">
					<label><?php esc_html_e( 'Column headings', 'pfh-widgets' ); ?></label>
					<?php foreach ( [ 0 => 'Voedingsstof', 1 => 'Per 100ml', 2 => 'Per portie (200ml)' ] as $i => $placeholder ) : ?>
						<input type="text" style="width:31%;margin-right:1%"
							name="<?php echo esc_attr( self::NUTRITION_COLS ); ?>[<?php echo (int) $i; ?>]"
							value="<?php echo esc_attr( $columns[ $i ] ); ?>"
							placeholder="<?php echo esc_attr( $placeholder ); ?>" />
					<?php endforeach; ?>
					<span class="description" style="display:block;margin:6px 0 0">
						<?php esc_html_e( 'Empty uses the wording set on the element.', 'pfh-widgets' ); ?>
					</span>
				</p>
			</div>
			<?php self::repeater( $id, self::NUTRITION ); ?>
		</div>
		<?php
		self::script();
	}

	/**
	 * @param string $title Section heading.
	 */
	private static function section( $title ) {
		echo '<div class="options_group"><p class="form-field" style="margin:0"><strong>' . esc_html( $title ) . '</strong></p></div>';
	}

	/**
	 * One repeating field as a table.
	 *
	 * @param int    $id  Product.
	 * @param string $key Meta key.
	 */
	private static function repeater( $id, $key ) {
		$spec    = self::repeaters()[ $key ];
		$columns = $spec['columns'];
		$rows    = self::rows( $id, $key );

		// One blank row, so the table is never a header and a button.
		if ( ! $rows ) {
			$rows = [ array_fill_keys( array_keys( $columns ), '' ) ];
		}
		?>
		<div class="options_group">
			<p class="form-field">
				<label><?php echo esc_html( $spec['label'] ); ?></label>
				<span class="description"><?php echo esc_html( $spec['help'] ); ?></span>
			</p>

			<table class="widefat pfh-rep" data-pfh-rep="<?php echo esc_attr( $key ); ?>" style="margin:0 12px 10px;width:auto;min-width:92%">
				<thead>
					<tr>
						<?php foreach ( $columns as $column ) : ?>
							<th style="text-align:left"><?php echo esc_html( $column['label'] ); ?></th>
						<?php endforeach; ?>
						<th style="width:1%"></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $i => $row ) : ?>
						<tr>
							<?php foreach ( $columns as $name => $column ) : ?>
								<td>
									<?php
									$field = $key . '[' . (int) $i . '][' . $name . ']';
									$value = isset( $row[ $name ] ) ? (string) $row[ $name ] : '';

									if ( 'media' === $column['type'] ) {
										$src = $value ? wp_get_attachment_image_url( (int) $value, 'thumbnail' ) : '';
										?>
										<span class="pfh-rep__media">
											<img src="<?php echo esc_url( $src ? $src : '' ); ?>" alt="" style="width:34px;height:34px;object-fit:contain;vertical-align:middle;<?php echo $src ? '' : 'display:none'; ?>" />
											<input type="hidden" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>" />
											<button type="button" class="button pfh-rep__pick"><?php esc_html_e( 'Choose', 'pfh-widgets' ); ?></button>
											<button type="button" class="button-link pfh-rep__drop"<?php echo $src ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Remove', 'pfh-widgets' ); ?></button>
										</span>
										<?php
									} elseif ( 'textarea' === $column['type'] ) {
										printf(
											'<textarea name="%s" rows="2" style="width:100%%" placeholder="%s">%s</textarea>',
											esc_attr( $field ),
											esc_attr( isset( $column['placeholder'] ) ? $column['placeholder'] : '' ),
											esc_textarea( $value )
										);
									} else {
										printf(
											'<input type="text" name="%s" value="%s" style="width:100%%" placeholder="%s" />',
											esc_attr( $field ),
											esc_attr( $value ),
											esc_attr( isset( $column['placeholder'] ) ? $column['placeholder'] : '' )
										);
									}
									?>
								</td>
							<?php endforeach; ?>
							<td><button type="button" class="button pfh-rep__remove" aria-label="<?php esc_attr_e( 'Remove this row', 'pfh-widgets' ); ?>">&times;</button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p style="margin:0 12px 12px">
				<button type="button" class="button pfh-rep__add"><?php esc_html_e( 'Add a row', 'pfh-widgets' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * One script for every repeating field on the screen.
	 */
	private static function script() {
		static $done = false;

		if ( $done ) {
			return;
		}

		$done = true;
		?>
		<script>
		( function () {
			/*
			 * Row names are renumbered after every change. PHP takes the array
			 * as it arrives, so a gap is harmless — but a duplicate index
			 * quietly drops a row.
			 */
			function renumber( table ) {
				Array.prototype.forEach.call( table.tBodies[ 0 ].rows, function ( row, i ) {
					Array.prototype.forEach.call( row.querySelectorAll( '[name]' ), function ( field ) {
						field.name = field.name.replace( /\[\d+\]/, '[' + i + ']' );
					} );
				} );
			}

			function blank( row ) {
				Array.prototype.forEach.call( row.querySelectorAll( 'input, textarea' ), function ( field ) {
					field.value = '';
				} );

				Array.prototype.forEach.call( row.querySelectorAll( '.pfh-rep__media img' ), function ( image ) {
					image.src = '';
					image.style.display = 'none';
				} );

				Array.prototype.forEach.call( row.querySelectorAll( '.pfh-rep__drop' ), function ( drop ) {
					drop.style.display = 'none';
				} );
			}

			Array.prototype.forEach.call( document.querySelectorAll( '.pfh-rep' ), function ( table ) {
				var body = table.tBodies[ 0 ];
				var group = table.closest( '.options_group' );

				group.querySelector( '.pfh-rep__add' ).addEventListener( 'click', function () {
					var row = body.rows[ body.rows.length - 1 ].cloneNode( true );

					blank( row );
					body.appendChild( row );
					renumber( table );
					row.querySelector( 'input, textarea' ).focus();
				} );

				body.addEventListener( 'click', function ( event ) {
					var remove = event.target.closest( '.pfh-rep__remove' );
					var pick = event.target.closest( '.pfh-rep__pick' );
					var drop = event.target.closest( '.pfh-rep__drop' );

					if ( remove ) {
						// Always leave one row, so there is something to add to.
						if ( body.rows.length > 1 ) {
							remove.closest( 'tr' ).remove();
						} else {
							blank( body.rows[ 0 ] );
						}

						renumber( table );

						return;
					}

					if ( drop ) {
						var cell = drop.closest( '.pfh-rep__media' );

						cell.querySelector( 'input' ).value = '';
						cell.querySelector( 'img' ).style.display = 'none';
						drop.style.display = 'none';

						return;
					}

					if ( ! pick || ! window.wp || ! window.wp.media ) {
						return;
					}

					var host = pick.closest( '.pfh-rep__media' );
					var frame = window.wp.media( { title: pick.textContent, multiple: false, library: { type: 'image' } } );

					frame.on( 'select', function () {
						var image = frame.state().get( 'selection' ).first().toJSON();
						var thumb = ( image.sizes && image.sizes.thumbnail ) ? image.sizes.thumbnail.url : image.url;

						host.querySelector( 'input' ).value = image.id;
						host.querySelector( 'img' ).src = thumb;
						host.querySelector( 'img' ).style.display = '';
						host.querySelector( '.pfh-rep__drop' ).style.display = '';
					} );

					frame.open();
				} );
			} );
		}() );
		</script>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Saving
	 * ------------------------------------------------------------------ */

	/**
	 * @param int $product_id Product being saved.
	 */
	public static function save( $product_id ) {
		// WooCommerce has already checked the nonce and capability by here.
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		$plain = [
			self::BADGE           => 'text',
			self::STORAGE_TITLE   => 'text',
			self::NUTRITION_TITLE => 'text',
			self::INGREDIENTS     => 'html',
			self::ALLERGENS       => 'html',
			self::NUTRITION_INTRO => 'html',
		];

		foreach ( $plain as $key => $kind ) {
			$raw   = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			$value = 'html' === $kind ? wp_kses_post( (string) $raw ) : sanitize_text_field( (string) $raw );

			self::put( $product_id, $key, trim( $value ) );
		}

		// A checkbox that is off is simply absent, so "no" has to be written.
		self::put( $product_id, self::STEPS_SHOW, isset( $_POST[ self::STEPS_SHOW ] ) ? 'yes' : 'no', true );

		$columns = [];

		if ( isset( $_POST[ self::NUTRITION_COLS ] ) && is_array( $_POST[ self::NUTRITION_COLS ] ) ) {
			foreach ( wp_unslash( $_POST[ self::NUTRITION_COLS ] ) as $column ) {
				$columns[] = sanitize_text_field( (string) $column );
			}
		}

		self::put( $product_id, self::NUTRITION_COLS, array_filter( $columns ) ? $columns : '' );

		foreach ( self::repeaters() as $key => $spec ) {
			$rows = [];

			if ( isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) {
				foreach ( wp_unslash( $_POST[ $key ] ) as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}

					$clean = [];

					foreach ( $spec['columns'] as $name => $column ) {
						$value = isset( $row[ $name ] ) ? (string) $row[ $name ] : '';

						if ( 'media' === $column['type'] ) {
							$clean[ $name ] = (string) absint( $value );
						} elseif ( 'textarea' === $column['type'] ) {
							$clean[ $name ] = wp_kses_post( $value );
						} else {
							$clean[ $name ] = sanitize_text_field( $value );
						}
					}

					// A row is a row only if something other than its icon is
					// filled in — an icon on its own has nothing to label.
					$words = $clean;
					unset( $words['icon'] );

					if ( implode( '', $words ) !== '' ) {
						$rows[] = $clean;
					}
				}
			}

			self::put( $product_id, $key, $rows );
		}
		// phpcs:enable
	}

	/**
	 * Write a value, or remove the row when there is nothing to write.
	 *
	 * @param int    $product_id Product.
	 * @param string $key        Meta key.
	 * @param mixed  $value      Value.
	 * @param bool   $keep_empty Write it even when it is falsy.
	 */
	private static function put( $product_id, $key, $value, $keep_empty = false ) {
		/*
		 * The same treatment Bricks' own data gets: where the postmeta column
		 * is utf8, a 4-byte character has the whole write refused and the
		 * product appears not to save.
		 */
		if ( class_exists( 'PFH_Widgets_Save_Guard' ) && PFH_Widgets_Save_Guard::needed() ) {
			$value = PFH_Widgets_Save_Guard::encode( $value );
		}

		if ( ! $keep_empty && ( '' === $value || [] === $value ) ) {
			delete_post_meta( $product_id, $key );

			return;
		}

		update_post_meta( $product_id, $key, $value );
	}

	/* ---------------------------------------------------------------------
	 * Reading them back
	 * ------------------------------------------------------------------ */

	/**
	 * A plain field.
	 *
	 * @param int    $product_id Product.
	 * @param string $key        Meta key.
	 * @return string
	 */
	public static function text( $product_id, $key ) {
		$value = get_post_meta( (int) $product_id, $key, true );

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Is the steps panel wanted for this product? Yes unless it was turned off.
	 *
	 * @param int $product_id Product.
	 * @return bool
	 */
	public static function steps_shown( $product_id ) {
		return 'no' !== self::text( $product_id, self::STEPS_SHOW );
	}

	/**
	 * The three column headings, empty where the element's wording should win.
	 *
	 * @param int $product_id Product.
	 * @return array<int, string>
	 */
	public static function columns( $product_id ) {
		$value = get_post_meta( (int) $product_id, self::NUTRITION_COLS, true );
		$value = is_array( $value ) ? array_values( $value ) : [];

		return [
			isset( $value[0] ) ? (string) $value[0] : '',
			isset( $value[1] ) ? (string) $value[1] : '',
			isset( $value[2] ) ? (string) $value[2] : '',
		];
	}

	/**
	 * A repeating field's rows, with every column present.
	 *
	 * Also understands the shapes another field plugin would store: a plain
	 * list of strings, or ACF's own key names.
	 *
	 * @param int    $product_id Product.
	 * @param string $key        Meta key.
	 * @param string $shape      Which repeater's columns to read it as, when
	 *                           the key is one of the editor's own.
	 * @return array<int, array<string, string>>
	 */
	public static function rows( $product_id, $key, $shape = '' ) {
		$shape   = $shape ? $shape : $key;
		$spec    = isset( self::repeaters()[ $shape ] ) ? self::repeaters()[ $shape ] : null;
		$columns = $spec ? array_keys( $spec['columns'] ) : [ 'label' ];
		$stored  = get_post_meta( (int) $product_id, $key, true );
		$rows    = [];

		if ( ! is_array( $stored ) ) {
			return $rows;
		}

		$aliases = [
			'label'   => [ 'label', 'text', 'title', 'name', 'highlight', 'claim' ],
			'note'    => [ 'note', 'value', 'meta', 'suffix' ],
			'heading' => [ 'heading', 'title', 'label' ],
			'text'    => [ 'text', 'description', 'content', 'body' ],
			'icon'    => [ 'icon', 'image', 'media' ],
			'c1'      => [ 'c1', 'label', 'name', 'nutrient' ],
			'c2'      => [ 'c2', 'value', 'per100' ],
			'c3'      => [ 'c3', 'portion' ],
		];

		foreach ( $stored as $row ) {
			$clean = [];

			foreach ( $columns as $column ) {
				if ( is_scalar( $row ) ) {
					// A plain list: the first column is the whole of it.
					$clean[ $column ] = $column === $columns[0] ? trim( (string) $row ) : '';

					continue;
				}

				$clean[ $column ] = is_array( $row )
					? self::first( $row, isset( $aliases[ $column ] ) ? $aliases[ $column ] : [ $column ] )
					: '';
			}

			$words = $clean;
			unset( $words['icon'] );

			if ( implode( '', $words ) !== '' ) {
				$rows[] = $clean;
			}
		}

		return $rows;
	}

	/**
	 * The badge for a product, if it has one.
	 *
	 * @param int    $product_id Product.
	 * @param string $meta_key   Where to read it from.
	 * @return string
	 */
	public static function badge( $product_id, $meta_key = self::BADGE ) {
		return self::text( $product_id, $meta_key ? $meta_key : self::BADGE );
	}

	/**
	 * The highlights for a product, as label/note rows.
	 *
	 * @param int    $product_id Product.
	 * @param string $meta_key   Where to read them from.
	 * @return array<int, array{label: string, note: string}>
	 */
	public static function highlights( $product_id, $meta_key = self::HIGHLIGHTS ) {
		$rows = self::rows( $product_id, $meta_key ? $meta_key : self::HIGHLIGHTS, self::HIGHLIGHTS );
		$out  = [];

		foreach ( $rows as $row ) {
			$out[] = [
				'label' => isset( $row['label'] ) ? $row['label'] : '',
				'note'  => isset( $row['note'] ) ? $row['note'] : '',
			];
		}

		return $out;
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
