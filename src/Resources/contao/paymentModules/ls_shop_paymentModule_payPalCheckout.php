<?php
namespace Merconis\Core;
use Contao\Controller;
use Contao\Database;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;
use function LeadingSystems\Helpers\lsDebugLog;

class ls_shop_paymentModule_payPalCheckout extends ls_shop_paymentModule_standard {
    const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';
    const LIVE_URL = 'https://api-m.paypal.com';
    const VALID_CAPTURESTATUSDETAILS = ['PENDING_REVIEW','ECHECK','INTERNATIONAL_WITHDRAWAL'];

    public $arrCurrentSettings = array();

    private $arrPastLogs = [];
    private $isError =  false;

    public function initialize($specializedManually = false) {
        $this->arrPastLogs = [];
        $this->isError = false;
    }


    function __destruct() {

        //if there is an error and logMode = ERROR, Every Past Request gets logged
        if($this->arrCurrentSettings['payPalCheckout_logMode'] == 'ERROR' && $this->isError){

            $arrPastLogs = $this->arrPastLogs;

            foreach ($arrPastLogs as $arrLog){
                //log mode needs to be bypassed because logMode ERROR normaly dont get logged
                $this->writeLog($arrLog[0], $arrLog[1], "",true);
            }
        }
    }


    public function writeLog($outputType, $output, $logModeInfoText, $bypassLogMode = false){

        if($bypassLogMode == false){

            //the log mode info is checked beforehand and $bypassLogMode=true must be used to log it
            if($this->arrCurrentSettings['payPalCheckout_logMode'] == 'ERROR'){
                $this->arrPastLogs[] = [$outputType, $output];
                return;
            }

            if($this->arrCurrentSettings['payPalCheckout_logMode'] == 'NONE') {
                return;
            }
        }

        //Request Data will not get logged on INFO logMode only Request and Response Header
        if($this->arrCurrentSettings['payPalCheckout_logMode'] == 'INFO' && $outputType == 'Request Data'){
            return;
        }

        if($this->arrCurrentSettings['payPalCheckout_logMode'] == 'INFO'){
            $output = $logModeInfoText."\n".$output;
        }

        $str_filename = 'payPalCheckout_'.$this->arrCurrentSettings['payPalCheckout_logMode'].'_'.date("Y-m-d").'.log';
        lsDebugLog($output, "[".date("d-m-Y h:i:sa")."] [".$outputType."]", 'regular', false, '', false, $str_filename);

    }


    public function checkoutFinishAllowed() {
        return true;
    }

    public function statusOkayToRedirectToCheckoutFinish() {
        return true;
    }

    private function payPalCheckout_getBaseUrl($isLiveMode) {
        return $isLiveMode ? self::LIVE_URL : self::SANDBOX_URL;
    }

    private function payPalCheckout_fetchAccessToken($clientId, $clientSecret, $isLiveMode) {
        $baseUrl = $this->payPalCheckout_getBaseUrl($isLiveMode);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl.'/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $isLiveMode ? true : false);
        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $result = curl_exec($ch);

        $this->writeLog("Request", curl_getinfo($ch)['request_header'],'Send Request to create a new Access Token');

        $objResonse = json_decode($result);
        $accessToken = $objResonse->access_token;

        if(isset($accessToken)){
            $this->writeLog("Response", $result, 'A new AccessToken was created ('.$accessToken.')' );
        }else{
            $this->writeLog("Response", $result, 'There was an Error creating a new AccessToken');
            $this->isError = true;
        }


        if (curl_errno($ch)) {
            $this->writeLog('curlError', curl_error($ch), 'cURL error while requesting access token');
            $this->isError = true;
        }
        curl_close($ch);

        return $accessToken;
    }

    private function payPalCheckout_getaccessToken(){
        return $this->payPalCheckout_fetchAccessToken(
            $this->arrCurrentSettings['payPalCheckout_clientID'],
            $this->arrCurrentSettings['payPalCheckout_clientSecret'],
            $this->arrCurrentSettings['payPalCheckout_liveMode']
        );
    }

    public function afterCheckoutFinish($orderIdInDb = 0, $order = array(), $afterCheckoutUrl = '', $oix = '') {
        return '';
    }

