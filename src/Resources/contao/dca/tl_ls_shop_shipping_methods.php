<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_shipping_methods'] = array(
	'config' => array(
		'dataContainer' => 'Table',
		'onload_callback' => array(
			array('Merconis\Core\ls_shop_shipping_methods','modifyDCA')
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
			'fields' => array('sorting', 'title'),
			'disableGrouping' => true,
			'panelLayout' => 'filter;search,limit',
		),
		
		'label' => array(
			'fields' => array('title', 'alias'),
			'format' => '<strong>%s</strong> <span style="font-style: italic;">(Alias: %s)</span>'
		),
		
		'global_operations' => array(
			'all' => array(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),
		
		'operations' => array(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
				'button_callback'	=>	array('Merconis\Core\ls_shop_shipping_methods','getDeleteButton')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		
		)	
	),
	
	'palettes' => array(
		'__selector__' => array('dynamicSteuersatzType','feeType'),
		'default' => '{title_legend},title,alias,description;{type_legend},type,formAdditionalData;{steuersatz_legend},dynamicSteuersatzType;{excludedGroups_legend},excludedGroups;{weightLimit_legend},weightLimitMin,weightLimitMax;{priceLimit_legend},priceLimitMin,priceLimitMax,priceLimitAddCouponToValueOfGoods;{countryLimit_legend},countries,countriesAsBlacklist;{fee_legend},feeType;{afterCheckout_legend},infoAfterCheckout,additionalInfo;{published_legend},published;{misc_legend},cssID,cssClass,sorting'
	),
	
	'subpalettes' => array(
		'dynamicSteuersatzType_none' => 'steuersatz',
		'feeType_fixed' => 'feeValue',
		'feeType_percentaged' => 'feeAddCouponToValueOfGoods,feeValue',
		'feeType_weight' => 'feeWeightValues',
		'feeType_price' => 'feeAddCouponToValueOfGoods,feePriceValues',
		'feeType_weightAndPrice' => 'feeAddCouponToValueOfGoods,feeWeightValues,feePriceValues',
		'feeType_formula' => 'feeFormula,feeFormulaResultConvertToDisplayPrice'
	),
	
	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'maxlength'=>255),
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'alias' => array (
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['alias'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'clr topLinedGroup'),
			'save_callback' => array (
				array('Merconis\Core\ls_shop_shipping_methods', 'generateAlias')
			),
			'sorting' => true,
			'flag' => 11,
			'search' => true,
            'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"
		),
		
		'description' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['description'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('allowHtml' => true, 'preserveTags' => true, 'tl_class' => 'clr', 'merconis_multilanguage' => true, 'merconis_multilanguage_topLinedGroup' => true),
            'sql'                     => "text NULL"
		),
		
		'type' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['type'],
			'inputType' => 'select',
			'options_callback' => array('Merconis\Core\ls_shop_shipping_methods', 'getShippingModulesAsOptions'),
			'reference' => ($GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['type']['options'] ?? ''),
			'eval' => array('submitOnChange' => true, 'tl_class'=>'w50', 'helpwizard' => true),
			'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default 'standard'"
		),
		
		'formAdditionalData' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['formAdditionalData'],
			'inputType' => 'select',
			'foreignKey' => 'tl_form.title',
			'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'dynamicSteuersatzType' => array(
			'exclude' => true,
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['dynamicSteuersatzType'],
			'inputType' => 'select',
			'options' => array('none', 'main', 'max', 'min'),
			'reference' => ($GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['dynamicSteuersatzType']['options'] ?? ''),
			'eval' => array('submitOnChange' => true, 'tl_class'=>'w50'),
			'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default 'none'"
		),

		'steuersatz' => array(
			'exclude'		=> true,
			'label'			=> &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['steuersatz'],
			'inputType'		=> 'select',
			'options_callback'	=> array('Merconis\Core\ls_shop_generalHelper','getSteuersatzOptions'),
			'eval'			=> array('tl_class'=>'w50'),
			'filter' => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'excludedGroups' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['excludedGroups'],
			'exclude' => true,
			'inputType' => 'checkboxWizard',
			'foreignKey' => 'tl_member_group.name',
			'eval' => array('tl_class'=>'clr','multiple'=>true),
            'sql'                     => "blob NULL"
		),
		
		'weightLimitMin' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['weightLimitMin'],
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50'),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),
		
		'weightLimitMax' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['weightLimitMax'],
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50'),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),
		
		'priceLimitMin' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimitMin'],
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50'),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),
		
		'priceLimitMax' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimitMax'],
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50'),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),
		
		'priceLimitAddCouponToValueOfGoods' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimitAddCouponToValueOfGoods'],
			'inputType'               => 'checkbox',
			'eval'					  =>	array('tl_class' => 'clr m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'countries' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['countries'],
			'inputType'		=>	'text',
			'eval'			=>	array('tl_class' => 'w50'),
			'search' => true,
            'sql'                     => "text NULL"
		),
		
		'countriesAsBlacklist' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['countriesAsBlacklist'],
			'inputType'               => 'checkbox',
			'eval'					  =>	array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'feeType' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeType'],
			'inputType' => 'select',
			'options' => array('none', 'fixed', 'percentaged', 'weight', 'price', 'weightAndPrice', 'formula'),
			'reference' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeType']['options'],
			'eval' => array('submitOnChange' => true, 'helpwizard' => true),
			'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default 'none'"
		),
		
		'feeValue' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeValue'],
			'inputType' => 'text',
			'eval'			=>	array('rgxp' => 'numberWithDecimals'),
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
		),
		
		'feeAddCouponToValueOfGoods' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeAddCouponToValueOfGoods'],
			'inputType'               => 'checkbox',
			'eval'					  =>	array('tl_class' => 'clr m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'feeFormula' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeFormula'],
			'inputType' => 'text',
			'eval'			=>	array('rgxp' => 'feeFormula', 'tl_class'=>'long', 'decodeEntities' => true),
            'sql'                     => "text NULL"
		),
		
		'feeFormulaResultConvertToDisplayPrice' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeFormulaResultConvertToDisplayPrice'],
			'inputType'               => 'checkbox',
			'eval'					  =>	array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'feeWeightValues' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeWeightValues'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
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
            'sql'                     => "text NULL"
		),
		
		'feePriceValues' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feePriceValues'],
			'inputType' => 'text',
			'eval'			=> array(
				'rgxp' => 'numberWithDecimalsLeftAndRight',
				'tl_class' => 'merconis-component-autostart--merconisWidgetMultiText',
                'decodeEntities' => true,
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
            'sql'                     => "text NULL"
		),
		
		'infoAfterCheckout' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['infoAfterCheckout'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class' => 'clr', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true),
            'sql'                     => "text NULL"
		),
		
		'additionalInfo' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['additionalInfo'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class' => 'clr', 'merconis_multilanguage' => true),
            'sql'                     => "text NULL"
		),
		
		'published' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['published'],
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'filter' => true,
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'cssID' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['cssID'],
			'inputType'		=>	'text',
			'eval'			=>	array('tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'cssClass' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['cssClass'],
			'inputType'		=>	'text',
			'eval'			=>	array('tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'sorting' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['sorting'],
			'exclude' => true,
			'inputType'		=>	'text',
			'eval'			=>	array('rgxp' => 'number', 'tl_class' => 'w50', 'mandatory' => true),
			'sorting' => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        )
	)
);





