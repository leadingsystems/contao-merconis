<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

require_once __DIR__ . '/../Support/EInvoicingFixtureLoader.php';

use DateTimeImmutable;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\InvoiceValidationException;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReason;
use LeadingSystems\MerconisBundle\EInvoicing\Service\SemanticInvoiceBuilder;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingFixtureLoader;
use PHPUnit\Framework\Attributes\DataProvider;
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
        string $expectedPaymentMeansCode,
        string $expectedTaxCategoryCode,
        string $expectedBuyerCountry,
        string $expectedShipToCountry,
        string $expectedPaymentTermsDays,
        string $expectedPaymentTermsNote,
    ): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput($fixtureId);

        self::assertSame('Merconis GmbH', $shopSettings['ls_shop_einvoicingSellerName'] ?? null);
        self::assertSame($expectedPaymentMeansCode, $paymentMethod[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] ?? null);
        self::assertSame($expectedPaymentMeansCode, $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY] ?? null);
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
        self::assertSame($expectedPaymentMeansCode, $invoice->payment['meansCode']);
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

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0001',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [[EInvoicingErrorCode::MISSING_BT_27, []]],
            $this->describeFailureReasons($failureReasons)
        );
    }

    public function testBuildReportsMultipleMissingSellerFieldsAsIndividualReasons(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        unset($shopSettings['ls_shop_einvoicingSellerStreet']);
        unset($shopSettings['ls_shop_einvoicingSellerCity']);
        $shopSettings['ls_shop_einvoicingSellerElectronicAddressScheme'] = 'XX';

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0001',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [
                [EInvoicingErrorCode::INVALID_BT_34_1, ['scheme' => 'XX']],
                [EInvoicingErrorCode::MISSING_BT_35, []],
                [EInvoicingErrorCode::MISSING_BT_37, []],
            ],
            $this->describeFailureReasons($failureReasons)
        );
    }

    public function testBuildReportsMissingElectronicAddressSchemeSeparatelyFromInvalidScheme(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        unset($shopSettings['ls_shop_einvoicingSellerElectronicAddressScheme']);

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0001',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [[EInvoicingErrorCode::MISSING_BT_34_1, []]],
            $this->describeFailureReasons($failureReasons)
        );
    }

    public function testBuildReportsMissingVatExemptionStatusOnlyOnce(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        unset($orderData[EInvoicingSnapshotFields::ORDER_VAT_EXEMPTION_STATUS_ORDER_KEY]);

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0001',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [[EInvoicingErrorCode::SNAPSHOT_MISSING_VAT_EXEMPTION_STATUS, []]],
            $this->describeFailureReasons($failureReasons)
        );
    }

    public function testBuildReportsInvalidVatExemptionStatus(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData[EInvoicingSnapshotFields::ORDER_VAT_EXEMPTION_STATUS_ORDER_KEY] = 'unknownStatus';

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0001',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [[EInvoicingErrorCode::SNAPSHOT_INVALID_VAT_EXEMPTION_STATUS, []]],
            $this->describeFailureReasons($failureReasons)
        );
    }

    public function testBuildReportsLineItemQuantityAsMissingOrInvalidWithLineId(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-002');
        $itemKeys = array_keys($orderData['items']);
        unset($orderData['items'][$itemKeys[0]]['quantity']);
        $orderData['items'][$itemKeys[1]]['quantity'] = 0;

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0002',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [
                [EInvoicingErrorCode::MISSING_BT_129, ['lineId' => (string) $itemKeys[0]]],
                [EInvoicingErrorCode::INVALID_BT_129, ['lineId' => (string) $itemKeys[1]]],
            ],
            $this->describeFailureReasons($failureReasons)
        );
    }

    public function testBuildReportsMissingLineItemTaxRateNameAndPriceWithLineId(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-002');
        $itemKeys = array_keys($orderData['items']);
        unset($orderData['items'][$itemKeys[0]]['taxPercentage']);
        unset(
            $orderData['items'][$itemKeys[1]]['extendedInfo']['_productTitle_customerLanguage'],
            $orderData['items'][$itemKeys[1]]['extendedInfo']['_productTitle'],
            $orderData['items'][$itemKeys[1]]['productTitle'],
            $orderData['items'][$itemKeys[1]]['artNr']
        );
        unset($orderData['items'][$itemKeys[2]]['price']);

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0002',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [
                [EInvoicingErrorCode::MISSING_BT_152, ['lineId' => (string) $itemKeys[0]]],
                [EInvoicingErrorCode::MISSING_BT_153, ['lineId' => (string) $itemKeys[1]]],
                [EInvoicingErrorCode::MISSING_BT_146, ['lineId' => (string) $itemKeys[2]]],
                [EInvoicingErrorCode::MISSING_BG_25, []],
                [EInvoicingErrorCode::MISSING_BG_23, []],
            ],
            $this->describeFailureReasons($failureReasons)
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

    public function testBuildAllowsOnlinePaymentWithoutSellerIban(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY] = '68';
        $paymentMethod[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] = '68';
        unset($shopSettings['ls_shop_einvoicingSellerIban']);

        $invoice = (new SemanticInvoiceBuilder())->build(
            $orderData,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates,
            $memberGroup,
            'RE-2026-0069',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame('68', $invoice->payment['meansCode']);
        self::assertNull($invoice->payment['iban']);
    }

    public function testBuildValidatesSepaDirectDebitSnapshotFields(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY] = '59';
        $paymentMethod[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] = '59';
        $orderData[EInvoicingSnapshotFields::ORDER_MANDATE_REFERENCE_ORDER_KEY] = 'MANDATE-2026-0001';
        $shopSettings[EInvoicingSnapshotFields::SHOP_SETTINGS_CREDITOR_ID_FIELD] = 'DE98ZZZ09999999999';
        $orderData[EInvoicingSnapshotFields::ORDER_DEBTOR_IBAN_ORDER_KEY] = 'DE44500105175407324931';

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

        self::assertSame('59', $invoice->payment['meansCode']);
        self::assertSame('MANDATE-2026-0001', $invoice->payment['mandateReference']);
        self::assertSame('DE98ZZZ09999999999', $invoice->payment['creditorId']);
        self::assertSame('DE44500105175407324931', $invoice->payment['debtorIban']);
    }

    public function testBuildRejectsSepaDirectDebitWithoutMandatorySnapshotFields(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY] = '59';
        $paymentMethod[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] = '59';
        unset($orderData[EInvoicingSnapshotFields::ORDER_MANDATE_REFERENCE_ORDER_KEY]);
        unset($shopSettings[EInvoicingSnapshotFields::SHOP_SETTINGS_CREDITOR_ID_FIELD]);
        unset($orderData[EInvoicingSnapshotFields::ORDER_DEBTOR_IBAN_ORDER_KEY]);

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0059',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [
                [EInvoicingErrorCode::MISSING_BT_89, ['paymentMeansCode' => '59']],
                [EInvoicingErrorCode::MISSING_BT_90, ['paymentMeansCode' => '59']],
                [EInvoicingErrorCode::MISSING_BT_91, ['paymentMeansCode' => '59']],
            ],
            $this->describeFailureReasons($failureReasons)
        );
    }

    /**
     * @dataProvider unsupportedCardPaymentMeansProvider
     */
    public function testBuildRejectsUnsupportedCardPaymentMeansCodes(string $meansCode): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY] = $meansCode;
        $paymentMethod[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] = $meansCode;

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0048',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [[EInvoicingErrorCode::MISSING_BT_87, ['paymentMeansCode' => $meansCode]]],
            $this->describeFailureReasons($failureReasons)
        );
    }

    public function testBuildReportsInvalidPaymentMeansCodeWithTheCodeAsParameter(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY] = '999';

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0001',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [[EInvoicingErrorCode::INVALID_BT_81, ['paymentMeansCode' => '999']]],
            $this->describeFailureReasons($failureReasons)
        );
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
        $firstItemKey = (string) array_key_first($orderData['items']);

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0405',
            new DateTimeImmutable('2026-10-14')
        );

        self::assertSame(
            [[EInvoicingErrorCode::SNAPSHOT_MISSING_BT_151, ['lineId' => $firstItemKey]]],
            $this->describeFailureReasons($failureReasons)
        );
    }

    public function testBuildRejectsMissingCheckoutSnapshotData(): void
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = EInvoicingFixtureLoader::loadFixtureInput('S1-EINV-001');
        $firstItemKey = array_key_first($orderData['items']);
        unset($orderData[EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY]);
        unset($orderData['items'][$firstItemKey]['extendedInfo'][EInvoicingSnapshotFields::ITEM_TAX_CATEGORY_CODE]);

        $failureReasons = $this->buildExpectingFailureReasons(
            [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup],
            'RE-2026-0005',
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame(
            [
                [EInvoicingErrorCode::SNAPSHOT_MISSING_BT_81, []],
                [EInvoicingErrorCode::SNAPSHOT_MISSING_BT_151, ['lineId' => (string) $firstItemKey]],
            ],
            $this->describeFailureReasons($failureReasons)
        );
    }

    /**
     * @param \Closure(array<int, mixed>): array<int, mixed>                      $mutateFixtureInput
     * @param list<array{0: EInvoicingErrorCode, 1: array<string, string>}> $expectedFailureReasons
     */
    #[DataProvider('failureScenarioProvider')]
    public function testBuildReportsFailureScenarioWithCodesAndParameters(
        string $fixtureId,
        string $invoiceNumber,
        \Closure $mutateFixtureInput,
        array $expectedFailureReasons,
    ): void {
        $failureReasons = $this->buildExpectingFailureReasons(
            $mutateFixtureInput(EInvoicingFixtureLoader::loadFixtureInput($fixtureId)),
            $invoiceNumber,
            new DateTimeImmutable('2026-10-08')
        );

        self::assertSame($expectedFailureReasons, $this->describeFailureReasons($failureReasons));
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: \Closure, 3: list<array{0: EInvoicingErrorCode, 1: array<string, string>}>}>
     */
    public static function failureScenarioProvider(): iterable
    {
        $unchanged = static fn (array $fixtureInput): array => $fixtureInput;

        yield 'invoice number missing' => ['S1-EINV-001', '  ', $unchanged, [[EInvoicingErrorCode::MISSING_BT_1, []]]];

        yield 'currency code missing' => ['S1-EINV-001', 'RE-1', static function (array $fixtureInput): array {
            unset($fixtureInput[1]['ls_shop_currencyCode']);

            return $fixtureInput;
        }, [[EInvoicingErrorCode::MISSING_BT_5, []]]];

        yield 'seller tax identifiers missing' => ['S1-EINV-001', 'RE-1', static function (array $fixtureInput): array {
            unset($fixtureInput[1]['ls_shop_ownVATID'], $fixtureInput[1]['ls_shop_einvoicingSellerTaxNumber']);

            return $fixtureInput;
        }, [[EInvoicingErrorCode::MISSING_BT_31_OR_BT_32, []]]];

        yield 'seller postal code, country and electronic address missing' => ['S1-EINV-001', 'RE-1', static function (array $fixtureInput): array {
            unset(
                $fixtureInput[1]['ls_shop_einvoicingSellerPostalCode'],
                $fixtureInput[1]['ls_shop_country'],
                $fixtureInput[1]['ls_shop_einvoicingSellerElectronicAddress']
            );

            return $fixtureInput;
        }, [
            [EInvoicingErrorCode::MISSING_BT_38, []],
            [EInvoicingErrorCode::MISSING_BT_40, []],
            [EInvoicingErrorCode::MISSING_BT_34, []],
        ]];

        yield 'buyer name and address missing' => ['S1-EINV-001', 'RE-1', static function (array $fixtureInput): array {
            foreach (['firstname', 'lastname', 'company', 'street', 'postal', 'city'] as $personalDataKey) {
                unset($fixtureInput[0]['customerData']['personalData'][$personalDataKey]);
            }

            return $fixtureInput;
        }, [
            [EInvoicingErrorCode::MISSING_BT_44, []],
            [EInvoicingErrorCode::MISSING_BT_50, []],
            [EInvoicingErrorCode::MISSING_BT_53, []],
            [EInvoicingErrorCode::MISSING_BT_52, []],
        ]];

        yield 'buyer country, tax country and delivery country missing' => ['S1-EINV-001', 'RE-1', static function (array $fixtureInput): array {
            unset(
                $fixtureInput[0]['customerData']['personalData_originalOptionValues']['country'],
                $fixtureInput[0][EInvoicingSnapshotFields::ORDER_TAX_COUNTRY_ORDER_KEY]
            );

            return $fixtureInput;
        }, [
            [EInvoicingErrorCode::MISSING_BT_55, []],
            [EInvoicingErrorCode::SNAPSHOT_MISSING_TAX_COUNTRY, []],
            [EInvoicingErrorCode::MISSING_BT_80, []],
        ]];

        yield 'payment means code missing in snapshot and payment method' => ['S1-EINV-001', 'RE-1', static function (array $fixtureInput): array {
            unset(
                $fixtureInput[0][EInvoicingSnapshotFields::ORDER_PAYMENT_MEANS_CODE_ORDER_KEY],
                $fixtureInput[2][EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD]
            );

            return $fixtureInput;
        }, [
            [EInvoicingErrorCode::SNAPSHOT_MISSING_BT_81, []],
            [EInvoicingErrorCode::MISSING_BT_81, []],
        ]];

        yield 'seller IBAN missing for credit transfer' => ['S1-EINV-001', 'RE-1', static function (array $fixtureInput): array {
            unset($fixtureInput[1]['ls_shop_einvoicingSellerIban']);

            return $fixtureInput;
        }, [[EInvoicingErrorCode::MISSING_BT_84, ['paymentMeansCode' => '58']]]];

        yield 'shipping charge without tax assignment' => ['S2-EINV-010', 'RE-1', static function (array $fixtureInput): array {
            $fixtureInput[0]['shippingMethod_amountTaxedWith'] = [];

            return $fixtureInput;
        }, [[EInvoicingErrorCode::MISSING_BT_103, ['chargeReason' => 'Versandkosten']]]];

        yield 'shipping charge without tax rate' => ['S2-EINV-010', 'RE-1', static function (array $fixtureInput): array {
            $fixtureInput[0]['shippingMethod_amountTaxedWith'] = [3 => ['amountTaxedHerewith' => 5.9]];

            return $fixtureInput;
        }, [[EInvoicingErrorCode::MISSING_BT_103, ['chargeReason' => 'Versandkosten']]]];

        yield 'shipping charge without tax category snapshot' => ['S2-EINV-010', 'RE-1', static function (array $fixtureInput): array {
            unset($fixtureInput[0][EInvoicingSnapshotFields::ORDER_SHIPPING_TAX_CATEGORY_ORDER_KEY]);

            return $fixtureInput;
        }, [[EInvoicingErrorCode::SNAPSHOT_MISSING_BT_102, ['chargeReason' => 'Versandkosten']]]];
    }

    /**
     * @param array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>, 3: array<string, mixed>, 4: array<int|string, array<string, mixed>>, 5: array<string, mixed>|null} $fixtureInput
     *
     * @return list<EInvoicingFailureReason>
     */
    private function buildExpectingFailureReasons(array $fixtureInput, string $invoiceNumber, DateTimeImmutable $invoiceDate): array
    {
        [$orderData, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup] = $fixtureInput;

        try {
            (new SemanticInvoiceBuilder())->build(
                $orderData,
                $shopSettings,
                $paymentMethod,
                $shippingMethod,
                $taxRates,
                $memberGroup,
                $invoiceNumber,
                $invoiceDate
            );
        } catch (InvoiceValidationException $invoiceValidationException) {
            return $invoiceValidationException->failureReasons;
        }

        self::fail('Expected an InvoiceValidationException.');
    }

    /**
     * @param list<EInvoicingFailureReason> $failureReasons
     *
     * @return list<array{0: EInvoicingErrorCode, 1: array<string, string>}>
     */
    private function describeFailureReasons(array $failureReasons): array
    {
        return array_map(
            static fn (EInvoicingFailureReason $failureReason): array => [$failureReason->code, $failureReason->parameters],
            $failureReasons
        );
    }

    /**
     * @return iterable<string, array{string, string, int, int, float, float, float, list<float>, string, string, string, string, string, string}>
     */
    public static function fixtureProvider(): iterable
    {
        yield 'fixture-001' => ['S1-EINV-001', 'RE-2026-0001', 1, 1, 10.0, 1.9, 11.9, [19.0], '58', 'S', 'DE', 'DE', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-002' => ['S1-EINV-002', 'RE-2026-0002', 3, 1, 111.0, 21.09, 132.09, [19.0], '58', 'S', 'DE', 'DE', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-003' => ['S1-EINV-003', 'RE-2026-0003', 2, 2, 20.0, 2.6, 22.6, [7.0, 19.0], '58', 'S', 'DE', 'DE', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-004' => ['S2-EINV-010', 'RE-2026-0210', 2, 2, 20.0, 3.01, 28.91, [7.0, 19.0], '58', 'S', 'DE', 'DE', '12', 'Zahlbar in 12 Tagen.'];
        yield 'fixture-005' => ['S3-EINV-001', '2026102677', 1, 1, 10.0, 1.9, 11.9, [19.0], '59', 'S', 'DE', 'DE', '0', 'Einzug per SEPA-Lastschrift.'];
        yield 'fixture-006' => ['S3-EINV-002', '2026102678', 1, 1, 10.0, 1.9, 11.9, [19.0], '68', 'S', 'DE', 'DE', '', ''];
        yield 'fixture-007' => ['S4-EINV-001', 'RE-2026-0401', 1, 1, 10.0, 0.0, 10.0, [0.0], '58', 'K', 'FR', 'FR', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-008' => ['S4-EINV-002', 'RE-2026-0402', 1, 1, 10.0, 1.9, 11.9, [19.0], '58', 'S', 'FR', 'DE', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-009' => ['S4-EINV-003', 'RE-2026-0403', 1, 1, 10.0, 0.0, 10.0, [0.0], '58', 'G', 'CH', 'CH', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-010' => ['S4-EINV-004', 'RE-2026-0404', 1, 1, 10.0, 0.0, 10.0, [0.0], '58', 'Z', 'DE', 'DE', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
        yield 'fixture-011' => ['S4-EINV-006', 'RE-2026-0406', 1, 1, 10.0, 0.0, 10.0, [0.0], '58', 'G', 'FR', 'CH', '14', 'Zahlbar innerhalb von 14 Tagen netto.'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedCardPaymentMeansProvider(): iterable
    {
        yield 'bank-card' => ['48'];
        yield 'credit-card' => ['54'];
        yield 'debit-card' => ['55'];
    }
}
