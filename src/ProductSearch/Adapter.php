<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use LeadingSystems\MerconisBundle\ProductSearch\Enum\Mode;
use Merconis\Core\ls_shop_productSearcher;

class Adapter
{
    private Mode $mode;
    private bool $useFilter;
    private string $productListId;
    private ls_shop_productSearcher $searchClient;

    private int $numPerPage = 0;
    private int $currentPage = 1;
    private array $sorting = [['field' => 'title', 'direction' => 'ASC']];

    public function setMode(Mode $mode): void
    {
        $this->mode = $mode;
    }

    public function initialize(bool $useFilter, string $productListId): void
    {
        $this->useFilter = $useFilter;
        $this->productListId = $productListId;
        $this->searchClient = new ls_shop_productSearcher($this->useFilter, $this->productListId);
        $this->searchClient->setNonLegacyUsage();
    }

    public function setSearchCriterion(string $fieldName, $criterion): void
    {
        $this->notAllowedIn(Mode::SearchEngine);
        $this->searchClient->setSearchCriterion($fieldName, $criterion);
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
            trigger_error (
                'Sorting definition array must not be empty',
                E_USER_WARNING
            );
        }

        $this->sorting = $sortingDefinition;
        $this->searchClient->sorting = $this->sorting;
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