<?php

namespace Merconis\Core;

class ls_shop_paymentModule_payPalCheckout extends ls_shop_paymentModule_standard {


    const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';
    const LIVE_URL = 'https://api-m.paypal.com';

    public $arrCurrentSettings = array();

    public function initialize($specializedManually = false) {
        if (!isset($_SESSION['lsShopPaymentProcess']['payPalCheckout']) || !is_array($_SESSION['lsShopPaymentProcess']['payPalCheckout'])) {
            $this->payPalCheckout_resetSessionStatus();
        }

        $this->payPalCheckout_checkRelevantCalculationDataHash();
    }

    public function statusOkayToShowCustomUserInterface() {
        return ls_shop_cartX::getInstance()->calculation['invoicedAmount'] > 0 ? true : false;
    }

    public function getCustomUserInterface() {

        if(\Input::post('payPalCheckout_reset')){
            $this->payPalCheckout_resetSessionStatus();
            \Controller::reload();
        }

        if (\Input::post('payPalCheckout_orderId') && \Input::post('payPalCheckout_authorizationId')) {
            $_SESSION['lsShopPaymentProcess']['payPalCheckout']['orderId'] = \Input::post('payPalCheckout_orderId');
            $_SESSION['lsShopPaymentProcess']['payPalCheckout']['authorizationId'] = \Input::post('payPalCheckout_authorizationId');
            $_SESSION['lsShopPaymentProcess']['payPalCheckout']['authorized'] = true;
            \Controller::reload();
        }

        if ($this->payPalCheckout_check_paymentIsAuthorized()) {
            return $this->payPalCheckout_showAuthorizationStatus();
        } else {
            return $this->payPalCheckout_showPaymentWall();
        }
    }

    private function payPalCheckout_createOrder(){
        $access_token = $this->payPalCheckout_getaccessToken();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, ($this->arrCurrentSettings['payPalCheckout_liveMode'] ? self::LIVE_URL : self::SANDBOX_URL).'/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->arrCurrentSettings['payPalCheckout_liveMode'] ? true : false);

        $totalvalue = 0;
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

            $totalvalue =+ ($quantity * $price);

            $itemlist[] = [
                "name"=> $name,
                "unit_amount"=> [
                    "currency_code"=> $currency_code,
                    "value"=> $price
                ],
                "quantity"=> strval($quantity)
            ];
        }

        $discount = 0;

