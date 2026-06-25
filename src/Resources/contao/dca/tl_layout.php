<?php

namespace Merconis\Core;

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('lsShopFilter_legend')
    ->addField(
        [
            'ls_shop_filterMode',
        ], 'lsShopFilter_legend'
    )
    ->applyToPalette('default', 'tl_layout')
;

$GLOBALS['TL_DCA']['tl_layout']['palettes']['__selector__'][] = 'ls_shop_filterMode';
$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['ls_shop_filterMode_classic'] = 'ls_shop_useFilterInStandardProductlist,ls_shop_numFilterFieldsInSummary,ls_shop_useFilterMatchEstimates,ls_shop_matchEstimatesMaxNumProducts,ls_shop_matchEstimatesMaxFilterValues,ls_shop_useFilterInProductDetails,ls_shop_hideFilterFormInProductDetails';
$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['ls_shop_filterMode_fast'] = 'ls_shop_useFilterInStandardProductlist,ls_shop_fastFilterAutoSubmit,ls_shop_fastFilterHideZeroMatches,ls_shop_fastFilterResetMode,ls_shop_hideFilterFormInProductDetails';

$GLOBALS['TL_DCA']['tl_layout']['fields']['lsShopOutputDefinitionSet'] = array (
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_filterMode'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_filterMode'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['', 'classic', 'fast'],
    'reference' => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_filterMode_options'],
    'eval'      => ['submitOnChange' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
    'sql'       => "varchar(16) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_fastFilterAutoSubmit'] = [
    'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_fastFilterAutoSubmit'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class'=>'w50'],
    'sql'                     => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_fastFilterHideZeroMatches'] = [
    'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_fastFilterHideZeroMatches'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class'=>'w50'],
    'sql'                     => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_fastFilterResetMode'] = [
    'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_fastFilterResetMode'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'default'                 => 'always',
    'options'                 => ['never', 'always', 'branch'],
    'reference'               => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_fastFilterResetMode_options'],
    'eval'                    => ['tl_class'=>'w50'],
    'sql'                     => "varchar(16) NOT NULL default 'always'"
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_useFilterInStandardProductlist'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_useFilterInStandardProductlist'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'clr m12'),
    'sql'                     => "char(1) NOT NULL default ''"
);


$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_numFilterFieldsInSummary'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_numFilterFieldsInSummary'],
    'inputType' => 'text',
    'eval' => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50', 'mandatory' => true),
    'sql'                     => "smallint(5) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_useFilterMatchEstimates'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_useFilterMatchEstimates'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'clr m12'),
    'sql'                     => "char(1) NOT NULL default ''"
);


$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_matchEstimatesMaxNumProducts'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_matchEstimatesMaxNumProducts'],
    'inputType' => 'text',
    'eval' => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50', 'mandatory' => true),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_matchEstimatesMaxFilterValues'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_matchEstimatesMaxFilterValues'],
    'inputType' => 'text',
    'eval' => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50', 'mandatory' => true),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_useFilterInProductDetails'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_useFilterInProductDetails'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50 m12'),
    'sql'                     => "char(1) NOT NULL default ''"
);


$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_hideFilterFormInProductDetails'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_hideFilterFormInProductDetails'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50 m12'),
    'sql'                     => "char(1) NOT NULL default ''"
);
