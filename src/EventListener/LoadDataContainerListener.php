<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\Input;
use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_languageHelper;

class LoadDataContainerListener
{
    public function __invoke($strDCAName): void
    {
        if (Input::get('do') != 'themes' || Input::get('key') != 'importTheme') {
            ls_shop_languageHelper::createMultiLanguageDCAFields($strDCAName);
        }

        ls_shop_generalHelper::removeFieldsForEditAll($strDCAName);
    }
}