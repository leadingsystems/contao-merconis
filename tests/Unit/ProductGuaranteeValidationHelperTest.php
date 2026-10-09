<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeValidationHelper;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeValidationHelperTest extends TestCase
{
    public function testNormalizeDurationInputConvertsValidMonthsToStoredYears(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        $result = $helper->normalizeDurationInput('30');

        self::assertSame('2.5', $result['effectiveValue']);
        self::assertSame('2.5', $result['storedValue']);
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

    public function testConvertStoredYearsToMonthsInputRoundsInvalidStoredFractionsConsistently(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertSame('40', $helper->convertStoredYearsToMonthsInput('3.333'));
        self::assertSame('42', $helper->convertStoredYearsToMonthsInput('3,5'));
    }

    public function testFieldMappingUsesOneCanonicalAssignment(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

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
            'guaranteeDurationYears',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION
            )
        );
        self::assertSame(
            'guaranteeDurationYears',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION
            )
        );
        self::assertNull($helper->getFieldNameForMessageCode('unknown_code'));
    }

    public function testUnmappedMessagesRemainAvailableAlongsideFieldMessages(): void
    {
        $helper = new ProductGuaranteeValidationHelper();
        $messages = [
            [
                'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
                'parameters' => [],
            ],
            [
                'code' => 'unknown_code',
                'parameters' => ['context'],
            ],
        ];

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND],
            array_column($helper->getMessagesForField($messages, 'guaranteeBrand'), 'code')
        );
        self::assertSame(
            ['unknown_code'],
            array_column($helper->getUnmappedMessages($messages), 'code')
        );
        self::assertSame(
            ['unknown_code'],
            array_column(
                $helper->getGlobalMessages(
                    $messages,
                    ['guaranteeDurationYears', 'guaranteeBrand', 'guaranteeModelIdentifier']
                ),
                'code'
            )
        );
    }
}
