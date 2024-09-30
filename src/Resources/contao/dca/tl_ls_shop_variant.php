<?php

namespace Merconis\Core;

use Contao\DataContainer;
use Contao\DC_Table;
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_ls_shop_variant'] = array(
	'config' => array(
        'notCreatable' => true,
		'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
		'ptable' => 'tl_ls_shop_product',
		'doNotCopyRecords' => true,
		'oncopy_callback' => array (
			/*
			 * This callback will only be called if a variant is copied directly.
			 * If it is copied automatically because it's parent product is copied,
			 * the callback is not used.
			 *
			 * To make sure the callback can also be used for variants that are copied
			 * with their parent product, we have to prevent them from being copied
			 * with the contao automatism and copy them on our own. The oncopy callback
			 * of the product takes care of that.
			 */
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
		'oncut_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
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
                'lsshopvariantcode' => 'unique'
            )
        )
	),

	'list' => array(

		'sorting' => array(
			'mode' => DataContainer::MODE_PARENT,
			'fields' => array('sorting'),
			'panelLayout' => 'filter;sort,search,limit',
			'headerFields'            => array('title', 'lsShopProductProducer'),
			'disableGrouping'   => true,
			'child_record_callback'   => array('Merconis\Core\tl_ls_shop_variant_controller', 'listVariants')
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
			'edit' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"'
			),
			'toggle' => array (
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['toggle'],
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('Merconis\Core\tl_ls_shop_variant_controller', 'toggleIcon')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)

		)
	),
	'palettes' => array(
		'__selector__' => array('overrideAvailabilitySettingsOfParentProduct', 'useGroupPrices_1', 'useGroupPrices_2', 'useGroupPrices_3', 'useGroupPrices_4', 'useGroupPrices_5', 'useScalePrice', 'useScalePrice_1', 'useScalePrice_2', 'useScalePrice_3', 'useScalePrice_4', 'useScalePrice_5'),
		'default' => '
			{lsShopVariantCode_legend},
			lsShopVariantCode;
			
			{lsShopCollectiveOrder_legend},
			lsShopRuntimeFrom,
			lsShopRuntimeUntil,
			lsShopDeliveryDate,
			lsShopMinimumCustomerOrders,
			lsShopMinimumOrders,
			lsShopMaximumOrders;

			{lsShopStatus_legend};
			
			{configurator_legend};
			
			{lsShopUnits_legend},
			lsShopVariantQuantityUnit,
			lsShopVariantMengenvergleichUnit;
			
			{lsShopTitleAndDescriptions_legend},
			title,
			alias;
			
			{lsShopImages_legend};
			
			{lsShopVariantAttributesAndValues_legend};
			
			{lsShopPrice_legend},
			lsShopVariantPrice,
			useOldPrice,
			lsShopVariantPriceOld,
			lsShopVariantMengenvergleichDivisor;

			{lsShopPrice_1_legend};

			{lsShopPrice_2_legend};

			{lsShopPrice_3_legend};

			{lsShopPrice_4_legend};

			{lsShopPrice_5_legend};
			
			{lsShopStockDeliveryTimeAndAvailability_legend};
			
			{associatedProducts_legend};
		'
	),

	'subpalettes' => array(
        'overrideAvailabilitySettingsOfParentProduct' => '
			availableFrom,
			preorderingAllowed,
			deliveryInfoSetToUseInPreorderPhase
	    ',

		'useGroupPrices_1' => '
			priceForGroups_1,
			lsShopVariantPrice_1,
			lsShopVariantPriceType_1,
			useScalePrice_1,
			useOldPrice_1,
			lsShopVariantPriceOld_1,
			lsShopVariantPriceTypeOld_1,
		',

		'useGroupPrices_2' => '
			priceForGroups_2,
			lsShopVariantPrice_2,
			lsShopVariantPriceType_2,
			useScalePrice_2,
			useOldPrice_2,
			lsShopVariantPriceOld_2,
			lsShopVariantPriceTypeOld_2,
		',

		'useGroupPrices_3' => '
			priceForGroups_3,
			lsShopVariantPrice_3,
			lsShopVariantPriceType_3,
			useScalePrice_3,
			useOldPrice_3,
			lsShopVariantPriceOld_3,
			lsShopVariantPriceTypeOld_3,
		',

		'useGroupPrices_4' => '
			priceForGroups_4,
			lsShopVariantPrice_4,
			lsShopVariantPriceType_4,
			useScalePrice_4,
			useOldPrice_4,
			lsShopVariantPriceOld_4,
			lsShopVariantPriceTypeOld_4,
		',

		'useGroupPrices_5' => '
			priceForGroups_5,
			lsShopVariantPrice_5,
			lsShopVariantPriceType_5,
			useScalePrice_5,
			useOldPrice_5,
			lsShopVariantPriceOld_5,
			lsShopVariantPriceTypeOld_5,
		',

		'useScalePrice' => 'scalePriceType,scalePriceQuantityDetectionMethod,scalePriceQuantityDetectionAlwaysSeparateConfigurations,scalePriceKeyword,scalePrice',
		'useScalePrice_1' => 'scalePriceType_1,scalePriceQuantityDetectionMethod_1,scalePriceQuantityDetectionAlwaysSeparateConfigurations_1,scalePriceKeyword_1,scalePrice_1',
		'useScalePrice_2' => 'scalePriceType_2,scalePriceQuantityDetectionMethod_2,scalePriceQuantityDetectionAlwaysSeparateConfigurations_2,scalePriceKeyword_2,scalePrice_2',
		'useScalePrice_3' => 'scalePriceType_3,scalePriceQuantityDetectionMethod_3,scalePriceQuantityDetectionAlwaysSeparateConfigurations_3,scalePriceKeyword_3,scalePrice_3',
		'useScalePrice_4' => 'scalePriceType_4,scalePriceQuantityDetectionMethod_4,scalePriceQuantityDetectionAlwaysSeparateConfigurations_4,scalePriceKeyword_4,scalePrice_4',
		'useScalePrice_5' => 'scalePriceType_5,scalePriceQuantityDetectionMethod_5,scalePriceQuantityDetectionAlwaysSeparateConfigurations_5,scalePriceKeyword_5,scalePrice_5'
	),

	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),

        'pid' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'lsShopVariantStock' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'sorting' => array(
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['sorting'],
            'exclude' => true,
            'inputType'		=>	'text',
            'eval'			=>	array('rgxp' => 'number', 'tl_class' => 'w50', 'mandatory' => true),
            'sorting' => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
		'lsShopVariantCode' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantCode'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('tl_class' => 'w50', 'unique' => true, 'mandatory' => true, 'decodeEntities' => true, 'maxlength'=>255),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_generalHelper', 'checkForUniqueProductCode')
			),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

        'lsShopRuntimeFrom' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopRuntimeFrom'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard', 'mandatory' => true),
			'sql'                     => "varchar(10) NOT NULL default ''"
        ),

        'lsShopRuntimeUntil' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopRuntimeUntil'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard', 'mandatory' => true),
            'save_callback' => array (
                array('Merconis\Core\tl_ls_shop_variant_controller', 'changeUntilDate')
            ),
            'sql'                     => "varchar(10) NOT NULL default ''"
        ),

        'lsShopDeliveryDate' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopDeliveryDate'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('tl_class' => 'w50 wizard'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'lsShopMinimumOrders' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopMinimumOrders'],
            'exclude'                 => true,
            'inputType'			      => 'text',
            'eval'					  => array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,2) NOT NULL default '0.00'"
        ),

        'lsShopMaximumOrders' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopMaximumOrders'],
            'exclude'                 => true,
            'inputType'			      => 'text',
            'eval'					  => array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,2) NOT NULL default '0.00'"
        ),


        'lsShopMinimumCustomerOrders' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopMinimumCustomerOrders'],
            'exclude'                 => true,
            'inputType'			      => 'text',
            'eval'					  => array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,2) NOT NULL default '0.00'"
        ),

		'published' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['published'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'doNotShow' => true),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'configurator' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['configurator'],
			'exclude' => true,
			'inputType' => 'select',
			'foreignKey' => 'tl_ls_shop_configurator.title',
			'eval' => array('includeBlankOption' => true, 'doNotShow' => true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

        'customizerLogicFile' => array(
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['customizerLogicFile'],
            'exclude' => true,
            'inputType'		=>	'fileTree',
            'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'php', 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "binary(16) NULL"
        ),

		'lsShopVariantQuantityUnit' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantQuantityUnit'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'merconis_picker_headline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['quantityUnitPickerHeadline'] ?? '', 'decodeEntities' => true, 'maxlength' => 255),
			'filter'		=> true,
			'wizard' => array (
				array('Merconis\Core\ls_shop_generalHelper', 'beValuePickerWizard')
			),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'lsShopVariantMengenvergleichUnit' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantMengenvergleichUnit'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_picker_headline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['quantityComparisonUnitPickerHeadline'] ?? '', 'decodeEntities' => true, 'maxlength' => 255, 'doNotShow' => true),
			'wizard' => array (
				array('Merconis\Core\ls_shop_generalHelper', 'beValuePickerWizard')
			),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'decodeEntities' => true, 'maxlength'=>255),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'alias' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['alias'],
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
				array('Merconis\Core\tl_ls_shop_variant_controller', 'generateAlias')
			),
            'sql'                     => "varchar(128) BINARY NOT NULL default ''"

		),

		'description' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['description'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'decodeEntities' => true, 'doNotShow' => true),
            'sql'                     => "text NULL"
		),

		'shortDescription' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['shortDescription'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'decodeEntities' => true, 'doNotShow' => true),
            'sql'                     => "text NULL"
		),

		'flex_contents' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contents'],
			'exclude' => true,
			'inputType' => 'text',
			'eval'                    => array(
				'tl_class'=>'clr merconis-component-autostart--merconisWidgetMultiText',
				'merconis_multilanguage' => true,
				'preserveTags' => true,
                'doNotShow' => true,
				'data-merconis-widget-options' => '
					{
						"arr_fields": [
							{
								"type": "text",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contents_label01'] ?? '').'"
							},
							{
								"type": "textarea",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contents_label02'] ?? '').'"
							}
						],
						"cssClass": "key-value-widget"
					}
				'
			),
            'sql'                     => "mediumtext NULL"
		),

		'flex_contentsLanguageIndependent' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contentsLanguageIndependent'],
			'exclude' => true,
			'inputType' => 'text',
			'eval'                    => array(
				'tl_class'=>'clr topLinedGroup merconis-component-autostart--merconisWidgetMultiText',
				'preserveTags' => true,
                'doNotShow' => true,
				'data-merconis-widget-options' => '
					{
						"arr_fields": [
							{
								"type": "text",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contentsLanguageIndependent_label01'] ?? '').'"
							},
							{
								"type": "textarea",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_variant']['flex_contentsLanguageIndependent_label02'] ?? '').'"
							}
						],
						"cssClass": "key-value-widget"
					}
				'
			),
            'sql'                     => "mediumtext NULL"
		),

		'lsShopProductVariantMainImage' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopProductVariantMainImage'],
			'exclude' => true,
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF', 'tl_class'=>'clr', 'doNotShow' => true),
			'sql'                     => "binary(16) NULL",
		),

		'lsShopProductVariantMoreImages' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopProductVariantMoreImages'],
			'exclude' => true,
			'inputType'		=>	'fileTree',
			'eval'			=> array('multiple' => true, 'fieldType'=>'checkbox', 'files'=>true, 'filesOnly' => true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF,flv,mp4,mp2,swf,mov,avi', 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "blob NULL"
		),

		'lsShopProductVariantAttributesValues' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopProductVariantAttributesValues'],
			'default'                 => '',
			'exclude' => true,
			'inputType'               => 'text',
			'eval'					  => array('disabled' => true, 'decodeEntities' => true, 'doNotShow' => true),
			'save_callback' => array (
				array('Merconis\Core\tl_ls_shop_variant_controller', 'insertAttributeValueAllocationsInAllocationTable')
			),
            'sql'                     => "text NULL"
		),

		'lsShopVariantPrice' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPrice'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceType' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		'useScalePrice' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12', 'doNotShow' => true),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceQuantityDetectionMethod'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12', 'doNotShow' => true),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255, 'doNotShow' => true),
			'search'		=> true,
			'filter'		=> true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'scalePrice' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
                'doNotShow' => true,
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

		'useOldPrice' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'clr'),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'lsShopVariantPriceOld' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceOld'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceTypeOld' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceTypeOld'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		/*
		 * Deviant price settings for group 1
		 */
		'useGroupPrices_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useGroupPrices_1'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'priceForGroups_1' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true, 'doNotShow' => true),
            'sql'                     => "blob NULL"
		),

		'lsShopVariantPrice_1' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPrice'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceType_1' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		'useScalePrice_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceQuantityDetectionMethod'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword_1' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255, 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'scalePrice_1' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
                'doNotShow' => true,
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

		'useOldPrice_1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'lsShopVariantPriceOld_1' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceOld'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceTypeOld_1' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceTypeOld'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		/*
		 * Deviant price settings for group 2
		 */
		'useGroupPrices_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useGroupPrices_2'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'priceForGroups_2' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true, 'doNotShow' => true),
            'sql'                     => "blob NULL"
		),

		'lsShopVariantPrice_2' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPrice'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceType_2' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		'useScalePrice_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceQuantityDetectionMethod'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword_2' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255, 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'scalePrice_2' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
                'doNotShow' => true,
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

		'useOldPrice_2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'lsShopVariantPriceOld_2' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceOld'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceTypeOld_2' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceTypeOld'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		/*
		 * Deviant price settings for group 3
		 */
		'useGroupPrices_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useGroupPrices_3'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'priceForGroups_3' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true, 'doNotShow' => true),
            'sql'                     => "blob NULL"
		),

		'lsShopVariantPrice_3' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPrice'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceType_3' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType']?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		'useScalePrice_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceQuantityDetectionMethod'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword_3' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255, 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'scalePrice_3' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
                'doNotShow' => true,
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

		'useOldPrice_3' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'lsShopVariantPriceOld_3' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceOld'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceTypeOld_3' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceTypeOld'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		/*
		 * Deviant price settings for group 4
		 */
		'useGroupPrices_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useGroupPrices_4'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'priceForGroups_4' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true, 'doNotShow' => true),
            'sql'                     => "blob NULL"
		),

		'lsShopVariantPrice_4' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPrice'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceType_4' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		'useScalePrice_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceQuantityDetectionMethod'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword_4' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255, 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'scalePrice_4' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
                'doNotShow' => true,
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

		'useOldPrice_4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'lsShopVariantPriceOld_4' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceOld'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceTypeOld_4' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceTypeOld'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		/*
		 * Deviant price settings for group 5
		 */
		'useGroupPrices_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useGroupPrices_5'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'doNotCopy'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
			'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'priceForGroups_5' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['priceForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true, 'doNotShow' => true),
            'sql'                     => "blob NULL"
		),

		'lsShopVariantPrice_5' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPrice'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceType_5' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		'useScalePrice_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useScalePrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr m12', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceType_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('scalePriceStandalone','scalePricePercentaged','scalePriceFixedAdjustment'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'scalePriceStandalone'"
		),

		'scalePriceQuantityDetectionMethod_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionMethod'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('separatedVariantsAndConfigurations','separatedVariants','separatedProducts','separatedScalePriceKeywords'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['scalePriceQuantityDetectionMethod'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'separatedVariantsAndConfigurations'"
		),

		'scalePriceQuantityDetectionAlwaysSeparateConfigurations_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceQuantityDetectionAlwaysSeparateConfigurations'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'scalePriceKeyword_5' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePriceKeyword'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=> array('tl_class' => 'w50', 'decodeEntities' => true, 'maxlength'=>255, 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'scalePrice_5' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['scalePrice'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
                'doNotShow' => true,
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

		'useOldPrice_5' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['useOldPrice'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'lsShopVariantPriceOld_5' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceOld'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantPriceTypeOld_5' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantPriceTypeOld'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantPriceType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		'lsShopVariantWeight' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantWeight'],
			'exclude' => true,
			'inputType'			      =>	'text',
			'eval'					  =>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true, 'doNotShow' => true),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),

		'lsShopVariantWeightType' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantWeightType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('standalone','adjustmentPercentaged','adjustmentFix'),
			'reference'               => $GLOBALS['TL_LANG']['tl_ls_shop_variant']['options']['lsShopVariantWeightType'] ?? null,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50', 'doNotShow' => true),
            'sql'                     => "varchar(255) NOT NULL default 'adjustmentPercentaged'"
		),

		'lsShopVariantMengenvergleichDivisor' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantMengenvergleichDivisor'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals','tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "decimal(12,6) NOT NULL default '0.000000'"
		),

		'lsShopVariantDeliveryInfoSet' => array(
			'label'			=> &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['lsShopVariantDeliveryInfoSet'],
			'exclude' => true,
			'inputType'		=> 'select',
			'foreignKey'	=> 'tl_ls_shop_delivery_info.title',
			'eval'			=> array('tl_class' => 'w50', 'includeBlankOption' => true, 'doNotShow' => true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
		),

        'overrideAvailabilitySettingsOfParentProduct' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['overrideAvailabilitySettingsOfParentProduct'],
            'exclude' => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr', 'doNotShow' => true),
            'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'availableFrom' => array(
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['availableFrom'],
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard', 'doNotShow' => true),
            'sql'                     => "varchar(10) NOT NULL default ''"
        ),

        'preorderingAllowed' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['preorderingAllowed'],
            'exclude' => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class'=>'w50 m12', 'doNotShow' => true),
            'filter'		=> true,
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'deliveryInfoSetToUseInPreorderPhase' => array(
            'label'			=> &$GLOBALS['TL_LANG']['tl_ls_shop_variant']['deliveryInfoSetToUseInPreorderPhase'],
            'exclude' => true,
            'inputType'		=> 'select',
            'foreignKey'	=> 'tl_ls_shop_delivery_info.title',
            'eval'			=> array('tl_class' => 'w50', 'includeBlankOption' => true, 'doNotShow' => true),
            'filter'		=> true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
        ),

		'associatedProducts' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_variant']['associatedProducts'],
			'exclude' => true,
			'inputType'		=>	'ls_shop_productSelectionWizard',
			'eval'			=> array('tl_class'=>'clr', 'doNotShow' => true),
            'sql'                     => "blob NULL"
		)
	)
);





