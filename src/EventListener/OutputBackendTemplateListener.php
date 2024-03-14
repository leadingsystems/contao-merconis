<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\System;
use Merconis\Core\ls_shop_generalHelper;

class OutputBackendTemplateListener
{
    public function execute($str_content, $str_template): string|array
    {
           return ls_shop_generalHelper::merconis_getBackendLsjs($str_content, $str_template);
    }
}