<?php

namespace Merconis\Core;

/*
 *
 */

use function LeadingSystems\Helpers\lsDebugLog;

class xrechnung_element
{

    private $trans = null;

    private $arrOrder = [];
    private $name = '';
    private $elementId = '';
    private $dataSource = '';
    private $dataTransformation = '';
    private $tabs = '';
    private $xml = '';
    private $nachfolger = '';
    private $firstSub = '';
    private $parent = '';
    private $sub = [];

    public function __construct($element)
    {

        #$obj1 = new \Merconis\Core\xrechnung_trait_datatransformation();
        #$obj1 = new xrechnung_datatransformation();
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
            $this->dataTransformation = (isset($element['transformation'])) ? $element['transformation'] : '';
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
    }


    public function getElementId(): string
    {
        return $this->elementId;
    }

    public function setRef(array &$arrOrder): void
    {
        $this->arrOrder = &$arrOrder;
    }


    public function evalIE(): string
    {
        $xmlResult = '';
        $xmlCode = '';
        $data = null;
#$this->arrOrder['status03'] = 'meintest';

        if ($this->firstSub != '') {

            $xmlCode .= '
';

            foreach ($this->sub as $key => $subElem) {
                $xmlCode .= $subElem->evalIE();
            }
        }

        //Zuerst alle Regeln auswerten

        //Daten holen
        if ($this->dataSource) {
            if (isset($this->arrOrder[$this->dataSource])) {
                $data = $this->arrOrder[$this->dataSource];
            } else {
                lsDebugLog('','Den geforderten Schlüssel '.$this->dataSource.' für '.$this->elementId.' gibts im Auftragsarray nicht!' );
#echo 'Den geforderten Schlüssel '.$this->dataSource.' für '.$this->id.' gibts im Auftragsarray nicht!';
            }


        }

        //Daten-Transformations-Funktionen
        if ($this->dataTransformation) {
            if (method_exists($this->trans, $this->dataTransformation)) {
                $funcName =$this->dataTransformation;
                $data = $this->trans->{$funcName}($data);
            }
        }

        $xmlData = (is_null($data)) ? '' : $data;
        $xmlData .= $xmlCode;


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


    public function addSub($elem)
    {
        $key = $elem->getElementId();
        $this->sub[$key] = $elem;
    }
/*
// Daten Transformations Funktionen
    private function ts2Date(int $timestamp): string
    {
        return date('Y-m-d', $timestamp);
    }


    private function payment2Means(string $paymentTitle): string
    {
        $result = '';
        if ($paymentTitle == 'PayPal Checkout') {
            $result = 30;
        }
        return $result;
    }

    private function customizationId(string $paymentTitle): string
    {
//TODO: hier prüfen, wie der String dynamisch zusammengebaut werden muss
        return 'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0';
    }
*/
}