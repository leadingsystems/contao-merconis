<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Merconis\Core\ls_shop_cross_seller;

#[AsInsertTag('shopcrossseller')]
#[AsInsertTag('shop_cross_seller')]
class CrossSeller extends InsertTag
{

	public function customInserttags($strTag, $params) {

        $arrParams = explode(',', $params[0]);
        $crossSellerID = trim($arrParams[0]);
        if ($arrParams[1]) {
            $GLOBALS['merconis_globals']['str_currentProductAliasForCrossSeller'] = trim($arrParams[1]);
        }
        $objCrossSeller = new ls_shop_cross_seller($crossSellerID);
        $str_output = $objCrossSeller->parseCrossSeller();
        if ($arrParams[1]) {
            unset($GLOBALS['merconis_globals']['str_currentProductAliasForCrossSeller']);
        }
        return $str_output;

	}

}
