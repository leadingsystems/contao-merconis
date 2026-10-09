<?php

$GLOBALS['TL_LANG']['ls_contao_merconis_einvoicing'] = [
    'tl_lsShopSettings' => [
        'ls_contao_merconis_einvoicing_legend' => 'E-Invoicing',
        'ls_contao-merconis' => [
            'ls_shop_einvoicingSellerName' => [
                'Seller name',
                'Please enter the seller name to be used in the e-invoice here.',
            ],
            'ls_shop_einvoicingSellerStreet' => [
                'Street',
                'Please enter the street of the seller address for the e-invoice here.',
            ],
            'ls_shop_einvoicingSellerPostalCode' => [
                'Postal code',
                'Please enter the postal code of the seller address for the e-invoice here.',
            ],
            'ls_shop_einvoicingSellerCity' => [
                'City',
                'Please enter the city of the seller address for the e-invoice here.',
            ],
            'ls_shop_einvoicingSellerTaxNumber' => [
                'Tax number',
                'Please optionally enter the seller tax number here.',
            ],
            'ls_shop_einvoicingSellerElectronicAddress' => [
                'Electronic address',
                'Please enter the seller electronic address for the e-invoice here, e.g. email, GLN or routing ID.',
            ],
            'ls_shop_einvoicingSellerElectronicAddressScheme' => [
                'Electronic address scheme',
                'Please enter the matching EAS code here, e.g. EM, 0204 or 0088.',
            ],
            'ls_shop_einvoicingSellerIban' => [
                'IBAN',
                'Please optionally enter the seller IBAN for payment information in the e-invoice here.',
            ],
            'ls_shop_einvoicingSellerBic' => [
                'BIC',
                'Please optionally enter the seller BIC for payment information in the e-invoice here.',
            ],
            'ls_shop_einvoicingPaymentTermsDays' => [
                'Payment terms (days)',
                'Please optionally enter the default number of days until due date for e-invoices here.',
            ],
            'ls_shop_einvoicingPaymentTermsNote' => [
                'Payment terms note',
                'Please optionally enter the default payment terms note for e-invoices here.',
            ],
        ],
    ],
    'tl_member_group' => [
        'ls_contao_merconis_einvoicing_legend' => 'E-Invoicing',
        'ls_contao-merconis' => [
            'lsShopEinvoicingPaymentTermsDays' => [
                'Payment terms (days)',
                'Please optionally enter a group-specific payment terms override here.',
            ],
            'lsShopEinvoicingPaymentTermsNote' => [
                'Payment terms note',
                'Please optionally enter a group-specific payment terms note override here.',
            ],
        ],
    ],
    'tl_ls_shop_payment_methods' => [
        'ls_contao_merconis_einvoicing_legend' => 'E-Invoicing',
        'ls_contao-merconis' => [
            'ls_shop_einvoicingPaymentMeansCode' => [
                'Payment Means Code',
                'Please enter the EN 16931 payment means code for this payment method here. Stage 1 uses 58 for SEPA transfer by default.',
            ],
        ],
    ],
    'tl_ls_shop_steuersaetze' => [
        'ls_contao_merconis_einvoicing_legend' => 'E-Invoicing',
        'ls_contao-merconis' => [
            'ls_shop_einvoicingTaxCategory' => [
                'Tax category',
                'Choose the EN 16931 tax category here. The default value derives the category automatically from the tax rate.',
                'options' => [
                    'auto' => 'Tax rate as configured',
                    'S' => 'Standard rate (S)',
                    'Z' => 'Zero rated (Z)',
                    'E' => 'Exempt from tax (E)',
                    'AE' => 'Reverse charge (AE)',
                    'K' => 'Intra-community supply (K)',
                    'G' => 'Export outside EU (G)',
                ],
            ],
        ],
    ],
];
