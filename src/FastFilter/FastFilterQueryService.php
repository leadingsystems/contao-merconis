<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

use Contao\Database;

class FastFilterQueryService
{
    /**
     * @param list<int> $productIds
     * @param list<int> $attributeIds
     *
     * @return array<int, array<int, array{numericValue: string|null}>>
     */
    public function getAvailableAttributeOptions(array $productIds, array $attributeIds): array
    {
        if ($productIds === [] || $attributeIds === []) {
            return [];
        }

        $availableOptions = [];
        $query = "
            SELECT      `attribute_id`, `attribute_value_id`, MAX(`numeric_value`) AS `numeric_value`
            FROM        `tl_ls_shop_fast_filter_index`
            WHERE       `product_id` IN (" . $this->createPlaceholders($productIds) . ")
                AND     `attribute_id` IN (" . $this->createPlaceholders($attributeIds) . ")
                AND     `attribute_value_id` > 0
            GROUP BY    `attribute_id`, `attribute_value_id`
        ";

        $optionResult = Database::getInstance()->prepare($query)->execute(...array_merge($productIds, $attributeIds));

        while ($optionResult->next()) {
            $attributeId = (int) $optionResult->attribute_id;
            $attributeValueId = (int) $optionResult->attribute_value_id;
            $availableOptions[$attributeId][$attributeValueId] = [
                'numericValue' => $optionResult->numeric_value,
            ];
        }

        return $availableOptions;
    }

