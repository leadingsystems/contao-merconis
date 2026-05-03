<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

final class FastFilterSearchCoordinator
{
    public function __construct(
        private readonly FastFilterOptionProvider $optionProvider,
        private readonly FastFilterEstimateProvider $estimateProvider,
        private readonly FastFilterMatcher $matcher,
        private readonly FastFilterSessionWriter $sessionWriter,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return array{products: array<int, array<string, mixed>>, notAllProductsMatch: bool, numProductsNotMatching: int}
     */
    public function apply(array $products): array
    {
        $this->sessionWriter->ensureSession();

        $productIds = $this->extractProductIds($products);
        $availableOptions = $this->optionProvider->buildAvailableOptions($productIds);
        $this->sessionWriter->writeAvailableOptions($availableOptions);

        $criteria = $_SESSION['lsShop']['fastFilter']['criteria'] ?? ['attributes' => [], 'producers' => []];
        $matchEstimates = $this->estimateProvider->buildMatchEstimates($productIds, $criteria);
        $this->sessionWriter->writeMatchEstimates($matchEstimates);

        $matchResult = $this->matcher->match($productIds, $criteria);
        $this->sessionWriter->writeMatches($matchResult['matchedProducts'], $matchResult['matchedVariants']);

        $matchedProductIdMap = array_fill_keys($matchResult['matchedProductIds'], true);
        $matchedProducts = [];

        foreach ($products as $product) {
            if (isset($matchedProductIdMap[(int) $product['id']])) {
                $matchedProducts[] = $product;
            }
        }

        return [
            'products' => $matchedProducts,
            'notAllProductsMatch' => count($matchedProducts) !== count($products),
            'numProductsNotMatching' => count($products) - count($matchedProducts),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return list<int>
     */
    private function extractProductIds(array $products): array
    {
        $productIds = [];

        foreach ($products as $product) {
            if (isset($product['id'])) {
                $productIds[] = (int) $product['id'];
            }
        }

        return array_values(array_unique($productIds));
    }
}
