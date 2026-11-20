<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class LegalGuaranteeFrontendWiringTest extends TestCase
{
    private const SRC_BASE_PATH = __DIR__ . '/../../src/';
    private const LANGUAGE_BASE_PATH = self::SRC_BASE_PATH . 'Resources/contao/languages/';

    /**
     * Variablen des LSCSS-Frameworks, die die Grundgestaltung verwenden darf
     * (Implementierungsentscheidung 0031, Abschnitt "Grundgestaltung").
     */
    private const ALLOWED_LSCSS_VARIABLES = [
        '$ls-color-black',
        '$ls-color-grey',
        '$ls-color-white',
        '$ls-font-size-h4',
        '$ls-font-weight-bold',
        '$ls-margin-bottom-standard',
        '$ls-shadow-l',
    ];

    /**
     * Wortlaut nach Implementierungsentscheidung 0031, Abschnitt "Sprachschlüssel".
     */
    private const BUTTON_TRANSLATIONS = [
        'de' => [
            'lgnButtonText' => 'Ihre gesetzlichen Gewährleistungsrechte',
            'garanButtonLabel' => 'Herstellergarantie %s Jahre',
            'popoverCloseLabel' => 'Schließen',
        ],
        'en' => [
            'lgnButtonText' => 'Your legal guarantee rights',
            'garanButtonLabel' => 'Manufacturer guarantee %s years',
            'popoverCloseLabel' => 'Close',
        ],
    ];

    /**
     * Schlüssel, die `getTranslations()` liest (Arbeitsauftrag 0018).
     */
    private const LABEL_TRANSLATION_KEYS = [
        'lgnTitle',
        'lgnEuLinkText',
        'garanBlockHeadline',
        'garanEuLinkText',
        'garanTitlePattern',
    ];

    public function testProductObjectExposesButtonsAndDirectOutputs(): void
    {
        $productClassContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/classes/ls_shop_product.php'
        );

        foreach ([
            '_lgnNotice' => 'renderProductLgnNotice',
            '_lgnNoticeButton' => 'renderProductLgnNoticeButton',
            '_garanLabel' => 'renderProductGaranLabel',
            '_garanLabelButton' => 'renderProductGaranLabelButton',
        ] as $getterName => $providerMethod) {
            self::assertMatchesRegularExpression(
                sprintf('/case \'%s\':\s+return System::getContainer\(\)\s+->get\(LegalGuaranteeMarkupProvider::class\)\s+->%s\(\$this\);/', $getterName, $providerMethod),
                $productClassContents
            );
        }
    }

    public function testProductTemplatePlacesGaranButtonAfterFilterMatchAndLgnButtonAfterDescription(): void
    {
        $templateContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/templates/template_productDetails_01.html5'
        );

        $filterMatchPosition = strpos($templateContents, '<?php echo isset($str_html_filterMatch) ? $str_html_filterMatch : \'\'; ?>');
        $garanButtonPosition = strpos($templateContents, '<?php echo $this->objProduct->_garanLabelButton; ?>');
        $selectorTwoEndPosition = strpos($templateContents, 'id="ajaxReloadByVariantSelector_3_');
        $descriptionEndPosition = strpos($templateContents, '<?php // <- ##### DESCRIPTION ###### ?>');
        $lgnButtonPosition = strpos($templateContents, '<?php echo $this->objProduct->_lgnNoticeButton; ?>');
        $producerInfoPosition = strpos($templateContents, '<?php echo isset($str_html_productProducerInfo)');

        self::assertIsInt($filterMatchPosition);
        self::assertIsInt($garanButtonPosition);
        self::assertIsInt($lgnButtonPosition);
        self::assertGreaterThan($filterMatchPosition, $garanButtonPosition);
        self::assertLessThan($selectorTwoEndPosition, $garanButtonPosition);
        self::assertGreaterThan($descriptionEndPosition, $lgnButtonPosition);
        self::assertLessThan($producerInfoPosition, $lgnButtonPosition);
        self::assertStringNotContainsString('merconis-legal-guarantee-', $templateContents);
        self::assertStringNotContainsString('->_lgnNotice ?', $templateContents);
        self::assertStringNotContainsString('->_garanLabel ?', $templateContents);
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
        self::assertMatchesRegularExpression(
            '/LegalGuaranteeTemplateRendererInterface:\s+alias: LeadingSystems\\\\MerconisBundle\\\\LegalGuarantee\\\\Frontend\\\\LegalGuaranteeTemplateRenderer/',
            $servicesContents
        );
        self::assertStringContainsString("['legalGuarantee']['lgnTitle']", $defaultLanguageDe);
        self::assertStringContainsString("['legalGuarantee']['garanEuLinkText']", $defaultLanguageEn);
    }

    public function testButtonLanguageKeysExistInBothLanguagesAndAreReadWithoutInlineFallback(): void
    {
        $originalTlLang = $GLOBALS['TL_LANG'] ?? null;

        try {
            foreach (self::BUTTON_TRANSLATIONS as $language => $expectedTranslations) {
                $GLOBALS['TL_LANG'] = [];
                include self::LANGUAGE_BASE_PATH . $language . '/default.php';

                foreach ($expectedTranslations as $key => $expectedTranslation) {
                    self::assertSame(
                        $expectedTranslation,
                        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'][$key] ?? null,
                        sprintf('%s.%s', $language, $key)
                    );
                }
            }
        } finally {
            $GLOBALS['TL_LANG'] = $originalTlLang;
        }

        $providerContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'LegalGuarantee/Frontend/LegalGuaranteeMarkupProvider.php'
        );

        foreach (array_keys(self::BUTTON_TRANSLATIONS['de']) as $key) {
            self::assertStringContainsString(sprintf("=> \$translations['%s'],", $key), $providerContents);
            self::assertDoesNotMatchRegularExpression(sprintf("/\\['%s'\\]\\s*\\?\\?/", $key), $providerContents);
        }
    }

    public function testLabelLanguageKeysExistInBothLanguagesAndAreReadWithoutInlineFallback(): void
    {
        $originalTlLang = $GLOBALS['TL_LANG'] ?? null;

        try {
            foreach (['de', 'en'] as $language) {
                $GLOBALS['TL_LANG'] = [];
                include self::LANGUAGE_BASE_PATH . $language . '/default.php';

                foreach (self::LABEL_TRANSLATION_KEYS as $key) {
                    $translation = $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'][$key] ?? null;

                    self::assertIsString($translation, sprintf('%s.%s', $language, $key));
                    self::assertNotSame('', trim($translation), sprintf('%s.%s', $language, $key));
                }
            }
        } finally {
            $GLOBALS['TL_LANG'] = $originalTlLang;
        }

        $providerContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'LegalGuarantee/Frontend/LegalGuaranteeMarkupProvider.php'
        );

        foreach (self::LABEL_TRANSLATION_KEYS as $key) {
            self::assertStringContainsString(sprintf("'%s' => \$translations['%s'],", $key, $key), $providerContents);
            self::assertDoesNotMatchRegularExpression(sprintf("/\\['%s'\\]\\s*\\?\\?/", $key), $providerContents);
        }

        self::assertDoesNotMatchRegularExpression("/\\['legalGuarantee'\\]\\s*\\?\\?/", $providerContents);
        self::assertStringContainsString("'headline' => \$this->getTranslations()['garanBlockHeadline'],", $providerContents);
    }

    public function testCheckoutReviewPassesClosuresAndShowsAssignmentBeforeLgnButtonInWrapper(): void
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

        self::assertStringContainsString('->createCheckoutTemplateVariables()', $moduleContents);
        self::assertStringNotContainsString('->checkoutLgnNotice()', $moduleContents);
        self::assertStringNotContainsString('->checkoutGaranBlock()', $moduleContents);
        self::assertStringContainsString('if ($this->checkoutGaranBlock || $this->checkoutLgnNoticeButton) {', $templateContents);
        self::assertStringContainsString('<div class="merconis-legal-guarantee-checkout">', $templateContents);
        self::assertLessThan(
            strpos($templateContents, '<?php echo $this->checkoutLgnNoticeButton; ?>'),
            strpos($templateContents, '<?php echo $this->checkoutGaranBlock; ?>')
        );
        self::assertStringNotContainsString('$this->checkoutLgnNotice;', $templateContents);
        self::assertStringNotContainsString('gllCheckoutLinks', $templateContents);
        self::assertStringNotContainsString('garanCheckoutBlock', $templateContents);
        self::assertStringContainsString("#[AsInsertTag('shop_eu_lgn')]", $insertTagContents);
        self::assertStringContainsString('->renderCurrentLanguageLgnNotice()', $insertTagContents);
    }

    public function testBaseStylingUsesOnlyAllowedLscssVariablesWithoutMediaQueriesOrHiddenPopovers(): void
    {
        $scssBundleContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/lscss/_merconis.scss'
        );
        $scssPartialContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/lscss/_merconis-legal-guarantee.scss'
        );

        self::assertStringContainsString('@import "_merconis-legal-guarantee.scss";', $scssBundleContents);

        foreach ([
            '.merconis-legal-guarantee-unit {',
            '.merconis-legal-guarantee-button {',
            '.merconis-legal-guarantee-assignment {',
            '.merconis-legal-guarantee-checkout {',
            '&__popover:popover-open {',
            '&::backdrop {',
            '&:focus-visible {',
        ] as $expectedSelector) {
            self::assertStringContainsString($expectedSelector, $scssPartialContents);
        }

        preg_match_all('/\$[a-z0-9-]+/', $scssPartialContents, $variableMatches);

        self::assertSame([], array_values(array_diff(array_unique($variableMatches[0]), self::ALLOWED_LSCSS_VARIABLES)));
        self::assertStringNotContainsString('@media', $scssPartialContents);
        self::assertStringNotContainsString('media-breakpoint', $scssPartialContents);
        self::assertStringNotContainsString('[popover]', $scssPartialContents);
        self::assertStringContainsString('text-transform: none;', $scssPartialContents);
    }

    public function testCheckoutStylingUsesTheNewWrapperClassOnlyForTheContentWidth(): void
    {
        $checkoutScssContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/lscss/_merconis-cart-checkout-order-review.scss'
        );

        self::assertMatchesRegularExpression(
            '/\.merconis-legal-guarantee-checkout \{\s+:not\(\.my-orders\) > & \{\s+@include ls_mediumContentWrapper;\s+\}\s+\}/',
            $checkoutScssContents
        );
        self::assertStringNotContainsString('merconis-legal-guarantee-checkout-', $checkoutScssContents);
    }
}
