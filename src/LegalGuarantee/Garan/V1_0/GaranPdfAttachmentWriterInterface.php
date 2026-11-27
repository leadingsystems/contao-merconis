<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0;

interface GaranPdfAttachmentWriterInterface
{
    /**
     * @param list<array{
     *   type: 'label'|'notice',
     *   productNames: list<string>,
     *   noticeText: string,
     *   orderNumber: string,
     *   articleNumbers: list<string>,
     *   svgMarkup?: string,
     *   euLinkText?: string,
     *   euLinkUrl?: string
     * }> $pages
     *
     * @return array{relativePath: string}
     */
    public function write(array $pages, string $orderNumber, int $orderDateUnixTimestamp): array;
}
