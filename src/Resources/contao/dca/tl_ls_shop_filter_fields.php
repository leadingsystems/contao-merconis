<?php

namespace Merconis\Core;

use Contao\Backend;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_ls_shop_filter_fields'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
		'ctable' => array('tl_ls_shop_filter_field_values'),
		'onsubmit_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'ondelete_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'oncopy_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'onrestore_version_callback' => array(
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
			'mode' => DataContainer::MODE_SORTABLE,
			'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'fields' => array('title'),
			'disableGrouping' => true,
			'panelLayout' => 'sort,search,limit'
		),

		'label' => array(
			'fields' => array('title', 'alias'),
			'format' => '<strong>%s</strong> <span style="font-style: italic;">(Alias: %s)</span>'
		),

		'global_operations' => array(
			'all'
		),

		'operations' => array(
            'edit',
			'children' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['children'],
				'href'                => 'table=tl_ls_shop_filter_field_values',
				'icon'                => 'children.svg',
				'button_callback'	=>	array('Merconis\Core\ls_shop_filter_fields','getEditButton')
			),
			'copy',
			'delete',
			'toggle',
			'show'
		)
	),

	'palettes' => array(
		'__selector__' => array('dataSource'),
		'default' => '{title_legend},title,alias;{dataSource_legend},dataSource;{output_legend},numItemsInReducedMode,classForFilterFormField,filterFormFieldType,priority;{published_legend},published;',
		'attribute' => '{title_legend},title,alias;{dataSource_legend},dataSource,sourceAttribute;{output_legend},numItemsInReducedMode,classForFilterFormField,filterFormFieldType,priority,templateToUse,disableFilterIfOnlyOneValue;{filterLogic_legend},filterMode,makeFilterModeUserAdjustable;{published_legend},published;',
        'attributesMinMax' => '{title_legend},title,alias;{dataSource_legend},dataSource,sourceAttribute;{output_legend},classForFilterFormField,priority,templateToUseForRangeField;{filterLogic_legend};{published_legend},published;',
		'producer' => '{title_legend},title,alias;{dataSource_legend},dataSource;{output_legend},numItemsInReducedMode,classForFilterFormField,filterFormFieldType,priority,templateToUse;{published_legend},published;',
		'price' => '{title_legend},title,alias;{dataSource_legend},dataSource;{output_legend},classForFilterFormField,priority,templateToUseForPriceField;{published_legend},published;',
        'flexContentLI' => '{title_legend},title,alias;{dataSource_legend},dataSource,flexContentLIKey;{output_legend},numItemsInReducedMode,classForFilterFormField,filterFormFieldType,priority,templateToUseForFlexContentLIField;{filterLogic_legend},filterMode,makeFilterModeUserAdjustable;{published_legend},published;',
        'flexContentLD' => '{title_legend},title,alias;{dataSource_legend},dataSource,flexContentLDKey;{output_legend},numItemsInReducedMode,classForFilterFormField,filterFormFieldType,priority,templateToUseForFlexContentLDField;{filterLogic_legend},filterMode,makeFilterModeUserAdjustable;{published_legend},published;',
        'flexContentLIMinMax' => '{title_legend},title,alias;{dataSource_legend},dataSource,flexContentLIKey;{output_legend},classForFilterFormField,priority,templateToUseForFlexContentLIMinMaxField;{filterLogic_legend};{published_legend},published;'
	),

	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'maxlength'=>255),
			'sorting' => true,
			'flag' => 11,
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'alias' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['alias'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'clr topLinedGroup'),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_filter_fields', 'generateAlias')
			),
			'sorting' => true,
			'flag' => 11,
			'search' => true,
            'sql'                     => "varchar(128) BINARY NOT NULL default ''"
		),

		'dataSource' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['dataSource'],
			'default'                 => 'attribute',
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => array('attribute', 'attributesMinMax', 'producer', 'price', 'flexContentLI', 'flexContentLD', 'flexContentLIMinMax'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['dataSource']['options'],
			'eval'					  => array('tl_class' => 'clr', 'helpwizard' => true, 'submitOnChange' => true),
            'sql'                     => "varchar(255) NOT NULL default ''",
            'save_callback' => array (array('Merconis\Core\ls_shop_filter_fields', 'dataSource_unsetSession'))
		),

		'sourceAttribute' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['sourceAttribute'],
			'inputType' => 'select',
			'foreignKey' => 'tl_ls_shop_attributes.title',
			'eval' => array('tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

        'flexContentLIKey' => array (
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['flexContentLIKey'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'maxlength'=>255, 'mandatory' => true),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'flexContentLDKey' => array (
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['flexContentLDKey'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'maxlength'=>255, 'mandatory' => true),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

		'numItemsInReducedMode' => array (
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['numItemsInReducedMode'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50', 'mandatory' => true),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'classForFilterFormField' => array (
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['classForFilterFormField'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50', 'maxlength'=>255),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'filterFormFieldType' => array (
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterFormFieldType'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => array('checkbox', 'radio'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterFormFieldType']['options'],
			'eval'                    => array('tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'priority' => array (
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['priority'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50', 'mandatory' => true),
			'sorting' => true,
			'flag' => 12,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'templateToUse'				  => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUse'],
			'exclude'				  => true,
			'inputType'               => 'select',
			'options_callback'		  => array('Merconis\Core\ls_shop_filter_fields', 'getFilterFieldTemplates'),
			'eval'                    => array('tl_class'=>'w50'),
            'sql'                     => "varchar(64) NOT NULL default 'template_formFilterField_standard'"
		),

		'templateToUseForPriceField'  => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUseForPriceField'],
			'exclude'				  => true,
			'inputType'               => 'select',
			'options_callback'		  => array('Merconis\Core\ls_shop_filter_fields', 'getPriceFilterFieldTemplates'),
			'eval'                    => array('tl_class'=>'w50'),
            'sql'                     => "varchar(64) NOT NULL default 'template_formPriceFilterField_standard'"
		),

        'templateToUseForFlexContentLIField'  => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUseForFlexContentLIField'],
            'exclude'				  => true,
            'inputType'               => 'select',
            'options_callback'		  => array('Merconis\Core\ls_shop_filter_fields', 'getFlexContentLIFilterFieldTemplates'),
            'eval'                    => array('tl_class'=>'w50'),
            'sql'                     => "varchar(64) NOT NULL default 'template_formFlexContentLIFilterField_standard'"
        ),

        'templateToUseForFlexContentLDField'  => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUseForFlexContentLDField'],
            'exclude'				  => true,
            'inputType'               => 'select',
            'options_callback'		  => array('Merconis\Core\ls_shop_filter_fields', 'getFlexContentLDFilterFieldTemplates'),
            'eval'                    => array('tl_class'=>'w50'),
            'sql'                     => "varchar(64) NOT NULL default 'template_formFlexContentLDFilterField_standard'"
        ),

        'templateToUseForFlexContentLIMinMaxField'  => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUseForFlexContentLIMinMaxField'],
            'exclude'				  => true,
            'inputType'               => 'select',
            'options_callback'		  => array('Merconis\Core\ls_shop_filter_fields', 'getFlexContentLIMinMaxFilterFieldTemplates'),
            'eval'                    => array('tl_class'=>'w50'),
            'sql'                     => "varchar(64) NOT NULL default 'template_formFlexContentLIMinMaxFilterField_standard'"
        ),

        'templateToUseForRangeField'  => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUseForRangeField'],
            'exclude'				  => true,
            'inputType'               => 'select',
            'options_callback'		  => array('Merconis\Core\ls_shop_filter_fields', 'getRangeFilterFieldTemplates'),
            'eval'                    => array('tl_class'=>'w50'),
            'sql'                     => "varchar(64) NOT NULL default 'template_formAttributesMinMaxFilterField_standard'"
        ),

        'disableFilterIfOnlyOneValue' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['disableFilterIfOnlyOneValue'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class'=>'w50 m12', 'doNotCopy' => true),
            'sql'                     => "char(1) NOT NULL default '1'"
        ),

		'filterMode' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterMode'],
			'exclude'				  => true,
			'inputType'               => 'select',
			'options'                 => array('or', 'and'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterMode']['options'],
			'eval'                    => array('tl_class'=>'w50'),
            'sql'                     => "varchar(64) NOT NULL default ''"
		),

		'makeFilterModeUserAdjustable' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['makeFilterModeUserAdjustable'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'published' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['published'],
			'exclude' => true,
            'toggle' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		)
	)
);

