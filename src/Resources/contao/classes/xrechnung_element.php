<?php

namespace Merconis\Core;

/*
 *
 */

use function LeadingSystems\Helpers\lsDebugLog;

class xrechnung_element
{

    private $trans = null;

    public $arrOrder = [];

    private $name = '';
    private $elementId = '';
    private $dataSource = '';
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


    public function subElems($obj )
    {
        $xmlSubCode = '';
        if ($obj->firstSub != ''
            //&& $this->ignoreSubElements == false
            ) {

            $xmlSubCode .= '
';
            foreach ($obj->sub as $subElementId => $subElement) {
                //Unter-Informations-Element muss die gleichen Parameter erhalten
                $subElement->additionalParams = $obj->additionalParams;
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
            if (method_exists($this->trans, $this->processFunction)) {
                $funcName = $this->processFunction;
                $xmlSubCode = $this->trans->{$funcName}($this);
            }
        }

        if ($this->ignoreSubElements == false) {

//TODO: Kann die Funktion ´subElems´ auch bei repeatForEveryTaxKey eingesetzt werden
$xmlSubCode = $this->subElems($this);
/*
        if ($this->firstSub != ''
            //&& $this->ignoreSubElements == false
            ) {

            $xmlSubCode .= '
';
            foreach ($this->sub as $subElementId => $subElement) {
                //Unter-Informations-Element muss die gleichen Parameter erhalten
                $subElement->additionalParams = $this->additionalParams;
                $xmlSubCode .= $subElement->evalIE();
            }
        }
*/
        }

        //Zuerst alle Regeln auswerten

        //Daten holen
        if ($this->dataSource) {
            if (isset($this->arrOrder[$this->dataSource])) {
                $data = $this->arrOrder[$this->dataSource];
            } else {
                lsDebugLog('','Den geforderten Schlüssel '.$this->dataSource.' für '.$this->elementId.' gibts im Auftragsarray nicht!' );
            }
        }

        //Daten-Transformations-Funktionen
        if ($this->dataTransformation) {

            if (method_exists($this->trans, $this->dataTransformation)) {
                $funcName = $this->dataTransformation;
                $data = $this->trans->{$funcName}($data
                    , $this->additionalParams
                );
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
}