<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

use Contao\PageModel;

final class FastFilterSearchCoordinator
{
    public const REQUEST_MARKER = 'fastFilterFormDataHasBeenPrepared';

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
        $this->synchronizePageScope();
        $this->markFilterFormDataPreparedForCurrentRequest();

        $productIds = $this->extractProductIds($products);
        $availableOptions = $this->optionProvider->buildAvailableOptions($productIds);
        $this->sessionWriter->writeAvailableOptions($availableOptions);

        $criteria = $_SESSION['lsShop']['fastFilter']['criteria'] ?? ['attributes' => [], 'producers' => []];
        $effectiveCriteria = $this->buildEffectiveCriteria($criteria, $availableOptions);
        $matchEstimates = $this->estimateProvider->buildMatchEstimates($productIds, $effectiveCriteria);
        $this->sessionWriter->writeMatchEstimates($matchEstimates);

        $matchResult = $this->matcher->match($productIds, $effectiveCriteria);
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

    private function synchronizePageScope(): void
    {
        $currentPageId = $this->getCurrentPageId();

        if ($currentPageId === null) {
            return;
        }

        $pageScope = $this->getPageScope();

        if ($pageScope === null) {
            $this->sessionWriter->reset();
            $this->sessionWriter->writePageScope($currentPageId);

            return;
        }

        if ($this->shouldKeepCriteriaForPageScope($currentPageId, $pageScope)) {
            return;
        }

        $this->sessionWriter->reset();
        $this->sessionWriter->writePageScope($currentPageId);
    }

    private function shouldKeepCriteriaForPageScope(int $currentPageId, int $pageScope): bool
    {
        $resetMode = (string) ($GLOBALS['merconis_globals']['ls_shop_fastFilterResetMode'] ?? 'always');

        if ($resetMode === 'never') {
            return true;
        }

        if ($currentPageId === $pageScope) {
            return true;
        }

        if ($resetMode !== 'branch') {
            return false;
        }

        if (in_array($pageScope, $this->getCurrentPageTrail(), true)) {
            return true;
        }

        $pageScopeModel = PageModel::findById($pageScope);

        if (!$pageScopeModel instanceof PageModel) {
            return false;
        }

        return in_array($currentPageId, $this->normalizePageTrail($pageScopeModel->trail), true);
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $availableOptions
     *
     * @return array{attributes: array<int, list<int>>, producers: list<string>}
     */
    private function buildEffectiveCriteria(array $criteria, array $availableOptions): array
    {
        $effectiveCriteria = [
            'attributes' => [],
            'producers' => [],
        ];
        $availableAttributes = is_array($availableOptions['attributes'] ?? null) ? $availableOptions['attributes'] : [];
        $attributeCriteria = is_array($criteria['attributes'] ?? null) ? $criteria['attributes'] : [];

        foreach ($attributeCriteria as $attributeId => $selectedValues) {
            $attributeId = (int) $attributeId;
            $availableAttributeValues = $availableAttributes[$attributeId] ?? null;

            if (!is_array($availableAttributeValues) || $availableAttributeValues === []) {
                continue;
            }

            $selectedValues = is_array($selectedValues) ? $selectedValues : [$selectedValues];
            $effectiveAttributeValueIds = [];

            foreach ($selectedValues as $selectedValue) {
                $attributeValueId = (int) $selectedValue;

                if (isset($availableAttributeValues[$attributeValueId])) {
                    $effectiveAttributeValueIds[] = $attributeValueId;
                }
            }

            $effectiveAttributeValueIds = array_values(array_unique($effectiveAttributeValueIds));

            if ($effectiveAttributeValueIds !== []) {
                $effectiveCriteria['attributes'][$attributeId] = $effectiveAttributeValueIds;
            }
        }

        $availableProducers = is_array($availableOptions['producers'] ?? null) ? $availableOptions['producers'] : [];
        $availableProducerMap = array_fill_keys(array_map('strval', $availableProducers), true);
        $producerCriteria = is_array($criteria['producers'] ?? null) ? $criteria['producers'] : [];

        foreach ($producerCriteria as $selectedProducer) {
            $producer = (string) $selectedProducer;

            if (isset($availableProducerMap[$producer])) {
                $effectiveCriteria['producers'][] = $producer;
            }
        }

        $effectiveCriteria['producers'] = array_values(array_unique($effectiveCriteria['producers']));

        return $effectiveCriteria;
    }

    private function getCurrentPageId(): ?int
    {
        if (!isset($GLOBALS['objPage']->id)) {
            return null;
        }

        return (int) $GLOBALS['objPage']->id;
    }

    private function getPageScope(): ?int
    {
        $pageScope = $_SESSION['lsShop']['fastFilter']['pageScope'] ?? null;

        if ($pageScope === null || $pageScope === '') {
            return null;
        }

        return (int) $pageScope;
    }

    /**
     * @return list<int>
     */
    private function getCurrentPageTrail(): array
    {
        return $this->normalizePageTrail($GLOBALS['objPage']->trail ?? []);
    }

    /**
     * @param mixed $pageTrail
     *
     * @return list<int>
     */
    private function normalizePageTrail(mixed $pageTrail): array
    {
        if (!is_array($pageTrail)) {
            return [];
        }

        return array_values(array_map('intval', $pageTrail));
    }

    private function markFilterFormDataPreparedForCurrentRequest(): void
    {
        $GLOBALS['merconis_globals'][self::REQUEST_MARKER] = true;
    }
}
