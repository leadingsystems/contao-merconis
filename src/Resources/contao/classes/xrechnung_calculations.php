<?php
namespace Merconis\Core;


/*  Enthält Funktionen zur Berechnung von Daten
 *  Nötig, wenn gerechnet werden muss und das eine angegebene Source-Feld nicht ausreicht z.B.
 *  Berechnung von Teilbeträgen
 */
class xrechnung_calculations
{
    private $arrOrder = [];


    /*  Setzt einen Bezug auf das Auftragsarray.
     *
     */
    public function setRef(array &$arrOrder): void
    {
        $this->arrOrder = &$arrOrder;
    }


    /*  BT-5
     *  Liefert den Währungscode aus dem arrOrder Array.
     */
    public function getCurrencyCode(): string
    {
        $currencyCode = $this->arrOrder['currency'];
        return $currencyCode;
    }

    /*  BT-82
     *  Liefert den Währungscode aus dem arrOrder Array.
     *
     */
    public function getPaymentMeansText(): string
    {
        $paymentMeansText = $this->arrOrder['paymentMethod_title'];
        return $paymentMeansText;
    }


    /*  @unitCode   bzw. Invoiced quantity unit of measure
     *
     *  The unit of measure that applies to the invoiced quantity. Codes for unit of
     *  packaging from UNECE Recommendation No. 21 can be used in accordance with the
     *  descriptions in the "Intro" section of UN/ECE Recommendation 20, Revision 11 (2015)
     *  https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cbc-InvoicedQuantity/
     *
     *  Zuordnung unserer arrOrder quantityUnit zum 3-stelligen Code aus ´UNECE Recommendation No. 21´
     *  (z.B. für BT-129)
     */
    public function getUnitCode(array $additionalParams): string
    {
        $itemNo = $additionalParams['groupKey'];

        $quantityUnit = $this->arrOrder['items'][$itemNo]['quantityUnit'];

//TODO: die Liste weiter ausbauen: Was für Mengeneinheiten haben wir alle ? (Stück, Beutel sind bereits bekannt, welche noch ?)
        $unitCode = match ($quantityUnit) {
            'Beutel' => 'XBG',
/*
	X43	Bag, super bulk	A cloth plastic or paper based bag having the dimensions of the pallet on which it is constructed.
	X44	Bag, polybag	A type of plastic bag, typically used to wrap promotional pieces, publications, product samples, and/or catalogues.
	X5H	Bag, woven plastic
	X5L	Bag, textile
	X5M	Bag, paper
	XEC	Bag, plastic
	XFX	Bag, flexible container
	XGY	Bag, gunny	A sack made of gunny or burlap, used for transporting coarse commodities, such as grains, potatoes, and other agricultural products.
	XMB	Bag, multiply
*/
            'Stück', 'Stk.', 'ST' => 'XPP',
            default => ''
        };

        return $unitCode;
    }


    /*  BT-115
     *  Ausstehende Restbeträge
     */
    public function amountDueForPayment(float $invoiceTotalAmountWithVat): string
    {
//TODO: Haben wir Teil-Rechnungen ? Dann wären hier bereits gezahlte Beträge drin
        #$prepaidAmount = $this->arrOrder['KEY_ZUM_BEREITS_GEZAHLTEN_BETRAG'];
        $prepaidAmount = 0;
//TODO: diesen hardcodierten Betrag wieder löschen und aus arrOrder den richtigen nehmen

        $amountDueForPayment = $invoiceTotalAmountWithVat - $prepaidAmount;
        return xrechnung_datatransformation::format_unitPriceAmount($amountDueForPayment);;
    }

    /*
     *  Gibt das aktuelle Datum zurück.
     *  z.B. für BT-2
     */
    public function currentDate(): string
    {
        return date('Y-m-d', time());
    }
}