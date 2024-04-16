<?php

	/*
	 * Fields
	 */
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopProductVariantAttributesValues'] 				= array('Allocating a variant attribute to a value','Select attributes and values which you have already defined under menu item "Product variant attributes".');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['title']												= array('Product variant designation');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['alias']												= array('Alias');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['description']										= array('Product variant description');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['shortDescription']									= array('Brief description');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopRuntimeFrom']                                  = array('Runtime from','Defines the start of the collective purchase for the product.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopRuntimeUntil']                                 = array('Runtime until','Defines the end of the collective purchase for the product.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopDeliveryDate']                                 = array('Delivery date','Indication of the expected delivery date. Pure output field with no functional significance.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopMinimumOrders']                                = array('Minimum sales quantity','Specifies the minimum number of items sold for the collective purchase to be realized.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopMaximumOrders']                                = array('Maximum sales quantity','Indication of the maximum number of items sold.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVpe']                                          = array('Unit (PU)','Packaging unit (PU), in which packaged quantities can the product be purchased.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopMinimumCustomerOrders']                        = array('Minimum order quantity','The minimum quantity of the individual product (not PU) that the buyer must order.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['published']											= array('Published');

$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useGroupPrices_1']									=	array('Use deviant prices for group 1');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useGroupPrices_2']									=	array('Use deviant prices for group 2');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useGroupPrices_3']									=	array('Use deviant prices for group 3');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useGroupPrices_4']									=	array('Use deviant prices for group 4');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useGroupPrices_5']									=	array('Use deviant prices for group 5');

$GLOBALS['TL_LANG']['tl_ls_shop_variant']['priceForGroups']										=	array('Valid for groups');
	
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPrice']									= array('Price','Enter the price for this variant here. Please define the type of price indication in the opposite choice box. If you use scale prices, please enter the price for one unit here.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceType']								= array('Type of price indication');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useScalePrice']										=	array('Use scale price');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceType']										=	array('Scale price type');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionMethod']					=	array('Quantity detection method');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionAlwaysSeparateConfigurations']	=	array('Always separate different configurations', 'Products and variants which have been individualized using a configurator will not be summarized with products and variants whose configuration differs.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceKeyword']									=	array('Scale price keyword', 'Enter a keyword that can be used to summarize multiple cart items for the quantity detection.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePrice']											=	array('Scale price (left: from which quantity, right: which price or which price adjustment)');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceOld']								= array('Old price','Enter the old price for this variant here. Please define the type of price indication in the opposite choice box.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceTypeOld']							= array('Old price: Type of price indication');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantWeight']								= array('Weight','Enter the weight for this variant here. Please define the type of weight indication in the opposite choice box.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantWeightType']							= array('Type of weight indication');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantQuantityUnit']						=	array('Deviant quantity unit', 'Only if different from the main product: Enter the quantity unit here (e.g. piece, litre, running metre etc.).');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantMengenvergleichUnit']				=	array('Deviant unit for quantity comparative price', 'Only if different from the main product: Enter "100 g", for example, if you wish to convert the price of a package to 100 g for better comparability and if you also wish to display it accordingly. Use the place holder "%s" to insert the calculated quantity comparative price. This enables you to realize, for example, an indication like "corresponds to 1,95 EUR per 100 g".');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantMengenvergleichDivisor']			=	array('Deviant divisor for quantity comparative price calculation', 'Only if different from the main product: Enter the divisor here with which the quantity comparative price is calculated. You can determine this divisor by dividing the quantity of a package by the comparative quantity. Example: You have a product in a 275 g package and wish to display the quantity comparative price for 100 g. Enter "2.75" (275 g / 100 g = 2,75) in this case. If you wish to display the price for a comparative quantity of 1000 g, please enter "0.275" (275 g / 1000 g = 0,275).');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantCode']									= array('Product code','Enter the product code for this variant here. Please define the type of indication in the opposite choice box.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopProductVariantMainImage']						= array('Variant image', 'Please select the main image for this variant here.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopProductVariantMoreImages']						= array('Further images', 'Please select further images for this variant here.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantDeliveryInfoSet']						= array('Settings on goods in stock/delivery time');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['overrideAvailabilitySettingsOfParentProduct']		=	array('Override availability and pre-order settings of the main product');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['availableFrom']				                	=	array('Available from');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['preorderingAllowed']				                =	array('Pre-orderable', 'If this option is enabled, the product can be pre-ordered if it is not yet available. Regardless of the stock handling setting assigned to the product, the stock level can run into the negative if the pre-order function is activated.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['deliveryInfoSetToUseInPreorderPhase']            =   array('Deviating settings on goods in stock/delivery time in pre-order phase');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantDeliveryTime']							= array('Delivery time');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['configurator']										= array('Use configurator');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['customizerLogicFile']  							= array('Use customizer file');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useOldPrice']										= array('Use old price');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contents']										= array('Flexible variant information','You can add as many information as you like and you can use them in the templates as product properties by referencing them with their keyword.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contentsLanguageIndependent']					= array('Flexible variant information (language independent)','You can add as many information as you like and you can use them in the templates as product properties by referencing them with their keyword.');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['associatedProducts']								=	array('Associated products', 'Select products here that you want to associate with this variant with regard to implementing individual functions.');


