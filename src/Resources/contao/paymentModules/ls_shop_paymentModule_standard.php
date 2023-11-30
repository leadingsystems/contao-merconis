<?php

namespace Merconis\Core;

	use Contao\CoreBundle\Monolog\ContaoContext;
    use Contao\StringUtil;
    use Contao\System;

    class ls_shop_paymentModule_standard extends \Controller {
		public function logPaymentError($context = '', $errorInformation01 = '', $errorInformation02 = '', $errorInformation03 = '', $bln_resetSelectedPaymentMethod = true) {
			## fixEndlessRecursionOnPaymentError begin ##
			/*
			 * Setting this flag in the logPaymentError functions of special payment modules is very important because it is possible
			 * that this function is being called directly from a payment module itself which means that the general logPaymentError function
			 * in ls_shop_paymentModule is skipped and therefore can not set this flag itself.
			 */
			$_SESSION['lsShop']['blnPaymentOrShippingErrorOccured'] = true;
			## fixEndlessRecursionOnPaymentError end ##

			error_log('Payment error in payment method "'.$this->arrCurrentSettings['title'].'" (type: '.$this->arrCurrentSettings['type'].') in context "'.$context.'"');
			
			ob_start();
			
			if ($errorInformation01) {
				echo "======================== Extended error information no. 1: =============================\r\n";
				if (is_array($errorInformation01)){
					print_r($errorInformation01);
				} else {
					var_dump($errorInformation01);
				}
			}
			if ($errorInformation02) {
				echo "======================== Extended error information no. 2: =============================\r\n";
				if (is_array($errorInformation02)){
					print_r($errorInformation02);
				} else {
					var_dump($errorInformation02);
				}
			}
			if ($errorInformation03) {
				echo "======================== Extended error information no. 3: =============================\r\n";
				if (is_array($errorInformation03)){
					print_r($errorInformation03);
				} else {
					var_dump($errorInformation03);
				}
			}
			
			$outputBuffer = ob_get_contents();
			ob_end_clean();

            System::getContainer()->get('monolog.logger.contao')->info(
                'MERCONIS: Payment error in payment method "'.$this->arrCurrentSettings['title'].'" (type: '.$this->arrCurrentSettings['type'].') in context "'.$context.'"'."\r\n\r\n".$outputBuffer,
                ['contao' => new ContaoContext('MERCONIS MESSAGES', TL_MERCONIS_ERROR)]
            );
			
			if ($bln_resetSelectedPaymentMethod) {
				ls_shop_checkoutData::getInstance()->resetSelectedPaymentMethod();
			}
		}
		
		public function get_paymentMethod_moduleReturnData_forOrderId($int_orderID) {
			if (!$int_orderID) {
				return null;
			}
			
			$obj_dbres_moduleReturnData = \Database::getInstance()->prepare("
				SELECT		`paymentMethod_moduleReturnData`
				FROM		`tl_ls_shop_orders`
				WHERE		`id` = ?
			")
			->execute($int_orderID);
			
			if (!$obj_dbres_moduleReturnData->numRows) {
				return null;
			}
			
			return StringUtil::deserialize($obj_dbres_moduleReturnData->first()->paymentMethod_moduleReturnData);
		}
		
		public function update_paymentMethod_moduleReturnData_inOrder($int_orderID = 0, $var_paymentMethod_moduleReturnData = '') {
			if (!$int_orderID) {
				return;
			}
			
			if (is_array($var_paymentMethod_moduleReturnData)) {
				$var_paymentMethod_moduleReturnData = serialize($var_paymentMethod_moduleReturnData);
			}
			
			$obj_dbquery = \Database::getInstance()->prepare("
				UPDATE	`tl_ls_shop_orders`
				SET		`paymentMethod_moduleReturnData` = ?
				WHERE	`id` = ?
			")
			->limit(1)
			->execute($var_paymentMethod_moduleReturnData, $int_orderID);
		}
		
		public function update_fieldValue_inOrder($int_orderID = 0, $str_fieldName = '', $var_fieldValue = '') {
			if (!$int_orderID || !$str_fieldName) {
				return;
			}
			
			if (is_array($var_fieldValue)) {
				$var_fieldValue = serialize($var_fieldValue);
			}
			
			$obj_dbquery = \Database::getInstance()->prepare("
				UPDATE	`tl_ls_shop_orders`
				SET		`".$str_fieldName."` = ?
				WHERE	`id` = ?
			")
			->limit(1)
			->execute($var_fieldValue, $int_orderID);

			if (strpos($str_fieldName, '_currentStatus') !== false) {
				$objOrderMessages = new ls_shop_orderMessages($int_orderID, 'onStatusChangeImmediately', 'sendWhen', null, true);
				$objOrderMessages->sendMessages();
			}
		}
		
		public function redirectToErrorPage($context = '', $errorInformation01 = '', $errorInformation02 = '', $errorInformation03 = '') {
			/** @var \PageModel $objPage */
			global $objPage;
			
			## fixEndlessRecursionOnPaymentError begin ##
			/*
			 * Setting this flag in the redirectToErrorPage functions of special payment modules is very important because it is possible
			 * that this function is being called directly from a payment module itself which means that the general redirectToErrorPage function
			 * in ls_shop_paymentModule is skipped and therefore can not set this flag itself.
			 */
			$_SESSION['lsShop']['blnPaymentOrShippingErrorOccured'] = true;
			## fixEndlessRecursionOnPaymentError end ##
			
			$this->logPaymentError($context, $errorInformation01, $errorInformation02, $errorInformation03);
			
			ls_shop_checkoutData::getInstance()->resetSelectedPaymentMethod();
			
			## fixEndlessRecursionOnPaymentError begin ##
			/*
			 * Only redirect to the error page if the current page is one of a few specific pages in the checkout process.
			 * If it's not, just reload the current page. 
			 */
			if (
					is_object($objPage)
				&&	(
						ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id') == $objPage->id
						||	ls_shop_languageHelper::getLanguagePage('ls_shop_reviewPages', false, 'id') == $objPage->id
						||	ls_shop_languageHelper::getLanguagePage('ls_shop_checkoutFinishPages', false, 'id') == $objPage->id
					)
			) {
				$this->redirect(ls_shop_languageHelper::getLanguagePage('ls_shop_checkoutPaymentErrorPages'));
			} else {
                if (!\Environment::get('isAjaxRequest') && $_SESSION['ls_cajax']['requestData'] === null) {
                    $this->reload();
                }
			}
			## fixEndlessRecursionOnPaymentError end ##
		}
		
		public function initialize() {
		}
		
		public function afterPaymentMethodSelection() {
		}
				
		public function afterPaymentMethodAdditionalDataConfirm() {
		}
						
		public function statusOkayToShowAdditionalDataForm() {
			return true;
		}
		
		public function statusOkayToShowCustomUserInterface() {
			return true;
		}
		
		public function statusOkayToRedirectToCheckoutFinish() {
			return true;
		}
		
		public function beforeCheckoutFinish() {
		}
				
		public function checkoutFinishAllowed() {
			return true;
		}
				
		public function afterCheckoutFinish() {
			$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = '';
		}

		public function check_usePaymentAfterCheckoutPage() {
			return false;
		}
				
		public function onAfterCheckoutPage() {
		}

		public function onPaymentAfterCheckoutPage() {
		}

		public function getPaymentInfo() {
			return '';
		}
		
		public function getCustomUserInterface() {
			return '';
		}
		
		public function getFormIDForAdditionalData($formID) {
			return $formID;
		}

		private function getPaymentMethodMessage($type = 'success') {
			/** @var \PageModel $objPage */
			global $objPage;
			$msg = '';

			// Only show messages if the checkout page displaying the payment selection is currently opened.
			if (
					($objPage->id ?? null) == ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id')
				&&	isset($_SESSION['lsShopPaymentProcess']['standard']['messages'][$type])
			) {
				if (!is_array($_SESSION['lsShopPaymentProcess']['standard']['messages'][$type])) {
					$msg = $_SESSION['lsShopPaymentProcess']['standard']['messages'][$type];
				} else {
					foreach ($_SESSION['lsShopPaymentProcess']['standard']['messages'][$type] as $msgPart) {
						if ($msg) {
							$msg .= '<br />';
						}
						$msg .= $msgPart;
					}
				}
				unset($_SESSION['lsShopPaymentProcess']['standard']['messages'][$type]);
			}
			return $msg;			
		}
		
		public function getPaymentMethodSuccessMessage() {
			return $this->getPaymentMethodMessage('success');
		}
		
		public function getPaymentMethodErrorMessage() {
			return $this->getPaymentMethodMessage('error');
		}
		
		private function setPaymentMethodMessage($msg = '', $type = 'success') {
			if (!$msg) {
				return;
			}
			
			// Ist eine Fehlermeldung bereits enthalten, so wird sie nicht erneut hinzugefügt, da doppelte Meldungen sinnlos sind
			if (
					isset($_SESSION['lsShopPaymentProcess']['standard']['messages'][$type])
				&&	is_array($_SESSION['lsShopPaymentProcess']['standard']['messages'][$type])
				&&	in_array($msg, $_SESSION['lsShopPaymentProcess']['standard']['messages'][$type])
			) {
				return;
			}
			
			$_SESSION['lsShopPaymentProcess']['standard']['messages'][$type][] = $msg;
		}
		
		public function setPaymentMethodSuccessMessage($msg = '') {
			$this->setPaymentMethodMessage($msg, 'success');
		}
		
		public function setPaymentMethodErrorMessage($msg = '') {
			$this->setPaymentMethodMessage($msg, 'error');
		}
		
		public function showPaymentDetailsInBackendOrderDetailView() {
		}
		
		public function showPaymentStatusInOverview() {
		}
		
		public function modifyConfirmOrderForm($form = '') {
			return $form;
		}
		
		public function determineOix() {
			return '';
		}
		
	}
?>