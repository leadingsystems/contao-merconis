<?php

namespace Merconis\Core;

use Contao\DataContainer;
use Contao\Input;
use Contao\System;

class ls_shop_ajaxController
{
    /*
     * contao.hook: executePreActions
     */
	public function executePreActions($strAction) {
		switch ($strAction) {
			case 'ls_shop_loadCorrespondingAttributeValuesAsOptions':
				$attributeValuesOptions = ls_shop_generalHelper::getAttributeValuesAsOptions(Input::post('attributeID'));
				$arrResponse = array('attributeValuesOptions' => $attributeValuesOptions);
				echo json_encode($arrResponse);
				exit;
				break;

			case 'ls_shop_productSelection::loadProduct':
				System::loadLanguageFile('be_productSearch');
				if (!Input::post('productID')) {
					$arrResponse = array('html' => '');
				} else {
					$objProductOutput = new ls_shop_productOutput(Input::post('productID'), '', 'template_productBackendOverview_03');
					$arrResponse = array('html' => $objProductOutput->parseOutput());					
				}
				echo json_encode($arrResponse);
				exit;
				break;

			case 'toggleLsShopMainLanguagePagetree':
				$this->strAjaxId = preg_replace('/.*_([0-9a-zA-Z]+)$/i', '$1', Input::post('id'));
				$this->strAjaxKey = str_replace('_' . $this->strAjaxId, '', Input::post('id'));

				if (Input::get('act') == 'editAll')
				{
					$this->strAjaxKey = preg_replace('/(.*)_[0-9a-zA-Z]+$/i', '$1', $this->strAjaxKey);
					$this->strAjaxName = preg_replace('/.*_([0-9a-zA-Z]+)$/i', '$1', Input::post('name'));
				}

                /*
                 * @toDo "Contao\Session" class has been deprecated and will no longer work in Contao 5.0. Use the session service instead.
                 */
				$nodes = \Session::getInstance()->get($this->strAjaxKey);
				$nodes[$this->strAjaxId] = intval(Input::post('state'));
				\Session::getInstance()->set($this->strAjaxKey, $nodes);
				exit; break;

			case 'loadLsShopMainLanguagePagetree':
				$this->strAjaxId = preg_replace('/.*_([0-9a-zA-Z]+)$/i', '$1', Input::post('id'));
				$this->strAjaxKey = str_replace('_' . $this->strAjaxId, '', Input::post('id'));

				if (Input::get('act') == 'editAll')
				{
					$this->strAjaxKey = preg_replace('/(.*)_[0-9a-zA-Z]+$/i', '$1', $this->strAjaxKey);
					$this->strAjaxName = preg_replace('/.*_([0-9a-zA-Z]+)$/i', '$1', Input::post('name'));
				}

                /*
                 * @toDo "Contao\Session" class has been deprecated and will no longer work in Contao 5.0. Use the session service instead.
                 */
				$nodes = \Session::getInstance()->get($this->strAjaxKey);
				$nodes[$this->strAjaxId] = intval(Input::post('state'));
				\Session::getInstance()->set($this->strAjaxKey, $nodes);
				break;
				
			case 'sendOrderMessage':
				if (!Input::post('orderID') || !Input::post('messageTypeID')) {
					break;
				}
				$objOrderMessages = new ls_shop_orderMessages(Input::post('orderID'), Input::post('messageTypeID'), 'id');
				$objOrderMessages->sendMessages();
				
				/*
				 * Generate the messageType button which is needed as the return value
				 */
				$arrOrder = ls_shop_generalHelper::getOrder(Input::post('orderID'), 'id', true);
				$arrMessageTypes = ls_shop_generalHelper::getMessageTypesForOrderOverview($arrOrder, true);
				echo $arrMessageTypes[Input::post('messageTypeID')]['button'];
				exit;
				break;
				
			case 'callImporterFunction':
				$obj_importController = System::importStatic('Merconis\Core\ls_shop_importController');
				switch (Input::post('what')) {
					case 'importFile':
						$obj_importController->importFile();
						
						$arrResponse = array(
							'value' => $obj_importController->getImportFileInfoMinimal(),
							'success' => true
						);
						
						echo json_encode($arrResponse);
						break;

					case 'validateFile':
						$obj_importController->validateFile();
						$arrResponse = array(
							'value' => $obj_importController->getImportFileInfoMinimal(),
							'success' => true
						);
						
						echo json_encode($arrResponse);
						break;

					case 'getConfiguration':
						$arrResponse = array(
							'value' => $obj_importController->getConfiguration(),
							'success' => true
						);
						
						echo json_encode($arrResponse);
						break;

					case 'deleteFile':
						$obj_importController->clearImportFolder();
						
						$arrResponse = array(
							'value' => '',
							'success' => true
						);
						
						echo json_encode($arrResponse);
						break;
				}
				exit;
				break;

			default:
				break;
		}		
	}

	/*
	 * contao.hook: executePostActions
	 */
	public function executePostActions($strAction, DataContainer $dc) {
		switch ($strAction) {
			case 'ls_shop_productSelection::loadProduct':				
				break;
				
			case 'ls_shop_loadCorrespondingAttributeValuesAsOptions':
				break;
				
			default:
				break;
		}
	}
}