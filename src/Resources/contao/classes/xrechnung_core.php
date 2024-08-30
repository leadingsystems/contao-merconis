<?php

namespace Merconis\Core;

/*
 * Diese Klasse bildet alle Regeln von XRechnung ab zur Erstellung einer XML Datei
 * Version: 3.0.2
 *
 * Der Einsatz von SimpleXML wurde verworfen, weil es im Ergebnis die Umbrüche und Tabs entfernt
 * Der Einsatz von DOMDocument wurde verworfen, weil die Beispiele nicht dynamisch genug waren (2 hardcodierte Schlüssel/Stufen)
 */

#use Psr\Container\ContainerInterface;
#use Symfony\Component\DependencyInjection\Container;

class xrechnung_core
{
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

        //Alternativer Weg mit Objekten
        $this->initInformationElements();
	}


    public function initInformationElements(): void
    {

        $this->conta = new \SplObjectStorage();
//TODO: dokumentieren
        foreach ($this->listElementsTr as $elem) {

            $IElem = $this->callIEbyId($elem['id']);

            if ($IElem) {
                $IElem->fillRemaining($elem);
            } else {
                $IElem = new xrechnung_element($elem);
                $this->conta->attach($IElem);
            }
            $IElem->setRef($this->arrOrder);



            if (isset($elem['parent']) && $elem['parent'] != '') {
                $parent = $this->callIEbyId($elem['parent']);

                if (!$parent) {
                    $parent = new xrechnung_element(
                        array('id' => $elem['parent'])
                    );
                }
                $parent->addSub($IElem);
                $this->conta->attach($parent);
                unset($parent);
            }

            //Beginnendes Element merken
            if ($this->first === null) {
                if (!$IElem->hasParent()) {
                    $this->first = $IElem;
                }
            }
        }
    }


    public function callIEbyId(string $elementId): ?xrechnung_element
    {
        if ($elementId != '') {
            foreach ($this->conta as $IElem ) {
                if ($IElem->getElementId() == $elementId ) {
                    return $IElem;
                }
            }
        }
        return null;
    }

    //Ansatz 2: jede Informationseinheit ist ein Objekt vom Typ "xrechnung_element" und die werden nacheinander abgearbeitet
    public function create(): string
    {
        $IElem = $this->first;

        ob_start();
        echo '<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
        echo '<ubl:Invoice xmlns:ubl="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2 http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd">' . "\r\n";

        do {
            if (!$IElem->hasParent()) {
                $IElem->setTabsOfParent('');
                $xml = $IElem->evalIE();
                echo $xml;
            }
            $nextElem = $IElem->getNextElement();
            $IElem = $this->callIEbyId($nextElem);

        } while ($IElem !== null);

        echo '</ubl:Invoice>'. "\r\n";
        $result = ob_get_clean();

        return $result;
    }


    /*  Zusätzliche Werte für die Rechnung, die nicht in der Bestellung stehen
     *  können hier nachträglich hinzugefügt werden
     *  Z.B. MessageCounterNr
     */
    public function setAdditionalData(array $data): xrechnung_core
    {
        foreach ($data as $key => $value) {
            $this->arrOrder[$key] = $value;
        }
        return $this;
    }
}
