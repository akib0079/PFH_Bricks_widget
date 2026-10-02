<?php
/**
 * Product photographs: which kind each one is, and serving them sharp.
 *
 * The shop has two kinds of product photograph. A cut-out on a transparent
 * background sits on the plugin's soft grey tile with room around it. A
 * photograph with its own background — a packshot on white, a styled shot on
 * a table — used to sit on that same tile as a smaller rectangle, which the
 * client read as "a white box with a grey border, zoomed out". Those now fill
 * the whole tile instead. Which kind a picture is cannot be told from its
 * file type (a PNG can be opaque), so the corners are looked at once and the
 * answer kept on the attachment.
 *
 * Also here: the repair for photographs an upload optimiser flattened. Admin
 * and Site Enhancements' "Image Upload Control" converts PNGs it believes are
 * opaque to JPG, and it misjudges palette PNGs whose transparency lives in a
 * tRNS chunk — the transparent area became black. Where the original PNG is
 * still next to the JPG on disk, the attachment is pointed back at it.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Photo {

	/** Attachment meta holding the verdict: '1' own background, '0' cut-out. */
	const META = '_pfh_backdrop';

	/** Bumped when the way of deciding changes, so old verdicts are redone. */
	const VERSION = '1';

	const REPAIR_ACTION = 'pfh_repair_photos';

	/** How long one click of the repair button may work before stopping. */
	const REPAIR_SECONDS = 40;

	public static function init() {
		// A regenerated or replaced file may be a different kind of picture.
		add_filter( 'wp_update_attachment_metadata', [ __CLASS__, 'forget' ], 10, 2 );
		add_action( 'admin_post_' . self::REPAIR_ACTION, [ __CLASS__, 'handle_repair' ] );
	}

	/**
	 * @param array $data          Attachment metadata.
	 * @param int   $attachment_id Attachment.
	 * @return array
	 */
	public static function forget( $data, $attachment_id ) {
		delete_post_meta( (int) $attachment_id, self::META );

		return $data;
	}

	/**
	 * Does this photograph bring its own background?
	 *
	 * Answered from the four corners of a small rendition: any corner that is
	 * see-through makes it a cut-out. A JPEG cannot be see-through, so it
	 * always has a background — no need to open it.
	 *
	 * @param int $attachment_id Attachment.
	 * @return bool
	 */
	public static function has_backdrop( $attachment_id ) {
		$attachment_id = (int) $attachment_id;

		if ( $attachment_id <= 0 ) {
			return false;
		}

		$known = get_post_meta( $attachment_id, self::META, true );

		if ( is_string( $known ) && '' !== $known && 0 === strpos( $known, self::VERSION . ':' ) ) {
			return '1' === substr( $known, -1 );
		}

		$verdict = self::look( $attachment_id );

		update_post_meta( $attachment_id, self::META, self::VERSION . ':' . ( $verdict ? '1' : '0' ) );

		return $verdict;
	}

	/**
	 * @param int $attachment_id Attachment.
	 * @return bool
	 */
	private static function look( $attachment_id ) {
		return self::file_has_backdrop( self::small_file( $attachment_id ) );
	}

	/**
	 * The same question asked of a file on disk.
	 *
	 * @param string $file Path.
	 * @return bool
	 */
	private static function file_has_backdrop( $file ) {
		if ( '' === $file || ! is_readable( $file ) ) {
			return false;
		}

		$type = strtolower( (string) pathinfo( $file, PATHINFO_EXTENSION ) );

		if ( in_array( $type, [ 'jpg', 'jpeg' ], true ) ) {
			return true;
		}

		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			// Without GD a PNG or WebP is assumed to be a cut-out, which is
			// how they were always drawn.
			return false;
		}

		$bytes = @file_get_contents( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file, failure handled.

		if ( false === $bytes || '' === $bytes ) {
			return false;
		}

		$image = @imagecreatefromstring( $bytes ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- unreadable images are handled.

		if ( ! $image ) {
			return false;
		}

		$w = imagesx( $image );
		$h = imagesy( $image );

		// A pixel in from each corner: some exports leave a one-pixel
		// transparent border around an otherwise solid photograph.
		$points = [ [ 1, 1 ], [ $w - 2, 1 ], [ 1, $h - 2 ], [ $w - 2, $h - 2 ] ];
		$solid  = true;

		foreach ( $points as $point ) {
			$x = max( 0, min( $w - 1, $point[0] ) );
			$y = max( 0, min( $h - 1, $point[1] ) );

			$rgba  = imagecolorsforindex( $image, imagecolorat( $image, $x, $y ) );
			$alpha = isset( $rgba['alpha'] ) ? (int) $rgba['alpha'] : 0;

			// GD's alpha runs 0 (opaque) to 127 (clear).
			if ( $alpha > 20 ) {
				$solid = false;
				break;
			}
		}

		imagedestroy( $image );

		return $solid;
	}

	/**
	 * Is this a packshot on plain white: all four corners near white?
	 *
	 * Such a photograph sits on a white page as well as a cut-out does, so a
	 * category without a cut-out product can still lead with one of its own
	 * (feedback, 2026-09-28: the honey page showed a lemonade jar).
	 *
	 * @param int $attachment_id Attachment.
	 * @return bool
	 */
	public static function on_white( $attachment_id ) {
		$file = self::small_file( (int) $attachment_id );

		if ( '' === $file || ! is_readable( $file ) || ! function_exists( 'imagecreatefromstring' ) ) {
			return false;
		}

		$bytes = @file_get_contents( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file, failure handled.
		$image = $bytes ? @imagecreatefromstring( $bytes ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- unreadable images are handled.

		if ( ! $image ) {
			return false;
		}

		$w     = imagesx( $image );
		$h     = imagesy( $image );
		$white = true;

		foreach ( [ [ 2, 2 ], [ $w - 3, 2 ], [ 2, $h - 3 ], [ $w - 3, $h - 3 ] ] as $point ) {
			$rgba = imagecolorsforindex( $image, imagecolorat( $image, max( 0, $point[0] ), max( 0, $point[1] ) ) );

			if ( min( $rgba['red'], $rgba['green'], $rgba['blue'] ) < 238 || ( isset( $rgba['alpha'] ) && $rgba['alpha'] > 20 ) ) {
				$white = false;
				break;
			}
		}

		imagedestroy( $image );

		return $white;
	}

	/**
	 * The smallest rendition on disk, so deciding costs as little as it can.
	 *
	 * @param int $attachment_id Attachment.
	 * @return string Path, or ''.
	 */
	private static function small_file( $attachment_id ) {
		$full = get_attached_file( $attachment_id );

		if ( ! $full ) {
			return '';
		}

		$meta = wp_get_attachment_metadata( $attachment_id );

		if ( is_array( $meta ) && ! empty( $meta['sizes'] ) ) {
			foreach ( [ 'woocommerce_gallery_thumbnail', 'thumbnail', 'woocommerce_thumbnail', 'medium' ] as $size ) {
				if ( ! empty( $meta['sizes'][ $size ]['file'] ) ) {
					$path = path_join( dirname( $full ), $meta['sizes'][ $size ]['file'] );

					if ( is_readable( $path ) ) {
						return $path;
					}
				}
			}
		}

		return $full;
	}

	/**
	 * Everything a card needs to draw one photograph sharply.
	 *
	 * A 300px thumbnail was stretched over a card that is drawn 300px wide on
	 * a 2x screen, which is why the category pages looked soft. The browser
	 * now gets a srcset and picks; the plain src is the 600px rendition, so
	 * even without srcset it is not blurred.
	 *
	 * @param int    $attachment_id Attachment.
	 * @param string $sizes         The sizes attribute.
	 * @return array{src: string, srcset: string, sizes: string, backdrop: bool}|null
	 */
	public static function card_image( $attachment_id, $sizes ) {
		$attachment_id = (int) $attachment_id;

		if ( $attachment_id <= 0 ) {
			return null;
		}

		$src = wp_get_attachment_image_src( $attachment_id, 'woocommerce_single' );

		if ( empty( $src[0] ) ) {
			$src = wp_get_attachment_image_src( $attachment_id, 'full' );
		}

		if ( empty( $src[0] ) ) {
			return null;
		}

		$srcset = wp_get_attachment_image_srcset( $attachment_id, 'woocommerce_single' );

		return [
			'src'      => (string) $src[0],
			'srcset'   => $srcset ? (string) $srcset : '',
			'sizes'    => (string) $sizes,
			'backdrop' => self::has_backdrop( $attachment_id ),
		];
	}

	/* ------------------------------------------------------------------ */
	/* Repair                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Attachments that are PNGs in name but JPEGs on disk.
	 *
	 * @return array<int, array{id: int, file: string, png: string, products: int[]}>
	 */
	public static function flattened() {
		global $wpdb;

		$ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT p.ID FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wp_attached_file'
			 WHERE p.post_type = 'attachment' AND p.post_mime_type = 'image/png'
			 AND ( m.meta_value LIKE '%.jpg' OR m.meta_value LIKE '%.jpeg' )"
		);

		$out = [];

		foreach ( array_map( 'intval', (array) $ids ) as $id ) {
			$file = (string) get_attached_file( $id );
			$png  = preg_replace( '/\.jpe?g$/i', '.png', $file );

			// "-scaled" is WordPress's copy of a big upload; the original
			// next to it carries no suffix.
			if ( ! is_readable( $png ) ) {
				$png = preg_replace( '/-scaled\.png$/i', '.png', $png );
			}

			/*
			 * Only a PNG that is actually see-through is worth going back to.
			 * One that is opaque throughout — a photograph saved as PNG — is
			 * better as the JPEG it became: same picture, a fraction of the
			 * weight, which is what the converter was for.
			 */
			$readable = is_readable( $png );

			$out[] = [
				'id'          => $id,
				'file'        => $file,
				'png'         => $readable ? $png : '',
				'transparent' => $readable && self::png_is_see_through( $png ),
				'products'    => self::used_by( $id ),
			];
		}

		return $out;
	}

	/**
	 * Is any corner of this PNG see-through?
	 *
	 * The header says whether the file can hold transparency at all; only if
	 * it can is the picture opened, and a very large one is taken on trust
	 * rather than decoded on an admin screen.
	 *
	 * @param string $file Path of a PNG.
	 * @return bool
	 */
	private static function png_is_see_through( $file ) {
		$head = @file_get_contents( $file, false, null, 0, 33 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file, failure handled.

		if ( ! is_string( $head ) || 33 !== strlen( $head ) || "\x89PNG\r\n\x1a\n" !== substr( $head, 0, 8 ) ) {
			return false;
		}

		$size = unpack( 'Nw/Nh', substr( $head, 16, 8 ) );
		$type = ord( $head[25] );

		// Grey or RGB with an alpha channel can be see-through; the others
		// only through a tRNS chunk.
		if ( 4 !== $type && 6 !== $type ) {
			$bytes = @file_get_contents( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file, failure handled.

			if ( ! is_string( $bytes ) || false === strpos( $bytes, 'tRNS' ) ) {
				return false;
			}
		}

		if ( (int) $size['w'] * (int) $size['h'] > 16000000 ) {
			return true;
		}

		return ! self::file_has_backdrop( $file );
	}

	/**
	 * Products showing this attachment, as main image or in the gallery.
	 *
	 * @param int $attachment_id Attachment.
	 * @return int[]
	 */
	private static function used_by( $attachment_id ) {
		global $wpdb;

		$id = (int) $attachment_id;

		$rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
				 WHERE ( meta_key = '_thumbnail_id' AND meta_value = %s )
				 OR ( meta_key = '_product_image_gallery' AND CONCAT( ',', meta_value, ',' ) LIKE %s )",
				(string) $id,
				'%,' . $wpdb->esc_like( (string) $id ) . ',%'
			)
		);

		return array_map( 'intval', (array) $rows );
	}

	/**
	 * Point an attachment back at its original PNG and rebuild its sizes.
	 *
	 * The JPEG is left where it is: nothing is deleted, so a repair that
	 * turns out wrong can be undone by pointing it back.
	 *
	 * @param int    $attachment_id Attachment.
	 * @param string $png           Path of the original PNG.
	 * @return bool
	 */
	public static function repair( $attachment_id, $png ) {
		if ( '' === $png || ! is_readable( $png ) ) {
			return false;
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		/*
		 * The sizes first, the switch after: a request cut short while the
		 * sizes are made leaves the attachment as it was, still listed, so
		 * the next run picks it up again rather than leaving it half done.
		 */
		$meta = wp_generate_attachment_metadata( (int) $attachment_id, $png );

		if ( ! is_array( $meta ) || empty( $meta ) ) {
			return false;
		}

		// A large original gets WordPress's "-scaled" copy, and that copy is
		// what the attachment points at, as on any upload.
		$uploads = wp_get_upload_dir();
		$file    = ! empty( $meta['file'] ) && ! empty( $uploads['basedir'] ) ? path_join( $uploads['basedir'], $meta['file'] ) : $png;

		update_attached_file( (int) $attachment_id, is_readable( $file ) ? $file : $png );
		wp_update_post(
			[
				'ID'             => (int) $attachment_id,
				'post_mime_type' => 'image/png',
			]
		);
		wp_update_attachment_metadata( (int) $attachment_id, $meta );
		clean_attachment_cache( (int) $attachment_id );

		return true;
	}

	public static function handle_repair() {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt hier geen rechten voor.', 'pfh-widgets' ) );
		}

		check_admin_referer( self::REPAIR_ACTION );

		$fixed = 0;
		$left  = 0;
		$start = time();

		foreach ( self::flattened() as $row ) {
			if ( ! $row['transparent'] ) {
				continue;
			}

			// Hosts end a request after a minute or so, and every photo
			// means a fresh set of sizes; the rest waits for the next click.
			if ( time() - $start > self::REPAIR_SECONDS ) {
				break;
			}

			if ( self::repair( $row['id'], $row['png'] ) ) {
				$fixed++;
			} else {
				$left++;
			}
		}

		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}

		// A repaired photo may now be the cut-out a category header looks for.
		if ( class_exists( 'PFH_Widgets_Collection' ) ) {
			global $wpdb;

			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
					$wpdb->esc_like( '_transient_' . PFH_Widgets_Collection::HEADER_CACHE ) . '%',
					$wpdb->esc_like( '_transient_timeout_' . PFH_Widgets_Collection::HEADER_CACHE ) . '%'
				)
			);
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'           => PFH_Widgets_Diagnose::SLUG,
					'pfh_photos_ok'  => $fixed,
					'pfh_photos_bad' => $left,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * The repair box on the Diagnose screen.
	 */
	public static function render_repair_box() {
		$rows = array_values(
			array_filter(
				self::flattened(),
				static function ( $row ) {
					return $row['transparent'] || '' === $row['png'];
				}
			)
		);

		echo '<div class="card" style="max-width:none;margin:16px 0">';
		echo '<h2>' . esc_html__( 'Productfoto\'s met een zwarte achtergrond', 'pfh-widgets' ) . '</h2>';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		if ( isset( $_GET['pfh_photos_ok'] ) ) {
			printf(
				'<p><strong>%s</strong></p>',
				esc_html(
					sprintf(
						/* translators: 1: repaired, 2: not repaired. */
						__( '%1$d hersteld, %2$d niet automatisch te herstellen. Staan er hieronder nog foto\'s, klik dan nogmaals.', 'pfh-widgets' ),
						absint( $_GET['pfh_photos_ok'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						absint( $_GET['pfh_photos_bad'] ?? 0 ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					)
				)
			);
		}

		if ( ! $rows ) {
			echo '<p>' . esc_html__( 'Geen gevonden: alle PNG-foto\'s zijn nog PNG.', 'pfh-widgets' ) . '</p></div>';
			return;
		}

		echo '<p>' . esc_html__( 'Deze foto\'s zijn bij het uploaden van PNG naar JPG omgezet, waardoor de doorzichtige achtergrond zwart werd. Waar de originele PNG nog op de server staat, zet de knop de foto terug. Er wordt niets verwijderd. Omgezette PNG\'s zonder doorzichtigheid blijven JPG: dat is hetzelfde plaatje, maar lichter.', 'pfh-widgets' ) . '</p>';
		echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>ID</th><th>' . esc_html__( 'Bestand', 'pfh-widgets' ) . '</th><th>' . esc_html__( 'Originele PNG', 'pfh-widgets' ) . '</th><th>' . esc_html__( 'Producten', 'pfh-widgets' ) . '</th></tr></thead><tbody>';

		foreach ( $rows as $row ) {
			printf(
				'<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				(int) $row['id'],
				esc_html( basename( $row['file'] ) ),
				$row['png'] ? esc_html__( 'gevonden', 'pfh-widgets' ) : '<strong>' . esc_html__( 'niet gevonden — opnieuw uploaden', 'pfh-widgets' ) . '</strong>',
				esc_html( implode( ', ', $row['products'] ) )
			);
		}

		echo '</tbody></table>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:12px">';
		echo '<input type="hidden" name="action" value="' . esc_attr( self::REPAIR_ACTION ) . '">';
		wp_nonce_field( self::REPAIR_ACTION );
		submit_button( __( 'Herstel deze foto\'s', 'pfh-widgets' ), 'primary', 'submit', false );
		echo '</form></div>';
	}
}
