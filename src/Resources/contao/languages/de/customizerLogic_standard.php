<?php

$GLOBALS['TL_LANG']['MSC']['merconis']['customizerLogic_standard'] = [
    'fieldsets' => [
        'step01' => [
            'name' => 'Schritt 1',
            'misc' => []
        ],
        'step01-01' => [
            'name' => 'Form',
            'misc' => []
        ],
        'step01-02' => [
            'name' => 'Größe',
            'misc' => []
        ],
        'step02' => [
            'name' => 'Schritt 2',
            'misc' => []
        ],
        'step02-01' => [
            'name' => 'Oberflächenbearbeitung',
            'misc' => [
                'hint' => 'Sie können mehrere Optionen kombinieren.'
            ]
        ],
        'step03' => [
            'name' => 'Schritt 3',
            'misc' => []
        ],
        'step04' => [
            'name' => 'Schritt 4',
            'misc' => []
        ],
        'step05' => [
            'name' => 'Schritt 5',
            'misc' => []
        ],
    ],

    'fields'=> [
        'shape' => [
            'name' => '',
            'values' => [
                'cube' => 'Würfel',
                'ball' => 'Kugel',
                'cylinder' => 'Zylinder',
                'pyramid' => 'Pyramide'
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
            'name' => 'feuerverzinkt',
            'values' => [
                '0' => 'nein',
                '1' => 'ja'
            ]
        ],

        'surfaceprocessing-2' => [
            'name' => 'lackiert',
            'values' => [
                '0' => 'nein',
                '1' => 'ja'
            ]
        ],

        'surfaceprocessing-3' => [
            'name' => '',
            'values' => [
                '0' => '',
                '1' => 'imprägniert'
            ]
        ],

        'surfaceprocessing-4' => [
            'name' => '',
            'values' => [
                '0' => '',
                '1' => 'poliert'
            ]
        ],

        'hangers-preassembled' => [
            'name' => 'Aufhängung vormontiert',
            'values' => [
                '' => 'bitte wählen',
                'yes' => 'ja',
                'no' => 'nein'
            ]
        ],

        'holes' => [
            'name' => 'Bohrungen',
            'values' => [
                'front' => 'vorne',
                'back' => 'hinten',
                'left' => 'links',
                'right' => 'rechts'
            ]
        ],

        'print' => [
            'name' => 'Beschriftung',
            'initialValue' => 'Für Dich!'
        ],

        'greeting-card-text' => [
            'name' => 'Text für Grußkarte',
            'initialValue' => 'Alles Gute zum Geburtstag und ein tolles neues Lebensjahr wünsche ich Dir!'
        ],
    ]
];