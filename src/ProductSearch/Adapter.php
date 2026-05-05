<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use Contao\Controller;
use Contao\System;
use LeadingSystems\MerconisBundle\Common\Session\ObjectStatePersistor\ObjectStatePersistorTrait;
use LeadingSystems\MerconisBundle\ProductSearch\Enum\Mode;
use LeadingSystems\MerconisBundle\ProductSearch\Enum\MappingMode;
use LeadingSystems\MerconisBundle\SearchServer\SearchServer;
use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_productSearcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use LeadingSystems\MerconisBundle\ProductSearch\SearchTermMappingService;
use LeadingSystems\MerconisBundle\ProductSearch\FacetPresenter;

class Adapter
{
    use ObjectStatePersistorTrait;

    private LoggerInterface $logger;
    private SearchServer $searchServer;
    private ls_shop_productSearcher $standardSearchClient;
    private ?Mode $mode = null;
    private bool $useFilter;
    private ?string $productListId;

    private array $searchCriteria =  ['title' => '*', 'published' => '1'];
    /** Caller-provided, un-augmented criteria for re-augmentation on mapping changes */
    private array $rawSearchCriteria =  ['title' => '*', 'published' => '1'];
    private int $numPerPage = 0;
    private int $currentPage = 1;
    private array $sortingCriteria = [['field' => 'title', 'direction' => 'ASC']];
    private array $fixedSorting = [];
    private int $truncateResultsIfMoreThan = 0;
    private bool $cancelSearchIfMoreThanTruncateLimit = false;
    private bool $emptyFieldMatchesPerDefault = false;
    private int $maxResults = 0;

    private SearchResult $searchResult;
    private Environment $twig;
    private RequestStack $requestStack;
    private Helper $helper;
    private TranslatorInterface $translator;
    private SearchTermMappingService $termMappingService;

	/** Mapping mode: 'quick' or 'full' to select mappings */
	private MappingMode $mappingMode = MappingMode::Full;

    public function __construct(SearchServer $searchServer, Helper $helper, LoggerInterface $logger, Environment $twig, RequestStack $requestStack, TranslatorInterface $translator, SearchTermMappingService $termMappingService)
    {
        $this->searchServer = $searchServer;
        $this->logger = $logger;
        $this->searchResult = new SearchResult([], null);
        $this->twig = $twig;
        $this->requestStack = $requestStack;
        $this->helper = $helper;
        $this->translator = $translator;
        $this->termMappingService = $termMappingService;
    }

    private function augmentFulltextIfApplicable(string $fulltext): string
    {
        if ($fulltext === '') {
            return $fulltext;
        }
        try {
            $mode = $this->mode ?? null;
            $applyAugmentation = $this->termMappingService->isEnabled() && (
                $mode === null || $mode === Mode::Standard || ($mode === Mode::SearchServer && $this->termMappingService->isApplyInElasticsearch())
            );
            if ($applyAugmentation) {
				return $this->termMappingService->augment($fulltext, $this->mappingMode);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Search term augmentation failed: ' . $e->getMessage());
        }
        return $fulltext;
    }

    public function setMode(Mode $mode): void
    {
        $this->mode = $mode;
    }

    private function setDefaultMode(): void
    {
        /*
         * Do me! The default mode should be defined as a system/environment setting.
         *  Maybe it should be configurable in the Contao backend?
         */
        $this->setMode(Mode::SearchServer);
    }

	public function setMappingMode(MappingMode $mode): void
	{
		$this->mappingMode = $mode;
        // Ensure criteria reflect new mapping mode even if set earlier
        $this->reaugmentCriteriaForCurrentMapping();
	}

    public function initialize(bool $useFilter = false, ?string $productListId = null): void
    {
        $this->useFilter = $useFilter;
        $this->productListId = $productListId;

        if ($this->mode === null) {
            $this->setDefaultMode();
        }
		if (!isset($this->mappingMode)) {
			$this->setMappingMode(MappingMode::Full);
		}

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient = new ls_shop_productSearcher($this->useFilter, $this->productListId);
                $this->standardSearchClient->setNonLegacyUsage();
                break;

            case Mode::SearchServer:
                /*
                 * The searchEngine service is received through DI and does not need to be instantiated.
                 * Therefore, just break.
                 */
                break;

            default:
                throw new \Exception('Unexpected mode "' . $this->mode->name . '" not implemented yet.');
                break;
        }

        $this->initializePersistor(['searchCriteria'], $this->productListId . '::' . $this->useFilter . '::' . $this->mode->name);

        $this->receiveUserInput();
    }

