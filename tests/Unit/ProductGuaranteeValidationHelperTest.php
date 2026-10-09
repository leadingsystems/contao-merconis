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

    public function testProductEnableGaranIsLockedOnlyWhenStoredGllAndGaranAreDisabled(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertTrue($helper->shouldDisableProductEnableGaran([
            'enableGll' => '',
            'enableGaran' => '',
        ]));
        self::assertFalse($helper->shouldDisableProductEnableGaran([
            'enableGll' => '',
            'enableGaran' => '1',
        ]));
        self::assertFalse($helper->shouldDisableProductEnableGaran([
            'enableGll' => '1',
            'enableGaran' => '',
        ]));
    }

    public function testVariantGaranOverrideIsLockedOnlyWhenStoredOverrideIsDisabledAndParentGllIsDisabled(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertTrue($helper->shouldDisableVariantGaranOverride(
            ['garanOverride' => ''],
            ['enableGll' => '']
        ));
        self::assertFalse($helper->shouldDisableVariantGaranOverride(
            ['garanOverride' => '1'],
            ['enableGll' => '']
        ));
        self::assertFalse($helper->shouldDisableVariantGaranOverride(
            ['garanOverride' => ''],
            ['enableGll' => '1']
        ));
    }

    public function testAppendHintToFieldLabelKeepsExistingDescription(): void
    {
        $helper = new ProductGuaranteeValidationHelper();

        self::assertSame(
            ['Label', 'Bestehende Beschreibung. Zusätzlicher Hinweis.'],
            $helper->appendHintToFieldLabel(
                ['Label', 'Bestehende Beschreibung.'],
                'Zusätzlicher Hinweis.'
            )
        );
        self::assertSame(
            ['Label', 'Nur Hinweis.'],
            $helper->appendHintToFieldLabel(
                ['Label'],
                'Nur Hinweis.'
            )
        );
    }
}
