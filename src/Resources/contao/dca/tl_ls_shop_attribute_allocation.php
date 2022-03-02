<?php

$GLOBALS['TL_DCA']['tl_ls_shop_attribute_allocation'] = array(
    'config' => array(
        'sql' => array
        (
            'keys' => array
            (
                'pid' => 'index'
            )
        )
    ),
    'fields' =>  array(
        'pid' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['pid'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'parentIsVariant' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['parentIsVariant'],
            'exclude'                 => true,
            'sql'                     => "char(1) NOT NULL default '0'"
        ),
        'attributeID' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['attributeID'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'attributeValueID' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['attributeValueID'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'sorting' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['sorting'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        )
    )
);
