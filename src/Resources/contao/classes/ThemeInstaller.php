<?php

namespace Merconis\Core;

class ThemeInstaller
{
    protected $obj_template = null;

    private $int_status = themeInstallerStatus::UNKNOWN;
    private $arr_installedThemeExtensions = [];

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
        $this->obj_template = new \FrontendTemplate('lsm_themeInstaller');
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
