<?php

namespace Merconis\Core;

foreach ($GLOBALS['TL_DCA']['tl_form_field']['palettes'] as $k => $v) {
	if (is_array($v)) {
		continue;
	}

    /*
     * only add the conditional "mandatory fields" to palettes which already include the regular "mandatory field"
     */
    $GLOBALS['TL_DCA']['tl_form_field']['palettes'][$k] = $v.';{lsShop_legend:hide},
        ' . (strpos($GLOBALS['TL_DCA']['tl_form_field']['palettes'][$k], 'mandatory') !== false ? '
            lsShop_mandatoryOnConditionField,lsShop_mandatoryOnConditionValue,lsShop_mandatoryOnConditionBoolean,
            lsShop_mandatoryOnConditionField2,lsShop_mandatoryOnConditionValue2,lsShop_mandatoryOnConditionBoolean2,
        ' : '') . '
        lsShop_ShowOnConditionField,lsShop_ShowOnConditionValue,lsShop_ShowOnConditionBoolean';

}



$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_mandatoryOnConditionField'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_mandatoryOnConditionField'],
	'exclude' => true,
	'inputType'		=> 'select',
	'options_callback'	=> array('Merconis\Core\ls_shop_generalHelper', 'getOtherFieldsInFormAsOptions'),
	'eval'			=> array('includeBlankOption' => true, 'tl_class' => 'w33'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_mandatoryOnConditionValue'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_mandatoryOnConditionValue'],
	'exclude' => true,
	'inputType'		=> 'text',
	'eval'			=> array('tl_class' => 'w33'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_mandatoryOnConditionBoolean'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_mandatoryOnConditionBoolean'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval'			=> array('tl_class' => 'w33-cbx-middle'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_mandatoryOnConditionField2'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_mandatoryOnConditionField'],
    'exclude' => true,
    'inputType'		=> 'select',
    'options_callback'	=> array('Merconis\Core\ls_shop_generalHelper', 'getOtherFieldsInFormAsOptions'),
    'eval'			=> array('includeBlankOption' => true, 'tl_class' => 'w33'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_mandatoryOnConditionValue2'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_mandatoryOnConditionValue'],
    'exclude' => true,
    'inputType'		=> 'text',
    'eval'			=> array('tl_class' => 'w33'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_mandatoryOnConditionBoolean2'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_mandatoryOnConditionBoolean'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval'			=> array('tl_class' => 'w33-cbx-middle'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_ShowOnConditionField'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_ShowOnConditionField'],
    'exclude' => true,
    'inputType'		=> 'select',
    'options_callback'	=> array('Merconis\Core\ls_shop_generalHelper', 'getOtherFieldsInFormAsOptions'),
    'eval'			=> array('includeBlankOption' => true, 'tl_class' => 'w33'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_ShowOnConditionValue'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_ShowOnConditionValue'],
    'exclude' => true,
    'inputType'		=> 'text',
    'eval'			=> array('tl_class' => 'w33'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['lsShop_ShowOnConditionBoolean'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_form_field']['lsShop_ShowOnConditionBoolean'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval'			=> array('tl_class' => 'w33-cbx-middle'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['rgxp']['options'][] = 'merconisCheckVATID';


//add palettes for html Wrapper
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['htmlWrapperStart'] = $GLOBALS['TL_DCA']['tl_form_field']['palettes']['html'];
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['htmlWrapperStop'] = $GLOBALS['TL_DCA']['tl_form_field']['palettes']['html'];




