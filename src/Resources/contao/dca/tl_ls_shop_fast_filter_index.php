<?php

$GLOBALS['TL_DCA']['tl_ls_shop_fast_filter_index'] = [
    'config' => [
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'product_id,variant_id,attribute_id,attribute_value_id' => 'unique',
                'product_id,variant_id' => 'index',
                'attribute_id,attribute_value_id,product_id,variant_id' => 'index',
                'filter_field_id,attribute_value_id' => 'index',
                'producer,product_id' => 'index',
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
        'product_id' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'variant_id' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'producer' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'filter_field_id' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'attribute_id' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'attribute_value_id' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'numeric_value' => [
            'sql' => "decimal(18,6) NULL",
        ],
    ],
];
