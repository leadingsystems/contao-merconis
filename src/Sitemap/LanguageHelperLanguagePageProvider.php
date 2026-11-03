<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

use Merconis\Core\ls_shop_languageHelper;

final class LanguageHelperLanguagePageProvider implements LanguagePageProviderInterface
{
    public function getLanguagePages(int $pageId): array
    {
        if ($pageId <= 0) {
            return [];
        }

        $languagePages = ls_shop_languageHelper::getLanguagePages($pageId);

        return is_array($languagePages) ? $languagePages : [];
    }
}
