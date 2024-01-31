<?php

namespace Merconis\Core;

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_ls_shop_configurator'] = array(
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
				'icon'                => 'edit.svg'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"',
				'button_callback'	=>	array('Merconis\Core\ls_shop_configurator','getDeleteButton')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
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
        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
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
            'sql'                     => "varchar(128) BINARY NOT NULL default ''"
        ),

        'form' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['form'],
            'exclude' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_form.title',
            'filter' => true,
      'sql'                     => "int(10) unsigned NOT NULL default '0'",
      'eval' => ['includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['form_blankOptionLabel']]
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

class ls_shop_configurator extends Backend {
    public function __construct() {
        parent::__construct();
    }

    public function generateAlias($varValue, DataContainer $dc) {
        $autoAlias = false;

        $currentTitle = isset($dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()}) && $dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()} ? $dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()} : $dc->activeRecord->title;

        // Generate an alias if there is none
        if ($varValue == '') {
            $autoAlias = true;
            $varValue = StringUtil::generateAlias($currentTitle);
        }
        $objAlias = Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_configurator WHERE id=? OR alias=?")
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
            $button = '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
        } else {
            $button = Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
        }

        return $button;
    }
}