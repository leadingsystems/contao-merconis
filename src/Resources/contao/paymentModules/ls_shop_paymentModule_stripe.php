<?php
namespace Merconis\Core;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Database;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use Stripe\PaymentIntent;
use function LeadingSystems\Helpers\lsDebugLog;

class ls_shop_paymentModule_stripe extends ls_shop_paymentModule_standard {

    public $arrCurrentSettings = array();

    /**
     * Stripe requires a minimum amount. If this method is not present, the payment module fallback is 0.
     */
    public function getMinimumValueOfGoods(): float {
        return 0.5;
    }

    public static function getStripePaymentMethodOptions(DataContainer $dc): array
    {
        $availableOptions = [
            'card' => 'card',
            'google_pay' => 'google_pay',
            'apple_pay' => 'apple_pay',

            'acss_debit' => 'acss_debit',
            'affirm' => 'affirm',
            'afterpay_clearpay' => 'afterpay_clearpay',
            'alipay' => 'alipay',
            'alma' => 'alma',
            'amazon_pay' => 'amazon_pay',
            'au_becs_debit' => 'au_becs_debit',
            'bacs_debit' => 'bacs_debit',
            'bancontact' => 'bancontact',
            'billie' => 'billie',
            'blik' => 'blik',
            'boleto' => 'boleto',
            'cashapp' => 'cashapp',
            'crypto' => 'crypto',
            'custom' => 'custom',
            'customer_balance' => 'customer_balance',
            'eps' => 'eps',
            'fpx' => 'fpx',
            'giropay' => 'giropay',
            'grabpay' => 'grabpay',
            'ideal' => 'ideal',
            'interac_present' => 'interac_present',
            'kakao_pay' => 'kakao_pay',
            'klarna' => 'klarna',
            'konbini' => 'konbini',
            'kr_card' => 'kr_card',
            'link' => 'link',
            'mb_way' => 'mb_way',
            'mobilepay' => 'mobilepay',
            'multibanco' => 'multibanco',
            'naver_pay' => 'naver_pay',
            'nz_bank_account' => 'nz_bank_account',
            'oxxo' => 'oxxo',
            'p24' => 'p24',
            'pay_by_bank' => 'pay_by_bank',
            'payco' => 'payco',
            'paynow' => 'paynow',
            'paypal' => 'paypal',
            'paypay' => 'paypay',
            'payto' => 'payto',
            'pix' => 'pix',
            'promptpay' => 'promptpay',
            'revolut_pay' => 'revolut_pay',
            'samsung_pay' => 'samsung_pay',
            'satispay' => 'satispay',
            'sepa_debit' => 'sepa_debit',
            'sofort' => 'sofort',
            'swish' => 'swish',
            'twint' => 'twint',
            'us_bank_account' => 'us_bank_account',
            'wechat_pay' => 'wechat_pay',
            'zip' => 'zip',
        ];

        if (isset($GLOBALS['MERCONIS_HOOKS']['modifyStripePaymentMethodOptions']) && is_array($GLOBALS['MERCONIS_HOOKS']['modifyStripePaymentMethodOptions'])) {
            foreach (ls_shop_generalHelper::getSortedMerconisHookCallbacks('modifyStripePaymentMethodOptions') as $mccb) {
                $objMccb = System::importStatic($mccb[0]);
                $availableOptions = $objMccb->{$mccb[1]}($availableOptions, $dc);
            }
        }

        return $availableOptions;
    }

    public static function getStripePaymentMapping(): array
    {
        $availableOptions = [];

        if (isset($GLOBALS['MERCONIS_HOOKS']['modifyStripePaymentMapping']) && is_array($GLOBALS['MERCONIS_HOOKS']['modifyStripePaymentMapping'])) {
            foreach (ls_shop_generalHelper::getSortedMerconisHookCallbacks('modifyStripePaymentMapping') as $mccb) {
                $objMccb = System::importStatic($mccb[0]);
                $availableOptions = $objMccb->{$mccb[1]}($availableOptions);
            }
        }

        return $availableOptions;
    }

