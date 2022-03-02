<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_orders_customer_data'] = array(
    'config' => array(
        'sql' => array
        (
            'engine' => 'MyISAM',
            'charset' => 'COLLATE utf8_general_ci',
            'keys' => array
            (
                'id' => 'primary'
            )
        )
    ),
    'fields' =>  array(
        'id' => array (
            'sql'                     => "bigint(20) unsigned NOT NULL auto_increment"
        ),
        'pid' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['pid'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'tstamp' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['tstamp'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'dataType' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['dataType'],
            'exclude'                 => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'fieldName' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['fieldName'],
            'exclude'                 => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'fieldValue' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['fieldValue'],
            'exclude'                 => true,
            'sql'                     => "text NULL"
        )
    )
);

