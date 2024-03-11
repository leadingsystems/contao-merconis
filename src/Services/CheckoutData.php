<?php

namespace LeadingSystems\MerconisBundle\Services;

namespace LeadingSystems\MerconisBundle\Services;

use Contao\Widget;
use Merconis\Core\ls_shop_checkoutData;

class CheckoutData
{
    public function ls_shop_processFormData($arrPost, $arrForm, $arrFiles){
        ls_shop_checkoutData::getInstance()->ls_shop_processFormData($arrPost, $arrForm, $arrFiles);
    }

    public function ls_shop_loadFormField(Widget $objWidget, $strForm, $arrForm){
        return ls_shop_checkoutData::getInstance()->ls_shop_loadFormField($objWidget, $strForm, $arrForm);
    }
}