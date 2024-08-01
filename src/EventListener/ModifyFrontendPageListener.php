<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_generalHelper;

class ModifyFrontendPageListener
{
    public function __invoke(string $str_content, string $str_template): string
    {
        $str_content = ls_shop_generalHelper::callback_modifyFrontendPage($str_content, $str_template);
        $str_content = ls_shop_generalHelper::storeConfiguratorDataToSession($str_content);
        $str_content = ls_shop_generalHelper::storeCustomizerDataToSession($str_content);

        return $str_content;
    }
}