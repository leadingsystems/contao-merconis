<?php

namespace Merconis\Core;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\StringUtil;
use Contao\System;

class InstallerController extends \Controller {
	protected $obj_config = null;
	/*
	 * ****************************************************
	 * ****************************************************
	 * 
	 * FIXME: Vor dem neuen Release muss auf jeden Fall geprüft werden, welche Versionen aktuell bereits draußen sind,
	 * welche Versionsnummer die neue Veröffentlichung wirklich bekommt und entsprechend die Funktionsbezeichnungen
	 * der Konverter-Routinen angepasst/korrigiert werden!
	 * 
	 * ****************************************************
	 * ****************************************************
	 */
	protected $arrMapOldIDToNewID = array(); // In diesem Array wird der Zusammenhang zwischen ursprünglicher ID (Key) und neuer ID (Value) hergestellt
	protected $alreadyExistingRootPageID = null;

	protected $arrVersionHistory = array(
		'2.0.0 stable',
		'2.0.1 stable',
		'2.0.2 stable',
		'2.0.3 stable',
		'2.1.0 stable',
		'2.1.1 stable',
		'2.1.2 stable',
		'2.1.3 stable',
		'2.1.4 stable',
		'2.1.5 stable',
		'2.1.6 stable',
		'2.1.7 stable',
		'2.1.8 stable',
		'2.2.0 stable',
		'2.2.1 stable',
		'3.0.0 stable',
		'4.0.0'
	);
	protected $arrUpdateStepsWithInstructions = array(
// no instructions		'2_0_0_stable_2_0_1_stable',
// no instructions		'2_0_1_stable_2_0_2_stable',
// no instructions		'2_0_2_stable_2_0_3_stable',
		'2_0_3_stable_2_1_0_stable',
		'2_1_0_stable_2_1_1_stable',
		'2_1_1_stable_2_1_2_stable',
		'2_1_2_stable_2_1_3_stable',
		'2_1_3_stable_2_1_4_stable',
		'2_1_4_stable_2_1_5_stable',
		'2_1_5_stable_2_1_6_stable',
		'2_1_6_stable_2_1_7_stable',
		'2_1_7_stable_2_1_8_stable',
		'2_1_8_stable_2_2_0_stable',
		'2_2_0_stable_2_2_1_stable',
		'2_2_1_stable_3_0_0_stable',
		'3_0_0_stable_4_0_0_stable',
	);

	protected $pathToThemePreviewImages = 'vendor/leadingsystems/contao-merconis/src/Resources/contao/installerResources/merconisfiles/themes/theme%s/misc/previewImage.jpg';


	public function __construct() {
		$this->obj_config = \Config::getInstance();
		parent::__construct();
		$this->import('Files');
		$this->import('BackendUser');

		if (\Input::get('merconisThemeRepositoryMode')) {
			$_SESSION['lsShop']['merconisThemeRepositoryMode'] = \Input::get('merconisThemeRepositoryMode');
		}
	}

	public function __get($what) {
		switch ($what) {
			case 'availableThemes':
				return $this->getAvailableThemes();
				break;

			default:
				## cc3a ##
				return parent::__get($what);
				## cc3e ##
				break;
		}
	}

	public function getInstallationStatus() {
        if (!isset($GLOBALS['TL_CONFIG']['ls_shop_installedCompletely']) || !$GLOBALS['TL_CONFIG']['ls_shop_installedCompletely']) {
            $statusStatic = 'shopInstallScriptNotFinished';
        } else {
            $statusStatic = 'complete';
        }

		if ($statusStatic == 'complete') {
			return $statusStatic;
		}

		/*
		 * Check if there are signs of an invalid system status that makes
		 * the installation of merconis with the installer impossible
		 */
		if ($statusStatic != 'complete') {
			if (!$this->checkIfInstallationPossible()) {
				return 'invalidSystemStatus';
			}
		}

		$arrStatus = array(
			'languageSelectorDBOkay' => false,
			'shopDBOkay' => false,
			'wholeDBOkay' => false,
			'noApiKey' => false,
			'rootPageExists' => false
		);

		if ($this->ls_getRootPageID()) {
			$arrStatus['rootPageExists'] = true;
		}

		if (\Database::getInstance()->fieldExists('ls_cnc_languageSelector_correspondingMainLanguagePage', 'tl_page')) {
			$arrStatus['languageSelectorDBOkay'] = true;
		}

		if (\Database::getInstance()->tableExists('tl_ls_shop_orders')) {
			$arrStatus['shopDBOkay'] = true;
		}

		if (
			$arrStatus['shopDBOkay']
			&&	$arrStatus['languageSelectorDBOkay']
		) {
			$arrStatus['wholeDBOkay'] = true;
		}

		if (
		    !isset($GLOBALS['TL_CONFIG']['ls_api_key'])
            || !$GLOBALS['TL_CONFIG']['ls_api_key']
        ) {
		    $arrStatus['noApiKey'] = true;
        }

		return $arrStatus;
	}

	/*
	 * Before the integration of the theme selection in the installer, this function checked for conflicts that were
	 * completely depending on files that are now definitely part of the selected theme. Therefore these checks must
	 * happen later, when the theme to install has been selected. Anyway, there might be some conflicts that could be
	 * detected before theme selection and if so, we could use this function which therefore remains intact but without
	 * actual checks. This means that this function gets called in the right time and if it returns false after whatever
	 * check it's going to perform, an error message will be displayed to the user.
	 */
	protected function checkIfInstallationPossible() {
//        System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Check if installation is possible', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
		$blnPossible = true;

		return $blnPossible;
	}

