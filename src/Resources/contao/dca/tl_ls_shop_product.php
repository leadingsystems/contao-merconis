<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_product'] = array(
	'config' => array(
		'dataContainer' => 'Table',
		'ctable' => array('tl_ls_shop_variant'),
		'oncopy_callback' => array (
			array('Merconis\Core\ls_shop_generalHelper', 'attributeValueAllocationCopy'),
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'ondelete_callback' => array (
			array('Merconis\Core\ls_shop_generalHelper', 'attributeValueAllocationRemoveOrphanedRecords'),
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'onsubmit_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'onrestore_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		)
	),

	'list' => array(
		'sorting' => array(
			'mode' => 2,
			'fields' => array('id'),
			'flag' => 11,
			'panelLayout' => 'filter;sort,search,limit'
		),

		'label' => array(
			'fields' => array('title'),
			'format' => '%s',
			'label_callback' => array('Merconis\Core\tl_ls_shop_product_controller','createLabel')
		),

		'global_operations' => array(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),

		'operations' => array(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['edit'],
				'href'                => 'table=tl_ls_shop_variant',
				'icon'                => 'bundles/leadingsystemsmerconis/images/icons/editVariants.png',
				'attributes'          => 'class="contextmenu"'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'bundles/leadingsystemsmerconis/images/icons/editProduct.png'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('Merconis\Core\tl_ls_shop_product_controller', 'toggleIcon')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)

		)
	),
	'palettes' => array(
		'__selector__' => array('useGroupRestrictions', 'useGroupPrices_1', 'useGroupPrices_2', 'useGroupPrices_3', 'useGroupPrices_4', 'useGroupPrices_5', 'useScalePrice', 'useScalePrice_1', 'useScalePrice_2', 'useScalePrice_3', 'useScalePrice_4', 'useScalePrice_5'),
		'default' => '
			{lsShopTitleAndDescriptions_legend},
			title,
			description,
			shortDescription,
			keywords,
			pageTitle,
			pageDescription,
			flex_contents,
			flex_contentsLanguageIndependent;
			
			{lsShopProductCode_legend},
			lsShopProductCode,
			alias;

			{lsShopPublishAndState_legend},
			published,
			lsShopProductIsNew,
			lsShopProductIsOnSale'.(\Input::get('act') == 'editAll' ? '' : ',sorting').';
			
			{configurator_legend},
			configurator;
			
			{lsShopUnits_legend},
			lsShopProductQuantityUnit,
			lsShopProductMengenvergleichUnit;
			
			{lsShopPages_legend},
			pages;
			
			{groupRestrictions_legend},
			useGroupRestrictions;

			{lsShopProducer_legend},
			lsShopProductProducer;
			
			{lsShopImages_legend},
			lsShopProductMainImage,
			lsShopProductMoreImages;
			
			{lsShopAttributesAndValues_legend},
			lsShopProductAttributesValues;
			
			{lsShopPrice_legend},
			lsShopProductPrice,
			useScalePrice,
			lsShopProductPriceOld,
			useOldPrice,
			lsShopProductSteuersatz,
			lsShopProductWeight,
			lsShopProductQuantityDecimals,
			lsShopProductMengenvergleichDivisor;

			{lsShopPrice_1_legend},
			useGroupPrices_1;

			{lsShopPrice_2_legend},
			useGroupPrices_2;

			{lsShopPrice_3_legend},
			useGroupPrices_3;

			{lsShopPrice_4_legend},
			useGroupPrices_4;

			{lsShopPrice_5_legend},
			useGroupPrices_5;

			{lsShopStock_legend},
			lsShopProductDeliveryInfoSet;
			
			{lsShopRecommendedProducts_legend},
			lsShopProductRecommendedProducts;
			
			{associatedProducts_legend},
			associatedProducts;
			
			{lsShopTemplate_legend},
			lsShopProductDetailsTemplate
		'
	),

	/*
	 * FIXME: Implement associatedProducts in MPM and importer!
	 */

	'subpalettes' => array(
	    'useGroupRestrictions' => '
	        allowedGroups
	    ',

		'useGroupPrices_1' => '
			priceForGroups_1,
			lsShopProductPrice_1,
			useScalePrice_1,
			lsShopProductPriceOld_1,
			useOldPrice_1
		',

		'useGroupPrices_2' => '
			priceForGroups_2,
			lsShopProductPrice_2,
			useScalePrice_2,
			lsShopProductPriceOld_2,
			useOldPrice_2
		',

		'useGroupPrices_3' => '
			priceForGroups_3,
			lsShopProductPrice_3,
			useScalePrice_3,
			lsShopProductPriceOld_3,
			useOldPrice_3
		',

		'useGroupPrices_4' => '
			priceForGroups_4,
			lsShopProductPrice_4,
			useScalePrice_4,
			lsShopProductPriceOld_4,
			useOldPrice_4
		',

		'useGroupPrices_5' => '
			priceForGroups_5,
			lsShopProductPrice_5,
			useScalePrice_5,
			lsShopProductPriceOld_5,
			useOldPrice_5
		',

		'useScalePrice' => 'scalePriceType,scalePriceQuantityDetectionMethod,scalePriceQuantityDetectionAlwaysSeparateConfigurations,scalePriceKeyword,scalePrice',
		'useScalePrice_1' => 'scalePriceType_1,scalePriceQuantityDetectionMethod_1,scalePriceQuantityDetectionAlwaysSeparateConfigurations_1,scalePriceKeyword_1,scalePrice_1',
		'useScalePrice_2' => 'scalePriceType_2,scalePriceQuantityDetectionMethod_2,scalePriceQuantityDetectionAlwaysSeparateConfigurations_2,scalePriceKeyword_2,scalePrice_2',
		'useScalePrice_3' => 'scalePriceType_3,scalePriceQuantityDetectionMethod_3,scalePriceQuantityDetectionAlwaysSeparateConfigurations_3,scalePriceKeyword_3,scalePrice_3',
		'useScalePrice_4' => 'scalePriceType_4,scalePriceQuantityDetectionMethod_4,scalePriceQuantityDetectionAlwaysSeparateConfigurations_4,scalePriceKeyword_4,scalePrice_4',
		'useScalePrice_5' => 'scalePriceType_5,scalePriceQuantityDetectionMethod_5,scalePriceQuantityDetectionAlwaysSeparateConfigurations_5,scalePriceKeyword_5,scalePrice_5'
	),

	'fields' => array(
		'id' => array(
			'sql' => 'int(10) unsigned NOT NULL auto_increment'
		),

		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'decodeEntities' => true, 'maxlength'=>255),
			'sorting' => true,
			'flag' => 11,
			'search'		=> true
		),

		'description' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['description'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'decodeEntities' => true),
			'search'		=> true
		),

		'shortDescription' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['shortDescription'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'decodeEntities' => true),
			'search'		=> true
		),

		'keywords' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['keywords'],
			'exclude' => true,
			'inputType' => 'textarea',
			'eval' => array('style'=>'height:60px;', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'decodeEntities' => true),
			'search'		=> true
		),

        'pageTitle' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['pageTitle'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => false, 'tl_class'=>'w50', 'merconis_multilanguage' => true, 'decodeEntities' => true, 'maxlength'=>255),
            'sorting' => true,
            'flag' => 11,
            'search'		=> true
        ),

        'pageDescription' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['pageDescription'],
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => array('style'=>'height:60px;', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'decodeEntities' => true),
            'search'		=> true
        ),

        'flex_contents' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents'],
			'exclude' => true,
			'inputType' => 'text',
			'eval'                    => array(
				'tl_class'=>'clr merconis-component-autostart--merconisWidgetMultiText',
				'merconis_multilanguage' => true,
				'preserveTags' => true,
				'data-merconis-widget-options' => '
					{
						"arr_fields": [
							{
								"type": "text",
								"label": "'.$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents_label01'].'"
							},
							{
								"type": "textarea",
								"label": "'.$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents_label02'].'"
							}
						],
						"cssClass": "key-value-widget"
					}
				'
			)
		),

		'flex_contentsLanguageIndependent' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contentsLanguageIndependent'],
			'exclude' => true,
			'inputType' => 'text',
			'eval'                    => array(
				'tl_class'=>'clr topLinedGroup merconis-component-autostart--merconisWidgetMultiText',
				'preserveTags' => true,
				'data-merconis-widget-options' => '
					{
						"arr_fields": [
							{
								"type": "text",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contentsLanguageIndependent_label01'] ?? '').'"
							},
							{
								"type": "textarea",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contentsLanguageIndependent_label02'] ?? '').'"
							}
						],
						"cssClass": "key-value-widget"
					}
				'
			)
		),

		'lsShopProductCode' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductCode'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'unique' => true, 'mandatory' => true, 'decodeEntities' => true, 'maxlength'=>255),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_generalHelper', 'checkForUniqueProductCode')
			),
			'sorting' => true,
			'flag' => 11,
			'search'		=> true
		),

		'alias' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['alias'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'                    => array(
				'rgxp' => 'alnum',
				'doNotCopy' => true,
				'spaceToUnderscore' => true,
				'maxlength' => 128,
				'tl_class' => 'w50',
				'merconis_multilanguage' => true,
				'merconis_multilanguage_noTopLinedGroup' => false
			),
			'save_callback' => array (
				array('Merconis\Core\tl_ls_shop_product_controller', 'generateAlias')
			)

		),

		'published' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['published'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'filter'		=> true
		),

		'lsShopProductIsNew' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductIsNew'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'filter'		=> true
		),

		'lsShopProductIsOnSale' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductIsOnSale'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'filter'		=> true
		),

		'sorting' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['sorting'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'number', 'tl_class' => 'w50', 'mandatory' => true),
			'sorting' => true
		),

		'configurator' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['configurator'],
			'exclude' => true,
			'inputType' => 'select',
			'foreignKey' => 'tl_ls_shop_configurator.title',
			'eval' => array('includeBlankOption' => true),
			'filter' => true,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'lsShopProductQuantityUnit' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductQuantityUnit'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'merconis_picker_headline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['quantityUnitPickerHeadline'], 'decodeEntities' => true, 'maxlength' => 255),
			'filter'		=> true,
			'wizard' => array (
				array('Merconis\Core\ls_shop_generalHelper', 'beValuePickerWizard')
			)
		),

		'lsShopProductMengenvergleichUnit' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMengenvergleichUnit'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_picker_headline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['quantityComparisonUnitPickerHeadline'], 'decodeEntities' => true, 'maxlength' => 255),
			'wizard' => array (
				array('Merconis\Core\ls_shop_generalHelper', 'beValuePickerWizard')
			)
		),

		'pages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['pages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'),
			'save_callback' => array (
				array('Merconis\Core\tl_ls_shop_product_controller', 'convertPageSelection')
			)
		),

        'useGroupRestrictions' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupRestrictions'],
            'exclude' => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
            'filter'		=> true
        ),

        'allowedGroups' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['allowedGroups'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'foreignKey'              => 'tl_member_group.name',
            'eval'                    => array('multiple'=>true)
        ),

        'lsShopProductProducer' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductProducer'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'merconis_picker_headline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['producerPickerHeadline'], 'decodeEntities' => true, 'maxlength' => 255),
			'sorting' => true,
			'flag' => 11,
			'search'		=> true,
			'wizard' => array (
				array('Merconis\Core\ls_shop_generalHelper', 'beValuePickerWizard')
			)
		),

		'lsShopProductMainImage' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMainImage'],
			'exclude' => true,
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),

		'lsShopProductMoreImages' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMoreImages'],
			'exclude' => true,
			'inputType'		=>	'fileTree',
			'eval'			=> array('multiple' => true, 'fieldType'=>'checkbox', 'files'=>true, 'filesOnly' => true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF,flv,mp4,mp2,swf,mov,avi', 'tl_class'=>'clr')
		),

		'lsShopProductAttributesValues' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductAttributesValues'],
			'default'                 => '',
			'exclude' => true,
			'inputType'               => 'text',
			'eval'					  => array('tl_class' => 'merconis-component-autostart--merconisWidgetAttributesValues', 'decodeEntities' => true),
			'save_callback' => array (
				array('Merconis\Core\tl_ls_shop_product_controller', 'insertAttributeValueAllocationsInAllocationTable')
			)
		),

		'lsShopProductPrice' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true),
			'sorting' => true,
			'flag' => 11
		),

		'useScalePrice' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12'),
			'filter'		=> true
		),

		'scalePriceType' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr')
		),

		'scalePriceQuantityDetectionMethod' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50')
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'filter'		=> true
		),

		'scalePriceKeyword' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255),
			'search'		=> true,
			'filter'		=> true
		),

		'scalePrice' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
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
			)
		),

		'lsShopProductPriceOld' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true)
		),

		'useOldPrice' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12'),
			'filter'		=> true
		),

		'lsShopProductSteuersatz' => array(
			'label'			=> &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductSteuersatz'],
			'exclude' => true,
			'inputType'		=> 'select',
			'options_callback'	=> array('Merconis\Core\ls_shop_generalHelper','getNonDynamicSteuersatzOptions'),
			'eval'			=> array('tl_class' => 'w50'),
			'filter'		=> true
		),

		'lsShopProductWeight' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductWeight'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true)
		),

		'lsShopProductQuantityDecimals' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductQuantityDecimals'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('rgxp'=>'digit', 'maxlength'=>1, 'tl_class' => 'w50', 'mandatory' => true)
		),

		'lsShopProductMengenvergleichDivisor' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMengenvergleichDivisor'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals','tl_class' => 'w50', 'mandatory' => true)
		),

		/*
		 * Deviant price settings for group 1
		 */
		'useGroupPrices_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_1'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true
		),

		'priceForGroups_1' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true)
		),

		'lsShopProductPrice_1' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true)
		),

		'useScalePrice_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12')
		),

		'scalePriceType_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr')
		),

		'scalePriceQuantityDetectionMethod_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50')
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),

		'scalePriceKeyword_1' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255)
		),

		'scalePrice_1' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
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
			)
		),

		'lsShopProductPriceOld_1' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true)
		),

		'useOldPrice_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12')
		),

		/*
		 * Deviant price settings for group 2
		 */
		'useGroupPrices_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_2'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true
		),

		'priceForGroups_2' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true)
		),

		'lsShopProductPrice_2' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true)
		),

		'useScalePrice_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12')
		),

		'scalePriceType_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr')
		),

		'scalePriceQuantityDetectionMethod_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50')
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),

		'scalePriceKeyword_2' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255)
		),

		'scalePrice_2' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
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
			)
		),

		'lsShopProductPriceOld_2' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true)
		),

		'useOldPrice_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12')
		),

		/*
		 * Deviant price settings for group 3
		 */
		'useGroupPrices_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_3'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true
		),

		'priceForGroups_3' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true)
		),

		'lsShopProductPrice_3' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true)
		),

		'useScalePrice_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12')
		),

		'scalePriceType_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr')
		),

		'scalePriceQuantityDetectionMethod_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50')
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),

		'scalePriceKeyword_3' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255)
		),

		'scalePrice_3' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
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
			)
		),

		'lsShopProductPriceOld_3' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true)
		),

		'useOldPrice_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12')
		),

		/*
		 * Deviant price settings for group 4
		 */
		'useGroupPrices_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_4'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true
		),

		'priceForGroups_4' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true)
		),

		'lsShopProductPrice_4' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true)
		),

		'useScalePrice_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12')
		),

		'scalePriceType_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr')
		),

		'scalePriceQuantityDetectionMethod_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50')
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),

		'scalePriceKeyword_4' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255)
		),

		'scalePrice_4' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
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
			)
		),

		'lsShopProductPriceOld_4' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true)
		),

		'useOldPrice_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12')
		),

		/*
		 * Deviant price settings for group 5
		 */
		'useGroupPrices_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_5'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true
		),

		'priceForGroups_5' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true)
		),

		'lsShopProductPrice_5' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true)
		),

		'useScalePrice_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12')
		),

		'scalePriceType_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr')
		),

		'scalePriceQuantityDetectionMethod_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50')
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),

		'scalePriceKeyword_5' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255)
		),

		'scalePrice_5' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
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
			)
		),

		'lsShopProductPriceOld_5' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true)
		),

		'useOldPrice_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12')
		),



		'lsShopProductDeliveryInfoSet' => array(
			'label'			=> &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductDeliveryInfoSet'],
			'exclude' => true,
			'inputType'		=> 'select',
			'foreignKey'	=> 'tl_ls_shop_delivery_info.title',
			'eval'			=> array('tl_class' => 'w50', 'includeBlankOption' => true),
			'filter'		=> true,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'lsShopProductRecommendedProducts' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductRecommendedProducts'],
			'exclude' => true,
			'inputType'		=>	'ls_shop_productSelectionWizard',
			'eval'			=> array('tl_class'=>'clr')
		),

		'associatedProducts' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['associatedProducts'],
			'exclude' => true,
			'inputType'		=>	'ls_shop_productSelectionWizard',
			'eval'			=> array('tl_class'=>'clr')
		),

		'lsShopProductDetailsTemplate' => array(
			'label'					  => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductDetailsTemplate'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => $this->getTemplateGroup('template_productDetails_'),
			'eval'					  => array('tl_class' => 'w50', 'includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['blankOptionLabel'])
		)
	)
);

