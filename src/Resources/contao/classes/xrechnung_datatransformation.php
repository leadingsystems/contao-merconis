<?php
namespace Merconis\Core;



class xrechnung_datatransformation
{

    public function repeatForEveryTaxKey($parent)
    {
        $xmlCode = '';

        //reguläre Unter-Knoten Auswertung stoppen
        $parent->setIgn(true);

        //Für jeden Steuer-Kategorien Schlüssel soll die reguläre Unterknoten-Auswertung stattfinden
        $taxCategories = array_keys($parent->arrOrder['totalTaxedWith']);
        foreach ($taxCategories as $taxCategory)
        {
            $parent->addi = array('taxCategory' => $taxCategory);

            if ($parent->firstSub != '') {

                $xmlCode .= '
    ';

                foreach ($parent->sub as $subElementId => $subElement) {
                    //Unter-Informations-Element muss die gleichen Parameter erhalten
                    $subElement->addi = $parent->addi;
                    $xmlCode .= $subElement->evalIE();
                }
            }
        }

        return $xmlCode;
    }


    // Daten Transformations Funktionen
    public function ts2Date(?int $timestamp): string
    {
        return date('Y-m-d', $timestamp);
    }


    public function payment2Means(string $paymentTitle): string
    {
        //UNTDID 4461
//TODO: hier soll man anhand des paymentTitles (aus arrOrder) auf den richtigen Code kommen - VERVOLLSTÄNDIGEN
        $result = '';
        if ($paymentTitle == 'PayPal Checkout') {
            $result = 30;
        }
        return $result;
    }

    public function customizationId(?string $anything): string
    {
//TODO: hier prüfen, wie der String dynamisch zusammengebaut werden muss
        return 'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0';
    }

    public function invoiceTypeCode(?string $anything): string
    {
//TODO: prüfen, anhand von was (aus arrOrder) man auf auf einen der richtigen folgenden Werte kommt
/*
         326 (Partial invoice)
        •  380 (Commercial invoice)
        •  384 (Corrected invoice)
        •  389 (Self-billed invoice)
        •  381 (Credit note)
        •  875 (Partial construction invoice)
        •  876 (Partial final construction invoice)
        •  877 (Final construction invoice)
*/
        return '380';
    }

    public function taxableAmountOfCategory(?array $anything, ?array $additionalData): string
    {
        $taxCategory = $additionalData['taxCategory'];
        return $anything[$taxCategory]['amountTaxedHerewith'];
    }

    public function taxAmountOfCategory(?array $anything): string
    {
        $test = 1;

        return '';
    }

    public function vatCategoryCode(?string $anything): string
    {
        $test = 1;

        return '';
    }

}