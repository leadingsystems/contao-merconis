<?php

namespace Merconis\Core;
use Contao\StringUtil;
use function LeadingSystems\Helpers\createMultidimensionalArray;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_messages
{

    /**
     *  $identificationToken and $findBy are going to be used to find the tl_ls_shop_message_type's
     *  example: $findBy = 'id' $identificationToken = 6
     */
	protected $identificationToken = null;
	protected $findBy = 'alias';
	protected $ls_language = 'en';

	protected $arrMessageModels = null;
	protected $arrMessageTypes = null;
    
	protected $counterNr = null;

    /**
     *  additionalData is sometimes needed to know what button got pressed and what to do, it can be anything needed
     *  example: if "send order messages" got pressed in the backend, the orderId would be the additionalData
     */
    protected $additionalData = null;
	
	public function __construct($identificationToken = null, $findBy = null, $additionalData = null, $language = null) {
		/** @var \PageModel $objPage */
		global $objPage;

		$this->identificationToken = $identificationToken;
		$this->findBy = $findBy ?: $this->findBy;
        $this->additionalData = $additionalData;

		/*
		 * If no language is given as an attribute on instantiation, $objPage->language will be used. If $objPage->language
		 * is not available (backend call). If this language information
		 * also doesn't exist (which should never be the case) english (defined as default in this class) is used.
		 * 
		 * This way a frontend call without an explicitly given language should automatically send messages in the current frontend language,
		 * and therefore it's probably not very often required to give a language as an attribute on instantiation. An example when
		 * it's required to give a language would be the order notice because in this case a message to the shop admin in the shop language
		 * should be sent from a frontend call regardless of what the current frontend language is.
		 */
		$this->ls_language = $language ?: (isset($objPage) && is_object($objPage) && $objPage->language ? $objPage->language : $this->ls_language);

		$this->getMessageModels();

	}
	
	public function __set($key, $value) {
		switch ($key) {
			default:
				break;
		}
	}

	public function getMessageTypes() {
		$this->arrMessageTypes = array();
		
		/*
		 * Get the message type(s) that corresponds with the given identification token. Although it's not very likely
		 * to be used it's still possible to have multiple message types with the same "sendWhen" value so it's important
		 * that this function or even the whole class can deal with multiple message types and messages for one 
		 * sending process.
		 */
		$objMessageTypes = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_message_type`
			WHERE		`".$this->findBy."` = ?
		")
		->execute($this->identificationToken);
		
		if (!$objMessageTypes->numRows) {
			return false;
		}
		
		while ($objMessageTypes->next()) {
			$arrMessageType = $objMessageTypes->row();

			$this->arrMessageTypes[$objMessageTypes->id] = $arrMessageType;
		}
	}

    public static function getMessageTypesStatic($findBy, $identificationToken, $memberGroupInfo_id = false) {
        $arrMessageTypes = array();

        /*
         * Get the message type(s) that corresponds with the given identification token. Although it's not very likely
         * to be used it's still possible to have multiple message types with the same "sendWhen" value so it's important
         * that this function or even the whole class can deal with multiple message types and messages for one
         * sending process.
         */

        $groupInfo = "";

        if($memberGroupInfo_id){
            $groupInfo = "AND	`tl_ls_shop_message_model`.`member_group` LIKE '%%" . $memberGroupInfo_id . "%'";
        }


        $objMessageTypes = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_message_type`
			WHERE		`".$findBy."` = ?
			AND		(
								SELECT	COUNT(*)
								FROM	`tl_ls_shop_message_model`
								WHERE	`tl_ls_shop_message_model`.`pid` = `tl_ls_shop_message_type`.`id`
									AND	`tl_ls_shop_message_model`.`published` = '1'
									".$groupInfo."
							) > 0
		")
            ->execute($identificationToken);

        //TODO: für was wird das gebraucht?: AND	`tl_ls_shop_message_model`.`member_group` LIKE ?

        if (!$objMessageTypes->numRows) {
            return false;
        }

        while ($objMessageTypes->next()) {
            $arrMessageType = $objMessageTypes->row();

            $arrMessageTypes[$objMessageTypes->id] = $arrMessageType;
        }
        return $arrMessageTypes;
    }




	//returns a list of buttons
    public function getButtonArray()
    {

        foreach ($this->arrMessageTypes as $arrMessageType) {
            if (isset($GLOBALS['MERCONIS_HOOKS']['getMessageSendButton']) && is_array($GLOBALS['MERCONIS_HOOKS']['getMessageSendButton'])) {
                foreach ($GLOBALS['MERCONIS_HOOKS']['getMessageSendButton'] as $mccb) {

                    $objMccb = \System::importStatic($mccb[0]);

                    //return value is false is button dont exist for this hook
                    $returnValue = $objMccb->{$mccb[1]}($arrMessageType, $this->additionalData);

                    if ($returnValue) {
                        return $returnValue;
                    }

                }
            }
        }

        return [];

    }

    //bekomme alle Message Models und unterscheide dann je nach User welche geschickt werden
	public function getMessageModels() {
		$arrMessageModels = array();

		$this->getMessageTypes();
		
		foreach ($this->arrMessageTypes as $messageTypeID => $arrMessageType) {

            $objMessageModels = \Database::getInstance()->prepare("
                        SELECT		*
                        FROM		`tl_ls_shop_message_model`
                        WHERE		`pid` = ?
                            AND		`published` = 1
                    ")
                ->execute($messageTypeID);

            if (!$objMessageModels->numRows) {
                continue;
            }

            while ($objMessageModels->next()) {
                $arrMessageModels[$objMessageModels->id] = $objMessageModels->row();
                //TODO verschieben wird an einer anderen stelle gebraucht
			}
        }
		
		$this->arrMessageModels = $arrMessageModels;
	}
	
	public function sendMessages() {

		if (!is_array($this->arrMessageModels)) {
			return false;
		}

		$currentMessageTypeID = null;
		$lastMessageTypeID = null;

        $this->getMessageTypes();

		foreach ($this->arrMessageModels as $arrMessageModel) {

			$currentMessageTypeID = $arrMessageModel['pid'];

            $arrAllReceiverAddresses = $this->getReceiverAddresses($this->arrMessageTypes[$currentMessageTypeID], $arrMessageModel);
			
			if ($arrAllReceiverAddresses === null)	{
				// no receiver address could be determined therefore this message model is being skipped
				continue;
			}
			
			if (!\Validator::isEmail(\Idna::encodeEmail($arrMessageModel['senderAddress']))) {
				// log an error if the sender address is invalid and then skip this message model
				\System::log('MERCONIS: message using message model with id '.$arrMessageModel['id'].' could not be sent because sender address "'.$arrMessageModel['senderAddress'].'" is invalid', 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);
				continue;
			}
			
			if (!$arrMessageModel['useHTML'] && !$arrMessageModel['useRawtext']) {
				// log an error if neither useHTML nor useRawtext is checked and then skip this message model
				\System::log('MERCONIS: message using message model with id '.$arrMessageModel['id'].' could not be sent because neither the usage of HTML nor the usage of rawtext is selected', 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);
				continue;
			}


            foreach($arrAllReceiverAddresses as $arrReceiverAddresses) {

                /*
                 * If this is the first message to be sent for a messageType, i.e. the currentMessageTypeID is not the same
                 * as the lastMessageTypeID, a new counter nr is being generated (if necessary),
                 */
                if ($currentMessageTypeID != $lastMessageTypeID) {
                    $this->generateNewCounterNr($currentMessageTypeID);
                }
                $lastMessageTypeID = $currentMessageTypeID;

                if ($arrMessageModel['useHTML']) {
                    $objTemplate_emailHTML = new \FrontendTemplate($arrMessageModel['template_html']);
                    if (version_compare(VERSION . '.' . BUILD, '3.3.0', '<')) {
                        $objTemplate_emailHTML->content = \Controller::replaceInsertTags($this->ls_replaceWildcards($this->arrMessageTypes[$currentMessageTypeID], $arrReceiverAddresses['data'], \Controller::replaceInsertTags($arrMessageModel['content_html_'.$arrReceiverAddresses['data']['language']])));
                    } else {
                        $objTemplate_emailHTML->content = \Controller::replaceInsertTags($this->ls_replaceWildcards($this->arrMessageTypes[$currentMessageTypeID], $arrReceiverAddresses['data'], \StringUtil::insertTagToSrc(\Controller::replaceInsertTags($arrMessageModel['content_html_'.$arrReceiverAddresses['data']['language']]))));
                    }
                    $objTemplate_emailHTML->arrMessageModel = $arrMessageModel;
                    $objTemplate_emailHTML->counterNr = $this->counterNr;
                }

                if ($arrMessageModel['useRawtext']) {
                    $objTemplate_rawtext = new \FrontendTemplate($arrMessageModel['template_rawtext']);
                    $objTemplate_rawtext->content = \Controller::replaceInsertTags($this->ls_replaceWildcards($this->arrMessageTypes[$currentMessageTypeID], $arrReceiverAddresses['data'], \Controller::replaceInsertTags($arrMessageModel['content_rawtext_'.$arrReceiverAddresses['data']['language']])));
                    $objTemplate_rawtext->arrMessageModel = $arrMessageModel;
                    $objTemplate_rawtext->counterNr = $this->counterNr;
                }

                $arrMessageToSendAndSave = array(
                    'tstamp' => time(),
                    'messageTypeAlias' => $this->arrMessageTypes[$arrMessageModel['pid']]['alias'],
                    'messageTypeID' => $currentMessageTypeID,
                    'messageModelID' => $arrMessageModel['id'],
                    'counterNr' => $this->counterNr,
                    'senderName' => html_entity_decode($arrMessageModel['senderName']),
                    'senderAddress' => $arrMessageModel['senderAddress'],
                    'receiverMainAddress' => $arrReceiverAddresses['main'],
                    'receiverBccAddress' => $arrReceiverAddresses['bcc'],
                    'subject' => html_entity_decode($this->ls_replaceWildcards($this->arrMessageTypes[$currentMessageTypeID], $arrReceiverAddresses['data'], \Controller::replaceInsertTags($arrMessageModel['subject_'.$arrReceiverAddresses['data']['language']]))),
                    'bodyHTML' => $arrMessageModel['useHTML'] ? $objTemplate_emailHTML->parse() : '',
                    'bodyRawtext' => $arrMessageModel['useRawtext'] ? $objTemplate_rawtext->parse() : '',
                    'dynamicPdfAttachmentPaths' => StringUtil::deserialize($arrMessageModel['dynamicAttachments_'.$arrReceiverAddresses['data']['language']]),
                    'attachmentPaths' => StringUtil::deserialize($arrMessageModel['attachments_'.$arrReceiverAddresses['data']['language']])
                );

                if (isset($GLOBALS['MERCONIS_HOOKS']['manipulateMessageToSendAndSave']) && is_array($GLOBALS['MERCONIS_HOOKS']['manipulateMessageToSendAndSave'])) {
                    foreach ($GLOBALS['MERCONIS_HOOKS']['manipulateMessageToSendAndSave'] as $mccb) {
                        $objMccb = \System::importStatic($mccb[0]);
                        $arrMessageToSendAndSave = $objMccb->{$mccb[1]}($arrMessageToSendAndSave, $this->arrMessageTypes[$currentMessageTypeID], $arrMessageModel, $this->additionalData);
                    }
                }

                $objEmail = new \Email();
                $objEmail->embedImages = !$arrMessageModel['externalImages'];
                $objEmail->from = $arrMessageToSendAndSave['senderAddress'];
                $objEmail->fromName = $arrMessageToSendAndSave['senderName'];
                $objEmail->subject = $arrMessageToSendAndSave['subject'];

                // Dynamic PDF attachments
                $arrTmpGeneratedDynamicAttachmentFiles = array();
                if (is_array($arrMessageToSendAndSave['dynamicPdfAttachmentPaths']) && count($arrMessageToSendAndSave['dynamicPdfAttachmentPaths']) > 0) {

                    foreach ($arrMessageToSendAndSave['dynamicPdfAttachmentPaths'] as $strDynamicAttachmentFile) {
                        $strDynamicAttachmentFile = ls_getFilePathFromVariableSources($strDynamicAttachmentFile);
                        /*
                         * Use the possibly given dynamicAttachmentFile(s) to create a pdf file
                         * and use this file as attachments
                         */
                        if (file_exists(TL_ROOT . '/' . $strDynamicAttachmentFile)) {
                            require_once(TL_ROOT . '/' . $strDynamicAttachmentFile);

                            // The class name of the dynamicAttachmentFile must match it's filename without the suffix
                            $dynamicAttachmentClassName = 'Merconis\Core\\' . preg_replace('/(^.*\/)([^\/\.]*)(\.php$)/', '\\2', $strDynamicAttachmentFile);

                            /*
                             * The __constructor function of the dynamicAttachmentClass has to take the order array as an argument
                             * and it has to return the file path of the saved file so that it can be attached to the email object
                             * directly.
                             */

                            $flexParameters = createMultidimensionalArray(\LeadingSystems\Helpers\createOneDimensionalArrayFromTwoDimensionalArray(json_decode($arrMessageModel['flex_parameters'])), 2, 1);

                            $objDynamicAttachment = new $dynamicAttachmentClassName(null, $this->counterNr, array_merge($flexParameters, $arrReceiverAddresses['data']));
                            $dynamicAttachmentSavedFilename = $objDynamicAttachment->parse();

                            if ($dynamicAttachmentSavedFilename && file_exists(TL_ROOT . '/' . $dynamicAttachmentSavedFilename)) {
                                $objEmail->attachFile(TL_ROOT . '/' . $dynamicAttachmentSavedFilename);
                                $arrTmpGeneratedDynamicAttachmentFiles[] = $dynamicAttachmentSavedFilename;
                            }
                        }
                    }
                }

                $arrMessageToSendAndSave['dynamicPdfAttachmentPaths'] = $arrTmpGeneratedDynamicAttachmentFiles;

                // Attachments
                if (is_array($arrMessageToSendAndSave['attachmentPaths']) && count($arrMessageToSendAndSave['attachmentPaths']) > 0) {
                    foreach ($arrMessageToSendAndSave['attachmentPaths'] as $strAttachment) {
                        $strAttachment = ls_getFilePathFromVariableSources($strAttachment);
                        if ($strAttachment && file_exists(TL_ROOT . '/' . $strAttachment)) {
                            $objEmail->attachFile(TL_ROOT . '/' . $strAttachment);
                        }
                    }
                }

                if ($arrMessageModel['useHTML']) {
                    $objEmail->html = \Controller::convertRelativeUrls($arrMessageToSendAndSave['bodyHTML']);
                }

                if ($arrMessageModel['useRawtext']) {
                    $objEmail->text = $arrMessageToSendAndSave['bodyRawtext'];
                }

                if ($arrMessageToSendAndSave['receiverBccAddress']) {
                    $objEmail->sendBcc($arrMessageToSendAndSave['receiverBccAddress']);
                }

                try {
                    //TODO: works fine, add later again to send mails
                    $objEmail->sendTo($arrMessageToSendAndSave['receiverMainAddress']);
                    \System::log('MERCONIS: message sent using message model with id ' . $arrMessageModel['id'], 'MERCONIS MESSAGES', TL_MERCONIS_MESSAGES);
                } catch (\Exception $e) {
                    \System::log('MERCONIS: Swift Exception, message "' . $this->arrMessageTypes[$arrMessageModel['pid']]['alias'] . '"using message model with id ' . $arrMessageModel['id'] . ' could not be sent (' . StringUtil::standardize($e->getMessage()) . ')', 'MERCONIS MESSAGES', TL_MERCONIS_MESSAGES);
                }

                $this->writeDispatchDate($currentMessageTypeID);

                $this->saveSentMessage($arrMessageToSendAndSave);
            }
		}
		\System::loadLanguageFile('default', $GLOBALS['TL_LANGUAGE'], true);
	}

	protected function saveSentMessage($arrMessageToSave) {
		$arrMessageToSave['dynamicPdfAttachmentPaths'] = serialize($arrMessageToSave['dynamicPdfAttachmentPaths']);
		$arrMessageToSave['attachmentPaths'] = serialize($arrMessageToSave['attachmentPaths']);

        $string = '';


        $last_key = array_key_last($arrMessageToSave);

        foreach ($arrMessageToSave as $key => $value){
            if ($key == $last_key) {
                $string = $string.'`'.$key.'` = ?';
            } else {
                $string = $string.'`'.$key.'` = ?,';
            }
        }

        if(!$arrMessageToSave['receiverMainAddress']){
            return;
        }

		\Database::getInstance()->prepare("
			INSERT INTO	`tl_ls_shop_messages_sent`
			SET			".$string."
		")
		->execute($arrMessageToSave);
	}





	/*
	 * This function generates the new counter number for a given messageType.
	 * 
	 * Important: 
	 * If the message type doesn't use a counter, the new counter number needs to be
	 * set to null 
	 */
	protected function generateNewCounterNr($messageTypeID = null) {
		if (!$messageTypeID || !$this->arrMessageTypes[$messageTypeID]['useCounter']) {
			$this->counterNr = null;
			return;
		}
		
		$counterNr = $this->arrMessageTypes[$messageTypeID]['counterString'];
		
		/*
		 * Zurücksetzen des Zählers, sofern durch den angegebenen Rücksetz-Zyklus nötig
		 */
		$resetCounter = false;
		if (isset($this->arrMessageTypes[$messageTypeID]['counterRestartCycle'])) {
			if (!$this->arrMessageTypes[$messageTypeID]['lastDispatchDateUnixTimestamp']) {
				/*
				 * Ist ein Rücksetzzyklus angegeben und existiert keine letzte Nachricht (lastDispatchDateUnixTimestamp also false/leer),
				 * so wird auf jeden Fall zurückgesetzt, da also auf jeden Fall ein neues Jahr, ein neuer Monat oder was
				 * auch immer beginnt.
				 */
				$resetCounter = true;
			} else {
				/*
				 * Existiert eine letzte Bestellung und damit ein Timestamp der letzten Bestellung,
				 * so wird geprüft, ob abhängig vom eingestellten Rücksetzzyklus ein neuer Zeitraum
				 * beginnt und der Zähler also zurückgesetzt werden muss.
				 */
				switch($this->arrMessageTypes[$messageTypeID]['counterRestartCycle']) {
					case 'day':
						// Prüfen, ob sich das Jahr seit der letzten Bestellung geändert hat
						if (date('d') != date('d', $this->arrMessageTypes[$messageTypeID]['lastDispatchDateUnixTimestamp'])) {
							$resetCounter = true;
						}
						// kein break, da die darunterfolgenden Prüfungen auch noch stattfinden sollen

					case 'week':
						// Prüfen, ob sich das Jahr seit der letzten Bestellung geändert hat
						if (date('W') != date('W', $this->arrMessageTypes[$messageTypeID]['lastDispatchDateUnixTimestamp'])) {
							$resetCounter = true;
						}
						// kein break, da die darunterfolgenden Prüfungen auch noch stattfinden sollen
						
					case 'month':
						// Prüfen, ob sich das Jahr seit der letzten Bestellung geändert hat
						if (date('m') != date('m', $this->arrMessageTypes[$messageTypeID]['lastDispatchDateUnixTimestamp'])) {
							$resetCounter = true;
						}
						// kein break, da die darunterfolgenden Prüfungen auch noch stattfinden sollen
						
					case 'year':
						// Prüfen, ob sich das Jahr seit der letzten Bestellung geändert hat
						if (date('Y') != date('Y', $this->arrMessageTypes[$messageTypeID]['lastDispatchDateUnixTimestamp'])) {
							$resetCounter = true;
						}
						break;
				}				
			}
		}

		/*
		 * Ermitteln des nächsten Zählers, beim Startwert anfangen, wenn entweder eine der Rücksetzbedingungen
		 * true ergab oder wenn noch kein Counter eingetragen oder dieser 0 bzw. false ist
		 */
		if ($resetCounter || !isset($this->arrMessageTypes[$messageTypeID]['counter']) || !$this->arrMessageTypes[$messageTypeID]['counter']) {
			$this->arrMessageTypes[$messageTypeID]['counter'] = $this->arrMessageTypes[$messageTypeID]['counterStart'] ? $this->arrMessageTypes[$messageTypeID]['counterStart'] : 1;
		} else {
			$this->arrMessageTypes[$messageTypeID]['counter'] = $this->arrMessageTypes[$messageTypeID]['counter'] + 1;
		}
		
		/*
		 * Eintragen des ermittelten Zählers in Datensatz
		 */
		\Database::getInstance()->prepare("
			UPDATE		`tl_ls_shop_message_type`
			SET			`counter` = ?
			WHERE		`id` = ?
		")
		->limit(1)
		->execute($this->arrMessageTypes[$messageTypeID]['counter'], $messageTypeID);

		
		/*
		 * Ermitteln der Datumsplatzhalter und ersetzen
		 * derselben
		 */
		preg_match_all('/\{\{date:(.*)\}\}/siU', $counterNr, $matches);
		foreach ($matches[0] as $key => $match) {
			$counterNr = preg_replace('/'.preg_quote($match).'/siU', date($matches[1][$key]), $counterNr);
		}
		
		/*
		 * Ersetzen des Zähler-Platzhalters
		 */

        //mit parameter
        preg_match_all('/\{\{counter:(.*)\}\}/siU', $counterNr, $matches);
        foreach ($matches[0] as $key => $match) {
            $counterNr = preg_replace('/'.preg_quote($match).'/siU', str_pad($this->arrMessageTypes[$messageTypeID]['counter'], $matches[1][$key], "0", STR_PAD_LEFT), $counterNr);
        }


        //ohne parameter
        $counterNr = preg_replace('/\{\{counter\}\}/siU', $this->arrMessageTypes[$messageTypeID]['counter'], $counterNr);
		
		$this->counterNr = $counterNr;
	}

	protected function writeDispatchDate($messageTypeID = null) {
		if (!$messageTypeID) {
			return false;
		}
		
		\Database::getInstance()->prepare("
			UPDATE		`tl_ls_shop_message_type`
			SET			`lastDispatchDateUnixTimestamp` = ?
			WHERE		`id` = ?
		")
		->limit(1)
		->execute(time(), $messageTypeID);
	}
	
	protected function getReceiverAddresses($arrMessageType, $arrMessageModel = null) {
		if (!$arrMessageModel) {
			return null;
		}

        /*
		$arrReceiverAddresses = array(
			'main' => null,
			'bcc' => null,
            'language' => 'en'
		);
        */

        $arrAllReceiverAddresses = [];


        if (isset($GLOBALS['MERCONIS_HOOKS']['addReceiverAddresses']) && is_array($GLOBALS['MERCONIS_HOOKS']['addReceiverAddresses'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['addReceiverAddresses'] as $mccb) {
                $objMccb = \System::importStatic($mccb[0]);
                $arrAllReceiverAddresses = array_merge($arrAllReceiverAddresses, $objMccb->{$mccb[1]}($arrMessageType, $arrMessageModel, $this->additionalData));
            }
        }
		
		$blnAddressInvalid = false;

        if (!empty($arrAllReceiverAddresses)) {
            foreach ($arrAllReceiverAddresses as $key => $arrReceiverAddresses) {

                if ($arrMessageModel['sendToSpecificAddress'] && $arrMessageModel['specificAddress']) {
                    if (!$arrReceiverAddresses['main']) {
                        // set the main address to the specific address if this one is given and the main address is not yet set
                        $arrAllReceiverAddresses[$key]['main'] = $arrMessageModel['specificAddress'];
                    } else {
                        // set the bcc address to the specific address if it is given and the main address is already set
                        $arrAllReceiverAddresses[$key]['bcc'] = $arrMessageModel['specificAddress'];
                    }
                }

                if ($arrReceiverAddresses['main'] && !\Validator::isEmail(\Idna::encodeEmail($arrReceiverAddresses['main']))) {
                    // log an error if the address is invalid
                    \System::log('MERCONIS: message using message model with id ' . $arrMessageModel['id'] . ' could not be sent because main receiver address "' . $arrReceiverAddresses['main'] . '" is invalid', 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);
                }

                if ($arrReceiverAddresses['bcc'] && !\Validator::isEmail(\Idna::encodeEmail($arrReceiverAddresses['bcc']))) {
                    // log an error if the address is invalid
                    \System::log('MERCONIS: message using message model with id ' . $arrMessageModel['id'] . ' could not be sent because BCC receiver address "' . $arrReceiverAddresses['bcc'] . '" is invalid', 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);
                }

            }
        } else {
            //TODO: add later again
            /*
            $arrAllReceiverAddresses[] = array(
                'main' => $arrMessageModel['specificAddress'],
                'bcc' => null,
                'data' =>[
                    'language' => $this->ls_language
                ]

		    );*/
        }

        //set default language if no language is set in data array
        if (empty($arrReceiverAddresses['data']) || empty($arrReceiverAddresses['data']['language'])) {
            $arrReceiverAddresses['data']['language'] = $this->ls_language;
        }

		return $arrAllReceiverAddresses;
	}

	protected function ls_replaceWildcards($arrMessageType, $data, $text) {
		/*
		 * Replace the counterNr wildcard
		 */
		if ($this->counterNr) {
			$text = preg_replace('/(&#35;&#35;counterNr&#35;&#35;)|(##counterNr##)/siU', $this->counterNr, $text);
		}

        //TODO: hook um wildcards zu ersetzen
        if (isset($GLOBALS['MERCONIS_HOOKS']['replaceWildcards']) && is_array($GLOBALS['MERCONIS_HOOKS']['replaceWildcards'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['replaceWildcards'] as $mccb) {
                $objMccb = \System::importStatic($mccb[0]);
                $text = $objMccb->{$mccb[1]}($arrMessageType, $text, $data, $this->additionalData);
            }
        }

		return $text;
	}
}

?>