<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfTemplateRenderer;

final class GaranV1OrderSnapshotRenderer implements GaranOrderSnapshotRendererInterface
{
    private const VERSION = 'v1.0';

    public function __construct(
        private readonly GaranPdfTemplateRenderer $renderer,
    ) {
    }

    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * @param array<string, mixed> $itemSnapshot
     */
    public function render(array $itemSnapshot): string
    {
        $decimalsSeparator = trim((string) ($itemSnapshot['decimalsSeparator'] ?? ''));

        return $this->renderer->render(
            trim((string) ($itemSnapshot['garanBrand'] ?? '')),
            trim((string) ($itemSnapshot['garanModelIdentifier'] ?? '')),
            trim((string) ($itemSnapshot['garanDurationYears'] ?? '')),
            $decimalsSeparator
        );
    }
}
