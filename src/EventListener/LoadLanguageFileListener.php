<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\System;
use Merconis\Core\ls_shop_generalHelper;

class LoadLanguageFileListener
{
    public function execute($filename, $language): void
    {
        if(System::getContainer()->get('merconis.routing.scope')->isFrontend()) {
            ls_shop_generalHelper::ls_shop_loadThemeLanguageFiles($filename, $language);
        }
    }
}