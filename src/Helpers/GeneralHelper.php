<?php

namespace LeadingSystems\MerconisBundle\Helpers;

use Contao\Config;
use Contao\Environment;

class GeneralHelper
{
    public function check_refererCheckCanBeBypassed()
    {
        $str_ipWhitelist = Config::get('ls_shop_ipWhitelist');
        $str_urlWhitelist = Config::get('ls_shop_urlWhitelist');

        if (
            empty($str_ipWhitelist)
            && empty($str_urlWhitelist)
        ) {
            return false;
        }

        $arr_allowedIpAddresses = array_map('trim', explode(',', $str_ipWhitelist));

        if ((isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], $arr_allowedIpAddresses))) {
            return true;
        } else if ($str_urlWhitelist && strlen($str_urlWhitelist) > 2) {
            if (preg_match($str_urlWhitelist, Environment::get('request'))) {
                return true;
            }
        }

        return false;
    }
}