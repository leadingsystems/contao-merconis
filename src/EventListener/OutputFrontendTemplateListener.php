<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\System;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterFormRenderer;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterRuntime;
use Merconis\Core\ls_shop_filterController;

class OutputFrontendTemplateListener
{
    public function __invoke($str_content, $str_template)
    {
        if (FastFilterRuntime::isActive()) {
            $str_content = System::getContainer()
                ->get(FastFilterFormRenderer::class)
                ->generateAndInsertFilterForms($str_content, $str_template);
        } else {
            $str_content = ls_shop_filterController::getInstance()->generateAndInsertFilterForms($str_content, $str_template);
        }

        return $str_content;
    }
}