class tl_ls_shop_product_controller extends \Backend {

	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function generateAlias($str_value, \DataContainer $dc) {
		/*
		 * By default we don't expect to have to create an auto alias.
		 * Whether we have to do so or not, will be determined later
		 */
		$bln_createAutoAlias = false;

		/*
		 * The alias is a multilanguage field so we have to determine its language
		 * first in order to be able to create an auto alias from the corresponding
		 * title field.
		 * 
		 * If we can't find an underscore in the field name, we can't figure out
		 * the language and we probably don't deal with the expected field, so
		 * we return the field value unaltered.
		 */
		if (strpos($dc->field, '_') === false) {
			return $str_value;
		}
		$str_fieldLanguage = end(explode('_', $dc->field));

		$str_titleToUseForAutoAlias =
			(
					isset($dc->activeRecord->{'title_'.$str_fieldLanguage})
				&&	$dc->activeRecord->{'title_'.$str_fieldLanguage}
			)
			?	$dc->activeRecord->{'title_'.$str_fieldLanguage}
			:	$dc->activeRecord->title;

		/*
		 * If no alias value has been provided, we have to create an auto alias
		 */
		if ($str_value == '') {
			$bln_createAutoAlias = true;
			$str_value = \StringUtil::generateAlias($str_titleToUseForAutoAlias);
		}

		/*
		 * The alias must not be longer than 128 characters
		 */
		$str_value = substr($str_value, 0, 128);

		/*
		 * Check whether the alias already exists, i.e. we can already
		 * find a record with this alias
		 */
		$obj_dbres_recordForAlias = \Database::getInstance()->prepare("
			SELECT		`id`
			FROM		`tl_ls_shop_product`
			WHERE		`id` = ?
				OR		`".$dc->field."` = ?
		")
		->execute(
			$dc->id,
			$str_value
		);

		if ($obj_dbres_recordForAlias->numRows > 1) {
			/*
			 * If we don't create an auto alias, we throw an exception, which
			 * in this case displays an error message for this field.
			 * 
			 * If we create an auto alias, we add the record id to the alias
			 * to make it unique. When doing that, we have to make sure that
			 * the created alias still isn't longer than 128 characters.
			 */
			if (!$bln_createAutoAlias) {
				throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $str_value));
			}

			$str_aliasSuffix = '-'.$dc->id;
			$str_value = substr($str_value, 0, 128 - strlen($str_aliasSuffix)).$str_aliasSuffix;
		}

