<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeValidationHelper;
use PHPUnit\Framework\TestCase;

final class LegalGuaranteeProductDataDcaTest extends TestCase
{
    private const DCA_BASE_PATH = __DIR__ . '/../../src/Resources/contao/dca/';
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';
    private const SERVICES_PATH = __DIR__ . '/../../src/Resources/config/services.yml';
    private const MIGRATION_PATH = __DIR__ . '/../../src/Migration/EnableGllDefaultMigration.php';
    private const SETTINGS_DCA_PATH = __DIR__ . '/../../src/Resources/contao/dca/tl_lsShopSettings.php';
    private const DEFAULT_LANGUAGE_DE_PATH = __DIR__ . '/../../src/Resources/contao/languages/de/default.php';
    private const DEFAULT_LANGUAGE_EN_PATH = __DIR__ . '/../../src/Resources/contao/languages/en/default.php';

    protected function setUp(): void
    {
        parent::setUp();
        unset(
            $GLOBALS['TL_DCA']['tl_ls_shop_product'],
            $GLOBALS['TL_DCA']['tl_ls_shop_variant']
        );
    }

    public function testProductDcaContainsGuaranteeFieldsWithExpectedSchemaAndDefaults(): void
    {
        $this->loadProductDca();

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_product'];

        self::assertSame('1', $tableConfig['fields']['enableGll']['default']);
        self::assertSame("char(1) NOT NULL default ''", $tableConfig['fields']['enableGll']['sql']);
        self::assertSame("char(1) NOT NULL default ''", $tableConfig['fields']['enableGaran']['sql']);
        self::assertSame(
            'validateLegalGuaranteeBeforeSave',
            $tableConfig['fields']['enableGll']['save_callback'][0][1]
        );
        self::assertSame(
            'validateLegalGuaranteeBeforeSave',
            $tableConfig['fields']['enableGaran']['save_callback'][0][1]
        );
        self::assertSame("decimal(3,1) NULL", $tableConfig['fields']['guaranteeDurationYears']['sql']);
        self::assertArrayNotHasKey('mandatory', $tableConfig['fields']['guaranteeDurationYears']['eval']);
        self::assertArrayNotHasKey('mandatory', $tableConfig['fields']['guaranteeBrand']['eval']);
        self::assertArrayNotHasKey('mandatory', $tableConfig['fields']['guaranteeModelIdentifier']['eval']);
        self::assertSame(35, $tableConfig['fields']['guaranteeBrand']['eval']['maxlength']);
        self::assertSame(11, $tableConfig['fields']['guaranteeModelIdentifier']['eval']['maxlength']);
        self::assertStringContainsString('{lsShopGuaranteeLabels_legend}', $tableConfig['palettes']['default']);
        self::assertStringContainsString('enableGll', $tableConfig['palettes']['default']);
        self::assertStringContainsString('enableGaran', $tableConfig['palettes']['default']);
        self::assertStringContainsString('guaranteeDurationYears', $tableConfig['palettes']['default']);
        self::assertStringContainsString('guaranteeBrand', $tableConfig['palettes']['default']);
        self::assertStringContainsString('guaranteeModelIdentifier', $tableConfig['palettes']['default']);
        self::assertNotContains('enableGaran', $tableConfig['palettes']['__selector__']);
        self::assertArrayNotHasKey('enableGaran', $tableConfig['subpalettes']);
        self::assertSame(
            'validateLegalGuaranteeBeforeSave',
            $tableConfig['fields']['guaranteeDurationYears']['save_callback'][0][1]
        );
        self::assertSame(
            ProductGuaranteeValidationHelper::class,
            $tableConfig['fields']['guaranteeDurationYears']['save_callback'][1][0]
        );
        self::assertSame(
            'normalizeDurationStoredValue',
            $tableConfig['fields']['guaranteeDurationYears']['save_callback'][1][1]
        );
        self::assertSame(
            ProductGuaranteeValidationHelper::class,
            $tableConfig['fields']['guaranteeDurationYears']['load_callback'][0][0]
        );
        self::assertSame(
            'convertStoredYearsToMonthsInput',
            $tableConfig['fields']['guaranteeDurationYears']['load_callback'][0][1]
        );
        self::assertSame(
            'validateLegalGuaranteeBeforeSave',
            $tableConfig['fields']['guaranteeBrand']['save_callback'][0][1]
        );
        self::assertSame(
            'validateLegalGuaranteeBeforeSave',
            $tableConfig['fields']['guaranteeModelIdentifier']['save_callback'][0][1]
        );
        self::assertSame(
            'validateLegalGuaranteeConfiguration',
            $tableConfig['config']['onsubmit_callback'][0][1]
        );
        self::assertArrayNotHasKey('submitOnChange', $tableConfig['fields']['enableGaran']['eval']);
        self::assertArrayNotHasKey('attributes_callback', $tableConfig['fields']['enableGaran']);
        self::assertSame(
            'configureLegalGuaranteeFieldRequirement',
            $tableConfig['config']['onload_callback'][0][1]
        );
        self::assertArrayNotHasKey('oncreate_callback', $tableConfig['config']);
        self::assertArrayNotHasKey('onbeforesubmit_callback', $tableConfig['config']);
    }

