<?php
namespace Merconis\Core;


/*  Enthält Funktionen zur Berechnung von Daten
 *
 */
class xrechnung_calculations
{
    private $arrOrder = [];


    public function setRef(array &$arrOrder): void
    {
        $this->arrOrder = &$arrOrder;
    }

    /*  BT-115
     *  Ausstehende Restbeträge
     */
    public function amountDueForPayment(float $invoiceTotalAmountWithVat): string
    {
$test = 1;
        #$prepaidAmount = $this->arrOrder['KEY_ZUM_BEREITS_GEZAHLTEN_BETRAG'];
//TODO: Haben wir Teil-Rechnungen ? Dann wären hier bereits gezahlte Beträge drin
        $prepaidAmount = 1;

        $result = $invoiceTotalAmountWithVat - $prepaidAmount;
        return $result;

    }

}