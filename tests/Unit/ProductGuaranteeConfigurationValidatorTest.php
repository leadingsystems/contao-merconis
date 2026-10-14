<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeConfigurationValidatorTest extends TestCase
{
    private function createBrandAtLimit(): string
    {
        return 'ÄÖÜ&<>ß' . str_repeat('A', 13);
    }

    private function createModelAtLimit(): string
    {
        return 'ä&"<>ß' . str_repeat('B', 1);
    }

    private function createBrandAboveLimit(): string
    {
        return 'ÄÖÜ&<>ß' . str_repeat('A', 14);
    }

    private function createModelAboveLimit(): string
    {
        return 'ä&"<>ß' . str_repeat('B', 2);
    }

    public function testProductValidationStoresWholeMonthsAsYears(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '30',
            'guaranteeBrand' => ' ACME &amp; Co. ',
            'guaranteeModelIdentifier' => ' M-42 ',
        ]);

        self::assertSame('1', $result->getNormalizedData()['enableGaran']);
        self::assertSame('2.5', $result->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('ACME & Co.', $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame('M-42', $result->getNormalizedData()['guaranteeModelIdentifier']);
        self::assertSame([], $result->getMessages());
    }

    public function testProductValidationKeepsGaranEnabledWhenBrandIsMissing(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '36',
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
            'guaranteeDurationYears' => '31',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model',
        ]);

        self::assertSame('', $result->getNormalizedData()['enableGaran']);
        self::assertNull($result->getNormalizedData()['guaranteeDurationYears']);
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
            'guaranteeDurationYears' => '36',
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
                'guaranteeDurationYears' => null,
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                [
                    'lsShopVariantCode' => 'VAR-001',
                    'garanOverride' => '1',
                    'enableGaran' => '1',
                ],
                [
                    'lsShopVariantCode' => 'VAR-002',
                    'garanOverride' => '1',
                    'enableGaran' => '1',
                ],
                [
                    'lsShopVariantCode' => 'VAR-IGNORED',
                    'garanOverride' => '',
                    'enableGaran' => '1',
                ],
            ]
        );

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_VARIANT_OVERRIDE,
            $result->getMessages()[0]['code']
        );
        self::assertSame(['VAR-001, VAR-002'], $result->getMessages()[0]['parameters']);
    }

    public function testVariantWithoutOverrideUsesParentEffectiveValuesButStillValidatesOwnDuration(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanOverride' => '',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '31',
                'guaranteeBrand' => 'Ignored',
                'guaranteeModelIdentifier' => 'Ignored',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '4.5',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
            $result->getMessages()[0]['code']
        );
        self::assertNull($result->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('1', $result->getEffectiveData()['enableGaran']);
        self::assertSame('4.5', $result->getEffectiveData()['guaranteeDurationYears']);
        self::assertSame('Parent Brand', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('ParentM', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantOverrideFallsBackToProductValuesForEmptyFields(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '5.0',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame([], $result->getMessages());
        self::assertNull($result->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('5', $result->getEffectiveData()['guaranteeDurationYears']);
        self::assertSame('Parent Brand', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('ParentM', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantWithoutOverrideRejectsEffectiveGaranWhenParentGllIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
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
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INHERITED_GLL_REQUIRED,
            $result->getMessages()[0]['code']
        );
    }

    public function testVariantOverrideKeepsGaranEnabledWhenEffectiveBrandIsMissing(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '60',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '5.0',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame('1', $result->getNormalizedData()['enableGaran']);
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
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '60',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            [
                'enableGll' => '',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '5.0',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED,
            $result->getMessages()[0]['code']
        );
    }

    public function testInitialGuaranteeBrandUsesProducerWithMaximumLengthTwenty(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        self::assertSame(
            'ABCDEFGHIJKLMNOPQRST',
            $validator->getInitialGuaranteeBrand('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890ABCDEFGHIJ')
        );
    }

    public function testProductValidationAcceptsExactCharacterLimitsWithSpecialCharactersAndUmlauts(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = $this->createBrandAtLimit();
        $model = $this->createModelAtLimit();

        self::assertSame(20, mb_strlen($brand));
        self::assertSame(7, mb_strlen($model));

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '36',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame($model, $result->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testProductValidationRejectsBrandAndModelLongerThanLimits(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = $this->createBrandAboveLimit();
        $model = $this->createModelAboveLimit();

        self::assertSame(21, mb_strlen($brand));
        self::assertSame(8, mb_strlen($model));

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '36',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG,
            ],
            array_column($result->getMessages(), 'code')
        );
        self::assertSame([21, 8], array_map('mb_strlen', [$brand, $model]));
    }

    public function testProductValidationRejectsOverlongBrandAndModelEvenWhenGaranIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = $this->createBrandAboveLimit();
        $model = $this->createModelAboveLimit();

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG,
            ],
            array_column($result->getMessages(), 'code')
        );
    }

    public function testProductValidationAcceptsExactCharacterLimitsEvenWhenGaranIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = $this->createBrandAtLimit();
        $model = $this->createModelAtLimit();

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame($model, $result->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testVariantFallbackDecodesParentEntityValuesBeforeValidation(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanOverride' => '',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '36',
                'guaranteeBrand' => ' Elternmarke &amp; Co. ',
                'guaranteeModelIdentifier' => ' M&amp;S ',
            ],
        );

        self::assertSame([], $result->getMessages());
        self::assertSame('Elternmarke & Co.', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('M&S', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantOverrideRejectsOverlongOwnValuesEvenWhenGaranIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = $this->createBrandAboveLimit();
        $model = $this->createModelAboveLimit();

        $result = $validator->validateVariant(
            [
                'garanOverride' => '1',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => $brand,
                'guaranteeModelIdentifier' => $model,
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG,
            ],
            array_column($result->getMessages(), 'code')
        );
    }

    public function testVariantWithoutOverrideRejectsInheritedOverlongValuesWhenParentGaranIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = $this->createBrandAboveLimit();
        $model = $this->createModelAboveLimit();

        $result = $validator->validateVariant(
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

        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG,
            ],
            array_column($result->getMessages(), 'code')
        );
        self::assertSame($brand, $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame($model, $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantWithoutOverrideRejectsOverlongOwnValuesEvenWhenProductValuesAreShort(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = $this->createBrandAboveLimit();
        $model = $this->createModelAboveLimit();

        $result = $validator->validateVariant(
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

        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG,
            ],
            array_column($result->getMessages(), 'code')
        );
        self::assertCount(2, $result->getMessages());
        self::assertSame($brand, $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame($model, $result->getNormalizedData()['guaranteeModelIdentifier']);
        self::assertSame('Kurz', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('Kurz', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantWithoutOverrideAcceptsExactOwnLimitsWithShortParentValues(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = $this->createBrandAtLimit();
        $model = $this->createModelAtLimit();

        $result = $validator->validateVariant(
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

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame($model, $result->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testVariantOverrideAcceptsExactOwnLimitsEvenWhenGaranIsDisabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = $this->createBrandAtLimit();
        $model = $this->createModelAtLimit();

        $result = $validator->validateVariant(
            [
                'garanOverride' => '1',
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

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame($model, $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantOverrideWithEmptyOwnFieldsAcceptsInheritedExactLimits(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();
        $brand = $this->createBrandAtLimit();
        $model = $this->createModelAtLimit();

        $result = $validator->validateVariant(
            [
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '5.0',
                'guaranteeBrand' => $brand,
                'guaranteeModelIdentifier' => $model,
            ],
        );

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame($model, $result->getEffectiveData()['guaranteeModelIdentifier']);
    }
}