    public function testVariantDcaContainsOverrideSelectorAndGuaranteeSubpalette(): void
    {
        $this->loadVariantDca();

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_variant'];

        self::assertNotContains('garanOverride', $tableConfig['palettes']['__selector__']);
        self::assertStringContainsString('enableGaran', $tableConfig['palettes']['default']);
        self::assertStringContainsString('guaranteeDurationYears', $tableConfig['palettes']['default']);
        self::assertStringContainsString('guaranteeBrand', $tableConfig['palettes']['default']);
        self::assertStringContainsString('guaranteeModelIdentifier', $tableConfig['palettes']['default']);
        self::assertArrayNotHasKey('garanOverride', $tableConfig['subpalettes']);
        self::assertSame('w50 m12', $tableConfig['fields']['garanOverride']['eval']['tl_class']);
        self::assertSame("char(1) NOT NULL default ''", $tableConfig['fields']['garanOverride']['sql']);
        self::assertSame(
            'validateLegalGuaranteeBeforeSave',
            $tableConfig['fields']['garanOverride']['save_callback'][0][1]
        );
        self::assertSame("char(1) NOT NULL default ''", $tableConfig['fields']['enableGaran']['sql']);
        self::assertSame(
            'validateLegalGuaranteeBeforeSave',
            $tableConfig['fields']['enableGaran']['save_callback'][0][1]
        );
        self::assertSame("decimal(3,1) NULL", $tableConfig['fields']['guaranteeDurationYears']['sql']);
        self::assertSame(
            'validateLegalGuaranteeBeforeSave',
            $tableConfig['fields']['guaranteeDurationYears']['save_callback'][0][1]
        );
        self::assertSame(
            ProductGuaranteeValidationHelper::class,
            $tableConfig['fields']['guaranteeDurationYears']['save_callback'][1][0]
        );
        self::assertSame(
            'normalizeDurationStoredValue',
            $tableConfig['fields']['guaranteeDurationYears']['save_callback'][1][1]
        );
        self::assertSame(
            ProductGuaranteeValidationHelper::class,
            $tableConfig['fields']['guaranteeDurationYears']['load_callback'][0][0]
        );
        self::assertSame(
            'convertStoredYearsToMonthsInput',
            $tableConfig['fields']['guaranteeDurationYears']['load_callback'][0][1]
        );
        self::assertSame(
            'validateLegalGuaranteeBeforeSave',
            $tableConfig['fields']['guaranteeBrand']['save_callback'][0][1]
        );
        self::assertSame(
            'validateLegalGuaranteeBeforeSave',
            $tableConfig['fields']['guaranteeModelIdentifier']['save_callback'][0][1]
        );
        self::assertSame(
            'validateLegalGuaranteeConfiguration',
            $tableConfig['config']['onsubmit_callback'][0][1]
        );
        self::assertArrayNotHasKey('submitOnChange', $tableConfig['fields']['garanOverride']['eval']);
        self::assertArrayNotHasKey('attributes_callback', $tableConfig['fields']['garanOverride']);
        self::assertArrayNotHasKey('onload_callback', $tableConfig['config']);
    }

