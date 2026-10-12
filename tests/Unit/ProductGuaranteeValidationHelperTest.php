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
        Input::setPost('action', null);
        Input::setPost('field', null);
        Input::setGet('act', null);
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
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION => 'Ungültige Dauer: %s',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ungültige Dauer: 15');

        $helper->throwValidationExceptionForField(
            [[
                'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
                'parameters' => ['15'],
            ]],
            'guaranteeDurationYears',
            'tl_ls_shop_product'
        );
    }

    public function testSubpaletteToggleRequestIsDetectedOnlyForTheToggledField(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        Input::setPost('action', 'toggleSubpalette');
        Input::setPost('field', 'enableGaran');

        self::assertTrue($helper->isSubpaletteToggleRequestForField('enableGaran'));
        self::assertFalse($helper->isSubpaletteToggleRequestForField('guaranteeBrand'));
    }

    public function testSubpaletteToggleSaveValueKeepsStoredValueOnlyForOwnFieldMessages(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        Input::setPost('action', 'toggleSubpalette');
        Input::setPost('field', 'enableGaran');

        self::assertSame(
            '',
            $helper->getSubpaletteToggleSaveValue(
                [[
                    'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED,
                    'parameters' => [],
                ]],
                'enableGaran',
                '1',
                ''
            )
        );
        self::assertSame(
            '1',
            $helper->getSubpaletteToggleSaveValue(
                [[
                    'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
                    'parameters' => [],
                ]],
                'enableGaran',
                '1',
                ''
            )
        );
    }

    public function testGaranOverrideToggleUsesEnableGaranMessagesAsBlockingSignal(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        Input::setPost('action', 'toggleSubpalette');
        Input::setPost('field', 'garanOverride');

        $messages = [[
            'code' => ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED,
            'parameters' => [],
        ]];

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED],
            array_column($helper->getSubpaletteToggleFieldMessages($messages, 'garanOverride'), 'code')
        );
        self::assertSame(
            '',
            $helper->getSubpaletteToggleSaveValue(
                $messages,
                'garanOverride',
                '1',
                ''
            )
        );
    }

    public function testProductEnableGaranAutoSubmitAttributesReplaceToggleWithBackendSubmitInEditMode(): void
    {
        $helper = new ProductGuaranteeValidationHelper();
        Input::setGet('act', 'edit');

        $attributes = $helper->configureProductEnableGaranAutoSubmitAttributes([
            'onclick' => "AjaxRequest.toggleSubpalette(this, 'sub_enableGaran', 'enableGaran')",
        ]);

        self::assertStringContainsString("Backend.autoSubmit('tl_ls_shop_product')", $attributes['onclick']);
        self::assertStringContainsString('"guaranteeDurationYears"', $attributes['onclick']);
        self::assertStringContainsString('"guaranteeBrand"', $attributes['onclick']);
        self::assertStringContainsString('"guaranteeModelIdentifier"', $attributes['onclick']);
        self::assertStringContainsString('\\u200B', $attributes['onclick']);
        self::assertStringNotContainsString('AjaxRequest.toggleSubpalette', $attributes['onclick']);
    }

    public function testVariantGaranOverrideAutoSubmitAttributesReplaceToggleWithBackendSubmitInEditMode(): void
    {
        $helper = new ProductGuaranteeValidationHelper();
        Input::setGet('act', 'edit');

        $attributes = $helper->configureVariantGaranOverrideAutoSubmitAttributes([
            'onclick' => "AjaxRequest.toggleSubpalette(this, 'sub_garanOverride', 'garanOverride')",
        ]);

        self::assertStringContainsString("Backend.autoSubmit('tl_ls_shop_variant')", $attributes['onclick']);
        self::assertStringContainsString('"guaranteeDurationYears"', $attributes['onclick']);
        self::assertStringContainsString('"guaranteeBrand"', $attributes['onclick']);
        self::assertStringContainsString('"guaranteeModelIdentifier"', $attributes['onclick']);
        self::assertStringContainsString('\\u200B', $attributes['onclick']);
        self::assertStringNotContainsString('AjaxRequest.toggleSubpalette', $attributes['onclick']);
    }

    public function testGuaranteeSelectorAutoSubmitAttributesStayUnchangedOutsideEditMode(): void
    {
        $helper = new ProductGuaranteeValidationHelper();
        Input::setGet('act', 'editAll');

        $attributes = [
            'onclick' => "AjaxRequest.toggleSubpalette(this, 'sub_enableGaran', 'enableGaran')",
        ];

        self::assertSame(
            $attributes,
            $helper->configureProductEnableGaranAutoSubmitAttributes($attributes)
        );
    }

    public function testAutoSubmitPlaceholderValuesAreNormalizedToEmptyStrings(): void
    {
        $helper = new ProductGuaranteeValidationHelper();
        $placeholder = html_entity_decode('&#8203;', ENT_QUOTES | ENT_HTML5, 'UTF-8');

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
                'guaranteeDurationYears' => $placeholder,
                'guaranteeBrand' => $placeholder,
                'guaranteeModelIdentifier' => $placeholder,
            ],
            [
                'enableGaran',
                'guaranteeDurationYears',
                'guaranteeBrand',
                'guaranteeModelIdentifier',
            ],
            'guaranteeBrand',
            $placeholder
        );

        self::assertSame('', $validationData['guaranteeDurationYears']);
        self::assertSame('', $validationData['guaranteeBrand']);
        self::assertSame('', $validationData['guaranteeModelIdentifier']);
    }

    public function testAutoSubmitPlaceholderFragmentsAreRemovedFromValidationAndStoredValues(): void
    {
        $helper = new ProductGuaranteeValidationHelper();
        $placeholder = html_entity_decode('&#8203;', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $validationData = $helper->buildValidationInputData(
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'guaranteeDurationYears' => $placeholder . '36',
                'guaranteeBrand' => $placeholder . 'Nestle',
                'guaranteeModelIdentifier' => 'Nestle' . $placeholder,
            ],
            [
                'guaranteeDurationYears',
                'guaranteeBrand',
                'guaranteeModelIdentifier',
            ]
        );

        self::assertSame('36', $validationData['guaranteeDurationYears']);
        self::assertSame('Nestle', $validationData['guaranteeBrand']);
        self::assertSame('Nestle', $validationData['guaranteeModelIdentifier']);
        self::assertSame('Nestle', $helper->normalizeAutoSubmitPlaceholderValue($placeholder . 'Nestle'));
        self::assertSame('Nestle', $helper->normalizeAutoSubmitPlaceholderValue('Nestle' . $placeholder));
        self::assertSame('36', $helper->normalizeAutoSubmitPlaceholderValue($placeholder . '36'));
    }
}
