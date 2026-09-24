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
        $renderer = new GaranPdfTemplateRenderer(
            new OfficialGuaranteeAssetLocator(
                dirname(__DIR__, 2),
                new OfficialGuaranteeLanguageResolver(),
            )
        );

        $renderedSvg = $renderer->render('Merconis', 'MX-42', '5');

        self::assertStringContainsString('data-field="brand"', $renderedSvg);
        self::assertStringContainsString('data-field="model"', $renderedSvg);
        self::assertStringContainsString('data-field="duration"', $renderedSvg);
        self::assertStringContainsString('>Merconis<', $renderedSvg);
        self::assertStringContainsString('>MX-42<', $renderedSvg);
        self::assertStringContainsString('>5<', $renderedSvg);
        self::assertStringNotContainsString('Brand/Trademark', $renderedSvg);
        self::assertStringNotContainsString('Model identifier', $renderedSvg);
    }
}
