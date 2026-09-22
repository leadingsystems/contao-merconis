<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class LegalGuaranteeProductDataDcaTest extends TestCase
{
    private const DCA_BASE_PATH = __DIR__ . '/../../src/Resources/contao/dca/';
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';
    private const SERVICES_PATH = __DIR__ . '/../../src/Resources/config/services.yml';
    private const MIGRATION_PATH = __DIR__ . '/../../src/Migration/EnableGllDefaultMigration.php';

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

        self::assertSame("char(1) NOT NULL default '1'", $tableConfig['fields']['enableGll']['sql']);
        self::assertSame("char(1) NOT NULL default ''", $tableConfig['fields']['enableGaran']['sql']);
        self::assertSame("decimal(3,1) NULL", $tableConfig['fields']['guaranteeDurationYears']['sql']);
        self::assertSame(40, $tableConfig['fields']['guaranteeBrand']['eval']['maxlength']);
        self::assertSame(25, $tableConfig['fields']['guaranteeModelIdentifier']['eval']['maxlength']);
        self::assertStringContainsString('{lsShopGuaranteeLabels_legend}', $tableConfig['palettes']['default']);
        self::assertStringContainsString('enableGll', $tableConfig['palettes']['default']);
        self::assertStringContainsString('enableGaran', $tableConfig['palettes']['default']);
        self::assertSame(
            'validateLegalGuaranteeConfiguration',
            $tableConfig['config']['onsubmit_callback'][0][1]
        );
        self::assertSame(
            'oncreateGuaranteeBrandDefault',
            $tableConfig['config']['oncreate_callback'][0][1]
        );
    }

    public function testVariantDcaContainsOverrideSelectorAndGuaranteeSubpalette(): void
    {
        $this->loadVariantDca();

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_variant'];

        self::assertContains('garanOverride', $tableConfig['palettes']['__selector__']);
        self::assertStringContainsString('enableGaran', $tableConfig['subpalettes']['garanOverride']);
        self::assertStringContainsString('guaranteeDurationYears', $tableConfig['subpalettes']['garanOverride']);
        self::assertStringContainsString('guaranteeBrand', $tableConfig['subpalettes']['garanOverride']);
        self::assertStringContainsString('guaranteeModelIdentifier', $tableConfig['subpalettes']['garanOverride']);
        self::assertSame("char(1) NOT NULL default ''", $tableConfig['fields']['garanOverride']['sql']);
        self::assertSame("char(1) NOT NULL default ''", $tableConfig['fields']['enableGaran']['sql']);
        self::assertSame("decimal(3,1) NULL", $tableConfig['fields']['guaranteeDurationYears']['sql']);
        self::assertSame(
            'validateLegalGuaranteeConfiguration',
            $tableConfig['config']['onsubmit_callback'][0][1]
        );
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
    }

    public function testServicesAndMigrationAreRegisteredForEnableGllBackfillAndValidation(): void
    {
        $servicesContents = (string) file_get_contents(self::SERVICES_PATH);
        $migrationContents = (string) file_get_contents(self::MIGRATION_PATH);

        self::assertStringContainsString(
            'LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator',
            $servicesContents
        );
        self::assertStringContainsString(
            'merconis.migration.enable_gll_default_migration',
            $servicesContents
        );
        self::assertStringContainsString("SET `enableGll` = '1'", $migrationContents);
        self::assertStringContainsString(
            'Review digital products and services manually and disable GLL where required.',
            $migrationContents
        );
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
}
