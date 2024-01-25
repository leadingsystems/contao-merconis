<?php

namespace Merconis\Core;

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_ls_shop_filter_field_values'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
		'ptable' => 'tl_ls_shop_filter_fields',
		'onsubmit_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'ondelete_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'oncut_callback' => array(
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
			'mode' => DataContainer::MODE_PARENT,
			'fields' => array('sorting'),
			'panelLayout' => 'search,limit',
			'headerFields' => array('title'),
			'disableGrouping' => true,
			'child_record_callback'   => array('Merconis\Core\ls_shop_filter_field_values', 'listChildRecords')
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
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_field_values']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_field_values']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_field_values']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_field_values']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_field_values']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		
		)	
	),
	
	'palettes' => array(
		'default' => '{filterValue_legend},filterValue,alias;{output_legend},classForFilterFormField,importantFieldValue'
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
        'sorting' => array(
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
		'filterValue' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_field_values']['filterValue'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'maxlength'=>255),
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'alias' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_field_values']['alias'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'clr topLinedGroup'),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_filter_field_values', 'generateAlias')
			),
			'search' => true,
            'sql'                     => "varchar(128) BINARY NOT NULL default ''"
		),
		
		'classForFilterFormField' => array (
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_field_values']['classForFilterFormField'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50', 'maxlength'=>255),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'importantFieldValue' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_field_values']['importantFieldValue'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		)
	)
);




class ls_shop_filter_field_values extends Backend {
	public function __construct() {
		parent::__construct();
	}
	
	public function listChildRecords($arrRow) {
		return sprintf('<strong>%s</strong> <span style="font-style: italic;">(Alias: %s)</span>', $arrRow['filterValue'], $arrRow['alias']);
	}

	public function generateAlias($varValue, DataContainer $dc) {
		$autoAlias = false;

		$currentFilterValue = $dc->activeRecord->filterValue;

		// Generate an alias if there is none
		if ($varValue == '') {
			$autoAlias = true;
			$varValue = StringUtil::generateAlias($currentFilterValue);
		}
		$objAlias = Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_filter_field_values WHERE id=? OR alias=?")
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
}