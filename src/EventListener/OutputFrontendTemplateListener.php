<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_filterController;
use Merconis\Core\ls_shop_generalHelper;

class OutputFrontendTemplateListener
{
    public function __invoke($strContent, $strTemplate)
    {
        $strContent = ls_shop_filterController::getInstance()->generateAndInsertFilterForms($strContent, $strTemplate);
        $strContent = ls_shop_generalHelper::callback_outputFrontendTemplate($strContent, $strTemplate);

        return $strContent;
    }
}