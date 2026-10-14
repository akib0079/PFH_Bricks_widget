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
	const INGR_TITLE      = '_pfh_ingredients_title';
	const ALLERGENS_TITLE = '_pfh_allergens_title';
	const FREE_FROM_TITLE = '_pfh_free_from_title';
	const TAB_NAMES       = '_pfh_tab_names';
	const TABS            = '_pfh_tabs';
	const STORAGE_TITLE   = '_pfh_storage_title';
	const STORAGE         = '_pfh_storage';
	const NUTRITION_TITLE = '_pfh_nutrition_title';
	const NUTRITION_INTRO = '_pfh_nutrition_intro';
	const NUTRITION_COLS  = '_pfh_nutrition_cols';
	const NUTRITION       = '_pfh_nutrition';

	/* What the pills' heading says when nobody has said otherwise. */
	const FREE_FROM_DEFAULT = 'Bijzonder';

	/* The old shop's tabs (its theme's "custom tabs"), read only. */
	const LEGACY_TABS = 'wb_custom_tabs';

	/* The sections under the product. */
	const HIGHLIGHT_TITLE = '_pfh_highlight_title';
	const RELATED         = '_pfh_related';
	const BOTTOM_IMAGE    = '_pfh_bottom_image';
	const USP_EYEBROW     = '_pfh_usp_eyebrow';
	const USP_TITLE       = '_pfh_usp_title';
	const USP             = '_pfh_usp';
	const FAQ             = '_pfh_faq';

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

			// The editor for the product's own tabs (wp.editor).
			wp_enqueue_editor();
		}
	}

	/**
	 * Every repeating field, and the columns it holds.
	 *
	 * @return array
	 */
	/**
	 * The repeaters' image columns, by meta key — what a design export has
	 * to renumber when it moves a product's fields to another site.
	 *
	 * @return array<string, string[]>
	 */
	public static function media_columns() {
		$out = [];

		foreach ( self::repeaters() as $key => $spec ) {
			foreach ( (array) ( $spec['columns'] ?? [] ) as $name => $column ) {
				if ( 'media' === ( $column['type'] ?? '' ) ) {
					$out[ $key ][] = $name;
				}
			}
		}

		return $out;
	}

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
				'label'   => __( 'Pills', 'pfh-widgets' ),
				'help'    => __( 'One per line. Each becomes a ticked pill under the ingredients, below the heading above.', 'pfh-widgets' ),
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
			self::USP        => [
				'label'   => __( 'Why this product', 'pfh-widgets' ),
				'help'    => __( 'One card each. Leave the colour empty and they take the four pastels in turn.', 'pfh-widgets' ),
				'columns' => [
					'icon'  => [ 'label' => __( 'Icon', 'pfh-widgets' ), 'type' => 'media' ],
					'label' => [ 'label' => __( 'Title', 'pfh-widgets' ), 'type' => 'text', 'placeholder' => 'Authentiek Grieks' ],
					'text'  => [ 'label' => __( 'Text', 'pfh-widgets' ), 'type' => 'textarea', 'placeholder' => 'Gemaakt van vers geoogst Grieks fruit.' ],
					'color' => [ 'label' => __( 'Background', 'pfh-widgets' ), 'type' => 'text', 'placeholder' => '#F9E9CF' ],
				],
			],
			self::FAQ        => [
				'label'   => __( 'Questions', 'pfh-widgets' ),
				'help'    => __( 'This product\'s own questions. With none, the element falls back to the ones set on it.', 'pfh-widgets' ),
				'columns' => [
					'label' => [ 'label' => __( 'Question', 'pfh-widgets' ), 'type' => 'text', 'placeholder' => 'Is Gia giamas gemaakt van echt fruit?' ],
					'text'  => [ 'label' => __( 'Answer', 'pfh-widgets' ), 'type' => 'textarea' ],
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

			<?php
			/**
			 * Fires inside the Products For Home tab, under its first fields.
			 *
			 * @param int $id Product.
			 */
			do_action( 'pfh_widgets_product_panel', $id );
			?>

			<?php self::section( esc_html__( 'Product section', 'pfh-widgets' ) ); ?>
			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					[
						'id'          => self::HIGHLIGHT_TITLE,
						'value'       => self::text( $id, self::HIGHLIGHT_TITLE ),
						'label'       => esc_html__( 'Highlight title', 'pfh-widgets' ),
						'placeholder' => 'Smaak — Mandarijn',
						'desc_tip'    => true,
						'description' => esc_html__( 'The small line above the highlights, different for every product. Empty leaves it out.', 'pfh-widgets' ),
					]
				);
				?>
			</div>
			<?php self::repeater( $id, self::HIGHLIGHTS ); ?>

			<?php self::section( esc_html__( 'Tab names', 'pfh-widgets' ) ); ?>
			<div class="options_group">
				<?php $names = self::tab_names( $id ); ?>
				<p class="form-field">
					<label><?php esc_html_e( 'This product\'s tabs', 'pfh-widgets' ); ?></label>
					<?php foreach ( self::tab_defaults() as $key => $placeholder ) : ?>
						<input type="text" style="width:23%;margin-right:1%"
							name="<?php echo esc_attr( self::TAB_NAMES ); ?>[<?php echo esc_attr( $key ); ?>]"
							value="<?php echo esc_attr( $names[ $key ] ); ?>"
							placeholder="<?php echo esc_attr( $placeholder ); ?>"
							aria-label="<?php echo esc_attr( $placeholder ); ?>" />
					<?php endforeach; ?>
					<span class="description" style="display:block;margin:6px 0 0">
						<?php esc_html_e( 'Renames a tab on this product only. Empty keeps the name set on the element. A tab with nothing in it is not shown.', 'pfh-widgets' ); ?>
					</span>
				</p>
			</div>

			<?php self::section( esc_html__( 'Tabs of its own (Inhoud, …)', 'pfh-widgets' ) ); ?>
			<?php self::tabs_editor( $id ); ?>

			<?php self::section( esc_html__( 'Tab: Ingredienten', 'pfh-widgets' ) ); ?>
			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					[
						'id'          => self::INGR_TITLE,
						'value'       => self::text( $id, self::INGR_TITLE ),
						'label'       => esc_html__( 'Heading', 'pfh-widgets' ),
						'placeholder' => 'Ingrediënten',
						'desc_tip'    => true,
						'description' => esc_html__( 'The heading at the top of the tab. Empty uses the wording set on the element.', 'pfh-widgets' ),
					]
				);

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

				woocommerce_wp_text_input(
					[
						'id'          => self::ALLERGENS_TITLE,
						'value'       => self::text( $id, self::ALLERGENS_TITLE ),
						'label'       => esc_html__( 'Allergens heading', 'pfh-widgets' ),
						'placeholder' => 'Allergenen',
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
			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					[
						'id'          => self::FREE_FROM_TITLE,
						'value'       => self::text( $id, self::FREE_FROM_TITLE ),
						'label'       => esc_html__( 'Heading above the pills', 'pfh-widgets' ),
						'placeholder' => self::FREE_FROM_DEFAULT,
						'desc_tip'    => true,
						'description' => esc_html__( 'The small heading above the ticked pills. Empty says "Bijzonder".', 'pfh-widgets' ),
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

			<?php self::section( esc_html__( 'Related products', 'pfh-widgets' ) ); ?>
			<div class="options_group">
				<p class="form-field">
					<label for="<?php echo esc_attr( self::RELATED ); ?>"><?php esc_html_e( 'Show these products', 'pfh-widgets' ); ?></label>
					<select class="wc-product-search" multiple="multiple" style="width:50%" id="<?php echo esc_attr( self::RELATED ); ?>"
						name="<?php echo esc_attr( self::RELATED ); ?>[]"
						data-placeholder="<?php esc_attr_e( 'Search for a product…', 'pfh-widgets' ); ?>"
						data-action="woocommerce_json_search_products_and_variations"
						data-exclude="<?php echo esc_attr( $id ); ?>">
						<?php
						foreach ( self::related( $id ) as $related_id ) {
							$related = wc_get_product( $related_id );

							if ( $related ) {
								printf( '<option value="%s" selected="selected">%s</option>', esc_attr( $related_id ), esc_html( wp_strip_all_tags( $related->get_formatted_name() ) ) );
							}
						}
						?>
					</select>
					<span class="description" style="display:block;margin:6px 0 0">
						<?php esc_html_e( 'Leave empty and the section shows other products from this product\'s own category. With neither, it is not shown at all.', 'pfh-widgets' ); ?>
					</span>
				</p>
			</div>

			<?php self::section( esc_html__( 'Bottom add to cart', 'pfh-widgets' ) ); ?>
			<div class="options_group">
				<?php
				self::media_field(
					$id,
					self::BOTTOM_IMAGE,
					esc_html__( 'Picture', 'pfh-widgets' ),
					esc_html__( 'Shown beside the reminder at the bottom of the page. A cut-out on a transparent background is what the design uses. Empty uses the product image.', 'pfh-widgets' )
				);
				?>
			</div>

			<?php self::section( esc_html__( 'Why this product', 'pfh-widgets' ) ); ?>
			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					[
						'id'          => self::USP_EYEBROW,
						'value'       => self::text( $id, self::USP_EYEBROW ),
						'label'       => esc_html__( 'Eyebrow', 'pfh-widgets' ),
						'placeholder' => 'WAAROM GIA...GIAMAS',
					]
				);

				woocommerce_wp_text_input(
					[
						'id'          => self::USP_TITLE,
						'value'       => self::text( $id, self::USP_TITLE ),
						'label'       => esc_html__( 'Section title', 'pfh-widgets' ),
						'placeholder' => 'Puur natuur, <em>ongeevenaard</em> van smaak',
						'desc_tip'    => true,
						'description' => esc_html__( 'Wrap a word in <em> to set it in the italic serif, as drawn.', 'pfh-widgets' ),
					]
				);
				?>
			</div>
			<?php self::repeater( $id, self::USP ); ?>

			<?php self::section( esc_html__( 'Questions', 'pfh-widgets' ) ); ?>
			<?php self::repeater( $id, self::FAQ ); ?>
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
	 * One picture, chosen from the media library.
	 *
	 * The same markup the repeater's picture cells use, so it looks like the
	 * rest of the box; the handler below is its own, because the repeater's is
	 * bound to that table rather than to the page.
	 *
	 * @param int    $id          Product.
	 * @param string $key         Meta key.
	 * @param string $label       Field label.
	 * @param string $description Help text.
	 */
	private static function media_field( $id, $key, $label, $description ) {
		static $printed = false;

		$value = (int) self::text( $id, $key );
		$src   = $value ? wp_get_attachment_image_url( $value, 'thumbnail' ) : '';
		?>
		<p class="form-field">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<span class="pfh-rep__media pfh-one__media">
				<img src="<?php echo esc_url( $src ? $src : '' ); ?>" alt="" style="width:46px;height:46px;object-fit:contain;vertical-align:middle;<?php echo $src ? '' : 'display:none'; ?>" />
				<input type="hidden" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" />
				<button type="button" class="button pfh-one__pick"><?php esc_html_e( 'Choose', 'pfh-widgets' ); ?></button>
				<button type="button" class="button-link pfh-one__drop"<?php echo $src ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Remove', 'pfh-widgets' ); ?></button>
			</span>
			<span class="description" style="display:block;margin:6px 0 0"><?php echo esc_html( $description ); ?></span>
		</p>
		<?php

		if ( $printed ) {
			return;
		}

		$printed = true;
		?>
		<script>
		( function () {
			document.addEventListener( 'click', function ( event ) {
				var pick = event.target.closest( '.pfh-one__pick' );
				var drop = event.target.closest( '.pfh-one__drop' );
				var host = ( pick || drop ) && ( pick || drop ).closest( '.pfh-one__media' );

				if ( ! host ) {
					return;
				}

				if ( drop ) {
					host.querySelector( 'input' ).value = '';
					host.querySelector( 'img' ).style.display = 'none';
					drop.style.display = 'none';

					return;
				}

				if ( ! window.wp || ! window.wp.media ) {
					return;
				}

				var frame = window.wp.media( { title: pick.textContent, multiple: false, library: { type: 'image' } } );

				frame.on( 'select', function () {
					var image = frame.state().get( 'selection' ).first().toJSON();
					var thumb = ( image.sizes && image.sizes.thumbnail ) ? image.sizes.thumbnail.url : image.url;

					host.querySelector( 'input' ).value = image.id;
					host.querySelector( 'img' ).src = thumb;
					host.querySelector( 'img' ).style.display = '';
					host.querySelector( '.pfh-one__drop' ).style.display = '';
				} );

				frame.open();
			} );
		}() );
		</script>
		<?php
	}

	/**
	 * The product's own tabs: a name and a text each.
	 *
	 * Until a product is saved with this editor, it shows the old shop's tabs
	 * (Inhoud, Ingrediënten …), so what is on the page is what can be edited
	 * here — nothing is lost, and nothing is shown that cannot be changed.
	 * Saving the product makes them its own; a tab emptied here disappears.
	 *
	 * @param int $id Product.
	 */
	private static function tabs_editor( $id ) {
		$own  = self::has_tabs( $id );
		$rows = $own ? self::tabs( $id ) : array_map(
			static function ( $row ) {
				return [ 'title' => $row['title'], 'content' => self::tidy( $row['content'] ) ];
			},
			self::legacy_tabs( $id )
		);
		?>
		<div class="options_group pfh-xtabs" data-pfh-xtabs data-next="<?php echo (int) count( $rows ); ?>">
			<p class="form-field">
				<label><?php esc_html_e( 'Tabs', 'pfh-widgets' ); ?></label>
				<span class="description">
					<?php esc_html_e( 'Each one is a tab of its own on the product page, after Omschrijving, with its name as the heading. A tab with no text is not shown; remove it to take it away for good.', 'pfh-widgets' ); ?>
					<?php if ( ! $own && $rows ) : ?>
						<br /><strong><?php esc_html_e( 'These came over from the old shop. Edit them here; they are saved with the product.', 'pfh-widgets' ); ?></strong>
					<?php endif; ?>
				</span>
			</p>
			<input type="hidden" name="<?php echo esc_attr( self::TABS ); ?>_sent" value="1" />
			<div class="pfh-xtabs__list">
				<?php
				foreach ( $rows as $i => $row ) {
					self::tab_row( $id, (string) $i, $row['title'], $row['content'] );
				}
				?>
			</div>
			<script type="text/template" class="pfh-xtabs__template"><?php self::tab_row( $id, '__i__', '', '' ); ?></script>
			<p style="margin:0 12px 12px">
				<button type="button" class="button pfh-xtabs__add"><?php esc_html_e( 'Add a tab', 'pfh-widgets' ); ?></button>
			</p>
		</div>
		<style>
			.pfh-xtab { margin: 0 12px 12px; border: 1px solid #dcdcde; border-radius: 4px; background: #fff; }
			.pfh-xtab .form-field { margin: 0; }
			.pfh-xtab__body { padding: 0 20px 12px 162px; }
			/*
			 * WooCommerce sizes the panel's fields at half width and floats
			 * them, and Meta for WooCommerce indents every editor in the panel
			 * and stops it at 550px: this one spans the box under its name.
			 */
			.pfh-xtab__body textarea,
			.pfh-xtab__body .wp-editor-wrap { float: none !important; width: 100% !important; }
			.pfh-xtab__body .wp-editor-container { float: none !important; width: auto !important; max-width: none !important; margin-left: 0 !important; }
			.pfh-xtab__body textarea { min-height: 140px; }
			.pfh-xtab__note { display: block; margin: 4px 0 0; color: #646970; }
			.pfh-xtab__note.is-hidden { color: #b32d2e; }
			.pfh-xtab__remove { margin-left: 8px !important; color: #b32d2e !important; }
		</style>
		<script>
		( function () {
			var box = document.querySelector( '[data-pfh-xtabs]' );

			if ( ! box ) {
				return;
			}

			var list = box.querySelector( '.pfh-xtabs__list' );
			var template = box.querySelector( '.pfh-xtabs__template' ).innerHTML;
			var next = parseInt( box.getAttribute( 'data-next' ), 10 ) || 0;

			// The visual editor where WordPress has one; the plain box is
			// still a working field where it does not.
			function editor( area ) {
				if ( area.getAttribute( 'data-ready' ) || ! window.wp || ! window.wp.editor || ! window.wp.editor.initialize ) {
					return;
				}

				area.setAttribute( 'data-ready', '1' );
				window.wp.editor.initialize( area.id, {
					tinymce: {
						wpautop: true,
						height: 180,
						toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
						block_formats: 'Alinea=p;Kop 3=h3;Kop 4=h4'
					},
					quicktags: { buttons: 'strong,em,link,ul,ol,li' },
					mediaButtons: false
				} );
			}

			/*
			 * An editor started inside a hidden panel draws at no height, and
			 * this panel is hidden until its tab is clicked: start each one
			 * when it first comes into view.
			 */
			var watcher = 'IntersectionObserver' in window ? new window.IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						watcher.unobserve( entry.target );
						editor( entry.target );
					}
				} );
			} ) : null;

			function watch( area ) {
				if ( watcher ) {
					watcher.observe( area );
				} else {
					editor( area );
				}
			}

			Array.prototype.forEach.call( list.querySelectorAll( 'textarea' ), watch );

			box.querySelector( '.pfh-xtabs__add' ).addEventListener( 'click', function () {
				var holder = document.createElement( 'div' );

				// Never reuse a number: a duplicate index quietly drops a tab.
				holder.innerHTML = template.replace( /__i__/g, String( next++ ) ).trim();

				var row = holder.firstElementChild;

				list.appendChild( row );
				watch( row.querySelector( 'textarea' ) );
				row.querySelector( 'input[type="text"]' ).focus();
			} );

			list.addEventListener( 'click', function ( event ) {
				var remove = event.target.closest( '.pfh-xtab__remove' );

				if ( ! remove ) {
					return;
				}

				event.preventDefault();

				var row = remove.closest( '.pfh-xtab' );
				var area = row.querySelector( 'textarea' );

				if ( area.getAttribute( 'data-ready' ) && window.wp.editor.remove ) {
					window.wp.editor.remove( area.id );
				}

				row.remove();
			} );
		}() );
		</script>
		<?php
	}

	/**
	 * One tab in the editor.
	 *
	 * @param int    $id      Product.
	 * @param string $i       Its number, or the template's placeholder.
	 * @param string $title   Name.
	 * @param string $content Text.
	 */
	private static function tab_row( $id, $i, $title, $content ) {
		$field = self::TABS . '[' . $i . ']';
		$area  = 'pfh-xtab-' . $i;
		$note  = '' !== $title ? self::tab_note( $id, $title ) : null;
		?>
		<div class="pfh-xtab">
			<p class="form-field">
				<label for="<?php echo esc_attr( $area ); ?>-title"><?php esc_html_e( 'Tab name', 'pfh-widgets' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $area ); ?>-title" name="<?php echo esc_attr( $field ); ?>[title]" value="<?php echo esc_attr( $title ); ?>" placeholder="Inhoud" style="width:50%" />
				<button type="button" class="button-link pfh-xtab__remove"><?php esc_html_e( 'Remove this tab', 'pfh-widgets' ); ?></button>
				<?php if ( $note ) : ?>
					<span class="pfh-xtab__note<?php echo $note['shown'] ? '' : ' is-hidden'; ?>"><?php echo esc_html( $note['text'] ); ?></span>
				<?php endif; ?>
			</p>
			<div class="pfh-xtab__body">
				<label for="<?php echo esc_attr( $area ); ?>" class="screen-reader-text"><?php esc_html_e( 'Text', 'pfh-widgets' ); ?></label>
				<textarea id="<?php echo esc_attr( $area ); ?>" name="<?php echo esc_attr( $field ); ?>[content]" rows="8"><?php echo esc_textarea( $content ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * What a tab named like one of the built-in ones does: it fills that tab
	 * while the fields for it are empty, as the old shop's text always has.
	 *
	 * @param int    $id    Product.
	 * @param string $title Tab name.
	 * @return array{text: string, shown: bool}|null Null for a tab of its own.
	 */
	private static function tab_note( $id, $title ) {
		$key = self::builtin_key( $title );

		if ( ! $key || 'desc' === $key ) {
			return null;
		}

		$label = self::tab_defaults()[ $key ];

		if ( self::fills( $id, $key ) ) {
			return [
				/* translators: %s: tab name. */
				'text'  => sprintf( __( 'Not shown: the %s fields below are filled in, and they come first. Empty them to show this text instead, or remove this tab.', 'pfh-widgets' ), $label ),
				'shown' => false,
			];
		}

		return [
			/* translators: %s: tab name. */
			'text'  => sprintf( __( 'Shown as the %s tab, because its own fields below are empty.', 'pfh-widgets' ), $label ),
			'shown' => true,
		];
	}

	/**
	 * The picture the bottom reminder should use.
	 *
	 * The product's own choice, then whatever WooCommerce already shows for
	 * it — a product nobody has set a cut-out for still has a picture.
	 *
	 * @param int    $id   Product.
	 * @param string $size Image size.
	 * @return string URL, or '' when the product has no picture at all.
	 */
	public static function bottom_image( $id, $size = 'large' ) {
		$own = (int) self::text( $id, self::BOTTOM_IMAGE );

		if ( $own ) {
			$url = wp_get_attachment_image_url( $own, $size );

			if ( $url ) {
				return (string) $url;
			}
		}

		$featured = (int) get_post_thumbnail_id( $id );

		/*
		 * No picture chosen for it: the first cut-out among the product's
		 * own photographs, so the card shows the product rather than a white
		 * box on mint (feedback, 2026-09-28: "delete the background of the
		 * image here"). The featured image only when there is no cut-out.
		 */
		if ( class_exists( 'PFH_Widgets_Photo' ) ) {
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
			$gallery = $product ? array_map( 'intval', (array) $product->get_gallery_image_ids() ) : [];

			foreach ( array_filter( array_merge( [ $featured ], $gallery ) ) as $candidate ) {
				if ( ! PFH_Widgets_Photo::has_backdrop( $candidate ) ) {
					$url = wp_get_attachment_image_url( $candidate, $size );

					if ( $url ) {
						return (string) $url;
					}
				}
			}
		}

		return $featured ? (string) wp_get_attachment_image_url( $featured, $size ) : '';
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
			self::HIGHLIGHT_TITLE => 'text',
			self::USP_EYEBROW     => 'text',
			self::USP_TITLE       => 'html',
			self::STORAGE_TITLE   => 'text',
			self::NUTRITION_TITLE => 'text',
			self::INGR_TITLE      => 'text',
			self::ALLERGENS_TITLE => 'text',
			self::FREE_FROM_TITLE => 'text',
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

		self::put( $product_id, self::BOTTOM_IMAGE, isset( $_POST[ self::BOTTOM_IMAGE ] ) ? (int) absint( $_POST[ self::BOTTOM_IMAGE ] ) : 0 );

		$columns = [];

		if ( isset( $_POST[ self::NUTRITION_COLS ] ) && is_array( $_POST[ self::NUTRITION_COLS ] ) ) {
			foreach ( wp_unslash( $_POST[ self::NUTRITION_COLS ] ) as $column ) {
				$columns[] = sanitize_text_field( (string) $column );
			}
		}

		self::put( $product_id, self::NUTRITION_COLS, array_filter( $columns ) ? $columns : '' );

		$names = [];

		if ( isset( $_POST[ self::TAB_NAMES ] ) && is_array( $_POST[ self::TAB_NAMES ] ) ) {
			$sent = wp_unslash( $_POST[ self::TAB_NAMES ] );

			foreach ( array_keys( self::tab_defaults() ) as $key ) {
				$names[ $key ] = isset( $sent[ $key ] ) ? trim( sanitize_text_field( (string) $sent[ $key ] ) ) : '';
			}
		}

		self::put( $product_id, self::TAB_NAMES, array_filter( $names, 'strlen' ) ? $names : '' );

		/*
		 * The product's own tabs, only when the editor was on the form. An
		 * empty list is written, not removed: removing it would bring the old
		 * shop's tabs back, which is the opposite of what emptying it meant.
		 */
		if ( isset( $_POST[ self::TABS . '_sent' ] ) ) {
			$tabs = [];
			$sent = isset( $_POST[ self::TABS ] ) && is_array( $_POST[ self::TABS ] ) ? wp_unslash( $_POST[ self::TABS ] ) : [];

			foreach ( $sent as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$title   = trim( sanitize_text_field( isset( $row['title'] ) ? (string) $row['title'] : '' ) );
				$content = trim( wp_kses_post( isset( $row['content'] ) ? (string) $row['content'] : '' ) );

				if ( '' === $title && '' === $content ) {
					continue;
				}

				$tabs[] = [
					// The name the field suggests, rather than a tab without one.
					'title'   => '' !== $title ? $title : 'Inhoud',
					'content' => $content,
				];
			}

			self::put( $product_id, self::TABS, $tabs, true );
		}

		$related = [];

		if ( isset( $_POST[ self::RELATED ] ) && is_array( $_POST[ self::RELATED ] ) ) {
			foreach ( wp_unslash( $_POST[ self::RELATED ] ) as $related_id ) {
				$related_id = absint( $related_id );

				// A product cannot be related to itself.
				if ( $related_id && $related_id !== (int) $product_id ) {
					$related[] = $related_id;
				}
			}
		}

		self::put( $product_id, self::RELATED, array_values( array_unique( $related ) ) );

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
	 * The built-in tabs and what they are called unless renamed.
	 *
	 * @return array<string, string>
	 */
	public static function tab_defaults() {
		return [
			'desc'      => 'Omschrijving',
			'ingr'      => 'Ingrediënten',
			'storage'   => 'Houdbaarheid',
			'nutrition' => 'Voedingswaarden',
			'reviews'   => 'Reviews',
		];
	}

	/**
	 * This product's own names for the built-in tabs, '' where it has none.
	 *
	 * @param int $product_id Product.
	 * @return array<string, string>
	 */
	public static function tab_names( $product_id ) {
		$value = get_post_meta( (int) $product_id, self::TAB_NAMES, true );
		$value = is_array( $value ) ? $value : [];
		$out   = [];

		foreach ( array_keys( self::tab_defaults() ) as $key ) {
			$out[ $key ] = isset( $value[ $key ] ) && is_scalar( $value[ $key ] ) ? trim( (string) $value[ $key ] ) : '';
		}

		return $out;
	}

	/**
	 * Which built-in tab a tab name stands for, if any.
	 *
	 * @param string $title Tab name.
	 * @return string 'ingr', 'storage', 'nutrition', 'desc' or ''.
	 */
	public static function builtin_key( $title ) {
		$map = [
			'ingredienten'    => 'ingr',
			'houdbaarheid'    => 'storage',
			'voedingswaarden' => 'nutrition',
			'omschrijving'    => 'desc',
			'beschrijving'    => 'desc',
		];

		$slug = sanitize_title( remove_accents( (string) $title ) );

		return isset( $map[ $slug ] ) ? $map[ $slug ] : '';
	}

	/**
	 * Are a built-in tab's own fields filled in for this product?
	 *
	 * @param int    $product_id Product.
	 * @param string $key        'ingr', 'storage', 'nutrition' or 'desc'.
	 * @return bool
	 */
	public static function fills( $product_id, $key ) {
		switch ( $key ) {
			case 'ingr':
				return '' !== self::text( $product_id, self::INGREDIENTS )
					|| '' !== self::text( $product_id, self::ALLERGENS )
					|| (bool) self::rows( $product_id, self::FREE_FROM );
			case 'storage':
				return (bool) self::rows( $product_id, self::STORAGE );
			case 'nutrition':
				return (bool) self::rows( $product_id, self::NUTRITION );
			case 'desc':
				return true;
		}

		return false;
	}

	/**
	 * Has this product been saved with its own tabs (even none)?
	 *
	 * @param int $product_id Product.
	 * @return bool
	 */
	public static function has_tabs( $product_id ) {
		return metadata_exists( 'post', (int) $product_id, self::TABS );
	}

	/**
	 * The product's own tabs, in order.
	 *
	 * @param int $product_id Product.
	 * @return array<int, array{title: string, content: string}>
	 */
	public static function tabs( $product_id ) {
		$value = get_post_meta( (int) $product_id, self::TABS, true );
		$out   = [];

		foreach ( is_array( $value ) ? $value : [] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$title   = isset( $row['title'] ) && is_scalar( $row['title'] ) ? trim( (string) $row['title'] ) : '';
			$content = isset( $row['content'] ) && is_scalar( $row['content'] ) ? trim( (string) $row['content'] ) : '';

			if ( '' !== $title || '' !== $content ) {
				$out[] = [ 'title' => $title, 'content' => $content ];
			}
		}

		return $out;
	}

	/**
	 * The old shop's tabs for one product, as it stored them, minus the
	 * empty ones and its copy of the description.
	 *
	 * @param int $product_id Product.
	 * @return array<int, array{title: string, content: string}>
	 */
	public static function legacy_tabs( $product_id ) {
		$stored = get_post_meta( (int) $product_id, self::LEGACY_TABS, true );
		$out    = [];

		foreach ( is_array( $stored ) ? $stored : [] as $tab ) {
			if ( ! is_array( $tab ) ) {
				continue;
			}

			$title   = trim( wp_strip_all_tags( isset( $tab['title'] ) ? (string) $tab['title'] : '' ) );
			$content = trim( strip_shortcodes( isset( $tab['content'] ) ? (string) $tab['content'] : '' ) );

			if ( '' === $title || '' === trim( wp_strip_all_tags( $content ) ) || 'desc' === self::builtin_key( $title ) ) {
				continue;
			}

			$out[] = [
				// "inhoud" on one product, "Inhoud" on the rest.
				'title'   => function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $title, 0, 1 ) ) . mb_substr( $title, 1 ) : ucfirst( $title ),
				'content' => $content,
			];
		}

		return $out;
	}

	/**
	 * The old shop's markup made fit to edit: its page builder left inline
	 * styles, classes and empty spans that mean nothing here.
	 *
	 * @param string $html Old text.
	 * @return string
	 */
	public static function tidy( $html ) {
		$html = preg_replace( '/\s(?:style|class|id|data-[\w-]+)="[^"]*"/i', '', (string) $html );

		$html = wp_kses(
			$html,
			[
				'p'      => [],
				'br'     => [],
				'strong' => [],
				'b'      => [],
				'em'     => [],
				'i'      => [],
				'a'      => [ 'href' => true, 'target' => true, 'rel' => true ],
				'ul'     => [],
				'ol'     => [],
				'li'     => [],
				'h2'     => [],
				'h3'     => [],
				'h4'     => [],
				'table'  => [],
				'thead'  => [],
				'tbody'  => [],
				'tr'     => [],
				'th'     => [],
				'td'     => [],
			]
		);

		return trim( (string) $html );
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
	 * The products chosen to sit beside this one.
	 *
	 * @param int $product_id Product.
	 * @return int[]
	 */
	public static function related( $product_id ) {
		$value = get_post_meta( (int) $product_id, self::RELATED, true );

		return is_array( $value ) ? array_values( array_filter( array_map( 'absint', $value ) ) ) : [];
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
