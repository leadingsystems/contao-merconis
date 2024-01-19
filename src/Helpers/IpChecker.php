<?php

namespace LeadingSystems\MerconisBundle\Helpers;

class IpChecker
{
    public function parseIpRange(string $inputIpOrRange): array
    {
        if (strpos($inputIpOrRange, '-') !== false) {
            list($start, $end) = array_map('trim', explode('-', $inputIpOrRange, 2));
        } elseif (strpos($inputIpOrRange, '/') !== false) {
            list($ip, $subnet) = explode('/', $inputIpOrRange, 2);
            $start = long2ip((ip2long($ip) & ~((1 << (32 - $subnet)) - 1)));
            $end = long2ip(ip2long($ip) | ((1 << (32 - $subnet)) - 1));
        } else {
            $start = $end = $inputIpOrRange;
        }

        return [$start, $end];
    }

    public function isIpInRange(string $ip, string $rangeStart, string $rangeEnd): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($rangeStart, FILTER_VALIDATE_IP) ||  !filter_var($rangeEnd, FILTER_VALIDATE_IP)) {
            return false;
        }

        $ip = ip2long($ip);
        $rangeStart = ip2long($rangeStart);
        $rangeEnd = ip2long($rangeEnd);

        return $ip >= $rangeStart && $ip <= $rangeEnd;
    }
}