<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsBlockInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\PageModel;
use Contao\System;

#[AsBlockInsertTag('shopifispreorderable', endTag: 'shopifispreorderable')]
#[AsBlockInsertTag('shopifisnotpreorderable', endTag: 'shopifisnotpreorderable')]

#[AsBlockInsertTag('shop_if_is_preorderable', endTag: 'shop_if_is_preorderable')]
#[AsBlockInsertTag('shop_if_is_not_preorderable', endTag: 'shop_if_is_not_preorderable')]
class IfIsPreorderable extends BlockInsertTag
{
    public function __construct()
    {
        parent::__construct(['shopifisnotpreorderable', 'shop_if_is_not_preorderable']);
    }

    public function customInserttags($insertTag): bool
    {
        /** @var PageModel $objPage */
        global $objPage;

        if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
            System::getContainer()->get('monolog.logger.contao')->info('Trying to render insert tag "' . $insertTag->getName() . '" in wrong context. Its usage is only supported in delivery time messages.', ['contao' => new ContaoContext('MERCONIS INSERT TAGS', TL_MERCONIS_ERROR)]);
            return false;
        }

        /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
        $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];


        if ($obj_productOrVariant->_isPreorderable) {
            return true;
        }
        return false;
    }

}
