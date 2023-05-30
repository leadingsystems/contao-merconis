<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['title']										= array('Designation');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['description']								= array('Description');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['infoAfterCheckout']							= array('Information after completion of the order');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['additionalInfo']							= array('Additional information');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['formAdditionalData']							= array('Form for customer entries');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['dynamicSteuersatzType']						= array('Dynamic tax rate', 'Choose which method should be used to dynamically select a tax rate.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['steuersatz']									= array('Tax rate', 'Select the tax rate here by which possible costs for this shipping options will be taxed.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['type']										= array('Type of shipping option','Select the type of shipping option here. Most shipping options can be realized by selecting "Standard".');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['published']									= array('Published','Tick this if you would like to offer this shipping option in your shop.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['sorting']									= array('Sorting number');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeType']									= array('Type of cost calculation','Define here how costs will be calculated.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeAddCouponToValueOfGoods']				= array('Include coupons in value of goods', 'If the charge calculation is based on the value of goods, this checkbox allows you to define whether coupon values should be included in the value of goods or not.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeValue']									= array('Price');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeFormula']									= array('Forumla for charge calculation', 'Always use the dot sign as the decimal separator. Available placeholders: ##totalValueOfGoods##, ##totalWeightOfGoods##, ##totalValueOfCoupons##. Beside conventional calculations, using ternary operators is also possible. Therefore, the following example would work: ##totalValueOfGoods## > 300 ? 0 : (##totalWeightOfGoods## <= 10 ? 10 : (##totalWeightOfGoods## <= 20 ? 20 : (##totalWeightOfGoods## <= 30 ? 30 : 40)))');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeFormulaResultConvertToDisplayPrice']		= array('Convert the calculated charge into a display price', 'Choose this option if the result of your calculation is a net or gross price as defined in the basic MERCONIS settings, in order to display the correct charge for the customer. Don\'t choose this option if you perform a calculation that\'s based for example on the "value of goods" placeholder because in this case the result of your calculation is already the correct display price for the customer.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeWeightValues']							= array('Price by weight (left side: up to which weight, right side: which price)','Define here up to which weight which fixed amount for shipping will be charged.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feePriceValues']								= array('Price by value of goods (left side: up to which value of goods, right side: which price)','Define here up to which value of goods which fixed amount for shipping will be charged.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['excludedGroups']								= array('Groups to be excluded', 'Select the groups here for which this shipping option shall not be available.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['weightLimitMin']								= array('Minimum weight', 'Enter the minimum weight for the delivery here from which on this shipping option will be available. Enter "0" to ignore this value.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['weightLimitMax']								= array('Maximum weight', 'Enter the maximum weight for the delivery here up to which this shipping option will be available. Enter "0" to ignore this value.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimitMin']								= array('Minimum value of goods', 'Enter the minimum value of goods here from which on this shipping option will be available. Enter "0" to ignore this value.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimitMax']								= array('Maximum value of goods', 'Enter the maximum value of goods here up to which this shipping option will be available. Enter "0" to ignore this value.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimitAddCouponToValueOfGoods']			= array('Include coupons in value of goods', 'This checkbox allows you to define whether coupon values should be included in the value of goods when checking the minimum or maximum value of goods.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['countries']									= array('Country selection', 'Please enter the countries as a list separated by commas.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['countriesAsBlacklist']						= array('Interpret country selection as a blacklist');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['cssID']										= array('CSS ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['cssClass']									= array('CSS class');
    $GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['notSelectable']									= array('Not Selectable');

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['title_legend']			= 'Designation';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['steuersatz_legend']	= 'Tax rate';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['afterCheckout_legend']			= 'After completion of the order';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['type_legend']			= 'Type of shipping option';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['published_legend']		= 'Publish';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['fee_legend']				= 'Cost calculation';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['excludedGroups_legend']	= 'Group-related settings';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['weightLimit_legend']		= 'Weight restrictions';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimit_legend']		= 'Restrictions of the value of goods';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['countryLimit_legend']		= 'Restrictions of the permitted countries';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['misc_legend']		= 'Miscellaneous';
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['new']        = array('New shipping option', 'Define a new shipping option');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['edit']        = array('Edit shipping option', 'Edit shipping option ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['delete']        = array('Delete shipping option', 'Delete shipping option ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['copy']        = array('Copy shipping option', 'Copy shipping option ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['show']        = array('Show details', 'Show details of shipping option ID %s');
	
	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeType']['options'] = array(
		'none' => array('Free of charge'),
		'fixed' => array('Fixed amount', 'Select this option if you would like to define the costs as a fixed amount.'),
		'percentaged' => array('Percentage', 'Select this option if you would like to define the costs as a percentage value.'),
		'weight' => array('By weight', 'Select this option if you would like to define the costs as a fixed amount depending on the total weight of the ordered goods.'),
		'price' => array('By value of goods', 'Select this option if you would like to define the costs as a fixed amount depending on the value of the ordered goods. This setting makes sense for insured shipping etc.'),
		'weightAndPrice' => array('By weight and value of goods', 'Select this option if you would like to define the costs as a fixed amount depending on the weight and the value of the ordered goods. Please note that the costs which you will hereafter state by weight and price will be added up accordingly. This setting makes sense for insured shipping etc. because shipping and insurance costs can be handled separately.'),
		'formula' => array('Calculation formula', 'Define a formula for charge calcualation using placeholders.')
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['dynamicSteuersatzType']['options'] = array(
		'none' => 'no dynamics',
		'main' => 'follow the main service',
		'max' => 'highest used',
		'min' => 'lowest used'
	);
		
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['type']['options'] = array(
		'Standard' => array('Standard shipping', 'Most shipping options can be realized with the standard shipping module. Please contact your administrator if you require a custom-made shipping module for a certain shipping method.')
	);
