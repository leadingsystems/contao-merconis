<?php

namespace Merconis\Core;

class ls_shop_apiController_themeExporter
{
    protected $tmpExportDir = 'merconisTmpThemeExport/theme';
    protected $targetExportDir = 'merconisThemeExport/theme';

    protected $themeSrcDir = 'files/merconisfiles/theme';

    protected $themeTemplatesSrcDir = 'templates/merconis-theme';

    protected $exportZipFileName = 'theme.zip';

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

        $this->checkDNS();

        /*
         * Make sure that there is an empty data folder in the source
         */
        $this->makeEmptyDataFolderInSrc();

        $this->createExportTmpFolder();
        $this->createExportTargetFolder();

        $this->exportLocalconfig();
        $this->exportTables();

        $this->writeZipExportFile();
        $this->deleteTmpExportDir();

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data('successfully exported to ' . $this->exportZipFileName);
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
        $this->dirCopy($this->themeSrcDir, $this->tmpExportDir);

        /*
         * Copy the theme's template folder if it exists
         */
        if (is_dir($this->themeTemplatesSrcDir)) {
            $this->dirCopy($this->themeTemplatesSrcDir, $this->tmpExportDir . '/' . $this->themeTemplatesSrcDir);
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

        $objFile = new \File($this->tmpExportDir . '/data/exportLocalconfig.dat');
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

        $objFile = new \File($this->tmpExportDir . '/data/exportTables.dat');
        $objFile->write(serialize($arrTables));
        $objFile->close();
    }

    protected function writeZipExportFile()
    {
        $objArchive = new \ZipWriter($this->targetExportDir . '/' . $this->exportZipFileName);
        $this->addFolderToArchive($objArchive, $this->tmpExportDir);
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