<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_generalHelper;

class GetUserNavigationListener
{
    public function __invoke($arr_modules, $bln_showAll): array
    {
        return (new ls_shop_generalHelper)->manipulateBackendNavigation($arr_modules, $bln_showAll);
    }
}