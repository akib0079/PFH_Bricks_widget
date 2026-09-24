<?php
/**
 * Settings framework.
 *
 * Every module (the WebwinkelKeur feed, Instagram) declares a flat
 * schema; this file turns that schema into defaults, a sanitiser and an admin
 * screen, so a new field is one array entry rather than three code paths that
 * can drift apart.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

/**
 * Base class for a settings-backed module.
 *
 * Children set OPTION and fields(); everything else comes from here.
 */
abstract class PFH_Settings_Module {

	/**
	 * Option name holding this module's values. Children must override.
	 */
	const OPTION = '';

	/**
	 * Per-request memo of the resolved values, keyed by option name.
	 *
	 * Static is safe here because the key is the option name, not per-instance
	 * state: two modules never share a bucket.
	 *
	 * @var array<string, array>
	 */
	private static $memo = [];

	/**
	 * Schema.
	 *
	 * @return array<string, array{label:string, desc?:string, fields:array}>
	 */
	public static function fields() {
		return [];
	}

	/**
	 * Flatten the schema to key => field definition.
	 *
	 * @return array<string, array>
	 */
	public static function flat_fields() {
		$out = [];

		foreach ( static::fields() as $section ) {
			if ( empty( $section['fields'] ) || ! is_array( $section['fields'] ) ) {
				continue;
			}

			foreach ( $section['fields'] as $key => $field ) {
				$out[ $key ] = $field;
			}
		}

		return $out;
	}

	/**
	 * Default values, derived from the schema.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		$out = [];

		foreach ( static::flat_fields() as $key => $field ) {
			if ( 'info' === ( $field['type'] ?? 'text' ) ) {
				continue;
			}

			$out[ $key ] = $field['default'] ?? '';
		}

		return $out;
	}

	/**
	 * Stored values merged over the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all() {
		$option = static::OPTION;

		if ( isset( self::$memo[ $option ] ) ) {
			return self::$memo[ $option ];
		}

		$stored = get_option( $option, [] );
		$stored = is_array( $stored ) ? $stored : [];

		/**
		 * Filter a module's resolved settings.
		 *
		 * @param array  $values Settings.
		 * @param string $option Option name.
		 */
		$values = apply_filters( 'pfh_widgets_settings', array_merge( static::defaults(), $stored ), $option );

		self::$memo[ $option ] = $values;

		return $values;
	}

	/**
	 * One setting.
	 *
	 * @param string $key      Field key.
	 * @param mixed  $fallback Returned when the key is unknown.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$all = static::all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Persist values, sanitised.
	 *
	 * @param array $raw Raw input, usually $_POST.
	 * @return array The stored values.
	 */
	public static function update( array $raw ) {
		$clean = static::sanitize( $raw );

		update_option( static::OPTION, $clean );
		unset( self::$memo[ static::OPTION ] );

		return $clean;
	}

	/**
	 * Drop the per-request memo (tests, and after a programmatic update).
	 */
	public static function forget() {
		unset( self::$memo[ static::OPTION ] );
	}

	/**
	 * Coerce raw input to the schema's types.
	 *
	 * Unknown keys are dropped, so a stale form cannot write junk into the
	 * option, and every value comes back in the type the module expects.
	 *
	 * @param array $raw Raw input.
	 * @return array
	 */
	public static function sanitize( array $raw ) {
		$out = [];

		foreach ( static::flat_fields() as $key => $field ) {
			$type = $field['type'] ?? 'text';

			if ( 'info' === $type ) {
				continue;
			}

			$default = $field['default'] ?? '';

			// An unchecked checkbox is absent from the POST body entirely.
			if ( 'checkbox' === $type ) {
				$out[ $key ] = ! empty( $raw[ $key ] );
				continue;
			}

			if ( ! isset( $raw[ $key ] ) ) {
				$out[ $key ] = $default;
				continue;
			}

			$value = wp_unslash( $raw[ $key ] );

			switch ( $type ) {
				case 'number':
					$value = is_numeric( $value ) ? $value + 0 : $default;

					if ( isset( $field['min'] ) ) {
						$value = max( $field['min'], $value );
					}

					if ( isset( $field['max'] ) ) {
						$value = min( $field['max'], $value );
					}

					$out[ $key ] = $value;
					break;

				case 'select':
					$choices     = array_keys( (array) ( $field['choices'] ?? [] ) );
					$value       = (string) $value;
					$out[ $key ] = in_array( $value, $choices, true ) ? $value : $default;
					break;

				case 'url':
					$out[ $key ] = esc_url_raw( trim( (string) $value ) );
					break;

				case 'color':
					$out[ $key ] = PFH_Widgets_Helpers::color( (string) $value, (string) $default );
					break;

				case 'textarea':
					$out[ $key ] = sanitize_textarea_field( (string) $value );
					break;

				case 'html':
					$out[ $key ] = wp_kses_post( (string) $value );
					break;

				case 'media':
					$out[ $key ] = absint( $value );
					break;

				case 'password':
					$out[ $key ] = trim( (string) $value );
					break;

				default:
					$out[ $key ] = sanitize_text_field( (string) $value );
					break;
			}
		}

		return $out;
	}
}

