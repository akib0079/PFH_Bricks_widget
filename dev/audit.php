<?php
/**
 * Repeatable audit for the PFH widgets:
 *  1. every control has tab / type / a group that exists
 *  2. no inline custom property is shadowed by a media-query override
 *     (the inline value would win and the responsive rule would die)
 *  3. every `--token-set` emitted by PHP is actually read by the CSS
 */
require __DIR__ . '/stubs.php';

$dir = __DIR__ . '/../pfh-bricks-widgets/';

define( 'PFH_WIDGETS_VERSION', '0' );
define( 'PFH_WIDGETS_FILE', $dir . 'pfh-bricks-widgets.php' );
define( 'PFH_WIDGETS_DIR', $dir );
define( 'PFH_WIDGETS_URL', './' );

require $dir . 'includes/class-pfh-helpers.php';
require $dir . 'includes/class-pfh-icons.php';
require $dir . 'includes/class-pfh-cart.php';
require $dir . 'includes/class-pfh-ajax.php';
require $dir . 'includes/class-pfh-reviews.php';
require $dir . 'includes/class-pfh-assets.php';
require $dir . 'includes/trait-pfh-design-revision.php';
require $dir . 'includes/trait-pfh-product-card.php';
require $dir . 'includes/class-pfh-archive.php';

$elements = [
	'PFH_Element_Header'         => [ 'class-pfh-element-header.php', 'pfh-header.css' ],
	'PFH_Element_Footer'         => [ 'class-pfh-element-footer.php', 'pfh-footer.css' ],
	'PFH_Element_Hero'           => [ 'class-pfh-element-hero.php', 'pfh-hero.css' ],
	'PFH_Element_Categories'     => [ 'class-pfh-element-categories.php', 'pfh-categories.css' ],
	'PFH_Element_Products'       => [ 'class-pfh-element-products.php', 'pfh-products.css' ],
	'PFH_Element_Product_Grid'   => [ 'class-pfh-element-product-grid.php', 'pfh-pgrid.css' ],
	'PFH_Element_Recent'         => [ 'class-pfh-element-recent.php', 'pfh-products.css' ],
	'PFH_Element_Highlight'      => [ 'class-pfh-element-highlight.php', 'pfh-highlight.css' ],
	'PFH_Element_Featured'       => [ 'class-pfh-element-featured.php', 'pfh-featured.css' ],
	'PFH_Element_Featured_Olive' => [ 'class-pfh-element-featured-olive.php', 'pfh-featured.css' ],
	'PFH_Element_Info'           => [ 'class-pfh-element-info.php', 'pfh-info.css' ],
	'PFH_Element_Reviews'        => [ 'class-pfh-element-reviews.php', 'pfh-reviews.css' ],
	'PFH_Element_Features'       => [ 'class-pfh-element-features.php', 'pfh-features.css' ],
	'PFH_Element_Cta'            => [ 'class-pfh-element-cta.php', 'pfh-cta.css' ],
	'PFH_Element_Rating'         => [ 'class-pfh-element-rating.php', 'pfh-rating.css' ],
	'PFH_Element_Archive'        => [ 'class-pfh-element-archive.php', 'pfh-archive.css' ],
	'PFH_Element_Shophead'       => [ 'class-pfh-element-shophead.php', 'pfh-shop.css' ],
	'PFH_Element_Shopdesc'       => [ 'class-pfh-element-shopdesc.php', 'pfh-shop.css' ],
	'PFH_Element_Notice'         => [ 'class-pfh-element-notice.php', 'pfh-shop.css' ],
	'PFH_Element_Counter'        => [ 'class-pfh-element-counter.php', 'pfh-shop.css' ],
	'PFH_Element_Faq'            => [ 'class-pfh-element-faq.php', 'pfh-shop.css' ],
];

foreach ( $elements as $class => $meta ) {
	require_once $dir . 'elements/' . $meta[0];
}

/** Tokens a stylesheet sets inside any @media block. */
function media_tokens( $css ) {
	$out = [];
	$len = strlen( $css );

	for ( $i = 0; $i < $len; $i++ ) {
		if ( 0 !== substr_compare( $css, '@media', $i, 6 ) ) {
			continue;
		}

		$brace = strpos( $css, '{', $i );
		if ( false === $brace ) {
			break;
		}

		$depth = 0;
		for ( $j = $brace; $j < $len; $j++ ) {
			if ( '{' === $css[ $j ] ) { $depth++; }
			if ( '}' === $css[ $j ] ) { $depth--; if ( 0 === $depth ) { break; } }
		}

		$body = substr( $css, $brace, $j - $brace );
		if ( preg_match_all( '/(--[a-z0-9-]+)\s*:/i', $body, $m ) ) {
			foreach ( $m[1] as $t ) { $out[ $t ] = true; }
		}

		$i = $j;
	}

	return array_keys( $out );
}

