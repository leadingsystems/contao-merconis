<?php

namespace Merconis\Core;

use Contao\Database;
use Contao\File;
use Contao\Folder;
use Contao\System;
use Contao\ZipWriter;

class ls_shop_apiController_themeExporter_legacy
{
    protected $tmpExportDir = 'merconisTmpThemeExport';
    protected $targetExportDir = 'merconisThemeExport';

    protected $themeSrcParentDir = 'files/merconisfiles/theme/../..';
    protected $themeSrcDir = null;
    protected $themeSrcDirName = null;

    protected $newThemeFolder = false;

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

    private function __clone()
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
    protected function apiResource_merconisThemeExporter_export_legacy()
    {
        $this->obj_apiReceiver->requireScope(['BE']);
        $this->obj_apiReceiver->requireUser(['beUser']);

        $this->getThemeSrcDir();

        $this->checkDNS();

        /*
         * Set the exportZipFileName
         */
        $this->exportZipFileName = $this->themeSrcDirName . '.chk.zip';

        $this->createExportTmpFolder();
        $this->writeZipExportFile();
        $this->createExportTargetFolder();
        $this->moveFilesToExportTargetFolder();
        $this->deleteTmpExportDir();

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data('successfully exported to ' . $this->exportZipFileName);
    }

    protected function getThemeSrcDir()
    {
        $this->themeSrcDir = $this->themeSrcParentDir . '/theme';
        $this->themeSrcDirName = 'theme';
    }

    protected function checkDNS()
    {
        $obj_dbres_rootDns = Database::getInstance()->prepare("
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

    protected function createExportTmpFolder()
    {
        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
        /*
         * Remove a possibly existing old tmp export directory
         */
        if (is_dir($str_projectDir . '/' . $this->tmpExportDir)) {
            $this->rmdirRecursively($str_projectDir . '/' . $this->tmpExportDir);
        }

        /*
         * Create a new and empty tmp export directory
         */
        mkdir($str_projectDir . '/' . $this->tmpExportDir);

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
        if (is_dir($str_projectDir . '/' . $this->tmpExportDir)) {
            $this->rmdirRecursively($str_projectDir . '/' . $this->tmpExportDir, 'doNotExport');
        }

    }

    protected function writeZipExportFile()
    {
        $objArchive = new ZipWriter($this->tmpExportDir . '/' . $this->exportZipFileName);
        $this->addFolderToArchive($objArchive, $this->tmpExportDir . '/' . $this->themeSrcDirName);
        $objArchive->close();
    }

    protected function addFolderToArchive(ZipWriter $objArchive, $strFolder)
    {
        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
        // Return if the folder does not exist
        if (!is_dir($str_projectDir . '/' . $strFolder)) {
            return;
        }

        // Recursively add the files and subfolders
        foreach (Folder::scan($str_projectDir . '/' . $strFolder) as $strFile) {
            if (is_dir($str_projectDir . '/' . $strFolder . '/' . $strFile)) {
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
        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
        /*
         * Create a new and empty tmp export directory if there isn't one already
         */
        if (!is_dir($str_projectDir . '/' . $this->targetExportDir)) {
            mkdir($str_projectDir . '/' . $this->targetExportDir);
        }
    }

    protected function moveFilesToExportTargetFolder()
    {
        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
        /*
         * Move the export zip file
         */
        if (is_file($str_projectDir . '/' . $this->targetExportDir . '/' . $this->exportZipFileName)) {
            unlink($str_projectDir . '/' . $this->targetExportDir . '/' . $this->exportZipFileName);
        }
        rename($str_projectDir . '/' . $this->tmpExportDir . '/' . $this->exportZipFileName, $str_projectDir . '/' . $this->targetExportDir . '/' . $this->exportZipFileName);
        chmod($str_projectDir . '/' . $this->targetExportDir . '/' . $this->exportZipFileName, 0755);

        /*
         * Copy the themeInfo.dat
         */
        if (is_file($str_projectDir . '/' . $this->targetExportDir . '/themeInfo.chk.dat')) {
            unlink($str_projectDir . '/' . $this->targetExportDir . '/themeInfo.chk.dat');
        }
        copy($str_projectDir . '/themeInfo.dat', $str_projectDir . '/' . $this->targetExportDir . '/themeInfo.chk.dat');

        /*
         * Create the file holding the md5 hash of the export file
         */
        if (is_file($str_projectDir . '/' . $this->targetExportDir . '/' . $this->exportHashFilename)) {
            unlink($str_projectDir . '/' . $this->targetExportDir . '/' . $this->exportHashFilename);
        }
        file_put_contents($str_projectDir . '/' . $this->targetExportDir . '/' . $this->exportHashFilename, md5_file($str_projectDir . '/' . $this->targetExportDir . '/' . $this->exportZipFileName));
    }

    protected function deleteTmpExportDir()
    {
        $this->rmdirRecursively(System::getContainer()->getParameter('kernel.project_dir') . '/' . $this->tmpExportDir);
    }

    /*
     * Do not use the contao file and folder classes because we copy
     * files outside of the upload path and that causes problems with the
     * DBAFS if we use the contao classes.
     */
    protected function dirCopy($src, $dest)
    {
        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
        if (!file_exists($str_projectDir . '/' . $src) || file_exists($str_projectDir . '/' . $dest)) {
            return;
        }

        if (is_file($str_projectDir . '/' . $src)) {
            copy($str_projectDir . '/' . $src, $str_projectDir . '/' . $dest);
            return;
        }

        if (is_dir($str_projectDir . '/' . $src)) {
            mkdir($str_projectDir . '/' . $dest);
            $sourceHandle = opendir($str_projectDir . '/' . $src);
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