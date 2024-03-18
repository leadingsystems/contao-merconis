<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\System;
use Merconis\Core\ls_shop_generalHelper;

class LoadLanguageFileListener
{
    public function __invoke($str_filename, $str_language): void
    {
        if(System::getContainer()->get('merconis.routing.scope')->isFrontend()) {
            ls_shop_generalHelper::ls_shop_loadThemeLanguageFiles($str_filename, $str_language);
        }
    }
}