    /**
     * Maps a Merconis Stripe payment method selection (e.g. "google_pay") to the Stripe payment behaviour.
     *
     * Merconis hook:
     * - $GLOBALS['MERCONIS_HOOKS']['modifyStripePaymentBehaviour']
     *   Signature: function(array $paymentBehaviour, array $settings, array $paymentInfo): array
     *
     * @param string $selection
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $paymentInfo
     *
     * @return array<string, mixed>
     */
    public static function getStripePaymentBehaviour(string $selection, array $settings = []): array
    {
        $paymentMethodOptions = self::getStripePaymentMapping();

        $selectionType = $paymentMethodOptions[$selection] ?? $selection;

        $paymentBehaviour = [
            'stripeType' => $selectionType,
            'stripeSelection' => $selection,
            'stripeElementOptions' => [],
            'stripeCreatePaymentOptions' => [],
        ];

        if (isset($GLOBALS['MERCONIS_HOOKS']['modifyStripePaymentBehaviour']) && is_array($GLOBALS['MERCONIS_HOOKS']['modifyStripePaymentBehaviour'])) {
            foreach (ls_shop_generalHelper::getSortedMerconisHookCallbacks('modifyStripePaymentBehaviour') as $mccb) {
                $objMccb = System::importStatic($mccb[0]);
                $paymentBehaviour = $objMccb->{$mccb[1]}($paymentBehaviour, $settings);
            }
        }

        return $paymentBehaviour;
    }


    public function initialize($specializedManually = false) {

        $session = System::getContainer()->get('merconis.session')->getSession();
        $sessionKey = 'stripe_payment_provider';
        $arr_stripe_session = $session->get($sessionKey);

        if (!isset($arr_stripe_session) || !is_array($arr_stripe_session)) {
            $this->stripeCheckout_resetSessionStatus();
        }
        $this->stripeCheckout_checkRelevantCalculationDataHash();
    }
    public function statusOkayToShowCustomUserInterface() {

        return ls_shop_cartX::getInstance()->calculation['invoicedAmount'] > 0 ? true : false;
    }

    function __destruct() {
    }


    public function getCustomUserInterface() {
        return $this->stripeCheckout_showPaymentWall();
    }


    public function checkoutFinishAllowed() {
        return $this->stripeCheckout_check_paymentIsAuthorized();
    }

    // Ist dafür zuständig das beim auswählen von stripe weiter geklickt werden kann, hier passiert noch keine prüfung deswegen immer true
    public function statusOkayToRedirectToCheckoutFinish() {
        return $this->stripeCheckout_check_paymentIsAuthorized();
    }

