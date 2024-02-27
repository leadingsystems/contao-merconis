<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsBlockInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\System;

#[AsBlockInsertTag('shopifavailablebasedondate', endTag: 'shopifavailablebasedondate')]
#[AsBlockInsertTag('shopifnotavailablebasedondate', endTag: 'shopifnotavailablebasedondate')]

#[AsBlockInsertTag('shop_if_available_based_on_date', endTag: 'shop_if_available_based_on_date')]
#[AsBlockInsertTag('shop_if_not_available_based_on_date', endTag: 'shop_if_not_available_based_on_date')]
class IfAvailableBasedOnDate extends BlockInsertTag
{
    public function __construct()
    {
        parent::__construct(['shopifnotavailablebasedondate', 'shop_if_not_available_based_on_date']);
    }

    public function customInserttags($insertTag): bool
    {

        if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
            System::getContainer()->get('monolog.logger.contao')->info('Trying to render insert tag "' . $insertTag->getName() . '" in wrong context. Its usage is only supported in delivery time messages.', ['contao' => new ContaoContext('MERCONIS INSERT TAGS', TL_MERCONIS_ERROR)]);
            return '';
        }

        /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
        $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];


        if ($obj_productOrVariant->_isAvailableBasedOnDate) {
            return true;
        }
        return false;
    }

}
