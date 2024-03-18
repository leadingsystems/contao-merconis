<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_ajaxController;

class ExecutePreActionsListener
{
    public function __invoke($str_action): void
    {
        (new ls_shop_ajaxController)->executePreActions($str_action);
    }
}