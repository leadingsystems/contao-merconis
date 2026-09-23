<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationApplier;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeConfigurationApplierTest extends TestCase
{
    public function testApplyToProductRowPrefillsBrandAndDecoratesDurationWarning(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '3,7',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model 42',
        ]);

        self::assertSame('ACME Corporation', $result['row']['guaranteeBrand']);
        self::assertSame('3.5', $result['row']['guaranteeDurationYears']);
        self::assertSame('guaranteeDurationYears', $result['messages'][0]['field']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DURATION_ROUNDED_DOWN,
            $result['messages'][0]['code']
        );
    }

    public function testApplyToVariantRowDecoratesMissingBrandMessage(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToVariantRow(
            [
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '4.5',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'Variant Model',
            ],
            [
                'enableGaran' => '1',
                'guaranteeDurationYears' => '4.5',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'Parent Model',
            ],
        );

        self::assertSame('', $result['row']['enableGaran']);
        self::assertSame('guaranteeBrand', $result['messages'][0]['field']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            $result['messages'][0]['code']
        );
    }

    public function testBuildExportColumnsUsesOwnValuesForRoundtrip(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $productColumns = $applier->buildExportColumns([
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '5,0',
            'guaranteeBrand' => ' Product Brand ',
            'guaranteeModelIdentifier' => ' Product Model ',
        ]);

        $variantColumns = $applier->buildExportColumns(
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '5.0',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'Parent Model',
            ],
            [
                'garanOverride' => '0',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '3,5',
                'guaranteeBrand' => ' Variant Brand ',
                'guaranteeModelIdentifier' => ' Variant Model ',
            ],
        );

        self::assertSame(
            [
                'enableGll' => '1',
                'garanOverride' => '',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '5.0',
                'guaranteeBrand' => 'Product Brand',
                'guaranteeModelIdentifier' => 'Product Model',
            ],
            $productColumns
        );
        self::assertSame(
            [
                'enableGll' => '',
                'garanOverride' => '',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '3.5',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'Variant Model',
            ],
            $variantColumns
        );
    }
}
