<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\PageModel;

class SitemapLanguageTargetProcessor
{
    public function __construct(
        private readonly LanguagePageProviderInterface $languagePageProvider,
        private readonly PageDetailsProviderInterface $pageDetailsProvider,
        private readonly SitemapPageCorrespondence $pageCorrespondence,
        private readonly SitemapPageEligibility $pageEligibility,
        private readonly SitemapUrlAppender $sitemapUrlAppender,
    ) {
    }

    /**
     * @param array<string, mixed> $productRow
     */
    public function appendProductUrlsForAssignedPage(
        SitemapEvent $event,
        PageModel $assignedPage,
        array $productRow,
    ): int {
        $assignedPageId = (int) $assignedPage->id;
        if ($assignedPageId <= 0) {
            return 0;
        }

        $addedUrlCount = 0;

        foreach ($this->languagePageProvider->getLanguagePages($assignedPageId) as $languagePageInfo) {
            $targetPageId = (int) ($languagePageInfo['id'] ?? 0);
            if ($targetPageId <= 0) {
                continue;
            }

            $targetPage = $this->pageDetailsProvider->getPageDetailsCached($targetPageId);
            if (!$targetPage instanceof PageModel) {
                continue;
            }

            if (
                !$this->pageCorrespondence->matches($assignedPage, $targetPage)
                || !$this->pageEligibility->isEligible($targetPage)
            ) {
                continue;
            }

            $targetLanguage = (string) $targetPage->language;
            if ('' === $targetLanguage) {
                continue;
            }

            $productAlias = (string) ($productRow['alias_'.$targetLanguage] ?? '');
            if ('' === $productAlias) {
                continue;
            }

            if ($this->sitemapUrlAppender->appendProductUrl($event, $targetPage, $productAlias)) {
                ++$addedUrlCount;
            }
        }

        return $addedUrlCount;
    }
}
