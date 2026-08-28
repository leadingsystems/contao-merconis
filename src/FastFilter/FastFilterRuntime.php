<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

final class FastFilterRuntime
{
    public static function isActive(): bool
    {
        return !empty($GLOBALS['merconis_globals']['ls_shop_activateFilter'])
            && !empty($GLOBALS['merconis_globals']['ls_shop_useFastFilter']);
    }
}
