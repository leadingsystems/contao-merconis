<?php
namespace Merconis\Core;


#trait xrechnung_trait_1            // Erst in 8.2 gestattet
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
    //1:    Name des Informations Elements  z.B. Invoice Number
    //2:    ID des Informations Elements    z.B. BT-2
    //3:    Datenquelle bzw. Schlüsselname im arrOrder Array   z.B. orderNr
    //4:    Daten-Transformations-funktion  z.B. date('Ymd') für Zeitstempel
    //5:    xml code mit Einrückung und evtl. Data Platzhalter
    //6:    nächstes Informations Element bzw. Nachfolger
    public $listElementsTr = array(
        array('name' => 'customizationId',
            'id' => '',
            'xml' => '  <cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0</cbc:CustomizationID>',
            'next' => 'BT-1'),
        array('name' => 'Invoice Number',
            'id' => 'BT-1',
//FALSCH dürfte eher
            'source' => 'orderNr',
            'xml' => '  <cbc:ID>[DATA]</cbc:ID>',
            'next' => 'BT-2'),
        array('name' => 'issueDate',
            'id' => 'BT-2',
//wahrschenlich falsch
            'source' => 'orderDateUnixTimestamp',
            'transformation' => 'ts2Date',
            'xml' => '  <cbc:IssueDate>[DATA]</cbc:IssueDate>',
            'next' => 'BT-9'),

        array('name' => 'Payment Due Date',
            'id' => 'BT-9',
//FEHLT
            #'source' => 'FEHLT',
            'transformation' => 'ts2Date',
            'xml' => '  <cbc:DueDate>[DATA]</cbc:DueDate>',
            'next' => 'BT-3'),
        array('name' => 'Invoice type code',
            'id' => 'BT-3',
//FEHLT
            #'source' => 'FEHLT',
            'xml' => '  <cbc:InvoiceTypeCode>[DATA]</cbc:InvoiceTypeCode>',
            'next' => 'BG-1'),
        array('name' => 'INVOICE NOTE',
            'id' => 'BG-1',
            'source' => 'notesShort',
            'xml' => '  <cbc:Note>[DATA]</cbc:Note>',
            'next' => 'BT-5'),
        array('name' => 'Invoice currency code',
            'id' => 'BT-5',
            'source' => 'currency',
            'xml' => '  <cbc:DocumentCurrencyCode>[DATA]</cbc:DocumentCurrencyCode>',
            'next' => 'BT-10'),
        array('name' => 'Buyer reference',
            'id' => 'BT-10',
            'source' => 'customerNr',
            'xml' => '  <cbc:BuyerReference>[DATA]</cbc:BuyerReference>',
            'next' => 'BT-13'),


        array('name' => '_Order Reference ID',
            'id' => 'BT-13_SUB-1',
            'source' => 'orderNr',
            'xml' => '    <cbc:ID>MyOrderRef</cbc:ID>',
            'parent' => ''
            ),


        array('name' => 'Purchase order reference',
            'id' => 'BT-13',
            //'source' => 'orderNr',
            'xml' => '  <cac:OrderReference>[DATA]</cac:OrderReference>',
            'next' => 'dueDate',
            'firstSub' => 'BT-13_SUB-1'
            ),


    );

    public function tes1()
    {
        return 'test1';
    }


}