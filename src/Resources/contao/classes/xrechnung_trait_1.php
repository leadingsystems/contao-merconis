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
    //1:
    public $listElementsTr = array(
        array('customizationId', '', '', '  <cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0</cbc:CustomizationID>', 'invoiceNumber'),
        array('invoice Number', 'BT-1', 'orderNr', '  <cbc:ID>[DATA]</cbc:ID>', 'issueDate'),
        array('issueDate', 'BT-2', 'orderDateUnixTimestamp', '  <cbc:IssueDate>[DATA]</cbc:IssueDate>', 'dueDate'),


    );

    public function tes1()
    {
        return 'test1';
    }


}