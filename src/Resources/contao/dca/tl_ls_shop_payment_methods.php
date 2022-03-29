<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_payment_methods'] = array(
    'config' => array(
        'dataContainer' => 'Table',
        'onload_callback' => array(
            array('Merconis\Core\ls_shop_payment_methods','modifyDCA')
        ),
        'sql' => array
        (
            'engine' => 'MyISAM',
            'charset' => 'utf8 COLLATE utf8_general_ci',
            'keys' => array
            (
                'id' => 'primary'
            )
        )
    ),

    'list' => array(
        'sorting' => array(
            'mode' => 1,
            'flag' => 1,
            'fields' => array('sorting', 'title'),
            'disableGrouping' => true,
            'panelLayout' => 'filter;search,limit'
        ),

        'label' => array(
            'fields' => array('title', 'alias'),
            'format' => '<strong>%s</strong> <span style="font-style: italic;">(Alias: %s)</span>'
        ),

        'global_operations' => array(
            'all' => array(
                'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
            )
        ),

        'operations' => array(
            'edit' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'copy' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['copy'],
                'href'                => 'act=copy',
                'icon'                => 'copy.gif'
            ),
            'delete' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
                'button_callback'	=>	array('Merconis\Core\ls_shop_payment_methods','getDeleteButton')
            ),
            'show' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )

        )
    ),

    'palettes' => array(
        '__selector__' => array('dynamicSteuersatzType','feeType'),
        'default' => '{title_legend},title,alias,description;{type_legend},type,formAdditionalData;{steuersatz_legend},dynamicSteuersatzType;{excludedGroups_legend},excludedGroups;{weightLimit_legend},weightLimitMin,weightLimitMax;{priceLimit_legend},priceLimitMin,priceLimitMax,priceLimitAddCouponToValueOfGoods,priceLimitAddShippingToValueOfGoods;{countryLimit_legend},countries,countriesAsBlacklist;{fee_legend},feeType;{afterCheckout_legend},infoAfterCheckout,additionalInfo;{published_legend},published;{misc_legend},cssID,cssClass,sorting'
    ),

    'subpalettes' => array(
        'dynamicSteuersatzType_none' => 'steuersatz',
        'feeType_fixed' => 'feeValue',
        'feeType_percentaged' => 'feeAddCouponToValueOfGoods,feeAddShippingToValueOfGoods,feeValue',
        'feeType_weight' => 'feeWeightValues',
        'feeType_price' => 'feeAddCouponToValueOfGoods,feeAddShippingToValueOfGoods,feePriceValues',
        'feeType_weightAndPrice' => 'feeAddCouponToValueOfGoods,feeAddShippingToValueOfGoods,feeWeightValues,feePriceValues',
        'feeType_formula' => 'feeFormula,feeFormulaResultConvertToDisplayPrice'
    ),

    'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),

        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'paypalAPIUsername' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paypalAPIPassword' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paypalAPISignature' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paypalShipToFieldNameFirstname' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paypalShipToFieldNameLastname' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paypalShipToFieldNameStreet' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paypalShipToFieldNameCity' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paypalShipToFieldNamePostal' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paypalShipToFieldNameState' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paypalShipToFieldNameCountryCode' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paypalLiveMode' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'paypalShowItems' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'paypalSecondForm' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'paypalGiropayRedirectForm' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'paypalGiropaySuccessPages' => array (
            'sql'                     => "blob NULL"
        ),

        'paypalGiropayCancelPages' => array (
            'sql'                     => "blob NULL"
        ),

        'paypalBanktransferPendingPages' => array (
            'sql'                     => "blob NULL"
        ),

        'sofortueberweisungConfigkey' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'sofortueberweisungUseCustomerProtection' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'santanderWebQuickVendorNumber' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'santanderWebQuickVendorPassword' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'santanderWebQuickLiveMode' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'santanderWebQuickMinAge' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '18'"
        ),

        'santanderWebQuickFieldNameSalutation' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'santanderWebQuickFieldNameFirstName' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'santanderWebQuickFieldNameLastName' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'santanderWebQuickFieldNameEmailAddress' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'santanderWebQuickFieldNameStreet' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'santanderWebQuickFieldNameCity' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'santanderWebQuickFieldNameZipCode' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'santanderWebQuickFieldNameCountry' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_clientID' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_clientSecret' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_liveMode' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'payPalPlus_logMode' => array (
            'sql'                     => "varchar(255) NOT NULL default 'NONE'"
        ),

        'payPalPlus_shipToFieldNameFirstname' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_shipToFieldNameLastname' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_shipToFieldNameStreet' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_shipToFieldNameCity' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_shipToFieldNamePostal' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_shipToFieldNameState' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_shipToFieldNameCountryCode' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_shipToFieldNamePhone' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_subaccountId' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_portalId' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_key' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_liveMode' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'payone_clearingtype' => array (
            'sql'                     => "varchar(16) NOT NULL default ''"
        ),

        'payone_fieldNameFirstname' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameLastname' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameCompany' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameStreet' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameAddressaddition' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameZip' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameCity' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameCountry' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameEmail' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameTelephonenumber' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameBirthday' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNameGender' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_fieldNamePersonalid' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'saferpay_username' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'saferpay_password' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'saferpay_customerId' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'saferpay_terminalId' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'saferpay_merchantEmail' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'saferpay_liveMode' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'saferpay_captureInstantly' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'saferpay_paymentMethods' => array (
            'sql'                     => "blob NULL"
        ),

        'saferpay_wallets' => array (
            'sql'                     => "blob NULL"
        ),

        'vrpay_userId' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'vrpay_password' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'vrpay_token' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'vrpay_entityId' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'vrpay_liveMode' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'vrpay_testMode' => array (
            'sql'                     => "varchar(8) NOT NULL default ''"
        ),

        'vrpay_paymentInstrument' => array (
            'sql'                     => "varchar(32) NOT NULL default ''"
        ),

        'vrpay_creditCardBrands' => array (
            'sql'                     => "blob NULL"
        ),

        'vrpay_fieldName_street1' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'vrpay_fieldName_city' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'vrpay_fieldName_postcode' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'vrpay_fieldName_country' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'vrpay_fieldName_givenName' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'vrpay_fieldName_surname' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'title' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['title'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'maxlength'=>255),
            'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'alias' => array (
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['alias'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'clr topLinedGroup'),
            'save_callback' => array (
                array('Merconis\Core\ls_shop_payment_methods', 'generateAlias')
            ),
            'sorting' => true,
            'flag' => 11,
            'search' => true,
            'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"
        ),

        'description' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['description'],
            'exclude' => true,
            'inputType'               => 'textarea',
            'eval'                    => array('allowHtml' => true, 'preserveTags' => true, 'tl_class' => 'clr', 'merconis_multilanguage' => true, 'merconis_multilanguage_topLinedGroup' => true),
            'sql'                     => "text NULL"
        ),

        'type' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['type'],
            'inputType' => 'select',
            'options_callback' => array('Merconis\Core\ls_shop_payment_methods', 'getPaymentModulesAsOptions'),
            'reference' => $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['type']['options'],
            'eval' => array('submitOnChange' => true, 'tl_class'=>'w50', 'helpwizard' => true),
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default 'standard'"
        ),

        'formAdditionalData' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['formAdditionalData'],
            'inputType' => 'select',
            'foreignKey' => 'tl_form.title',
            'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'dynamicSteuersatzType' => array(
            'exclude' => true,
            'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['dynamicSteuersatzType'],
            'inputType' => 'select',
            'options' => array('none', 'main', 'max', 'min'),
            'reference' => $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['dynamicSteuersatzType']['options'],
            'eval' => array('submitOnChange' => true, 'tl_class'=>'w50'),
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default 'none'"
        ),

        'steuersatz' => array(
            'exclude'		=> true,
            'label'			=> &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['steuersatz'],
            'inputType'		=> 'select',
            'options_callback'	=> array('Merconis\Core\ls_shop_generalHelper','getSteuersatzOptions'),
            'eval' => array('tl_class'=>'w50'),
            'filter' => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'excludedGroups' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['excludedGroups'],
            'exclude' => true,
            'inputType' => 'checkboxWizard',
            'foreignKey' => 'tl_member_group.name',
            'eval' => array('tl_class'=>'clr','multiple'=>true),
            'sql'                     => "blob NULL"
        ),

        'weightLimitMin' => array(
            'exclude'		=>	true,
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['weightLimitMin'],
            'inputType'		=>	'text',
            'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50'),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'weightLimitMax' => array(
            'exclude'		=>	true,
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['weightLimitMax'],
            'inputType'		=>	'text',
            'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50'),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'priceLimitMin' => array(
            'exclude'		=>	true,
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitMin'],
            'inputType'		=>	'text',
            'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50'),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'priceLimitMax' => array(
            'exclude'		=>	true,
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitMax'],
            'inputType'		=>	'text',
            'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50'),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'priceLimitAddCouponToValueOfGoods' => array(
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitAddCouponToValueOfGoods'],
            'inputType'               => 'checkbox',
            'eval'					  =>	array('tl_class' => 'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'priceLimitAddShippingToValueOfGoods' => array(
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitAddShippingToValueOfGoods'],
            'inputType'               => 'checkbox',
            'eval'					  =>	array('tl_class' => 'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'countries' => array(
            'exclude'		=>	true,
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['countries'],
            'inputType'		=>	'text',
            'eval'			=>	array('tl_class' => 'w50'),
            'search' => true,
            'sql'                     => "text NULL"
        ),

        'countriesAsBlacklist' => array(
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['countriesAsBlacklist'],
            'inputType'               => 'checkbox',
            'eval'					  =>	array('tl_class' => 'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'feeType' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeType'],
            'inputType' => 'select',
            'options' => array('none', 'fixed', 'percentaged', 'weight', 'price', 'weightAndPrice', 'formula'),
            'reference' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeType']['options'],
            'eval' => array('submitOnChange' => true, 'helpwizard' => true),
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default 'none'"
        ),

        'feeValue' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeValue'],
            'inputType' => 'text',
            'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class'=>'w50'),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'feeAddCouponToValueOfGoods' => array(
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeAddCouponToValueOfGoods'],
            'inputType'               => 'checkbox',
            'eval'					  =>	array('tl_class' => 'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'feeAddShippingToValueOfGoods' => array(
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeAddShippingToValueOfGoods'],
            'inputType'               => 'checkbox',
            'eval'					  =>	array('tl_class' => 'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'feeFormula' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeFormula'],
            'inputType' => 'text',
            'eval'			=>	array('rgxp' => 'feeFormula', 'tl_class'=>'long', 'decodeEntities' => true),
            'sql'                     => "text NULL"
        ),

        'feeFormulaResultConvertToDisplayPrice' => array(
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeFormulaResultConvertToDisplayPrice'],
            'inputType'               => 'checkbox',
            'eval'					  =>	array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'feeWeightValues' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeWeightValues'],
            'inputType' => 'text',
            'eval'			=> array(
                'rgxp' => 'numberWithDecimalsLeftAndRight',
                'tl_class' => 'merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
                'data-merconis-widget-options' => '
					{
						"arr_fields": [
							{
								"type": "text",
								"label": ""
							},
							{
								"type": "text",
								"label": ""
							}
						],
						"cssClass": ""
					}
				'
            ),
            'sql'                     => "text NULL'"
        ),

        'feePriceValues' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feePriceValues'],
            'inputType' => 'text',
            'eval'			=> array(
                'rgxp' => 'numberWithDecimalsLeftAndRight',
                'tl_class' => 'merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
                'data-merconis-widget-options' => '
					{
						"arr_fields": [
							{
								"type": "text",
								"label": ""
							},
							{
								"type": "text",
								"label": ""
							}
						],
						"cssClass": ""
					}
				'
            ),
            'sql'                     => "text NULL"
        ),

        'infoAfterCheckout' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['infoAfterCheckout'],
            'exclude' => true,
            'inputType'               => 'textarea',
            'eval'                    => array('rte'=>'tinyMCE', 'tl_class' => 'clr', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true),
            'sql'                     => "text NULL"
        ),

        'additionalInfo' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['additionalInfo'],
            'exclude' => true,
            'inputType'               => 'textarea',
            'eval'                    => array('rte'=>'tinyMCE', 'tl_class' => 'clr', 'merconis_multilanguage' => true),
            'sql'                     => "text NULL"
        ),

        'published' => array(
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['published'],
            'inputType'               => 'checkbox',
            'eval'                    => array('doNotCopy'=>true),
            'filter' => true,
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'cssID' => array(
            'exclude'		=>	true,
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['cssID'],
            'inputType'		=>	'text',
            'eval'			=>	array('tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'cssClass' => array(
            'exclude'		=>	true,
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['cssClass'],
            'inputType'		=>	'text',
            'eval'			=>	array('tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'sorting' => array(
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['sorting'],
            'exclude' => true,
            'inputType'		=>	'text',
            'eval'			=>	array('rgxp' => 'number', 'tl_class' => 'w50', 'mandatory' => true),
            'sorting' => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        )
    )
);



class ls_shop_payment_methods extends \Backend {
    public function __construct() {
        parent::__construct();
    }

    public function generateAlias($varValue, \DataContainer $dc) {
        $autoAlias = false;

        $currentTitle = isset($dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()}) && $dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()} ? $dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()} : $dc->activeRecord->title;

        // Generate an alias if there is none
        if ($varValue == '') {
            $autoAlias = true;
            $varValue = \StringUtil::generateAlias($currentTitle);
        }
        $objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_payment_methods WHERE id=? OR alias=?")
            ->execute($dc->id, $varValue);

        // Check whether the alias exists
        if ($objAlias->numRows > 1) {
            if (!$autoAlias) {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }
            $varValue .= '-' . $dc->id;
        }

        return $varValue;
    }

    /*
     * Diese Funktion modifiziert das DCA in Abh ngigkeit vom gew hlten Zahlungsmodul.
     * Es werden hierbei die in der Zahlungsmodul-Definition definierten BE_formFields eingetragen
     */
    public function modifyDCA($dc)
    {
        $obj_paymentModule = new ls_shop_paymentModule();
        if (!$dc->id) {
            /*
             * Handelt es sich bei dem Aufruf nicht um einen datensatzbezogenen Aufruf,
             * so wird die Verarbeitung dieser Funktion abgebrochen
             */
            return;
        }
        $objPaymentMethod = \Database::getInstance()->prepare("SELECT * FROM `tl_ls_shop_payment_methods` WHERE `id` = ?")
            ->limit(1)
            ->execute($dc->id);
        $objPaymentMethod->first();

        /*
         * We don't do anything if we don't have any payment method specific BE_formFields to add because
         * in this case subpalette form fields wouldn't make any sense even if they were registered in the payment
         * method class.
         */
        if (!is_array($obj_paymentModule->types[$objPaymentMethod->type]['BE_formFields'])) {
            return;
        }

        $this->addBeFormFields($objPaymentMethod->type);

        $this->addBeFormFieldSubpalettes($objPaymentMethod->type);
    }

    protected function addBeFormFields($str_paymentMethodType) {
        $obj_paymentModule = new ls_shop_paymentModule();
        if (!is_array($obj_paymentModule->types[$str_paymentMethodType]['BE_formFields'])) {
            return;
        }

        $this->addBeFormFieldsAndStandardLabels($obj_paymentModule->types[$str_paymentMethodType]['BE_formFields']);

        /*
         * Einfügen der BE_formFields in die Default-Palette
         */
        $paletteInsertion = ';{' . $obj_paymentModule->types[$str_paymentMethodType]['typeCode'] . '_legend},';
        foreach ($obj_paymentModule->types[$str_paymentMethodType]['BE_formFields'] as $formFieldTitle => $formFieldInfo) {
            $paletteInsertion .= $formFieldTitle . ',';
        }
        $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['palettes']['default'] = preg_replace('/(;\{excludedGroups_legend\})/siU', $paletteInsertion . '$1', $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['palettes']['default']);
    }

    protected function addBeFormFieldSubpalettes($str_paymentMethodType) {
        $obj_paymentModule = new ls_shop_paymentModule();
        if (!is_array($obj_paymentModule->types[$str_paymentMethodType]['BE_formFields_subpalettes'])) {
            return;
        }

        foreach ($obj_paymentModule->types[$str_paymentMethodType]['BE_formFields_subpalettes'] as $str_subpaletteName => $arr_subpalette) {
            if (!in_array($arr_subpalette['selector'], $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['palettes']['__selector__'])) {
                $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['palettes']['__selector__'][] = $arr_subpalette['selector'];
            }

            $this->addBeFormFieldsAndStandardLabels($arr_subpalette['fields']);

            $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['subpalettes'][$str_subpaletteName] = implode(',', array_keys($arr_subpalette['fields']));
        }
    }

    protected function addBeFormFieldsAndStandardLabels($arr_fields) {
        /*
         * Insert the fields into the fields array of this DCA definition
         */
        array_insert($GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['fields'], 0, $arr_fields);

        /*
         * Assign the standard label
         */
        foreach ($arr_fields as $formFieldTitle => $formFieldInfo) {
            $GLOBALS['TL_DCA']['tl_ls_shop_payment_methods']['fields'][$formFieldTitle]['label'] = &$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods'][$formFieldTitle];
        }
    }

    public function getPaymentModulesAsOptions() {
        $paymentModules = array();
        $obj_paymentModule = new ls_shop_paymentModule();
        foreach ($obj_paymentModule->types as $paymentModuleName => $paymentModuleInfo) {
            $paymentModules[$paymentModuleName] = $paymentModuleInfo['title'];
        }
        return $paymentModules;
    }

    /*
     * Diese Funktion prüft, ob der Datensatz irgendwo im Shop verwendet wird und gibt nur dann
     * den funktionsfähigen Löschen-Button zurück, wenn der Datensatz nicht verwendet wird und
     * daher bedenkenlos gelöscht werden kann.
     */
    public function getDeleteButton($row, $href, $label, $title, $icon, $attributes) {
        $arr_methodIDsCurrentlyUsed = ls_shop_generalHelper::getPaymentOrShippingMethodsUsedInOrders('payment');

        if (!in_array($row['id'], $arr_methodIDsCurrentlyUsed)) {
            $button = '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
        } else {
            $button = \Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
        }

        return $button;
    }
}
