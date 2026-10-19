<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfTemplateRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use PHPUnit\Framework\TestCase;

final class GaranPdfTemplateRendererTest extends TestCase
{
    public function testRenderInjectsSnapshotValuesIntoPreparedPdfTemplate(): void
    {
        $assetLocator = $this->createAssetLocator();
        $renderer = new GaranPdfTemplateRenderer($assetLocator);
        $templateContents = (string) file_get_contents(
            $assetLocator->getGaranPdfTemplatePath('v1.0')
        );

        $renderedSvg = $renderer->render('Merconis', 'MX-42', '30.0', ',');

        self::assertStringContainsString('data-field="brand"', $templateContents);
        self::assertStringContainsString('data-field="model"', $templateContents);
        self::assertStringContainsString('data-field="brand"', $renderedSvg);
        self::assertStringContainsString('data-field="model"', $renderedSvg);
        self::assertStringContainsString('data-field="duration"', $renderedSvg);
        self::assertStringContainsString('>Merconis<', $renderedSvg);
        self::assertStringContainsString('>MX-42<', $renderedSvg);
        self::assertStringContainsString('>30<', $renderedSvg);
        self::assertStringNotContainsString('Brand/Trademark', $renderedSvg);
        self::assertStringNotContainsString('Model identifier', $renderedSvg);
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }
}
