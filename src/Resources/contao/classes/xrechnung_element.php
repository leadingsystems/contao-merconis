<?php

namespace Merconis\Core;

/*
 *
 */

use function LeadingSystems\Helpers\lsDebugLog;

class xrechnung_element
{

    private $trans = null;
    private $calcu = null;

    public $arrOrder = [];

    private $name = '';
    private $elementId = '';
    private $dataSource = '';
    private $dataTransformation = '';
    public $tabs = '';
    private $tabsParent = '';
    public $xml = '';
    private $attributes = [];
    private $nachfolger = '';
    public $firstSub = '';
    private $parent = '';
    public $sub = [];
    private $repeat = '';
    private $calculate = '';
    private $ignoreSubElements = false;
    public $additionalParams = null;
    private $evaluationError = '';


    public function __construct($element)
    {
        $this->trans = new \Merconis\Core\xrechnung_datatransformation();
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
        if ($this->repeat == '') {
            $this->repeat = (isset($element['repeat'])) ? $element['repeat'] : '';
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


    public function evalIE(): string
    {
        try {

            $xmlResult = '';
            $xmlSubCode = '';
            $repeatCnt = 0;
            $xmlAttributeValue = '';
            $xmlAttributeCode = '';
            $data = null;


            if ($this->repeat != '') {
                //die Foreach für jeden Eintrag wiederholen
                $keyGroups = array_keys($this->arrOrder[$this->repeat]);
            } else {
                //die Foreach soll nur 1x ausgeführt werden
                $keyGroups = [0];
            }

            foreach ($keyGroups as $groupKey) {

                if ($groupKey != 0) {
                    $this->additionalParams = array('groupKey' => $groupKey);
                }

                //Sobald es aber mehr als 1 Element ist müssen die XML Tags hier drin erneut geschlossen und wieder geöffnet werden
                $repeatCnt++;
                if ($repeatCnt > 1) {
                    $xmlSubCode .= $this->tabsParent.'</'.$this->xml.'>
';
                    $xmlSubCode .= $this->tabsParent.'<'.$this->xml.'>';
                }

                if ($this->firstSub != '') {
                    $xmlSubCode .= '
';
                    foreach ($this->sub as $subElementId => $subElement) {
                        //Unter-Informations-Element muss die gleichen Parameter erhalten
                        $subElement->additionalParams = $this->additionalParams;
                        $subElement->setTabsOfParent($this->tabsParent);
                        $xmlSubCode .= $subElement->evalIE();
                    }
                }
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
            $xmlResult = $this->tabsParent.'<'.$this->xml.$xmlAttributeCode.'>'.$xmlData;

            if ($this->firstSub != '') {
                $xmlResult .= $this->tabsParent;
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

    /*  Das Information Element erhält einen String mit den Tabs des Parents und für sich selbst werden 2 Spaces
     *  dran gehängt
     */
    public function setTabsOfParent(string $tabs): void
    {
        $this->tabsParent = $tabs.'  ';
    }
}