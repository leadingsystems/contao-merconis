<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\Input;
use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_languageHelper;

class LoadDataContainerListener
{
    public function __invoke($str_dcaName): void
    {
        if (Input::get('do') != 'themes' || Input::get('key') != 'importTheme') {
            ls_shop_languageHelper::createMultiLanguageDCAFields($str_dcaName);
        }

        ls_shop_generalHelper::removeFieldsForEditAll($str_dcaName);
    }
}