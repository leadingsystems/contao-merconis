<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Merconis\Core\ls_shop_languageHelper;

#[AsInsertTag('shoplink')]
#[AsInsertTag('shop_link')]
class Link extends InsertTag
{

	public function customInserttags($strTag, $params) {

		return ls_shop_languageHelper::getLanguagePage('ls_shop_'.$params[0].'s'); // Als Parameter wird z. B. "cartPage" angegeben, da das Feld in der localconfig allerdings in Mehrzahl benannt ist, wird das "s" angehängt.
	}
}
