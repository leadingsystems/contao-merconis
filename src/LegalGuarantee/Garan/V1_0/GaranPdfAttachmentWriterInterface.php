<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

interface GaranPdfAttachmentWriterInterface
{
    /**
     * @param list<array{
     *   svgMarkup: string,
     *   productNames: list<string>,
     *   euLinkText: string,
     *   euLinkUrl: string
     * }> $pages
     *
     * @return array{relativePath: string}
     */
    public function write(array $pages, string $preferredBaseName): array;
}
