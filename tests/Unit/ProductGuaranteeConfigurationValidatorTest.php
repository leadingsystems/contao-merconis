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
            'guaranteeDurationYears' => '30',
            'guaranteeBrand' => ' ACME ',
            'guaranteeModelIdentifier' => ' M-42 ',
        ]);

        self::assertSame('1', $result->getNormalizedData()['enableGaran']);
        self::assertSame('2.5', $result->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('ACME', $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame('M-42', $result->getNormalizedData()['guaranteeModelIdentifier']);
        self::assertSame([], $result->getMessages());
    }

    public function testProductValidationKeepsGaranEnabledWhenBrandIsMissing(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct([
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
                'enableGaran' => '1',
                'guaranteeDurationYears' => '4.5',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'Parent Model',
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
        self::assertSame('Parent Model', $result->getEffectiveData()['guaranteeModelIdentifier']);
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
                'enableGaran' => '1',
                'guaranteeDurationYears' => '5.0',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'Parent Model',
            ],
        );

        self::assertSame([], $result->getMessages());
        self::assertNull($result->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('5', $result->getEffectiveData()['guaranteeDurationYears']);
        self::assertSame('Parent Brand', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('Parent Model', $result->getEffectiveData()['guaranteeModelIdentifier']);
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
                'guaranteeModelIdentifier' => 'Variant Model',
            ],
            [
                'enableGaran' => '1',
                'guaranteeDurationYears' => '5.0',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'Parent Model',
            ],
        );

        self::assertSame('1', $result->getNormalizedData()['enableGaran']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            $result->getMessages()[0]['code']
        );
    }

    public function testInitialGuaranteeBrandUsesProducerWithMaximumLengthForty(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        self::assertSame(
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890ABCD',
            $validator->getInitialGuaranteeBrand('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890ABCDEFGHIJ')
        );
    }
}
