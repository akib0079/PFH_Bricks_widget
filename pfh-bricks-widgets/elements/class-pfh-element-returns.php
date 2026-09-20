<?php
/**
 * Bricks element: the returns policy.
 *
 * The page itself is PFH_Element_Policy; this is the shop's own words. The
 * first section carries the button to the returns portal, because that is
 * what most people came to this page to find.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-pfh-element-policy.php';

class PFH_Element_Returns extends PFH_Element_Policy {

	public $name = 'pfh-returns';
	public $icon = 'ti-back-left';

	public function get_label() {
		return esc_html__( 'PFH Returns', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'returns', 'retour', 'retourneren', 'defect', 'herroeping', 'pfh' ];
	}

	protected function copy() {
		return [
			'eyebrow'  => 'Retourneren',
			'title'    => 'Retourbeleid <em>Products for Home</em>',
			'lede'     => 'Je hebt het recht om je bestelling tot 14 dagen na ontvangst zonder opgave van reden te annuleren. Hieronder lees je hoe dat werkt, welke uitzonderingen er zijn en wat je doet als er iets mis is met je bestelling.',
			'updated'  => '',
			'numbered' => false,
			'sections' => [
				[
					'title' => 'Herroepingsrecht',
					'text'  => '<p>Je hebt het recht om je bestelling tot 14 dagen na ontvangst zonder opgave van reden te annuleren. Let op: producten waarvan de verzegeling is verbroken kunnen niet worden geretourneerd — lees hieronder over de uitzonderingen.</p>'
						. '<p>Na annulering heb je nogmaals 14 dagen de tijd om je product retour te sturen.</p>',
					'link'  => 'Retour starten',
					'url'   => 'https://productsforhome.tracewise.nl/',
				],
				[
					'title' => 'Uitzonderingen op het herroepingsrecht',
					'text'  => '<p>Voor bepaalde producten geldt dat deze niet geretourneerd kunnen worden. Dit geldt in het bijzonder voor:</p>'
						. '<ul><li>Voedingsmiddelen en dranken waarvan de verzegeling is verbroken</li>'
						. '<li>Producten die om hygiënische of gezondheidsredenen niet geschikt zijn om te worden teruggezonden en waarvan de verzegeling is verbroken</li></ul>'
						. '<p>Deze producten zijn uitgesloten van het herroepingsrecht conform de geldende wetgeving.</p>',
				],
				[
					'title' => 'Retourkosten',
					'text'  => '<p>De kosten voor het retourneren van je bestelling zijn voor eigen rekening.</p>',
				],
				[
					'title' => 'Voorwaarden voor retourneren',
					'text'  => '<p>Wanneer je gebruikmaakt van je herroepingsrecht, vragen wij je het product:</p>'
						. '<ul><li>Compleet te retourneren, inclusief alle geleverde toebehoren</li>'
						. '<li>Indien redelijkerwijs mogelijk in de originele staat</li>'
						. '<li>In de originele verpakking</li></ul>'
						. '<blockquote><p>Ga zorgvuldig om met het product en de verpakking. Indien het product of de verpakking meer beschadigd is dan nodig om het artikel te beoordelen, kunnen wij de waardevermindering in rekening brengen.</p></blockquote>',
				],
				[
					'title' => 'Niet afgehaalde of geweigerde pakketten',
					'text'  => '<p>Indien een bestelling niet wordt afgehaald bij een pakketpunt of wordt geweigerd bij levering, wordt deze automatisch naar ons geretourneerd.</p>'
						. '<p>In dat geval behouden wij ons het recht voor om de gemaakte verzend- en retourkosten in mindering te brengen op het terug te betalen bedrag. Het resterende bedrag wordt na ontvangst van de retourzending aan je terugbetaald.</p>',
				],
				[
					'title' => 'Terugbetaling',
					'text'  => '<p>Na ontvangst en controle van de retourzending ontvang je het aankoopbedrag exclusief retourverzendkosten terug. Wij verwerken de terugbetaling binnen 14 dagen na aanmelding van de retour.</p>'
						. '<p>Wij mogen echter wachten met terugbetaling totdat wij het product hebben ontvangen of totdat je hebt aangetoond dat het product is teruggestuurd. De terugbetaling gebeurt via dezelfde betaalmethode als waarmee de bestelling is geplaatst.</p>',
				],
				[
					'title' => 'Retourvoorwaarden',
					'text'  => '<ul><li>Retouren dienen binnen 14 dagen na ontvangst te worden aangemeld</li>'
						. '<li>Na aanmelding heb je nogmaals 14 dagen om het product te retourneren</li>'
						. '<li>Retourkosten zijn voor eigen rekening</li>'
						. '<li>Verzendrisico van de retourzending ligt bij de klant</li>'
						. '<li>Retourzendingen worden alleen geaccepteerd indien deze in originele staat zijn</li></ul>'
						. '<p>Beschadigde of gebruikte artikelen kunnen worden geweigerd of er kan waardevermindering worden toegepast. Wij behouden ons het recht voor om retourzendingen te weigeren of kosten in rekening te brengen indien niet aan bovenstaande voorwaarden wordt voldaan.</p>',
				],
				[
					'title' => 'Verkeerd of incompleet geleverd',
					'text'  => '<p>Klopt je bestelling niet? Neem dan zo snel mogelijk contact met ons op en vermeld daarbij je naam, je bestelnummer en de betreffende artikelen. Wij zorgen voor een passende oplossing.</p>',
					'chips' => 'klantenservice@productsforhome.nl',
				],
				[
					'title' => 'Ruilen',
					'text'  => '<p>Het is niet mogelijk om artikelen direct te ruilen. Wil je een ander product ontvangen? Plaats dan eenvoudig een nieuwe bestelling en retourneer het ongewenste artikel.</p>',
				],
				[
					'title' => 'Defect ontvangen product',
					'text'  => '<p>Heb je een defect product ontvangen? Neem dan zo snel mogelijk contact met ons op en vermeld:</p>'
						. '<ul><li>Je bestelnummer</li>'
						. '<li>Een omschrijving van het defect</li>'
						. '<li>Duidelijk fotobewijs</li></ul>',
					'chips' => 'klantenservice@productsforhome.nl',
				],
				[
					'title' => 'Voorwaarden defectmelding',
					'text'  => '<p>Wij adviseren om defecten binnen 3 dagen na ontvangst te melden, zodat wij je snel kunnen helpen. De regeling geldt voor:</p>'
						. '<ul><li>Transportschade</li>'
						. '<li>Fabricagefouten</li></ul>'
						. '<p>Schade door eigen gebruik valt hier niet onder.</p>',
				],
			],
		];
	}
}
