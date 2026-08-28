<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

use Contao\PageModel;

final class SitemapPageEligibility
{
    public function isEligible(PageModel $pageModel, ?int $currentTimestamp = null): bool
    {
        $timestamp = $currentTimestamp ?? time();

        if ('regular' !== (string) $pageModel->type) {
            return false;
        }

        if ('1' !== (string) $pageModel->published) {
            return false;
        }

        $start = (string) $pageModel->start;
        if ('' !== $start && (int) $start >= $timestamp) {
            return false;
        }

        $stop = (string) $pageModel->stop;
        if ('' !== $stop && (int) $stop <= $timestamp) {
            return false;
        }

        if ('1' === (string) $pageModel->noSearch) {
            return false;
        }

        return 'map_never' !== (string) $pageModel->sitemap;
    }
}
