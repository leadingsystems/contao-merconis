<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\License;

/**
 * Ergebnis einer technischen License-Key-Prüfung.
 */
final class LicenseKeyVerificationResult
{
    /**
     * @param array<string, mixed>|null $payload
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly string $reasonCode,
        public readonly ?array $payload = null,
    ) {
    }
}

