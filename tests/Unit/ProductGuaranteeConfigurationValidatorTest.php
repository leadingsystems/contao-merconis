<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeConfigurationValidatorTest extends TestCase
{
    public function testProductValidationRoundsDurationDownAndKeepsValidGaranEnabled(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct([
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '3,7',
            'guaranteeBrand' => ' ACME ',
            'guaranteeModelIdentifier' => ' M-42 ',
        ]);

        self::assertSame('1', $result->getNormalizedData()['enableGaran']);
        self::assertSame('3.5', $result->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('ACME', $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame('M-42', $result->getNormalizedData()['guaranteeModelIdentifier']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DURATION_ROUNDED_DOWN,
            $result->getMessages()[0]['code']
        );
        self::assertSame(['3,7', '3.5'], $result->getMessages()[0]['parameters']);
    }

    public function testProductValidationDisablesGaranWhenBrandIsMissing(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct([
            'enableGaran' => '1',
            'guaranteeDurationYears' => '4.5',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model',
        ]);

        self::assertSame('', $result->getNormalizedData()['enableGaran']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            $result->getMessages()[0]['code']
        );
    }

    public function testProductValidationRoundsBelowMinimumAndDisablesGaran(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateProduct([
            'enableGaran' => '1',
            'guaranteeDurationYears' => '2,3',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model',
        ]);

        self::assertSame('', $result->getNormalizedData()['enableGaran']);
        self::assertSame('2.0', $result->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DURATION_ROUNDED_DOWN,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_DURATION_BELOW_MINIMUM,
            ],
            array_column($result->getMessages(), 'code')
        );
    }

    public function testVariantWithoutOverrideUsesOnlyParentEffectiveValues(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanOverride' => '',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '9.5',
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

        self::assertSame([], $result->getMessages());
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
        self::assertSame('5.0', $result->getEffectiveData()['guaranteeDurationYears']);
        self::assertSame('Parent Brand', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('Parent Model', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantOverrideDisablesGaranWhenEffectiveBrandIsMissing(): void
    {
        $validator = new ProductGuaranteeConfigurationValidator();

        $result = $validator->validateVariant(
            [
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '5.0',
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

        self::assertSame('', $result->getNormalizedData()['enableGaran']);
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
