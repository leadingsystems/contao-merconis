<?php

namespace Merconis\Core;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\WebProfile;
use PayPal\Api\Presentation;
use PayPal\Api\Payment;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PayerInfo;
use PayPal\Api\ShippingAddress;
use PayPal\Api\PatchRequest;
use PayPal\Api\Patch;
use PayPal\Api\PaymentExecution;
use PayPal\Exception\PayPalConnectionException;

class ls_shop_paymentModule_payPalPlus extends ls_shop_paymentModule_standard {
	public $arrCurrentSettings = array();
	
	protected $payPalPlus_obj_apiContext = null;
	
	protected $payPalPlus_arr_returnUrls = array(
		'return' => '',
		'notAuthorized' => '',
		'authorized' => ''
	);

	public function initialize($specializedManually = false) {
        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopPaymentProcess =  $session->get('lsShopPaymentProcess');

		if (!isset($session_lsShopPaymentProcess['payPalPlus']) || !is_array($session_lsShopPaymentProcess['payPalPlus'])) {
			$this->payPalPlus_resetSessionStatus();
		}
		
		$this->payPalPlus_createApiContext();
		
		$this->payPalPlus_determineRedirectUrls();
		$this->payPalPlus_handleReturnUrlCall();
		
		$this->payPalPlus_checkRelevantCalculationDataHash();
	}
	
	public function statusOkayToShowCustomUserInterface() {
		return ls_shop_cartX::getInstance()->calculation['invoicedAmount'] > 0 ? true : false;
	}
	
	public function getCustomUserInterface() {
		if ($this->payPalPlus_check_paymentIsAuthorized()) {
			return $this->payPalPlus_showAuthorizationStatus();
		} else {
			if ($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameCountryCode'])) {
				return $this->payPalPlus_showPaymentWall();
			}
		}
	}
	
	public function checkoutFinishAllowed() {
		return $this->payPalPlus_check_paymentIsAuthorized();
	}
	
	public function statusOkayToRedirectToCheckoutFinish() {
		return $this->payPalPlus_check_paymentIsAuthorized();
	}
	
	public function afterCheckoutFinish($orderIdInDb = 0, $order = array(), $afterCheckoutUrl = '', $oix = '') {
        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopPaymentProcess =  $session->get('lsShopPaymentProcess');

        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop');
        $session_lsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = '';
        $session->set('lsShop', $session_lsShop);
		
		$obj_payment = Payment::get($session_lsShopPaymentProcess['payPalPlus']['paymentId'], $this->payPalPlus_obj_apiContext);
		$obj_execute = new PaymentExecution();
		$obj_execute->setPayerId($session_lsShopPaymentProcess['payPalPlus']['PayerID']);
		
		try {
			$obj_payment->execute($obj_execute, $this->payPalPlus_obj_apiContext);
			$this->payPalPlus_updateSaleDetailsInOrderRecord($orderIdInDb);
			$this->payPalPlus_resetSessionStatus();
		} catch (\Exception $e) {
			/*
			 * Ideally, we could catch an exception if something goes wrong and
			 * then jump to the payment error page and not finish the order but
			 * unfortunately in this situation we have to either risk that the
			 * payment is executed and finishing the order could still fail
			 * (that's extremely bad) or to risk that the order is successfully
			 * finished and the payment fails. A failed payment isn't as bad as
			 * a failed order after a completed payment because if the merchant
			 * doesn't get his money he won't ship the goods but instead contact
			 * the buyer.
			 * 
			 * Conclusion: We catch the exception only to log and silence it.
			 */
			$this->logPaymentError(__METHOD__, $e->getMessage(), json_encode($e->getData()));
			
			$paymentMethod_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($orderIdInDb);
			$paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus'] = 'Payment module error (see order details)';
			$paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg'] = $e->getMessage().' ERROR DATA: '.json_encode($e->getData());

			$this->payPalPlus_updateSaleDetailsInOrderRecord($orderIdInDb, $paymentMethod_moduleReturnData);
			$this->payPalPlus_resetSessionStatus();
		}
	}
	
	public function afterPaymentMethodSelection() {
		$this->payPalPlus_resetSessionStatus();
	}

	public function getPaymentInfo() {
        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopPaymentProcess =  $session->get('lsShopPaymentProcess');

		$arrPaymentInfo = array(
			'str_paymentId' => $session_lsShopPaymentProcess['payPalPlus']['paymentId'],
			'arr_saleDetails' => array(
				'str_saleId' => '',
				'str_currentStatus' => '',
				'str_errorMsg' => ''
			)
		);
		return serialize($arrPaymentInfo);
	}
	
