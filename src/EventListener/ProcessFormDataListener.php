<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\System;
use Merconis\Core\ls_shop_checkoutData;

class ProcessFormDataListener
{
    public function __invoke($arrPost, $arrForm, $arrFiles): void
    {
        if(System::getContainer()->get('merconis.routing.scope')->isFrontend())
        {
            ls_shop_checkoutData::getInstance()->ls_shop_processFormData($arrPost, $arrForm, $arrFiles);
        }
    }

}