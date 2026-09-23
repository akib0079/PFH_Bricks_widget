<?php
/**
 * Bricks element: Products For Home recently viewed slider.
 *
 * The product slider, showing only what this visitor has already looked at.
 * It extends the slider rather than copying it, so the card, the drag
 * behaviour and every style control stay identical by construction — the two
 * cannot drift apart the way a duplicated element would.
 *
 * Two things are its own:
 *
 *   The source is pinned. "Recently viewed" is not one option among several
 *   here; it is what this element is.
 *
 *   It loads after the page by default. The history lives in a per-visitor
 *   cookie, so a page cache that stored the rendered markup would serve one
 *   shopper's browsing history to the next. Fetching it afterwards keeps the
 *   page itself cacheable and keeps each visitor's history their own.
 *
 * With nothing viewed it shows the shop's best sellers under their own
 * heading instead, so the section is never an empty gap on a first visit —
 * or, switched to "nothing", it renders nothing at all.
 *
 * The history itself is recorded by PFH_Widgets_Recent, not by WooCommerce:
 * WooCommerce only writes its cookie while its own sidebar widget is active,
 * which on a Bricks site it never is — so this section had nothing to show.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

/*
 * It extends the product slider, so the parent has to be there before this
 * class is declared. Bricks loads an element file on its own, and a parent
 * merely assumed to be loaded is a fatal on a live page rather than a notice.
 */
if ( ! class_exists( 'PFH_Element_Products' ) && defined( 'PFH_WIDGETS_DIR' ) ) {
	require_once PFH_WIDGETS_DIR . 'elements/class-pfh-element-products.php';
}

class PFH_Element_Recent extends PFH_Element_Products {

	/** Where the deferred render finds its settings. */
	const CONFIG = 'pfh_recent_cfg_';

	public $name = 'pfh-recent';
	public $icon = 'ti-back-left';

	public function get_label() {
		return esc_html__( 'PFH Recently Viewed', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'recently', 'viewed', 'history', 'slider', 'products', 'carousel', 'pfh' ];
	}

	public function enqueue_scripts() {
		parent::enqueue_scripts();
		PFH_Widgets_Assets::recent();
	}

	public function set_controls() {
		parent::set_controls();

		// The source is what this element is, so it is not offered.
		unset( $this->controls['source'], $this->controls['manualCards'], $this->controls['productIds'], $this->controls['category'] );

		// The slider's heading is one field, with <em> marking the accent.
		if ( isset( $this->controls['heading'] ) ) {
			$this->controls['heading']['default'] = 'Recently <em>viewed</em>';
		}

		if ( isset( $this->controls['limit'] ) ) {
			$this->controls['limit']['default']     = 12;
			$this->controls['limit']['description'] = esc_html__( 'WooCommerce remembers the last 15 products a visitor opened.', 'pfh-widgets' );
		}

		$this->controls['fallback'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'With nothing viewed yet', 'pfh-widgets' ),
			'type'        => 'select',
			'inline'      => true,
			'default'     => 'popular',
			'options'     => [
				'popular' => esc_html__( 'Show the best sellers', 'pfh-widgets' ),
				'none'    => esc_html__( 'Show nothing', 'pfh-widgets' ),
			],
			'description' => esc_html__( 'A first-time visitor has no history. The best sellers fill the space under their own heading instead of leaving a gap.', 'pfh-widgets' ),
		];

		$this->controls['fallbackHeading'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Heading for the best sellers', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'Most <em>popular</em>',
			'description' => esc_html__( 'The recently viewed heading would be untrue over products nobody viewed, so they get their own.', 'pfh-widgets' ),
		];

