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
    use \Merconis\Core\xrechnung_elementData;


    /*  Array containing all the data of a Merconis order
     *  @var    array
     */
    private $arrOrder = array();

    /*  contains all information elements
     *  @var    SplObjectStorage
     */
    private $container = null;

    /*  the first information element with which the evaluation begins
     *  @var    xrechnung_element
     */
    private $first = null;                      //erstes element (Ansatz 2)


    /*
     *  The constructor function takes the order array and the messages counter nr as arguments
     *
     *  @param  array   $arrOrder       Array containing all the data of a Merconis order
     */
	public function __construct($arrOrder = array())
    {
        $this->arrOrder = $arrOrder;
        $this->initInformationElements();
	}


    /*  For each entry from the xRechnung data object, an object of type InformationElement
     *  is created, filled with values ​​and assigned to the container. Child
     *  elements are assigned to their parent elements.
     *  Each element receives a reference (not a copy) to the arrOrder array.
     *  The maintenance of the information elements should not be dependent on their position
     *  or order in the php file. But via their firstSub or Parent properties
     */
    public function initInformationElements(): void
    {

        $this->container = new \SplObjectStorage();
        foreach ($this->listElements as $element) {

            $IElem = $this->callIEbyId($element['id']);

            if ($IElem) {
                $IElem->fillRemaining($element);
            } else {
                $IElem = new xrechnung_element($element);
                $this->container->attach($IElem);
            }
            $IElem->setReference($this->arrOrder);

            if (isset($element['parent']) && $element['parent'] != '') {
                $parent = $this->callIEbyId($element['parent']);

                if (!$parent) {
                    $parent = new xrechnung_element(
                        array('id' => $element['parent'])
                    );
                }
                $parent->addSub($IElem);
                $this->container->attach($parent);
                unset($parent);
            }

            if ($this->first === null) {
                if (!$IElem->hasParent()) {
                    $this->first = $IElem;
                }
            }
        }
    }


    /*  Looks for the information element in the container using the unique ID
     *  and returns the object from it
     *
     *  @param  string  $elementId      unique string key of InformationElement (e.g. BT-24)
     *  @return object  $IElem          InformationElement if found, otherwise null
     */
    public function callIEbyId(string $elementId): ?xrechnung_element
    {
        if ($elementId != '') {
            foreach ($this->container as $IElem ) {
                if ($IElem->getElementId() == $elementId ) {
                    return $IElem;
                }
            }
        }
        return null;
    }


    /*  Generation of the XML string based on the information elements. Only the top-level
     *  elements (those without parents) are included in the loop. The child elements are processed 
     *  recursively.
     *
     *  @return string          the complete xml code of XRechnung
     */
    public function create(): string
    {
        $IElem = $this->first;

        ob_start();
        echo '<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
        echo '<ubl:Invoice xmlns:ubl="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2 http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd">' . "\r\n";

        do {
            if (!$IElem->hasParent()) {
                echo $IElem->setTabsOfParent('')
                    ->evalInformationElement();
            }
            $IElem = $this->callIEbyId($IElem->getNextElement());

        } while ($IElem !== null);

        echo '</ubl:Invoice>'. "\r\n";
        $result = ob_get_clean();

        return $result;
    }


    /*  Additional values ​​for the invoice that are not in the order can be added here later
     *  (to arrOrder array)
     *  @param  array       $data       e.g. messagecounternr or bank account information
     *  @return object                  xrechnung_core
     */
    public function setAdditionalData(array $data): xrechnung_core
    {
        foreach ($data as $key => $value) {
            $this->arrOrder[$key] = $value;
        }
        return $this;
    }
}
