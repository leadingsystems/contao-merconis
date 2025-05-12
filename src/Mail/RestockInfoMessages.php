<?php

namespace LeadingSystems\MerconisBundle\Mail;

use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_languageHelper;
use Merconis\Core\ls_shop_messages;

class RestockInfoMessages
{


    const ORDER_MANUAL = 'manual';

    public function __construct()
    {

    }

    public function getMessageSendButton($arrMessageType, $additionalData)
    {
        if($arrMessageType['sendWhen'] != 'onRestock'){
            return false;
        }

        $orderId = $additionalData;

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
        if($arrMessageType['sendWhen'] != 'onRestock'){
            return [];
        }

        $memberData = $this->getMemberData($additionalData['memberId']);

        $arrMails = [];

        if ($arrMessageModel['sendToMemberAddress'] && $memberData !== null && $memberData['email']) {
            $mailAddress = $memberData['email'];
        }

        $arrMails[] =
            [
                'main' => $mailAddress,
                'bcc' => null,
                'data' => [
                    'language' => $memberData['country'],
                ]
            ];

        return $arrMails;
    }

    //hier gibt es die möglichkeit Wildcards zu ersetzen
    public function replaceWildcards($arrMessageType, $text, $data, $additionalData)
    {
        if($arrMessageType['sendWhen'] != 'onRestock'){
            return $text;
        }

        $memberData = $this->getMemberData($additionalData['memberId']);

        $objProduct = ls_shop_generalHelper::getObjProduct($additionalData['productVariantId']);

        if ($objProduct !== null) {
            $text = ls_shop_generalHelper::ls_replaceProductWildcards($text, $objProduct, $memberData['country']);
        }

        if($memberData !== null) {
            $text = ls_shop_generalHelper::ls_replaceMemberWildcards($text, $memberData);
        }


        return $text;
    }

    private function getMemberData($int_memberId){

        $obj_dbres_memberData = \Database::getInstance()
            ->prepare("
                    SELECT      *
                    FROM        tl_member
                    WHERE       id = ?
                ")
            ->limit(1)
            ->execute(
                $int_memberId
            );

        if ($obj_dbres_memberData->numRows) {
            return $obj_dbres_memberData->row();
        }
    }


}