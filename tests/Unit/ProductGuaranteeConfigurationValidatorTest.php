<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeConfigurationValidatorTest extends TestCase
{
    public function testProductValidationStoresWholeMonthsAsYears(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '30',
            'guaranteeBrand' => ' ACME &amp; Co. ',
            'guaranteeModelIdentifier' => ' M-42 ',
        ]);

        self::assertSame('1', $result->getNormalizedData()['enableGaran']);
        self::assertSame('30', $result->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame('ACME &amp; Co.', $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame('M-42', $result->getNormalizedData()['guaranteeModelIdentifier']);
        self::assertSame([], $result->getMessages());
    }

    public function testProductValidationKeepsGaranEnabledWhenBrandIsMissing(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '36',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model',
        ]);

        self::assertSame('1', $result->getNormalizedData()['enableGaran']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            $result->getMessages()[0]['code']
        );
    }

    public function testProductValidationRejectsInvalidMonthValuesWithoutRounding(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct([
            'enableGaran' => '',
            'guaranteeDurationMonths' => '31',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model',
        ]);

        self::assertSame('', $result->getNormalizedData()['enableGaran']);
        self::assertNull($result->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
            $result->getMessages()[0]['code']
        );
        self::assertSame(['31'], $result->getMessages()[0]['parameters']);
    }

    public function testProductValidationRejectsGaranWhenGllIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct([
            'enableGll' => '',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '36',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model',
        ]);

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED,
            $result->getMessages()[0]['code']
        );
    }

    public function testProductValidationRejectsDisabledGllWhenVariantsKeepOwnGaran(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct(
            [
                'enableGll' => '',
                'enableGaran' => '',
                'guaranteeDurationMonths' => null,
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                [
                    'lsShopVariantCode' => 'VAR-001',
                    'garanMode' => 'override',
                ],
                [
                    'lsShopVariantCode' => 'VAR-002',
                    'garanMode' => 'override',
                ],
                [
                    'lsShopVariantCode' => 'VAR-IGNORED',
                    'garanMode' => 'disabled',
                ],
            ]
        );

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_VARIANT_OVERRIDE,
            $result->getMessages()[0]['code']
        );
        self::assertSame(['VAR-001, VAR-002'], $result->getMessages()[0]['parameters']);
    }

    public function testVariantInheritUsesParentEffectiveValuesButStillValidatesOwnDuration(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'inherit',
                'guaranteeDurationMonths' => '31',
                'guaranteeBrand' => 'Ignored',
                'guaranteeModelIdentifier' => 'Ignored',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '54',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
            $result->getMessages()[0]['code']
        );
        self::assertNull($result->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame('1', $result->getEffectiveData()['enableGaran']);
        self::assertSame('54', $result->getEffectiveData()['guaranteeDurationMonths']);
        self::assertSame('Parent Brand', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('ParentM', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantOverrideDoesNotFallBackToProductValuesForEmptyFields(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
            ],
            array_column($result->getMessages(), 'code')
        );
        self::assertNull($result->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame('', $result->getEffectiveData()['guaranteeDurationMonths']);
        self::assertSame('', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantInheritDoesNotDuplicateParentGllValidation(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'inherit',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGll' => '',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame([], $result->getMessages());
    }

    public function testVariantOverrideKeepsGaranEnabledWhenEffectiveBrandIsMissing(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame('override', $result->getNormalizedData()['garanMode']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            $result->getMessages()[0]['code']
        );
    }

    public function testVariantOverrideRejectsOwnGaranWhenParentGllIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            [
                'enableGll' => '',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_GLL_REQUIRED,
            $result->getMessages()[0]['code']
        );
    }

    public function testVariantValidationRejectsInvalidGaranMode(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
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

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INVALID_MODE,
            $result->getMessages()[0]['code']
        );
        self::assertSame('inherit', $result->getNormalizedData()['garanMode']);
    }

    public function testInitialGuaranteeBrandKeepsFullProducerName(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $producer = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789567890ABCDEFGHIJ';

        self::assertSame($producer, $validator->getInitialGuaranteeBrand($producer));
    }

    public function testProductValidationAcceptsLongBrandAndModelValues(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '36',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame($model, $result->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testProductValidationAcceptsLongBrandAndModelEvenWhenGaranIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationMonths' => '',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame($model, $result->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testVariantFallbackKeepsParentTextValuesWithoutEntityDecoding(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'inherit',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '36',
                'guaranteeBrand' => ' Elternmarke &amp; Co. ',
                'guaranteeModelIdentifier' => ' M&amp;S ',
            ],
        );

        self::assertSame([], $result->getMessages());
        self::assertSame('Elternmarke &amp; Co.', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('M&amp;S', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantOverrideAcceptsLongOwnValuesEvenWhenGaranIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $validator->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => $brand,
                'guaranteeModelIdentifier' => $model,
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION],
            array_column($result->getMessages(), 'code')
        );
        self::assertSame($brand, $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame($model, $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantInheritKeepsInheritedLongValuesWhenParentGaranIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $validator->validateVariant(
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

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame($model, $result->getEffectiveData()['guaranteeModelIdentifier']);
    }
}
