<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\ArticleModel;
use Merconis\Core\ls_shop_generalHelper;

class GetArticleListener
{
    public function __invoke(ArticleModel $objArticle): void
    {
        ls_shop_generalHelper::conditionalArticleOutput($objArticle);
    }
}