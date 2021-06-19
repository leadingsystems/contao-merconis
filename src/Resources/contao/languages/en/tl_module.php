<?php

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_module']['ls_shop_cart_template']						= array('Template for shopping cart display');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_orderReview_template']				= array('Template for order review display');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_afterCheckout_template']				= array('Template for displaying information after ordering');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_paymentAfterCheckout_template']				= array('Template for displaying payment after ordering');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_myOrders_template']					= array('Template for displaying the customer\'s order overview');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_myOrders_sortingOptions']				= array('Sorting options offered');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_myOrderDetails_template']				= array('Template for displaying the order details');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_filterForm_template']				= array('Template for displaying the filter form');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_filterSummary_template'] = array('Template for displaying the filter summary');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_productSearch_template']				= array('Template for displaying the query form');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_productSearch_minlengthInput']		= array('Minimum length of search term');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_cross_seller']							= array('Cross-seller', 'Please select here which cross-seller you wish to implement.');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_productManagementApiInspector_apiPage'] = array('API page', 'Please choose the page with the embedded API module.');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_productOverviewShowProductsFromSubordinatePages'] = array('Show products of subordinate pages', 'Activate the checkbox if you want the product list to also show products that are not assigned to the current page itself, but to its subordinate pages.');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_productOverviewStartLevel'] = array('Start level', 'Here you can determine the level in the page structure from which products are included in the output. The 0 means that products of the page on which the "Product Overview" module is located are also displayed.');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_productOverviewStopLevel'] = array('Stop level', 'Here you can determine up to which level in the page structure products are considered for output. The 0 means that there is no limit.');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_productOverviewConsiderUnpublishedPages'] = array('Consider unpublished pages', 'Activate the checkbox if you want products to be displayed if they are on unpublished pages or on pages that are subordinate to an unpublished page.');
$GLOBALS['TL_LANG']['tl_module']['ls_shop_productOverviewConsiderHiddenPages'] = array('Consider hidden pages', 'Activate the checkbox if you also want products to be displayed if they are on hidden pages or on pages that are subordinate to a hidden page.');

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_module']['lsShopProductOverview_legend']					= 'Product overview settings';
$GLOBALS['TL_LANG']['tl_module']['lsShopCart_legend']						= 'Shopping cart settings';
$GLOBALS['TL_LANG']['tl_module']['lsShopOrderReview_legend']			= 'Order review settings';
$GLOBALS['TL_LANG']['tl_module']['lsShopAfterCheckout_legend']			= 'Settings for information after ordering';
$GLOBALS['TL_LANG']['tl_module']['lsShopPaymentAfterCheckout_legend']			= 'Settings for payment after ordering';
$GLOBALS['TL_LANG']['tl_module']['lsShopMyOrders_legend']				= 'Settings for customer\'s order overview';
$GLOBALS['TL_LANG']['tl_module']['lsShopMyOrderDetails_legend']			= 'Settings for order details';
$GLOBALS['TL_LANG']['tl_module']['lsShopFilterForm_legend']			= 'Settings for the filter form';
$GLOBALS['TL_LANG']['tl_module']['lsShopCrossSeller_legend']				= 'Cross-seller';
$GLOBALS['TL_LANG']['tl_module']['lsShopProductSearch_legend']				= 'Product search';
$GLOBALS['TL_LANG']['tl_module']['ls_shop_productManagementApiInspector'] = 'Product Management API Inspector';