    //wird aufgerufen sobald order durch ist nicht sobald die zahlung durch ist
    public function afterCheckoutFinish($orderIdInDb = 0, $order = array(), $afterCheckoutUrl = '', $oix = '') {

        $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['stripe']['paymentErrorAfterFinishedOrder'];
    }
    public function afterPaymentMethodSelection() {
        $this->stripeCheckout_resetSessionStatus();
    }
    public function getPaymentInfo() {

        $settings = $this->arrCurrentSettings;

        $paymentMethodSelection = (string) ($this->arrCurrentSettings['stripe_paymentMethods'] ?? '');
        $stripePaymentBehaviour = self::getStripePaymentBehaviour($paymentMethodSelection, $settings);

        $arrPaymentInfo = array(
            'stripeType' => $stripePaymentBehaviour['stripeType'],
            'stripeSelection' => $stripePaymentBehaviour['stripeSelection'],

            'billing_details' => [
                "address" => [
                    "city" => $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameCity']),
                    "country" => $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameCountryCode']),
                    "line1" => $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameStreet']),
                    "line2" => $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameAddressLine2']),
                    "postal_code" => $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNamePostal']),
                    "state" => $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameState'])
                ],
                "phone" => $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNamePhone']),
                "email"=> $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameEMail']),
                "name"=> $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameFirstname']). " ". $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameLastname']),
            ]
        );
        return $arrPaymentInfo;
    }


    public function createPaymentIntent() {


        return "";
    }


    public function showPaymentDetailsInBackendOrderDetailView($arrOrder = array(), $paymentMethod_moduleReturnData = '') {

        $stripe_paymentIntent = $arrOrder['stripe_paymentIntent'];
        $paymentMethod_id = $arrOrder['paymentMethod_id'];

        // Falls kein PaymentIntent vorhanden ist, nichts anzeigen
        if (!$stripe_paymentIntent || !$paymentMethod_id) {
            return '';
        }

        // Stripe-Schlüssel aus der Datenbank holen
        $db = Database::getInstance();
        $objPaymentMethod = $db->prepare("
            SELECT stripe_privateKey, stripe_publicKey 
            FROM tl_ls_shop_payment_methods 
            WHERE id = ?
        ")->limit(1)->execute($paymentMethod_id);

        if (!$objPaymentMethod->numRows) {
            return '<div>Stripe-Zahlungsmethode nicht gefunden.</div>';
        }

        $stripeSecretKey = $objPaymentMethod->stripe_privateKey;
        $stripePublicKey = $objPaymentMethod->stripe_publicKey;

        // Stripe initialisieren
        try {
            \Stripe\Stripe::setApiKey($stripeSecretKey);

            // PaymentIntent abrufen
            $paymentIntent = \Stripe\PaymentIntent::retrieve($stripe_paymentIntent);

            // Infos extrahieren
            $status = $paymentIntent->status;
            $amount = number_format($paymentIntent->amount / 100, 2, ',', '.');
            $currency = strtoupper($paymentIntent->currency);
            $method = $paymentIntent->payment_method_types[0] ?? 'unbekannt';

            // Optional: Bezahlzeitpunkt, falls vorhanden
            $created = date('d.m.Y H:i', $paymentIntent->created);

        } catch (\Exception $e) {
            return '<div>Fehler beim Abrufen des Stripe-Status: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        // Ausgabe im Backend
        ob_start();
        ?>
        <div class="paymentDetails stripe">
            <br>
            <strong>Stripe-Zahlungsdetails</strong><br>
            <b>Status:</b> <?= htmlspecialchars($status) ?><br>
            <b>Betrag:</b> <?= $amount . ' ' . $currency ?><br>
            <b>Zahlungsmethode:</b> <?= htmlspecialchars($method) ?><br>
            <b>Erstellt am:</b> <?= htmlspecialchars($created) ?><br>
            <b>Payment Intent ID:</b> <?= htmlspecialchars($stripe_paymentIntent) ?>
        </div>
        <?php
        $outputValue = ob_get_clean();

        return $outputValue;
    }
    public function showPaymentStatusInOverview($arrOrder = array(), $paymentMethod_moduleReturnData = '') {

        $stripe_paymentIntent = $arrOrder['stripe_paymentIntent'];
        $paymentMethod_id = $arrOrder['paymentMethod_id'];

        // Falls kein PaymentIntent vorhanden ist, nichts anzeigen
        if (!$stripe_paymentIntent || !$paymentMethod_id) {
            return '';
        }

        $db = Database::getInstance();
        $objPaymentMethod = $db->prepare("
            SELECT stripe_paymentMethods
            FROM tl_ls_shop_payment_methods 
            WHERE id = ?
        ")->limit(1)->execute($paymentMethod_id);

        if (!$objPaymentMethod->numRows) {
            return '<div>Stripe-Zahlungsmethode nicht gefunden.</div>';
        }

        $paymentMethods = $objPaymentMethod->stripe_paymentMethods;

        ob_start();
        ?>
        <div id="stripe_order">
            <br>
            <strong>Stripe-Zahlungsdetails</strong><br>
            <b>PaymentMethods:</b> <?= htmlspecialchars($paymentMethods) ?><br>
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
     * stripe authorization is obsolete.
     */
    protected function stripeCheckout_checkRelevantCalculationDataHash() {
        $str_relevantCalculationDataHash = sha1(
            ls_shop_cartX::getInstance()->calculation['shippingFee'][0]
            .	ls_shop_cartX::getInstance()->calculation['paymentFee'][0]
            .	ls_shop_cartX::getInstance()->calculation['taxInclusive']
            .	ls_shop_cartX::getInstance()->calculation['invoicedAmount']
            .	ls_shop_cartX::getInstance()->calculation['invoicedAmountNet']
            .	$GLOBALS['TL_CONFIG']['ls_shop_currencyCode']
            .   $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameFirstname'])
            .   $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameLastname'])
            .   $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameStreet'])
            .   $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameCity'])
            .   $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameCountryCode'])
            .   $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNamePostal'])
            .    $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNamePhone'])
            .   $this->stripeCheckout_getShippingFieldValue($this->arrCurrentSettings['stripe_shipToFieldNameState'])
        );

        /*
         * If the relevantCalculationDataHash has not been stored in the session yet, we store it now.
         */

        $session = System::getContainer()->get('merconis.session')->getSession();
        $sessionKey = 'stripe_payment_provider';
        $arr_stripe_session = $session->get($sessionKey);

        if (
            !isset($arr_stripe_session['relevantCalculationDataHash'])
            ||	!$arr_stripe_session['relevantCalculationDataHash']
        ) {
            $arr_stripe_session['relevantCalculationDataHash'] = $str_relevantCalculationDataHash;
            $session->set($sessionKey, $arr_stripe_session);
        }

        /*
         * If the current relevantCalculationDataHash differs from the one stored in the session we reset the payment
         * session status which will eventually lead to a new order being created and a possibly existing
         * authorization being voided.
         */
        if ($arr_stripe_session['relevantCalculationDataHash'] != $str_relevantCalculationDataHash) {
            $this->stripeCheckout_resetSessionStatus();
        }
    }
    protected function stripeCheckout_check_paymentIsAuthorized() {
        return true;
    }

    /*
     * This will be shown in the Frontend Payment Section
     */
    protected function stripeCheckout_showPaymentWall() {
        /** @var PageModel $objPage */
        global $objPage;

        $obj_template = new FrontendTemplate('stripeCheckoutCustomUserInterface');

        $obj_template->bln_paymentAuthorized = true;
        return $obj_template->parse();
    }
    protected function stripeCheckout_resetSessionStatus($bln_cancelPossiblyExistingAuthorization = true) {

        $session = System::getContainer()->get('merconis.session')->getSession();
        $sessionKey = 'stripe_payment_provider';

        $session->set($sessionKey, []);
    }
    protected function stripeCheckout_getShippingFieldValue($str_fieldName) {
        $str_valueWildcardPattern = '/(?:#|&#35;){2}value::(.*)(?:#|&#35;){2}/';
        if (preg_match($str_valueWildcardPattern, $str_fieldName, $arr_matches)) {
            $str_fieldName = preg_replace($str_valueWildcardPattern, $this->stripeCheckout_getShippingFieldValue($arr_matches[1]), $str_fieldName);
        }
        $arrCheckoutFormFields = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'];
        if(isset($arrCheckoutFormFields['useDeviantShippingAddress']['value']) && $arrCheckoutFormFields['useDeviantShippingAddress']['value'] == "1"){
            $str_fieldName = $str_fieldName.'_alternative';
        }
        return $arrCheckoutFormFields[$str_fieldName]['value'];
    }

    public function getCheckoutJavascript() {

        $objJsTemplate = new FrontendTemplate('template_stripe');
        return $objJsTemplate->parse();
    }

    public function specialInfoForPaymentMethodAfterCheckoutFinish() {

        $paymentModule = ls_shop_paymentModule::getInstance();


        $arr_settings = $paymentModule->settings;

        $publicKey = $arr_settings['stripe_publicKey'];
        $stripeSecretKey = $arr_settings['stripe_privateKey'];

        $type = $arr_settings['type'];

        if($type != 'stripe'){
            return;
        }

        $oih = Input::get('oih');
        $payment_intent = Input::get('payment_intent');

        // Wenn keine Bestell-ID vorhanden ist, abbrechen
        if (!$oih) {
            return;
        }

        if (!$oih) {
            return;
        }

        if($oih && $payment_intent){
            Controller::redirect($GLOBALS['merconis_globals']['ls_shop_afterCheckoutPagesUrl'].'?oih='.$oih);
        }

        // PaymentIntent-ID aus der Datenbank holen
        $order = Database::getInstance()
            ->prepare("SELECT stripe_paymentIntent FROM tl_ls_shop_orders WHERE orderIdentificationHash = ?")
            ->limit(1)
            ->execute($oih)
            ->fetchAssoc();

        // Wenn keine Bestellung oder kein PaymentIntent gefunden wurde, abbrechen
        if (!$order || !$order['stripe_paymentIntent']) {
            return;
        }

        $paymentIntentId = $order['stripe_paymentIntent'];

        try {

            // Stripe initialisieren
            \Stripe\Stripe::setApiKey($stripeSecretKey);

            // Den PaymentIntent von der Stripe-API abrufen
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            // 4. Den wahren Status und Betrag prüfen
            if ($paymentIntent->status === 'succeeded') {
                $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['stripe']['paymentSuccessAfterFinishedOrder'];
            } else {
                $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['stripe']['paymentErrorAfterFinishedOrder'];
            }

        } catch (\Stripe\Exception\ApiErrorException $e) {
            $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['stripe']['paymentErrorAfterFinishedOrder'];

        }
        return $_SESSION['lsShop']['specialInfoForPaymentMethodAfterCheckoutFinish'];
    }

    // We dont change the form but att javascript that we need for stripe to work
    public function modifyConfirmOrderForm($str_form = '') {

        $GLOBALS['TL_JAVASCRIPT'][] = 'https://js.stripe.com/v3/|defer';
        return $str_form;
    }

    public function writeLog($outputType, $output, $logModeInfoText, $bypassLogMode = false){

        if($bypassLogMode == false){

            //the log mode info is checked beforehand and $bypassLogMode=true must be used to log it
            if($this->arrCurrentSettings['stripe_logMode'] == 'ERROR'){
                $this->arrPastLogs[] = [$outputType, $output];
                return;
            }

            if($this->arrCurrentSettings['stripe_logMode'] == 'NONE') {
                return;
            }
        }

        //Request Data will not get logged on INFO logMode only Request and Response Header
        if($this->arrCurrentSettings['stripe_logMode'] == 'INFO' && $outputType == 'Request Data'){
            return;
        }

        if($this->arrCurrentSettings['stripe_logMode'] == 'INFO'){
            $output = $logModeInfoText."\n".$output;
        }

        $str_filename = 'stripe_'.$this->arrCurrentSettings['stripe_logMode'].'_'.date("Y-m-d").'.log';
        lsDebugLog($output, "[".date("d-m-Y h:i:sa")."] [".$outputType."]", 'regular', false, '', false, $str_filename);

    }


}
?>
