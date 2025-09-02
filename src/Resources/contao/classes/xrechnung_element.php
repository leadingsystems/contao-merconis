<?php

namespace Merconis\Core;

/*  The class of information elements. An object can be created for each business term (e.g. BT-24) from XRechnung
*
 */

use function LeadingSystems\Helpers\lsDebugLog;

class xrechnung_element
{

    /*  Object of data conversion functions
     *  @var    object
     */
    private $transformations = null;

    /*  Object of the calculation functions
     *  @var    object
     */
    private $calculations = null;

    /*  Object of functions for conditional flows
     *  @var    xrechnung_conditions
     */
    private $conditions = null;

    /*  The order array
     *  @var    array
     */
    public $arrOrder = [];

    /*  Meaningful name of the information element (no functional meaning)
     *  @var    string
     */
    private $name = '';

    /*  the unique ID of the element
     *  @var    string
     */
    private $elementId = '';

    /*  Field in the order array from which the data comes
     *  @var    string
     */
    private $dataSource = '';

    /*  Name of data processing functions
     *  @var    string
     */
    private $dataTransformation = '';

    /*  starting spacing characters in the output XML code
     *  @var    string
     */
    private $tabs = '';

    /*  Name of the XML tag that is output for the information element
     *  @var    string
     */
    private $xml = '';

    /*  array for properties within an XML tag
     *  @var    array
     */
    private $attributes = [];

    /*  Name of the next element
     *  @var    string
     */
    private $nextElement = '';

    /*  Name of the first child element
     *  @var    string
     */
    private $firstSub = '';

    /*  Name of the father element
     *  @var    string
     */
    private $parent = '';

    /*  Container for child elements
     *  @var    array
     */
    private $sub = [];

    /*  Name of keys in the order array that need to be repeated
     *  @var    string
     */
    private $repeat = '';

    /*  Name of calculation functions
     *  @var    string
     */
    private $calculate = '';

    /*  Name of conditional functions
     *  @var    string
     */
    private $condition = '';

    /*  further parameters for group keys
     *  @var    array
     */
    private $additionalParams = null;

    /*  Return of evaluation errors
     *  @var    string
     */
    private $evaluationError = '';

    /*  To save the evaluated results
     *  @var    mixed
     */
    private $evaledValue = null;


    public function __construct($element)
    {
        $this->transformations = new \Merconis\Core\xrechnung_datatransformation();
        $this->calculations = new \Merconis\Core\xrechnung_calculations();
        $this->conditions = new \Merconis\Core\xrechnung_conditions();

        $this->fillRemaining($element);
    }


    /*  Fills the object with the passed values
     *
     *  @param      string      $element        data of current information element (from elementData class)
     *  @return     void
     */
    public function fillRemaining($element): void
    {
        if ($this->name == '') {
            $this->name = (isset($element['name'])) ? $element['name'] : '';
        }
        if ($this->elementId == '') {
            $this->elementId = $element['id'];
        }
        if ($this->dataSource == '') {
            $this->dataSource = (isset($element['source'])) ? $element['source'] : '';
        }
        if ($this->dataTransformation == '') {
            $this->dataTransformation = (isset($element['transform'])) ? $element['transform'] : '';
        }
        if ($this->xml == '') {
            $this->xml = (isset($element['xml'])) ? $element['xml'] : '';
        }
        if ($this->attributes == []) {
            $this->attributes = (isset($element['xmlAttributes'])) ? $element['xmlAttributes'] : [];
        }
        if ($this->nextElement == '') {
            $this->nextElement = (isset($element['next'])) ? $element['next'] : '';
        }
        if ($this->firstSub == '') {
            $this->firstSub = (isset($element['firstSub'])) ? $element['firstSub'] : '';
        }
        if ($this->parent == '') {
            $this->parent = (isset($element['parent'])) ? $element['parent'] : '';
        }
        if ($this->repeat == '') {
            $this->repeat = (isset($element['repeat'])) ? $element['repeat'] : '';
        }
        if ($this->calculate == '') {
            $this->calculate = (isset($element['calculate'])) ? $element['calculate'] : '';
        }
        if ($this->condition == '') {
            $this->condition = (isset($element['condition'])) ? $element['condition'] : '';
        }
    }

