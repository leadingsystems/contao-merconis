<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_filterController;
use Merconis\Core\ls_shop_generalHelper;

class OutputFrontendTemplateListener
{
    public function __invoke($str_content, $str_template)
    {
        $str_content = ls_shop_filterController::getInstance()->generateAndInsertFilterForms($str_content, $str_template);
        $str_content = ls_shop_generalHelper::callback_outputFrontendTemplate($str_content, $str_template);

        return $str_content;
    }
}