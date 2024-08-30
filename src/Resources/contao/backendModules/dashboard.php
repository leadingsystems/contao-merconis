<?php

namespace Merconis\Core;

use Composer\InstalledVersions;
use Contao\BackendModule;

class dashboard extends BackendModule {

	protected $strTemplate = 'lsm_dashboard';
	
	
	protected function compile() {

        if(
            empty(ls_shop_generalHelper::getInstalledThemeExtensions()) &&
            !in_array('leadingsystems/merconis-theme-installer', InstalledVersions::getInstalledPackages() )
        )
        {
            //legacy way if no theme installer or theme extension is installed
            $obj_themeInstaller = Installer_legacy::getInstance();
            $this->Template->str_themeInstallerOutput = $obj_themeInstaller->parse();
        }else{

            $obj_themeInstaller = ThemeInstaller::getInstance();
            $this->Template->str_themeInstallerOutput = $obj_themeInstaller->parse();
        }

	}
}
?>