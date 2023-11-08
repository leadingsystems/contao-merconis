<?php
namespace Merconis\Core;
use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;

class ls_shop_paymentModule_payPalCheckout extends ls_shop_paymentModule_standard {
    const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';
    const LIVE_URL = 'https://api-m.paypal.com';
    public $arrCurrentSettings = array();
    public function initialize($specializedManually = false) {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $arrSessionlsShopPaymentProcess =  $session->get('lsShopPaymentProcess', []);

        if (!isset($arrSessionlsShopPaymentProcess['payPalCheckout']) || !is_array($arrSessionlsShopPaymentProcess['payPalCheckout'])) {
            $this->payPalCheckout_resetSessionStatus();
        }
        $this->payPalCheckout_checkRelevantCalculationDataHash();
    }
    public function statusOkayToShowCustomUserInterface() {
        return ls_shop_cartX::getInstance()->calculation['invoicedAmount'] > 0 ? true : false;
    }

    private function writeLog($outputType, $output){

        if (is_array($output) || is_object($output)) {
            ob_start();
            print_r($output);
            $output = ob_get_clean();
        }

        if($this->arrCurrentSettings['payPalCheckout_logMode'] !== 'NONE') {
            $myfile = fopen(TL_ROOT . '/system/logs/paypalCheckout.log', "a");
            fwrite($myfile, "[".date("d-m-Y h:i:sa")."] [".$outputType."] ".$output."\n");
            fclose($myfile);
        }
        //$this->arrCurrentSettings['payPalCheckout_logMode']
        //TL_ROOT.'/system/logs/PayPal.log',
    }

    public function getCustomUserInterface() {

        if(\Input::post('payPalCheckout_reset')){
            $this->payPalCheckout_resetSessionStatus();
            \Controller::reload();
        }
        if (\Input::post('payPalCheckout_orderId') && \Input::post('payPalCheckout_authorizationId')) {
            $session = \System::getContainer()->get('merconis.session')->getSession();
            $arrSessionlsShopPaymentProcess =  $session->get('lsShopPaymentProcess', []);

            $arrSessionlsShopPaymentProcess['payPalCheckout']['orderId'] = \Input::post('payPalCheckout_orderId');
            $arrSessionlsShopPaymentProcess['payPalCheckout']['authorizationId'] = \Input::post('payPalCheckout_authorizationId');
            $arrSessionlsShopPaymentProcess['payPalCheckout']['authorized'] = true;

            $session->set('lsShopPaymentProcess', $arrSessionlsShopPaymentProcess);
            \Controller::reload();
        }
        if ($this->payPalCheckout_check_paymentIsAuthorized()) {
            return $this->payPalCheckout_showAuthorizationStatus();
        } else {
            return $this->payPalCheckout_showPaymentWall();
        }
    }
    private function payPalCheckout_createOrder(){
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $arrSessionlsShopPaymentProcess =  $session->get('lsShopPaymentProcess', []);

        if ($arrSessionlsShopPaymentProcess['payPalCheckout']['orderId']) {
            return $arrSessionlsShopPaymentProcess['payPalCheckout']['orderId'];
        }

        $access_token = $this->payPalCheckout_getaccessToken();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ($this->arrCurrentSettings['payPalCheckout_liveMode'] ? self::LIVE_URL : self::SANDBOX_URL).'/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->arrCurrentSettings['payPalCheckout_liveMode'] ? true : false);

        $itemlist = [];
        $currency_code = $GLOBALS['TL_CONFIG']['ls_shop_currencyCode'];
        foreach (ls_shop_cartX::getInstance()->calculation['items'] as $arr_cartItem) {
            $arr_cartItemExtended = ls_shop_cartX::getInstance()->itemsExtended[$arr_cartItem['productCartKey']];
            $name = substr(\Controller::replaceInsertTags($arr_cartItemExtended['objProduct']->_title), 0, 127);
            $description = $arr_cartItemExtended['objProduct']->_hasCode ? substr($arr_cartItemExtended['objProduct']->_code, 0, 127) : '';
            if (intval($arr_cartItemExtended['quantity']) == $arr_cartItemExtended['quantity']) {
                $quantity = $arr_cartItemExtended['quantity'];
                $price = number_format($arr_cartItem['price'], 2, '.', '');
            } else {
                $quantity = 1;
                $price = number_format($arr_cartItem['priceCumulative'], 2, '.', '');
                $description = $description.' ('.$arr_cartItemExtended['quantity'].' '.$arr_cartItemExtended['objProduct']->_quantityUnit.' * '.$arr_cartItemExtended['objProduct']->_priceAfterTaxFormatted.')';
            }


            $itemlist[] = [
                "name"=> $name,
                "unit_amount"=> [
                    "currency_code"=> $currency_code,
                    "value"=> number_format($price , 2, '.', '')
                ],
                "quantity"=> strval($quantity)
            ];
        }
        $discount = 0;
        //discount must be positiv not negativ thats why (-1)*
        foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $arr_couponValue) {
            $discount = ls_add($discount, $arr_couponValue[0]);
        }
        $discount = number_format((-1)*$discount, 2, '.', '');


