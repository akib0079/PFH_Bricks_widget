<?php
/**
 * Bricks element: the privacy statement.
 *
 * The one page in this family whose words the shop did not hand over. What is
 * here is built from what the shop already states elsewhere — the terms say
 * what is done with an order, the shipping page names the carrier, the
 * account page names the returns portal — plus the rights the AVG gives
 * everyone, which are the law's words and not ours.
 *
 * It is therefore a draft. The panel says so, in the panel, where whoever
 * edits the page will actually read it: the retention periods and the list of
 * processors have to be confirmed by the shop before this is published.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-pfh-element-policy.php';

class PFH_Element_Privacy extends PFH_Element_Policy {

	public $name = 'pfh-privacy';
	public $icon = 'ti-lock';

	public function get_label() {
		return esc_html__( 'PFH Privacy', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'privacy', 'avg', 'gdpr', 'privacybeleid', 'cookies', 'pfh' ];
	}

	public function set_controls() {
		parent::set_controls();

		$this->controls['draft'] = [
			'tab'     => 'content',
			'group'   => 'head',
			'type'    => 'info',
			'content' => esc_html__( 'This text is a draft, written from what the shop states on its other pages. Check the retention periods and the list of parties the data is shared with before this page goes live.', 'pfh-widgets' ),
		];
	}

	protected function copy() {
		return [
			'eyebrow'  => 'Privacy',
			'title'    => 'Privacybeleid <em>Products for Home</em>',
			'lede'     => 'Products for Home verwerkt persoonsgegevens van iedereen die bij ons bestelt of contact met ons opneemt. In dit privacybeleid lees je welke gegevens dat zijn, waarom wij ze nodig hebben, hoe lang wij ze bewaren en welke rechten je daarbij hebt.',
			'updated'  => '',
			'numbered' => true,
			'sections' => [
				[
					'title' => 'Wie verwerkt je gegevens',
					'text'  => '<p>Products for Home is verantwoordelijk voor de verwerking van je persoonsgegevens zoals beschreven in dit privacybeleid. Je kunt ons bereiken via onderstaande gegevens.</p>'
						. '<ul><li>Products for Home, Nikkelweg 22, 2401 MM Alphen aan den Rijn</li>'
						. '<li>KVK 78571936</li>'
						. '<li>BTW NL003349641B31</li></ul>',
					'chips' => "klantenservice@productsforhome.nl\n0617392302",
				],
				[
					'title' => 'Welke gegevens wij verwerken',
					'text'  => '<p>Wij verwerken alleen de gegevens die wij nodig hebben om je bestelling uit te voeren en om je te kunnen helpen:</p>'
						. '<ul><li>Naam, adres, woonplaats en land</li>'
						. '<li>E-mailadres en telefoonnummer</li>'
						. '<li>Gegevens over je bestelling, je betaling en je bezorging</li>'
						. '<li>De inhoud van je berichten aan onze klantenservice</li>'
						. '<li>Gegevens van je account, als je er een aanmaakt</li>'
						. '<li>Technische gegevens van je bezoek aan de website, zoals je IP-adres en de pagina’s die je bekijkt</li></ul>'
						. '<blockquote><p>Je betaalgegevens komen nooit bij ons binnen. Die gaan rechtstreeks naar de betaaldienst waarmee je betaalt.</p></blockquote>',
				],
				[
					'title' => 'Waarvoor wij je gegevens gebruiken',
					'text'  => '<p>Wij gebruiken je gegevens voor de volgende doelen:</p>'
						. '<ul><li>Het uitvoeren, bezorgen en factureren van je bestelling</li>'
						. '<li>Het afhandelen van je betaling</li>'
						. '<li>Het beantwoorden van je vragen en het afhandelen van retouren en klachten</li>'
						. '<li>Het versturen van een uitnodiging om een beoordeling achter te laten over de producten die je hebt gekocht</li>'
						. '<li>Het versturen van onze nieuwsbrief, als je daar toestemming voor hebt gegeven</li>'
						. '<li>Het voldoen aan onze wettelijke verplichtingen, zoals de fiscale bewaarplicht</li>'
						. '<li>Het verbeteren en beveiligen van onze webshop</li></ul>',
				],
				[
					'title' => 'Op welke grond wij dat doen',
					'text'  => '<p>De AVG vraagt ons te benoemen waarop een verwerking berust. Voor ons zijn dat er vier:</p>'
						. '<ul><li><strong>De overeenkomst</strong> — zonder je naam en adres kunnen wij je bestelling niet bezorgen.</li>'
						. '<li><strong>Een wettelijke plicht</strong> — onze administratie moeten wij bewaren.</li>'
						. '<li><strong>Een gerechtvaardigd belang</strong> — bijvoorbeeld het tegengaan van misbruik van de webshop.</li>'
						. '<li><strong>Je toestemming</strong> — voor de nieuwsbrief en voor cookies die niet noodzakelijk zijn. Die toestemming kun je altijd weer intrekken.</li></ul>',
				],
				[
					'title' => 'Met wie wij je gegevens delen',
					'text'  => '<p>Wij verkopen je gegevens niet. Wij delen ze alleen met partijen die nodig zijn om je bestelling uit te voeren, en niet meer dan daarvoor nodig is:</p>'
						. '<ul><li>De bezorgdienst die je pakket bij je bezorgt</li>'
						. '<li>De betaaldienst waarmee je betaalt</li>'
						. '<li>Het platform waarmee wij je uitnodigen een beoordeling achter te laten</li>'
						. '<li>Het portaal waarmee je een retour aanmeldt</li>'
						. '<li>Onze hosting- en e-mailpartij, en onze boekhouding</li></ul>'
						. '<p>Met partijen die namens ons gegevens verwerken sluiten wij een verwerkersovereenkomst. Als de wet ons daartoe verplicht, kunnen wij gegevens ook aan een overheidsinstantie moeten verstrekken.</p>',
				],
				[
					'title' => 'Hoe lang wij je gegevens bewaren',
					'text'  => '<p>Wij bewaren je gegevens niet langer dan nodig is voor het doel waarvoor wij ze hebben gekregen.</p>'
						. '<ul><li>Gegevens die bij onze administratie horen, zoals facturen: zeven jaar, omdat de Belastingdienst dat van ons vraagt.</li>'
						. '<li>De gegevens van je account: zolang je het account houdt.</li>'
						. '<li>Berichten aan de klantenservice: tot je vraag is afgehandeld en de bijbehorende garantie- en retourtermijn voorbij is.</li>'
						. '<li>Je inschrijving op de nieuwsbrief: tot je je afmeldt.</li></ul>',
				],
				[
					'title' => 'Cookies',
					'text'  => '<p>Onze webshop gebruikt cookies. Noodzakelijke cookies zorgen ervoor dat de webshop werkt — je winkelmandje onthouden, bijvoorbeeld — en die kunnen niet worden uitgezet. Voor cookies die je bezoek meten of die voor marketing worden gebruikt vragen wij eerst je toestemming. Die keuze kun je altijd weer aanpassen.</p>'
						. '<p>Je kunt cookies ook zelf verwijderen of blokkeren via de instellingen van je browser. Sommige onderdelen van de webshop werken dan mogelijk niet meer goed.</p>',
				],
				[
					'title' => 'Hoe wij je gegevens beveiligen',
					'text'  => '<p>Wij nemen passende technische en organisatorische maatregelen om je gegevens te beschermen tegen verlies en tegen ongeoorloofde toegang. Onze webshop verstuurt gegevens over een beveiligde verbinding, en alleen medewerkers die dat voor hun werk nodig hebben kunnen bij je gegevens.</p>'
						. '<p>Heb je het idee dat je gegevens toch niet goed beveiligd zijn, of zijn er aanwijzingen van misbruik? Neem dan contact op met onze klantenservice.</p>',
				],
				[
					'title' => 'Je rechten',
					'text'  => '<p>Je hebt het recht om:</p>'
						. '<ul><li>Je gegevens in te zien</li>'
						. '<li>Je gegevens te laten corrigeren of aanvullen</li>'
						. '<li>Je gegevens te laten verwijderen</li>'
						. '<li>De verwerking te laten beperken</li>'
						. '<li>Bezwaar te maken tegen de verwerking</li>'
						. '<li>Je gegevens in een gangbaar bestand mee te krijgen</li>'
						. '<li>Een gegeven toestemming weer in te trekken</li></ul>'
						. '<p>Stuur je verzoek naar onze klantenservice. Wij reageren binnen vier weken. Om er zeker van te zijn dat het verzoek van jou komt, kunnen wij je vragen je te legitimeren.</p>',
					'chips' => 'klantenservice@productsforhome.nl',
				],
				[
					'title' => 'Een klacht over privacy',
					'text'  => '<p>Ben je het niet eens met hoe wij met je gegevens omgaan, laat het ons dan eerst weten — dan lossen wij het graag samen op. Je hebt daarnaast altijd het recht een klacht in te dienen bij de Autoriteit Persoonsgegevens.</p>',
					'link'  => 'Naar de Autoriteit Persoonsgegevens',
					'url'   => 'https://autoriteitpersoonsgegevens.nl/',
					'tone'  => 'highlight',
				],
			],
		];
	}
}
