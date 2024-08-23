<?php

namespace Merconis\Core;

/*
 *
 */

use function LeadingSystems\Helpers\lsDebugLog;

class xrechnung_element
{

    private $trans = null;
    private $process = null;
    private $calcu = null;

    public $arrOrder = [];

    private $name = '';
    private $elementId = '';
    private $dataSource = '';
    #private $dataSourceSub = '';
    private $dataTransformation = '';
    public $tabs = '';
    public $xml = '';
    private $attributes = [];
    private $nachfolger = '';
    public $firstSub = '';
    private $parent = '';
    public $sub = [];
    private $processFunction = '';
    private $calculate = '';
    private $ignoreSubElements = false;
    public $additionalParams = null;
    private $evaluationError = '';


    public function __construct($element)
    {
        $this->trans = new \Merconis\Core\xrechnung_datatransformation();
        $this->process = new \Merconis\Core\xrechnung_processfunctions();
        $this->calcu = new \Merconis\Core\xrechnung_calculations();

        $this->fillRemaining($element);
    }


    public function fillRemaining($element)
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
        if ($this->tabs == '') {
            $this->tabs = (isset($element['tabs'])) ? $element['tabs'] : '';
        }
        if ($this->xml == '') {
            $this->xml = (isset($element['xml'])) ? $element['xml'] : '';
        }
        if ($this->attributes == []) {
            $this->attributes = (isset($element['xmlAttributes'])) ? $element['xmlAttributes'] : '';
        }
        if ($this->nachfolger == '') {
            $this->nachfolger = (isset($element['next'])) ? $element['next'] : '';
        }
        if ($this->firstSub == '') {
            $this->firstSub = (isset($element['firstSub'])) ? $element['firstSub'] : '';
        }
        if ($this->parent == '') {
            $this->parent = (isset($element['parent'])) ? $element['parent'] : '';
        }
        if ($this->processFunction == '') {
            $this->processFunction = (isset($element['processFunction'])) ? $element['processFunction'] : '';
        }
        if ($this->calculate == '') {
            $this->calculate = (isset($element['calculate'])) ? $element['calculate'] : '';
        }
    }


    public function getElementId(): string
    {
        return $this->elementId;
    }

    public function setRef(array &$arrOrder): void
    {
        $this->arrOrder = &$arrOrder;

        $this->calcu->setRef($arrOrder);

    }


    public function evalSubElements(xrechnung_element $IElem ): string
    {
        $xmlSubCode = '';
        if ($IElem->firstSub != '') {

            $xmlSubCode .= '
';
            foreach ($IElem->sub as $subElementId => $subElement) {
                //Unter-Informations-Element muss die gleichen Parameter erhalten
                $subElement->additionalParams = $IElem->additionalParams;
                $xmlSubCode .= $subElement->evalIE();
            }
        }
        return $xmlSubCode;
    }

    public function evalIE(): string
    {
        try {

            $xmlResult = '';
            $xmlSubCode = '';
            $xmlAttributeValue = '';
            $xmlAttributeCode = '';
            $data = null;

        if ($this->processFunction != '') {

                $xmlSubCode .= '
';
                $functionName = $this->processFunction[0];
                $funcParam = $this->processFunction[1];
                if (method_exists($this->process, $functionName)) {
                    $xmlSubCode = $this->process->{$functionName}($this, $funcParam);
                }
            }

            if ($this->ignoreSubElements == false) {
                //TODO: Kann die Funktion ´evalSubElements´ auch bei repeatForEveryTaxKey eingesetzt werden
                $xmlSubCode = $this->evalSubElements($this);
            }

            //Geschäftsregeln auswerten


            //Attribute - Eigenschaften innerhalb eines XML Tags
            if ($this->attributes) {
                foreach ($this->attributes as $attribute ) {
                    $xmlAttribute = $attribute[0];
                    $functionName = $attribute[1];
                    if (method_exists($this->calcu, $functionName)) {
                        $xmlAttributeValue = $this->calcu->{$functionName}($this->additionalParams);
                    }
                    $xmlAttributeCode .= ' '.$xmlAttribute.'="'.$xmlAttributeValue.'"';
                }
            }


            //Daten holen
            if ($this->dataSource) {
                $data = $this->getSubKeyData($this->dataSource, $this->arrOrder);
                if ($this->evaluationError) {
                    lsDebugLog('',$this->evaluationError);
                    return $this->evaluationError;
                }
            }

if ($this->elementId == 'BT-115') {
    $test = 1;

    #$this->ftest($data);
}

            //Berechnungs-Funktionen
            if ($this->calculate) {
                if (method_exists($this->calcu, $this->calculate)) {
                    $functionName = $this->calculate;
                    $data = $this->calcu->{$functionName}($data, $this->additionalParams);
                }
            }

            //Daten-Transformations-Funktionen
            if ($this->dataTransformation) {
                if (method_exists($this->trans, $this->dataTransformation)) {
                    $functionName = $this->dataTransformation;
                    $data = $this->trans->{$functionName}($data, $this->additionalParams);
                }
            }

            $xmlData = (is_null($data)) ? '' : $data;
            $xmlData .= $xmlSubCode;


            //Einsatz ins Ergebnis-XML
            $xmlResult = $this->tabs.'<'.$this->xml.$xmlAttributeCode.'>'.$xmlData;

            if ($this->firstSub == '') {
                $xmlResult .= '';
            } else {
                $xmlResult .= $this->tabs;
            }

            $xmlResult .= '</'.$this->xml.'>';

            #$xmlResult .= '\r\n';              //GEHT NET
//TODO: hier noch eine bessere Lösung finden. \r\n muss doch irgendwie gehen
            $xmlResult .= '
';
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $xmlResult;
    }

/*
    private function ftest($val)
    {
$test = 1;
    }
*/

    public function getNextElement(): string
    {
        return $this->nachfolger;
    }

    public function hasParent(): bool
    {
        return $this->parent != '';
    }

    public function setIgnoreSubElements($val)
    {
        $this->ignoreSubElements = $val;
    }

    public function addSub($elem)
    {
        $key = $elem->getElementId();
        $this->sub[$key] = $elem;
    }

    /*  liefert den Wert eines Arrays zurück wobei das Array mehr als eine Verschachtelungs-Ebene hat. Also
     *  nicht $array['key'] sondern  $array['key1']['key2']['key3']
     *  $keys enthält dabei eine
     *  Liste von Teil-Schlüsseln anhand derer auf die nächst-tiefere Ebene des Quell Arrays zugegriffen wird.
     *  Mit jedem rekursiven Aufruf wird die nächste Ebene angesprochen.
     *
     */
    private function getSubKeyData(array $keys, mixed $source): mixed
    {
        if (is_array($keys) && count($keys) > 0) {

            //nächsten Teil des Schlüssels holen
            $firstKey = array_shift($keys);

            //Wenn der Schlüssel mit @ beginnt, dann ist ein Wert aus dem Array "additionalParams" gemeint
            if (substr($firstKey, 0, 1) == '@') {
                $additionalKey = substr($firstKey, 1);
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

}