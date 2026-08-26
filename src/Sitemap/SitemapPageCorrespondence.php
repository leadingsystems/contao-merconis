<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

use Contao\PageModel;

final class SitemapPageCorrespondence
{
    public function __construct(
        private readonly MainLanguagePageResolverInterface $mainLanguagePageResolver,
    ) {
    }

    public function matches(PageModel $assignedPage, PageModel $targetPage): bool
    {
        $assignedPageId = (int) $assignedPage->id;
        $targetPageId = (int) $targetPage->id;

        if ($assignedPageId <= 0 || $targetPageId <= 0) {
            return false;
        }

        if ($assignedPageId === $targetPageId) {
            return true;
        }

        $mainLanguagePageId = $this->mainLanguagePageResolver->resolve($targetPageId);

        return null !== $mainLanguagePageId && $mainLanguagePageId === $assignedPageId;
    }
}
