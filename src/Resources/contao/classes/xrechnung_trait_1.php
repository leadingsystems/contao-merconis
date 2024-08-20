<?php
namespace Merconis\Core;


abstract class xrechnung_trait_1
{
    const SETSADMIN = 'setadmin';           //Name des Mitglieds (Tabelle tl_member) welches Standard Sets anlegen darf
    const STANDARDSET = 'Standard';         //Name des Sets eines Setadmins welches als Standard funktionieren kann
    const TYPESTANDARD = 1;
    const TYPEUSER = 2;

}

abstract class xrechnung_trait_2
{
    const BT384 = 'Berichtigung';
    const BT381 = 'Gutschein/Gutschrift';         //Name des Sets eines Setadmins welches als Standard funktionieren kann
    const BT389 = 'Gutschrift nach UStG';
}



trait xrechnung_trait_func
{

    //Aufbau der Daten:
    //1:    name: Bezeichnung des Informations Elements aus PDF Dokumentation z.B. Invoice Number
    //2:    id: des Informations Elements    z.B. BT-2
    //          Bei Parent Elementen die nicht beschrieben sind: PAR_BT-81
    //          Bei Kind Elementen die nicht beschrieben sind:  BT-13_SUB-1
    //3:    source: Datenquelle bzw. Schlüsselname im arrOrder Array   z.B. orderNr
    //4:    transformation: Daten-Transformations-funktion  z.B. date('Ymd') für Zeitstempel
    //5:    tabs: String der die Einrückung im XML code bewirkt
    //5:    xml: der Key des XML Elements
    //6:    next: nächstes Informations Element bzw. Nachfolger
    //7:    parent: id des Eltern Elements z.B. BT-2
    //8:    specialfunc: Name einer speziellen Ablauf-Funktion
    public $listElementsTr = array(

        array('name' => 'customizationId',
            'id' => '',
            'transformation' => 'customizationId',
            'tabs' => '  ',
            'xml_alt' => '  <cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0</cbc:CustomizationID>',
            'xml' => 'cbc:CustomizationID',
            'next' => 'BT-1'),

        array('name' => 'Invoice Number',               //PFLICHT
            'id' => 'BT-1',
//FALSCH dürfte eher
            'source' => 'FEHLT',
            'tabs' => '  ',
            'xml' => 'cbc:ID',
            'next' => 'BT-2'),

        array('name' => 'Invoice issue Date',           //PFLICHT
            'id' => 'BT-2',
//wahrscheinlich falsch
            'source' => 'FEHLT',
            'transformation' => 'ts2Date',
            'tabs' => '  ',
            'xml' => 'cbc:IssueDate',
            'next' => 'BT-9'),

        array('name' => 'Payment Due Date',             //OPTIONAL
            'id' => 'BT-9',
            #'source' => 'FEHLT',
            'transformation' => 'ts2Date',
            'tabs' => '  ',
            'xml' => 'cbc:DueDate',
            'next' => 'BT-3'),

        array('name' => 'Invoice type code',        //PFLICHT
            'id' => 'BT-3',
            #'source' => 'FEHLT',
            'transformation' => 'invoiceTypeCode',
            'tabs' => '  ',
            'xml' => 'cbc:InvoiceTypeCode',
            'next' => 'BG-1'),

//TODO: sollen hier auch die weiteren notesLong oder freetext mit ausgegeben werden ? Dann gleich eine Gruppe machen
        array('name' => 'INVOICE NOTE',             //OPTIONAL
            'id' => 'BG-1',
            'source' => 'notesShort',
            'tabs' => '  ',
            'xml' => 'cbc:Note',
            'next' => 'BT-5'),

        array('name' => 'Invoice currency code',        //PFLICHT
            'id' => 'BT-5',
            'source' => 'currency',
            'tabs' => '  ',
            'xml' => 'cbc:DocumentCurrencyCode',
            'next' => 'BT-10'),

        array('name' => 'Buyer reference',              //PFLICHT
            'id' => 'BT-10',
            'source' => 'customerNr',
            'tabs' => '  ',
            'xml' => 'cbc:BuyerReference',
            'next' => 'BT-13'),

        //Bezug zu einem Auftrag
        array('name' => '_Order Reference ID',
            'id' => 'BT-13_SUB-1',
            'source' => 'orderNr',
            'tabs' => '    ',
            'xml' => 'cbc:ID',
            'parent' => 'BT-13'
            ),

        array('name' => 'Purchase order reference',     //OPTIONAL
            'id' => 'BT-13',
            //'source' => 'orderNr',
            'tabs' => '  ',
            'xml' => 'cac:OrderReference',
            'firstSub' => 'BT-13_SUB-1',
            'next' => 'PAR_BT-12'
            ),

        //Bezug zu einem Vertrag
        array('name' => '_Contract Document Reference',     //OPTIONAL
            'id' => 'PAR_BT-12',
            'tabs' => '  ',
            'xml' => 'cac:ContractDocumentReference',
            'firstSub' => 'BT-12',
            'next' => 'PAR_BT-81'
            ),

        array('name' => 'Contract Reference',          //OPTIONAL
            'id' => 'BT-12',
            'source' => '',
            'tabs' => '    ',
            'xml' => 'cbc:ID',
            'parent' => 'PAR_BT-12'
            ),

        //Zahlungsweise
        array('name' => 'Payment means',
            'id' => 'PAR_BT-81',
            'tabs' => '  ',
            'xml' => 'cac:PaymentMeans',
            'next' => 'BT-20',
            'firstSub' => 'BT-81'
            ),

        array('name' => 'Payment means type code',          //PFLICHT
            'id' => 'BT-81',
//NOCH KLÄREN: wie kommt man von unserem Payment Method Title zum richtigen type code UNTDID_4461_3.xlsx
            'source' => 'paymentMethod_title',
            'transformation' => 'payment2Means',
            'tabs' => '    ',
            'xml' => 'cbc:PaymentMeansCode',
            'next' => 'BT-83',
            'parent' => 'PAR_BT-81'
            ),

        #array('name' => 'Payment Id',
            #'id' => 'BT-81_UNK-1',
            #'source' => '',
            #'tabs' => '    ',
            #'xml' => 'cbc:PaymentID',
            #'next' => 'BT-82',
            #'parent' => 'PAR_BT-81'
            #),
//NOCH KLÄREN: bei Peppol
//  https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/tree/
// steht bei ´Remittance Information´ der Knoten ´cbc:PaymentID´
        array('name' => 'Remittance Information',           //OPTIONAL
            'id' => 'BT-83',
//NOCH KLÄREN: was soll hier rein, als Daten ? BT-83
            'source' => '',
            'tabs' => '    ',
            'xml' => 'cbc:PaymentID',
            'parent' => 'PAR_BT-81'
            ),

        array('name' => 'Payment means text',           //OPTIONAL
            'id' => 'BT-82',
            'source' => 'paymentMethod_title',
//NOCH KLÄREN: wie sieht der richtige XML Text zu diesem Knoten aus ?
            'tabs' => '    ',
            'xml' => 'cbc:PaymentMeansText',
            'parent' => 'PAR_BT-81'
            ),

        //Bankverbindung Zahlender
        array('name' => 'Payee Financial Account',
            'id' => 'PAR_BT-84',
            'tabs' => '    ',
            'xml' => 'cac:PayeeFinancialAccount',
            'parent' => 'PAR_BT-81',
            'firstSub' => 'BT-84'
            ),

        array('name' => 'Payment account identifier',       //PFLICHT
            'id' => 'BT-84',
//NOCH KLÄREN: Woher die Daten für IBAN
            'source' => '',
            'tabs' => '      ',
            'xml' => 'cbc:ID',
            'next' => 'BT-85',
            'parent' => 'PAR_BT-84'
            ),

        array('name' => 'Payment account name',             //OPTIONAL
            'id' => 'BT-85',
//NOCH KLÄREN: Woher die Daten für Kontoinhaber
            'source' => '',
            'tabs' => '      ',
            'xml' => 'cbc:Name',
            'parent' => 'PAR_BT-84'
            ),

        array('name' => 'Financial Institution Branch',
            'id' => 'PAR_BT-86',
            'source' => '',
            'tabs' => '      ',
            'xml' => 'cac:FinancialInstitutionBranch',
            'parent' => 'PAR_BT-84'
            ),

        array('name' => 'Payment service provider identifier',      //OPTIONAL
            'id' => 'BT-86',
            'source' => '',
            'tabs' => '        ',
            'xml' => 'cbc:ID',
            'parent' => 'PAR_BT-86'
            ),

        //Zahlungsbedingungen wie Skonto
        array('name' => 'Payment Terms',     //OPTIONAL
            'id' => 'BT-20',
            'tabs' => '  ',
            'xml' => 'cac:PaymentTerms',
            'firstSub' => 'BT-20_SUB-1',
            'next' => 'PAR_BT-110',
            ),

        array('name' => 'Payment Terms Note',          //OPTIONAL
            'id' => 'BT-20_SUB-1',
            'source' => 'FEHLT',
            'tabs' => '    ',
            'xml' => 'cbc:NOTE',
            'parent' => 'BT-20'
            ),

        //Summe Steuern
        array('name' => 'Tax Total',
            'id' => 'PAR_BT-110',
            'tabs' => '  ',
            'xml' => 'cac:TaxTotal',
            'firstSub' => 'BT-110'
            ),

        array('name' => 'Invoice total VAT amount',          //OPTIONAL
//TODO: Klären: es könnte auch BT-111 sein
            'id' => 'BT-110',
            'source' => 'taxTotal',
            'tabs' => '    ',
            'xml' => 'cbc:TaxAmount',
//TODO: Eigenschaften im XML Knoten umsetzen
'xmlAttr' => 'currencyID="EUR"',
            'parent' => 'PAR_BT-110',
            'next' => 'PAR_BT-116',
            ),

        array('name' => 'Tax Subtotal',
            //'id' => 'PAR_BT-110_SUB-1',
            'id' => 'PAR_BT-116',
            'tabs' => '    ',
            'xml' => 'cac:TaxSubtotal',
            'parent' => 'PAR_BT-110',
            //'firstSub' => 'PAR_BT-110-SUB-1-SUB-1'
            'firstSub' => 'BT-116',
            'specialfunc' => 'repeatForEveryTaxKey'
            ),

        array('name' => 'VAT category taxable amount',
            //'id' => 'PAR_BT-110-SUB-1-SUB-1',
            'id' => 'BT-116',
            'source' => 'totalTaxedWith',
            'transformation' => 'taxableAmountOfCategory',
            'tabs' => '      ',
            'xml' => 'cbc:TaxableAmount',
//TODO: Eigenschaften im XML Knoten umsetzen
'xmlAttr' => 'currencyID="EUR"',
            'parent' => 'PAR_BT-110_SUB-1'
            ),

        array('name' => 'VAT category tax amount',
            //'id' => 'PAR_BT-110-SUB-1-SUB-2',
            'id' => 'BT-117',
            'source' => 'tax',
            'transformation' => 'taxAmountOfCategory',
            'tabs' => '      ',
            'xml' => 'cbc:TaxAmount',
//TODO: Eigenschaften im XML Knoten umsetzen
'xmlAttr' => 'currencyID="EUR"',
            ),

        array('name' => 'VAT category tax',
            'id' => 'PAR_BT-110_SUB-1',
            'tabs' => '    ',
            'xml' => 'cac:TaxCategory',
            'parent' => 'PAR_BT-116',
            'firstSub' => 'BT-118'
            ),

        array('name' => 'VAT category code',
            'id' => 'BT-118',
            'source' => '',
            'transformation' => 'vatCategoryCode',
            'tabs' => '        ',
            'xml' => 'cbc:ID',
            'parent' => 'PAR_BT-110_SUB-1'
            ),
    );

}