<?php

namespace Merconis\Core;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

$GLOBALS['TL_DCA']['tl_ls_shop_export'] = array(
	'config' => array(
		'dataContainer' => 'Table',
		'onsubmit_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'ondelete_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'oncopy_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'onrestore_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		)
	),

	'list' => array(
		'sorting' => array(
			'mode' => 1,
			'flag' => 1,
			'fields' => array('title'),
			'disableGrouping' => true,
			'panelLayout' => 'filter;search,limit'
		),

		'label' => array(
			'fields' => array('title'),
			'format' => '%s',
			'label_callback' => array('Merconis\Core\ls_shop_export_dc','createLabel')
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
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)

		)
	),

	'palettes' => array(
		'__selector__' => array('feedActive', 'fileExportActive', 'useSegmentedOutput', 'dataSource', 'activateFilterByStatus01', 'activateFilterByStatus02', 'activateFilterByStatus03', 'activateFilterByStatus04', 'activateFilterByStatus05', 'activateAutomaticChangeStatus01', 'activateAutomaticChangeStatus02', 'activateAutomaticChangeStatus03', 'activateAutomaticChangeStatus04', 'activateAutomaticChangeStatus05', 'activateFilterByPaymentMethod', 'activateFilterByShippingMethod'),
		'default' => '{title_legend},title;{template_legend},template;{customLogic_legend},customLogicFile,flex_parameters;{feed_legend},feedActive;{fileExport_legend},fileExportActive;{segmentedOutput_legend},useSegmentedOutput;{dataSource_legend},dataSource;',
		'dataTable' => '{title_legend},title;{template_legend},template;{customLogic_legend},customLogicFile,flex_parameters;{feed_legend},feedActive;{fileExport_legend},fileExportActive;{segmentedOutput_legend},useSegmentedOutput;{dataSource_legend},dataSource;{dataTable_legend},tableName',
		'directSelection' => '{title_legend},title;{template_legend},template;{customLogic_legend},customLogicFile,flex_parameters;{feed_legend},feedActive;{fileExport_legend},fileExportActive;{segmentedOutput_legend},useSegmentedOutput;{dataSource_legend},dataSource,createProductObjects;{group_legend},simulateGroup;{directSelection_legend},productDirectSelection',
		'searchSelection' => '{title_legend},title;{template_legend},template;{customLogic_legend},customLogicFile,flex_parameters;{feed_legend},feedActive;{fileExport_legend},fileExportActive;{segmentedOutput_legend},useSegmentedOutput;{dataSource_legend},dataSource,createProductObjects;{group_legend},simulateGroup;{searchSelection_legend},
									groupStartSearchSelectionNewProduct,
									activateSearchSelectionNewProduct,
									searchSelectionNewProduct,
									groupStopSearchSelectionNewProduct,
									
									groupStartSearchSelectionSpecialPrice,
									activateSearchSelectionSpecialPrice,
									searchSelectionSpecialPrice,
									groupStopSearchSelectionSpecialPrice,
									
									groupStartSearchSelectionCategory,
									activateSearchSelectionCategory,
									searchSelectionCategory,
									groupStopSearchSelectionCategory,
									
									groupStartSearchSelectionProducer,
									activateSearchSelectionProducer,
									searchSelectionProducer,
									groupStopSearchSelectionProducer,
									
									groupStartSearchSelectionProductName,
									activateSearchSelectionProductName,
									searchSelectionProductName,
									groupStopSearchSelectionProductName,
									
									groupStartSearchSelectionArticleNr,
									activateSearchSelectionArticleNr,
									searchSelectionArticleNr,
									groupStopSearchSelectionArticleNr,
									
									groupStartSearchSelectionTags,
									activateSearchSelectionTags,
									searchSelectionTags,
									groupStopSearchSelectionTags',
		'dataOrders' => '{title_legend},title;{template_legend},template;{customLogic_legend},customLogicFile,flex_parameters;{feed_legend},feedActive;{fileExport_legend},fileExportActive;{segmentedOutput_legend},useSegmentedOutput;{dataSource_legend},dataSource;{dataOrders_legend},changedWithinMinutes,activateFilterByStatus01,activateFilterByStatus02,activateFilterByStatus03,activateFilterByStatus04,activateFilterByStatus05,activateFilterByPaymentMethod,activateFilterByShippingMethod,activateAutomaticChangeStatus01,activateAutomaticChangeStatus02,activateAutomaticChangeStatus03,activateAutomaticChangeStatus04,activateAutomaticChangeStatus05,sendOrderMailsOnStatusChange'
	),

	'subpalettes' => array(
		'feedActive' => 'feedName,feedPassword,feedContentType,feedFileName',
		'fileExportActive' => 'folder,fileName,appendToFile',
		'useSegmentedOutput' => 'numberOfRecordsPerSegment,finishSegmentedOutputWithExtraSegment',
		'activateFilterByStatus01' => 'filterByStatus01',
		'activateFilterByStatus02' => 'filterByStatus02',
		'activateFilterByStatus03' => 'filterByStatus03',
		'activateFilterByStatus04' => 'filterByStatus04',
		'activateFilterByStatus05' => 'filterByStatus05',
		'activateAutomaticChangeStatus01' => 'automaticChangeStatus01',
		'activateAutomaticChangeStatus02' => 'automaticChangeStatus02',
		'activateAutomaticChangeStatus03' => 'automaticChangeStatus03',
		'activateAutomaticChangeStatus04' => 'automaticChangeStatus04',
		'activateAutomaticChangeStatus05' => 'automaticChangeStatus05',
		'activateFilterByPaymentMethod' => 'filterByPaymentMethod',
		'activateFilterByShippingMethod' => 'filterByShippingMethod'
	),

	'fields' => array(
		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'maxlength'=>255),
			'search' => true
		),

		'template' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['template'],
			'default'                 => 'template_export_default',
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => $this->getTemplateGroup('template_export_'),
			'filter' => true,
			'eval'					  => array('tl_class' => 'w50', 'includeBlankOption' => true)
		),

        'customLogicFile' => array(
            'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_export']['customLogicFile'],
            'exclude' => true,
            'inputType'		=>	'fileTree',
            'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'php', 'tl_class'=>'clr'),
            'sql'                     => "binary(16) NULL"
        ),

		'flex_parameters' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['flex_parameters'],
			'exclude' => true,
			'inputType' => 'text',
			'eval'                    => array(
				'tl_class'=>'clr topLinedGroup merconis-component-autostart--merconisWidgetMultiText',
				'decodeEntities' => true,
				'data-merconis-widget-options' => '
					{
						"arr_fields": [
							{
								"type": "text",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_export']['flex_parameters_label01'] ?? '').'"
							},
							{
								"type": "textarea",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_export']['flex_parameters_label02'] ?? '').'"
							}
						],
						"cssClass": "key-value-widget"
					}
				'
			)
		),

		'feedActive' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['feedActive'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'feedName' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['feedName'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_export_dc', 'generateFeedName')
			),
			'sorting' => true,
			'flag' => 11,
			'search' => true
		),

		'feedPassword' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['feedPassword'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>255, 'tl_class' => 'w50')
		),

		'feedContentType' => array(
			'label'					  => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['feedContentType'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'					  => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50')
		),

		'feedFileName' => array(
			'label'					  => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['feedFileName'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'					  => array('maxlength' => 255, 'tl_class' => 'w50')
		),

		'fileExportActive' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['fileExportActive'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'useSegmentedOutput' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['useSegmentedOutput'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'numberOfRecordsPerSegment' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['numberOfRecordsPerSegment'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('rgxp' => 'digit', 'maxlength' => 3, 'tl_class' => 'w50')
		),

		'finishSegmentedOutputWithExtraSegment' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['finishSegmentedOutputWithExtraSegment'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),

		'folder' => array(
			'label'					  => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['folder'],
			'exclude'                 => true,
			'inputType'				  => 'fileTree',
			'eval'					  => array('fieldType' => 'radio', 'files' => false, 'tl_class' => 'clr'),
			'save_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'idFromUuid')
			),
			'load_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'uuidFromId')
			)
		),

		'fileName' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['fileName'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>255, 'tl_class'=>'clr'),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_export_dc', 'generateFileName')
			),
			'sorting' => true,
			'flag' => 11,
			'search' => true
		),

		'appendToFile' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['appendToFile'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr cbx')
		),

		'dataSource' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['dataSource'],
			'default'                 => 'directSelection',
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => array('dataTable', 'directSelection', 'searchSelection', 'dataOrders'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['dataSource']['options'],
			'eval'					  => array('helpwizard' => true, 'submitOnChange' => true),
			'filter' => true
		),

		'changedWithinMinutes' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['changedWithinMinutes'],
			'default'                 => 9999999,
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => array(5, 10, 15, 30, 60, 120, 720, 1440, 2880, 10080, 20160, 40320, 525600, 9999999),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['changedWithinMinutes']['options'],
			'filter' => true
		),



		'activateFilterByStatus01' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateFilterByStatus01'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr topLinedGroup')
		),

		'filterByStatus01' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['filterByStatus01'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'clr', 'multiple' => true),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues01AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues']
		),

		'activateFilterByStatus02' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateFilterByStatus02'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'filterByStatus02' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['filterByStatus02'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'clr', 'multiple' => true),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues02AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues']
		),

		'activateFilterByStatus03' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateFilterByStatus03'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'filterByStatus03' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['filterByStatus03'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'clr', 'multiple' => true),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues03AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues']
		),

		'activateFilterByStatus04' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateFilterByStatus04'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'filterByStatus04' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['filterByStatus04'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'clr', 'multiple' => true),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues04AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues']
		),

		'activateFilterByStatus05' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateFilterByStatus05'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'filterByStatus05' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['filterByStatus05'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'clr', 'multiple' => true),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues05AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues']
		),



		'activateFilterByPaymentMethod' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateFilterByPaymentMethod'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr topLinedGroup')
		),

		'filterByPaymentMethod' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['filterByPaymentMethod'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'clr', 'multiple' => true),
			'foreignKey'			  => 'tl_ls_shop_payment_methods.title'
		),

		'activateFilterByShippingMethod' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateFilterByShippingMethod'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'filterByShippingMethod' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['filterByShippingMethod'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'clr', 'multiple' => true),
			'foreignKey'			  => 'tl_ls_shop_shipping_methods.title'
		),



		'activateAutomaticChangeStatus01' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateAutomaticChangeStatus01'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr topLinedGroup')
		),

		'automaticChangeStatus01' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['automaticChangeStatus01'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'clr'),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues01AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues']
		),

		'activateAutomaticChangeStatus02' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateAutomaticChangeStatus02'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'automaticChangeStatus02' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['automaticChangeStatus02'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'clr'),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues02AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues']
		),

		'activateAutomaticChangeStatus03' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateAutomaticChangeStatus03'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'automaticChangeStatus03' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['automaticChangeStatus03'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'clr'),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues03AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues']
		),

		'activateAutomaticChangeStatus04' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateAutomaticChangeStatus04'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'automaticChangeStatus04' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['automaticChangeStatus04'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'clr'),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues04AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues']
		),

		'activateAutomaticChangeStatus05' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activateAutomaticChangeStatus05'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'automaticChangeStatus05' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_export']['automaticChangeStatus05'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'clr'),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues05AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues']
		),



		'sendOrderMailsOnStatusChange'=> array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['sendOrderMailsOnStatusChange'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),



		'createProductObjects' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['createProductObjects'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange' => true, 'tl_class'=>'clr')
		),

		'tableName' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['tableName'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'maxlength'=>255),
			'search' => true
		),

		'simulateGroup' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['simulateGroup'],
			'exclude' => true,
			'inputType' => 'select',
			'foreignKey' => 'tl_member_group.name',
			'eval' => array('tl_class'=>'w50', 'includeBlankOption' => true)
		),

		'productDirectSelection' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_export']['productDirectSelection'],
			'exclude' => true,
			'inputType'		=>	'ls_shop_productSelectionWizard',
			'eval'			=> array('tl_class'=>'clr ls_beBlock')
		),


        'groupStartSearchSelectionNewProduct' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_export']['headlineSearchSelectionNewProduct'] ?? '').'</h3>')
        ),

		'groupStopSearchSelectionNewProduct' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),

		'activateSearchSelectionNewProduct' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50')
		),

		'searchSelectionNewProduct' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['searchSelectionNewProduct'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('new', 'notNew'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['searchSelectionNewProduct']['options'],
			'eval'					  => array('tl_class' => 'w50')
		),



        'groupStartSearchSelectionSpecialPrice' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_export']['headlineSearchSelectionSpecialPrice'] ?? '').'</h3>')
        ),

		'groupStopSearchSelectionSpecialPrice' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),

		'activateSearchSelectionSpecialPrice' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50')
		),

		'searchSelectionSpecialPrice' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['searchSelectionSpecialPrice'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('specialPrice', 'noSpecialPrice'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['searchSelectionSpecialPrice']['options'],
			'eval'					  => array('tl_class' => 'w50')
		),



        'groupStartSearchSelectionCategory' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_export']['headlineSearchSelectionCategory'] ?? '').'</h3><p>'.($GLOBALS['TL_LANG']['tl_ls_shop_export']['subHeadlineSearchSelectionCategory'] ?? '').'</p>')
        ),

		'groupStopSearchSelectionCategory' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),

		'activateSearchSelectionCategory' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox'
		),

		'searchSelectionCategory' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['searchSelectionCategory'],
			'exclude' => true,
			'inputType'               => 'pageTree',
			'eval'                    => array('fieldType'=>'checkbox', 'multiple' => true)
		),



        'groupStartSearchSelectionProducer' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_export']['headlineSearchSelectionProducer'] ?? '').'</h3>')
        ),

		'groupStopSearchSelectionProducer' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),

		'activateSearchSelectionProducer' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50')
		),

		'searchSelectionProducer' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['searchSelectionProducer'],
			'exclude' => true,
			'inputType' => 'text',
			'eval'					  => array('tl_class' => 'w50', 'maxlength' => 255)
		),



        'groupStartSearchSelectionProductName' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_export']['headlineSearchSelectionProductName'] ?? '').'</h3>')
        ),

		'groupStopSearchSelectionProductName' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),

		'activateSearchSelectionProductName' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50')
		),

		'searchSelectionProductName' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['searchSelectionProductName'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255, 'tl_class' => 'w50')
		),



        'groupStartSearchSelectionArticleNr' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_export']['headlineSearchSelectionArticleNr'] ?? '').'</h3>')
        ),

		'groupStopSearchSelectionArticleNr' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),

		'activateSearchSelectionArticleNr' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50')
		),

		'searchSelectionArticleNr' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['searchSelectionArticleNr'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255, 'tl_class' => 'w50')
		),



        'groupStartSearchSelectionTags' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_export']['headlineSearchSelectionTags'] ?? '').'</h3>')
        ),

		'groupStopSearchSelectionTags' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),

		'activateSearchSelectionTags' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50')
		),

		'searchSelectionTags' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_export']['searchSelectionTags'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255, 'tl_class' => 'w50')
		)
	)
);

