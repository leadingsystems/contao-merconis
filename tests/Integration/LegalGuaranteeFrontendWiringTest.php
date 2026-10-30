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

    public function testShopSettingsAndLanguagesNoLongerExposeLegalGuaranteeInfoPageAssignment(): void
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

        self::assertStringNotContainsString('ls_shop_legalGuaranteeInfoPages', $settingsDcaContents);
        self::assertStringNotContainsString("['ls_shop_legalGuaranteeInfoPages']", $settingsLanguageDe);
        self::assertStringNotContainsString("['ls_shop_legalGuaranteeInfoPages']", $settingsLanguageEn);
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
        self::assertStringContainsString('InsertTag\AsInsertTag\\', $servicesContents);
        self::assertStringContainsString("['legalGuarantee']['gllTitle']", $defaultLanguageDe);
        self::assertStringContainsString("['legalGuarantee']['garanEuLinkText']", $defaultLanguageEn);
    }

    public function testCheckoutReviewWiringUsesProviderOutputInsteadOfCheckoutInsertTags(): void
    {
        $moduleContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/frontendModules/ModuleOrderReview.php'
        );
        $templateContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/templates/template_orderReview_default.html5'
        );
        $insertTagContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'InsertTag/AsInsertTag/ShopEuLgn.php'
        );

        self::assertStringContainsString('renderCheckoutGllLinks', $moduleContents);
        self::assertStringContainsString('renderCheckoutGaranBlock', $moduleContents);
        self::assertStringContainsString('merconis-legal-guarantee-checkout-output', $templateContents);
        self::assertStringContainsString("#[AsInsertTag('shop_eu_lgn')]", $insertTagContents);
    }
}
