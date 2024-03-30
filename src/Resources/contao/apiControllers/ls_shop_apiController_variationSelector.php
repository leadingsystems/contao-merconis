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
		$arr_selectedAttributeValues = $obj_product->_attributeValueIdsForVariationSelectorFlattened;


        $arr_return = array(
			'_allVariationAttributes' => $allVariationAttributes,
			'_selectedAttributeValues' => $arr_selectedAttributeValues,
		);
		
		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($arr_return);
	}

    protected function apiResource_variationSelector_getVariationForAttributeSelection() {
        /*
         * productId is the ID of the currently selected product variation
         */
        if (!\Input::get('productId')) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no productId given');
            return;
        }

        if (!\Input::get('attributeSelection') || !is_array($attributeSelection = json_decode(html_entity_decode(\Input::get('attributeSelection')), true))) {
            $this->obj_apiReceiver->fail();
            $this->obj_apiReceiver->set_data('no attributeSelection given');
            return;
        }

        $productID = \Input::get('productId');
        $obj_product = ls_shop_generalHelper::getObjProduct($productID);

        /*
         * We combine the requested attributeSelection with the current product's attributes so that we can look
         * for a product variation that is as close as possible to the current product but with the currently requested
         * attribute selection. Since a product with those specific attributes/values might not exist, we have to
         * look for an alternative product variation with only the requested attributeSelection if we don't get a
         * better matching product variation.
         */
        $fullAttributeSelection = [];
        foreach ($obj_product->_attributes as $attributeId => $attributeValues) {
            $fullAttributeSelection[$attributeId] = $attributeValues[0]['valueID'];
        }
        $fullAttributeSelection[key($attributeSelection)] = (int) current($attributeSelection);

        $isExactMatch = true;

        $matchingProductVariation = $obj_product->getVariationByAttributeValues($fullAttributeSelection);
        if ($matchingProductVariation === null) {
            /*
             * If we haven't found a matching variation with the full attribute selection, we have to look for a match
             * with only the actually requested attribute selection.
             * If we find a matching variation for that, we know that more attribute values are different from what
             * the user would expect. Therefore, we have to inform the user about that.
             */
            $isExactMatch = false;
            $matchingProductVariation = $obj_product->getVariationByAttributeValues($attributeSelection);
        }

        if ($matchingProductVariation === null) {
            trigger_error('No matching product variation found for attributeSelection ' . json_encode($attributeSelection) . '. This should never happen. Please check!', E_USER_WARNING);
        }

        $arr_return = array(
            '_productVariationId' => $matchingProductVariation->ls_ID ?? null,
            '_productVariationUrl' => $matchingProductVariation->_link ?? null,
            '_selectedAttributeValues' => $matchingProductVariation->_attributeValueIdsForVariationSelectorFlattened ?? null,
            '_isExactMatch' => $isExactMatch
        );

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_return);
    }
}
