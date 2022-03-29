<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_configurator'] = array(
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
			'mode' => 2,
			'flag' => 1,
			'fields' => array('title'),
			'disableGrouping' => true,
			'panelLayout' => 'filter;sort,search,limit'
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
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
				'button_callback'	=>	array('Merconis\Core\ls_shop_configurator','getDeleteButton')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		
		)	
	),
	
	'palettes' => array(
		'default' => '{title_legend},title,alias;{form_legend},form,startWithDataEntryMode,stayInDataEntryMode,skipStandardFormValidation;{customLogic_legend},customLogicFile;{template_legend},template;'
	),
	
	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'maxlength'=>255),
			'sorting' => true,
			'flag' => 11,
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'alias' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['alias'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_configurator', 'generateAlias')
			),
			'sorting' => true,
			'flag' => 11,
			'search' => true,
            'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"
		),
		
		'form' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['form'],
			'exclude' => true,
			'inputType' => 'select',
			'foreignKey' => 'tl_form.title',
			'filter' => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'startWithDataEntryMode' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['startWithDataEntryMode'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'filter' => true,
            'sql'                     => "char(1) NOT NULL default '1'"
		),

		'stayInDataEntryMode' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['stayInDataEntryMode'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'filter' => true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'skipStandardFormValidation' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['skipStandardFormValidation'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'filter' => true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'customLogicFile' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['customLogicFile'],
			'exclude' => true,
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'php', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),
		
		'template' => array(
			'label'					  => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['template'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => $this->getTemplateGroup('template_configurator_'),
			'eval'					  => array('tl_class' => 'w50'),
			'filter' => true,
            'sql'                     => "varchar(64) NOT NULL default ''"
		)
	)
);

$GLOBALS['TL_DCA']['tl_ls_shop_configurator']['fields']['tstamp'] = array (
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);




class ls_shop_configurator extends \Backend {
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
		$objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_configurator WHERE id=? OR alias=?")
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
		$configuratorsCurrentlyInUse = ls_shop_generalHelper::getConfiguratorsCurrentlyInUse();
		
		if (!in_array($row['id'], $configuratorsCurrentlyInUse)) {
			$button = '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
		} else {
			$button = \Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
		}
		
		return $button;
	}
}
?>