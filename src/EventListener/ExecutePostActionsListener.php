<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\DataContainer;
use Merconis\Core\ls_shop_ajaxController;

class ExecutePostActionsListener
{
    public function __invoke($str_action, DataContainer $dc): void
    {
        (new ls_shop_ajaxController)->executePostActions($str_action, $dc);
    }
}