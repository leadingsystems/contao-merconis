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

    /**
     * Purge the gallery-specific Merconis cache pool.
     */
    public function purgeGallery(): void
    {
        $container = System::getContainer();

        if (!$container->has('merconis.gallery.cache')) {
            return;
        }

        /** @var CacheItemPoolInterface $pool */
        $pool = $container->get('merconis.gallery.cache');
        $pool->clear();
    }

    /**
     * Purge the fragment-specific Merconis cache pool.
     */
    public function purgeFragment(): void
    {
        $container = System::getContainer();

        if (!$container->has('merconis.fragment.cache')) {
            return;
        }

        /** @var CacheItemPoolInterface $pool */
        $pool = $container->get('merconis.fragment.cache');
        $pool->clear();
    }

    /**
     * Purge the metadata-specific Merconis cache pool.
     */
    public function purgeMeta(): void
    {
        $container = System::getContainer();

        if (!$container->has('merconis.meta.cache')) {
            return;
        }

        /** @var CacheItemPoolInterface $pool */
        $pool = $container->get('merconis.meta.cache');
        $pool->clear();
    }

    /**
     * Purge the search-specific Merconis cache pool.
     */
    public function purgeSearch(): void
    {
        $container = System::getContainer();

        if (!$container->has('merconis.search.cache')) {
            return;
        }

        /** @var CacheItemPoolInterface $pool */
        $pool = $container->get('merconis.search.cache');
        $pool->clear();
    }
}


