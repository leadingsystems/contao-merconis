<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Exception;

use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReason;

/**
 * Eigener Erzeugungsfehler der E-Rechnungs-Schicht außerhalb der
 * Pflichtfeld-Validierung (z. B. Bestellung nicht gefunden,
 * Zielverzeichnis nicht anlegbar).
 */
final class EInvoicingGenerationException extends EInvoicingFailureException
{
    /**
     * @param array<string, string> $parameters
     */
    public static function forReason(EInvoicingErrorCode $code, array $parameters = []): self
    {
        return new self([new EInvoicingFailureReason($code, $parameters)]);
    }
}