/**
 * The admin screen that hosts every module.
 */
class PFH_Widgets_Settings {

	/**
	 * Menu slug.
	 */
	const PAGE = 'pfh-widgets';

	/**
	 * Nonce action.
	 */
	const NONCE = 'pfh_widgets_settings';

	/**
	 * Capability required to see and save the screen.
	 */
	const CAP = 'manage_options';

	/**
	 * Registered tabs: slug => [label, class].
	 *
	 * @var array<string, array{label:string|callable, class:string}>
	 */
	private static $tabs = [];

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	/**
	 * Register a module as a tab.
	 *
	 * Modules register on plugins_loaded, which is before WordPress lets a
	 * plugin translate anything — calling __() there logs a "translation
	 * loading was triggered too early" notice on every request. So the label
	 * can be a callable returning the translated text, and it is only called
	 * when the screen is drawn.
	 *
	 * @param string          $slug  Tab slug.
	 * @param string|callable $label Tab label, or a callable returning it.
	 * @param string          $class Module class name (extends PFH_Settings_Module).
	 */
	public static function register( $slug, $label, $class ) {
		self::$tabs[ $slug ] = [
			'label' => $label,
			'class' => $class,
		];
	}

	/**
	 * @return array<string, array{label:string|callable, class:string}>
	 */
	public static function tabs() {
		return self::$tabs;
	}

	/**
	 * Add the top-level menu.
	 */
	public static function menu() {
		add_menu_page(
			esc_html__( 'Products For Home', 'pfh-widgets' ),
			esc_html__( 'Products For Home', 'pfh-widgets' ),
			self::CAP,
			self::PAGE,
			[ __CLASS__, 'render' ],
			'dashicons-store',
			58
		);
	}

	/**
	 * Admin CSS, only on our own screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function assets( $hook ) {
		if ( 'toplevel_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'pfh-admin',
			PFH_WIDGETS_URL . 'assets/css/pfh-admin.css',
			[],
			PFH_WIDGETS_VERSION
		);

		wp_enqueue_media();

		wp_enqueue_script(
			'pfh-admin',
			PFH_WIDGETS_URL . 'assets/js/pfh-admin.js',
			[ 'jquery' ],
			PFH_WIDGETS_VERSION,
			true
		);
	}

	/**
	 * Which tab is showing.
	 *
	 * @return string
	 */
	private static function current_tab() {
		$tabs = array_keys( self::$tabs );

		if ( ! $tabs ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
		$want = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		return in_array( $want, $tabs, true ) ? $want : $tabs[0];
	}

	/**
	 * Render the screen and handle the save.
	 */
	public static function render() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to manage these settings.', 'pfh-widgets' ) );
		}

