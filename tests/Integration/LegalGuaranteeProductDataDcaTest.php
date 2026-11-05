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
    private const GUARANTEE_BRAND_DESCRIPTION_DE = "Der volle Wert wird gespeichert. Bei Überlänge wird der Markenname in der Labeldarstellung mit '...' gekürzt. Die Kürzungslänge ist in den Grundeinstellungen konfigurierbar.";
    private const GUARANTEE_BRAND_DESCRIPTION_EN = "The full value is stored. If too long, the brand name is truncated with '...' in the label display. The truncation length is configurable in the shop settings.";
    private const GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_DE = "Der volle Wert wird gespeichert. Bei Überlänge wird die Modellkennung in der Labeldarstellung mit '...' gekürzt. Die Kürzungslänge ist in den Grundeinstellungen konfigurierbar.";
    private const GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_EN = "The full value is stored. If too long, the model identifier is truncated with '...' in the label display. The truncation length is configurable in the shop settings.";

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
        self::assertArrayNotHasKey('maxlength', $tableConfig['fields']['guaranteeBrand']['eval']);
        self::assertArrayNotHasKey('maxlength', $tableConfig['fields']['guaranteeModelIdentifier']['eval']);
        self::assertSame("varchar(255) NOT NULL default ''", $tableConfig['fields']['guaranteeBrand']['sql']);
        self::assertSame("varchar(255) NOT NULL default ''", $tableConfig['fields']['guaranteeModelIdentifier']['sql']);
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
        self::assertArrayNotHasKey('maxlength', $tableConfig['fields']['guaranteeBrand']['eval']);
        self::assertArrayNotHasKey('maxlength', $tableConfig['fields']['guaranteeModelIdentifier']['eval']);
        self::assertSame("varchar(255) NOT NULL default ''", $tableConfig['fields']['guaranteeBrand']['sql']);
        self::assertSame("varchar(255) NOT NULL default ''", $tableConfig['fields']['guaranteeModelIdentifier']['sql']);
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
        self::assertStringNotContainsString('Maximal 35 Zeichen.', $productLanguageDe);
        self::assertStringNotContainsString('Maximal 11 Zeichen.', $variantLanguageDe);
        self::assertStringNotContainsString('Maximum 35 characters.', $productLanguageEn);
        self::assertStringNotContainsString('Maximum 11 characters.', $variantLanguageEn);
        self::assertStringNotContainsString('garan_disabled_brand_too_long', $productLanguageDe);
        self::assertStringNotContainsString('garan_disabled_model_too_long', $variantLanguageEn);
        self::assertStringNotContainsString('Garantiedauer in Monaten *', $productLanguageDe);
        self::assertStringNotContainsString('Brand/Trademark *', $productLanguageDe);
        self::assertStringNotContainsString('Model identifier *', $productLanguageEn);
        self::assertStringContainsString('Guarantee duration in months', $variantLanguageEn);
        self::assertStringNotContainsString('Guarantee duration in months *', $variantLanguageEn);
        $this->assertGuaranteeFieldLabelAndDescription(
            $productLanguageDe,
            'guaranteeBrand',
            'Brand/Trademark',
            self::GUARANTEE_BRAND_DESCRIPTION_DE
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $productLanguageEn,
            'guaranteeBrand',
            'Brand/Trademark',
            self::GUARANTEE_BRAND_DESCRIPTION_EN
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $variantLanguageDe,
            'guaranteeBrand',
            'Brand/Trademark',
            self::GUARANTEE_BRAND_DESCRIPTION_DE
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $variantLanguageEn,
            'guaranteeBrand',
            'Brand/Trademark',
            self::GUARANTEE_BRAND_DESCRIPTION_EN
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $productLanguageDe,
            'guaranteeModelIdentifier',
            'Model identifier',
            self::GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_DE
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $productLanguageEn,
            'guaranteeModelIdentifier',
            'Model identifier',
            self::GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_EN
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $variantLanguageDe,
            'guaranteeModelIdentifier',
            'Model identifier',
            self::GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_DE
        );
        $this->assertGuaranteeFieldLabelAndDescription(
            $variantLanguageEn,
            'guaranteeModelIdentifier',
            'Model identifier',
            self::GUARANTEE_MODEL_IDENTIFIER_DESCRIPTION_EN
        );
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

        self::assertStringContainsString(
            '{legalGuarantee_legend},ls_shop_enableGllMigrationControl,garanLabelTruncationLength;',
            $settingsDca
        );
        self::assertStringContainsString("'inputType' => 'htmlDiv'", $settingsDca);
        self::assertStringContainsString("['garanLabelTruncationLength']", $settingsDca);
        self::assertStringContainsString('applyEnableGllMigrationRequest', $settingsDca);
        self::assertStringContainsString("['legalGuarantee_legend']", $settingsLanguageDe);
        self::assertStringContainsString("['ls_shop_enableGllMigrationControl']", $settingsLanguageDe);
        self::assertStringContainsString("['garanLabelTruncationLength']", $settingsLanguageDe);
        self::assertStringContainsString("['ls_shop_enableGllMigrationControl']", $settingsLanguageEn);
        self::assertStringContainsString("['garanLabelTruncationLength']", $settingsLanguageEn);
        self::assertStringContainsString('Gewährleistung und Herstellergarantie', $settingsLanguageDe);
        self::assertStringContainsString(
            "Legal guarantee and manufacturer\\'s commercial guarantee",
            $settingsLanguageEn
        );
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

    private function assertGuaranteeFieldLabelAndDescription(
        string $languageFileContents,
        string $fieldName,
        string $expectedLabel,
        string $expectedDescription
    ): void {
        $pattern = "/\\['" . preg_quote($fieldName, '/') . "'\\]\\s*=\\s*(?:array\\(|\\[)\\s*'((?:\\\\'|[^'])*)'\\s*,\\s*'((?:\\\\'|[^'])*)'/";

        self::assertSame(1, preg_match($pattern, $languageFileContents, $matches));

        $actualLabel = str_replace("\\'", "'", $matches[1]);
        $actualDescription = str_replace("\\'", "'", $matches[2]);

        self::assertSame($expectedLabel, $actualLabel);
        self::assertSame($expectedDescription, $actualDescription);
        self::assertStringNotContainsString('‘', $actualDescription);
        self::assertStringNotContainsString('’', $actualDescription);
        self::assertStringNotContainsString('“', $actualDescription);
        self::assertStringNotContainsString('”', $actualDescription);
    }
}
