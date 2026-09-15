<?php
/**
 * Reading a setting, with the element's own declared defaults behind it.
 *
 * Two Bricks behaviours make this necessary, and together they were deleting
 * whole sections from live pages:
 *
 *   Bricks builds an element's control list when it needs the panel. On the
 *   front end it does not, so `$this->controls` is empty exactly where the
 *   defaults declared in it are wanted.
 *
 *   Bricks does not store a value that still equals its default. A section the
 *   editor drops in and leaves alone is saved with little or nothing in its
 *   settings — the defaults are the whole of its content.
 *
 * An element that answered "no setting, so nothing" therefore rendered nothing
 * at all on a live page while looking perfectly correct in the builder. The
 * control list is where an element's defaults are declared, so it is built on
 * demand and read.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

trait PFH_Element_Defaults {

	/** Set while set_controls() runs, so reading a default cannot recurse. */
	private $pfh_building_controls = false;

	/**
	 * A setting, the element's own default for it, then the caller's.
	 *
	 * @param string $key     Control name.
	 * @param mixed  $default Used when the control declares no default either.
	 * @return mixed
	 */
	private function setting( $key, $default = null ) {
		if ( isset( $this->settings[ $key ] ) && '' !== $this->settings[ $key ] ) {
			return $this->settings[ $key ];
		}

		/*
		 * Present but empty means the editor cleared it on purpose — an
		 * eyebrow they do not want, a button they removed. Handing back the
		 * control's default there would put it straight back on the page and
		 * leave no way to get rid of it.
		 */
		if ( array_key_exists( $key, (array) $this->settings ) ) {
			return $default;
		}

		return $this->control_default( $key, $default );
	}

	/**
	 * Is a switch on, taking the control's own default when it was never set?
	 *
	 * @param string $key     Control name.
	 * @param bool   $default Used when the control declares no default either.
	 * @return bool
	 */
	private function switched_on( $key, $default = true ) {
		if ( array_key_exists( $key, (array) $this->settings ) ) {
			return ! empty( $this->settings[ $key ] );
		}

		$own = $this->control_default( $key, null );

		return null === $own ? $default : ! empty( $own );
	}

	/**
	 * What this element declares as the default for one control.
	 *
	 * @param string $key     Control name.
	 * @param mixed  $default Returned when there is no declared default.
	 * @return mixed
	 */
	private function control_default( $key, $default = null ) {
		$this->ensure_controls();

		if ( isset( $this->controls[ $key ] ) && array_key_exists( 'default', $this->controls[ $key ] ) ) {
			$own = $this->controls[ $key ]['default'];

			if ( '' !== $own && null !== $own ) {
				return $own;
			}
		}

		return $default;
	}

	/**
	 * Make sure this element knows its own defaults.
	 *
	 * Building them is a pass over an array — the one control that used to
	 * query, the highlight's product picker, already declines to outside the
	 * builder — so it is cheap enough to do the moment a default is wanted,
	 * and it happens at most once per element.
	 */
	private function ensure_controls() {
		if ( ! empty( $this->controls ) || $this->pfh_building_controls ) {
			return;
		}

		// set_controls() reads settings through this same trait, so without
		// the flag the first missing default would recurse forever.
		$this->pfh_building_controls = true;

		try {
			$this->set_control_groups();
			$this->set_controls();
		} catch ( \Throwable $e ) {
			// A panel that cannot be built must not take the page with it.
			$this->controls = (array) $this->controls;
		}

		$this->pfh_building_controls = false;
	}
}
