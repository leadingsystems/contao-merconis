<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan;

interface GaranLabelRendererInterface
{
    public function getVersion(): string;

    public function render(
        string $brand,
        string $modelIdentifier,
        string $durationMonths
    ): string;
}
