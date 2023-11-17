<?php

namespace Merconis\Core;

use Contao\Controller;
use Contao\Database;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

class ls_shop_checkoutData {
	public $arrCheckoutData = array(
		'arrCustomerData' => array(),
		'selectedPaymentMethod' => '',
		'selectedPaymentMethodManually' => false,
		'cheapestPossiblePaymentMethod' => '',
		'arrPaymentMethodAdditionalData' => array(),
		'selectedShippingMethod' => '',
		'selectedShippingMethodManually' => false,
		'cheapestPossibleShippingMethod' => '',
		'arrShippingMethodAdditionalData' => array(),
		'loggedInData' => array(
			'userID' => 0,
			'userName' => ''
		)
	);
		
	private $arrValidation = array(
		'blnCustomerDataIsValid' => false,
		'blnPaymentMethodDataIsValid' => false,
		'blnShippingMethodDataIsValid' => false,
		'blnLoginStatusIsValid' => false,
		'blnCartIsValid' => false,
        'onlyNotSelectable' => false
	);
	
	private $blnCheckoutDataIsValid = false;
		
	private $formCustomerData = '';
	
	private $formPaymentMethodRadio = '';
	
	private $formShippingMethodRadio = '';
	
	private $formPaymentMethodAdditionalData = '';
	private $formShippingMethodAdditionalData = '';
	
	private $formPaymentMethodAdditionalDataID = 0;
	private $formShippingMethodAdditionalDataID = 0;
	private $formCustomerDataID = 0;
	private $formConfirmOrderID = 0;
	
	private $formConfirmOrder = '';

	private $arrCustomerDataReview = null;
	private $arrCustomerDataReviewOnlyOriginalOptionValues = null;
	private $customerDataReview = null;
	
	private $arrPaymentMethodAdditionalDataReview = null;
	private $arrPaymentMethodAdditionalDataReviewOnlyOriginalOptionValues = null;
	private $paymentMethodAdditionalDataReview = null;
	
	private $arrShippingMethodAdditionalDataReview = null;
	private $arrShippingMethodAdditionalDataReviewOnlyOriginalOptionValues = null;
	private $shippingMethodAdditionalDataReview = null;
	
	private $paymentMethodMessages = array(
		'error' => '',
		'success' => ''
	);
	
	/**
	 * Current object instance (Singleton)
	 * @var ls_shop_checkoutData
	 */
	protected static $objInstance;

	/**
	 * Prevent direct instantiation (Singleton)
	 */
	protected function __construct() {
		/** @var PageModel $objPage */
		global $objPage;
		if (is_object($objPage)) {
            System::loadLanguageFile('default', $objPage->language);
        }

		// CheckoutData aus der Session einlesen, sofern in der Session schon vorhanden
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop', []);

		$this->arrCheckoutData = isset($session_lsShop['arrCheckoutData']) ? $session_lsShop['arrCheckoutData'] : $this->arrCheckoutData;
	}
	
	public function writeCheckoutDataToSession() {
		// CheckoutData in die Session schreiben
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop', []);
        $session_lsShop['arrCheckoutData'] = $this->arrCheckoutData;
        $session->set('lsShop', $session_lsShop);
	}


	/**
	 * Prevent cloning of the object (Singleton)
	 */
	private function __clone() {}

	/**
	 * Return the current object instance (Singleton)
	 * @return ls_shop_checkoutData
	 */
	public static function getInstance() {
		if (!is_object(self::$objInstance))	{
			self::$objInstance = new self();
			/*
			 * Achtung, Reihenfolge ist wichtig!
			 */
			self::$objInstance->getLoginData();
			self::$objInstance->getFormIDs();
			self::$objInstance->analyzeRequiredFields();

			self::$objInstance->preselectPaymentOrShippingMethod();
			self::$objInstance->validateCheckoutData();

			self::$objInstance->getForms();

			self::$objInstance->checkIntegrity();
			self::$objInstance->getPaymentMethodMessages();
		}
		return self::$objInstance;
	}
	