$total    = 0;
$problems = [];
$css_all  = '';

foreach ( glob( $dir . 'assets/css/*.css' ) as $file ) {
	$css_all .= file_get_contents( $file ) . "\n";
}

$inline_by_element = [];

foreach ( $elements as $class => $meta ) {
	$el = new $class( [ 'id' => 'aud' ] );
	$el->set_control_groups();
	$el->set_controls();

	$groups = array_keys( (array) $el->control_groups );

	foreach ( $el->controls as $key => $control ) {
		$total++;

		if ( empty( $control['tab'] ) ) {
			$problems[] = "$class::$key missing 'tab'";
		}
		if ( empty( $control['type'] ) ) {
			$problems[] = "$class::$key missing 'type'";
		}
		if ( ! empty( $control['group'] ) && ! in_array( $control['group'], $groups, true ) ) {
			$problems[] = "$class::$key unknown group '{$control['group']}'";
		}
		if ( isset( $control['type'] ) && 'repeater' === $control['type'] && empty( $control['fields'] ) ) {
			$problems[] = "$class::$key repeater without fields";
		}
	}

	// Inline tokens this element writes.
	$src = file_get_contents( $dir . 'elements/' . $meta[0] );
	preg_match_all( "/'(--[a-z0-9-]+)'\s*=>/i", $src, $m );
	$inline_by_element[ $class ] = [ 'tokens' => array_unique( $m[1] ), 'css' => $meta[1] ];
}

echo "controls: $total across " . count( $elements ) . " elements\n";
echo "problems: " . ( $problems ? count( $problems ) : 'none' ) . "\n";
foreach ( $problems as $p ) { echo "  - $p\n"; }

// 2. dead media overrides
$dead = [];
foreach ( $inline_by_element as $class => $info ) {
	$path = $dir . 'assets/css/' . $info['css'];
	if ( ! file_exists( $path ) ) { continue; }

	$mt = media_tokens( file_get_contents( $path ) );

	foreach ( $info['tokens'] as $token ) {
		if ( in_array( $token, $mt, true ) ) {
			$dead[] = "$class: $token is written inline AND overridden in {$info['css']} — the inline value wins";
		}
	}
}
echo "dead media overrides: " . ( $dead ? count( $dead ) : 'none' ) . "\n";
foreach ( array_unique( $dead ) as $d ) { echo "  - $d\n"; }

// 3. unread -set tokens
$unread = [];
foreach ( $inline_by_element as $class => $info ) {
	foreach ( $info['tokens'] as $token ) {
		if ( '-set' !== substr( $token, -4 ) ) { continue; }
		if ( false === strpos( $css_all, 'var(' . $token ) ) {
			$unread[] = "$class: $token is emitted but never read by any stylesheet";
		}
	}
}
echo "unread -set tokens: " . ( $unread ? count( $unread ) : 'none' ) . "\n";
foreach ( array_unique( $unread ) as $u ) { echo "  - $u\n"; }

/* ---------------------------------------------------------------------------
 * 4. Style faults a browser only shows you once the widget is on a page with
 *    something else. Each of these shipped at least once.
 * ------------------------------------------------------------------------ */
$style = [];

