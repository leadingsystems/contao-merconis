<?php

namespace Merconis\Core;

/*
 *
 */

class xrechnung_element
{

    private $arrOrder = [];
    private $name = '';
    private $elementId = '';
    private $dataSource = '';
    private $dataTransformation = '';
    private $xml = '';
    private $nachfolger = '';
    private $firstSub = '';
    private $parent = '';
    private $sub = [];

    public function __construct($element)
    {
/*
        $this->name = (isset($element['name'])) ? $element['name'] : '';
        $this->elementId = $element['id'];
        $this->dataSource = (isset($element['source'])) ? $element['source'] : '';
        $this->dataTransformation = (isset($element['transformation'])) ? $element['transformation'] : '';
        $this->xml = (isset($element['xml'])) ? $element['xml'] : '';
        $this->nachfolger = (isset($element['next'])) ? $element['next'] : '';
        $this->firstSub = (isset($element['firstSub'])) ? $element['firstSub'] : '';
        $this->parent = (isset($element['parent'])) ? $element['parent'] : '';
*/
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

/*
    public function getName(): string
    {
        return $this->name;
    }
*/

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
            $data = $this->arrOrder[$this->dataSource];
        } else {
            $data = '';
        }

        //Daten-Transformations-Funktionen
        if ($data != '' && $this->dataTransformation) {
            if (method_exists($this, $this->dataTransformation)) {
                $funcName =$this->dataTransformation;
                $data = $this->{$funcName}($data);
            }
        }

        $data .= $xmlCode;


        //Einsatz ins Ergebnis-XML
        if (str_contains($this->xml, '[DATA]')) {
            $xmlResult = str_replace('[DATA]', $data, $this->xml );
        } else {
            //Kein [DATA] Element zu ersetzen
            $xmlResult = $this->xml;
        }
        #$xmlResult .= '\r\n';
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

}