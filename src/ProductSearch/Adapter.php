<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use Contao\Controller;
use LeadingSystems\MerconisBundle\Common\Session\ObjectStatePersistor\ObjectStatePersistorTrait;
use LeadingSystems\MerconisBundle\ProductSearch\Enum\Mode;
use LeadingSystems\MerconisBundle\SearchServer\SearchServer;
use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_productSearcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use LeadingSystems\MerconisBundle\ProductSearch\SearchTermMappingService;

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
    private int $numPerPage = 0;
    private int $currentPage = 1;
    private array $sortingCriteria = [['field' => 'title', 'direction' => 'ASC']];
    private array $fixedSorting = [];
    private int $truncateResultsIfMoreThan = 0;
    private bool $cancelSearchIfMoreThanTruncateLimit = false;
    private bool $emptyFieldMatchesPerDefault = false;

    private SearchResult $searchResult;
    private Environment $twig;
    private RequestStack $requestStack;
    private Helper $helper;
    private TranslatorInterface $translator;
    private SearchTermMappingService $termMappingService;

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

    public function initialize(bool $useFilter = false, ?string $productListId = null): void
    {
        $this->useFilter = $useFilter;
        $this->productListId = $productListId;

        if ($this->mode === null) {
            $this->setDefaultMode();
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

        $filterAsSearchCriterion = array_map(
            function($item) {
                return json_decode($item, true);
            },
            $filter ?? []
        );

        $this->setSearchCriterion('attributes', $filterAsSearchCriterion);

        Controller::reload();
    }

    public function setSearchCriterion(string $fieldName, $criterion): void
    {
        if (!$fieldName) {
            return;
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

        // Augment fulltext using search term mapping service if enabled
        if (isset($searchCriteria['fulltext']) && is_string($searchCriteria['fulltext']) && $searchCriteria['fulltext'] !== '') {
            try {
                $mode = $this->mode ?? null;
                $applyAugmentation = $this->termMappingService->isEnabled() && (
                    $mode === null || $mode === Mode::Standard || ($mode === Mode::SearchServer && $this->termMappingService->isApplyInElasticsearch())
                );
                if ($applyAugmentation) {
                    $searchCriteria['fulltext'] = $this->termMappingService->augment($searchCriteria['fulltext']);
                }
            } catch (\Throwable $e) {
                $this->logger->error('Search term augmentation failed: ' . $e->getMessage());
            }
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
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
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
        $filters = [];
        foreach ($combinedFacets as $facet) {
            $filters[$facet['attribute_id']][$facet['value_id']] = $facet;
        }
        ksort($filters);
        foreach ($filters as &$values) {
            ksort($values);
        }
        unset($values);

        $attributes = ls_shop_generalHelper::getProductAttributes();
        $attributeNames = array_column($attributes, 'title', 'id');
        $values = ls_shop_generalHelper::getAttributeValues();
        $valueNames = array_column($values, 'title', 'id');

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
        foreach ($filters as $attribute_id => $values) {
            $attributeTitle = $attributeNames[$attribute_id] ?? ('Attribute ' . $attribute_id);
            $preparedFilters[$attribute_id] = [
                'title' => $attributeTitle,
                'values' => []
            ];
            $selectedTitles = [];
            foreach ($values as $value_id => $facet) {
                $valueTitle = $valueNames[$value_id] ?? ('Value ' . $value_id);
                $isChecked = !empty($userSelected[$attribute_id][$value_id]);
                $isFilteredOut = $facet['is_filtered_out'] ?? false;
                $invalid = $facet['is_invalid'] ?? false;
                $liClass = ($isFilteredOut ? 'filter-value filter-value--out' : 'filter-value') . ($invalid ? ' invalid' : '');
                $checked = $isChecked ? 'checked' : '';
                $disabled = $isFilteredOut && !$isChecked ? 'disabled' : '';
                if ($isChecked) {
                    $selectedTitles[] = $valueTitle;
                }
                if ($useMatchEstimates) {
                    $filteredCount = $facet['filtered_product_count'] ?? 0;
                    $totalCount    = $facet['total_product_count'] ?? 0;
                    if ($filteredCount > 0) {
                        $activeStateClass = 'active';
                        $matchEstimateCount = $filteredCount;
                    } else {
                        $activeStateClass = 'inactive';
                        $matchEstimateCount = $totalCount;
                    }
                    $showCount = true;
                } else {
                    // When match estimates are disabled, do not compute or show counts
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

        return $this->twig->render(
            '@LeadingSystemsMerconis/frontend/product-search/filter/ui.html.twig',
            [
                'filters' => $preparedFilters,
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
}