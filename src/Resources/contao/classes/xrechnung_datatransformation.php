<?php
namespace Merconis\Core;


/*  Enthält Funktionen zur Nachbearbeitung von Daten aus dem arrOrder Array
 *
 */
class xrechnung_datatransformation
{

    /*  Anzahl Nachkommastellen beim Datentyp "Unit Price Amount" (Kapitel 8.10)
     */
    const UNITPRICEAMOUNT_DECIMALS = 4;


    public function ts2Date(?int $timestamp): string
    {
        return date('Y-m-d', $timestamp);
    }


    /*  BT-81
     *  Das als Code ausgedrückte erwartete oder genutzte Zahlungsmittel. Hierzu wird auf die Codeliste UNTDID 4461
     *  verwiesen.
     *  UNTDID 4461
     *
     */
    public function payment2Means(string $paymentTitle): string
    {
//TODO: hier soll man anhand des paymentTitles (aus arrOrder) auf den richtigen Code kommen - VERVOLLSTÄNDIGEN
/*
1	Instrument not defined
2	Automated clearing house credit
3	Automated clearing house debit
4	ACH demand debit reversal
5	ACH demand credit reversal
6	ACH demand credit
7	ACH demand debit
8	Hold
9	National or regional clearing
10	In cash
11	ACH savings credit reversal
12	ACH savings debit reversal
13	ACH savings credit
14	ACH savings debit
15	Bookentry credit
16	Bookentry debit
17	ACH demand cash concentration/disbursement (CCD) credit
18	ACH demand cash concentration/disbursement (CCD) debit
19	ACH demand corporate trade payment (CTP) credit
20	Cheque
21	Banker's draft
22	Certified banker's draft
23	Bank cheque (issued by a banking or similar establishment)
24	Bill of exchange awaiting acceptance
25	Certified cheque
26	Local cheque
27	ACH demand corporate trade payment (CTP) debit
28	ACH demand corporate trade exchange (CTX) credit
29	ACH demand corporate trade exchange (CTX) debit
30	Credit transfer
31	Debit transfer
32	ACH demand cash concentration/disbursement plus (CCD+) credit
33	ACH demand cash concentration/disbursement plus (CCD+) debit
34	ACH prearranged payment and deposit (PPD)
35	ACH savings cash concentration/disbursement (CCD) credit
36	ACH savings cash concentration/disbursement (CCD) debit
37	ACH savings corporate trade payment (CTP) credit
38	ACH savings corporate trade payment (CTP) debit
39	ACH savings corporate trade exchange (CTX) credit
40	ACH savings corporate trade exchange (CTX) debit
41	ACH savings cash concentration/disbursement plus (CCD+) credit
42	Payment to bank account
43	ACH savings cash concentration/disbursement plus (CCD+) debit
44	Accepted bill of exchange
45	Referenced home-banking credit transfer
46	Interbank debit transfer
47	Home-banking debit transfer
48	Bank card
49	Direct debit
50	Payment by postgiro
51	FR, norme 6 97-Telereglement CFONB (French Organisation for Banking Standards) - Option A
52	Urgent commercial payment
53	Urgent Treasury Payment
54	Credit card
55	Debit card
56	Bankgiro
57	Standing agreement
58	SEPA credit transfer
59	SEPA direct debit
60	Promissory note
61	Promissory note signed by the debtor
62	Promissory note signed by the debtor and endorsed by a bank
63	Promissory note signed by the debtor and endorsed by a third party
64	Promissory note signed by a bank
65	Promissory note signed by a bank and endorsed by another bank
66	Promissory note signed by a third party
67	Promissory note signed by a third party and endorsed by a bank
68	Online payment service
69	Transfer Advice
70	Bill drawn by the creditor on the debtor
74	Bill drawn by the creditor on a bank
75	Bill drawn by the creditor, endorsed by another bank
76	Bill drawn by the creditor on a bank and endorsed by a third party
77	Bill drawn by the creditor on a third party
78	Bill drawn by creditor on third party, accepted and endorsed by bank
91	Not transferable banker's draft
92	Not transferable local cheque
93	Reference giro
94	Urgent giro
95	Free format giro
96	Requested method for payment was not used
97	Clearing between partners
ZZZ	Mutually defined
*/
        $paymentMeans = match ($paymentTitle) {
            'PayPal Checkout' => '30',
            'Vorkasse', 'Vorauskasse.' => '30',
            default => '30'
        };
        return $paymentMeans;
    }