    public function receiveUserInput(): void
    {
        if (!$this->useFilter) {
            return;
        }
        $request = $this->requestStack->getCurrentRequest()->request;
        if ($request->get('FORM_SUBMIT') !== 'filterUI::' . $this->productListId) {
            return;
        }

        $filter = $request->get('filter', []);

        $decoded = array_map(function($item) { return json_decode($item, true); }, $filter ?? []);
        $attributeFilters = [];
        $producerFilters = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) { continue; }
            if (isset($entry['producer'])) {
                $val = trim((string) $entry['producer']);
                if ($val !== '') { $producerFilters[] = $val; }
                continue;
            }
            if (isset($entry['attribute_id']) && isset($entry['value_id'])) {
                $attributeFilters[] = ['attribute_id' => (int) $entry['attribute_id'], 'value_id' => (int) $entry['value_id']];
            }
        }

        $this->setSearchCriterion('attributes', $attributeFilters);
        $this->setSearchCriterion('producers', $producerFilters);

        Controller::reload();
    }

    public function setSearchCriterion(string $fieldName, $criterion): void
    {
        if (!$fieldName) {
            return;
        }

        // Track raw (un-augmented) value
        $this->rawSearchCriteria[$fieldName] = $criterion;

        if ($fieldName === 'fulltext' && is_string($criterion) && $criterion !== '') {
            $criterion = $this->augmentFulltextIfApplicable($criterion);
        }

        $this->searchCriteria[$fieldName] = $criterion;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->setSearchCriterion($fieldName, $criterion);
                break;

            case Mode::SearchServer:
                /*
                 * The SearchEngine receives a reference to this adapter when the search method is executed and can
                 * access search criteria directly from the adapter. Therefore, just break in this case.
                 */
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
    }

    public function setSearchCriteria(array $searchCriteria): void
    {
        if (!count($searchCriteria)) {
            $this->logger->warning('Search criteria array must not be empty');
        }

        // Persist raw (un-augmented) criteria
        $this->rawSearchCriteria = $searchCriteria;

        // Augment fulltext using search term mapping service if enabled
        if (isset($searchCriteria['fulltext']) && is_string($searchCriteria['fulltext']) && $searchCriteria['fulltext'] !== '') {
            $searchCriteria['fulltext'] = $this->augmentFulltextIfApplicable($searchCriteria['fulltext']);
        }

        $this->searchCriteria = $searchCriteria;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->setSearchCriteria($this->searchCriteria);
                break;

            case Mode::SearchServer:
                /*
                 * The SearchEngine receives a reference to this adapter when the search method is executed and can
                 * access search criteria directly from the adapter. Therefore, just break in this case.
                 */
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
    }

    public function setNumPerPage(int $num): void
    {
        $this->numPerPage = $num;
    }

    public function setCurrentPage(int $num): void
    {
        if ($num < 1) {
            $this->logger->warning('Setting current page to less than 1. This is most likely unintended. Please check!');
        }
        $this->currentPage = $num;
    }

    public function setSortingCriteria(array $sortingCriteria): void
    {
        if (!count($sortingCriteria)) {
            $this->logger->warning('Sorting criteria array must not be empty');
        }

        $this->sortingCriteria = $sortingCriteria;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->sorting = $this->sortingCriteria;
                break;

            case Mode::SearchServer:
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
    }

    /**
     * Cap the total number of results returned by the search engine.
     * Default (0) means no cap.
     */
    public function setMaxResults(int $num): void
    {
        $this->maxResults = max(0, $num);

        switch ($this->mode) {
            case Mode::Standard:
                // Map to legacy productSearcher limiting (SQL LIMIT)
                $this->standardSearchClient->limitRows = $this->maxResults;
                break;

            case Mode::SearchServer:
                // The SearchServer implementation reads getMaxResults() during execution.
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
    }

    public function getMaxResults(): int
    {
        return $this->maxResults;
    }

    public function setEmptyFieldMatchesPerDefault(bool $emptyFieldMatchesPerDefault): void
    {
        $this->emptyFieldMatchesPerDefault = $emptyFieldMatchesPerDefault;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->emptyFieldMatchesPerDefault = $this->emptyFieldMatchesPerDefault;
                break;

            case Mode::SearchServer:
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
    }

    public function setFixedSorting(array $fixedSorting): void
    {
        $this->fixedSorting = $fixedSorting;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->fixedSorting = $this->fixedSorting;
                break;

            case Mode::SearchServer:
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
    }

    public function setTruncateResultsIfMoreThan($num): void
    {
        $this->truncateResultsIfMoreThan = $num;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->truncateResultsIfMoreThan = $this->truncateResultsIfMoreThan;
                break;

            case Mode::SearchServer:
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
    }

    public function setCancelSearchIfMoreThanTruncateLimit(bool $cancel): void
    {
        $this->cancelSearchIfMoreThanTruncateLimit = $cancel;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->cancelSearchIfMoreThanTruncateLimit = $this->cancelSearchIfMoreThanTruncateLimit;
                break;

            case Mode::SearchServer:
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
    }

    public function search(): void
    {
        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->search();
                $this->searchResult->setResults($this->standardSearchClient->productResultsComplete);
                $this->searchResult->setHasUnmatchedProducts($this->standardSearchClient->blnNotAllProductsMatch);
                $this->searchResult->setNumUnmatchedProducts($this->standardSearchClient->numProductsNotMatching);
                $this->searchResult->setNumProductsUnfiltered($this->standardSearchClient->numProductsBeforeFilter);
                $this->searchResult->setNumProductsFiltered($this->searchResult->getNumProductsUnfiltered() - $this->searchResult->getNumUnmatchedProducts());
                break;

            case Mode::SearchServer:
                $this->searchResult = $this->searchServer->search($this, $this->helper->getSearchLanguage());
                $this->dispatchBeforeProductlistOutputBeforePaginationHook();
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
    }

    private function dispatchBeforeProductlistOutputBeforePaginationHook(): void
    {
        if (!$this->productListId || $this->searchResult->isFromCache()) {
            return;
        }

        if (!isset($GLOBALS['MERCONIS_HOOKS']['beforeProductlistOutputBeforePagination']) || !is_array($GLOBALS['MERCONIS_HOOKS']['beforeProductlistOutputBeforePagination'])) {
            return;
        }

        $productResultsComplete = $this->searchResult->getResults();

        foreach ($GLOBALS['MERCONIS_HOOKS']['beforeProductlistOutputBeforePagination'] as $mccb) {
            $objMccb = System::importStatic($mccb[0]);
            $productResultsComplete = $objMccb->{$mccb[1]}($this->productListId, $productResultsComplete);
        }

        $this->searchResult->setResults($productResultsComplete);
    }

    public function getProductResultsComplete(): array
    {
        return $this->searchResult->getResults();
    }

    public function getFacets(): ?Facets
    {
        return $this->searchResult->getFacets();
    }

    public function getNumResultsComplete(): int
    {
        return count($this->searchResult->getResults());
    }

    public function getNumPagesTotal(): int
    {
        return $this->numPerPage > 0 ? ceil ($this->getNumResultsComplete() / $this->numPerPage) : 1;
    }

    public function getProductResultsCurrentPage(): array
    {
        if ($this->numPerPage <= 0 || !$this->getNumResultsComplete()) {
            return $this->searchResult->getResults();
        }

        if ($this->currentPage < 1 || $this->currentPage > $this->getNumPagesTotal()) {
            /*
             * If requested page is out of range, return empty array.
             */
            return [];
        }

        $offset = ($this->currentPage - 1) * $this->numPerPage;
        $productResultsCurrentPage = array_slice($this->searchResult->getResults(), $offset, $this->numPerPage);
        return $productResultsCurrentPage;
    }

    public function getNumProductsUnfiltered(): int
    {
        return $this->searchResult->getNumProductsUnfiltered();
    }

    public function hasUnmatchedProducts(): bool
    {
        return $this->searchResult->hasUnmatchedProducts();
    }

    public function getNumUnmatchedProducts(): int
    {
        return $this->searchResult->getNumUnmatchedProducts();
    }

    public function getSearchCriteria(): array
    {
        return $this->searchCriteria;
    }

    public function getSortingCriteria(): array
    {
        return $this->sortingCriteria;
    }

    public function getFixedSorting(): array
    {
        return $this->fixedSorting;
    }

    public function getEmptyFieldMatchesPerDefault(): bool
    {
        return $this->emptyFieldMatchesPerDefault;
    }

    public function isUsingFilter(): bool
    {
        return (bool)$this->useFilter;
    }

    public function getFilterUI(): string
    {
        if (!$this->useFilter) {
            return '';
        }
        if ($this->getFacets() === null) {
            return '';
        }

        // Translator for localized conjunctions (e.g., "and")
        $andWord = $this->translator->trans('MSC.ls_shop.general.and', [], 'contao_default');

        $combinedFacets = $this->getFacets()->getCombinedFacets();
        $facetLookup = [];
        foreach ($combinedFacets as $facet) {
            if (isset($facet['attribute_id']) && isset($facet['value_id'])) {
                $facetLookup[$facet['attribute_id']][$facet['value_id']] = $facet;
            }
        }

        // Present prioritized and capped attributes/values (stateless)
        $presented = FacetPresenter::present(
            $this->getFacets(),
            $this->getSearchCriteria(),
            [
                'maxVisibleAttributes' => (int) ($GLOBALS['TL_CONFIG']['merconis_filter_maxVisibleAttributes'] ?? 12),
                'defaultMaxValuesPerAttribute' => (int) ($GLOBALS['TL_CONFIG']['merconis_filter_maxValuesPerAttribute'] ?? 10),
                'pinnedAliases' => (array) ($GLOBALS['TL_CONFIG']['merconis_filter_pinnedAliases'] ?? []),
                // language omitted to auto-detect from Page
            ]
        );

        // Determine whether match estimates should be shown (layout setting)
        $useMatchEstimates = isset($GLOBALS['merconis_globals']['ls_shop_useFilterMatchEstimates']) ? (bool)$GLOBALS['merconis_globals']['ls_shop_useFilterMatchEstimates'] : true;

        // User settings for checked/unchecked state
        $userFilterSettings = $this->searchCriteria['attributes'] ?? [];

        // Convert user filters to quick lookup for performance
        $userSelected = [];
        foreach ($userFilterSettings as $uf) {
            $userSelected[$uf['attribute_id']][$uf['value_id']] = true;
        }

        // Prepare final array for Twig
        $preparedFilters = [];
        foreach ($presented['visibleAttributes'] as $attr) {
            $attribute_id = (int) $attr['attribute_id'];
            $attributeTitle = (string) ($attr['title'] ?? ('Attribute ' . $attribute_id));
            $preparedFilters[$attribute_id] = [
                'title' => $attributeTitle,
                'values' => []
            ];
            $selectedTitles = [];
            foreach ($attr['values'] as $val) {
                $value_id = (int) $val['value_id'];
                $valueTitle = (string) ($val['title'] ?? ('Value ' . $value_id));
                $isChecked = in_array(['attribute_id' => $attribute_id, 'value_id' => $value_id], $userFilterSettings, true) || (!empty($userSelected[$attribute_id][$value_id]));
                $facet = $facetLookup[$attribute_id][$value_id] ?? null;
                $isFilteredOut = $facet['is_filtered_out'] ?? (($val['filtered_product_count'] ?? 0) === 0 && ($val['total_product_count'] ?? 0) > 0);
                $invalid = $facet['is_invalid'] ?? false;
                $liClass = ($isFilteredOut ? 'filter-value filter-value--out' : 'filter-value') . ($invalid ? ' invalid' : '');
                $checked = $isChecked ? 'checked' : '';
                $disabled = $isFilteredOut && !$isChecked ? 'disabled' : '';
                if ($isChecked) {
                    $selectedTitles[] = $valueTitle;
                }
                if ($useMatchEstimates) {
                    $filteredCount = $facet['filtered_product_count'] ?? ($val['filtered_product_count'] ?? 0);
                    $totalCount    = $facet['total_product_count'] ?? ($val['total_product_count'] ?? 0);
                    if ($filteredCount > 0) {
                        $activeStateClass = 'active';
                        $matchEstimateCount = $filteredCount;
                    } else {
                        $activeStateClass = 'inactive';
                        $matchEstimateCount = $totalCount;
                    }
                    $showCount = true;
                } else {
                    $activeStateClass = '';
                    $matchEstimateCount = null;
                    $showCount = false;
                }

                $encodedValue = json_encode(['attribute_id' => $attribute_id, 'value_id' => $value_id]);

                $preparedFilters[$attribute_id]['values'][$value_id] = [
                    'title'        => $valueTitle,
                    'liClass'     => $liClass,
                    'checked'      => $checked,
                    'disabled'     => $disabled,
                    'invalid'     => $invalid,
                    'activeStateClass'  => $activeStateClass,
                    'matchEstimateCount' => $matchEstimateCount,
                    'showCount'    => $showCount,
                    'encodedValue'=> $encodedValue,
                ];
            }

            // Create human-readable summary of selected values per attribute
            $summary = '';
            $countSelected = count($selectedTitles);
            if ($countSelected === 1) {
                $summary = $selectedTitles[0];
            } elseif ($countSelected === 2) {
                $summary = implode(' ' . $andWord . ' ', $selectedTitles);
            } elseif ($countSelected > 2) {
                $summary = implode(', ', array_slice($selectedTitles, 0, -1)) . ' ' . $andWord . ' ' . end($selectedTitles);
            }
            $preparedFilters[$attribute_id]['summary'] = $summary;
        }

        // Producers
        $preparedProducers = [];
        $selectedProducers = array_map('strval', $this->searchCriteria['producers'] ?? []);
        foreach (($presented['visibleProducers'] ?? []) as $prod) {
            $producerName = (string) ($prod['producer'] ?? '');
            $producerLabel = (string) ($prod['label'] ?? $producerName);
            if ($producerName === '') { continue; }
            $isChecked = in_array($producerName, $selectedProducers, true);
            // find counts in combined facets
            $facet = null;
            foreach ($combinedFacets as $entry) {
                if (isset($entry['producer']) && strtolower((string)$entry['producer']) === strtolower($producerName)) { $facet = $entry; break; }
            }
            if ($useMatchEstimates) {
                $filteredCount = $facet['filtered_product_count'] ?? ($prod['filtered_product_count'] ?? 0);
                $totalCount    = $facet['total_product_count'] ?? ($prod['total_product_count'] ?? 0);
                if ($filteredCount > 0) {
                    $activeStateClass = 'active';
                    $matchEstimateCount = $filteredCount;
                } else {
                    $activeStateClass = 'inactive';
                    $matchEstimateCount = $totalCount;
                }
                $showCount = true;
            } else {
                $activeStateClass = '';
                $matchEstimateCount = null;
                $showCount = false;
            }
            $encodedValue = json_encode(['producer' => $producerName]);
            $preparedProducers[] = [
                'title' => $producerLabel,
                'liClass' => 'filter-value',
                'checked' => $isChecked ? 'checked' : '',
                'disabled' => (!$isChecked && $useMatchEstimates && ($matchEstimateCount === 0)) ? 'disabled' : '',
                'invalid' => false,
                'activeStateClass' => $activeStateClass,
                'matchEstimateCount' => $matchEstimateCount,
                'showCount' => $showCount,
                'encodedValue' => $encodedValue,
            ];
        }

        return $this->twig->render(
            '@LeadingSystemsMerconis/frontend/product-search/filter/ui.html.twig',
            [
                'filters' => $preparedFilters,
                'producers' => $preparedProducers,
                'producerTitle' => $presented['producerTitle'] ?? 'Producer',
                'productListId' => $this->productListId
            ]
        );
    }

    private function notAllowedIn(Mode $mode): void
    {
        if ($this->mode === $mode) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'unknown';
            throw new \Exception($caller . ' is not allowed in ' . $this->mode->name . ' mode.');
        }
    }

    /**
     * Re-augment criteria from raw using current mapping mode; forward to Standard client when applicable.
     */
    private function reaugmentCriteriaForCurrentMapping(): void
    {
        if (!is_array($this->rawSearchCriteria) || !count($this->rawSearchCriteria)) {
            return;
        }
        $augmented = $this->rawSearchCriteria;
        if (isset($augmented['fulltext']) && is_string($augmented['fulltext']) && $augmented['fulltext'] !== '') {
            $augmented['fulltext'] = $this->augmentFulltextIfApplicable($augmented['fulltext']);
        }
        $this->searchCriteria = $augmented;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->setSearchCriteria($this->searchCriteria);
                break;
            case Mode::SearchServer:
                break;
            default:
                break;
        }
    }
}