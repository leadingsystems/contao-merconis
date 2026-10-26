<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

require_once __DIR__ . '/../Support/EInvoicingFixtureLoader.php';

use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use LeadingSystems\MerconisBundle\EInvoicing\Service\SemanticInvoiceBuilder;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use LeadingSystems\MerconisBundle\EInvoicing\Service\ZugferdXmlGenerator;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingFixtureLoader;
use PHPUnit\Framework\TestCase;

final class ZugferdXmlGeneratorFixtureTest extends TestCase
{
    /**
     * @dataProvider fixtureProvider
     *
     * @param list<string> $expectedTaxRates
     */
    public function testGeneratesCiiXmlForStageFixture(
        string $fixtureId,
        string $invoiceNumber,
        string $expectedGrandTotalAmount,
        int $expectedLineItemCount,
        array $expectedTaxRates,
        string $expectedPaymentMeansCode,
        string $invoiceDate,
    ): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput($fixtureId);

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            $invoiceNumber,
            new DateTimeImmutable($invoiceDate)
        );

        $xml = (new ZugferdXmlGenerator())->generate($invoice);

        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100');
        $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');

        self::assertSame('urn:cen.eu:en16931:2017', $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:ExchangedDocumentContext/ram:GuidelineSpecifiedDocumentContextParameter/ram:ID)'));
        self::assertSame($invoiceNumber, $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:ExchangedDocument/ram:ID)'));
        self::assertSame('380', $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:ExchangedDocument/ram:TypeCode)'));
        self::assertSame('Merconis GmbH', $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeAgreement/ram:SellerTradeParty/ram:Name)'));
        self::assertSame('LS-Vorname LS-Nachname', $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeAgreement/ram:BuyerTradeParty/ram:Name)'));
        self::assertSame($expectedPaymentMeansCode, $xpath->evaluate('string(//ram:SpecifiedTradeSettlementPaymentMeans/ram:TypeCode)'));
        self::assertSame($expectedGrandTotalAmount, $xpath->evaluate('string(//ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:GrandTotalAmount)'));
        self::assertSame((float) $expectedLineItemCount, $xpath->evaluate('count(//ram:IncludedSupplyChainTradeLineItem)'));
        self::assertSame(
            array_fill(0, $expectedLineItemCount, 'H87'),
            $this->xpathAttributeValues(
                $xpath,
                '//ram:IncludedSupplyChainTradeLineItem/ram:SpecifiedLineTradeDelivery/ram:BilledQuantity/@unitCode'
            )
        );
        self::assertEqualsCanonicalizing(
            $expectedTaxRates,
            $this->xpathStrings($xpath, '//ram:ApplicableHeaderTradeSettlement/ram:ApplicableTradeTax/ram:RateApplicablePercent')
        );
        self::assertSame(
            $this->canonicalizeXml(EInvoicingFixtureLoader::loadReferenceXml($fixtureId)),
            $this->canonicalizeXml($xml)
        );
    }

    public function testGeneratesShipToAddressForDeviantShippingAddress(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S4-EINV-002');

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0402',
            new DateTimeImmutable('2026-10-14')
        );

        $xml = (new ZugferdXmlGenerator())->generate($invoice);
        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100');
        $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');

        self::assertSame(
            'DE',
            $xpath->evaluate(
                'string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeDelivery/ram:ShipToTradeParty/ram:PostalTradeAddress/ram:CountryID)'
            )
        );
    }

    public function testGeneratesSeparateSevenPercentTaxSummaryForMinimumDynamicShippingRate(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S2-EINV-010');

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0210',
            new DateTimeImmutable('2026-10-08')
        );

        $xml = (new ZugferdXmlGenerator())->generate($invoice);
        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100');
        $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');

        self::assertSame(
            '7.00',
            $xpath->evaluate(
                'string(//ram:SpecifiedTradeAllowanceCharge/ram:CategoryTradeTax/ram:RateApplicablePercent)'
            )
        );
        self::assertSame(
            '15.90',
            $xpath->evaluate(
                'string(//ram:ApplicableHeaderTradeSettlement/ram:ApplicableTradeTax[ram:RateApplicablePercent="7.00"]/ram:BasisAmount)'
            )
        );
        self::assertSame(
            '10.00',
            $xpath->evaluate(
                'string(//ram:ApplicableHeaderTradeSettlement/ram:ApplicableTradeTax[ram:RateApplicablePercent="19.00"]/ram:BasisAmount)'
            )
        );
        self::assertSame(
            '3.01',
            $xpath->evaluate('string(//ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount)')
        );
    }

    public function testGeneratesDeliveryDateAndExemptionReasonForIntraCommunitySupply(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S4-EINV-001');

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0401',
            new DateTimeImmutable('2026-10-14')
        );

        $xml = (new ZugferdXmlGenerator())->generate($invoice);
        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100');
        $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');
        $xpath->registerNamespace('udt', 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100');

        self::assertSame(
            '20261014',
            $xpath->evaluate(
                'string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeDelivery/ram:ActualDeliverySupplyChainEvent/ram:OccurrenceDateTime/udt:DateTimeString)'
            )
        );
        self::assertSame(
            'Intra-community supply',
            $xpath->evaluate(
                'string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeSettlement/ram:ApplicableTradeTax/ram:ExemptionReason)'
            )
        );
    }

    public function testGeneratesSepaDirectDebitPaymentMeansWithMandateData(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY] = '59';
        $orderData[EInvoicingSnapshotFields::ORDER_MANDATE_REFERENCE_ORDER_KEY] = 'MANDATE-2026-0001';
        $orderData[EInvoicingSnapshotFields::ORDER_DEBTOR_IBAN_ORDER_KEY] = 'DE44500105175407324931';
        $paymentMethod[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] = '59';
        $shopSettings[EInvoicingSnapshotFields::SHOP_SETTINGS_CREDITOR_ID_FIELD] = 'DE98ZZZ09999999999';
        $shopSettings['ls_shop_einvoicingSellerIban'] = 'DE89370400440532013000';
        $shopSettings['ls_shop_einvoicingSellerBic'] = 'COBADEFFXXX';

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0059',
            new DateTimeImmutable('2026-10-08')
        );

        $xml = (new ZugferdXmlGenerator())->generate($invoice);
        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100');
        $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');

        self::assertSame('59', $xpath->evaluate('string(//ram:SpecifiedTradeSettlementPaymentMeans/ram:TypeCode)'));
        self::assertSame('DE98ZZZ09999999999', $xpath->evaluate('string(//ram:ApplicableHeaderTradeSettlement/ram:CreditorReferenceID)'));
        self::assertSame('DE44500105175407324931', $xpath->evaluate('string(//ram:SpecifiedTradeSettlementPaymentMeans/ram:PayerPartyDebtorFinancialAccount/ram:IBANID)'));
        self::assertSame('DE89370400440532013000', $xpath->evaluate('string(//ram:SpecifiedTradeSettlementPaymentMeans/ram:PayeePartyCreditorFinancialAccount/ram:IBANID)'));
        self::assertSame('COBADEFFXXX', $xpath->evaluate('string(//ram:SpecifiedTradeSettlementPaymentMeans/ram:PayeeSpecifiedCreditorFinancialInstitution/ram:BICID)'));
        self::assertSame('MANDATE-2026-0001', $xpath->evaluate('string(//ram:SpecifiedTradePaymentTerms/ram:DirectDebitMandateID)'));
    }

    /**
     * @return list<string>
     */
    private function xpathStrings(DOMXPath $xpath, string $expression): array
    {
        $values = [];

        foreach ($xpath->query($expression) ?: [] as $node) {
            $values[] = trim($node->textContent);
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    private function xpathAttributeValues(DOMXPath $xpath, string $expression): array
    {
        $values = [];

        foreach ($xpath->query($expression) ?: [] as $node) {
            $values[] = trim($node->textContent);
        }

        return $values;
    }

    private function canonicalizeXml(string $xml): string
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->loadXML($xml);

        return (string) $document->C14N();
    }

    /**
     * @return iterable<string, array{string, string, string, int, list<string>, string, string}>
     */
    public static function fixtureProvider(): iterable
    {
        yield 'fixture-001' => ['S1-EINV-001', 'RE-2026-0001', '11.90', 1, ['19.00'], '58', '2026-10-08'];
        yield 'fixture-002' => ['S1-EINV-002', 'RE-2026-0002', '132.09', 3, ['19.00'], '58', '2026-10-08'];
        yield 'fixture-003' => ['S1-EINV-003', 'RE-2026-0003', '22.60', 2, ['19.00', '7.00'], '58', '2026-10-08'];
        yield 'fixture-004' => ['S2-EINV-006', 'RE-2026-0206', '18.92', 1, ['19.00'], '58', '2026-10-08'];
        yield 'fixture-005' => ['S2-EINV-010', 'RE-2026-0210', '28.91', 2, ['19.00', '7.00'], '58', '2026-10-08'];
        yield 'fixture-006' => ['S3-EINV-001', '2026102677', '11.90', 1, ['19.00'], '59', '2026-10-26'];
        yield 'fixture-007' => ['S3-EINV-002', '2026102678', '11.90', 1, ['19.00'], '68', '2026-10-26'];
        yield 'fixture-008' => ['S4-EINV-001', 'RE-2026-0401', '10.00', 1, ['0.00'], '58', '2026-10-08'];
        yield 'fixture-009' => ['S4-EINV-002', 'RE-2026-0402', '11.90', 1, ['19.00'], '58', '2026-10-08'];
        yield 'fixture-010' => ['S4-EINV-003', 'RE-2026-0403', '10.00', 1, ['0.00'], '58', '2026-10-08'];
        yield 'fixture-011' => ['S4-EINV-004', 'RE-2026-0404', '10.00', 1, ['0.00'], '58', '2026-10-08'];
        yield 'fixture-012' => ['S4-EINV-006', 'RE-2026-0406', '10.00', 1, ['0.00'], '58', '2026-10-08'];
    }
}
