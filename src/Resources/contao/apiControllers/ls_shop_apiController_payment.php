<?php

namespace Merconis\Core;

use Contao\Database;
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

        if (Input::get('function') == 'get-payment-info') {
            $this->getPaymentInfoFromStripe();
        }



	}

    function finishOrder() {

        $obj_paymentModule = ls_shop_paymentModule::getInstance();
        $arr_settings = $obj_paymentModule->settings;
        $publicKey = $arr_settings['stripe_publicKey'];
        $privatKey = $arr_settings['stripe_privateKey'];

        // Set Stripe API key
        \Stripe\Stripe::setApiKey($privatKey);

        $infoPayment = $obj_paymentModule->getPaymentInfo();
        $paymentMethod = $obj_paymentModule->getPaymentInfo()['paymentMethod'];
        $billing_details = $obj_paymentModule->getPaymentInfo()['billing_details'];

        $priceInCent = intval(\Merconis\Core\ls_shop_cartX::getInstance()->calculation['total'][0]*100);

        $currency = strtolower($GLOBALS['TL_CONFIG']['ls_shop_currencyCode']);

        try {
            // Create PaymentIntent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $priceInCent, // Betrag in Cent
                'currency' => $currency,
                'payment_method_types' => [$paymentMethod],
            ]);

            $paymentIntentId = $paymentIntent->id;
            $clientSecret = $paymentIntent->client_secret;

            $obj_checkout = new ls_shop_checkout();

            $obj_checkout->completeCheckout([
                'stripe_paymentIntent' => $paymentIntentId
            ]);

            $orderId = $obj_checkout->getOrderId();
            $afterCheckoutUrl = $obj_checkout->getAfterCheckoutUrlWithOih();

            // Save Payment Intent, this will be used later to complete the payment
            $db = Database::getInstance();
            $db->prepare("UPDATE tl_ls_shop_orders SET stripe_paymentIntent = ? WHERE id = ?")
                ->execute($paymentIntentId, $orderId);

            $arr_return = array(
                'success' => true,
                'clientSecret' => $clientSecret,
                'afterCheckoutUrl' => $afterCheckoutUrl
            );

        }catch (\Exception $e) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('error in creating payment intent');
            return;
        }

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_return);
    }

    function getPaymentInfoFromStripe()
    {
        $obj_paymentModule = ls_shop_paymentModule::getInstance();
        $arr_settings = $obj_paymentModule->settings;
        $publicKey = $arr_settings['stripe_publicKey'];
        $privatKey = $arr_settings['stripe_privateKey'];

        // Set Stripe API key
        \Stripe\Stripe::setApiKey($privatKey);

        $paymentMethod = $obj_paymentModule->getPaymentInfo()['paymentMethod'];
        $billing_details = $obj_paymentModule->getPaymentInfo()['billing_details'];


        $arr_return = array(
            'functionEingabe' => Input::get('function'),
            'paymentMethodType' => $paymentMethod,
            'publicKey' => $publicKey,
            'billing_details' => $billing_details

        );

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_return);
    }

}