		$tab    = self::current_tab();
		$notice = '';

		if ( isset( $_POST['pfh_settings_tab'] ) ) {
			check_admin_referer( self::NONCE );

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitised on the next line.
			$posted = sanitize_key( wp_unslash( $_POST['pfh_settings_tab'] ) );

			if ( isset( self::$tabs[ $posted ] ) ) {
				$class = self::$tabs[ $posted ]['class'];

				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field is sanitised by the schema.
				$values = isset( $_POST['pfh'] ) && is_array( $_POST['pfh'] ) ? $_POST['pfh'] : [];

				$class::update( $values );

				/**
				 * Fires after a settings tab is saved.
				 *
				 * @param string $tab   Tab slug.
				 * @param string $class Module class.
				 */
				do_action( 'pfh_widgets_settings_saved', $posted, $class );

				$tab    = $posted;
				$notice = __( 'Settings saved.', 'pfh-widgets' );
			}
		}

		$class = self::$tabs[ $tab ]['class'] ?? '';

		if ( ! $class || ! class_exists( $class ) ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Products For Home', 'pfh-widgets' ) . '</h1></div>';

			return;
		}

		$values = $class::all();
		?>
		<div class="wrap pfh-settings">
			<h1><?php esc_html_e( 'Products For Home', 'pfh-widgets' ); ?></h1>

			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper pfh-settings__tabs">
				<?php foreach ( self::$tabs as $slug => $meta ) : ?>
					<a class="nav-tab <?php echo $slug === $tab ? 'nav-tab-active' : ''; ?>"
						href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&tab=' . $slug ) ); ?>">
						<?php echo esc_html( is_callable( $meta['label'] ) ? (string) call_user_func( $meta['label'] ) : (string) $meta['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&tab=' . $tab ) ); ?>">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="pfh_settings_tab" value="<?php echo esc_attr( $tab ); ?>">

				<?php foreach ( $class::fields() as $section ) : ?>
					<div class="pfh-settings__section">
						<h2><?php echo esc_html( $section['label'] ?? '' ); ?></h2>

						<?php if ( ! empty( $section['desc'] ) ) : ?>
							<p class="pfh-settings__intro"><?php echo wp_kses_post( $section['desc'] ); ?></p>
						<?php endif; ?>