        if ($this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameFirstname'])) { //firstname
            $firstname = $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameFirstname']);
        }
        if ($this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameLastname'])) { //lastname
            $lastname = $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameLastname']);
        }
        if ($this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameStreet'])) { //street
            $street = $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameStreet']);
        }
        if ($this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameCity'])) { //city
            $city = $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameCity']);
        }
        if ($this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameCountryCode'])) { //country
            $countryCode = strtoupper($this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameCountryCode']));
        }
        if ($this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNamePostal'])) { //postal
            $postalCode = $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNamePostal']);
        }
        if ($this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameState'])) { //state
            $state = $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameState']);
        }
        $arr_adress = [
            "address_line_1"=>  $street,
            "admin_area_2"=>  $city,
            "postal_code"=>  $postalCode,
            "country_code"=>  $countryCode,
        ];
        //add state if exist
        if($state){
            $arr_adress["admin_area_1"] = $state;
        }

        $arr_requestBody = [
            "intent" => "AUTHORIZE",
            "application_context"=> [
                "shipping_preference"=> 'SET_PROVIDED_ADDRESS',
            ],
            "purchase_units" =>  [
                [
                    "amount"=> [
                        "currency_code"=> $currency_code,
                        "value"=>  number_format(ls_shop_cartX::getInstance()->calculation['invoicedAmount'], 2, '.', ''),
                        "breakdown"=> [
                            "item_total"=> [
                                "currency_code"=> $currency_code,
                                "value"=> number_format(ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'][0], 2, '.', '')
                            ],
                            "tax_total"=> [
                                "currency_code"=> $currency_code,
                                "value"=> ls_shop_cartX::getInstance()->calculation['taxInclusive'] ? '0.00' : number_format(ls_sub(ls_shop_cartX::getInstance()->calculation['invoicedAmount'], ls_shop_cartX::getInstance()->calculation['invoicedAmountNet']), 2, '.', '')
                            ],
                            "discount"=> [
                                "currency_code"=> $currency_code,
                                "value"=> $discount
                            ],
                            "shipping"=> [
                                "currency_code"=> $currency_code,
                                "value"=> number_format(ls_shop_cartX::getInstance()->calculation['shippingFee'][0] > 0 ? ls_shop_cartX::getInstance()->calculation['shippingFee'][0] : 0, 2, '.', '')
                            ],
                            "shipping_discount"=> [
                                "currency_code"=> $currency_code,
                                "value"=>   number_format(
                                                (ls_shop_cartX::getInstance()->calculation['shippingFee'][0] < 0 ? abs(ls_shop_cartX::getInstance()->calculation['shippingFee'][0]) : 0)
                                                + (ls_shop_cartX::getInstance()->calculation['paymentFee'][0] < 0 ? abs(ls_shop_cartX::getInstance()->calculation['paymentFee'][0]) : 0),
                                                2, '.', ''
                                            )
                            ],
                            "handling"=> [
                                "currency_code"=> $currency_code,
                                "value"=> number_format(ls_shop_cartX::getInstance()->calculation['paymentFee'][0] > 0 ? ls_shop_cartX::getInstance()->calculation['paymentFee'][0] : 0, 2, '.', '')
                            ]
                        ]
                    ],
                    "shipping"=>  [
                        "name"=>  [
                            "full_name"=>  $firstname.' '.$lastname
                        ],
                        "address"=> $arr_adress
                    ],
                    "items" => $itemlist
                ]
            ]
        ];

        $this->writeLog('Request Data', $arr_requestBody);

        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($arr_requestBody));
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer '.$access_token;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $result = curl_exec($ch);

        $this->writeLog("Request", curl_getinfo($ch)['request_header']);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $this->writeLog("Response", $result);

        $orderId = json_decode($result)->id;

        $arrSessionlsShopPaymentProcess['payPalCheckout']['orderId'] = $orderId;
        $session->set('lsShopPaymentProcess', $arrSessionlsShopPaymentProcess);

        return $orderId;
    }
    public function checkoutFinishAllowed() {
        return $this->payPalCheckout_check_paymentIsAuthorized();
    }
    public function statusOkayToRedirectToCheckoutFinish() {
        return $this->payPalCheckout_check_paymentIsAuthorized();
    }
    private function payPalCheckout_getaccessToken(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ($this->arrCurrentSettings['payPalCheckout_liveMode'] ? self::LIVE_URL : self::SANDBOX_URL).'/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_USERPWD, $this->arrCurrentSettings['payPalCheckout_clientID'] . ':' . $this->arrCurrentSettings['payPalCheckout_clientSecret']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->arrCurrentSettings['payPalCheckout_liveMode'] ? true : false);
        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $result = curl_exec($ch);

        $this->writeLog("Request", curl_getinfo($ch)['request_header']);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $this->writeLog("Response", $result);
        return json_decode($result)->access_token;
    }