		return $str_value;
	}

	public function insertAttributeValueAllocationsInAllocationTable($str_value, \DataContainer $dc) {
		ls_shop_generalHelper::insertAttributeValueAllocationsInAllocationTable(json_decode($str_value), $dc->id, 0);
		return $str_value;
	}

	public function createLabel ($row, $label) {
		$this->loadLanguageFile('be_productSearch');
		$objProductOutput = new ls_shop_productOutput($row['id'], '', 'template_productBackendOverview_03');
		$label = '<div class="productViewBEList">'.$objProductOutput->parseOutput().'</div>';
		return $label;
	}

	public function toggleIcon($row, $href, $label, $title, $icon, $attributes) {
		if (strlen(\Input::get('tid'))) {
			$this->toggleVisibility(\Input::get('tid'), (\Input::get('state') == 1));
			$this->redirect($this->getReferer());
		}

		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_product::published', 'alexf')) {
			return '';
		}

		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

		if (!$row['published']) {
			$icon = 'invisible.gif';
		}

		return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
	}

	public function toggleVisibility($intId, $blnVisible) {
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_product::published', 'alexf')) {
			\System::log('Not enough permissions to publish/unpublish product ID "'.$intId.'"', 'tl_ls_shop_product toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

		if (is_array($GLOBALS['TL_DCA']['tl_ls_shop_product']['fields']['published']['save_callback'])) {
			foreach ($GLOBALS['TL_DCA']['tl_ls_shop_product']['fields']['published']['save_callback'] as $callback) {
				$this->import($callback[0]);
				$blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $this);
			}
		}

		// Update the database
		\Database::getInstance()->prepare("UPDATE tl_ls_shop_product SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
					   ->execute($intId);
	}

	/*
	 * This function checks whether a selected page is a main language page or a foreign language page. If it is a foreign
	 * language page, the main language equivalent is used instead. This is necessary because from Contao 3 on we use the
	 * standard pageTree which allows foreign language pages to be selected as well although Merconis requires main language
	 * pages to be selected.
	 *
	 * This function also makes sure that page ids in the serialized array are stored as strings and not as integers because
	 * the LIKE statement in the MySQL query that looks for products on specific pages relies on the presence of quotes
	 * to distinguish between the numeric array key and the page id.
	 */
	public function convertPageSelection($value) {
		if (!is_array($value)) {
			$value = deserialize($value, true);
		}

		$arrPageSelection = array();

		foreach ($value as $selctedPageID) {
			$tmpMainLanguageID = ls_shop_languageHelper::getMainlanguagePageIDForPageID($selctedPageID);
			if (!$tmpMainLanguageID) {
				continue;
			}
			$arrPageSelection[] = (string) $tmpMainLanguageID;
		}

		$value = serialize($arrPageSelection);

		return $value;
	}
}	
