<?php

namespace LeadingSystems\MerconisBundle\Mail;

use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_languageHelper;
use Merconis\Core\ls_shop_messages;

class OrderMessages
{


    const ORDER_MANUAL = 'manual';

    public function __construct()
    {

    }

    public function addMessageSendOption()
    {
        //TODO:tl_ls_shop_message_type dont get loaded in extension
        \System::loadLanguageFile('tl_ls_shop_message_type');

        //TODO: dont need return value because it is already set in the dca? maybe change?
        return [];
    }

    public function getMessageSendButton($arrMessageType, $additionalData)
    {
        if($arrMessageType['sendWhen'] != 'manual'){
            return false;
        }

        $orderId = $additionalData;

        //order must be forceRefreshed to see if messageType is sent
        $arrOrder = $orderId ? ls_shop_generalHelper::getOrder($orderId, 'id', true) : null;

        $arrMessageTypes = ls_shop_messages::getMessageTypesStatic("sendWhen", OrderMessages::ORDER_MANUAL);


        $twig = \Contao\System::getContainer()->get('twig');


        $buttons = [];

        foreach ($arrMessageTypes as $arrMessageType) {

            $blnAlreadySent = in_array($arrMessageType['id'], $arrOrder['messageTypesSent']);

            $arrMessageType['multilanguage']['title'] = ls_shop_languageHelper::getMultiLanguage($arrMessageType['id'], "tl_ls_shop_message_type_languages", array('title'), array($GLOBALS['TL_LANGUAGE']));

            $buttons[] = $twig->render(
                '@LeadingSystemsMerconis/backend/send_button.html.twig',
                [
                    /*'error' => 'test',*/
                    /*'title' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['collectiveOrder'],
                    'headline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['collectiveOrder'],*/

                    //array with messages types that should be displayed in backend to send messages
                    'sendWhen' => $arrMessageType['sendWhen'],
                    'messageType' => $arrMessageType['id'],
                    'lsShopProductCode' => $orderId,
                    'buttonTitle' => $arrMessageType['multilanguage']['title'], //button wurde gedrückt


                    'alreadySent' => $blnAlreadySent ? 'alreadySent' : '' //set class for alreadSent
                ]
            );
        }

        return $buttons;
    }

    public function manipulateMessageToSendAndSave($arrMessageToSendAndSave, $arrMessageType, $arrMessageModel, $additionalData)
    {

        if($arrMessageType['sendWhen'] != 'manual'){
            return $arrMessageToSendAndSave;
        }

        $orderId = $additionalData;

        $arrMessageToSendAndSave['orderID'] = $orderId ?: 0;

        return $arrMessageToSendAndSave;

    }

    //hier können die adressen hinzugefügt werden die versendet werden sollnen beim hook basierten
    public function addReceiverAddresses($arrMessageType, $arrMessageModel, $additionalData)
    {
        if($arrMessageType['sendWhen'] != 'manual'){
            return [];
        }

        $orderId = $additionalData;

        //TODO: check, true or false?
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
                    'language' => 'de', //TODO: change later
                ]
            ];


        return $arrMails;

    }

    //hier gibt es die möglichkeit Wildcards zu ersetzen
    public function replaceWildcards($arrMessageType, $text, $data, $additionalData)
    {
        if($arrMessageType['sendWhen'] != 'manual'){
            return $text;
        }

        //TODO: what wildcards need to be added for order messages?

        $orderId = $additionalData;

        //order must be forceRefreshed to see if messageType is sent
        $arrOrder = $orderId ? ls_shop_generalHelper::getOrder($orderId, 'id', true) : null;

        $text = ls_shop_generalHelper::ls_replaceOrderWildcards($text, $arrOrder);

        /*
        if ($this->obj_product !== null) {
            $text = ls_shop_generalHelper::ls_replaceProductWildcards($text, $this->obj_product, $this->ls_language);
        }
        if ($this->arr_memberData !== null) {
            $text = ls_shop_generalHelper::ls_replaceMemberWildcards($text, $this->arr_memberData);
        }*/


        return $text;
    }


}