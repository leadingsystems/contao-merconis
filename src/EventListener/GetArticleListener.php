<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\ArticleModel;
use Merconis\Core\ls_shop_generalHelper;

class GetArticleListener
{
    public function execute(ArticleModel $obj_article): void
    {
        ls_shop_generalHelper::conditionalArticleOutput($obj_article);
    }
}