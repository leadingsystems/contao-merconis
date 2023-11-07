<?php

namespace Merconis\Core;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;
use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;

	class ls_shop_paymentModule_payone extends ls_shop_paymentModule_standard {
		public $arrCurrentSettings = array();
		
		protected $transactionID = '';
		protected $str_integratorid = '2056000';
		
		public function initialize() {
		}

		public function checkoutFinishAllowed() {
			/*
			 * With payone, the checkout is always allowed, because we want the order to be finished
			 * bindlingly before we redirect the user to payone.
			 */
			return true;
		}
		
		public function modifyConfirmOrderForm($str_form = '') {
			$outputToAdd = '<div class="confirmCheckoutMessagePayone"><img src="bundles/leadingsystemsmerconis/images/payment/payone_logo_small.png" alt="PAYONE">'.$GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payone']['confirmCheckoutMessage'].'</div>';
			$str_form = $str_form.$outputToAdd;
			return $str_form;
		}
		
		/*
		 * Since we can't make payone call a callback url with the actual oix
		 * parameter but we can use a parameter called "param" and that can
		 * hold the oix, we simply have to read "param" and return it as oix.
		 */
		public function determineOix() {
			/*
			 * Make sure not to grant access to the order if we don't have the
			 * correct payone key given
			 */
			if (!Input::post('key') || Input::post('key') != md5($this->arrCurrentSettings['payone_key'])) {
				return '';
			}
			
			$str_oix = '';
			if (Input::post('param')) {
				$str_oix = Input::post('param');
			}
			return $str_oix;
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
			<div class="paymentDetails payone">
				<h2>
					<a href="https://www.payone.de/" target="_blank">
						<img src="bundles/leadingsystemsmerconis/images/payment/payone_logo_small.png" alt="PAYONE">
					</a>
					<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payone']['headlineBackendDetailsInfo']; ?>
				</h2>
				<div class="content">
					<div class="transactionID">
						<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payone']['transactionID']; ?>: </span>
						<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['txid'] ? $arr_currentStatus['arr_statusDetails']['txid'] : '---'; ?></span>
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
					<div class="details">
						<div class="detailItem">
							<span class="label">clearingtype: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['clearingtype']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">currency: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['currency']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">portalid: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['portalid']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">aid: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['aid']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">mode: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['mode']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">userid: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['userid']; ?></span>
						</div>
												
						<div class="detailItem">
							<span class="label">price: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['price']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">receivable: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['receivable']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">balance: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['balance']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">sequencenumber: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['sequencenumber']; ?></span>
						</div>
						<?php if ($arr_currentStatus['arr_statusDetails']['failedcause']) { ?>
							<div class="detailItem">
								<span class="label">failedcause: </span>
								<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['failedcause']; ?></span>
							</div>
						<?php } ?>
						<?php if ($arr_currentStatus['arr_statusDetails']['customerid']) { ?>
							<div class="detailItem">
								<span class="label">customerid: </span>
								<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['customerid']; ?></span>
							</div>
						<?php } ?>
						
						<div class="headline">
							<span>Personal data</span>
						</div>
						<div class="detailItem">
							<span class="label">firstname: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['firstname']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">lastname: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['lastname']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">company: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['company']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">street: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['street']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">zip: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['zip']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">city: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['city']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">country: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['country']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">email: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['email']; ?></span>
						</div>

						<div class="headline">
							<span>Shipping address</span>
						</div>
						<div class="detailItem">
							<span class="label">shipping_firstname: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['shipping_firstname']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">shipping_lastname: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['shipping_lastname']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">shipping_company: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['shipping_company']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">shipping_street: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['shipping_street']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">shipping_zip: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['shipping_zip']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">shipping_city: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['shipping_city']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label">shipping_country: </span>
							<span class="value"><?php echo $arr_currentStatus['arr_statusDetails']['shipping_country']; ?></span>
						</div>
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
			<div class="paymentStatusInOverview payone <?php echo strtolower(preg_replace('/\s+/', '-', $arr_currentStatus['str_statusValue'])); ?>">
				<img src="bundles/leadingsystemsmerconis/images/payment/payone_logo_small.png" alt="PAYONE">
				<span class="status">
					<?php echo strtoupper($arr_currentStatus['str_statusValue']); ?><?php echo $arr_currentStatus['str_statusValue'] == 'APPOINTED' && $arr_currentStatus['arr_statusDetails']['transaction_status'] && $arr_currentStatus['arr_statusDetails']['transaction_status'] != 'completed' ? ' ('.strtoupper($arr_currentStatus['arr_statusDetails']['transaction_status']).')' : ''; ?>
				</span>
			</div>
			<?php
			$str_outputValue = ob_get_clean();
			return $str_outputValue;
		}
		
		/*
		 * This payment function is called on the after checkout page if an oix is given there.
		 * Depending on the payone GET parameter (p1action) this function can handle notification
		 * calls or the call of the backurl (abort).
		 */
		public function onAfterCheckoutPage($arr_order = array()) {
			$str_p1action = Input::get('p1action') ? Input::get('p1action') : (Input::post('p1action') ? Input::post('p1action') : '');
			if ($str_p1action) {
				switch ($str_p1action) {
					case 'aborted':
						// write the error message to the special payment info and update the payment status in the order record
                        $session = \System::getContainer()->get('merconis.session')->getSession();
                        $arrLsShop =  $session->get('lsShop', []);
                        $arrLsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payone']['paymentErrorAfterFinishedOrder'];
                        $session->set('lsShop', $arrLsShop);

						$arr_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($arr_order['id']);
						$arr_moduleReturnData['arr_status'][] = array(
							'str_statusValue' => 'ABORTED',
							'str_statusReason' => 'Buyer aborted the transaction',
							'utstamp_statusModifiedTime' => time(),
							'arr_statusDetails' => array()							
						);
						$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_moduleReturnData);
						$this->update_fieldValue_inOrder($arr_order['id'], 'payone_currentStatus', 'ABORTED');
						
						/*
						 * redirect in order to get rid of the "aborted" value for the sue parameter which would cause
						 * multiple abortions to be written to the order record if the user would reload the page with the url
						 */
						$this->redirect(preg_replace('/p1action=aborted/', 'p1action=failed', Environment::get('request')));
						break;
					
					case 'success':
						// write the success message to the special payment info
                        $session = \System::getContainer()->get('merconis.session')->getSession();
                        $arrLsShop =  $session->get('lsShop', []);
                        $arrLsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payone']['paymentSuccessAfterFinishedOrder'];
                        $session->set('lsShop', $arrLsShop);
						break;
					
					case 'notification':
						$arr_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($arr_order['id']);
						$arr_moduleReturnData['arr_status'][] = array(
							'str_statusValue' => strtoupper(Input::post('txaction')),
							'str_statusReason' => 'status push from payone',
							'utstamp_statusModifiedTime' => time(),
							'arr_statusDetails' => array(
								'tstamp' => time(),
								'txaction' => Input::post('txaction'),
								'transaction_status' => Input::post('transaction_status'),
								'notify_version' => Input::post('notify_version'),
								'mode' => Input::post('mode'),
								'portalid' => Input::post('portalid'),
								'aid' => Input::post('aid'),
								'clearingtype' => Input::post('clearingtype'),
								'txtime' => Input::post('txtime'),
								'currency' => Input::post('currency'),
								'userid' => Input::post('userid'),
								'customerid' => Input::post('customerid'),
								'firstname' => Input::post('firstname'),
								'lastname' => Input::post('lastname'),
								'company' => Input::post('company'),
								'street' => Input::post('street'),
								'zip' => Input::post('zip'),
								'city' => Input::post('city'),
								'country' => Input::post('country'),
								'shipping_firstname' => Input::post('shipping_firstname'),
								'shipping_lastname' => Input::post('shipping_lastname'),
								'shipping_company' => Input::post('shipping_company'),
								'shipping_street' => Input::post('shipping_street'),
								'shipping_zip' => Input::post('shipping_zip'),
								'shipping_city' => Input::post('shipping_city'),
								'shipping_country' => Input::post('shipping_country'),
								'email' => Input::post('email'),
								'txid' => Input::post('txid'),
								'reference' => Input::post('reference'),
								'sequencenumber' => Input::post('sequencenumber'),
								'price' => Input::post('price'),
								'receivable' => Input::post('receivable'),
								'balance' => Input::post('balance'),
								'failedcause' => Input::post('failedcause')
							)
						);
						
						$this->update_paymentMethod_moduleReturnData_inOrder($arr_order['id'], $arr_moduleReturnData);
						$this->update_fieldValue_inOrder($arr_order['id'], 'payone_currentStatus', strtoupper(Input::post('txaction')));
						die('TSOK');
						break;
				}
			}
		}
		
		/*
		 * After the checkout the payone payment is processed.
		 */
		public function afterCheckoutFinish($int_orderIdInDb = 0, $arr_order = array(), $afterCheckoutUrl = '', $oix = '') {

			// Reset the special payment info
            $session = \System::getContainer()->get('merconis.session')->getSession();
            $arrLsShop =  $session->get('lsShop', []);
            $arrLsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = '';
            $session->set('lsShop', $arrLsShop);
			
			// if there are insufficient parameters the payment execution is aborted
			if (!$int_orderIdInDb || !is_array($arr_order) || !count($arr_order)) {
				// write an error message to the special payment info and log the error
                $session = \System::getContainer()->get('merconis.session')->getSession();
                $arrLsShop =  $session->get('lsShop', []);
                $arrLsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payone']['paymentErrorAfterFinishedOrder'];
                $session->set('lsShop', $arrLsShop);

				$this->logPaymentError(__METHOD__, 'insufficient parameters given');
				return;
			}

			/*
			 * ----------
			 * Generating the after checkout url that can be used for payone
			 */
			// adding the oih as a parameter to the afterCheckoutUrl
			$afterCheckoutUrl = $afterCheckoutUrl.(preg_match('/\?/', $afterCheckoutUrl) ? '&' : '?').'oix='.$oix;
			
			// make an absolute URL
			if (!preg_match('@^https?://@i', $afterCheckoutUrl)) {
				$afterCheckoutUrl = Environment::get('base') . $afterCheckoutUrl;
			}
			/*
			 * ----------
			 */			
			
			// get the payment url and redirect the customer
			try {
				$paymentUrl = $this->payone_createPaymentUrl($arr_order, $afterCheckoutUrl, $oix);
			} catch (\Exception $e) {
				$this->update_paymentMethod_moduleReturnData_inOrder($int_orderIdInDb, array(
					'arr_status' => array(
						0 => array(
							'str_statusValue' => 'ERROR',
							'str_statusReason' => 'An exception occured when creating the payone payment url',
							'utstamp_statusModifiedTime' => time(),
							'arr_statusDetails' => array()
						)
					)
				));
				$this->update_fieldValue_inOrder($int_orderIdInDb, 'payone_currentStatus', 'ERROR');
				$this->logPaymentError(__METHOD__, $e->getMessage());
				return;
			}
			
			$this->update_paymentMethod_moduleReturnData_inOrder($int_orderIdInDb, array(
				'arr_status' => array(
					0 => array(
						'str_statusValue' => 'STARTED',
						'str_statusReason' => 'redirecting to payone',
						'utstamp_statusModifiedTime' => time(),
						'arr_statusDetails' => array()
					)
				)
			));
			$this->update_fieldValue_inOrder($int_orderIdInDb, 'payone_currentStatus', 'STARTED');
			
			$this->redirect($paymentUrl);
			exit;
		}
		
		protected function payone_createPaymentUrl($arr_order, $afterCheckoutUrl, $oix) {
			if (
					!is_array($arr_order) || !count($arr_order)
				||	!$afterCheckoutUrl
				||	!$oix
			) {
				throw new \Exception(__METHOD__.' => $arr_order, $afterCheckoutUrl or $oix is not set correctly.');
			}
			
			$arr_payoneParams = array(
				'successurl' => $afterCheckoutUrl.'&p1action=success',
				'backurl' => $afterCheckoutUrl.'&p1action=aborted',
				'param' => $oix,
				'request' => 'authorization',
				'aid' => $this->arrCurrentSettings['payone_subaccountId'],
				'portalid' => $this->arrCurrentSettings['payone_portalId'],
				'api_version' => '3.9',
				'encoding' => 'UTF-8',
				'mode' => $this->arrCurrentSettings['payone_liveMode'] ? 'live' : 'test',
				'currency' => $arr_order['currency'],
				'reference' => $arr_order['orderNr'],
				'amount' => ls_mul($arr_order['invoicedAmount'], 100),
				'clearingtype' => $this->arrCurrentSettings['payone_clearingtype'] ? $this->arrCurrentSettings['payone_clearingtype'] : 'cc', // using credit card as default instead of throwing an exception if the clearingtype is not set. This should not happen anyway because it's a mandatory field when defining the payment module in the merconis backend.
				/*
				 * We don't use the autosubmit parameter because for the autosubmit
				 * to work we would need to collect clearingtype specific and
				 * onlinebanktranfertype specific customer data in merconis
				 * which we don't want to do.
				 *
				'autosubmit' => $this->arrCurrentSettings['payone_clearingtype'] && $this->arrCurrentSettings['payone_clearingtype'] !== 'cc' ? 'yes' : 'no'
				/* */
			);
			
			$arr_payoneParams = $this->payone_addProductsToPayoneParams($arr_payoneParams, $arr_order);
			$arr_payoneParams = $this->payone_addCouponsToPayoneParams($arr_payoneParams, $arr_order);
			$arr_payoneParams = $this->payone_addShippingFeeToPayoneParams($arr_payoneParams, $arr_order);
			$arr_payoneParams = $this->payone_addPaymentFeeToPayoneParams($arr_payoneParams, $arr_order);
			
			ksort($arr_payoneParams);
			
			$str_payoneHash = $this->payone_generateHash($arr_payoneParams, $this->arrCurrentSettings['payone_key']);
			
			$arr_payoneParams['firstname'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameFirstname']);
			$arr_payoneParams['lastname'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameLastname']);
			$arr_payoneParams['company'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameCompany']);
			$arr_payoneParams['street'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameStreet']);
			$arr_payoneParams['addressaddition'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameAddressaddition']);
			$arr_payoneParams['zip'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameZip']);
			$arr_payoneParams['city'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameCity']);
			$arr_payoneParams['country'] = strtoupper($this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameCountry']));
			$arr_payoneParams['email'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameEmail']);
			$arr_payoneParams['telephonenumber'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameTelephonenumber']);
			$arr_payoneParams['birthday'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameBirthday']);
			$arr_payoneParams['gender'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNameGender']);
			$arr_payoneParams['personalid'] = $this->payone_getPersonalDataFieldValue($this->arrCurrentSettings['payone_fieldNamePersonalid']);
			
			$arr_payoneParams['shipping_firstname'] = $this->payone_getShippingFieldValue($this->arrCurrentSettings['payone_fieldNameFirstname']);
			$arr_payoneParams['shipping_lastname'] = $this->payone_getShippingFieldValue($this->arrCurrentSettings['payone_fieldNameLastname']);
			$arr_payoneParams['shipping_company'] = $this->payone_getShippingFieldValue($this->arrCurrentSettings['payone_fieldNameCompany']);
			$arr_payoneParams['shipping_street'] = $this->payone_getShippingFieldValue($this->arrCurrentSettings['payone_fieldNameStreet']);
			$arr_payoneParams['shipping_zip'] = $this->payone_getShippingFieldValue($this->arrCurrentSettings['payone_fieldNameZip']);
			$arr_payoneParams['shipping_city'] = $this->payone_getShippingFieldValue($this->arrCurrentSettings['payone_fieldNameCity']);
			$arr_payoneParams['shipping_country'] = strtoupper($this->payone_getShippingFieldValue($this->arrCurrentSettings['payone_fieldNameCountry']));
			
			$arr_payoneParams['language'] = $arr_order['customerLanguage'];
			
			/*
			 * TEST ->
			 *
			$arr_payoneParams['onlinebanktransfertype'] = 'IDL';
			$arr_payoneParams['bankgrouptype'] = 'ING_BANK';
			$arr_payoneParams['iban'] = 'NL55INGB0000000000';
			$arr_payoneParams['bic'] = 'INGBNL2A';
			$arr_payoneParams['bankcountry'] = 'NL';
			/*
			 * <- TEST
			 */
			
			$str_payoneQueryString = $this->payone_generateQueryString($arr_payoneParams, $str_payoneHash);
			
			$str_redirectUrl = 'https://frontend.pay1.de/frontend/v2/'.$str_payoneQueryString;
			
			return $str_redirectUrl;
		}
		
		protected function payone_addProductsToPayoneParams($arr_payoneParams, $arr_order) {
			foreach($arr_order['items'] as $arr_item) {
				$arr_payoneParams['it'][count($arr_payoneParams['it']) + 1] = 'goods';
				$arr_payoneParams['id'][count($arr_payoneParams['id']) + 1] = $arr_item['artNr'];
				$arr_payoneParams['va'][count($arr_payoneParams['va']) + 1] = $arr_item['taxPercentage'];
				
				/*
				 * If the quantity is an integer, we can set it directly and we
				 * can then use the regular item price.
				 * 
				 * But if the quantity is a decimal value, we can not use it directly
				 * but instead have to set the quantity to 1 and use the cumulative
				 * position price as the item price and pass the information about
				 * the exact decimal quantity in the title
				 */
				if (intval($arr_item['quantity']) == $arr_item['quantity']) {
					$arr_payoneParams['no'][count($arr_payoneParams['no']) + 1] = intval($arr_item['quantity']);
					$arr_payoneParams['pr'][count($arr_payoneParams['pr']) + 1] = ls_mul($arr_item['price'], 100);
					$arr_payoneParams['de'][count($arr_payoneParams['de']) + 1] = $arr_item['extendedInfo']['_productTitle_customerLanguage'].($arr_item['isVariant'] ? ' ('.$arr_item['extendedInfo']['_title_customerLanguage'].')' : '');
				} else {
					$arr_payoneParams['no'][count($arr_payoneParams['no']) + 1] = 1;
					$arr_payoneParams['pr'][count($arr_payoneParams['pr']) + 1] = ls_mul($arr_item['priceCumulative'], 100);
					$arr_payoneParams['de'][count($arr_payoneParams['de']) + 1] = $arr_item['quantity'].' '.$arr_item['extendedInfo']['_quantityUnit_customerLanguage'].' '.$arr_item['productTitle'].($arr_item['variantTitle'] ? ' ('.$arr_item['variantTitle'].')' : '');
				}
			}
			return $arr_payoneParams;
		}
		
		protected function payone_addCouponsToPayoneParams($arr_payoneParams, $arr_order) {
			foreach($arr_order['couponsUsed'] as $arr_coupon) {
				if (count($arr_coupon['amountTaxedWith']) === 0) {
					/*
					 * If the coupon is not divided into separate parts for each
					 * tax rate, which would be the case if no tax is applied at all,
					 * we pass the coupons main amount an no tax rate to payone.
					 */
					$arr_payoneParams['it'][count($arr_payoneParams['it']) + 1] = 'voucher';
					$arr_payoneParams['id'][count($arr_payoneParams['id']) + 1] = $arr_coupon['artNr'];
					$arr_payoneParams['pr'][count($arr_payoneParams['pr']) + 1] = ls_mul($arr_coupon['amount'], 100);
					$arr_payoneParams['no'][count($arr_payoneParams['no']) + 1] = 1;
					$arr_payoneParams['de'][count($arr_payoneParams['de']) + 1] = sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payone']['coupon'], $arr_coupon['title']);
					$arr_payoneParams['va'][count($arr_payoneParams['va']) + 1] = 0;
				} else {
					foreach ($arr_coupon['amountTaxedWith'] as $arr_couponAmount) {
						if ($arr_couponAmount['amountTaxedHerewith'] == 0) {
							continue;
						}

						$arr_payoneParams['it'][count($arr_payoneParams['it']) + 1] = 'voucher';
						$arr_payoneParams['id'][count($arr_payoneParams['id']) + 1] = $arr_coupon['artNr'];
						$arr_payoneParams['pr'][count($arr_payoneParams['pr']) + 1] = ls_mul($arr_couponAmount['amountTaxedHerewith'], 100);
						$arr_payoneParams['no'][count($arr_payoneParams['no']) + 1] = 1;
						$arr_payoneParams['de'][count($arr_payoneParams['de']) + 1] = sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payone']['coupon'], $arr_coupon['title']);
						$arr_payoneParams['va'][count($arr_payoneParams['va']) + 1] = $arr_couponAmount['taxRate'];
					}
				}
			}
			return $arr_payoneParams;			
		}
		
		protected function payone_addShippingFeeToPayoneParams($arr_payoneParams, $arr_order) {
			foreach($arr_order['shippingMethod']['amountTaxedWith'] as $arr_shippingAmount) {
				if ($arr_shippingAmount['amountTaxedHerewith'] == 0) {
					continue;
				}
				
				$arr_payoneParams['it'][count($arr_payoneParams['it']) + 1] = 'shipment';
				$arr_payoneParams['id'][count($arr_payoneParams['id']) + 1] = 'shipping';
				$arr_payoneParams['pr'][count($arr_payoneParams['pr']) + 1] = ls_mul($arr_shippingAmount['amountTaxedHerewith'], 100);
				$arr_payoneParams['no'][count($arr_payoneParams['no']) + 1] = 1;
				$arr_payoneParams['de'][count($arr_payoneParams['de']) + 1] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payone']['shippingFee'];
				$arr_payoneParams['va'][count($arr_payoneParams['va']) + 1] = $arr_shippingAmount['taxRate'];
			}
			return $arr_payoneParams;			
		}
		
		protected function payone_addPaymentFeeToPayoneParams($arr_payoneParams, $arr_order) {
			foreach($arr_order['paymentMethod']['amountTaxedWith'] as $arr_paymentAmount) {
				if ($arr_paymentAmount['amountTaxedHerewith'] == 0) {
					continue;
				}
				
				$arr_payoneParams['it'][count($arr_payoneParams['it']) + 1] = 'handling';
				$arr_payoneParams['id'][count($arr_payoneParams['id']) + 1] = 'payment';
				$arr_payoneParams['pr'][count($arr_payoneParams['pr']) + 1] = ls_mul($arr_paymentAmount['amountTaxedHerewith'], 100);
				$arr_payoneParams['no'][count($arr_payoneParams['no']) + 1] = 1;
				$arr_payoneParams['de'][count($arr_payoneParams['de']) + 1] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payone']['paymentFee'];
				$arr_payoneParams['va'][count($arr_payoneParams['va']) + 1] = $arr_paymentAmount['taxRate'];
			}
			return $arr_payoneParams;			
		}
		
		protected function payone_generateHash($arr_payoneParams, $str_payoneKey) {
			$str_toHash = '';
			$str_hash = '';
			
			if (!is_array($arr_payoneParams) || !$str_payoneKey) {
				return $str_hash;
			}
			
			ksort($arr_payoneParams);
			
			foreach ($arr_payoneParams as $str_key => $var_value) {
				if (is_array($var_value)) {
					$arr_payoneParams[$str_key] = implode('', $var_value);
				}
			}
			
			$str_toHash = implode('', $arr_payoneParams);
			
			$str_hash = hash_hmac("sha384", $str_toHash, $str_payoneKey);
			
			return $str_hash;
		}
		
		protected function payone_generateQueryString($arr_payoneParams, $str_hash) {
			$str_query = '';
			if (!is_array($arr_payoneParams) || !$str_hash) {
				return $str_query;
			}
			
			/*
			 * Sorting is irrelevant here because it makes no difference in the
			 * query string. We sort anyway because it might make debugging easier
			 * if the parameters appear in a defined order.
			 */
			ksort($arr_payoneParams);
			
			foreach ($arr_payoneParams as $str_key => $var_value) {
				if (!is_array($var_value)) {
					$str_query .= ($str_query ? '&' : '?').$str_key.'='.urlencode($var_value);
				} else {
					foreach ($var_value as $str_key2 => $var_value2) {
						$str_query .= ($str_query ? '&' : '?').$str_key.'['.$str_key2.']='.urlencode($var_value2);
					}
				}
			}
			
			return $str_query.'&hash='.$str_hash.'&integratorid='.$this->str_integratorid;
		}

		protected function payone_getPersonalDataFieldValue($str_fieldName) {
			$arr_checkoutFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'];
			$str_value =		$arr_checkoutFormFields[$str_fieldName]['value']
							?	$arr_checkoutFormFields[$str_fieldName]['value']
							:	'';

			return $str_value;
		}

		protected function payone_getShippingFieldValue($str_fieldName) {
            $str_valueWildcardPattern = '/(?:#|&#35;){2}value::(.*)(?:#|&#35;){2}/';
            if (preg_match($str_valueWildcardPattern, $str_fieldName, $arr_matches)) {
                $str_fieldName = preg_replace($str_valueWildcardPattern, $this->payone_getShippingFieldValue($arr_matches[1]), $str_fieldName);
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