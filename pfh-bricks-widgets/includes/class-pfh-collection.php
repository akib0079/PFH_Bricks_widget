<?php
/**
 * Per-category settings for the sections under a collection's products.
 *
 * One Bricks template draws every category, so on its own every category
 * shows the same "Inspiratie nodig?" band, the same questions and the same
 * bundle. This gives each category a say over those three sections, on the
 * screen the client already uses for that category: Products → Categories →
 * edit.
 *
 * The rules are the same for all three, and they are the whole design:
 *
 *   A category can hide a section. Nothing else can make it disappear.
 *
 *   A field the category fills in replaces that one piece. A field it leaves
 *   empty keeps whatever the section in Bricks says. So a category nobody has
 *   touched looks exactly as it always did, and "fallback content" is simply
 *   the section as it is set up in the template.
 *
 *   The bundle can be tied to a product. The product then decides the price,
 *   the saving and where the section links to — the numbers can never go
 *   stale against the shop — while every word stays the category's to write.
 *
 * Everything is stored in one term meta value. No four-byte characters are
 * kept: where the database is utf8 rather than utf8mb4, WordPress refuses to
 * write a value holding one — silently, and the whole value with it.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Collection {

	const META  = '_pfh_collection';
	const NONCE = 'pfh_collection_save';

	/** The saving line, when the category does not word its own. */
	const SAVING = 'Bespaar %amount% — %percent% korting';

	public static function init() {
		add_action( 'product_cat_edit_form_fields', [ __CLASS__, 'fields' ], 30 );
		add_action( 'edited_product_cat', [ __CLASS__, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	/* ---------------------------------------------------------------------
	 * The shape
	 * ------------------------------------------------------------------ */

	/**
	 * Every field, empty.
	 *
	 * @return array
	 */
	private static function blank() {
		return [
			'inspire' => [
				'hide'  => false,
				'title' => '',
				'text'  => '',
				'label' => '',
				'url'   => '',
			],
			'faq'     => [
				'hide'  => false,
				'title' => '',
				'items' => [],
			],
			'bundle'  => [
				'hide'         => false,
				'product'      => 0,
				'eyebrow'      => '',
				'title_top'    => '',
				'title_bottom' => '',
				'text'         => '',
				'points'       => [],
				'saving'       => '',
				'image'        => 0,
				'url'          => '',
			],
		];
	}

	/**
	 * One category's settings, every key present.
	 *
	 * @param int $term_id Category.
	 * @return array
	 */
	public static function get( $term_id ) {
		$stored = $term_id ? get_term_meta( (int) $term_id, self::META, true ) : [];
		$stored = is_array( $stored ) ? $stored : [];
		$out    = self::blank();

		foreach ( $out as $section => $fields ) {
			if ( empty( $stored[ $section ] ) || ! is_array( $stored[ $section ] ) ) {
				continue;
			}

			foreach ( $fields as $key => $empty ) {
				if ( array_key_exists( $key, $stored[ $section ] ) ) {
					$out[ $section ][ $key ] = $stored[ $section ][ $key ];
				}
			}
		}

		return $out;
	}

	/**
	 * The category this page is about, if it is about one.
	 *
	 * @return WP_Term|null
	 */
	public static function term() {
		$term = null;

		if ( function_exists( 'is_tax' ) && is_tax( 'product_cat' ) ) {
			$queried = get_queried_object();
			$term    = $queried instanceof WP_Term ? $queried : null;
		}

		/**
		 * Filter the category the collection sections follow.
		 *
		 * @param WP_Term|null $term Category being viewed, or null.
		 */
		$term = apply_filters( 'pfh_collection_term', $term );

		return $term instanceof WP_Term && 'product_cat' === $term->taxonomy ? $term : null;
	}

	/**
	 * One section's settings for the category being viewed.
	 *
	 * @param string $section 'inspire', 'faq' or 'bundle'.
	 * @return array|null Null when this is not a category page.
	 */
	public static function section( $section ) {
		$term = self::term();

		if ( ! $term ) {
			return null;
		}

		$all = self::get( $term->term_id );

		return isset( $all[ $section ] ) ? $all[ $section ] : null;
	}

	/* ---------------------------------------------------------------------
	 * The bundle's product
	 * ------------------------------------------------------------------ */

	/**
	 * What a product offers: its price, its saving and where it lives.
	 *
	 * A product that is gone, unpublished or hidden from the catalogue offers
	 * nothing, and the section falls back to what is typed in Bricks rather
	 * than advertising something nobody can buy.
	 *
	 * @param int    $product_id Product.
	 * @param string $saving     Saving line, with %amount% and %percent%.
	 * @return array|null
	 */
	public static function offer( $product_id, $saving = '' ) {
		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = wc_get_product( (int) $product_id );

		if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
			return null;
		}

		if ( $product->is_type( 'variable' ) ) {
			$now = (float) $product->get_variation_price( 'min', true );
			$was = (float) $product->get_variation_regular_price( 'min', true );
		} else {
			$now     = (float) wc_get_price_to_display( $product );
			$regular = $product->get_regular_price();
			$was     = '' !== $regular ? (float) wc_get_price_to_display( $product, [ 'price' => $regular ] ) : $now;
		}

		$on_sale = $product->is_on_sale() && $was > $now && $was > 0;
		$line    = '';

		if ( $on_sale ) {
			$line = str_replace(
				[ '%amount%', '%percent%' ],
				[ self::money( $was - $now ), (int) round( ( $was - $now ) / $was * 100 ) . '%' ],
				'' !== trim( $saving ) ? $saving : self::SAVING
			);
		}

		$image = '';

		if ( class_exists( 'PFH_Widgets_Product_Fields' ) ) {
			$image = PFH_Widgets_Product_Fields::bottom_image( $product->get_id(), 'large' );
		} elseif ( $product->get_image_id() ) {
			$image = (string) wp_get_attachment_image_url( $product->get_image_id(), 'large' );
		}

		return [
			'id'     => $product->get_id(),
			'name'   => $product->get_name(),
			'url'    => (string) get_permalink( $product->get_id() ),
			'now'    => $now > 0 ? self::money( $now ) : '',
			'was'    => $on_sale ? self::money( $was ) : '',
			'saving' => $line,
			'image'  => $image,
		];
	}

	/**
	 * A price as the shop writes it, as plain text.
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	private static function money( $amount ) {
		$html = function_exists( 'wc_price' ) ? wc_price( $amount ) : number_format_i18n( $amount, 2 );

		return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' ) );
	}

	/* ---------------------------------------------------------------------
	 * The panel
	 * ------------------------------------------------------------------ */

	/**
	 * The scripts the panel needs: the media library and WooCommerce's
	 * product search.
	 *
	 * @param string $hook Current screen.
	 */
	public static function assets( $hook ) {
		if ( 'term.php' !== $hook ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'product_cat' !== $screen->taxonomy ) {
			return;
		}

		wp_enqueue_media();

		if ( wp_script_is( 'wc-enhanced-select', 'registered' ) ) {
			wp_enqueue_script( 'wc-enhanced-select' );
		}

		if ( wp_style_is( 'woocommerce_admin_styles', 'registered' ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}
	}

	/**
	 * The three sections, on the category's own edit screen.
	 *
	 * @param WP_Term $term Category being edited.
	 */
	public static function fields( $term ) {
		$data = self::get( $term->term_id );
		$name = 'pfh_collection';
		?>
		<tr class="form-field pfh-col-row">
			<th scope="row"><?php esc_html_e( 'Collection page', 'pfh-widgets' ); ?></th>
			<td>
				<?php wp_nonce_field( self::NONCE, 'pfh_collection_nonce' ); ?>
				<p class="description pfh-col__intro"><?php esc_html_e( 'The sections under this category\'s products. Anything you leave empty keeps the text set on the section in Bricks, so a category you never touch looks exactly as it does now.', 'pfh-widgets' ); ?></p>

				<?php
				self::card(
					'inspire',
					__( 'Inspiration band', 'pfh-widgets' ),
					__( 'The strip with one line and a button — "Inspiratie nodig?"', 'pfh-widgets' ),
					$data['inspire'],
					static function () use ( $data, $name ) {
						$d = $data['inspire'];
						self::text( $name . '[inspire][title]', __( 'Title', 'pfh-widgets' ), $d['title'], 'Inspiratie nodig?' );
						self::area( $name . '[inspire][text]', __( 'Text', 'pfh-widgets' ), $d['text'], 'Ontdek recepten met Gia Giamas — van klassieke limonade tot zomerse cocktails.' );
						self::text( $name . '[inspire][label]', __( 'Button label', 'pfh-widgets' ), $d['label'], 'Bekijk alle recepten' );
						self::text( $name . '[inspire][url]', __( 'Button link', 'pfh-widgets' ), $d['url'], 'https://', 'url' );
					}
				);

				self::card(
					'faq',
					__( 'Questions', 'pfh-widgets' ),
					__( 'The "Veelgestelde vragen" accordion. Questions added here replace the section\'s own list on this category.', 'pfh-widgets' ),
					$data['faq'],
					static function () use ( $data, $name ) {
						$d = $data['faq'];
						self::text( $name . '[faq][title]', __( 'Heading', 'pfh-widgets' ), $d['title'], 'Veelgestelde Vragen' );
						self::faq_rows( $name . '[faq][items]', $d['items'] );
					}
				);

				self::card(
					'bundle',
					__( 'Bundle offer', 'pfh-widgets' ),
					__( 'The wide banner above the footer. Choose the product it sells: its price, its saving and its link then come from the product, so they are always current. The whole banner links to it.', 'pfh-widgets' ),
					$data['bundle'],
					static function () use ( $data, $name ) {
						$d = $data['bundle'];
						self::product( $name . '[bundle][product]', (int) $d['product'] );
						self::text( $name . '[bundle][eyebrow]', __( 'Eyebrow', 'pfh-widgets' ), $d['eyebrow'], 'Meest gekozen' );
						self::text( $name . '[bundle][title_top]', __( 'Title, first line', 'pfh-widgets' ), $d['title_top'], 'Proefpakket' );
						self::text( $name . '[bundle][title_bottom]', __( 'Title, second line (bold)', 'pfh-widgets' ), $d['title_bottom'], '3 smaken naar keuze' );
						self::area( $name . '[bundle][text]', __( 'Description', 'pfh-widgets' ), $d['text'], 'Ontdek de wereld van Gia Giamas. Kies zelf drie smaken uit het volledige assortiment…' );
						self::area( $name . '[bundle][points]', __( 'Selling points — one per line', 'pfh-widgets' ), implode( "\n", (array) $d['points'] ), "Kies zelf 3 smaken uit het assortiment\nIdeaal cadeau — inclusief receptenkaart\nGratis verzending bij bestelling", 4 );
						self::text( $name . '[bundle][saving]', __( 'Saving line', 'pfh-widgets' ), $d['saving'], self::SAVING, 'text', __( 'Shown only while the product is on sale. %amount% and %percent% are worked out from its price.', 'pfh-widgets' ) );
						self::media( $name . '[bundle][image]', (int) $d['image'] );
						self::text( $name . '[bundle][url]', __( 'Link instead of the product', 'pfh-widgets' ), $d['url'], 'https://', 'url', __( 'Leave empty to link to the chosen product.', 'pfh-widgets' ) );
					}
				);
				?>
			</td>
		</tr>
		<?php
		self::style();
		self::script();
	}

	/**
	 * One section as a folding card, with its hide switch and its state.
	 *
	 * @param string   $key    Section.
	 * @param string   $title  Heading.
	 * @param string   $help   What the section is.
	 * @param array    $data   Its settings.
	 * @param callable $fields Prints the fields.
	 */
	private static function card( $key, $title, $help, array $data, $fields ) {
		$state = self::state( $data );
		$names = [
			'default' => __( 'As in Bricks', 'pfh-widgets' ),
			'custom'  => __( 'Customised', 'pfh-widgets' ),
			'hidden'  => __( 'Hidden', 'pfh-widgets' ),
		];
		?>
		<details class="pfh-col__card"<?php echo 'default' !== $state ? ' open' : ''; ?>>
			<summary>
				<span class="pfh-col__card-title"><?php echo esc_html( $title ); ?></span>
				<span class="pfh-col__state pfh-col__state--<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $names[ $state ] ); ?></span>
			</summary>
			<div class="pfh-col__card-body">
				<p class="description"><?php echo esc_html( $help ); ?></p>
				<label class="pfh-col__hide">
					<input type="checkbox" name="pfh_collection[<?php echo esc_attr( $key ); ?>][hide]" value="1"<?php checked( ! empty( $data['hide'] ) ); ?> />
					<?php esc_html_e( 'Hide this section on this category', 'pfh-widgets' ); ?>
				</label>
				<div class="pfh-col__fields">
					<?php call_user_func( $fields ); ?>
				</div>
			</div>
		</details>
		<?php
	}

	/**
	 * @param array $data One section's settings.
	 * @return string 'hidden', 'custom' or 'default'.
	 */
	private static function state( array $data ) {
		if ( ! empty( $data['hide'] ) ) {
			return 'hidden';
		}

		foreach ( $data as $key => $value ) {
			if ( 'hide' !== $key && ! empty( $value ) ) {
				return 'custom';
			}
		}

		return 'default';
	}

	private static function text( $name, $label, $value, $placeholder = '', $type = 'text', $help = '' ) {
		printf(
			'<p class="pfh-col__field"><label><span>%s</span><input type="%s" name="%s" value="%s" placeholder="%s" class="widefat" /></label>%s</p>',
			esc_html( $label ),
			'url' === $type ? 'url' : 'text',
			esc_attr( $name ),
			esc_attr( (string) $value ),
			esc_attr( $placeholder ),
			$help ? '<span class="description">' . esc_html( $help ) . '</span>' : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped here.
		);
	}

	private static function area( $name, $label, $value, $placeholder = '', $rows = 2 ) {
		printf(
			'<p class="pfh-col__field"><label><span>%s</span><textarea name="%s" rows="%d" placeholder="%s" class="widefat">%s</textarea></label></p>',
			esc_html( $label ),
			esc_attr( $name ),
			(int) $rows,
			esc_attr( $placeholder ),
			esc_textarea( (string) $value )
		);
	}

	/**
	 * WooCommerce's own product search, so a shop with hundreds of products
	 * is not one enormous dropdown.
	 *
	 * @param string $name  Field name.
	 * @param int    $value Product id.
	 */
	private static function product( $name, $value ) {
		$product = $value && function_exists( 'wc_get_product' ) ? wc_get_product( $value ) : null;
		?>
		<p class="pfh-col__field">
			<label><span><?php esc_html_e( 'Product', 'pfh-widgets' ); ?></span></label>
			<select class="wc-product-search" name="<?php echo esc_attr( $name ); ?>" data-placeholder="<?php esc_attr_e( 'Search for a product…', 'pfh-widgets' ); ?>" data-action="woocommerce_json_search_products" data-allow_clear="true" style="width:100%">
				<option value=""></option>
				<?php if ( $product ) : ?>
					<option value="<?php echo (int) $product->get_id(); ?>" selected="selected"><?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?></option>
				<?php endif; ?>
			</select>
			<span class="description"><?php esc_html_e( 'With no product, the banner keeps the price typed in Bricks.', 'pfh-widgets' ); ?></span>
		</p>
		<?php
	}

	/**
	 * @param string $name  Field name.
	 * @param int    $value Attachment id.
	 */
	private static function media( $name, $value ) {
		$src = $value ? wp_get_attachment_image_url( $value, 'thumbnail' ) : '';
		?>
		<div class="pfh-col__field pfh-col__media">
			<span class="pfh-col__label"><?php esc_html_e( 'Image', 'pfh-widgets' ); ?></span>
			<span class="pfh-col__media-row">
				<img src="<?php echo esc_url( $src ? $src : '' ); ?>" alt=""<?php echo $src ? '' : ' hidden'; ?> />
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $value ? (int) $value : ''; ?>" />
				<button type="button" class="button pfh-col__pick"><?php esc_html_e( 'Choose image', 'pfh-widgets' ); ?></button>
				<button type="button" class="button-link pfh-col__drop"<?php echo $src ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'pfh-widgets' ); ?></button>
			</span>
			<span class="description"><?php esc_html_e( 'Leave empty to use the product\'s own cut-out picture, or the section\'s image when no product is chosen.', 'pfh-widgets' ); ?></span>
		</div>
		<?php
	}

	/**
	 * The questions, as rows that can be added, removed and moved.
	 *
	 * @param string $name  Field name.
	 * @param array  $items Stored questions.
	 */
	private static function faq_rows( $name, array $items ) {
		// One blank row, so there is always something to type into.
		$items = $items ? $items : [ [ 'q' => '', 'a' => '' ] ];
		?>
		<div class="pfh-col__field">
			<span class="pfh-col__label"><?php esc_html_e( 'Questions', 'pfh-widgets' ); ?></span>
			<ol class="pfh-col__faq" data-pfh-col-faq data-name="<?php echo esc_attr( $name ); ?>">
				<?php foreach ( array_values( $items ) as $i => $item ) : ?>
					<li class="pfh-col__faq-row">
						<div class="pfh-col__faq-fields">
							<input type="text" class="widefat" name="<?php echo esc_attr( $name . '[' . $i . '][q]' ); ?>" value="<?php echo esc_attr( isset( $item['q'] ) ? $item['q'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Question', 'pfh-widgets' ); ?>" />
							<textarea class="widefat" rows="2" name="<?php echo esc_attr( $name . '[' . $i . '][a]' ); ?>" placeholder="<?php esc_attr_e( 'Answer', 'pfh-widgets' ); ?>"><?php echo esc_textarea( isset( $item['a'] ) ? $item['a'] : '' ); ?></textarea>
						</div>
						<div class="pfh-col__faq-tools">
							<button type="button" class="button pfh-col__up" aria-label="<?php esc_attr_e( 'Move up', 'pfh-widgets' ); ?>">&uarr;</button>
							<button type="button" class="button pfh-col__down" aria-label="<?php esc_attr_e( 'Move down', 'pfh-widgets' ); ?>">&darr;</button>
							<button type="button" class="button pfh-col__remove" aria-label="<?php esc_attr_e( 'Remove this question', 'pfh-widgets' ); ?>">&times;</button>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
			<button type="button" class="button pfh-col__add"><?php esc_html_e( 'Add a question', 'pfh-widgets' ); ?></button>
			<span class="description"><?php esc_html_e( 'With none, the section shows its own questions from Bricks.', 'pfh-widgets' ); ?></span>
		</div>
		<?php
	}

	private static function style() {
		?>
		<style>
			.pfh-col-row > td { padding-top: 14px; }
			.pfh-col__intro { margin: 0 0 12px !important; max-width: 760px; }
			.pfh-col__card { max-width: 760px; margin: 0 0 10px; border: 1px solid #dcdcde; border-radius: 8px; background: #fff; }
			.pfh-col__card > summary { display: flex; align-items: center; gap: 10px; padding: 12px 16px; cursor: pointer; list-style: none; }
			.pfh-col__card > summary::-webkit-details-marker { display: none; }
			.pfh-col__card > summary::before { content: "\25B8"; color: #787c82; transition: transform .15s ease; }
			.pfh-col__card[open] > summary::before { transform: rotate(90deg); }
			.pfh-col__card-title { font-weight: 600; font-size: 14px; }
			.pfh-col__state { margin-left: auto; padding: 2px 9px; border-radius: 99px; font-size: 12px; line-height: 20px; background: #f0f0f1; color: #50575e; }
			.pfh-col__state--custom { background: #dfe9dc; color: #2d4a2a; }
			.pfh-col__state--hidden { background: #fcf0f1; color: #8a2424; }
			.pfh-col__card-body { padding: 0 16px 14px; border-top: 1px solid #f0f0f1; }
			.pfh-col__card-body > .description { margin: 12px 0 10px; }
			.pfh-col__hide { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px; font-weight: 500; }
			.pfh-col__card:has(.pfh-col__hide input:checked) .pfh-col__fields { opacity: .45; }
			.pfh-col__field { margin: 0 0 12px !important; }
			.pfh-col__field label > span, .pfh-col__label { display: block; margin-bottom: 4px; font-weight: 500; }
			.pfh-col__field .description { display: block; margin-top: 4px; }
			.pfh-col__media-row { display: flex; align-items: center; gap: 10px; }
			.pfh-col__media-row img { width: 56px; height: 56px; object-fit: contain; border: 1px solid #dcdcde; border-radius: 6px; background: #f6f7f7; }
			.pfh-col__media-row [hidden] { display: none !important; }
			.pfh-col__faq { margin: 0 0 8px; padding: 0; list-style: none; counter-reset: pfh-q; }
			.pfh-col__faq-row { display: flex; gap: 8px; align-items: flex-start; margin: 0 0 8px; padding: 10px; border: 1px solid #dcdcde; border-radius: 6px; background: #f6f7f7; counter-increment: pfh-q; }
			.pfh-col__faq-row::before { content: counter(pfh-q); min-width: 18px; padding-top: 6px; color: #787c82; font-weight: 600; }
			.pfh-col__faq-fields { flex: 1; display: grid; gap: 6px; }
			.pfh-col__faq-tools { display: flex; gap: 4px; }
			.pfh-col__faq-tools .button { min-width: 30px; padding: 0 6px; }
		</style>
		<?php
	}

	private static function script() {
		?>
		<script>
		( function () {
			document.addEventListener( 'click', function ( event ) {
				var t = event.target;
				var list, row;

				/* ---- the questions ---- */
				if ( t.closest( '.pfh-col__add' ) ) {
					list = t.closest( '.pfh-col__field' ).querySelector( '[data-pfh-col-faq]' );
					row = list.lastElementChild.cloneNode( true );
					row.querySelectorAll( 'input, textarea' ).forEach( function ( f ) { f.value = ''; } );
					list.appendChild( row );
					renumber( list );
					row.querySelector( 'input' ).focus();
					return;
				}

				row = t.closest( '.pfh-col__faq-row' );

				if ( row ) {
					list = row.parentNode;

					if ( t.closest( '.pfh-col__remove' ) ) {
						if ( list.children.length > 1 ) {
							row.remove();
						} else {
							row.querySelectorAll( 'input, textarea' ).forEach( function ( f ) { f.value = ''; } );
						}
					} else if ( t.closest( '.pfh-col__up' ) && row.previousElementSibling ) {
						list.insertBefore( row, row.previousElementSibling );
					} else if ( t.closest( '.pfh-col__down' ) && row.nextElementSibling ) {
						list.insertBefore( row.nextElementSibling, row );
					}

					renumber( list );
					return;
				}

				/* ---- the image ---- */
				var media = t.closest( '.pfh-col__media' );

				if ( ! media ) {
					return;
				}

				var input = media.querySelector( 'input[type=hidden]' );
				var img = media.querySelector( 'img' );
				var drop = media.querySelector( '.pfh-col__drop' );

				if ( t.closest( '.pfh-col__drop' ) ) {
					input.value = '';
					img.hidden = true;
					drop.hidden = true;
					return;
				}

				if ( ! t.closest( '.pfh-col__pick' ) || ! window.wp || ! window.wp.media ) {
					return;
				}

				var frame = window.wp.media( { multiple: false, library: { type: 'image' } } );

				frame.on( 'select', function () {
					var image = frame.state().get( 'selection' ).first().toJSON();

					input.value = image.id;
					img.src = ( image.sizes && image.sizes.thumbnail ) ? image.sizes.thumbnail.url : image.url;
					img.hidden = false;
					drop.hidden = false;
				} );

				frame.open();
			} );

			/*
			 * Indexes follow the order on screen. PHP keeps whatever order the
			 * fields arrive in, but two rows sharing an index become one.
			 */
			function renumber( list ) {
				var name = list.getAttribute( 'data-name' );

				Array.prototype.forEach.call( list.children, function ( row, i ) {
					row.querySelector( 'input' ).name = name + '[' + i + '][q]';
					row.querySelector( 'textarea' ).name = name + '[' + i + '][a]';
				} );
			}
		}() );
		</script>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Saving
	 * ------------------------------------------------------------------ */

	/**
	 * @param int $term_id Category saved.
	 */
	public static function save( $term_id ) {
		if ( ! isset( $_POST['pfh_collection_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pfh_collection_nonce'] ) ), self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every field is sanitised in clean().
		$raw   = isset( $_POST['pfh_collection'] ) && is_array( $_POST['pfh_collection'] ) ? wp_unslash( $_POST['pfh_collection'] ) : [];
		$clean = self::clean( $raw );

		// A category that says nothing stores nothing.
		if ( $clean === self::blank() ) {
			delete_term_meta( $term_id, self::META );

			return;
		}

		update_term_meta( $term_id, self::META, $clean );
	}

	/**
	 * Sanitise a submitted panel into the stored shape.
	 *
	 * Public so the tests can put a panel through exactly what a save does.
	 *
	 * @param array $raw Submitted fields.
	 * @return array
	 */
	public static function clean( array $raw ) {
		$out = self::blank();
		$get = static function ( $section, $key ) use ( $raw ) {
			return isset( $raw[ $section ][ $key ] ) ? $raw[ $section ][ $key ] : '';
		};

		foreach ( [ 'inspire', 'faq', 'bundle' ] as $section ) {
			$out[ $section ]['hide'] = ! empty( $raw[ $section ]['hide'] );
		}

		$out['inspire']['title'] = self::line( $get( 'inspire', 'title' ) );
		$out['inspire']['text']  = self::para( $get( 'inspire', 'text' ) );
		$out['inspire']['label'] = self::line( $get( 'inspire', 'label' ) );
		$out['inspire']['url']   = esc_url_raw( self::line( $get( 'inspire', 'url' ) ) );

		$out['faq']['title'] = self::line( $get( 'faq', 'title' ) );

		foreach ( is_array( $get( 'faq', 'items' ) ) ? $get( 'faq', 'items' ) : [] as $item ) {
			$q = self::line( isset( $item['q'] ) ? $item['q'] : '' );

			// A question is the row; an answer on its own is not one.
			if ( '' === $q ) {
				continue;
			}

			$out['faq']['items'][] = [
				'q' => $q,
				'a' => self::strip4( wp_kses_post( trim( (string) ( isset( $item['a'] ) ? $item['a'] : '' ) ) ) ),
			];
		}

		$product = absint( $get( 'bundle', 'product' ) );

		$out['bundle']['product']      = $product && 'product' === get_post_type( $product ) ? $product : 0;
		$out['bundle']['eyebrow']      = self::line( $get( 'bundle', 'eyebrow' ) );
		$out['bundle']['title_top']    = self::line( $get( 'bundle', 'title_top' ) );
		$out['bundle']['title_bottom'] = self::line( $get( 'bundle', 'title_bottom' ) );
		$out['bundle']['text']         = self::para( $get( 'bundle', 'text' ) );
		$out['bundle']['saving']       = self::line( $get( 'bundle', 'saving' ) );
		$out['bundle']['url']          = esc_url_raw( self::line( $get( 'bundle', 'url' ) ) );

		$image = absint( $get( 'bundle', 'image' ) );

		$out['bundle']['image'] = $image && wp_attachment_is_image( $image ) ? $image : 0;

		foreach ( preg_split( '/\R/', (string) $get( 'bundle', 'points' ) ) as $point ) {
			$point = self::line( $point );

			if ( '' !== $point ) {
				$out['bundle']['points'][] = $point;
			}
		}

		return $out;
	}

	private static function line( $value ) {
		return self::strip4( sanitize_text_field( is_scalar( $value ) ? (string) $value : '' ) );
	}

	private static function para( $value ) {
		return self::strip4( sanitize_textarea_field( is_scalar( $value ) ? (string) $value : '' ) );
	}

	/**
	 * Drop characters outside the Basic Multilingual Plane — emoji, mostly.
	 *
	 * @param string $value Text.
	 * @return string
	 */
	private static function strip4( $value ) {
		return trim( (string) preg_replace( '/[\x{10000}-\x{10FFFF}]/u', '', (string) $value ) );
	}
}
