<?php

namespace LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;

interface CommonInterface
{
    public function getAdapterName(): string;
    public function initialize(): void;
}