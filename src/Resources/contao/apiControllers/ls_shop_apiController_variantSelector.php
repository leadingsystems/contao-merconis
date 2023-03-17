<?php

namespace Merconis\Core;

class ls_shop_apiController_variantSelector
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
	 * Returns the data initially required by the variant selector.
	 * Add the parameter 'productVariantId' to specify for which product variant
	 * you are requesting the data.
	 */
	protected function apiResource_variantSelector_getInitialData() {
		if (!\Input::get('productVariantId')) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no productVariantId given');
			return;
		}
		
		$obj_product = ls_shop_generalHelper::getObjProduct(\Input::get('productVariantId'));
		
		$arr_selectedAttributeValues = $obj_product->_variantIsSelected ? $obj_product->_selectedVariant->_attributeValueIds : $obj_product->_attributeValueIds;
		
		/*
		 * A product or variant can have multiple values selected for the same
		 * attribute and therefore the product/variant property _attributeValueIds
		 * delivers an array for each attribute holding all selected values.
		 * 
		 * The variant selector doesn't support multiple selected values and therefore
		 * expects only one value id for each attribute id, so we have to translate
		 * the array accordingly
		 */
		foreach ($arr_selectedAttributeValues as $int_attributeId => $arr_valueIds) {
			$arr_selectedAttributeValues[$int_attributeId] = $arr_valueIds[0];
		}
		
		$arr_return = array(
			'_allVariantAttributes' => $obj_product->_allVariantAttributes,
			'_selectedAttributeValues' => $arr_selectedAttributeValues,
			'_possibleAttributeValues' => $obj_product->_getPossibleAttributeValuesForCurrentSelection($arr_selectedAttributeValues)
		);
		
		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($arr_return);
	}
}
