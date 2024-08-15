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

    public function __construct($element)
    {
        $this->name = $element['name'];
        $this->elementId = $element['id'];
        $this->dataSource = ($element['source']) ? $element['source'] : '';
        $this->dataTransformation = ($element['transformation']) ? $element['transformation'] : '';
        $this->xml = $element['xml'];
        $this->nachfolger = ($element['next']) ? $element['next'] : '';
    }

    public function getName(): string
    {
        return $this->name;
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
#$this->arrOrder['status03'] = 'meintest';

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


        //Einsatz ins Ergebnis-XML
        if (str_contains($this->xml, '[DATA]')) {
            $xmlResult = str_replace('[DATA]', $data, $this->xml );
        } else {
            //Kein [DATA] Element zu ersetzen
            $xmlResult = $this->xml;
        }
        return $xmlResult;
    }

    public function getNextElement(): string
    {
        return $this->nachfolger;
    }


    private function ts2Date(int $timestamp): string
    {
        return date('Y-m-d', $timestamp);
    }

}