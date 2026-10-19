<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfTemplateRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\GaranV1OrderSnapshotRenderer;
use PHPUnit\Framework\TestCase;

final class GaranV1OrderSnapshotRendererTest extends TestCase
{
    public function testGetVersionReturnsFixedV10(): void
    {
        $renderer = new GaranV1OrderSnapshotRenderer(
            new GaranPdfTemplateRenderer($this->createAssetLocator())
        );

        self::assertSame('v1.0', $renderer->getVersion());
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }
}
