<?php
/**
 * Bricks element: paying and delivery.
 *
 * The page itself is PFH_Element_Policy; this is the shop's own words. Not
 * numbered — the reader of this page is looking for one answer, not reading a
 * contract through — so the contents panel and the headings carry it.
 *
 * @package PFH_Widgets
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-pfh-element-policy.php';

class PFH_Element_Shipping extends PFH_Element_Policy {

	public $name = 'pfh-shipping';
	public $icon = 'ti-truck';

	public function get_label() {
		return esc_html__( 'PFH Paying and delivery', 'pfh-widgets' );
	}

	public function get_keywords() {
		return [ 'shipping', 'payment', 'betalen', 'bezorgen', 'verzending', 'pfh' ];
	}

	protected function copy() {
		return [
			'eyebrow'  => 'Bestellen',
			'title'    => 'Betalen en <em>bezorgen</em>',
			'lede'     => 'Na iedere bestelling ontvang je direct een bevestiging per e-mail. In deze e-mail vind je het ordernummer, de bestelde artikelen, de verzendkosten en het totaalbedrag.',
			'updated'  => '',
			'numbered' => false,
			'sections' => [
				[
					'title' => 'Betaalmethode',
					'text'  => '<p>Bij Products for Home kun je veilig betalen met:</p>',
					'chips' => "iDEAL\nPayPal\nKlarna\nBancontact",
				],
				[
					'title' => 'Betalen via bol',
					'text'  => '<p>Daarnaast nemen wij deel aan een pilot in samenwerking met Bol. Hierbij kun je inloggen met je bol-account en kiezen voor:</p>'
						. '<ul><li>Direct betalen via onze webshop</li><li>Achteraf betalen via bol</li></ul>',
				],
				[
					'title' => 'Is je betaling mislukt?',
					'text'  => '<p>Het kan voorkomen dat er iets misgaat tijdens het betalen, bijvoorbeeld door een foutmelding. Twijfel je of de betaling goed is doorgekomen?</p>'
						. '<ol><li>Controleer eerst je bankafschriften om te zien of het bedrag is afgeschreven.</li>'
						. '<li>Is het bedrag afgeschreven maar heb je geen bevestiging ontvangen? Neem dan gerust contact met ons op — wij kijken het graag voor je na.</li></ol>'
						. '<p>Weet je zeker dat de betaling niet is gelukt? Dan is je bestelling helaas niet geplaatst. Je kunt de bestelling dan opnieuw plaatsen en opnieuw betalen.</p>',
				],
				[
					'title' => 'Verzendwijze en kosten',
					'text'  => '<p>Products for Home verzendt alle bestellingen met DHL.</p>'
						. '<h3>Nederland</h3>'
						. '<ul><li>Bezorging aan huis of servicepunt — € 5,99</li>'
						. '<li>Gratis verzending vanaf € 60,-</li></ul>'
						. '<h3>Waddeneilanden</h3>'
						. '<ul><li>Bezorging aan huis of servicepunt — € 5,99 + € 6,66 onder € 60</li>'
						. '<li>Bezorging aan huis of servicepunt — € 6,65 vanaf € 60</li>'
						. '<li>Gratis verzending vanaf € 100,-</li></ul>'
						. '<h3>België</h3>'
						. '<ul><li>Bezorging aan huis of servicepunt — € 8,30</li>'
						. '<li>Gratis verzending vanaf € 70,-</li></ul>',
				],
				[
					'title' => 'Levertijden',
					'text'  => '<p>Wij doen er alles aan om jouw bestelling zo snel mogelijk te verzenden.</p>'
						. '<ul><li>Bestellingen die vóór 15:00 uur worden geplaatst, worden dezelfde werkdag verzonden.</li>'
						. '<li>Gemiddelde levertijd Nederland: 1 à 2 werkdagen</li>'
						. '<li>Gemiddelde levertijd België: 2 à 3 werkdagen</li></ul>'
						. '<blockquote><p><strong>Let op:</strong> wij zijn afhankelijk van de bezorgdienst. Products for Home is niet aansprakelijk voor eventuele vertragingen bij de vervoerder.</p></blockquote>'
						. '<p>In uitzonderlijke gevallen behouden wij ons het recht voor een bestelling te annuleren en het betaalde bedrag terug te storten.</p>',
				],
				[
					'title' => 'Nabestellingen',
					'text'  => '<p>Bevat je bestelling een artikel dat op nabestelling staat? Dan wordt de volledige bestelling verzonden zodra alle artikelen op voorraad zijn.</p>',
				],
				[
					'title' => 'Vragen over je bestelling?',
					'text'  => '<p>Neem gerust contact met ons op — wij kijken het graag voor je na.</p>',
					'chips' => "klantenservice@productsforhome.nl\n0617392302",
					'tone'  => 'highlight',
				],
			],
		];
	}
}
