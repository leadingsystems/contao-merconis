<?php


namespace LeadingSystems\MerconisBundle\Messenger;

use Cron\CronExpression;
use Doctrine\DBAL\Connection;
use Merconis\Core\ls_shop_orderMessages;


class RestockInfo
{

    private Connection $connection;
    private string $executionResultMessage = '';
    private string $cronExpression;

    public function __construct(Connection $connection, string $cronExpression)
    {
        if (!CronExpression::isValidExpression($cronExpression)) {
            throw new \Exception($GLOBALS['TL_LANG']['tl_ls_scheduler_job']['misc']['invalidCronExpressionErrorMessage']);
        }

        $this->connection = $connection;
        $this->cronExpression = $cronExpression;
    }


    public function getCronExpression(): string{

        return $this->cronExpression;
    }


    public function run(): void
    {
        $this->sendRestockInfo();
    }

    public function getExecutionResultMessage(): string
    {
        return $this->executionResultMessage ?: 'Executed successfully without returning specific execution result message.';
    }


    public function sendRestockInfo()
    {
        /** @var PageModel $objPage */
        global $objPage;

        $connection = $this->connection;

        $resultProducts = $connection->executeQuery("
        SELECT
            N.productVariantId, N.productId, N.variantId, N.memberId, N.language
        FROM tl_ls_shop_restock_info_list N
        LEFT JOIN tl_ls_shop_product P ON P.id = N.productId
        WHERE N.variantId = 0 AND P.lsShopProductStock > 0
    ");

        foreach ($resultProducts->fetchAllAssociative() as $row) {

            $str_tmp_objPageLanguage = $objPage->language;
            $objPage->language = $row['language'];

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

            $objPage->language = $str_tmp_objPageLanguage;
        }


        $resultVariants = $connection->executeQuery("
        SELECT
            N.productVariantId, N.productId, N.variantId, N.memberId, N.language
        FROM tl_ls_shop_restock_info_list N
        LEFT JOIN tl_ls_shop_variant V ON V.id = N.variantId
        WHERE N.variantId > 0 AND V.lsShopVariantStock > 0
    ");

        foreach ($resultVariants->fetchAllAssociative() as $row) {

            $str_tmp_objPageLanguage = $objPage->language;
            $objPage->language = $row['language'];

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

            $objPage->language = $str_tmp_objPageLanguage;
        }

        $this->executionResultMessage = 'Executed successfully without returning specific execution result message.';
    }





}