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
     *  Das in Textform ausgedrückte erwartete oder genutzte Zahlungsmittel. Es wird als XML Attribut
     *  im Element BT-81 eingesetzt (deswegen keine Anwendung über Source)
     */
    public function getPaymentMeansText(): string
    {
        $paymentMeansText = $this->arrOrder['paymentMethod_title'];
        return $paymentMeansText;
    }

    /*  BT-83
     *  Das in Textform ausgedrückte erwartete oder genutzte Zahlungsmittel. Es wird als XML Attribut
     *  im Element BT-81 eingesetzt (deswegen keine Anwendung über Source)
     */
    public function paymentMeansId(): string
    {
//TODO: den Wert dynamisch ermitteln
        return 'abc';
    }


    /*  BT-84
     *  Die Kennung des Kontos, auf das die Zahlung erfolgen soll: IBAN für Zahlungen im
     *  SEPA-Raum, Kontonummer oder IBAN im Falle von Auslandszahlungen.
     */
    public function payeeFinancialAccount(): string
    {
//TODO: den Wert dynamisch ermitteln
        #return 'SOLADEST600';
        return 'DE20 1234 1234 1234';
    }

    /*  BT-85
     *  Name des Kontos bei einem Zahlungsdienstleister, auf das die Zahlung erfolgen
     *  soll. (z. B. Kontoinhaber)
     */
    public function paymentAccountName(): string
    {
        $firstname = $this->arrOrder['customerData']['personalData']['firstname'];
        $lastname = $this->arrOrder['customerData']['personalData']['lastname'];
        $accountName = ($firstname) ? $firstname.' ' : '';
        $accountName .= $lastname;
        return $accountName;
    }

    /*  BT-86
     *  Die Kennung des Konto führenden Zahlungsdienstleisters. Diese Kennung ergibt sich bei
     *  Zahlungen im SEPA-Raum im Regelfall aus der IBAN.
     */
    public function paymentProviderIdentifier(): string
    {
//TODO: den Wert dynamisch ermitteln
        return 'SOLADEST600';
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
        return xrechnung_datatransformation::format_unitPriceAmount($amountDueForPayment);
    }

    /*
     *  Gibt das aktuelle Datum zurück.
     *  z.B. für BT-2
     */
    public function currentDate(): string
    {
        return date('Y-m-d', time());
    }



    /*  BT-34
     *  Gibt die elektronische Adresse des Verkäufers an, an die die Antwort der Anwendungsebene
     *  auf eine Rechnung gesendet werden kann.
     */
    public function sellerEletronicAdress(): string
    {
//TODO: wo kriegen wir die eMail des Verkäufers her ?
        return 'supplier@web.de';
    }


    /*  BT-34 -> Seller electronic address/Scheme identifier
     *  Das Bildungsschema für "Seller electronic address" (BT-34). Es ist die Codeliste
     *  Electronic Address Scheme code list (EAS) zu verwenden. Die Codeliste wird von der
     *  Connecting Europe Facility gepflegt und herausgegeben.
     */
    public function sellerEletronicAdressScheme(): string
    {
//TODO: kriegt noch keine Parameter ($data ist leer) und liefert daher immer EM zurück
        $schemeId = match ([]) {
            #'Stück', 'Stk.', 'ST' => 'XPP',
            default => 'EM'             //eMail
        };
        return $schemeId;
    }

    /*  BT-35
     *  Seller address line 1
     */
    public function sellerStreetName(): string
    {
//TODO: Die Strasse des Verkäufers
        return 'Königsstrasse 10/1';
    }

    /*  BT-37
     *  Seller city
     */
    public function sellerCity(): string
    {
//TODO: hier brauchen wir die Stadt des Verkäufers/Händlers
        return 'Sachsenheim';
    }

    /*  BT-38
     *  Seller country code
     */
    public function sellerPostCode(): string
    {
//TODO: hier die PLZ des Verkäufers/Händlers
        return '74320';
    }

    /*  BT-40
     *  Seller country code
     */
    public function sellerCountryCode(): string
    {
//TODO: hier das Länderkürzel des Verkäufers/Händlers
        return 'DE';
    }

    /*  BT-30
     *  Seller legal registration identifier
     */
    public function sellerRegistrationName(): string
    {
//TODO: hier eingetragene Firmenname des Verkäufers/Händlers
        return 'Kunde GmbH';
    }

    /*  BT-41
     *  Angaben zu Ansprechpartner oder Kontaktstelle (wie z. B. Name einer Person, Abteilungs-
     *  oder Bürobezeichnung).
     */
    public function sellerContactPoint(): string
    {
//TODO: hier Ansprechpartner Name des Verkäufers/Händlers
        return 'ASP Max Mustermann';
    }

    /*  BT-42
     *  Telefonnummer des Ansprechpartners oder der Kontaktstelle
     */
    public function sellerContactTelephone(): string
    {
//TODO: hier Ansprechpartner Telefonnummer des Verkäufers/Händlers
        return '07354 4385748';
    }

    /*  BT-43
     *  Eine E-Mail-Adresse des Ansprechpartners oder der Kontaktstelle.
     */
    public function sellerContactEmail(): string
    {
//TODO: hier Ansprechpartner eMail des Verkäufers/Händlers
        return 'salescontact@supplier.com';
    }


    /*  BT-49 -> Buyer electronic address/Scheme identifier
     *  Das Bildungsschema für "Seller electronic address" (BT-49). Es ist die Codeliste
     *  Electronic Address Scheme code list (EAS) zu verwenden. Die Codeliste wird von der
     *  Connecting Europe Facility gepflegt und herausgegeben.
     */
    public function buyerEletronicAdressScheme(): string
    {
//TODO: kriegt noch keine Parameter ($data ist leer) und liefert daher immer EM zurück
        $schemeId = match ([]) {
            #'Stück', 'Stk.', 'ST' => 'XPP',
            default => 'EM'             //eMail
        };
        return $schemeId;
    }

    /*  BT-31
     *  Seller VAT identifier
     *  Die Umsatzsteuer-Identifikationsnummer des Verkäufers.
     */
    public function sellerVATIdentifier(): string
    {
//TODO: den dynamischen Wert holen
        return 'DE270370361';
    }

    /*  Untergruppe von BT-31
     *
     *  Mandatory element. For Seller VAT identifier (BT-31), use value “VAT”, for the seller
     *  tax registration identifier (BT-32), use != "VAT"
     *  Example value: VAT
     */
    public function sellerTAXSchemeId(): string
    {
//TODO: dynamischen ermitteln
        return 'VAT';
    }
}