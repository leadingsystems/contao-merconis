<?php

$GLOBALS['TL_DCA']['tl_ls_shop_product_page_map'] = array(
    'config' => array(
        'sql' => array
        (
            'keys' => array
            (
                'pid,page_id' => 'primary',
                'pid' => 'index',
                'page_id' => 'index'
            )
        )
    ),
    'fields' =>  array(
        'pid' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'page_id' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        )
    )
);


