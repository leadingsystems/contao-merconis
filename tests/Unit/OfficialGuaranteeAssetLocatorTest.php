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

    public function testReturnsNullWhilePreparedPdfAssetsAreStillMissing(): void
    {
        $assetLocator = $this->createAssetLocator();

        self::assertNull($assetLocator->resolveGllPdfPath('de_AT'));
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }
}
