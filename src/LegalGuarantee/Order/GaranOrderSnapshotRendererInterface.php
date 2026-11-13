<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

interface GaranOrderSnapshotRendererInterface
{
    public function getVersion(): string;

    /**
     * @param array<string, mixed> $itemSnapshot
     */
    public function render(array $itemSnapshot): string;
}
