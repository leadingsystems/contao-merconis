<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_ajaxController;

class ExecutePreActionsListener
{
    public function __invoke($strAction): void
    {
        (new ls_shop_ajaxController)->executePreActions($strAction);
    }
}