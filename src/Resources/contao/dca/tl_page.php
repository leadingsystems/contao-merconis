<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = array('Merconis\Core\ls_shop_languageHelper', 'multilanguageInitialization');

$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][] = 'lsShopIncludeLayoutForDetailsView';
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] = preg_replace('/(;\{cache_legend:.*\})/siU',';{lsShop_legend},ls_shop_currencyBeforeValue,ls_shop_decimalsSeparator,ls_shop_thousandsSeparator,lsShopOutputDefinitionSet,lsShopIncludeLayoutForDetailsView\\1',$GLOBALS['TL_DCA']['tl_page']['palettes']['root']);
$GLOBALS['TL_DCA']['tl_page']['palettes']['regular'] = preg_replace('/(;\{cache_legend:.*\})/siU',';{lsShop_legend},lsShopOutputDefinitionSet,lsShopIncludeLayoutForDetailsView,ls_shop_useAsCategoryForErp\\1',$GLOBALS['TL_DCA']['tl_page']['palettes']['regular']);
$GLOBALS['TL_DCA']['tl_page']['subpalettes']['lsShopIncludeLayoutForDetailsView'] = 'lsShopLayoutForDetailsView';

// Definieren des Formularfeldes
$GLOBALS['TL_DCA']['tl_page']['fields']['ls_shop_useAsCategoryForErp'] = array (
    'label'                   => &$GLOBALS['TL_LANG']['tl_page']['ls_shop_useAsCategoryForErp'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['ls_shop_currencyBeforeValue'] = array(
	'exclude' => true,
	'label' => &$GLOBALS['TL_LANG']['tl_page']['ls_shop_currencyBeforeValue'],
	'inputType' => 'checkbox',
	'eval' => array('tl_class'=>'w50')
);
		
$GLOBALS['TL_DCA']['tl_page']['fields']['ls_shop_decimalsSeparator'] = array(
	'exclude' => true,
	'label' => &$GLOBALS['TL_LANG']['tl_page']['ls_shop_decimalsSeparator'],
	'inputType' => 'text',
	'eval' => array('mandatory' => true, 'maxlength' => 1, 'tl_class'=>'w50', 'decodeEntities' => true)
);
		
$GLOBALS['TL_DCA']['tl_page']['fields']['ls_shop_thousandsSeparator'] = array(
	'exclude' => true,
	'label' => &$GLOBALS['TL_LANG']['tl_page']['ls_shop_thousandsSeparator'],
	'inputType' => 'text',
	'eval' => array('maxlength' => 1, 'tl_class'=>'w50', 'decodeEntities' => true)
);

$GLOBALS['TL_DCA']['tl_page']['fields']['lsShopOutputDefinitionSet'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_page']['lsShopOutputDefinitionSet'],
	'default'				  => 0,
	'exclude'                 => true,
	'inputType'               => 'select',
	'foreignKey'		  	=> 'tl_ls_shop_output_definitions.title',
	'reference'				  => &$GLOBALS['TL_LANG']['tl_page']['lsShopOutputDefinitionSet']['reference'],
	'eval'					  => array('tl_class'=>'w50', 'helpwizard' => true, 'includeBlankOption' => true),
	'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['lsShopIncludeLayoutForDetailsView'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_page']['lsShopIncludeLayoutForDetailsView'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50', 'submitOnChange'=>true),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_page']['fields']['lsShopLayoutForDetailsView'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_page']['lsShopLayoutForDetailsView'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'foreignKey'              => 'tl_layout.name',
	'options_callback'        => array('tl_page', 'getPageLayouts'),
	'eval'                    => array('chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default '0'",
	'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
);
