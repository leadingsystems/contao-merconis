<?php

namespace Merconis\Core;
use Composer\InstalledVersions;
use Contao\Date;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;
use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;

	class ls_shop_paymentModule_saferpay extends ls_shop_paymentModule_standard {
		public $arrCurrentSettings = array();
		
		protected $arr_saferpay_apiUrls = array(
			'test' => 'https://test.saferpay.com/api',
			'live' => 'https://www.saferpay.com/api'
		);
		
		protected $arr_saferpay_backofficeUrls = array(
			'test' => 'https://test.saferpay.com/BO',
			'live' => 'https://www.saferpay.com/BO'
		);
		
		protected $arr_saferpay_backofficeDetailsUrls = array(
			'test' => 'https://test.saferpay.com/BO/Commerce/JournalDetail?gxid=',
			'live' => 'https://www.saferpay.com/BO/Commerce/JournalDetail?gxid='
		);
		
		protected $str_saferpay_apiSpecVersion = '1.3';
		
		public function initialize() {
		}

		public function checkoutFinishAllowed() {
			/*
			 * With saferpay, the checkout is always allowed, because we want the order to be finished
			 * bindlingly before we redirect the user to saferpay.
			 */
			return true;
		}
		
		public function modifyConfirmOrderForm($str_form = '') {
			$outputToAdd = '<div class="confirmCheckoutMessageSaferpay'.($this->arrCurrentSettings['cssClass'] ? ' '.$this->arrCurrentSettings['cssClass'] : '').'">'.$GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['confirmCheckoutMessage'].'</div>';
			$str_form = $str_form.$outputToAdd;
			return $str_form;
		}
		
		/*
		 * After the checkout the payment is processed.
		 */
		public function afterCheckoutFinish($int_orderIdInDb = 0, $arr_order = array(), $afterCheckoutUrl = '', $oix = '') {
			// Reset the special payment info
			$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = '';
			
			// if there are insufficient parameters the payment execution is aborted
			if (!$int_orderIdInDb || !is_array($arr_order) || !count($arr_order)) {
				// write an error message to the special payment info and log the error
				$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['paymentErrorAfterFinishedOrder'];
				$this->logPaymentError(__METHOD__, 'insufficient parameters given');
				return;
			}

			/*
			 * ----------
			 * Generating the after checkout url that can be used for saferpay
			 */
			// adding the oih as a parameter to the afterCheckoutUrl
			$afterCheckoutUrl = $afterCheckoutUrl.(preg_match('/\?/', $afterCheckoutUrl) ? '&' : '?').'oix='.$oix;
			
			// make an absolute URL
			if (!preg_match('@^https?://@i', $afterCheckoutUrl)) {
				$afterCheckoutUrl = ls_shop_generalHelper::getEnvironmentBase(true) . $afterCheckoutUrl;
			}
			/*
			 * ----------
			 */			
			
			// get the payment url and redirect the customer
			try {
				$arr_initializationResponse = $this->saferpay_initializePayment($arr_order, $afterCheckoutUrl, $oix);
				$paymentUrl = $arr_initializationResponse['RedirectUrl'];
			} catch (\Exception $e) {
				$this->update_paymentMethod_moduleReturnData_inOrder($int_orderIdInDb, array(
					'str_saferpayToken' => '',
					'str_transactionId' => '',
					'arr_paymentMeans' => array(),
					'arr_status' => array(
						0 => array(
							'str_statusValue' => 'ERROR',
							'str_statusReason' => 'An exception occured when requesting the saferpay payment url',
							'utstamp_statusModifiedTime' => time(),
							'arr_statusDetails' => array()
						)
					)
				));
				$this->update_fieldValue_inOrder($int_orderIdInDb, 'saferpay_currentStatus', 'ERROR');
				$this->logPaymentError(__METHOD__, $e->getMessage());
				return;
			}
			
			$this->update_paymentMethod_moduleReturnData_inOrder($int_orderIdInDb, array(
				'str_saferpayToken' => $arr_initializationResponse['Token'],
				'str_transactionId' => '',
				'arr_paymentMeans' => array(),
				'arr_status' => array(
					0 => array(
						'str_statusValue' => 'STARTED',
						'str_statusReason' => 'initialization/redirecting to saferpay',
						'utstamp_statusModifiedTime' => time(),
						'arr_statusDetails' => null
					)
				)
			));
			$this->update_fieldValue_inOrder($int_orderIdInDb, 'saferpay_currentStatus', 'STARTED');
			
			$this->redirect($paymentUrl);
			exit;
		}
		
		public function showPaymentDetailsInBackendOrderDetailView($arr_order = array(), $arr_paymentMethod_moduleReturnData = '') {
			if (!count($arr_order) || !$arr_paymentMethod_moduleReturnData) {
				return null;
			}
			
			$str_outputValue = '';
			
			$arr_paymentMethod_moduleReturnData = StringUtil::deserialize($arr_paymentMethod_moduleReturnData);
			
			/*
			 * The newest status is the last in the array but we want to display
			 * the newest status first, so we reverse the status array.
			 */
			$arr_statusAllEntries = array_reverse($arr_paymentMethod_moduleReturnData['arr_status']);
			$arr_currentStatus = $arr_statusAllEntries[0];
			
			if (Input::get('saferpay_cancel') == $arr_order['id']) {
				$str_request = StringUtil::ampersand(Environment::get('request'), true);
				$str_request = preg_replace('/&amp;saferpay_cancel=[0-9]*/', '', $str_request);
				
				try {
					$arr_cancelResponse = $this->saferpay_cancelPayment($arr_paymentMethod_moduleReturnData['str_transactionId']);
					
					$arr_paymentMethod_moduleReturnData['arr_status'][] = array(
						'str_statusValue' => 'CANCELED',
						'str_statusReason' => 'cancelation',
						'utstamp_statusModifiedTime' => time(),
						'arr_statusDetails' => $arr_cancelResponse
					);

					$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_paymentMethod_moduleReturnData);
					$this->update_fieldValue_inOrder($arr_order['id'], 'saferpay_currentStatus', 'CANCELED');
				} catch (\Exception $e) {
					$arr_paymentMethod_moduleReturnData['arr_status'][] = array(
						/*
						 * We keep the latest status because nothing has changed
						 * since the cancelation didn't work.
						 */
						'str_statusValue' => $arr_currentStatus['str_statusValue'],
						'str_statusReason' => 'cancelation failed',
						'utstamp_statusModifiedTime' => time(),
						'arr_statusDetails' => array()
					);

					$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_paymentMethod_moduleReturnData);
					$this->update_fieldValue_inOrder($arr_order['id'], 'saferpay_currentStatus', $arr_currentStatus['str_statusValue']);
				}
					
				$this->redirect($str_request);
			}
			
			if (Input::get('saferpay_capture') == $arr_order['id']) {
				$str_request = StringUtil::ampersand(Environment::get('request'), true);
				$str_request = preg_replace('/&amp;saferpay_capture=[0-9]*/', '', $str_request);
				
				try {
					$arr_captureResponse = $this->saferpay_capture($arr_paymentMethod_moduleReturnData['str_transactionId']);
					
					$arr_paymentMethod_moduleReturnData['arr_status'][] = array(
						'str_statusValue' => 'CAPTURED',
						'str_statusReason' => 'capture',
						'utstamp_statusModifiedTime' => time(),
						'arr_statusDetails' => $arr_captureResponse
					);

					$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_paymentMethod_moduleReturnData);
					$this->update_fieldValue_inOrder($arr_order['id'], 'saferpay_currentStatus', 'CAPTURED');
				} catch (\Exception $e) {
					$arr_paymentMethod_moduleReturnData['arr_status'][] = array(
						/*
						 * We keep the latest status because nothing has changed
						 * since the capture didn't work.
						 */
						'str_statusValue' => $arr_currentStatus['str_statusValue'],
						'str_statusReason' => 'capture failed',
						'utstamp_statusModifiedTime' => time(),
						'arr_statusDetails' => array()
					);

					$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_paymentMethod_moduleReturnData);
					$this->update_fieldValue_inOrder($arr_order['id'], 'saferpay_currentStatus', $arr_currentStatus['str_statusValue']);
				}
					
				$this->redirect($str_request);
			}
			
			ob_start();
			?>
			<div class="paymentDetails saferpay">
				<h2>
					<?php
					$str_backofficeUrl = $this->arr_saferpay_backofficeUrls[$this->arrCurrentSettings['saferpay_liveMode'] ? 'live' : 'test'];
					?>
					<a href="<?php echo $str_backofficeUrl; ?>" target="_blank">
						<img src="bundles/leadingsystemsmerconis/images/payment/saferpay_logo_small.png" alt="SAFERPAY">
					</a>
					<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['headlineBackendDetailsInfo']; ?>
				</h2>
				<div class="content">
					<div class="transactionID">
						<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['transactionID']; ?>: </span>
						<span class="value"><?php echo isset($arr_paymentMethod_moduleReturnData['str_transactionId']) && $arr_paymentMethod_moduleReturnData['str_transactionId'] ? $arr_paymentMethod_moduleReturnData['str_transactionId'] : '---'; ?></span>
					</div>
					<div class="paymentMeans">
						<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['paymentMeans']; ?>: </span>
						<span class="value"><?php echo isset($arr_paymentMethod_moduleReturnData['arr_paymentMeans']['Brand']['Name']) && $arr_paymentMethod_moduleReturnData['arr_paymentMeans']['Brand']['Name'] ? $arr_paymentMethod_moduleReturnData['arr_paymentMeans']['Brand']['Name'] : '---'; ?></span>
					</div>
					<div class="capturePayment">
						<?php
							if (
									isset($arr_paymentMethod_moduleReturnData['str_transactionId']) && $arr_paymentMethod_moduleReturnData['str_transactionId']
								&&	$arr_currentStatus['str_statusValue'] === 'AUTHORIZED'
							) {
								$str_request = StringUtil::ampersand(Environment::get('request'), true);
								$str_request .= '&saferpay_capture='.$arr_order['id'];
								?>
								<a onclick="Backend.getScrollOffset();" href="<?php echo $str_request; ?>">
									<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['capturePayment']; ?>
								</a>
								<?php
							}
						?>
					</div>
					<div class="cancelPayment">
						<?php
							if (
									isset($arr_paymentMethod_moduleReturnData['str_transactionId']) && $arr_paymentMethod_moduleReturnData['str_transactionId']
								&&	($arr_currentStatus['str_statusValue'] === 'AUTHORIZED' || $arr_currentStatus['str_statusValue'] === 'CAPTURED')
							) {
								$str_request = StringUtil::ampersand(Environment::get('request'), true);
								$str_request .= '&saferpay_cancel='.$arr_order['id'];
								?>
								<a onclick="Backend.getScrollOffset();" href="<?php echo $str_request; ?>">
									<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['cancelPayment']; ?>
								</a>
								<?php
							}
						?>
					</div>
					<div class="status">
						<?php
							foreach ($arr_statusAllEntries as $arr_statusEntry) {
								?>
								<div class="value">
									<div class="statusModifiedTime"><?php echo date($GLOBALS['TL_CONFIG']['dateFormat'].', H:i:s', $arr_statusEntry['utstamp_statusModifiedTime']); ?></div>
									<div class="statusValue"><?php echo strtoupper($arr_statusEntry['str_statusValue']); ?><?php echo $arr_statusEntry['str_statusValue'] == 'APPOINTED' && $arr_statusEntry['arr_statusDetails']['transaction_status'] && $arr_statusEntry['arr_statusDetails']['transaction_status'] != 'completed' ? ' ('.strtoupper($arr_statusEntry['arr_statusDetails']['transaction_status']).')' : ''; ?></div>
									<div class="statusReason"><?php echo $arr_statusEntry['str_statusReason']; ?></div>
								</div>
								<?php
							}
						?>
					</div>
				</div>
			</div>
			
			<?php if (false) { ?>
				<pre>
					<?php print_r($arr_statusAllEntries); ?>
				</pre>
			<?php } ?>
			<?php
			$str_outputValue = ob_get_clean();
			return $str_outputValue;
		}

		public function showPaymentStatusInOverview($arr_order = array(), $arr_paymentMethod_moduleReturnData = '') {
			if (!count($arr_order) || !$arr_paymentMethod_moduleReturnData) {
				return null;
			}
			
			$str_outputValue = '';
			$arr_paymentMethod_moduleReturnData = StringUtil::deserialize($arr_paymentMethod_moduleReturnData);
			
			$arr_statusAllEntries = array_reverse($arr_paymentMethod_moduleReturnData['arr_status']);
			$arr_currentStatus = $arr_statusAllEntries[0];

			
			ob_start();
			?>
			<div class="paymentStatusInOverview saferpay <?php echo strtolower(preg_replace('/\s+/', '-', $arr_currentStatus['str_statusValue'])); ?>">
				<img src="bundles/leadingsystemsmerconis/images/payment/saferpay_logo_small.png" alt="SAFERPAY">
				<span class="status">
					<?php echo strtoupper($arr_currentStatus['str_statusValue']); ?>
				</span>
				<div class="paymentMeans">
					<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['paymentMeans']; ?>: </span>
					<span class="value"><?php echo isset($arr_paymentMethod_moduleReturnData['arr_paymentMeans']['Brand']['Name']) && $arr_paymentMethod_moduleReturnData['arr_paymentMeans']['Brand']['Name'] ? $arr_paymentMethod_moduleReturnData['arr_paymentMeans']['Brand']['Name'] : '---'; ?></span>
				</div>
			</div>
			<?php
			$str_outputValue = ob_get_clean();
			return $str_outputValue;
		}

		/*
		 * This payment function is called on the after checkout page if an oix is given there.
		 * Depending on the "saferpay_action" GET parameter this function can handle several
		 * situations, including notifications
		 */
		public function onAfterCheckoutPage($arr_order = array()) {
			if (Input::get('saferpay_action')) {
				switch (Input::get('saferpay_action')) {
					case 'abort':
						/*
						 * write the error message to the special payment info
						 * and update the payment status in the order record
						 */
						$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['paymentErrorAfterFinishedOrder'];
						
						$arr_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($arr_order['id']);
						$arr_moduleReturnData['arr_status'][] = array(
							'str_statusValue' => 'ABORTED',
							'str_statusReason' => 'Buyer aborted the transaction',
							'utstamp_statusModifiedTime' => time(),
							'arr_statusDetails' => array()							
						);
						$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_moduleReturnData);
						$this->update_fieldValue_inOrder($arr_order['id'], 'saferpay_currentStatus', 'ABORTED');
						
						/*
						 * redirect in order to get rid of the "aborted" value for
						 * the saferpay_action parameter which would cause multiple
						 * abortions to be written to the order record if the user
						 * would reload the page with the same url.
						 */
						$this->redirect(preg_replace('/saferpay_action=abort/', 'saferpay_action=abort_handled', Environment::get('request')));
						break;
					
					case 'fail':
						/* 
						 * write the error message to the special payment info
						 * and update the payment status in the order record
						 */
						$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['paymentErrorAfterFinishedOrder'];
						
						$arr_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($arr_order['id']);
						$arr_moduleReturnData['arr_status'][] = array(
							'str_statusValue' => 'FAILED',
							'str_statusReason' => 'the transaction failed',
							'utstamp_statusModifiedTime' => time(),
							'arr_statusDetails' => array()							
						);
						$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_moduleReturnData);
						$this->update_fieldValue_inOrder($arr_order['id'], 'saferpay_currentStatus', 'FAILED');
						
						/*
						 * redirect in order to get rid of the "aborted" value for
						 * the saferpay_action parameter which would cause multiple
						 * abortions to be written to the order record if the user
						 * would reload the page with the same url.
						 */
						$this->redirect(preg_replace('/saferpay_action=fail/', 'saferpay_action=fail_handled', Environment::get('request')));
						break;
					
					case 'success':
						$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['paymentSuccessAfterFinishedOrder'];
						break;

					
					case 'notification':
						$arr_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($arr_order['id']);
						
						try {
							$arr_assertResponse = $this->saferpay_getAssertResponse($arr_moduleReturnData['str_saferpayToken']);
						} catch (\Exception $e) {
							$this->logPaymentError(__METHOD__, $e->getMessage());
							$arr_assertResponse = null;
						}
						
						$arr_moduleReturnData['arr_status'][] = array(
							'str_statusValue' => $arr_assertResponse !== null ? strtoupper($arr_assertResponse['Transaction']['Status']) : 'NOTIFICATION ERROR',
							'str_statusReason' => 'notification',
							'utstamp_statusModifiedTime' => time(),
							'arr_statusDetails' => $arr_assertResponse !== null ? $arr_assertResponse : array()
						);
						
						$arr_moduleReturnData['str_transactionId'] = $arr_assertResponse['Transaction']['Id'];
						$arr_moduleReturnData['arr_paymentMeans'] = $arr_assertResponse['PaymentMeans'];

						$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_moduleReturnData);
						$this->update_fieldValue_inOrder($arr_order['id'], 'saferpay_currentStatus', $arr_assertResponse !== null ? strtoupper($arr_assertResponse['Transaction']['Status']) : 'NOTIFICATION ERROR');
						
						/*
						 * Instantly capture authorized payments
						 */
						if ($this->arrCurrentSettings['saferpay_captureInstantly']) {
							if ($arr_assertResponse['Transaction']['Status'] === 'AUTHORIZED') {
								try {
									$arr_captureResponse = $this->saferpay_capture($arr_assertResponse['Transaction']['Id']);

									$arr_moduleReturnData['arr_status'][] = array(
										'str_statusValue' => 'CAPTURED',
										'str_statusReason' => 'capture',
										'utstamp_statusModifiedTime' => time(),
										'arr_statusDetails' => $arr_captureResponse
									);

									$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_moduleReturnData);
									$this->update_fieldValue_inOrder($arr_order['id'], 'saferpay_currentStatus', 'CAPTURED');
								} catch (\Exception $e) {
									$this->logPaymentError(__METHOD__, $e->getMessage());
									$arr_moduleReturnData['arr_status'][] = array(
										'str_statusValue' => 'ERROR',
										'str_statusReason' => 'capture failed',
										'utstamp_statusModifiedTime' => time(),
										'arr_statusDetails' => array()
									);

									$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_moduleReturnData);
									$this->update_fieldValue_inOrder($arr_order['id'], 'saferpay_currentStatus', 'ERROR');
								}
							}
						}
						
						die('NOTIFICATION SUCCESSFULLY HANDLED');
						break;
				}
			}
		}
		
		protected function saferpay_getAssertResponse($str_token) {
			if (!$str_token) {
				return null;
			}
			
			$arr_assertResponse = $this->saferpay_postRequest(
				'Payment/v1/PaymentPage/Assert',
				array(
					'Token' => $str_token
				)
			);
			
			return $arr_assertResponse;
		}
		
		protected function saferpay_capture($str_transactionId) {
			if (!$str_transactionId) {
				return null;
			}
			
			$arr_captureResponse = $this->saferpay_postRequest(
				'Payment/v1/Transaction/Capture',
				array(
					'TransactionReference' => array(
						'TransactionId' => $str_transactionId
					)
				)
			);
			
			return $arr_captureResponse;
		}
		
		protected function saferpay_cancelPayment($str_transactionId) {
			if (!$str_transactionId) {
				return null;
			}
			
			$arr_cancelResponse = $this->saferpay_postRequest(
				'Payment/v1/Transaction/Cancel',
				array(
					'TransactionReference' => array(
						'TransactionId' => $str_transactionId
					)
				)
			);
			
			return $arr_cancelResponse;
		}
		
		protected function saferpay_initializePayment($arr_order, $afterCheckoutUrl, $oix) {
			$this->arrCurrentSettings['saferpay_username'];
			
			if (
					!is_array($arr_order) || !count($arr_order)
				||	!$afterCheckoutUrl
				||	!$oix
			) {
				throw new \Exception(__METHOD__.' => $arr_order, $afterCheckoutUrl or $oix is not set correctly.');
			}
			
			$arr_initializationParameters = array(
				'Payment' => array(
					'Amount' => array(
						/*
						 * We have to send the invoiced amount in the smallest
						 * unit and since Merconis only supports decimal currencies
						 * (because there aren't any relevant non-decimal currencies
						 * in the entire world), we multiply the amount by 100.
						 */
						'Value' => ls_mul($arr_order['invoicedAmount'], 100),
						'CurrencyCode' => $arr_order['currency']
					),
					'OrderId' => $arr_order['orderNr'],
					'Description' => sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['saferpay']['paymentDescription'], $arr_order['orderNr'], Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $arr_order['orderDateUnixTimestamp']), ls_shop_generalHelper::outputPrice($arr_order['invoicedAmount'])),
					'PayerNote' => $arr_order['orderNr']
				),
				
				'Payer' => array(
					'LanguageCode' => $arr_order['customerLanguage']
				),

				'PaymentMethods' => StringUtil::deserialize($this->arrCurrentSettings['saferpay_paymentMethods'], true),

				'Wallets' => StringUtil::deserialize($this->arrCurrentSettings['saferpay_wallets'], true),

				'ReturnUrls' => array(
					'Success' => $afterCheckoutUrl.'&saferpay_action=success',
					'Fail' => $afterCheckoutUrl.'&saferpay_action=fail',
					'Abort' => $afterCheckoutUrl.'&saferpay_action=abort'
				),
				
				'Notification' => array(
					'MerchantEmail' => $this->arrCurrentSettings['saferpay_merchantEmail'],
					
					/*
					 * FIXME: Currently personal data field names are not entered
					 * in a payment method's record, so in this case this would
					 * always fallback to 'email'. If there's no reason to ask
					 * for other personal data field names, we leave it at that,
					 * because 'email' is a fixed field name anyway.
					 */
					'PayerEmail' => $this->saferpay_getPersonalDataFieldValue($this->arrCurrentSettings['saferpay_fieldNameEmail'] ? $this->arrCurrentSettings['saferpay_fieldNameEmail'] : 'email'),
					
					'NotifyUrl' => $afterCheckoutUrl.'&saferpay_action=notification'
				)
			);
			
			/*
			 * Remove the MerchantEmail element if it does not hold a value because
			 * evidently, this breaks the initialization.
			 */
			if (!$arr_initializationParameters['Notification']['MerchantEmail']) {
				unset($arr_initializationParameters['Notification']['MerchantEmail']);
			}
			
			$arr_response = $this->saferpay_postRequest(
				'Payment/v1/PaymentPage/Initialize',
				$arr_initializationParameters
			);
			
			return $arr_response;
		}

		/*
		 * $arr_parametersForJson is a multidimensional Array, that assembles the JSON structure
		 * $str_resource is the resource path added to the test version or live
		 * version of the saferpay api url (e. g. 'Payment/v1/Transaction/Initialize')
		 */
		protected function saferpay_postRequest($str_resource, $arr_parametersForJson){
			$url = $this->arr_saferpay_apiUrls[$this->arrCurrentSettings['saferpay_liveMode'] ? 'live' : 'test'].'/'.$str_resource;
			
			/*
			 * ===>
			 * Adding the parameters that are the same for all post requestsf
			 */
			
			/*
			 * Add the request header to the parameters
			 */
			$arr_parametersForJson['RequestHeader'] = array(
				'SpecVersion' => $this->str_saferpay_apiSpecVersion,
				'CustomerId' => $this->arrCurrentSettings['saferpay_customerId'],
				'RequestId' => md5(microtime().rand(0, 1000)), // FIXME: Will work but is not the intended use because we don't consider retries
				'RetryIndicator' => 0, // FIXME: Will work but is not the intended use because we don't consider retries
				'ClientInfo' => array(
					'ShopInfo' => 'MERCONIS '.InstalledVersions::getPrettyVersion('leadingsystems/contao-merconis')
				)
			);
			
			/*
			 * Add the terminal id to the parameters
			 */
			$arr_parametersForJson['TerminalId'] = $this->arrCurrentSettings['saferpay_terminalId'];
			
			/*
			 * <===
			 */
			
			
			$curl = curl_init($url);
			
			//Set Options for CURL
			curl_setopt($curl, CURLOPT_HEADER, false);
			
			//Return Response to Application
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			
			//Set Content-Headers to JSON
			curl_setopt($curl, CURLOPT_HTTPHEADER,array("Content-type: application/json"));
			
			//Execute call via http-POST
			curl_setopt($curl, CURLOPT_POST, true);
			
			//Set POST-Body
			//convert DATA-Array into a JSON-Object
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($arr_parametersForJson));
			
			//WARNING!!!!!
			//This option should NOT be "false"
			//Otherwise the connection is not secured
			//You can turn it of if you're working on the test-system with no vital data
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			
			//HTTP-Basic Authentication for the Saferpay JSON-API
			curl_setopt($curl, CURLOPT_USERPWD, $this->arrCurrentSettings['saferpay_username'] . ":" . $this->arrCurrentSettings['saferpay_password']);
			
			//CURL-Execute & catch response
			$jsonResponse = curl_exec($curl);
			
			//Get HTTP-Status
			//Abort if Status != 200 
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			if ($status != 200) {
				ob_start();
				var_dump($arr_parametersForJson);
				$str_jsonDump = ob_get_clean();
				
				throw new \Exception('Error: call to URL '.$url.' failed with status '.$status.', response '.$jsonResponse.', curl_error '.curl_error($curl).', curl_errno '.curl_errno($curl).'HTTP-Status: '.$status.'<||||> DUMP: URL: '.$url.' <|||> JSON: '.$str_jsonDump);
			}
			//IMPORTANT!!!
			//Close connection!
			curl_close($curl);
			
			//Convert response into an Array
			$response = json_decode($jsonResponse, true);
			
			return $response;
		}

		protected function saferpay_getPersonalDataFieldValue($str_fieldName) {
			$arr_checkoutFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'];
			$str_value =		$arr_checkoutFormFields[$str_fieldName]['value']
							?	$arr_checkoutFormFields[$str_fieldName]['value']
							:	'';
			
			return $str_value;
		}
	}
?>
