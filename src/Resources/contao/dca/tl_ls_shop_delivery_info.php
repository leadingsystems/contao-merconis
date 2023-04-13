<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_delivery_info'] = array(
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
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
				'button_callback'	=>	array('Merconis\Core\ls_shop_delivery_info','getDeleteButton')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		
		)	
	),
	'palettes' => array(
		'default' => '{title_legend},title,alias;{stockSettings_legend},useStock,allowOrdersWithInsufficientStock,alertWhenLowerThanMinimumStock,minimumStock;{deliveryTime_legend},deliveryTimeDaysWithSufficientStock,deliveryTimeMessageWithSufficientStock,deliveryTimeDaysWithInsufficientStock,deliveryTimeMessageWithInsufficientStock'
	),
	
	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'maxlength'=>255),
			'sorting' => true,
			'flag' => 11,
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'alias' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['alias'],
			'exclude' => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_delivery_info', 'generateAlias')
			),
			'sorting' => true,
			'flag' => 11,
			'search' => true,
            'sql'                     => "varchar(128) BINARY NOT NULL default ''"
		),
		
		'useStock'	=> array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['useStock'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class' => 'w50'),
			'filter' => true,
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'allowOrdersWithInsufficientStock'	=> array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['allowOrdersWithInsufficientStock'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class' => 'w50'),
			'filter' => true,
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'alertWhenLowerThanMinimumStock'	=> array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['alertWhenLowerThanMinimumStock'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class' => 'w50'),
			'filter' => true,
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'minimumStock' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['minimumStock'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50', 'mandatory' => true, 'rgxp' => 'digit'),
			'filter' => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'deliveryTimeDaysWithSufficientStock' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['deliveryTimeDaysWithSufficientStock'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'clr', 'mandatory' => true, 'rgxp' => 'digit'),
			'filter' => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'deliveryTimeMessageWithSufficientStock' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['deliveryTimeMessageWithSufficientStock'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('allowHtml'=>true, 'class'=>'monospace', 'rte'=>'ace|html',  'tl_class'=>'clr', 'merconis_multilanguage' => true),
            'sql'                     => "text NULL"
		),
		
		'deliveryTimeDaysWithInsufficientStock' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['deliveryTimeDaysWithInsufficientStock'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'clr topLinedGroup', 'mandatory' => true, 'rgxp' => 'digit'),
			'filter' => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'deliveryTimeMessageWithInsufficientStock' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_delivery_info']['deliveryTimeMessageWithInsufficientStock'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('allowHtml'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'tl_class'=>'clr', 'merconis_multilanguage' => true),
            'sql'                     => "text NULL"
		)
	)
);




class ls_shop_delivery_info extends \Backend {
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

		$objAlias = \Database::getInstance()->prepare("SELECT `id` FROM tl_ls_shop_delivery_info WHERE id=? OR alias=?")
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
		/*
		 * Auslesen der Produkt-Artikel
		 */
		$objProducts = \Database::getInstance()->prepare("SELECT `id` FROM tl_ls_shop_product WHERE `lsShopProductDeliveryInfoSet` = ?")
								  ->execute($row['id']);
								  
		if (!$objProducts->numRows && $GLOBALS['TL_CONFIG']['ls_shop_delivery_infoSet'] != $row['id']) {
			/*
			 * Wenn das deliveryInfoSet bei keinem Produkt verwendet wird,
			 * darf gelöscht werden.
			 */
			$button = '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
		} else {
			/*
			 * Wird das deliveryInfoSet bei Produkten verwendet,
			 * so darf es nicht gelöscht werden
			 */
			$button = \Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
		}
		return $button;
	}
}