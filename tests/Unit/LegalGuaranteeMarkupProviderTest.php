<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupProvider;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use PHPUnit\Framework\TestCase;

final class LegalGuaranteeMarkupProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'] = [
            'de' => [
                'gllTitle' => 'Gesetzliche Gewährleistung, mindestens zwei Jahre',
                'gllEuLinkText' => 'EU-Information zur gesetzlichen Gewährleistung',
                'garanBlockHeadline' => 'Herstellergarantie für folgende Artikel:',
                'garanEuLinkText' => 'EU-Information zur Herstellergarantie',
            ],
            'en' => [
                'gllTitle' => 'Legal guarantee, at least two years',
                'gllEuLinkText' => 'EU information about the legal guarantee',
                'garanBlockHeadline' => 'Manufacturer\'s commercial guarantee for the following items:',
                'garanEuLinkText' => 'EU information about the manufacturer\'s commercial guarantee',
            ],
        ];
        $GLOBALS['TL_CSS'] = [];
    }

    public function testRenderCurrentLanguageGllNoticeUsesResolvedLanguageAndAccessibilityTitle(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];

        $markup = $this->createProvider()->renderCurrentLanguageGllNotice();

        self::assertStringContainsString('lang="de"', $markup);
        self::assertStringContainsString('Gesetzliche Gewährleistung, mindestens zwei Jahre', $markup);
        self::assertContains(
            'bundles/leadingsystemsmerconis/legal-guarantee/legal-guarantee.css',
            $GLOBALS['TL_CSS']
        );
    }

    public function testRenderCurrentLanguageGllNoticeFallsBackToEnglishForUnknownLocale(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'zz_ZZ'];

        $markup = $this->createProvider()->renderCurrentLanguageGllNotice();

        self::assertStringContainsString('lang="en"', $markup);
        self::assertStringContainsString('Legal guarantee, at least two years', $markup);
    }

    private function createProvider(): LegalGuaranteeMarkupProvider
    {
        $assetLocator = new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );

        return new LegalGuaranteeMarkupProvider(
            $assetLocator,
            new GaranLabelRenderer($assetLocator),
            new ProductGuaranteeDisplayResolver(),
            new LegalGuaranteeMarkupBuilder(),
        );
    }
}
