<?php

namespace Merconis\Core;

/*
 * Diese Klasse bildet alle Regeln von XRechnung ab zur Erstellung einer XML Datei
 * Version: 3.0.2
 *
 * Der Einsatz von SimpleXML wurde verworfen, weil es im Ergebnis die Umbrüche und Tabs entfernt
 * Der Einsatz von DOMDocument wurde verworfen, weil die Beispiele nicht dynamisch genug waren (2 hardcodierte Schlüssel/Stufen)
 */

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;

class xrechnung_core
    #implements ContainerInterface

    # extends \Controller
    #extends \Merconis\Core\xrechnung_trait_1
    #extends \Merconis\Core\xrechnung_trait_2
{
     # use \Merconis\Core\xrechnung_trait_1;

    use \Merconis\Core\xrechnung_trait_func;


    private $arrOrder = array();
    private $res = '';                          //der fertige XML String
    private $conta = null;                       //Container für alle Einzelobjekte
    private $first = null;                      //erstes element (Ansatz 2)


    /*
     * The constructor function takes the order array and the messages counter nr as arguments
     */
	public function __construct($arrOrder = array())
    {
        $this->arrOrder = $arrOrder;
        #$this->xmltest();
        #$this->domtest();

        //Alternativer Weg mit Objekten
        $this->initInformationElements();
	}


    public function initInformationElements()
    {
/*
        $listElements =
            array(
                array('customizationId', '', '', '  <cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0</cbc:CustomizationID>', 'invoiceNumber'),
                array('invoiceNumber', 'BT-1', 'orderNr', '  <cbc:ID>[DATA]</cbc:ID>', 'issueDate'),
                array('issueDate', 'BT-2', 'orderDateUnixTimestamp', '  <cbc:IssueDate>[DATA]</cbc:IssueDate>', 'dueDate'),
            );
*/
        $this->conta = new \SplObjectStorage();

        foreach ($this->listElementsTr as $elem) {

            $infElem = new xrechnung_element($elem);
            $infElem->setRef($this->arrOrder);

            $this->first = ($this->first === null) ? $infElem : $this->first;

            $this->conta->attach($infElem);
        }
    }


    public function callIEbyId(string $elementId): ?xrechnung_element
    {
        if ($elementId == '') {
            return null;
        }

        foreach ($this->conta as $myobj ) {
            if ($myobj->getElementId() == $elementId ) {
                return $myobj;
            }
        }
        return null;
    }

    //Ansatz 2: jede Informationseinheit ist ein Objekt vom Typ "xrechnung_element" und die werden nacheinander abgearbeitet
    public function create()
    {
        $IElem = $this->first;

        ob_start();
        echo '<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
        echo '<ubl:Invoice xmlns:ubl="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2 http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd">' . "\r\n";

        do {
            $xml = $IElem->evalIE();
            echo $xml;

            $nextElem = $IElem->getNextElement();

            $IElem = $this->callIEbyId($nextElem);


        } while ($IElem !== null);

        echo '</ubl:Invoice>'. "\r\n";
        $result = ob_get_clean();
    }



    //Ansatz 1: jede Informationseinheit läuft in einer eigenen Funktion und die werden nacheinander aufgerufen
    public function create_1()
    {
        ob_start();
        echo '<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
        echo '<ubl:Invoice xmlns:ubl="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2 http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd">' . "\r\n";

        $this->customizationId();
        $this->profileId();
        $this->onlyId();
        $this->invoiceIssueDate();
        $this->invoiceDueDate();
        $this->invoiceTypeCode();


        $this->note();
        $this->documentCurrencyCode();
        $this->buyerReference();
        $this->orderReference();

        # hier noch einige andere Knoten

        $this->invoiceLine();


        echo '</ubl:Invoice>'. "\r\n";
        $result = ob_get_clean();

        return $result;
    }


    private function customizationId()
    {
        $customizationId = 'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0';
?>
  <cbc:CustomizationID><?= $customizationId ?></cbc:CustomizationID>
<?php
    }

    private function profileId()
    {
        $profileId = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
?>
  <cbc:ProfileID><?= $profileId ?></cbc:ProfileID>
<?php
    }

    private function onlyId()
    {
        $id = '1234';
?>
  <cbc:ID><?= $id ?></cbc:ID>
<?php
    }

    private function invoiceTypeCode()
    {
        $typeCode = 380;
?>
  <cbc:InvoiceTypeCode><?= $typeCode ?></cbc:InvoiceTypeCode>
<?php
    }

    private function invoiceIssueDate()
    {
        $issueDate = '2019-10-15';
?>
  <cbc:IssueDate><?= $issueDate ?></cbc:IssueDate>
<?php
    }

    private function invoiceDueDate()
    {
        $dueDate = '2019-10-15';
?>
  <cbc:DueDate><?= $dueDate ?></cbc:DueDate>
<?php
    }

    private function note()
    {
        $note = 'test';
?>
  <cbc:Note><?= $note ?></cbc:Note>
<?php
    }

    private function documentCurrencyCode()
    {
        $currency = 'EUR';
?>
  <cbc:DocumentCurrencyCode><?= $currency ?></cbc:DocumentCurrencyCode>
<?php
    }

    private function buyerReference()
    {
        $buyerReference = '1234';
?>
  <cbc:BuyerReference><?= $buyerReference ?></cbc:BuyerReference>
<?php
    }

    private function orderReference()
    {
        $orderReference = '1234';
?>
  <cac:OrderReference>
    <cbc:ID><?= $orderReference ?></cbc:ID>
  </cac:OrderReference>
<?php
    }


    private function invoiceLine()
    {
        $invoiceLineId = '0';

        $taxCategory = eTaxCategories::STANDARDRATE;
                        #eTaxCategories::ZERORATEDGOODS;

?>
  <cac:InvoiceLine>
    <cbc:ID><?=$invoiceLineId ?></cbc:ID>
    <cbc:InvoicedQuantity unitCode="XPP">0</cbc:InvoicedQuantity>
    <cbc:LineExtensionAmount currencyID="EUR">0.00</cbc:LineExtensionAmount>
<?php
        foreach ($this->arrOrder['items'] as $item ) {
?>
    <cac:Item>
      <cbc:Description><?= $item['extendedInfo']['_shortDescription'] ?></cbc:Description>
      <cbc:Name>Erste Position</cbc:Name>
      <cac:SellersItemIdentification>
        <cbc:ID><?= $taxCategory ?></cbc:ID>
      </cac:SellersItemIdentification>
      <cac:ClassifiedTaxCategory>
        <cbc:ID><?= $item['artNr'] ?></cbc:ID>
        <cbc:Percent><?= $item['taxPercentage'] ?></cbc:Percent>
        <cac:TaxScheme>
          <cbc:ID>VAT</cbc:ID>
        </cac:TaxScheme>
      </cac:ClassifiedTaxCategory>
    </cac:Item>
<?php
        }
?>
    <cac:Price>
      <cbc:PriceAmount currencyID="EUR">10000.20</cbc:PriceAmount>
    </cac:Price>
  </cac:InvoiceLine>
<?php
    }





}

