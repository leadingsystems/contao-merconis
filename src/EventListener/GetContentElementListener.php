<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_generalHelper;

class GetContentElementListener
{
    public function __invoke($objElement, $str_buffer): string
    {
        return ls_shop_generalHelper::conditionalCTEOutput($objElement, $str_buffer);
    }
}