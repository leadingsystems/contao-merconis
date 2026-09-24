<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

use Contao\System;
use Merconis\Core\ls_shop_product;

final class OrderLabelSnapshotHook
{
    /**
     * @param array<string, mixed> $orderItem
     *
     * @return array<string, mixed>
     */
    public function storeCartItemSnapshot(array $orderItem, ls_shop_product $product): array
    {
        return $this->getSnapshotBuilder()->enrichOrderItem(
            $orderItem,
            $product->mainData,
            $product->_variantIsSelected ? $product->_selectedVariant->mainData : null
        );
    }

    /**
     * @param array<string, mixed> $order
     *
     * @return array<string, mixed>
     */
    public function storeOrderSnapshot(array $order): array
    {
        return $this->getSnapshotBuilder()->enrichOrder($order);
    }

    private function getSnapshotBuilder(): OrderLabelSnapshotBuilder
    {
        return System::getContainer()->get(OrderLabelSnapshotBuilder::class);
    }
}
