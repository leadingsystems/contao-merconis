<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;

#[AsInsertTag('shopcurrentlanguage')]
#[AsInsertTag('shop_current_language')]
class CurrentLanguage extends InsertTag
{

	public function customInserttags($strTag, $params) {

		global $objPage;
		return $objPage->language;
	}
}
