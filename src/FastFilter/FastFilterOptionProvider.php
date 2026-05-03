<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

final class FastFilterOptionProvider
{
    public function __construct(
        private readonly FastFilterConfigurationRepository $configurationRepository,
        private readonly FastFilterQueryService $queryService,
    ) {
    }

    /**
     * @param list<int> $productIds
     *
     * @return array<string, mixed>
     */
    public function buildAvailableOptions(array $productIds): array
    {
        $attributeIds = [];
        $attributesThatCanBeHidden = [];

        foreach ($this->configurationRepository->getActiveFields() as $field) {
            if ($field['dataSource'] === 'attribute' && (int) $field['sourceAttribute'] > 0) {
                $attributeId = (int) $field['sourceAttribute'];
                $attributeIds[] = $attributeId;

                if (!empty($field['disableFilterIfOnlyOneValue'])) {
                    $attributesThatCanBeHidden[] = $attributeId;
                }
            }
        }

        $attributeIds = array_values(array_unique($attributeIds));

        if ($attributesThatCanBeHidden !== []) {
            $attributeIds = $this->removeAttributesWithOnlyOneAvailableValue(
                $productIds,
                $attributeIds,
                array_values(array_unique($attributesThatCanBeHidden)),
            );
        }

        return [
            'attributes' => $this->queryService->getAvailableAttributeOptions($productIds, $attributeIds),
            'producers' => $this->queryService->getAvailableProducers($productIds),
        ];
    }

    /**
     * @param list<int> $productIds
     * @param list<int> $attributeIds
     * @param list<int> $attributesThatCanBeHidden
     *
     * @return list<int>
     */
    private function removeAttributesWithOnlyOneAvailableValue(
        array $productIds,
        array $attributeIds,
        array $attributesThatCanBeHidden,
    ): array {
        $valueCounts = $this->queryService->getDistinctAttributeValueCounts($productIds, $attributesThatCanBeHidden);

        return array_values(
            array_filter(
                $attributeIds,
                static function (int $attributeId) use ($attributesThatCanBeHidden, $valueCounts): bool {
                    return !in_array($attributeId, $attributesThatCanBeHidden, true)
                        || ($valueCounts[$attributeId] ?? 0) > 1;
                }
            )
        );
    }
}
