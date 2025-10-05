<?php

namespace LeadingSystems\MerconisBundle\Maintenance;

use Contao\System;
use Psr\Cache\CacheItemPoolInterface;

class PurgeMerconisCache
{
    /**
     * Purge the dedicated Merconis cache pool.
     */
    public function purge(): void
    {
        $container = System::getContainer();

        if (!$container->has('merconis.cache')) {
            return;
        }

        /** @var CacheItemPoolInterface $pool */
        $pool = $container->get('merconis.cache');
        $pool->clear();
    }
}


