<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['title']										= array('Designation');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['description']									= array('Description');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productCode']									= array('Product code', 'Here you can store a product code which will be displayed during ordering to identify the used coupon.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponCode']									= array('Coupon code', 'Enter the code here which the customer must enter in the shopping cart to cash this coupon.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponValueType']								= array('Type of coupon value', 'Select here whether the stated coupon value is charged as a fixed or percentage deduction.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponValue']									= array('Coupon value', 'Enter the coupon value as a fixed or percentage amount here. The coupon value is always deducted from the total value of goods which is apparent to your customer. Corresponding to this, whether an entered fixed value is interpreted as a gross or net amount depends on whether the customer can see gross or net prices in the shop. To avoid differences in value through the use of different customer groups, please use the possibility to define the customer groups for which a coupon is valid.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['minimumOrderValue']							= array('Minimum order value', 'Important: Please enter the amount here as a gross or net value according to the output price settings valid for this customer group. The global setting for entered prices is not applied here!');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['allowedForGroups']							= array('Valid for the following customer groups', 'Please define the customer groups here for which this coupon is valid.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['start']										= array('Period of validity from');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['stop']										= array('Period of validity until');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['limitNumAvailable']							= array('Limit available quantity');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['numAvailable']								= array('Available quantity');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['changeNumAvailable']							= array('Change available quantity', 'Enter "+100" to add 100 to the quantity or "-100" to subtract 100 from the quantity or "100" to set the quantity to exactly 100.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['published']									= array('Active');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productDirectSelection']						= array('Product selection');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productSelectionType']						= array('Type of product selection');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['activate']									= array('Use', 'Activate to use the search criterion');
    $GLOBALS['TL_LANG']['tl_ls_shop_coupon']['minimumOrderValueforCouponActivate']          = array('Minimum order value only for products selection', 'When activated, the minimum order value refers only to the product selection');

    $GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionNewProduct']					= array('New product');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionSpecialPrice']				= array('Special reduced price');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionCategory']					= array('Page/category');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionProducer']					= array('Manufacturer');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionProductName']					= array('Product designation');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionArticleNr']					= array('Product code');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionTags']						= array('Search keys');

	/*
	 * Misc
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionNewProduct'] = 'Search criterion "New product"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionSpecialPrice'] = 'Search criterion "Special reduced price"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionCategory'] = 'Search criterion "Page/category"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['subHeadlineSearchSelectionCategory'] = 'Please note that the currently called page is used for every search by default if you activate this search criterion without explicitely selecting a page/category.';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionProducer'] = 'Search criterion "Manufacturer"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionProductName'] = 'Search criterion "Product designation"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionArticleNr'] = 'Search criterion "Product code"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionTags'] = 'Search criterion "Search keys"';

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['title_legend']   = 'Designation';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['status_legend']   = 'Status';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['generalSettings_legend']   = 'General settings';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['numAvailable_legend']   = 'Available quantity';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productSelectionType_legend']   = 'Type of product selection';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['directSelection_legend']   = 'Simple product selection';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelection_legend']   = 'Product selection due to detailed search';
    $GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productWhitelist']   = 'Interprete Product selection as Whitelist';
    $GLOBALS['TL_LANG']['tl_ls_shop_coupon']['blacklistActivate']   = array('Exclude product(s)', 'Interpret product selection as a blacklist and thus exclude it for the coupon.');
    $GLOBALS['TL_LANG']['tl_ls_shop_coupon']['minimumOrderValueforCoupon']   = 'minimum order value only for selected products';

	/*
	 * References
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponValueType']['options'] = array(
		'percentaged' => array('Percentage value', 'The coupon value is charged as a percentage deduction.'),
		'fixed' => array('Fixed amount', 'The coupon value is charged as a fixed amount.')
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productSelectionType']['options'] = array(
		'noSelection' => array('No product selection', 'Select this if you do not wish to limit the validity of the coupon to certain products.'),
		'directSelection' => array('Direct product selection', 'Select this if you wish to directly define the products for which the coupon is supposed to be valid.'),
		'searchSelection' => array('Product search', 'The products for which the coupon is valid are determined by means of stipulated search criteria.')
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionNewProduct']['options'] = array(
		'new' => array('Product has been marked new'),
		'notNew' => array('Product has not been marked new')
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionSpecialPrice']['options'] = array(
		'specialPrice' => array('Product has been marked special reduced price'),
		'noSpecialPrice' => array('Product has not been marked special reduced price')
	);
	
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['new']        = array('New coupon', 'Define a new coupon');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['edit']        = array('Edit coupon', 'Edit coupon ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['delete']        = array('Delete coupon', 'Delete coupon ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['copy']        = array('Copy coupon', 'Copy coupon ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['show']        = array('Show details', 'Show details of coupon with ID %s');
