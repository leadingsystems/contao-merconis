<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

class Facets
{
    private array $unfilteredFacets;
    private array $filteredFacets;
    private array $combinedFacets;

    public function __construct(array $unfilteredFacets, array $filteredFacets, array $combinedFacets)
    {

        $this->unfilteredFacets = $unfilteredFacets;
        $this->filteredFacets = $filteredFacets;
        $this->combinedFacets = $combinedFacets;
    }

    public function getUnfilteredFacets(): array
    {
        return $this->unfilteredFacets;
    }

    public function getFilteredFacets(): array
    {
        return $this->filteredFacets;
    }

    public function getCombinedFacets(): array
    {
        return $this->combinedFacets;
    }


}