	public function __get($what) {
		switch ($what) {
			case 'customPaymentMethodUserInterface':
				return $this->getCustomPaymentOrShippingMethodUserInterface('payment');
				break;
			
			case 'customShippingMethodUserInterface':
				return $this->getCustomPaymentOrShippingMethodUserInterface('shipping');
				break;
			
			case 'noPaymentMethodSelected':
				if (!isset($this->arrCheckoutData['selectedPaymentMethod']) || !$this->arrCheckoutData['selectedPaymentMethod']) {
					return true;
				}
				return false;
				break;
				
			case 'noPaymentMethodCouldBeDetermined':
				if ((!isset($this->arrCheckoutData['selectedPaymentMethod']) || !$this->arrCheckoutData['selectedPaymentMethod']) && (!isset($this->arrCheckoutData['cheapestPossiblePaymentMethod']) || !$this->arrCheckoutData['cheapestPossiblePaymentMethod'])) {
					return true;
				}
				return false;
				break;
			
			case 'noShippingMethodSelected':
				if (!isset($this->arrCheckoutData['selectedShippingMethod']) || !$this->arrCheckoutData['selectedShippingMethod']) {
					return true;
				}
				return false;
				break;
				
			case 'noShippingMethodCouldBeDetermined':
				if ((!isset($this->arrCheckoutData['selectedShippingMethod']) || !$this->arrCheckoutData['selectedShippingMethod']) && (!isset($this->arrCheckoutData['cheapestPossibleShippingMethod']) || !$this->arrCheckoutData['cheapestPossibleShippingMethod'])) {
					return true;
				}
				return false;
				break;
			
			case 'arrCheckoutData':
				return $this->arrCheckoutData;
				break;
				
			case 'formCustomerData':
				return $this->formCustomerData;
				break;
				
			case 'formPaymentMethodRadio':
				return $this->formPaymentMethodRadio;
				break;
				
			case 'formShippingMethodRadio':
				return $this->formShippingMethodRadio;
				break;
				
			case 'formPaymentMethodAdditionalData':
				return $this->formPaymentMethodAdditionalData;
				break;
				
			case 'formShippingMethodAdditionalData':
				return $this->formShippingMethodAdditionalData;
				break;
				
			case 'signUpLink':
				return sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText049'], ls_shop_languageHelper::getLanguagePage('ls_shop_signUpPages'));
				break;
				
			case 'formConfirmOrder':
				return $this->formConfirmOrder;
				break;
				
			case 'validationErrors':
				return $this->getValidationErrorsOutput();
				break;
				
			case 'arrValidation':
				return $this->arrValidation;
				break;
				
			case 'customerDataIsValid':
				return $this->arrValidation['blnCustomerDataIsValid'];
				break;

            case 'onlyNotSelectable':
                return $this->arrValidation['onlyNotSelectable'];
                break;
				
			case 'paymentMethodDataIsValid':
				return $this->arrValidation['blnPaymentMethodDataIsValid'];
				break;
				
			case 'shippingMethodDataIsValid':
				return $this->arrValidation['blnShippingMethodDataIsValid'];
				break;
				
			case 'loginStatusIsValid':
				return $this->arrValidation['blnLoginStatusIsValid'];
				
			case 'cartIsValid':
				return $this->arrValidation['blnCartIsValid'];
				
			case 'checkoutDataIsValid':
				return $this->blnCheckoutDataIsValid;
				break;
				
			case 'arrCustomerDataReview':
				return $this->getArrCustomerDataReview();
				break;
				
			case 'arrCustomerDataReviewOnlyOriginalOptionValues':
				return $this->getArrCustomerDataReviewOnlyOriginalOptionValues();
				break;

			case 'customerDataReview':
				return $this->getCustomerDataReview();
				break;
				
			case 'arrPaymentMethodAdditionalDataReview':
				return $this->getArrPaymentMethodAdditionalDataReview();
				break;
				
			case 'arrPaymentMethodAdditionalDataReviewOnlyOriginalOptionValues':
				return $this->getArrPaymentMethodAdditionalDataReviewOnlyOriginalOptionValues();
				break;

			case 'paymentMethodAdditionalDataReview':
				return $this->getPaymentMethodAdditionalDataReview();
				break;
				
			case 'arrShippingMethodAdditionalDataReview':
				return $this->getArrShippingMethodAdditionalDataReview();
				break;

			case 'arrShippingMethodAdditionalDataReviewOnlyOriginalOptionValues':
				return $this->getArrShippingMethodAdditionalDataReviewOnlyOriginalOptionValues();
				break;

			case 'shippingMethodAdditionalDataReview':
				return $this->getShippingMethodAdditionalDataReview();
				break;
				
			case 'paymentMethodErrorMessage':
				return $this->paymentMethodMessages['error'];
				break;
				
			case 'paymentMethodSuccessMessage':
				return $this->paymentMethodMessages['success'];
				break;
		}
	}

	private function getCustomPaymentOrShippingMethodUserInterface($what = 'payment') {
		if ($what != 'payment' && $what != 'shipping') {
			return '';
		}
		
		if (!isset($GLOBALS['merconis_globals']['checkoutData']['customPaymentOrShippingMethodUserInterface'][$what])) {
			switch ($what) {
				case 'payment':
					// ### paymentMethod callback ########################
					$obj_paymentModule = System::importStatic('Merconis\Core\ls_shop_paymentModule', null, true);
					if ($obj_paymentModule->statusOkayToShowCustomUserInterface()) {
						$GLOBALS['merconis_globals']['checkoutData']['customPaymentOrShippingMethodUserInterface'][$what] = $obj_paymentModule->getCustomUserInterface();
					} else {
						$GLOBALS['merconis_globals']['checkoutData']['customPaymentOrShippingMethodUserInterface'][$what] = '';
					}
					// ###################################################
					break;
				
				case 'shipping':
					// ### shippingMethod callback ########################
					// not implemented yet
					// ###################################################
					break;
			}
		}
		return $GLOBALS['merconis_globals']['checkoutData']['customPaymentOrShippingMethodUserInterface'][$what] ?? null;
	}

	private function getPaymentMethodMessages() {
		if (!isset($GLOBALS['merconis_globals']['checkoutData']['paymentMethodMessages'])) {
			// ### paymentMethod callback ########################
			$obj_paymentModule = System::importStatic('Merconis\Core\ls_shop_paymentModule', null, true);
			$GLOBALS['merconis_globals']['checkoutData']['paymentMethodMessages']['error'] = $obj_paymentModule->getPaymentMethodErrorMessage();
			$GLOBALS['merconis_globals']['checkoutData']['paymentMethodMessages']['success'] = $obj_paymentModule->getPaymentMethodSuccessMessage();
			// ###################################################			
		}
		$this->paymentMethodMessages = $GLOBALS['merconis_globals']['checkoutData']['paymentMethodMessages'];
	}

	/**
	 * Diese Funktion prüft, ob ein spezieller Step im Checkout-Prozess aufgerufen wird und ob dieser entsprechend
	 * des aktuellen Validierungszustandes erlaubt ist. Ist er nicht erlaubt, wird weitergeleitet zur aktuellen Seite
	 * OHNE step-Parameter
	 */
	private function checkIntegrity() {
		/** @var PageModel $objPage */
		global $objPage;
		if (!Input::get('step')) {
			return true;
		}
		
		switch(Input::get('step')) {
			case 'cart':
			case 'dataEntry':
			case 'shippingSelection':
			case 'paymentSelection':
				break;
			case 'review':
			default:
				if (!$this->blnCheckoutDataIsValid) {
					Controller::redirect(Controller::generateFrontendUrl($objPage->row()));
				}
				break;
		}
	}

	private function getCustomerDataReview() {
		if (is_null($this->customerDataReview)) {
			$obj_template = new FrontendTemplate('template_customerDataReview');
			$obj_template->arr_data = $this->arrCustomerDataReview;
			$this->customerDataReview = $obj_template->parse();
		}
		return $this->customerDataReview;
	}

