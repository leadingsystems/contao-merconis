<?php

namespace Merconis\Core;

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/*
 * Conditional Output
 */
PaletteManipulator::create()
    ->addLegend('lsShopConditionalOutput_legend')
    ->addField('lsShopOutputCondition', 'lsShopConditionalOutput_legend')
    ->applyToPalette('default', 'tl_article')
;
			
$GLOBALS['TL_DCA']['tl_article']['fields']['lsShopOutputCondition'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_article']['lsShopOutputCondition'],
	'exclude'       => true,
	'inputType'		=> 'select',
	'options'		=> array('always', 'onlyInOverview', 'onlyInSingleview', 'onlyIfCartNotEmpty', 'onlyIfCartEmpty', 'onlyIfFeUserLoggedIn', 'onlyIfFeUserNotLoggedIn'),
	'reference'		=> &$GLOBALS['TL_LANG']['tl_article']['lsShopOutputCondition']['options'],
	'eval'			=> array('tl_class' => 'w50', 'helpwizard' => true),
    'sql'                     => "varchar(32) NOT NULL default ''"
);

