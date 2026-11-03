<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

use Contao\PageModel;

interface PageDetailsProviderInterface
{
    public function getPageDetailsCached(int $pageId): ?PageModel;
}
