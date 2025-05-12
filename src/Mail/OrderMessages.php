<?php

namespace LeadingSystems\MerconisBundle\Mail;

use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_languageHelper;
use Merconis\Core\ls_shop_messages;

class OrderMessages
{

    const ORDER_MANUAL = 'manual';
    const ORDER_ON_STATUS_CHANGE_IMMEDIATELY = 'onStatusChangeImmediately';
    const ORDER_ON_STATUS_CHANGE_CRON_DAILY = 'onStatusChangeCronDaily';
    const ORDER_ON_STATUS_CHANGE_CRON_HOURLY = 'onStatusChangeCronHourly';

    public function __construct()
    {

    }

    public function getMessageSendButton($arrMessageType, $additionalData)
    {
        if($arrMessageType['sendWhen'] != 'manual'){
            return false;
        }

        $orderId = $additionalData;

        //order must be forceRefreshed to see if messageType is sent
        $arrOrder = $orderId ? ls_shop_generalHelper::getOrder($orderId, 'id', true) : null;

        $arrMessageTypes = ls_shop_messages::getMessageTypesStatic("sendWhen", OrderMessages::ORDER_MANUAL, $arrOrder['memberGroupInfo_id']);

        $twig = \Contao\System::getContainer()->get('twig');

        $buttons = [];

        foreach ($arrMessageTypes as $arrMessageType) {

            $blnAlreadySent = in_array($arrMessageType['id'], $arrOrder['messageTypesSent']);

            $arrMessageType['multilanguage']['title'] = ls_shop_languageHelper::getMultiLanguage($arrMessageType['id'], "tl_ls_shop_message_type_languages", array('title'), array($GLOBALS['TL_LANGUAGE']));

            $buttons[] = $twig->render(
                '@LeadingSystemsMerconis/backend/send_button.html.twig',
                [
                    'sendWhen' => $arrMessageType['sendWhen'],
                    'messageType' => $arrMessageType['id'],
                    'lsShopProductCode' => $orderId,
                    'buttonTitle' => $arrMessageType['multilanguage']['title'],

                    'alreadySent' => $blnAlreadySent ? 'alreadySent' : '' //set class for alreadSent
                ]
            );
        }

        return $buttons;
    }

    public function manipulateMessageToSendAndSave($arrMessageToSendAndSave, $arrMessageType, $arrMessageModel, $additionalData)
    {

        if(
            $arrMessageType['sendWhen'] != 'manual' &&
            $arrMessageType['sendWhen'] != OrderMessages::ORDER_ON_STATUS_CHANGE_IMMEDIATELY &&
            $arrMessageType['sendWhen'] != OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_DAILY &&
            $arrMessageType['sendWhen'] != OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_HOURLY
        )
        {
            return $arrMessageToSendAndSave;
        }

        //if status correlation does not fit order
        if (
            $arrMessageType['sendWhen'] == OrderMessages::ORDER_ON_STATUS_CHANGE_IMMEDIATELY ||
            $arrMessageType['sendWhen'] == OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_DAILY ||
            $arrMessageType['sendWhen'] == OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_HOURLY
        ) {
            if (!$this->checkIfMessageTypeStatusCorrelationFitsTheOrder($arrMessageType, $additionalData)) {
                return $arrMessageToSendAndSave;
            }
        }

        $orderId = $additionalData;

        $arrOrder = $orderId ? ls_shop_generalHelper::getOrder($orderId, 'id', false) : null;

        if($arrOrder){
            $arrMessageToSendAndSave['orderNr'] = $arrOrder['orderNr'];
        }

        $arrMessageToSendAndSave['orderID'] = $orderId ?: 0;

        return $arrMessageToSendAndSave;

    }

