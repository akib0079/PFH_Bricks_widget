<?php
/**
 * Bricks element: the terms and conditions.
 *
 * The page itself is PFH_Element_Policy. This is nothing but the shop's own
 * words, so that dropping the element on a page gives the client the document
 * they already have rather than an empty frame to retype it into.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-pfh-element-policy.php';

class PFH_Element_Terms extends PFH_Element_Policy {

	public $name = 'pfh-terms';
	public $icon = 'ti-receipt';

	public function get_label() {
		return esc_html__( 'PFH Terms', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'terms', 'algemene voorwaarden', 'legal', 'pfh' ];
	}

	protected function copy() {
		return [
			'eyebrow'  => 'Voorwaarden',
			'title'    => 'Algemene Voorwaarden <em>Products for Home</em>',
			'lede'     => 'Deze algemene voorwaarden zijn van toepassing op alle aanbiedingen, bestellingen en overeenkomsten die via de webshop van Products for Home (hierna te noemen “wij” of “onze”) worden gesloten. Door een bestelling te plaatsen, ga je akkoord met deze voorwaarden.',
			'updated'  => '',
			'numbered' => true,
			'sections' => [
				[
					'title' => 'Algemeen',
					'text'  => '<p>1.1 Deze algemene voorwaarden zijn van toepassing op alle aanbiedingen, bestellingen en overeenkomsten via onze webshop.</p>'
						. '<p>1.2 Wij behouden ons het recht voor om deze voorwaarden te allen tijde te wijzigen. Wijzigingen worden voorafgaand aan de wijziging op de website gepubliceerd.</p>'
						. '<p>1.3 Alle producten die wij aanbieden, zijn zorgvuldig geselecteerd en voldoen aan de Nederlandse wetgeving en Europese richtlijnen, met name op het gebied van voedselveiligheid en biologische certificering.</p>',
				],
				[
					'title' => 'Productinformatie en prijzen',
					'text'  => '<p>2.1 Wij doen er alles aan om de producten op de webshop zo duidelijk mogelijk te beschrijven, inclusief de prijzen, ingrediënten, herkomst en eventuele certificeringen (zoals biologisch).</p>'
						. '<p>2.2 Alle prijzen zijn inclusief btw, tenzij anders aangegeven, en exclusief verzendkosten.</p>'
						. '<p>2.3 De prijzen en productbeschrijvingen op de website kunnen van tijd tot tijd worden aangepast. Wij behouden ons het recht voor om prijsfouten of wijzigingen van de prijzen door te voeren zonder voorafgaande kennisgeving.</p>'
						. '<p>2.4 Houd er rekening mee dat natuurproducten zoals honing, olijfolie en bijenwasproducten kunnen variëren in kleur, geur en textuur. Dit komt doordat ze afhankelijk zijn van de oogst en natuurlijke variaties. De kleur van het product kan daarom afwijken van de foto op de website.</p>',
				],
				[
					'title' => 'Bestelling en betaling',
					'text'  => '<p>3.1 Je kunt producten bestellen via onze webshop door het product aan je winkelmandje toe te voegen en de stappen voor de bestelling te doorlopen.</p>'
						. '<p>3.2 Betaling dient te geschieden via de beschikbare betaalmethoden op de webshop (iDEAL, Bancontact en PayPal).</p>'
						. '<p>3.3 Het moment van betaling is het moment waarop de overeenkomst tot stand komt. Na ontvangst van betaling wordt de bestelling verwerkt.</p>',
				],
				[
					'title' => 'Levering',
					'text'  => '<p>4.1 Wij streven ernaar om de bestelling die voor 15:00 geplaatst word dezelfde werkdag na ontvangst van betaling te verzenden, tenzij anders vermeld op de productpagina.</p>'
						. '<p>4.2 Levering vindt plaats op het door jou opgegeven adres.</p>'
						. '<p>4.3 De levertijden zijn indicatief en kunnen door onvoorziene omstandigheden variëren. Wij kunnen niet aansprakelijk worden gesteld voor vertragingen buiten onze controle.</p>'
						. '<p>4.4 Wij leveren alleen binnen Nederland en België. Voor leveringen naar andere landen kunnen extra kosten en levertijden van toepassing zijn.</p>',
				],
				[
					'title' => 'Herroepingsrecht',
					'text'  => '<p>5.1 Als consument heb je het recht om je bestelling binnen 14 dagen na ontvangst zonder opgave van redenen te annuleren, conform de Wet Koop op Afstand.</p>'
						. '<p>5.2 Je kunt het herroepingsrecht uitoefenen door ons een duidelijke verklaring te sturen via e-mail.</p>'
						. '<p>5.3 Na annulering dien je de producten binnen 14 dagen naar ons terug te sturen. De kosten van de retourzending zijn voor jouw rekening, tenzij anders overeengekomen.</p>'
						. '<p>5.4 Voor producten die snel kunnen bederven (zoals honing, gia giamas, olijfolie en bijenwasproducten), geldt dat het herroepingsrecht niet van toepassing is, tenzij het product defect of beschadigd is bij levering. Dit geldt ook voor producten die om hygiënische redenen niet geretourneerd kunnen worden zodra de verpakking is geopend.</p>',
				],
				[
					'title' => 'Retourneren en ruilen',
					'text'  => '<p>6.1 Mocht het product beschadigd of de verpakking meer beschadigd zijn dan nodig is om het product te proberen, dan kunnen we deze waardevermindering van het product aan u doorberekenen. Behandel het product dus met zorg en zorg ervoor dat deze bij een retour goed verpakt is.</p>'
						. '<p>6.2 Je dient ons binnen 3 dagen na ontvangst van de bestelling te informeren als het product kapot of beschadigd is geleverd. Na deze periode kunnen wij geen aansprakelijkheid meer nemen voor beschadigingen tijdens het transport.</p>'
						. '<p>6.3 Het retouradres en verdere instructies worden verstrekt na ontvangst van je herroepingsverzoek.</p>'
						. '<p>6.4 Wij zullen het verschuldigde orderbedrag binnen 14 dagen na aanmelding van uw retour terugstorten mits het product reeds in goede orde retour ontvangen is.</p>'
						. '<p>6.5 De kosten voor het retourneren van de producten zijn voor jouw eigen rekening, tenzij anders overeengekomen.</p>'
						. '<p>6.6 Bij klachten dient een consument zich allereerst te wenden tot de ondernemer. Indien de webwinkel is aangesloten bij WebwinkelKeur en bij klachten die niet in onderling overleg opgelost kunnen worden dient de consument zich te wenden tot WebwinkelKeur (<a href="https://www.webwinkelkeur.nl" target="_blank" rel="noopener noreferrer">www.webwinkelkeur.nl</a>), deze zal gratis bemiddelen. Controleer of deze webwinkel een lopend lidmaatschap heeft via <a href="https://www.webwinkelkeur.nl/leden/" target="_blank" rel="noopener noreferrer">www.webwinkelkeur.nl/leden</a>. Mocht er dan nog niet tot een oplossing gekomen worden, heeft de consument de mogelijkheid om zijn klacht te laten behandelen door de door WebwinkelKeur aangestelde onafhankelijke geschillencommissie, de uitspraak hiervan is bindend en zowel ondernemer als consument stemmen in met deze bindende uitspraak. Aan het voorleggen van een geschil aan deze geschillencommissie zijn kosten verbonden die door de consument betaalt dienen te worden aan de betreffende commissie.</p>',
				],
				[
					'title' => 'Aansprakelijkheid',
					'text'  => '<p>7.1 Wij zijn niet aansprakelijk voor enige schade die voortvloeit uit het gebruik van de producten, tenzij deze schade het gevolg is van opzet of grove schuld van onze kant.</p>'
						. '<p>7.2 Onze aansprakelijkheid is in ieder geval beperkt tot het bedrag dat je hebt betaald voor het product.</p>'
						. '<p>7.3 Wij zijn niet verantwoordelijk voor mogelijke allergieën die je kunt hebben voor de ingrediënten in onze producten. Het is jouw verantwoordelijkheid om de productinformatie goed door te lezen en bij twijfel een arts te raadplegen.</p>',
				],
				[
					'title' => 'Privacy en Gegevensverwerking (AVG)',
					'text'  => '<p>8.1 Wij verwerken je persoonsgegevens uitsluitend voor het verwerken van je bestelling en om je op de hoogte te houden van relevante aanbiedingen, tenzij je aangeeft geen marketinginformatie te ontvangen.</p>'
						. '<p>8.2 Wij nemen passende technische en organisatorische maatregelen om je persoonsgegevens te beschermen tegen verlies of ongeoorloofde toegang.</p>'
						. '<p>8.3 Wij verzamelen ook gegevens om je uit te nodigen een beoordeling achter te laten van de producten die je hebt gekocht. Dit gebeurt via een beoordelingsformulier, dat je ontvangt na de levering van je bestelling. Door een bestelling te plaatsen, geef je toestemming voor het ontvangen van deze uitnodiging.</p>'
						. '<p>8.4 Voor meer informatie over hoe wij je persoonsgegevens verwerken, verwijzen wij je naar ons <a href="/privacybeleid/">privacybeleid</a>.</p>',
				],
				[
					'title' => 'Intellectuele Eigendom',
					'text'  => '<p>9.1 Alle rechten met betrekking tot de inhoud van onze website, zoals teksten, afbeeldingen, logo’s en merken, zijn eigendom van Products for Home of de desbetreffende rechthebbenden.</p>'
						. '<p>9.2 Het is verboden om zonder toestemming van Products for Home de inhoud van de website te gebruiken, te reproduceren of te distribueren.</p>',
				],
				[
					'title' => 'Toepasselijk recht en geschillen',
					'text'  => '<p>10.1 Op alle overeenkomsten en transacties via onze webshop is Nederlands recht van toepassing.</p>'
						. '<p>10.2 Geschillen zullen worden voorgelegd aan de bevoegde rechter in Nederland, tenzij een andere juridische regeling van toepassing is op de consument.</p>',
				],
				[
					'title' => 'Klantenservice',
					'text'  => '<p>Voor vragen, klachten of opmerkingen kun je contact met ons opnemen via:</p>',
					'chips' => "klantenservice@productsforhome.nl\n0617392302\nAlphen aan den Rijn",
					'tone'  => 'highlight',
				],
			],
		];
	}
}
