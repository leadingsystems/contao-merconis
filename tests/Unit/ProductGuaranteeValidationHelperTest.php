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
        Input::setPost('garanMode_42', null);
        Input::setPost('guaranteeBrand_42', null);
        Input::resetCache();

        parent::tearDown();
    }

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

    public function testNormalizeDurationStoredValueReturnsValidatedMonthString(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertSame('30', $helper->normalizeDurationStoredValue('30'));
        self::assertNull($helper->normalizeDurationStoredValue('31'));
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
                    ['guaranteeDurationMonths', 'guaranteeBrand', 'guaranteeModelIdentifier']
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
                'guaranteeDurationMonths' => '3',
                'guaranteeBrand' => 'Stored brand',
                'guaranteeModelIdentifier' => 'Stored model',
            ],
            [
                'enableGaran' => '',
                'guaranteeDurationMonths' => '3',
                'guaranteeBrand' => 'Changed brand',
                'guaranteeModelIdentifier' => 'Stored model',
            ],
            ['enableGaran', 'guaranteeDurationMonths']
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

    public function testGuaranteeSubpaletteFieldDetectionReturnsExpectedResult(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertTrue($helper->isGuaranteeSubpaletteField('guaranteeDurationMonths'));
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
                'guaranteeDurationMonths' => '36',
                'guaranteeBrand' => 'Brand',
                'guaranteeModelIdentifier' => 'Model',
            ])
        );
        self::assertSame(
            [
                'enableGll',
                'enableGaran',
                'guaranteeDurationMonths',
                'guaranteeBrand',
                'guaranteeModelIdentifier',
            ],
            $helper->getVisibleProductFieldNames([
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '36',
                'guaranteeBrand' => 'Brand',
                'guaranteeModelIdentifier' => 'Model',
            ])
        );
    }

    public function testVisibleVariantFieldNamesIncludeGuaranteeFieldsOnlyWhenGaranOverrideIsTruthy(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertSame(
            ['garanMode'],
            $helper->getVisibleVariantFieldNames([
                'garanMode' => 'inherit',
                'guaranteeDurationMonths' => '36',
                'guaranteeBrand' => 'Brand',
                'guaranteeModelIdentifier' => 'Model',
            ])
        );
        self::assertSame(
            [
                'garanMode',
                'guaranteeDurationMonths',
                'guaranteeBrand',
                'guaranteeModelIdentifier',
            ],
            $helper->getVisibleVariantFieldNames([
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '36',
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
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '36',
                'guaranteeBrand' => 'Brand',
                'guaranteeModelIdentifier' => 'Model',
            ],
            ['enableGaran']
        );

        self::assertSame('1', $validationData['enableGaran']);
        self::assertSame('', $validationData['guaranteeDurationMonths']);
        self::assertSame('', $validationData['guaranteeBrand']);
        self::assertSame('', $validationData['guaranteeModelIdentifier']);
    }

    public function testReadPostedFieldValuesSupportsEditAllSuffixes(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        Input::setPost('garanMode_42', 'override');
        Input::setPost('guaranteeBrand_42', 'Brand');

        self::assertSame(
            [
                'garanMode' => 'override',
                'guaranteeBrand' => 'Brand',
            ],
            $helper->readPostedFieldValues(
                ['garanMode', 'guaranteeBrand', 'guaranteeModelIdentifier'],
                '_42'
            )
        );
    }
}
