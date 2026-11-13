<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationApplier;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeConfigurationApplierTest extends TestCase
{
    public function testApplyToProductRowPrefillsBrandAndKeepsMonths(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();
        $producer = "ACME & Co. 'Premium' " . str_repeat('X', 60);

        $result = $applier->applyToProductRow([
            'producer' => $producer,
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '30',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model42',
        ]);

        self::assertSame("ACME & Co. 'Premium' " . str_repeat('X', 60), $result['row']['guaranteeBrand']);
        self::assertSame('30', $result['row']['guaranteeDurationMonths']);
        self::assertSame([], $result['messages']);
    }

    public function testApplyToVariantRowKeepsOverrideModeWhenBrandIsMissing(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToVariantRow(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '54',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame('override', $result['row']['garanMode']);
        self::assertSame('guaranteeBrand', $result['messages'][0]['field']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            $result['messages'][0]['code']
        );
    }

    public function testWriteMessagesBlockPersistenceAndExposeReadableError(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'enableGaran' => '',
            'guaranteeDurationMonths' => '31',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model42',
        ]);

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame(
            'field "guaranteeDurationMonths": GARAN was not saved because "31" is not an allowed guarantee duration. Use whole months from 30 to 360 in steps of 6.',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testApplyToProductRowValidatesRawImportMonthsAgainstStoredYearLikeRowValue(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToProductRow(
            [
                'producer' => 'ACME Corporation',
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '216',
                'guaranteeBrand' => 'ACME',
                'guaranteeModelIdentifier' => 'Model42',
            ],
            true,
            'producer',
            [],
            '24'
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertNull($result['row']['guaranteeDurationMonths']);
        self::assertSame(
            'field "guaranteeDurationMonths": GARAN was not saved because "24" is not an allowed guarantee duration. Use whole months from 30 to 360 in steps of 6.',
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
            'guaranteeDurationMonths' => '36',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model42',
        ]);

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('enableGaran', $result['messages'][0]['field']);
        self::assertSame(
            'field "enableGaran": GARAN was not saved because GLL is disabled.',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testWriteMessagesExposeOverrideGllDependencyOnGaranMode(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToVariantRow(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'VariantM',
            ],
            [
                'enableGll' => '',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('garanMode', $result['messages'][0]['field']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_GLL_REQUIRED,
            $result['messages'][0]['code']
        );
        self::assertSame(
            'field "garanMode": GARAN was not saved because garanMode "override" requires GLL on the parent product.',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testApplyToVariantRowValidatesRawImportMonthsAgainstStoredYearLikeRowValue(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToVariantRow(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '216',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '186',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
            '24'
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertNull($result['row']['guaranteeDurationMonths']);
        self::assertSame(
            'field "guaranteeDurationMonths": GARAN was not saved because "24" is not an allowed guarantee duration. Use whole months from 30 to 360 in steps of 6.',
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
                'guaranteeDurationMonths' => null,
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            true,
            'producer',
            [
                [
                    'lsShopVariantCode' => 'VAR-001',
                    'garanMode' => 'override',
                ],
            ]
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('enableGll', $result['messages'][0]['field']);
        self::assertSame(
            'field "enableGll": GLL was not disabled because the following variants use garanMode "override": VAR-001.',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testApplyToProductRowKeepsLongValuesWhenGaranIsDisabled(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationMonths' => '',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertFalse($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame($brand, $result['row']['guaranteeBrand']);
        self::assertSame($model, $result['row']['guaranteeModelIdentifier']);
    }

    public function testApplyToVariantRowKeepsInheritedLongValuesWhenOverrideIsDisabled(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $applier->applyToVariantRow(
            [
                'garanMode' => 'inherit',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => $brand,
                'guaranteeModelIdentifier' => $model,
            ],
        );

        self::assertFalse($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('inherit', $result['row']['garanMode']);
        self::assertSame('', $result['row']['guaranteeBrand']);
        self::assertSame('', $result['row']['guaranteeModelIdentifier']);
    }

    public function testWriteMessagesExposeInvalidGaranMode(): void
    {
        $applier = new ProductGuaranteeConfigurationApplier();

        $result = $applier->applyToVariantRow(
            [
                'garanMode' => 'bogus',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('garanMode', $result['messages'][0]['field']);
        self::assertSame(
            'field "garanMode": GARAN was not saved because "bogus" is not a valid garanMode. Use "inherit", "disabled" or "override".',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }
}
