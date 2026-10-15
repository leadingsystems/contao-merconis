<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

require_once __DIR__ . '/../Support/EInvoicingFixtureLoader.php';

use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use LeadingSystems\MerconisBundle\EInvoicing\Service\SemanticInvoiceBuilder;
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
            new DateTimeImmutable('2026-10-08')
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
        self::assertSame('58', $xpath->evaluate('string(//ram:SpecifiedTradeSettlementPaymentMeans/ram:TypeCode)'));
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
            $this->canonicalizeXml($this->normalizeReferenceXml(EInvoicingFixtureLoader::loadReferenceXml($fixtureId))),
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

    private function normalizeReferenceXml(string $xml): string
    {
        $xml = str_replace('unitCode="C62"', 'unitCode="H87"', $xml);

        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100');
        $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');

        $deliveryNode = $xpath->query('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeDelivery')->item(0);
        $existingShipTo = $xpath->query('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeDelivery/ram:ShipToTradeParty')->item(0);
        $buyerNode = $xpath->query('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeAgreement/ram:BuyerTradeParty')->item(0);

        if ($deliveryNode !== null && $existingShipTo === null && $buyerNode !== null) {
            $shipToNode = $document->createElementNS(
                'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100',
                'ram:ShipToTradeParty'
            );

            foreach (['Name', 'PostalTradeAddress'] as $childName) {
                $childNode = $xpath->query('ram:' . $childName, $buyerNode)->item(0);

                if ($childNode !== null) {
                    $shipToNode->appendChild($document->importNode($childNode, true));
                }
            }

            $deliveryNode->appendChild($shipToNode);
        }

        return (string) $document->saveXML($document->documentElement);
    }

    /**
     * @return iterable<string, array{string, string, string, int, list<string>}>
     */
    public static function fixtureProvider(): iterable
    {
        yield 'fixture-001' => ['S1-EINV-001', 'RE-2026-0001', '11.90', 1, ['19.00']];
        yield 'fixture-002' => ['S1-EINV-002', 'RE-2026-0002', '132.09', 3, ['19.00']];
        yield 'fixture-003' => ['S1-EINV-003', 'RE-2026-0003', '22.60', 2, ['19.00', '7.00']];
        yield 'fixture-004' => ['S4-EINV-001', 'RE-2026-0401', '10.00', 1, ['0.00']];
        yield 'fixture-005' => ['S4-EINV-002', 'RE-2026-0402', '11.90', 1, ['19.00']];
        yield 'fixture-006' => ['S4-EINV-003', 'RE-2026-0403', '10.00', 1, ['0.00']];
        yield 'fixture-007' => ['S4-EINV-004', 'RE-2026-0404', '10.00', 1, ['0.00']];
        yield 'fixture-008' => ['S4-EINV-006', 'RE-2026-0406', '10.00', 1, ['0.00']];
    }
}
