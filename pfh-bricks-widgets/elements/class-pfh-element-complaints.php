<?php
/**
 * Bricks element: complaints.
 *
 * The shortest page on the shop, and the one people reach when something has
 * already gone wrong — so it says what to do first, and what happens if that
 * does not settle it. The three steps are the shop's own sentence, set out as
 * steps; the words are not changed.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-pfh-element-policy.php';

class PFH_Element_Complaints extends PFH_Element_Policy {

	public $name = 'pfh-complaints';
	public $icon = 'ti-comment-alt';

	public function get_label() {
		return esc_html__( 'PFH Complaints', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'complaints', 'klachten', 'geschil', 'webwinkelkeur', 'pfh' ];
	}

	protected function copy() {
		return [
			'eyebrow'  => 'Klantenservice',
			'title'    => 'Een <em>klacht</em> doorgeven',
			'lede'     => 'Het kan altijd voorkomen dat er iets niet helemaal gaat zoals gepland. We raden u aan om klachten eerst bij ons kenbaar te maken door te mailen naar klantenservice@productsforhome.nl.',
			'updated'  => '',
			'numbered' => false,
			'sections' => [
				[
					'title' => 'Laat het ons eerst weten',
					'text'  => '<p>Mail ons met je bestelnummer en een korte omschrijving van wat er niet klopt. Wij kijken het na en komen zo snel mogelijk bij je terug met een oplossing.</p>',
					'chips' => "klantenservice@productsforhome.nl\n0617392302",
					'link'  => 'Mail de klantenservice',
					'url'   => 'mailto:klantenservice@productsforhome.nl',
					'tone'  => 'highlight',
				],
				[
					'title' => 'Komen we er samen niet uit?',
					'text'  => '<p>Leidt dit niet tot een oplossing, dan is het mogelijk om uw geschil aan te melden voor bemiddeling via WebwinkelKeur. Die bemiddeling is gratis.</p>',
					'link'  => 'Geschil aanmelden bij WebwinkelKeur',
					'url'   => 'https://www.webwinkelkeur.nl/kennisbank/consumenten/geschil',
				],
				[
					'title' => 'Wat er daarna gebeurt',
					'text'  => '<p>Komen we er ook met bemiddeling niet uit, dan kan de klacht worden voorgelegd aan de onafhankelijke geschillencommissie die door WebwinkelKeur is aangesteld. Die uitspraak is bindend voor zowel ons als voor jou. Aan het voorleggen van een geschil aan deze commissie zijn kosten verbonden, die door de consument aan de betreffende commissie betaald dienen te worden.</p>'
						. '<p>Je kunt via <a href="https://www.webwinkelkeur.nl/leden/" target="_blank" rel="noopener noreferrer">www.webwinkelkeur.nl/leden</a> controleren of onze webwinkel een lopend lidmaatschap heeft.</p>',
				],
			],
		];
	}
}
