<?php
namespace Merconis\Core;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_productManagementApiPreprocessor
{
	protected static $str_preprocessorMethodNamePrefix = 'preprocess_';

	public static $arr_resourceAndFieldDefinition = array(
		'apiResource_writeProductData' => array(
			'bln_expectsMultipleDataRows' => true,
			'str_httpRequestMethod' => 'post',
			'str_responseType' => 'json',
			'arr_fields' => array(
				'type' => array(
					'preprocessor' => 'preprocess_rowType',
					'description' => '',
					'fieldType' => 'input_output',
					'exceptionSkipsRow' => true
				),
				'sorting' => array(
					'preprocessor' => 'preprocess_sorting',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'publish' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'productcode' => array(
					'preprocessor' => 'preprocess_productcode',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'parentProductcode' => array(
					'preprocessor' => 'preprocess_parentProductcode',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'language' => array(
					'preprocessor' => 'preprocess_language',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'category' => array(
					'preprocessor' => 'preprocess_category',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'taxclass' => array(
					'preprocessor' => 'preprocess_taxclass',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'name' => array(
					'preprocessor' => 'preprocess_name',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'description' => array(
					'preprocessor' => 'preprocess_standard',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'alias' => array(
					'preprocessor' => 'preprocess_standard',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'shortDescription' => array(
					'preprocessor' => 'preprocess_standard',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'producer' => array(
					'preprocessor' => 'preprocess_string_maxlength_255',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'price' => array(
					'preprocessor' => 'preprocess_price',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceType' => array(
					'preprocessor' => 'preprocess_priceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useScalePrice' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceType' => array(
					'preprocessor' => 'preprocess_scalePriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionMethod' => array(
					'preprocessor' => 'preprocess_scalePriceQuantityDetectionMethod',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionAlwaysSeparateConfigurations' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceKeyword' => array(
					'preprocessor' => 'preprocess_string_maxlength_255',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePrice' => array(
					'preprocessor' => 'preprocess_scalePrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPrice' => array(
					'preprocessor' => 'preprocess_oldPrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPriceType' => array(
					'preprocessor' => 'preprocess_oldPriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useOldPrice' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'weight' => array(
					'preprocessor' => 'preprocess_weight',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'weightType' => array(
					'preprocessor' => 'preprocess_weightType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'unit' => array(
					'preprocessor' => 'preprocess_string_maxlength_255',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'quantityComparisonUnit' => array(
					'preprocessor' => 'preprocess_string_maxlength_255',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'quantityComparisonDivisor' => array(
					'preprocessor' => 'preprocess_quantityComparisonDivisor',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'quantityDecimals' => array(
					'preprocessor' => 'preprocess_quantityDecimals',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'new' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'onSale' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'keywords' => array(
					'preprocessor' => 'preprocess_standard',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'image' => array(
					'preprocessor' => 'preprocess_image',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'moreImages' => array(
					'preprocessor' => 'preprocess_moreImages',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'changeStock' => array(
					'preprocessor' => 'preprocess_changeStock',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'settingsForStockAndDeliveryTime' => array(
					'preprocessor' => 'preprocess_settingsForStockAndDeliveryTime',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'recommendedProducts' => array(
					'preprocessor' => 'preprocess_recommendedProducts',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'configurator' => array(
					'preprocessor' => 'preprocess_configurator',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'template' => array(
					'preprocessor' => 'preprocess_string_maxlength_64',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'property1' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property2' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property3' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property4' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property5' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property6' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property7' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property8' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property9' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property10' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property11' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property12' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property13' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property14' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property15' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property16' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property17' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property18' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property19' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'property20' => array(
					'preprocessor' => 'preprocess_attribute',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'value1' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value2' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value3' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value4' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value5' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value6' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value7' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value8' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value9' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value10' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value11' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value12' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value13' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value14' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value15' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value16' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value17' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value18' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value19' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'value20' => array(
					'preprocessor' => 'preprocess_attributeValue',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'propertiesAndValues' => array(
					'preprocessor' => 'preprocess_propertiesAndValues',
					'description' => '',
					'fieldType' => 'output'
				),

				'flex_contents' => array(
					'preprocessor' => 'preprocess_flexContents',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'flex_contentsLanguageIndependent' => array(
					'preprocessor' => 'preprocess_flexContentsLanguageIndependent',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'useGroupPrices_1' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceForGroups_1' => array(
					'preprocessor' => 'preprocess_groupPrices_priceForGroups',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'price_1' => array(
					'preprocessor' => 'preprocess_groupPrices_price',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceType_1' => array(
					'preprocessor' => 'preprocess_groupPrices_priceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useScalePrice_1' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceType_1' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionMethod_1' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePriceQuantityDetectionMethod',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionAlwaysSeparateConfigurations_1' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceKeyword_1' => array(
					'preprocessor' => 'preprocess_string_maxlength_255',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePrice_1' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPrice_1' => array(
					'preprocessor' => 'preprocess_groupPrices_oldPrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPriceType_1' => array(
					'preprocessor' => 'preprocess_groupPrices_oldPriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useOldPrice_1' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'useGroupPrices_2' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceForGroups_2' => array(
					'preprocessor' => 'preprocess_groupPrices_priceForGroups',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'price_2' => array(
					'preprocessor' => 'preprocess_groupPrices_price',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceType_2' => array(
					'preprocessor' => 'preprocess_groupPrices_priceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useScalePrice_2' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceType_2' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionMethod_2' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePriceQuantityDetectionMethod',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionAlwaysSeparateConfigurations_2' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceKeyword_2' => array(
					'preprocessor' => 'preprocess_string_maxlength_255',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePrice_2' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPrice_2' => array(
					'preprocessor' => 'preprocess_groupPrices_oldPrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPriceType_2' => array(
					'preprocessor' => 'preprocess_groupPrices_oldPriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useOldPrice_2' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'useGroupPrices_3' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceForGroups_3' => array(
					'preprocessor' => 'preprocess_groupPrices_priceForGroups',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'price_3' => array(
					'preprocessor' => 'preprocess_groupPrices_price',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceType_3' => array(
					'preprocessor' => 'preprocess_groupPrices_priceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useScalePrice_3' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceType_3' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionMethod_3' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePriceQuantityDetectionMethod',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionAlwaysSeparateConfigurations_3' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceKeyword_3' => array(
					'preprocessor' => 'preprocess_string_maxlength_255',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePrice_3' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPrice_3' => array(
					'preprocessor' => 'preprocess_groupPrices_oldPrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPriceType_3' => array(
					'preprocessor' => 'preprocess_groupPrices_oldPriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useOldPrice_3' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'useGroupPrices_4' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceForGroups_4' => array(
					'preprocessor' => 'preprocess_groupPrices_priceForGroups',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'price_4' => array(
					'preprocessor' => 'preprocess_groupPrices_price',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceType_4' => array(
					'preprocessor' => 'preprocess_groupPrices_priceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useScalePrice_4' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceType_4' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionMethod_4' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePriceQuantityDetectionMethod',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionAlwaysSeparateConfigurations_4' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceKeyword_4' => array(
					'preprocessor' => 'preprocess_string_maxlength_255',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePrice_4' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPrice_4' => array(
					'preprocessor' => 'preprocess_groupPrices_oldPrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPriceType_4' => array(
					'preprocessor' => 'preprocess_groupPrices_oldPriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useOldPrice_4' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),

				'useGroupPrices_5' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceForGroups_5' => array(
					'preprocessor' => 'preprocess_groupPrices_priceForGroups',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'price_5' => array(
					'preprocessor' => 'preprocess_groupPrices_price',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'priceType_5' => array(
					'preprocessor' => 'preprocess_groupPrices_priceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useScalePrice_5' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceType_5' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionMethod_5' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePriceQuantityDetectionMethod',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceQuantityDetectionAlwaysSeparateConfigurations_5' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePriceKeyword_5' => array(
					'preprocessor' => 'preprocess_string_maxlength_255',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'scalePrice_5' => array(
					'preprocessor' => 'preprocess_groupPrices_scalePrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPrice_5' => array(
					'preprocessor' => 'preprocess_groupPrices_oldPrice',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'oldPriceType_5' => array(
					'preprocessor' => 'preprocess_groupPrices_oldPriceType',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'useOldPrice_5' => array(
					'preprocessor' => 'preprocess_pseudoBoolean',
					'description' => '',
					'fieldType' => 'input_output'
				)
			)
		),

		'apiResource_deleteProductData' => array(
			'bln_expectsMultipleDataRows' => true,
			'str_httpRequestMethod' => 'post',
			'str_responseType' => 'json',
			'arr_fields' => array(
				'type' => array(
					'preprocessor' => 'preprocess_rowType',
					'description' => '',
					'fieldType' => 'input_output',
					'exceptionSkipsRow' => true
				),
				'productcode' => array(
					'preprocessor' => 'preprocess_productcode',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'parentProductcode' => array(
					'preprocessor' => 'preprocess_parentProductcode',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'language' => array(
					'preprocessor' => 'preprocess_language',
					'description' => '',
					'fieldType' => 'input_output'
				)
			)
		),

		'apiResource_changeStock' => array(
			'bln_expectsMultipleDataRows' => true,
			'str_httpRequestMethod' => 'post',
			'str_responseType' => 'json',
			'arr_fields' => array(
				'type' => array(
					'preprocessor' => 'preprocess_rowTypeNoLanguage',
					'description' => '',
					'fieldType' => 'input_output',
					'exceptionSkipsRow' => true
				),
				'productcode' => array(
					'preprocessor' => 'preprocess_productcode',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'changeStock' => array(
					'preprocessor' => 'preprocess_changeStock',
					'description' => '',
					'fieldType' => 'input_output'
				)
			)
		),

		'apiResource_getCategoryAliases' => array(
			'arr_fields' => array()
		),

        'apiResource_getCategorys' => array(
            'arr_fields' => array()
        ),

        'apiResource_deleteCategorys' => array(
            'bln_expectsMultipleDataRows' => true,
            'str_httpRequestMethod' => 'post',
            'str_responseType' => 'json',
            'arr_fields' => array(

                'id' => array(
                    'preprocessor' => 'preprocess_categoryId',
                    'description' => 'must be an existing page id or null ',
                    'fieldType' => 'input_output'
                ),
            )
        ),

        'apiResource_writeCategorys' => array(
            'bln_expectsMultipleDataRows' => true,
            'str_httpRequestMethod' => 'post',
            'str_responseType' => 'json',
            'arr_fields' => array(

                'id' => array(
                    'preprocessor' => 'preprocess_categoryId',
                    'description' => 'must be an existing page id or null ',
                    'fieldType' => 'input_output'
                ),
                'parentId' => array(
                    'preprocessor' => 'preprocess_categoryId',
                    'description' => 'must be an existing page id or null ',
                    'fieldType' => 'input_output'
                ),
                'type' => array(
                    'preprocessor' => 'preprocess_categoryRowType',
                    'description' => 'Einer der Werte root, regular, error_xxx',
                    'fieldType' => 'input_output',
                    'exceptionSkipsRow' => true
                ),
                'sorting' => array(
                    'preprocessor' => 'preprocess_categorySorting',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'published' => array(
                    'preprocessor' => 'preprocess_pseudoBoolean',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'language' => array(
                    'preprocessor' => 'preprocess_language',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'title' => array(
                    'preprocessor' => 'preprocess_categoryName',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'alias' => array(
                    'preprocessor' => 'preprocess_urlAlias',
                    'description' => 'string der als eindeutiger identifizierer in der url verwendet werden kann',
                    'fieldType' => 'input_output'
                ),
                'pageTitle' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'description' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),



                'robots' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'redirect' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'jumpTo' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'redirectBack' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'url' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'target' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'dns' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'staticFiles' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'staticPlugins' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'favicon' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'robotsTxt' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'adminEmail' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'dateFormat' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'timeFormat' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'datimFormat' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'validAliasCharacters' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'createSitemap' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'sitemapName' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'useSSL' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'autoforward' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'groups' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'includeLayout' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'layout' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'includeCache' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'cache' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'alwaysLoadFromCache' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'clientCache' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'includeChmod' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'cuser' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'cgroup' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'chmod' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'noSearch' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'requireItem' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'cssClass' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'sitemap' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'hide' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'guests' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'tabindex' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'accesskey' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'start' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'stop' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'enforceTwoFactor' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'twoFactorJumpTo' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'lsShopLayoutForDetailsView' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'lsShopIncludeLayoutforDetailsView' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'lsShopOutputDefinitionSet' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'ls_shop_thousandsSeparator' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'ls_shop_decimalsSeparator' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'ls_shop_currencyBeforeValue' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'ls_shop_useAsCategoryForErp' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),
                'ls_cnc_languageSelector_correspondingMainLanguagePage' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => '',
                    'fieldType' => 'input_output'
                ),

            )
        ),

        'apiResource_getProducts' => array(
            'arr_fields' => array()
        ),

        'apiResource_getManufacturer' => array(
            'arr_fields' => array()
        ),

        'apiResource_getMemberGroups' => array(
            'arr_fields' => array()
        ),

        'apiResource_getShippingMethods' => array(
            'arr_fields' => array()
        ),

        'apiResource_getTaxRates' => array(
            'arr_fields' => array()
        ),

        'apiResource_getCurrency' => array(
            'arr_fields' => array()
        ),

        'apiResource_writeCurrency' => array(
            'bln_expectsMultipleDataRows' => false,
            'str_httpRequestMethod' => 'post',
            'str_responseType' => 'json',
            'arr_fields' => array(

                'id' => array(
                    'preprocessor' => 'preprocess_standard',
                    'description' => 'must be an existing page id or null e.g. €',
                    'fieldType' => 'input_output'
                ),
                'symbol' => array(
                    'preprocessor' => 'preprocess_minLength',
                    'description' => 'Character that represents the currency symbol ',
                    'fieldType' => 'input_output'
                ),
                'isoCode' => array(
                    'preprocessor' => 'preprocess_currencyIso',
                    'description' => 'currency code according to ISO-4217, e.g. EUR',
                    'fieldType' => 'input_output'
                ),
            )
        ),

		'apiResource_getInputPriceType' => array(
			'arr_fields' => array()
		),

		'apiResource_getPriceAndWeightModificationTypes' => array(
			'arr_fields' => array()
		),

		'apiResource_getScalePriceTypes' => array(
			'arr_fields' => array()
		),

		'apiResource_getScalePriceQuantityDetectionMethods' => array(
			'arr_fields' => array()
		),

		'apiResource_getDeliveryInfoTypeAliases' => array(
			'arr_fields' => array()
		),

		'apiResource_getConfiguratorAliases' => array(
			'arr_fields' => array()
		),

		'apiResource_getProductDetailsTemplates' => array(
			'arr_fields' => array()
		),

		'apiResource_getPropertyAndValueAliases' => array(
			'arr_fields' => array()
		),

		'apiResource_getTaxClassAliases' => array(
			'arr_fields' => array()
		),

		'apiResource_getStandardProductImagePath' => array(
			'arr_fields' => array()
		),

		'apiResource_syncDbafs' => array(
			'arr_fields' => array()
		),

		'apiResource_getProductImageNames' => array(
			'arr_fields' => array()
		),

		'apiResource_getProductImageByName' => array(
			'str_httpRequestMethod' => 'get',
			'str_responseType' => 'image',
			'arr_fields' => array(
				'image' => array(
					'preprocessor' => 'preprocess_image',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'width' => array(
					'preprocessor' => 'preprocess_imageSize',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'height' => array(
					'preprocessor' => 'preprocess_imageSize',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'resizeMode' => array(
					'preprocessor' => 'preprocess_contaoImageResizeMode',
					'description' => '',
					'fieldType' => 'input_output'
				),
				'forceRefresh' => array(
					'preprocessor' => 'preprocess_boolean',
					'description' => '',
					'fieldType' => 'input_output'
				)
			)
		)
	);

	/*
	 * This function takes the input data rows and makes sure that each required field exists
	 * and has an acceptable value.
	 */
	public static function preprocess($arr_dataRows, $str_context) {
		if (!key_exists($str_context, self::$arr_resourceAndFieldDefinition)) {
			return null;
		}

		$arr_preprocessingResult = array(
			'bln_hasError' => false,
			'arr_messages' => array(),
			'arr_preprocessedDataRows' => array()
		);

		if (
			!isset(self::$arr_resourceAndFieldDefinition[$str_context]['bln_expectsMultipleDataRows'])
			||	!self::$arr_resourceAndFieldDefinition[$str_context]['bln_expectsMultipleDataRows']
		) {
			$arr_dataRows = array($arr_dataRows);
		}

		foreach ($arr_dataRows as $int_rowNumber => $arr_row) {
			$arr_preprocessingResult['arr_preprocessedDataRows'][$int_rowNumber] = array();

			foreach ($arr_fieldNameToPreprocessorTypeAssignment = self::$arr_resourceAndFieldDefinition[$str_context]['arr_fields'] as $str_fieldName => $arr_fieldDefinition) {
				$var_inputValue = isset($arr_row[$str_fieldName]) ? $arr_row[$str_fieldName] : null;


				try {
					if (
						!is_array($arr_fieldDefinition)
						||	!isset($arr_fieldDefinition['preprocessor'])
						||	!($arr_fieldDefinition['preprocessor'])
					) {
						throw new \Exception('bad preprocessor definition');
					}

					if (!method_exists(__CLASS__, $arr_fieldDefinition['preprocessor'])) {
						throw new \Exception('preprocessor function \''.$arr_fieldDefinition['preprocessor'].'\' does not exist');
					}

					$arr_preprocessingResult['arr_preprocessedDataRows'][$int_rowNumber][$str_fieldName] = call_user_func('self::'.$arr_fieldDefinition['preprocessor'], $var_inputValue, $arr_row, $str_fieldName, $str_context, $arr_preprocessingResult['arr_preprocessedDataRows'][$int_rowNumber]);
				} catch (\Exception $e) {
					$arr_preprocessingResult['bln_hasError'] = true;
					$arr_preprocessingResult['arr_messages'][$int_rowNumber + 1][$str_fieldName] = $e->getMessage();

					if (isset($arr_fieldDefinition['exceptionSkipsRow']) && $arr_fieldDefinition['exceptionSkipsRow']) {
						continue 2;
					}
				}
			}
		}

		if (
			!isset(self::$arr_resourceAndFieldDefinition[$str_context]['bln_expectsMultipleDataRows'])
			||	!self::$arr_resourceAndFieldDefinition[$str_context]['bln_expectsMultipleDataRows']
		) {
			$arr_preprocessingResult['arr_preprocessedDataRows'] = $arr_preprocessingResult['arr_preprocessedDataRows'][0];
		}

		return $arr_preprocessingResult;
	}

	public static function getPreprocessorDescriptions() {
		if (!isset($GLOBALS['merconis_globals']['cache']['arr_productManagementApiPreprocessorDescriptions'])) {
			$arr_preprocessorDescriptions = array();

			$obj_reflection = new \ReflectionClass(__CLASS__);
			$arr_reflectionMethods = $obj_reflection->getMethods();

			if (is_array($arr_reflectionMethods)) {
				foreach ($arr_reflectionMethods as $obj_reflectionMethod) {
					/*
					 * Skip methods with unprefixed names
					 */
					if (strpos($obj_reflectionMethod->name, self::$str_preprocessorMethodNamePrefix) === false) {
						continue;
					}

					$str_preprocessorDescription = $obj_reflectionMethod->getDocComment();

					// clean the comment
					$str_preprocessorDescription = preg_replace('/\h\h+/', '', $str_preprocessorDescription);
					$str_preprocessorDescription = preg_replace('/\*\s?/', '', $str_preprocessorDescription);
					$str_preprocessorDescription = substr($str_preprocessorDescription, 1);
					$str_preprocessorDescription = substr($str_preprocessorDescription, 0, -1);
					$str_preprocessorDescription = trim($str_preprocessorDescription);

					$arr_preprocessorDescriptions[$obj_reflectionMethod->getName()] = $str_preprocessorDescription;
				}
			}

			$GLOBALS['merconis_globals']['cache']['arr_productManagementApiPreprocessorDescriptions'] = $arr_preprocessorDescriptions;
		}

		return $GLOBALS['merconis_globals']['cache']['arr_productManagementApiPreprocessorDescriptions'];
	}



	/**
	 * Expected input: anything
	 * Accepted input: anything
	 * Normalization: nothing
	 */
	protected static function preprocess_standard($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$var_output = $var_input;
		return $var_output;
	}

	/**
	 * Expected input: 'insert', 'update'
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_mode($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = trim($var_input);

		$arr_allowedModes = array('insert', 'update');

		if (!in_array($str_output, $arr_allowedModes)) {
			throw new \Exception('wrong mode given (allowed values: \''.implode('\', \'', $arr_allowedModes).'\')');
		}

		return $str_output;
	}

	/**
	 * Expected input: 'product', 'variant', 'productLanguage' or 'variantLanguage'
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_rowType($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = trim($var_input);

		if (!$str_output) {
			throw new \Exception('no row type given (allowed values: \''.implode('\', \'', ls_shop_productManagementApiHelper::$dataRowTypesInOrderToProcess).'\')');
		}

		if (!in_array($str_output, ls_shop_productManagementApiHelper::$dataRowTypesInOrderToProcess)) {
			throw new \Exception('wrong row type given (allowed values: \''.implode('\', \'', ls_shop_productManagementApiHelper::$dataRowTypesInOrderToProcess).'\')');
		}
		return $str_output;
	}

	/**
	 * Expected input: 'product', 'variant'
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_rowTypeNoLanguage($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = trim($var_input);

		$arr_allowedRowTypes = array();
		foreach (ls_shop_productManagementApiHelper::$dataRowTypesInOrderToProcess as $str_rowType) {
			if (strpos($str_rowType, 'Language') !== false) {
				continue;
			}
			$arr_allowedRowTypes[] = $str_rowType;
		}

		if (!$str_output) {
			throw new \Exception('no row type given (allowed values: \''.implode('\', \'', $arr_allowedRowTypes).'\')');
		}

		if (!in_array($str_output, $arr_allowedRowTypes)) {
			throw new \Exception('wrong row type given (allowed values: \''.implode('\', \'', $arr_allowedRowTypes).'\')');
		}
		return $str_output;
	}

	/**
	 * Expected input: positive integer
	 * Accepted input: anything
	 * Normalization: cast as positive integer
	 */
	protected static function preprocess_sorting($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$int_output = (int) $var_input;
		$int_output = abs($int_output);
		return $int_output;
	}

	/**
	 * Expected input: '1' (representing true) or the empty string (representing false)
	 * Accepted input: anything
	 * Normalization: cast as boolean and return 1 for true and the empty string for false
	 */
	protected static function preprocess_pseudoBoolean($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = $var_input ? '1' : '';
		return $str_output;
	}

	/**
	 * Expected input: '1' (representing true) or the empty string (representing false)
	 * Accepted input: anything
	 * Normalization: cast as boolean
	 */
	protected static function preprocess_boolean($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = (bool) $var_input;
		return $str_output;
	}

	/**
	 * Expected input: any string with up to 255 characters or an empty string. Mandatory for row types 'product' and 'variant'
	 * Accepted input: as expected
	 * Normalization: the input string will be trimmed, i.e. leading and trailing whitespace will be removed
	 */
	protected static function preprocess_productcode($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!$str_output) {
			throw new \Exception('a value is mandatory for row type \''.$arr_row['type'].'\'');
		}

		if (strlen($str_output) > 255) {
			throw new \Exception('the value must not be longer than 255 characters');
		}

		return $str_output;
	}

	/**
	 * Expected input: any string with up to 255 characters or an empty string. Mandatory for all row types except 'product'
	 * Accepted input: as expected
	 * Normalization: the input string will be trimmed, i.e. leading and trailing whitespace will be removed
	 */
	protected static function preprocess_parentProductcode($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('variant', 'productLanguage', 'variantLanguage'))
		) {
			return '';
		}

		if (
			$str_context === 'apiResource_deleteProductData'
			&&	$arr_row['type'] === 'variant'
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!$str_output) {
			throw new \Exception('a value is mandatory for row type \''.$arr_row['type'].'\'');
		}

		if (strlen($str_output) > 255) {
			throw new \Exception('the value must not be longer than 255 characters');
		}

		return $str_output;
	}

	/**
	 * Expected input: the language code of a language used in the contao installation or an empty string
	 * Accepted input: as expected
	 * Normalization: if an empty string is given, the shop's main language/fallback language will be used
	 */
	protected static function preprocess_language($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = trim($var_input);

		if (!$str_output) {
			if (in_array($arr_row['type'], array('productLanguage', 'variantLanguage'))) {
				throw new \Exception('a value is mandatory for row type \''.$arr_row['type'].'\'');
			} else {
				$str_output = ls_shop_languageHelper::getFallbackLanguage();
			}
		}

		if (!in_array($str_output, ls_shop_languageHelper::getAllLanguages())) {
			throw new \Exception('the language code is wrong or the language does not exist in your contao installation');
		}

		return $str_output;
	}

	/**
	 * Expected input: Only for row type 'product': a page alias of a contao main language page where the product should be displayed or a comma separated list of multiple aliases
	 * Accepted input: as expected
	 * Normalization: creating page list
	 */
	protected static function preprocess_category($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		$arr_categories = ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $str_output);

		$arr_pageAliases = ls_shop_productManagementApiHelper::getPageAliases();

        $arr_categoriesToWrite = array();

        if (count($arr_categories)) {
            foreach ($arr_categories as $str_category) {
                if (!in_array($str_category, $arr_pageAliases)) {
                    continue;
                }

                $arr_categoriesToWrite[] = $str_category;
            }
        }

		$str_output = implode(',', $arr_categoriesToWrite);

		$str_output = ls_shop_productManagementApiHelper::generatePageListFromCategoryValue($str_output);

		return $str_output;
	}

	/**
	 * Expected input: an existing tax class alias or an empty string
	 * Accepted input: as expected
	 * Normalization: translate into the taxclass id
	 */
	protected static function preprocess_taxclass($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product'))) {
			return '';
		}

		$str_output = $var_input;

		if (!$str_output) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\'');
		}

		if ($str_output && !ls_shop_productManagementApiHelper::getTaxClassID($str_output)) {
			throw new \Exception('the given tax class alias does not exist');
		}

		$str_output = ls_shop_productManagementApiHelper::getTaxClassID($str_output);

		return $str_output;
	}

	/**
	 * Expected input: any string with 1 to 255 characters
	 * Accepted input: as expected
	 * Normalization: the input string will be trimmed, i.e. leading and trailing whitespace will be removed
	 */
	protected static function preprocess_name($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = trim($var_input);

		$int_length = strlen($str_output);

		if ($int_length < 1) {
			if (
			in_array($arr_row['type'], array('product'))
			) {
				throw new \Exception('the value must be at least one character long');
			}
		}

		if ($int_length > 255) {
			throw new \Exception('the value must not be longer than 255 characters');
		}

		return $str_output;
	}

	/**
	 * Expected input: any string with up to 255 characters or an empty string
	 * Accepted input: as expected
	 * Normalization: the input string will be trimmed, i.e. leading and trailing whitespace will be removed
	 */
	protected static function preprocess_string_maxlength_255($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = trim($var_input);

		$int_length = strlen($str_output);

		if ($int_length > 255) {
			throw new \Exception('the value must not be longer than 255 characters');
		}

		return $str_output;
	}

	/**
	 * Expected input: any string with up to 64 characters or an empty string
	 * Accepted input: as expected
	 * Normalization: the input string will be trimmed, i.e. leading and trailing whitespace will be removed
	 */
	protected static function preprocess_string_maxlength_64($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = trim($var_input);

		$int_length = strlen($str_output);

		if ($int_length > 64) {
			throw new \Exception('the value must not be longer than 64 characters');
		}

		return $str_output;
	}

	/**
	 * Expected input: a numeric value with an optional decimal point and up to 4 decimals. Mandatory for row types 'product' and 'variant'
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_price($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!preg_match('/^-?\d+(\.\d{1,4})?$/', $str_output)) {
			throw new \Exception('not a valid price');
		}

		return $str_output;
	}

	/**
	 * Expected input: 'independent', 'percentaged', 'fixed'. Mandatory for row type 'variant'
	 * Accepted input: as expected
	 * Normalization: translates the value using the modificationTypesTranslationMap
	 */
	protected static function preprocess_priceType($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\'');
		}

		if (!key_exists($str_output, ls_shop_productManagementApiHelper::$modificationTypesTranslationMap)) {
			throw new \Exception('incorrect value given');
		}

		$str_output = ls_shop_productManagementApiHelper::$modificationTypesTranslationMap[$str_output];

		return $str_output;
	}

	/**
	 * Expected input: a numeric value with an optional decimal point and up to 4 decimals. Mandatory for row types 'product' and 'variant'
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_weight($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!preg_match('/^-?\d+(\.\d{1,4})?$/', $str_output)) {
			throw new \Exception('not a valid weight');
		}

		return $str_output;
	}

	/**
	 * Expected input: 'independent', 'percentaged', 'fixed'. Mandatory for row type 'variant'
	 * Accepted input: as expected
	 * Normalization: translates the value using the modificationTypesTranslationMap
	 */
	protected static function preprocess_weightType($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\'');
		}

		if (!key_exists($str_output, ls_shop_productManagementApiHelper::$modificationTypesTranslationMap)) {
			throw new \Exception('incorrect value given');
		}

		$str_output = ls_shop_productManagementApiHelper::$modificationTypesTranslationMap[$str_output];

		return $str_output;
	}

	/**
	 * Expected input: 'scalePriceStandalone', 'scalePricePercentaged', 'scalePriceFixedAdjustment'. Mandatory for row types 'product' and 'variant' if field 'useScalePrice' is 1 (true)
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_scalePriceType($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		if (!$arr_row['useScalePrice']) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useScalePrice\' is 1 (true)');
		}

		if (!in_array($str_output, ls_shop_productManagementApiHelper::$arr_scalePriceTypes)) {
			throw new \Exception('incorrect value given');
		}

		return $str_output;
	}

	/**
	 * Expected input: 'separatedVariantsAndConfigurations', 'separatedVariants', 'separatedProducts', 'separatedScalePriceKeywords'. Mandatory for row types 'product' and 'variant' if field 'useScalePrice' is 1 (true)
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_scalePriceQuantityDetectionMethod($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		if (!$arr_row['useScalePrice']) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useScalePrice\' is 1 (true)');
		}

		if (!in_array($str_output, ls_shop_productManagementApiHelper::$arr_scalePriceQuantityDetectionMethods)) {
			throw new \Exception('incorrect value given');
		}

		return $str_output;
	}

	/**
	 * Expected input: scale price string. Mandatory for row types 'product' and 'variant' if field 'useScalePrice' is 1 (true). A valid scale price string would be '5.5=100.95;10.2=80.1234;20=50'. Regular expression pattern for a scale price string: /^(\d+(\.\d{0,4})?=\d+(\.\d{0,4})?)(;\d+(\.\d{0,4})?=\d+(\.\d{0,4})?)*$/
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_scalePrice($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		if (!$arr_row['useScalePrice']) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useScalePrice\' is 1 (true)');
		}

		if ($str_output && !preg_match('/^(\d+(\.\d{0,4})?=\d+(\.\d{0,4})?)(;\d+(\.\d{0,4})?=\d+(\.\d{0,4})?)*$/', $str_output)) {
			throw new \Exception('not a valid scale price string');
		}

		return $str_output;
	}

	/**
	 * Expected input: a numeric value with an optional decimal point and up to 4 decimals. Mandatory for row types 'product' and 'variant' if field 'useOldPrice' is 1 (true)
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_oldPrice($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		if (!$arr_row['useOldPrice']) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useOldPrice\' is 1 (true)');
		}

		if (!preg_match('/^\d+(\.\d{1,4})?$/', $str_output)) {
			throw new \Exception('not a valid price');
		}

		return $str_output;
	}

	/**
	 * Expected input: 'independent', 'percentaged', 'fixed'. Mandatory for row types 'variant' if field 'useOldPrice' is 1 (true)
	 * Accepted input: as expected
	 * Normalization: translates the value using the modificationTypesTranslationMap
	 */
	protected static function preprocess_oldPriceType($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('variant'))
		) {
			return '';
		}

		if (!$arr_row['useOldPrice']) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useOldPrice\' is 1 (true)');
		}

		if (!key_exists($str_output, ls_shop_productManagementApiHelper::$modificationTypesTranslationMap)) {
			throw new \Exception('incorrect value given');
		}

		$str_output = ls_shop_productManagementApiHelper::$modificationTypesTranslationMap[$str_output];

		return $str_output;
	}

	/**
	 * Expected input: a numeric value with an optional decimal point and up to 4 decimals. Mandatory for row types 'product' and 'variant' if the corresponding field 'useGroupPrices_x' is 1 (true)
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_groupPrices_price($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$int_groupPriceNumber = substr($str_fieldName, -1);

		if (!$arr_row['useGroupPrices_'.$int_groupPriceNumber]) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useGroupPrices_'.$int_groupPriceNumber.'\' is 1 (true)');
		}

		if (!preg_match('/^\d+(\.\d{1,4})?$/', $str_output)) {
			throw new \Exception('not a valid price');
		}

		return $str_output;
	}

	/**
	 * Expected input: a numeric member group id or a comma separated list of numeric member group ids. Mandatory for row types 'product' and 'variant' if the corresponding field 'useGroupPrices_x' is 1 (true)
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_groupPrices_priceForGroups($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$int_groupPriceNumber = substr($str_fieldName, -1);

		if (!$arr_row['useGroupPrices_'.$int_groupPriceNumber]) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useGroupPrices_'.$int_groupPriceNumber.'\' is 1 (true)');
		}

		if (!preg_match('/^\d+(,\d+)*$/', $str_output)) {
			throw new \Exception('incorrect value given');
		}

		return $str_output;
	}

	/**
	 * Expected input: 'independent', 'percentaged', 'fixed'. Mandatory for row types 'product' and 'variant' if the corresponding field 'useGroupPrices_x' is 1 (true)
	 * Accepted input: as expected
	 * Normalization: translates the value using the modificationTypesTranslationMap
	 */
	protected static function preprocess_groupPrices_priceType($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$int_groupPriceNumber = substr($str_fieldName, -1);

		if (!$arr_row['useGroupPrices_'.$int_groupPriceNumber]) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useGroupPrices_'.$int_groupPriceNumber.'\' is 1 (true)');
		}

		if (!key_exists($str_output, ls_shop_productManagementApiHelper::$modificationTypesTranslationMap)) {
			throw new \Exception('incorrect value given');
		}

		$str_output = ls_shop_productManagementApiHelper::$modificationTypesTranslationMap[$str_output];

		return $str_output;
	}

	/**
	 * Expected input: 'scalePriceStandalone', 'scalePricePercentaged', 'scalePriceFixedAdjustment'. Mandatory for row types 'product' and 'variant' if the corresponding field 'useGroupPrices_x' is 1 (true) and field 'useScalePrice_x' is 1 (true)
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_groupPrices_scalePriceType($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$int_groupPriceNumber = substr($str_fieldName, -1);

		if (!$arr_row['useGroupPrices_'.$int_groupPriceNumber]) {
			return '';
		}

		if (!$arr_row['useScalePrice_'.$int_groupPriceNumber]) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useGroupPrices_'.$int_groupPriceNumber.'\' is 1 (true) and field \'useScalePrice_'.$int_groupPriceNumber.'\' is 1 (true)');
		}

		if (!in_array($str_output, ls_shop_productManagementApiHelper::$arr_scalePriceTypes)) {
			throw new \Exception('incorrect value given');
		}

		return $str_output;
	}

	/**
	 * Expected input: 'separatedVariantsAndConfigurations', 'separatedVariants', 'separatedProducts', 'separatedScalePriceKeywords'. Mandatory for row types 'product' and 'variant' if the corresponding field 'useGroupPrices_x' is 1 (true) and field 'useScalePrice_x' is 1 (true)
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_groupPrices_scalePriceQuantityDetectionMethod($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$int_groupPriceNumber = substr($str_fieldName, -1);

		if (!$arr_row['useGroupPrices_'.$int_groupPriceNumber]) {
			return '';
		}

		if (!$arr_row['useScalePrice_'.$int_groupPriceNumber]) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useGroupPrices_'.$int_groupPriceNumber.'\' is 1 (true) and field \'useScalePrice_'.$int_groupPriceNumber.'\' is 1 (true)');
		}

		if (!in_array($str_output, ls_shop_productManagementApiHelper::$arr_scalePriceQuantityDetectionMethods)) {
			throw new \Exception('incorrect value given');
		}

		return $str_output;
	}

	/**
	 * Expected input: scale price string. Mandatory for row types 'product' and 'variant' if the corresponding field 'useGroupPrices_x' is 1 (true) and field 'useScalePrice_x' is 1 (true). A valid scale price string would be '5.5=100.95;10.2=80.1234;20=50'. Regular expression pattern for a scale price string: /^(\d+(\.\d{0,4})?=\d+(\.\d{0,4})?)(;\d+(\.\d{0,4})?=\d+(\.\d{0,4})?)*$/
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_groupPrices_scalePrice($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$int_groupPriceNumber = substr($str_fieldName, -1);

		if (!$arr_row['useGroupPrices_'.$int_groupPriceNumber]) {
			return '';
		}

		if (!$arr_row['useScalePrice_'.$int_groupPriceNumber]) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useGroupPrices_'.$int_groupPriceNumber.'\' is 1 (true) and field \'useScalePrice_'.$int_groupPriceNumber.'\' is 1 (true)');
		}

		if ($str_output && !preg_match('/^(\d+(\.\d{0,4})?=\d+(\.\d{0,4})?)(;\d+(\.\d{0,4})?=\d+(\.\d{0,4})?)*$/', $str_output)) {
			throw new \Exception('not a valid scale price string');
		}

		return $str_output;
	}

	/**
	 * Expected input: a numeric value with an optional decimal point and up to 4 decimals. Mandatory for row types 'product' and 'variant' if the corresponding field 'useGroupPrices_x' is 1 (true) and field 'useOldPrice_x' is 1 (true).
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_groupPrices_oldPrice($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$int_groupPriceNumber = substr($str_fieldName, -1);

		if (!$arr_row['useGroupPrices_'.$int_groupPriceNumber]) {
			return '';
		}

		if (!$arr_row['useOldPrice_'.$int_groupPriceNumber]) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useGroupPrices_'.$int_groupPriceNumber.'\' is 1 (true) and field \'useOldPrice_'.$int_groupPriceNumber.'\' is 1 (true)');
		}

		if (!preg_match('/^\d+(\.\d{1,4})?$/', $str_output)) {
			throw new \Exception('not a valid price');
		}

		return $str_output;
	}

	/**
	 * Expected input: 'independent', 'percentaged', 'fixed'. Mandatory for row types 'variant' if the corresponding field 'useGroupPrices_x' is 1 (true) and field 'useOldPrice_x' is 1 (true).
	 * Accepted input: as expected
	 * Normalization: translates the value using the modificationTypesTranslationMap
	 */
	protected static function preprocess_groupPrices_oldPriceType($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('variant'))
		) {
			return '';
		}

		$int_groupPriceNumber = substr($str_fieldName, -1);

		if (!$arr_row['useGroupPrices_'.$int_groupPriceNumber]) {
			return '';
		}

		if (!$arr_row['useOldPrice_'.$int_groupPriceNumber]) {
			return '';
		}

		$str_output = trim($var_input);

		if (!strlen($str_output)) {
			throw new \Exception('a value is mandatory for row type \'' . $arr_row['type'] . '\' when field \'useGroupPrices_'.$int_groupPriceNumber.'\' is 1 (true) and field \'useOldPrice_'.$int_groupPriceNumber.'\' is 1 (true)');
		}

		if (!key_exists($str_output, ls_shop_productManagementApiHelper::$modificationTypesTranslationMap)) {
			throw new \Exception('incorrect value given');
		}

		$str_output = ls_shop_productManagementApiHelper::$modificationTypesTranslationMap[$str_output];

		return $str_output;
	}

	/**
	 * Expected input: a numeric value with an optional decimal point and up to 6 decimals or an empty string
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_quantityComparisonDivisor($var_input) {
		$str_output = trim($var_input);

		if ($str_output && !preg_match('/^\d+(\.\d{1,6})?$/', $str_output)) {
			throw new \Exception('not a valid value');
		}

		return $str_output;
	}

	/**
	 * Expected input: positive integer
	 * Accepted input: anything
	 * Normalization: cast as positive integer
	 */
	protected static function preprocess_quantityDecimals($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$int_output = (int) $var_input;
		$int_output = abs($int_output);
		return $int_output;
	}

	/**
	 * Expected input: a numeric value with an optional decimal point and up to 4 decimals and optionally a preceding plus sign or minus sign or an empty string
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_changeStock($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = trim($var_input);

		if (
			$str_context === 'apiResource_changeStock'
			&&	(
				$str_output === null
				||	$str_output === ''
				||	$str_output === false
			)
		) {
			throw new \Exception('a value is mandatory');
		}

		if ($str_output && !preg_match('/^(\+|-)?\d+(\.\d{1,4})?$/', $str_output)) {
			throw new \Exception('not a valid value');
		}

		return $str_output;
	}

	/**
	 * Expected input: an image file name of an image located in the merconis standard product image folder or an empty string.
	 * Accepted input: as expected
	 * Normalization: translates an existing image file name into the dbafs uuid
	 */
	protected static function preprocess_image($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
			$str_context == 'apiResource_writeProductData'
			&&	!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (
			$str_context == 'apiResource_writeProductData'
			&&	!$str_output
		) {
			return '';
		}

		if (strpos($str_output, ',') !== false) {
			throw new \Exception('the image name must not contain commas');
		}

		$str_output = $str_output ? ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImageFolder']).'/'.$str_output : '';

		$obj_imageModels = \FilesModel::findMultipleByPaths(array($str_output));

		if ($obj_imageModels !== null) {
			$str_output = $obj_imageModels->first()->uuid;
		} else {
			throw new \Exception('the image file does not exist in the merconis standard product image folder');
		}

		return $str_output;
	}

	/**
	 * Expected input: an image file name of an image located in the merconis standard product image folder or multiple comma separated image file names or an empty string.
	 * Accepted input: as expected
	 * Normalization:
	 */
	protected static function preprocess_moreImages($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!$str_output) {
			return '';
		}

		$str_output = ls_shop_productManagementApiHelper::prepareMoreImages($str_output);

		return $str_output;
	}

	/**
	 * Expected input: an existing alias of a stock and delivery info record or an empty string if the default should be used.
	 * Accepted input: as expected
	 * Normalization: translates into the stock and delivery info record id
	 */
	protected static function preprocess_settingsForStockAndDeliveryTime($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!$str_output) {
			return 0;
		}

		if (!in_array($str_output, ls_shop_productManagementApiHelper::getDeliveryInfoTypeAliases())) {
			throw new \Exception('the given alias does not exist');
		}

		$str_output = ls_shop_productManagementApiHelper::getDeliveryInfoSetID($str_output);

		return $str_output;
	}

	/**
	 * Expected input: a comma separated list containing the product codes of recommended products or an empty string
	 * Accepted input: as expected
	 * Normalization: creates a ready-to-save recommended product collection
	 */
	protected static function preprocess_recommendedProducts($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		/*
		 * WICHTIG: Der eingetragene Wert für "recommendedProducts" ist nicht vollständig korrekt, da er nicht die benötigten IDS
		 * sondern die Artikelnummern enthält, und daher nur von temporärer Natur. Nach erfolgtem Import muss für alle Produkte
		 * daher eine entsprechende Übersetzung dieses Wertes stattfinden.
		 */
		$str_output = ls_shop_productManagementApiHelper::prepareRecommendedProducts($str_output);

		return $str_output;
	}

	/**
	 * Expected input: an existing alias of a configurator record or an empty string if no configurator should be used.
	 * Accepted input: as expected
	 * Normalization: translates into the configurator record id
	 */
	protected static function preprocess_configurator($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!$str_output) {
			return 0;
		}

		if (!in_array($str_output, ls_shop_productManagementApiHelper::getConfiguratorAliases())) {
			throw new \Exception('the given alias does not exist');
		}

		$str_output = ls_shop_productManagementApiHelper::getConfiguratorID($str_output);

		return $str_output;
	}

	/**
	 * Expected input: an existing property alias or an empty string
	 * Accepted input: as expected
	 * Normalization: translates into the property id
	 */
	protected static function preprocess_attribute($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!$str_output) {
			return 0;
		}

		$arr_attributeAndValueAliases = ls_shop_productManagementApiHelper::getAttributeAndValueAliases();

		if (!in_array($str_output, $arr_attributeAndValueAliases['attributeAliases'])) {
			throw new \Exception('given property alias does not exist');
		}

		$str_output = ls_shop_productManagementApiHelper::getAttributeIDForAlias($str_output);

		return $str_output;
	}

	/**
	 * Expected input: an existing property value alias or an empty string
	 * Accepted input: as expected
	 * Normalization: translates into the property value id
	 */
	protected static function preprocess_attributeValue($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!$str_output) {
			return 0;
		}

		$arr_attributeAndValueAliases = ls_shop_productManagementApiHelper::getAttributeAndValueAliases();

		if (!in_array($str_output, $arr_attributeAndValueAliases['attributeValueAliases'])) {
			throw new \Exception('given property value alias does not exist');
		}

		$str_output = ls_shop_productManagementApiHelper::getAttributeValueIDForAlias($str_output);

		$int_attributeValueFieldNumber = preg_replace('/[^\d]/', '', $str_fieldName);

		if (!ls_shop_generalHelper::checkIfAttributeAndValueBelongTogether($arr_normalizedRow['property'.$int_attributeValueFieldNumber], $str_output)) {
			throw new \Exception('given property value does not belong to the given property');
		}

		return $str_output;
	}

	protected static function preprocess_propertiesAndValues($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$arr_propertiesAndValues = array();

		for ($i = 1; $i <= ls_shop_productManagementApiHelper::$int_numImportableAttributesAndValues; $i++) {
			if (!$arr_normalizedRow['property'.$i] || !$arr_normalizedRow['value'.$i]) {
				continue;
			}
			$arr_propertiesAndValues[] = array(
				$arr_normalizedRow['property'.$i],
				$arr_normalizedRow['value'.$i]
			);
		}

		return json_encode($arr_propertiesAndValues);
	}

	/**
	 * Expected input: An empty string if flex contents should not be used. If they should be used, either a string like [[&quot;flexkey1&quot;,&quot;flexvalue1&quot;],[&quot;flexkey2&quot;,&quot;flexvalue2&quot;]] or a comma separated list of field names that are not part of the standard field set expected by the api (but are being sent with the api request anyway) and that should be used as flex contents. The field names will be used as flex content keys and the respective field values are used as the flex content values.
	 * Accepted input: as expected
	 * Normalization: an import-ready flex content collection will be created
	 */
	protected static function preprocess_flexContents($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		$str_output = trim($var_input);

		$arr_flexContents = json_decode($str_output, true);

		if (!is_array($arr_flexContents)) {
            $arr_flexContentFields = ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $str_output);
            $arr_flexContents = array();

            foreach ($arr_flexContentFields as $str_flexContentFieldKey) {
                $arr_flexContents[] = array(
                    $str_flexContentFieldKey,
                    isset($arr_row[$str_flexContentFieldKey]) ? $arr_row[$str_flexContentFieldKey] : ''
                );
            }
        }

        $str_output = json_encode($arr_flexContents);

		return $str_output;
	}

	/**
	 * Expected input: An empty string if flex contents should not be used. If they should be used, either a string like [[&quot;flexkey1&quot;,&quot;flexvalue1&quot;],[&quot;flexkey2&quot;,&quot;flexvalue2&quot;]] or a comma separated list of field names that are not part of the standard field set expected by the api (but are being sent with the api request anyway) and that should be used as flex contents. The field names will be used as flex content keys and the respective field values are used as the flex content values.
	 * Accepted input: as expected
	 * Normalization: an import-ready flex content collection will be created
	 */
	protected static function preprocess_flexContentsLanguageIndependent($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($arr_row['type'], array('product', 'variant'))
		) {
			return '';
		}

		$str_output = trim($var_input);

        $arr_flexContents = json_decode($str_output, true);

        if (!is_array($arr_flexContents)) {
            $arr_flexContentFields = ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $str_output);
            $arr_flexContents = array();

            foreach ($arr_flexContentFields as $str_flexContentFieldKey) {
                $arr_flexContents[] = array(
                    $str_flexContentFieldKey,
                    isset($arr_row[$str_flexContentFieldKey]) ? $arr_row[$str_flexContentFieldKey] : ''
                );
            }
        }

		$str_output = json_encode($arr_flexContents);

		return $str_output;
	}

	/**
	 * Expected input: positive integer greater than 0
	 * Accepted input: as expected
	 * Normalization: cast as positive integer
	 */
	protected static function preprocess_imageSize($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($str_context, array('apiResource_getProductImageByName'))
		) {
			return '';
		}

		$int_output = (int) $var_input;
		$int_output = abs($int_output);

		if (!$int_output) {
			throw new \Exception('positive value expected');
		}

		return $int_output;
	}

	/**
	 * Expected input: string; one of the contao image resize modes (see $GLOBALS['TL_CROP'])
	 * Accepted input: as expected
	 * Normalization: none
	 */
	protected static function preprocess_contaoImageResizeMode($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
		if (
		!in_array($str_context, array('apiResource_getProductImageByName'))
		) {
			return '';
		}

		$str_output = trim($var_input);

		if (!$str_output) {
			throw new \Exception('no resize mode given');
		}

		if (
			!in_array($str_output, $GLOBALS['TL_CROP']['relative'])
			&&	!in_array($str_output, $GLOBALS['TL_CROP']['exact'])
		) {
			throw new \Exception('unsupported resize mode given');
		}

		return $str_output;
	}


    /**
     * Expected input: 'root', 'regular', 'error_403' or 'error_401'
     * Accepted input: as expected
     * Normalization: none
     */
    protected static function preprocess_categoryRowType($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
        $str_output = trim($var_input);

        if (!$str_output) {
            throw new \Exception('no row type given (allowed values: \''.implode('\', \'', ls_shop_productManagementApiHelper::$dataCategoryRowTypesInOrderToProcess).'\')');
        }

        if (!in_array($str_output, ls_shop_productManagementApiHelper::$dataCategoryRowTypesInOrderToProcess)) {
            throw new \Exception('wrong row type given (allowed values: \''.implode('\', \'', ls_shop_productManagementApiHelper::$dataCategoryRowTypesInOrderToProcess).'\')');
        }
        return $str_output;
    }

    /**
     * Expected input: an existing page id or null
     * Accepted input: as expected
     * Normalization: none
     */
    protected static function preprocess_categoryId($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
        $str_output = trim($var_input);

        if ($var_input ) {
            $arr_result = ls_shop_generalHelper::ls_shop_getPageDataForID($var_input);

            if (count($arr_result) == 0) {
                throw new \Exception('this page Id does not existes: \''.$var_input.'\')');
            }
        }
        return $str_output;
    }


    /**
     * Expected input: any string with 1 to 255 characters
     * Accepted input: as expected
     * Normalization: the input string will be trimmed, i.e. leading and trailing whitespace will be removed
     */
    protected static function preprocess_categoryName($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
        $str_output = trim($var_input);

        $int_length = strlen($str_output);

        if ($int_length < 1) {
            if (
            in_array($arr_row['type'],
                ls_shop_productManagementApiHelper::$dataCategoryRowTypesInOrderToProcess
                )

            ) {
                throw new \Exception('the value must be at least one character long');
            }
        }

        if ($int_length > 255) {
            throw new \Exception('the value must not be longer than 255 characters');
        }

        return $str_output;
    }

    /**
     * Expected input: positive integer
     * Accepted input: anything
     * Normalization: cast as positive integer
     */
    protected static function preprocess_categorySorting($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
        if (
        !in_array($arr_row['type'], array('regular', 'root', 'error_401', 'error_403', 'error_404'))
        ) {
            return '';
        }

        $int_length = strlen($var_input);
        if ($int_length < 1) {
            throw new \Exception('the value must be at least one character long');
        }

        $int_output = (int) $var_input;
        $int_output = abs($int_output);
        return $int_output;
    }


    /**
     * Expected input: String: Der Seitenalias ist eine eindeutige Referenz, die anstelle der numerischen Seiten-ID aufgerufen werden kann
     * Die Funktion wirft entweder einen Fehler auf, wenn der Alias völlig ungültig ist und formatiert ihn andernfalls in einen
     * gültigen URL-Schlüssel
     * Accepted input: Buchstaben, Zahlen und die Sonderzeichen: .-/_
     * Normalization: cast as url valid string
     */
    protected static function preprocess_urlAlias($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {
        if (
        preg_match( '/[^a-zA-Z0-9 \.\/\-_]/',$var_input)
        ) {
            throw new \Exception('the value contains forbidden characters. (Allowed: a-z, 0-9, . - _ /)');
        }

        //TODO: prüfen, ob die URL schon besteht

        $str_output = trim($var_input);
        $str_output = strtolower($str_output);
        $str_output = str_replace(' ', '-', $str_output);
        return $str_output;
    }


    /**
     * Expected input: Text
     * Accepted input: Mindestens ein Zeichen länge
     * Normalization: the input string will be trimmed
     */
    protected static function preprocess_minLength($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {

        $str_output = trim($var_input);

        $int_length = strlen($str_output);

        if ($int_length < 1) {
            throw new \Exception('the value must be at least one character long');
        }

        return $str_output;
    }

    /**
     * Expected input: String als ISO Code für die Währung
     * Accepted input: 3 Buchstaben die einer Währung gemäß ISO 4217 entspricht
     * Normalization: the input string will be trimmed
     */
    protected static function preprocess_currencyIso($var_input, $arr_row, $str_fieldName, $str_context, $arr_normalizedRow) {

        $var_input = trim($var_input);
        $str_output = strtoupper($var_input);

        if (
            !in_array($str_output, array('AED','AFN','ALL','AMD','ANG','AOA','ARS','AUD','AWG','AZN','BAM','BBD','BDT','BGN','BHD','BIF','BMD','BND','BOB','BOV','BRL','BSD','BTN','BWP','BYN','BZD','CAD','CDF','CHE','CHF','CHW','CLF','CLP','CNY','COP','COU','CRC','CUC','CUP','CVE','CZK','DJF','DKK','DOP','DZD','EGP','ERN','ETB','EUR','FJD','FKP','GBP','GEL','GHS','GIP','GMD','GNF','GTQ','GYD','HKD','HNL','HRK','HTG','HUF','IDR','ILS','INR','IQD','IRR','ISK','JMD','JOD','JPY','KES','KGS','KHR','KMF','KPW','KRW','KWD','KYD','KZT','LAK','LBP','LKR','LRD','LSL','LYD','MAD','MDL','MGA','MKD','MMK','MNT','MOP','MRU','MUR','MVR','MWK','MXN','MXV','MYR','MZN','NAD','NGN','NIO','NOK','NPR','NZD','OMR','PAB','PEN','PGK','PHP','PKR','PLN','PYG','QAR','RON','RSD','RUB','RWF','SAR','SBD','SCR','SDG','SEK','SGD','SHP','SLL','SOS','SRD','SSP','STN','SVC','SYP','SZL','THB','TJS','TMT','TND','TOP','TRY','TTD','TWD','TZS','UAH','UGX','UAH','UYI','UYU','UYW','UZS','VES','VND','VUV','WST','XAF','XCD','XOF','XPF','XSU','YER','ZAR','ZMW','ZWL'))
            ) {
            throw new \Exception('the value have to be one of accepted ISO Codes');
        }

        return $str_output;
    }
}