<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

use Merconis\Core\ls_shop_languageHelper;

final class LanguageHelperMainLanguagePageResolver implements MainLanguagePageResolverInterface
{
    public function resolve(int $pageId): ?int
    {
        if ($pageId <= 0) {
            return null;
        }

        $mainLanguagePageId = (int) ls_shop_languageHelper::getMainlanguagePageIDForPageID($pageId);

        return $mainLanguagePageId > 0 ? $mainLanguagePageId : null;
    }
}
