<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsBlockInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Contao\PageModel;
use Merconis\Core\ls_shop_languageHelper;

#[AsBlockInsertTag('shopifoncartpage', endTag: 'shopifoncartpage')]
#[AsBlockInsertTag('shopifnotoncartpage', endTag: 'shopifnotoncartpage')]

#[AsBlockInsertTag('shop_if_on_cart_page', endTag: 'shop_if_on_cart_page')]
#[AsBlockInsertTag('shop_if_not_on_cart_page', endTag: 'shop_if_not_on_cart_page')]
class IfOnCartPage extends BlockInsertTag
{
    public function __construct()
    {
        parent::__construct(['shopifnotoncartpage', 'shop_if_not_on_cart_page']);
    }

    public function customInserttags($insertTag): bool
    {
        /** @var PageModel $objPage */
        global $objPage;

        if ($objPage->id == ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id')) {
            return true;
        }
        return false;
    }

}
