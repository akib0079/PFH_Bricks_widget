<?php
/**
 * Let a corrected design reach an element Bricks has already saved.
 *
 * Bricks writes a value for every control into the page when it is saved and
 * hands the element those settings at render. A control default is only ever
 * consulted for a key that is *missing*, so an element built under an older
 * release keeps that release's look for ever: changing a default in PHP
 * cannot reach it, and every correction looks like nothing happened.
 *
 * An element using this trait stamps its settings with a revision. When the
 * stamp is behind, the keys the design owns are dropped once so the current
 * defaults apply; the stamp is then current and the editor's own choices are
 * kept from then on. Keys that belong to the editor rather than the design —
 * how many columns, which category, the copy — are never listed and never
 * touched.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

trait PFH_Design_Revision {

	/**
	 * Drop design-owned settings the editor never actually chose.
	 *
	 * Call first thing in render(), before anything reads a setting.
	 *
	 * Non-destructive on purpose. Stamping the settings with a revision and
	 * clearing the lot when the stamp was behind looked tidier, but a builder
	 * does not necessarily store a value that equals its control default — so
	 * the stamp never persisted, the reset ran on every render, and it wiped
	 * whatever the editor had just set. Comparing against the previous
	 * default instead needs no stamp and cannot eat anyone's work.
	 *
	 * @param array<string, mixed> $was Setting name => the default it used to have.
	 */
	private function apply_design_revision( array $was ) {
		foreach ( $was as $key => $old ) {
			if ( ! array_key_exists( $key, $this->settings ) ) {
				continue;
			}

			// Still the old default means nobody chose it; anything else is
			// the editor's and is left alone.
			if ( $this->settings[ $key ] === $old ) {
				unset( $this->settings[ $key ] );
			}
		}
	}

	/**
	 * A setting, then this element's own control default, then the caller's.
	 *
	 * The middle step is what makes a dropped setting fall back to the right
	 * value: an element retunes shared controls for itself, and those
	 * defaults live on the control, not at the call site.
	 *
	 * @param string $key     Control name.
	 * @param mixed  $default Used only when the control has no default either.
	 * @return mixed
	 */
	private function setting( $key, $default = null ) {
		if ( isset( $this->settings[ $key ] ) && '' !== $this->settings[ $key ] ) {
			return $this->settings[ $key ];
		}

		/*
		 * Present but empty means the editor cleared it on purpose — an
		 * eyebrow they do not want, a button they removed. Handing back the
		 * control's default there would put the text straight back on the
		 * page and there would be no way to get rid of it.
		 */
		if ( array_key_exists( $key, (array) $this->settings ) ) {
			return $default;
		}

		// Absent: this element's own default for it, then the caller's.
		if ( isset( $this->controls[ $key ] ) && array_key_exists( 'default', $this->controls[ $key ] ) ) {
			$own = $this->controls[ $key ]['default'];

			if ( '' !== $own && null !== $own ) {
				return $own;
			}
		}

		return $default;
	}
}
