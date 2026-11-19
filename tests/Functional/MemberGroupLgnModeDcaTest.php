<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;

/*
 * Functional statt Integration: Die DCA-Datei instanziiert `PaletteManipulator` aus
 * `contao/core-bundle`, das nicht in `require-dev` steht (Implementierungsentscheidung 0010).
 */
final class MemberGroupLgnModeDcaTest extends TestCase
{
    private const MEMBER_GROUP_DCA_PATH = __DIR__ . '/../../src/Resources/contao/dca/tl_member_group.php';
    private const TIMESTAMP_CALLBACK = ['Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp'];
    private const CONTAO_CORE_DEFAULT_PALETTE = '{title_legend},name;{redirect_legend:hide},redirect;{disable_legend:hide},disable,start,stop';

    private mixed $originalMemberGroupDca;
    private mixed $originalMemberGroupLanguage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalMemberGroupDca = $GLOBALS['TL_DCA']['tl_member_group'] ?? null;
        $this->originalMemberGroupLanguage = $GLOBALS['TL_LANG']['tl_member_group'] ?? null;

        $GLOBALS['TL_DCA']['tl_member_group'] = [
            'config' => [],
            'palettes' => ['default' => self::CONTAO_CORE_DEFAULT_PALETTE],
            'fields' => [],
        ];

        require self::MEMBER_GROUP_DCA_PATH;
    }

    protected function tearDown(): void
    {
        if ($this->originalMemberGroupDca === null) {
            unset($GLOBALS['TL_DCA']['tl_member_group']);
        } else {
            $GLOBALS['TL_DCA']['tl_member_group'] = $this->originalMemberGroupDca;
        }

        if ($this->originalMemberGroupLanguage === null) {
            unset($GLOBALS['TL_LANG']['tl_member_group']);
        } else {
            $GLOBALS['TL_LANG']['tl_member_group'] = $this->originalMemberGroupLanguage;
        }

        parent::tearDown();
    }

    public function testLgnModeFieldIsMandatorySelectWithoutPreselection(): void
    {
        $lgnModeField = $GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopLgnMode'];

        self::assertSame('select', $lgnModeField['inputType']);
        self::assertSame(['on', 'off', 'blocked'], $lgnModeField['options']);
        self::assertTrue($lgnModeField['exclude']);
        self::assertTrue($lgnModeField['eval']['includeBlankOption']);
        self::assertArrayHasKey('blankOptionLabel', $lgnModeField['eval']);
        self::assertTrue($lgnModeField['eval']['mandatory']);
        self::assertArrayNotHasKey('default', $lgnModeField);
        self::assertSame("varchar(16) NOT NULL default ''", $lgnModeField['sql']);
    }

    /**
     * Hilfe-Assistent und halbe Breite nach Implementierungsentscheidung 0027.
     */
    public function testLgnModeFieldHasHelpWizardAndHalfWidth(): void
    {
        $lgnModeField = $GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopLgnMode'];
        $lgnModeCssClasses = explode(' ', $lgnModeField['eval']['tl_class']);

        self::assertTrue($lgnModeField['eval']['helpwizard']);
        self::assertArrayNotHasKey('explanation', $lgnModeField);
        self::assertContains('w50', $lgnModeCssClasses);
        self::assertContains('clr', $lgnModeCssClasses);
    }

    public function testLgnModeFieldLabelsReferenceLanguageFiles(): void
    {
        $GLOBALS['TL_LANG']['tl_member_group']['lsShopLgnMode'] = ['Title', 'Description'];
        $GLOBALS['TL_LANG']['tl_member_group']['lsShopLgnModeOptions'] = ['on' => 'Show'];
        $GLOBALS['TL_LANG']['tl_member_group']['lsShopLgnModeBlankOption'] = 'Please select';

        $lgnModeField = $GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopLgnMode'];

        self::assertSame(['Title', 'Description'], $lgnModeField['label']);
        self::assertSame(['on' => 'Show'], $lgnModeField['reference']);
        self::assertSame('Please select', $lgnModeField['eval']['blankOptionLabel']);
    }

    public function testLgnModeFieldIsAppendedToShopLegend(): void
    {
        $defaultPalette = $GLOBALS['TL_DCA']['tl_member_group']['palettes']['default'];

        self::assertSame(
            1,
            preg_match('/\{lsShop_legend:hide\}([^;]*)/', $defaultPalette, $shopLegendMatches)
        );

        $shopLegendFields = explode(',', trim($shopLegendMatches[1], ','));

        self::assertContains('lsShopLgnMode', $shopLegendFields);
        self::assertSame('lsShopLgnMode', end($shopLegendFields));
        self::assertSame(1, substr_count($defaultPalette, 'lsShopLgnMode'));
    }

    public function testMemberGroupHasNoAdditionalCallbacks(): void
    {
        $memberGroupDca = $GLOBALS['TL_DCA']['tl_member_group'];

        self::assertSame(
            ['onsubmit_callback', 'ondelete_callback', 'onrestore_version_callback'],
            array_keys($memberGroupDca['config'])
        );
        self::assertSame([self::TIMESTAMP_CALLBACK], $memberGroupDca['config']['onsubmit_callback']);
        self::assertSame([self::TIMESTAMP_CALLBACK], $memberGroupDca['config']['ondelete_callback']);
        self::assertSame([self::TIMESTAMP_CALLBACK], $memberGroupDca['config']['onrestore_version_callback']);

        foreach ($memberGroupDca['fields'] as $fieldName => $fieldDefinition) {
            foreach (array_keys($fieldDefinition) as $fieldDefinitionKey) {
                self::assertStringNotContainsString(
                    '_callback',
                    (string) $fieldDefinitionKey,
                    sprintf('Field %s must not define callbacks.', $fieldName)
                );
            }
        }
    }
}
