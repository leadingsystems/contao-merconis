<?php

namespace Merconis\Core;

class ls_shop_apiController {
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
	 * Returns product properties the same way as they would be available in a php
	 * template file via $obj_product.
	 * Add the parameter 'productId' to the resource to specify for which product
	 * you are requesting the data.
	 * Add the parameter 'properties' with a comma separated list of product properties to request.
	 * 
	 * Please note that not every property can be json encoded and therefore not
	 * every property can be read with an api call.
	 */
	protected function apiResource_getProductProperty() {
		if (!\Input::get('productId')) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no productId given');
			return;
		}
		
		$arr_requestedProperties = array_map('trim', explode(',', \Input::get('properties')));
		
		if (!is_array($arr_requestedProperties) || !count($arr_requestedProperties)) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no property or properties requested');
			return;
		}
		
		$obj_product = ls_shop_generalHelper::getObjProduct(\Input::get('productId'));
		
		$bln_useVariant = \Input::get('useVariant') ? true : false;
		if ($bln_useVariant && !$obj_product->_variantIsSelected) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('variant property requested but no variant selected');
			return;			
		}
		
		$arr_return = array();
		
		foreach ($arr_requestedProperties as $str_requestedProperty) {
			$arr_return[$str_requestedProperty] = $bln_useVariant ? $obj_product->_selectedVariant->{$str_requestedProperty} : $obj_product->{$str_requestedProperty};
		}
		
		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($arr_return);
	}
	

	
	/**
	 * Calls a product method the same way as it would be called in a php template
	 * file via $obj_product.
	 * Add the parameter 'productId' to the resource to specify for which product
	 * you are calling the method.
	 * Add the parameter 'parameters' holding an array with the parameters to pass
	 * to the product method in the order required by the method.
	 * 
	 * Please note that not every return value can be json encoded and therefore not
	 * every return value can be successfully read with an api call.
	 */
	protected function apiResource_callProductMethod() {
		if (!\Input::get('productId')) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no productId given');
			return;
		}
		
		if (!\Input::get('method')) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no method specified');
			return;
		}
		
		$obj_product = ls_shop_generalHelper::getObjProduct(\Input::get('productId'));
		
		$arr_parameters = json_decode(html_entity_decode(\Input::get('parameters')), true);
		
		if (!is_array($arr_parameters)) {
			$arr_parameters = array();
		}
		
		$var_return = call_user_func_array(array($obj_product, \Input::get('method')), $arr_parameters);
		
		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($var_return);
	}

    /**
     * Calls a method of a product's configurator object.
     * Add the parameter 'productId' to the resource to specify for which product
     * you are calling the configurator's custom logic method.
     * Add the parameter 'parameters' holding an array with the parameters to pass
     * to the requested configurator custom logic method in the order required by the method.
     *
     * Please note that not every return value can be json encoded and therefore not
     * every return value can be successfully read with an api call.
     *
     * 31.01.2022, 1. alle Variablen mit der Methode (get/post) holen, mit der die erste Variable geholt worden ist und
     *  2. Rückumwandlung von HTML-Entities
     */
    protected function apiResource_callConfiguratorCustomLogicMethodForProduct() {

        if (\Input::get('productId')) {

            $int_productId = \Input::get('productId');
            $str_getOrPost = 'get';                             //alle weiteren Zugriffe auf empfangene Daten mit der Methode
        } else if (\Input::post('productId')) {

            $int_productId = \Input::post('productId');
            $str_getOrPost = 'post';                             //alle weiteren Zugriffe auf empfangene Daten mit der Methode
        } else {

            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no productId given');
            return;
        }



        $str_method = \Input::{$str_getOrPost}('method');

        if (!$str_method) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no method specified');
            return;
        }

        $obj_productOrVariant = ls_shop_generalHelper::getObjProduct($int_productId);

        if ($obj_productOrVariant->_variantIsSelected) {
            $obj_productOrVariant = $obj_productOrVariant->_selectedVariant;
        }
        $obj_configurator = $obj_productOrVariant->_objConfigurator;

        $str_inputParameters = \Input::{$str_getOrPost}('parameters');

        $str_inputParameters = html_entity_decode($str_inputParameters);

        $arr_parameters = json_decode($str_inputParameters, true);


        if (!is_array($arr_parameters)) {
            $arr_parameters = array();
        }

        if (!is_object($obj_configurator->objCustomLogic)) {
            $this->obj_apiReceiver->success();
            $this->obj_apiReceiver->set_data('product has no configurator custom logic object');
            return;
        }

        if (!method_exists($obj_configurator->objCustomLogic, \Input::{$str_getOrPost}('method'))) {
            $this->obj_apiReceiver->success();
            $this->obj_apiReceiver->set_data('method does not exist in configurator custom logic object');
            return;
        }

        $var_return = call_user_func_array(array($obj_configurator->objCustomLogic, \Input::{$str_getOrPost}('method')), $arr_parameters);

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($var_return);
    }

    /**
     * Calls a method of a product's customizer object.
     * Add the parameter 'productId' to specify for which product you are calling the customizer's method.
     * Add the parameter 'parameters' holding an array with the parameters to pass
     * to the requested customizer method in the order required by the method.
     *
     * Please note that not every return value can be json encoded and therefore not
     * every return value can be successfully read with an api call.
     */
    protected function apiResource_callCustomizerMethodForProduct() {

        if (\Input::get('productId')) {
            $int_productId = \Input::get('productId');
            $str_getOrPost = 'get';                             // all further accesses to received data with this method
        } else if (\Input::post('productId')) {
            $int_productId = \Input::post('productId');
            $str_getOrPost = 'post';                             // all further accesses to received data with this method
        } else {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no productId given');
            return;
        }

        $str_method = \Input::{$str_getOrPost}('method');

        if (!$str_method) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no method specified');
            return;
        }

        $obj_productOrVariant = ls_shop_generalHelper::getObjProduct($int_productId);

        if ($obj_productOrVariant->_variantIsSelected) {
            $obj_productOrVariant = $obj_productOrVariant->_selectedVariant;
        }

        if (!$obj_productOrVariant->_hasCustomizer){
            $this->obj_apiReceiver->success();
            $this->obj_apiReceiver->set_data('product has no customizer');
            return;
        }

        $str_inputParameters = \Input::{$str_getOrPost}('parameters');
        $str_inputParameters = html_entity_decode($str_inputParameters);
        $arr_parameters = json_decode($str_inputParameters, true);


        if (!is_array($arr_parameters)) {
            $arr_parameters = [];
        }

        if (!method_exists($obj_productOrVariant->_customizer, \Input::{$str_getOrPost}('method'))) {
            $this->obj_apiReceiver->success();
            $this->obj_apiReceiver->set_data('method does not exist in customizer object');
            return;
        }

        $var_return = call_user_func_array(array($obj_productOrVariant->_customizer, \Input::{$str_getOrPost}('method')), $arr_parameters);

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($var_return);
    }

    /**
     * Returns a specific merconis url
     */
    protected function apiResource_getMerconisPageUrl() {
        if (!\Input::post('pageType')) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no pageType given');
            return;
        }

        $str_return = ls_shop_languageHelper::getLanguagePage(\Input::post('pageType'));

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($str_return);
    }

    /**
     * Returns all language independent flex contents that exist in all products and variants
     */
    protected function apiResource_getAllFlexContentsLI() {
        $arr_return = ls_shop_generalHelper::getAllFlexContentsLI();

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_return);
    }

    /**
     * Returns all values for language independent flex content with a specific key that exist in all products and variants
     */
    protected function apiResource_getFlexContentLIValues() {
        if (!\Input::get('flexContentKey')) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no flexContentKey given');
            return;
        }
        $arr_return = ls_shop_generalHelper::getFlexContentLIValues(\Input::get('flexContentKey'));

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_return);
    }


    /**
     * Returns all language dependent flex contents that exist in all products and variants
     */
    protected function apiResource_getAllFlexContentsLD() {
        $arr_return = ls_shop_generalHelper::getAllFlexContentsLD();

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_return);
    }

    /**
     * Returns all values for language dependent flex content with a specific key that exist in all products and variants
     */
    protected function apiResource_getFlexContentLDValues() {
        if (!\Input::get('flexContentKey')) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no flexContentKey given');
            return;
        }
        $arr_return = ls_shop_generalHelper::getFlexContentLDValues(\Input::get('flexContentKey'));

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_return);
    }

}


