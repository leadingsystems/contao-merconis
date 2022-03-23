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
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'parentIsVariant' => array (
            'sql'                     => "char(1) NOT NULL default '0'"
        ),
        'attributeID' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'attributeValueID' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'sorting' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        )
    )
);
