<?php
namespace Merconis\Core;



class xrechnung_datatransformation
{


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

}