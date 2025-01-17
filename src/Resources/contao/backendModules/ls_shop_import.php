<?php

namespace Merconis\Core;
use Contao\StringUtil;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_import extends \BackendModule {

	protected $strTemplate = 'be_import';
	
	
	protected function compile() {
		if (!$GLOBALS['TL_CONFIG']['ls_shop_standardProductImportFolder']) {
			return;
		}
		
		$obj_importController = new ls_shop_importController();
		
		// Create the file upload field
		$objFfl_importFileUpload = new \FileUpload(array(
			'name' => 'importFileUpload'
		));
		
		if (\Input::post('FORM_SUBMIT') == 'beModule_importUploadFile') {
			/*
			 * If a file has been uploaded, we remove all files that currently exist in the import folder
			 * and move the uploaded file into that folder
			 */
			
//			$objFfl_importFileUpload->validate();
			
			if (!$objFfl_importFileUpload->hasError()) {
				/*
				 * Remove all files from import folder
				 */
				$obj_importController->clearImportFolder();
				
				/*
				 * We only accept csv files and to achieve this, we have to temporarily override the
				 * current uploadTypes setting that has been globally defined by contao itself.
				 */
				$uploadTypesTemp = $GLOBALS['TL_CONFIG']['uploadTypes'];
				$GLOBALS['TL_CONFIG']['uploadTypes'] = 'csv';
				
				// Move the uploaded file into the import folder
				$objFfl_importFileUpload->uploadTo(ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImportFolder']));
				
				// Reset the uploadTypes
				$GLOBALS['TL_CONFIG']['uploadTypes'] = $uploadTypesTemp;
				$this->reload();
			} else {
				/*
				 * Currently we don't do anything special if the upload field does not validate
				 */
			}
		}
		
		$this->Template->request = StringUtil::ampersand(\Environment::get('request'), true);
		$this->Template->ffl_importFileUpload = $objFfl_importFileUpload->generateMarkup();
		$this->Template->arrCurrentlyExistingImportFileInfo = $_SESSION['lsShop']['importFileInfo'] ?? null;
		$this->Template->messages = \Message::generate(false, true);
	}
}
?>