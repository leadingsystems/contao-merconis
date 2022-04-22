<?php

namespace Merconis\Core;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_orderMessages
{
	protected $orderID = null;
	protected $identificationToken = null;
	protected $findBy = 'alias';
	protected $ls_language = 'en';
	
	protected $arrOrder = null;
	protected $arrMessageModels = null;
	protected $arrMessageTypes = null;
	
	protected $counterNr = null;
	
	public function __construct($orderID = null, $identificationToken = null, $findBy = null, $language = null, $blnForceOrderRefresh = false) {
		/** @var \PageModel $objPage */
		global $objPage;
		
		$this->orderID = $orderID;
		$this->identificationToken = $identificationToken;
		$this->findBy = $findBy ? $findBy : $this->findBy;
		
		$this->arrOrder = $this->orderID ? ls_shop_generalHelper::getOrder($this->orderID, 'id', $blnForceOrderRefresh) : null;
		
		/*
		 * If no language is given as an attribute on instantiation, $objPage->language will be used. If $objPage->language
		 * is not available (backend call), the customerLanguage saved in the order will be used. If this language information
		 * also doesn't exist (which should never be the case) english (defined as default in this class) is used.
		 * 
		 * This way a frontend call without an explicitly given language should automatically send messages in the current frontend language,
		 * and a backend call without an explicitly given language should automatically send messages in the order's customerLanguage
		 * and therefore it's probably not very often required to give a language as an attribute on instantiation. An example when
		 * it's required to give a language would be the order notice because in this case a message to the shop admin in the shop language
		 * should be sent from a frontend call regardless of what the current frontend language is.
		 */
		$this->ls_language = $language ? $language : (isset($objPage) && is_object($objPage) ? $objPage->language : (isset($this->arrOrder['customerLanguage']) && $this->arrOrder['customerLanguage'] ? $this->arrOrder['customerLanguage'] : $this->ls_language));
		
		$this->getMessageModels();
		
		$this->getReceiverAddresses();
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
			
			/*
			 * If the messageType has already be sent for the current order,
			 * it has to be skipped
			 */
			if (in_array($arrMessageType['id'], $this->arrOrder['messageTypesSent'])) {
				continue;
			}
			
			/*
			 * If the message types to use are the ones which have to be sent on status change
			 * (onStatusChangeImmediately or onStatusChangeCronDaily/onStatusChangeCronHourly), then their status correlation
			 * has to be considered
			 */
			if ($this->findBy == 'sendWhen' && ($this->identificationToken == 'onStatusChangeImmediately' || $this->identificationToken == 'onStatusChangeCronDaily' || $this->identificationToken == 'onStatusChangeCronHourly')) {
				if (!$this->checkIfMessageTypeStatusCorrelationFitsTheOrder($arrMessageType)) {
					continue;
				}
			}

			$this->arrMessageTypes[$objMessageTypes->id] = $arrMessageType;
		}
	}

	/*
	 * Check whether the different status types' values of the order fit the status correlation values
	 * of the message type. All status types for which a correlation is defined in the message type
	 * need to fit. If one of them doesn't, this function returns false.
	 */
	protected function checkIfMessageTypeStatusCorrelationFitsTheOrder($arrMessageType = null) {
		if (!$arrMessageType) {
			return false;
		}

		$blnAtLeastOneStatusCorrelationUsed = false;
		for ($i = 1; $i <= 5; $i++) {
			$statusNr = strlen($i) < 2 ? '0'.$i : $i;
			if ($arrMessageType['useStatusCorrelation'.$statusNr]) {
				$blnAtLeastOneStatusCorrelationUsed = true;
				if ($this->arrOrder['status'.$statusNr] != $arrMessageType['statusCorrelation'.$statusNr]) {
					return false;
				}
			}
		}

		if ($arrMessageType['usePaymentStatusCorrelation']) {
			$blnAtLeastOneStatusCorrelationUsed = true;
			if ($this->arrOrder[$arrMessageType['paymentStatusCorrelation_paymentProvider'].'_currentStatus'] != $arrMessageType['paymentStatusCorrelation_statusValue']) {
				return false;
			}
		}
		
		if (!$blnAtLeastOneStatusCorrelationUsed) {
			return false;
		}

		return true;
	}
	
	public function getMessageModels() {
		$arrMessageModels = array();
		
		if (!$this->orderID || !$this->identificationToken || ($this->findBy != 'id' && $this->findBy != 'alias' && $this->findBy != 'sendWhen')) {
			return null;
		}
		
		$this->getMessageTypes();
		
		foreach ($this->arrMessageTypes as $messageTypeID => $arrMessageType) {
			$objMessageModels = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_ls_shop_message_model`
				WHERE		`pid` = ?
					AND		`member_group` LIKE ?
					AND		`published` = 1
			")
			->execute($messageTypeID, '%%"'.$this->arrOrder['memberGroupInfo_id'].'"%');
			
			if (!$objMessageModels->numRows) {
				continue;
			}
			
			$arrMessageModels[$objMessageModels->id] = $objMessageModels->row();
			$arrMessageModels[$objMessageModels->id]['multilanguage'] = ls_shop_languageHelper::getMultiLanguage($objMessageModels->id, "tl_ls_shop_message_model_languages", array('subject', 'senderName', 'content_html', 'content_rawtext', 'attachments', 'dynamicAttachments'), array($this->ls_language));
		}
		
		$this->arrMessageModels = $arrMessageModels;
	}
	
	public function sendMessages() {
		if (!is_array($this->arrMessageModels)) {
			return false;
		}
		\System::loadLanguageFile('default', $this->arrOrder['customerLanguage'], true);

		$currentMessageTypeID = null;
		$lastMessageTypeID = null;
		
		foreach ($this->arrMessageModels as $arrMessageModel) {
			
			if (isset($GLOBALS['MERCONIS_HOOKS']['beforeSendingOrderMessage']) && is_array($GLOBALS['MERCONIS_HOOKS']['beforeSendingOrderMessage'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['beforeSendingOrderMessage'] as $mccb) {
					$objMccb = \System::importStatic($mccb[0]);
					$arrMessageModel = $calculatedScaledPrice = $objMccb->{$mccb[1]}($arrMessageModel, $this->arrOrder);
					
					if (!is_array($arrMessageModel)) {
						continue 2;
					}
				}
			}

			$currentMessageTypeID = $arrMessageModel['pid'];
			
			$arrReceiverAddresses = $this->getReceiverAddresses($arrMessageModel);
			
			if ($arrReceiverAddresses === null)	{
				// no receiver address could be determined therefore this message model is being skipped
				continue;
			}
			
			if (!\Validator::isEmail(\Idna::encodeEmail($arrMessageModel['senderAddress']))) {
				// log an error if the sender address is invalid and then skip this message model
				\System::log('MERCONIS: message using message model with id '.$arrMessageModel['id'].' and order with order nr '.$this->arrOrder['orderNr'].' could not be sent because sender address "'.$arrMessageModel['senderAddress'].'" is invalid', 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);
				continue;
			}
			
			if (!$arrMessageModel['useHTML'] && !$arrMessageModel['useRawtext']) {
				// log an error if neither useHTML nor useRawtext is checked and then skip this message model
				\System::log('MERCONIS: message using message model with id '.$arrMessageModel['id'].' and order with order nr '.$this->arrOrder['orderNr'].' could not be sent because neither the usage of HTML nor the usage of rawtext is selected', 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);
				continue;
			}
						
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
					$objTemplate_emailHTML->content = \Controller::replaceInsertTags($this->ls_replaceWildcards(\Controller::replaceInsertTags($arrMessageModel['multilanguage']['content_html'])));
				} else {
					$objTemplate_emailHTML->content = \Controller::replaceInsertTags($this->ls_replaceWildcards(\StringUtil::insertTagToSrc(\Controller::replaceInsertTags($arrMessageModel['multilanguage']['content_html']))));
				}
				$objTemplate_emailHTML->arrOrder = $this->arrOrder;
				$objTemplate_emailHTML->arrMessageModel = $arrMessageModel;
				$objTemplate_emailHTML->counterNr = $this->counterNr;
			}
			
			if ($arrMessageModel['useRawtext']) {
				$objTemplate_rawtext = new \FrontendTemplate($arrMessageModel['template_rawtext']);
				$objTemplate_rawtext->content = \Controller::replaceInsertTags($this->ls_replaceWildcards(\Controller::replaceInsertTags($arrMessageModel['multilanguage']['content_rawtext'])));
				$objTemplate_rawtext->arrOrder = $this->arrOrder;
				$objTemplate_rawtext->arrMessageModel = $arrMessageModel;
				$objTemplate_rawtext->counterNr = $this->counterNr;
			}
			
			$arrMessageToSendAndSave = array(
				'tstamp' => time(),
				'orderID' => $this->orderID,
				'orderNr' => $this->arrOrder['orderNr'],
				'messageTypeAlias' => $this->arrMessageTypes[$arrMessageModel['pid']]['alias'],
				'messageTypeID' => $currentMessageTypeID,
				'messageModelID' => $arrMessageModel['id'],
				'counterNr' => $this->counterNr,
				'senderName' => html_entity_decode($arrMessageModel['multilanguage']['senderName']),
				'senderAddress' => $arrMessageModel['senderAddress'],
				'receiverMainAddress' => $arrReceiverAddresses['main'],
				'receiverBccAddress' => $arrReceiverAddresses['bcc'],
				'subject' => html_entity_decode($this->ls_replaceWildcards(\Controller::replaceInsertTags($arrMessageModel['multilanguage']['subject']))),
				'bodyHTML' => $arrMessageModel['useHTML'] ? $objTemplate_emailHTML->parse() : '',
				'bodyRawtext' => $arrMessageModel['useRawtext'] ? $objTemplate_rawtext->parse() : '',
				'dynamicPdfAttachmentPaths' => deserialize($arrMessageModel['multilanguage']['dynamicAttachments']),
				'attachmentPaths' => deserialize($arrMessageModel['multilanguage']['attachments'])
			);
				
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
					if (file_exists(TL_ROOT.'/'.$strDynamicAttachmentFile)) {
						require_once(TL_ROOT.'/'.$strDynamicAttachmentFile);

						// The class name of the dynamicAttachmentFile must match it's filename without the suffix
						$dynamicAttachmentClassName = 'Merconis\Core\\'.preg_replace('/(^.*\/)([^\/\.]*)(\.php$)/', '\\2', $strDynamicAttachmentFile);
	
						/*
						 * The __constructor function of the dynamicAttachmentClass has to take the order array as an argument
						 * and it has to return the file path of the saved file so that it can be attached to the email object
						 * directly.
						 */
						$objDynamicAttachment = new $dynamicAttachmentClassName($this->arrOrder, $this->counterNr);
						$dynamicAttachmentSavedFilename = $objDynamicAttachment->parse();
						
						if ($dynamicAttachmentSavedFilename && file_exists(TL_ROOT.'/'.$dynamicAttachmentSavedFilename)) {
							$objEmail->attachFile(TL_ROOT.'/'.$dynamicAttachmentSavedFilename);
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
					if ($strAttachment && file_exists(TL_ROOT.'/'.$strAttachment)) {
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
				$objEmail->sendTo($arrMessageToSendAndSave['receiverMainAddress']);
				\System::log('MERCONIS: message sent for order with order nr '.$this->arrOrder['orderNr'].' using message model with id '.$arrMessageModel['id'], 'MERCONIS MESSAGES', TL_MERCONIS_MESSAGES);
			} catch (\Exception $e) {
				\System::log('MERCONIS: Swift Exception, message "'.$this->arrMessageTypes[$arrMessageModel['pid']]['alias'].'" for order with order nr '.$this->arrOrder['orderNr'].' using message model with id '.$arrMessageModel['id'].' could not be sent ('.standardize($e->getMessage()).')', 'MERCONIS MESSAGES', TL_MERCONIS_MESSAGES);
			}
			
			$this->writeDispatchDate($currentMessageTypeID);
			
			$this->saveSentMessage($arrMessageToSendAndSave);
		}
		\System::loadLanguageFile('default', $GLOBALS['TL_LANGUAGE'], true);
	}

	protected function saveSentMessage($arrMessageToSave) {
		$arrMessageToSave['dynamicPdfAttachmentPaths'] = serialize($arrMessageToSave['dynamicPdfAttachmentPaths']);
		$arrMessageToSave['attachmentPaths'] = serialize($arrMessageToSave['attachmentPaths']);
		
		\Database::getInstance()->prepare("
			INSERT INTO	`tl_ls_shop_messages_sent`
			SET			`tstamp` = ?,
						`orderID` = ?,
						`orderNr` = ?,
						`messageTypeAlias` = ?,
						`messageTypeID` = ?,
						`messageModelID` = ?,
						`counterNr` = ?,
						`senderName` = ?,
						`senderAddress` = ?,
						`receiverMainAddress` = ?,
						`receiverBccAddress` = ?,
						`subject` = ?,
						`bodyHTML` = ?,
						`bodyRawtext` = ?,
						`dynamicPdfAttachmentPaths` = ?,
						`attachmentPaths` = ?
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
	
	protected function getReceiverAddresses($arrMessageModel = null) {
		if (!$arrMessageModel) {
			return null;
		}
		
		$arrReceiverAddresses = array(
			'main' => null,
			'bcc' => null
		);
		
		// use customer address no. 1 if it can be determined
		if ($arrMessageModel['sendToCustomerAddress1'] && isset($this->arrOrder['customerData'][$arrMessageModel['customerDataType1']][$arrMessageModel['customerDataField1']]) && $this->arrOrder['customerData'][$arrMessageModel['customerDataType1']][$arrMessageModel['customerDataField1']]) {
			$arrReceiverAddresses['main'] = $this->arrOrder['customerData'][$arrMessageModel['customerDataType1']][$arrMessageModel['customerDataField1']];
		}
		
		// overwrite the current main address with customer address no. 2 if it can be determined
		if ($arrMessageModel['sendToCustomerAddress2'] && isset($this->arrOrder['customerData'][$arrMessageModel['customerDataType2']][$arrMessageModel['customerDataField2']]) && $this->arrOrder['customerData'][$arrMessageModel['customerDataType2']][$arrMessageModel['customerDataField2']]) {
			$arrReceiverAddresses['main'] = $this->arrOrder['customerData'][$arrMessageModel['customerDataType1']][$arrMessageModel['customerDataField2']];
		}
		
		if ($arrMessageModel['sendToSpecificAddress'] && $arrMessageModel['specificAddress']) {
			if (!$arrReceiverAddresses['main']) {
				// set the main address to the specific address if this one is given and the main address is not yet set
				$arrReceiverAddresses['main'] = $arrMessageModel['specificAddress'];
			} else {
				// set the bcc address to the specific address if it is given and the main address is already set
				$arrReceiverAddresses['bcc'] = $arrMessageModel['specificAddress'];
			}
		}
		
		$blnAddressInvalid = false;
		
		if ($arrReceiverAddresses['main'] && !\Validator::isEmail(\Idna::encodeEmail($arrReceiverAddresses['main']))) {
			// log an error if the address is invalid
			\System::log('MERCONIS: message using message model with id '.$arrMessageModel['id'].' and order with order nr '.$this->arrOrder['orderNr'].' could not be sent because main receiver address "'.$arrReceiverAddresses['main'].'" is invalid', 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);
			$blnAddressInvalid = true;
		}
		
		if ($arrReceiverAddresses['bcc'] && !\Validator::isEmail(\Idna::encodeEmail($arrReceiverAddresses['bcc']))) {
			// log an error if the address is invalid
			\System::log('MERCONIS: message using message model with id '.$arrMessageModel['id'].' and order with order nr '.$this->arrOrder['orderNr'].' could not be sent because BCC receiver address "'.$arrReceiverAddresses['bcc'].'" is invalid', 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);
			$blnAddressInvalid = true;
		}
		
		// return null if the main address is not set, otherwise return the address array
		return $arrReceiverAddresses['main'] && !$blnAddressInvalid ? $arrReceiverAddresses : null;
	}

	protected function ls_replaceWildcards($text) {
		/*
		 * Replace the counterNr wildcard
		 */
		if ($this->counterNr) {
			$text = preg_replace('/(&#35;&#35;counterNr&#35;&#35;)|(##counterNr##)/siU', $this->counterNr, $text);
		}

        $text = ls_shop_generalHelper::ls_replaceOrderWildcards($text, $this->arrOrder);

		return $text;
	}
}

?>