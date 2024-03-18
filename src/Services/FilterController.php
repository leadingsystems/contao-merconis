<?php

namespace LeadingSystems\MerconisBundle\Services;

namespace LeadingSystems\MerconisBundle\Services;

use Merconis\Core\ls_shop_filterController;

class FilterController
{
    public function generateAndInsertFilterForms($strContent, $strTemplate){

        return ls_shop_filterController::getInstance()->generateAndInsertFilterForms($strContent, $strTemplate);
    }
}