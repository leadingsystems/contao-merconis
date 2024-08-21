<?php
namespace Merconis\Core;



class xrechnung_datatransformation
{

    public function repeatForEveryTaxKey($parent)
    {
        $xmlSubCode = '';
        $cnt = 0;

        //reguläre Unter-Knoten Auswertung stoppen
        $parent->setIgnoreSubElements(true);

        //Für jeden Steuer-Kategorien Schlüssel soll die reguläre Unterknoten-Auswertung stattfinden
        $taxCategories = array_keys($parent->arrOrder['totalTaxedWith']);
        foreach ($taxCategories as $taxCategory)
        {
            $cnt++;
            if ($cnt > 1) {
                $xmlSubCode .= $parent->tabs.'</'.$parent->xml.'>
';
                $xmlSubCode .= $parent->tabs.'<'.$parent->xml.'>';
            }
            $parent->additionalParams = array('taxCategory' => $taxCategory);

//TODO: Kann die Funktion ´subElems´ auch bei repeatForEveryTaxKey eingesetzt werden
#$xmlSubCode = $parent->subElems($parent);

            if ($parent->firstSub != '') {

                $xmlSubCode .= '
';
                foreach ($parent->sub as $subElementId => $subElement) {
                    //Unter-Informations-Element muss die gleichen Parameter erhalten
                    $subElement->additionalParams = $parent->additionalParams;
                    $xmlSubCode .= $subElement->evalIE();
                }
            }

        }

        return $xmlSubCode;
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

    public function taxableAmountOfCategory(?array $taxCategoryNode, ?array $additionalData): string
    {
        $taxCategory = $additionalData['taxCategory'];
        return $taxCategoryNode[$taxCategory]['amountTaxedHerewith'];
    }

    public function taxAmountOfCategory(?array $taxCategoryNode, ?array $additionalData): string
    {
        $taxCategory = $additionalData['taxCategory'];
        return $taxCategoryNode[$taxCategory]['taxAmount'];
    }

    /*  BT-118
     *
     */
    public function vatCategoryCode(?string $anything): string
    {
//TODO: BT-118 Wie kommen wir von arrOrder auf den richtigen Code ?
/*
•  S (Standard rate)
•  Z (Zero rated goods)
•  E (Exempt from tax)
•  AE (VAT Reverse Charge)
•  K (VAT exempt for EEA intra-community supply of goods and services)
•  G (Free export item, tax not charged)
•  O (Services outside scope of tax)
•  L (Canary Islands general indirect tax)
•  M (Tax for production, services and importation in Ceuta and Melilla)

abstract class eTaxCategories
{
    const STANDARDRATE = 'S';
    const ZERORATEDGOODS = 'Z';
}
*/
        $categoryCode = 'S';
        return $categoryCode;
    }

    public function vatCategoryRate(?array $taxCategoryNode, ?array $additionalData): string
    {
        $taxCategory = $additionalData['taxCategory'];
        return $taxCategoryNode[$taxCategory]['taxRate'];
    }

    public function taxSchemeVat(?string $anything): string
    {
        $result = 'VAT';
        return $result;
    }

}