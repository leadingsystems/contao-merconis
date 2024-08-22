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

    public $arrOrder = [];

    private $name = '';
    private $elementId = '';
    private $dataSource = '';
    private $dataSourceSub = '';
    private $dataTransformation = '';
    public $tabs = '';
    public $xml = '';
    private $nachfolger = '';
    public $firstSub = '';
    private $parent = '';
    public $sub = [];
    private $processFunction = '';
    private $ignoreSubElements = false;
    public $additionalParams = null;

    public function __construct($element)
    {
        $this->trans = new \Merconis\Core\xrechnung_datatransformation();
        $this->process = new \Merconis\Core\xrechnung_processfunctions();

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
        if ($this->dataSourceSub == '') {
            $this->dataSourceSub = (isset($element['sourceSub'])) ? $element['sourceSub'] : '';
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
    }


    public function getElementId(): string
    {
        return $this->elementId;
    }

    public function setRef(array &$arrOrder): void
    {
        $this->arrOrder = &$arrOrder;
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
        $xmlResult = '';
        $xmlSubCode = '';
        $data = null;

        if ($this->processFunction != '') {

            $xmlSubCode .= '
';
            $funcName = $this->processFunction[0];
            $funcParam = $this->processFunction[1];
            if (method_exists($this->process, $funcName)) {
                $xmlSubCode = $this->process->{$funcName}($this, $funcParam);
            }
        }

        if ($this->ignoreSubElements == false) {
            //TODO: Kann die Funktion ´evalSubElements´ auch bei repeatForEveryTaxKey eingesetzt werden
            $xmlSubCode = $this->evalSubElements($this);
        }

        //Geschäftsregeln auswerten

        //Daten holen
        if ($this->dataSource) {
            if (isset($this->arrOrder[$this->dataSource])) {
                $data = $this->arrOrder[$this->dataSource];
            } else {
                lsDebugLog('','Den geforderten Schlüssel '.$this->dataSource.' für '.$this->elementId.' gibts im Auftragsarray nicht!' );
            }
        }

        //Daten aus tieferer Ebene
        if ($this->dataSourceSub) {
            $data = $this->getSubKeyData($this->dataSourceSub, $this->arrOrder);
        }


        //Daten-Transformations-Funktionen
        if ($this->dataTransformation) {

            if (method_exists($this->trans, $this->dataTransformation)) {
                $funcName = $this->dataTransformation;
                $data = $this->trans->{$funcName}($data, $this->additionalParams);
            }
        }

        $xmlData = (is_null($data)) ? '' : $data;
        $xmlData .= $xmlSubCode;


        //Einsatz ins Ergebnis-XML
        $xmlResult = $this->tabs.'<'.$this->xml.'>'.$xmlData;

        if ($this->firstSub == '') {
            $xmlResult .= '';
        } else {
            $xmlResult .= $this->tabs;
        }

        $xmlResult .= '</'.$this->xml.'>';

        #$xmlResult .= '\r\n';              //GEHT NET
        $xmlResult .= '
';
        return $xmlResult;
    }

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
    private function getSubKeyData(array $keys, $source): string
    {
        if (is_array($keys) && count($keys) > 0) {

            //nächsten Teil des Schlüssels holen
            $firstKey = array_shift($keys);

            //Wenn der Schlüssel mit @ beginnt, dann ist ein Wert aus dem Array "additionalParams" gemeint
            if (substr($firstKey, 0, 1) == '@') {
                $additionalKey = substr($firstKey, 1);
                $firstKey = $this->additionalParams[$additionalKey];
            }

            $sourcePart = $source[$firstKey];
            $result = $this->getSubKeyData($keys, $sourcePart);
        } else {
            $result = $source;
        }

        return $result;
    }

}