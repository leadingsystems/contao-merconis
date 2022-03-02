<?php

namespace Merconis\Core;

/*
 * CrossSeller
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['lsShopCrossSellerCTE'] = '{type_legend},type;{lsShopCrossSeller_legend},lsShopCrossSeller;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_content']['fields']['lsShopCrossSeller'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_content']['lsShopCrossSeller'],
	'exclude'                 => true,
	'inputType'		=> 'select',
	'foreignKey'	=> 'tl_ls_shop_cross_seller.title',
	'eval'			=> array('tl_class' => 'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);


/*
 * Conditional Output
 */
foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $paletteName => $palette)  {
	if ($paletteName == '__selector__') {
		continue;
	}
	$GLOBALS['TL_DCA']['tl_content']['palettes'][$paletteName] .= ';{lsShopConditionalOutput_legend},lsShopOutputCondition';
}
			
$GLOBALS['TL_DCA']['tl_content']['fields']['lsShopOutputCondition'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_content']['lsShopOutputCondition'],
	'exclude'                 => true,
	'inputType'		=> 'select',
	'options'		=> array('always', 'onlyInOverview', 'onlyInSingleview', 'onlyIfCartNotEmpty', 'onlyIfCartEmpty', 'onlyIfFeUserLoggedIn', 'onlyIfFeUserNotLoggedIn'),
	'reference'			=> &$GLOBALS['TL_LANG']['tl_content']['lsShopOutputCondition']['options'],
	'eval'			=> array('tl_class' => 'w50', 'helpwizard' => true),
    'sql'                     => "varchar(32) NOT NULL default ''"
);
