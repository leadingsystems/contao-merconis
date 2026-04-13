<?php


namespace LeadingSystems\MerconisBundle\Messenger;

use Contao\Database;
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

    /**
     * Sendet Order-Nachrichten, die auf Statusänderungen basieren, effizient über einen Prefilter.
     */
    protected static function sendMessagesOnStatusChange(string $identificationToken): void
    {
        $arrRelevantOrderIdMap = self::getRelevantOrderIdMapForStatusChangeMessageTypes($identificationToken);

        if (!count($arrRelevantOrderIdMap)) {
            return;
        }

        foreach ($arrRelevantOrderIdMap as $orderId) {
            $objOrderMessages = new ls_shop_orderMessages($orderId, $identificationToken, 'sendWhen', null, true);
            $objOrderMessages->sendMessages();
        }
    }

    /**
     * Liefert eine ID-Map relevanter Orders für Statusänderungs-Nachrichten.
     *
     * "Relevant" bedeutet hier:
     * - für mindestens einen MessageType mit `sendWhen = $identificationToken`
     * - Status-/Payment-Korrelation passt zur Order
     * - es existiert mindestens ein published MessageModel für die MemberGroup der Order
     * - für diese Order wurde dieser MessageType noch nicht gesendet
     *
     * Die Logik spiegelt die Prüfungen aus `ls_shop_orderMessages` wider, verhindert aber die teure Order-Objekt-Erzeugung im Skip-Pfad.
     *
     * @return array<int, int> Map: orderId => orderId
     */
    protected static function getRelevantOrderIdMapForStatusChangeMessageTypes($identificationToken)
    {

        $arrRelevantOrderIdMap = [];

        $objMessageTypes = Database::getInstance()
            ->prepare("
                SELECT      *
                FROM        `tl_ls_shop_message_type`
                WHERE       `sendWhen` = ?
            ")
            ->execute($identificationToken);

        while ($objMessageTypes->next()) {
            $arrMessageType = $objMessageTypes->row();

            $blnAtLeastOneCorrelationUsed = false;

            $strQuery = "
                SELECT      o.`id`
                FROM        `tl_ls_shop_orders` o
                WHERE       NOT EXISTS (
                                SELECT  1
                                FROM    `tl_ls_shop_messages_sent` s
                                WHERE   s.`orderID` = o.`id`
                                    AND s.`messageTypeID` = ?
                            )
                    AND         EXISTS (
                                SELECT  1
                                FROM    `tl_ls_shop_message_model` mm
                                WHERE   mm.`pid` = ?
                                    AND mm.`published` = '1'
                                    AND mm.`member_group` LIKE CONCAT('%\"', o.`memberGroupInfo_id`, '\"%')
                            )
            ";

            $arrQueryValues = [
                $arrMessageType['id'],
                $arrMessageType['id'],
            ];

            for ($i = 1; $i <= 5; $i++) {
                $statusNr = strlen($i) < 2 ? '0' . $i : (string) $i;
                if (!empty($arrMessageType['useStatusCorrelation' . $statusNr])) {
                    $blnAtLeastOneCorrelationUsed = true;
                    $strQuery .= " AND o.`status" . $statusNr . "` = ? ";
                    $arrQueryValues[] = $arrMessageType['statusCorrelation' . $statusNr];
                }
            }

            if (!empty($arrMessageType['usePaymentStatusCorrelation'])) {
                $blnAtLeastOneCorrelationUsed = true;

                $paymentProvider = (string) $arrMessageType['paymentStatusCorrelation_paymentProvider'];


                // Spaltenname ist in Merconis konsistent: <provider>_currentStatus
                $paymentStatusField = $paymentProvider . '_currentStatus';
                $strQuery .= " AND o.`" . $paymentStatusField . "` = ? ";
                $arrQueryValues[] = $arrMessageType['paymentStatusCorrelation_statusValue'];

            }

            if (!$blnAtLeastOneCorrelationUsed) {
                continue;
            }

            $objRelevantOrders = Database::getInstance()
                ->prepare($strQuery)
                ->execute($arrQueryValues);

            while ($objRelevantOrders->next()) {
                $arrRelevantOrderIdMap[$objRelevantOrders->id] = $objRelevantOrders->id;
            }
        }

        return $arrRelevantOrderIdMap;
    }

}