<?php

namespace Merconis\Core;

class ls_shop_apiController_variationSelector
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
	
	/**
	 * Returns the data initially required by the variation selector.
	 * Add the parameter 'productId' to specify for which product
	 * you are requesting the data.
	 */
	protected function apiResource_variationSelector_getInitialData() {
		if (!\Input::get('productId')) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no productId given');
			return;
		}

        /*
         * We are only interested in the product id because product variations have nothing to do with variants
         */
        $productID = \Input::get('productId');
		
		$obj_product = ls_shop_generalHelper::getObjProduct($productID);

        $allVariationAttributes = $obj_product->getAllVariationAttributes();
		
		$arr_selectedAttributeValues = $obj_product->_attributeValueIds;
		
		/*
		 * A product can have multiple values selected for the same
		 * attribute and therefore the product property _attributeValueIds
		 * delivers an array for each attribute holding all selected values.
		 * 
		 * The variation selector doesn't support multiple selected values and therefore
		 * expects only one value id for each attribute id, so we have to translate
		 * the array accordingly
		 */
		foreach ($arr_selectedAttributeValues as $int_attributeId => $arr_valueIds) {
			$arr_selectedAttributeValues[$int_attributeId] = $arr_valueIds[0];
		}
		
		$arr_return = array(
			'_allVariationAttributes' => $allVariationAttributes,
			'_selectedAttributeValues' => $arr_selectedAttributeValues,
			'_possibleAttributeValues' => $obj_product->_getPossibleAttributeValuesForCurrentSelection($arr_selectedAttributeValues, false, true)
		);
		
		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($arr_return);
	}
}
