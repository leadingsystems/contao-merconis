<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;

/*
 * Functional statt Integration: Die DCA-Datei instanziiert `PaletteManipulator` aus
 * `contao/core-bundle`, das nicht in `require-dev` steht (Implementierungsentscheidung 0010).
 */
final class MemberGroupGllModeDcaTest extends TestCase
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

    public function testGllModeFieldIsMandatorySelectWithoutPreselection(): void
    {
        $gllModeField = $GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopGllMode'];

        self::assertSame('select', $gllModeField['inputType']);
        self::assertSame(['on', 'off', 'blocked'], $gllModeField['options']);
        self::assertTrue($gllModeField['exclude']);
        self::assertTrue($gllModeField['eval']['includeBlankOption']);
        self::assertArrayHasKey('blankOptionLabel', $gllModeField['eval']);
        self::assertTrue($gllModeField['eval']['mandatory']);
        self::assertArrayNotHasKey('default', $gllModeField);
        self::assertSame("varchar(16) NOT NULL default ''", $gllModeField['sql']);
    }

    public function testGllModeFieldLabelsReferenceLanguageFiles(): void
    {
        $GLOBALS['TL_LANG']['tl_member_group']['lsShopGllMode'] = ['Title', 'Description'];
        $GLOBALS['TL_LANG']['tl_member_group']['lsShopGllModeOptions'] = ['on' => 'Show'];
        $GLOBALS['TL_LANG']['tl_member_group']['lsShopGllModeBlankOption'] = 'Please select';

        $gllModeField = $GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopGllMode'];

        self::assertSame(['Title', 'Description'], $gllModeField['label']);
        self::assertSame(['on' => 'Show'], $gllModeField['reference']);
        self::assertSame('Please select', $gllModeField['eval']['blankOptionLabel']);
    }

    public function testGllModeFieldIsAppendedToShopLegend(): void
    {
        $defaultPalette = $GLOBALS['TL_DCA']['tl_member_group']['palettes']['default'];

        self::assertSame(
            1,
            preg_match('/\{lsShop_legend:hide\}([^;]*)/', $defaultPalette, $shopLegendMatches)
        );

        $shopLegendFields = explode(',', trim($shopLegendMatches[1], ','));

        self::assertContains('lsShopGllMode', $shopLegendFields);
        self::assertSame('lsShopGllMode', end($shopLegendFields));
        self::assertSame(1, substr_count($defaultPalette, 'lsShopGllMode'));
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
