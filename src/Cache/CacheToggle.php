<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache;

class CacheToggle
{
    // Set to false to disable all Merconis cache read/write/locks quickly
    public static bool $enabled = true;
}


