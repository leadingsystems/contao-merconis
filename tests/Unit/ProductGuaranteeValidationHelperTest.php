<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeValidationHelper;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeValidationHelperTest extends TestCase
{
    public function testNormalizeDurationInputKeepsValidMonthsAsStoredValue(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        $result = $helper->normalizeDurationInput('30');

        self::assertSame('30', $result['effectiveValue']);
        self::assertSame('30', $result['storedValue']);
        self::assertSame([], $result['messages']);
    }

    public function testNormalizeDurationInputRejectsInvalidMonthsWithoutRounding(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        $result = $helper->normalizeDurationInput('31');

        self::assertSame('', $result['effectiveValue']);
        self::assertNull($result['storedValue']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
            $result['messages'][0]['code']
        );
        self::assertSame(['31'], $result['messages'][0]['parameters']);
    }

    public function testFieldMappingUsesOneCanonicalAssignment(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertSame(
            'enableGaran',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED
            )
        );
        self::assertSame(
            'garanMode',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INVALID_MODE
            )
        );
        self::assertSame(
            'garanMode',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_GLL_REQUIRED
            )
        );
        self::assertSame(
            'enableGll',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_VARIANT_OVERRIDE
            )
        );
        self::assertSame(
            'guaranteeBrand',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND
            )
        );
        self::assertSame(
            'guaranteeModelIdentifier',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL
            )
        );
        self::assertSame(
            'guaranteeDurationMonths',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION
            )
        );
        self::assertSame(
            'guaranteeDurationMonths',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION
            )
        );
        self::assertNull($helper->getFieldNameForMessageCode('unknown_code'));
    }

    public function testTruthyCheckUsesStoredCheckboxSemantics(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertTrue($helper->isTruthy('1'));
        self::assertTrue($helper->isTruthy(1));
        self::assertFalse($helper->isTruthy('0'));
        self::assertFalse($helper->isTruthy(false));
        self::assertFalse($helper->isTruthy(null));
    }

    public function testGetMessagesForFieldFiltersByMappedField(): void
    {
        $helper = new ProductGuaranteeValidationHelper();
        $messages = [
            [
                'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
                'parameters' => [],
            ],
            [
                'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
                'parameters' => [],
            ],
        ];

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND],
            array_column($helper->getMessagesForField($messages, 'guaranteeBrand'), 'code')
        );
    }

    public function testThrowValidationExceptionForFieldUsesMappedFieldMessage(): void
    {
        $helper = new ProductGuaranteeValidationHelper();
        $GLOBALS['TL_LANG']['tl_ls_shop_product']['guaranteeValidationMessages'] = [
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND => 'Brand fehlt.',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Brand fehlt.');

        $helper->throwValidationExceptionForField(
            [[
                'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
                'parameters' => [],
            ]],
            'guaranteeBrand',
            'tl_ls_shop_product'
        );
    }
}
