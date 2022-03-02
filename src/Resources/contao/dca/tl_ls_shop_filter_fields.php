<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_filter_fields'] = array(
	'config' => array(
		'dataContainer' => 'Table',
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
		'onrestore_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
        'sql' => array
        (
            'engine' => 'MyISAM',
            'charset' => 'COLLATE utf8_general_ci',
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
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['edit'],
				'href'                => 'table=tl_ls_shop_filter_field_values',
				'icon'                => 'edit.gif',
				'button_callback'	=>	array('Merconis\Core\ls_shop_filter_fields','getEditButton')
			),
			'editheader' => array (
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'toggle' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('Merconis\Core\ls_shop_filter_fields', 'toggleIcon')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		
		)	
	),
	
	'palettes' => array(
		'__selector__' => array('dataSource'),
		'default' => '{title_legend},title,alias;{dataSource_legend},dataSource;{output_legend},numItemsInReducedMode,classForFilterFormField,filterFormFieldType,priority;{published_legend},published;',
		'attribute' => '{title_legend},title,alias;{dataSource_legend},dataSource,sourceAttribute;{output_legend},numItemsInReducedMode,classForFilterFormField,filterFormFieldType,priority,templateToUse;{filterLogic_legend},filterMode,makeFilterModeUserAdjustable;{published_legend},published;',
		'producer' => '{title_legend},title,alias;{dataSource_legend},dataSource;{output_legend},numItemsInReducedMode,classForFilterFormField,filterFormFieldType,priority,templateToUse;{published_legend},published;',
		'price' => '{title_legend},title,alias;{dataSource_legend},dataSource;{output_legend},classForFilterFormField,priority,templateToUseForPriceField;{published_legend},published;'
	),
	
	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
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
            'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"
		),
		
		'dataSource' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['dataSource'],
			'default'                 => 'attribute',
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => array('attribute', 'producer', 'price'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['dataSource']['options'],
			'eval'					  => array('tl_class' => 'clr', 'helpwizard' => true, 'submitOnChange' => true),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'sourceAttribute' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['sourceAttribute'],
			'inputType' => 'select',
			'foreignKey' => 'tl_ls_shop_attributes.title',
			'eval' => array('tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
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
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		)
	)
);

$GLOBALS['TL_DCA']['tl_ls_shop_filter_fields']['fields']['tstamp'] = array (
    'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['tstamp'],
    'exclude'                 => true,
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);



class ls_shop_filter_fields extends \Backend {
	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function generateAlias($varValue, \DataContainer $dc) {
		$autoAlias = false;

		$currentTitle = isset($dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()}) && $dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()} ? $dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()} : $dc->activeRecord->title;

		// Generate an alias if there is none
		if ($varValue == '') {
			$autoAlias = true;
			$varValue = \StringUtil::generateAlias($currentTitle);
		}
		$objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_filter_fields WHERE id=? OR alias=?")
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
			$button = '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
		} else {
			$button = \Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
		}
		
		return $button;
	}
	
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes) {
		if (strlen(\Input::get('tid'))) {
			$this->toggleVisibility(\Input::get('tid'), (\Input::get('state') == 1));
			$this->redirect($this->getReferer());
		}

		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_filter_fields::published', 'alexf')) {
			return '';
		}

		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

		if (!$row['published']) {
			$icon = 'invisible.gif';
		}		

		return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
	}

	public function toggleVisibility($intId, $blnVisible) {
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_filter_fields::published', 'alexf')) {
			\System::log('Not enough permissions to publish/unpublish filter field ID "'.$intId.'"', 'tl_ls_shop_filter_fields toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}
		
		ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

		if (is_array($GLOBALS['TL_DCA']['tl_ls_shop_filter_fields']['fields']['published']['save_callback'])) {
			foreach ($GLOBALS['TL_DCA']['tl_ls_shop_filter_fields']['fields']['published']['save_callback'] as $callback) {
				$this->import($callback[0]);
				$blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $this);
			}
		}

		// Update the database
		\Database::getInstance()->prepare("UPDATE tl_ls_shop_filter_fields SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
					   ->execute($intId);
	}
	
	public function getFilterFieldTemplates() {
		return $this->getTemplateGroup('template_formFilterField_');
	}

	public function getPriceFilterFieldTemplates() {
		return $this->getTemplateGroup('template_formPriceFilterField_');
	}
}
?>