<?php
namespace Merconis\Core;



abstract class xrechnung_trait_2
{
    const BT384 = 'Berichtigung';
    const BT381 = 'Gutschein/Gutschrift';
    const BT389 = 'Gutschrift nach UStG';
}



trait xrechnung_trait_func
{

    //Aufbau der Daten:
    //1:    name: Bezeichnung des Informations Elements aus PDF Dokumentation z.B. Invoice Number
    //2:    id: des Informations Elements    z.B. BT-2
    //          Bei Parent Elementen die nicht beschrieben sind: PAR_BT-81
    //          Bei Kind Elementen die nicht beschrieben sind:  BT-13_SUB-1
    //          Jede weitere Stufe erhält weiter SUB Suffixe
    //          Völlig unbekannte Knoten erhalten
    //3.1:  source: Datenquell bzw. Schlüsselname im arrOrder Array, wenn es um einen tiefer gelegenen Knoten geht z.B. items[1][itemPosition]
    //          Wenn der Schlüssel mit @ beginnt, dann ist ein Wert aus dem Array "additionalParams" gemeint
    //4:    transform: Daten-transforms-funktion  z.B. date('Ymd') für Zeitstempel
    //:     calculate: Berechnungs-funktion z.B. amountDueForPayment für Berechnung von Restbeträgen (wenn source als
    //              Quell-Feld Angabe nicht ausreicht)
    //5:    xml: der Key des XML Elements
    //:     xmlAttributes: array für Eigenschaften innerhalb eines XML Tags. Jedes Attribut hat ein Array mit 2 Elementen
    //              Erstes element ist der Name z.B. currencyID="EUR".
    //              Das Zweite sind die Daten für das ein entsprechender Funktionsname
    //              angegeben wird
    //      firstSub: erstes Child-Element von Gruppenelementen mit dem begonnen werden soll.
    //6:    next: nächstes Informations Element bzw. Nachfolger
    //7:    parent: id des Eltern Elements z.B. BT-2
    //8:    repeat: das aktuelle Element wird wiederholt und zwar für jeden Key der unterhalb des angegebenen Keys in arrOrder steht
    //              Bsp: ´items´. Hier stehen mehrere Ids (für jeden Artikel einen). Somit wird das ganze InformationElement
    //              für jedes Item (Bestellposition) wiederholt
    public $listElementsTr = array(

        array('name' => 'customizationId',
            'id' => '',
            'transform' => 'customizationId',
            'xml' => 'cbc:CustomizationID',
            'next' => 'BT-1'),

        array('name' => 'Invoice Number',               //PFLICHT
            'id' => 'BT-1',
            'source' => ['messageCounterNr'],
            'xml' => 'cbc:ID',
            'next' => 'BT-2'
#'next' => 'BG-22'
        ),


        array('name' => 'Invoice issue Date',           //PFLICHT
            'id' => 'BT-2',
//wahrscheinlich falsch - es müsste das Ausstellungsdatum der Rechnung sein
            #'source' => ['orderDateUnixTimestamp'],
            #'transform' => 'ts2Date',
            'calculate' => 'currentDate',
            'xml' => 'cbc:IssueDate',
            'next' => 'BT-9'),

        array('name' => 'Payment Due Date',             //OPTIONAL
            'id' => 'BT-9',
//Fälligkeitsdatum des Rechnungsbetrages, FEHLT
            'source' => ['tstamp'],
            'transform' => 'ts2Date',
            'xml' => 'cbc:DueDate',
            'next' => 'BT-3'),

        array('name' => 'Invoice type code',        //PFLICHT
            'id' => 'BT-3',
            'transform' => 'invoiceTypeCode',
            'xml' => 'cbc:InvoiceTypeCode',
            'next' => 'BG-1'),

//TODO: sollen hier auch die weiteren notesLong oder freetext mit ausgegeben werden ? Dann gleich eine Gruppe machen
        array('name' => 'INVOICE NOTE',             //OPTIONAL
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

        //Bezug zu einem Auftrag
        array('name' => '_Order Reference ID',
            'id' => 'BT-13_SUB-1',
            'source' => ['orderNr'],
            'xml' => 'cbc:ID',
            'parent' => 'BT-13'
            ),

        array('name' => 'Purchase order reference',     //OPTIONAL
            'id' => 'BT-13',
            'source' => [],
            'xml' => 'cac:OrderReference',
            'firstSub' => 'BT-13_SUB-1',
            'next' => 'PAR_BT-12'
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
            'source' => [],
            'xml' => 'cbc:ID',
            'parent' => 'PAR_BT-12'
            ),

        //Bezug zu einem Projekt
        array('name' => '_Project reference',     //OPTIONAL
            'id' => 'PAR_BT-11',
            'xml' => 'cac:ProjectReference',
            'firstSub' => 'BT-11',
            'next' => 'BG-4'
#'next' => 'BG-7'
            ),

        array('name' => 'Project reference',          //OPTIONAL
            'id' => 'BT-11',
//TODO: haben wir Bezug zu Projekten ?
            'source' => [],
'calculate' => 'getPaymentMeansText',
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
            #'source' => [],
            'xml' => 'cbc:EndpointID',
            'xmlAttributes' => [['schemeID', 'sellerEletronicAdressScheme']],
            'calculate' => 'sellerEletronicAdress',
            'parent' => 'BG-4_SUB-1',
'next' => 'BG-4_SUB-1',
            ),

        array('name' => 'Seller postal adress',          //PFLICHT
            'id' => 'BG-5',
            'xml' => 'cac:PostalAddress',
            'parent' => 'BG-4_SUB-1',
            'firstSub' => 'BT-35',
            'next' => 'PAR_BT-30',
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
            #'next' => 'PAR_BT-81',
            ),

        array('name' => 'Seller country code',          //PFLICHT
            'id' => 'BT-40',
            'xml' => 'cac:Country',
'calculate' => 'sellerCountryCode',
            'parent' => 'PAR_BT-40',
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
            'next' => 'BG-8',
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
            #'next' => 'PAR_BT-81',
            ),

        array('name' => 'Buyer country code',          //PFLICHT
            'id' => 'BT-55',
            'source' => ['customerData', 'personalData', 'country'],
            'transform' => 'countryName2CountryCode',
            'xml' => 'cac:Country',
            'parent' => 'PAR_BT-55',
            ),

        array('name' => '_Buyer legal registration identifier',          //OPTIONAL
            'id' => 'PAR_BT-47',
            'xml' => 'cac:PartyLegalEntity',
            'parent' => 'BG-7_SUB-1',
            'firstSub' => 'BT-47',
            'next' => 'BG-9',
            ),

        array('name' => 'Buyer legal registration identifier',          //OPTIONAL
            'id' => 'BT-47',
            'source' => ['customerData', 'personalData', 'company'],
            'xml' => 'cbc:RegistrationName',
            'parent' => 'PAR_BT-47',
            ),


        array('name' => 'Buyer contact',          //OPTIONAL
            'id' => 'BG-9',
            'xml' => 'cac:Contact',
            'parent' => 'BG-7_SUB-1',
            'firstSub' => 'BT-56',
            ),

        array('name' => 'Buyer contact point',          //OPTIONAL
            'id' => 'BT-56',
            'source' => '',
            'xml' => 'cbc:Name',
            'parent' => 'BG-9',
            'next' => 'BT-57',
            ),

        array('name' => 'Buyer contact telephone number',          //OPTIONAL
            'id' => 'BT-57',
            'source' => '',
            'xml' => 'cbc:Telephone',
            'parent' => 'BG-9',
            'next' => 'BT-58',
            ),

        array('name' => 'Buyer contact email address',          //OPTIONAL
            'id' => 'BT-58',
            'source' => '',
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
//NOCH KLÄREN: wie kommt man von unserem Payment Method Title zum richtigen type code UNTDID_4461_3.xlsx
            'source' => ['paymentMethod_title'],
            'transform' => 'payment2Means',
            'xml' => 'cbc:PaymentMeansCode',
            //BT-82     -> getPaymentMeansText
            'xmlAttributes' => [['name', 'getPaymentMeansText']],
            'next' => 'BT-83',
            'parent' => 'PAR_BT-81'
            ),

//NOCH KLÄREN: bei Peppol
//  https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/tree/
// steht bei ´Remittance Information´ der Knoten ´cbc:PaymentID´
        array('name' => 'Remittance Information',           //OPTIONAL
            'id' => 'BT-83',
//NOCH KLÄREN: was soll hier rein, als Daten ?
            'source' => [],
            'xml' => 'cbc:PaymentID',
            'parent' => 'PAR_BT-81'
            ),


        //Bankverbindung Zahlender
        array('name' => 'Payee Financial Account',
            'id' => 'PAR_BT-84',
            'xml' => 'cac:PayeeFinancialAccount',
            'parent' => 'PAR_BT-81',
            'firstSub' => 'BT-84'
            ),

        array('name' => 'Payment account identifier',       //PFLICHT
            'id' => 'BT-84',
//NOCH KLÄREN: Woher die Daten für IBAN
            'source' => [],
            'xml' => 'cbc:ID',
            'next' => 'BT-85',
            'parent' => 'PAR_BT-84'
            ),

        array('name' => 'Payment account name',             //OPTIONAL
            'id' => 'BT-85',
//NOCH KLÄREN: Woher die Daten für Kontoinhaber
            'source' => [],
            'xml' => 'cbc:Name',
            'parent' => 'PAR_BT-84'
            ),

        array('name' => 'Financial Institution Branch',
            'id' => 'PAR_BT-86',
            'source' => [],
            'xml' => 'cac:FinancialInstitutionBranch',
            'parent' => 'PAR_BT-84'
            ),

        array('name' => 'Payment service provider identifier',      //OPTIONAL
            'id' => 'BT-86',
            'source' => [],
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
            'xml' => 'cbc:NOTE',
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
            'source' => ['totalTaxedWith', '@groupKey', 'amountTaxedHerewith'],
            'xml' => 'cbc:TaxableAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'PAR_BT-116'
            ),

        array('name' => 'VAT category tax amount',            //PFLICHT
            'id' => 'BT-117',
            'source' => ['tax', '@groupKey', 'taxAmount'],
            'xml' => 'cbc:TaxAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'PAR_BT-116'
            ),

        array('name' => 'VAT category tax',
            'id' => 'PAR_BT-118_SUB-1',
            'xml' => 'cac:TaxCategory',
            'parent' => 'PAR_BT-116',
            'firstSub' => 'BT-118'
            ),


        array('name' => 'VAT category code',                //PFLICHT
            'id' => 'BT-118',
            'source' => ['tax', '@groupKey', 'vatCategoryCode'],
            'xml' => 'cbc:ID',
            'parent' => 'PAR_BT-118_SUB-1'
            ),

        array('name' => 'VAT category rate',                //PFLICHT
            'id' => 'BT-119',
            'source' => ['tax', '@groupKey', 'taxRate'],
            'xml' => 'cbc:Percent',
            'parent' => 'PAR_BT-118_SUB-1'
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
            'source' => [],
            'transform' => 'taxSchemeVat',
            'xml' => 'cbc:ID',
            'parent' => 'BT-118_SUB-1'
            ),


        // DOCUMENT TOTALS
        array('name' => 'Document Totals / Legal Monetary Total',                //PFLICHT
            'id' => 'BG-22',
            'xml' => 'cac:LegalMonetaryTotal',
            'firstSub' => 'BT-106',
            'next' => 'BG-25',
            ),

        array('name' => 'Sum of Invoice line net amount',                //PFLICHT
            'id' => 'BT-106',
            'source' => ['invoicedAmountNet'],
            'xml' => 'cbc:LineExtensionAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-22'
            ),

        array('name' => 'Invoice total amount without VAT',                //PFLICHT
            'id' => 'BT-109',
//TODO: hier muss vermutlich entweder gerechnet werden oder es kommt ein brauchbarer Wert
            'source' => ['invoicedAmountNet'],
            'xml' => 'cbc:TaxExclusiveAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-22'
            ),

        array('name' => 'Invoice total amount with VAT',                //PFLICHT
            'id' => 'BT-112',
//TODO: könnte "invoicedAmount" sein oder "total"
            'source' => ['invoicedAmount'],
            'xml' => 'cbc:TaxInclusiveAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-22'
            ),

        array('name' => 'Paid amount',                //OPTIONAL
            'id' => 'BT-113',
//TODO: Haben wir Teil-Rechnungen ? Dann wären hier bereits gezahlte Beträge drin
            'source' => [],
            'xml' => 'cbc:PrepaidAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-22'
            ),

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
            'source' => ['items', '@groupKey', 'itemPosition'],
            'xml' => 'cbc:ID',
            'parent' => 'BG-25'
            ),

        array('name' => 'Invoice Line Note',                //OPTIONAL
            'id' => 'BT-127',
            'source' => ['items', '@groupKey', 'productTitle'],
            'xml' => 'cbc:Note',
            'parent' => 'BG-25'
            ),

        array('name' => 'Invoiced Quantity',                //OPTIONAL
            'id' => 'BT-129',
            'source' => ['items', '@groupKey', 'quantity'],
            'xml' => 'cbc:InvoicedQuantity',
            'xmlAttributes' => [['unitCode', 'getUnitCode']],
            'parent' => 'BG-25'
            ),

        array('name' => 'Invoice line net amount',                //PFLICHT
            'id' => 'BT-131',
            'source' => ['items'],
            'transform' => 'calculateLineNetPrice',
            'xml' => 'cbc:LineExtensionAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-25'
            ),

        //ITEM
        array('name' => 'Item Information',                //PFLICHT
            'id' => 'BG-31',
            'xml' => 'cac:Item',
            'firstSub' => 'BT-126',
            'parent' => 'BG-25'
            ),

        array('name' => 'Item Name',                //PFLICHT
            'id' => 'BT-153',
            'source' => ['items', '@groupKey', 'productTitle'],
            'xml' => 'cbc:Name',
            'parent' => 'BG-31'
            ),

        array('name' => 'Item Description',                //OPTIONAL
            'id' => 'BT-154',
//TODO: vielleicht ausführlichere Beschreibung ?
            'source' => ['items', '@groupKey', 'productTitle'],
            'xml' => 'cbc:Description',
            'parent' => 'BG-31'
            ),

        array('name' => 'Sellers Item Identification',                //OPTIONAL
            'id' => 'PAR_BT-155',
            'xml' => 'cac:SellersItemIdentification',
            'firstSub' => 'BT-155',
            'parent' => 'BG-31'
            ),

        array('name' => 'Item Sellers identifier',                //OPTIONAL
            'id' => 'BT-155',
//TODO: könnte productVariantID sein, aber auch productCartKey oder auch artNr
            'source' => ['items', '@groupKey', 'productVariantID'],
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
            #'transform' => 'vatCategoryCode',
            'source' => ['items', '@groupKey', 'vatCategoryCode'],
            'xml' => 'cbc:ID',
            'parent' => 'BG-30'
            ),

        array('name' => 'Invoiced item VAT rate',                //PFLICHT
            'id' => 'BT-152',
            'source' => ['items', '@groupKey', 'taxPercentage'],
            'xml' => 'cbc:Percent',
            'parent' => 'BG-30'
            ),

        array('name' => 'Tax scheme',                   //OPTIONAL
            'id' => 'BG-30_SUB-1',
            'xml' => 'cac:TaxScheme',
            'parent' => 'BG-30',
            'firstSub' => 'BG-30_SUB-1_SUB-1'
            ),

        array('name' => 'Tax scheme Id',                //OPTIONAL
            'id' => 'BG-30_SUB-1_SUB-1',
            'source' => [],
            'transform' => 'taxSchemeVat',
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
            'source' => ['items'],
            'transform' => 'calculateLineNetPrice',
            'xml' => 'cbc:PriceAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-29'
            ),

        array('name' => 'Item price discount',                //OPTIONAL
            'id' => 'BT-147',
            'source' => ['items'],
            'transform' => 'calculateLineDiscount',
            'xml' => 'cbc:amount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-29'
            ),

        array('name' => 'Item gross price',                //OPTIONAL
            'id' => 'BT-148',
            'source' => ['items', '@groupKey', 'price'],
            'xml' => 'cbc:BaseAmount',
            'xmlAttributes' => [['currencyID', 'getCurrencyCode']],
            'parent' => 'BG-29'
            ),
    );

}