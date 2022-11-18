<?php

namespace Merconis\Core;

class dashboard extends \BackendModule {

	protected $strTemplate = 'lsm_dashboard';
	
	
	protected function compile() {
		$obj_themeInstaller = ThemeInstaller::getInstance();
		$this->Template->str_themeInstallerOutput = $obj_themeInstaller->parse();
	}
}
?>