		$this->controls['deferred'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Load after the page', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Leave this on. A visitor\'s history lives in their own cookie, so a cached page that had this baked in would show one shopper what the last one had been looking at. Turn it off only on a site with no page caching at all.', 'pfh-widgets' ),
		];
	}

	/**
	 * A product to leave out: the one the visitor is looking at right now.
	 *
	 * @var int
	 */
	private $exclude = 0;

	public function render() {
		// Whatever is stored, this element has one source.
		$this->settings['source'] = 'viewed';

		// On a product page, "recently viewed" should not open with the page
		// the shopper is already on.
		if ( ! $this->exclude && is_singular( 'product' ) ) {
			$this->exclude = (int) get_queried_object_id();
		}

		// Read directly: the parent's is_on() is private to it, and widening
		// that for one subclass is not worth the reach it would give away.
		$deferred = ! array_key_exists( 'deferred', (array) $this->settings )
			|| ! empty( $this->settings['deferred'] );

		if ( ! $deferred || PFH_Widgets_Helpers::is_builder_context() ) {
			echo $this->slider(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered by the slider, which escapes its own output.

			return;
		}

		/*
		 * Deferred: nothing of the visitor's is in the markup, so the page
		 * stays cacheable. The placeholder carries no content and is hidden,
		 * which is also the right resting state — a visitor with no history
		 * never sees anything appear.
		 */
		$uid = $this->uid();

		set_transient( self::CONFIG . $uid, $this->settings, DAY_IN_SECONDS );

		printf(
			'<div class="pfh-recent" data-pfh-recent="%s" data-pfh-recent-exclude="%d" hidden></div>',
			esc_attr( $uid ),
			(int) $this->exclude
		);
	}

	/**
	 * The slider: the visitor's own history, else the best sellers.
	 *
	 * @return string Markup, or '' when there is nothing worth showing.
	 */
	private function slider() {
		$html = $this->draw( 'viewed', [] );

		if ( false !== strpos( $html, 'pfh-prod__card' ) ) {
			return $html;
		}

		$fallback = isset( $this->settings['fallback'] ) ? (string) $this->settings['fallback'] : 'popular';

		if ( 'none' === $fallback ) {
			return PFH_Widgets_Helpers::is_builder_context() ? $html : '';
		}

		$heading = array_key_exists( 'fallbackHeading', (array) $this->settings )
			? (string) $this->settings['fallbackHeading']
			: 'Most <em>popular</em>';

		return $this->draw( 'best', [ 'heading' => $heading ] );
	}

	/**
	 * Render the parent slider from one source.
	 *
	 * On a fresh element every time: the slider remembers the products it
	 * found and the attributes it set, so a second draw on the same one would
	 * reuse the first draw's empty list and double its classes.
	 *
	 * @param string $source   'viewed' or 'best'.
	 * @param array  $override Settings to change for this draw.
	 * @return string
	 */
	private function draw( $source, array $override ) {
		$id       = isset( $this->element['id'] ) ? (string) $this->element['id'] : 'pfh-recent';
		$settings = array_merge( (array) $this->settings, $override, [ 'source' => $source ] );

		$fresh           = new self( [ 'id' => $id ] );
		$fresh->name     = $this->name;
		$fresh->settings = $settings;
		$fresh->element  = [ 'id' => $id, 'name' => $this->name, 'settings' => $settings ];
		$fresh->controls = $this->controls;

		$exclude = (int) $this->exclude;
		$skip    = static function ( $args ) use ( $exclude ) {
			if ( ! $exclude ) {
				return $args;
			}

			/*
			 * WordPress ignores post__not_in whenever post__in is set, and the
			 * history is a post__in list — so the product is taken out of the
			 * list itself. [ 0 ] keeps an emptied list meaning "nothing"
			 * rather than "everything".
			 */
			if ( ! empty( $args['post__in'] ) ) {
				$kept             = array_values( array_diff( array_map( 'intval', (array) $args['post__in'] ), [ $exclude ] ) );
				$args['post__in'] = $kept ? $kept : [ 0 ];

				return $args;
			}

			$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : [], [ $exclude ] );

			return $args;
		};

		add_filter( 'pfh_widgets_products_args', $skip );

		$html = $fresh->plain();

		remove_filter( 'pfh_widgets_products_args', $skip );

		return $html;
	}

	/**
	 * The parent slider, as it would render on its own.
	 *
	 * @return string
	 */
	private function plain() {
		ob_start();
		parent::render();

		return trim( (string) ob_get_clean() );
	}

	/**
	 * A stable id for this element's stored settings.
	 *
	 * @return string
	 */
	private function uid() {
		$id = isset( $this->id ) ? (string) $this->id : '';

		return $id ? sanitize_key( $id ) : substr( md5( wp_json_encode( $this->settings ) ), 0, 12 );
	}

	/* ---------------------------------------------------------------------
	 * The deferred render
	 * ------------------------------------------------------------------ */

	/**
	 * Render one deferred slider for the visitor asking for it.
	 */
	public static function ajax() {
		check_ajax_referer( PFH_Widgets_Ajax::NONCE, 'nonce' );

		$id = isset( $_POST['element'] ) ? sanitize_key( wp_unslash( $_POST['element'] ) ) : '';

		if ( ! $id ) {
			wp_send_json_error( [ 'message' => __( 'That request did not say which slider it was for.', 'pfh-widgets' ) ], 400 );
		}

		$settings = get_transient( self::CONFIG . $id );

		if ( ! is_array( $settings ) ) {
			wp_send_json_error( [ 'message' => __( 'That slider is no longer on the page.', 'pfh-widgets' ) ], 404 );
		}

		$element           = new self( [ 'id' => $id ] );
		$element->name     = 'pfh-recent';
		$element->settings = array_merge( $settings, [ 'source' => 'viewed', 'deferred' => false ] );

		$element->element = [
			'id'       => $id,
			'name'     => 'pfh-recent',
			'settings' => $element->settings,
		];

		$element->set_control_groups();
		$element->set_controls();

		// The product page the request came from, so it is not listed on itself.
		$exclude = isset( $_POST['exclude'] ) ? absint( wp_unslash( $_POST['exclude'] ) ) : 0;

		$element->exclude = $exclude && 'product' === get_post_type( $exclude ) ? $exclude : 0;

		// deferred is off in the settings above, so this renders the slider.
		ob_start();
		$element->render();
		$html = trim( (string) ob_get_clean() );

		/*
		 * Only a real slider goes back. Anything else — an editor placeholder,
		 * a notice from another plugin that hooked the render — would be
		 * shown to a shopper who simply has no history yet, and this section
		 * is supposed to be invisible to them.
		 */
		if ( false === strpos( $html, 'pfh-prod__card' ) ) {
			wp_send_json_success( [ 'html' => '' ] );
		}

		wp_send_json_success( [ 'html' => $html ] );
	}
}
