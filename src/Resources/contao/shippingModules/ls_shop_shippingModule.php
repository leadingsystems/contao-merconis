<?php

namespace Merconis\Core;

	/**
	 * Diese Klasse stellt die Versandmodule bereit. Die Einstellungen für ein Versandmodul werden in $this->types definiert
	 * und dann automatisch verarbeitet, z. B. für die Anpassung des DCA in tl_ls_shop_shipping_methods.
	 * 
	 * Wichtig: type "standard" muss AUF JEDEN FALL so drin sein, da dies in der SQL-Definition als Standard für type eingetragen wird.
	 * 
	 * Zu beachten:
	 * Die Definition der Backend-Form-Fields (BE_formFields) funktioniert exakt so wie in der DCA-Definition. Das Array
	 * $this->types['xyz']['BE_formFields'] entspricht hierbei exakt dem Fields-Array in der DCA-Definition und wird auch
	 * in selbige exakt eingeschoben. Das Definieren von Pflichtfeldern "mandatory"=>true ist problematisch und
	 * sollte nicht erfolgen, da dies beim Hin-und-Her-Wechseln im Backend-Formular dann zu Problemen führt.
	 * 
	 * Wird eine neue Modul-Art erstellt (also hier reinprogrammiert), so muss die Tabelle tl_ls_shop_shipping_methods um die hierfür
	 * benötigten Felder entsprechend erweitert werden.
	 *
	 */
	class ls_shop_shippingModule extends \Controller {
		public $types = array(
			'standard' => array(
				'title' => 'Standard',
				'BE_formFields' => array()
			)
/*			Deaktiviert, da es sich hierbei nur um ein Beispiel für die Einbidung einer
 *			weiteren Versandart mit zusätzlichen Backend-Eingabefeldern handelt.
			,
			'test' => array(
				'typeCode' => 'test', // Muss ein variablennamentauglicher Wert sein, da er z. B. für die Legend-Bezeichnung, also als Array-Key, Verwendung findet
				'title' => 'TEST-Spedition', // Der Title wird als Options-Name im Select-Feld (DCA) verwendet. Mit diesem Namen können im Options-Referenz-Sprach Array eine mehrsprachige Bezeichnung sowie eine Erklärung für den helpwizard hinterlegt werden
				'BE_formFields' => array(
					'TestZumBeispielKundennummerBeiEinerSpedition' => array(
						'label' => '', // Wird hier kein Label eingetragen, so wird automatisch ein Label-Verweis zur Sprachdatei mit dem Feldnamen (Array-Key) verwendet (Standard)
						'inputType' => 'text'
					),
					'TestZumBeispielPasswort' => array(
						'label' => '',
						'inputType' => 'text'
					)
				)
			)
 */
		);
		public $currentType = 'standard';
		public $settings = array();
		
		
		public function __construct() {
			if(\System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
				$this->import('FrontendUser', 'User');
			}
			parent::__construct();
		}
		
		/*
		 * Only an empty dummy function because the functionality is not yet implemented in the shipping module
		 * but during checkout this function is already called so that there's no need to change the code creating
		 * the order summary when the shipping module is updated
		 */
		public function getShippingInfo() {
			return '';
		}
	}
?>