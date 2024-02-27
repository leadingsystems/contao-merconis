<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsBlockInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\System;

#[AsBlockInsertTag('shopiforderallowed', endTag: 'shopiforderallowed')]
#[AsBlockInsertTag('shopifordernotallowed', endTag: 'shopifordernotallowed')]

#[AsBlockInsertTag('shop_if_order_allowed', endTag: 'shop_if_order_allowed')]
#[AsBlockInsertTag('shop_if_order_not_allowed', endTag: 'shop_if_order_not_allowed')]
class IfOrderAllowed extends BlockInsertTag
{
    public function __construct()
    {
        parent::__construct(['shopifordernotallowed', 'shop_if_order_not_allowed']);
    }

    public function customInserttags($insertTag): bool
    {

        if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
            System::getContainer()->get('monolog.logger.contao')->info('Trying to render insert tag "' . $insertTag->getName() . '" in wrong context. Its usage is only supported in delivery time messages.', ['contao' => new ContaoContext('MERCONIS INSERT TAGS', TL_MERCONIS_ERROR)]);
            return false;
        }

        /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
        $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];


        if ($obj_productOrVariant->_orderAllowed) {
            return true;
        }
        return false;
    }

}
