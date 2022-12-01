<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_singular_storage'] = array(
    'config' => array(
        'sql' => array
        (
            'keys' => array
            (
                'key' => 'primary'
            )
        )
    ),
    'fields' =>  array(
        'key' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'int_value' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'float_value' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),
        'str_value' => array (
            'sql'                     => "text NULL"
        ),
        'bln_value' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'arr_value' => array (
            'sql'                     => "blob NULL"
        )
    )
);

