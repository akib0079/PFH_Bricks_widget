<?php
/**
 * Bricks element: Products For Home featured section — olive.
 *
 * The mirrored twin of the honey band: image on the left, text on the right,
 * green ground. It is the same element with different defaults rather than a
 * second copy, so any fix to the featured section applies to both.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-pfh-element-featured.php';

class PFH_Element_Featured_Olive extends PFH_Element_Featured {

	public $name = 'pfh-featured-olive';
	public $icon = 'ti-layout-media-left-alt';

	const OLIVE_MEDIA = self::BASE . 'Mask-group-2.png';
	const OLIVE_LEAF  = self::BASE . 'mdi_leaf.svg';
	const OLIVE_ARROW = self::BASE . 'arrow-up-right-01-1.svg';
	/* Matte recovered and halo softened — see assets/img/ and the README. */
	/* Resolved at call time, because it is served from the project repo. */

	/** Shared accent: the leaf fill and the arrow stroke are both this green. */
	const OLIVE_GREEN = '#697c66';

	public function get_label() {
		return esc_html__( 'PFH Featured Section — Olive', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'featured', 'olive', 'oil', 'promo', 'banner', 'marquee', 'pfh' ];
	}

	/**
	 * Same controls as the honey band, re-seeded for the olive variant.
	 */
	public function set_controls() {
		parent::set_controls();

		foreach ( $this->olive_defaults() as $key => $value ) {
			if ( isset( $this->controls[ $key ] ) ) {
				$this->controls[ $key ]['default'] = $value;
			}
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function olive_defaults() {
		return [
			// Layout — flat green, no pattern.
			'bgColor'   => [ 'hex' => self::OLIVE_GREEN ],
			'bgImage'   => '',

			// Image on the left, text on the right.
			'imageSide' => 'left',
			'image'     => [ 'url' => self::OLIVE_MEDIA ],
			'imageAlt'  => 'Griekse extra vierge olijfolie',

			// Floating olive branch, top right.
			'decorImage'   => [ 'url' => PFH_Widgets_Assets::img( 'pfh-olive-branch.png' ) ],
			'decorW'       => 190,
			'decorX'       => 82,
			'decorY'       => 6,

			// Light type on the green ground.
			'headColor' => [ 'hex' => '#ffffff' ],
			'textColor' => [ 'rgb' => 'rgba(255, 255, 255, 0.9)' ],
			'linkColor' => [ 'hex' => '#ffffff' ],

			// White pill, green label and arrow.
			'btnColor'  => [ 'hex' => self::OLIVE_GREEN ],
			'btnBorder' => [ 'rgb' => 'rgba(255, 255, 255, 0.6)' ],
			'btnIcon'   => [ 'url' => self::OLIVE_ARROW ],

			// Notice bar: light ground, green text, leaf separator.
			'mqBg'     => [ 'hex' => '#edf1e8' ],
			'mqColor'  => [ 'hex' => self::OLIVE_GREEN ],
			'mqIcon'   => [ 'url' => self::OLIVE_LEAF ],
			'mqSize'   => 20,
			'mqWeight' => '600',
		];
	}
}
