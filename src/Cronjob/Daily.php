<?php

namespace LeadingSystems\MerconisBundle\Cronjob;

use LeadingSystems\MerconisBundle\Helpers\RestockInfoMessenger;
use Doctrine\DBAL\Connection;

class Daily
{
    private Connection $connection;


    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(): void
    {
        $restockInfoMessenger = new RestockInfoMessenger($this->connection);

        $restockInfoMessenger->sendRestockInfo();
        $restockInfoMessenger->sendMessagesOnStatusChangeCronDaily();
    }
}