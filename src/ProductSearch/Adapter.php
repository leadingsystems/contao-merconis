<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use LeadingSystems\MerconisBundle\ProductSearch\Enum\Mode;
use LeadingSystems\MerconisBundle\SearchEngine\SearchEngine;
use Merconis\Core\ls_shop_productSearcher;
use Psr\Log\LoggerInterface;

class Adapter
{
    private LoggerInterface $logger;
    private SearchEngine $searchEngine;
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

    public function __construct(SearchEngine $searchEngine, LoggerInterface $logger)
    {
        $this->searchEngine = $searchEngine;
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
                break;

            case Mode::SearchEngine:
                /*
                 * Do me! Since we're receiving the searchEngine service through DI, we don't have to instantiate
                 *  it here. We can simply use it when we need to. So there's probably nothin to do in this switch case.
                 *  If so, decide whether to keep this case anyway and place a comment here to make this more clear.
                 */
                throw new \Exception('Mode "' . $this->mode->name . '" not implemented yet.');
                break;

            default:
                throw new \Exception('Unexpected mode "' . $this->mode->name . '" not implemented yet.');
                break;
        }

        $this->standardSearchClient->setNonLegacyUsage();
    }

    public function setSearchCriterion(string $fieldName, $criterion): void
    {
        $this->notAllowedIn(Mode::SearchEngine);

        if (!$fieldName) {
            return;
        }

        $this->searchCriteria[$fieldName] = $criterion;

        $this->standardSearchClient->setSearchCriterion($fieldName, $criterion);
    }

    public function setSearchCriteria(array $searchCriteria): void
    {
        $this->notAllowedIn(Mode::SearchEngine);

        if (!count($searchCriteria)) {
            $this->logger->warning('Search criteria array must not be empty');
        }

        $this->searchCriteria = $searchCriteria;

        $this->standardSearchClient->setSearchCriteria($this->searchCriteria);
    }

    public function setNumPerPage(int $num): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->numPerPage = $num;
        $this->standardSearchClient->numPerPage = $this->numPerPage;
    }

    public function setCurrentPage(int $num): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->currentPage = $num;
        $this->standardSearchClient->currentPage = $this->currentPage;
    }

    public function setSorting(array $sortingDefinition): void
    {
        $this->notAllowedIn(Mode::SearchEngine);

        if (!count($sortingDefinition)) {
            $this->logger->warning('Sorting definition array must not be empty');
        }

        $this->sorting = $sortingDefinition;
        $this->standardSearchClient->sorting = $this->sorting;
    }

    public function setEmptyFieldMatchesPerDefault(bool $emptyFieldMatchesPerDefault): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->emptyFieldMatchesPerDefault = $emptyFieldMatchesPerDefault;
        $this->standardSearchClient->emptyFieldMatchesPerDefault = $this->emptyFieldMatchesPerDefault;
    }

    public function setFixedSorting(array $fixedSorting): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->fixedSorting = $fixedSorting;
        $this->standardSearchClient->fixedSorting = $this->fixedSorting;
    }

    public function setTruncateResultsIfMoreThan($num): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->truncateResultsIfMoreThan = $num;
        $this->standardSearchClient->truncateResultsIfMoreThan = $this->truncateResultsIfMoreThan;
    }

    public function setCancelSearchIfMoreThanTruncateLimit(bool $cancel): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->cancelSearchIfMoreThanTruncateLimit = $cancel;
        $this->standardSearchClient->cancelSearchIfMoreThanTruncateLimit = $this->cancelSearchIfMoreThanTruncateLimit;
    }

    public function search(): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->standardSearchClient->search();
    }

    public function getNumPagesTotal(): int
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->standardSearchClient->numPagesTotal;
    }

    public function getProductResultsCurrentPage(): array
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->standardSearchClient->productResultsCurrentPage;
    }

    public function getProductResultsComplete(): array
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->standardSearchClient->productResultsComplete;
    }

    public function getNumProductsBeforeFilter(): int
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->standardSearchClient->numProductsBeforeFilter;
    }

    public function hasMismatchedProducts(): bool
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->standardSearchClient->blnNotAllProductsMatch;
    }

    public function getNumProductsNotMatching(): int
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->standardSearchClient->numProductsNotMatching;
    }

    public function getNumResultsComplete(): int
    {
        $this->notAllowedIn(Mode::SearchEngine);
        return $this->standardSearchClient->numResultsComplete;
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