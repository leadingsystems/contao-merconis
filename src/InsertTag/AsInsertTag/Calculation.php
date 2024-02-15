<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_generalHelper;

#[AsInsertTag('shopcalculation')]
#[AsInsertTag('shop_calculation')]
class Calculation extends InsertTag
{

	public function customInserttags($strTag, $params) {

        switch ($params[0]) {
            case 'invoicedAmount':
                return ls_shop_generalHelper::outputPrice(ls_shop_cartX::getInstance()->calculation['invoicedAmount']);
                break;
        }
        return false;

	}

}