class tl_ls_shop_variant_controller extends \Backend {

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
		 * the language and we probably don't deal with the exptected field, so
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
			FROM		`tl_ls_shop_variant`
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
		ls_shop_generalHelper::insertAttributeValueAllocationsInAllocationTable(json_decode($str_value), $dc->id, 1);
		return $str_value;
	}

	public function listVariants($arrRow) {
		$this->loadLanguageFile('be_productSearch');
		$objProductOutput = new ls_shop_productOutput($arrRow['pid'].'-'.$arrRow['id'], '', 'template_productBackendOverview_03');
		$label = '<div class="productViewBEList">'.$objProductOutput->parseOutput().'</div>';
		return $label;
	}

	public function toggleIcon($row, $href, $label, $title, $icon, $attributes) {
		if (strlen(\Input::get('tid'))) {
			$this->toggleVisibility(\Input::get('tid'), (\Input::get('state') == 1));
			$this->redirect($this->getReferer());
		}

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_variant::published', 'alexf')) {
			return '';
		}

		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

		if (!$row['published']) {
			$icon = 'invisible.svg';
		}

		return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
	}

	public function toggleVisibility($intId, $blnVisible) {
		// Check permissions to publish
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_variant::published', 'alexf')) {
			\System::log('Not enough permissions to publish/unpublish variant ID "'.$intId.'"', 'tl_ls_shop_variant toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_ls_shop_variant']['fields']['published']['save_callback'])) {
			foreach ($GLOBALS['TL_DCA']['tl_ls_shop_variant']['fields']['published']['save_callback'] as $callback) {
				$this->import($callback[0]);
				$blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $this);
			}
		}

		// Update the database
		\Database::getInstance()->prepare("UPDATE tl_ls_shop_variant SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
					   ->execute($intId);
	}

    public function changeUntilDate($value, \DataContainer $dc): bool|int
    {
        return strtotime('+23 hours + 59 minutes + 59 seconds', $value);
    }
}
