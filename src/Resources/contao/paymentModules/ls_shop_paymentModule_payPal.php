<?php

namespace Merconis\Core;
use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;

	class ls_shop_paymentModule_payPal extends ls_shop_paymentModule_standard {
		public $arrCurrentSettings = array();
		
		public function initialize($specializedManually = false) {
			// --> WICHTIG: DEFINITIONEN MÜSSEN ZU ALLERERST ERFOLGEN -->
			$this->API_Endpoint_sandbox = 'https://api-3t.sandbox.paypal.com/nvp';
			$this->API_Endpoint_live = 'https://api-3t.paypal.com/nvp';
			$this->API_Endpoint = $this->arrCurrentSettings['paypalLiveMode'] ? $this->API_Endpoint_live : $this->API_Endpoint_sandbox;
			
			$this->redirectToLogin_sandbox = 'https://sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout';
			$this->redirectToLogin_live = 'https://paypal.com/cgi-bin/webscr?cmd=_express-checkout';
			$this->redirectToLogin = $this->arrCurrentSettings['paypalLiveMode'] ? $this->redirectToLogin_live : $this->redirectToLogin_sandbox;
			
			$this->redirectToGiropay_sandbox = 'https://sandbox.paypal.com/cgi-bin/webscr?cmd=_complete-express-checkout';
			$this->redirectToGiropay_live = 'https://paypal.com/cgi-bin/webscr?cmd=_complete-express-checkout';
			$this->redirectToGiropay = $this->arrCurrentSettings['paypalLiveMode'] ? $this->redirectToGiropay_live : $this->redirectToGiropay_sandbox;			
			
			$this->API_version = '95.0';
			// <-- <--


			/*
			 * ###### Create the return url #######
			 */
			$this->returnUrl = \Environment::get('base').ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages');
			/*
			 * #######################################
			 */
			
			if (!isset($_SESSION['lsShopPaymentProcess']['paypal']) || !is_array($_SESSION['lsShopPaymentProcess']['paypal'])) {
				$_SESSION['lsShopPaymentProcess']['paypal'] = array();
			}
			
			if (!$specializedManually) {
				$this->paypal_checkIfMethodAllowed();
				
				$this->paypal_checkIfCalculationChanged();
				
				$this->paypal_checkIfRedirectedFromPayPal();
			}
		}

		public function afterCheckoutFinish($orderIdInDb = 0, $order = array(), $afterCheckoutUrl = '', $oix = '') {
			$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = '';
			
			###
			$this->paypal_doExpressCheckoutPayment($order);
			###
			
			if (!$_SESSION['lsShopPaymentProcess']['paypal']['finishedSuccessfully']) {
				$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['paypal']['paymentErrorAfterFinishedOrder'];
			} else {
				if ($_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['REDIRECTREQUIRED']) {
					$giropayRedirectionForm = $this->getForm($this->arrCurrentSettings['paypalGiropayRedirectForm']);
					$giropayRedirectionForm = preg_replace('/(<form.*action=")(.*)(")/siU', '\\1'.$this->redirectToGiropay.'&token='.$_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['TOKEN'].'\\3', $giropayRedirectionForm);
					$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $giropayRedirectionForm;
				}
			}

			/*
			 * Nach abgeschlossener Bestellung wird das Array mit Informationen über den Status der PayPal-Zahlung zurückgesetzt, um zu verhindern, dass bei einer weiteren Bestellung versucht wird, die alte Autorisierung weiter zu benutzen.
			 */
			unset($_SESSION['lsShopPaymentProcess']['paypal']);
		}
		
		public function afterPaymentMethodSelection() {
			$_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse'] = array(
				'ACK' => 'Failure',
				'TOKEN' => ''
			);
			$_SESSION['lsShopPaymentProcess']['paypal']['authorized'] = false;
			$_SESSION['lsShopPaymentProcess']['paypal']['finishedSuccessfully'] = false;

			$this->paypal_setExpressCheckout();
		}
		
		public function afterPaymentMethodAdditionalDataConfirm() {
			if ($_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['ACK'] != 'Success' || !$_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['TOKEN']) {
				/*
				 * Zahlung nicht erfolgreich eingeleitet, deshalb keine Weiterleitung zum PayPal-Login,
				 * stattdessen Fehlerseite!
				 */
				$this->redirectToErrorPage('afterPaymentMethodAdditionalDataConfirm', $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['ACK']);
			}

			$this->redirect($this->redirectToLogin.'&token='.$_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['TOKEN']);
		}		
		
		public function statusOkayToShowAdditionalDataForm() {
			/*
			 * sollte unnötig sein!
			$this->paypal_renewExpressCheckoutDetailsResponse();
			 */
			if ($_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['ACK'] == 'Success') {
				return true;
			} else {
				return false;
			}
		}
		
		public function statusOkayToRedirectToCheckoutFinish() {
			/*
			 * sollte unnötig sein!
			$this->paypal_renewExpressCheckoutDetailsResponse();
			 */
			if ($_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['ACK'] == 'Success' && $_SESSION['lsShopPaymentProcess']['paypal']['authorized']) {
				return true;
			} else {
				return false;
			}
		}
		
		public function beforeCheckoutFinish() {
		}
		
		public function checkoutFinishAllowed() {
			if ($_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['ACK'] != 'Success') {
				return false;
			} else {
				return true;
			}
		}

		public function getFormIDForAdditionalData($formID) {
			if ($this->statusOkayToRedirectToCheckoutFinish()) {
				return $this->arrCurrentSettings['paypalSecondForm'];
			} else {
				return $formID;
			}
		}

		public function getPaymentInfo() {
			$info = '';
			/*
			 * sollte unnötig sein!
			$this->paypal_renewExpressCheckoutDetailsResponse();
			 */
			foreach ($_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse'] as $key => $value) {
				$info .= '<strong>'.$key.':</strong> '.$value."<br />\r\n";
			}
			return $info;
		}
		
		public function showPaymentDetailsInBackendOrderDetailView($arrOrder = array(), $paymentMethod_moduleReturnData = '') {
			if (!count($arrOrder) || !$paymentMethod_moduleReturnData) {
				return null;
			}
			
			$outputValue = '';
			
			$varValue = \Contao\StringUtil::deserialize($paymentMethod_moduleReturnData);
			ob_start();
			echo '<div class="payPalInfo">';
			if (is_array($varValue)) {
				print_r($varValue);
			} else {
				echo $varValue;
			}
			echo '</div>';
			$outputValue = ob_get_clean();
	
			return $outputValue;
		}
		
		/**
		 * Diese Funktion prüft, ob diese Zahlungsmethode überhaupt erlaubt ist. Es kann unter Umständen vorkommen,
		 * dass Teile der Verarbeitung dieser Zahlungsmethode (in diesem Fall die Kontaktaufnahme mit PayPal) noch
		 * durchgeführt werden sollen, obwohl die Zahlungsmethode durch Änderung der Bestellung nicht mehr erlaubt ist.
		 */
		protected function paypal_checkIfMethodAllowed() {
			if (!ls_shop_generalHelper::checkIfPaymentMethodIsAllowed(ls_shop_checkoutData::getInstance()->arrCheckoutData['selectedPaymentMethod'])) {
				ls_shop_checkoutData::getInstance()->resetSelectedPaymentMethod();

                if (!\Environment::get('isAjaxRequest') && $_SESSION['ls_cajax']['requestData'] === null) {
                    $this->reload();
                }
			}
		}
		
		/**
		 * Diese Funktion prüft, ob sich an der Kalkulation etwas verändert hat und setzt, sofern es eine Änderung gab,
		 * die gewählte Zahlungsmethode zurück und lädt die Seite neu. So wird verhindert, dass mit einer für die neue
		 * Kalkulation nicht mehr passenden PayPal-Transaktion/-Autorisierung weitergearbeitet wird.
		 */
		protected function paypal_checkIfCalculationChanged() {
			if (!$_SESSION['lsShopPaymentProcess']['paypal']['finishedSuccessfully']) {
				$arrCurrentNVP = $this->paypal_createSetExpressCheckoutNVP();
				if (isset($_SESSION['lsShopPaymentProcess']['paypal']['setExpressCheckoutNVP'])) {
					 if (
					 			$_SESSION['lsShopPaymentProcess']['paypal']['setExpressCheckoutNVP']['PAYMENTREQUEST_0_AMT'] != $arrCurrentNVP['PAYMENTREQUEST_0_AMT']
					 		||
					 			$_SESSION['lsShopPaymentProcess']['paypal']['setExpressCheckoutNVP']['PAYMENTREQUEST_0_CURRENCYCODE'] != $arrCurrentNVP['PAYMENTREQUEST_0_CURRENCYCODE']
					 		||
					 			$_SESSION['lsShopPaymentProcess']['paypal']['setExpressCheckoutNVP']['PAYMENTREQUEST_0_ITEMAMT'] != $arrCurrentNVP['PAYMENTREQUEST_0_ITEMAMT']
					 		||
					 			$_SESSION['lsShopPaymentProcess']['paypal']['setExpressCheckoutNVP']['PAYMENTREQUEST_0_SHIPPINGAMT'] != $arrCurrentNVP['PAYMENTREQUEST_0_SHIPPINGAMT']
					 		||
					 			$_SESSION['lsShopPaymentProcess']['paypal']['setExpressCheckoutNVP']['PAYMENTREQUEST_0_HANDLINGAMT'] != $arrCurrentNVP['PAYMENTREQUEST_0_HANDLINGAMT']
					 		||
					 			$_SESSION['lsShopPaymentProcess']['paypal']['setExpressCheckoutNVP']['PAYMENTREQUEST_0_TAXAMT'] != $arrCurrentNVP['PAYMENTREQUEST_0_TAXAMT']
					) {
					 	unset($_SESSION['lsShopPaymentProcess']['paypal']['setExpressCheckoutNVP']);
						
						if (isset($_SESSION['lsShopPaymentProcess']['paypal']['authorized']) && $_SESSION['lsShopPaymentProcess']['paypal']['authorized']) {
							$this->setPaymentMethodErrorMessage($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['paypal']['authorizationObsolete']);
						}

						$_SESSION['lsShopPaymentProcess']['paypal']['authorized'] = false;

						$this->paypal_setExpressCheckout();
					 }
				}
				$_SESSION['lsShopPaymentProcess']['paypal']['setExpressCheckoutNVP'] = $arrCurrentNVP;
			}
		}
		
		protected function paypal_checkIfRedirectedFromPayPal() {
			/*
			 * Wurde ein Token in der URL übergeben, so ist dies das Zeichen für den Aufruf als Folge des Redirects von PayPal zurück
			 */
			if (\Input::get('token') && \Input::get('token') == $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['TOKEN']) {
				// Verarbeiten eines Abbruchs
				if (\Input::get('cancelPaypal')) {
					$this->setPaymentMethodErrorMessage($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['paypal']['authorizationCancelled']);
					$this->afterPaymentMethodSelection();
					$this->redirect($this->returnUrl.'#checkoutStepPayment');
				}
				
				$this->paypal_renewExpressCheckoutDetailsResponse();
				
				// Verarbeiten einer erfolgreichen Autorisierung
				if (
						isset($_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['PAYERID'])
					&&	$_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['PAYERID']
					&&	\Input::get('PayerID')
					&&	$_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['PAYERID'] == \Input::get('PayerID')
				) {
					$_SESSION['lsShopPaymentProcess']['paypal']['authorized'] = true;
					$this->setPaymentMethodSuccessMessage($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['paypal']['successfullyAuthorized']);
					$this->redirect($this->returnUrl.'#checkoutStepPayment');
				}
			}
		}
		
		protected function paypal_renewExpressCheckoutDetailsResponse($blnRedirectOnError = true) {
			// Ermitteln der GetExpressCheckoutDetailsResponse und Ablegen in Session
			$arrNVP = array(
				'METHOD' => 'GetExpressCheckoutDetails',
				'TOKEN' => $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['TOKEN']
			);
			$_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse'] = $this->paypal_hashCall('GetExpressCheckoutDetails', $arrNVP);

			if ($_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['ACK'] != 'Success') {
				/*
				 * Die Empfangenen ExpressCheckoutDetails enthalten einen Fehler! 
				 */
				if ($blnRedirectOnError) {
					$this->redirectToErrorPage('paypal_renewExpressCheckoutDetailsResponse', $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']);
				} else {
					$this->logPaymentError('paypal_renewExpressCheckoutDetailsResponse', $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']);
				}
			}
		}
		
		protected function paypal_createSetExpressCheckoutNVP() {
			$cancelUrl = $this->returnUrl.(preg_match('/\?/', $this->returnUrl) ? '&' : '?').'cancelPaypal=1';
			
			$giropaysuccesUrl = \Environment::get('base').ls_shop_languageHelper::getLanguagePage('giropaySuccessPages', \Contao\StringUtil::deserialize($this->arrCurrentSettings['paypalGiropaySuccessPages']));
			$giropaycancelUrl = \Environment::get('base').ls_shop_languageHelper::getLanguagePage('giropayCancelPages', \Contao\StringUtil::deserialize($this->arrCurrentSettings['paypalGiropayCancelPages']));
			$banktxnpendingUrl = \Environment::get('base').ls_shop_languageHelper::getLanguagePage('banktransferPendingPages', \Contao\StringUtil::deserialize($this->arrCurrentSettings['paypalBanktransferPendingPages']));

			/*
			 * Hinzufügen der allgemeinen Bestellinformationen zum NVP-Array
			 */
			$arrNVP = array(
				'ALLOWNOTE' => 0,
				'LOCALECODE' => 'DE',
				'SOLUTIONTYPE' => 'Sole',
				'LANDINGPAGE' => 'Billing',
				'RETURNURL' => $this->returnUrl,
				'CANCELURL' => $cancelUrl,
				'GIROPAYSUCCESSURL' => $giropaysuccesUrl,
				'GIROPAYCANCELURL' => $giropaycancelUrl,
				'BANKTXNPENDINGURL' => $banktxnpendingUrl,
				'NOSHIPPING' => '1',
//				'ADDROVERRIDE' => '1', // Das Setzen dieses Flags hat möglicherweise zur Folge, dass die Versandadresse angezeigt wird, obwohl das NOSHIPPING-Flag das verhindern sollte
				
				'PAYMENTREQUEST_0_AMT' => number_format(ls_shop_cartX::getInstance()->calculation['invoicedAmount'], 2, '.', ''),
				'PAYMENTREQUEST_0_CURRENCYCODE' => $GLOBALS['TL_CONFIG']['ls_shop_currencyCode']
			);
			
			if ($this->arrCurrentSettings['paypalShowItems']) {
				/*
				 * Nur wenn einzelne Positionen der Bestellung an PayPal übergeben werden sollen,
				 * sind die Infos für ITEMAMT und SHIPPINGAMT zu übergeben
				 */
				
				if (ls_shop_cartX::getInstance()->calculation['taxInclusive']) {
					$arrNVP['PAYMENTREQUEST_0_ITEMAMT'] = number_format(ls_sub(ls_sub(ls_shop_cartX::getInstance()->calculation['invoicedAmount'], ls_shop_cartX::getInstance()->calculation['shippingFee'][0]), ls_shop_cartX::getInstance()->calculation['paymentFee'][0]), 2, '.', '');
				} else {
					$arrNVP['PAYMENTREQUEST_0_ITEMAMT'] = number_format(ls_sub(ls_sub(ls_shop_cartX::getInstance()->calculation['invoicedAmountNet'], ls_shop_cartX::getInstance()->calculation['shippingFee'][0]), ls_shop_cartX::getInstance()->calculation['paymentFee'][0]), 2, '.', '');
				}
				$arrNVP['PAYMENTREQUEST_0_SHIPPINGAMT'] = ls_shop_cartX::getInstance()->calculation['shippingFee'][0];
				$arrNVP['PAYMENTREQUEST_0_HANDLINGAMT'] = ls_shop_cartX::getInstance()->calculation['paymentFee'][0];

				/*
				 * Hinzufügen der separaten Steuer-Angabe, sofern Netto-Ausgabe
				 */
			 	if (!ls_shop_cartX::getInstance()->calculation['taxInclusive']) {
					$arrNVP['PAYMENTREQUEST_0_TAXAMT'] = number_format(ls_sub(ls_shop_cartX::getInstance()->calculation['invoicedAmount'], ls_shop_cartX::getInstance()->calculation['invoicedAmountNet']), 2, '.', '');
				}
			}			
			
			/*
			 * DEAKTIVIERT, DA WIR AKTUELL DAVON AUSGEHEN, DASS DIE VERSANDADRESSE BESSER ÜBERHAUPT NICHT ÜBERTRAGEN UND AUCH GAR NICHT ANGEZEIGT WERDEN SOLLTE
			 * Hinzufügen der Versandadresse
			 *
			$arrCheckoutFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'];
			
			$arrNVP['PAYMENTREQUEST_0_SHIPTONAME'] = ($arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameFirstname'].'_alternative']['value'] ? $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameFirstname'].'_alternative']['value'] : $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameFirstname']]['value']).' '.($arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameLastname'].'_alternative']['value'] ? $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameLastname'].'_alternative']['value'] : $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameLastname']]['value']);
			$arrNVP['PAYMENTREQUEST_0_SHIPTOSTREET'] = $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameStreet'].'_alternative']['value'] ? $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameStreet'].'_alternative']['value'] : $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameStreet']]['value'];
			$arrNVP['PAYMENTREQUEST_0_SHIPTOCITY'] = $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameCity'].'_alternative']['value'] ? $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameCity'].'_alternative']['value'] : $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameCity']]['value'];
			$arrNVP['PAYMENTREQUEST_0_SHIPTOZIP'] = $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNamePostal'].'_alternative']['value'] ? $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNamePostal'].'_alternative']['value'] : $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNamePostal']]['value'];
			$arrNVP['PAYMENTREQUEST_0_SHIPTOSTATE'] = $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameState'].'_alternative']['value'] ? $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameState'].'_alternative']['value'] : $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameState']]['value'];
			$arrNVP['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameCountryCode'].'_alternative']['value'] ? $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameCountryCode'].'_alternative']['value'] : $arrCheckoutFormFields[$this->arrCurrentSettings['paypalShipToFieldNameCountryCode']]['value'];
			 */
			
			/*
			 * Hinzufügen der einzelnen Positionen der Bestellung zum NVP-Array
			 */
			if ($this->arrCurrentSettings['paypalShowItems']) {
				$itemCount = 0;
				$itemAmount = 0;
				foreach (ls_shop_cartX::getInstance()->calculation['items'] as $cartItem) {
					$cartItemExtended = ls_shop_cartX::getInstance()->itemsExtended[$cartItem['productCartKey']];

					if ($cartItemExtended['quantity'] == 0) {
						continue;
					}
					
					$itemAmount = ls_add($itemAmount, $cartItem['priceCumulative']);

					$arrNVP['L_PAYMENTREQUEST_0_NAME'.$itemCount] = substr(\Controller::replaceInsertTags($cartItemExtended['objProduct']->_title), 0, 127);
					$arrNVP['L_PAYMENTREQUEST_0_DESC'.$itemCount] = $cartItemExtended['objProduct']->_hasCode ? substr($cartItemExtended['objProduct']->_code, 0, 127) : '';
					
					/*
					 * Ist die Menge ganzzahlig, so kann die Position ganz normal mit dem Einzelpreis und der Menge an PayPal übergeben werden.
					 * Ist die Menge aber nicht ganzzahlig, so muss als Menge 1 übergeben werden, als Preis wird der kumulierte Positionspreis übergeben
					 * und in die Beschreibung wird die tatsächliche Menge und der tatsächliche Einzelpreis eingetragen.
					 */
					if (intval($cartItemExtended['quantity']) == $cartItemExtended['quantity']) {
						$arrNVP['L_PAYMENTREQUEST_0_AMT'.$itemCount] = number_format($cartItem['price'], 2, '.', '');
						$arrNVP['L_PAYMENTREQUEST_0_QTY'.$itemCount] = $cartItemExtended['quantity'];
					} else {
						$arrNVP['L_PAYMENTREQUEST_0_AMT'.$itemCount] = number_format($cartItem['priceCumulative'], 2, '.', '');
						$arrNVP['L_PAYMENTREQUEST_0_QTY'.$itemCount] = 1;
						$arrNVP['L_PAYMENTREQUEST_0_DESC'.$itemCount] = $arrNVP['L_PAYMENTREQUEST_0_DESC'.$itemCount].' ('.$cartItemExtended['quantity'].' '.$cartItemExtended['objProduct']->_quantityUnit.' * '.$cartItemExtended['objProduct']->_priceAfterTaxFormatted.')';
					}
					
					$itemCount++;
				}

				foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $couponValue) {
					$itemAmount = ls_add($itemAmount, $couponValue[0]);
					
					$arrNVP['L_PAYMENTREQUEST_0_NAME'.$itemCount] = 'COUPON';
					$arrNVP['L_PAYMENTREQUEST_0_DESC'.$itemCount] = '';
					$arrNVP['L_PAYMENTREQUEST_0_AMT'.$itemCount] = number_format($couponValue[0], 2, '.', '');
					$arrNVP['L_PAYMENTREQUEST_0_QTY'.$itemCount] = 1;
					$itemCount++;
				}
			}

			return $arrNVP;
		}
		
		public function paypal_setExpressCheckout() {
			$arrNVP = $this->paypal_createSetExpressCheckoutNVP();
			$arrResultNVP = $this->paypal_hashCall('SetExpressCheckout', $arrNVP);

			if ($arrResultNVP['ACK'] != 'Success' || !$arrResultNVP['TOKEN']) {
				$this->redirectToErrorPage('paypal_setExpressCheckout ($arrNVP, $arrResultNVP)', $arrNVP, $arrResultNVP);
			}
			$_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['TOKEN'] = $arrResultNVP['TOKEN'];
			$this->paypal_renewExpressCheckoutDetailsResponse();
		}

		public function paypal_doExpressCheckoutPayment($arr_order) {
			$arrNVP = array(
				'TOKEN' => $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['TOKEN'],
				'PAYERID' => $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['PAYERID'],
				'PAYMENTREQUEST_0_AMT' => $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['PAYMENTREQUEST_0_AMT'],
				'PAYMENTREQUEST_0_NOTETEXT' => (isset($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['paypal']['orderNo']) && $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['paypal']['orderNo'] ? $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['paypal']['orderNo'].': ' : 'order no.: ').$arr_order['orderNr'],
				'PAYMENTREQUEST_0_DESC' => (isset($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['paypal']['paymentDesc']) && $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['paypal']['paymentDesc'] ? $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['paypal']['paymentDesc'].': ' : 'order no.: ').$arr_order['orderNr'],
				'PAYMENTREQUEST_0_CURRENCYCODE' => $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['PAYMENTREQUEST_0_CURRENCYCODE'],
				'PAYMENTREQUEST_0_ITEMAMT' => $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['PAYMENTREQUEST_0_ITEMAMT'],
				'PAYMENTREQUEST_0_SHIPPINGAMT' => $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['PAYMENTREQUEST_0_SHIPPINGAMT'],
				'PAYMENTREQUEST_0_HANDLINGAMT' => $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['PAYMENTREQUEST_0_HANDLINGAMT'],
				'PAYMENTREQUEST_0_TAXAMT' => $_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['PAYMENTREQUEST_0_TAXAMT']
			);

			$arrResultNVP = $this->paypal_hashCall('DoExpressCheckoutPayment', $arrNVP);
			
			if ($arrResultNVP['ACK'] == 'Success') {
				$this->paypal_renewExpressCheckoutDetailsResponse(false);
				if ($_SESSION['lsShopPaymentProcess']['paypal']['GetExpressCheckoutDetailsResponse']['ACK'] == 'Success') {
					$_SESSION['lsShopPaymentProcess']['paypal']['finishedSuccessfully'] = true;
				}
			}
		}
		
		public function paypal_create_strNVP($arrNVP) {
			$strNVP = '';
			foreach ($arrNVP as $n => $v) {
				if ($strNVP) {
					$strNVP .= '&';
				}
				$strNVP .= $n.'='.urlencode($v);
			}
			return $strNVP;
		}
		
		public function paypal_hashCall($methodName, $arrNVP) {
			if (!$methodName || !is_array($arrNVP)) {
				$this->redirectToErrorPage('paypal_hashCall', $arrNVP);
			}

			$strNVP = $this->paypal_create_strNVP($arrNVP);
			
			//setting the curl parameters.
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->API_Endpoint);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
		
			//turning off the server and peer verification(TrustManager Concept).
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST, 1);
		
			// Vorbereiten des NVP-Request-Strings
			$strRequestNVP = "METHOD=".urlencode($methodName)."&VERSION=".urlencode($this->API_version)."&PWD=".urlencode($this->arrCurrentSettings['paypalAPIPassword'])."&USER=".urlencode($this->arrCurrentSettings['paypalAPIUsername'])."&SIGNATURE=".urlencode($this->arrCurrentSettings['paypalAPISignature'])."&".$strNVP;
		
			// Anh�ngen des NVP-Request-Strings an CURL als Post
			curl_setopt($ch, CURLOPT_POSTFIELDS, $strRequestNVP);
			
			// Ausf�hren des CURL, entgegennehmen der Response
			$strResponseNVP = curl_exec($ch);
		
			// Assoziative Arrays aus den NVP-Strings erstellen
			$arrResultNVP = $this->paypal_deformatNVP($strResponseNVP);
			$arrRequestNVP = $this->paypal_deformatNVP($strRequestNVP);
		
			if (curl_errno($ch)) {
				// Weiterleiten auf Fehlerseite, 
				$_SESSION['lsShopPaymentProcess']['paypal']['curl_errno'] = curl_errno($ch);
				$_SESSION['lsShopPaymentProcess']['paypal']['curl_error'] = curl_error($ch);
				$this->redirectToErrorPage('curl_errno', 'curl_errno: '.$_SESSION['lsShopPaymentProcess']['paypal']['curl_errno'].', curl_error: '.$_SESSION['lsShopPaymentProcess']['paypal']['curl_error']);
			} else {
				curl_close($ch);
			}
			
			return $arrResultNVP;
		}

		/*
		 * This function will take NVPString and convert it to an Associative Array and it will decode the response.
		 * It is usefull to search for a particular key and displaying arrays.
		 * @nvpstr is NVPString.
		 * @nvpArray is Associative Array.
		 */
		public function paypal_deformatNVP($strNVP) {
			$intial=0;
			$arrNVP = array();
			
			while(strlen($strNVP)){
				// postion of Key
				$keypos= strpos($strNVP,'=');
				
				// position of value
				$valuepos = strpos($strNVP,'&') ? strpos($strNVP,'&'): strlen($strNVP);
				
				// getting the Key and Value values and storing in a Associative Array
				$keyval=substr($strNVP,$intial,$keypos);
				$valval=substr($strNVP,$keypos+1,$valuepos-$keypos-1);
				
				// decoding the respose
				$arrNVP[urldecode($keyval)] =urldecode( $valval);
				$strNVP=substr($strNVP,$valuepos+1,strlen($strNVP));
			}
			return $arrNVP;
		}
	}
?>