    /**
     * @param list<int> $productIds
     *
     * @return list<string>
     */
    public function getAvailableProducers(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $producers = [];
        $producerResult = Database::getInstance()->prepare("
            SELECT      `producer`
            FROM        `tl_ls_shop_fast_filter_index`
            WHERE       `product_id` IN (" . $this->createPlaceholders($productIds) . ")
                AND     `producer` != ''
            GROUP BY    `producer`
            ORDER BY    `producer` ASC
        ")->execute(...$productIds);

        while ($producerResult->next()) {
            $producers[] = (string) $producerResult->producer;
        }

        return $producers;
    }

    /**
     * @param list<int> $productIds
     * @param list<int> $attributeIds
     *
     * @return array<int, int>
     */
    public function getDistinctAttributeValueCounts(array $productIds, array $attributeIds): array
    {
        if ($productIds === [] || $attributeIds === []) {
            return [];
        }

        $valueCounts = [];
        $countResult = Database::getInstance()->prepare("
            SELECT      `attribute_id`, COUNT(DISTINCT `attribute_value_id`) AS `attribute_value_count`
            FROM        `tl_ls_shop_fast_filter_index`
            WHERE       `product_id` IN (" . $this->createPlaceholders($productIds) . ")
                AND     `attribute_id` IN (" . $this->createPlaceholders($attributeIds) . ")
                AND     `attribute_value_id` > 0
            GROUP BY    `attribute_id`
        ")->execute(...array_merge($productIds, $attributeIds));

        while ($countResult->next()) {
            $valueCounts[(int) $countResult->attribute_id] = (int) $countResult->attribute_value_count;
        }

        return $valueCounts;
    }

    /**
     * @param list<int> $productIds
     *
     * @return array<int, array<int, bool>>
     */
    public function getUnitsByProduct(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $unitsByProduct = [];
        $unitResult = Database::getInstance()->prepare("
            SELECT      `product_id`, `variant_id`
            FROM        `tl_ls_shop_fast_filter_index`
            WHERE       `product_id` IN (" . $this->createPlaceholders($productIds) . ")
            GROUP BY    `product_id`, `variant_id`
        ")->execute(...$productIds);

        while ($unitResult->next()) {
            $productId = (int) $unitResult->product_id;
            $variantId = (int) $unitResult->variant_id;
            $unitsByProduct[$productId][$variantId] = true;
        }

        return $unitsByProduct;
    }

    /**
     * @param list<int>             $productIds
     * @param array<string, mixed>  $criteria
     *
     * @return array<int, array<int, bool>>
     */
    public function getMatchingUnits(array $productIds, array $criteria): array
    {
        if ($productIds === []) {
            return [];
        }

        $candidateUnits = $this->getProducerMatchedUnits($productIds, $criteria['producers'] ?? []);
        $attributeCriteria = $this->normalizeAttributeCriteria($criteria['attributes'] ?? []);

        if ($attributeCriteria === []) {
            return $candidateUnits;
        }

        $attributeMatchedUnits = $this->getAttributeMatchedUnits(array_keys($candidateUnits), $attributeCriteria);
        $matchingUnits = [];

        foreach ($attributeMatchedUnits as $productId => $variantMatches) {
            foreach ($variantMatches as $variantId => $matched) {
                if (isset($candidateUnits[$productId][$variantId])) {
                    $matchingUnits[$productId][$variantId] = $matched;
                }
            }
        }

        return $matchingUnits;
    }

    /**
     * @param list<int>    $productIds
     * @param list<string> $producers
     *
     * @return array<int, array<int, bool>>
     */
    private function getProducerMatchedUnits(array $productIds, array $producers): array
    {
        $queryParameters = $productIds;
        $producerCondition = '';

        if ($producers !== []) {
            $producerCondition = " AND `producer` IN (" . $this->createPlaceholders($producers) . ")";
            $queryParameters = array_merge($queryParameters, $producers);
        }

        $matchedUnits = [];
        $unitResult = Database::getInstance()->prepare("
            SELECT      `product_id`, `variant_id`
            FROM        `tl_ls_shop_fast_filter_index`
            WHERE       `product_id` IN (" . $this->createPlaceholders($productIds) . ")
                " . $producerCondition . "
            GROUP BY    `product_id`, `variant_id`
        ")->execute(...$queryParameters);

        while ($unitResult->next()) {
            $matchedUnits[(int) $unitResult->product_id][(int) $unitResult->variant_id] = true;
        }

        return $matchedUnits;
    }

    /**
     * @param list<int>                 $productIds
     * @param array<int, list<int>>      $attributeCriteria
     *
     * @return array<int, array<int, bool>>
     */
    private function getAttributeMatchedUnits(array $productIds, array $attributeCriteria): array
    {
        if ($productIds === []) {
            return [];
        }

        $conditionParts = [];
        $queryParameters = $productIds;

        foreach ($attributeCriteria as $attributeId => $attributeValueIds) {
            $conditionParts[] = "(`attribute_id` = ? AND `attribute_value_id` IN (" . $this->createPlaceholders($attributeValueIds) . "))";
            $queryParameters[] = $attributeId;
            $queryParameters = array_merge($queryParameters, $attributeValueIds);
        }

        $queryParameters[] = count($attributeCriteria);
        $matchedUnits = [];
        $unitResult = Database::getInstance()->prepare("
            SELECT      `product_id`, `variant_id`
            FROM        `tl_ls_shop_fast_filter_index`
            WHERE       `product_id` IN (" . $this->createPlaceholders($productIds) . ")
                AND     (" . implode(' OR ', $conditionParts) . ")
            GROUP BY    `product_id`, `variant_id`
            HAVING      COUNT(DISTINCT `attribute_id`) = ?
        ")->execute(...$queryParameters);

        while ($unitResult->next()) {
            $matchedUnits[(int) $unitResult->product_id][(int) $unitResult->variant_id] = true;
        }

        return $matchedUnits;
    }

    /**
     * @param array<int|string, mixed> $values
     */
    private function createPlaceholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }

    /**
     * @param mixed $attributeCriteria
     *
     * @return array<int, list<int>>
     */
    private function normalizeAttributeCriteria(mixed $attributeCriteria): array
    {
        if (!is_array($attributeCriteria)) {
            return [];
        }

        $normalizedCriteria = [];

        foreach ($attributeCriteria as $attributeId => $attributeValueIds) {
            $values = is_array($attributeValueIds) ? $attributeValueIds : [$attributeValueIds];
            $values = array_values(array_filter(array_map('intval', $values)));

            if ($values !== []) {
                $normalizedCriteria[(int) $attributeId] = $values;
            }
        }

        return $normalizedCriteria;
    }
}
