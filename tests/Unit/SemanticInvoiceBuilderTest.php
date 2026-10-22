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
        string $expectedTaxCategoryCode,
        string $expectedBuyerCountry,
        string $expectedShipToCountry,
        string $expectedPaymentTermsDays,
        string $expectedPaymentTermsNote,
    ): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput($fixtureId);

        self::assertSame('Merconis GmbH', $shopSettings['ls_shop_einvoicingSellerName'] ?? null);
        self::assertSame('58', $paymentMethod[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] ?? null);
        self::assertSame('58', $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY] ?? null);
        self::assertSame($expectedPaymentTermsDays, $paymentMethod['einvoicingPaymentTermsDays'] ?? null);
        self::assertSame($expectedPaymentTermsNote, $paymentMethod['einvoicingPaymentTermsNote'] ?? null);

        foreach ($orderData['items'] as $item) {
            self::assertSame(
                $expectedTaxCategoryCode,
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
        self::assertSame($expectedBuyerCountry, $invoice->buyer['countryCode']);
        self::assertSame($expectedShipToCountry, $invoice->shipTo['countryCode']);
        self::assertSame('58', $invoice->payment['meansCode']);
        self::assertCount($expectedLineItemCount, $invoice->lineItems);
        self::assertSame('H87', $invoice->lineItems[0]['unitCode']);
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
        $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY] = '68';
        $paymentMethod[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] = '58';

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

    public function testBuildUsesPaymentMethodTermsWhenMemberGroupOverrideIsEmpty(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0014',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame('Zahlbar innerhalb von 14 Tagen netto.', $invoice->payment['termsNote']);
        self::assertSame('2026-10-22', $invoice->payment['dueDate']?->format('Y-m-d'));
    }

    public function testBuildUsesPaymentMethodTermsWhenMemberGroupIsMissing(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            null,
            'RE-2026-0015',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame('Zahlbar innerhalb von 14 Tagen netto.', $invoice->payment['termsNote']);
        self::assertSame('2026-10-22', $invoice->payment['dueDate']?->format('Y-m-d'));
    }

    public function testBuildUsesMemberGroupOverrideAndKeepsZeroAsValidValue(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $memberGroup['lsShopEinvoicingPaymentTermsDays'] = '0';
        $memberGroup['lsShopEinvoicingPaymentTermsNote_de'] = 'Sofort fällig.';

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0000',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame('Sofort fällig.', $invoice->payment['termsNote']);
        self::assertSame('2026-10-08', $invoice->payment['dueDate']?->format('Y-m-d'));
    }

    public function testBuildUsesTaxCategoryFromCheckoutSnapshot(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $firstItemKey = array_key_first($orderData['items']);
        $taxClassId = (string) $orderData['items'][$firstItemKey]['taxClass'];

        $orderData['items'][$firstItemKey]['extendedInfo'][EInvoicingSnapshotFields::ITEM_TAX_CATEGORY_CODE] = 'AE';
        $taxRates[$taxClassId][EInvoicingSnapshotFields::TAX_CONFIGURATION_CATEGORY_FIELD] = 'auto';

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

    public function testBuildUsesUnitCodeFromCheckoutSnapshot(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $firstItemKey = array_key_first($orderData['items']);
        $orderData['items'][$firstItemKey]['extendedInfo'][EInvoicingSnapshotFields::ITEM_UNIT_CODE] = 'KGM';

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0200',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame('KGM', $invoice->lineItems[0]['unitCode']);
    }

    public function testBuildUsesShipToAddressFromDeviantShippingAddress(): void
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

        self::assertSame('FR', $invoice->buyer['countryCode']);
        self::assertSame('DE', $invoice->shipTo['countryCode']);
    }

    public function testBuildAddsDeliveryDateAndExemptionReasonForIntraCommunitySupply(): void
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

        self::assertNotNull($invoice->deliveryDate);
        self::assertSame('2026-10-14', $invoice->deliveryDate?->format('Y-m-d'));
        self::assertSame('Intra-community supply', $invoice->taxSummaries[0]['exemptionReason']);
    }

    public function testBuildUsesMerchantDeliveryDateOverrideForBt72(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData['einvoicingDeliveryDate'] = (new DateTimeImmutable('2026-10-09'))->getTimestamp();

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0072',
            new DateTimeImmutable('2026-10-14')
        );

        self::assertSame('2026-10-09', $invoice->deliveryDate?->format('Y-m-d'));
    }

    public function testBuildFallsBackToInvoiceDateWhenMerchantDeliveryDateOverrideIsEmpty(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData['einvoicingDeliveryDate'] = '';

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0073',
            new DateTimeImmutable('2026-10-14')
        );

        self::assertSame('2026-10-14', $invoice->deliveryDate?->format('Y-m-d'));
    }

    public function testBuildFallsBackToInvoiceDateWhenMerchantDeliveryDateOverrideIsZeroOrMissing(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData['einvoicingDeliveryDate'] = 0;

        $invoiceWithZeroOverride = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0074',
            new DateTimeImmutable('2026-10-14')
        );

        $orderData['einvoicingDeliveryDate'] = null;

        $invoiceWithMissingOverride = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0075',
            new DateTimeImmutable('2026-10-14')
        );

        self::assertSame('2026-10-14', $invoiceWithZeroOverride->deliveryDate?->format('Y-m-d'));
        self::assertSame('2026-10-14', $invoiceWithMissingOverride->deliveryDate?->format('Y-m-d'));
    }

    public function testBuildUsesDocumentChargeTaxCategoryFromCheckoutSnapshot(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_TAX_CATEGORY_ORDER_KEY] = 'AE';
        $orderData['paymentMethod_amount'] = '5.0000';
        $orderData['paymentMethod_amountTaxedWith'] = [
            '2' => [
                'taxRate' => '19',
                'amountTaxedHerewith' => 5,
            ],
        ];
        $paymentMethod['feeType'] = 'fix';

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0403',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertCount(1, $invoice->documentCharges);
        self::assertSame('AE', $invoice->documentCharges[0]['taxCategoryCode']);
    }

    public function testBuildRejectsRuleSixFixture(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S4-EINV-005');

        $this->expectException(InvoiceValidationException::class);
        $this->expectExceptionMessage('Steuerkategorie');

        (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0405',
            new DateTimeImmutable('2026-10-14')
        );
    }

    public function testBuildRejectsMissingCheckoutSnapshotData(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $firstItemKey = array_key_first($orderData['items']);
        unset($orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY]);
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
     * @return iterable<string, array{string, string, int, int, float, float, float, list<float>, string, string, string, string, string}>
     */
    public static function fixtureProvider(): iterable
    {
        yield 'fixture-001' => ['S1-EINV-001', 'RE-2026-0001', 1, 1, 10.0, 1.9, 11.9, [19.0], 'S', 'DE', 'DE', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-002' => ['S1-EINV-002', 'RE-2026-0002', 3, 1, 111.0, 21.09, 132.09, [19.0], 'S', 'DE', 'DE', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-003' => ['S1-EINV-003', 'RE-2026-0003', 2, 2, 20.0, 2.6, 22.6, [7.0, 19.0], 'S', 'DE', 'DE', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-004' => ['S2-EINV-010', 'RE-2026-0210', 2, 2, 20.0, 3.01, 28.91, [7.0, 19.0], 'S', 'DE', 'DE', '12', 'Zahlbar in 12 Tagen.'];
        yield 'fixture-005' => ['S4-EINV-001', 'RE-2026-0401', 1, 1, 10.0, 0.0, 10.0, [0.0], 'K', 'FR', 'FR', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-006' => ['S4-EINV-002', 'RE-2026-0402', 1, 1, 10.0, 1.9, 11.9, [19.0], 'S', 'FR', 'DE', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-007' => ['S4-EINV-003', 'RE-2026-0403', 1, 1, 10.0, 0.0, 10.0, [0.0], 'G', 'CH', 'CH', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-008' => ['S4-EINV-004', 'RE-2026-0404', 1, 1, 10.0, 0.0, 10.0, [0.0], 'Z', 'DE', 'DE', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-009' => ['S4-EINV-006', 'RE-2026-0406', 1, 1, 10.0, 0.0, 10.0, [0.0], 'G', 'FR', 'CH', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
    }
}
