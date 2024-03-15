<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_generalHelper;

class GetUserNavigationListener
{
    public function __invoke($arr_modules, $blnShowAll)
    {
        return (new ls_shop_generalHelper)->manipulateBackendNavigation($arr_modules, $blnShowAll);
    }
}