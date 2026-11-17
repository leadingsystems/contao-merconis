<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopOutputPriceType']										= array('Output prices gross/net', 'Define here whether customers belonging to this group will see gross or net prices.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopPriceAdjustment']										= array('Price adjustment (percentage)', 'Here you can define a percentage surcharge or discount on all product prices for all customers belonging to this group.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopMinimumOrderValue']									= array('Minimum order value', 'Important: Please enter the amount as gross or net amount according to the output price setting valid for this group here. The global setting for entered prices is not applied here!');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopMinimumOrderValueAddCouponToValueOfGoods']			= array('Consider coupons when checking the minimum order value', 'Define whether coupons should be considered when checking the minimum order value.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopFormCustomerData']									= array('Customer data form', 'Please select the form you wish to use for customer data entry during the ordering process here.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopFormConfirmOrder']									= array('Confirmation of order form', 'Please select the form customers of this customer group shall use to confirm their order here.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopStandardPaymentMethod']								= array('Pre-selected payment method', 'If you want a certain payment method to be selected automatically at completion of the order, you can define this here.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopStandardShippingMethod']								= array('Pre-selected shipping method', 'If you want a certain shipping method to be selected automatically at completion of the order, you can define this here.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopGllMode'] = ['Legal guarantee notice', "Defines whether members of this group see the notice on the legal guarantee and the label of the manufacturer guarantee. 'Show' applies to products set to 'As member group'. 'Only products set to Always show' suits shops selling mostly services or digital content. 'Never show' is for groups without consumers, e.g. business customers, and also applies to the manufacturer guarantee. A change does not adjust any products."];
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopGllModeBlankOption'] = 'Please select';
	
	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_member_group']['lsShop_legend']			= 'MERCONIS settings';

	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopOutputPriceType']['options'] = array(
		'gross' => array('Gross prices','Select this option if members of this group are supposed to see gross prices.'),
		'net' => array('Net prices','Select this option if members of this group are supposed to see net prices.')
	);

	$GLOBALS['TL_LANG']['tl_member_group']['lsShopGllModeOptions'] = [
		'on' => 'Show',
		'off' => "Only products set to 'Always show'",
		'blocked' => 'Never show (e.g. business customers)',
	];
