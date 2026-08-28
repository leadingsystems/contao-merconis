<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

use Contao\PageModel;

final class ContaoPageDetailsProvider implements PageDetailsProviderInterface
{
    public function __construct(
        private readonly object $pageController,
    ) {
    }

    public function getPageDetailsCached(int $pageId): ?PageModel
    {
        if ($pageId <= 0) {
            return null;
        }

        $pageDetails = $this->pageController->getPageDetailsCached($pageId);

        return $pageDetails instanceof PageModel ? $pageDetails : null;
    }
}
