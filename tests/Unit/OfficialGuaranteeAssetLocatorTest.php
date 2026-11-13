<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use PHPUnit\Framework\TestCase;

final class OfficialGuaranteeAssetLocatorTest extends TestCase
{
    public function testDetectsAvailableSvgLanguagesFromVersionedCoreDirectory(): void
    {
        $assetLocator = $this->createAssetLocator();
        $availableLanguages = $assetLocator->getAvailableGllSvgLanguages();

        self::assertContains('de', $availableLanguages);
        self::assertContains('en', $availableLanguages);
        self::assertContains('fr', $availableLanguages);
    }

    public function testResolvesLocaleToExistingSvgAsset(): void
    {
        $assetLocator = $this->createAssetLocator();

        self::assertStringEndsWith(
            '/src/Resources/public/legal-guarantee/gll/v1.0/legal-guarantee-notice-de.svg',
            $assetLocator->resolveGllSvgPath('de_AT')
        );
        self::assertStringEndsWith(
            '/src/Resources/public/legal-guarantee/gll/v1.0/legal-guarantee-notice-en.svg',
            $assetLocator->resolveGllSvgPath('xx_YY')
        );
    }

    public function testResolvesLocaleToExistingPdfAsset(): void
    {
        $assetLocator = $this->createAssetLocator();

        self::assertStringEndsWith(
            '/src/Resources/public/legal-guarantee/gll/v1.0/legal-guarantee-notice-de.pdf',
            (string) $assetLocator->resolveGllPdfPath('de_AT')
        );
        self::assertStringEndsWith(
            '/src/Resources/public/legal-guarantee/gll/v1.0/legal-guarantee-notice-en.pdf',
            (string) $assetLocator->resolveGllPdfPath('xx_YY')
        );
        self::assertFileExists((string) $assetLocator->resolveGllPdfPath('de_AT'));
        self::assertFileExists((string) $assetLocator->resolveGllPdfPath('xx_YY'));
    }

    public function testProvidesPdfForEveryOfficialSvgLanguage(): void
    {
        $assetLocator = $this->createAssetLocator();
        $svgLanguages = $assetLocator->getAvailableGllSvgLanguages();
        $pdfLanguages = $assetLocator->getAvailableGllPdfLanguages();

        self::assertSame(
            [
                'bg', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi', 'fr',
                'ga', 'hr', 'hu', 'it', 'lt', 'lv', 'mt', 'nl', 'pl', 'pt',
                'ro', 'sk', 'sl', 'sv',
            ],
            $svgLanguages
        );
        self::assertSame($svgLanguages, $pdfLanguages);
    }

    public function testResolvesEuTextLinksFromVersionedMetadataFiles(): void
    {
        $assetLocator = $this->createAssetLocator();

        self::assertStringEndsWith(
            '/src/Resources/public/legal-guarantee/gll/v1.0/eu-text-links.json',
            $assetLocator->getGllEuTextLinksPath()
        );
        self::assertSame(
            'https://europa.eu/youreurope/garantien',
            $assetLocator->getGllEuUrlByLanguage('de')
        );
        self::assertSame(
            'https://europa.eu/youreurope/guarantees',
            $assetLocator->resolveGllEuUrl('zz_ZZ')
        );
        self::assertStringEndsWith(
            '/src/Resources/legal-guarantee/garan/v1.0/eu-text-links.json',
            $assetLocator->getGaranEuTextLinksPath()
        );
        self::assertSame(
            'https://europa.eu/youreurope/commercial-guarantee-durability/',
            $assetLocator->resolveGaranEuUrl()
        );
    }

    public function testProvidesVersionedGaranTemplatesAndInterFonts(): void
    {
        $assetLocator = $this->createAssetLocator();

        self::assertFileExists($assetLocator->getGaranColourTemplatePath());
        self::assertFileExists($assetLocator->getGaranPdfTemplatePath());
        self::assertFileExists($assetLocator->getGaranNestedTemplatePath());
        self::assertStringEndsWith(
            '/src/Resources/legal-guarantee/garan/v1.0/garan-label-nested.svg',
            $assetLocator->getGaranNestedTemplatePath()
        );
        self::assertStringEndsWith(
            '/src/Resources/legal-guarantee/garan/v1.0/garan-label-colour.svg',
            $assetLocator->getGaranPdfTemplatePath()
        );
        self::assertSame($assetLocator->getGaranColourTemplatePath(), $assetLocator->getGaranPdfTemplatePath());
        self::assertFileExists($assetLocator->getInterFontDirectory() . '/Inter-Regular.woff2');
        self::assertFileExists($assetLocator->getInterFontDirectory() . '/Inter-SemiBold.woff2');
        self::assertFileExists($assetLocator->getInterFontDirectory() . '/Inter-ExtraBold.woff2');
        self::assertFileExists($assetLocator->getInterFontDirectory() . '/Inter-Regular.ttf');
        self::assertFileExists($assetLocator->getInterFontDirectory() . '/Inter-SemiBold.ttf');
        self::assertFileExists($assetLocator->getInterFontDirectory() . '/Inter-ExtraBold.ttf');
        self::assertFileExists($assetLocator->getInterFontDirectory() . '/LICENSE.txt');
    }

    public function testFontStylesheetPreparesInterWithoutSettingShopFont(): void
    {
        $stylesheetPath = $this->createAssetLocator()->getFontStylesheetPath();
        $stylesheetContents = (string) file_get_contents($stylesheetPath);

        self::assertStringEndsWith(
            '/src/Resources/public/legal-guarantee/legal-guarantee-fonts.css',
            $stylesheetPath
        );
        self::assertStringContainsString('@font-face', $stylesheetContents);
        self::assertStringContainsString('Inter-Regular.woff2', $stylesheetContents);
        self::assertStringContainsString('Inter-SemiBold.woff2', $stylesheetContents);
        self::assertStringContainsString('Inter-ExtraBold.woff2', $stylesheetContents);
        self::assertStringContainsString('Inter-Regular.ttf', $stylesheetContents);
        self::assertStringContainsString('Inter-SemiBold.ttf', $stylesheetContents);
        self::assertStringContainsString('Inter-ExtraBold.ttf', $stylesheetContents);
        self::assertStringContainsString('format("woff2")', $stylesheetContents);
        self::assertStringContainsString('format("truetype")', $stylesheetContents);
        self::assertStringNotContainsString('.otf', $stylesheetContents);
        self::assertStringNotContainsString('format("opentype")', $stylesheetContents);
        self::assertStringNotContainsString('.merconis-legal-guarantee-label', $stylesheetContents);
        self::assertStringNotContainsString('max-width: 600px', $stylesheetContents);
        self::assertDoesNotMatchRegularExpression(
            '/\b(body|html)\b[^{]*\{[^}]*font-family\s*:/s',
            $stylesheetContents
        );
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }
}
