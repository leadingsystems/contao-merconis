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
    private $xml = '';
    private $nachfolger = '';

    public function __construct($name, $elementId, $dataSource, $xml, $nachfolger)
    {
        $this->name = $name;
        $this->elementId = $elementId;
        $this->dataSource = $dataSource;
        $this->xml = $xml;
        $this->nachfolger = $nachfolger;
    }

    public function getName(): string
    {
        return $this->name;
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

}