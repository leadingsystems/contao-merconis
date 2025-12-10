<?php

namespace Merconis\Core;

use Contao\Database;
use Contao\Environment;
use Contao\Input;

class ls_shop_apiController_payment
{
    protected static $objInstance;

    /** @var \LeadingSystems\Api\ls_apiController $obj_apiReceiver */
    protected $obj_apiReceiver = null;

    protected function __construct() {}

    private function __clone() {}

    public static function getInstance() {
        if (!is_object(self::$objInstance))
        {
            self::$objInstance = new self();
        }

        return self::$objInstance;
    }

    public function processRequest($str_resourceName, $obj_apiReceiver) {
        if (!$str_resourceName || !$obj_apiReceiver) {
            return;
        }

        $this->obj_apiReceiver = $obj_apiReceiver;

        /*
         * If this class has a method that matches the resource name, we call it.
         * If not, we don't do anything because another class with a corresponding
         * method might have a hook registered.
         */
        if (method_exists($this, $str_resourceName)) {
            $this->{$str_resourceName}();
        }
    }


    protected function apiResource_paymentapi() {
        if (!Input::get('function')) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no function given');
            return;
        }

        if (Input::get('function') == 'finish-order') {
            $this->finishOrder();
        }
    }

    function finishOrder() {

        $obj_paymentModule = ls_shop_paymentModule::getInstance();
        $arr_settings = $obj_paymentModule->settings;

        $clientId = $arr_settings['payPalCheckout_clientID'];
        $clientSecret = $arr_settings['payPalCheckout_clientSecret'];

        // Preis
        $total = \Merconis\Core\ls_shop_cartX::getInstance()->calculation['total'][0];

        // Währung
        $currency = strtoupper($GLOBALS['TL_CONFIG']['ls_shop_currencyCode']);

        $baseUrl = ($arr_settings['payPalCheckout_liveMode'] ? ls_shop_paymentModule_payPalCheckout::LIVE_URL : ls_shop_paymentModule_payPalCheckout::SANDBOX_URL);


        // get Access Token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$baseUrl/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US'
        ]);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $clientSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $tokenResult = curl_exec($ch);
        curl_close($ch);

        // Merconis Checkout
        $obj_checkout = new ls_shop_checkout();

        $accessToken = json_decode($tokenResult)->access_token;

        $obj_checkout->completeCheckout();

        $afterCheckoutUrl = Environment::get('base').$obj_checkout->getAfterCheckoutUrlWithOih();

        // create Order
        $body = json_encode([
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "amount" => [
                    "currency_code" => $currency,
                    "value" => number_format($total, 2, '.', '')
                ]
            ]],
            "application_context" => [
                "return_url" => $afterCheckoutUrl,
                "cancel_url" => $afterCheckoutUrl
            ]
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$baseUrl/v2/checkout/orders");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $accessToken"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $orderResult = curl_exec($ch);
        curl_close($ch);

        $order = json_decode($orderResult, true);

        // finde approve-link
        $approveUrl = null;
        foreach ($order['links'] as $l) {
            if ($l['rel'] === 'approve') { $approveUrl = $l['href']; break; }
        }

        // PAYPAL ORDER-ID in Merconis speichern
        Database::getInstance()
            ->prepare("UPDATE tl_ls_shop_orders SET payPalCheckout_orderID=?, payPalCheckout_currentStatus = ? WHERE id=?")
            ->execute($order['id'], $order['status'], $obj_checkout->getOrderId());


        // set onPageLoadRedirect url because the user should be informed that the order is created even if the user goes back to another side in payment process
        $_SESSION['lsShop']['onPageLoadRedirectUrl'] = $afterCheckoutUrl;

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data([
            "id" => $order['id'],
            'approveUrl' => $approveUrl,
            'afterCheckoutUrl' => $afterCheckoutUrl
        ]);
    }

}