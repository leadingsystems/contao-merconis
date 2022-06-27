<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_steuersaetze'] = array(
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
            'keys' => array
            (
                'id' => 'primary'
            )
        )
	),
	
	'list' => array(
		'sorting' => array(
			'mode' => 1,
			'flag' => 1,
			'fields' => array('title'),
			'disableGrouping' => true,
			'panelLayout' => 'search,limit'
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
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
				'button_callback'	=>	array('Merconis\Core\ls_shop_steuersaetze','getDeleteButton')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		
		)	
	),
	
	'palettes' => array(
		'default' => '{title_legend},title,alias;{steuerPeriod1_legend},steuerProzentPeriod1,startPeriod1,stopPeriod1;{steuerPeriod2_legend},steuerProzentPeriod2,startPeriod2,stopPeriod2'
	),
	
	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),

        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
		
		'title' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['title'],
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'maxlength'=>255),
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'alias' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['alias'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_steuersaetze', 'generateAlias')
			),
			'search' => true,
            'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"

		),

		'steuerProzentPeriod1' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['steuerProzentPeriod1'],
			'inputType' => 'text',
			'eval'			=> array(
				'decodeEntities' => true,
				'rgxp' => 'numberWithDecimalsAndHashsignLeftTextRight',
				'tl_class' => 'merconis-component-autostart--merconisWidgetMultiText',
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
			'save_callback' => array(
				array('Merconis\Core\ls_shop_steuersaetze', 'checkIfWildcardsUsedAndAllowed')
			),
            'sql'                     => "text NULL"
		),
		
		'startPeriod1' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['startPeriod1'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
            'sql'                     => "varchar(10) NOT NULL default ''"
		),
		
		'stopPeriod1' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['stopPeriod1'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
            'sql'                     => "varchar(10) NOT NULL default ''"
		),
		
		'steuerProzentPeriod2' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['steuerProzentPeriod2'],
			'inputType' => 'text',
			'eval'			=> array(
				'decodeEntities' => true,
				'rgxp' => 'numberWithDecimalsAndHashsignLeftTextRight',
				'tl_class' => 'merconis-component-autostart--merconisWidgetMultiText',
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
			'save_callback' => array(
				array('Merconis\Core\ls_shop_steuersaetze', 'checkIfWildcardsUsedAndAllowed')
			),
            'sql'                     => "text NULL"
		),
		
		'startPeriod2' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['startPeriod2'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
            'sql'                     => "varchar(10) NOT NULL default ''"
		),
		
		'stopPeriod2' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['stopPeriod2'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>$this->getDatePickerString(), 'tl_class'=>'w50 wizard'),
            'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);





class ls_shop_steuersaetze extends \Backend {
	public function generateAlias($varValue, \DataContainer $dc) {
		$autoAlias = false;

		// Generate an alias if there is none
		if ($varValue == '') {
			$autoAlias = true;
			$varValue = \StringUtil::generateAlias($dc->activeRecord->title);
		}

		$objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_steuersaetze WHERE id=? OR alias=?")
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

	public function getDeleteButton($row, $href, $label, $title, $icon, $attributes) {
		/*
		 * Auslesen der Produkte
		 */
		$objProducts = \Database::getInstance()->prepare("SELECT `id` FROM tl_ls_shop_product WHERE `lsShopProductSteuersatz` = ?")
								  ->execute($row['id']);
								  
		if (!$objProducts->numRows) {
			/*
			 * The tax rate can be deleted if it is not in use with any product
			 */
			$button = '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
		} else {
			/*
			 * The tax rate must not be deleted if it is in use with at least one product
			 */
			$button = \Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
		}
		return $button;
	}
	
	/*
	 * In order to understand what this function is all about, take a look at ls_shop_generalHelper::parseSteuersatz(),
	 * $GLOBALS['MERCONIS_HOOKS']['customTaxRateCalculation'] and the comments for this hook!
	 *
	 * This function checks if this tax class is used with one or more products and if it
	 * is it checks if a wildcard is used as a tax value because that is not allowed
	 * for tax classes used with products.
	 */
	public function checkIfWildcardsUsedAndAllowed($varValue, \DataContainer $dc) {
		$objProducts = \Database::getInstance()->prepare("
			SELECT	`id`
			FROM	`tl_ls_shop_product`
			WHERE	`lsShopProductSteuersatz` = ?
		")
		->execute($dc->id);
								  
		if (!$objProducts->numRows) {
			// return the value without further checks if the tax class is not used with at least one product
			return $varValue;
		}

		if (strpos($varValue, '#') !== false) {
			throw new \Exception($GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['wildcardNotAllowed']);
		} else {
			return $varValue;
		}
	}
}