/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantCode_legend']							= 'Product code';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopCollectiveOrder_legend']						= 'Collective buying settings';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopStatus_legend']								= 'Status';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopTitleAndDescriptions_legend']					= 'Designation and descriptions';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopUnits_legend']									= 'Units (language-dependent)';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopImages_legend']								= 'Images';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantAttributesAndValues_legend']			= 'Variant attributes and values';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopPrice_legend']									= 'Price and weight indication';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopPrice_1_legend']								= 'Deviant prices for group no. 1';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopPrice_2_legend']								= 'Deviant prices for group no. 2';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopPrice_3_legend']								= 'Deviant prices for group no. 3';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopPrice_4_legend']								= 'Deviant prices for group no. 4';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopPrice_5_legend']								= 'Deviant prices for group no. 5';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopStockDeliveryTimeAndAvailability_legend']									= 'Goods in stock, delivery time and availability';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['configurator_legend']								= 'Configurator settings';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['associatedProducts_legend']							= 'Associated products';



/*
 * Options
 */
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] = array(
	'standalone' => array('Stand-alone price in '.($GLOBALS['TL_CONFIG']['ls_shop_currency'] ? $GLOBALS['TL_CONFIG']['ls_shop_currency'] : '<i>&lt;No currency defined yet in the basic settings&gt;</i>'),'The stated price will completely replace the price stated in the main product.'),
	'adjustmentPercentaged' => array('Percentage adjustment','The stated price is a percentage adjustment of the price stated in the main product.'),
	'adjustmentFix' => array('Adjustment with fixed price in '.($GLOBALS['TL_CONFIG']['ls_shop_currency'] ? $GLOBALS['TL_CONFIG']['ls_shop_currency'] : '<i>&lt;no currency defined yet in the basic settings&gt;</i>'),'The stated price is a fixed surcharge or discount on the main product price.')
);

$GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantWeightType'] = array(
	'standalone' => array('Stand-alone weight in '.($GLOBALS['TL_CONFIG']['ls_shop_weightUnit'] ? $GLOBALS['TL_CONFIG']['ls_shop_weightUnit'] : '<i>&lt;No weight unit defined yet in the basic settings&gt;</i>'),'The stated weight will completely replace the weight stated in the main product.'),
	'adjustmentPercentaged' => array('Percentage adjustment','The stated weight is a percentage adjustment of the weight stated in the main product.'),
	'adjustmentFix' => array('Adjustment with fixed weight in '.($GLOBALS['TL_CONFIG']['ls_shop_weightUnit'] ? $GLOBALS['TL_CONFIG']['ls_shop_weightUnit'] : '<i>&lt;No weight unit defined yet in the basic settings&gt;</i>'),'The stated weight is a fixed plus or minus on the weight of the main product.')
);

$GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceType'] = array(
	'scalePriceStandalone' => array('Fixed scale price', 'The given price will be used directly'),
	'scalePricePercentaged' => array('Percentaged adjustment', 'The given percentaged adjustment will be applied to the basic product price. Please precede the given value with a minus sign to create a deduction.'),
	'scalePriceFixedAdjustment' => array('Adjustment with a fixed value', 'The given value will be applied as an adjustment to the basic product price. Please precede the given value with a minus sign to create a deduction.')
);

$GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceQuantityDetectionMethod'] = array(
	'separatedVariantsAndConfigurations' => array('Separated by products, variants and configurations', 'Every cart item will be counted separately, no matter if the product, it\'s variant or even only it\'s configuration differs.'),
	'separatedVariants' => array('Separated by products and variants', 'Cart items only differing in their configuration will be summarized. Different variants will be counted separately.'),
	'separatedProducts' => array('Separated by products', 'Different products in the cart will be counted separately but different variants and configurations will be summarized.'),
	'separatedScalePriceKeywords' => array('Summarized by scale price keyword', 'Cart items with the same scale price keyword will be summarized. Apart from that, different cart items will be counted separately.')
);

/*
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['new']        = array('New variant', 'Define a new variant');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['edit']        = array('Edit variant', 'Edit variant with ID %s');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['editheader']  = array('Edit product', 'Edit product');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['delete']      = array('Delete variant', 'Delete variant ID %s');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['copy']        = array('Copy variant', 'Copy variant with ID %s');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['cut']        = array('Move variant', 'Move variant with ID %s');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['show']        = array('Show details', 'Show details of the variant with ID %s');
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['toggle']        = array('Publish variant', 'Publish variant ID %s');

/*
 * Misc
 */
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contents_label01'] = 'Keyword';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contents_label02'] = 'Variant information';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contentsLanguageIndependent_label01'] = 'Keyword';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contentsLanguageIndependent_label02'] = 'Variant information';

$GLOBALS['TL_LANG']['tl_ls_shop_variant']['attributesValues_label01'] = 'Attribute';
$GLOBALS['TL_LANG']['tl_ls_shop_variant']['attributesValues_label02'] = 'Value';
