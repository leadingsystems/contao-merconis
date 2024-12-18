<?php

namespace Merconis\Core;

use Contao\Backend;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_user']['config']['onload_callback'][] = array('Merconis\Core\tl_user', 'onloadCallback');

$GLOBALS['TL_DCA']['tl_user']['fields']['lsShopBeOrderTemplateOverview'] = array(
	'exclude' => true,
	'label' => &$GLOBALS['TL_LANG']['tl_user']['lsShopBeOrderTemplateOverview'],
	'inputType' => 'select',
	'options_callback' => array('Merconis\Core\ls_shop_generalHelper', 'getTemplates_beOrderOverview'),
	'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
	'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_user']['fields']['lsShopBeOrderTemplateDetails'] = array(
	'exclude' => true,
	'label' => &$GLOBALS['TL_LANG']['tl_user']['lsShopBeOrderTemplateDetails'],
	'inputType' => 'select',
	'options_callback' => array('Merconis\Core\ls_shop_generalHelper', 'getTemplates_beOrderDetails'),
	'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
	'sql'                     => "varchar(64) NOT NULL default ''"
);

class tl_user extends Backend
{
    public function onloadCallback(): void
    {
        foreach ($GLOBALS['TL_DCA']['tl_user']['palettes'] as $name => $palette)  {
            if (!is_string($palette)) {
                continue;
            }
            PaletteManipulator::create()
                ->addLegend('lsShop_legend', null, PaletteManipulator::POSITION_AFTER, true)
                ->addField([
                    'lsShopBeOrderTemplateOverview',
                    'lsShopBeOrderTemplateDetails'
                ], 'lsShop_legend')
                ->applyToPalette($name, 'tl_user')
            ;
        }
    }
}
