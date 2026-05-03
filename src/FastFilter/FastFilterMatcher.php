<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

final class FastFilterMatcher
{
    public function __construct(
        private readonly FastFilterQueryService $queryService,
    ) {
    }

    /**
     * @param list<int>            $productIds
     * @param array<string, mixed> $criteria
     *
     * @return array{matchedProductIds: list<int>, matchedProducts: array<int, string>, matchedVariants: array<int, bool>}
     */
    public function match(array $productIds, array $criteria): array
    {
        $unitsByProduct = $this->queryService->getUnitsByProduct($productIds);
        $matchingUnits = $this->queryService->getMatchingUnits($productIds, $criteria);
        $matchedProductIds = [];
        $matchedProducts = [];
        $matchedVariants = [];

        foreach ($productIds as $productId) {
            $productUnits = $unitsByProduct[$productId] ?? [0 => true];
            $variantIds = array_values(array_filter(array_keys($productUnits)));
            $productUnitMatches = isset($matchingUnits[$productId][0]);
            $matchingVariantCount = 0;

            foreach ($variantIds as $variantId) {
                $variantMatches = isset($matchingUnits[$productId][$variantId]);
                $matchedVariants[$variantId] = $variantMatches;

                if ($variantMatches) {
                    $matchingVariantCount++;
                }
            }

            if ($productUnitMatches || ($variantIds === [] && isset($matchingUnits[$productId][0]))) {
                $matchedProductIds[] = $productId;
                $matchedProducts[$productId] = 'complete';
                continue;
            }

            if ($matchingVariantCount > 0) {
                $matchedProductIds[] = $productId;
                $matchedProducts[$productId] = $matchingVariantCount === count($variantIds) ? 'complete' : 'partial';
                continue;
            }

            $matchedProducts[$productId] = 'none';
        }

        return [
            'matchedProductIds' => $matchedProductIds,
            'matchedProducts' => $matchedProducts,
            'matchedVariants' => $matchedVariants,
        ];
    }
}
