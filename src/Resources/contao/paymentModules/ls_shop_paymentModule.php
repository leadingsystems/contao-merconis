<?php

namespace Merconis\Core;

	/**
	 * Diese Klasse stellt die Zahlungsmodule bereit. Die Einstellungen für ein Zahlungsmodul werden in $this->types definiert
	 * und dann automatisch verarbeitet, z. B. für die Anpassung des DCA in tl_ls_shop_payment_methods.
	 * 
	 * Wichtig: type "standard" muss AUF JEDEN FALL so drin sein, da dies in der SQL-Definition als Standard für type eingetragen wird.
	 * 
	 * Zu beachten:
	 * Die Definition der Backend-Form-Fields (BE_formFields) funktioniert exakt so wie in der DCA-Definition. Das Array
	 * $this->types['xyz']['BE_formFields'] entspricht hierbei exakt dem Fields-Array in der DCA-Definition und wird auch
	 * in selbige exakt eingeschoben. Das Definieren von Pflichtfeldern "mandatory"=>true ist problematisch und
	 * sollte nicht erfolgen, da dies beim Hin-und-Her-Wechseln im Backend-Formular dann zu Problemen führt.
	 * 
	 * Wird eine neue Modul-Art erstellt (also hier reinprogrammiert), so muss die Tabelle tl_ls_shop_payment_methods um die hierfür
	 * benötigten Felder entsprechend erweitert werden.
	 * 
	 * Für Funktionen, die sich je nach Zahlungsmethode unterscheiden bzw. für manche Zahlungsmethoden
	 * überhaupt nicht existieren, gibt es eine zentrale Funktion, die aufgerufen wird und die dann
	 * prüft, ob es eine für die aktuell gewählte Zahlungsmethode zutreffende Funktion gibt. Ist dies
	 * der Fall, wird die entsprechende Funktion ausgeführt. Ist dies nicht der Fall, wird entweder
	 * abgebrochen oder eine Standardfunktion ausgeführt.
	 * 
	 * Funktionen, die nur für die Interna einer einzelnen Zahlungsmethode nötig sind, bekommen ein Prefix,
	 * das dem TypeCode entspricht.
	 * 
	 * Variablen im "Root"-Namespace der Klasse/des Objektes, die nur für die Interna einer einzelnen Zahlungsmethode nötig sind, bekommen ein Prefix,
	 * das dem TypeCode entspricht. Variablen, die rein in den Funktionen Verwendung finden, benötigen kein solches Prefix.
	 * 
	 * Session-Variablen, die nur für die Interna/Abwicklung einer einzelnen Zahlungsmethode nötig sind, 
	 * werden wie folgt benannt: z. B. $_SESSION['lsShopPaymentProcess']['paypal']['xyz']
	 * Auf den Key, der dem TypeCode entspricht, ist besonders zu achten!
	 *
	 */
	class ls_shop_paymentModule extends \Controller {
		public $types = array(
			'standard' => array(
				'typeCode' => 'standard',
				'title' => 'Standard',
				'className' => 'Merconis\Core\ls_shop_paymentModule_standard',
				'BE_formFields' => array()
			),
			'sofortueberweisung' => array(
				'typeCode' => 'sofortueberweisung',
				'title' => 'Sofortueberweisung',
				'className' => 'Merconis\Core\ls_shop_paymentModule_sofortueberweisung',
				'BE_formFields' => array(
					'sofortueberweisungConfigkey' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'sofortueberweisungUseCustomerProtection' => array(
						'label' => '',
						'inputType' => 'checkbox'
					)
				)
			),
			'santanderWebQuick' => array(
				'typeCode' => 'santanderWebQuick',
				'title' => 'Santander WebQuick',
				'className' => 'Merconis\Core\ls_shop_paymentModule_santanderWebQuick',
				'BE_formFields' => array(
					'santanderWebQuickVendorNumber' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'santanderWebQuickVendorPassword' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'santanderWebQuickLiveMode' => array(
						'label' => '',
						'inputType' => 'checkbox'
					),
					'santanderWebQuickMinAge' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'santanderWebQuickFieldNameSalutation' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'santanderWebQuickFieldNameFirstName' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'santanderWebQuickFieldNameLastName' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'santanderWebQuickFieldNameEmailAddress' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'santanderWebQuickFieldNameStreet' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'santanderWebQuickFieldNameCity' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'santanderWebQuickFieldNameZipCode' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'santanderWebQuickFieldNameCountry' => array(
						'label' => '',
						'inputType' => 'text'
					)
				)
			),
			'paypal' => array(
				'typeCode' => 'paypal', // Dieser Wert muss dem Array-Key im $types-Array entsprechen, da er z. B. für die Legend-Bezeichnung, also als Array-Key, Verwendung findet
				'title' => 'PayPal', // Der Title wird als Options-Name im Select-Feld (DCA) verwendet. Mit diesem Namen können im Options-Referenz-Sprach Array eine mehrsprachige Bezeichnung sowie eine Erklärung für den helpwizard hinterlegt werden
				'className' => 'Merconis\Core\ls_shop_paymentModule_payPal',
				'BE_formFields' => array(
					'paypalAPIUsername' => array(
						'label' => '', // Wird hier kein Label eingetragen, so wird automatisch ein Label-Verweis zur Sprachdatei mit dem Feldnamen (Array-Key) verwendet (Standard)
						'inputType' => 'text'
					),
					'paypalAPIPassword' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'paypalAPISignature' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'paypalSecondForm' => array(
						'label' => '',
						'inputType' => 'select',
						'foreignKey' => 'tl_form.title'
					),
					'paypalGiropayRedirectForm' => array(
						'label' => '',
						'inputType' => 'select',
						'foreignKey' => 'tl_form.title'
					),
					'paypalGiropaySuccessPages' => array(
						'label' => '',
						'inputType' => 'pageTree',
						'eval' => array('multiple' => true, 'fieldType'=>'checkbox')
					),
					'paypalGiropayCancelPages' => array(
						'label' => '',
						'inputType' => 'pageTree',
						'eval' => array('multiple' => true, 'fieldType'=>'checkbox')
					),
					'paypalBanktransferPendingPages' => array(
						'label' => '',
						'inputType' => 'pageTree',
						'eval' => array('multiple' => true, 'fieldType'=>'checkbox')
					),
					'paypalShipToFieldNameFirstname' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'paypalShipToFieldNameLastname' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'paypalShipToFieldNameStreet' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'paypalShipToFieldNameCity' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'paypalShipToFieldNamePostal' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'paypalShipToFieldNameState' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'paypalShipToFieldNameCountryCode' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'paypalLiveMode' => array(
						'label' => '',
						'inputType' => 'checkbox'
					),
					'paypalShowItems' => array(
						'label' => '',
						'inputType' => 'checkbox'
					)
				)
			),
			'payPalPlus' => array(
				'typeCode' => 'payPalPlus', // Dieser Wert muss dem Array-Key im $types-Array entsprechen, da er z. B. für die Legend-Bezeichnung, also als Array-Key, Verwendung findet
				'title' => 'PayPal Plus', // Der Title wird als Options-Name im Select-Feld (DCA) verwendet. Mit diesem Namen können im Options-Referenz-Sprach Array eine mehrsprachige Bezeichnung sowie eine Erklärung für den helpwizard hinterlegt werden
				'className' => 'Merconis\Core\ls_shop_paymentModule_payPalPlus',
				'BE_formFields' => array(
					'payPalPlus_clientID' => array(
						'label' => '', // Wird hier kein Label eingetragen, so wird automatisch ein Label-Verweis zur Sprachdatei mit dem Feldnamen (Array-Key) verwendet (Standard)
						'inputType' => 'text'
					),
					'payPalPlus_clientSecret' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payPalPlus_liveMode' => array(
						'label' => '',
						'inputType' => 'checkbox'
					),
					'payPalPlus_logMode' => array(
						'label' => '',
						'inputType' => 'select',
						'options' => array('NONE', 'DEBUG', 'INFO', 'WARN', 'ERROR'),
						'default' => 'NONE'
					),
					'payPalPlus_shipToFieldNameFirstname' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payPalPlus_shipToFieldNameLastname' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payPalPlus_shipToFieldNameStreet' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payPalPlus_shipToFieldNameCity' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payPalPlus_shipToFieldNamePostal' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payPalPlus_shipToFieldNameState' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payPalPlus_shipToFieldNameCountryCode' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payPalPlus_shipToFieldNamePhone' => array(
						'label' => '',
						'inputType' => 'text'
					)
				)
			),
			'payPalCheckout' => array(
				'typeCode' => 'payPalCheckout', // Dieser Wert muss dem Array-Key im $types-Array entsprechen, da er z. B. für die Legend-Bezeichnung, also als Array-Key, Verwendung findet
				'title' => 'Paypal Checkout', // Der Title wird als Options-Name im Select-Feld (DCA) verwendet. Mit diesem Namen können im Options-Referenz-Sprach Array eine mehrsprachige Bezeichnung sowie eine Erklärung für den helpwizard hinterlegt werden
				'className' => 'Merconis\Core\ls_shop_paymentModule_payPalCheckout',
				'BE_formFields' => array(
					'payPalCheckout_clientID' => array(
						'label' => '', // Wird hier kein Label eingetragen, so wird automatisch ein Label-Verweis zur Sprachdatei mit dem Feldnamen (Array-Key) verwendet (Standard)
						'inputType' => 'text'
					),
					'payPalCheckout_clientSecret' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payPalCheckout_liveMode' => array(
						'label' => '',
						'inputType' => 'checkbox'
					)
				)
			),
			'payone' => array(
				'typeCode' => 'payone',
				'title' => 'PAYONE',
				'className' => 'Merconis\Core\ls_shop_paymentModule_payone',
				'BE_formFields' => array(
					'payone_subaccountId' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_portalId' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_key' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_liveMode' => array(
						'label' => '',
						'inputType' => 'checkbox'
					),
					'payone_clearingtype' => array(
						'label' => '',
						'inputType' => 'select',
						'options' => array('', 'elv', 'cc', 'vor', 'rec', 'sb', 'wlt'), // the option 'fnc' is not supported because apparently, payone does not offer financing with the frontend api
						'reference' => '', // needs to be overridden in the constructor because we can't assign a $_GLOBALS reference here
						'default' => '',
						'eval' => array('mandatory' => true)
					),
					'payone_fieldNameFirstname' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameLastname' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameCompany' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameStreet' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameAddressaddition' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameZip' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameCity' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameCountry' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameEmail' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameTelephonenumber' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameBirthday' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNameGender' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'payone_fieldNamePersonalid' => array(
						'label' => '',
						'inputType' => 'text'
					),
				)
			),
			'saferpay' => array(
				'typeCode' => 'saferpay',
				'title' => 'SAFERPAY',
				'className' => 'Merconis\Core\ls_shop_paymentModule_saferpay',
				'BE_formFields' => array(
					'saferpay_username' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'saferpay_password' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'saferpay_customerId' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'saferpay_terminalId' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'saferpay_merchantEmail' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'saferpay_liveMode' => array(
						'label' => '',
						'inputType' => 'checkbox'
					),
					'saferpay_captureInstantly' => array(
						'label' => '',
						'inputType' => 'checkbox'
					),
					'saferpay_paymentMethods' => array(
						'label' => '',
						'inputType' => 'checkbox',
						'options' => array('AMEX', 'BONUS', 'DINERS', 'DIRECTDEBIT', 'EPRZELEWY', 'EPS', 'GIROPAY', 'IDEAL', 'INVOICE', 'JCB', 'MAESTRO', 'MASTERCARD', 'MYONE', 'PAYPAL', 'POSTCARD', 'POSTFINANCE', 'SAFERPAYTEST', 'SOFORT', 'VISA', 'VPAY', 'TWINT'),
						'reference' => '', // needs to be overridden in the constructor because we can't assign a $_GLOBALS reference here
						'default' => '',
						'eval' => array('multiple' => true)
					),
					'saferpay_wallets' => array(
						'label' => '',
						'inputType' => 'checkbox',
						'options' => array('MASTERPASS'),
						'reference' => '', // needs to be overridden in the constructor because we can't assign a $_GLOBALS reference here
						'default' => '',
						'eval' => array('multiple' => true)
					)
				)
			),

			'vrpay' => array(
				'typeCode' => 'vrpay',
				'title' => 'VR Pay',
				'className' => 'Merconis\Core\ls_shop_paymentModule_vrpay',
				'BE_formFields' => array(
					'vrpay_userId' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'vrpay_password' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'vrpay_token' => array(
						'label' => '',
						'inputType' => 'text'
				    	),
					'vrpay_entityId' => array(
						'label' => '',
						'inputType' => 'text'
					),
					'vrpay_liveMode' => array(
						'label' => '',
						'inputType' => 'checkbox'
					),
					'vrpay_testMode' => array(
						'label' => '',
						'inputType' => 'select',
						'options' => array('', 'INTERNAL', 'EXTERNAL'),
						'reference' => '', // needs to be overridden in the constructor because we can't assign a $_GLOBALS reference here
						'default' => ''
					),
					'vrpay_paymentInstrument' => array(
						'label' => '',
						'inputType' => 'select',
						'options' => array(
							'creditcard',
							'giropay',
							'paydirekt',
							'directdebit_sepa',
							'sofortueberweisung',
							'paypal',
							// 'easycredit_ratenkauf',
							// 'klarna_invoice'
						),
						'reference' => '', // needs to be overridden in the constructor because we can't assign a $_GLOBALS reference here
						'default' => '',
						'eval' => array('mandatory' => true, 'submitOnChange' => true)
					)
				),
				'BE_formFields_subpalettes' => array(
					'vrpay_paymentInstrument_creditcard' => array(
						'selector' => 'vrpay_paymentInstrument',
						'fields' => array(
							'vrpay_creditCardBrands' => array(
								'label' => '',
								'inputType' => 'checkbox',
								'options' => array('VISA', 'MASTER', 'AMEX', 'JCB', 'DINERS'),
								'reference' => '', // needs to be overridden in the constructor because we can't assign a $_GLOBALS reference here
								'default' => '',
								'eval' => array('multiple' => true, 'mandatory' => true)
							)
						)
					),

					'vrpay_paymentInstrument_paydirekt' => array(
						'selector' => 'vrpay_paymentInstrument',
						'fields' => array(
							'vrpay_fieldName_givenName' => array(
								'label' => '',
								'inputType' => 'text'
							),
							'vrpay_fieldName_surname' => array(
								'label' => '',
								'inputType' => 'text'
							),
							'vrpay_fieldName_street1' => array(
								'label' => '',
								'inputType' => 'text'
							),
							'vrpay_fieldName_city' => array(
								'label' => '',
								'inputType' => 'text'
							),
							'vrpay_fieldName_postcode' => array(
								'label' => '',
								'inputType' => 'text'
							),
							'vrpay_fieldName_country' => array(
								'label' => '',
								'inputType' => 'text'
							)
						)
					)
				)
			)
		);
		protected $arrCurrentSettings = array();
		protected $paymentFinished = false;
		protected $isAuthorized = false;
		
		
		public function __construct() {
			$this->types['payone']['BE_formFields']['payone_clearingtype']['reference'] = $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_clearingtype']['options'];
			
			$this->types['saferpay']['BE_formFields']['saferpay_paymentMethods']['reference'] = $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_paymentMethods']['options'];
			$this->types['saferpay']['BE_formFields']['saferpay_wallets']['reference'] = $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_wallets']['options'];

			$this->types['vrpay']['BE_formFields']['vrpay_paymentInstrument']['reference'] = $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_paymentInstrument']['options'];
			$this->types['vrpay']['BE_formFields_subpalettes']['vrpay_paymentInstrument_creditcard']['fields']['vrpay_creditCardBrands']['reference'] = $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_creditCardBrands']['options'];

			$this->types['vrpay']['BE_formFields']['vrpay_testMode']['reference'] = $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_testMode']['options'];

			if (isset($GLOBALS['MERCONIS_HOOKS']['modifyPaymentModuleTypes']) && is_array($GLOBALS['MERCONIS_HOOKS']['modifyPaymentModuleTypes'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['modifyPaymentModuleTypes'] as $mccb) {
					$objMccb = \System::importStatic($mccb[0]);
					$this->types = $objMccb->{$mccb[1]}($this->types);
				}
			}
			
			if(FE_USER_LOGGED_IN) {
				$this->import('FrontendUser', 'User');
			}
			parent::__construct();
			
			/*
			 * Sofern bereits eine PaymentMethod gewählt ist, wird das Zahlungsmodul
			 * automatisch "spezialisiert"
			 */
			$arrPaymentMethodInfo = ls_shop_generalHelper::getPaymentMethodInfo(ls_shop_checkoutData::getInstance()->arrCheckoutData['selectedPaymentMethod']);
			if (is_array($arrPaymentMethodInfo)) {
				$this->specialize($arrPaymentMethodInfo);
			}
		}
		
		public function __get($strValue) {
			switch($strValue) {
				case 'settings':
					return $this->arrCurrentSettings;
					break;
					
				default:
					## cc3a ##
					return parent::__get($strValue);
					## cc3e ##
					break;
			}
		}
		
		public function specializeManuallyWithPaymentID($paymentID, $blnForceRefresh = true) {
			$arrPaymentMethodInfo = ls_shop_generalHelper::getPaymentMethodInfo($paymentID, true);
			if (is_array($arrPaymentMethodInfo)) {
				$this->specialize($arrPaymentMethodInfo, $blnForceRefresh, true);
			}
		}

		/*
		 * Diese Funktion "spezialisiert" das Zahlungsmodul. Hierzu wird die ID des Zahlungsmethoden-Datensatzes
		 * erwartet, da aus diesem der genaue Typ für die Spezialisierung sowie die im Backend vorgenommenen
		 * Einstellungen ausgelesen werden
		 */
		protected function specialize($arrPaymentMethodInfo = false, $blnForceRefresh = false, $specializedManually = false) {
			if (!is_array($arrPaymentMethodInfo)) {
				return false;
			}
			
			/*
			 * Ist der paymentMethod ein Typ hinterlegt, der gar nicht existiert (weil sich seit der Erfassung des paymentMethod-Datensatzes im Contao-Backend
			 * z. B. einiges in der Programmdatei verändert hat und dieses paymentMethod nicht mehr verfügbar ist), so wird die Funktion abgebrochen
			 * und false zurückgegeben
			 */
			if (!isset($this->types[$arrPaymentMethodInfo['type']]) || !is_array($this->types[$arrPaymentMethodInfo['type']])) {
				return false;
			}
			
			/*
			 * Wenn alles passt, werden die aktuellen Einstellungen der paymentMethod in diesem Objekt als currentSettings hinterlegt.
			 */
			$this->arrCurrentSettings = $arrPaymentMethodInfo;
			
			$this->import($this->types[$arrPaymentMethodInfo['type']]['className'], 'specialModule', $blnForceRefresh);
			$this->specialModule->arrCurrentSettings = $this->arrCurrentSettings;
			
			$this->initialize($specializedManually);
		}
		
		protected function initialize($specializedManually = false) {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($specializedManually);
			} else {
				return null;
			}			
		}
		
		/*
		 * --> Allgemeine Funktionen zum externen Aufruf
		 * 
		 * WICHTIG: Funktionen, die im Standard-Zahlungsmodul definiert sind, werden an die speziellen Zahlungsmodule weitervererbt,
		 * da sich diese vom Standard-Zahlungsmodul ableiten. Sofern Funktionen nur für den internen Aufruf gedacht sind, reicht es daher,
		 * wenn sie im Standard-Zahlungsmodul definiert werden. In diesm übergeordneten Master-Modul müssen nur Funktionen definiert werden,
		 * deren externer Aufruf möglich sein muss.
		 */
		
		public function redirectToErrorPage($context = '', $errorInformation01 = '', $errorInformation02 = '', $errorInformation03 = '') {
			$methodName = __FUNCTION__;
			## fixEndlessRecursionOnPaymentError begin ##
			$_SESSION['lsShop']['blnPaymentOrShippingErrorOccured'] = true;
			## fixEndlessRecursionOnPaymentError end ##
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($context, $errorInformation01, $errorInformation02, $errorInformation03);
			} else {
				return null;
			}
		}
		
		public function logPaymentError($context = '', $errorInformation01 = '', $errorInformation02 = '', $errorInformation03 = '') {
			$methodName = __FUNCTION__;
			## fixEndlessRecursionOnPaymentError begin ##
			$_SESSION['lsShop']['blnPaymentOrShippingErrorOccured'] = true;
			## fixEndlessRecursionOnPaymentError end ##
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($context, $errorInformation01, $errorInformation02, $errorInformation03);
			} else {
				return null;
			}
		}
		
		public function update_paymentMethod_moduleReturnData_inOrder($int_orderID = 0, $var_paymentMethod_moduleReturnData = '') {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($int_orderID, $var_paymentMethod_moduleReturnData);
			} else {
				return null;
			}
		}
		
		public function afterPaymentMethodSelection() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return null;
			}
		}
				
		public function afterPaymentMethodAdditionalDataConfirm() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return null;
			}
		}
						
		public function statusOkayToShowAdditionalDataForm() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return true;
			}
		}
						
		public function statusOkayToShowCustomUserInterface() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return true;
			}
		}
				
		public function statusOkayToRedirectToCheckoutFinish() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return true;
			}
		}
				
		public function beforeCheckoutFinish() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return null;
			}
		}
				
		public function checkoutFinishAllowed() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return true;
			}
		}
				
		public function afterCheckoutFinish($orderIdInDb = 0, $order = array(), $afterCheckoutUrl = '', $oix = '') {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($orderIdInDb, $order, $afterCheckoutUrl, $oix);
			} else {
				return null;
			}
		}
		
		public function check_usePaymentAfterCheckoutPage($orderIdInDb = 0, $order = array()) {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($orderIdInDb, $order);
			} else {
				return false;
			}
		}

		public function onAfterCheckoutPage($order = array()) {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($order);
			} else {
				return null;
			}
		}
				
		public function onPaymentAfterCheckoutPage($order = array()) {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($order);
			} else {
				return null;
			}
		}

		public function getPaymentInfo() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return null;
			}
		}
				
		public function getCustomUserInterface() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return null;
			}
		}
		
		public function getFormIDForAdditionalData($formID) {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($formID);
			} else {
				return 0;
			}
		}
		
		public function getPaymentMethodSuccessMessage() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return '';
			}
		}
		
		public function getPaymentMethodErrorMessage() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return '';
			}
		}
		
		public function showPaymentDetailsInBackendOrderDetailView($arrOrder = array(), $paymentMethod_moduleReturnData = '') {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($arrOrder, $paymentMethod_moduleReturnData);
			} else {
				return null;
			}			
		}
		
		public function showPaymentStatusInOverview($arrOrder = array(), $paymentMethod_moduleReturnData = '') {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($arrOrder, $paymentMethod_moduleReturnData);
			} else {
				return null;
			}			
		}
		
		public function determineOix() {
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}();
			} else {
				return null;
			}			
		}
		
		public function modifyConfirmOrderForm($form = '') {
			/*
			 * This hook makes it possible to override the modifyConfirmOrderForm behaviour of a payment module.
			 * The hooked function probably only wants to modify the form if a certain payment module is selected.
			 * To leave the form unmodified the hooked function must return null. If null is returned, the regular
			 * payment module function is called. If a value other than null is returned, this value will be
			 * returned and the regular payment module function is skipped.
			 */
			if (isset($GLOBALS['MERCONIS_HOOKS']['modifyConfirmOrderForm']) && is_array($GLOBALS['MERCONIS_HOOKS']['modifyConfirmOrderForm'])) {
				$modifiedForm = $form;
				foreach ($GLOBALS['MERCONIS_HOOKS']['modifyConfirmOrderForm'] as $mccb) {
					$objMccb = \System::importStatic($mccb[0]);
					$modifiedForm = $objMccb->{$mccb[1]}($modifiedForm);
					
				}
				if (!is_null($modifiedForm)) {
					return $modifiedForm;
				}
			}
			
			$methodName = __FUNCTION__;
			if (method_exists($this->specialModule, $methodName)) {
				return $this->specialModule->{$methodName}($form);
			} else {
				return $form;
			}
		}
		
		/*
		 * <-- Allgemeine Funktionen zum externen Aufruf
		 */
	}
?>
