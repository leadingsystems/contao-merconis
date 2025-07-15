<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use LeadingSystems\MerconisBundle\ProductSearch\Enum\Mode;
use Merconis\Core\ls_shop_productSearcher;
use Psr\Log\LoggerInterface;

class Adapter
{
    private LoggerInterface $logger;
    private Mode $mode;
    private bool $useFilter;
    private ?string $productListId;
    private ls_shop_productSearcher $searchClient;

    private array $searchCriteria =  ['title' => '*', 'published' => '1'];
    private int $numPerPage = 0;
    private int $currentPage = 1;
    private array $sorting = [['field' => 'title', 'direction' => 'ASC']];
    private array $fixedSorting = [];
    private int $truncateResultsIfMoreThan = 0;
    private bool $cancelSearchIfMoreThanTruncateLimit = false;
    private bool $emptyFieldMatchesPerDefault = false;

    public function __construct(LoggerInterface $logger)
    {
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
        $this->searchClient = new ls_shop_productSearcher($this->useFilter, $this->productListId);
        $this->searchClient->setNonLegacyUsage();
    }

    public function setSearchCriterion(string $fieldName, $criterion): void
    {
        $this->notAllowedIn(Mode::SearchEngine);

        if (!$fieldName) {
            return;
        }

        $this->searchCriteria[$fieldName] = $criterion;

        $this->searchClient->setSearchCriterion($fieldName, $criterion);
    }

    public function setSearchCriteria(array $searchCriteria): void
    {
        $this->notAllowedIn(Mode::SearchEngine);

        if (!count($searchCriteria)) {
            $this->logger->warning('Search criteria array must not be empty');
        }

        $this->searchCriteria = $searchCriteria;

        $this->searchClient->setSearchCriteria($this->searchCriteria);
    }

    public function setNumPerPage(int $num): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->numPerPage = $num;
        $this->searchClient->numPerPage = $this->numPerPage;
    }

    public function setCurrentPage(int $num): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->currentPage = $num;
        $this->searchClient->currentPage = $this->currentPage;
    }

    public function setSorting(array $sortingDefinition): void
    {
        $this->notAllowedIn(Mode::SearchEngine);

        if (!count($sortingDefinition)) {
            $this->logger->warning('Sorting definition array must not be empty');
        }

        $this->sorting = $sortingDefinition;
        $this->searchClient->sorting = $this->sorting;
    }

    public function setEmptyFieldMatchesPerDefault(bool $emptyFieldMatchesPerDefault): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->emptyFieldMatchesPerDefault = $emptyFieldMatchesPerDefault;
        $this->searchClient->emptyFieldMatchesPerDefault = $this->emptyFieldMatchesPerDefault;
    }

    public function setFixedSorting(array $fixedSorting): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->fixedSorting = $fixedSorting;
        $this->searchClient->fixedSorting = $this->fixedSorting;
    }

    public function setTruncateResultsIfMoreThan($num): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->truncateResultsIfMoreThan = $num;
        $this->searchClient->truncateResultsIfMoreThan = $this->truncateResultsIfMoreThan;
    }

    public function setCancelSearchIfMoreThanTruncateLimit(bool $cancel): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->cancelSearchIfMoreThanTruncateLimit = $cancel;
        $this->searchClient->cancelSearchIfMoreThanTruncateLimit = $this->cancelSearchIfMoreThanTruncateLimit;
    }

    public function search(): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->searchClient->search();
    }

    public function getNumPagesTotal(): int
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->searchClient->numPagesTotal;
    }

    public function getProductResultsCurrentPage(): array
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->searchClient->productResultsCurrentPage;
    }

    public function getProductResultsComplete(): array
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->searchClient->productResultsComplete;
    }

    public function getNumProductsBeforeFilter(): int
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->searchClient->numProductsBeforeFilter;
    }

    public function hasMismatchedProducts(): bool
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->searchClient->blnNotAllProductsMatch;
    }

    public function getNumProductsNotMatching(): int
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->searchClient->numProductsNotMatching;
    }

    public function getNumResultsComplete(): int
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->searchClient->numResultsComplete;
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