        //discount must be positiv not negativ thats why (-1)*
        foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $arr_couponValue) {
            $discount = (-1)*number_format($arr_couponValue[0], 2, '.', '');
        }


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

        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode([
            "intent" => "AUTHORIZE",
                "application_context"=> [
                    //"brand_name"=> 'myBrand',
                    "shipping_preference"=> 'SET_PROVIDED_ADDRESS',
            ],
            "purchase_units" =>  [
                [
                    "amount"=> [
                        "currency_code"=> $currency_code,
                        "value"=>  strval($totalvalue - $discount),
                        "breakdown"=> [
                            "item_total"=> [
                                "currency_code"=> $currency_code,
                                "value"=> strval($totalvalue)
                            ],
                            "discount"=> [
                                "currency_code"=> $currency_code,
                                "value"=> strval($discount)
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
        ]));

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer '.$access_token;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $orderId = json_decode($result)->id;

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

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result)->access_token;
    }

    public function afterCheckoutFinish($orderIdInDb = 0, $order = array(), $afterCheckoutUrl = '', $oix = '') {


        $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = '';

        $access_token = $this->payPalCheckout_getaccessToken();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, ($this->arrCurrentSettings['payPalCheckout_liveMode'] ? self::LIVE_URL : self::SANDBOX_URL).'/v2/payments/authorizations/'. $_SESSION['lsShopPaymentProcess']['payPalCheckout']['authorizationId'] .'/capture');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->arrCurrentSettings['payPalCheckout_liveMode'] ? true : false);


        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer '.$access_token;
        //$headers[] = 'PayPal-Mock-Response: {"mock_application_codes": "PAYER_CANNOT_PAY"}';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $status = json_decode($result)->status;

        try {
            if($status == "COMPLETED"){
                // write the success message to the special payment info
                $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['paymentSuccessAfterFinishedOrder'];
            }else{
                // write the error message to the special payment info -> order is completet but payment is incomplete
                $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['paymentErrorAfterFinishedOrder'];
            }
            //egal ob es schief läuft oder nicht oder immer abspeichern
            $this->payPalCheckout_updateSaleDetailsInOrderRecord($orderIdInDb);
            $this->payPalCheckout_resetSessionStatus();

        } catch (\Exception $e) {

            $this->logPaymentError(__METHOD__, $e->getMessage());

            $paymentMethod_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($orderIdInDb);
            $paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus'] = 'Payment module error (see order details)';
            $paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg'] = $e->getMessage().' ERROR DATA: '.json_encode($e->getData());

            $this->payPalCheckout_resetSessionStatus();
        }
    }

    public function afterPaymentMethodSelection() {
        $this->payPalCheckout_resetSessionStatus();
    }

    public function getPaymentInfo() {

        $arrPaymentInfo = array(
            'str_authorizationId' => $_SESSION['lsShopPaymentProcess']['payPalCheckout']['authorizationId'],
            'str_orderId' => $_SESSION['lsShopPaymentProcess']['payPalCheckout']['orderId'],
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
            'str_currentStatus' => ''
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

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);


        try{
            $resultJson = json_decode($result);
            $arr_saleDetails['str_currentStatus'] = $resultJson->purchase_units[0]->payments->authorizations[0]->status;
            $arr_saleDetails['str_orderId'] = $resultJson->id;
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
                    <div class="detailItem">
                        <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['status']; ?>:</span>
                        <span class="value"><?php echo strtoupper($paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus']); ?></span>
                    </div>
                    <?php
                    if ($paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg']) {
                        ?>
                        <div class="detailItem">
                            <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['errorMsgLabel']; ?>:</span>
                            <span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg']; ?></span>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="detailItem">
                        <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['orderId']; ?>:</span>
                        <span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_orderId']; ?></span>
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
                        <span class="value"><?php echo strtoupper($paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus']); ?></span>
                    </div>
                    <div class="detailItem">
                        <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['orderId']; ?>:</span>
                        <span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_orderId']; ?></span>
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
            !$this->payPalCheckout_check_paymentIsAuthorized()
            ||	!isset($_SESSION['lsShopPaymentProcess']['payPalCheckout']['relevantCalculationDataHash'])
            ||	!$_SESSION['lsShopPaymentProcess']['payPalCheckout']['relevantCalculationDataHash']
        ) {
            $_SESSION['lsShopPaymentProcess']['payPalCheckout']['relevantCalculationDataHash'] = $str_relevantCalculationDataHash;
        }

        /*
         * But if the payment has already been authorized and the relevantCalculationDataHash
         * is stored in the session, we compare the hash to the current hash and
         * if it differs, we reset the payment status and display a message.
         */
        else if ($_SESSION['lsShopPaymentProcess']['payPalCheckout']['relevantCalculationDataHash'] != $str_relevantCalculationDataHash) {
            $this->setPaymentMethodErrorMessage($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['authorizationObsolete']);
            $this->payPalCheckout_resetSessionStatus();
        }
    }

    protected function payPalCheckout_check_paymentIsAuthorized() {

        return (
            isset($_SESSION['lsShopPaymentProcess']['payPalCheckout']['authorized'])
            &&	$_SESSION['lsShopPaymentProcess']['payPalCheckout']['authorized']
            &&	$_SESSION['lsShopPaymentProcess']['payPalCheckout']['authorizationId']
            &&	$_SESSION['lsShopPaymentProcess']['payPalCheckout']['orderId']
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

    protected function payPalCheckout_resetSessionStatus() {
        $_SESSION['lsShopPaymentProcess']['payPalCheckout'] = array(
            'authorized' => false,
            'authorizationId' => null,
            'orderId' => null,
            'relevantCalculationDataHash' => null
        );
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