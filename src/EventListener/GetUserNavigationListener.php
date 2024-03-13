<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_generalHelper;

class GetUserNavigationListener
{
    public function execute($arr_modules, $blnShowAll)
    {
        return ls_shop_generalHelper::manipulateBackendNavigation($arr_modules, $blnShowAll);
    }
}