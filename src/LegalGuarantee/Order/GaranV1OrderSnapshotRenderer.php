<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfTemplateRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;

final class GaranV1OrderSnapshotRenderer implements GaranOrderSnapshotRendererInterface
{
    public function __construct(
        private readonly GaranPdfTemplateRenderer $renderer,
    ) {
    }

    public function getVersion(): string
    {
        return OfficialGuaranteeAssetLocator::GARAN_VERSION;
    }

    /**
     * @param array<string, mixed> $itemSnapshot
     */
    public function render(array $itemSnapshot): string
    {
        return $this->renderer->render(
            trim((string) ($itemSnapshot['garanBrand'] ?? '')),
            trim((string) ($itemSnapshot['garanModelIdentifier'] ?? '')),
            trim((string) ($itemSnapshot['garanDurationYears'] ?? ''))
        );
    }
}
