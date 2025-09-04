<?php

namespace Merconis\Core;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\Database;
use Contao\Environment;
use Contao\Module;
use Contao\PageModel;
use Contao\System;
use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;

class ModuleCheckoutFinish extends Module {
	
	public function generate() {
		if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
			$this->import('Contao\FrontendUser', 'User');
		}
		
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$objTemplate = new BackendTemplate('be_wildcard');
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
		$obj_paymentModule = ls_shop_paymentModule::getInstance();

		// ### paymentMethod callback ########################
		$obj_paymentModule->beforeCheckoutFinish();
		// ###################################################

		$obj_checkout = new ls_shop_checkout();
		$obj_checkout->completeCheckout();

		if($obj_checkout->isCheckoutDone()){
			$obj_paymentModule->redirectToErrorPage('checkoutFinish not allowed');
		}

		if ($obj_checkout->hasUsePaymentAfterCheckoutPage()) {
			$this->redirect($obj_checkout->getPaymentAfterCheckoutUrlWithOih());
		} else {
			$this->redirect($obj_checkout->getAfterCheckoutUrlWithOih());
		}

	}
	
}
?>
