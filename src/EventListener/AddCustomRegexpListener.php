<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\Widget;
use Merconis\Core\ls_shop_custom_regexp;
use Merconis\Core\ls_shop_custom_regexp_fe;

class AddCustomRegexpListener
{
    public function execute($strRegexp, &$varValue, Widget $objWidget)
    {
        if(!(new ls_shop_custom_regexp)->customRegexp($strRegexp, $varValue,  $objWidget)){
            return (new ls_shop_custom_regexp_fe)->customRegexp($strRegexp, $varValue,  $objWidget);
        }
    }
}