foreach ( glob( $dir . 'assets/css/pfh-*.css' ) as $path ) {
	$name = basename( $path );

	if ( 'pfh-admin.css' === $name ) { continue; }

	$css = file_get_contents( $path );

	// (a) `font-family: inherit` on a widget root. These stylesheets load
	// after pfh-base and match with the same one-class specificity, so
	// inherit wins and the widget renders in the theme's font.
	if ( preg_match_all( '/^\.(pfh-[a-z-]+)\s*\{([^}]*)\}/m', $css, $blocks, PREG_SET_ORDER ) ) {
		foreach ( $blocks as $b ) {
			if ( preg_match( '/font-family:\s*inherit/', $b[2] ) ) {
				$style[] = "$name: .{$b[1]} sets font-family:inherit — use var(--pfh-font) or the theme's font wins";
			}
		}
	}

	// (b) aspect-ratio with min-height and no width cap resolves a width of
	// its own and grows out of any narrower container.
	if ( preg_match_all( '/([^{}\n]+)\{([^{}]*)\}/', $css, $blocks, PREG_SET_ORDER ) ) {
		foreach ( $blocks as $b ) {
			$body = $b[2];

			// `aspect-ratio: auto` switches the ratio off and `min-height: 0`
			// removes the floor, so neither can force a width.
			if ( ! preg_match( '/aspect-ratio:\s*(?!auto)\S/', $body ) ) { continue; }
			if ( ! preg_match( '/min-height:\s*(?!0\s*[;}])\S/', $body ) ) { continue; }

			if ( ! preg_match( '/\b(?:max-)?width\s*:/', $body ) ) {
				$sel = trim( preg_replace( '/\s+/', ' ', $b[1] ) );
				$style[] = "$name: $sel has aspect-ratio + min-height but no width cap — it will overflow a narrow container";
			}
		}
	}

	/*
	 * A third check lived here: "does this selector start on a class the
	 * stylesheet owns". It flagged 269 selectors, nearly all of them
	 * legitimate root modifiers (.pfh-cart-br, .pfh-reveal-up, .pfh-bp-991),
	 * because ownership cannot be read off a selector — only off which
	 * element emits the class. Cross-widget leaks are checked in the
	 * browser instead, where the answer is observable:
	 * dev/wp-testbed/style-scan.js.
	 */
}

// (d) Every front-end stylesheet must reach the base layer, or .pfh-scope
// has no tokens and no resets.
$php_all = '';
foreach ( array_merge( glob( $dir . 'includes/*.php' ), glob( $dir . 'elements/*.php' ) ) as $f ) {
	$php_all .= file_get_contents( $f );
}

if ( preg_match_all( "/wp_enqueue_style\(\s*'(pfh-[a-z-]+)'\s*,\s*PFH_WIDGETS_URL[^,]+,\s*\[([^\]]*)\]/", $php_all, $m, PREG_SET_ORDER ) ) {
	foreach ( $m as $hit ) {
		// pfh-admin styles the settings screen, which has no .pfh-scope.
		if ( 'pfh-base' === $hit[1] || 'pfh-admin' === $hit[1] ) { continue; }

		if ( false === strpos( $hit[2], 'pfh-base' ) ) {
			$style[] = "{$hit[1]} is enqueued inline without pfh-base as a dependency — its .pfh-scope tokens will be undefined";
		}
	}
}

echo "style faults: " . ( $style ? count( $style ) : 'none' ) . "\n";
foreach ( array_unique( $style ) as $s2 ) { echo "  - $s2\n"; }

/* ---------------------------------------------------------------------------
 * 5. Will it install?
 *
 * A plugin that cannot be uploaded is not a plugin. WordPress reports a zip
 * bigger than the server's upload limit as "Incompatible Archive." — which
 * says nothing about the size, and sent a release back once. Hosts commonly
 * cap uploads at 1M or 2M, so this keeps the payload well inside the smaller
 * of the two, and names the file responsible when it does not.
 * ------------------------------------------------------------------------ */
$budget = 900 * 1024;   // comfortably inside a 1M limit
$weight = [];
$total  = 0;

$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( rtrim( $dir, '/' ), FilesystemIterator::SKIP_DOTS ) );

foreach ( $it as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}

	$size    = $file->getSize();
	$total  += $size;
	$weight[ str_replace( $dir, '', $file->getPathname() ) ] = $size;
}

arsort( $weight );

/*
 * The zip is roughly two thirds of the tree, because the PHP and CSS compress
 * hard while the images barely move. Estimating from the images alone keeps
 * this honest without having to build an archive to check.
 */
$images = 0;
foreach ( $weight as $path => $size ) {
	if ( preg_match( '/\.(png|jpe?g|gif|webp)$/i', $path ) ) {
		$images += $size;
	}
}

$estimate = (int) ( $images + ( ( $total - $images ) * 0.28 ) );
$over     = $estimate > $budget;

echo 'packaged size: ~' . round( $estimate / 1024 ) . 'K of a ' . round( $budget / 1024 ) . "K budget"
	. ( $over ? '   <-- OVER, it may not upload' : '' ) . "\n";

if ( $over ) {
	echo "  heaviest files:\n";
	foreach ( array_slice( $weight, 0, 5, true ) as $path => $size ) {
		echo '  - ' . $path . ' (' . round( $size / 1024 ) . "K)\n";
	}
}

exit( ( $problems || $dead || $unread || $style || $over ) ? 1 : 0 );