    public function check_usePaymentAfterCheckoutPage($orderIdInDb = 0, $order = array())
    {
        return ($order['invoicedAmount'] ?? 0) > 0;
    }

    public function getPaymentInfo() {
        $arrPaymentInfo = array(
            'str_orderId' => '',
            'arr_saleDetails' => array(
                'str_orderId' => '',
                'str_currentStatus' => '',
                'str_captureId' => '',
                'str_captureStatus' => '',
                'str_captureStatusDetails' => '',
                'str_errorMsg' => ''
            )
        );

        return serialize($arrPaymentInfo);
    }

    protected function payPalCheckout_updateSaleDetailsInOrderRecord($int_orderIdInDb, $payPalCheckout_orderId = null, $paymentMethod_moduleReturnData = null)
    {
        if (!$int_orderIdInDb) {
            return null;
        }

        if (!is_array($paymentMethod_moduleReturnData)) {
            $paymentMethod_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId($int_orderIdInDb);
        }

        if (!is_array($paymentMethod_moduleReturnData)) {
            $paymentMethod_moduleReturnData = [];
        }

        if (!isset($paymentMethod_moduleReturnData['arr_saleDetails']) || !is_array($paymentMethod_moduleReturnData['arr_saleDetails'])) {
            $paymentMethod_moduleReturnData['arr_saleDetails'] = [
                'str_orderId' => '',
                'str_currentStatus' => '',
                'str_captureId' => '',
                'str_captureStatus' => '',
                'str_captureStatusDetails' => '',
                'str_errorMsg' => ''
            ];
        }

        if (!empty($paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg'])) {
            // Do not override an existing error message by fetching fresh status data
            return $paymentMethod_moduleReturnData;
        }

        // PayPal Order ID determine
        if (!$payPalCheckout_orderId) {
            $payPalCheckout_orderId = $paymentMethod_moduleReturnData['str_orderId'] ?? null;
        }
        if (!$payPalCheckout_orderId) {
            $payPalCheckout_orderId = Database::getInstance()
                ->prepare("SELECT payPalCheckout_orderId FROM tl_ls_shop_orders WHERE id=?")
                ->limit(1)
                ->execute($int_orderIdInDb)
                ->payPalCheckout_orderId;
        }
        if (!$payPalCheckout_orderId) {
            return $paymentMethod_moduleReturnData;
        }

        $paymentMethod_moduleReturnData['str_orderId'] = $payPalCheckout_orderId;
        $paymentMethod_moduleReturnData['arr_saleDetails'] = $this->payPalCheckout_getSaleDetailsForOrderId($payPalCheckout_orderId);

        // Persist moduleReturnData (primary source of truth for PayPal Checkout)
        $this->update_paymentMethod_moduleReturnData_inOrder($int_orderIdInDb, $paymentMethod_moduleReturnData);

        // Also update dedicated fields for filtering/searching
        $this->update_fieldValue_inOrder($int_orderIdInDb, 'payPalCheckout_orderId', $paymentMethod_moduleReturnData['arr_saleDetails']['str_orderId']);
        $this->update_fieldValue_inOrder($int_orderIdInDb, 'payPalCheckout_currentStatus', $paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatus'] ?: $paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus']);

        return $paymentMethod_moduleReturnData;
    }

