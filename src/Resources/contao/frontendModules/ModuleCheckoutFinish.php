<?php

namespace Merconis\Core;
use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;

class ModuleCheckoutFinish extends \Module {
	protected $objCart = null;
	private $orderNr = null;
	
	public function generate() {
		if (FE_USER_LOGGED_IN) {
			$this->import('FrontendUser', 'User');
		}
		
		if (TL_MODE == 'BE') {
			$objTemplate = new \BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS - Bestellabschluss ###';
			return $objTemplate->parse();
		}
		
		/*
		 * Sollte der Zustand der Checkout-Daten und des Warenkorbs (Mindestwarenwert) nicht okay sein, so wird
		 * zur Warenkorb-Seite gesprungen.
		 */
		if (!ls_shop_generalHelper::check_minimumOrderValueIsReached() || !ls_shop_checkoutData::getInstance()->checkoutDataIsValid) {
			ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages');
			$this->redirect($GLOBALS['merconis_globals']['ls_shop_cartPagesUrl']);
		}
		
		return parent::generate();
	}
	
	public function compile() {
		$obj_paymentModule = new ls_shop_paymentModule();

		// ### paymentMethod callback ########################
		$obj_paymentModule->beforeCheckoutFinish();
		// ###################################################
				
		if ($obj_paymentModule->checkoutFinishAllowed()) {
			
			/*
			 * Anfang: Verfügbarkeitsprüfung
			 * 
			 * Prüfen, ob alle Positionen des Warenkorbs noch in ausreichender Menge verfügbar sind.
			 * Wenn ja, reduzieren des Lagerbestands für die einzelnen Positionen um die im Warenkorb enthaltende Menge.
			 * Dann Abschluss der Bestellung, also hier einfach weiter abarbeiten.
			 * WICHTIG: Vor Prüfung der Verfügbarkeit die Tabellen 'tl_ls_shop_product' und 'tl_ls_shop_variant' sperren, nach der
			 * Reduzierung der Menge wieder entsperren.
			 * Reicht die Menge einer Position nicht aus, so wird die im Warenkorb hinterlegte Menge reduziert
			 * und der Bestellabschluss abgebrochen und mit einer entsprechenden Meldung wieder auf die Checkout-Seite
			 * geleitet.
			 */
			$useTableLock = true;
			if ($useTableLock) {
				\Database::getInstance()->prepare("
					LOCK TABLES
									`tl_files` READ,
									`tl_layout` READ,
									`tl_form` READ,
									`tl_form_field` READ,
									`tl_ls_shop_configurator` READ,
									`tl_page` READ,
									`tl_ls_shop_coupon` WRITE,
									`tl_ls_shop_product` WRITE,
									`tl_ls_shop_variant` WRITE,
									`tl_ls_shop_delivery_info` READ,
									`tl_ls_shop_steuersaetze` READ,
									`tl_ls_shop_attributes` READ,
									`tl_ls_shop_attribute_values` READ
				")->execute();
			}
			
			$cartPositionsStockSufficient = ls_shop_cartHelper::checkCartPositionsStockSufficient();
			
			$bln_couponsAllowed = true;
			if (is_array(ls_shop_cartX::getInstance()->calculation['couponValues'])) {
				foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $couponID => $arrCouponValues) {
					$bln_couponsAllowed = ls_shop_cartHelper::check_couponIsValid($couponID);
					if (!$bln_couponsAllowed) {
						ls_shop_msg::setMsg(array(
							'class' => 'couponsNotAllowed',
							'reference' => 'couponsGeneral'
						));
						break;
					}
				}
			}

			if (!$cartPositionsStockSufficient || !$bln_couponsAllowed) {
				/*
				 * Lagerbestand für mindestens eine Position nicht ausreichend, daher Bestellabschluss abbrechen und zurück zur Checkout-Seite
				 */
				if ($useTableLock) {
					\Database::getInstance()->prepare("UNLOCK TABLES")->execute();
				}
				ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages');
				$urlCart = $GLOBALS['merconis_globals']['ls_shop_cartPagesUrl'];
				$urlCart = $urlCart.(strpos($urlCart, '?') !== false ? '&' : '?').'step=cart';
				$this->redirect($urlCart);
			} else {
				/*
				 * Lagerbestand für alle Positionen ausreichend, die Bestellung wird also ausgeführt und an dieser
				 * Stelle erfolgt nun die Reduzierung des Lagerbestands für die einzelnen Positionen
				 */
				ls_shop_cartHelper::reduceStockForCartPositions();
				
				/*
				 * At this point we need to reduce the numAvailable value
				 * of the coupon(s).
				 */
				if (is_array(ls_shop_cartX::getInstance()->calculation['couponValues'])) {
					foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $couponID => $arrCouponValues) {
						$obj_dbquery = \Database::getInstance()->prepare("
							UPDATE		`tl_ls_shop_coupon`
							SET			`numAvailable` = `numAvailable` - 1
							WHERE		`id` = ?
								AND		`limitNumAvailable` = '1'
								AND		`numAvailable` > 0
						")
						->limit(1)
						->execute($couponID);
					}
				}
			}
			
			if ($useTableLock) {
				\Database::getInstance()->prepare("UNLOCK TABLES")->execute();
			}
			/*
			 * Ende Verfügbarkeitsprüfung
			 */
			
			ls_shop_cartHelper::countSalesForCartPositions();
		
		
			/*
			 * Erzeugen der Bestellnummer
			 */
			$this->generateOrderNr();
			
			/*
			 * Generieren der zusammengefassten Bestellung als HTML- und Text-Version
			 */
			$order = $this->createOrder();

			/*
			 * Speichern der Bestellung in der DB
			 */
			$orderIdInDb = $this->saveOrderInDB($order);
			
			if (isset($GLOBALS['MERCONIS_HOOKS']['afterCheckout']) && is_array($GLOBALS['MERCONIS_HOOKS']['afterCheckout'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['afterCheckout'] as $mccb) {
					$objMccb = \System::importStatic($mccb[0]);
					$objMccb->{$mccb[1]}($orderIdInDb, $order);
				}
			}
			
			/*
			 * Sending the orderConfirmation and orderNotice messages
			 */
			$objOrderMessages = new ls_shop_orderMessages($orderIdInDb, 'asOrderConfirmation', 'sendWhen');
			$objOrderMessages->sendMessages();

			$objOrderMessages = new ls_shop_orderMessages($orderIdInDb, 'asOrderNotice', 'sendWhen', ls_shop_languageHelper::getFallbackLanguage());
			$objOrderMessages->sendMessages();
		

			unset($_SESSION['lsShopCart']);
			unset($_SESSION['lsShop']['configurator']);
			unset($_SESSION['lsShop']['customizerStorage']);

			$afterCheckoutUrl = ls_shop_languageHelper::getLanguagePage('ls_shop_afterCheckoutPages');
			$afterCheckoutUrlWithOih = $afterCheckoutUrl.(preg_match('/\?/', $afterCheckoutUrl) ? '&' : '?').'oih='.$order['orderIdentificationHash'];
			
			// Saving the oix for the oih in the session to verify that this session is the one that can display the order on the after checkout page
			$oix = ls_shop_generalHelper::encodeOix($orderIdInDb);
			$_SESSION['lsShop']['oix2oih'][$oix] = $order['orderIdentificationHash'];


			// ### paymentMethod callback ########################
			$obj_paymentModule->afterCheckoutFinish($orderIdInDb, $order, $afterCheckoutUrl, $oix);
			// ###################################################	

			$str_paymentAfterCheckoutUrl = ls_shop_languageHelper::getLanguagePage('ls_shop_paymentAfterCheckoutPages');
			$str_paymentAfterCheckoutUrlWithOih = $str_paymentAfterCheckoutUrl.(preg_match('/\?/', $str_paymentAfterCheckoutUrl) ? '&' : '?').'oih='.$order['orderIdentificationHash'];

			// ### paymentMethod callback ########################
			$bln_usePaymentAfterCheckoutPage = $obj_paymentModule->check_usePaymentAfterCheckoutPage($orderIdInDb, $order);
			// ###################################################

			if (isset($GLOBALS['MERCONIS_HOOKS']['afterCheckoutBeforeRedirect']) && is_array($GLOBALS['MERCONIS_HOOKS']['afterCheckoutBeforeRedirect'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['afterCheckoutBeforeRedirect'] as $mccb) {
				    $objMccb = \System::importStatic($mccb[0]);
				    /*
				     * We have to get the most up to date order record
				     */
				    $objMccb->{$mccb[1]}($orderIdInDb, ls_shop_generalHelper::getOrder($orderIdInDb));
				}
			}

			if ($bln_usePaymentAfterCheckoutPage) {
				$this->redirect($str_paymentAfterCheckoutUrlWithOih);
			} else {
				$this->redirect($afterCheckoutUrlWithOih);
			}
		} else {
			// ### paymentMethod callback ########################
			$obj_paymentModule->redirectToErrorPage('checkoutFinish not allowed');
			// ###################################################
		}
	}

	public function generateOrderNr() {
		$useTableLock = true;
		if ($useTableLock) {
			\Database::getInstance()->prepare("
				LOCK TABLES
				`tl_ls_shop_orders` WRITE
			")->execute();
		}
		/*
		 * Auslesen der Informationen zur letzten Bestellung
		 */
		$objLastOrder = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_orders`
			ORDER BY	`id` DESC
		")
		->limit(1)
		->execute();
		
		/*
		 * Make sure that we load absolutely fresh config values
		 */
		\Config::preload();
		
		$orderNr = html_entity_decode($GLOBALS['TL_CONFIG']['ls_shop_orderNrString']);
		
		$timestampLastOrder = false;
		if ($objLastOrder->numRows) {
			$objLastOrder->first();
			$timestampLastOrder = $objLastOrder->orderDateUnixTimestamp; 
		}
		
		/*
		 * Zurücksetzen des Zählers, sofern durch den angegebenen Rücksetz-Zyklus nötig
		 */
		$resetCounter = false;
		if (isset($GLOBALS['TL_CONFIG']['ls_shop_orderNrRestartCycle'])) {
			if (!$timestampLastOrder) {
				/*
				 * Ist ein Rücksetzzyklus angegeben und existiert keine letzte Bestellung ($timestampLastOrder also false),
				 * so wird auf jeden Fall zurückgesetzt, da also auf jeden Fall ein neues Jahr, ein neuer Monat oder was
				 * auch immer beginnt.
				 */
				$resetCounter = true;
			} else {
				/*
				 * Existiert eine letzte Bestellung und damit ein Timestamp der letzten Bestellung,
				 * so wird geprüft, ob abhängig vom eingestellten Rücksetzzyklus ein neuer Zeitraum
				 * beginnt und der Zähler also zurückgesetzt werden muss.
				 */
				switch($GLOBALS['TL_CONFIG']['ls_shop_orderNrRestartCycle']) {
					case 'day':
						// Prüfen, ob sich das Jahr seit der letzten Bestellung geändert hat
						if (date('d') != date('d', $timestampLastOrder)) {
							$resetCounter = true;
						}
						// kein break, da die darunterfolgenden Prüfungen auch noch stattfinden sollen

					case 'week':
						// Prüfen, ob sich das Jahr seit der letzten Bestellung geändert hat
						if (date('W') != date('W', $timestampLastOrder)) {
							$resetCounter = true;
						}
						// kein break, da die darunterfolgenden Prüfungen auch noch stattfinden sollen
						
					case 'month':
						// Prüfen, ob sich das Jahr seit der letzten Bestellung geändert hat
						if (date('m') != date('m', $timestampLastOrder)) {
							$resetCounter = true;
						}
						// kein break, da die darunterfolgenden Prüfungen auch noch stattfinden sollen
						
					case 'year':
						// Prüfen, ob sich das Jahr seit der letzten Bestellung geändert hat
						if (date('Y') != date('Y', $timestampLastOrder)) {
							$resetCounter = true;
						}
						break;
				}				
			}
		}

		/*
		 * Ermitteln des nächsten Zählers, beim Startwert anfangen, wenn entweder eine der Rücksetzbedingungen
		 * true ergab oder wenn in der localconfig noch kein Counter eingetragen oder dieser 0 bzw. false ist
		 */
		if ($resetCounter || !isset($GLOBALS['TL_CONFIG']['ls_shop_orderNrCounter']) || !$GLOBALS['TL_CONFIG']['ls_shop_orderNrCounter']) {
			$nextCounter = $GLOBALS['TL_CONFIG']['ls_shop_orderNrStart'] ? $GLOBALS['TL_CONFIG']['ls_shop_orderNrStart'] : 1;
		} else {
			$nextCounter = $GLOBALS['TL_CONFIG']['ls_shop_orderNrCounter'] + 1;
		}
		
		/*
		 * Eintragen des ermittelten Zählers in localconfig
		 */
		$this->Config->update("\$GLOBALS['TL_CONFIG']['ls_shop_orderNrCounter']", $nextCounter);
		$this->Config->save();
		if ($useTableLock) {
			\Database::getInstance()->prepare("UNLOCK TABLES")->execute();
		}
		
		/*
		 * Ermitteln der Datumsplatzhalter und ersetzen
		 * derselben
		 */
		preg_match_all('/\{\{date:(.*)\}\}/siU', $orderNr, $matches);
		foreach ($matches[0] as $key => $match) {
			$orderNr = preg_replace('/'.preg_quote($match).'/siU', date($matches[1][$key]), $orderNr);
		}
		
		/*
		 * Ersetzen des Zähler-Platzhalters
		 */

        //mit parameter
        preg_match_all('/\{\{counter:(.*)\}\}/siU', $orderNr, $matches);
        foreach ($matches[0] as $key => $match) {
            $orderNr = preg_replace('/'.preg_quote($match).'/siU', str_pad($nextCounter, $matches[1][$key], "0", STR_PAD_LEFT), $orderNr);
        }

        //ohne parameter
        $orderNr = preg_replace('/\{\{counter\}\}/siU', $nextCounter, $orderNr);

		$this->orderNr = $orderNr;
	}
	
	public function createOrder() {
		$obj_paymentModule = new ls_shop_paymentModule();
		$obj_shippingModule = new ls_shop_shippingModule();

		/** @var \PageModel $objPage */
		global $objPage;

		$customerGroupInfo = ls_shop_generalHelper::getGroupSettings4User();
		
		$arrStatusValues = ls_shop_generalHelper::getStatusValues();


		$arrData = ls_shop_checkoutData::getInstance()->arrCheckoutData["arrCustomerData"]; // später wieder überschreiben aktuell wird es nur geholt

		//get formID from first Element for the arrData, this is the form that needs to be checked
		$formID = $arrData[array_key_first($arrData)]["arrData"]["pid"];

		$objFormFields = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_form_field`
				WHERE		`pid` = ?
					AND		`invisible` != 1
				ORDER BY	`sorting`
			")
			->execute($formID);

		if (!$objFormFields->numRows) {
			return array();
		}

		$arr_formfieldData = $objFormFields->fetchAllAssoc();

		$currentFieldset = null;

		//$arr_formfieldData has all databasefields including fieldset, $arrData does not have have fieldset
		foreach ($arr_formfieldData as $formfield){

			//save currentFieldset -> used later to check if it needs to check for dependencys
			if($formfield['type'] == 'fieldsetStart'){
				$currentFieldset = $formfield;
			}

			//delete currentFieldset
			if($formfield['type'] == 'fieldsetStop'){
				$currentFieldset = null;
			}

			foreach ($arrData as $key=>$currentFormfield){
				//check if formfield from $arr_formfieldData is current formfield from $arrData to get there value
				if($formfield['id'] == $currentFormfield['arrData']['id']) {

					//loop for every arrData and check if it is dependet to anything
					foreach ($arrData as $formfieldDependentcy) {

						if($formfieldDependentcy['arrData']['type'] == 'checkbox' && $formfieldDependentcy['value'] == ""){
							$formfieldDependentcy['value'] = "0";
						}

						if (
							(   //check dependent to a formfield and if condition is met, if yes delete value
								$currentFormfield['arrData']['lsShop_ShowOnConditionField'] == $formfieldDependentcy['arrData']['id'] &&
								(
									$currentFormfield['arrData']['lsShop_ShowOnConditionValue'] != $formfieldDependentcy['value'] &&
									$currentFormfield['arrData']['lsShop_ShowOnConditionBoolean'] == "" //unchecked
									||
									$currentFormfield['arrData']['lsShop_ShowOnConditionValue'] == $formfieldDependentcy['value'] &&
									$currentFormfield['arrData']['lsShop_ShowOnConditionBoolean'] == 1 //checked
								)
							)
							||
							(    //check dependent to a fieldset and if condition is met, if yes delete value
								$currentFieldset != null && $currentFieldset['lsShop_ShowOnConditionField'] == $formfieldDependentcy['arrData']['id'] &&
								(
									$currentFieldset['lsShop_ShowOnConditionValue'] != $formfieldDependentcy['value'] &&
									$currentFieldset['lsShop_ShowOnConditionBoolean'] == "" //unchecked
									||
									$currentFieldset['lsShop_ShowOnConditionValue'] == $formfieldDependentcy['value'] &&
									$currentFieldset['lsShop_ShowOnConditionBoolean'] == 1 //checked
								)
							)
						) {
							$arrData[$key]['value'] = '';
						}
					}
				}
			}
		}

		ls_shop_checkoutData::getInstance()->arrCheckoutData["arrCustomerData"] = $arrData;


		
		$arrOrder = array(
			'orderIdentificationHash' => sha1(microtime().rand(1,99999999)).md5($this->orderNr).md5(time()), // no language
			'orderNr' => $this->orderNr, // no language
			'orderDateUnixTimestamp' => time(), // no language
			'orderDate' => date("Y-m-d H:i:s"), // no language
			'customerNr' => FE_USER_LOGGED_IN ? $this->User->id : 0, // no language
			'customerLanguage' => $objPage->language, // no language
			'customerInfo' => array(
				'personalData' => $this->createShopLanguageArray(ls_shop_checkoutData::getInstance()->arrCustomerDataReview), // shop language
				'personalData_originalOptionValues' => ls_shop_checkoutData::getInstance()->arrCustomerDataReviewOnlyOriginalOptionValues, // no language
				'personalData_customerLanguage' => $this->createCustomerLanguageArray(ls_shop_checkoutData::getInstance()->arrCustomerDataReview), // customer language
				'personalDataReview_customerLanguage' => \Controller::replaceInsertTags(ls_shop_checkoutData::getInstance()->customerDataReview), // customer language

				'paymentData' => $this->createShopLanguageArray(ls_shop_checkoutData::getInstance()->arrPaymentMethodAdditionalDataReview), // shop language
				'paymentData_originalOptionValues' => ls_shop_checkoutData::getInstance()->arrPaymentMethodAdditionalDataReviewOnlyOriginalOptionValues, // no language
				'paymentData_customerLanguage' => $this->createCustomerLanguageArray(ls_shop_checkoutData::getInstance()->arrPaymentMethodAdditionalDataReview), // shop language
				'paymentDataReview_customerLanguage' => \Controller::replaceInsertTags(ls_shop_checkoutData::getInstance()->paymentMethodAdditionalDataReview), // customer language

				'shippingData' => $this->createShopLanguageArray(ls_shop_checkoutData::getInstance()->arrShippingMethodAdditionalDataReview), // shop language
				'shippingData_originalOptionValues' => ls_shop_checkoutData::getInstance()->arrShippingMethodAdditionalDataReviewOnlyOriginalOptionValues, // no language
				'shippingData_customerLanguage' => $this->createCustomerLanguageArray(ls_shop_checkoutData::getInstance()->arrShippingMethodAdditionalDataReview), // shop language
				'shippingDataReview_customerLanguage' => \Controller::replaceInsertTags(ls_shop_checkoutData::getInstance()->shippingMethodAdditionalDataReview), // customer language

				'memberGroupInfo' => array(
					'id' => $customerGroupInfo['id'], // no language
					'name' => $customerGroupInfo['name'] // shop language 
				)
			),
			
			/*
			 * Store some environment information
			 */
			'currency' => $GLOBALS['TL_CONFIG']['ls_shop_currencyCode'], // no language
			'weightUnit' => $GLOBALS['TL_CONFIG']['ls_shop_weightUnit'], // no language
			'userOutputPriceType' => ls_shop_generalHelper::getOutputPriceType() == 'netto' ? 'net' : 'gross', // no language
			'inputPriceType' => $GLOBALS['TL_CONFIG']['ls_shop_priceType'] == 'netto' ? 'net' : 'gross', // no language
			'numDecimalsPrice' => $GLOBALS['TL_CONFIG']['ls_shop_numDecimals'], // no language
			'numDecimalsWeight' => $GLOBALS['TL_CONFIG']['ls_shop_numDecimalsWeight'], // no language
			'decimalsSeparator' => $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'], // no language
			'thousandsSeparator' => $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'], // no language
			'miscData' => array( // no language
				'pageDNS' => $objPage->domain ?: '',
				'host' => \Environment::get('host'),
				'domain' => ($objPage->rootUseSSL ? 'https://' : 'http://') . ($objPage->domain ?: \Environment::get('host')) . TL_PATH . '/',
				'languageUsedOnCheckout' => $objPage->language,
				'ipAnonymized' => \System::anonymizeIp(\Environment::get('ip'))
			),
			
			'totalValueOfGoods' => ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'][0], // no language
			'totalValueOfGoodsTaxedWith' => array(), // no language
			'noVATBecauseOfEnteredIDs' => ls_shop_generalHelper::checkVATID() ? '1' : '', // no language
			'VATIDValidationResult' => ls_shop_generalHelper::getVATIDValidationResult(isset(ls_shop_checkoutData::getInstance()->arrCustomerDataReview['VATID']) ? ls_shop_checkoutData::getInstance()->arrCustomerDataReview['VATID'] : false), // no language
			'totalWeightOfGoods' => ls_shop_cartX::getInstance()->calculation['totalWeightOfGoods'], // no language
			'couponsUsed' => array(), // no language
			'couponsTotalValue' => 0, // no language
			'paymentMethod' => array(), // no language 
			'shippingMethod' => array(), // no language
			'total' => ls_shop_cartX::getInstance()->calculation['total'][0], // no language
			'totalTaxedWith' => array(), // no language
			'taxTotal' => ls_shop_cartX::getInstance()->calculation['tax'][0], // no language
			'tax' => array(), // no language
			'taxInclusive' => ls_shop_cartX::getInstance()->calculation['taxInclusive'] ? '1' : '', // no language
			'invoicedAmount' => ls_shop_cartX::getInstance()->calculation['invoicedAmount'], // no language
			'invoicedAmountNet' => ls_shop_cartX::getInstance()->calculation['invoicedAmountNet'], // no language
			'items' => array(), // no language
			'status01' => ($arrStatusValues['status01'][0] ?? null) ? $arrStatusValues['status01'][0] : '', // no language
			'status02' => ($arrStatusValues['status02'][0] ?? null) ? $arrStatusValues['status02'][0] : '', // no language
			'status03' => ($arrStatusValues['status03'][0] ?? null) ? $arrStatusValues['status03'][0] : '', // no language
			'status04' => ($arrStatusValues['status04'][0] ?? null) ? $arrStatusValues['status04'][0] : '', // no language
			'status05' => ($arrStatusValues['status05'][0] ?? null) ? $arrStatusValues['status05'][0] : '' // no language
		);
		
		$tmpObjPageLanguage = $objPage->language;
		$objPage->language = ls_shop_languageHelper::getFallbackLanguage();
		
		$arrOrder['customerInfo']['personalDataReview'] = \Controller::replaceInsertTags(ls_shop_checkoutData::getInstance()->customerDataReview); // shop language
		$arrOrder['customerInfo']['paymentDataReview'] = \Controller::replaceInsertTags(ls_shop_checkoutData::getInstance()->paymentMethodAdditionalDataReview); // shop language
		$arrOrder['customerInfo']['shippingDataReview'] = \Controller::replaceInsertTags(ls_shop_checkoutData::getInstance()->shippingMethodAdditionalDataReview); // shop language
		
		$objPage->language = $tmpObjPageLanguage;
		
		if (is_array(ls_shop_cartX::getInstance()->calculation['paymentFee'])) {
			$arrOrder['paymentMethod']['title'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['paymentFee']['info']['id'], "tl_ls_shop_payment_methods_languages", array('title'), array(ls_shop_languageHelper::getFallbackLanguage())); // shop language
			$arrOrder['paymentMethod']['title_customerLanguage'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['paymentFee']['info']['id'], "tl_ls_shop_payment_methods_languages", array('title'), array($objPage->language)); // customer language
			$arrOrder['paymentMethod']['infoAfterCheckout'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['paymentFee']['info']['id'], "tl_ls_shop_payment_methods_languages", array('infoAfterCheckout'), array(ls_shop_languageHelper::getFallbackLanguage())); // shop language
			$arrOrder['paymentMethod']['infoAfterCheckout_customerLanguage'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['paymentFee']['info']['id'], "tl_ls_shop_payment_methods_languages", array('infoAfterCheckout'), array($objPage->language)); // customer language
			$arrOrder['paymentMethod']['additionalInfo'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['paymentFee']['info']['id'], "tl_ls_shop_payment_methods_languages", array('additionalInfo'), array(ls_shop_languageHelper::getFallbackLanguage())); // shop language
			$arrOrder['paymentMethod']['additionalInfo_customerLanguage'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['paymentFee']['info']['id'], "tl_ls_shop_payment_methods_languages", array('additionalInfo'), array($objPage->language)); // customer language
			$arrOrder['paymentMethod']['id'] = ls_shop_cartX::getInstance()->calculation['paymentFee']['info']['id']; // no language
			$arrOrder['paymentMethod']['alias'] = ls_shop_cartX::getInstance()->calculation['paymentFee']['info']['alias']; // no language
			$arrOrder['paymentMethod']['feeInfo_customerLanguage'] = ls_shop_cartX::getInstance()->calculation['paymentFee']['info']['feeInfo']; // customer language
			$arrOrder['paymentMethod']['moduleReturnData'] = $obj_paymentModule->getPaymentInfo(); // language depends on module behaviour, most likely shop language
			$arrOrder['paymentMethod']['amount'] = ls_shop_cartX::getInstance()->calculation['paymentFee'][0]; // no language
			$arrOrder['paymentMethod']['amountTaxedWith'] = array(); // no language
			foreach (ls_shop_cartX::getInstance()->calculation['paymentFee'] as $taxClassID => $value) {
				if ($taxClassID == 0 || $taxClassID == 'info') {
					continue;
				}
				$arrOrder['paymentMethod']['amountTaxedWith'][$taxClassID] = array(
					'taxRate' => ls_shop_generalHelper::getCurrentTax($taxClassID), // no language
					'amountTaxedHerewith' => $value // no language
				);
			}
		}
		
		if (is_array(ls_shop_cartX::getInstance()->calculation['shippingFee'])) {
			$arrOrder['shippingMethod']['title'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['shippingFee']['info']['id'], "tl_ls_shop_shipping_methods_languages", array('title'), array(ls_shop_languageHelper::getFallbackLanguage())); // shop language
			$arrOrder['shippingMethod']['title_customerLanguage'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['shippingFee']['info']['id'], "tl_ls_shop_shipping_methods_languages", array('title'), array($objPage->language)); // customer language
			$arrOrder['shippingMethod']['infoAfterCheckout'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['shippingFee']['info']['id'], "tl_ls_shop_shipping_methods_languages", array('infoAfterCheckout'), array(ls_shop_languageHelper::getFallbackLanguage())); // shop language
			$arrOrder['shippingMethod']['infoAfterCheckout_customerLanguage'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['shippingFee']['info']['id'], "tl_ls_shop_shipping_methods_languages", array('infoAfterCheckout'), array($objPage->language)); // customer language
			$arrOrder['shippingMethod']['additionalInfo'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['shippingFee']['info']['id'], "tl_ls_shop_shipping_methods_languages", array('additionalInfo'), array(ls_shop_languageHelper::getFallbackLanguage())); // shop language
			$arrOrder['shippingMethod']['additionalInfo_customerLanguage'] = ls_shop_languageHelper::getMultiLanguage(ls_shop_cartX::getInstance()->calculation['shippingFee']['info']['id'], "tl_ls_shop_shipping_methods_languages", array('additionalInfo'), array($objPage->language)); // customer language
			$arrOrder['shippingMethod']['id'] = ls_shop_cartX::getInstance()->calculation['shippingFee']['info']['id']; // no language
			$arrOrder['shippingMethod']['alias'] = ls_shop_cartX::getInstance()->calculation['shippingFee']['info']['alias']; // no language
			$arrOrder['shippingMethod']['feeInfo_customerLanguage'] = ls_shop_cartX::getInstance()->calculation['shippingFee']['info']['feeInfo']; // customer language
			$arrOrder['shippingMethod']['moduleReturnData'] = $obj_shippingModule->getShippingInfo(); // language depends on module behaviour, most likely shop language
			$arrOrder['shippingMethod']['amount'] = ls_shop_cartX::getInstance()->calculation['shippingFee'][0]; // no language
			$arrOrder['shippingMethod']['amountTaxedWith'] = array(); // no language
			foreach (ls_shop_cartX::getInstance()->calculation['shippingFee'] as $taxClassID => $value) {
				if ($taxClassID == 0 || $taxClassID == 'info') {
					continue;
				}
				$arrOrder['shippingMethod']['amountTaxedWith'][$taxClassID] = array(
					'taxRate' => ls_shop_generalHelper::getCurrentTax($taxClassID), // no language
					'amountTaxedHerewith' => $value // no language
				);
			}
		}
				
		if (is_array(ls_shop_cartX::getInstance()->calculation['couponValues'])) {
			foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $couponID => $arrCouponValues) {
				$arrCouponInfo = ls_shop_cartX::getInstance()->couponsUsed[$couponID];
				
				$arrCouponMainlanguageInfo = ls_shop_languageHelper::getMultiLanguage($couponID, 'tl_ls_shop_coupon_languages', array('title', 'description'), array(ls_shop_languageHelper::getFallbackLanguage()));
				
				$arrOrder['couponsUsed'][$couponID] = array(
					'title' => $arrCouponMainlanguageInfo['title'], // shop language
					'title_customerLanguage' => $arrCouponInfo['title'], // customer language
					'description' => $arrCouponMainlanguageInfo['description'], // shop language
					'description_customerLanguage' => $arrCouponInfo['description'], // customer language
					'discountOutput' => $arrCouponInfo['extendedInfo']['discountOutput'], // no language
					'artNr' => $arrCouponInfo['extendedInfo']['productCode'], // no language
					'invalid' => $arrCouponInfo['hasErrors'] ? '1' : '', // no language
					'amount' => $arrCouponValues[0], // no language
					'amountTaxedWith' => array() // no language
				);
				
				$arrOrder['couponsTotalValue'] = ls_add($arrOrder['couponsTotalValue'], $arrCouponValues[0]);
				
				if (!ls_shop_generalHelper::checkVATID() && $arrCouponValues[0] != 0) {
					foreach ($arrCouponValues as $taxClassID => $value) {
						if ($taxClassID == 0) {
							continue;
						}
						
						$arrOrder['couponsUsed'][$couponID]['amountTaxedWith'][$taxClassID] = array(
							'taxRate' => ls_shop_generalHelper::getCurrentTax($taxClassID), // no language
							'amountTaxedHerewith' => $value // no language
						);
					}
				}
			}
		}
		
		if (!ls_shop_generalHelper::checkVATID()) {
			foreach (ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'] as $taxClassID => $totalValueOfGoodsTaxedWith) {
				if ($taxClassID == 0) {
					continue;
				}
				$arrOrder['totalValueOfGoodsTaxedWith'][$taxClassID] = array(
					'taxRate' => ls_shop_generalHelper::getCurrentTax($taxClassID), // no language
					'amountTaxedHerewith' => $totalValueOfGoodsTaxedWith // no language
				);
			}
		}
		
		if (!ls_shop_generalHelper::checkVATID()) {
			foreach (ls_shop_cartX::getInstance()->calculation['total'] as $taxClassID => $totalTaxedWith) {
				if ($taxClassID == 0) {
					continue;
				}
				$arrOrder['totalTaxedWith'][$taxClassID] = array(
					'taxRate' => ls_shop_generalHelper::getCurrentTax($taxClassID), // no language
					'amountTaxedHerewith' => $totalTaxedWith // no language
				);
			}
		}
		
		if (!ls_shop_generalHelper::checkVATID()) {
			foreach (ls_shop_cartX::getInstance()->calculation['tax'] as $taxClassID => $value) {
				if ($taxClassID == 0) {
					continue;
				}
				$arrOrder['tax'][$taxClassID] = array(
					'taxRate' => ls_shop_generalHelper::getCurrentTax($taxClassID), // no language
					'taxAmount' => $value // no language
				);
			}
		}
		
		foreach (ls_shop_cartX::getInstance()->calculation['items'] as $arrItem) {
			$objProduct = ls_shop_generalHelper::getObjProduct($arrItem['productCartKey']);
			$objProduct->ls_setMainLanguageMode(true);
			
			$blnIsVariant = $objProduct->_variantIsSelected;
			if ($blnIsVariant) {
                $objProductOrVariant = &$objProduct->_selectedVariant;
            } else {
                $objProductOrVariant = &$objProduct;
            }

			$arrItem['isVariant'] = $blnIsVariant ? '1' : ''; // no language
			$arrItem['artNr'] = $blnIsVariant ? $objProduct->_selectedVariant->_code : $objProduct->_code; // no language
			$arrItem['productTitle'] = $objProduct->_title; // shop language
			$arrItem['variantTitle'] = $blnIsVariant ? $objProduct->_selectedVariant->_title : ''; // shop language
			$arrItem['quantityUnit'] = $blnIsVariant ? $objProduct->_selectedVariant->_quantityUnit : $objProduct->_quantityUnit; // shop language
			$arrItem['quantityDecimals'] = $blnIsVariant ? $objProduct->_selectedVariant->_quantityDecimals : $objProduct->_quantityDecimals; // no language
			$arrItem['configurator'] = array(
				'merchantRepresentation' => $objProduct->_hasConfigurator ? $objProduct->_configuratorMerchantRepresentation : '', // depends on customLogic file, most likely shop language
				'cartRepresentation' => $objProduct->_hasConfigurator ? $objProduct->_configuratorCartRepresentation : '', // customer language
				'hasValue' => $objProduct->_hasConfigurator ? $objProduct->_configuratorHasValue : '', // no language
				'referenceNumber' => $objProduct->_hasConfigurator ? $objProduct->_configuratorReferenceNumber : '' // no language
			);

			$arrItem['customizer'] = array(
				'hasCustomization' => $objProductOrVariant->_hasCustomizer && $objProductOrVariant->_customizer->hasCustomization() ? '1' : '',
				'summary' => $objProductOrVariant->_hasCustomizer && $objProductOrVariant->_customizer->hasCustomization() ? $objProductOrVariant->_customizer->getSummary() : '',
				'summaryForCart' => $objProductOrVariant->_hasCustomizer && $objProductOrVariant->_customizer->hasCustomization() ? $objProductOrVariant->_customizer->getSummaryForCart() : '',
				'summaryForMerchant' => $objProductOrVariant->_hasCustomizer && $objProductOrVariant->_customizer->hasCustomization() ? $objProductOrVariant->_customizer->getSummaryForMerchant() : '',
				'flexData' => $objProductOrVariant->_hasCustomizer && $objProductOrVariant->_customizer->hasCustomization() ? $objProductOrVariant->_customizer->getFlexData() : '',
			);
			$arrItem['extendedInfo'] = array(
				'_productVariantID' => $blnIsVariant ? $objProduct->_selectedVariant->_productVariantID : $objProduct->_productVariantID, // no language
				'_configuratorID' => $blnIsVariant ? $objProduct->_selectedVariant->_configuratorID : $objProduct->_configuratorID, // no language
				'_hasConfigurator' => $objProduct->_hasConfigurator, // no language
				'_cartKey' => $blnIsVariant ? $objProduct->_selectedVariant->_cartKey : $objProduct->_cartKey, // no language
				'_productTitle' => $blnIsVariant ? $objProduct->_selectedVariant->_productTitle : $objProduct->_title, // shop language
				'_title' => $blnIsVariant ? $objProduct->_selectedVariant->_title : $objProduct->_title, // shop language
				'_hasTitle' => $blnIsVariant ? $objProduct->_selectedVariant->_hasTitle : $objProduct->_hasTitle, // no language
				'_code' => $blnIsVariant ? $objProduct->_selectedVariant->_code : $objProduct->_code, // no language
				'_hasCode' => $blnIsVariant ? $objProduct->_selectedVariant->_hasCode : $objProduct->_hasCode, // no language
				'_isNew' => $objProduct->_isNew, // no language
				'_isOnSale' => $objProduct->_isOnSale, // no language
				'_producer' => $objProduct->_producer, // no language
				'_hasProducer' => $objProduct->_hasProducer, // no language
				'_deliveryInfo' => $blnIsVariant ? $objProduct->_selectedVariant->_deliveryInfo : $objProduct->_deliveryInfo, // shop language
				'_useStock' => $blnIsVariant ? $objProduct->_selectedVariant->_useStock : $objProduct->_useStock, // no language
				'_stock' => $blnIsVariant ? $objProduct->_selectedVariant->_stock : $objProduct->_stock, // no language
				'_allowOrdersWithInsufficientStock' => $blnIsVariant ? $objProduct->_selectedVariant->_allowOrdersWithInsufficientStock : $objProduct->_allowOrdersWithInsufficientStock, // no language
				'_deliveryTimeMessage' => $blnIsVariant ? $objProduct->_selectedVariant->_deliveryTimeMessage : $objProduct->_deliveryTimeMessage, // shop language
				'_deliveryTimeDays' => $blnIsVariant ? $objProduct->_selectedVariant->_deliveryTimeDays : $objProduct->_deliveryTimeDays, // no language
				'_deliveryTimeMessageInCart' => $blnIsVariant ? $objProduct->_selectedVariant->_deliveryTimeMessageInCart($arrItem['quantity']) : $objProduct->_deliveryTimeMessageInCart($arrItem['quantity']), // shop language
				'_mainImage' => $blnIsVariant ? $objProduct->_selectedVariant->_mainImage : $objProduct->_mainImage, // no language
				'_hasMainImage' => $blnIsVariant ? $objProduct->_selectedVariant->_hasMainImage : $objProduct->_hasMainImage, // no language

				'_mainImageOfProduct' => $objProduct->_mainImage, // no language
				'_hasMainImageOfProduct' => $objProduct->_hasMainImage, // no language

				'_mainImageOfVariant' => $blnIsVariant ? $objProduct->_selectedVariant->_mainImage : null, // no language
				'_hasMainImageOfVariant' => $blnIsVariant ? $objProduct->_selectedVariant->_hasMainImage : false, // no language

				'_shortDescription' => $blnIsVariant && $objProduct->_selectedVariant->_shortDescription ? $objProduct->_selectedVariant->_shortDescription : $objProduct->_shortDescription, // shop language
				'_hasShortDescription' => ($blnIsVariant && $objProduct->_selectedVariant->_hasShortDescription) || $objProduct->_hasShortDescription, // no language
				'_weight' => $blnIsVariant ? $objProduct->_selectedVariant->_weight : $objProduct->_weight, // no language
				'_weightFormatted' => $blnIsVariant ? $objProduct->_selectedVariant->_weightFormatted : $objProduct->_weightFormatted, // no language
				'_hasWeight' => $blnIsVariant ? $objProduct->_selectedVariant->_hasWeight : $objProduct->_hasWeight, // no language
				'_quantityUnit' => $objProduct->_quantityUnit, // shop language
				'_hasQuantityUnit' => $objProduct->_hasQuantityUnit, // no language
				'_quantityComparisonUnit' => $objProduct->_quantityComparisonUnit, // shop language
				'_hasQuantityComparisonUnit' => $objProduct->_hasQuantityComparisonUnit, // no language
				'_linkToProduct' => $objProduct->_linkToProduct, // no language
				'_linkToVariant' => $blnIsVariant ? $objProduct->_selectedVariant->_linkToVariant : '', // no language
				'_quantityComparisonText' => $objProduct->_quantityComparisonText, // shop language
				'_taxInfo' => $blnIsVariant ? $objProduct->_selectedVariant->_taxInfo : $objProduct->_taxInfo, // shop language
				'_shippingInfo' => $blnIsVariant ? $objProduct->_selectedVariant->_shippingInfo : $objProduct->_shippingInfo, // shop language
				'_quantityDecimals' => $objProduct->_quantityDecimals, // no language
				'_attributes' => $blnIsVariant ? $objProduct->_selectedVariant->_attributes : $objProduct->_attributes, // shop language
				'_attributesAsString' => $blnIsVariant ? $objProduct->_selectedVariant->_attributesAsString : $objProduct->_attributesAsString, // shop language

				'_attributesOfProduct' => $objProduct->_attributes, // shop language
				'_attributesOfProductAsString' => $objProduct->_attributesAsString, // shop language

				'_attributesOfVariant' => $blnIsVariant ? $objProduct->_selectedVariant->_attributes : null, // shop language
				'_attributesOfVariantAsString' => $blnIsVariant ? $objProduct->_selectedVariant->_attributesAsString : null, // shop language

				'_useScalePrice' => $blnIsVariant ? $objProduct->_selectedVariant->_useScalePrice : $objProduct->_useScalePrice, // no language
				'_scalePriceQuantity' => $blnIsVariant ? $objProduct->_selectedVariant->_scalePriceQuantity : $objProduct->_scalePriceQuantity // no language
			);
			
			$objProduct->ls_setMainLanguageMode(false);
			
			$arrItem['extendedInfo']['_productTitle_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_productTitle : $objProduct->_title; // customer language
			$arrItem['extendedInfo']['_title_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_title : $objProduct->_title; // customer language
			$arrItem['extendedInfo']['_deliveryInfo_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_deliveryInfo : $objProduct->_deliveryInfo; // customer language
			$arrItem['extendedInfo']['_deliveryTimeMessage_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_deliveryTimeMessage : $objProduct->_deliveryTimeMessage; // customer language
			$arrItem['extendedInfo']['_deliveryTimeMessageInCart_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_deliveryTimeMessageInCart($arrItem['quantity']) : $objProduct->_deliveryTimeMessageInCart($arrItem['quantity']); // customer language
			$arrItem['extendedInfo']['_shortDescription_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_shortDescription : $objProduct->_shortDescription; // customer language
			$arrItem['extendedInfo']['_quantityUnit_customerLanguage'] = $objProduct->_quantityUnit; // customer language
			$arrItem['extendedInfo']['_quantityComparisonUnit_customerLanguage'] = $objProduct->_quantityComparisonUnit; // customer language
			$arrItem['extendedInfo']['_quantityComparisonText_customerLanguage'] = $objProduct->_quantityComparisonText; // customer language
			$arrItem['extendedInfo']['_taxInfo_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_taxInfo : $objProduct->_taxInfo; // customer language
			$arrItem['extendedInfo']['_shippingInfo_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_shippingInfo : $objProduct->_shippingInfo; // customer language
			
			$arrItem['extendedInfo']['_attributes_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_attributes : $objProduct->_attributes; // customer language
			$arrItem['extendedInfo']['_attributesAsString_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_attributesAsString : $objProduct->_attributesAsString; // customer language

			$arrItem['extendedInfo']['_attributesOfProduct_customerLanguage'] = $objProduct->_attributes; // customer language
			$arrItem['extendedInfo']['_attributesOfProductAsString_customerLanguage'] = $objProduct->_attributesAsString; // customer language

			$arrItem['extendedInfo']['_attributesOfVariant_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_attributes : null; // customer language
			$arrItem['extendedInfo']['_attributesOfVariantAsString_customerLanguage'] = $blnIsVariant ? $objProduct->_selectedVariant->_attributesAsString : null; // customer language
			
			if (isset($GLOBALS['MERCONIS_HOOKS']['storeCartItemInOrder']) && is_array($GLOBALS['MERCONIS_HOOKS']['storeCartItemInOrder'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['storeCartItemInOrder'] as $mccb) {
					$objMccb = \System::importStatic($mccb[0]);
					$arrItem = $objMccb->{$mccb[1]}($arrItem, $objProduct);
				}
			}
			
			$arrOrder['items'][] = $arrItem;
		}

		if (isset($GLOBALS['MERCONIS_HOOKS']['preparingOrderDataToStore']) && is_array($GLOBALS['MERCONIS_HOOKS']['preparingOrderDataToStore'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['preparingOrderDataToStore'] as $mccb) {
				$objMccb = \System::importStatic($mccb[0]);
				$arrOrder = $objMccb->{$mccb[1]}($arrOrder);
			}
		}
		
		return $arrOrder;
	}

	public function saveOrderInDB($order) {
		/*
		 * writing the order row in tl_ls_shop_orders
		 */
		$objInsertedOrder = \Database::getInstance()->prepare("
			INSERT INTO `tl_ls_shop_orders`
			SET			`tstamp` = ?,
						`orderIdentificationHash` = ?,
						`orderNr` = ?,
						`orderDateUnixTimestamp` = ?,
						`orderDate` = ?,
						`customerNr` = ?,
						`customerLanguage` = ?,
						`firstname` = ?,
						`lastname` = ?,
						`personalDataReview` = ?,
						`personalDataReview_customerLanguage` = ?,
						`paymentDataReview` = ?,
						`paymentDataReview_customerLanguage` = ?,
						`shippingDataReview` = ?,
						`shippingDataReview_customerLanguage` = ?,
						`memberGroupInfo_id` = ?,
						`memberGroupInfo_name` = ?,
						`currency` = ?,
						`weightUnit` = ?,
						`userOutputPriceType` = ?,
						`inputPriceType` = ?,
						`numDecimalsPrice` = ?,
						`numDecimalsWeight` = ?,
						`decimalsSeparator` = ?,
						`thousandsSeparator` = ?,
						`miscData` = ?,
						`totalValueOfGoods` = ?,
						`totalValueOfGoodsTaxedWith` = ?,
						`noVATBecauseOfEnteredIDs` = ?,
						`VATIDValidationResult` = ?,
						`totalWeightOfGoods` = ?,
						`couponsUsed` = ?,
						`couponsTotalValue` = ?,
						`paymentMethod_title` = ?,
						`paymentMethod_title_customerLanguage` = ?,
						`paymentMethod_infoAfterCheckout` = ?,
						`paymentMethod_infoAfterCheckout_customerLanguage` = ?,
						`paymentMethod_additionalInfo` = ?,
						`paymentMethod_additionalInfo_customerLanguage` = ?,
						`paymentMethod_id` = ?,
						`paymentMethod_alias` = ?,
						`paymentMethod_feeInfo_customerLanguage` = ?,
						`paymentMethod_moduleReturnData` = ?,
						`paymentMethod_amount` = ?,
						`paymentMethod_amountTaxedWith` = ?,
						`shippingMethod_title` = ?,
						`shippingMethod_title_customerLanguage` = ?,
						`shippingMethod_infoAfterCheckout` = ?,
						`shippingMethod_infoAfterCheckout_customerLanguage` = ?,
						`shippingMethod_additionalInfo` = ?,
						`shippingMethod_additionalInfo_customerLanguage` = ?,
						`shippingMethod_id` = ?,
						`shippingMethod_alias` = ?,
						`shippingMethod_feeInfo_customerLanguage` = ?,
						`shippingMethod_moduleReturnData` = ?,
						`shippingMethod_amount` = ?,
						`shippingMethod_amountTaxedWith` = ?,
						`total` = ?,
						`totalTaxedWith` = ?,
						`taxTotal` = ?,
						`tax` = ?,
						`taxInclusive` = ?,
						`invoicedAmount` = ?,
						`invoicedAmountNet` = ?,
						`status01` = ?,
						`status02` = ?,
						`status03` = ?,
						`status04` = ?,
						`status05` = ?
		")
		->execute(
			$order['orderDateUnixTimestamp'],
			$order['orderIdentificationHash'],
			$order['orderNr'],
			$order['orderDateUnixTimestamp'],
			$order['orderDate'],
			$order['customerNr'],
			$order['customerLanguage'],
			isset($order['customerInfo']['personalData']['firstname']) ? $order['customerInfo']['personalData']['firstname'] : '',
			isset($order['customerInfo']['personalData']['lastname']) ? $order['customerInfo']['personalData']['lastname'] : '',
			$order['customerInfo']['personalDataReview'],
			$order['customerInfo']['personalDataReview_customerLanguage'],
			$order['customerInfo']['paymentDataReview'],
			$order['customerInfo']['paymentDataReview_customerLanguage'],
			$order['customerInfo']['shippingDataReview'],
			$order['customerInfo']['shippingDataReview_customerLanguage'],
			$order['customerInfo']['memberGroupInfo']['id'],
			$order['customerInfo']['memberGroupInfo']['name'],
			$order['currency'],
			$order['weightUnit'],
			$order['userOutputPriceType'],
			$order['inputPriceType'],
			$order['numDecimalsPrice'],
			$order['numDecimalsWeight'],
			$order['decimalsSeparator'],
			$order['thousandsSeparator'],
			serialize($order['miscData']),
			$order['totalValueOfGoods'],
			serialize($order['totalValueOfGoodsTaxedWith']),
			$order['noVATBecauseOfEnteredIDs'],
			$order['VATIDValidationResult'],
			$order['totalWeightOfGoods'],
			serialize($order['couponsUsed']),
			$order['couponsTotalValue'],
			$order['paymentMethod']['title'],
			$order['paymentMethod']['title_customerLanguage'],
			$order['paymentMethod']['infoAfterCheckout'],
			$order['paymentMethod']['infoAfterCheckout_customerLanguage'],
			$order['paymentMethod']['additionalInfo'],
			$order['paymentMethod']['additionalInfo_customerLanguage'],
			$order['paymentMethod']['id'],
			$order['paymentMethod']['alias'],
			$order['paymentMethod']['feeInfo_customerLanguage'],
			$order['paymentMethod']['moduleReturnData'],
			$order['paymentMethod']['amount'],
			serialize($order['paymentMethod']['amountTaxedWith']),
			$order['shippingMethod']['title'],
			$order['shippingMethod']['title_customerLanguage'],
			$order['shippingMethod']['infoAfterCheckout'],
			$order['shippingMethod']['infoAfterCheckout_customerLanguage'],
			$order['shippingMethod']['additionalInfo'],
			$order['shippingMethod']['additionalInfo_customerLanguage'],
			$order['shippingMethod']['id'],
			$order['shippingMethod']['alias'],
			$order['shippingMethod']['feeInfo_customerLanguage'],
			$order['shippingMethod']['moduleReturnData'],
			$order['shippingMethod']['amount'],
			serialize($order['shippingMethod']['amountTaxedWith']),
			$order['total'],
			serialize($order['totalTaxedWith']),
			$order['taxTotal'],
			serialize($order['tax']),
			$order['taxInclusive'],
			$order['invoicedAmount'],
			$order['invoicedAmountNet'],
			$order['status01'],
			$order['status02'],
			$order['status03'],
			$order['status04'],
			$order['status05']
		);
		
		$orderIdInDb = $objInsertedOrder->insertId;

		/*
		 * writing the customer information in tl_ls_shop_orders_customer_data
		 */
		foreach ($order['customerInfo'] as $dataType => $arrData) {
			/*
			 * memberGroupInfo holds only known fields so this
			 * information has been written directly into the
			 * order row itself and therefore can be ignored here
			 */
			if (
					$dataType != 'personalData'
				&&	$dataType != 'personalData_customerLanguage'
				&&	$dataType != 'personalData_originalOptionValues'
				&&	$dataType != 'paymentData'
				&&	$dataType != 'paymentData_customerLanguage'
				&&	$dataType != 'paymentData_originalOptionValues'
				&&	$dataType != 'shippingData'
				&&	$dataType != 'shippingData_customerLanguage'
				&&	$dataType != 'shippingData_originalOptionValues'
			) {
				continue;
			}
			
			if (!is_array($arrData)) {
				continue;
			}
			
			foreach ($arrData as $fieldName => $fieldValue) {
				\Database::getInstance()->prepare("
					INSERT INTO	`tl_ls_shop_orders_customer_data`
					SET			`pid` = ?,
								`tstamp` = ?,
								`dataType` = ?,
								`fieldName` = ?,
								`fieldValue` = ?
				")
				->execute(
					$orderIdInDb,
					$order['orderDateUnixTimestamp'],
					$dataType,
					$fieldName,
					$fieldValue
				);
			}
		}
		
		/*
		 * writing the items information in tl_ls_shop_orders_items
		 */
		foreach ($order['items'] as $itemKey => $arrItem) {
			if (!is_array($arrItem)) {
				continue;
			}
			
			/*
			 * the numeric array key is used as the cart position number but because most humans start counting with 1 instead of 0
			 * the itemPosition is increased by 1
			 */
			$itemPosition = $itemKey + 1;
			
			\Database::getInstance()->prepare("
				INSERT INTO	`tl_ls_shop_orders_items`
				SET			`pid` = ?,
							`tstamp` = ?,
							`itemPosition` = ?,
							`productVariantID` = ?,
							`productCartKey` = ?,
							`price` = ?,
							`weight` = ?,
							`quantity` = ?,
							`priceCumulative` = ?,
							`weightCumulative` = ?,
							`taxClass` = ?,
							`taxPercentage` = ?,
							`isVariant` = ?,
							`artNr` = ?,
							`productTitle` = ?,
							`variantTitle` = ?,
							`quantityUnit` = ?,
							`quantityDecimals` = ?,
							`configurator_merchantRepresentation` = ?,
							`configurator_cartRepresentation` = ?,
							`configurator_hasValue` = ?,
							`configurator_referenceNumber` = ?,
							`customizer_hasCustomization` = ?,
							`customizer_summary` = ?,
							`customizer_summaryForCart` = ?,
							`customizer_summaryForMerchant` = ?,
							`customizer_flexData` = ?,
							`extendedInfo` = ?
			")
			->execute(
				$orderIdInDb,
				$order['orderDateUnixTimestamp'],
				$itemPosition,
				$arrItem['productVariantID'],
				$arrItem['productCartKey'],
				$arrItem['price'],
				$arrItem['weight'],
				$arrItem['quantity'],
				$arrItem['priceCumulative'],
				$arrItem['weightCumulative'],
				$arrItem['taxClass'],
				$arrItem['taxPercentage'],
				$arrItem['isVariant'],
				$arrItem['artNr'],
				$arrItem['productTitle'],
				$arrItem['variantTitle'],
				$arrItem['quantityUnit'],
				$arrItem['quantityDecimals'],
				$arrItem['configurator']['merchantRepresentation'],
				$arrItem['configurator']['cartRepresentation'],
				$arrItem['configurator']['hasValue'],
				$arrItem['configurator']['referenceNumber'],
				$arrItem['customizer']['hasCustomization'],
				$arrItem['customizer']['summary'],
				$arrItem['customizer']['summaryForCart'],
				$arrItem['customizer']['summaryForMerchant'],
				$arrItem['customizer']['flexData'],
				serialize($arrItem['extendedInfo'])
			);			
		}
		
		return $orderIdInDb;
	}

	protected function createShopLanguageArray($arr_data) {
		if (!is_array($arr_data)) {
			return $arr_data;
		}

		/** @var \PageModel $objPage */
		global $objPage;

		$tmpObjPageLanguage = $objPage->language;
		$objPage->language = ls_shop_languageHelper::getFallbackLanguage();

		foreach ($arr_data as $k => $v) {
			$arr_data[$k] = \Controller::replaceInsertTags($v, false);
		}

		$objPage->language = $tmpObjPageLanguage;

		return $arr_data;
	}

	protected function createCustomerLanguageArray($arr_data) {
		if (!is_array($arr_data)) {
			return $arr_data;
		}

		foreach ($arr_data as $k => $v) {
			$arr_data[$k] = \Controller::replaceInsertTags($v, false);
		}

		return $arr_data;
	}
	
}
?>
