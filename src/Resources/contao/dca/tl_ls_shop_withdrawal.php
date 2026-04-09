<?php
declare(strict_types=1);

namespace Merconis\Core;

$GLOBALS['TL_DCA'][basename(__FILE__, '.php')] = [
    'config' => [
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'orderReference' => 'index',
                'withdrawalId' => 'unique',
            ],
        ],
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'withdrawalId' => [
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'withdrawalTimestamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'name' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'email' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'orderReference' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'snapshotOrderNr' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotOrderDate' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotBillingAddress' => [
            'sql' => "blob NULL",
        ],
        'snapshotShippingAddress' => [
            'sql' => "blob NULL",
        ],
        'snapshotPaymentMethod' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotPaymentMethod_customerLanguage' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotShippingMethod' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'snapshotShippingMethod_customerLanguage' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'freetext' => [
            'sql' => "text NULL",
        ],
        'scenario' => [
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];
