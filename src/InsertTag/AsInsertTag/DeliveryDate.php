<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Date;
use Contao\PageModel;
use Contao\System;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;

#[AsInsertTag('shopdeliverydate')]
#[AsInsertTag('shop_delivery_date')]
class DeliveryDate extends InsertTag
{

	public function customInserttags($strTag, $params) {
		/** @var PageModel $objPage */
		global $objPage;

        if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
            System::getContainer()->get('monolog.logger.contao')->info('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', ['contao' => new ContaoContext('MERCONIS INSERT TAGS', TL_MERCONIS_ERROR)]);
            return '';
        }

        /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
        $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];

        $deliveryTimeDays = $obj_productOrVariant->getDeliveryTimeDays($GLOBALS['merconis_globals']['arr_dataForInsertTags']['float_requestedQuantity']);

        if (isset($GLOBALS['MERCONIS_HOOKS']['manipulateDeliveryTimeShown']) && is_array($GLOBALS['MERCONIS_HOOKS']['manipulateDeliveryTimeShown'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['manipulateDeliveryTimeShown'] as $mccb) {
                $objMccb = System::importStatic($mccb[0]);
                $deliveryTimeDays = $objMccb->{$mccb[1]}($deliveryTimeDays, $obj_productOrVariant);
            }
        }


        $str_deliveryDate = Date::parse($objPage->dateFormat, time() + 86400 * $deliveryTimeDays);
        return $str_deliveryDate;

	}

}
