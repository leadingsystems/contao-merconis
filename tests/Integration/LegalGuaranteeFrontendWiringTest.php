<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class LegalGuaranteeFrontendWiringTest extends TestCase
{
    private const SRC_BASE_PATH = __DIR__ . '/../../src/';
    private const LANGUAGE_BASE_PATH = self::SRC_BASE_PATH . 'Resources/contao/languages/';

    public function testProductTemplateAndProductObjectExposeFrontendGuaranteeMarkup(): void
    {
        $templateContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/templates/template_productDetails_01.html5'
        );
        $productClassContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/classes/ls_shop_product.php'
        );

        self::assertStringContainsString('_gllNotice', $templateContents);
        self::assertStringContainsString('_garanLabel', $templateContents);
        self::assertStringContainsString("case '_gllNotice':", $productClassContents);
        self::assertStringContainsString("case '_garanLabel':", $productClassContents);
    }

    public function testShopSettingsAndLanguagesExposeLegalGuaranteeInfoPageAssignment(): void
    {
        $settingsDcaContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/dca/tl_lsShopSettings.php'
        );
        $settingsLanguageDe = (string) file_get_contents(
            self::LANGUAGE_BASE_PATH . 'de/tl_lsShopSettings.php'
        );
        $settingsLanguageEn = (string) file_get_contents(
            self::LANGUAGE_BASE_PATH . 'en/tl_lsShopSettings.php'
        );

        self::assertStringContainsString('ls_shop_legalGuaranteeInfoPages', $settingsDcaContents);
        self::assertStringContainsString("['ls_shop_legalGuaranteeInfoPages']", $settingsLanguageDe);
        self::assertStringContainsString("['ls_shop_legalGuaranteeInfoPages']", $settingsLanguageEn);
    }

    public function testServicesAndLanguageFilesRegisterFrontendGuaranteeComponents(): void
    {
        $servicesContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/config/services.yml'
        );
        $defaultLanguageDe = (string) file_get_contents(
            self::LANGUAGE_BASE_PATH . 'de/default.php'
        );
        $defaultLanguageEn = (string) file_get_contents(
            self::LANGUAGE_BASE_PATH . 'en/default.php'
        );

        self::assertStringContainsString('LegalGuaranteeMarkupProvider', $servicesContents);
        self::assertStringContainsString('InsertTag\AsInsertTag\GllNotice', $servicesContents);
        self::assertStringContainsString('InsertTag\AsInsertTag\GllCheckoutLink', $servicesContents);
        self::assertStringContainsString('InsertTag\AsInsertTag\GaranCheckoutBlock', $servicesContents);
        self::assertStringContainsString("['legalGuarantee']['de']['gllTitle']", $defaultLanguageDe);
        self::assertStringContainsString("['legalGuarantee']['en']['garanEuLinkText']", $defaultLanguageEn);
    }
}
