<?php

namespace LeadingSystems\MerconisBundle\ProductSearch;

use Merconis\Core\ls_shop_languageHelper;

class Helper
{
    public function getSearchLanguage() {
        $searchLanguage = null;

        // Use the language of the current page if we have a fronted call
        if (TL_MODE == 'FE') {
            /** @var \PageModel $objPage */
            global $objPage;
            $searchLanguage = $objPage->language;
        }

        if (!$searchLanguage) {
            $searchLanguage = ls_shop_languageHelper::getFallbackLanguage();
        }

        return $searchLanguage;
    }
}