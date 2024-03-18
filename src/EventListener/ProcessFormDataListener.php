<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\System;
use Merconis\Core\ls_shop_checkoutData;

class ProcessFormDataListener
{
    public function __invoke($arr_post, $arr_form, $arr_files): void
    {
        if(System::getContainer()->get('merconis.routing.scope')->isFrontend())
        {
            ls_shop_checkoutData::getInstance()->ls_shop_processFormData($arr_post, $arr_form, $arr_files);
        }
    }

}