abstract class eTaxCategories
{
    const STANDARDRATE = 'S';
    const ZERORATEDGOODS = 'Z';
}


/*          Erst in 8.2 gestattet
trait xrechnung_trait_test1
{
    private const SETSADMIN = 'setadmin';           //Name des Mitglieds (Tabelle tl_member) welches Standard Sets anlegen darf
    const STANDARDSET = 'Standard';         //Name des Sets eines Setadmins welches als Standard funktionieren kann
    const TYPESTANDARD = 1;
    const TYPEUSER = 2;

}
*/



/*
private function domtest()
    {

        $input_array = array(
            'article' => array(
                array(
                    'title' => 'Favorite Star Rating with jQuery',
                    'link' => 'https://phppot.com/jquery/dynamic-star-rating-with-php-and-jquery/',
                    'description' => 'Doing favorite star rating using jQuery Displays HTML stars.'
                ),
                array(
                    'title' => 'PHP RSS Feed Read and List',
                    'link' => 'https://phppot.com/php/php-simplexml-parser/',
                    'description' => 'simplexml_load_file() function is used for reading data from XML.'
                )
            )
        );

        $xml = new \DOMDocument();

        $rootNode = $xml->appendChild($xml->createElement("items"));

        foreach ($input_array['article'] as $article) {
            if (! empty($article)) {
                $itemNode = $rootNode->appendChild($xml->createElement('item'));
                foreach ($article as $k => $v) {
                    $itemNode->appendChild($xml->createElement($k, $v));
                }
            }
        }

        $xml->formatOutput = true;

        $res =  $xml->saveXML();

return;
        $backup_file_name = 'file_backup_' . time() . '.xml';
        $xml->save($backup_file_name);

        header('Content-Description: File Transfer');
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename=' . basename($backup_file_name));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($backup_file_name));
        ob_clean();
        flush();
        readfile($backup_file_name);
        exec('rm ' . $backup_file_name);

    }

    // function defination to convert array to xml
    function array_to_xml( $data, &$xml_data ) {
        foreach( $data as $key => $value ) {
            if( is_array($value) ) {
                if( is_numeric($key) ){
                    $key = 'item'.$key; //dealing with <0/>..<n/> issues
                }
                $subnode = $xml_data->addChild($key);
                $this->array_to_xml($value, $subnode);
            } else {
                $xml_data->addChild("$key",htmlspecialchars("$value"));
            }
         }
    }

    private function xmltest()
    {
        // initializing or creating array
        #$data = array('total_stud' => 500);
        $data = array (
          'bla' => 'blub',
          'foo' => 'bar',
          'another_array' => array (
            'stack' => 'overflow',
          ),
        );

        // creating object of SimpleXMLElement
        $xml_data = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');

        // function call to convert array to xml
        $this->array_to_xml($data,$xml_data);


        #$res = $xml_data->__toString();
        $res2 = $xml_data->asXML();

        #print $xml_data->asXML();

        //saving generated xml file;
        $result = $xml_data->asXML('files/merconisfiles/dynamicAttachmentFiles/generatedFiles/invoice_test.xml');

    }
*/