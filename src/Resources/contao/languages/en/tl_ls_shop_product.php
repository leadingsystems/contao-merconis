<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['id']												=	array('ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['artType']										=	array('Type of product', 'Select here whether you wish to define a product or a foreign language product.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['pages']											=	array('Pages on which the product will be displayed','Select the page here on which the product will be displayed. Should you run a multilingual shop, please only select the pages in the main language.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['title']											=	array('Product designation');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['alias']											=	array('Alias');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductAlias']								=	array('Product alias','Will be filled in automatically if you do not make a manual entry.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['description']									=	array('Product description');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['shortDescription']								=	array('Brief description');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['published']										=	array('Published');

    $GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupRestrictions']							=	array('Group Restrictions');
    $GLOBALS['TL_LANG']['tl_ls_shop_product']['allowedGroups']							        =	array('Allowed for the following groups');

    $GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_1']								=	array('Use deviant prices for group 1');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_2']								=	array('Use deviant prices for group 2');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_3']								=	array('Use deviant prices for group 3');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_4']								=	array('Use deviant prices for group 4');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_5']								=	array('Use deviant prices for group 5');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups']									=	array('Valid for groups');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice']								=	array('Price in '.($GLOBALS['TL_CONFIG']['ls_shop_currency'] ? $GLOBALS['TL_CONFIG']['ls_shop_currency'] : '<i>&lt;No currency defined yet in the basic settings&gt;</i>'), 'It depends on the global basic shop setting whether a gross or net price is stored here. If you use scale prices, please enter the price for one unit here.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice']									=	array('Use scale price');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType']									=	array('Scale price type');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod']				=	array('Quantity detection method');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations']	=	array('Always separate different configurations', 'Products and variants which have been individualized using a configurator will not be summarized with products and variants whose configuration differs.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword']								=	array('Scale price keyword', 'Enter a keyword that can be used to summarize multiple cart items for the quantity detection.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePrice']										=	array('Scale price (left: from which quantity, right: which price or which price adjustment)');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld']							=	array('Old price in '.($GLOBALS['TL_CONFIG']['ls_shop_currency'] ? $GLOBALS['TL_CONFIG']['ls_shop_currency'] : '<i>&lt;No currency defined yet in the basic settings&gt;</i>'), 'It depends on the global basic shop setting whether a gross or net price is stored here.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductSteuersatz']						=	array('Tax rate', 'Select the tax rate here by which this product will be taxed.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductCode']								=	array('Product code');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductWeight']							=	array('Weight in '.($GLOBALS['TL_CONFIG']['ls_shop_weightUnit'] ? $GLOBALS['TL_CONFIG']['ls_shop_weightUnit'] : '<i>&lt;No weight unit defined yet in the basic settings&gt;</i>'), 'The product weight can be of great significance for the calculation of the shipping costs etc.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductQuantityUnit']						=	array('Quantity unit', 'Enter the quantity unit here (e.g. piece, litre, running metre etc.).');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductQuantityDecimals']					=	array('Decimal places for quantity', 'Define here how many decimal places for the quantity shall be permitted. If a product is sold per piece, then enter "0" so that only whole pieces can be ordered. If a product is sold per running metre, then enter "2" to enable the customer to also order 2,75 running metres etc.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMengenvergleichUnit']				=	array('Unit for quantity comparative price', 'Enter "100 g", for example, if you wish to convert the price of a package to 100 g for better comparability and if you also wish to display it accordingly. Use the place holder "%s" to insert the calculated quantity comparative price. This enables you to realize, for example, an indication like "corresponds to 1,95 EUR per 100 g".');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMengenvergleichDivisor']			=	array('Divisor for quantity comparative price calculation', 'Enter the divisor here with which the quantity comparative price is calculated. You can determine this divisor by dividing the quantity of a package by the comparative quantity. Example: You have a product in a 275 g package and wish to display the quantity comparative price for 100 g. Enter "2.75" (275 g / 100 g = 2,75) in this case. If you wish to display the price for a comparative quantity of 1000 g, please enter "0.275" (275 g / 1000 g = 0,275).');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMainImage']							=	array('Product image', 'Select the main image for this product here.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMoreImages']						=	array('Further images', 'Select further images for this product here.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductDetailsTemplate']					=	array('Template for product detail display');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductIsNew']								=	array('New product');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductIsOnSale']							=	array('Special offer');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductDeliveryInfoSet']					=	array('Settings on goods in stock/delivery time');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductDeliveryTime']						=	array('Delivery time');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductRecommendedProducts']				=	array('Recommended products', 'Select products here that can be displayed in a corresponding CrossSeller.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['associatedProducts']								=	array('Associated products', 'Select products here that you want to associate with this product with regard to implementing individual functions.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductProducer']							= 	array('Manufacturer');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['keywords']										= 	array('Keywords');
    $GLOBALS['TL_LANG']['tl_ls_shop_product']['pageTitle']										=	array('Page title', 'Enter the page title to be used on the product detail page. You can use it to improve the importance of the product detail page for search engines. If you do not enter anything, the product name will precede the regular Contao page title.');
    $GLOBALS['TL_LANG']['tl_ls_shop_product']['pageDescription']								=	array('Meta page description', 'Enter here the page description to be used on the product detail page in the search engine relevant meta element "description". Up to 255 characters are possible, a maximum of 160 is recommended. If you enter something here, this text will be used under all circumstances. If you leave the field empty, either the page description, which Contao regularly creates, or the normal product description can be used, depending on the basic settings.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['sorting']										=	array('Sorting number');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['configurator']									=	array('Use configurator');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['customizerLogicFile']							=	array('Use customizer file');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice']									=	array('Use old price');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents']									= 	array('Flexible product information','You can add as many information as you like and you can use them in the templates as product properties by referencing them with their keyword.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contentsLanguageIndependent']				= 	array('Flexible product information (language independent)','You can add as many information as you like and you can use them in the templates as product properties by referencing them with their keyword.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductAttributesValues'] 					=	array('Allocating an attribute to a value','Select attributes and values which you have already defined under menu item "Product variant attributes".');
	
	
	/*
	 * Legends
	 */
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductCode_legend']						= 'Product code and alias';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPublishAndState_legend']					= 'Status';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopTitleAndDescriptions_legend']				= 'Designation and descriptions';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopUnits_legend']							= 'Units (language-dependent)';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPages_legend']							= 'Page/category assignment';
     $GLOBALS['TL_LANG']['tl_ls_shop_product']['groupRestrictions_legend']					    = 'Group Restrictions';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProducer_legend']							= 'Manufacturer';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopImages_legend']							= 'Images';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopAttributesAndValues_legend']				= 'Attributes and values';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_legend']							= 'Price and weight indications';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_1_legend']							= 'Deviant prices for group no. 1';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_2_legend']							= 'Deviant prices for group no. 2';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_3_legend']							= 'Deviant prices for group no. 3';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_4_legend']							= 'Deviant prices for group no. 4';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_5_legend']							= 'Deviant prices for group no. 5';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopStock_legend']							= 'Goods in stock and delivery time';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopRecommendedProducts_legend']				= 'Recommended products';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['associatedProducts_legend']						= 'Associated products';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopTemplate_legend']							= 'Display template';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['configurator_legend']							= 'Configurator settings';
	
	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['artType']['options'] = array(
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] = array(
		'scalePriceStandalone' => array('Fixed scale price', 'The given price will be used directly'),
		'scalePricePercentaged' => array('Percentaged adjustment', 'The given percentaged adjustment will be applied to the basic product price. Please precede the given value with a minus sign to create a deduction.'),
		'scalePriceFixedAdjustment' => array('Adjustment with a fixed value', 'The given value will be applied as an adjustment to the basic product price. Please precede the given value with a minus sign to create a deduction.')
	);

	$GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] = array(
		'separatedVariantsAndConfigurations' => array('Separated by products, variants and configurations', 'Every cart item will be counted separately, no matter if the product, it\'s variant or even only it\'s configuration differs.'),
		'separatedVariants' => array('Separated by products and variants', 'Cart items only differing in their configuration will be summarized. Different variants will be counted separately.'),
		'separatedProducts' => array('Separated by products', 'Different products in the cart will be counted separately but different variants and configurations will be summarized.'),
		'separatedScalePriceKeywords' => array('Summarized by scale price keyword', 'Cart items with the same scale price keyword will be summarized. Apart from that, different cart items will be counted separately.')
	);

	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['new']        = array('New product', 'Define a new product');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['edit']        = array('Edit variants', 'Edit variants of product with ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['editheader']  = array('Edit product', 'Edit product with ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['delete']        = array('Delete product', 'Delete product with ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['toggle']        = array('Publish product', 'Publish product with ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['copy']        = array('Copy product', 'Copy product with ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['show']        = array('Show details', 'Show details of product with ID %s');

	/*
	 * Misc
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['blankOptionLabel'] = 'Standard';
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents_label01'] = 'Keyword';
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents_label02'] = 'Product information';
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contentsLanguageIndependent_label01'] = 'Keyword';
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contentsLanguageIndependent_label02'] = 'Product information';

	$GLOBALS['TL_LANG']['tl_ls_shop_product']['attributesValues_label01'] = 'Attribute';
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['attributesValues_label02'] = 'Value';
