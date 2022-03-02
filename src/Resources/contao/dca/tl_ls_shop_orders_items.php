<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_orders_orders_items']['fields'] = array(
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
            'sql'                     => "int(10) unsigned NOT NULL default '0"
        ),
        'tstamp' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['tstamp'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'itemPosition' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['itemPosition'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'productVariantID' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['productVariantID'],
            'exclude'                 => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'productCartKey' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['productCartKey'],
            'exclude'                 => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'price' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['price'],
            'exclude'                 => true,
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'weight' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['weight'],
            'exclude'                 => true,
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'quantity' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['quantity'],
            'exclude'                 => true,
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'priceCumulative' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['priceCumulative'],
            'exclude'                 => true,
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'weightCumulative' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['weightCumulative'],
            'exclude'                 => true,
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'taxClass' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['taxClass'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'taxPercentage' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['taxPercentage'],
            'exclude'                 => true,
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'isVariant' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['isVariant'],
            'exclude'                 => true,
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'artNr' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['artNr'],
            'exclude'                 => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'productTitle' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['productTitle'],
            'exclude'                 => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'variantTitle' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['variantTitle'],
            'exclude'                 => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'quantityUnit' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['quantityUnit'],
            'exclude'                 => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'quantityDecimals' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['quantityDecimals'],
            'exclude'                 => true,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'configurator_merchantRepresentation' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['configurator_merchantRepresentation'],
            'exclude'                 => true,
            'sql'                     => "blob NULL"
        ),

        'configurator_cartRepresentation' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['configurator_cartRepresentation'],
            'exclude'                 => true,
            'sql'                     => "blob NULL"
        ),

        'configurator_hasValue' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['configurator_hasValue'],
            'exclude'                 => true,
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'configurator_referenceNumber' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['configurator_referenceNumber'],
            'exclude'                 => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'extendedInfo' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['extendedInfo'],
            'exclude'                 => true,
            'sql'                     => "blob NULL"
        )
    )
);





