<?php

$GLOBALS['TL_LANG']['ls_contao_merconis_einvoicing'] = [
    'tl_lsShopSettings' => [
        'ls_contao_merconis_einvoicing_legend' => 'E-Rechnung',
        'ls_contao-merconis' => [
            'ls_shop_einvoicingSellerName' => [
                'Firmenname',
                'Bitte tragen Sie hier den Firmennamen ein, der in der E-Rechnung als Verkäufername verwendet werden soll.',
            ],
            'ls_shop_einvoicingSellerStreet' => [
                'Straße',
                'Bitte tragen Sie hier die Straße der Verkäuferanschrift für die E-Rechnung ein.',
            ],
            'ls_shop_einvoicingSellerPostalCode' => [
                'PLZ',
                'Bitte tragen Sie hier die Postleitzahl der Verkäuferanschrift für die E-Rechnung ein.',
            ],
            'ls_shop_einvoicingSellerCity' => [
                'Ort',
                'Bitte tragen Sie hier den Ort der Verkäuferanschrift für die E-Rechnung ein.',
            ],
            'ls_shop_einvoicingSellerTaxNumber' => [
                'Steuernummer',
                'Bitte tragen Sie hier optional die Steuernummer des Verkäufers ein.',
            ],
            'ls_shop_einvoicingSellerElectronicAddress' => [
                'Elektronische Adresse',
                'Bitte tragen Sie hier die elektronische Verkäuferadresse für die E-Rechnung ein, z. B. E-Mail, GLN oder Leitweg-ID.',
            ],
            'ls_shop_einvoicingSellerElectronicAddressScheme' => [
                'Schema der elektronischen Adresse',
                'Bitte tragen Sie hier den zugehörigen EAS-Code ein, z. B. EM, 0204 oder 0088.',
            ],
            'ls_shop_einvoicingSellerIban' => [
                'IBAN',
                'Bitte tragen Sie hier optional die IBAN des Verkäufers für Zahlungsinformationen in der E-Rechnung ein.',
            ],
            'ls_shop_einvoicingSellerBic' => [
                'BIC',
                'Bitte tragen Sie hier optional den BIC des Verkäufers für Zahlungsinformationen in der E-Rechnung ein.',
            ],
            'ls_shop_einvoicingPaymentTermsDays' => [
                'Zahlungsziel (Tage)',
                'Bitte tragen Sie hier optional die Anzahl der Tage bis zur Fälligkeit als Standardwert für E-Rechnungen ein.',
            ],
            'ls_shop_einvoicingPaymentTermsNote' => [
                'Zahlungsbedingungen',
                'Bitte tragen Sie hier optional die Standard-Zahlungsbedingungen für E-Rechnungen ein.',
            ],
        ],
    ],
    'tl_member_group' => [
        'ls_contao_merconis_einvoicing_legend' => 'E-Rechnung',
        'ls_contao-merconis' => [
            'lsShopEinvoicingPaymentTermsDays' => [
                'Zahlungsziel (Tage)',
                'Bitte tragen Sie hier optional ein abweichendes Zahlungsziel für diese Mitgliedergruppe ein.',
            ],
            'lsShopEinvoicingPaymentTermsNote' => [
                'Zahlungsbedingungen',
                'Bitte tragen Sie hier optional abweichende Zahlungsbedingungen für diese Mitgliedergruppe ein.',
            ],
        ],
    ],
    'tl_ls_shop_payment_methods' => [
        'ls_contao_merconis_einvoicing_legend' => 'E-Rechnung',
        'ls_contao-merconis' => [
            'ls_shop_einvoicingPaymentMeansCode' => [
                'Payment Means Code',
                'Bitte tragen Sie hier den EN-16931 Payment Means Code für diese Zahlungsart ein. In Stufe 1 ist standardmäßig 58 für SEPA-Überweisung vorgesehen.',
            ],
        ],
    ],
    'tl_ls_shop_steuersaetze' => [
        'ls_contao_merconis_einvoicing_legend' => 'E-Rechnung',
        'ls_contao-merconis' => [
            'ls_shop_einvoicingTaxCategory' => [
                'Steuerkategorie',
                'Bestimmen Sie hier die EN-16931-Steuerkategorie. Der Standardwert leitet die Kategorie automatisch aus dem Steuersatz ab.',
                'options' => [
                    'auto' => 'Steuersatz wie angegeben',
                    'S' => 'Normalsatz (S)',
                    'Z' => 'Nullsatz (Z)',
                    'E' => 'Steuerbefreit (E)',
                    'AE' => 'Reverse Charge (AE)',
                    'K' => 'Innergemeinschaftlich (K)',
                    'G' => 'Export Drittland (G)',
                ],
            ],
        ],
    ],
];
