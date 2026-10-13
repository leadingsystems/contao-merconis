<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationApplier;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeConfigurationApplierTest extends TestCase
{
    public function testApplyToProductRowPrefillsBrandAndConvertsMonthsToYears(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToProductRow([
            'producer' => 'ACME &amp; Co. &#39;Premium&#39;',
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '30',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model 42',
        ]);

        self::assertSame("ACME & Co. 'Premium'", $result['row']['guaranteeBrand']);
        self::assertSame('2.5', $result['row']['guaranteeDurationYears']);
        self::assertSame([], $result['messages']);
    }

    public function testApplyToVariantRowKeepsEnableGaranWhenBrandIsMissing(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToVariantRow(
            [
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '60',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'Variant Model',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '4.5',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'Parent Model',
            ],
        );

        self::assertSame('1', $result['row']['enableGaran']);
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
            'guaranteeBrand' => ' Product &amp; Brand ',
            'guaranteeModelIdentifier' => ' Product &quot;Model&quot; ',
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
                'guaranteeBrand' => ' Variant &amp; Brand ',
                'guaranteeModelIdentifier' => ' Variant &quot;Model&quot; ',
            ],
        );

        self::assertSame(
            [
                'enableGll' => '1',
                'garanOverride' => '',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '60',
                'guaranteeBrand' => 'Product & Brand',
                'guaranteeModelIdentifier' => 'Product "Model"',
            ],
            $productColumns
        );
        self::assertSame(
            [
                'enableGll' => '',
                'garanOverride' => '',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '42',
                'guaranteeBrand' => 'Variant & Brand',
                'guaranteeModelIdentifier' => 'Variant "Model"',
            ],
            $variantColumns
        );
    }

    public function testWriteMessagesBlockPersistenceAndExposeReadableError(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'enableGaran' => '',
            'guaranteeDurationYears' => '31',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model 42',
        ]);

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame(
            'field "guaranteeDurationYears": GARAN was not saved because "31" is not an allowed guarantee duration. Use whole months from 30 to 360 in steps of 6.',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testWriteMessagesExposeGllDependencyOnEnableGaran(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'enableGll' => '',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '36',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model 42',
        ]);

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('enableGaran', $result['messages'][0]['field']);
        self::assertSame(
            'field "enableGaran": GARAN was not saved because GLL is disabled.',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testWriteMessagesExposeInheritedGllDependencyOnEnableGaran(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToVariantRow(
            [
                'garanOverride' => '',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGll' => '',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '5.0',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'Parent Model',
            ],
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('enableGaran', $result['messages'][0]['field']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INHERITED_GLL_REQUIRED,
            $result['messages'][0]['code']
        );
        self::assertSame(
            'field "enableGaran": GARAN was not saved. The variant inherits GARAN from the parent product, where GARAN is enabled but GLL is disabled.',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testWriteMessagesExposeAffectedVariantsOnEnableGll(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToProductRow(
            [
                'producer' => 'ACME Corporation',
                'enableGll' => '',
                'enableGaran' => '',
                'guaranteeDurationYears' => null,
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            true,
            'producer',
            [
                [
                    'lsShopVariantCode' => 'VAR-001',
                    'garanOverride' => '1',
                    'enableGaran' => '1',
                ],
            ]
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('enableGll', $result['messages'][0]['field']);
        self::assertSame(
            'field "enableGll": GARAN was not saved because GLL is disabled and the following variants still use variant-specific GARAN data: VAR-001.',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testWriteMessagesExposeLengthViolationsForBrandAndModel(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        self::assertSame(
            'field "guaranteeBrand": GARAN was not saved because "Brand/Trademark" must not be longer than 40 characters.',
            $applier->formatWriteErrorMessage([[
                'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                'field' => 'guaranteeBrand',
                'parameters' => ['40'],
            ]])
        );
        self::assertSame(
            'field "guaranteeModelIdentifier": GARAN was not saved because "Model identifier" must not be longer than 25 characters.',
            $applier->formatWriteErrorMessage([[
                'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG,
                'field' => 'guaranteeModelIdentifier',
                'parameters' => ['25'],
            ]])
        );
    }

    public function testApplyToProductRowBlocksOverlongValuesWhenGaranIsDisabled(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 34);
        $model = 'ä&"<>ß' . str_repeat('B', 20);

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame(
            ['guaranteeBrand', 'guaranteeModelIdentifier'],
            array_column($result['messages'], 'field')
        );
        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG,
            ],
            array_column($result['messages'], 'code')
        );
    }

    public function testApplyToVariantRowBlocksInheritedOverlongValuesWhenOverrideIsDisabled(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 34);
        $model = 'ä&"<>ß' . str_repeat('B', 20);

        $result = $applier->applyToVariantRow(
            [
                'garanOverride' => '',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => $brand,
                'guaranteeModelIdentifier' => $model,
            ],
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame(
            ['guaranteeBrand', 'guaranteeModelIdentifier'],
            array_column($result['messages'], 'field')
        );
        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG,
            ],
            array_column($result['messages'], 'code')
        );
    }

    public function testApplyToVariantRowBlocksOwnOverlongValuesWhenOverrideIsDisabledWithoutDuplicateMessages(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 34);
        $model = 'ä&"<>ß' . str_repeat('B', 20);

        $result = $applier->applyToVariantRow(
            [
                'garanOverride' => '',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => $brand,
                'guaranteeModelIdentifier' => $model,
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => 'Kurz',
                'guaranteeModelIdentifier' => 'Kurz',
            ],
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame(
            ['guaranteeBrand', 'guaranteeModelIdentifier'],
            array_column($result['messages'], 'field')
        );
        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG,
            ],
            array_column($result['messages'], 'code')
        );
        self::assertCount(2, $result['messages']);
    }

    public function testApplyToVariantRowAcceptsExactOwnLimitsWhenOverrideIsDisabled(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 33);
        $model = 'ä&"<>ß' . str_repeat('B', 19);

        $result = $applier->applyToVariantRow(
            [
                'garanOverride' => '',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => $brand,
                'guaranteeModelIdentifier' => $model,
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => 'Kurz',
                'guaranteeModelIdentifier' => 'Kurz',
            ],
        );

        self::assertSame([], $result['messages']);
        self::assertSame($brand, $result['row']['guaranteeBrand']);
        self::assertSame($model, $result['row']['guaranteeModelIdentifier']);
    }
}