class ls_shop_filter_fields extends Backend {
	public function __construct() {
		parent::__construct();
		$this->import('Contao\BackendUser', 'User');
	}

	public function generateAlias($varValue, DataContainer $dc) {
		$autoAlias = false;

		$currentTitle = isset($dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()}) && $dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()} ? $dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()} : $dc->activeRecord->title;

		// Generate an alias if there is none
		if ($varValue == '') {
			$autoAlias = true;
			$varValue = StringUtil::generateAlias($currentTitle);
		}
		$objAlias = Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_filter_fields WHERE id=? OR alias=?")
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

	public function getEditButton($row, $href, $label, $title, $icon, $attributes) {
		if ($row['dataSource'] == 'producer') {
			$button = '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
		} else {
			$button = Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
		}

		return $button;
	}

	public function getFilterFieldTemplates() {
		return $this->getTemplateGroup('template_formFilterField_');
	}

	public function getPriceFilterFieldTemplates() {
		return $this->getTemplateGroup('template_formPriceFilterField_');
	}

    public function getFlexContentLIFilterFieldTemplates() {
        return $this->getTemplateGroup('template_formFlexContentLIFilterField_');
    }

    public function getFlexContentLDFilterFieldTemplates() {
        return $this->getTemplateGroup('template_formFlexContentLDFilterField_');
    }

    public function getFlexContentLIMinMaxFilterFieldTemplates() {
        return $this->getTemplateGroup('template_formFlexContentLIMinMaxFilterField_');
    }

    public function dataSource_unsetSession($value) {
        if (in_array($value,array('flexContentLIMinMax', 'flexContentLI'))) {
            unset($_SESSION['lsShop']['filter']['flexContentLIKeys']);
        }
        return $value;
    }

    public function getRangeFilterFieldTemplates() {
        return $this->getTemplateGroup('template_formAttributesMinMaxFilterField_');
    }
}