<?php

namespace Merconis\Core;

foreach ($GLOBALS['TL_DCA']['tl_form_field']['palettes'] as $k => $v) {
	if (is_array($v)) {
		continue;
	}
	$GLOBALS['TL_DCA']['tl_form_field']['palettes'][$k] = $v.';{lsShop_legend:hide},lsShop_mandatoryOnConditionField,lsShop_mandatoryOnConditionValue,lsShop_mandatoryOnConditionField2,lsShop_mandatoryOnConditionValue2';
}

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_mandatoryOnConditionField'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_mandatoryOnConditionField'],
	'exclude' => true,
	'inputType'		=> 'select',
	'options_callback'	=> array('Merconis\Core\ls_shop_generalHelper', 'getOtherFieldsInFormAsOptions'),
	'eval'			=> array('tl_class' => 'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_mandatoryOnConditionValue'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_mandatoryOnConditionValue'],
	'exclude' => true,
	'inputType'		=> 'text',
	'eval'			=> array('tl_class' => 'w50'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_mandatoryOnConditionField2'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_mandatoryOnConditionField'],
    'exclude' => true,
    'inputType'		=> 'select',
    'options_callback'	=> array('Merconis\Core\ls_shop_generalHelper', 'getOtherFieldsInFormAsOptions'),
    'eval'			=> array('tl_class' => 'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_mandatoryOnConditionValue2'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_mandatoryOnConditionValue'],
    'exclude' => true,
    'inputType'		=> 'text',
    'eval'			=> array('tl_class' => 'w50'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['rgxp']['options'][] = 'merconisCheckVATID';




