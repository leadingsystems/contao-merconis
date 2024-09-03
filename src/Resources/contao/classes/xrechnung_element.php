<?php

namespace Merconis\Core;

/*
 *
 */

use function LeadingSystems\Helpers\lsDebugLog;

class xrechnung_element
{

    private $transformations = null;
    private $calculations = null;
    private $conditions = null;

    public $arrOrder = [];

    private $name = '';
    private $elementId = '';
    private $dataSource = '';
    private $dataTransformation = '';
    private $tabs = '';
    private $xml = '';
    private $attributes = [];
    private $nextElement = '';
    private $firstSub = '';
    private $parent = '';
    private $sub = [];
    private $repeat = '';
    private $calculate = '';
    private $condition = '';
    private $additionalParams = null;
    private $evaluationError = '';
    private $evaledValue = null;


    public function __construct($element)
    {
        $this->transformations = new \Merconis\Core\xrechnung_datatransformation();
        $this->calculations = new \Merconis\Core\xrechnung_calculations();
        $this->conditions = new \Merconis\Core\xrechnung_conditions();

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
                //die Foreach für jeden Eintrag wiederholen
                $groupKeys = array_keys($this->arrOrder[$this->repeat]);
            } else {
                //die Foreach soll nur 1x ausgeführt werden
                $groupKeys = [0];
            }

            foreach ($groupKeys as $groupKey) {

                $this->additionalParams = ($groupKey != 0) ? array('groupKey' => $groupKey) : $this->additionalParams;

                //Sobald es aber mehr als 1 Element ist müssen die XML Tags hier drin erneut geschlossen und wieder geöffnet werden
                $repeatCnt++;
                if ($repeatCnt > 1) {
                    $xmlSubCode .= $this->tabs.'</'.$this->xml.'>'.PHP_EOL;
                    $xmlSubCode .= $this->tabs.'<'.$this->xml.'>';
                }

                if ($this->firstSub != '') {
                    $xmlSubCode .= PHP_EOL;
//TODO: hier noch die next eigenschaft verwenden
                    foreach ($this->sub as $subElementId => $subElement) {
                        //Unter-Informations-Element muss die gleichen Parameter erhalten
                        $subElement->additionalParams = $this->additionalParams;
                        $subElement->setTabsOfParent($this->tabs);
                        $xmlSubCode .= $subElement->evalInformationElement();
                    }
                }
            }



            //Geschäftsregeln auswerten


            //Attribute - Eigenschaften innerhalb eines XML Tags
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
                if (method_exists($this->calculations, $this->calculate)) {
                    $functionName = $this->calculate;
                    $data = $this->calculations->{$functionName}($data, $this->additionalParams);
                }
            }

            //Daten-Transformations-Funktionen
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
             *  have the values ​​of all group keys, storage takes place at group key level
             */
            if (isset($this->additionalParams['groupKey'])) {
                //Wiederholungsgruppe -> Werte werden mit dem Groupkey geschlüsselt
                $groupKey = $this->additionalParams['groupKey'];
                $this->evaledValue[$groupKey] = $xmlData;
            } else {
                //einfacher Wert
                $this->evaledValue = $xmlData;
            }

            $xmlData .= $xmlSubCode;

            //leere Knoten werden bei validierung bemängelt. Trim, weil durch Sub-Knoten Umbrüche enthalten sein können
            if (trim($xmlData) == '') {
                lsDebugLog('',$this->elementId.': Der Knoten bleibt leer weil Daten fehlen!');
                return '';
            }

            //Einsatz ins Ergebnis-XML
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



    public function getNextElement(): string
    {
        return $this->nextElement;
    }

    public function hasParent(): bool
    {
        return $this->parent != '';
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

            //Wenn der Schlüssel selbst ein Array ist, dann ist ein Wert aus dem Array "additionalParams" gemeint
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

    /*  Das Information Element erhält einen String mit den Tabs des Parents und für sich selbst werden 2 Spaces
     *  dran gehängt
     */
    public function setTabsOfParent(string $tabs): xrechnung_element
    {
        $this->tabs = $tabs.'  ';
        return $this;
    }
}