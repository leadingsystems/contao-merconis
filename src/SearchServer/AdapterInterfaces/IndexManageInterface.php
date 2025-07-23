<?php

namespace LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;

interface IndexManageInterface
{
    public function initialize(): void;
    public function create(): OperationResult;
}