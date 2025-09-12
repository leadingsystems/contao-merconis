<?php

namespace LeadingSystems\MerconisBundle\Cronjob;

use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\Helpers\RestockInfoMessenger;

class Hourly
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(): void
    {
        $restockInfoMessenger = new RestockInfoMessenger($this->connection);

        $restockInfoMessenger->sendMessagesOnStatusChangeCronHourly();
    }
}