class ls_shop_shipping_methods extends \Backend {
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
		$objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_shipping_methods WHERE id=? OR alias=?")
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
	 * Diese Funktion modifiziert das DCA in Abh�ngigkeit vom gew�hlten Zahlungsmodul.
	 * Es werden hierbei die in der Zahlungsmodul-Definition definierten BE_formFields eingetragen
	 */
	public function modifyDCA($dc) {
		$obj_shippingModule = new ls_shop_shippingModule();
		if (!$dc->id) {
			/*
			 * Handelt es sich bei dem Aufruf nicht um einen datensatzbezogenen Aufruf,
			 * so wird die Verarbeitung dieser Funktion abgebrochen
			 */
			return;
		}
		$objShippingMethod = \Database::getInstance()->prepare("SELECT * FROM `tl_ls_shop_shipping_methods` WHERE `id` = ?")
											->limit(1)
											->execute($dc->id);
		$objShippingMethod->first();
		if (!is_array($obj_shippingModule->types[$objShippingMethod->type]['BE_formFields'])) {
			return false;
		}
		
		/*
		 * Einf�gen der BE_formFields in das Fields-Array dieser DCA-Definition
		 */
		array_insert($GLOBALS['TL_DCA']['tl_ls_shop_shipping_methods']['fields'], 0, $obj_shippingModule->types[$objShippingMethod->type]['BE_formFields']);

		/*
		 * Hinterlegen der Standard-Labels, sofern keine speziell im Zahlungsmodul hinterlegt wurden
		 */
		foreach ($obj_shippingModule->types[$objShippingMethod->type]['BE_formFields'] as $formFieldTitle => $formFieldInfo) {
			$GLOBALS['TL_DCA']['tl_ls_shop_shipping_methods']['fields'][$formFieldTitle]['label'] = &$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods'][$formFieldTitle];
		}
		
		/*
		 * Einf�gen der BE_formFields in die Default-Palette
		 */
		$paletteInsertion = ';{'.$obj_shippingModule->types[$objShippingMethod->type]['typeCode'].'_legend},';
		foreach ($obj_shippingModule->types[$objShippingMethod->type]['BE_formFields'] as $formFieldTitle => $formFieldInfo) {
			$paletteInsertion .= $formFieldTitle.',';
		}
		$GLOBALS['TL_DCA']['tl_ls_shop_shipping_methods']['palettes']['default'] = preg_replace('/(;\{excludedGroups_legend\})/siU', $paletteInsertion.'$1', $GLOBALS['TL_DCA']['tl_ls_shop_shipping_methods']['palettes']['default']);
	}
	
	public function getShippingModulesAsOptions() {
		$obj_shippingModule = new ls_shop_shippingModule();
		$shippingModules = array();
		foreach ($obj_shippingModule->types as $shippingModuleName => $shippingModuleInfo) {
			$shippingModules[$shippingModuleName] = $shippingModuleInfo['title'];
		}
		return $shippingModules;
	}

	/*
	 * Diese Funktion prüft, ob der Datensatz irgendwo im Shop verwendet wird und gibt nur dann
	 * den funktionsfähigen Löschen-Button zurück, wenn der Datensatz nicht verwendet wird und
	 * daher bedenkenlos gelöscht werden kann.
	 */
	public function getDeleteButton($row, $href, $label, $title, $icon, $attributes) {
		$arr_methodIDsCurrentlyUsed = ls_shop_generalHelper::getPaymentOrShippingMethodsUsedInOrders('shipping');
		
		if (!in_array($row['id'], $arr_methodIDsCurrentlyUsed)) {
			$button = '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
		} else {
			$button = \Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
		}
		
		return $button;
	}
}
