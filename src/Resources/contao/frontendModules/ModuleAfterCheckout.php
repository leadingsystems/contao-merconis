<?php

namespace Merconis\Core;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Module;
use Contao\System;

class ModuleAfterCheckout extends Module {
	public function generate() {
		if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
			$this->import('Contao\FrontendUser', 'User');
		}
		
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS - Kasse - Nach Checkout ###';
			return $objTemplate->parse();
		}
		return parent::generate();
	}
	
	public function compile() {
		$this->strTemplate = $this->ls_shop_afterCheckout_template;

		$obj_paymentModule = ls_shop_paymentModule::getInstance();
		
		/**
		 * Nach dem Umbau des Bestellabschlusshandlings wird hier nicht mehr mit einer in der Session abgelegten Bestellung gearbeitet.
		 * Stattdessen muss immer direkt auf die in der DB gespeicherte Bestellung zugegriffen und mit den dort hinterlegten Informationen mittels
		 * eines speziellen Templates für die AfterCheckout-Seite (idealerweise größtenteils identisch mit dem Bestellbestätigungstemplate)
		 * die Ausgabe generiert werden.
		 * 
		 * Die Identifikation der darzustellenden Bestellung erfolgt hier dann immer über den "oih" (order identification hash), der ggf.
		 * durchaus auch für diesen Zweck direkt als GET-Parameter angegeben werden kann, wodurch der Aufruf identisch wäre mit dem Aufruf,
		 * der durch den Klick auf den entsprechenden Link in einer Bestellbestätigung erfolgt. Der "oih" als Get-Parameter ist unkritisch,
		 * da die Bestellung nur angezeigt, aber nichts mit ihr unternommen werden kann. Zudem ist es dem Kunden so auch möglich, diese
		 * Seite als Bookmark abzulegen, auch wenn das ggf. kein unbedingt benötigtes Verhalten ist.
		 */
		
		/*
		 * Der oih (Order Identification Hash) kann entweder direkt als Get-Parameter "oih" vorhanden sein oder aber
		 * als order id im hex format (Get-Parameter "oix").
		 * 
		 * Letzteres kommt zum Einsatz, wenn die After-Checkout-URL z. B. als Return-URL an einen Zahlungsanbieter übergeben werden soll.
		 * Da der oih zur direkten Anzeige der Bestellung genutzt wird, darf sie dem Zahlungsanbieter nicht mitgeteilt werden.
		 * Die Bestellungs-ID hingegen wird nirgends verwendet, um ohne Anmeldung Bestell-Informationen auszugeben (eine gültige ID
		 * könnte ja auch leicht erraten werden), sie kann also als Identifikationsmerkmal für den Zahlungsanbieter verwendet werden.
		 * Auch wenn es natürlich keine ernst zu nehmende zusätzliche Sicherheit bietet, werden die IDs umgewandelt auf eine spezielle
		 * Art erweitert und hexadezimal codiert, damit der übergebene Parameter nicht den Eindruck einer Sicherheitslücke entstehen lässt.
		 */
		
		/* -->
		 * If a parameter "callbackPaymentMethodId" is given, we use it to specialize
		 * the payment module. This is necessary, if the call to this page is
		 * a return URL from a payment provider and it doesn't contain the oix.
		 * This is the case, for example, with payone's TransactionStatus URL,
		 * where we are not able to include the oix parameter directly.
		 * 
		 * So, with the given payment method id and the specialized payment module
		 * we can call the callback function "determineOix" which then provides
		 * the oix. After that, the rest of the script works exactly the way it does
		 * with a directly given oix.
		 */

		$int_callbackPaymentMethodId = null;
		$bln_paymentModuleAlreadySpecialized = false;
		$str_oixFromCallback = '';
		
		if (Input::get('callbackPaymentMethodId')) {
			$int_callbackPaymentMethodId = Input::get('callbackPaymentMethodId');
		} else if (Input::post('callbackPaymentMethodId')) {
			$int_callbackPaymentMethodId = Input::post('callbackPaymentMethodId');
		}
		
		if ($int_callbackPaymentMethodId) {
			$obj_paymentModule->specializeManuallyWithPaymentID($int_callbackPaymentMethodId);
			$bln_paymentModuleAlreadySpecialized = true;
			
			// ### paymentMethod callback ########################
			$str_oixFromCallback = $obj_paymentModule->determineOix();
			// ###################################################
		}
		/*
		 * <--
		 */
		
		/*
		 * Get the order id from an oix if given as a get or post parameter
		 */
		$idFromOix = null;
		$oix = null;
		if ($str_oixFromCallback) {
			$oix = $str_oixFromCallback;
			$idFromOix = ls_shop_generalHelper::decodeOix($oix);
		} else if (Input::get('oix')) {
			$oix = Input::get('oix');
			$idFromOix = ls_shop_generalHelper::decodeOix($oix);
		} else if (Input::post('oix')) {
			$oix = Input::post('oix');
			$idFromOix = ls_shop_generalHelper::decodeOix($oix);
		}
		
		/*
		 * Look if an oih for the oix can be found in the session or given as a get or post parameter
		 */
		$oih = null;
		if (isset($_SESSION['lsShop']['oix2oih'][$oix]) && $_SESSION['lsShop']['oix2oih'][$oix]) {
			$oih = $_SESSION['lsShop']['oix2oih'][$oix];
		} else if (Input::get('oih')) {
			$oih = Input::get('oih');
		} else if (Input::post('oih')) {
			$oih = Input::get('oih');
		}
		
		$arrOrder = null;

		/*
		 * If we have an id from an oix we get the order and pass it through the payment function
		 * "onAfterCheckoutPage".
		 */
		if ($idFromOix) {
			$arrOrder = ls_shop_generalHelper::getOrder($idFromOix);
			// ### paymentMethod callback ########################
			if (!$bln_paymentModuleAlreadySpecialized) {
				$obj_paymentModule->specializeManuallyWithPaymentID($arrOrder['paymentMethod_id']);
			}
			$obj_paymentModule->onAfterCheckoutPage($arrOrder);
			// ###################################################

		}
		
		/*
		 * If we have an oih we get the order and create the output
		 */
		if ($oih){
			// If have already got the order from an oix, we use it. If not, we get it from the oih.
			$arrOrder = is_array($arrOrder) ? $arrOrder : ls_shop_generalHelper::getOrder($oih, 'orderIdentificationHash');
			if (!is_array($arrOrder) || !count($arrOrder)) {
				return false;
			}

			$this->Template = new FrontendTemplate($this->strTemplate);
			
			$this->Template->arrOrder = $arrOrder;
			$this->Template->specialInfoForPaymentMethod = isset($_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish']) ? $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] : '';
			$this->Template->specialInfoForShippingMethod = isset($_SESSION['lsShop']['specialInfoForShippingMethodAfterCheckoutFinish']) ? $_SESSION['lsShop']['specialInfoForShippingMethodAfterCheckoutFinish'] : '';
			
			/*
			$this->Template->infoForPaymentMethod = trim(ls_shop_languageHelper::getMultiLanguage($_SESSION['lsShop']['finishedOrder']['selectedPaymentMethod'], 'tl_ls_shop_payment_methods_languages', array('infoAfterCheckout'), array($objPage->language)));
			$this->Template->infoForShippingMethod = trim(ls_shop_languageHelper::getMultiLanguage($_SESSION['lsShop']['finishedOrder']['selectedShippingMethod'], 'tl_ls_shop_shipping_methods_languages', array('infoAfterCheckout'), array($objPage->language)));
			 *
			 */
			
			} else {
				return false;
			}	
	}
}
?>