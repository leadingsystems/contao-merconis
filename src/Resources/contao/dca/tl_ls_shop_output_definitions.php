<?php

namespace Merconis\Core;

use Contao\ArrayUtil;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_ls_shop_output_definitions'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
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
		),
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary'
            )
        )
	),

	'list' => array(
		'sorting' => array(
			'mode' => DataContainer::MODE_SORTED,
			'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'fields' => array('title'),
			'disableGrouping' => true,
			'panelLayout' => 'search,limit'

		),

		'label' => array(
			'fields' => array('title'),
			'format' => '%s'
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
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"',
				'button_callback'	=>	array('Merconis\Core\ls_shop_output_definitions','getDeleteButton')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)

		)
	),

	'palettes' => array(
		'default' => '
			{title_legend},
			title;
			
			{outputDefinitions_legend},
			lsShopProductTemplate,			
			lsShopProductOverviewSorting,
			lsShopProductOverviewSortingKeyOrAlias,
			lsShopProductOverviewUserSorting,
			lsShopProductOverviewUserSortingFields,
			lsShopProductOverviewPagination;
			
			{outputDefinitionsCrossSeller_legend},
			lsShopProductTemplate_crossSeller,
			lsShopProductOverviewSorting_crossSeller,
			lsShopProductOverviewSortingKeyOrAlias_crossSeller,
			lsShopProductOverviewUserSorting_crossSeller,
			lsShopProductOverviewUserSortingFields_crossSeller,
			lsShopProductOverviewPagination_crossSeller
		'
	),

	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),

        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'lsShopProductTemplate_crossSeller' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'lsShopProductOverviewSorting_crossSeller' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'lsShopProductOverviewSortingKeyOrAlias_crossSeller' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'lsShopProductOverviewUserSorting_crossSeller' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'lsShopProductOverviewUserSortingFields_crossSeller' => array (
            'sql'                     => "text NULL"
        ),

        'lsShopProductOverviewPagination_crossSeller' => array (
            'sql'                     => "int(3) unsigned NOT NULL default '0'"
        ),

		'title' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['title'],
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'maxlength'=>255),
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'lsShopProductTemplate' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductTemplate'],
			'inputType' => 'select',
			'options_callback'		=> array('Merconis\Core\ls_shop_output_definitions','ls_getTemplateOptions'),
			'reference'				=> &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductTemplate']['reference'],
			'eval'					=> array('helpwizard' => true),
            'sql'                   => "varchar(255) NOT NULL default ''"
		),

		'lsShopProductOverviewSorting' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting'],
			'inputType' => 'select',
			'options_callback'        => array('Merconis\Core\ls_shop_output_definitions','ls_getOverviewSortingOptions'),
			'reference'				=> &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference'],
			'eval'					=> array('helpwizard' => false),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'lsShopProductOverviewSortingKeyOrAlias' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSortingKeyOrAlias'],
			'inputType' => 'text',
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'lsShopProductOverviewUserSorting' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewUserSorting'],
			'inputType' => 'select',
			'options_callback'        => array('Merconis\Core\ls_shop_output_definitions','ls_getOverviewUserSortingOptions'),
			'reference'				=> &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewUserSorting']['reference'],
			'eval'					=> array('helpwizard' => false),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'lsShopProductOverviewUserSortingFields' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewUserSortingFields'],
			'inputType' => 'text',
			'eval' => array(
				'tl_class' => 'merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
				'data-merconis-widget-options' => '
					{
						"arr_fields": [
							{
								"type": "select",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewUserSortingFields']['labels']['field1'] ?? '').'",
								"options": [
									{
										"value": "title_sortDir_ASC",
										"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['title_sortDir_ASC'] ?? '').'"
									},
									{
										"value": "title_sortDir_DESC",
										"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['title_sortDir_DESC'] ?? '').'"
									},									
									{
										"value" : "lsShopProductPrice_sortDir_ASC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['lsShopProductPrice_sortDir_ASC'] ?? '').'"
									},
									{
										"value" : "lsShopProductPrice_sortDir_DESC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['lsShopProductPrice_sortDir_DESC'] ?? '').'"
									},
									{
										"value" : "lsShopProductCode_sortDir_ASC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['lsShopProductCode_sortDir_ASC'] ?? '').'"
									},
									{
										"value" : "lsShopProductCode_sortDir_DESC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['lsShopProductCode_sortDir_DESC'] ?? '').'"
									},
									{
										"value" : "sorting_sortDir_ASC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['sorting_sortDir_ASC'] ?? '').'"
									},
									{
										"value" : "sorting_sortDir_DESC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['sorting_sortDir_DESC'] ?? '').'"
									},
									{
										"value" : "lsShopProductProducer_sortDir_ASC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['lsShopProductProducer_sortDir_ASC'] ?? '').'"
									},
									{
										"value" : "lsShopProductProducer_sortDir_DESC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['lsShopProductProducer_sortDir_DESC'] ?? '').'"
									},
									{
										"value" : "lsShopProductWeight_sortDir_ASC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['lsShopProductWeight_sortDir_ASC'] ?? '').'"
									},
									{
										"value" : "lsShopProductWeight_sortDir_DESC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['lsShopProductWeight_sortDir_DESC'] ?? '').'"
									},
									{
										"value" : "priority_sortDir_ASC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['priority_sortDir_ASC'] ?? '').'"
									},
									{
										"value" : "priority_sortDir_DESC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['priority_sortDir_DESC'] ?? '').'"
									},
									{
										"value" : "flex_contentsLanguageIndependentKEYORALIAS_sortDir_ASC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['flex_contentsLanguageIndependentKEYORALIAS_sortDir_ASC'] ?? '').'"
									},
									{
										"value" : "flex_contentsLanguageIndependentKEYORALIAS_sortDir_DESC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['flex_contentsLanguageIndependentKEYORALIAS_sortDir_DESC'] ?? '').'"
									},
									{
										"value" : "flex_contentsKEYORALIAS_sortDir_ASC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['flex_contentsKEYORALIAS_sortDir_ASC'] ?? '').'"
									},
									{
										"value" : "flex_contentsKEYORALIAS_sortDir_DESC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['flex_contentsKEYORALIAS_sortDir_DESC'] ?? '').'"
									},
									{
										"value" : "lsShopProductAttributesValuesKEYORALIAS_sortDir_ASC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['lsShopProductAttributesValuesKEYORALIAS_sortDir_ASC'] ?? '').'"
									},
									{
										"value" : "lsShopProductAttributesValuesKEYORALIAS_sortDir_DESC",
										"label" : "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']['lsShopProductAttributesValuesKEYORALIAS_sortDir_DESC'] ?? '').'"
									}
								]
							},
							{
								"type": "text",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewUserSortingFields']['labels']['field2'] ?? '') .'"
							},
							{
								"type": "textarea",
								"label": "'.($GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewUserSortingFields']['labels']['field3'] ?? '') .'"
							}
						],
						"cssClass": "output-definition-user-sorting-fields-widget"
					}
				'
			),
            'sql'                     => "text NULL"
		),

		'lsShopProductOverviewPagination' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewPagination'],
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'rgxp' => 'digit', 'maxlength' => 3),
            'sql'                     => "int(3) unsigned NOT NULL default '0'"
		)
	)
);

$GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductTemplate_crossSeller'] = $GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductTemplate'];
$GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductOverviewSorting_crossSeller'] = $GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductOverviewSorting'];
$GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductOverviewSortingKeyOrAlias_crossSeller'] = $GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductOverviewSortingKeyOrAlias'];
$GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductOverviewUserSorting_crossSeller'] = $GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductOverviewUserSorting'];
$GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductOverviewUserSortingFields_crossSeller'] = $GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductOverviewUserSortingFields'];
$GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductOverviewPagination_crossSeller'] = $GLOBALS['TL_DCA']['tl_ls_shop_output_definitions']['fields']['lsShopProductOverviewPagination'];

class ls_shop_output_definitions extends \Backend {
	public function __construct() {
		parent::__construct();
	}

	public function ls_getTemplateOptions() {
		$arrOptions = $this->getTemplateGroup('template_productOverview_');
		ArrayUtil::arrayInsert($arrOptions, 0, array('template_productOverview_useDetailsTemplate' => 'template_productOverview_useDetailsTemplate'));
		return $arrOptions;
	}

	public function ls_getOverviewSortingOptions() {
		$arrOptions = array(
			'title_sortDir_ASC',
			'title_sortDir_DESC',

			'lsShopProductPrice_sortDir_ASC',
			'lsShopProductPrice_sortDir_DESC',

			'lsShopProductCode_sortDir_ASC',
			'lsShopProductCode_sortDir_DESC',

			'sorting_sortDir_ASC',
			'sorting_sortDir_DESC',

			'lsShopProductProducer_sortDir_ASC',
			'lsShopProductProducer_sortDir_DESC',

			'lsShopProductWeight_sortDir_ASC',
			'lsShopProductWeight_sortDir_DESC',

			'priority_sortDir_ASC',
			'priority_sortDir_DESC',

			'flex_contentsLanguageIndependentKEYORALIAS_sortDir_ASC',
			'flex_contentsLanguageIndependentKEYORALIAS_sortDir_DESC',

			'flex_contentsKEYORALIAS_sortDir_ASC',
			'flex_contentsKEYORALIAS_sortDir_DESC',

			'lsShopProductAttributesValuesKEYORALIAS_sortDir_ASC',
			'lsShopProductAttributesValuesKEYORALIAS_sortDir_DESC'
		);
		return $arrOptions;
	}

	public function ls_getOverviewUserSortingOptions() {
		$arrOptions = array('yes', 'no');
		return $arrOptions;
	}

	/*
	 * Diese Funktion prüft, ob der Datensatz irgendwo im Shop verwendet wird und gibt nur dann
	 * den funktionsfähigen Löschen-Button zurück, wenn der Datensatz nicht verwendet wird und
	 * daher bedenkenlos gelöscht werden kann.
	 */
	public function getDeleteButton($row, $href, $label, $title, $icon, $attributes) {
		if (!in_array($row['id'], ls_shop_generalHelper::getOutputDefinitionsCurrentlyInUse())) {
			$button = '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
		} else {
			$button = \Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
		}

		return $button;
	}
}
