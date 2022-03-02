<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_singular_storage'] = array(
    'config' => array(
        'sql' => array
        (
            'engine' => 'MyISAM',
            'charset' => 'COLLATE utf8_general_ci',
            'keys' => array
            (
                'key' => 'primary'
            )
        )
    ),
    'fields' =>  array(
        'key' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['key'],
            'exclude'                 => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'int_value' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['int_value'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'float_value' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['float_value'],
            'exclude'                 => true,
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),
        'str_value' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['str_value'],
            'exclude'                 => true,
            'sql'                     => "text NULL"
        ),
        'bln_value' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['bln_value'],
            'exclude'                 => true,
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'arr_value' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['arr_value'],
            'exclude'                 => true,
            'sql'                     => "blob NULL"
        )
    )
);

