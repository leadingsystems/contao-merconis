<?php

namespace Merconis\Core;

/*
 * This class represents the main administration and processing of the XRechnung
 * Version: 3.0.2
 */

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
    private $first = null;

    /*  The constructor function takes the order array and the messages counter nr as arguments
     *
     *  @param  array   $arrOrder       Array containing all the data of a Merconis order
     */
	public function __construct($arrOrder = array())
    {
        $this->arrOrder = $arrOrder;
        $this->initInformationElements();
	}

    /*  For each entry from the xRechnung data object, an object of type InformationElement
     *  is created, filled with values and assigned to the container. Child
     *  elements are assigned to their parent elements.
     *  Each element receives a reference (not a copy) to the arrOrder array.
     *  The maintenance of the information elements should not be dependent on their position
     *  or order in the php file. But via their firstSub or Parent properties
     */
    public function initInformationElements(): void
    {
        $this->container = new \SplObjectStorage();
        foreach ($this->listElements as $element) {

            $informationElement = $this->callElementbyId($element['id']);

            if ($informationElement) {
                $informationElement->fillRemaining($element);
            } else {
                $informationElement = new xrechnung_element($element);
                $this->container->attach($informationElement);
            }
            $informationElement->setReference($this->arrOrder);

            if (isset($element['parent']) && $element['parent'] != '') {
                $parent = $this->callElementbyId($element['parent']);

                if (!$parent) {
                    $parent = new xrechnung_element(
                        array('id' => $element['parent'])
                    );
                }
                $parent->addSub($informationElement, $element['id']);
                $this->container->attach($parent);
                unset($parent);
            }

            if ($this->first === null) {
                if (!$informationElement->hasParent()) {
                    $this->first = $informationElement;
                }
            }
        }
    }

    /*  Looks for the information element in the container using the unique ID
     *  and returns the object from it
     *
     *  @param  string  $elementId              unique string key of InformationElement (e.g. BT-24)
     *  @return object  $informationElement     InformationElement if found, otherwise null
     */
    private function callElementbyId(string $elementId): ?xrechnung_element
    {
        if ($elementId != '') {
            foreach ($this->container as $informationElement ) {
                if ($informationElement->getElementId() == $elementId ) {
                    return $informationElement;
                }
            }
        }
        return null;
    }

    /*  Generation of the XML string based on the information elements. Only the top-level
     *  elements (those without parents) are included in the loop. The child elements are processed 
     *  recursively.
     *
     *  @return string      $result       the complete xml code of XRechnung
     */
    public function create(): string
    {
        $informationElement = $this->first;

        ob_start();
        echo '<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
        echo '<ubl:Invoice xmlns:ubl="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2 http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd">' . "\r\n";

        do {
            if (!$informationElement->hasParent()) {
                echo $informationElement->setTabsOfParent('')
                    ->evalInformationElement();
            }
            $informationElement = $this->callElementbyId($informationElement->getNextElement());

        } while ($informationElement !== null);

        echo '</ubl:Invoice>'. "\r\n";
        $result = ob_get_clean();

        return $result;
    }

    /*  Additional values for the invoice that are not in the order can be added here later
     *  (to arrOrder array)
     *
     *  @param  array       $data       e.g. messagecounternr or bank account information
     *  @return object      $this       xrechnung_core
     */
    public function setAdditionalData(array $data): xrechnung_core
    {
        foreach ($data as $key => $value) {
            $this->arrOrder[$key] = $value;
        }
        return $this;
    }
}
