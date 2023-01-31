<?php

namespace Merconis\Core;

	foreach ($GLOBALS['TL_DCA']['tl_user']['palettes'] as $k => $v) {
		if ($k == '__selector__') {
			continue;
		}
		
		$GLOBALS['TL_DCA']['tl_user']['palettes'][$k] .= ';{lsShop_legend:hide},lsShopBeOrderTemplateOverview,lsShopBeOrderTemplateDetails';
	}
	
	$GLOBALS['TL_DCA']['tl_user']['fields']['lsShopBeOrderTemplateOverview'] = array(
		'exclude' => true,
		'label' => &$GLOBALS['TL_LANG']['tl_user']['lsShopBeOrderTemplateOverview'],
		'inputType' => 'select',
		'options_callback' => array('Merconis\Core\ls_shop_generalHelper', 'getTemplates_beOrderOverview'),
		'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
        'sql' => "varchar(64) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_user']['fields']['lsShopBeOrderTemplateDetails'] = array(
		'exclude' => true,
		'label' => &$GLOBALS['TL_LANG']['tl_user']['lsShopBeOrderTemplateDetails'],
		'inputType' => 'select',
		'options_callback' => array('Merconis\Core\ls_shop_generalHelper', 'getTemplates_beOrderDetails'),
		'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
        'sql' => "varchar(64) NOT NULL default ''"
	);
?>