						<table class="form-table" role="presentation">
							<tbody>
								<?php foreach ( (array) ( $section['fields'] ?? [] ) as $key => $field ) : ?>
									<?php self::row( $key, $field, $values ); ?>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * One settings row.
	 *
	 * @param string $key    Field key.
	 * @param array  $field  Field definition.
	 * @param array  $values Current values.
	 */
	private static function row( $key, array $field, array $values ) {
		$type  = $field['type'] ?? 'text';
		$label = $field['label'] ?? $key;
		$desc  = $field['desc'] ?? '';
		$value = $values[ $key ] ?? ( $field['default'] ?? '' );
		$name  = 'pfh[' . $key . ']';
		$id    = 'pfh-' . $key;
		$show  = $field['show_if'] ?? '';

		// An info row is either static copy or a panel the module draws itself,
		// which is how the live diagnostics get onto the screen.
		if ( 'info' === $type ) {
			?>
			<tr class="pfh-settings__info"<?php echo $show ? ' data-show-if="' . esc_attr( $show ) . '"' : ''; ?>>
				<td colspan="2">
					<?php
					if ( ! empty( $field['callback'] ) && is_callable( $field['callback'] ) ) {
						call_user_func( $field['callback'], $values );
					} else {
						echo '<div class="pfh-settings__note">' . wp_kses_post( $desc ) . '</div>';
					}
					?>
				</td>
			</tr>
			<?php
			return;
		}
		?>
		<tr<?php echo $show ? ' data-show-if="' . esc_attr( $show ) . '"' : ''; ?>>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<?php
				switch ( $type ) {
					case 'checkbox':
						printf(
							'<label class="pfh-settings__toggle"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s data-pfh-field="%4$s"> <span>%5$s</span></label>',
							esc_attr( $id ),
							esc_attr( $name ),
							checked( ! empty( $value ), true, false ),
							esc_attr( $key ),
							esc_html( $field['cb_label'] ?? __( 'Enable', 'pfh-widgets' ) )
						);
						break;

					case 'select':
						printf( '<select id="%1$s" name="%2$s" data-pfh-field="%3$s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $key ) );

						foreach ( (array) ( $field['choices'] ?? [] ) as $ck => $cl ) {
							printf(
								'<option value="%1$s" %2$s>%3$s</option>',
								esc_attr( $ck ),
								selected( (string) $value, (string) $ck, false ),
								esc_html( $cl )
							);
						}

						echo '</select>';

						if ( ! empty( $field['preview'] ) ) {
							echo '<div class="pfh-settings__preview" data-pfh-preview="' . esc_attr( $key ) . '">';

							foreach ( (array) $field['preview'] as $ck => $sample ) {
								printf(
									'<code data-for="%1$s"%2$s>%3$s</code>',
									esc_attr( $ck ),
									(string) $value === (string) $ck ? '' : ' hidden',
									esc_html( $sample )
								);
							}

							echo '</div>';
						}
						break;

					case 'textarea':
					case 'html':
						printf(
							'<textarea id="%1$s" name="%2$s" rows="%3$d" class="large-text code">%4$s</textarea>',
							esc_attr( $id ),
							esc_attr( $name ),
							absint( $field['rows'] ?? 4 ),
							esc_textarea( (string) $value )
						);
						break;

					case 'number':
						printf(
							'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text"%4$s%5$s%6$s>',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( (string) $value ),
							isset( $field['min'] ) ? ' min="' . esc_attr( (string) $field['min'] ) . '"' : '',
							isset( $field['max'] ) ? ' max="' . esc_attr( (string) $field['max'] ) . '"' : '',
							isset( $field['step'] ) ? ' step="' . esc_attr( (string) $field['step'] ) . '"' : ''
						);

						if ( ! empty( $field['suffix'] ) ) {
							echo ' <span class="pfh-settings__suffix">' . esc_html( $field['suffix'] ) . '</span>';
						}
						break;

					case 'color':
						printf(
							'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text pfh-color"><input type="color" class="pfh-color__swatch" value="%4$s" aria-label="%5$s">',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( (string) $value ),
							esc_attr( preg_match( '/^#[0-9a-f]{6}$/i', (string) $value ) ? (string) $value : '#000000' ),
							esc_attr__( 'Pick a colour', 'pfh-widgets' )
						);
						break;

					case 'media':
						$src = $value ? wp_get_attachment_image_url( (int) $value, 'medium' ) : '';
						?>
						<div class="pfh-media" data-pfh-media>
							<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
							<div class="pfh-media__frame"><?php if ( $src ) : ?><img src="<?php echo esc_url( $src ); ?>" alt=""><?php endif; ?></div>
							<button type="button" class="button pfh-media__pick"><?php esc_html_e( 'Choose image', 'pfh-widgets' ); ?></button>
							<button type="button" class="button-link pfh-media__clear"<?php echo $value ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'pfh-widgets' ); ?></button>
						</div>
						<?php
						break;

					case 'password':
						printf(
							'<input type="password" id="%1$s" name="%2$s" value="%3$s" class="regular-text" autocomplete="off" spellcheck="false">',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( (string) $value )
						);
						break;

					default:
						printf(
							'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text"%5$s>',
							esc_attr( 'url' === $type ? 'url' : 'text' ),
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( (string) $value ),
							! empty( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : ''
						);
						break;
				}

				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				?>
			</td>
		</tr>
		<?php
	}
}