    //hier können die adressen hinzugefügt werden die versendet werden sollnen beim hook basierten
    public function addReceiverAddresses($arrMessageType, $arrMessageModel, $additionalData)
    {

        if(
            $arrMessageType['sendWhen'] != 'manual' &&
            $arrMessageType['sendWhen'] != OrderMessages::ORDER_ON_STATUS_CHANGE_IMMEDIATELY &&
            $arrMessageType['sendWhen'] != OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_DAILY &&
            $arrMessageType['sendWhen'] != OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_HOURLY
        )
        {
            return [];
        }

        //if status correlation does not fit order
        if (
            $arrMessageType['sendWhen'] == OrderMessages::ORDER_ON_STATUS_CHANGE_IMMEDIATELY ||
            $arrMessageType['sendWhen'] == OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_DAILY ||
            $arrMessageType['sendWhen'] == OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_HOURLY
        ) {
            if (!$this->checkIfMessageTypeStatusCorrelationFitsTheOrder($arrMessageType, $additionalData)) {
                return [];
            }
        }

        $orderId = $additionalData;

        $blnForceOrderRefresh = false;

        $arrOrder = $orderId ? ls_shop_generalHelper::getOrder($orderId, 'id', $blnForceOrderRefresh) : null;

        $arrMails = [];


        // use customer address no. 1 if it can be determined
        if ($arrMessageModel['sendToCustomerAddress1'] && isset($arrOrder['customerData'][$arrMessageModel['customerDataType1']][$arrMessageModel['customerDataField1']]) && $arrOrder['customerData'][$arrMessageModel['customerDataType1']][$arrMessageModel['customerDataField1']]) {
            $mailAddress = $arrOrder['customerData'][$arrMessageModel['customerDataType1']][$arrMessageModel['customerDataField1']];
        }

        // overwrite the current main address with customer address no. 2 if it can be determined
        if ($arrMessageModel['sendToCustomerAddress2'] && isset($arrOrder['customerData'][$arrMessageModel['customerDataType2']][$arrMessageModel['customerDataField2']]) && $arrOrder['customerData'][$arrMessageModel['customerDataType2']][$arrMessageModel['customerDataField2']]) {
            $mailAddress = $arrOrder['customerData'][$arrMessageModel['customerDataType1']][$arrMessageModel['customerDataField2']];
        }

        $arrMails[] =
            [
                'main' => $mailAddress,
                'bcc' => null,
                'data' => [
                    'language' => $arrOrder['customerLanguage'],
                ]
            ];


        return $arrMails;

    }

    //hier gibt es die möglichkeit Wildcards zu ersetzen
    public function replaceWildcards($arrMessageType, $text, $data, $additionalData)
    {
        if(
            $arrMessageType['sendWhen'] != 'manual' &&
            $arrMessageType['sendWhen'] != OrderMessages::ORDER_ON_STATUS_CHANGE_IMMEDIATELY &&
            $arrMessageType['sendWhen'] != OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_DAILY &&
            $arrMessageType['sendWhen'] != OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_HOURLY
        )
        {
            return $text;
        }

        //if status correlation does not fit order
        if (
            $arrMessageType['sendWhen'] == OrderMessages::ORDER_ON_STATUS_CHANGE_IMMEDIATELY ||
            $arrMessageType['sendWhen'] == OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_DAILY ||
            $arrMessageType['sendWhen'] == OrderMessages::ORDER_ON_STATUS_CHANGE_CRON_HOURLY
        ) {
            if (!$this->checkIfMessageTypeStatusCorrelationFitsTheOrder($arrMessageType, $additionalData)) {
                return $text;
            }
        }

        $orderId = $additionalData;

        //order must be forceRefreshed to see if messageType is sent
        $arrOrder = $orderId ? ls_shop_generalHelper::getOrder($orderId, 'id', true) : null;

        $text = ls_shop_generalHelper::ls_replaceOrderWildcards($text, $arrOrder);

        return $text;
    }


    /*
	 * Check whether the different status types' values of the order fit the status correlation values
	 * of the message type. All status types for which a correlation is defined in the message type
	 * need to fit. If one of them doesn't, this function returns false.
	 */
    protected function checkIfMessageTypeStatusCorrelationFitsTheOrder($arrMessageType, $additionalData) {

        $orderId = $additionalData;
        $arrOrder = $orderId ? ls_shop_generalHelper::getOrder($orderId, 'id', true) : null;

        $blnAtLeastOneStatusCorrelationUsed = false;
        for ($i = 1; $i <= 5; $i++) {
            $statusNr = strlen($i) < 2 ? '0'.$i : $i;
            if ($arrMessageType['useStatusCorrelation'.$statusNr]) {
                $blnAtLeastOneStatusCorrelationUsed = true;
                if ($arrOrder['status'.$statusNr] != $arrMessageType['statusCorrelation'.$statusNr]) {
                    return false;
                }
            }
        }

        if ($arrMessageType['usePaymentStatusCorrelation']) {
            $blnAtLeastOneStatusCorrelationUsed = true;
            if ($arrOrder[$arrMessageType['paymentStatusCorrelation_paymentProvider'].'_currentStatus'] != $arrMessageType['paymentStatusCorrelation_statusValue']) {
                return false;
            }
        }

        if (!$blnAtLeastOneStatusCorrelationUsed) {
            return false;
        }

        return true;
    }


}