    public function customizationId(): string
    {
//TODO: hier prüfen, wie der String dynamisch zusammengebaut werden muss
        return 'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0';
    }

    /*  BT-3
     *  Ein Code, der den Funktionstyp der Rechnung angibt.
     *  Anmerkung: Der Rechnungstyp muss gemäß UNTDID 1001, spezifiziert werden.
     */
    public function invoiceTypeCode(): string
    {
/*      UNTDID 1001
         326 (Partial invoice)
        •  380 (Commercial invoice)
        •  384 (Corrected invoice)
        •  389 (Self-billed invoice)
        •  381 (Credit note)
        •  875 (Partial construction invoice)
        •  876 (Partial final construction invoice)
        •  877 (Final construction invoice)
*/
        //ISSUE: 23.08.2024
        //https://lsboard.de/project/18/task/6395#comment-3691
        $invoiceTypeCode = '380';

        return $invoiceTypeCode;
    }


    /*  BT-118, BT-151
     *  UNTDID 5305
     */
/*
    public function vatCategoryCode(): string
    {
//TODO: BT-118 Wie kommen wir von arrOrder auf den richtigen Code ?

        $categoryCode = 'S';
        return $categoryCode;
    }
*/
/*      UNTDID 5305
•  S (Standard rate)
•  Z (Zero rated goods)
•  E (Exempt from tax)
•  AE (VAT Reverse Charge)
•  K (VAT exempt for EEA intra-community supply of goods and services)
•  G (Free export item, tax not charged)
•  O (Services outside scope of tax)
•  L (Canary Islands general indirect tax)
•  M (Tax for production, services and importation in Ceuta and Melilla)
*/


    public function taxSchemeVat(): string
    {
//TODO: Vorgabe: für Seller VAT identifier (BT-31) soll es "VAT" sein,
// für seller tax registration identifier (BT-32), soll es NICHT "VAT" sein
        $result = 'VAT';
        return $result;
    }


    /*  Formatiert den übergebenen Betrag nach dem Datentyp "Unit Price Amount"
     *
     * */
    public static function format_unitPriceAmount(float $amount): string
    {
//TODO: könnte hier ls_shop_generalHelper::outputPrice oder ls_shop_generalHelper::getDisplayPrice verwendet werden ?
        $decimalsSeparator = ($GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] ?? '.');
        $thousandsSeparator = ($GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] ?? '');
        return number_format($amount, self::UNITPRICEAMOUNT_DECIMALS, $decimalsSeparator, $thousandsSeparator);;
    }

    /*  BT-146:
     *  Berechnung item net price für die Rechnungszeile
     *  Es wird der Bruttopreis genommen und durch die MwSt dividiert.
     *  Unit Price Amount
     */
    public function calculateLineNetPrice(array $items, array $additionalParams): string
    {
//TODO: ist das was in arrOrder[items][1][price] steht immer der Bruttopreis ? Wenn nein, dann muss hier unterschieden werden
        $invoiceLine = $items[$additionalParams['groupKey']];

        $netPrice = 100 * $invoiceLine['price'] / (100 + (float) $invoiceLine['taxPercentage']);

        return $this->format_unitPriceAmount($netPrice);
    }


    /*  BT-147:
     *  Berechnung Item price discount für die Rechnungszeile
     *  Es wird der Bruttopreis genommen und mit der MwSt multipliziert.
     *  Unit Price Amount
     */
    public function calculateLineDiscount(array $items, array $additionalParams): string
    {
//TODO: ist das was in arrOrder[items][1][price] steht immer der Bruttopreis ? Wenn nein, dann muss hier unterschieden werden
        $invoiceLine = $items[$additionalParams['groupKey']];

        $discount = $invoiceLine['price'] * ((float) $invoiceLine['taxPercentage'] / 100);

        return $this->format_unitPriceAmount($discount);
    }

}