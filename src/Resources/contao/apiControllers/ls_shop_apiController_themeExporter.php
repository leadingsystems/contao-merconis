<?php

namespace Merconis\Core;

class ls_shop_apiController_themeExporter
{
    protected $tmpExportDir = 'merconisTmpThemeExport';
    protected $targetExportDir = 'merconisThemeExport';

    protected $themeSrcParentDir = 'files/merconisfiles/themes';
    protected $themeSrcDir = null;
    protected $themeSrcDirName = null;

    protected $themeTemplatesSrcParentDir = 'templates';
    protected $themeTemplatesSrcDir = null;
    protected $themeTemplatesSrcDirName = null;

    protected $exportZipFileName = '';
    protected $exportHashFilename = 'hash.chk.dat';

    protected static $objInstance;

    /** @var \LeadingSystems\Api\ls_apiController $obj_apiReceiver */
    protected $obj_apiReceiver = null;

    protected function __construct()
    {
    }

    final private function __clone()
    {
    }

    public static function getInstance()
    {
        if (!is_object(self::$objInstance)) {
            self::$objInstance = new self();
        }

        return self::$objInstance;
    }

    public function processRequest($str_resourceName, $obj_apiReceiver)
    {
        if (!$str_resourceName || !$obj_apiReceiver) {
            return;
        }

        $this->obj_apiReceiver = $obj_apiReceiver;

        /*
         * If this class has a method that matches the resource name, we call it.
         * If not, we don't do anything because another class with a corresponding
         * method might have a hook registered.
         */
        if (method_exists($this, $str_resourceName)) {
            $this->{$str_resourceName}();
        }
    }

    /**
     * Merconis Installer:
     *
     * Test
     *
     * Scope: BE
     *
     * Allowed user types: beUser
     */
    protected function apiResource_merconisThemeExporter_export()
    {
        $this->obj_apiReceiver->requireScope(['BE']);
        $this->obj_apiReceiver->requireUser(['beUser']);

        $this->getThemeSrcDir();
        $this->checkThemeFolderName();
        $this->checkDNS();
        $this->getThemeTemplatesSrcDir();
        $this->checkThemeTemplatesFolderName();

        /*
         * Set the exportZipFileName
         */
        $this->exportZipFileName = $this->themeSrcDirName . '.chk.zip';

        /*
         * Make sure that there is an empty data folder in the source
         */
        $this->makeEmptyDataFolderInSrc();

        $this->createExportTmpFolder();
        $this->exportLocalconfig();
        $this->exportTables();
        $this->writeZipExportFile();
        $this->createExportTargetFolder();
        $this->moveFilesToExportTargetFolder();
        $this->deleteTmpExportDir();

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data('successfully exported to ' . $this->exportZipFileName);
    }

    protected function getThemeSrcDir()
    {
        $arrThemeFolders = array_diff(scandir(TL_ROOT . '/' . $this->themeSrcParentDir), array('.', '..'));

        /*
         * Remove elements that aren't directories
         */
        foreach ($arrThemeFolders as $k => $item) {
            if (!is_dir(TL_ROOT . '/' . $this->themeSrcParentDir . '/' . $item)) {
                unset ($arrThemeFolders[$k]);
            }
        }

        if (count($arrThemeFolders) == 0) {
            throw new \Exception('The theme source directory does not exist.');
        }

        if (count($arrThemeFolders) > 1) {
            throw new \Exception('There is more than one theme source directory. Please make sure that there is only the one that should be used for the export.');
        }

        $this->themeSrcDir = count($arrThemeFolders) == 1 ? $this->themeSrcParentDir . '/' . current($arrThemeFolders) : null;

        $this->themeSrcDirName = count($arrThemeFolders) == 1 ? current($arrThemeFolders) : null;
    }

    protected function checkThemeFolderName()
    {
        /*
         * Check if the theme folder is named properly. This is important because the Merconis installer relies on it.
         */
        $themeNamePattern = '/^theme[0-9]+$/';
        if (!preg_match($themeNamePattern, $this->themeSrcDirName)) {
            throw new \Exception(sprintf('The the theme folder to export (%s) is not named properly (%s)', $this->themeSrcDirName, $themeNamePattern));
        }
    }

