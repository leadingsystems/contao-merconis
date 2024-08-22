<?php
namespace Merconis\Core;


/*  Enthält Funktionen zur Nachbearbeitung von Daten aus dem arrOrder Array
 *
 */
class xrechnung_datatransformation
{

    public function ts2Date(?int $timestamp): string
    {
        return date('Y-m-d', $timestamp);
    }


    public function payment2Means(string $paymentTitle): string
    {
        //UNTDID 4461
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
/*
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
*/

    /*  BT-118, BT-151
     *  UNTDID 5305
     */
    public function vatCategoryCode(?string $anything): string
    {
//TODO: BT-118 Wie kommen wir von arrOrder auf den richtigen Code ?
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

abstract class eTaxCategories
{
    const STANDARDRATE = 'S';
    const ZERORATEDGOODS = 'Z';
}
*/
        $categoryCode = 'S';
        return $categoryCode;
    }
/*
    public function vatCategoryRate(?array $taxCategoryNode, ?array $additionalData): string
    {
        $taxCategory = $additionalData['taxCategory'];
        return $taxCategoryNode[$taxCategory]['taxRate'];
    }
*/

    public function taxSchemeVat(?string $anything): string
    {
//TODO: für Seller VAT identifier (BT-31) soll es "VAT" sein, für seller tax registration identifier (BT-32), soll es NICHT "VAT" sein
        $result = 'VAT';
        return $result;
    }

    public function calculateLineNetPrice()
    {

    }

}