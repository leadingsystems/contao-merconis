<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_generalHelper;

class GetContentElementListener
{
    public function execute($objElement, $strBuffer): void
    {
        ls_shop_generalHelper::conditionalCTEOutput($objElement, $strBuffer);
    }
}