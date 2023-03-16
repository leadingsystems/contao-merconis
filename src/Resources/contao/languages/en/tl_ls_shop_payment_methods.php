<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['title']										= array('Designation');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['description']								= array('Description');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['infoAfterCheckout']							= array('Information after completion of order');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['additionalInfo']								= array('Additional information (e.g. term of payment)');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['formAdditionalData']							= array('Form for customer entries');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['dynamicSteuersatzType']						= array('Dynamic tax rate', 'Choose which method should be used to dynamically select a tax rate.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['steuersatz']									= array('Tax rate', 'Select the tax rate here by which applicable charges for this payment option will be taxed, if required.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['type']										= array('Type of payment option','Select the type of payment option here. Most payment options (e.g. cash on delivery, direct debit etc.) can be realized by selecting "Standard".');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['published']									= array('Published','Tick this to make this payment option available in your shop.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['sorting']									= array('Sorting number');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeType']									= array('Type of charge calculation','Define here how charges will be calculated.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeValue']									= array('Charge');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeAddCouponToValueOfGoods']					= array('Include coupons in value of goods', 'If the charge calculation is based on the value of goods, this checkbox allows you to define whether coupon values should be included in the value of goods or not.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeAddShippingToValueOfGoods']				= array('Include shipping fee in value of goods', 'If the charge calculation is based on the value of goods, this checkbox allows you to define whether the shipping fee should be included in the value of goods or not.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeFormula']									= array('Forumla for charge calculation', 'Always use the dot sign as the decimal separator. Available placeholders: ##totalValueOfGoods##, ##totalWeightOfGoods##, ##totalValueOfCoupons##, ##shippingFee##. Beside conventional calculations, using ternary operators is also possible. Therefore, the following example would work: ##totalValueOfGoods## > 300 ? 0 : (##totalWeightOfGoods## <= 10 ? 10 : (##totalWeightOfGoods## <= 20 ? 20 : (##totalWeightOfGoods## <= 30 ? 30 : 40)))');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeFormulaResultConvertToDisplayPrice']		= array('Convert the calculated charge into a display price', 'Choose this option if the result of your calculation is a net or gross price as defined in the basic MERCONIS settings, in order to display the correct charge for the customer. Don\'t choose this option if you perform a calculation that\'s based for example on the "value of goods" placeholder because in this case the result of your calculation is already the correct display price for the customer.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeWeightValues']							= array('Charge by weight (left side: up to which weight, right side: which price)','Define here up to which weight which fixed amount for payment will be charged.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feePriceValues']								= array('Charge by value of goods (left side: up to which value of goods, right side: which price)','Define here up to which value of goods which fixed amount for payment will be charged.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['excludedGroups']								= array('Groups to be excluded', 'Select the groups here for which this payment option shall not be available.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['weightLimitMin']								= array('Minimum weight', 'Enter the minimum weight for delivery here from which on this payment option shall be available. Enter "0" to ignore this value.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['weightLimitMax']								= array('Maximum weight', 'Enter the maximum weight for delivery here up to which this payment option shall be available. Enter "0" to ignore this value.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitMin']								= array('Minimum value of goods', 'Enter the minimum value of goods here from which on this payment option shall be available. Enter "0" to ignore this value.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitMax']								= array('Maximum value of goods', 'Enter the maximum value of goods here up to which this payment option shall be available. Enter "0" to ignore this value.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitAddCouponToValueOfGoods']			= array('Include coupons in value of goods', 'This checkbox allows you to define whether coupon values should be included in the value of goods when checking the minimum or maximum value of goods.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitAddShippingToValueOfGoods']		= array('Include shipping fee in value of goods', 'This checkbox allows you to define whether the shipping fee should be included in the value of goods when checking the minimum or maximum value of goods.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['countries']									= array('Country selection', 'Please enter the countries as a list separated by commas.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['countriesAsBlacklist']						= array('Interpret country selection as a blacklist');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['cssID']										= array('CSS ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['cssClass']									= array('CSS class');

	/*
	 * PayPal-Bezeichnungen
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalAPIUsername']							= array('PayPal API user name', 'Please enter the value here of which you learn when you have your API access data displayed in your PayPal account.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalAPIPassword']							= array('PayPal API password', 'Please enter the value here of which you learn when you have your API access data displayed in your PayPal account.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalAPISignature']							= array('PayPal API signature', 'Please enter the value here of which you learn when you have your API access data displayed in your PayPal account.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalSecondForm']							= array('Form after PayPal authentication', 'Please select the form to be displayed to get back to PayPal again after the first authentication in order to enable your customer to change data, if required.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalGiropayRedirectForm']					= array('Form for Giropay/bank transfer forwarding', 'Please select the form to be displayed after completion of the order from which your customer will be forwarded to PayPal payment via Giropay/bank transfer to complete the payment process.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalGiropaySuccessPages']					= array('Page after successful Giropay payment', 'Please select the page here which will be displayed after a successful Giropay payment. Should you run a multilingual shop, please select the respective page for each language.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalGiropayCancelPages']					= array('Page after Giropay abortion', 'Please select the page here which will be displayed after a potential abortion of a Giropay payment procedure. Should you run a multilingual shop, please select the respective page for each language.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalBanktransferPendingPages']				= array('Page after payment via bank transfer', 'Please select the page here which will be displayed after payment via banktransfer. Should you run a multilingual shop, please select the respective page for each language.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameFirstname']				= array('Field name in checkout form for "First name"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameLastname']				= array('Field name in checkout form for "Family name"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameStreet']					= array('Field name in checkout form for "Street"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameCity']					= array('Field name in checkout form for "City"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNamePostal']					= array('Field name in checkout form for "Zip code"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameState']					= array('Field name in checkout form for "Federal state/region"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameCountryCode']			= array('Field name in checkout form for "Country"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalLiveMode']								= array('Live mode', 'Please activate this checkbox if your PayPal payment module shall go live. Please note that the API access data which you store in this payment module are different depending on whether the PayPal payment module is in live mode or sandbox mode. If this checkbox is not activated, the payment module will communicate with the PayPal sandbox, a test environment in which no real payments take place. The sandbox mode should be used for preliminary tests with this payment module on condition that real customers cannot use this payment option while the tests take place.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShowItems']								= array('Transfer single positions to PayPal', 'Activate this checkbox if you want information on single positions of the order to be transferred to PayPal.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypal_legend']								= 'PayPal settings';

	/*
     * PayPalCheckout-Bezeichnungen
     */
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_legend']                          = 'PayPal Checkout settings';
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_clientID']                    = array('PayPal Checkout Client ID', 'Please enter the value here of which you learn when you have your API access data displayed in your PayPal account.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_clientSecret']                = array('PayPal Checkout Client Secret', 'Please enter the value here of which you learn when you have your API access data displayed in your PayPal account.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_liveMode']                    = array('Live mode', 'Please activate this checkbox if your PayPal payment module shall go live. Please note that the API access data which you store in this payment module are different depending on whether the PayPal payment module is in live mode or sandbox mode. If this checkbox is not activated, the payment module will communicate with the PayPal sandbox, a test environment in which no real payments take place. The sandbox mode should be used for preliminary tests with this payment module on condition that real customers cannot use this payment option while the tests take place.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_logMode']						= array('Logging', 'Please select whether a log file should be written to /system/logs and if so, which log level to use');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameFirstname']				= array('Field name in checkout form for "First name"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameLastname']				= array('Field name in checkout form for "Family name"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameStreet']					= array('Field name in checkout form for "Street"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameCity']					= array('Field name in checkout form for "City"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNamePostal']					= array('Field name in checkout form for "Zip code"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameState']					= array('Field name in checkout form for "Federal state/region" (Placeholder possible)', 'Please enter the field name of the respective input field in the checkout form here. The placeholder "##value::fieldname##" allows to specify a dynamic field name depending on a value present in another form field. Example: Entering "province_##value::country##" results in the field name "province_us" if "USA" is selected as country in the "country" field, or "province_ca" if Canada is selected as country. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameCountryCode']			= array('Field name in checkout form for "Country"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');

    /*
     * PayPalPlus-Bezeichnungen
     */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_legend']						= 'PayPal Plus settings';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_clientID']							= array('PayPal Plus Client ID', 'Please enter the value here of which you learn when you have your API access data displayed in your PayPal account.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_clientSecret']						= array('PayPal Plus Client Secret', 'Please enter the value here of which you learn when you have your API access data displayed in your PayPal account.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_liveMode']						= array('Live mode', 'Please activate this checkbox if your PayPal payment module shall go live. Please note that the API access data which you store in this payment module are different depending on whether the PayPal payment module is in live mode or sandbox mode. If this checkbox is not activated, the payment module will communicate with the PayPal sandbox, a test environment in which no real payments take place. The sandbox mode should be used for preliminary tests with this payment module on condition that real customers cannot use this payment option while the tests take place.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_logMode']						= array('Logging', 'Please select whether a log file should be written to /system/logs and if so, which log level to use');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameFirstname']				= array('Field name in checkout form for "First name"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameLastname']				= array('Field name in checkout form for "Family name"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameStreet']					= array('Field name in checkout form for "Street"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameCity']					= array('Field name in checkout form for "City"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNamePostal']					= array('Field name in checkout form for "Zip code"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameState']					= array('Field name in checkout form for "Federal state/region" (Placeholder possible)', 'Please enter the field name of the respective input field in the checkout form here. The placeholder "##value::fieldname##" allows to specify a dynamic field name depending on a value present in another form field. Example: Entering "province_##value::country##" results in the field name "province_us" if "USA" is selected as country in the "country" field, or "province_ca" if Canada is selected as country. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameCountryCode']			= array('Field name in checkout form for "Country"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNamePhone']			= array('Field name in checkout form for "Phone"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. If these values are not correctly transferred to PayPal, it might happen that you cannot take advantage of various PayPal service options. In this connection, please also make sure to define reasonable mandatory fields in the checkout form and to only permit values for the respective fields of which you know that PayPal will accept them.');

	/*
	 * PayOne-Bezeichnungen
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_legend']						= 'PAYONE settings';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_subaccountId']						= array('PAYONE Sub account ID', 'Please enter the value here of which you learn when you have the API parameter tab displayed in the payment portal section of the PAYONE Merchant Interface.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_portalId']							= array('PAYONE Portal ID', 'Please enter the value here of which you learn when you have the API parameter tab displayed in the payment portal section of the PAYONE Merchant Interface.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_key']							= array('PAYONE Portal key', 'Please enter the value here of which you learn when you have the API parameter tab displayed in the payment portal section of the PAYONE Merchant Interface.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_liveMode']						= array('Live mode', 'Please activate this checkbox if you want to go live with the PAYONE module.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_clearingtype']	= array('Clearing type', 'Please choose the clearing type used by PAYONE');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_clearingtype']['options'] = array(
		'elv' => 'Debit payment',
		'cc' => 'Credit card',
		'vor' => 'Prepayment',
		'rec' => 'Invoice',
		'sb' => 'Online bank transfer',
		'wlt' => 'E-wallet',
		'fnc' => 'Financing'
	);
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameFirstname']	= array('Field name in checkout form for payone parameter "firstname"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameLastname']	= array('Field name in checkout form for payone parameter "lastname"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameCompany']	= array('Field name in checkout form for payone parameter "company"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameStreet']	= array('Field name in checkout form for payone parameter "street"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameAddressaddition']	= array('Field name in checkout form for payone parameter "addressaddition"', 'Please enter the field name of the respective input field in the checkout form here. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameZip']	= array('Field name in checkout form for payone parameter "zip"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameCity']	= array('Field name in checkout form for payone parameter "city"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameCountry']	= array('Field name in checkout form for payone parameter "country"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameEmail']	= array('Field name in checkout form for payone parameter "email"', 'Please enter the field name of the respective input field in the checkout form here. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameTelephonenumber']	= array('Field name in checkout form for payone parameter "telephonenumber"', 'Please enter the field name of the respective input field in the checkout form here. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameBirthday']	= array('Field name in checkout form for payone parameter "birthday"', 'Please enter the field name of the respective input field in the checkout form here. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameGender']	= array('Field name in checkout form for payone parameter "gender"', 'Please enter the field name of the respective input field in the checkout form here. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNamePersonalid']	= array('Field name in checkout form for payone parameter "personalid"', 'Please enter the field name of the respective input field in the checkout form here. Please have a look at the documentation of the payone frontend platform in order to learn more about the required fields and valid values.');

	/*
	 * VR-Pay-Bezeichnungen
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_legend'] = 'VR Pay settings';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_userId'] = array('User ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_password'] = array('Password');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_token'] = array('Token');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_entityId'] = array('Entity ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_liveMode'] = array('Live mode');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_testMode'] = array('Test mode');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_testMode']['options'] = array(
		'INTERNAL' => 'INTERNAL (VR Pay simulators)',
		'EXTERNAL' => 'EXTERNAL (uses the payment instrument sandboxes)'
	);
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_paymentInstrument'] = array('Payment instrument');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_paymentInstrument']['options'] = array(
		'creditcard' => 'Credit card',
		'giropay' => 'Giropay',
		'paydirekt' => 'Paydirekt',
		'klarna_invoice' => 'Klarna Invoice',
		'directdebit_sepa' => 'SEPA Direct debit',
		'sofortueberweisung' => 'Sofort payment',
		'paypal' => 'Pay Pal',
		'easycredit_ratenkauf' => 'Ratenkauf by easyCredit'
	);
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_creditCardBrands'] = array('Accepted credit cards');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_creditCardBrands']['options']		= array(
		'AMEX' => 'AMEX',
		'DINERS' => 'DINERS',
		'JCB' => 'JCB',
		'MASTERCARD' => 'MASTER',
		'VISA' => 'VISA'
	);
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_street1']	= array('Field name in checkout form for "street"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_city']	= array('Field name in checkout form for "city"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_postcode']	= array('Field name in checkout form for "postcode"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_country']	= array('Field name in checkout form for "country"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_givenName']	= array('Field name in checkout form for "firstname"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_surname']	= array('Field name in checkout form for "lastname"', 'Please enter the field name of the respective input field in the checkout form here. If you use fields for an alternative dispatch address, please make sure to use the same field names for corresponding fields with the appended character string "_alternative" at the end.');

	/*
	 * Saferpay-Bezeichnungen
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_legend']						= 'SAFERPAY settings';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_username']						= array('Username');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_password']						= array('Password');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_customerId']					= array('Customer ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_terminalId']					= array('Terminal ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_merchantEmail']					= array('Merchant\'s email address');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_liveMode']						= array('Live mode', 'Please activate this checkbox if you want to go live with the PAYONE module.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_captureInstantly']				= array('Capture instantly, if possible');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_paymentMethods']				= array('Payment Methods', 'Please choose the payment methods used by Saferpay');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_paymentMethods']['options']		= array(
		'AMEX' => 'AMEX',
		'BONUS' => 'BONUS',
		'DINERS' => 'DINERS',
		'DIRECTDEBIT' => 'DIRECTDEBIT',
		'EPRZELEWY' => 'EPRZELEWY',
		'EPS' => 'EPS',
		'GIROPAY' => 'GIROPAY',
		'IDEAL' => 'IDEAL',
		'INVOICE' => 'INVOICE',
		'JCB' => 'JCB',
		'MAESTRO' => 'MAESTRO',
		'MASTERCARD' => 'MASTERCARD',
		'MYONE' => 'MYONE',
		'PAYPAL' => 'PAYPAL',
		'POSTCARD' => 'POSTCARD',
		'POSTFINANCE' => 'POSTFINANCE',
		'SAFERPAYTEST' => 'SAFERPAYTEST',
		'SOFORT' => 'SOFORT',
		'VISA' => 'VISA',
		'VPAY' => 'VPAY',
		'TWINT' => 'TWINT'
	);
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_wallets']				= array('Wallets', 'Please choose the wallets used by Saferpay');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_wallets']['options']		= array(
		'MASTERPASS' => 'MASTERPASS'
	);
	
	/*
	 * sofortbanking
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['sofortueberweisungConfigkey']				= array('"Online Bank Transfer." configuration key', 'Please enter the configuration key of your "Online Bank Transfer." project here. If you don\'t know your configuration key, you can find it in your "Online Bank Transfer." user account.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['sofortueberweisungUseCustomerProtection']	= array('Use customer protection (with SOFORT bank account)', 'Define whether the "Online Bank Transfer." customer protection should be used. Please note that you are only allowed to use this option if you have a SOFORT bank account. Please check with the payment provider to find out about your qualification to offer customer protection.');
		
	/*
	 * Santander WebQuick
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickVendorNumber']				= array('Santander WebQuick vendor number');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickVendorPassword']	= array('Santander WebQuick password');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickLiveMode']	= array('Use Santander WebQuick live mode');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickMinAge'] = array('required age');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameSalutation']	= array('Field name in checkout form for "salutation"', 'Please make sure that the field can only have the values "Herr" and "Frau". Passing this information to Santander is optional. Just leave this field blank if you don\'t gather this information from your customer.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameFirstName']	= array('Field name in checkout form for "first name"', 'Gathering this information from your customer and passing it to Santander is mandatory. Please enter the field name in any case.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameLastName']	= array('Field name in checkout form for "last name"', 'Gathering this information from your customer and passing it to Santander is mandatory. Please enter the field name in any case.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameEmailAddress']	= array('Field name in checkout form for "email address"', 'Passing this information to Santander is optional. Just leave this field blank if you don\'t gather this information from your customer.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameStreet']	= array('Field name in checkout form for "street"', 'Passing this information to Santander is optional. Just leave this field blank if you don\'t gather this information from your customer.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameCity']	= array('Field name in checkout form for "city"', 'Passing this information to Santander is optional. Just leave this field blank if you don\'t gather this information from your customer.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameZipCode']	= array('Field name in checkout form for "zip code"', 'Passing this information to Santander is optional. Just leave this field blank if you don\'t gather this information from your customer.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameCountry']	= array('Field name in checkout form for "country"', 'Passing this information to Santander is optional. Just leave this field blank if you don\'t gather this information from your customer.');
	
	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['title_legend']			= 'Designation';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['steuersatz_legend']	= 'Tax rate';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['afterCheckout_legend']			= 'After completion of the order';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['type_legend']				= 'Type of payment option';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['published_legend']		= 'Publish';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['fee_legend']				= 'Charges';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['excludedGroups_legend']	= 'Group-related settings';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['weightLimit_legend']		= 'Weight restrictions';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimit_legend']		= 'Restrictions of the value of goods';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['countryLimit_legend']		= 'Restrictions of permitted countries';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['misc_legend']		= 'Miscellaneous';
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['new']        = array('New payment option', 'Define a new payment option');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['edit']        = array('Edit payment option', 'Edit payment option ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['delete']        = array('Delete payment option', 'Delete payment option ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['copy']        = array('Copy payment option', 'Copy payment option ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['show']        = array('Show details', 'Show details of payment option ID %s');
	
	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeType']['options'] = array(
		'none' => array('Free of charge'),
		'fixed' => array('Fixed amount', 'Select this option if you would like to define the charge as a fixed amount.'),
		'percentaged' => array('Percentage', 'Select this option if you would like to define the charge as a percentage value.'),
		'weight' => array('By weight', 'Select this option if you would like to define the charge as a fixed amount depending on the total weight of the ordered goods.'),
		'price' => array('By value of goods', 'Select this option if you would like to define the charge as a fixed amount depending on the value of the ordered goods.'),
		'weightAndPrice' => array('By weight and value of goods', 'Select this option if you would like to define the charge as a fixed amount depending on the weight and value of the ordered goods. Please note that the charges which you will state hereafter will be added up accordingly.'),
		'formula' => array('Calculation formula', 'Define a formula for charge calcualation using placeholders.')
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['dynamicSteuersatzType']['options'] = array(
		'none' => 'no dynamics',
		'main' => 'follow the main service',
		'max' => 'highest used',
		'min' => 'lowest used'
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['type']['options'] = array(
		'Standard' => array('Standard', 'Most payment options like "Invoice", "Cash on delivery" and "Direct debit authorization" can be realized with this standard module.'),
		'PayPal' => array('Payment via PayPal', 'Select this option if you wish to make payment via PayPal available by using this payment module. Please note that you must be a registered PayPal user and that the API access data provided by PayPal are required.'),
		'Sofortueberweisung' => array('Payment in advance via "Online Bank Transfer."'),
		'Santander WebQuick' => array('Financing with Santander'),
		'PayPal Plus' => array('Payment via PayPal Plus'),
		'PAYONE' => array('Payment via PAYONE'),
		'SAFERPAY' => array('Payment via Saferpay'),
		'VR Pay' => array('Payment via VR Pay')
	);
