<?php

namespace Merconis\Core;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\Date;
use Contao\File;
use Contao\FilesModel;
use Contao\Folder;
use Contao\System;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_importController
{

	protected $allowedImportFileVersions = array('1.4');
	
	protected $dataRowTypesInOrderToProcess = array();
	
	protected $importFileHandle = null;
	protected $intNumMaxRowsToProcessInOneRound = 100;

	private $bln_importFileHasRowForVersionAndSpecialChars = true;

	
	public function __construct($bln_importFileHasRowForVersionAndSpecialChars = true) {
	    $this->bln_importFileHasRowForVersionAndSpecialChars = (bool) $bln_importFileHasRowForVersionAndSpecialChars;
	    
		setlocale(LC_ALL, (isset($GLOBALS['TL_CONFIG']['ls_shop_importCsvLocale']) && $GLOBALS['TL_CONFIG']['ls_shop_importCsvLocale'] ? $GLOBALS['TL_CONFIG']['ls_shop_importCsvLocale'] : 'en_US.utf-8'));

		$this->dataRowTypesInOrderToProcess = ls_shop_productManagementApiHelper::$dataRowTypesInOrderToProcess;
		$this->intNumMaxRowsToProcessInOneRound = isset($GLOBALS['TL_CONFIG']['ls_shop_numMaxImportRecordsPerRound']) && $GLOBALS['TL_CONFIG']['ls_shop_numMaxImportRecordsPerRound'] ? $GLOBALS['TL_CONFIG']['ls_shop_numMaxImportRecordsPerRound'] : $this->intNumMaxRowsToProcessInOneRound;
		
		$this->getCurrentlyExistingImportFileInfo();
	}

	public function __destruct() {
		$this->closeImportFile();
	}

	public function getConfiguration() {
		return array(
			'lang' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['importer'],
			'fileInfo' => $this->getImportFileInfoMinimal()
		);
	}
	
	/*
	 * This function returns the file information that is important in js
	 * and omits unnecessary information to prevent unnecessary traffic.
	 * 
	 * Important note: Although it seems to be much easier to just make
	 * a duplicate of $_SESSION['lsShop']['importFileInfo'] and then unset
	 * the unnecessary information we don't go this way on purpose because
	 * $_SESSION['lsShop']['importFileInfo'] could hold a huge amount of data and therefore
	 * it might cause problems with the memory limit if we duplicate the data
	 * even if it's just for a short moment.
	 */
	public function getImportFileInfoMinimal() {
		return array(
			'hash' => $_SESSION['lsShop']['importFileInfo']['hash'],
			'name' => $_SESSION['lsShop']['importFileInfo']['name'],
			'fullFilename' => $_SESSION['lsShop']['importFileInfo']['fullFilename'],
			'date' => $_SESSION['lsShop']['importFileInfo']['date'],
			'size' => $_SESSION['lsShop']['importFileInfo']['size'],
			'status' => $_SESSION['lsShop']['importFileInfo']['status'],
			'numRecords' => $_SESSION['lsShop']['importFileInfo']['numRecords'],
			'numProcessedRecords' => $_SESSION['lsShop']['importFileInfo']['numProcessedRecords'],
			'changesStock' => $_SESSION['lsShop']['importFileInfo']['changesStock'],
			'deletesRecords' => $_SESSION['lsShop']['importFileInfo']['deletesRecords'],
			'currentlyProcessingDataRowType' => $_SESSION['lsShop']['importFileInfo']['currentlyProcessingDataRowType'],
			'recommendedProductsTranslated' => $_SESSION['lsShop']['importFileInfo']['recommendedProductsTranslated']
		);
	}
	
	/**
	 * This function writes necessary basic information about the currently existing import file
	 * to the session. This information can be modified/extended by other functions, e.g. the analyzing function.
	 * 
	 * If the file hash determined by this function matches the file hash in the session this function
	 * does not overwrite the file information so that modified/extended data stays intact.
	 */
	public function getCurrentlyExistingImportFileInfo() {
		if (!$GLOBALS['TL_CONFIG']['ls_shop_standardProductImportFolder']) {
			return null;
		}

		$arrImportFileInfo = array(
			'hash' => '',
			'name' => '',
			'fullFilename' => '',
			'date' => '',
			'size' => '',
			'status' => 'notValidatedYet',
			'numRecords' => array(
				'products' => 0,
				'variants' => 0,
				'productLanguages' => 0,
				'variantLanguages' => 0
			),
			'numProcessedRecords' => array(
				'products' => 0,
				'variants' => 0,
				'productLanguages' => 0,
				'variantLanguages' => 0
			),
			'recommendedProductsTranslated' => false,
			'changesStock' => false,
			'deletesRecords' => false,
			'arrKeys' => array(),
			'arrAnalyzingInfo' => array(
				'product_productcodes' => array(),
				'variant_productcodes' => array()
			),
			'hasError' => false,
			'arrMessages' => array(),
			'arrImportInfos' => array(
				'productsToIgnore' => array(),
				'productsToDelete' => array(),
				'variantsToIgnore' => array(),
				'variantsToDelete' => array(),
				'productsProductcodeToID' => array(),
				'variantsProductcodeToID' => array()
			),
			'currentlyProcessingDataRowType' => null,
			'lastFilePointerPosition' => 0
		);

        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
		$importFolder = $str_projectDir.'/'.ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImportFolder']);
		$files = scandir($importFolder);
		
		// If exactly one file exists (which must be the case), $files counts 3 because of the pseudo files "." und ".."
		if (count($files) != 3) {
			$this->clearImportFolder();
			unset($_SESSION['lsShop']['importFileInfo']);
			return null;
		}
		
		$file = $files[2];

		$objFile = new File(ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImportFolder']).'/'.$file);
		
		$arrImportFileInfo['name'] = $objFile->name;
		$arrImportFileInfo['fullFilename'] = $str_projectDir.'/'.ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImportFolder']).'/'.$file;
		$arrImportFileInfo['date'] = Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $objFile->mtime);
		
		$arrImportFileInfo['sizeBytes'] = $objFile->size;
		if ($arrImportFileInfo['sizeBytes'] >= 1024 && $arrImportFileInfo['sizeBytes'] < 1048576) {
			$arrImportFileInfo['size'] = round(($arrImportFileInfo['sizeBytes'] / 1024), 2).' KiB';
		} else if ($arrImportFileInfo['sizeBytes'] >= 1048576) {
			$arrImportFileInfo['size'] = round(($arrImportFileInfo['sizeBytes'] / 1048576), 2).' MiB';
		} else {
			$arrImportFileInfo['size'] = $arrImportFileInfo['sizeBytes'].' Byte';
		}
		
		$arrImportFileInfo['hash'] = sha1($arrImportFileInfo['fullFilename'].$arrImportFileInfo['date'].$arrImportFileInfo['sizeBytes']);
		
		/*
		 * If there is no file information in the session we write it to the session
		 * and if there is file information in the session but the currently determined
		 * hash doesn't match the hash in the session, we overwrite the information in the
		 * session and set the status to 'fileChanged'.
		 * 
		 * If there's file information in the session and the hash matches we don't do anything.
		 */
		if (!isset($_SESSION['lsShop']['importFileInfo'])) {
			$_SESSION['lsShop']['importFileInfo'] = $arrImportFileInfo;
		} else if ($_SESSION['lsShop']['importFileInfo']['hash'] != $arrImportFileInfo['hash']) {
			$arrImportFileInfo['status'] = 'fileChanged';
			$_SESSION['lsShop']['importFileInfo'] = $arrImportFileInfo;
		}
	}

	public function validateFile() {
		$this->analyzeImportFile();
		
		if ($_SESSION['lsShop']['importFileInfo']['hasError']) {
			foreach ($_SESSION['lsShop']['importFileInfo']['arrMessages'] as $message) {
                System::getContainer()->get('monolog.logger.contao')->info('MERCONIS IMPORTER: '.$message, ['contao' => new ContaoContext('MERCONIS IMPORTER', TL_MERCONIS_IMPORTER)]);
			}
			$_SESSION['lsShop']['importFileInfo']['status'] = 'notOk';
			return false;
		} else {
			$_SESSION['lsShop']['importFileInfo']['status'] = 'ok';
			return true;
		}
	}

	/**
	 * Diese Funktion analysiert eine Import-Datei und gibt das übergebene Array
	 * um einige weitere Informationen erweitert zurück
	 */
	protected function analyzeImportFile() {
		setlocale(LC_ALL, (isset($GLOBALS['TL_CONFIG']['ls_shop_importCsvLocale']) && $GLOBALS['TL_CONFIG']['ls_shop_importCsvLocale'] ? $GLOBALS['TL_CONFIG']['ls_shop_importCsvLocale'] : 'en_US.utf-8'));
		
		if (!$this->openImportFile()) {
			$_SESSION['lsShop']['importFileInfo']['hasError'] = true;
			$_SESSION['lsShop']['importFileInfo']['arrMessages'][] = $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['importText15'];
			return;
		}
		
		
		while (($row = $this->getImportFileRow()) !== false) {
			if ($this->bln_importFileHasRowForVersionAndSpecialChars && $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] == 1) {
				$importFileVersion = $row[0];
				$testSpecialCharacters = $row[1];
				
				/*
				 * Prüfen, ob die Versionsnummer der Import-Datei erlaubt ist
				 */
				if (!in_array($importFileVersion, $this->allowedImportFileVersions)) {
					$_SESSION['lsShop']['importFileInfo']['hasError'] = true;
					$_SESSION['lsShop']['importFileInfo']['arrMessages'][] = $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['importText08'];
					return;
				}
				
				/*
				 * Prüfen, ob die Test-Sonderzeichen korrekt erkannt werden können
				 */
				if (
						!preg_match('/ä/', $testSpecialCharacters)
					||	!preg_match('/ö/', $testSpecialCharacters)
					||	!preg_match('/ü/', $testSpecialCharacters)
					||	!preg_match('/ß/', $testSpecialCharacters)
					||	!preg_match('/é/', $testSpecialCharacters)
					||	!preg_match('/è/', $testSpecialCharacters)
					||	!preg_match('/ê/', $testSpecialCharacters)
				) {
					$_SESSION['lsShop']['importFileInfo']['hasError'] = true;
					$_SESSION['lsShop']['importFileInfo']['arrMessages'][] = $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['importText09'];
					return;
				}
				/* */

				continue;
			} else if (
			    ($this->bln_importFileHasRowForVersionAndSpecialChars && $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] == 2)
                || (!$this->bln_importFileHasRowForVersionAndSpecialChars && $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] == 1)
            ) {
				$_SESSION['lsShop']['importFileInfo']['arrKeys'] = $row;
				continue;
			}
			
			
			/*
			 * Prüfen, ob die Anzahl der anhand der Überschriftszeile ermittelten Felder zur Anzahl
			 * der in dieser Zeile ausgelesenen Felder passt. Ist dies nicht der Fall, so liegt
			 * höchstwahrscheinlich ein Problem mit nicht gequoteten Zeilenumbrüchen vor. Auf jeden
			 * Fall muss der Import bzw. die Prüfung an dieser Stelle dann abgebrochen werden.
			 * 
			 * Hinweis: Durch eine Code-Umstrukturierung findet die Prüfung jetzt in der Funktion
			 * getImportFileRow() statt und liefert, sofern die Anzahl der Felder nicht passt,
			 * null zurück.
			 */
			if ($row === null) {
				$_SESSION['lsShop']['importFileInfo']['hasError'] = true;
				$_SESSION['lsShop']['importFileInfo']['arrMessages'][] = $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['importText13'];
				return;
			}
			
			
			
			/*
			 * Merken der existierenden Artikelnummern, um die angegebenen übergeordneten Artikelnummern
			 * später prüfen zu können.
			 * 
			 * Zählen der Datensatztypen
			 * 
			 * Prüfen, ob Lagerbestandsänderungen vorgenommen werden sollen
			 * 
			 * Prüfen, ob Löschungen vorgenommen werden sollen
			 */
			
			if ($row['changeStock']) {
				$_SESSION['lsShop']['importFileInfo']['changesStock'] = true;
			}
			
			if ($row['delete']) {
				$_SESSION['lsShop']['importFileInfo']['deletesRecords'] = true;
			}
			
			if ($row['type'] == 'product') {
				$_SESSION['lsShop']['importFileInfo']['arrAnalyzingInfo']['product_productcodes'][] = $row['productcode'];
				$_SESSION['lsShop']['importFileInfo']['numRecords']['products']++;
			} else if ($row['type'] == 'variant') {
				$_SESSION['lsShop']['importFileInfo']['arrAnalyzingInfo']['variant_productcodes'][] = $row['productcode'];
				$_SESSION['lsShop']['importFileInfo']['numRecords']['variants']++;
			} else if ($row['type'] == 'productLanguage') {
				$_SESSION['lsShop']['importFileInfo']['numRecords']['productLanguages']++;
			} else if ($row['type'] == 'variantLanguage') {
				$_SESSION['lsShop']['importFileInfo']['numRecords']['variantLanguages']++;
			}
		}
		
		/*
		 * Verschiedene Integritäts- und Plausibilitätschecks
		 */
		$arrDataErrors = array(
			'notExistingDataType' => false,
			'missingProductcode' => false,
			'missingProductName' => false,
			'missingTaxClass' => false,
			'variantIncorrectParentProductcode' => false,
			'variantLanguageIncorrectParentProductcode' => false,
			'productLanguageIncorrectParentProductcode' => false,
			'missingOrWrongLanguagecode' => false,
			'notExistingAttribute' => false,
			'notExistingAttributeValue' => false,
			'notMatchingAttributesAndValues' => false,
			'notExistingCategory' => false,
			'notExistingPriceType' => false,
			'notExistingPriceTypeOld' => false,
			'notExistingWeightType' => false,
			'notExistingDeliveryInfoType' => false,
			'wrongStockValue' => false,
			'missingFlexContentFields' => false,
			'missingFlexContentFieldsLanguageIndependent' => false,
			
			'valueInvalid_name' => false,
			'valueInvalid_availableFrom' => false,
			'valueInvalid_sorting' => false,
			'valueInvalid_price' => false,
			'valueInvalid_oldPrice' => false,
			'valueInvalid_weight' => false,
			'valueInvalid_unit' => false,
			'valueInvalid_quantityComparisonUnit' => false,
			'valueInvalid_quantityComparisonDivisor' => false,
			
			'productValueInvalid_quantityDecimals' => false,
			'productValueInvalid_template' => false,
			'productValueInvalid_producer' => false,
			
			'valueInvalid_scalePriceType' => false,
			'valueInvalid_scalePriceQuantityDetectionMethod' => false,
			'valueInvalid_scalePriceKeyword' => false,
						
			'valueInvalid_productcode' => false
		);
		
		/*
		 * Durchlaufen und prüfen der einzelnen Datensätze. Die durchlaufenen
		 * Datensätze (auch die leeren, also de facto alle Zeilen) werden gezählt,
		 * damit zu jedem Fehler die betroffenen Zeilen festgehalten werden können.
		 */
		$rowCounter = 0;
		while (($row = $this->getImportFileRow()) !== false) {
			if (
			    $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] == 1
                || ($this->bln_importFileHasRowForVersionAndSpecialChars && $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] == 2)
            ) {
				continue;
			}
			$rowCounter++;
			
			if ($this->rowIsEmpty($row)) {
				continue;
			}
			
			foreach ($arrDataErrors as $errorKey => $arrErrorDetected) {
				if ($this->checkDataFor($errorKey, $row)) {
					$arrDataErrors[$errorKey][] = $rowCounter;
				}
			}
		}
		
		/*
		 * Durchlaufen der möglichen Fehler und bei Vorliegen eines Fehlers hinzufügen der entsprechenden Meldung
		 */
		foreach ($arrDataErrors as $errorKey => $arrErrorDetected) {
			if ($arrErrorDetected && is_array($arrErrorDetected)) {
				$strRowNumbers = '';
				foreach ($arrErrorDetected as $rowNr) {
					if ($strRowNumbers) {
						$strRowNumbers .= ', ';
					}
					$strRowNumbers .= $rowNr;
				}
				$_SESSION['lsShop']['importFileInfo']['hasError'] = true;
				$_SESSION['lsShop']['importFileInfo']['arrMessages'][] = sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['importErrors'][$errorKey], $strRowNumbers);
			}
		}
	}
	
	public function importFile() {
		if ($_SESSION['lsShop']['importFileInfo']['status'] != 'ok') {
			$_SESSION['lsShop']['importFileInfo']['status'] = 'importFailed';
			return;
		}
		
		if (
				!isset($_SESSION['lsShop']['importFileInfo']['currentlyProcessingDataRowType'])
			||	!$_SESSION['lsShop']['importFileInfo']['currentlyProcessingDataRowType']
		) {
			/*
			 * If we don't know anything about the currently processing data row type,
			 * we start with the first data row type and with the file pointer positioned
			 * at the beginning of the file
			 */
			reset($this->dataRowTypesInOrderToProcess);
			$_SESSION['lsShop']['importFileInfo']['currentlyProcessingDataRowType'] = $this->dataRowTypesInOrderToProcess[key($this->dataRowTypesInOrderToProcess)];
			$_SESSION['lsShop']['importFileInfo']['lastFilePointerPosition'] = 0;
			
			if (isset($GLOBALS['MERCONIS_HOOKS']['import_begin']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_begin'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['import_begin'] as $mccb) {
					$objMccb = System::importStatic($mccb[0]);
					$objMccb->{$mccb[1]}();
				}
			}
		} else if ($_SESSION['lsShop']['importFileInfo']['lastFilePointerPosition'] === null) {
			/*
			 * If we know the currently processing data row type but the last file pointer position
			 * is null, we jump to the next data row type and set the file pointer to the beginning
			 * of the file
			 */
			$nextDataRowTypeKey = array_search($_SESSION['lsShop']['importFileInfo']['currentlyProcessingDataRowType'], $this->dataRowTypesInOrderToProcess) + 1;
			if (!isset($this->dataRowTypesInOrderToProcess[$nextDataRowTypeKey])) {
				/*
				 * If there is no next data row type and recommended products have already been translated, we are finished!
				 */
				if ($_SESSION['lsShop']['importFileInfo']['recommendedProductsTranslated']) {
					$_SESSION['lsShop']['importFileInfo']['status'] = 'importFinished';
					
					/*
					 * Adding this confirmation message might not be a good idea because it will be displayed when the page reloads which,
					 * because of AJAX, might not be the case until it's too late and the message could be confusing.
					 */
					// $this->addConfirmationMessage(sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['importText07'], $_SESSION['lsShop']['importFileInfo']['name']));
			
					/*
					 * Not sure if it's a good idea to clear the import folder after the import.
					 */
					// $this->clearImportFolder();
					
					if (isset($GLOBALS['MERCONIS_HOOKS']['import_finished']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_finished'])) {
						foreach ($GLOBALS['MERCONIS_HOOKS']['import_finished'] as $mccb) {
							$objMccb = System::importStatic($mccb[0]);
							$objMccb->{$mccb[1]}();
						}
					}

					ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();
				} else {
					if (!$_SESSION['lsShop']['importFileInfo']['recommendedProductsTranslated']) {
						ls_shop_productManagementApiHelper::translateRecommendedProductCodesInIDs();
						$_SESSION['lsShop']['importFileInfo']['recommendedProductsTranslated'] = true;
					}
				}
				
				return;				
			}
			$_SESSION['lsShop']['importFileInfo']['currentlyProcessingDataRowType'] = $this->dataRowTypesInOrderToProcess[$nextDataRowTypeKey];
			$_SESSION['lsShop']['importFileInfo']['lastFilePointerPosition'] = 0;
		}
		
		$this->openImportFile();
		
		/*
		 * Walk through the csv rows and because we deal with one data row type
		 * at a time, skip all rows with the wrong data row type
		 */
		$count = 0;
		while (($row = $this->getImportFileRow()) !== false) {
			if ($row['type'] != $_SESSION['lsShop']['importFileInfo']['currentlyProcessingDataRowType']) {
				continue;
			}
			
			switch ($row['type']) {
				case 'product':
					$this->processProductData($row);
					
					if (!isset($_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['products'])) {
						$_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['products'] = 0;
					}
					$_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['products']++;
					break;

				case 'variant':
					$this->processVariantData($row);
					
					if (!isset($_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['variants'])) {
						$_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['variants'] = 0;
					}
					$_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['variants']++;
					break;

				case 'productLanguage':
					$this->processProductLanguageData($row);
					
					if (!isset($_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['productLanguages'])) {
						$_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['productLanguages'] = 0;
					}
					$_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['productLanguages']++;
					break;

				case 'variantLanguage':
					$this->processVariantLanguageData($row);
					
					if (!isset($_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['variantLanguages'])) {
						$_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['variantLanguages'] = 0;
					}
					$_SESSION['lsShop']['importFileInfo']['numProcessedRecords']['variantLanguages']++;
					break;
			}
			
			$count++;
			if ($count >= $this->intNumMaxRowsToProcessInOneRound) {
				break;
			}
		}
	}
	
	/*
	 * Diese Funktion verarbeitet einen Produkt-Datensatz, was entweder das Löschen aus der Datenbank, das Eintragen in die Datenbank
	 * oder das Aktualisieren eines Eintrags in der Datenbank bedeuten kann.
	 */
	protected function processProductData($row) {
		if (isset($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingProductData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingProductData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingProductData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$row = $objMccb->{$mccb[1]}($row);
			}
		}
		
		/*
		 * Zu ignorierenden Datensatz überspringen und merken
		 */
		if ($row['ignore']) {
			$_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsToIgnore'][] = $row['productcode'];
			return true;
		}
		
		// Prüfen, ob es ein Produkt mit der Artikelnummer bereits gibt
		$alreadyExistsAsID = false;

		$objProdExists = Database::getInstance()->prepare("
			SELECT		`id`
			FROM		`tl_ls_shop_product`
			WHERE		`lsShopProductCode` = ?
		")
		->execute($row['productcode']);

		if ($objProdExists->numRows) {
			$alreadyExistsAsID = $objProdExists->id;
			$_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsProductcodeToID'][$row['productcode']] = $alreadyExistsAsID;
		}
		
		/* ######################################################################################################################
		 * Löschen, sofern gewünscht und Datensatz bereits vorhanden. Falls Datensatz nicht vorhanden, Funktion einfach abbrechen
		 */
		if ($row['delete']) {
			$_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsToDelete'][] = $row['productcode'];
			if ($alreadyExistsAsID) {
				Database::getInstance()->prepare("
					DELETE FROM	`tl_ls_shop_product`
					WHERE		`id` = ?
				")
				->limit(1)
				->execute($alreadyExistsAsID);
				
				// Löschen der Varianten zum Produkt
				$this->deleteVariantsForProduct($alreadyExistsAsID);
			}
			return true;
		}
		/*
		 * ######################################################################################################################
		 */
		


		// Feldwerte, die nicht einfach direkt eingetragen werden können, sondern in irgendeiner Form übersetzt werden müssen, vorbereiten
		$row['category'] = ls_shop_productManagementApiHelper::generatePageListFromCategoryValue($row['category']);
		$row['taxclass'] = ls_shop_productManagementApiHelper::getTaxClassID($row['taxclass']);
		$str_configuratorOrCustomizerValue = $row['configurator'];
		$row['configurator'] = ls_shop_productManagementApiHelper::getConfiguratorID($str_configuratorOrCustomizerValue);

		/*
		 * The import file does not actually contain a field named "customizer". Instead, the value in the field "configurator"
		 * can be interpreted as a reference to either a configurator or a customizer. The field "customizer" will
		 * be created right here and, if the field "configurator" contains a reference to a customizer, filled accordingly.
		 */
        $row['customizer'] = ls_shop_productManagementApiHelper::getCustomizerLogicFileReference($str_configuratorOrCustomizerValue);

		$row['settingsForStockAndDeliveryTime'] = ls_shop_productManagementApiHelper::getDeliveryInfoSetID($row['settingsForStockAndDeliveryTime']);
		$row['settingsForStockAndDeliveryTimeInPreorderPhase'] = ls_shop_productManagementApiHelper::getDeliveryInfoSetID($row['settingsForStockAndDeliveryTimeInPreorderPhase']);
		$row['availableFrom'] = !$row['availableFrom'] ? '' : strtotime($row['availableFrom']);
		$row['moreImages'] = ls_shop_productManagementApiHelper::prepareMoreImages($row['moreImages']);
		$row['flex_contents'] = ls_shop_productManagementApiHelper::generateFlexContentsString($row);
		$row['flex_contentsLanguageIndependent'] = ls_shop_productManagementApiHelper::generateFlexContentsStringLanguageIndependent($row);
		
		/*
		 * We count from 0 because we also have to handle the non-group-specific fields
		 */
		for ($i=0; $i <= ls_shop_productManagementApiHelper::$int_numImportableGroupPrices; $i++) {
			$str_multipriceFieldSuffix = $i === 0 ? '' : ('_'.$i);
			
			/*
			 * 'priceForGroups' only exists in multi prices
			 */
			if ($i > 0) {
				$row['priceForGroups'.$str_multipriceFieldSuffix] = ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $row['priceForGroups'.$str_multipriceFieldSuffix]);
			}
			
			$row['scalePriceType'.$str_multipriceFieldSuffix] = $row['scalePriceType'.$str_multipriceFieldSuffix] ? $row['scalePriceType'.$str_multipriceFieldSuffix] : 'scalePriceStandalone';
			$row['scalePriceQuantityDetectionMethod'.$str_multipriceFieldSuffix] = $row['scalePriceQuantityDetectionMethod'.$str_multipriceFieldSuffix] ? $row['scalePriceQuantityDetectionMethod'.$str_multipriceFieldSuffix] : 'separatedVariantsAndConfigurations';
			$row['scalePrice'.$str_multipriceFieldSuffix] = ls_shop_productManagementApiHelper::generateScalePriceArray($row['scalePrice'.$str_multipriceFieldSuffix]);
		}

				
		/*
		 * Das Feld 'images' darf nur ein Bild enthalten, kann aber theoretisch auch mehrere mit kommagetrennte Bilder liefern,
		 * was entsprechend unterbunden werden muss. Es wird daher geprüft, ob ein Komma im String enthalten ist. Wenn ja, wird
		 * an den Kommas getrennt und nur der erste Wert verwendet
		 */
		$arrTmp = false;
		if (preg_match('/,/', $row['image'])) {
			$arrTmp = explode(',', $row['image']);
		}
		$row['image'] = is_array($arrTmp[0]) ? trim($arrTmp[0]) : trim($row['image']);
		$row['image'] = $row['image'] ? ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImageFolder']).'/'.$row['image'] : '';
		$objModels = FilesModel::findMultipleByPaths(array($row['image']));
		if ($objModels !== null) {
			$row['image'] = $objModels->first()->uuid;
		} else {
			$row['image'] = null;
		}
		
		$row['propertiesAndValues'] = ls_shop_productManagementApiHelper::preparePropertiesAndValues($row);
		
		/*
		 * WICHTIG: Der eingetragene Wert für "recommendedProducts" ist nicht vollständig korrekt, da er nicht die benötigten IDS
		 * sondern die Artikelnummern enthält, und daher nur von temporärer Natur. Nach erfolgtem Import muss für alle Produkte
		 * daher eine entsprechende Übersetzung dieses Wertes stattfinden.
		 */
		$row['recommendedProducts'] = ls_shop_productManagementApiHelper::prepareRecommendedProducts($row['recommendedProducts']);
		

		if (isset($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingProductData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingProductData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingProductData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$row = $objMccb->{$mccb[1]}($row, $alreadyExistsAsID);
			}
		}

		/* ######################################################################################################################
		 * Update, falls Datensatz vorhanden
		 * 
		 */
		if ($alreadyExistsAsID) {
			$str_addGroupPriceFieldsToQuery = ls_shop_productManagementApiHelper::createGroupPriceFieldsForQuery('product');
			
			$objUpdateProduct = Database::getInstance()->prepare("
				UPDATE		`tl_ls_shop_product`
				SET			`title` = ?,
							`alias` = ?,
							`sorting` = ?,
							`keywords` = ?,
							`shortDescription` = ?,
							`description` = ?,
							`published` = ?,
							`pages` = ?,
							`lsShopProductPrice` = ?,
							`lsShopProductPriceOld` = ?,
							`useOldPrice` = ?,
							`availableFrom` = ?,
							`preorderingAllowed` = ?,
							`lsShopProductWeight` = ?,
							`lsShopProductSteuersatz` = ?,
							`lsShopProductQuantityUnit` = ?,
							`lsShopProductQuantityDecimals` = ?,
							`lsShopProductMengenvergleichUnit` = ?,
							`lsShopProductMengenvergleichDivisor` = ?,
							`lsShopProductMainImage` = ?,
							`lsShopProductMoreImages` = ?,
							`lsShopProductDetailsTemplate` = ?,
							`lsShopProductIsNew` = ?,
							`lsShopProductIsOnSale` = ?,
							`lsShopProductRecommendedProducts` = ?,
							`lsShopProductDeliveryInfoSet` = ?,
							`deliveryInfoSetToUseInPreorderPhase` = ?,
							`lsShopProductProducer` = ?,
							`configurator` = ?,
							`customizerLogicFile` = ?,
							`flex_contents` = ?,
							`flex_contentsLanguageIndependent` = ?,
							`lsShopProductAttributesValues` = ?,
							`useScalePrice` = ?,
							`scalePriceType` = ?,
							`scalePriceQuantityDetectionMethod` = ?,
							`scalePriceQuantityDetectionAlwaysSeparateConfigurations` = ?,
							`scalePriceKeyword` = ?,
							`scalePrice` = ?
							".$str_addGroupPriceFieldsToQuery."
				WHERE		`id` = ?
			")
			->limit(1);
			
			$arr_queryParams = array(
				$row['name'], // String, maxlength 255
				ls_shop_productManagementApiHelper::generateProductAlias($row['name'], $row['alias'], $alreadyExistsAsID),
				$row['sorting'] && $row['sorting'] > 0 ? $row['sorting'] : 0, // int empty = 0
				$row['keywords'], // text
				$row['shortDescription'], // text
				$row['description'], // text
				$row['publish'] ? '1' : '', // 1 or ''
				$row['category'], // blob
				$row['price'] ? $row['price'] : 0, // decimal, empty = 0
				$row['oldPrice'] ? $row['oldPrice'] : 0, // decimal, empty = 0
				$row['useOldPrice'] ? '1' : '', // 1 or ''
                $row['availableFrom'], // String, date in format yyyy-mm-dd or empty string
				$row['preorderingAllowed'] ? '1' : '', // 1 or ''
				$row['weight'] ? $row['weight'] : 0, // decimal, empty = 0
				$row['taxclass'] ? $row['taxclass'] : 0, // int, empty = 0
				$row['unit'], // String, maxlength 255
				$row['quantityDecimals'] && $row['quantityDecimals'] > 0 ? $row['quantityDecimals'] : 0, // int, empty = 0
				$row['quantityComparisonUnit'], // String, maxlength 255
				$row['quantityComparisonDivisor'] ? $row['quantityComparisonDivisor'] : 0, // decimal, empty = 0
				$row['image'], // binary(16), translated, check unclear
				$row['moreImages'], // blob, translated, check unclear
				$row['template'], // String, maxlength 64
				$row['new'] ? '1' : '', // 1 or ''
				$row['onSale'] ? '1' : '', // 1 or ''
				$row['recommendedProducts'], // blob, translated, check unclear
				$row['settingsForStockAndDeliveryTime'] ? $row['settingsForStockAndDeliveryTime'] : 0, // int, empty = 0
				$row['settingsForStockAndDeliveryTimeInPreorderPhase'] ? $row['settingsForStockAndDeliveryTimeInPreorderPhase'] : 0, // int, empty = 0
				$row['producer'], // String, maxlength 255
				$row['configurator'] ? $row['configurator'] : 0, // int, empty = 0
				$row['customizer'] ?: null, // binary file reference, empty = null
				$row['flex_contents'], // blob, translated, check unclear
				$row['flex_contentsLanguageIndependent'], // blob, translated, check unclear
				$row['propertiesAndValues'], // blob, translated, check unclear
				$row['useScalePrice'] ? '1' : '', // 1 or ''
				$row['scalePriceType'], // String, maxlength 255
				$row['scalePriceQuantityDetectionMethod'], // String, maxlength 255
				$row['scalePriceQuantityDetectionAlwaysSeparateConfigurations'] ? '1' : '', // 1 or ''
				$row['scalePriceKeyword'], // String, maxlength 255
				$row['scalePrice'] // blob, translated, check unclear
			);
			
			$arr_queryParams = ls_shop_productManagementApiHelper::addGroupPriceFieldsToQueryParam($arr_queryParams, $row, 'product');
						
			// Must be the last parameter in the array
			$arr_queryParams[] = $alreadyExistsAsID;
			
			$objUpdateProduct->execute(...array_values($arr_queryParams));

			ls_shop_generalHelper::insertAttributeValueAllocationsInAllocationTable($row['propertiesAndValues'], $alreadyExistsAsID, 0);
			
			/*
			 * Durchführen der Lagerbestandsänderung, sofern das Feld nicht wirklich leer ist. Eine eingetragene "0" führt auch zur entsprechenden
			 * Lagerbestandsänderung. Enthält der Feldwert etwas anderes als Zahlen von 0-9 und einen Punkt, ein Plus- bzw. ein Minuszeichen, so
			 * wird die Lagerbestandsänderung nicht durchgeführt. Ist ein Plus- oder Minuszeichen enthalten, so wird berechnet, falls nicht, dann
			 * wird der Wert fest eingetragen.
			 */
			if ($row['changeStock'] !== '' && $row['changeStock'] !== null && $row['changeStock'] !== false && !preg_match('/[^0-9+-.]/', $row['changeStock'])) {
				ls_shop_generalHelper::changeStockDirectly('product', $alreadyExistsAsID, $row['changeStock']);
			}
			
			/*
			 * Spracheinträge schreiben
			 */
			ls_shop_languageHelper::saveMultilanguageValue(
				$alreadyExistsAsID,
				$row['language'],
				'tl_ls_shop_product_languages',
				array('title', 'alias', 'keywords', 'description', 'lsShopProductQuantityUnit', 'lsShopProductMengenvergleichUnit', 'shortDescription', 'flex_contents'),
				array($row['name'], ls_shop_productManagementApiHelper::generateProductAlias($row['name'], $row['alias'], $alreadyExistsAsID, $row['language']), $row['keywords'],$row['description'],$row['unit'],$row['quantityComparisonUnit'],$row['shortDescription'],$row['flex_contents'])
			);
			
			if (isset($GLOBALS['MERCONIS_HOOKS']['import_afterUpdatingProductData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_afterUpdatingProductData'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['import_afterUpdatingProductData'] as $mccb) {
					$objMccb = System::importStatic($mccb[0]);
					$objMccb->{$mccb[1]}($alreadyExistsAsID);
				}
			}
			
			return true;
		}
		
		/* ######################################################################################################################
		 * Neu einfügen, falls Datensatz noch nicht vorhanden
		 * 
		 */
		else {
			$str_addGroupPriceFieldsToQuery = ls_shop_productManagementApiHelper::createGroupPriceFieldsForQuery('product');
			
			$objInsertProduct = Database::getInstance()->prepare("
				INSERT INTO	`tl_ls_shop_product`
				SET			`tstamp` = ?,
							`title` = ?,
							`alias` = ?,
							`sorting` = ?,
							`lsShopProductCode` = ?,
							`keywords` = ?,
							`shortDescription` = ?,
							`description` = ?,
							`published` = ?,
							`pages` = ?,
							`lsShopProductPrice` = ?,
							`lsShopProductPriceOld` = ?,
							`useOldPrice` = ?,
							`availableFrom` = ?,
							`preorderingAllowed` = ?,
							`lsShopProductWeight` = ?,
							`lsShopProductSteuersatz` = ?,
							`lsShopProductQuantityUnit` = ?,
							`lsShopProductQuantityDecimals` = ?,
							`lsShopProductMengenvergleichUnit` = ?,
							`lsShopProductMengenvergleichDivisor` = ?,
							`lsShopProductMainImage` = ?,
							`lsShopProductMoreImages` = ?,
							`lsShopProductDetailsTemplate` = ?,
							`lsShopProductIsNew` = ?,
							`lsShopProductIsOnSale` = ?,
							`lsShopProductRecommendedProducts` = ?,
							`lsShopProductDeliveryInfoSet` = ?,
							`deliveryInfoSetToUseInPreorderPhase` = ?,
							`lsShopProductProducer` = ?,
							`configurator` = ?,
							`customizerLogicFile` = ?,
							`flex_contents` = ?,
							`flex_contentsLanguageIndependent` = ?,
							`lsShopProductAttributesValues` = ?,
							`useScalePrice` = ?,
							`scalePriceType` = ?,
							`scalePriceQuantityDetectionMethod` = ?,
							`scalePriceQuantityDetectionAlwaysSeparateConfigurations` = ?,
							`scalePriceKeyword` = ?,
							`scalePrice` = ?
							".$str_addGroupPriceFieldsToQuery."
			");
			
			$arr_queryParams = array(
				time(),
				$row['name'], // String, maxlength 255
				ls_shop_productManagementApiHelper::generateProductAlias($row['name'], $row['alias']),
				$row['sorting'] && $row['sorting'] > 0 ? $row['sorting'] : 0, // int empty = 0
				$row['productcode'], // String, maxlength 255
				$row['keywords'], // text
				$row['shortDescription'], // text
				$row['description'], // text
				$row['publish'] ? '1' : '', // 1 or ''
				$row['category'], // blob
				$row['price'] ? $row['price'] : 0, // decimal, empty = 0
				$row['oldPrice'] ? $row['oldPrice'] : 0, // decimal, empty = 0
				$row['useOldPrice'] ? '1' : '', // 1 or ''
                $row['availableFrom'], // String, date in format yyyy-mm-dd or empty string
				$row['preorderingAllowed'] ? '1' : '', // 1 or ''
				$row['weight'] ? $row['weight'] : 0, // decimal, empty = 0
				$row['taxclass'] ? $row['taxclass'] : 0, // int, empty = 0
				$row['unit'], // String, maxlength 255
				$row['quantityDecimals'] && $row['quantityDecimals'] > 0 ? $row['quantityDecimals'] : 0, // int, empty = 0
				$row['quantityComparisonUnit'], // String, maxlength 255
				$row['quantityComparisonDivisor'] ? $row['quantityComparisonDivisor'] : 0, // decimal, empty = 0
				$row['image'], // binary(16), translated, check unclear
				$row['moreImages'], // blob, translated, check unclear
				$row['template'], // String, maxlength 64
				$row['new'] ? '1' : '', // 1 or ''
				$row['onSale'] ? '1' : '', // 1 or ''
				$row['recommendedProducts'], // blob, translated, check unclear
				$row['settingsForStockAndDeliveryTime'] ? $row['settingsForStockAndDeliveryTime'] : 0, // int, empty = 0
				$row['settingsForStockAndDeliveryTimeInPreorderPhase'] ? $row['settingsForStockAndDeliveryTimeInPreorderPhase'] : 0, // int, empty = 0
				$row['producer'], // String, maxlength 255
				$row['configurator'] ? $row['configurator'] : 0, // int, empty = 0
                $row['customizer'] ?: null, // binary file reference, empty = null
                $row['flex_contents'], // blob, translated, check unclear
				$row['flex_contentsLanguageIndependent'], // blob, translated, check unclear
				$row['propertiesAndValues'], // blob, translated, check unclear
				$row['useScalePrice'] ? '1' : '', // 1 or ''
				$row['scalePriceType'], // String, maxlength 255
				$row['scalePriceQuantityDetectionMethod'], // String, maxlength 255
				$row['scalePriceQuantityDetectionAlwaysSeparateConfigurations'] ? '1' : '', // 1 or ''
				$row['scalePriceKeyword'], // String, maxlength 255
				$row['scalePrice'] // blob, translated, check unclear
			);
			
			$arr_queryParams = ls_shop_productManagementApiHelper::addGroupPriceFieldsToQueryParam($arr_queryParams, $row, 'product');
			
			$objInsertProduct->execute(...array_values($arr_queryParams));

			$newProductID = $objInsertProduct->insertId;
			$_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsProductcodeToID'][$row['productcode']] = $newProductID;

			ls_shop_generalHelper::insertAttributeValueAllocationsInAllocationTable($row['propertiesAndValues'], $newProductID, 0);
			
			/*
			 * Durchführen der Lagerbestandsänderung, sofern das Feld nicht wirklich leer ist. Eine eingetragene "0" führt auch zur entsprechenden
			 * Lagerbestandsänderung. Enthält der Feldwert etwas anderes als Zahlen von 0-9 und einen Punkt, ein Plus- bzw. ein Minuszeichen, so
			 * wird die Lagerbestandsänderung nicht durchgeführt. Ist ein Plus- oder Minuszeichen enthalten, so wird berechnet, falls nicht, dann
			 * wird der Wert fest eingetragen.
			 */
			if ($row['changeStock'] !== '' && $row['changeStock'] !== null && $row['changeStock'] !== false && !preg_match('/[^0-9+-.]/', $row['changeStock'])) {
				ls_shop_generalHelper::changeStockDirectly('product', $newProductID, $row['changeStock']);
			}
			
			/*
			 * Spracheinträge schreiben
			 */
			ls_shop_languageHelper::saveMultilanguageValue(
				$newProductID,
				$row['language'],
				'tl_ls_shop_product_languages',
				array('title', 'alias', 'keywords', 'description', 'lsShopProductQuantityUnit', 'lsShopProductMengenvergleichUnit', 'shortDescription', 'flex_contents'),
				array($row['name'], ls_shop_productManagementApiHelper::generateProductAlias($row['name'], $row['alias'], $newProductID, $row['language']),$row['keywords'],$row['description'],$row['unit'],$row['quantityComparisonUnit'],$row['shortDescription'],$row['flex_contents'])
			);
			
			if (isset($GLOBALS['MERCONIS_HOOKS']['import_afterInsertingProductData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_afterInsertingProductData'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['import_afterInsertingProductData'] as $mccb) {
					$objMccb = System::importStatic($mccb[0]);
					$objMccb->{$mccb[1]}($newProductID);
				}
			}
			
			return true;
		}
	}

	protected function processVariantData($row) {
		if (in_array($row['parentProductcode'], $_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsToIgnore'])) {
			$row['ignore'] = 'x';
		}
		
		if (in_array($row['parentProductcode'], $_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsToDelete'])) {
			$row['delete'] = 'x';
		}
		
		if (isset($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingVariantData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingVariantData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingVariantData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$row = $objMccb->{$mccb[1]}($row);
			}
		}
		
		if ($row['ignore']) {
			$_SESSION['lsShop']['importFileInfo']['arrImportInfos']['variantsToIgnore'] = $row['productcode'];
			return true;
		}
		
		// Prüfen, ob es ein Produkt mit der Artikelnummer bereits gibt
		$alreadyExistsAsID = false;
		$parentProductID = false;

		$objVariant = Database::getInstance()->prepare("
			SELECT		`id`,
						`pid`
			FROM		`tl_ls_shop_variant`
			WHERE		`lsShopVariantCode` = ?
		")
		->execute($row['productcode']);
		
		if ($objVariant->numRows) {
			$objVariant->first();
			$alreadyExistsAsID = $objVariant->id;
			$_SESSION['lsShop']['importFileInfo']['arrImportInfos']['variantsProductcodeToID'][$row['productcode']] = $alreadyExistsAsID;
			
			$parentProductID = $objVariant->pid;
		}
		
		if (!$parentProductID) {
			if (!isset($_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsProductcodeToID'][$row['parentProductcode']])) {
				return false;
			}
			$parentProductID = $_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsProductcodeToID'][$row['parentProductcode']];
		}
		
		/* ######################################################################################################################
		 * Löschen, sofern gewünscht und Datensatz bereits vorhanden. Falls Datensatz nicht vorhanden, Funktion einfach abbrechen
		 */
		if ($row['delete']) {
			$_SESSION['lsShop']['importFileInfo']['arrImportInfos']['variantsToDelete'] = $row['productcode'];
			if ($alreadyExistsAsID) {
				// Löschen der Varianten zum Produkt
				$this->deleteVariant($alreadyExistsAsID);
			}
			return true;
		}
		/*
		 * ######################################################################################################################
		 */
		
		
		
		// Feldwerte, die nicht einfa#ch direkt eingetragen werden können, sondern in irgendeiner Form übersetzt werden müssen, vorbereiten
		$row['settingsForStockAndDeliveryTime'] = ls_shop_productManagementApiHelper::getDeliveryInfoSetID($row['settingsForStockAndDeliveryTime']);
		$row['settingsForStockAndDeliveryTimeInPreorderPhase'] = ls_shop_productManagementApiHelper::getDeliveryInfoSetID($row['settingsForStockAndDeliveryTimeInPreorderPhase']);
        $row['availableFrom'] = !$row['availableFrom'] ? '' : strtotime($row['availableFrom']);
        $str_configuratorOrCustomizerValue = $row['configurator'];
        $row['configurator'] = ls_shop_productManagementApiHelper::getConfiguratorID($str_configuratorOrCustomizerValue);

        /*
         * The import file does not actually contain a field named "customizer". Instead, the value in the field "configurator"
         * can be interpreted as a reference to either a configurator or a customizer. The field "customizer" will
         * be created right here and, if the field "configurator" contains a reference to a customizer, filled accordingly.
         */
        $row['customizer'] = ls_shop_productManagementApiHelper::getCustomizerLogicFileReference($str_configuratorOrCustomizerValue);
		
		$row['weightType'] = ls_shop_productManagementApiHelper::$modificationTypesTranslationMap[$row['weightType']];
		$row['weightType'] = $row['weightType'] ? $row['weightType'] : '';
		
		$row['moreImages'] = ls_shop_productManagementApiHelper::prepareMoreImages($row['moreImages']);
		
		$row['flex_contents'] = ls_shop_productManagementApiHelper::generateFlexContentsString($row);
		$row['flex_contentsLanguageIndependent'] = ls_shop_productManagementApiHelper::generateFlexContentsStringLanguageIndependent($row);
				
		/*
		 * We count from 0 because we also have to handle the non-group-specific fields
		 */
		for ($i=0; $i <= ls_shop_productManagementApiHelper::$int_numImportableGroupPrices; $i++) {
			$str_multipriceFieldSuffix = $i === 0 ? '' : ('_'.$i);
			
			/*
			 * 'priceForGroups' only exists in multi prices
			 */
			if ($i > 0) {
				$row['priceForGroups'.$str_multipriceFieldSuffix] = ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $row['priceForGroups'.$str_multipriceFieldSuffix]);
			}
			
			$row['priceType'.$str_multipriceFieldSuffix] = ls_shop_productManagementApiHelper::$modificationTypesTranslationMap[$row['priceType'.$str_multipriceFieldSuffix]];
			$row['priceType'.$str_multipriceFieldSuffix] = $row['priceType'.$str_multipriceFieldSuffix] ? $row['priceType'.$str_multipriceFieldSuffix] : '';
			$row['oldPriceType'.$str_multipriceFieldSuffix] = ls_shop_productManagementApiHelper::$modificationTypesTranslationMap[$row['oldPriceType'.$str_multipriceFieldSuffix]];
			$row['oldPriceType'.$str_multipriceFieldSuffix] = $row['oldPriceType'.$str_multipriceFieldSuffix] ? $row['oldPriceType'.$str_multipriceFieldSuffix] : '';
			$row['scalePriceType'.$str_multipriceFieldSuffix] = $row['scalePriceType'.$str_multipriceFieldSuffix] ? $row['scalePriceType'.$str_multipriceFieldSuffix] : 'scalePriceStandalone';
			$row['scalePriceQuantityDetectionMethod'.$str_multipriceFieldSuffix] = $row['scalePriceQuantityDetectionMethod'.$str_multipriceFieldSuffix] ? $row['scalePriceQuantityDetectionMethod'.$str_multipriceFieldSuffix] : 'separatedVariantsAndConfigurations';
			$row['scalePrice'.$str_multipriceFieldSuffix] = ls_shop_productManagementApiHelper::generateScalePriceArray($row['scalePrice'.$str_multipriceFieldSuffix]);
		}
		
		/*
		 * Das Feld 'images' darf nur ein Bild enthalten, kann aber theoretisch auch mehrere mit kommagetrennte Bilder liefern,
		 * was entsprechend unterbunden werden muss. Es wird daher geprüft, ob ein Komma im String enthalten ist. Wenn ja, wird
		 * an den Kommas getrennt und nur der erste Wert verwendet
		 */
		$arrTmp = false;
		if (preg_match('/,/', $row['image'])) {
			$arrTmp = explode(',', $row['image']);
		}
		$row['image'] = is_array($arrTmp[0]) ? trim($arrTmp[0]) : trim($row['image']);
		$row['image'] = $row['image'] ? ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImageFolder']).'/'.$row['image'] : '';
		$objModels = FilesModel::findMultipleByPaths(array($row['image']));
		if ($objModels !== null) {
			$row['image'] = $objModels->first()->uuid;
		} else {
			$row['image'] = null;
		}
		
		$row['propertiesAndValues'] = ls_shop_productManagementApiHelper::preparePropertiesAndValues($row);
		


		if (isset($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingVariantData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingVariantData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingVariantData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$row = $objMccb->{$mccb[1]}($row, $alreadyExistsAsID, $parentProductID);
			}
		}
		
		/* ######################################################################################################################
		 * Update, falls Datensatz vorhanden
		 * 
		 */
		if ($alreadyExistsAsID) {
			$str_addGroupPriceFieldsToQuery = ls_shop_productManagementApiHelper::createGroupPriceFieldsForQuery('variant');
			
			$objUpdateVariant = Database::getInstance()->prepare("
				UPDATE		`tl_ls_shop_variant`
				SET			`title` = ?,
							`alias` = ?,
							`sorting` = ?,
							`shortDescription` = ?,
							`description` = ?,
							`published` = ?,
							`lsShopProductVariantAttributesValues` = ?,
							`lsShopVariantPrice` = ?,
							`lsShopVariantPriceType` = ?,
							`lsShopVariantPriceOld` = ?,
							`lsShopVariantPriceTypeOld` = ?,
							`useOldPrice` = ?,
							`overrideAvailabilitySettingsOfParentProduct` = ?,
							`availableFrom` = ?,
							`preorderingAllowed` = ?,
							`lsShopVariantWeight` = ?,
							`lsShopVariantWeightType` = ?,
							`lsShopVariantQuantityUnit` = ?,
							`lsShopVariantMengenvergleichUnit` = ?,
							`lsShopVariantMengenvergleichDivisor` = ?,
							`lsShopProductVariantMainImage` = ?,
							`lsShopProductVariantMoreImages` = ?,
							`lsShopVariantDeliveryInfoSet` = ?,
							`deliveryInfoSetToUseInPreorderPhase` = ?,
							`configurator` = ?,
							`customizerLogicFile` = ?,
							`flex_contents` = ?,
							`flex_contentsLanguageIndependent` = ?,
							`useScalePrice` = ?,
							`scalePriceType` = ?,
							`scalePriceQuantityDetectionMethod` = ?,
							`scalePriceQuantityDetectionAlwaysSeparateConfigurations` = ?,
							`scalePriceKeyword` = ?,
							`scalePrice` = ?
							".$str_addGroupPriceFieldsToQuery."
				WHERE		`id` = ?
			")
			->limit(1);
			
			$arr_queryParams = array(
				$row['name'], // String, maxlength 255
				ls_shop_productManagementApiHelper::generateVariantAlias($row['name'], $row['alias'], $alreadyExistsAsID),
				$row['sorting'] && $row['sorting'] > 0 ? $row['sorting'] : 0, // int empty = 0
				$row['shortDescription'], // text
				$row['description'], // text
				$row['publish'] ? '1' : '', // 1 or ''
				$row['propertiesAndValues'], // blob, translated, check unclear
				$row['price'] ? $row['price'] : 0, // decimal, empty = 0
				$row['priceType'], // String, maxlength 255
				$row['oldPrice'] ? $row['oldPrice'] : 0, // decimal, empty = 0
				$row['oldPriceType'], // String, maxlength 255
				$row['useOldPrice'] ? '1' : '', // 1 or ''
				$row['overrideAvailabilitySettingsOfParentProduct'] ? '1' : '', // 1 or ''
                $row['availableFrom'], // String, date in format yyyy-mm-dd or empty string
				$row['preorderingAllowed'] ? '1' : '', // 1 or ''
				$row['weight'] ? $row['weight'] : 0, // decimal, empty = 0
				$row['weightType'], // String, maxlength 255
				$row['unit'], // String, maxlength 255
				$row['quantityComparisonUnit'], // String, maxlength 255
				$row['quantityComparisonDivisor'] ? $row['quantityComparisonDivisor'] : 0, // decimal, empty = 0
				$row['image'], // binary(16), translated, check unclear
				$row['moreImages'], // blob, translated, check unclear
				$row['settingsForStockAndDeliveryTime'] ? $row['settingsForStockAndDeliveryTime'] : 0, // int, empty = 0
				$row['settingsForStockAndDeliveryTimeInPreorderPhase'] ? $row['settingsForStockAndDeliveryTimeInPreorderPhase'] : 0, // int, empty = 0
				$row['configurator'] ? $row['configurator'] : 0, // int, empty = 0
                $row['customizer'] ?: null, // binary file reference, empty = null
				$row['flex_contents'], // blob, translated, check unclear
				$row['flex_contentsLanguageIndependent'], // blob, translated, check unclear
				$row['useScalePrice'] ? '1' : '', // 1 or ''
				$row['scalePriceType'], // String, maxlength 255
				$row['scalePriceQuantityDetectionMethod'], // String, maxlength 255
				$row['scalePriceQuantityDetectionAlwaysSeparateConfigurations'] ? '1' : '', // 1 or ''
				$row['scalePriceKeyword'], // String, maxlength 255
				$row['scalePrice'] // blob, translated, check unclear
			);
			
			$arr_queryParams = ls_shop_productManagementApiHelper::addGroupPriceFieldsToQueryParam($arr_queryParams, $row, 'variant');
			
			// Must be the last parameter in the array
			$arr_queryParams[] = $alreadyExistsAsID;
			
			$objUpdateVariant->execute(...array_values($arr_queryParams));

			ls_shop_generalHelper::insertAttributeValueAllocationsInAllocationTable($row['propertiesAndValues'], $alreadyExistsAsID, 1);

			/*
			 * Durchführen der Lagerbestandsänderung, sofern das Feld nicht wirklich leer ist. Eine eingetragene "0" führt auch zur entsprechenden
			 * Lagerbestandsänderung. Enthält der Feldwert etwas anderes als Zahlen von 0-9 und einen Punkt, ein Plus- bzw. ein Minuszeichen, so
			 * wird die Lagerbestandsänderung nicht durchgeführt. Ist ein Plus- oder Minuszeichen enthalten, so wird berechnet, falls nicht, dann
			 * wird der Wert fest eingetragen.
			 */
			if ($row['changeStock'] !== '' && $row['changeStock'] !== null && $row['changeStock'] !== false && !preg_match('/[^0-9+-.]/', $row['changeStock'])) {
				ls_shop_generalHelper::changeStockDirectly('variant', $alreadyExistsAsID, $row['changeStock']);
			}
			
			/*
			 * Spracheinträge schreiben
			 */
			ls_shop_languageHelper::saveMultilanguageValue(
				$alreadyExistsAsID,
				$row['language'],
				'tl_ls_shop_variant_languages',
				array('title', 'alias', 'description', 'lsShopVariantQuantityUnit', 'lsShopVariantMengenvergleichUnit', 'shortDescription', 'flex_contents'),
				array($row['name'], ls_shop_productManagementApiHelper::generateVariantAlias($row['name'], $row['alias'], $alreadyExistsAsID, $row['language']), $row['description'], $row['unit'], $row['quantityComparisonUnit'], $row['shortDescription'], $row['flex_contents'])
			);
			
			if (isset($GLOBALS['MERCONIS_HOOKS']['import_afterUpdatingVariantData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_afterUpdatingVariantData'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['import_afterUpdatingVariantData'] as $mccb) {
					$objMccb = System::importStatic($mccb[0]);
					$objMccb->{$mccb[1]}($alreadyExistsAsID);
				}
			}
			
			return true;
		}
		
		/* ######################################################################################################################
		 * Neu einfügen, falls Datensatz noch nicht vorhanden
		 * 
		 */
		else {
			$str_addGroupPriceFieldsToQuery = ls_shop_productManagementApiHelper::createGroupPriceFieldsForQuery('variant');
			
			$objInsertVariant = Database::getInstance()->prepare("
				INSERT INTO	`tl_ls_shop_variant`
				SET			`tstamp` = ?,
							`pid` = ?,
							`title` = ?,
							`alias` = ?,
							`sorting` = ?,
							`lsShopVariantCode` = ?,
							`shortDescription` = ?,
							`description` = ?,
							`published` = ?,
							`lsShopProductVariantAttributesValues` = ?,
							`lsShopVariantPrice` = ?,
							`lsShopVariantPriceType` = ?,
							`lsShopVariantPriceOld` = ?,
							`lsShopVariantPriceTypeOld` = ?,
							`useOldPrice` = ?,
							`overrideAvailabilitySettingsOfParentProduct` = ?,
							`availableFrom` = ?,
							`preorderingAllowed` = ?,
							`lsShopVariantWeight` = ?,
							`lsShopVariantWeightType` = ?,
							`lsShopVariantQuantityUnit` = ?,
							`lsShopVariantMengenvergleichUnit` = ?,
							`lsShopVariantMengenvergleichDivisor` = ?,
							`lsShopProductVariantMainImage` = ?,
							`lsShopProductVariantMoreImages` = ?,
							`lsShopVariantDeliveryInfoSet` = ?,
							`deliveryInfoSetToUseInPreorderPhase` = ?,
							`configurator` = ?,
							`customizerLogicFile` = ?,
							`flex_contents` = ?,
							`flex_contentsLanguageIndependent` = ?,
							`useScalePrice` = ?,
							`scalePriceType` = ?,
							`scalePriceQuantityDetectionMethod` = ?,
							`scalePriceQuantityDetectionAlwaysSeparateConfigurations` = ?,
							`scalePriceKeyword` = ?,
							`scalePrice` = ?
							".$str_addGroupPriceFieldsToQuery."
			");
			
			$arr_queryParams = array(
				time(),
				$parentProductID,
				$row['name'], // String, maxlength 255
				ls_shop_productManagementApiHelper::generateVariantAlias($row['name'], $row['alias']),
				$row['sorting'] && $row['sorting'] > 0 ? $row['sorting'] : 0, // int empty = 0
				$row['productcode'], // String, maxlength 255
				$row['shortDescription'], // text
				$row['description'], // text
				$row['publish'] ? '1' : '', // 1 or ''
				$row['propertiesAndValues'], // blob, translated, check unclear
				$row['price'] ? $row['price'] : 0, // decimal, empty = 0
				$row['priceType'], // String, maxlength 255
				$row['oldPrice'] ? $row['oldPrice'] : 0, // decimal, empty = 0
				$row['oldPriceType'], // String, maxlength 255
				$row['useOldPrice'] ? '1' : '', // 1 or ''
				$row['overrideAvailabilitySettingsOfParentProduct'] ? '1' : '', // 1 or ''
                $row['availableFrom'], // String, date in format yyyy-mm-dd or empty string
				$row['preorderingAllowed'] ? '1' : '', // 1 or ''
				$row['weight'] ? $row['weight'] : 0, // decimal, empty = 0
				$row['weightType'], // String, maxlength 255
				$row['unit'], // String, maxlength 255
				$row['quantityComparisonUnit'], // String, maxlength 255
				$row['quantityComparisonDivisor'] ? $row['quantityComparisonDivisor'] : 0, // decimal, empty = 0
				$row['image'], // binary(16), translated, check unclear
				$row['moreImages'], // blob, translated, check unclear
				$row['settingsForStockAndDeliveryTime'] ? $row['settingsForStockAndDeliveryTime'] : 0, // int, empty = 0
				$row['settingsForStockAndDeliveryTimeInPreorderPhase'] ? $row['settingsForStockAndDeliveryTimeInPreorderPhase'] : 0, // int, empty = 0
				$row['configurator'] ? $row['configurator'] : 0, // int, empty = 0
                $row['customizer'] ?: null, // binary file reference, empty = null
				$row['flex_contents'], // blob, translated, check unclear
				$row['flex_contentsLanguageIndependent'], // blob, translated, check unclear
				$row['useScalePrice'] ? '1' : '', // 1 or ''
				$row['scalePriceType'], // String, maxlength 255
				$row['scalePriceQuantityDetectionMethod'], // String, maxlength 255
				$row['scalePriceQuantityDetectionAlwaysSeparateConfigurations'] ? '1' : '', // 1 or ''
				$row['scalePriceKeyword'], // String, maxlength 255
				$row['scalePrice'] // blob, translated, check unclear
			);
			
			$arr_queryParams = ls_shop_productManagementApiHelper::addGroupPriceFieldsToQueryParam($arr_queryParams, $row, 'variant');
			
			$objInsertVariant->execute(...array_values($arr_queryParams));
			
			$newVariantID = $objInsertVariant->insertId;
			$_SESSION['lsShop']['importFileInfo']['arrImportInfos']['variantsProductcodeToID'][$row['productcode']] = $newVariantID;

			ls_shop_generalHelper::insertAttributeValueAllocationsInAllocationTable($row['propertiesAndValues'], $newVariantID, 1);
			
			/*
			 * Durchführen der Lagerbestandsänderung, sofern das Feld nicht wirklich leer ist. Eine eingetragene "0" führt auch zur entsprechenden
			 * Lagerbestandsänderung. Enthält der Feldwert etwas anderes als Zahlen von 0-9 und einen Punkt, ein Plus- bzw. ein Minuszeichen, so
			 * wird die Lagerbestandsänderung nicht durchgeführt. Ist ein Plus- oder Minuszeichen enthalten, so wird berechnet, falls nicht, dann
			 * wird der Wert fest eingetragen.
			 */
			if ($row['changeStock'] !== '' && $row['changeStock'] !== null && $row['changeStock'] !== false && !preg_match('/[^0-9+-.]/', $row['changeStock'])) {
				ls_shop_generalHelper::changeStockDirectly('variant', $newVariantID, $row['changeStock']);
			}
			
			/*
			 * Spracheinträge schreiben
			 */
			ls_shop_languageHelper::saveMultilanguageValue(
				$newVariantID,
				$row['language'],
				'tl_ls_shop_variant_languages',
				array('title', 'alias', 'description', 'lsShopVariantQuantityUnit', 'lsShopVariantMengenvergleichUnit', 'shortDescription', 'flex_contents'),
				array($row['name'], ls_shop_productManagementApiHelper::generateVariantAlias($row['name'], $row['alias'], $newVariantID, $row['language']), $row['description'], $row['unit'], $row['quantityComparisonUnit'], $row['shortDescription'], $row['flex_contents'])
			);
			
			if (isset($GLOBALS['MERCONIS_HOOKS']['import_afterInsertingVariantData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_afterInsertingVariantData'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['import_afterInsertingVariantData'] as $mccb) {
					$objMccb = System::importStatic($mccb[0]);
					$objMccb->{$mccb[1]}($newVariantID);
				}
			}
			
			return true;
		}
	}

	protected function processProductLanguageData($row) {
		if (in_array($row['parentProductcode'], $_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsToIgnore'])) {
			$row['ignore'] = 'x';
		}
		
		if (in_array($row['parentProductcode'], $_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsToDelete'])) {
			$row['delete'] = 'x';
		}
		
		if (isset($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingProductLanguageData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingProductLanguageData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingProductLanguageData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$row = $objMccb->{$mccb[1]}($row);
			}
		}
		
		if ($row['ignore']) {
			return true;
		}

		/*
		 * ID des übergeordneten Datensatzes ermitteln
		 */
		if (!isset($_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsProductcodeToID'][$row['parentProductcode']])) {
			return false;
		}
		$parentProductID = $_SESSION['lsShop']['importFileInfo']['arrImportInfos']['productsProductcodeToID'][$row['parentProductcode']];
		
		if ($row['delete']) {
			ls_shop_languageHelper::deleteEntry($parentProductID, 'tl_ls_shop_product_languages', array($row['language']));
			return;
		}
		
		// Feldwerte, die nicht einfach direkt eingetragen werden können, sondern in irgendeiner Form übersetzt werden müssen, vorbereiten
		$row['flex_contents'] = ls_shop_productManagementApiHelper::generateFlexContentsString($row);
	
		if (isset($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingProductLanguageData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingProductLanguageData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingProductLanguageData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$row = $objMccb->{$mccb[1]}($row, $parentProductID);
			}
		}
		
		/*
		 * Datensatz in Sprachtabelle schreiben
		 */
		ls_shop_languageHelper::saveMultilanguageValue(
			$parentProductID,
			$row['language'],
			'tl_ls_shop_product_languages',
			array('title', 'alias', 'keywords', 'description', 'lsShopProductQuantityUnit', 'lsShopProductMengenvergleichUnit', 'shortDescription', 'flex_contents'),
			array($row['name'], ls_shop_productManagementApiHelper::generateProductAlias($row['name'], $row['alias'], $parentProductID, $row['language']), $row['keywords'],$row['description'],$row['unit'],$row['quantityComparisonUnit'],$row['shortDescription'],$row['flex_contents'])
		);
			
		if (isset($GLOBALS['MERCONIS_HOOKS']['import_afterWritingProductLanguageData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_afterWritingProductLanguageData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['import_afterWritingProductLanguageData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$objMccb->{$mccb[1]}($parentProductID);
			}
		}
	}

	protected function processVariantLanguageData($row) {
		if (in_array($row['parentProductcode'], $_SESSION['lsShop']['importFileInfo']['arrImportInfos']['variantsToIgnore'])) {
			$row['ignore'] = 'x';
		}
		
		if (in_array($row['parentProductcode'], $_SESSION['lsShop']['importFileInfo']['arrImportInfos']['variantsToDelete'])) {
			$row['delete'] = 'x';
		}
		
		if (isset($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingVariantLanguageData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingVariantLanguageData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingVariantLanguageData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$row = $objMccb->{$mccb[1]}($row);
			}
		}
		
		if ($row['ignore']) {
			return true;
		}

		/*
		 * ID des übergeordneten Datensatzes ermitteln
		 */
		if (!isset($_SESSION['lsShop']['importFileInfo']['arrImportInfos']['variantsProductcodeToID'][$row['parentProductcode']])) {
			return false;
		}
		$parentProductID = $_SESSION['lsShop']['importFileInfo']['arrImportInfos']['variantsProductcodeToID'][$row['parentProductcode']];
		
		if ($row['delete']) {
			ls_shop_languageHelper::deleteEntry($parentProductID, 'tl_ls_shop_variant_languages', array($row['language']));
			return;
		}
	
		// Feldwerte, die nicht einfach direkt eingetragen werden können, sondern in irgendeiner Form übersetzt werden müssen, vorbereiten
		$row['flex_contents'] = ls_shop_productManagementApiHelper::generateFlexContentsString($row);
	
		if (isset($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingVariantLanguageData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingVariantLanguageData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['import_beforeWritingVariantLanguageData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$row = $objMccb->{$mccb[1]}($row, $parentProductID);
			}
		}
		
		/*
		 * Datensatz in Sprachtabelle schreiben
		 */
		ls_shop_languageHelper::saveMultilanguageValue(
			$parentProductID,
			$row['language'],
			'tl_ls_shop_variant_languages',
			array('title', 'alias', 'description', 'lsShopVariantQuantityUnit', 'lsShopVariantMengenvergleichUnit', 'shortDescription', 'flex_contents'),
			array($row['name'], ls_shop_productManagementApiHelper::generateVariantAlias($row['name'], $row['alias'], $parentProductID, $row['language']), $row['description'], $row['unit'], $row['quantityComparisonUnit'], $row['shortDescription'], $row['flex_contents'])
		);
			
		if (isset($GLOBALS['MERCONIS_HOOKS']['import_afterWritingVariantLanguageData']) && is_array($GLOBALS['MERCONIS_HOOKS']['import_afterWritingVariantLanguageData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['import_afterWritingVariantLanguageData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$objMccb->{$mccb[1]}($parentProductID);
			}
		}
	}

	protected function openImportFile() {
		$this->importFileHandle = null;
		if (($handle = fopen($_SESSION['lsShop']['importFileInfo']['fullFilename'], "rb")) !== FALSE) {
			$this->importFileHandle = $handle;
			return true;
		}
		return false;
	}

	protected function closeImportFile() {
		if ($this->importFileHandle !== null) {
			fclose($this->importFileHandle);
		}
	}
	
	protected function getImportFileRow() {
		if (!isset($_SESSION['lsShop']['importFileInfo']['lastFilePointerPosition'])) {
			$_SESSION['lsShop']['importFileInfo']['lastFilePointerPosition'] = null;
		}
		if ($_SESSION['lsShop']['importFileInfo']['lastFilePointerPosition'] !== null) {
			fseek($this->importFileHandle, $_SESSION['lsShop']['importFileInfo']['lastFilePointerPosition'], SEEK_SET );
		}
		$row = fgetcsv(
			$this->importFileHandle,
			0,
			(isset($GLOBALS['TL_CONFIG']['ls_shop_importCsvDelimiter']) && $GLOBALS['TL_CONFIG']['ls_shop_importCsvDelimiter'] ? $GLOBALS['TL_CONFIG']['ls_shop_importCsvDelimiter'] : ';'),
			(isset($GLOBALS['TL_CONFIG']['ls_shop_importCsvEnclosure']) && $GLOBALS['TL_CONFIG']['ls_shop_importCsvEnclosure'] ? $GLOBALS['TL_CONFIG']['ls_shop_importCsvEnclosure'] : '"'),
			(isset($GLOBALS['TL_CONFIG']['ls_shop_importCsvEscape']) && $GLOBALS['TL_CONFIG']['ls_shop_importCsvEscape'] ? $GLOBALS['TL_CONFIG']['ls_shop_importCsvEscape'] : '\\')
		);
		$_SESSION['lsShop']['importFileInfo']['lastFilePointerPosition'] = ftell($this->importFileHandle);

		if ($row === false) {
			rewind($this->importFileHandle);
			$_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] = 0;
			$_SESSION['lsShop']['importFileInfo']['lastFilePointerPosition'] = null;
			return false;
		}

		if (!isset($_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'])) {
            $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] = 0;
        }

		$_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow']++;

		if ($_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] == 1) {
			$row[0] = $this->rmBOM($row[0]);
		} else if (
		    ($this->bln_importFileHasRowForVersionAndSpecialChars && $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] >= 3)
            || (!$this->bln_importFileHasRowForVersionAndSpecialChars && $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] >= 2)
        ) {
			if (count($_SESSION['lsShop']['importFileInfo']['arrKeys']) == count($row)) {
				$row = array_combine($_SESSION['lsShop']['importFileInfo']['arrKeys'], $row);
			} else {
				$row = null;
			}
		}
		return $row;
	}

	protected function rowIsEmpty($row = array()) {
		$empty = true;
		if (is_array($row)) {
            foreach ($row as $value) {
                if ($value) {
                    $empty = false;
                    break;
                }
            }
        }
		return $empty;
	}

	/*
	 * Diese Funktion erwartet die Angabe einer durchzuführenden Prüfung sowie eines Datensatzes
	 * und prüft daraufhin, ob der Datensatz den entsprechenden Fehler enthält und gibt in diesem 
	 * Fall true zurück. Fehlerprüfungen werden nur bei passenden Datensätzen durchgeführt.
	 */
	protected function checkDataFor($checkFor = '', $row = array()) {
		if (!$checkFor || !is_array($row) || !count($row)) {
			throw new \Exception('insufficient parameters given');
		}
		
		switch ($checkFor) {
			case 'notExistingDataType':
				if (!in_array($row['type'], $this->dataRowTypesInOrderToProcess)) {
					return true;
				}
				break;
				
			case 'missingProductcode':
				if ($row['type'] != 'product' && $row['type'] != 'variant') {
					break;
				}
				if (!$row['productcode']) {
					return true;
				}
				break;
				
			case 'missingProductName':
				if ($row['delete']) {
					break;
				}
				if ($row['type'] != 'product' && $row['type'] != 'productLanguage') {
					break;
				}
				if ($row['name'] === '') {
					return true;
				}
				break;
				
			case 'missingTaxClass':
				if ($row['delete']) {
					break;
				}
				if ($row['type'] != 'product') {
					break;
				}
				if (!ls_shop_productManagementApiHelper::getTaxClassID($row['taxclass'])) {
					return true;
				}
				break;
				
			case 'variantIncorrectParentProductcode':
				if ($row['type'] != 'variant') {
					break;
				}
				if (!in_array($row['parentProductcode'], $_SESSION['lsShop']['importFileInfo']['arrAnalyzingInfo']['product_productcodes'])) {
					return true;
				}
				break;
				
			case 'variantLanguageIncorrectParentProductcode':
				if ($row['type'] != 'variantLanguage') {
					break;
				}
				if (!in_array($row['parentProductcode'], $_SESSION['lsShop']['importFileInfo']['arrAnalyzingInfo']['variant_productcodes'])) {
					return true;
				}
				break;
				
			case 'productLanguageIncorrectParentProductcode':
				if ($row['type'] != 'productLanguage') {
					break;
				}
				if (!in_array($row['parentProductcode'], $_SESSION['lsShop']['importFileInfo']['arrAnalyzingInfo']['product_productcodes'])) {
					return true;
				}
				break;
				
			case 'missingOrWrongLanguagecode':
				if ($row['delete'] && ($row['type'] == 'product' || $row['type'] == 'variant')) {
					break;
				}
			
				if (!$row['language'] || !in_array($row['language'], ls_shop_languageHelper::getAllLanguages())) {
					return true;
				}
				break;
				
			case 'notExistingAttribute':
				if ($row['delete']) {
					break;
				}
				if ($row['type'] != 'variant' && $row['type'] != 'product') {
					break;
				}
				$arrAttributeAndValueAliases = ls_shop_productManagementApiHelper::getAttributeAndValueAliases();
				for ($i=1; $i <= ls_shop_productManagementApiHelper::$int_numImportableAttributesAndValues; $i++) {
					if ($row['property'.$i] && !in_array($row['property'.$i], $arrAttributeAndValueAliases['attributeAliases'])) {
						return true;
					}
				}
				break;
				
			case 'notExistingAttributeValue':
				if ($row['delete']) {
					break;
				}
				if ($row['type'] != 'variant' && $row['type'] != 'product') {
					break;
				}
				$arrAttributeAndValueAliases = ls_shop_productManagementApiHelper::getAttributeAndValueAliases();
				for ($i=1; $i <= ls_shop_productManagementApiHelper::$int_numImportableAttributesAndValues; $i++) {
					if ($row['property'.$i] && !in_array($row['value'.$i], $arrAttributeAndValueAliases['attributeValueAliases'])) {
						return true;
					}
				}
				break;
				
			case 'notMatchingAttributesAndValues':
				if ($row['delete']) {
					break;
				}
				if ($row['type'] != 'variant' && $row['type'] != 'product') {
					break;
				}
				for ($i=1; $i <= ls_shop_productManagementApiHelper::$int_numImportableAttributesAndValues; $i++) {
					
					/*
					 * Translate the attribute's alias into it's id
					 */
					$attributeID = ls_shop_productManagementApiHelper::getAttributeIDForAlias($row['property'.$i]);

					if (!$attributeID) {
						/*
						 * just skip this attribute/value, if no id could be found for the alias,
						 * because detecting nonexistent attribute/values is the job of another
						 * error control routine
						 */
						continue;
					}
					
					/*
					 * Translate the attribute value's alias into it's id
					 */
					$attributeValueID = ls_shop_productManagementApiHelper::getAttributeValueIDForAlias($row['value'.$i]);

					if (!$attributeValueID) {
						/*
						 * just skip this attribute/value, if no id could be found for the alias,
						 * because detecting nonexistent attribute/values is the job of another
						 * error control routine
						 */
						continue;
					}

					if (!ls_shop_generalHelper::checkIfAttributeAndValueBelongTogether($attributeID, $attributeValueID)) {
						return true;
					}
				}
				break;

			case 'notExistingCategory':
				if ($row['delete']) {
					break;
				}
				
				if ($row['type'] != 'product') {
					break;
				}
				
				/*
				 * not existing category if no category value is given at all
				 */
				if (!$row['category']) {
					return true;
				}
				
				$arrCategories = explode(',', $row['category']);
				$arrPageAliases = ls_shop_productManagementApiHelper::getPageAliases();
				
				foreach ($arrCategories as $category) {
					$category = trim($category);
					if (!in_array($category, $arrPageAliases)) {
						return true;
					}					
				}
				
				break;
				
			case 'notExistingPriceType':
				if ($row['delete']) {
					break;
				}
				if ($row['type'] != 'variant') {
					break;
				}
				
				/*
				 * We count from 0 because we also have to check the non-group-specific field
				 */
				for ($i=0; $i <= ls_shop_productManagementApiHelper::$int_numImportableGroupPrices; $i++) {
					/*
					 * If no value is given at all, that's okay because we will
					 * assign a default value in this case during the import
					 */
					if (!$row['priceType'.($i === 0 ? '' : ('_'.$i))]) {
						continue;
					}
					
					if (!array_key_exists($row['priceType'.($i === 0 ? '' : ('_'.$i))], ls_shop_productManagementApiHelper::$modificationTypesTranslationMap)) {
						return true;
					}
				}
				
				return false;
				break;

			case 'notExistingPriceTypeOld':
				if ($row['delete']) {
					break;
				}
				if ($row['type'] != 'variant') {
					break;
				}
				
				
				/*
				 * We count from 0 because we also have to check the non-group-specific field
				 */
				for ($i=0; $i <= ls_shop_productManagementApiHelper::$int_numImportableGroupPrices; $i++) {
					/*
					 * If no value is given at all, that's okay because we will
					 * assign a default value in this case during the import
					 */
					if (!$row['oldPriceType'.($i === 0 ? '' : ('_'.$i))]) {
						continue;
					}
					
					if (!array_key_exists($row['oldPriceType'.($i === 0 ? '' : ('_'.$i))], ls_shop_productManagementApiHelper::$modificationTypesTranslationMap)) {
						return true;
					}
				}
				
				return false;
				break;

			case 'notExistingWeightType':
				if ($row['delete']) {
					break;
				}
				if ($row['type'] != 'variant') {
					break;
				}
				if (!array_key_exists($row['weightType'], ls_shop_productManagementApiHelper::$modificationTypesTranslationMap)) {
					return true;
				}
				break;
				
			case 'notExistingDeliveryInfoType':
				if ($row['delete']) {
					break;
				}
				if ($row['type'] != 'product' && $row['type'] != 'variant') {
					break;
				}
				if (
				    $row['settingsForStockAndDeliveryTime'] && !in_array($row['settingsForStockAndDeliveryTime'], ls_shop_productManagementApiHelper::getDeliveryInfoTypeAliases())
                    || $row['settingsForStockAndDeliveryTimeInPreorderPhase'] && !in_array($row['settingsForStockAndDeliveryTimeInPreorderPhase'], ls_shop_productManagementApiHelper::getDeliveryInfoTypeAliases())
                ) {
					return true;
				}
				break;

			case 'wrongStockValue':
				if ($row['delete']) {
					break;
				}
				if ($row['type'] != 'product' && $row['type'] != 'variant') {
					break;
				}
				if ($row['changeStock'] !== '' && (preg_match('/[^0-9+-.]/', $row['changeStock']) || preg_match('/,/', $row['changeStock']))) {
					return true;
				}
				break;

			case 'missingFlexContentFields':
				foreach (ls_shop_productManagementApiHelper::getImportFlexFieldKeys() as $importField) {
					if (!in_array($importField, $_SESSION['lsShop']['importFileInfo']['arrKeys'])) {
						return true;
					}
				}
				break;

			case 'missingFlexContentFieldsLanguageIndependent':
				foreach (ls_shop_productManagementApiHelper::getImportFlexFieldKeysLanguageIndependent() as $importField) {
					if (!in_array($importField, $_SESSION['lsShop']['importFileInfo']['arrKeys'])) {
						return true;
					}
				}
				break;

			case 'valueInvalid_name':
				if ($row['delete']) {
					break;
				}
				
				return strlen($row['name']) > 255;
				break;

            case 'valueInvalid_availableFrom':
                if ($row['delete']) {
                    break;
                }

                if (!$row['availableFrom']) {
                    return false;
                }

                $arr_dateParts = explode('-', $row['availableFrom']);
                if (count($arr_dateParts) === 3 && checkdate($arr_dateParts[1], $arr_dateParts[2], $arr_dateParts[0])) {
                    return false; // valid date
                } else {
                    return true; // invalid date
                }
                break;


			case 'valueInvalid_sorting':
				if ($row['delete'] || ($row['type'] != 'product' && $row['type'] != 'variant')) {
					break;
				}
				
				return preg_match('[^0-9]', $row['sorting']);
				break;

			case 'valueInvalid_price':
				if ($row['delete'] || ($row['type'] != 'product' && $row['type'] != 'variant')) {
					break;
				}
				
				/*
				 * We count from 0 because we also have to check the non-group-specific field
				 */
				for ($i=0; $i <= ls_shop_productManagementApiHelper::$int_numImportableGroupPrices; $i++) {
					if ($row['price'.($i === 0 ? '' : ('_'.$i))]) {
						if (!preg_match('/^-?\d+(\.\d+)?$/', $row['price'.($i === 0 ? '' : ('_'.$i))])) {
							return true;
						}
					}					
				}
				
				return false;
				break;
			
			case 'valueInvalid_oldPrice':
				if ($row['delete'] || ($row['type'] != 'product' && $row['type'] != 'variant')) {
					break;
				}
				
				/*
				 * We count from 0 because we also have to check the non-group-specific field
				 */
				for ($i=0; $i <= ls_shop_productManagementApiHelper::$int_numImportableGroupPrices; $i++) {
					if ($row['oldPrice'.($i === 0 ? '' : ('_'.$i))]) {
						if (!preg_match('/^-?\d+(\.\d+)?$/', $row['oldPrice'.($i === 0 ? '' : ('_'.$i))])) {
							return true;
						}
					}					
				}
				
				return false;
				break;
			
			case 'valueInvalid_weight':
				if ($row['delete'] || ($row['type'] != 'product' && $row['type'] != 'variant')) {
					break;
				}
				
				if (!$row['weight']) {
					return false;
				}
				
				return !preg_match('/^-?\d+(\.\d+)?$/', $row['weight']);
				break;
			
			case 'valueInvalid_unit':
				if ($row['delete']) {
					break;
				}
				
				return strlen($row['unit']) > 255;
				break;
			
			case 'valueInvalid_quantityComparisonUnit':
				if ($row['delete']) {
					break;
				}
				
				return strlen($row['quantityComparisonUnit']) > 255;
				break;

			case 'productValueInvalid_quantityDecimals':
				if ($row['delete'] || $row['type'] != 'product') {
					break;
				}
				
				if (!$row['quantityDecimals']) {
					return false;
				}
				
				return preg_match('[^0-9]', $row['quantityDecimals']);
				break;
			
			case 'valueInvalid_quantityComparisonDivisor':
				if ($row['delete']) {
					break;
				}
				
				if (!$row['quantityComparisonDivisor']) {
					return false;
				}
				
				return !preg_match('/^-?\d+(\.\d+)?$/', $row['quantityComparisonDivisor']);
				break;
			
			case 'productValueInvalid_template':
				if ($row['delete'] || $row['type'] != 'product') {
					break;
				}
				
				return strlen($row['template']) > 64;
				break;
			
			case 'productValueInvalid_producer':
				if ($row['delete'] || $row['type'] != 'product') {
					break;
				}
				
				return strlen($row['producer']) > 255;
				break;
			
			case 'valueInvalid_productcode':
				if ($row['delete'] || ($row['type'] != 'product' && $row['type'] != 'variant')) {
					break;
				}
				
				return strlen($row['productcode']) > 255;
				break;
				
			case 'valueInvalid_scalePriceType':
				if ($row['delete'] || ($row['type'] != 'product' && $row['type'] != 'variant')) {
					break;
				}
				
				/*
				 * We count from 0 because we also have to check the non-group-specific field
				 */
				for ($i=0; $i <= ls_shop_productManagementApiHelper::$int_numImportableGroupPrices; $i++) {
					if (!empty($row['scalePriceType'.($i === 0 ? '' : ('_'.$i))])) {
						if (!in_array($row['scalePriceType'.($i === 0 ? '' : ('_'.$i))], array('scalePriceStandalone', 'scalePricePercentaged', 'scalePriceFixedAdjustment'))) {
							return true;
						}
					}					
				}
				
				return false;
				break;
				
			case 'valueInvalid_scalePriceQuantityDetectionMethod':
				if ($row['delete'] || ($row['type'] != 'product' && $row['type'] != 'variant')) {
					break;
				}
				
				/*
				 * We count from 0 because we also have to check the non-group-specific field
				 */
				for ($i=0; $i <= ls_shop_productManagementApiHelper::$int_numImportableGroupPrices; $i++) {
					if (!empty($row['scalePriceQuantityDetectionMethod'.($i === 0 ? '' : ('_'.$i))])) {
						if (!in_array($row['scalePriceQuantityDetectionMethod'.($i === 0 ? '' : ('_'.$i))], array('separatedVariantsAndConfigurations', 'separatedVariants', 'separatedProducts', 'separatedScalePriceKeywords'))) {
							return true;
						}
					}					
				}
				
				return false;
				break;
				
			case 'valueInvalid_scalePriceKeyword':
				if ($row['delete'] || ($row['type'] != 'product' && $row['type'] != 'variant')) {
					break;
				}

				/*
				 * We count from 0 because we also have to check the non-group-specific field
				 */
				for ($i=0; $i <= ls_shop_productManagementApiHelper::$int_numImportableGroupPrices; $i++) {
					if (strlen($row['scalePriceKeyword'.($i === 0 ? '' : ('_'.$i))]) > 255) {
						return true;
					}
				}
				
				return false;
				break;
		}
		
		return false;
	}

	/**
	 * Diese Funktion leert das Import-Verzeichnis
	 */
	public function clearImportFolder() {
		if (!$GLOBALS['TL_CONFIG']['ls_shop_standardProductImportFolder']) {
			return;
		}

		$objFolder = new Folder(ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImportFolder']));
		$objFolder->purge();
	}

	/*
	 * Diese Funktion löscht alle Varianten zu einem Produkt
	 */
	protected function deleteVariantsForProduct($productID = false) {
		if (!$productID) {
			throw new \Exception('no product id given.');
		}
		
		$objVariants = Database::getInstance()->prepare("
			SELECT		`id`
			FROM		`tl_ls_shop_variant`
			WHERE		`pid` = ?
		")
		->execute($productID);
		
		while ($objVariants->next()) {
			$this->deleteVariant($objVariants->id);
		}
	}
	
	/*
	 * Diese Funktion löscht eine einzelne Variante
	 */
	protected function deleteVariant($variantID = false) {
		if (!$variantID) {
			throw new \Exception('no variant id given.');
		}
		
		Database::getInstance()->prepare("
			DELETE FROM	`tl_ls_shop_variant`
			WHERE		`id` = ?
		")
		->limit(1)
		->execute($variantID);
	}
	
	/*
	 * Diese Funktion entfernt das BOM (Byte Order Mark) am Anfang eines Strings
	 */
	protected function rmBOM($string) { 
		if(substr($string, 0,3) == pack("CCC",0xef,0xbb,0xbf)) { 
		    $string=substr($string, 3); 
		}
		return $string; 
	}
}