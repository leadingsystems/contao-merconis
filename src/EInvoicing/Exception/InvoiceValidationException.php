<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Exception;

/**
 * Pflichtfeld- oder Konsistenzverletzungen bei der Befüllung des
 * semantischen Rechnungsmodells. Mehrere gleichzeitige Verletzungen
 * bleiben als einzelne Fehlergründe erhalten.
 */
final class InvoiceValidationException extends EInvoicingFailureException
{
}