	protected function payPalPlus_updateSaleDetailsInOrderRecord($int_orderIdInDb, $paymentMethod_moduleReturnData = null) {
		if (!$int_orderIdInDb) {
			return;
		}
		
		if (!is_array($paymentMethod_moduleReturnData)) {
			$paymentMethod_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($int_orderIdInDb);
			
			if ($paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg']) {
				/*
				 * Don't read the status from paypal and don't update the paymentMethod_moduleReturnData
				 * if it already contains an error because we don't want to override
				 * the error message.
				 */
				return $paymentMethod_moduleReturnData;
			}

			$paymentMethod_moduleReturnData['arr_saleDetails'] = $this->payPalPlus_getSaleDetailsForPaymentId($paymentMethod_moduleReturnData['str_paymentId']);
		}
		
		/*
		 * Update the whole moduleReturnData ...
		 */
		$this->update_paymentMethod_moduleReturnData_inOrder($int_orderIdInDb, $paymentMethod_moduleReturnData);
		
		/*
		 * ... and also update special payment data fields that are stored separately
		 * in the order record because they need to be usable for filtering/searching
		 * in the order overview
		 */
		$this->update_fieldValue_inOrder($int_orderIdInDb, 'payPalPlus_saleId', $paymentMethod_moduleReturnData['arr_saleDetails']['str_saleId']);
		$this->update_fieldValue_inOrder($int_orderIdInDb, 'payPalPlus_currentStatus', $paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus']);
		
		return $paymentMethod_moduleReturnData;
	}
	
	protected function payPalPlus_getSaleDetailsForPaymentId($str_paymentId) {
		$arr_saleDetails = array(
			'str_saleId' => '',
			'str_currentStatus' => ''
		);
		
		if (!$str_paymentId) {
			return $arr_saleDetails;
		}
		
		$obj_payment = Payment::get($str_paymentId, $this->payPalPlus_obj_apiContext);
		
		$obj_transactions = $obj_payment->getTransactions();
		if (count($obj_transactions) != 1) {
			/*
			 * Not exactly one transaction given
			 */
			$arr_saleDetails['str_currentStatus'] = 'payment information could not be read correctly [ppp01]';
			return $arr_saleDetails;
		}
		$obj_transaction = reset($obj_transactions);
		
		$obj_relatedResources = $obj_transaction->getRelatedResources();

		$obj_sale = null;
		foreach ($obj_transaction->getRelatedResources() as $obj_relatedResource) {
			$obj_sale = $obj_relatedResource->getSale();
			if ($obj_sale !== null) {
				break;
			}
		}
		
		if ($obj_sale === null) {
			/*
			 * No sale transaction found
			 */
			$arr_saleDetails['str_currentStatus'] = 'payment information could not be read correctly [ppp02]';
			return $arr_saleDetails;
		}
		
		$arr_saleDetails['str_currentStatus'] = $obj_sale->getState();
		$arr_saleDetails['str_saleId'] = $obj_sale->getId();
		
		return $arr_saleDetails;
	}
	
	public function showPaymentDetailsInBackendOrderDetailView($arrOrder = array(), $paymentMethod_moduleReturnData = '') {
		if (!count($arrOrder) || !$paymentMethod_moduleReturnData) {
			return null;
		}
		
		$outputValue = '';
		$paymentMethod_moduleReturnData = $this->payPalPlus_updateSaleDetailsInOrderRecord($arrOrder['id']);
		
		ob_start();
		?>
		<div class="paymentDetails payPalPlus">
			<h2>
				<a href="https://www.paypal.com/" target="_blank">
					<img src="https://www.paypalobjects.com/webstatic/de_DE/i/de-pp-logo-150px.png" border="0" alt="PayPal Logo" />
				</a>
				<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalPlus']['headlineBackendDetailsInfo']; ?>
			</h2>
			<div class="content">
				<div class="details">
					<div class="detailItem">
						<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalPlus']['status']; ?>:</span>
						<span class="value"><?php echo strtoupper($paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus']); ?></span>
					</div>
					<?php
						if ($paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg']) {
							?>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalPlus']['errorMsgLabel']; ?>:</span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg']; ?></span>
							</div>							
							<?php
						}
					?>
					<div class="detailItem">
						<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalPlus']['saleTransactionCode']; ?>:</span>
						<span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_saleId']; ?></span>
					</div>
				</div>
			</div>
		</div>

		<?php
		$outputValue = ob_get_clean();
		return $outputValue;
	}
	
	public function showPaymentStatusInOverview($arrOrder = array(), $paymentMethod_moduleReturnData = '') {
		if (!count($arrOrder) || !$paymentMethod_moduleReturnData) {
			return null;
		}
		
		if (Input::get('payPalPlus_updateStatus') && Input::get('payPalPlus_updateStatus') == $arrOrder['id']) {
			$this->payPalPlus_updateSaleDetailsInOrderRecord($arrOrder['id']);
			$this->redirect(ls_shop_generalHelper::getUrl(true, array('payPalPlus_updateStatus')));
		}

		$outputValue = '';
		$paymentMethod_moduleReturnData = StringUtil::deserialize($paymentMethod_moduleReturnData);

		$str_statusUpdateUrl = ls_shop_generalHelper::getUrl();
		$str_statusUpdateUrl = $str_statusUpdateUrl.(strpos($str_statusUpdateUrl, '?') !== false ? '&' : '?').'payPalPlus_updateStatus='.$arrOrder['id'].'#payPalPlus_order'.$arrOrder['id'];

		ob_start();
		?>
		<div id="payPalPlus_order<?php echo $arrOrder['id']; ?>" class="paymentStatusInOverview payPalPlus <?php echo strtolower(preg_replace('/\s+/', '-', $arrLastStatus['statusValue'])); ?>">
			<img src="https://www.paypalobjects.com/webstatic/de_DE/i/de-pp-logo-100px.png" border="0" alt="PayPal Logo" />
			<div class="content">
				<div class="details">
					<div class="detailItem">
						<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalPlus']['status']; ?>:</span>
						<span class="value"><?php echo strtoupper($paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus']); ?></span>
					</div>
					<div class="detailItem">
						<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalPlus']['saleTransactionCode']; ?>:</span>
						<span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_saleId']; ?></span>
					</div>
				</div>
			</div>
			<div class="statusUpdate">
				<a href="<?php echo $str_statusUpdateUrl; ?>"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalPlus']['updateStatus']; ?></a>
			</div>
		</div>
		<?php
		$outputValue = ob_get_clean();
		return $outputValue;
	}
	
	/*
	 * This function takes the relevant calculation data and creates an sha1 hash
	 * from it. This hash will then be stored in the session so that everytime
	 * this function is called the current calculation status can be compared
	 * to the last one. If the calculation status differs, an already existing
	 * pay pal authorization is obsolete.
	 */
	protected function payPalPlus_checkRelevantCalculationDataHash() {
        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopPaymentProcess =  $session->get('lsShopPaymentProcess');

		$str_relevantCalculationDataHash = sha1(
				ls_shop_cartX::getInstance()->calculation['shippingFee'][0]
			.	ls_shop_cartX::getInstance()->calculation['paymentFee'][0]
			.	ls_shop_cartX::getInstance()->calculation['taxInclusive']
			.	ls_shop_cartX::getInstance()->calculation['invoicedAmount']
			.	ls_shop_cartX::getInstance()->calculation['invoicedAmountNet']
			.	$GLOBALS['TL_CONFIG']['ls_shop_currencyCode']
		);
		
		/*
		 * If the payment has not been authorized yet or the relevantCalculationDataHash
		 * has not been stored in the session yet, we store it now.
		 */
		if (
				!$this->payPalPlus_check_paymentIsAuthorized()
			||	!isset($session_lsShopPaymentProcess['payPalPlus']['relevantCalculationDataHash'])
			||	!$session_lsShopPaymentProcess['payPalPlus']['relevantCalculationDataHash']
		) {
            $session_lsShopPaymentProcess['payPalPlus']['relevantCalculationDataHash'] = $str_relevantCalculationDataHash;
            $session->set('lsShop', $session_lsShopPaymentProcess);
		}
		
		/*
		 * But if the payment has already been authorized and the relevantCalculationDataHash
		 * is stored in the session, we compare the hash to the current hash and
		 * if it differs, we reset the payment status and display a message.
		 */
		else if ($session_lsShopPaymentProcess['payPalPlus']['relevantCalculationDataHash'] != $str_relevantCalculationDataHash) {
			$this->setPaymentMethodErrorMessage($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalPlus']['authorizationObsolete']);
			$this->payPalPlus_resetSessionStatus();
		}
	}
	
	protected function payPalPlus_check_paymentIsAuthorized() {
        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopPaymentProcess =  $session->get('lsShopPaymentProcess');

		return (
				isset($session_lsShopPaymentProcess['payPalPlus']['authorized'])
			&&	$session_lsShopPaymentProcess['payPalPlus']['authorized']
			&&	$session_lsShopPaymentProcess['payPalPlus']['paymentId']
			&&	$session_lsShopPaymentProcess['payPalPlus']['PayerID']
		);
	}
	
	protected function payPalPlus_showAuthorizationStatus() {
		$obj_template = new FrontendTemplate('payPalPlusCustomUserInterface');
		$obj_template->bln_paymentAuthorized = true;
		return $obj_template->parse();		
	}
	
	protected function payPalPlus_showPaymentWall() {
		/** @var PageModel $objPage */
		global $objPage;
		try {
			$str_approvalUrl = $this->payPalPlus_createPayment();
		} catch (PayPalConnectionException $e) {
			$this->redirectToErrorPage(__METHOD__, 'An exception occured: '.$e->getMessage(), json_encode($e->getData()));
		}
		
		if (!$str_approvalUrl) {
			$this->redirectToErrorPage(__METHOD__, 'no approval url received as a result of the call to '.__CLASS__.'::payPalPlus_createPayment');
		}
		
		$obj_template = new FrontendTemplate('payPalPlusCustomUserInterface');
		$obj_template->bln_paymentAuthorized = false;
		$obj_template->str_approvalUrl = $str_approvalUrl;
		
		/*
		 * Must be the country code of the billing address
		 */
		$obj_template->str_countryCode = strtoupper(ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'][$this->arrCurrentSettings['payPalPlus_shipToFieldNameCountryCode']]['value']);
		
		$obj_template->str_mode = $this->arrCurrentSettings['payPalPlus_liveMode'] ? 'live' : 'sandbox';
		
		$obj_template->str_language = $objPage->language;
		
		return $obj_template->parse();
	}
	
	protected function payPalPlus_createApiContext() {
		if ($this->payPalPlus_obj_apiContext !== null) {
			return;
		}
		
		$this->payPalPlus_obj_apiContext = new ApiContext(
			new OAuthTokenCredential(
				$this->arrCurrentSettings['payPalPlus_clientID'],
				$this->arrCurrentSettings['payPalPlus_clientSecret']
			)
		);
		
		$this->payPalPlus_obj_apiContext->setConfig(array(
			'http.ConnectionTimeOut' => 30,
			'http.Retry' => 1,
			'mode' => $this->arrCurrentSettings['payPalPlus_liveMode'] ? 'live' : 'sandbox',
			'log.LogEnabled' => $this->arrCurrentSettings['payPalPlus_logMode'] !== 'NONE',
			'log.FileName' => System::getContainer()->getParameter('kernel.project_dir').'/system/logs/PayPal.log',
			'log.LogLevel' => $this->arrCurrentSettings['payPalPlus_logMode']
        ));

	}
	
	protected function payPalPlus_createPayment() {
		$str_approvalUrl = null;
		
		$obj_redirectUrls = new RedirectUrls();
		$obj_payer = new Payer();
		$obj_details = new Details();
		$obj_amount = new Amount();
		$obj_transaction = new Transaction();
		$obj_payment = new Payment();
		
		$obj_redirectUrls
			->setReturnUrl($this->payPalPlus_arr_returnUrls['authorized'])
			->setCancelUrl($this->payPalPlus_arr_returnUrls['notAuthorized']);
		
		$obj_payer->setPaymentMethod("paypal");
		
		$obj_details
			->setShipping(ls_shop_cartX::getInstance()->calculation['shippingFee'][0])
			->setHandlingFee(ls_shop_cartX::getInstance()->calculation['paymentFee'][0])
			->setTax(ls_shop_cartX::getInstance()->calculation['taxInclusive'] ? '0.00' : number_format(ls_sub(ls_shop_cartX::getInstance()->calculation['invoicedAmount'], ls_shop_cartX::getInstance()->calculation['invoicedAmountNet']), 2, '.', ''))
			->setSubtotal(ls_shop_cartX::getInstance()->calculation['taxInclusive'] ? number_format(ls_sub(ls_sub(ls_shop_cartX::getInstance()->calculation['invoicedAmount'], ls_shop_cartX::getInstance()->calculation['shippingFee'][0]), ls_shop_cartX::getInstance()->calculation['paymentFee'][0]), 2, '.', '') : number_format(ls_sub(ls_sub(ls_shop_cartX::getInstance()->calculation['invoicedAmountNet'], ls_shop_cartX::getInstance()->calculation['shippingFee'][0]), ls_shop_cartX::getInstance()->calculation['paymentFee'][0]), 2, '.', ''));
		
		$obj_amount
			->setCurrency($GLOBALS['TL_CONFIG']['ls_shop_currencyCode'])
			->setTotal(number_format(ls_shop_cartX::getInstance()->calculation['invoicedAmount'], 2, '.', ''))
			->setDetails($obj_details);
		
		$obj_itemList = new ItemList();
		foreach (ls_shop_cartX::getInstance()->calculation['items'] as $arr_cartItem) {
			$arr_cartItemExtended = ls_shop_cartX::getInstance()->itemsExtended[$arr_cartItem['productCartKey']];
			$obj_item = new Item();
			$obj_item
				->setName(substr(System::getContainer()->get('contao.insert_tag.parser')->replace($arr_cartItemExtended['objProduct']->_title), 0, 127))
				->setDescription($arr_cartItemExtended['objProduct']->_hasCode ? substr($arr_cartItemExtended['objProduct']->_code, 0, 127) : '')
				->setCurrency($GLOBALS['TL_CONFIG']['ls_shop_currencyCode'])
				/*
				 * We would like to simply set the quantity and the (not
				 * cumulative) item price but unfortunately there is problem
				 * if the quantity is a decimal value because the item amounts
				 * and the total don't add up in the end.
				 * 
				 * Therefore we use the workaround a few lines below.
				 */
				/* *
				->setQuantity((float)$arr_cartItemExtended['quantity'])
				->setPrice(number_format($arr_cartItem['price'], 2, '.', ''))
				/* */
				;
			
			/*
			 * Workaround for the decimal quantity problem:
			 * If the quantity is an integer/not a decimal value, we set the
			 * quantity just as it is and also the non-cumulative item amount.
			 * But if the quantity is a decimal value, we set the quantity to 1
			 * and the item amount to the cumulative item amount. This means
			 * that we declare the whole cart position as one make-believe item.
			 * We note this in the description.
			 */
			/* */
			if (intval($arr_cartItemExtended['quantity']) == $arr_cartItemExtended['quantity']) {
				$obj_item
					->setQuantity($arr_cartItemExtended['quantity'])
					->setPrice(number_format($arr_cartItem['price'], 2, '.', ''));
			} else {
				$obj_item
					->setQuantity(1)
					->setPrice(number_format($arr_cartItem['priceCumulative'], 2, '.', ''))
					->setDescription($obj_item->getDescription().' ('.$arr_cartItemExtended['quantity'].' '.$arr_cartItemExtended['objProduct']->_quantityUnit.' * '.$arr_cartItemExtended['objProduct']->_priceAfterTaxFormatted.')');
			}
			/* */
			
			$obj_itemList->addItem($obj_item);
		}
		
		foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $arr_couponValue) {
			$obj_itemCoupon = new Item();
			$obj_itemCoupon
				->setName('COUPON')
				->setDescription('')
				->setCurrency($GLOBALS['TL_CONFIG']['ls_shop_currencyCode'])
				->setQuantity(1)
				->setPrice(number_format($arr_couponValue[0], 2, '.', ''));

			$obj_itemList->addItem($obj_itemCoupon);
		}
		
		$obj_itemList->setShippingAddress($this->payPalPlus_createShippingAddress());
		
		$obj_transaction
			->setAmount($obj_amount)
			->setItemList($obj_itemList)
			->setDescription('Purchase from '.date('Y-m-d, H:i'));
		
        $obj_payment
			->setIntent('sale')
			->setPayer($obj_payer)
			->setTransactions(array($obj_transaction))
			->setRedirectUrls($obj_redirectUrls);

		$obj_payment->create($this->payPalPlus_obj_apiContext);			
		
		foreach ($obj_payment->getLinks() as $obj_link) {
			if ($obj_link->getRel() == 'approval_url') {
				$str_approvalUrl = $obj_link->getHref();
				break;
			}
		}
		
		return $str_approvalUrl;
	}
	
	protected function payPalPlus_determineRedirectUrls() {
		$this->payPalPlus_arr_returnUrls['return'] = Environment::get('base').ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages');
		$this->payPalPlus_arr_returnUrls['notAuthorized'] = $this->payPalPlus_arr_returnUrls['return'].(preg_match('/\?/', $this->payPalPlus_arr_returnUrls['return']) ? '&' : '?').'&approvedPayPalPlusPayment=no';
		$this->payPalPlus_arr_returnUrls['authorized'] = $this->payPalPlus_arr_returnUrls['return'].(preg_match('/\?/', $this->payPalPlus_arr_returnUrls['return']) ? '&' : '?').'approvedPayPalPlusPayment=yes';
	}

	protected function payPalPlus_handleReturnUrlCall() {
		/*
		 * This is not a return url call, so don't do anything.
		 */
		if (!Input::get('approvedPayPalPlusPayment')) {
			return;
		}
		
		/*
		 * Cancel url called, not authorized!
		 */
		if (
				!filter_var(Input::get('approvedPayPalPlusPayment'), FILTER_VALIDATE_BOOLEAN)
			||	!Input::get('paymentId')
			||	!Input::get('PayerID')
		) {
			$this->payPalPlus_resetSessionStatus();
			$this->setPaymentMethodErrorMessage($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalPlus']['paymentNotAuthorized']);
			$this->redirect($this->payPalPlus_arr_returnUrls['return'].'#checkoutStepPayment');
		}

        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopPaymentProcess =  $session->get('lsShopPaymentProcess');

        $session_lsShopPaymentProcess['payPalPlus']['authorized'] = true;
        $session_lsShopPaymentProcess['payPalPlus']['paymentId'] = Input::get('paymentId');
        $session_lsShopPaymentProcess['payPalPlus']['PayerID'] = Input::get('PayerID');

        $session->set('lsShop', $session_lsShopPaymentProcess);
		
		$this->redirect($this->payPalPlus_arr_returnUrls['return'].'#checkoutStepPayment');
	}
	
	protected function payPalPlus_resetSessionStatus() {
        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopPaymentProcess =  $session->get('lsShopPaymentProcess');

        $session_lsShopPaymentProcess['payPalPlus'] = array(
			'authorized' => false,
			'paymentId' => null,
			'PayerID' => null,
			'relevantCalculationDataHash' => null
		);

        $session->set('lsShop', $session_lsShopPaymentProcess);
	}
	
	protected function payPalPlus_getShippingFieldValue($str_fieldName) {
	    $str_valueWildcardPattern = '/(?:#|&#35;){2}value::(.*)(?:#|&#35;){2}/';
	    if (preg_match($str_valueWildcardPattern, $str_fieldName, $arr_matches)) {
	        $str_fieldName = preg_replace($str_valueWildcardPattern, $this->payPalPlus_getShippingFieldValue($arr_matches[1]), $str_fieldName);
        }

		$arrCheckoutFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'];
		$str_value = $arrCheckoutFormFields[$str_fieldName.(isset($arrCheckoutFormFields['useDeviantShippingAddress']['value']) && $arrCheckoutFormFields['useDeviantShippingAddress']['value'] ? '_alternative' : '')]['value'];

		if (!$str_value) {
			$str_value = null;
		}
		
		return $str_value;
    }
	
	protected function payPalPlus_createShippingAddress() {
		$obj_shippingAddress = new ShippingAddress();
		
		$obj_shippingAddress->setRecipientName($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameFirstname']).' '.$this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameLastname']));
		
		if ($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameStreet'])) {
			$obj_shippingAddress->setLine1($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameStreet']));
		}
		
		if ($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameCity'])) {
			$obj_shippingAddress->setCity($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameCity']));
		}
		
		if ($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameCountryCode'])) {
			$obj_shippingAddress->setCountryCode(strtoupper($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameCountryCode'])));
		}
		
		if ($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNamePostal'])) {
			$obj_shippingAddress->setPostalCode($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNamePostal']));
		}

		if ($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameState'])) {
			$obj_shippingAddress->setState($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNameState']));
		}
		
		if ($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNamePhone'])) {
			$obj_shippingAddress->setPhone($this->payPalPlus_getShippingFieldValue($this->arrCurrentSettings['payPalPlus_shipToFieldNamePhone']));
		}
		
		return $obj_shippingAddress;
	}
}
?>