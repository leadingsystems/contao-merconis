<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

use Contao\Database;

final class FastFilterEstimateProvider
{
    public function __construct(
        private readonly FastFilterConfigurationRepository $configurationRepository,
    ) {
    }

    /**
     * Berechnet die Trefferzahlen pro aktiver Filteroption auf dem materialisierten Index.
     *
     * @param list<int>            $productIds
     * @param array<string, mixed> $criteria
     *
     * @return array{attributes: array<int, array<int, array{products: int}>>, producers: array<string, array{products: int}>}
     */
    public function buildMatchEstimates(array $productIds, array $criteria): array
    {
        $productIds = $this->normalizeProductIds($productIds);

        if ($productIds === []) {
            return [
                'attributes' => [],
                'producers' => [],
            ];
        }

        $attributeCriteria = $this->normalizeAttributeCriteria($criteria['attributes'] ?? []);
        $producerCriteria = $this->normalizeProducerCriteria($criteria['producers'] ?? []);
        $attributeEstimates = [];
        $producerEstimates = [];
        $attributeEstimateGroups = [];
        $activeProducerFilterExists = false;

        foreach ($this->configurationRepository->getActiveFields() as $field) {
            if ($field['dataSource'] === 'attribute' && (int) $field['sourceAttribute'] > 0) {
                $targetAttributeId = (int) $field['sourceAttribute'];
                $otherAttributeCriteria = $attributeCriteria;
                unset($otherAttributeCriteria[$targetAttributeId]);
                $criteriaSignature = $this->createCriteriaSignature($otherAttributeCriteria, $producerCriteria);

                if (!isset($attributeEstimateGroups[$criteriaSignature])) {
                    $attributeEstimateGroups[$criteriaSignature] = [
                        'attributeCriteria' => $otherAttributeCriteria,
                        'producerCriteria' => $producerCriteria,
                        'attributeIds' => [],
                    ];
                }

                $attributeEstimateGroups[$criteriaSignature]['attributeIds'][] = $targetAttributeId;
            }

            if ($field['dataSource'] === 'producer') {
                $activeProducerFilterExists = true;
            }
        }

        foreach ($attributeEstimateGroups as $attributeEstimateGroup) {
            $attributeEstimates += $this->calculateAttributeEstimatesForAttributes(
                $productIds,
                $attributeEstimateGroup['attributeIds'],
                $attributeEstimateGroup['attributeCriteria'],
                $attributeEstimateGroup['producerCriteria'],
            );
        }

        if ($activeProducerFilterExists) {
            $producerEstimates = $this->calculateProducerEstimates($productIds, $attributeCriteria);
        }

        return [
            'attributes' => $attributeEstimates,
            'producers' => $producerEstimates,
        ];
    }

