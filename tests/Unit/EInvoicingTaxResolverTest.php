<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

require_once __DIR__ . '/../Support/EInvoicingFixtureLoader.php';

use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingTaxResolver;
use LeadingSystems\MerconisBundle\Tests\Support\EInvoicingFixtureLoader;
use PHPUnit\Framework\TestCase;

final class EInvoicingTaxResolverTest extends TestCase
{
    /**
     * @dataProvider taxContextProvider
     */
    public function testResolvesTaxContextAndCategoryFromFixture(
        string $fixtureId,
        string $expectedTaxCountry,
        string $expectedVatExemptionStatus,
        ?string $expectedTaxCategory,
    ): void {
        [$orderData, $shopSettings, , , $taxRates] = EInvoicingFixtureLoader::loadFixtureInput($fixtureId);
        $firstItemKey = array_key_first($orderData['items']);
        $firstItem = $orderData['items'][$firstItemKey];
        $taxClassId = (string) $firstItem['taxClass'];
        $configuredCategory = $taxRates[$taxClassId][EInvoicingSnapshotFields::TAX_CONFIGURATION_CATEGORY_FIELD] ?? null;
        $taxRate = is_numeric($firstItem['taxPercentage'] ?? null) ? (float) $firstItem['taxPercentage'] : null;

        $taxContext = EInvoicingTaxResolver::resolveSnapshotTaxContextFromOrder($orderData, $shopSettings);

        self::assertSame($expectedTaxCountry, $taxContext['taxCountry']);
        self::assertSame($expectedVatExemptionStatus, $taxContext['vatExemptionStatus']);
        self::assertSame(
            $expectedTaxCategory,
            EInvoicingTaxResolver::resolveTaxCategory(
                is_scalar($configuredCategory) ? (string) $configuredCategory : null,
                $taxRate,
                $taxContext
            )
        );
    }

    /**
     * @return iterable<string, array{string, string, string, ?string}>
     */
    public static function taxContextProvider(): iterable
    {
        yield 'regel-1-standard-rate' => [
            'S1-EINV-001',
            'DE',
            EInvoicingTaxResolver::VAT_EXEMPTION_STATUS_NOT_EXEMPT_EU,
            'S',
        ];
        yield 'regel-1-eu-lieferung-ins-inland' => [
            'S4-EINV-002',
            'DE',
            EInvoicingTaxResolver::VAT_EXEMPTION_STATUS_NOT_EXEMPT_EU,
            'S',
        ];
        yield 'regel-2-k' => [
            'S4-EINV-001',
            'FR',
            EInvoicingTaxResolver::VAT_EXEMPTION_STATUS_EXEMPT_EU,
            'K',
        ];
        yield 'regel-3-g-mit-befreiung-drittland' => [
            'S4-EINV-006',
            'CH',
            EInvoicingTaxResolver::VAT_EXEMPTION_STATUS_EXEMPT_NON_EU,
            'G',
        ];
        yield 'regel-4-g-ohne-befreiung-drittland' => [
            'S4-EINV-003',
            'CH',
            EInvoicingTaxResolver::VAT_EXEMPTION_STATUS_NOT_EXEMPT_NON_EU,
            'G',
        ];
        yield 'regel-5-explizites-z' => [
            'S4-EINV-004',
            'DE',
            EInvoicingTaxResolver::VAT_EXEMPTION_STATUS_NOT_EXEMPT_EU,
            'Z',
        ];
        yield 'regel-6-fehlerfall' => [
            'S4-EINV-005',
            'DE',
            EInvoicingTaxResolver::VAT_EXEMPTION_STATUS_NOT_EXEMPT_EU,
            null,
        ];
    }

    public function testFallsBackToDefaultEuCountriesWhenConfigurationIsEmpty(): void
    {
        [$orderData, $shopSettings] = EInvoicingFixtureLoader::loadFixtureInput('S4-EINV-001');
        $shopSettings['ls_shop_euCountrycodes'] = '';

        $taxContext = EInvoicingTaxResolver::resolveSnapshotTaxContextFromOrder($orderData, $shopSettings);

        self::assertSame('FR', $taxContext['taxCountry']);
        self::assertTrue($taxContext['isEuCountry']);
        self::assertSame(
            EInvoicingTaxResolver::VAT_EXEMPTION_STATUS_EXEMPT_EU,
            $taxContext['vatExemptionStatus']
        );
    }

    public function testUsesConfiguredEuCountriesListForTaxClassification(): void
    {
        [$orderData, $shopSettings] = EInvoicingFixtureLoader::loadFixtureInput('S4-EINV-001');
        $shopSettings['ls_shop_euCountrycodes'] = 'de,be';

        $taxContext = EInvoicingTaxResolver::resolveSnapshotTaxContextFromOrder($orderData, $shopSettings);

        self::assertSame('FR', $taxContext['taxCountry']);
        self::assertFalse($taxContext['isEuCountry']);
        self::assertSame(
            EInvoicingTaxResolver::VAT_EXEMPTION_STATUS_EXEMPT_NON_EU,
            $taxContext['vatExemptionStatus']
        );
    }
}
