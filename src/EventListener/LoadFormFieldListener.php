<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\System;
use Contao\Widget;
use Merconis\Core\ls_shop_checkoutData;
use Merconis\Core\ls_shop_generalHelper;

class LoadFormFieldListener
{
    public function execute(Widget $objWidget, $strForm, $arrForm)
    {
        if(System::getContainer()->get('merconis.routing.scope')->isFrontend())
        {
            $objWidget =  ls_shop_checkoutData::getInstance()->ls_shop_loadFormField($objWidget, $strForm, $arrForm);
            $objWidget =  ls_shop_generalHelper::handleConditionalFormFields($objWidget, $strForm, $arrForm);
        }

        return $objWidget;
    }

}