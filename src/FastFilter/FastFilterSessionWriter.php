<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

final class FastFilterSessionWriter
{
    /**
     * Erzeugt die isolierte `fastFilter`-Sessionstruktur.
     */
    public function ensureSession(): void
    {
        if (!isset($_SESSION['lsShop']['fastFilter'])) {
            $this->reset();
        }
    }

    /**
     * Setzt nur den neuen Session-Bereich zurück.
     */
    public function reset(): void
    {
        $_SESSION['lsShop']['fastFilter'] = [
            'criteria' => [
                'attributes' => [],
                'producers' => [],
            ],
            'availableOptions' => [
                'attributes' => [],
                'producers' => [],
            ],
            'matchEstimates' => [
                'attributes' => [],
                'producers' => [],
            ],
            'matchedProducts' => [],
            'matchedVariants' => [],
            'legacyBridgeActive' => false,
            'lastResetTimestamp' => time(),
        ];

        unset(
            $_SESSION['lsShop']['filter']['matchedProducts'],
            $_SESSION['lsShop']['filter']['matchedVariants'],
            $_SESSION['lsShop']['filter']['matchEstimates'],
        );
    }

    /**
     * Speichert normalisierte Kriterien als Quelle der Wahrheit.
     *
     * @param array<string, mixed> $criteria
     */
    public function writeCriteria(array $criteria): void
    {
        $this->ensureSession();

        $_SESSION['lsShop']['fastFilter']['criteria'] = [
            'attributes' => $criteria['attributes'] ?? [],
            'producers' => $criteria['producers'] ?? [],
        ];
    }

    /**
     * Speichert verfügbare Optionen für das neue Template.
     *
     * @param array<string, mixed> $availableOptions
     */
    public function writeAvailableOptions(array $availableOptions): void
    {
        $this->ensureSession();

        $_SESSION['lsShop']['fastFilter']['availableOptions'] = [
            'attributes' => $availableOptions['attributes'] ?? [],
            'producers' => $availableOptions['producers'] ?? [],
        ];
    }

    /**
     * Speichert die SQL-berechneten Trefferzahlen für das Fast-Filter-View-Model.
     *
     * @param array<string, mixed> $matchEstimates
     */
    public function writeMatchEstimates(array $matchEstimates): void
    {
        $this->ensureSession();

        $_SESSION['lsShop']['fastFilter']['matchEstimates'] = [
            'attributes' => $matchEstimates['attributes'] ?? [],
            'producers' => $matchEstimates['producers'] ?? [],
        ];
        $_SESSION['lsShop']['filter']['matchEstimates'] = [
            'attributeValues' => $this->flattenAttributeEstimates($matchEstimates['attributes'] ?? []),
            'flexContentLIValues' => [],
            'flexContentLDValues' => [],
            'producers' => $this->hashProducerEstimates($matchEstimates['producers'] ?? []),
        ];
        $_SESSION['lsShop']['filter']['noMatchEstimatesDetermined'] = false;
    }

    /**
     * Schreibt die schmale Legacy-Brücke für bestehende `_filterMatch`-Consumer.
     *
     * @param array<int, string> $matchedProducts
     * @param array<int, bool>   $matchedVariants
     */
    public function writeMatches(array $matchedProducts, array $matchedVariants): void
    {
        $this->ensureSession();

        $_SESSION['lsShop']['fastFilter']['matchedProducts'] = $matchedProducts;
        $_SESSION['lsShop']['fastFilter']['matchedVariants'] = $matchedVariants;
        $_SESSION['lsShop']['fastFilter']['legacyBridgeActive'] = true;

        $_SESSION['lsShop']['filter']['matchedProducts'] = $matchedProducts;
        $_SESSION['lsShop']['filter']['matchedVariants'] = $matchedVariants;
    }

    /**
     * Entfernt nur die vom Fast Filter erzeugten Brückendaten.
     */
    public function clearLegacyBridge(): void
    {
        if (!isset($_SESSION['lsShop']['fastFilter']['legacyBridgeActive']) || !$_SESSION['lsShop']['fastFilter']['legacyBridgeActive']) {
            return;
        }

        unset(
            $_SESSION['lsShop']['filter']['matchedProducts'],
            $_SESSION['lsShop']['filter']['matchedVariants'],
            $_SESSION['lsShop']['filter']['matchEstimates'],
        );
        $_SESSION['lsShop']['fastFilter']['legacyBridgeActive'] = false;
    }

    /**
     * @param array<int, array<int, array{products: int}>> $attributeEstimates
     *
     * @return array<int, array{products: int}>
     */
    private function flattenAttributeEstimates(array $attributeEstimates): array
    {
        $flattenedEstimates = [];

        foreach ($attributeEstimates as $estimatesByAttributeValue) {
            foreach ($estimatesByAttributeValue as $attributeValueId => $estimate) {
                $flattenedEstimates[(int) $attributeValueId] = [
                    'products' => (int) ($estimate['products'] ?? 0),
                ];
            }
        }

        return $flattenedEstimates;
    }

    /**
     * @param array<string, array{products: int}> $producerEstimates
     *
     * @return array<string, array{products: int}>
     */
    private function hashProducerEstimates(array $producerEstimates): array
    {
        $hashedEstimates = [];

        foreach ($producerEstimates as $producer => $estimate) {
            $hashedEstimates[md5($producer)] = [
                'products' => (int) ($estimate['products'] ?? 0),
            ];
        }

        return $hashedEstimates;
    }
}