class ls_shop_export_dc extends \Backend {
	public function __construct() {
		parent::__construct();
	}

	public function generateFeedName($varValue, \DataContainer $dc) {
		$autoAlias = false;

		$currentTitle = $dc->activeRecord->title;

		// Generate an alias if there is none
		if ($varValue == '') {
			$autoAlias = true;
			$varValue = \StringUtil::generateAlias($currentTitle);
		}
		$objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_export WHERE id=? OR feedName=?")
			->execute($dc->id, $varValue);

		// Check whether the alias exists
		if ($objAlias->numRows > 1) {
			if (!$autoAlias) {
				throw new \Exception(sprintf($GLOBALS['TL_LANG']['tl_ls_shop_export']['ERR']['feedNameExists'], $varValue));
			}
			$varValue .= '-' . $dc->id;
		}

		return $varValue;
	}

	public function generateFileName($varValue, \DataContainer $dc) {
		$autoAlias = false;

		$currentTitle = $dc->activeRecord->title;

		// Generate an alias if there is none
		if ($varValue == '') {
			$autoAlias = true;
			$varValue = \StringUtil::generateAlias($currentTitle);
		}
		$objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_export WHERE id=? OR fileName=?")
			->execute($dc->id, $varValue);

		// Check whether the alias exists
		if ($objAlias->numRows > 1) {
			if (!$autoAlias) {
				throw new \Exception(sprintf($GLOBALS['TL_LANG']['tl_ls_shop_export']['ERR']['fileNameExists'], $varValue));
			}
			$varValue .= '-' . $dc->id;
		}

		return $varValue;
	}

