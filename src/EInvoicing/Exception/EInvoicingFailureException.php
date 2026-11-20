<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Exception;

use InvalidArgumentException;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReason;
use RuntimeException;
use Throwable;

/**
 * Basis aller eigenen Fehler der E-Rechnungs-Schicht. Trägt die
 * strukturierten Fehlergründe; die Exception-Meldung enthält nur die
 * Fehlercodes und ist nicht für die Ausgabe an Menschen bestimmt.
 */
abstract class EInvoicingFailureException extends RuntimeException
{
    /**
     * @param list<EInvoicingFailureReason> $failureReasons
     *
     * @throws InvalidArgumentException Wenn keine Fehlergründe übergeben werden
     */
    public function __construct(
        public readonly array $failureReasons,
        ?Throwable $previous = null,
    ) {
        if ($failureReasons === []) {
            throw new InvalidArgumentException('At least one failure reason is required.');
        }

        parent::__construct(
            implode(', ', array_map(
                static fn (EInvoicingFailureReason $failureReason): string => $failureReason->code->value,
                $failureReasons
            )),
            0,
            $previous
        );
    }
}
