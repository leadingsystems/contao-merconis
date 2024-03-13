<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_generalHelper;

class GetSystemMessagesListener
{
    public function execute()
    {
        return ls_shop_generalHelper::getMerconisSystemMessages();
    }
}