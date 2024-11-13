<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsBlockInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Merconis\Core\ls_shop_generalHelper;

#[AsBlockInsertTag(name: 'shopifincheckout', endTag: 'shopifincheckout')]
#[AsBlockInsertTag('shopifnotincheckout', endTag: 'shopifnotincheckout')]

#[AsBlockInsertTag('shop_if_in_checkout', endTag: 'shop_if_in_checkout')]
#[AsBlockInsertTag('shop_if_not_in_checkout', endTag: 'shop_if_not_in_checkout')]
class IfInCheckout extends BlockInsertTag
{
    public function __construct()
    {
        parent::__construct(['shopifnotincheckout', 'shop_if_not_in_checkout']);
    }

    public function customInserttags($insertTag): bool
    {
        ls_shop_generalHelper::isInCheckout();
    }

}
