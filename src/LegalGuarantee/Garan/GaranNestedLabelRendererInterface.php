<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan;

interface GaranNestedLabelRendererInterface
{
    public function getVersion(): string;

    public function render(string $durationMonths): string;
}
