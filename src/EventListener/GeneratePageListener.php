<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\System;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterIndexMaintenance;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterRuntime;
use Merconis\Core\ls_shop_generalHelper;

class GeneratePageListener
{
    public function __construct(
        private readonly FastFilterIndexMaintenance $fastFilterIndexMaintenance,
    ) {
    }

    public function __invoke(): void
    {
        if(System::getContainer()->get('merconis.routing.scope')->isFrontend())
        {
            ls_shop_generalHelper::ls_shop_provideInfosForJS();

            if (FastFilterRuntime::isActive()) {
                $this->fastFilterIndexMaintenance->ensureFreshIndex();
            }
        }
    }
}