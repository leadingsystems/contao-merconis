<?php

/*
 * Fields
 */
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['title']										= array('Designation');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['text01']										= array('Text area 1');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['text02']										= array('Text area 2');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['template']									= array('Display template');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['fallbackCrossSeller']						= array('Alternative CrossSeller (optional)', 'Optionally select a CrossSeller here which will be displayed as an alternative if the current CrossSeller does not contain any viewable products.');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['fallbackOutput']								= array('Alternative output (optional)', 'Optionally define an alternative output if the current CrossSeller does not contain any viewable products and if you also have not defined an alternative CrossSeller for this case.');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productDirectSelection']						= array('Product selection');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productSelectionType']						= array('Type of product selection');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['activate']									= array('Use', 'Activate to apply the search criterion');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['published']									= array('Active');

$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['maxNumProducts']								= array('Maximum number of products', 'Define a maximum number of products for display in this CrossSeller. Enter "0" to display all matching products.');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['noOutputIfMoreThanMaxResults']					= array('No output if more than maximum');

$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionNewProduct']					= array('New product');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionSpecialPrice']				= array('Special reduced price');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionCategory']					= array('Page/category');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionProducer']					= array('Manufacturer', 'The following place holders which are based on certain features of a product opened in the product detail view are available for the search: "{{currentProduct_name}}" (Product designation), "{{currentProduct_articleNr}}" (Product code), "{{currentProduct_producer}}" (Manufacturer)');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionProductName']					= array('Product designation', 'The following place holders which are based on certain features of a product opened in the product detail view are available for the search: "{{currentProduct_name}}" (Product designation), "{{currentProduct_articleNr}}" (Product code), "{{currentProduct_producer}}" (Manufacturer)');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionArticleNr']					= array('Product code', 'The following place holders which are based on certain features of a product opened in the product detail view are available for the search: "{{currentProduct_name}}" (Product designation), "{{currentProduct_articleNr}}" (Product code), "{{currentProduct_producer}}" (Manufacturer)');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionTags']						= array('Keywords', 'Search for the keywords stored in the products. The following place holders which are based on certain features of a product opened in the product detail view are available for the search: "{{currentProduct_name}}" (Product designation), "{{currentProduct_articleNr}}" (Product code), "{{currentProduct_producer}}" (Manufacturer)');

$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['canBeFiltered']								= array('Can be filtered');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['doNotUseCrossSellerOutputDefinitions']		= array('Use product overview display settings', 'Use the display settings for the product overview instead of those for CrossSellers.');

/*
 * Misc
 */
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionNewProduct'] = 'Search criterion "New product"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionSpecialPrice'] = 'Search criterion "Special reduced price"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionCategory'] = 'Search criterion "Page/category"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['subHeadlineSearchSelectionCategory'] = 'Please note that the currently called page is used for every search by default if you activate this search criterion without explicitely selecting a page/category.';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionProducer'] = 'Search criterion "Manufacturer"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionProductName'] = 'Search criterion "Product designation"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionArticleNr'] = 'Search criterion "Product code"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionTags'] = 'Search criterion "Product keywords"';

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['title_legend']   = 'Designation';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['textOutput_legend']   = 'Text output';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['template_legend']   = 'Display';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['fallbackCrossSeller_legend']   = 'Alternative CrossSeller/alternative output';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productSelectionType_legend']   = 'Type of product selection';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['directSelection_legend']   = 'Simple product selection';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelection_legend']   = 'Product selection based on detailed search';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['lastSeen_legend']   = 'Last viewed products';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['frontendProductSearch_legend']   = 'Frontend product search';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['recommendedProducts_legend']   = 'Recommended products';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['filterSettings_legend'] = 'Filter behavior';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['published_legend'] = 'Activation';

/*
 * References
 */
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productSelectionType']['options'] = array(
    'hookSelection' => array('Individual via hook', 'With this selection type you can make the actual product selection using the hook "crossSellerHookSelection".'),
    'directSelection' => array('Direct product selection', 'This selection option enables you to directly select products.'),
    'searchSelection' => array('Product search', 'The products to be displayed in the CrossSeller are determined by means of stipulated search criteria.'),
    'lastSeen' => array('Last viewed products', 'The last viewed products are displayed in the CrossSeller.'),
    'favorites' => array('Favorites/Watchlist', 'The products that the visitor has added to his favorites/watchlist are displayed in the CrossSeller.'),
    'restockInfoList' => array('Products with active notification of renewed availability', 'The CrossSeller displays the products for which the visitor has activated the notification of renewed availability.'),
    'recommendedProducts' => array('Recommended products', 'The CrossSeller displays products which you have allocated the product displayed in the detail view as "Recommended products".'),
    'frontendProductSearch' => array('Frontend product search', 'The search result of a product search carried out in the frontend is displayed in the CrossSeller.')
);

$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionNewProduct']['options'] = array(
    'new' => array('Product has been marked new'),
    'notNew' => array('Product has not been marked new')
);

$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionSpecialPrice']['options'] = array(
    'specialPrice' => array('Product has been marked special reduced price'),
    'noSpecialPrice' => array('Product has not been marked special reduced price')
);


/*
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['new']        = array('New CrossSeller', 'Define a new CrossSeller');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['edit']        = array('Edit CrossSeller', 'Edit CrossSeller ID %s');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['delete']        = array('Delete CrossSeller', 'Delete CrossSeller ID %s');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['copy']        = array('Copy CrossSeller', 'Copy CrossSeller ID %s');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['show']        = array('Show details', 'Show details of CrossSeller ID %s');
