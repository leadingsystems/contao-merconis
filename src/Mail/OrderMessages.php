<?php

namespace LeadingSystems\MerconisBundle\Mail;

use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_languageHelper;

class OrderMessages
{

    /*
     * //TODO: remove: is from copy
    const COLLECTIVE_ORDER_COMPLETE = 'collectiveOrderComplete';
    const COLLECTIVE_ORDER_CANCELED = 'collectiveOrderCanceled';
    */

    public function __construct()
    {

    }

    public function addMessageSendOption()
    {
        //TODO:tl_ls_shop_message_type dont get loaded in extension
        \System::loadLanguageFile('tl_ls_shop_message_type');

        //self::COLLECTIVE_ORDER_COMPLETE, self::COLLECTIVE_ORDER_CANCELED

        //TODO: dont need return value because it is already set in the dca? maybe change?
        return [];
    }

    public function getMessageTypes2($findBy, $identificationToken ) {
        $arrMessageTypes = array();

        /*
         * Get the message type(s) that corresponds with the given identification token. Although it's not very likely
         * to be used it's still possible to have multiple message types with the same "sendWhen" value so it's important
         * that this function or even the whole class can deal with multiple message types and messages for one
         * sending process.
         */
        $objMessageTypes = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_message_type`
			WHERE		`".$findBy."` = ?
		")
            ->execute($identificationToken);

        if (!$objMessageTypes->numRows) {
            return false;
        }

        while ($objMessageTypes->next()) {
            $arrMessageType = $objMessageTypes->row();

            $arrMessageTypes[$objMessageTypes->id] = $arrMessageType;
        }
        return $arrMessageTypes;
    }

    //return button template //TODO: render the right button, currently only test button is rendered
    public function getMessageSendButton($arrMessageType, $additionalData)
    {
        if($arrMessageType['sendWhen'] != 'manual'){
            return false;
        }

        $orderId = $additionalData;

        //order must be forceRefreshed to see if messageType is sent
        $arrOrder = $orderId ? ls_shop_generalHelper::getOrder($orderId, 'id', true) : null;


        //TODO: optimieren und umbenennen
        $arrMessageTypes = $this->getMessageTypes2("sendWhen", 'manual');


        $twig = \Contao\System::getContainer()->get('twig');


        $buttons = [];

        foreach ($arrMessageTypes as $arrMessageType) {

            $blnAlreadySent = in_array($arrMessageType['id'], $arrOrder['messageTypesSent']);

            $arrMessageType['multilanguage']['title'] = ls_shop_languageHelper::getMultiLanguage($arrMessageType['id'], "tl_ls_shop_message_type_languages", array('title'), array($GLOBALS['TL_LANGUAGE']));

            $buttons[] = $twig->render(
                '@LeadingSystemsMerconis/backend/collective_order_send_button.html.twig',
                [
                    /*'error' => 'test',*/
                    /*'title' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['collectiveOrder'],
                    'headline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['collectiveOrder'],*/

                    //array with messages types that should be displayed in backend to send messages
                    'sendWhen' => '',
                    'messageType' => '',
                    'lsShopProductCode' => '',
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

        //TODO: add Receiver Addresses for orders

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

        //TODO: add replace wildcards again
        //TODO: currently no wildcard for order get replaced

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