	private function getPaymentMethodAdditionalDataReview() {
		if (is_null($this->paymentMethodAdditionalDataReview)) {
			$arrPaymentMethodAdditionalDataReview = $this->getArrPaymentMethodAdditionalDataReview();

			if (!count($arrPaymentMethodAdditionalDataReview)) {
				return '';
			}

			$obj_template = new FrontendTemplate('template_additionalPaymentDataReview');
			$obj_template->arr_data = $arrPaymentMethodAdditionalDataReview;
			$this->paymentMethodAdditionalDataReview = $obj_template->parse();
		}
		return $this->paymentMethodAdditionalDataReview;
	}

	private function getShippingMethodAdditionalDataReview() {
		if (is_null($this->shippingMethodAdditionalDataReview)) {
			$arrShippingMethodAdditionalDataReview = $this->getArrShippingMethodAdditionalDataReview();

			if (!count($arrShippingMethodAdditionalDataReview)) {
				return '';
			}

			$obj_template = new FrontendTemplate('template_additionalShippingDataReview');
			$obj_template->arr_data = $arrShippingMethodAdditionalDataReview;
			$this->shippingMethodAdditionalDataReview = $obj_template->parse();
		}
		return $this->shippingMethodAdditionalDataReview;
	}
	
	private function getArrCustomerDataReview() {
		if (is_null($this->arrCustomerDataReview)) {
			$this->arrCustomerDataReview = ls_shop_generalHelper::getArrDataReview($this->arrCheckoutData['arrCustomerData']);
		}
		return $this->arrCustomerDataReview;
	}

	private function getArrCustomerDataReviewOnlyOriginalOptionValues() {
		if (is_null($this->arrCustomerDataReviewOnlyOriginalOptionValues)) {
			$this->arrCustomerDataReviewOnlyOriginalOptionValues = ls_shop_generalHelper::getArrDataReview($this->arrCheckoutData['arrCustomerData'], true);
		}
		return $this->arrCustomerDataReviewOnlyOriginalOptionValues;
	}

	private function getArrPaymentMethodAdditionalDataReview() {
		if (is_null($this->arrPaymentMethodAdditionalDataReview)) {
			$this->arrPaymentMethodAdditionalDataReview = ls_shop_generalHelper::getArrDataReview($this->arrCheckoutData['arrPaymentMethodAdditionalData']);
		}
		return $this->arrPaymentMethodAdditionalDataReview;
	}

	private function getArrPaymentMethodAdditionalDataReviewOnlyOriginalOptionValues() {
		if (is_null($this->arrPaymentMethodAdditionalDataReviewOnlyOriginalOptionValues)) {
			$this->arrPaymentMethodAdditionalDataReviewOnlyOriginalOptionValues = ls_shop_generalHelper::getArrDataReview($this->arrCheckoutData['arrPaymentMethodAdditionalData'], true);
		}
		return $this->arrPaymentMethodAdditionalDataReviewOnlyOriginalOptionValues;
	}

	private function getArrShippingMethodAdditionalDataReview() {
		if (is_null($this->arrShippingMethodAdditionalDataReview)) {
			$this->arrShippingMethodAdditionalDataReview = ls_shop_generalHelper::getArrDataReview($this->arrCheckoutData['arrShippingMethodAdditionalData']);
		}
		return $this->arrShippingMethodAdditionalDataReview;
	}

	private function getArrShippingMethodAdditionalDataReviewOnlyOriginalOptionValues() {
		if (is_null($this->arrShippingMethodAdditionalDataReviewOnlyOriginalOptionValues)) {
			$this->arrShippingMethodAdditionalDataReviewOnlyOriginalOptionValues = ls_shop_generalHelper::getArrDataReview($this->arrCheckoutData['arrShippingMethodAdditionalData'], true);
		}
		return $this->arrShippingMethodAdditionalDataReviewOnlyOriginalOptionValues;
	}

	/**
	 * Diese Funktion ermittelt die Formular-IDs für die Additional-Info-Formulare zu Zahlungs- und Versandmethode
	 */
	private function getFormIDs() {
		$this->formCustomerDataID = $this->getFormIDForCustomerData();
		$this->formConfirmOrderID = $this->getFormIDForConfirmOrder();
		$this->formPaymentMethodAdditionalDataID = $this->getFormIDForSelectedPaymentOrShippingMethod('payment');
		$this->formShippingMethodAdditionalDataID = $this->getFormIDForSelectedPaymentOrShippingMethod('shipping');
	}

	/**
	 * Diese Funktion generiert und validiert dabei - sofern Daten gesendet wurden - die benötigten Formulare.
	 */
	private function getForms() {
		/*
		 * If forms are rendered in the context of the contao hook "postLogin", the objPage object might not be available but
		 * if the form contains iflng insert tags, the language property of objPage must be available because otherwise an exception
		 * would be thrown. To prevent this exception, we create a fallback dummy object for objPage.
		 */
		if (!isset($GLOBALS['objPage'])) {
			$GLOBALS['objPage'] = new \stdClass();
			$GLOBALS['objPage']->language = '';
        	}
		
		$this->formCustomerData = Controller::getForm($this->formCustomerDataID);

		// ### paymentMethod callback ########################
		$obj_paymentModule = System::importStatic('Merconis\Core\ls_shop_paymentModule', null, true);

		if ($this->formPaymentMethodAdditionalDataID && $obj_paymentModule->statusOkayToShowAdditionalDataForm()) {
			// ###################################################
			$this->formPaymentMethodAdditionalData = Controller::getForm($this->formPaymentMethodAdditionalDataID);
		}

		if ($this->formShippingMethodAdditionalDataID) {
			$this->formShippingMethodAdditionalData = Controller::getForm($this->formShippingMethodAdditionalDataID);
		}

		$this->formPaymentMethodRadio = $this->getPaymentOrShippingMethodForm('payment');
		$this->formShippingMethodRadio = $this->getPaymentOrShippingMethodForm('shipping');
		
		if ($obj_paymentModule->checkoutFinishAllowed()) {
			$this->formConfirmOrder = Controller::getForm($this->formConfirmOrderID);
		}

	}
	
