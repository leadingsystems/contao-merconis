<?php


namespace LeadingSystems\MerconisBundle\Messenger;

use Cron\CronExpression;
use Doctrine\DBAL\Connection;
use Merconis\Core\ls_shop_orderMessages;

class OnStatusChange
{
    protected Connection $connection;
    protected string $executionResultMessage = '';

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

    public function getExecutionResultMessage(): string
    {
        return $this->executionResultMessage ?: 'Executed successfully without returning specific execution result message.';
    }

    protected function sendMessagesOnStatusChange(string $changeType): void
    {
        $orders = $this->connection->fetchAllAssociative("SELECT id FROM tl_ls_shop_orders");

        foreach ($orders as $order) {

            $objOrderMessages = new ls_shop_orderMessages($order['id'], $changeType, 'sendWhen', null, true);
            $objOrderMessages->sendMessages();
        }
    }

}