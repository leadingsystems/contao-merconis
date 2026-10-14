<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\Input;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeValidationHelper;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeValidationHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        Input::setPost('enableGaran_42', null);
        Input::setPost('guaranteeBrand_42', null);
        Input::resetCache();

        parent::tearDown();
    }

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
            'enableGaran',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED
            )
        );
        self::assertSame(
            'enableGaran',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INHERITED_GLL_REQUIRED
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
            'guaranteeBrand',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG
            )
        );
        self::assertSame(
            'guaranteeModelIdentifier',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL
            )
        );
        self::assertSame(
            'guaranteeModelIdentifier',
            $helper->getFieldNameForMessageCode(
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MODEL_TOO_LONG
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

    public function testBuildChangedFieldValuesOnlyReturnsChangedRequestedFields(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        $fieldsToPersist = $helper->buildChangedFieldValues(
            [
                'enableGaran' => '1',
                'guaranteeDurationYears' => '3',
                'guaranteeBrand' => 'Stored brand',
                'guaranteeModelIdentifier' => 'Stored model',
            ],
            [
                'enableGaran' => '',
                'guaranteeDurationYears' => '3',
                'guaranteeBrand' => 'Changed brand',
                'guaranteeModelIdentifier' => 'Stored model',
            ],
            ['enableGaran', 'guaranteeDurationYears']
        );

        self::assertSame(
            ['enableGaran' => ''],
            $fieldsToPersist
        );
    }

    public function testThrowValidationExceptionForFieldUsesMappedFieldMessage(): void
    {
        $helper = new ProductGuaranteeValidationHelper();
        $GLOBALS['TL_LANG']['tl_ls_shop_product']['guaranteeValidationMessages'] = [
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG => 'Brand ist zu lang: %s',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Brand ist zu lang: 30');

        $helper->throwValidationExceptionForField(
            [[
                'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_BRAND_TOO_LONG,
                'parameters' => ['30'],
            ]],
            'guaranteeBrand',
            'tl_ls_shop_product'
        );
    }

    public function testGuaranteeSubpaletteFieldDetectionReturnsExpectedResult(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertTrue($helper->isGuaranteeSubpaletteField('guaranteeDurationYears'));
        self::assertTrue($helper->isGuaranteeSubpaletteField('guaranteeBrand'));
        self::assertTrue($helper->isGuaranteeSubpaletteField('guaranteeModelIdentifier'));
        self::assertFalse($helper->isGuaranteeSubpaletteField('enableGaran'));
    }

    public function testVisibleProductFieldNamesIncludeGuaranteeFieldsOnlyWhenEnableGaranIsTruthy(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertSame(
            ['enableGll', 'enableGaran'],
            $helper->getVisibleProductFieldNames([
                'enableGll' => '1',
                'enableGaran' => '',
                'guaranteeDurationYears' => '36',
                'guaranteeBrand' => 'Brand',
                'guaranteeModelIdentifier' => 'Model',
            ])
        );
        self::assertSame(
            [
                'enableGll',
                'enableGaran',
                'guaranteeDurationYears',
                'guaranteeBrand',
                'guaranteeModelIdentifier',
            ],
            $helper->getVisibleProductFieldNames([
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '36',
                'guaranteeBrand' => 'Brand',
                'guaranteeModelIdentifier' => 'Model',
            ])
        );
    }

    public function testVisibleVariantFieldNamesIncludeGuaranteeFieldsOnlyWhenGaranOverrideIsTruthy(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertSame(
            ['garanOverride', 'enableGaran'],
            $helper->getVisibleVariantFieldNames([
                'garanOverride' => '',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '36',
                'guaranteeBrand' => 'Brand',
                'guaranteeModelIdentifier' => 'Model',
            ])
        );
        self::assertSame(
            [
                'garanOverride',
                'enableGaran',
                'guaranteeDurationYears',
                'guaranteeBrand',
                'guaranteeModelIdentifier',
            ],
            $helper->getVisibleVariantFieldNames([
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '36',
                'guaranteeBrand' => 'Brand',
                'guaranteeModelIdentifier' => 'Model',
            ])
        );
    }

    public function testBuildValidationInputDataUsesOnlyAcceptedPostedFieldNames(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        $validationData = $helper->buildValidationInputData(
            [
                'enableGll' => '1',
                'enableGaran' => '',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGaran' => '1',
                'guaranteeDurationYears' => '36',
                'guaranteeBrand' => 'Brand',
                'guaranteeModelIdentifier' => 'Model',
            ],
            ['enableGaran']
        );

        self::assertSame('1', $validationData['enableGaran']);
        self::assertSame('', $validationData['guaranteeDurationYears']);
        self::assertSame('', $validationData['guaranteeBrand']);
        self::assertSame('', $validationData['guaranteeModelIdentifier']);
    }

    public function testReadPostedFieldValuesSupportsEditAllSuffixes(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        Input::setPost('enableGaran_42', '1');
        Input::setPost('guaranteeBrand_42', 'Brand');

        self::assertSame(
            [
                'enableGaran' => '1',
                'guaranteeBrand' => 'Brand',
            ],
            $helper->readPostedFieldValues(
                ['enableGaran', 'guaranteeBrand', 'guaranteeModelIdentifier'],
                '_42'
            )
        );
    }
}
