<?php
/**
 * Shared product card behaviour.
 *
 * The card, its controls and the WooCommerce query live here so the product
 * slider and the product grid stay identical — a change to the card is a change
 * in one place. Each element still owns its own layout, heading and styling.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

trait PFH_Product_Card_Trait {

	/**
	 * Default review destination.
	 *
	 * A trait cannot hold a constant before PHP 8.2, so this is a method.
	 *
	 * @return string
	 */
	private static function review_url() {
		return PFH_Widgets_Reviews::review_url();
	}

	/**
	 * Parse a comma separated list of IDs.
	 *
	 * @param string $value Raw control value.
	 * @return int[]
	 */
	private function id_list( $value ) {
		$ids = array_map( 'absint', array_map( 'trim', explode( ',', (string) $value ) ) );

		return array_values( array_filter( $ids ) );
	}

	/**
	 * Normalise both sources into one card shape.
	 *
	 * @return array<int, array>
	 */
	/**
	 * Cards for this element, resolved once.
	 *
	 * Per instance, not a method static. A static is shared by every instance
	 * of the class, so on a page carrying two product sliders the second one
	 * returned the first one's cards and never ran its own query — which is
	 * why a category filter worked in the builder, where elements render one
	 * per request, and did nothing on the front end.
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private $pfh_cards = null;

	private function cards() {
		if ( null !== $this->pfh_cards ) {
			return $this->pfh_cards;
		}

		$this->pfh_cards = 'manual' === $this->get( 'source', 'onsale' )
			? $this->manual_cards()
			: $this->product_cards();

		return $this->pfh_cards;
	}

	/**
	 * @return array<int, array>
	 */
	private function manual_cards() {
		$cards = [];

		foreach ( (array) $this->get( 'manualCards', [] ) as $row ) {
			if ( empty( $row['title'] ) && empty( $row['image'] ) ) {
				continue;
			}

			$link = PFH_Widgets_Helpers::link( isset( $row['link'] ) ? $row['link'] : null );

			$cards[] = [
				'id'       => 0,
				'title'    => PFH_Widgets_Helpers::dd( isset( $row['title'] ) ? $row['title'] : '' ),
				'image'    => PFH_Widgets_Helpers::image_url( isset( $row['image'] ) ? $row['image'] : null, 'medium_large' ),
				'link'     => $link,
				'price'    => isset( $row['price'] ) ? $row['price'] : '',
				'oldPrice' => isset( $row['oldPrice'] ) ? $row['oldPrice'] : '',
				'onSale'   => ! empty( $row['onSale'] ),
				'rating'   => null,
				'reviews'  => null,
				'cart'     => null,
			];
		}

		return $cards;
	}

	private function default_manual_cards() {
		$cards = [ 'Gia…Giamas bundle 450ml', 'Gia…Giamas bundle 1L', 'Gia…Giamas bundle 450ml', 'Combo deal best olive oil and raw pine honey' ];
		$out   = [];

		foreach ( $cards as $title ) {
			$out[] = [
				'title'    => $title,
				'price'    => '€ 41.97 incl. VAT',
				'oldPrice' => '€ 46.97 incl. VAT',
				'onSale'   => true,
			];
		}

		return $out;
	}

	/**
	 * @return array<int, array>
	 */
	/**
	 * One product as a card array.
	 *
	 * Shared so the shop archive draws exactly the same card as the slider and
	 * the grid — three near-identical builders is how they drift apart.
	 *
	 * @param \WC_Product|false $product Product.
	 * @return array|null Null when the product should not be shown.
	 */
	private function card_from_product( $product ) {
		if ( ! $product || ! is_callable( [ $product, 'is_visible' ] ) || ! $product->is_visible() ) {
			return null;
		}

		$image = get_the_post_thumbnail_url( $product->get_id(), 'woocommerce_thumbnail' );

		return [
			'id'       => $product->get_id(),
			'title'    => $product->get_name(),
			'image'    => $image ? $image : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_thumbnail' ) : '' ),
			'link'     => [
				'href'   => $product->get_permalink(),
				'target' => '',
				'rel'    => '',
				'aria'   => '',
			],
			'price'    => $this->price_html( $product, 'current' ),
			'oldPrice' => $product->is_on_sale() ? $this->price_html( $product, 'regular' ) : '',
			'onSale'   => $product->is_on_sale(),
			'rating'   => (float) $product->get_average_rating(),
			'reviews'  => (int) $product->get_review_count(),
			'cart'     => $product,
		];
	}

	private function product_cards() {
		if ( ! PFH_Widgets_Helpers::has_woocommerce() || ! function_exists( 'wc_get_product' ) ) {
			return [];
		}

		$query = new WP_Query( $this->query_args() );
		$cards = [];

		foreach ( $query->posts as $post ) {
			$card = $this->card_from_product( wc_get_product( $post ) );

			if ( $card ) {
				$cards[] = $card;
			}
		}

		wp_reset_postdata();

		return $cards;
	}

	/**
	 * @return array WP_Query arguments.
	 */
	private function query_args() {
		$source  = (string) $this->get( 'source', 'onsale' );
		$limit   = max( 1, (int) $this->get( 'limit', 8 ) );
		$orderby = (string) $this->get( 'orderby', 'menu_order' );

		$args = [
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
		];

		$visibility = [
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => [ 'exclude-from-catalog' ],
			'operator' => 'NOT IN',
		];

		if ( $this->is_on( 'hideOutOfStock', false ) ) {
			$visibility['terms'][] = 'outofstock';
		}

		$args['tax_query'] = [ $visibility ]; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query

		switch ( $source ) {
			case 'viewed':
				$viewed = PFH_Widgets_Helpers::recently_viewed( $limit );

				/*
				 * Nothing viewed means nothing to show — and the 0 is what
				 * makes that true. An empty post__in is ignored by WP_Query,
				 * which would quietly turn "products you looked at" into
				 * "every product in the shop".
				 */
				$args['post__in'] = $viewed ? $viewed : [ 0 ];
				$args['orderby']  = 'post__in';
				unset( $args['order'] );
				break;

			case 'related':
				/*
				 * What the editor chose for this product, else the rest of its
				 * own category, else nothing at all — the section is not worth
				 * showing filled with whatever the shop happens to sell.
				 */
				$current = $this->related_to();
				$chosen  = $current ? PFH_Widgets_Product_Fields::related( $current ) : [];

				if ( $chosen ) {
					$args['post__in'] = $chosen;
					$args['orderby']  = 'post__in';
					unset( $args['order'] );

					break;
				}

				$cats = $current ? wp_get_post_terms( $current, 'product_cat', [ 'fields' => 'ids' ] ) : [];

				if ( $current && $cats && ! is_wp_error( $cats ) ) {
					$args['tax_query'][] = [
						'taxonomy'         => 'product_cat',
						'field'            => 'term_id',
						'terms'            => $cats,
						'include_children' => true,
					];

					// Never the product the shopper is already looking at. The
					// order is the shop's own, so what the editor arranged in
					// the category is what appears here.
					$args['post__not_in'] = [ $current ];

					break;
				}

				$args['post__in'] = [ 0 ];
				break;

			case 'onsale':
				$on_sale = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : [];
				// The 0 keeps post__in non-empty so an empty sale list returns nothing
				// rather than silently falling back to every product.
				$args['post__in'] = array_merge( [ 0 ], $on_sale );
				break;

			case 'featured':
				$args['tax_query'][] = [
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
				];
				break;

			case 'category':
				$cats = PFH_Widgets_Helpers::category_ids( $this->get( 'category' ) );

				if ( $cats ) {
					$args['tax_query'][] = [
						'taxonomy'         => 'product_cat',
						'field'            => 'term_id',
						'terms'            => $cats,
						'include_children' => true,
					];

					break;
				}

				/*
				 * Chosen "a product category" but nothing resolved. Returning
				 * the whole catalogue here is what made a lost setting look
				 * like a filtering bug — the shelf filled with unrelated
				 * products instead of showing that nothing was configured.
				 */
				$args['post__in'] = [ 0 ];
				break;

			case 'best':
				$orderby = 'popularity';
				break;

			case 'recent':
				$orderby = 'date';
				break;

			case 'cart':
				$args['post__in'] = $this->goes_with_cart( $limit );
				$args['orderby']  = 'post__in';
				$orderby          = '';
				unset( $args['order'] );
				break;

			case 'ids':
				$ids              = $this->id_list( $this->get( 'productIds', '' ) );
				$args['post__in'] = $ids ? $ids : [ 0 ];
				$args['orderby']  = 'post__in';
				$orderby          = '';
				break;
		}

		switch ( $orderby ) {
			case 'popularity':
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			case 'price':
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'ASC';
				break;

			case 'price-desc':
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			case 'date':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;

			case 'title':
			case 'rand':
				$args['orderby'] = $orderby;
				break;
		}

		/**
		 * Filter the product slider query.
		 *
		 * @param array  $args   WP_Query args.
		 * @param string $source Selected source.
		 */
		return apply_filters( 'pfh_widgets_products_args', $args, $source );
	}

	/**
	 * Build one price string with the configured suffix.
	 *
	 * @param WC_Product $product Product.
	 * @param string     $which   'current' or 'regular'.
	 * @return string
	 */
	/**
	 * Tokens the shared card styles read.
	 *
	 * @return array<string, string>
	 */
	/**
	 * Every CSS variable driven by a control this trait owns.
	 *
	 * The three elements that render cards used to each keep their own copy
	 * of this list, and the copies drifted: the archive had none at all, so
	 * its cards fell back to the stylesheet and the TOEVOEGEN button came
	 * out olive instead of teal. The controls live here, so the variables
	 * they feed live here too, and each element merges this into its own
	 * layout variables.
	 *
	 * @return array
	 */
	private function card_vars() {
		$lines = (int) $this->get( 'titleLines', 2 );

		return [
			'--pfh-badge-bg'         => PFH_Widgets_Helpers::color( $this->get( 'badgeBg' ), '#7d9569' ),
			'--pfh-badge-color'      => PFH_Widgets_Helpers::color( $this->get( 'badgeColor' ), '#ffffff' ),
			'--pfh-badge-size-set'   => PFH_Widgets_Helpers::unit( $this->get( 'badgeSize', 12 ) ),
			'--pfh-badge-radius'     => PFH_Widgets_Helpers::unit( $this->get( 'badgeRadius', 8 ) ),
			'--pfh-badge-pad-x-set'  => PFH_Widgets_Helpers::unit( $this->get( 'badgePadX', 13 ) ),
			'--pfh-badge-pad-y-set'  => PFH_Widgets_Helpers::unit( $this->get( 'badgePadY', 7 ) ),
			'--pfh-badge-offset-set' => PFH_Widgets_Helpers::unit( $this->get( 'badgeOffset', 16 ) ),

			'--pfh-cart-size-set'    => PFH_Widgets_Helpers::unit( $this->get( 'cartSize', 46 ) ),
			'--pfh-cart-icon'        => PFH_Widgets_Helpers::unit( $this->get( 'cartIconSize', 20 ) ),
			'--pfh-cart-bg'          => PFH_Widgets_Helpers::color( $this->get( 'cartBg' ), '#7caeb2' ),
			'--pfh-cart-color'       => PFH_Widgets_Helpers::color( $this->get( 'cartColor' ), '#ffffff' ),
			'--pfh-cart-hover'       => PFH_Widgets_Helpers::color( $this->get( 'cartHoverBg' ), '#3f4c3e' ),
			'--pfh-cart-radius'      => PFH_Widgets_Helpers::unit( $this->get( 'cartRadius', 5 ) ),
			'--pfh-cart-h'           => PFH_Widgets_Helpers::unit( $this->get( 'cartHeight', 36 ) ),
			'--pfh-cart-label'       => (string) $this->get( 'cartLabel', 'TOEVOEGEN' ),

			'--pfh-star'             => PFH_Widgets_Helpers::color( $this->get( 'starColor' ), '#5f6d46' ),
			'--pfh-star-empty'       => PFH_Widgets_Helpers::color( $this->get( 'starEmptyColor' ), 'rgba(95,109,70,.25)' ),
			'--pfh-star-size'        => PFH_Widgets_Helpers::unit( $this->get( 'starSize', 14 ) ),
			'--pfh-rev-size'         => PFH_Widgets_Helpers::unit( $this->get( 'reviewSize', 13 ) ),
			'--pfh-rev-color'        => PFH_Widgets_Helpers::color( $this->get( 'reviewColor' ), '#3d4a3a' ),

			'--pfh-t-size-set'       => PFH_Widgets_Helpers::unit( $this->get( 'titleSize', 20 ) ),
			'--pfh-t-weight'         => $this->get( 'titleWeight', '500' ),
			'--pfh-t-lh'             => $this->get( 'titleLineHeight', 1 ),
			'--pfh-t-color'          => PFH_Widgets_Helpers::color( $this->get( 'titleColor' ), '#22301c' ),
			'--pfh-t-lines'          => $lines > 0 ? $lines : 99,

			'--pfh-pr-size'          => PFH_Widgets_Helpers::unit( $this->get( 'priceSize', 14 ) ),
			'--pfh-pr-weight'        => $this->get( 'priceWeight', '500' ),
			'--pfh-pr-color'         => PFH_Widgets_Helpers::color( $this->get( 'priceColor' ), '#5f6d46' ),
			'--pfh-pr-old'           => PFH_Widgets_Helpers::color( $this->get( 'oldPriceColor' ), 'rgba(95,109,70,.5)' ),
			'--pfh-pr-old-size'      => $this->get( 'oldPriceSize' )
				? PFH_Widgets_Helpers::unit( $this->get( 'oldPriceSize' ) )
				: 'var(--pfh-pr-size)',
			'--pfh-pr-gap'           => PFH_Widgets_Helpers::unit( $this->get( 'priceGap', 12 ) ),
		];
	}

	private function price_html( $product, $which ) {
		if ( 'woo' === $this->get( 'priceMode', 'split' ) ) {
			return 'current' === $which ? $product->get_price_html() : '';
		}

		// The slider repeats the suffix on both prices; the archive's card
		// carries it on the current price only, which is how each is drawn.
		$suffix_here = 'regular' !== $which || $this->is_on( 'priceSuffixOld' );

		if ( ! function_exists( 'wc_get_price_to_display' ) || ! function_exists( 'wc_price' ) ) {
			return '';
		}

		$amount = 'regular' === $which
			? wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] )
			: wc_get_price_to_display( $product );

		if ( '' === $amount || null === $amount ) {
			return '';
		}

		$suffix = $suffix_here ? trim( (string) $this->get( 'priceSuffix', '' ) ) : '';
		$html   = wc_price( $amount );

		if ( $suffix ) {
			$html .= ' <span class="pfh-prod__suffix">' . esc_html( $suffix ) . '</span>';
		}

		return $html;
	}

	/**
	 * Should this card carry the sale badge?
	 *
	 * @param array $card Normalised card.
	 * @return bool
	 */
	private function shows_badge( $card ) {
		if ( ! $this->is_on( 'badgeEnable' ) ) {
			return false;
		}

		switch ( (string) $this->get( 'badgeMode', 'onsale' ) ) {
			case 'all':
				return true;

			case 'selected':
				return $card['id'] && in_array( (int) $card['id'], $this->id_list( $this->get( 'badgeProducts', '' ) ), true );

			default:
				return ! empty( $card['onSale'] );
		}
	}

	/**
	 * @param array $card Normalised card.
	 */
	private function render_card( $card ) {
		$href = ! empty( $card['link']['href'] ) ? $card['link']['href'] : '';

		echo '<li class="pfh-prod__item">';
		echo '<article class="pfh-prod__card">';

		// -- Media -------------------------------------------------------
		echo '<div class="pfh-prod__media">';

		if ( $this->shows_badge( $card ) && $this->get( 'badgeText' ) ) {
			echo '<span class="pfh-prod__badge">' . esc_html( PFH_Widgets_Helpers::dd( $this->get( 'badgeText' ) ) ) . '</span>';
		}

		if ( $href ) {
			echo '<a class="pfh-prod__media-link" href="' . esc_url( $href ) . '" tabindex="-1" aria-hidden="true">';
		}

		if ( ! empty( $card['image'] ) ) {
			printf(
				'<img class="pfh-prod__img" src="%s" alt="%s" loading="lazy" decoding="async" />',
				esc_url( $card['image'] ),
				esc_attr( $card['title'] )
			);
		}

		if ( $href ) {
			echo '</a>';
		}

		// Over the image, unless it is the full-width button — that belongs
		// under the price, where a label has somewhere to go.
		if ( 'block' !== (string) $this->get( 'cartPosition', 'br' ) ) {
			$this->render_cart_button( $card );
		}

		echo '</div>';

		// -- Body --------------------------------------------------------
		echo '<div class="pfh-prod__body">';

		$this->render_reviews( $card );

		if ( ! empty( $card['title'] ) ) {
			echo '<h3 class="pfh-prod__name">';

			if ( $href ) {
				echo '<a href="' . esc_url( $href ) . '">' . esc_html( $card['title'] ) . '</a>';
			} else {
				echo esc_html( $card['title'] );
			}

			echo '</h3>';
		}

		if ( ! empty( $card['price'] ) || ! empty( $card['oldPrice'] ) ) {
			echo '<div class="pfh-prod__prices">';

			if ( ! empty( $card['price'] ) ) {
				echo '<span class="pfh-prod__price">' . wp_kses_post( $card['price'] ) . '</span>';
			}

			if ( ! empty( $card['oldPrice'] ) ) {
				echo '<del class="pfh-prod__old">' . wp_kses_post( $card['oldPrice'] ) . '</del>';
			}

			echo '</div>';
		}

		if ( 'block' === (string) $this->get( 'cartPosition', 'br' ) ) {
			$this->render_cart_button( $card );
		}

		echo '</div>';
		echo '</article>';
		echo '</li>';
	}

	/**
	 * @param array $card Normalised card.
	 */
	/**
	 * Which figures this card's review row should show.
	 *
	 * @param array $card Normalised card.
	 * @return array{stars:float, text:string}|null Null hides the row.
	 */
	private function review_row( $card ) {
		$mode = (string) $this->get( 'reviewMode', 'static' );

		if ( 'shop' === $mode ) {
			return $this->shop_review_row();
		}

		if ( 'woocommerce' === $mode ) {
			$count = isset( $card['reviews'] ) ? (int) $card['reviews'] : 0;

			if ( $count > 0 && null !== $card['rating'] ) {
				return [
					'stars' => (float) $card['rating'],
					'text'  => sprintf(
						/* translators: %s: number of reviews. */
						_n( '%s review', '%s reviews', $count, 'pfh-widgets' ),
						number_format_i18n( $count )
					),
				];
			}

			$fallback = (string) $this->get( 'reviewFallback', 'shop' );

			if ( 'hide' === $fallback ) {
				return null;
			}

			if ( 'shop' === $fallback ) {
				return $this->shop_review_row();
			}
		}

		return [
			'stars' => (float) $this->get( 'reviewStars', 5 ),
			'text'  => (string) $this->get( 'reviewText', '' ),
		];
	}

	/**
	 * The shop's live WebwinkelKeur figures, shaped for a card.
	 *
	 * The typed stars and text are the fallback, so an unreachable feed leaves
	 * the card exactly as it was designed rather than showing nought.
	 *
	 * @return array{stars:float, text:string}
	 */
	private function shop_review_row() {
		$figures = PFH_Widgets_Reviews::figures(
			[
				'live'  => true,
				'scale' => 5,
				'score' => (string) $this->get( 'reviewStars', 5 ),
				'count' => 0,
			]
		);

		return [
			'stars' => (float) $figures['stars'],
			'text'  => PFH_Widgets_Reviews::tokens( (string) $this->get( 'reviewText', '' ), $figures ),
		];
	}

	private function render_reviews( $card ) {
		if ( ! $this->is_on( 'reviewEnable' ) ) {
			return;
		}

		$row = $this->review_row( $card );

		if ( null === $row ) {
			return;
		}

		$stars = $row['stars'];
		$text  = $row['text'];

		$link = PFH_Widgets_Helpers::link( $this->get( 'reviewLink' ) );

		// An unlinked trust row is decoration; send it to the review page.
		if ( '' === $link['href'] ) {
			$link = [
				'href'   => self::review_url(),
				'target' => '_blank',
				'rel'    => 'noopener nofollow',
				'aria'   => '',
			];
		}

		$tag = $link['href'] ? 'a' : 'div';

		echo '<' . $tag . ' class="pfh-prod__reviews"' . ( 'a' === $tag ? PFH_Widgets_Helpers::link_attrs( $link ) : '' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in link_attrs().

		// The shop grid is drawn with the count alone; the slider shows stars.
		if ( $this->is_on( 'reviewShowStars' ) ) {
			echo '<span class="pfh-prod__stars" role="img" aria-label="' . esc_attr( sprintf( /* translators: %s: rating out of five. */ __( '%s out of 5', 'pfh-widgets' ), number_format_i18n( $stars, 1 ) ) ) . '">';

			for ( $i = 1; $i <= 5; $i++ ) {
				$fill = max( 0, min( 1, $stars - $i + 1 ) );

				printf(
					'<span class="pfh-prod__star" style="--pfh-fill:%s%%">%s%s</span>',
					esc_attr( (string) round( $fill * 100 ) ),
					PFH_Widgets_Icons::get( 'star', 'pfh-prod__star-bg' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
					PFH_Widgets_Icons::get( 'star', 'pfh-prod__star-fg' )  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
				);
			}

			echo '</span>';
		}

		if ( $text ) {
			echo '<span class="pfh-prod__reviews-text">' . esc_html( $text ) . '</span>';
		}

		echo '</' . $tag . '>';
	}

	/**
	 * Add to cart button. Uses WooCommerce's own AJAX classes where possible so
	 * it triggers the added_to_cart event the header drawer already listens for.
	 *
	 * @param array $card Normalised card.
	 */
	private function render_cart_button( $card ) {
		if ( ! $this->is_on( 'cartEnable' ) ) {
			return;
		}

		$icon = PFH_Widgets_Helpers::image_url( $this->get( 'cartIcon' ), 'full' );
		$glyph = $icon
			? sprintf( '<img class="pfh-prod__cart-icon" src="%s" alt="" aria-hidden="true" decoding="async" />', esc_url( $icon ) )
			// basket-add is the icon the client supplied for this button; the
			// generic cart glyph is only the last resort.
			: PFH_Widgets_Icons::get( 'basket-add', 'pfh-prod__cart-icon' );

		if ( 'block' === (string) $this->get( 'cartPosition', 'br' ) ) {
			$label = trim( (string) $this->get( 'cartLabel', '' ) );

			if ( '' !== $label ) {
				$glyph .= sprintf(
					'<span class="pfh-prod__cart-label" data-added="%s">%s</span>',
					esc_attr( (string) $this->get( 'cartAddedLabel', 'TOEGEVOEGD' ) ),
					esc_html( $label )
				);
			}
		}

		$product = isset( $card['cart'] ) ? $card['cart'] : null;

		// No product object (manual cards, or WooCommerce absent): plain link.
		if ( ! $product || ! is_object( $product ) ) {
			$href = ! empty( $card['link']['href'] ) ? $card['link']['href'] : '';

			if ( ! $href ) {
				return;
			}

			printf(
				'<a class="pfh-prod__cart" href="%s" aria-label="%s">%s</a>',
				esc_url( $href ),
				esc_attr( sprintf( /* translators: %s: product name. */ __( 'View %s', 'pfh-widgets' ), $card['title'] ) ),
				$glyph // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above.
			);

			return;
		}

		$buyable = $product->is_purchasable() && $product->is_in_stock();

		/*
		 * Out of stock is a dead end, so the button says so and stops being a
		 * button: no basket icon to suggest it can be added, nothing to click
		 * and nothing for the keyboard to land on. A link to the product page
		 * here only invites a second disappointment.
		 */
		if ( ! $product->is_in_stock() ) {
			printf(
				'<span class="pfh-prod__cart is-unavailable" aria-disabled="true">%s</span>',
				'block' === (string) $this->get( 'cartPosition', 'br' )
					? '<span class="pfh-prod__cart-label">' . esc_html( (string) $this->get( 'cartSoldOutLabel', 'Niet beschikbaar' ) ) . '</span>'
					: '<span class="pfh-prod__sr">' . esc_html( (string) $this->get( 'cartSoldOutLabel', 'Niet beschikbaar' ) ) . '</span>'
			);

			return;
		}

		// Grouped products need their own quantity table; send those to the
		// product page rather than guessing.
		if ( ! $buyable || $product->is_type( 'grouped' ) ) {
			printf(
				'<a class="pfh-prod__cart" href="%s" aria-label="%s">%s</a>',
				esc_url( $product->get_permalink() ),
				esc_attr( sprintf( /* translators: %s: product name. */ __( 'View %s', 'pfh-widgets' ), $product->get_name() ) ),
				$glyph // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above.
			);

			return;
		}

		$variable = $product->is_type( 'variable' );

		/*
		 * The href stays the real WooCommerce destination so the button still
		 * works with JavaScript off — the script cancels the navigation and
		 * either adds straight to the cart or opens the variant chooser.
		 */
		printf(
			'<a href="%s" class="pfh-prod__cart%s" data-pfh-add="%s" data-pfh-type="%s" data-product_id="%s" data-quantity="1" rel="nofollow" aria-label="%s">%s</a>',
			esc_url( $variable ? $product->get_permalink() : $product->add_to_cart_url() ),
			$variable ? ' is-variable' : '',
			esc_attr( $product->get_id() ),
			esc_attr( $variable ? 'variable' : 'simple' ),
			esc_attr( $product->get_id() ),
			esc_attr(
				$variable
					? sprintf( /* translators: %s: product name. */ __( 'Choose options for %s', 'pfh-widgets' ), $product->get_name() )
					: sprintf( /* translators: %s: product name. */ __( 'Add %s to your cart', 'pfh-widgets' ), $product->get_name() )
			),
			$glyph // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above.
		);
	}

	/**
	 * The product these are related to.
	 *
	 * @return int
	 */
	private function related_to() {
		if ( is_singular( 'product' ) ) {
			return (int) get_queried_object_id();
		}

		$preview = (int) $this->get( 'previewId', 0 );

		if ( $preview ) {
			return $preview;
		}

		global $post;

		if ( $post && 'product' === get_post_type( $post ) ) {
			return (int) $post->ID;
		}

		$recent = get_posts( [ 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ] );

		return $recent ? (int) $recent[0] : 0;
	}

	/**
	 * For the cart page: what the shop set as cross-sells for the products
	 * in the cart, then its best sellers to fill the row — never something
	 * already in the cart. With nothing in the cart (or in the builder,
	 * where there is no cart), simply the best sellers.
	 *
	 * @param int $limit How many.
	 * @return int[] Product ids, in order; [0] when there are none.
	 */
	private function goes_with_cart( $limit ) {
		$in_cart = [];
		$ids     = [];

		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				$in_cart[] = (int) $item['product_id'];

				if ( ! empty( $item['variation_id'] ) ) {
					$in_cart[] = (int) $item['variation_id'];
				}
			}

			$ids = array_values( array_diff( array_map( 'intval', WC()->cart->get_cross_sells() ), $in_cart ) );
		}

		if ( count( $ids ) < $limit ) {
			$best = get_posts(
				[
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => $limit,
					'post__not_in'   => array_merge( $in_cart, $ids ),
					'meta_key'       => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'orderby'        => 'meta_value_num',
					'order'          => 'DESC',
					'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						[
							'taxonomy' => 'product_visibility',
							'field'    => 'name',
							'terms'    => [ 'exclude-from-catalog', 'outofstock' ],
							'operator' => 'NOT IN',
						],
					],
				]
			);

			$ids = array_merge( $ids, array_map( 'intval', $best ) );
		}

		$ids = array_slice( array_values( array_unique( $ids ) ), 0, $limit );

		return $ids ? $ids : [ 0 ];
	}

	private function source_controls() {
		$this->controls['source'] = [
			'tab'     => 'content',
			'group'   => 'source',
			'label'   => esc_html__( 'Show', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'onsale'     => esc_html__( 'Products on sale', 'pfh-widgets' ),
				'featured'   => esc_html__( 'Featured products', 'pfh-widgets' ),
				'category'   => esc_html__( 'A product category', 'pfh-widgets' ),
				'best'       => esc_html__( 'Best selling', 'pfh-widgets' ),
				'recent'     => esc_html__( 'Newest products', 'pfh-widgets' ),
				'viewed'     => esc_html__( 'Recently viewed by this visitor', 'pfh-widgets' ),
				'cart'       => esc_html__( 'Goes with what is in the cart', 'pfh-widgets' ),
				'ids'        => esc_html__( 'Specific products', 'pfh-widgets' ),
				'manual'     => esc_html__( 'Manual cards (no WooCommerce)', 'pfh-widgets' ),
			],
			'default' => 'onsale',
		];

		$this->controls['category'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Category', 'pfh-widgets' ),
			'type'        => 'select',
			'searchable'  => true,
			'options'     => PFH_Widgets_Helpers::product_cat_options(),
			'placeholder' => esc_html__( 'Select a category', 'pfh-widgets' ),
			'required'    => [ 'source', '=', 'category' ],
		];

		$this->controls['productIds'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Product IDs', 'pfh-widgets' ),
			'type'        => 'text',
			'placeholder' => '128, 94, 71',
			'description' => esc_html__( 'Comma separated. The slider keeps this order.', 'pfh-widgets' ),
			'required'    => [ 'source', '=', 'ids' ],
		];

		$this->controls['manualCards'] = [
			'tab'           => 'content',
			'group'         => 'source',
			'label'         => esc_html__( 'Cards', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'required'      => [ 'source', '=', 'manual' ],
			'default'       => $this->default_manual_cards(),
			'fields'        => [
				'title'    => [
					'label' => esc_html__( 'Title', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'image'    => [
					'label' => esc_html__( 'Image', 'pfh-widgets' ),
					'type'  => 'image',
				],
				'price'    => [
					'label'  => esc_html__( 'Price', 'pfh-widgets' ),
					'type'   => 'text',
					'inline' => true,
				],
				'oldPrice' => [
					'label'  => esc_html__( 'Old price', 'pfh-widgets' ),
					'type'   => 'text',
					'inline' => true,
				],
				'link'     => [
					'label' => esc_html__( 'Link', 'pfh-widgets' ),
					'type'  => 'link',
				],
				'onSale'   => [
					'label'   => esc_html__( 'Show the sale badge', 'pfh-widgets' ),
					'type'    => 'checkbox',
					'default' => true,
				],
			],
		];

		$this->controls['limit'] = [
			'tab'      => 'content',
			'group'    => 'source',
			'label'    => esc_html__( 'Number of products', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 1,
			'max'      => 24,
			'inline'   => true,
			'default'  => 8,
			'required' => [ 'source', '!=', 'manual' ],
		];

		$this->controls['orderby'] = [
			'tab'      => 'content',
			'group'    => 'source',
			'label'    => esc_html__( 'Order by', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => [
				'menu_order' => esc_html__( 'Menu order', 'pfh-widgets' ),
				'date'       => esc_html__( 'Newest', 'pfh-widgets' ),
				'title'      => esc_html__( 'Title', 'pfh-widgets' ),
				'popularity' => esc_html__( 'Best selling', 'pfh-widgets' ),
				'price'      => esc_html__( 'Price, low to high', 'pfh-widgets' ),
				'price-desc' => esc_html__( 'Price, high to low', 'pfh-widgets' ),
				'rand'       => esc_html__( 'Random', 'pfh-widgets' ),
			],
			'default'  => 'menu_order',
			'required' => [ 'source', '!=', [ 'manual', 'ids', 'cart' ] ],
		];

		$this->controls['hideOutOfStock'] = [
			'tab'      => 'content',
			'group'    => 'source',
			'label'    => esc_html__( 'Hide out of stock', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => false,
			'required' => [ 'source', '!=', 'manual' ],
		];
	}

	private function badge_controls() {
		$this->controls['badgeEnable'] = [
			'tab'     => 'content',
			'group'   => 'badge',
			'label'   => esc_html__( 'Show the badge', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['badgeMode'] = [
			'tab'         => 'content',
			'group'       => 'badge',
			'label'       => esc_html__( 'Show it on', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'onsale'   => esc_html__( 'Products that are actually on sale', 'pfh-widgets' ),
				'selected' => esc_html__( 'Only the products I pick', 'pfh-widgets' ),
				'all'      => esc_html__( 'Every card', 'pfh-widgets' ),
			],
			'default'     => 'onsale',
			'required'    => [ 'badgeEnable', '=', true ],
			'description' => esc_html__( 'WooCommerce decides what counts as on sale; picking products overrides that.', 'pfh-widgets' ),
		];

		$this->controls['badgeProducts'] = [
			'tab'         => 'content',
			'group'       => 'badge',
			'label'       => esc_html__( 'Product IDs to badge', 'pfh-widgets' ),
			'type'        => 'text',
			'placeholder' => '128, 94',
			'description' => esc_html__( 'Comma separated product IDs.', 'pfh-widgets' ),
			'required'    => [ [ 'badgeEnable', '=', true ], [ 'badgeMode', '=', 'selected' ] ],
		];

		$this->controls['badgeText'] = [
			'tab'      => 'content',
			'group'    => 'badge',
			'label'    => esc_html__( 'Label', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'SALES',
			'required' => [ 'badgeEnable', '=', true ],
		];

		$this->controls['badgeBg'] = [
			'tab'      => 'content',
			'group'    => 'badge',
			'label'    => esc_html__( 'Background', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#7d9569' ],
			'required' => [ 'badgeEnable', '=', true ],
		];

		$this->controls['badgeColor'] = [
			'tab'      => 'content',
			'group'    => 'badge',
			'label'    => esc_html__( 'Text colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#ffffff' ],
			'required' => [ 'badgeEnable', '=', true ],
		];

		$this->controls['badgeSize'] = [
			'tab'      => 'content',
			'group'    => 'badge',
			'label'    => esc_html__( 'Font size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 8,
			'max'      => 24,
			'inline'   => true,
			'default'  => 12,
			'required' => [ 'badgeEnable', '=', true ],
		];

		$this->controls['badgeRadius'] = [
			'tab'      => 'content',
			'group'    => 'badge',
			'label'    => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 40,
			'inline'   => true,
			'default'  => 8,
			'required' => [ 'badgeEnable', '=', true ],
		];

		$this->controls['badgePadX'] = [
			'tab'      => 'content',
			'group'    => 'badge',
			'label'    => esc_html__( 'Side padding (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 40,
			'inline'   => true,
			'default'  => 13,
			'required' => [ 'badgeEnable', '=', true ],
		];

		$this->controls['badgePadY'] = [
			'tab'      => 'content',
			'group'    => 'badge',
			'label'    => esc_html__( 'Vertical padding (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 30,
			'inline'   => true,
			'default'  => 7,
			'required' => [ 'badgeEnable', '=', true ],
		];

		$this->controls['badgeOffset'] = [
			'tab'      => 'content',
			'group'    => 'badge',
			'label'    => esc_html__( 'Distance from the corner (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 60,
			'inline'   => true,
			'default'  => 16,
			'required' => [ 'badgeEnable', '=', true ],
		];
	}

	/**
	 * The cart controls.
	 *
	 * Defaults here are the slider's, because the slider is the widget this
	 * card was drawn for: a round button in the corner of the image that
	 * appears on hover. The archive's card is a different design — a
	 * full-width TOEVOEGEN bar under the price — and it sets that for itself
	 * in card_defaults(). Changing these to suit the archive, as happened
	 * once, silently restyles every product slider on the site.
	 */
	private function cart_controls() {
		$this->controls['cartEnable'] = [
			'tab'         => 'content',
			'group'       => 'cart',
			'label'       => esc_html__( 'Show the add to cart button', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Revealed on hover, and always visible on touch devices.', 'pfh-widgets' ),
		];

		$this->controls['cartIcon'] = [
			'tab'         => 'content',
			'group'       => 'cart',
			'label'       => esc_html__( 'Icon', 'pfh-widgets' ),
			'type'        => 'image',
			'required'    => [ 'cartEnable', '=', true ],
			'description' => esc_html__( 'Leave empty to use the built-in cart icon.', 'pfh-widgets' ),
		];

		$this->controls['cartPosition'] = [
			'tab'      => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Position', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => [
				'br'    => esc_html__( 'Bottom right, over the image', 'pfh-widgets' ),
				'tr'    => esc_html__( 'Top right, over the image', 'pfh-widgets' ),
				'bl'    => esc_html__( 'Bottom left, over the image', 'pfh-widgets' ),
				'block' => esc_html__( 'Full-width button under the price', 'pfh-widgets' ),
			],
			'default'  => 'br',
			'required' => [ 'cartEnable', '=', true ],
		];

		$this->controls['cartLabel'] = [
			'tab'         => 'content',
			'group'       => 'cart',
			'label'       => esc_html__( 'Button label', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => 'TOEVOEGEN',
			'description' => esc_html__( 'Only shown by the full-width button; the corner icon has no room for a label.', 'pfh-widgets' ),
			'required'    => [ [ 'cartEnable', '=', true ], [ 'cartPosition', '=', 'block' ] ],
		];

		$this->controls['cartSoldOutLabel'] = [
			'tab'      => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Label when out of stock', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => 'Niet beschikbaar',
			'required' => [ 'cartEnable', '=', true ],
		];

		$this->controls['cartAddedLabel'] = [
			'tab'         => 'content',
			'group'       => 'cart',
			'label'       => esc_html__( 'Label after adding', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => 'TOEGEVOEGD',
			'required'    => [ [ 'cartEnable', '=', true ], [ 'cartPosition', '=', 'block' ] ],
			'description' => esc_html__( 'Shown for a moment on the full-width button so the shopper sees the add land.', 'pfh-widgets' ),
		];

		$this->controls['cartHeight'] = [
			'tab'      => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Button height (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 24,
			'max'      => 72,
			'inline'   => true,
			'default'  => 36,
			'required' => [ [ 'cartEnable', '=', true ], [ 'cartPosition', '=', 'block' ] ],
		];

		$this->controls['cartReveal'] = [
			'tab'      => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Reveal', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => [
				'up'    => esc_html__( 'Fade + rise', 'pfh-widgets' ),
				'scale' => esc_html__( 'Fade + pop', 'pfh-widgets' ),
				'fade'  => esc_html__( 'Fade', 'pfh-widgets' ),
				'none'  => esc_html__( 'Always visible', 'pfh-widgets' ),
			],
			'default'  => 'up',
			'required' => [ 'cartEnable', '=', true ],
		];

		$this->controls['cartSize'] = [
			'tab'      => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Button size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 28,
			'max'      => 80,
			'inline'   => true,
			'default'  => 46,
			'required' => [ 'cartEnable', '=', true ],
		];

		$this->controls['cartIconSize'] = [
			'tab'      => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Icon size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 10,
			'max'      => 40,
			'inline'   => true,
			'default'  => 20,
			'required' => [ 'cartEnable', '=', true ],
		];

		$this->controls['cartBg'] = [
			'tab'      => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Background', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#51604f' ],
			'required' => [ 'cartEnable', '=', true ],
		];

		$this->controls['cartColor'] = [
			'tab'      => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Icon colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#ffffff' ],
			'required' => [ 'cartEnable', '=', true ],
		];

		$this->controls['cartHoverBg'] = [
			'tab'      => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Background (hover)', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#3f4c3e' ],
			'required' => [ 'cartEnable', '=', true ],
		];

		$this->controls['cartRadius'] = [
			'tab'      => 'content',
			'group'    => 'cart',
			'label'    => esc_html__( 'Corner radius (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 60,
			'inline'   => true,
			'default'  => 999,
			'required' => [ 'cartEnable', '=', true ],
		];
	}

	private function review_controls() {
		$this->controls['reviewEnable'] = [
			'tab'     => 'content',
			'group'   => 'reviews',
			'label'   => esc_html__( 'Show the review row', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['reviewMode'] = [
			'tab'         => 'content',
			'group'       => 'reviews',
			'label'       => esc_html__( 'Source', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'static'      => esc_html__( 'Same text on every card', 'pfh-widgets' ),
				'shop'        => esc_html__( 'The shop’s live WebwinkelKeur rating', 'pfh-widgets' ),
				'woocommerce' => esc_html__( 'Each product’s own rating', 'pfh-widgets' ),
			],
			'default'     => 'static',
			'required'    => [ 'reviewEnable', '=', true ],
			'description' => esc_html__( 'This store collects reviews in WebwinkelKeur, which rates the shop rather than individual products — so the shop rating is the real figure available for every card. Per-product needs WooCommerce reviews on the products themselves.', 'pfh-widgets' ),
		];

		$this->controls['reviewFallback'] = [
			'tab'         => 'content',
			'group'       => 'reviews',
			'label'       => esc_html__( 'When a product has no reviews', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'shop'   => esc_html__( 'Show the shop rating instead', 'pfh-widgets' ),
				'hide'   => esc_html__( 'Hide the row on that card', 'pfh-widgets' ),
				'static' => esc_html__( 'Show the text below', 'pfh-widgets' ),
			],
			'default'     => 'shop',
			'required'    => [ [ 'reviewEnable', '=', true ], [ 'reviewMode', '=', 'woocommerce' ] ],
			'description' => esc_html__( 'A brand new product showing nought stars reads worse than no row at all.', 'pfh-widgets' ),
		];

		$this->controls['reviewShowStars'] = [
			'tab'      => 'content',
			'group'    => 'reviews',
			'label'    => esc_html__( 'Show the star row', 'pfh-widgets' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'reviewEnable', '=', true ],
		];

		$this->controls['reviewStars'] = [
			'tab'      => 'content',
			'group'    => 'reviews',
			'label'    => esc_html__( 'Stars', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 0,
			'max'      => 5,
			'step'     => 0.5,
			'inline'   => true,
			'default'  => 5,
			'required' => [ 'reviewEnable', '=', true ],
		];

		$this->controls['reviewText'] = [
			'tab'      => 'content',
			'group'    => 'reviews',
			'label'    => esc_html__( 'Review text', 'pfh-widgets' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => '124 reviews',
			'required' => [ 'reviewEnable', '=', true ],
		];

		$this->controls['reviewLink'] = [
			'tab'         => 'content',
			'group'       => 'reviews',
			'label'       => esc_html__( 'Review link', 'pfh-widgets' ),
			'type'        => 'link',
			'default'     => [
				'type'   => 'external',
				'url'    => self::review_url(),
				'newTab' => true,
			],
			'required'    => [ 'reviewEnable', '=', true ],
			'description' => esc_html__( 'Defaults to the WebwinkelKeur page for this shop.', 'pfh-widgets' ),
		];

		$this->controls['starColor'] = [
			'tab'      => 'content',
			'group'    => 'reviews',
			'label'    => esc_html__( 'Star colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#5f6d46' ],
			'required' => [ 'reviewEnable', '=', true ],
		];

		$this->controls['starEmptyColor'] = [
			'tab'      => 'content',
			'group'    => 'reviews',
			'label'    => esc_html__( 'Empty star colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'rgb' => 'rgba(95, 109, 70, 0.25)' ],
			'required' => [ 'reviewEnable', '=', true ],
		];

		$this->controls['starSize'] = [
			'tab'      => 'content',
			'group'    => 'reviews',
			'label'    => esc_html__( 'Star size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 8,
			'max'      => 30,
			'inline'   => true,
			'default'  => 14,
			'required' => [ 'reviewEnable', '=', true ],
		];

		$this->controls['reviewSize'] = [
			'tab'      => 'content',
			'group'    => 'reviews',
			'label'    => esc_html__( 'Review text size (px)', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 8,
			'max'      => 24,
			'inline'   => true,
			'default'  => 13,
			'required' => [ 'reviewEnable', '=', true ],
		];

		$this->controls['reviewColor'] = [
			'tab'      => 'content',
			'group'    => 'reviews',
			'label'    => esc_html__( 'Review text colour', 'pfh-widgets' ),
			'type'     => 'color',
			'default'  => [ 'hex' => '#3d4a3a' ],
			'required' => [ 'reviewEnable', '=', true ],
		];
	}

	private function type_controls() {
		$this->controls['titleSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Title size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 10,
			'max'     => 40,
			'inline'  => true,
			'default' => 20,
		];

		$this->controls['titleWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Title weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '500',
		];

		$this->controls['titleLineHeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Title line height', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0.9,
			'max'     => 2,
			'step'    => 0.01,
			'inline'  => true,
			'default' => 1,
		];

		$this->controls['titleColor'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Title colour', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#22301c' ],
			'description' => esc_html__( 'Figma token: Olive Green / green-900.', 'pfh-widgets' ),
		];

		$this->controls['titleLines'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Title lines before trimming', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 5,
			'inline'      => true,
			'default'     => 2,
			'description' => esc_html__( 'Keeps every card the same height. 0 shows the full title.', 'pfh-widgets' ),
		];

		$this->controls['priceSize'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Price size (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 9,
			'max'     => 30,
			'inline'  => true,
			'default' => 14,
		];

		$this->controls['priceWeight'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Price weight', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->weight_options(),
			'default' => '500',
		];

		$this->controls['priceColor'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Price colour', 'pfh-widgets' ),
			'type'        => 'color',
			'default'     => [ 'hex' => '#5f6d46' ],
			'description' => esc_html__( 'Figma token: Olive Green / green-700.', 'pfh-widgets' ),
		];

		$this->controls['oldPriceSize'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Old price size (px)', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 8,
			'max'         => 24,
			'inline'      => true,
			'description' => esc_html__( 'Leave empty to match the current price.', 'pfh-widgets' ),
		];

		$this->controls['oldPriceColor'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Old price colour', 'pfh-widgets' ),
			'type'    => 'color',
			'default' => [ 'rgb' => 'rgba(95, 109, 70, 0.5)' ],
		];

		$this->controls['priceSuffixOld'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Suffix on the old price too', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Off puts it on the current price only, which is how the shop grid is drawn.', 'pfh-widgets' ),
		];

		$this->controls['priceGap'] = [
			'tab'     => 'content',
			'group'   => 'type',
			'label'   => esc_html__( 'Gap between prices (px)', 'pfh-widgets' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 40,
			'inline'  => true,
			'default' => 12,
		];

		$this->controls['priceMode'] = [
			'tab'      => 'content',
			'group'    => 'type',
			'label'    => esc_html__( 'Price format', 'pfh-widgets' ),
			'type'     => 'select',
			'inline'   => true,
			'options'  => [
				'split' => esc_html__( 'Sale price then old price', 'pfh-widgets' ),
				'woo'   => esc_html__( 'WooCommerce default', 'pfh-widgets' ),
			],
			'default'  => 'split',
			'required' => [ 'source', '!=', 'manual' ],
		];

		$this->controls['priceSuffix'] = [
			'tab'         => 'content',
			'group'       => 'type',
			'label'       => esc_html__( 'Price suffix', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'default'     => 'incl. VAT',
			'required'    => [ [ 'source', '!=', 'manual' ], [ 'priceMode', '=', 'split' ] ],
			'description' => esc_html__( 'Appended to both prices.', 'pfh-widgets' ),
		];
	}

	private function weight_options() {
		return [
			'300' => esc_html__( 'Light (300)', 'pfh-widgets' ),
			'400' => esc_html__( 'Regular (400)', 'pfh-widgets' ),
			'500' => esc_html__( 'Medium (500)', 'pfh-widgets' ),
			'600' => esc_html__( 'Semibold (600)', 'pfh-widgets' ),
			'700' => esc_html__( 'Bold (700)', 'pfh-widgets' ),
		];
	}
}
