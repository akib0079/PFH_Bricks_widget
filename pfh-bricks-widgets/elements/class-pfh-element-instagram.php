<?php
/**
 * Bricks element: Products For Home Instagram strip.
 *
 * A heading with one word in the italic serif, the account as a pill, two
 * round arrows, and under all of it a row of square photographs that drifts
 * past the edges of the page and never ends.
 *
 * The photographs are the shop's Instagram feed when a token is connected
 * under Products For Home → Instagram. When it is not — or Instagram is down,
 * or the token has lapsed — the pictures set here are drawn instead, so the
 * section is never empty and never broken. With neither, it is left off the
 * page rather than drawn hollow.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Instagram extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-instagram';
	public $icon         = 'ti-instagram';
	public $css_selector = '.pfh-ig';

	public function get_label() {
		return esc_html__( 'PFH Instagram Strip', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'instagram', 'social', 'feed', 'marquee', 'strip', 'gallery', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::instagram();
	}

	public function set_control_groups() {
		foreach ( [
			'content' => esc_html__( 'Content', 'pfh-widgets' ),
			'feed'    => esc_html__( 'Instagram feed', 'pfh-widgets' ),
			'motion'  => esc_html__( 'Motion', 'pfh-widgets' ),
			'style'   => esc_html__( 'Style', 'pfh-widgets' ),
			'layout'  => esc_html__( 'Layout', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		/* ---- content ---- */

		$this->controls['title'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Title', 'pfh-widgets' ),
			'type'        => 'text',
			'default'     => 'Ontdekken <em>Instagram</em>',
			'description' => esc_html__( 'A word wrapped in <em> is set in the italic serif, as drawn.', 'pfh-widgets' ),
		];

		$this->controls['handle'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Account', 'pfh-widgets' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 'products.for.home',
		];

		$this->controls['profileUrl'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Account link', 'pfh-widgets' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 'https://www.instagram.com/products.for.home/',
			'default'     => '',
			'description' => esc_html__( 'Left empty the account reads as a label rather than a link.', 'pfh-widgets' ),
		];

		$this->controls['showNav'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Show the arrows', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['images'] = [
			'tab'           => 'content',
			'group'         => 'content',
			'label'         => esc_html__( 'Pictures', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'default'       => [],
			'fields'        => [
				'image' => [ 'label' => esc_html__( 'Picture', 'pfh-widgets' ), 'type' => 'image' ],
				'title' => [ 'label' => esc_html__( 'Description', 'pfh-widgets' ), 'type' => 'text' ],
			],
			'description'   => esc_html__( 'Used when no Instagram feed is connected, and whenever Instagram cannot be reached.', 'pfh-widgets' ),
		];

		/* ---- feed ---- */

		$this->controls['useFeed'] = [
			'tab'     => 'content',
			'group'   => 'feed',
			'label'   => esc_html__( 'Use the feed', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['limit'] = [
			'tab'      => 'content',
			'group'    => 'feed',
			'label'    => esc_html__( 'Posts to show', 'pfh-widgets' ),
			'type'     => 'number',
			'min'      => 1,
			'max'      => 50,
			'inline'   => true,
			'default'  => 12,
			'required' => [ 'useFeed', '=', true ],
		];

		$this->controls['linkTiles'] = [
			'tab'         => 'content',
			'group'       => 'feed',
			'label'       => esc_html__( 'Link each picture to its post', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => esc_html__( 'Only pictures that came from the feed have a post to link to.', 'pfh-widgets' ),
		];

		$this->controls['feedNote'] = [
			'tab'     => 'content',
			'group'   => 'feed',
			'type'    => 'info',
			'content' => esc_html__( 'The token lives under Products For Home → Instagram, not here: one account for the whole shop, and no key stored in the page. Without one the pictures above are used.', 'pfh-widgets' ),
		];

		/* ---- motion ---- */

		$this->controls['speed'] = [
			'tab'         => 'content',
			'group'       => 'motion',
			'label'       => esc_html__( 'Speed', 'pfh-widgets' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 200,
			'inline'      => true,
			'default'     => 40,
			'description' => esc_html__( 'Pixels a second. 0 holds it still and leaves the arrows and dragging working.', 'pfh-widgets' ),
		];

		$this->controls['direction'] = [
			'tab'     => 'content',
			'group'   => 'motion',
			'label'   => esc_html__( 'Direction', 'pfh-widgets' ),
			'type'    => 'select',
			'inline'  => true,
			'default' => 'left',
			'options' => [
				'left'  => esc_html__( 'Right to left', 'pfh-widgets' ),
				'right' => esc_html__( 'Left to right', 'pfh-widgets' ),
			],
		];

		$this->controls['pauseHover'] = [
			'tab'         => 'content',
			'group'       => 'motion',
			'label'       => esc_html__( 'Pause under the cursor', 'pfh-widgets' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'A picture someone is looking at stops moving.', 'pfh-widgets' ),
		];

		/* ---- style ---- */

		$this->controls['titleInk']  = $this->colour( 'style', esc_html__( 'Title', 'pfh-widgets' ), '#14181b' );
		$this->controls['accent']    = $this->colour( 'style', esc_html__( 'Title accent', 'pfh-widgets' ), '#2b5f63' );
		$this->controls['pillInk']   = $this->colour( 'style', esc_html__( 'Account', 'pfh-widgets' ), '#14181b' );
		$this->controls['pillLine']  = $this->colour( 'style', esc_html__( 'Account border', 'pfh-widgets' ), '#cfdbd6' );
		$this->controls['navInk']    = $this->colour( 'style', esc_html__( 'Arrows', 'pfh-widgets' ), '#14181b' );
		$this->controls['navLine']   = $this->colour( 'style', esc_html__( 'Arrow border', 'pfh-widgets' ), '#dcd8c6' );

		$this->controls['titleSize'] = $this->number( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 36, 20, 72 );
		$this->controls['pillSize']  = $this->number( 'style', esc_html__( 'Account height (px)', 'pfh-widgets' ), 36, 28, 56 );
		$this->controls['navSize']   = $this->number( 'style', esc_html__( 'Arrow size (px)', 'pfh-widgets' ), 38, 28, 56 );
		$this->controls['radius']    = $this->number( 'style', esc_html__( 'Picture radius (px)', 'pfh-widgets' ), 15, 0, 40 );

		/* ---- layout ---- */

		$this->controls['maxWidth'] = $this->number( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1240, 600, 1600 );
		$this->controls['tile']     = $this->number( 'layout', esc_html__( 'Picture size (px)', 'pfh-widgets' ), 272, 120, 480 );
		$this->controls['gap']      = $this->number( 'layout', esc_html__( 'Space between pictures (px)', 'pfh-widgets' ), 24, 0, 60 );
		$this->controls['headGap']  = $this->number( 'layout', esc_html__( 'Space under the heading (px)', 'pfh-widgets' ), 36, 0, 120 );
		$this->controls['padTop']   = $this->number( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 72, 0, 200 );
		$this->controls['padBottom'] = $this->number( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 72, 0, 200 );
	}

	/**
	 * @param string $group   Group.
	 * @param string $label   Label.
	 * @param string $default Hex.
	 * @return array
	 */
	private function colour( $group, $label, $default ) {
		return [
			'tab'     => 'content',
			'group'   => $group,
			'label'   => $label,
			'type'    => 'color',
			'inline'  => true,
			'default' => [ 'hex' => $default ],
		];
	}

	/**
	 * @param string $group   Group.
	 * @param string $label   Label.
	 * @param int    $default Default.
	 * @param int    $min     Min.
	 * @param int    $max     Max.
	 * @return array
	 */
	private function number( $group, $label, $default, $min, $max ) {
		return [
			'tab'     => 'content',
			'group'   => $group,
			'label'   => $label,
			'type'    => 'number',
			'min'     => $min,
			'max'     => $max,
			'inline'  => true,
			'default' => $default,
		];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$tiles = $this->tiles();

		if ( ! $tiles ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-ig pfh-ig--empty"><p>'
					. esc_html__( 'Nothing to show yet: no Instagram feed is connected under Products For Home, and no pictures have been added here.', 'pfh-widgets' )
					. '</p></div>';
			}

			return;
		}

		$this->set_attribute( '_root', 'class', [ 'pfh-ig', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-ig__inner"><div class="pfh-ig__head">';

		$title = trim( (string) $this->setting( 'title', '' ) );

		if ( '' !== $title ) {
			// <em> is the italic serif in the design; nothing else is allowed.
			echo '<h2 class="pfh-ig__title">' . wp_kses( $title, [ 'em' => [], 'i' => [], 'br' => [], 'strong' => [] ] ) . '</h2>';
		}

		echo '<div class="pfh-ig__tools">';
		$this->render_handle();
		$this->render_nav();
		echo '</div></div></div>';

		$this->render_strip( $tiles );

		echo '</section>';
	}

	/**
	 * The account, as a pill. A link only once a profile URL is given.
	 */
	private function render_handle() {
		$handle = trim( (string) $this->setting( 'handle', '' ) );

		if ( '' === $handle ) {
			return;
		}

		$url  = esc_url( trim( (string) $this->setting( 'profileUrl', '' ) ) );
		$icon = PFH_Widgets_Icons::get( 'instagram', 'pfh-ig__pill-icon' );
		$body = $icon . '<span class="pfh-ig__pill-label">' . esc_html( $handle ) . '</span>';

		if ( '' !== $url ) {
			printf(
				'<a class="pfh-ig__pill" href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( $url ),
				$body // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);

			return;
		}

		echo '<span class="pfh-ig__pill">' . $body . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	}

	/**
	 * The two round arrows.
	 */
	private function render_nav() {
		if ( ! $this->switched_on( 'showNav', true ) ) {
			return;
		}

		echo '<div class="pfh-ig__nav">';

		printf(
			'<button type="button" class="pfh-ig__arrow pfh-ig__arrow--prev" data-pfh-ig-step="-1" aria-label="%s">%s</button>',
			esc_attr__( 'Previous pictures', 'pfh-widgets' ),
			PFH_Widgets_Icons::get( 'nav-left' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		);

		printf(
			'<button type="button" class="pfh-ig__arrow pfh-ig__arrow--next" data-pfh-ig-step="1" aria-label="%s">%s</button>',
			esc_attr__( 'More pictures', 'pfh-widgets' ),
			PFH_Widgets_Icons::get( 'nav-right' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		);

		echo '</div>';
	}

	/**
	 * The strip itself.
	 *
	 * One set of pictures is printed; the script clones it as many times as the
	 * screen needs and drifts the track. Nothing is cloned on the server —
	 * doubling the markup would double the images a browser without the script
	 * has to download for no gain.
	 *
	 * @param array $tiles Pictures.
	 */
	private function render_strip( array $tiles ) {
		printf(
			'<div class="pfh-ig__strip" data-pfh-ig data-pfh-ig-speed="%s" data-pfh-ig-direction="%s" data-pfh-ig-pause="%s">',
			esc_attr( (string) max( 0, (int) $this->setting( 'speed', 40 ) ) ),
			esc_attr( 'right' === $this->setting( 'direction', 'left' ) ? 'right' : 'left' ),
			$this->switched_on( 'pauseHover', true ) ? '1' : '0'
		);

		echo '<ul class="pfh-ig__set" data-pfh-ig-set>';

		$link = $this->switched_on( 'linkTiles', false );

		foreach ( $tiles as $i => $tile ) {
			echo '<li class="pfh-ig__tile">';

			$image = sprintf(
				'<img class="pfh-ig__img" src="%s" alt="%s" width="%d" height="%d" decoding="async"%s />',
				esc_url( $tile['url'] ),
				esc_attr( $tile['alt'] ),
				(int) $this->setting( 'tile', 272 ),
				(int) $this->setting( 'tile', 272 ),
				$i < 6 ? '' : ' loading="lazy"'
			);

			if ( $link && '' !== $tile['permalink'] ) {
				printf(
					'<a class="pfh-ig__link" href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url( $tile['permalink'] ),
					$image // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				);
			} else {
				echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			}

			echo '</li>';
		}

		echo '</ul></div>';
	}

	/* ---------------------------------------------------------------------
	 * Pictures
	 * ------------------------------------------------------------------ */

	/**
	 * The feed, or what is set here.
	 *
	 * @return array<int, array{url:string, alt:string, permalink:string}>
	 */
	private function tiles() {
		$out = [];

		if ( $this->switched_on( 'useFeed', true ) && class_exists( 'PFH_Widgets_Instagram' ) ) {
			foreach ( PFH_Widgets_Instagram::posts( [ 'limit' => (int) $this->setting( 'limit', 12 ) ] ) as $post ) {
				if ( empty( $post['url'] ) ) {
					continue;
				}

				$out[] = [
					'url'       => (string) $post['url'],
					'alt'       => isset( $post['alt'] ) ? (string) $post['alt'] : '',
					'permalink' => isset( $post['permalink'] ) ? (string) $post['permalink'] : '',
				];
			}
		}

		if ( $out ) {
			return $out;
		}

		return $this->own_images();
	}

	/**
	 * The pictures set on the element.
	 *
	 * @return array<int, array{url:string, alt:string, permalink:string}>
	 */
	private function own_images() {
		$rows = $this->setting( 'images', [] );
		$out  = [];

		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$image = isset( $row['image'] ) ? $row['image'] : '';
			$url   = '';

			if ( is_array( $image ) ) {
				$url = PFH_Widgets_Helpers::image_url( $image, 'medium_large' );
			} elseif ( is_scalar( $image ) && absint( $image ) ) {
				$url = (string) wp_get_attachment_image_url( absint( $image ), 'medium_large' );
			} elseif ( is_string( $image ) && '' !== $image ) {
				$url = $image;
			}

			if ( '' === $url ) {
				continue;
			}

			$alt = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';

			if ( '' === $alt && is_array( $image ) && ! empty( $image['id'] ) ) {
				$alt = (string) get_post_meta( (int) $image['id'], '_wp_attachment_image_alt', true );
			}

			$out[] = [
				'url'       => $url,
				'alt'       => $alt,
				'permalink' => '',
			];
		}

		return $out;
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-ig-max'       => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1240 ) ),
				'--pfh-ig-tile-set'  => PFH_Widgets_Helpers::unit( $this->setting( 'tile', 272 ) ),
				'--pfh-ig-gap-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'gap', 24 ) ),
				'--pfh-ig-head-set'  => PFH_Widgets_Helpers::unit( $this->setting( 'headGap', 36 ) ),
				'--pfh-ig-pt-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 72 ) ),
				'--pfh-ig-pb-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 72 ) ),
				'--pfh-ig-title-set' => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 36 ) ),
				'--pfh-ig-pill-set'  => PFH_Widgets_Helpers::unit( $this->setting( 'pillSize', 36 ) ),
				'--pfh-ig-nav-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'navSize', 38 ) ),
				'--pfh-ig-radius'    => PFH_Widgets_Helpers::unit( $this->setting( 'radius', 15 ) ),
				'--pfh-ig-title-ink' => PFH_Widgets_Helpers::color( $this->setting( 'titleInk' ), '#14181b' ),
				'--pfh-ig-accent'    => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#2b5f63' ),
				'--pfh-ig-pill-ink'  => PFH_Widgets_Helpers::color( $this->setting( 'pillInk' ), '#14181b' ),
				'--pfh-ig-pill-line' => PFH_Widgets_Helpers::color( $this->setting( 'pillLine' ), '#cfdbd6' ),
				'--pfh-ig-nav-ink'   => PFH_Widgets_Helpers::color( $this->setting( 'navInk' ), '#14181b' ),
				'--pfh-ig-nav-line'  => PFH_Widgets_Helpers::color( $this->setting( 'navLine' ), '#dcd8c6' ),
			]
		);
	}
}
