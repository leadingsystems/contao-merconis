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
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['productVariantId'],
            'exclude'                 => true,
            'sql'                     => "varchar(64) NOT NULL default ''"
        ),
        'productId' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['productId'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'variantId' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['variantId'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'memberId' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['memberId'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'language' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['language'],
            'exclude'                 => true,
            'sql'                     => "varchar(8) NOT NULL default ''"
        )
    )
);

