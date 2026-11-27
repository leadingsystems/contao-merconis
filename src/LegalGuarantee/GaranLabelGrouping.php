<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee;

final class GaranLabelGrouping
{
    /**
     * Fasst Positionen mit gleichem Label zusammen (Spec 27, Abschnitt 7.3). Die Gruppen
     * folgen ihrer ersten Position, die Positionen ihrer Reihenfolge.
     *
     * @param list<array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string,
     *   position: array{productName: string, variantName: string, articleNumber: string}
     * }> $entries
     *
     * @return list<array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string,
     *   positions: list<array{productName: string, variantName: string, articleNumber: string}>
     * }>
     */
    public function groupEntries(array $entries): array
    {
        $groupsByKey = [];
        $groupOrder = [];

        foreach ($entries as $entry) {
            $normalizedEntry = $this->normalizeEntry($entry);
            $groupKey = $this->buildGroupKey($normalizedEntry);

            if (!isset($groupsByKey[$groupKey])) {
                $groupsByKey[$groupKey] = [
                    'productId' => $normalizedEntry['productId'],
                    'garanVersion' => $normalizedEntry['garanVersion'],
                    'brand' => $normalizedEntry['brand'],
                    'modelIdentifier' => $normalizedEntry['modelIdentifier'],
                    'durationMonths' => $normalizedEntry['durationMonths'],
                    'positions' => [],
                ];
                $groupOrder[] = $groupKey;
            }

            $groupsByKey[$groupKey]['positions'][] = $normalizedEntry['position'];
        }

        return array_values(array_map(
            static fn (string $groupKey): array => $groupsByKey[$groupKey],
            $groupOrder
        ));
    }

    /**
     * @param array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string,
     *   position: array{productName: string, variantName: string, articleNumber: string}
     * } $entry
     *
     * @return array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string,
     *   position: array{productName: string, variantName: string, articleNumber: string}
     * }
     */
    private function normalizeEntry(array $entry): array
    {
        return [
            'productId' => trim($entry['productId']),
            'garanVersion' => trim($entry['garanVersion']),
            'brand' => trim($entry['brand']),
            'modelIdentifier' => trim($entry['modelIdentifier']),
            'durationMonths' => trim($entry['durationMonths']),
            'position' => [
                'productName' => trim($entry['position']['productName']),
                'variantName' => trim($entry['position']['variantName']),
                'articleNumber' => trim($entry['position']['articleNumber']),
            ],
        ];
    }

    /**
     * @param array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string
     * } $entry
     */
    private function buildGroupKey(array $entry): string
    {
        return json_encode([
            $entry['productId'],
            $entry['garanVersion'],
            $entry['brand'],
            $entry['modelIdentifier'],
            $entry['durationMonths'],
        ], JSON_THROW_ON_ERROR);
    }
}
