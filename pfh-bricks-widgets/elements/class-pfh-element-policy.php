<?php
/**
 * Bricks element: a policy or information page.
 *
 * Terms, shipping, returns, complaints, privacy — documents rather than shop
 * pages. They are all the same shape: an opening, a run of numbered sections,
 * and a contents panel that keeps up with the reader. So they are all the
 * same element, and the five pages the shop already has are subclasses of it
 * that carry nothing but their own words.
 *
 * Every line of every one of them is a field. The client rewrites a clause
 * the way they would rewrite a sentence in a letter, and nothing here needs
 * opening again.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Element_Policy extends \Bricks\Element {

	use PFH_Element_Defaults;

	public $category     = 'products-for-home';
	public $name         = 'pfh-policy';
	public $icon         = 'ti-layout-accordion-separated';
	public $css_selector = '.pfh-policy';

	public function get_label() {
		return esc_html__( 'PFH Info page', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'policy', 'legal', 'terms', 'voorwaarden', 'beleid', 'pfh' ];
	}

	public function enqueue_scripts() {
		PFH_Widgets_Assets::policy();
	}

	/**
	 * The words this page opens with.
	 *
	 * Everything a subclass changes, it changes here — the controls, the
	 * render and the stylesheet are the same for all of them.
	 *
	 * @return array{eyebrow:string, title:string, lede:string, updated:string, numbered:bool, sections:array}
	 */
	protected function copy() {
		return [
			'eyebrow'  => '',
			'title'    => '',
			'lede'     => '',
			'updated'  => '',
			'numbered' => false,
			'sections' => [],
		];
	}

	public function set_control_groups() {
		foreach ( [
			'head'     => esc_html__( 'Opening', 'pfh-widgets' ),
			'sections' => esc_html__( 'Sections', 'pfh-widgets' ),
			'contents' => esc_html__( 'Contents panel', 'pfh-widgets' ),
			'style'    => esc_html__( 'Style', 'pfh-widgets' ),
			'layout'   => esc_html__( 'Layout', 'pfh-widgets' ),
		] as $key => $title ) {
			$this->control_groups[ $key ] = [ 'title' => $title, 'tab' => 'content' ];
		}
	}

	public function set_controls() {
		$copy = $this->copy();

		/* ---- opening ---- */

		$this->controls['eyebrow'] = $this->text( 'head', esc_html__( 'Eyebrow', 'pfh-widgets' ), $copy['eyebrow'] );

		$this->controls['title'] = $this->text(
			'head',
			esc_html__( 'Title', 'pfh-widgets' ),
			$copy['title'],
			[ 'description' => esc_html__( 'A word wrapped in <em> is set in the italic serif.', 'pfh-widgets' ) ]
		);

		$this->controls['lede'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'label'   => esc_html__( 'Opening paragraph', 'pfh-widgets' ),
			'type'    => 'editor',
			'default' => $copy['lede'],
		];

		$this->controls['updated'] = $this->text(
			'head',
			esc_html__( 'Last changed', 'pfh-widgets' ),
			$copy['updated'],
			[ 'description' => esc_html__( 'Shown as a small line under the opening. Leave it empty to hide it.', 'pfh-widgets' ) ]
		);

		/* ---- the sections ---- */

		$this->controls['numbered'] = [
			'tab'     => 'content',
			'group'   => 'sections',
			'label'   => esc_html__( 'Number the sections', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => $copy['numbered'],
		];

		$this->controls['sections'] = [
			'tab'           => 'content',
			'group'         => 'sections',
			'label'         => esc_html__( 'Sections', 'pfh-widgets' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'default'       => $copy['sections'],
			'fields'        => [
				'title' => [
					'label' => esc_html__( 'Heading', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'text'  => [
					'label' => esc_html__( 'Text', 'pfh-widgets' ),
					'type'  => 'editor',
				],
				'chips' => [
					'label'       => esc_html__( 'Chips', 'pfh-widgets' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'One per line: payment methods, an address, a phone number. An e-mail address or a phone number becomes a link.', 'pfh-widgets' ),
				],
				'link'  => [
					'label' => esc_html__( 'Button text', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'url'   => [
					'label' => esc_html__( 'Button link', 'pfh-widgets' ),
					'type'  => 'text',
				],
				'tone'  => [
					'label'   => esc_html__( 'Look', 'pfh-widgets' ),
					'type'    => 'select',
					'inline'  => true,
					'default' => 'plain',
					'options' => [
						'plain'     => esc_html__( 'Plain', 'pfh-widgets' ),
						'highlight' => esc_html__( 'On its own ground', 'pfh-widgets' ),
					],
				],
			],
			'description'   => esc_html__( 'A paragraph that opens with its own number — 1.1, 6.6 — gets that number set in the margin. A quote in the editor becomes a highlighted note.', 'pfh-widgets' ),
		];

		/* ---- contents ---- */

		$this->controls['showToc'] = [
			'tab'     => 'content',
			'group'   => 'contents',
			'label'   => esc_html__( 'Show the contents', 'pfh-widgets' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['tocTitle'] = $this->text( 'contents', esc_html__( 'Heading', 'pfh-widgets' ), 'Op deze pagina' );

		/* ---- style ---- */

		$this->controls['ink']      = $this->colour( 'style', esc_html__( 'Headings', 'pfh-widgets' ), '#22301c' );
		$this->controls['bodyInk']  = $this->colour( 'style', esc_html__( 'Body text', 'pfh-widgets' ), '#43503f' );
		$this->controls['mutedInk'] = $this->colour( 'style', esc_html__( 'Quiet text', 'pfh-widgets' ), '#8d9589' );
		$this->controls['accent']   = $this->colour( 'style', esc_html__( 'Accent', 'pfh-widgets' ), '#2b5f63' );
		$this->controls['band']     = $this->colour( 'style', esc_html__( 'Highlighted ground', 'pfh-widgets' ), '#f6f8f5' );

		$this->controls['titleSize'] = $this->number( 'style', esc_html__( 'Title size (px)', 'pfh-widgets' ), 42, 22, 64 );
		$this->controls['headSize']  = $this->number( 'style', esc_html__( 'Heading size (px)', 'pfh-widgets' ), 22, 16, 36 );
		$this->controls['textSize']  = $this->number( 'style', esc_html__( 'Text size (px)', 'pfh-widgets' ), 16, 14, 20 );

		/* ---- layout ---- */

		$this->controls['maxWidth']   = $this->number( 'layout', esc_html__( 'Container width (px)', 'pfh-widgets' ), 1140, 600, 1600 );
		$this->controls['measure']    = $this->number( 'layout', esc_html__( 'Document width (px)', 'pfh-widgets' ), 760, 480, 960 );
		$this->controls['asideWidth'] = $this->number( 'layout', esc_html__( 'Contents width (px)', 'pfh-widgets' ), 260, 180, 360 );
		$this->controls['padTop']     = $this->number( 'layout', esc_html__( 'Space above (px)', 'pfh-widgets' ), 56, 0, 200 );
		$this->controls['padBottom']  = $this->number( 'layout', esc_html__( 'Space below (px)', 'pfh-widgets' ), 88, 0, 200 );
	}

	/* ---- control helpers ---- */

	protected function text( $group, $label, $default = '', array $extra = [] ) {
		return array_merge( [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'text', 'default' => $default ], $extra );
	}

	protected function colour( $group, $label, $default ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'color', 'inline' => true, 'default' => [ 'hex' => $default ] ];
	}

	protected function number( $group, $label, $default, $min, $max ) {
		return [ 'tab' => 'content', 'group' => $group, 'label' => $label, 'type' => 'number', 'min' => $min, 'max' => $max, 'inline' => true, 'default' => $default ];
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	public function render() {
		$sections = $this->sections();
		$title    = trim( (string) $this->setting( 'title', '' ) );
		$lede     = trim( (string) $this->setting( 'lede', '' ) );

		if ( '' === $title && '' === $lede && ! $sections ) {
			if ( PFH_Widgets_Helpers::is_builder_context() ) {
				echo '<div class="pfh-policy pfh-policy--empty"><p>'
					. esc_html__( 'Nothing to show yet: give the page a title or a section.', 'pfh-widgets' )
					. '</p></div>';
			}

			return;
		}

		// One section is a page, not a contents list.
		$toc = $this->switched_on( 'showToc' ) && count( $sections ) > 1;

		$this->set_attribute( '_root', 'class', [ 'pfh-policy', 'pfh-scope' ] );
		$this->set_attribute( '_root', 'style', $this->build_vars() );

		echo '<section ' . $this->render_attributes( '_root' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks escapes its own attributes.
		echo '<div class="pfh-policy__inner">';

		$this->render_head( $title, $lede );

		printf( '<div class="pfh-policy__layout%s">', $toc ? '' : ' pfh-policy__layout--wide' );

		echo '<div class="pfh-policy__main" data-pfh-contents-body>';
		$this->render_sections( $sections );
		echo '</div>';

		if ( $toc ) {
			$this->render_toc( $sections );
		}

		echo '</div></div></section>';
	}

	/**
	 * @param string $title Title.
	 * @param string $lede  Opening paragraph.
	 */
	private function render_head( $title, $lede ) {
		$eyebrow = trim( (string) $this->setting( 'eyebrow', '' ) );
		$updated = trim( (string) $this->setting( 'updated', '' ) );

		if ( '' === $title && '' === $lede && '' === $eyebrow && '' === $updated ) {
			return;
		}

		echo '<header class="pfh-policy__head">';

		if ( '' !== $eyebrow ) {
			echo '<p class="pfh-policy__eyebrow">' . esc_html( $eyebrow ) . '</p>';
		}

		if ( '' !== $title ) {
			echo '<h1 class="pfh-policy__title">' . wp_kses( $title, [ 'em' => [], 'i' => [], 'br' => [], 'strong' => [] ] ) . '</h1>';
		}

		if ( '' !== $lede ) {
			echo '<div class="pfh-policy__lede">' . wp_kses_post( wpautop( $lede ) ) . '</div>';
		}

		if ( '' !== $updated ) {
			printf(
				'<p class="pfh-policy__updated">%s<span>%s</span></p>',
				PFH_Widgets_Icons::get( 'clock' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG.
				esc_html( $updated )
			);
		}

		echo '</header>';
	}

	/**
	 * @param array $sections Sections.
	 */
	private function render_sections( array $sections ) {
		if ( ! $sections ) {
			return;
		}

		echo '<div class="pfh-policy__sections">';

		foreach ( $sections as $section ) {
			printf(
				'<section class="pfh-policy__section%s" id="%s">',
				'highlight' === $section['tone'] ? ' pfh-policy__section--highlight' : '',
				esc_attr( $section['id'] )
			);

			if ( '' !== $section['title'] ) {
				echo '<div class="pfh-policy__section-head">';

				if ( $section['number'] ) {
					echo '<span class="pfh-policy__no">' . esc_html( $section['number'] ) . '</span>';
				}

				echo '<h2 class="pfh-policy__section-title">' . wp_kses( $section['title'], [ 'em' => [], 'i' => [], 'br' => [], 'strong' => [] ] ) . '</h2>';
				echo '</div>';
			}

			if ( '' !== $section['text'] ) {
				echo '<div class="pfh-policy__body">'
					. PFH_Widgets_Policy::body( $section['text'] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd there.
					. '</div>';
			}

			$this->render_chips( $section['chips'] );

			if ( '' !== $section['link'] && '' !== $section['url'] ) {
				printf(
					'<a class="pfh-policy__cta" href="%s"%s>%s%s</a>',
					esc_url( $section['url'] ),
					$section['external'] ? ' target="_blank" rel="noopener noreferrer"' : '',
					esc_html( $section['link'] ),
					PFH_Widgets_Icons::get( 'arrow-ne' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG.
				);
			}

			echo '</section>';
		}

		echo '</div>';
	}

	/**
	 * A row of chips: payment methods, an address, a way to reach someone.
	 *
	 * @param string[] $chips Lines.
	 */
	private function render_chips( array $chips ) {
		if ( ! $chips ) {
			return;
		}

		echo '<ul class="pfh-policy__chips">';

		foreach ( $chips as $chip ) {
			echo '<li class="pfh-policy__chip">';

			if ( is_email( $chip ) ) {
				printf(
					'%s<a href="mailto:%s">%s</a>',
					PFH_Widgets_Icons::get( 'mail' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG.
					esc_attr( sanitize_email( $chip ) ),
					esc_html( $chip )
				);
			} elseif ( preg_match( '/^[0-9 +()\-]{8,}$/', $chip ) ) {
				printf(
					'%s<a href="tel:%s">%s</a>',
					PFH_Widgets_Icons::get( 'phone' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG.
					esc_attr( PFH_Widgets_Helpers::diallable( $chip ) ),
					esc_html( $chip )
				);
			} else {
				echo esc_html( $chip );
			}

			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * @param array $sections Sections.
	 */
	private function render_toc( array $sections ) {
		$title = trim( (string) $this->setting( 'tocTitle', '' ) );

		// A <details> so a phone can fold it away; open, so a desktop sees it.
		printf(
			'<details class="pfh-policy__toc" open data-pfh-contents="pfh-policy"><summary>%s</summary><p class="pfh-policy__toc-title">%s</p><ul class="pfh-policy__toc-list">',
			esc_html( $title ),
			esc_html( $title )
		);

		foreach ( $sections as $section ) {
			if ( '' === $section['title'] ) {
				continue;
			}

			$label = wp_strip_all_tags( $section['title'] );

			printf(
				'<li class="pfh-policy__toc-item"><a class="pfh-policy__toc-link" href="#%s" title="%s">%s%s</a></li>',
				esc_attr( $section['id'] ),
				esc_attr( $label ),
				$section['number'] ? '<span class="pfh-policy__toc-no">' . esc_html( $section['number'] ) . '.</span>' : '',
				esc_html( PFH_Widgets_Helpers::shorten( $label ) )
			);
		}

		echo '</ul></details>';
	}

	/* ---------------------------------------------------------------------
	 * Reading the fields
	 * ------------------------------------------------------------------ */

	/**
	 * @return array<int, array{id:string, number:int, title:string, text:string, chips:string[], link:string, url:string, external:bool, tone:string}>
	 */
	private function sections() {
		$rows     = $this->setting( 'sections', [] );
		$numbered = $this->switched_on( 'numbered', false );
		$seen     = [];
		$out      = [];
		$number   = 0;

		foreach ( is_array( $rows ) ? $rows : [] as $index => $row ) {
			$title = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';
			$text  = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';
			$chips = PFH_Widgets_Policy::lines( isset( $row['chips'] ) ? $row['chips'] : '' );
			$link  = isset( $row['link'] ) ? trim( (string) $row['link'] ) : '';
			$url   = isset( $row['url'] ) ? trim( (string) $row['url'] ) : '';

			if ( '' === $title && '' === $text && ! $chips ) {
				continue;
			}

			$number++;

			$out[] = [
				'id'       => PFH_Widgets_Policy::anchor( $title, $index, $seen ),
				'number'   => $numbered ? $number : 0,
				'title'    => $title,
				'text'     => $text,
				'chips'    => $chips,
				'link'     => $link,
				'url'      => $url,
				'external' => self::external( $url ),
				'tone'     => isset( $row['tone'] ) && 'highlight' === $row['tone'] ? 'highlight' : 'plain',
			];
		}

		return $out;
	}

	/**
	 * Does this link leave the shop?
	 *
	 * The returns page sends people to the carrier's own portal and the
	 * complaints page to WebwinkelKeur; both should open beside the shop
	 * rather than replace it. A link to a page of this site should not.
	 *
	 * @param string $url Link.
	 * @return bool
	 */
	private static function external( $url ) {
		$host = wp_parse_url( (string) $url, PHP_URL_HOST );

		if ( ! $host || ! function_exists( 'home_url' ) ) {
			return false;
		}

		$bare = static function ( $name ) {
			return strtolower( preg_replace( '/^www\./i', '', (string) $name ) );
		};

		return $bare( $host ) !== $bare( wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	private function build_vars() {
		return PFH_Widgets_Helpers::css_vars(
			[
				'--pfh-pol-max'         => PFH_Widgets_Helpers::unit( $this->setting( 'maxWidth', 1140 ) ),
				'--pfh-pol-measure-set' => PFH_Widgets_Helpers::unit( $this->setting( 'measure', 760 ) ),
				'--pfh-pol-aside-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'asideWidth', 260 ) ),
				'--pfh-pol-pt-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'padTop', 56 ) ),
				'--pfh-pol-pb-set'      => PFH_Widgets_Helpers::unit( $this->setting( 'padBottom', 88 ) ),
				'--pfh-pol-title-set'   => PFH_Widgets_Helpers::unit( $this->setting( 'titleSize', 42 ) ),
				'--pfh-pol-head-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'headSize', 22 ) ),
				'--pfh-pol-text-set'    => PFH_Widgets_Helpers::unit( $this->setting( 'textSize', 16 ) ),
				'--pfh-pol-ink'         => PFH_Widgets_Helpers::color( $this->setting( 'ink' ), '#22301c' ),
				'--pfh-pol-body'        => PFH_Widgets_Helpers::color( $this->setting( 'bodyInk' ), '#43503f' ),
				'--pfh-pol-muted'       => PFH_Widgets_Helpers::color( $this->setting( 'mutedInk' ), '#8d9589' ),
				'--pfh-pol-accent'      => PFH_Widgets_Helpers::color( $this->setting( 'accent' ), '#2b5f63' ),
				'--pfh-pol-band'        => PFH_Widgets_Helpers::color( $this->setting( 'band' ), '#f6f8f5' ),
			]
		);
	}
}
