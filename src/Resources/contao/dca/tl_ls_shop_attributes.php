<?php

namespace Merconis\Core;

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_ls_shop_attributes'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
		'ctable' => array('tl_ls_shop_attribute_values'),
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
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_attributes']['edit'],
				'href'                => 'table=tl_ls_shop_attribute_values',
				'icon'                => 'edit.gif'
			),
			'editheader' => array (
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_attributes']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_attributes']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_attributes']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"',
				'button_callback'	=>	array('Merconis\Core\ls_shop_attributes','getDeleteButton')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_attributes']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		
		)	
	),
	
	'palettes' => array(
		'default' => '{title_legend},title,alias;'
	),
	
	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
		'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_attributes']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'maxlength'=>255),
			'sorting' => true,
			'flag' => 11,
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'alias' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_attributes']['alias'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'clr topLinedGroup'),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_attributes', 'generateAlias')
			),
			'sorting' => true,
			'flag' => 11,
			'search' => true,
            'sql'                     => "varchar(128) BINARY NOT NULL default ''"
		)
	)
);



class ls_shop_attributes extends \Backend {
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
		$objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_attributes WHERE id=? OR alias=?")
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
	 * Diese Funktion prüft, ob der Datensatz irgendwo im Shop verwendet wird und gibt nur dann
	 * den funktionsfähigen Löschen-Button zurück, wenn der Datensatz nicht verwendet wird und
	 * daher bedenkenlos gelöscht werden kann.
	 */
	public function getDeleteButton($row, $href, $label, $title, $icon, $attributes) {
		$attributesAndValuesCurrentlyInUse = ls_shop_generalHelper::getAttributesAndValuesCurrentlyInUse();
		
		if (!in_array($row['id'], $attributesAndValuesCurrentlyInUse['arrAttributeIDs'])) {
			$button = '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
		} else {
			$button = \Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
		}
		
		return $button;
	}
}