<?php

namespace Merconis\Core;

	use Contao\StringUtil;

class ls_shop_paymentModule_vrpay extends ls_shop_paymentModule_standard {
		public $arrCurrentSettings = array();
		
		protected $arr_vrpay_apiUrls = array(
			'test' => 'https://test.vr-pay-ecommerce.de',
			'live' => 'https://vr-pay-ecommerce.de'
		);

		protected $arr_vrpay_backofficeUrls = array(
			'test' => 'https://test.vr-pay-ecommerce.de/bip/login.prc',
			'live' => 'https://vr-pay-ecommerce.de/bip/entitylogin.link'
		);

		protected $str_vrpay_apiVersion = 'v1';

		protected $arr_vrpay_statusCodePatterns = array(
			'/^(000\.000\.|000\.100\.1|000\.[36])/' => array(
				'groupName' => 'successfully_processed',
				'groupStatus' => 'SUCCESS'
			),
			'/^(000\.400\.0|000\.400\.100)/' => array(
				'groupName' => 'successfully_processed--review_manually',
				'groupStatus' => 'REVIEW MANUALLY'
			),
			'/^(000\.200)/' => array(
				'groupName' => 'pending',
				'groupStatus' => 'PENDING'
			),
			'/^(800\.400\.5|100\.400\.500)/' => array(
				'groupName' => 'waiting',
				'groupStatus' => 'WAITING'
			),
			'/^(000\.400\.[1][0-9][1-9]|000\.400\.2)/' => array(
				'groupName' => 'rejected--3dsecure_or_intercard',
				'groupStatus' => 'REJECTED'
			),
			'/^(800\.[17]00|800\.800\.[123])/' => array(
				'groupName' => 'rejected--external_bank',
				'groupStatus' => 'REJECTED'
			),
			'/^(900\.[1234]00)/' => array(
				'groupName' => 'rejected--communication_error',
				'groupStatus' => 'REJECTED'
			),
			'/^(800\.5|999\.|600\.1|800\.800\.8)/' => array(
				'groupName' => 'rejected--system_error',
				'groupStatus' => 'REJECTED'
			),
			'/^(100\.39[765])/' => array(
				'groupName' => 'rejected--asynchronous_workflow',
				'groupStatus' => 'REJECTED'
			),
			'/^(100\.400|100\.38|100\.370\.100|100\.370\.11)/' => array(
				'groupName' => 'rejected--external_risk_systems',
				'groupStatus' => 'REJECTED'
			),
			'/^(800\.400\.1)/' => array(
				'groupName' => 'rejected--address_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(800\.400\.2|100\.380\.4|100\.390)/' => array(
				'groupName' => 'rejected--3dsecure',
				'groupStatus' => 'REJECTED'
			),
			'/^(100\.100\.701|800\.[32])/' => array(
				'groupName' => 'rejected--blacklist_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(800\.1[123456]0)/' => array(
				'groupName' => 'rejected--risk_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(600\.[23]|500\.[12]|800\.121)/' => array(
				'groupName' => 'rejected--configuration_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(100\.[13]50)/' => array(
				'groupName' => 'rejected--registration_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(100\.250|100\.360)/' => array(
				'groupName' => 'rejected--job_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(700\.[1345][05]0)/' => array(
				'groupName' => 'rejected--reference_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(200\.[123]|100\.[53][07]|800\.900|100\.[69]00\.500)/' => array(
				'groupName' => 'rejected--format_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(100\.800)/' => array(
				'groupName' => 'rejected--address_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(100\.[97]00)/' => array(
				'groupName' => 'rejected--contact_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(100\.100|100.2[01])/' => array(
				'groupName' => 'rejected--account_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(100\.55)/' => array(
				'groupName' => 'rejected--amount_validation',
				'groupStatus' => 'REJECTED'
			),
			'/^(100\.380\.[23]|100\.380\.101)/' => array(
				'groupName' => 'rejected--risk_management',
				'groupStatus' => 'REJECTED'
			),
			'/^(000\.100\.2)/' => array(
				'groupName' => 'chargeback_related',
				'groupStatus' => 'REJECTED'
			)
		);

		public function initialize() {
		}

		public function checkoutFinishAllowed() {
			/*
			 * With vrpay, the checkout is always allowed, because we want the order to be finished
			 * bindingly before we perform the payment.
			 */
			return true;
		}
		
		public function modifyConfirmOrderForm($str_form = '') {
			$outputToAdd = '<div class="confirmCheckoutMessageVrpay'.($this->arrCurrentSettings['cssClass'] ? ' '.$this->arrCurrentSettings['cssClass'] : '').'">'.$GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['vrpay']['confirmCheckoutMessage'].'</div>';
			$str_form = $str_form.$outputToAdd;
			return $str_form;
		}
		
		public function afterCheckoutFinish($int_orderIdInDb = 0, $arr_order = array(), $str_afterCheckoutUrl = '', $oix = '') {
			/*
			 * ----------
			 * Generating the return url/after checkout url that can be used for vrpay. vrpay calls it "shopperResultUrl"
			 */
			// adding the oih/oix as a parameter to the afterCheckoutUrl
			$str_afterCheckoutUrl = $str_afterCheckoutUrl.(preg_match('/\?/', $str_afterCheckoutUrl) ? '&' : '?').'oix='.$oix;

			// make an absolute URL
			if (!preg_match('@^https?://@i', $str_afterCheckoutUrl)) {
				$str_afterCheckoutUrl = \Environment::get('base') . $str_afterCheckoutUrl;
			}
			/*
			 * ----------
			 */

			try {
				$arr_initializationResponse = $this->vrpay_prepareCheckout($arr_order);
			} catch (\Exception $e) {
				$this->update_paymentMethod_moduleReturnData_inOrder($int_orderIdInDb, array(
					'str_transactionId' => '',
					'str_afterCheckoutUrl' => $str_afterCheckoutUrl,
					'str_paymentInstrumentAndBrand' => $this->arrCurrentSettings['vrpay_paymentInstrument'],
					'arr_status' => array(
						0 => array(
							'str_statusValue' => 'ERROR',
							'str_statusReason' => 'An exception occurred when initializing the vrpay payment',
							'str_statusReasonCode' => '',
							'utstamp_statusModifiedTime' => time(),
							'arr_statusDetails' => array()
						)
					)
				));
				$this->update_fieldValue_inOrder($int_orderIdInDb, 'vrpay_currentStatus', 'ERROR');
				$this->logPaymentError(__METHOD__, $e->getMessage());
				return;
			}

			$this->update_paymentMethod_moduleReturnData_inOrder($int_orderIdInDb, array(
				'str_transactionId' => $arr_initializationResponse['id'],
				'str_afterCheckoutUrl' => $str_afterCheckoutUrl,
				'str_paymentInstrumentAndBrand' => $this->arrCurrentSettings['vrpay_paymentInstrument'],
				'arr_status' => array(
					0 => array(
						'str_statusValue' => 'STARTED',
						'str_statusReason' => $arr_initializationResponse['result']['description'],
						'str_statusReasonCode' => $arr_initializationResponse['result']['code'],
						'utstamp_statusModifiedTime' => time(),
						'arr_statusDetails' => array()
					)
				)
			));
			$this->update_fieldValue_inOrder($int_orderIdInDb, 'vrpay_currentStatus', 'STARTED');
		}
		
		public function check_usePaymentAfterCheckoutPage($int_orderIdInDb = 0, $arr_order = array()) {
			return true;
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

			ob_start();
			?>
			<div class="paymentDetails vrpay">
				<h2>
					<?php
					$str_backofficeUrl = $this->arr_vrpay_backofficeUrls[$this->arrCurrentSettings['vrpay_liveMode'] ? 'live' : 'test'];
					?>
					<a href="<?php echo $str_backofficeUrl; ?>" target="_blank">
						<img src="bundles/leadingsystemsmerconis/images/payment/vrpay_logo_medium.png" alt="VR Pay">
					</a>
					<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['vrpay']['headlineBackendDetailsInfo']; ?>
				</h2>
				<div class="content">
					<div class="transactionID">
						<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['vrpay']['transactionID']; ?>: </span>
						<span class="value"><?php echo isset($arr_paymentMethod_moduleReturnData['str_transactionId']) && $arr_paymentMethod_moduleReturnData['str_transactionId'] ? $arr_paymentMethod_moduleReturnData['str_transactionId'] : '---'; ?></span>
					</div>
					<div class="paymentBrand">
						<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['vrpay']['paymentBrand']; ?>: </span>
						<span class="value"><?php echo isset($arr_statusAllEntries[0]['arr_statusDetails']['paymentBrand']) && $arr_statusAllEntries[0]['arr_statusDetails']['paymentBrand'] ? $arr_statusAllEntries[0]['arr_statusDetails']['paymentBrand'] : '---'; ?></span>
					</div>
					<div class="status">
						<?php
						foreach ($arr_statusAllEntries as $arr_statusEntry) {
							?>
							<div class="value">
								<div class="statusModifiedTime"><?php echo date($GLOBALS['TL_CONFIG']['dateFormat'].', H:i:s', $arr_statusEntry['utstamp_statusModifiedTime']); ?></div>
								<div class="statusValue"><?php echo strtoupper($arr_statusEntry['str_statusValue']); ?></div>
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
					<?php print_r($arr_paymentMethod_moduleReturnData); ?>
				</pre>
			<?php } ?>

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
			<div class="paymentStatusInOverview vrpay <?php echo strtolower(preg_replace('/\s+/', '-', $arr_currentStatus['str_statusValue'])); ?>">
				<img src="bundles/leadingsystemsmerconis/images/payment/vrpay_logo_small.png" alt="VR Pay">
				<span class="status">
					<?php echo strtoupper($arr_currentStatus['str_statusValue']); ?>
				</span>
				<div class="paymentBrand">
					<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['vrpay']['paymentBrand']; ?>: </span>
					<span class="value"><?php echo isset($arr_statusAllEntries[0]['arr_statusDetails']['paymentBrand']) && $arr_statusAllEntries[0]['arr_statusDetails']['paymentBrand'] ? $arr_statusAllEntries[0]['arr_statusDetails']['paymentBrand'] : '---'; ?></span>
				</div>
			</div>
			<?php
			$str_outputValue = ob_get_clean();
			return $str_outputValue;
		}

		/*
		 * This payment function is called on the after checkout page if an oix is given there.
		 */
		public function onAfterCheckoutPage($arr_order = array()) {
			$arr_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($arr_order['id']);

			switch (\Input::get('vrpay_action')) {
				case 'abort':
					$arr_moduleReturnData['arr_status'][] = array(
						'str_statusValue' => 'ABORTED',
						'str_statusCode' => '',
						'str_statusReason' => 'customer cancelled vr pay payment on payment after checkout page',
						'utstamp_statusModifiedTime' => time(),
						'arr_statusDetails' => array()
					);
					$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_moduleReturnData);
					$this->update_fieldValue_inOrder($arr_order['id'], 'vrpay_currentStatus', 'ABORTED');

					$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['vrpay']['paymentErrorAfterFinishedOrder'];

					/*
					 * redirect in order to get rid of the "aborted" value for
					 * the vrpay_action parameter which would cause multiple
					 * abortions to be written to the order record if the user
					 * would reload the page with the same url.
					 */
					$this->redirect(preg_replace('/vrpay_action=abort/', 'vrpay_action=abort_handled', \Environment::get('request')));
					break;

				default:
					/*
					 * ->
					 * Get the transaction status
					 */
					if (\Input::get('resourcePath')) {
						$arr_paymentStatusResponseData = $this->vrpay_doRequest(
							$this->arr_vrpay_apiUrls[$this->arrCurrentSettings['vrpay_liveMode'] ? 'live' : 'test'].\Input::get('resourcePath'),
							array(),
							'GET'
						);

						$arr_resultCodeGroupInfo = $this->vrpay_getResultCodeGroup($arr_paymentStatusResponseData['result']['code']);

						$arr_moduleReturnData['str_transactionId'] = $arr_paymentStatusResponseData['id'];
						$arr_moduleReturnData['str_paymentInstrumentAndBrand'] = $arr_paymentStatusResponseData['paymentBrand'];
						$arr_moduleReturnData['arr_status'][] = array(
							'str_statusValue' => $arr_resultCodeGroupInfo['groupStatus'],
							'str_statusCode' => $arr_paymentStatusResponseData['result']['code'],
							'str_statusReason' => $arr_paymentStatusResponseData['result']['description'],
							'utstamp_statusModifiedTime' => time(),
							'arr_statusDetails' => $arr_paymentStatusResponseData
						);
						$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_moduleReturnData);
						$this->update_fieldValue_inOrder($arr_order['id'], 'vrpay_currentStatus', $arr_resultCodeGroupInfo['groupStatus']);

						switch ($arr_resultCodeGroupInfo['groupStatus']) {
							case 'SUCCESS':
								$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['vrpay']['paymentSuccessAfterFinishedOrder'];
								break;

							default:
								$_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['vrpay']['paymentErrorAfterFinishedOrder'];
								break;
						}

						/*
						 * redirect in order to get rid of the "resourcePath" parameter
						 * which would cause multiple status entries to be written to
						 * the order record if the user would reload the page with the same url.
						 */
						$this->redirect(preg_replace('/&{0,1}resourcePath=[^&]*/', '', \Environment::get('request')));
					}
					/*
					 * <-
					 */
					break;
			}

		}

		public function onPaymentAfterCheckoutPage($arr_order = array()) {
			/** @var \PageModel $objPage */
			global $objPage;
			$arr_paymentMethod_moduleReturnData = StringUtil::deserialize($arr_order['paymentMethod_moduleReturnData']);

			switch($this->arrCurrentSettings['vrpay_paymentInstrument']) {
				case 'creditcard':
					$arr_paymentBrands = StringUtil::deserialize($this->arrCurrentSettings['vrpay_creditCardBrands']);
					break;

				case 'giropay':
					$arr_paymentBrands = array('GIROPAY');
					break;

				case 'paydirekt':
					$arr_paymentBrands = array('PAYDIREKT');
					break;

				case 'directdebit_sepa':
					$arr_paymentBrands = array('DIRECTDEBIT_SEPA');
					break;

				case 'sofortueberweisung':
					$arr_paymentBrands = array('SOFORTUEBERWEISUNG');
					break;

				case 'paypal':
					$arr_paymentBrands = array('PAYPAL');
					break;

				case 'easycredit_ratenkauf':
					$arr_paymentBrands = array('RATENKAUF');
					break;

				case 'klarna_invoice':
					$arr_paymentBrands = array('KLARNA_INVOICE');
					break;
			}

			$str_cancelUrl = $arr_paymentMethod_moduleReturnData['str_afterCheckoutUrl'].(strpos($arr_paymentMethod_moduleReturnData['str_afterCheckoutUrl'], '?') === false ? '?' : '&').'vrpay_action=abort';

			$arr_return = array(
				'str_output' => ''
			);

			$GLOBALS['TL_HEAD'][] = '
<script>
    var wpwlOptions = {
        locale: "'.$objPage->language.'"
    }
</script>';

			$GLOBALS['TL_HEAD'][] = '<script src="'.$this->vrpay_getApiUrl().'paymentWidgets.js?checkoutId='.$arr_paymentMethod_moduleReturnData['str_transactionId'].'"></script>';

			ob_start();
			?>
			<form action="<?php echo $arr_paymentMethod_moduleReturnData['str_afterCheckoutUrl']; ?>" class="paymentWidgets" data-brands="<?php echo implode(' ', $arr_paymentBrands); ?>"></form>
			<p><a href="<?php echo $str_cancelUrl; ?>"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['vrpay']['cancelPayment']; ?></a></p>
			<?php
			$arr_return['str_output'] = ob_get_clean();

			return $arr_return;
		}

		protected function vrpay_prepareCheckout($arr_order) {
			$arr_requestParameters = array(
				'amount' => number_format(floor($arr_order['invoicedAmount']*100)/100, '2', '.', ''), // make sure that we have only two decimals
				'currency' => $arr_order['currency'],
				'merchantTransactionId' => $arr_order['orderNr'],
				'descriptor' => $arr_order['orderNr']
			);

			if (!$this->arrCurrentSettings['vrpay_liveMode'] && $this->arrCurrentSettings['vrpay_testMode']) {
				$arr_requestParameters['testMode'] = $this->arrCurrentSettings['vrpay_testMode'];
			}

			switch($this->arrCurrentSettings['vrpay_paymentInstrument']) {
				case 'creditcard':
					$arr_requestParameters['paymentType'] = 'DB';
					break;

				case 'giropay':
					$arr_requestParameters['paymentType'] = 'DB';
					break;

				case 'paydirekt':
					$arr_requestParameters['paymentType'] = 'DB';

					$arr_requestParameters['shipping.street1'] = $this->vrpay_getShippingFieldValue($this->arrCurrentSettings['vrpay_fieldName_street1']);
					$arr_requestParameters['shipping.city'] = $this->vrpay_getShippingFieldValue($this->arrCurrentSettings['vrpay_fieldName_city']);
					$arr_requestParameters['shipping.postcode'] = $this->vrpay_getShippingFieldValue($this->arrCurrentSettings['vrpay_fieldName_postcode']);
					$arr_requestParameters['shipping.country'] = strtoupper($this->vrpay_getShippingFieldValue($this->arrCurrentSettings['vrpay_fieldName_country']));
					$arr_requestParameters['customer.givenName'] = $this->vrpay_getShippingFieldValue($this->arrCurrentSettings['vrpay_fieldName_givenName']);
					$arr_requestParameters['customer.surname'] = $this->vrpay_getShippingFieldValue($this->arrCurrentSettings['vrpay_fieldName_surname']);
					break;

				case 'directdebit_sepa':
					$arr_requestParameters['paymentType'] = 'DB';
					break;

				case 'sofortueberweisung':
					$arr_requestParameters['paymentType'] = 'DB';
					break;

				case 'paypal':
					$arr_requestParameters['paymentType'] = 'DB';
					break;

				case 'easycredit_ratenkauf':
					$arr_requestParameters['paymentType'] = 'PA';

					$arr_requestParameters['customer.email'] = '';
					$arr_requestParameters['customer.sex'] = 'M';
					$arr_requestParameters['customer.phone'] = '';
					$arr_requestParameters['customer.surname'] = '';
					$arr_requestParameters['customer.givenName'] = '';
					$arr_requestParameters['customer.birthDate'] = '';
					$arr_requestParameters['billing.city'] = '';
					$arr_requestParameters['billing.country'] = '';
					$arr_requestParameters['billing.street1'] = '';
					$arr_requestParameters['billing.postcode'] = '';
					$arr_requestParameters['shipping.city'] = '';
					$arr_requestParameters['shipping.country'] = '';
					$arr_requestParameters['shipping.street1'] = '';
					$arr_requestParameters['shipping.postcode'] = '';
					$arr_requestParameters['cart.items[0].name'] = '';
					$arr_requestParameters['cart.items[0].type'] = '';
					$arr_requestParameters['cart.items[0].price'] = '';
					$arr_requestParameters['cart.items[0].currency'] = '';
					$arr_requestParameters['cart.items[0].quantity'] = '';
					$arr_requestParameters['cart.items[0].merchantItemId'] = '';
					$arr_requestParameters['customParameters']['RISK_BESTELLUNGERFOLGTUEBERLOGIN'] = '';
					$arr_requestParameters['customParameters']['RISK_KUNDESEIT'] = '';
					$arr_requestParameters['customParameters']['RISK_NEGATIVEZAHLUNGSINFORMATION'] = '';
					$arr_requestParameters['customParameters'][''] = '';
					$arr_requestParameters['customParameters'][''] = '';
					$arr_requestParameters['customParameters'][''] = '';
					break;

				case 'klarna_invoice':
					$arr_requestParameters['paymentType'] = 'PA';
					break;
			}

			$arr_responseData = $this->vrpay_doRequest(
				'checkouts',
				$arr_requestParameters
			);

			return $arr_responseData;
		}

		protected function vrpay_doRequest($str_resource, $arr_requestData = array(), $str_method = 'POST') {
			if (strpos($str_resource, 'http') !== false) {
				/*
				 * If $str_resource holds a complete url, we use it directly as the apiResourceUrl
				 */
				$str_apiResourceUrl = $str_resource;
			} else {
				$str_apiResourceUrl = $this->vrpay_getApiUrl() . $str_resource;
			}

		    	//06.04.2020, Anpassung wg. Authentifizierungs-Erweiterung (token statt Username und PWD)
		    	//07.04.2020, und wg. angekündigter Änderung im September gleich von 'authentication.entityId' nach 'entityId' geändert
		    	if ($this->arrCurrentSettings['vrpay_token']) {
				$arr_authData = array(
				    'entityId' => $this->arrCurrentSettings['vrpay_entityId']
				);
			} else {
				$arr_authData = array(
				    'authentication.userId' => $this->arrCurrentSettings['vrpay_userId'],
				    'authentication.password' => $this->arrCurrentSettings['vrpay_password'],
				    'entityId' => $this->arrCurrentSettings['vrpay_entityId']
				);
			}

			$arr_data = array_merge($arr_authData, $arr_requestData);

			$str_dataQuery = http_build_query($arr_data, '', '&');

			$handler_curl = curl_init();

			if ($str_method !== 'POST') {
				$str_apiResourceUrl = $str_apiResourceUrl.(strpos($str_apiResourceUrl, '?') === false ? '?' : '&').$str_dataQuery;
			}

			//06.04.2020, VR-Pay benötigt den Token im Header. Und muss html_entity_decode´d werden, da == in &#61; umgewandelt werden.
            		if ($this->arrCurrentSettings['vrpay_token']) {
                		$arr_header = array('Authorization:Bearer ' . html_entity_decode($this->arrCurrentSettings['vrpay_token']));
                		curl_setopt($handler_curl, CURLOPT_HTTPHEADER, $arr_header);
            		}

			curl_setopt($handler_curl, CURLOPT_URL, $str_apiResourceUrl);

			if ($str_method === 'POST') {
				curl_setopt($handler_curl, CURLOPT_POST, 1);
				curl_setopt($handler_curl, CURLOPT_POSTFIELDS, $str_dataQuery);
			}
			curl_setopt($handler_curl, CURLOPT_SSL_VERIFYPEER, $this->arrCurrentSettings['vrpay_liveMode'] ? true : false);
			curl_setopt($handler_curl, CURLOPT_RETURNTRANSFER, true);
			$arr_responseData = curl_exec($handler_curl);

			if(curl_errno($handler_curl)) {
				return curl_error($handler_curl);
			}

			curl_close($handler_curl);

			return json_decode($arr_responseData, true);
		}

		protected function vrpay_getApiUrl() {
			return $this->arr_vrpay_apiUrls[$this->arrCurrentSettings['vrpay_liveMode'] ? 'live' : 'test'] . '/' . $this->str_vrpay_apiVersion . '/';
		}

		protected function vrpay_getResultCodeGroup($str_resultCode) {
			foreach ($this->arr_vrpay_statusCodePatterns as $str_pattern => $arr_groupInfo) {
				if (preg_match($str_pattern, $str_resultCode)) {
					return $arr_groupInfo;
				}
			}
		}

		protected function vrpay_getShippingFieldValue($str_fieldName) {
            $str_valueWildcardPattern = '/(?:#|&#35;){2}value::(.*)(?:#|&#35;){2}/';
            if (preg_match($str_valueWildcardPattern, $str_fieldName, $arr_matches)) {
                $str_fieldName = preg_replace($str_valueWildcardPattern, $this->vrpay_getShippingFieldValue($arr_matches[1]), $str_fieldName);
            }

			$arrCheckoutFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'];
            $str_value = $arrCheckoutFormFields[$str_fieldName.(isset($arrCheckoutFormFields['useDeviantShippingAddress']['value']) && $arrCheckoutFormFields['useDeviantShippingAddress']['value'] ? '_alternative' : '')]['value'];

			if (!$str_value) {
				$str_value = null;
			}

			return $str_value;
		}

	}
?>
