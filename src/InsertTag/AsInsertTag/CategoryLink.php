<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\PageModel;
use Contao\System;

#[AsInsertTag('shopcategorylink')]
#[AsInsertTag('shop_category_link')]
class CategoryLink extends InsertTag
{

	public function customInserttags($strTag, $params) {
		/** @var PageModel $objPage */
		global $objPage;

        $pageModel = PageModel::findWithDetails($objPage->row()['id']);
        $objContentUrlGenerator = System::getContainer()->get('contao.routing.content_url_generator');

        return $objContentUrlGenerator->generate($pageModel);
	}

}
