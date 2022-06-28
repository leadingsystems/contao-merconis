<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_orders_customer_data'] = array(
    'config' => array(
        'sql' => array
        (
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
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'dataType' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'fieldName' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'fieldValue' => array (
            'sql'                     => "text NULL"
        )
    )
);

