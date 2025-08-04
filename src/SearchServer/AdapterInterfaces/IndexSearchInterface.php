<?php

namespace LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces;

use LeadingSystems\MerconisBundle\ProductSearch\Adapter;

interface IndexSearchInterface
{
    public function initialize(): void;
    public function search(Adapter &$productSearchAdapter): array;
    public function getFacetData(): array;
}