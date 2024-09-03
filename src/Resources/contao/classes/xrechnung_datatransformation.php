<?php
namespace Merconis\Core;


/*  Enthält Funktionen zur Nachbearbeitung von Daten aus dem arrOrder Array
 *
 */
class xrechnung_datatransformation
{

    /*  Anzahl Nachkommastellen beim Datentyp "Unit Price Amount" (Kapitel 8.10)
     */
    const UNITPRICEAMOUNT_DECIMALS = 2;


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
    public function payment2Means(string $paymentAlias): string
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
        $paymentMeansCode = match ($paymentAlias) {
            'paypal' => '68',                                   // =    Online payment service
            'paypal-plus' => '68',                              // =    Online payment service
            'PayPal Checkout', 'paypal-checkout' => '68',       // =    Online payment service
            'saferpay' => '68',                                 // =    Online payment service
            'payone' => '68',                                   // =    Online payment service
            'santander-finanzierung' => '68',                   // =    Online payment service
            'sofort' => '68',                                   // =    Online payment service
            'vr-pay' => '54',                                   // =    Credit Card
            'Vorkasse', 'Vorauskasse.', 'vorkasse', 'vorauskasse' => '30',
            'Lastschrift', 'lastschrift' => '30',
            default => '30'             // = (Credit transfer (non-SEPA)
        };
        return $paymentMeansCode;
    }


    /*  BT-3
     *  A code that indicates the function type of the invoice.
     *  Note: The invoice type must be specified according to UNTDID 1001.
     *  @return     string      $invoiceTypeCode        code for function of invoice
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


    /*
     *
     *  @return     string      $result        either ´VAT´ or not ´VAT´
     */
    public function taxSchemeVat(): string
    {
//TODO: Vorgabe: für Seller VAT identifier (BT-31) soll es "VAT" sein,
// für seller tax registration identifier (BT-32), soll es NICHT "VAT" sein
        $result = 'VAT';
        return $result;
    }


    /*  Formats the passed amount according to the “Unit Price Amount” data type
     *
     *  @param      mixed       $amount     float/string value to be formatted as number
     *  @param      int         $decimals   number of decimals
     *  @return     string                  formatted amount
     */
    public static function format_unitPriceAmount(mixed $amount, ?int $decimals = null): string
    {
        $amount = (float) $amount;

        $decimals = $decimals ?? self::UNITPRICEAMOUNT_DECIMALS;

//TODO: könnte hier ls_shop_generalHelper::outputPrice oder ls_shop_generalHelper::getDisplayPrice verwendet werden ?
        $decimalsSeparator = ($GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] ?? '.');
        $thousandsSeparator = ($GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] ?? '');
        return number_format($amount, $decimals, $decimalsSeparator, $thousandsSeparator);
    }


    /*  Removes HTML tags and breaks from the passed text
     *  E.g. BT-20_SUB-1
     *  @param      string      $source     string to be cleared
     *  @return     string      $result     xml compatible text (without tags/line feeds)
     */
    public function replaceTags(string $source): string
    {
        $result = strip_tags($source);
        $result = str_replace(array("\r", "\n"), ' ', $result);
        return $result;
    }


    /*  Usage of built-in php function strtoupper
     *  E.g. BT-55 (country code "de" to "DE")
     *  @param      string      $source     string to be changed
     *  @return     string      $result     ucase string
     */
    public function strtoupper(string $source): string
    {
        return strtoupper($source);
    }

}