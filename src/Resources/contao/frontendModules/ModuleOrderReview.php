<?php

namespace Merconis\Core;

class ModuleOrderReview extends \Module {
	public function generate() {
		if (TL_MODE == 'BE') {
			$objTemplate = new \BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS order review ###';
			return $objTemplate->parse();
		}
		
		return parent::generate();
	}

	public function compile() {
		if (!ls_shop_generalHelper::check_finishingOrderIsAllowed()) {
			\Controller::redirect(ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages'));
		}

		$this->Template = new \FrontendTemplate($this->ls_shop_orderReview_template);

		$formConfirmOrder = ls_shop_checkoutData::getInstance()->formConfirmOrder;
		// ### paymentMethod callback ########################
		$obj_paymentModule = new ls_shop_paymentModule();
		$formConfirmOrder = $obj_paymentModule->modifyConfirmOrderForm($formConfirmOrder);
		// ###################################################
		$this->Template->formConfirmOrder = $formConfirmOrder;

		$this->Template->noVATBecauseOfEnteredIDs = ls_shop_generalHelper::checkVATID();

		$groupSettings = ls_shop_generalHelper::getGroupSettings4User();
		$this->Template->minimumOrderValue = $groupSettings['lsShopMinimumOrderValue'];

		$this->Template->minimumOrderValueOkay = ls_shop_generalHelper::check_minimumOrderValueIsReached();

		$this->Template->arrRequiredCheckoutData = array(
			'formCustomerData' => ls_shop_checkoutData::getInstance()->formCustomerData,
			'checkoutDataErrors' => ls_shop_checkoutData::getInstance()->validationErrors,
			'checkoutDataIsValid' => ls_shop_checkoutData::getInstance()->checkoutDataIsValid,
			'customerDataIsValid' => ls_shop_checkoutData::getInstance()->customerDataIsValid,

            'countNotSelectable' => ls_shop_checkoutData::getInstance()->arrCheckoutData['countNotSelectable'],
			'paymentMethodDataIsValid' => ls_shop_checkoutData::getInstance()->paymentMethodDataIsValid,
			'noPaymentMethodSelected' => ls_shop_checkoutData::getInstance()->noPaymentMethodSelected,
			'noPaymentMethodCouldBeDetermined' => ls_shop_checkoutData::getInstance()->noPaymentMethodCouldBeDetermined,

			'shippingMethodDataIsValid' => ls_shop_checkoutData::getInstance()->shippingMethodDataIsValid,
			'noShippingMethodSelected' => ls_shop_checkoutData::getInstance()->noShippingMethodSelected,
			'noShippingMethodCouldBeDetermined' => ls_shop_checkoutData::getInstance()->noShippingMethodCouldBeDetermined,

			'cartIsValid' => ls_shop_checkoutData::getInstance()->cartIsValid,

			'formPaymentMethodRadio' => ls_shop_checkoutData::getInstance()->formPaymentMethodRadio,
			'formShippingMethodRadio' => ls_shop_checkoutData::getInstance()->formShippingMethodRadio,

			'formPaymentMethodAdditionalData' => ls_shop_checkoutData::getInstance()->formPaymentMethodAdditionalData,
			'formShippingMethodAdditionalData' => ls_shop_checkoutData::getInstance()->formShippingMethodAdditionalData,

			'customPaymentMethodUserInterface' => ls_shop_checkoutData::getInstance()->customPaymentMethodUserInterface,
			'customShippingMethodUserInterface' => ls_shop_checkoutData::getInstance()->customShippingMethodUserInterface,

			'signUpLink' => ls_shop_checkoutData::getInstance()->signUpLink,

			'arrCustomerDataReview' => ls_shop_checkoutData::getInstance()->arrCustomerDataReview,
			'customerDataReview' => ls_shop_checkoutData::getInstance()->customerDataReview,

			'arrPaymentMethodAdditionalDataReview' => ls_shop_checkoutData::getInstance()->arrPaymentMethodAdditionalDataReview,
			'paymentMethodAdditionalDataReview' => ls_shop_checkoutData::getInstance()->paymentMethodAdditionalDataReview,

			'arrShippingMethodAdditionalDataReview' => ls_shop_checkoutData::getInstance()->arrShippingMethodAdditionalDataReview,
			'shippingMethodAdditionalDataReview' => ls_shop_checkoutData::getInstance()->shippingMethodAdditionalDataReview,

			'paymentMethodErrorMessage' => ls_shop_checkoutData::getInstance()->paymentMethodErrorMessage,
			'paymentMethodSuccessMessage' => ls_shop_checkoutData::getInstance()->paymentMethodSuccessMessage
		);
	}
}
?>