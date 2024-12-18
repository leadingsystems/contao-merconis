<?php

namespace Merconis\Core;

use Contao\System;
use LeadingSystems\Helpers\FlexWidget;

class ModuleCart extends \Module {
	public function generate() {
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$objTemplate = new \BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS Warenkorb ###';
			return $objTemplate->parse();
		}
		
		return parent::generate();
	}

	public function compile() {
		/** @var \PageModel $objPage */
		global $objPage;

		$arrWidgets = array();
		
		foreach (ls_shop_cartX::getInstance()->itemsExtended as $productCartKey => $cartItem) {
			$arrWidgets[$productCartKey] = array();
			/*
			 * Buttons für die Mengenänderung
			 */
			$obj_FlexWidget_inputQuantity = new FlexWidget(
				array(
					'str_uniqueName' => 'quantity_item',
                    'str_template' => 'ls_flexWidget_defaultNumber',

					/*
					 * This is necessary because ModuleCart could be compiled twice, e.g. if the mini cart and the big
					 * cart exist on the same page
					 */
					'bln_multipleWidgetsWithSameNameAllowed' => true,

					'arr_validationFunctions' => array(
						array(
							'str_className' => '\Merconis\Core\FlexWidgetValidator',
							'str_methodName' => 'quantityInput'
						)
					),

					'arr_moreData' => array(
						'class' => 'quantity-item',
                        'decimalsAmount' => $cartItem['objProduct']->_quantityDecimals
					),

					'str_label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText016'],
					'str_allowedRequestMethod' => 'post',
					'var_value' => ls_shop_cartHelper::cleanQuantity(
					    $cartItem['objProduct'],
                        $cartItem['quantity']
                    )
				)
			);

			$arrWidgets[$productCartKey]['inputQuantity'] = $obj_FlexWidget_inputQuantity->getOutput();

			/*
			 * Aktualisieren des Warenkorbs, sofern angefordert
			 */
			if (\Input::post('FORM_SUBMIT') && preg_match('/^product_quantity_form_/siU', \Input::post('FORM_SUBMIT')) && \Input::post('productID') == $productCartKey) {
				if (!$obj_FlexWidget_inputQuantity->bln_hasErrors) {
					ls_shop_cartHelper::updateCartItem(\Input::post('productID'), $obj_FlexWidget_inputQuantity->getValue());
					$this->reload();
				}
			} else if(\Input::post('FORM_SUBMIT') && preg_match('/^product_delete_form_/siU', \Input::post('FORM_SUBMIT')) && \Input::post('productIDDelete') == $productCartKey) {
				// Negative Menge für Warenkorbposition bedeutet eine Lösch-Anforderung
				ls_shop_cartHelper::updateCartItem(\Input::post('productIDDelete'), -1);
				$this->reload();
			}
			
		}

		$this->Template = new \FrontendTemplate($this->ls_shop_cart_template);

		$formConfirmOrder = ls_shop_checkoutData::getInstance()->formConfirmOrder;
		// ### paymentMethod callback ########################
		$obj_paymentModule = ls_shop_paymentModule::getInstance();
		$formConfirmOrder = $obj_paymentModule->modifyConfirmOrderForm($formConfirmOrder);
		// ###################################################
		$this->Template->formConfirmOrder = $formConfirmOrder;

		
		$this->Template->allowCheckout = $GLOBALS['TL_CONFIG']['ls_shop_allowCheckout'];
		
		$this->Template->noVATBecauseOfEnteredIDs = ls_shop_generalHelper::checkVATID();
		
		/*
		 * Gutschein-Handling
		 */
		if (\Input::get('deleteCoupon')) {
			ls_shop_cartHelper::deleteUsedCoupon(\Input::get('deleteCoupon'));
			$this->redirect(\Controller::generateFrontendUrl($objPage->row()));
		}

		$obj_FlexWidget_inputCoupon = new FlexWidget(
			array(
				'str_uniqueName' => 'use_coupon',

				/*
				 * This is necessary because ModuleCart could be compiled twice, e.g. if the mini cart and the big
				 * cart exist on the same page
				 */
				'bln_multipleWidgetsWithSameNameAllowed' => true,

				'str_label' =>
                    $GLOBALS['TL_LANG']['MOD']['ls_shop']['coupon']['text001'],
				'str_allowedRequestMethod' => 'post',

				'arr_validationFunctions' => array(
					array(
						'str_className' => '\Merconis\Core\FlexWidgetValidator',
						'str_methodName' => 'couponWidget'
					)
				),

                'arr_moreData' => [
                    'class' => 'coupon-code'
                ]
			)
		);

		$this->Template->fflInputCoupon = $obj_FlexWidget_inputCoupon->getOutput();
		
		if (\Input::post('FORM_SUBMIT') == 'useCouponSubmit') {
			if (!$obj_FlexWidget_inputCoupon->bln_hasErrors) {
				ls_shop_cartHelper::useCoupon($obj_FlexWidget_inputCoupon->getValue());
				$this->redirect(\Environment::get('request').'#coupon');
			}
		}
		
		/*
		 * Ende Gutschein-Handling
		 */

		$this->Template->arrWidgets = $arrWidgets;
		
		$this->Template->formAction = \Environment::get('request');
		$this->Template->formMethod = 'post';

		ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages');

		$groupSettings = ls_shop_generalHelper::getGroupSettings4User();
		$this->Template->minimumOrderValue = $groupSettings['lsShopMinimumOrderValue'];
		
		$this->Template->minimumOrderValueOkay = ls_shop_generalHelper::check_minimumOrderValueIsReached();
		
		$this->Template->bln_couponStatusOkay = true;
		if (is_array(ls_shop_cartX::getInstance()->calculation['couponValues'])) {
			foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $couponID => $arrCouponValues) {
				if (!ls_shop_cartHelper::check_couponIsValid($couponID)) {
					$this->Template->bln_couponStatusOkay = false;
					break;
				}
			}
		}
		
		
		###############
		###############

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
		###############
		###############
	}
}