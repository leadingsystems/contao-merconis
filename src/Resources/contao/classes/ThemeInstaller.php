<?php

namespace Merconis\Core;

use Contao\CoreBundle\Util\SymlinkUtil;

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
            \Controller::reload();
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
        $this->restoreForeignKeyRelations();
        $this->updateInsertTagCorrelations__insert_module();
        \Config::getInstance()->update("\$GLOBALS['TL_CONFIG']['ls_shop_installedCompletely']", true);
        \System::log('MERCONIS INSTALLER: Setting installation complete flag in localconfig.php', 'MERCONIS INSTALLER', TL_MERCONIS_INSTALLER);
    }

    /**
     * This function searches insert tags that include modules and updates the module IDs specified there
     * to yield valid references to the modules' new IDs after import.
     */
    private function updateInsertTagCorrelations__insert_module()
    {
        $str_pattern = '/(\{\{insert_module::)(.*)(\}\})/siU';

        $arr_tablesToConsider = array('tl_module', 'tl_content');

        foreach ($arr_tablesToConsider as $str_tableName) {
            if (!isset($this->arr_mapOldIDToNewID[$str_tableName]) || !is_array($this->arr_mapOldIDToNewID[$str_tableName])) {
                continue;
            }

            foreach ($this->arr_mapOldIDToNewID[$str_tableName] as $int_insertedElementId) {
                $obj_dbres_recordsToHandle = \Database::getInstance()
                    ->prepare("
                        SELECT		*
                        FROM		`" . $str_tableName . "`
                        WHERE		`id` = ?
                    ")
                    ->limit(1)
                    ->execute($int_insertedElementId);

                $arr_currentRecordToHandle = $obj_dbres_recordsToHandle->first()->row();

                $str_setStatement = '';
                foreach ($arr_currentRecordToHandle as $str_fieldName => $str_value) {
                    preg_match_all($str_pattern, $str_value, $arr_matches);
                    if (is_array($arr_matches[2]) && count($arr_matches[2])) {
                        foreach ($arr_matches[2] as $k => $int_oldModuleID) {
                            $str_insertTagToReplace = '/\{\{insert_module::'.$int_oldModuleID.'\}\}/siU';

                            /*
                             * Get the new module id to replace the old one used in the original insert tag
                             * and then place it in the replacement string.
                             *
                             * The usage of 'tl_module' here has nothing to do with the tables to consider
                             * when looking for occurrences of insert tags to update! So even if we currently
                             * handle insert tags in another table, we still have to get a new module ID for
                             * an old one and find it in the mapping array for tl_module.
                             */
                            $str_insertTagNew = $arr_matches[1][$k].$this->arr_mapOldIDToNewID['tl_module'][$int_oldModuleID].$arr_matches[3][$k];

                            $arr_currentRecordToHandle[$str_fieldName] = preg_replace($str_insertTagToReplace, $str_insertTagNew, $arr_currentRecordToHandle[$str_fieldName]);
                        }
                    }

                    if ($str_setStatement) {
                        $str_setStatement .= ",\r\n";
                    }
                    $str_setStatement .= "`".$str_fieldName."` = ?";
                }

                $arr_queryValues = $arr_currentRecordToHandle;
                $arr_queryValues[] = $arr_currentRecordToHandle['id'];

                \Database::getInstance()
                    ->prepare("
                        UPDATE		`" . $str_tableName . "`
                        SET			" . $str_setStatement . "
                        WHERE		`id` = ?
                    ")
                    ->limit(1)
                    ->execute($arr_queryValues);
            }
        }
    }

    /**
     * In this function, all newly inserted records are run through and checked to see if any ForeignKey relationships
     * exist. If this is the case, these relationships are restored.
     *
     * FIXME: Attention, so far only IDs as foreignKey are supported here.
     * It is possible that aliases will also be relevant as foreignKey!
     */
    private function restoreForeignKeyRelations() {
        // get the foreignKey relations
        $arr_relations = $this->getDatabaseRelations();

        /*
         * All relations are now processed individually. For each relation, all records to be corrected are read,
         * i.e. the records that were newly inserted in the corresponding table. The newly inserted records are those
         * that are contained in the array $this->arr_mapOldIDToNewID.
         */
        \LeadingSystems\Helpers\lsErrorLog('$this->arr_mapOldIDToNewID', $this->arr_mapOldIDToNewID, 'lslog_14');
        foreach ($arr_relations as $arr_relation) {
            \LeadingSystems\Helpers\lsErrorLog('$arr_relation', $arr_relation, 'lslog_14');

            if ($arr_relation['pTable'] == 'localconfig') { // Es handelt sich um eine Relation zur localconfig
                $str_oldForeignKey = $GLOBALS['TL_CONFIG'][$arr_relation['pField']];
                $str_newForeignKey = $this->getNewForeignKey($arr_relation, $str_oldForeignKey);

                /*
                 * Eintragen des neuen foreignKey in localconfig
                 */
                \Config::getInstance()->update("\$GLOBALS['TL_CONFIG']['".$arr_relation['pField']."']", $str_newForeignKey);

            } else { // It is a relation of two DB tables

                // All newly inserted records of the current pTable are read out
                if (is_array($this->arr_mapOldIDToNewID[$arr_relation['pTable']])) {
                    foreach ($this->arr_mapOldIDToNewID[$arr_relation['pTable']] as $int_pTableRowId) {
                        $obj_dbres_row = \Database::getInstance()
                            ->prepare("
                                SELECT		*
                                FROM		`" . $arr_relation['pTable'] . "`
                                WHERE		`id` = ?
                            ")
                            ->execute($int_pTableRowId);

                        if (!$obj_dbres_row->numRows) {
                            continue;
                        }

                        // Reading out the previously stored assignment
                        $str_oldForeignKey = $obj_dbres_row->{$arr_relation['pField']};
                        \LeadingSystems\Helpers\lsErrorLog('pTableRow', $obj_dbres_row->row(), 'lslog_14');
                        \LeadingSystems\Helpers\lsErrorLog('$str_oldForeignKey', $str_oldForeignKey, 'lslog_14');

                        $str_newForeignKey = $this->getNewForeignKey($arr_relation, $str_oldForeignKey);
                        $str_newForeignKey = $str_newForeignKey ? $str_newForeignKey : 0;

                        /*
                         * Entering the new foreignKey
                         */
                        $obj_dbquery_update = \Database::getInstance()
                            ->prepare("
                                UPDATE		`" . $arr_relation['pTable'] . "`
                                SET			`" . $arr_relation['pField'] . "` = ?
                                WHERE		`id` = ?
                            ")
                            ->limit(1)
                            ->execute($str_newForeignKey, $int_pTableRowId);
                        \LeadingSystems\Helpers\lsErrorLog('$obj_dbquery_update->query:', $obj_dbquery_update->query, 'lslog_14');
                    }
                }
            }
        }
    }

    private function getNewForeignKey($arr_relation, $int_oldForeignKey)
    {
        switch ($arr_relation['relationType']) {
            case 'single': // The ForeignKey is a single value
                \LeadingSystems\Helpers\lsErrorLog('single!', '', 'lslog_14');
                /*
                 * Now it is determined which is the new foreignKey. For this we look in the array
                 * $this->arr_mapOldIDToNewID in the key for the corresponding cTable (i.e. the linked table),
                 * what the new foreignKey is to the old foreignKey.
                 */
                $int_newForeignKey = $this->arr_mapOldIDToNewID[$arr_relation['cTable']][$int_oldForeignKey];
                \LeadingSystems\Helpers\lsErrorLog('$int_newForeignKey = $this->arr_mapOldIDToNewID['.$arr_relation['cTable'].']['.$int_oldForeignKey.'];', $int_newForeignKey, 'lslog_14');
                break;

            case 'array': // The ForeignKey is a (serialized) array
                \LeadingSystems\Helpers\lsErrorLog('array!', '', 'lslog_14');
                $arr_oldForeignKeys =  is_array($int_oldForeignKey) ? $int_oldForeignKey : deserialize($int_oldForeignKey);
                $arr_newForeignKeys = array();
                if (is_array($arr_oldForeignKeys)) {
                    foreach ($arr_oldForeignKeys as $k => $int_oldForeignKey) {
                        /*
                         * Important: The determined foreignKey is explicitly stored as a string in the serialized array.
                         * This is important for product page selection, because the quotes used when storing a value in
                         * string form in the serialized array are important for recognizing product page assignments!
                         */
                        $arr_newForeignKeys[$k] = strval($this->arr_mapOldIDToNewID[$arr_relation['cTable']][$int_oldForeignKey]);

                        \LeadingSystems\Helpers\lsErrorLog('$arr_newForeignKeys['.$k.'] = $this->arr_mapOldIDToNewID['.$arr_relation['cTable'].']['.$int_oldForeignKey.'];', $arr_newForeignKeys[$k], 'lslog_14');
                    }
                } else {
                    \LeadingSystems\Helpers\lsErrorLog('old foreign key is not an array', $arr_relation, 'lslog_14');
                    \LeadingSystems\Helpers\lsErrorLog('old foreign key: ', $arr_oldForeignKeys, 'lslog_14');
                }
                $int_newForeignKey = serialize($arr_newForeignKeys);
                break;

            case 'special': // Der ForeignKey ist in einem speziellen Format gespeichert, der gesondert gehandhabt werden muss
                \LeadingSystems\Helpers\lsErrorLog('special!', '', 'lslog_14');
                switch ($arr_relation['pTable']) {
                    case 'tl_layout':
                        switch ($arr_relation['pField']) {
                            case 'modules':
                                /*
                                 * Special case: Module assignments in tl_layout are not a simple array. Instead, modules
                                 * and their assignments to content areas are recorded in a complex way here. Accordingly,
                                 * special handling is necessary to read out the old module IDs from the original
                                 * foreignKey, determine the new module IDs, and recreate the correct foreignKey with
                                 * the new module IDs.
                                 */
                                $arr_modulesInLayout = is_array($int_oldForeignKey) ? $int_oldForeignKey : deserialize($int_oldForeignKey);
                                foreach ($arr_modulesInLayout as $k => $v) {
                                    if (!$v['mod']) {
                                        continue;
                                    }
                                    $int_oldForeignKey = $v['mod'];
                                    $arr_modulesInLayout[$k]['mod'] = strval($this->arr_mapOldIDToNewID[$arr_relation['cTable']][$int_oldForeignKey]);
                                }
                                $int_newForeignKey = serialize($arr_modulesInLayout);
                                break;
                        }
                        break;
                }
                break;

            default:
                throw new \Exception('unsupported relation type given');
                break;
        }
        return $int_newForeignKey;
    }

    private function getDatabaseRelations()
    {
        $arr_relations = array();
        $str_relationsFile = file_get_contents(TL_ROOT.'/vendor/leadingsystems/contao-merconis/src/Resources/contao/config/database.sql');
        preg_match_all('/@(.*)\.(.*)@(.*)\.(.*)=(.*)@/', $str_relationsFile, $arr_matches);
        foreach ($arr_matches[0] as $k => $v) {
            $arr_relations[] = array(
                'pTable' => $arr_matches[1][$k],
                'pField' => $arr_matches[2][$k],
                'cTable' => $arr_matches[3][$k],
                'cField' => $arr_matches[4][$k],
                'relationType' => $arr_matches[5][$k]
            );
        }
        \LeadingSystems\Helpers\lsErrorLog('$arr_relations', $arr_relations, 'lslog_14');
        return $arr_relations;
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

        $str_setStatement = '';

        foreach ($arr_data as $str_fieldName => $var_value) {
            /*
             * If the field does not exist in the target table, the field will not be included in the insert statement.
             * If IDs are not to be retained and it is the ID field, it will not be included in the insert statement.
             * If aliases are not to be preserved and it is the alias field, it will not be included in the insert statement.
             */
            if (!\Database::getInstance()->fieldExists($str_fieldName, $str_targetTable) || (!$bln_preserveId && $str_fieldName == 'id') || (!$bln_preserveAlias && $str_fieldName == 'alias')) {
                unset($arr_data[$str_fieldName]);
            } else {
                if ($str_setStatement) {
                    $str_setStatement .= ",\r\n";
                }
                $str_setStatement .= "`".$str_fieldName."` = ?";
            }
        }

        $obj_dbquery = \Database::getInstance()->prepare("
			INSERT INTO `".$str_targetTable."`
			SET		".$str_setStatement."
		")
            ->execute($arr_data);

        $int_insertId = $obj_dbquery->insertId;

        /*
         * If the alias is not to be retained and the alias field exists in the target table, a new alias is generated,
         * ensuring that the alias is unique by appending the record ID if required.
         */
        if (!$bln_preserveAlias && \Database::getInstance()->fieldExists('alias', $str_targetTable)) {
            $str_alias = (isset($arr_data['title']) && $arr_data['title'] ? standardize(\StringUtil::restoreBasicEntities($arr_data['title'])) : 'record-'.$int_insertId);

            $str_alias = strlen($str_alias) > 100 ? substr($str_alias, 0, 100) : $str_alias;

            $obj_dbres_checkAlias = \Database::getInstance()
                ->prepare("
                    SELECT		`id`
                    FROM		`".$str_targetTable."`
                    WHERE		`alias` = ?
                ")
                ->execute($str_alias);

            if ($obj_dbres_checkAlias->numRows) {
                $str_alias = $str_alias.'-'.$int_insertId;
            }

            \Database::getInstance()
                ->prepare("
                    UPDATE		`".$str_targetTable."`
                    SET			`alias` = ?
                    WHERE		`id` = ?
                ")
                ->limit(1)
                ->execute($str_alias, $int_insertId);
        }

        // The ID with which the record was inserted serves as the return value of this function
        return $int_insertId;
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
        $obj_folder = new \Contao\Folder('files/merconisfiles');
        $obj_folder->unprotect();
        try {
            SymlinkUtil::symlink('vendor/' . $this->arr_installedThemeExtensions[0] . '/src/Resources/theme', $obj_folder->path . '/themes', TL_ROOT);
        } catch (\Exception $e) {
            \System::log(TL_MERCONIS_THEME_SETUP . ': Creating symlink failed with message "' . $e->getMessage() . '".', TL_MERCONIS_THEME_SETUP, TL_MERCONIS_THEME_SETUP);
        }

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