    public function testLanguageFilesContainGuaranteeLabelsAndMessagesInGermanAndEnglish(): void
    {
        $productLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_ls_shop_product.php');
        $productLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_ls_shop_product.php');
        $variantLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_ls_shop_variant.php');
        $variantLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_ls_shop_variant.php');

        self::assertStringContainsString("['enableGll']", $productLanguageDe);
        self::assertStringContainsString("['enableGll']", $productLanguageEn);
        self::assertStringContainsString("['garanOverride']", $variantLanguageDe);
        self::assertStringContainsString("['garanOverride']", $variantLanguageEn);
        self::assertStringContainsString("['lsShopGuaranteeLabels_legend']", $productLanguageDe);
        self::assertStringContainsString("['lsShopGuaranteeLabels_legend']", $variantLanguageDe);
        self::assertStringContainsString("['guaranteeValidationMessages']", $productLanguageDe);
        self::assertStringContainsString("['guaranteeValidationMessages']", $variantLanguageEn);
        self::assertStringNotContainsString('enableGaranActivationRequiresStoredGllHint', $productLanguageDe);
        self::assertStringNotContainsString('enableGaranActivationRequiresStoredGllHint', $productLanguageEn);
        self::assertStringNotContainsString('garanOverrideRequiresStoredGllHint', $variantLanguageDe);
        self::assertStringNotContainsString('garanOverrideRequiresStoredGllHint', $variantLanguageEn);
        self::assertStringContainsString('garan_disabled_gll_required', $productLanguageDe);
        self::assertStringContainsString('garan_disabled_gll_variant_override', $productLanguageEn);
        self::assertStringContainsString('garan_disabled_gll_required', $variantLanguageDe);
        self::assertStringContainsString('garan_inherited_gll_required', $variantLanguageDe);
        self::assertStringContainsString('garan_disabled_gll_variant_override', $variantLanguageEn);
        self::assertStringContainsString('garan_inherited_gll_required', $variantLanguageEn);
        self::assertStringContainsString('Garantiedauer in Monaten', $productLanguageDe);
        self::assertStringContainsString('Brand/Trademark', $productLanguageDe);
        self::assertStringContainsString('Model identifier', $productLanguageEn);
        self::assertStringNotContainsString('Garantiedauer in Monaten *', $productLanguageDe);
        self::assertStringNotContainsString('Brand/Trademark *', $productLanguageDe);
        self::assertStringNotContainsString('Model identifier *', $productLanguageEn);
        self::assertStringContainsString('Guarantee duration in months', $variantLanguageEn);
        self::assertStringNotContainsString('Guarantee duration in months *', $variantLanguageEn);
        self::assertStringNotContainsString(
            '`',
            $this->extractGuaranteeValidationMessage($productLanguageDe, 'garan_disabled_gll_required')
        );
        self::assertStringNotContainsString(
            '`',
            $this->extractGuaranteeValidationMessage($productLanguageDe, 'garan_disabled_gll_variant_override')
        );
        self::assertStringNotContainsString(
            '`',
            $this->extractGuaranteeValidationMessage($productLanguageEn, 'garan_disabled_gll_required')
        );
        self::assertStringNotContainsString(
            '`',
            $this->extractGuaranteeValidationMessage($productLanguageEn, 'garan_disabled_gll_variant_override')
        );
        self::assertStringNotContainsString(
            '`',
            $this->extractGuaranteeValidationMessage($variantLanguageDe, 'garan_disabled_gll_required')
        );
        self::assertStringNotContainsString(
            '`',
            $this->extractGuaranteeValidationMessage($variantLanguageDe, 'garan_disabled_gll_variant_override')
        );
        self::assertStringNotContainsString(
            '`',
            $this->extractGuaranteeValidationMessage($variantLanguageDe, 'garan_inherited_gll_required')
        );
        self::assertStringNotContainsString(
            '`',
            $this->extractGuaranteeValidationMessage($variantLanguageEn, 'garan_disabled_gll_required')
        );
        self::assertStringNotContainsString(
            '`',
            $this->extractGuaranteeValidationMessage($variantLanguageEn, 'garan_disabled_gll_variant_override')
        );
        self::assertStringNotContainsString(
            '`',
            $this->extractGuaranteeValidationMessage($variantLanguageEn, 'garan_inherited_gll_required')
        );
    }

