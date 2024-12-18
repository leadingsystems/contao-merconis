<?php

namespace Merconis\Core;

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('lsShopFilter_legend')
    ->addField(
        [
            'ls_shop_activateFilter',
            'ls_shop_useFilterInStandardProductlist',
            'ls_shop_numFilterFieldsInSummary',
            'ls_shop_useFilterMatchEstimates',
            'ls_shop_matchEstimatesMaxNumProducts',
            'ls_shop_matchEstimatesMaxFilterValues',
            'ls_shop_useFilterInProductDetails',
            'ls_shop_hideFilterFormInProductDetails'
        ], 'lsShopFilter_legend'
    )
    ->applyToPalette('default', 'tl_layout')
;

$GLOBALS['TL_DCA']['tl_layout']['fields']['lsShopOutputDefinitionSet'] = array (
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['ls_shop_activateFilter'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['ls_shop_activateFilter'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'clr m12'),
    'sql'                     => "char(1) NOT NULL default ''"
);

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
