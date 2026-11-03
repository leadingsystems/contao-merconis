<?php
declare(strict_types=1);

namespace Merconis\Core;

$GLOBALS['TL_DCA'][basename(__FILE__, '.php')] = [
    'config' => [
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],
    'fields' => [
        'id' => [
            'sql' => "bigint(20) unsigned NOT NULL auto_increment",
        ],
        'pid' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'orderItemReference' => [
            'sql' => "bigint(20) unsigned NOT NULL default '0'",
        ],
        'snapshotProductName' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotProductName_customerLanguage' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotVariantTitle' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotVariantTitle_customerLanguage' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotProductNumber' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotUnitPrice' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotUnitPrice_customerLanguage' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotQuantityUnit' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotQuantityUnit_customerLanguage' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotOrderedQuantity' => [
            'sql' => "decimal(12,4) NOT NULL default '0.0000'",
        ],
        'snapshotQuantityDecimals' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'snapshotSalesUnitSize' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'snapshotConfiguratorReferenceNumber' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotCustomizerReferenceNumber' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'withdrawnQuantity' => [
            'sql' => "decimal(12,4) NOT NULL default '0.0000'",
        ],
    ],
];
