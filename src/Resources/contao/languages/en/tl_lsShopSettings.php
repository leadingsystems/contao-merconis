<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_country']					= array('Country in which the shop is run', 'Enter the two-digit country code (ISO-3166-1 coding list) of the country in which the shop is run here.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ownVATID']				= array('Own VAT ident number', 'Should you have a VAT ident number, please enter it here.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_euCountrycodes']			= array('EU countries', 'Enter the two-digit country codes (ISO-3166-1 coding list) of the countries in the EU.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_currency']				= array('Currency', 'Please enter the currency here that shall be used everywhere in the shop.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_currencyCode']			= array('Currency code according to ISO-4217', 'Enter the three-digit currency code according to ISO-4217 (e.g. "EUR" for Euro, "GBP" for British Pound or "USD" for US Dollar) here for the currency used by you.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_weightUnit']				= array('Weight unit', 'Enter the unit here in which weights in the shop are stated. Weight units are required whenever shipping costs per weight are calculated.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_quantityDefault']			= array('Defaut value for quantity input');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numDecimals']				= array('Number of decimal places for price indication', 'Define here with how many decimal places prices in the shop shall be displayed.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceRoundingFactor']		= array('Price rounding model (e.g. Swiss centime rounding)', 'Define here which rounding model Merconis should use for all displayed and calculated prices. Swiss shop owners should use rounding by steps of 0,05 in most cases. If large numbers of small parts are sold and therefore the original price accuracy needs to be used for product prices, it is possible to select rounding by steps of 0,01 here and apply the 0,05-step-rounding only to the invoiced amount and possibly a displayed tax value in the respective templates. Please note that some payment providers validate calculations and reject payments if the cumulated product prices don\'t add up exactly to match the invoiced amount. To prevent payment providers from rejecting payments, Merconis internally uses values with the original accuracy when a special rounding is applied in the templates.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numDecimalsWeight']		= array('Number of decimal places for weight indication', 'Define here with how many decimal places weights in the shop shall be displayed.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceType']				= array('Type of entered prices gross/net', 'IMPORTANT: Enter here whether you state gross or net prices everywhere in the administration interface. If you sell your products transnationally, it is mandatory that you enter net prices. Hint for the frontend output: Whether you display gross or net prices in the frontend is defined within the contao member group(s).');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardGroup']			= array('Standard user group', 'Some shop settings are made separately for different user and member groups. Define here to which group a non-registered customer shall be assigned.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_allowCheckout']			= array('Completion of order with or without login', 'Define here whether completion of the order shall be possible with or without login.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_autoSelectCheapestPossibleShippingAndPaymentMethods'] = array('Automatically select lowest priced payment and shipping option', 'Select this checkbox if you want the cheapest payment and shipping option to be selected automatically if your customer has not made a selection yet or if the selection your customer has made cannot be applied any more due to changes of the order. The automatic selection enables your customer to directly complete the order with the cheapest payment and shipping option without having to make a selection. If you do not use this feature, the cheapest payment and shipping option will be provided for as a suggestion in the calculation. However, in this case, the completion of the order is only possible if the customer makes a selection. Please check whether legal provisions in your country might argue against the use of this setting.');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_useProductDescriptionAsSeoDescription'] = array('Use product description as meta description', 'Select this checkbox if you want the product description to be used as the meta page description. If available, the product short description will be used, otherwise the long description will be used. If no product description exists at all, the regular page description provided by contao will be used. Please note: If the product is explicitly assigned its own page description, this will always be used regardless of this setting.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_loginModuleID'] 			= array('Login module for use during completion of order');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_miniCartModuleID'] 		= array('Mini shopping cart module', 'Module to be updated via AJAX');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_useVATIDValidation']		= array('Use the online validation of the VATID');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_useAjaxForPaginationAndSorting'] = array('Use ajax for pagination and sorting', 'When displaying product lists, ajax technology is used to reload the product list when using pagination and sorting without having to reload the entire page');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_VATIDValidationSOAPOptions'] = array('Options for SOAP client', 'Please do not change the default settings unless you know exactly what you do. Faulty settings can cause the validation of the VAT ident number to slow down. Please consider that depending on your server configuration it is possible that the default settings are not ideal and can cause a validation slow down as well. In this case, please try to remove all option entries and check if the validation works without delay this way.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_beOrderTemplateOverview'] = array('Backend template for order overview');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_beOrderTemplateDetails'] = array('Backend template for order details');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrCounter']			= array('Current count');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrString']			= array('Order number format', 'Here you can define in which format the order number should be output/displayed. Use the {{counter}} placeholder to insert the counter number. You can insert a pre-zeroed counter value by specifying the placeholder as {{counter:7}}, for example, where the total number of digits with which the counter is to be output is specified after the colon. If required, you can also use the placeholder {{date:}} to insert a variable date automatically. After the colon you can write any date specification in the syntax of the PHP function "date()". Example: "{{date:Y}}-{counter}}" results in the order number "2012-147" for the 147th order in 2012.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrStart']			= array('Start value of the counter ({{counter}})', 'Define here with which value the counter ({{counter}}) shall start.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartCycle']		= array('Resetting the counter ({{counter}})', 'Define here at which intervals the counter({{counter}}) shall be reset, if required.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartNow']		= array('Reset counter ({{counter}}) immediately', 'Activate this option if you want the counter ({{counter}}) to be reset to the start value immediately when your settings are saved.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues01']		= array('Status options for Status 1', 'Please enter the possible values for this status type as a list separated by commas. The value stated on the first position will be used as the default status for a new order. Leave the field blank if you do not need this status type.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues02']		= array('Status options for Status 2', 'Please enter the possible values for this status type as a list separated by commas. The value stated on the first position will be used as the default status for a new order. Leave the field blank if you do not need this status type.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues03']		= array('Status options for Status 3', 'Please enter the possible values for this status type as a list separated by commas. The value stated on the first position will be used as the default status for a new order. Leave the field blank if you do not need this status type.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues04']		= array('Status options for Status 4', 'Please enter the possible values for this status type as a list separated by commas. The value stated on the first position will be used as the default status for a new order. Leave the field blank if you do not need this status type.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues05']		= array('Status options for Status 5', 'Please enter the possible values for this status type as a list separated by commas. The value stated on the first position will be used as the default status for a new order. Leave the field blank if you do not need this status type.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_shippingInfoPages']		= array('Page "Shipping terms"', 'Enter the page on which your shipping terms are explained here. A link to this page can be found along with the product display. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_cartPages']				= array('Page "Shopping cart"', 'Select the page that contains the "Shopping cart" module here. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_reviewPages'] = array('Page "Checkout - Review order"', 'Select the page that contains the "Shopping cart" module here. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_signUpPages']			= array('Page "Registration"', 'Select the page that contains the module "Order review" here. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutPaymentErrorPages']	= array('Page "Error during processing of the payment option"', 'Select the page that contains the error message for failed processing of the payment option here. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutShippingErrorPages']	= array('Page "Error during processing of the shipping option"', 'Select the page that contains the error message for failed processing of the shipping option here. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutFinishPages']	= array('Page "Completion of order"', 'Select the page that contains the module "Completion of order" here. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_paymentAfterCheckoutPages']		= array('Page "Payment after completion of order"', 'Select the page that contains the module "Payment after completion of order" here. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_afterCheckoutPages']		= array('Page "Information after completion of order"', 'Select the page that contains the module "Information after completion of order" here. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ajaxPages']			=  array('Page "AJAX"', 'Select the page to be used for AJAX inquiries here. This page should only contain the relevant MERCONIS modules. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchResultPages'] = array('Page "Product search results"', 'Select the page on which the product search results shall be displayed here. Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_myOrdersPages'] = array('Page "My orders"', 'Select the page which contains the module "My orders". Should you run a multilingual shop, please select the corresponding page for each language.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_myOrderDetailsPages'] = array('Page "My orders - details"', 'Select the page which contains the module "My orders - details". Should you run a multilingual shop, please select the corresponding page for each language.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_videoDummyCover']	= array('Default cover for videos', 'Select a graphic to be used as cover image for videos if no cover graphic matching the video can be found.');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_noProductImage']	= array('Default product image', 'Select a graphic to be used as product image if no product image can be found.');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_sizeMainImage']			= array('Product main images: Image width and height', 'Please set the image width and height for product main images here.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_sizeMoreImages']			= array('Further product images: Image width and height', 'Please set the image width and height for further product images here.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_moreImagesMargin']			= array('Further product images: Distance between images', 'Please define the top, right, bottom and left distance for further product images here.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_imagesFullsize']			= array('Large view/New window','Open product images in large view in a lightbox or in a new window when clicked.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_moreImagesSortBy']			= array('Sort by','Please define the sorting order here.');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_sizeMainImage02']			= array('Product image in gallery: Image width and height', 'Please set the image width and height for product images in the gallery view.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_galleryItemWidth']			= array('Width of an individual product area');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_galleryItemHeight']		= array('Height of an individual product area');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_galleryItemMargin']		= array('Outside distance of an individual product area', 'Please set the top, right, bottom and left distance for an individual product area here.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_versandkostenType']			= array('Shipping costs incl./excl.', 'Define here whether you state your prices with shipping costs included or excluded.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_output_definitionset']	= array('Standard display default', 'Please select the display default to be used as standard.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_delivery_infoSet']		= array('Standard setting for goods in stock/delivery time', 'Please select the default setting for goods in stock and delivery time to be used as standard.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_productDetailsTemplate'] =	array('Standard template for product detail view');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImageFolder'] =	array('Standard folder for product images', 'Define the folder that is automatically searched for matching product images on the basis of product codes by the shop. The shop is capable of finding images of which the file name starts with the product code followed by a separator (see next input field) (e.g. 1234_imagename.jpg) or of which the file name contains only the product code (e.g. 123.jpg).');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImageDelimiter'] =	array('Separator for product codes in product images', 'Define here which separator you use to separate the product code in an image name from the rest of the file name.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_imageSortingStandardDirection'] = array('Default sorting of product images');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImportFolder'] =	array('Standard folder for import files', 'Define the folder here in which the import function will store a file to be imported after the upload.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvDelimiter'] = array('CSV delimiter character');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvEnclosure'] = array('CSV field enclosure character');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvEscape'] = array('CSV escape character');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvLocale'] = array('CSV locale');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numMaxImportRecordsPerRound'] = array('Number of records for import splitting', 'To make sure that very big imports don\'t result in a very long execution time of a single php script which would require high server limits, the import is split into parts. Define here, how many records can be imported at a time.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitImageSizeWidth'] = array('Image display width', 'Width of the displayed LiveHits image in px');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitImageSizeHeight'] = array('Image display height', 'Height of the displayed LiveHits image in px');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsMaxNumHits'] = array('Maximum number of displayed hits');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsMinLengthSearchTerm'] = array('Minimum length of the search term');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importFlexFieldKeys'] = array('Import columns for flexible product information','Enter the column headers of the fields to be imported as flexible product information as a list separated by commas. Please note that the column headers are used as keywords for the flexible product information.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importFlexFieldKeysLanguageIndependent'] = array('Import columns for flexible product info (language independent)','Enter the column headers of the fields to be imported as flexible product information as a list separated by commas. Please note that the column headers are used as keywords for the flexible product information.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ownEmailAddress'] = array('Own Email address', 'Used for various system notifications');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_maxNumParallelSearchCaches'] = array('Maximum number of parallel search caches');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchCacheLifetimeSec'] = array('Lifetime of search caches in seconds');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_considerGroupPricesInFilterAndSorting'] = array('Consider group prices for filter and sorting','Please deactivate this option for performance reasons if you have not entered deviant group prices for any product or variant.');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ignoreGroupRestrictionsInSearch'] = array('Ignore group restrictions in search', 'If there are no products with group restrictions, it can improve search performance if this search criterion is completely ignored. If this setting is selected, but there are products with group restrictions, these products will be found but not displayed. Instead, gaps occur in the displayed product lists.');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_alwaysAddIdToAliasDuringProductImport'] = array('When importing products, always add ID to alias', 'With this setting, the uniqueness check of aliases is omitted during product import, thus achieving a significant performance advantage. Uniqueness is achieved by automatically appending the internal product ID to the alias.');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_title'] = array('Product designation: Entire search text matches complete field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_keywords'] = array('Keywords: Entire search text matches complete field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_shortDescription'] = array('Short description: Entire search text matches complete field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_description'] = array('Description: Entire search text matches complete field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_productCode'] = array('Product code: Entire search text matches complete field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_producer'] = array('Producer: Entire search text matches complete field value');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_title'] = array('Product designation: Entire search text contained in field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_keywords'] = array('Keywords: Entire search text contained in field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_shortDescription'] = array('Short description: Entire search text contained in field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_description'] = array('Description: Entire search text contained in field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_productCode'] = array('Product code: Entire search text contained in field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_producer'] = array('Producer: Entire search text contained in field value');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_title'] = array('Product designation: Single search term matches complete field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_keywords'] = array('Keywords: Single search term matches complete field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_shortDescription'] = array('Short description: Single search term matches complete field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_description'] = array('Description: Single search term matches complete field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_productCode'] = array('Product code: Single search term matches complete field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_producer'] = array('Producer: Single search term matches complete field value');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_title'] = array('Product designation: Single search term contained in field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_keywords'] = array('Keywords: Single search term contained in field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_shortDescription'] = array('Short description: Single search term contained in field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_description'] = array('Description: Single search term contained in field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_productCode'] = array('Product code: Single search term contained in field value');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_producer'] = array('Producer: Single search term contained in field value');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ipWhitelist'] = array('IP whitelist', 'Enter all IP addresses that are allowed to call your system (e.g. in order to send payment information) as a list separated by commas.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_urlWhitelist'] = array('URL whitelist', 'Enter a regular rexpression which will be used to check whether the request token check should be skipped for a request.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_blnCompatMode2-1-4'] = array('Compatibility mode for updates from versions < 2.1.5', 'In version 2.1.5 the file structure has changed partially. Use this compatibility mode if you have the old file structure and want Merconis to use it.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_sortingCharacterTranslationTable'] = array('Replacement table for sorting', 'In order to control the way special characters are considered during sorting, you can define replacements for special characters.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_dcaNamesWithoutMultilanguageSupport'] = array('DCAs to skip in multilanguage initialization', 'Comma-separated list of DCA names to skip in the multilanguage initialization.');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsDebugMode'] = array('Debug mode');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsNoCacheMode'] = array('Deactivate caching');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsNoMinifierMode'] = array('Deactivate minification');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lscssFileToLoad'] = array('SCSS file to load', 'By default, an SCSS file supplied by Merconis is used and no other file need/should be selected here. A different selection is only necessary if, for example, additional/changed styles are to be used due to own extensions. Attention: If you select your own SCSS file, the file supplied by Merconis by default will no longer be loaded. It is therefore advisable to create your own file as a copy of the original Merconis file and then add/change it.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lscssDebugMode'] = array('Debug mode');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lscssNoCacheMode'] = array('Deactivate caching');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lscssNoMinifierMode'] = array('Deactivate minification');

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['basic_legend']   = 'Basic information';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['euSettings_legend'] = 'Settings regarding the European Union';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['userSettings_legend']   = 'User-related settings';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['pageSettings_legend']   = 'Page settings';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['systemImages_legend']   = 'System images';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['orderNr_legend']   = 'Order number';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['systemSettings_legend']   = 'System settings';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['liveHits_legend']   = 'MERCONIS LiveHits settings';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['orderStatusTypes_legend'] = 'Status options';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['productPresentationTemplate01_legend'] = 'Product detail view settings';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['productPresentationTemplate02_legend'] = 'Gallery view settings';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['performanceSettings_legend'] = 'Performance settings';
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['productSearchSettings_legend'] = 'Hit weighting for product search';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['importSettings_legend'] = 'Import settings';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['compatSettings_legend'] = 'Compatibility settings';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ipWhitelist_legend'] = 'Whitelist for referer check';

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['backendLsjs_legend'] = 'Settings for LSJS in the backend';

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['backendLscss_legend'] = 'Settings for LSCSS in the backend';

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['misc_legend'] = 'Advanced settings';
	
	/*
	 * References
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['edit'] = 'Edit basic shop settings';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['name_asc']  = 'File name (ascending)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['name_desc'] = 'File name (descending)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['date_asc']  = 'Date (ascending)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['date_desc'] = 'Date (descending)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['meta']      = 'Meta file (meta.txt)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['random']    = 'Random order';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartCycle']['options'] = array(
		'never' => 'Never',
		'year' => 'New year',
		'month' => 'New month',
		'week' => 'New week',
		'day' => 'New day'	
	);
	
	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceRoundingFactor']['options'] = array(
		'100' => array('0,01 steps', 'Rounding by steps of 0,01 (Standard for most currencies)'),
		'20' => array('0,05 steps (used in Switzerland)', 'Rounding by steps of 0,05 (e.g. in Switzerland)')
	);
	
 	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_imageSortingStandardDirection']['options'] = array(
		'name_asc' => 'File name (ascending)',
		'name_desc' => 'File name (descending)',
		'date_asc' => 'Date (ascending)',
		'date_desc' => 'Date (descending)',
		'random' => 'Random order'
	);
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceType']['options'] = array(
		'brutto' => array('Gross prices','Select this option if you store gross prices for your products.'),
		'netto' => array('Net prices','Select this option if you store net prices for your products.')
	);
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_versandkostenType']['options'] = array(
		'incl' => array('Included','Select this option if you store prices for your products with the shipping costs included. A help note is displayed along with the price.'),
		'excl' => array('Excluded','Select this option if you store prices for your products with the shipping costs excluded. A help note is displayed along with the price.')
	);
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_allowCheckout']['options'] = array(
		'withLogin' => array('With login', 'Select this option if you wish to enable only logged-in users to complete an order.'),
		'withoutLogin' => array('Without login', 'Select this option if you wish to enable non-logged-in users to complete an order and if you also do not wish to offer the possibility to log in during the ordering process.'),
		'both' => array('With and without login', 'Select this option if you wish to enable both logged-in and non-logged-in users to complete an order and if you wish to offer the possibility to log in during the ordering process.')
	);	
