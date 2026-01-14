<?php

namespace Merconis\Core;

use Contao\Database;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;

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
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('invalid request method');
            return;
        }

        if (!Input::post('REQUEST_TOKEN')) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('missing request token');
            return;
        }

        if (!Input::post('function')) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no function given');
            return;
        }

        if (Input::post('function') == 'finish-order') {
            $this->finishOrder();
        }
    }

    function finishOrder() {

        $obj_paymentModule = ls_shop_paymentModule::getInstance();
        $arr_settings = $obj_paymentModule->settings;

        $clientId = $arr_settings['payPalCheckout_clientID'];
        $clientSecret = $arr_settings['payPalCheckout_clientSecret'];

        $cartCalculation = \Merconis\Core\ls_shop_cartX::getInstance()->calculation;
        $cartItemsExtended = \Merconis\Core\ls_shop_cartX::getInstance()->itemsExtended;
        $total = $cartCalculation['invoicedAmount'];
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
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $tokenResult = curl_exec($ch);
        $obj_paymentModule->writeLog("Request", curl_getinfo($ch)['request_header'],'Send Request to create a new Access Token in finish order');

        curl_close($ch);
        $accessToken = json_decode($tokenResult)->access_token;

        if(isset($accessToken)){
            $obj_paymentModule->writeLog("Response", $tokenResult, 'A new AccessToken '.$accessToken.' was created in finish order');
        }else{
            $obj_paymentModule->writeLog("Response", $tokenResult, 'There was an Error creating a new AccessToken in finish order');
            $this->isError = true;
        }


        // Merconis Checkout
        $obj_checkout = new ls_shop_checkout();
        $obj_checkout->completeCheckout();

        $orderIdInDb = (int) $obj_checkout->getOrderId();
        $oix = ls_shop_generalHelper::encodeOix($orderIdInDb);

        /*
         * IMPORTANT:
         * Do not expose the oih in a payment provider return/cancel URL.
         * The oih must remain read-only and is not meant to be shared externally.
         */
        $afterCheckoutUrlWithOix = preg_replace(
            '/([?&])oih=[^&]*(&?)/',
            '$1',
            (string) $obj_checkout->getAfterCheckoutUrlWithOih()
        );
        $afterCheckoutUrlWithOix = rtrim($afterCheckoutUrlWithOix, '?&');
        $afterCheckoutUrlWithOix .= (strpos($afterCheckoutUrlWithOix, '?') !== false ? '&' : '?') . 'oix=' . $oix;

        $afterCheckoutUrl = Environment::get('base') . $afterCheckoutUrlWithOix;

        $checkoutCustomerData = \Merconis\Core\ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData'] ?? [];
        $useAlternativeShipping = isset($checkoutCustomerData['useDeviantShippingAddress']['value']) && $checkoutCustomerData['useDeviantShippingAddress']['value'] == "1";
        $getShippingFieldValue = function(string $fieldName) use ($checkoutCustomerData, $useAlternativeShipping) {
            $fieldKey = $useAlternativeShipping ? $fieldName.'_alternative' : $fieldName;
            return $checkoutCustomerData[$fieldKey]['value'] ?? '';
        };

        $shippingName = trim($getShippingFieldValue($arr_settings['payPalCheckout_shipToFieldNameFirstname']) . ' ' . $getShippingFieldValue($arr_settings['payPalCheckout_shipToFieldNameLastname']));
        $shippingStreet = $getShippingFieldValue($arr_settings['payPalCheckout_shipToFieldNameStreet']);
        $shippingCity = $getShippingFieldValue($arr_settings['payPalCheckout_shipToFieldNameCity']);
        $shippingPostal = $getShippingFieldValue($arr_settings['payPalCheckout_shipToFieldNamePostal']);
        $shippingCountry = strtoupper($getShippingFieldValue($arr_settings['payPalCheckout_shipToFieldNameCountryCode']));
        $shippingState = $getShippingFieldValue($arr_settings['payPalCheckout_shipToFieldNameState']);

        $shippingAddress = [];
        if ($shippingStreet && $shippingCity && $shippingPostal && $shippingCountry) {
            $shippingAddress = [
                "name" => [
                    "full_name" => $shippingName
                ],
                "address" => [
                    "address_line_1" => $shippingStreet,
                    "admin_area_2" => $shippingCity,
                    "postal_code" => $shippingPostal,
                    "country_code" => $shippingCountry
                ]
            ];

            if ($shippingState) {
                $shippingAddress["address"]["admin_area_1"] = $shippingState;
            }
        }

        $shippingAmount = $cartCalculation['shippingFee'][0] ?? 0;
        $handlingAmount = $cartCalculation['paymentFee'][0] ?? 0;
        $taxAmount = $cartCalculation['taxInclusive'] ? 0 : ($cartCalculation['invoicedAmount'] - $cartCalculation['invoicedAmountNet']);
        $discountRaw = 0;
        foreach ($cartCalculation['couponValues'] as $couponValue) {
            $discountRaw += $couponValue[0];
        }
        $discountAmount = max(0, abs(min(0, $discountRaw)));

        $items = [];
        $itemsTotalFromLines = 0;
        foreach ($cartCalculation['items'] as $cartItem) {
            $cartItemExtended = $cartItemsExtended[$cartItem['productCartKey']] ?? null;
            if ($cartItemExtended === null || ($cartItemExtended['quantity'] ?? 0) == 0) {
                continue;
            }

            $itemName = substr($cartItemExtended['objProduct']->_title ?? 'Item', 0, 127);
            $itemDescription = $cartItemExtended['objProduct']->_hasCode ? substr($cartItemExtended['objProduct']->_code, 0, 127) : '';
            $isIntegerQty = intval($cartItemExtended['quantity']) == $cartItemExtended['quantity'];

            if ($isIntegerQty) {
                $unitPrice = number_format($cartItem['price'], 2, '.', '');
                $quantity = $cartItemExtended['quantity'];
                $lineTotal = $quantity * (float) $unitPrice;
            } else {
                $unitPrice = number_format($cartItem['priceCumulative'], 2, '.', '');
                $quantity = 1;
                $itemDescription = trim($itemDescription.' ('.$cartItemExtended['quantity'].' '.$cartItemExtended['objProduct']->_quantityUnit.' * '.$cartItemExtended['objProduct']->_priceAfterTaxFormatted.')');
                $lineTotal = (float) $unitPrice;
            }

            $itemsTotalFromLines += $lineTotal;

            $items[] = [
                "name" => $itemName,
                "description" => $itemDescription,
                "quantity" => (string) $quantity,
                "unit_amount" => [
                    "currency_code" => $currency,
                    "value" => $unitPrice
                ]
            ];
        }

        $itemTotal = $itemsTotalFromLines > 0
            ? $itemsTotalFromLines
            : $cartCalculation['invoicedAmount'] + $discountAmount - $shippingAmount - $handlingAmount - $taxAmount;

        $body = json_encode([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => $afterCheckoutUrl,
                "cancel_url" => $afterCheckoutUrl,
                "shipping_preference" => "SET_PROVIDED_ADDRESS",
                "user_action" => "PAY_NOW"
            ],
            "purchase_units" => [[
                "amount" => [
                    "currency_code" => $currency,
                    "value" => number_format($total, 2, '.', ''),
                    "breakdown" => [
                        "item_total" => [
                            "currency_code" => $currency,
                            "value" => number_format($itemTotal, 2, '.', '')
                        ],
                        "shipping" => [
                            "currency_code" => $currency,
                            "value" => number_format($shippingAmount, 2, '.', '')
                        ],
                        "handling" => [
                            "currency_code" => $currency,
                            "value" => number_format($handlingAmount, 2, '.', '')
                        ],
                        "tax_total" => [
                            "currency_code" => $currency,
                            "value" => number_format($taxAmount, 2, '.', '')
                        ],
                        "discount" => [
                            "currency_code" => $currency,
                            "value" => number_format($discountAmount, 2, '.', '')
                        ]
                    ]
                ],
                "items" => $items,
                "shipping" => $shippingAddress
            ]]
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
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $orderResult = curl_exec($ch);
        $obj_paymentModule->writeLog("Request", curl_getinfo($ch)['request_header'],'Send Request to create a new Access Token in finish order');

        curl_close($ch);

        $order = json_decode($orderResult, true);

        if(isset($accessToken)){
            $obj_paymentModule->writeLog("Response", $orderResult, 'A new Order Intent was created');
        }else{
            $obj_paymentModule->writeLog("Response", $orderResult, 'There was an Error creating a new Order Intent');
            $this->isError = true;
        }


        // finde approve-link
        $approveUrl = null;
        foreach ($order['links'] as $l) {
            if ($l['rel'] === 'approve') { $approveUrl = $l['href']; break; }
        }

        // PAYPAL ORDER-ID in Merconis speichern

        $existingPaymentMethodModuleReturnData = Database::getInstance()
            ->prepare("SELECT paymentMethod_moduleReturnData FROM tl_ls_shop_orders WHERE id=?")
            ->limit(1)
            ->execute($orderIdInDb)
            ->paymentMethod_moduleReturnData;

        $paymentMethodModuleReturnData = StringUtil::deserialize($existingPaymentMethodModuleReturnData, true);
        if (!is_array($paymentMethodModuleReturnData)) {
            $paymentMethodModuleReturnData = [];
        }

        $paymentMethodModuleReturnData['str_orderId'] = $order['id'] ?? '';
        $paymentMethodModuleReturnData['arr_saleDetails'] = array_merge(
            $paymentMethodModuleReturnData['arr_saleDetails'] ?? [],
            [
                'str_orderId' => $order['id'] ?? '',
                'str_currentStatus' => $order['status'] ?? '',
                'str_captureId' => '',
                'str_captureStatus' => '',
                'str_captureStatusDetails' => '',
                'str_errorMsg' => ($order['message'] ?? ''),
            ]
        );

        Database::getInstance()
            ->prepare("
                UPDATE tl_ls_shop_orders
                SET
                    payPalCheckout_orderId=?,
                    payPalCheckout_currentStatus=?,
                    paymentMethod_moduleReturnData=?
                WHERE id=?
            ")
            ->execute(
                $order['id'] ?? '',
                $order['status'] ?? '',
                serialize($paymentMethodModuleReturnData),
                $orderIdInDb
            );


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