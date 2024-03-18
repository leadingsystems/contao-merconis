<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_generalHelper;

class ModifyFrontendPageListener
{
    public function __invoke($var_arg): string
    {
        $var_arg = ls_shop_generalHelper::storeConfiguratorDataToSession($var_arg);
        $var_arg = ls_shop_generalHelper::storeCustomizerDataToSession($var_arg);
        return $var_arg;
    }
}