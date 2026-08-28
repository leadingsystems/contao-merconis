<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\PageModel;

final class SitemapPageScope
{
    public function contains(PageModel $pageModel, SitemapEvent $event): bool
    {
        $rootPageIds = array_map('intval', $event->getRootPageIds());
        $pageId = (int) $pageModel->id;
        $rootPageId = (int) $pageModel->rootId;

        return \in_array($pageId, $rootPageIds, true) || \in_array($rootPageId, $rootPageIds, true);
    }
}
