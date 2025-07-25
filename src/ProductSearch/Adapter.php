<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use LeadingSystems\MerconisBundle\ProductSearch\Enum\Mode;
use LeadingSystems\MerconisBundle\SearchServer\SearchServer;
use Merconis\Core\ls_shop_productSearcher;
use Psr\Log\LoggerInterface;

class Adapter
{
    private LoggerInterface $logger;
    private SearchServer $searchServer;
    private ls_shop_productSearcher $standardSearchClient;
    private Mode $mode;
    private bool $useFilter;
    private ?string $productListId;

    private array $searchCriteria =  ['title' => '*', 'published' => '1'];
    private int $numPerPage = 0;
    private int $currentPage = 1;
    private array $sorting = [['field' => 'title', 'direction' => 'ASC']];
    private array $fixedSorting = [];
    private int $truncateResultsIfMoreThan = 0;
    private bool $cancelSearchIfMoreThanTruncateLimit = false;
    private bool $emptyFieldMatchesPerDefault = false;

    private array $productResultsComplete = [];

    public function __construct(SearchServer $searchServer, LoggerInterface $logger)
    {
        $this->searchServer = $searchServer;
        $this->logger = $logger;
    }

    public function setMode(Mode $mode): void
    {
        $this->mode = $mode;
    }

    public function initialize(bool $useFilter = false, ?string $productListId = null): void
    {
        $this->useFilter = $useFilter;
        $this->productListId = $productListId;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient = new ls_shop_productSearcher($this->useFilter, $this->productListId);
                $this->standardSearchClient->setNonLegacyUsage();
                break;

            case Mode::SearchEngine:
                /*
                 * The searchEngine service is received through DI and does not need to be instantiated.
                 * Therefore, just break.
                 */
                break;

            default:
                throw new \Exception('Unexpected mode "' . $this->mode->name . '" not implemented yet.');
                break;
        }
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

            case Mode::SearchEngine:
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

        $this->searchCriteria = $searchCriteria;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->setSearchCriteria($this->searchCriteria);
                break;

            case Mode::SearchEngine:
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

    public function setSorting(array $sortingDefinition): void
    {
        if (!count($sortingDefinition)) {
            $this->logger->warning('Sorting definition array must not be empty');
        }

        $this->sorting = $sortingDefinition;

        switch ($this->mode) {
            case Mode::Standard:
                $this->standardSearchClient->sorting = $this->sorting;
                break;

            case Mode::SearchEngine:
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

            case Mode::SearchEngine:
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

            case Mode::SearchEngine:
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

            case Mode::SearchEngine:
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

            case Mode::SearchEngine:
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
                $this->productResultsComplete = $this->standardSearchClient->productResultsComplete;
                break;

            case Mode::SearchEngine:
                $this->productResultsComplete = $this->searchServer->search($this);
                break;

            default:
                $this->notAllowedIn($this->mode);
                break;
        }
    }

    public function getProductResultsComplete(): array
    {
        return $this->productResultsComplete;
    }

    public function getNumResultsComplete(): int
    {
        return count($this->productResultsComplete);
    }

    public function getNumPagesTotal(): int
    {
        return $this->numPerPage > 0 ? ceil ($this->getNumResultsComplete() / $this->numPerPage) : 1;
    }

    public function getProductResultsCurrentPage(): array
    {
        if ($this->numPerPage <= 0 || !$this->getNumResultsComplete()) {
            return $this->productResultsComplete;
        }

        if ($this->currentPage < 1 || $this->currentPage > $this->getNumPagesTotal()) {
            /*
             * If requested page is out of range, return empty array.
             */
            return [];
        }

        $offset = ($this->currentPage - 1) * $this->numPerPage;
        $productResultsCurrentPage = array_slice($this->productResultsComplete, $offset, $this->numPerPage);
        return $productResultsCurrentPage;
    }

    public function getNumProductsBeforeFilter(): int
    {
        switch ($this->mode) {
            case Mode::Standard:
                return $this->standardSearchClient->numProductsBeforeFilter;
                break;

            case Mode::SearchEngine:
                return $this->getNumResultsComplete();
                break;

            default:
                $this->notAllowedIn($this->mode);
                return 0;
                break;
        }
    }

    public function hasUnmatchedProducts(): bool
    {
        switch ($this->mode) {
            case Mode::Standard:
                return $this->standardSearchClient->blnNotAllProductsMatch;
                break;

            case Mode::SearchEngine:
                /*
                 * Implement real functionality here instead of placeholder
                 */
                return false;
                break;

            default:
                $this->notAllowedIn($this->mode);
                return false;
                break;
        }
    }

    public function getNumProductsNotMatching(): int
    {
        switch ($this->mode) {
            case Mode::Standard:
                return $this->standardSearchClient->numProductsNotMatching;
                break;

            case Mode::SearchEngine:
                /*
                 * Implement real functionality here instead of placeholder
                 */
                return 0;
                break;

            default:
                $this->notAllowedIn($this->mode);
                return 0;
                break;
        }
    }

    public function getSearchCriteria(): array
    {
        return $this->searchCriteria;
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