<?php
namespace Merconis\Core;


/*  Contains functions for calculating data
 *  Necessary if calculations have to be made and/or the one specified source field is not sufficient
 */
class xrechnung_calculations
{
    /*  Array containing all the data of a Merconis order
     *  @var    array
     */
    private $arrOrder = [];


    /*  Sets a reference to the order array.
     *
     *  @param  array   $arrOrder   byreference, Array containing all the data of a Merconis order
     *  @return void
     */
    public function setReference(array &$arrOrder): void
    {
        $this->arrOrder = &$arrOrder;
    }


    public function customizationId(): string
    {
//TODO: hier prüfen, ob und wie der String dynamisch zusammengebaut werden muss
        return 'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0';
    }

    public function businessProcessType(): string
    {
//TODO: hier prüfen, ob und wie der String dynamisch zusammengebaut werden muss
        return 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
    }

    /*  BT-5
     *  The currency in which all invoice amounts are stated, with the exception of the total sales tax amount.
     *  amount to be stated in the billing currency.
     *  Note: Only one currency is to be used in the invoice, the "Invoice total VAT amount in accounting
     *  currency" (BT-111) must be shown in the billing currency. The valid currencies are ISO 4217
     *  “Codes for the representation of currencies and funds” registered. Only the alpha 3 representation may be used
     *  become.
     *
     *  @return     string      $currencyCode       e.g. "EUR"
     */
    public function getCurrencyCode(): string
    {
        $currencyCode = $this->arrOrder['currency'];
        return $currencyCode;
    }

    /*  BT-82
     *  The expected or used means of payment expressed in text form. It is used as an XML attribute
     *  used in element BT-81 (therefore not used via source)
     *
     *  @return     string      $paymentMeansText
     */
    public function getPaymentMeansText(): string
    {
        $paymentMeansText = $this->arrOrder['paymentMethod_title'];
        return $paymentMeansText;
    }

    /*  BT-83
     *  A text value used to link the payment to the invoice issued by the seller.
     *  Note: Specifying a intended purpose helps the seller assign an incoming payment
     *  Payment for the respective payment process. If remittance information was provided in the invoice, then-
     *  te these can therefore be used when making the payment.
     *
     *  @return     string      $paymentMeansText
     */
    public function remittanceInformation(): string
    {
//TODO: den Wert dynamisch ermitteln
        return 'abc';
    }


    /*  @unitCode   bzw. Invoiced quantity unit of measure
     *
     *  The unit of measure that applies to the invoiced quantity. Codes for unit of
     *  packaging from UNECE Recommendation No. 21 can be used in accordance with the
     *  descriptions in the "Intro" section of UN/ECE Recommendation 20, Revision 11 (2015)
     *  https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cbc-InvoicedQuantity/
     *
     *  Assignment of our arrOrder quantityUnit to the 3-digit code from 'UNECE Recommendation No. 21´
     *  (e.g. for BT-129)
     *
     * @param   array   $additionalParams       contains groupkey of current repeating group
     * @return  string  $unitCode               string according to UN/ECE Recommendation 20, Revision 11
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
            default => 'XPP'
        };

        return $unitCode;
    }


    /*  BT-115
     *  The outstanding amount to be paid.
     *  Note: This amount is the "Invoice total amount with VAT" (BT-112) minus the "Paid amount"
     *  (BT-113). In the case of a fully paid invoice, this amount is zero. The amount is negative if
     *  the “Paid amount” (BT-113) is greater than the “Invoice total amount with VAT” (BT-112).
     *
     *  @param      float   $invoiceTotalAmountWithVat  invoicedAmount
     *  @return     string  $amountDueForPayment        The outstanding amount to be paid
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

    /*  Returns the current date.
     *
     *  e.g. for BT-2
     *  @return     string      current date e.g. 2024-08-30
     */
    public function currentDate(): string
    {
        return date('Y-m-d', time());
    }


    /*  BT-34
     *  Specifies the seller's electronic address to which the application layer response will be sent
     *  can be sent on an invoice
     */
    public function sellerEletronicAdress(): string
    {
//TODO: wo kriegen wir die eMail des Verkäufers her ?
        return 'supplier@web.de';
    }


    /*  BT-34 -> Seller electronic address/Scheme identifier
     *  The education scheme for "Seller electronic address" (BT-34). It's the code list
     *  Use Electronic Address Scheme code list (EAS). The code list is provided by the
     *  Connecting Europe Facility maintained and published.
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
     *  Information about the contact person or point of contact (such as the name of a person, department
     *  or office name)
     */
    public function sellerContactPoint(): string
    {
//TODO: hier Ansprechpartner Name des Verkäufers/Händlers
        return 'ASP Max Mustermann';
    }

    /*  BT-42
     *  Telephone number of the contact person or point of contact
     */
    public function sellerContactTelephone(): string
    {
//TODO: hier Ansprechpartner Telefonnummer des Verkäufers/Händlers
        return '07354 4385748';
    }

    /*  BT-43
     *  An email address of the contact person or point of contact.
     */
    public function sellerContactEmail(): string
    {
//TODO: hier Ansprechpartner eMail des Verkäufers/Händlers
        return 'salescontact@supplier.com';
    }


    /*  BT-49 -> Buyer electronic address/Scheme identifier
     *  The education scheme for "Seller electronic address" (BT-49). It's the code list
     *  Use Electronic Address Scheme code list (EAS). The code list is provided by the
     *  Connecting Europe Facility maintained and published.
     *
     *  @return     string      $schemeId      id of scheme
     */
    public function buyerEletronicAdressScheme(): string
    {
        $schemeId = match ([]) {
            #'Stück', 'Stk.', 'ST' => 'XPP',
            default => 'EM'             //eMail
        };
        return $schemeId;
    }


    /*  BT-44, BT-45
     *  Buyer Name, Buyer Trading Name
     *
     *  @return     string      $buyerName      firstname and lastname of buyer
     */
    public function buyerName(): string
    {
        $firstname = $this->arrOrder['customerData']['personalData']['firstname'];
        $lastname = $this->arrOrder['customerData']['personalData']['lastname'];
        $buyerName = ($firstname) ? $firstname.' ' : '';
        $buyerName .= $lastname;
        return $buyerName;
    }

    /*  BT-31
     *  Seller VAT identifier
     *  The seller's VAT number.
     */
    public function sellerVATIdentifier(): string
    {
//TODO: den dynamischen Wert holen
        return 'DE270370361';
    }

    /*  Subgroup of BT-31
     *
     *  Mandatory element. For Seller VAT identifier (BT-31), use value “VAT”, for the seller
     *  tax registration identifier (BT-32), use != "VAT"
     *  Example value: VAT
     */
    public function sellerTAXSchemeId(): string
    {
        return 'VAT';
    }


    /*  BT-11
     *  Project Reference
     */
    public function getProjectName(): string
    {
        return 'Dummy Projectname';
    }


}