<?php

namespace LeadingSystems\MerconisBundle\Cronjob;

use Merconis\Core\ls_shop_generalHelper;

class Hourly
{
    public function __invoke(): void
    {
        ls_shop_generalHelper::sendMessagesOnStatusChangeCronHourly();
    }
}