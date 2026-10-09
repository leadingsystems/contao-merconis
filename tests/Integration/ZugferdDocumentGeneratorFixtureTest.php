<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

require_once __DIR__ . '/../Support/EInvoicingFixtureLoader.php';

use DateTimeImmutable;
use horstoeko\zugferd\ZugferdDocumentPdfReader;
use LeadingSystems\MerconisBundle\EInvoicing\Service\SemanticInvoiceBuilder;
use LeadingSystems\MerconisBundle\EInvoicing\Service\VisibleInvoiceHtmlRenderer;
use LeadingSystems\MerconisBundle\EInvoicing\Service\ZugferdDocumentGenerator;
use LeadingSystems\MerconisBundle\EInvoicing\Service\ZugferdHybridPdfGenerator;
use LeadingSystems\MerconisBundle\EInvoicing\Service\ZugferdXmlGenerator;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingFixtureLoader;
use PHPUnit\Framework\TestCase;

final class ZugferdDocumentGeneratorFixtureTest extends TestCase
{
    /**
     * @dataProvider fixtureProvider
     *
     * @param list<string> $expectedLineItemNames
     */
    public function testGeneratesHybridPdfForStageFixture(
        string $fixtureId,
        string $invoiceNumber,
        array $expectedLineItemNames,
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

        $htmlRenderer = new VisibleInvoiceHtmlRenderer();
        $visibleHtml = $htmlRenderer->render($invoice);

        self::assertStringContainsString($invoiceNumber, $visibleHtml);
        self::assertStringContainsString('Merconis GmbH', $visibleHtml);
        self::assertStringContainsString('LS-Vorname LS-Nachname', $visibleHtml);

        foreach ($expectedLineItemNames as $expectedLineItemName) {
            self::assertStringContainsString($expectedLineItemName, $visibleHtml);
        }

        $generatedDocument = (new ZugferdDocumentGenerator(
            new ZugferdXmlGenerator(),
            $htmlRenderer,
            new ZugferdHybridPdfGenerator(),
        ))->generate($invoice, $visibleHtml);

        self::assertSame('invoice-' . $invoiceNumber . '-zugferd.pdf', $generatedDocument->fileName);
        self::assertStringStartsWith('%PDF-', $generatedDocument->pdfContent);
        self::assertSame(
            trim($generatedDocument->xmlContent),
            trim(ZugferdDocumentPdfReader::getXmlFromContent($generatedDocument->pdfContent))
        );
    }

    /**
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function fixtureProvider(): iterable
    {
        yield 'fixture-001' => ['S1-EINV-001', 'RE-2026-0001', ['S1-EINV-001: Produkt 1: 19%']];
        yield 'fixture-002' => [
            'S1-EINV-002',
            'RE-2026-0002',
            [
                'S1-EINV-002: Produkt 1: 19%',
                'S1-EINV-002: Produkt 2: 19%',
                'S1-EINV-002: Produkt 3: 19%',
            ],
        ];
        yield 'fixture-003' => [
            'S1-EINV-003',
            'RE-2026-0003',
            [
                'S1-EINV-003: Produkt 1: 19%',
                'S1-EINV-003: Produkt 2: 7%',
            ],
        ];
    }
}
