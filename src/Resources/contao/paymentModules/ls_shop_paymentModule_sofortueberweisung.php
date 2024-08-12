<?php

namespace Merconis\Core;

	use Contao\Database;
    use Contao\Environment;
    use Contao\Input;
    use Contao\StringUtil;
    use Contao\System;

    class ls_shop_paymentModule_sofortueberweisung extends ls_shop_paymentModule_standard {
		public $arrCurrentSettings = array();
		
		protected $transactionID = '';
		
		public function initialize()
        {
            $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
			/*
			 * Including the sofortueberweisung SDK classes
			 */
			require_once($str_projectDir.'/vendor/leadingsystems/contao-merconis/src/Resources/contao/vendor/sofortueberweisung/sdk/payment/sofortLibSofortueberweisung.inc.php');
			require_once($str_projectDir.'/vendor/leadingsystems/contao-merconis/src/Resources/contao/vendor/sofortueberweisung/sdk/core/sofortLibNotification.inc.php');
			require_once($str_projectDir.'/vendor/leadingsystems/contao-merconis/src/Resources/contao/vendor/sofortueberweisung/sdk/core/sofortLibTransactionData.inc.php');
		}
		
		/*
		 * After the checkout the sofortueberweisung payment is processed.
		 */
		public function afterCheckoutFinish($orderIdInDb = 0, $order = array(), $afterCheckoutUrl = '', $oix = '') {
			// Reset the special payment info
            $session = System::getContainer()->get('merconis.session')->getSession();
            $session_lsShop =  $session->get('lsShop');
            $session_lsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = '';
            $session->set('lsShop', $session_lsShop);
			
			// if there are insufficient parameters the payment execution is aborted
			if (!$orderIdInDb || !is_array($order) || !count($order)) {
				// write an error message to the special payment info and log the error
                $session_lsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['paymentErrorAfterFinishedOrder'];
                $session->set('lsShop', $session_lsShop);
				$this->logPaymentError('ls_shop_paymentModule_sofortueberweisung::afterCheckoutFinish()', 'insufficient parameters given');
				return;
			}

			/*
			 * ----------
			 * Generating the after checkout url that can be used for sofortueberweisung
			 */
			// adding the oih as a parameter to the afterCheckoutUrl
			$afterCheckoutUrl = $afterCheckoutUrl.(preg_match('/\?/', $afterCheckoutUrl) ? '&' : '?').'oix='.$oix;
			
			// make an absolute URL
			if (!preg_match('@^https?://@i', $afterCheckoutUrl)) {
				$afterCheckoutUrl = Environment::get('base') . $afterCheckoutUrl;
			}
			/*
			 * ----------
			 */			
			
			/*
			 * Instantiating a new Sofortueberweisung, set the relevant parameters and send the request
			 */
			$Sofortueberweisung = new \Sofortueberweisung($this->arrCurrentSettings['sofortueberweisungConfigkey']);

			$Sofortueberweisung->setAmount(number_format($order['invoicedAmount'], 2, '.', ''));
			$Sofortueberweisung->setCurrencyCode($order['currency']);
			$Sofortueberweisung->setReason(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['paymentReason'], $order['orderNr']));

			$Sofortueberweisung->setSuccessUrl($afterCheckoutUrl, true);
			$Sofortueberweisung->setAbortUrl($afterCheckoutUrl.'&sue=aborted');
			$Sofortueberweisung->setNotificationUrl($afterCheckoutUrl.'&sue=notification');
			
			$Sofortueberweisung->setCustomerprotection($this->arrCurrentSettings['sofortueberweisungUseCustomerProtection'] ? true : false);
			
			$Sofortueberweisung->sendRequest();
			
			if($Sofortueberweisung->isError()) {
				// the sofortueberweisung API didn't accept the data therefore we write an error message to the special payment info and log the error
                $session_lsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['paymentErrorAfterFinishedOrder'];
                $session->set('lsShop', $session_lsShop);
				$sofortueberweisungError = $Sofortueberweisung->getError();
				$this->logPaymentError('ls_shop_paymentModule_sofortueberweisung::afterCheckoutFinish()', $sofortueberweisungError);
				return;
			} else {
				// Get the transaction id and update the payment info in the order record
				$this->transactionID = $Sofortueberweisung->getTransactionId();
				$this->updatePaymentInfo($orderIdInDb, array(
					'statusValue' => 'STARTED',
					'statusReason' => 'redirecting to &quot;Sofort.&quot;/&quot;Online Bank Transfer.&quot;',
					'statusModifiedTime' => time()
				));
				$this->update_fieldValue_inOrder($orderIdInDb, 'sofortbanking_currentStatus', 'STARTED');

				// get the payment url and redirect the customer
				$paymentUrl = $Sofortueberweisung->getPaymentUrl();
				header('Location: '.$paymentUrl);
				exit;
			}
		}

		/*
		 * This function gets called during the creation of the order array which happens before the sofortueberweisung
		 * transaction is even started, so we return a default transaction array.
		 */
		public function getPaymentInfo() {
			$arrPaymentInfo = array(
				'transactionID' => 'unknown',
				'status' => array(
					array(
						'statusValue' => 'PREPARING TRANSACTION',
						'statusReason' => '&quot;Sofort.&quot;/&quot;Online Bank Transfer.&quot; used for checkout',
						'statusModifiedTime' => time()
					)
				),
				'details' => array(
					'amount' => '',
					'amountRefunded' => '',
					'customerProtection' => '',
					'languageCode' => '',
					'currency' => '',
					'projectID' => '',
					/* *
					'recipientHolder' => '',
					'recipientAccountNumber' => '',
					'recipientBankCode' => '',
					'recipientCountryCode' => '',
					'recipientBankName' => '',
					'recipientBic' => '',
					'recipientIban' => '',
					'senderHolder' => '',
					'senderAccountNumber' => '',
					'senderBankCode' => '',
					'senderCountryCode' => '',
					'senderBankName' => '',
					'senderBic' => '',
					'senderIban' => ''
					/* */
				)
			);
			return serialize($arrPaymentInfo);
		}
		
		/*
		 * This function reads the current transaction status and writes it to the order record
		 * if both the order id and the transaction id are available. If the optional third
		 * status parameter is given, the current transaction status is not actually read but
		 * instead the given status is written to the order record, therefore in this case
		 * the transaction id does not have to be available.
		 */
		public function updatePaymentInfo($orderIdInDb = 0, $status = false) {
			/*
			 * Abort this function if we have no order id which means that we wouldn't know
			 * which order record to update or if we neither have the transaction id to get
			 * the current status or a fixed status to write.
			 */
			if (!$orderIdInDb || (!$this->transactionID && !$status)) {
				return false;
			}
			
			$arrOrder = ls_shop_generalHelper::getOrder($orderIdInDb, 'id', true);

			/*
			 * Abort if we could not retrieve correct order data
			 */
			if (!is_array($arrOrder)) {
				return false;
			}
			
			/*
			 * The payment information is stored serialized in the order's paymentMethod_moduleReturnData field
			 * and we retrieve the information here
			 */
			$arrPaymentInfo = StringUtil::deserialize($arrOrder['paymentMethod_moduleReturnData']);
			
			if ($arrPaymentInfo['transactionID'] == 'unknown') {
				// If the transaction id is still unknown we overwrite it with the transaction id that should be available by now
				$arrPaymentInfo['transactionID'] = $this->transactionID;
			} else if ($arrPaymentInfo['transactionID'] != $this->transactionID) {
				// If we already have written a transaction id to the payment info but the current transaction id differs, we note this as a status update
				$arrPaymentInfo['status'][] = array(
					'statusValue' => 'TRANSACTION ID CHANGED TO '.($this->transactionID ? $this->transactionID : 'unknown'),
					'statusReason' => '',
					'statusModifiedTime' => time()
				);

				$this->update_fieldValue_inOrder($orderIdInDb, 'sofortbanking_currentStatus', 'TRANSACTION ID CHANGED');
			}
			
			if (!$status) {
				/*
				 * If there is no fixed status given as a parameter we have a transaction id that we use
				 * to request transaction data
				 */
				$SofortLibTransactionData = new \SofortLibTransactionData($this->arrCurrentSettings['sofortueberweisungConfigkey']);
				$SofortLibTransactionData->addTransaction($this->transactionID);
				$SofortLibTransactionData->sendRequest();
				
				if ($SofortLibTransactionData->isError()) {
					// Write an error status if the transaction has an error
					$arrPaymentInfo['status'][] = array(
						'statusValue' => 'ERROR',
						'statusReason' => $SofortLibTransactionData->getError(),
						'statusModifiedTime' => time()
					);
					$this->update_fieldValue_inOrder($orderIdInDb, 'sofortbanking_currentStatus', 'ERROR');
				} else {
					// Write the currently retrieved status
					$arrPaymentInfo['status'][] = array(
						'statusValue' => $SofortLibTransactionData->getStatus(),
						'statusReason' => $SofortLibTransactionData->getStatusReason(),
						'statusModifiedTime' => strtotime($SofortLibTransactionData->getStatusModifiedTime())
					);
					$this->update_fieldValue_inOrder($orderIdInDb, 'sofortbanking_currentStatus', $SofortLibTransactionData->getStatus());
					
					// Write/Update further transaction details
					$arrPaymentInfo['details'] = array(
						'amount' => $SofortLibTransactionData->getAmount(),
						'amountRefunded' => $SofortLibTransactionData->getAmountRefunded(),
						'customerProtection' => $SofortLibTransactionData->getConsumerProtection() ? 'yes' : 'no',
						'languageCode' => $SofortLibTransactionData->getLanguageCode(),
						'currency' => $SofortLibTransactionData->getCurrency(),
						'projectID' => $SofortLibTransactionData->getProjectId(),
						/* *
						'recipientHolder' => $SofortLibTransactionData->getRecipientHolder(),
						'recipientAccountNumber' => $SofortLibTransactionData->getRecipientAccountNumber(),
						'recipientBankCode' => $SofortLibTransactionData->getRecipientBankCode(),
						'recipientCountryCode' => $SofortLibTransactionData->getRecipientCountryCode(),
						'recipientBankName' => $SofortLibTransactionData->getRecipientBankName(),
						'recipientBic' => $SofortLibTransactionData->getRecipientBic(),
						'recipientIban' => $SofortLibTransactionData->getRecipientIban(),
						'senderHolder' => $SofortLibTransactionData->getSenderHolder(),
						'senderAccountNumber' => $SofortLibTransactionData->getSenderAccountNumber(),
						'senderBankCode' => $SofortLibTransactionData->getSenderBankCode(),
						'senderCountryCode' => $SofortLibTransactionData->getSenderCountryCode(),
						'senderBankName' => $SofortLibTransactionData->getSenderBankName(),
						'senderBic' => $SofortLibTransactionData->getSenderBic(),
						'senderIban' => $SofortLibTransactionData->getSenderIban()
						/* */
					);
				}
			} else {
				// Write the given fixed status
				$arrPaymentInfo['status'][] = $status;
			}
			
			// Update the order record with the changed payment information
			Database::getInstance()->prepare("
				UPDATE	`tl_ls_shop_orders`
				SET		`paymentMethod_moduleReturnData` = ?
				WHERE	`id` = ?
			")
			->limit(1)
			->execute(serialize($arrPaymentInfo), $orderIdInDb);
			
			// Return the payment info, which might not be necessary
			return $arrPaymentInfo;
		}

		/*
		 * This payment function is called on the after checkout page if an oix is given there.
		 * Depending on the sofortueberweisung GET parameter (sue) this function can handle notification
		 * calls or the call of the abort url.
		 */
		public function onAfterCheckoutPage($order = array()) {
			if (Input::get('sue')) {
				switch (Input::get('sue')) {
					case 'aborted':
						// write the error message to the special payment info and update the payment status in the order record
                        $session = System::getContainer()->get('merconis.session')->getSession();
                        $session_lsShop =  $session->get('lsShop');
                        $session_lsShop['specialInfoForPaymentMethodAfterCheckoutFinish'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['paymentErrorAfterFinishedOrder'];
                        $session->set('lsShop', $session_lsShop);

						$this->updatePaymentInfo($order['id'], array(
							'statusValue' => 'ABORTED',
							'statusReason' => 'Buyer aborted the transaction',
							'statusModifiedTime' => time()
						));

						$this->update_fieldValue_inOrder($order['id'], 'sofortbanking_currentStatus', 'ABORTED');
						
						/*
						 * redirect in order to get rid of the "aborted" value for the sue parameter which would cause
						 * multiple abortions to be written to the order record if the user would reload the page with the url
						 */
						$this->redirect(preg_replace('/sue=aborted/', 'sue=failed', Environment::get('request')));
						break;
					
					case 'notification':
						/*
						 * Read the transaction id from the notification and then update the payment info
						 */
						$SofortLib_Notification = new \SofortLibNotification();
						$this->transactionID = $SofortLib_Notification->getNotification(file_get_contents('php://input'));
						$this->updatePaymentInfo($order['id']);
						break;
				}
			}
		}
		
		public function checkoutFinishAllowed() {
			/*
			 * With sofortueberweisung, the checkout is always allowed, because we want the order to be finished
			 * bindlingly before we redirect the user to sofortueberweisung. If the payment is not possible
			 * it is to late to change the order and the user must see a message telling him to contact the
			 * shop operator in order to settle the issue.
			 * 
			 * The only other option (which we do not use) would be to perform the sofortueberweisung payment before the order
			 * is finished which is extremely complicated because in this case we would have to block
			 * the item's stock and set it free if the sofortueberweisung payment can not be finished.
			 */
			return true;
		}
		
		public function showPaymentDetailsInBackendOrderDetailView($arrOrder = array(), $paymentMethod_moduleReturnData = '') {
			if (!count($arrOrder) || !$paymentMethod_moduleReturnData) {
				return null;
			}
			
			$outputValue = '';
			$paymentMethod_moduleReturnData = StringUtil::deserialize($paymentMethod_moduleReturnData);
			ob_start();
			?>
			<div class="paymentDetails sofortbanking">
				<h2>
					<a href="https://www.sofort.com/" target="_blank">
						<img src="https://images.sofort.com/de/su/75x28.png" alt="SOFORT Überweisung - Einfach, Schnell, Sicher" height="28" width="75">
					</a>
					<?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['headlineBackendDetailsInfo']; ?>
				</h2>
				<div class="content">
					<div class="transactionID">
						<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['transactionID']; ?>: </span>
						<span class="value"><?php echo $paymentMethod_moduleReturnData['transactionID']; ?></span>
					</div>
					<div class="status">
						<?php
							foreach ($paymentMethod_moduleReturnData['status'] as $arrStatus) {
								?>
								<div class="value">
									<div class="statusModifiedTime"><?php echo date($GLOBALS['TL_CONFIG']['dateFormat'].', H:i:s', $arrStatus['statusModifiedTime']); ?></div>
									<div class="statusValue"><?php echo strtoupper($arrStatus['statusValue']); ?></div>
									<div class="statusReason"><?php echo $arrStatus['statusReason']; ?></div>
								</div>
								<?php
							}
						?>
					</div>
					<div class="details">
						<div class="detailItem">
							<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['amount']; ?>: </span>
							<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['amount']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['amountRefunded']; ?>: </span>
							<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['amountRefunded']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['customerProtection']; ?>: </span>
							<span class="value"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung'][$paymentMethod_moduleReturnData['details']['customerProtection']]; ?></span>
						</div>
						<div class="detailItem">
							<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['languageCode']; ?>: </span>
							<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['languageCode']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['currency']; ?>: </span>
							<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['currency']; ?></span>
						</div>
						<div class="detailItem">
							<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['projectID']; ?>: </span>
							<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['projectID']; ?></span>
						</div>
						<?php if (false) { ?>
							<div class="senderHeadline">
								<span><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['sender']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['senderHolder']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['senderHolder']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['senderAccountNumber']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['senderAccountNumber']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['senderBankCode']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['senderBankCode']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['senderCountryCode']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['senderCountryCode']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['senderBankName']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['senderBankName']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['senderBic']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['senderBic']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['senderIban']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['senderIban']; ?></span>
							</div>

							<div class="recipientHeadline">
								<span><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['recipient']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['recipientHolder']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['recipientHolder']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['recipientAccountNumber']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['recipientAccountNumber']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['recipientBankCode']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['recipientBankCode']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['recipientCountryCode']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['recipientCountryCode']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['recipientBankName']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['recipientBankName']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['recipientBic']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['recipientBic']; ?></span>
							</div>
							<div class="detailItem">
								<span class="label"><?php echo $GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['recipientIban']; ?>: </span>
								<span class="value"><?php echo $paymentMethod_moduleReturnData['details']['recipientIban']; ?></span>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
			
			<?php
			$outputValue = ob_get_clean();
			return $outputValue;
		}

		public function showPaymentStatusInOverview($arrOrder = array(), $paymentMethod_moduleReturnData = '') {
			if (!count($arrOrder) || !$paymentMethod_moduleReturnData) {
				return null;
			}
			
			$outputValue = '';
			$paymentMethod_moduleReturnData = StringUtil::deserialize($paymentMethod_moduleReturnData);
			
			$arrLastStatus = $paymentMethod_moduleReturnData['status'][count($paymentMethod_moduleReturnData['status']) - 1];
			
			ob_start();
			?>
			<div class="paymentStatusInOverview sofortbanking <?php echo strtolower(preg_replace('/\s+/', '-', $arrLastStatus['statusValue'])); ?>">
				<img src="https://images.sofort.com/de/su/75x28.png" alt="SOFORT Überweisung - Einfach, Schnell, Sicher" height="28" width="75">
				<span class="status"><?php echo strtoupper($arrLastStatus['statusValue']); ?></span>
			</div>
			<?php
			$outputValue = ob_get_clean();
			return $outputValue;
		}
		
		public function modifyConfirmOrderForm($form = '') {
			$outputToAdd = '<div class="confirmCheckoutMessageSofortbanking"><img class="sofortbankingLogo" src="https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/pay_now/descriptive/pink.svg" alt="'.$GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['slogan'].'" height="38" width="100">'.$GLOBALS['TL_LANG']['MOD']['ls_shop']['paymentMethods']['sofortueberweisung']['confirmCheckoutMessage'].'</div>';
			$form = $form.$outputToAdd;
			return $form;
		}
	}
?>