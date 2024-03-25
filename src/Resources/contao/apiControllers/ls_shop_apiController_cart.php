<?php

namespace Merconis\Core;

use Contao\Input;
use Contao\System;

class ls_shop_apiController_cart {
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
	
	/**
	 * Adds a product/variant to the cart
	 */
	protected function apiResource_addToCart() {
		if (!Input::post('productVariantId')) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no productVariantId given');
			return;
		}

		if (!Input::post('quantity')) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no quantity given');
			return;
		}

        $arr_return = ls_shop_cartHelper::addToCart(Input::post('productVariantId'), Input::post('quantity'));

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($arr_return);
	}

	/**
	 * Empties the cart completely
	 */
	protected function apiResource_emptyCart() {

        $session = System::getContainer()->get('merconis.session')->getSession();

        if ($session->has('lsShopCart')) {
            $session->remove('lsShopCart');
        }

		$this->obj_apiReceiver->success();
//		$this->obj_apiReceiver->set_data($arr_return);
	}
}
