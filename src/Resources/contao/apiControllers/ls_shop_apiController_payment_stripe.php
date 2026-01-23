<?php

namespace Merconis\Core;

use Contao\Database;
use Contao\Input;

class ls_shop_apiController_payment_stripe
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

            $clientSecret = Input::get('clientSecret');
            if(!$clientSecret){
                $this->obj_apiReceiver->fail();
                $this->obj_apiReceiver->set_data('no client secret given');
            }
            $this->finishOrder($clientSecret);
        }

        if (Input::get('function') == 'get-payment-intend') {
            $this->getPaymentIntend();
        }

        if (Input::get('function') == 'get-payment-info') {
            $this->getPaymentInfoFromStripe();
        }



	}

    private function getPaymentIntendFromClientSecret($clientSecret)
    {
        return explode('_secret_', $clientSecret)[0];
    }


    function getPaymentIntend() {

        //-------------------------- Destroy old Client Secret --------------------------

        $clientSecret = $_SESSION['lsShop']['clientSecret'];

        $obj_paymentModule = ls_shop_paymentModule::getInstance();
        $arr_settings = $obj_paymentModule->settings;
        $publicKey = $arr_settings['stripe_publicKey'];
        $privatKey = $arr_settings['stripe_privateKey'];

        // Set Stripe API key
        \Stripe\Stripe::setApiKey($privatKey);

        if($clientSecret){

            $paymentIntent = explode('_secret_', $clientSecret)[0];


            try{
                $pi = \Stripe\PaymentIntent::retrieve($paymentIntent); // PaymentIntent-Objekt holen
                $pi->cancel(); // Instanzmethode
            }catch (\Exception $e){
                //kann vorkommen wenn bereits abgeschlossen wurde aber noch was in der session drin steckt
            }

            $clientSecret = null;
        }


        $_SESSION['lsShop']['clientSecret'] = $clientSecret;


        //-------------------------- create new Client Secret --------------------------

        $paymentInfo = $obj_paymentModule->getPaymentInfo();
        $stripeSelection = $paymentInfo['stripeSelection'] ?? ($paymentInfo['paymentMethod'] ?? '');
        $billing_details = $paymentInfo['billing_details'] ?? [];

        $stripePaymentBehaviour = ls_shop_paymentModule_stripe::getStripePaymentBehaviour(
            (string) $stripeSelection,
            $arr_settings
        );

        $priceInCent = intval(\Merconis\Core\ls_shop_cartX::getInstance()->calculation['total'][0]*100);

        $currency = strtolower($GLOBALS['TL_CONFIG']['ls_shop_currencyCode']);

        try {

            $arr_paymentInformationToSend = [
                'amount' => $priceInCent, // Betrag in Cent
                'currency' => $currency,
                'payment_method_types' => [$stripePaymentBehaviour['stripeType']],
            ];


            $obj_paymentModule->writeLog(
                "Info",
                'Create paymentIntentId with ' . json_encode($arr_paymentInformationToSend),
                ''
            );

            // Create PaymentIntent
            $paymentIntent = \Stripe\PaymentIntent::create($arr_paymentInformationToSend);

            $paymentIntentId = $paymentIntent->id;
            $clientSecret = $paymentIntent->client_secret;


            // save clientSecret in Session
            $_SESSION['lsShop']['clientSecret'] = $clientSecret;

            $arr_return = array(
                'success' => true,
                'clientSecret' => $clientSecret
            );


        }catch (\Exception $e) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('error in creating payment intent');
            return;
        }

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_return);
    }

    function finishOrder($clientSecret) {

        $obj_paymentModule = ls_shop_paymentModule::getInstance();
        $arr_settings = $obj_paymentModule->settings;
        $publicKey = $arr_settings['stripe_publicKey'];
        $privatKey = $arr_settings['stripe_privateKey'];

        // Set Stripe API key
        \Stripe\Stripe::setApiKey($privatKey);

        try {
            // Create PaymentIntent

            $paymentIntentId = $this->getPaymentIntendFromClientSecret($clientSecret);

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

            try {
                \Stripe\PaymentIntent::update(
                    $paymentIntentId,
                    [
                        'description' => 'Aktualisierte Beschreibung',
                    ]
                );
            }catch (\Exception $e) {
                //log descrition konnte nicht aktualisiert werden
            }

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

        $obj_paymentModule->writeLog("Info", 'Order created with paymentIntentId('.$paymentIntentId.')', '');

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

        $paymentInfo = $obj_paymentModule->getPaymentInfo();
        $stripeSelection = $paymentInfo['stripeSelection'];
        $billing_details = $paymentInfo['billing_details'] ?? [];

        $stripePaymentBehaviour = ls_shop_paymentModule_stripe::getStripePaymentBehaviour(
            (string) $stripeSelection,
            $arr_settings
        );


        $arr_return = array(
            'functionEingabe' => Input::get('function'),
            'paymentMethodType' => $stripePaymentBehaviour['stripeType'],
            'stripeElementOptions' => $stripePaymentBehaviour['stripeElementOptions'],
            'stripeCreatePaymentOptions' => $stripePaymentBehaviour['stripeCreatePaymentOptions'] ?? [],
            'publicKey' => $publicKey,
            'billing_details' => $billing_details

        );

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_return);
    }

}
