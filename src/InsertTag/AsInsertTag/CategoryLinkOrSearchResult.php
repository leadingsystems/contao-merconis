<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use Merconis\Core\ls_shop_languageHelper;

#[AsInsertTag('shopcategorylinkorsearchresult')]
#[AsInsertTag('shop_category_link_or_search_result')]
class CategoryLinkOrSearchResult extends InsertTag
{

	public function customInserttags($strTag, $params) {
		/** @var PageModel $objPage */
		global $objPage;

        $pageModel = PageModel::findWithDetails($objPage->row()['id']);
        $objContentUrlGenerator = System::getContainer()->get('contao.routing.content_url_generator');

        return Input::get('calledBy') == 'searchResult' ? ls_shop_languageHelper::getLanguagePage('ls_shop_searchResultPages') : $objContentUrlGenerator->generate($pageModel);
	}

}