    /*  Returns the ID of the element
     *
     *  @return     string      $this->elementId        unique key of element
     */
    public function getElementId(): string
    {
        return $this->elementId;
    }


    /*  Sets a reference to the order array. Likewise for the calculation object
     *
     *  @param  array   $arrOrder   byreference, Array containing all the data of a Merconis order
     *  @return void
     */
    public function setReference(array &$arrOrder): void
    {
        $this->arrOrder = &$arrOrder;
        $this->calculations->setReference($arrOrder);
        $this->conditions->setReference($arrOrder);
    }


    /*
     *  This is where the actual evaluation of each individual information element takes place, which takes place in
     *  several steps
     *  - Checking conditions
     *  - possible recursive calling of child elements
     *  - Evaluation of attributes
     *  - Extract data from the order array
     *  - Execution of calculation functions
     *  - Execution of the transformation functions
     *  - Storage of results
     *  - complete creation of the XML code and its return
     *
     *  @return     string      $xmlResult      xml code to be exported to file (for the information element)
     */

    public function evalInformationElement(): string
    {
        try {
            $xmlResult = '';
            $xmlSubCode = '';
            $repeatCnt = 0;
            $xmlAttributeValue = '';
            $xmlAttributeCode = '';
            $data = null;


            //conditional processing
            if ($this->condition) {
                if (method_exists($this->conditions, $this->condition)) {
                    $functionName = $this->condition;
                    if (!$this->conditions->{$functionName}()) {
                        return '';
                    }
                }
            }


            if ($this->repeat != '') {
                //repeat the foreach for each entry
                $groupKeys = array_keys($this->arrOrder[$this->repeat]);
            } else {
                //the foreach should only be executed once
                $groupKeys = [0];
            }

            foreach ($groupKeys as $groupKey) {

                $this->additionalParams = ($groupKey != 0) ? array('groupKey' => $groupKey) : $this->additionalParams;

                //But as soon as there are more than 1 element, the XML tags in here have to be closed and reopened again
                $repeatCnt++;
                if ($repeatCnt > 1) {
                    $xmlSubCode .= $this->tabs.'</'.$this->xml.'>'.PHP_EOL;
                    $xmlSubCode .= $this->tabs.'<'.$this->xml.'>';
                }

                if ($this->firstSub != '') {
                    $xmlSubCode .= PHP_EOL;

                    $subElement = $this->getElementById($this->firstSub);
                    while(is_object($subElement)) {
                        $subElement->additionalParams = $this->additionalParams;
                        $subElement->setTabsOfParent($this->tabs);
                        $xmlSubCode .= $subElement->evalInformationElement();
                        $subElement = $this->getElementById($subElement->nextElement);
                    };
                }
            }


            if ($this->attributes) {
                foreach ($this->attributes as $attribute ) {
                    $xmlAttribute = $attribute[0];
                    $functionName = $attribute[1];
                    if (method_exists($this->calculations, $functionName)) {
                        $xmlAttributeValue = $this->calculations->{$functionName}($this->additionalParams);
                    }
                    $xmlAttributeCode .= ' '.$xmlAttribute.'="'.$xmlAttributeValue.'"';
                }
            }


            if ($this->dataSource) {
                $data = $this->getSubKeyData($this->dataSource, $this->arrOrder);
                if ($this->evaluationError) {
                    lsDebugLog('',$this->evaluationError);
                    return $this->evaluationError;
                }
            }


            if ($this->calculate) {
                if (method_exists($this->calculations, $this->calculate)) {
                    $functionName = $this->calculate;
                    $data = $this->calculations->{$functionName}($data, $this->additionalParams);
                }
            }


            if ($this->dataTransformation) {
                $functionName = $this->dataTransformation[0];
                $params = $this->dataTransformation[1] ?? null;
                if (method_exists($this->transformations, $functionName)) {
                    $data = $this->transformations->{$functionName}($data, $params);
                }
            }

            $xmlData = $data ?? '';

            /*  There is one object per business term but not one for each XML line. So we have an
             *  object for BT-131 but one XML term per invoice line (groupkeys). In order to still
             *  have the values of all group keys, storage takes place at group key level
             */
            if (isset($this->additionalParams['groupKey'])) {
                //Repeating group -> Values are encoded with the group key
                $groupKey = $this->additionalParams['groupKey'];
                $this->evaledValue[$groupKey] = $xmlData;
            } else {
                $this->evaledValue = $xmlData;
            }

            $xmlData .= $xmlSubCode;

            //empty nodes are criticized during validation. Trim because sub-nodes can contain breaks
            if (trim($xmlData) == '') {
                lsDebugLog('',$this->elementId.': Der Knoten bleibt leer weil Daten fehlen!');
                return '';
            }

            $xmlResult = $this->tabs.'<'.$this->xml.$xmlAttributeCode.'>'.$xmlData;

            if ($this->firstSub != '') {
                $xmlResult .= $this->tabs;
            }

            $xmlResult .= '</'.$this->xml.'>'.PHP_EOL;

        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $xmlResult;
    }



    /*  Returns the id of the next element
     *
     *  @return     string      $this->nextElement      id of next element (next Attribute)
     */
    public function getNextElement(): string
    {
        return $this->nextElement;
    }

    /*  Indicates whether there is a parent element
     *
     *  @return     bool        true if parent exists
     */
    public function hasParent(): bool
    {
        return $this->parent != '';
    }


    /*  The passed element is included - with its elementId as key - in the 'sub' list of the current element
     *
     *  @param      xrechnung_element   $element        element to be included
     *  @param      string              $elementId      if of element
     *  @return     void
     */
    public function addSub(xrechnung_element $element, string $elementId): void
    {
        $this->sub[$elementId] = $element;
    }

    /*  returns the value of an array where the array has more than one nesting level. So
     *  not $array['key'] but $array['key1']['key2']['key3']
     *  $keys contains one
     *  List of partial keys used to access the next lower level of the source array.
     *  The next level is addressed with each recursive call.
     *
     *  @param  array       $keys       list of keys to access the (part) arrOrder array
     *  @param  mixed       $source     array of the whole or partial order
     *  @return object      $result     The rest that is at the end of the key array
     */
    private function getSubKeyData(array $keys, mixed $source): mixed
    {
        if (is_array($keys) && count($keys) > 0) {

            $firstKey = array_shift($keys);

            //If the key itself is an array, then it means a value from the “additionalParams” array
            if (is_array($firstKey)) {
                $additionalKey = $firstKey[0];
                $firstKey = $this->additionalParams[$additionalKey];
            }

            if (!isset($source[$firstKey]) || $firstKey == '')  {
                $this->evaluationError = 'Fehler beim Element ´'.$this->elementId.'´,  Schlüssel ´'.$firstKey.'´ ist nicht vorhanden!';
                return null;
            }

            $sourcePart = $source[$firstKey];
            $result = $this->getSubKeyData($keys, $sourcePart);
        } else {
            $result = $source;
        }

        return $result;
    }

    /*  The information element receives a string with the parent's tabs and 2 spaces for itself
     *  attached to it
     *
     *  @param  string      $tabs       string with spaces/tabs from parent information element
     *  @return object      $this       current element object
     */
    public function setTabsOfParent(string $tabs): xrechnung_element
    {
        $this->tabs = $tabs.'  ';
        return $this;
    }

    /*  Returns the corresponding object for the passed element ID
     *
     *  @param  string      $elementId              id of an information element
     *  @return object      $informationElement     object of information element
     */
    private function getElementById(string $elementId): ?xrechnung_element
    {
        $informationElement = null;
        if ($elementId != '') {
            if (isset($this->sub[$elementId])) {
                $informationElement = $this->sub[$elementId];
            } else {
                lsDebugLog('','Im Element '.$this->elementId.' soll das sub Element ´'.$elementId.'´ hinzugefügt werden welches nicht existiert');
            }
        }
        return $informationElement;
    }
}