<?php
namespace Merconis\Core;


trait xrechnung_elementData
{

    //Aufbau der Daten:
    //    name: Name of the information element from PDF documentation, e.g. Invoice Number
    //          Has no functional impact
    //    id: of the information element e.g. BT-2
    //          For parent elements that are not described: PAR_BT-81
    //          For child elements that are not described: BT-13_SUB-1
    //          Each additional level continues to receive SUB suffixes
    //          Get completely unknown nodes
    //    source: Data source or key name in the arrOrder array if it is a lower node
    //          goes e.g. items[1][itemPosition]
    //          If the key itself is an array, then it means a value from the "additionalParams" array
    //    transform: Data transforms function e.g. date('Ymd') for timestamp. Specified as an array. First element
    //          is the function name, second element is for parameters, e.g. "0".
    //    calculate: Calculation function e.g. amountDueForPayment for calculating remaining amounts (if source as
    //          Source field specification is not enough)
    //    condition: Calculation functions for conditional processes
    //    xml: the key of the XML element
    //    xmlAttributes: array for properties within an XML tag. Each attribute has an array with 2 elements
    //          First element is the name e.g. currencyID="EUR".
    //          The second is the data for which a corresponding function name
    //          is specified
    //    firstSub: first child element of group elements to start with.
    //    next: next information element or successor
    //    parent: id of the parent element e.g. BT-2
    //    repeat: the current element is repeated for every key that is below the specified key in arrOrder
    //    Example: 'items'. There are several IDs here (one for each article). Thus the entire InformationElement
    //          repeated for each item (order item).
    public $listElements = array(

        array('name' => 'customizationId',
            'id' => 'BT-24',
            'calculate' => 'customizationId',
            'xml' => 'cbc:CustomizationID',
            'next' => 'BT-23'
        ),

        array('name' => 'Business process type',               //PFLICHT
            'id' => 'BT-23',
            'calculate' => 'businessProcessType',
            'xml' => 'cbc:ProfileID',
            'next' => 'BT-1'
        ),

        array('name' => 'Invoice Number',               //PFLICHT
            'id' => 'BT-1',
            'source' => ['messageCounterNr'],
            'xml' => 'cbc:ID',
            'next' => 'BT-2'
        ),

        //das Ausstellungsdatum der Rechnung sein
        array('name' => 'Invoice issue Date',           //PFLICHT
            'id' => 'BT-2',
            'calculate' => 'currentDate',
            'xml' => 'cbc:IssueDate',
            'next' => 'BT-9'),

        //Fälligkeitsdatum des Rechnungsbetrages
        array('name' => 'Payment Due Date',             //OPTIONAL
            'id' => 'BT-9',
            'source' => ['tstamp'],
            'transform' => ['timestamp2Date'],
            'xml' => 'cbc:DueDate',
            'next' => 'BT-3'),

        array('name' => 'Invoice type code',        //PFLICHT
            'id' => 'BT-3',
            'transform' => ['invoiceTypeCode'],
            'xml' => 'cbc:InvoiceTypeCode',
            'next' => 'BG-1'),

//TODO: sollen hier auch die weiteren notesLong oder freetext mit ausgegeben werden ? Dann gleich eine Gruppe machen
        array('name' => 'Invoice Note',             //OPTIONAL
            'id' => 'BG-1',
            'source' => ['notesShort'],
            'xml' => 'cbc:Note',
            'next' => 'BT-5'),

        array('name' => 'Invoice currency code',        //PFLICHT
            'id' => 'BT-5',
            'source' => ['currency'],
            'xml' => 'cbc:DocumentCurrencyCode',
            'next' => 'BT-10'),

        array('name' => 'Buyer reference',              //PFLICHT
            'id' => 'BT-10',
            'source' => ['customerNr'],
            'xml' => 'cbc:BuyerReference',
            'next' => 'BT-13'),

        array('name' => 'Purchase order reference',     //OPTIONAL
            'id' => 'BT-13',
            'xml' => 'cac:OrderReference',
            'firstSub' => 'BT-13_SUB-1',
            'next' => 'PAR_BT-12'
            ),

            //Bezug zu einem Auftrag
            array('name' => '_Order Reference ID',
                'id' => 'BT-13_SUB-1',
                'source' => ['orderNr'],
                'xml' => 'cbc:ID',
                'parent' => 'BT-13'
                ),

        //Bezug zu einem Vertrag
        array('name' => '_Contract Document Reference',     //OPTIONAL
            'id' => 'PAR_BT-12',
            'xml' => 'cac:ContractDocumentReference',
            'firstSub' => 'BT-12',
            'next' => 'PAR_BT-11'
            ),

            array('name' => 'Contract Reference',          //OPTIONAL
                'id' => 'BT-12',
                'xml' => 'cbc:ID',
                'parent' => 'PAR_BT-12'
                ),

        //Bezug zu einem Projekt
        array('name' => '_Project reference',     //OPTIONAL
            'id' => 'PAR_BT-11',
            'xml' => 'cac:ProjectReference',
            'firstSub' => 'BT-11',
            'next' => 'BG-4'
            ),

            array('name' => 'Project reference',          //OPTIONAL
                'id' => 'BT-11',
//TODO: haben wir Bezug zu Projekten ?
                'calculate' => 'getProjectName',
                'xml' => 'cbc:ID',
                'parent' => 'PAR_BT-11'
                ),


        //Verkäufer
        array('name' => 'Seller',          //PFLICHT
            'id' => 'BG-4',
            'xml' => 'cac:AccountingSupplierParty',
            'firstSub' => 'BG-4_SUB-1',
            'next' => 'BG-7',
            ),

        array('name' => 'Seller Party',          //PFLICHT
            'id' => 'BG-4_SUB-1',
            'xml' => 'cac:Party',
            'parent' => 'BG-4',
            'firstSub' => 'BT-34',
            ),

        array('name' => 'Seller electronic adress',          //PFLICHT
            'id' => 'BT-34',
            'xml' => 'cbc:EndpointID',
            'xmlAttributes' => [['schemeID', 'sellerEletronicAdressScheme']],
            'calculate' => 'sellerEletronicAdress',
            'parent' => 'BG-4_SUB-1',
            'next' => 'BG-5',
            ),

        array('name' => 'Seller postal adress',          //PFLICHT
            'id' => 'BG-5',
            'xml' => 'cac:PostalAddress',
            'parent' => 'BG-4_SUB-1',
            'firstSub' => 'BT-35',
            'next' => 'PAR_BT-31',
            ),

        array('name' => 'Seller address line 1',          //OPTIONAL
            'id' => 'BT-35',
            'xml' => 'cbc:StreetName',
            'calculate' => 'sellerStreetName',
            'parent' => 'BG-5',
            'next' => 'BT-37',
            ),

        array('name' => 'Seller city',          //PFLICHT
            'id' => 'BT-37',
            'xml' => 'cbc:CityName',
            'calculate' => 'sellerCity',
            'parent' => 'BG-5',
            'next' => 'BT-38',
            ),

        array('name' => 'Seller post code',          //PFLICHT
            'id' => 'BT-38',
            'xml' => 'cbc:PostalZone',
            'calculate' => 'sellerPostCode',
            'parent' => 'BG-5',
            'next' => 'PAR_BT-40',
            ),

        array('name' => '_Seller country code',          //PFLICHT
            'id' => 'PAR_BT-40',
            'xml' => 'cac:Country',
            'parent' => 'BG-5',
            'firstSub' => 'BT-40',
            ),

        array('name' => 'Seller country code',          //PFLICHT
            'id' => 'BT-40',
            'xml' => 'cbc:IdentificationCode',
            'calculate' => 'sellerCountryCode',
            'parent' => 'PAR_BT-40',
            ),


        array('name' => 'Party VAT/Tax Identifiers',          //OPTIONAL, kann aber bemängelt werden
            'id' => 'PAR_BT-31',
            'xml' => 'cac:PartyTaxScheme',
            'parent' => 'BG-4_SUB-1',
            'firstSub' => 'BT-31',
            'next' => 'PAR_BT-30',
            ),

            array('name' => 'Seller VAT identifier',          //BEDINGT, WENN vatCategoryCode = ´S´
                'id' => 'BT-31',
                'source' => '',
                'calculate' => 'sellerVATIdentifier',
                'xml' => 'cbc:CompanyID',
                'parent' => 'PAR_BT-31',
                'next' => 'PAR_BT-31_SUB-1',
                ),

            array('name' => 'Seller Tax Scheme',          //
                'id' => 'PAR_BT-31_SUB-1',
                'xml' => 'cac:TaxScheme',
                'parent' => 'PAR_BT-31',
                'firstSub' => 'PAR_BT-31_SUB-1_SUB-1',
                ),

                array('name' => 'Seller Tax Scheme Id',          //
                    'id' => 'PAR_BT-31_SUB-1_SUB-1',
                    'calculate' => 'sellerTAXSchemeId',
                    'xml' => 'cbc:ID',
                    'parent' => 'PAR_BT-31_SUB-1',
                    ),

        array('name' => '_Seller legal registration identifier',          //OPTIONAL
            'id' => 'PAR_BT-30',
            'xml' => 'cac:PartyLegalEntity',
            'parent' => 'BG-4_SUB-1',
            'firstSub' => 'BT-30',
            'next' => 'BG-6',
            ),

        array('name' => 'Seller legal registration identifier',          //OPTIONAL
            'id' => 'BT-30',
            'xml' => 'cbc:RegistrationName',
            'calculate' => 'sellerRegistrationName',
            'parent' => 'PAR_BT-30',
            ),


        array('name' => 'Seller contact',          //PFLICHT
            'id' => 'BG-6',
            'xml' => 'cac:Contact',
            'parent' => 'BG-4_SUB-1',
            'firstSub' => 'BT-41',
            ),

        array('name' => 'Seller contact point',          //PFLICHT
            'id' => 'BT-41',
            'xml' => 'cbc:Name',
            'calculate' => 'sellerContactPoint',
            'parent' => 'BG-6',
            'next' => 'BT-42',
            ),

        array('name' => 'Seller contact telephone number',          //PFLICHT
            'id' => 'BT-42',
            'xml' => 'cbc:Telephone',
            'calculate' => 'sellerContactTelephone',
            'parent' => 'BG-6',
            'next' => 'BT-43',
            ),

        array('name' => 'Seller contact email address',          //PFLICHT
            'id' => 'BT-43',
            'xml' => 'cbc:ElectronicMail',
            'calculate' => 'sellerContactEmail',
            'parent' => 'BG-6',
            ),


        //Käufer
        array('name' => 'Buyer',          //PFLICHT
            'id' => 'BG-7',
            'xml' => 'cac:AccountingCustomerParty',
            'firstSub' => 'BG-7_SUB-1',
            'next' => 'PAR_BT-81',
            ),

            array('name' => 'Buyer Party',          //PFLICHT
                'id' => 'BG-7_SUB-1',
                'xml' => 'cac:Party',
                'parent' => 'BG-7',
                'firstSub' => 'BT-49',
                ),

                array('name' => 'Buyer electronic adress',          //PFLICHT
                    'id' => 'BT-49',
                    'source' => ['customerData', 'personalData', 'email'],
                    'xml' => 'cbc:EndpointID',
                    'xmlAttributes' => [['schemeID', 'buyerEletronicAdressScheme']],
                    'parent' => 'BG-7_SUB-1',
                    'next' => 'PAR_BT-44',
                    ),


            array('name' => '_Buyer Party Name',          //PFLICHT
                'id' => 'PAR_BT-44',
                'xml' => 'cac:PartyName',
                'parent' => 'BG-7_SUB-1',
                'firstSub' => 'BT-45',
                'next' => 'BG-8',
                ),

            array('name' => 'Buyer Trading Name',          //PFLICHT
                'id' => 'BT-45',
                'calculate' => 'buyerName',
                'xml' => 'cbc:Name',
                'parent' => 'PAR_BT-44',
                ),

        array('name' => 'Buyer postal adress',          //PFLICHT
            'id' => 'BG-8',
            'xml' => 'cac:PostalAddress',
            'parent' => 'BG-7_SUB-1',
            'firstSub' => 'BT-50',
            'next' => 'PAR_BT-47',
            ),

        array('name' => 'Buyer address line 1',          //OPTIONAL
            'id' => 'BT-50',
            'source' => ['customerData', 'personalData', 'street'],
            'xml' => 'cbc:StreetName',
            'parent' => 'BG-8',
            'next' => 'BT-52',
            ),

        array('name' => 'Buyer city',          //PFLICHT
            'id' => 'BT-52',
            'source' => ['customerData', 'personalData', 'city'],
            'xml' => 'cbc:CityName',
            'parent' => 'BG-8',
            'next' => 'BT-53',
            ),

        array('name' => 'Buyer post code',          //PFLICHT
            'id' => 'BT-53',
            'source' => ['customerData', 'personalData', 'postal'],
            'xml' => 'cbc:PostalZone',
            'parent' => 'BG-8',
            'next' => 'PAR_BT-55',
            ),

        array('name' => '_Buyer country code',          //PFLICHT
            'id' => 'PAR_BT-55',
            'xml' => 'cac:Country',
            'parent' => 'BG-8',
            'firstSub' => 'BT-55',
            ),

        array('name' => 'Buyer country code',          //PFLICHT
            'id' => 'BT-55',
            'source' => ['customerData', 'personalData_originalOptionValues', 'country'],
            'transform' => ['strtoupper'],
            'xml' => 'cbc:IdentificationCode',
            'parent' => 'PAR_BT-55',
            ),

        array('name' => '_Buyer legal registration identifier',          //OPTIONAL
            'id' => 'PAR_BT-47',
            'xml' => 'cac:PartyLegalEntity',
            'parent' => 'BG-7_SUB-1',
            'firstSub' => 'BT-44',
            'next' => 'BG-9',
            ),

        //Hier die gleichen Daten wie BT-45 ´Buyer Trading Name´
        array('name' => 'Buyer name',          //OPTIONAL
            'id' => 'BT-44',
            'calculate' => 'buyerName',
            'xml' => 'cbc:RegistrationName',
            'parent' => 'PAR_BT-47',
            'next' => 'BT-47',
            ),

        array('name' => 'Buyer legal registration identifier',          //OPTIONAL
            'id' => 'BT-47',
            'source' => ['customerData', 'personalData', 'company'],
            'xml' => 'cbc:CompanyID',
            'parent' => 'PAR_BT-47',
            ),

        array('name' => 'Buyer contact',          //OPTIONAL - WENN FEHLEND, WERDEN SIE BEI VALIDIERUNG BEMÄNGELT
            'id' => 'BG-9',
            'xml' => 'cac:Contact',
            'parent' => 'BG-7_SUB-1',
            'firstSub' => 'BT-56',
            ),

            array('name' => 'Buyer contact point',          //OPTIONAL
                'id' => 'BT-56',
                'calculate' => 'buyerName',
                'xml' => 'cbc:Name',
                'parent' => 'BG-9',
                'next' => 'BT-57',
                ),

            array('name' => 'Buyer contact telephone number',          //OPTIONAL
                'id' => 'BT-57',
                'source' => ['customerData', 'personalData', 'phone'],
                'xml' => 'cbc:Telephone',
                'parent' => 'BG-9',
                'next' => 'BT-58',
                ),

            array('name' => 'Buyer contact email address',          //OPTIONAL
                'id' => 'BT-58',
                'source' => ['customerData', 'personalData', 'email'],
                'xml' => 'cbc:ElectronicMail',
                'parent' => 'BG-9',
                ),

        //Zahlungsweise
        array('name' => 'Payment means',
            'id' => 'PAR_BT-81',
            'xml' => 'cac:PaymentMeans',
            'next' => 'BT-20',
            'firstSub' => 'BT-81'
            ),

            array('name' => 'Payment means type code',          //PFLICHT
                'id' => 'BT-81',
                'source' => ['paymentMethod_alias'],
                'transform' => ['payment2MeansTypeCode'],
                'xml' => 'cbc:PaymentMeansCode',
                //BT-82     -> getPaymentMeansText
                'xmlAttributes' => [['name', 'getPaymentMeansText']],
                'next' => 'BT-83',
                'parent' => 'PAR_BT-81'
                ),

            array('name' => 'Remittance Information',           //OPTIONAL
                'id' => 'BT-83',
                'calculate' => 'remittanceInformation',
                'xml' => 'cbc:PaymentID',
                'parent' => 'PAR_BT-81',
                'next' => 'PAR_BT-84',
                ),


            //Bankverbindung empfangender Händler
            array('name' => 'Payee Financial Account',
                'id' => 'PAR_BT-84',
                'condition' => 'accountDataByPayment',
                'xml' => 'cac:PayeeFinancialAccount',
                'parent' => 'PAR_BT-81',
                'firstSub' => 'BT-84'
                ),

                array('name' => 'Payment account identifier',       //PFLICHT
                    'id' => 'BT-84',
                    'source' => ['flexibleParams', 'iban'],
                    'xml' => 'cbc:ID',
                    'next' => 'BT-85',
                    'parent' => 'PAR_BT-84'
                    ),

                array('name' => 'Payment account name',             //OPTIONAL
                    'id' => 'BT-85',
                    'source' => ['flexibleParams', 'kontoinhaber'],
                    'xml' => 'cbc:Name',
                    'parent' => 'PAR_BT-84'
                    ),

        array('name' => 'Financial Institution Branch',
            'id' => 'PAR_BT-86',
            'xml' => 'cac:FinancialInstitutionBranch',
            'parent' => 'PAR_BT-84',
            'firstSub' => 'BT-86'
            ),

            array('name' => 'Payment service provider identifier',      //OPTIONAL
                'id' => 'BT-86',
                'source' => ['flexibleParams', 'bic'],
                'xml' => 'cbc:ID',
                'parent' => 'PAR_BT-86'
                ),

        //Zahlungsbedingungen wie Skonto
        array('name' => 'Payment Terms',     //OPTIONAL
            'id' => 'BT-20',
            'xml' => 'cac:PaymentTerms',
            'firstSub' => 'BT-20_SUB-1',
            'next' => 'BG-23',
            ),

        array('name' => 'Payment Terms Note',          //OPTIONAL
            'id' => 'BT-20_SUB-1',
//Eigentlich: Eine Textbeschreibung der Zahlungsbedingungen, die für den fälligen Zahlungsbetrag gelten (einschließlich
//Beschreibung möglicher Skontobedingungen).
            'source' => ['paymentMethod_infoAfterCheckout'],
            'transform' => ['replaceTags'],
            'xml' => 'cbc:Note',
            'parent' => 'BT-20'
            ),

        //Summe Steuern
        array('name' => 'Tax Total / VAT Breakdown',
            'id' => 'BG-23',
            'xml' => 'cac:TaxTotal',
            'firstSub' => 'BT-110',
            'next' => 'BG-22',
            ),

        array('name' => 'Invoice total VAT amount',          //OPTIONAL
//TODO: Klären: es könnte auch BT-111 sein
            'id' => 'BT-110',
            'source' => ['taxTotal'],
            'transform' => ['format_unitPriceAmount'],
            'xml' => 'cbc:TaxAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-23',
            'next' => 'PAR_BT-116',
            ),

        array('name' => 'Tax Subtotal',
            'id' => 'PAR_BT-116',
            'xml' => 'cac:TaxSubtotal',
            'parent' => 'BG-23',
            'firstSub' => 'BT-116',
            'repeat' => 'totalTaxedWith'
            ),

        array('name' => 'VAT category taxable amount',          //PFLICHT
            'id' => 'BT-116',
            'source' => ['totalTaxedWith', ['groupKey'], 'amountTaxedHerewith'],
            'xml' => 'cbc:TaxableAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'PAR_BT-116',
            'next' => 'BT-117',
            ),

        array('name' => 'VAT category tax amount',            //PFLICHT
            'id' => 'BT-117',
            'source' => ['tax', ['groupKey'], 'taxAmount'],
            'transform' => ['format_unitPriceAmount'],
            'xml' => 'cbc:TaxAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'PAR_BT-116',
            'next' => 'PAR_BT-118_SUB-1',
            ),

        array('name' => 'VAT category tax',
            'id' => 'PAR_BT-118_SUB-1',
            'xml' => 'cac:TaxCategory',
            'parent' => 'PAR_BT-116',
            'firstSub' => 'BT-118'
            ),


        array('name' => 'VAT category code',                //PFLICHT
            'id' => 'BT-118',
            'source' => ['tax', ['groupKey'], 'vatCategoryCode'],
            'xml' => 'cbc:ID',
            'parent' => 'PAR_BT-118_SUB-1',
            'next' => 'BT-119',
            ),

        array('name' => 'VAT category rate',                //PFLICHT
            'id' => 'BT-119',
            'source' => ['tax', ['groupKey'], 'taxRate'],
            'xml' => 'cbc:Percent',
            'parent' => 'PAR_BT-118_SUB-1',
            'next' => 'BT-118_SUB-1',
            ),

        //Nicht im PDF beschrieben
        array('name' => 'Tax category scheme',
            'id' => 'BT-118_SUB-1',
            'xml' => 'cac:TaxScheme',
            'parent' => 'PAR_BT-118_SUB-1',
            'firstSub' => 'BT-118_SUB-1_SUB-1'
            ),

        array('name' => 'Tax category scheme Id',                //PFLICHT
            'id' => 'BT-118_SUB-1_SUB-1',
            'transform' => ['taxSchemeVat'],
            'xml' => 'cbc:ID',
            'parent' => 'BT-118_SUB-1'
            ),


        //Gesamtsummen
        array('name' => 'Document Totals / Legal Monetary Total',                //PFLICHT
            'id' => 'BG-22',
            'xml' => 'cac:LegalMonetaryTotal',
            'firstSub' => 'BT-106',
            'next' => 'BG-25',
            ),

        array('name' => 'Sum of Invoice line net amount',                //PFLICHT
            'id' => 'BT-106',
            'source' => ['invoicedAmountNet'],
            'transform' => ['format_unitPriceAmount'],
            'xml' => 'cbc:LineExtensionAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-22',
            'next' => 'BT-109',
            ),

        array('name' => 'Invoice total amount without VAT',                //PFLICHT
            'id' => 'BT-109',
            'source' => ['invoicedAmountNet'],                      //invoicedAmountNet ist IMMER der Nettobetrag
            'transform' => ['format_unitPriceAmount'],
            'xml' => 'cbc:TaxExclusiveAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-22',
            'next' => 'BT-112',
            ),

        array('name' => 'Invoice total amount with VAT',                //PFLICHT
            'id' => 'BT-112',
            'source' => ['invoicedAmount'],                         //invoicedAmount ist IMMER der Bruttobetrag
            'transform' => ['format_unitPriceAmount'],
            'xml' => 'cbc:TaxInclusiveAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-22',
            'next' => 'BT-115',
            ),

//TODO: Haben wir Teil-Rechnungen ? Dann wären hier bereits gezahlte Beträge drin
        #array('name' => 'Paid amount',                //OPTIONAL
            #'id' => 'BT-113',
            #'source' => [],
            #'xml' => 'cbc:PrepaidAmount',
            #'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            #'parent' => 'BG-22'
            #),

        array('name' => 'Amount due for payment',                //PFLICHT
            'id' => 'BT-115',
            'source' => ['invoicedAmount'],
            'calculate' => 'amountDueForPayment',
            'xml' => 'cbc:PayableAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-22'
            ),


        // Invoice Line
        array('name' => 'Invoice Line',                //PFLICHT
            'id' => 'BG-25',
            'xml' => 'cac:InvoiceLine',
            'firstSub' => 'BT-126',
            'repeat' => 'items'
            ),

        array('name' => 'Invoice Line Identifier',                //PFLICHT
            'id' => 'BT-126',
            'source' => ['items', ['groupKey'], 'itemPosition'],
            'xml' => 'cbc:ID',
            'parent' => 'BG-25',
            'next' => 'BT-129',
            ),

        array('name' => 'Invoice Line Note',                //OPTIONAL
            'id' => 'BT-127',
            'source' => ['items', ['groupKey'], 'productTitle'],
            'xml' => 'cbc:Note',
            'parent' => 'BG-25'
            ),

        array('name' => 'Invoiced Quantity',                //OPTIONAL
            'id' => 'BT-129',
            'source' => ['items', ['groupKey'], 'quantity'],
            'transform' => ['format_unitPriceAmount', 0],
            'xml' => 'cbc:InvoicedQuantity',
            'xmlAttributes' => [['unitCode', 'getUnitCode']],
            'parent' => 'BG-25',
            'next' => 'BT-131',
            ),

        array('name' => 'Invoice line net amount',                //PFLICHT
            'id' => 'BT-131',
            'source' => ['items', ['groupKey'], 'priceCumulative'],
            'transform' => ['format_unitPriceAmount'],
            'xml' => 'cbc:LineExtensionAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-25',
            'next' => 'BG-31',
            ),

        //ITEM
        array('name' => 'Item Information',                //PFLICHT
            'id' => 'BG-31',
            'xml' => 'cac:Item',
            'firstSub' => 'BT-154',
            'parent' => 'BG-25',
            'next' => 'BG-29',
            ),

        array('name' => 'Item Description',                //OPTIONAL
            'id' => 'BT-154',
//TODO: vielleicht ausführlichere Beschreibung ?
            'source' => ['items', ['groupKey'], 'productTitle'],
            'xml' => 'cbc:Description',
            'parent' => 'BG-31',
            'next' => 'BT-153',
            ),

        array('name' => 'Item Name',                //PFLICHT
            'id' => 'BT-153',
            'source' => ['items', ['groupKey'], 'productTitle'],
            'xml' => 'cbc:Name',
            'parent' => 'BG-31',
            'next' => 'PAR_BT-155',
            ),

        array('name' => 'Sellers Item Identification',                //OPTIONAL
            'id' => 'PAR_BT-155',
            'xml' => 'cac:SellersItemIdentification',
            'firstSub' => 'BT-155',
            'parent' => 'BG-31',
            'next' => 'BG-30',
            ),

        array('name' => 'Item Sellers identifier',                //OPTIONAL
            'id' => 'BT-155',
//TODO: könnte productVariantID sein, aber auch productCartKey oder auch artNr
            'source' => ['items', ['groupKey'], 'productVariantID'],
            'xml' => 'cbc:ID',
            'parent' => 'PAR_BT-155'
            ),

        array('name' => 'Line VAT Information',                //OPTIONAL
            'id' => 'BG-30',
            'xml' => 'cac:ClassifiedTaxCategory',
            'firstSub' => 'BT-151',
            'parent' => 'BG-31'
            ),

        array('name' => 'Item Sellers identifier',                //OPTIONAL
            'id' => 'BT-151',
            'source' => ['items', ['groupKey'], 'vatCategoryCode'],
            'xml' => 'cbc:ID',
            'parent' => 'BG-30',
            'next' => 'BT-152',
            ),

        array('name' => 'Invoiced item VAT rate',                //PFLICHT
            'id' => 'BT-152',
            'source' => ['items', ['groupKey'], 'taxPercentage'],
            'xml' => 'cbc:Percent',
            'parent' => 'BG-30',
            'next' => 'BG-30_SUB-1',
            ),

        array('name' => 'Tax scheme',                   //OPTIONAL
            'id' => 'BG-30_SUB-1',
            'xml' => 'cac:TaxScheme',
            'parent' => 'BG-30',
            'firstSub' => 'BG-30_SUB-1_SUB-1'
            ),

        array('name' => 'Tax scheme Id',                //OPTIONAL
            'id' => 'BG-30_SUB-1_SUB-1',
            'transform' => ['taxSchemeVat'],
            'xml' => 'cbc:ID',
            'parent' => 'BG-30_SUB-1'
            ),

        //Price
        array('name' => 'Price Details',                   //PFLICHT
            'id' => 'BG-29',
            'xml' => 'cac:Price',
            'parent' => 'BG-25',
            'firstSub' => 'BT-146'
            ),

            array('name' => 'Item net price',                //PFLICHT
                'id' => 'BT-146',
                'source' => ['items', ['groupKey'], 'price'],
                'transform' => ['format_unitPriceAmount'],
                'xml' => 'cbc:PriceAmount',
                'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
                'parent' => 'BG-29',
                ),

        //Dieser Knoten wird von der Validierungsprüfung als ungültig gemeldet -> auskommentiert
        #array('name' => 'Item price discount',                //OPTIONAL
            #'id' => 'BT-147',
            #'source' => ['items'],
            #'transform' => 'calculateLineDiscount',
            #'xml' => 'cbc:amount',
            #'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            #'parent' => 'BG-29'
            #),

        //Dieser Knoten wird von der Validierungsprüfung als ungültig gemeldet -> auskommentiert
        #array('name' => 'Item gross price',                //OPTIONAL
            #'id' => 'BT-148',
            #'source' => ['items', ['groupKey'], 'price'],
            #'transform' => ['format_unitPriceAmount'],
            #'xml' => 'cbc:BaseAmount',
            #'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            #'parent' => 'BG-29'
            #),
    );

}