    protected function payPalCheckout_getSaleDetailsForOrderId($str_orderId) {
        $arr_saleDetails = array(
            'str_orderId' => '',
            'str_currentStatus' => '',
            'str_captureId' => '',
            'str_captureStatus' => '',
            'str_captureStatusDetails' => '',
            'str_errorMsg' => ''
        );

        if (!$str_orderId) {
            return $arr_saleDetails;
        }

        $access_token = $this->payPalCheckout_getaccessToken();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ($this->arrCurrentSettings['payPalCheckout_liveMode'] ? self::LIVE_URL : self::SANDBOX_URL) . '/v2/checkout/orders/' . $str_orderId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->arrCurrentSettings['payPalCheckout_liveMode'] ? true : false);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);
        $this->writeLog("Request", curl_getinfo($ch)['request_header'], 'Try To get Details For Order Id: ' . $str_orderId);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $resultJson = json_decode($result);

        if (isset($resultJson->id) && isset($resultJson->status)) {
            $this->writeLog("Response", $result, 'Got Order Data orderId:' . $resultJson->id . ' current Status:' . $resultJson->status);
        } else {
            $this->writeLog("Response", $result, 'There was an error getting payment informations');
            $this->isError = true;
        }

        try {
            $arr_saleDetails['str_orderId'] = $resultJson->id ?? '';
            $arr_saleDetails['str_currentStatus'] = $resultJson->status ?? '';

            $capture = $resultJson->purchase_units[0]->payments->captures[0] ?? null;
            if ($capture) {
                $arr_saleDetails['str_captureId'] = $capture->id ?? '';
                $arr_saleDetails['str_captureStatus'] = $capture->status ?? '';
                $arr_saleDetails['str_captureStatusDetails'] = $capture->status_details->reason ?? '';
            }
        } catch (\Exception $e) {
            $arr_saleDetails['str_currentStatus'] = 'payment information could not be read correctly [ppc01]';
        }

        return $arr_saleDetails;
    }


    public function showPaymentDetailsInBackendOrderDetailView($arrOrder = array(), $paymentMethod_moduleReturnData = '') {
        if (!count($arrOrder) || !$paymentMethod_moduleReturnData) {
            return null;
        }

        $paymentMethod_moduleReturnData = StringUtil::deserialize($paymentMethod_moduleReturnData, true);

        if (Input::get('payPalCheckout_updateStatus') && Input::get('payPalCheckout_updateStatus') == $arrOrder['id']) {
            $paymentMethod_moduleReturnData = $this->payPalCheckout_updateSaleDetailsInOrderRecord($arrOrder['id'], null, $paymentMethod_moduleReturnData);
            $this->redirect(ls_shop_generalHelper::getUrl(true, array('payPalCheckout_updateStatus')));
        }

        $paymentMethod_moduleReturnData = $this->payPalCheckout_updateSaleDetailsInOrderRecord($arrOrder['id'], null, $paymentMethod_moduleReturnData);

        ob_start();
        ?>
        <div class="paymentDetails payPalCheckout<?php echo ($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatus']) == 'COMPLETED' ? ' paypal-capture-status-completed' : (in_array(($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatusDetails']), self::VALID_CAPTURESTATUSDETAILS) ? ' paypal-capture-status-pending paypal-capture-status-details-valid' : ' paypal-capture-status-pending paypal-capture-status-details-invalid') ?>">
            <div class="paymentProviderLink">
                <a href="https://www.paypal.com/" target="_blank" rel="noopener noreferrer">
                    <img src="https://www.paypalobjects.com/webstatic/de_DE/i/de-pp-logo-150px.png" alt="PayPal Logo" />
                </a>
            </div>
            <h3>
                <?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['headlineBackendDetailsInfo']; ?>
            </h3>
            <div class="content">
                <div class="details">
                    <?php if (!empty($paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg'])) { ?>
                        <div class="detailBlock">
                            <div class="detailItem">
                                <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['errorMsgLabel']; ?>:</span>
                                <span class="value"><?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg']; ?></span>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="detailBlock">
                        <div class="detailItem">
                            <span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['status']; ?>:</span>
                            <span class="value"><?php echo strtoupper(($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatus'] ?? '') ?: ($paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus'] ?? '')); ?></span>
                        </div>
                        <div class="detailItem">
                            <span class="label"><?php echo !empty($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureId']) ? $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['captureId'] : $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['orderId']; ?>:</span>
                            <span class="value"><?php echo !empty($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureId']) ? $paymentMethod_moduleReturnData['arr_saleDetails']['str_captureId'] : ($paymentMethod_moduleReturnData['arr_saleDetails']['str_orderId'] ?? ''); ?></span>
                        </div>
                    </div>
                </div>
                <?php if($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatus'] != 'COMPLETED') { ?>
                    <div class="payment-provider-message">
                        <?php if (in_array(($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatusDetails']), self::VALID_CAPTURESTATUSDETAILS)) { ?>
                            <span><?php echo sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['captureStatusDetailsValid'], $paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatusDetails']); ?></span>
                        <?php } else { ?>
                            <span><?php echo sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['captureStatusDetailsInvalid'], $paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatusDetails']); ?></span>
                        <?php } ?>
                    </div>
                <?php } ?>
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

        $paymentMethod_moduleReturnData = StringUtil::deserialize($paymentMethod_moduleReturnData, true);

        if (Input::get('payPalCheckout_updateStatus') && Input::get('payPalCheckout_updateStatus') == $arrOrder['id']) {
            $paymentMethod_moduleReturnData = $this->payPalCheckout_updateSaleDetailsInOrderRecord($arrOrder['id'], null, $paymentMethod_moduleReturnData);
            $this->redirect(ls_shop_generalHelper::getUrl(true, array('payPalCheckout_updateStatus')));
        }

        $paymentMethod_moduleReturnData = $this->payPalCheckout_updateSaleDetailsInOrderRecord($arrOrder['id'], null, $paymentMethod_moduleReturnData);

        $str_statusUpdateUrl = ls_shop_generalHelper::getUrl();
        $str_statusUpdateUrl = $str_statusUpdateUrl.(strpos($str_statusUpdateUrl, '?') !== false ? '&' : '?').'payPalCheckout_updateStatus='.$arrOrder['id'].'#payPalCheckout_order'.$arrOrder['id'];

        ob_start();
        ?>
        <div id="payPalCheckout_order<?php echo $arrOrder['id']; ?>" class="paymentStatusInOverview payPalCheckout<?php echo $paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatus'] == 'COMPLETED' ? ' paypal-capture-status-completed' : (in_array($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatusDetails'], self::VALID_CAPTURESTATUSDETAILS) ? ' paypal-capture-status-pending paypal-capture-status-details-valid' : ' paypal-capture-status-pending paypal-capture-status-details-invalid') ?>">
            <img src="https://www.paypalobjects.com/webstatic/de_DE/i/de-pp-logo-100px.png" alt="PayPal Logo" />
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

                <?php if($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatus'] != 'COMPLETED'){ ?>
                <div class="payment-provider-message">
                    <?php if(in_array($paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatusDetails'], self::VALID_CAPTURESTATUSDETAILS)){ ?>
                        <span><?php echo sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['captureStatusDetailsValid'], $paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatusDetails']) ?></span>
                    <?php }else{ ?>
                        <span><?php echo sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['captureStatusDetailsInvalid'], $paymentMethod_moduleReturnData['arr_saleDetails']['str_captureStatusDetails']) ?></span>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
            <div class="statusUpdate">
                <a href="<?php echo $str_statusUpdateUrl; ?>"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['updateStatus']; ?></a>
            </div>
        </div>
        <?php
        $outputValue = ob_get_clean();
        return $outputValue;
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


    public function specialInfoForPaymentMethodAfterCheckoutFinish() {

        $this->payPalCheckout_captureOrder();
    }

    function payPalCheckout_captureOrder() {

        /*
         * Capture based on oix
         */
        $oix = Input::get('oix');
        if (!$oix) {
            return false;
        }

        $db = Database::getInstance();
        $orderIdInDb = ls_shop_generalHelper::decodeOix($oix);
        if (!$orderIdInDb) {
            return false;
        }

        $orderRow = $db->prepare("SELECT * FROM tl_ls_shop_orders WHERE id = ?")
            ->limit(1)
            ->execute($orderIdInDb)
            ->fetchAssoc();

        if (!$orderRow) {
            return false;
        }

        // PayPal OrderID aus DB
        $orderID = $orderRow['payPalCheckout_orderId'];

        if (!$orderID) {
            return false;
        }

        $obj_paymentModule = ls_shop_paymentModule::getInstance();
        $arr_settings = $obj_paymentModule->settings;

        $clientId = $arr_settings['payPalCheckout_clientID'];
        $clientSecret = $arr_settings['payPalCheckout_clientSecret'];

        $baseUrl = $this->payPalCheckout_getBaseUrl($arr_settings['payPalCheckout_liveMode']);

        $accessToken = $this->payPalCheckout_fetchAccessToken($clientId, $clientSecret, $arr_settings['payPalCheckout_liveMode']);
        if(!$accessToken){
            return false;
        }

        // Capture durchführen
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$baseUrl/v2/checkout/orders/$orderID/capture");
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $accessToken"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $arr_settings['payPalCheckout_liveMode'] ? true : false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $captureResult = curl_exec($ch);
        $this->writeLog("Request", curl_getinfo($ch)['request_header'], 'Try to capture payment');

        curl_close($ch);

        $capture = json_decode($captureResult);

        if ($capture) {
            $this->writeLog("Response", $captureResult, 'capturing payment result');
        } else {
            $this->writeLog("Response", $captureResult, 'There was an Error capturing the payment');
            $this->isError = true;
        }

        $captureErrorMsg = '';
        if (is_object($capture) && (isset($capture->name) || isset($capture->message) || isset($capture->debug_id))) {
            $captureErrorMsgParts = [];
            if (isset($capture->name) || isset($capture->message)) {
                $captureErrorMsgParts[] = trim((string) ($capture->name ?? '') . ': ' . (string) ($capture->message ?? ''));
            }

            if (isset($capture->details) && is_array($capture->details)) {
                foreach ($capture->details as $detail) {
                    if (!is_object($detail)) {
                        continue;
                    }

                    $issue = (string) ($detail->issue ?? '');
                    $description = (string) ($detail->description ?? '');
                    $issueLine = trim($issue . ($description ? ' - ' . $description : ''));

                    if ($issueLine !== '') {
                        $captureErrorMsgParts[] = $issueLine;
                    }
                }
            }

            if (isset($capture->debug_id) && (string) $capture->debug_id !== '') {
                $captureErrorMsgParts[] = 'debug_id: ' . (string) $capture->debug_id;
            }

            $captureErrorMsg = trim(implode(' | ', array_filter($captureErrorMsgParts)));
        }

        $captureDetails = $capture->purchase_units[0]->payments->captures[0] ?? null;
        $captureStatus = $captureDetails->status ?? '';
        $captureId = $captureDetails->id ?? '';
        $captureStatusDetails = $captureDetails->status_details->reason ?? '';

        $paymentMethod_moduleReturnData = $this->get_paymentMethod_moduleReturnData_forOrderId((int) $orderRow['id']);
        if (!is_array($paymentMethod_moduleReturnData)) {
            $paymentMethod_moduleReturnData = [];
        }
        $paymentMethod_moduleReturnData['str_orderId'] = $orderID;
        $paymentMethod_moduleReturnData['arr_saleDetails'] = array_merge(
            $paymentMethod_moduleReturnData['arr_saleDetails'] ?? [],
            [
                'str_orderId' => $orderID,
                'str_currentStatus' => $capture->status ?? ($captureErrorMsg ? 'ERROR' : ($paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus'] ?? '')),
                'str_captureId' => $captureId,
                'str_captureStatus' => $captureStatus,
                'str_captureStatusDetails' => $captureStatusDetails,
                'str_errorMsg' => $captureErrorMsg ?: ($paymentMethod_moduleReturnData['arr_saleDetails']['str_errorMsg'] ?? '')
            ]
        );

        $this->update_paymentMethod_moduleReturnData_inOrder((int) $orderRow['id'], $paymentMethod_moduleReturnData);
        $this->update_fieldValue_inOrder((int) $orderRow['id'], 'payPalCheckout_currentStatus', $captureStatus ?: ($captureErrorMsg ? 'ERROR' : ($paymentMethod_moduleReturnData['arr_saleDetails']['str_currentStatus'] ?? '')));

        if ($this->payPalCheckout_checkIfOrderValidFromCapture($capture)) {

            $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish']
                = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['paymentSuccessAfterFinishedOrder'];

            return true;
        }

        // Something went wrong
        $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish']
            = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['payPalCheckout']['paymentErrorAfterFinishedOrder'];

        return false;


    }

    protected function payPalCheckout_checkIfOrderValidFromCapture($capture) {

        if (!$capture || empty($capture->purchase_units)) {
            return false;
        }

        $captureDetails = $capture->purchase_units[0]->payments->captures[0] ?? null;

        if (!$captureDetails) {
            return false;
        }

        if ($captureDetails->status === 'COMPLETED') {
            return true;
        }

        if ($captureDetails->status === 'PENDING') {

            $reason = $captureDetails->status_details->reason ?? '';

            if (in_array($reason, self::VALID_CAPTURESTATUSDETAILS, true)) {
                return true;
            }
        }

        return false;
    }

    public function getCheckoutJavascript() {

        $objJsTemplate = new FrontendTemplate('template_payPalCheckout');
        return $objJsTemplate->parse();
    }

}
?>


