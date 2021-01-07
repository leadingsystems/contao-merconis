<?php

namespace Merconis\Core;

/*
 * Conditional Output
 */
foreach ($GLOBALS['TL_DCA']['tl_article']['palettes'] as $paletteName => $palette)  {
	if ($paletteName == '__selector__') {
		continue;
	}
	$GLOBALS['TL_DCA']['tl_article']['palettes'][$paletteName] .= ';{lsShopConditionalOutput_legend},lsShopOutputCondition';
}
			
$GLOBALS['TL_DCA']['tl_article']['fields']['lsShopOutputCondition'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_article']['lsShopOutputCondition'],
	'exclude'       => true,
	'inputType'		=> 'select',
	'options'		=> array('always', 'onlyInOverview', 'onlyInSingleview', 'onlyIfCartNotEmpty', 'onlyIfCartEmpty', 'onlyIfFeUserLoggedIn', 'onlyIfFeUserNotLoggedIn'),
	'reference'		=> &$GLOBALS['TL_LANG']['tl_article']['lsShopOutputCondition']['options'],
	'eval'			=> array('tl_class' => 'w50', 'helpwizard' => true)
);
