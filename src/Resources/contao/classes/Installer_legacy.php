<?php

namespace Merconis\Core;

use Composer\InstalledVersions;
use Contao\Controller;
use Contao\Database;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\System;

class Installer_legacy
{
    protected $obj_template = null;

    /*
     * Current object instance (Singleton)
     */
    protected static $objInstance;

    /*
     * Prevent cloning of the object (Singleton)
     */
    private function __clone()
    {
    }


    /*
     * Return the current object instance (Singleton)
     */
    public static function getInstance()
    {
        if (!is_object(self::$objInstance)) {
            self::$objInstance = new self();
        }
        return self::$objInstance;
    }

    /*
     * Prevent direct instantiation (Singleton)
     */
    protected function __construct()
    {
        System::loadLanguageFile('lsm_installer');
        $this->obj_template = new FrontendTemplate('lsm_installer');
        $this->obj_template->str_output = $this->getIncompleteInstallationMessage();
    }

    public function parse()
    {
        return $this->obj_template->parse();
    }

    protected function getIncompleteInstallationMessage()
    {
        /** @var \Merconis\Core\InstallerController_legacy $obj_installerController */
        $obj_installerController = System::importStatic('Merconis\Core\InstallerController_legacy');

        // Installationsstatus zu Beginn abfragen
        $arrInstallationStatus = $obj_installerController->getInstallationStatus();

//        dump($arrInstallationStatus);

        /*
         * Nichts weiter machen, wenn Shop bereits vollständig installiert ist und keine Update-Situation vorliegt.
         */
        $varUpdateSituation = $obj_installerController->checkForUpdateSituation();

//        dump($varUpdateSituation);

        if (!is_array($arrInstallationStatus) && $arrInstallationStatus == 'invalidSystemStatus') {
            ob_start();
            ?>
            <div class="ls_shop ls_shop_systemMessage shopInstallationNotPossible">
                <?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage22']; ?>
            </div>
            <?php
            return ob_get_clean();
        } else if (!is_array($arrInstallationStatus) && $arrInstallationStatus == 'complete' && !is_array($varUpdateSituation)) {
            ob_start();

            $urlToFrontendShopInstallation = Environment::get('base');

            /*
             * Prüfen, ob bei der Seite mit dem Alias "merconis-root-page-main-language" das Fallback-Flag gesetzt ist
             */
            $blnMerconisFallbackFlagSet = true;

            $objMerconisRootPageMainLanguage = Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_page`
				WHERE		`alias` = 'merconis-root-page-main-language'
			")
                ->execute();

            if ($objMerconisRootPageMainLanguage->numRows) {
                $objMerconisRootPageMainLanguage->first();
                if (!$objMerconisRootPageMainLanguage->fallback) {
                    $blnMerconisFallbackFlagSet = false;
                }
            }

            ?>
            <div class="ls_shop ls_shop_systemMessage shopInstalledCompletely">
                <?php echo $urlToFrontendShopInstallation ? sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage07'], InstalledVersions::getPrettyVersion('leadingsystems/contao-merconis'), $urlToFrontendShopInstallation) : sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage08'], InstalledVersions::getPrettyVersion('leadingsystems/contao-merconis')); ?>
                <?php echo !$blnMerconisFallbackFlagSet ? $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage10'] : ''; ?>
                <?php echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage23'], $GLOBALS['TL_CONFIG']['merconis_serviceNumber']); ?>
                <ul>
                    <?php
                    $strNeedle = 'leadingsystems';
                    foreach (InstalledVersions::getInstalledPackages() as $strPackage) {
                        if (str_contains($strPackage,$strNeedle))
                        {
                            echo '<li>'.str_replace($strNeedle.'/','',$strPackage).' ('. InstalledVersions::getPrettyVersion($strPackage).')</li>';
                        }
                    }
                    ?>
                </ul>
            </div>
            <?php
            return ob_get_clean();
        } else if (!is_array($arrInstallationStatus) && $arrInstallationStatus == 'complete' && is_array($varUpdateSituation)) {
            ob_start();
            ?>
            <div class="ls_shop ls_shop_systemMessage updateSituation">
                <?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage15']; ?>
                <?php
                if (!$varUpdateSituation['blnHasError']) {
                    if (!$GLOBALS['merconis_globals']['update']['arrUpdateStatus']['updateInProgress']) {
                        /*
                         * Display a introduction if we have an update situation but the update is not in progress yet
                         */
                        echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage11'], $varUpdateSituation['installedVersion'], $varUpdateSituation['currentProgramFilesVersion'], 'contao?do=ls_shop_dashboard&lsShopUpdateAction=startUpdateProgress');
                    } else {
                        /*
                         * If the update is in progress we show different messages depending on the current update step
                         */
                        switch ($GLOBALS['merconis_globals']['update']['arrUpdateStatus']['currentStep']) {

                            case 'versionTrailInformation':
                                /*
                                 * Show a message that informs about the version trail and whether or not there is more than one version affected
                                 */
                                if (count($varUpdateSituation['arrVersionTrailToUpdate']) > 2) {
//										lsShopErrorLog("\$varUpdateSituation['arrVersionTrailToUpdate']", $varUpdateSituation['arrVersionTrailToUpdate'], 'tmp');
                                    echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage13'];
                                }

                                echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage16'], 'contao/install.php');
                                echo '<hr>';
                                $lastVersionInTrail = '';
                                foreach ($varUpdateSituation['arrVersionTrailToUpdate'] as $strVersion) {
                                    if (!$lastVersionInTrail) {
                                        $lastVersionInTrail = $strVersion;
                                        continue;
                                    }
                                    ?>
                                    <p>
                                        <?php echo $lastVersionInTrail; ?> => <?php echo $strVersion; ?>
                                        <?php
                                        $arrConverterRoutineInfo = $obj_installerController->checkForNecessaryConverterRoutine($lastVersionInTrail, $strVersion);
                                        if ($obj_installerController->checkForNecessaryInstructions($lastVersionInTrail, $strVersion)) {
                                            ?>
                                            &nbsp;&nbsp;&nbsp;<a target="_blank"
                                                                 href="http://www.merconis.com/tl_files/EigeneDateien/DokumenteUndSonstigeDateien/updateInstructions/index.htm#update_from_<?php echo preg_replace('/[.\s]/', '-', $lastVersionInTrail); ?>_to_<?php echo preg_replace('/[.\s]/', '-', $strVersion); ?>.htm"><?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage12']; ?></a>
                                            <?php
                                        } else {
                                            if (!$arrConverterRoutineInfo['routineName']) {
                                                ?>
                                                &nbsp;&nbsp;&nbsp;<?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage21']; ?>
                                                <?php
                                            }
                                        }

                                        if ($arrConverterRoutineInfo['routineName']) {
                                            if (!$arrConverterRoutineInfo['alreadyExecuted']) {
                                                if (!$arrConverterRoutineInfo['blnIsAllowed']) {
                                                    ?>
                                                    &nbsp;&nbsp;&nbsp;<?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage20']; ?>
                                                    <?php
                                                } else {
                                                    ?>
                                                    &nbsp;&nbsp;&nbsp;<a
                                                        href="contao?do=ls_shop_dashboard&lsShopUpdateAction=<?php echo $arrConverterRoutineInfo['routineName']; ?>"><?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage18']; ?></a>
                                                    <?php
                                                }
                                            } else {
                                                ?>
                                                &nbsp;&nbsp;&nbsp;<?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage19']; ?>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </p>
                                    <?php
                                    $lastVersionInTrail = $strVersion;
                                }
                                echo '<hr>';
                                ?>
                                <?php echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage14'], 'contao?do=ls_shop_dashboard&lsShopUpdateAction=markUpdateAsFinished'); ?>
                                <?php
                                break;
                        }
                    }
                } else {
                    if ($varUpdateSituation['errorCode'] == 'installedVersionUnknown') {
                        $count = 0;
                        $numVersionsInHistory = count($varUpdateSituation['arrVersionHistory']);
                        echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage17'];
                        foreach ($varUpdateSituation['arrVersionHistory'] as $versionInHistory) {
                            $count++;
                            if ($count == $numVersionsInHistory) {
                                break;
                            }
                            ?>
                            <a href="contao?do=ls_shop_dashboard&lsShopUpdateAction=setInstalledVersion&installedVersion=<?php echo urlencode($versionInHistory); ?>"><?php echo $versionInHistory; ?></a>
                            <br/>
                            <?php
                        }
                    } else {
                        echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['updateErrors'][$varUpdateSituation['errorCode']];
                        if ($varUpdateSituation['errorCode'] != 'currentFilesVersionUnreleased') {
                            echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage14'], 'contao?do=ls_shop_dashboard&lsShopUpdateAction=markUpdateAsFinished');
                        }
                    }
                }
                ?>
            </div>
            <?php
            /*
             * Call the update function which will perform the update routine given as a get parameter
             */
            $obj_installerController->shopUpdate();
            return ob_get_clean();
        }


        /*
         * Ausführen des aktuellen Installationsschrittes
         */
        $obj_installerController->shopInstallation();

        // Installationsstatus erneut abfragen, da nun ein Installationsschritt gelaufen ist
        $arrInstallationStatus = $obj_installerController->getInstallationStatus();


        /*
         * If the flag 'ls_shop_installedCompletely' is not set in the localconfig (not even as 'false')
         * we are at the very beginning of the installation process.
         */
        if (!isset($GLOBALS['TL_CONFIG']['ls_shop_installedCompletely'])) {
            ob_start();
            ?>
            <div class="ls_shop ls_shop_systemMessage">
                <?php echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-01'], 'contao?do=ls_shop_dashboard&lsShopInstallationStep=1'); ?>
            </div>
            <?php
            return ob_get_clean();
        }

        if ($arrInstallationStatus['noApiKey']) {
            ob_start();
            ?>
            <div class="ls_shop ls_shop_systemMessage contaoInstallScriptNotRun">
                <?php echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-03']); ?>
            </div>
            <?php
            return ob_get_clean();
        }

        if (!$arrInstallationStatus['wholeDBOkay']) {
            ob_start();
            ?>
            <div class="ls_shop ls_shop_systemMessage contaoInstallScriptNotRun">
                <?php echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-02'], 'contao/install'); ?>
            </div>
            <?php
            return ob_get_clean();
        }

        if ($arrInstallationStatus['wholeDBOkay']) {
            ob_start();
            ?>
            <div class="ls_shop ls_shop_systemMessage">
                <?php echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-06'], 'contao?do=ls_shop_dashboard&lsShopInstallationStep=2'); ?>

                <?php
                if ($_SESSION['lsShop']['selectedThemeCanNotBeInstalled']) {
                    unset($_SESSION['lsShop']['selectedThemeCanNotBeInstalled']);
                    ?>
                    <div class="shopErrorMsg"><?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-12']; ?></div>
                    <?php
                }

                if ($_SESSION['lsShop']['noThemeSelected']) {
                    unset($_SESSION['lsShop']['noThemeSelected']);
                    ?>
                    <div class="shopErrorMsg"><?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-13']; ?></div>
                    <?php
                }

                if (Input::get('switchThemeSource')) {
                    $_SESSION['lsShop']['themeSource'] = Input::get('switchThemeSource');
                    Controller::redirect('contao?do=ls_shop_dashboard');
                }
                ?>

                <div class="merconisThemeSourceSwitch">
                    <div class="switch">
                        <a onclick="this.blur();"
                           class="<?php echo !isset($_SESSION['lsShop']['themeSource']) || $_SESSION['lsShop']['themeSource'] == 'repository' ? 'active' : 'inactive'; ?>"
                           href="contao?do=ls_shop_dashboard&switchThemeSource=repository"><?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-15']; ?></a>
                        <a onclick="this.blur();"
                           class="<?php echo isset($_SESSION['lsShop']['themeSource']) && $_SESSION['lsShop']['themeSource'] == 'local' ? 'active' : 'inactive'; ?>"
                           href="contao?do=ls_shop_dashboard&switchThemeSource=local"><?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-14']; ?></a>
                    </div>
                    <form action="contao?do=ls_shop_dashboard&lsShopInstallationStep=2" method="post">
                        <input type="hidden" name="FORM_SUBMIT" value="installer_themeSelection">
                        <input type="hidden" name="REQUEST_TOKEN" value="<?= System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue() ?>">

                        <?php
                        if (is_array($obj_installerController->availableThemes) && count($obj_installerController->availableThemes)) {
                            foreach ($obj_installerController->availableThemes as $arrTheme) {
                                if (!$arrTheme['compatibleWithMerconisVersion']) {
                                    continue;
                                }
                                ?>
                                <div class="installerThemeSelection">
                                    <?php
                                    if ($arrTheme['compatibleWithContaoVersion'] && $arrTheme['compatibleWithMerconisVersion']) {
                                        ?>
                                        <input<?php echo $arrTheme['default'] ? ' checked' : ''; ?> type="radio"
                                                                                                    name="installer_selectedTheme"
                                                                                                    id="installer_selectedTheme<?php echo $arrTheme['id']; ?>"
                                                                                                    value="<?php echo $arrTheme['id']; ?>|<?php echo $arrTheme['version']; ?>">
                                        <?php
                                    }
                                    ?>
                                    <label <?php if ($arrTheme['compatibleWithContaoVersion'] && $arrTheme['compatibleWithMerconisVersion']) { ?>
                                        for="installer_selectedTheme<?php echo $arrTheme['id']; ?><?php } ?>">
                                        <?php
                                        if ($arrTheme['imgUrl']) {
                                            ?>
                                            <span class="img"><img src="<?php echo $arrTheme['imgUrl']; ?>"></span>
                                            <?php
                                        }
                                        ?>
                                        <span class="name"><?php echo $arrTheme['name'] . ' ' . $arrTheme['version']; ?></span>
                                        <span class="description"><?php echo $arrTheme['description']; ?></span>
                                        <?php
                                        if (!$arrTheme['compatibleWithContaoVersion']) {
                                            ?>
                                            <span class="compatibilityIssue"><?php echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-10'], $arrTheme['contaoCompatibilityFrom'] == $arrTheme['contaoCompatibilityTo'] ? $arrTheme['contaoCompatibilityFrom'] : $arrTheme['contaoCompatibilityFrom'] . ' - ' . $arrTheme['contaoCompatibilityTo']); ?></span>
                                            <?php
                                        }
                                        if (!$arrTheme['compatibleWithMerconisVersion']) {
                                            ?>
                                            <span class="compatibilityIssue"><?php echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-11'], $arrTheme['merconisCompatibilityFrom'] == $arrTheme['merconisCompatibilityTo'] ? $arrTheme['merconisCompatibilityFrom'] : $arrTheme['merconisCompatibilityFrom'] . ' - ' . $arrTheme['merconisCompatibilityTo']); ?></span>
                                            <?php
                                        }
                                        if($arrTheme['livePreviewUrl']) {
                                            ?>
                                            <span class="preview"><a target="_blank"
                                                                     href="<?php echo $arrTheme['livePreviewUrl']; ?>"><?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-08']; ?></a></span>
                                            <?php
                                        }
                                        ?>
                                    </label>
                                </div>
                                <?php
                            }
                            ?>
                            <?php
                            if (isset($_SESSION['lsShop']['themeSource']) && $_SESSION['lsShop']['themeSource'] == 'repository') {
                                ?>
                                <p><?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-19']; ?></p>
                                <?php
                            }
                            ?>
                            <input type="submit" name="selectTheme" onclick="lsjs.loadingIndicator.__controller.show()"
                                   value="<?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-07']; ?>">
                            <?php
                        } else {
                            ?>
                            <div class="shopErrorMsg">
                                <?php
                                if (isset($_SESSION['lsShop']['themeSource']) && $_SESSION['lsShop']['themeSource'] == 'repository') {
                                    echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-16'], isset($_SESSION['lsShop']['themeRepositoryError']) && $_SESSION['lsShop']['themeRepositoryError'] ? ' (' . $_SESSION['lsShop']['themeRepositoryError'] . ')' : '');
                                } else {
                                    echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['systemMessages']['installToolMessage01-09'];
                                }
                                ?>
                            </div>
                            <?php
                        }
                        ?>
                    </form>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        return '';
    }
}