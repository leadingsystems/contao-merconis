<?php

namespace Merconis\Core;

use Contao\Database;
use Contao\File;
use Contao\Folder;
use Contao\System;
use Contao\ZipWriter;

class ls_shop_apiController_themeExporter
{
    protected $str_dataExportPath = 'vendor/%s/src/Resources/theme/setup';
    protected $str_localconfigExportFileName = 'exportLocalconfig.dat';
    protected $str_tablesExportFileName = 'exportTables.dat';

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
    protected function apiResource_merconisThemeExporter_export()
    {
        $this->obj_apiReceiver->requireScope(['BE']);
        $this->obj_apiReceiver->requireUser(['beUser']);

        $arr_installedThemeExtensions = ls_shop_generalHelper::getInstalledThemeExtensions();
        $this->str_dataExportPath = sprintf($this->str_dataExportPath, $arr_installedThemeExtensions[0]);
        if (!is_dir(System::getContainer()->getParameter('kernel.project_dir') . '/' . $this->str_dataExportPath)) {
            throw new \Exception('Export directory does not exist: ' . $this->str_dataExportPath);
        }

        $this->checkDNS();

        $this->exportLocalconfig();
        $this->exportTables();

        $this->obj_apiReceiver->success();

        $this->obj_apiReceiver->set_data('successfully exported to ' . $this->str_dataExportPath);
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

    /**
     * Diese Funktion exportiert alle localconfig-Einträge, die mit dem Präfix
     * "ls_shop_" beginnen und speichert sie in die Export-Datei. Der Eintrag "ls_shop_installedCompletely"
     * wird natürlich nicht exportiert, obwohl er mit "ls_shop_" beginnt, da dieses Flag
     * ja erst nach abgeschlossener Installation gesetzt wird!
     */
    protected function exportLocalconfig()
    {
        if (file_exists(System::getContainer()->getParameter('kernel.project_dir') . '/' . $this->str_dataExportPath . '/' . $this->str_localconfigExportFileName)) {
            unlink(System::getContainer()->getParameter('kernel.project_dir') . '/' . $this->str_dataExportPath . '/' . $this->str_localconfigExportFileName);
        }

        $arrLocalconfigExport = array();
        foreach ($GLOBALS['TL_CONFIG'] as $k => $v) {
            if (preg_match('/^ls_shop_/siU', $k) && $k != 'ls_shop_installedCompletely' && $k != 'ls_shop_serial') {
                $arrLocalconfigExport[$k] = $v;
            }
        }

        $objFile = new \File($this->str_dataExportPath . '/' . $this->str_localconfigExportFileName);
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
        if (file_exists(System::getContainer()->getParameter('kernel.project_dir') . '/' . $this->str_dataExportPath . '/' . $this->str_tablesExportFileName)) {
            unlink(System::getContainer()->getParameter('kernel.project_dir') . '/' . $this->str_dataExportPath . '/' . $this->str_tablesExportFileName);
        }

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
            $objQuery = Database::getInstance()->prepare("
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

        $objFile = new \File($this->str_dataExportPath . '/' . $this->str_tablesExportFileName);

        $objFile->write(serialize($arrTables));
        $objFile->close();
    }

}