	public function createLabel($arr_row, $str_label) {
		$obj_template = new \BackendTemplate('template_beExport');

		$obj_template->arr_row = $arr_row;

        $arr_ajaxPage = ls_shop_languageHelper::getLanguagePage('ls_shop_ajaxPages', false, 'array');
        if (is_array($arr_ajaxPage)) {
            $obj_targetPageCollection = \PageModel::findById($arr_ajaxPage['id']);
            $obj_template->str_ajaxUrl = $obj_targetPageCollection->getFrontendUrl('/resource/exportFeed').'?feedName='.$arr_row['feedName'].($arr_row['feedPassword'] ? '&pwd='.$arr_row['feedPassword'] : '');
        } else {
            $obj_template->str_ajaxUrl = 'AJAX PAGE NOT FOUND';
        }

		$arr_existingExportFiles = array();

		if ($arr_row['fileExportActive'] && $arr_row['fileName'] && $arr_row['folder']) {
			$str_pathToFileExportFolder = ls_getFilePathFromVariableSources($arr_row['folder']);

			if (file_exists(TL_ROOT.'/'.$str_pathToFileExportFolder)) {
				foreach (scandir(TL_ROOT.'/'.$str_pathToFileExportFolder) as $str_fileName) {
					if (
							$str_fileName == '.'
						||	$str_fileName == '..'
						||	!preg_match(
								'/'.
								preg_replace(
									'/TMPWILDCARD/',
									'.*?',
									preg_quote(
										preg_replace(
											'/\{\{.*?\}\}/',
											'TMPWILDCARD',
											$arr_row['fileName']
										)
									)
								).
								'/',
								$str_fileName
							)
					) {
						continue;
					}

					$arr_existingExportFiles[] = array(
						'fileName' => $str_fileName,
						'url' => \Environment::get('base').$str_pathToFileExportFolder.'/'.$str_fileName,
						'dateTime' => date($GLOBALS['TL_CONFIG']['datimFormat'], filemtime(TL_ROOT.'/'.$str_pathToFileExportFolder.'/'.$str_fileName)),
						'fileSize' => \Controller::getReadableSize(filesize(TL_ROOT.'/'.$str_pathToFileExportFolder.'/'.$str_fileName))
					);
				}
			}
		}

		$obj_template->arr_existingExportFiles = $arr_existingExportFiles;

		$str_label = $obj_template->parse();

		return $str_label;
	}
}
