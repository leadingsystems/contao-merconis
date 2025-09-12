<?php

namespace LeadingSystems\MerconisBundle\Helpers;

use Merconis\Core\ls_shop_orderMessages;

class RestockInfoMessenger
{
    public $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }


    public function sendRestockInfo()
    {
        $connection = $this->connection;

        $resultProducts = $connection->executeQuery("
        SELECT
            N.productVariantId, N.productId, N.variantId, N.memberId, N.language
        FROM tl_ls_shop_restock_info_list N
        LEFT JOIN tl_ls_shop_product P ON P.id = N.productId
        WHERE N.variantId = 0 AND P.lsShopProductStock > 0
    ");

        foreach ($resultProducts->fetchAllAssociative() as $row) {
            $objOrderMessages = new ls_shop_orderMessages(
                null,
                'onRestock',
                'sendWhen',
                $row['language'],
                false,
                $row['memberId'],
                $row['productVariantId']
            );
            $objOrderMessages->sendMessages();

            $connection->executeStatement(
                "DELETE FROM tl_ls_shop_restock_info_list WHERE productVariantId = ? AND memberId = ?",
                [
                    $row['productVariantId'],
                    $row['memberId']
                ]
            );
        }


        $resultVariants = $connection->executeQuery("
        SELECT
            N.productVariantId, N.productId, N.variantId, N.memberId, N.language
        FROM tl_ls_shop_restock_info_list N
        LEFT JOIN tl_ls_shop_variant V ON V.id = N.variantId
        WHERE N.variantId > 0 AND V.lsShopVariantStock > 0
    ");

        foreach ($resultVariants->fetchAllAssociative() as $row) {
            $objOrderMessages = new ls_shop_orderMessages(
                null,
                'onRestock',
                'sendWhen',
                $row['language'],
                false,
                $row['memberId'],
                $row['productVariantId']
            );
            $objOrderMessages->sendMessages();

            $connection->executeStatement(
                "DELETE FROM tl_ls_shop_restock_info_list WHERE productVariantId = ? AND memberId = ?",
                [
                    $row['productVariantId'],
                    $row['memberId']
                ]
            );
        }
    }

    public function sendMessagesOnStatusChangeCronDaily(): void
    {
        $this->sendMessagesOnStatusChange('onStatusChangeCronDaily');
    }

    public function sendMessagesOnStatusChangeCronHourly(): void
    {
        $this->sendMessagesOnStatusChange('onStatusChangeCronHourly');
    }

    private function sendMessagesOnStatusChange(string $changeType): void
    {
        $orders = $this->connection->fetchAllAssociative("SELECT id FROM tl_ls_shop_orders");

        foreach ($orders as $order) {

            $objOrderMessages = new ls_shop_orderMessages($order['id'], $changeType, 'sendWhen', null, true);
            $objOrderMessages->sendMessages();
        }
    }


}