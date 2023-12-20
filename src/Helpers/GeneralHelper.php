<?php

namespace LeadingSystems\MerconisBundle\Helpers;

use Contao\Config;
use Contao\Environment;

class GeneralHelper
{
    private IpChecker $ipChecker;

    public function __construct(IpChecker $ipChecker)
    {

        $this->ipChecker = $ipChecker;
    }

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

        foreach ($arr_allowedIpAddresses as $allowedIpAddressOrRange) {
            list($allowedIpStart, $allowedIpEnd) = $this->ipChecker->parseIpRange($allowedIpAddressOrRange);
            if ($this->ipChecker->isIpInRange($_SERVER['REMOTE_ADDR'] ?? '', $allowedIpStart, $allowedIpEnd)) {
                return true;
            }
        }

        if ($str_urlWhitelist && strlen($str_urlWhitelist) > 2) {
            if (preg_match($str_urlWhitelist, Environment::get('request'))) {
                return true;
            }
        }

        return false;
    }
}