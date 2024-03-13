<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_ajaxController;

class ExecutePreActionsListener
{
    public function execute($strAction): void
    {
        ls_shop_ajaxController::executePreActions($strAction);
    }
}