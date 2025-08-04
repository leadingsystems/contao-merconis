<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

class SearchResult
{
    private array $results = [];
    private ?Facets $facets = null;

    public function __construct(array $results, ?Facets $facets = null)
    {

        $this->results = $results;
        $this->facets = $facets;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getFacets(): Facets
    {
        return $this->facets;
    }

    public function setFacets(Facets $facets): void
    {
        $this->facets = $facets;
    }
}