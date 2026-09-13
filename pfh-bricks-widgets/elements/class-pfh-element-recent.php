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
 * With nothing viewed it renders nothing at all — no heading, no empty rail.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

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

		$this->controls['deferred'] = [
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Load after the page', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Leave this on. A visitor\'s history lives in their own cookie, so a cached page that had this baked in would show one shopper what the last one had been looking at. Turn it off only on a site with no page caching at all.', 'pfh-widgets' ),
		];
	}

	public function render() {
		// Whatever is stored, this element has one source.
		$this->settings['source'] = 'viewed';

		// Read directly: the parent's is_on() is private to it, and widening
		// that for one subclass is not worth the reach it would give away.
		$deferred = ! array_key_exists( 'deferred', (array) $this->settings )
			|| ! empty( $this->settings['deferred'] );

		if ( ! $deferred || PFH_Widgets_Helpers::is_builder_context() ) {
			parent::render();

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
			'<div class="pfh-recent" data-pfh-recent="%s" hidden></div>',
			esc_attr( $uid )
		);
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

	public static function init() {
		add_action( 'wp_ajax_pfh_recent', [ __CLASS__, 'ajax' ] );
		add_action( 'wp_ajax_nopriv_pfh_recent', [ __CLASS__, 'ajax' ] );
	}

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
