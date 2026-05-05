<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

class SearchResult
{
    private array $results = [];
    private ?Facets $facets = null;
    private bool $hasUnmatchedProducts = false;
    private int $numUnmatchedProducts = 0;
    private int $numProductsUnfiltered;
    private int $numProductsFiltered;
    private bool $isFromCache;

    public function __construct(array $results, ?Facets $facets = null, bool $hasUnmatchedProducts = false, int $numUnmatchedProducts = 0, int $numProductsUnfiltered = 0, int $numProductsFiltered = 0, bool $isFromCache = false)
    {
        $this->results = $results;
        $this->facets = $facets;
        $this->hasUnmatchedProducts = $hasUnmatchedProducts;
        $this->numUnmatchedProducts = $numUnmatchedProducts;
        $this->numProductsUnfiltered = $numProductsUnfiltered;
        $this->numProductsFiltered = $numProductsFiltered;
        $this->isFromCache = $isFromCache;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getFacets(): ?Facets
    {
        return $this->facets;
    }

    public function setFacets(Facets $facets): void
    {
        $this->facets = $facets;
    }

    public function hasUnmatchedProducts(): bool
    {
        return $this->hasUnmatchedProducts;
    }

    public function setHasUnmatchedProducts(bool $hasUnmatchedProducts): void
    {
        $this->hasUnmatchedProducts = $hasUnmatchedProducts;
    }

    public function getNumUnmatchedProducts(): int
    {
        return $this->numUnmatchedProducts;
    }

    public function setNumUnmatchedProducts(int $numUnmatchedProducts): void
    {
        $this->numUnmatchedProducts = $numUnmatchedProducts;
    }

    public function getNumProductsUnfiltered(): int
    {
        return $this->numProductsUnfiltered;
    }

    public function setNumProductsUnfiltered(int $numProductsUnfiltered): void
    {
        $this->numProductsUnfiltered = $numProductsUnfiltered;
    }

    public function getNumProductsFiltered(): int
    {
        return $this->numProductsFiltered;
    }

    public function setNumProductsFiltered(int $numProductsFiltered): void
    {
        $this->numProductsFiltered = $numProductsFiltered;
    }

    public function isFromCache(): bool
    {
        return $this->isFromCache;
    }

    public function setIsFromCache(bool $isFromCache): void
    {
        $this->isFromCache = $isFromCache;
    }

}