<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\System;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;

#[AsInsertTag('shopdeliverytimedays')]
#[AsInsertTag('shop_delivery_time_days')]
class DeliveryTimeDays extends InsertTag
{

	public function customInserttags($strTag, $params) {

        if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
            System::getContainer()->get('monolog.logger.contao')->info('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', ['contao' => new ContaoContext('MERCONIS INSERT TAGS', TL_MERCONIS_ERROR)]);
            return '';
        }

        /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
        $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];
        $int_deliveryTimeDays = $obj_productOrVariant->getDeliveryTimeDays($GLOBALS['merconis_globals']['arr_dataForInsertTags']['float_requestedQuantity']);
        return $int_deliveryTimeDays;

	}
}
