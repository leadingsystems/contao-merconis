<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Failure;

use LeadingSystems\MerconisBundle\EInvoicing\Exception\EInvoicingFailureException;
use Throwable;

/**
 * Einzige Stelle, an der ein beliebiges `Throwable` auf Fehlergründe
 * abgebildet wird: Eigene Fehler liefern ihre Codes, alle übrigen
 * ergeben `EInvoicingErrorCode::RUNTIME_ERROR` mit der unübersetzten
 * Original-Meldung als Parameter (entfällt bei leerer Meldung).
 */
final class EInvoicingFailureReasonMapper
{
    /**
     * @return list<EInvoicingFailureReason>
     */
    public static function fromThrowable(Throwable $throwable): array
    {
        if ($throwable instanceof EInvoicingFailureException) {
            return $throwable->failureReasons;
        }

        $originalMessage = trim($throwable->getMessage());

        if ($originalMessage === '') {
            return [new EInvoicingFailureReason(EInvoicingErrorCode::RUNTIME_ERROR)];
        }

        return [new EInvoicingFailureReason(EInvoicingErrorCode::RUNTIME_ERROR, ['message' => $originalMessage])];
    }
}
