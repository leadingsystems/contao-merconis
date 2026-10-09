<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

require_once __DIR__ . '/../Support/EInvoicingFixtureLoader.php';

use DateTimeImmutable;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\InvoiceValidationException;
use LeadingSystems\MerconisBundle\EInvoicing\Service\SemanticInvoiceBuilder;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingFixtureLoader;
use PHPUnit\Framework\TestCase;

final class SemanticInvoiceBuilderTest extends TestCase
{
    /**
     * @dataProvider fixtureProvider
     *
     * @param list<float> $expectedTaxRates
     */
    public function testBuildsSemanticInvoiceFromStageFixture(
        string $fixtureId,
        string $invoiceNumber,
        int $expectedLineItemCount,
        int $expectedTaxSummaryCount,
        float $expectedLineTotalAmount,
        float $expectedTaxTotalAmount,
        float $expectedGrandTotalAmount,
        array $expectedTaxRates,
    ): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput($fixtureId);

        self::assertSame('Merconis GmbH', $shopSettings['ls_shop_einvoicingSellerName'] ?? null);
        self::assertSame('58', $paymentMethod['ls_shop_einvoicingPaymentMeansCode'] ?? null);
        self::assertSame('58', $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE] ?? null);

        foreach ($orderData['items'] as $item) {
            self::assertSame(
                'S',
                $item['extendedInfo'][EInvoicingSnapshotFields::ITEM_TAX_CATEGORY_CODE] ?? null
            );
        }

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

        self::assertSame('urn:cen.eu:en16931:2017', $invoice->guidelineId);
        self::assertSame('380', $invoice->invoiceTypeCode);
        self::assertSame($invoiceNumber, $invoice->invoiceNumber);
        self::assertSame('EUR', $invoice->currencyCode);
        self::assertSame('Merconis GmbH', $invoice->seller['name']);
        self::assertSame('LS-Vorname LS-Nachname', $invoice->buyer['name']);
        self::assertSame('58', $invoice->payment['meansCode']);
        self::assertCount($expectedLineItemCount, $invoice->lineItems);
        self::assertCount($expectedTaxSummaryCount, $invoice->taxSummaries);
        self::assertSame($expectedLineTotalAmount, $invoice->totals['lineTotalAmount']);
        self::assertSame($expectedTaxTotalAmount, $invoice->totals['taxTotalAmount']);
        self::assertSame($expectedGrandTotalAmount, $invoice->totals['grandTotalAmount']);
        self::assertSame($expectedGrandTotalAmount, $invoice->totals['duePayableAmount']);
        self::assertEqualsCanonicalizing(
            $expectedTaxRates,
            array_map(
                static fn (array $taxSummary): float => (float) $taxSummary['taxRate'],
                $invoice->taxSummaries
            )
        );
    }

    public function testBuildRejectsMissingRequiredSellerField(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        unset($shopSettings['ls_shop_einvoicingSellerName']);

        $this->expectException(InvoiceValidationException::class);
        $this->expectExceptionMessage('Der Verkäufername (BT-27) fehlt.');

        (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0001',
            new DateTimeImmutable('2026-10-08')
        );
    }

    public function testBuildUsesPaymentMeansCodeFromCheckoutSnapshot(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE] = '68';
        $paymentMethod['ls_shop_einvoicingPaymentMeansCode'] = '58';

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0068',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame('68', $invoice->payment['meansCode']);
    }

    public function testBuildUsesTaxCategoryFromCheckoutSnapshot(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $firstItemKey = array_key_first($orderData['items']);
        $taxClassId = (string) $orderData['items'][$firstItemKey]['taxClass'];

        $orderData['items'][$firstItemKey]['extendedInfo'][EInvoicingSnapshotFields::ITEM_TAX_CATEGORY_CODE] = 'AE';
        $taxRates[$taxClassId]['ls_shop_einvoicingTaxCategory'] = 'auto';

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0004',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame('AE', $invoice->lineItems[0]['taxCategoryCode']);
        self::assertSame('AE', $invoice->taxSummaries[0]['taxCategoryCode']);
    }

    public function testBuildRejectsMissingCheckoutSnapshotData(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $firstItemKey = array_key_first($orderData['items']);
        unset($orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE]);
        unset($orderData['items'][$firstItemKey]['extendedInfo'][EInvoicingSnapshotFields::ITEM_TAX_CATEGORY_CODE]);

        $this->expectException(InvoiceValidationException::class);
        $this->expectExceptionMessage('Checkout-Snapshot');

        (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0005',
            new DateTimeImmutable('2026-10-08')
        );
    }

    /**
     * @return iterable<string, array{string, string, int, int, float, float, float, list<float>}>
     */
    public static function fixtureProvider(): iterable
    {
        yield 'fixture-001' => ['S1-EINV-001', 'RE-2026-0001', 1, 1, 10.0, 1.9, 11.9, [19.0]];
        yield 'fixture-002' => ['S1-EINV-002', 'RE-2026-0002', 3, 1, 111.0, 21.09, 132.09, [19.0]];
        yield 'fixture-003' => ['S1-EINV-003', 'RE-2026-0003', 2, 2, 20.0, 2.6, 22.6, [7.0, 19.0]];
    }
}
