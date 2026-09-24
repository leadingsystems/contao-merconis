<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

interface GaranPdfAttachmentWriterInterface
{
    /**
     * @return array{relativePath: string, cleanupAfterSend: bool}
     */
    public function write(string $svgMarkup, string $preferredBaseName): array;
}