	/**
	 * Diese Funktion ermittelt die Felder, die für den Checkout benötigt werden
	 */
	private function analyzeRequiredFields() {
		$this->analyzeRequiredCustomerDataFields();
		$this->analyzeRequiredPaymentOrShippingMethodFields('payment');
		$this->analyzeRequiredPaymentOrShippingMethodFields('shipping');
	}

	private function getLoginData() {
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			return;
		}
		/*
		 * Ist in den Checkout-Data ein Login-Status vermerkt und passt dieser nicht zum tatsächlichen Login-Zustand,
		 * so wird die gewählte Zahlungs- und Versand-Methode zurückgesetzt und der Login-Status in den Checkout-Data
		 * auch zurückgesetzt
		 */
		if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
			$obj_user = System::importStatic('FrontendUser');

			if ($this->arrCheckoutData['loggedInData']['userID'] != $obj_user->id) {
				$this->ls_shop_postLogin($obj_user);
			}

			$this->arrCheckoutData['loggedInData'] = array(
				'userID' => $obj_user->id,
				'userName' => $obj_user->username
			);
		} else {
			if ($this->arrCheckoutData['loggedInData']['userID']) {
				$this->arrCheckoutData['loggedInData'] = array(
					'userID' => 0,
					'userName' => ''
				);
				$this->resetSelectedPaymentAndShippingMethod();
			}
			$this->arrCheckoutData['loggedInData'] = array(
				'userID' => 0,
				'userName' => ''
			);
		}

		$this->writeCheckoutDataToSession();
	}
		
	/*
	 * Diese Funktion ermittelt die Felder, die für die ausgewählte Zahlungsmethode benötigt werden.
	 */
	private function analyzeRequiredPaymentOrShippingMethodFields($what = 'payment') {
		if ($what != 'payment' && $what != 'shipping') {
			return false;
		}
	
		$arrAdditionalDataKey = $what == 'payment' ? 'arrPaymentMethodAdditionalData' : 'arrShippingMethodAdditionalData';
		
		$this->arrCheckoutData[$arrAdditionalDataKey] = ls_shop_generalHelper::analyzeRequiredDataFields($this->getFormIDForSelectedPaymentOrShippingMethod($what), $this->arrCheckoutData[$arrAdditionalDataKey]);

		$this->writeCheckoutDataToSession();
	}

	private function getFormIDForCustomerData() {
		$groupInfo = ls_shop_generalHelper::getGroupSettings4User();
		return $groupInfo['lsShopFormCustomerData'];
	}

	private function getFormIDForConfirmOrder() {
		$groupInfo = ls_shop_generalHelper::getGroupSettings4User();
		return $groupInfo['lsShopFormConfirmOrder'];
	}

	private function getFormIDForSelectedPaymentOrShippingMethod($what = 'payment') {
		$formID = 0;
		if ($what != 'payment' && $what != 'shipping') {
			return $formID;
		}
		
		$methodID = $what == 'payment' ? $this->arrCheckoutData['selectedPaymentMethod'] : $this->arrCheckoutData['selectedShippingMethod'];
		$tableName = 'tl_ls_shop_'.$what.'_methods';
		
		if (!$methodID) {
			return $formID;
		}
		
		$objMethod = Database::getInstance()->prepare("
			SELECT		`formAdditionalData`
			FROM		`".$tableName."`
			WHERE		`id` = ?
		")
		->execute($methodID);
		
		if (!$objMethod->numRows) {
			return $formID;
		}
		
		$objMethod->first();
		
		$formID = $objMethod->formAdditionalData;
		
		if ($what == 'payment') {
			// ### paymentMethod callback ########################
			$obj_paymentModule = System::importStatic('Merconis\Core\ls_shop_paymentModule', null, true);
			$formID = $obj_paymentModule->getFormIDForAdditionalData($formID);
			// ###################################################
		}
		
		return $formID;
	}
	
	/**
	 * Diese Funktion ermittelt die Felder, die für die persönlichen Daten (customerData) nötig sind.
	 * Hierfür wird das Formular, welches in den Shop-Grundeinstellungen für die Erfassung der persönlichen Daten
	 * angegeben ist, analysiert. Nur Felder, die nicht "invisible" sind und deren "name"-Attribut gesetzt ist,
	 * sind relevant. 
	 */
	private function analyzeRequiredCustomerDataFields() {
		$this->arrCheckoutData['arrCustomerData'] = ls_shop_generalHelper::analyzeRequiredDataFields($this->formCustomerDataID, $this->arrCheckoutData['arrCustomerData']);

		$this->writeCheckoutDataToSession();
	}

	/*
	 * Diese Funktion wird über einen Hook aufgerufen, sobald ein Formular abgeschickt und erfolgreich validiert wurde.
	 * Abhängig davon, um welches Formular es sich handelt, werden die passenden internen Funktionen aufgerufen.
	 */
	public function ls_shop_processFormData($arrPost, $arrForm, $arrFiles) {
		if ($arrForm['id'] == $this->formCustomerDataID) {
			ls_shop_msg::setMsg(array(
				'class' => 'customerDataSubmitted',
				'reference' => 'customerDataSubmitted',
				'arrDetails' => array()
			));

			$this->processCustomerData($arrPost, $arrForm, $arrFiles);
		} else if ($arrForm['id'] == $this->formPaymentMethodAdditionalDataID) {
			ls_shop_msg::setMsg(array(
				'class' => 'paymentAdditionalDataSubmitted',
				'reference' => 'paymentAdditionalDataSubmitted',
				'arrDetails' => array()
			));

			$this->processPaymentOrShippingMethodAdditionalData($arrPost, $arrForm, $arrFiles, 'payment');
		} else if ($arrForm['id'] == $this->formShippingMethodAdditionalDataID) {
			ls_shop_msg::setMsg(array(
				'class' => 'shippingAdditionalDataSubmitted',
				'reference' => 'shippingAdditionalDataSubmitted',
				'arrDetails' => array()
			));

			$this->processPaymentOrShippingMethodAdditionalData($arrPost, $arrForm, $arrFiles, 'shipping');
		} else if ($arrForm['id'] == $this->formConfirmOrderID) {
			$this->processFormConfirmData();
		}
	}
	
	/**
	 * Diese Funktion wird über einen Hook aufgerufen und damit exakt dann ausgeführt, wenn die Bestellbestätigung
	 * durch die erfolgreiche Validierung der Bestellbestätigungsformulars erfolgt ist.
	 * 
	 * Es muss nun der tatsächliche Bestellabschluss erfolgen!
	 */
	private function processFormConfirmData() {
		ls_shop_languageHelper::getLanguagePage('ls_shop_checkoutFinishPages');
		Controller::redirect($GLOBALS['merconis_globals']['ls_shop_checkoutFinishPagesUrl'].'#finish');
	}
	
	/*
	 * Die per POST übergebenen Daten werden dem Checkout-Data-Objekt hinterlegt.
	 */
	private function processCustomerData($arrPost, $arrForm, $arrFiles) {
		foreach ($arrPost as $fieldName => $value) {
			if (isset($this->arrCheckoutData['arrCustomerData'][$fieldName])) {
				$this->arrCheckoutData['arrCustomerData'][$fieldName]['value'] = $value;
			}
		}

		$this->writeCheckoutDataToSession();

		Controller::redirect(Environment::get('request').'#customerData');
	}

	/*
	 * Die per POST übergebenen Daten werden dem Checkout-Data-Objekt hinterlegt.
	 */
	private function processPaymentOrShippingMethodAdditionalData($arrPost, $arrForm, $arrFiles, $what = 'payment') {
		if ($what != 'payment' && $what != 'shipping') {
			return false;
		}
		
		$key = $what == 'payment' ? 'arrPaymentMethodAdditionalData' : 'arrShippingMethodAdditionalData';
		
		foreach ($arrPost as $fieldName => $value) {
			if (isset($this->arrCheckoutData[$key][$fieldName])) {
				$this->arrCheckoutData[$key][$fieldName]['value'] = $value;
			}
		}
		
		if ($what == 'payment') {

			// ### paymentMethod callback ########################
			$obj_paymentModule = System::importStatic('Merconis\Core\ls_shop_paymentModule', null, true);
			$obj_paymentModule->afterPaymentMethodAdditionalDataConfirm();
			// ###################################################

		}

		$this->writeCheckoutDataToSession();

		Controller::redirect(Environment::get('request').'#'.$what);
	}

	/*
	 * Diese Funktion wird über einen Hook aufgerufen, wenn ein Formularfeld geladen wird.
	 * Abhängig davon, um welches Formular es sich handelt, werden die passenden internen Funktionen aufgerufen.
	 */
	public function ls_shop_loadFormField(Widget $objWidget, $strForm, $arrForm) {
		if ($arrForm['id'] == $this->formCustomerDataID) {
			return $this->loadCustomerDataFormField($objWidget, $strForm, $arrForm);
		} else if ($arrForm['id'] == $this->formPaymentMethodAdditionalDataID) {
			return $this->loadPaymentOrShippingMethodAdditionalDataFormField($objWidget, $strForm, $arrForm, 'payment');
		} else if ($arrForm['id'] == $this->formShippingMethodAdditionalDataID) {
			return $this->loadPaymentOrShippingMethodAdditionalDataFormField($objWidget, $strForm, $arrForm, 'shipping');
		}
		
		return $objWidget;
	}
	
	/*
	 * Es wird geprüft, ob das Formularfeld vorausgefüllt werden soll. Dies ist der Fall, 
	 * wenn der Inhalt des Feldes bereits im Checkout-Data-Objekt hinterlegt ist.
	 */
	private function loadCustomerDataFormField(Widget $objWidget, $strForm, $arrForm) {
		return ls_shop_generalHelper::prefillFormField($objWidget, $this->arrCheckoutData['arrCustomerData']);
	}
	
	/*
	 * Es wird geprüft, ob das Formularfeld vorausgefüllt werden soll. Dies ist der Fall, 
	 * wenn der Inhalt des Feldes bereits im Checkout-Data-Objekt hinterlegt ist.
	 */
	private function loadPaymentOrShippingMethodAdditionalDataFormField(Widget $objWidget, $strForm, $arrForm, $what = 'payment') {
		if ($what != 'payment' && $what != 'shipping') {
			return $objWidget;
		}
		$key = $what == 'payment' ? 'arrPaymentMethodAdditionalData' : 'arrShippingMethodAdditionalData';
		return ls_shop_generalHelper::prefillFormField($objWidget, $this->arrCheckoutData[$key]);
	}

	/*
	 * Diese Funktion gibt das Formular für die Zahlungsmethoden zurück und nimmt auch die Validierung
	 * vor, falls Daten bereits gesendet wurden.
	 */
	private function getPaymentOrShippingMethodForm($what = 'payment') {
		if ($what != 'payment' && $what != 'shipping') {
			throw new \Exception('insufficient parameters given');
		}

		if (
				Input::get('selectPaymentOrShipping')
			&&	Input::get('selectPaymentOrShipping') === $what
			&&	Input::get('id')
		) {

			$this->arrCheckoutData['selected'.ucfirst($what).'Method'] = (int) Input::get('id');
			$this->arrCheckoutData['selected'.ucfirst($what).'MethodManually'] = true;

			if ($what == 'payment') {
				// ### paymentMethod callback ########################
				$obj_paymentModule = System::importStatic('Merconis\Core\ls_shop_paymentModule', null, true);
				$obj_paymentModule->afterPaymentMethodSelection();
				// ###################################################
			}

			if ($what == 'payment') {
				if (isset($GLOBALS['MERCONIS_HOOKS']['paymentOptionSelected']) && is_array($GLOBALS['MERCONIS_HOOKS']['paymentOptionSelected'])) {
					foreach ($GLOBALS['MERCONIS_HOOKS']['paymentOptionSelected'] as $mccb) {
						$objMccb = System::importStatic($mccb[0]);
						$objMccb->{$mccb[1]}((int) Input::get('id'));
					}
				}
			} else if ($what == 'shipping') {
				if (isset($GLOBALS['MERCONIS_HOOKS']['shippingOptionSelected']) && is_array($GLOBALS['MERCONIS_HOOKS']['shippingOptionSelected'])) {
					foreach ($GLOBALS['MERCONIS_HOOKS']['shippingOptionSelected'] as $mccb) {
						$objMccb = System::importStatic($mccb[0]);
						$objMccb->{$mccb[1]}((int) Input::get('id'));
					}
				}
			}

			$this->writeCheckoutDataToSession();

			Controller::redirect(\LeadingSystems\Helpers\getUrlWithoutParameters(array('selectPaymentOrShipping', 'id')));
		}

        $objMethod = Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_shipping_methods`
			WHERE		`id` = ?
		")
            ->execute($this->arrCheckoutData['selected'.ucfirst($what).'Method']);

        $first = $objMethod->fetchAssoc();

        if($first['notSelectable'] == 1){
            $this->arrCheckoutData['selected'.ucfirst($what).'Method'] = "";
        }

		$obj_templateForPaymentOrShippingSelection = new FrontendTemplate('template_paymentAndShippingSelect');
		$obj_templateForPaymentOrShippingSelection->arr_availableOptions = ls_shop_generalHelper::getPaymentOrShippingMethods($what);
		$obj_templateForPaymentOrShippingSelection->int_selectedOptionId = $this->arrCheckoutData['selected'.ucfirst($what).'Method'];
		$obj_templateForPaymentOrShippingSelection->str_selectWhat = $what;

		return $obj_templateForPaymentOrShippingSelection->parse();
	}

	/**
	 * Diese Funktion validiert die Checkout-Data, da das reine Validieren eines Formulars beim Absenden desselben
	 * nicht ausreichend ist. Es muss möglich sein, direkt vor dem endgültigen Bestellabschluss die vollständige
	 * Validität der Checkout-Data zu prüfen, damit Manipulationen verhindert werden können.
	 */
	private function validateCheckoutData() {
		$this->arrValidation = array(
			'blnCustomerDataIsValid' => $this->validateCustomerData(),
			'blnPaymentMethodDataIsValid' => $this->validatePaymentOrShippingMethodData('payment'),
			'blnShippingMethodDataIsValid' => $this->validatePaymentOrShippingMethodData('shipping'),
			'blnLoginStatusIsValid' => $this->validateLoginStatus(),
			'blnCartIsValid' => $this->validateCartStatus()
		);
        $this->checkSelectableStatus();
			
		$this->blnCheckoutDataIsValid =
				$this->arrValidation['blnCustomerDataIsValid']
			&&	$this->arrValidation['blnPaymentMethodDataIsValid']
			&&	$this->arrValidation['blnShippingMethodDataIsValid']
			&&	$this->arrValidation['blnLoginStatusIsValid']
			&&	$this->arrValidation['blnCartIsValid'];
	}
	
	/**
	 * Prüfen, ob der Warenkorb-Zustand valide ist.
	 */
	private function validateCartStatus() {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShopCart', []);

		if (!isset($session_lsShop['items']) || !is_array($session_lsShop['items']) || !count($session_lsShop['items'])) {
			return false;
		}
		return ls_shop_cartHelper::validateOrderPermissionOfCartPositions();
	}

	
	/**
	 * Prüfen der Validität des Login-Zustands. Der Zustand ist nur dann NICHT VALIDE, wenn die Option
	 * "withLogin" in den Shop-Grundeinstellungen gewählt ist und der User nicht eingeloggt ist. Der Zustand
	 * ist aber valide, wenn kein Login gefordert ist, der User aber dennoch angemeldet ist. Ein Zwangslogout
	 * ist hier natürlich nicht vorgesehen.
	 */
	private function validateLoginStatus() {
		switch($GLOBALS['TL_CONFIG']['ls_shop_allowCheckout']) {
			case 'both':
				return true;
				break;
				
			case 'withLogin':
				if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
					return true;
				} else {
					return false;
				}
				break;
				
			case 'withoutLogin':
				/*
				 * Auch wenn kein Login 
				 */
				return true;
				break;
		}
	}
	
	private function validateCustomerData() {
		return ls_shop_generalHelper::validateCollectedFormData($this->arrCheckoutData['arrCustomerData'], $this->formCustomerDataID);
	}

    private function checkSelectableStatus() {
        $this->arrCheckoutData['countNotSelectable'] = ls_shop_generalHelper::checkSelectableStatus();
    }
	
	/*
	 * This function checks whether or not a payment or shipping method should be preselected and if
	 * no payment or shipping function has been selected yet, selects it.
	 */
	private function preselectPaymentOrShippingMethod() {
		if (Input::post('isAjax')) {
			return;
		}

		$groupInfo = ls_shop_generalHelper::getGroupSettings4User();
		$blnReloadRequired = false;
		
		$cheapestPossiblePaymentMethodBefore = $this->arrCheckoutData['cheapestPossiblePaymentMethod'];
		$this->arrCheckoutData['cheapestPossiblePaymentMethod'] = ls_shop_generalHelper::getCheapestAvailableMethodID('payment');
		if ($cheapestPossiblePaymentMethodBefore != $this->arrCheckoutData['cheapestPossiblePaymentMethod']) {
			$blnReloadRequired = true;
		}
		
		$cheapestPossibleShippingMethodBefore = $this->arrCheckoutData['cheapestPossibleShippingMethod'];
		$this->arrCheckoutData['cheapestPossibleShippingMethod'] = ls_shop_generalHelper::getCheapestAvailableMethodID('shipping');
		if ($cheapestPossibleShippingMethodBefore != $this->arrCheckoutData['cheapestPossibleShippingMethod']) {
			$blnReloadRequired = true;
		}
		
		## fixEndlessRecursionOnNoCookiesError begin ##
		/*
		 * Skip the preselection if there are no items in the cart. If there are no items in the cart, there should be
		 * no need to have the preselection and if there are items in the cart, performing the preselection can't produce
		 * the cookie related endless recursion because cookies must be activated to have items in the cart.
		 * 
		 * To check whether there are items in the cart we access the cart session variable directly and do not use
		 * ls_shop_cartX because loading ls_shop_cartX would be too much overhead and could probably cause problems
		 * and to just make this simple check, we just don't need it.
		 */
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShopCart', []);

		if (!isset($session_lsShopCart['items']) || !count($session_lsShopCart['items'])) {
			$this->writeCheckoutDataToSession();
			return;
		}
		## fixEndlessRecursionOnNoCookiesError end ##
		
		## fixEndlessRecursionOnPaymentError begin ##
		/*
		 * Skip the preselection directly after the determination of the cheapest possible options
		 * if a payment or shipping error occured earlier. This way we prevent the endless recursion that would
		 * take place if the method that just threw the error would be selected again instantly.
		 */
		if (isset($session_lsShop['blnPaymentOrShippingErrorOccured']) && $session_lsShop['blnPaymentOrShippingErrorOccured']) {
			$this->writeCheckoutDataToSession();
			return;
		}
		## fixEndlessRecursionOnPaymentError end ##

		if (!$this->arrCheckoutData['selectedPaymentMethod'] || !$this->arrCheckoutData['selectedPaymentMethodManually']) {
			if (
					(!$this->arrCheckoutData['selectedPaymentMethod'] || $cheapestPossiblePaymentMethodBefore != $this->arrCheckoutData['cheapestPossiblePaymentMethod'])
				&&	isset($GLOBALS['TL_CONFIG']['ls_shop_autoSelectCheapestPossibleShippingAndPaymentMethods'])
				&&	$GLOBALS['TL_CONFIG']['ls_shop_autoSelectCheapestPossibleShippingAndPaymentMethods']) 
			{
				if ($this->arrCheckoutData['cheapestPossiblePaymentMethod']) {
					$this->arrCheckoutData['selectedPaymentMethod'] = $this->arrCheckoutData['cheapestPossiblePaymentMethod'];
					// ### paymentMethod callback ########################
					$obj_paymentModule = System::importStatic('Merconis\Core\ls_shop_paymentModule', null, true);
					$obj_paymentModule->afterPaymentMethodSelection();
					// ###################################################

					$blnReloadRequired = true;
				}
			} else if ($groupInfo['lsShopStandardPaymentMethod']) {
				if (ls_shop_generalHelper::checkIfPaymentMethodIsAllowed($groupInfo['lsShopStandardPaymentMethod'])) {
					$this->arrCheckoutData['selectedPaymentMethod'] = $groupInfo['lsShopStandardPaymentMethod'];
					$this->arrCheckoutData['selectedPaymentMethodManually'] = true;
					// ### paymentMethod callback ########################
					$obj_paymentModule = System::importStatic('Merconis\Core\ls_shop_paymentModule', null, true);
					$obj_paymentModule->afterPaymentMethodSelection();
					// ###################################################

					$blnReloadRequired = true;
				}
			}			
		}

		if (!$this->arrCheckoutData['selectedShippingMethod'] || !$this->arrCheckoutData['selectedShippingMethodManually']) {
			if (
					(!$this->arrCheckoutData['selectedShippingMethod'] || $cheapestPossibleShippingMethodBefore != $this->arrCheckoutData['cheapestPossibleShippingMethod'])
				&&	isset($GLOBALS['TL_CONFIG']['ls_shop_autoSelectCheapestPossibleShippingAndPaymentMethods'])
				&&	$GLOBALS['TL_CONFIG']['ls_shop_autoSelectCheapestPossibleShippingAndPaymentMethods'])
			{
				if ($this->arrCheckoutData['cheapestPossibleShippingMethod']) {
					$this->arrCheckoutData['selectedShippingMethod'] = $this->arrCheckoutData['cheapestPossibleShippingMethod'];

					$blnReloadRequired = true;
				}
			} else if ($groupInfo['lsShopStandardShippingMethod']) {
				if (ls_shop_generalHelper::checkIfShippingMethodIsAllowed($groupInfo['lsShopStandardShippingMethod'])) {
					$this->arrCheckoutData['selectedShippingMethod'] = $groupInfo['lsShopStandardShippingMethod'];
					$this->arrCheckoutData['selectedShippingMethodManually'] = true;

					$blnReloadRequired = true;
				}
			}			
		}

		$this->writeCheckoutDataToSession();

		if ($blnReloadRequired) {
			if (!System::getContainer()->get('merconis.routing.scope')->isBackend()) {
				if (!Environment::get('isAjaxRequest')) {
					Controller::reload();
				}
			}
		}
	}
	
	private function validatePaymentOrShippingMethodData($what = 'payment') {
		if ($what != 'payment' && $what != 'shipping') {
			throw new \Exception('insufficient parameters given');
		}

            /*
             * Wenn keine Zahlungs- bzw. Versandmethode gewählt ist, ist der Zustand auf jeden Fall NICHT valide
             */
            if (!$this->arrCheckoutData['selected'.ucfirst($what).'Method']) {
                $blnIsValid = false;
                return $blnIsValid;
            } else {

                $objMethod = Database::getInstance()->prepare("
                SELECT		*
                FROM		`tl_ls_shop_shipping_methods`
                WHERE		`id` = ?
                ")
                ->execute($this->arrCheckoutData['selected'.ucfirst($what).'Method']);

                $first = $objMethod->fetchAssoc();

                if($first['notSelectable'] == 1){
                    return false;
                }

			switch($what) {
				case 'payment':
					if (!ls_shop_generalHelper::checkIfPaymentMethodIsAllowed($this->arrCheckoutData['selectedPaymentMethod'])) {
						$this->arrCheckoutData['selectedPaymentMethod'] = '';
						if (!System::getContainer()->get('merconis.routing.scope')->isBackend()) {
							if (!Environment::get('isAjaxRequest')) {
								$this->writeCheckoutDataToSession();
								Controller::reload();
							}
						}
					}
					break;

				case 'shipping':
					if (!ls_shop_generalHelper::checkIfShippingMethodIsAllowed($this->arrCheckoutData['selectedShippingMethod'])) {
						$this->arrCheckoutData['selectedShippingMethod'] = '';
						if (!System::getContainer()->get('merconis.routing.scope')->isBackend()) {
							if (!Environment::get('isAjaxRequest')) {
								$this->writeCheckoutDataToSession();
								Controller::reload();
							}
						}
					}
					break;
			}
		}
		
		if ($what == 'payment') {

			// ### paymentMethod callback ########################
			$obj_paymentModule = System::importStatic('Merconis\Core\ls_shop_paymentModule', null, true);
			if (!$obj_paymentModule->statusOkayToRedirectToCheckoutFinish()) {
				$blnIsValid = false;
				return $blnIsValid;				
			}
			// ###################################################

		}
		
		$formIDProperty = 'form'.ucfirst($what).'MethodAdditionalDataID';
		$arrAdditionalDataKey = $what == 'payment' ? 'arrPaymentMethodAdditionalData' : 'arrShippingMethodAdditionalData';

		$blnIsValid = true;

		if (!$this->{$formIDProperty}) {
			return $blnIsValid;
		}
		
		$blnIsValid = ls_shop_generalHelper::validateCollectedFormData($this->arrCheckoutData[$arrAdditionalDataKey], $this->{$formIDProperty});

		$this->writeCheckoutDataToSession();

		return $blnIsValid;
	}

	private function getValidationErrorsOutput() {
		$arrErrorOutputs = array();
		if (!$this->arrValidation['blnCustomerDataIsValid']) {
			$arrErrorOutputs[] = $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText028'];
		}
		if (!$this->arrValidation['blnPaymentMethodDataIsValid']) {
			$arrErrorOutputs[] = $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText029'];
		}
		if (!$this->arrValidation['blnShippingMethodDataIsValid']) {
			$arrErrorOutputs[] = $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText030'];
		}
		if (!$this->arrValidation['blnLoginStatusIsValid']) {
			$arrErrorOutputs[] = $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText031'];
		}
		return $arrErrorOutputs;
	}
	
	public function resetSelectedPaymentAndShippingMethod() {
		$this->resetSelectedPaymentMethod();
		$this->resetSelectedShippingMethod();
		$this->preselectPaymentOrShippingMethod();
	}
	
	public function resetSelectedPaymentMethod() {
		$this->arrCheckoutData['selectedPaymentMethod'] = '';
		## fixEndlessRecursionOnPaymentError begin ##
		$this->arrCheckoutData['cheapestPossiblePaymentMethod'] = '';
		## fixEndlessRecursionOnPaymentError end ##
		$this->arrCheckoutData['arrPaymentMethodAdditionalData'] = array();

		$this->writeCheckoutDataToSession();
	}
	
	public function resetSelectedShippingMethod() {
		$this->arrCheckoutData['selectedShippingMethod'] = '';
		## fixEndlessRecursionOnPaymentError begin ##
		$this->arrCheckoutData['cheapestPossibleShippingMethod'] = '';
		## fixEndlessRecursionOnPaymentError end ##
		$this->arrCheckoutData['arrShippingMethodAdditionalData'] = array();

		$this->writeCheckoutDataToSession();
	}
	
	/*
	 * Diese Funktion wird über einen Hook nach erfolgtem Login ausgeführt und
	 * hinterlegt die nun verfügbaren Benutzerdaten im Checkout-Data-Array
	 */
	public function ls_shop_postLogin (FrontendUser $objUser) {
		/** @var PageModel $objPage */
		global $objPage;

		$this->arrCheckoutData['loggedInData']['userID'] = $objUser->id;
		
		/*
		 * make sure that the correct information for the just now logged in user is available
		 */
		ls_shop_generalHelper::getGroupSettings4User(true, $objUser);
		$this->getFormIDs();
		$this->analyzeRequiredFields();

		foreach ($this->arrCheckoutData['arrCustomerData'] as $fieldName => $arrFieldInfo) {
			if (isset($objUser->{$fieldName}) && $this->checkIfValueAllowed($objUser->{$fieldName}, $arrFieldInfo)) {
				$this->arrCheckoutData['arrCustomerData'][$fieldName]['value'] = $objUser->{$fieldName};
			}
		}

        if (isset($GLOBALS['MERCONIS_HOOKS']['afterCustomerDataHasBeenPrefilledAfterLogin']) && is_array($GLOBALS['MERCONIS_HOOKS']['afterCustomerDataHasBeenPrefilledAfterLogin'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['afterCustomerDataHasBeenPrefilledAfterLogin'] as $mccb) {
                $objMccb = System::importStatic($mccb[0]);
                $this->arrCheckoutData = $objMccb->{$mccb[1]}($objUser, $this->arrCheckoutData);
            }
        }

		$this->writeCheckoutDataToSession();

		$this->resetSelectedPaymentAndShippingMethod();

		/*
		 * Sofern die aktuell aufgerufene Seite die Warenkorb-Seite ist, findet eine Weiterleitung
		 * auf die selbe Seite mit passendem Anchor statt.
		 */
		if (($objPage->id ?? null) == ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id')) {
			Controller::redirect(Environment::get('request').'#customerData');
		}
	}
	
	/*
	 * Diese Funktion prüft, ob ein übergebener Wert ein gültiger Feldwert entsprechend einer
	 * übergebenen fieldInfo ist. Aktuell findet nur eine Prüfung bei select- und radio-Feldern
	 * statt, es wird also keine wirkliche Validierung durchgeführt sondern lediglich geprüft,
	 * ob der Feldwert als Option enthalten ist.
	 */
	private function checkIfValueAllowed($fieldValue, $arrFieldInfo) {
		if ($arrFieldInfo['arrData']['type'] == 'select' || $arrFieldInfo['arrData']['type'] == 'radio') {
			$arrOptions = StringUtil::deserialize($arrFieldInfo['arrData']['options']);
			if (!is_array($arrOptions) || !count($arrOptions)) {
				return false;
			}
			
			foreach ($arrOptions as $arrOption) {
				if ($fieldValue == $arrOption['value']) {
					return true;
				}
			}
			
			return false;
		} else {
			return true;
		}
	}
}
