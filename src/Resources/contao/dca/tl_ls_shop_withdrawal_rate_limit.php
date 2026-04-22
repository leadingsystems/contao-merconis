<?php
declare(strict_types=1);

namespace Merconis\Core;

$GLOBALS['TL_DCA'][basename(__FILE__, '.php')] = [
    'config' => [
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'scope,identifierHash,timeBucket' => 'unique',
                'tstamp' => 'index',
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
        'scope' => [
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'identifierHash' => [
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'timeBucket' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'hitCount' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
    ],
];
