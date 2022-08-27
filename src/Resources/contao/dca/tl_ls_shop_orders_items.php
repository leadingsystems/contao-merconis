<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA'][basename(__FILE__, '.php')] = array(
    'config' => array(
        'sql' => array
        (
            'engine' => 'MyISAM',
            'charset' => 'utf8 COLLATE utf8_general_ci',
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
        'itemPosition' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'productVariantID' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'productCartKey' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'price' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'weight' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'quantity' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'priceCumulative' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'weightCumulative' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'taxClass' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'taxPercentage' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'isVariant' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'artNr' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'productTitle' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'variantTitle' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'quantityUnit' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'quantityDecimals' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'configurator_merchantRepresentation' => array (
            'sql'                     => "blob NULL"
        ),

        'configurator_cartRepresentation' => array (
            'sql'                     => "blob NULL"
        ),

        'configurator_hasValue' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'configurator_referenceNumber' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'customizer_hasCustomization' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'customizer_summary' => array (
            'sql'                     => "blob NULL"
        ),

        'customizer_summaryForCart' => array (
            'sql'                     => "blob NULL"
        ),

        'customizer_summaryForMerchant' => array (
            'sql'                     => "blob NULL"
        ),

        'extendedInfo' => array (
            'sql'                     => "blob NULL"
        )
    )
);





