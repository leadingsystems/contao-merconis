<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\System;
use Merconis\Core\ls_shop_generalHelper;

class OutputFrontendPageListener
{
    public function execute(): void
    {
        if(System::getContainer()->get('merconis.routing.scope')->isFrontend())
        {
            ls_shop_generalHelper::ls_shop_provideInfosForJS();
        }
    }
}