<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\Widget;
use Merconis\Core\ls_shop_custom_regexp;
use Merconis\Core\ls_shop_custom_regexp_fe;

class AddCustomRegexpListener
{
    public function __invoke($str_regexp, &$var_value, Widget $objWidget): bool
    {
        if(!(new ls_shop_custom_regexp)->customRegexp($str_regexp, $var_value, $objWidget)){
            return (new ls_shop_custom_regexp_fe)->customRegexp($str_regexp, $var_value, $objWidget);
        }
        return true;
    }
}