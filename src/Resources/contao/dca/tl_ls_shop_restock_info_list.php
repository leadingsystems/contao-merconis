<?php

$GLOBALS['TL_DCA']['tl_ls_shop_restock_info_list'] = array(
    'config' => array(
        'sql' => array
        (
            'keys' => array
            (
                'productvariantid' => 'index',
                'variantid' => 'index',
                'productid' => 'index',
                'memberid' => 'index'
            )
        )
    ),
    'fields' =>  array(
        'productVariantId' => array (
            'sql'                     => "varchar(64) NOT NULL default ''"
        ),
        'productId' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'variantId' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'memberId' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'language' => array (
            'sql'                     => "varchar(8) NOT NULL default ''"
        )
    )
);

