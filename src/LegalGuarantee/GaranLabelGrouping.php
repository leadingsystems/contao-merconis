<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee;

final class GaranLabelGrouping
{
    /**
     * @param list<array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string,
     *   productName: string
     * }> $entries
     *
     * @return list<array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string,
     *   productNames: list<string>
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
                    'productNames' => [],
                ];
                $groupOrder[] = $groupKey;
            }

            $groupsByKey[$groupKey]['productNames'][] = $normalizedEntry['productName'];
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
     *   productName: string
     * } $entry
     *
     * @return array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string,
     *   productName: string
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
            'productName' => trim($entry['productName']),
        ];
    }

    /**
     * @param array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string,
     *   productName: string
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