    public function testServicesAndMigrationAreRegisteredForEnableGllBackfillAndValidation(): void
    {
        $servicesContents = (string) file_get_contents(self::SERVICES_PATH);

        self::assertStringContainsString(
            'LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator',
            $servicesContents
        );
        self::assertStringContainsString(
            'LegalGuarantee\ProductData\EnableGllSettingsMigrationManager',
            $servicesContents
        );
        self::assertStringContainsString(
            "LeadingSystems\\MerconisBundle\\LegalGuarantee\\ProductData\\EnableGllSettingsMigrationManager:\n    public: true",
            $servicesContents
        );
        self::assertStringNotContainsString(
            'merconis.migration.enable_gll_default_migration',
            $servicesContents
        );
        self::assertFileDoesNotExist(self::MIGRATION_PATH);
    }

    public function testSettingsDcaAndDashboardTextsExposeOneTimeMigrationControl(): void
    {
        $settingsDca = (string) file_get_contents(self::SETTINGS_DCA_PATH);
        $defaultLanguageDe = (string) file_get_contents(self::DEFAULT_LANGUAGE_DE_PATH);
        $defaultLanguageEn = (string) file_get_contents(self::DEFAULT_LANGUAGE_EN_PATH);
        $settingsLanguageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_lsShopSettings.php');
        $settingsLanguageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_lsShopSettings.php');

        self::assertStringContainsString('{legalGuarantee_legend},ls_shop_enableGllMigrationControl;', $settingsDca);
        self::assertStringContainsString("'inputType' => 'htmlDiv'", $settingsDca);
        self::assertStringContainsString('applyEnableGllMigrationRequest', $settingsDca);
        self::assertStringContainsString("['legalGuarantee_legend']", $settingsLanguageDe);
        self::assertStringContainsString("['ls_shop_enableGllMigrationControl']", $settingsLanguageDe);
        self::assertStringContainsString("['ls_shop_enableGllMigrationControl']", $settingsLanguageEn);
        self::assertStringContainsString("['dashboard']['gllAnnouncement']", $defaultLanguageDe);
        self::assertStringContainsString("['dashboard']['gllAnnouncement']", $defaultLanguageEn);
    }

    private function loadProductDca(): void
    {
        $loader = new class {
            public function getTemplateGroup(string $prefix): array
            {
                return [];
            }

            public function requireFile(string $path): void
            {
                require $path;
            }
        };

        $loader->requireFile(self::DCA_BASE_PATH . 'tl_ls_shop_product.php');
    }

    private function loadVariantDca(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_variant.php';
    }

    private function extractGuaranteeValidationMessage(string $languageFileContents, string $messageCode): string
    {
        $pattern = "/'" . preg_quote($messageCode, '/') . "' => '((?:\\\\'|[^'])*)'/";

        self::assertSame(1, preg_match($pattern, $languageFileContents, $matches));

        return str_replace("\\'", "'", $matches[1]);
    }
}
