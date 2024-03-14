<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\DataContainer;
use Merconis\Core\ls_shop_ajaxController;

class ExecutePostActionsListener
{
    public function __invoke($strAction, DataContainer $dc): void
    {
        (new ls_shop_ajaxController)->executePostActions($strAction, $dc);
    }
}