    /**
     * @param list<int>            $productIds
     * @param list<int>            $targetAttributeIds
     * @param array<int, list<int>> $attributeCriteria
     * @param list<string>         $producerCriteria
     *
     * @return array<int, array<int, array{products: int}>>
     */
    private function calculateAttributeEstimatesForAttributes(
        array $productIds,
        array $targetAttributeIds,
        array $attributeCriteria,
        array $producerCriteria,
    ): array {
        if ($targetAttributeIds === []) {
            return [];
        }

        if ($attributeCriteria === [] && $producerCriteria === []) {
            return $this->calculateDirectAttributeEstimates($productIds, $targetAttributeIds);
        }

        $temporaryTableName = $this->createTemporaryTableName();
        $this->createMatchingUnitsTable($temporaryTableName, $productIds, $attributeCriteria, $producerCriteria);

        try {
            $queryParameters = $targetAttributeIds;
            $estimateResult = Database::getInstance()->prepare("
                SELECT      `target`.`attribute_id`,
                            `target`.`attribute_value_id`,
                            COUNT(DISTINCT `target`.`product_id`) AS `product_estimate`
                FROM        `" . $temporaryTableName . "` `matching_units`
                INNER JOIN  `tl_ls_shop_fast_filter_index` `target`
                    ON      `target`.`product_id` = `matching_units`.`product_id`
                    AND     `target`.`variant_id` = `matching_units`.`variant_id`
                WHERE       `target`.`attribute_id` IN (" . $this->createPlaceholders($targetAttributeIds) . ")
                    AND     `target`.`attribute_value_id` > 0
                GROUP BY    `target`.`attribute_id`, `target`.`attribute_value_id`
            ")->execute(...$queryParameters);

            return $this->mapAttributeEstimateRows($estimateResult);
        } finally {
            Database::getInstance()->execute("DROP TEMPORARY TABLE IF EXISTS `" . $temporaryTableName . "`");
        }
    }

    /**
     * @param list<int> $productIds
     * @param list<int> $targetAttributeIds
     *
     * @return array<int, array<int, array{products: int}>>
     */
    private function calculateDirectAttributeEstimates(array $productIds, array $targetAttributeIds): array
    {
        $queryParameters = array_merge($productIds, $targetAttributeIds);
        $estimateResult = Database::getInstance()->prepare("
            SELECT      `target`.`attribute_id`,
                        `target`.`attribute_value_id`,
                        COUNT(DISTINCT `target`.`product_id`) AS `product_estimate`
            FROM        `tl_ls_shop_fast_filter_index` `target`
            WHERE       `target`.`product_id` IN (" . $this->createPlaceholders($productIds) . ")
                AND     `target`.`attribute_id` IN (" . $this->createPlaceholders($targetAttributeIds) . ")
                AND     `target`.`attribute_value_id` > 0
            GROUP BY    `target`.`attribute_id`, `target`.`attribute_value_id`
        ")->execute(...$queryParameters);

        return $this->mapAttributeEstimateRows($estimateResult);
    }

    /**
     * @param object $estimateResult
     *
     * @return array<int, array<int, array{products: int}>>
     */
    private function mapAttributeEstimateRows(object $estimateResult): array
    {
        $estimatesByAttribute = [];

        while ($estimateResult->next()) {
            $attributeId = (int) $estimateResult->attribute_id;
            $attributeValueId = (int) $estimateResult->attribute_value_id;
            $estimatesByAttribute[$attributeId][$attributeValueId] = [
                'products' => (int) $estimateResult->product_estimate,
            ];
        }

        return $estimatesByAttribute;
    }

    /**
     * @param list<int>            $productIds
     * @param array<int, list<int>> $attributeCriteria
     * @param list<string>         $producerCriteria
     */
    private function createMatchingUnitsTable(
        string $temporaryTableName,
        array $productIds,
        array $attributeCriteria,
        array $producerCriteria,
        bool $includeProducer = false,
    ): void {
        $producerColumnDefinition = $includeProducer ? ",
                `producer` varchar(255) NOT NULL default ''" : '';
        $producerKeyDefinition = $includeProducer ? ",
                KEY `producer` (`producer`)" : '';

        Database::getInstance()->execute("
            CREATE TEMPORARY TABLE `" . $temporaryTableName . "` (
                `product_id` int unsigned NOT NULL,
                `variant_id` int unsigned NOT NULL
                " . $producerColumnDefinition . ",
                PRIMARY KEY (`product_id`, `variant_id`)
                " . $producerKeyDefinition . "
            ) ENGINE=MEMORY
        ");

        $queryParameters = $productIds;
        $producerCondition = $this->createProducerCondition($producerCriteria, $queryParameters);
        $attributeCriteriaParameters = [];
        $attributeCriteriaCondition = $this->createAttributeCriteriaCondition($attributeCriteria, $attributeCriteriaParameters);
        $queryParameters = array_merge($queryParameters, $attributeCriteriaParameters);

        $havingCondition = '';

        if ($attributeCriteria !== []) {
            $queryParameters[] = count($attributeCriteria);
            $havingCondition = "HAVING      COUNT(DISTINCT `attribute_id`) = ?";
        }

        $indexHint = $attributeCriteria !== [] ? " FORCE INDEX (`attribute_id_attribute_value_id_product_id_variant_id`)" : '';
        $insertColumns = $includeProducer ? "(`product_id`, `variant_id`, `producer`)" : "(`product_id`, `variant_id`)";
        $producerSelect = $includeProducer ? ", MAX(`producer`)" : '';

        Database::getInstance()->prepare("
            INSERT IGNORE INTO `" . $temporaryTableName . "` " . $insertColumns . "
            SELECT      `product_id`, `variant_id`" . $producerSelect . "
            FROM        `tl_ls_shop_fast_filter_index`" . $indexHint . "
            WHERE       `product_id` IN (" . $this->createPlaceholders($productIds) . ")
                        " . $producerCondition . "
                        " . $attributeCriteriaCondition . "
            GROUP BY    `product_id`, `variant_id`
                        " . $havingCondition . "
        ")->execute(...$queryParameters);
    }

    /**
     * @param list<int>            $productIds
     * @param array<int, list<int>> $attributeCriteria
     *
     * @return array<string, array{products: int}>
     */
    private function calculateProducerEstimates(array $productIds, array $attributeCriteria): array
    {
        if ($attributeCriteria === []) {
            return $this->calculateDirectProducerEstimates($productIds);
        }

        $temporaryTableName = $this->createTemporaryTableName();
        $this->createMatchingUnitsTable($temporaryTableName, $productIds, $attributeCriteria, [], true);

        try {
            $estimateResult = Database::getInstance()->execute("
                SELECT      `producer`,
                            COUNT(DISTINCT `product_id`) AS `product_estimate`
                FROM        `" . $temporaryTableName . "`
                WHERE       `producer` != ''
                GROUP BY    `producer`
                ORDER BY    `producer` ASC
            ");

            return $this->mapProducerEstimateRows($estimateResult);
        } finally {
            Database::getInstance()->execute("DROP TEMPORARY TABLE IF EXISTS `" . $temporaryTableName . "`");
        }
    }

    /**
     * @param list<int> $productIds
     *
     * @return array<string, array{products: int}>
     */
    private function calculateDirectProducerEstimates(array $productIds): array
    {
        $estimateResult = Database::getInstance()->prepare("
            SELECT      `target`.`producer`,
                        COUNT(DISTINCT `target`.`product_id`) AS `product_estimate`
            FROM        `tl_ls_shop_fast_filter_index` `target`
            WHERE       `target`.`product_id` IN (" . $this->createPlaceholders($productIds) . ")
                AND     `target`.`producer` != ''
            GROUP BY    `target`.`producer`
            ORDER BY    `target`.`producer` ASC
        ")->execute(...$productIds);

        return $this->mapProducerEstimateRows($estimateResult);
    }

    /**
     * @param object $estimateResult
     *
     * @return array<string, array{products: int}>
     */
    private function mapProducerEstimateRows(object $estimateResult): array
    {
        $estimatesByProducer = [];

        while ($estimateResult->next()) {
            $estimatesByProducer[(string) $estimateResult->producer] = [
                'products' => (int) $estimateResult->product_estimate,
            ];
        }

        return $estimatesByProducer;
    }

    /**
     * @param array<int|string, mixed> $queryParameters
     * @param array<int, list<int>>    $attributeCriteria
     */
    private function createAttributeCriteriaCondition(array $attributeCriteria, array &$queryParameters): string
    {
        if ($attributeCriteria === []) {
            return '';
        }

        $conditionParts = [];

        foreach ($attributeCriteria as $attributeId => $attributeValueIds) {
            $conditionParts[] = "(`attribute_id` = ? AND `attribute_value_id` IN (" . $this->createPlaceholders($attributeValueIds) . "))";
            $queryParameters[] = $attributeId;
            $queryParameters = array_merge($queryParameters, $attributeValueIds);
        }

        return " AND (" . implode(' OR ', $conditionParts) . ")";
    }

    /**
     * @param list<string>            $producerCriteria
     * @param array<int|string, mixed> $queryParameters
     */
    private function createProducerCondition(array $producerCriteria, array &$queryParameters): string
    {
        if ($producerCriteria === []) {
            return '';
        }

        $queryParameters = array_merge($queryParameters, $producerCriteria);

        return " AND `producer` IN (" . $this->createPlaceholders($producerCriteria) . ")";
    }

    private function createTemporaryTableName(): string
    {
        return str_replace('.', '_', uniqid('tmp_ff_estimates_', true));
    }

    /**
     * @param array<int, list<int>> $attributeCriteria
     * @param list<string>          $producerCriteria
     */
    private function createCriteriaSignature(array $attributeCriteria, array $producerCriteria): string
    {
        ksort($attributeCriteria);
        sort($producerCriteria);

        return (string) json_encode([$attributeCriteria, $producerCriteria]);
    }

    /**
     * @param array<int|string, mixed> $values
     */
    private function createPlaceholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }

    /**
     * @param list<int> $productIds
     *
     * @return list<int>
     */
    private function normalizeProductIds(array $productIds): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $productIds))));
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
            $values = array_values(array_unique(array_filter(array_map('intval', $values))));

            if ($values !== []) {
                $normalizedCriteria[(int) $attributeId] = $values;
            }
        }

        return $normalizedCriteria;
    }

    /**
     * @param mixed $producerCriteria
     *
     * @return list<string>
     */
    private function normalizeProducerCriteria(mixed $producerCriteria): array
    {
        if (!is_array($producerCriteria)) {
            return [];
        }

        $normalizedCriteria = [];

        foreach ($producerCriteria as $producer) {
            $producer = trim((string) $producer);

            if ($producer !== '') {
                $normalizedCriteria[] = $producer;
            }
        }

        return array_values(array_unique($normalizedCriteria));
    }
}
