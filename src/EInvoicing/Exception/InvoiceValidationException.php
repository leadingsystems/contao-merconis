<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Exception;

use RuntimeException;

final class InvoiceValidationException extends RuntimeException
{
    /**
     * @param list<string> $violations
     */
    public function __construct(
        public readonly array $violations,
    ) {
        parent::__construct(implode("\n", $violations));
    }
}
