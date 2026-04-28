<?php

namespace Merconis\Core;

use Contao\DataContainer;
use Contao\DC_Table;
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_ls_shop_product'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
		'ctable' => array('tl_ls_shop_variant'),
		'oncopy_callback' => array (
			array('Merconis\Core\ls_shop_generalHelper', 'attributeValueAllocationCopy'),
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
        'ondelete_callback' => array (
            array('Merconis\\Core\\ls_shop_generalHelper', 'attributeValueAllocationRemoveOrphanedRecords'),
            array('Merconis\\Core\\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp'),
            array('Merconis\\Core\\tl_ls_shop_product_controller', 'deleteFromPageMap')
        ),
        'onsubmit_callback' => array(
            array('Merconis\\Core\\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp'),
            array('Merconis\\Core\\tl_ls_shop_product_controller', 'syncPageMapOnSubmit')
        ),
		'onrestore_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'alias' => 'unique',
				'lsshopproductcode' => 'unique',
				'mpn' => 'index',
				'gtin' => 'index',
				'variationgroupcode' => 'index',
				// FULLTEXT indexes for faster descriptive searches (MyISAM/InnoDB 5.6+)
				'title' => 'fulltext',
				'description' => 'fulltext',
				'shortDescription' => 'fulltext',
				'keywords' => 'fulltext',
				'lsShopProductProducer' => 'index'
			)
		)
	),

	'list' => array(
		'sorting' => array(
			'mode' => DataContainer::MODE_SORTABLE,
			'fields' => array('id'),
			'flag' => DataContainer::SORT_ASC,
			'panelLayout' => 'filter;sort,search,limit',
            'filter' => [['productTypeCollectiveOrder=?', 1]]
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
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['toggle'],
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('Merconis\Core\tl_ls_shop_product_controller', 'toggleIcon')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
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
			variationGroupCode,
			alias,
			mpn,
			gtin;

			{lsShopPublishAndState_legend},
			published,
			lsShopProductIsNew,
			lsShopProductIsOnSale'.(\Input::get('act') == 'editAll' ? '' : ',sorting').',
			productTypeCollectiveOrder;
			
			{configurator_legend},
			configurator,
			customizerLogicFile;
			
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

			{lsShopStockDeliveryTimeAndAvailability_legend},
			lsShopProductDeliveryInfoSet,
			availableFrom,
			preorderingAllowed,
			deliveryInfoSetToUseInPreorderPhase;
			
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

        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'lsShopProductStock' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'lsShopProductNumSales' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'decodeEntities' => true, 'maxlength'=>255),
			'sorting' => true,
			'flag' => 11,
			'search'		=> true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'description' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['description'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'decodeEntities' => true),
			'search'		=> true,
            'sql'                     => "text NULL"
		),

		'shortDescription' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['shortDescription'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'decodeEntities' => true),
			'search'		=> true,
            'sql'                     => "text NULL"
		),

		'keywords' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['keywords'],
			'exclude' => true,
			'inputType' => 'textarea',
			'eval' => array('style'=>'height:60px;', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'decodeEntities' => true),
			'search'		=> true,
            'sql'                     => "text NULL"
		),

        'pageTitle' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['pageTitle'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => false, 'tl_class'=>'w50', 'merconis_multilanguage' => true, 'decodeEntities' => true, 'maxlength'=>255),
            'sorting' => true,
            'flag' => 11,
            'search'		=> true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'pageDescription' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['pageDescription'],
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => array('style'=>'height:60px;', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'decodeEntities' => true),
            'search'		=> true,
            'sql'                     => "varchar(255) NOT NULL default ''"
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
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents_label01'] ?? '').'"
							},
							{
								"type": "textarea",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents_label02'] ?? '').'"
							}
						],
						"cssClass": "key-value-widget"
					}
				'
			),
            'sql'                     => "mediumtext NULL"
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
			),
            'sql'                     => "mediumtext NULL"
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
			'search'		=> true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'mpn' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['mpn'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength' => 128),
			'search'		         => true,
			'sql'                     => "varchar(128) NOT NULL default ''"
		),

		'gtin' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['gtin'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class' => 'w50', 'rgxp' => 'digit', 'maxlength' => 14),
			'search'		         => true,
			'sql'                     => "varchar(14) NOT NULL default ''"
		),

		'variationGroupCode' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['variationGroupCode'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255),
			'sorting' => true,
			'flag' => 11,
			'search'		=> true,
            'sql'                     => "varchar(255) NULL default ''"
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
			),
            'sql'                     => "varchar(128) BINARY NOT NULL default ''"

		),

		'published' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['published'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'lsShopProductIsNew' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductIsNew'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'lsShopProductIsOnSale' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductIsOnSale'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'sorting' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['sorting'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'number', 'tl_class' => 'w50', 'mandatory' => true),
			'sorting' => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

        'productTypeCollectiveOrder' => array(
            'label'                  => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['productTypeCollectiveOrder'],
            'exclude'                => true,
            'inputType'              => 'checkbox',
            'eval'                   => array('disabled' => true, 'tl_class' => 'w50'),
            'filter'		         => true,
            'sql'                    => "char(1) NOT NULL default ''"
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

        'customizerLogicFile' => array(
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['customizerLogicFile'],
            'exclude' => true,
            'inputType'		=>	'fileTree',
            'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'php', 'tl_class'=>'clr'),
            'sql'                     => "binary(16) NULL"
        ),

		'lsShopProductQuantityUnit' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductQuantityUnit'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'merconis_picker_headline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['quantityUnitPickerHeadline'] ?? '', 'decodeEntities' => true, 'maxlength' => 255),
			'filter'		=> true,
			'wizard' => array (
				array('Merconis\Core\ls_shop_generalHelper', 'beValuePickerWizard')
			),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'lsShopProductMengenvergleichUnit' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMengenvergleichUnit'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_picker_headline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['quantityComparisonUnitPickerHeadline'] ?? '', 'decodeEntities' => true, 'maxlength' => 255),
			'wizard' => array (
				array('Merconis\Core\ls_shop_generalHelper', 'beValuePickerWizard')
			),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'pages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['pages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'),
			'save_callback' => array (
				array('Merconis\Core\tl_ls_shop_product_controller', 'convertPageSelection')
			),
            'sql'                     => "blob NULL"
		),

        'useGroupRestrictions' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupRestrictions'],
            'exclude' => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
            'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'allowedGroups' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['allowedGroups'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'foreignKey'              => 'tl_member_group.name',
            'eval'                    => array('multiple'=>true),
            'sql'                     => "blob NULL"
        ),

        'lsShopProductProducer' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductProducer'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'merconis_picker_headline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['producerPickerHeadline'] ?? '', 'decodeEntities' => true, 'maxlength' => 255),
			'sorting' => true,
			'flag' => 11,
			'search'		=> true,
			'wizard' => array (
				array('Merconis\Core\ls_shop_generalHelper', 'beValuePickerWizard')
			),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'lsShopProductMainImage' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMainImage'],
			'exclude' => true,
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF,webp,WEBP,mp4', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),

		'lsShopProductMoreImages' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMoreImages'],
			'exclude' => true,
			'inputType'		=>	'fileTree',
			'eval'			=> array('multiple' => true, 'fieldType'=>'checkbox', 'files'=>true, 'filesOnly' => true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF,webp,WEBP,flv,mp4,mp2,swf,mov,avi', 'tl_class'=>'clr'),
            'sql'                     => "blob NULL"
		),

		'lsShopProductAttributesValues' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductAttributesValues'],
			'default'                 => '',
			'exclude' => true,
			'inputType'               => 'text',
			'eval'					  => array('decodeEntities' => true, 'disabled' => true),
			'save_callback' => array (
				array('Merconis\Core\tl_ls_shop_product_controller', 'insertAttributeValueAllocationsInAllocationTable')
			),
            'sql'                     => "text NULL"
		),

		'lsShopProductPrice' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true),
			'sorting' => true,
			'flag' => 11,
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useScalePrice' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr'),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255),
			'search'		=> true,
			'filter'		=> true,
            'sql'                     => "varchar(255) NOT NULL default ''"
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
			),
            'sql'                     => "text NULL"
		),

		'lsShopProductPriceOld' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useOldPrice' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'lsShopProductSteuersatz' => array(
			'label'			=> &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductSteuersatz'],
			'exclude' => true,
			'inputType'		=> 'select',
			'options_callback'	=> array('Merconis\Core\ls_shop_generalHelper','getNonDynamicSteuersatzOptions'),
			'eval'			=> array('tl_class' => 'w50', 'includeBlankOption'=>true, 'mandatory' => true),
			'filter'		=> true,
            'sql'                     => "int(10) unsigned NULL"
		),

		'lsShopProductWeight' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductWeight'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopProductQuantityDecimals' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductQuantityDecimals'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('rgxp'=>'digit', 'maxlength'=>1, 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'lsShopProductMengenvergleichDivisor' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMengenvergleichDivisor'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals','tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,6) NOT NULL default '0.000000'"
		),

		/*
		 * Deviant price settings for group 1
		 */
		'useGroupPrices_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_1'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'priceForGroups_1' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
            'sql'                     => "blob NULL"
		),

		'lsShopProductPrice_1' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useScalePrice_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr'),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword_1' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255),
            'sql'                     => "varchar(255) NOT NULL default ''"
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
			),
            'sql'                     => "text NULL"
		),

		'lsShopProductPriceOld_1' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useOldPrice_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		/*
		 * Deviant price settings for group 2
		 */
		'useGroupPrices_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_2'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'priceForGroups_2' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
            'sql'                     => "blob NULL"
		),

		'lsShopProductPrice_2' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useScalePrice_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr'),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword_2' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255),
            'sql'                     => "varchar(255) NOT NULL default ''"
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
			),
            'sql'                     => "text NULL"
		),

		'lsShopProductPriceOld_2' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useOldPrice_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		/*
		 * Deviant price settings for group 3
		 */
		'useGroupPrices_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_3'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'priceForGroups_3' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
            'sql'                     => "blob NULL"
		),

		'lsShopProductPrice_3' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useScalePrice_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr'),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword_3' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255),
            'sql'                     => "varchar(255) NOT NULL default ''"
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
			),
            'sql'                     => "text NULL"
		),

		'lsShopProductPriceOld_3' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useOldPrice_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		/*
		 * Deviant price settings for group 4
		 */
		'useGroupPrices_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_4'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'priceForGroups_4' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
            'sql'                     => "blob NULL"
		),

		'lsShopProductPrice_4' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useScalePrice_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr'),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword_4' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255),
            'sql'                     => "varchar(255) NOT NULL default ''"
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
			),
            'sql'                     => "text NULL"
		),

		'lsShopProductPriceOld_4' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useOldPrice_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		/*
		 * Deviant price settings for group 5
		 */
		'useGroupPrices_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_5'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'priceForGroups_5' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
            'sql'                     => "blob NULL"
		),

		'lsShopProductPrice_5' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'clr', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useScalePrice_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr'),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => ($GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] ?? ''),
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword_5' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255),
            'sql'                     => "varchar(255) NOT NULL default ''"
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
			),
            'sql'                     => "text NULL"
		),

		'lsShopProductPriceOld_5' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'useOldPrice_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'lsShopProductDeliveryInfoSet' => array(
			'label'			=> &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductDeliveryInfoSet'],
			'exclude' => true,
			'inputType'		=> 'select',
			'foreignKey'	=> 'tl_ls_shop_delivery_info.title',
			'eval'			=> array('tl_class' => 'w50', 'includeBlankOption' => true),
			'filter'		=> true,
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
		),

        'availableFrom' => array(
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['availableFrom'],
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard clr'),
            'sql'                     => "varchar(10) NOT NULL default ''"
        ),

        'preorderingAllowed' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['preorderingAllowed'],
            'exclude' => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class'=>'w50 m12'),
            'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'deliveryInfoSetToUseInPreorderPhase' => array(
            'label'			=> &$GLOBALS['TL_LANG']['tl_ls_shop_product']['deliveryInfoSetToUseInPreorderPhase'],
            'exclude' => true,
            'inputType'		=> 'select',
            'foreignKey'	=> 'tl_ls_shop_delivery_info.title',
            'eval'			=> array('tl_class' => 'w50', 'includeBlankOption' => true),
            'filter'		=> true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
        ),

		'lsShopProductRecommendedProducts' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductRecommendedProducts'],
			'exclude' => true,
			'inputType'		=>	'ls_shop_productSelectionWizard',
			'eval'			=> array('tl_class'=>'clr'),
            'sql'                     => "blob NULL"
		),

		'associatedProducts' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_product']['associatedProducts'],
			'exclude' => true,
			'inputType'		=>	'ls_shop_productSelectionWizard',
			'eval'			=> array('tl_class'=>'clr'),
            'sql'                     => "blob NULL"
		),

		'lsShopProductDetailsTemplate' => array(
			'label'					  => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductDetailsTemplate'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => $this->getTemplateGroup('template_productDetails_'),
			'eval'					  => array('tl_class' => 'w50', 'includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_ls_shop_product']['blankOptionLabel']),
            'sql'                     => "varchar(64) NOT NULL default ''"
		)
	)
);
