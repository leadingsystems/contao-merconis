<?php

namespace Merconis\Core;

class ThemeInstaller
{
    protected $obj_template = null;

    private $int_status = themeInstallerStatus::UNKNOWN;
    private $arr_installedThemeExtensions = [];

    /*
     * In this array the relation between original ID (Key) and new ID (Value) is established
     */
    private $arr_mapOldIDToNewID = [];
    private $int_alreadyExistingRootPageID = null;

    /*
     * Current object instance (Singleton)
     */
    protected static $objInstance;

    /*
     * Prevent cloning of the object (Singleton)
     */
    final private function __clone()
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
        \System::loadLanguageFile('lsm_themeInstaller');
        $this->getInstalledThemeExtensions();
        $this->getStatus();

        $this->handleUserAction();

        $this->obj_template = new \FrontendTemplate('lsm_themeInstaller');
    }

    private function handleUserAction()
    {
        if (\Input::post('FORM_SUBMIT') == 'themeInstaller_beginSetup') {
            if (\Input::post('themeToInstall') !== $this->arr_installedThemeExtensions[0]) {
                \System::log(TL_MERCONIS_THEME_SETUP . ': Theme "' . \Input::post('themeToInstall') . '" should be set up but it is not the one and only installed theme extension', TL_MERCONIS_THEME_SETUP, TL_MERCONIS_THEME_SETUP);
                \Controller::reload();
            }

            $this->runSetup();
        }
    }

    private function runSetup()
    {
        $this->writeLocalconfig();
        $this->writeDatabase();
        $this->generateSymlinks();
    }

    private function writeLocalconfig()
    {
        /*
         * Entering the basic settings in localconfig. In some cases, the values must be replaced with
         * the correct values (ID assignments) later in the setup process.
         */
        $arr_exportLocalconfig = deserialize(file_get_contents(TL_ROOT . '/vendor/' . $this->arr_installedThemeExtensions[0] . '/src/Resources/theme/data/exportLocalconfig.dat'));

        \System::log(TL_MERCONIS_THEME_SETUP . ': Inserting MERCONIS configuration values in localconfig.php', TL_MERCONIS_THEME_SETUP, TL_MERCONIS_THEME_SETUP);

        foreach ($arr_exportLocalconfig as $k => $v) {
            if ($k == 'ls_shop_installedVersion') {
                /*
                 * We don't use this information in the localconfig anymore. If it is still in the localconfig export
                 * file, we ignore it.
                 */
                continue;
            }

            \Config::getInstance()->update("\$GLOBALS['TL_CONFIG']['".$k."']", $v);
        }

    }

    private function writeDatabase()
    {
        $arr_exportTables = deserialize(file_get_contents(TL_ROOT . '/vendor/' . $this->arr_installedThemeExtensions[0] . '/src/Resources/theme/data/exportTables.dat'));
        $this->importTables($arr_exportTables);
    }

    private function importTables($arr_import)
    {
        // Festhalten der ID der bereits vor dem Datenimport existierenden Root-Page
        $this->int_alreadyExistingRootPageID = $this->getRootPageId();

        \System::log(TL_MERCONIS_THEME_SETUP . ': Importing tables ', TL_MERCONIS_THEME_SETUP, TL_MERCONIS_THEME_SETUP);

        /*
         * Make sure that 'tl_page' is the first element in the array because it is important that
         * the pages are inserted first.
         */
        $arr_tmp_import = ['tl_page' => []];
        foreach ($arr_import as $str_tableName => $arr_rows) {
            $arr_tmp_import[$str_tableName] = $arr_rows;
        }
        $arr_import = $arr_tmp_import;

        foreach ($arr_import as $str_tableName => $arr_rows) {
            $this->arr_mapOldIDToNewID[$str_tableName] = [];

            $str_detailsAboutImportedRows = "";

            foreach ($arr_rows as $arr_row) {
                // Determine whether IDs and/or aliases should be preserved. This is only the case for the merconis/ls_shop tables.
                $bln_preserveID = false;
                $bln_preserveAlias = false;
                if (preg_match('/^tl_ls_shop_/', $str_tableName)) {
                    $bln_preserveID = true;
                    $bln_preserveAlias = true;
                } else if ($str_tableName == 'tl_page') {
                    /*
                     * Preserve all tl_page aliases, because the export ensures that a domain root entry exists.
                     */
                    $bln_preserveAlias = true;
                } else if (isset($arr_row['alias']) && strpos($arr_row['alias'], 'merconis') !== false) {
                    /*
                     * If the record has an alias containing the string "merconis", we preserve the alias
                     */
                    $bln_preserveAlias = true;
                }

                /*
                 * check if a dns-entry is necessary. It is not necessary if the target installation doesn't have an
                 * already existing root page.
                 */
                if(($str_tableName == 'tl_page') && ($arr_row['type'] == 'root') && ($this->int_alreadyExistingRootPageID == 0)) {
                    $arr_row['dns'] = '';
                }

                $int_newId = $this->insertData($str_tableName, $arr_row, $bln_preserveID, $bln_preserveAlias);

                $str_detailsAboutImportedRows .= ($str_detailsAboutImportedRows ? ", " : "").$int_newId;
                $this->arr_mapOldIDToNewID[$str_tableName][$arr_row['id']] = $int_newId;
            }

            \System::log(TL_MERCONIS_THEME_SETUP . ': Importing '.count($arr_rows).' rows into '.$str_tableName."\r\n (ID: ".$str_detailsAboutImportedRows.")", TL_MERCONIS_THEME_SETUP, TL_MERCONIS_THEME_SETUP);

            if ($str_tableName == 'tl_page') {
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
     * This function takes care of entering data into the database
     */
    private function insertData($str_targetTable, $arr_data, $bln_preserveId = false, $bln_preserveAlias = false)
    {
        if (!$str_targetTable || !is_array($arr_data)) {
            throw new \Exception('insufficient parameters given, import data may be invalid');
        }

        if (!\Database::getInstance()->tableExists($str_targetTable)) {
            throw new \Exception('target table does not exist ('.$str_targetTable.')');
        }

        $setStatement = '';

        foreach ($arr_data as $fieldName => $value) {
            /*
             * Existiert das Feld in der Zieltabelle nicht, so wird das Feld nicht in das Insert-Statement aufgenommen.
             * Sollen IDs nicht erhalten bleiben und handelt es sich um das ID-Feld, so wird es nicht in das Insert-Statement aufgenommen.
             * Sollen Aliase nicht erhalten bleiben und handelt es sich um das Alias-Feld, so wird es nicht in das Insert-Statement aufgenommen.
             */
            if (!\Database::getInstance()->fieldExists($fieldName, $str_targetTable) || (!$bln_preserveId && $fieldName == 'id') || (!$bln_preserveAlias && $fieldName == 'alias')) {
                unset($arr_data[$fieldName]);
            } else {
                if ($setStatement) {
                    $setStatement .= ",\r\n";
                }
                $setStatement .= "`".$fieldName."` = ?";
            }
        }

        $objQuery = \Database::getInstance()->prepare("
			INSERT INTO `".$str_targetTable."`
			SET		".$setStatement."
		")
            ->execute($arr_data);

        $insertID = $objQuery->insertId;

        /*
         * Soll der Alias nicht erhalten bleiben und existiert das Alias-Feld in der Zieltabelle,
         * so wird ein neuer Alias generiert, wobei bei Bedarf durch Anhängen der Datensatz-ID
         * sichergestellt wird, dass der Alias unique ist.
         */
        if (!$bln_preserveAlias && \Database::getInstance()->fieldExists('alias', $str_targetTable)) {
            $alias = (isset($arr_data['title']) && $arr_data['title'] ? standardize(\StringUtil::restoreBasicEntities($arr_data['title'])) : 'record-'.$insertID);

            $alias = strlen($alias) > 100 ? substr($alias, 0, 100) : $alias;

            $objCheckAlias = \Database::getInstance()->prepare("
				SELECT		`id`
				FROM		`".$str_targetTable."`
				WHERE		`alias` = ?
			")
                ->execute($alias);
            if ($objCheckAlias->numRows) {
                $alias = $alias.'-'.$insertID;
            }

            $objUpdateAlias = \Database::getInstance()->prepare("
				UPDATE		`".$str_targetTable."`
				SET			`alias` = ?
				WHERE		`id` = ?
			")
                ->limit(1)
                ->execute($alias, $insertID);
        }

        // Die ID, mit der der Datensatz eingefügt wurde, dient als Rückgabewert dieser Funktion
        return $insertID;
    }

    protected function getRootPageId() {
        /*
         * Determine the first best root page
         */
        $int_rootId = 0;
        $obj_dbres_rootpages = \Database::getInstance()
            ->prepare("
                SELECT		*
                FROM		`tl_page`
                WHERE		`type` = 'root'
            ")
            ->execute();
        
        if ($obj_dbres_rootpages->numRows) {
            while ($obj_dbres_rootpages->next()) {
                if ($obj_dbres_rootpages->fallback || !$int_rootId) {
                    $int_rootId = $obj_dbres_rootpages->id;
                }
            }
        }

        return $int_rootId;
    }


    private function generateSymlinks()
    {
        $obj_automator = \Controller::importStatic('Contao\Automator', 'Automator');
        $obj_automator->generateSymlinks();

    }

    public function parse()
    {
        $this->obj_template->arr_installedThemeExtensions = $this->arr_installedThemeExtensions;
        $this->obj_template->int_status = $this->int_status;
        return $this->obj_template->parse();
    }

    private function getStatus()
    {
        if (isset($GLOBALS['TL_CONFIG']['ls_shop_installedCompletely']) && $GLOBALS['TL_CONFIG']['ls_shop_installedCompletely']) {
            $this->int_status = themeInstallerStatus::FINISHED;
        } else if (!count($this->arr_installedThemeExtensions)) {
            $this->int_status = themeInstallerStatus::THEME_EXTENSION_NOT_INSTALLED;
        } else if (count($this->arr_installedThemeExtensions) > 1) {
            $this->int_status = themeInstallerStatus::MULTIPLE_THEME_EXTENSION_INSTALLED;
        } else if (!isset($GLOBALS['TL_CONFIG']['ls_api_key']) || !$GLOBALS['TL_CONFIG']['ls_api_key']) {
            $this->int_status = themeInstallerStatus::NO_API_KEY;
        } else if (
            !\Database::getInstance()->fieldExists('ls_cnc_languageSelector_correspondingMainLanguagePage', 'tl_page')
            || !\Database::getInstance()->tableExists('tl_ls_shop_orders')
        ) {
            $this->int_status = themeInstallerStatus::DB_NOT_OK;
        } else {
            $this->int_status = themeInstallerStatus::READY_FOR_SETUP;
        }
    }

    private function getInstalledThemeExtensions()
    {
        $str_composerLockContent = file_get_contents(TL_ROOT . '/composer.lock');
        preg_match_all('/"name".*?:.*?"(.*\/merconis-theme.*)"/', $str_composerLockContent, $arr_matches);
        $this->arr_installedThemeExtensions = $arr_matches[1];
    }
}

abstract class themeInstallerStatus
{
    const UNKNOWN = 0;
    const THEME_EXTENSION_NOT_INSTALLED = 1;
    const READY_FOR_SETUP = 2;
    const FINISHED = 3;
    const MULTIPLE_THEME_EXTENSION_INSTALLED = 4;
    const NO_API_KEY = 5;
    const DB_NOT_OK = 6;
}