    public function afterCheckoutFinish($orderIdInDb = 0, $order = array(), $afterCheckoutUrl = '', $oix = '') {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $arrLsShop =  $session->get('lsShop', []);
        $arrSessionlsShopPaymentProcess =  $session->get('lsShopPaymentProcess', []);
        $arrLsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = '';


        $access_token = $this->payPalCheckout_getaccessToken();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ($this->arrCurrentSettings['payPalCheckout_liveMode'] ? self::LIVE_URL : self::SANDBOX_URL).'/v2/payments/authorizations/'. $arrSessionlsShopPaymentProcess['payPalCheckout']['authorizationId'] .'/capture');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->arrCurrentSettings['payPalCheckout_liveMode'] ? true : false);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer '.$access_token;
        //$headers[] = 'PayPal-Mock-Response: {"mock_application_codes": "PAYER_CANNOT_PAY"}';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $result = curl_exec($ch);

        $this->writeLog("Request", curl_getinfo($ch)['request_header']);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $this->writeLog("Response", $result);
        $status = json_decode($result)->status;

        try {
            if($status == "COMPLETED"){
                // write the success message to the special payment info
                $arrLsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['paymentSuccessAfterFinishedOrder'];
            }else{
                // write the error message to the special payment info -> order is completet but payment is incomplete
                $arrLsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['paymentErrorAfterFinishedOrder'];
            }
            //egal ob es schief läuft oder nicht oder immer abspeichern
            $this->payPalCheckout_updateSaleDetailsInOrderRecord($orderIdInDb);
            $this->payPalCheckout_resetSessionStatus(false);
        } catch (\Exception $e) {
            $this->logPaymentError(__METHOD__, $e->getMessage());
            $paymentMethod_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($orderIdInDb);
            $paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus'] = 'Payment module error (see order details)';
            $paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg'] = $e->getMessage().' ERROR DATA: '.json_encode($e->getData());
            $this->payPalCheckout_resetSessionStatus(false);
        }
        $session->set('lsShop', $arrLsShop);
    }
    public function afterPaymentMethodSelection() {
        $this->payPalCheckout_resetSessionStatus();
    }
    public function getPaymentInfo() {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $arrSessionlsShopPaymentProcess =  $session->get('lsShopPaymentProcess', []);

        $arrPaymentInfo = array(
            'str_authorizationId' => $arrSessionlsShopPaymentProcess['payPalCheckout']['authorizationId'],
            'str_orderId' =>$arrSessionlsShopPaymentProcess['payPalCheckout']['orderId'],
        );
        return serialize($arrPaymentInfo);
    }
    protected function payPalCheckout_updateSaleDetailsInOrderRecord($int_orderIdInDb, $paymentMethod_moduleReturnData = null) {
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
            $paymentMethod_moduleReturnData['arr_saleDetails'] = $this->payPalCheckout_getSaleDetailsForOrderId($paymentMethod_moduleReturnData['str_orderId']);
        }
        $this->update_paymentMethod_moduleReturnData_inOrder($int_orderIdInDb, $paymentMethod_moduleReturnData);
        $this->update_fieldValue_inOrder($int_orderIdInDb, 'payPalCheckout_orderId', $paymentMethod_moduleReturnData['arr_saleDetails']['str_orderId']);
        $this->update_fieldValue_inOrder($int_orderIdInDb, 'payPalCheckout_currentStatus', $paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus']);
        return $paymentMethod_moduleReturnData;
    }
    protected function payPalCheckout_getSaleDetailsForOrderId($str_orderId) {
        $arr_saleDetails = array(
            'str_orderId' => '',
            'str_currentStatus' => '',
            'str_authorizationId' => '',
            'str_authorizationStatus' => '',
            'str_captureId' => '',
            'str_captureStatus' => ''
        );
        if (!$str_orderId) {
            return $arr_saleDetails;
        }
        $access_token = $this->payPalCheckout_getaccessToken();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ($this->arrCurrentSettings['payPalCheckout_liveMode'] ? self::LIVE_URL : self::SANDBOX_URL).'/v2/checkout/orders/'.$str_orderId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->arrCurrentSettings['payPalCheckout_liveMode'] ? true : false);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer '.$access_token;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $result = curl_exec($ch);

        $this->writeLog("Request", curl_getinfo($ch)['request_header']);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $this->writeLog("Response", $result);


        try{
            $resultJson = json_decode($result);
            $arr_saleDetails['str_orderId'] = $resultJson->id;
            $arr_saleDetails['str_currentStatus'] = $resultJson->status;

            $arr_saleDetails['str_authorizationId'] = $resultJson->purchase_units[0]->payments->authorizations[0]->id;
            $arr_saleDetails['str_authorizationStatus'] = $resultJson->purchase_units[0]->payments->authorizations[0]->status;

            $arr_saleDetails['str_captureId'] = $resultJson->purchase_units[0]->payments->captures[0]->id;
            $arr_saleDetails['str_captureStatus'] = $resultJson->purchase_units[0]->payments->captures[0]->status;
        }catch (\Exception $e) {
            $arr_saleDetails['str_currentStatus'] = 'payment information could not be read correctly [ppc01]';
        }
        return $arr_saleDetails;
    }
    public function showPaymentDetailsInBackendOrderDetailView($arrOrder = array(), $paymentMethod_moduleReturnData = '') {
        if (!count($arrOrder) || !$paymentMethod_moduleReturnData) {
            return null;
        }
        $outputValue = '';
        $paymentMethod_moduleReturnData = $this->payPalCheckout_updateSaleDetailsInOrderRecord($arrOrder['id']);
        ob_start();
        ?>
        <div class="paymentDetails payPalCheckout">
            <h2>
                <a href="https://www.paypal.com/" target="_blank">
                    <img src="https://www.paypalobjects.com/webstatic/de_DE/i/de-pp-logo-150px.png" border="0" alt="PayPal Logo" />
                </a>
                <?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['headlineBackendDetailsInfo']; ?>
            </h2>
            <div class="content">
                <div class="details">
                    <?php
                    if ($paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg']) {
                        ?>
                        <div class="detailBlock">
                            <div class="detailItem">
                                <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['errorMsgLabel']; ?>:</span>
                                <span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg']; ?></span>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="detailBlock">
                        <div class="detailItem">
                            <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['orderId']; ?>:</span>
                            <span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_orderId']; ?></span>
                        </div>
                        <div class="detailItem">
                            <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['status']; ?>:</span>
                            <span class="value"><?php echo strtoupper($paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus']); ?></span>
                        </div>
                    </div>
                    <div class="detailBlock">
                        <div class="detailItem">
                            <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['authorizationId']; ?>:</span>
                            <span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_authorizationId']; ?></span>
                        </div>
                        <div class="detailItem">
                            <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['authorizationStatus']; ?>:</span>
                            <span class="value"><?php echo strtoupper($paymentMethod_moduleReturnData['arr_saleDetails']['str_authorizationStatus']); ?></span>
                        </div>
                    </div>
                    <div class="detailBlock">
                        <div class="detailItem">
                            <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['captureId']; ?>:</span>
                            <span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_captureId']; ?></span>
                        </div>
                        <div class="detailItem">
                            <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['captureStatus']; ?>:</span>
                            <span class="value"><?php echo strtoupper($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatus']); ?></span>
                        </div>
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
        if (\Input::get('payPalCheckout_updateStatus') && \Input::get('payPalCheckout_updateStatus') == $arrOrder['id']) {
            $this->payPalCheckout_updateSaleDetailsInOrderRecord($arrOrder['id']);
            $this->redirect(ls_shop_generalHelper::getUrl(true, array('payPalCheckout_updateStatus')));
        }
        $outputValue = '';
        $paymentMethod_moduleReturnData = deserialize($paymentMethod_moduleReturnData);
        $str_statusUpdateUrl = ls_shop_generalHelper::getUrl();
        $str_statusUpdateUrl = $str_statusUpdateUrl.(strpos($str_statusUpdateUrl, '?') !== false ? '&' : '?').'payPalCheckout_updateStatus='.$arrOrder['id'].'#payPalCheckout_order'.$arrOrder['id'];
        ob_start();
        ?>
        <div id="payPalCheckout_order<?php echo $arrOrder['id']; ?>" class="paymentStatusInOverview payPalCheckout">
            <img src="https://www.paypalobjects.com/webstatic/de_DE/i/de-pp-logo-100px.png" border="0" alt="PayPal Logo" />
            <div class="content">
                <div class="details">
                    <div class="detailItem">
                        <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['status']; ?>:</span>
                        <span class="value"><?php echo strtoupper($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatus'] ?: $paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus']); ?></span>
                    </div>
                    <div class="detailItem">
                        <span class="label"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_captureId'] ? $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['captureId'] : $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['orderId']; ?>:</span>
                        <span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_captureId'] ?: $paymentMethod_moduleReturnData['arr_saleDetails']['str_orderId']; ?></span>
                    </div>
                </div>
            </div>
            <div class="statusUpdate">
                <a href="<?php echo $str_statusUpdateUrl; ?>"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['updateStatus']; ?></a>
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
    protected function payPalCheckout_checkRelevantCalculationDataHash() {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $arrSessionlsShopPaymentProcess =  $session->get('lsShopPaymentProcess', []);

        $str_relevantCalculationDataHash = sha1(
            ls_shop_cartX::getInstance()->calculation['shippingFee'][0]
            .	ls_shop_cartX::getInstance()->calculation['paymentFee'][0]
            .	ls_shop_cartX::getInstance()->calculation['taxInclusive']
            .	ls_shop_cartX::getInstance()->calculation['invoicedAmount']
            .	ls_shop_cartX::getInstance()->calculation['invoicedAmountNet']
            .	$GLOBALS['TL_CONFIG']['ls_shop_currencyCode']
            .   $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameFirstname'])
            .   $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameLastname'])
            .   $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameStreet'])
            .   $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameCity'])
            .   $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameCountryCode'])
            .   $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNamePostal'])
            .   $this->payPalCheckout_getShippingFieldValue($this->arrCurrentSettings['payPalCheckout_shipToFieldNameState'])
        );

        /*
         * If the relevantCalculationDataHash has not been stored in the session yet, we store it now.
         */
        if (
            !isset($arrSessionlsShopPaymentProcess['payPalCheckout']['relevantCalculationDataHash'])
            ||	!$arrSessionlsShopPaymentProcess['payPalCheckout']['relevantCalculationDataHash']
        ) {
            $arrSessionlsShopPaymentProcess['payPalCheckout']['relevantCalculationDataHash'] = $str_relevantCalculationDataHash;
            $session->set('lsShopPaymentProcess', $arrSessionlsShopPaymentProcess);
        }

        /*
         * If the current relevantCalculationDataHash differs from the one stored in the session we reset the payment
         * session status which will eventually lead to a new pay pal order being created and a possibly existing
         * authorization being voided.
         */
        if ($arrSessionlsShopPaymentProcess['payPalCheckout']['relevantCalculationDataHash'] != $str_relevantCalculationDataHash) {
            $this->payPalCheckout_resetSessionStatus();
        }
    }
    protected function payPalCheckout_check_paymentIsAuthorized() {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $arrSessionlsShopPaymentProcess =  $session->get('lsShopPaymentProcess', []);
        return (
            isset($arrSessionlsShopPaymentProcess['payPalCheckout']['authorized'])
            &&	$arrSessionlsShopPaymentProcess['payPalCheckout']['authorized']
            &&	$arrSessionlsShopPaymentProcess['payPalCheckout']['authorizationId']
            &&	$arrSessionlsShopPaymentProcess['payPalCheckout']['orderId']
        );
    }
    protected function payPalCheckout_showAuthorizationStatus() {
        $obj_template = new \FrontendTemplate('payPalCheckoutCustomUserInterface');
        $obj_template->bln_paymentAuthorized = true;
        return $obj_template->parse();
    }
    protected function payPalCheckout_showPaymentWall() {
        /** @var \PageModel $objPage */
        global $objPage;
        $orderId = $this->payPalCheckout_createOrder();
        $obj_template = new \FrontendTemplate('payPalCheckoutCustomUserInterface');
        $obj_template->clientId = $this->arrCurrentSettings['payPalCheckout_clientID'];
        $obj_template->bln_paymentAuthorized = false;
        $obj_template->orderId = $orderId;
        $obj_template->str_mode = $this->arrCurrentSettings['payPalCheckout_liveMode'] ? 'live' : 'sandbox';
        $obj_template->str_language = $objPage->language;
        return $obj_template->parse();
    }
    protected function payPalCheckout_resetSessionStatus($bln_cancelPossiblyExistingAuthorization = true) {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $arrSessionlsShopPaymentProcess =  $session->get('lsShopPaymentProcess', []);

        if($bln_cancelPossiblyExistingAuthorization && $arrSessionlsShopPaymentProcess['payPalCheckout']['authorizationId']){
            $access_token = $this->payPalCheckout_getaccessToken();
            $authorizationID = $arrSessionlsShopPaymentProcess['payPalCheckout']['authorizationId'];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, ($this->arrCurrentSettings['payPalCheckout_liveMode'] ? self::LIVE_URL : self::SANDBOX_URL).'/v2/payments/authorizations/'.$authorizationID.'/void');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->arrCurrentSettings['payPalCheckout_liveMode'] ? true : false);

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer '.$access_token;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            $result = curl_exec($ch);

            $this->writeLog("Request", curl_getinfo($ch)['request_header']);

            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);

            $this->writeLog("Response", $result);
            $this->setPaymentMethodErrorMessage($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['authorizationObsolete']);
        }


        $arrSessionlsShopPaymentProcess['payPalCheckout'] = array(
            'authorized' => false,
            'authorizationId' => null,
            'orderId' => null,
            'relevantCalculationDataHash' => null
        );
        $session->set('lsShopPaymentProcess', $arrSessionlsShopPaymentProcess);
    }
    protected function payPalCheckout_getShippingFieldValue($str_fieldName) {
        $str_valueWildcardPattern = '/(?:#|&#35;){2}value::(.*)(?:#|&#35;){2}/';
        if (preg_match($str_valueWildcardPattern, $str_fieldName, $arr_matches)) {
            $str_fieldName = preg_replace($str_valueWildcardPattern, $this->payPalCheckout_getShippingFieldValue($arr_matches[1]), $str_fieldName);
        }
        $arrCheckoutFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'];
        if(isset($arrCheckoutFormFields['useDeviantShippingAddress']['value']) && $arrCheckoutFormFields['useDeviantShippingAddress']['value'] == "1"){
            $str_fieldName = $str_fieldName.'_alternative';
        }
        return $arrCheckoutFormFields[$str_fieldName]['value'];
    }
}
?>