    protected function checkDNS()
    {
        $obj_dbres_rootDns = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_page`
                WHERE       `type` = 'root'
                    AND     `dns` = ''
			")
            ->execute();

        if ($obj_dbres_rootDns->numRows) {
            throw new \Exception('Domain entry/DNS in the root page is not set');
        }
    }

    protected function getThemeTemplatesSrcDir()
    {
        $arrThemeTemplatesFolders = array_diff(scandir(TL_ROOT . '/' . $this->themeTemplatesSrcParentDir), array('.', '..'));

        /*
         * Remove elements that aren't directories
         */
        foreach ($arrThemeTemplatesFolders as $k => $item) {
            if (!is_dir(TL_ROOT . '/' . $this->themeTemplatesSrcParentDir . '/' . $item)) {
                unset ($arrThemeTemplatesFolders[$k]);
            }
        }

        if (count($arrThemeTemplatesFolders) > 1) {
            throw new \Exception('There is more than one theme templates directory. Please make sure that there is only the one that should be used for the export.');
        }

        if (count($arrThemeTemplatesFolders) == 1) {
            $this->themeTemplatesSrcDir = $this->themeTemplatesSrcParentDir . '/' . current($arrThemeTemplatesFolders);
            $this->themeTemplatesSrcDirName = current($arrThemeTemplatesFolders);
        } else {
            $this->themeTemplatesSrcDir = '';
            $this->themeTemplatesSrcDirName = '';
        }
    }

    protected function checkThemeTemplatesFolderName()
    {
        $expectedThemeTemplatesSrcDirName = 'merconisTemplates' . ucfirst($this->themeSrcDirName);
        if ($this->themeTemplatesSrcDirName && $this->themeTemplatesSrcDirName != $expectedThemeTemplatesSrcDirName) {
            throw new \Exception(sprintf('The templates folder (%s) does not have the expected name (%s)', $this->themeTemplatesSrcDirName, $expectedThemeTemplatesSrcDirName));
        }
    }

    protected function makeEmptyDataFolderInSrc()
    {
        $dataDir = TL_ROOT . '/' . $this->themeSrcDir . '/data';

        // first remove a possibly already existing data dir
        if (is_dir($dataDir)) {
            $this->rmdirRecursively($dataDir);
        } else if (is_file($dataDir)) {
            unlink($dataDir);
        }

        // and then create a new empty one
        mkdir($dataDir);
    }

    protected function createExportTmpFolder()
    {
        /*
         * Remove a possibly existing old tmp export directory
         */
        if (is_dir(TL_ROOT . '/' . $this->tmpExportDir)) {
            $this->rmdirRecursively(TL_ROOT . '/' . $this->tmpExportDir);
        }

        /*
         * Create a new and empty tmp export directory
         */
        mkdir(TL_ROOT . '/' . $this->tmpExportDir);

        /*
         * Copy the theme folder to the tmp export directory
         */
        $this->dirCopy($this->themeSrcDir, $this->tmpExportDir . '/' . $this->themeSrcDirName);

        /*
         * Copy the theme's template folder if it exists
         */
        if ($this->themeTemplatesSrcDir) {
            $this->dirCopy($this->themeTemplatesSrcDir, $this->tmpExportDir . '/' . $this->themeSrcDirName . '/' . $this->themeTemplatesSrcDirName);
        }

        /*
         * Remove forbidden folders from tmp export directory
         */
        if (is_dir(TL_ROOT . '/' . $this->tmpExportDir)) {
            $this->rmdirRecursively(TL_ROOT . '/' . $this->tmpExportDir, 'doNotExport');
        }

    }

    /**
     * Diese Funktion exportiert alle localconfig-Einträge, die mit dem Präfix
     * "ls_shop_" beginnen und speichert sie in die Export-Datei. Der Eintrag "ls_shop_installedCompletely"
     * wird natürlich nicht exportiert, obwohl er mit "ls_shop_" beginnt, da dieses Flag
     * ja erst nach abgeschlossener Installation gesetzt wird!
     */
    protected function exportLocalconfig()
    {
        $arrLocalconfigExport = array();
        foreach ($GLOBALS['TL_CONFIG'] as $k => $v) {
            if (preg_match('/^ls_shop_/siU', $k) && $k != 'ls_shop_installedCompletely') {
                $arrLocalconfigExport[$k] = $v;
            }
        }

        $objFile = new \File($this->tmpExportDir . '/' . $this->themeSrcDirName . '/data/exportLocalconfig.dat');
        $objFile->write(serialize($arrLocalconfigExport));
        $objFile->close();
    }

    /**
     * Diese Funktion exportiert die Datensätze aller relevanten Tabellen. Es werden dabei
     * konsequent alle enthaltenen Datensätze exportiert, das Projekt, aus dem exportiert wird,
     * muss daher vor dem Export bereinigt sein.
     */
    protected function exportTables()
    {
        $arrTables = array(
            'tl_article' => array(),
            'tl_content' => array(),
            'tl_files' => array(),
            'tl_form' => array(),
            'tl_form_field' => array(),
            'tl_layout' => array(),

            'tl_ls_shop_attributes' => array(),
            'tl_ls_shop_attribute_allocation' => array(),
            'tl_ls_shop_attribute_values' => array(),
            'tl_ls_shop_configurator' => array(),
            'tl_ls_shop_coupon' => array(),
            'tl_ls_shop_cross_seller' => array(),
            'tl_ls_shop_delivery_info' => array(),
            'tl_ls_shop_export' => array(),
            'tl_ls_shop_filter_fields' => array(),
            'tl_ls_shop_filter_field_values' => array(),
            'tl_ls_shop_message_model' => array(),
            'tl_ls_shop_message_type' => array(),
            'tl_ls_shop_output_definitions' => array(),
            'tl_ls_shop_payment_methods' => array(),
            'tl_ls_shop_product' => array(),
            'tl_ls_shop_shipping_methods' => array(),
            'tl_ls_shop_steuersaetze' => array(),
            'tl_ls_shop_variant' => array(),

            'tl_member' => array(),
            'tl_member_group' => array(),
            'tl_module' => array(),
            'tl_newsletter_channel' => array(),
            'tl_page' => array(),
            'tl_theme' => array(),
            'tl_news_archive' => array(),
            'tl_news' => array(),
            'tl_image_size' => array(),
            'tl_image_size_item' => array(),
        );

        foreach ($arrTables as $tableName => $v) {
            $objQuery = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`" . $tableName . "`
			")
                ->execute();
            $arrTables[$tableName] = $objQuery->fetchAllAssoc();
        }

        /*
         * Remove all rows from tl_files where the respective file is not part of the installerResources
         */
        foreach ($arrTables['tl_files'] as $k => $v) {
            if (!preg_match('/merconisfiles/', $v['path'])) {
                unset($arrTables['tl_files'][$k]);
            }
        }

        $objFile = new \File($this->tmpExportDir . '/' . $this->themeSrcDirName . '/data/exportTables.dat');
        $objFile->write(serialize($arrTables));
        $objFile->close();
    }

    protected function writeZipExportFile()
    {
        $objArchive = new \ZipWriter($this->tmpExportDir . '/' . $this->exportZipFileName);
        $this->addFolderToArchive($objArchive, $this->tmpExportDir . '/' . $this->themeSrcDirName);
        $objArchive->close();
    }

    protected function addFolderToArchive(\ZipWriter $objArchive, $strFolder)
    {
        // Return if the folder does not exist
        if (!is_dir(TL_ROOT . '/' . $strFolder)) {
            return;
        }

        // Recursively add the files and subfolders
        foreach (scan(TL_ROOT . '/' . $strFolder) as $strFile) {
            if (is_dir(TL_ROOT . '/' . $strFolder . '/' . $strFile)) {
                $this->addFolderToArchive($objArchive, $strFolder . '/' . $strFile);
            } else {
                $strTarget = preg_replace('/' . preg_quote($this->tmpExportDir, '/') . '/', '', $strFolder);
                // Always store files in files and convert the directory upon import
                $objArchive->addFile($strFolder . '/' . $strFile, $strTarget . '/' . $strFile);
            }
        }
    }

    protected function createExportTargetFolder()
    {
        /*
         * Create a new and empty tmp export directory if there isn't one already
         */
        if (!is_dir(TL_ROOT . '/' . $this->targetExportDir)) {
            mkdir(TL_ROOT . '/' . $this->targetExportDir);
        }
    }

    protected function moveFilesToExportTargetFolder()
    {
        /*
         * Move the export zip file
         */
        if (is_file(TL_ROOT . '/' . $this->targetExportDir . '/' . $this->exportZipFileName)) {
            unlink(TL_ROOT . '/' . $this->targetExportDir . '/' . $this->exportZipFileName);
        }
        rename(TL_ROOT . '/' . $this->tmpExportDir . '/' . $this->exportZipFileName, TL_ROOT . '/' . $this->targetExportDir . '/' . $this->exportZipFileName);
        chmod(TL_ROOT . '/' . $this->targetExportDir . '/' . $this->exportZipFileName, 0755);

        /*
         * Copy the themeInfo.dat
         */
        if (is_file(TL_ROOT . '/' . $this->targetExportDir . '/themeInfo.chk.dat')) {
            unlink(TL_ROOT . '/' . $this->targetExportDir . '/themeInfo.chk.dat');
        }
        copy(TL_ROOT . '/' . $this->tmpExportDir . '/' . $this->themeSrcDirName . '/themeInfo.dat', TL_ROOT . '/' . $this->targetExportDir . '/themeInfo.chk.dat');

        /*
         * Create the file holding the md5 hash of the export file
         */
        if (is_file(TL_ROOT . '/' . $this->targetExportDir . '/' . $this->exportHashFilename)) {
            unlink(TL_ROOT . '/' . $this->targetExportDir . '/' . $this->exportHashFilename);
        }
        file_put_contents(TL_ROOT . '/' . $this->targetExportDir . '/' . $this->exportHashFilename, md5_file(TL_ROOT . '/' . $this->targetExportDir . '/' . $this->exportZipFileName));
    }

    protected function deleteTmpExportDir()
    {
        $this->rmdirRecursively(TL_ROOT . '/' . $this->tmpExportDir);
    }

    /*
     * Do not use the contao file and folder classes because we copy
     * files outside of the upload path and that causes problems with the
     * DBAFS if we use the contao classes.
     */
    protected function dirCopy($src, $dest)
    {
        if (!file_exists(TL_ROOT . '/' . $src) || file_exists(TL_ROOT . '/' . $dest)) {
            return;
        }

        if (is_file(TL_ROOT . '/' . $src)) {
            copy(TL_ROOT . '/' . $src, TL_ROOT . '/' . $dest);
            return;
        }

        if (is_dir(TL_ROOT . '/' . $src)) {
            mkdir(TL_ROOT . '/' . $dest);
            $sourceHandle = opendir(TL_ROOT . '/' . $src);
            while ($file = readdir($sourceHandle)) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $this->dirCopy($src . '/' . $file, $dest . '/' . $file);
            }
        }
    }

    protected function rmdirRecursively($dir = null, $str_dirNamePattern = null)
    {
        if (!$dir) {
            return;
        }

        if (is_dir($dir)) {
            if (
                $str_dirNamePattern === null
                || strpos($dir, $str_dirNamePattern) !== false
            ) {
                $bln_deleteThisDir = true;
            } else {
                $bln_deleteThisDir = false;
            }

            $objects = scandir($dir);

            foreach ($objects as $object) {
                if ($object == "." || $object == "..") {
                    continue;
                }

                if (is_dir($dir . "/" . $object)) {
                    $this->rmdirRecursively($dir . "/" . $object, $bln_deleteThisDir ? null : $str_dirNamePattern);
                } else {
                    if ($bln_deleteThisDir) {
                        unlink($dir . "/" . $object);
                    }
                }
            }

            if ($bln_deleteThisDir) {
                $var_deletedDir = rmdir($dir);
            }
        }
    }
}