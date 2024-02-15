<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsBlockInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Contao\PageModel;
use Merconis\Core\ls_shop_languageHelper;

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
        /** @var PageModel $objPage */
        global $objPage;

        if (in_array(
            $objPage->id,
            [
                ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id'),
                ls_shop_languageHelper::getLanguagePage('ls_shop_reviewPages', false, 'id'),
                ls_shop_languageHelper::getLanguagePage('ls_shop_checkoutFinishPages', false, 'id'),
                ls_shop_languageHelper::getLanguagePage('ls_shop_paymentAfterCheckoutPages', false, 'id'),
                ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id'),
            ]
        )) {
            return true;
        }
        return false;
    }

}