	protected function checkIfThemeCanBeInstalled() {
        System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Check if theme can be installed', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
        $blnPossible = true;

		/*
		 * Check if the theme has been selected correctly
		 */
		if (
			!$_SESSION['lsShop']['installer_selectedTheme']['id']
			||	!$_SESSION['lsShop']['installer_selectedTheme']['srcPath']
			||	!is_dir(TL_ROOT.'/'.$_SESSION['lsShop']['installer_selectedTheme']['srcPath'])
			||	!$_SESSION['lsShop']['installer_selectedTheme']['srcPathTemplates']
		) {
            System::getContainer()->get('monolog.logger.contao')->info(
			    "MERCONIS INSTALLER: The theme has not been selected correctly. Please try again and contact the MERCONIS support if it still does not work.
			    ||
                \$_SESSION['lsShop']['installer_selectedTheme']['id'] is \"".$_SESSION['lsShop']['installer_selectedTheme']['id']."\"
                ||
                \$_SESSION['lsShop']['installer_selectedTheme']['srcPath'] is \"".$_SESSION['lsShop']['installer_selectedTheme']['srcPath']."\" (".(is_dir(TL_ROOT.'/'.$_SESSION['lsShop']['installer_selectedTheme']['srcPath']) ? 'exists' : 'does not exist').")
                ||
                \$_SESSION['lsShop']['installer_selectedTheme']['srcPathTemplates'] is \"".$_SESSION['lsShop']['installer_selectedTheme']['srcPathTemplates']."\"
			    ", ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
			$blnPossible = false;
		}

		/*
		 * Abort the check if the theme has not been selected correctly. In this case no other check is possible.
		 */
		if (!$blnPossible) {
			return $blnPossible;
		}


		/*
		 * Check if the data files are okay
		 */
		if (!file_exists(TL_ROOT.'/'.$_SESSION['lsShop']['installer_selectedTheme']['srcPathExportTablesDat'])) {
            System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: File "exportTables.dat" not found. Installation impossible.', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
			$blnPossible = false;
		} else {
			$arrExportTables = StringUtil::deserialize(file_get_contents(TL_ROOT.'/'.$_SESSION['lsShop']['installer_selectedTheme']['srcPathExportTablesDat']));

			if (!is_array($arrExportTables)) {
                System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: File "exportTables.dat" is corrupt. Installation impossible.', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
				$blnPossible = false;
			}
		}


		if (!file_exists(TL_ROOT.'/'.$_SESSION['lsShop']['installer_selectedTheme']['srcPathExportLocalconfigDat'])) {
            System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: File "exportLocalconfig.dat" not found. Installation impossible.', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
			$blnPossible = false;
		} else {
			$arrExportLocalconfig = StringUtil::deserialize(file_get_contents(TL_ROOT.'/'.$_SESSION['lsShop']['installer_selectedTheme']['srcPathExportLocalconfigDat']));

			if (!is_array($arrExportLocalconfig)) {
                System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: File "exportLocalconfig.dat" is corrupt. Installation impossible.', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
				$blnPossible = false;
			}
		}

		/*
		 * Abort the check if the data files are not okay. In this case no other check is possible.
		 */
		if (!$blnPossible) {
			return $blnPossible;
		}


		/*
		 * Check if there are UUID conflicts in tl_files
		 */
		if (is_array($arrExportTables['tl_files'])) {
			$blnUuidConflictInTlFilesDetected = false;
			foreach ($arrExportTables['tl_files'] as $row) {
				$objCheckTlFilesRecordWithUUID = \Database::getInstance()->prepare("
					SELECT		*
					FROM		`tl_files`
					WHERE		`uuid` = ?
				")
					->execute($row['uuid']);

				if ($objCheckTlFilesRecordWithUUID->numRows) {
					$blnUuidConflictInTlFilesDetected = true;

					while ($objCheckTlFilesRecordWithUUID->next()) {
                        System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: row in tl_files with id '.$objCheckTlFilesRecordWithUUID->id.' (path: '.$objCheckTlFilesRecordWithUUID->path.') already has the UUID '.StringUtil::binToUuid($row['uuid']), ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
					}
				}
			}

			if ($blnUuidConflictInTlFilesDetected) {
                System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Installation impossible because of a uuid conflict in tl_files.', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
				$blnPossible = false;
			}
		}

		return $blnPossible;
	}

	protected function ls_getRootPageID() {
		/*
		 * Ermitteln der erstbesten Root-Seite
		 */
		$rootID = 0;
		$objRootpages = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_page`
			WHERE		`type` = 'root'
		")
			->execute();
		if ($objRootpages->numRows) {
			while ($objRootpages->next()) {
				if ($objRootpages->fallback || !$rootID) {
					$rootID = $objRootpages->id;
				}
			}
		}

		return $rootID;
	}

	public function shopInstallation() {
		if ($this->getInstallationStatus() == 'complete') {
			return;
		}
		if (!\Input::get('lsShopInstallationStep')) {
			return;
		}

		switch (\Input::get('lsShopInstallationStep')) {
			case 1:
                ls_shop_generalHelper::purgeContaoCache();

                \Config::persist('ls_shop_installedCompletely', false);

                \Controller::redirect('contao?do=ls_shop_dashboard');
			    break;

			case 2:
                ls_shop_generalHelper::purgeContaoCache();

				if (\Input::post('FORM_SUBMIT') && \Input::post('FORM_SUBMIT') == 'installer_themeSelection') {
					if (!\Input::post('installer_selectedTheme')) {
						$_SESSION['lsShop']['noThemeSelected'] = true;
						\Controller::redirect('contao?do=ls_shop_dashboard');
					}

					$arrThemeIDAndVersion = explode('|', \Input::post('installer_selectedTheme'));

					$_SESSION['lsShop']['installer_selectedTheme']['id'] = $arrThemeIDAndVersion[0];
					$_SESSION['lsShop']['installer_selectedTheme']['version'] = $arrThemeIDAndVersion[1];
					$_SESSION['lsShop']['installer_selectedTheme']['srcPath'] = 'vendor/leadingsystems/contao-merconis/src/Resources/contao/installerResources/merconisfiles/themes/theme'.$_SESSION['lsShop']['installer_selectedTheme']['id'];
					$_SESSION['lsShop']['installer_selectedTheme']['templateFolderName'] = 'merconisTemplatesTheme'.$_SESSION['lsShop']['installer_selectedTheme']['id'];
					$_SESSION['lsShop']['installer_selectedTheme']['srcPathTemplates'] = $_SESSION['lsShop']['installer_selectedTheme']['srcPath'].'/'.$_SESSION['lsShop']['installer_selectedTheme']['templateFolderName'];
					$_SESSION['lsShop']['installer_selectedTheme']['srcPathExportTablesDat'] = $_SESSION['lsShop']['installer_selectedTheme']['srcPath'].'/data/exportTables.dat';
					$_SESSION['lsShop']['installer_selectedTheme']['srcPathExportLocalconfigDat'] = $_SESSION['lsShop']['installer_selectedTheme']['srcPath'].'/data/exportLocalconfig.dat';
				}

				/*
				 * Download the theme if we are in repository mode
				 */
				if (!isset($_SESSION['lsShop']['themeSource']) || $_SESSION['lsShop']['themeSource'] == 'repository') {
					$this->downloadThemeFromRepository();
				}

				if (isset($_SESSION['lsShop']['installer_selectedTheme'])) {
					if (!$this->checkIfThemeCanBeInstalled()) {
                        System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Installation not possible with theme '.$_SESSION['lsShop']['installer_selectedTheme']['id'], ['contao' => new ContaoContext('MERCONIS MESSAGES', TL_MERCONIS_ERROR)]);

						unset($_SESSION['lsShop']['installer_selectedTheme']);
						$_SESSION['lsShop']['selectedThemeCanNotBeInstalled'] = true;
						\Controller::redirect('contao?do=ls_shop_dashboard');
					}
				}

				/*
				 * Kopieren der Theme-Templates
				 */
				if (file_exists(TL_ROOT.'/'.$_SESSION['lsShop']['installer_selectedTheme']['srcPathTemplates']) && !file_exists(TL_ROOT.'/templates/'.$_SESSION['lsShop']['installer_selectedTheme']['templateFolderName'])) {
                    System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Copying theme templates to templates folder', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_INSTALLER)]);
					$this->dirCopy($_SESSION['lsShop']['installer_selectedTheme']['srcPathTemplates'], 'templates/'.$_SESSION['lsShop']['installer_selectedTheme']['templateFolderName']);
				} else {
                    System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Not copying theme templates to templates folder', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_INSTALLER)]);
				}

				/*
				 * Eintragen der Grundeinstellungen in localconfig. Teilweise müssen die Werte später
				 * im Installationsprozess noch durch die richtigen Werte (ID-Zuordnungen) ersetzt werden.
				 */
				$arrExportLocalconfig = StringUtil::deserialize(file_get_contents(TL_ROOT.'/'.$_SESSION['lsShop']['installer_selectedTheme']['srcPathExportLocalconfigDat']));

                System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Inserting MERCONIS configuration values in localconfig.php', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_INSTALLER)]);

				foreach ($arrExportLocalconfig as $k => $v) {
					/*
					 * Deal with the csv escape character which would break the localconfig
					 * if it was just an unescaped backslash. So, essentially, we just look
					 * if the value is a backslash, and if it is, we add another backslash
					 * to escape it.
					 */
					if (version_compare(VERSION . '.' . BUILD, '3.2.9', '<')) {
						if ($k == 'ls_shop_importCsvEscape') {
							$v = $v == '\\' ? '\\\\' : $v;
						}
					}

					if ($k == 'ls_shop_installedVersion') {
						$merconisVersion = ls_shop_generalHelper::getMerconisFilesVersion();
						$v = $merconisVersion;
					}

					$this->obj_config->update("\$GLOBALS['TL_CONFIG']['".$k."']", $v);
				}

				/*
				 * Validate LaFP installation with checksum to prevent conflicts with already existing records
				 */
                eval('$u = '.pack('H*', '6465636865782874696d65282929').'; '.pack('H*', '24746869732d3e6f626a5f636f6e6669672d3e75706461746528225c24474c4f42414c535b27544c5f434f4e464947275d5b27222e7061636b2827482a272c2027366436353732363336663665363937333566373336353732373636393633363534653735366436323635373227292e22275d222c20737472746f757070657228737562737472286d6435282475292c20302c2033292e247529293b'));

				\Controller::redirect('contao?do=ls_shop_dashboard&lsShopInstallationStep=3');
				break;

			case 3:
                $this->import('Automator');

                ls_shop_generalHelper::purgeContaoCache();

				$arrExportTables = StringUtil::deserialize(file_get_contents(TL_ROOT.'/'.$_SESSION['lsShop']['installer_selectedTheme']['srcPathExportTablesDat']));

				$this->lsShopImportTables($arrExportTables);

				$this->restoreForeignKeyRelations();

				$this->updateInsertTagCorrelations__insert_module();

				$this->moveMerconisInstallerRedirectionsPage();

				$str_tmp_originalFileSyncExcludeValue = $GLOBALS['TL_CONFIG']['fileSyncExclude'] ? $GLOBALS['TL_CONFIG']['fileSyncExclude'] : '';

				$this->obj_config->update("\$GLOBALS['TL_CONFIG']['fileSyncExclude']", $str_tmp_originalFileSyncExcludeValue.',merconisfiles');
				$this->obj_config->save();
				$this->obj_config->preload();

				$this->copyFiles();

				$this->deleteUnnecessaryThemeFiles();

				$this->obj_config->update("\$GLOBALS['TL_CONFIG']['fileSyncExclude']", $str_tmp_originalFileSyncExcludeValue);

				$this->preventLanguageFallbackConflict();

				$this->obj_config->update("\$GLOBALS['TL_CONFIG']['ls_shop_installedCompletely']", true);

                System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Setting installation complete flag in localconfig.php', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_INSTALLER)]);

                $this->Automator->generateSymlinks();

                \Controller::redirect('contao?do=ls_shop_dashboard');
				break;
		}
	}

	/**
	 * Diese Funktion prüft, ob durch das Einfügen der MERCONIS-Seitenbäume ein Konflikt durch ein doppeltes Fallback-Flag
	 * entstanden ist und entfernt ggf. das Fallback-Flag bei der MERCONIS-Hauptsprachseite.
	 */
	protected function preventLanguageFallbackConflict() {
		$objPagesWithoutDomainAndWithLanguageFallbackFlag = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_page`
			WHERE		`type` = ?
				AND		`dns` = ?
				AND		`fallback` = ?
		")
			->execute('root', '', '1');

		if ($objPagesWithoutDomainAndWithLanguageFallbackFlag->numRows > 1) {
			/*
			 * Es existiert mehr als eine Seite, die keinen Domaineintrag und ein gesetztes Sprachen-Fallback-Flag hat.
			 * In diesem Fall wird das Flag in der MERCONIS-Seite entfernt
			 */
			$objUpdate = \Database::getInstance()->prepare("
				UPDATE		`tl_page`
				SET			`fallback` = ?
				WHERE		`alias` = ?
			")
				->limit(1)
                /*
                 * bug: there is no more alias with the entry 'merconis-root-page-main-language'
                 * fallback will not be removed
                 */
				->execute('', 'merconis-root-page-main-language');
		}
	}

	/**
	 * Diese Funktion sucht Insert-Tags, die Module einbinden, und aktualisiert die dort angegebenen
	 * Modul-IDs, damit sie gültige Referenzen zu den nach dem Import neuen IDs der Module ergeben.
	 */
	protected function updateInsertTagCorrelations__insert_module() {
		$pattern = '/(\{\{insert_module::)(.*)(\}\})/siU';

		$arr_tablesToConsider = array('tl_module', 'tl_content');

		foreach ($arr_tablesToConsider as $str_tableName) {
		    if (!isset($this->arrMapOldIDToNewID[$str_tableName]) || !is_array($this->arrMapOldIDToNewID[$str_tableName])) {
		        continue;
            }

            foreach ($this->arrMapOldIDToNewID[$str_tableName] as $int_insertedElementId) {
                $obj_dbres_recordsToHandle = \Database::getInstance()
                    ->prepare("
                        SELECT		*
                        FROM		`" . $str_tableName . "`
                        WHERE		`id` = ?
                    ")
                    ->limit(1)
                    ->execute($int_insertedElementId);

                $arr_currentRecordToHandle = $obj_dbres_recordsToHandle->first()->row();

                $setStatement = '';
                foreach ($arr_currentRecordToHandle as $fieldName => $value) {
                    preg_match_all($pattern, $value, $matches);
                    if (is_array($matches[2]) && count($matches[2])) {
                        foreach ($matches[2] as $key => $oldModuleID) {
                            $insertTagToReplace = '/\{\{insert_module::'.$oldModuleID.'\}\}/siU';

                            /*
                             * Get the new module id to replace the old one used in the original insert tag
                             * and then place it in the replacement string.
                             *
                             * The usage of 'tl_module' here has nothing to do with the tables to consider
                             * when looking for occurrences of insert tags to update! So even if we currently
                             * handle insert tags in another table, we still have to get a new module ID for
                             * an old one and find it in the mapping array for tl_module.
                             */
                            $insertTagNew = $matches[1][$key] . '--' . $this->arrMapOldIDToNewID['tl_module'][$oldModuleID] . '--' . $matches[3][$key];

                            $arr_currentRecordToHandle[$fieldName] = preg_replace($insertTagToReplace, $insertTagNew, $arr_currentRecordToHandle[$fieldName]);
                        }

                        $arr_currentRecordToHandle[$fieldName] = preg_replace('/(\{\{insert_module::)(--)(.*)(--)(\}\})/siU', '$1$3$5', $arr_currentRecordToHandle[$fieldName]);
                    }

                    if ($setStatement) {
                        $setStatement .= ",\r\n";
                    }
                    $setStatement .= "`".$fieldName."` = ?";
                }

                $arrQueryValues = $arr_currentRecordToHandle;
                $arrQueryValues[] = $arr_currentRecordToHandle['id'];

                $objUpdate = \Database::getInstance()
                    ->prepare("
                        UPDATE		`" . $str_tableName . "`
                        SET			" . $setStatement . "
                        WHERE		`id` = ?
                    ")
                    ->limit(1)
                    ->execute($arrQueryValues);
            }
        }
	}

	protected function copyFiles() {
		$targetPath = 'files/merconisfiles';
        System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Copying Merconis files to '.$targetPath, ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_INSTALLER)]);
		$this->dirCopy('vendor/leadingsystems/contao-merconis/src/Resources/contao/installerResources/merconisfiles', $targetPath);
		$this->rmdirRecursively(TL_ROOT.'/vendor/leadingsystems/contao-merconis/src/Resources/contao/installerResources');
	}

	protected function deleteUnnecessaryThemeFiles() {
        System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Deleting unnecessary theme files for theme '.$_SESSION['lsShop']['installer_selectedTheme']['id'], ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_INSTALLER)]);

		if (!isset($_SESSION['lsShop']['installer_selectedTheme']['id']) || !$_SESSION['lsShop']['installer_selectedTheme']['id']) {
			return;
		}


		/*
		 * Delete theme folders that do not belong to the selected and installed theme. For example, if a theme other
		 * than basic has been installed, the basic theme folder must be deleted.
		 */
		$arrThemes = scandir(TL_ROOT.'/files/merconisfiles/themes');

		if (is_array($arrThemes)) {
			foreach($arrThemes as $themeFolder) {
				if ($themeFolder == '.' || $themeFolder == '..') {
					continue;
				}

				if ($themeFolder != 'theme'.$_SESSION['lsShop']['installer_selectedTheme']['id']) {
					$this->rmdirRecursively(TL_ROOT.'/files/merconisfiles/themes/'.$themeFolder);
				}
			}
		}

		/*
		 * If the selected and currently installed theme comes with its own template folder,
		 * this folder must be deleted now, because this template folder has already been moved
		 * to the contao templates folder and it might be irritating if it still existed in
		 * the theme folder
		 */
		$unnecessaryTemplatesFolder = TL_ROOT.'/files/merconisfiles/themes/theme'.$_SESSION['lsShop']['installer_selectedTheme']['id'].'/'.$_SESSION['lsShop']['installer_selectedTheme']['templateFolderName'];
		if (file_exists($unnecessaryTemplatesFolder) && is_dir($unnecessaryTemplatesFolder)) {
			$this->rmdirRecursively($unnecessaryTemplatesFolder);
		}
	}

	protected function rmdirRecursively($dir = null) {
		if (!$dir || strpos($dir, 'merconisfiles') === false) {
			return;
		}

		if (is_dir($dir)) {
			$objects = scandir($dir);

			foreach ($objects as $object) {
				if ($object == "." || $object == "..") {
					continue;
				}

				if (is_dir($dir."/".$object)) {
					$this->rmdirRecursively($dir."/".$object);
				} else {
					unlink($dir."/".$object);
				}
			}

			rmdir($dir);
		}
	}

	protected function dirCopy($src, $dest) {
		if (!file_exists(TL_ROOT.'/'.$src) || file_exists(TL_ROOT.'/'.$dest)) {
			return false;
		}

		if (is_file(TL_ROOT.'/'.$src)) {
			$objFile = new \File($src);
			$objFile->copyTo($dest);
			return;
		}

		if (is_dir(TL_ROOT.'/'.$src)) {
			$objNewDir = new \Folder($dest);
			$sourceHandle = opendir(TL_ROOT.'/'.$src);
			while ($file = readdir($sourceHandle)) {
				if ($file == '.' || $file == '..') {
					continue;
				}
				$this->dirCopy($src.'/'.$file, $dest.'/'.$file);
			}
		}
	}

	/**
	 * Verschieben der Merconis-Installer-Weiterleitung in die bereits vor dem Daten-Import existierende Root-Page.
	 * Zusätzlich wird der Sorting-Wert möglichst groß gewählt, um zu verhindern, dass diese Weiterleitungsseite an
	 * erster Position unterhalb der bestehenden Root-page eingehängt wird.
	 */
	protected function moveMerconisInstallerRedirectionsPage() {
		if ($this->alreadyExistingRootPageID) {
            System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: moving MERCONIS redirection page in already existing root page with id '.$this->alreadyExistingRootPageID, ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_INSTALLER)]);

			\Database::getInstance()->prepare("
				UPDATE		`tl_page`
				SET			`pid` = ?,
							`sorting` = ?
				WHERE		`alias` = ?
			")
				->limit(1)
				->execute($this->alreadyExistingRootPageID, 9999999, 'merconis-installer-redirection');
		} else {
            System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Removing MERCONIS redirection page because it is is not required in this installation', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_INSTALLER)]);

			\Database::getInstance()->prepare("
				DELETE FROM	`tl_page`
				WHERE		`alias` = ?
			")
				->limit(1)
				->execute('merconis-installer-redirection');
		}
	}

	/**
	 * In dieser Funktion werden alle neu eingefügten Datensätze durchlaufen
	 * und daraufhin geprüft, ob irgendwelche ForeignKey-Beziehungen bestehen.
	 * Ist dies der Fall, so werden diese Beziehungen wieder hergestellt.
	 *
	 * FIXME: Achtung, bislang werden hier nur IDs als foreignKey unterstützt,
	 * möglicherweise werden auch mal Aliase als foreignKey relevant!
	 */
	protected function restoreForeignKeyRelations() {
		// get the foreignKey relations
		$arrRelations = $this->lsShopGetDatabaseRelations();

		/*
		 * Es werden nun alle Relationen einzeln abgearbeitet. Zu jeder Relation werden alle zu korrigierenden
		 * Datensätze ausgelesen, also die Datensätze, die in der entsprechenden Tabelle neu eingefügt wurden.
		 * Die neu eingefügten Datensätze, sind jene, die im Array $this->arrMapOldIDToNewID enthalten sind.
		 */
		\LeadingSystems\Helpers\lsErrorLog('$this->arrMapOldIDToNewID', $this->arrMapOldIDToNewID, 'lslog_14');
		foreach ($arrRelations as $relation) {
			\LeadingSystems\Helpers\lsErrorLog('$relation', $relation, 'lslog_14');

			if ($relation['pTable'] == 'localconfig') { // Es handelt sich um eine Relation zur localconfig
				$oldForeignKey = $GLOBALS['TL_CONFIG'][$relation['pField']];
				$newForeignKey = $this->getNewForeignKey($relation, $oldForeignKey);

				/*
				 * Eintragen des neuen foreignKey in localconfig
				 */
				$this->obj_config->update("\$GLOBALS['TL_CONFIG']['".$relation['pField']."']", $newForeignKey);

			} else { // Es handelt sich um eine Relation zweier DB-Tabellen

				// Alle neu eingefügten Datensätze der aktuellen pTable werden ausgelesen
				if (is_array($this->arrMapOldIDToNewID[$relation['pTable']])) {
					foreach ($this->arrMapOldIDToNewID[$relation['pTable']] as $pTableRowID) {
						$objRow = \Database::getInstance()->prepare("
							SELECT		*
							FROM		`" . $relation['pTable'] . "`
							WHERE		`id` = ?
						")
							->execute($pTableRowID);

						if (!$objRow->numRows) {
							continue;
						}

						// Auslesen der bislang hinterlegten Zuordnung
						$oldForeignKey = $objRow->{$relation['pField']};
						\LeadingSystems\Helpers\lsErrorLog('pTableRow', $objRow->row(), 'lslog_14');
						\LeadingSystems\Helpers\lsErrorLog('$oldForeignKey', $oldForeignKey, 'lslog_14');

						$newForeignKey = $this->getNewForeignKey($relation, $oldForeignKey);
						$newForeignKey = $newForeignKey ? $newForeignKey : 0;

						/*
						 * Eintragen des neuen foreignKeys
						 */
						$objUpdate = \Database::getInstance()->prepare("
							UPDATE		`" . $relation['pTable'] . "`
							SET			`" . $relation['pField'] . "` = ?
							WHERE		`id` = ?
						")
							->limit(1)
							->execute($newForeignKey, $pTableRowID);
						\LeadingSystems\Helpers\lsErrorLog('$objUpdate->query:', $objUpdate->query, 'lslog_14');
					}
				}
			}
		}
	}

	protected function getNewForeignKey($relation, $oldForeignKey) {
		switch ($relation['relationType']) {
			case 'single': // Der ForeignKey ist ein einzelner Wert
				\LeadingSystems\Helpers\lsErrorLog('single!', '', 'lslog_14');
				/*
				 * Nun wird ermittelt, welches der neue foreignKey ist. Hierfür wird im Array $this->arrMapOldIDToNewID
				 * im Key für die entsprechende cTable (also die verknüpfte Tabelle) nachgeschaut, was der neue foreignKey
				 * zum alten foreignKey ist
				 */
				$newForeignKey = $this->arrMapOldIDToNewID[$relation['cTable']][$oldForeignKey];
				\LeadingSystems\Helpers\lsErrorLog('$newForeignKey = $this->arrMapOldIDToNewID['.$relation['cTable'].']['.$oldForeignKey.'];', $newForeignKey, 'lslog_14');
				break;

			case 'array': // Der ForeignKey ist ein (serialisiertes) Array
				\LeadingSystems\Helpers\lsErrorLog('array!', '', 'lslog_14');
				$arrOldForeignKeys =  is_array($oldForeignKey) ? $oldForeignKey : StringUtil::deserialize($oldForeignKey);
				$arrNewForeignKeys = array();
				if (is_array($arrOldForeignKeys)) {
					foreach ($arrOldForeignKeys as $key => $oldForeignKey) {
						/*
						 * Wichtig: Der ermittelte foreignKey wird im serialisierten Array explizit als String gespeichert. Dies ist bei der Seitenauswahl
						 * für Produkte wichtig, da die Quotes, die beim Speichern eines Wertes in String-Form im serialisierten Array verwendet werden,
						 * zur Erkennung der Produkt-Seitenzuordnungen wichtig sind!
						 */
						$arrNewForeignKeys[$key] = strval($this->arrMapOldIDToNewID[$relation['cTable']][$oldForeignKey]);

						\LeadingSystems\Helpers\lsErrorLog('$arrNewForeignKeys['.$key.'] = $this->arrMapOldIDToNewID['.$relation['cTable'].']['.$oldForeignKey.'];', $arrNewForeignKeys[$key], 'lslog_14');
					}
				} else {
					\LeadingSystems\Helpers\lsErrorLog('old foreign key is not an array', $relation, 'lslog_14');
					\LeadingSystems\Helpers\lsErrorLog('old foreign key: ', $arrOldForeignKeys, 'lslog_14');
				}
				$newForeignKey = serialize($arrNewForeignKeys);
				break;

			case 'special': // Der ForeignKey ist in einem speziellen Format gespeichert, der gesondert gehandhabt werden muss
				\LeadingSystems\Helpers\lsErrorLog('special!', '', 'lslog_14');
				switch ($relation['pTable']) {
					case 'tl_layout':
						switch ($relation['pField']) {
							case 'modules':
								/*
								 * Spezialfall: Modul-Zuordnungen in tl_layout sind kein einfaches Array. Hier werden stattdessen Module und ihre Zuordnung
								 * zu Inhaltsbereichen komplex festgehalten. Entsprechend ist eine Sonderbehandlung nötig, um die alten Modul-IDs aus dem
								 * ursprünglichen foreignKey auszulesen, die neuen Modul-IDs zu ermitteln und den korrekten foreignKey mit den neuen
								 * Modul-IDs wieder zu erstellen.
								 */
								$arrModulesInLayout = is_array($oldForeignKey) ? $oldForeignKey : StringUtil::deserialize($oldForeignKey);
								foreach ($arrModulesInLayout as $k => $v) {
									if (!$v['mod']) {
										continue;
									}
									$oldForeignKey = $v['mod'];
									$arrModulesInLayout[$k]['mod'] = strval($this->arrMapOldIDToNewID[$relation['cTable']][$oldForeignKey]);
								}
								$newForeignKey = serialize($arrModulesInLayout);
								break;
						}
						break;
				}
				break;

			default:
				throw new \Exception('unsupported relation type given');
				break;
		}
		return $newForeignKey;
	}

	/*
	 * Diese Funktion importiert die Tabelleninhalte und hält die Zuordnung
	 * von ursprünglicher ID zu neuer ID fest.
	 */
	protected function lsShopImportTables($arrImport) {
		// Festhalten der ID der bereits vor dem Datenimport existierenden Root-Page
		$this->alreadyExistingRootPageID = $this->ls_getRootPageID();

        System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Importing tables ', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_INSTALLER)]);

		/*
		 * Make sure that 'tl_page' is the first element in the array because it is important that
		 * the pages are inserted first.
		 */
		$tmpArrImport = array('tl_page' => array());
		foreach ($arrImport as $tableName => $rows) {
			$tmpArrImport[$tableName] = $rows;
		}
		$arrImport = $tmpArrImport;

		foreach ($arrImport as $tableName => $rows) {
			$this->arrMapOldIDToNewID[$tableName] = array();

			$detailsAboutImportedRows = "";

			foreach ($rows as $row) {
				// Bestimmen, ob IDs und/oder Aliase erhalten bleiben sollen. Dies ist nur bei den Shop-Tabellen der Fall.
				$preserveID = false;
				$preserveAlias = false;
				if (preg_match('/^tl_ls_shop_/', $tableName)) {
					$preserveID = true;
					$preserveAlias = true;
				} else if ($tableName == 'tl_page') {
					/*
					 * Preserve all tl_page aliases from the lsShopImportTables, because the export ensures that a domain root entry exists.
					 */
					$preserveAlias = true;
				} else if (isset($row['alias']) && strpos($row['alias'], 'merconis') !== false) {
				    /*
				     * If the record has an alias containing the string "merconis", we preserve the alias
				     */
                    $preserveAlias = true;
                }

                /*
                 * check if an dns-entry is necessary.
                 */
                if(($tableName == 'tl_page') && ($row['type'] == 'root') && ($this->alreadyExistingRootPageID == 0)) {
                    $row['dns'] = '';
                }

                $newID = $this->lsShopInsertData($tableName, $row, $preserveID, $preserveAlias);

				$detailsAboutImportedRows .= ($detailsAboutImportedRows ? ", " : "").$newID;
				$this->arrMapOldIDToNewID[$tableName][$row['id']] = $newID;
			}

            System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Importing '.count($rows).' rows into '.$tableName."\r\n (ID: ".$detailsAboutImportedRows.")", ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_INSTALLER)]);

			if ($tableName == 'tl_page') {
				/*
				 * Call the function loading all DCA configurations in order to automatically create the required language fields
				 * if the rows in tl_page have just been inserted because now the languages which are required for the installation
				 * data exist.
				 * 
				 * The global flag indicating that the multilanguage dca manipulation needs to be processed although the installation
				 * is not complete yet has to be set here.
				 */
				$GLOBALS['merconis_globals']['createMultiLanguageDCAFieldsDuringInstallation'] = true;
				$GLOBALS['merconis_globals']['determineExistingLanguagesDuringInstallation'] = true;
				ls_shop_languageHelper::multilanguageInitialization(false);
			}
		}
	}


	/*
	 * Diese Funktion übernimmt das Eintragen von Daten in die Datenbank
	 */
	protected function lsShopInsertData($targetTable = false, $arrData = false, $preserveID = false, $preserveAlias = false) {
		if (!$targetTable || !is_array($arrData)) {
			throw new \Exception('insufficient parameters given, import data may be invalid');
		}

		if (!\Database::getInstance()->tableExists($targetTable)) {
			throw new \Exception('target table does not exist ('.$targetTable.')');
		}

		$setStatement = '';

		foreach ($arrData as $fieldName => $value) {
			/*
			 * Existiert das Feld in der Zieltabelle nicht, so wird das Feld nicht in das Insert-Statement aufgenommen.
			 * Sollen IDs nicht erhalten bleiben und handelt es sich um das ID-Feld, so wird es nicht in das Insert-Statement aufgenommen.
			 * Sollen Aliase nicht erhalten bleiben und handelt es sich um das Alias-Feld, so wird es nicht in das Insert-Statement aufgenommen.
			 */
			if (!\Database::getInstance()->fieldExists($fieldName, $targetTable) || (!$preserveID && $fieldName == 'id') || (!$preserveAlias && $fieldName == 'alias')) {
				unset($arrData[$fieldName]);
			} else {
				if ($setStatement) {
					$setStatement .= ",\r\n";
				}
				$setStatement .= "`".$fieldName."` = ?";
			}
		}

		$objQuery = \Database::getInstance()->prepare("
			INSERT INTO `".$targetTable."`
			SET		".$setStatement."
		")
			->execute($arrData);

		$insertID = $objQuery->insertId;

		/*
		 * Soll der Alias nicht erhalten bleiben und existiert das Alias-Feld in der Zieltabelle,
		 * so wird ein neuer Alias generiert, wobei bei Bedarf durch Anhängen der Datensatz-ID
		 * sichergestellt wird, dass der Alias unique ist.
		 */
		if (!$preserveAlias && \Database::getInstance()->fieldExists('alias', $targetTable)) {
			$alias = (isset($arrData['title']) && $arrData['title'] ? StringUtil::standardize(StringUtil::restoreBasicEntities($arrData['title'])) : 'record-'.$insertID);

			$alias = strlen($alias) > 100 ? substr($alias, 0, 100) : $alias;

			$objCheckAlias = \Database::getInstance()->prepare("
				SELECT		`id`
				FROM		`".$targetTable."`
				WHERE		`alias` = ?
			")
				->execute($alias);
			if ($objCheckAlias->numRows) {
				$alias = $alias.'-'.$insertID;
			}

			$objUpdateAlias = \Database::getInstance()->prepare("
				UPDATE		`".$targetTable."`
				SET			`alias` = ?
				WHERE		`id` = ?
			")
				->limit(1)
				->execute($alias, $insertID);
		}

		// Die ID, mit der der Datensatz eingefügt wurde, dient als Rückgabewert dieser Funktion
		return $insertID;
	}

	protected function lsShopGetDatabaseRelations() {
		$arrRelations = array();
		$strDatabaseFile = file_get_contents(TL_ROOT.'/vendor/leadingsystems/contao-merconis/src/Resources/contao/config/database.sql');
		preg_match_all('/@(.*)\.(.*)@(.*)\.(.*)=(.*)@/', $strDatabaseFile, $matches);
		foreach ($matches[0] as $k => $v) {
			$arrRelations[] = array(
				'pTable' => $matches[1][$k],
				'pField' => $matches[2][$k],
				'cTable' => $matches[3][$k],
				'cField' => $matches[4][$k],
				'relationType' => $matches[5][$k]
			);
		}
        \LeadingSystems\Helpers\lsErrorLog('$arrRelations', $arrRelations, 'lslog_14');
		return $arrRelations;
	}

	protected function downloadThemeFromRepository() {
		/*
		 * Get the hash from the repository
		 */
		$url = 'http://themerepository.merconis.com/theme'.$_SESSION['lsShop']['installer_selectedTheme']['id'].'/'.$_SESSION['lsShop']['installer_selectedTheme']['version'].'/merconisThemeExport/hash'.($_SESSION['lsShop']['merconisThemeRepositoryMode'] ? '.'.$_SESSION['lsShop']['merconisThemeRepositoryMode'] : '').'.dat';

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT_MS, 4000);
		$downloadFileHash = curl_exec($curl);
		curl_close($curl);
		if (!$downloadFileHash) {
            System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Hash could not be retrieved', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
		}

		$zipTargetPath = 'vendor/leadingsystems/contao-merconis/src/Resources/contao/installerResources/merconisfiles/themes';

        if (!is_dir(TL_ROOT . '/' . $zipTargetPath)) {
            mkdir(TL_ROOT . '/' . $zipTargetPath);
        }

        $zipTargetFilename = $zipTargetPath.'/theme'.$_SESSION['lsShop']['installer_selectedTheme']['id'].'.zip';
		$unzipTargetPath = 'vendor/leadingsystems/contao-merconis/src/Resources/contao/installerResources/merconisfiles/themes';
		$unzipTargetFoldername = $unzipTargetPath.'/theme'.$_SESSION['lsShop']['installer_selectedTheme']['id'];

		$downloadUrl = 'http://themerepository.merconis.com/theme'.$_SESSION['lsShop']['installer_selectedTheme']['id'].'/'.$_SESSION['lsShop']['installer_selectedTheme']['version'].'/merconisThemeExport/theme'.$_SESSION['lsShop']['installer_selectedTheme']['id'].($_SESSION['lsShop']['merconisThemeRepositoryMode'] ? '.'.$_SESSION['lsShop']['merconisThemeRepositoryMode'] : '').'.zip';

		$fp = fopen(TL_ROOT.'/'.$zipTargetFilename, 'w+');
		$curl = curl_init($downloadUrl);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);

		/*
		 * Set the timeout depending on max_execution_time. We want to make sure that
		 * we don't run into that limit and therefore subtract 5 seconds from max_execution_time
		 * which should be enough to cover the rest of the scripts execution time.
		 * 
		 * If the resulting timout is under 25 seconds we set it to 25 seconds because
		 * we want to make sure that we don't have a ridiculously low timeout e.g. if the
		 * max_execution_time could not be retrieved correctly
		 */

		if (ini_get('max_execution_time') < 60) {
			ini_set('max_execution_time', 60); // try to set (will only work if safe mode is not enabled)
		}

		$timeout = ini_get('max_execution_time') - 5;
		$timeout = $timeout > 25 ? $timeout : 25;

		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($curl, CURLOPT_FILE, $fp);
		curl_exec($curl);
		curl_close($curl);
		fclose($fp);

		// If the target folder already exists, delete it!
		if (is_dir(TL_ROOT.'/'.$unzipTargetFoldername)) {
			$this->rmdirRecursively(TL_ROOT.'/'.$unzipTargetFoldername);
		}

		if ($downloadFileHash == md5_file(TL_ROOT.'/'.$zipTargetFilename)) {
			// unzip
			try {
				$objArchive = new \ZipReader($zipTargetFilename);
				while ($objArchive->next()) {
					\File::putContent($unzipTargetPath.'/'.$objArchive->file_name, $objArchive->unzip());
				}
				unset($objArchive);
			} catch (\Exception $e) {
                System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Downloaded theme archive invalid ('.TL_ROOT.'/'.$zipTargetFilename.')', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
			}
		} else {
            System::getContainer()->get('monolog.logger.contao')->info('MERCONIS INSTALLER: Downloaded theme archive has a wrong hash ('.TL_ROOT.'/'.$zipTargetFilename.')', ['contao' => new ContaoContext('MERCONIS INSTALLER', TL_MERCONIS_ERROR)]);
		}

		// delete the downloaded zip file
		unlink(TL_ROOT.'/'.$zipTargetFilename);
	}

	protected function getAvailableThemes() {
		if (!$GLOBALS['merconis_globals']['availableThemes']) {
			$merconisVersion = ls_shop_generalHelper::getMerconisFilesVersion();

			// remove the "beta/rc/stable" part of the version string
			$merconisVersion = preg_replace('/^(\d{1,3}\.\d{1,3}\.\d{1,3}).*/', '$1', $merconisVersion);

			$arrThemes = array();

			if (is_array($this->getArrThemeInfos())) {
				foreach ($this->getArrThemeInfos() as $strThemeInfo) {
					/*
					 * Defining the required data fields of a theme info,
					 * using dummy values at this time
					 */
					$arrThemeInfo = array(
						'id' => 0,
						'name' => '',
						'version' => '',
						'imgUrl' => '',
						'livePreviewUrl' => 'http://google.de',
						'description' => '',
						'default' => false,
						'contaoCompatibilityFrom' => '',
						'contaoCompatibilityTo' => '',
						'merconisCompatibilityFrom' => '',
						'merconisCompatibilityTo' => '',
						'compatibleWithContaoVersion' => true,
						'compatibleWithMerconisVersion' => true
					);

					/*
					 * Walk through all theme info fields and look for the field
					 * key in the string that has been read from the themeInfo.dat
					 * file. We use a regular expression for that.
					 */
					foreach ($arrThemeInfo as $k => $v) {
						if ($k == 'compatibleWithContaoVersion' || $k == 'compatibleWithMerconisVersion') {
							continue;
						}

						preg_match('/^.*"'.$k.'".*:.*"(.*)".*$/m', $strThemeInfo, $arrMatches);

						switch ($k) {
							case 'default':
								$arrThemeInfo[$k] = isset($arrMatches[1]) ? ($arrMatches[1] == 'true' ? true : false) : $v;
								break;

							default:
								$arrThemeInfo[$k] = isset($arrMatches[1]) ? $arrMatches[1] : $v;
								break;
						}

						if ($k == 'name' || $k == 'description') {
							$arrThemeInfo[$k] = $this->parseThemeInfoLanguageString($arrThemeInfo[$k]);
						}
					}

					/*
					 * Determining the compatibility status of the theme whose
					 * theme info we're processing right now.
					 */
					$arrThemeInfo['compatibleWithContaoVersion'] = (
							$arrThemeInfo['contaoCompatibilityFrom'] == '0.0.0'
							||	version_compare(VERSION . '.' . BUILD, $arrThemeInfo['contaoCompatibilityFrom'], '>=')
						) && (
							$arrThemeInfo['contaoCompatibilityTo'] == '0.0.0'
							||	version_compare(VERSION . '.' . BUILD, $arrThemeInfo['contaoCompatibilityTo'], '<=')
						);

					$arrThemeInfo['compatibleWithMerconisVersion'] = (
							$arrThemeInfo['merconisCompatibilityFrom'] == '0.0.0'
							||	version_compare($merconisVersion, $arrThemeInfo['merconisCompatibilityFrom'], '>=')
						) && (
							$arrThemeInfo['merconisCompatibilityTo'] == '0.0.0'
							||	version_compare($merconisVersion, $arrThemeInfo['merconisCompatibilityTo'], '<=')
						);

					/*
					 * Don't add the theme if we couldn't get an id, a name or the version
					 */
					if (!$arrThemeInfo['id'] || !$arrThemeInfo['name'] || !$arrThemeInfo['version']) {
						continue;
					}

					/*
					 * Don't add the theme if it is compatible with a merconis version below
					 * 2.2.0 which means that it is not one of the new responsive generation.
					 */
					if (version_compare('2.2.0', $arrThemeInfo['merconisCompatibilityFrom'], '>')) {
						continue;
					}

					/*
					 * Override the preview image with the local resource if the current source is local
					 */
					if (isset($_SESSION['lsShop']['themeSource']) && $_SESSION['lsShop']['themeSource'] == 'local') {
						$arrThemeInfo['imgUrl'] = sprintf($this->pathToThemePreviewImages, $arrThemeInfo['id']);
					}

					/*
					 * Add the theme to the theme list. If another version of the theme is already in the theme list, only
					 * override the theme info if the already contained one is not compatible with
					 * either the current contao or merconis version and if both its compatible
					 * contao and merconis version is lower than/equal to the one of the theme version we're checking now.
					 * 
					 * Also override the theme info if both the already contained
					 * one and the one we're currently checking are compatible
					 * with both the current merconis and contao version and
					 * the theme info we're checking now has the higher version
					 * number.
					 */
					if (
						!isset($arrThemes[$arrThemeInfo['id']])
						||
						(
							(!$arrThemes[$arrThemeInfo['id']]['compatibleWithContaoVersion'] || !$arrThemes[$arrThemeInfo['id']]['compatibleWithMerconisVersion'])
							&&	(version_compare($arrThemes[$arrThemeInfo['id']]['contaoCompatibilityTo'], $arrThemeInfo['contaoCompatibilityTo'], '<=') && version_compare($arrThemes[$arrThemeInfo['id']]['merconisCompatibilityTo'], $arrThemeInfo['merconisCompatibilityTo'], '<='))
						)
						||
						(
							/*
							 * Both theme versions are compatible with the
							 * current contao and merconis version.
							 */
							($arrThemes[$arrThemeInfo['id']]['compatibleWithContaoVersion'] && $arrThemes[$arrThemeInfo['id']]['compatibleWithMerconisVersion'])
							&&	($arrThemeInfo['compatibleWithContaoVersion'] && $arrThemeInfo['compatibleWithMerconisVersion'])

							/*
							 * The theme info we're checking now has the
							 * higher version number
							 */
							&& (version_compare($arrThemes[$arrThemeInfo['id']]['version'], $arrThemeInfo['version'], '<'))
						)
					) {
						$arrThemes[$arrThemeInfo['id']] = $arrThemeInfo;
					}
				}
			}

			$GLOBALS['merconis_globals']['availableThemes'] = $arrThemes;
		}

		return $GLOBALS['merconis_globals']['availableThemes'];
	}

	protected function loadThemeInfoFromThemeRepository() {
		if (!function_exists('curl_init')) {
			$_SESSION['lsShop']['themeRepositoryError'] = $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-17'];
			return false;
		}

		$url = 'http://themerepository.merconis.com/getThemeInfos.php'.(isset($_SESSION['lsShop']['merconisThemeRepositoryMode']) && $_SESSION['lsShop']['merconisThemeRepositoryMode'] ? '?mode='.$_SESSION['lsShop']['merconisThemeRepositoryMode'] : '');

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT_MS, 4000);
		$strThemeInfos = curl_exec($curl);
		curl_close($curl);

		if (!$strThemeInfos) {
			$_SESSION['lsShop']['themeRepositoryError'] = $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-18'];
			return false;
		}

		$arrThemeInfos = explode('--------', $strThemeInfos);

		foreach ($arrThemeInfos as $k => $v) {
			if (!$v) {
				unset($arrThemeInfos[$k]);
			}
		}
		return $arrThemeInfos;
	}

	protected function getArrThemeInfos() {
		/*
		 * Get the theme infos from the merconis theme repository if this is the currently selected source
		 */
		// Use the repository as the theme source if it has been selected or if no source has been selected yet
		if (!isset($_SESSION['lsShop']['themeSource']) || $_SESSION['lsShop']['themeSource'] == 'repository') {
			$arrThemeInfos = $this->loadThemeInfoFromThemeRepository();
			return $arrThemeInfos;
		}

		/*
		 * Read the local theme info if we couldn't get theme info from the repository
		 */
		$arrThemeInfos = array();

		$themeFolderPath = TL_ROOT.'/vendor/leadingsystems/contao-merconis/src/Resources/contao/installerResources/merconisfiles/themes';
		if (!is_dir($themeFolderPath)) {
		    mkdir($themeFolderPath);
        }
		$arrThemeFolders = scandir($themeFolderPath);

		if (is_array($arrThemeFolders)) {
			foreach($arrThemeFolders as $themeFolder) {
				if ($themeFolder == '.' || $themeFolder == '..') {
					continue;
				}

				$themeInfoFile = $themeFolderPath.'/'.$themeFolder.'/themeInfo.dat';

				if (!file_exists($themeInfoFile)) {
					continue;
				}

				$strThemeInfo = file_get_contents($themeInfoFile);

				if ($strThemeInfo) {
					$arrThemeInfos[] = $strThemeInfo;
				}
			}
		}

		return $arrThemeInfos;
	}

	protected function parseThemeInfoLanguageString($str = '') {
		preg_match('/\['.$this->BackendUser->language.'\](.*)\['.$this->BackendUser->language.'\]/', $str, $arrMatches);
		if ($arrMatches[1]) {
			return $arrMatches[1];
		}

		preg_match('/\[en\](.*)\[en\]/', $str, $arrMatches);
		if ($arrMatches[1]) {
			return $arrMatches[1];
		}

		return $str;
	}

	public function shopUpdate() {
        ls_shop_generalHelper::purgeContaoCache();

		$varUpdateSituation = $this->checkForUpdateSituation();
		if (!\Input::get('lsShopUpdateAction') || !is_array($varUpdateSituation)) {
			return;
		}
		$updateAction = \Input::get('lsShopUpdateAction');

		switch ($updateAction) {
			case 'setInstalledVersion':
				if (!\Input::get('installedVersion') || !in_array(\Input::get('installedVersion'), $this->arrVersionHistory)) {
					\Controller::redirect('contao?do=ls_shop_dashboard');
				}
				$GLOBALS['merconis_globals']['update']['arrUpdateStatus'] = array(
					'updateInProgress' => false,
					'currentStep' => ''
				);
				$this->obj_config->update("\$GLOBALS['TL_CONFIG']['ls_shop_installedVersion']", \Input::get('installedVersion'));
				$this->writeUpdateStatus();
				\Controller::redirect('contao?do=ls_shop_dashboard');
				break;

			case 'startUpdateProgress':
				$GLOBALS['merconis_globals']['update']['arrUpdateStatus']['updateInProgress'] = true;
				$GLOBALS['merconis_globals']['update']['arrUpdateStatus']['currentStep'] = 'versionTrailInformation';
				$this->writeUpdateStatus();
				\Controller::redirect('contao?do=ls_shop_dashboard');
				break;

			case 'converterRoutine_2_0_3_stable_2_1_0_stable':
				$this->{$updateAction}();
				\Controller::redirect('contao?do=ls_shop_dashboard');
				break;

			case 'converterRoutine_2_1_4_stable_2_1_5_stable':
				$this->{$updateAction}();
				\Controller::redirect('contao?do=ls_shop_dashboard');
				break;

			case 'converterRoutine_2_2_0_stable_2_2_1_stable':
				$this->{$updateAction}();
				\Controller::redirect('contao?do=ls_shop_dashboard');
				break;

			case 'converterRoutine_2_2_1_stable_3_0_0_stable':
				$this->{$updateAction}();
				\Controller::redirect('contao?do=ls_shop_dashboard');
				break;

			case 'markUpdateAsFinished':
				$GLOBALS['merconis_globals']['update']['arrUpdateStatus'] = array(
					'updateInProgress' => false,
					'currentStep' => ''
				);
				$this->obj_config->update("\$GLOBALS['TL_CONFIG']['ls_shop_installedVersion']", $varUpdateSituation['currentProgramFilesVersion']);
				$this->writeUpdateStatus();
				\Controller::redirect('contao?do=ls_shop_dashboard');
				break;
		}
	}

	protected function writeUpdateStatus() {
		$this->obj_config->update("\$GLOBALS['TL_CONFIG']['ls_shop_updateStatus']", serialize($GLOBALS['merconis_globals']['update']['arrUpdateStatus']));
	}

	/*
	 * This function determines whether or not we currently have an update situation.
	 * An update situation exists if the program files are present in a version that is higher
	 * than the MERCONIS version number saved in the localconfig.
	 */
	public function checkForUpdateSituation() {
		$currentProgramFilesVersion = ls_shop_generalHelper::getMerconisFilesVersion();
		$installedVersion = isset($GLOBALS['TL_CONFIG']['ls_shop_installedVersion']) ? $GLOBALS['TL_CONFIG']['ls_shop_installedVersion'] : 'unknown';

		if ($currentProgramFilesVersion == $installedVersion) {
			/*
			 * We don't have an update situation if the currentProgramFilesVersion matches the installedVersion
			 */
			return false;
		} else {
			/*
			 * We have an update situation, so the first thing we have to do is getting the update status from the localconfig.
			 * If this array doesn't exist or it is invalid in the localconfig, we create a default update status array.
			 */
			if (isset($GLOBALS['TL_CONFIG']['ls_shop_updateStatus'])) {
				$GLOBALS['merconis_globals']['update']['arrUpdateStatus'] = StringUtil::deserialize($GLOBALS['TL_CONFIG']['ls_shop_updateStatus'], true);
			}

			if (
				!isset($GLOBALS['merconis_globals']['update']['arrUpdateStatus'])
				||	!is_array($GLOBALS['merconis_globals']['update']['arrUpdateStatus'])
				||	!count($GLOBALS['merconis_globals']['update']['arrUpdateStatus'])
				||	!isset($GLOBALS['merconis_globals']['update']['arrUpdateStatus']['updateInProgress'])
				||	!isset($GLOBALS['merconis_globals']['update']['arrUpdateStatus']['currentStep'])
			) {
				$GLOBALS['merconis_globals']['update']['arrUpdateStatus'] = array(
					'updateInProgress' => false,
					'currentStep' => ''
				);
			}

			$keyInstalledVersion = array_search($installedVersion, $this->arrVersionHistory);
			$keyCurrentProgramFilesVersion = array_search($currentProgramFilesVersion, $this->arrVersionHistory);

			$errorCode = '';
			if (strpos($currentProgramFilesVersion, 'unreleased') !== false) {
                $errorCode = 'currentFilesVersionUnreleased';
            } else if ($keyInstalledVersion === false || $keyCurrentProgramFilesVersion === false || $keyCurrentProgramFilesVersion < $keyInstalledVersion) {
				if ($installedVersion == 'unknown') {
					$errorCode = 'installedVersionUnknown';
				} else {
					$errorCode = 'currentVersionSituationInvalid';
				}
			}

			/*
			 * Get the relevant part of the version history as the version trail to update.
			 * In other words, we get all versions that we have to walk through from the former
			 * version to the new one.
			 */
			$arrVersionTrailToUpdate = array_slice($this->arrVersionHistory, $keyInstalledVersion);

			$arrReturn = array(
				'currentProgramFilesVersion' => $currentProgramFilesVersion,
				'installedVersion' => $installedVersion,
				'arrVersionTrailToUpdate' => $arrVersionTrailToUpdate,
				'arrVersionHistory' => $this->arrVersionHistory,
				'blnHasError' => $errorCode ? true : false,
				'errorCode' => $errorCode
			);

			return $arrReturn;
		}
	}

	/*
	 * This function is called from the ls_shop_systemMessagesController and checks whether or not there are instructions
	 * for a certain update step.
	 */
	public function checkForNecessaryInstructions($versionFrom = null, $versionTo = null) {
		$versionFrom = preg_replace('/[.\s]/', '_', $versionFrom);
		$versionTo = preg_replace('/[.\s]/', '_', $versionTo);

		return in_array($versionFrom.'_'.$versionTo, $this->arrUpdateStepsWithInstructions);
	}

	/*
	 * This function is called from the ls_shop_systemMessagesController and checks whether or not there is a converterRoutine
	 * for a certain update step. The return array contains the name of the routine if it exists and the information if the
	 * execution of the routine is allowed and if it has already been executed.
	 */
	public function checkForNecessaryConverterRoutine($versionFrom = null, $versionTo = null) {
		$arrRoutineInfo = array(
			'routineName' => '',
			'alreadyExecuted' => false,
			'blnIsAllowed' => false
		);
		if (!$versionFrom || !$versionTo) {
			return $arrRoutineInfo;
		}
		$versionFrom = preg_replace('/[.\s]/', '_', $versionFrom);
		$versionTo = preg_replace('/[.\s]/', '_', $versionTo);

		$methodName = 'converterRoutine_'.$versionFrom.'_'.$versionTo;

		if (method_exists($this, $methodName)) {
			$arrRoutineInfo['routineName'] = $methodName;
			if (isset($GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines']) && in_array($methodName, $GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines'])) {
				$arrRoutineInfo['alreadyExecuted'] = true;
			}

			$checkIfAllowedMethodName = 'check_if_'.$methodName.'_allowed';
			$arrRoutineInfo['blnIsAllowed'] = method_exists($this, $checkIfAllowedMethodName) ? $this->{$checkIfAllowedMethodName}() : true;
		}

		return $arrRoutineInfo;
	}

	/*
	 * This function writes the routineName of the already executed converterRoutine to the localconfig
	 * so that we know which routines have been execute by now. This is important to prevent a routine
	 * from being executed twice and can be important if a converterRoutine depends on other converterRoutines
	 * to be executed before.
	 */
	protected function markConverterRoutineAsExecuted($routineName) {
		if (!isset($GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines'])) {
			$GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines'] = array();
		}

		$GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines'][] = $routineName;
		$this->writeUpdateStatus();
	}

	/*
	 * If a converterRoutine matching the version numbers of an update step is available
	 * it will be presented as a link in the update helper. If it has already been executed,
	 * in which case the function's name is stored in the localconfig, the link to this routine
	 * is no longer active.
	 * 
	 * This function gets called by the function InstallerController::shopUpdate() and there
	 * needs to be a case defined with this function name.
	 */
	protected function converterRoutine_2_0_3_stable_2_1_0_stable() {
		$methodName = substr(__METHOD__, strripos(__METHOD__, '::') + 2);
		if (isset($GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines']) && in_array($methodName, $GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines'])) {
			return;
		}

		/*
		 * Creating a dummy attribute row that can be used as the parent for all attribute values
		 * that have not been assigned to an attribute yet (which is the case for all existing
		 * attribute values because the pid is new).
		 */
		$objInsert = \Database::getInstance()->prepare("
			INSERT INTO	`tl_ls_shop_attributes`
			SET			`title` = ?,
						`alias` = ?,
						`tstamp` = ?						
		")
			->execute('MERCONIS DUMMY ATTRIBUTE', 'merconis_dummy_attribute', time());
		$dummyAttributeID = $objInsert->insertId;

		/*
		 * Setting the pid to assign all attribute values to the dummy attribute
		 */
		$objUpdate = \Database::getInstance()->prepare("
			UPDATE		`tl_ls_shop_attribute_values`
			SET			`pid` = ?
			WHERE		`pid` = ?
				OR		`pid` = ?
		")
			->execute($dummyAttributeID, 0, '');

		ls_shop_generalHelper::updateAllAttributeValueAllocationsInAllocationTable();

		/*
		 * Writing default values in the localconfig
		 */
		$this->obj_config->update("\$GLOBALS['TL_CONFIG']['ls_shop_maxNumParallelSearchCaches']", 20);
		$this->obj_config->update("\$GLOBALS['TL_CONFIG']['ls_shop_searchCacheLifetimeSec']", 300);
		$this->obj_config->update("\$GLOBALS['TL_CONFIG']['ls_shop_importCsvDelimiter']", ';');
		$this->obj_config->update("\$GLOBALS['TL_CONFIG']['ls_shop_importCsvEnclosure']", '"');
		$this->obj_config->update("\$GLOBALS['TL_CONFIG']['ls_shop_numMaxImportRecordsPerRound']", 1000);

		/*
		 * Set the new "published" flag in all cross_seller records
		 */
		\Database::getInstance()->prepare("
			UPDATE		`tl_ls_shop_cross_seller`
			SET			`published` = '1'
		")
			->execute();

		$this->markConverterRoutineAsExecuted($methodName);
	}

	protected function converterRoutine_2_1_4_stable_2_1_5_stable() {
		$methodName = substr(__METHOD__, strripos(__METHOD__, '::') + 2);
		if (isset($GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines']) && in_array($methodName, $GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines'])) {
			return;
		}

		$this->obj_config->update("\$GLOBALS['TL_CONFIG']['ls_shop_blnCompatMode2-1-4']", true);

		$this->markConverterRoutineAsExecuted($methodName);
	}

	protected function converterRoutine_2_2_0_stable_2_2_1_stable() {
		$methodName = substr(__METHOD__, strripos(__METHOD__, '::') + 2);
		if (isset($GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines']) && in_array($methodName, $GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines'])) {
			return;
		}

		$arr_languageSpecificAliasFieldnames = array();
		$arr_fields = \Database::getInstance()->listFields('tl_ls_shop_product', true);
		foreach ($arr_fields as $arr_fieldDetails) {
			if (strpos($arr_fieldDetails['name'], 'alias_') !== false) {
				$arr_languageSpecificAliasFieldnames[] = $arr_fieldDetails['name'];
			}
		}

		foreach ($arr_languageSpecificAliasFieldnames as $str_fieldToUpdate) {
			$obj_dbquery_updateLanguageSpecificAliases = \Database::getInstance()->prepare("
				UPDATE		`tl_ls_shop_product`
				SET			`".$str_fieldToUpdate."` = `alias`
				WHERE		`".$str_fieldToUpdate."` = ''
			")
				->execute();
		}

		$this->markConverterRoutineAsExecuted($methodName);
	}

	protected function converterRoutine_2_2_1_stable_3_0_0_stable() {
		$methodName = substr(__METHOD__, strripos(__METHOD__, '::') + 2);
		if (isset($GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines']) && in_array($methodName, $GLOBALS['merconis_globals']['update']['arrUpdateStatus']['alreadyExecutedConverterRoutines'])) {
			return;
		}

		\Database::getInstance()
			->prepare("
			UPDATE		`tl_page`
			SET			`ls_shop_decimalsSeparator` = ?,
						`ls_shop_thousandsSeparator` = ?,
						`ls_shop_currencyBeforeValue` = ?
			WHERE		`type` = ?
		")
			->execute(
				$GLOBALS['TL_CONFIG']['ls_shop_decimalsSeparator'],
				$GLOBALS['TL_CONFIG']['ls_shop_thousandsSeparator'],
				$GLOBALS['TL_CONFIG']['ls_shop_currencyBeforeValue'],
				'root'
			);

		$this->obj_config->delete("\$GLOBALS['TL_CONFIG']['ls_shop_decimalsSeparator']");
		$this->obj_config->delete("\$GLOBALS['TL_CONFIG']['ls_shop_thousandsSeparator']");
		$this->obj_config->delete("\$GLOBALS['TL_CONFIG']['ls_shop_currencyBeforeValue']");

		$this->markConverterRoutineAsExecuted($methodName);
	}


	/*
	 * If a "check_if_converterRoutine_..._allowed" function is available it is called in order
	 * to determine whether or not the link to call the converter routine should be active.
	 * 
	 * If no such function exists the converter link is always active because the update helper
	 * assumes that there is no special condition to wait for.
	 */
	protected function check_if_converterRoutine_2_0_3_stable_2_1_0_stable_allowed() {
		if (!\Database::getInstance()->fieldExists('pid', 'tl_ls_shop_attribute_values')) {
			return false;
		} else {
			return true;
		}
	}

	/*
	 * We look for a language specific alias field in tl_ls_shop_product because
	 * that indicates that the database update has already been performed and the
	 * fields required for the converter routine exist.
	 */
	protected function check_if_converterRoutine_2_2_0_stable_2_2_1_stable_allowed() {
		$arr_fields = \Database::getInstance()->listFields('tl_ls_shop_product', true);
		foreach ($arr_fields as $arr_fieldDetails) {
			if (strpos($arr_fieldDetails['name'], 'alias_') !== false) {
				return true;
			}
		}
		return false;
	}
}