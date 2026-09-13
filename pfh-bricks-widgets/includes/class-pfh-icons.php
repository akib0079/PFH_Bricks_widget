<?php
/**
 * Inline SVG icon set.
 *
 * These are fallbacks for the header action icons (the client supplies real
 * SVG uploads for search / cart / account) plus the UI + social glyphs.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

class PFH_Widgets_Icons {

	/**
	 * Return an inline SVG string.
	 *
	 * @param string $name  Icon slug.
	 * @param string $class Extra class for the <svg>.
	 * @return string
	 */
	public static function get( $name, $class = '' ) {
		$icons = self::all();

		if ( ! isset( $icons[ $name ] ) ) {
			return '';
		}

		$boxes = self::boxes();
		$box   = isset( $boxes[ $name ] ) ? $boxes[ $name ] : '0 0 24 24';

		/*
		 * fill="none" on the root, because SVG's default fill is black.
		 * Every shape below declares its own fill or stroke, so this changes
		 * nothing for them — but a supplied icon drawn with strokes alone,
		 * as the basket, the filter and the FAQ chevrons all were, otherwise
		 * arrives as a solid black blob instead of a line drawing.
		 */
		$attrs = 'xmlns="http://www.w3.org/2000/svg" viewBox="' . esc_attr( $box ) . '" fill="none" aria-hidden="true" focusable="false"';

		if ( $class ) {
			$attrs .= ' class="' . esc_attr( $class ) . '"';
		}

		return '<svg ' . $attrs . '>' . $icons[ $name ] . '</svg>';
	}

	/**
	 * Icons drawn on a grid other than 24x24.
	 *
	 * @return array<string, string>
	 */
	private static function boxes() {
		return [
			'basket-add' => '0 0 10 10',
			'filter'     => '0 0 12 12',
			'faq-open'   => '0 0 14 8',
			'faq-close'  => '0 0 14 8',
		];
	}

	/**
	 * Social icon slugs offered in the footer repeater.
	 *
	 * @return array<string, string>
	 */
	public static function social_options() {
		return [
			'facebook'  => esc_html__( 'Facebook', 'pfh-widgets' ),
			'instagram' => esc_html__( 'Instagram', 'pfh-widgets' ),
			'x'         => esc_html__( 'X / Twitter', 'pfh-widgets' ),
			'youtube'   => esc_html__( 'YouTube', 'pfh-widgets' ),
			'linkedin'  => esc_html__( 'LinkedIn', 'pfh-widgets' ),
			'tiktok'    => esc_html__( 'TikTok', 'pfh-widgets' ),
			'pinterest' => esc_html__( 'Pinterest', 'pfh-widgets' ),
			'whatsapp'  => esc_html__( 'WhatsApp', 'pfh-widgets' ),
		];
	}

	/**
	 * The raw icon bodies.
	 *
	 * @return array<string, string>
	 */
	private static function all() {
		$stroke = 'fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';

		return [
			// UI.
			'search'    => '<circle cx="11" cy="11" r="7" ' . $stroke . '/><path d="m20 20-3.5-3.5" ' . $stroke . '/>',
			'cart'      => '<path d="M6 7h12l-1.2 11.1a2 2 0 0 1-2 1.9H9.2a2 2 0 0 1-2-1.9L6 7Z" ' . $stroke . '/><path d="M9.5 9V6.5a2.5 2.5 0 0 1 5 0V9" ' . $stroke . '/>',
			'account'   => '<circle cx="12" cy="8.5" r="3.5" ' . $stroke . '/><path d="M4.8 20a7.2 7.2 0 0 1 14.4 0" ' . $stroke . '/>',
			'chevron'   => '<path d="m6 9 6 6 6-6" ' . $stroke . '/>',
			'arrow'     => '<path d="M4 12h16m0 0-6-6m6 6-6 6" ' . $stroke . '/>',
			'close'     => '<path d="m6 6 12 12M18 6 6 18" ' . $stroke . '/>',
			'burger'    => '<path d="M4 7h16M4 12h16M4 17h16" ' . $stroke . '/>',
			'minus'     => '<path d="M5 12h14" ' . $stroke . '/>',
			'plus'      => '<path d="M12 5v14M5 12h14" ' . $stroke . '/>',
			'trash'     => '<path d="M4 7h16M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7m2.5 0-.7 12a1.8 1.8 0 0 1-1.8 1.7H9a1.8 1.8 0 0 1-1.8-1.7L6.5 7" ' . $stroke . '/>',
			'check'     => '<path d="m5 13 4.5 4.5L19 7" ' . $stroke . '/>',
			'arrow-ne'  => '<path d="M7 17 17 7m0 0H8m9 0v9" ' . $stroke . '/>',
			// Supplied by the client for the shop template.
			'basket-add' => '<path d="M5.41797 7.50004H8.7513M7.08464 9.16671V5.83337" stroke="currentColor" stroke-width="0.751785" stroke-linecap="round"/><path d="M2.91797 3.12504V2.91671C2.91797 1.76612 3.85071 0.833374 5.0013 0.833374C6.15189 0.833374 7.08464 1.76612 7.08464 2.91671V3.12504" stroke="currentColor" stroke-width="0.751785"/><path d="M4.16667 9.16667C3.21666 9.16662 2.73785 9.16321 2.40337 8.86467C2.06501 8.56271 1.97957 8.05267 1.80869 7.03254L1.32411 4.13982C1.24529 3.66929 1.20588 3.43403 1.32724 3.27951C1.44859 3.125 1.67278 3.125 2.12116 3.125H7.87883C8.32721 3.125 8.55142 3.125 8.67275 3.27951C8.79413 3.43403 8.75471 3.66929 8.67588 4.13982L8.53179 5" stroke="currentColor" stroke-width="0.751785" stroke-linecap="round"/><path d="M1.875 7.29163H4.16667" stroke="currentColor" stroke-width="0.751785" stroke-linecap="round"/>',
			'faq-open'   => '<path d="M0.750049 0.75006C0.750049 0.75006 5.16895 6.75005 6.75005 6.75006C8.33115 6.75007 12.75 0.750061 12.75 0.750061" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
			'faq-close'  => '<path d="M0.750049 6.75C0.750049 6.75 5.16895 0.750011 6.75005 0.750001C8.33115 0.74999 12.75 6.75 12.75 6.75" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
			'filter'     => '<path d="M3.5 10.5V9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/><path d="M8.5 10.5V7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/><path d="M8.5 3V1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.5 4.5V1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.5 9C3.03406 9 2.80109 9 2.61732 8.9239C2.37229 8.8224 2.17761 8.6277 2.07612 8.3827C2 8.1989 2 7.96595 2 7.5C2 7.03405 2 6.8011 2.07612 6.6173C2.17761 6.3723 2.37229 6.1776 2.61732 6.0761C2.80109 6 3.03406 6 3.5 6C3.96594 6 4.19891 6 4.38269 6.0761C4.62771 6.1776 4.82239 6.3723 4.92388 6.6173C5 6.8011 5 7.03405 5 7.5C5 7.96595 5 8.1989 4.92388 8.3827C4.82239 8.6277 4.62771 8.8224 4.38269 8.9239C4.19891 9 3.96594 9 3.5 9Z" stroke="currentColor"/><path d="M8.5 6C8.03405 6 7.8011 6 7.6173 5.9239C7.3723 5.8224 7.1776 5.6277 7.0761 5.3827C7 5.1989 7 4.96594 7 4.5C7 4.03406 7 3.80109 7.0761 3.61732C7.1776 3.37229 7.3723 3.17761 7.6173 3.07612C7.8011 3 8.03405 3 8.5 3C8.96595 3 9.1989 3 9.3827 3.07612C9.6277 3.17761 9.8224 3.37229 9.9239 3.61732C10 3.80109 10 4.03406 10 4.5C10 4.96594 10 5.1989 9.9239 5.3827C9.8224 5.6277 9.6277 5.8224 9.3827 5.9239C9.1989 6 8.96595 6 8.5 6Z" stroke="currentColor"/>',

			'star'      => '<path fill="currentColor" d="M12 1.8l3.09 6.26 6.91 1-5 4.87 1.18 6.87L12 17.56l-6.18 3.25L7 13.94l-5-4.87 6.91-1L12 1.8Z"/>',

			// Social.
			'facebook'  => '<path fill="currentColor" d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.55-1.5h1.65V3.63A22 22 0 0 0 14.29 3.5c-2.38 0-4.01 1.45-4.01 4.12V9.9H7.6V13h2.68v8h3.22Z"/>',
			'instagram' => '<path fill="currentColor" d="M12 3.8c2.67 0 2.99.01 4.04.06.98.04 1.5.2 1.86.34.47.18.8.4 1.15.75.35.35.57.68.75 1.15.14.36.3.88.34 1.86.05 1.05.06 1.37.06 4.04s-.01 2.99-.06 4.04c-.04.98-.2 1.5-.34 1.86-.18.47-.4.8-.75 1.15-.35.35-.68.57-1.15.75-.36.14-.88.3-1.86.34-1.05.05-1.37.06-4.04.06s-2.99-.01-4.04-.06c-.98-.04-1.5-.2-1.86-.34a3.1 3.1 0 0 1-1.15-.75 3.1 3.1 0 0 1-.75-1.15c-.14-.36-.3-.88-.34-1.86-.05-1.05-.06-1.37-.06-4.04s.01-2.99.06-4.04c.04-.98.2-1.5.34-1.86.18-.47.4-.8.75-1.15.35-.35.68-.57 1.15-.75.36-.14.88-.3 1.86-.34C9.01 3.81 9.33 3.8 12 3.8Zm0 4.4a3.8 3.8 0 1 0 0 7.6 3.8 3.8 0 0 0 0-7.6Zm0 6.27a2.47 2.47 0 1 1 0-4.94 2.47 2.47 0 0 1 0 4.94Zm4.84-6.42a.89.89 0 1 1-1.78 0 .89.89 0 0 1 1.78 0Z"/>',
			'x'         => '<path fill="currentColor" d="M17.2 3h3.1l-6.77 7.73L21.5 21h-6.23l-4.88-6.38L4.8 21H1.7l7.24-8.27L2.5 3h6.39l4.41 5.83L17.2 3Zm-1.09 16.15h1.72L7.97 4.76H6.13l9.98 14.39Z"/>',
			'youtube'   => '<path fill="currentColor" d="M21.6 8.02a2.5 2.5 0 0 0-1.76-1.77C18.27 5.83 12 5.83 12 5.83s-6.27 0-7.84.42A2.5 2.5 0 0 0 2.4 8.02 26.1 26.1 0 0 0 2 12a26.1 26.1 0 0 0 .4 3.98 2.5 2.5 0 0 0 1.76 1.77c1.57.42 7.84.42 7.84.42s6.27 0 7.84-.42a2.5 2.5 0 0 0 1.76-1.77A26.1 26.1 0 0 0 22 12a26.1 26.1 0 0 0-.4-3.98ZM10.05 15V9l5.2 3-5.2 3Z"/>',
			'linkedin'  => '<path fill="currentColor" d="M6.94 8.5H3.9V21h3.04V8.5ZM5.42 3a1.77 1.77 0 1 0 0 3.53 1.77 1.77 0 0 0 0-3.53ZM20.1 13.8c0-3.3-1.77-4.84-4.13-4.84-1.9 0-2.75 1.05-3.22 1.78V8.5H9.7c.04.86 0 12.5 0 12.5h3.04v-6.98c0-.27.02-.55.1-.74.22-.55.72-1.11 1.56-1.11 1.1 0 1.54.84 1.54 2.07V21h3.05l.11-7.2Z"/>',
			'tiktok'    => '<path fill="currentColor" d="M16.6 3h-3.1v12.36a2.4 2.4 0 1 1-1.86-2.34V9.83a5.53 5.53 0 1 0 4.96 5.5V9.5a6.6 6.6 0 0 0 3.9 1.26V7.65A3.75 3.75 0 0 1 16.6 3Z"/>',
			'pinterest' => '<path fill="currentColor" d="M12 3a9 9 0 0 0-3.28 17.38c-.08-.73-.15-1.86.03-2.66.16-.72 1.06-4.58 1.06-4.58s-.27-.54-.27-1.34c0-1.26.73-2.2 1.63-2.2.77 0 1.14.58 1.14 1.27 0 .78-.49 1.94-.75 3.02-.21.9.45 1.64 1.34 1.64 1.61 0 2.85-1.7 2.85-4.15 0-2.17-1.56-3.69-3.79-3.69-2.58 0-4.1 1.94-4.1 3.94 0 .78.3 1.62.68 2.07a.27.27 0 0 1 .06.26c-.07.28-.22.9-.25 1.02-.04.17-.13.2-.3.12-1.14-.53-1.85-2.19-1.85-3.52 0-2.87 2.08-5.5 6-5.5 3.15 0 5.6 2.24 5.6 5.24 0 3.13-1.97 5.65-4.71 5.65-.92 0-1.79-.48-2.08-1.05l-.57 2.16c-.2.79-.76 1.78-1.13 2.38A9 9 0 1 0 12 3Z"/>',
			'whatsapp'  => '<path fill="currentColor" d="M12.04 3a8.94 8.94 0 0 0-7.6 13.66L3 21.5l4.96-1.4A8.94 8.94 0 1 0 12.04 3Zm0 1.68a7.26 7.26 0 1 1-3.7 13.5l-.27-.16-2.94.83.8-2.87-.18-.29a7.26 7.26 0 0 1 6.29-11.01Zm4.19 9.2c-.23-.11-1.35-.66-1.56-.74-.21-.08-.36-.11-.51.12-.15.22-.58.73-.71.88-.13.15-.26.17-.49.06a5.94 5.94 0 0 1-2.97-2.6c-.22-.38.22-.35.64-1.18.07-.15.03-.27-.02-.38-.06-.11-.51-1.24-.7-1.7-.19-.44-.38-.38-.52-.39h-.44c-.15 0-.4.06-.6.28-.21.23-.79.77-.79 1.88s.81 2.18.92 2.33c.11.15 1.59 2.44 3.87 3.42 1.44.62 2 .68 2.72.57.44-.07 1.35-.55 1.54-1.09.19-.53.19-.99.13-1.09-.05-.1-.2-.16-.43-.27Z"/>',
		];
	}
}
