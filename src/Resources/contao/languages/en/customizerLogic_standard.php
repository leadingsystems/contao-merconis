<?php

$GLOBALS['TL_LANG']['MSC']['merconis']['customizerLogic_standard'] = [
    'fieldsets' => [
        'step01' => [
            'name' => 'Step 1',
            'misc' => []
        ],
        'step01-01' => [
            'name' => 'Shape',
            'misc' => []
        ],
        'step01-02' => [
            'name' => 'Size',
            'misc' => []
        ],
        'step02' => [
            'name' => 'Step 2',
            'misc' => []
        ],
        'step02-01' => [
            'name' => 'Surface processing',
            'misc' => [
                'hint' => 'You can combine several options.'
            ]
        ],
        'step03' => [
            'name' => 'Step 3',
            'misc' => []
        ],
        'step04' => [
            'name' => 'Step 4',
            'misc' => []
        ],
        'step05' => [
            'name' => 'Step 5',
            'misc' => []
        ],
    ],

    'fields'=> [
        'shape' => [
            'name' => '',
            'values' => [
                'cube' => 'Cube',
                'ball' => 'Ball',
                'cylinder' => 'Cylinder',
                'pyramid' => 'Pyramid'
            ]
        ],

        'size' => [
            'name' => '',
            'values' => [
                'xs' => 'XS',
                's' => 'S',
                'm' => 'M',
                'l' => 'L',
                'xl' => 'XL',
                'xxl' => 'XXL'
            ]
        ],

        'surfaceprocessing-1' => [
            'name' => 'hot-dip galvanized',
            'values' => [
                '0' => 'no',
                '1' => 'yes'
            ]
        ],

        'surfaceprocessing-2' => [
            'name' => 'varnished',
            'values' => [
                '0' => 'no',
                '1' => 'yes'
            ]
        ],

        'surfaceprocessing-3' => [
            'name' => '',
            'values' => [
                '0' => '',
                '1' => 'impregnated'
            ]
        ],

        'surfaceprocessing-4' => [
            'name' => '',
            'values' => [
                '0' => '',
                '1' => 'polished'
            ]
        ],

        'hangers-preassembled' => [
            'name' => 'Hangers preassembled',
            'values' => [
                '' => 'please choose',
                'yes' => 'yes',
                'no' => 'no'
            ]
        ],

        'holes' => [
            'name' => 'Holes',
            'values' => [
                'front' => 'front',
                'back' => 'rear',
                'left' => 'left',
                'right' => 'right'
            ]
        ],

        'print' => [
            'name' => 'Labeling',
            'initialValue' => 'For you!'
        ],

        'greeting-card-text' => [
            'name' => 'Text for greeting card',
            'initialValue' => 'Happy birthday and I wish you a great new year!'
        ],
    ]
];