<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\System;
use Contao\Widget;
use Merconis\Core\ls_shop_checkoutData;
use Merconis\Core\ls_shop_generalHelper;

class LoadFormFieldListener
{
    public function __invoke(Widget $objWidget, $str_form, $arr_form): Widget
    {
        if(System::getContainer()->get('merconis.routing.scope')->isFrontend())
        {
            $objWidget =  ls_shop_checkoutData::getInstance()->ls_shop_loadFormField($objWidget, $str_form, $arr_form);
            $objWidget =  ls_shop_generalHelper::handleConditionalFormFields($objWidget, $str_form, $arr_form);
        }

        return $objWidget;
    }

}