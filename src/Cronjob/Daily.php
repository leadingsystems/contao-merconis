<?php

namespace LeadingSystems\MerconisBundle\Cronjob;

use Merconis\Core\ls_shop_generalHelper;

class Daily
{
    public function __invoke(): void
    {
        ls_shop_generalHelper::sendRestockInfo();
        ls_shop_generalHelper::sendMessagesOnStatusChangeCronDaily();
    }
}