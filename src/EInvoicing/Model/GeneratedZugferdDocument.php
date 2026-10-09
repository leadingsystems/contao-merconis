<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Model;

final readonly class GeneratedZugferdDocument
{
    public function __construct(
        public string $fileName,
        public string $xmlContent,
        public string $pdfContent,
    ) {
    }
}
