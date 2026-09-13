<?php
/**
 * A small PDF writer.
 *
 * Enough of PDF 1.4 to lay out an invoice and nothing more: the standard
 * Helvetica faces, text with real metrics so wrapping is accurate, rules,
 * filled boxes and one raster image. No dependency, because bundling Dompdf
 * into a widget plugin costs more than the documents are worth.
 *
 * Coordinates are given from the top-left in points, which is how a document
 * is actually measured; the flip to PDF's bottom-left origin happens here.
 *
 * Text is encoded to CP1252 (WinAnsi). That covers Dutch and English, which
 * is the whole of this store's content. Greek or Cyrillic would need an
 * embedded TrueType font and a different encoding.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_PDF {

	/**
	 * Advance widths per 1000 units, for characters 32-126.
	 *
	 * @var array<string, int[]>
	 */
	private static $metrics = [
		'regular' => [
			278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
			556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
			1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
			667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
			333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
			556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
		],
		'bold'    => [
			278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278,
			556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 333, 333, 584, 584, 584, 611,
			975, 722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778,
			667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 333, 278, 333, 584, 556,
			333, 556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611,
			611, 611, 389, 556, 333, 611, 556, 778, 556, 556, 500, 389, 280, 389, 584,
		],
	];

	/**
	 * Widths for the punctuation block at 0x80-0x9F, which has no ASCII twin.
	 *
	 * @var array<int, int>
	 */
	private static $high = [
		0x80 => 556, 0x82 => 222, 0x83 => 556, 0x84 => 333, 0x85 => 1000, 0x86 => 556,
		0x87 => 556, 0x88 => 333, 0x89 => 1000, 0x8A => 667, 0x8B => 333, 0x8C => 1000,
		0x8E => 611, 0x91 => 222, 0x92 => 222, 0x93 => 333, 0x94 => 333, 0x95 => 350,
		0x96 => 556, 0x97 => 1000, 0x98 => 333, 0x99 => 1000, 0x9A => 500, 0x9B => 333,
		0x9C => 944, 0x9E => 500, 0x9F => 667,
	];

	/**
	 * 0xA0-0xFF folded to the ASCII character that shares its advance.
	 *
	 * Accented Latin letters are exactly as wide as their base letter in both
	 * Helvetica faces, so this is not an approximation for the characters
	 * Dutch actually uses.
	 */
	const FOLD = ' !cLoY|S O a<--O o+23 uP. 1o>%%%?AAAAAAWCEEEEIIIIDNOOOOOxOUUUUYPBaaaaaamceeeeiiiionooooo+ouuuuypy';

	/** Page geometry, in points. */
	private $width;
	private $height;
	private $margin;

	/** @var array<int, string> Content stream per page. */
	private $pages = [];

	/** @var int Index of the page being written. */
	private $page = -1;

	/** @var array<int, array{data:string, w:int, h:int}> Embedded images. */
	private $images = [];

	/** Current text state. */
	private $size = 10.0;
	private $bold = false;
	private $fill = '0 0 0';

	/**
	 * @param array $args {
	 *     @type float $width  Page width in points. Default A4.
	 *     @type float $height Page height in points. Default A4.
	 *     @type float $margin Default margin. Default 40.
	 * }
	 */
	public function __construct( array $args = [] ) {
		$this->width  = (float) ( $args['width'] ?? 595.28 );
		$this->height = (float) ( $args['height'] ?? 841.89 );
		$this->margin = (float) ( $args['margin'] ?? 40.0 );
	}

	/* ---------------------------------------------------------------------
	 * Geometry
	 * ------------------------------------------------------------------ */

	public function page_width() {
		return $this->width;
	}

	public function page_height() {
		return $this->height;
	}

	public function margin() {
		return $this->margin;
	}

	/**
	 * Usable width between the margins.
	 *
	 * @return float
	 */
	public function inner_width() {
		return $this->width - ( 2 * $this->margin );
	}

	/**
	 * How many pages exist so far.
	 *
	 * @return int
	 */
	public function page_count() {
		return count( $this->pages );
	}

	/**
	 * Start a new page.
	 *
	 * @return $this
	 */
	public function add_page() {
		$this->pages[] = '';
		$this->page    = count( $this->pages ) - 1;

		return $this;
	}

	/**
	 * Point subsequent drawing at an existing page.
	 *
	 * Lets a caller come back and stamp something it could not know earlier,
	 * such as "page 2 of 5".
	 *
	 * @param int $index Zero-based page index.
	 * @return $this
	 */
	public function select_page( $index ) {
		$index = (int) $index;

		if ( isset( $this->pages[ $index ] ) ) {
			$this->page = $index;
		}

		return $this;
	}

	/**
	 * Append a content-stream operator to the current page.
	 *
	 * @param string $op Operator line.
	 */
	private function write( $op ) {
		if ( $this->page < 0 ) {
			$this->add_page();
		}

		$this->pages[ $this->page ] .= $op . "\n";
	}

	/**
	 * Format a number the way a content stream wants it.
	 *
	 * %.2F rather than %.2f so a locale using a decimal comma cannot produce
	 * a stream the reader rejects.
	 *
	 * @param float $value Number.
	 * @return string
	 */
	private function n( $value ) {
		return sprintf( '%.2F', (float) $value );
	}

	/**
	 * Flip a top-left y to PDF's bottom-left origin.
	 *
	 * @param float $y Distance from the top.
	 * @return float
	 */
	private function y( $y ) {
		return $this->height - (float) $y;
	}

	/* ---------------------------------------------------------------------
	 * Colour and text state
	 * ------------------------------------------------------------------ */

	/**
	 * Set the fill colour.
	 *
	 * @param string $hex Hex colour.
	 * @return $this
	 */
	public function color( $hex ) {
		$this->fill = self::rgb( $hex );

		return $this;
	}

	/**
	 * Hex to a PDF colour triplet.
	 *
	 * @param string $hex Hex colour.
	 * @return string
	 */
	private static function rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return '0 0 0';
		}

		return sprintf(
			'%.3F %.3F %.3F',
			hexdec( substr( $hex, 0, 2 ) ) / 255,
			hexdec( substr( $hex, 2, 2 ) ) / 255,
			hexdec( substr( $hex, 4, 2 ) ) / 255
		);
	}

	/**
	 * Set the face and size for subsequent text.
	 *
	 * @param float $size Point size.
	 * @param bool  $bold Use the bold face.
	 * @return $this
	 */
	public function font( $size, $bold = false ) {
		$this->size = (float) $size;
		$this->bold = (bool) $bold;

		return $this;
	}

	/* ---------------------------------------------------------------------
	 * Measuring
	 * ------------------------------------------------------------------ */

	/**
	 * Width of a string at the current size, in points.
	 *
	 * @param string $text Text.
	 * @return float
	 */
	public function width_of( $text ) {
		$bytes = $this->encode( (string) $text );
		$table = self::$metrics[ $this->bold ? 'bold' : 'regular' ];
		$total = 0;

		$length = strlen( $bytes );

		for ( $i = 0; $i < $length; $i++ ) {
			$code = ord( $bytes[ $i ] );

			if ( $code >= 32 && $code <= 126 ) {
				$total += $table[ $code - 32 ];
				continue;
			}

			if ( isset( self::$high[ $code ] ) ) {
				$total += self::$high[ $code ];
				continue;
			}

			if ( $code >= 0xA0 ) {
				$base = ord( substr( self::FOLD, $code - 0xA0, 1 ) );
				$total += ( $base >= 32 && $base <= 126 ) ? $table[ $base - 32 ] : 556;
				continue;
			}

			$total += 556;
		}

		return ( $total / 1000 ) * $this->size;
	}

	/**
	 * Break text into lines that fit a width.
	 *
	 * Splits on spaces; a single word wider than the box is broken by
	 * character, so a long SKU or URL cannot run off the page.
	 *
	 * @param string $text  Text, newlines respected.
	 * @param float  $width Available width in points.
	 * @return string[]
	 */
	public function wrap( $text, $width ) {
		$width = (float) $width;
		$lines = [];

		foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $paragraph ) {
			$words   = preg_split( '/\s+/', trim( $paragraph ) );
			$current = '';

			foreach ( (array) $words as $word ) {
				if ( '' === $word ) {
					continue;
				}

				$candidate = '' === $current ? $word : $current . ' ' . $word;

				if ( $this->width_of( $candidate ) <= $width ) {
					$current = $candidate;
					continue;
				}

				if ( '' !== $current ) {
					$lines[] = $current;
					$current = '';
				}

				// The word alone still does not fit: cut it.
				while ( $this->width_of( $word ) > $width && strlen( $word ) > 1 ) {
					$cut = strlen( $word );

					while ( $cut > 1 && $this->width_of( substr( $word, 0, $cut ) ) > $width ) {
						$cut--;
					}

					$lines[] = substr( $word, 0, $cut );
					$word    = substr( $word, $cut );
				}

				$current = $word;
			}

			$lines[] = $current;
		}

		return $lines;
	}

	/* ---------------------------------------------------------------------
	 * Drawing
	 * ------------------------------------------------------------------ */

	/**
	 * Draw one line of text.
	 *
	 * @param float  $x     Left edge, or the right edge when aligning right.
	 * @param float  $y     Baseline distance from the top.
	 * @param string $text  Text.
	 * @param string $align left|right|center.
	 * @return $this
	 */
	public function text( $x, $y, $text, $align = 'left' ) {
		$text = (string) $text;

		if ( '' === $text ) {
			return $this;
		}

		if ( 'right' === $align ) {
			$x -= $this->width_of( $text );
		} elseif ( 'center' === $align ) {
			$x -= $this->width_of( $text ) / 2;
		}

		$this->write(
			sprintf(
				'BT %s rg /%s %s Tf %s %s Td (%s) Tj ET',
				$this->fill,
				$this->bold ? 'F2' : 'F1',
				$this->n( $this->size ),
				$this->n( $x ),
				$this->n( $this->y( $y ) ),
				$this->escape( $text )
			)
		);

		return $this;
	}

	/**
	 * Draw wrapped text.
	 *
	 * @param float  $x      Left edge.
	 * @param float  $y      Baseline of the first line.
	 * @param float  $width  Box width.
	 * @param string $text   Text.
	 * @param float  $leading Line height. Defaults to 1.35em.
	 * @return float The y just past the last line.
	 */
	public function paragraph( $x, $y, $width, $text, $leading = 0 ) {
		$leading = $leading > 0 ? (float) $leading : $this->size * 1.35;

		foreach ( $this->wrap( $text, $width ) as $line ) {
			$this->text( $x, $y, $line );
			$y += $leading;
		}

		return $y;
	}

	/**
	 * Filled rectangle.
	 *
	 * @param float  $x   Left.
	 * @param float  $y   Top.
	 * @param float  $w   Width.
	 * @param float  $h   Height.
	 * @param string $hex Fill colour.
	 * @return $this
	 */
	public function rect( $x, $y, $w, $h, $hex ) {
		$this->write(
			sprintf(
				'%s rg %s %s %s %s re f',
				self::rgb( $hex ),
				$this->n( $x ),
				$this->n( $this->y( $y + $h ) ),
				$this->n( $w ),
				$this->n( $h )
			)
		);

		return $this;
	}

	/**
	 * Horizontal or diagonal rule.
	 *
	 * @param float  $x1    Start x.
	 * @param float  $y1    Start y.
	 * @param float  $x2    End x.
	 * @param float  $y2    End y.
	 * @param string $hex   Colour.
	 * @param float  $thick Line width.
	 * @return $this
	 */
	public function line( $x1, $y1, $x2, $y2, $hex = '#cccccc', $thick = 0.5 ) {
		$this->write(
			sprintf(
				'%s RG %s w %s %s m %s %s l S',
				self::rgb( $hex ),
				$this->n( $thick ),
				$this->n( $x1 ),
				$this->n( $this->y( $y1 ) ),
				$this->n( $x2 ),
				$this->n( $this->y( $y2 ) )
			)
		);

		return $this;
	}

	/**
	 * Place an image.
	 *
	 * Anything GD can open is re-encoded to JPEG first, so the PDF only ever
	 * has to carry one image filter and transparency is flattened onto white
	 * rather than rendering black.
	 *
	 * @param string $binary Raw image bytes.
	 * @param float  $x      Left.
	 * @param float  $y      Top.
	 * @param float  $max_w  Maximum width.
	 * @param float  $max_h  Maximum height.
	 * @return array{w:float, h:float} The size actually drawn, zeroes on failure.
	 */
	public function image( $binary, $x, $y, $max_w, $max_h ) {
		$jpeg = self::to_jpeg( $binary );

		if ( ! $jpeg ) {
			return [
				'w' => 0.0,
				'h' => 0.0,
			];
		}

		$scale = min( $max_w / $jpeg['w'], $max_h / $jpeg['h'], 1.0 );
		$w     = $jpeg['w'] * $scale;
		$h     = $jpeg['h'] * $scale;

		$this->images[] = $jpeg;
		$index          = count( $this->images );

		$this->write(
			sprintf(
				'q %s 0 0 %s %s %s cm /I%d Do Q',
				$this->n( $w ),
				$this->n( $h ),
				$this->n( $x ),
				$this->n( $this->y( $y + $h ) ),
				$index
			)
		);

		return [
			'w' => $w,
			'h' => $h,
		];
	}

	/**
	 * Normalise any image to baseline JPEG via GD.
	 *
	 * @param string $binary Raw bytes.
	 * @return array{data:string, w:int, h:int}|null
	 */
	private static function to_jpeg( $binary ) {
		if ( ! is_string( $binary ) || '' === $binary || ! function_exists( 'imagecreatefromstring' ) ) {
			return null;
		}

		$image = @imagecreatefromstring( $binary ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

		if ( ! $image ) {
			return null;
		}

		$w = imagesx( $image );
		$h = imagesy( $image );

		// Flatten onto white: PDF's DCTDecode has no alpha channel.
		$flat = imagecreatetruecolor( $w, $h );
		imagefill( $flat, 0, 0, imagecolorallocate( $flat, 255, 255, 255 ) );
		imagecopy( $flat, $image, 0, 0, 0, 0, $w, $h );
		imagedestroy( $image );

		ob_start();
		imagejpeg( $flat, null, 88 );
		$data = ob_get_clean();
		imagedestroy( $flat );

		if ( ! is_string( $data ) || '' === $data ) {
			return null;
		}

		return [
			'data' => $data,
			'w'    => $w,
			'h'    => $h,
		];
	}

	/* ---------------------------------------------------------------------
	 * Encoding
	 * ------------------------------------------------------------------ */

	/**
	 * UTF-8 to CP1252.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private function encode( $text ) {
		$text = (string) $text;

		if ( '' === $text ) {
			return '';
		}

		// Pure ASCII is already CP1252; skip the converters entirely.
		if ( ! preg_match( '/[\x80-\xFF]/', $text ) ) {
			return $text;
		}

		if ( function_exists( 'iconv' ) ) {
			$out = @iconv( 'UTF-8', 'CP1252//TRANSLIT//IGNORE', $text ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

			if ( false !== $out && '' !== $out ) {
				return $out;
			}
		}

		if ( function_exists( 'mb_convert_encoding' ) ) {
			$out = @mb_convert_encoding( $text, 'CP1252', 'UTF-8' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

			if ( is_string( $out ) && '' !== $out ) {
				return $out;
			}
		}

		// Last resort: drop anything above ASCII rather than emit broken bytes.
		return (string) preg_replace( '/[^\x20-\x7E\n]/', '', $text );
	}

	/**
	 * Escape a string for a PDF literal.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private function escape( $text ) {
		return strtr(
			$this->encode( $text ),
			[
				'\\' => '\\\\',
				'('  => '\\(',
				')'  => '\\)',
				"\r" => '',
				"\n" => ' ',
			]
		);
	}

	/* ---------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	/**
	 * Assemble the file.
	 *
	 * @return string Raw PDF bytes.
	 */
	public function output() {
		if ( ! $this->pages ) {
			$this->add_page();
		}

		$images = count( $this->images );
		$first  = 5 + $images;
		$count  = count( $this->pages );

		$kids = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$kids[] = ( $first + ( $i * 2 ) + 1 ) . ' 0 R';
		}

		$xobjects = '';

		for ( $i = 1; $i <= $images; $i++ ) {
			$xobjects .= '/I' . $i . ' ' . ( 4 + $i ) . ' 0 R ';
		}

		$resources = '<< /Font << /F1 3 0 R /F2 4 0 R >>'
			. ( $xobjects ? ' /XObject << ' . trim( $xobjects ) . ' >>' : '' )
			. ' /ProcSet [/PDF /Text /ImageC] >>';

		$objects = [
			1 => '<< /Type /Catalog /Pages 2 0 R >>',
			2 => '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . $count . ' >>',
			3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
			4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
		];

		foreach ( $this->images as $i => $image ) {
			$objects[ 5 + $i ] = '<< /Type /XObject /Subtype /Image /Width ' . (int) $image['w']
				. ' /Height ' . (int) $image['h']
				. ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '
				. strlen( $image['data'] ) . " >>\nstream\n" . $image['data'] . "\nendstream";
		}

		for ( $i = 0; $i < $count; $i++ ) {
			$content = $this->pages[ $i ];
			$filter  = '';

			if ( function_exists( 'gzcompress' ) ) {
				$packed = gzcompress( $content, 6 );

				if ( is_string( $packed ) && strlen( $packed ) < strlen( $content ) ) {
					$content = $packed;
					$filter  = ' /Filter /FlateDecode';
				}
			}

			$content_obj = $first + ( $i * 2 );
			$page_obj    = $content_obj + 1;

			$objects[ $content_obj ] = '<< /Length ' . strlen( $content ) . $filter . " >>\nstream\n" . $content . "\nendstream";

			$objects[ $page_obj ] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '
				. $this->n( $this->width ) . ' ' . $this->n( $this->height ) . '] '
				. '/Resources ' . $resources . ' /Contents ' . $content_obj . ' 0 R >>';
		}

		ksort( $objects );

		$out     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = [];

		foreach ( $objects as $number => $body ) {
			$offsets[ $number ] = strlen( $out );
			$out               .= $number . " 0 obj\n" . $body . "\nendobj\n";
		}

		$total = count( $objects ) + 1;
		$start = strlen( $out );

		$out .= "xref\n0 " . $total . "\n0000000000 65535 f \n";

		for ( $i = 1; $i < $total; $i++ ) {
			$out .= sprintf( "%010d 00000 n \n", isset( $offsets[ $i ] ) ? $offsets[ $i ] : 0 );
		}

		$out .= "trailer\n<< /Size " . $total . " /Root 1 0 R >>\nstartxref\n" . $start . "\n%%EOF";

		return $out;
	}
}
