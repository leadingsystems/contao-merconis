<?php

namespace Merconis\Core;

	use Contao\Config;
    use Contao\Environment;
    use Contao\FrontendTemplate;
    use Contao\Input;
    use Contao\StringUtil;

    class ls_shop_paymentModule_santanderWebQuick extends ls_shop_paymentModule_standard {
		public $arrCurrentSettings = array();
		
		protected $santanderWebQuick_obj_vendor = null;
		
		protected $santanderWebQuick_obj_soapClient = null;
		
		protected $santanderWebQuick_str_wsdlLive = 'https://www.netadam.de/ws/services/FinanceInterface.wsdl';
		protected $santanderWebQuick_str_wsdlTest = 'https://testnetadam.santander.de/ws/services/FinanceInterface.wsdl';
                
		protected $santanderWebQuick_str_redirectUrlLive = 'https://www.netadam.de/webquick/deutsch/startWebfinanz.jsp?haendlernr=%s&ordernr=%s&leasID=%s';
		protected $santanderWebQuick_str_redirectUrlTest = 'https://testnetadam.santander.de/webquick/deutsch/startWebfinanz.jsp?haendlernr=%s&ordernr=%s&leasID=%s';
		
		protected $arr_testModeStreamContextOptions = array(
			"ssl" => array(
				"verify_peer" => false,
				"verify_peer_name" => false,
			)
		);
		
		public function initialize() {
			$this->santanderWebQuick_obj_vendor = new santanderWebQuick_vendor();
			$this->santanderWebQuick_obj_vendor->vendorNumber = $this->arrCurrentSettings['santanderWebQuickVendorNumber'];
			$this->santanderWebQuick_obj_vendor->password = $this->arrCurrentSettings['santanderWebQuickVendorPassword'];
			
			$this->santanderWebQuick_obj_soapClient = new \SoapClient(
					$this->arrCurrentSettings['santanderWebQuickLiveMode']
				? 	$this->santanderWebQuick_str_wsdlLive
				: 	$this->santanderWebQuick_str_wsdlTest,
				
				$this->arrCurrentSettings['santanderWebQuickLiveMode']
				? 	array()
				: 	array(
					'stream_context' => stream_context_create($this->arr_testModeStreamContextOptions)
				)
			);
			
			if (!isset($_SESSION['lsShopPaymentProcess']['santanderWebQuick'])) {
				$this->santanderWebQuick_resetSessionData();
			}
			
			$this->santanderWebQuick_getApplicationStatus();
		}
		
		protected function santanderWebQuick_resetSessionData() {
			$arrCheckoutFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'];
			$arrPaymentMethodAdditionalDataFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrPaymentMethodAdditionalData'];

			$_SESSION['lsShopPaymentProcess']['santanderWebQuick'] = array(
				/*
				 * True if a previous application has been canceld, e.g. because
				 * of a changed invoice amount. This flag needs to be preserved
				 * when resetting the session data.
				 */
				'bln_previousFinancingApplicationCanceled' => $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['bln_previousFinancingApplicationCanceled'] ?: false,
				
				/*
				 * Holds the financing amount so that we can detect, when
				 * the current invoice amount doesn't match the financing
				 * amount, in which case a financing application with the
				 * wrong amound needs to be cancelled.
				 */
				'float_financingAmount' => 0,

				'bln_statusCheckedAtLeastOnce' => false,

				/*
				 * Santander application status codes from "getApplicationStatus"
				 * api call. We start with -1 because this is not a santander
				 * value and it indicates that the financing application has
				 * not been started yet.
				 * 
				 * We use the status value -2 to indicate that something went
				 * wrong so bad that we can't use santander
				 */
				'int_status' => -1,

				/*
				 * The financeID generated by santander. We start with an
				 * empty value and overwrite with the result of the first
				 * api call ("storeFinanceData")
				 */
				'str_financeID' => '',

				/*
				 * The bankStatement as returned by Santander for the api
				 * call to "getApplicationStatus"
				 */
				'str_bankStatement' => '',

				/*
				 * We can't use the order no because it doesn't exist yet
				 * since the order isn't finished yet.
				 */
				'str_orderID' => substr(StringUtil::standardize($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameFirstName']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameFirstName']]['value']), 0,20).'_'.time(),

				/*
				 * The birthday values that we need to collect before we
				 * can call "storeFinanceData"
				 */
				'arr_birthday' => array(
					'bln_isValid' => false,
					'int_day' => '',
					'int_month' => '',
					'int_year' => ''
				)
			);
		}
		
		public function statusOkayToShowCustomUserInterface() {
			$arrCheckoutFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'];
			$arrPaymentMethodAdditionalDataFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrPaymentMethodAdditionalData'];

			$str_firstName = ($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameFirstName']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameFirstName']]['value']) ?: null;
			$str_lastName = ($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameLastName']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameLastName']]['value']) ?: null;

			if (!$this->santanderWebQuick_obj_vendor->vendorNumber || !$this->santanderWebQuick_obj_vendor->password) {
				$this->logPaymentError('ls_shop_paymentModule_santanderWebQuick::statusOkayToShowCustomUserInterface()', 'insufficient vendor data given');
				$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] = -2;
				return true;
			} else if (!$str_firstName || !$str_lastName) {
				$this->logPaymentError('ls_shop_paymentModule_santanderWebQuick::statusOkayToShowCustomUserInterface()', 'insufficient customer data given');
				$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] = -2;
				return true;
			} else {
				return true;
			}
		}
		
		public function getCustomUserInterface() {
			$obj_template = new FrontendTemplate('santanderWebQuickCustomUserInterface');

			$arrCheckoutFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'];
			$arrPaymentMethodAdditionalDataFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrPaymentMethodAdditionalData'];
						
			if ($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] == -2) {
				return $obj_template->parse();
			}
			
			if (Input::post('FORM_SUBMIT') && Input::post('FORM_SUBMIT') == 'santanderWebQuickCheckStatus') {
				$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['bln_statusCheckedAtLeastOnce'] = true;
			}
			
			/*
			 * Handle POST data from the birthday form
			 */
			if (Input::post('FORM_SUBMIT') && Input::post('FORM_SUBMIT') == 'santanderWebQuickBirthday') {
				$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['int_day'] = intval(Input::post('santanderWebQuickBirthdayDay')) ?: '';
				$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['int_month'] = intval(Input::post('santanderWebQuickBirthdayMonth')) ?: '';
				$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['int_year'] = intval(Input::post('santanderWebQuickBirthdayYear')) ?: '';
				
				$str_birthdayValidationResult = $this->santanderWebQuick_validateBirthday($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['int_day'], $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['int_month'], $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['int_year'], $this->arrCurrentSettings['santanderWebQuickMinAge']);
				switch ($str_birthdayValidationResult) {
					case 'enteredValuesInvalid':
						$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_birthdayError'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['birthdayError01'];
						$this->reload();
						break;
					
					case 'minAgeNotOk':
						$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_birthdayError'] = sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['birthdayError02'], $this->arrCurrentSettings['santanderWebQuickMinAge']);
						$this->reload();
						break;
					
					case 'birthdayOk':
						$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['bln_isValid'] = true;
						$this->reload();
						break;
				}
			}
			
			/*
			 * If we haven't called "storeFinanceData" yet, i.e. we don't have
			 * a financeID and a status value = -1 and the birthday is valid,
			 * we call "storeFinanceData"
			 */
			if (
					$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['bln_isValid']
				&&	$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] === -1
				&&	!$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_financeID']
			) {
				$obj_storeFinanceDataRequest = new santanderWebQuick_storeFinanceDataRequest();
				$obj_storeFinanceDataRequest->vendor = $this->santanderWebQuick_obj_vendor;
				$obj_storeFinanceDataRequest->totalPrice = number_format(ls_shop_cartX::getInstance()->calculation['invoicedAmount'], 2, '.', '');

				$obj_storeFinanceDataRequest->firstName = ($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameFirstName']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameFirstName']]['value']) ?: null;
				$obj_storeFinanceDataRequest->lastName = ($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameLastName']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameLastName']]['value']) ?: null;
				
				$obj_storeFinanceDataRequest->orderID = $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_orderID'];
				$obj_storeFinanceDataRequest->productName = sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc02'], date(Config::get('dateFormat'), time()));

				$obj_storeFinanceDataRequest->customerBirthday = $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['int_year'].'-'.$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['int_month'].'-'.$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['arr_birthday']['int_day'];

				$obj_storeFinanceDataRequest->salutation = ($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameSalutation']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameSalutation']]['value']) ?: null;
				$obj_storeFinanceDataRequest->emailAddress = ($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameEmailAddress']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameEmailAddress']]['value']) ?: null;
				$obj_storeFinanceDataRequest->street = ($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameStreet']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameStreet']]['value']) ?: null;
				$obj_storeFinanceDataRequest->city = ($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameCity']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameCity']]['value']) ?: null;
				$obj_storeFinanceDataRequest->zipCode = ($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameZipCode']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameZipCode']]['value']) ?: null;
				$obj_storeFinanceDataRequest->country = ($arrCheckoutFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameCountry']]['value'] ?: $arrPaymentMethodAdditionalDataFormFields[$this->arrCurrentSettings['santanderWebQuickFieldNameCountry']]['value']) ?: null;

				try {
					$obj_storeFinanceDataResponse = $this->santanderWebQuick_obj_soapClient->storeFinanceData($obj_storeFinanceDataRequest);
				} catch (\Exception $e) {
					$this->logPaymentError('ls_shop_paymentModule_santanderWebQuick::getCustomUserInterface()', 'Exception in api call "storeFinanceData": '.$e->getMessage()." |  \r\n"
							. 'totalPrice: '.$obj_storeFinanceDataRequest->totalPrice." |  \r\n"
							. 'firstName: '.$obj_storeFinanceDataRequest->firstName." |  \r\n"
							. 'lastName: '.$obj_storeFinanceDataRequest->lastName." |  \r\n"
							. 'orderID: '.$obj_storeFinanceDataRequest->orderID." |  \r\n"
							. 'productName: '.$obj_storeFinanceDataRequest->productName." |  \r\n"
							. 'customerBirthday: '.$obj_storeFinanceDataRequest->customerBirthday." |  \r\n"
							. 'salutation: '.$obj_storeFinanceDataRequest->salutation." |  \r\n"
							. 'emailAddress: '.$obj_storeFinanceDataRequest->emailAddress." |  \r\n"
							. 'street: '.$obj_storeFinanceDataRequest->street." |  \r\n"
							. 'city: '.$obj_storeFinanceDataRequest->city." |  \r\n"
							. 'zipCode: '.$obj_storeFinanceDataRequest->zipCode." |  \r\n"
							. 'country: '.$obj_storeFinanceDataRequest->country, '', '', false);
					
					$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] = -2;
					$this->reload();
				}
				
				if ($obj_storeFinanceDataResponse->state != 0) {
					$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] = -2;
					$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['float_financingAmount'] = 0;
					$this->logPaymentError('ls_shop_paymentModule_santanderWebQuick::getCustomUserInterface()', 'cannot store finance data. returned storeFinanceDataResponse state: '.$obj_storeFinanceDataResponse->state, '', '', false);
				} else {
					$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_financeID'] = $obj_storeFinanceDataResponse->financeID;
					$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['float_financingAmount'] = ls_shop_cartX::getInstance()->calculation['invoicedAmount'];
					$this->santanderWebQuick_getApplicationStatus(true);
				}
				$this->reload();
			}
			
			$obj_template->formAction = Environment::get('request');
			$obj_template->linkToSantander = sprintf(($this->arrCurrentSettings['santanderWebQuickLiveMode'] ? $this->santanderWebQuick_str_redirectUrlLive : $this->santanderWebQuick_str_redirectUrlTest), $this->arrCurrentSettings['santanderWebQuickVendorNumber'], $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_orderID'], $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_financeID']);
			return $obj_template->parse();
		}
		
		public function afterCheckoutFinish($orderIdInDb = 0, $order = array(), $afterCheckoutUrl = '', $oix = '') {
            $session = \System::getContainer()->get('merconis.session')->getSession();
            $arrLsShop =  $session->get('lsShop', []);
            $arrLsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = '';
            $session->set('lsShop', $arrLsShop);
			/*
			 * after finishing the order we reset the payment module's session
			 * data to prevent the financing application from being canceled
			 * because the cart is now empty which results in a changed "invoice
			 * amount"
			 */
			unset($_SESSION['lsShopPaymentProcess']['santanderWebQuick']);
		}

		public function showPaymentStatusInOverview($arrOrder = array(), $paymentMethod_moduleReturnData = '', $bln_useDetailsMode = false) {
			if (!count($arrOrder) || !$paymentMethod_moduleReturnData) {
				return null;
			}
			
			$outputValue = '';
			$paymentMethod_moduleReturnData = StringUtil::deserialize($paymentMethod_moduleReturnData);
			
			if (Input::get('santanderWebQuick_cancel') && Input::get('santanderWebQuick_cancel') == $arrOrder['id']) {
				$obj_cancelContractRequest = new santanderWebQuick_cancelContract();
				$obj_cancelContractRequest->vendor = $this->santanderWebQuick_obj_vendor;
				$obj_cancelContractRequest->financeID = $paymentMethod_moduleReturnData['str_financeID'];
				
				$obj_cancelContractResponse = $this->santanderWebQuick_obj_soapClient->cancelContract($obj_cancelContractRequest);
				
				if ($obj_cancelContractResponse->state != 0) {
					$this->logPaymentError('ls_shop_paymentModule_santanderWebQuick::showPaymentStatusInOverview()', 'Cannot cancel contract with finance id -- '.$paymentMethod_moduleReturnData['str_financeID'].' --. Returned cancelContractResponse state: '.$obj_cancelContractResponse->state, '', '', false);
					$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['error'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc18'];
				} else {
					$paymentMethod_moduleReturnData['bln_canceled'] = true;
					$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['success'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc17'];
				}
				
				if ($bln_useDetailsMode) {
					$this->update_paymentMethod_moduleReturnData_inOrder($arrOrder['id'], $paymentMethod_moduleReturnData);
					$this->redirect(ls_shop_generalHelper::getUrl(true, array('santanderWebQuick_updateStatus', 'santanderWebQuick_cancel', 'santanderWebQuick_markAsDelivered')));
				}
			}
			
			if (Input::get('santanderWebQuick_markAsDelivered') && Input::get('santanderWebQuick_markAsDelivered') == $arrOrder['id']) {
				$str_specifiedAmount = Input::post('specifiedAmount') ?: 0;
				$bln_specifiedAmountOk = true;
				if ($str_specifiedAmount) {
					if (preg_match('[^0-9.]', $str_specifiedAmount) || strpos($str_specifiedAmount, '.') === false) {
						$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['error'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc24'];
						$bln_specifiedAmountOk = false;
					} else if ($str_specifiedAmount > $arrOrder['invoicedAmount']) {
						$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['error'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc25'];
						$bln_specifiedAmountOk = false;
					}
				} else {
					$str_specifiedAmount = $arrOrder['invoicedAmount'];
				}
				
				if ($bln_specifiedAmountOk) {
					$obj_deliveredRequest = new santanderWebQuick_delivered();
					$obj_deliveredRequest->vendor = $this->santanderWebQuick_obj_vendor;
					$obj_deliveredRequest->financeID = $paymentMethod_moduleReturnData['str_financeID'];
					$obj_deliveredRequest->specifiedAmount = $str_specifiedAmount;
					
					$obj_deliveredResponse = $this->santanderWebQuick_obj_soapClient->delivered($obj_deliveredRequest);

					if ($obj_deliveredResponse->state != 0) {
						$this->logPaymentError('ls_shop_paymentModule_santanderWebQuick::showPaymentStatusInOverview()', 'Cannot report delivery for finance id -- '.$paymentMethod_moduleReturnData['str_financeID'].' --. Returned cancelContractResponse state: '.$obj_deliveredResponse->state, '', '', false);
						$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['error'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc20'];
					} else {
						$paymentMethod_moduleReturnData['bln_delivered'] = true;
						$paymentMethod_moduleReturnData['int_deliveredSpecifiedAmount'] = $str_specifiedAmount;
						$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['success'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc19'];
					}
				}

				if ($bln_useDetailsMode) {
					$this->update_paymentMethod_moduleReturnData_inOrder($arrOrder['id'], $paymentMethod_moduleReturnData);
					$this->redirect(ls_shop_generalHelper::getUrl(true, array('santanderWebQuick_updateStatus', 'santanderWebQuick_cancel', 'santanderWebQuick_markAsDelivered')));
				}
			}
			
			/*
			 * Update status if necessary
			 */
			if (
					$bln_useDetailsMode
				||	(Input::get('santanderWebQuick_updateStatus') && Input::get('santanderWebQuick_updateStatus') == $arrOrder['id'])
				||	(Input::get('santanderWebQuick_cancel') && Input::get('santanderWebQuick_cancel') == $arrOrder['id'])
				||	(Input::get('santanderWebQuick_markAsDelivered') && Input::get('santanderWebQuick_markAsDelivered') == $arrOrder['id'])
			) {
				$obj_getApplicationStatusRequest = new santanderWebQuick_getApplicationStatus();
				$obj_getApplicationStatusRequest->vendor = $this->santanderWebQuick_obj_vendor;
				$obj_getApplicationStatusRequest->financeID = $paymentMethod_moduleReturnData['str_financeID'];
				$obj_getApplicationStatusResponse = $this->santanderWebQuick_obj_soapClient->getApplicationStatus($obj_getApplicationStatusRequest);
				
				$paymentMethod_moduleReturnData['int_status'] = $obj_getApplicationStatusResponse->applicationState;
				$paymentMethod_moduleReturnData['str_bankStatement'] = $obj_getApplicationStatusResponse->bankStatement;
				$paymentMethod_moduleReturnData['utstamp_status'] = time();

				$this->update_paymentMethod_moduleReturnData_inOrder($arrOrder['id'], $paymentMethod_moduleReturnData);

				if (!$bln_useDetailsMode) {
					$this->redirect(ls_shop_generalHelper::getUrl(true, array('santanderWebQuick_updateStatus', 'santanderWebQuick_cancel', 'santanderWebQuick_markAsDelivered')));
				}
			}
									
			$str_statusUpdateUrl = ls_shop_generalHelper::getUrl();
			$str_statusUpdateUrl = $str_statusUpdateUrl.(strpos($str_statusUpdateUrl, '?') !== false ? '&' : '?').'santanderWebQuick_updateStatus='.$arrOrder['id'].'#santander_order'.$arrOrder['id'];

			$str_cancelUrl = ls_shop_generalHelper::getUrl();
			$str_cancelUrl = $str_cancelUrl.(strpos($str_cancelUrl, '?') !== false ? '&' : '?').'santanderWebQuick_cancel='.$arrOrder['id'].'#santander_order'.$arrOrder['id'];
			
			$str_markAsDeliveredUrl = ls_shop_generalHelper::getUrl();
			$str_markAsDeliveredUrl = $str_markAsDeliveredUrl.(strpos($str_markAsDeliveredUrl, '?') !== false ? '&' : '?').'santanderWebQuick_markAsDelivered='.$arrOrder['id'].'#santander_order'.$arrOrder['id'];

			ob_start();
			?>
			<div id="santander_order<?php echo $arrOrder['id']; ?>" class="paymentStatusInOverview santanderWebQuick status_<?php echo $paymentMethod_moduleReturnData['int_status']; ?><?php echo $bln_useDetailsMode ? ' details' : ''; ?>">
				<div class="statusBlock">
					<?php echo sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc13'], date(Config::get('datimFormat'), $paymentMethod_moduleReturnData['utstamp_status'])); ?>:
					<span class="status">
						<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['statusTitles'][$paymentMethod_moduleReturnData['int_status']] ?: $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['statusTitles']['unknown']; ?>
					</span>
				</div>
				<?php
					if ($paymentMethod_moduleReturnData['bln_delivered']) {
						?>
						<div class="deliveryStatusBlock">
							<p class="success"><?php echo sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc26'], ls_shop_generalHelper::outputPrice($paymentMethod_moduleReturnData['int_deliveredSpecifiedAmount'])); ?></p>
						</div>
						<?php
					}
				?>
				<?php
					if ($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['error']) {
						?>
						<p class="error"><?php echo $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['error']; ?></p>
						<?php
						unset($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['error']);
					}
					if ($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['success']) {
						?>
						<p class="success"><?php echo $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['success']; ?></p>
						<?php
						unset($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['BE']['messages'][$arrOrder['id']]['success']);
					}
				?>
				<div class="statusUpdate">
					<a href="<?php echo $str_statusUpdateUrl; ?>"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc14']; ?></a>
				</div>
				<?php
					if (
							!$paymentMethod_moduleReturnData['bln_canceled']
						&&	!in_array($paymentMethod_moduleReturnData['int_status'], array(0,2,6,7,11), true)
					) {
						?>
						<div class="cancel">
							<a onclick="if(!confirm('<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc21']; ?>'))return false;" href="<?php echo $str_cancelUrl; ?>"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc16']; ?></a>
						</div>
						<?php
					}
				?>

				<?php
					if (
							!$paymentMethod_moduleReturnData['bln_delivered']
						&&	$paymentMethod_moduleReturnData['int_status'] == 3
					) {
						?>
						<div class="markAsDelivered">
							<span onclick="this.getParent().getElement('form').setStyle('display', 'inline-block');"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc15']; ?></span>
							<form action="<?php echo $str_markAsDeliveredUrl; ?>" method="post">
								<input type="text" name="specifiedAmount" value="" placeholder="<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc23']; ?>" />
								<input onclick="if(!confirm('<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['santanderWebQuick']['misc22']; ?>'))return false;" type="submit" value="OK" />
							</form>
						</div>
						<?php
					}
				?>
						
				<?php
					if ($bln_useDetailsMode) {
						?>
						<div class="bankStatement"><?php echo urldecode($paymentMethod_moduleReturnData['str_bankStatement']); ?></div>
						<?php
					}
				?>
			</div>
			<?php
			$outputValue = ob_get_clean();
			return $outputValue;
		}
		
		protected function santanderWebQuick_getApplicationStatus($bln_triggerGeneralErrorOnMissingFinanceID = false, $bln_refresh = false) {
			if (
					isset($GLOBALS['lsShopPaymentProcess']['santanderWebQuick']['bln_alreadyGotStatus'])
				&&	$GLOBALS['lsShopPaymentProcess']['santanderWebQuick']['bln_alreadyGotStatus']
				&&	!$bln_refresh
			) {
				return;
			}
			
			$GLOBALS['lsShopPaymentProcess']['santanderWebQuick']['bln_alreadyGotStatus'] = true;
			
			/*
			 * The general error status can not be changed
			 */
			if ($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] === -2) {
				return;
			}
			
			if (!$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_financeID']) {
				$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] = $bln_triggerGeneralErrorOnMissingFinanceID ? -2 : -1;
				return;
			}
			
			/*
			 * Cancel an existing application if the invoice amount has changed
			 */
			if ($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['float_financingAmount'] != ls_shop_cartX::getInstance()->calculation['invoicedAmount']) {
				$obj_cancelContractRequest = new santanderWebQuick_cancelContract();
				$obj_cancelContractRequest->vendor = $this->santanderWebQuick_obj_vendor;
				$obj_cancelContractRequest->financeID = $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_financeID'];
				
				$obj_cancelContractResponse = $this->santanderWebQuick_obj_soapClient->cancelContract($obj_cancelContractRequest);
				
				if ($obj_cancelContractResponse->state != 0) {
					$this->logPaymentError('ls_shop_paymentModule_santanderWebQuick::santanderWebQuick_getApplicationStatus()', 'Cannot cancel contract with finance id -- '.$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_financeID'].' --. Returned cancelContractResponse state: '.$obj_cancelContractResponse->state, '', '', false);
				}
				
				$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['bln_previousFinancingApplicationCanceled'] = true;
				$this->santanderWebQuick_resetSessionData();
				$this->reload();
			}
			
			$obj_getApplicationStatusRequest = new santanderWebQuick_getApplicationStatus();
			$obj_getApplicationStatusRequest->vendor = $this->santanderWebQuick_obj_vendor;
			$obj_getApplicationStatusRequest->financeID = $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_financeID'];
			
			$obj_getApplicationStatusResponse = $this->santanderWebQuick_obj_soapClient->getApplicationStatus($obj_getApplicationStatusRequest);
			
			$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] = $obj_getApplicationStatusResponse->applicationState;
			$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_bankStatement'] = urlencode($obj_getApplicationStatusResponse->bankStatement);
			
			/*
			 * The status response from santander should never be -1. If it is, we
			 * trigger a general error because there's a conflict because a santander
			 * status code that shouldn't even exist and one of our own internal
			 * status codes.
			 */
			if ($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] === -1) {
				$_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'] = -2;
			}
		}
		
		protected function santanderWebQuick_validateBirthday(&$int_day, &$int_month, &$int_year, $int_minimumAge) {
			if ($int_day < 1 || $int_day > 31) {
				$int_day = '';
			}

			if ($int_month < 1 || $int_month > 12) {
				$int_month = '';
			}

			if ($int_year < 1900) {
				$int_year = '';
			}

			if (
					!$int_day
				||	!$int_month
				||	!$int_year
			) {
				return 'enteredValuesInvalid';
			}

			$utstamp_birthday = mktime(0, 0, 0, $int_month, $int_day, $int_year);

			$int_day = date('d', $utstamp_birthday);
			$int_month = date('m', $utstamp_birthday);
			$int_year = date('Y', $utstamp_birthday);
			
			/*
			 * Check if the required minimum age is ok
			 */
			if (strtotime('today midnight') - $utstamp_birthday < $int_minimumAge * 86400 * 365) {
				return 'minAgeNotOk';
			}
			
			return 'birthdayOk';
		}
		
		public function updatePaymentInfo($orderIdInDb = 0, $status = false) {
		}

		public function onAfterCheckoutPage($order = array()) {
		}
		
		public function checkoutFinishAllowed() {
			switch ($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status']) {
				case 0: // finance id has been generated but application has not been sent yet
				case 1: // application has been sent and is being checked by santander
				case 2: // rejected
				case 6: // canceled
				case 7: // rejected
				case -1: // we don't have a finance id yet
				case -2: // a general error occured and we can't use Santander
					return false;
					break;

				case 3: // approved
				case 5: // approved temporarily
				case 11: // activated
				case 13: // approved temporarily
					return true;
					break;
			}
		}
		
		public function statusOkayToRedirectToCheckoutFinish() {
			switch ($_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status']) {
				case 0: // finance id has been generated but application has not been sent yet
				case 1: // application has been sent and is being checked by santander
				case 2: // rejected
				case 6: // canceled
				case 7: // rejected
				case -1: // we don't have a finance id yet
				case -2: // a general error occured and we can't use Santander
					return false;
					break;

				case 3: // approved
				case 5: // approved temporarily
				case 11: // activated
				case 13: // approved temporarily
					return true;
					break;
			}
		}
		
		public function getPaymentInfo() {
			$arr_info = array(
				'str_financeID' => $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_financeID'],
				'str_bankStatement' => $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['str_bankStatement'],
				'int_status' => $_SESSION['lsShopPaymentProcess']['santanderWebQuick']['int_status'],
				'utstamp_status' => time(),
				'bln_delivered' => false,
				'int_deliveredSpecifiedAmount' => 0,
				'bln_canceled' => false
			);
			
			return serialize($arr_info);
		}
		
		public function showPaymentDetailsInBackendOrderDetailView($arrOrder = array(), $paymentMethod_moduleReturnData = '') {
			if (!count($arrOrder) || !$paymentMethod_moduleReturnData) {
				return null;
			}
			
			return $this->showPaymentStatusInOverview($arrOrder, $paymentMethod_moduleReturnData, true);
		}
	}

	class santanderWebQuick_vendor {
		public $vendorNumber;
		public $password;
	}
	
	class santanderWebQuick_storeFinanceDataRequest {
		public $vendor;
		public $orderID;
		public $productName;
		public $totalPrice;
		public $salutation;
		public $firstName;
		public $lastName;
		public $emailAddress;
		public $street;
		public $city;
		public $zipCode;
		public $country;
		public $customerBirthday;
	}
	
	class santanderWebQuick_getApplicationStatus {
		public $financeID;
	}
	
	class santanderWebQuick_cancelContract {
		public $financeID;
	}
	
	class santanderWebQuick_delivered {
		public $financeID;
		public $specifiedAmount;
	}
