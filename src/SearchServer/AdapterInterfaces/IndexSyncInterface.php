<?php

namespace LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces;

use LeadingSystems\MerconisBundle\Common\DTO\OperationResult;

interface IndexSyncInterface
{
    public function initialize(): void;
    public function sync(): OperationResult;
}