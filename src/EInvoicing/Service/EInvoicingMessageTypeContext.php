<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Service;

/**
 * Hält den Nachrichtentyp des Nachrichtenmodells, für das der Hook
 * `beforeSendingOrderMessage` zuletzt aufgerufen wurde. Geteilter
 * Container-Service: Hook und Fehler-Reporter verwenden dieselbe Instanz.
 *
 * Der Kontext ist an die Bestellung gebunden, für die er hinterlegt wurde.
 * Ein Kontext aus einer anderen Bestellung wird nie geliefert.
 */
final class EInvoicingMessageTypeContext
{
    private ?int $orderId = null;

    private ?int $messageTypeId = null;

    /**
     * Ersetzt den bisherigen Kontext vollständig, auch wenn Bestell-ID oder
     * Nachrichtentyp nicht ermittelbar sind (`null`).
     */
    public function remember(?int $orderId, ?int $messageTypeId): void
    {
        $this->orderId = $orderId;
        $this->messageTypeId = $messageTypeId;
    }

    /**
     * Liefert den Nachrichtentyp nur, wenn der Kontext für genau diese
     * Bestellung hinterlegt wurde.
     */
    public function getMessageTypeIdForOrder(int $orderId): ?int
    {
        if ($orderId <= 0 || $this->orderId !== $orderId) {
            return null;
        }

        return $this->messageTypeId;
    }
}
